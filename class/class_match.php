<?
/* ------------------------------------------------------------

	Name : class_match.php

	Desc : ����/��Ī ���
	Created by sy.hwang 2007.10.18

------------------------------------------------------------ */

class class_match
{

    ///////////////////////////////////////////
    // ��Ī ������ �Ǹ�ó product_id�� �����´�
    // 2008.8.16 - jk
    function get_shop_productid( $shop_id, $product_id )
    {

    }

    //////////////////////////////////////////
    // ���� ��ǰ �ֹ�ó��
    function bind_order($product_id="")
    {
	global $connect;

	if ($product_id) $condition = " and self_no = '$product_id'";
	else $condition = "";

	/////////////////////////////////////////////
	$sql = "select * from order_temp where self_no != '' and bit not in (2,3) ${condition}";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	    if (substr($list[self_no],0,1) == '_') $waiting = true;
	    else $waiting = false;

	    $self_no = str_replace("_", "", $list[self_no]);

	    // ���� ��ǰ���� ���θ� �Ǵ��Ͽ� ������ǰ�ΰ�츸 ó��
	    $is_binding = $this->is_binding($self_no);
	    if ($is_binding)
	    {
		$org_no = $self_no;
		$arrs = $this->get_binding_array($self_no);
		$i = 0;
		foreach ($arrs as $id)
		{
		    $list[self_no] = ($waiting == true) ? "_".$id : $id;

		    if ($i == 0) // ù��°�� ������Ʈ
		    {
			$sql  = "update order_temp  set 
                                	self_no     = '$list[self_no]', 
                                	code9       = '$org_no',
                                	bit         = '2'
                          	  where order_no    = '$list[order_no]' 
                            	    and order_subno = '$list[order_subno]'";

			mysql_query($sql, $connect) or die(mysql_error());
			debug($sql);
		    }
		    else
		    {
			$list[bit] = 3;
			$list[qty] = 0;
			$list[code9] = $org_no;
			$this->copy_order($list);
		    }
		    $i++;
	    	}
	    }
	}
    }

    //////////////////////////////////////////
    // ���� ��ǰ �ֹ�����
    function is_binding($product_id)
    {
	global $connect;

	$sql = "select packed from products where product_id = '$product_id'";
 	$list = mysql_fetch_array(mysql_query($sql, $connect));

	if ($list[packed]) return true;
	else return false;
    }

    //////////////////////////////////////////
    // bind�� ��ǰ ��� ���ϱ�
    function get_binding_array($product_id)
    {
	global $connect;

	$sql = "select pack_list from products where product_id = '$product_id'";
 	$list = mysql_fetch_array(mysql_query($sql, $connect));

	$arrs = explode(",", $list[pack_list]);
	for ($i=0, $j=0; $i < count($arrs); $i++)
	{
	    if (trim($arrs[$i]) != "")
	    {
		$array[$j] = $arrs[$i];
		$j++;
	    }
	}
	return $array;
    }


    ///////////////////////////////////
    function copy_order($list, $more="")
    {
	global $connect;
	$sql = "insert into order_temp set 
		order_no 	= '$list[order_no]',
		shop_id 	= '$list[shop_id]',
		self_no 	= '$list[self_no]',
		pay_date 	= '$list[pay_date]',
		order_date 	= '$list[order_date]',
		order_time 	= '$list[order_time]',
		collect_date 	= '$list[collect_date]',
		limit_date 	= '$list[limit_date]',
		product_no 	= '$list[product_no]',
		product_name 	= '$list[product_name]',
		options 	= '$list[options]',
		qty 		= '$list[qty]',
		price 		= '$list[price]',
		su_price 	= '$list[su_price]',
		amount 		= '$list[amount]',
		order_name 	= '$list[order_name]',
		order_tel 	= '$list[order_tel]',
		order_mobile 	= '$list[order_mobile]',
		order_email 	= '$list[order_email]',
		recv_name 	= '$list[recv_name]',
		recv_tel 	= '$list[recv_tel]',
		recv_mobile 	= '$list[recv_mobile]',
		zip 		= '$list[zip]',
		address 	= '$list[address]',
		memo 		= '$list[memo]',
		message 	= '$list[message]',
		trans_fee 	= '$list[trans_fee]',
		trans_who 	= '$list[trans_who]',
		sale_type 	= '$list[sale_type]',
		pay_type 	= '$list[pay_type]',
		bill 		= '$list[bill]',
		fee 		= '$list[fee]',
		fee_rate 	= '$list[fee_rate]',
		category 	= '$list[category]',
		coupon_amt 	= '$list[coupon_amt]',
		mileage 	= '$list[mileage]',
		gift 		= '$list[gift]',
		howtopay 	= '$list[howtopay]',
		order_seq 	= '$list[order_seq]',
		code1 		= '$list[code1]',
		code2 		= '$list[code2]',
		code3 		= '$list[code3]',
		code4 		= '$list[code4]',
		code5 		= '$list[code5]',
		code6 		= '$list[code6]',
		code7 		= '$list[code7]',
		code8 		= '$list[code8]',
		code9 		= '$list[code9]',
		code10 		= '$list[code10]',
		${more}
		bit 		= '$list[bit]'
		";
	mysql_query($sql, $connect) or die(mysql_error());
    }

    function insert_code_match($id, $shop_id, $shop_code, $option, $undo_time)
    {
	global $connect;

        $sql  = "insert into code_match (
			id, 
			shop_id, 
			shop_code, 
			shop_option, 
			input_date, 
			input_time) 
		 values (
			'$id', 
			'$shop_id', 
			'$shop_code', 
			'$option', 
			'$undo_time', 
			'$undo_time'
		 )";

        debug($sql);
        @mysql_query($sql, $connect);
    }

    function insert_bind_match($id, $shop_id, $shop_code, $option, $org_id, $undo_time)
    {
	global $connect;

        $sql  = "insert into bind_match (
			id, 
			shop_id, 
			shop_code, 
			shop_option, 
			org_id,
			input_date, 
			input_time) 
		 values (
			'$id', 
			'$shop_id', 
			'$shop_code', 
			'$option', 
			'$org_id',
			'$undo_time', 
			'$undo_time'
		 )";

        debug($sql);
        @mysql_query($sql, $connect);
    } 

    function delete_order($no, $subno)
    {
	global $connect;

	$sql = "delete from order_temp
		 where order_no = '$no'
		   and order_subno = '$subno'";
	mysql_query($sql, $connect) or die(mysql_error());

	return;
    }

    ////////////////////////////////////////////////////////////
    //  �� �Լ��� �ɼǺ� ���ֿ����� ��밡���ϵ��� �Ǿ� ����. 
    function auto_matching()
    {
	global $connect;

    	$pattern1 = "[\[][0-9]{5}\]";	// [00010]
    	$dummy = array("[", "]");


	/////////////////////////////////////////////
	// rayine test : 10002, 10102
  	$sql = "select * 
            	  from order_temp 
           	 where (self_no = '')
		   and shop_id in (10002, 10102)
             	   and bit not in (2,3)";

	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	    // ��ǰ�� �ֹ���ȣ������ ã�Ƽ� �����ϸ� �� �ڵ�� ��Ī�Ѵ�. 
	    // �ɼǺ� �����̹Ƿ� �ӽ� ��Īó���Ѵ�.
	    $self_no = "";

	    // 1. �ɼǸ� ������ [S00011] ã��
	    $product_id = class_match::pattern_id6($list[options]);
	    if ($product_id) 
	    {
		$self_no = ${product_id};
		$bit = 6;
	    }
	    else
	    {
	 	// 2. �ɼǸ� ������ [00011] ã��
		$product_id = class_match::pattern_id5($list[options]);
		if ($product_id)
		{
		    $self_no = "__".${product_id};
		    $bit = 7;
		}
		else
		{
		    // 3. ��ǰ�� ������ [00011] ã��
		    $product_id = class_match::pattern_id5($list[product_name]);
		    if ($product_id)
		    {
			$self_no = "__".${product_id};
			$bit = 8;
		    }
		}
	    }

	    if ($self_no)
	    {
		$upd_sql = "update order_temp set
				   self_no = '$self_no',
				   bit     = '$bit'
			     where order_no    = '$list[order_no]' 
			       and order_subno = '$list[order_subno]'";

		mysql_query($upd_sql, $connect) or die(mysql_error());
		debug($upd_sql);
	    }
	}
    }

    // ��ǰ��/�ɼǸ� ���� [00010] ���� �ľ�
    function pattern_id5($string)
    {
    	$pattern = "[\[][0-9]{5}\]";
	return class_match::find_pattern($pattern, $string);
    }

    // �ɼǸ� ���� [S00010] ���� �ľ� : [S00001] ã�� ������ S00001 ã�´�.
    function pattern_id6($string)
    {
    	$pattern = "[\[](S)[0-9]{5}\]";	
	$str = class_match::find_pattern($pattern, $string);

	if (!$str)
	{
    	    $pattern = "(S)[0-9]{5}";	
	    $str = class_match::find_pattern($pattern, $string);
	}

	return $str;
    }

    // ���ڿ� ���� �ľ�
    function find_pattern($pattern, $string)
    {
    	$dummy = array("[", "]");
	$id = "";

	if (ereg($pattern, $string, $matches))
	    $id = str_replace($dummy, "", $matches[0]);

	return $id;
    }

}
?>
