<?
class class_ordercancel extends class_top
{
	
    function ordercancel( $shop_id , $order_id , $order_id_seq , $cancel_qty , $shop_product_id )
    {
        global $connect;

debug("!!!!!!!!!!!!!!!!!");


$shop_id		 = "10002";
$order_id		 = "1718286682";
$order_id_seq	 = "1744532749";
$cancel_qty		 = "1";
$shop_product_id = "153279336";


		$query = "SELECT a.seq				a_seq
					   , a.pack				a_pack
				  FROM  orders a
				       ,order_products b
				 WHERE  b.order_seq = a.seq
				   AND b.shop_id = '$shop_id'
				   AND a.order_id = '$order_id'
				   AND a.order_id_seq = '$order_id_seq'
				   AND b.shop_product_id = '$shop_product_id'
				   AND match_type NOT IN (5) 
				   AND is_gift = 0
				  ";
debug($query);

		$result = mysql_query($query, $connect);
		// 취소상품이 포함된 주문을 검사 한다.
		while($data = mysql_fetch_assoc($result))
		{
debug_array($data);			
			// 해당 주문별로 취소 수량을 비교하여 취소한다.
			$_query = "SELECT	 a.seq				a_seq
							   , a.order_id			a_order_id
							   , a.order_id_seq		a_order_id_seq
							   , a.pack				a_pack
							   , a.qty				a_qty
							   , b.seq				b_seq
							   , b.shop_product_id	b_shop_product_id
							   , b.shop_id			b_shop_id
							   , b.qty				b_qty
							   , b.is_gift			b_is_gift
						  FROM  orders a
						       ,order_products b
						 WHERE  b.order_seq = a.seq";
				 
			if($data[a_pack] > 0 )
				$_query .= " AND a.pack = $data[a_pack]";
			else 
				$_query .= " AND a.seq = $data[a_seq]";

debug($_query);
			//현 주문의 갯수?
			$order_cnt = mysql_fetch_row($query, $connect);
			$_result = mysql_query($_query, $connect);
			$is_gift_list = array();
			//orders 의 qty 랑 차이의 비율만큼 order_prd 의 qty를 취소시킴.
			while($_data = mysql_fetch_assoc($_result))
			{
				
debug_array($_data);
				
			}
				
		}
    }
}
?>
