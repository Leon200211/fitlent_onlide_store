<?php


namespace core\base\models;

// класс хранит вспомогательные методы для crud
abstract class BaseModelMethods
{


    // массив встроенных функций в mySql
    protected $mySql_function = ['NOW()'];

    protected $tableRows;

    // ===================================================
    // Для SELECT
    // ===================================================

    // группировка всех полей для вывода и работы
    protected function createFields($set, $table = false, $join = false){
        // проверка на существование полей
//        if(empty($set['fields'])){
//            return '*';
//        }
//        $set['fields'] = (!empty($set['fields']) and is_array($set['fields']))
//            ? $set['fields'] : '*';


        // проверяем нужно ли нам возвращать значения
        if(array_key_exists('fields', $set) && $set['fields'] === null){
            return '';
        }

        $concat_table = '';
        $alias_table = $table;

        if(!$set['no_concat']){
            $arr = $this->createTableAlias($table);

            $concat_table = $arr['alias'] . '.';
            $alias_table = $arr['alias'];
        }


        // проверяем нужно ли структурировать данные
        $join_structure = false;
        if(($join || isset($set['join_structure'])) && $set['join_structure'] && $table){
            $join_structure = true;

            $this->showColumns($table);

            if(!isset($this->tableRows[$table]['multi_id_row'])){
                $set['fields'] = [];
            }

        }

        $fields = '';


        if(!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']){

            if(!$join){
                $fields = $concat_table . "*,";
            }else{
                foreach ($this->tableRows[$alias_table] as $key => $item){
                    if($key !== 'id_row' && $key !== 'multi_id_row'){
                        $fields .= $concat_table . $key . ' as TABLE' . $alias_table . 'TABLE_' . $key . ',';
                    }
                }
            }

        }else{

            $id_field = false;

            foreach ($set['fields'] as $field){

                if($join_structure && !$id_field && $this->tableRows[$alias_table] === $field){
                    $id_field = true;
                }

                if($field){

                    if($join && $join_structure){

                        if(preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)){
                            $fields .= $concat_table . $matches[1] . ' as TABLE' . $alias_table . 'TABLE_' . $matches[2] . ',';
                        }else{
                            $fields .= $concat_table . $field . ' as TABLE' . $alias_table . 'TABLE_' . $field . ',';
                        }

                    }else{
                        $fields .= $concat_table . $field . ',';
                    }

                }

            }

            if(!$id_field && $join_structure){

                if($join){
                    $fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ' as TABLE' . $alias_table . 'TABLE_' . $this->tableRows[$alias_table]['id_row'] . ',';
                }else{
                    $fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ',';
                }

            }

        }

        return $fields;

    }


    // создание запроса для конструкции Where
    protected function createWhere($set, $table = false, $instruction = 'WHERE'){

        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
            ? $this->createTableAlias($table)['alias'] . '.' : '';

        $where = '';

        if(is_string($set['where'])){
            return $instruction . ' ' . trim($set['where']);
        }

        if(!empty($set['where']) and is_array($set['where'])){

            // пришли ли операнды
            $set['operand'] = (!empty($set['operand']) and is_array($set['operand'])) ? $set['operand'] : ['='];
            // пришли ли условия
            $set['condition'] = (!empty($set['condition']) and is_array($set['condition'])) ? $set['condition'] : ['AND'];

            $where = $instruction;

            $o_count = 0;
            $c_count = 0;

            foreach ($set['where'] as $key => $item){

                $where .= " ";

                // определяем операнд
                if(isset($set['operand'][$o_count])){
                    $operand = $set['operand'][$o_count];
                    $o_count++;
                }else{
                    $operand = $set['operand'][$o_count-1];
                }
                // определяем условие
                if(isset($set['condition'][$c_count])){
                    $condition = $set['condition'][$c_count];
                    $c_count++;
                }else{
                    $condition = $set['condition'][$c_count-1];
                }

                if($operand === 'IN' or $operand === 'NOT IN'){
                    if(is_string($item) and strpos($item, 'SELECT') === 0){
                        $in_str = $item;
                    }else{
                        if(is_array($item)){
                            $temp_item = $item;
                        }else{
                            $temp_item = explode(',', $item);
                        }
                        $in_str = '';

                        foreach ($temp_item as $v){
                            $in_str .= "'" . addslashes(trim($v)) . "',";
                        }
                    }

                    $where .= $table . $key . ' ' . $operand . " (" . rtrim($in_str, ',') . ") " . $condition;

                }elseif(strpos($operand, 'LIKE') !== false){
                    $like_template = explode('%', $operand);

                    foreach ($like_template as $lt_key => $lt){
                        if(!$lt){
                            if(!$lt_key){
                                $item = '%' . $item;
                            }else{
                                $item .= '%';
                            }
                        }
                    }

                    $where .= $table . $key . " LIKE '" . addslashes($item) . "' $condition";

                }else {
                    // проверка на подзапросы
                    if(strpos($item, 'SELECT') === 0){
                        $where .= $table . $key . $operand . '(' . $item . ') ' . $condition;
                    }else{
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' " . $condition;
                    }

                }

            }

            // убираем последнее условие
            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;

    }


    // создание join запроса
    protected function createJoin($set, $table, $new_where = false){

        $fields = '';
        $join = '';
        $where = '';


        if(isset($set['join'])){

            $join_table = $table;

            foreach ($set['join'] as $key => $item) {

                if(is_int($key)){
                    if(!$item['table']){
                        continue;
                    }else{
                        $key = $item['table'];
                    }
                }

                // таблица для join
                $concatTable = $this->createTableAlias($key)['alias'];

                if($join){
                    $join .= ' ';
                }

                if(isset($item['on']) and $item['on']){
                    $join_fields = [];

                    if(isset($item['on']['fields']) and is_array($item['on']['fields']) and count($item['on']['fields']) === 2){
                        $join_fields = $item['on']['fields'];
                    }else if(count($item['on']) === 2){
                        $join_fields = $item['on'];
                    }else{
                        // для скипа этой итерации в которые мы вложены
                        //continue 2;
                        continue;
                    }

                    // выбираем какой join использоваться
                    if(!$item['type']){
                        $join .= 'LEFT JOIN ';
                    }else{
                        $join .= trim(strtoupper($item['type'])) . ' JOIN ';
                    }

                    $join .= $key . ' ON ';

                    // проверка с какой таблицей стыковаться
                    if(@$item['on']['table']){
                        $join_temp_table = $item['on']['table'];
                    }else{
                        $join_temp_table = $join_table;
                    }

                    // добавляем таблицу для join
                    $join .= $this->createTableAlias($join_temp_table)['alias'];

                    // указания полей для стыковки
                    $join .= '.' . $join_fields[0] . '=' . $concatTable . '.' . $join_fields[1];

                    $join_table = $key;


                    if($new_where){
                        if($item['where']){
                            $new_where = false;
                        }
                        $group_condition = 'WHERE';
                    }else{
                        $group_condition = isset($item['group_condition']) ? strtoupper($item['group_condition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key, $set['join_structure']);
                    $fields = ',' . $fields;

                    $where .= $this->createWhere($item, $key, $group_condition);

                }
            }
        }

        return compact('fields', 'join', 'where');

    }


    // создание запроса сортировки
    protected function createOrder($set, $table = false){

        $table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
            ? $this->createTableAlias($table)['alias'] . '.' : '';

        $order_by = '';
        if(isset($set['order']) and $set['order']){

            // если пришла строка, преобразуем в массив для корректного выполнения скрипта
            $set['order'] = (array)$set['order'];

            $set['order_direction'] = (isset($set['order_direction']) and $set['order_direction'])
                ? (array)$set['order_direction'] : ['ASC'];

            $order_by = 'ORDER BY ';
            $direct_count = 0;
            foreach ($set['order'] as $order){
                // направление сортировки
                if(@$set['order_direction'][$direct_count]){
                    $order_direction = strtoupper($set['order_direction'][$direct_count]);
                    $direct_count++;
                }else{
                    $order_direction = strtoupper($set['order_direction'][$direct_count-1]);
                }

                // если отсортировать нужно используя встроенные функции MySQL
                if(in_array($order, $this->mySql_function)){
                    $order_by .= $order . ',';
                }

                // сортировка по числовым данным
                if(is_int($order)){
                    $order_by .= $order . ' ' . $order_direction . ',';
                }else{
                    $order_by .= $table . $order . ' ' . $order_direction . ',';
                }

            }

            // обрезаем запятую
            $order_by = rtrim($order_by, ',');
        }
        return $order_by;
    }





    // ===================================================
    // Для INSERT
    // ===================================================

    protected function createInsert($fields, $files, $except){

        $insert_arr = [];


        $insert_arr['fields'] = "(";
        $insert_arr['values'] = "";

        // проверка на многомерный массив
        $array_type = array_keys($fields)[0];
        if(is_int($array_type)){

            $check_fields = false;
            $count_fields = 0;


            foreach ($fields as $i => $item){

                $insert_arr['values'] .= "(";

                // во всех множествах должно быть одинаковое кол-во элементов
                if(!$count_fields){
                    $count_fields = count($item);
                }


                // счетчик количества полей в создании записи
                $j = 0;

                foreach ($item as $row => $value){

                    // если в подмножестве есть то, что нужно пропустить
                    if($except and in_array($row, $except)) continue;


                    if(!$check_fields) $insert_arr['fields'] .= $row . ',';

                    // если передаем функции sql
                    if(in_array($value, $this->mySql_function)){
                        $insert_arr['values'] .= $value . ',';
                    }elseif ($value == "NULL" or $value === NULL){
                        $insert_arr['values'] .= "NULL" . ',';
                    }else{
                        $insert_arr['values'] .= "'" . addslashes($value) . "',";
                    }

                    $j++;

                    // проверка на кол-во полей в записе
                    if($j === $count_fields) break;

                }

                // если след. запись меньше чем $count_fields, то добавим пустые ячейки
                if($j < $count_fields){
                    for(; $j < $count_fields; $j++){
                        $insert_arr['values'] .= "NULL";
                    }
                }

                // удаляем запятую в конце одной записи и ставим запятую перед след записью
                $insert_arr['values'] = rtrim($insert_arr['values'], ',') . "),";

                // сохранили название полей для insert
                if(!$check_fields) $check_fields = true;

            }

        }else{

            $insert_arr['fields'] = "(";
            $insert_arr['values'] = "(";

            if($fields){
                foreach ($fields as $row => $value){
                    // если вставить кроме
                    if($except and in_array($row, $except)) continue;

                    @$insert_arr['fields'] .= $row . ',';

                    if(in_array($value, $this->mySql_function)){
                        $insert_arr['values'] .= $value . ',';
                    }elseif ($value == "NULL" or $value === NULL){
                        $insert_arr['values'] .= "NULL" . ',';
                    }else{
                        $insert_arr['values'] .= "'" . addslashes($value) . "',";
                    }

                }
            }

            if($files){
                foreach ($files as $row => $file){

                    @$insert_arr['fields'] .= $row . ',';

                    if(is_array($file)){
                        @$insert_arr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                    }else{
                        @$insert_arr['values'] .= "'" . addslashes($file) . "',";
                    }

                }
            }

            // удаляем запятую в конце одной записи и закрываем скобку
            $insert_arr['values'] = rtrim($insert_arr['values'], ',') . ")";

        }


        // удаляем запятую в конце
        $insert_arr['fields'] = rtrim($insert_arr['fields'], ',') . ')';
        $insert_arr['values'] = rtrim($insert_arr['values'], ',');

        return $insert_arr;

    }



    // ===================================================
    // Для UPDATE
    // ===================================================
    protected function createUpdate($fields, $files, $except){

        $update = '';

        if($fields){
            foreach ($fields as $row => $value) {

                // если обновить кроме
                if($except and in_array($row, $except)) continue;

                $update .= $row . "=";

                if(in_array($value, $this->mySql_function)){
                    $update .= $value . ',';
                }else if($value === NULL){
                    $update .= "NULL" . ',';
                }else{
                    $update .= "'" . addslashes($value) . "',";
                }

            }
        }

        if($files){
            foreach ($files as $row => $file){

                $update .= $row . '=';

                if(is_array($file)){
                    $update .= "'" . addslashes(json_encode($file)) . "',";
                }else{
                    $update .= "'" . addslashes($file) . "',";
                }
            }
        }

        return rtrim($update, ',');

    }


    // метод для структурирования данных
    protected function joinStructure($res, $table){

        $join_arr = [];

        $id_row = $this->tableRows[$this->createTableAlias($table)['alias']]['id_row'];


        foreach ($res as $value) {
            if($value){

                if(!isset($join_arr[$value[$id_row]])){
                    $join_arr[$value[$id_row]] = [];
                }

                foreach ($value as $key => $item){

                    // заполняем результирующий массив
                    if(preg_match('/TABLE(.+)?TABLE/u', $key, $matches)){
                        // получаем нормализованное имя таблицы
                        $table_name_normal = $matches[1];

                        // сохраняем первичный ключ таблицы, если он не является мультиключом
                        if(!isset($this->tableRows[$table_name_normal]['multi_id_row'])){
                            $join_id_row = $value[$matches[0] . '_' . $this->tableRows[$table_name_normal]['id_row']];
                        }else {

                            $join_id_row = '';

                            foreach ($this->tableRows[$table_name_normal]['multi_id_row'] as $multi){
                                $join_id_row .= $value[$matches[0] . '_' . $multi];
                            }

                        }

                        // получаем чистый ключ
                        $row = preg_replace('/TABLE(.+)TABLE_/u', '', $key);

                        // проверяем на дубляж
                        if($join_id_row && !isset($join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row])){
                            $join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row] = $item;
                        }

                        continue;

                    }

                    // если работаем не с join таблицей
                    $join_arr[$value[$id_row]][$key] = $item;

                }

            }
        }

        return $join_arr;

    }


    // метод для создания псевдонимов таблиц
    protected function createTableAlias($table){

        $arr = [];

        // если нашли пробелы в названии таблицы
        if(preg_match('/\s+/i', $table)){

            $table = preg_replace('/\s{2,}/i', ' ', $table);

            $table_name = explode(' ', $table);

            // имя таблицы
            $arr['table'] = trim($table_name[0]);
            // псевдоним таблицы
            $arr['alias'] = trim($table_name[1]);

        }else{

            $arr['alias'] = $arr['table'] = $table;

        }

        return $arr;

    }



}