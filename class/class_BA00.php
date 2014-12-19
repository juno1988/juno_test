<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_BA00
//
class class_BA00 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function BA00()
    {
	global $connect;
	global $template, $line_per_page;

	$sql  = "select * from userinfo where id = '$_SESSION[LOGIN_ID]'";
	$result = mysql_query($sql, $connect);
	$list = mysql_fetch_array($result);
        $master_code = substr( $template, 0,1);

	// config
	$query = "select * from userinfo where id='root'";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_assoc( $result );

	//-------------------------------
	// sys_domain
	$sys_connect = sys_db_connect();
	$sql = "select * from sys_domain where id = '" . _DOMAIN_ . "'";
	$sys_list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function modify()
    {
	global $connect;

	$mycode = $_REQUEST[mycode];

	$corpname = $_REQUEST[corpname];
	$boss = $_REQUEST[boss];
	$corpno1 = $_REQUEST[corpno1];
	$corpno2 = $_REQUEST[corpno2];
	$corpno3 = $_REQUEST[corpno3];
	if ($corpno1) $corpno = $corpno1."-".$corpno2."-".$corpno3;
	else $corpno = "";

	$tel = $_REQUEST[tel];
	$mobile = $_REQUEST[mobile];
	$email = $_REQUEST[email];
	$smsok = $_REQUEST[smsok];

	$zip1 = $_REQUEST[zip1];
	$zip2 = $_REQUEST[zip2];
	$address1 = $_REQUEST[address1];
	$address2 = $_REQUEST[address2];
	$admin = $_REQUEST[admin];

	//----------------------------
	// 비번 변경은 별도 function에서 처리
	

	// 정보 변경..
	$sys_connect = sys_db_connect();
	$sql = "update  sys_domain set
			corp_boss	= '$boss',
			corp_no		= '$corpno',
			corp_tel	= '$tel',
			corp_mobile	= '$mobile',
			email		= '$email',
			corp_zip1	= '$zip1',
			corp_zip2	= '$zip2',
			corp_address	= '$address1',
			corp_address2	= '$address2',
			smsok 		= '$smsok',
			corp_admin	= '$admin'
		 where  id 		= '" . _DOMAIN_ . "'";

	debug ($sql);
	mysql_query($sql, $sys_connect) or die(mysql_error($sys_connect));

	echo "<script>document.location.href = '?template=BA00';</script>";
	exit;
	
    }

	//-------------------------------
	// change_passwd
	function change_passwd()
	{
		global $connect;

		foreach ($_REQUEST as $key=>$value) $$key = trim($value);

		$sql = "select id from userinfo where id = '$_SESSION[LOGIN_ID]' and passwd = password('$old_passwd')";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if ($list) {
			$upd_sql = "update userinfo set passwd = password('$new_passwd'), last_modified = now() where id = '$_SESSION[LOGIN_ID]'";
			mysql_query($upd_sql, $connect) or die(mysql_error());
			
			$sys_connect = sys_db_connect();
            $pass = substr($new_passwd,0,3) . "********";
            $ins_sql = "insert into sys_passwd_history (
                              crdate
                            , worker
                            , domain
                            , userid
                            , pass
                            , howto
                        ) values (
                              now()
                            , '$_SESSION[LOGIN_ID]'
                            , '$_SESSION[LOGIN_DOMAIN]'
                            , '$_SESSION[LOGIN_ID]'
                            , '$pass'
                            , 'Web'
                        )";
            mysql_query($ins_sql, $sys_connect) or die(mysql_error());

			echo 1;
			
		} else {
			echo 0;
		}
	}
}

?>

