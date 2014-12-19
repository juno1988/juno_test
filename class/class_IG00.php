<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_lock.php";
require_once "class_ui.php";

class class_IG00 extends class_top
{
    //###############################
    // 상품재고조회 화면
    //###############################
    function IG00()
    {
        global $template, $connect, $page, $line_per_page, $link_url, $template_page, $enable_org_price;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result;
        global $start_date, $end_date, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $s_group_id, $search_sort;
		global $multi_supply_group, $multi_supply;
		
        // trim
        $query_str = trim( $query_str );

        // 불량창고 이름
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        // 조회 필드
        $f = $this->get_setting(1);
                
        // 시작일
        if( !$start_date )
        {
            if( $template_page == "IH10" )
                $start_date = date("Y-m-d");
            else
                $start_date = date("Y-m-d", strtotime("-6 day"));
        }

        // 페이지
        if( !$page )
        {
            $page = 1;
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
            return;
        }
        
        // link url
        $par = array('template','query_type','query_str','products_sort','except_soldout','category','display_result',
                     'start_date','end_date','m_sub_category_1','m_sub_category_2','m_sub_category_3','multi_supply_group', 'multi_supply','str_supply_code');
        $link_url = $this->build_link_url3( $par );        ;

        if( $display_result == 999 )
            $line_per_page = 999999;
        else
            $line_per_page = $display_result;
            
        // 설정 저장
        $query_config = "update ez_config set display_result = $display_result";
        mysql_query($query_config, $connect);
        $_SESSION[DISPLAY_RESULT] = $display_result;

        if( !$template_page )
        {
            //+++++++++++++++++++++++++++++++++++++
            // 정상재고합, 정상재고금액합
            //+++++++++++++++++++++++++++++++++++++
            $query = "select sum(b.stock) sum_stock,
                             sum(b.stock * a.org_price) sum_price
                        from products a,
                             current_stock b
                       where a.is_represent = 0 and 
                             a.is_delete = 0 and 
                             a.enable_stock = 1 and
                             a.product_id = b.product_id and
                             b.bad = 0 ";
    
            if( $str_supply_code )
                $query .= " and a.supply_code in ( $str_supply_code ) ";
            
            if($multi_supply)
            	$query .= " and a.supply_code in ( $multi_supply ) ";
				
            // 상품 검색
            if( $query_str ) 
            {
                if( $query_type == 'product_id' )
                    $query .= " and a.product_id = '$query_str' ";
                    
                else if( $query_type == 'name' )
                    $query .= " and replace(a.name,' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), $query_str) . "%' ";
        
                else if( $query_type == 'options' )
                    $query .= " and a.options like '%" . str_replace(" ", "%", $query_str) . "%' ";
        
                else if( $query_type == 'name_options' )
                    $query .= " and replace(concat(a.name, a.options),' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), $query_str) . "%' ";
        
                else if( $query_type == 'barcode')
                    $query .= " and a.barcode = '$query_str' ";
    
                else if( $query_type == 'supply' )
                {
                    $supply_code_arr = array();
                    $query_supply = "select code from userinfo where name like '%".str_replace(array('%','_'), array('\\%','\\_'), $query_str)."%' ";
                    $result_supply = mysql_query($query_supply, $connect);
                    while( $data_supply = mysql_fetch_assoc($result_supply) )
                        $supply_code_arr[] = $data_supply[code];

                    $query .= " and a.supply_code in (".implode(",", $supply_code_arr).") ";
                }

                else if( $query_type == 'brand' )
                    $query .= " and a.brand like '%" . str_replace(" ", "%", $query_str) . "%' ";
        
                else if( $query_type == 'supply_options' )
                    $query .= " and a.supply_options like '%" . str_replace(" ", "%", $query_str) . "%' ";
    
                else if( $query_type == 'location' )
                    $query .= " and a.location = '$query_str' ";
                
                else if( $query_type == 'sheetname' )
                    $query .= " and s.name like '%$query_str%' ";

                else if( $query_type == 'memo' ) 
                {
                    if( $query_str > '' )
                        $query .= " and a.memo like '%$query_str%' ";
                    else
                        $query .= " and a.memo > '' ";
                }
    
                else if( $query_type == 'origin' )
                    $query .= " and a.origin like '%$query_str%' ";

            }
            
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
    
            // 품절
            if( $except_soldout == 1 )
                $query .= " and a.enable_sale=1 ";
            else if( $except_soldout == 2 )
                $query .= " and a.enable_sale=0 ";

            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $total_stock = $data[sum_stock];
            $total_stock_price = $data[sum_price];
        }

        // 전체 쿼리
        $query = $this->get_IG00();
        debug("상품재고조회 쿼리 : " .$query );
        $result = mysql_query($query, $connect);

        // 총 개수
        $total_rows = mysql_num_rows( $result );

        // 페이지 쿼리
        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
        $result = mysql_query($query, $connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 상품재고조회 쿼리
    //###############################
    function get_IG00()
    {
        global $template, $connect, $page, $line_per_page, $link_url, $template_page, $enable_org_price;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result;
        global $start_date, $end_date, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $s_group_id, $search_sort;
        global $multi_supply_group, $multi_supply;
        
        //+++++++++++++++++++++++++++++++++++++++++++++++++++
        // 정렬에 대표상품 등록일 유무에 따라 다른 쿼리
        //
        //     PRODUCTS_SORT
        //     1 : 상품명 
        //     2 : 공급처 > 상품명 
        //     3 : 등록일 > 상품명 
        //     4 : 등록일 > 공급처 > 상품명 
        //
        //     SORT_REG_DATE
        //     0 : 대표상품 등록일
        //     1 : 옵션상품 등록일
        //
        //+++++++++++++++++++++++++++++++++++++++++++++++++++
        if( ($_SESSION[PRODUCTS_SORT] == 3 || $_SESSION[PRODUCTS_SORT] == 4) && $_SESSION[SORT_REG_DATE] == 0 )
        {
            $query = "select a.product_id as a_product_id, 
            				 a.reserve_qty as a_stock_in_standby,
                             a.supply_code as a_supply_code,
                             a.barcode    as a_barcode,
                             a.maker    as a_maker,
                             a.name as a_name,
                             a.brand a_brand,
                             a.supply_options a_supply_options,
                             a.stock_alarm1,
                             a.stock_alarm2,
                             a.options as a_options,
                             a.product_gift as a_product_gift,
                             a.memo as a_memo, 
                             a.barcode as a_barcode, 
                             a.org_price as a_org_price,
                             a.supply_price as a_supply_price,
                             a.shop_price as a_shop_price,
                             a.stock_alarm1 as a_stock_alarm1, 
                             a.stock_alarm2 as a_stock_alarm2,
                             a.org_id as a_org_id,
                              ";

            if( _DOMAIN_ == 'au2' )
                $query .= "  a.req_stockin as a_req_stockin, ";

            $query .= "      c.name as c_name,";

            // 입고요청목록 & 입고요청상품
            if( $template_page == "IH10" || $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  d.seq d_seq,
                             d.crdate d_crdate,
                             d.qty d_qty,
                             d.memo d_memo, ";

            // 입고요청상품
            if( $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  s.name s_name,";

            // 입고수량
            if( $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  d.stockin1 d_stockin1,
                             d.stockin2 d_stockin2,
                             d.stockin3 d_stockin3,
                             d.stockin4 d_stockin4,
                             d.stockin5 d_stockin5,
                             d.stockin6 d_stockin6,
                             d.stockin7 d_stockin7,
                             d.stockin8 d_stockin8,
                             d.stockin9 d_stockin9,
                             d.stockin10 d_stockin10,";

            $query .= "      if(b.product_id is null, a.reg_date, b.reg_date) as a_reg_date 
            
                        from products a left outer join products b on (a.org_id=b.product_id),
                             userinfo c ";
        }
        else
        {
            $query = "select a.product_id as a_product_id, 
            				 a.reserve_qty as a_stock_in_standby,
                             a.supply_code as a_supply_code,
                             a.barcode    as a_barcode,
                             a.maker    as a_maker,
                             a.name as a_name,
                             a.brand a_brand,
                             a.supply_options a_supply_options,
                             a.stock_alarm1,
                             a.stock_alarm2,
                             a.options as a_options,
                             a.product_gift as a_product_gift,
                             a.memo as a_memo, 
                             a.barcode as a_barcode, 
                             a.org_price as a_org_price, 
                             a.supply_price as a_supply_price, 
                             a.shop_price as a_shop_price, 
                             a.stock_alarm1 as a_stock_alarm1, 
                             a.stock_alarm2 as a_stock_alarm2, 
                             a.org_id as a_org_id,
                             ";

            if( _DOMAIN_ == 'au2' )
                $query .= "  a.req_stockin as a_req_stockin, ";

            $query .= "      c.name as c_name,";

            // 입고요청목록 & 입고요청상품
            if( $template_page == "IH10" || $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  d.seq d_seq,
                             d.crdate d_crdate,
                             d.qty d_qty,
                             d.memo d_memo, ";

            // 입고요청상품
            if( $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  s.name s_name,";

            // 입고수량
            if( $template_page == "IH20" || $template_page == "IH30" )
                $query .= "  d.stockin1 d_stockin1,
                             d.stockin2 d_stockin2,
                             d.stockin3 d_stockin3,
                             d.stockin4 d_stockin4,
                             d.stockin5 d_stockin5,
                             d.stockin6 d_stockin6,
                             d.stockin7 d_stockin7,
                             d.stockin8 d_stockin8,
                             d.stockin9 d_stockin9,
                             d.stockin10 d_stockin10,";

            $query .= "      a.reg_date as a_reg_date 
                        from products a,
                             userinfo c ";
        }
        
        // 개별입고요청
        if( $template_page == "IH10" || $template_page == "IH20" || $template_page == "IH30" )
            $query .= ", stockin_req d";

        // 입고요청상품
        if( $template_page == "IH20" || $template_page == "IH30" )
            $query .= ", stockin_req_sheet s";

        // 사용자 원가조회불가 설정
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $enable_org_price = true;
        else 
            $enable_org_price = false;

        //++++++++++++++++++++++++
        // 쿼리 기본조건
        //
        //      - 대표상품 제외
        //      - 삭제상품 제외
        //      - 재고관리상품
        //
        //++++++++++++++++++++++++
        $query .= " where a.is_represent = 0 and 
                          a.is_delete = 0 and 
                          a.enable_stock = 1 and
                          a.supply_code = c.code ";

        // 입고요청목록 & 입고요청상품
        if( $template_page == "IH10" || $template_page == "IH20" || $template_page == "IH30" )
            $query .= " and a.product_id = d.product_id ";

        // 입고요청목록
        if( $template_page == "IH10" )
            $query .= " and d.sheet = 0 ";
        
        // 입고요청상품
        if( $template_page == "IH20" || $template_page == "IH30" )
            $query .= " and d.sheet = s.seq ";

        //++++++++++++++++++++++++
        // 복수 공급처 
        //++++++++++++++++++++++++
        if( $str_supply_code )
            $query .= " and a.supply_code in ( $str_supply_code ) ";
            
        if($multi_supply)
        	$query .= " and a.supply_code in ( $multi_supply ) ";


        //++++++++++++++++++++++++
        // 상품 검색
        //++++++++++++++++++++++++
        if( $query_str || $query_type == 'memo' ) 
        {
            if( $query_type == 'product_id' )
                $query .= " and a.product_id = '$query_str' ";
                
            else if( $query_type == 'name' )
                $query .= " and replace(a.name,' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), $query_str) . "%' ";
    
            else if( $query_type == 'options' )
                $query .= " and a.options like '%" . str_replace(" ", "%", $query_str) . "%' ";
    
            else if( $query_type == 'name_options' )
                $query .= " and replace(concat(a.name, a.options),' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), $query_str) . "%' ";

            else if( $query_type == 'barcode')
                $query .= " and a.barcode = '$query_str' ";

            else if( $query_type == 'supply' )
            {
                $supply_code_arr = array();
                $query_supply = "select code from userinfo where name like '%".str_replace(array('%','_'), array('\\%','\\_'), $query_str)."%' ";
                $result_supply = mysql_query($query_supply, $connect);
                while( $data_supply = mysql_fetch_assoc($result_supply) )
                    $supply_code_arr[] = $data_supply[code];

                $query .= " and a.supply_code in (".implode(",", $supply_code_arr).") ";
            }

            else if( $query_type == 'brand' )
                $query .= " and a.brand like '%" . str_replace(" ", "%", $query_str) . "%' ";
    
            else if( $query_type == 'supply_options' )
                $query .= " and a.supply_options like '%" . str_replace(" ", "%", $query_str) . "%' ";

            else if( $query_type == 'location' )
                $query .= " and a.location = '$query_str' ";
            
            else if( $query_type == 'sheetname' )
                $query .= " and s.name like '%$query_str%' ";

            else if( $query_type == 'memo' ) 
            {
                if( $query_str > '' )
                    $query .= " and a.memo like '%$query_str%' ";
                else
                    $query .= " and a.memo > '' ";
            }

            else if( $query_type == 'origin' )
                $query .= " and a.origin like '%$query_str%' ";

        }
        
        //++++++++++++++++++++++++
        // 카테고리
        //++++++++++++++++++++++++
        if( $category )
            $query .= " and a.category = '$category' ";

        //++++++++++++++++++++++++
        // 멀티 카테고리
        //++++++++++++++++++++++++
        if( $m_sub_category_1 )
            $query .= " and a.m_category1 = '$m_sub_category_1' ";
        if( $m_sub_category_2 )
            $query .= " and a.m_category2 = '$m_sub_category_2' ";
        if( $m_sub_category_3 )
            $query .= " and a.m_category3 = '$m_sub_category_3' ";

        //++++++++++++++++++++++++
        // 품절
        //++++++++++++++++++++++++
        if( $except_soldout == 1 )
            $query .= " and a.enable_sale=1 ";
        else if( $except_soldout == 2 )
            $query .= " and a.enable_sale=0 ";
            
        //++++++++++++++++++++++++
        // 정렬
        //++++++++++++++++++++++++
        if( _DOMAIN_ == 'ilovej' || _DOMAIN_ == 'polotown'  || $_SESSION[PRODUCT_ORDERBY] )
            $sort_c_options = "a_product_id";
        else
            $sort_c_options = "a_options";

        $query .= " order by ";
        
        // 입고차수 수량 정렬
        if( $search_sort >= 1 )
            $query .= " d_stockin{$search_sort} desc, ";
        
        // 입고요청상품
        if( $template_page == "IH20" || $template_page == "IH30" )
            $query .= " s.name, ";

        if ( $products_sort == 1 ) // 상품명
            $query .= " a_name, $sort_c_options";
        else if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= " c_name, a_name, $sort_c_options";
        else if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= " a_reg_date desc, a_name, $sort_c_options";
        else if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= " a_reg_date desc, c_name, a_name, $sort_c_options";
            
        else if ( $products_sort == 8 )  // 로케이션
            $query .= " a.location desc ";
        else if ( $products_sort == 9)  // 로케이션 > 상품명
            $query .= " a.location desc, a.name";
            
        return $query;
    }
    
    function save_file_IG00()
    {
        global $template, $connect, $page, $line_per_page, $link_url, $template_page, $enable_org_price;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result;
        global $start_date, $end_date, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $s_group_id, $search_sort;
		global $multi_supply_group, $multi_supply;
		
        $query = $this->get_IG00();
        $result = mysql_query($query, $connect);

        // 조회 필드
        $f = $this->get_setting(2);

        // 사용자 원가조회불가 설정
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $enable_org_price = true;
        else 
            $enable_org_price = false;

        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $stock_obj = new class_stock();

        // 기간 날짜 수
        $query_date = "select dateDIFF('$end_date','$start_date') dd";
        $result_date = mysql_query($query_date, $connect);
        $data_date = mysql_fetch_assoc($result_date);
        
        $date_diff = $data_date[dd]+1;
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_temp = array();

            // 정상재고
            if( $f[stock_normal] || $f[stock_normal_price] ) 
            {
                $val_stock1 = $stock_obj->get_current_stock($data[a_product_id], 0);
                $val_stock2 = $val_stock1 * $data[a_org_price];
                $val_stock11 = $val_stock1 * $data[a_shop_price];
                $sum_stock1 += $val_stock1;
                $sum_stock2 += $val_stock2;
            }
    
            // 불량재고
            if( $f[stock_bad] || $f[stock_bad_price] ) 
            {
                $val_stock3 = $stock_obj->get_current_stock($data[a_product_id], 1);
                $val_stock4 = $val_stock3 * $data[a_org_price];
                $val_stock12 = $val_stock3 * $data[a_shop_price];
                $sum_stock3 += $val_stock3;
                $sum_stock4 += $val_stock4;
            }
    
            // 송장수량
            if( $f[trans_cnt] || $f[ready_trans_cnt] )
            {
                $val_stock5 = $stock_obj->get_ready_stock( $data[a_product_id] );
                $sum_stock5 += $val_stock5;
            }
                
            // 접수수량
            if( $f[ready_cnt] || $f[ready_trans_cnt] )
            {
                $val_stock6 = $stock_obj->get_ready_stock2( $data[a_product_id] );
                $sum_stock6 += $val_stock6;
            }

            // 기간 총발주, 기간 평균발주
            if( $f[period_all_order] || $f[period_avg_order] )
            {
                $query_day = "select sum(qty) sum_qty 
                                from order_products 
                               where product_id = '$data[a_product_id]' and 
                                     match_date >= '$start_date 00:00:00' and 
                                     match_date <= '$end_date 23:59:59'";
                $result_day = mysql_query($query_day, $connect);
                $data_day = mysql_fetch_assoc($result_day);
                
                $sum_stock7 += $data_day[sum_qty];
            }
            
            // 기간 총입고, 기간 총배송, 기간 평균입고, 기간 평균배송
            if( $f[period_all_in] || $f[period_all_trans] || $f[period_avg_in] || $f[period_avg_trans] )
            {
                $query_stock = "select sum(stockin) sum_stockin,
                                       sum(trans) sum_trans
                                  from stock_tx 
                                 where product_id='$data[a_product_id]' and 
                                       crdate >= '$start_date' and 
                                       crdate <= '$end_date'";
                $result_stock = mysql_query($query_stock, $connect);
                $data_stock = mysql_fetch_assoc($result_stock);
    
                $sum_stock8 += $data_stock[sum_stockin];
                $sum_stock9 += $data_stock[sum_trans];
            }
            
        
            $arr_temp = array();
            if( $f['sheet_name'          ] )  $arr_temp['sheet_name'          ] = $data[s_name];
            if( $f['supply_name'         ] )  $arr_temp['supply_name'         ] = $data[c_name];
            if( $f['maker'         		 ] )  $arr_temp['maker'     		  ] = $data[a_maker];
            if( $f['product_id'          ] )  $arr_temp['product_id'          ] = $data[a_product_id];                    
            if( $f['reg_date'            ] )  $arr_temp['reg_date'            ] = $data[a_reg_date];
            if( $f['barcode'             ] )  $arr_temp['barcode'             ] = $data[a_barcode];                    
            if( $f['product_img'         ] )  $arr_temp['product_img'         ] = $this->disp_image4( $data[product_id] );
            if( $f['product_name'        ] )  $arr_temp['product_name'        ] = $data[a_name];                          
            if( $f['product_options'     ] )  $arr_temp['product_options'     ] = $data[a_options];                       
            if( $f['product_name_options'] )  $arr_temp['product_name_options'] = $data[a_name] . " " . $data[a_options];
            if( $f['product_gift'		 ] )  $arr_temp['product_gift'		  ] = $data[a_product_gift];
            if( $f['brand'               ] )  $arr_temp['brand'               ] = $data[a_brand];
            if( $f['supply_options'      ] )  $arr_temp['supply_options'      ] = $data[a_supply_options];
            if( $f['product_memo'        ] )  $arr_temp['product_memo'        ] = $data[a_memo];              
            if( $f['org_price'] && $enable_org_price )  $arr_temp['org_price' ] = number_format($data[a_org_price]);      
            if( $f['supply_price'        ] )  $arr_temp['supply_price' ]        = number_format($data[a_supply_price]);      
            if( $f['shop_price'          ] )  $arr_temp['shop_price' ]          = number_format($data[a_shop_price]);      
            
            if( $f['stock_alarm1'        ] )  $arr_temp['stock_alarm1' ]        = number_format($data[stock_alarm1]);      
            if( $f['stock_alarm2'        ] )  $arr_temp['stock_alarm2' ]        = number_format($data[stock_alarm2]);      

            if( _DOMAIN_ == 'au2' )
            {
                // 입고합계
                $query_stockin = "select sum(stockin) sum_stockin 
                                    from stock_tx
                                   where product_id = '$data[a_product_id]'
                                     and bad = 0 ";
                $result_stockin = mysql_query($query_stockin, $connect);
                $data_stockin = mysql_fetch_assoc($result_stockin);
                
                $arr_temp['req_stockin1'] = number_format($data[a_req_stockin]);
                $arr_temp['req_stockin2'] = number_format($data_stockin[sum_stockin]);
                $arr_temp['req_stockin3'] = number_format($data[a_req_stockin] - $data_stockin[sum_stockin]);
            }

            if( $f['period_all_order'    ] )  $arr_temp['period_all_order'    ] = number_format($data_day[sum_qty]);
            if( $f['period_all_in'       ] )  $arr_temp['period_all_in'       ] = number_format($data_stock[sum_stockin]);
            if( $f['period_all_trans'    ] )  $arr_temp['period_all_trans'    ] = number_format($data_stock[sum_trans]);
            if( $f['period_avg_order'    ] )  $arr_temp['period_avg_order'    ] = number_format($data_day[sum_qty]/$date_diff,2);
            if( $f['period_avg_in'       ] )  $arr_temp['period_avg_in'       ] = number_format($data_stock[sum_stockin]/$date_diff,2);
            if( $f['period_avg_trans'    ] )  $arr_temp['period_avg_trans'    ] = number_format($data_stock[sum_trans]/$date_diff,2);

            if( $f['stock_normal'        ] )  $arr_temp['stock_normal'        ] = number_format($val_stock1);             
            if( $f['stock_normal_price'] && $enable_org_price )  $arr_temp['stock_normal_price'  ] = number_format($val_stock2);             
            if( $f['shop_normal_price'   ] )  $arr_temp['shop_normal_price'  ] = number_format($val_stock11);
            if( $f['stock_bad'           ] )  $arr_temp['stock_bad'           ] = number_format($val_stock3);             
            if( $f['stock_bad_price'] && $enable_org_price )  $arr_temp['stock_bad_price'     ] = number_format($val_stock4);             
            if( $f['shop_bad_price'      ] )  $arr_temp['shop_bad_price'      ] = number_format($val_stock12);
            if( $f['ready_cnt'           ] )  $arr_temp['ready_cnt'           ] = number_format($val_stock6);             
            if( $f['trans_cnt'           ] )  $arr_temp['trans_cnt'           ] = number_format($val_stock5);             
            if( $f['ready_trans_cnt'     ] )  $arr_temp['ready_trans_cnt'     ] = number_format($val_stock5+$val_stock6);
            if( $f['stock_able'          ] )  $arr_temp['stock_able'          ] = number_format($val_stock1-$val_stock5-$val_stock6);

            if( $f['request_qty'         ] )  $arr_temp['request_qty'         ] = number_format($data[d_qty]);
            if( $f['request_memo'        ] )  $arr_temp['request_memo'        ] = $data[d_memo];
            if( $f['request_date'        ] )  $arr_temp['request_date'        ] = $data[d_crdate];

            $stockin_all = $data[d_stockin1] + $data[d_stockin2] + $data[d_stockin3] + $data[d_stockin4] + $data[d_stockin5] + $data[d_stockin6] + $data[d_stockin7] + $data[d_stockin8] + $data[d_stockin9] + $data[d_stockin10];
            if( $f['stockin_all'         ] )  $arr_temp['stockin_all'         ] = number_format($stockin_all);

            if( $f['stockin_01'          ] )  $arr_temp['stockin_01'          ] = number_format($data[d_stockin1]);
            if( $f['stockin_02'          ] )  $arr_temp['stockin_02'          ] = number_format($data[d_stockin2]);
            if( $f['stockin_03'          ] )  $arr_temp['stockin_03'          ] = number_format($data[d_stockin3]);
            if( $f['stockin_04'          ] )  $arr_temp['stockin_04'          ] = number_format($data[d_stockin4]);
            if( $f['stockin_05'          ] )  $arr_temp['stockin_05'          ] = number_format($data[d_stockin5]);
            if( $f['stockin_06'          ] )  $arr_temp['stockin_06'          ] = number_format($data[d_stockin6]);
            if( $f['stockin_07'          ] )  $arr_temp['stockin_07'          ] = number_format($data[d_stockin7]);
            if( $f['stockin_08'          ] )  $arr_temp['stockin_08'          ] = number_format($data[d_stockin8]);
            if( $f['stockin_09'          ] )  $arr_temp['stockin_09'          ] = number_format($data[d_stockin9]);
            if( $f['stockin_10'          ] )  $arr_temp['stockin_10'          ] = number_format($data[d_stockin10]);

            $arr_datas[] = $arr_temp;

            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }

        $this->make_file_IG00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_IG00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $template, $connect, $page, $line_per_page, $link_url, $enable_org_price;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result;
        global $multi_supply_group, $multi_supply;
        

        $saveTarget = _upload_dir . $filename; 

        // 불량창고 이름
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        // 조회 필드
        $f = $this->get_setting(2);

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        if( $f['sheet_name'          ] )  $buffer .= "<td class=header_item>전표이름</td>";  
        if( $f['supply_name'         ] )  $buffer .= "<td class=header_item>공급처</td>";                  
        if( $f['maker'       		 ] )  $buffer .= "<td class=header_item>제조사</td>";    
        if( $f['product_id'          ] )  $buffer .= "<td class=header_item>상품코드</td>";                 
        if( $f['reg_date'            ] )  $buffer .= "<td class=header_item>등록일</td>";

        if( $f['barcode'             ] )  $buffer .= "<td class=header_item>바코드</td>";                 
        if( $f['product_img'         ] )  $buffer .= "<td class=header_item>이미지</td>";                  
        if( $f['product_name'        ] )  $buffer .= "<td class=header_item>상품명</td>";                  
        if( $f['product_options'     ] )  $buffer .= "<td class=header_item>옵션</td>";                   
        if( $f['product_name_options'] )  $buffer .= "<td class=header_item>상품명+옵션</td>";
        if( $f['product_gift'		 ] )  $buffer .= "<td class=header_item>사은품</td>";
        if( $f['brand'               ] )  $buffer .= "<td class=header_item>공급처상품명</td>";                 
        if( $f['supply_options'      ] )  $buffer .= "<td class=header_item>공급처옵션</td>";                 
        if( $f['product_memo'        ] )  $buffer .= "<td class=header_item>상품메모</td>";               
        if( $f['org_price'] && $enable_org_price )  $buffer .= "<td class=header_item>원가</td>";                   
        
        // jkryu add 2012.8.2
        if( $f['supply_price'] )          $buffer .= "<td class=header_item>공급가</td>";
        if( $f['shop_price'] )            $buffer .= "<td class=header_item>판매가</td>";                   
        if( $f['stock_alarm1'] )            $buffer .= "<td class=header_item>재고경고수량</td>";                   
        if( $f['stock_alarm2'] )            $buffer .= "<td class=header_item>재고위험수량</td>";                   

        if( _DOMAIN_ == 'au2' )
        {
            $buffer .= "<td class=header_item>오더수량</td>";
            $buffer .= "<td class=header_item>입고수량</td>";
            $buffer .= "<td class=header_item>남은수량</td>";
        }

        if( $f['period_all_order'    ] )  $buffer .= "<td class=header_item>기간총발주</td>";
        if( $f['period_all_in'       ] )  $buffer .= "<td class=header_item>기간총입고</td>";
        if( $f['period_all_trans'    ] )  $buffer .= "<td class=header_item>기간총배송</td>";
        if( $f['period_avg_order'    ] )  $buffer .= "<td class=header_item>기간평균발주</td>";
        if( $f['period_avg_in'       ] )  $buffer .= "<td class=header_item>기간평균입고</td>";
        if( $f['period_avg_trans'    ] )  $buffer .= "<td class=header_item>기간평균배송</td>";

        if( $f['stock_normal'        ] )  $buffer .= "<td class=header_item>정상재고</td>";                 
        if( $f['stock_normal_price'] && $enable_org_price )  $buffer .= "<td class=header_item>정상재고금액</td>";               
        if( $f['shop_normal_price'   ] )  $buffer .= "<td class=header_item>정상판매금액</td>";
        if( $f['stock_bad'           ] )  $buffer .= "<td class=header_item>".$bad_name."재고</td>";        
        if( $f['stock_bad_price'] && $enable_org_price )  $buffer .= "<td class=header_item>".$bad_name."재고금액</td>";      
        if( $f['shop_bad_price'      ] )  $buffer .= "<td class=header_item>".$bad_name."판매금액</td>";      
        if( $f['ready_cnt'           ] )  $buffer .= "<td class=header_item>미배송(접수)</td>";                   
        if( $f['trans_cnt'           ] )  $buffer .= "<td class=header_item>미배송(송장)</td>";                   
        if( $f['ready_trans_cnt'     ] )  $buffer .= "<td class=header_item>미배송(접수+송장)</td>";
        if( $f['stock_able'          ] )  $buffer .= "<td class=header_item>가용재고</td>";

        if( $f['request_qty'         ] )  $buffer .= "<td class=header_item>요청수량</td>";
        if( $f['request_memo'        ] )  $buffer .= "<td class=header_item>요청메모</td>";
        if( $f['request_date'        ] )  $buffer .= "<td class=header_item>요청일</td>";

        if( $f['stockin_all'         ] )  $buffer .= "<td class=header_item>총입고</td>";
        if( $f['stockin_01'          ] )  $buffer .= "<td class=header_item>1차입고</td>";
        if( $f['stockin_02'          ] )  $buffer .= "<td class=header_item>2차입고</td>";
        if( $f['stockin_03'          ] )  $buffer .= "<td class=header_item>3차입고</td>";
        if( $f['stockin_04'          ] )  $buffer .= "<td class=header_item>4차입고</td>";
        if( $f['stockin_05'          ] )  $buffer .= "<td class=header_item>5차입고</td>";
        if( $f['stockin_06'          ] )  $buffer .= "<td class=header_item>6차입고</td>";
        if( $f['stockin_07'          ] )  $buffer .= "<td class=header_item>7차입고</td>";
        if( $f['stockin_08'          ] )  $buffer .= "<td class=header_item>8차입고</td>";
        if( $f['stockin_09'          ] )  $buffer .= "<td class=header_item>9차입고</td>";
        if( $f['stockin_10'          ] )  $buffer .= "<td class=header_item>10차입고</td>";

        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                if( $key == 'supply_name' || $key == 'product_id' || $key == 'product_img' || $key == 'product_name' || 
                    $key == 'product_options' || $key == 'product_name_options' || $key == 'product_gift' || $key == 'request_date' || $key == 'reg_date' ||
                    $key == 'brand' || $key == 'supply_options'  || $key == 'maker')
                    $buffer .= "<td class=str_item>$v</td>\n";
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

    function download_IG00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"상품재고조회.xls"));
    }    


    //###############################
    // 조회 필드 설정팝업
    //###############################
    function IG01()
    {
        global $template, $connect, $template_page;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $f = $this->get_setting(1);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 조회 필드 설정팝업 - 다운로드
    //###############################
    function IG02()
    {
        global $template, $connect, $template_page;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $f = $this->get_setting(2);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    //###############################
    // 재고조정 팝업
    //###############################
    function IG03()
    {
        global $template, $connect, $template_page;


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 재고조정 팝업
    //###############################
    function stock_save()
    {
        global $template, $connect, $template_page;
        global $data, $bad, $type, $memo;
                $val = array();

        // Lock Check
        $obj_lock = new class_lock(201);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }


		foreach ($data as &$value) {
			// input parameter
	        $info_arr = array(
	            type       => $type,
	            product_id => $value["product_id"],
	            bad        => $bad,
	            location   => 'Def',
	            qty        => $value["qty"],
	            memo       => $memo
	        );
	
	        $obj = new class_stock();
	        $obj->set_stock($info_arr);
	
	        $val[stock] = class_stock::get_current_stock( $product_id, 0 );
	        $val[stock_bad] = class_stock::get_current_stock( $product_id, 1 );
	        
	        $val['error'] = 0;	
		} 
        

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
        
        echo json_encode( $val );
    }
    
      
    //###############################
    // 전표 상품추가
    //###############################
    function IG10()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $str_supply_code, $name, $options, $brand, $supply_options, $seq;

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
            $brand = trim( $brand );
            $supply_options = trim( $supply_options );
            
            // link url
            $par = array('template','str_supply_code', 'name', 'options', 'brand', 'supply_options');
            $link_url = $this->build_link_url3( $par );
            
            $query = "select b.name       b_supply_name,
                             a.product_id a_product_id,
                             a.brand,
                             a.stock_alarm1,
                             a.stock_alarm2,
                             a.barcode    a_barcode,
                             a.name       a_product_name,
                             a.options    a_options
                             a.product_gift a_product_gift,
                        from products a,
                             userinfo b
                       where a.supply_code = b.code and
                             a.is_delete = 0 and
                             a.is_represent = 0 ";
           
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
    
    //*****************************
    // 조회 필드 읽기
    //*****************************
    function get_setting($field)
    {
        global $connect, $template_page;
        
        $query = "select stock_field" . $this->get_field($field, $template_page) . " as stock_field from ez_config";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $f = array();
        foreach( explode(",",$data[stock_field]) as $_f )
            $f[$_f] = 1;

        return $f;
    }
    
    //*****************************
    // 조회 필드 설정
    //*****************************
    function save_setting()
    {
        global $connect, $field, $setting, $template_page;

        $query = "update ez_config set stock_field" . $this->get_field($field, $template_page) . "='$setting'";
        mysql_query($query, $connect);
    }

    //*****************************
    // 필드 변환
    //*****************************
    function get_field($field, $template_page)
    {
        if( $field == 1 && $template_page == "IH00" )
            $_field = 3;
        else if( $field == 1 && $template_page == "IH10" )
            $_field = 4;
        else if( $field == 2 && $template_page == "IH10" )
            $_field = 5;
        else if( $field == 1 && $template_page == "IH20" )
            $_field = 6;
        else if( $field == 2 && $template_page == "IH20" )
            $_field = 7;
        else if( $field == 1 && $template_page == "IH30" )
            $_field = 8;
        else if( $field == 2 && $template_page == "IH30" )
            $_field = 9;
        else
            $_field = $field;

        return $_field;
    }

    //*****************************
    // 재고정보
    //*****************************
    function get_stock_info()
    {
        global $connect, $product_id;

        $val = array();
        
        // 상품원가
        $data = class_product::get_info($product_id, 'org_price');
        $org_price = $data[org_price];
        
        $stock_obj = new class_stock();
        $val['stock_n'] = $stock_obj->get_current_stock($product_id, 0);
        $val['stock_np'] = $val['stock_n'] * $org_price;
        $val['stock_b'] = $stock_obj->get_current_stock($product_id, 1);
        $val['stock_bp'] = $val['stock_b'] * $org_price;
        
        echo json_encode($val);
    }

    //*****************************
    // 재고경고수량 변경
    //*****************************
    function update_stock_alarm1()
    {
        global $connect, $product_id, $new_val;

        $val = array();
        
        $query = "update products set stock_alarm1 = '$new_val' where product_id='$product_id'";
        mysql_query($query, $connect);
            
        $val["error"] = 0;
        $val["new_value"] = $new_val;
        echo json_encode($val);
    }

    //*****************************
    // 재고위험수량 변경
    //*****************************
    function update_stock_alarm2()
    {
        global $connect, $product_id, $new_val;

        $val = array();
        
        $query = "update products set stock_alarm2 = '$new_val' where product_id='$product_id'";
        mysql_query($query, $connect);
            
        $val["error"] = 0;
        $val["new_value"] = $new_val;
        echo json_encode($val);
    }
    
    //*****************************
    // 입고요청생성 수량
    //*****************************
    function update_request_qty()
    {
        global $connect, $product_id, $new_val, $org_val, $seq_val;

        $val = array();
        
        $val["seq"] = "";
        $val["new_value"] = "";

        if( $new_val > 0 )
        {
            // update
            if( $org_val > 0 )
            {
                $query = "update stockin_req set product_id='$product_id', qty = '$new_val' where seq=$seq_val";
                mysql_query($query, $connect);

                $val["seq"] = $seq_val;
            }
            // insert
            else
            {
                $query = "insert stockin_req set product_id='$product_id', crdate=now(), crtime=now(), qty = $new_val";
                mysql_query($query, $connect);
                
                $query_seq = "select * from stockin_req where product_id='$product_id' order by seq desc limit 1";
                $result_seq = mysql_query($query_seq, $connect);
                $data_seq = mysql_fetch_assoc($result_seq);
                
                $val["seq"] = $data_seq[seq];
            }

            $val["new_value"] = $new_val;
        }
        // delete
        else
        {
            $query = "delete from stockin_req where seq=$seq_val";
            mysql_query($query, $connect);
        }

            
        $val["error"] = 0;
        echo json_encode($val);
    }

    //*****************************
    // 입고요청생성 메모
    //*****************************
    function update_request_memo()
    {
        global $connect, $product_id, $new_val, $seq_val;

        $val = array();
        
        $query = "update stockin_req set product_id='$product_id', memo = '$new_val' where seq=$seq_val";
        if( mysql_query($query, $connect) )
            $val["error"] = 0;
        else
            $val["error"] = 1;

        $val["new_value"] = $new_val;
        echo json_encode($val);
    }

    //*****************************
    // 개별요청전표생성
    //*****************************
    function create_stockin_req()
    {
        global $connect, $sheet_name, $data_list;

        $val = array();
        
        $query = "insert stockin_req_sheet set name='$sheet_name',
                                               worker = '$_SESSION[LOGIN_NAME]', 
                                               crdate = now()";
        mysql_query($query, $connect);
        
        $query = "select seq from stockin_req_sheet where name='$sheet_name' order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $sheet = $data[seq];
        
        $product_str = "";
        foreach( explode(",",substr($data_list,0,-1)) as $_v )
            $product_str .= ($product_str ? "," : "" ) . "'$_v'";
        
        $query = "update stockin_req set sheet=$sheet where sheet=0 and product_id in ($product_str)";
        mysql_query($query, $connect);
        
        $supply_str = "";
        $query = "select supply_code from products where product_id in ($product_str) group by supply_code order by supply_code";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $supply_str .= ($supply_str ? "," : "") . $data[supply_code];

        $query = "update stockin_req_sheet set supply_list = '$supply_str' where seq=$sheet";
        mysql_query($query, $connect);

        $val["error"] = 0;
        echo json_encode($val);
    }

    //*****************************
    // 개별요청전표 불러오기
    //*****************************
    function load_stockin_req()
    {
        global $connect;

        $val = array();
        
        $supply_str = "";
        $query = "select supply_code from products where product_id in ($product_str) group by supply_code order by supply_code";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $supply_str .= ($supply_str ? "," : "") . $data[supply_code];

        $query = "update stockin_req_sheet set supply_list = '$supply_str' where seq=$sheet";
        mysql_query($query, $connect);

        $val["error"] = 0;
        echo json_encode($val);
    }
    
    //*****************************
    // 개별요청전표추가
    //*****************************
    function add_stockin_req()
    {
        global $connect, $data_list, $sheet_seq;
        
        $val = array();
        $all_supply_list="";
        $o_supply_list = array();
        $n_supply_list = array();
        $temp = array();

        // 기존 전표정보
        $query = "select seq,name,supply_list from stockin_req_sheet where seq ='$sheet_seq'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $all_supply_list = $data[supply_list];
        
        $product_str = "";
        foreach( explode(",",substr($data_list,0,-1)) as $_v )
        {
            $product_str .= ($product_str ? "," : "" ) . "'$_v'";
        }
        
        // 전표에 상품 추가
        $query = "update stockin_req set sheet=$sheet_seq where sheet=0 and product_id in ($product_str)";
        mysql_query($query, $connect);

        // 공급처 목록 업데이트
        $supply_str = "";
        $query = "select supply_code 
                    from products 
                   where product_id in ($product_str) ";
        if( $all_supply_list )
            $query .= " and supply_code not in ($all_supply_list) ";
        $query .= "group by supply_code order by supply_code";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $supply_str .= ($supply_str ? "," : "") . $data[supply_code];

        if( $supply_str )
        {
            if( $all_supply_list )
                $query = "update stockin_req_sheet set supply_list = concat(supply_list, ',', '$supply_str') where seq=$sheet_seq";
            else
                $query = "update stockin_req_sheet set supply_list = '$supply_str' where seq=$sheet_seq";
            mysql_query($query, $connect);
        }
        
        $val["error"] = 0;
        echo json_encode($val);
    }
    
    //*****************************
    // 차수입고
    //*****************************
    function stockin_step()
    {
        global $connect, $sheet_seq, $step_no, $data_list;
        
        $val = array();
        
        // 해당 차수 입고 완료 확인
        $query = "select step_no from stockin_req_sheet where seq=$sheet_seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        if( $data[step_no] <> $step_no )
        {
            $val["error"] = 1;
            echo json_encode($val);
            return;
        }        

        // Lock Check
        $obj_lock = new class_lock(201);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $obj = new class_stock();
        
        $stockin_arr = explode(",", substr($data_list,0,-1));
        foreach($stockin_arr as $stockin_prd)
        {
            list($prd, $qty, $org_price) = explode(":", $stockin_prd);
            $qty = (int)$qty;
            if( $prd && is_int($qty) )
            {
                $query = "update stockin_req set stockin" . $step_no . " = $qty where product_id='$prd' and sheet=$sheet_seq";
                mysql_query($query, $connect);
                
                $info_arr = array(
                    type       => "in",
                    product_id => $prd,
                    bad        => 0,
                    location   => 'Def',
                    qty        => $qty,
                    memo       => "",
                    org_price  => $org_price
                );
                $obj = new class_stock();
                $obj->set_stock($info_arr, $_SESSION[LOGIN_NAME], $sheet_seq * -1);
            }
        }
        
        // 차수 업데이트
        $query = "update stockin_req_sheet set step_no=$step_no + 1, crdate" . $step_no . "=now(), worker" . $step_no . "='$_SESSION[LOGIN_NAME]' where seq=$sheet_seq";
        mysql_query($query, $connect);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
        
        $val["error"] = 0;
        echo json_encode($val);
    }

    //*****************************
    // 개별요청삭제
    //*****************************
    function stockin_delete()
    {
        global $connect, $data_list, $sheet_seq;
        
        $val = array();

        $seq_str = "";
        foreach( explode(",",substr($data_list,0,-1)) as $_v )
            $seq_str .= "$_v,";
        
        // 삭제
        $query = "delete from stockin_req where seq in (" . substr($seq_str,0,-1) . ")";
debug("개별요청삭제 : ".$query);
        mysql_query($query, $connect);

        $val["error"] = 0;
        echo json_encode($val);
    }

    //*****************************
    // 전표 상품추가
    //*****************************
    function add_sheet_product()
    {
        global $connect, $seq, $product_id;
        
        $val = array();
        
        // 해당 전표에 이미 있는 상품인지 확인
        $query = "select * from stockin_req where product_id='$product_id' and sheet=$seq";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
            echo json_encode($val);
            return;
        }
        
        $query = "insert stockin_req 
                     set crdate = now(),
                         crtime = now(),
                         product_id = '$product_id',
                         sheet = $seq";
        mysql_query($query, $connect);

        $val["error"] = 0;
        echo json_encode($val);
    }
    
    
    //*****************************
    // 오더수량 변경(au2)
    //*****************************
    function chang_order_qty()
    {
        global $connect, $product_id, $qty;

        $query = "update products set req_stockin = '$qty' where product_id='$product_id' ";
        mysql_query($query, $connect);
    }
}
?>
