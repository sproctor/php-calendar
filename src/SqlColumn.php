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

class SqlColumn {
	var $name;
	var $type;

	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
	}

	function get_create_query() {
		return "`{$this->name}` {$this->type}";
	}

	function get_add_query() {
		return "ADD `{$this->name}` {$this->type}";
	}

	function get_update_query() {
		return "MODIFY `{$this->name}` {$this->type}";
	}
}

?>
