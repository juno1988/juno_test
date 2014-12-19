<?
require_once "class_B.php";
require_once "class_C.php";
require_once "class_top.php";
require_once "class_file.php";
require_once "ExcelReader/reader.php";
require_once "lib/ez_excel_lib.php";

////////////////////////////////
// class name: class_D900
//
class class_D900 extends class_top 
{ 
    function D900()
    {
        global $template, $start_date, $end_date, $shop_id, $page, $order_id, $status_sel, $order_cs_sel;
        global $query_trans_who, $order_name, $keyword, $search_type;
    
        if ( !$page )
        {
            $_REQUEST["page"] = 1;
            $page = 1;
        }
    
        $par_arr = array("template","action","shop_id","start_date","end_date","status_sel","order_cs_sel", "page",
                   "query_trans_who","order_name","keyword", "search_type");
    
        $link_url_list = $this->build_link_par($par_arr);  
        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();
        
        if ( $_REQUEST["page"] )
        {
            echo "<script>show_waiting()</script>";             
            $result = $this->search2( &$total_rows, &$total_rows_b ); 
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        if ( $_REQUEST["page"] )
            echo "<script>hide_waiting()</script>";
    }

    function D901()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";         
    }
     
    function D910()
    {
        global $template, $search_type, $str_from, $str_to, $page, $line_per_page, $search_type, $reg_search_type, $org_page;

        if ( !$page )
        {
            $_REQUEST["page"] = 1;
            $page = 1;
        }

        $par_arr = array("template","action","search_type","reg_search_type","str_from","str_to","page");
    
        $link_url_list = $this->build_link_par($par_arr);  
        $line_per_page = 20;
        $link_url = "?" . $this->build_link_url();

        if ( $_REQUEST["page"] )
        {
            echo "<script>show_waiting()</script>";
            $result = $this->macro_search(&$total_rows); 
        }

        $master_code = substr( $template, 0,1);            
        include "template/" . $master_code ."/" . $template . ".htm";

        if ( $_REQUEST["page"] )
            echo "<script>hide_waiting()</script>";
    }
  
    //============================================
    // D900 검색로직
    // 
    function search2( &$total_rows, &$total_rows_b )
    {
        global $connect, $start_date, $end_date, $shop_id, $page, $status_sel, $order_cs_sel;
        global $query_trans_who, $order_name, $keyword, $search_type;

        if ( !$start_date)  $start_date = date('Y-m-d', strtotime('-60 day'));
        if ( !$end_date )  $end_date = date('Y-m-d');

        // 상품 개수
        $query_order_cnt   = "select count(distinct a.seq) a_cnt, count(b.seq) b_cnt ";
 
        // 주문 정보 , 발주일 기준
        $query = " select a.seq seq,
                          a.collect_date collect_date,
                          a.shop_id shop_id,
                          a.order_id order_id,
                          a.recv_name recv_name,
                          a.product_name product_name,
                          b.shop_options shop_options,
                          b.seq order_seq,
                          a.memo memo,
                          a.qty qty,
                          a.status status,
                          a.order_cs order_cs" ;
    
        $opt   = "   from orders a,
                          order_products b
                    where a.collect_date >= '$start_date' and 
                          a.collect_date <= '$end_date' and
                          a.seq = b.order_seq and
                          a.order_cs<>1 and
                          a.status = 1 ";
        // C/S 
        switch( $order_cs_sel )
        {
            case 1: $opt .= " and a.order_cs in ( 0 )   "; break;
            case 2: $opt .= " and a.order_cs in ( 1,2 ) "; break;
            case 3: $opt .= " and a.hold > 0            "; break;
            case 4: $opt .= " and a.cross_change > 0    "; break;
        }
        //배송비
  		switch( $query_trans_who )
        {
            case 1: $opt .= " and a.trans_who = '선불' "; break;
            case 2: $opt .= " and a.trans_who = '착불' "; break;
        }
       // 판매처
        if ( $shop_id )        
            $opt .= " and a.shop_id = '$shop_id' ";
	    
        //=======================================
        // 검색키워드
        if ($keyword)
        {
            switch ( $search_type )
            {
                // 수령자
                case 0: 
                    $opt .= " and a.recv_name like '%$keyword%' ";
                    break;
                // 수령자 전화
                case 1: 
                    if( strlen($keyword) > 4 )
                        $opt .= " and a.recv_tel = '$keyword' ";
                    else
                        $opt .= " and a.recv_tel like '%$keyword%' ";
                    break;
                // 수령자 핸드폰
                case 2: 
                    if( strlen($keyword) > 4 )
                        $opt .= " and a.recv_mobile = '$keyword' ";
                    else
                        $opt .= " and a.recv_mobile like '%$keyword%' ";
                    break;
                // 주문자
                case 3: 
                    $opt .= " and a.order_name = '$keyword' ";
                    break;
                // 주문자 전화
                case 4: 
                    if( strlen($keyword) > 4 )
                        $opt .= " and a.order_tel = '$keyword' ";
                    else
                        $opt .= " and a.order_tel like '%$keyword%' ";
                    break;
                // 주문자 핸드폰
                case 5: 
                    if( strlen($keyword) > 4 )
                        $opt .= " and a.order_mobile = '$keyword' ";
                    else
                        $opt .= " and a.order_mobile like '%$keyword%' ";
                    break;
                // 관리번호
                case 6: 
                    $opt .= " and a.seq='$keyword' ";
                    break;
                // 주문번호
                case 7: 
                    $opt .= " and a.order_id='$keyword' ";
                    break;
                // 송장번호
                case 8: 
                    $opt .= " and a.trans_no='$keyword' ";
                    break;
                // 판매처 상품명
                case 9: 
                    $opt .= " and a.product_name like '%$keyword%' ";
                    break;
                // 판매처 옵션
                case 10: 
                    $opt .= " and a.options like '%$keyword%' ";
                    break;                
                // 판매처 상품코드
                case 11: 
                    $opt .= " and a.shop_product_id='$keyword' ";
                    break;                
                // 판매처 메모
                case 12: 
                    $opt .= " and a.memo like '%$keyword%' ";
                    break;                
            }
        }
        // 전체 주문 개수
        $result_order_cnt = mysql_query( $query_order_cnt . $opt, $connect );
        $data             = mysql_fetch_assoc( $result_order_cnt );
        $total_rows       = $data[b_cnt];
        $total_rows_b     = $data[a_cnt];
                        
        // limit
        $start = (($page ? $page : 1 )-1) * 20;
        $opt .= " limit $start, 20";
debug("출력전 주문수정 : " . $query . $opt);
        $result = mysql_query( $query . $opt, $connect );
          
        return $result;
    }
    
    // 상품명, 옵션, 메모 수정시 업데이트 처리
    function orders_update()
    {
        global $connect, $seq, $product_name, $orders_options, $memo;
        
        $query = "update orders a inner join order_products b on a.seq=b.order_seq
                     set a.product_name = '$product_name',
                         b.shop_options = '$orders_options',
                         a.memo         = '$memo' 
                   where b.seq=$seq";
debug("출력전 옵션수정 : " . $query);
        if( mysql_query( $query, $connect ) )
            $arr['error'] = 0;
        else
            $arr['error'] = 1;

        echo json_encode($arr);         
    }

    // 매크로 검색
    function macro_search(&$total_rows)
    {
        global $connect, $search_type, $str_from, $str_to, $page, $line_per_page;
        
        $query = "select * from check_text ";
        switch ( $search_type )
        {
            // 상품명
            case 1: $opt .= " check_type=1 "; break;        
            // 옵션
            case 2: $opt .= " check_type=2 "; break;
            // 메모
            case 3: $opt .= " check_type=3 "; break;
        }      

        if( $str_from )
            $opt .= ($search_type ? " and " : "" ) . " str_from like '%$str_from%' ";
        
        if( $str_to )
            $opt .= ($search_type ? " and " : "" ) . " str_to like '%$str_to%' ";
    
        $query .= ($opt ? " where " . $opt : "") . " order by reg_date desc ";
        // 매크로 갯수         
        $result = mysql_query( $query, $connect );
        $total_rows = mysql_num_rows($result);
        
        // limit
        $start = (($page ? $page : 1 )-1) * $line_per_page;
        $query .= " limit $start, $line_per_page";
        $result = mysql_query( $query, $connect );
        
        return $result;  
    }

    // 매크로 변경전, 변경후 업데이트
    function macro_update()
    {
        global $connect, $seq, $str_from, $str_to;
        
        $arr = array();

        // check_type 구하기
        $query = "select check_type from check_text where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 변경전 항목 중복 체크
        $query = "select str_from from check_text where seq<>$seq and check_type='$data[check_type]' and str_from='$str_from'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
        {
            $arr['error'] = 1;
            echo json_encode($arr);
            return;
        }       
        
        $query = "update check_text 
                     set str_from = '$str_from',
                         str_to = '$str_to' 
                   where seq=$seq";
debug("매크로 업데이트 : " . $query);
        if( mysql_query( $query, $connect ) )
            $arr['error'] = 0;
        else
            $arr['error'] = 2;

        echo json_encode($arr);            
    }
    
    // 매크로 신규 등록
    function macro_create()
    {
        global $connect, $str_from, $str_to, $search_type;
        $arr = array();
        
        // 변경전 항목 중복 체크
        $query = "select str_from from check_text where check_type='$search_type' and str_from='$str_from'";        
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
        {
            $arr['error'] = 1;
            echo json_encode($arr);
            return;
        }
        else
        {
            $query = "insert check_text 
                         set check_type = '$search_type', 
                         str_from = '$str_from',
                         str_to = '$str_to',
                         reg_date = now(),
                         worker = '$_SESSION[LOGIN_NAME]'";
            if ( mysql_query( $query, $connect ) )
                $arr['error'] = 0;
            else
                $qrr['error'] = 2;
                
            echo json_encode($arr);
        }
    }

    // 매크로 삭제
    function delete()
    {
        global $connect, $macro_list;

        if( $macro_list )
        {
            $sql = "delete from check_text where seq in ($macro_list)";
debug("매크로 삭제 : " . $sql);
            mysql_query($sql, $connect) or die(mysql_error());
        }

        echo "<script>document.location.href = '?template=D910';</script>";
        exit;
    }

    // 전체 적용
    function total_apply()
    {
        global $connect;
        
        // check_text 정보
        $query = "select seq, check_type, str_from, str_to from check_text order by reg_date";
        if( _DOMAIN_ != 'ecoskin' )
            $query .= " desc";
        $result_check_text = mysql_query( $query, $connect );
        $arr = array();
$i = 0;
        // 타입 기준으로 while문 수행
        while ( $data_check_text = mysql_fetch_assoc( $result_check_text ) )
        {            
            $str_from = $data_check_text[str_from];
            $str_to = $data_check_text[str_to];

            // 변경전 값이 space(숫자) 아니면
            if( !preg_match( '/^space\([0-9]+\)$/', $str_from ) )
            {
                $str_from_search = str_replace(array('%','_'), array('\\%','\\_'), addslashes($str_from));
                $str_from = "'" . addslashes($str_from) . "'";
            }
                
            // 변경후 값이 space(숫자) 아니면
            if( !preg_match( '/^space\([0-9]+\)$/', $str_to ) )
                $str_to = "'" . addslashes($str_to) . "'";

            switch($data_check_text[check_type])
            {  
                case 1: //상품명
                    $query = "update orders set product_name = replace( product_name, $str_from, $str_to ) where status=1";
                    break;
                case 2: //옵션
                    $query = "update orders a, 
                                     order_products b  
                                 set b.shop_options = replace( b.shop_options, $str_from, $str_to ) 
                               where a.seq = b.order_seq
                                 and a.status=1 
                                 and a.order_cs not in (1,3)
                                 and b.shop_options like '%$str_from_search%' ";
                    break;
                case 3: //메모
                    $query = "update orders set memo = replace( memo, $str_from, $str_to ) where status=1";
                    break;
                default:
                    continue 2;
            }
debug( "옵션수정(".$i++."):".$query);
            mysql_query( $query, $connect );
        }
    }
    
    //다운로드
    function save_file()
    {
        global $connect;

        // 엑셀 헤더
        $list_data = array();
        $list_data[] = array(
            "check_type"   => "타입",
            "str_from"     => "변경전",
            "str_to"       => "변경후",
            "reg_date"     => "등록일",
            "worker"       => "등록자"
        );

        $query = "select * from check_text order by reg_date desc";
        
        $result = mysql_query( $query, $connect );
        
        while( $data = mysql_fetch_assoc($result) )
        {
            if ( $data[check_type] == 1 )      $check_type="상품명";
            else if ( $data[check_type] == 2 ) $check_type="옵션";
            else if ( $data[check_type] == 3 ) $check_type="메모";
            $list_data[] = array(
                check_type   => $check_type,
                str_from     => $data[str_from ],
                str_to       => $data[str_to ],
                reg_date     => $data[reg_date ],
                worker       => $data[worker ]
            );
        }
        $this->make_file( $list_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                    <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
                    <body>
                    <html><table border=1>
                    ";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {
                    // 숫자
                    if( $key == 'xxx' )
                        $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";'>" . $value . "</td>";
                    // 문자
                    else
                        $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
                }
            }            
            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
    }
   
    /////////////////////////////////////
    //  파일 다운받기
    function download2()
    {
        global $filename;        
        $obj = new class_file();
        $obj->download_file( $filename, "macro_list.xls");
    }  

    function D920()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";         
    }

    function upload()
    {
        global $connect;
        
        $obj = new class_file();
        $arr_data =  $obj->upload();

        foreach ($arr_data as $data )
        {
            if( $data[0] == "상품명" )
                $type = 1;
            else if( $data[0] == "옵션" )
                $type = 2;
            else if( $data[0] == "메모" )
                $type = 3;
            else
                continue;
            
            $query = "insert check_text
                         set str_from   = '" . addslashes( $data[1] ) . "',
                             str_to     = '" . addslashes( $data[2] ) . "',
                             check_type = $type,
                             reg_date   = now(),
                             worker     = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query, $connect);
        }
        
        echo "
        <script language='javascript'>
        alert('등록완료');
        parent.hide_waiting();
        </script>
        ";        
    }     
}//end
