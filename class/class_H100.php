<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_H100
//

class class_H100 extends class_top {

    ///////////////////////////////////////////

    function H100()
    {
	global $connect;
	global $template, $search;

	$sys_connect = sys_db_connect();
	$page = $_GET[page];

	$link_url = "?template=H100&";
	if( $search )
	    $link_url .= "search=$search&";

	$sql = "select count(*) cnt from sys_notice_board where is_delete = 0";
	if( $search )
	    $sql .= " and (subject like '%$search%' or content like '%$search%')";
	$total = mysql_fetch_array(mysql_query($sql, $sys_connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$starter = $page ? ($page-1) * $line_per_page : 0;

	$sql = "select * from sys_notice_board where is_delete=0 "; 
	if( $search )
	    $sql .= " and (subject like '%$search%' or content like '%$search%')";
	$sql .= " order by input_time desc limit $starter, $line_per_page";
	$result = mysql_query($sql, $sys_connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function H101()
    {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$no = $_GET[no];
	$sql = "select * from sys_notice_board where no = '$no'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H102()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H103()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function insert()
    {
	global $connect;

	$subject = $_POST[subject];
	$content = $_POST[content];
	$subject = addslashes($subject);
	$content = addslashes($content);

	$sql = "insert into sys_notice_board (input_time, subject, content) values (now(), '$subject', '$content')";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=H100';</script>";
	exit;
    }

    function H104()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function notice()
    {
	global $connect;

	$sys_connect = sys_db_connect();

	$no = $_REQUEST[no];
	$myid = _DOMAIN_ . ":" . $_SESSION[LOGIN_ID];

	$sql = "select readok from sys_notice_board
		 where no = '$no'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

	if (strlen($list[readok]) == 0) $readok = $myid . ",";
	else $readok = $list[readok] . $myid . ",";

	// 
	$sql = "update sys_notice_board  
		   set readok = '$readok'
		 where no = '$no'";

	mysql_query($sql, $sys_connect) or die(mysql_error());
	echo "<script>document.location.href = '?template=H101&no=${no}';</script>";
	exit;
	
    }

}

?>
