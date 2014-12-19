<?
require_once "class_top.php";
require_once "class_F.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_shop.php";
require_once "class_product.php";
require_once "class_statconfig.php";
require_once "class_ui.php";

////////////////////////////////
// class name: class_FP00

class class_FP00 extends class_top {

    ///////////////////////////////////////////
    function FP00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;
        
        // 페이지
        $line_per_page = 50;

        if( !$page )
            $page = 1;
        else
        {
            $this->show_wait();

            $str_supply_code = $this->get_str_supply();
            $supply_code = split(",", $str_supply_code );

            // link url
            $par = array('template','product_code','product_name','options','str_supply_code','s_group_id','query_type','start_date','end_date','order_status','shop_id','include_sum','except_gift');
            $link_url = $this->build_link_url3( $par );
            $link_url .= "supply_code=$str_supply_code";

            // 화면 표시할 배열
            $total_arr = array();
            $data_arr = array();
            $orders_only_arr = array();
            
            ///**************************************
            // query - order_products 포함
            $orders_only = false;
            $query = $this->get_FP00_query($orders_only);
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);
            
            // total
            while( $data = mysql_fetch_assoc($result) )
            {
                $total_arr['p_qty'] += $data['sum_c_qty'];
                $total_arr['org_price'] += $data['sum_org_price'];
                $total_arr['extra_money'] += $data['sum_extra_money'];
            }

            $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
debug("class_FP00 orders_only-false : " . $query);
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $data_arr[] = array(
                    "a_shop_id"         => $data['a_shop_id']
                   ,"b_shop_name"       => $data['b_shop_name']
                   ,"a_shop_product_id" => $data['a_shop_product_id']
                   ,"a_product_name"    => $data['a_product_name']
                   ,"a_options"         => $data['a_options']
                   ,"sum_c_qty"         => $data['sum_c_qty']
                   ,"sum_org_price"     => $data['sum_org_price']
                   ,"sum_extra_money"   => $data['sum_extra_money']
                );                    
            }

            ///**************************************
            // query - order_products 제외
            $orders_only = true;
            $query = $this->get_FP00_query($orders_only);
            $result = mysql_query($query, $connect);
            
            // total
            while( $data = mysql_fetch_assoc($result) )
            {
                $total_arr['qty'] += $data['sum_a_qty'];
                $total_arr['amount'] += $data['sum_a_amount'];
                $total_arr['supply_price'] += $data['sum_a_supply_price'];
                $total_arr['prepay_price'] += $data['sum_a_prepay_price'];
            }

            $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
debug("class_FP00 orders_only-true : " . $query);
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ] = array(
                    "qty"          => $data['sum_a_qty']
                   ,"amount"       => $data['sum_a_amount']
                   ,"supply_price" => $data['sum_a_supply_price']
                   ,"prepay_price" => $data['sum_a_prepay_price']
                );                    
            }

            // orders
            $trans_price_arr = $this->get_FP00_trans_price($total_arr);
            
            $this->hide_wait();
        }
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    ///////////////////////////////////////////
    function FP00_old()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;
        
        // 페이지
        $line_per_page = 50;

        if( !$page )
            $page = 1;
        else
        {
            $this->show_wait();

            $str_supply_code = $this->get_str_supply();
            $supply_code = split(",", $str_supply_code );

            // link url
            $par = array('template','product_code','product_name','options','str_supply_code','s_group_id','query_type','start_date','end_date','order_status','shop_id','include_sum','except_gift');
            $link_url = $this->build_link_url3( $par );
            $link_url .= "supply_code=$str_supply_code";

            // 화면 표시할 배열
            $total_arr = array();
            $data_arr = array();
            $orders_only_arr = array();
            
            ///**************************************
            // query - order_products 포함
            $orders_only = false;
            $query = $this->get_FP00_query($orders_only);
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);
            
            // total
            while( $data = mysql_fetch_assoc($result) )
            {
                $total_arr['p_qty'] += $data['sum_c_qty'];
                $total_arr['org_price'] += $data['sum_org_price'];
                $total_arr['extra_money'] += $data['sum_extra_money'];
            }

            $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
debug("class_FP00 orders_only-false : " . $query);
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $data_arr[] = array(
                    "a_shop_id"         => $data['a_shop_id']
                   ,"b_shop_name"       => $data['b_shop_name']
                   ,"a_shop_product_id" => $data['a_shop_product_id']
                   ,"a_product_name"    => $data['a_product_name']
                   ,"a_options"         => $data['a_options']
                   ,"sum_c_qty"         => $data['sum_c_qty']
                   ,"sum_org_price"     => $data['sum_org_price']
                   ,"sum_extra_money"   => $data['sum_extra_money']
                );                    
            }

            ///**************************************
            // query - order_products 제외
            $orders_only = true;
            $query = $this->get_FP00_query($orders_only);
            $result = mysql_query($query, $connect);
            
            // total
            while( $data = mysql_fetch_assoc($result) )
            {
                $total_arr['qty'] += $data['sum_a_qty'];
                $total_arr['amount'] += $data['sum_a_amount'];
                $total_arr['supply_price'] += $data['sum_a_supply_price'];
                $total_arr['prepay_price'] += $data['sum_a_prepay_price'];
            }

            $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
debug("class_FP00 orders_only-true : " . $query);
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ] = array(
                    "qty"          => $data['sum_a_qty']
                   ,"amount"       => $data['sum_a_amount']
                   ,"supply_price" => $data['sum_a_supply_price']
                   ,"prepay_price" => $data['sum_a_prepay_price']
                );                    
            }

            // orders
            $trans_price_arr = $this->get_FP00_trans_price($total_arr);
            
            $this->hide_wait();
        }
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_FP00_query($orders_only)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;
        
		switch ($query_type) {
			case "collect_date" :
				$date_str = "a.collect_date as a_date"; 		
				break;
			case "trans_date_pos" :
				$date_str = "a.trans_date_pos as a_date"; 		
				break;
			case "order_date" :
				$date_str = "a.order_date as a_date"; 			
				break;
		}

        if( _DOMAIN_ == 'mkh2009' )
            $org_price_str = " sum( if(c.org_price>0, c.org_price, d.org_price * c.qty) ) sum_org_price ";
        else
            $org_price_str = " sum( d.org_price * c.qty ) sum_org_price ";

        // query orders
        if( $orders_only )
        {
            $query = "select a.shop_id           a_shop_id
                            ,a.shop_product_id   a_shop_product_id
                            ,a.options           a_options
                            ,sum(a.qty)          sum_a_qty
                            ,sum(a.amount)       sum_a_amount
                            ,sum(a.supply_price) sum_a_supply_price
                            ,sum(a.prepay_price) sum_a_prepay_price
                        from orders a, 
                             shopinfo b ";
        }
        else
        {
            $query = "select a.shop_id           a_shop_id
                            ,b.shop_name         b_shop_name
                            ,a.shop_product_id   a_shop_product_id
                            ,a.product_name      a_product_name
                            ,a.options           a_options
    						,${date_str}
                            ,sum(c.qty)          sum_c_qty
                            ,sum(c.extra_money)  sum_extra_money
                            ,$org_price_str
                        from orders a,
                             shopinfo b,
                             order_products c,
                             products d ";
        }
        $where = $this->get_FP00_where($orders_only);
        $query = $query . $where . " group by a.shop_id, a.shop_product_id, a.options";
        return $query;
    }
    
    function get_FP00_where($orders_only=0)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;
        
        $supply_group_list = "";
        // 공급처그룹 선택
        if( $s_group_id )
        {
            $query_group = "select code from userinfo where group_id = $s_group_id";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $supply_group_list .= ($supply_group_list ? "," : "") . $data_group[code];
        }

        // trim
        $product_code = trim( $product_code );
        $product_name = trim( $product_name );
        $options = trim( $options );
        
        if( $orders_only )
        {
            $where = "where a.shop_id = b.shop_id 
                        and a.order_cs<>1 ";
        }
        else
        {
            $where  = " where a.shop_id = b.shop_id 
                          and a.seq = c.order_seq 
                          and c.product_id = d.product_id 
                          and a.order_cs<>1 ";
        }
        
        // 날짜
		switch ($query_type) {
			case "collect_date" :
				$where .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
				break;
			case "trans_date_pos" :
				$where .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
				break;
			case "order_date" :
				$where .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";
				break;
		}

        // 복수 공급처 
        if( $str_supply_code || $supply_group_list || $except_gift )
            $where .= " and a.seq in (select order_seq from order_products where order_cs not in (1,2) ";
        if( $str_supply_code )
            $where .= " and supply_id in ($str_supply_code) ";
        if( $supply_group_list )
            $where .= " and supply_id in ($supply_group_list) ";

        if( $except_gift )
            $where .= " and is_gift = 0 ";
        if( $str_supply_code || $supply_group_list || $except_gift )
            $where .= " ) ";

        // 상태
        if ( $order_status == 1 )
            $status = 1;
        else if ( $order_status == 7 )
            $status = 7;
        else if ( $order_status == 8 )
            $status = 8;
        else if ( $order_status == 99 )
            $status = "1,7";

        if ( $status )
            $where .= " and a.status in ( $status )";
        else
            $where .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $where .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $where .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $where .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $where .= " and a.options like '%$options%'";
            
        // 배송후교환
        $obj = new class_statconfig();
        $arr_config = $obj->get_config();    

        if ( $arr_config[change] == "org")
            $where .= " and a.c_seq = 0 ";
        else
            $where .= " and a.order_cs not in (7,8)";

        return $where;
    }
    
    function get_FP00_trans_price(&$total_arr)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;
        
        $trans_price_arr = array();

        // 기본 택배비
        $base_trans_price = $this->get_base_trans_price();

        // 합포주문의 배송비 계산을 위한 수량 배열
        $pack_qty = array();
        $query = "select a.pack     a_pack
                        ,sum(a.qty) sum_a_qty
                    from orders a,
                         shopinfo b ";
        $orders_only = true;
        $query .= $this->get_FP00_where($orders_only) . " and a.trans_who='선불' and a.pack>0 group by a.pack";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $pack_qty[$data['a_pack']] = $data['sum_a_qty'];

        // query orders
        $query = "select a.pack              a_pack
                        ,a.shop_id           a_shop_id
                        ,a.shop_product_id   a_shop_product_id
                        ,a.options           a_options
                        ,a.qty               a_qty
                    from orders a
                        ,shopinfo b ";
        $orders_only = true;
        $query .= $this->get_FP00_where($orders_only);
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 선불택배비
            if( $data[a_pack] > 0 )
                $trans_price = $base_trans_price * ( $data['a_qty'] / $pack_qty[$data['a_pack']] );
            else
                $trans_price = $base_trans_price;

            $trans_price_arr[$data['a_shop_id']][$data['a_shop_product_id']][$data['a_options']] += $trans_price;
            $total_arr[trans_price] += $trans_price;
        }
        
        return $trans_price_arr;
    }

    function FP01()
    {
        global $template, $connect, $shop_id,$product_id,$options,$start_date,$end_date,$query_type;
        
        $options = urldecode(base64_decode( $options ));        
        
        if ( $query_type == "trans_date_pos" )
        {
            $start_date = $start_date . " 00:00:00";
            $end_date   = $end_date   . " 23:59:59";
        }
        
        $query = "select a.order_id,a.collect_date,b.qty, b.org_price, b.order_seq, b.product_id
                    from orders a, order_products b
                   where a.seq             =  b.order_seq
                     and a.$query_type    >= '$start_date'
                     and a.$query_type    <= '$end_date'
                     and a.shop_id         =  '$shop_id'
                     and a.shop_product_id =  '$product_id'
                     and a.options         =  '$options'";
        
        // echo $query . "<br>";
        
        $arr_result = array();
        $result     = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) ) 
        {
            $_arr = $this->get_product_info( $data[product_id] );
            
            $arr_result[] = array(
                order_id      => $data[order_id]
                ,collect_date => $data[collect_date]
                ,order_seq    => $data[order_seq]
                ,product_id   => $data[product_id]
                ,name         => $_arr[name]
                ,options      => $_arr[options]
                ,org_price    => $data[org_price]
                ,qty          => $data[qty]
            );   
        }
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_product_info( $product_id )
    {
        global $connect;
        
        $query = "select * from products where product_id='$product_id'";   
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data;
    }
    
    function get_base_trans_price()
    {
        global $connect;
        
        $query = "select value from stat_config where code='usertrans_price'";   
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[value];
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 옵션매칭
    function save_file()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$shop_id, $include_sum, $except_gift;

		switch ($query_type) {
			case "collect_date" :
				$date_str = "발주일"; 		break;
			case "trans_date_pos" :
				$date_str = "배송일"; 		break;
			case "order_date" :
				$date_str = "주문일"; 		break;
		}

        // 엑셀 헤더
        $arr = array();
        
        if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
        {
            $arr[] = array(
                "select_date"  => ${date_str},
                "shop_name"    => "판매처",
                "product_code" => "상품코드",
                "product_name" => "상품명",
                "options"      => "옵션",
                "qty"          => "수량",
                "p_qty"        => "상품수",
                "amount"       => "판매금액"
               ,"supply_price" => "정산금액"
               ,"prepay_price" => "선결제택배비"
               ,"org_price"    => "상품원가"
               ,"deliv_price"  => "선불 택배비"
               ,"margin_price" => "마진금"
               ,"margin"       => "마진"
            );
        }
        else
        {
            $arr[] = array(
                "select_date"  => ${date_str},
                "shop_name"    => "판매처",
                "product_code" => "상품코드",
                "product_name" => "상품명",
                "options"      => "옵션",
                "qty"          => "수량",
                "p_qty"        => "상품수",
                "amount"       => "판매금액"
               ,"supply_price" => "정산금액"
               ,"org_price"    => "상품원가"
               ,"deliv_price"  => "선불 택배비"
               ,"margin_price" => "마진금"
               ,"margin"       => "마진"
            );
        }
        $str_supply_code = $this->get_str_supply();
        $supply_code = split(",", $str_supply_code );

        // 화면 표시할 배열
        $total_arr = array();
        $data_arr = array();
        $orders_only_arr = array();
        
        ///**************************************
        // query - order_products 포함
        $orders_only = false;
        $query = $this->get_FP00_query($orders_only);
        $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $data_arr[] = array(
                "a_date"            => $data['a_date']
               ,"a_shop_id"         => $data['a_shop_id']
               ,"b_shop_name"       => $data['b_shop_name']
               ,"a_shop_product_id" => $data['a_shop_product_id']
               ,"a_product_name"    => $data['a_product_name']
               ,"a_options"         => $data['a_options']
               ,"sum_c_qty"         => $data['sum_c_qty']
               ,"sum_org_price"     => $data['sum_org_price']
               ,"sum_extra_money"   => $data['sum_extra_money']
            );                    

            $total_arr['p_qty'] += $data['sum_c_qty'];
            $total_arr['org_price'] += $data['sum_org_price'];
        }

        ///**************************************
        // query - order_products 제외
        $orders_only = true;
        $query = $this->get_FP00_query($orders_only);
        $query .= " order by b.sort_name, a.product_name, a.shop_product_id, a.options ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ] = array(
                "qty"          => $data['sum_a_qty']
               ,"amount"       => $data['sum_a_amount']
               ,"supply_price" => $data['sum_a_supply_price']
               ,"prepay_price" => $data['sum_a_prepay_price']
            );                    

            $total_arr['qty'] += $data['sum_a_qty'];
            $total_arr['amount'] += $data['sum_a_amount'];
            $total_arr['supply_price'] += $data['sum_a_supply_price'];
            $total_arr['prepay_price'] += $data['sum_a_prepay_price'];
        }

        // orders
        $trans_price_arr = $this->get_FP00_trans_price($total_arr);

        $sum_qty          += $qty;
        $sum_p_qty        += $p_qty;
        $sum_amount       += $amount;
        $sum_supply_price += $supply_price;
        $sum_prepay_price += $prepay_price;
        $sum_org_price    += $org_price;
        $sum_deliv_price  += $deliv_price;
        $sum_margin_price += $margin_price;

        $old_shop_name = "";
        $old_shop_product_id = "";
        $old_product_name = "";
        
		$date_arr = array();
        $i = 1;

        foreach ( $data_arr as $data )
        {
            // 수량
            $qty = $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ]['qty'];
            // 상품수
            $p_qty = $data['sum_c_qty'];
            // 판매금액
            $amount = $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ]['amount'] + $data['sum_extra_money'];
            // 정산금액
            $supply_price = $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ]['supply_price'] + $data['sum_extra_money'];
            // 선결제택배비
            $prepay_price = $orders_only_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ]['prepay_price'];
            // 원가
            $org_price   = $data['sum_org_price'];
            // 선불 택배비
            $deliv_price = round( $trans_price_arr[ $data['a_shop_id'] ][ $data['a_shop_product_id'] ][ $data['a_options'] ] );

            // 마진
            $margin_price = $supply_price - $org_price - $deliv_price;
            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                $margin_price += $prepay_price;
            if( $margin_price > 0 )
                $margin = sprintf( "%.2f", $margin_price/($supply_price - $deliv_price) );
            else
                $margin = 0;

            if( $include_sum )
            {
                // 상품코드 같으면 더한다.
                if( $old_shop_name == $data[b_shop_name] && $old_shop_product_id == $data[a_shop_product_id] )
                {
                    $sum_qty          += $qty;
                    $sum_p_qty        += $p_qty;
                    $sum_amount       += $amount;
                    $sum_supply_price += $supply_price;
                    $sum_prepay_price += $prepay_price;
                    $sum_org_price    += $org_price;
                    $sum_deliv_price  += $deliv_price;
                    $sum_margin_price += $margin_price;
                }
                else
                {
                    // 처음이 아니면
                    if( $old_shop_product_id != "" )
                    {
                        if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                            $sum_margin = sprintf("%.2f",($sum_supply_price + $sum_prepay_price - $sum_org_price - $sum_deliv_price )/($sum_supply_price - $sum_deliv_price) );
                        else
                            $sum_margin = sprintf("%.2f",($sum_supply_price - $sum_org_price - $sum_deliv_price )/($sum_supply_price - $sum_deliv_price) );
                        
						//----------------------------------
						//-- 기간 보여주기 처리 (for alice)
						sort($date_arr);
						$res_arr = array_unique($date_arr);
						if (sizeof($res_arr) == 1) {
							$select_date = $res_arr[0];
						} else {
							foreach ($res_arr as $key=>$value) {
								$last_date = $value;
							}
							$select_date = $res_arr[0] . "~" . $last_date;
						}
						//----------------------------------

                        if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                        {
                            $arr[] = array( 
                                "select_date"  => $select_date,
                                "shop_name"    => $old_shop_name,
                                "product_code" => $old_shop_product_id,
                                "product_name" => $old_product_name,
                                "options"      => "합 계",
                                "qty"          => number_format($sum_qty),
                                "p_qty"        => number_format($sum_p_qty),
                                "amount"       => number_format($sum_amount),
                                "supply_price" => number_format($sum_supply_price),
                                "prepay_price" => number_format($sum_prepay_price),
                                "org_price"    => number_format($sum_org_price),    
                                "deliv_pack"   => number_format($sum_deliv_price),
                                "margin_price" => number_format($sum_margin_price),                        
                                "margin"       => $sum_margin * 100 . "%",
                                "is_sum"       => 1
                            );
                        }
                        else
                        {
                            $arr[] = array( 
                                "select_date"  => $select_date,
                                "shop_name"    => $old_shop_name,
                                "product_code" => $old_shop_product_id,
                                "product_name" => $old_product_name,
                                "options"      => "합 계",
                                "qty"          => number_format($sum_qty),
                                "p_qty"        => number_format($sum_p_qty),
                                "amount"       => number_format($sum_amount),
                                "supply_price" => number_format($sum_supply_price),
                                "org_price"    => number_format($sum_org_price),    
                                "deliv_pack"   => number_format($sum_deliv_price),
                                "margin_price" => number_format($sum_margin_price),                        
                                "margin"       => $sum_margin * 100 . "%",
                                "is_sum"       => 1
                            );
                        }
						$date_arr = array();
                    }
            
                    $old_shop_name       = $data[b_shop_name];
                    $old_shop_product_id = $data[a_shop_product_id];
                    $old_product_name    = $data[a_product_name];
                    
                    // sum 새로 추가
                    $sum_qty          = $qty;
                    $sum_p_qty        = $p_qty;
                    $sum_amount       = $amount;                    
                    $sum_deliv_price  = $deliv_price;
                    $sum_supply_price = $supply_price;
                    $sum_prepay_price = $prepay_price;
                    $sum_org_price    = $org_price;
                    $sum_margin_price = $margin_price;
                }
            }

            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
            {
                $arr[] = array( 
                    "select_date"  => $data['a_date'],
                    "shop_name"    => $data['b_shop_name'],
                    "product_code" => $data['a_shop_product_id'],
                    "product_name" => $data['a_product_name'],
                    "options"      => $data['a_options'],
                    "qty"          => number_format($qty),
                    "p_qty"        => number_format($p_qty),
                    "amount"       => number_format($amount),
                    "supply_price" => number_format($supply_price),
                    "prepay_price" => number_format($prepay_price),
                    "org_price"    => number_format($org_price),
                    "deliv_price"  => number_format($deliv_price),
                    "margin_price" => number_format($margin_price),
                    "margin"       => $margin * 100 . "%",
                    "is_sum"       => 0
                );
            }
            else
            {
                $arr[] = array( 
                    "select_date"  => $data['a_date'],
                    "shop_name"    => $data['b_shop_name'],
                    "product_code" => $data['a_shop_product_id'],
                    "product_name" => $data['a_product_name'],
                    "options"      => $data['a_options'],
                    "qty"          => number_format($qty),
                    "p_qty"        => number_format($p_qty),
                    "amount"       => number_format($amount),
                    "supply_price" => number_format($supply_price),
                    "org_price"    => number_format($org_price),
                    "deliv_price"  => number_format($deliv_price),
                    "margin_price" => number_format($margin_price),
                    "margin"       => $margin * 100 . "%",
                    "is_sum"       => 0
                );
            }
            
			$date_arr[] = $data['a_date'];
			$select_date = $data['a_date'];

            if( $i % 73 == 0 )
                $this->show_txt( $i++ );
        }

        // 마지막 합계
        if( $include_sum )
        {   
            $sum_margin = sprintf("%.2f",($sum_supply_price - $sum_org_price - $sum_deliv_price )/$sum_supply_price );
            
			//----------------------------------
			//-- 기간 보여주기 처리 (for alice)
			sort($date_arr);
			$res_arr = array_unique($date_arr);
			if (sizeof($res_arr) == 1) {
				$select_date = $res_arr[0];
			} else {
				foreach ($res_arr as $key=>$value) {
					$last_date = $value;
				}
				$select_date = $res_arr[0] . "~" . $last_date;
			}
			//----------------------------------

            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
            {
                $arr[] = array( 
                    "select_date"  => $select_date,
                    "shop_name"    => $old_shop_name,
                    "product_code" => $old_shop_product_id,
                    "product_name" => $old_product_name,
                    "options"      => "합 계",
                    "qty"          => number_format($sum_qty),
                    "p_qty"        => number_format($sum_p_qty),
                    "amount"       => number_format($sum_amount),
                    "supply_price" => number_format($sum_supply_price),
                    "prepay_price" => number_format($sum_prepay_price),
                    "org_price"    => number_format($sum_org_price),
                    "deliv_pack"   => number_format($sum_deliv_price),   
                    "margin_price" => number_format($sum_margin_price),
                    "margin"       => $margin * 100 . "%",
                    "is_sum"       => 1
                );
            }
            else
            {
                $arr[] = array( 
                    "select_date"  => $select_date,
                    "shop_name"    => $old_shop_name,
                    "product_code" => $old_shop_product_id,
                    "product_name" => $old_product_name,
                    "options"      => "합 계",
                    "qty"          => number_format($sum_qty),
                    "p_qty"        => number_format($sum_p_qty),
                    "amount"       => number_format($sum_amount),
                    "supply_price" => number_format($sum_supply_price),
                    "org_price"    => number_format($sum_org_price),
                    "deliv_pack"   => number_format($sum_deliv_price),   
                    "margin_price" => number_format($sum_margin_price),
                    "margin"       => $margin * 100 . "%",
                    "is_sum"       => 1
                );
            }
        }

        $fn = $this->make_file( $arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    function make_file( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_sale_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\"\#\,\#\#0_ \;\[Red\]\\-\#\,\#\#0\\ \" ;
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
br
	{mso-data-placement:same-cell;}
</style>
<body>
<html><table border=1>
";

        fwrite($handle, $buffer);
 
        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            if( $row[is_sum] )
                $sum_row = "bgcolor=#DDDDDD";
            else
                $sum_row = "";
                
            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value ) 
                {
                    if( $key == 'is_sum' )  continue;
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
                }
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {
                    if( $key == 'is_sum' )  
                        continue;
                    else if( $key == 'qty' || $key == 'p_qty' || $key == 'amount' || $key == 'supply_price' || $key == 'prepay_price' || $key == 'org_price' || $key == 'deliv_price' || $key == 'deliv_pack' || $key == 'margin_price' )
                        $buffer .= "<td class=num_item $sum_row>" . $value . "</td>";
                    else
                        $buffer .= "<td class=str_item $sum_row>" . $value . "</td>";
                }
            }
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);
        
        return $filename;
    }

    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "sale_data.xls");
    }    

}

?>
