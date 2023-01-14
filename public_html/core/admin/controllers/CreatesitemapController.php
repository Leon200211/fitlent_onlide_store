<?php


namespace core\admin\controllers;


use core\base\controllers\BaseMethods;



// контроллер для создания карты сайта
class CreatesitemapController
{

    use BaseMethods;

    protected $linkArr = [];  // массив с ссылками
    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'docx', 'mp4', 'mp3', 'mpeg'];

    protected $filterArr = [
        'url' => [],
        'get' => []
    ];



    protected function inputData(){

        // если у нас не установлена библиотека curl
        if(function_exists('curl_init')){
            $this->writeLog('Отсутствует библиотека CURL');
            $_SESSION['res']['answer'] = '<div class="error">Library CURL as apsent. Creation of sitemap imposible!</div>';
            $this->redirect();
        }


        // снимает время ограничения работы скрипта
        set_time_limit(0);

        // если на момент вызова файл с прошлыми логами остался, то удаляем его
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile)){
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);
        }


        // начинаем парсить
        $this->parsing(SITE_URL);


        // строим карту сайта
        $this->createSitemap();


        !$_SESSION['res']['answer'] && $_SESSION['res']['asnwer'] = '<div class="success">Sitemap is created!</div>';

        // редиректим на страницу вызова
        $this->redirect();

    }


    // метод для парсинга сайта
    protected function  parsing($url, $index = 0){



    }


    // метод по фильтрации ссылок
    protected function filter($link){

    }


    protected function createSitemap(){

    }



}