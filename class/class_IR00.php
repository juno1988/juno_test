<?
require_once "class_top.php";
require_once "class_table.php";
require_once "class_supply.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_ui.php";
require_once "class_sms.php";
require_once "class_db.php";
require_once "zip.lib.php";
require_once "Classes/PHPExcel.php";

class class_IR00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function IR00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $date_type, $start_date, $end_date, $title, $status, $is_delete, $date_type, $string, $string_type;

        // 조회 필드
        $page_code = 'IR00';
        $f = class_table::get_setting();

        // 검색
        if( $search )
        {
            // 전체 쿼리
            $data_all = $this->get_IR00($f, &$total_rows, &$sum_arr);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        
        // 처음 화면
        else
        {
            // 초기 검색 조건
            $page_code = 'IR00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
        
            // 날짜
            $date_type = $f_date_type;

            // 요청일
            if( $f_search_date == 0 )
            {
                $start_date = date("Y-m-d");
                $end_date = date("Y-m-d");
            }
            else if( $f_search_date == 1 )
            {
                $start_date = date("Y-m-d", strtotime("-1 day"));
                $end_date = date("Y-m-d", strtotime("-1 day"));
            }
            else if( $f_search_date == 2 )
            {
                $start_date = date("Y-m-d", strtotime("-1 week"));
                $end_date = date("Y-m-d");
            }
            else if( $f_search_date == 3 )
            {
                $start_date = date("Y-m-d", strtotime("-1 week"));
                $end_date = date("Y-m-d", strtotime("-1 day"));
            }

            // 요청상태
            $status = $f_status;
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메인 쿼리
    //###############################
    function get_IR00($f, &$total_rows, &$sum_arr, $download=0)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $start_date, $end_date, $title, $status, $is_delete, $date_type, $string, $string_type;

        $this->show_wait($download);

        // 공급처 정보
        $supply_info = class_supply::get_supply_arr();

        // 검색 
        $query = "select a.product_id a_product_id,
                         b.name b_name,
                         b.options b_options, 
                         b.barcode b_barcode, 
                         b.location b_location,
                         b.brand b_brand, 
                         b.origin b_origin,
                         b.supply_options b_supply_options,
                         b.memo b_memo,
                         b.org_price b_org_price,
                         b.supply_price b_supply_price,
                         b.shop_price b_shop_price,
                         b.reg_date b_reg_date,
                         b.enable_sale b_enable_sale,
                         b.sale_stop_date b_sale_stop_date,
                         a.exp_qty a_exp_qty,
                         a.in_qty a_in_qty,
                         a.exp_memo a_exp_memo,
                         b.supply_code b_supply_code,
                         b.img_500 b_img_500,
                         b.org_id b_org_id,
                         c.title c_title,
                         c.req_date c_req_date,
                         c.exp_date c_exp_date,
                         c.status c_status
                    from expect_stockin_item a
                        ,products b
                        ,expect_stockin_sheet c
                   where c.seq = a.sheet_seq
                     and a.product_id = b.product_id 
                     and c.is_delete = 0 ";
        
        if ( $date_type == 1 )
            $query .= " and c.req_date>='$start_date' and c.req_date<='$end_date'  ";
        else if ( $date_type == 2 )
            $query .= " and c.exp_date>='$start_date' and c.exp_date<='$end_date'  ";
        
        if ( $status == 1 )
            $query .= " and c.status = 0 ";
        else if ( $status == 2 )
            $query .= " and c.status = 1 ";

        if( $string )
        {
            if( $string_type == 'name'           )
                $query .= " and b.name like '%$string%' ";
            else if( $string_type == 'options'        )
                $query .= " and b.options like '%$string%' ";
            else if( $string_type == 'product_id'     )
                $query .= " and b.product_id = '$string' ";
            else if( $string_type == 'barcode'        )
                $query .= " and b.barcode = '$string' ";
            else if( $string_type == 'origin'         )
                $query .= " and b.origin like '%$string%' ";
            else if( $string_type == 'brand'          )
                $query .= " and b.brand like '%$string%' ";
            else if( $string_type == 'supply_options' )
                $query .= " and b.supply_options like '%$string%' ";
        }
debug("입고예정상품조회 : " . $query);
        $result = mysql_query( $query, $connect );
        $total_rows = mysql_num_rows($result);

        $all_product_list = "";
        
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
                if( $download )
                    echo "<script type='text/javascript'>parent.show_txt( '$i / $total_rows' )</script>";
                else
                    echo "<script type='text/javascript'>show_txt( '$i / $total_rows' )</script>";
                flush();
            }
            usleep(1000);

            // 출력 정보 가져오기
            $temp_arr = $this->get_IR00_data_arr($data, $f, $supply_info);

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
            
            // 수량, 날짜 경우 역순정렬
            if( $s_val[field] == "exp_qty"    ||
                $s_val[field] == "in_qty"   ||
                $s_val[field] == "in_price" )
                $ss_arr[] = SORT_DESC;
        }

        // 전체 데이터 멀티 컬럼 정렬
        $this->array_multi_column_sort($ss_arr, &$data_all);

        return $data_all;
    }

    //###############################
    // 화면설정팝업
    //###############################
    function IR01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_IR00_data_arr($data, $f, $supply_info)
    {
        global $connect, $start_date, $end_date;

        // 현재고 구하기
        $current_stock = false;
        // 현재고 구하기-불량
        $current_stock_bad = false;
        // 상품 이미지(대)
        $product_img = false;
        // 상품 이미지(소)
        $product_img_small = false;
        // 가장 오래된 발주일
        $use_collect_date = false;
        // 접수 구하기
        $use_status1_qty = false;
        // 송장 구하기
        $use_status7_qty = false;

        foreach($f as $f_val)
        {
            // 현재고 구하기
            if( $f_val[field_id] == "stock" )
                $current_stock = true;
            // 현재고 구하기-불량
            else if( $f_val[field_id] == "stock_bad" )
                $current_stock_bad = true;
            // 상품 이미지(대)
            else if( $f_val[field_id] == "product_img" )
                $product_img = true;
            // 상품 이미지(소)
            else if( $f_val[field_id] == "product_img_small" )
                $product_img_small = true;
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $use_collect_date = true;
            // 접수
            else if( $f_val[field_id] == "status1_qty" || $f_val[field_id] == "status17_qty" )
                $use_status1_qty = true;
            // 송장
            else if( $f_val[field_id] == "status7_qty" || $f_val[field_id] == "status17_qty" )
                $use_status7_qty = true;
        }
        
        // 상품 이미지(대)
        if( $product_img )
            $product_img_str = $this->disp_image3_2( ($data[b_org_id] > '' ? $data[b_org_id] : $data[a_product_id]), $data[b_img_500] );

        // 상품 이미지(소)
        if( $product_img_small )
            $product_img_small_str = $this->disp_image3_2( ($data[b_org_id] > '' ? $data[b_org_id] : $data[a_product_id]), $data[b_img_500], 50 );

        // 품절 정보
        if( $data[b_enable_sale] )
        {
            $enable_sale = "|";
            $sale_stop_date = "";
        }
        else
        {
            $enable_sale = "<img src='images/soldout.gif'>|품절";
            $sale_stop_date = $data[b_sale_stop_date];
        }
        
        // 가장 오래된 발주일
        if( $use_collect_date )
        {
            $query_cd = "select a.collect_date collect_date
                           from orders a, order_products b 
                          where a.seq=b.order_seq and";
                       
            // 미배송 상태 - 원래는 미배송 기준을 접수/송장 어느걸로 할건지 읽어와야하나 그냥 '접수 또는 송장'으로 한다.
            $order_status=3;
            if( $order_status == 1 )
                $query_cd .=  " a.status = 1 and ";
            else if( $order_status == 2 )
                $query_cd .=  " a.status = 7 and ";
            else if( $order_status == 3 )
                $query_cd .=  " a.status in (1,7) and ";
               
            $query_cd .=  "  b.order_cs not in (1,2,3,4) and
                             b.product_id='$data[a_product_id]'
                    order by a.collect_date limit 1";

            $result_cd = mysql_query($query_cd, $connect);
            $data_cd = mysql_fetch_assoc($result_cd);
                            
            $old_collect_date = $data_cd[collect_date];
        }
        
        // 접수
        if( $use_status1_qty )
            $status1_qty = class_stock::get_ready_stock2($data[a_product_id]);
        else
            $status1_qty = 0;

        // 송장
        if( $use_status7_qty )
            $status7_qty = class_stock::get_ready_stock($data[a_product_id]);
        else
            $status7_qty = 0;

        // 상품정보
        $product_info = array(
            "product_id"          => $data[a_product_id],
            "product_name"        => $data[b_name],
            "options"             => $data[b_options],
            "name_options"        => $data[b_name] . " " . $data[b_options],
            "barcode"             => $data[b_barcode],
            "origin"              => $data[b_origin],
            "location"            => $data[b_location],
            "supply_product_name" => $data[b_brand],
            "supply_options"      => $data[b_supply_options],
            "supply_product_name_options" => $data[b_brand] . " " . $data[b_supply_options],
            "product_memo"        => $data[b_memo],
            "org_price"           => $data[b_org_price],
            "supply_price"        => $data[b_supply_price],
            "shop_price"          => $data[b_shop_price],
            "stock"               => ( $current_stock ? class_stock::get_current_stock($data[a_product_id]) : 0 ),
            "stock_bad"           => ( $current_stock_bad ? class_stock::get_current_stock($data[a_product_id],1) : 0 ),
            "reg_date"            => $data[b_reg_date],
            "reg_date_option"     => $data[b_reg_date],
            "enable_sale"         => $enable_sale,
            "sale_stop_date"      => $sale_stop_date,
            "product_img"         => $product_img_str,
            "product_img_small"   => $product_img_small_str,
            "status1_qty"         => $status1_qty,
            "status7_qty"         => $status7_qty,
            "status17_qty"        => $status17_qty
        );
        // 원가
        $product_org_price = $data[b_org_price];

        $temp_arr = array();
        foreach( $f as $f_val )
        {
            if( !$f_val[chk] && !$f_val[sort] )  continue;

            //+++++++++++++++++++
            // 공급처 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "supply" ) 
            {
                $temp_arr[$f_val[field_id]] = class_table::get_supply_arr_data($f_val[field_id], $supply_info, $data[b_supply_code]);
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

            //+++++++++++++++++++
            // 입고예정전표상세 (IQ10)
            //+++++++++++++++++++

            // 예정수량
            if( $f_val[field_id] == "exp_qty" ) 
                $temp_arr[$f_val[field_id]] = $data[a_exp_qty];
            // 예정금액
            else if( $f_val[field_id] == "request_amount" ) 
                $temp_arr[$f_val[field_id]] = $data[a_exp_qty] * $product_org_price;
            // 입고수량
            else if( $f_val[field_id] == "in_qty" ) 
                $temp_arr[$f_val[field_id]] = $data[a_in_qty];
            // 입고금액
            else if( $f_val[field_id] == "stockin_amount" ) 
                $temp_arr[$f_val[field_id]] = $data[a_in_qty] * $product_org_price;
            // 예정메모
            else if( $f_val[field_id] == "exp_memo" ) 
                $temp_arr[$f_val[field_id]] = $data[a_exp_memo];
            // 재고메모
            else if( $f_val[field_id] == "stock_memo" ) 
                $temp_arr[$f_val[field_id]] = $data[a_memo];
            // 전표이름
            else if( $f_val[field_id] == "sheet_name" ) 
                $temp_arr[$f_val[field_id]] = $data[c_title];
            // 전표상태
            else if( $f_val[field_id] == "sheet_status" ) 
                $temp_arr[$f_val[field_id]] = ($data[sheet_status] == 1 ? "예정" : "완료");
            // 요청일
            else if( $f_val[field_id] == "req_date" ) 
                $temp_arr[$f_val[field_id]] = $data[c_req_date];
            // 예정일
            else if( $f_val[field_id] == "exp_date" ) 
                $temp_arr[$f_val[field_id]] = $data[c_exp_date];
            // 상태
            else if( $f_val[field_id] == "status" ) 
                $temp_arr[$f_val[field_id]] = ( $data[c_status] ? "완료" : "예정" );
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $temp_arr[$f_val[field_id]] = $old_collect_date;
        }
        
        // 상품코드
        $temp_arr["product_id"] = $data[a_product_id];

        return $temp_arr;
    }    


    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_IR00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $date_type, $start_date, $end_date, $status, $string_type, $string, $download;

        // 조회 필드
        $page_code = 'IR00_file';
        $f = class_table::get_setting();

        // 전체 쿼리
        if( $search )
            $data_all = $this->get_IR00($f, &$total_rows, &$sum_arr, 1);
        
        // $data_all[] = $sum_arr;
        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_IR00( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_IR00( $data_all, $fn, $f, $is_email=0, $is_download_all=0, $is_html=0)
    {
        global $connect, $is_date, $sheet_title, $req_date, $supply_memo;
        global $sheet;
        
        // 전표 seq
        $seq = $sheet;
        
        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        $col = 0;
        $row = 1;

        // 전표 헤더 정보
        $query = "select * from in_req_bill where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 전표 공급처 정보
        if( $data[supply_code] )
        {
            $supply_info = class_supply::get_info($data[supply_code]);
    
            $sheet_title   = $data[title];
            $sheet_crdate  = $data[crdate];
            $sheet_tel     = $supply_info[tel] . " / " . $supply_info[mobile];
            $sheet_email   = $supply_info[email];
            $sheet_address = $supply_info[address1] . " " . $supply_info[address2];
            $sheet_memo    = $data[supply_memo];
        }
        else
        {
            $sheet_title   = "";
            $sheet_crdate  = "";
            $sheet_tel     = "";
            $sheet_email   = "";
            $sheet_address = "";
            $sheet_memo    = "";
        }
        
        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        global $page_code;
        $page_code = 'IR00_search';
        
        // 전체 다운로드가 아닐 경우만
        if( !$is_download_all )
        {
            foreach(class_table::get_setting() as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
        }

        $end_col = "E";
        if( $is_email ? $f_email_header_title : $f_download_header_title )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("전표명 : ".$sheet_title, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_crdate : $f_download_header_crdate )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("요청일 : ".$sheet_crdate, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_tel : $f_download_header_tel )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("공급처 연락처 : ".$sheet_tel, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_email : $f_download_header_email )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("공급처 이메일 : ".$sheet_email, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_address : $f_download_header_address )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("공급처 주소 : ".$sheet_address, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_memo : $f_download_header_memo )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("공급처 메모 : ".$sheet_memo, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }

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

        $objPageSetup = new PHPExcel_Worksheet_PageSetup();
        $objPageSetup->setFitToPage(true);
        $objPageSetup->setFitToWidth(1);
        $objPageSetup->setFitToHeight(0);

        $sheet->setPageSetup($objPageSetup);

        if( $is_html )
        {
            $writer = new PHPExcel_Writer_HTML($excel);
            $writer->save($filename);
        }
        else
        {
            $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $writer->save($filename);
        }
        return $filename;
    }

    //###############################
    // 다운로드
    //###############################
    function download_IR00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

}
?>
