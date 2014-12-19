<?
//==================================
// date: 2006.7.123
// jk.ryu
// 주문 관련 정보 처리
class class_order{

    function get_order( $seq )
    {
        global $connect;
        $query = "select * from orders where seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data;
    }
    ///////////////////////////////////////////
    // 부분 취소
    function get_part_cancel( $seq )
    {
        global $connect;
        $query = "select qty from part_cancel where order_seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data[qty];
    }
    
    ////////////////////////////////////////
    // 주문의 조건을 상품 array로 가져옴
    // date: 2009.10.29 - jk
    function get_product_list( $arr_options )
    {
        global $connect, $page;
        $query = "select b.*,a.supply_price,a.amount, a.seq, a.order_id,a.collect_date,a.code11,a.code12,a.code13,a.code14,a.code15,a.code16
                         ,a.code17,a.code18,a.code19,a.status
                    from orders a, order_products b";

        $is_where = 1;
        $query   .= " where a.seq = b.order_seq ";

        if ( $arr_options[date_type] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= "a." . $arr_options[date_type] . " >= '$arr_options[start_date] 00:00:00' and a." . $arr_options[date_type] . " <= '$arr_options[end_date] 23:59:59'";
        }

        if ( $arr_options[shop_id] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " a.shop_id = $arr_options[shop_id] ";
        }
        
        if ( $arr_options[order_cs] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " b.order_cs in ( $arr_options[order_cs] )";
        }
        
        $query .= " order by b.order_seq";
        //echo $query;
        
        $result = mysql_query ( $query, $connect );
        return $result;
    }
    
    ////////////////////////////////////////
    // 주문의 조건을 상품 array로 가져옴
    // date: 2013.03.29 - jkh
    // F303 화면에서 정렬순서 때문에 사은품을 아래로 표시
    function get_product_list2( $arr_options )
    {
        global $connect, $page;
        $query = "select b.*,a.supply_price,a.amount, a.seq, a.order_id,a.collect_date,a.code11,a.code12,a.code13,a.code14,a.code15,a.code16
                         ,a.code17,a.code18,a.code19,a.status,a.c_seq
                    from orders a, order_products b";

        $is_where = 1;
        $query   .= " where a.seq = b.order_seq ";

        if ( $arr_options[date_type] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= "a." . $arr_options[date_type] . " >= '$arr_options[start_date] 00:00:00' and a." . $arr_options[date_type] . " <= '$arr_options[end_date] 23:59:59'";
        }

        if ( $arr_options[shop_id] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " a.shop_id = $arr_options[shop_id] ";
        }
        
        if ( $arr_options[order_cs] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " b.order_cs in ( $arr_options[order_cs] )";
        }
        
        $query .= " order by b.order_seq, b.is_gift";
        //echo $query;
        
        $result = mysql_query ( $query, $connect );
        return $result;
    }
    
    ////////////////////////////////////////
    // 주문의 조건을 array로 가져옴
    // date: 2009.10.28 - jk
    function get_list( $arr_options )
    {
        global $connect, $page;
        $query = "select *
                    from orders ";

        $is_where = 0;

        if ( $arr_options[date_type] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= $arr_options[date_type] . " >= '$arr_options[start_date] 00:00:00' and " . $arr_options[date_type] . " <= '$arr_options[end_date] 23:59:59'";
        }

        if ( $arr_options[shop_id] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " shop_id = $arr_options[shop_id] ";
        }

        if ( $arr_options[not_order_cs] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " order_cs not in ( $arr_options[not_order_cs] )";
        }
        
        if ( $arr_options[order_cs] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " order_cs in ( $arr_options[order_cs] )";
        }

        if ( $arr_options[ex_add_order] )
        {
            $query   .= $is_where ? " and " : " where ";
            $is_where = 1;
            $query   .= " seq<>order_id and c_seq=0 and copy_seq=0 ";
        }

        $result = mysql_query ( $query, $connect );
        return $result;
    }

    // 개수를 가져옴.
    function get_count( $arr_options )
    {
        global $connect, $page;
        $query = "select count(*) cnt 
                    from orders a, products b ";
        $query .= $this->build_options( $arr_options );


        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result);

        return $data[cnt];
    }
    
    function re_pack( $old_pack, $new_pack )
    {
        global $connect;
        $query = "update orders set pack=$new_pack where pack=$old_pack";
        mysql_query ( $query , $connect);
    }

    //////////////////////////////////////
    //초기화
    function init_priority()
    {
        global $connect;
        $query = "update orders set priority=null where status=1 and priority <> 99";
        mysql_query ( $query , $connect) or die( mysql_error() );
        return mysql_affected_rows();
    }

    // priority적용
    function set_priority( $seq, $priority, $warehouse='' )
    {
        global $connect;

        $query = "update orders set priority='$priority', warehouse='$warehouse' where seq='$seq'";
        mysql_query ( $query , $connect) or die( mysql_error() );

        $query = "update orders set priority='$priority', warehouse='$warehouse' where pack='$seq'";
        mysql_query ( $query , $connect) or die( mysql_error() );

        return mysql_affected_rows();
    }

    ////////////////////////////////////////
    // 주문의 조건을 array로 가져옴
    // date: 2007.12.1 - jk
    function get_list_arr( $arr_options )
    {
        global $connect, $shop_id, $product_name, $options, $status, $order_cs, $page;
        $query = "select b.product_id, b.name, b.options, a.collect_date, a.shop_id
                    from orders a, products b ";

        $query .= $this->build_options( $arr_options );
        if ( $page )
            $query .= " limit 0,20";

        $result = mysql_query ( $query, $connect );

        $arr_result = array ();
        while ( $data = mysql_fetch_array( $result ) )
        {
            $arr_result[] = array ( 
                        product_id => $data[product_id],
                        shop_name  => $data[shop_name],
                        status     => $data[status],
                        order_cs   => $data[order_cs],
                );
        }
    }

    function build_options( $arr_options )
    {
        $is_where = 0;

        

        if ( $arr_options[shop_id] )
            $query .= "        and a.shop_id = '" . $arr_options[shop_id] . "'";

        if ( $arr_options[status] )
            $query .= "        and a.status in (" . $arr_options[status] . ")";

        if ( $arr_options[order_cs] )
            $query .= "        and a.order_cs not in (" . $arr_options[order_cs] . ")";

        if ( $arr_options[priority] )
            $query .= "        and a.priority < " . $arr_options[priority];

        if ( $arr_options[date_type] )
            $query .= " and a." . $arr_options[date_type] . " >= '$arr_options[start_date] 00:00:00' and a." . $arr_options[date_type] . " <= '$arr_options[end_date] 23:59:59'";

        return $query;        
    }
    
    // pack 번호로 seq 리스트를 만든다. : "( seq1, seq2, ... )"
    // pack이 0이면 "( seq )" 리턴한다.
    function get_seqList_byPack( $seq, $pack )
    {
        global $connect;
        
        if( $pack > 0 )
        {
            $query = "select seq from orders where pack=$pack";
            $result = mysql_query( $query, $connect );
            
            $seqList = "(";
            while( $data = mysql_fetch_array( $result ) )
                $seqList .= $data[seq] . ",";

            $result_list = substr( $seqList, 0, strlen($seqList)-1) . ")";
        }
        else
            $result_list = "(".$seq.")";

        return $result_list;
    }
    
    // pack 에 포함된 seq 리스트. pack이 아닐 경우 seq 
    function get_pack_seq($pack, &$seq_arr, &$seq_str)
    {
        global $connect;

        $query = "select seq from orders where pack=$pack or seq=$pack";
        $result = mysql_query($query, $connect);
        $seq_arr = array();
        while( $data = mysql_fetch_assoc($result) )
            $seq_arr[] = $data[seq];
        
        $seq_str = implode(",",$seq_arr);
    }
    
    /////////////////////////////////
    // 부분배송 표시 - 합포 주문만 유효.
    function set_part_seq( $pack )
    {
        global $connect;
        if( $pack > 0 )
        {
            $query = "select max(part_seq) max_part_seq from orders";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $new_part_seq = $data[max_part_seq] + 1;
            
            $query = "update orders set part_seq = $new_part_seq where pack=$pack and part_seq=0";
            mysql_query($query, $connect);

/* 잠시 막음. 수정 필요 2011-06-16 장경희

            $seq_arr = array();
            $query = "select seq from orders where pack='$pack'";
            $result = mysql_query($query, $connect);
            while($data = mysql_fetch_assoc($result))
                $seq_arr[] = $data[seq];

            $seq_str = implode(",", $seq_arr);
            
            $query = "select seq from orders where pack='$pack'";
            $result = mysql_query($query, $connect);
            while($data = mysql_fetch_assoc($result))
            {
                // cs 이력 남기기
                $sql = "insert csinfo 
                           set order_seq  = '$data[seq]',
                               pack       = '$pack',
                               input_date = now(),
                               input_time = now(),
                               writer     = '$_SESSION[LOGIN_NAME]',
                               cs_type    = 0,
                               cs_reason  = '',
                               cs_result  = '0',
                               content    = '부분배송[$new_part_seq] - $seq_str'";
                mysql_query ( $sql, $connect );
            }
*/
        }
    }

    /////////////////////////////////
    // 부분배송 주문 수량 가져오기
    function get_part_seq_orders( $status, $part_seq )
    {
        global $connect;
        
        $query = "select count(*) cnt from orders where status = $status and part_seq = $part_seq and order_cs<>1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        return $data[cnt];
    }
}

?>
