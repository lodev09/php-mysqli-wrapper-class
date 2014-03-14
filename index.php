<?php

require_once("lib/class.mysql.php");

$host = "localhost";
$db = "test";
$pwd = "roottest";
$user = "root";

$sql = new MySQL($host, $db, $user, $pwd);

$clean = true; //Do you want to clean the output for HTML Display?

$obj_data = $sql->query("select * from dt_sample", MySQL::QUERY_OBJ, $clean);
if ($obj_data) {
	foreach ($obj_data as $data) {
		echo "[".$data->ID."] Name: ".$data->Name."<br />";
	}
}
echo "<br />";
$one_row = $sql->query_row("select * from dt_sample order by Name asc");
echo "First Record: ".($one_row ? $one_row->Name : "(None)");
echo "<br /><br />";

//filter
$filter = MySQL::build_filter_string(
	array("Name"=>"LIKE 'B%'", "City"=>"= '".$sql->escape("CityFilter'")."'"), 
	array("operator"=>"or"), //default is and
	true //we start with WHERE clause, unless you want to append the fitler
);

$result = $sql->query("select * from dt_sample ".$filter);
if ($result) {
	echo "Found ".$sql->get_num_rows()." rows<br />";
	var_dump($result);
}

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



//update record
$result = $sql->update("update dt_sample set City = 'NewCity' where Name='MyName'");
var_dump($result);
echo "UPDATE: Affected Rows: ".$sql->affected_rows()."<br />";


//delete record
$result = $sql->delete("delete from dt_sample where Name = 'MyName'");
var_dump($result);
echo "DELETE: Affected Rows: ".$sql->affected_rows()."<br />";

echo "<br /><br />";
//query and get data by array
$assoc_data = $sql->query("select * from dt_sample", MySQL::QUERY_ASSOC, $clean);
if ($assoc_data) {
	foreach ($assoc_data as $data) {
		echo "[".$data["ID"]."] Name: ".$data["Name"]."<br />"; 
	}
}


?>