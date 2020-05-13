<?php

namespace Models;

class User extends Model
{
    protected static $table = 'users';
    protected static $model = 'User';

    public
        $login,
        $email;
    protected
        $id,
        $password;

    public function __construct(array $data=[])
    {
        if($data) {
            $this->id = $data['id'] ?? null;
            $this->login = $data['login'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->password = $data['password'] ?? null;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    /*Регистрация пользователя*/
    public static function register($email, $password, $login)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $email = strtolower($email);
        //если запись прошла успешно вернуть созданного пользователя
        $data =[
            'login' => $login,
            'email' => $email,
            'password' => $password,
        ];
        if($id = static::insert($data)){
            return static::getById($id);
        }
        return false;
    }

    /*Авторизация пользователя*/
    public static function login($login, $password)
    {
        $result = null;
        $login = strtolower($login);

        $data =[
            'login' => $login
        ];
        if(!empty($result = static::get($data))){
            $result = array_shift($result);
        }else{
            $data =[
                'email' => $login
            ];
            if(!empty($result = static::get($data))){
                $result = array_shift($result);
            }
        }

        if($result){
            if(!password_verify($password, $result->password)){
                $result = null;
            }
        }

        //если запрос прошел успешно вернуть объект User
        return $result;
    }

}