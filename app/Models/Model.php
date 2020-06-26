<?php

namespace Models;

use PDO;

/**
 * Абстрактный класс для взаимодействия моделей с базой данных
 *
 * Содержит только статические свойства и методы
 *
 */
class Model
{
    /**
     * экземпляр PDO, предоставляющий соединение с базой данных
     */
    private static $_db;

    /**
     * Подключение к БД
     *
     * @param resource $file путь к файлу с настройками БД
     * @return bool
     */
    public static function initDb(resource $file = null) : bool
    {
        $file = $file ?? ROOT_DIR.'settings.ini';
        if ($config = parse_ini_file($file, TRUE)){
            try {
                $dns = $config['database']['driver'] . ':host=' . $config['database']['host'] . ';dbname=' . $config['database']['dbname'] . ';charset=utf8';
                self::$_db = new PDO($dns, $config['database']['user'], $config['database']['pass']);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @return PDO
     */
    protected static function getDb() : ?PDO
    {
        if(is_null(self::$_db)) self::initDb();
        return self::$_db;
    }

    /**
     * Получение количества записей в таблице
     *
     * @param array $params параметры для формирования условий оператора WHERE
     * @return int
     */
    public static function count(array $params = null) : int
    {
        $result = 0;
        if (empty($params)) {
            $result = self::getDb()->query("SELECT COUNT(*) as count FROM ".static::$table)->fetchColumn();
        } else {
            $where = static::convertParamsToQuery($params);
            $query = self::getDb()->prepare("SELECT COUNT(*) as count FROM " . static::$table . $where['str']);
            if(!empty($where['data']) && $query->execute($where['data']))
                $result = $query->fetchColumn();
        }
        return $result;
    }

    /**
     * Получение списка записей
     *
     * @param array $params параметры для формирования условий оператора WHERE
     * @param int $count параметр для оператора LIMIT
     * @param int $start параметр для оператора OFFSET
     * @param string $column параметр для оператора ORDER BY
     * @param bool $desk задать сортировку по убыванию
     * @return array массив объектов модели
     */
    public static function get(array $params = null, int $count=null, int $start=0, string $column=null, bool $desk=null) : array
    {
        // получение количества записей в таблице
        $rows = static::count($params);
        $result = [];
        if($rows > 0) {
            $limit_str = '';
            if($count > 0){
                $limit_str .= " LIMIT $count ";
            }
            if($start > 0){
                $limit_str .= " OFFSET $start ";
            }
            $sort_str = '';
            if($column != null){
                $sort_str = $desk ? " ORDER BY $column DESC " : " ORDER BY $column ";
            }

            if (empty($params)) {
                $result = self::getDb()->query("SELECT * FROM " . static::$table . $sort_str . $limit_str)->fetchAll();
            } else {
                $where = static::convertParamsToQuery($params);
                $query = self::getDb()->prepare("SELECT * FROM " . static::$table . $where['str'] . $sort_str . $limit_str);
                if (!empty($where['data']) && $query->execute($where['data'])) {
                    $items = $query->fetchAll();
                    $model = 'Models\\' . static::$model;
                    if (class_exists($model)) {
                        // возвращает массив обЪектов модели
                        foreach ($items as $item) {
                            $result[] = new $model($item);
                        }
                    } else {
                        $result = $items;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Получение записей по id
     *
     * @param int $id
     * @return Model объект модели
     */
    public static function getById(int $id) : ?Model
    {
        if($result = self::getDb()->query("SELECT * FROM ".static::$table." WHERE id=$id LIMIT 1")->fetch()){
            $model = 'Models\\'.static::$model;
            if(class_exists($model)) return new $model($result);
            return $result;
        }
        return null;
    }

    /**
     * Добавление новой записи
     *
     * @param array $data данные для формирования запроса
     * @return int id созданной записи
     */
    protected static function insert(array $data) : ?int
    {
        if(empty($data)) return null;
        $query_fields = '';
        $query_values = '';
        $query_data = [];
        foreach ($data as $key => $val){
            if(is_null($val) || is_array($val) || is_object($val)) continue;
            if(!empty($query_fields)) $query_fields .= ', ';
            if(!empty($query_values)) $query_values .= ', ';
            $query_fields .= "`$key`";
            $query_values .= ":$key";
            $query_data[":$key"] = is_bool($val) ? (int)$val : $val;
        }
        if(self::getDb()->prepare("INSERT INTO ".static::$table." ($query_fields) VALUES ($query_values)")->execute($query_data)){
            return self::getDb()->lastInsertId();
        }
        return null;
    }

    /**
     * Обновление записи
     *
     * @param int $id
     * @param array $data данные для формирования запроса
     * @return bool true если запрос прошел успешно
     */
    protected static function update(int $id, array $data) : bool
    {
        if(empty($data) || !static::getById($id))
            return false;
        $query_str = '';
        $query_data = [];
        foreach ($data as $key => $val){
            if(is_null($val) || is_array($val) || is_object($val)) continue;
            if(!empty($query_str)) $query_str .= ', ';
            $query_str .= "`$key` = :$key";
            $query_data[":$key"] = is_bool($val) ? (int)$val : $val;
        }
        if(self::getDb()->prepare("UPDATE ".static::$table." SET $query_str WHERE id = $id")->execute($query_data)){
            return true;
        }
        return false;
    }

    /**
     * Удаление записи
     *
     * @param int $id
     * @return bool true если запрос прошел успешно
     */
    public static function delete(int $id) : bool
    {
        if(self::getDb()->query("DELETE FROM ".static::$table." WHERE id = $id")){
            return true;
        }
        return false;
    }

    /**
     * Преобразует параметры для формирования условий оператора WHERE в строку
     *
     * @param array $params параметры для формирования условий оператора WHERE
     * @return string строка условий с оператором WHERE
     */
    private static function convertParamsToQuery(array $params) : array
    {
        $result = ['str' => '', 'data' => []];
        $param_str = '';
        $search_str = '';
        foreach ($params as $key => $val){
            if($key == '_SEARCH_'){
                if(is_array($val)){
                    foreach ($val as $s_key => $s_val){
                        if(is_null($s_val) || is_array($s_val) || is_object($s_val)) continue;
                        if(!empty($search_str)) $search_str .= " or ";
                        $data_key = ":s_$s_key";
                        $search_str .= " `$s_key` like $data_key ";
                        $result['data'][$data_key] = "%$s_val%";
                    }
                }
                continue;
            };
            if(is_null($val) || is_array($val) || is_object($val)) continue;
            if(!empty($param_str)) $param_str .= ', ';
            $param_str .= "`$key` = :$key";
            $result['data'][":$key"] = is_bool($val) ? (int)$val : $val;
        }
        if(!empty($param_str) && !empty($search_str)){
            $result['str'] = " WHERE ($param_str) and ($search_str) ";
        }elseif (!empty($param_str)){
            $result['str'] = " WHERE ($param_str) ";
        }elseif (!empty($search_str)){
            $result['str'] = " WHERE ($search_str) ";
        }
        return $result;
    }

//TODO Перенести миграции в отдельный файл для консольной команды
    /**
     * Создание таблиц БД
     *
     * @return void
     */
    public static function runMigrations() : void
    {
        $db = self::getDb();
        try
        {
            //  таблица Пользователей
            $db->query("CREATE TABLE IF NOT EXISTS users(
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                login VARCHAR(255) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL
                ) CHARACTER SET utf8 COLLATE utf8_general_ci;");
            //  таблица Задач
            $db->query("CREATE TABLE IF NOT EXISTS tasks(
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                `text` MEDIUMTEXT NOT NULL,
                `status` BOOLEAN NOT NULL DEFAULT false,
                created_at DATETIME NOT NULL,
                updated_at DATETIME
                ) CHARACTER SET utf8 COLLATE utf8_general_ci;");
        }
        catch(PDOException $e)
        {
            die("Error: ".$e->getMessage());
        }
    }

}