<?php

namespace Models;

/**
 * Модель задачи
 *
 * Статические свойства $table и $model с названиями таблицы и модели,
 * необходимые для использования методов родительского класса Model:
 * count(), get(), getById(), insert(), update(), delete().
 * Публичные свойства соответствуют названиям столбцов в таблице БД
 */
class Task extends Model
{
    protected static $table = 'tasks';
    protected static $model = 'Task';

    public
        $id,
        $username,
        $email,
        $text,
        $status,
        $created_at,
        $updated_at;

    public function __construct(array $data=[])
    {
        if($data) {
            $this->init($data);
        }
    }

    /**
     * Присвоение значений свойствам текущей задачи
     *
     * @return void
     */
    protected function init(array $data) : void
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->text = $data['text'] ?? null;
        $this->status = $data['status'] ?? false;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
    }

    /**
     * Сохранение текущей задачи
     *
     * Метод создает новую запись в БД или обновляет существующую
     *
     * @return bool
     */
    public function save() : bool
    {
        if(isset($this->id) && ($original_task = static::getById($this->id))){
            $data = [ 'status' => $this->status ];
            if($original_task->text != $this->text){
                $data['text'] = $this->text;
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            if(static::update($this->id, $data)){
                $this->updated_at = $data['updated_at'];
                return true;
            }
        }else{
            $data = self::toArray();
            $data['created_at'] = date('Y-m-d H:i:s');
            if($id = static::insert($data)){
                $this->id = $id;
                $this->created_at = $data['created_at'];
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'text' => $this->text,
            'status' => $this->status
        ];
    }

    /**
     * Статический метод для формирования данных поиска
     *
     * @param string $query Строка поиска
     * @return array [название_столбца => подстрока_поиска]
     */
    public static function getSearchParams (string $query) :array
    {
        $data = [
            '_SEARCH_' => [
                'username' => $query,
                'email' => $query,
                'text' => $query,
            ]
        ];
        return $data;
    }

}