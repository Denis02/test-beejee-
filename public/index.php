<?php

session_start();
date_default_timezone_set('europe/kiev');

// Константы проекта
define('PUBLIC_DIR',getcwd().'/');
define('ROOT_DIR',getcwd().'/../');
define('RESOURCES_DIR',getcwd().'/../resources/');
define('APP_DIR',getcwd().'/../app/');

// Автозагрузка классов
spl_autoload_register(function (string $class){
    $file = APP_DIR.str_replace('\\','/',$class).'.php';
    if(is_file($file)){
        require_once $file;
    }
});

// Глобальная функция для вывода страницы
function view(string $content, array $data = null, string $layout='app'){
    ob_start();
    include_once RESOURCES_DIR.'views/'.$content.'.php';
    $content = ob_get_clean();
    include_once RESOURCES_DIR.'views/layouts/'.$layout.'.php';
    exit();
}

// Глобальная функция для Ajax ответа
function json_response($data, int $code = null){
    header('Content-type: application/json');
    if($code){
        $code_str = "HTTP/1.0 ";
        switch ($code){
            case 400:
                $code_str .= '400 Bad Request';
                break;
            case 401:
                $code_str .= '401 Unauthorized';
                break;
            default:
                $code_str .= '200 OK';
        }
        header($code_str);
    }
    echo json_encode($data);
    exit();
}

// Глобальная функция для проверки авторизации пользователя
function auth(){
    if(isset($_SESSION['user']) && isset($_SESSION['ip'])){
        if($_SESSION['ip'] == $_SERVER['REMOTE_ADDR']) return true;
    }
    return false;
}

// Запуск миграций базы данных
// Раскомментировать при первом запуске на новом сервере
//\Models\Model::runMigrations();

// Подключение файла маршрутизации
require_once APP_DIR.'routes.php';
