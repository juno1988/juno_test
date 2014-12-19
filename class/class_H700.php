<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_H700
//

class class_H700 extends class_top {

    ///////////////////////////////////////////

    function H700()
    {
	global $connect;
	global $template;

	$page = $_GET[page];

	$link_url = "?template=H700&";

	if ($_SESSION[LOGIN_LEVEL] == 0)
	{
	    $options = "and (vendor_code = '".$_SESSION[LOGIN_CODE]."' or vendor_code = '1001') ";
	}
	$sql = "select count(*) cnt from vendor_board 
		 where is_delete = 0
		   and is_admin = 0
		       ${options}
		";
	$total = mysql_fetch_array(mysql_query($sql, $connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$starter = $page ? ($page-1) * $line_per_page : 0;

	$sql = "select * from vendor_board 
		 where is_delete = 0
		   and is_admin = 0
		       ${options}
		 order by no desc, subno asc limit $starter, $line_per_page";
	$result = mysql_query($sql, $connect) or die(mysql_error());

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function H701()
    {
	global $connect;
	global $template, $no, $subno;

	$sql = "select * from vendor_board where no = '$no' and subno='$subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H702()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H703()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H704()
    {
	global $connect;
	global $template, $line_per_page;

	$no = $_GET[no];
	$subno = $_GET[subno];
	$sql = "select * from vendor_board where no = '$no' and subno='$subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function insert()
    {
	global $connect;
	global $attached;

	foreach ($_REQUEST as $key=>$value)
	  $$key = $value;

	$subject = addslashes($subject);
	$content = addslashes($content);

	$sql = "select max(no) from vendor_board limit 1";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$max_no = $list[0] + 1;

	if ($attached)
	{
	  $upload_dir = "./"._upload_path."/";
	  $attached_file = $_FILES['attached']['name'];
	  if (!move_uploaded_file($_FILES['attached']['tmp_name'], $upload_dir.$attached_file))
	  {
	    echo "file upload failed";
	    exit;
	  }
	}

	$sql = "insert into vendor_board set
		no = '$max_no',
		subno = '0',
		input_time = now(),
		vendor_code = '".$_SESSION[LOGIN_CODE]."',
		userid = '".$_SESSION[LOGIN_ID]."',
		writer = '$writer',
		mobile = '$mobile',
		email  = '$email',
		subject = '$subject',
		content = '$content',
		passwd = '$passwd',
		is_admin = '$is_admin',
		attached = '$attached_file',
		ip = '".$_SERVER[REMOTE_ADDR]."'
	";

	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=H700';</script>";
	exit;
    }

    function reply()
    {
	global $connect;

	$no = $_POST[no];
	$subno = $_POST[subno];
	$depth = $_POST[depth];
	$vendor_code = $_POST[vendor_code];

	if ($depth == 0)
	{
	    $sql = "select max(subno) from tbl_${dbname}_board where no = $no and depth= 1";
	    $list = mysql_fetch_array(mysql_query($sql, $connect));
	    if ($list[0]) $input_subno = ++$list[0];
	    else $input_subno = "A";
	}
	else
	{
	    $sql = "select max(subno) from tbl_${dbname}_board where no = $no and parent = '$subno' and depth=$depth+1";
	    $list = mysql_fetch_array(mysql_query($sql, $connect));
	    if ($list[0]) $input_subno = ++$list[0];
	    else
	    {
		$first_char = substr($subno, 0, 1);
		$input_subno = $subno.$first_char;
	    }
	}
	$depth = $depth + 1;

	$writer = $_POST[writer];
	$content = $_POST[content];
	$subject = $_POST[subject];
	$passwd = $_POST[passwd];

	$subject = addslashes($subject);
	$content = addslashes($content);

	$sql = "insert into vendor_board set
		no = '$no',
		subno = '$input_subno',
		input_time = now(),
		vendor_code = '$vendor_code',
		userid = '".$_SESSION[LOGIN_ID]."',
		writer = '$writer',
		subject = '$subject',
		content = '$content',
		passwd = '$passwd',
		reply = '2',
		depth = '$depth',
		ip = '".$_SERVER[REMOTE_ADDR]."'
	";

	mysql_query($sql, $connect) or die(mysql_error());

	/////////////////////////
	$sql = "update vendor_board set reply = 1 where no = '$no' and subno = '$subno'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=H700';</script>";
	exit;
    }

    function delete()
    {
	global $connect;

	$no = $_REQUEST[no];
	$subno = $_REQUEST[subno];

	$sql = "delete from vendor_board
		 where no = '$no' and subno = '$subno'";
	
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>document.location.href = '?template=H700';</script>";
	exit;
    }

    function ack()
    {
	global $connect;

	$no = $_REQUEST[no];
	$subno = $_REQUEST[subno];

	$sql = "select ack from vendor_board where no = '$no' and subno = '$subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	
	$ack = $list[ack] . $_SESSION[LOGIN_ID] . ",";
	$sql = "update vendor_board set ack = '$ack'
		 where no = '$no' and subno = '$subno'";
	
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>document.location.href = '?template=H701&no=$no&subno=$subno';</script>";
    }
}

?>
