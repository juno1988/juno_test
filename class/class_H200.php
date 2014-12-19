<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_H200
//

class class_H200 extends class_top {

    ///////////////////////////////////////////

    function H200()
    {
		global $connect;
		global $template;

		$sys_connect = sys_db_connect();
		foreach ($_REQUEST as $key=>$value) $$key = $value;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }



    function H201()
    {
		global $connect;
		global $template;

		$sys_connect = sys_db_connect();
		foreach ($_REQUEST as $key=>$value) $$key = $value;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H202()
    {
		global $connect;
		global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H203()
    {
		global $connect;
		global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function H204()
    {
		global $connect;
		global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


	//문답게시판 이용가능여부 check
	function is_possible_post()
	{
		global $connect;
		$sys_connect = sys_db_connect();
		
		$val[error] = 0;	
		$domain =  array("ezadmin", "pimz", "changki77", "ilovej");
		if(in_array(_DOMAIN_, $domain))
		{
			$sql = "SELECT * FROM sys_tcs_board WHERE gid = 'client' AND crdate = date(now()) and domain='"._DOMAIN_."' AND userid = '".$_SESSION[LOGIN_ID]."'";		
			//오늘 작성한 글이 있음.
			if(mysql_num_rows(mysql_query($sql, $sys_connect)))
				$val[error] = 1;
		}
		
    	echo json_encode( $val );
	}


	//---------------------------------------------
	// 문답게시판 등록
    function insert()
    {
		global $connect;
		$sys_connect = sys_db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$subject = addslashes($subject);
		$content = addslashes($content);

		$is_secret = ($secret) ? 1 : 0;

		$attached_name = $_FILES['attached']['name'];
		if(!empty($attached_name))
		{
			$upload_dir = _upload_dir;
			if (!move_uploaded_file($_FILES['attached']['tmp_name'], $upload_dir.$attached_name)) {
				echo "upload failed";
				exit;
			}
		}


		$sql = "select max(no) from sys_tcs_board limit 1";
		$list = mysql_fetch_array(mysql_query($sql, $sys_connect));
		$max_no = $list[0] + 1;

		$url = $_SERVER[HTTP_HOST];

		$sql = "insert into sys_tcs_board set
						no 			= '$max_no',
						subno 		= '0',
						crdate 		= now(),
						crtime 		= now(),
						gid 		= 'client',
						domain 		= '"._DOMAIN_."',
						userid 		= '".$_SESSION[LOGIN_ID]."',
						passwd 		= '$passwd',
						writer 		= '$writer',
						mobile 		= '$mobile',
						email  		= '$email',
						subject 	= '$subject',
						content 	= '$content',
						attached 	= '$attached_name',
						url 		= '$url',
						is_secret	= '$is_secret',
						ip 			= '".$_SERVER[REMOTE_ADDR]."'
		";

		mysql_query($sql, $sys_connect) or die(mysql_error());

		// mail to 3 person
		$send_content = 
			_DOMAIN_ . "<br>
			$writer<br>
			$mobile<br>
			$email<br><br>
			<b>" . stripslashes($subject). "</b><br>
			$content
		";

		$subject = "[" . _DOMAIN_ . "] " . iconv("utf-8", "euckr", "문답게시판 요청등록");
		$from_name = iconv("utf-8", "euckr", "이지어드민") . "<webmaster@ezadmin.co.kr>";
		$from = "From:$from_name\nContent-Type:text/html";

		mail("syhwang@pimz.co.kr", $subject, $send_content, $from);
		mail("jkryu@pimz.co.kr",   $subject, $send_content, $from);
		mail("khjang@pimz.co.kr",  $subject, $send_content, $from);
		mail("jypark@pimz.co.kr",  $subject, $send_content, $from);
		mail("yjpark@pimz.co.kr",  $subject, $send_content, $from);

		echo "<script>document.location.href = '?template=H200';</script>";
		exit;
    }

    function H104()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


	//---------------------------------------------
    function verify_passwd()
    {
		global $connect;
		$sys_connect = sys_db_connect();

		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$sql = "select passwd from sys_tcs_board where no = '$no' and subno = 1";
		$list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));

		if ($list[passwd] == $passwd && $list[passwd]) {
			echo 1;
		} else {
			echo 0;
		}
	}

}

?>
