<?php
#/

namespace core\base\exceptions;


use core\base\controllers\BaseMethods;


// класс исключений
class RouteException extends \Exception
{

    protected $messages;

    use BaseMethods; // для метода writeLog

    // конструктор
    public function __construct($message = "", $code = 0)
    {

        parent::__construct($message, $code);

        $this->messages = include 'messages.php';

        // создаем сообщение для логов
        $error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];
        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n In line" . $this->getLine() . "\r\n";


        // возвращаем сообщение для пользователя
        if($this->messages[$this->getCode()]){
            //$this->message = $this->messages[$this->getCode()];
        }

        // запись логов
        $this->writeLog($error);

    }

}