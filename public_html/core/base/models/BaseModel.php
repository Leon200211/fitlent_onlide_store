<?php


namespace core\base\models;


use core\base\exceptions\DbException;


// класс для работы с базовой моделью
abstract class BaseModel extends BaseModelMethods
{



    protected $db;

    protected function connect()
    {
        // подключение к БД
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

        // отлов и логирование ошибок
        if($this->db->connect_error){
            throw new DbException("Ошибка подключение к БД: " .
                $this->db->connect_errno . " " . $this->db->connect_error);
        }

        $this->db->query("SET NAMES UTF8");

    }


    // базовый метод обращения к БД
    // запрет переопределения у наследников
    /**
     * @param $query
     * @param string $crud = c - INSERT / r - SELECT / u - UPDATE / d - DELETE
     * @param false $return_id
     * @return array|bool|int|string
     * @throws DbException
     */
    final public function my_query($query, $crud = 'r', $return_id = false){

        $result = $this->db->query($query);

        if($this->db->affected_rows === -1){
            throw new DbException("Ошибка в SQL запросе: $query - {$this->db->errno} {$this->db->error}");
        }

        switch ($crud){

            case 'r':
                if($result->num_rows){
                    $res = [];

                    for($i = 0; $i < $result->num_rows; $i++){
                        $res[] = $result->fetch_assoc();
                    }
                    return $res;
                }
                return false;
                break;

            case 'c':

                if($return_id){
                    // вернуть id вставленного элемента
                    return $this->db->insert_id;
                }
                return true;
                break;

            default:
                return true;
                break;


        }
    }



    // чтение из БД
    /**
     * @param $table - Таблица из БД
     * @param array $set
    $res = $db->read($table, [
        'fields' => ['id', 'name'],
        'no_concat' => false/true  Если true не присоединять имя таблицы к полям и where
        'where' => [
            'name' => 'leon, as, ad',
            'id' => 'Leon , sadd, sd',
            'fio' => 'test',
            'cat' => 'with',
            'color' => ['red', 'blue', 'black']
        ],
        'operand' => ['IN', 'NOT IN', 'LIKE%', '=', 'IN'],
        'condition' => ['AND', 'OR'],
        'order' => ['id', 'name'],
        'order_direction' => ['ASC', 'DESC'],
        'limit' => '2',
        'join' => [
            [
            'table' => 'join_table1',
            'fields' => ['id as j_id', 'name as j_name'],
            'type' => 'left',
            'where' => ['name' => 'leon'],
            'operand' => ['='],
            'condition' => ['OR'],
            'on' => ['id', 'namessea']
        ],
        'join_table2' => [
            'table' => 'join_table1',
            'fields' => ['id as j2_id', 'name as j2_name'],
            'type' => 'left',
            'where' => ['name' => 'leon'],
            'operand' => ['!='],
            'condition' => ['OR'],
            'on' => [
                'table' => 'join_table1',
                'fields' => ['id', 'namessea']
            ]
        ]
    ]
     */
    public function read($table, $set = []){

        // получение полей
        $fields = $this->createFields($set, $table);
        // строим запрос
        $where = $this->createWhere($set, $table);

        if(!isset($where)){
            $new_where = true;
        }else{
            $new_where = false;
        }

        // массив join
        $join_arr = $this->createJoin($set, $table, $new_where);


        // объединяем запрос
        $fields .= $join_arr['fields'];
        $where .= $join_arr['where'];
        $join = $join_arr['join'];

        // удаляем последнюю запятую
        $fields = rtrim($fields, ',');

        // сортировку в запросе
        $order = $this->createOrder($set, $table);

        // лимит записей
        $limit = @$set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        // запрос
        $query = "SELECT $fields FROM $table $join $where $order $limit";
        // Вызов базового метода обращения к БД
        return $this->my_query($query, 'r');

    }



    // функция для добавления записи в таблицу
    final public function add($table, $set = []){

        $set['fields'] = (!empty($set['fields']) and is_array($set['fields']))
            ? $set['fields'] : $_POST;
        $set['files'] = (!empty($set['files']) and is_array($set['files']))
            ? $set['files'] : false;

        if(!$set['fields'] and !$set['files']){
            return false;
        }

        $set['return_id'] = !empty($set['return_id']) ? true : false;
        $set['except'] = (!empty($set['except']) and is_array($set['except']))
            ? $set['except'] : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        $query = "INSERT INTO $table {$insert_arr['fields']} VALUE {$insert_arr['values']}";

        return $this->my_query($query, 'c', $set['return_id']);

    }



    // функция для редактирования данных таблицы
    final public function update($table, $set = []){

        $set['fields'] = (!empty($set['fields']) and is_array($set['fields']))
            ? $set['fields'] : $_POST;
        $set['files'] = (!empty($set['files']) and is_array($set['files']))
            ? $set['files'] : false;

        if(!$set['fields'] and !$set['files']){
            return false;
        }

        $set['except'] = (!empty($set['except']) and is_array($set['except']))
            ? $set['except'] : false;

        $where = '';

        // all_rows - обновить все поля в таблице
        if(!isset($set['all_rows'])){
            if(isset($set['where'])){
                $where = $this->createWhere($set);
            }else{
                $columns = $this->showColumns($table);
                // если нет полей
                if(!$columns){
                    return false;
                }


                if($columns['id_row'] and $set['fields'][$columns['id_row']]){
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];

                    // удаляем поле
                    unset($set['fields'][$columns['id_row']]);
                }

            }
        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

        $query = "UPDATE $table SET $update $where";

        return $this->my_query($query, 'u');

    }



    // функция для удаления записи из таблицы в Базе данных
    // метод может удалять с использованием группировки таблиц
    final public function delete($table, $set){

        $table = trim($table);

        $where = $this->createWhere($set, $table);

        $columns = $this->showColumns($table);
        if(!$columns){
            return false;
        }

        if(isset($set['fields']) and is_array($set['fields']) and !empty($set['fields'])){

            if($columns['id_row']){
                // поиск первичного ключа
                $key = array_search($columns['id_row'], $set['fields']);
                // строгое не равенство потому что если индекс будет 0 он будет false
                if($key !== false){
                    unset($set['fields'][$key]);
                }
            }
            
            $fields = [];

            foreach ($set['fields'] as $field) {
                $fields[$field] = $columns[$field]['Default'];
            }


            $update = $this->createUpdate($fields, false, false);

            $query = "UPDATE $table SET $update $where";

        }else{

            $join_arr = $this->createJoin($set, $table);
            $join = $join_arr['join'];
            $join_tables = $join_arr['tables'];

            $query = 'DELETE ' . $table . $join_tables . " FROM " . $table . ' ' . $join . ' ' . $where;

        }

        return $this->my_query($query, 'u');

    }


    // метод возвращающий список всех таблиц из БД
    final public function showTables(){
        $query = 'SHOW TABLES';

        $tables = $this->my_query($query);

        $tables_arr = [];

        if($tables){
            foreach ($tables as $table){
                // reset — Устанавливает внутренний указатель массива на его первый элемент
                $tables_arr[] = reset($table);
            }
        }

        return $tables_arr;

    }


    // метод возвращает список всех полей в таблице
    final public function showColumns($table){
        $query = "SHOW COLUMNS FROM $table";
        $res = $this->my_query($query);
        
        $columns = [];
        if($res){
            foreach ($res as $column) {
                $columns[$column['Field']] = $column;
                // если это первичный ключ
                if($column['Key'] === 'PRI'){
                    $columns['id_row'] = $column['Field'];
                }
            }
        }

        return $columns;
    }


}