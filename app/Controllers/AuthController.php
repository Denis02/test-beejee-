<?php

namespace Controllers;

use Models\User;

class AuthController
{
    /*Регистрация нового пользователя*/
    public function registration(){
        //перенаправление авторизированного пользователя
        if(auth())
            return header('Location: /');
        //POST-запрос
        if(isset($_POST['login']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['password_confirmation'])){
            if($_POST['password'] == $_POST['password_confirmation']) {
                //если регистрация прошла успешно перенаправить на метод Авторизации
                if (User::register($_POST['email'], $_POST['password'], $_POST['login'])) {
                    return $this->login();
                }
            }
        }
        //отображение страницы регистрации
        return view ('auth/registration');
    }

    /*Авторизация пользователя*/
    public function login(){
        //перенаправление авторизированного пользователя
        if(auth()) {
            if(isset($_SERVER['HTTP_REFERER']))
                return header('Location: '.$_SERVER['HTTP_REFERER']);
            return header('Location: /');
        }
        //POST-запрос
        if(isset($_POST['login']) && isset($_POST['password'])){
            //если авторизация прошла успешно перенаправить на Главную страницу
            if($user = User::login($_POST['login'],$_POST['password'])){
                $_SESSION['user'] = serialize($user);
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
                return header('Location: /');
            }
        }
        $data = [];
        if($_POST)
            $data['message'] = 'Неверный логин или пароль!';

        return view('auth/login', $data);
    }

    /*Разлогинивание пользователя*/
    public function logout(){
        unset($_SESSION['user']);
        unset($_SESSION['ip']);
        if(isset($_SERVER['HTTP_REFERER']))
            return header('Location: '.$_SERVER['HTTP_REFERER']);
        return header('Location: /');
    }

}