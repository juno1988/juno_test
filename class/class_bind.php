<?
/* ------------------------------------------------------------

	Name : class_bind.php

	Desc : 묶음 상품매칭
	Created by sy.hwang 2007.10.18

------------------------------------------------------------ */

class class_bind
{

    //////////////////////////////////////////
    // 묶음 상품 주문처리
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

	    // 묶음 상품인지 여부를 판단하여 묶음상품인경우만 처리
	    $is_binding = $this->is_binding($self_no);
	    if ($is_binding)
	    {
		$org_no = $self_no;
		$arrs = $this->get_binding_array($self_no);
		$i = 0;
		foreach ($arrs as $id)
		{
		    $list[self_no] = ($waiting == true) ? "_".$id : $id;

		    if ($i == 0) // 첫번째는 업데이트
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
			$list[price] = 0;
			$list[amount] = 0;
			$list[code9] = $org_no;
			$this->copy_order($list);
		    }
		    $i++;
	    	}
	    }
	}
    }


    //////////////////////////////////////////
    // 묶음 상품 매칭 처리 (2차매칭)
    function match_bind_order()
    {
	global $connect;

	/////////////////////////////////////////////
	$sql = "select * from order_temp where self_no != '' and substring(self_no,1,1) != '_' and bit in (2,3) and length(self_no) = 5";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	    $sql = "select id from bind_match 
		     where shop_id 	= '$list[shop_id]'
		       and shop_code 	= '$list[shop_code]'
		       and shop_option 	= '$list[shop_option]'
		       and org_id 	= '$list[self_no]'";
	    $list2 = mysql_fetch_array(mysql_query($sql, $connect));
	    // 2차 매칭테이블에 해당 정보가 있으면 코드로 업데이트
	    if ($list2)
	    {
		$upd_sql = "update order_temp set
				   self_no = '$list2[id]',
				   bit = 4,
				   org_id = '$list[self_no]'
			     where order_no = '$list[order_no]'
			       and order_subno = '$list[order_subno]'
		";

		mysql_query($upd_sql, $connect) or die(mysql_error());
	    }
	    // 매칭정보가 없으면 self_no앞에 '_'를 붙여준다.
	    else
	    {
		$self_no = "_" . $list[self_no];
		$upd_sql = "update order_temp set
				   self_no = '$self_no'
			     where order_no = '$list[order_no]'
			       and order_subno = '$list[order_subno]'
		";
		mysql_query($upd_sql, $connect) or die(mysql_error());
	    }
	}
    }


    //////////////////////////////////////////
    // 묶음 상품 주문여부
    function is_binding($product_id)
    {
	global $connect;

	$sql = "select packed from products where product_id = '$product_id'";
 	$list = mysql_fetch_array(mysql_query($sql, $connect));

	if ($list[packed]) return true;
	else return false;
    }

    //////////////////////////////////////////
    // bind된 상품 목록 구하기
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


    function copy_order($list)
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
		bit 		= '$list[bit]'
		";

	mysql_query($sql, $connect) or die(mysql_error());
	debug($sql);
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
}
?>
