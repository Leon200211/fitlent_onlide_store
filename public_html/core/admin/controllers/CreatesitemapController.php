<?php


namespace core\admin\controllers;



use core\base\controllers\BaseMethods;



// контроллер для создания карты сайта
class CreatesitemapController extends BaseAdmin
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
        if(!function_exists('curl_init')){
            $this->writeLog('Отсутствует библиотека CURL');
            $_SESSION['res']['answer'] = '<div class="error">Library CURL as absent. Creation of sitemap impossible!</div>';
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


        // проверяем на конечный слеш в адресе сайта
        if(mb_strlen(SITE_URL) + 1 === mb_strlen($url) and mb_strrpos($url, '/') === mb_strlen($url) - 1){
            return;
        }


        // инициализируем CURL
        $curl = curl_init();  // дескриптор

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // получаем ответы
        curl_setopt($curl, CURLOPT_HEADER, true);  // ответы заголовков
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);  // следовать ли за редиректами
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);  // время ожидания от сервера
        curl_setopt($curl, CURLOPT_RANGE, 0 - 4194304);  // ограничиваем объем данных на загрузку ( 4 Mb)


        // отправка CURL запроса
        $out = curl_exec($curl);

        // уничтожаем дескриптор
        curl_close($curl);


        // ищем заголовки
        if(!preg_match('/Content-Type:\s+text\/html/uis', $out)){

            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            return;

        }


        // проверяем код ответа
        if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $out)){

            $this->writeLog('Не корректная сслыка при парсинге - ' . $url, $this->parsingLogFile);

            unset($this->linkArr[$index]);
            $this->linkArr = array_values($this->linkArr);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap is created' . '</div>';

            return;

        }



    }


    // метод по фильтрации ссылок
    protected function filter($link){

    }


    protected function createSitemap(){

    }



}