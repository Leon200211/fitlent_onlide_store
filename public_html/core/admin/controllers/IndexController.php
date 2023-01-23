<?php


namespace core\admin\controllers;



use core\base\controllers\BaseController;
use core\admin\models\Model;
use core\base\settings\Settings;


// индексный контроллер для админа
class IndexController extends BaseController
{

    protected function inputData(){

        $redirect = PATH . Settings::get('routes')['admin']['alias'] . '/show';
        $this->redirect($redirect);

    }

}