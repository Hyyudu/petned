<?php
require_once('mysqli.php');
require_once('db_connect.php');

class DB {
	private $db;
    private $last_sql = '';

	public function __construct() {
		$this->db = new DB\MySQLi(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
	}

    /**
     * @param $sql
     * @param array $placeholders
     * @return mixed
     * Returns string - sql request with placeholders replaced by data
     */
    public function parseSql($sql, $placeholders=array())   {
        if ($placeholders) {
            preg_match_all('~\:([\w\d_]+)~', $sql, $matches);
            if ($matches[0])
                foreach ($matches[1] as $pl) {

                    $val = isset($placeholders[$pl]) ? $placeholders[$pl] : '';
                    if (is_object($val) && get_class($val) == 'SqlWord')    {
                        $val = $val->text;
                    }
                    elseif (is_numeric($val) && (is_int($val) || is_float($val)))   {}
                    else
                        $val = "'".$this->escape($val)."'";

                    $sql = preg_replace("~:" . $pl . "(?![\w\d_])~", $val, $sql);
                }
        }
        return $sql;
    }

    /**
     * @param $sql
     * @param array $placeholders
     * @return mixed
     * executes query $sql, replacing :foo with $placeholders['foo'] with escaping etc.
     */
	public function query($sql, $placeholders = array()) {
        $sql = $this->parseSql($sql, $placeholders);
        $this->last_sql = $sql;
        return $this->db->query($sql);
	}

    private function _getResult($sql, $placeholders)    {
        $q = $this->query($sql, $placeholders);
        return $q->rows;
    }

    public function fetchRows($sql, $placeholders = array(), $classify ='') {
        $out = $this->_getResult($sql, $placeholders);
        if (!$classify)
            return ($out);
        else return DB_array::associate($out, $classify, false);

    }

    public function fetchRow($sql, $placeholders = array()) {
        $res = $this->_getResult($sql, $placeholders);
        if (count($res))
            return $res[0];
    }

    /**
     * @param $sql
     * @param array $placeholders
     * @param string $id_field
     * @param string $value_field
     * @return array
     * fetchDict('select foo, bar from table', ...., 'foo', 'bar') => array('foo1'=>'bar1', 'foo2' => 'bar2');
     */
    public function fetchDict($sql, $placeholders=array(), $id_field = '', $value_field = '') {
        $rows = $this->_getResult($sql, $placeholders);
        $out = array();
        foreach ($rows as $item)
            $out[$item[$id_field]] = $item[$value_field];
        return $out; 
    }

    /**
     * @param $sql
     * @param array $placeholders
     * @return mixed
     * for something like "select name from table where id=123"
     */
    public function fetchField($sql, $placeholders=array()) {
        $rows = $this->_getResult($sql, $placeholders);
        foreach ($rows as $item)
            foreach ($item as $key=>$value)
                return $value;
    }


    /**
     * @param $sql
     * @param array $placeholders
     * @return array
     * fetchColumn('select id from table1') => array(5,6,8)
     */
    public function fetchColumn($sql, $placeholders=array())    {
        $rows = $this->_getResult($sql, $placeholders);
        $out = array();
        foreach ($rows as $item)
            foreach ($item as $key=>$value)
                $out[]= $value;
        return $out;
    }

    /**
     * @param $table
     * @param $data
     * @param bool $only_sql_text
     * @return mixed
     * Inserts row/rows into $table
     * insert('mytable', array('id'=>1, 'name' => 'azaza'))
     * or
     * insert('mytable', array(array('name'=>'foo', 'value'=>5), array('name'=>'bar', 'value'=>7)))
     * $only_sql_text - return query text instead of executing it
     */
    public function insert($table, $data, $only_sql_text = false)  {
        if (!$data) {
            require_once('tools.php');
            error_log("DB insert into $table without data. Backtrace: ".tools::backtrace_text());
        }
        $col_data = $this->fetchRows('show columns from '.$table);
        $existing = array();
        foreach ($col_data as $col)
            $existing[] = $col['Field'];

//        true - insert several lines, false - 1 line
        $array_insert = isset($data[0]);


        $keys = $array_insert ? array_keys($data[0]) : array_keys($data);
        $ins_keys = array_intersect($existing, $keys);

        $query = "insert into $table (`".join('`, `', $ins_keys)."`) values ";
        if (!$array_insert)
            $data = array($data);
        $new_data = array();
        foreach ($data as $i=>$item)    {
            $query.= "(:".join($i.', :', $ins_keys).$i.'), ';
            foreach ($ins_keys as $key)
                $new_data[$key.$i] = $item[$key];
        }
//        cut last comma
        $query = substr($query, 0, -2);
        if ($only_sql_text)
            return $this->parseSql($query, $new_data);
        $this->query($query, $new_data);
    }

    public function replace($table, $data, $only_sql_text = false)  {
        $sql = $this->insert($table, $data, true);
        $sql = str_replace("insert into", "replace into", $sql);
        if ($only_sql_text)
            return $sql; 
        $this->query($sql);
    }

    public function getLastSql()    {
        return $this->last_sql;
    }

	public function escape($value) {
		return $this->db->escape($value);
	}

	public function countAffected() {
		return $this->db->countAffected();
	}

	public function getLastId() {
		return $this->db->getLastId();
	}
}

class SqlWord   {
    public $text;
    public function __construct($text)  {
        $this->text = $text;
    }
}

class DB_array{
    /**
     * @param $data
     * @param string $classify
     * @param bool $cumulative
     * @param bool $drop_classified_fields
     * @return array
     * Creates associative array from flat (nested, if classify is array of field names).
     * Cumulative - if inside one set of $classify fieldnames is one record (false) or several (true)
     */
    public static function associate($data, $classify ='', $cumulative = false, $drop_classified_fields = true)    {
        $output = array();
        if (!is_array($classify))
            $classify = array($classify);
        foreach ($data as $item) {
//            if $classify == 'azaza', we have $output[$item['azaza']] = $item
//            if $classify == array('foo', 'bar'), we have $output[$item['foo']][$item['bar']] = $item
//            if $cumulative - not ... = $item, but ... []= $item
//            if $drop_classified_fields, $item['foo'] and $item['bar'] are removed from $item

            $firstlevel = 1;
            foreach ($classify as $field)   {
                if ($firstlevel)
                    $tmp = &$output[$item[$field]];
                else
                    $tmp = &$tmp[$item[$field]];
                $firstlevel = 0;
            }
            if ($drop_classified_fields)    {
                foreach ($classify as $field)
                    unset($item[$field]);
                if (count($item) == 1) {
                    $item = array_values($item);
                    $item = $item[0];
                }
            }
            if ($cumulative)
                $tmp[]=$item;
            else
                $tmp=$item;
            unset($tmp);
        }

        return $output;
    }
}

