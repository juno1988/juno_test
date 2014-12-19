<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_IF00 extends class_top
{
    /////////////////////////////////////
    // 판매처 주문 상세
    /////////////////////////////////////
    function IF00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $query_search_type, $query_str, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;
        global $str_supply_code, $multi_supply_group, $multi_supply;

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

        $arr_wh = class_stock::get_warehouse_info();
        $cnt_wh = count($arr_wh);
        
        // link url
        $par = array('template','str_supply_code','multi_supply_group','multi_supply','query_search_type','query_str','wh_sel', 'stock_type', 'query_type', 'start_date', 'end_date','except_soldout');
        $link_url = $this->build_link_url3( $par );
        $line_per_page = 50;
        
        $query = $this->get_IF00();
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result );
        
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_qty];
            $total_amount += $data[amount];
        }

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_IF00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_search_type, $query_str, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;
 		global $str_supply_code, $multi_supply_group, $multi_supply;
 		
        $query = "select b.name        b_name,
                         a.product_id  a_product_id,
                         a.name        a_name, 
                         a.options     a_options,
                         a.org_price   a_org_price,
                         a.enable_sale a_enable_sale
                    from products a,
                         userinfo b
                   where a.supply_code = b.code and
                         a.is_represent = 0 and
                         a.is_delete = 0";

        if( $str_supply_code )
				$query .= " and a.supply_code in ( $str_supply_code ) ";
		if($multi_supply)
			$query .= " and a.supply_code in ( $multi_supply ) ";

debug("창고재고조회 : $query_str / $query_type");
        if( $query_str )
        {
            $query_str = trim($query_str);
            
            switch( $query_search_type )
            {
                case 'name':
                    $query .= " and a.name like '%$query_str%' ";
                    break;
                case 'options':
                    $query .= " and a.options like '%$query_str%' ";
                    break;
                case 'name_options':
                    list($query_str1, $query_str2) = split(" ", $query_str, 2);
                    $string1 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str1) );
                    $string2 = str_replace( array("%","_"), array("\\%","\\_"), trim($query_str2) );
                    $query .= " and a.name like '%$string1%' and a.options like '%$string2%' ";
                    break;
                case 'product_id':
                    $query .= " and a.product_id = '$query_str'";
                    break;
                case 'barcode':
                    $query .= " and a.barcode = '$query_str'";
                    break;
                case 'origin':
                    $query .= " and a.origin like '%$query_str%' ";
                    break;
            }
        }
        
        if( $except_soldout == 1 )
            $query .= " and a.enable_sale = 1 ";
        else if( $except_soldout == 2 )
            $query .= " and a.enable_sale = 0 ";

        $query .= " order by b.name, a.name, a.options ";
debug("창고재고조회 : " . $query);
        return $query;
    }   

    function save_file_IF00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_search_type, $query_str, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;
		global $str_supply_code, $multi_supply_group, $multi_supply;
		
        $query = $this->get_IF00();
        $result = mysql_query($query, $connect);

        $arr_wh = class_stock::get_warehouse_info();
        $cnt_wh = count($arr_wh);
        
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();

        $arr_sum1 = array();
        $arr_sum2 = array();
        
        while( $data = mysql_fetch_assoc($result) )
        {
            // 기본창고
            $data_stock = class_stock::get_current_stock( $data[a_product_id] );
            
            // 불량창고
            $data_stock_bad = class_stock::get_current_stock( $data[a_product_id],1 );

            // 송장
/*            
            $query_7 = "select sum(b.qty) sum_b_qty 
                          from orders a, order_products b 
                         where a.seq = b.order_seq and 
                               a.status = 7 and
                               b.order_cs not in (1,2,3,4) and
                               b.product_id = '$data[a_product_id]'";
            $result_7 = mysql_query($query_7, $connect);
            $data_7 = mysql_fetch_assoc($result_7);
            $qty_7 = ( $data_7[sum_b_qty] > 0 ? number_format($data_7[sum_b_qty]) : "" );
*/
            $qty_7 = class_stock::get_ready_stock($data[a_product_id]);  
            $qty_7 = ( $qty_7 > 0 ? number_format($qty_7) : "" );
            
            // 접수
/*
            $query_1 = "select sum(b.qty) sum_b_qty 
                          from orders a, order_products b 
                         where a.seq = b.order_seq and 
                               a.status = 1 and
                               b.order_cs not in (1,2,3,4) and
                               b.product_id = '$data[a_product_id]'";
            $result_1 = mysql_query($query_1, $connect);
            $data_1 = mysql_fetch_assoc($result_1);
            $qty_1 = ( $data_1[sum_b_qty] > 0 ? number_format($data_1[sum_b_qty]) : "" );
*/            
            $qty_1 = class_stock::get_ready_stock2($data[a_product_id]);  
            $qty_1 = ( $qty_1 > 0 ? number_format($qty_1) : "" );

            $arr_temp = array(
                'supply'     => $data[b_name]       ,
                'product_id' => $data[a_product_id] ,
                'name'       => $data[a_name]       ,
                'options'    => $data[a_options]    ,
                'stock'      => $data_stock         ,
                'bad'        => $data_stock_bad     ,
                'qty_7'      => $qty_7              ,
                'qty_1'      => $qty_1
            );

            $arr_sum1[stock] += $data_stock;
            $arr_sum1[bad] += $data_stock_bad;
            $arr_sum1[qty_7] += $qty_7;
            $arr_sum1[qty_1] += $qty_1;
            
            $arr_sum2[stock] += $data_stock * $data[a_org_price];
            $arr_sum2[bad] += $data_stock_bad * $data[a_org_price];
            $arr_sum2[qty_7] += $qty_7 * $data[a_org_price];
            $arr_sum2[qty_1] += $qty_1 * $data[a_org_price];
            
            // 현재고
            if( $query_type == 'stock' )
            {
                $query_wh = "select * from current_stock_wh 
                               where product_id = '$data[a_product_id]' and 
                                     bad = $stock_type";
                if( $wh_sel )
                    $query_wh .= " and wh='$wh_sel'";
                else
                    $query_wh .= " order by wh";
            }
            // 입고
            else if( $query_type == 'in' )
            {
                $query_wh = "select sum(qty) stock, wh 
                               from stock_tx_history_wh 
                              where product_id = '$data[a_product_id]' and
                                    crdate >= '$start_date 00:00:00' and
                                    crdate <= '$end_date 23:59:59' and
                                    job = 'in' and
                                    bad = $stock_type";
                if( $wh_sel )
                    $query_wh .= " and wh='$wh_sel' group by wh";
                else
                    $query_wh .= " group by wh order by wh";
            }
            // 출고
            else if( $query_type == 'out' )
            {
                $query_wh = "select sum(qty) stock, wh
                               from stock_tx_history_wh 
                              where product_id = '$data[a_product_id]' and
                                    crdate >= '$start_date 00:00:00' and
                                    crdate <= '$end_date 23:59:59' and
                                    job = 'out' and
                                    bad = $stock_type";
                if( $wh_sel )
                    $query_wh .= " and wh='$wh_sel' group by wh";
                else
                    $query_wh .= " group by wh order by wh";
            }

            // 창고별 데이터
            if( $wh_sel )
            {
                $result_wh = mysql_query($query_wh, $connect);
                $data_wh = mysql_fetch_assoc($result_wh);

                $val = number_format($data_wh[stock]);
                if( $val == 0 )  $val = "";
                $arr_temp[$wh_sel] = $val;
                
                $arr_sum1[$wh_sel] += $val;
                $arr_sum2[$wh_sel] += $val * $data[a_org_price];
            }
            else
            {
                $arr = array();
                
                $result_wh = mysql_query($query_wh, $connect);
                while( $data_wh = mysql_fetch_assoc($result_wh) )
                    $arr[$data_wh[wh]] = $data_wh[stock];
    
                for($i=0; $i<$cnt_wh; $i++)
                {
                    $val = number_format( $arr[$arr_wh[$i]] );
                    if( $val == 0 )  $val = "";
                    $arr_temp[$arr_wh[$i]] = $val;

                    $arr_sum1[$arr_wh[$i]] += $val;
                    $arr_sum2[$arr_wh[$i]] += $val * $data[a_org_price];
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
        
        $arr_sum1[supply] = "";
        $arr_sum1[product_id] = "";
        $arr_sum1[name] = "";
        $arr_sum1[options] = "";
        
        $arr_datas[] = $arr_sum1;

        $arr_sum2[supply] = "";
        $arr_sum2[product_id] = "";
        $arr_sum2[name] = "";
        $arr_sum2[options] = "";
        
        $arr_datas[] = $arr_sum2;

        $this->make_file_IF00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_IF00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $period, $start_date, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;

        $arr_wh = class_stock::get_warehouse_info();
        $cnt_wh = count($arr_wh);
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item>공급처</td>\n";
        $buffer .= "<td class=header_item>상품코드</td>\n";
        $buffer .= "<td class=header_item>상품명</td>\n";
        $buffer .= "<td class=header_item>옵션</td>\n";
        $buffer .= "<td class=header_item>기본창고</td>\n";
        $buffer .= "<td class=header_item>$_SESSION[EXTRA_STOCK_TYPE]</td>\n";
        $buffer .= "<td class=header_item>송장</td>\n";
        $buffer .= "<td class=header_item>접수</td>\n";
        
        if( $wh_sel )
            $buffer .= "<td class=header_item>" . $wh_sel . "</td>\n";
        else
        {
            foreach($arr_wh as $wh)
                $buffer .= "<td class=header_item>" . $wh . "</td>\n";
        }
        
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        $data_cnt = count($arr_datas);
        
        $i = 0;
        foreach( $arr_datas as $val )
        {
            if( $i++ >= $data_cnt-2 )  continue;

            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                if( $key == 'supply' || $key == 'product_id' || $key == 'name' || $key == 'options' )
                    $buffer .= "<td class=str_item>$v</td>\n";
                else
                    $buffer .= "<td class=num_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }

        // 수량합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=4>수량합계</td>";
        foreach( $arr_datas[$data_cnt-2] as $key => $v )
        {
            if( $key == 'supply' || $key == 'product_id' || $key == 'name' || $key == 'options' )
                continue;
            else
                $buffer .= "<td class=num_item>$v</td>\n";
        }
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);
        
        // 금액합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=4>금액합계</td>";
        foreach( $arr_datas[$data_cnt-1] as $key => $v )
        {
            if( $key == 'supply' || $key == 'product_id' || $key == 'name' || $key == 'options' )
                continue;
            else
                $buffer .= "<td class=num_item>$v</td>\n";
        }
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_IF00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"창고재고조회.xls"));
    }    
    

    /////////////////////////////////////
    // 고객주문건요청
    /////////////////////////////////////
    function IF20()
    {
        global $template, $connect, $start_date, $end_date, $wh, $string;

        if( !$start_date )  $start_date = date('Y-m-d', strtotime('-7 day'));
        
        $query = "select * from wh_req_sheet 
                   where crdate>='$start_date 00:00:00' and
                         crdate<='$end_date 23:59:59'";
        if( $wh )
            $query .= " and wh = '$wh'";
        
        if( $string )
            $query .= " and sheet_name like '%$string%' ";
        
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // 고객주문건요청 추가
    /////////////////////////////////////
    function IF21()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_search_type, $query_str, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // 고객주문건요청 상품추가
    /////////////////////////////////////
    function IF22()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_id, $name, $options;

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
            
            // link url
            $par = array('template','supply_id', 'seq', 'name', 'options');
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

            if( $name )
                $query .= " and a.name like '%$name%' ";
                
            if( $options )
                $query .= " and a.options like '%$options%' ";
    
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
    
    /////////////////////////////////////
    // 고객주문건요청 전표등록
    /////////////////////////////////////
    function reg_sheet()
    {
        global $connect, $wh, $sheet_name, $product_id_list, $qty_list, $memo_list;
        
        // 전표 등록
        $query = "insert wh_req_sheet 
                     set crdate = now(),
                         wh = '$wh',
                         sheet_name = '$sheet_name',
                         worker = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query, $connect);
        
        // 전표 번호
        $query = "select max(seq) max_seq from wh_req_sheet where wh='$wh' and sheet_name='$sheet_name'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $sheet_no = $data[max_seq];
        
        // 리스트 등록
        $prd_arr  = explode(","     , substr($product_id_list,0,-1));
        $qty_arr  = explode(","     , substr($qty_list       ,0,-1));
        $memo_arr = explode("&pimz&", substr($memo_list      ,0,-6));
        foreach( $prd_arr as $key => $value )
        {
            $query = "insert wh_req_item
                         set sheet = '$sheet_no',
                             product_id = '$value',
                             qty = '" . $qty_arr[$key] . "',
                             memo = '" . $memo_arr[$key] . "'";
            mysql_query($query, $connect);
        }
        
    }

    /////////////////////////////////////
    // 고객주문건요청 상세
    /////////////////////////////////////
    function IF23()
    {
        global $template, $connect, $seq;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // 고객주문건요청 전표수정
    /////////////////////////////////////
    function modify_sheet()
    {
        global $connect, $seq_list, $memo_list;
        
        // 리스트 등록
        $seq_arr  = explode(","     , substr($seq_list ,0,-1));
        $memo_arr = explode("&pimz&", substr($memo_list,0,-6));
        foreach( $seq_arr as $key => $value )
        {
            $query = "update wh_req_item
                         set memo = '" . $memo_arr[$key] . "'
                       where seq = '" . $seq_arr[$key] . "'";
            mysql_query($query, $connect);
        }
        
    }
    
    /////////////////////////////////////
    // 고객주문건요청 상품삭제
    /////////////////////////////////////
    function del_sheet_item()
    {
        global $template, $connect, $seq;
        
        $query = "update wh_req_item
                     set is_del=1,
                         del_date = now(),
                         del_worker = '$_SESSION[LOGIN_NAME]'
                   where seq = $seq";
        mysql_query($query, $connect);
    }


    /////////////////////////////////////
    // A/S건요청
    /////////////////////////////////////
    function IF30()
    {
        global $template, $connect, $start_date, $end_date, $wh, $string;

        if( !$start_date )  $start_date = date('Y-m-d', strtotime('-7 day'));
        
        $query = "select * from wh_req_sheet2 
                   where crdate>='$start_date 00:00:00' and
                         crdate<='$end_date 23:59:59'";
        if( $wh )
            $query .= " and wh = '$wh'";
        
        if( $string )
            $query .= " and sheet_name like '%$string%' ";
        
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // A/S건요청 추가
    /////////////////////////////////////
    function IF31()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_search_type, $query_str, $wh_sel, $stock_type, $query_type, $start_date, $end_date, $except_soldout;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // A/S건요청 상품추가
    /////////////////////////////////////
    function IF32()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_id, $name, $options;

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
            
            // link url
            $par = array('template','supply_id', 'seq', 'name', 'options');
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

            if( $name )
                $query .= " and a.name like '%$name%' ";
                
            if( $options )
                $query .= " and a.options like '%$options%' ";
    
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
    
    /////////////////////////////////////
    // A/S건요청 전표등록
    /////////////////////////////////////
    function reg_sheet2()
    {
        global $connect, $wh, $sheet_name, $product_id_list, $qty_list, $memo_list;
        
        // 전표 등록
        $query = "insert wh_req_sheet2
                     set crdate = now(),
                         wh = '$wh',
                         sheet_name = '$sheet_name',
                         worker = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query, $connect);
        
        // 전표 번호
        $query = "select max(seq) max_seq from wh_req_sheet2 where wh='$wh' and sheet_name='$sheet_name'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $sheet_no = $data[max_seq];
        
        // 리스트 등록
        $prd_arr  = explode(","     , substr($product_id_list,0,-1));
        $qty_arr  = explode(","     , substr($qty_list       ,0,-1));
        $memo_arr = explode("&pimz&", substr($memo_list      ,0,-6));
        foreach( $prd_arr as $key => $value )
        {
            $query = "insert wh_req_item2
                         set sheet = '$sheet_no',
                             product_id = '$value',
                             qty = '" . $qty_arr[$key] . "',
                             memo = '" . $memo_arr[$key] . "'";
            mysql_query($query, $connect);
        }
        
    }

    /////////////////////////////////////
    // A/S건요청 상세
    /////////////////////////////////////
    function IF33()
    {
        global $template, $connect, $seq;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    /////////////////////////////////////
    // A/S건요청 전표수정
    /////////////////////////////////////
    function modify_sheet2()
    {
        global $connect, $seq_list, $memo_list;
        
        // 리스트 등록
        $seq_arr  = explode(","     , substr($seq_list ,0,-1));
        $memo_arr = explode("&pimz&", substr($memo_list,0,-6));
        foreach( $seq_arr as $key => $value )
        {
            $query = "update wh_req_item2
                         set memo = '" . $memo_arr[$key] . "'
                       where seq = '" . $seq_arr[$key] . "'";
            mysql_query($query, $connect);
        }
        
    }
    
    /////////////////////////////////////
    // A/S건요청 상품삭제
    /////////////////////////////////////
    function del_sheet_item2()
    {
        global $template, $connect, $seq;
        
        $query = "update wh_req_item2
                     set is_del=1,
                         del_date = now(),
                         del_worker = '$_SESSION[LOGIN_NAME]'
                   where seq = $seq";
        mysql_query($query, $connect);
    }
}
?>
