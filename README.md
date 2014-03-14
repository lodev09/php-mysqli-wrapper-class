PHP5 MySQL Helper Class
============================
A very simple yet useful helper class for PHP used in accessing your MySQL database using PHP's native _**mysqli**_. Requires php 5 or higher.

## Features
* Object Oriented (OOP)
* Built-in String Cleaner (`MySQL::clean_html_string` and `MySQL::clean_sql_string`)
* Simplified data fetching
* Extra validation methods
* Or you can ask for more ... :)

## Install
    //require the main class file
    require_once("lib/class.mysql.php");

## Init
Initialize by creating a new instance of MySQL class

    $host = "localhost";
    $db = "test";
    $pwd = "mypass";
    $user = "root";

    //create new instance of the class by passing your db credentials
    $sql = new MySQL($host, $db, $user, $pwd);

## Query
### Select

    $clean = true;

    //Get data by Object
    $obj_data = $sql->query("select * from dt_sample", MySQL::QUERY_OBJ, $clean);
    if ($obj_data) {
        foreach ($obj_data as $data) {
            echo "[".$data->ID."] Name: ".$data->Name."<br />";
        }
    }

    //Get data by Array
    $assoc_data = $sql->query("select * from dt_sample", MySQL::QUERY_ASSOC, $clean);
    if ($assoc_data) {
        foreach ($assoc_data as $data) {
            echo "[".$data["ID"]."] Name: ".$data["Name"]."<br />"; 
        }
    }

### Query a row
Use `MySQL::query_row` to query only the first result set

    $one_row = $sql->query_row("select * from dt_sample order by Name asc");
    echo "First Record: ".($one_row ? $one_row->Name : "(None)");

### Filter
Built-in filter string generator. `MySQL::get_filter_string(array filters[, array options = array(), boolean where = false])`. You can use `MySQL::get_num_rows()` to get the number of rows returned.

    $filter = MySQL::get_filter_string(
        array("Name"=>"LIKE 'B%'", "City"=>"= '".$sql->clean_sql_string("CityFilter'")."'"), 
        array("operator"=>"or"),
        true
    );

    $result = $sql->query("select * from dt_sample ".$filter);
    if ($result) {
        echo "Found ".$sql->get_num_rows()." rows<br />";
        var_dump($result);
    }

### Insert

    //Fields to insert
    $fields = array(
        "name" => "~!@#%^&*()_+'"
    );
    //clean the array
    $fields = $sql->clean_sql_array($fields);

    //Fields to insert (object)
    $fields_obj = new stdClass;
    $fields_obj->city = "'%/\"";
    //clean the object
    $fields_obj = $sql->clean_sql_obj($fields_obj);

    $date = MySQL::get_mysql_datetime();

    $result = $sql->insert("insert into dt_sample(Name, City, Date) values('".$fields["name"]."', '".$fields_obj->city."', '".$date."')");
    var_dump($result);

### Update & Delete
Update and Delete `em

    //update record
    $result = $sql->update("update dt_sample set City = 'NewCity' where Name='MyName'");
    var_dump($result);
    echo "UPDATE: Affected Rows: ".$sql->affected_rows()."<br />";

    //delete record
    $result = $sql->delete("delete from dt_sample where Name = 'MyName'");
    var_dump($result);
    echo "DELETE: Affected Rows: ".$sql->affected_rows()."<br />";

## Reference
MySQL class reference. These are public methods, properties that you can access outside the class. See **code comments** for more information.

### Constants
#### Query Result Type
* `MySQL::QUERY_OBJ = 1`
* `MySQL::QUERY_ASSOC = 2`

### Properties

* `public $host = "localhost"` - database host
* `public $user = "root"` - database username
* `public $pass = ""` - database password
* `public $db = ""` - database name

### Methods

* `MySQL::get_mysql_datetime(string $date)`
 - **$date**: The date string input to be formatted
 - **return**: formatted mysql date string

* `MySQL::get_filter_string(array $filters[, mixed $options = false, boolean $where = false])` build a filter string to be used in a query.
 - **$filters**: Filter array. _see sample code above_.
 - **$options**: Optional parameters. _see sample code above_.
 - **$where**: If `true`, start the filter with the **WHERE** clause otherwise start with **AND**.
 - **return**: formatted string.

* `MySQL::clean_sql_array(array $array)` clean an sql array to be used in a query. Clean means _**escaping**_ special characters.
 - **$array**: the array input to be cleaned.
 - **return**: cleaned array.

* `MySQL::clean_sql_obj(object $obj)` clean an sql array to be used in a query. Clean means _**escaping**_ special characters.
 - **$array**: the array input to be cleaned.
 - **return**: cleaned object.

* `MySQL::clean_sql_string(string $str)` cleans a string to bused in a query.
 - **$str**: the string input.
 - **return**: cleaned `string`.

* `MySQL::get_error()` returns `mysqli_error()`
 - **return**: mysqli error string.

* `MySQL::query($sql [, $return_type = MySQL::QUERY_OBJ, $clean = true])` run an sql statement
 - **$sql**: the sql statement
 - **$return_type**: return type (default `MySQL::QUERY_OBJ`)
 - **$clean**: should it clean the output (useful for echoing HTML content)
 - **return**: mixed (_object_ or _array_)

* `MySQL::query_row($sql [, $return_type = MySQL::QUERY_OBJ, $clean = true])` same as `query()` but returns `1` row

* `MySQL::update($sql)` run an **UPDATE** statement
 - **$sql**: the sql statement
 - **return**: boolean `true` or `false`

* `MySQL::delete($sql)` run a **DELETE** statement
 - **$sql**: the sql statement
 - **return**: boolean `true` or `false`

* `MySQL::insert($sql)` run a **INSERT** statement
 - **$sql**: the sql statement
 - **return**: mixed `int` (last inserted id) or `false`

#### Additional Methods
* `MySQL::was_inserted()` returns `true` if inserted, otherwise `false`
* `MySQL::was_updated()` returns `true` if updated, otherwise `false`
* `MySQL::was_deleted()` returns `true` if deleted, otherwise `false`
* `MySQL::get_num_rows()` returns the number of rows queried
* `MySQL::get_last_inserted_id()` returns the generated id for an AUTO_INCREMENT column or false.
* `MySQL::query_succeeded()` returns `true` if query was successfull, otherwise `false`
* `MySQL::affected_rows()` returns the number of rows affected from UPDATE or DELETE

## Feedback

All bugs, feature requests, pull requests, feedback, etc., are welcome. Visit my site at [www.lodev09.com](http://www.lodev09.com "www.lodev09.com").

## License
See [LICENSE](LICENSE) file.