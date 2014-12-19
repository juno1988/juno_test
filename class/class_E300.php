<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_shop.php";

////////////////////////////////
// class name: class_E300
//

class class_E300 extends class_top {

    ///////////////////////////////////////////
    // 
    function E300()
    {
        echo "<script>show_waiting();</script>";
        global $connect;

            // arr_config set
        	$change[$arr_config['change']] = "checked";
        	$cancel[$arr_config['cancel']] = "checked";
        	$usertrans_price               = $arr_config['usertrans_price'];
       
        global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type, $is_complete, $status, $search_sel, $status_sel, $sel_order_cs_sel, $is_complete_arr_sel, $current_rows, $work_type;
        global $cancel_type, $change_type, $user_cs_type,$start_hour, $end_hour, $complete_date, $date_type;

        $par_arr = array("template","action","start_date","end_date","search_sel"
                        ,"keyword","shop_id","supply_code","status_sel"
                        ,"sel_order_cs_sel","is_complete_arr_sel","page","work_type"
                        ,"worker","start_hour","end_hour","complete_date","date_type"
                        );
        $link_url_list = $this->build_link_par($par_arr);

        $line_per_page = 300;
        $link_url = "?" . $this->build_link_url();

        //if (!$start_date) $start_date = date('Y-m-d', strtotime('-5 day'));
        if (!$start_date) $start_date = date('Y-m-d');
        	$limit  = 1;
        	
        if( $page )
            $this->cs_list( &$total_rows, &$r_cs, $limit );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting();</script>";
    }

    function build_query2()
    {
        global $start_date,$is_complete,$search_type, $keyword, $end_date,$order_cs, $status, $page;
        global $supply_id,$act,$shop_id,$supply_code, $search_sel, $status_sel, $sel_order_cs_sel, $is_complete_arr_sel, $work_type;
        global $cancel_type, $change_type, $user_cs_type,$worker,$start_hour, $end_hour, $complete_date, $date_type;

        //////////////////////////////////////////////
        // 검색
        $starter = $page ? ($page-1) * $line_per_page : 0;
      
        $options = "from orders a, csinfo b, order_products d
                   where a.seq        = b.order_seq and 
                         a.seq        = d.order_seq ";

        // 삭제된 cs는 보지 않는다.
        $options .= " and b.is_delete = 0 ";   

        // 공급처
        // products.supply_code -> order_products.supply_id로 변경
        // 2009.12.4
        if( $supply_code ) 
        {
            $options .= " and d.supply_id = $supply_code ";
        }

        // 날짜
        if( $complete_date == "on" )
            $options .= " and complete_date >= '$start_date $start_hour:00:00' and complete_date <= '$end_date $end_hour:59:59' ";
        else
        {
            if( $date_type == 1 )
            {
                if( $start_hour == "00" )
                    $options .= " and b.input_date >= '$start_date' ";
                else
                    $options .= " and timestamp(b.input_date, b.input_time) >= '$start_date $start_hour:00:00' ";

                if( $end_hour == "23" )
                    $options .= " and b.input_date <= '$end_date' ";
                else
                    $options .= " and timestamp(b.input_date, b.input_time) <= '$end_date $end_hour:59:59' ";
            }
            else if( $date_type == 2 )
                $options .= " and concat(a.collect_date,' ',a.collect_time) >= '$start_date $start_hour:00:00' and concat(a.collect_date,' ',a.collect_time) <= '$end_date $end_hour:59:59' ";
            else if( $date_type == 3 )
                $options .= " and a.trans_date_pos >= '$start_date $start_hour:00:00' and a.trans_date_pos <= '$end_date $end_hour:59:59' ";
        }
        
        if ( $worker )
        {
            $options .= " and b.writer = '$worker' ";   
        }

		// 내용없이 cs를 남기는 경우도 있어서 주석 처리 함.. -jk 2012.1.17
        // $options .= " and b.content <> ''";

        // 검색키워드
        if( $keyword )
        {
            switch($search_sel)
            {
                case 1: // 수령자
                    $options .= " and a.recv_name = '${keyword}'" ;
                    break;
                case 2: // 수령자전화
                    $options .= " and a.recv_tel = '${keyword}'" ;
                    break;
                case 3: // 수령자핸드폰
                    $options .= " and a.recv_mobile = '${keyword}'" ;
                    break;
                case 15: // 주소
                    $options .= " and a.recv_address like '%${keyword}%'" ;
                    break;
                case 4: // 주문자
                    $options .= " and a.order_name = '${keyword}'" ;
                    break;
                case 5: // 주문자전화
                    $options .= " and a.order_tel = '${keyword}'" ;
                    break;
                case 6: // 주문자핸드폰
                    $options .= " and a.order_mobile = '${keyword}'" ;
                    break;
                case 7: // 주문번호
                    $options .= " and a.order_id = '${keyword}'" ;
                    break;
                case 8: // 판매처상품코드
                    $options .= " and a.shop_product_id = '${keyword}'" ;
                    break;
                case 9: // 판매처상품명
                    $options .= " and a.product_name like '%${keyword}%'" ;
                    break;
                case 14: // 판매처옵션
                    $options .= " and d.shop_options like '%${keyword}%'" ;
                    break;
                case 10: // 어드민상품코드
                    $options .= " and d.product_id = '${keyword}'" ;
                    break;
                case 11: // 어드민상품명
                    $options .= "" ;
                    break;
                case 12: // 메모내용[시스템]
                    $options .= " and b.content like '%${keyword}%'" ;
                    break;
                case 16: // 메모내용[사용자]
                    $options .= " and b.user_content like '%${keyword}%'" ;
                    break;
                case 13: // 작업자
                    $options .= " and b.writer like '%${keyword}%'" ;
                    break;
            }
        }
        
        // 판매처
        if ($shop_id != '')
            $options .= " and a.shop_id = '${shop_id}' ";

        // 주문상태
        switch ( $status_sel )
        {
           case "1": // 발주
               $options .= " and a.status = 0 ";
               break;
           case "2": // 접수
               $options .= " and a.status = 1 ";
               break;
           case "3": // 송장
               $options .= " and a.status = 7 ";
               break;
           case "4": // 배송
               $options .= " and a.status = 8 ";
               break;
        }
      
        switch ( $sel_order_cs_sel )
        {
            case 1: $options .= " and a.order_cs in ( 0 )"; break;          // 정상
            case 2: $options .= " and a.order_cs in ( 1,2,3,4 )"; break;    // 취소 ( 배송전,후)
            case 3: $options .= " and a.order_cs in ( 5,6,7,8 )"; break;
            case 4: $options .= " and a.order_cs in ( 1,2 )"; break;
            case 5: $options .= " and a.order_cs in ( 3,4 )"; break;        // 배송 후 취소
            case 6: $options .= " and a.order_cs in ( 5,6 )"; break;
            case 7: $options .= " and a.order_cs in ( 7,8 )"; break;
            case 8: $options .= " and a.hold > 0"; break;
            case 9: $options .= " and a.cross_change > 0"; break;
        }
     
        switch ( $is_complete_arr_sel )
        {
            case 1:
                $options .= " and b.cs_result = 0 ";
                break;
            case 2:
                $options .= " and b.cs_result = 1 ";
                break;
        }
        
        // 작업
        if( $work_type )
        {
            if( $work_type == 10 )
                $options .= " and b.cs_type in (10,11,16)  ";
            else if( $work_type == 12 )
                $options .= " and b.cs_type in (12,13,18)  ";
            else if( $work_type == 17 )
                $options .= " and b.cs_type = 17 and d.order_cs in (5,6) ";
            else if( $work_type == 32 )
                $options .= " and b.cs_type = 17 and d.order_cs in (7,8) ";
            else
                $options .= " and b.cs_type = $work_type ";
        }
        
        // cs type
        if( $cancel_type )
            $options .= " and b.cs_reason = '$cancel_type' ";
        if( $change_type )
            $options .= " and b.cs_reason = '$change_type' ";
        
        // cs_reason?? - jk
        if( $user_cs_type )
            $options .= " and b.cs_reason = '$user_cs_type' ";
        
        return $options;  
    }

    function build_query()
    {
        global $start_date,$is_complete,$search_type, $keyword, $end_date,$order_cs, $status;
        global $supply_id,$act,$shop_id,$supply_code, $search_sel, $status_sel, $sel_order_cs_sel, $is_complete_arr_sel;

        //////////////////////////////////////////////
        // 검색
        $starter = $page ? ($page-1) * $line_per_page : 0;
      
        $options = "from orders a, csinfo b, products c, order_products d
                   where a.seq        = b.order_seq and 
                         c.product_id = d.product_id and
                         a.seq        = d.order_seq ";

        // 공급처
        if( $supply_code ) $options .= " and c.supply_code = $supply_code ";

        // 날짜
        $options .= " and b.input_date >= '$start_date' and b.input_date <= '$end_date' ";

        // 검색키워드
        if( $keyword )
        {
            switch($search_sel)
            {
                case 1: // 주문자
                    $options .= " and a.order_name like '%${keyword}%'" ;
                    break;
                case 2: // 수령자
                    $options .= " and a.recv_name like '%${keyword}%'" ;
                    break;
                case 3: // 주문번호
                    $options .= " and a.order_id = '${keyword}'" ;
                    break;
                case 4: // 주문자전화
                    $options .= " and a.order_tel = '${keyword}'" ;
                    break;
                case 5: // 상품코드
                    $options .= " and d.product_id = '${keyword}'" ;
                    break;
                case 6: // 상품명
                    $options .= " and c.name like '%${keyword}%'" ;
                    break;
                case 7: // 메모내용
                    $options .= " and b.res_content like '%${keyword}%'" ;
                    break;
                case 8: // 작업자
                    $options .= " and b.writer like '%${keyword}%'" ;
                    break;
            }
        }
        
        // 판매처
        if ($shop_id != '')
            $options .= " and a.shop_id = '${shop_id}' ";

        // 주문상태
        switch ( $status_sel )
        {
           case "1": // 발주
               $options .= " and a.status = 0 ";
               break;
           case "2": // 접수
               $options .= " and a.status = 1 ";
               break;
           case "3": // 송장
               $options .= " and a.status = 7 ";
               break;
           case "4": // 배송
               $options .= " and a.status = 8 ";
               break;
        }
      
        switch ( $sel_order_cs_sel )
        {
            case 1: $options .= " and a.order_cs in ( 0 )"; break;          // 정상
            case 2: $options .= " and a.order_cs in ( 1,2,3,4 )"; break;    // 취소 ( 배송전,후)
            case 3: $options .= " and a.order_cs in ( 5,6,7,8 )"; break;
            case 4: $options .= " and a.order_cs in ( 1,2 )"; break;
            case 5: $options .= " and a.order_cs in ( 3,4 )"; break;        // 배송 후 취소
            case 6: $options .= " and a.order_cs in ( 5,6 )"; break;
            case 7: $options .= " and a.order_cs in ( 7,8 )"; break;
            case 8: $options .= " and a.hold > 0"; break;
            case 9: $options .= " and a.cross_change > 0"; break;
        }
     
        switch ( $is_complete_arr_sel )
        {
            case 1:
                $options .= " and b.cs_result = 0 ";
                break;
            case 2:
                $options .= " and b.cs_result = 0 ";
                break;
        }
    
        return $options;  
    }
      
    ///////////////////////////////////////////////////
    // cs list not yet 미처리 CS 
    // date : 2005.9.22
    function cs_list( &$total_rows, &$result, $limit_option = 0 )
    {
        global $connect, $page, $current_rows;

        $line_per_page = 300; 
        $starter = $page ? ($page-1) * $line_per_page : 0;
        /////////////////////////////////////////////////////
        $query       = "select a.*, b.input_date as cs_date,b.input_time as cs_time,b.content,b.user_content,b.writer,b.cs_reason ";
        $count_query = "select count(distinct(b.seq)) cnt  ";
        $query_ext   = $this->build_query2();
        // $query_ext  .= " group by b.order_seq desc";
        
        if ( $limit_option )
            $limit_clause = "GROUP BY b.seq  order by b.seq desc limit $starter, $line_per_page";
    
        // 총 개수
        $result     = mysql_query ( $count_query . $query_ext, $connect );
        $data       = mysql_fetch_array( $result );
        $total_rows = $data[cnt];
        

        // 결과
        $query = $query . $query_ext . $limit_clause;
        
debug( "cs 내역aaa:".$query );

        $result = mysql_query($query, $connect) or die(mysql_error());
        $current_rows = mysql_num_rows($result);
    }
    
    //  C/S내역일괄완료처리
    //  2014.03.18
    //  
    function cs_confirm()
    {
    	global $connect;
    	global $template;
    	global $start_date, $end_date, $keyword, $order_cs, $search_type, $is_complete, $status, $search_sel, $status_sel, $sel_order_cs_sel, $is_complete_arr_sel, $current_rows, $work_type;
        global $cancel_type, $change_type, $user_cs_type,$start_hour, $end_hour, $complete_date, $date_type;
    	
    	$query = "SELECT b.seq b_seq, b.cs_result b_cs_result ";
    	$query  .= $this->build_query2();
    	
    	$result = mysql_query($query, $connect) or die(mysql_error());
    	
    	$cnt = 0;
    	while($data = mysql_fetch_assoc($result))
    	{
    		if($data[b_cs_result] == 0)//미처리 이면
    		{
    			$_query = "UPDATE csinfo SET cs_result = 1, complete_date = now(), content = concat(content,concat(now(),' CS일괄완료처리')) WHERE seq = $data[b_seq]";
    			$_result = mysql_query($_query, $connect);
    			$cnt++;
    		}
    	}
    	echo "<script>alert('".$cnt." 건의 C/S가 처리되었습니다.');</script>";
    	        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////////
    // cs의 존재 여부 check
    // date: 2005.12.2
    function cs_exist( $seq )
    {
        global $connect, $worker, $is_complete_arr_sel,$work_type,$cancel_type,$user_cs_type,$change_type;

        $string = "";
        $query = "select * from csinfo where order_seq='$seq' and is_delete=0 ";
        
        if ( $worker )
            $query .= " and writer='$worker'";
        
        // 처리여부
        if ( $is_complete_arr_sel == 1 )
        {
            $query .= " and cs_result = 0 ";   
        }
        else if ( $is_complete_arr_sel == 2 )
            $query .= " and cs_result = 1 ";   
            
        // 작업
        if( $work_type )
        {
            if( $work_type == 10 )
                $query .= " and cs_type in (10,11,16)  ";
            else if( $work_type == 12 )
                $query .= " and cs_type in (12,13,18)  ";
            else if( $work_type == 17 )
                $query .= " and cs_type = 17 ";
            else if( $work_type == 32 )
                $query .= " and cs_type = 17 ";
            else
                $query .= " and cs_type = $work_type ";
        }
        
        // cs type
        if( $cancel_type )
            $query .= " and cs_reason = '$cancel_type' ";
        if( $change_type )
            $query .= " and cs_reason = '$change_type' ";
        if( $user_cs_type )
            $query .= " and cs_reason = '$user_cs_type' ";
        
        $query .= " order by seq desc ";

        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $cs_type = class_top::get_cs_type_str( $data[cs_type] );
            $cs_result = $this->get_cs_result( $data[cs_result] );
            $string .= "--------------------------------------------<br>
                        $data[input_date] $data[input_time] - $data[writer] / $cs_type / $cs_result /<br>
                        $data[content].$data[user_content]<br>";
        }
        return $string;
    }

   ////////////////////////////////////////
   // excel download
   function save_file()
   {
      global $connect, $product_info;
      
      $this->cs_list( &$total_rows, &$r_cs );

        $_data_temp["seq"         ] = "관리번호";
        $_data_temp["collect_date"] = "발주일";

	    if ( _DOMAIN_ == "changsin" )
        	$_data_temp["org_shop_name"   ] = "원주문 판매처";

        $_data_temp["shop_name"   ] = "판매처";
        $_data_temp["order_id"    ] = "주문번호";
        $_data_temp["product_name"] = "판매처 상품명";
        $_data_temp["options"     ] = "판매처 옵션";
        $_data_temp["order_name"  ] = "주문자";
        $_data_temp["order_tel"   ] = "주문자 연락처";
        $_data_temp["recv_name"   ] = "수령자";
        $_data_temp["recv_tel"    ] = "수령자 연락처";
        $_data_temp["recv_address"] = "배송지주소";
        $_data_temp["recv_zip"    ] = "우편번호";
        $_data_temp["memo"        ] = "주문시 요구사항";
        $_data_temp["trans_no"    ] = "송장번호";
        $_data_temp["status"      ] = "상태";

        if( $product_info )
        {
	        $_data_temp["product_id"  ] = "상품코드";
	        $_data_temp["qty"         ] = "수량";
	        $_data_temp["ez_prd_name" ] = "상품명";
	        $_data_temp["ez_options"  ] = "옵션";
	        $_data_temp["org_price"   ] = "원가";
	        $_data_temp["shop_price"  ] = "판매가";
	    }

        $_data_temp["cs_status"   ] = "C/S 상태";
        $_data_temp["cs_reason"   ] = "C/S 상세";
        $_data_temp["cs_qty"      ] = "C/S 개수";
        $_data_temp["cs_list"     ] = "C/S 내역";

		$_data[]= $_data_temp;


        $old_order_seq = 0;
        while( $data = mysql_fetch_assoc($r_cs) )
        {
            //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            // 2013-07-02
            // 복수상품일경우 중복되게 다운로드되는걸 막음.
            // 기존 몇몇 업체에서 전체 업체로 수정
            //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
            if( _DOMAIN_ == 'buy7942' || _DOMAIN_ == 'efolium2' || _DOMAIN_ == 'mooas' || 1 )
            {
                if( $data[seq] == $old_order_seq )
                    continue;
                else
                    $old_order_seq = $data[seq];
            }
            
            // 상품정보 포함
            if( $product_info )
            {
                // 상품정보 
                $i = 0;
                $query_prd = "select * from order_products where order_seq=$data[seq]";
                $result_prd = mysql_query($query_prd, $connect);
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    // 상품정보
                    $p_info = class_product::get_info($data_prd[product_id]);
                    
                    if( $i == 0 )
                    {
                    	$_data_order_temp["seq"          ] = $data[seq];
                    	$_data_order_temp["collect_date" ] = $data[collect_date];
                    	$_data_order_temp["shop_name"    ] = class_shop::get_shop_name( $data[shop_id] );
                    	if ( _DOMAIN_ == "changsin" )
                    		$_data_order_temp["org_shop_name"] = $this->get_org_shop_name( $data[seq] );    
                    	$_data_order_temp["order_id"     ] = $data[order_id];         
                    	$_data_order_temp["product_name" ] = $data[product_name];
                    	$_data_order_temp["options"      ] = $data[options];                            
                    	$_data_order_temp["order_name"   ] = $data[order_name];
                    	$_data_order_temp["order_tel"    ] = $data[order_tel] . " / " . $data[order_mobile];
                    	$_data_order_temp["recv_name"    ] = $data[recv_name]; 
                    	$_data_order_temp["recv_tel"     ] = $data[recv_tel] . " / " . $data[recv_mobile];
                    	$_data_order_temp["recv_address" ] = $data[recv_address];   
                    	$_data_order_temp["recv_zip"     ] = $data[recv_zip];                            
                    	$_data_order_temp["memo"         ] = $data[memo];                                
                    	$_data_order_temp["trans_no"     ] = $data[trans_no];
                    	$_data_order_temp["status"       ] = $this->get_order_status2($data[status]);
                    	$_data_order_temp["product_id"   ] = $p_info[product_id];        
                    	$_data_order_temp["qty"          ] = $data_prd[qty];                            
                    	$_data_order_temp["ez_prd_name"  ] = $p_info[name];                                 
                    	$_data_order_temp["ez_options"   ] = $p_info[options];
                    	$_data_order_temp["org_price"    ] = $p_info[org_price];
                    	$_data_order_temp["shop_price"   ] = $p_info[shop_price];
                    	$_data_order_temp["cs_status"    ] = $this->get_order_cs2($data[order_cs]);
                    	$_data_order_temp["status"     	 ] = $this->get_order_status2($data[status]);
                    	$_data_order_temp["cs_reason"	 ] = $data[cs_reason];
                    	
                    	$_data_order = $_data_order_temp;
                    	                        
                        $cs_query = "select * from csinfo where order_seq='$data[seq]' and is_delete=0 ";
                        //
                        // type을 선택한 경우 type별로 cs를 가져와야 한다. blueforce요청사항 2012.4.10 - jk
                        // 
                        // 작업
                        global $work_type,$cancel_type,$change_type,$user_cs_type,$start_date,$end_date,$start_hour, $end_hour,$is_complete_arr_sel;
                        if( $work_type )
                        {
                            if( $work_type == 10 )
                                $cs_query .= " and cs_type in (10,11,16)  ";
                            else if( $work_type == 12 )
                                $cs_query .= " and cs_type in (12,13,18)  ";
                            else if( $work_type == 17 )
                                $cs_query .= " and cs_type = 17 and d.order_cs in (5,6) ";
                            else if( $work_type == 32 )
                                $cs_query .= " and cs_type = 17 and d.order_cs in (7,8) ";
                            else
                                $cs_query .= " and cs_type = $work_type ";
                        }
                        
                        switch ( $is_complete_arr_sel )
                        {
                            case 1:
                                $cs_query .= " and cs_result = 0 ";
                                break;
                            case 2:
                                $cs_query .= " and cs_result = 1 ";
                                break;
                        }
                        
                        // cs type
                        if( $cancel_type )
                            $cs_query .= " and cs_reason = '$cancel_type' ";
                        if( $change_type )
                            $cs_query .= " and cs_reason = '$change_type' ";
                        
                        // cs_reason?? - jk
                        if( $user_cs_type )
                            $cs_query .= " and cs_reason = '$user_cs_type' ";
                            
                        if ( $start_date )
                            $cs_query .= " and input_date >= '" . $start_date . "' ";
                        
                        if ( $end_date )
                            $cs_query .= " and input_date <= '" . $end_date . "' ";
                            
                        if ( $start_hour )
                            $cs_query .= " and input_time >= '" . $start_hour . ":00:00' ";
                        
                        if ( $end_hour )
                            $cs_query .= " and input_time <= '" . $end_hour . ":00:00' ";
                        $cs_query .= " order by seq ";
                        
                        debug( "blueforce cs_query: " . $cs_query );
                        
                        $cs_result = mysql_query($cs_query, $connect);
                        
                        $_qty = 0;
		                $_cs_temp = array();
		
		                while( $cs_data = mysql_fetch_assoc($cs_result) )
		                {
		                    if( $cs_data[content].$cs_data[user_content])
		                    {
		                        $_qty++;
		                        $content = str_replace( array("><","<",">"), " / ", $cs_data[content].$cs_data[user_content] );
		                        $_cs_temp[] = "[ " . $cs_data[input_date] . " - " . $cs_data[writer] . " / " . 
		                        $this->get_cs_type($cs_data[cs_type]) . " / " . $this->get_cs_result($cs_data[cs_result]) . " ] " . $content;
		                    }
		                }
		                
		                $_data_order['cs_qty'] = $_qty;
		                $_data_order = array_merge($_data_order, $_cs_temp);
                    }
                    else
                    {
                    	$_data_order_temp = array();
                    	
                    	// changsin일 경우 원주문 판매처 추가..	
                    	$_data_order_temp["seq"          ] = $data[seq]                            ;
                    	$_data_order_temp["collect_date" ] = ''                                    ;
                    	$_data_order_temp["shop_name"    ] = ''                                    ;
                    	if ( _DOMAIN_ == "changsin" )
                    		$_data_order_temp["org_shop_name"] = $this->get_org_shop_name( $data[seq] );
                    	$_data_order_temp["order_id"     ] = ''                                    ;
                    	$_data_order_temp["product_name" ] = ''                                    ;
                    	$_data_order_temp["options"      ] = ''                                    ;
                    	$_data_order_temp["order_name"   ] = ''                                    ;
                    	$_data_order_temp["order_tel"    ] = ''                                    ;
                    	$_data_order_temp["recv_name"    ] = ''                                    ;
                    	$_data_order_temp["recv_tel"     ] = ''                                    ;
                    	$_data_order_temp["recv_address" ] = ''                                    ;
                    	$_data_order_temp["recv_zip"     ] = ''                                    ;
                    	$_data_order_temp["memo"         ] = ''                                    ;
                    	$_data_order_temp["trans_no"     ] = ''                                    ;
                    	$_data_order_temp["status"       ] = ''                                    ;
                    	$_data_order_temp["product_id"   ] = $p_info[product_id]                   ;
                    	$_data_order_temp["qty"          ] = $data_prd[qty]                        ;
                    	$_data_order_temp["ez_prd_name"  ] = $p_info[name]                         ;
                    	$_data_order_temp["ez_options"   ] = $p_info[options]                      ;
                    	$_data_order_temp["org_price"    ] = $p_info[org_price]                    ;
                    	$_data_order_temp["shop_price"   ] = $p_info[shop_price]                   ;
                    	$_data_order_temp["cs_status"    ] = ''                                    ;
                    	
                    	$_data_order = $_data_order_temp;
                    }
                    
                    $i++;
                    $_data[] = $_data_order;
                }
            }
            
            // 상품정보 미포함
            else
            {
                if ( _DOMAIN_ == "changsin" )
                {
                    $_data_order = array(
                        "seq"           => $data[seq],
                        "collect_date"  => $data[collect_date],
                        "shop_name"     => class_shop::get_shop_name( $data[shop_id] ),
                        "org_shop_name" => $this->get_org_shop_name( $data[seq] ),
                        "order_id"      => $data[order_id],
                        "product_name"  => $data[product_name],
                        "options"       => $data[options],
                        "order_name"    => $data[order_name],
                        "order_tel"     => $data[order_tel] . " / " . $data[order_mobile],
                        "recv_name"     => $data[recv_name],
                        "recv_tel"      => $data[recv_tel] . " / " . $data[recv_mobile],
                        "recv_address"  => $data[recv_address],
                        "recv_zip"      => $data[recv_zip],
                        "memo"          => $data[memo],
                        "trans_no"      => $data[trans_no],
                        "status"        => $this->get_order_status2($data[status]),
                        "cs_status"     => $this->get_order_cs2($data[order_cs]),
                        "cs_reason"     => $data[cs_reason]
                        );
                    
                }
                else
                {
                    $_data_order = array(
                        "seq"           => $data[seq],
                        "collect_date"  => $data[collect_date],
                        "shop_name"     => class_shop::get_shop_name( $data[shop_id] ),
                        "order_id"      => $data[order_id],
                        "product_name"  => $data[product_name],
                        "options"       => $data[options],
                        "order_name"    => $data[order_name],
                        "order_tel"     => $data[order_tel] . " / " . $data[order_mobile],
                        "recv_name"     => $data[recv_name],
                        "recv_tel"      => $data[recv_tel] . " / " . $data[recv_mobile],
                        "recv_address"  => $data[recv_address],
                        "recv_zip"      => $data[recv_zip],
                        "memo"          => $data[memo],
                        "trans_no"      => $data[trans_no],
                        "status"        => $this->get_order_status2($data[status]),
                        "cs_status"     => $this->get_order_cs2($data[order_cs]),
                        "cs_reason"     => $data[cs_reason]
                    );
                }
    
                $cs_query = "select * from csinfo where order_seq='$data[seq]' and is_delete=0 ";
                //
                // type을 선택한 경우 type별로 cs를 가져와야 한다. blueforce요청사항 2012.4.10 - jk
                // 
                // 작업
                global $work_type,$cancel_type,$change_type,$user_cs_type,$start_date,$end_date,$start_hour, $end_hour,$is_complete_arr_sel;
                if( $work_type )
                {
                    if( $work_type == 10 )
                        $cs_query .= " and cs_type in (10,11,16)  ";
                    else if( $work_type == 12 )
                        $cs_query .= " and cs_type in (12,13,18)  ";
                    else if( $work_type == 17 )
                        $cs_query .= " and cs_type = 17 and d.order_cs in (5,6) ";
                    else if( $work_type == 32 )
                        $cs_query .= " and cs_type = 17 and d.order_cs in (7,8) ";
                    else
                        $cs_query .= " and cs_type = $work_type ";
                }
                
                switch ( $is_complete_arr_sel )
                {
                    case 1:
                        $cs_query .= " and cs_result = 0 ";
                        break;
                    case 2:
                        $cs_query .= " and cs_result = 1 ";
                        break;
                }
                
                // cs type
                if( $cancel_type )
                    $cs_query .= " and cs_reason = '$cancel_type' ";
                if( $change_type )
                    $cs_query .= " and cs_reason = '$change_type' ";
                
                // cs_reason?? - jk
                if( $user_cs_type )
                    $cs_query .= " and cs_reason = '$user_cs_type' ";
                
                if ( $start_date )
                    $cs_query .= " and input_date >= '" . $start_date . "' ";
                
                if ( $end_date )
                    $cs_query .= " and input_date <= '" . $end_date . "' ";
                
                if ( $start_hour )
                    $cs_query .= " and input_time >= '" . $start_hour . ":00:00' ";
                
                if ( $end_hour )
                    $cs_query .= " and input_time <= '" . $end_hour . ":00:00' ";
                $cs_query .= " order by seq ";
                
                //$cs_query = "select * from csinfo where order_seq='$data[seq]' order by seq ";
                $cs_result = mysql_query($cs_query, $connect);

                $_qty = 0;
                $_cs_temp = array();

                while( $cs_data = mysql_fetch_assoc($cs_result) )
                {
                    if( $cs_data[content].$cs_data[user_content] )
                    {
                        $_qty++;
                        $content = str_replace( array("><","<",">"), " / ", $cs_data[content].$cs_data[user_content] );
                        $_cs_temp[] = "[ " . $cs_data[input_date] . " - " . $cs_data[writer] . " / " . 
                        $this->get_cs_type($cs_data[cs_type]) . " / " . $this->get_cs_result($cs_data[cs_result]) . " ] " . $content;
                    }
                }
                
                $_data_order['cs_qty'] = $_qty;
                $_data_order = array_merge($_data_order, $_cs_temp);
                
                if ( _DOMAIN_ == "changsin" )
                {
                    $_data_order["org_shop_name"] = $this->get_org_shop_name( $data[seq] );
                }
                
                //debug( "xx1" . $_data_order[0]['seq'] );
                print_r ( $_data_order );
                
                $_data[] = $_data_order;
            }
        }

        $this->save_file_cs( $_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
   }

   function save_file_cs( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
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

            // for column
            foreach ( $row as $key=>$value) 
            {
                if ( $key == 'cs_qty' )
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\"'>" . $value . "</td>";
                else
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
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
    
    // 원 주문 판매처
    function get_org_shop_name( $_seq )
    {
        global $connect;
        
        //return "org name: $_seq";
        
        $query  = "select copy_seq from orders where seq=$_seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[copy_seq] )
        {
            $query2  = "select shop_id from orders where seq=$data[copy_seq]";
            
            $result2 = mysql_query( $query2, $connect );
            $data2   = mysql_fetch_assoc( $result2 );
            
            $query   = "select shop_name from shopinfo where shop_id='$data2[shop_id]'";
            
            $result3 = mysql_query( $query, $connect );
            $data3   = mysql_fetch_assoc( $result3 );
            
            return $data3['shop_name'];
        }
        return "";
    }
    
    //////////////////////////////////////
    // 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "cs_list.xls");
    }    

    function get_cs_type( $cs_type )
    {
        switch( $cs_type )
        {
            case 0	:  $cs_type = "일반";         break;
            case 1	:  $cs_type = "보류설정";     break;
            case 2	:  $cs_type = "보류해제";     break;
            case 3	:  $cs_type = "배송정보변경"; break;
            case 4	:  $cs_type = "송장입력";     break;
            case 5	:  $cs_type = "송장삭제";     break;
            case 6	:  $cs_type = "배송확인";     break;
            case 7	:  $cs_type = "배송취소";     break;
            case 8	:  $cs_type = "합포추가";     break;
            case 9	:  $cs_type = "합포제외";     break;
            case 10	:  $cs_type = "합포취소";     break;
            case 11	:  $cs_type = "주문취소";     break;
            case 12	:  $cs_type = "합포정상복귀"; break;
            case 13	:  $cs_type = "주문정상복귀"; break;
            case 14	:  $cs_type = "주문복사";     break;
            case 15	:  $cs_type = "주문생성";     break;
            case 16	:  $cs_type = "상품취소";     break;
            case 17	:  $cs_type = "상품교환";     break;
            case 18	:  $cs_type = "상품정상복귀"; break;
            case 19	:  $cs_type = "상품추가";     break;
            case 20	:  $cs_type = "매칭삭제";     break;
            case 21	:  $cs_type = "미송설정";     break;
            case 22	:  $cs_type = "회수설정";     break;
            case 34	:  $cs_type = "회수요청";     break;
            case 35	:  $cs_type = "반품예정";     break;
            case 100:  $cs_type = "완료처리";     break;
        }
        return $cs_type;
    }
    
    function get_cs_result( $cs_result )
    {
        switch( $cs_result )
        {
            case 0: $cs_result = "미처리"; break;
            case 1: $cs_result = "완료"; break;
        }
        return $cs_result;
    }
  
}

?>
