<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_shop.php";
require_once "class_ui.php";
require_once "class_supply.php";
require_once "class_category.php";
require_once "class_multicategory.php";
require_once "class_table.php";
require_once "Classes/PHPExcel.php";

class class_IO00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function IO00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $date_type,$start_date, $end_date, $supply_group, $supply_code, $str_supply_code, $product_id, $name, $options, $enable_stock_type,
               $group_id, $shop_id, $str_shop_code,
               $order_status, $is_all, $except_soldout, $stock_option, $nostock_option, $stock_alarm1, $stock_alarm2, $include_no_order, $stock_minus,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $supply_name_search;

        global $product_qty_list, $work_no, $start_hour, $end_hour;
        global $multi_supply_group, $multi_supply;
        
        if( !$start_hour )  $start_hour = "00:00:00";
        if( !$end_hour )  $end_hour = "23:59:59";

        // 조회 필드
        $page_code = 'IO00';
        $f = class_table::get_setting();
        
        if( $search )
        {
            // 재고부족 검색&저장
            if( !$work_no )
                $this->get_IO00_search();

            // 전체 쿼리
            $data_all = $this->get_IO00($f, &$total_rows, &$sum_arr);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        else
        {
            // 초기 검색 조건
            $page_code = 'IO00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
            
			// 발주기간
            if( $f_collect_date == 0 )
                $start_date = date("Y-m-d");
            else if( $f_collect_date == 1 )
                $start_date = date("Y-m-d", strtotime("-1 week"));
            else if( $f_collect_date == 2 )
                $start_date = date("Y-m-d", strtotime("-1 month"));
            else if( $f_collect_date == 3 )
                $start_date = date("Y-m-d", strtotime("-3 month"));
            else if( $f_collect_date == 4 )
                $start_date = date("Y-m-d", strtotime("-1 year"));
                
            // 재고관리
            $enable_stock_type = $f_enable_stock;
            
            // 미배송
            $order_status = $f_not_trans;
            
            // 체크박스
            $is_all           = $f_is_all          ;
            $except_soldout   = $f_except_soldout  ;
            $stock_option     = $f_stock_option    ;
            $nostock_option   = $f_nostock_option  ;
            $stock_alarm1     = $f_stock_alarm1    ;
            $stock_alarm2     = $f_stock_alarm2    ;
            $include_no_order = $f_include_no_order;
            $stock_minus      = $f_stock_minus     ;
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 메인 쿼리 - 저장하기
    //###############################
    function get_IO00_search()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $date_type, $start_date, $end_date, $supply_group, $supply_code, $str_supply_code, $product_id, $name, $options, $enable_stock_type,
               $group_id, $shop_id, $str_shop_code,
               $order_status, $is_all, $except_soldout, $stock_option, $nostock_option, $stock_alarm1, $stock_alarm2, $include_no_order, $stock_minus,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $supply_name_search;
        global $work_no, $start_hour, $end_hour, $date_time;
        global $multi_supply_group, $multi_supply;
        
        $this->show_wait($download);

        // 판매처 복수선택
        if ( $str_shop_code == "")
        {
            foreach( $shop_id as $_c )
            {
                $str_shop_code .= $str_shop_code ? "," : "";
                $str_shop_code .= $_c;
            }
        }
        
        if($date_type=="collect_date")
        	$date_time="collect_time";
        else
        	$date_time=$date_type;
        
        // 2일 전 데이터 삭제
        $date_1day = date("Y-m-d", strtotime("-1 day"));
        $query = "delete from in_req_bill_search where crdate<'$date_1day 00:00:00' ";
        mysql_query($query, $connect);

        // 작업번호
        $query = "select max(work_no) new_work_no from in_req_bill_search";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $work_no = $data[new_work_no]+1;

        // 재고부족 불러오기
        $query = "select c.product_id     c_product_id,
                         c.org_id         c_org_id,
                         c.stock_alarm1   c_stock_alarm1,
                         c.stock_alarm2   c_stock_alarm2,
                         c.reserve_qty    c_reserve_qty,
                         c.return_qty     c_return_qty,
                         sum(b.qty)       sum_b_qty
                    from orders a,
                         order_products b,
                         products c
                   where a.seq=b.order_seq and
                         b.product_id=c.product_id and
                         a.$date_type >= '$start_date' and
                         a.$date_type <= '$end_date' and
                         timestamp(date(a.$date_type), time(a.$date_time)) between '$start_date $start_hour' and '$end_date $end_hour' and
                         b.order_cs not in (1,2,3,4) and
                         c.is_delete=0 and
                         c.is_represent=0 ";

        // 공급처그룹
        if ( $supply_group )
        {
            $code_list = array();
            $query_sg = "select code from userinfo where group_id='$supply_group'";
            $result_sg = mysql_query($query_sg, $connect);
            while( $data_sg = mysql_fetch_assoc($result_sg) )
                $code_list[] = $data_sg[code];
                
            $query .= " and c.supply_code in (" . implode(",", $code_list) . ") ";
        }
           
        // 공급처명
        if ( $supply_name_search )
        {
            $code_list = array();
            $query_sg = "select code from userinfo where name like '%$supply_name_search%'";
            $result_sg = mysql_query($query_sg, $connect);
            while( $data_sg = mysql_fetch_assoc($result_sg) )
                $code_list[] = $data_sg[code];
                
            $query .= " and c.supply_code in (" . implode(",", $code_list) . ") ";
        }
           
        // 공급처
        if ( $str_supply_code )
            $query .= " and c.supply_code in ( $str_supply_code )";
        if ( $multi_supply )
            $query .= " and c.supply_code in ( $multi_supply )";

        // 판매처그룹
        if( $group_id )
        {
            $shop_id_arr = array();
            $query_group = "select shop_id from shopinfo where group_id='$group_id'";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $shop_id_arr[] = $data_group[shop_id];
                
            $query .= " and a.shop_id in (". implode(",", $shop_id_arr) .")";
        }
        
        // 판매처
        if( $str_shop_code )
            $query .= " and a.shop_id in ($str_shop_code) ";
        
        // 상품코드
        if ( $product_id )
            $query .= " and b.product_id = '$product_id'";
           
        // 상품명
        if( $name )
            $query .= " and c.name like '%$name%'";
        
        // 옵션
        if ( $options )
            $query .= " and c.options like '%$options%'";

        // 재고관리
        if( $enable_stock_type == 0 )
            $query .= " and c.enable_stock=1 ";
        else if( $enable_stock_type == 1 )
            $query .= " and c.enable_stock=0 ";

        // 미배송 상태
        if( $order_status == 1 )
            $query .=  " and a.status = 1 ";
        else if( $order_status == 2 )
            $query .=  " and a.status = 7 ";
        else if( $order_status == 3 )
            $query .=  " and a.status in (1,7) ";
                   
        // 품절제외
        if( $except_soldout )
            $query .= " and c.enable_sale=1";

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

        $query .= " group by c.product_id";
debug("재고부족2 불러오기 : " . $query);
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);

        $product_id_list = "";
        
        $start_time = time();
        $i = 1;
        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>show_txt( '조회중 : $i / $total_rows' )</script>";
                flush();
            }
            usleep(1000);
            
            // 현재고
            $cur_stock = class_stock::get_current_stock( $data[c_product_id] );
    
            $base_stock = 0;
            if( $stock_alarm1 )
                $base_stock = $data[c_stock_alarm1];
            else if( $stock_alarm2 )
                $base_stock = $data[c_stock_alarm2];
            
            // 이지체인 요청수량
            $ezchain_qty = $this->get_ezchain_lack_req_qty( $this->get_ezchain_req_qty($data[c_product_id]) );

            // 부족수량
            $lack_qty = $data[sum_b_qty] - $cur_stock + $base_stock + $ezchain_qty[lack_qty];
            $lack_qty = ( $lack_qty > 0 ? $lack_qty : 0 );

            // 예정수량
            $exp_qty = $this->get_expect_stockin_qty($data[c_product_id]);

            // 입고예정, 입고대기, 교환대기           
            $req_qty = $lack_qty - $exp_qty - $data[c_reserve_qty] - $data[c_return_qty] + $ezchain_qty[req_qty];
            if( $req_qty < 0 )  $req_qty = 0;
            
            if( _DOMAIN_ == 'fromh1' )  $req_qty = 0;

            // 요청수량
            if( _DOMAIN_ == 'box4u' )
                $lack_qty =  $cur_stock - $data[sum_b_qty];

            // 전체(부족수량 0 포함)이 아닐경우, 부족수량 0은 넘어간다.
            if( !$is_all && $lack_qty == 0 )  continue;

            // 목록에 추가
            $query_insert = "insert in_req_bill_search
                                set product_id = '$data[c_product_id]',
                                    org_id = '$data[c_org_id]',
                                    cur_stock = '$cur_stock',
                                    not_yet_deliv = '$data[sum_b_qty]',
                                    lack_qty = '$lack_qty',
                                    req_qty = '$req_qty',
                                    exp_qty = '$exp_qty',
                                    work_no = '$work_no',
                                    worker = '$_SESSION[LOGIN_NAME]',
                                    crdate = now()";
            mysql_query($query_insert, $connect);
            
            $product_id_list .= ($product_id_list ? "," : "") . "'" . $data[c_product_id] . "'";
        }
        
        // 2013-10-11 이 부분은 필요없는 부분으로 판단됨. 상품이 없어도 work_no 는 정상진행.
        // if( !$product_id_list )  $work_no = "";

        // 이지체인 요청수량
        if( $_SESSION[EZCHAIN_REQUEST] )
        {
            $query_prd = "select a.product_id      a_product_id,
                                 a.org_id          a_org_id,
                                 a.stock_alarm1    a_stock_alarm1,
                                 a.stock_alarm2    a_stock_alarm2,
                                 a.reserve_qty     c_reserve_qty,
                                 a.return_qty      c_return_qty,
                                 ifnull(sum(c.qty),0) b_stock
                            from products a
                                ,ecn_warehouse_out_req_sheet b
                                ,ecn_warehouse_out_req_item c                                
                           where " . ($product_id_list ? "a.product_id not in ($product_id_list) and " : "") ." 
                                 a.product_id=c.product_id
                             and b.seq = c.deliv_sheet_seq
                             and b.status = '$_SESSION[EZCHAIN_REQUEST_STATUS]'
                             and b.is_delete = 0
                             and a.is_delete = 0
                             and a.is_represent = 0 ";
			
			// 상품명
	        if( $name )
	            $query_prd .= " and a.name like '%$name%'";
	        
	        // 옵션
	        if ( $options )
	            $query_prd .= " and a.options like '%$options%'";
	
	        // 재고관리
	        if( $enable_stock_type == 0 )
	            $query_prd .= " and a.enable_stock=1 ";
	        else if( $enable_stock_type == 1 )
	            $query_prd .= " and a.enable_stock=0 ";
	
	        // 품절제외
	        if( $except_soldout )
	            $query_prd .= " and a.enable_sale=1";
	
	        // 카테고리
	        if( $category )
	            $query_prd .= " and a.category = '$category' ";

	        // 멀티 카테고리
	        if( $m_sub_category_1 )
	            $query_prd .= " and a.m_category1 = '$m_sub_category_1' ";
	        if( $m_sub_category_2 )
	            $query_prd .= " and a.m_category2 = '$m_sub_category_2' ";
	        if( $m_sub_category_3 )
	            $query_prd .= " and a.m_category3 = '$m_sub_category_3' ";
	            
	            
	            
            // 공급처그룹
            if ( $supply_group )
            {
                $code_list = array();
                $query_sg = "select code from userinfo where group_id='$supply_group'";
                $result_sg = mysql_query($query_sg, $connect);
                while( $data_sg = mysql_fetch_assoc($result_sg) )
                    $code_list[] = $data_sg[code];
                    
                $query_prd .= " and a.supply_code in (" . implode(",", $code_list) . ") ";
            }
               
            // 공급처
            if ( $str_supply_code )
                $query_prd .= " and a.supply_code in ( $str_supply_code )";
			if ( $multi_supply )
                $query_prd .= " and a.supply_code in ( $multi_supply )";
            // 재고관리
            if( $enable_stock_type == 0 )
                $query_prd .= " and a.enable_stock=1 ";
            else if( $enable_stock_type == 1 )
                $query_prd .= " and a.enable_stock=0 ";
    
            // 품절제외
            if( $except_soldout )
                $query_prd .= " and a.enable_sale=1";

            $query_prd .= " group by a.product_id
                            having b_stock > 0 ";
debug("이지체인 요청수량 : " . $query_prd);
            $result_prd = mysql_query($query_prd, $connect);
            $total_rows = mysql_num_rows($result);
            $i = 1;
            while( $data_prd = mysql_fetch_assoc($result_prd) )
            {
                $i++;
                $new_time = time();
                if( $new_time - $start_time > 0 )
                {
                    $start_time = $new_time;
                    echo str_pad(" " , 256); 
                    echo "<script type='text/javascript'>show_txt( '조회중(이지체인요청) : $i / $total_rows' )</script>";
                    flush();
                }
                usleep(1000);
        
                // 현재고
                $cur_stock = class_stock::get_current_stock( $data_prd[a_product_id] );

                // 이지체인 요청수량
                $ezchain_qty = $this->get_ezchain_lack_req_qty($data_prd[b_stock]);
    
                // 부족수량
                $lack_qty = $cur_stock * -1 + $ezchain_qty[lack_qty];
                $lack_qty = ( $lack_qty > 0 ? $lack_qty : 0 );
                
                // 예정수량
                $exp_qty = $this->get_expect_stockin_qty($data_prd[a_product_id]);
    
                // 입고예정, 입고대기, 교환대기           
                $req_qty = $lack_qty - $exp_qty - $data_prd[c_reserve_qty] - $data_prd[c_return_qty] + $ezchain_qty[req_qty];
                if( $req_qty < 0 )  $req_qty = 0;

                // 목록에 추가
                $query_insert = "insert in_req_bill_search
                                    set product_id = '$data_prd[a_product_id]',
                                        org_id = '$data_prd[a_org_id]',
                                        cur_stock = '$cur_stock',
                                        not_yet_deliv = 0,
                                        lack_qty = '$lack_qty',
                                        req_qty = '$req_qty',
                                        exp_qty = '".$this->get_expect_stockin_qty($data[a_product_id])."',
                                        work_no = '$work_no',
                                        worker = '$_SESSION[LOGIN_NAME]',
                                        crdate = now()";
                mysql_query($query_insert, $connect);

                $product_id_list .= ($product_id_list ? "," : "") . "'" . $data_prd[a_product_id] . "'";
            }
        }

        // 재고 있는 옵션, 없는 옵션 포합
        if( $product_id_list > '' && ($stock_option || $nostock_option) )
        {
            $query_prd = "select a.product_id     a_product_id,
                                 a.org_id         a_org_id,
                                 a.stock_alarm1   a_stock_alarm1,
                                 a.stock_alarm2   a_stock_alarm2,
                                 a.reserve_qty    c_reserve_qty,
                                 a.return_qty     c_return_qty,
                                 b.stock          b_stock
                            from products a left outer join current_stock b on a.product_id = b.product_id and b.bad=0,
                                 in_req_bill_search c
                           where a.org_id > '' and
                                 a.org_id = c.org_id and
                                 a.product_id not in ($product_id_list) and 
                                 c.work_no = '$work_no' and
                                 a.is_delete = 0 ";
    
            // 재고 기준수량
            $stock_base = 0;
            if( $stock_alarm1 )
                $stock_base = "a.stock_alarm1";
            else if( $stock_alarm2 )
                $stock_base = "a.stock_alarm2";
            
            if( $stock_option && !$nostock_option )
                $query_prd .= " and ifnull(b.stock,0) > $stock_base ";
            else if( !$stock_option && $nostock_option )
                $query_prd .= " and ifnull(b.stock,0) <= $stock_base ";
    
            // 재고관리
            if( $enable_stock_type == 0 )
                $query_prd .= " and a.enable_stock=1 ";
            else if( $enable_stock_type == 1 )
                $query_prd .= " and a.enable_stock=0 ";
    
            // 품절제외
            if( $except_soldout )
                $query_prd .= " and a.enable_sale=1";
                
            $query_prd .= " group by a.product_id";
            $result_prd = mysql_query($query_prd, $connect);
            $total_rows = mysql_num_rows($result);
            $i = 1;
            while( $data_prd = mysql_fetch_assoc($result_prd) )
            {
                $i++;
                $new_time = time();
                if( $new_time - $start_time > 0 )
                {
                    $start_time = $new_time;
                    echo str_pad(" " , 256); 
                    echo "<script type='text/javascript'>show_txt( '조회중(추가) : $i / $total_rows' )</script>";
                    flush();
                }
                usleep(1000);
        
                // 현재고
                $cur_stock = $data_prd[b_stock];
        
                $base_stock = 0;
                if( $stock_alarm1 )
                    $base_stock = $data_prd[a_stock_alarm1];
                else if( $stock_alarm2 )
                    $base_stock = $data_prd[a_stock_alarm2];
                    
                // 미배송
                $query_not = "select c.product_id     c_product_id,
                                     sum(b.qty)       sum_b_qty
                                from orders a,
                                     order_products b,
                                     products c
                               where a.seq=b.order_seq and
                                     b.product_id=c.product_id and
                                     b.product_id = '$data_prd[a_product_id]' and
                                     a.$date_type >= '$start_date' and
                                     a.$date_type <= '$end_date' and
                                     timestamp(date(a.$date_type), time(a.$date_time)) between '$start_date $start_hour' and '$end_date $end_hour' and
                                     b.order_cs not in (1,2,3,4) ";
                // 미배송 상태
                if( $order_status == 1 )
                    $query_not .=  " and a.status = 1 ";
                else if( $order_status == 2 )
                    $query_not .=  " and a.status = 7 ";
                else if( $order_status == 3 )
                    $query_not .=  " and a.status in (1,7) ";
                $query_not .= " group by c.product_id";

                $result_not = mysql_query($query_not, $connect);
                $data_not = mysql_fetch_assoc($result_not);
                $not_yet_qty = $data_not[sum_b_qty];
                
                // 부족수량
                $lack_qty = $not_yet_qty - $cur_stock + $base_stock;
                $lack_qty = ( $lack_qty > 0 ? $lack_qty : 0 );

                // 예정수량
                $exp_qty = $this->get_expect_stockin_qty($data_prd[a_product_id]);
    
                // 입고예정, 입고대기, 교환대기           
                $req_qty = $lack_qty - $exp_qty - $data_prd[c_reserve_qty] - $data_prd[c_return_qty];
                if( $req_qty < 0 )  $req_qty = 0;
                
                if( _DOMAIN_ == 'fromh1' )
                {
                    $lack_qty = 0;
                    $req_qty = 0;
                }

                // 목록에 추가
                $query_insert = "insert in_req_bill_search
                                    set product_id = '$data_prd[a_product_id]',
                                        org_id = '$data_prd[a_org_id]',
                                        cur_stock = '$cur_stock',
                                        not_yet_deliv = '$not_yet_qty',
                                        lack_qty = '$lack_qty',
                                        req_qty = '$req_qty',
                                        exp_qty = '".$this->get_expect_stockin_qty($data[a_product_id])."',
                                        work_no = '$work_no',
                                        worker = '$_SESSION[LOGIN_NAME]',
                                        crdate = now()";
                mysql_query($query_insert, $connect);
                
                $product_id_list .= ($product_id_list ? "," : "") . "'" . $data_prd[a_product_id] . "'";
            }
        }

        // 주문 없는 상품 포함( 단, "재고경고수량기준" 또는 "재고위험수량기준" 둘중에 한가지 체크)
//        if( $include_no_order && ($stock_alarm1 || $stock_alarm2) )

        // 2014-04-03 장경희. 주문없는상품폼함 설정시 재고경고수량기준 또는 재고위험수량기준 체크 안할 경우, 전체 상품 불러온다.
        if( $include_no_order )
        {
            $query_prd = "select a.product_id      a_product_id,
                                 a.org_id          a_org_id,
                                 a.stock_alarm1    a_stock_alarm1,
                                 a.stock_alarm2    a_stock_alarm2,
                                 a.reserve_qty     c_reserve_qty,
                                 a.return_qty      c_return_qty,
                                 ifnull(b.stock,0) b_stock
                            from products a left outer join current_stock b on a.product_id=b.product_id and b.bad=0
                           where " . ($product_id_list ? "a.product_id not in ($product_id_list) and " : "") ." 
                                 a.is_delete = 0 and
                                 a.is_represent = 0 "; 
           
	        // 상품명
	        if( $name )
	            $query_prd .= " and a.name like '%$name%'";
	        
	        // 옵션
	        if ( $options )
	            $query_prd .= " and a.options like '%$options%'";
	
	        // 재고관리
	        if( $enable_stock_type == 0 )
	            $query_prd .= " and a.enable_stock=1 ";
	        else if( $enable_stock_type == 1 )
	            $query_prd .= " and a.enable_stock=0 ";
	
	        // 품절제외
	        if( $except_soldout )
	            $query_prd .= " and a.enable_sale=1";
	
	        // 카테고리
	        if( $category )
	            $query_prd .= " and a.category = '$category' ";

	        // 멀티 카테고리
	        if( $m_sub_category_1 )
	            $query_prd .= " and a.m_category1 = '$m_sub_category_1' ";
	        if( $m_sub_category_2 )
	            $query_prd .= " and a.m_category2 = '$m_sub_category_2' ";
	        if( $m_sub_category_3 )
	            $query_prd .= " and a.m_category3 = '$m_sub_category_3' ";

            // 공급처그룹
            if ( $supply_group )
            {
                $code_list = array();
                $query_sg = "select code from userinfo where group_id='$supply_group'";
                $result_sg = mysql_query($query_sg, $connect);
                while( $data_sg = mysql_fetch_assoc($result_sg) )
                    $code_list[] = $data_sg[code];
                    
                $query_prd .= " and a.supply_code in (" . implode(",", $code_list) . ") ";
            }
               
            // 공급처
            if ( $str_supply_code )
                $query_prd .= " and a.supply_code in ( $str_supply_code )";
			if ( $multi_supply )
                $query_prd .= " and a.supply_code in ( $multi_supply )";
            // 재고 기준수량
            $stock_base = 0;
            if( $stock_alarm1 )
                $query_prd .= " and a.stock_alarm1 > 0 and ifnull(b.stock,0) < a.stock_alarm1 ";
            else if( $stock_alarm2 )
                $query_prd .= " and a.stock_alarm2 > 0 and ifnull(b.stock,0) < a.stock_alarm2 ";
    
            // 재고관리
            if( $enable_stock_type == 0 )
                $query_prd .= " and a.enable_stock=1 ";
            else if( $enable_stock_type == 1 )
                $query_prd .= " and a.enable_stock=0 ";
    
            // 품절제외
            if( $except_soldout )
                $query_prd .= " and a.enable_sale=1";

            $query_prd .= " group by a.product_id";
            $result_prd = mysql_query($query_prd, $connect);
            $total_rows = mysql_num_rows($result);
            $i = 1;
            while( $data_prd = mysql_fetch_assoc($result_prd) )
            {
                $i++;
                $new_time = time();
                if( $new_time - $start_time > 0 )
                {
                    $start_time = $new_time;
                    echo str_pad(" " , 256); 
                    echo "<script type='text/javascript'>show_txt( '조회중(추가) : $i / $total_rows' )</script>";
                    flush();
                }
                usleep(1000);
        
                // 현재고
                $cur_stock = $data_prd[b_stock];
        
                $base_stock = 0;
                if( $stock_alarm1 )
                    $base_stock = $data_prd[a_stock_alarm1];
                else if( $stock_alarm2 )
                    $base_stock = $data_prd[a_stock_alarm2];
                    
                // 부족수량
                $lack_qty = $base_stock - $cur_stock;
                $lack_qty = ( $lack_qty > 0 ? $lack_qty : 0 );
                
                // 예정수량
                $exp_qty = $this->get_expect_stockin_qty($data_prd[a_product_id]);
    
                // 입고예정, 입고대기, 교환대기           
                $req_qty = $lack_qty - $exp_qty - $data_prd[c_reserve_qty] - $data_prd[c_return_qty];
                if( $req_qty < 0 )  $req_qty = 0;
                
                if( _DOMAIN_ == 'fromh1' )
                {
                    $lack_qty = 0;
                    $req_qty = 0;
                }

                // 목록에 추가
                $query_insert = "insert in_req_bill_search
                                    set product_id = '$data_prd[a_product_id]',
                                        org_id = '$data_prd[a_org_id]',
                                        cur_stock = '$cur_stock',
                                        not_yet_deliv = 0,
                                        lack_qty = '$lack_qty',
                                        req_qty = '$req_qty',
                                        exp_qty = '".$this->get_expect_stockin_qty($data[a_product_id])."',
                                        work_no = '$work_no',
                                        worker = '$_SESSION[LOGIN_NAME]',
                                        crdate = now()";
                mysql_query($query_insert, $connect);

                $product_id_list .= ($product_id_list ? "," : "") . "'" . $data_prd[a_product_id] . "'";
            }
        }
        
        // 재고가 마이너스인 상품 모두 포함
        if( $stock_minus )
        {
        	
            $query_prd = "select a.product_id      a_product_id,
                                 a.org_id          a_org_id,
                                 a.stock_alarm1    a_stock_alarm1,
                                 a.stock_alarm2    a_stock_alarm2,
                                 a.reserve_qty     c_reserve_qty,
                                 a.return_qty      c_return_qty,
                                 ifnull(b.stock,0) b_stock
                            from products a, current_stock b
                           where " . ($product_id_list ? "a.product_id not in ($product_id_list) and " : "") ." 
                                 a.product_id=b.product_id
                             and b.bad=0
                             and a.is_delete = 0
                             and a.is_represent = 0 ";

        	if(_DOMAIN_ == "hanstyle")
                $query_prd .= " and a.memo != '' ";
            else
                $query_prd .= " and b.stock < 0 ";			
			
			// 상품명
	        if( $name )
	            $query_prd .= " and a.name like '%$name%'";
	        
	        // 옵션
	        if ( $options )
	            $query_prd .= " and a.options like '%$options%'";
	
	        // 재고관리
	        if( $enable_stock_type == 0 )
	            $query_prd .= " and a.enable_stock=1 ";
	        else if( $enable_stock_type == 1 )
	            $query_prd .= " and a.enable_stock=0 ";
	
	        // 품절제외
	        if( $except_soldout )
	            $query_prd .= " and a.enable_sale=1";
	
	        // 카테고리
	        if( $category )
	            $query_prd .= " and a.category = '$category' ";

	        // 멀티 카테고리
	        if( $m_sub_category_1 )
	            $query_prd .= " and a.m_category1 = '$m_sub_category_1' ";
	        if( $m_sub_category_2 )
	            $query_prd .= " and a.m_category2 = '$m_sub_category_2' ";
	        if( $m_sub_category_3 )
	            $query_prd .= " and a.m_category3 = '$m_sub_category_3' ";
	            
	            
	            
            // 공급처그룹
            if ( $supply_group )
            {
                $code_list = array();
                $query_sg = "select code from userinfo where group_id='$supply_group'";
                $result_sg = mysql_query($query_sg, $connect);
                while( $data_sg = mysql_fetch_assoc($result_sg) )
                    $code_list[] = $data_sg[code];
                    
                $query_prd .= " and a.supply_code in (" . implode(",", $code_list) . ") ";
            }
               
            // 공급처
            if ( $str_supply_code )
                $query_prd .= " and a.supply_code in ( $str_supply_code )";
			if ( $multi_supply )
                $query_prd .= " and a.supply_code in ( $multi_supply )";
            // 재고관리
            if( $enable_stock_type == 0 )
                $query_prd .= " and a.enable_stock=1 ";
            else if( $enable_stock_type == 1 )
                $query_prd .= " and a.enable_stock=0 ";
    
            // 품절제외
            if( $except_soldout )
                $query_prd .= " and a.enable_sale=1";

            $query_prd .= " group by a.product_id";
debug("재고가 마이너스인 상품 : " . $query_prd);
            $result_prd = mysql_query($query_prd, $connect);
            $total_rows = mysql_num_rows($result);
            $i = 1;
            while( $data_prd = mysql_fetch_assoc($result_prd) )
            {
                $i++;
                $new_time = time();
                if( $new_time - $start_time > 0 )
                {
                    $start_time = $new_time;
                    echo str_pad(" " , 256); 
                    echo "<script type='text/javascript'>show_txt( '조회중(마이너스) : $i / $total_rows' )</script>";
                    flush();
                }
                usleep(1000);
        
                // 현재고
                $cur_stock = $data_prd[b_stock];
                    
                // 부족수량
                $lack_qty = $cur_stock * -1;
                
                // 예정수량
                $exp_qty = $this->get_expect_stockin_qty($data_prd[a_product_id]);
    
                // 입고예정, 입고대기, 교환대기           
                $req_qty = $lack_qty - $exp_qty - $data_prd[c_reserve_qty] - $data_prd[c_return_qty];
                if( $req_qty < 0 )  $req_qty = 0;

                // 목록에 추가
                $query_insert = "insert in_req_bill_search
                                    set product_id = '$data_prd[a_product_id]',
                                        org_id = '$data_prd[a_org_id]',
                                        cur_stock = '$cur_stock',
                                        not_yet_deliv = 0,
                                        lack_qty = '$lack_qty',
                                        req_qty = '$req_qty',
                                        exp_qty = '".$this->get_expect_stockin_qty($data[a_product_id])."',
                                        work_no = '$work_no',
                                        worker = '$_SESSION[LOGIN_NAME]',
                                        crdate = now()";
                mysql_query($query_insert, $connect);

                $product_id_list .= ($product_id_list ? "," : "") . "'" . $data_prd[a_product_id] . "'";
            }
        }

        // 최소주문수량
        if( _DOMAIN_ == 'zangternet' || _DOMAIN_ == 'happysugar' )
        {
            $query = "select * from in_req_bill_search where work_no = '$work_no' ";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_prd = "select * from products where product_id='$data[product_id]' ";
                $result_prd = mysql_query($query_prd, $connect);
                $data_prd = mysql_fetch_assoc($result_prd);
                
                // 시장가에 최소주문수량 입력
                $new_qty = (int)ceil($data[req_qty] / $data_prd[market_price]) * $data_prd[market_price];
                
                $query_up = "update in_req_bill_search set req_qty = $new_qty where seq=$data[seq]";
                mysql_query($query_up, $connect);
            }
        }
        
        $this->hide_wait();
    }

    //###############################
    // 메인 쿼리 - 불러오기
    //###############################
    function get_IO00($f, &$total_rows, &$sum_arr, $is_download=0)
    {
        ini_set('memory_limit', '1000M');

        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $work_no, $order_status;
        global $ecn_stock, $ecn_w_info, $ecn_w_list;

        $this->show_wait($is_download);

        // 이지체인 창고재고조회
        $this->get_ecn_info();

        // 공급처 정보 배열
        $supply_info = class_supply::get_supply_arr();

        $query = "select * from in_req_bill_search where work_no = '$work_no'";
debug("재고만큼2 get_IO00 : " . $query);
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        $start_time = time();
    
        $sum_arr = array();
    
        // 전체 데이타
        $data_all = array();
        $i = 1;
        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                if( $is_download )
                    echo "<script type='text/javascript'>parent.show_txt( '$i / $total_rows' )</script>";
                else
                    echo "<script type='text/javascript'>show_txt( '자료 생성중 : $i / $total_rows' )</script>";
                flush();
            }
            usleep(1000);
            
            // 출력 정보 가져오기
            $temp_arr = $this->get_IO00_data_arr($data, $f, $supply_info);

            // 합계
            foreach( $f as $f_val )
            {
                if( $f_val[chk] )
                {
                    if( $f_val[use_sum] )
                        $sum_arr[$f_val[field_id]] += $temp_arr[$f_val[field_id]];
                }
            }
    
            $data_all[] = $temp_arr;
        }

        // 합계의 첫번째는 "합계"
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $sum_arr[$f_val[field_id]] = "합계";
                break;
            }
        }
    
        // 기본정렬
        $sort_arr = array();
        foreach( $f as $f_val )
        {
            if( $f_val[sort] > 0 )
            {
                $sort_arr[] = array(
                    "no"    => $f_val[sort],
                    "field" => $f_val[field_id]
                );
            }
        }
        // 정렬순서 정렬
        $sort_arr = $this->array_array_sort($sort_arr, "no");
    
        // 정렬 필드를 정렬 하여 배열로...
        $ss_arr = array();
        
        // 헤더 클릭
        if( $sort )
        {
            $ss_arr[] = $sort;
            if( $sort_order )
                $ss_arr[] = SORT_ASC;
            else
                $ss_arr[] = SORT_DESC;
        }

        foreach( $sort_arr as $s_val )
        {
            $ss_arr[] = $s_val[field];
            
            // 수량일 경우 역순정렬
            if( $s_val[field] == "org_price"     ||
                $s_val[field] == "stock"         ||
                $s_val[field] == "not_yet_deliv" ||
                $s_val[field] == "lack_qty"      ||
                $s_val[field] == "request_qty" )
                $ss_arr[] = SORT_DESC;
        }

        // 정렬필드 순으로 전체 데이터 정렬하기
        foreach ($ss_arr as $ss_key => $ss_val) 
        {
            if (is_string($ss_val)) 
            {
                $tmp = array();
                foreach ($data_all as $da_key => $da_val)
                    $tmp[$da_key] = $da_val[$ss_val];
                $ss_arr[$ss_key] = $tmp;
            }
        }
        $ss_arr[] = &$data_all;
        call_user_func_array('array_multisort', $ss_arr);

        return $data_all;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_IO00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $work_no, $order_status;

        // 조회 필드
        $page_code = 'IO00_file';
        $f = class_table::get_setting();

        // 전체 쿼리
        $data_all = $this->get_IO00($f, &$total_rows, &$sum_arr, 1);

        $data_all[] = $sum_arr;
        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_IO00( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_IO00( $data_all, $fn, $f )
    {
        global $connect;
        
        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        $col = 0;
        $row = 1;

        ini_set("memory_limit","256M");
            
        // 헤더 & 폭
        $cell_width = array();
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $sheet->getCellByColumnAndRow($col++, $row)->setValueExplicit($f_val[header], PHPExcel_Cell_DataType::TYPE_STRING);
                $cell_width[$f_val[field_id]] = strlen( iconv('utf-8','cp949',$f_val[header] ) );
            }
        }

        $end_col = PHPExcel_Cell::stringFromColumnIndex($col-1);
        
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFill()->getStartColor()->setARGB('FFCCFFCC');
        
        if( _DOMAIN_ == 'beginning' )
            $sheet->getStyle("A{$row}:{$end_col}{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        else
            $sheet->getStyle("A{$row}:{$end_col}{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFont()->setBold(true);
        
        foreach ($data_all as $data_val) {
            $row++;
            $col = 0;

            foreach( $f as $f_val )
            {
                if( !$f_val[chk] )  continue;
                
                $d_key = $f_val[field_id];
                $d_val = $data_val[$d_key];
                
                if( $f_val[tag] == "img" )
                    list($_temp, $d_val) = explode("|", $d_val);

                // 폭 계산
                $new_width = strlen( iconv('utf-8','cp949',$d_val) );
                if( $cell_width[$d_key] < $new_width )  
                    $cell_width[$d_key] = $new_width;

                class_table::print_xls($d_val, $f_val[is_num], &$sheet, $col, $row);
                $col++;
            }
        }
        $data_all = array();

        // 최종 폭 설정
        $col = 0;
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $col_idx = PHPExcel_Cell::stringFromColumnIndex($col++);
                $sheet->getColumnDimension($col_idx)->setWidth($cell_width[$f_val[field_id]]+2);
            }
        }

        // border
        
        // beginning 헤더 폰트 변경. 요청사항
        if( _DOMAIN_ == 'beginning' )
        {
            $styleArray = array(
            	'font' => array(
            		'name' => '굴림',
            		'size' => 10,
            	),
            	'borders' => array(
            		'allborders' => array(
            			'style' => PHPExcel_Style_Border::BORDER_THIN ,
            			'color' => array('argb' => 'FF000000'),
            		),
            	),
            );
            $sheet->getStyle('A1:'.$end_col."1")->applyFromArray($styleArray);

            $styleArray = array(
            	'font' => array(
            		'name' => '굴림',
            		'size' => 9,
            	),
            	'borders' => array(
            		'allborders' => array(
            			'style' => PHPExcel_Style_Border::BORDER_THIN ,
            			'color' => array('argb' => 'FF000000'),
            		),
            	),
            );
            $sheet->getStyle('A2:'.$end_col.$row)->applyFromArray($styleArray);
        }
        else
        {
            $styleArray = array(
            	'font' => array(
            		'name' => '굴림',
            		'size' => 9,
            	),
            	'borders' => array(
            		'allborders' => array(
            			'style' => PHPExcel_Style_Border::BORDER_THIN ,
            			'color' => array('argb' => 'FF000000'),
            		),
            	),
            );
            $sheet->getStyle('A1:'.$end_col.$row)->applyFromArray($styleArray);
        }
        
        $objPageSetup = new PHPExcel_Worksheet_PageSetup();
        $objPageSetup->setFitToPage(true);
        $objPageSetup->setFitToWidth(1);
        $objPageSetup->setFitToHeight(0);

        $sheet->setPageSetup($objPageSetup);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        return $filename;
    }

    //###############################
    // 다운로드
    //###############################
    function download_IO00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

    //###############################
    // 조회 필드 설정팝업
    //###############################
    function IO01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //
    // 발주 상품 내역 조회 - jk
    //
    function IO02()
    {
        global $template, $connect, $d_val, $product_id;
        
        // 숫자만 가져와야 함
        $digit = preg_replace("/([^0-9])/","",$d_val);
        $collect_date = date("Y-m-d", strtotime("-" . $digit . " days"));

        // S20442
        $query = "select a.seq, a.order_id, a.order_name, a.shop_id , b.qty
                    from orders a, order_products b
                   where a.seq  = b.order_seq
                     and a.collect_date = '$collect_date'                     
                     and b.product_id = '$product_id'";

     
        $result = mysql_query( $query, $connect );                     

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        echo "<div style='display:none'>$query</div>";
    }
    
    //###############################
    // 상품 추가
    //###############################
    function IO03()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_id, $supply_code, $supply_name, $name, $options, $brand, $supply_options;
        global $work_no;

        // 작업중
        $this->show_wait();

        // 페이지
        if( !$page )
            $page = 1;
        else
        {
            $line_per_page = 50;

            $name = trim( $name );
            $options = trim( $options );
            $supply_name = trim( $supply_name );
            $supply_options = trim( $supply_options );
            
            // link url
            $par = array('template','supply_id', 'supply_name', 'seq', 'name', 'options', 'work_no');
            $link_url = $this->build_link_url3( $par );
            
            $query = "select b.name       b_supply_name,
                             a.product_id a_product_id,
                             a.name       a_product_name,
                             a.options    a_options
                        from products a,
                             userinfo b
                       where a.supply_code = b.code and
                             a.is_delete = 0 and
                             a.is_represent = 0 ";
           
            if( $supply_id )
                $query .= " and a.supply_code = $supply_id ";
			if( $supply_code )
                $query .= " and a.supply_code = $supply_code ";

            if( $name )
                $query .= " and a.name like '%$name%' ";
                
            if( $options )
                $query .= " and a.options like '%$options%' ";
    
            if( $brand )
                $query .= " and a.brand like '%$brand%' ";
                
            if( $supply_options )
                $query .= " and a.supply_options like '%$supply_options%' ";
    
            // 전체 개수
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);
    
            // 정렬
            $query .= " order by b_supply_name, a_product_name, a_options ";
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
            $result = mysql_query($query, $connect);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 요청전표 만들기
    //###############################
    function IO04()
    {
        global $template, $connect, $work_no;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_IO00_data_arr($data, $f, $supply_info)
    {
        global $connect;
        global $work_no, $order_status;
        global $ecn_stock, $ecn_w_info, $ecn_w_list;
        global $date_type, $start_date, $end_date, $start_hour, $end_hour, $date_time;
        
        // 상품 상세
        $query_prd = "select * from products where product_id = '$data[product_id]'";
        $result_prd = mysql_query($query_prd, $connect);
        $data_prd = mysql_fetch_assoc($result_prd);

        // 상품 이미지
        $product_img = false;
        // 상품 이미지(소)
        $product_img_small = false;
        // 대표상품 등록일
        $org_reg_date = false;
        // 불량재고
        $use_bad_stock = false;
        // 반품수량
        $use_bad_return = false;
        // 가장 오래된 발주일
        $use_collect_date = false;
        // 과거발주수량
        $use_old_balju = false;
        // 과거발주수량-주
        $use_old_balju_week = false;
        // 대표상품코드
        $use_org_id = false;
        // 카테고리
        $use_category = false;        
        // 주문상태별 상품수량
        $use_status_qty = false;


        foreach($f as $f_val)
        {
            // 상품 이미지
            if( $f_val[field_id] == "product_img" )
                $product_img = true;
            // 상품 이미지(소)
            else if( $f_val[field_id] == "product_img_small" )
                $product_img_small = true;
            // 대표상품 등록일
            else if( $f_val[field_id] == "reg_date" && $data[org_id] > '' )
                $org_reg_date = true;
            // 불량재고
            else if( $f_val[field_id] == "stock_bad"               || 
                     $f_val[field_id] == "stock_bad_amount_org"    || 
                     $f_val[field_id] == "stock_bad_amount_supply" || 
                     $f_val[field_id] == "stock_bad_amount_shop" )
                $use_bad_stock = true;
            // 반품수량
            else if( $f_val[field_id] == "bad_return" )
                $use_bad_return = true;
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $use_collect_date = true;
            // 과거발주수량
            else if( $f_val[field_id] == "balju_0" || 
                     $f_val[field_id] == "balju_1" || 
                     $f_val[field_id] == "balju_2" || 
                     $f_val[field_id] == "balju_3" || 
                     $f_val[field_id] == "balju_4" || 
                     $f_val[field_id] == "balju_5" || 
                     $f_val[field_id] == "balju_6" || 
                     $f_val[field_id] == "balju_7" 
            )
                $use_old_balju = true;
            // 과거발주수량-주
            else if( $f_val[field_id] == "balju_1_week" || 
                     $f_val[field_id] == "balju_2_week" || 
                     $f_val[field_id] == "balju_3_week" || 
                     $f_val[field_id] == "balju_4_week" || 
                     $f_val[field_id] == "balju_5_week"
            )
                $use_old_balju_week = true;
            else if( $f_val[field_id] == "balju_ave" )
                $use_old_balju = true;
			else if( $f_val[field_id] == "org_id" )
                $use_org_id = true;
            else if( $f_val[field_id] == "category" )
                $use_category = true;
            // 주문상태별 상품수량
            else if( $f_val[field_id] == "status_0_qty" || 
                     $f_val[field_id] == "status_1_qty" || 
                     $f_val[field_id] == "status_7_qty" || 
                     $f_val[field_id] == "status_8_qty"
            )
            	$use_status_qty = true;
        }
        
        // 상품 이미지(대)
        if( $product_img )
            $product_img_str = $this->disp_image3_2( ($data_prd[org_id] > '' ? $data_prd[org_id] : $data_prd[product_id]), $data_prd[img_500] );

        // 상품 이미지(소)
        if( $product_img_small )
            $product_img_small_str = $this->disp_image3_2( ($data_prd[org_id] > '' ? $data_prd[org_id] : $data_prd[product_id]), $data_prd[img_500], 50 );

        // 품절 정보
        if( $data_prd[enable_sale] )
        {
            $enable_sale = "|";
            $sale_stop_date = "";
        }
        else
        {
            $enable_sale = "<img src='images/soldout.gif'>|품절";
            $sale_stop_date = $data_prd[sale_stop_date];
        }

        // 대표상품 등록일
        if( $org_reg_date )
        {
            $query_org = "select * from products where product_id='$data[org_id]'";
            $result_org = mysql_query($query_org, $connect);
            $data_org = mysql_fetch_assoc($result_org);
            
            $reg_date = $data_org[reg_date];
        }
        else
            $reg_date = $data_prd[reg_date];
        
        // 불량재고
        if( $use_bad_stock )
            $stock_bad = class_stock::get_current_stock( $data_prd[product_id], 1 );

        // 반품수량
        if( $use_bad_return )
        {
            // bad return 구하기
            $month_ago = date('Y-m-d', strtotime("-1 month"));
            $br_seq_list = "";
            $query_br = "select seq from sheet_out where crdate>'$month_ago 00:00:00' ";
            $result_br = mysql_query($query_br, $connect);
            while( $data_br = mysql_fetch_assoc($result_br) )
                $br_seq_list .= ($br_seq_list ? "," : "") . $data_br[seq];
                
            $query_br_tx = "select sum(qty) sum_qty 
                              from stock_tx_history 
                             where product_id = '$data_prd[product_id]' and 
                                   job = 'out' and
                                   sheet in ($br_seq_list) 
                          group by product_id";
            $result_br_tx = mysql_query($query_br_tx, $connect);
            $data_br_tx = mysql_fetch_assoc($result_br_tx);
        }

        // 가장 오래된 발주일
        if( $use_collect_date )
        {
            $query_cd = "select a.collect_date collect_date
                           from orders a, order_products b 
                          where a.seq=b.order_seq and";
                       
            // 미배송 상태
            if( $order_status == 1 )
                $query_cd .=  " a.status = 1 and ";
            else if( $order_status == 2 )
                $query_cd .=  " a.status = 7 and ";
            else if( $order_status == 3 )
                $query_cd .=  " a.status in (1,7) and ";
               
            $query_cd .=  "  b.order_cs not in (1,2,3,4) and
                             b.product_id='$data_prd[product_id]'
                    order by a.collect_date limit 1";

            $result_cd = mysql_query($query_cd, $connect);
            $data_cd = mysql_fetch_assoc($result_cd);
                            
            $old_collect_date = $data_cd[collect_date];
            
            if( _DOMAIN_ == 'dd0924' )
            {
                $query_date = "select datediff(date(now()),'$old_collect_date') as late_date";
                $result_date = mysql_query($query_date, $connect);
                $data_date = mysql_fetch_assoc($result_date);
                
                $old_collect_date = $data_date[late_date];
            }
        }
        //카테고리
        if($use_category)
        {
        	$str_categoty = $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data_prd[str_category]) : class_category::get_category_name( $data_prd[category] );
        }

        // 과거발주수량
        if( $use_old_balju )
        {
        	$old_day = date('Y-m-d', strtotime("-7 days"));
        	$old_balju_query ="SELECT collect_date, SUM(b.qty) as qty
        	                     FROM orders a, order_products b
        	                    WHERE a.seq = b.order_seq
        	                      AND b.product_id = '$data[product_id]'
        	                      AND b.order_cs not in(1,2,3,4)
        	                      AND collect_date >= '$old_day'
        	                 GROUP BY collect_date
        	                 ";
        	$old_balju_result = mysql_query($old_balju_query, $connect);
        	
        	$old_data = array();
        	$balju_ave_val = 0;
            while($old_balju_data = mysql_fetch_assoc($old_balju_result))
            {
            	$old_data[$old_balju_data[collect_date]] = $old_balju_data[qty];
            	$balju_ave_val += $old_balju_data[qty];
            }
        }
        
        // 과거발주수량-주
        if( $use_old_balju_week )
        {
        	$old_day_start = date('Y-m-d', strtotime("-35 days"));
        	$old_day_end = date('Y-m-d');

        	$old_balju_query ="SELECT collect_date, SUM(b.qty) as qty
        	                     FROM orders a, order_products b
        	                    WHERE a.seq = b.order_seq
        	                      AND b.product_id = '$data[product_id]'
        	                      AND b.order_cs not in(1,2,3,4)
        	                      AND collect_date >= '$old_day_start'
        	                      AND collect_date < '$old_day_end'
        	                 GROUP BY collect_date
        	                 ";
        	$old_balju_result = mysql_query($old_balju_query, $connect);
        	
        	$old_data_week = array(
        	    "-1" => 0,
        	    "-2" => 0,
        	    "-3" => 0,
        	    "-4" => 0,
        	    "-5" => 0,
        	);
            while($old_balju_data = mysql_fetch_assoc($old_balju_result))
            {
                if( date('Y-m-d', strtotime("-7 days")) <= $old_balju_data[collect_date] && $old_balju_data[collect_date] < date('Y-m-d') )
            	    $old_data_week["-1"] += $old_balju_data[qty];
                else if( date('Y-m-d', strtotime("-14 days")) <= $old_balju_data[collect_date] && $old_balju_data[collect_date] < date('Y-m-d', strtotime("-7 days")) )
            	    $old_data_week["-2"] += $old_balju_data[qty];
                else if( date('Y-m-d', strtotime("-21 days")) <= $old_balju_data[collect_date] && $old_balju_data[collect_date] < date('Y-m-d', strtotime("-14 days")) )
            	    $old_data_week["-3"] += $old_balju_data[qty];
                else if( date('Y-m-d', strtotime("-28 days")) <= $old_balju_data[collect_date] && $old_balju_data[collect_date] < date('Y-m-d', strtotime("-21 days")) )
            	    $old_data_week["-4"] += $old_balju_data[qty];
                else if( date('Y-m-d', strtotime("-35 days")) <= $old_balju_data[collect_date] && $old_balju_data[collect_date] < date('Y-m-d', strtotime("-28 days")) )
            	    $old_data_week["-5"] += $old_balju_data[qty];
            }
        }
        
        if($use_status_qty)
        {
        	$status_qty_query = "SELECT a.status status
        							  , SUM(b.qty) qty
        						   FROM orders a
        						   	  , order_products b
        						  WHERE a.seq = b.order_seq
        						    AND b.product_id = '$data_prd[product_id]'
        						    AND a.order_cs NOT IN (1,3)
        					   GROUP BY a.status";
			
			$status_qty = array();
			$status_qty[0] = 0;
			$status_qty[1] = 0;
			$status_qty[7] = 0;
			$status_qty[8] = 0;
			$status_qty_result = mysql_query($status_qty_query, $connect);
			while($status_qty_data = mysql_fetch_assoc($status_qty_result))
            {
            	$status_qty[$status_qty_data[status]]= $status_qty_data[qty];
            }
			
        }
        
         
    
        // 상품정보
        $product_info = array(
            "product_id"          => $data_prd[product_id],
            "org_id"          => $data_prd[org_id],
            "product_name"        => $data_prd[name],
            "options"             => $data_prd[options],
            "name_options"        => $data_prd[name] . " " . $data_prd[options],
            "supply_name_options" => $data_prd[brand] . " " . $data_prd[supply_options],
            "supply_product_name" => $data_prd[brand],
            "supply_options"      => $data_prd[supply_options],
            "barcode"             => $data_prd[barcode],
            "location"            => $data_prd[location],
            "product_img"         => $product_img_str,
            "product_img_small"   => $product_img_small_str,
            "product_memo"        => $data_prd[memo],
            "org_price"           => $data_prd[org_price],
            "supply_price"        => $data_prd[supply_price],
            "shop_price"          => $data_prd[shop_price],
            "stock"               => $data[cur_stock],
            "stock_amount_org"    => $data[cur_stock] * $data_prd[org_price],
            "stock_amount_supply" => $data[cur_stock] * $data_prd[supply_price],
            "stock_amount_shop"   => $data[cur_stock] * $data_prd[shop_price],
            "stock_bad"               => $stock_bad,
            "stock_bad_amount_org"    => $stock_bad * $data_prd[org_price],
            "stock_bad_amount_supply" => $stock_bad * $data_prd[supply_price],
            "stock_bad_amount_shop"   => $stock_bad * $data_prd[shop_price],
            "not_yet_deliv"       => $data[not_yet_deliv],
            "reg_date"            => $reg_date,
            "reg_date_option"     => $data_prd[reg_date],
            "enable_sale"         => $enable_sale,
            "sale_stop_date"      => $sale_stop_date,
            "stock_alarm1"		=> $data_prd[stock_alarm1],
            "stock_alarm2"      => $data_prd[stock_alarm2],
            "reserve_qty"		=> $data_prd[reserve_qty],
            "return_qty"		=> $data_prd[return_qty],
            "origin"            => $data_prd[origin],
            "usable_stock1"		=> $data[cur_stock] - $data[not_yet_deliv],
            "category"			=>$str_categoty
        );

        $temp_arr = array();
        foreach( $f as $f_val )
        {
            // 상품코드 - 무조건 추가
            if( $f_val[field_id] == "product_id" )
                $temp_arr[$f_val[field_id]] = $product_info[$f_val[field_id]];

            if( !$f_val[chk] && !$f_val[sort] )  continue;

            //+++++++++++++++++++
            // 공급처 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "supply" ) 
            {
                $temp_arr[$f_val[field_id]] = class_table::get_supply_arr_data($f_val[field_id], $supply_info, $data_prd[supply_code]);
                continue;
            }

            //+++++++++++++++++++
            // 상품 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "product" ) 
            {
                $temp_arr[$f_val[field_id]] = $product_info[$f_val[field_id]];
                continue;
            }

            // 부족수량
            if( $f_val[field_id] == "lack_qty" )
                $temp_arr[$f_val[field_id]] = $data[lack_qty];
            // 요청수량
            else if( $f_val[field_id] == "request_qty" )
                $temp_arr[$f_val[field_id]] = $data[req_qty];
            // 반품수량
            else if( $f_val[field_id] == "bad_return" )
                $temp_arr[$f_val[field_id]] = $data_br_tx[sum_qty];
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $temp_arr[$f_val[field_id]] = $old_collect_date;
            // 매출차트
            else if( $f_val[field_id] == "sale_chart" )
                $temp_arr[$f_val[field_id]] = "<img src='/images2/btn_timer_small.gif'>";
            // 매장 총 재고
            else if( $f_val[field_id] == "ecn_stock" )
            {
                // 매장재고 구하기
                $query_wh = "select sum(qty) sum_qty, sum(move) sum_move from ecn_current_stock where product_id='$data[product_id]' and bad=0";
                $result_wh = mysql_query($query_wh, $connect);
                $data_wh = mysql_fetch_assoc($result_wh);
                
                // 본사이동
                $query_move = "select move from current_stock where product_id='$data[product_id]' and bad=0";
                $result_move = mysql_query($query_move, $connect);
                $data_move = mysql_fetch_assoc($result_move);
                
                $temp_arr[$f_val[field_id]] = $data_wh['sum_qty']+$data_wh['sum_move']+$data_move['move'];
            }
            // 금일발주 0
            else if( $f_val[field_id] == "balju_0" )
            {
            	$old_day = date('Y-m-d', strtotime("-0 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            }   
            // 금일발주 1
            else if( $f_val[field_id] == "balju_1" )
            {            
                $old_day = date('Y-m-d', strtotime("-1 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            // 금일발주 2
            else if( $f_val[field_id] == "balju_2" )
            {
                $old_day = date('Y-m-d', strtotime("-2 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 3
            else if( $f_val[field_id] == "balju_3" )
            {
                $old_day = date('Y-m-d', strtotime("-3 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 4
            else if( $f_val[field_id] == "balju_4" )
            {
                $old_day = date('Y-m-d', strtotime("-4 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 5
            else if( $f_val[field_id] == "balju_5" )
            {
                $old_day = date('Y-m-d', strtotime("-5 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 6
            else if( $f_val[field_id] == "balju_6" )
            {
                $old_day = date('Y-m-d', strtotime("-6 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 7
            else if( $f_val[field_id] == "balju_7" )
            {
                $old_day = date('Y-m-d', strtotime("-7 days"));
                $temp_arr[$f_val[field_id]] = $old_data[$old_day];
            } 
            
            // 금일발주 -7 건 평균
            else if( $f_val[field_id] == "balju_ave" )
            {
            	$average = sprintf("%2.1f" ,$balju_ave_val/7);            	
                $temp_arr[$f_val[field_id]] = $average;
            } 
            
            // 주단위 발주 -1
            else if( $f_val[field_id] == "balju_1_week" )
            {            
                $temp_arr[$f_val[field_id]] = $old_data_week["-1"];
            } 
            // 주단위 발주 -2
            else if( $f_val[field_id] == "balju_2_week" )
            {
                $temp_arr[$f_val[field_id]] = $old_data_week["-2"];
            } 
            // 주단위 발주 -3
            else if( $f_val[field_id] == "balju_3_week" )
            {
                $temp_arr[$f_val[field_id]] = $old_data_week["-3"];
            } 
            // 주단위 발주 -4
            else if( $f_val[field_id] == "balju_4_week" )
            {
                $temp_arr[$f_val[field_id]] = $old_data_week["-4"];
            } 
            // 주단위 발주 -5
            else if( $f_val[field_id] == "balju_5_week" )
            {
                $temp_arr[$f_val[field_id]] = $old_data_week["-5"];
            } 

            // 입고예정수량
            else if( $f_val[field_id] == "expect_qty" )
            {
                $temp_arr[$f_val[field_id]] = $data[exp_qty];
            } 
            // 발주수량 => 기간발주수량
            else if( $f_val[field_id] == "status_0_qty" )
            {
                // 발주수량 
                $query_balju_qty = "select ifnull(sum(b.qty),0) sum_b_qty
                                      from orders a,
                                           order_products b
                                     where a.seq=b.order_seq and
                                           a.$date_type >= '$start_date' and
                                           a.$date_type <= '$end_date' and
                                           timestamp(date(a.$date_type), time(a.$date_time)) between '$start_date $start_hour' and '$end_date $end_hour' and
                                           b.order_cs not in (1,2) and
                                           b.product_id = '$data[product_id]' ";
                $result_balju_qty = mysql_query($query_balju_qty, $connect);
                $data_balju_qty = mysql_fetch_assoc($result_balju_qty);
                
                $temp_arr[$f_val[field_id]] = $data_balju_qty[sum_b_qty];
            }
            // 접수수량
            else if( $f_val[field_id] == "status_1_qty" )
            {
                $temp_arr[$f_val[field_id]] = $status_qty[1];
            }
            // 송장수량
            else if( $f_val[field_id] == "status_7_qty" )
            {
                $temp_arr[$f_val[field_id]] = $status_qty[7];
            }
            // 배송수량
            else if( $f_val[field_id] == "status_8_qty" )
            {
                $temp_arr[$f_val[field_id]] = $status_qty[8];
            }
            // 이지체인창고1
            else if( $f_val[field_id] == "ecn_warehouse_stock1" )
            {
                if( $ecn_stock )
                {
                    $query_ecn = "select qty 
                                    from ecn_current_stock 
                                   where warehouse_seq = '" . $ecn_w_info[0][seq] . "'
                                     and product_id = '$data_prd[product_id]' 
                                     and bad = 0 ";
                    $result_ecn = mysql_query($query_ecn, $connect);
                    if( mysql_num_rows($result_ecn) )
                    {
                        $data_ecn = mysql_fetch_assoc($result_ecn);
                        $ecn_stock_qty = $data_ecn[qty];
                    }
                    else 
                        $ecn_stock_qty = 0;
                    $temp_arr[$f_val[field_id]] = $ecn_stock_qty;
                }
                else
                    $temp_arr[$f_val[field_id]] = "";
            }
            // 이지체인 요청수량
            else if( $f_val[field_id] == "ezchain_request" )
            {
                $temp_arr[$f_val[field_id]] = $this->get_ezchain_req_qty($data_prd[product_id]);
            }
        }
        
        // work_no
        $temp_arr["work_no"] = $work_no;

        // seq 번호
        $temp_arr["seq_no"] = $data[seq];

        // 공급처 코드
        $temp_arr["supply_code"] = $data_prd[supply_code];

        return $temp_arr;
    }    
    
    //###############################
    // 미배송 주문 팝업
    //###############################
    function IL10()
    {
        global $connect, $template;
        global $product_id, $start_date, $end_date, $order_status;
        
        // 상품정보
        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $product_info = mysql_fetch_assoc($result);

        // 공급처
        $supply_name = class_supply::get_name($product_info[supply_code]);

        // 수량
        $query = "select c.shop_name      c_shop_name,
                         a.order_id       a_order_id,
                         a.collect_date   a_collect_date,
                         a.recv_name      a_recv_name,
                         a.recv_tel       a_recv_tel,
                         a.recv_mobile    a_recv_mobile,
                         a.recv_address   a_recv_address,
                         a.product_name   a_product_name,
                         a.options        a_options,
                         b.qty            b_qty
                    from orders a, 
                         order_products b, 
                         shopinfo c
                   where a.seq = b.order_seq and 
                         a.shop_id = c.shop_id and
                         a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and
                         b.product_id='$product_id' and 
                         b.order_cs not in ( 1,2,3,4 ) ";
        // 상태
        switch( $order_status )
        {
            case "1": $query .= " and a.status = 1 "; break;
            case "2": $query .= " and a.status = 7 "; break;
            case "3": $query .= " and a.status in (1,7) "; break;
        }
        
        $query .= " order by c.sort_name, a.collect_date";
        $result = mysql_query($query, $connect);
        
        // 화면 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 변경내용저장
    //###############################
    function save_data_change()
    {
        global $connect, $work_no, $stockin_str;
        
        $val = array();

        foreach( explode(",",$stockin_str) as $p_val )
        {
            // [상품코드]필드1:값1$필드2:값2$필드3:값3$
            if( preg_match('/\[(.+)\](.+)/', $p_val, $matches ) )
            {
                // 상품코드
                $product_id = $matches[1];
                
                $query_set = "";

                // 필드1:값1$필드2:값2$필드3:값3$
                foreach( explode("$", $matches[2]) as $par_val )
                {
                    if( !$par_val ) continue;
                    
                    // 필드1:값1
                    list($f_name, $f_val) = explode(":", $par_val);
                    
                    // url 인코딩
                    if( $f_name == "request_memo" || $f_name == "stock_memo" || $f_name == "product_memo" )
                        $f_val = addslashes(urldecode( $f_val ));
                    
                    if( $f_name == "product_memo"  || $f_name == "reserve_qty")
                    {
                    	if( $f_name == "product_memo" )  $f_name = "memo";
                    	if( $f_name == "reserve_qty" )  $f_name = "reserve_qty";
                    	
                        $query_memo = "update products set $f_name='$f_val' where product_id = '$product_id'";
                        mysql_query($query_memo, $connect);
                    }
                    else
                    {
                        // field 변경
                        if( $f_name == "request_qty" )  $f_name = "req_qty";
                        $query_set .= ($query_set ? "," : "") . "$f_name = '$f_val'";
                    }
                    
                }
                
                if( $query_set )
                {
                    $query = "update in_req_bill_search set $query_set where work_no=$work_no and product_id='$product_id'";
                    mysql_query($query, $connect);
                }
            }           
        }
    }

    //###############################
    // 변경내용리셋
    //###############################
    function reset_data()
    {
        global $connect, $work_no;

        $query = "update in_req_bill_search
                     set req_qty = lack_qty
                   where work_no = $work_no";
        mysql_query($query, $connect);
    }

    //###############################
    // 상품 추가
    //###############################
    function add_product()
    {
        global $connect, $work_no, $product_id, $req_qty;
        
        $val = array();
        
        // 이미 추가된 상품인지 확인
        $query = "select * from in_req_bill_search where work_no=$work_no and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 2;
        }
        else
        {
            // 추가
            $query = "insert in_req_bill_search
                         set product_id    = '$product_id',
                             req_qty       = '$req_qty',
                             exp_qty = '".$this->get_expect_stockin_qty($product_id)."',
                             work_no       = '$work_no',
                             worker        = '$_SESSION[LOGIN_NAME]',
                             crdate        = now(),
                             is_add        = 1";
            if( mysql_query( $query, $connect ) )
                $val['error'] = 0;
            else
            {
                $val['error'] = 1;
debug("상품추가 오류 : " . $query);
            }
        }
        
        echo json_encode( $val );
    }

    //###############################
    // 전표 생성
    //###############################
    function save_bill()
    {
        global $connect, $title, $work_no;
        
        $val = array();
        
        // 전표중복확인
        $query_check = "select * from in_req_bill where title='$title' ";
        $result_check = mysql_query($query_check, $connect);
        if( mysql_num_rows($result_check) )
        {
            $val[error] = 1;
            echo json_encode($val);
            exit;
        }

        $query1 = "insert into in_req_bill 
                      set crdate=Now()
                         ,crtime=Now()
                         ,title = '$title'
                         ,status = 1
                         ,owner = '$_SESSION[LOGIN_NAME]' ";
        mysql_query( $query1, $connect );
        
        // 입력된 seq를 찾는다
        $query = "select last_insert_id() a from in_req_bill;";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $bill_seq = $data[a];

        // 공급처 정보 리스트
        $supply_arr = class_supply::get_supply_arr();

        $query = "select a.product_id    a_product_id,
                         b.supply_code   b_supply_code,
                         b.name          b_name,
                         b.options       b_options,
                         b.brand         b_brand,
                         a.cur_stock     a_cur_stock,
                         a.not_yet_deliv a_not_yet_deliv,
                         a.lack_qty      a_lack_qty,
                         a.req_qty       a_req_qty,
                         a.is_add        a_is_add
                    from in_req_bill_search a,
                         products b
                   where a.work_no = '$work_no' and
                         a.product_id = b.product_id ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( _DOMAIN_ == 'fromh1' && $data[a_req_qty] == 0 )  continue;
            
            $query = "insert in_req_bill_item 
                         set bill_seq      = $bill_seq
                            ,supply_id     = $data[b_supply_code]
                            ,supply_name   = '" . addslashes( $supply_arr[$data[b_supply_code]]["supply_name"] ) . "'
                            ,product_id    = '$data[a_product_id]'
                            ,product_name  = '" . addslashes($data[b_name]) . "'
                            ,brand         = '" . addslashes($data[b_brand]) . "'
                            ,options       = '" . addslashes($data[b_options]) . "'
                            ,stock         = '$data[a_cur_stock]'
                            ,not_yet_deliv = '$data[a_not_yet_deliv]'
                            ,stock_sub     = '$data[a_lack_qty]'
                            ,req_stock     = '$data[a_req_qty]'
                            ,stockin       = '$data[a_req_qty]'";   
            mysql_query( $query, $connect );
        }

        $val[seq] = $bill_seq;
        echo json_encode($val);
    }

    //###############################
    // 공급처별 전표 생성
    //###############################
    function save_bill_each()
    {
        global $connect, $title, $work_no;

        $val = array();
        
        // 공급처 정보 리스트
        $supply_arr = class_supply::get_supply_arr();

        // 상품정보 리스트
        $arr_req = array();
        
        $query = "select a.product_id    a_product_id,
                         b.supply_code   b_supply_code,
                         b.name          b_name,
                         b.options       b_options,
                         b.brand         b_brand,
                         a.cur_stock     a_cur_stock,
                         a.not_yet_deliv a_not_yet_deliv,
                         a.lack_qty      a_lack_qty,
                         a.req_qty       a_req_qty,
                         a.is_add        a_is_add,
                         b.memo			 b_memo
                    from in_req_bill_search a,
                         products b
                   where a.work_no = '$work_no' and
                         a.product_id = b.product_id ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 요청수량이 0 이면 넘어감
            if( $data[a_req_qty] <= 0  )
            {
            	if((_DOMAIN_ == "hanstyle" ||_DOMAIN_ == "ezadmin" ) && $data[b_memo] != "")
            	{
            		//hanstyle은 수량이  0 이어도 메모가 있으면 넘어가짐	
            	}
            	else 
            	{
            		continue; 	
            	}
            }

            $arr_req[$data[b_supply_code]][] = array(
                product_id    => $data[a_product_id],
                product_name  => $data[b_name],
                brand         => $data[b_brand],
                options       => $data[b_options],
                stock         => $data[a_cur_stock],
                not_yet_deliv => $data[a_not_yet_deliv],
                stock_sub     => $data[a_lack_qty],
                req_stock     => $data[a_req_qty],
                is_add        => $data[a_is_add]
            );
        }
        
        // 공급처별 전표 생성 - 전표중복확인
        foreach( $arr_req as $supply_key => $supply_val )
        {
            $new_title = $title . addslashes("_" . $supply_arr[$supply_key]["supply_name"]);
            
            // 동일전표 생성 불가
            $query_check = "select * from in_req_bill where title='" . $new_title . "'";
            $result_check = mysql_query($query_check, $connect);
            if( mysql_num_rows($result_check) )
            {
                $val[error] = 1;
                echo json_encode($val);
                exit;
            }
        }

        // 공급처별 전표 생성
        foreach( $arr_req as $supply_key => $supply_val )
        {
            $new_title = $title . addslashes("_" . $supply_arr[$supply_key]["supply_name"]);
            
            // 전표 만들기
            $query1 = "insert in_req_bill 
                          set crdate=Now()
                             ,crtime=Now()
                             ,title = '" . $new_title . "'
                             ,status = 1
                             ,supply_code = $supply_key
                             ,owner = '" . $_SESSION[LOGIN_NAME] . "'";
            mysql_query( $query1, $connect );

            // 입력된 seq를 찾는다
            $query = "select last_insert_id() a from in_req_bill;";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $bill_seq = $data[a];

            // 전표에 각 상품을 넣는다.
            foreach( $supply_val as $p_val )
            {
                $query = "insert in_req_bill_item 
                             set bill_seq       = $bill_seq
                                 ,supply_id     = $supply_key
                                 ,supply_name   = '" . addslashes($supply_arr[$supply_key]["supply_name"]) . "'
                                 ,product_id    = '$p_val[product_id]'
                                 ,product_name  = '" . addslashes($p_val[product_name]) . "'
                                 ,brand         = '" . addslashes($p_val[brand]) . "'
                                 ,options       = '" . addslashes($p_val[options]) . "'
                                 ,stock         = '$p_val[stock]'
                                 ,not_yet_deliv = '$p_val[not_yet_deliv]'
                                 ,stock_sub     = '$p_val[stock_sub]'
                                 ,req_stock     = '$p_val[req_stock]'
                                 ,stockin       = '$p_val[req_stock]'";   
debug("공급처별 전표생성에 아이템 추가 : " . $query);
                mysql_query( $query, $connect );
            }
        }
        
        $val[error] = 0;
        echo json_encode($val);
  	}

    //###############################
    // 매출차트 
    //###############################
    function IO05()
    {
        global $template, $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 이지체인 요청수량
    //###############################
    function get_ezchain_req_qty($product_id)
    {
        global $connect;

        // 환경설정에서 설정해야만 조회
        if( !$_SESSION[EZCHAIN_REQUEST] )
            return $re;
        
        // 승인 상태의 전표
        $query = "select ifnull(sum(b.qty),0) sum_qty
                    from ecn_warehouse_out_req_sheet a
                        ,ecn_warehouse_out_req_item b
                   where a.status='$_SESSION[EZCHAIN_REQUEST_STATUS]' 
                     and a.seq = b.deliv_sheet_seq
                     and b.product_id = '$product_id' 
                     and a.is_delete = 0";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        return $data[sum_qty];
    }

    //###############################
    // 이지체인 요청수량 - 부족/요청 
    //###############################
    function get_ezchain_lack_req_qty($qty)
    {
        global $connect;

        // 이지체인 요청수량을 부족수량에 추가하지 않고 요청 수량에만 추가하는 업체
        $not_lack_but_req = 0;
        if( _DOMAIN_ == 'dabagirl2' )
            $not_lack_but_req = 1;
        
        $re = array(
            "lack_qty" => (!$not_lack_but_req ? $qty : 0)
           ,"req_qty"  => ( $not_lack_but_req ? $qty : 0)
        );
        
        return $re;
    }
}
?>
