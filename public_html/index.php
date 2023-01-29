<?php
#/
// все запросы по ссылкам
// навигационные запросы по сайту


// константа безопасности
define('VG_ACCESS', true);


header('Content-Type:text/html;charset=utf-8'); // в какой кодировки пользователь обрабатывает данные (первый заголовок)
session_start(); //стартуем сессию


// отключаем сообщение о предупреждениях
//error_reporting(0);


require_once 'config.php';  // базовые настройки для хостинга
require_once 'core/base/settings/internal_settings.php';  // фундаментальные настройки сайта

use core\base\exceptions\RouteException;  // импортируем пространство имен для исключения
use core\base\exceptions\DbException;  // импортируем пространство имен для исключения БД
use \core\base\controllers\BaseRoute;  // роут контроллер



try{
    BaseRoute::routeDirection();
}
catch (RouteException $e){
    exit($e->getMessage());
}
catch (DbException $e){
    exit($e->getMessage());
}

