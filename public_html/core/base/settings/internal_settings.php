<?php


defined('VG_ACCESS') or die('Access denied');


// константы
const TEMPLATE = 'templates/default/';
const ADMIN_TEMPLATE = 'core/admin/views/';
const UPLOAD_DIR = 'userfiles/';

const COOKIE_VERSION = '1.0.0';
const CRYPT_KEY = '';
const COOKIE_TIME = 60;
const BLOCK_TIME = 3;

const QTY = 8;
const QTY_LINKS = 3;

const ADMIN_CSS_JS = [
    'style' => ['css/main.css'],
    'scripts' => ['js/frameworkfunction.js', 'js/scripts.js']
];

const USER_CSS_JS = [
    'style' => [],
    'scripts' => []
];


use core\base\exceptions\RouteException;  // импортируем пространство имен для исключения
// для автоматического импортирование классов, не зависимо от их нахождения
function autoloadMainClasses($class_name){

    $class_name = str_replace('\\', '/', $class_name);

    if(!@include_once $class_name . '.php'){  // знак @ игнорирует ошибки вызванные в условии
        throw new RouteException('Не верное имя файла для подключения - ' . $class_name);
    }
}

spl_autoload_register('autoloadMainClasses');



