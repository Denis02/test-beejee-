<?php

$url = parse_url($_SERVER['REQUEST_URI']);
$page = $url["path"];

switch ($page){
    case '/':
        (new \Controllers\TaskController())->index();
        break;
    //Ajax-запросы
    case '/api/action-task':
        (new \Controllers\TaskController())->action();
        break;
    case '/api/create-task':
        (new \Controllers\TaskController())->create();
        break;
    case '/api/edit-task':
        (new \Controllers\TaskController())->edit();
        break;
    case '/api/delete-task':
        (new \Controllers\TaskController())->delete();
        break;

    // Авторизация и регистрация пользователей
    case '/login':
        (new \Controllers\AuthController())->login();
        break;
    case '/logout':
        (new \Controllers\AuthController())->logout();
        break;
    case '/registration':
        (new \Controllers\AuthController())->registration();
        break;

    default:
        echo '404';
        header("HTTP/1.0 404 Not Found");
        exit();
}
