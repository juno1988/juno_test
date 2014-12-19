<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_ui.php";
require_once "class_supply.php";
require_once "class_category.php";
require_once "class_multicategory.php";
require_once "Classes/PHPExcel.php";

class class_IL00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function IL00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $start_date, $end_date, $product_id, $name, $options, $enable_stock_type,
               $order_status, $is_all, $except_soldout, $stock_option, $nostock_option, $stock_alarm1, $stock_alarm2,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
		global $multi_supply_group, $multi_supply, $str_supply_code;
        global $product_qty_list;
        
        

        // 불량창고 이름
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        // 조회 필드
        $page_code = 'IL00';
        $f = $this->get_setting();
        
        if( $search )
        {
            $this->show_wait();
            
            // 전체 쿼리
            $data_all = $this->get_IL00($f, &$total_rows);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        else
        {
            // 조회 조건
            $page_code = 'IL00_search';
            $f_search = $this->get_setting();
            
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
            $is_all         = $f_is_all        ;
            $except_soldout = $f_except_soldout;
            $stock_option   = $f_stock_option  ;
            $nostock_option = $f_nostock_option;
            $stock_alarm1   = $f_stock_alarm1  ;
            $stock_alarm2   = $f_stock_alarm2  ;
        }
            
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 메인 쿼리
    //###############################
    function get_IL00($f, &$total_rows, $is_download=0)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $start_date, $end_date, $product_id, $name, $options, $enable_stock_type,
               $order_status, $is_all, $except_soldout, $stock_option, $nostock_option, $stock_alarm1, $stock_alarm2,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
               
        global $multi_supply_group, $multi_supply, $str_supply_code;
        
        global $product_qty_list;
        
        // 상품추가
        if( $product_qty_list )
        {
            // 상품리스트
            $req_qty_arr = array();
            $product_id_list = "";
            foreach( explode(",", $product_qty_list) as $req_val )
            {
                list($req_pid, $not_trans, $req_qty) = explode(":", $req_val);
                $req_qty_arr[$req_pid] = $req_qty;
                $product_id_list .= "'$req_pid',";
            }
            $product_id_list = substr($product_id_list,0,-1);
        }
        
        $query = "select c.supply_code    c_supply_code,
                         c.product_id     c_product_id,
                         c.barcode        c_barcode,
                         c.name           c_name,
                         c.options        c_options,
                         c.brand          c_brand,
                         c.supply_options c_supply_options,
                         c.org_id         c_org_id,
                         c.img_500        c_img_500,
                         c.org_price      c_org_price,
                         c.enable_sale    c_enable_sale,
                         c.location       c_location,
                         c.stock_alarm1   c_stock_alarm1,
                         c.stock_alarm2   c_stock_alarm2,
                         c.category       c_category,
                         c.m_category1    c_m_category1,
                         c.m_category2    c_m_category2,
                         c.m_category3    c_m_category3,
                         c.str_category   c_str_category,
                         c.reg_date       c_reg_date,
                         sum(b.qty)       sum_b_qty
                    from orders a,
                         order_products b,
                         products c
                   where a.seq=b.order_seq and
                         b.product_id=c.product_id and
                         a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and
                         b.order_cs not in (1,2,3,4) and
                         c.is_delete=0 and
                         c.is_represent=0 ";


        if( $str_supply_code )
            $query .= " and c.supply_code in ( $str_supply_code ) ";
		if($multi_supply)
			$query .= " and c.supply_code in ( $multi_supply ) ";

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
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        $start_time = time();
    
        // 미배송    
        $sum_stock1 = 0;
        // 부족수량
        $sum_stock2 = 0;
    
        // 공급처정보
        $supply_info = array();
        $query_supply = "select a.code     a_code,
                                a.name     a_name,
                                a.tel      a_tel,
                                a.mobile   a_mobile,
                                a.address1 a_address1,
                                a.address2 a_address2,
                                b.name     b_name
                           from userinfo a left outer join supply_group b on a.group_id=b.group_id
                          where level = 0";
        $result_supply = mysql_query($query_supply, $connect);
        while( $data_supply = mysql_fetch_assoc($result_supply) )
        {
            $supply_info[$data_supply[a_code]]["supply_name"] = $data_supply[a_name];
            $supply_info[$data_supply[a_code]]["supply_tel"] = $data_supply[a_tel] . " / " . $data_supply[a_mobile];
            $supply_info[$data_supply[a_code]]["supply_address"] = $data_supply[a_address1] . " " . $data_supply[a_address2];
            $supply_info[$data_supply[a_code]]["group_name"] = $data_supply[b_name];
        }
    
        $all_product_list = "";
        
        // 전체 데이타
        $data_all = array();
        $product_id_all = "";
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
                    echo "<script type='text/javascript'>show_txt( '$i / $total_rows' )</script>";
                flush();
            }
            
            // 헤더정렬, 상품추가, 다운로드 작업시 요청수량을 입력 값으로
            $data[req_qty] = $req_qty_arr[$data[c_product_id]];

            // 출력 정보 가져오기
            $temp_arr = $this->get_data_arr($data, $f, $supply_info);

            // 전체(부족수량 0 포함)이 아닐경우, 부족수량 0은 넘어간다.(상품추가용 조회 아닐때)
            if( !$is_all && $temp_arr[lack_qty] == 0 && !$product_qty_list )  continue;
    
            // 미배송 합계
            $sum_stock1 += $temp_arr[not_trans];
            // 부족수량 합계
            $sum_stock2 += $temp_arr[lack_qty];
    
            $data_all[] = $temp_arr;
            $product_id_all .= ($product_id_all ? "," : "") . "'$data[c_product_id]'";

            $all_product_list .= "'$data[c_product_id]',";
            usleep(10000);
        }
    
        // 재고 있는 옵션, 없는 옵션 포합
        if( ($stock_option || $nostock_option) && !$product_qty_list  )
        {
            // 상품코드 리스트
            $org_id_all = "";
            $query_prd = "select org_id from products where product_id in ($product_id_all) group by org_id";
            $result_prd = mysql_query($query_prd, $connect);
            while( $data_prd = mysql_fetch_assoc($result_prd) )
                $org_id_all .= ($org_id_all ? "," : "") . "'$data_prd[org_id]'";
            
            $product_id_stock = "";
            $query_prd = "select a.supply_code    c_supply_code,
                                 a.product_id     c_product_id,
                                 a.name           c_name,
                                 a.options        c_options,
                                 a.brand          c_brand,
                                 a.supply_options c_supply_options,
                                 a.org_id         c_org_id,
                                 a.img_500        c_img_500,
                                 a.stock_alarm1   c_stock_alarm1,
                                 a.stock_alarm2   c_stock_alarm2
                            from products a, 
                                 current_stock b 
                           where a.product_id = b.product_id and 
                                 b.bad = 0 and ";
    
            // 재고 기준수량
            $stock_base = 0;
            if( $stock_alarm1 )
                $stock_base = "a.stock_alarm1";
            else if( $stock_alarm2 )
                $stock_base = "a.stock_alarm2";
            
            if( $stock_option && !$nostock_option )
                $query_prd .= "  b.stock > $stock_base and ";
            else if( !$stock_option && $nostock_option )
                $query_prd .= "  b.stock <= $stock_base and ";
    
            $query_prd .= "      a.org_id in ($org_id_all) and
                                 a.product_id not in ($product_id_all)";
    
            // 재고관리
            if( $enable_stock_type == 0 )
                $query_prd .= " and a.enable_stock=1 ";
            else if( $enable_stock_type == 1 )
                $query .= " and a.enable_stock=0 ";
    
            // 품절제외
            if( $except_soldout )
                $query_prd .= " and a.enable_sale=1";
    
            $result_prd = mysql_query($query_prd, $connect);
    
            $org_order_status = $order_status;
            $order_status = 0;
    
            while( $data_prd = mysql_fetch_assoc($result_prd) )
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
        
                // 헤더정렬, 상품추가, 다운로드 작업시 요청수량을 입력 값으로
                $data_prd[req_qty] = $req_qty_arr[$data_prd[c_product_id]];

                // 출력 정보 가져오기
                $temp_arr = $this->get_data_arr($data_prd, $f, $supply_info);
                
                // 미배송 합계
                $sum_stock1 += $temp_arr[not_trans];
                // 부족수량 합계
                $sum_stock2 += $temp_arr[lack_qty];
        
                $data_all[] = $temp_arr;
        
                $all_product_list .= "'$data[c_product_id]',";
                usleep(10000);
            }
    
            $order_status = $org_order_status;
        }

        // 조건 조회로 추가된 상품리스트
        $all_product_list = substr($all_product_list,0,-1);
        
        // 상품추가
        if( $product_qty_list )
        {
            $query = "select c.supply_code    c_supply_code,
                             c.product_id     c_product_id,
                             c.barcode        c_barcode,
                             c.name           c_name,
                             c.options        c_options,
                             c.brand          c_brand,
                             c.supply_options c_supply_options,
                             c.org_id         c_org_id,
                             c.img_500        c_img_500,
                             c.org_price      c_org_price,
                             c.enable_sale    c_enable_sale,
                             c.location       c_location,
                             c.stock_alarm1   c_stock_alarm1,
                             c.stock_alarm2   c_stock_alarm2
                        from products c
                       where c.product_id in ($product_id_list) ";
            if( $all_product_list )
                $query .= " and c.product_id not in ($all_product_list)";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                // 헤더정렬, 상품추가, 다운로드 작업시 요청수량을 입력 값으로
                $data[req_qty] = $req_qty_arr[$data[c_product_id]];
    
                // 출력 정보 가져오기
                $temp_arr = $this->get_data_arr($data, $f, $supply_info);
    
                // 미배송 합계
                $sum_stock1 += $temp_arr[not_trans];
                // 부족수량 합계
                $sum_stock2 += $temp_arr[lack_qty];
        
                $data_all[] = $temp_arr;
                usleep(10000);
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
            if( $s_val[field] == "stock"       ||
                $s_val[field] == "not_trans"   ||
                $s_val[field] == "lack_qty"    ||
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
    function save_file_IL00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $start_date, $end_date, $product_id, $name, $options, $enable_stock_type,
               $order_status, $is_all, $except_soldout, $stock_option, $nostock_option, $stock_alarm1, $stock_alarm2,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        
        global $multi_supply_group, $multi_supply, $str_supply_code;
        global $product_qty_list;
        
        // 조회 필드
        $page_code = 'IL00_file';
        $f = $this->get_setting();
        
        // 전체 쿼리
        $data_all = $this->get_IL00($f, &$total_rows, 1);

        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_IL00( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_IL00( $arr_datas, $fn, $f )
    {
        global $connect, $is_date, $sheet_title, $req_date, $supply_memo;

        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        $col = 0;
        $row = 1;
        
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
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFont()->setBold(true);
        
        foreach ($arr_datas as $arr_val) {
            $row++;
            $col = 0;
            foreach( $f as $f_val )
            {
                if( $f_val[chk] )
                {
                    $d_key = $f_val[field_id];
                    $d_val = $arr_val[$d_key];
                    
                    // 폭 계산
                    $new_width = strlen( iconv('utf-8','cp949',$d_val) );
                    if( $cell_width[$d_key] < $new_width )  
                        $cell_width[$d_key] = $new_width;
    
                    if( $d_key == "stock"        ||
                        $d_key == "not_trans"    ||
                        $d_key == "lack_qty"     ||
                        $d_key == "org_price"    ||
                        $d_key == "stock_alarm1" ||
                        $d_key == "stock_alarm2" ||
                        $d_key == "request_qty" )
                    {
                        $cell = $sheet->getCellByColumnAndRow($col, $row);
                        $cell->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
                    }
                    else
                        $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($d_val, PHPExcel_Cell_DataType::TYPE_STRING);
                    $col++;
                }
            }
        }
        
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
        $styleArray = array(
        	'font' => array(
        		'name' => '굴림체',
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

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        return $filename;
    }

    //###############################
    // 다운로드
    //###############################
    function download_IL00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

    //###############################
    // 조회 필드 설정팝업
    //###############################
    function IL01()
    {
        global $template, $connect, $page_code;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $page_code = 'IL00';
        $f = $this->get_setting(1);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 다운로드 필드 설정팝업
    //###############################
    function IL02()
    {
        global $template, $connect, $page_code;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $page_code = 'IL00_file';
        $f = $this->get_setting(1);
                
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 기본검색조건 설정팝업
    //###############################
    function IL03()
    {
        global $template, $connect, $page_code;

        //++++++++++++++++++++++++
        // 불량창고 이름
        //++++++++++++++++++++++++
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        $page_code = 'IL00_search';
        $f = $this->get_setting(1);

        foreach($f as $f_val)
        {
            $f_var = "f_$f_val[field_id]";
            $$f_var = $f_val[field_name];
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
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
    // 상품추가 팝업
    //###############################
    function IL20()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_code, $name, $options, $query_type, $search_string;

        // 작업중
        $this->show_wait();

        // 페이지
        if( !$page )
            $page = 1;
        else
        {
            $line_per_page = 15;

            $name = trim( $name );
            $options = trim( $options );
            
            // link url
            $par = array('template','supply_code', 'name', 'options');
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
           
            if( $supply_code )
                $query .= " and a.supply_code = $supply_code ";

            if( $search_string )
            {
                switch( $query_type )
                {
                    case "name":
                        $query .= " and a.name like '%$search_string%' ";
                        break;
                    case "options":
                        $query .= " and a.options like '%$search_string%' ";
                        break;
                    case "barcode":
                        $query .= " and a.barcode = '$search_string' ";
                        break;
                }
            }
    
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
    function get_setting($field_set = 0)
    {
        global $connect, $sys_connect, $page_code;
        
        $f = array();

        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            $enable_org_price = false;
        else 
            $enable_org_price = true;
            
        // 조회
        $query = "select * from field_set_detail where page_code='$page_code' order by order_no";
        $result = mysql_query($query, $connect);

        // 화면 최초 오픈시 자료 없을 경우 시스템 설정으로 세팅. 조회, 다운로드 
        if( !mysql_num_rows($result) )
        {
            $query = "select * from sys_field_set_detail where page_code in ('$page_code', '${page_code}_file', '${page_code}_search') ";
            $result = mysql_query($query, $sys_connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query = "insert field_set_detail
                             set page_code  = '$data[page_code]',
                                 order_no   = '$data[order_no]',
                                 field_id   = '$data[field_id]',
                                 field_name = '$data[field_name]',
                                 chk        = '$data[chk]',
                                 header     = '$data[header]',
                                 sort       = '$data[sort]'";
                mysql_query($query, $connect);
            }
            
            $query = "select * from field_set_detail where page_code='$page_code' order by order_no";
            $result = mysql_query($query, $connect);
        }

        while( $data = mysql_fetch_assoc($result) )
        {
            // 원가조회불가
            if( !$field_set && !$enable_org_price && ( $_f == "org_price"      ||
                                                       $_f == "total_org_price" ) )  continue;
            $f[$data[order_no]] = array(
                "field_id"   => $data[field_id],
                "field_name" => $data[field_name],
                "chk"        => $data[chk],
                "header"     => $data[header],
                "sort"       => $data[sort]
            );
        }
        
        return $f;
    }
    
    //*****************************
    // 조회 필드 설정
    //*****************************
    function save_setting()
    {
        global $connect, $page_code, $setting, $dn_setting;
        
        // 기존자료 삭제
        $query = "delete from field_set_detail where page_code='$page_code'";
        mysql_query($query,$connect);
        
        // setting 파싱
        foreach( explode(",", $setting) as $set_val )
        {
            list($order_no, $chk, $field_id, $field_name, $header, $sort) = explode(":", $set_val);
            
            $field_name = stripslashes($field_name);
            $field_name = urldecode($field_name);
            $field_name = addslashes($field_name);
            
            $header = stripslashes($header);
            $header = urldecode($header);
            $header = addslashes($header);
            
            $query = "insert field_set_detail
                         set page_code  = '$page_code',
                             order_no   = '$order_no',
                             field_id   = '$field_id',
                             field_name = '$field_name',
                             chk        = '$chk',
                             header     = '$header',
                             sort       = '$sort'";
            mysql_query($query, $connect);
        }
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
    // 출력 row 생성하기
    //*****************************
    function get_data_arr($data, $f, $supply_info)
    {
        global $stock_alarm1, $stock_alarm2, $order_status, $connect;
        
        // 현재고
        $cur_stock = class_stock::get_current_stock( $data[c_product_id] );

        $base_stock = 0;
        if( $stock_alarm1 )
            $base_stock = $data[c_stock_alarm1];
        else if( $stock_alarm2 )
            $base_stock = $data[c_stock_alarm2];
        
        // 부족수량
        $lack_qty = $data[sum_b_qty] - $cur_stock + $base_stock;
        $lack_qty = ( $lack_qty > 0 ? $lack_qty : 0 );
        
        $temp_arr = array();
        foreach( $f as $f_val )
        {
            if( !$f_val[chk] && !$f_val[sort] )  continue;

            // 공급처코드
            if     ( $f_val[field_id] == "supply_code"    )
                $temp_arr[$f_val[field_id]] = $data[c_supply_code];
                
            // 공급처그룹
            else if( $f_val[field_id] == "supply_group"   ) 
                $temp_arr[$f_val[field_id]] = $supply_info[$data[c_supply_code]]["group_name"];
                
            // 공급처명
            else if( $f_val[field_id] == "supply_name"    ) 
                $temp_arr[$f_val[field_id]] = $supply_info[$data[c_supply_code]]["supply_name"];

            // 연락처
            else if( $f_val[field_id] == "supply_tel"    ) 
                $temp_arr[$f_val[field_id]] = $supply_info[$data[c_supply_code]]["supply_tel"];

            // 주소
            else if( $f_val[field_id] == "supply_address"    ) 
                $temp_arr[$f_val[field_id]] = $supply_info[$data[c_supply_code]]["supply_address"];

            // 바코드
            else if( $f_val[field_id] == "barcode"   ) 
                $temp_arr[$f_val[field_id]] = $data[c_barcode];

            // 상품명
            else if( $f_val[field_id] == "product_name"   ) 
                $temp_arr[$f_val[field_id]] = $data[c_name];

            // 옵션
            else if( $f_val[field_id] == "options"        ) 
                $temp_arr[$f_val[field_id]] = $data[c_options];

            // 상품명 + 옵션
            else if( $f_val[field_id] == "name_options"   ) 
                $temp_arr[$f_val[field_id]] = $data[c_name] . " " . $data[c_options];

            // 원가
            else if( $f_val[field_id] == "org_price"        ) 
                $temp_arr[$f_val[field_id]] = $data[c_org_price];

            // 품절
            else if( $f_val[field_id] == "enable_sale"        ) 
                $temp_arr[$f_val[field_id]] = ($data[c_enable_sale] ? "" : "품절");

            // 로케이션
            else if( $f_val[field_id] == "location"        ) 
                $temp_arr[$f_val[field_id]] = $data[c_location];

            // 경고수량
            else if( $f_val[field_id] == "stock_alarm1"        ) 
                $temp_arr[$f_val[field_id]] = $data[c_stock_alarm1];

            // 위험수량
            else if( $f_val[field_id] == "stock_alarm2"        ) 
                $temp_arr[$f_val[field_id]] = $data[c_stock_alarm2];

            // 이미지
            else if( $f_val[field_id] == "img"            ) 
                $temp_arr[$f_val[field_id]] = $this->disp_image3_1( ($data[c_org_id] ? $data[c_org_id] : $data[c_product_id]), $data[c_img_500] );

            // 공급처상품명
            else if( $f_val[field_id] == "brand"          ) 
                $temp_arr[$f_val[field_id]] = $data[c_brand];

            // 공급처옵션
            else if( $f_val[field_id] == "supply_options" ) 
                $temp_arr[$f_val[field_id]] = $data[c_supply_options];

            // 재고
            else if( $f_val[field_id] == "stock"          ) 
                $temp_arr[$f_val[field_id]] = $cur_stock;

            // 미배송
            else if( $f_val[field_id] == "not_trans"      ) 
                $temp_arr[$f_val[field_id]] = $data[sum_b_qty];

            // 요청수량
            else if( $f_val[field_id] == "request_qty"    ) 
                $temp_arr[$f_val[field_id]] = ($data[req_qty] ? $data[req_qty] : $lack_qty);

            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date"    ) 
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
                                 b.product_id='$data[c_product_id]'
                        order by a.collect_date limit 1";

                $result_cd = mysql_query($query_cd, $connect);
                $data_cd = mysql_fetch_assoc($result_cd);
                                
                $temp_arr[$f_val[field_id]] = $data_cd[collect_date];
            }

            // 카테고리
            else if( $f_val[field_id] == "category"    ) 
            {
debug("카테고리 : start");
                $temp_arr[$f_val[field_id]] = ($_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data[c_str_category]) : class_category::get_category_name($data[c_category]) );
debug("카테고리 : end");
            }

            // 등록일
            else if( $f_val[field_id] == "product_reg_date"    ) 
                $temp_arr[$f_val[field_id]] = $data[c_reg_date];
        }
        
        // 상품코드은 항상!!
        $temp_arr["product_id"] = $data[c_product_id];

        // 부족수량은 항상!!  전체(부족수량 0 포함) 옵션 처리 위해서
        $temp_arr["lack_qty"] = $lack_qty;
        
debug("재고부족요청조회2 작업중 : " . $data[c_product_id]);

        return $temp_arr;
    }    

    function add_product()
    {
        global $connect, $product_id, $page_code, $qty;
        global $product_qty_list;
        
        // 조회 필드
        $page_code = 'IL00';
        $f = $this->get_setting();
        
        $data_all = $this->get_IL00($f, &$total_rows, 1);
        $data_val = $data_all[0];
        
        $add_tr = "<tr class=real_data pid='$data_val[product_id]'>";
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )  
            {
                $disp_data = $data_val[$f_val[field_id]];
                if( $f_val[field_id] == "stock"     ||
                    $f_val[field_id] == "not_trans" ||
                    $f_val[field_id] == "lack_qty"  )
                    $add_tr .= "<td field='$f_val[field_id]' class=right>" . number_format($disp_data) . "</td>";
                else if( $f_val[field_id] == "org_price" )
                    $add_tr .= "<td field='$f_val[field_id]' class=right><span>" . number_format($disp_data) . "</span></td>";
                else if( $f_val[field_id] == "request_qty" )
                    $add_tr .= "<td field='$f_val[field_id]' class=center><input type=text class='input22num input_request_qty' style='width=40px' value='" . number_format($disp_data) . "' ></input></td>";
                else if( $f_val[field_id] == "img" )
                    $add_tr .= "<td field='$f_val[field_id]' class=left>" . ($disp_data ? $disp_data : "&nbsp;") . "</td>";
                else
                    $add_tr .= "<td field='$f_val[field_id]' class=left><span>" . ($disp_data ? htmlspecialchars($disp_data, ENT_QUOTES) : "&nbsp;") . "</span></td>";
            }
        }
        $add_tr .= "</tr>";

        $val = array();
        $val["add_tr"] = $add_tr;
        echo json_encode($val);
    }    

    //*************************************
    // save bill
    function save_bill()
    {
        global $connect, $title, $product_qty_list;
        
        $query1 = "insert into in_req_bill 
                     set crdate=Now()
                         ,crtime=Now()
                         ,title = '" . $title. "'
                         ,status = 1
                         ,owner = '" . $_SESSION[LOGIN_NAME] . "'";
                                 
        mysql_query( $query1, $connect );
        
        // 입력된 seq를 찾는다
        $query = "select last_insert_id() a from in_req_bill;";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $bill_seq = $data[a];

        // 상품정보 리스트
        $arr_request = array();
        foreach ( explode(",", $product_qty_list) as $p_val )
        {   
            list( $product_id, $not_trans, $req_qty ) = split(":", $p_val );
            $arr_request[$product_id]["not_trans"] = $not_trans;
            $arr_request[$product_id]["req_qty"] = $req_qty;
        }

        // 공급처 정보 리스트
        $supply_arr = class_supply::get_supply_arr();
        
        foreach ( $arr_request as $product_id => $p_val )
        {
            $info = class_product::get_info( $product_id );

            if( $info[enable_stock] )
                $stock = class_stock::get_current_stock( $product_id );
            else
                $stock = 0;

            $trans_ready = $arr_request[$product_id]["not_trans"];
            $stock_sub = $stock - $trans_ready;
            $qty = $arr_request[$product_id]["req_qty"];

            $query = "insert into in_req_bill_item 
                         set bill_seq      = $bill_seq
                            ,supply_id     = $info[supply_code]
                            ,supply_name   = '" . addslashes( $supply_arr[$info[supply_code]]["supply_name"] ) . "'
                            ,product_id    = '$product_id'
                            ,product_name  = '" . addslashes($info[name]) . "'
                            ,brand         = '" . addslashes($info[brand]) . "'
                            ,options       = '" . addslashes($info[options]) . "'
                            ,stock         = '$stock'
                            ,not_yet_deliv = '$trans_ready'
                            ,stock_sub     = '$stock_sub'
                            ,req_stock     = '$qty'
                            ,stockin       = '$qty'";   

            mysql_query( $query, $connect );
        }

        $val[seq] = $bill_seq;
        echo json_encode($val);
    }

    function save_bill_each()
    {
        global $connect, $title, $product_qty_list;
debug("save_bill_each start");
        // 공급처 정보 리스트
        $supply_arr = class_supply::get_supply_arr();

        // 상품정보 리스트
        $arr_req = array();
        foreach ( explode(",", $product_qty_list) as $p_val )
        {   
            list( $product_id, $not_trans, $req_qty ) = split(":", $p_val );

            // 수량이 0 이면 넘어감
            if( $req_qty == 0 )  continue;

            // 상품정보
            $query = "select * from products where product_id='$product_id'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);

            $arr_req[$data[supply_code]][] = array(
                product_id    => $product_id,
                product_name  => $data[name],
                brand         => $data[brand],
                options       => $data[options],
                not_yet_deliv => $not_trans,
                req_stock     => $req_qty
            );
        }

        // 공급처별 전표 생성
        foreach( $arr_req as $supply_key => $supply_val )
        {
            $new_title = addslashes($title . "_" . $supply_arr[$supply_key]["supply_name"]);
            
            if( _DOMAIN_ == 'topboy' )
            {
                $query_check = "select * from in_req_bill where title='" . $new_title . "'";
                $result_check = mysql_query($query_check, $connect);
                if( mysql_num_rows($result_check) )
                {
debug("공급처별 전표 생성 ** 중복 **");
                    continue;
                }
            }
            
            // 전표 만들기
            $query1 = "insert into in_req_bill 
                         set crdate=Now()
                             ,crtime=Now()
                             ,title = '" . $new_title . "'
                             ,status = 1
                             ,supply_code = $supply_key
                             ,owner = '" . $_SESSION[LOGIN_NAME] . "'";
            if( mysql_query( $query1, $connect ) )
            {
                // 입력된 seq를 찾는다
                $query = "select last_insert_id() a from in_req_bill;";
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                $bill_seq = $data[a];
    
                // 전표에 각 상품을 넣는다.
                foreach( $supply_val as $p_val )
                {
                    // 현재고
                    $stock = class_stock::get_current_stock($p_val[product_id]);
                    $stock_sub = $stock - $p_val[not_yet_deliv];
    
                    $query = "insert into in_req_bill_item 
                                 set bill_seq       = $bill_seq
                                     ,supply_id     = $supply_key
                                     ,supply_name   = '" . addslashes($supply_arr[$supply_key]["supply_name"]) . "'
                                     ,product_id    = '$p_val[product_id]'
                                     ,product_name  = '" . addslashes($p_val[product_name]) . "'
                                     ,brand         = '" . addslashes($p_val[brand]) . "'
                                     ,options       = '" . addslashes($p_val[options]) . "'
                                     ,stock         = '$stock'
                                     ,not_yet_deliv = '$p_val[not_yet_deliv]'
                                     ,stock_sub     = '$stock_sub'
                                     ,req_stock     = '$p_val[req_stock]'
                                     ,stockin       = '$p_val[req_stock]'";   
                    mysql_query( $query, $connect );
                }
            }
        }
debug("save_bill_each end");
    }
}
?>
