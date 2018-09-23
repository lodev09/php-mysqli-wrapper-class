PHP mysqli Wrapper Class
============================

> If you're working on a new PHP project, I recommend for you to use PDO instead. I'm only maintaining this class because old projects are still using this.

You can find my own version of PDO wrapper [here](https://github.com/lodev09/php-models)

A very simple yet useful helper class for PHP used in accessing your MySQL database using PHP's native _**mysqli**_. Requires php 5 or higher.

## Features
* Object Oriented (OOP)
* Built-in escapes and cleaning `MySQL::escape`
* Simplified data fetching
* Extra validation methods
* Or you can ask for more ... :)

## Install
```php
//require the main class file
require_once("lib/class.mysql.php");
```
## Init
Initialize by creating a new instance of MySQL class
```php
$host = "localhost";
$db = "test";
$pwd = "mypass";
$user = "root";

//create new instance of the class by passing your db credentials
$sql = new MySQL($host, $db, $user, $pwd);
```
## Query
### Select
```php
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
```
### Query a row
Use `MySQL::query_row` to query only the first result set
```php
$one_row = $sql->query_row("select * from dt_sample order by Name asc");
echo "First Record: ".($one_row ? $one_row->Name : "(None)");
```
### Filter
Built-in filter string generator. `MySQL::build_filter_string(array filters[, array options = array(), boolean where = false])`. You can use `MySQL::get_num_rows()` to get the number of rows returned.
```php
$filter = MySQL::build_filter_string(
	array("Name"=>"LIKE 'B%'", "City"=>"= '".$sql->escape("CityFilter'")."'"), 
	array("operator"=>"or"),
	true
);

$result = $sql->query("select * from dt_sample ".$filter);
if ($result) {
	echo "Found ".$sql->get_num_rows()." rows<br />";
	var_dump($result);
}
```
### Insert
```php
//Fields to insert
$fields = array(
	"name" => "~!@#%^&*()_+'"
);
//clean the array
$fields = $sql->escape($fields);

//Fields to insert (object)
$fields_obj = new stdClass;
$fields_obj->city = "'%/\"";
//clean the object
$fields_obj = $sql->escape($fields_obj);

$date = MySQL::get_mysql_datetime();

$result = $sql->insert("insert into dt_sample(Name, City, Date) values('".$fields["name"]."', '".$fields_obj->city."', '".$date."')");
var_dump($result);
```
### Update & Delete
Update and Delete `em
```php
//update record
$result = $sql->update("update dt_sample set City = 'NewCity' where Name='MyName'");
var_dump($result);
echo "UPDATE: Affected Rows: ".$sql->affected_rows()."<br />";

//delete record
$result = $sql->delete("delete from dt_sample where Name = 'MyName'");
var_dump($result);
echo "DELETE: Affected Rows: ".$sql->affected_rows()."<br />";
```
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
* `public $debug = false` - turn debug on/off

### Methods

* `MySQL::get_mysql_datetime(string $date)` - Get a MySQL formatted date. Defaults `now()`
* `MySQL::build_filter_string(array $filters[, mixed $options = false, boolean $where = false])` - build a filter string to be used in a query.
* `MySQL::escape(mixed $var)` escape an sql array, string or object to be used in a query.
* `MySQL::get_error()` returns `mysqli_error()`
* `MySQL::query($sql [, $return_type = MySQL::QUERY_OBJ, $clean = true])` run an sql statement
* `MySQL::query_row($sql [, $return_type = MySQL::QUERY_OBJ, $clean = true])` same as `query()` but returns the first row
* `MySQL::update($sql)` run an **UPDATE** statement
* `MySQL::delete($sql)` run a **DELETE** statement
* `MySQL::insert($sql)` run a **INSERT** statement

#### Additional Methods
* `MySQL::was_inserted()` returns `true` if inserted, otherwise `false`
* `MySQL::was_updated()` returns `true` if updated, otherwise `false`
* `MySQL::was_deleted()` returns `true` if deleted, otherwise `false`
* `MySQL::get_num_rows()` returns the number of rows queried
* `MySQL::get_last_inserted_id()` returns the generated id for an AUTO_INCREMENT column or false.
* `MySQL::query_succeeded()` returns `true` if query was successfull, otherwise `false`
* `MySQL::affected_rows()` returns the number of rows affected from UPDATE or DELETE

## Feedback
All bugs, feature requests, pull requests, feedback, etc., are welcome. Visit my site at [www.lodev09.com](http://www.lodev09.com "www.lodev09.com") or email me at [lodev09@gmail.com](mailto:lodev09@gmail.com)

## Credits
&copy; 2011-2014 - Coded by Jovanni Lo / [@lodev09](http://twitter.com/lodev09)

## License
Released under the [MIT License](http://opensource.org/licenses/MIT).
See [LICENSE](LICENSE) file.
