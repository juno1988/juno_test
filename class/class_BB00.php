<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_BB00
//

class class_BB00 extends class_top {

    ///////////////////////////////////////////
    // shop들의 list출력

    function BB00()
    {
	global $connect;
	global $template, $line_per_page;

	$sys_connect = sys_db_connect();

	$sql  = "select * from sys_domain where id = '"._DOMAIN_."'";
	$result = mysql_query($sql, $sys_connect);
	$service = mysql_fetch_array($result);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>

