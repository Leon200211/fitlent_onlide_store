<?php


namespace core\base\settings;


use core\base\controllers\Singleton;


// класс настроек
// используется Паттерн проектирования Singleton (Одиночка)
class Settings
{

    use Singleton;

    // геттер для получения данных
    static public function get($property){
        return self::getInstance()->$property;
    }



    // настройки пути
    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controllers/',
            'hrUrl' => false,
            'routes' => [

            ]
        ],
        'settings' => [
            'path' => 'core/base/settings/'
        ],
        'plugins' => [
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => false
        ],
        'user' => [
            'path' => 'core/user/controllers/',
            'hrUrl' => true,
            'routes' => [

            ]
        ],
        'default' => [
            'controller' => 'IndexController',
            'inputMethod' => 'inputData',
            'outputMethod' => 'outputData'
        ]
    ];

    // расширение
    private $expansion = 'core/admin/expansion/';

    private $projectTables = [
        'articles' => ['name' => 'Переводsdf таблицы', 'img' => 'pages.png'],
        'teacher' => ['name' => 'Переводsdf sdfsfsfтаблицы'],
    ];

    private $defaultTable = 'articles';

    // путь к служебным сообщениям
    private $messages = 'core/base/messages/';


    // путь к шаблонам
    private $formTemplates = PATH . 'core/admin/views/include/form_templates/';

    private $templateArr = [
        'text' => ['name'],
        'textarea' => ['content', 'keywords'],
        'radio' => ['visible'],
        'checkboxlist' => ['filters'],
        'select' => ['menu_position', 'parent_id'],
        'img' => ['img'],
        'gallery_img' => ['gallery_img']
    ];

    // для админ панели
    private $blockNeedle = [
        'vg-rows' => [],
        'vg-img' => ['img', 'gallery_img'],
        'vg-content' => ['content']
    ];

    // для работы со связью n:m
    private $manyToMany = [
        'goods_filters' => ['articles', 'teacher']  // 'type' => 'child' || 'root'
    ];

    // для перевода названия таблиц
    private $translate = [
        'name' => ['Название', 'Не более 100 символов'],
        'content' => []
    ];

    // для работы с внешними данными, в рамках одной таблицы
    private $rootItems = [
        'name' => 'Корневая',
        'tables' => ['articles', 'teacher']
    ];

    // словарь для radio
    private $radio = [
        'visible' => ['Нет', 'Да', 'default' => 'Да']
    ];


    // массив полей для валидации
    private $validation = [
        'name' => ['empty' => true, 'trim' => true, 'count' => 100],
        'price' => ['int' => true],
        'login' => ['empty' => true, 'trim' => true],
        'password' => ['crypt' => true, 'empty' => true],
        'keywords' => ['count' => 70, 'trim' => true],
        'description' => ['count' => 160, 'trim' => true],
    ];


    // для склейки полей
    public function clueProperties($class){
        $baseProperties = [];

        foreach ($this as $name => $item) {
            $property = $class::get($name);

            if(is_array($property) and is_array($item)){
                //$baseProperties[$name] = array_merge_recursive($this->$name, $property); не подойдет для многомерных массивов
                //$baseProperties[$name] = array_replace_recursive($this->$name, $property); Заменяет везде значения, не подходит
                // поэтому придется написать свой метод arrayMergeRecursive
                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;
            }

            if(!$property){
                $baseProperties[$name] = $this->$name;
            }
        }

        return $baseProperties;

    }


    // Метод для склейки массивов с заменой значения по текстовому ключу
    public function arrayMergeRecursive(){

        $arrays = func_get_args();  // получение аргументов функции

        $base = array_shift($arrays);  // возвращает первый элемент массива и удаляет его из исходного массива

        foreach ($arrays as $array){
            foreach ($array as $key => $value){
                if(is_array($value) and @is_array($base[$key])){
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                }else{
                    if(is_int($key)){
                        // склейка
                        // если еще нет такого элемента, то добавим его
                        if(!in_array($value, $base)){
                            array_push($base, $value);
                        }
                        continue;
                    }
                    // замена
                    $base[$key] = $value;
                }
            }
        }
        return $base;

    }

}