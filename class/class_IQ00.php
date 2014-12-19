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

/*

CREATE TABLE `expect_stockin_sheet` (
  `seq` int(11) NOT NULL auto_increment,
  `title` varchar(100) NOT NULL,
  `crdate` datetime NOT NULL,
  `cruser` varchar(30) NOT NULL,
  `status` int(1) NOT NULL,
  `is_delete` int(1) NOT NULL,
  `req_date` char(10) NOT NULL,
  `exp_date` char(10) NOT NULL,
  `complete_date` datetime NOT NULL,
  `complete_user` varchar(30) NOT NULL,
  `delete_date` datetime NOT NULL,
  `delete_user` varchar(30) NOT NULL,
  `memo` text,
  `log` text,
  PRIMARY KEY  (`seq`),
  KEY `expect_stockin_sheet_idx1` (`crdate`),
  KEY `expect_stockin_sheet_idx2` (`req_date`),
  KEY `expect_stockin_sheet_idx3` (`exp_date`),
  KEY `expect_stockin_sheet_idx4` (`complete_date`)
) ENGINE=MyISAM;

CREATE TABLE `expect_stockin_item` (
  `seq` int(11) NOT NULL auto_increment,
  `sheet_seq` int(1) NOT NULL,
  `add_date` datetime NOT NULL,
  `product_id` varchar(30) NOT NULL,
  `exp_qty` int(1) NOT NULL,
  `in_qty` int(1) NOT NULL,
  `exp_memo` varchar(100) NOT NULL,
  PRIMARY KEY  (`seq`),
  KEY `expect_stockin_item_idx1` (`sheet_seq`),
  KEY `expect_stockin_item_idx2` (`product_id`)
) ENGINE=MyISAM;

*/

class class_IQ00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function IQ00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $date_type, $start_date, $end_date, $title, $req_status, $is_delete;

        // 조회 필드
        $page_code = 'IQ00';
        $f = class_table::get_setting();

        // 검색
        if( $search )
        {
            // 전체 쿼리
            $data_all = $this->get_IQ00($f, &$total_rows, &$sum_arr);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        
        // 처음 화면
        else
        {
            // 초기 검색 조건
            $page_code = 'IQ00_search';
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
            $req_status = $f_status;
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메인 쿼리
    //###############################
    function get_IQ00($f, &$total_rows, &$sum_arr)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $date_type, $start_date, $end_date, $title, $req_status, $is_delete;
        
        $this->show_wait($download);

        // 검색 
        $query = "select * from expect_stockin_sheet where ";
        
        if( $date_type == 0 )
            $query .= " crdate>='$start_date 00:00:00' and crdate<='$end_date 23:59:59' ";
        else if( $date_type == 1 )
            $query .= " req_date>='$start_date' and req_date<='$end_date' ";
        else if( $date_type == 2 )
            $query .= " exp_date>='$start_date' and exp_date<='$end_date' ";
        else if( $date_type == 3 )
            $query .= " complete_date>='$start_date 00:00:00' and complete_date<='$end_date 23:59:59' ";
        
        if ( $req_status )
            $query .= " and status=$req_status-1 ";
        
        if ( $title )
            $query .= " and title like '%$title%'";

        if ( $is_delete )
            $query .= " and is_delete=1 ";
        else
            $query .= " and is_delete=0 ";

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
            $temp_arr = $this->get_IQ00_data_arr($data, $f, $supply_info);

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
    // 다운로드 파일 만들기
    //###############################
    function save_file_IQ00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order, $sheet;
        global $date_type, $start_date, $end_date, $title, $req_status, $is_delete, $download_type;

        // 조회 필드
        $page_code = 'IQ10_file';
        $f = class_table::get_setting();

        // 검색 
        $sheet_list = "";
        $query = "select seq from expect_stockin_sheet where ";
        
        if( $date_type == 0 )
            $query .= " crdate>='$start_date 00:00:00' and crdate<='$end_date 23:59:59' ";
        else if( $date_type == 1 )
            $query .= " req_date>='$start_date' and req_date<='$end_date' ";
        else if( $date_type == 2 )
            $query .= " exp_date>='$start_date' and exp_date<='$end_date' ";
        else if( $date_type == 3 )
            $query .= " complete_date>='$start_date 00:00:00' and complete_date<='$end_date 23:59:59' ";
        
        if ( $req_status )
            $query .= " and status=$req_status-1 ";
        
        if ( $title )
            $query .= " and title like '%$title%'";

        if ( $is_delete )
            $query .= " and is_delete=1 ";
        else
            $query .= " and is_delete=0 ";

        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_assoc($result) )
            $sheet_list .= ($sheet_list ? "," : "") . $data[seq];

        // 전체단일파일
        if( $download_type )
        {
            // 전체 쿼리
            $data_all = array();
            $sum_arr_all = array();
            foreach( explode(",", $sheet_list) as $sheet_val )
            {
                $sheet = $sheet_val;
                $data_all = array_merge($data_all, $this->get_IQ10($f, &$total_rows, &$sum_arr, 1));
                
                $sum_arr_all[] = $sum_arr;
            }
            // sum의 총합계
            $sum_arr_sum = array();
            foreach( $sum_arr_all as $sum_val )
            {
                foreach( $sum_val as $s_key => $s_val )
                {
                    if( $s_val === "합계" )
                        $sum_arr_sum[$s_key] = "합계";
                    else
                        $sum_arr_sum[$s_key] += $s_val;
                }
            }

            $data_all[] = $sum_arr_sum;
            $fn = "expect_stock_" . date("Ymd_His") . ".xls";
            $this->make_file_IQ10( $data_all, $fn, $f, 0, 1 );
        }
        // 개별압축파일
        else
        {
            $file_arr = array();
            foreach( explode(",", $sheet_list) as $sheet_val )
            {
                $sheet = $sheet_val;
                $data_all = $this->get_IQ10($f, &$total_rows, &$sum_arr, 1);

                // 전표명
                $query_sheet_name = "select * from expect_stockin_sheet where seq=$sheet";
                $result_sheet_name = mysql_query($query_sheet_name, $connect);
                $data_sheet_name = mysql_fetch_assoc($result_sheet_name);
                
                // 파일명에 다음의 문자 불가( \ / : * ? " < > | )
                $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$data_sheet_name[title]) . ".xls";
                
                $data_all[] = $sum_arr;
                $this->make_file_IQ10( $data_all, $fn, $f, 0);
                $file_arr[$fn] = _upload_dir.$fn;
            }
    
            $fn = "expect_stock_" . date("Ymd_His") . ".zip";
            
            $ziper = new zipfile(); 
            $ziper->addFiles($file_arr);  //array of files 
            $ziper->output(_upload_dir . $fn); 
        }
        
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 화면설정팝업
    //###############################
    function IQ01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 새 전표 생성 팝업
    //###############################
    function IQ06()
    {
        global $template, $connect, $sheet;

        if( !$start_date )  $start_date = date("Y-m-d");
        if( !$start_date2 )  $start_date2 = date("Y-m-d");

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_IQ00_data_arr($data, $f, $supply_info)
    {
        global $connect;
        
        // 전표 상품 상세
        $query_detail = "select sum(a.exp_qty) sum_exp_qty,
                                sum(a.in_qty) sum_in_qty,
                                sum(a.in_qty * b.org_price) sum_in_price
                           from expect_stockin_item a,
                                products b
                          where a.product_id = b.product_id and
                                a.sheet_seq = $data[seq]";
        $result_detail = mysql_query($query_detail, $connect);
        $data_detail = mysql_fetch_assoc($result_detail);

        $temp_arr = array();
        foreach( $f as $f_val )
        {
            if( !$f_val[chk] && !$f_val[sort] )  continue;

            // 완료일
            if( $f_val[field_id] == "complete_date" )
                $temp_arr[$f_val[field_id]] = ($data[status] ? $data[complete_date ] : "");
            // 삭제일
            else if( $f_val[field_id] == "delete_date" )
                $temp_arr[$f_val[field_id]] = ($data[is_delete] ? $data[delete_date ] : "");
            // 총예정수량
            else if( $f_val[field_id] == "exp_total_qty" )
                $temp_arr[$f_val[field_id]] = $data_detail[sum_exp_qty];
            // 총입고수량
            else if( $f_val[field_id] == "in_total_qty" )
                $temp_arr[$f_val[field_id]] = $data_detail[sum_in_qty];
            // 상태
            else if( $f_val[field_id] == "status" )
            {
                if( $data[is_delete] )
                    $temp_arr[$f_val[field_id]] = "삭제";
                else
                    $temp_arr[$f_val[field_id]] = ($data[status] ? "완료" : "예정");
            }
            // 나머지
            else
                $temp_arr[$f_val[field_id]] = $data[$f_val[field_id]];
        }

        // 전표 번호
        $temp_arr["sheet"] = $data[seq];
        
        return $temp_arr;
    }    

    //###############################
    // 메인 화면 IQ10
    //###############################
    function IQ10()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $sheet;

        // 조회 필드
        $page_code = 'IQ10';
        $f = class_table::get_setting();

        // 전체 쿼리
        if( $search )
            $data_all = $this->get_IQ10($f, &$total_rows, &$sum_arr);

        // 정렬방향
        if( $sort )
            $sort_order = ($sort_order ? 0 : 1);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메인 쿼리
    //###############################
    function get_IQ10($f, &$total_rows, &$sum_arr, $download=0)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $sheet, $start_date, $end_date;
        
        $this->show_wait($download);
        
        // 공급처 정보
        $supply_info = class_supply::get_supply_arr();
        
        // 전표 이름, 상태
        $query_sheet = "select * from expect_stockin_sheet where seq=$sheet";
        $result_sheet = mysql_query($query_sheet, $connect);
        $data_sheet = mysql_fetch_assoc($result_sheet);
        $sheet_name = $data_sheet[title];
        $sheet_status = $data_sheet[status];

        // 검색 
        $query = "select a.product_id a_product_id,
                         b.name b_name,
                         b.options b_options, 
                         b.barcode b_barcode, 
                         b.location b_location,
                         b.brand b_brand, 
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
                         a.seq a_seq
                    from expect_stockin_item a,
                         products b
                   where a.sheet_seq = $sheet and
                         a.product_id = b.product_id";
debug("get_IQ10 : " . $query);
        $result = mysql_query( $query, $connect );
        $total_rows = mysql_num_rows($result);
        
        $sum_arr = array();

        // 전체 데이타
        $data_all = array();
        $i = 1;
        while( $data = mysql_fetch_assoc($result) )
        {
            // 전표이름, 상태
            $data[sheet_name] = $sheet_name;
            $data[sheet_status] = $sheet_status;
            
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
            $temp_arr = $this->get_IQ10_data_arr($data, $f, $supply_info);

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
            
            // 수량, 날짜 경우 역순정렬
            if( $s_val[field] == "exp_qty"  ||
                $s_val[field] == "in_qty" ||
                $s_val[field] == "add_date"    )
                $ss_arr[] = SORT_DESC;
        }

        $this->array_multi_column_sort($ss_arr, &$data_all);

        return $data_all;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_IQ10()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet;

        // 조회 필드
        $page_code = 'IQ10_file';
        $f = class_table::get_setting();

        // 전체 쿼리
        if( $search )
            $data_all = $this->get_IQ10($f, &$total_rows, &$sum_arr, 1);
        
        $data_all[] = $sum_arr;
        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_IQ10( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_IQ10( $data_all, $fn, $f, $is_email=0, $is_download_all=0, $is_html=0)
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
        $query = "select * from expect_stockin_sheet where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $sheet_title    = $data[title];
        $sheet_req_date = $data[req_date];
        $sheet_exp_date = $data[exp_date];
        $sheet_memo     = $data[memo];
        
        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        global $page_code;
        $page_code = 'IQ10_search';
        
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
        if( $f_download_header_title )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("전표명 : ".$sheet_title, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $f_download_header_req_date )
        {
    	    if( _DOMAIN_ == 'ilovejchina' )
    	        $req_date_str = "배송일";
    	    else 
    	        $req_date_str = "입고요청일";

            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("$req_date_str : ".$sheet_req_date, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $f_download_header_exp_date )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("예정일 : ".$sheet_exp_date, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_memo : $f_download_header_memo )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("메모 : ".$sheet_memo, PHPExcel_Cell_DataType::TYPE_STRING);
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
    function download_IQ10()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    
    //###############################
    // 변경내용리셋팝업
    //###############################
    function IQ02()
    {
        global $template, $connect, $seq, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    //###############################
    // 화면설정팝업
    //###############################
    function IQ11()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 상품 추가 팝업
    //###############################
    function IQ13()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_id, $supply_name, $string, $string_type;

        // 작업중
        $this->show_wait();

        if( !$supply_id )
        {
            // 공급처명
            $query = "select * from in_req_bill where seq=$seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            if( $data[supply_code] )
            {
                $supply_id = $data[supply_code];
                $query = "select name from userinfo where code=$supply_id";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);
                $supply_name = $data[name];
            }
        }
        
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
            $par = array('template','supply_id', 'supply_name', 'seq', 'name', 'options');
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

            if( $string )
            {
                if( $string_type == "name" )
                    $query .= " and a.name like '%$string%' ";
                else if( $string_type == "options" )
                    $query .= " and a.options like '%$string%' ";
                else if( $string_type == "product_id" )
                    $query .= " and a.product_id = '$string' ";
                else if( $string_type == "barcode" )
                    $query .= " and a.barcode = '$string' ";
                else if( $string_type == "origin" )
                    $query .= " and a.origin like '%$string%' ";
                else if( $string_type == "brand" )
                    $query .= " and a.brand like '%$string%' ";
                else if( $string_type == "supply_options" )
                    $query .= " and a.supply_options like '%$string%' ";
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
    // 출력 row 생성하기
    //*****************************
    function get_IQ10_data_arr($data, $f, $supply_info)
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

        // 상품정보
        $product_info = array(
            "product_id"          => $data[a_product_id],
            "product_name"        => $data[b_name],
            "options"             => $data[b_options],
            "name_options"        => $data[b_name] . " " . $data[b_options],
            "barcode"             => $data[b_barcode],
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
            "product_img_small"   => $product_img_small_str
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
                $temp_arr[$f_val[field_id]] = $data[sheet_name];
            // 전표상태
            else if( $f_val[field_id] == "sheet_status" ) 
                $temp_arr[$f_val[field_id]] = ($data[sheet_status] == 1 ? "예정" : "완료");
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $temp_arr[$f_val[field_id]] = $old_collect_date;
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "item_delete_btn" )
                $temp_arr[$f_val[field_id]] = "<img src=./images/del_link.gif>삭제";
        }
        
        // 상품코드
        $temp_arr["product_id"] = $data[a_product_id];

        // 관리번호
        $temp_arr["seq"] = $data[a_seq];

        return $temp_arr;
    }    

    //###############################
    // 상품일괄추가
    //###############################
    function IQ15()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 전표 정보 수정
    //###############################
    function save_sheet_info()
    {
        global $connect, $sheet, $req_date, $exp_date, $memo;
        
        $query = "update expect_stockin_sheet set req_date='$req_date', exp_date='$exp_date', memo='$memo' where seq=$sheet";
        mysql_query($query, $connect);
    }

    //###############################
    // 전표 삭제
    //###############################
    function delete_sheet()
    {
        global $connect, $sheet;
        
        if(_DOMAIN_ == lalael2)
        {//완전삭제 복구x
        	$query = "delete from expect_stockin_sheet where seq=$sheet";	
        }
        else 
        {
         	$query = "update expect_stockin_sheet set is_delete=1, delete_date=now(), delete_user='$_SESSION[LOGIN_ID]' where seq=$sheet";
        }
        
        mysql_query($query, $connect);
    }
    
    //###############################
    // 상품 삭제
    //###############################
    function delete_item()
    {
        global $connect, $sheet, $item_seq;
        
        $query = "delete from expect_stockin_item where sheet_seq = $sheet and seq = $item_seq";
        mysql_query($query, $connect);
    }

    //###############################
    // 전표 삭제 취소
    //###############################
    function cancel_delete_sheet()
    {
        global $connect, $sheet;
        
        $query = "update expect_stockin_sheet set is_delete=0  where seq=$sheet";
        mysql_query($query, $connect);
    }

    //###############################
    // 전표 삭제 - all
    //###############################
    function delete_sheet_all()
    {
        global $connect, $seq_list;
        
        foreach( explode(",", $seq_list) as $seq_val )
        {
            $query = "select * from in_req_bill where seq=$seq_val";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            debug( "입고요청전표 삭제2 : seq - $data[seq] | title - $data[title] | crdate - $data[crdate] | crtime - $data[crtime] | status - $data[status] | owner - $data[owner] | smstime - $data[smstime] | complete_date - $data[complete_date] | worker - $data[worker] ");
    
            $query = "delete from in_req_bill where seq=$seq_val";
            mysql_query($query, $connect);

            $query = "delete from in_req_bill_item where bill_seq=$seq_val";
            mysql_query($query, $connect);
        }
    }

    //###############################
    // 변경내용저장
    //###############################
    function save_stockin()
    {
        global $connect, $seq, $stockin_str;
        
        $val = array();
        foreach( explode(",",$stockin_str) as $p_val )
        {
            // [상품코드][관리번호]필드1:값1$필드2:값2$필드3:값3$
            if( preg_match('/\[(.+)\]\[(.+)\](.+)/', $p_val, $matches ) )
            {
                // 상품코드
                $product_id = $matches[1];
                // 관리번호
                $seq = $matches[2];
                
                $query_set = "";

                // 필드1:값1$필드2:값2$필드3:값3$
                foreach( explode("$", $matches[3]) as $par_val )
                {
                    if( !$par_val ) continue;
                    
                    // 필드1:값1
                    list($f_name, $f_val) = explode(":", $par_val);
                    
                    // url 인코딩
                    if( $f_name == "expect_memo" || $f_name == "product_memo" )
                        $f_val = addslashes(urldecode( $f_val ));
                    
                    if( $f_name == "product_memo" )
                    {
                        $query_memo = "update products set memo='$f_val' where product_id = '$product_id'";
                        mysql_query($query_memo, $connect);
                    }
                    else
                        $query_set .= ($query_set ? "," : "") . "$f_name = '$f_val'";
                }
                
                if( $query_set )
                {
                    $query = "update expect_stockin_item set $query_set where seq='$seq'";
                    mysql_query($query, $connect);
                }
            }           
        }
    }

    //###############################
    // 변경내용리셋
    //###############################
    function reset_stockin()
    {
        global $connect, $sheet, $stockin_type, $stockin_qty;

        $query = "update expect_stockin_item
                     set $stockin_type = $stockin_qty,
                         exp_memo = ''
                   where sheet_seq = $sheet";
        mysql_query($query, $connect);
    }

    //###############################
    // 상품 추가
    //###############################
    function add_expect_stockin()
    {
        global $connect, $seq, $product_id, $exp_qty, $in_qty, $exp_memo;
        
        $val = array();
        
        // 추가
        $query = "insert expect_stockin_item
                     set sheet_seq     = $seq,
                         add_date      = now(),
                         product_id    = '$product_id',
                         exp_memo      = '$exp_memo',
                         exp_qty       = '$exp_qty',
                         in_qty        = '$in_qty'";
        if( mysql_query( $query, $connect ) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
        
        echo json_encode( $val );
    }

    //###############################
    // 상품 일괄  추가
    //###############################
    function add_all_expect_stockin()
    {
        global $connect, $pro_data, $seq;
        
        
        foreach ($pro_data as &$v) {
        	$product_id = $v["product_id"];
        	$in_qty		= $v["in_qty"];
        	$exp_qty	= $v["in_qty"];
        	$exp_memo	= $v["exp_memo"];
        	
        	$val = array();
	        // 추가
	        $query = "insert expect_stockin_item
	                     set sheet_seq     = $seq,
	                         add_date      = now(),
	                         product_id    = '$product_id',
	                         exp_memo      = '$exp_memo',
	                         exp_qty       = '$exp_qty',
	                         in_qty        = '$in_qty'";

	        if( mysql_query( $query, $connect ) )
	            $val['error'] = 0;
	        else
	            $val['error'] = 1;
        } 
        echo json_encode( $val );
    }
    //###############################
    // 완료 처리
    //###############################
    function set_complate()
    {
        global $template, $sheet, $connect;
        
        // 이미 완료처리된 전표인지 확인
        $query = "select status from expect_stockin_sheet where seq=$sheet";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[status] == 1 )
        {
            $val['error'] = 0;
            echo json_encode( $val );
            return;
        }
        
        $val = array();
        $query = "update expect_stockin_sheet set status=1, complete_date=now(), complete_user='$_SESSION[LOGIN_NAME]' where seq=$sheet";
        if( mysql_query( $query, $connect ) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
        
        $obj = new class_stock();

        $query_list = "select * from expect_stockin_item where sheet_seq=$sheet";
        $result_list = mysql_query($query_list, $connect);
        while( $data_list = mysql_fetch_assoc($result_list) )
        {
            if( $data_list[in_qty] == 0 )  continue;
            
            $info_arr = array(
                type       => "in",
                product_id => $data_list[product_id],
                bad        => 0,
                location   => 'Def',
                qty        => $data_list[in_qty],
                memo       => "입고예정전표입고($sheet):".$data_list[exp_memo],
                order_seq  => ""
            );
            $obj->set_stock($info_arr);
        }
        
        echo json_encode( $val );
    }    

    //###############################
    // 완료 처리 취소
    //###############################
    function cancel_complate()
    {
        global $template, $sheet, $connect;
        
        // 이미 완료취소처리된 전표인지 확인
        $query = "select status from expect_stockin_sheet where seq=$sheet";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[status] == 0 )
        {
            $val['error'] = 0;
            echo json_encode( $val );
            return;
        }
        
        $val = array();
        $query = "update expect_stockin_sheet set status=0 where seq=$sheet";
        if( mysql_query( $query, $connect ) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
        
        $obj = new class_stock();

        $query_list = "select * from expect_stockin_item where sheet_seq=$sheet";
        $result_list = mysql_query($query_list, $connect);
        while( $data_list = mysql_fetch_assoc($result_list) )
        {
            if( $data_list[in_qty] == 0 )  continue;
            
            $info_arr = array(
                type       => "in",
                product_id => $data_list[product_id],
                bad        => 0,
                location   => 'Def',
                qty        => $data_list[in_qty] * -1,
                memo       => "입고예정전표취소($sheet):".$data_list[exp_memo],
                order_seq  => ""
            );
            $obj->set_stock($info_arr);
        }
        
        echo json_encode( $val );
    }    

    function create_stockin_sheet()
    {
        global $connect, $req_date, $exp_date, $sheet_title;
        
        $val = array();
        
        // 동일 전표명 확인
        $query = "select * from expect_stockin_sheet where title='$sheet_title'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
            echo json_encode($val);
            exit;
        }
        
        $query = "insert expect_stockin_sheet 
                     set title    = '$sheet_title'
                        ,crdate   = now()
                        ,cruser   = '$_SESSION[LOGIN_ID]'
                        ,status   = 0
                        ,req_date = '$req_date'
                        ,exp_date = '$exp_date'";
        mysql_query($query, $connect);
        
        $val['error'] = 0;
        echo json_encode($val);
    }

    function upload()
    {
        global $connect, $sheet;
        
        $obj = new class_file();
        $arr_data =  $obj->upload();

        foreach ($arr_data as $data )
        {
            $query = "select product_id from products where product_id='$data[0]' and is_delete=0 and is_represent=0";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
            {
                // 추가
                $query = "insert expect_stockin_item
                             set sheet_seq     = $sheet,
                                 add_date      = now(),
                                 product_id    = '$data[0]',
                                 exp_qty       = '$data[1]',
                                 in_qty        = '$data[1]'";
                mysql_query( $query, $connect );
            }
        }
        
        echo "
        <script language='javascript'>
        parent.opener.myform.submit();
        parent.hide_waiting();
        </script>
        ";        
    }
    
}
?>
