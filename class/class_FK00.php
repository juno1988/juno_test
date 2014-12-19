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
// class name: class_FK00

class class_FK00 extends class_top {

    function FK01()
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

    ///////////////////////////////////////////
    function FK00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum, $except_gift;
        global $supply_code, $str_supply_code, $s_group_id;
        
        // 페이지
        $line_per_page = 50;

        if( !$page )
            $page = 1;
        else
        {
            $this->show_wait();

            $str_supply_code = $this->get_str_supply();

            // link url
            $par = array('template','shop_id','query_type','start_date','end_date','order_status','product_code','product_name','options','include_sum','except_gift','s_group_id');
            $link_url = $this->build_link_url3( $par );
            $link_url .= "supply_code=$str_supply_code";

            $supply_code = split(",", $str_supply_code );

            // query
            $query = $this->get_FK00_query();
debug("FK00 query : " . $query);            
            // 총합계 구하기에서 사용
            $query_org = $query;

            // 전체 개수
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);
            
            $query .= " order by b.sort_name, a.product_name, a.options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
            $result = mysql_query($query, $connect);
            
            //
            // $arr_trans : 택배비및 정산 관련 추가 데이터
            //  [product_id][option]["qty"]    
            $arr_result = $this->get_extra_stat_price();

            // 전체 합계
            $total_sum = $this->get_FK00_total_sum($arr_result, $query_org);

            $this->hide_wait();
        }
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_FK00_query()
    {    
        global $template, $connect, $page, $line_per_page, $link_url;
        global $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum, $except_gift;
        global $supply_code, $str_supply_code, $s_group_id;
        
        $supply_group_list = "";
        if( $s_group_id )
        {
            $query_group = "select code from userinfo where group_id = $s_group_id";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $supply_group_list .= ($supply_group_list ? "," : "") . $data_group[code];
        }

		switch ($query_type) {
			case "collect_date" :
				$date_str = "a.collect_date as a_date"; 		break;
			case "trans_date_pos" :
				$date_str = "a.trans_date_pos as a_date"; 		break;
			case "order_date" :
				$date_str = "a.order_date as a_date"; 			break;
		}

        // trim
        $product_code = trim( $product_code );
        $product_name = trim( $product_name );
        $options = trim( $options );
        
        // query orders
        $query = "select a.shop_id           a_shop_id, 
                         b.shop_name         b_shop_name,
                         a.shop_product_id   a_shop_product_id,
                         a.product_name      a_product_name,
                         a.options           a_options,
						 ${date_str},
                         sum(a.qty)          sum_a_qty,
                         sum(a.amount)       sum_a_amount, 
                         sum(a.supply_price) sum_a_supply_price
                    from orders a,
                         shopinfo b ";

        $query .= " where a.shop_id = b.shop_id and
                         a.order_cs<>1 ";
        // 복수 공급처 
        if( $str_supply_code || $supply_group_list || $except_gift )
            $query .= " and a.seq in (select order_seq from order_products where order_cs not in (1,2) ";
        if( $str_supply_code )
            $query .= " and supply_id in ($str_supply_code) ";
        if( $supply_group_list )
            $query .= " and supply_id in ($supply_group_list) ";
        if( $except_gift )
            $query .= " and is_gift = 0 ";
        if( $str_supply_code || $supply_group_list || $except_gift )
            $query .= " ) ";

        // 날짜
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $query .= " and a.options like '%$options%'";
            
        // 배송후교환
        $obj = new class_statconfig();
        $arr_config = $obj->get_config();    

        if ( $arr_config[change] == "org")
            $query .= " and a.c_seq = 0 ";
        else
            $query .= " and a.order_cs not in (7,8)";

        $query .= " group by a.shop_id, a.shop_product_id, a.options";
debug("판매처상품매출통계 : " . $query);
        return $query;
    }

    //
    // 상품 가격에 대한 정산 추가..
    //
    function get_extra_stat_price(  )
    {
        global $connect, $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum, $except_gift;
        global $supply_code, $str_supply_code, $s_group_id;
        
        $supply_group_list = "";
        if( $s_group_id )
        {
            $query_group = "select code from userinfo where group_id = $s_group_id";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $supply_group_list .= ($supply_group_list ? "," : "") . $data_group[code];
        }

        $arr_result = array();
        
        // trim
        $product_code = trim( $product_code );
        $product_name = trim( $product_name );
        $options = trim( $options );
        
        if( _DOMAIN_ == 'mkh2009' )
        {
            // query orders
            $query = "select a.shop_id a_shop_id
                             ,a.shop_product_id product_id
                             ,a.options
                             ,b.prd_supply_price
                             ,b.org_price
                             ,b.qty
                             ,if(b.org_price>0, b.org_price, c.org_price * b.qty) org_price_sum
                        from orders a,
                             order_products b,
                             products c
                       where a.seq = b.order_seq
                             and b.product_id = c.product_id
                             and a.order_cs<>1 
                             ";
        }else{
            // query orders
            $query = "select a.shop_id a_shop_id
                             ,a.shop_product_id product_id
                             ,a.options
                             ,b.prd_supply_price
                             ,b.org_price
                             ,b.qty
                             ,c.org_price * b.qty org_price_sum
                        from orders a,
                             order_products b,
                             products c
                       where a.seq = b.order_seq
                             and b.product_id = c.product_id
                             and a.order_cs<>1 
                             ";
        }

        // 날짜
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

        // 복수 공급처 
        if( $str_supply_code || $supply_group_list || $except_gift )
            $query .= " and a.seq in (select order_seq from order_products where order_cs not in (1,2) ";
        if( $str_supply_code )
            $query .= " and supply_id in ($str_supply_code) ";
        if( $supply_group_list )
            $query .= " and supply_id in ($supply_group_list) ";
        if( $except_gift )
            $query .= " and is_gift = 0 ";
        if( $str_supply_code || $supply_group_list || $except_gift )
            $query .= " ) ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $query .= " and a.options like '%$options%'";
            
        // 사은품 제외
        if ( $except_gift )
            $query .= " and b.is_gift=0 ";
            
        // 배송후교환
        $obj = new class_statconfig();
        $arr_config = $obj->get_config();    

        if ( $arr_config[change] == "org")
            $query .= " and a.c_seq = 0 ";
        else
            $query .= " and a.order_cs not in (7,8)";
debug("FK00 : " . $query);
        $result = mysql_query( $query, $connect );
        
        //
        // part1
        // 상품별 옵션별 정산 금액과 원가를 계산한다.
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_id    = trim( $data[a_shop_id] );
            $product_id = trim( $data[product_id] );
            $options    = trim( $data[options] );
             
            $arr_result[$shop_id.$product_id][$options][supply_price] += $data[prd_supply_price];
            $arr_result[$shop_id.$product_id][$options][org_price]    += $data[org_price_sum];
            $arr_result[$shop_id.$product_id][$options][qty]          += $data[qty];
        }
        
        //
        // step2. 택배 비용 계산
        // 
        $per_deliv_price = 0;
        
        // trans_no에 전체 배송 상품이 몇개?
        $arr_info = $this->calc_trans_price_deliv();

        // trans_no에 해당 상품이 몇개 인가?
        $this->calc_trans_price_product( $arr_info, &$arr_result );
        
        
        return $arr_result;
    }
    
    //
    // 택배비 계산
    // 기준은 합포 번호.
    //
    function calc_trans_price(&$arr_result)
    {
        // trans_no에 전체 배송 상품이 몇개?
        $arr_info = $this->calc_trans_price_deliv();

        // trans_no에 해당 상품이 몇개 인가?
        // sample
        // select a.shop_product_id,a.trans_no,b.qty from orders a, order_products b where a.seq=b.order_seq and a.shop_product_id='150293146' group by shop_product_id,options,trans_no;
        $this->calc_trans_price_product( $arr_info, &$arr_result );
        
    }
    
    function get_base_trans_price()
    {
        global $connect;
        
        $query = "select value from stat_config where code='usertrans_price'";   
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[value];
    }
    //
    // 상품 옵션별 택배비 계산
    // arr_info에는 합포 번호별 전체 상품 수가 들어가 있다.
    //
    function calc_trans_price_product( $arr_info, &$arr_result )
    {
        global $connect, $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum;
        
        // 기본 택배비는 2000원으로 설정 - test
        $base_trans_price = $this->get_base_trans_price();
        
        $query = "select a.shop_id a_shop_id
                        ,a.shop_product_id product_id
                        ,a.options
                        ,b.qty
                        ,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                    from orders a
                        ,order_products b
                   where a.seq       = b.order_seq
                     and a.order_cs<>1
                     and a.trans_who = '선불' ";
        
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $query .= " and a.options like '%$options%'";

        // 배송후교환
        $obj = new class_statconfig();
        $arr_config = $obj->get_config();    

        if ( $arr_config[change] == "org")
            $query .= " and a.c_seq = 0 ";
        else
            $query .= " and a.order_cs not in (7,8)";

        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $_deliv_price = round($data[qty] / $arr_info[ $data[xx] ] * $base_trans_price);
            $arr_result[$data[a_shop_id].$data[product_id]][$data[options]]['deliv_price'] += $_deliv_price;   
        }
        

    }
    
    //
    // 송장 별 총 배송 상품이 몇개?
    //
    function calc_trans_price_deliv()
    {
        global $connect, $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum;
        
        $query = "select sum(b.qty) s_qty, if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                    from orders a, order_products b
                   where a.seq       = b.order_seq
                     and a.trans_who = '선불' ";
        
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        //if ( $product_code )
        //    $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        //if ( $product_name )
        //    $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        //if ( $options )
        //    $query .= " and a.options like '%$options%'";
        
        $query .= " group by xx";
        
        //echo $query;
        
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[ $data[xx] ] = $data[s_qty];
        }

        return $arr_result;
    }
    
    //
    // 선불 개수
    //
    function get_total_predeliv_cnt( &$per_deliv_price )
    {
        global $connect, $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum;
        
        //
        // 배송비 가중치를 구한다.
        // 전체 선불 개수에서 전체 상품 수를 나눈다.
        // 2012-1-31
        $query = "select count(distinct trans_no) cnt from orders a where trans_who='선불' ";
        
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $query .= " and a.options like '%$options%'";

        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $total_deliv = $data[cnt];
        
        //
        // 상품별 선불 개수 확인
        //
        $query = "select a.shop_product_id, a.options, sum(b.qty) s_qty
                    from orders a, order_products b 
                   where a.seq = b.order_seq 
                     and a.trans_who='선불' ";
        
        if( $query_type == "collect_date" )
            $query .= " and a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date 00:00:00' and a.trans_date_pos <= '$end_date 23:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status in ( $status )";
        else
            $query .= " and a.status > 0";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";

        // 상품코드
        if ( $product_code )
            $query .= " and a.shop_product_id like '%$product_code%'";
        
        // 상품명
        if ( $product_name )
            $query .= " and a.product_name like '%$product_name%'";
        
        // 옵션
        if ( $options )
            $query .= " and a.options like '%$options%'";
        
        // 사은품 제외
        if ( $except_gift )
            $query .= " and b.is_gift=0 ";
        
        $query .= " group by a.shop_id, a.shop_product_id, a.options";
        //echo $query;
        
        $result = mysql_query( $query, $connect );
        $tot = 0;
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $product_id = trim( $data[shop_product_id] );
            $options    = trim( $data[options] );
            $arr_result[$product_id][$options][qty] = $data[s_qty];
            $tot        += $data[s_qty];
        }      
        
        echo "tot: $tot : tot_deliv: $total_deliv <br>";
        
        $per_deliv_price = $total_deliv * 2000 / $tot;
        
        // print_r ( $arr_result );
        return $arr_result;
    }
    
    //
    // item추가..
    // $arr_item[ $product_id][$options][supply_price]
    // $arr_item[ $product_id][$options][org_price]
    function add_item( $data, &$arr_result )
    {
        $shop_id    = trim( $data[a_shop_id] );
        $product_id = trim( $data[product_id] );
        $options    = trim( $data[options] );
         
        $arr_result[$shop_id.$product_id][$options][supply_price] += $data[prd_supply_price];
        $arr_result[$shop_id.$product_id][$options][org_price]    += $data[org_price_sum];
        $arr_result[$shop_id.$product_id][$options][qty]          += $data[qty];
    }
    
    // 정산 값 출력
    function disp_value( $arr_result, $product_id, $options, $code )
    {
        $product_id = trim( $product_id );
        $options    = trim( $options );
        
        return $arr_result[$product_id][$options][$code];
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 옵션매칭
    function save_file()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $query_type,$start_date, $end_date,$order_status,$shop_id,$product_code, $product_name, $options, $include_sum;
        global $supply_code, $str_supply_code, $s_group_id;

        // 정산 관련 계산..
        $arr_result = $this->get_extra_stat_price(); 

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
        $arr[] = array(
            "select_date"  => ${date_str},
            "shop_name"    => "판매처",
            "product_code" => "상품코드",
            "product_name" => "상품명",
            "options"      => "옵션",
            "qty"          => "수량",
            "amount"       => "판매금액"
           ,"supply_price" => "정산금액"
           ,"org_price"    => "상품원가"
           ,"deliv_price"  => "선불 택배비"
           ,"margin_price" => "마진금"
           ,"margin"       => "마진"
            
        );

        $str_supply_code = $this->get_str_supply();
        $supply_code = split(",", $str_supply_code );

        $query = $this->get_FK00_query();
        $result = mysql_query( $query, $connect );

        $sum_qty = 0;
        $sum_amount = 0;
        $old_shop_name = "";
        $old_shop_product_id = "";
        $old_product_name = "";
        
        
		$date_arr = array();
        $i = 1;
        while ( $data = mysql_fetch_array( $result ) )
        {
            // 상품 수량
            $p_qty = $arr_result[$data['a_shop_id'].$data['a_shop_product_id']][$data['a_options']][qty];
            
            // deliv_price : 선불 택배비
            $deliv_price = $arr_result[$data['a_shop_id'].$data['a_shop_product_id']][$data['a_options']]['deliv_price'];
            
            // 정산 금액
            $supply_price = $data['sum_a_supply_price'];
            
            // 원가
            $org_price   = $this->disp_value($arr_result,$data[a_shop_id].$data['a_shop_product_id'],$data['a_options'],"org_price");
            
            // 마진
            $margin = sprintf("%.2f",($supply_price - $org_price - $deliv_price )/$supply_price );
        
            $margin_price = $supply_price - $org_price - $deliv_price;
        
            if( $include_sum )
            {
                // 상품코드 같으면 더한다.
                if( $old_shop_name == $data[b_shop_name] && $old_shop_product_id == $data[a_shop_product_id] )
                {
                    $sum_qty    += $data[sum_a_qty];
                    $sum_amount += $data[sum_a_amount];
                    
                    // 새로 추가
                    $sum_p_qty        += $p_qty;
                    $sum_deliv_price  += $deliv_price;
                    $sum_supply_price += $supply_price;
                    $sum_org_price    += $org_price;
                    $sum_margin_price += $margin_price;
                }
                else
                {
                    // 처음이 아니면
                    if( $old_shop_product_id != "" )
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


                        $arr[] = array( 
                            "select_date"  => $select_date,
                            "shop_name"    => $old_shop_name,
                            "product_code" => $old_shop_product_id,
                            "product_name" => $old_product_name,
                            "options"      => "합 계",
                            "qty"          => number_format($sum_qty),
                            "amount"       => number_format($sum_amount),
                            "supply_price" => number_format($sum_supply_price),
                            "org_price"    => number_format($sum_org_price),    
                            "deliv_pack"   => number_format($sum_deliv_price),
                            "margin_price" => number_format($sum_margin_price),                        
                            "margin"       => $sum_margin * 100 . "%",
                            "is_sum"       => 1
                        );

						$date_arr = array();
                    }
            
                    $sum_qty             = $data[sum_a_qty];
                    $sum_amount          = $data[sum_a_amount];                    
                    $old_shop_name       = $data[b_shop_name];
                    $old_shop_product_id = $data[a_shop_product_id];
                    $old_product_name    = $data[a_product_name];
                    
                    // sum 새로 추가
                    $sum_p_qty        = $p_qty;
                    $sum_deliv_price  = $deliv_price;
                    $sum_supply_price = $supply_price;
                    $sum_org_price    = $org_price;
                    $sum_margin_price = $margin_price;
                }
            }

            $arr[] = array( 
                "select_date"  => $data['a_date'],
                "shop_name"    => $data['b_shop_name'],
                "product_code" => $data['a_shop_product_id'],
                "product_name" => $data['a_product_name'],
                "options"      => $data['a_options'],
                "qty"          => number_format($data['sum_a_qty']),
                "amount"       => number_format($data['sum_a_amount']),
                "supply_price" => number_format($supply_price),
                "org_price"    => number_format($org_price),
                "deliv_price"  => number_format($deliv_price),
                "margin_price" => number_format($margin_price),
                "margin"       => $margin * 100 . "%",
                "is_sum"       => 0
            );

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

            $arr[] = array( 
                "select_date"  => $select_date,
                "shop_name"    => $old_shop_name,
                "product_code" => $old_shop_product_id,
                "product_name" => $old_product_name,
                "options"      => "합 계",
                "qty"          => number_format($sum_qty),
                "amount"       => number_format($sum_amount),
                "supply_price" => number_format($sum_supply_price),
                "org_price"    => number_format($sum_org_price),
                "deliv_pack"   => number_format($sum_deliv_price),   
                "margin_price" => number_format($sum_margin_price),
                "margin"       => $margin * 100 . "%",
                "is_sum"       => 1
            );
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
                    else if( $key == 'qty' || $key == 'amount' )
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


    function F202()
    {
        global $connect, $template;
        global $query_type, $product_id, $start_date, $end_date, $status, $total_qty;

        // 상품정보
        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $product_info = mysql_fetch_assoc($result);
        
        // 공급처
        $supply_name = class_supply::get_name($product_info[supply_code]);
        
        // 수량
        $query = "select sum(b.qty) sum, 
                         c.shop_name
                    from orders a, order_products b, shopinfo c
                   where a.seq = b.order_seq and 
                         b.shop_id = c.shop_id and
                         b.product_id = '$product_id' and
                         a.$query_type >= '$start_date' and
                         a.$query_type <= '$end_date' and
                         b.order_cs not in (1,2) and
                         a.status > 0";
        
        // 상태
        switch( $status )
        {
            case 1: $query .= " a.status = 1 and "; break;
            case 7: $query .= " a.status = 7 and "; break;
            case 8: $query .= " a.status = 8 and "; break;
            case 99: $query .= " a.status in (1,7) and "; break;
        }
        
        $query .= " group by b.shop_id order by c.shop_name";
        $result = mysql_query($query, $connect);
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    function get_data2(&$arr_data, $download=0)
    {
        global $connect, $template;
        global $start_date, $end_date, $supply_id, $query_type, $product_id, $order_status, $shop_id;

        $i = 0;
        if( $download )
        {
            $arr_data[$i++] = array( 
                supply_name   => "공급처"
                ,product_id   => "상품코드"
                ,name         => "상품명"
                ,brand        => "사입처 상품명"
                ,options      => "옵션"
                ,org_price    => "원가"
                ,supply_price => "공급가"
                ,shop_price   => "판매가"
                ,stock        => "현재고"
                ,qty          => "수량"
                ,type         => ""
            );
        }
        
        // 묶음개수 종류 구하기
        $query = $this->get_all_order_seq();
        $result = mysql_query( $query, $connect );
        
        $total_cnt = mysql_num_rows($result);
        $k = 1;
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $download )
            {
                $msg = $k++ . " / " . $total_cnt;
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
            else
                $this->show_txt( $k++ . " / " . $total_cnt );
            
            // 단품
            if( $data[q] == 1 )
            {
                $query_single = $this->get_single_order_seq();
                $result_single = mysql_query( $query_single, $connect );
                $data_single = mysql_fetch_assoc($result_single);

                $arr_product = $this->get_product_info( $data_single[product_id] );
                $arr_data[$i++] = array( 
                    supply_name   => class_supply::get_name($data_single[supply_id])
                    ,product_id   => $data_single[product_id] 
                    ,name         => $arr_product[name]
                    ,brand        => $arr_product[brand]
                    ,options      => $arr_product[options]
                    ,org_price    => $arr_product[org_price]
                    ,supply_price => $arr_product[supply_price]
                    ,shop_price   => $arr_product[shop_price]
                    ,stock        => class_stock::get_current_stock($data_single[product_id])
                    ,qty          => $data_single[total_qty]
                    ,type         => 1
                );
            }

            // 묶음
            else
            {
                $pack_arr = array();
                $temp_arr = array();
                $order_seq = 0;
                
                // 묶음 수가 $data[q] 개인 주문들을 가져온다.
                $query_pack = $this->get_pack_order_seq($data[q]);
                $result_pack = mysql_query($query_pack, $connect);
                while( $data_pack = mysql_fetch_assoc($result_pack) )
                {
                    // 주문번호가 바뀌면
                    if( $order_seq <> $data_pack[order_seq] )  
                    {
                        // 최초 order_seq=0일 때는, 주문번호 새로고침
                        if( $order_seq )  
                        {
                            // product_id 배열을 문자열로
                            $product_str = implode( ',',$temp_arr[product_id] );
                            
                            // product_id 문자열이 이미 있는지 확인
                            $find_ok = false;
                            foreach( $pack_arr as $key => $value )
                            {
                                // 있으면 기존 수량에 더한다.
                                if( $value[product_id] == $product_str )
                                {
                                    $pack_arr[$key][qty] += $temp_arr[qty];
                                    $find_ok = true;
                                    break;
                                }
                            }
                                
                            // 없으면, 새로 추가
                            if( !$find_ok )
                            {
                                $pack_arr[] = array(
                                    product_id => $product_str,
                                    qty        => $temp_arr[qty]
                                );
                            }
                        }
                        $order_seq = $data_pack[order_seq];
                        
                        // 임시배열 초기화
                        $temp_arr = array();
                    }
                    
                    // 임시 배열에 저장
                    $temp_arr[product_id][] = $data_pack[product_id];
                    if( $data_pack[product_id] == $product_id )
                        $temp_arr[qty] += $data_pack[qty];
                }

                // 마지막 임시 배열에 대해 한번 더 실행...
                
                // product_id 배열을 문자열로
                $product_str = implode( ',',$temp_arr[product_id] );
                
                // product_id 문자열이 이미 있는지 확인
                $find_ok = false;
                foreach( $pack_arr as $key => $value )
                {
                    // 있으면 기존 수량에 더한다.
                    if( $value[product_id] == $product_str )
                    {
                        $pack_arr[$key][qty] += $temp_arr[qty];
                        $find_ok = true;
                        break;
                    }
                }
                    
                // 없으면, 새로 추가
                if( !$find_ok )
                {
                    $pack_arr[] = array(
                        product_id => $product_str,
                        qty        => $temp_arr[qty]
                    );
                }

                // 풀어서 넣는다.
                foreach( $pack_arr as $key => $value )
                {
                    // 구분 더미 배열
                    if( $arr_data )  $arr_data[$i++] = array( type => 2 );

                    // product_id를 배열로..
                    foreach( explode(',', $value[product_id] ) as $pid )
                    {
                        $arr_product = $this->get_product_info( $pid );
                        $arr_data[$i++] = array( 
                            supply_name   => class_supply::get_name2($pid)
                            ,product_id   => $pid 
                            ,name         => $arr_product[name]
                            ,brand        => $arr_product[brand]
                            ,options      => $arr_product[options]
                            ,org_price    => $arr_product[org_price]
                            ,supply_price => $arr_product[supply_price]
                            ,shop_price   => $arr_product[shop_price]
                            ,stock        => class_stock::get_current_stock($pid)
                            ,qty          => ($pid==$product_id?$value[qty]:"")
                            ,type         => ($pid==$product_id?1:0)
                        );
                    }
                }
            }
        }
    }

    // 상품통계상세에서 상품의 주문 형태 가지수를 구한다. 
    function get_all_order_seq()
    {
        global $connect;
        global $product_id, $query_type, $start_date, $end_date, $order_status, $shop_id;

        // 주문 상태
        if ( $order_status == 1 )
            $status = 1;
        else if ( $order_status == 7 )        
            $status = 7;
        else if ( $order_status == 8 )        
            $status = 8;
        else if ( $order_status == 99 )        
            $status = "1,7";
        else
            $status = "1,7,8";
    
        // 해당 기간내에, 해당 상품이 들어있는 order_seq를 구한다.
        $all_order_seq = "
            select b.order_seq as order_seq
              from orders a,
                   order_products b
             where $query_type >= '$start_date 00:00:00' and
                   $query_type <= '$end_date 23:59:59' and
                   b.product_id = '$product_id' and
                   b.order_cs not in (1,2,3,4) and
                   a.status in ($status) and
                   a.seq = b.order_seq
        ";
        
        if( $_SESSION[LOGIN_LEVEL] == 9 )
            echo $query;
            
        
        if( $shop_id )  $all_order_seq .= " and b.shop_id=$shop_id";
        
        $result = mysql_query($all_order_seq, $connect);
        $arr = array();
        while( $data = mysql_fetch_assoc($result) )
            $arr[] = $data[order_seq];
        $arr_str = implode(',', $arr);
        
        // 위에서 구한 order_seq에 대해, order_seq, product_id 중복 제거한다.
        $distinct_order_seq = "
            select distinct order_seq, product_id
              from order_products 
             where order_seq in ($arr_str)
        ";
        
        // 각 order_seq에 대해, 상품이 몇개씩 있는지 알아낸다.
        $sort_order_seq= "
            select count(c.order_seq) as cnt 
              from ($distinct_order_seq) c
          group by c.order_seq 
        ";
        
        // 상품 개수대로 수량만 구한다.
        $query = "
            select distinct d.cnt q
              from ($sort_order_seq) d
          order by q
        ";
        
        return $query;
    }

    // 상품통계상세에서 상품의 단품주문 수를 구한다. 
    function get_single_order_seq()
    {
        global $connect;
        global $product_id, $query_type, $start_date, $end_date, $order_status, $shop_id;

        // 주문 상태
        if ( $order_status == 1 )
            $status = 1;
        else if ( $order_status == 7 )        
            $status = 7;
        else if ( $order_status == 8 )        
            $status = 8;
        else if ( $order_status == 99 )        
            $status = "1,7";
        else
            $status = "1,7,8";

        // 해당 기간내에, 해당 상품이 들어있는 order_seq를 구한다.
        $all_order_seq = "
            select b.order_seq as order_seq
              from orders a,
                   order_products b
             where $query_type >= '$start_date 00:00:00' and
                   $query_type <= '$end_date 23:59:59' and
                   b.product_id =  '$product_id' and
                   b.order_cs not in (1,2,3,4) and
                   a.status in ($status) and
                   a.seq = b.order_seq
        ";
        
        if( $shop_id )  $all_order_seq .= " and b.shop_id=$shop_id";
        
        $result = mysql_query($all_order_seq, $connect);
        $arr = array();
        while( $data = mysql_fetch_assoc($result) )
            $arr[] = $data[order_seq];
        $arr_str = implode(',', $arr);
        
        // 위에서 구한 order_seq에 대해, order_seq, product_id 중복 제거한다.
        $distinct_order_seq = "
            select distinct order_seq, product_id
              from order_products 
             where order_seq in ($arr_str)
        ";
        
        // 각 order_seq에 대해, 1개인 상품 order_seq 구하기
        $single_order_seq = "
            select c.order_seq as o_seq, count(c.order_seq) as cnt
              from ($distinct_order_seq) c
          group by c.order_seq 
            having cnt=1
        ";
        
        // 각 order_seq에 대해, 1개인 상품 order_seq 구하기 - order_seq 만 따로 뽑아내기
        $single_order_seq2 = "
            select d.o_seq
              from ($single_order_seq) d
        ";

        // 각 order_seq에 대해, 1개인 상품 order_seq 구하기 - order_seq 만 따로 뽑아내기
        $query = "
            select supply_id, product_id, sum(qty) as total_qty
              from order_products
             where order_seq in ($single_order_seq2)
          group by product_id
        ";

        return $query;
    }

    // 상품통계상세에서 상품의 복수상품주문 수를 구한다. 
    function get_pack_order_seq($cnt)
    {
        global $connect;
        global $product_id, $query_type, $start_date, $end_date, $order_status, $shop_id;

        // 주문 상태
        if ( $order_status == 1 )
            $status = 1;
        else if ( $order_status == 7 )        
            $status = 7;
        else if ( $order_status == 8 )        
            $status = 8;
        else if ( $order_status == 99 )        
            $status = "1,7";
        else
            $status = "1,7,8";

        // 해당 기간내에, 해당 상품이 들어있는 order_seq를 구한다.
        $all_order_seq = "
            select b.order_seq as order_seq
              from orders a,
                   order_products b
             where 
                   $query_type >= '$start_date 00:00:00' and
                   $query_type <= '$end_date 23:59:59' and
                   b.product_id =  '$product_id' and
                   b.order_cs not in (1,2,3,4) and
                   a.status in ($status) and
                   a.seq = b.order_seq
        ";
        
        if( $shop_id )  $all_order_seq .= " and b.shop_id=$shop_id";
        
        $result = mysql_query($all_order_seq, $connect);
        $arr = array();
        while( $data = mysql_fetch_assoc($result) )
            $arr[] = $data[order_seq];
        $arr_str = implode(',', $arr);
        
        // 위에서 구한 order_seq에 대해, order_seq, product_id 중복 제거한다.
        $distinct_order_seq = "
            select distinct order_seq, product_id
              from order_products 
             where order_seq in ($arr_str)
        ";
        
        // 각 order_seq에 대해, $cnt 개인 상품 order_seq 구하기
        $pack_order_seq = "
            select c.order_seq as o_seq, count(c.order_seq) as cnt
              from ($distinct_order_seq) c
          group by c.order_seq 
            having cnt=$cnt
        ";
        
        // 각 order_seq에 대해, $cnt 개인 상품 order_seq 구하기 - order_seq 만 따로 뽑아내기
        $pack_order_seq2 = "
            select d.o_seq
              from ($pack_order_seq) d
        ";
        
        // 각 order_seq에 대해, $cnt 개인 상품 order_seq 구하기 - order_seq 만 따로 뽑아내기
        $query = "
            select order_seq, product_id, sum(qty) as qty
              from order_products
             where order_seq in ($pack_order_seq2)
          group by order_seq, product_id
          order by order_seq, product_id
        ";

        return $query;
    }

    // 전체 합계
    function get_FK00_total_sum($arr_result, $query)
    {
        global $connect;

        $total_qty = 0;
        $total_amount = 0;
        $total_supply_price = 0;
        $total_org_price = 0;
        $total_p_qty = 0;
        $total_deliv_price = 0;
        
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_a_qty];
            $total_amount += $data[sum_a_amount];
            $total_supply_price += $data[sum_a_supply_price];            
        }

        foreach( $arr_result as $arr_key => $arr_val )
        {
            foreach( $arr_val as $a_key => $a_val )
            {
//debug($arr_key . "==" . $a_key . "==" . $a_val[org_price]);
                $total_org_price    += $a_val[org_price];
                $total_p_qty        += $a_val[qty];
                $total_deliv_price  += $a_val[deliv_price];
            }
        }
        
        $total_array = array(
            "qty"          => $total_qty,
            "amount"       => $total_amount,
            "supply_price" => $total_supply_price,
            "org_price"    => $total_org_price,
            "p_qty"        => $total_p_qty,
            "deliv_price"  => $total_deliv_price
        );
        
        return $total_array;
    }
}

?>
