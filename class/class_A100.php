<?
require_once "class_top.php";
require_once "class_A.php";

////////////////////////////////
// class name: class_A100
//
class class_A100 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function A100()
    {
	global $connect;
	global $template, $line_per_page;

	$sql  = "select * from userinfo where id = '$_SESSION[LOGIN_ID]'";
	$result = mysql_query($sql, $connect);
	$list = mysql_fetch_array($result);

        $master_code = substr( $template, 0,1);

	if ($_SESSION[_V2_])
	{
	    $V2_FILENAME = "template/${master_code}/${template}.v2.htm";
	    if (file_exists($V2_FILENAME))
	    {
		include $V2_FILENAME;
		exit;
	    }
	}

        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function modify()
    {
	global $connect;

	$mycode = $_REQUEST[mycode];
	$passwd = $_REQUEST[passwd];

	$corpname = $_REQUEST[corpname];
	$boss = $_REQUEST[boss];
	$corpno1 = $_REQUEST[corpno1];
	$corpno2 = $_REQUEST[corpno2];
	$corpno3 = $_REQUEST[corpno3];
	if ($corpno1) $corpno = $corpno1."-".$corpno2."-".$corpno3;
	else $corpno = "";

	$tel = $_REQUEST[tel];

	$mobile1 = $_REQUEST[mobile1];
	$mobile2 = $_REQUEST[mobile2];
	$mobile3 = $_REQUEST[mobile3];
	if ($mobile1 && $mobile2 && $mobile3)
	  $mobile = $mobile1."-".$mobile2."-".$mobile3;

	$email = $_REQUEST[email];
	$smsok = $_REQUEST[smsok];

	$zip1 = $_REQUEST[zip1];
	$zip2 = $_REQUEST[zip2];
	$address1 = $_REQUEST[address1];
	$address2 = $_REQUEST[address2];
	$admin = $_REQUEST[admin];

	$sql = "update userinfo set
			name = '$corpname',
			passwd = '$passwd',
			boss = '$boss',
			corpno = '$corpno',
			tel = '$tel',
			mobile = '$mobile',
			email = '$email',
			zip1 = '$zip1',
			zip2 = '$zip2',
			address1 = '$address1',
			address2 = '$address2',
			smsok = '$smsok',
			admin = '$admin'
		where code = '$mycode'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=A100';</script>";
	exit;
	
    }

}

?>
