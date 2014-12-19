<?
include_once "../lib/lib_common.php";
require_once "class_statrule.php";

$connect = db_connect();
$obj = new class_statrule();

$query = "select * from orders where seq=20871";
$result = mysql_query( $query, $connect );
$data   = mysql_fetch_array( $result );
#print_r ( $data );

$aa = $obj->get_price( $data, "supply_price" );

print "\n-----------\n";
print_r ( $aa );
print "\n-----------\n";

?>
