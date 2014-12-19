<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_ui.php";
require_once "class_category.php";
require_once "class_multicategory.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_IE00 extends class_top
{
    /////////////////////////////////////
    // 판매처 주문 상세
    /////////////////////////////////////
    function IE00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $product_id, $name, $options, $start_date, $end_date, $work_type, $stock_type, $period, $daily_stock_all, $s_group_id, $except_soldout;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $shop_id, $str_shop_code, $group_id;
        global $start_date2, $end_date2, $product_date, $shop_product_id;
		global $multi_supply_group, $multi_supply;
		global $query_type, $query_str;
		
        // 페이지
        if( !$page )
        {
            $page = 1;
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
            return;
        }
        
        // 작업중
        $this->show_wait();

        // link url
        $par = array('template','supply_code','product_id','name','options','start_date','end_date','work_type','stock_type','period','daily_stock_all','s_group_id','except_soldout','category','m_sub_category_1','m_sub_category_2','m_sub_category_3','shop_product_id','multi_supply_group', 'multi_supply','query_type','query_str');
        $link_url = $this->build_link_url3( $par );
        
        // 조회기간
        $period = (strtotime($end_date)-strtotime($start_date)) / (60*60*24) + 1;
        
        // 전체 조회 -> 세션저장
        if( $daily_stock_all && !$_SESSION[DAILY_STOCK_ALL] )
        {
            $_SESSION[DAILY_STOCK_ALL] = 1;
            $query_config = "update ez_config set daily_stock_all = 1";
            mysql_query($query_config, $connect);
        }
        else if( !$daily_stock_all && $_SESSION[DAILY_STOCK_ALL] )
        {
            $_SESSION[DAILY_STOCK_ALL] = 0;
            $query_config = "update ez_config set daily_stock_all = 0";
            mysql_query($query_config, $connect);
        }
        
        $query = $this->get_IE00();
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result );
        
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_qty];
            $total_amount += $data[amount];
        }

        //$query .= " limit 3000";
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_IE00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $product_id, $name, $options, $start_date, $end_date, $work_type, $stock_type, $period, $daily_stock_all, $s_group_id, $except_soldout;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $str_supply_code, $str_shop_code;
        global $shop_id, $str_shop_code, $group_id;
        global $start_date2, $end_date2, $product_date, $shop_product_id;
        global $multi_supply_group, $multi_supply;
		global $query_type, $query_str;
        
        // 판매처 복수선택
        if ( $str_shop_code == "")
        {
            foreach( $shop_id as $_c )
            {
                $str_shop_code .= $str_shop_code ? "," : "";
                $str_shop_code .= $_c;
            }
        }
        
        // 판매처 그룹
        if( $group_id )
        {
            $group_arr = array();
            $query = "select shop_id from shopinfo where group_id = $group_id";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $group_arr[] = $data[shop_id];
            
            $shop_group_str = implode(",", $group_arr);
            if( !$shop_group_str )
                $shop_group_str = 99999;
        }
        
        $query = "select b.name           b_name,
                         a.product_id     a_product_id,
                         a.name           a_name, 
                         a.options        a_options,
                         a.brand          a_brand,
                         a.origin         a_origin,
                         a.barcode        a_barcode,
                         a.supply_options a_supply_options, 
                         a.reg_date       a_reg_date,
                         a.org_price      a_org_price,
                         a.category       a_category,
                         a.str_category   a_str_category,
                         a.enable_sale    a_enable_sale,
                         a.memo           a_memo,
                         a.sale_date	  a_sale_date,
						 a.reserve_qty	  a_reserve_qty,                         
                         b.tel            b_tel,
                         b.mobile         b_mobile,
                         b.address1       b_address1,
                         b.address2       b_address2 ";

        // 재고
        if( $work_type == 'order_trans' )
        {
            $product_arr = array();
            
            // 배송 상품코드
            $query1 = $query;
            $query1 .= " from products a,
                             userinfo b,
                             stock_tx_history c,
                             orders d
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id = c.product_id and
                             c.order_seq = d.seq and 
                             c.bad = $stock_type and 
                             c.crdate >= '$start_date 00:00:00' and 
                             c.crdate <= '$end_date 23:59:59' and 
                             c.job = 'trans' ";

            if ( $str_supply_code )
                $query1 .= " and a.supply_code in ( $str_supply_code )";
			if($multi_supply)
            	$query1 .= " and a.supply_code in ( $multi_supply ) ";
                
            // query_str & query_type
            if( $query_str )
            {
                switch( $query_type )
                {
                    case "name"         :
                        if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $query_str) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " replace(a.name,' ','') like '%$str_val%' ";
                            }
                            
                            if( $or_str > "" )
                                $query1 .= " and ($or_str) ";
                        }
                        else
                        {
                            $string = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($query_str) );
                            $query1 .= " and replace(a.name,' ','') like '%$string%' "; 
                        }
                        break;
                    case "options"      :
                        $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                        $query1 .= " and a.options like '%$string%' ";
                        break;
                    case "name_options" :
                        list($query_str1, $query_str2) = split(" ", $query_str, 2);
                        $string1 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str1) );
                        $string2 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str2) );
                        $query1 .= " and a.name like '%$string1%' and a.options like '%$string2%' ";
                        break;
                    case "product_id"   :
                        $query1 .= " and a.product_id = '$query_str' ";
                        break;
                    case "barcode"      :
                        $query1 .= " and a.barcode = '$query_str' ";
                        break;
                    case "origin"       :
                        if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $query_str) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"), array("\\%","\\_"), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " a.origin like '%$str_val%' ";
                            }
                            
                            if( $or_str > "" )
                                $query1 .= " and ($or_str) ";
                        }
                        else
                        {
                            $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                            $query1 .= " and a.origin like '%$string%' "; 
                        }
                        break;
                }
            }


            if( $product_date == 1 )
                $query1 .= " and a.reg_date >= '$start_date2' and a.reg_date <= '$end_date2' ";

            // 카테고리
            if( $category )
                $query .= " and a.category = '$category' ";
    
            // 멀티 카테고리
            if( $m_sub_category_1 )
                $query .= " and a.m_category1 = '$m_sub_category_1' ";
            if( $m_sub_category_2 )
                $query .= " and a.m_category2 = '$m_sub_category_2' ";
            if( $m_sub_category_3 )
                $query .= " and a.m_category3 = '$m_sub_category_3' ";
            
            if( $except_soldout == 1 )
                $query1 .= " and a.enable_sale = 1 ";
            else if( $except_soldout == 2 )
                $query1 .= " and a.enable_sale = 0 ";

            // 판매처조건
            if( $str_shop_code )
                $query1 .= " and d.shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query1 .= " and d.shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query1 .= " and d.shop_product_id = '$shop_product_id' ";

            $query1 .= " group by a.product_id order by b.name, a.name, a.options ";
            $result = mysql_query($query1, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $product_arr[] = "'" . $data[a_product_id] . "'";
            
            // 주문 상품코드
            $query2 = $query;
            $query2 .= " from products a,
                             userinfo b,
                             order_products c
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id = c.product_id and
                             c.match_date >= '$start_date 00:00:00' and
                             c.match_date <= '$end_date 23:59:59' ";
            if ( $str_supply_code )
                $query2 .= " and a.supply_code in ( $str_supply_code )";
			if($multi_supply)
            	$query2 .= " and a.supply_code in ( $multi_supply ) ";


            // query_str & query_type
            if( $query_str )
            {
                switch( $query_type )
                {
                    case "name"         :
                        if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $query_str) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " replace(a.name,' ','') like '%$str_val%' ";
                            }
                            
                            if( $or_str > "" )
                                $query2 .= " and ($or_str) ";
                        }
                        else
                        {
                            $string = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($query_str) );
                            $query2 .= " and replace(a.name,' ','') like '%$string%' "; 
                        }
                        break;
                    case "options"      :
                        $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                        $query2 .= " and a.options like '%$string%' ";
                        break;
                    case "name_options" :
                        list($query_str1, $query_str2) = split(" ", $query_str, 2);
                        $string1 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str1) );
                        $string2 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str2) );
                        $query2 .= " and a.name like '%$string1%' and a.options like '%$string2%' ";
                        break;
                    case "product_id"   :
                        $query2 .= " and a.product_id = '$query_str' ";
                        break;
                    case "barcode"      :
                        $query2 .= " and a.barcode = '$query_str' ";
                        break;
                    case "origin"       :
                        if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $query_str) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"), array("\\%","\\_"), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " a.origin like '%$str_val%' ";
                            }
                            
                            if( $or_str > "" )
                                $query2 .= " and ($or_str) ";
                        }
                        else
                        {
                            $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                            $query2 .= " and a.origin like '%$string%' "; 
                        }
                        break;
                }
            }


            if( $product_date == 1 )
                $query2 .= " and a.reg_date >= '$start_date2' and a.reg_date <= '$end_date2' ";

            // 판매처조건
            if( $str_shop_code )
                $query2 .= " and c.shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query2 .= " and c.shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query2 .= " and c.shop_product_id = '$shop_product_id' ";

            $query2 .= " group by a.product_id order by b.name, a.name, a.options ";
            $result = mysql_query($query2, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $product_arr[] = "'" . $data[a_product_id] . "'";

            // 실제 상품코드
            $query .= " from products a ";
            if(_DOMAIN_ == "lalael2")
	            $query .= " left outer join products p on a.org_id=p.product_id ";
            $query .= ",userinfo b
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id in (" . implode(",", $product_arr) . ")";
        }
        else if( $work_type == 'stockin' ||
            $work_type == 'stockout'||
            $work_type == 'arrange' ||
            $work_type == 'retin'   ||
            $work_type == 'retout'  ||
            $work_type == 'SHOP_REQ'  ||
            $work_type == 'HQ_RETURN'  )
        {
            $query .= " from products a";
            if(_DOMAIN_ == "lalael2")
	            $query .= " left outer join products p on a.org_id=p.product_id ";
            $query .=      ",userinfo b,
                             stock_tx c
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id = c.product_id and
                             c.bad = $stock_type and 
                             c.crdate >= '$start_date' and 
                             c.crdate <= '$end_date' and 
                             c.$work_type > 0 ";
        }
        else if( $work_type == 'trans' )
        {
            $query .= " from products a";
            if(_DOMAIN_ == "lalael2")
	            $query .= " left outer join products p on a.org_id=p.product_id ";
            $query .=      ",userinfo b,
                             stock_tx_history c,
                             orders d
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id = c.product_id and
                             c.order_seq = d.seq and 
                             c.bad = $stock_type and 
                             c.crdate >= '$start_date 00:00:00' and 
                             c.crdate <= '$end_date 23:59:59' and 
                             c.job = 'trans' ";

            // 판매처조건
            if( $str_shop_code )
                $query .= " and d.shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query .= " and d.shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query .= " and d.shop_product_id = '$shop_product_id' ";
        }
        // 주문
        else if( $work_type == 'order' )
        {
            $query .= " from products a ";
            if(_DOMAIN_ == "lalael2")
	            $query .= " left outer join products p on a.org_id=p.product_id ";
            $query .= "      ,userinfo b,
                             order_products c
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 and
                             a.product_id = c.product_id and
                             c.match_date >= '$start_date 00:00:00' and
                             c.match_date <= '$end_date 23:59:59' ";

            // 판매처조건
            if( $str_shop_code )
                $query .= " and c.shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query .= " and c.shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query .= " and c.shop_product_id = '$shop_product_id' ";
        }
        // 주문(0 포함)
        else if( $work_type == 'order_0' )
        {
            $query .= " from products a ";
            if(_DOMAIN_ == "lalael2")
	            $query .= " left outer join products p on a.org_id=p.product_id ";
            $query .= " 
             left outer join order_products c
             			  on a.product_id = c.product_id 
                         and c.match_date >= '$start_date 00:00:00' 
                         and c.match_date <= '$end_date 23:59:59', 
                             userinfo b
                             
                       where a.supply_code = b.code and
                             a.is_represent = 0 and
                             a.is_delete = 0 ";
        }
        
        if ( $str_supply_code )
            $query .= " and a.supply_code in ( $str_supply_code )";

		if($multi_supply)
           	$query .= " and a.supply_code in ( $multi_supply ) ";


        // query_str & query_type
        if( $query_str )
        {
            switch( $query_type )
            {
                case "name"         :
                    if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                    {
                        $or_str = "";
                        foreach(explode("$", $query_str) as $str_val)
                        {
                            if( !$str_val )  continue;
                            
                            $str_val = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($str_val) );
                            $or_str .= ($or_str ? " or " : "") . " replace(a.name,' ','') like '%$str_val%' ";
                        }
                        
                        if( $or_str > "" )
                            $query .= " and ($or_str) ";
                    }
                    else
                    {
                        $string = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($query_str) );
                        $query .= " and replace(a.name,' ','') like '%$string%' "; 
                    }
                    break;
                case "options"      :
                    $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                    $query .= " and a.options like '%$string%' ";
                    break;
                case "name_options" :
                    list($query_str1, $query_str2) = split(" ", $query_str, 2);
                    $string1 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str1) );
                    $string2 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str2) );
                    $query .= " and a.name like '%$string1%' and a.options like '%$string2%' ";
                    break;
                case "product_id"   :
                    $query .= " and a.product_id = '$query_str' ";
                    break;
                case "barcode"      :
                    $query .= " and a.barcode = '$query_str' ";
                    break;
                case "origin"       :
                    if( $_SESSION[IS_DB_ALONE] && strpos($query_str, "$") !== false )
                    {
                        $or_str = "";
                        foreach(explode("$", $query_str) as $str_val)
                        {
                            if( !$str_val )  continue;
                            
                            $str_val = str_replace( array("%","_"), array("\\%","\\_"), trim($str_val) );
                            $or_str .= ($or_str ? " or " : "") . " a.origin like '%$str_val%' ";
                        }
                        
                        if( $or_str > "" )
                            $query .= " and ($or_str) ";
                    }
                    else
                    {
                        $string = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str) );
                        $query .= " and a.origin like '%$string%' "; 
                    }
                    break;
            }
        }


        if( $product_date == 1 )
            $query .= " and a.reg_date >= '$start_date2' and a.reg_date <= '$end_date2' ";

        // 카테고리
        if( $category )
            $query .= " and a.category = '$category' ";

        // 멀티 카테고리
        if( $m_sub_category_1 )
            $query .= " and a.m_category1 = '$m_sub_category_1' ";
        if( $m_sub_category_2 )
            $query .= " and a.m_category2 = '$m_sub_category_2' ";
        if( $m_sub_category_3 )
            $query .= " and a.m_category3 = '$m_sub_category_3' ";

        if( $except_soldout == 1 )
            $query .= " and a.enable_sale = 1 ";
        else if( $except_soldout == 2 )
            $query .= " and a.enable_sale = 0 ";

        if( _DOMAIN_ == 'ilovej'  || $_SESSION[PRODUCT_ORDERBY]  )
                $query .= " group by a.product_id order by b.name, a.name, a.product_id ";
        else if( _DOMAIN_ == 'lalael2' )
                $query .= " group by a.product_id order by ifnull(p.reg_date, a.reg_date), b.name, a.name, a.product_id ";
        else
                $query .= " group by a.product_id order by b.name, a.name, a.options ";


        return $query;
    }   

    function save_file_IE00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $product_id, $name, $options, $start_date, $end_date, $work_type, $stock_type, $period, $daily_stock_all, $s_group_id, $except_soldout;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $str_supply_code, $str_shop_code;
        global $shop_id, $str_shop_code, $group_id;
        global $start_date2, $end_date2, $product_date, $shop_product_id;
		global $multi_supply_group, $multi_supply;
		global $query_type, $query_str;
		
		
        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();

        // 조회기간
        $period = (strtotime($end_date)-strtotime($start_date)) / (60*60*24) + 1;
        
        $query = $this->get_IE00();
debug("일자별 재고조회 다운로드 : " . $query);
        $result = mysql_query($query, $connect);

        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 현재고
            $data_stock = class_stock::get_current_stock( $data[a_product_id], $stock_type );

            if( _DOMAIN_ == 'blueforce' )
            {
                // 창고재고 구하기
                $query_wh = "select sum(stock) sum_stock, sum(bad) sum_bad from current_stock_wh where product_id='$data[a_product_id]' group by product_id";
                $result_wh = mysql_query($query_wh, $connect);
                $data_wh = mysql_fetch_assoc($result_wh);
                
                $data_stock_wh = ($stock_type ? $data_wh[sum_bad] : $data_wh[sum_stock] ) + $data_stock;
            }

            if( _DOMAIN_ == 'ilovej' )
            {
                // 접수수량
                $query_status1 = "select sum(b.qty) sum_b_qty 
                                    from orders a,
                                         order_products b
                                   where a.seq = b.order_seq and
                                         a.status = 1 and
                                         b.order_cs not in (1,2) and
                                         b.product_id = '$data[a_product_id]'";
    
                // 판매처조건
                if( $str_shop_code )
                    $query_status1 .= " and a.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_status1 .= " and a.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";
                    
                $result_status1 = mysql_query($query_status1, $connect);
                $data_status1 = mysql_fetch_assoc($result_status1);

                // 가용재고 구하기
                $stock_stand_by = $data_stock - $data_status1[sum_b_qty];
                
                // 매장재고 구하기
                $query_wh = "select sum(qty) sum_qty, sum(move) sum_move from ecn_current_stock where product_id='$data[a_product_id]' and bad=$stock_type";
                $result_wh = mysql_query($query_wh, $connect);
                $data_wh = mysql_fetch_assoc($result_wh);
                
                // 본사이동
                $query_move = "select move from current_stock where product_id='$data[a_product_id]' and bad=$stock_type";
                $result_move = mysql_query($query_move, $connect);
                $data_move = mysql_fetch_assoc($result_move);
                
                $data_stock_ecn = $data_wh['sum_qty']+$data_wh['sum_move']+$data_move['move'];
                
                // 매장총판매 구하기
                $query_sell = "select sum(qty) sum_qty from ecn_stock_tx_history where product_id='$data[a_product_id]' and bad=0 and work='SHOP_SELL'";
                $result_sell = mysql_query($query_sell, $connect);
                $data_sell = mysql_fetch_assoc($result_sell);
                
                $stock_tot_sell = $data_sell['sum_qty'];
                
                // 매장기간판매 구하기
                $query_sell_period = "select sum(qty) sum_qty from ecn_stock_tx_history where product_id='$data[a_product_id]' and bad=0 and work='SHOP_SELL' and crdate>='$start_date 00:00:00' and crdate<='$end_date 23:59:59'";
                $result_sell_period = mysql_query($query_sell_period, $connect);
                $data_sell_period = mysql_fetch_assoc($result_sell_period);
                
                $stock_period_sell = $data_sell_period['sum_qty'];
                
                // 총입고 구하기
                $query_in = "select sum(stockin) sum_qty from stock_tx where product_id='$data[a_product_id]' and bad=$stock_type";
                $result_in = mysql_query($query_in, $connect);
                $data_in = mysql_fetch_assoc($result_in);
                
                $stock_tot_in = $data_in['sum_qty'];
                
                // 기간입고 구하기
                $query_in = "select sum(stockin) sum_qty from stock_tx where product_id='$data[a_product_id]' and bad=$stock_type and crdate>='$start_date' and crdate<='$end_date' ";
                $result_in = mysql_query($query_in, $connect);
                $data_in = mysql_fetch_assoc($result_in);
                
                $stock_prd_in = $data_in['sum_qty'];

                $stockin_first = ""; 
                $stockin_last = ""; 
                if( $work_type == 'order_0' )  
                {
                    // 최초입고
                    $query_in = "select crdate from stock_tx where product_id='$data[a_product_id]' and stockin>0 order by crdate limit 1";
                    $result_in = mysql_query($query_in, $connect);
                    if( mysql_num_rows($result_in) )
                    {
                        $data_in = mysql_fetch_assoc($result_in);
                        $stockin_first = $data_in[crdate]; 
                    }
                    
                    // 최종입고
                    $query_in = "select crdate from stock_tx where product_id='$data[a_product_id]' and stockin>=15 order by crdate desc limit 1";
                    $result_in = mysql_query($query_in, $connect);
                    if( mysql_num_rows($result_in) )
                    {
                        $data_in = mysql_fetch_assoc($result_in);
                        $stockin_last = $data_in[crdate]; 
                    }
                }
            }

            $query_trans = "select sum(a.qty) sum_trans
                              from stock_tx_history a
                                  ,orders b
                             where a.order_seq = b.seq
                               and a.job = 'trans'
                               and a.product_id='$data[a_product_id]'
                               and a.crdate>='$start_date 00:00:00' 
                               and a.crdate<='$end_date 23:59:59'  ";

            // 판매처조건
            if( $str_shop_code )
                $query_trans .= " and b.shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query_trans .= " and b.shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query .= " and b.shop_product_id = '$shop_product_id' ";

            $result_trans = mysql_query($query_trans, $connect);
            $data_trans = mysql_fetch_assoc($result_trans);
            $sum_trans  = $data_trans[sum_trans];
            
            // 미배송
            if( _DOMAIN_ == 'beginning' )
            {
                $query_notrans = "select sum(b.qty) sum_b_qty 
                                    from orders a,
                                         order_products b
                                   where a.seq = b.order_seq and
                                         a.status = 1 and
                                         b.order_cs not in (1,2) and
                                         b.product_id = '$data[a_product_id]'";
    
                // 판매처조건
                if( $str_shop_code )
                    $query_notrans .= " and a.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_notrans .= " and a.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";
                    
                $result_notrans = mysql_query($query_notrans, $connect);
                $data_notrans = mysql_fetch_assoc($result_notrans);
                $sum_no_trans1 = $data_notrans[sum_b_qty];


                $query_notrans = "select sum(b.qty) sum_b_qty 
                                    from orders a,
                                         order_products b
                                   where a.seq = b.order_seq and
                                         a.status = 7 and
                                         b.order_cs not in (1,2) and
                                         b.product_id = '$data[a_product_id]'";
    
                // 판매처조건
                if( $str_shop_code )
                    $query_notrans .= " and a.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_notrans .= " and a.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";
                    
                $result_notrans = mysql_query($query_notrans, $connect);
                $data_notrans = mysql_fetch_assoc($result_notrans);
                $sum_no_trans2 = $data_notrans[sum_b_qty];
            }
            else
            {
                $query_notrans = "select sum(b.qty) sum_b_qty 
                                    from orders a,
                                         order_products b
                                   where a.seq = b.order_seq and
                                         a.status < 8 and
                                         b.order_cs not in (1,2) and
                                         b.product_id = '$data[a_product_id]'";
    
                // 판매처조건
                if( $str_shop_code )
                    $query_notrans .= " and a.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_notrans .= " and a.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";
                    
                $result_notrans = mysql_query($query_notrans, $connect);
                $data_notrans = mysql_fetch_assoc($result_notrans);
                $sum_no_trans = $data_notrans[sum_b_qty];
            }
            
            // 평균발주
            $query_day = "select sum(qty) sum_qty from order_products 
                           where product_id = '$data[a_product_id]' and 
                                 match_date >= '$start_date 00:00:00' and 
                                 match_date <= '$end_date 23:59:59'";

            // 판매처조건
            if( $str_shop_code )
                $query_day .= " and shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query_day .= " and shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query .= " and shop_product_id = '$shop_product_id' ";

            $result_day = mysql_query($query_day, $connect);
            $data_day = mysql_fetch_assoc($result_day);
            $avg_order = $data_day[sum_qty]/$period;

            // 7일 발주
			$yester_day = date("Y-m-d", strtotime("-1 day"));
            $days_7_ago = date("Y-m-d", strtotime("-7 day"));

			$diff_date1 = explode("-",$data[a_sale_date]); 
			$diff_date2 = explode("-",$days_7_ago); 
			
			$tm1 = mktime(0,0,0,$diff_date1[1],$diff_date1[2],$diff_date1[0]); 
			$tm2 = mktime(0,0,0,$diff_date2[1],$diff_date2[2],$diff_date2[0]);
			$diff_days = 7;
			if( ($tm1 - $tm2) / 86400 > 0)
			{
				$diff_days = ($tm1 - $tm2) / 86400 + 1;
				$days_7_ago = $data[a_sale_date];
			}
            $query_day2 = "select sum(qty) sum_qty from order_products 
                           where product_id = '$data[a_product_id]' and 
                                 match_date >= '$days_7_ago 00:00:00' and 
                                 match_date <= '$yester_day 23:59:59'";

            // 판매처조건
            if( $str_shop_code )
                $query_day2 .= " and shop_id in ($str_shop_code) ";
            if( $shop_group_str )
                $query_day2 .= " and shop_id in ($shop_group_str) ";
            if( $shop_product_id )
                $query .= " and shop_product_id = '$shop_product_id' ";

            $result_day2 = mysql_query($query_day2, $connect);
            $data_day2 = mysql_fetch_assoc($result_day2);

            // 기간입고
            $query_stockin = "select sum(stockin) sum_stockin from stock_tx where product_id='$data[a_product_id]' and crdate>='$start_date' and crdate<='$end_date'";
            $result_stockin = mysql_query($query_stockin, $connect);
            $data_stockin = mysql_fetch_assoc($result_stockin);

            // 평균입고
            $avg_stockin = $data_stockin[sum_stockin]/$period;
            
            // 평균배송
            $avg_trans = $data_trans[sum_trans]/$period;

            $arr_temp = array();
            $arr_temp['supply'			] = $data[b_name];
            $arr_temp['supply_tel'		] = $data[b_tel] . " / " . $data[b_mobile];
            $arr_temp['supply_address'	] = $data[b_address1] . " " . $data[b_address2];
            $arr_temp['category'		] = ($_SESSION[MULTI_CATEGORY] ? htmlspecialchars(class_multicategory::get_category_str($data[a_str_category])) : class_category::get_category_name($data[a_category]) );
            $arr_temp['a_product_id'	] = $data[a_product_id];
            $arr_temp['a_barcode'		] = $data[a_barcode];
            if( _DOMAIN_ == 'beginning' || _DOMAIN_ == 'hm3pl' || _DOMAIN_ == 'nawoo'  || _DOMAIN_ == 'flyday' )
            {
            	$arr_temp['name'		] = $data[a_name];
            	$arr_temp['options'		] = $data[a_options];
            	$arr_temp['brand'		] = $data[a_brand];
            	$arr_temp['supply_options'] = $data[a_supply_options];
            	$arr_temp['sold_out'	] = ($data[a_enable_sale] ? "" : "품절");
            }
            else 
            {
            	$arr_temp['name'		] = $data[a_name] . " " . $data[a_options];
            	$arr_temp['brand'		] = $data[a_brand] . " " . $data[a_supply_options];
            }

            if( _DOMAIN_ == 'ilovej' )
            {
            	$arr_temp['origin'		] = $data[a_origin];
            }
            
            $arr_temp['prd_memo'		] = $data[a_memo];
            $arr_temp['reg_date'		] = $data[a_reg_date];
            $arr_temp['org_price'		] = $data[a_org_price];
            $arr_temp['stock'			] = $data_stock;
            $arr_temp['stock_wh'		] = $data_stock_wh;
            
            if( _DOMAIN_ != 'beginning' && _DOMAIN_ != 'hm3pl' && _DOMAIN_ != 'nawoo' )
            {
	            $arr_temp['data_stand_by' ] = $stock_stand_by;
	            $arr_temp['data_stock_ecn'] = $data_stock_ecn;
	            $arr_temp['stock_tot_sell'] = $stock_tot_sell;
	            $arr_temp['stock_period_sell'] = $stock_period_sell;
	            $arr_temp['stock_tot_in'  ] = $stock_tot_in;
	            $arr_temp['stock_prd_in'  ] = $stock_prd_in;
        	}
        	
        	if( _DOMAIN_ == 'ilovej' )
        	{
	            $arr_temp['stockin_first'  ] = $stockin_first;
	            $arr_temp['stockin_last'  ] = $stockin_last;
        	}
        	
            $arr_temp['sum_trans'] = $sum_trans;
            if( _DOMAIN_ == 'beginning' )
            {
            	$arr_temp['sum_no_trans1'] = $sum_no_trans1;
            	$arr_temp['sum_no_trans2'] = $sum_no_trans2;
            }
            else 
            {
             	$arr_temp['sum_no_trans'] = $sum_no_trans;
            }
            $arr_temp['avg_order'		] = ($avg_order   	  ? $avg_order   	: "");
            $arr_temp['avg_stockin'		] = ($avg_stockin 	  ? $avg_stockin 	: "");
            $arr_temp['avg_trans'		] = ($avg_trans  	  ? $avg_trans   	: "");
            $arr_temp['before7_order'	] = ($data_day2[sum_qty]/$diff_days   ? $data_day2[sum_qty]/$diff_days  : "");
            $arr_temp['remain_days'		] = $data_stock	   *$diff_days / $data_day2[sum_qty];
            
            // 입고예정
            if(_DOMAIN_ =='lalael2' || _DOMAIN_ =='ezadmin')
            {
            	$arr_temp['sum_exp_qty'		] = $this->get_expect_stockin_qty($data[a_product_id]);
        	}
        	
            $arr_temp['remain_days_wh'	] = $data_stock_wh *7 / $data_day2[sum_qty];


            
            if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
                unset($arr_temp[org_price]);

            // 일자별
            $arr = array();

            // 발주+배송
            if( $work_type == 'order_trans' )  
            {
                $arr_ot = array();
                
                // 발주
                $_date = $start_date;
                while( $_date <= $end_date )
                {
                    $query_day = "select sum(qty) sum_qty from order_products 
                                   where product_id = '$data[a_product_id]' and 
                                         match_date >= '$_date 00:00:00' and 
                                         match_date <= '$_date 23:59:59'";

                    // 판매처조건
                    if( $str_shop_code )
                        $query_day .= " and shop_id in ($str_shop_code) ";
                    if( $shop_group_str )
                        $query_day .= " and shop_id in ($shop_group_str) ";
                    if( $shop_product_id )
                        $query .= " and shop_product_id = '$shop_product_id' ";

                    $result_day = mysql_query($query_day, $connect);
                    $data_day = mysql_fetch_assoc($result_day);
                    
                    if( $data_day[sum_qty] )
                    {
                        $arr_ot[$_date]["order"] = $data_day[sum_qty];
                        $arr_ot[$_date]["trans"] = 0;
                    }
                    else
                    {
                        $arr_ot[$_date]["order"] = 0;
                        $arr_ot[$_date]["trans"] = 0;
                    }
                    
                    // 다음날
                    $_date = date("Y-m-d", strtotime("1 day", strtotime($_date)));
                }

                // 배송
                $query_day = "select date(a.crdate) a_collect_date
                                    ,sum(a.qty) b_qty
                                from stock_tx_history a
                                    ,orders b
                               where a.order_seq = b.seq
                                 and a.job = 'trans'
                                 and a.product_id='$data[a_product_id]'
                                 and a.crdate>='$start_date 00:00:00' 
                                 and a.crdate<='$end_date 23:59:59'  ";

                // 판매처조건
                if( $str_shop_code )
                    $query_day .= " and b.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_day .= " and b.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";

                $query_day .= " group by a_collect_date order by a_collect_date ";
                $result_day = mysql_query($query_day, $connect);
                while( $data_day = mysql_fetch_assoc($result_day) )
                    $arr_ot[$data_day[a_collect_date]]["trans"] = $data_day[b_qty];
            }
            
            // 발주
            else if( $work_type == 'order' )  
            {
                $_date = $start_date;
                while( $_date <= $end_date )
                {
                    $query_day = "select sum(qty) sum_qty from order_products 
                                   where product_id = '$data[a_product_id]' and 
                                         match_date >= '$_date 00:00:00' and 
                                         match_date <= '$_date 23:59:59'";

                    // 판매처조건
                    if( $str_shop_code )
                        $query_day .= " and shop_id in ($str_shop_code) ";
                    if( $shop_group_str )
                        $query_day .= " and shop_id in ($shop_group_str) ";
                    if( $shop_product_id )
                        $query .= " and shop_product_id = '$shop_product_id' ";

                    $result_day = mysql_query($query_day, $connect);
                    $data_day = mysql_fetch_assoc($result_day);
                    
                    $arr[$_date] = $data_day[sum_qty];
                    
                    // 다음날
                    $_date = date("Y-m-d", strtotime("1 day", strtotime($_date)));
                }
            }

            else if( $work_type == 'trans' )  
            {
                $query_day = "select date(a.crdate) a_collect_date
                                    ,sum(a.qty) b_qty
                                from stock_tx_history a
                                    ,orders b
                               where a.order_seq = b.seq
                                 and a.job = 'trans'
                                 and a.product_id='$data[a_product_id]'
                                 and a.crdate>='$start_date 00:00:00' 
                                 and a.crdate<='$end_date 23:59:59'  ";

                // 판매처조건
                if( $str_shop_code )
                    $query_day .= " and b.shop_id in ($str_shop_code) ";
                if( $shop_group_str )
                    $query_day .= " and b.shop_id in ($shop_group_str) ";
                if( $shop_product_id )
                    $query .= " and b.shop_product_id = '$shop_product_id' ";

                $query_day .= " group by a_collect_date order by a_collect_date ";
                $result_day = mysql_query($query_day, $connect);
                while( $data_day = mysql_fetch_assoc($result_day) )
                    $arr[$data_day[a_collect_date]] = $data_day[b_qty];
//debug($query_day);                    
            }

            // 발주(0 포함)
            else if( $work_type == 'order_0' )  
            {
                $_date = $start_date;
                while( $_date <= $end_date )
                {
                    $query_day = "select sum(qty) sum_qty from order_products 
                                   where product_id = '$data[a_product_id]' and 
                                         match_date >= '$_date 00:00:00' and 
                                         match_date <= '$_date 23:59:59'";

                    // 판매처조건
                    if( $str_shop_code )
                        $query_day .= " and shop_id in ($str_shop_code) ";
                    if( $shop_group_str )
                        $query_day .= " and shop_id in ($shop_group_str) ";
                    if( $shop_product_id )
                        $query .= " and shop_product_id = '$shop_product_id' ";

                    // 2014-03-27. ilovej 차이나 판매처는 취소건 제외.
                    if( _DOMAIN_ == 'ilovej' && $str_shop_code == 10084 )
                        $query_day .= " and order_cs not in (1,2,3,4) " ;

                    $result_day = mysql_query($query_day, $connect);
                    $data_day = mysql_fetch_assoc($result_day);
                    
                    $arr[$_date] = $data_day[sum_qty];
                    
                    // 다음날
                    $_date = date("Y-m-d", strtotime("1 day", strtotime($_date)));
                }
            }

            // 재고
            else 
            {
                $query_day = "select * from stock_tx 
                               where product_id = '$data[a_product_id]' and 
                                     bad = $stock_type and 
                                     crdate >= '$start_date' and 
                                     crdate <= '$end_date'
                               order by crdate";
                $result_day = mysql_query($query_day, $connect);
                while( $data_day = mysql_fetch_assoc($result_day) )
                {
                    if( $work_type == "HQ_RETURN" )
                        $_work_type = "hq_return"; 
                    else if( $work_type == "SHOP_REQ" )
                        $_work_type = "shop_req"; 
                    else
                        $_work_type = $work_type;

                    $arr[$data_day[crdate]] = $data_day[$_work_type];
                }
            }
            
            for($i=0; $i<$period; $i++)
            {
                $d = date("Y-m-d", strtotime($start_date."+".$i."day"));

                // 발주+배송
                if( $work_type == 'order_trans' )
                {
                    $val_order = number_format( $arr_ot[$d][order] );
                    $val_trans = number_format( $arr_ot[$d][trans] );
                    if( $val_order == 0 )  $val_order = "";
                    if( $val_trans == 0 )  $val_trans = "";

                    $arr_temp[$d] = array(
                        "order" => $val_order,
                        "trans" => $val_trans
                    );
                }
                
                // 주문 또는 재고
                else
                {
                    $val = number_format( $arr[$d] );
                    if( $val == 0 )  $val = "";
                    $arr_temp[$d] = $val;
                }
            }
            
            $arr_datas[] = $arr_temp;
            
            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }

		$current_time = date("YmdHis");
        $this->make_file_IE00( $arr_datas, "download_${current_time}.xls" );

        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "일자별재고조회다운로드[$start_date ~ $end_date]");

        echo "<script language='javascript'>parent.set_file('download_${current_time}.xls')</script>";
    }

    function make_file_IE00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $period, $start_date, $work_type;
        global $start_date2, $end_date2;

        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item>공급처</td>\n";
        $buffer .= "<td class=header_item>연락처</td>\n";
        $buffer .= "<td class=header_item>주소</td>\n";
        $buffer .= "<td class=header_item>카테고리</td>\n";
        $buffer .= "<td class=header_item>상품코드</td>\n";
        $buffer .= "<td class=header_item>바코드</td>\n";
        
        if( _DOMAIN_ == 'beginning' || _DOMAIN_ == 'hm3pl' || _DOMAIN_ == 'nawoo'  || _DOMAIN_ == 'flyday')
        {
            $buffer .= "<td class=header_item>상품명</td>\n";
            $buffer .= "<td class=header_item>옵션</td>\n";
            $buffer .= "<td class=header_item>공급처상품명</td>\n";
            $buffer .= "<td class=header_item>공급처옵션</td>\n";
            $buffer .= "<td class=header_item>품절</td>\n";
        }
        else
        {
            $buffer .= "<td class=header_item>상품명+옵션</td>\n";
            $buffer .= "<td class=header_item>공급처상품명+옵션</td>\n";
        }
        
        if( _DOMAIN_ == 'ilovej' )
        {
            $buffer .= "<td class=header_item>원산지</td>\n";
        }
        
        $buffer .= "<td class=header_item>상품메모</td>\n";
        $buffer .= "<td class=header_item>상품등록일</td>\n";

        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $buffer .= "<td class=header_item>원가</td>\n";
            
        $buffer .= "<td class=header_item>현재재고</td>\n";

        if( _DOMAIN_ == 'blueforce' )
            $buffer .= "<td class=header_item>전체재고</td>\n";

        if( _DOMAIN_ == 'ilovej' )
        {
            $buffer .= "<td class=header_item>가용재고</td>\n";
            $buffer .= "<td class=header_item>매장재고</td>\n";
            $buffer .= "<td class=header_item>매장총판매</td>\n";
            $buffer .= "<td class=header_item>매장기간판매</td>\n";
            $buffer .= "<td class=header_item>총입고</td>\n";
            $buffer .= "<td class=header_item>기간입고</td>\n";
            $buffer .= "<td class=header_item>최초입고</td>\n";
            $buffer .= "<td class=header_item>최종입고</td>\n";
        }

        $buffer .= "<td class=header_item>기간배송</td>\n";
        if( _DOMAIN_ == 'beginning' )
        {
            $buffer .= "<td class=header_item>접수</td>\n";
            $buffer .= "<td class=header_item>송장</td>\n";
        }
        else
            $buffer .= "<td class=header_item>미배송</td>\n";
            
        $buffer .= "<td class=header_item>조회기간<br>평균발주</td>\n";
        $buffer .= "<td class=header_item>조회기간<br>평균입고</td>\n";
        $buffer .= "<td class=header_item>조회기간<br>평균배송</td>\n";
        $buffer .= "<td class=header_item>직전7일<br>평균발주</td>\n";
        $buffer .= "<td class=header_item>소진일</td>\n";
        
if( _DOMAIN_ == 'lalael2' || _DOMAIN_ =='ezadmin' ) {
                $buffer .= "<td class=header_item>입고예정</td>\n";
}

        if( _DOMAIN_ == 'blueforce' )
            $buffer .= "<td class=header_item>전체재고<br>소진일</td>\n";

        for($i=0; $i<$period; $i++)
        {
            $d = date("Y-m-d", strtotime($start_date."+".$i."day"));
            $buffer .= "<td class=header_item>" . substr($d,5,5) . "</td>\n";
        }
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                // blueforce
                if( _DOMAIN_ != 'blueforce' )
                    if( $key == 'stock_wh' || $key == 'remain_days_wh' )  continue;

                // ilovej
                if( _DOMAIN_ != 'ilovej' )
                    if( $key == 'data_stand_by' || $key == 'data_stock_ecn' || $key == 'stock_tot_sell' || $key == 'stock_period_sell' || $key == 'stock_tot_in' || $key == 'stock_prd_in' || $key == 'stockin_first' || $key == 'stockin_last' )  continue;

                if( $key == 'supply' || $key == 'product_id' || $key == 'name' || $key == 'options' || $key == 'reg_date' || $key == 'brand' || $key == 'stockin_first' || $key == 'stockin_last' )
                    $buffer .= "<td class=str_item>$v</td>\n";

                // 날짜별 데이터
                else if( preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $key) )
                {
                    if( $work_type == 'order_trans' )
                    {
                        $buffer .= "<td class=num_item>
                                        <table border=1>
                                            <tr><td class=num_item>$v[order]</td></tr>
                                            <tr><td class=num_item>$v[trans]</td></tr>
                                        </table>
                                    </td>\n
                                   ";
                    }
                    else
                        $buffer .= "<td class=num_item>$v</td>\n";
                }
                else
                    $buffer .= "<td class=num_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_IE00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"stock_download.xls"));
    }    
    
    function change_inreq()
    {
        global $connect, $product_id, $qty;
        
        $val = array();
        
        $query = "select * from stockin_req where crdate='" . date("Y-m-d") . "' and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        
        // 기존에 값이 있는 경우
        if( mysql_num_rows($result) )
        {
            // 수량이 있으면 해당 수량으로 업데이트
            if( $qty > 0 )
                $query = "update stockin_req set qty='$qty' where crdate='" . date("Y-m-d") . "' and product_id='$product_id'";
            // 수량이 없으면 기존 데이터 삭제
            else
                $query = "delete from stockin_req where crdate='" . date("Y-m-d") . "' and product_id='$product_id'";
        }
        // 기존에 값이 없는 경우
        else
        {
            // 수량이 있으면 데이터 입력
            if( $qty > 0 )
                $query = "insert stockin_req set qty='$qty', crdate='" . date("Y-m-d") . "', product_id='$product_id'";
            // 수량이 없으면 그냥 리턴
            else
            {
                $val['error'] = 0;
                echo json_encode( $val );
                return;
            }
        }
        if( mysql_query($query, $connect) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
            
        echo json_encode( $val );
    }
	function reserve_qty()
    {
        global $connect, $product_id, $qty;
        
        $val = array();
        
        // 수량이 있으면 데이터 입력
        if( $qty >= 0 )
            $query = "UPDATE products SET reserve_qty='$qty' WHERE product_id='$product_id'";
        // 수량이 없으면 그냥 리턴
        else
        {
            $val['error'] = 0;
            echo json_encode( $val );
            return;
        }
        if( mysql_query($query, $connect) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
            
        echo json_encode( $val );
    }

}
?>
