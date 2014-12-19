<?
	//배송후 전체취소
	function trans_cancel($seq_str)	
	{
		/*
		$cancel_query = "select a.seq a_seq, 
							     a.order_id,
							     a.order_id_seq
							from orders a, 
							     order_products b 
							where a.seq = b.order_seq and 
							     b.order_seq in ($seq_str)";
							     
debug("배송후 취소 cancel_select_query : $cancel_query");

				$cancel_result = mysql_query($cancel_query, $connect);				
				while( $cancel_data = mysql_fetch_assoc($cancel_result) )
				{
					$cancel_data_query = "SELECT * FROM shop_stat_upload WHERE order_id = '$cancel_data[order_id]'";
					$cancel_data_result = mysql_query($cancel_data_query, $connect);
					$cancel_data_data = mysql_fetch_assoc($cancel_data_result);
debug("배송후 취소 cancel_data_query : $cancel_data_query");					
					if($cancel_data_data[stat_check]== "N")
					{
						$cancel_data_query = "DELETE FROM shop_stat_upload WHERE order_id = '$cancel_data[order_id]'";
						$cancel_data_result = mysql_query($cancel_data_query, $connect);
debug("배송후 취소 cancel_data_query : $cancel_data_query");					
					}
		}
		*/
	}
	
	//배송후 개별취소
	function trans_checked_cancel($checked_seq_str)
	{
		/*
		$cancel_query = "select a.seq a_seq, 
				     a.order_id,
				     a.order_id_seq
				from orders a, 
				     order_products b 
				where a.seq = b.order_seq and 
				     b.seq in ($checked_seq_str)";

		$cancel_result = mysql_query($cancel_query, $connect);
		
		while( $cancel_data = mysql_fetch_assoc($cancel_result) )
		{
			$cancel_data_query = "SELECT * FROM shop_stat_upload WHERE order_id = '$cancel_data[order_id]'";
			$cancel_data_result = mysql_query($cancel_data_query, $connect);
			$cancel_data_data = mysql_fetch_assoc($cancel_data_result);
			
			if($cancel_data_data[stat_check]=='N')
			{
				$cancel_data_query = "DELETE FROM shop_stat_upload WHERE order_id = '$cancel_data[order_id]'";
				$cancel_data_result = mysql_query($cancel_data_query, $connect);
	
			}					
		}
		*/
	}
	
	
	//배송
	function shop_stat_upload_E900_insert($data_shop_id, $data_seq)
	{
		$this->myfunc2();
		
//		echo "<script>alert('aaaa');</script>";
debug("shop_stat_upload_E900_insert 진입!");
/*
        switch($data_orders[shop_id])
        {
        	case '10001':
        	$stat_info_query = "INSERT IGNORE shop_stat_upload
    							(shop_id, order_id, order_id_seq, order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, service_price, discount_price)
						 SELECT  shop_id, order_id, '', order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount+code31, (code15*11), code16+code13
						 from orders where seq = $data_orders[seq]";
        	break;	
        	case '10002':
        	$stat_info_query = "INSERT IGNORE shop_stat_upload
    							(shop_id, order_id, order_id_seq, order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, service_price, discount_price)
						 SELECT  shop_id, order_id, '', order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount+code31, (code15), code16+code13
						 from orders where seq = $data_orders[seq]";
        	break;	
        	case '10050':
        	$stat_info_query = "INSERT IGNORE shop_stat_upload
    							(shop_id, order_id, order_id_seq, order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, service_price, discount_price)
						 SELECT  shop_id, order_id, order_id_seq, order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, replace(left(code6,5),',',''),code15
						 from orders where seq = $data_orders[seq]";
        	break;	
        			        case '10058':
        	$stat_info_query = "INSERT IGNORE shop_stat_upload
    							(shop_id, order_id, order_id_seq, order_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, pay_type, discount_price)
						 SELECT  shop_id, order_id,           '', order_name, sum(qty), trans_date_pos, order_date, collect_date, sum(supply_price), sum(amount), pay_type,sum((code15)-(code16)-(code17 * qty)-(code18))
						 from orders where order_id = $data_orders[order_id] and (pay_type !='무통장' or pay_type != '전액할인') group by order_id";
        	break;
        	
debug( "insert_shop_stat_upload $stat_info_query" );
        }
        

        
        
    	$stat_info_result = mysql_query ($stat_info_query, $connect );
        $stat_info_data = mysql_fetch_assoc($stat_info_result);
 */
	}
?>
