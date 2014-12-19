<?
require_once "class_top.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_DI00
//

class class_DI00 extends class_top {

    ///////////////////////////////////////////
    // shop들의 list출력

    function DI00()
    {
	global $connect;
	global $template;

        $master_code = substr($template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function update()
    {
	global $connect;
	global $template;

	// 일자의 형태가 2008-1-12 인 경우가 발생하여 2008-01-12로 수정함.
	$work_date = sprintf("%04d-%02d-%02d", $_REQUEST[s_year], $_REQUEST[s_mon], $_REQUEST[s_day]);
	$seq = $_REQUEST[seq];
	$status = $_REQUEST[status];

	if ($status == 3)
	{
	    $this->balju_add();
	    echo "<script>alert('처리되었습니다.');</script>";
            $master_code = substr($template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
	    exit;
	}

	$sql = "select * from balju_history where crdate = '$work_date' and seq = '$seq'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	if ($list)
	{
	    if ($status == 1 && $list[status] >= 1) # START
	    {
		echo "<script>alert('이미 발주가 진행중/진행완료입니다.');</script>";
	    }
	    else if ($status == 2 && $list[status] == 2) # END
	    {
		echo "<script>alert('이미 발주완료 상태입니다.');</script>";
	    }
	    else if ($status == 2 && $list[status] == 1)
	    {
		$time_sql = " end_time = now(), ";
		
	        $sql = "update balju_history set 
			end_time = now(),
			status = '$status',
			userid = '$_SESSION[LOGIN_ID]'
		     where crdate = '$work_date'
		       and seq = '$seq'";
	  	mysql_query($sql, $connect) or die(mysql_error());

		$this->send_sms();
	    }
	}
	else
	{
	    if ($status == 1) # END
	    {
	        $sql = "insert into balju_history set
			crdate = '$work_date',
			seq = '$seq',
			start_time = now(),
			status = '$status',
			userid = '$_SESSION[LOGIN_ID]'";
	  	mysql_query($sql, $connect) or die(mysql_error());
	    }
	}

        $master_code = substr($template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function undo()
    {
	global $connect;
	global $template;

	$work_date = $_REQUEST[work_date];
	$seq = $_REQUEST[seq];

	$sql = "select * from balju_history where crdate = '$work_date' and seq = '$seq'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	if ($list)
	{
	    $sql = "delete from  balju_history
		     where crdate = '$work_date'
		       and seq = '$seq'";
	    mysql_query($sql, $connect) or die(mysql_error());
	}

        $master_code = substr($template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // 추가발주 처리
    function balju_add()
    {
	include "lib/sms_lib.php";

	global $connect;
	$today = date("Y-m-d");

	$sql = "select name, tel, mobile from userinfo where id = '"._DOMAIN_."'"; 
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$sender = $list[tel];
	$vendor = $list[name];

	$msg = "[이지어드민]
추가발주자료가 등록되었습니다. 주문을 확인하세요.
($vendor)";

	$sql = "select balju_seq from orders
		 where collect_date = '$today'
		   and balju_seq > 0
		 order by balju_seq desc limit 1";
	$list0 = mysql_fetch_array(mysql_query($sql, $connect));
	if ($list0)
	{

	    $sql = "select distinct supply_id from orders
		     where collect_date = '$today'
		       and balju_seq = '$list0[balju_seq]'";
	    $result = mysql_query($sql, $connect) or die(mysql_error());
	    while ($list = mysql_fetch_array($result))
	    {
		$sql = "select mobile from userinfo where code = '$list[supply_id]' and level = 0 and smsok = 1 and mobile != '' and substring(mobile,1,2) = '01'";
		$list2 = mysql_fetch_array(mysql_query($sql, $connect));
		if ($list2)
		{
		    $receiver = $list2[mobile];
	            sms_send($receiver, $sender, $msg);   
		}
	    }
	}
    }

    function send_sms()
    {
	global $connect;
	$today = date("Y-m-d");

	$sql = "select name, tel, mobile from userinfo where id = '"._DOMAIN_."'"; 
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$sender = $list[tel];
	$vendor = $list[name];
	
	include_once "lib/sms_lib.php";

	$msg = "[이지어드민]
발주처리가 완료되었습니다.  주문다운로드가 가능합니다.
($vendor)";

	///////////////////////////////////////
	$sql = "select distinct supply_id from orders where collect_date = '$today' and balju_seq = 0";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	  $sql = "select mobile from userinfo where level = 0 and smsok = 1 and mobile != '' and substring(mobile,1,2) = '01' and code = '$list[supply_id]'";
	  $result2 = mysql_query($sql, $connect) or die(mysql_error());
	  while ($list2 = mysql_fetch_array($result2))
	  {
	    $receiver = $list2[mobile];
	    debug("send_sms : 업체코드 : $list[supply_id] ($receiver) ($msg)");
	    sms_send($receiver, $sender, $msg);   
	  }
	}

    }
}



?>
