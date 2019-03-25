<?php

class Db {

    // connection
    private $db_connection;
    private $db_info   =   array();
    private $prefix =  '';
    private static $db_connection_pool =  array();    // db connection pool

    // query
    private $table_list  =   array();   // table
    private $field_list =   array();    // field array(['a']=>array('name','id','date'));
    private $limit_start    =   0;
    private $limit_length    =   0;
    private $where_list  =   array();    // where condition
    private $group_list  =   array();    // group by field
    private $order_list  =   array();    // sorting order
    private $use_index  =   array();    // use index

    private $save   =   false;  // save property
    private $debug   =   false;

    /**
     * create new db instance
     *
     * @param string $connection_name
     * @throws Exception
     * @return void
     */
    public function __construct($connection_name='main'){
        if(!is_string($connection_name)){
            throw new Exception('no connection name');
        }
        if(empty(self::$db_connection_pool[$connection_name])){
            $db_config_path =  CONFIG_PATH.'/db.config.php';
            if(!is_file($db_config_path)){
                throw new Exception('no db config file');
            }
            include $db_config_path;
            if(
                empty($db_config[$connection_name]) ||
                empty($db_config[$connection_name]['host']) ||
                empty($db_config[$connection_name]['user']) ||
                empty($db_config[$connection_name]['password']) ||
                empty($db_config[$connection_name]['db'])
            ){
                throw new Exception('error db config file');
            }
            $this->db_info =  $db_config[$connection_name];
            if(!empty($db_config[$connection_name]['prefix'])){
                $this->prefix  =  $db_config[$connection_name]['prefix'];
            }
            self::$db_connection_pool[$connection_name]  =   $this->connect($this->db_info);
        }
        $this->db_connection   =  self::$db_connection_pool[$connection_name];
    }

    /**
     * create new db connection
     *
     * @param array $db_info
     * @throws Exception
     * @return mixed
     */
    private function connect($db_info){
        if(
            empty($db_info) ||
            empty($db_info['host']) ||
            empty($db_info['user']) ||
            empty($db_info['password']) ||
            empty($db_info['db'])
        ){
            throw new Exception('error db info');
        }
        $db_connection   =   mysqli_connect($db_info['host'], $db_info['user'], $db_info['password'], $db_info['db']);
        if(!$db_connection){
            throw new Exception('connect db failed');
        }
        if(!empty($db_info['charset']) && in_array($db_info['charset'],array('utf8'))){
            mysqli_query($db_connection, "SET NAMES '{$db_info['charset']}'");
        }
        return $db_connection;
    }

    /**
     * execute db query
     *
     * @param string $query
     * @throws Exception
     * @return mixed
     */
    public function query($query) {
        if (!$this->db_connection)
            return false;
        $result = mysqli_query($this->db_connection, $query);
        if($result===false){
            $error_no = mysqli_errno($this->db_connection);
            if($error_no==2006 || $error_no==2013){
                $this->close();
                $this->db_connection   =  $this->connect($this->db_info);
                if($this->db_connection){
                    $result = mysqli_query($this->db_connection, $query);
                    if($result){
                        return $result;
                    }else{
                        $error_no = mysqli_errno($this->db_connection);
                        $error_msg = mysqli_error($this->db_connection);
                        throw new Exception('query error : ['.$error_no.']'.$error_msg);
                    }
                }else{
                    $error_no = mysqli_errno($this->db_connection);
                    $error_msg = mysqli_error($this->db_connection);
                    throw new Exception('query error : ['.$error_no.']'.$error_msg);
                }
            }
        }else{
            if(!$this->save){
                $this->reset();
            }
            return $result;
        }

    }

    /**
     * add table table list
     *
     * @param string $table
     * @return Db
     */
    public function table($table) {
        if(!is_string($table))  return;
        // have as
        $as_table   =   null;
        if(stristr($table,' as ')){
            list($table,$as_table)  =   explode('as',$table);
            $table  =   trim($table);
            $as_table  =   trim($as_table);
        }
        $_table =   array('table'=>$table);
        if($as_table){
            $_table['as']   =   $as_table;
        }
        $this->table_list[] =   $_table;
        return $this;
    }

    /**
     * join table
     *
     * @param string $join_type
     * @param string $table
     * @param mixed $on
     * @throws Exception
     * @return Db
     */
    public function join($join_type,$table,$on) {
        $on_array =   array();
        if(is_string($on)){
            $on_array[] =   $on;
        }else if(is_array($on)){
            $on_array =   $on;
        }

        if(!in_array($join_type,array('LEFT','RIGHT','INNER','LEFT OUTER','RIGHT OUTER','CROSS'))){
            throw new Exception('error join type : '.$join_type);
        }

        // have as
        $as_table   =   null;
        if(stristr($table,' as ')){
            list($table,$as_table)  =   explode('as',$table);
            $table  =   trim($table);
            $as_table  =   trim($as_table);
        }
        $_table =   array('table'=>$table,'join'=>$join_type,'on'=>$on_array);
        if($as_table){
            $_table['as']   =   $as_table;
        }
        $this->table_list[] =   $_table;
        return $this;
    }

    /**
     * left join
     *
     * @param string $table
     * @param string $on
     * @return Db
     */
    public function leftJoin($table,$on){
        return call_user_func_array(array($this,'join'),array('LEFT',$table,$on));
    }

    /**
     * implode query string
     *
     * @return string
     */
    private function getTableQuery(){
        if(empty($this->table_list))    return;
        $table_query   =   '';
        foreach($this->table_list as $table){
            if(!empty($table['join'])){
                $table_query    .=  ' '.$table['join'].' JOIN ';
            }
            $table_query   .=   '`'.$this->prefix.$table['table'].'`';
            if(!empty($table['as'])){
                $table_query   .=  ' as `'.$table['as'].'`';
            }
            if(!empty($table['on'])){
                if(is_array($table['on'])){
                    $table_query    .=  ' ON '.implode(' AND ',$table['on']);
                }else if(is_string($table['on'])){
                    $table_query    .=  ' ON '.$table['on'];
                }
            }
        }
        return $table_query;
    }

    /**
     * add field to field list
     *
     * @param mixed
     * @return string
     */
    public function field() {
        $arg_count = func_num_args();
        if(!$arg_count) return;
        for($i=0;$i<$arg_count;$i++){
            $arg    =   func_get_arg($i);
            if(is_array($arg)){
                foreach($arg as $_arg){
                    if(!$_arg)  continue;
                    $this->_field($_arg);
                }
            }else if(is_string($arg)){
                $this->_field($arg);
            }
        }
        return $this;
    }

    /**
     * add field to field list
     *
     * @param string $field
     * @return void
     */
    private function _field($field) {
        if(!is_string($field))  return;
        // define as
        $table  =   $as_field   =   null;

        // require space around
        if(stristr($field,' as ')){
            list($field,$as_field)    =   explode(' as ',$field);
            $field  =   trim($field);
            $as_field   =   trim($as_field);
        }
        // define table
        if(!stristr($field,'(') && stristr($field,'.')){
            list($table,$field)  =   explode('.',$field);
            $table  =   trim($table);
            $field  =   trim($field);
        }

        $_field =   array('field'=>$field);
        if($table){
            $_field['table']    =   $table;
        }
        if($as_field){
            $_field['as']   =   $as_field;
        }
        $this->field_list[] =   $_field;
        return;
    }

    /**
     * implode field query from field list
     *
     * @return string
     */
    private function getFieldQuery(){
        if(empty($this->field_list))    return '*';

        $field_query_array =   array();
        foreach($this->field_list as $field){
            $field_query   =   '';
            if(!empty($field['table'])){
                $field_query   .=   '`'.$field['table'].'`.';
            }
            $field_query   .=   $field['field']=='*' ? '*' : ((preg_match("/^[a-z0-9\_]+$/i",$field['field'])) ? '`'.$field['field'].'`' : $field['field']);
            if(!empty($field['as'])){
                $field_query   .=   ' as `'.$field['as'].'`';
            }
            $field_query_array[]   =   $field_query;
        }
        return implode(',',$field_query_array);
    }


    /**
     * add where query
     *
     * @param mixed
     * @return Db
     */
    public function where() {
        $arg_count = func_num_args();
        if(!$arg_count) return;
        for($i=0;$i<$arg_count;$i++){
            $arg    =   func_get_arg($i);
            if(is_array($arg)){
                foreach($arg as $_arg){
                    if(!$_arg)  continue;
                    $this->where_list[] =   $_arg;
                }
            }else if(is_string($arg)){
                $this->where_list[]  = $arg;
            }
        }
        return $this;
    }

    /**
     * add where query with '?' and replace to param
     *
     * @param mixed
     * @throws Exception
     * @return Db
     */
    public function whereSafe() {
        $arg_count = func_num_args();
        if($arg_count<2){
            throw new Exception('where safe error arg count');
        }
        $source =   func_get_arg(0);
        if(!stristr($source,'?')){
            throw new Exception("safe where require needle '?'");
        }
        $_where_list  =   explode('?',$source);
        $_where_count   =   sizeof($_where_list);
        if($_where_count != $arg_count){
            throw new Exception("where safe count not matches");
        }
        $where  =   $_where_list[0];
        for($i=1;$i<sizeof($_where_list);$i++){
            $where  .=  $this->escapeString(func_get_arg($i)).$_where_list[$i];
        }
        $this->where_list[]  =   $where;
        return $this;
    }

    /**
     * implode where list to string
     *
     * @return string
     */
    private function getWhereQuery() {
        if($this->where_list){
            return ' WHERE ('.implode(') AND (',$this->where_list).')';
        }else{
            return ' WHERE 1';
        }
    }


    /**
     * add group list
     *
     * @param mixed
     * @return Db
     */
    public function group() {
        $arg_count = func_num_args();
        if(!$arg_count) return;
        for($i=0;$i<$arg_count;$i++){
            $arg    =   func_get_arg($i);
            if(is_array($arg)){
                foreach($arg as $_arg){
                    if(!$_arg)  continue;
                    $this->group_list[] =   $_arg;
                }
            }else if(is_string($arg)){
                $this->group_list[]   =   $arg;
            }
        }
        return $this;
    }


    /**
     * implode group list to group by query
     *
     * @return string
     */
    private function getGroupQuery() {
        if(!$this->group_list){
            return '';
        }
        $query_group_list   =   array();
        foreach($this->group_list as $group){
            $query_group_list[] =   $group;
        }
        return ' GROUP BY '.implode(',',$query_group_list);
    }

    /**
     * add order to order list
     *
     * @param mixed
     * @return Db
     */
    public function order() {
        $arg_count = func_num_args();
        if(!$arg_count) return;
        for($i=0;$i<$arg_count;$i++){
            $arg    =   func_get_arg($i);
            if(is_array($arg)){
                foreach($arg as $_arg){
                    if(!$_arg)  continue;
                    $this->_order($_arg);
                }
            }else if(is_string($arg)){
                $this->_order($arg);
            }
        }
        return $this;
    }

    /**
     * add order to order list
     *
     * @param string $order
     * @return string
     */
    private function _order($order) {
        if(!$order || !is_string($order))   return;
        if(strpos($order,'(')){  // 함수형일대는 asc가 없다
            $order_field    =   trim($order);
            $order_type =   '';
        }else if(preg_match("/ asc$/i",$order)){
            $order_field    =   trim(substr($order,0,strlen($order)-4));
            $order_type =   'ASC';
        }else if(preg_match("/ desc$/i",$order)){
            $order_field    =   trim(substr($order,0,strlen($order)-5));
            $order_type =   'DESC';
        }else{
            $order_field    =   trim($order);
            $order_type =   'ASC';
        }
        $this->order_list[] =   array('field'=>$order_field,'type'=>$order_type);
    }

    /**
     * implode order list to order by query
     *
     * @return string
     */
    private function getOrderQuery() {
        if(!$this->order_list){
            return '';
        }
        $query_order_list   =   array();
        foreach($this->order_list as $order){
            $query_order_list[] =   $order['field'].' '.$order['type'];
        }
        return ' ORDER BY '.implode(',',$query_order_list);
    }

    /**
     * set limit
     *
     * @param (int 0 , int $limit_length)
     * @param (int $limit_start , int $limit_length)
     * @return Db
     */
    public function limit () {
        $arg_count = func_num_args();
        if($arg_count==1){
            $limit_length   =   (int)func_get_arg(0);
            if(is_int($limit_length)){
                $this->limit_start  =   0;
                $this->limit_length  =   $limit_length;
            }
        }else if($arg_count==2){
            $limit_start   =   (int)func_get_arg(0);
            $limit_length   =   (int)func_get_arg(1);
            if(is_int($limit_start) && is_int($limit_length)){
                $this->limit_start  =   $limit_start;
                $this->limit_length  =   $limit_length;
            }
        }
        return $this;
    }

    /**
     * implode limit to limit query
     *
     * @return string
     */
    private function getLimitQuery() {
        $limit_query   =   '';
        if($this->limit_length){
            $limit_query   =   ' LIMIT ';
            if($this->limit_start && is_int($this->limit_start) && $this->limit_start>0){
                $limit_query   .=  $this->limit_start.',';
            }
            $limit_query   .=  $this->limit_length;
        }
        return $limit_query;
    }

    /**
     * use index to use index list
     *
     * @param mixed
     * @return Db
     */
    public function useIndex(){
        $arg_count = func_num_args();
        for($i=0;$i<$arg_count;$i++){
            $index  =   func_get_arg($i);
            if(is_array($index)){
                $this->use_index   =   array_merge($this->use_index,$index);
            }else if(is_string($index)){
                $this->use_index[]   =   $index;
            }
        }
        return $this;
    }


    /**
     * implode use index to query
     *
     * @return string
     */
    private function getIndexQuery() {
        if(!$this->use_index){
            return '';
        }
        return ' USE INDEX ('.implode(',',$this->use_index).')';
    }

    /**
     * select query and return array
     *
     * @throws Exception
     * @return array
     */
    public function select() {
        if(empty($this->table_list)){
            throw new Exception('no table');
        }
        $query_field =   $this->getFieldQuery();
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();
        $query_group =   $this->getGroupQuery();
        $query_order =   $this->getOrderQuery();
        $query_limit    =   $this->getLimitQuery();
        $query_index    =   $this->getIndexQuery();
        $query   =   'SELECT '.$query_field.' FROM '.$query_table.$query_index.$query_where.$query_group.$query_order.$query_limit;

        $result  =   $this->query($query);
        $data_list  =   array();
        if($result){
            while ($data = $this->fetchArray($result))
                $data_list[] = $data;
        }
        return $data_list;
    }


    /**
     * select query and return first one
     *
     * @throws Exception
     * @return array
     */
    public function selectOne() {
        $this->limit(1);
        $data   =   $this->select();
        return $data ? $data[0] : null;
    }

    /**
     * alias of selectOne
     *
     * @throws Exception
     * @return array
     */
    public function one() {
        return $this->selectOne();
    }

    /**
     * select count query
     *
     * @throws Exception
     * @return int
     */
    public function count() {
        if(empty($this->table_list)){
            throw new Exception('no table');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();
        if($this->group_list){
            $query_group =   $this->getGroupQuery();
            $query   =   'SELECT count(*) as cnt FROM (SELECT 1 FROM '.$query_table.$query_where.$query_group.') as T';
        }else{
            $query   =   'SELECT count(*) as cnt FROM '.$query_table.$query_where;
        }

        $result  =   $this->query($query);
        if($result){
            $data = $this->fetchArray($result);
            return $data['cnt'];
        }
        return 0;
    }

    /**
     * select sum query
     *
     * @param string $field
     * @throws Exception
     * @return int
     */
    public function sum($field=''){
        if(empty($this->table_list)){
            throw new Exception('no table');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();

        if($field){
            $field_query    =   $this->escapeString($field);
        }else{
            $field_query    =   $this->field_list[0]['field'];
        }
        if(!stristr($field_query,'.')){
            $field_query   =  '`'.$field_query.'`';
        }
        $query   =   'SELECT sum('.$field_query.') as _sum_ FROM '.$query_table.$query_where;

        $result  =   $this->query($query);
        if($result){
            $data = $this->fetchArray($result);
            return !empty($data['_sum_']) ? $data['_sum_'] : 0;
        }else{
            return  null;
        }
    }


    /**
     * select max query
     *
     * @param string $field
     * @throws Exception
     * @return int
     */
    public function max($field=''){
        if(empty($this->table_list)){
            throw new Exception('no table');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();

        if($field){
            $field_query    =   $this->escapeString($field);
        }else{
            $field_query    =   $this->field_list[0]['field'];
        }
        $query   =   'SELECT max(`'.$field_query.'`) as _max_ FROM '.$query_table.$query_where;

        $result  =   $this->query($query);
        if($result){
            $data = $this->fetchArray($result);
            return !empty($data['_max_']) ? $data['_max_'] : 0;
        }else{
            return  null;
        }

    }

    /**
     * select min query
     *
     * @param string $field
     * @throws Exception
     * @return int
     */
    public function min($field=''){
        if(empty($this->table_list)){
            throw new Exception('no table');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();

        if($field){
            $field_query    =   $this->escapeString($field);
        }else{
            $field_query    =   $this->field_list[0]['field'];
        }
        $query   =   'SELECT min(`'.$field_query.'`) as _min_ FROM '.$query_table.$query_where;

        $result  =   $this->query($query);
        if($result){
            $data = $this->fetchArray($result);
            return !empty($data['_min_']) ? $data['_min_'] : 0;
        }else{
            return null;
        }

    }

    /**
     * insert to db
     * key=>value , key to field
     *
     * @param array $insert
     * @throws Exception
     * @return bool
     */
    public function insert($insert){
        if(sizeof($this->table_list)!=1){
            throw new Exception('error table count');
        }
        $query_table =   $this->getTableQuery();
        $field_list   =   array();
        $value_list =   array();
        foreach($insert as $field=>$value){
            $field_list[]   =   $this->escapeString($field);
            $value_list[]   =   $this->escapeString($value);
        }
        $fields = "`" . implode("`,`", $field_list) . "`";
        $values = "'" . implode("','", $value_list) . "'";
        $query  =   "INSERT INTO ".$query_table."({$fields}) VALUES ({$values})";
        return $this->query($query);
    }

    /**
     * delete from db
     *
     * @throws Exception
     * @return bool
     */
    public function delete() {
        if(sizeof($this->table_list)!=1){
            throw new Exception('error table count');
        }
        if(empty($this->where_list)){
            throw new Exception('no delete condition');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();
        $query_order =   $this->getOrderQuery();
        $query_limit    =   $this->getLimitQuery();
        $query  =   "DELETE FROM ".$query_table.$query_where.$query_order.$query_limit;
        return $this->query($query);
    }

    /**
     * update query
     *
     * @param mixed
     * @throws Exception
     * @return bool
     */
    public function update() {
        if(sizeof($this->table_list)!=1){
            throw new Exception('error table count');
        }
        $update =   array();
        $arg_count = func_num_args();
        if($arg_count==1){
            $update =   func_get_arg(0);
            if(!is_array($update)){
                throw new Exception('error update format');
            }
        }else if($arg_count==2){
            $field  =   func_get_arg(0);
            if(!is_string($field) || !preg_match("/^[a-z][a-z0-9\_]*$/i",$field)){
                throw new Exception('error update field');
            }
            $value  =   func_get_arg(1);
            if(!is_numeric($value) && !is_string($value)){
                throw new Exception('error update value');
            }
            $update =   array($field=>$value);
        }
        $query_table =   $this->getTableQuery();
        if(empty($this->where_list)){
            throw new Exception('no update where condition');
        }
        $query_where =   $this->getWhereQuery();

        $query_order =   $this->getOrderQuery();
        $query_limit    =   $this->getLimitQuery();
        $set_list =   array();
        foreach($update as $field=>$value){
            $field =   $this->escapeString($field);
            $value =   $this->escapeString($value);
            if(preg_match("/^[\+\-]{2}[0-9]+$/",$value)){
                $pre    =   substr($value,0,1);
                $value  =   substr($value,2);
                if($pre=='+'){
                    $set_list[] =   "`{$field}`=`{$field}`+{$value}";
                }else if($pre=='-'){
                    $set_list[] =   "`{$field}`=`{$field}`-{$value}";
                }
            }else{
                $set_list[] =   "`{$field}`='{$value}'";
            }
        }
        $query_set  =   implode(",",$set_list);
        $query  =   "UPDATE ".$query_table." SET ".$query_set.$query_where.$query_order.$query_limit;
        return $this->query($query);
    }

    /**
     * plus value to field
     *
     * @param int $value
     * @throws Exception
     * @return bool
     */
    public function plus($value=1) {
        if(!$value || !is_numeric($value) || $value==0){
            throw new Exception('error plus value');
        }
        if(!$this->field_list){
            throw new Exception('plus field required');
        }
        if(sizeof($this->table_list)!=1){
            throw new Exception('error table count');
        }
        $query_table =   $this->getTableQuery();
        $query_where =   $this->getWhereQuery();
        $query_order =   $this->getOrderQuery();
        $query_limit    =   $this->getLimitQuery();
        $plus_list =   array();
        foreach($this->field_list as $field){
            $plus_list[]    =   '`'.$field['field'].'`=`'.$field['field'].'`+('.$value.')';
        }
        $query_plus =   implode(',',$plus_list);
        $query  =   "UPDATE ".$query_table." SET ".$query_plus.$query_where.$query_order.$query_limit;
        return $this->query($query);
    }

    /**
     * minus to field
     *
     * @param int $value
     * @throws Exception
     * @return bool
     */
    public function minus($value=1) {
        $value  =   $value>0 ? '-'.$value : abs($value);
        return $this->plus($value);
    }

    /**
     * save property
     *
     * @return void
     */
    public function save(){
        $this->save =   true;
    }

    /**
     * reset all property
     *
     * @param string $type
     * @return void
     */
    public function reset($type=null) {
        if($type){
            switch($type){
                case 'field':
                    $this->field_list  =   array();
                    break;
                case 'table':
                    $this->table_list  =   array();
                    break;
                case 'where':
                    $this->where_list  =   array();
                    break;
                case 'order':
                    $this->order_list  =   array();
                    break;
            }
        }else{
            $this->table_list  =   array();
            $this->field_list =   array();
            $this->limit_start    =   0;
            $this->limit_length    =   0;
            $this->where_list  =   array();
            $this->order_list  =   array();
            $this->group_list  =   array();
        }
    }

    /**
     * get last insert id
     *
     * @throws Exception
     * @return int
     */
    public function insertId() {
        if (!$this->db_connectionconn){
            throw new Exception('db connection lost');
        }
        return mysqli_insert_id($this->db_connectionconn);
    }

    /**
     * get last affected rows
     *
     * @throws Exception*
     * @return int
     */
    public function affectedRows() {
        if (!$this->db_connectionconn){
            throw new Exception('db connection lost');
        }
        return mysqli_affected_rows($this->db_connectionconn);
    }

    /**
     * fetch resource into array
     *
     * @param resource $result
     * @return array
     */
    public function fetchArray($result) {
        $row = mysqli_fetch_assoc($result);
        return $row;
    }

    /**
     * debug db query
     *
     * @return Db
     */
    public function debug(){
        $this->debug =  true;
        return $this;
    }

    /**
     * close db connection
     *
     * @throws Exception
     * @return void
     */
    public function close() {
        if (!$this->db_connectionconn){
            throw new Exception('db connection lost');
        }
        mysqli_close($this->db_connectionconn);
    }

    /**
     * escape string
     *
     * @param string $string
     * @throws Exception
     * @return string
     */
    public function escapeString($string) {
        if (!$this->db_connectionconn){
            throw new Exception('db connection lost');
        }
        $string = mysqli_real_escape_string($this->db_connectionconn, $string);
        return $string;
    }

    /**
     * self password function
     *
     * @param string $password
     * @return string
     */
    public function password($password) {
        return md5(sha1($password));
    }


}