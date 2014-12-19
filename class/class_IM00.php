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

require_once "class_IT00.php";

include_once "googleapis/urlshortner.php";
include_once "googleapis/urlshortner_naver.php";
include_once "googleapis/urlshortner_pimz.php";

class class_IM00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function IM00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $start_date, $end_date, $title, $req_status;

        // 조회 필드
        $page_code = 'IM00';
        $f = class_table::get_setting();

        // 검색
        if( $search )
        {
            // 전체 쿼리
            $data_all = $this->get_IM00($f, &$total_rows, &$sum_arr);
            
            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        
        // 처음 화면
        else
        {
            // 초기 검색 조건
            $page_code = 'IM00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
        
            // 요청일
            if( $f_req_date == 0 )
            {
                $start_date = date("Y-m-d");
                $end_date = date("Y-m-d");
            }
            else if( $f_req_date == 1 )
            {
                $start_date = date("Y-m-d", strtotime("-1 day"));
                $end_date = date("Y-m-d", strtotime("-1 day"));
            }
            else if( $f_req_date == 2 )
            {
                $start_date = date("Y-m-d", strtotime("-1 week"));
                $end_date = date("Y-m-d");
            }
            else if( $f_req_date == 3 )
            {
                $start_date = date("Y-m-d", strtotime("-1 week"));
                $end_date = date("Y-m-d", strtotime("-1 day"));
            }

            // 요청상태
            $req_status = $f_req_status;
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메인 쿼리
    //###############################
    function get_IM00($f, &$total_rows, &$sum_arr)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $start_date, $end_date, $title, $req_status;
        
        $this->show_wait($download);

        // 공급처 정보 배열
        $supply_info = class_supply::get_supply_arr();
        
        // 검색 
        $query = "select * from in_req_bill where crdate>='$start_date' and crdate<='$end_date' ";
        
        if ( $req_status )
            $query .= " and status=$req_status ";
        
        if ( $title )
            $query .= " and title like '%$title%'";

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
            $temp_arr = $this->get_IM00_data_arr($data, $f, $supply_info);

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
            if( $s_val[field] == "req_date"        ||
                $s_val[field] == "product_qty"     ||
                $s_val[field] == "total_qty"       ||
                $s_val[field] == "total_org_price" )
                $ss_arr[] = SORT_DESC;
        }

        // 전체 데이터 멀티 컬럼 정렬
        $this->array_multi_column_sort($ss_arr, &$data_all);

        return $data_all;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_IM00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $sheet_list, $download_type;

        // 조회 필드
        $page_code = 'IM10_file';
        $f = class_table::get_setting();

        // 전체단일파일
        if( $download_type )
        {
            // 전체 쿼리
            $data_all = array();
            $sum_arr_all = array();
            foreach( explode(",", $sheet_list) as $sheet_val )
            {
                $sheet = $sheet_val;
                $data_all = array_merge($data_all, $this->get_IM10($f, &$total_rows, &$sum_arr, 1));
                
                // 구분용 한줄 추가
                if( _DOMAIN_ == 'soramam' )
                    $data_all[] = array();
                
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
            $fn = "request_stock_" . date("Ymd_His") . ".xls";
            $this->make_file_IM10( $data_all, $fn, $f, 0, 1 );
        }
        // 개별압축파일
        else
        {
            $file_arr = array();
            foreach( explode(",", $sheet_list) as $sheet_val )
            {
                $sheet = $sheet_val;
                $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 1);

                // 전표명
                $query_sheet_name = "select * from in_req_bill where seq=$sheet";
                $result_sheet_name = mysql_query($query_sheet_name, $connect);
                $data_sheet_name = mysql_fetch_assoc($result_sheet_name);
                
                // 파일명에 다음의 문자 불가( \ / : * ? " < > | )
                $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$data_sheet_name[title]) . ".xls";
                
                $data_all[] = $sum_arr;
                $this->make_file_IM10( $data_all, $fn, $f, 0);
                $file_arr[$fn] = _upload_dir.$fn;
            }
    
            $fn = "request_stock_" . date("Ymd_His") . ".zip";
            
            $ziper = new zipfile(); 
            $ziper->addFiles($file_arr);  //array of files 
            $ziper->output(_upload_dir . $fn); 
        }
        
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 전체 문자 보내기
    //###############################
    function send_sms_IM00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $mobile_from, $mobile_content, $sheet_list;

        // 조회 필드
        $page_code = 'IM10_email';
        $f = class_table::get_setting();

        // 대량문자 보내기
        $query = "insert into multi_sms_title 
                     set crdate= Now()
                        ,owner='" . $_SESSION[LOGIN_NAME] . "'
                        ,memo = '공급처 입고요청'";
        mysql_query( $query, $connect );

        $query = "select max(sms_seq) max_sms_seq from multi_sms_title";
        $result = mysql_query($query, $connect);
        $data   = mysql_fetch_assoc($result );

        $sms_seq = $data[max_sms_seq];
        $total_count = 0;

        foreach( explode(",", $sheet_list) as $sheet_val )
        {
            $sheet = $sheet_val;


            // 전표명
            $query = "select * from in_req_bill where seq=$sheet"; 
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $sheet_name = $data[title];

            
            // 핸드폰 번호 없으면 발송 안함
            if( $data[supply_code] )
            {
                $query_supply = "select * from userinfo where code=$data[supply_code]";
                $result_supply = mysql_query($query_supply, $connect);
                $data_supply = mysql_fetch_assoc($result_supply);
                
                $mobile_top = substr($data_supply[mobile],0,3);
                $tel_top = substr($data_supply[tel],0,3);
                if( $mobile_top == "010" || $mobile_top == "011" || $mobile_top == "016" || $mobile_top == "017" || $mobile_top == "018" || $mobile_top == "019" )
                    $mobile_to = $data_supply[mobile];
                else if( $tel_top == "010" || $tel_top == "011" || $tel_top == "016" || $tel_top == "017" || $tel_top == "018" || $tel_top == "019" )
                    $mobile_to = $data_supply[tel];
                else
                    continue;
            }
            else
                continue;
                
            $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 0);

            $_sheet_seq = $sheet;
            
            // 개별전표 파일 생성하기 (이름에 '/'가 있는경우 '-'로 수정)
            $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$sheet_name) . ".html";
            $this->make_file_IM10( $data_all, $fn, $f, 1, 0, 1 );

    
            // 문자 보내기
            $fn_url = str_replace("+", "%20", urlencode($fn));

            $full_url = "http://admin" . $_SESSION[WEB_SERVER] . ".ezadmin.co.kr/data/" . _DOMAIN_ . "/" . $fn_url;
            
            $sms_content = $mobile_content . " " . $this->make_short_url($full_url);

            // 대량문자로 보낸다
            foreach( explode(",",$mobile_to) as $mobile_to_val )
            {
                $query = "insert into multi_sms_list 
                             set sms_seq       = $sms_seq
                                ,order_seq     = 0
                                ,recv_mobile   = '$mobile_to_val'
                                ,send_mobile   = '$mobile_from'
                                ,memo          = '$sms_content'
                                ,status        = 0
                                ,reg_date      = Now()
                                ,sheet_seq	   = '$_sheet_seq'
                                ";

                mysql_query($query, $connect);
                $total_count++;
            }

        }
        
        if( $total_count )
        {
            $_connect = sys_db_connect();
            
            $query = "insert into sys_sms_multi
                         set domain     = '" . _DOMAIN_ . "'
                             ,sms_seq   = $sms_seq
                             ,req_count = $total_count
                             ,status    = 0
                             ,reg_date  = Now()
                             ";
            mysql_query( $query, $_connect );                         
        }
        
        echo "<script type='text/javascript'>alert('문자를 발송하였습니다.')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    //###############################
    // 전체 이메일 보내기
    //###############################
    function send_email_IM00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $mail_from, $mail_to, $mail_title, $mail_content, $sheet_list;

        // 조회 필드
        $page_code = 'IM10_email';
        $f = class_table::get_setting();

        foreach( explode(",", $sheet_list) as $sheet_val )
        {
            $sheet = $sheet_val;
            
            // 전표명
            $query = "select * from in_req_bill where seq=$sheet"; 
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $sheet_name = $data[title];
            
            // 메일주소 없으면 발송 안함
            if( $data[supply_code] )
            {
                $query_supply = "select * from userinfo where code=$data[supply_code]";
                $result_supply = mysql_query($query_supply, $connect);
                $data_supply = mysql_fetch_assoc($result_supply);
                
                if( $data_supply[email] )
                    $mail_to = $data_supply[email];
                else
                    continue;
            }
            else
                continue;
                
            $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 0);
            
            // 개별전표 파일 생성하기 (이름에 '/'가 있는경우 '-'로 수정)
            $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$sheet_name) . ".xls";
            $this->make_file_IM10( $data_all, $fn, $f, 1 );
    
            // 메일 보내기
            $mail_list = $mail_to;
            foreach( explode(",", $mail_list) as $mail_val )
            {
                $mail_to = trim( $mail_val );
                $r = $this->send_sheet_email( $fn, $sheet_val );
            }
        }
        
        echo "<script type='text/javascript'>alert('메일을 발송하였습니다.')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    //###############################
    // 메모 일괄 추가
    //###############################
    function add_memo_IM00()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $memo, $sheet_list;

        foreach( explode(",", $sheet_list) as $sheet_val )
        {
            $sheet = $sheet_val;
            
            // 전표정보
            $query = "select * from in_req_bill where seq=$sheet"; 
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $new_memo = addslashes($memo) . "\n" . addslashes($data[supply_memo]);
            
            $query_supply = "update in_req_bill set supply_memo = '$new_memo' where seq=$data[seq]";
            mysql_query($query_supply, $connect);
        }

        echo "<script type='text/javascript'>alert('메모를 추가하였습니다.')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    //###############################
    // 화면설정팝업
    //###############################
    function IM01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메일 보내기 팝업
    //###############################
    function IM02()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 문자 보내기 팝업
    //###############################
    function IM04()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메모 일괄 추가 팝업
    //###############################
    function IM05()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메일 보내기 이력 팝업
    //###############################
    function IM03()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 문자 보내기 이력 팝업
    //###############################
    function IM06()
    {
        global $template, $connect, $sheet;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_IM00_data_arr($data, $f, $supply_info)
    {
        global $connect;
        
        // 전표 상품 상세
        $query_detail = "select count(a.product_id) cnt,
                                sum(a.stockin) sum_stockin,
                                sum(a.stockin * b.org_price) sum_price,
                                sum(b.reserve_qty) sum_reserve_qty
                           from in_req_bill_item a,
                                products b
                          where a.product_id = b.product_id and
                                a.bill_seq = $data[seq]";
        $result_detail = mysql_query($query_detail, $connect);
        $data_detail = mysql_fetch_assoc($result_detail);

        $temp_arr = array();
        foreach( $f as $f_val )
        {
            if( !$f_val[chk] && !$f_val[sort] )  continue;

            //+++++++++++++++++++
            // 공급처 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "supply" ) 
            {
                $temp_arr[$f_val[field_id]] = class_table::get_supply_arr_data($f_val[field_id], $supply_info, $data[supply_code]);
                continue;
            }

            //+++++++++++++++++++
            // 입고요청전표 (IM00)
            //+++++++++++++++++++

            // 요청일
            if( $f_val[field_id] == "request_date" )
                $temp_arr[$f_val[field_id]] = $data[crdate] . " " . $data[crtime];
            // 요청자
            else if( $f_val[field_id] == "request_who" )
                $temp_arr[$f_val[field_id]] = $data[owner];
            // 완료일
            else if( $f_val[field_id] == "complete_date" )
                $temp_arr[$f_val[field_id]] = $data[complete_date];
            // 완료자
            else if( $f_val[field_id] == "complete_who" )
                $temp_arr[$f_val[field_id]] = $data[worker];
            // 상품수
            else if( $f_val[field_id] == "total_product" )
                $temp_arr[$f_val[field_id]] = $data_detail[cnt];
            // 총입고수량
            else if( $f_val[field_id] == "total_product_qty" )
                $temp_arr[$f_val[field_id]] = $data_detail[sum_stockin];
            // 총원가
            else if( $f_val[field_id] == "total_org_price" )
                $temp_arr[$f_val[field_id]] = $data_detail[sum_price];
            else if( $f_val[field_id] == "stock_in_standby" )
            	$temp_arr[$f_val[field_id]] = $data_detail[sum_reserve_qty];
            
            // 전표명
            else if( $f_val[field_id] == "sheet_name" )
                $temp_arr[$f_val[field_id]] = $data[title];
            // 상태
            else if( $f_val[field_id] == "request_status" )
                $temp_arr[$f_val[field_id]] = ($data[status]==1 ? "요청" : "완료");
                
            // sms발송회수
            else if( $f_val[field_id] == "send_sms_cnt" )
        	{
                $query_sms	= "select count(*) as cnt from multi_sms_list where sheet_seq = $data[seq]";
                $result_sms = mysql_query($query_sms, $connect);       
                if( mysql_num_rows($result_sms) )
                {
                	$data_sms = mysql_fetch_assoc($result_sms);
                	if($data_sms[cnt]>0)
                		$data_sms[cnt] = $data_sms[cnt]."회";
                	else
                		$data_sms[cnt] = " ";
                	
                	$temp_arr[$f_val[field_id]] = $data_sms[cnt];
                }
            }
                
            // sms확인
            else if( $f_val[field_id] == "check_sms" )
            {
                $query_sms	= "select max(status) as status from multi_sms_list where sheet_seq = $data[seq]";
                $result_sms = mysql_query($query_sms, $connect);       
                $row = mysql_num_rows($result_sms);
                if($row)
                {
                	$data_sms = mysql_fetch_assoc($result_sms);
                	if($data_sms[status] > 0 )
                		$data_sms[status] = "전송완료";
                	else if($data_sms[status] == "")
                		$data_sms[status] = "";
                	else
                		$data_sms[status] = "전송실패";
                	
                	$temp_arr[$f_val[field_id]] = $data_sms[status];
                }
            }
            
            
            // 이메일발송회수
            else if( $f_val[field_id] == "send_email_cnt" )
                $temp_arr[$f_val[field_id]] = ($data[send_email_cnt] > 0 ? "$data[send_email_cnt] 회" : "");
            // 이메일확인
            else if( $f_val[field_id] == "check_email" )
            {
                $check_time = "";
                $query_email = "select * from send_email_history where bill_seq=$data[seq] and check_time>0 order by check_time limit 1";
                $result_email = mysql_query($query_email, $connect);
                if( mysql_num_rows($result_email) )
                {
                    $data_email = mysql_fetch_assoc($result_email);
                    if( $data_email[check_time] > 0 )
                        $check_time = $data_email[check_time];
                }
                $temp_arr[$f_val[field_id]] = $check_time;
            }
        }
        
        // 전표 번호
        $temp_arr["sheet"] = $data[seq];

        // 공급처 코드
        $temp_arr["supply_code"] = ($data[supply_code] ? $data[supply_code] : "");

        return $temp_arr;
    }    

    //###############################
    // 메인 화면 IM10
    //###############################
    function IM10()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $sheet;

        // 조회 필드
        $page_code = 'IM10';
        $f = class_table::get_setting();

        // 전체 쿼리
        if( $search )
            $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr);

        // 정렬방향
        if( $sort )
            $sort_order = ($sort_order ? 0 : 1);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메인 쿼리
    //###############################
    function get_IM10($f, &$total_rows, &$sum_arr, $download=0)
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
        global $sheet, $start_date, $end_date;
        
        $this->show_wait($download);
        
        // 공급처 정보
        $supply_info = class_supply::get_supply_arr();
        
        // 전표 이름, 상태
        $query_sheet = "select * from in_req_bill where seq=$sheet";
        $result_sheet = mysql_query($query_sheet, $connect);
        $data_sheet = mysql_fetch_assoc($result_sheet);
        $sheet_name = $data_sheet[title];
        $sheet_status = $data_sheet[status];


        // 검색 
        $query = "select a.product_id a_product_id,

                         a.not_yet_deliv a_not_yet_deliv,
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
                         a.req_stock a_req_stock,
                         a.stockin a_stockin,
                         a.memo a_memo,
                         a.req_memo a_req_memo,
                         b.supply_code b_supply_code,
                         b.img_500 b_img_500,
                         b.org_id b_org_id,
                         
        				 b.reserve_qty b_reserve_qty,
        				 b.return_qty b_return_qty,
        				 b.return_money b_return_money,
						 a.return_qty        a_return_qty       ,						 
						 a.return_money      a_return_money     ,
						 a.reserve_money_qty a_reserve_money_qty,
						 a.reserve_money     a_reserve_money    

                    from in_req_bill_item a,
                         products b
                   where a.bill_seq = $sheet and
                         a.product_id = b.product_id";
debug("IM00 : " . $query);
                         

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
            $temp_arr = $this->get_IM10_data_arr($data, $f, $supply_info);

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
            if( $s_val[field] == "stock"       ||
                $s_val[field] == "not_trans"   ||
                $s_val[field] == "lack_qty"    ||
                $s_val[field] == "request_qty" )
                $ss_arr[] = SORT_DESC;
        }

        $this->array_multi_column_sort($ss_arr, &$data_all);

        return $data_all;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_IM10()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet;

        // 조회 필드
        $page_code = 'IM10_file';
        $f = class_table::get_setting();

        // 전체 쿼리
        if( $search )
            $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 1);
        
        $data_all[] = $sum_arr;
        $fn = "request_stock_" . date("Ymd_His") . ".xls";
        $this->make_file_IM10( $data_all, $fn, $f );
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_IM10( $data_all, $fn, $f, $is_email=0, $is_download_all=0, $is_html=0)
    {
        global $connect, $sys_connect, $is_date, $sheet_title, $req_date, $supply_memo;
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
        
        $sql = "select * from sys_domain where id = '" . _DOMAIN_ . "'";
		$sys_data = mysql_fetch_assoc(mysql_query($sql, $sys_connect));
		
		
		
        //++++++++++++++++++++++++
        // 조회 필드
        //++++++++++++++++++++++++
        global $page_code;
        $page_code = 'IM10_search';
        
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
        if( $is_email ? $f_email_header_bal : $f_download_header_bal )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("발주처 : ".$sys_data[corp_name], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_bal_tel : $f_download_header_bal_tel )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("발주처 연락처 : $sys_data[corp_tel] / $sys_data[corp_mobile]", PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->mergeCells("A$row:{$end_col}{$row}");
            $row++;
        }
        if( $is_email ? $f_email_header_bal_addr : $f_download_header_bal_addr )
        {
            $sheet->getCellByColumnAndRow(0, $row)->setValueExplicit("발주처 주소 : $sys_data[corp_address] $sys_data[corp_address2]", PHPExcel_Cell_DataType::TYPE_STRING);
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
    function download_IM10()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

    //###############################
    // 화면설정팝업
    //###############################
    function IM11()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 메일 보내기 팝업
    //###############################
    function IM12()
    {
        global $template, $connect, $sheet;

        // 공급처 정보
        $query = "select * from in_req_bill where seq=$sheet";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 받는 메일 주소
        $mail_to = "";
        if( $data[supply_code] )
        {
            $query = "select * from userinfo where code=$data[supply_code]";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $mail_to = $data[email];
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	//###############################
    // 교환입고 대기 
    //###############################
    function IM15()
    {
    	global $template, $connect, $product_id, $sheet_id;
    	
    	$query = "select org_price, return_qty, return_money from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $product_info = mysql_fetch_assoc($result);
    	
    	$master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function return_qty_modify()
    {
    	global $template, $connect, $product_id, $sheet_id, $modify_type, $return_qty, $return_money, $minus_return_qty, $plus_return_qty, $plus_return_money, $minus_return_money, $memo;
    	
    	$query = "select return_qty, return_money from products where product_id='$product_id'";
    	$result = mysql_query($query, $connect);
    	$data = mysql_fetch_assoc($result);
    	
    	$org_qty = $data[return_qty];
    	$org_money = $data[return_money];
    	
    	//$modify_type 1:교환대기조정, 2:잔대기조정, 3:교환대기->잔대기, 4:잔대기->교환대기
		if($modify_type == 1)
		{
			$query = "UPDATE products SET return_qty = $return_qty WHERE product_id = '$product_id'";
			$modify_qty = $return_qty - $org_qty;
			$modify_money = 0;
		}
		else if($modify_type == 2)
		{
			$query = "UPDATE products SET return_money = $return_money WHERE product_id = '$product_id'";
			$modify_qty = 0;
			$modify_money = $return_money - $org_money;
		}
		else if($modify_type == 3)
		{
			$query = "UPDATE products SET return_qty = return_qty - $minus_return_qty, return_money = return_money + $plus_return_money WHERE product_id = '$product_id'";
			$modify_qty = $minus_return_qty * -1;
			$modify_money = $plus_return_money;
		}
		else if($modify_type == 4)
		{
			$query = "UPDATE products SET return_qty = return_qty + $plus_return_qty, return_money = return_money - $minus_return_money WHERE product_id = '$product_id'";
			$modify_qty = $plus_return_qty;
			$modify_money = $minus_return_money * -1;
		}
		mysql_query($query, $connect);
		
		// log
		class_IT00::return_ready_sheet_log($sheet_id, $product_id, 2, $modify_qty, $modify_money, $memo);
    }

    //###############################
    // SMS 보내기 팝업
    //###############################
    function IM14()
    {
        global $template, $connect, $sheet;

        // 공급처 정보
        $query = "select * from in_req_bill where seq=$sheet";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 받는 번호
        $mail_to = "";
        if( $data[supply_code] )
        {
            $query = "select * from userinfo where code=$data[supply_code]";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $mobile_top = substr($data[mobile],0,3);
            $tel_top = substr($data[tel],0,3);
            if( $mobile_top == "010" || $mobile_top == "011" || $mobile_top == "016" || $mobile_top == "017" || $mobile_top == "018" || $mobile_top == "019" )
                $mobile_to = $data[mobile];
            else if( $tel_top == "010" || $tel_top == "011" || $tel_top == "016" || $tel_top == "017" || $tel_top == "018" || $tel_top == "019" )
                $mobile_to = $data[tel];
            else
                $mobile_to = "";
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //###############################
    // 상품 추가 팝업
    //###############################
    function IM13()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_id, $supply_name, $name, $options, $brand, $supply_options;

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
    // 출력 row 생성하기
    //*****************************
    function get_IM10_data_arr($data, $f, $supply_info)
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
            "org_id"          	  => $data[b_org_id],
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
            "not_yet_deliv"       => $data[a_not_yet_deliv],
            "reg_date"            => $data[b_reg_date],
            "reg_date_option"     => $data[b_reg_date],
            "stock_in_standby"    => $data[b_reserve_qty],
            "enable_sale"         => $enable_sale,
            "sale_stop_date"      => $sale_stop_date,
            "product_img"         => $product_img_str,
            "product_img_small"   => $product_img_small_str
        );
        // 원가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            $product_org_price = 0;
        else
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
            // 입고요청전표상세 (IM10)
            //+++++++++++++++++++

            // 요청수량
            if( $f_val[field_id] == "request_qty" ) 
                $temp_arr[$f_val[field_id]] = $data[a_req_stock];
            // 요청수량_수정
            else if( $f_val[field_id] == "request_qty_input" ) 
                $temp_arr[$f_val[field_id]] = $data[a_req_stock];
            // 요청금액[원가]
            else if( $f_val[field_id] == "request_amount" ) 
                $temp_arr[$f_val[field_id]] = $data[a_req_stock] * $product_org_price;
            // 요청수량[판매가]
			else if( $f_val[field_id] == "request_shop_price" ) 
                $temp_arr[$f_val[field_id]] = $data[a_req_stock] * $product_info[shop_price];
            // 입고수량
            else if( $f_val[field_id] == "stockin_qty" ) 
                $temp_arr[$f_val[field_id]] = $data[a_stockin];
            // 입고금액
            else if( $f_val[field_id] == "stockin_amount" ) 
                $temp_arr[$f_val[field_id]] = $data[a_stockin] * $product_org_price;
            // 요청메모
            else if( $f_val[field_id] == "request_memo" ) 
                $temp_arr[$f_val[field_id]] = $data[a_req_memo];
            // 재고메모
            else if( $f_val[field_id] == "stock_memo" ) 
                $temp_arr[$f_val[field_id]] = $data[a_memo];
            // 전표이름
            else if( $f_val[field_id] == "sheet_name" ) 
                $temp_arr[$f_val[field_id]] = $data[sheet_name];
            // 전표상태
            else if( $f_val[field_id] == "sheet_status" ) 
                $temp_arr[$f_val[field_id]] = ($data[sheet_status] == 1 ? "요청" : "완료");
            // 가장 오래된 발주일
            else if( $f_val[field_id] == "collect_date" )
                $temp_arr[$f_val[field_id]] = $old_collect_date;
            //공급처 교환대기
			else if( $f_val[field_id] == "supply_change_standby" )
			{
                if($data[b_return_qty] || $data[b_return_money])
                {
                    $hidden_str = "style='display:none;'";
                    $expand_str = "";
                }
                else
                {
                    $hidden_str = "";
                    $expand_str = "style='display:none;'";
                }
                $str .= "
                	<div class='standby_head standby_hidden' $hidden_str>
        	            <img src='images/plus.png' class='show_hidden_btn1'>
        	        </div>
                	<div class='standby_head standby_expand' $expand_str>
        	            <img src='images/minus.png' class='show_hidden_btn2'>
        	            <div class='return_modify_btn'>조정</div>
        	        </div>
                	<div class='standby_body standby_expand' $expand_str>
    	                <ul>
    		                <li>
    			                <div class='standby_body_title'>교환대기수량</div>
    			                <input class='input22 standby_body_content input_readonly return_qty_standby' type=text value='" . number_format($data[b_return_qty]) . "' readonly>
    		                </li>
    		                <li style='padding-bottom:5px;'>
    		                	<div class='standby_body_title'>교환입고수량</div>
    		                	<input class='input22 standby_body_content return_qty num_int' type='text' value='" . number_format($data[a_return_qty]) . "'>
    		                </li>		                
    		                <li>
    			                <div class='standby_body_title'>잔 대기금액</div>
    			                <input class='input22 standby_body_content input_readonly return_money_standby' type=text value='" . number_format($data[b_return_money]) . "' readonly>
    		                </li>
    		                <li>
    		                	<div class='standby_body_title'>잔 처리금액</div>
    		                	<input class='input22 standby_body_content return_money num_int' type='text' org_price='$data[b_org_price]' value='" . number_format($data[a_return_money]) . "'>
    		                </li>
    	                </ul>
                    </div>
                ";
                $temp_arr[$f_val[field_id]] = $str;
			}
			//입고대기
			else if( $f_val[field_id] == "stock_in_standby2" )
			{
			    // 입고처리수량은, 입고대기수량에서 잔처리수량 뺀 수량과 입고수량 중에서 작은 값.
			    $expect_stockin_qty = min($data[b_reserve_qty] - $data[a_reserve_money_qty], $data[a_stockin]);
			    
                if($data[b_reserve_qty])
                {
                    $hidden_str = "style='display:none;'";
                    $expand_str = "";
                }
                else
                {
                    $hidden_str = "";
                    $expand_str = "style='display:none;'";
                }
                $str = "
                	<div class='standby_head standby_hidden' $hidden_str>
        	            <img src='images/plus.png' class='show_hidden_btn1'>
        	        </div>
                	<div class='standby_head standby_expand' $expand_str>
        	            <img src='images/minus.png' class='show_hidden_btn2'>
        	        </div>
                    <div class='standby_body standby_expand' $expand_str>
    	                <ul>
    		                <li>
    			                <div class='standby_body_title'>입고대기수량</div>
    			                <input class='input22 standby_body_content input_readonly standby_qty' type=text value='" . number_format($data[b_reserve_qty]) . "' readonly>
    		                </li>
    		                <li style='padding-bottom:5px;'>
    			                <div class='standby_body_title'>입고처리수량</div>
    			                <input class='input22 standby_body_content input_readonly standby_in_qty' type=text value='" . number_format($expect_stockin_qty) . "' readonly>
    		                </li>
    		                <li>
    		                	<div class='standby_body_title'>잔 처리수량</div>
    		                	<input class='input22 standby_body_content reserve_money_qty num_int' type='text' value='" . number_format($data[a_reserve_money_qty]) . "'>
    		                </li>
    		                <li>
    		                	<div class='standby_body_title'>잔 처리금액</div>
    		                	<input class='input22 standby_body_content reserve_money num_int' org_price='$data[b_org_price]' type='text' value='" . number_format($data[a_reserve_money]) . "'>
    		                </li>		                
    	                </ul>
                    </div>
                ";
            	$temp_arr[$f_val[field_id]] = $str;
			}
			else if( $f_val[field_id] == "return_qty_standby" )
			{
				$temp_arr[$f_val[field_id]] = ($data[b_return_qty]);
			}
			else if( $f_val[field_id] == "return_qty" )
			{
				$temp_arr[$f_val[field_id]] = ($data[a_return_qty]);
			}
			else if( $f_val[field_id] == "return_money_standby" )
			{
				$temp_arr[$f_val[field_id]] = ($data[b_return_money]);
			}
			else if( $f_val[field_id] == "return_money" )
			{
				$temp_arr[$f_val[field_id]] = ($data[a_return_money]);
			}
        }

        // 상품코드
        $temp_arr["product_id"] = $data[a_product_id];
        // 상품코드
        $temp_arr["org_id"] = $data[b_org_id];

        return $temp_arr;
    }    

    //###############################
    // 링크 문자 보내기
    //###############################
    function send_sms_IM10()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $mobile_from, $mobile_to, $mobile_content;
        
        $sheet_seq = $sheet;

        // 조회 필드
        $page_code = 'IM10_email';
        $f = class_table::get_setting();

        $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 0);
        
        // 전표명
        $query = "select * from in_req_bill where seq=$sheet"; 
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $sheet_name = $data[title];

        // 개별전표 파일 생성하기 (이름에 '/'가 있는경우 '-'로 수정)
        $data_all[] = $sum_arr;
        $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$sheet_name) . ".html";
        $this->make_file_IM10( $data_all, $fn, $f, 1, 0, 1 );

        $fn_url = str_replace("+", "%20", urlencode($fn));

        $full_url = "http://admin" . $_SESSION[WEB_SERVER] . ".ezadmin.co.kr/data/" . _DOMAIN_ . "/" . $fn_url;
        $sms_content = $mobile_content . " " . $this->make_short_url($full_url);
        
        class_sms::send_ums($sms_content, $mobile_from, $mobile_to, "");        

        // 메일 보내기
        if( 1 )
            $msg = "문자를 발송하였습니다..";
        else
            $msg = "문자 발송에 실패했습니다..";

        echo "<script type='text/javascript'>alert('$msg')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    //###############################
    // 이메일 보내기
    //###############################
    function send_email_IM10()
    {
        global $template, $connect, $search, $page_code, $sort, $sort_order;
        global $sheet, $mail_from, $mail_to, $mail_title, $mail_content;
        
        $sheet_seq = $sheet;

        // 조회 필드
        $page_code = 'IM10_email';
        $f = class_table::get_setting();

        $data_all = $this->get_IM10($f, &$total_rows, &$sum_arr, 0);
        
        // 전표명
        $query = "select * from in_req_bill where seq=$sheet"; 
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $sheet_name = $data[title];

        // 개별전표 파일 생성하기 (이름에 '/'가 있는경우 '-'로 수정)
        $data_all[] = $sum_arr;
        $fn = str_replace(array("\\", "/", ":", "*", "?", "\"", "<", ">", "|"),"_",$sheet_name) . ".xls";
        $this->make_file_IM10( $data_all, $fn, $f, 1 );

        // 메일 보내기
        if( $_SESSION[LOGIN_ID] == 'root2' )
        {
            $mail_list = $mail_to;
            foreach( explode(",", $mail_list) as $mail_val )
            {
                $mail_to = trim($mail_val);
                if( $this->send_sheet_email( $fn, $sheet_seq ) )
                    $msg = "메일을 발송하였습니다..";
                else
                    $msg = "메일 발송에 실패했습니다..";
            }
        }
        else
        {
            if( $this->send_sheet_email( $fn, $sheet_seq ) )
                $msg = "메일을 발송하였습니다..";
            else
                $msg = "메일 발송에 실패했습니다..";
        }
                
        echo "<script type='text/javascript'>alert('$msg')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    function send_sheet_email($fn, $sheet_seq)
    {
        global $connect, $sheet;
        global $mail_from, $mail_to, $mail_title, $mail_content;
        
            
        // 메일 보내기 로그
        $query = "insert send_email_history
                     set bill_seq   = '$sheet_seq',
                         crdate     = now(),
                         mail_from  = '$mail_from',
                         mail_to    = '$mail_to',
                         worker     = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query, $connect);
        $query = "select seq from send_email_history where bill_seq='$sheet_seq' order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $email_seq = $data[seq];

        $from = $mail_from;
        $to = $mail_to;
        $subject = iconv('utf-8','cp949',$mail_title);
        $body = iconv('utf-8','cp949',$mail_content);
        
        $boundary = "----".uniqid("part"); // 이메일 내용 구분자 설정
        
        // 헤더생성
        $header .= "Return-Path: <$from>\r\n";    // 반송 이메일 주소
        $header .= "From: $from\r\n";      // 보내는 사람 이메일 주소
        $header .= "MIME-Version: 1.0\r\n";    // MIME 버전 표시
        $header .= "Content-Type: Multipart/mixed; boundary = \"$boundary\"";  // 구분자가 $boundary 임을 알려줌
        // 여기부터는 이메일 본문 생성 
        $mailbody .= "This is a multi-part message in MIME format.\r\n\r\n";  // 메세지
        $mailbody .= "--$boundary\r\n";               // 내용 구분 시작
        //내용이 일반 텍스트와 html 을 사용하며 한글이라고 알려줌
        $mailbody .= "Content-Type: text/html; charset=\"EUC-KR\"\r\n";
        //암호화 방식을 알려줌
        $mailbody .= "Content-Transfer-Encoding: base64\r\n\r\n";
        //이메일 내용을 암호화 해서 추가
        $body .= " <img src='http://".$_SERVER["HTTP_HOST"]."/api/check_email.php?id="._DOMAIN_."&seq=$email_seq' width=0 height=0>";
        $mailbody .= base64_encode(nl2br($body))."\r\n\r\n";
        // 첨부파일
        $fp = fopen(_upload_dir.$fn, "r");
        $file = fread($fp, filesize(_upload_dir.$fn));  // 파일 내용을 읽음
        fclose($fp);           // 파일 close
        $filename = iconv('utf-8','cp949',$fn);  // 파일명만 추출 후 $filename에 저장
        // 파일첨부파트
        $mailbody .= "--$boundary\r\n";     // 내용 구분자 추가
        // 여기부터는 어떤 내용이라는 것을 알려줌
        $mailbody.= "Content-Type: application/vnd.ms-excel; name=\""."=?EUC-KR?B?".base64_encode(nl2br($filename))."?="."\"\r\n";
        //암호화 방식을 알려줌
        $mailbody .= "Content-Transfer-Encoding: base64\r\n";
        // 첨부파일임을 알려줌
        $mailbody .= "Content-Disposition: attachment; filename=\""."=?EUC-KR?B?".base64_encode(nl2br($filename))."?="."\"\r\n\r\n";
        // 파일 내요을 암호화 하여 추가
        $mailbody .= base64_encode($file)."\r\n\r\n";

// 2014-01-22
// 클라우드서버(42,43,44,45,46)에서 hanmail로 발송이 안되서 premium 서버에서 발송하도록 수정
//        $res = mail($to,"=?EUC-KR?B?".base64_encode(nl2br($subject))."?=",$mailbody,$header);
        $res = mailx($to,"=?EUC-KR?B?".base64_encode(nl2br($subject))."?=",$mailbody,$header);
        if( $res )
        {
            // 메일 보내기 회수 업데이트
            $query = "update in_req_bill set send_email_cnt = send_email_cnt + 1 where seq = $sheet_seq";
            mysql_query($query, $connect);
        }

        return $res;
    }

    function save_supply_name()
    {
        global $connect, $seq, $memo;
        
        $query = "update in_req_bill set supply_memo='$memo' where seq=$seq";
        mysql_query($query, $connect);
    }

    //###############################
    // 전표 삭제
    //###############################
    function delete_sheet()
    {
        global $connect, $seq;
        
        $query = "select * from in_req_bill where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        debug( "입고요청전표 삭제2 : seq - $data[seq] | title - $data[title] | crdate - $data[crdate] | crtime - $data[crtime] | status - $data[status] | owner - $data[owner] | smstime - $data[smstime] | complete_date - $data[complete_date] | worker - $data[worker] ");

        $query = "delete from in_req_bill where seq=$seq";
        mysql_query($query, $connect);

        $query = "delete from in_req_bill_item where bill_seq=$seq";
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
                    
                    if( $f_name == "product_memo" )
                    {
                        $query_memo = "update products set memo='$f_val' where product_id = '$product_id'";
                        mysql_query($query_memo, $connect);
                    }
                    else
                    {
                        // field 변경
                        if( $f_name == "stockin_qty" )  $f_name = "stockin";
                        else if( $f_name == "stock_memo" )  $f_name = "memo";
                        else if( $f_name == "request_memo" )  $f_name = "req_memo";
                        else if( $f_name == "request_qty_input" )  $f_name = "req_stock";

                        $query_set .= ($query_set ? "," : "") . "$f_name = '$f_val'";
                    }
                }
                
                if( $query_set )
                {
                    $query = "update in_req_bill_item set $query_set where bill_seq=$seq and product_id='$product_id'";
debug("save_stockin : " . $query);
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
        global $connect, $seq, $stockin_type;

        $query = "update in_req_bill_item
                     set stockin = " . ($stockin_type ? "req_stock" : "0") . ",
                         memo = '',
                         req_memo = ''
                   where bill_seq = $seq";
        mysql_query($query, $connect);
    }

    //###############################
    // 상품 추가
    //###############################
    function add_req_stockin()
    {
        global $connect, $seq, $product_id, $req_qty, $stockin_qty;
        
        $val = array();
        
        // 이미 전표에 추가된 상품인지 확인
        $query = "select * from in_req_bill_item where bill_seq=$seq and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 2;
        }
        else
        {
            // 상품정보
            $query = "select b.name    b_name,
                             b.code    b_code,
                             a.name    a_name,
                             a.brand   a_brand,
                             a.options a_options,
                             a.enable_stock a_enable_stock
                        from products a,
                             userinfo b
                       where a.supply_code = b.code and
                             a.product_id = '$product_id'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 현재고
            if( $data[a_enable_stock] )
                $stock = class_stock::get_current_stock( $product_id );
            else
                $stock = 0;
            
            $supply_name  = addslashes( $data[b_name]    );
            $product_name = addslashes( $data[a_name]    );
            $brand        = addslashes( $data[a_brand]   );
            $opions       = addslashes( $data[a_options] );
            
            // 추가
            $query = "insert in_req_bill_item
                         set bill_seq      = $seq,
                             supply_name   = '$supply_name',
                             supply_id     = '$data[b_code]',
                             product_id    = '$product_id',
                             product_name  = '$product_name',
                             brand         = '$brand',
                             options       = '$options',
                             stock         = $stock,
                             not_yet_deliv = 0,
                             stock_sub     = 0,
                             req_stock     = '$req_qty',
                             stockin       = '$stockin_qty'";
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
        global $template, $seq, $connect;
        
        // 이미 완료처리된 전표인지 확인
        $query = "select status, title, supply_code from in_req_bill where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[status] == 2 )
        {
            $val['error'] = 0;
            echo json_encode( $val );
            return;
        }
        
        $val = array();
        $query = "update in_req_bill set status=2, complete_date=now(), worker='$_SESSION[LOGIN_NAME]' where seq=$seq";
        if( mysql_query( $query, $connect ) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
        
        if( $_SESSION[REQ_STOCKIN_AUTO] )
        {
            $obj = new class_stock();

            $query_list = "select * from in_req_bill_item where bill_seq=$seq";
            $result_list = mysql_query($query_list, $connect);
            while( $data_list = mysql_fetch_assoc($result_list) )
            {
                if( $data_list[stockin] != 0 )
                {
                    $info_arr = array(
                        type       => "in",
                        product_id => $data_list[product_id],
                        bad        => 0,
                        location   => 'Def',
                        qty        => $data_list[stockin],
                        memo       => "입고요청전표 자동입고:".$data_list[memo],
                        order_seq  => ""
                    );
                    $obj->set_stock($info_arr);
                }
                
                // 교환대기
        		if( $_SESSION[SUPPLY_RETURN_READY] && ($data_list[return_qty] || $data_list[return_money]) )
        		{
        		    // 교환입고
            		if( $data_list[return_qty] )
            		{
                        $info_arr = array(
                            type       => "retin",
                            product_id => $data_list[product_id],
                            bad        => 0,
                            location   => 'Def',
                            qty        => $data_list[return_qty],
                            memo       => "입고요청전표 교환입고",
                            order_seq  => ""
                        );
                        $obj->set_stock($info_arr);
            		}
            		
            		// 잔처리
            		if( $data_list[return_money] )
            		{
            		    $query = "update userinfo set return_money = return_money + $data_list[return_money] where code=$data[supply_code]";
            		    mysql_query($query, $connect);

                        // 현재잔
                        $query_money = "select return_money from userinfo where code=$data[supply_code]";
                        $result_money = mysql_query($query_money, $connect);
                        $data_money = mysql_fetch_assoc($result_money);
                        
            		    // log
                        $query = "insert supply_money_log
                                     set crdate = now()
                                        ,cruser = '$_SESSION[LOGIN_ID]'
                                        ,supply_code = '$data[supply_code]'
                                        ,work_type = 1
                                        ,work_money = '$data_list[return_money]'
                                        ,supply_money = '$data_money[return_money]'
                                        ,memo = '입고요청전표 : $data[title] [$data_list[product_id]]' ";
                        mysql_query($query, $connect);
            		}

                    // 교환대기수량 조정
                    $query = "update products 
                                 set return_qty = return_qty - $data_list[return_qty] 
                                    ,return_money = return_money - $data_list[return_money]
                               where product_id='$data_list[product_id]' ";
                    mysql_query($query, $connect);

            		class_IT00::return_ready_sheet_log($seq, $data_list[product_id], 1, $data_list[return_qty], $data_list[return_money], "");
            	}
            }
        }
        
        echo json_encode( $val );
    }    

    // url 줄이기
    function make_short_url($url)
    {
        // 구글 url 단축
        if( $_SESSION[WEB_SERVER] == 15 || $_SESSION[WEB_SERVER] == 17 || $_SESSION[WEB_SERVER] == 18 )
            $short_url = "";
        else if( $_SESSION[WEB_SERVER] == 20 )
            $short_url = shortenUrl_pimz($url);
        else
            $short_url = shortenUrl($url);
        
        // 구글 url 단축 실패하면, 네이버 api 적용
        if( !trim($short_url) )
            $short_url = shortenUrl_naver($url);

        // 네이버도 실패하면 그대로 보냄
        if( !trim($short_url) )
            $short_url = $url;

        return $short_url;
    }

	function IM16()
	{
		global $template, $connect;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}

	function new_sheet_each()
	{
		global $template, $connect;
		global $sheet_title, $sheet_memo;
		global $str_supply_code, $multi_supply;

	    $supply_arr = array();
	    if( $str_supply_code )
	        $supply_arr[] = $str_supply_code;
	    else
	        $supply_arr = explode(",", $multi_supply);
	    
	    foreach($supply_arr as $supply)
	    {
	        $new_title = $sheet_title . "_" . class_supply::get_name($supply);

            $query1 = "insert in_req_bill 
                          set crdate=Now()
                             ,crtime=Now()
                             ,title = '" . $new_title . "'
                             ,status = 1
                             ,supply_code = $supply
                             ,owner = '" . $_SESSION[LOGIN_NAME] . "'";
            mysql_query($query1, $connect);
	    }
	}
	
}
?>
