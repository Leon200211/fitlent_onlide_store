<?php


namespace core\base\controllers;


use core\base\settings\Settings;


// класс для работы с Ajax запросами
class BaseAjax extends BaseController
{

    // метод для определения контроллера
    public function route(){

        $route = Settings::get('routes');


        $controller = $route['user']['path'] . 'AjaxController';

        $data = $this->isPost() ? $_POST : $_GET;

        if(isset($data['ADMIN_MODE'])){
            unset($data['ADMIN_MODE']);
            $controller = $route['admin']['path'] . 'AjaxController';
        }


        $ajax = new $controller;
        $ajax->createAjaxData($data);

        return ($ajax->ajax());

    }


    protected function createAjaxData($data){
        $this->data = $data;
    }

}
