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
                $this->parsing($links);
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


        // очищаем массив
        if($this->all_links){
            foreach ($this->all_links as $key => $link){
                if(!$this->filter($link)) unset($this->all_links[$key]);
            }
        }


        // строим карту сайта
        $this->createSitemap();


        !$_SESSION['res']['answer'] && $_SESSION['res']['asnwer'] = '<div class="success">Sitemap is created!</div>';

        // редиректим на страницу вызова
        $this->redirect();

    }



    // метод для парсинга сайта
    protected function parsing($urls){

        if(!$urls) return;
        $urls = (array)$urls;


        // инициализируем CURL
        $curl = [];
        $curlMulty = curl_multi_init();  // дескриптор
        foreach ($urls as $i => $url){

            $curl[$i] = curl_init();
            curl_setopt($curl[$i], CURLOPT_URL, $url);
            curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);  // получаем ответы
            curl_setopt($curl[$i], CURLOPT_HEADER, true);  // ответы заголовков
            curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, true);  // следовать ли за редиректами
            curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120);  // время ожидания от сервера
            curl_setopt($curl[$i], CURLOPT_RANGE, 0 - 4194304);  // ограничиваем объем данных на загрузку (4 Mb)
            curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate');  // для разбора gzip сжатия страницы

            curl_multi_add_handle($curlMulty, $curl[$i]);  // добавляем новый поток

        }


        do{
            $status = curl_multi_exec($curlMulty, $active);
            $info = curl_multi_info_read($curlMulty);

            if(false !== $info){
                if($info['result'] !== 0){

                    $i = array_search($info['handle'], $curl);
                    $error = curl_errno($curl[$i]);
                    $message = curl_error($curl[$i]);
                    $header = curl_getinfo($curl[$i]);

                    // если возникла ошибка
                    if($error != 0){
                        $this->cancel(0, 'Error loading ' . $header['url'] .
                            ' http code: ' . $header['http_code'] . ' error: ' . $error .
                            ' message: ' . $message);
                    }

                }
            }

            if($status > 0){
                $this->cancel(0, curl_multi_strerror($status));
            }

        }while($status === CURLM_CALL_MULTI_PERFORM || $active);


        $result = [];
        // получаем инфу от каждого дескриптора
        foreach ($urls as $i => $url){
            $result[$i] = curl_multi_getcontent($curl[$i]);
            // удаляем дескриптор из потока
            curl_multi_remove_handle($curlMulty, $curl[$i]);
            // уничтожаем дескриптор
            curl_close($curl[$i]);


            // ищем заголовки
            if(!preg_match('/Content-Type:\s+text\/html/uis', $result[$i])){
                $this->cancel(0, 'Incorrect content type: ' . $url);
                continue;
            }


            // проверяем код ответа
            if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $result[$i])){
                $this->cancel(0, 'Incorrect server code: ' . $url);
                continue;
            }

            $this->createLinks($result[$i]);

        }

        curl_multi_close($curlMulty);

    }


    // метод по сохранению ссылок
    protected function createLinks($content){

        if($content){
            // поиск ссылок
            preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/uis', $content, $links);
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
                        $this->temp_links[] = $link;
                        $this->all_links[] = $link;
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


    // метод для создания карты сайта из ссылок
    protected function createSitemap(){

        // Представляет все содержимое HTML- или XML-документа; служит корнем дерева документа.
        $dom = new \domDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        // корневой элемент
        $root = $dom->createElement('urlset');

        // атрибуты коневого элемента
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:xls', 'http://w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

        $dom->appendChild($root);

        $sxe = simplexml_import_dom($dom);

        if($this->all_links){

            // текущая дата в определенном формате
            $date = new \DateTime();
            $lastMod = $date->format('Y-m-d') . 'T' . $date->format('H:i:s+01:00');

            foreach ($this->all_links as $item){

                $elem = trim(mb_substr($item, mb_strlen(SITE_URL)), '/');
                $elem = explode('/', $elem);

                // приоритет обхода
                $count = '0.' . (count($elem) - 1);
                $priority = 1 - (float)$count;

                if($priority == 1){
                    $priority = '1.0';
                }

                // добавляем элементы
                $urlMain = $sxe->addChild('url');
                $urlMain->addChild('loc', htmlspecialchars($item));
                $urlMain->addChild('lastmod', $lastMod);
                $urlMain->addChild('changefreq', 'weekly');
                $urlMain->addChild('priority', $priority);

            }

        }

        $dom->save($_SERVER['DOCUMENT_ROOT'] . PATH . 'sitemap.xml');

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