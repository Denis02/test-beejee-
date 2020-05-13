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
        if ($params == null) {
            $rows = self::getDb()->query("SELECT COUNT(*) as count FROM ".static::$table)->fetchColumn();
        } else {
            $where_str = static::convertParamsToString($params);
            $rows = self::getDb()->query("SELECT COUNT(*) as count FROM " .static::$table . $where_str)->fetchColumn();
        }
        return $rows;
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
            $where_str = '';
            if(!empty($params) && is_array($params)){
                $where_str = static::convertParamsToString($params);
            }
            $items = self::getDb()->query("SELECT * FROM " . static::$table . $where_str . $sort_str . $limit_str)->fetchAll();
            $model = 'Models\\'.static::$model;
            if(class_exists($model)){
                // возвращает массив обЪектов модели
                foreach ($items as $item) {
                    $result[] = new $model($item);
                }
            }else{
                $result = $items;
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
        $fields_str = '';
        $values_str = '';
        foreach ($data as $key => $val){
            if(is_null($val)) continue;
            if(!empty($fields_str)) $fields_str .= ', ';
            if(!empty($values_str)) $values_str .= ', ';
            $fields_str .= "`$key`";
            if(is_bool($val)){
                $values_str .= $val ? 1 : 0;
            }elseif (is_string($val)){
                $values_str .= self::getDb()->quote($val);
            }else{
                $values_str .= $val;
            }
        }
        if($res=self::getDb()->query("INSERT INTO ".static::$table." ($fields_str) VALUES ($values_str)")){
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
        $data_str = '';
        foreach ($data as $key => $val){
            if(is_null($val)) continue;
            if(!empty($data_str)) $data_str .= ', ';
            $_val = $val;
            if(is_bool($val)){
                $_val = $val ? 1 : 0;
            }elseif (is_string($val)){
                $_val = self::getDb()->quote($val);
            }
            $data_str .= "`$key` = $_val";
        }
        if(self::getDb()->query("UPDATE ".static::$table." SET $data_str WHERE id = $id")){
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
    private static function convertParamsToString(array $params) : string
    {
        $result = '';
        $param_str = '';
        $search_str = '';
        foreach ($params as $key => $val){
            if($key == '_SEARCH_'){
                if(is_array($val)){
                    foreach ($val as $s_key => $s_val){
                        if(!empty($search_str)) $search_str .= " or ";
                        $search_str .= " `$s_key` like " . self::getDb()->quote("%$s_val%") . " ";
                    }
                }
                continue;
            };
            if(!empty($param_str)) $param_str .= ', ';
            $_val = $val;
            if(is_bool($val)){
                $_val = $val ? 1 : 0;
            }elseif (is_string($val)){
                $_val = self::getDb()->quote($val);
            }
            $param_str .= "`$key` = $_val";
        }
        if(!empty($param_str) && !empty($search_str)){
            $result = " WHERE ($param_str) and ($search_str) ";
        }elseif (!empty($param_str)){
            $result = " WHERE ($param_str) ";
        }elseif (!empty($search_str)){
            $result = " WHERE ($search_str) ";
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