<?
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_stock.php";

//////////////////////////////////////////////
// 공급처 반품전표
class class_IT00 extends class_top
{
	// 공급처 반품전표 목록
	function IT00()
	{
		global $template, $connect;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function IT01()
	{
		global $template, $connect;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function IT02()
	{
		global $template, $connect, $supply_code, $product_id;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function IT03()
	{
		global $template, $connect, $str;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}	
	
	function new_sheet_add()
	{
		global $template, $connect, $p_list, $title, $memo;

        // 상품리스트 디코딩
        $p_list_supply = array();
        foreach( explode("|", $p_list) as $p_data )
        {
            $p_arr = array();
            foreach( explode("$", $p_data) as $p_val )
            {
                list($k,$v) = explode(":", $p_val);
                if( $k == "memo" )  $v = urldecode($v);
                $p_arr[$k] = $v;
            }
            $p_list_supply[$p_arr['supply_code']][] = $p_arr;
        }

		// 공급처별
		foreach( $p_list_supply as $p_key => $p_val )
		{
		    // 공급처명
		    $supply_name = class_supply::get_name($p_key);
		    $sheet_name = $title . "_" . $supply_name;
		    
		    // 전표 등록
		    $query = "insert return_ready_sheet
		                 set crdate = now()
		                    ,crworker = '$_SESSION[LOGIN_ID]'
		                    ,sheet_name = '$sheet_name'
		                    ,supply_code = $p_key
		                    ,status = 0
		                    ,memo = '$memo'";
            mysql_query($query, $connect);
            
            // 방금 등록한 전표의 seq 구하기
            $query = "select seq from return_ready_sheet where supply_code='$p_key' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $sheet_seq = $data[seq];
            
            // 전표에 상품등록
            foreach( $p_val as $p_info )
            {
                $query = "insert return_ready_sheet_detail
                             set sheet_no = $sheet_seq
                                ,product_id = '$p_info[product_id]'
                                ,normal_out = '$p_info[normal_out]'
                                ,bad_out = '$p_info[bad_out]'
                                ,return_type = '$p_info[return_type]'
                                ,return_product_id = '$p_info[return_product_id]'
                                ,return_qty = '$p_info[return_qty]'
                                ,return_sub_money = '$p_info[return_sub_money]'
                                ,return_money = '$p_info[return_money]'
                                ,memo = '$p_info[memo]'";
                mysql_query($query, $connect);
            }
		}
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
	        $sheet_name = $sheet_title . "_" . class_supply::get_name($supply);
		    $query = "insert return_ready_sheet
		                 set crdate = now()
		                    ,crworker = '$_SESSION[LOGIN_ID]'
		                    ,sheet_name = '$sheet_name'
		                    ,supply_code = $supply
		                    ,status = 0
		                    ,memo = '$sheet_memo'";
            mysql_query($query, $connect);
	    }
	}
	
	function get_product_list()
	{
		global $template, $connect;
		global $query_type, $query_str, $supply_code;
		global $str_supply_code, $multi_supply_group, $multi_supply;
		
		$query_str = trim($query_str);
		
		$query = "SELECT product_id, name, options, supply_code, org_price FROM products WHERE is_delete = 0 AND is_represent = 0";
		
        $option = "";
		if($query_str)
		{
		    if( $query_type == "product_id" || $query_type == "barcode" )
			    $option .= " AND $query_type = '$query_str'";
            else
			    $option .= " AND $query_type LIKE '%$query_str%'";
		}

		if($supply_code)
			$option .=" AND supply_code = '$supply_code' ";
			
		if ( $str_supply_code )
			$option .= " AND supply_code in ( $str_supply_code ) ";

		if($multi_supply)
			$option .= " AND supply_code in ( $multi_supply ) ";	
			
		$option .=" LIMIT 100 ";

		$result = mysql_query ( $query. $option, $connect ) or die( mysql_error() );
		$data_arr = array();
		while ( $data = mysql_fetch_array ( $result ) )
		{
            $data[normal_out]  = 0;
            $data[bad_out]     = 0;
    
            $data[return_type] = 1;
    
            $data[rp_id]       = $data[product_id];
            
            $data[button]      = "add";

            $data[return_qty]  = 0;
    
            $data[send_org]    = $data[org_price];
            $data[send_qty]    = 0;
            $data[send_price]  = 0;
    
            $data[return_org]   = $data[org_price];
            $data[return_qty]   = 0;
            $data[return_price] = 0;
    
            $return_sub_money = 0;
    
            $return_money = 0;
    
            $memo = "";  
		    
		    $data_arr[] = $this->get_product_tr_str($data);
		}

		echo json_encode( $data_arr );
	}
	
	function get_product_search()
	{
		global $template, $connect;
		global $query_type, $query_str, $supply_code;
		
		$query_str = trim($query_str);
		
		$query = "SELECT product_id
		                ,name
		                ,options
		                ,supply_code
		                ,org_price 
		            FROM products 
		           WHERE is_delete = 0 
		             AND is_represent = 0 
		             AND supply_code = '$supply_code'";
		
        $option = "";
		if($query_str)
		{
		    if( $query_type == "product_id" || $query_type == "barcode" )
			    $option .= " AND $query_type = '$query_str'";
            else
			    $option .= " AND $query_type LIKE '%$query_str%'";
		}
		$option .=" LIMIT 100 ";

		$result = mysql_query ( $query. $option, $connect ) or die( mysql_error() );
		$data_arr = array();
		while ( $data = mysql_fetch_array ( $result ) )
		{
            $data[stock] = number_format(class_stock::get_current_stock($data[product_id],0));      
            $data[bad]   = number_format(class_stock::get_current_stock($data[product_id],1));        
            $data[org_price] = number_format($data[org_price]);        

		    $data_arr[] = $data;
		}

		echo json_encode( $data_arr );
	}
	
	function IT10()
	{
		global $template, $connect;
		global $date_type, $start_date, $end_date, $string, $sheet_type, $is_del;
		global $str_supply_code, $multi_supply_group, $multi_supply;
		
		if(!$start_date)
		{
			$date_type="crdate";
			$start_date = date("Y-m-d");
			$end_date = date("Y-m-d");
		}
		
		if( $is_del == "on" )
		    $is_del = 1;
		else
		    $is_del = 0;

		$query = "SELECT * FROM return_ready_sheet WHERE $date_type >= '$start_date 00:00:00' AND $date_type <= '$end_date 23:59:59' and is_del= $is_del";

		if ( $str_supply_code )
			$query .= " AND supply_code in ( $str_supply_code ) ";

		if($multi_supply)
			$query .= " AND supply_code in ( $multi_supply ) ";	
			
		if($string)
			$query .= " AND sheet_name LIKE '%$string%' ";
			
		if($sheet_type && $sheet_type < 99)
			$query .= " AND status = ($sheet_type-1) ";
			
		$query .= " ORDER BY seq DESC";	
		$result = mysql_query ( $query, $connect ) or die( mysql_error() );
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	
	function IT11()
	{
		global $template, $connect;
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	
	function IT12()
	{
		global $template, $connect, $sheet_seq, $supply_code;
		
		
		$query = "SELECT * FROM return_ready_sheet_detail WHERE sheet_no = $sheet_seq";
		$result = mysql_query ( $query. $option, $connect ) or die( mysql_error() );
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function IT40()
	{
		global $template, $connect, $page;
		global $query_type, $query_str;
		global $str_supply_code, $multi_supply;

		if( $page )
		{
    		$query = "SELECT b.seq b_seq
    		            FROM products a
    		                ,return_ready_sheet_detail b
    		           where a.product_id = b.product_id
    		             and a.is_delete = 0
    		             and a.is_represent = 0 ";
            if( $str_supply_code )
                $query .= " and a.supply_code = '$str_supply_code' ";
            if( $multi_supply )
                $query .= " and a.supply_code in ($multi_supply)";
            
            if( $query_str )
            {
    		    if( $query_type == "product_id" || $query_type == "barcode" )
    			    $query .= " AND a.$query_type = '$query_str' ";
                else
    			    $query .= " AND a.$query_type LIKE '%$query_str%' ";
            }
            
            $query .= " order by seq desc limit 100";
    		$result = mysql_query ( $query. $option, $connect );
        }
        
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	
	function sheet_save()//저장
	{
		global $template, $connect, $str, $sheet_seq, $memo, $p_list;

        // 상품리스트 디코딩
        $p_list_arr = array();
        foreach( explode("|", $p_list) as $p_data )
        {
            $p_arr = array();
            foreach( explode("$", $p_data) as $p_val )
            {
                list($k,$v) = explode(":", $p_val);
                if( $k == "memo" )  $v = urldecode($v);
                $p_arr[$k] = $v;
            }
            $p_list_arr[] = $p_arr;
        }
        
        // 전표 memo 수정
        $query = "update return_ready_sheet set memo='$memo' where seq=$sheet_seq";
        mysql_query($query, $connect);

        // 기존 정보 삭제
        $query = "delete from return_ready_sheet_detail where sheet_no=$sheet_seq";
        mysql_query($query, $connect);
        
		// 새로 저장
		foreach( $p_list_arr as $p_val )
		{
            $query = "insert return_ready_sheet_detail
                         set sheet_no = $sheet_seq
                            ,product_id = '$p_val[product_id]'
                            ,normal_out = '$p_val[normal_out]'
                            ,bad_out = '$p_val[bad_out]'
                            ,return_type = '$p_val[return_type]'
                            ,return_product_id = '$p_val[return_product_id]'
                            ,return_qty = '$p_val[return_qty]'
                            ,return_sub_money = '$p_val[return_sub_money]'
                            ,return_money = '$p_val[return_money]'
                            ,memo = '$p_val[memo]'";
            mysql_query($query, $connect);
        }
	}

    // 전표 출고처리 전체
	function product_return_all()
	{
		global $template, $connect, $sheet_seq, $sheet_seq_list;
		
		foreach(explode(",", $sheet_seq_list) as $v)
		{
		    $sheet_seq = $v;
		    $this->product_return();
		}
    }
    
    // 전표 출고처리
	function product_return()
	{
		global $template, $connect, $sheet_seq;
		
		$obj = new class_stock();
		
		$val = array();
		
		// 전표 확인 확인
		$query = "SELECT * FROM return_ready_sheet WHERE seq = $sheet_seq";
		$result = mysql_query ( $query, $connect );
		$sheet_info = mysql_fetch_assoc($result);
		
		// 이미 출고처리
		if( $sheet_info[status] == 1 )
		    $val['error'] = 1;
		// 삭제된 전표
		else if( $sheet_info[is_del] == 1 )
		    $val['error'] = 2;
		
		// 오류
		if( $val['error'] > 0 )
		{
		    echo json_encode($val);
		    return;
		}
		
		// 전표 update
		$query = "update return_ready_sheet 
		             set out_worker = '$_SESSION[LOGIN_ID]'
		                ,out_date = now()
		                ,status = 1
		           where seq = $sheet_seq";
        mysql_query($query, $connect);
		
		// 상품처리
		$query = "SELECT * FROM return_ready_sheet_detail WHERE sheet_no = $sheet_seq";
		$result = mysql_query ( $query. $option, $connect );
		while($data = mysql_fetch_assoc($result))
		{
			//정상재고 출고
			if($data[normal_out])
			{
				$info_arr = array(
	                    type       => "retout",
	                    product_id => $data[product_id],
	                    bad        => 0,
	                    location   => 'Def',
	                    qty        => $data[normal_out],
	                    memo       => "공급처교환출고($sheet_seq)",
	                    order_seq  => ""
	                );
				$obj->set_stock($info_arr);	
			}
			
			//불량재고 출고
			if($data[bad_out])
			{
				$info_arr = array(
	                    type       => "retout",
	                    product_id => $data[product_id],
	                    bad        => 1,
	                    location   => 'Def',
	                    qty        => $data[bad_out],
	                    memo       => "공급처교환출고($sheet_seq)",
	                    order_seq  => ""
	                );
				$obj->set_stock($info_arr);	
			}

			$return_type = "";
			$return_money = $data[tot_price];
			
			// 불량교환
			if($data[return_type] == 1)
			{
				$query = "UPDATE products 
    			             SET return_qty = return_qty + " . ($data[bad_out] + $data[normal_out]) . "
    			           WHERE product_id = '$data[product_id]'";
    			mysql_query ( $query, $connect );
    			
    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, $data[bad_out] + $data[normal_out], 0);
			}
			
			// 상품교환
			else if($data[return_type] == 2)
			{
				$query = "UPDATE products 
    			             SET return_qty = return_qty + $data[return_qty]
    			           WHERE product_id = '$data[return_product_id]'";
    			mysql_query ( $query, $connect );

    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[return_product_id], 0, $data[return_qty], 0);

                // 차액 있을 경우
                if( $data[return_sub_money] )
                {
    				$query = "UPDATE products 
        			             SET return_money = return_money + $data[return_sub_money]
        			           WHERE product_id = '$data[product_id]'";
        			mysql_query ( $query, $connect );
    
        			// 이력:전표, 상품코드, 타입, 수량, 금액
        			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, 0, $data[return_sub_money]);
        		}
			}
			
			// 환불
			else if($data[return_type] == 3)
			{
			    if( $data[return_money] )
			    {
    				$query = "UPDATE products 
        			             SET return_money = return_money + $data[return_money]
        			           WHERE product_id = '$data[product_id]'";
        			mysql_query ( $query, $connect );
    
        			// 이력:전표, 상품코드, 타입, 수량, 금액
        			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, 0, $data[return_money]);
        		}
			}
			
			// 반품
			else if($data[return_type] == 4)
			{
			    // 아무것도 안함
			}
		} 
	}
	
	// 전표 출고 취소처리
	function cancel_out()
	{
		global $template, $connect, $sheet_seq;
		
		$obj = new class_stock();
		
		$val = array();
		
		// 전표 확인 확인
		$query = "SELECT * FROM return_ready_sheet WHERE seq = $sheet_seq";
		$result = mysql_query ( $query, $connect );
		$sheet_info = mysql_fetch_assoc($result);
		
		// 이미 출고 취소처리
		if( $sheet_info[status] == 0 )
		    $val['error'] = 1;
		
		// 오류
		if( $val['error'] > 0 )
		{
		    echo json_encode($val);
		    return;
		}
		
		// 전표 update
		$query = "update return_ready_sheet 
		             set out_worker = ''
		                ,out_date = 0
		                ,status = 0
		           where seq = $sheet_seq";
        mysql_query($query, $connect);
		
		// 상품처리
		$query = "SELECT * FROM return_ready_sheet_detail WHERE sheet_no = $sheet_seq";
		$result = mysql_query ( $query. $option, $connect );
		while($data = mysql_fetch_assoc($result))
		{
			//정상재고 출고 복구
			if($data[normal_out])
			{
				$info_arr = array(
	                    type       => "retout",
	                    product_id => $data[product_id],
	                    bad        => 0,
	                    location   => 'Def',
	                    qty        => $data[normal_out] * -1,
	                    memo       => "공급처교환출고취소($sheet_seq)",
	                    order_seq  => ""
	                );
				$obj->set_stock($info_arr);	
			}
			
			//불량재고 출고
			if($data[bad_out])
			{
				$info_arr = array(
	                    type       => "retout",
	                    product_id => $data[product_id],
	                    bad        => 1,
	                    location   => 'Def',
	                    qty        => $data[bad_out] * -1,
	                    memo       => "공급처교환출고취소($sheet_seq)",
	                    order_seq  => ""
	                );
				$obj->set_stock($info_arr);	
			}

			$return_type = "";
			$return_money = $data[tot_price];
			if($data[return_type] == 1)
			{
				$query = "UPDATE products 
    			             SET return_qty = return_qty - " . ($data[bad_out] + $data[normal_out]) . "
    			           WHERE product_id = '$data[product_id]'";
    			mysql_query ( $query, $connect );
    			
    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, ($data[bad_out] + $data[normal_out]) * -1, 0);
			}
			else if($data[return_type] == 2)
			{
				$query = "UPDATE products 
    			             SET return_qty = return_qty - $data[return_qty]
    			           WHERE product_id = '$data[return_product_id]'";
    			mysql_query ( $query, $connect );

    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[return_product_id], 0, $data[return_qty] * -1, 0);

				$query = "UPDATE products 
    			             SET return_money = return_money - $data[return_sub_money]
    			           WHERE product_id = '$data[product_id]'";
    			mysql_query ( $query, $connect );

    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, 0, $data[return_sub_money] * -1);
			}
			else if($data[return_type] == 3)
			{
				$query = "UPDATE products 
    			             SET return_money = return_money - $data[return_money]
    			           WHERE product_id = '$data[product_id]'";
    			mysql_query ( $query, $connect );

    			// 이력:전표, 상품코드, 타입, 수량, 금액
    			$this->return_ready_sheet_log($sheet_seq, $data[product_id], 0, 0, $data[return_money] * -1);
			}
			else if($data[return_type] == 4)
			{
			    // 아무것도 안함
			}
		}
		
		$val['error'] = 0;
		echo json_encode($val);
	}

	function IT20()
	{
		global $template, $connect, $page, $except_zero;
		global $query_type, $query_str, $supply_code;
		global $str_supply_code, $multi_supply_group, $multi_supply;

        if( $page )
        {
    		$query_str = trim($query_str);
    		
    		$query = "SELECT * FROM products WHERE is_delete = 0 and is_represent=0 ";
    		
    		if($query_str)
    		{
    		    if( $query_type == "product_id" || $query_type == "barcode" )
    			    $query .= " AND $query_type = '$query_str' ";
                else
    			    $query .= " AND $query_type LIKE '%$query_str%' ";
    		}
    
    		if ( $str_supply_code )
    			$query .= " AND supply_code in ( $str_supply_code ) ";
    
    		if($multi_supply)
    			$query .= " AND supply_code in ( $multi_supply ) ";	
    		
    		if( $except_zero == "on" )
    		    $query .= " and (return_qty>0 or return_money>0)";
            $query .= " limit 100";
    		$result = mysql_query ( $query, $connect ) or die( mysql_error() );
		}
debug("교환 상품 목록 : " . $query);		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	
	function IT30()
	{
		global $template, $connect,  $start_date, $end_date;
		global $str_supply_code, $multi_supply_group, $multi_supply;
		global $work_type, $str_type, $string;
		
		
		$query = "SELECT a.*
		                ,a.memo a_memo
		                ,b.supply_code b_supply_code
				    FROM return_ready_sheet_log a
				       , products b
				   WHERE a.product_id = b.product_id
				     AND a.crdate >= '$start_date 00:00:00'
				     AND a.crdate <= '$end_date 23:59:59' ";
				    
		if ( $str_supply_code )
			$query .= " AND b.supply_code in ( $str_supply_code ) ";

		if($multi_supply)
			$query .= " AND b.supply_code in ( $multi_supply ) ";	
			
		if($type_type)
			$query .= " AND a.work_type in ( $type_type ) ";	
		
		if($string)
		{
			if($str_type == 1)
				$query .= " AND b.name LIKE '%$string%'";	
			else if($str_type == 2)
				$query .= " AND b.options LIKE '%$string%'";	
			else if($str_type == 3)
				$query .= " AND b.product_id = '$string'";	
			else if($str_type == 4)
				$query .= " AND b.barcode = '$barcode'";	
			else if($str_type == 5)
				$query .= " AND b.brand like '%$string%'";	
			else if($str_type == 6)
				$query .= " AND b.supply_options = '%$string%'";	
		}
		
		$query .= " ORDER BY seq desc";
		
		$result = mysql_query ( $query. $option, $connect ) or die( mysql_error() );
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function get_product_tr_str($info)
	{
        $product_id  = $info[product_id]; 
        $supply_code = $info[supply_code];
        $supply_name = class_supply::get_name($info[supply_code]);
        
        $p_info   = class_product::get_info($product_id);
        $name     = $p_info[name];
        $options  = $p_info[options];
        $org_price= $p_info[org_price];
        
        $normal_stock = number_format(class_stock::get_current_stock($info[product_id],0));      
        $bad_stock    = number_format(class_stock::get_current_stock($info[product_id],1));        

        $normal_out  = number_format($info[normal_out]);
        $bad_out     = number_format($info[bad_out]);

        if( $info[button] == "add" )
        { 
            // add
            $button_str = "
                <a href='javascript:do_nothing()' onclick='javascript:add(this)' class='btn_premium2 add' >
                <img src=images/search_link.gif>추가
                </a>
            ";
        }
        else if( $info[button] == "del" )
        {
            // del
            $button_str = "
                <a href='javascript:do_nothing()' onclick='javascript:del(this)' class='btn_premium2 del' >
                <img src=images/del_link.gif>삭제
                </a>
            ";
        }
        else
            $button_str = "&nbsp;";
        
        $return_type = $info[return_type];

        $rp_id       = $info[rp_id];
        if( $rp_id > "" )
        {
            $rp_info    = class_product::get_info($rp_id);
            $rp_name     = $rp_info[name];
            $rp_options  = $rp_info[options];
            $rp_org_price= $rp_info[org_price];
        }
        else
        {
            $rp_name     = "";
            $rp_options  = "";
            $rp_org_price= $org_price;
        }
        $send_org   = number_format($org_price);  
        $send_qty   = number_format($info[send_qty]);  
        $send_price = number_format($org_price * $info[send_qty]);  

        $return_org   = number_format($rp_org_price);  
        $return_qty   = number_format($info[return_qty]);  
        $return_price = number_format($rp_org_price * $info[return_qty]);  

        $return_sub_money = number_format($info[return_sub_money]);  

        if( $info[return_type] == 3 )
            $return_money = number_format($info[return_money]);
        else
            $return_money = $send_price;

        $memo = $info[memo];  

        $str = "<tr class='before data' product_id='$product_id' supply_code='$supply_code'>
                    <td class='supply_code' noWrap='nowrap'>$supply_name</td>
                    <td class='left product_info' noWrap='nowrap'>
                        <span class='p_title'>상품코드 :</span><span class='p_data'>$product_id</span><br>
                        <span class='p_title'>상품명 :</span><span class='p_data'>$name</span><br>
                        <span class='p_title'>옵션 :</span><span class='p_data'>$options</span>
                    </td>
                    <td class='left stock_normal' noWrap='nowrap'>
                        <span class='p_title'>정상재고 :</span><input class='input22 input_num input_disable' type=text value='$normal_stock'><br>
                        <span class='p_title'>교환수량 :</span><input class='input22 input_num normal_out' onfocus='input_num_focus(this)' onblur='onblur_normal_out(this)' type=text value='$normal_out' >
                    </td>
                    <td class='left stock_bad' noWrap='nowrap'>
                        <span class='p_title'>불량재고 :</span><input class='input22 input_num input_disable' type=text value='$bad_stock'><br>
                        <span class='p_title'>교환수량 :</span><input class='input22 input_num bad_out' onfocus='input_num_focus(this)' onblur='onblur_bad_out(this)' type=text value='$bad_out' >
                    </td>
                    <td class='left cmd_button' noWrap='nowrap'>
                        $button_str
                    </td>
                    <td class='return_type' noWrap='nowrap'>
                        <select onchange='return_type_change(this)'>
                            <option value=1 " . ($return_type == 1 ? "selected" : "") . ">불량교환</option>
                            <option value=2 " . ($return_type == 2 ? "selected" : "") . ">상품교환</option>
                            <option value=3 " . ($return_type == 3 ? "selected" : "") . ">환불	</option>
                            <option value=4 " . ($return_type == 4 ? "selected" : "") . ">반품	</option>
                        </select>
                    </td>
                    <td class='left return_product' noWrap='nowrap'>
                        <div class='show_rp' style='display:none;'>
                            <span class='p_title'>상품코드 :</span><span class='p_data_btn rp_id' onclick='javascript:product_change(this)'>$rp_id</span><br>
                            <span class='p_title'>상품명 :</span><span class='p_data rp_name'>$rp_name</span><br>
                            <span class='p_title'>옵션 :</span><span class='p_data rp_options'>$rp_options</span>
                        </div>
                        <div class='hide_rp'>&nbsp;</div>
                    </td>
                    <td  class='return_qty' noWrap='nowrap'>
                        <div class='show_rp' style='display:none;'>
                            <input class='input22 input_num return_qty' onfocus='input_num_focus(this)' onblur='onblur_return_qty(this)' type=text value='$return_qty' >
                        </div>
                        <div class='hide_rp'>&nbsp;</div>
                    </td>
                    <td class='left return_money' noWrap='nowrap'>
                        <div class='show_rp' style='display:none;'>
                            <span class='p_title'>반품상품 :</span>
                                <span class='p_price send_org'>$send_org</span>*
                                <span class='p_qty send_qty'>$send_qty</span>=
                                <span class='p_price send_price'>$send_price</span><br>
                            <span class='p_title'>교환상품 :</span>
                                <span class='p_price return_org'>$return_org</span>*
                                <span class='p_qty return_qty'>$return_qty</span>=
                                <span class='p_price return_price'>$return_price</span><br>
                            <span class='p_title'>차액 :</span><input class='input22 input_price return_sub_money' onfocus='input_num_focus(this)' onblur='input_num_blur(this, \"num_int\")' type=text value='$return_sub_money'>
                        </div>
                        <div class='show_sp' style='display:none;'>
                            <span class='p_price send_org'>$send_org</span>*
                            <span class='p_qty send_qty'>$send_qty</span>=
                            <input class='input22 input_price_short return_money' onfocus='input_num_focus(this)' onblur='input_num_blur(this, \"num_int\")' type=text value='$return_money'>
                        </div>
                        <div class='hide_rp'>&nbsp;</div>
                    </td>
                    <td class='BorderEnd memo' noWrap='nowrap'><input class='input22 input_text memo' type=text value='$memo'></td>
                </tr>";
        return $str;
	}
	
	function del_sheet()
	{
	    global $connect, $sheet_seq;

	    // 이미 출고처리된 전표는 삭제 불가
	    $query = "select status from return_ready_sheet where seq=$sheet_seq";
	    $result = mysql_query($query, $connect);
	    $data = mysql_fetch_assoc($result);
	    
	    $val = array();
	    if( $data[status] == 1 )
	        $val[error] = 1;
	    else
	    {
    	    $query = "update return_ready_sheet set is_del=1, deldate=now(), deluser='$_SESSION[LOGIN_ID]' where seq=$sheet_seq";
    	    mysql_query($query, $connect);
    	    $val[error] = 0;
    	}
    	echo json_encode( $val );
	}

	function restore_sheet()
	{
	    global $connect, $sheet_seq;

	    $query = "update return_ready_sheet set is_del=0, deldate='', deluser='' where seq=$sheet_seq";
	    mysql_query($query, $connect);
	    $val[error] = 0;

    	echo json_encode( $val );
	}
	
	function return_ready_sheet_log($sheet_no, $product_id, $type, $qty, $money, $memo="")
	{
	    global $connect;
	    
        $ready_qty = 0;
        $in_qty = 0;
        $arrange_qty = 0;
        
        $ready_money = 0;
        $in_money = 0;
        $arrange_money = 0;

	    // 교환대기
	    if( $type == 0 )
	    {
	        $ready_qty = $qty;
	        $ready_money = $money;
	    }
	    // 교환입고
	    else if( $type == 1 )
	    {
	        $in_qty = $qty;
	        $in_money = $money;
	    }
	    // 조정
	    else
	    {
	        $arrange_qty = $qty;
	        $arrange_money = $money;
	    }

	    $query = "insert return_ready_sheet_log
	                 set crdate = now()
	                    ,cruser = '$_SESSION[LOGIN_ID]'
	                    ,product_id = '$product_id'
	                    ,work_type = $type
	                    ,ready_qty = $ready_qty
	                    ,in_qty = $in_qty
	                    ,arrange_qty = $arrange_qty
	                    ,ready_money = $ready_money
	                    ,in_money = $in_money
	                    ,arrange_money = $arrange_money
	                    ,sheet_no = $sheet_no
	                    ,memo = '$memo' ";
debug("교환대기 로그 : " . $query);
        mysql_query($query, $connect);
	}
}
?>
