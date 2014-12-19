<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_H400
//

class class_H400 extends class_top {

    ///////////////////////////////////////////

    function H400()
    {
	global $connect;
	global $template, $search;

	$sys_connect = sys_db_connect();
	$page = $_GET[page];

	$link_url = "?template=H400&";
	if( $search )
	    $link_url .= "search=$search&";

	$sql = "select count(*) cnt from sys_upgrade_board where is_delete = 0 and share = 1";
	if( $search )
	    $sql .= " and (subject like '%$search%' or content like '%$search%')";
	$total = mysql_fetch_array(mysql_query($sql, $sys_connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$starter = $page ? ($page-1) * $line_per_page : 0;

	$sql = "select * from sys_upgrade_board where is_delete=0 and share = 1"; 
	if( $search )
	    $sql .= " and (subject like '%$search%' or content like '%$search%')";
	$sql .= " order by input_time desc limit $starter, $line_per_page";
	$result = mysql_query($sql, $sys_connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function H401()
    {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$no = $_GET[no];
	$sql = "select * from sys_upgrade_board where no = '$no'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>
