<?php

/**
 * MySQL Wrapper Class in PHP5
 * @author Jovanni Lo
 * @link http://www.lodev09.com
 * @see http://php.net/manual/en/book.mysql.php
 * @license 
 * The MIT License (MIT)
 * Copyright (c) 2014 Jovanni Lo
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
     * construct class
     * @param string $host     server/host of MySQL
     * @param string $database database name
     * @param string $username user
     * @param string $password password
     */
    public function __construct($host = 'localhost', $database = '', $username = '', $password = '') {
        if (empty($database) && empty($username) && empty($password)) {
            trigger_error('Invalid parameter values to establish connection.', E_USER_ERROR);
        } else {
            if (!$this->connect($host, $database, $username, $password)) {
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
        }
        unset($this);
    }

    /**
     * Establishes a connection to the database specified
     * @param  string $host     the host to connect to
     * @param  string $database the database to connect to
     * @param  string $username the username of the db to connect to
     * @param  string $password the password of the db to connect to
     * @return boolean          returns true if successfully connected
     */
    private function connect($host, $database, $username, $password) {
        $this->host = $host;
        $this->db = $database;
        $this->user = $username;
        $this->pass = $password;
        if (is_null($this->linkId)) {
            $this->linkId = mysqli_connect($host, $username, $password) or trigger_error('Could not establish a connection.',
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
    public static function get_mysql_datetime($date="") {
        if ($date == "")
            return date( 'Y-m-d H:i:s');
        else {
            $stamp = strtotime($date);
            return date( 'Y-m-d H:i:s', $stamp );
        }
    }

    /**
     * Process a filter string for query statement
     * @param  mixed  $filters  the filter array or object
     * @param  mixed $options   optional filters
     * @param  boolean $where   true if we start from WHERE clause, otherwise AND
     * @return string           returns string filter
     */
    public static function get_filter_string($filters, $options = false, $where = false) {
        $filter_str = "";
        $operator = "and";
        $enclose = false;
        if ($options) {
            $options = (object)$options;
            $operator = isset($options->operator) ? $options->operator : "and";
            $enclose = isset($options->enclose) ? $options->enclose : false;
        }
        $arr_filters = array();
        foreach ($filters as $field => $filter) {
            $arr_filters[] = "$field $filter";
        }
        if ($arr_filters) {
            $filter_str = implode(" $operator ", $arr_filters);
            return ($where ? "where" : "and")." ".($enclose ? "(" : "").$filter_str.($enclose ? ")" : "");
        } else return "";
        
    }

    /**
     * Clean an array of strings for query
     * @param  array $array array input
     * @return array        return the clean array
     */
    public function clean_sql_array($array) {
        return array_map(array($this, "clean_sql_string"), $array);
    }

    /**
     * Clean an object of strings for query
     * @param  object $array object input
     * @return object        return the clean object
     */
    public function clean_sql_obj($obj) {
        return (object)array_map(array($this, "clean_sql_string"), self::object_to_array($obj));
    }

    /**
     * Clean the string to be used by MySQL
     * @param string $str  the string to be cleaned
     * @return string      return the clean string
     */
    public function clean_sql_string($str) {
        return mysqli_real_escape_string($this->linkId, $str);    
    }

    /**
     * Retrieves the last error.
     * @return string the error text from the last MySQL function, or empty string if no error occurred. 
     */
    public function get_error() {
        return mysqli_error($this->linkId);
    }

    /**
     * Executes a command on the database.
     * @param string $sql         the query to run.
     * @param mixed $return_type  return type of the query
     * @param boolean $clean      true if you want to clean the values for HTML display
     * @return mixed              returns the result object/array data
     */
    public function query($sql = '', $return_type = self::QUERY_OBJ, $clean = true) {
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            if ($return_type == self::QUERY_ASSOC) return $this->as_array($clean);
            elseif ($return_type == self::QUERY_OBJ) return $this->as_obj($clean);
        }
        // Parameters are empty.
        else {
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
    public function query_row($sql = '', $return_type = self::QUERY_OBJ, $clean = true) {
        // Check to see that the parameters are not empty.
        if (!empty($sql)) {

            // Execute the query.
            $this->run_query($sql);
            if ($return_type == self::QUERY_ASSOC) $row = $this->as_array($clean);
            elseif ($return_type == self::QUERY_OBJ) $row = $this->as_obj($clean);

            if (!$row) return false;
            else  return $row[0];
        }
        // Parameters are empty.
        else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
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
        }
        // Parameters are empty.
        else {
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
        }
        // Parameters are empty.
        else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
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
        }
        // Parameters are empty.
        else {
            trigger_error('You need to provide a query.', E_USER_ERROR);
        }
    }

    /**
     * Executes a sql query.
     * @param string $query    the sql statement.
     * @return boolean         true for success, False if not.
     */
    private function run_query($query = null) {
        // Check to see if the sql statement variable is set.
        if (!is_null($query)) {
            // Determine the query type. (SELECT, UPDATE, INSERT, DELETE etc.)
            $this->query_type = $this->get_query_type($query);

            if (!$this->query_result = mysqli_query($this->linkId, $query)) {
                trigger_error('[ERR] '.$this->get_error(), E_USER_ERROR);
            }

            switch ($this->query_type) {
                case 'INSERT':
                    $this->last_inserted_id = mysqli_insert_id($this->linkId);
                    break;
                case 'DELETE':
                case 'UPDATE':
                    break;
                default:
                    $this->num_rows = $this->query_succeeded() ? mysqli_num_rows($this->query_result) : 0;
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
    private function free_result() {
        return mysqli_free_result($this->query_result);
    }

    /**
     * To determine the query type used in the query. ex: SELECT, INSERT. In order to run the affected_rows(); function below we need to determine the query type.
     * @param string $query     the sql statement.
     * @return string           the first word in the query.
     */
    private function get_query_type($query = '') {
        $query = explode(' ', $query);
        return strtoupper($query[0]);
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
    private function as_array($clean = true) {
        // If the last query ran was unsuccessfull, then return false.
        if (!$this->query_result) {
            return false;
        } else {
            if (!$this->affected_rows() == 0) {
                $rows = array();

                while ($row = mysqli_fetch_assoc($this->query_result)) {
                    array_push($rows, $clean ? $this->clean_html_string_array($row) : $row);
                }

                $this->free_result();
                return $rows;
            } else {
                return false;
            }
        }
    }

    /**
     * Return the result as an object.
     * @param boolean       true if the output should return a cleaned object 
     * @return mixed        an array of object rows if succcessful, false if not.
     */
    private function as_obj($clean = true) {
        // If the last query ran was unsuccessfull, then return false.
        if (!$this->query_result) {
            return false;
        } else {
            if (!$this->affected_rows() == 0) {
                $rows = array();

                while ($row = mysqli_fetch_object($this->query_result)) {
                    array_push($rows, $clean ? $this->clean_html_string_obj($row) : $row);
                }

                $this->free_result();
                return $rows;
            } else {
                return false;
            }
        }
    }

    /**
     * Cleans the array for HTML display
     * @param  array $array     array input
     * @return array            returns a clean array
     */
    private function clean_html_string_array($array) {
        return array_map(array($this, "clean_html_string"), $array);
    }

    /**
     * Cleans the object for HTML display
     * @param  STDClass $obj STDClass object
     * @return STDClass      returns a clean STDClass object
     */
    private function clean_html_string_obj($obj) {
        return (object)array_map(array($this, "clean_html_string"), self::object_to_array($obj));
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
     * @param  string $str_value string input
     * @return string            returns the cleaned string for HTML display
     */
    private function clean_html_string($str_value) {
        if (is_null($str_value)) $str_value = "";
        $new_str = is_string($str_value) ? htmlentities(html_entity_decode($str_value, ENT_QUOTES)) : $str_value;
        return nl2br(utf8_encode($new_str));
    }
}

?>
