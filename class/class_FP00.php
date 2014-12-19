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
        global $query_type,$start_date, $end_date,$order_status, $order_cs_sel, $shop_id,$str_shop_id, $include_sum, $except_gift, $except_trans_cancel;
        
        // 페이지
        $line_per_page = 50;

        $str_supply_code = $this->get_str_supply();
        $supply_code = split(",", $str_supply_code );
        
        $str_shop_id = "";    
        foreach ( $shop_id as $_id )
        {
            $str_shop_id .= $str_shop_id ? "," : "";
            $str_shop_id .=  $_id;
        }

        if( $page === "0" )
        {
            $page = 1;
            $this->show_wait();

            // 화면 표시할 배열
            $data_arr = array();
            $orders_only_arr = array();
            $trans_price_arr = array();

            // 기간설정
            $org_start_date = $start_date;
            $org_end_date = $end_date;
            
            // 조회기간
            $query = "select datediff('$end_date','$start_date') dd";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $date_diff = $data['dd'] + 1;
            
            // 판매처 정보
            $shop_info = array();
            $query = "select shop_id, shop_name, sort_name from shopinfo";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $shop_info[$data[shop_id]] = array(
                    "shop_name" => $data['shop_name']
                   ,"sort_name" => $data['sort_name']
               );
            }
            
            // 기본 택배비
            $base_trans_price = $this->get_base_trans_price();

            // 합포주문의 배송비 계산을 위한 합포수량 배열
            $pack_qty = array();
            $query = "select a.pack a_pack
                        from orders a,
                             shopinfo b ";
            $orders_only = true;
            $query .= $this->get_FP00_where($orders_only) . " and a.trans_who='선불' and a.pack>0 group by a.pack";
            $result = mysql_query($query, $connect);
            
            $total_rows = mysql_num_rows($result);
            
            $i = 0;
            $start_time = time();
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_pack = "select sum(qty) sum_qty
                                 from orders
                                where pack = $data[a_pack]
                                  and order_cs not in (1,3)";
                $result_pack = mysql_query($query_pack, $connect);
                $data_pack = mysql_fetch_assoc($result_pack);
                
                $pack_qty[$data['a_pack']] = $data_pack['sum_qty'];

                $i++;
                $new_time = time();
                if( $new_time - $start_time > 0 )
                {
                    $start_time = $new_time;
                    echo str_pad(" " , 256); 
                    echo "<script type='text/javascript'>show_txt( '합포수량계산 : $i / $total_rows' )</script>";
                    flush();
                    usleep(10000);
                }
            }

            // 테이블 초기화
            $query = "truncate table FP00_result";
            mysql_query($query, $connect);
            
            // 전체 기간을 하루씩 쪼개서 조회
            for( $i=0; $i < $date_diff; $i++)
            {
                $this->show_txt( "기간 : " . ($i+1) . " / $date_diff" );

                $start_date = date("Y-m-d", strtotime("$i day", strtotime("$org_start_date")));
                $end_date = $start_date;
                
                ///**************************************
                // query - order_products 포함
                $orders_only = false;
                $query = $this->get_FP00_query($orders_only);
                $result = mysql_query($query, $connect);

                // total
                while( $data = mysql_fetch_assoc($result) )
                {
                    $_shop_id = $data['a_shop_id'];
                    $_product_id = $data['a_shop_product_id'];
                    $_product_name = $data['a_product_name'];
                    $_options = $data['a_options'];

                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['orders_date']  = $data['a_date'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['product_qty'] += $data['sum_c_qty'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['org_price']   += $data['sum_org_price'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['extra_money'] += $data['sum_extra_money'];
                }

                ///**************************************
                // query - order_products 제외
                $trans_price_arr = $this->get_FP00_trans_price($pack_qty, $base_trans_price);

                $orders_only = true;
                $query = $this->get_FP00_query($orders_only);
                $result = mysql_query($query, $connect);

                // total
                while( $data = mysql_fetch_assoc($result) )
                {
                    $_shop_id = $data['a_shop_id'];
                    $_product_id = $data['a_shop_product_id'];
                    $_product_name = $data['a_product_name'];
                    $_options = $data['a_options'];

                    // 배송비
                    $trans_price = $trans_price_arr[$_shop_id][$_product_id][$_product_name][$_options];

                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['order_qty']    += $data['sum_a_qty'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['amount']       += $data['sum_a_amount'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['supply_price'] += $data['sum_a_supply_price'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['prepay_price'] += $data['sum_a_prepay_price'];
                    $data_arr[$_shop_id][$_product_id][$_product_name][$_options]['trans_price']  += $trans_price;
                }
            }

            // 총수량
            foreach($data_arr as $shop_key => $shop_val)
                foreach($shop_val as $pid_key => $pid_val)
                    foreach($pid_val as $name_key => $name_val)
                        $total_arr_cnt += count($name_val);

            $start_time = time();
            foreach($data_arr as $shop_key => $shop_val)
            {
                foreach($shop_val as $pid_key => $pid_val)
                {
                    foreach($pid_val as $name_key => $name_val)
                    {
                        foreach($name_val as $opt_key => $opt_val)
                        {
                            $query_ex = "insert FP00_result
                                            set orders_date     = '$opt_val[orders_date]'
                                               ,shop_id         = $shop_key
                                               ,shop_name       = '". $shop_info[$shop_key]['shop_name'] ."'
                                               ,sort_name       = '". $shop_info[$shop_key]['sort_name'] ."'
                                               ,shop_product_id = '$pid_key'
                                               ,product_name    = '$name_key'
                                               ,options         = '$opt_key'
                                               ,order_qty       = $opt_val[order_qty]
                                               ,product_qty     = $opt_val[product_qty]
                                               ,amount          = $opt_val[amount]
                                               ,supply_price    = $opt_val[supply_price]
                                               ,prepay_price    = $opt_val[prepay_price]
                                               ,org_price       = $opt_val[org_price]
                                               ,extra_money     = $opt_val[extra_money]
                                               ,trans_price     = $opt_val[trans_price]";
                            mysql_query($query_ex, $connect);

                            $i++;
                            $new_time = time();
                            if( $new_time - $start_time > 0 )
                            {
                                $start_time = $new_time;
                                echo str_pad(" " , 256); 
                                echo "<script type='text/javascript'>show_txt( '작업중 : $i / $total_arr_cnt' )</script>";
                                flush();
                                usleep(10000);
                            }
                        }
                    }
                }
            }
            
            $query = "select sum(order_qty)    sum_order_qty
                            ,sum(product_qty)  sum_product_qty
                            ,sum(amount)       sum_amount
                            ,sum(supply_price) sum_supply_price
                            ,sum(prepay_price) sum_prepay_price
                            ,sum(extra_money)  sum_extra_money
                            ,sum(org_price)    sum_org_price
                            ,sum(trans_price)  sum_trans_price
                            ,count(*) cnt
                        from FP00_result";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $total_qty              = $data[sum_order_qty];
            $total_p_qty            = $data[sum_product_qty];
            $total_amount           = $data[sum_amount];
            $total_supply_price     = $data[sum_supply_price];
            $total_extra_money      = $data[sum_extra_money];
            $total_org_price        = $data[sum_org_price];
            $total_prepay_price     = $data[sum_prepay_price];
            $total_trans_price      = $data[sum_trans_price];

            $total_rows = $data['cnt'];
            
            $query = "select * from FP00_result order by sort_name, product_name, shop_product_id, options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
            $result = mysql_query($query, $connect);
            
            $this->hide_wait();

            // 화면 출력
            $start_date = $org_start_date;
            $end_date = $org_end_date;
        }
        else if( $page >= 1 )
        {
            $query = "select sum(order_qty)    sum_order_qty
                            ,sum(product_qty)  sum_product_qty
                            ,sum(amount)       sum_amount
                            ,sum(supply_price) sum_supply_price
                            ,sum(prepay_price) sum_prepay_price
                            ,sum(extra_money)  sum_extra_money
                            ,sum(org_price)    sum_org_price
                            ,sum(trans_price)  sum_trans_price
                            ,count(*) cnt
                        from FP00_result";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $total_qty              = $data[sum_order_qty];
            $total_p_qty            = $data[sum_product_qty];
            $total_amount           = $data[sum_amount];
            $total_supply_price     = $data[sum_supply_price];
            $total_extra_money      = $data[sum_extra_money];
            $total_org_price        = $data[sum_org_price];
            $total_prepay_price     = $data[sum_prepay_price];
            $total_trans_price      = $data[sum_trans_price];

            $total_rows = $data['cnt'];

            $query = "select * from FP00_result order by sort_name, product_name, shop_product_id, options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
            $result = mysql_query($query, $connect);
        }
        else
        {
            $page = 0;
            
            $start_date = date("Y-m-d");
            $end_date = $start_date;
        }

        // link url
        $par = array('template','product_code','product_name','options','str_supply_code','s_group_id','query_type','start_date','end_date','order_status','order_cs_sel','shop_id','include_sum','except_gift','except_trans_cancel');
        $link_url = $this->build_link_url3( $par );
        $link_url .= "supply_code=$str_supply_code";

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_FP00_query($orders_only)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$order_cs_sel,$shop_id,$str_shop_id, $include_sum, $except_gift, $except_trans_cancel;
        
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
                            ,a.product_name      a_product_name
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
        $query = $query . $where . " group by a.shop_id, a.shop_product_id, a.product_name, a.options";
        return $query;
    }
    
    function get_FP00_where($orders_only=0)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$order_cs_sel,$shop_id,$str_shop_id, $include_sum, $except_gift, $except_trans_cancel;
        
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
                          and a.order_cs<>1 
                          and c.order_cs not in (1,2) ";
                          
           if($except_gift)
            $where .= "and c.is_gift = 0 ";
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

        if( $except_trans_cancel )
            $where .= " and a.order_cs<> 3 ";

        // 상태
        if ( $order_status == 1 )
            $status = 1;
        else if ( $order_status == 7 )
            $status = 7;
        else if ( $order_status == 8 )
            $status = 8;
        else if ( $order_status == 99 )
            $status = "1,7";
		
        switch( $order_cs_sel )
        {
            case 1: $where .= " AND a.order_cs IN ( 0 )"; break;
            case 2: $where .= " AND a.order_cs IN ( 1,2 )"; break;
            case 3: $where .= " AND a.order_cs IN ( 3,4 )"; break;
            case 4: $where .= " AND a.order_cs IN ( 5,6 )"; break;
            case 5: $where .= " AND a.order_cs IN ( 7,8 )"; break;
            case 6: $where .= " AND a.order_cs IN ( 1,2,3,4 )"; break;
            case 7: $where .= " AND a.order_cs IN ( 5,6,7,8 )"; break;            
        }

        if ( $status )
            $where .= " and a.status in ( $status )";
        else
            $where .= " and a.status > 0";

        // 판매처
        if ( $str_shop_id )
            $where .= " and a.shop_id IN ($str_shop_id)";

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
    
    function get_FP00_trans_price($pack_qty, $base_trans_price=0)
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $product_code, $product_name, $options;
        global $supply_code, $str_supply_code, $s_group_id;
        global $query_type,$start_date, $end_date,$order_status,$order_cs_sel,$shop_id,$str_shop_id, $include_sum, $except_gift, $except_trans_cancel;

        $trans_price_arr = array();
        
        // query orders
        $query = "select a.pack              a_pack
                        ,a.shop_id           a_shop_id
                        ,a.shop_product_id   a_shop_product_id
                        ,a.product_name      a_product_name
                        ,a.options           a_options
                        ,a.qty               a_qty
                    from orders a
                        ,shopinfo b ";
        $orders_only = true;
        $query .= $this->get_FP00_where($orders_only) . " and a.trans_who='선불'";
        
debug("FP00 ".$query);
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 선불택배비
            if( $data[a_pack] > 0 && $pack_qty[$data['a_pack']] > 0 )
                $trans_price = $base_trans_price * ( $data['a_qty'] / $pack_qty[$data['a_pack']] );
            else
                $trans_price = $base_trans_price;

            $trans_price_arr[$data['a_shop_id']][$data['a_shop_product_id']][$data['a_product_name']][$data['a_options']] += round($trans_price);
        }
        return $trans_price_arr;
    }

    function FP01()
    {
        global $template, $connect, $shop_id,$str_shop_id,$product_id,$product_name,$options,$start_date,$end_date,$query_type, $except_gift, $except_trans_cancel;
        
        $product_id = urldecode(base64_decode( $product_id ));        
        $product_name = urldecode(base64_decode( $product_name ));
        $options = urldecode(base64_decode( $options ));        


        if ( $query_type == "trans_date_pos" )
        {
            $start_date = $start_date . " 00:00:00";
            $end_date   = $end_date   . " 23:59:59";
        }
        
        $query = "select a.order_id,a.collect_date,a.recv_name,b.qty, b.org_price, b.order_seq, b.product_id
                    from orders a, order_products b
                   where a.seq             =  b.order_seq
                     and a.$query_type    >= '$start_date'
                     and a.$query_type    <= '$end_date'
                     and a.shop_id         IN  ($shop_id)
                     and a.shop_product_id =  '$product_id'
                     and a.product_name    =  '$product_name'
                     and a.options         =  '$options'";
		if($except_gift)
			$query .=" and b.is_gift = 0";

		if($except_trans_cancel)
			$query .=" and a.order_cs <> 3 ";

        
debug("FP01 : " . $query);
        
        $arr_result = array();
        $result     = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) ) 
        {
            $_arr = $this->get_product_info( $data[product_id] );
            
            $arr_result[] = array(
                order_id      => $data[order_id]
                ,collect_date => $data[collect_date]
                ,order_seq    => $data[order_seq]
                ,recv_name    => $data[recv_name]
                ,product_id   => $data[product_id]
                ,name         => $_arr[name]
                ,options      => $_arr[options]
                ,org_price    => ( _DOMAIN_ == 'mkh2009' ? $data[org_price] : $_arr[org_price] * $data[qty] )
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
        global $query_type,$start_date, $end_date,$order_status,$order_cs_sel,$shop_id,$str_shop_id, $include_sum, $except_gift, $except_trans_cancel;
        
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

        $temp_arr[select_date]  = ${date_str};
        $temp_arr[shop_name]    = "판매처";
        if( _DOMAIN_ == 'jbstar' )
        {
        	$temp_arr[shop_id] = "판매처코드";
        }
        	
        $temp_arr[product_code] = "상품코드";
        $temp_arr[product_name] = "상품명";
        $temp_arr[options]      = "옵션";

        if( _DOMAIN_ == 'jbstar' )
        {	
        	$temp_arr[product_id] = "어드민상품코드";
            $temp_arr[pname] = "어드민상품명";
        }

        $temp_arr[qty]          = "수량";
        $temp_arr[p_qty]        = "상품수";
        $temp_arr[amount]       = "판매금액";
        $temp_arr[supply_price] = "정산금액";

        if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
            $temp_arr[prepay_price] = "선결제택배비";

        $temp_arr[org_price]    = "상품원가";
        $temp_arr[deliv_price]  = "선불 택배비";
        $temp_arr[margin_price] = "마진금";
        $temp_arr[margin]       = "마진";

        $arr[] = $temp_arr;

        $str_supply_code = $this->get_str_supply();
        $supply_code = split(",", $str_supply_code );

        $i = 1;

        $old_shop_name = "";
        $old_shop_product_id = "";
        $old_product_name = "";

        $query = "select * from FP00_result order by sort_name, product_name, shop_product_id, options ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 수량
            $qty = $data['order_qty'];
            // 상품수
            $p_qty = $data['product_qty'];
            // 판매금액
            $amount = $data['amount'] + $data['extra_money'];
            // 정산금액
            $supply_price = $data['supply_price'] + $data['extra_money'];
            // 선결제택배비
            $prepay_price = $data['prepay_price'];
            // 원가
            $org_price   = $data['org_price'];
            // 선불 택배비
            $deliv_price = $data['trans_price'];

            // 마진
            $margin_price = $supply_price - $org_price - $deliv_price;
            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                $margin_price += $prepay_price;
            if( $margin_price > 0 )
            {
                $margin = sprintf( "%.2f", $margin_price/($supply_price - $deliv_price) );
                if(_DOMAIN_ == "gamsung")//마진율 재계산
                	$margin = sprintf( "%.2f", $margin_price/$amount );
            }
            else
                $margin = 0;

            if( $include_sum )
            {
                // 상품코드 같으면 더한다.
                if( $old_shop_name == $data[shop_name] && $old_shop_product_id == $data[shop_product_id] )
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

                        $temp_arr = array();
                        
                        $temp_arr["select_date"]  = $select_date;
                        $temp_arr["shop_name"]    = $old_shop_name;
                        $temp_arr["product_code"] = $old_shop_product_id;
                        $temp_arr["product_name"] = $old_product_name;
                        $temp_arr["options"]      = "합 계";

                        if( _DOMAIN_ == 'jbstar' )
                            $temp_arr["pname"] = "";

                        $temp_arr["qty"]          = number_format($sum_qty);
                        $temp_arr["p_qty"]        = number_format($sum_p_qty);
                        $temp_arr["amount"]       = number_format($sum_amount);
                        $temp_arr["supply_price"] = number_format($sum_supply_price);

                        if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                            $temp_arr["prepay_price"] = number_format($sum_prepay_price);

                        $temp_arr["org_price"]    = number_format($sum_org_price);
                        $temp_arr["deliv_pack"]   = number_format($sum_deliv_price);
                        $temp_arr["margin_price"] = number_format($sum_margin_price);
                        $temp_arr["margin"]       = $sum_margin * 100 . "%";
                        $temp_arr["is_sum"]       = 1;

                        $arr[] = $temp_arr;

						$date_arr = array();
                    }
            
                    $old_shop_name       = $data[shop_name];
                    $old_shop_product_id = $data[shop_product_id];
                    $old_product_name    = $data[product_name];
                    
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

            $temp_arr = array();
            
            $temp_arr["select_date"]  = $data['orders_date'];
            $temp_arr["shop_name"]    = $data['shop_name'];
            if( _DOMAIN_ == 'jbstar' )
            {
            	$temp_arr["shop_id"] =  $data['shop_id'];
            }
            $temp_arr["product_code"] = $data['shop_product_id'];
            $temp_arr["product_name"] = $data['product_name'];
            $temp_arr["options"]      = $data['options'];

            if( _DOMAIN_ == 'jbstar' )
            {
                global $connect, $query_type, $start_date, $end_date;
                
                $query_pname = "select distinct c.name c_name
                 						   	  , b.product_id
 		                                   from orders a
 		                                       ,order_products b
 		                                       ,products c
 		                                  where a.seq = b.order_seq
 		                                    and b.product_id = c.product_id
 		                                    and a.shop_id = $data[shop_id]
 		                                    and b.shop_product_id = '" . addslashes($data[shop_product_id]) . "'
 		                                    and a.options = '" . addslashes($data[options]) . "' ";
    
                // 날짜
        		switch ($query_type) {
        			case "collect_date" :
        				$query_pname .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        				break;
        			case "trans_date_pos" :
        				$query_pname .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        				break;
        			case "order_date" :
        				$query_pname .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";
        				break;
        		}
    			$query_pname .= " order by b.seq ";
    debug("jbstar 테스트 : " . $query_pname);
        		$result_pname = mysql_query($query_pname, $connect);
    
        		$temp_str = "<table border=1 cellpadding=0 cellspacing=0 width='100%' height='100%'>";
        		while( $data_pname = mysql_fetch_assoc($result_pname) )
        		{
        		    $temp_str .= "<tr>";
        		    $temp_str .= "<td class=str_item>".$data_pname[product_id]."</td>";
        		    $temp_str .= "</tr>";
        		}
        		$temp_str .= "</table>";
        		$temp_arr["product_id"] = $temp_str;
        		
        		
        		$result_pname = mysql_query($query_pname, $connect);
        		$temp_str = "<table border=1 cellpadding=0 cellspacing=0 width='100%' height='100%'>";
        		while( $data_pname = mysql_fetch_assoc($result_pname) )
        		{
        		    $temp_str .= "<tr>";
        		    $temp_str .= "<td class=str_item>".$data_pname[c_name]."</td>";        		    
        		    $temp_str .= "</tr>";
        		}
        		$temp_str .= "</table>";
				
                $temp_arr["pname"] = $temp_str;
                
            }

            $temp_arr["qty"]          = number_format($qty);
            $temp_arr["p_qty"]        = number_format($p_qty);
            $temp_arr["amount"]       = number_format($amount);
            $temp_arr["supply_price"] = number_format($supply_price);

            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                $temp_arr["prepay_price"] = number_format($prepay_price);

            $temp_arr["org_price"]    = number_format($org_price);
            $temp_arr["deliv_price"]  = number_format($deliv_price);
            $temp_arr["margin_price"] = number_format($margin_price);
            $temp_arr["margin"]       = $margin * 100 . "%";
            $temp_arr["is_sum"]       = 0;

            $arr[] = $temp_arr;

			$date_arr[] = $data['orders_date'];
			$select_date = $data['orders_date'];

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

            $temp_arr = array();

            $temp_arr["select_date"]  = $select_date;
            $temp_arr["shop_name"]    = $old_shop_name;
            $temp_arr["product_code"] = $old_shop_product_id;
            $temp_arr["product_name"] = $old_product_name;
            $temp_arr["options"]      = "합 계";

            if( _DOMAIN_ == 'jbstar' )
                $temp_arr["pname"] = "";

            $temp_arr["qty"]          = number_format($sum_qty);
            $temp_arr["p_qty"]        = number_format($sum_p_qty);
            $temp_arr["amount"]       = number_format($sum_amount);
            $temp_arr["supply_price"] = number_format($sum_supply_price);

            if( !$_SESSION[SUPPLY_PRICE_WITH_TRANS] )
                $temp_arr["prepay_price"] = number_format($sum_prepay_price);

            $temp_arr["org_price"]    = number_format($sum_org_price);
            $temp_arr["deliv_pack"]   = number_format($sum_deliv_price);
            $temp_arr["margin_price"] = number_format($sum_margin_price);
            $temp_arr["margin"]       = $margin * 100 . "%";
            $temp_arr["is_sum"]       = 1;

            $arr[] = $temp_arr;
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
