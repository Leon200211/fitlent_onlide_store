<?php


namespace core\base\controllers;


trait Singleton
{

    static private $_instance;

    private function __construct(){

    }

    private function __clone(){

    }

    static public function getInstance(){
        if(self::$_instance instanceof self){  // проверка существует ли уже объект класса
            return self::$_instance;
        }


        self::$_instance = new self;


        // проверяем есть ли экземпляра метод connect, если есть, то вызываем его ( для работы с моделями )
        if(method_exists(self::$_instance, 'connect')){
            self::$_instance->connect();
        }


        return self::$_instance;  // если еще нет объекта, создать
    }

}