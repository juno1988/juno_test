<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_bind.php";

////////////////////////////////
// class name: class_D100
//

class class_D100 extends class_top {

    function change_qty()
    {
	echo "change_qty";
    }

    ///////////////////////////////////////////
    // shop들의 list출력
    function D100()
    {
	global $connect;
	global $template;

	$this->begin( "주문배송관리");
	$sys_connect = sys_db_connect();
	$curr_page = $_GET[page];

	$sql = "select count(*) cnt from shopinfo";
	$total = mysql_fetch_array(mysql_query($sql, $connect));
	$total_rows = $total[cnt];

	$line_per_page = 10;
	$link_url = "?template=D100";


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function D106()
    {
	global $connect;
	global $template, $order_no, $order_subno;

	$sql = "select * from order_temp where order_no='$order_no' and order_subno='$order_subno'";

	$data = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function D107()
    {
	global $connect;
	global $template;
	global $order_no, $order_subno;

	$sql = "select * 
		  from order_temp 
		 where order_no    ='$order_no' 
		   and order_subno ='$order_subno'";

	$data = mysql_fetch_array(mysql_query($sql, $connect));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    /*
	2008.12.22 sy.hwang for only qmart
    */
    function copy_order() 
    {
	global $connect, $template;
	global $order_no, $order_subno, $product_name, $options, $memo, $qty;

	$sql = "select * from order_temp
		 where order_no    ='$order_no' 
		   and order_subno ='$order_subno'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));

	// change data to input variables... 
	$list[product_name] = $product_name;
	$list[options] 	    = $options;
	$list[memo] 	    = $memo;
	$list[qty] 	    = $qty;
	$list[price] 	    = 0;
	$list[amount] 	    = 0;
	$list[su_price]     = 0; // 2009.3.17 - jk추가
	$more = "is_copy = 1,";	// 복사본 주문 표시

	include_once "class/class_match.php";
debug( "begin copy");
	class_match::copy_order($list, $more);
debug( "end copy");

        echo "<script>opener.location.reload();</script>";
        echo "<script>self.close();</script>";
        exit;
    }

    function del_copy_order() 
    {
	global $connect, $template;
	global $order_no, $order_subno;

	include_once "class/class_match.php";
	class_match::delete_order($order_no, $order_subno);

        $this->redirect("template.htm?template=D101");
    }

    function option_update()
    {
	global $connect;
	global $template, $order_no, $order_subno, $options, $memo;

	$sql = "update order_temp set options='" . addslashes($options) ."',memo='" . addslashes($memo) . "' where order_no='$order_no' and order_subno='$order_subno'";
	mysql_query($sql, $connect);

        $this->redirect("popup.htm?template=D106&order_no=$order_no&order_subno=$order_subno");
        exit;
    }

    ///////////////////////////////////////////
    // D100 -> upload excel
    function upload()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . "_upload.php";

	exit;
    }

    ///////////////////////////////////////////
    // D100 -> match order_temp
    function match()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . "_match.php";

	exit;
    }

    ///////////////////////////////////////////
    // D101 -> move order_temp to orders
    function move()
    {
	global $connect;
	global $template;

	// 최종 작업
	require_once "class_worktx.php";
	$obj = new class_worktx();
	$obj->clean_tx();
	
	$sys_connect = sys_db_connect();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . "_move.php";

	exit;
    }

    // D101 -> undo
    function undo()
    {
	global $connect;
	global $template;

	///////////////////////////////
	$sql = "select count(*) cnt from order_temp where match_time is not null and match_time !='' and match_id = '$_SESSION[LOGIN_ID]'";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$list = mysql_fetch_array($result);
	$rows = $list[cnt];
	if ($rows == 0)
	{
	    echo "<script>alert('실행취소할 자료가 없습니다.');</script>";
	    $this->redirect("template.htm?template=D101");
	    exit;
	}

	///////////////////////////////
	$sql = "select max(match_time) match_time from order_temp where match_time is not null and match_time != '' and match_id = '$_SESSION[LOGIN_ID]'";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$list = mysql_fetch_array($result);
	$match_time = $list[match_time];

	if ($match_time)
	{
	    $upd_sql = "update order_temp set self_no = '', match_time = '', match_id = '' where match_time = '$match_time' and match_id = '$_SESSION[LOGIN_ID]'";
	    @mysql_query($upd_sql, $connect);

	    $treat_date = trim(substr($match_time, 0, 10));
	    $treat_time = trim(substr($match_time, 10));
	    $del_sql = "delete from code_match where input_date = '$treat_date' and input_time = '$treat_time'";
	    @mysql_query($del_sql, $connect);

	    $del_sql = "delete from name_match where input_date = '$treat_date' and input_time = '$treat_time'";
	    @mysql_query($del_sql, $connect);
	}

	///////////////////////////////

	$this->redirect("template.htm?template=D101");
	exit;
    }

    ///////////////////////////////////////////
    // D101
    function D101()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
	if ($_SESSION[STOCK_MANAGE_USE])
	{
            include "template/" . $master_code ."/" . $template . "_opt.htm";
	}
	else
	{
            include "template/" . $master_code ."/" . $template . ".htm";
	}
    }

    ///////////////////////////////////////////
    // D102
    function D102()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    // D103
    function D103()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


}

?>
