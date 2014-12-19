<?
////////////////////////////////
// class name: class_F
//

class class_F {
    function get_query_opt($basis, $start_date, $end_date)
    {
        if ($basis == 1)       // ������ ����
        {
            $ret = " and collect_date >= '$start_date' 
                     and collect_date <= '$end_date'";
        } 
        else if ($basis == 2)  // �����Է��� ����
        {
            $ret = " and substring(trans_date,1,10) >= '$start_date'
                     and substring(trans_date,1,10) <= '$end_date'";

        }
        else if ($basis == 3) // POS ����� ����
        {
            $ret = " and status = 8
                     and substring(trans_date_pos,1,10) >= '$start_date'
                     and substring(trans_date_pos,1,10) <= '$end_date'";
        }
        return $ret;
    }

    //////////////////////////////////////////////
    // Ư�� ����ó ����ݾ� - ��ǰ��..
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

    // ����ó ��ü ���� �ݾ� (����ó��)
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
    // Ư�� ����ó ��ұݾ� - ��ǰ��
    function  get_cancel_sql($supply_id, $start_date, $end_date)
    {
        global $query_type, $apply_cancel_date;
        $date_type[1] = "collect_date";
        $date_type[2] = "trans_date";
        $date_type[3] = "trans_date_pos";

        if ( $apply_cancel_date )
            $date_type[$query_type] = "refund_date";
        
        // pos�� �����̸� ����� ��Ҹ�
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

   
    // ��ü ����ó�� ��ұݾ�
    function  get_cancel2_sql($start_date, $end_date)
    {
        global $query_type, $apply_cancel_date;
        $date_type[1] = "collect_date";
        $date_type[2] = "trans_date";
        $date_type[3] = "trans_date_pos";

        if ( $apply_cancel_date )
            $date_type[$query_type] = "refund_date";
        
        // pos�� �����̸� ����� ��Ҹ�
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

    // Ư�� ����ó ��ü �����ֹ�
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

    // Ư�� ����ó ��ü ����ֹ�
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

    // c_dt:������, r_dt:ȯ���� , s_dt:��ȸ������, e_dt:��ȸ��������
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
                    if ($c_dt < $s_dt && $r_dt <= $e_dt) $ret = "�������";
                    else if ($c_dt >= $s_dt && $r_dt <= $e_dt) $ret = "������";
                    else if ($c_dt >= $s_dt && $r_dt > $e_dt) $ret = "�Ϳ����";
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
                $ret = "����";
                break;
          case 3:        
                $ret = "��������";
                break;
          case 4:        
                $ret = "��������";
                break;
          case 5:        
          case 6:        
          case 7:        
          case 8:        
                $ret = "����(��ȯ)";
                break;
          case 11:        
                $ret = "��ȯ";
                break;
          default :
                $ret = $order_cs;
                break;
          
        }
        return $ret;
    }
}

?>
