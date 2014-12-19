<?
require_once "class_E.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_top.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_shop.php";
require_once "class_file.php";
require_once "class_supply.php";
require_once "ExcelReader/reader.php";
require_once "lib/ez_excel_lib.php";

////////////////////////////////
// class name: class_DD00
//
class class_DD00 extends class_top 
{
   var $order_id;
   var $debug = "on"; // 전체 download: on/off
   var $format;
   var $font   = 'Arial'; 
   var $size   = 10; 
   var $align  = 'right'; 
   var $valign = 'vcenter'; 
   var $bold   = 0; 
   var $italic = 0; 

    function DD00()
    {
        global $template, $connect, $link_url_list, $line_per_page;
        global $title, $pid, $pid_ex, $shop_pid, $shop_id, $shop_pid_ex, $trans_free, $gift_msg, $product;
		global $multi_supply_group, $multi_supply, $str_supply_code;

        $line_per_page = 20;
        if( $_REQUEST["page"] )
        {
            $result = $this->get_list( &$total_rows, $page );
        }
        
        $par_arr = array("template","action","title","pid","pid_ex","shop_pid","shop_id","shop_pid_ex",
                         "trans_free","gift_msg","product","page","multi_supply_group","multi_supply","str_supply_code");
        $link_url_list = $this->build_link_par($par_arr);     

       
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // 사은품 추가 팝업
    function DD01()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 사은품 팝업
    function DD02()
    {
        global $template, $connect, $seq;
        
        $query = "select * from new_gift where seq=$seq";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
            $data = mysql_fetch_assoc($result);
        else
            echo "<script>alert('삭제된 사은품 설정입니다.');self.close()</script>";
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 사은품 일괄처리
    function DD03()
    {
        global $template, $connect, $seq;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 어드민 상품코드 검색 팝업
    function DD04()
    {
        global $template, $connect,$page, $link_url_list;
        global $supply_code, $product_name, $options, $org_only;
        
        $par_arr = array("template","action","supply_code","product_name","options","page");
        $link_url_list = $this->build_link_par($par_arr);  
        
        if( $_REQUEST[page] )
        {
            $query = "select * from products where is_delete=0 ";
            if( $supply_code )
                $query .= " and supply_code=$supply_code ";
            if( $product_name )
                $query .= " and name like '%$product_name%' ";
            if( $options )
                $query .= " and options like '%" . str_replace(" ","%",$options) . "%'";
            if( $org_only )
                $query .= " and substring(product_id,1,1) <> 'S' ";
            
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);

            $line_per_page = 20;
            if( $page < 1 )  $page = 1;
            $query .= " limit " . ($page-1)*$line_per_page . ", " . $line_per_page;
            $result = mysql_query($query, $connect);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 사은품 상품코드 검색 팝업
    function DD05()
    {
        global $template, $connect,$page, $link_url_list;
        global $supply_code, $product_name, $options;
        
        $par_arr = array("template","action","supply_code","product_name","options","page");
        $link_url_list = $this->build_link_par($par_arr);  
        
        if( $_REQUEST[page] )
        {
            $query = "select * from products where is_represent=0 and is_delete=0 ";
            if( $supply_code )
                $query .= " and supply_code=$supply_code ";
            if( $product_name )
                $query .= " and name like '%$product_name%' ";
            if( $options )
                $query .= " and options like '%" . str_replace(" ","%",$options) . "%'";
                
            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);

            $line_per_page = 20;
            if( $page < 1 )  $page = 1;
            $query .= " limit " . ($page-1)*$line_per_page . ", " . $line_per_page;
            $result = mysql_query($query, $connect);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function DD06()
    {
        global $template, $connect, $link_url_list, $line_per_page;
        global $title, $pid, $pid_ex, $shop_pid, $shop_id, $shop_pid_ex, $trans_free, $gift_msg, $product;

        $line_per_page = 20;
        if( $_REQUEST["page"] )
        {
            $result = $this->get_list_log( &$total_rows, $page );
        }
        
        $par_arr = array("template","action","title","pid","pid_ex","shop_pid","shop_id","shop_pid_ex",
                         "trans_free","gift_msg","product","page");
        $link_url_list = $this->build_link_par($par_arr);     

       
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // 사은품 로그 팝업
    function DD07()
    {
        global $template, $connect, $id;
        
        $query = "select * from new_gift_log where id=$id";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 사은품 추가
    function add_gift()
    {
        global $connect, $title, $pid, $pid_ex, $shop_id, $shop_pid, $shop_pid_ex, $deal_no_ex,
               $qty_start, $qty_end, $price_start, $price_end, $price_flag, $all_price_flag, $trans_free, $gift_msg, $only_flag, 
               $product, $qty_flag, $start_date, $end_date, $qty_multi, $pay_type, $deal_no, $start_hour, $end_hour, $random_gift;

        // 공백, 줄바꿈 제거
        $arr_space = array(" ","\n","\r","\t");
        $pid      = str_replace($arr_space, "", $pid     );
        $shop_pid = str_replace($arr_space, "", $shop_pid);
        $product  = str_replace($arr_space, "", $product );
        
        $pay_type = trim( $pay_type );

        $query = "insert new_gift
                     set pid         = '$pid',
                         shop_id     = '$shop_id',
                         shop_pid    = '$shop_pid',
                         qty_start   = '$qty_start',
                         qty_end     = '$qty_end',
                         price_start = '$price_start',
                         price_end   = '$price_end',
                         gift_msg    = '$gift_msg',
                         trans_free  = '" . ($trans_free=="on" ? 1 : 0) . "',
                         random_gift = '" . ($random_gift=="on" ? 1 : 0) . "',
                         pid_ex      = '" . ($pid_ex=="on" ? 1 : 0) . "',
                         shop_pid_ex = '" . ($shop_pid_ex=="on" ? 1 : 0) . "',
                         deal_no_ex = '" . ($deal_no_ex=="on" ? 1 : 0) . "',
                         only_flag   = '" . ($only_flag=="on" ? 1 : 0) . "',
                         worker      = '$_SESSION[LOGIN_NAME]',
                         crdate      = now(),
                         product     = '$product',
                         qty_flag    = '" . ($qty_flag=="on" ? 1 : 0) . "',
                         price_flag  = '" . ($price_flag=="on" ? 1 : 0) . "',
                         all_price_flag  = '" . ($all_price_flag=="on" ? 1 : 0) . "',
                         title       = '$title',
                         start_date  = '$start_date',
                         end_date    = '$end_date',
                         start_hour  = '$start_hour',
                         end_hour    = '$end_hour',
                         qty_multi   = '$qty_multi',
                         pay_type    = '$pay_type',
                         deal_no     = '$deal_no'";
        mysql_query($query, $connect);
        
        echo "<script>self.close()</script>";
    }
    
    function log_gift($seq, $work_type)
    {
        global $connect;
        
        $query = "select * from new_gift where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $query = "insert new_gift_log 
                     set person = '$_SESSION[LOGIN_NAME]', 
                         reg_date = now(), 
                         work_type = $work_type";
        foreach( $data as $key => $val )
            $query .= ",$key='$val'";
debug( $query );
        mysql_query($query, $connect);
    }
    
    function get_list(&$total_rows, $page, $download=0)
    {
        global $connect, $template, $line_per_page, $page;
        global $title, $pid, $pid_ex, $shop_id, $shop_pid, $shop_pid_ex, $trans_free, $gift_msg, $product;
		global $multi_supply_group, $multi_supply, $str_supply_code;
		
        $options = "";
        if( $title       )  $options .= ($options ? " and"  : "") . " title like '%$title%'";
        if( $pid         )  $options .= ($options ? " and"  : "") . " pid like '%$pid%'";
        if( $pid_ex      )  $options .= ($options ? " and"  : "") . " pid_ex=1";
        if( $shop_id     )  $options .= ($options ? " and"  : "") . " shop_id = '$shop_id'";
        if( $shop_pid    )  $options .= ($options ? " and"  : "") . " shop_pid like '%$shop_pid%'";
        if( $shop_pid_ex )  $options .= ($options ? " and"  : "") . " shop_pid_ex=1";
        if( $trans_free  )  $options .= ($options ? " and"  : "") . " trans_free=1";
        if( $gift_msg    )  $options .= ($options ? " and"  : "") . " gift_msg like '%$gift_msg%'";
        if( $product     )  $options .= ($options ? " and"  : "") . " product like '%$product%'";
        
        
        //공급처검색 으로 걸려있는 어드민상품 찾기.
        $supply_in_seq = "";
        if( $str_supply_code  ||  $multi_supply)
        {
        	$query = "SELECT seq, pid FROM new_gift WHERE pid >'' ";
        	$result = mysql_query($query, $connect);
        	while ( $data = mysql_fetch_array ( $result ) )
        	{
				$_pid = explode(',', $data[pid]);
				foreach ( $_pid as $idx => $val)
					$_pid[$idx] = "'".$val."'";
				$_pid = implode(',',$_pid);
        		
        		$_query = "SELECT count(*) cnt FROM products WHERE product_id IN ($_pid) ";        		
        		
				if( $str_supply_code )
					$_query .= " and supply_code in ( $str_supply_code ) ";
				if($multi_supply)
					$_query .= " and supply_code in ( $multi_supply ) ";
        		
        		$_result = mysql_query($_query, $connect);
        		$_data = mysql_fetch_array ( $_result );
        		if($_data[cnt] > 0)
        			$supply_in_seq 	= $supply_in_seq ? $supply_in_seq .",".$data[seq] : $data[seq];        		
        	}
        }
        if( $supply_in_seq )  $options .= ($options ? " and"  : "") . " seq in ($supply_in_seq)";
        
        
        if( $download )
        {
            $query = "select * from new_gift" . ($options ? " where $options" : "") . " order by seq desc";
            $result = mysql_query($query, $connect);
        }
        else
        {
            // 전체 카운트
            $query = "select count(seq) cnt from new_gift" . ($options ? " where $options" : "");
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $total_rows = $data[cnt];
            
            if( !$page ) $page = 1;
            $limit = " limit " . ($page-1) * $line_per_page . "," . $line_per_page ;

            $query = "select * from new_gift" . ($options ? " where $options" : "") . " order by seq desc $limit";
            
            debug ($query);
            $result = mysql_query($query, $connect);
        }
        
        return $result;
    }

    // 사은품 로그 조회
    function get_list_log(&$total_rows, $page, $download=0)
    {
        global $connect, $template, $line_per_page, $page;
        global $title, $pid, $pid_ex, $shop_id, $shop_pid, $shop_pid_ex, $trans_free, $gift_msg, $product;

        $options = "";
        if( $title       )  $options .= ($options ? " and"  : "") . " title like '%$title%'";
        if( $pid         )  $options .= ($options ? " and"  : "") . " pid like '%$pid%'";
        if( $pid_ex      )  $options .= ($options ? " and"  : "") . " pid_ex=1";
        if( $shop_id     )  $options .= ($options ? " and"  : "") . " shop_id = '$shop_id'";
        if( $shop_pid    )  $options .= ($options ? " and"  : "") . " shop_pid like '%$shop_pid%'";
        if( $shop_pid_ex )  $options .= ($options ? " and"  : "") . " shop_pid_ex=1";
        if( $trans_free  )  $options .= ($options ? " and"  : "") . " trans_free=1";
        if( $gift_msg    )  $options .= ($options ? " and"  : "") . " gift_msg like '%$gift_msg%'";
        if( $product     )  $options .= ($options ? " and"  : "") . " product like '%$product%'";
        
        if( $download )
        {
            $query = "select * from new_gift_log" . ($options ? " where $options" : "") . " order by reg_date desc";
            $result = mysql_query($query, $connect);
        }
        else
        {
            // 전체 카운트
            $query = "select count(seq) cnt from new_gift_log" . ($options ? " where $options" : "");
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $total_rows = $data[cnt];
            
            if( !$page ) $page = 1;
            $limit = " limit " . ($page-1) * $line_per_page . "," . $line_per_page ;

            $query = "select * from new_gift_log" . ($options ? " where $options" : "") . " order by reg_date desc $limit";
            $result = mysql_query($query, $connect);
        }
        
        return $result;
    }

    function get_status($start_date, $end_date)
    {
        $today = strtotime( date("Y-m-d",strtotime("now")) ); // 오늘 0시 0분 0초를 설정
        $s_date = strtotime( $start_date );
        $e_date = strtotime( $end_date );
        
        if( $today < $s_date )
            return "<font color=blue>적용전</font>";
        else if( $today <= $e_date )
            return "<font color=red><b>적용중</b></font>";
        else
            return "<font color=black>종료</font>";
    }

    // 어드민 상품코드, 사은품 상품코드 체크
    function check_pid()
    {
        global $connect, $pid, $product;
        
        $val = array();
        
        // 공백, 줄바꿈 제거
        $arr_space = array(" ","\n","\r","\t");
        $pid      = str_replace($arr_space, "", $pid     );
        $shop_pid = str_replace($arr_space, "", $shop_pid);
        $product  = str_replace($arr_space, "", $product );

        $pid_cnt     = count( array_unique(explode(",",$pid    )) );
        $product_cnt = count( array_unique(explode(",",$product)) );
        
        // 어드민 상품코드 체크
        if( $pid )
        {
            $pid = "'" . str_replace(",","','",$pid) . "'";
            $query = "select count(product_id) as cnt from products where product_id in ($pid) and is_delete=0";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            if( $pid_cnt != $data[cnt] )
            {
                $val['error'] = 1;
                echo json_encode( $val );
                return;
            }
        }
        
        // 사은품 상품코드 체크
        if( $product )
        {
            foreach( explode(",", $product) as $p_val )
            {
                $p_val = trim($p_val);
                if( !$p_val )  continue;
                
                $query = "select product_id from products where product_id = '$p_val' and is_delete=0 and is_represent=0";

                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) == 0 )
                {
                    $val['error'] = 2;
                    echo json_encode( $val );
                    return;
                }
            }
        }
                
        $val['error'] = 0;
        echo json_encode( $val );
    }
    
    function save_gift()
    {
        global $template, $connect;
        global $seq, $title, $pid, $pid_ex, $shop_pid, $shop_pid_ex, $qty_start, $qty_end, $price_start, $price_end, $random_gift, $deal_no_ex,
               $price_flag, $all_price_flag, $trans_free, $gift_msg, $only_flag, $product, $qty_flag, $start_date, $end_date, $shop_id, $qty_multi, $pay_type, $deal_no, $start_hour, $end_hour;
        
        // 로그
        $this->log_gift($seq, 2);

        // 공백, 줄바꿈 제거
        $arr_space = array(" ","\n","\r","\t");
        $pid      = str_replace($arr_space, "", $pid     );
        $shop_pid = str_replace($arr_space, "", $shop_pid);
        $product  = str_replace($arr_space, "", $product );
        
        $pay_type = trim($pay_type);
        $deal_no = trim($deal_no);

        $pid_ex      = ($pid_ex     =="on" ? 1 : 0);
        $shop_pid_ex = ($shop_pid_ex=="on" ? 1 : 0);
        $deal_no_ex = ($deal_no_ex=="on" ? 1 : 0);
        
        $price_flag  = ($price_flag =="on" ? 1 : 0);
        $all_price_flag  = ($all_price_flag =="on" ? 1 : 0);
        $trans_free  = ($trans_free =="on" ? 1 : 0);
        $random_gift = ($random_gift=="on" ? 1 : 0);
        $only_flag   = ($only_flag  =="on" ? 1 : 0);
        $qty_flag    = ($qty_flag   =="on" ? 1 : 0);
        
        $query = "update new_gift
                     set title = '$title'
                         ,pid         = '$pid'        
                         ,shop_id     = '$shop_id'    
                         ,shop_pid    = '$shop_pid'   
                         ,qty_start   = '$qty_start'  
                         ,qty_end     = '$qty_end'    
                         ,price_start = '$price_start'
                         ,price_end   = '$price_end'  
                         ,gift_msg    = '$gift_msg'   
                         ,trans_free  = '$trans_free' 
                         ,random_gift = '$random_gift' 
                         ,pid_ex      = '$pid_ex'     
                         ,shop_pid_ex = '$shop_pid_ex'                         
                         ,only_flag   = '$only_flag'  
                         ,product     = '$product'    
                         ,qty_flag    = '$qty_flag'   
                         ,price_flag  = '$price_flag' 
                         ,all_price_flag  = '$all_price_flag' 
                         ,title       = '$title'      
                         ,start_date  = '$start_date' 
                         ,end_date    = '$end_date'
                         ,start_hour  = '$start_hour' 
                         ,end_hour    = '$end_hour'
                         ,qty_multi   = '$qty_multi'
                         ,pay_type    = '$pay_type'
                         ,deal_no     = '$deal_no'
                         ,deal_no_ex = '$deal_no_ex'
                         ,crdate      = now()
                   where seq = $seq";
debug("사은품 : " . $query);
        mysql_query($query, $connect);
        
        $this->redirect("?template=DD02&seq=$seq");
    }
    
    function delete_gift()
    {
        global $template, $connect, $seq;
        
        // 로그
        $this->log_gift($seq, 3);

        $query = "delete from new_gift where seq=$seq";
        mysql_query($query, $connect);
        
        echo "<script>self.close()</script>";
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $template, $connect, $page;
        global $title, $pid, $pid_ex, $shop_id, $shop_pid, $shop_pid_ex, $trans_free, $gift_msg, $product;
		global $multi_supply_group, $multi_supply, $str_supply_code;
		
        // 엑셀 헤더
        $excel_data = array();
        $excel_data[] = array(
            "seq"          => "번호",                 
            "title"        => "사은품 이름",             
            "crdate"       => "생성일",
            "worker"       => "생성자",
            "pid"          => "어드민 상품코드",           
            "pid_ex"       => "어드민 상품코드 제외",        
            "shop_id"      => "판매처코드",                
            "shop_name"    => "판매처명",                
            "shop_pid"     => "판매처 상품코드",           
            "shop_pid_ex"  => "판매처 상품코드 제외",        
            "qty_start"    => "상품수량최소",             
            "qty_end"      => "상품수량최대",             
            "price_start"  => "주문금액최소",             
            "price_end"    => "주문금액최소",             
            "price_flag"   => "자체판매가",              
            "all_price_flag"   => "전체판매가",              
            "trans_free"   => "무료배송",               
            "gift_msg"     => "사은품 내용",             
            "only_flag"    => "중복불가",               
            "product"      => "사은품 상품",             
            "qty_flag"     => "수량만큼",
            "start_date"   => "시작일",
            "end_date"     => "종료일",
            "status"       => "상태",
            "work"         => "작업",
            "qty_multi"    => "배수",
            "pay_type"     => "결제수단",
            "deal_no"      => "딜번호",
            "deal_no_ex"   => "딜번호 제외",
            "start_hour"   => "시작시간",
            "end_hour"     => "종료시간",
            "random_gift"  => "랜덤적용"
        );

        $result = $this->get_list( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $info = array(
                "seq"          => $data[seq        ] ?  $data[seq        ] : "",
                "title"        => $data[title      ] ?  $data[title      ] : "",
                "crdate"       => $data[crdate     ] ?  $data[crdate     ] : "",
                "worker"       => $data[worker     ] ?  $data[worker     ] : "",
                "pid"          => $data[pid        ] ?  $data[pid        ] : "",
                "pid_ex"       => $data[pid_ex     ] ?  $data[pid_ex     ] : "",
                "shop_id"      => $data[shop_id    ] ?  $data[shop_id    ] : "",
                "shop_name"    => $data[shop_id    ] ?  class_shop::get_shop_name($data[shop_id]) : "",
                "shop_pid"     => $data[shop_pid   ] ?  $data[shop_pid   ] : "",
                "shop_pid_ex"  => $data[shop_pid_ex] ?  $data[shop_pid_ex] : "",
                "qty_start"    => $data[qty_start  ] ?  $data[qty_start  ] : "",
                "qty_end"      => $data[qty_end    ] ?  $data[qty_end    ] : "",
                "price_start"  => $data[price_start] ?  $data[price_start] : "",
                "price_end"    => $data[price_end  ] ?  $data[price_end  ] : "",
                "price_flag"   => $data[price_flag ] ?  $data[price_flag ] : "",
                "all_price_flag"   => $data[all_price_flag ] ?  $data[all_price_flag ] : "",
                "trans_free"   => $data[trans_free ] ?  $data[trans_free ] : "",
                "gift_msg"     => $data[gift_msg   ] ?  $data[gift_msg   ] : "",
                "only_flag"    => $data[only_flag  ] ?  $data[only_flag  ] : "",
                "product"      => $data[product    ] ?  $data[product    ] : "",
                "qty_flag"     => $data[qty_flag   ] ?  $data[qty_flag   ] : "",
                "start_date"   => $data[start_date ] ?  $data[start_date ] : "",
                "end_date"     => $data[end_date   ] ?  $data[end_date   ] : "",
                "status"       => $this->get_status($data[start_date], $data[end_date]),
                "work"         => "",
                "qty_multi"    => $data[qty_multi  ] ?  $data[qty_multi  ] : "",
                "pay_type"     => $data[pay_type   ] ?  $data[pay_type   ] : "",
                "deal_no"      => $data[deal_no    ] ?  $data[deal_no    ] : "",
                "deal_no_ex"  => $data[deal_no_ex] ?  $data[deal_no_ex] : "",
                "start_hour"   => $data[start_hour ],
                "end_hour"     => $data[end_hour   ],
                "random_gift"  => $data[random_gift] ?  $data[random_gift] : ""
            );
            $excel_data[] = $info;
        }
        $this->make_file( $excel_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

   function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
br
	{mso-data-placement:same-cell;}
</style>
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
                    if( $key == 'pid_ex' || $key == 'shop_pid_ex' || $key == 'qty_start' || $key == 'qty_end' || $key == 'deal_no_ex' ||
                        $key == 'price_start' || $key == 'price_end' || $key == 'price_flag'|| $key == 'all_price_flag' || $key == 'trans_free' ||
                        $key == 'only_flag' || $key == 'qty_flag' || $key == 'seq' || $key == 'qty_multi' )
                        $buffer .= "<td class=num_item>" . $value . "</td>";
                    else if( 0 )
                        $buffer .= "<td class=mul_item>" . str_replace("\n", "<br>", $value) . "</td>";
                    else
                        $buffer .= "<td class=str_item>" . $value . "</td>";
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

    //////////////////////////////////////
    // 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "gift_list.xls");
    }    

    //////////////////////////////////////
    // 파일 업로드
    function upload()
    {
        global $connect, $admin_file, $_file;
        
        $transaction = $this->begin("사은품 일괄처리");
        
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();

        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
debug_array($arr);
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( $i == $row_cnt+1 ) continue;  // 마지막행
            
            $qty_multi = ( $row[25] >= 1 ? $row[25] : 1 );
            $arr_list = array(
                seq         => $row[0] ,
                title       => $row[1] ,
                pid         => $row[4] ,
                pid_ex      => $row[5] ,
                shop_id     => $row[6] ,
                shop_pid    => $row[8] ,
                shop_pid_ex => $row[9] ,
                qty_start   => $row[10],
                qty_end     => $row[11],
                price_start => $row[12],
                price_end   => $row[13],
                price_flag  => $row[14],
                all_price_flag  => $row[15],
                trans_free  => $row[16],
                gift_msg    => $row[17],
                only_flag   => $row[18],
                product     => $row[19],
                qty_flag    => $row[20],
                start_date  => $row[21],
                end_date    => $row[22],
                work        => $row[24],
                qty_multi   => $qty_multi,
                pay_type    => $row[26],
                deal_no     => $row[27],
                deal_no_ex	=> $row[28],
                start_hour  => ($row[29] == "" ? 0 : $row[29]),
                end_hour    => ($row[30] == "" ? 23 : $row[30]),
                random_gift => $row[31]
            );
debug_array($arr_list);
            // 작업 구분
            switch( strtoupper($arr_list[work]) )
            {
                case "A":
                    if( !$this->add_gift_file($arr_list, &$err_cnt, &$err_result, $i) )  continue 2;
                    break;
                case "M":
                    if( !$this->modify_gift_file($arr_list, &$err_cnt, &$err_result, $i) )  continue 2;
                    break;
                case "D":
                    if( !$this->delete_gift_file($arr_list, &$err_cnt, &$err_result, $i) )  continue 2;
                    break;
                default:
                    if( $err_cnt++ < 20 )
                        $err_result .= " $i 행 : 작업 타입을 입력하세요 <br> ";
                    continue 2;
            }
            
            if ( $i % 10 == 0 )
            $this->show_txt( $i . "/" . count($arr));          
            $n++;
        }
        
        $this->hide_wait();
        $this->jsAlert("$n 개 입력 완료 되었습니다.");
        
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=DD03&err_cnt=$err_cnt&err_result=$err_result");
    }

    function add_gift_file($arr_list, &$err_cnt, &$err_result, $i)
    {
        global $connect;
        
        // 사은품 이름 확인
        if( !$arr_list[title] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '사은품 이름'을 입력하세요. <br> ";
            return false;
        }

        // 판매처 코드 확인
        if( $arr_list[shop_id] && !class_shop::get_shop_name( $arr_list[shop_id] ) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '판매처 코드'가 잘못되었습니다. <br> ";
            return false;
        }

        // 사은품 조건 확인
        if( !$arr_list[qty_start] && !$arr_list[qty_end] && !$arr_list[price_start] && !$arr_list[price_end] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '상품수량' 또는 '주문금액' 조건을 하나 이상 입력하세요. <br> ";
            return false;
        }
        
        // 사은품 설정 확인
        if( !$arr_list[trans_free] && !$arr_list[gift_msg] && !$arr_list[product] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '무료배송' 또는 '사은품 내용' 또는 '사은품 상품'을 하나 이상 입력하세요. <br> ";
            return false;
        }
        
        // 시작일 포멧 확인
        if( !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $arr_list[start_date]) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '시작일' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 종료일 포멧 확인
        if( !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $arr_list[end_date]) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '종료일' 포멧이 잘못되었습니다. <br> ";
            return false;
        }

        // 시작시간
        if( $arr_list[start_hour] < 0 or $arr_list[start_hour] > 23 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '시작시간' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 종료시간
        if( $arr_list[end_hour] < 0 or $arr_list[end_hour] > 23 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '종료시간' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 배수 확인
        if( $arr_list[qty_multi] < 1 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '배수' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 상품코드 확인 - 조건
        $id_arr = explode(",", $arr_list[pid]);
        
        $p_arr = array();
        foreach( $id_arr as $p_val )
        {
            if( trim($p_val) )
                $p_arr[] = "'" . trim($p_val) . "'";
        }
            
        $p_cnt = count( $p_arr );
        $p_str = implode(",", $p_arr);
        
        // 상품 테이블에서 검색
        if( $p_str ) 
        {
            $query = "select product_id from products where is_delete=0 and product_id in ($p_str)";
            $result = mysql_query($query, $connect);
            // 수량이 작으면 
            if( mysql_num_rows($result) < $p_cnt )
            {
                // 미등록 상품코드 찾기
                while( $data = mysql_fetch_assoc($result) )
                {
                    $new_arr = array();
                    foreach( $id_arr as $id_val )
                    {
                        if( $id_val !== $data[product_id] )
                            $new_arr[] = $id_val;
                    }
                    $id_arr = $new_arr;
                }
                
                if( $id_arr )
                {
                    if( $err_cnt++ < 20 )
                        $err_result .= " $i 행 : 잘못된 상품코드가 있습니다. (". implode(",",$id_arr) .") <br> ";
                    return false;
                }
            }        
        }

        $query = "insert new_gift set ";
        $j = 0;
        foreach ( $arr_list as $key => $val )
        {
            if( $key == "seq" || $key == "work" )  continue;
            
            $query .= ($j++ ? "," : "");
            $query .= $key . "=\"" . htmlspecialchars(addslashes($val)) . "\"";
        }
        $query .= ", crdate=now(), worker='$_SESSION[LOGIN_NAME]'";

        // 추가
        if( !mysql_query( $query, $connect ) )
        {
            if( $err_cnt++ < 20 )
            {
                $err_result .= " $i 행 : 사은품 추가에 실패했습니다. <br> ";
                if( $_SESSION[LOGIN_LEVEL] == 9 )
                    $err_result .= $query;
            }
            return false;
        }
        
        return true;
    }

    function modify_gift_file($arr_list, &$err_cnt, &$err_result, $i)
    {
        global $connect;
        
        // seq 확인
        $query = "select * from new_gift where seq=$arr_list[seq]";
        $result = mysql_query($query, $connect);
        if( !mysql_num_rows($result) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : 등록되어있지 않은 '번호'입니다. <br> ";
            return false;
        }

        // 사은품 이름 확인
        if( !$arr_list[title] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '사은품 이름'을 입력하세요. <br> ";
            return false;
        }

        // 판매처 코드 확인
        if( $arr_list[shop_id] && !class_shop::get_shop_name( $arr_list[shop_id] ) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '판매처 코드'가 잘못되었습니다. <br> ";
            return false;
        }

        // 사은품 조건 확인
        if( !$arr_list[qty_start] && !$arr_list[qty_end] && !$arr_list[price_start] && !$arr_list[price_end] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '상품수량' 또는 '주문금액' 조건을 하나 이상 입력하세요. <br> ";
            return false;
        }
        
        // 사은품 설정 확인
        if( !$arr_list[trans_free] && !$arr_list[gift_msg] && !$arr_list[product] )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '무료배송' 또는 '사은품 내용' 또는 '사은품 상품'을 하나 이상 입력하세요. <br> ";
            return false;
        }
        
        // 시작일 포멧 확인
        if( !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $arr_list[start_date]) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '시작일' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 종료일 포멧 확인
        if( !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $arr_list[end_date]) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '종료일' 포멧이 잘못되었습니다. <br> ";
            return false;
        }

        // 시작시간
        if( $arr_list[start_hour] < 0 || $arr_list[start_hour] > 23 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '시작시간' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 종료시간
        if( $arr_list[end_hour] < 0 || $arr_list[end_hour] > 23 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '종료시간' 포멧이 잘못되었습니다. <br> ";
            return false;
        }
        
        // 배수 확인
        if( $arr_list[qty_multi] < 1 )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : '배수' 포멧이 잘못되었습니다. <br> ";
            return false;
        }

        // 상품코드 확인 - 조건
        if( $arr_list[pid] )
        {
            $id_arr = explode(",", $arr_list[pid]);
            
            $p_arr = array();
            foreach( $id_arr as $p_val )
                $p_arr[] = "'" . trim($p_val) . "'";
                
            $p_cnt = count( $p_arr );
            $p_str = implode(",", $p_arr);
            
            // 상품 테이블에서 검색
            $query = "select product_id from products where is_delete=0 and product_id in ($p_str)";
            $result = mysql_query($query, $connect);
            // 수량이 작으면 
            if( mysql_num_rows($result) < $p_cnt )
            {
                // 미등록 상품코드 찾기
                while( $data = mysql_fetch_assoc($result) )
                {
                    $new_arr = array();
                    foreach( $id_arr as $id_val )
                    {
                        if( $id_val !== $data[product_id] )
                            $new_arr[] = $id_val;
                    }
                    $id_arr = $new_arr;
                }
                
                if( $id_arr )
                {
                    if( $err_cnt++ < 20 )
                        $err_result .= " $i 행 : 잘못된 상품코드가 있습니다. (". implode(",",$id_arr) .") <br> ";
                    return false;
                }
            }        
        }

        // 로그
        $this->log_gift($arr_list[seq],5);

        $query = "update new_gift set ";
        $j = 0;
        foreach ( $arr_list as $key => $val )
        {
            if( $key == "seq" || $key == "work" )  continue;
            
            $query .= ($j++ ? "," : "");
            $query .= $key . "=\"" . htmlspecialchars(addslashes($val)) . "\"";
        }
        $query .= ", crdate=now(), worker='$_SESSION[LOGIN_NAME]' where seq=$arr_list[seq]";

        // 변경
        if( !mysql_query( $query, $connect ) )
        {
            if( $err_cnt++ < 20 )
            {
                $err_result .= " $i 행 : 사은품 변경에 실패했습니다. <br> ";
                if( $_SESSION[LOGIN_LEVEL] == 9 )
                    $err_result .= $query;
            }
            return false;
        }
        
        return true;
    }
    
    function delete_gift_file($arr_list, &$err_cnt, &$err_result, $i)
    {
        global $connect;
        
        // seq 확인
        $query = "select * from new_gift where seq=$arr_list[seq]";
        $result = mysql_query($query, $connect);
        if( !mysql_num_rows($result) )
        {
            if( $err_cnt++ < 20 )
                $err_result .= " $i 행 : 등록되어있지 않은 '번호'입니다. <br> ";
            return false;
        }

        // 로그
        $this->log_gift($arr_list[seq],6);

        $query = "delete from new_gift where seq=$arr_list[seq]";
        // 삭제
        if( !mysql_query( $query, $connect ) )
        {
            if( $err_cnt++ < 20 )
            {
                $err_result .= " $i 행 : 사은품 삭제에 실패했습니다. <br> ";
                if( $_SESSION[LOGIN_LEVEL] == 9 )
                    $err_result .= $query;
            }
            return false;
        }
        
        return true;
    }

    //////////////////////////////////////
    // 사은품 로그 - 파일 만들기
    function save_file2()
    {
        global $template, $connect, $page;
        global $title, $pid, $pid_ex, $shop_id, $shop_pid, $shop_pid_ex, $trans_free, $gift_msg, $product;

        // 엑셀 헤더
        $excel_data = array();
        $excel_data[] = array(
            "seq"          => "번호",                 
            "title"        => "사은품 이름",             
            "crdate"       => "생성일",
            "worker"       => "생성자",
            "pid"          => "어드민 상품코드",           
            "pid_ex"       => "어드민 상품코드 제외",        
            "shop_id"      => "판매처코드",                
            "shop_name"    => "판매처명",                
            "shop_pid"     => "판매처 상품코드",           
            "shop_pid_ex"  => "판매처 상품코드 제외",        
            "qty_start"    => "상품수량최소",             
            "qty_end"      => "상품수량최대",             
            "price_start"  => "주문금액최소",             
            "price_end"    => "주문금액최소",             
            "price_flag"   => "자체판매가",              
            "all_price_flag"   => "전체판매가",              
            "trans_free"   => "무료배송",               
            "gift_msg"     => "사은품 내용",             
            "only_flag"    => "중복불가",               
            "product"      => "사은품 상품",             
            "qty_flag"     => "수량만큼",
            "start_date"   => "시작일",
            "end_date"     => "종료일",
            "work_type"    => "작업",
            "qty_multi"    => "배수",
            "pay_type"     => "결제수단",
            "deal_no"      => "딜번호",
            "deal_no_ex"   => "딜번호 제외",
            "start_hour"   => "시작시간",
            "end_hour"     => "종료시간",
            "random_gift"  => "랜덤적용",
            "person"       => "작업자",
            "reg_date"     => "작업일"
        );

        $result = $this->get_list_log( &$cnt_all, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            switch( $data[work_type] )
            {
                case 1: $work_type = "생성"; break;
                case 2: $work_type = "변경"; break;
                case 3: $work_type = "삭제"; break;
                case 4: $work_type = "일괄생성"; break;
                case 5: $work_type = "일괄변경"; break;
                case 6: $work_type = "일괄삭제"; break;
            }
            
            $info = array(
                "seq"          => $data[seq        ] ?  $data[seq        ] : "",
                "title"        => $data[title      ] ?  $data[title      ] : "",
                "crdate"       => $data[crdate     ] ?  $data[crdate     ] : "",
                "worker"       => $data[worker     ] ?  $data[worker     ] : "",
                "pid"          => $data[pid        ] ?  $data[pid        ] : "",
                "pid_ex"       => $data[pid_ex     ] ?  $data[pid_ex     ] : "",
                "shop_id"      => $data[shop_id    ] ?  $data[shop_id    ] : "",
                "shop_name"    => $data[shop_id    ] ?  class_shop::get_shop_name($data[shop_id]) : "",
                "shop_pid"     => $data[shop_pid   ] ?  $data[shop_pid   ] : "",
                "shop_pid_ex"  => $data[shop_pid_ex] ?  $data[shop_pid_ex] : "",
                "qty_start"    => $data[qty_start  ] ?  $data[qty_start  ] : "",
                "qty_end"      => $data[qty_end    ] ?  $data[qty_end    ] : "",
                "price_start"  => $data[price_start] ?  $data[price_start] : "",
                "price_end"    => $data[price_end  ] ?  $data[price_end  ] : "",
                "price_flag"   => $data[price_flag ] ?  $data[price_flag ] : "",
                "all_price_flag"   => $data[all_price_flag ] ?  $data[all_price_flag ] : "",
                "trans_free"   => $data[trans_free ] ?  $data[trans_free ] : "",
                "gift_msg"     => $data[gift_msg   ] ?  $data[gift_msg   ] : "",
                "only_flag"    => $data[only_flag  ] ?  $data[only_flag  ] : "",
                "product"      => $data[product    ] ?  $data[product    ] : "",
                "qty_flag"     => $data[qty_flag   ] ?  $data[qty_flag   ] : "",
                "start_date"   => $data[start_date ] ?  $data[start_date ] : "",
                "end_date"     => $data[end_date   ] ?  $data[end_date   ] : "",
                "work_type"    => $work_type,
                "qty_multi"    => $data[qty_multi  ] ?  $data[qty_multi  ] : "",
                "pay_type"     => $data[pay_type   ] ?  $data[pay_type   ] : "",
                "deal_no"      => $data[deal_no    ] ?  $data[deal_no    ] : "",
                "deal_no_ex"	=> $data[deal_no_ex] ?  $data[deal_no_ex] : "",                
                "start_hour"   => $data[start_hour ],
                "end_hour"     => $data[end_hour   ],
                "random_gift"  => $data[random_gift] ?  $data[random_gift] : "",
                "person"       => $data[person     ] ?  $data[person     ] : "",
                "reg_date"     => $data[reg_date   ] ?  $data[reg_date   ] : ""
            );
            $excel_data[] = $info;
        }
        $this->make_file( $excel_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
    
    function del_wrong_id()
    {
        global $template, $connect, $ids;

        $new_arr = array();
        foreach( explode(",", $ids) as $pid )
        {
            $pid = trim($pid);
            
            $query = "select count(*) as cnt from products where product_id = '$pid' and is_delete=0";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            if( $data[cnt] )
                $new_arr[] = $pid;
        }
        
        $val = array();
        $val['error'] = 0;
        $val['new_list'] = implode(",", $new_arr);
        
        echo json_encode($val);
    }
}   

