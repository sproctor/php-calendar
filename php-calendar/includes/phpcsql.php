<?php
/*
 * Copyright 2013 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
   this file contains the db schema and functions to use it.
*/

if ( !defined('IN_PHPC') ) {
       die("Hacking attempt");
}

class PhpcSqlTable {
	var $columns;
	var $keys;
	var $name;

	function __construct($name, $columns = array(), $keys = array()) {
		$this->name = $name;
		$this->columns = $columns;
		$this->keys = $keys;
	}

	function addColumn($name, $type) {
		$this->columns[] = new PhpcSqlColumn($name, $type);
	}

	function addKey($name, $non_unique, $columns) {
		$this->keys[] = new PhpcSqlKey($name, $non_unique, $columns);
	}

	function create($dbh) {
	}

	function update($dbh) {
		$this->updateColumns($dbh);
		$this->updateKeys($dbh);
	}

	function updateColumns($dbh) {
		$tags = array();

		// Update Columns
		$query = "SHOW FULL COLUMNS FROM {$this->name}";
		$sth = $dbh->query($query);
		//echo "<pre>";
		$current_columns = array();
		while($result = $sth->fetch_assoc()) {
			$current_columns[$result['Field']] = $result;
			//print_r($result);
		}
		foreach($this->columns as $column) {
			if (isset($current_columns[$column->name])) {
				$existing_column = $current_columns[$column->name];
				$type = $existing_column['Type'];
				if ($existing_column['Collation'] != '')
					$type .= " COLLATE {$existing_column['Collation']}";
				if ($existing_column['Null'] == 'NO')
					$type .= " NOT NULL";
				$default = $existing_column['Default'];
				if ($default != '') {
					// TODO replace this with a search of
					//   this list of all unquotes values
					if($default == 'CURRENT_TIMESTAMP')
						$type .= " DEFAULT $default";
					else
						$type .= " DEFAULT '$default'";
				}
				if ($existing_column['Extra'] != '')
					$type .= ' '.$existing_column['Extra'];
				if ($type != $column->type) {
					$query = "ALTER TABLE `{$this->name}`\n"
						.$column->get_update_query();
					$tags[] = tag('div', __('Updating column: ') . $column->name);
					print_r($existing_column);
				//echo "existing type: $type\nnew type: {$column->type}\n";
					//echo $query, "\n";
					$dbh->query($query)
						or db_error($dbh, "error in query", $query);
				}
			} else {
				$query = "ALTER TABLE `{$this->name}`\n"
					.$column->get_add_query();
				//echo $query, "\n";
				$dbh->query($query)
					or db_error($dbh, "error in query", $query);
			}
		}
		//echo "</pre>";
		return $tags;
	}

	function updateKeys($dbh) {
		$tags = array();

		// Upate Keys
		$query = "SHOW INDEX FROM {$this->name}";
		$sth = $dbh->query($query);
		//echo "<pre>";
		$current_keys = array();
		while($result = $sth->fetch_assoc()) {
			$key_name = $result['Key_name'];
			if (isset($current_keys[$key_name])) {
				$key = $current_keys[$key_name];
				$key['Columns'] .=
					",`{$result['Column_name']}`";
			} else {
				$key = array('Key_name' => $key_name,
					'Non_unique' => $result['Non_unique'],
					'Columns' => "`{$result['Column_name']}`");
			}
			$current_keys[$key_name] = $key;
		}
		foreach($this->keys as $key) {
			if (isset($current_keys[$key->name])) {
				$existing_key = $current_keys[$key->name];
				$existing_columns = $existing_key['Columns'];
				if ($existing_columns != $key->columns
					|| $existing_key['Non_unique'] !=
						$key->non_unique) {
					$query = "ALTER TABLE `{$this->name}`\n"
						.$key->get_update_query();
				//echo "existing columns: $existing_columns\n";
				//echo "new columns: {$key->columns}\n";
					//echo "running query: $query\n";
					$tags = tag('div', __("Updating key: ") . $key->name);
					$dbh->query($query)
						or db_error($dbh, "error in query", $query);
				}
			} else {
				$query = "ALTER TABLE `{$this->name}`\n"
					.$column->get_add_query();
			//echo $query, "\n";
				$dbh->query($query)
					or db_error($dbh, "error in query", $query);
			}
		}
		//echo "</pre>";
		return $tags;
	}
}

class PhpcSqlColumn {
	var $name;
	var $type;

	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
	}

	function get_update_query() {
		return "MODIFY `{$this->name}` {$this->type}";
	}
}

class PhpcSqlKey {
	var $name;
	var $non_unique;
	var $columns;

	function __construct($name, $non_unique, $columns) {
		$this->name = $name;
		$this->non_unique = $non_unique;
		$this->columns = $columns;
	}

	function get_update_query () {
		if($this->name == "PRIMARY")
			return "DROP PRIMARY KEY, ADD PRIMARY KEY ({$this->columns})";
		return "DROP KEY `{$this->name}`, ADD "
			. ($this->non_unique ? "" : "UNIQUE ") . 
			"KEY `{$this->name}` ({$this->columns});";
	}
}

function db_error($dbh, $str, $query = "") {
	$string = $str . "<pre>" . htmlspecialchars($dbh->error,
			ENT_COMPAT, "UTF-8") . "</pre>";
	if($query != "") {
		$string .= "<pre>" . __('SQL query') . ": "
			. htmlspecialchars($query, ENT_COMPAT, "UTF-8")
			. "</pre>";
	}
	die($string);
}
?>
