<?php
namespace DB;
final class MySQLi {
	private $link;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		$this->link = new \mysqli($hostname, $username, $password, $database, $port);

		if ($this->link->connect_error) {
			trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
			exit();
		}

		$this->link->set_charset("utf8");
		$this->link->query("SET SQL_MODE = ''");
	}

	public function query($sql) {


		if(defined('SANDBOX'))
		{
			$trace = debug_backtrace();
			$filename = (isset($trace[0]['file'])) ? $trace[0]['file'] : '---';
			$query_time = (time() + microtime());
		}

		$query = $this->link->query($sql);


		if (!$this->link->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();

				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;

				$query->close();

				return $result;
			} else {
				return true;
			}
		} else {
//			trigger_error('Error: ' . $this->link->error  . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
            $bt = debug_backtrace();
            $bt_text = '';
            foreach ($bt as $item) {
                if (isset($item['class']))
                    $bt_text.=$item['class'].'->';
                if (isset($item['function']))
                    $bt_text.=$item['function'];
                if (isset($item['file']))
                    $bt_text.=" at ".$item['file'].', line '.$item['line'];
                $bt_text.="\n";
            }
            $this->log('Error # ' . $this->link->errno.': '. $this->link->error  ."\n". $sql."\n".$bt_text."===================================================================================\n");
            return false;
		}
	}

//    logging utility
    public function log($text)  {
        echo $text;
    }

	public function escape($value) {
		return $this->link->real_escape_string($value);
	}

	public function countAffected() {
		return $this->link->affected_rows;
	}

	public function getLastId() {
		return $this->link->insert_id;
	}

	public function __destruct() {
		$this->link->close();
	}
}