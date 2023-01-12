<?php


namespace libraries;


// класс для обработки файлов
class FileEdit
{

    protected $imgArr = [];
    protected $directory;


    public function addFile($directory = false){

        if(!$directory) $this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR;
            else $this->directory = $directory;



        foreach ($_FILES as $key => $file){

            // если пришел не один файл
            if(is_array($file['name'])){

                $file_arr = [];

                for($i = 0; $i < count($file['name']); $i++){

                    if(!empty($file['name'][$i])){

                        $file_arr['name'] = $file['name'][$i];
                        $file_arr['type'] = $file['type'][$i];
                        $file_arr['tmp_name'] = $file['tmp_name'][$i];
                        $file_arr['error'] = $file['error'][$i];
                        $file_arr['size'] = $file['size'][$i];

                        $res_name = $this->createFile($file_arr);

                        if($res_name) $this->imgArr[$key][] = $res_name;

                    }

                }

            }else{      // если пришел 1 файл

                if($file['name']){

                    $res_name = $this->createFile($file);

                    if($res_name) $this->imgArr[$key] = $res_name;

                }

            }

        }

        return $this->getFiles();

    }


    // метод обработки названия файлов
    protected function createFile($file){

        $fileNameArr = explode('.', $file['name']);
        $ext = $fileNameArr[count($fileNameArr) - 1]; // записываем расширение файла
        unset($fileNameArr[count($fileNameArr) - 1]);

        // создаем массив из строки
        $fileName = implode('.', $fileNameArr);

        // вернет корректное имя файла
        $fileName = (new TextModify())->translit($fileName);

        // чтобы названия не повторялись
        $fileName = $this->checkFile($fileName, $ext);


        // формируем полный путь к файлу
        $fileDestination = $this->directory . $fileName;

        if($this->uploadFile($file['tmp_name'], $fileDestination)){
            return $fileName;
        }

        return false;

    }


    // метод загрузки файла на сервер
    protected function uploadFile($tmpName, $fileDestination){

        if(move_uploaded_file($tmpName, $fileDestination)){
            return true;
        }

        return false;

    }


    // метод для решения проблемы повторения файлов
    public function checkFile($fileName, $ext, $fileLastName = ''){

        // если такого файла нет
        if(!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext)){
            return $fileName . $fileLastName . '.' . $ext;
        }else{  // если файл с таким именем уже есть
            return $this->checkFile($fileName, $ext, '_' . hash('crc32', time() . mt_rand(1, 1000)));  // передаем хеш рандомной строки
        }

    }


    // метод геттер для файлов
    public function getFiles(){
        return $this->imgArr;
    }



}