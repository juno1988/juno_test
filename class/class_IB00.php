<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_ui.php";
require_once "zip.lib.php";
require_once "Classes/PHPExcel.php";


//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_IB00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고부족요청/조회
    function IB00()
    {
        global $template, $start_date, $connect;


        if( !$start_date )  $start_date = date('Y-m-d', strtotime("-$_SESSION[STOCK_PERIOD_DAY] day"));
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 입고요청전표
    function IB01()
    {
        global $template, $connect, $start_date, $end_date,$status, $title, $page;

        // 작업중
        $this->show_wait();
        
        $par_arr = array("template","start_date","end_date","title","status","page");
        $link_url_list = $this->build_link_par($par_arr);     

        if( !$start_date )
        {
            if ( _DOMAIN_ == "voiceone" || _DOMAIN_ == "freestyle" || _DOMAIN_ == "iboss" || _DOMAIN_ == "plays" || _DOMAIN_ == "buyclub" )
        	    $start_date = date('Y-m-d');
            else if ( $_SESSION[USE_EACH_SUPPLY] )
        	    $start_date = date('Y-m-d', strtotime("-1 day"));
            else
        	    $start_date = date('Y-m-d', strtotime("-14 day"));
    	}

        if( !$end_date )
        {
            if ( $_SESSION[USE_EACH_SUPPLY] && _DOMAIN_ != "voiceone" && _DOMAIN_ != "freestyle" && _DOMAIN_ != "iboss" && _DOMAIN_ != "plays" && _DOMAIN_ != "buyclub")
        	    $end_date = date('Y-m-d', strtotime("-1 day"));
            else
        	    $end_date = date('Y-m-d');
    	}

        // 검색 
        $query = "select * from in_req_bill where crdate>='$start_date' and crdate<='$end_date' ";
        
        if ( $status )
            $query .= " and status=$status ";
        
        if ( $title )
            $query .= " and title like '%$title%'";

        $result = mysql_query( $query, $connect );
        $total_rows = mysql_num_rows($result);

        if ( $_SESSION[USE_EACH_SUPPLY] )
            $query .= " order by title";
        else
            $query .= " order by seq desc";
            
        $result = mysql_query( $query, $connect );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //
    // 
    function IB07()
    {
        global $template, $connect, $seq, $sort, $dir;
        
        $arr_result = $this->get_requestin_conf();
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";   
    }
    
    //
    // 입고 상세..
    function IB08()
    {
        global $template, $connect, $product_id;
        
        // 입고 요청..
        
        // 입고 수량
      
        // 초기화...아직 없음.
        $start_date = date("Y-m-d", strtotime("-" . $arr_conf[request_term] . " days" ));
        
        // 입고요청개수
        $query = "select crdate, sum(req_stock) qty 
                    from in_req_bill a, in_req_bill_item b 
                   where a.seq        = b.bill_seq
                     and a.crdate    >= '$start_date'
                     and b.product_id = '$product_id'
                     group by crdate order by crdate
                     ";
        
        $result = mysql_query( $query, $connect );
        
        $arr_result = array();
        
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_result[ $data[crdate] ][req_stock] = $data[qty] ? $data[qty] : 0;   
            $arr_result[ $data[crdate] ][in_stock]  = 0;
        }
        
        // 입고개수
        // 환경설정에서 입고요청개수 - 입고개수를 on으로 해 놓았을 경우에만 처리 함..
        $query = "select crdate, sum(stockin) qty 
                    from stock_tx 
                  where crdate >= '$start_date' and product_id='$product_id'
                  group by crdate order by crdate";    
            
        $result = mysql_query( $query, $connect );
        
        while ( $_data   = mysql_fetch_assoc( $result ) )
        {
            $arr_result[ $_data[crdate] ][in_stock]  = $_data[qty] ? $_data[qty] : 0;
            $arr_result[ $_data[crdate] ][req_stock] = $arr_result[ $_data[crdate] ][req_stock] ? $arr_result[ $_data[crdate] ][req_stock] : 0;   
        }
        
		ksort( $arr_result );       
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";   
    }
    
    //////////////////////////////////////////////////////
    // 입고요청전표상세
    function IB02()
    {
        global $template, $connect, $seq, $sort, $dir;
        
        // 전표정보
        $query_sheet   = "select * from in_req_bill where seq=$seq";
        $result_sheet = mysql_query( $query_sheet, $connect );
        $data_sheet   = mysql_fetch_assoc( $result_sheet );

        $link_url = "?" . $this->build_link_url();

        // 검색 
        $query = "select c.name as supply_name,
                         a.product_id as product_id,
                         b.name as product_name,
                         b.options as options,
                         b.brand as brand,
                         b.supply_options as supply_options,
                         a.stock as stock,
                         a.not_yet_deliv as not_yet_deliv,
                         a.req_stock as req_stock,
                         a.stockin as stockin,
                         a.memo as memo,
                         b.memo as pmemo
                    from in_req_bill_item a, products b, userinfo c 
                   where a.bill_seq=$seq and
                         a.product_id=b.product_id and
                         b.supply_code=c.code";
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);                 

        if( $sort )
        {
            $query .= " order by $sort ";
            if( $dir )
            {
                $query .= " desc ";
                $dir = 0;
            }
            else
                $dir = 1;
            $query .= ", c.name, b.name, b.options";
        }
        else
            $query .= " order by c.name, b.name, b.options";
 
debug("입고요청전표상세:$query");   
        $result = mysql_query( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////////////
    // 상품추가
    function IB03()
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
debug("상품추가:$query");            
            $result = mysql_query($query, $connect);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function set_session()
    {
        global $connect, $value;
        
        $_SESSION["PACK_P_O"] = $value;  
        
        $query = "update ez_config set pack_p_o='" . ($value=='true' ? 'true' : '') . "'";
        mysql_query($query, $connect);
        
        echo $_SESSION["PACK_P_O"];
    }
    
    //*************************************
    // 재고부족요청/조회 검색
    function search()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock, $order_status,
               $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
		global $multi_supply_group, $multi_supply, $str_supply_code;
        
        // 공통 모듈에서 data 가져온다.
        $total_rows = 0;
        $_arr  = $this->get_list( &$total_rows );
        $_json = json_encode( $_arr );
        
        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
        </script>
        ";
    }   

    //*************************************
    // 입고요청 작업 완료
    function set_complate()
    {
        global $template, $seq, $connect, $stockin_str;
        
        // 이미 완료처리된 전표인지 확인
        $query = "select status from in_req_bill where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[status] == 2 )
        {
            $val['error'] = 0;
            echo json_encode( $val );
            return;
        }
        
        // 입고수량 변경
        $stockin_arr = explode(",", substr($stockin_str,0,-1));
        foreach($stockin_arr as $s)
        {
            $ss = explode(":",$s);
            $query = "update in_req_bill_item set stockin=$ss[1] where bill_seq=$seq and product_id='$ss[0]'";
            mysql_query($query, $connect);
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
                if( $data_list[stockin] == 0 )  continue;
                
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
        }
        
        echo json_encode( $val );
    }    

    //*************************************
    // save bill
    function save_bill()
    {
        global $connect, $title;
        
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
        
        $arr_result = array( seq => $data[a] );
        
        echo json_encode( $arr_result );
    }

    // 
    function save_bill_item()
    {
        global $connect, $bill_seq, $start_date, $end_date, $order_status;

        $arr_request = array();
        foreach ( $_REQUEST as $key=>$val )
        {   
            if ( preg_match ("/req/", $key ))
            {
                list( $req, $product_id ) = split("_", $key );   
                $arr_request[$product_id] = $val;
            }
        }

        foreach ( $arr_request as $product_id => $qty )
        {
            $info = class_product::get_info( $product_id );

            if( $info[enable_stock] )
                $stock = class_stock::get_current_stock( $product_id );
            else
                $stock = 0;
            
            // 미배송 구하기
            $query = "select sum(b.qty) sum
                        from orders a,
                             order_products b
                       where a.seq = b.order_seq and ";

            if( $order_status == 1 )
                $query .= "  a.status = 1 and ";
            else if( $order_status == 2 )
                $query .= "  a.status = 7 and ";
            else
                $query .= "  a.status in (1,7) and ";

            if( $start_date )
                $query .= " a.collect_date>='$start_date' and ";
                
            if( $end_date )
                $query .= " a.collect_date<='$end_date' and ";
                
            $query .= "      b.order_cs not in (1,2) and
                             b.product_id='$product_id'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $trans_ready = $data[sum];
            $stock_sub = $stock - $trans_ready;

            $query = "insert into in_req_bill_item 
                         set bill_seq = $bill_seq
                             ,supply_id = $info[supply_code]
                             ,supply_name = '" . addslashes( class_supply::get_name($info[supply_code]) ) . "'
                             ,product_id = '$product_id'
                             ,product_name = '" . addslashes($info[name]) . "'
                             ,brand        = '" . addslashes($info[brand]) . "'
                             ,options      = '" . addslashes($info[options]) . "'
                             ,stock        = '$stock'
                             ,not_yet_deliv = '$trans_ready'
                             ,stock_sub     = '$stock_sub'
                             ,req_stock     = '$qty'
                             ,stockin       = '$qty'";   

            mysql_query( $query, $connect );
        }
        
    }
    
    function save_bill_each()
    {
        global $connect, $title;

        // request qty
        $arrReq = array();
        foreach ( $_REQUEST as $key=>$val )
        {
            // 수량이 0 이면 볼것도 없이 넘어감
            if( $val == 0 )  continue;
            
            if( substr($key,0,4)=="req_" )
            {
                list( $req, $product_id ) = explode("_", $key );   
                
                // 공급처
                $query = "select supply_code from products where product_id='$product_id'";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);

                $arrReq[$data[supply_code]][] = array( 
                    "product_id"  => $product_id,
                    "qty"         => $val
                );
            }
        }

        // 공급처별 전표 생성
        foreach( $arrReq as $key => $val )
        {
            // 공급처명
            $query = "select name, email from userinfo where code=$key"; 
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $supply_name = $data[name];
            $supply_email = $data[email];
            
            // 전표 만들기
            $query1 = "insert into in_req_bill 
                         set crdate=Now()
                             ,crtime=Now()
                             ,title = '" . addslashes($title . "_" . $supply_name) . "'
                             ,status = 1
                             ,supply_code = $key
                             ,owner = '" . $_SESSION[LOGIN_NAME] . "'";
debug("전표생성 : " . $query1 );
            mysql_query( $query1, $connect );
            
            // 입력된 seq를 찾는다
            $query = "select last_insert_id() a from in_req_bill;";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $bill_seq = $data[a];

            // 전표에 각 상품을 넣는다.
            foreach( $val as $p_info )
            {
                $product_id = $p_info[product_id];
                $qty = $p_info[qty];
                
                // 상품정보
                $query = "select * from products where product_id='$product_id'";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);

                $product_name = $data[name];
                $brand = $data[brand];
                $options = $data[options];
                
                // 재고정보
                $query = "select * from current_stock where product_id='$product_id'";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);
                $stock = $data[stock];
                
                // 미배송수량
                $query_sum = "select sum(b.qty) sum
                                from orders a,
                                     order_products b
                               where a.seq = b.order_seq and
                                     a.status in (1,7) and
                                     b.order_cs not in (1,2) and
                                     b.product_id='$product_id'";
                $result_sum = mysql_query( $query_sum, $connect );
                $data_sum = mysql_fetch_assoc($result_sum);
                $qty_ready = $data_sum[sum];
                
                $sub = $qty_ready - $stock;
                
                $query = "insert into in_req_bill_item 
                             set bill_seq       = $bill_seq
                                 ,supply_id     = $key
                                 ,supply_name   = '" . addslashes($supply_name) . "'
                                 ,product_id    = '$product_id'
                                 ,product_name  = '" . addslashes($product_name) . "'
                                 ,brand         = '" . addslashes($brand) . "'
                                 ,options       = '" . addslashes($options) . "'
                                 ,stock         = '$stock'
                                 ,not_yet_deliv = '$qty_ready'
                                 ,stock_sub     = '$sub'
                                 ,req_stock     = '$qty'
                                 ,stockin       = '$qty'";   
debug("전표 상품등록 : " . $query );
                mysql_query( $query, $connect );
            }

        }
        
        echo "<script type='text/javascript'>alert('전표를 저장하였습니다.')</script>";
    }
    
    function send_sheet_email($fn)
    {
        global $mail_from, $mail_to, $mail_title, $mail_content;
        
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
//        return mail($to,"=?EUC-KR?B?".base64_encode(nl2br($subject))."?=",$mailbody,$header);
        return mailx($to,"=?EUC-KR?B?".base64_encode(nl2br($subject))."?=",$mailbody,$header);
    }

    function get_list( &$total_rows, $is_download=0 )
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $page, 
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type,
               $is_stock,$is_all, $except_soldout, $products_sort, $stock_option, $nostock_option,$stock_alarm, $order_status;
        global $str_supply_code, $enable_stock_type, $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $s_group_id;
        global $multi_supply_group, $multi_supply, $str_supply_code;
        
        
        $arr_request = array();
        foreach ( $_REQUEST as $key=>$val )
        {   
            if ( preg_match ("/req/", $key ))
            {
                list( $req, $pid ) = split("_", $key );   
                $arr_request[$pid] = $val;
            }
        }
                       
        $query = "select c.name supply_name,
                         c.code supply_code,
                         c.tel supply_tel,
                         c.mobile supply_mobile,
                         c.address1 supply_addr1,
                         c.address2 supply_addr2,
                         c.account_number account_number,
                         c.admin admin,
                         d.product_id product_id,
                         d.barcode,
                         d.name product_name,
                         d.options options,
                         d.brand brand,
                         d.supply_options supply_options,
                         d.org_price org_price,
                         d.enable_sale enable_sale,
                         d.enable_stock enable_stock,
                         d.location location,
                         d.stock_alarm1 d_stock_alarm1,
                         sum(b.qty) sum,
                         ifnull(e.reg_date,d.reg_date) reg_date ";

        // 재고 있는/없는 다른 옵션상품 같이보기
        if( $stock_option || $nostock_option )
            $query .= ", ifnull(e.product_id,d.product_id) org_product_id, e.is_represent is_rep ";

        $query .= " from orders a,
                         order_products b,
                         userinfo c,
                         products d left outer join 
                         products e on d.org_id=e.product_id
                   where a.seq=b.order_seq and
                         b.product_id=d.product_id and
                         d.supply_code=c.code and ";

        // 미배송 상태
        if( $order_status == 1 )
            $query .=  " a.status = 1 and ";
        else if( $order_status == 2 )
            $query .=  " a.status = 7 and ";
        else
            $query .=  " a.status in (1,7) and ";

        if( $_SESSION[USE_STOCK_PERIOD] )
        {
            $query .= "  a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and";
        }
        $query .= "      b.order_cs not in (1,2,3,4) and
                         d.is_delete=0 and
                         d.is_represent=0 ";
                         
        if( $enable_stock_type == 0 )
            $query .= " and d.enable_stock=1";
        else if( $enable_stock_type == 1 )
            $query .= " and d.enable_stock=0";

        if ( $product_id )
            $query .= " and b.product_id = '$product_id'";
           
        if( $name )
            $query .= " and d.name like '%$name%'";
        
        if ( $options )
            $query .= " and d.options like '%$options%'";

        // 카테고리
        if( $category )
            $query .= " and d.category = '$category' ";

        // 멀티 카테고리
        if( $m_sub_category_1 )
            $query .= " and d.m_category1 = '$m_sub_category_1' ";
        if( $m_sub_category_2 )
            $query .= " and d.m_category2 = '$m_sub_category_2' ";
        if( $m_sub_category_3 )
            $query .= " and d.m_category3 = '$m_sub_category_3' ";

        if( $str_supply_code )
            $query .= " and d.supply_code in ( $str_supply_code ) ";
		if($multi_supply)
			$query .= " and d.supply_code in ( $multi_supply ) ";

        if( $except_soldout )
            $query .= " and d.enable_sale=1";
            
        // 재고 있는 다른 옵션상품 같이보기
        if( $stock_option || $nostock_option )
            $query .= " group by org_product_id ";
        else
            $query .= " group by b.product_id ";
        
        // 전체 상품수
        $result = mysql_query($query, $connect);
        $total_products = mysql_num_rows($result);
        
        // order by
        if( _DOMAIN_ == 'ilovej' )
            $sort_c_options = "d.product_id";
        else
            $sort_c_options = "d.options";

        if ( $products_sort == 1 ) // 상품명
            $query .= " order by d.name, $sort_c_options ";
        if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= " order by c.name, d.name, $sort_c_options";
        if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= " order by reg_date, d.name, $sort_c_options";
        if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= " order by reg_date, c.name, d.name, $sort_c_options";
        if ( $products_sort == 5 )  // 공급처주소 > 상품명
            $query .= " order by c.address1, d.name, $sort_c_options";

        debug( "재고부족 : " . $query );
        
        $i = 0;
        $_arr   = array();
        $total_rows = 0;
        
        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $enable_org_price = true;
        else
            $enable_org_price = false;        

        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {   
            // 재고 있는 다른 옵션상품 같이보기 & 대표상품
            if( ($stock_option || $nostock_option) && $data[is_rep] )
            {
                $arr_temp = array();
                $stock_flag = false;
                
                $query_opt = "select product_id, 
                                     barcode,
                                     options, 
                                     supply_options, 
                                     enable_sale,
                                     enable_stock,
                                     stock_alarm1
                                from products 
                               where org_id='$data[org_product_id]' and
                                     is_delete = 0 and
                                     is_represent = 0 ";

                if( $enable_stock_type == 0 )
                    $query_opt .= " and enable_stock=1";
                else if( $enable_stock_type == 1 )
                    $query_opt .= " and enable_stock=0";

                if ( $options )
                    $query_opt .= " and options like '%$options%'";
                if( $except_soldout )
                    $query_opt .= " and enable_sale=1";
                
                if( _DOMAIN_ == 'ilovej' )
                    $query_opt .= " order by product_id";
                else
                    $query_opt .= " order by options";
                $result_opt = mysql_query($query_opt, $connect);
                while( $data_opt = mysql_fetch_assoc($result_opt) )
                {
                    $query_sum = "select sum(b.qty) sum
                                    from orders a,
                                         order_products b
                                   where a.seq = b.order_seq and ";

                    if( $order_status == 1 )
                        $query_sum .= "  a.status = 1 and ";
                    else if( $order_status == 2 )
                        $query_sum .= "  a.status = 7 and ";
                    else
                        $query_sum .= "  a.status in (1,7) and ";
                    
                    $query_sum .= "      b.order_cs not in (1,2) and
                                         b.product_id='$data_opt[product_id]'
                                group by b.product_id";
                    $result_sum = mysql_query( $query_sum, $connect );
                    $data_sum = mysql_fetch_assoc($result_sum);
                    if( $data_sum )
                        $sum = $data_sum[sum];
                    else
                        $sum = 0;
                        
                    if( $data_opt[enable_stock] )
                        $_current_stock = class_stock::get_current_stock( $data_opt[product_id] );
                    else
                        $_current_stock = 0;

                    $stock_sub = $sum - $_current_stock;
                    if( $stock_alarm )  $stock_sub += $data_opt[stock_alarm1];
                    $stock_sub = ( $stock_sub > 0 ? $stock_sub : 0 );

                    // 주문수량 0이고, 재고없는 옵션 포함 아니고, 재고 없으면 넘어감
                    if( $sum == 0 && !$nostock_option && $_current_stock == 0 )  continue;
                    
                    // 주문수량 0이고, 재고있는 옵션 포함 아니고, 재고 있으면 넘어감
                    if( $sum == 0 && !$stock_option && $_current_stock != 0 )  continue;
                    
                    // 다운로드 또는 전체 또는 부족수량 있을 경우 해당상품 포함
                    if( $is_all || $stock_sub > 0 )  $stock_flag = true;
                    
                    if ( $_SESSION["PACK_P_O"] == "true" )
                    {
                        $arr_temp[] = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data_opt[product_id]
                            ,barcode         => $data_opt[barcode]
                            ,product_name    => $data[product_name] . " " . $data_opt[options]
                            ,brand           => $data[brand]
                            ,brand_options   => $data_opt[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                            ,stock           => ($_current_stock ? $_current_stock : 0)
                            ,trans_ready     => ($sum ? $sum : "")
                            ,stock_sub       => $stock_sub
                            ,enable_sale     => ($data_opt[enable_sale] ? "" : "품절")
                            ,collect_date    => ""
                            ,location        => $data[location]
                            ,req_qty         => $arr_request[$data_opt[product_id]]
                            ,stock_alarm     => $data_opt[stock_alarm1]
                            ,account_number  => $data[account_number]
                            ,admin           => $data[admin]
                        );    
                    }
                    else
                    {
                        if( _DOMAIN_ == 'vishop77' )
                        {
                            $arr_temp[] = array( 
                                supply_name      => $data[supply_name]
                                ,supply_id       => $data[supply_code]
                                ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                                ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                                ,product_id      => $data_opt[product_id]
                                ,barcode         => $data_opt[barcode]
                                ,product_name    => $data[product_name]
                                ,brand           => $data[brand]
                                ,options         => $data_opt[options]
                                ,brand_options   => $data_opt[supply_options]
                                ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                                ,stock           => ($_current_stock ? $_current_stock : 0)
                                ,trans_ready     => ($sum ? $sum : "")
                                ,stock_sub       => $stock_sub
                                ,enable_sale     => ($data_opt[enable_sale] ? "" : "품절")
                                ,collect_date    => ""
                                ,location        => $data[location]
                                ,req_qty         => $arr_request[$data_opt[product_id]]
                                ,stock_alarm     => $data_opt[stock_alarm1]
                                ,account_number  => $data[account_number]
                                ,admin           => $data[admin]
                            );
                        }
                        else if( _DOMAIN_ == 'secretbb' )
                        {
                            $arr_temp[] = array( 
                                supply_name      => $data[supply_name]
                                ,supply_id       => $data[supply_code]
                                ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                                ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                                ,product_id      => $data_opt[product_id]
                                ,barcode         => $data_opt[barcode]
                                ,brand           => $data[brand]
                                ,product_name    => $data[product_name]
                                ,options         => $data_opt[options]
                                ,brand_options   => $data_opt[supply_options]
                                ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                                ,stock           => ($_current_stock ? $_current_stock : 0)
                                ,trans_ready     => ($sum ? $sum : "")
                                ,stock_sub       => $stock_sub
                                ,enable_sale     => ($data_opt[enable_sale] ? "" : "품절")
                                ,collect_date    => ""
                                ,location        => $data[location]
                                ,req_qty         => $arr_request[$data_opt[product_id]]
                                ,stock_alarm     => $data_opt[stock_alarm1]
                                ,account_number  => $data[account_number]
                                ,admin           => $data[admin]
                            );
                        }
                        else if( _DOMAIN_ == 'justone' )
                        {
                            // bad return 구하기
                            $month_ago = date('Y-m-d', strtotime("-1 week"));
                            $br_seq_list = "";
                            $query_br = "select seq from sheet_out where crdate>'$month_ago 00:00:00' ";
                            $result_br = mysql_query($query_br, $connect);
                            while( $data_br = mysql_fetch_assoc($result_br) )
                                $br_seq_list .= ($br_seq_list ? "," : "") . $data_br[seq];
                                
                            $query_br_tx = "select sum(qty) sum_qty from stock_tx_history where job='out' and sheet in ($br_seq_list) group by product_id";
                            $result_br_tx = mysql_query($query_br_tx, $connect);
                            $data_br_tx = mysql_fetch_assoc($result_br_tx);
                            
                            $arr_temp[] = array( 
                                supply_name      => $data[supply_name]
                                ,supply_id       => $data[supply_code]
                                ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                                ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                                ,product_id      => $data_opt[product_id]
                                ,barcode         => $data_opt[barcode]
                                ,product_name    => $data[product_name]
                                ,options         => $data_opt[options]
                                ,brand           => $data[brand]
                                ,brand_options   => $data_opt[supply_options]
                                ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                                ,stock           => ($_current_stock ? $_current_stock : 0)
                                ,trans_ready     => ($sum ? $sum : "")
                                ,stock_sub       => $stock_sub
                                ,enable_sale     => ($data_opt[enable_sale] ? "" : "품절")
                                ,collect_date    => ""
                                ,location        => $data[location]
                                ,req_qty         => $arr_request[$data_opt[product_id]]
                                ,stock_alarm     => $data_opt[stock_alarm1]
                                ,account_number  => $data[account_number]
                                ,admin           => $data[admin]
                                ,bad_return      => $data_br_tx[sum_qty]
                            );
                        }
                        else
                        {
                            $arr_temp[] = array( 
                                supply_name      => $data[supply_name]
                                ,supply_id       => $data[supply_code]
                                ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                                ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                                ,product_id      => $data_opt[product_id]
                                ,barcode         => $data_opt[barcode]
                                ,product_name    => $data[product_name]
                                ,options         => $data_opt[options]
                                ,brand           => $data[brand]
                                ,brand_options   => $data_opt[supply_options]
                                ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                                ,stock           => ($_current_stock ? $_current_stock : 0)
                                ,trans_ready     => ($sum ? $sum : "")
                                ,stock_sub       => $stock_sub
                                ,enable_sale     => ($data_opt[enable_sale] ? "" : "품절")
                                ,collect_date    => ""
                                ,location        => $data[location]
                                ,req_qty         => $arr_request[$data_opt[product_id]]
                                ,stock_alarm     => $data_opt[stock_alarm1]
                                ,account_number  => $data[account_number]
                                ,admin           => $data[admin]
                            );
                        }
                    }
                }

                if( $stock_flag )
                {
                    foreach( $arr_temp as $temp_data )
                    {
                        $total_rows++;
                        $_arr['list'][] = $temp_data;
                    }
                }

            }
            // 일반
            else
            {
                if( $data[enable_stock] )
                    $_current_stock = class_stock::get_current_stock( $data[product_id] );
                else
                    $_current_stock = 0;
                
                $stock_sub = $data[sum] - $_current_stock;
                if( $stock_alarm )  $stock_sub += $data[d_stock_alarm1];
                $stock_sub = ( $stock_sub > 0 ? $stock_sub : 0 );

                // 전체 아니면 0 제외  
                if ( !$is_all && $stock_sub == 0 )  continue;
            
                $total_rows++;
                
                // 상품과 옵션을 한 줄에 표시...
                if ( $_SESSION["PACK_P_O"] == "true" )
                {
                    $_arr['list'][] = array( 
                        supply_name      => $data[supply_name]
                        ,supply_id       => $data[supply_code]
                        ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                        ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                        ,product_id      => $data[product_id]
                        ,barcode         => $data[barcode]
                        ,product_name    => $data[product_name]. $data[options]
                        ,brand           => $data[brand]
                        ,brand_options   => $data[supply_options]
                        ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                        ,stock           => ($_current_stock ? $_current_stock : 0)
                        ,trans_ready     => ($data[sum] ? $data[sum] : "")
                        ,stock_sub       => $stock_sub
                        ,enable_sale     => ($data[enable_sale] ? "" : "품절")
                        ,collect_date    => ""
                        ,location        => $data[location]
                        ,stock_alarm     => $data[d_stock_alarm1]
                        ,account_number  => $data[account_number]
                        ,admin           => $data[admin]
                    );
                }
                else
                {
                    if( _DOMAIN_ == 'vishop77' )
                    {
                        $_arr['list'][] = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,product_name    => $data[product_name]
                            ,brand           => $data[brand]
                            ,options         => $data[options]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                            ,stock           => ($_current_stock ? $_current_stock : 0)
                            ,trans_ready     => ($data[sum] ? $data[sum] : "")
                            ,stock_sub       => $stock_sub
                            ,enable_sale     => ($data[enable_sale] ? "" : "품절")
                            ,collect_date    => ""
                            ,location        => $data[location]
                            ,stock_alarm     => $data[d_stock_alarm1]
                            ,account_number  => $data[account_number]
                            ,admin           => $data[admin]
                        );  
                    }
                    else if( _DOMAIN_ == 'secretbb' )
                    {
                        $_arr['list'][] = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,brand           => $data[brand]
                            ,product_name    => $data[product_name]
                            ,options         => $data[options]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                            ,stock           => ($_current_stock ? $_current_stock : 0)
                            ,trans_ready     => ($data[sum] ? $data[sum] : "")          // 미배송
                            ,stock_sub       => $stock_sub                              // 요청수량
                            ,enable_sale     => ($data[enable_sale] ? "" : "품절")      // 품절
                            ,collect_date    => ""                                      // 발주일
                            ,location        => $data[location]
                            ,stock_alarm     => $data[d_stock_alarm1]
                            ,account_number  => $data[account_number]
                            ,admin           => $data[admin]
                        );  
                    }
                    else if( _DOMAIN_ == 'justone' )
                    {
                        // bad return 구하기
                        $month_ago = date('Y-m-d', strtotime("-1 month"));
                        $br_seq_list = "";
                        $query_br = "select seq from sheet_out where crdate>'$month_ago 00:00:00' ";
debug("bad return 구하기1 : " . $query_br);
                        $result_br = mysql_query($query_br, $connect);
                        while( $data_br = mysql_fetch_assoc($result_br) )
                            $br_seq_list .= ($br_seq_list ? "," : "") . $data_br[seq];
                            
                        $query_br_tx = "select sum(qty) sum_qty 
                                          from stock_tx_history 
                                         where product_id = '$data[product_id]' and 
                                               job = 'out' and
                                               sheet in ($br_seq_list) 
                                      group by product_id";
debug("bad return 구하기2 : " . $query_br_tx);
                        $result_br_tx = mysql_query($query_br_tx, $connect);
                        $data_br_tx = mysql_fetch_assoc($result_br_tx);

                        $_arr['list'][] = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,product_name    => $data[product_name]
                            ,options         => $data[options]
                            ,brand           => $data[brand]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                            ,stock           => ($_current_stock ? $_current_stock : 0)
                            ,trans_ready     => ($data[sum] ? $data[sum] : "")          // 미배송
                            ,stock_sub       => $stock_sub                              // 요청수량
                            ,enable_sale     => ($data[enable_sale] ? "" : "품절")      // 품절
                            ,collect_date    => ""                                      // 발주일
                            ,location        => $data[location]
                            ,stock_alarm     => $data[d_stock_alarm1]
                            ,account_number  => $data[account_number]
                            ,admin           => $data[admin]
                            ,bad_return      => ( $data_br_tx[sum_qty] > 0 ? $data_br_tx[sum_qty] : 0 )
                        );  
                    }
                    else
                    {
                        $_arr['list'][] = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,product_name    => $data[product_name]
                            ,options         => $data[options]
                            ,brand           => $data[brand]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0 )
                            ,stock           => ($_current_stock ? $_current_stock : 0)
                            ,trans_ready     => ($data[sum] ? $data[sum] : "")          // 미배송
                            ,stock_sub       => $stock_sub                              // 요청수량
                            ,enable_sale     => ($data[enable_sale] ? "" : "품절")      // 품절
                            ,collect_date    => ""                                      // 발주일
                            ,location        => $data[location]
                            ,stock_alarm     => $data[d_stock_alarm1]
                            ,account_number  => $data[account_number]
                            ,admin           => $data[admin]
                        );  
                    }
                }
            
            }
            $i++;
            
            if( $stock_option || $nostock_option )
            {
                echo "<script language='javascript'>parent.show_txt('" . $i . " / " . $total_products . "')</script>";
                flush();
            }
        }
        $_arr['total'] = $total_rows;
        $_arr['stock_alarm'] = $stock_alarm;

        // wizi 정렬 순서를 부족재고 순서로
        if( _DOMAIN_ == 'wizi' )
        {
            $temp_arr = array();
            $new_arr = array();
            
            $cnt = count($_arr['list']);
            for( $j=0; $j<$cnt; $j++)
                $temp_arr[$j] = $_arr['list'][$j][stock_sub];

            arsort($temp_arr);
            
            foreach($temp_arr as $key => $val)
                $new_arr[] = $_arr['list'][$key];

            $_arr['list'] = $new_arr;
        }
        
        //
        // 미입고요청 수량 조회...
        // 2012.1.12 - jkryu
        $arr_conf = $this->get_requestin_conf();
        for( $i=0; $i < count($_arr['list']); $i++ )
        {
            // 입고요청
            $_arr['list'][$i]['request_input'] = $this->get_request_input( $_arr['list'][$i]['product_id'],$arr_conf );
            
            // 요청수량 재 계산..
            $_arr['list'][$i]['not_deliv'] = $_arr['list'][$i]['trans_ready'];
            
            // 요청수량..
            $_arr['list'][$i]['stock_sub'] = $this->recalc_stock_sub( $_arr['list'][$i], $arr_conf);
        }
        
        return $_arr;
    }
    
    // 요청수량 재계산...
    function recalc_stock_sub( $dd, $arr_conf)
    {
        foreach( $arr_conf['request_rule_arr'] as $a )
        {   
            if ( is_numeric($dd[ $a ]) ) 
                $str_rule .= $dd[$a];
            else
                $str_rule .= $a;
        }
        // return $str_rule;
        // $str_rule = 0;
        // debug( " recalc_stock_sub: $str_rule " );
        
        eval( "\$stock_sub = " . $str_rule . ";" );
        
        return $stock_sub > 0 ? $stock_sub : 0;
    }
    
    //
    // 미 입고 환경설정 가져오기
    // 2012.1.12 - jkryu
    // ezauto_config에 저장을 한다..
    // request_min_input : 입고시 요청수량 차감 => 기본값 1 ( 입고시 요청 차감 )
    // request_term      : 입고요청 기간        => 기본값 10일
    // request_rule      : 입고요청 룰..        => [미배송]-[현재고]-[입고요청수량]
    //                                             not_deliv,-,current_stock/stock_alarm,-,request_input
    function get_requestin_conf()
    {
        global $connect;
        
        $query = "select * from ezauto_config where code in ( 'request_min_input', 'request_term', 'request_rule')";
        $result = mysql_query( $query, $connect );
        
        // 초기화
        $arr_return = array( "request_min_input" => 1, "request_term" => 10 );
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_return[$data[code]] = $data[value] ? $data[value] : $data[str_value];
        }
        
        //print_r ( $arr_return );
        //echo "<br>----<br>";
        
        $arr_return['request_min_input']   = $arr_return['request_min_input'] === 0 || $arr_return['request_min_input'] == "" ? 0 : 1;
        $arr_return['request_term']        = $arr_return['request_term']      != "" ? $arr_return['request_term']      : 10;
        $arr_return['request_rule']        = $arr_return['request_rule']      != "" ? $arr_return['request_rule']      : "stock_sub";
        $arr_return['request_rule_arr']    = split(",",$arr_return['request_rule']);
        $arr_return['request_rule']        = str_replace(",","",$arr_return['request_rule']);
        
        return $arr_return;
    }
    
    function save_config()
    {
        global $connect, $request_min_input, $request_term, $rule0, $rule1, $rule2,$rule3,$rule4;
        
        $this->save_config_action( "request_min_input", $request_min_input );
        
        $this->save_config_action( "request_term", $request_term );
        
        $this->save_config_action( "request_rule", "$rule0,$rule1,$rule2,$rule3,$rule4" );
        
        $this->redirect("?template=IB07");   
    }
    
    function save_config_action( $code, $value )
    {
        global $connect;
        
        $query = "select * from ezauto_config where code = '$code'";
        $result = mysql_query( $query, $connect );
        
        if ( mysql_num_rows( $result ) > 0 )
        {
            $query = "update ezauto_config set value='$value',str_value='$value' where code='$code'";   
        }   
        else
        {
            $query = "insert into ezauto_config set value='$value', str_value='$value',code='$code'";   
        }
        
        //echo ( "save_config_action: " . $query . "<br>");
        mysql_query( $query, $connect );
        
    }
    
    // 
    // 미입고요청 개수 가져오기..
    // 2012.1.12 - jkryu
    function get_request_input( $product_id, $arr_conf )
    {
        global $connect;
        
        $start_date = date("Y-m-d", strtotime("-" . $arr_conf[request_term] . " days" ));
        
        // 입고요청개수
        $query = "select sum(req_stock) qty 
                    from in_req_bill a, in_req_bill_item b 
                   where a.seq        = b.bill_seq
                     and a.crdate    >= '$start_date'
                     and b.product_id = '$product_id'";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 입고개수
        // 환경설정에서 입고요청개수 - 입고개수를 on으로 해 놓았을 경우에만 처리 함..
        if ( $arr_conf[request_min_input] == 1 )
        {
            $query = "select sum(stockin) qty from stock_tx where crdate >= '$start_date' and product_id='$product_id'";    
            
            $result = mysql_query( $query, $connect );
            $_data   = mysql_fetch_assoc( $result );
            
            $data[qty] = $data[qty] - ($_data[qty] ? $_data[qty] : 0);
        }
        
        // 음수일 경우 0 으로 reset
        return $data[qty] ? ($data[qty] < 0 ? 0 : $data[qty]) : 0;
        //return $data[qty] ? $data[qty] : 0;
    }
    
    // 입고 전표 내용..
    function get_reqin_info( $bill_seq )
    {
        global $connect;
        
        $arr_result = array();
        
        // 본상품 코드 
        $arr_org = array();
        
        $query = "select * from in_req_bill_item where bill_seq=$bill_seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 총 입고수량
            $arr_result['total_req_stock'] += $data[stockin];
            
            if ( $_SESSION[USE_EACH_SUPPLY] || _DOMAIN_ == "alice" || _DOMAIN_ == "blueforce" || _DOMAIN_ == "plays" || _DOMAIN_ == "sgo" )
            {
                $pinfo = class_product::get_info( $data[product_id] );

                // 총 입고금액
                $arr_result['total_org_price'] += $pinfo[org_price] * $data[stockin];

                // 총 상품
                if( $pinfo[org_id] )
                    $arr_org[] = $pinfo[org_id];
                else
                    $arr_org[] = $pinfo[product_id];
            }
            else
                $arr_result['cnt']++;
        }
        
        if ( $_SESSION[USE_EACH_SUPPLY] || _DOMAIN_ == "alice" || _DOMAIN_ == "blueforce" || _DOMAIN_ == "plays" || _DOMAIN_ == "sgo" )
            $arr_result['cnt'] = count( array_unique($arr_org) );
        
        return $arr_result;
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock, $is_all, $enable_sale, $nostock_option, $stock_alarm;
        global $category, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $str_supply_code, $multi_supply_group, $multi_supply;
        
        // get list from common module
        $is_download = 1;
        $_arr = $this->get_list( &$total_rows, $is_download );
        
        $fn = $this->make_file( $_arr['list'] );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    //***********************
    // 파일 생성
    // 입고요청전표상세 다운로드..
    function make_file( $arr_datas, $f = 0, $fn='' )
    {
        global $connect, $is_date;
        
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        if( $fn )
            $filename = $fn;
        else
            $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        $f_stock = ( $f ? "요청수량" : "부족수량" );
        
        //debug("pack_p_c: " . $_SESSION["PACK_P_O"] );
        
        
        if ( $_SESSION["PACK_P_O"] == "true" )
        {
            $_arr = array(
                "공급처"
                ,"공급처코드"
                ,"연락처"
                ,"주소"
                ,"상품코드"
                ,"바코드"
                ,"상품명"
                ,"공급처상품명"
                ,"공급처옵션"
                ,"원가"
                ,"재고"
                ,"미배송"
                ,$f_stock
                ,"품절"
                ,"발주일"
                ,"로케이션"
            );
        }
        else
        {
            if( _DOMAIN_ == 'vishop77' )
            {
                $_arr = array(
                    "공급처"
                    ,"공급처코드"
                    ,"주소"
                    ,"연락처"
                    ,"상품코드"
                    ,"바코드"
                    ,"상품명"
                    ,"공급처상품명"
                    ,"옵션"
                    ,"공급처옵션"
                    ,"원가"
                    ,"재고"
                    ,"미배송"
                    ,$f_stock
                    ,"품절"
                    ,"발주일"
                    ,"로케이션"
                );
            }
            else if( _DOMAIN_ == 'secretbb' )
            {
                $_arr = array(
                    "공급처"
                    ,"공급처코드"
                    ,"연락처"
                    ,"주소"
                    ,"상품코드"
                    ,"바코드"
                    ,"공급처상품명"
                    ,"상품명"
                    ,"옵션"
                    ,"공급처옵션"
                    ,"원가"
                    ,"재고"
                    ,"미배송"
                    ,$f_stock
                    ,"품절"
                    ,"발주일"
                    ,"로케이션"
                );
            }
            else
            {
                $_arr = array(
                    "공급처"
                    ,"공급처코드"
                    ,"연락처"
                    ,"주소"
                    ,"상품코드"
                    ,"바코드"
                    ,"상품명"
                    ,"옵션"
                    ,"공급처상품명"
                    ,"공급처옵션"
                    ,"원가"
                    ,"재고"
                    ,"미배송"
                    ,$f_stock
                    ,"품절"
                    ,"발주일"
                    ,"로케이션"
                );
            }
        }
    
        
        if( !$f )  
        {
            $_arr[] = "경고";
            $_arr[] = "계좌번호";
            $_arr[] = "담당자";
            $_arr[] = "미입고요청";
            $_arr[] = "미배송";
        }
        
        // 자동입고??
        // 입고전표 조회(IB02) 다운로드시 입고수량 헤더가 필요..
        // 재고부족요청/조회(IB00) 다운로드시 입고수량 헤더가 필요 없음.
        // 이게 제대로 나오고 있었을까??
        //if( $_SESSION[REQ_STOCKIN_AUTO] )
        //    $_arr[] = "입고수량";        
        if ( $_REQUEST['template'] == "IB02" )
            $_arr[] = "입고수량";

        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_rows = count($arr_datas);
        $i=0;
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
            $style2 = 'font:9pt "굴림"; white-space:nowrap;';
            $style3 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:"\#\,\#\#0_ "';
            
            // for column
            foreach ( $row as $key=>$value) 
            {
                //debug( "vv: $key : $value " );
                
                // 가장 오래된 발주일
                if( $is_date == 1 && $key == "collect_date" )
                {
                    $query = "select a.collect_date collect_date
                                from orders a, order_products b 
                               where a.seq=b.order_seq and
                                     a.status in (1,7) and
                                     b.order_cs not in (1,2) and
                                     b.product_id='$row[product_id]'
                            order by a.collect_date limit 1";
                    $result = mysql_query($query, $connect);
                    $data = mysql_fetch_assoc($result);
                    $value = $data[collect_date];
                }
                
                if( $key == "product_id" || $key == "brand" || $key == "brand_options" )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else if( $key == 'org_price' || $key == 'stock' || $key == 'trans_ready' )
                    $buffer .= "<td style='$style3' x:num=\"$value\">" . $value . "</td>";
                else if( $key == 'stock_sub' )
                {
                    // 2011-08-04 박팀장 요청
                    if( _DOMAIN_ == 'lylon' && $value == 0 )
                        $buffer .= "<td style='$style1'>부족없음</td>";
                    else
                        $buffer .= "<td style='$style3' x:num=\"$value\">" . $value . "</td>";
                }
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
            }
            $buffer .= "</tr>\n";

            if( $is_date )
            {
                if ( $i++ % 7 == 0)
                {
                    echo "<script language='javascript'>parent.show_txt('" . $i . " / " . $total_rows . "')</script>";
                    flush();
                }
            }
            
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename, $new_name;

        if( !$new_name )  $new_name = "stock_data_테스트.xls";

        $obj = new class_file();
        $obj->download_file( $filename , urlencode($new_name));
    }    

    //////////////////////////////////////
    // 입고요청전표 리스트 
    function save_file2()
    {
        global $template, $connect, $bill_seq, $stock_option, $sheet_title, $req_date, $supply_memo;

        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $enable_org_price = true;
        else
            $enable_org_price = false;        

        if( $_SESSION[USE_EACH_SUPPLY] )
        {
            $query = "select * from in_req_bill where seq=$bill_seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $fn = str_replace("/", "-", $data[title]) . ".xls";
            
            // 완료여부
            if( $data[status] == 2 )
                $complete = 1;
            else
                $complete = 1;  // 완료여부 관계없이 입고수량 표시
                
            // 검색 
            $query = "select a.product_id a_product_id,
                             b.name a_product_name,
                             b.options a_options,
                             a.req_stock a_req_stock,
                             b.org_price b_org_price,
                             a.stockin a_stockin,
                             b.brand b_brand,
                             b.barcode
                        from in_req_bill_item a, products b
                       where a.bill_seq=$bill_seq and
                             a.product_id=b.product_id
                       order by b.name, b.options";
            $result = mysql_query($query, $connect);
            
            $_arr = array();
            $total_qty = 0;
            $total_price = 0;
            $total_stockin = 0;
            while( $data = mysql_fetch_assoc($result) )
            {
                if ( $_SESSION["PACK_P_O"] == "true" ) 
                {
                    $temp_arr = array( 
                        product_id       => $data[a_product_id]
                        ,barcode         => $data[barcode]
                        ,product_name    => $data[a_product_name] . " " . $data[a_options]
                        ,brand           => $data[b_brand]
                        ,req_stock       => $data[a_req_stock]
                        ,org_price       => ($enable_org_price ? $data[b_org_price] : 0)
                        ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                    );
                }
                else
                {    
                    $temp_arr = array( 
                        product_id       => $data[a_product_id]
                        ,barcode         => $data[barcode]
                        ,product_name    => $data[a_product_name]
                        ,options         => $data[a_options]
                        ,brand           => $data[b_brand]
                        ,req_stock       => $data[a_req_stock]
                        ,org_price       => ($enable_org_price ? $data[b_org_price] : 0)
                        ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                    );
                }

                if( $complete )
                {
                    $temp_arr[stockin] = $data[a_stockin];
                    $total_stockin += $data[a_stockin];
                }
                
                $total_qty += $data[a_req_stock];
                $total_price += $data[b_org_price] * $data[a_stockin];
                
                $_arr['list'][] = $temp_arr;
            }
            
            if ( $_SESSION["PACK_P_O"] == "true" ) 
            {
                $temp_arr = array( 
                    product_id       => "합계"
                    ,barcode         => ""
                    ,product_name    => ""
                    ,brand           => ""
                    ,req_stock       => $total_qty
                    ,org_price       => ""
                    ,total_org_price => $total_price
                );
            }
            else
            {
                $temp_arr = array( 
                    product_id       => "합계"
                    ,barcode         => ""
                    ,product_name    => ""
                    ,options         => ""
                    ,brand           => ""
                    ,req_stock       => $total_qty
                    ,org_price       => ""
                    ,total_org_price => $total_price
                );
            }
            if( $complete )
                $temp_arr[stockin] = $total_stockin;
            
            $_arr['list'][] = $temp_arr;
    
            // 전표명 & 요청일 & 공급처 메모
            $query_info = "select * from in_req_bill where seq=$bill_seq";
            $result_info = mysql_query($query_info, $connect);
            $data_info = mysql_fetch_assoc($result_info);
            
            $sheet_title = $data_info[title];
            $req_date = $data_info[crdate] . " " . $data_info[crtime];
            $supply_memo = htmlspecialchars_decode($data_info[supply_memo]);
    
            $query_supply = "select * from userinfo where code='$data_info[supply_code]'";
            $result_supply = mysql_query($query_supply, $connect);
            $data_supply = mysql_fetch_assoc($result_supply);
    
            global $supply_tel, $supply_add;
            $supply_tel = $data_supply[tel] . " / " . $data_supply[mobile];
            $supply_add = $data_supply[address1] . " " . $data_supply[address2];

            $this->make_file_mail( $_arr['list'], $fn, $complete, 1);
        }
        else
        {
            // 검색 
            $query = "select c.name as supply_name,
                             c.code as supply_code,
                             c.tel as supply_tel,
                             c.mobile as supply_mobile,
                             c.address1 as supply_addr1,
                             c.address2 as supply_addr2,
                             a.product_id as product_id,
                             b.name as product_name,
                             b.options as options,
                             b.brand as brand,
                             b.supply_options as supply_options,
                             b.org_price as org_price,
                             a.stock as stock,
                             a.not_yet_deliv as not_yet_deliv,
                             a.req_stock as req_stock,
                             b.enable_sale as enable_sale,
                             a.stockin as stockin,
                             b.barcode,
                             b.location as location";
    
            // 재고 있는 다른 옵션상품 같이보기
            if( $is_download && $stock_option )
                $query .= " ,if(org_id>0,org_id,product_id) as org_product_id ";
                
            $query .= " from in_req_bill_item a, products b, userinfo c 
                       where a.bill_seq=$bill_seq and
                             a.product_id=b.product_id and
                             b.supply_code=c.code";
                             
            if( _DOMAIN_ == 'hckim1515' || _DOMAIN_ == 'secretbb' )
                $order_by_address = "supply_addr1,";
            else
                $order_by_address = "";

            // 재고 있는 다른 옵션상품 같이보기
            if( $is_download && $stock_option )
                $query .= " group by org_product_id order by $order_by_address c.name, b.name";
            else
                $query .= " order by $order_by_address c.name, b.name, b.options";
            $result = mysql_query($query, $connect);
            
            $_arr = array();
            while( $data = mysql_fetch_assoc($result) )
            {
                if ( $_SESSION["PACK_P_O"] == "true" )
                {
                    $temp_arr = array( 
                        supply_name      => $data[supply_name]
                        ,supply_id       => $data[supply_code] 
                        ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                        ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                        ,product_id      => $data[product_id]
                        ,barcode         => $data[barcode]
                        ,product_name    => $data[product_name]. $data[options]
                        ,brand           => $data[brand]
                        ,brand_options   => $data[supply_options]
                        ,org_price       => ($enable_org_price ? $data[org_price] : 0)
                        ,stock           => ($data[stock] ? $data[stock] : 0)
                        ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                        ,req_stock       => $data[req_stock]
                        ,enable_sale     => ($data[enable_sale]? "":"품절")
                        ,collect_date    => ""
                        ,location        => $data[location]
                    );
                }
                else
                {
                    if( _DOMAIN_ == 'vishop77' )
                    {
                        $temp_arr = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code] 
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,product_name    => $data[product_name]
                            ,brand           => $data[brand]
                            ,options         => $data[options]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0)
                            ,stock           => ($data[stock] ? $data[stock] : 0)
                            ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                            ,req_stock       => $data[req_stock]
                            ,enable_sale     => ($data[enable_sale]? "":"품절")
                            ,collect_date    => ""
                            ,location        => $data[location]
                        );  
                    }
                    else if( _DOMAIN_ == 'secretbb' )
                    {
                        $temp_arr = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code] 
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,brand           => $data[brand]
                            ,product_name    => $data[product_name]
                            ,options         => $data[options]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0)
                            ,stock           => ($data[stock] ? $data[stock] : 0)
                            ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                            ,req_stock       => $data[req_stock]
                            ,enable_sale     => ($data[enable_sale]? "":"품절")
                            ,collect_date    => ""
                            ,location        => $data[location]
                        );  
                    }
                    else
                    {
                        $temp_arr = array( 
                            supply_name      => $data[supply_name]
                            ,supply_id       => $data[supply_code] 
                            ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                            ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                            ,product_id      => $data[product_id]
                            ,barcode         => $data[barcode]
                            ,product_name    => $data[product_name]
                            ,options         => $data[options]
                            ,brand           => $data[brand]
                            ,brand_options   => $data[supply_options]
                            ,org_price       => ($enable_org_price ? $data[org_price] : 0)
                            ,stock           => ($data[stock] ? $data[stock] : 0)
                            ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                            ,req_stock       => $data[req_stock]
                            ,enable_sale     => ($data[enable_sale]? "":"품절")
                            ,collect_date    => ""
                            ,location        => $data[location]
                        );  
                    }
                }
                if( $_SESSION[REQ_STOCKIN_AUTO] )
                    $temp_arr['stockin'] = $data[stockin];
    
                $_arr['list'][] = $temp_arr;
            }
            
            // save_file2
            foreach( $_arr['list'][0] as $k => $v )
            {
                //debug("$k : $v");   
            }     
            
            $fn = $this->make_file( $_arr['list'], 1 );
        }
                
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    // 입고요청 추가
    function add_input_plan()
    {
        global $connect, $product_id, $qty;
        
        // 이미 있는지 확인
        $query = "select * from stockin_req 
                   where crdate = date(now()) and 
                         product_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
        {
            // 업데이트
            $query = "update stockin_req
                         set qty = qty + $qty
                       where crdate = date(now()) and
                             product_id='$product_id'";
        }
        else
        {
            // 추가하기
            $query = "insert stockin_req
                         set crdate = now(),
                             product_id = '$product_id',
                             qty = '$qty'";
        }
        mysql_query($query, $connect);
        
        // 추가된 값 구하기
        $query = "select qty from stockin_req where crdate = date(now()) and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val = array();
        $val['data'] = $data[qty];
        
        echo json_encode( $val );
    }

    function delete_sheet()
    {
        global $connect, $seq;
        
        $query = "select * from in_req_bill where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        debug( "입고요청전표 삭제 : seq - $data[seq] | title - $data[title] | crdate - $data[crdate] | crtime - $data[crtime] | status - $data[status] | owner - $data[owner] | smstime - $data[smstime] | complete_date - $data[complete_date] | worker - $data[worker] ");

        $query = "delete from in_req_bill where seq=$seq";
        mysql_query($query, $connect);
    }
    
    function change_req_stockin()
    {
        global $connect, $seq, $product_id, $qty;
        
        $val = array();

        if( !is_numeric($qty) )
            $val['error'] = 1;
        else
        {
            $query = "update in_req_bill_item set stockin=$qty where bill_seq=$seq and product_id='$product_id'";
            if( mysql_query($query, $connect) )
            {
                $val['error'] = 0;

                $query = "select stockin from in_req_bill_item where bill_seq=$seq and product_id='$product_id'";
                $result = mysql_query( $query, $connect );
                $data = mysql_fetch_assoc($result);
                
                $val['qty'] = $data[stockin];
            }
            else
                $val['error'] = 2;
        }
        
        echo json_encode( $val );
    }

    function change_req_memo()
    {
        global $connect, $seq, $product_id, $memo;
        
        $val = array();
        $query = "update in_req_bill_item set memo='$memo' where bill_seq=$seq and product_id='$product_id'";
        if( mysql_query($query, $connect) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
            
        echo json_encode( $val );
    }

    function change_req_pmemo()
    {
        global $connect, $seq, $product_id, $memo;
        
        $val = array();
        $query = "update products set memo='$memo' where product_id='$product_id'";
        if( mysql_query($query, $connect) )
            $val['error'] = 0;
        else
            $val['error'] = 1;
            
        echo json_encode( $val );
    }

    function add_req_stockin()
    {
        global $connect, $seq, $product_id, $qty;
        
        $val = array();
        
        // 이미 전표에 추가된 상품인지 확인
        $query = "select * from in_req_bill_item where bill_seq=$seq and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);

            if( $_SESSION[REQ_STOCKIN_AUTO] )
            {
                $query = "update in_req_bill_item
                             set stockin = stockin + $qty
                           where bill_seq = $data[bill_seq] and
                                 product_id = '$data[product_id]' ";
            }
            else
            {
                $query = "update in_req_bill_item
                             set req_stock = req_stock + $qty
                           where bill_seq = $data[bill_seq] and
                                 product_id = '$data[product_id]' ";
            }

            if( mysql_query( $query, $connect ) )
                $val['error'] = 0;
            else
                $val['error'] = 1;
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
            if( $_SESSION[REQ_STOCKIN_AUTO] )
            {
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
                                 req_stock     = 0,
                                 stockin       = $qty";
            }
            else
            {
                $query = "insert in_req_bill_item
                             set bill_seq      = $seq,
                                 supply_name   = '$supply_name',
                                 supply_id     = '$data[b_code]',
                                 product_id    = '$product_id',
                                 product_name  = '$product_name',
                                 brand         = '$brand',
                                 options       = '$options',
                                 stock         = 0,
                                 not_yet_deliv = 0,
                                 stock_sub     = 0,
                                 req_stock     = $stock,
                                 stockin       = $qty";
            }
            if( mysql_query( $query, $connect ) )
                $val['error'] = 0;
            else
                $val['error'] = 1;
        }
        
        echo json_encode( $val );
        exit;
    }

    // 검색된 전표 전체 파일 압축하여 다운로드하기
    function save_file3()
    {
        global $template, $connect, $seq, $start_date, $end_date, $title, $status, $download_file_type;

        $query = "select * from in_req_bill where seq in ($seq)";
        $result = mysql_query($query, $connect);
        
        if( $download_file_type == 1 )
        {
            $fn = "입고요청전표.xls";
            $this->make_sheet_file_all($seq, $fn);
            echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
        }
        else
        {
            $file_arr = array();
            while($data=mysql_fetch_assoc($result))
            {
                $fn = str_replace("/","-",$data[title]) . ".xls";
                
                if( $data[status] == 2 )
                    $complete = 1;
                else
                    $complete = 0;
                    
                if( $_SESSION[USE_EACH_SUPPLY] )
                    $this->make_sheet_file_mail($data[seq], $fn, $complete, 1);
                else
                    $this->make_sheet_file($data[seq], $fn);
                    
                $file_arr[$fn] = _upload_dir.$fn;
            }
    
            $zip_fn = "입고요청";
            if( $title )  $zip_fn .= "_$title";
            $zip_fn .= ".zip";
            
            $ziper = new zipfile(); 
            $ziper->addFiles($file_arr);  //array of files 
            $ziper->output(_upload_dir . $zip_fn); 
            echo "<script type='text/javascript'>parent.set_file('$zip_fn')</script>";
        }
    }

    // 개별전표 파일 생성하기
    function make_sheet_file($bill_seq, $fn)
    {
        global $template, $connect;
        
        // 검색 
        $query = "select c.name as supply_name,
                         c.code as supply_code,
                         c.tel as supply_tel,
                         c.mobile as supply_mobile,
                         c.address1 as supply_addr1,
                         c.address2 as supply_addr2,
                         a.product_id as product_id,
                         b.name as product_name,
                         b.options as options,
                         b.brand as brand,
                         b.supply_options as supply_options,
                         b.org_price as org_price,
                         a.stock as stock,
                         a.not_yet_deliv as not_yet_deliv,
                         a.req_stock as req_stock,
                         b.enable_sale as enable_sale,
                         a.stockin as stockin,
                         b.location as location
                    from in_req_bill_item a, products b, userinfo c 
                   where a.bill_seq=$bill_seq and
                         a.product_id=b.product_id and
                         b.supply_code=c.code
                   order by c.name, b.name, b.options";
        $result = mysql_query($query, $connect);
        
        $_arr = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            if ( $_SESSION["PACK_P_O"] == "true" )
            {
                $temp_arr = array( 
                    supply_name      => $data[supply_name]
                    ,supply_id       => $data[supply_code] 
                    ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                    ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                    ,product_id      => $data[product_id]
                    ,product_name    => $data[product_name] . " " . $data[options]
                    ,brand           => $data[brand]
                    ,brand_options   => $data[supply_options]
                    ,org_price       => $data[org_price]
                    ,stock           => ($data[stock] ? $data[stock] : 0)
                    ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                    ,req_stock       => $data[req_stock]
                    ,enable_sale     => ($data[enable_sale]? "":"품절")
                    ,collect_date    => ""
                    ,location        => $data[location]
                    );
            }
            else
            {
                $temp_arr = array( 
                    supply_name      => $data[supply_name]
                    ,supply_id       => $data[supply_code] 
                    ,supply_contact  => $data[supply_tel] . " / " . $data[supply_mobile]
                    ,supply_address  => $data[supply_addr1] . " " . $data[supply_addr2]
                    ,product_id      => $data[product_id]
                    ,product_name    => $data[product_name]
                    ,options         => $data[options]
                    ,brand           => $data[brand]
                    ,brand_options   => $data[supply_options]
                    ,org_price       => $data[org_price]
                    ,stock           => ($data[stock] ? $data[stock] : 0)
                    ,trans_ready     => ($data[not_yet_deliv] ? $data[not_yet_deliv] : "")
                    ,req_stock       => $data[req_stock]
                    ,enable_sale     => ($data[enable_sale]? "":"품절")
                    ,collect_date    => ""
                    ,location        => $data[location]
                );
            }
            
            if( $_SESSION[REQ_STOCKIN_AUTO] )
                $temp_arr['stockin'] = $data[stockin];

            $_arr['list'][] = $temp_arr;
        }

        $this->make_file( $_arr['list'], 1, $fn );
    }

    // 개별전표 파일 생성하기 - 메일 보내기용
    function make_sheet_file_mail($bill_seq, $fn, $complete=0, $is_zip=0)
    {
        global $template, $connect, $sheet_title, $req_date, $supply_memo;
        
        // 검색 
        $query = "select a.product_id a_product_id,
                         a.product_name a_product_name,
                         a.options a_options,
                         a.req_stock a_req_stock,
                         b.org_price b_org_price,
                         a.stockin a_stockin,
                         b.brand b_brand
                    from in_req_bill_item a, products b
                   where a.bill_seq=$bill_seq and
                         a.product_id=b.product_id ";
                         
        // 목록에서 개별압축파일 받을때는 요청수량 0 도 포함
        if( !$is_zip )
            $query .= " and a.req_stock > 0 ";                         
                         
        $query .= " order by b.name, b.options";
        $result = mysql_query($query, $connect);
        
        $_arr = array();
        $total_qty = 0;
        $total_price = 0;
        $total_stockin = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            if ( $_SESSION["PACK_P_O"] == "true" )
            {
                $temp_arr = array( 
                    product_id      => $data[a_product_id]
                    ,product_name    => $data[a_product_name] . " " .$data[a_options]
                    ,brand           => $data[b_brand]
                    ,req_stock       => $data[a_req_stock]
                    ,org_price       => $data[b_org_price]
                    ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                );
            }
            else
            {
                $temp_arr = array( 
                    product_id      => $data[a_product_id]
                    ,product_name    => $data[a_product_name]
                    ,options         => $data[a_options]
                    ,brand           => $data[b_brand]
                    ,req_stock       => $data[a_req_stock]
                    ,org_price       => $data[b_org_price]
                    ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                ); 
            }
            
            if( $complete )
            {
                $temp_arr[stockin] = $data[a_stockin];
                $total_stockin += $data[a_stockin];
            }

            $total_qty += $data[a_req_stock];
            $total_price += $data[b_org_price] * $data[a_req_stock];
            
            $_arr['list'][] = $temp_arr;
        }
        
        if ( $_SESSION["PACK_P_O"] == "true")
        {
            $temp_arr = array( 
                product_id    => "합계"
                ,product_name    => ""
                ,brand           => ""
                ,req_stock       => $total_qty
                ,org_price       => ""
                ,total_org_price => $total_price
            );
        }
        else
        {
            $temp_arr = array( 
                product_id    => "합계"
                ,product_name    => ""
                ,options         => ""
                ,brand           => ""
                ,req_stock       => $total_qty
                ,org_price       => ""
                ,total_org_price => $total_price
            );  
        }
        
        if( $complete )
            $temp_arr[stockin] = $total_stockin;
        
        $_arr['list'][] = $temp_arr;

        // 전표명 & 요청일
        $query_info = "select * from in_req_bill where seq=$bill_seq";
        $result_info = mysql_query($query_info, $connect);
        $data_info = mysql_fetch_assoc($result_info);
        
        $sheet_title = $data_info[title];
        $req_date = $data_info[crdate] . " " . $data_info[crtime];
        $supply_memo = htmlspecialchars_decode($data_info[supply_memo]);
        
        $query_supply = "select * from userinfo where code='$data_info[supply_code]'";
        $result_supply = mysql_query($query_supply, $connect);
        $data_supply = mysql_fetch_assoc($result_supply);

        global $supply_tel, $supply_add;
        $supply_tel = $data_supply[tel] . " / " . $data_supply[mobile];
        $supply_add = $data_supply[address1] . " " . $data_supply[address2];

        $this->make_file_mail( $_arr['list'], $fn, $complete);
    }

    //***********************
    // 파일 생성 - 메일 보내기용
    function make_file_mail( $arr_datas, $fn, $complete=0, $use_barcode=0)
    {
        global $connect, $is_date, $sheet_title, $req_date, $supply_memo, $supply_tel, $supply_add;

        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        if ( $_SESSION["PACK_P_O"] == "true" )
        {
            if( $complete )
                $end_col = "G";
            else
                $end_col = "F";
        }
        else
        {
            if( $complete )
                $end_col = "H";
            else
                $end_col = "G";
        }
        
        // 바코드 필드 추가
        if( $use_barcode )
        {
            if( $end_col == "F" )
                $end_col = "G";
            else if( $end_col == "G" )
                $end_col = "H";
            else if( $end_col == "H" )
                $end_col = "I";
        }

        $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("전표명 : ".$sheet_title, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(0, 2)->setValueExplicit("요청일 : ".$req_date, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(0, 3)->setValueExplicit("공급처 연락처 : ".$supply_tel, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(0, 4)->setValueExplicit("공급처 주소 : ".$supply_add, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow(0, 5)->setValueExplicit("공급처 메모 : ".$supply_memo, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->mergeCells('A1:'.$end_col.'1');
        $sheet->mergeCells('A2:'.$end_col.'2');
        $sheet->mergeCells('A3:'.$end_col.'3');
        $sheet->mergeCells('A4:'.$end_col.'4');
        $sheet->mergeCells('A5:'.$end_col.'5');

        $col_cnt = 0;
        $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("상품코드", PHPExcel_Cell_DataType::TYPE_STRING);

        if( $use_barcode )
            $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("바코드", PHPExcel_Cell_DataType::TYPE_STRING);

        if ( $_SESSION["PACK_P_O"] == "true" )
            $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
        else
        {
            $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("옵션",     PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("공급처상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
        
        $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("요청수량", PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("원가",     PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("총원가",   PHPExcel_Cell_DataType::TYPE_STRING);
        
        if( $complete )
            $sheet->getCellByColumnAndRow($col_cnt++, 6)->setValueExplicit("입고수량",   PHPExcel_Cell_DataType::TYPE_STRING);
         
        $sheet->getStyle('A6:'.$end_col.'6')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A6:'.$end_col.'6')->getFill()->getStartColor()->setARGB('FFCCFFCC');
        $sheet->getStyle('A6:'.$end_col.'6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A6:'.$end_col.'6')->getFont()->setBold(true);

        $base_width = strlen( iconv('utf-8','cp949',"공급처상품명" ) );
        $cell_width = array($base_width,$base_width,$base_width,$base_width);
        
        foreach ($arr_datas as $row => $row_data) {
            $row = $row + 7;
            $col = 0;
            foreach ($row_data as $key => $value) {
                // 폭 계산1
                if( $col == 1 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[0] = ( $cell_width[0] < $new_width ? $new_width : $cell_width[0] );
                }

                // 폭 계산2
                if( $col == 2 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[1] = ( $cell_width[1] < $new_width ? $new_width : $cell_width[1] );
                }
                
                // 폭 계산3
                if( $col == 3 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[2] = ( $cell_width[2] < $new_width ? $new_width : $cell_width[2] );
                }
                
                // 폭 계산4
                if( $col == 4 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[3] = ( $cell_width[3] < $new_width ? $new_width : $cell_width[3] );
                }
                
                $value = trim($value);
                if( $key == 'req_stock' || $key == 'org_price' || $key == 'total_org_price' || $key == 'stockin' )
                {
                    $cell = $sheet->getCellByColumnAndRow($col, $row);
                    $cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
                }
                else
                    $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
        }
        
        $sheet->getColumnDimension('B')->setWidth($cell_width[0]);
        $sheet->getColumnDimension('C')->setWidth($cell_width[1]);
        $sheet->getColumnDimension('D')->setWidth($cell_width[2]);
        $sheet->getColumnDimension('E')->setWidth($cell_width[3]);
        
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



    // 전체전표 파일 생성하기
    function make_sheet_file_all($bill_seq, $fn)
    {
        global $template, $connect;
        
        // 공급처 연락처
        $supply_tel = array();
        $query = "select * from userinfo where level=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $supply_tel[$data[code]] = $data[address1] . " " . $data[address2] . " (" . $data[tel] . " / " . $data[mobile] . ") ";
        
        // 검색 
        $query = "select a.bill_seq a_bill_seq,
                         a.product_id a_product_id,
                         a.product_name a_product_name,
                         a.options a_options,
                         a.req_stock a_req_stock,
                         b.org_price b_org_price,
                         a.stockin a_stockin,
                         b.brand b_brand,
                         c.title c_title,
                         c.status c_status,
                         b.supply_code b_supply_code
                    from in_req_bill_item a, products b, in_req_bill c
                   where a.bill_seq in ($bill_seq) and
                         a.product_id=b.product_id and
                         a.bill_seq = c.seq 
                   order by c.title, b.name, b.options";
        $result = mysql_query($query, $connect);
        
        $_arr = array();
        $total_qty = 0;
        $total_price = 0;
        $total_stockin = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            if ( $_SESSION["PACK_P_O"] == "true" )
            {
                $temp_arr = array( 
                    title            => $data[c_title]
                    ,product_id      => $data[a_product_id]
                    ,product_name    => $data[a_product_name] .  $data[a_options]
                    ,brand           => $data[b_brand]
                    ,req_stock       => $data[a_req_stock]
                    ,org_price       => $data[b_org_price]
                    ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                    ,stockin         => ($data[c_status] == 2 ? $data[a_stockin] : "")
                );
            }
            else
            {
                $temp_arr = array( 
                    title            => $data[c_title]
                    ,product_id      => $data[a_product_id]
                    ,product_name    => $data[a_product_name]
                    ,options         => $data[a_options]
                    ,brand           => $data[b_brand]
                    ,req_stock       => $data[a_req_stock]
                    ,org_price       => $data[b_org_price]
                    ,total_org_price => $data[b_org_price] * $data[a_req_stock]
                    ,stockin         => ($data[c_status] == 2 ? $data[a_stockin] : "")
                ); 
            }
            
            // 공급처 연락처
            $temp_arr["supply_tel"] = $supply_tel[$data[b_supply_code]];

            $total_qty     += $data[a_req_stock];
            $total_price   += $data[b_org_price] * $data[a_req_stock];
            $total_stockin += ($data[c_status] == 2 ? $data[a_stockin] : 0);
            
            $_arr['list'][] = $temp_arr;
        }
        
        if ( $_SESSION["PACK_P_O"] == "true")
        {
            $temp_arr = array( 
                title          => "합계"
                ,product_id    => ""
                ,product_name    => ""
                ,brand           => ""
                ,req_stock       => $total_qty
                ,org_price       => ""
                ,total_org_price => $total_price
                ,stockin         => $total_stockin
            );
        }
        else
        {
            $temp_arr = array( 
                title          => "합계"
                ,product_id    => ""
                ,product_name    => ""
                ,options         => ""
                ,brand           => ""
                ,req_stock       => $total_qty
                ,org_price       => ""
                ,total_org_price => $total_price
                ,stockin         => $total_stockin
            );  
        }
        
        // 공급처 연락처
        $temp_arr["supply_tel"] = "";

        $_arr['list'][] = $temp_arr;

        $this->make_file_mail_all( $_arr['list'], $fn );
    }

    //***********************
    // 파일 생성 - 메일 보내기용
    function make_file_mail_all( $arr_datas, $fn )
    {
        global $connect, $is_date;

        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        if ( $_SESSION["PACK_P_O"] == "true" )
            $end_col = "H";
        else
            $end_col = "J";

        if ( $_SESSION["PACK_P_O"] == "true" )
        {
            $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("전표명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(1, 1)->setValueExplicit("상품코드", PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(2, 1)->setValueExplicit("상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(3, 1)->setValueExplicit("공급처상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
            
            $sheet->getCellByColumnAndRow(4, 1)->setValueExplicit("요청수량", PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(5, 1)->setValueExplicit("원가",     PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(6, 1)->setValueExplicit("총원가",   PHPExcel_Cell_DataType::TYPE_STRING);
            
            $sheet->getCellByColumnAndRow(7, 1)->setValueExplicit("입고수량",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(8, 1)->setValueExplicit("연락처",   PHPExcel_Cell_DataType::TYPE_STRING);
        }
        else
        {
            $sheet->getCellByColumnAndRow(0, 1)->setValueExplicit("전표명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(1, 1)->setValueExplicit("상품코드", PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(2, 1)->setValueExplicit("상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(3, 1)->setValueExplicit("옵션",     PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(4, 1)->setValueExplicit("공급처상품명",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(5, 1)->setValueExplicit("요청수량", PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(6, 1)->setValueExplicit("원가",     PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(7, 1)->setValueExplicit("총원가",   PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->getCellByColumnAndRow(8, 1)->setValueExplicit("입고수량",   PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->getCellByColumnAndRow(9, 1)->setValueExplicit("연락처",   PHPExcel_Cell_DataType::TYPE_STRING);
        }
         
        $sheet->getStyle('A1:'.$end_col.'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle('A1:'.$end_col.'1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
        $sheet->getStyle('A1:'.$end_col.'1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:'.$end_col.'1')->getFont()->setBold(true);

        $cell_width = array(
            strlen( iconv('utf-8','cp949',"상품명") ),
            strlen( iconv('utf-8','cp949',"옵션"  ) ),
            strlen( iconv('utf-8','cp949',"공급처상품명"  ) )
        );
        
        foreach ($arr_datas as $row => $row_data) {
            $row = $row + 2;
            $col = 0;
            foreach ($row_data as $key => $value) {
                // 전표명 폭 계산
                if( $col == 0 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[0] = ( $cell_width[0] < $new_width ? $new_width : $cell_width[0] );
                }

                // 상품명 폭 계산
                if( $col == 2 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[1] = ( $cell_width[1] < $new_width ? $new_width : $cell_width[1] );
                }

                // 옵션 계산
                if( $col == 3 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[2] = ( $cell_width[2] < $new_width ? $new_width : $cell_width[2] );
                }
                
                // 공급처상품명
                if( $col == 4 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[3] = ( $cell_width[3] < $new_width ? $new_width : $cell_width[3] );
                }
                
                // 공급처연락처
                if( $col == 9 )
                {
                    $new_width = strlen( iconv('utf-8','cp949',$value) );
                    $cell_width[8] = ( $cell_width[8] < $new_width ? $new_width : $cell_width[8] );
                }
                
                $value = trim($value);
                if( $key == 'req_stock' || $key == 'org_price' || $key == 'total_org_price' || $key == 'stockin' )
                {
                    $cell = $sheet->getCellByColumnAndRow($col, $row);
                    $cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
                }
                else
                    $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
        }
        
        $sheet->getColumnDimension('A')->setWidth($cell_width[0]);
        $sheet->getColumnDimension('C')->setWidth($cell_width[1]);
        $sheet->getColumnDimension('D')->setWidth($cell_width[2]);
        $sheet->getColumnDimension('E')->setWidth($cell_width[3]);
        $sheet->getColumnDimension('J')->setWidth($cell_width[8]);
        
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


    //////////////////////////////////////////////////////
    // 상품추가 - 재고부족요청에서
    function IB05()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $seq, $supply_code, $name, $options, $query_type, $search_string;
 		global $str_supply_code, $multi_supply_group, $multi_supply, $enable_sale;
 		
 		
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
            $par = array('template','supply_code', 'name', 'options','query_type', 'search_string');
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
                       
                       
			if($enable_sale)
			{
				if($enable_sale	==1)
					$query .= " AND a.enable_sale = 0 ";
				else if($enable_sale	==2)
					$query .= " AND a.enable_sale NOT IN (0) ";
			}
			
			
			if( $str_supply_code )
				$query .= " and a.supply_code in ( $str_supply_code ) ";
			if($multi_supply)
				$query .= " and a.supply_code in ( $multi_supply ) ";
                

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
    
    //////////////////////////////////////////////////////
    // 입고요청 이메일 보내기
    function IB06()
    {
        global $template, $connect, $seq, $type;

        // 해당 전표의 첫번째 상품
        $query = "select * from in_req_bill_item where bill_seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $supply_code = $data[supply_id];
        
        // 받는 메일 주소
        $query = "select * from userinfo where code=$supply_code";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $mail_to = $data[email];
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_product_info()
    {
        global $connect, $product_id;
        
        $val = array();
        
        $info = class_product::get_info( $product_id );
        
        $val['product_id']    = $product_id;
        $val['supply_name']   = class_supply::get_name( $info[supply_code] );
        $val['enable_sale']   = $info[enable_sale];
        $val['product_name']  = $info[name];
        $val['options']       = $info[options];
        $val['brand']         = $info[brand];
        $val['brand_options'] = $info[supply_option];
        
        if( $info[enable_stock] )
            $val['stock'] = class_stock::get_current_stock( $product_id );
        else
            $val['stock'] = 0;
        
        // 미배송 구하기
        $query = "select sum(b.qty) sum
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.status in (1,7) and
                         b.order_cs not in (1,2) and
                         b.product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $val['trans_ready'] = ($data[sum] ? $data[sum] : 0);
        $val['stock_sub'] = $val['stock'] - $val['trans_ready'];

        echo json_encode($val);
    }
    
    function send_mail_each()
    {
        global $connect, $seq, $mail_from, $mail_to, $mail_title, $mail_content;

        // 전표명
        $query = "select * from in_req_bill where seq=$seq"; 
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $sheet_name = $data[title];

        // 개별전표 파일 생성하기 (이름에 '/'가 있는경우 '-'로 수정)
        $file_name = str_replace("/", "-", $sheet_name) . ".xls";
        $this->make_sheet_file_mail($seq, $file_name);
        
        $val = array();
        
        // 메일 보내기
        $this->send_sheet_email( $file_name );
        
        echo "<script type='text/javascript'>alert('메일을 발송하였습니다.')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    function send_mail_all()
    {
        global $connect, $seq, $mail_from, $mail_to, $mail_title, $mail_content;

        $this->show_wait();
        
        // 전표
        $i = 1;
        $seq_arr = explode(",", $seq);
        $cnt = count($seq_arr);
        foreach( $seq_arr as $seq )
        {
            $query = "select * from in_req_bill where seq=$seq"; 
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $sheet_name = $data[title];
            
            // 상품
            $query = "select * from in_req_bill_item where bill_seq=$seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 공급처
            $query = "select * from userinfo where code=$data[supply_id]";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $mail_to = $data[email];

            // 개별전표 파일 생성하기
            $file_name = str_replace("/", "-", $sheet_name) . ".xls";
            $this->make_sheet_file_mail($seq, $file_name);
            
            $this->send_sheet_email( $file_name );

            echo "<script type='text/javascript'>show_txt('" . $i++ . " / " . $cnt . "')</script>";
            flush();
        }
        
        echo "<script type='text/javascript'>alert('메일을 발송하였습니다.')</script>";
        echo "<script type='text/javascript'>self.close()</script>";
    }

    function reset_stockin()
    {
        global $connect, $seq;
        
        $query = "update in_req_bill_item set stockin=0 where bill_seq=$seq ";
        mysql_query($query, $connect);
    }
    
    function save_stockin()
    {
        global $connect, $seq, $stockin_str;
        
        foreach( explode(",", $stockin_str) as $qty_val )
        {
            list($product_id, $qty) = explode(":", $qty_val);
            if( $product_id )
            {
                $query = "update in_req_bill_item set stockin=$qty where bill_seq = $seq and product_id = '$product_id' ";
                mysql_query($query, $connect);
            }
        }
    }
        
    function save_qty()
    {
        global $connect, $seq, $stockin_str;

        // 입고수량 변경
        $stockin_arr = explode(",", substr($stockin_str,0,-1));
        foreach($stockin_arr as $s)
        {
            $ss = explode(":",$s);
            $query = "update in_req_bill_item set stockin=$ss[1] where bill_seq=$seq and product_id='$ss[0]'";
            mysql_query($query, $connect);
        }
    }
    
    function save_supply_name()
    {
        global $connect, $seq, $memo;
        
        $query = "update in_req_bill set supply_memo='$memo' where seq=$seq";
        mysql_query($query, $connect);
    }





}
?>
