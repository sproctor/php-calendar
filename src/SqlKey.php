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

namespace PhpCalendar;

class SqlKey {
	var $name;
	var $non_unique;
	var $columns;

	function __construct($name, $non_unique, $columns) {
		$this->name = $name;
		$this->non_unique = $non_unique;
		$this->columns = $columns;
	}

	function get_create_query () {
		if($this->name == "PRIMARY")
			return "PRIMARY KEY ({$this->columns})";

		return ($this->non_unique ? "" : "UNIQUE ") . "KEY `{$this->name}` ({$this->columns})";
	}

	function get_update_query () {
		if($this->name == "PRIMARY")
			return "DROP PRIMARY KEY, ADD PRIMARY KEY ({$this->columns})";
		return "DROP KEY `{$this->name}`, ADD "
			. ($this->non_unique ? "" : "UNIQUE ") . 
			"KEY `{$this->name}` ({$this->columns});";
	}
}

?>
