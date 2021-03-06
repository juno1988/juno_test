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

////////////////////////////////
// class name: class_F200

class class_F200 extends class_top {

    ///////////////////////////////////////////
    function F200()
    {
        global $connect, $template, $page;
        global $start_date, $end_date,$order_status,$shop_id,$str_shop_id,$supply_id,$query_type,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;

        if( $product_group == "on" ) 
            $product_group = 1;
        else
            $product_group = 0;

        $str_shop_id = "";    
        if ( count( $shop_id ) > 0 )
        {
          foreach ( $shop_id as $_id ) 
          {
              $str_shop_id .= $str_shop_id ? "," : "";
              $str_shop_id .=  $_id;
          }
        }

        if( _DOMAIN_ == 'ephod' || _DOMAIN_ == 'ephodkids' ){
        	global $date_diff;
	        //시작일과 끝일간의 일수 차이를 계산
	        $_date1 = explode("-",$end_date); 
			$_date2 = explode("-",$start_date); 
			$tm1 = mktime(0,0,0,$_date1[1],$_date1[2],$_date1[0]); 
			$tm2 = mktime(0,0,0,$_date2[1],$_date2[2],$_date2[0]);
	 		$date_diff = ($tm1 - $tm2) / 86400 + 1;
 		}
 		
        if(!$page)
        {
			$page=1;

            if( _DOMAIN_ == 'realcoco' || _DOMAIN_ == 'elkara' || _DOMAIN_ == 'mny2' )
                $order_cs_34 = "checked";
			
            // 화면 출력
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
			exit;
        }
 
        $this->show_wait();
    
        if( $_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2 ) 
        {
            $query = $this->F200_query();
            
debug("어드민상품매출통계 조회 : " . $query);
            $result = mysql_query( $query, $connect );
            $total_rows = mysql_num_rows($result);
            
            $arr_data = array();
            $i = 1;
            while ( $data = mysql_fetch_array( $result ) )
            {
                if( $order_cs_34 )
                    $data[sum_qty] -= $data[sum_qty_c];
                
                // 수량 0 인 경우. 기간 전에 발주된 주문이 기간 내에 취소된 경우의 상품. 전체상품 아닐 경우만.
                if( !$all_products && $data[sum_qty] == 0 && _DOMAIN_ != 'soimall' )  continue;
                
                $arr_data[] = array( 
                    supply_name   => $data[supply_name]
                    ,shop_id      => $data[shop_id]
                    ,product_id   => $data[product_id] 
                    ,enable_sale  => $data[enable_sale] 
                    ,name         => $data[name]
                    ,brand        => $data[brand]
                    ,options      => $data[options]
                    ,barcode      => $data[barcode]
                    ,org_price    => number_format($data[org_price])
                    ,supply_price => number_format($data[supply_price])
                    ,shop_price   => number_format($data[shop_price])

                    ,qty               => number_format($data[sum_qty])
                    ,shop_price_real   => number_format($data[sum_prd_amount])
                    ,extra_money       => number_format($data[sum_prd_extra_money])
                    ,supply_price_real => number_format($data[sum_prd_supply_price])

                    ,qty_c               => number_format($data[sum_qty_c])
                    ,shop_price_real_c   => number_format($data[sum_prd_amount_c])
                    ,extra_money_c       => number_format($data[sum_prd_extra_money_c])
                    ,supply_price_real_c => number_format($data[sum_prd_supply_price_c])

                    ,category		=> $data[c_category]
                    ,m_category1	=> $data[c_m_category1]
                    ,m_category2	=> $data[c_m_category2]
                    ,m_category3	=> $data[c_m_category3]
                    ,str_category	=> $data[c_str_category]
                );
    
                if ( $i%20 == 0 )
                    $this->show_txt( $i++ );
            }
        }
        else
        {
            $query = $this->F200_query_nomatch();
debug("어드민상품매출통계 조회 : " . $query);
            
            $result = mysql_query( $query, $connect );
            $total_rows = mysql_num_rows($result);
            
            $arr_data = array();
            $i = 1;
            while ( $data = mysql_fetch_array( $result ) )
            {
                $arr_data[] = array( 
                    qty           => number_format($data[sum_qty]),
                    shop_options  => ( $_SESSION[PRODUCT_NAME_EXP] ? $data[product_name] . " " : "" ) . $data[shop_options]
                );
    
                if ( $i%20 == 0 )
                    $this->show_txt( $i++ );
            }
        }

        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function F200_query_time(&$query_time, &$query_time_check)
    {
        global $connect, $template, $page;
        global $start_date, $end_date,$order_status,$shop_id,$str_shop_id,$supply_id,$query_type,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;

        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();

        $query_time = "";
        if( $query_type == "collect_date" )
        {
            if( $start_hour != "00" )
                $query_time .= " timestamp(a.collect_date, a.collect_time) >= '$start_date $start_hour:00:00' ";
            else
                $query_time .= " a.collect_date >= '$start_date' ";
                
            if( $end_hour != "23" )
                $query_time .= " and timestamp(a.collect_date, a.collect_time) <= '$end_date $end_hour:59:59' ";
            else
                $query_time .= " and a.collect_date <= '$end_date' ";
        }
        else if( $query_type == "trans_date" )
            $query_time .= " a.trans_date >= '$start_date $start_hour:00:00' and a.trans_date <= '$end_date $end_hour:59:59' ";
        else if( $query_type == "trans_date_pos" )
            $query_time .= " a.trans_date_pos >= '$start_date $start_hour:00:00' and a.trans_date_pos <= '$end_date $end_hour:59:59' ";
        else if( $query_type == "order_date" )
            $query_time .= " a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

        // soimall 이거나 또는 정산설정에서 취소주문 기준일이 취소일 이면서 배송후취소 제외인 경우
        if( _DOMAIN_ == 'soimall' || ($arr_config["cancel"] == "refund_date" && $order_cs_34) )
        {
            $query_time_check = $query_time;
            if( _DOMAIN_ == 'realcoco' )
                $query_time = " $query_time ";
            else
                $query_time = "(($query_time) or (b.cancel_date>='$start_date $start_hour:00:00' and b.cancel_date<='$end_date $end_hour:59:59')) ";
        }
        else
            $query_time_check = 1;
    }

    function F200_query()
    {
        global $connect, $template, $page;
        global $start_date, $end_date,$order_status,$shop_id,$str_shop_id,$supply_id,$query_type,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;

        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();

        $this->F200_query_time($query_time, $query_time_check);
    
        // query orders
        $query = "select b.*,
                         sum(if($query_time_check,b.qty             ,0)) sum_qty,
                         sum(if($query_time_check,b.prd_amount      ,0)) sum_prd_amount,
                         sum(if($query_time_check,b.extra_money     ,0)) sum_prd_extra_money,
                         sum(if($query_time_check,b.prd_supply_price,0)) sum_prd_supply_price,
                         sum(if(b.order_cs=3 or b.order_cs=4,b.qty,0             )) sum_qty_c,
                         sum(if(b.order_cs=3 or b.order_cs=4,b.prd_amount,0      )) sum_prd_amount_c,
                         sum(if(b.order_cs=3 or b.order_cs=4,b.extra_money,0     )) sum_prd_extra_money_c,
                         sum(if(b.order_cs=3 or b.order_cs=4,b.prd_supply_price,0)) sum_prd_supply_price_c,
                         d.name supply_name,
                         c.name name,
                         c.brand brand,
                         c.options options,
                         c.barcode barcode,
                         c.org_price org_price,
                         c.supply_price supply_price,
                         c.shop_price shop_price,
                         c.enable_sale enable_sale,
                         a.shop_id a_shop_id,
                         c.category c_category,
                         c.m_category1 c_m_category1,
                         c.m_category2 c_m_category2,
                         c.m_category3 c_m_category3,
                         c.str_category c_str_category,
                         ifnull(e.reg_date,c.reg_date) org_reg_date";

        if( !$all_products )
        {
            $query .= " ,sum(b.qty) * c.shop_price sale_price
                        ,if(c.org_id>0,c.org_id,c.product_id) org_product_id";
        }
        
        // from
        if( $all_products )
        {
            $query .= " from products c left outer join  products e on c.org_id=e.product_id
                             left outer join order_products b on b.product_id=c.product_id
                             left outer join orders a on a.seq=b.order_seq,
                             userinfo d ";
        }
        else
        {
            $query .= " from orders a, order_products b, userinfo d,
                             products c left outer join  products e on c.org_id=e.product_id ";
        }

        // where
        // 2014-12-09 장경희. 삭제된 상품도 조회되게 수정
        $query .= " where c.supply_code = d.code ";
        //              and c.is_delete>=0";

        if ( $stock_products )
            $query .= " and c.enable_stock=1 ";

        // 품절
        if( $except_soldout == 1 )
            $query .= " and c.enable_sale=1 ";
        else if( $except_soldout == 2 )
            $query .= " and c.enable_sale=0 ";

        if( !$all_products )
        {
            $query .= "  and a.seq = b.order_seq
                         and b.product_id = c.product_id 
                         and b.order_cs not in (1,2) 
                         and " . $query_time;
    
            // 상태
            switch( $order_status )
            {
                case 1: $query .= " and a.status = 1 "; break;
                case 7: $query .= " and a.status = 7 "; break;
                case 8: $query .= " and a.status = 8 "; break;
                case 99: $query .= " and a.status in (1,7) "; break;
                default: $query .= " and a.status > 0 "; break;
            }

            // 배송후 교환
            if( $arr_config['change'] == 'org' )
                $query .= " and a.c_seq = 0 ";
            else
                $query .= " and b.order_cs not in (7,8) ";
            
            if ( $str_shop_id )
                $query .= " and a.shop_id in ( $str_shop_id )";
    
    		if( $str_supply_code )
    			$query .= " and b.supply_id in ( $str_supply_code ) ";
    		if($multi_supply)
    			$query .= " and b.supply_id in ( $multi_supply ) ";

            if ( $gift_product == 1 )
                $query .= " and b.is_gift=0 ";
            else if ( $gift_product == 2 )
                $query .= " and b.is_gift>0 ";

            if ( $pack_status == 1 ) 
                $query .= " and a.pack > 0 ";
            else if ( $pack_status == 2 ) 
                $query .= " and a.pack = 0 ";
        }

        $string = trim($string);
        if( $string > "" )
        {
            switch( $string_type )
            {
                case "name":             
                    $query .= " and c.name like '%$string%'";
                    break;
                case "options":          
                    $query .= " and c.options like '%$string%'";
                    break;
                case "name_options":
                    $string2 = str_replace(" ", "%", $string);
                    $query .= " and concat(c.name, c.options) like '%$string2%'";
                    break;
                case "product_id":       
                    $query .= " and c.product_id = '$string'";
                    break;
                case "barcode":          
                    $query .= " and c.barcode = '$string'";
                    break;
                case "brand":            
                    $query .= " and c.brand like '%$string%'";
                    break;
                case "supply_options":   
                    $query .= " and c.supply_options like '%$string%'";
                    break;
                case "shop_prd_name":    
                    $query .= " and a.product_name like '%$string%'";
                    break;
                case "shop_prd_options": 
                    $query .= " and a.options like '%$string%'";
                    break;
                case "shop_product_id": 
                    $query .= " and a.shop_product_id = '$string'";
                    break;
            }
        }
        
        // 카테고리
        if( $category )
            $query .= " and c.category = '$category' ";

        // 멀티 카테고리
        if( $m_sub_category_1 )
            $query .= " and c.m_category1 = '$m_sub_category_1' ";
        if( $m_sub_category_2 )
            $query .= " and c.m_category2 = '$m_sub_category_2' ";
        if( $m_sub_category_3 )
            $query .= " and c.m_category3 = '$m_sub_category_3' ";

        if( $product_group )
            $query .= " group by org_product_id ";
        else
            $query .= " group by c.product_id ";                   

        if( $each_shop_download )
            $query .= ", a.shop_id";

        if ($sort)
        {
            if($dir)
            {
                $query .= " order by $sort desc ,";
                $dir = 0;
            }
            else
            {
                $query .= " order by $sort asc ,";
                $dir =1;
            }
        }
        else
            $query .= " order by ";
            
        if( _DOMAIN_ == 'ilovej' )
            $sort_c_options = "c.product_id";
        else
            $sort_c_options = "c.options";
            
        if ( $products_sort == 1 ) // 상품명
            $query .= " c.name, $sort_c_options ";
        if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= " d.name , c.name , $sort_c_options";
        if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= " org_reg_date, c.name, $sort_c_options";
        if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= " org_reg_date, d.name, c.name, $sort_c_options";
        if ( $products_sort == 5 )  // 수량 > 상품명
            $query .= " sum_qty desc, c.name , $sort_c_options";
        if ( $products_sort == 6 )  // 매출 > 상품명
            $query .= " sale_price desc, c.name , $sort_c_options";
        if ( $products_sort == 7 )  // 등록일 > 수량
            $query .= " c.reg_date desc, sum_qty desc";
        if ( $products_sort == 8 )  // 로케이션
        	$query .= " c.location desc ";
    	if ( $products_sort == 9)  // 로케이션 > 상품명
        	$query .= " c.location desc, c.name";
        	
        if( $each_shop_download )
            $query .= ", a.shop_id";

        return $query;
    }
    
    function F200_query_nomatch()
    {
        global $connect, $template, $page;
        global $start_date, $end_date,$order_status,$shop_id,$str_shop_id,$supply_id,$query_type,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;

        // query orders
        $query = "select a.product_name product_name,
                         b.* , sum(b.qty) sum_qty
           		    from orders a, order_products b
                   where a.seq = b.order_seq";
        if( $query_type == "collect_date" )
        {
            if( $start_hour != "00" )
                $query .= " and timestamp(a.collect_date, a.collect_time) >= '$start_date $start_hour:00:00' ";
            else
                $query .= " and a.collect_date >= '$start_date' ";
                
            if( $start_hour != "23" )
                $query .= " and timestamp(a.collect_date, a.collect_time) <= '$end_date $end_hour:59:59' ";
            else
                $query .= " and a.collect_date <= '$end_date' ";
        }
        else if( $query_type == "trans_date" )
            $query .= " and a.trans_date >= '$start_date $start_hour:00:00' and a.trans_date <= '$end_date $end_hour:59:59' ";
        else if( $query_type == "trans_date_pos" )
            $query .= " and a.trans_date_pos >= '$start_date $start_hour:00:00' and a.trans_date_pos <= '$end_date $end_hour:59:59' ";
        else if( $query_type == "order_date" )
            $query .= " and a.order_date >= '$start_date' and a.order_date <= '$end_date' ";

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
            $query .= " and a.status <> 0";

        if ( $str_shop_id )
            $query .= " and a.shop_id in ( $str_shop_id)";

		if( $str_supply_code )
			$query .= " and b.supply_id in ( $str_supply_code ) ";
		if($multi_supply)
			$query .= " and b.supply_id in ( $multi_supply ) ";
			
        if ( $string )
            $query .= " and b.shop_options like '%$string%'";
        
        $query .= " and b.order_cs not in (1,2) group by b.shop_options";
        if( $each_shop_download )
            $query .= ", a.shop_id";

        $query .= " order by b.shop_options";
        if( $each_shop_download )
            $query .= ", a.shop_id";
        
        return $query;
    }
    
    function F202()
    {
        global $connect, $template;
        global $product_id, $total_qty;

        global $start_date, $end_date,$start_hour, $end_hour;
        global $order_status,$shop_id,$supply_id,$query_type,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;
		global $is_cancel;

        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();
        
        $this->F200_query_time($query_time, $query_time_check);

        // 상품정보
        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $product_info = mysql_fetch_assoc($result);
        
        // 그룹별로 표시
        $str_product = "";
        if ( $product_group )
        {
            $query = "select product_id from products where org_id='$product_info[org_id]'";
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
                $str_product .= ($str_product ? "," : "") . "'" . $data[product_id] . "'"; 
        }
        
        // 공급처
        $supply_name = class_supply::get_name($product_info[supply_code]);
        
        // 수량
        $query = "select sum(if($query_time_check,b.qty,0)) sum,
                         sum(if(b.order_cs=3 or b.order_cs=4,b.qty,0)) sum_c,
                         c.shop_name,
                         a.shop_id
                    from orders a, order_products b, shopinfo c
                   where a.seq = b.order_seq 
                     and b.shop_id = c.shop_id 
                     and $query_time
                     and b.order_cs not in ( 1,2 ) ";

        // 상태
        switch( $order_status )
        {
            case 1: $query .= " and a.status = 1 "; break;
            case 7: $query .= " and a.status = 7 "; break;
            case 8: $query .= " and a.status = 8 "; break;
            case 99: $query .= " and a.status in (1,7) "; break;
            default: $query .= " and a.status > 0 "; break;
        }

        // 배송후 교환
        if( $arr_config['change'] == 'org' )
            $query .= " and a.c_seq=0 ";
        else
            $query .= " and b.order_cs not in (7,8) ";

        // 취소 조회
        if ( $is_cancel )
            $query .= " and b.order_cs in (3,4) ";

        if ( $shop_id )
            $query .= " and a.shop_id in ( $shop_id ) ";
        
        // 사은품
        if ( $gift_product == 1 )
            $query .= " and b.is_gift=0 ";
        else if ( $gift_product == 2 )
            $query .= " and b.is_gift>0 ";

        // 합포
        if ( $pack_status == 1 ) 
            $query .= " and a.pack > 0 ";
        else if ( $pack_status == 2 ) 
            $query .= " and a.pack = 0 ";

        if ( $str_product )
            $query .= " and b.product_id in ( $str_product ) ";   
        else if ( $product_id )
            $query .= " and b.product_id = '$product_id'";

        $string = trim($string);
        if( $string > "" )
        {
            switch( $string_type )
            {
                case "shop_prd_name":    
                    $query .= " and a.product_name like '%$string%'";
                    break;
                case "shop_prd_options": 
                    $query .= " and a.options like '%$string%'";
                    break;
                case "shop_product_id": 
                    $query .= " and a.shop_product_id = '$string'";
                    break;
            }
        }
        
        $query .= " group by a.shop_id order by c.shop_name";
debug("F202 query : " . $query);
        $result = mysql_query($query, $connect);
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 
    function F203()
    {
        global $connect, $template;
        global $query_type, $product_id, $status, $total_qty;
        
        global $start_date, $end_date,$start_hour, $end_hour;
        global $order_status,$shop_id,$supply_id,$string_type, $string, $products_sort, $all_products, $pack_status, $product_group, $stock_products, $sort, $dir, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $start_hour, $end_hour;
		global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;
		global $is_cancel;

        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();
        
        $this->F200_query_time($query_time, $query_time_check);

        // 상품정보
        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $product_info = mysql_fetch_assoc($result);
        
        // 그룹별로 표시
        $str_product = "";
        if ( $product_group )
        {
            $query = "select product_id from products where org_id='$product_info[org_id]'";
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
                $str_product .= ($str_product ? "," : "") . "'" . $data[product_id] . "'"; 
        }
        
        // 공급처
        $supply_name = class_supply::get_name($product_info[supply_code]);
        
        // 수량
        $query = "select if($query_time_check,b.qty,0) b_qty
                        ,if(b.order_cs=3 or b.order_cs=4,b.qty,0) b_qty_c
                        ,a.order_id
                        ,a.collect_date
                        ,a.amount
                        ,c.shop_name    
                        ,a.product_name
                        ,a.options                     
                    from orders a, order_products b, shopinfo c
                   where a.seq = b.order_seq 
                     and b.shop_id = c.shop_id 
                     and $query_time 
                     and b.order_cs not in ( 1,2 ) ";
        
        // 상태
        switch( $order_status )
        {
            case 1: $query .= " and a.status = 1 "; break;
            case 7: $query .= " and a.status = 7 "; break;
            case 8: $query .= " and a.status = 8 "; break;
            case 99: $query .= " and a.status in (1,7) "; break;
            default: $query .= " and a.status > 0 "; break;
        }
        
        // 배송후 교환
        if( $arr_config['change'] == 'org' )
            $query .= " and a.c_seq=0 ";
        else
            $query .= " and b.order_cs not in (7,8) ";

        // 취소주문
        if ( $is_cancel )
            $query .= " and b.order_cs in (3,4) ";

        $query .= " and a.shop_id = $shop_id ";

        // 사은품
        if ( $gift_product == 1 )
            $query .= " and b.is_gift=0 ";
        else if ( $gift_product == 2 )
            $query .= " and b.is_gift>0 ";

        // 합포
        if ( $pack_status == 1 ) 
            $query .= " and a.pack > 0 ";
        else if ( $pack_status == 2 ) 
            $query .= " and a.pack = 0 ";

        if ( $str_product )
            $query .= " and b.product_id in ( $str_product ) ";   
        else if ( $product_id )
            $query .= " and b.product_id = '$product_id'";

        $query .= " order by c.sort_name, a.collect_date";
        $result = mysql_query($query, $connect);
debug("F203 query : " . $query);        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 옵션매칭
    function save_file()
    {
        
        global $connect;
        global $start_date, $end_date,$order_status,$shop_id,$str_shop_id,$supply_id,$query_type,$string_type,$string,$products_sort,$pack_status, $product_group, $stock_products, $order_cs_34, $gift_product, $each_shop_download;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $str_shop_id, $start_hour, $end_hour;
        global $str_supply_code, $multi_supply_group, $multi_supply, $except_soldout;
        
        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();
        
        // 엑셀 헤더
        $product_data = array();

        
        $_p_data = array();

        if( $each_shop_download )
            $_p_data["shop_name"     ] = "판매처명";

        $_p_data["supply_name"     ] = "공급처명";
        $_p_data["supply_tel"      ] = "연락처";
        if(_DOMAIN_=="realcoco")
        {
        	$_p_data["c_str_category"  ] = "카테고리";
        }
        $_p_data["product_id"      ] = "상품코드";
        $_p_data["name"            ] = "상품명";
        $_p_data["brand"           ] = "사입처상품명";

        if ( $_SESSION[PACK_P_O] != "true" )
            $_p_data["options"         ] = "옵션";

        $_p_data["barcode"         ] = "바코드";
        $_p_data["org_price"       ] = "원가";
        $_p_data["supply_price"    ] = "공급가";        
        $_p_data["shop_price"      ] = "판매가";
        if( _DOMAIN_ == 'realcoco' )
        	$_p_data["a_amount"    ] = "판매금액";
        $_p_data["stock"           ] = "현재고";
        $_p_data["qty"             ] = "수량";

        $_p_data["tot_org_price"   ] = "원가금액";
        $_p_data["tot_supply_price"] = "공급가금액";
        $_p_data["sale"            ] = "판매가금액";

        if( _DOMAIN_ == 'soimall' )
        {
            $_p_data["qty_c"     ] = "취소수량";
            $_p_data["sale_c"     ] = "취소매출";
        }
		
        $_p_data["enable_sale"     ] = "품절";
        $_p_data["reg_date"        ] = "등록일";
        $_p_data["location"        ] = "로케이션";
        $_p_data["status1_qty"     ] = "접수상태수량";
        $_p_data["status2_qty"     ] = "송장상태수량";

        if( _DOMAIN_ == 'ephod'  || _DOMAIN_ == 'ephodkids' )
        {
            $_p_data["ave_cnt"     ] = "1일판매수량";
            $_p_data["trans_cnt"     ] = "발송후수량";
            $_p_data["salse_available_cnt"     ] = "판매가능일수";
        }
		
		if( _DOMAIN_ == 'happysugar' )
			$_p_data["memo"        ] = "메모";
		
	
        $product_data[] = $_p_data;
		
        $query = $this->F200_query();
        $result = mysql_query( $query, $connect );
        
debug("어드민상품매출통계 다운로드 : " . $query);        
		
        if( _DOMAIN_ == 'ephod'  || _DOMAIN_ == 'ephodkids' ){
        	global $date_diff;
	        //시작일과 끝일간의 일수 차이를 계산
	        $_date1 = explode("-",$end_date); 
			$_date2 = explode("-",$start_date); 
			$tm1 = mktime(0,0,0,$_date1[1],$_date1[2],$_date1[0]); 
			$tm2 = mktime(0,0,0,$_date2[1],$_date2[2],$_date2[0]);
	 		$date_diff = ($tm1 - $tm2) / 86400 + 1;
 		}




        $arr_data = array();
        $i = 1;
        while ( $data = mysql_fetch_array( $result ) )
        {        	
            if ( !$data[product_id] ) continue;
            
            $data[sum_qty] -= ( $order_cs_34 ? $data[sum_qty_c] : 0);
            
            $arr_product = $this->get_product_info( $data[product_id] );
            $arr_supply  = class_supply::get_info( $data[ supply_id ]);

            $tot_org_price = $data[sum_qty] * $arr_product[org_price];
            
            if( _DOMAIN_ == 'crystal' )
                $tot_supply_price = $data[sum_prd_supply_price];
            else
                $tot_supply_price = $data[sum_qty] * $arr_product[supply_price];

            if( _DOMAIN_ == 'plays' || _DOMAIN_ == 'elkara' || _DOMAIN_ == 'box4u' || _DOMAIN_ == 'gamsung' )
                $sale_price = $data['sum_prd_amount'] + $data['sum_prd_extra_money'];
            else
                $sale_price = $data[sum_qty] * $arr_product[shop_price];

            $sale_price_c = $data[sum_qty_c] * $arr_product[shop_price];
            
            // 접수, 송장 상태 수량 구하기
            $trans_ready = number_format(class_stock::get_ready_stock($data[product_id]));  
            $before_trans = number_format(class_stock::get_ready_stock2($data[product_id]));  

            $options = (substr($data[product_id],0,1)=="S") ? $arr_product[options] : $data[options];

            $_p_data = array();

            if( $each_shop_download )
                $_p_data["shop_name"     ] = $_SESSION["SHOP_NAME_" . $data[a_shop_id]];

            $_p_data["supply_name" ] = $arr_supply[name];
            $_p_data["supply_tel"  ] = $arr_supply[tel] . " / " . $arr_supply[mobile];
            if(_DOMAIN_ =="realcoco")
            {
	            if($data[c_str_category])
	            {
					$str_arr = explode(">",$data[c_str_category]);                	
					$data[c_str_category] = "";
					
					$str_arr_query = "SELECT name, depth from multi_category WHERE seq IN($str_arr[0], $str_arr[1], $str_arr[2])";					  
					$str_arr_result = mysql_query($str_arr_query, $connect);
					while($str_arr_data = mysql_fetch_assoc($str_arr_result ))
					{		
						$data[c_str_category] .= "$str_arr_data[name] >";
					}
				}
	            $_p_data["c_str_category" ] = $data[c_str_category];
        	}
            $_p_data["product_id"  ] = $product_group ? "" : $data[product_id];

            if ( $_SESSION[PACK_P_O] != "true" )
                $_p_data["name"        ] = $arr_product[name];
            else
                $_p_data["name"        ] = $arr_product[name] . ( $product_group ? "" : " " . $options );
            
            $_p_data["brand"       ] = $arr_product[brand];

            if ( $_SESSION[PACK_P_O] != "true" )
                $_p_data["options"     ] = ($product_group ? "" : $options);

            $_p_data["barcode"     ] = $arr_product[barcode];
            $_p_data["org_price"   ] = $arr_product[org_price];
            $_p_data["supply_price"] = $arr_product[supply_price];
            $_p_data["shop_price"  ] = $arr_product[shop_price];
            if( _DOMAIN_ == 'realcoco' )
            	$_p_data["a_amount"    ] = $data[sum_prd_amount] - ( $order_cs_34 ? $data[sum_prd_amount_c] : 0);
            $_p_data["stock"       ] = ($product_group ? "" : class_stock::get_current_stock($data[product_id]));
            $_p_data["qty"         ] = $data[sum_qty];

            $_p_data["tot_org_price"   ] = $tot_org_price;
            $_p_data["tot_supply_price"] = $tot_supply_price;
            $_p_data["sale"        ] = $sale_price;
            
            if( _DOMAIN_ == 'soimall' )
            {
                $_p_data["qty_c"         ] = $data[sum_qty_c];
                $_p_data["sale_c"        ] = $sale_price_c;
            }
            
            $_p_data["enable_sale" ] = ($data[enable_sale]==0 ? "품절" : "" );
            $_p_data["reg_date"    ] = $arr_product[reg_date];
            $_p_data["location"    ] = $arr_product[location];
            $_p_data["status1_qty"    ] = $before_trans;
            $_p_data["status2_qty"    ] = $trans_ready;
            
            if( _DOMAIN_ == 'happysugar' )
            	$_p_data["memo"    ] = $arr_product[memo];
            	
           	if(_DOMAIN_=="ephod"  || _DOMAIN_ == 'ephodkids')
			{
				$_query = "select sum(a.qty) tot_qty
				            from order_products a
				                ,orders b
				           where b.status     in (1,7)
				             and a.product_id = '$data[product_id]'
				             and a.order_cs in (0)
				             and a.order_seq  = b.seq";
				             
				if( $query_type == "collect_date" )
				    $_query .= " and b.collect_date >= '$start_date' and b.collect_date <= '$end_date' ";
				else if( $query_type == "trans_date" )
				    $_query .= " and b.trans_date >= '$start_date $start_hour:00:00' and b.trans_date <= '$end_date $end_hour:59:59' ";
				else if( $query_type == "trans_date_pos" )
				    $_query .= " and b.trans_date_pos >= '$start_date $start_hour:00:00' and b.trans_date_pos <= '$end_date $end_hour:59:59' ";
				else if( $query_type == "order_date" )
				    $_query .= " and b.order_date >= '$start_date' and b.order_date <= '$end_date' ";
				    
				$_result = mysql_query( $_query, $connect );
				$_data   = mysql_fetch_assoc( $_result );
				
				if(!$_data[tot_qty])
					$_data[tot_qty] = 0;
					
				$stock_cnt = class_stock::get_current_stock($_p_data["product_id"]);
				
				$_p_data["ave_cnt"    ] = $ave_cnt =sprintf("%1.1f", ($_p_data["qty"]/$date_diff));
				$_p_data["trans_cnt"    ] = $trans_cnt = $stock_cnt-$_data[tot_qty];
				$_p_data["salse_available_cnt"    ] = $salse_available_cnt = floor($trans_cnt / $ave_cnt);
				
			}
            
            $product_data[] = $_p_data;
            if( $i % 73 == 0 )
                $this->show_txt( $i++ );
        }
        $fn = $this->make_file( $product_data );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 일반매칭
    function save_file3()
    {
        
        global $connect;
        global $start_date, $end_date,$order_status,$shop_id, $str_shop_id,$supply_id,$query_type,$string,$products_sort, $order_cs_34, $gift_product, $each_shop_download, $start_hour, $end_hour;

        // 엑셀 헤더
        $product_data = array();
        $product_data[] = array(
            "qty"           => "수량",
            "shop_options"  => "상품"
        );

        // query orders
        $query = $this->F200_query_nomatch();
debug("어드민상품매출통계 다운로드 : " . $query);        

        $result = mysql_query( $query, $connect );

        $arr_data = array();
        $i = 1;
        while ( $data = mysql_fetch_array( $result ) )
        {
            $pname = ( $_SESSION[PRODUCT_NAME_EXP] ? $data[product_name] . " " : "" ) . $data[shop_options];
            $product_data[] = array( 
                qty           => $data[sum_qty],
                shop_options  => htmlspecialchars($pname)
            );

            if( $i % 73 == 0 )
                $this->show_txt( $i++ );
        }

        $fn = $this->make_file( $product_data );
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

            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {	
                    if( $key == 'org_price' || $key == 'supply_price' || $key == 'shop_price' || $key == 'stock' || $key == 'qty' || $key == 'sale' || $key == 'qty_c' || $key == 'sale_c' || $key == 'sale_supply' || $key == 'status1_qty' || $key == 'status2_qty'  || $key == 'tot_org_price'  || $key == 'tot_supply_price' || $key == 'a_amount' )
                        $buffer .= "<td class=num_item>" . $value . "</td>";
                    else
                        $buffer .= "<td class=str_item>" . $value . "</td>";
                }
            }
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
debug( "정렬조회 : ".$buffer );
        fwrite($handle, "</table>");
        fclose($fp);
        
        return $filename;
    }

    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "sale_data.xls");
    }    

    function get_product_info ( $product_id )
    {
        global $connect;

        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data;
    }

    ///////////////////////////////////////////
    // F200 -> calc
    function calc()
    {   
        global $connect;
        global $template;
        
        $master_code = substr( $template, 0,1); 
        include "template/" . $master_code ."/" . $template . "_calc.php";
        
        exit;
    }

    ///////////////////////////////////////////
    function F201()
    {
        global $connect, $template;
        global $product_id, $query_type, $start_date, $end_date, $order_status, $shop_id, $qty;

        $this->show_wait();
        $arr_data = array();
        $this->get_data2(&$arr_data);
        $this->hide_wait();
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file2()
    {
        global $connect;
        global $start_date, $end_date, $supply_id, $query_type, $product_id, $order_status, $shop_id;

        $arr_data = array();
        $this->get_data2(&$arr_data, 1);
        $fn = $this->make_file2( $arr_data );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    function make_file2( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_sale_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            if( $i == 0 )
                $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';
            else
                $style = 'font:9pt "굴림"; white-space:nowrap;';

            $row = $arr_datas[$i];
            if( $row[type] == 2 )  
            {
                $buffer = "<tr height=8>\n";
                $buffer .= "<td style='background:#aaa' colspan=10></td>";
            }
            else
            {
                $buffer = "<tr>\n";

                // for column
                foreach ( $row as $key=>$value) 
                    if( $key != "type" )
                        $buffer .= "<td style='$style'>" . $value . "</td>";
            }
            
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);
        
        return $filename;
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
        
        $str_shop_id = "";    
        if ( count( $shop_id ) > 0 )
        {
          foreach ( $shop_id as $_id )
          {
              $str_shop_id .= $str_shop_id ? "," : "";
              $str_shop_id .=  $_id;
          }
        }
        
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
            
        
        if( $str_shop_id )  
            $all_order_seq .= " and b.shop_id in ($str_shop_id)";
        
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
        
        $str_shop_id = "";    
        if ( count( $shop_id ) > 0 )
        {
          foreach ( $shop_id as $_id )
          {
              $str_shop_id .= $str_shop_id ? "," : "";
              $str_shop_id .=  $_id;
          }
        }
        
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
        
        if( $str_shop_id )  
            $all_order_seq .= " and b.shop_id in ($str_shop_id)";
        
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
        
        $str_shop_id = "";    
        if ( count( $shop_id ) > 0 )
        {
          foreach ( $shop_id as $_id )
          {
              $str_shop_id .= $str_shop_id ? "," : "";
              $str_shop_id .=  $_id;
          }
        }
        
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
        
        if( $str_shop_id ) 
             $all_order_seq .= " and b.shop_id in ($str_shop_id)";
        
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
}

?>
