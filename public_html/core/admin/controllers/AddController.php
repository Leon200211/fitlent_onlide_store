<?php


namespace core\admin\controllers;

use core\base\settings\Settings;



// контроллер для добавление информации через админ панель
class AddController extends BaseAdmin
{

    // определение действия
    protected $action = 'add';

    protected function inputData()
    {

        if(!$this->userId) $this->execBase();

        // Работа с данными из Post
        $this->checkPost();

        $this->createTableData();

        // получение данных из связных таблиц
        $this->createForeignData();

        // формируем позиции меню
        $this->createMenuPosition();

        $this->createRadio();

        // разбор колонок на блоки
        $this->createOutputData();


//        $this->data = [
//          'name' => 'masa',
//          'keywords' => 'Ключевики',
//            'img' => '1.png',
//            'gallery_img' => json_encode(['2.png', '3.png', '4.png'])
//        ];

    }




    // получение данных из связных таблиц
    protected function createForeignProperty($arr, $rootItems){
        if(in_array($this->table, $rootItems['tables'])){
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }




        $orderData = $this->createOrderData($arr['REFERENCED_TABLE_NAME']);




        // если таблица ссылается сама на себя
        if($this->data){
            if($arr['REFERENCED_TABLE_NAME'] === $this->table){
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                $operand[] = "!=";
            }
        }

        // получаем имя поля на которое ссылаемся из внешней таблицы
        $foreign = $this->model->read($arr['REFERENCED_TABLE_NAME'], [
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $orderData['name'], $orderData['parent_id']],
            'where' => $where,
            'operand' => $operand,
            'order' => $orderData['order'],
        ]);

        if($foreign){

            // если это не первое значение
            if($this->foreignData[$arr['COLUMN_NAME']]){
                foreach ($foreign as $value){
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            }else{
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }

        }
    }

    // получение данных из связных таблиц
    protected function createForeignData($settings = false){

        if(!$settings) $settings = Settings::getInstance();

        $rootItems = $settings::get('rootItems');

        // получаем все внешние ключи
        $keys = $this->model->showForeignKeys($this->table);

        if($keys){

            foreach ($keys as $item){
                $this->createForeignProperty($item, $rootItems);
            }

        }elseif ($this->columns['parent_id']){        // если нет никаких внешних ключей

            $arr['COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            $arr['REFERENCED_TABLE_NAME'] = $this->table;

            $this->createForeignProperty($arr, $rootItems);

        }

        return;

    }


    // метод для формирования позиция меню
    protected function createMenuPosition($settings = false){

        // если есть menu_position
        if($this->columns['menu_position']){

            if(!$settings) $settings = Settings::getInstance();

            $rootItems = $settings::get('rootItems');

            if($this->columns['parent_id']){

                if(in_array($this->table, $rootItems['tables'])){
                    $where = '`parent_id` IS NULL OR `parent_id` = 0';
                }else{

                    $parent = $this->model->showForeignKeys($this->table, 'parent_id');
                    if($parent){

                        if($this->table === $parent[0]['REFERENCED_TABLE_NAME']){
                            $where = '`parent_id` IS NULL OR `parent_id` = 0';
                        }else{

                            $columns = $this->model->showColumns($parent[0]['REFERENCED_TABLE_NAME']);

                            if($columns['parent_id']) $order[] = 'parent_id';
                            else $order[] = $parent[0]['REFERENCED_COLUMN_NAME'];

                            $id =  $this->model->read($parent[0]['REFERENCED_TABLE_NAME'], [
                                'fields' => [$parent[0]['REFERENCED_COLUMN_NAME']],
                                'order' => $order,
                                'limit' => '1'
                            ])[0][$parent[0]['REFERENCED_COLUMN_NAME']];

                            // сортировка
                            if($id) $where = ['parent_id' => $id];

                        }

                    }else{
                        $where = '`parent_id` IS NULL OR `parent_id` = 0';
                    }

                }

            }

            // отработка полученных данных
            $menu_pos = $this->model->read($this->table, [
               'where' => $where,
               'no_concat' => true
            ])[0]['count'] + 1;   // +1 потому что мы добавляем через сайт

            for($i = 1; $i <= $menu_pos; $i++){
                $this->foreignData['menu_position'][$i - 1]['id'] = $i;
                $this->foreignData['menu_position'][$i - 1]['name'] = $i;
            }

        }

        return;

    }


}