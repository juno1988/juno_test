<?
////////////////////////////////
// class name: class_F
//

class class_F {
    function get_query_opt($basis, $start_date, $end_date)
    {
        if ($basis == 1)       // 발주일 기준
        {
            $ret = " and collect_date >= '$start_date' 
                     and collect_date <= '$end_date'";
        } 
        else if ($basis == 2)  // 송장입력일 기준
        {
            $ret = " and substring(trans_date,1,10) >= '$start_date'
                     and substring(trans_date,1,10) <= '$end_date'";

        }
        else if ($basis == 3) // POS 출고일 기준
        {
            $ret = " and status = 8
                     and substring(trans_date_pos,1,10) >= '$start_date'
                     and substring(trans_date_pos,1,10) <= '$end_date'";
        }
        return $ret;
    }

    //////////////////////////////////////////////
    // 특정 공급처 매출금액 - 상품별..
    function  get_sale_sql($supply_id, $condition)   
    {
        $sql= "
          select distinct a.product_id, a.supply_id, b.name as product_name,
                 sum(a.shop_price*a.qty) shop_price , 
                 sum(a.org_price*a.qty) org_price, 
                 sum(a.supply_price*a.qty) supply_price , 
                 sum(a.qty) qty, 
                 sum(a.amount) amount
            from orders a, products b
           where a.product_id = b.product_id
             and supply_id != ''
             and supply_id = '${supply_id}'
                 ${condition}
             and trans_no > '' 
             and order_cs not in (3,6,8,9,10)
           group by product_id
        ";

        return $sql;
    }

    // 공급처 전체 매출 금액 (공급처별)
    function get_sale2_sql ($condition)
    {
            $sql = "
          select distinct supply_id,
                 sum(shop_price*qty) sale_amount , 
                 sum(org_price*qty) org_amount, 
                 sum(supply_price*qty) supply_amount, 
                 sum(qty) sale_qty
            from orders
           where supply_id != ''
                 ${condition}
             and trans_no > '' 
             and order_cs not in (3,6,8,9,10)
           group by supply_id
        ";
        return $sql;
    }



    //////////////////////////////////////////////
    // 특정 공급처 취소금액 - 상품별
    function  get_cancel_sql($supply_id, $start_date, $end_date)
    {
        global $query_type, $apply_cancel_date;
        $date_type[1] = "collect_date";
        $date_type[2] = "trans_date";
        $date_type[3] = "trans_date_pos";

        if ( $apply_cancel_date )
            $date_type[$query_type] = "refund_date";
        
        // pos일 기준이면 배송후 취소만
        if( $date_type[$query_type] == "trans_date_pos" )
            $status_condition = " and status=8 ";
        else
            $status_condition = "";

        $sql= "
              select distinct a.product_id, a.supply_id, b.name as product_name,
                 sum(a.shop_price*a.qty) shop_price , 
                 sum(a.org_price*a.qty) org_price, 
                 sum(a.supply_price*a.qty) supply_price, 
                 sum(a.qty) qty, 
                 sum(a.amount) amount
            from orders a, products b
           where a.product_id=b.product_id
             and a.supply_id != ''
             and a.supply_id = '$supply_id'
                 $status_condition
             and date_format(" . $date_type[$query_type] . ", '%Y-%m-%d') >= '$start_date'
             and date_format(" . $date_type[$query_type] . ", '%Y-%m-%d') <= '$end_date'
             and order_cs in (1,2,3,4,12)
           group by a.product_id
        ";

        return $sql;
    }

   
    // 전체 공급처별 취소금액
    function  get_cancel2_sql($start_date, $end_date)
    {
        global $query_type, $apply_cancel_date;
        $date_type[1] = "collect_date";
        $date_type[2] = "trans_date";
        $date_type[3] = "trans_date_pos";

        if ( $apply_cancel_date )
            $date_type[$query_type] = "refund_date";
        
        // pos일 기준이면 배송후 취소만
        if( $date_type[$query_type] == "trans_date_pos" )
            $status_condition = " and status=8 ";
        else
            $status_condition = "";

        $sql= "
              select distinct supply_id,
                 sum(shop_price*qty) cancel_sale_amount,
                 sum(org_price*qty) cancel_org_amount, 
                 sum(supply_price*qty) cancel_supply_amount, 
                 sum(qty) cancel_sale_qty
            from orders
           where supply_id != ''
                 $status_condition
             and date_format(" . $date_type[$query_type] . ", '%Y-%m-%d') >= '$start_date'
             and date_format(" . $date_type[$query_type] . ", '%Y-%m-%d') <= '$end_date'
             and order_cs in (1,2,3,4,12)
           group by supply_id
        ";
        return $sql;
    }

    // 특정 공급처 전체 매출주문
    function get_full_sql($supply_id, $query)
    {
        $sql= "
          select *
            from orders
           where supply_id != ''
             and supply_id = '${supply_id}'
                 ${query}
             and trans_no > '' 
             and order_cs not in (3,6,8,9,10)
           order by seq
        ";

        return $sql;
    }

    // 특정 공급처 전체 취소주문
    function get_fullx_sql($supply_id, $start_date, $end_date)
    {
        $sql= "
              select *
            from orders
           where supply_id != ''
             and supply_id = '$supply_id'
             and date_format(refund_date, '%Y-%m-%d') >= '$start_date'
             and date_format(refund_date, '%Y-%m-%d') <= '$end_date'
             and order_cs in (4)
           order by seq
        ";
        return $sql;
    }

    // c_dt:발주일, r_dt:환불일 , s_dt:조회시작일, e_dt:조회마지막일
    function make_status($order_cs, $c_dt, $r_dt, $s_dt, $e_dt)
    {
        $c_dt = substr($c_dt,0,10);
        $r_dt = substr($r_dt,0,10);
        $s_dt = substr($s_dt,0,10);
        $e_dt = substr($e_dt,0,10);

        switch ($order_cs)
         {
          case 0 :
          case 5 :
          case 6 :
          case 7 :
          case 8 :
          case 11 :
                $ret = "";
                break;
          case 4 :
                    if ($c_dt < $s_dt && $r_dt <= $e_dt) $ret = "전월취소";
                    else if ($c_dt >= $s_dt && $r_dt <= $e_dt) $ret = "당월취소";
                    else if ($c_dt >= $s_dt && $r_dt > $e_dt) $ret = "익월취소";
                break;
          default :
                $ret = $order_cs;
                break;
        }
        return $ret;
    }

    function get_order_cs($order_cs)
    {
        switch ($order_cs)
        {
          case 0:        
                $ret = "정상";
                break;
          case 3:        
                $ret = "배송전취소";
                break;
          case 4:        
                $ret = "배송후취소";
                break;
          case 5:        
          case 6:        
          case 7:        
          case 8:        
                $ret = "정상(교환)";
                break;
          case 11:        
                $ret = "교환";
                break;
          default :
                $ret = $order_cs;
                break;
          
        }
        return $ret;
    }
}

?>
