<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_ui.php";

class class_FL00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function FL00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group, $search_type, $sort, $sort_order;
        global $group_id, $shop_id, $str_shop_id;

        // 복수 공급처 
        $str_supply_code = "";
        foreach( $supply_code as $_c )
            $str_supply_code .= ($str_supply_code ? "," : "") . $_c;

        // 복수 판매처
        $str_shop_id = "";
        foreach( $shop_id as $_c )
            $str_shop_id .= ($str_shop_id ? "," : "") . $_c;

        // 불량창고 이름
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        // 조회 필드
        $title = 'FL00';
        $f = $this->get_setting();

        if( $search )
        {
            $this->show_wait();
            
            // 전체 쿼리
            $query = $this->get_FL00();
            $result = mysql_query($query, $connect);
            
            // 총 개수
            $total_rows = mysql_num_rows( $result );
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 메인 쿼리
    //###############################
    function get_FL00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group, $search_type, $sort, $sort_order;
        global $group_id, $shop_id, $str_shop_id;
        
        $query = "select * from userinfo where level=0 ";
        if( $str_supply_code )
            $query .= " and code in ($str_supply_code) ";
        if( $supply_group )
            $query .= " and group_id = '$supply_group' ";

        $query .= " order by group_id, name";
            
        return $query;
    }
    
    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_FL00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group, $search_type, $sort, $sort_order;
        global $group_id, $shop_id, $str_shop_id;

        // 조회 필드
        $title = 'FL00_file';
        $f = $this->get_setting();

        $start_time = time();
        $i = 0;
        
        $sum_stock1 = 0;
        $sum_stock2 = 0;
        $sum_stock3 = 0;
        $sum_stock4 = 0;
        $sum_stock5 = 0;
        $sum_stock6 = 0;
        $sum_stock7 = 0;
        $sum_stock8 = 0;
        $sum_stock9 = 0;
        $sum_stock10 = 0;
        $sum_stock11 = 0;
        $sum_stock12 = 0;
        $sum_stock13 = 0;
        $sum_stock14 = 0;
        $sum_stock15 = 0;
        $sum_stock16 = 0;
        $sum_stock17 = 0;
        $sum_stock18 = 0;
    
        $sum_req_stockin = 0;

        $arr_datas = array();
        
        $query = $this->get_FL00();
        $result = mysql_query($query, $connect);

        $shop_list = "";
        if( $group_id )
        {
            $query_group = "select shop_id from shopinfo where group_id = $group_id";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $shop_list .= ($shop_list ? "," : "") . $data_group[shop_id];
    
            if( !$shop_list )  $shop_list = 9999;
        }

        // 총 개수
        $total_rows = mysql_num_rows( $result );

        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$i / $total_rows' )</script>";
                flush();
            }
    
            $stock1  = 0;
            $stock2  = 0;
            $stock3  = 0;
            $stock4  = 0;
            $stock5  = 0;
            $stock6  = 0;
            $stock7  = 0;
            $stock8  = 0;
            $stock9  = 0;
            $amount1 = 0;
            $amount2 = 0;
            $amount3 = 0;
            $amount4 = 0;
            $amount5 = 0;
            $amount6 = 0;
            $amount7 = 0;
            $amount8 = 0;
            $amount9 = 0;
            
            // 입출고 금액 설정
            if( $_SESSION[SUPPLY_ORG_PRICE_TYPE] )
                $supply_org_price_type = "a.org_price";
            else
                $supply_org_price_type = "b.org_price";

            if( $shop_list || $str_shop_id )
            {
                $query_stock = "select a.job a_job,
                                       sum(a.qty) sum_a_qty,
                                       sum(a.qty * $supply_org_price_type) sum_a_org_price
                                  from stock_tx_history a,
                                       products b,
                                       orders c
                                 where a.product_id = b.product_id and 
                                       a.order_seq = c.seq and ";

                if( $shop_list )
                    $query_stock .= " c.shop_id in ($shop_list) and ";
                if( $str_shop_id )
                    $query_stock .= " c.shop_id in ($str_shop_id) and ";

                $query_stock .= "      b.supply_code = $data[code] and
                                       a.crdate >= '$start_date 00:00:00' and
                                       a.crdate <= '$end_date 23:59:59' and
                                       a.bad = '$stock_type'
                                 group by job";
            }
            else
            {
                $query_stock = "select a.job a_job,
                                       sum(a.qty) sum_a_qty,
                                       sum(a.qty * $supply_org_price_type) sum_a_org_price
                                  from stock_tx_history a,
                                       products b
                                 where a.product_id = b.product_id and
                                       b.supply_code = $data[code] and
                                       a.crdate >= '$start_date 00:00:00' and
                                       a.crdate <= '$end_date 23:59:59' and
                                       a.bad = '$stock_type'
                                 group by job";
            }
            $result_stock = mysql_query($query_stock, $connect);
            while( $data_stock = mysql_fetch_assoc($result_stock) )
            {
                switch( $data_stock[a_job] )
                {
                    case "in":
                        $stock1  = $data_stock[sum_a_qty];
                        $amount1 = $data_stock[sum_a_org_price];
                        break;
                    case "retin":
                        $stock2  = $data_stock[sum_a_qty];
                        $amount2 = $data_stock[sum_a_org_price];
                        break;
                    case "out":
                        $stock4  = $data_stock[sum_a_qty];
                        $amount4 = $data_stock[sum_a_org_price];
                        break;
                    case "retout":
                        $stock5  = $data_stock[sum_a_qty];
                        $amount5 = $data_stock[sum_a_org_price];
                        break;
                    case "trans":
                        $stock7  = $data_stock[sum_a_qty];
                        $amount7 = $data_stock[sum_a_org_price];
                        break;
                    case "arrange":
                        $stock8  = $data_stock[sum_a_qty];
                        $amount8 = $data_stock[sum_a_org_price];
                        break;
                }
                
                // 총입고
                $stock3 = $stock1 + $stock2;
                $amount3 = $amount1 + $amount2;
    
                // 총출고
                $stock6 = $stock4 + $stock5;
                $amount6 = $amount4 + $amount5;
            }
            
            if( $search_type == 0 && $stock1 == 0 ) continue;
            if( $search_type == 1 && $stock4 == 0 ) continue;
            if( $search_type == 2 && $stock7 == 0 ) continue;
            if( $search_type == 3 && $stock2 == 0 ) continue;
            if( $search_type == 4 && $stock5 == 0 ) continue;
            if( $search_type == 5 && $stock8 == 0 ) continue;

            // 현재고
            $query_cur = "select sum(a.stock) sum_a_stock,
                                 sum(a.stock * b.org_price) sum_amount 
                            from current_stock a,
                                 products b
                           where a.product_id = b.product_id and
                                 b.supply_code = '$data[code]' and
                                 a.bad = '$stock_type'";
            $result_cur = mysql_query($query_cur, $connect);
            $data_cur = mysql_fetch_assoc($result_cur);
                
            $stock9  = ($data_cur[sum_a_stock] ? $data_cur[sum_a_stock] : 0);
            $amount9 = ($data_cur[sum_amount]  ? $data_cur[sum_amount]  : 0);
            
            $temp_arr = array();
            if( $f[supply_code   ] ) $temp_arr[supply_code   ] = $data[code];
            if( $f[supply_group  ] ) $temp_arr[supply_group  ] = $this->get_supply_group_name($data[group_id]);
            if( $f[supply_name   ] ) $temp_arr[supply_name   ] = $data[name];

            if( _DOMAIN_ == 'au2' )
            {
                $query_req = "select sum(req_stockin) sum_req_stockin from products where supply_code = $data[code] and is_delete=0";
                $result_req = mysql_query($query_req, $connect);
                $data_req = mysql_fetch_assoc($result_req);
                
                $temp_arr["req_stockin"] = $data_req[sum_req_stockin];
            }

            if( $f[stock_in      ] ) $temp_arr[stock_in      ] = $stock1;
            if( $f[stock_retin   ] ) $temp_arr[stock_retin   ] = $stock2;
            if( $f[stock_allin   ] ) $temp_arr[stock_allin   ] = $stock3;
            if( $f[stock_out     ] ) $temp_arr[stock_out     ] = $stock4;
            if( $f[stock_retout  ] ) $temp_arr[stock_retout  ] = $stock5;
            if( $f[stock_allout  ] ) $temp_arr[stock_allout  ] = $stock6;
            if( $f[stock_trans   ] ) $temp_arr[stock_trans   ] = $stock7;
            if( $f[stock_arrange ] ) $temp_arr[stock_arrange ] = $stock8;
            if( $f[stock_current ] ) $temp_arr[stock_current ] = $stock9;
            if( $f[amount_in     ] ) $temp_arr[amount_in     ] = $amount1;
            if( $f[amount_retin  ] ) $temp_arr[amount_retin  ] = $amount2;
            if( $f[amount_allin  ] ) $temp_arr[amount_allin  ] = $amount3;
            if( $f[amount_out    ] ) $temp_arr[amount_out    ] = $amount4;
            if( $f[amount_retout ] ) $temp_arr[amount_retout ] = $amount5;
            if( $f[amount_allout ] ) $temp_arr[amount_allout ] = $amount6;
            if( $f[amount_trans  ] ) $temp_arr[amount_trans  ] = $amount7;
            if( $f[amount_arrange] ) $temp_arr[amount_arrange] = $amount8;
            if( $f[amount_current] ) $temp_arr[amount_current] = $amount9;
            $arr_datas[] = $temp_arr;
            
            $sum_stock1  += $stock1;
            $sum_stock2  += $stock2;
            $sum_stock3  += $stock3;
            $sum_stock4  += $stock4;
            $sum_stock5  += $stock5;
            $sum_stock6  += $stock6;
            $sum_stock7  += $stock7;
            $sum_stock8  += $stock8;
            $sum_stock9  += $stock9;
            $sum_stock10 += $amount1;
            $sum_stock11 += $amount2;
            $sum_stock12 += $amount3;
            $sum_stock13 += $amount4;
            $sum_stock14 += $amount5;
            $sum_stock15 += $amount6;
            $sum_stock16 += $amount7;
            $sum_stock17 += $amount8;
            $sum_stock18 += $amount9;
    
            $sum_req_stockin += $temp_arr["req_stockin"];

            usleep(1000);
        }
        
        if( $sort )
        {
            if( $sort_order )
                $arr_datas = $this->array_array_rsort($arr_datas, $sort);
            else
                $arr_datas = $this->array_array_sort($arr_datas, $sort);
        }

        $temp_arr = array();
        if( $f[supply_code   ] ) $temp_arr[supply_code   ] = "";
        if( $f[supply_group  ] ) $temp_arr[supply_group  ] = "";
        if( $f[supply_name   ] ) $temp_arr[supply_name   ] = "합계";

        if( _DOMAIN_ == 'au2'  ) $temp_arr[req_stockin   ] = $sum_req_stockin;

        if( $f[stock_in      ] ) $temp_arr[stock_in      ] = $sum_stock1;
        if( $f[stock_retin   ] ) $temp_arr[stock_retin   ] = $sum_stock2;
        if( $f[stock_allin   ] ) $temp_arr[stock_allin   ] = $sum_stock3;
        if( $f[stock_out     ] ) $temp_arr[stock_out     ] = $sum_stock4;
        if( $f[stock_retout  ] ) $temp_arr[stock_retout  ] = $sum_stock5;
        if( $f[stock_allout  ] ) $temp_arr[stock_allout  ] = $sum_stock6;
        if( $f[stock_trans   ] ) $temp_arr[stock_trans   ] = $sum_stock7;
        if( $f[stock_arrange ] ) $temp_arr[stock_arrange ] = $sum_stock8;
        if( $f[stock_current ] ) $temp_arr[stock_current ] = $sum_stock9;
        if( $f[amount_in     ] ) $temp_arr[amount_in     ] = $sum_stock10;
        if( $f[amount_retin  ] ) $temp_arr[amount_retin  ] = $sum_stock11;
        if( $f[amount_allin  ] ) $temp_arr[amount_allin  ] = $sum_stock12;
        if( $f[amount_out    ] ) $temp_arr[amount_out    ] = $sum_stock13;
        if( $f[amount_retout ] ) $temp_arr[amount_retout ] = $sum_stock14;
        if( $f[amount_allout ] ) $temp_arr[amount_allout ] = $sum_stock15;
        if( $f[amount_trans  ] ) $temp_arr[amount_trans  ] = $sum_stock16;
        if( $f[amount_arrange] ) $temp_arr[amount_arrange] = $sum_stock17;
        if( $f[amount_current] ) $temp_arr[amount_current] = $sum_stock18;
        $arr_datas[] = $temp_arr;
    
        $this->make_file_FL00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_FL00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result, $sort, $sort_order;
        global $group_id, $shop_id, $str_shop_id;

        $saveTarget = _upload_dir . $filename; 

        // 조회 필드
        $title = 'FL00_file';
        $f = $this->get_setting();

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        if( $f[supply_code   ] ) $buffer .= "<td class=header_item>공급처코드</td>";
        if( $f[supply_group  ] ) $buffer .= "<td class=header_item>공급처그룹</td>";
        if( $f[supply_name   ] ) $buffer .= "<td class=header_item>공급처명</td>";
        if( _DOMAIN_ == 'au2'  ) $buffer .= "<td class=header_item>오더수량</td>";
        if( $f[stock_in      ] ) $buffer .= "<td class=header_item>입고</td>";
        if( $f[stock_retin   ] ) $buffer .= "<td class=header_item>반품입고</td>";
        if( $f[stock_allin   ] ) $buffer .= "<td class=header_item>총입고</td>";
        if( $f[stock_out     ] ) $buffer .= "<td class=header_item>출고</td>";
        if( $f[stock_retout  ] ) $buffer .= "<td class=header_item>반품출고</td>";
        if( $f[stock_allout  ] ) $buffer .= "<td class=header_item>총출고</td>";
        if( $f[stock_trans   ] ) $buffer .= "<td class=header_item>배송</td>";
        if( $f[stock_arrange ] ) $buffer .= "<td class=header_item>조정</td>";
        if( $f[stock_current ] ) $buffer .= "<td class=header_item>현재고</td>";
        if( $f[amount_in     ] ) $buffer .= "<td class=header_item>입고금액</td>";
        if( $f[amount_retin  ] ) $buffer .= "<td class=header_item>반품입고금액</td>";
        if( $f[amount_allin  ] ) $buffer .= "<td class=header_item>총입고금액</td>";
        if( $f[amount_out    ] ) $buffer .= "<td class=header_item>출고금액</td>";
        if( $f[amount_retout ] ) $buffer .= "<td class=header_item>반품출고금액</td>";
        if( $f[amount_allout ] ) $buffer .= "<td class=header_item>총출고금액</td>";
        if( $f[amount_trans  ] ) $buffer .= "<td class=header_item>배송금액</td>";
        if( $f[amount_arrange] ) $buffer .= "<td class=header_item>조정금액</td>";
        if( $f[amount_current] ) $buffer .= "<td class=header_item>현재고금액</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                if( $key == 'supply_code' || $key == 'supply_group' || $key == 'supply_name' )
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

    function download_FL00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "supply_list.xls" );
    }    


    //###############################
    // 조회 필드 설정팝업
    //###############################
    function FL01()
    {
        global $template, $connect, $title;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $title = 'FL00';
        $f = $this->get_setting(1);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 상품 상세 목록 팝업
    //###############################
    function FL20()
    {
        global $template, $connect, $title;
        global $supply_code, $type, $bad, $start_date, $end_date, $sort, $sort_order;

        // 조회 필드
        $title = 'FL20';
        $f = $this->get_setting();
        
        $this->show_wait();
        
        // 전체 쿼리
        $query = $this->get_FL20();
        $result = mysql_query($query, $connect);
        
        // 총 개수
        $total_rows = mysql_num_rows( $result );
        
        // 정렬방향
        if( $sort )
            $sort_order = ($sort_order ? 0 : 1);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 메인 쿼리
    //###############################
    function get_FL20()
    {
        global $template, $connect, $title;
        global $supply_code, $type, $bad, $start_date, $end_date, $sort, $sort_order;
    
        $query_job = "";
        if     ( $type == "stock_in"      )  $query_job = " a.job = 'in'              ";
        else if( $type == "stock_retin"   )  $query_job = " a.job = 'retin'           ";
        else if( $type == "stock_allin"   )  $query_job = " a.job in ('in','retin')   ";
        else if( $type == "stock_out"     )  $query_job = " a.job = 'out'             ";
        else if( $type == "stock_retout"  )  $query_job = " a.job = 'retout'          ";
        else if( $type == "stock_allout"  )  $query_job = " a.job in ('out','retout') ";
        else if( $type == "stock_trans"   )  $query_job = " a.job = 'trans'           ";
        else if( $type == "stock_arrange" )  $query_job = " a.job = 'arrange'         ";
    
        // 재고작업
        if( $query_job )
        {
            $query = "select a.product_id a_product_id,
                             b.name b_name,
                             b.options b_options,
                             b.brand b_brand,
                             b.supply_options b_supply_options,
                             b.location b_location,
                             b.memo b_memo,
                             sum(a.qty) sum_a_qty,
                             sum(a.qty * a.org_price) sum_a_org_price,
                             b.org_price b_org_price,
                             b.shop_price b_shop_price
                        from stock_tx_history a, 
                             products b 
                       where a.product_id = b.product_id and
                             a.crdate >= '$start_date 00:00:00' and
                             a.crdate <= '$end_date 23:59:59' and
                             b.supply_code = $supply_code and 
                             a.bad = $bad and 
                             $query_job
                       group by a.product_id 
                       order by b.name, b.product_id";
        }
        // 현재고
        else
        {
            $query = "select a.product_id a_product_id,
                             b.name b_name,
                             b.options b_options,
                             b.brand b_brand,
                             b.supply_options b_supply_options,
                             b.location b_location,
                             sum(a.stock) sum_a_qty,
                             b.org_price b_org_price,
                             b.shop_price b_shop_price
                        from current_stock a, 
                             products b 
                       where a.product_id = b.product_id and
                             a.bad=$bad and
                             a.stock <> 0 and
                             b.supply_code = $supply_code
                       group by a.product_id 
                       order by b.name, b.product_id";
        }
            
        return $query;
    }
    
    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_FL20()
    {
        global $template, $connect, $title;
        global $supply_code, $type, $bad, $start_date, $end_date, $sort, $sort_order;

        // 조회 필드
        $title = 'FL20_file';
        $f = $this->get_setting();

        $query = $this->get_FL20();
        $result = mysql_query($query, $connect);

        // 총 개수
        $total_rows = mysql_num_rows( $result );

        // 공급처명
        $query_supply = "select * from userinfo where code=$supply_code";
        $result_supply = mysql_query($query_supply, $connect);
        $data_supply = mysql_fetch_assoc($result_supply);
        $supply_name = $data_supply[name];
    
        $start_time = time();
    
        $sum_qty = 0;
        $sum_total_org = 0;
        $sum_total_shop = 0;
        
        // 전체 데이타
        $data_all = array();
    
        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>show_txt( '$i / $total_rows' )</script>";
                flush();
            }
    
            if( $f[supply_code     ] )  $temp_arr["supply_code"     ] = $supply_code;
            if( $f[supply_name     ] )  $temp_arr["supply_name"     ] = $supply_name;
            if( $f[product_id      ] )  $temp_arr["product_id"      ] = $data[a_product_id];
            if( $f[product_name    ] )  $temp_arr["product_name"    ] = $data[b_name];
            if( $f[options         ] )  $temp_arr["options"         ] = $data[b_options];
            if( $f[name_options    ] )  $temp_arr["name_options"    ] = $data[b_name] . " " . $data[b_options];
            if( $f[brand           ] )  $temp_arr["brand"           ] = $data[b_brand];
            if( $f[supply_options  ] )  $temp_arr["supply_options"  ] = $data[b_supply_options];
            if( $f[location        ] )  $temp_arr["location"        ] = $data[b_location];
            if( $f[type_qty        ] )  $temp_arr["type_qty"        ] = $data[sum_a_qty];
            if( $f[org_price       ] )  $temp_arr["org_price"       ] = $data[b_org_price];
            if( $f[total_org_price ] )
            {
                if( $_SESSION[SUPPLY_ORG_PRICE_TYPE] )
                    $temp_arr["total_org_price" ] = $data[sum_a_org_price];
                else
                    $temp_arr["total_org_price" ] = $data[sum_a_qty] * $data[b_org_price];
            }
            if( $f[shop_price      ] )  $temp_arr["shop_price"      ] = $data[b_shop_price];
            if( $f[total_shop_price] )  $temp_arr["total_shop_price"] = $data[sum_a_qty] * $data[b_shop_price];
            if( $f[memo            ] )  $temp_arr["memo"            ] = $data[b_memo];
            $data_all[] = $temp_arr;
    
            $sum_qty += $temp_arr["type_qty"];
            $sum_total_org += $temp_arr["total_org_price"];
            $sum_total_shop += $temp_arr["total_shop_price"];
    
            usleep(10000);
        }
    
        if( $sort )
        {
            if( $sort_order )
                $data_all = $this->array_array_sort($data_all, $sort);
            else
                $data_all = $this->array_array_rsort($data_all, $sort);
        }

        $temp_arr = array();

        $colspan = 0;
        if( $f[supply_code     ] )  $colspan++;
        if( $f[supply_name     ] )  $colspan++;
        if( $f[product_id      ] )  $colspan++;
        if( $f[product_name    ] )  $colspan++;
        if( $f[options         ] )  $colspan++;
        if( $f[name_options    ] )  $colspan++;
        if( $f[brand           ] )  $colspan++;
        if( $f[supply_options  ] )  $colspan++;
        if( $f[location        ] )  $colspan++;

        for($i=0; $i < $colspan-1; $i++)
            $temp_arr[] = "";

        $temp_arr[] = "합계";

        if( $f[type_qty        ] )  $temp_arr[] = $sum_qty;
        if( $f[org_price       ] )  $temp_arr[] = "";
        if( $f[total_org_price ] )  $temp_arr[] = $sum_total_org;
        if( $f[shop_price      ] )  $temp_arr[] = "";
        if( $f[total_shop_price] )  $temp_arr[] = $sum_total_shop;
        if( $f[memo            ] )  $temp_arr[] = "";

        $i = 0;
        $temp_arr2 = array();
        if( $f[supply_code     ] )  $temp_arr2["supply_code"     ] = $temp_arr[$i++];
        if( $f[supply_name     ] )  $temp_arr2["supply_name"     ] = $temp_arr[$i++];
        if( $f[product_id      ] )  $temp_arr2["product_id"      ] = $temp_arr[$i++];
        if( $f[product_name    ] )  $temp_arr2["product_name"    ] = $temp_arr[$i++];
        if( $f[options         ] )  $temp_arr2["options"         ] = $temp_arr[$i++];
        if( $f[name_options    ] )  $temp_arr2["name_options"    ] = $temp_arr[$i++];
        if( $f[brand           ] )  $temp_arr2["brand"           ] = $temp_arr[$i++];
        if( $f[supply_options  ] )  $temp_arr2["supply_options"  ] = $temp_arr[$i++];
        if( $f[location        ] )  $temp_arr2["location"        ] = $temp_arr[$i++];
        if( $f[type_qty        ] )  $temp_arr2["type_qty"        ] = $temp_arr[$i++];
        if( $f[org_price       ] )  $temp_arr2["org_price"       ] = $temp_arr[$i++];
        if( $f[total_org_price ] )  $temp_arr2["total_org_price" ] = $temp_arr[$i++];
        if( $f[shop_price      ] )  $temp_arr2["shop_price"      ] = $temp_arr[$i++];
        if( $f[total_shop_price] )  $temp_arr2["total_shop_price"] = $temp_arr[$i++];
        if( $f[memo            ] )  $temp_arr2["memo"            ] = $temp_arr[$i++];

        $data_all[] = $temp_arr2;
    
        $this->make_file_FL20( $data_all, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_FL20( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $template, $connect, $title;
        global $supply_code, $type, $bad, $start_date, $end_date, $sort, $sort_order;

        $saveTarget = _upload_dir . $filename; 

        // 조회 필드
        $title = 'FL20_file';
        $f = $this->get_setting();

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        if     ( $type == "stock_in"      )  $type_str = "입고";
        else if( $type == "stock_retin"   )  $type_str = "반품입고";
        else if( $type == "stock_allin"   )  $type_str = "총입고";
        else if( $type == "stock_out"     )  $type_str = "출고";
        else if( $type == "stock_retout"  )  $type_str = "반품출고";
        else if( $type == "stock_allout"  )  $type_str = "총출고";
        else if( $type == "stock_trans"   )  $type_str = "배송";
        else if( $type == "stock_arrange" )  $type_str = "조정";
        else if( $type == "stock_current" )  $type_str = "현재고";

        // 헤더
        $buffer = "<tr>\n";
        if( $f[supply_code     ] ) $buffer .= "<td class=header_item>공급처코드         </td>";
        if( $f[supply_name     ] ) $buffer .= "<td class=header_item>공급처명           </td>";
        if( $f[product_id      ] ) $buffer .= "<td class=header_item>상품코드           </td>";
        if( $f[product_name    ] ) $buffer .= "<td class=header_item>상품명             </td>";
        if( $f[options         ] ) $buffer .= "<td class=header_item>옵션               </td>";
        if( $f[name_options    ] ) $buffer .= "<td class=header_item>상품명+옵션        </td>";
        if( $f[brand           ] ) $buffer .= "<td class=header_item>공급처상품명       </td>";
        if( $f[supply_options  ] ) $buffer .= "<td class=header_item>공급처옵션         </td>";
        if( $f[location        ] ) $buffer .= "<td class=header_item>로케이션           </td>";
        if( $f[type_qty        ] ) $buffer .= "<td class=header_item>$type_str 수량     </td>";
        if( $f[org_price       ] ) $buffer .= "<td class=header_item>원가               </td>";
        if( $f[total_org_price ] ) $buffer .= "<td class=header_item>총원가             </td>";
        if( $f[shop_price      ] ) $buffer .= "<td class=header_item>판매가             </td>";
        if( $f[total_shop_price] ) $buffer .= "<td class=header_item>총판매가           </td>";
        if( $f[memo            ] ) $buffer .= "<td class=header_item>상품메모           </td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                if( $key == 'supply_code'      ||    
                    $key == 'supply_name'      || 
                    $key == 'product_id'       || 
                    $key == 'product_name'     || 
                    $key == 'options'          || 
                    $key == 'name_options'     || 
                    $key == 'brand'            || 
                    $key == 'supply_options'   || 
                    $key == 'location'         || 
                    $key == 'memo' )
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

    function download_FL20()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "supply_list_detail.xls" );
    }    

    //###############################
    // 조회 필드 설정팝업
    //###############################
    function FL21()
    {
        global $template, $connect, $title;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $title = 'FL20';
        $f = $this->get_setting(1);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //*****************************
    // 조회 필드 읽기
    //*****************************
    function get_setting($field_set = 0)
    {
        global $connect, $title;
        
        $f = array();

        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            $enable_org_price = false;
        else 
            $enable_org_price = true;
            
        // 조회
        $query = "select field from field_set where title='$title'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        foreach( explode(",",$data[field]) as $_f )
        {
            if( !trim($_f) )  continue;
            
            // 원가조회불가
            if( !$field_set && !$enable_org_price && ( $_f == "amount_in"      ||
                                                       $_f == "amount_retin"   ||
                                                       $_f == "amount_allin"   ||
                                                       $_f == "amount_out"     ||
                                                       $_f == "amount_retout"  ||
                                                       $_f == "amount_allout"  ||
                                                       $_f == "amount_trans"   ||
                                                       $_f == "amount_arrange" ||
                                                       $_f == "amount_current" ||
                                                       $_f == "org_price"      ||
                                                       $_f == "total_org_price" ) )  continue;
            $f[$_f] = 1;
        }

        // 다운로드
        $query = "select field from field_set where title='{$title}_file'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        foreach( explode(",",$data[field]) as $_f )
        {
            // 원가조회불가
            if( !$field_set && !$enable_org_price && ( $_f == "amount_in"      ||
                                                       $_f == "amount_retin"   ||
                                                       $_f == "amount_allin"   ||
                                                       $_f == "amount_out"     ||
                                                       $_f == "amount_retout"  ||
                                                       $_f == "amount_allout"  ||
                                                       $_f == "amount_trans"   ||
                                                       $_f == "amount_arrange" ||
                                                       $_f == "amount_current" ||
                                                       $_f == "org_price"      ||
                                                       $_f == "total_org_price" ) )  continue;
            $f["dn_" . $_f] = 1;
        }
        
        return $f;
    }
    
    //*****************************
    // 조회 필드 설정
    //*****************************
    function save_setting()
    {
        global $connect, $title, $setting, $dn_setting;
        
        $query = "update field_set set field='$setting' where title='$title'";
        mysql_query($query, $connect);

        $query = "update field_set set field='$dn_setting' where title='{$title}_file'";
        mysql_query($query, $connect);
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

    function array_array_sort($multiArray, $keyColumn) {
        foreach($multiArray as $tmpRecords){
            $sortColumn[] = $tmpRecords[$keyColumn];        
        }
        array_multisort($sortColumn, SORT_ASC, $multiArray);
        return $multiArray;
    }
    
    function array_array_rsort($multiArray, $keyColumn) { 
        foreach($multiArray as $tmpRecords){
            $sortColumn[] = $tmpRecords[$keyColumn];        
        }
        array_multisort($sortColumn, SORT_DESC, $multiArray);
        return $multiArray;
    }    
}
?>
