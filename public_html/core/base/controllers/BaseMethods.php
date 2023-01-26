<?php


namespace core\base\controllers;

// трейт с базовыми методами
trait BaseMethods
{

    // очистка строк
    protected function clearStr($str){

        if(is_array($str)){
            foreach ($str as $key=>$item){
                $str[$key] = trim(strip_tags($item));
            }
            return $str;
        }else{
            return trim(strip_tags($str));
        }

    }



    // отчистка числовых данных
    protected function clearNum($num){
        return $num * 1;
    }


    // проверка на post
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }


    // проверка на ajax
    protected function isAjax(){
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }


    // редирект страниц
    protected function redirect($http = false, $code = false){
        if($code){
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

            // существует ли такой элемент
            if($codes[$code]){
                header($codes[$code]);
            }
        }

        if($http){
            $redirect = $http;
        }else{
            $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;
        }

        header("Location: $redirect");

        exit;
    }


    // записываем логи
    protected function writeLog($message, $file = 'log.txt', $event = 'Fault'){

        $dataTime = new \DateTime();

        $str = $event . ': ' . $dataTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";

        file_put_contents('log/' . $file, $str, FILE_APPEND);

    }


    // метод для получения стилей
    public function getStyles(){
        if($this->styles){
            foreach ($this->styles as $style){
                echo '<link rel="stylesheet" href="' . $style . '">';
            }
        }
    }

    // метод для получения скриптов
    public function getScripts(){
        if($this->scripts){
            foreach ($this->scripts as $script){
                echo '<script src="' . $script . '"></script>';
            }
        }
    }


}