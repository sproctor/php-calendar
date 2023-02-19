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

class SqlTable
{
    private $columns;
    private $keys;
    private $name;

    /**
     * SqlTable constructor.
     *
     * @param $name
     * @param SqlColumn[] $columns
     * @param SqlKey[]    $keys
     */
    public function __construct($name, $columns = array(), $keys = array())
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->keys = $keys;
    }

    /**
     * @param string $name
     * @param string $type
     */
    public function addColumn($name, $type)
    {
        $this->columns[] = new SqlColumn($name, $type);
    }

    /**
     * @param string $name
     * @param bool $non_unique
     * @param string $columns
     */
    public function addKey($name, $non_unique, $columns)
    {
        $this->keys[] = new SqlKey($name, $non_unique, $columns);
    }

    /**
     * @param \PDO $dbh
     * @param bool $drop
     * @throws \Exception
     */
    public function create(\PDO $dbh, $drop = false)
    {
        //echo "creating table\n";
        if ($drop) {
            $query = "DROP TABLE IF EXISTS `{$this->name}`";

            $result = $dbh->query($query);
            if (!$result) {
                throw new \Exception("Error dropping table `{$this->name}`: $query");
            }
        }

        $query = "CREATE TABLE `{$this->name}` (";
        $first_column = true;
        foreach ($this->columns as $column) {
            if (!$first_column) {
                $query .= ', ';
            }
            $first_column = false;
            $query .= "\n" . $column->getCreateQuery();
        }
        foreach ($this->keys as $key) {
            $query .= ",\n" . $key->getCreateQuery();
        }
        $query .= ')';
        
        // echo "<pre>Creating table '{$this->name}': $query\n</pre>";
        $dbh->exec($query);
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     * @throws \Exception
     */
    public function update(\PDO $dbh)
    {
        // Check if the table exists
        $stmt = $dbh->query("SHOW TABLES LIKE '{$this->name}'");
        if ($stmt->rowCount() == 0) {
            $this->create($dbh);
            return [__("created-type-notification", ['%type%' => __('database-table'), '%name%' =>$this->name])];
        } else {
            $column_messages = $this->updateColumns($dbh);
            $key_messages = $this->updateKeys($dbh);
            return array_merge($column_messages, $key_messages);
        }
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     * @throws FailedActionException
     */
    public function updateColumns(\PDO $dbh)
    {
        $updates = array();

        // Update Columns
        $query = "SHOW FULL COLUMNS FROM {$this->name}";
        $sth = $dbh->query($query);
        //echo "<pre>";
        $current_columns = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $current_columns[$result['Field']] = $result;
            //var_dump($result);
        }
        foreach ($this->columns as $column) {
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
                    if ($default == 'CURRENT_TIMESTAMP') {
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
                    .$column->getUpdateQuery();
                    //var_dump($existing_column);
                    //echo "existing type: $type\nnew type: {$column->type}\n";
                    //echo $query, "\n";
                    $result = $dbh->query($query);
                    if (!$result) {
                        throw new FailedActionException("error in query: $query");
                    }
                    $updates[] = __(
                        'updated-type-notification',
                        ['%type%' => __('database-column'), '%name%' => $this->name . '.' . $column->name]
                    );
                }
            } else {
                $query = "ALTER TABLE `{$this->name}`\n"
                .$column->getAddQuery();
                //echo $query, "\n";
                $result = $dbh->query($query);
                if (!$result) {
                    throw new FailedActionException("error in query: $query");
                }
                $updates[] = __(
                    'created-type-notification',
                    ['%type%' => __('database-column'), '%name%' => $this->name . '.' . $column->name]
                );
            }
        }
        //echo "</pre>";
        return $updates;
    }

    /**
     * @param \PDO $dbh
     * @return string[]
     * @throws \Exception
     */
    public function updateKeys(\PDO $dbh)
    {
        $updates = array();

        // Upate Keys
        $query = "SHOW INDEX FROM {$this->name}";
        $sth = $dbh->query($query);
        //echo "<pre>";
        $current_keys = array();
        while ($result = $sth->fetch(\PDO::FETCH_ASSOC)) {
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
        foreach ($this->keys as $key) {
            if (isset($current_keys[$key->name])) {
                $existing_key = $current_keys[$key->name];
                $existing_columns = $existing_key['Columns'];
                if ($existing_columns != $key->columns
                    || $existing_key['Non_unique'] !=         $key->non_unique
                ) {
                    $query = "ALTER TABLE `{$this->name}`\n"
                    .$key->getUpdateQuery();
                    //echo "existing columns: $existing_columns\n";
                    //echo "new columns: {$key->columns}\n";
                    //echo "running query: $query\n";
                    
                    $result = $dbh->query($query);
                    if (!$result) {
                        throw new \Exception("error in query: $query");
                    }
                    $updates[] = __(
                        "updated-type-notification",
                        ['%type%' => __('database-key'), '%name%' => $this->name . '.' . $key->name]
                    );
                }
            } else {
                $query = "ALTER TABLE `{$this->name}`\n"
                .$key->getAddQuery();
                //echo $query, "\n";
                $result = $dbh->query($query);
                if (!$result) {
                    throw new \Exception("error in query: $query");
                }
                $updates[] = __(
                    'created-type-notification',
                    ['%type%' => __('database-column'), '%name%' => $this->name . '.' . $key->name]
                );
            }
        }
        //echo "</pre>";
        return $updates;
    }
}
