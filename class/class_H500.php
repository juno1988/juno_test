<?
require_once "class_top.php";
require_once "class_H.php";
require_once "class_file.php";

////////////////////////////////
// class name: class_H500
//

class class_H500 extends class_top {

    ///////////////////////////////////////////

    function H500()
    {
	global $connect;
	global $template;

	$page = $_REQUEST[page];
	$category = $_REQUEST[category];

	if ($category) {
		$options = " and category = '$category'";
	}

	$link_url = "?template=H500&";

    //-----------------------------------
    $sql = "select category from internal_board_category";
    $result = mysql_query($sql, $connect) or die(mysql_error());
    $category_rows = @mysql_num_rows($result);
    while ($list = @mysql_fetch_assoc($result)) {
		$categories[] = $list[category];
    }

    //-----------------------------------
	$sql = "select count(*) cnt from internal_board 
		 where is_delete = 0
		       ${options}
		";
	$total = mysql_fetch_array(mysql_query($sql, $connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$starter = $page ? ($page-1) * $line_per_page : 0;

	$sql = "select * from internal_board 
		 where is_delete = 0
		       ${options}
		 order by no desc, subno asc limit $starter, $line_per_page";
	$result = mysql_query($sql, $connect) or die(mysql_error());

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	//-- top에서 사용
	//-- 읽지 않은 메시지 개수
    function unread_message()
	{
		global $connect;

		$strHTML = "";
		$sql = "select count(no) cnt from internal_board
				 where viewed is NULL
				    or (viewed != 'all' and
					    viewed not like '%$_SESSION[LOGIN_ID],%')";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));
		if ($list[cnt]) {
			$strHTML = "(<b>$list[cnt]</b>)";
		}

		echo $strHTML;
	}

    function H501()
    {
	global $connect;
	global $template;

	$no = $_GET[no];
	$subno = $_GET[subno];
	$sql = "select * from internal_board where no = '$no' and subno='$subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H502()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H503()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H504()
    {
	global $connect;
	global $template, $line_per_page;

	$no = $_GET[no];
	$subno = $_GET[subno];
	$sql = "select * from internal_board where no = '$no' and subno='$subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H505()
    {
		global $connect;
		global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function insert()
    {
	global $connect;
	global $attached;

	$writer 	= $_POST[writer];
	$mobile 	= $_POST[mobile];
	$email 		= $_POST[email];
	$content 	= $_POST[content];
	$subject 	= $_POST[subject];
	$passwd 	= $_POST[passwd];
	$category 	= $_POST[ca_select];

	$subject 	= addslashes($subject);
	$content 	= addslashes($content);

	$sql = "select max(no) from internal_board limit 1";
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

	$sql = "insert into internal_board set
		no = '$max_no',
		subno = '0',
		input_time = now(),
		userid = '".$_SESSION[LOGIN_ID]."',
		writer = '$writer',
		mobile = '$mobile',
		email  = '$email',
		subject = '$subject',
		content = '$content',
		passwd = '$passwd',
		attached = '$attached_file',
		category = '$category',
		ip = '".$_SERVER[REMOTE_ADDR]."'
	";

	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=H500';</script>";
	exit;
    }

    function reply()
    {
	global $connect;

	$no = $_POST[no];
	$subno = $_POST[subno];
	$depth = $_POST[depth];

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

	$sql = "insert into internal_board set
		no = '$no',
		subno = '$input_subno',
		input_time = now(),
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
	$sql = "update internal_board set reply = 1 where no = '$no' and subno = '$subno'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=H500';</script>";
	exit;
    }

    function delete()
    {
		global $connect;
	
		$no = $_REQUEST[no];
		$subno = $_REQUEST[subno];
	
		$sql = "delete from internal_board
		 	where no = '$no' and subno = '$subno'";
		
		$result = mysql_query($sql, $connect) or die(mysql_error($connect));
		$rows = mysql_affected_rows($connect);

		echo "<script>alert('삭제되었습니다.');</script>";
		echo "<script>document.location.href = '?template=H500';</script>";
		exit;
	}


  	function get_board_category() {
    	global $connect;

    	$sql = "select category from internal_board_category
				 order by category";
    	$result = @mysql_query($sql, $connect);

    	return $result;
  	}

    function del_category() {

		global $connect;

		$category = $_REQUEST[category];
		$del_sql = "delete from internal_board_category
					 where category = '$category'";

		@mysql_query($del_sql, $connect);
		$this->show_category();
    }


	function add_category() {
		global $connect;

		$category = $_REQUEST[category];

		$sql = "select count(*) cnt from internal_board_category";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));
		$rows = $list[cnt];

		$sql = "select category from internal_board_category
				 where category = '$category'";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		$list = mysql_fetch_assoc($result);
		

		if ($list) {
			echo iconv("utf-8", "euckr", "<div class=list><font color=red>이미 등록된 카테고리입니다.</font></div>");
		} else if ($rows >= 5) {
			echo iconv("utf-8", "euckr", "<div class=list><font color=red>최대 등록가능 개수는 5개 입니다.</font></div>");
		} else {
	
			$ins_sql = "insert into internal_board_category (category)
						values ('$category')";
			mysql_query($ins_sql, $connect) or die(mysql_error());	
		}

		$this->show_category();
	}

	function show_category() {
		global $connect;

		$category = $_REQUEST[category];

		$sql = "select category from internal_board_category";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result)) {
			echo "<div class=list>$list[category] <a href=javascript:del_category('$list[category]')><img src=/images2/del.jpg></a></div>";
		}
	}


    function H506()
    {
		global $connect;
		global $template;

    	$no = $_REQUEST[no];
    	$subno = $_REQUEST[subno];
    	$sql = "select * from internal_board where no = '$no' and subno='$subno'";
    	$list = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	function modify()
	{
		global $connect;
		global $attached;

		$no 		= $_REQUEST[no];
		$subno 		= $_REQUEST[subno];
		$subject 	= $_REQUEST[subject];
		$content 	= $_REQUEST[content];

		$subject 	= addslashes($subject);
		$content 	= addslashes($content);

/*
		if ($attached)
		{
			$upload_dir = "./"._upload_path."/";
			$attached_file = $_FILES['attached']['name'];
			if (!move_uploaded_file($_FILES['attached']['tmp_name'], $upload_dir.$attached_file)) {
				echo "file upload failed";
				exit;
			}
		}
*/

		$sql = "update internal_board set
					   subject = '$subject',
					   content = '$content'
				 where no = '$no'
				   and subno = '$subno'";
		
		mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>alert('수정되었습니다.');</script>";
	echo "<script>document.location.href = '?template=H501&no=$no&subno=$subno';</script>";
	exit;
    }


	function save_file()
	{
		global $connect;

		$sql = "select no, writer, input_time, subject, content from internal_board order by no desc";
		$result = mysql_query($sql, $connect);
		while ($list = mysql_fetch_assoc($result))
		{
			$_arr[] = $list;
		}

		$fn = $this->make_file($_arr);
		echo "<script>parent.set_file('$fn');</script>";
	}

	function download()
	{
        global $filename;

        $obj = new class_file();
        $obj->download_file($filename, "board_".date(Ymd).".xls");
	}

	function make_file($arr_datas)
	{
		$filename = "board_" . date("Ymd"). ".xls";
		$fp = fopen(_upload_dir . $filename, "w");

        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";

		$_arr = array(
					"NO",
					"작성자",
					"작성일",
					"제목",
					"내용"
		);

		$buffer .= "<tr>\n";
		foreach ($_arr as $value)
			$buffer .= "<td>" . $value . "</td>";

		$buffer .= "</tr>\n";

		// data
		foreach ($arr_datas as $row)
		{
			$buffer .= "<tr>\n";

			foreach ($row as $key=>$value)
			{
				$buffer .= "<td>" . $value . "</td>";
			}

			$buffer .= "</tr>\n";
		}
		$buffer .= "</table>\n";

        fwrite($fp, $buffer);
		fclose($fp);

		return $filename;
	}

}

?>
