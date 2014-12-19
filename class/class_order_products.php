<?

class class_order_products
{
    // order_products에서 extra_money를 계산한다.
    function get_extra_money( $seq , $is_all=0 )
    {
        global $connect;
        
        if ( $is_all == 1 )
            $query = "select sum(extra_money) sum_extra_money from order_products where order_seq = $seq";
        else
            $query = "select sum(extra_money) sum_extra_money from order_products where order_seq = $seq and order_cs not in (1,2,3,4)";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[sum_extra_money] ? $data[sum_extra_money] : 0;
    }
    
    // 취소 상품 개수..
    function get_refund_price( $seq, $start_date='', $end_date='' )
    {
        global $connect;
        $query = "select order_cs, supply_price,extra_money from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 전체취소, 전체 취소
        if ( $data[order_cs] == 1 || $data[order_cs] == 3 )
        {
            return $data[supply_price] + $data[extra_money];
        }
        else if ( $data[order_cs] == 2 || $data[order_cs] == 4 )
        {
            $query      = "select sum(refund_price) refund_price 
                             from order_products 
                            where order_seq=$seq and order_cs in (1,2,3,4)";
    
            if ( $start_date )
                $query .= " and cancel_date >= '$start_date 00:00:00'";
            
            if ( $end_date )
                $query .= " and cancel_date <= '$end_date 23:59:59'";
    
            $result     = mysql_query( $query, $connect );
            $data       = mysql_fetch_assoc( $result );
            return $data[refund_price] ? $data[refund_price] : 0;
        }
    }
    
    // 취소 상품 개수..
    function cancel_count( $seq )
    {
        global $connect;
        $arr_result = array();
        $query      = "select sum(qty) qty from order_products where order_seq=$seq and order_cs in (1,2,3,4)";
        $result     = mysql_query( $query, $connect );
        $data       = mysql_fetch_assoc( $result );
        return $data[qty];
    }
    
    // order.seq에 포함된 상품의 array 정보 return
    // 2009.9.25 - jk
    function get_arr_products( $seq )
    {
        global $connect;
        
        $arr_result = array();
        $query      = "select product_id, supply_id, sum(qty) qty from order_products where order_seq=$seq group by product_id";        
        $result     = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = array( 
                product_id => $data[product_id], 
                supply_id  => $data[supply_id], 
                qty        => $data[qty],
                trans_fee  => 0
                );   
        }
        
        return $arr_result;
    }
    
    // seq를 검사하여 이미 등록되어있는지 확인한다.
    function is_registered($seq)
    {
        global $connect;
        
        $query = "select * from order_products where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows($result) > 0 )
            return true;
        else
            return false;
    }
        
    // 상품을 추가한다.
    function insert_product($shop_id, $shop_product_id, $shop_options, $match_code, $order_seq, $qty, $marked)
    {
        global $connect;

        $shop_options = $shop_options;
        $query = "insert order_products 
                     set shop_id         = '$shop_id',
                         shop_product_id = '$shop_product_id', 
                         shop_options    = '$shop_options', 
                         match_code      = '$match_code', 
                         order_seq       = '$order_seq', 
                         qty             = '$qty',
                         org_qty         = '$qty',
                         marked          = '$marked'";
        return mysql_query( $query, $connect );
    }

    //////////////////////////////////////////////////////////
    // 매칭 처리.
    function match_product($prd_seq, $arr_match_id, $arr_match_qty, $no_save=0, $qty_mul, $match_type=0)
    {
        // $prd_seq : order_products 테이블의 seq
        // $arr_match_info : 매칭할 product_id, qty 배열
        //
        // return 0 : 성공
        //        1 : order_products에 해당 상품이 없음
        //        2 : 해당 상품이 이미 매칭되었음
        //        3 : 쿼리 실패

        global $connect;
        
        // 해당 상품을 order_products 테이블에서 가져온다.
        $query = "select * from order_products where seq=$prd_seq";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) > 0 )
        {
            $data = mysql_fetch_array( $result );
            
            // 상태가 매칭 전이면 매칭한다.
            if( $data[status] == 0 )
            {
                for($i=0; $i<count($arr_match_id); $i++)
                {
                    // 매칭 수량에, 주문 수량을 곱한다. 20091210 jkh 수정함. 
                    if( $qty_mul )
                        $final_qty = $arr_match_qty[$i] * $data[qty];
                    else
                        $final_qty = $arr_match_qty[$i];

                    $query_spl = "select * from products where product_id='$arr_match_id[$i]'";
                    $result_spl = mysql_query($query_spl, $connect);
                    $data_spl = mysql_fetch_array($result_spl);

                    $org_price = $data_spl[org_price];
                    $shop_price = $data_spl[shop_price];
                    
                    // 수량만큼 order_products 쪼게기 - 위메프는 수량만큼 불가
                    // 2014-09-11 장경희. 위메프도 가능하도록 수정.
                    if( $_SESSION[USE_INDIVIDUAL_QTY] )
                    {
                        // 처음이면 update
                        if( $i == 0 )
                        {
                            // 처음은 수량 1로 update
                            $query_match = "update order_products 
                                               set product_id      = '$arr_match_id[0]',
                                                   qty             = '1',
                                                   status          = 1,
                                                   supply_id       = '$data_spl[supply_code]',
                                                   org_price       = '$org_price',
                                                   shop_price      = '$shop_price',
                                                   no_save         = $no_save,
                                                   match_date      = now(),
                                                   match_type      = '$match_type',
                                                   match_worker    = '$_SESSION[LOGIN_NAME]'
                                             where seq = $prd_seq";

                            if( !mysql_query( $query_match, $connect ) )  return 3;
                            
                            // 처음이고 수량이 1 이상인 경우는 수량만큼 insert
                            for($j=0; $j<$final_qty-1; $j++ )
                            {
                                $query_match = "insert order_products
                                                   set order_seq       = '$data[order_seq]',
                                                       product_id      = '$arr_match_id[$i]',
                                                       qty             = '1',
                                                       org_qty         = '$data[org_qty]',
                                                       order_cs        = '$data[order_cs]',
                                                       shop_id         = '$data[shop_id]',
                                                       shop_product_id = '$data[shop_product_id]',
                                                       shop_options    = '$data[shop_options]',
                                                       marked          = '$data[marked]',
                                                       status          = 1,
                                                       supply_id       = '$data_spl[supply_code]',
                                                       org_price       = '$org_price',
                                                       shop_price      = '$shop_price',
                                                       no_save         = $no_save,
                                                       match_date      = now(),
                                                       match_type      = '$match_type',
                                                       match_worker    = '$_SESSION[LOGIN_NAME]'";

                                if( !mysql_query( $query_match, $connect ) )  return 3;
                            }
                        }
                        // 두 번 째 이후면 insert
                        else
                        {
                            // 두번째 이후는 수량만큼 insert
                            for($j=0; $j<$final_qty; $j++ )
                            {
                                $query_match = "insert order_products
                                                   set order_seq       = '$data[order_seq]',
                                                       product_id      = '$arr_match_id[$i]',
                                                       qty             = '1',
                                                       org_qty         = '$data[org_qty]',
                                                       order_cs        = '$data[order_cs]',
                                                       shop_id         = '$data[shop_id]',
                                                       shop_product_id = '$data[shop_product_id]',
                                                       shop_options    = '$data[shop_options]',
                                                       marked          = '$data[marked]',
                                                       status          = 1,
                                                       supply_id       = '$data_spl[supply_code]',
                                                       org_price       = '$org_price',
                                                       shop_price      = '$shop_price',
                                                       no_save         = $no_save,
                                                       match_date      = now(),
                                                       match_type      = '$match_type',
                                                       match_worker    = '$_SESSION[LOGIN_NAME]'";

                                if( !mysql_query( $query_match, $connect ) )  return 3;
                            }
                        }
                    }
                    
                    // 다른 업체는 정상적으로.
                    else
                    {
                        // 처음이면 update
                        if( $i == 0 )
                        {
                            $query_match = "update order_products 
                                               set product_id      = '$arr_match_id[0]',
                                                   qty             = '$final_qty',
                                                   status          = 1,
                                                   supply_id       = '$data_spl[supply_code]',
                                                   org_price       = " . $org_price * $final_qty . ",
                                                   shop_price      = " . $shop_price * $final_qty . ",
                                                   no_save         = $no_save,
                                                   match_date      = now(),
                                                   match_type      = '$match_type',
                                                   match_worker    = '$_SESSION[LOGIN_NAME]'
                                             where seq = $prd_seq";
                        }
                        // 두 번 째 이후면 insert
                        else
                        {
                            $query_match = "insert order_products
                                               set order_seq       = '$data[order_seq]',
                                                   product_id      = '$arr_match_id[$i]',
                                                   qty             = '$final_qty',
                                                   org_qty         = '$data[org_qty]',
                                                   order_cs        = '$data[order_cs]',
                                                   shop_id         = '$data[shop_id]',
                                                   shop_product_id = '$data[shop_product_id]',
                                                   shop_options    = '$data[shop_options]',
                                                   marked          = '$data[marked]',
                                                   status          = 1,
                                                   supply_id       = '$data_spl[supply_code]',
                                                   org_price       = " . $org_price * $final_qty . ",
                                                   shop_price      = " . $shop_price * $final_qty . ",
                                                   no_save         = $no_save,
                                                   match_date      = now(),
                                                   match_type      = '$match_type',
                                                   match_worker    = '$_SESSION[LOGIN_NAME]'";
                        }

                        if( !mysql_query( $query_match, $connect ) )  return 3;
                    }
                }
            }
            // 상태가 매칭 후면 종료
            else
                return 2;
        }
        // 해당 상품이 없으면 오류
        else
            return 1;
    }

    //////////////////////////////////////////////////////////
    // 매칭 취소한다.
    function cancel_match()
    {
        global $connect, $seq, $saved;

        $query = "select * from order_products where seq = $seq";
        $result = mysql_query($query, $connect);
        // 해당 주문 정보가 삭제됐음.
        if( mysql_num_rows($result) == 0 )  return 1;
        
        $data = mysql_fetch_array($result);
        // 해당 주문이 매칭 취소되었음
        if( $data[status] == 0 )  return 2;

        $data_order = class_order::get_order($data[order_seq]);
        // 해당 주문이 발주 완료했음.
        if( $data_order[status] != 0 )  return 3;

        // 해당 주문이 합포단계로 넘어갔음
        if( $data_order[status] == 0 && $data_order[order_status] > 30 )  return 4;

        // 매칭 키 값
        $m_id = $data[shop_id];
        $m_pd = $data[shop_product_id];
        $m_op = $data[shop_options];

        // 매칭조건에 상품명 추가
        $match_name = 0;
        if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
        {
            $match_name = 1;
            
            $query_pname = "select product_name from orders where seq=$data[order_seq]";
            $result_pname = mysql_query($query_pname, $connect);
            $data_pname = mysql_fetch_assoc($result_pname);
            
            $m_nm = $data_pname[product_name];
            
            $name_arr = array();
            $query_name_seq = "select seq 
                                 from orders
                                where status=0 
                                  and shop_id = $m_id
                                  and shop_product_id = '$m_pd'
                                  and product_name = '$m_nm' ";
            $result_name_seq = mysql_query($query_name_seq, $connect);
            while( $data_name_seq = mysql_fetch_assoc($result_name_seq) )
                $name_arr[] = $data_name_seq[seq];
                
            $name_str = implode(",", $name_arr);
        }
        
        // 매칭 정보를 저장했을 경우
        if( $saved )
        {
            // 매칭 정보를 삭제한다.
            $query = "delete from code_match 
                       where shop_id     = '$m_id' and 
                             shop_code   = '$m_pd' and 
                             shop_option = '$m_op'";
                             
            if( $match_name )
                $query .= " and shop_product_name = '$m_nm' ";
            mysql_query($query, $connect);
            
            // order_products에서 매칭된 주문의 order_seq를 구한다. 자동매칭된(marked=0,1,2) 주문만..
            $seq_arr = array();
            $query = "select order_seq 
                        from order_products
                       where shop_id         = '$m_id' and 
                             shop_product_id = '$m_pd' and 
                             shop_options    = '$m_op' and
                             no_save = 0 and 
                             marked in (0,1,2)
                    group by order_seq";
            $result = mysql_query($query, $connect);
            
            $seq_arr = array();
            while( $data = mysql_fetch_array($result) )
            {
                // 매칭조건에 상품명 포함인 경우
                if( $match_name && array_search($data[order_seq], $name_arr) === false )
                    continue;
                
                // 이미 매칭 완료가 된 주문은 매칭취소 대상에서 제외
                $query_pack = "select seq from orders where seq=$data[order_seq] and order_status>30";
                $result_pack = mysql_query($query_pack,$connect);
                if( mysql_num_rows($result_pack) > 0 )  continue;
                
                // 여러개의 상품으로 매칭된 경우 하나만 남기고 삭제
                $query_multi = "select seq
                                  from order_products 
                                 where order_seq       = $data[order_seq] and
                                       shop_id         = '$m_id' and 
                                       shop_product_id = '$m_pd' and 
                                       shop_options    = '$m_op' and
                                       marked in (0,1,2)
                                 order by seq";
                $result_multi = mysql_query($query_multi, $connect);
                if( mysql_num_rows($result_multi) > 1 )
                {
                    $del_seq_arr = array();
                    while( $data_multi = mysql_fetch_assoc($result_multi) )
                        $del_seq_arr[] = $data_multi[seq];

                    // 첫전째 제외하고 나머지 삭제
                    array_shift( $del_seq_arr );
                    $del_seq_list = implode(",", $del_seq_arr);
                    
                    mysql_query("delete from order_products where seq in ($del_seq_list)", $connect);
                }
                $seq_arr[] = $data[order_seq];
            }
            
            // order_products에서 해당 주문의 매칭 정보를 지운다.
            foreach( $seq_arr as $order_seq )
            {
                $query = "update order_products 
                             set product_id = null,
                                 status     = 0,
                                 match_date = null,
                                 supply_id  = null,
                                 org_price  = 0,
                                 shop_price = 0,
                                 qty        = org_qty
                           where order_seq       = '$order_seq' and 
                                 shop_id         = '$m_id' and 
                                 shop_product_id = '$m_pd' and 
                                 shop_options    = '$m_op' and
                                 no_save = 0 and 
                                 marked in (0,1,2)";
                mysql_query($query, $connect);
            }
        }
        // 매칭 정보를 저장하지 않았을 경우
        else
        {
            // 매칭 완료가 안된 경우만
            $query_pack = "select seq from orders where seq=$data[seq] and order_status>30";
            $result_pack = mysql_query($query_pack,$connect);
            if( mysql_num_rows($result_pack) == 0 )
            {
                // 여러개의 상품으로 매칭된 경우 하나만 남기고 삭제
                $query_multi = "select seq
                                  from order_products 
                                 where order_seq       = $data[order_seq] and
                                       shop_id         = '$m_id' and 
                                       shop_product_id = '$m_pd' and 
                                       shop_options    = '$m_op'
                                 order by seq";
                $result_multi = mysql_query($query_multi, $connect);
                if( mysql_num_rows($result_multi) > 1 )
                {
                    $del_seq_arr = array();
                    while( $data_multi = mysql_fetch_assoc($result_multi) )
                        $del_seq_arr[] = $data_multi[seq];

                    // 첫전째 제외하고 나머지 삭제
                    array_shift( $del_seq_arr );
                    $del_seq_list = implode(",", $del_seq_arr);
                    
                    mysql_query("delete from order_products where seq in ($del_seq_list)", $connect);
                }
    
                // order_products에서 해당 주문의 매칭 정보를 지운다.
                $query = "update order_products 
                             set product_id = null,
                                 status     = 0,
                                 match_date = null,
                                 supply_id  = null,
                                 org_price  = 0,
                                 shop_price = 0,
                                 no_save    = 0,
                                 qty        = org_qty
                           where order_seq       = '$data[order_seq]' and 
                                 shop_id         = '$m_id' and 
                                 shop_product_id = '$m_pd' and 
                                 shop_options    = '$m_op'";
                mysql_query($query, $connect);
            }
        }
        return 0;
    }
}
?>
