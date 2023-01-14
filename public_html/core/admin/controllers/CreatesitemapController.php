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

    protected $SITE_URL = 'http:/cpa.fvds.ru';

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
        $this->parsing($this->SITE_URL);


        // строим карту сайта
        $this->createSitemap();


        !$_SESSION['res']['answer'] && $_SESSION['res']['asnwer'] = '<div class="success">Sitemap is created!</div>';

        // редиректим на страницу вызова
        $this->redirect();

    }



    // метод для парсинга сайта
    protected function  parsing($url, $index = 0){


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
            $this->linkArr = array_values($this->linkArr);  // пересобираем ключи

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


        // поиск ссылок
        preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/uis', $out, $links);
        // если получили ссылки
        if(isset($links[2])){

            foreach ($links[2] as $link){

                if($link ==='/' or $link === $this->SITE_URL . '/'){
                    continue;
                }

                // проходим по исключающим расширениям
                foreach ($this->fileArr as $ext){

                    if($ext){

                        $ext = addslashes($ext);
                        $ext = str_replace('.', '\.', $ext);
                        // если нашли ссылку на файл с расширение из списка
                        if(preg_match('/' . $ext . '\s*?$/ui', $link)){
                            continue 2;
                        }

                    }

                }


                // проверка на относительную ссылку
                if(mb_strpos($link, '/') === 0){
                    $link = $this->SITE_URL . $link;
                }


                // проверка на внесение этой ссылки в наш список
                if(!in_array($link, $this->linkArr) and $link !== '#' and mb_strpos($link, $this->SITE_URL) === 0){
                    // проверяем ссылку фильтром
                    if($this->filter($link)){
                        $this->linkArr[] = $link;

                        // рекурсивно вызываем parsing, но уже для полученной ссылки, чтобы пройтись по всему сайту целиком
                        $this->parsing($link, count($this->linkArr) - 1);

                    }

                }

            }


        }

    }


    // метод по фильтрации ссылок
    protected function filter($link){

        return true;

    }


    protected function createSitemap(){

    }



}