<?php


namespace core\base\controllers;


// класс для асинхронных запросов
class BaseRoute
{

    use Singleton;
    use BaseMethods;


    public static function routeDirection(){

        if(self::getInstance()->isAjax()){
            exit((new BaseAjax())->route());
        }

        RouteController::getInstance()->route();

    }


}