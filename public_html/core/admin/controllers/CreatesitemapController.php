<?php


namespace core\admin\controllers;



use core\base\controllers\BaseMethods;



// контроллер для создания карты сайта
class CreatesitemapController extends BaseAdmin
{

    use BaseMethods;

    protected $all_links = [];  // массив с ссылками
    protected $temp_links = [];  // массив временных ссылок

    protected $maxLinks = 5000;  // максимальное число ссылок, которые curl будет обрабатывать за раз

    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'docx', 'mp4', 'mp3', 'mpeg'];

    protected $filterArr = [
        'url' => ['order'],
        'get' => []
    ];



    protected function inputData($links_counter = 1){

        // если у нас не установлена библиотека curl
        if(!function_exists('curl_init')){
            $this->cancel(0, 'Отсутствует библиотека CURL', '', true);
        }

        // проверка на админа
        if(!$this->userId) $this->execBase();

        // проверка на таблицу в БД
        if(!$this->checkParsingTable()){
            $this->cancel(0, 'Проблема с БД в таблице parsing_data', '', true);
        }

        // снимает время ограничения работы скрипта
        set_time_limit(0);


        $reserve = $this->model->read('parsing_data')[0];
        foreach ($reserve as $name => $item){
            if($item) $this->$name = json_decode($item);
                else $this->$name = [SITE_URL];
        }

        //$this->temp_links = ['http:/cpa.fvds.ru'];

        // макс число ссылок в зависимости от глубины рекурсии
        $this->maxLinks = (int)$links_counter > 1 ? ceil($this->maxLinks / $links_counter) : $this->maxLinks;

        // подсчет количества полученных ссылок
        while ($this->temp_links){
            $temp_links_count = count($this->temp_links);
            $links = $this->temp_links;
            $this->temp_links = [];

            // проверяем на допустимое число ссылок за раз
            if($temp_links_count > $this->maxLinks){
                // array_chunk — Разбивает массив на части
                $links = array_chunk($links, ceil($temp_links_count / $this->maxLinks));
                $count_chunks = count($links);

                for ($i = 0; $i < $count_chunks; $i++){
                    $this->parsing($links[$i]);
                    unset($links[$i]);

                    // сохраняем данные, которые еще не прошли парсинг, на случай ошибки
                    if($links){
                        // Деструктурирующее присваивание – это специальный синтаксис,
                        // который позволяет нам «распаковать» массивы или объекты в несколько переменных
                        $this->model->update('parsing_data', [
                            'fields' => [
                                'all_links' => json_encode($this->all_links),
                                'temp_links' => json_encode(array_merge(...$links))
                            ]
                        ]);
                    }

                }

            }else{
                $this->parsing($links[0]);
            }

            $this->model->update('parsing_data', [
                'fields' => [
                    'all_links' => json_encode($this->all_links),
                    'temp_links' => json_encode($this->temp_links)
                ]
            ]);

        }

        $this->model->update('parsing_data', [
            'fields' => [
                'all_links' => '',
                'temp_links' => ''
            ]
        ]);

        // строим карту сайта
        $this->createSitemap();


        !$_SESSION['res']['answer'] && $_SESSION['res']['asnwer'] = '<div class="success">Sitemap is created!</div>';

        // редиректим на страницу вызова
        $this->redirect();

    }



    // метод для парсинга сайта
    protected function parsing($url, $index = 0){


        // инициализируем CURL
        $curl = curl_init();  // дескриптор

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // получаем ответы
        curl_setopt($curl, CURLOPT_HEADER, true);  // ответы заголовков
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);  // следовать ли за редиректами
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);  // время ожидания от сервера
        curl_setopt($curl, CURLOPT_RANGE, 0 - 4194304);  // ограничиваем объем данных на загрузку (4 Mb)


        // отправка CURL запроса
        $out = curl_exec($curl);

        // уничтожаем дескриптор
        curl_close($curl);


        // ищем заголовки
        if(!preg_match('/Content-Type:\s+text\/html/uis', $out)){

            unset($this->all_links[$index]);
            $this->all_links = array_values($this->all_links);  // пересобираем ключи

            return;

        }


        // проверяем код ответа
        if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $out)){

            $this->writeLog('Не корректная ссылка при парсинге - ' . $url, $this->parsingLogFile);

            unset($this->all_links[$index]);
            $this->all_links = array_values($this->all_links);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap is created' . '</div>';

            return;

        }


        // поиск ссылок
        preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/uis', $out, $links);
        // если получили ссылки
        if(isset($links[2])){

            foreach ($links[2] as $link){

                if($link ==='/' or $link === SITE_URL . '/'){
                    continue;
                }

                // проходим по исключающим расширениям
                foreach ($this->fileArr as $ext){

                    if($ext){

                        $ext = addslashes($ext);
                        $ext = str_replace('.', '\.', $ext);
                        // если нашли ссылку на файл с расширение из списка
                        if(preg_match('/' . $ext . '(\s*?$|\?[^\/]*$)/ui', $link)){
                            continue 2;
                        }

                    }

                }


                // проверка на относительную ссылку
                if(mb_strpos($link, '/') === 0){
                    $link = SITE_URL . $link;
                }

                // сохраняем ссылку на сайт, с экранированными точной и слешем
                $site_url = mb_str_replace('.', '\.', mb_str_replace('/', '\/', SITE_URL));

                // проверка на внесение этой ссылки в наш список
                if(!in_array($link, $this->all_links) and !preg_match('/^(' . $site_url . ')?\/?#[^\/]*?$/ui', $link) and mb_strpos($link, SITE_URL) === 0){
                    // проверяем ссылку фильтром
                    if($this->filter($link)){
                        $this->all_links[] = $link;

                        // рекурсивно вызываем parsing, но уже для полученной ссылки, чтобы пройтись по всему сайту целиком
                        $this->parsing($link, count($this->all_links) - 1);

                    }

                }

            }


        }


    }


    // метод по фильтрации ссылок
    protected function filter($link){


        if($this->filterArr){

            foreach ($this->filterArr as $type => $values){

                if($values){

                    foreach ($values as $item){

                        $item = mb_str_replace('/', '\/', addslashes($item));

                        // если проверяем url
                        if($type === 'url'){
                            if(preg_match('/^[^\?]*' . $item . '/ui', $link)){
                                return false;
                            }
                        }

                        // если проверяем get параметры
                        if($type === 'get'){

                            if(preg_match('/(\?|&amp;|=|&)' . $item . '(=|&amp;|&|$)/ui', $link)){
                                return false;
                            }

                        }


                    }

                }

            }

        }


        return true;

    }


    protected function createSitemap(){

    }


    // метод для проверки наличия таблицы парсинга в БД
    protected function checkParsingTable(){

        $tables = $this->model->showTables();

        // если в БД нет такой таблицы
        if(!in_array('parsing_data', $tables)){

            $query = 'CREATE TABLE parsing_data (all_links text, temp_links text)';

            if(!$this->model->my_query($query, 'c') or
                !$this->model->add('parsing_data', ['fields' => ['all_links' => '', 'temp_links' => '']])
            ){ return false; }

        }

        return true;

    }


    // метод для завершения скрипта
    protected function cancel($success = 0, $message = '', $log_message = '', $exit = false){

        $exitArr = [];

        $exitArr['success'] = $success;
        $exitArr['message'] = $message ?: 'ERROR PARSING';

        $log_message = $log_message ?: $exitArr['message'];

        // тип вывода сообщения пользователю
        $class = 'success';

        if(!$exitArr['success']){
            // если на момент вызова файл с прошлыми логами остался, то удаляем его
//            if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile)){
//                @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);
//            }

            $class = 'error';
            $this->writeLog($log_message, 'parsing_log.txt');
        }

        if($exit){
            $exitArr['message'] = '<div class="' . $class . '">' . $exitArr['message'] . '</div>';
            exit(json_encode($exitArr));
        }

    }


}