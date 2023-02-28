<?php
/*
 * Copyright Sean Proctor
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

namespace App;

class SqlKey
{
    /**
     * SqlKey constructor.
     *
     * @param string $name
     * @param bool   $non_unique
     * @param string $columns
     */
    public function __construct(public $name, public $non_unique, public $columns)
    {
    }

    /**
     * @return string
     */
    public function getCreateQuery()
    {
        if ($this->name == "PRIMARY") {
            return "PRIMARY KEY ({$this->columns})";
        }

        return ($this->non_unique ? "" : "UNIQUE ") . "KEY `{$this->name}` ({$this->columns})";
    }

    /**
     * @return string
     */
    public function getUpdateQuery()
    {
        if ($this->name == "PRIMARY") {
            return "DROP PRIMARY KEY, ADD PRIMARY KEY ({$this->columns})";
        }
        return "DROP KEY `{$this->name}`, ADD "
        . ($this->non_unique ? "" : "UNIQUE ") .
        "KEY `{$this->name}` ({$this->columns});";
    }
}
