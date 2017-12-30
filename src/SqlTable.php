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

class SqlTable
{
    var $columns;
    var $keys;
    var $name;

    /**
     * SqlTable constructor.
     *
     * @param $name
     * @param SqlColumn[] $columns
     * @param SqlKey[]    $keys
     */
    function __construct($name, $columns = array(), $keys = array()) 
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->keys = $keys;
    }

    /**
     * @param string $name
     * @param string $type
     */
    function addColumn($name, $type) 
    {
        $this->columns[] = new SqlColumn($name, $type);
    }

    /**
     * @param string     $name
     * @param $non_unique
     * @param $columns
     */
    function addKey($name, $non_unique, $columns) 
    {
        $this->keys[] = new SqlKey($name, $non_unique, $columns);
    }

    /**
     * @param \PDO $dbh
     * @param bool $drop
     */
    function create(\PDO $dbh, $drop = false) 
    {
        //echo "creating table\n";
        if ($drop) {
            $query = "DROP TABLE IF EXISTS `{$this->name}`";

            $dbh->query($query)
            or $this->db_error($dbh, "Error dropping table `{$this->name}`.", $query);
        }

        $query = "CREATE TABLE `{$this->name}` (";
        $first_column = true;
        foreach($this->columns as $column) {
            if(!$first_column) {
                $query .= ', ';
            }
            $first_column = false;
            $query .= "\n" . $column->get_create_query();
        }
        foreach($this->keys as $key) {
            $query .= ",\n" . $key->get_create_query();
        }
        $query .= ')';
        try {
            // echo "<pre>Creating table '{$this->name}': $query\n</pre>";
            $dbh->exec($query);
        } catch(\PDOException $e) {
            $this->db_error($dbh, __("Error creating table"), $query);
        }
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     */
    function update(\PDO $dbh) 
    {
        // Check if the table exists
        $stmt = $dbh->query("SHOW TABLES LIKE '{$this->name}'");
        if($stmt->rowCount() == 0) {
            $this->create($dbh);
            return [__("Created table") . ": {$this->name}"];
        } else {
            $column_messages = $this->updateColumns($dbh);
            $key_messages = $this->updateKeys($dbh);
            return array_merge($column_messages, $key_messages);
        }
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     */
    function updateColumns(\PDO $dbh) 
    {
        $updates = array();

        // Update Columns
        $query = "SHOW FULL COLUMNS FROM {$this->name}";
        $sth = $dbh->query($query);
        //echo "<pre>";
        $current_columns = array();
        while($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $current_columns[$result['Field']] = $result;
            //var_dump($result);
        }
        foreach($this->columns as $column) {
            if (isset($current_columns[$column->name])) {
                $existing_column = $current_columns[$column->name];
                $type = $existing_column['Type'];
                if ($existing_column['Collation'] != '') {
                    $type .= " COLLATE {$existing_column['Collation']}";
                }
                if ($existing_column['Null'] == 'NO') {
                    $type .= " NOT NULL";
                }
                $default = $existing_column['Default'];
                if ($default != '') {
                    // TODO replace this with a search of
                    //   this list of all unquotes values
                    if($default == 'CURRENT_TIMESTAMP') {
                        $type .= " DEFAULT $default";
                    } else {
                        $type .= " DEFAULT '$default'";
                    }
                }
                if ($existing_column['Extra'] != '') {
                    $type .= ' '.$existing_column['Extra'];
                }
                if ($type != $column->type) {
                    $query = "ALTER TABLE `{$this->name}`\n"
                    .$column->get_update_query();
                    $updates[] = __('Updating column: ') . $this->name . '.' . $column->name;
                    //var_dump($existing_column);
                    //echo "existing type: $type\nnew type: {$column->type}\n";
                    //echo $query, "\n";
                    $dbh->query($query)
                    or $this->db_error($dbh, "error in query", $query);
                }
            } else {
                $query = "ALTER TABLE `{$this->name}`\n"
                .$column->get_add_query();
                //echo $query, "\n";
                $dbh->query($query)
                or $this->db_error($dbh, "error in query", $query);
                $updates[] = __('Added column: ') . $this->name . '.' . $column->name;
            }
        }
        //echo "</pre>";
        return $updates;
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     */
    function updateKeys(\PDO $dbh) 
    {
        $updates = array();

        // Upate Keys
        $query = "SHOW INDEX FROM {$this->name}";
        $sth = $dbh->query($query);
        //echo "<pre>";
        $current_keys = array();
        while($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
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
                    || $existing_key['Non_unique'] !=         $key->non_unique
                ) {
                    $query = "ALTER TABLE `{$this->name}`\n"
                    .$key->get_update_query();
                    //echo "existing columns: $existing_columns\n";
                    //echo "new columns: {$key->columns}\n";
                    //echo "running query: $query\n";
                    $updates[] = __("Updating key: ") . $this->name . '.' . $key->name;
                    $dbh->query($query)
                    or $this->db_error($dbh, "error in query", $query);
                }
            } else {
                $query = "ALTER TABLE `{$this->name}`\n"
                .$key->get_add_query();
                //echo $query, "\n";
                $dbh->query($query)
                or $this->db_error($dbh, "error in query", $query);
                $updates[] = __('Added column: ') . $this->name . '.' . $key->name;
            }
        }
        //echo "</pre>";
        return $updates;
    }

    /**
     * @param \PDO   $dbh
     * @param string $str
     * @param string $query
     */
    static function db_error(\PDO $dbh, $str, $query = "") 
    {
        echo $str . "<pre>" . json_encode($dbh->errorInfo()) . "\n";
        if($query != "") {
            echo __('SQL query') . ": "
            . htmlspecialchars($query, ENT_COMPAT, "UTF-8") . "\n";
        }
        debug_print_backtrace();
        print "</pre>";
        die;
    }
}
