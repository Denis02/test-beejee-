<?php

namespace Controllers;

use Models\Task;

/**
 * Контроллер работы с задачами
 *
 * Выполняет обработку и вывод списка задач и операции с задачей
 *
 */
class TaskController
{
    /**
     * Вывод страницы списка задач
     */
    public function index()
    {
        return view('task_book/task_list');
    }

    /**
     * Получение списка задач
     * json данные для таблицы списка задач
     */
    public function action()
    {
        if(empty($_POST)) json_response([], 400);

        // обработка пользовательских данных
        $displayCount = (int)$_POST['iDisplayLength'];                                  // количество задач на странице
        $displayStart = (int)$_POST['iDisplayStart'];                                   // первая задача на странице
        $iSortCol = (int)$_POST['iSortCol_0'];                                          // индекс поля сортировки
        $columnName = htmlspecialchars($_POST['mDataProp_'.$iSortCol]);                 // поле сортировки
        $SortDesc = htmlspecialchars($_POST['sSortDir_0']) == 'desc' ? true : false;    // сортировка по убыванию
        $searchValue = htmlspecialchars($_POST['sSearch']);                             // строка поиска

        // массив с данными для поиска
        $searchQuery = [];
        if(!empty($searchValue)){
            $searchQuery = Task::getSearchParams($searchValue);
        }

        // количество всех задач и количество задач соответствующих строке поиска
        $response["iTotalRecords"] = Task::count();
        $response["iTotalDisplayRecords"] = empty($searchQuery) ? $response["iTotalRecords"] : Task::count($searchQuery);

        // массив с объектами Task для вывода на странице списка задач
        $response["aaData"] = Task::get($searchQuery, $displayCount, $displayStart, $columnName, $SortDesc);

        json_response($response);
    }

    /**
     * Создание новой задачи
     */
    public function create()
    {
        if(empty($_POST) || empty($_POST['username']) || empty($_POST['email']) || empty($_POST['text']))
            json_response(['message'=> 'Все поля должны быть заполнены!'], 400);

        // обработка пользовательских данных
        $task_data = [
            'username' => preg_match('/[A-Za-zА-Яа-я 0-9_-]{1,40}$/', $_POST['username']) ? $_POST['username'] : null,
            'email' => filter_var($_POST['email'], FILTER_VALIDATE_EMAIL),
            'text' => htmlspecialchars($_POST['text'])
        ];

        if(empty($task_data['username']))
            json_response(['message'=> 'Недопустимые символы в поле "Имя"!'], 400);
        if(empty($task_data['email']))
            json_response(['message'=> 'Введите валидный e-mail!'], 400);

        // создание и сохранение новой задачи
        $task = new Task($task_data);
        if($task->save()){
            json_response($task->id);
        }

        json_response(['message'=> 'Задача не сохранена!'], 400);
    }

    /**
     *  Редактирование задачи через
     */
    public function edit()
    {
        if(!auth())
            json_response([], 401);
        if(empty($_POST['taskId']) || empty($_POST['status']) || empty($_POST['text']))
            json_response(['message'=> 'Все поля должны быть заполнены!'], 400);

        $taskId = filter_var($_POST['taskId'], FILTER_VALIDATE_INT);
        $task = Task::getById($taskId);

        if(is_object($task) && !empty($_POST['text'])){
            $task->text = htmlspecialchars($_POST['text']);
            $task->status = filter_var($_POST['status'], FILTER_VALIDATE_BOOLEAN);

            if($task->save()){
                json_response($task);
            }
        }

        json_response(['message'=> 'Задача не сохранена!'], 400);
    }

    /**
     *  Удаление задачи
     */
    public function delete()
    {
        if(!auth())
            json_response([], 401);
        if (!auth() || empty($_POST) || empty($_POST['taskId']))
            json_response([], 400);

        $taskId = filter_var($_POST['taskId'], FILTER_VALIDATE_INT);
        $task = Task::getById($taskId);

        if ($task && Task::delete($taskId)) {
            json_response($task->id);
        }

        json_response([], 400);
    }



}