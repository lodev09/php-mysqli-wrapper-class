<?php

/**
 * @package MySQL Wrapper Class in PHP5
 * @author Jovanni Lo
 * @link http://www.lodev09.com
 * @see http://php.net/manual/en/book.mysql.php
 * @license
 * The MIT License (MIT)
 * Copyright (c) 2017 Jovanni Lo
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class MySQL {
    const QUERY_OBJ = 1;
    const QUERY_ASSOC = 2;

    public $host = "localhost";
    public $user = "root";
    public $pass = "";
    public $db = "";
    public $debug = false;

    // global clean - clean output for HTML display
    public $clean = true;

    /**
     * Link ID of the current connection
     * @var resource
     */
    private $linkId = null;

    /**
     * Result of mysqli_query()
     * @var resource
     */
    private $query_result;

    /**
     * Current SQL Query statement
     * @var string
     */
    private $query;

    /**
     * Type of query
     * @var int
     */
    private $query_type;

    /**
     * Last ID of a successfull insert
     * @var variant
     */
    private $last_inserted_id;

    /**
     * Number of rows returned in the query
     * @var variant
     */
    private $num_rows;

    /**
     * Types of each fields in the result set
     * @var variant
     */
    private $types;

    /**
     * construct class
     * @param string $host     server/host of MySQL
     * @param string $database database name
     * @param string $username user
     * @param string $password password
     */
    public function __construct($host = 'localhost', $database = '', $username = '', $password = '', $port = 3306) {
        register_shutdown_function(array($this, '__destruct'));

        if (empty($database) && empty($username) && empty($password)) {
            trigger_error('Invalid parameter values to establish connection.', E_USER_ERROR);
        } else {
            if (!$this->connect($host, $database, $username, $password, $port)) {
                trigger_error('Could not establish a connection.', E_USER_ERROR);
            }
        }
    }

    /**
     * destruct class
     * Disconnect connection
     */
    public function __destruct() {
        if ($this->linkId) {
            mysqli_close($this->linkId);
            $this->linkId = null;
        }
    }

    /**
     * Establishes a connection to the database specified
     * @param  string $host     the host to connect to
     * @param  string $database the database to connect to
     * @param  string $username the username of the db to connect to
     * @param  string $password the password of the db to connect to
     * @return boolean          returns true if successfully connected
     */
    private function connect($host, $database, $username, $password, $port = 3306) {
        $this->host = $host;
        $this->db = $database;
        $this->user = $username;
        $this->pass = $password;
        if (is_null($this->linkId)) {
            $this->linkId = mysqli_connect($host, $username, $password, $database, $port) or trigger_error('Could not establish a connection.',
                E_USER_ERROR);

            // If we couldn't select the database, return false.
            if (!$this->select_db($database)) {
                trigger_error('Could not connect to database.', E_USER_ERROR);
                return false;
            }
            // Connection was a success.
            else {
                return true;
            }
        } else {
            // Assume we already have a connection.
            return true;
        }
    }

    /**
     * Sets the encoding charset (defaults to utf8)
     */
    public function set_charset($charset = 'utf8') {
        return mysqli_set_charset($this->linkId, $charset);
    }

    /**
     * Selects the database
     * @param  string $database the database to connect to
     * @return boolean           true for success, false if not
     */
    private function select_db($database) {
        // If there was an error selecting the database, return false.
        if (!mysqli_select_db($this->linkId, $database)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the formatted MySQL DateTime
     * @param  string $date string date
     * @return string       returns the formatted datetime
     */
    public static function get_date($date="", $time = true) {
        if ($date == "")
            return date('Y-m-d'.($time ? ' H:i:s' : ''));
        else {
            $stamp = strtotime($date);
            return $stamp > 0 ? date('Y-m-d'.($time ? ' H:i:s' : ''), $stamp) : false;
        }
    }

    /**
     * Process a filter string for query statement
     * @param  mixed  $filters  the filter array or object
     * @param  mixed $options   optional filters
     * @param  boolean $where   true if we start from WHERE clause, otherwise AND
     * @return string           returns string filter
     */
    public static function build_filter_string($input, $options = false, $append = "AND") {
        $filter_str = "";
        $operator = "AND";
        $enclose = false;
        if ($options) {
            $options = (object)$options;
            $operator = isset($options->operator) ? $options->operator : "AND";
            $enclose = isset($options->enclose) ? $options->enclose : false;
        }

        $filter_list = array();
        if ($input) {
            $filters = is_array($input) ? $input : array($input);
            foreach ($filters as $field => $filter) {
                if (is_int($field)) $filter_list[] = $filter;
                else $filter_list[] = "$field $filter";
            }
        }

        if ($filter_list) {
            $filter_str = implode(" $operator ", $filter_list);
            $filters = $append." ".($enclose ? "(" : "").$filter_str.($enclose ? ")" : "");
            return $filters;
        } else return "";

    }

    /**
     * Clean an array of strings for query
     * @param  array $array array input
     * @return array        return the clean array
     */
    private function real_escape_array($array) {
        foreach ($array as $field => $value)
            $array[$field] = $this->real_escape_string($value);

        return $array;
        // return array_map(array($this, "real_escape_string"), $array);
    }

    /**
     * Clean an object of strings for query
     * @param  object $array object input
     * @return object        return the clean object
     */
    private function real_escape_obj($obj) {
        foreach ($obj as $field => $value)
            $obj->{$field} = $this->real_escape_string($value);

        return $obj;
        // return (object)array_map(array($this, "real_escape_string"), self::object_to_array($obj));
    }

    /**
     * Clean the string to be used by MySQL
     * @param string $str  the string to be cleaned
     * @return string      return the clean string
     */
    private function real_escape_string($str) {
        return mysqli_real_escape_string($this->linkId, $str);
    }

    /**
     * Escape a string, array or object
     * @param  mixed $var [string, array or object]
     * @return mixed      escaped string, array or object
     */
    public function escape(&$var) {
        if (is_object($var))
            $var = $this->real_escape_obj($var);
        else if (is_array($var))
            $var = $this->real_escape_array($var);
        else
            $var = $this->real_escape_string($var);

        return $var;
    }

    /**
     * Retrieves the last error.
     * @return string the error text from the last MySQL function, or empty string if no error occurred.
     */
    public function get_error() {
        return mysqli_error($this->linkId);
    }

    /**
     * Executes a command on the database and accepts a callback for each row
     * @param  string $sql         the query to run
     * @param  closure $callback    the callback function
     * @param  mixed $return_type return type of the query
     * @param  boolean $clean       true if you want to clean the values for HTML display
     * @return mixed              returns the result object/array data
     */
    public function each($sql = '', $callback, $return_type = self::QUERY_OBJ, $clean = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            if (is_bool($this->query_result)) return $this->query_result;

            $rows = $return_type == self::QUERY_OBJ ? $this->as_obj($clean, $callback) : $this->as_array($clean, $callback);
            $this->free_result();

            if (!$rows) return false;
            else return $rows;
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }

    /**
     * Executes a command on the database (multi query)
     * @param string $sql         the query to run.
     * @param mixed $return_type  return type of the query
     * @param boolean $clean      true if you want to clean the values for HTML display
     * @return mixed              returns the result object/array data
     */
    public function query_multi($sql = '', $return_type = self::QUERY_OBJ, $clean = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            if (is_bool($this->query_result)) return $this->query_result;

            $results = array();
            $rows = $return_type == self::QUERY_OBJ ? $this->as_obj($clean) : $this->as_array($clean);
            $this->free_result(false);

            if ($rows) $results[] = $rows;

            while (mysqli_more_results($this->linkId) && mysqli_next_result($this->linkId)) {
                if ($this->query_result = mysqli_store_result($this->linkId)) {
                    $rows = $return_type == self::QUERY_OBJ ? $this->as_obj($clean) : $this->as_array($clean);
                    $this->free_result(false);

                    if ($rows) $results[] = $rows;
                }
            }

            return $results;
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }

    /**
     * Executes a command on the database.
     * @param string $sql         the query to run.
     * @param mixed $return_type  return type of the query
     * @param boolean $clean      true if you want to clean the values for HTML display
     * @return mixed              returns the result object/array data
     */
    public function query($sql = '', $return_type = self::QUERY_OBJ, $clean = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            if (is_bool($this->query_result)) return $this->query_result;

            $rows = $return_type == self::QUERY_OBJ ? $this->as_obj($clean) : $this->as_array($clean);
            $this->free_result();

            if (!$rows) return false;
            else return $rows;
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }
    /**
     * Executes a command on the database (one row)
     * @param string $sql         the query to run.
     * @param mixed $return_type  return type of the query
     * @param boolean $clean      true if you want to clean the values for HTML display
     * @return mixed              returns the result object/array data (object/array index 0)
     */
    public function query_row($sql = '', $return_type = self::QUERY_OBJ, $clean = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            $row = $return_type == self::QUERY_OBJ ? $this->as_obj($clean) : $this->as_array($clean);
            $this->free_result();

            if (!$row) return false;
            else return $row[0];
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }
    /**
     * Executes a general query command on the database
     * @param  string $sql [description]
     * @return [type]      [description]
     */
    public function query_other($sql = '') {
        if (!empty($sql)) {
            $this->run_query($sql);
            $this->free_result();
            return $this->query_succeeded();
        }
    }
    /**
     * Executes an update command on the database
     * @param string $sql   the query to run.
     * @return boolean      returns True if success otherwise False
     */
    public function update($sql = '') {
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            return $this->was_updated();
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }
    /**
     * Executes a delete command on the database
     * @param string $sql   the query to run.
     * @return boolean      returns True if success otherwise False
     */
    public function delete($sql = '') {
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            return $this->was_deleted();
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }

    /**
     * Builds an insert statement and call insert()
     * @param  string $table table
     * @param  array $data  data
     * @return boolean        result
     */
    public function insert_data($table, $data) {
        if (!$data) return false;

        $fields = array();
        $values = array();
        foreach ($data as $field => $value) {
            $fields[] = $field;

            if (is_null($value)) {
                $values[] = "NULL";
            } else if (strtolower($value) == 'now()') {
                $values[] = "NOW()";
            } else {
                $this->escape($value);
                $values[] = "'$value'";
            }
        }

        $insert_query = "INSERT INTO $table (".(implode(", ", $fields)).") VALUES(".implode(", ", $values).");";
        return $this->insert($insert_query);
    }

    /**
     * Executes an insert command on the database
     * @param string $sql   the query to run.
     * @return boolean      returns True if success otherwise False
     */
    public function insert($sql = '') {
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            return $this->was_inserted();
        } else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }

    /**
     * Executes a sql query.
     * @param string $query    the sql statement.
     * @return boolean         true for success, False if not.
     */
    private function run_query($query = null) {
        $this->query = $query;
        // Check to see if the sql statement variable is set.
        if (!is_null($query)) {
            // Determine the query type. (SELECT, UPDATE, INSERT, DELETE etc.)
            $this->query_type = $this->get_query_type($query);
            $this->query_result = mysqli_query($this->linkId, $query);

            if (!$this->query_result) {
                $error = '[ERR] '.$this->get_error();
                if ($this->debug) $error .= PHP_EOL.'[SQL] '.$query;

                trigger_error($error, E_USER_ERROR);
            }

            switch ($this->query_type) {
                case 'INSERT':
                    $this->last_inserted_id = mysqli_insert_id($this->linkId);
                    break;
                case 'SELECT':
                case 'CALL':
                    $this->num_rows = $this->query_succeeded() && !is_bool($this->query_result) ? mysqli_num_rows($this->query_result) : 0;
                    if (!is_bool($this->query_result))
                        $this->types = $this->get_types();

                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Return the total count of rows
     * @return int
     */
    public function get_num_rows() {
        return $this->num_rows;
    }

    /**
     * Return the last query statement
     * @return string
     */
    public function get_query() {
        return $this->query;
    }

    /**
     * Gets the last insert id.
     * @return variant The ID generated for an AUTO_INCREMENT column or False.
     */
    public function get_last_inserted_id() {
        return $this->last_inserted_id;
    }

    /**
     * Free result memory.
     * @return returns True on success or False on failure.
     */
    private function free_result($single_query = true) {
        if (is_resource($this->query_result)) mysqli_free_result($this->query_result);

        // if single query, free other result to avoid "command out of sync" error
        if ($single_query) {
            while (mysqli_more_results($this->linkId) && mysqli_next_result($this->linkId)) {
                if ($dummyResult = mysqli_store_result($this->linkId)) {
                    mysqli_free_result($dummyResult);
                }
            }
        }

        return true;
    }

    /**
     * To determine the query type used in the query. ex: SELECT, INSERT. In order to run the affected_rows(); function below we need to determine the query type.
     * @param string $query     the sql statement.
     * @return string           the first word in the query.
     */
    private function get_query_type($query = '') {
        $query = explode(' ', trim($query));
        return strtoupper($query[0]);
    }

    private function get_types() {
        if (!$this->query_result) return false;
        $types = array();
        if ($fields = mysqli_fetch_fields($this->query_result)) {
            foreach ($fields as $field) {
                $types[$field->name] = $field->type;
            }
        }

        return $types;
    }

    private function set_type($field, &$value) {
        if (is_null($value)) return;

        $mysqli_type = isset($this->types[$field]) ? $this->types[$field] : null;
        switch($mysqli_type) {
            case MYSQLI_TYPE_NULL:
                $type_name = 'null';
                break;
            case MYSQLI_TYPE_TINY:
            case MYSQLI_TYPE_SHORT:
            case MYSQLI_TYPE_LONG:
            case MYSQLI_TYPE_INT24:
            case MYSQLI_TYPE_LONGLONG:
                $type_name = 'int';
                break;
            case MYSQLI_TYPE_FLOAT:
            case MYSQLI_TYPE_DOUBLE:
            case MYSQLI_TYPE_NEWDECIMAL:
                $type_name = 'float';
                break;
            default:
                $type_name = 'string';
                break;
        }

        settype($value, $type_name);
    }

    /**
     * Verifies that the last query executed successfully.
     * @return boolean      true if the last query executed, False if not.
     */
    public function query_succeeded() {
        if (!$this->query_result) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Gets the number of rows affected by the last query executed.
     * @return int   the number of rows affected.
     */
    public function affected_rows() {
        if ($this->query_succeeded()) {
            // Retrieves the number of rows from a result set. This command is only valid for statements like SELECT or SHOW that return an actual result set.
            if (($this->query_type == 'SELECT') || ($this->query_type == 'SHOW')) {
                return mysqli_num_rows($this->query_result);
            } else {
                // To retrieve the number of rows affected by a INSERT, UPDATE, REPLACE or DELETE query, use mysqli_affected_rows().
                return mysqli_affected_rows($this->linkId);
            }
        } else {
            return 0;
        }
    }

    /**
     * Verifies that the current INSERT sql call ran successfully.
     * @return mixed        the last insert id if successful, false if not.
     */
    public function was_inserted() {
        if ($this->query_type == 'INSERT' && $this->query_succeeded()) {
            return $this->get_last_inserted_id();
        } else {
            return false;
        }
    }

    /**
     * Verifies that the current UPDATE sql call ran successfully.
     * @return boolean      true if successful, false if not.
     */
    public function was_updated() {
        if ($this->query_type == 'UPDATE' && $this->query_succeeded()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Verifies that the current DELETE sql call ran successfully.
     * @return boolean      true if successful, false if not.
     */
    public function was_deleted() {
        if ($this->query_type == 'DELETE' && $this->query_succeeded()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return the result as an array.
     * @param boolean $clean    true if the output should return a cleaned array
     * @return mixed            an array of rows if succcessful, false if not.
     */
    private function as_array($clean = null, $callback = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // If the last query ran was unsuccessfull, then return false.
        if (!$this->query_result) {
            $result = false;
        } else {
            if (!$this->affected_rows() == 0) {
                $rows = array();

                while ($row = mysqli_fetch_assoc($this->query_result))
                    array_push($rows, $this->process_row_array($row, $clean, $callback));

                $result = $rows;
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Return the result as an object.
     * @param boolean       true if the output should return a cleaned object
     * @return mixed        an array of object rows if succcessful, false if not.
     */
    private function as_obj($clean = null, $callback = null) {
        $clean = !is_null($clean) ? $clean : $this->clean;
        // If the last query ran was unsuccessfull, then return false.
        if (!$this->query_result) {
            $result = false;
        } else {
            if (!$this->affected_rows() == 0) {
                $rows = array();

                while ($row = mysqli_fetch_object($this->query_result))
                    array_push($rows, $this->process_row_obj($row, $clean, $callback));

                $result = $rows;
            } else {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Cleans the array for HTML display
     * @param  array $row     array input
     * @return array            returns a clean array
     */
    private function process_row_array($row, $clean = true, $callback = null) {
        foreach ($row as $field => $value) {
            $this->set_type($field, $value);
            $new_value = $clean ? self::clean_html_string($value) : $value;

            if ($callback) {
                $result = $callback($new_value, $row, $clean);
                if ($result) $new_value = $result;
            }

            $row[$field] = $new_value;
        }

        return $row;
    }

    /**
     * Cleans the object for HTML display
     * @param  STDClass $row STDClass object
     * @return STDClass      returns a clean STDClass object
     */
    private function process_row_obj($row, $clean = true, $callback = null) {
        foreach ($row as $field => $value) {
            $this->set_type($field, $value);
            $new_value = $clean ? self::clean_html_string($value) : $value;

            if ($callback) {
                $result = $callback($new_value, $row, $clean);
                if ($result) $new_value = $result;
            }

            $row->{$field} = $new_value;
        }

        return $row;
    }

    /**
     * Coverts an STDClass object to array
     * @param  STDClass $object     object to convert
     * @return array                returns the converted object
     */
    private static function object_to_array($object) {
        if (!is_object($object) && !is_array($object))
            return $object;

        if (is_object($object))
            $object = get_object_vars($object);

        return array_map(array(__CLASS__, 'object_to_array'), $object);
    }

    /**
     * Clean's the string
     * @param  string $value string input
     * @return string            returns the cleaned string for HTML display
     */
    private function clean_html_string($value) {
        if (is_null($value)) return $value;
        if (!is_string($value)) return $value;

        $new_value = htmlentities(html_entity_decode($value, ENT_QUOTES));
        return utf8_encode($new_value);
    }
}

?>