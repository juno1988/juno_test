<?
/*-------------------------------------------


	class_K600.php
	desc : 3PL 입고요청

--------------------------------------------*/
require_once "class_top.php";
require_once "class_3pl.php";

class class_K600 extends class_top {
    var $m_3pl = "";
    var $m_connect = "";

    /////////////////////
    function class_K600()
    {
	$this->m_3pl     = new class_3pl();
	$this->m_connect = $this->m_3pl->m_connect;
    }

    function K600()
    {
	global $template;

	$sql = "select * from 3pl_sheet_in
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
			  and status >= '3'
			  and status <= '5'
		 order by seq desc";
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function K601()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function K603()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    //////////////////////////////////////
    function get_name_sheet_sts($status)
    {
        switch ($status)
        {
            case 0:
                $ret = "전표준비중(0)";
                break; 
            case 1:
                $ret = "업체예정대기(1)";
                break; 
            case 2:
                $ret = "3PL예정대기(2)";
                break; 
            case 3:
                $ret = "업체확정대기(3)";
                break; 
            case 4:
                $ret = "3PL확정대기(4)";
                break;
            case 5:
                $ret = "입고확정완료(5)";
                break;
            case 6:
                $ret = "재고입고(6)";
                break;
            default :
                $ret = $status;
                break;
        }

        $ret = "<img src=/images/icon_step0${status}.gif align=absmiddle>";

        return $ret;
    }

    /////////////////////////////////////////
    function get_sheet_status($sheet)
    {
	/////////////////////////////////////
	$sql = "select status from  3pl_sheet_in
		 where seq    = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
	";
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$list = mysql_fetch_array($result);

	return $list[status];
    }

    function get_name($product_id, &$retname, &$retoption)
    {
	$sql = "select product_name, options from 3pl_products
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
		   and product_id = '$product_id'";
	$list = mysql_fetch_array(mysql_query($sql, $this->m_connect));
	$retname = $list[product_name];
	$retoption = $list[options];

	if (!$retname) $retname = "*** 3PL 미등록상품 ***";

	return;
    }

    function ok_all()
    {
	$sheet = $_REQUEST[sheet];

	//////////////////////////////////////
	$sql = "update 3pl_stock_wait set
		       status = 2
		 where sheet = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 1
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

	/////////////////////////////////////
	$sql = "update 3pl_sheet_in set
		       status = 4
		 where seq    = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 3
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

        echo "<script>document.location.href = 'template.htm?template=K403&sheet=${sheet}';</script>";
	exit;
    }
}
?>
