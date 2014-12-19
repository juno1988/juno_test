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
require_once "class_table.php";
require_once "class_lock.php";
require_once "Classes/PHPExcel.php";

class class_DJ00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function DJ00()
    {
        global $template, $connect, $page_code, $search;
        global $date_type,$start_date, $end_date, $start_hour, $end_hour;
        global $supply_group, $supply_code, $str_supply_code, $group_id, $shop_id, $query_type, $query_str;
        global $trans_all, $trans_able, $trans_part, $trans_not, $qty_over_1;

        // 조회 필드
        $page_code = 'DJ00';
        $f = class_table::get_setting();
        
        if( $search )
        {
            // 전체 쿼리
            $data_all = $this->get_DJ00($f, &$total_rows, &$sum_arr);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        else
        {
            // 초기 검색 조건
            $page_code = 'DJ00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
            
            // 체크박스
            $trans_able = $f_trans_able;
            $trans_part = $f_trans_part;
            $trans_not  = $f_trans_not;
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 데이터 조회
    //###############################
    function get_DJ00($f, &$total_rows, &$sum_arr)
    {
        global $template, $connect, $page_code;
        global $date_type,$start_date, $end_date, $start_hour, $end_hour;
        global $supply_group, $supply_code, $str_supply_code, $group_id, $shop_id, $query_type, $query_str;
        global $trans_all, $trans_able, $trans_part, $trans_not, $qty_over_1;
        global $stock_info, $status_qty1, $status_qty7, $assign_qty;
        
        $this->show_wait($download);

        // 재고할당정보
        $stock_info = array();
        $assign_qty = array();
        $query_stock = "select * from print_enable";
        $result_stock = mysql_query($query_stock, $connect);
        while($data_stock = mysql_fetch_assoc($result_stock))
        {
            $stock_info[$data_stock['order_seq']][$data_stock['product_seq']] = $data_stock['is_stock'];
            if( $data_stock[is_stock] )
                $assign_qty[$data_stock[product_id]] += $data_stock[qty];
        }

        // 주문 불러오기
        $query = "select distinct pack
                    from print_enable ";
debug("주문재고할당 불러오기 : " . $query);
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        $pack_arr = array();
        while( $data = mysql_fetch_assoc($result) )
            $pack_arr[] = $data[pack_seq];
        
        $pack_list = implode(",", $pack_arr);

        // 주문 불러오기
        $query = "select if(a.pack>0, a.pack, a.seq) pack_seq
                        ,a.seq a_seq
                        ,b.seq b_seq
                        ,a.pack a_pack
                        ,a.shop_id a_shop_id
                        ,a.order_id a_order_id
                        ,a.collect_date a_collect_date
                        ,a.recv_name a_recv_name
                        ,c.product_id c_product_id
                        ,c.name c_name
                        ,c.options c_options
                        ,b.qty b_qty
                        ,d.stock cur_stock
                        ,b.qty b_qty
                        ,e.status
                        ,e.is_stock
                    from orders a
                        ,order_products b
                        ,products c
                        ,current_stock d
                        ,print_enable e
                   where a.seq=b.order_seq 
                     and b.product_id=c.product_id
                     and b.product_id=d.product_id
                     and a.seq = e.order_seq
                     and d.bad = 0 
                     and a.status = 1
                     and b.order_cs not in (1,2,3,4)
                     and c.is_delete=0
                     and c.is_represent=0
                     and ( a.seq in ($pack_list) or a.pack in ($pack_list) ) 
                   order by pack_seq";
        $result = mysql_query($query, $connect);
        
        $data_all = array();
        $start_time = time();
        $i = 0;
        
        $old_pack_seq = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[pack_seq] == 0 || $old_pack_seq <> $data[pack_seq] )
            {
                $i++;
                $old_pack_seq = $data[pack_seq];
            }
            
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>show_txt( '조회중 : $i / $total_rows' )</script>";
                flush();
            }
            usleep(1000);
            
            // 출력 정보 가져오기
            $temp_arr = $this->get_DJ00_data_arr($data, $f, $supply_info);
    
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
    // 메인 쿼리 - 합포번호 구하기. 
    //###############################
    // 기간을 설정하더라도 이전 날짜의 주문과 합포인 경우 때문에 합포 번호를 구하고 해당 합포 번호의 주문들을 다시 구해야한다.
    function get_DJ00_query()
    {
        global $template, $connect, $page_code;
        global $date_type,$start_date, $end_date, $start_hour, $end_hour;
        global $supply_group, $supply_code, $str_supply_code, $group_id, $shop_id, $query_type, $query_str;
        global $trans_all, $trans_able, $trans_part, $trans_not, $qty_over_1;
        global $stock_info, $status_qty1, $status_qty7, $assign_qty;
        
        // 주문 불러오기
        $query = "select if(a.pack>0, a.pack, a.seq) pack_seq
                    from orders a use index (orders_idx_date_status)
                        ,order_products b
                        ,products c
                   where a.seq=b.order_seq 
                     and b.product_id=c.product_id 
                     and a.status = 1
                     and b.order_cs not in (1,2,3,4)
                     and c.is_delete=0
                     and c.is_represent=0 ";
        $query .= " group by pack_seq";
        return $query;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_DJ00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;

        global $work_no, $order_status;

        // 조회 필드
        $page_code = 'DJ00_file';
        $f = class_table::get_setting();

        // 전체 쿼리
        $data_all = $this->get_DJ00($f, &$total_rows, &$sum_arr, 1);

        $data_all[] = $sum_arr;
        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_DJ00( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_DJ00( $data_all, $fn, $f )
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
    function download_DJ00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

    //###############################
    // 조회 필드 설정팝업
    //###############################
    function DJ01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_DJ00_data_arr($data, $f, $supply_info)
    {
        global $connect;
        global $work_no, $order_status;
        global $stock_info, $status_qty1, $status_qty7, $assign_qty;

        // 상품 상세
        $query_prd = "select * from products where product_id = '$data[c_product_id]'";
        $result_prd = mysql_query($query_prd, $connect);
        $data_prd = mysql_fetch_assoc($result_prd);

        // 상품 이미지
        $product_img = false;
        // 상품 이미지(소)
        $product_img_small = false;
        // 카테고리
        $use_category = false;

        // 접수, 송장 상태 개수
        if( !isset($status_qty1[$data[c_product_id]]) )
        {
            $status_qty1[$data[c_product_id]] = class_stock::get_ready_stock2($data[c_product_id]);  
            $status_qty7[$data[c_product_id]] = class_stock::get_ready_stock($data[c_product_id]);  
        }
        
        foreach($f as $f_val)
        {
            // 상품 이미지
            if( $f_val[field_id] == "product_img" )
                $product_img = true;
            // 상품 이미지(소)
            else if( $f_val[field_id] == "product_img_small" )
                $product_img_small = true;
            else if( $f_val[field_id] == "category" )
                $use_category = true;
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

        //카테고리
        if($use_category)
        {
        	$str_categoty = $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data_prd[str_category]) : class_category::get_category_name( $data_prd[category] );
        }

        // 상품정보
        $_not_assign = $data[cur_stock] - $status_qty7[$data_prd[product_id]] - $assign_qty[$data_prd[product_id]];
        if( $_not_assign == 0 )  $_not_assign = "";
        $product_info = array(
            "product_id"          => $data_prd[product_id],
            "org_id"              => $data_prd[org_id],
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
            "stock_bad"           => $stock_bad,
            "stock_alarm1"		  => $data_prd[stock_alarm1],
            "stock_alarm2"        => $data_prd[stock_alarm2],
            "status_qty1"         => $status_qty1[$data_prd[product_id]],
            "status_qty7"         => $status_qty7[$data_prd[product_id]],
            "reserve_qty"		  => $data_prd[reserve_qty],
            "return_qty"		  => $data_prd[return_qty],
            "origin"              => $data_prd[origin],
            "category"			  => $str_categoty,
            "assign"              => $assign_qty[$data_prd[product_id]],
            "not_assign"          => $_not_assign
        );
        
        $temp_arr = array();
        foreach( $f as $f_val )
        {
            // 상품코드, 합포번호 - 무조건 추가
            if( $f_val[field_id] == "product_id" )
                $temp_arr[$f_val[field_id]] = $product_info[$f_val[field_id]];
            if( $f_val[field_id] == "pack")
                $temp_arr[$f_val[field_id]] = $data[a_pack];

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
                if( $f_val[field_id] == "product_name" )
                    $temp_arr[$f_val[field_id]] = "<span class='pname_style'>" . htmlspecialchars($product_info[$f_val[field_id]], ENT_QUOTES) . "</span>";
                else if( $f_val[field_id] == "options" )
                    $temp_arr[$f_val[field_id]] = "<span class='option_style'>" . htmlspecialchars($product_info[$f_val[field_id]], ENT_QUOTES) . "</span>";
                else
                    $temp_arr[$f_val[field_id]] = $product_info[$f_val[field_id]];
                continue;
            }

            if( $f_val[field_id] == "seq" )
                $temp_arr[$f_val[field_id]] = $data[a_seq];
            else if( $f_val[field_id] == "pack" )
                $temp_arr[$f_val[field_id]] = $data[a_pack];
            else if( $f_val[field_id] == "shop_id" )
                $temp_arr[$f_val[field_id]] = $data[a_shop_id];
            else if( $f_val[field_id] == "order_id" )
                $temp_arr[$f_val[field_id]] = $data[a_order_id];
            else if( $f_val[field_id] == "collect_date" )
                $temp_arr[$f_val[field_id]] = "<span class='day_style'>" . htmlspecialchars($data[a_collect_date], ENT_QUOTES) . "</span>";
            else if( $f_val[field_id] == "recv_name" )
                $temp_arr[$f_val[field_id]] = "<span class='name_style'>" . $this->popupcs($data[a_seq], htmlspecialchars($data[a_recv_name], ENT_QUOTES)) . "</span>";
            else if( $f_val[field_id] == "order_qty" )
                $temp_arr[$f_val[field_id]] = $data[b_qty];
            else if( $f_val[field_id] == "shop_name" )
                $temp_arr[$f_val[field_id]] = $_SESSION["SHOP_NAME_" . $data[a_shop_id]];
            else if( $f_val[field_id] == "pack_seq" )
                $temp_arr[$f_val[field_id]] = $data[pack_seq];
            else if( $f_val[field_id] == "product_seq" )
                $temp_arr[$f_val[field_id]] = $data[b_seq];
            else if( $f_val[field_id] == "qty" )
                $temp_arr[$f_val[field_id]] = $data[b_qty];
            else if( $f_val[field_id] == "deliv" )
            {
                if( isset($stock_info[$data[a_seq]][$data[b_seq]]) )
                    $temp_arr[$f_val[field_id]] = $stock_info[$data[a_seq]][$data[b_seq]] ? "<span class='deliv_o'>O</span>" : "<span class='deliv_x'>X</span>";
                else
                    $temp_arr[$f_val[field_id]] = "<span class='deliv_n'>?</span>";
            }
            else if( $f_val[field_id] == "en_stock" )
            {
                $_s = $product_info[stock] - $product_info[status_qty7];
                $temp_arr[$f_val[field_id]] = max($_s, 0);
            }
            else if( $f_val[field_id] == "stock_info" )
            {
                $_str  = "<div style='width:250px'>";

                // 미할당
                $_str .= "<div class='stock_asgn num_red'>$product_info[not_assign]</div><div class='stock_title'>+</div>";

                // 할당
                $_str .= "<div class='stock_asgn num_blue'>$product_info[assign]</div><div class='stock_title'>=</div>";

                // 가용재고
                $_en_stock = $product_info[stock] - $product_info[status_qty7];
                if( $_en_stock <= 0 )  $_en_stock = "";
                $_str .= "<div class='stock_asgn num_black'>$_en_stock</div><div class='stock_title'>(</div>";

                // 현재고
                $_str .= "<div class='stock_cur'>$product_info[stock]</div><div class='stock_title'>-</div>";

                // 송장
                $_str .= "<div class='stock_cur'>$product_info[status_qty7]</div><div class='stock_title'>)</div>";

                $_str .= "</div>";
                $temp_arr[$f_val[field_id]] = $_str;
            }
            else if( $f_val[field_id] == "pack_check" )
            {
                $temp_arr[$f_val[field_id]] = "<input type='checkbox' class='pack_checkbox' p_seq='$data[b_seq]'><input type=text class='pack_check_qty' value='$data[b_qty]'>";
            }
            else if( $f_val[field_id] == "divide_pack" )
            {
                $temp_arr[$f_val[field_id]] = "<div class='divide_pack_btn'>합포<br>제외</div>";
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

    //*****************************
    // 일괄합포제외
    //*****************************
    function drop_pack_all()
    {
        global $connect, $template, $checked_data_all, $checked_data, $stock_assign, $drop_pack_all;
        global $org_pack_lock, $drop_pack_lock, $org_pre_trans, $drop_pre_trans, $content;

        $org_pack_lock  = 0;
        $drop_pack_lock = 0;
        $org_pre_trans  = 0;
        $drop_pre_trans = 0;
        $content        = "";
        
        $stock_assign = 1;
        $drop_pack_all = 1;

        $val = array();
        $val['error'] = 0;
        
        $obj_lock = new class_lock(104);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );

            return;
        }

        require_once "class_E900.php";
        $obj_e900 = new class_E900();
        
        foreach( explode("/", $checked_data_all) as $_val )
        {
            if( !$_val )  continue;
            
            $checked_data = $_val;
            $obj_e900->drop_pack();
        }
        
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }
}
?>
