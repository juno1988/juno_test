<?

// 부분취소가 가능한 판매처 취소시 사용
// 2014-10-21 : cafe24, 11번가
// 
// $par = array(
//     shop_id,
//     order_id,
//     order_info1,
//     order_info2,
//     qty
// )

class class_cancel
{
    function cancel_order($par)
    {
        global $connect;
        
        // 판매처코드
        $par[shop_code] = $par[shop_id] % 100;
        
        // cancel_history에서 취소 이력을 조회
        $cancel_qty = $this->check_cancel_history($par);

        // 취소할 수량 없으면 리턴
        if( $cancel_qty == 0 )  return true;

        // 주문에서 취소 수량 조회
        $real_cancel_qty = $this->get_cancel_qty($par);
        
        // 부분취소 불가 또는 전체 취소
        if( $real_cancel_qty == -1 )
            return false;
        // 취소할 상품 없음
        else if( $real_cancel_qty == 0 )
            return true;

        // 최종 취소처리해야할 수량 = min(취소요청수량-실제취소수량, 취소해야할수량). 0 보다 작으면 0.
        $final_qty = max(min($par[qty] - $real_cancel_qty, $cancel_qty), 0);
        
        // 최종 취소수량 없으면 
        if( $final_qty == 0 )  return true;
        
        // 취소처리
        $re = $this->cancel_product($par, $final_qty);
        if( !$re )  return false;
        
        // 취소이력
        $this->log_cancel_history($par);
        
        return true;
    }
    
    //****************************************************
    // cancel_history에서 취소 이력을 조회
    //****************************************************
    function check_cancel_history($par)
    {
        global $connect;
        
        $sub_query = "";
        // 11번가, cafe24
        if( $par[shop_code] == 50 || $par[shop_code] == 72 )
            $sub_query = " and order_info1 = '$par[order_info1]' ";

        // 이미 취소처리한 이력이 있는지 확인
        $query = "select * 
                    from cancel_history
                   where shop_id = '$par[shop_id]'
                     and order_id = '$par[order_id]' 
                         $sub_query ";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            // 취소수량 일치
            if( $data[qty] >= $par[qty] )
                $cancel_qty = 0;
            else
                $cancel_qty = $par[qty] - $data[qty];
        }
        else
            $cancel_qty = $par[qty];

        return $cancel_qty;
    }
    
    //****************************************************
    // 취소이력
    //****************************************************
    function log_cancel_history($par)
    {
        global $connect;
        
        // 취소처리 이력
        $query = "insert cancel_history
                     set shop_id = '$par[shop_id]'
                        ,crdate = now()
                        ,order_id = '$par[order_id]' 
                        ,order_info1 = '$par[order_info1]' 
                        ,order_info2 = '$par[order_info2]' 
                        ,qty = '$par[qty]' ";
        mysql_query($query, $connect);
    }
    
    //****************************************************
    // 주문에서 취소 수량 조회
    //****************************************************
    function get_cancel_qty($par)
    {
        global $connect;
        
        $sub_query = "";
        // 11번가
        if( $par[shop_code] == 50 )
            $sub_query = " and a.order_id_seq = '$par[order_info1]' ";
        // cafe24
        else if( $par[shop_code] == 72 )
            $sub_query = " and a.shop_product_id = '$par[order_info1]' ";

        // 주문검색
        $query = "select ifnull(sum(a.qty),0) a_qty
                        ,ifnull(sum(b.qty),0) b_qty
                        ,ifnull(sum(if(b.order_cs in (1,2,3,4),b.qty,0)),0) b_qty_c
                    from orders a
                        ,order_products b
                   where a.seq = b.order_seq
                     and a.shop_id = '$par[shop_id]'
                     and a.order_id = '$par[order_id]'
                         $sub_query 
                     and a.c_seq = 0
                     and a.copy_seq = 0
                     and b.is_gift = 0 ";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 주문이 없으면 리턴
        if( $data[a_qty] == 0 )  return 0;
        
        // 주문수량과 상품 수량이 다르면 false 리턴. 기존 취소로직으로 처리
        if( $data[a_qty] != $data[b_qty] )  return -1;
        
        // 주문수량보다 취소할 수량이 크거나 같으면 false 리턴. 기존 취소로직으로 처리
        if( $data[a_qty] <= $cancel_qty )  return -1;
        
        // 이미 취소된 수량이 크거나 같으면 리턴
        if( $data[b_qty_c] >= $par[qty] )  return 0;
        
        // 취소처리해야할 수량
        return $data[b_qty_c];
    }

    //****************************************************
    // 취소처리
    //****************************************************
    function cancel_product($par, $final_qty)
    {
        global $connect;
        
        $sub_query = "";
        // 11번가
        if( $par[shop_code] == 50 )
            $sub_query = " and a.order_id_seq = '$par[order_info1]' ";
        // cafe24
        else if( $par[shop_code] == 72 )
            $sub_query = " and a.shop_product_id = '$par[order_info1]' ";

        // 주문검색
        $prd_arr = array();
        $query = "select b.seq b_seq
                        ,b.qty b_qty
                    from orders a
                        ,order_products b
                   where a.seq = b.order_seq
                     and a.shop_id = '$par[shop_id]'
                     and a.order_id = '$par[order_id]'
                         $sub_query 
                     and a.c_seq = 0
                     and a.copy_seq = 0
                     and b.is_gift = 0 
                     and b.order_cs not in (1,2,3,4) ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $prd_arr[ $data[b_seq] ] = $data[b_qty];

        // 취소처리할 order_products 배열
        $re = $this->get_cancel_array($prd_arr, $final_qty);
        
        // 배열 검증
        $re_sum = 0;
        foreach( $re as $v )
            $re_sum += $v;
        
        // 수량 다르면 실패
        if( $re_sum != $final_qty )  return false;
        
        // 실제 취소처리
        return $this->do_cancel( $re );
    }

    //****************************************************
    // 실제 취소처리
    //****************************************************
    function do_cancel($re)
    {
        return true;
    }

    //****************************************************
    // 취소처리할 주문 배열 구하기
    //****************************************************
    function get_cancel_array($arr, $qty)
    {
        $arrObj = new ArrayObject($arr);
    
        // 수량으로 감소 정렬
        $arrObj->uasort('cmp_down');
        $arr_d = $arrObj->getArrayCopy();
        
        $re = array();
        $tot_qty = 0;
        foreach( $arr_d as $k => $v )
        {
            // 합이 qty를 넘으면 다음
            if( $tot_qty + $v > $qty )  continue;
            
            $tot_qty += $v;
            $re[$k] = $v;
            
            // 합이 qty와 같으면 종료
            if( $tot_qty == $qty )  return $re;
        }
        
        // 일치 조합 없으면 증가 정렬
        $arrObj->asort();
        $arr_u = $arrObj->getArrayCopy();
        
        $re = array();
        $tot_qty = 0;
        foreach( $arr_u as $k => $v )
        {
            // 합이 qty를 넘으면 다음
            if( $tot_qty + $v > $qty )
            {
                $re[$k] = $qty - $tot_qty;
                return $re;
            }
            
            $tot_qty += $v;
            $re[$k] = $v;
            
            // 합이 qty와 같으면 종료
            if( $tot_qty == $qty )  return $re;
        }
        return $re;    
    }
}

// 감소 정렬
function cmp_down($a, $b) {
    return ($a == $b) ? 0 : (($a > $b) ? -1 : 1);
}

