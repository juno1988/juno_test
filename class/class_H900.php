<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_H900
//

class class_H900 extends class_top {

    ///////////////////////////////////////////

    function H900()
    {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$page = $_GET[page];

	$link_url = "?template=H900&";

    if ($_REQUEST[category]) {
		$options = " and category = '$_REQUEST[category]'";
    }

	$sql = "select count(*) cnt from sys_faq_board where is_open = 1 ${options} ";
	$total = mysql_fetch_array(mysql_query($sql, $sys_connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$starter = $page ? ($page-1) * $line_per_page : 0;


	$sql = "select * from sys_faq_board where is_open = 1 ${options} order by input_time desc limit $starter, $line_per_page";
	$result = mysql_query($sql, $sys_connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function H901()
    {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$no = $_REQUEST[no];
	$sql = "select * from sys_faq_board where no = '$no'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>
