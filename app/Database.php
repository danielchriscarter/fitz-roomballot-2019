<?php

require_once "Environment.php";

class Database {

	private $conn = null;       // database connection
	private $prefix = null;     // table prefix

	// singleton design pattern
	private static $instance;
	private function __construct($host, $user, $pass, $db, $pre) {
		$this->conn = mysqli_connect($host, $user, $pass, $db);
		$this->prefix = $pre;
	}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new Database(Environment::db_host, Environment::db_user, Environment::db_pass, Environment::db_name, Environment::db_prefix);
		}
		return self::$instance;
	}

	public function query($query) {
		if (Environment::db_read_only) {
			if (strpos($query, "INSERT") !== false) {
				throw new Exception("Unable to insert. Database is read only.");
			}
			if (strpos($query, "UPDATE") !== false) {
				throw new Exception("Unable to update. Database is read only.");
			}
			if (strpos($query, "DELETE") !== false) {
				throw new Exception("Unable to delete. Database is read only.");
			}
		}
		return mysqli_query($this->conn, $query);
	}

	public function error() {
		return mysqli_error($this->conn);
	}

	public function escape($str) {
		return mysqli_real_escape_string($this->conn, $str);
	}

	public function insert($table, $vars) {
		$rows = array_keys($vars);
		$vals = array_values($vars);
		foreach ($vals as $i => $val) {
			switch (gettype($val)) {
				case "integer":
				case "double":
					$vals[$i] = (string) $val;
					break;
				case "boolean":
					$vals[$i] = ($val ? "1" : "0");
					break;
				case "string":
					$vals[$i] = "\"" . mysqli_real_escape_string($this->conn, $val) . "\"";
					break;
				case "NULL":
					$vals[$i] = "NULL";
					break;
			}
		}
		$table = $this->prefix . $table;
		$result = $this->query("INSERT INTO `" . $table . "` (`" . implode("`, `", $rows) . "`) VALUES (" . implode(", ", $vals) . ");");
		return $result ? true : false;
	}

	public function update($table, $where, $vars) {
		$rows = array_keys($vars);
		$vals = array_values($vars);
		$pairs = array();
		foreach ($vals as $i => $val) {
			switch (gettype($val)) {
				case "integer":
				case "double":
					$vals[$i] = (string) $val;
					break;
				case "boolean":
					$vals[$i] = ($val ? "1" : "0");
					break;
				case "string":
					$vals[$i] = "\"" . mysqli_real_escape_string($this->conn, $val) . "\"";
					break;
				case "NULL":
					$vals[$i] = "NULL";
					break;
			}
			$pairs[] = "`" . $rows[$i] . "` = " . $vals[$i];
		}
		$table = $this->prefix . $table;
		$result = $this->query("UPDATE `" . $table . "` SET " . implode(", ", $pairs) . ($where ? " WHERE " . $where : "") . ";");
		return $result ? true : false;
	}

	public function delete($table, $where) {
		$table = $this->prefix . $table;
		$result = $this->query("DELETE FROM `" . $table . "`" . ($where ? " WHERE " . $where : "") . ";");
		return $result ? true : false;
	}

	public function fetch($table, $where=null, $sort=null, $limit=array(0, 1000)) {
		$table = $this->prefix . $table;
		// define a string to store our SQL query
		$conds = "";
		if ($where) {
			$conds .= " WHERE " . $where;
		}
		if ($sort) {
			$conds .= " ORDER BY " . $sort;
		}
		$conds .= " LIMIT " . implode(", ", $limit);
		$result = $this->query("SELECT * FROM `" . $table . "`" . $conds . ";");
		$data = array();

		// only parse the data if we were sucessful
		if ($result) {
			$count = 0;
			while ($row = mysqli_fetch_assoc($result)) {
				$data[] = $row;
				$count++;
			}
		}
		return $data;
	}

	public function transaction($queries){
	  $this->query("START TRANSACTION");

	  foreach($queries as $q){
		if( !$this->query($q) ){
		  $this->query("ROLLBACK");
		  return false;
		}
	  }

	  $this->query("COMMIT");
	  return true;
	}
}

?>
