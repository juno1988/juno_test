<?
require_once "class_top.php";
require_once "class_A.php";

////////////////////////////////
// class name: class_A200
//

class class_A200 extends class_top {

    ///////////////////////////////////////////
    // shop들의 list출력

    function A200()
    {
	global $connect;
	global $template, $line_per_page;

	$sys_connect = sys_db_connect();
	$sql  = "select * from userinfo where id = '$_SESSION[LOGIN_ID]' and level >= 8";
	$result = mysql_query($sql, $connect);
	$list = mysql_fetch_array($result);

	$sql  = "select * from sys_domain where id = '"._DOMAIN_."'";
	$result = mysql_query($sql, $sys_connect);
	$service = mysql_fetch_array($result);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>
