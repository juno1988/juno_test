<?
/* ------------------------------------------------------------

	Name : class_stat.php

	Desc : ����/��Ī ���
	Created by sy.hwang 2007.10.18

------------------------------------------------------------ */

class class_stat
{

    //////////////////////////////////////////
    // �Ǹ�ó�� �ֹ���� for G300
    function get_summary_by_shopid ($collect_date, $shop_list = "")
    {
	global $connect, $date_type, $start_date, $end_date;

        $date_type = $date_type ? $date_type : "collect_date";
	$sql = "select  distinct shop_id, 
			sum(amount) 		amount, 
			sum(shop_price * qty)   amount2,
			sum(code1) 		amount3,
			sum(qty) qty
		  from  orders
		 where  ";


	// jk ����
	if ( $start_date && $end_date )
	    $sql .= " $date_type >= '$start_date 00:00:00' and $date_type <= '$end_date 23:59:59'"; 
	else
	    $sql .= " $date_type = '$collect_date'";

	$sql .= " and c_seq = 0
		   and  order_cs not in (1,2,3,4,12)";

	if ( $shop_list )
	    $sql .= " and shop_id in ( $shop_list )";

	$sql .= "group  by shop_id
		 order  by shop_id";

	$result = mysql_query($sql, $connect) or die(mysql_error());

	return $result;
    }

    /////////////////////////////////////////
    // �ֹ� ������
    function get_order_by_date ($start_date, $end_date, $date_type="collect_date")
    {
	global $connect;

	$sql = "select	shop_id,
			sum(amount) 		amount, 
			sum(shop_price * qty)   amount2,
			sum(code1) 		amount3,
			sum(qty) qty
		  from  orders
		 where  ${date_type} >= '$start_date 00:00:00'
		   and  ${date_type} <= '$end_date 23:59:59'
		   and  order_cs not in (6,8,9,10)
		 group  by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());

	return $result;
    }

    //////////////////////////////////////////////
    // �Ǹ�ó ���
    function get_cancel_by_date ($start_date, $end_date)
    {
	global $connect, $date_type; // date_type�߰�

	// ������ refund date���� -> ��Ұ� �ϳ��� ����..���� 2009.2.2 - jk
	$sql = "select	shop_id,
			sum(amount) 		amount, 
			sum(shop_price * qty)   amount2,
			sum(code1) 		amount3,
			sum(qty) qty
		  from  orders
           	 where  date_format($date_type, '%Y-%m-%d') >= '$start_date'
             	   and  date_format($date_type, '%Y-%m-%d') <= '$end_date'
             	   and  order_cs in (1,2,3,4,12)
		 group  by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());

	return $result;
    }

    //////////////////////////////////////////////
    // �Ǹ�ó ��ȯ
    function get_change_by_date ($start_date, $end_date)
    {
	global $connect, $date_type;

        $date_type = $date_type ? $date_type : "collect_date";

	$sql = "select	shop_id,
			sum(amount) 		amount, 
			sum(shop_price * qty)   amount2,
			sum(code1) 		amount3,
			sum(qty) qty
		  from  orders
           	 where  $date_type >= '$start_date 00:00:00'
             	   and  $date_type <= '$end_date 23:59:59'
             	   and  order_cs in (11)
		 group  by shop_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());

	return $result;
    }

    //////////////////////////////////////////////////
    // ��ǰ�� �ֹ���� (�Ǹ�ó�ڵ�)
    function get_order_by_shopid ($shop_id, $start_date, $end_date)
    {
	global $connect, $date_type;
        $date_type = $date_type ? $date_type : "collect_date";

  	$sql = "select  distinct product_id, 
			product_name, 
			avg(shop_price) shop_price, 
			sum(qty) qty, 
			sum(amount) 		amount, 
			sum(shop_price * qty)   amount2,
			sum(code1) 		amount3,
			match_options,
			seq
            	  from  orders
           	 where  shop_id = '$shop_id'
             	   and  $date_type >= '$start_date 00:00:00'
             	   and  $date_type <= '$end_date 23:59:59'
             	   and  order_cs not in (6,8,9,10)
           	 group  by product_id
           	 order  by amount desc";

	$result = mysql_query($sql, $connect) or die(mysql_error());

	return $result;
    }
    
}
?>
