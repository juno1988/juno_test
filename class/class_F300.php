<?
require_once "class_C.php";
require_once "class_top.php";
require_once "class_statrule.php";
require_once "class_statrule2.php";
require_once "class_statconfig.php";
require_once "class_file.php";
require_once "class_ui.php";
require_once "class_order.php";
require_once "class_product.php";
require_once "class_shop.php";
require_once "class_order_products.php";


////////////////////////////////
// class name: class_F300
// 판매처별 정산통계

class class_F300 extends class_top {

    //********************************
    // 단순 출력 - jk
    function F308()
    {   
        global $connect;
        global $template, $line_per_page;
        global $shop_id, $start_date, $end_date, $product_id;
        
        // page 출력
        $master_code = substr( $template, 0,1);

        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //********************************
    // 단순 출력 - jk
    function F305()
    {
        global $connect;
        global $template, $line_per_page;
        global $shop_id, $start_date, $end_date, $product_id;
    
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 단순 출력 2
    // 2010.1.5 - jk
    function F306()
    {
        global $connect;
        global $template, $line_per_page;
        global $shop_id, $date_type, $start_date, $end_date, $product_id, $ex_cancel, $ex_gift, $ex_hold;
    
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    function save_file_F306()
    {
        global $connect, $template;
		global $date_type, $start_date, $end_date, $product_id, $ex_cancel, $ex_gift, $page;
		
		$temp_data = $this->stat_product_list2(1);
		
		$arr_datas =  array();
		$title_data = array();
		
    	$title_data[start_date]		= $start_date;
    	$title_data[end_date]		= $end_date;
    	$title_data[supply_name]	= $temp_data[supply_name];
    	$title_data[name]			= $temp_data[name];
		$arr_datas = $temp_data['list'];
    	

        $this->make_file_F306($title_data, $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
    function make_file_F306( $title_data, $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $connect, $template;
		global $date_type, $start_date, $end_date, $product_id, $ex_cancel, $ex_gift, $page;
		
    	$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item colspan=2>	상품코드  </td>";
			$buffer .="<td class=str_item>	$product_id   	</td>";			
        $buffer .= "</tr>\n";
        
        $buffer = "<tr>\n";
	        $buffer .="<td class=header_item colspan=2>	상품명  	</td>";
			$buffer .="<td class=str_item>	$title_data[name]</td>";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
			$buffer .="<td class=header_item colspan=2>	공급처  </td>";
			$buffer .="<td class=str_item>	$title_data[supply_name]   	</td>";		
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
        	$buffer .="<td class=header_item colspan=2>	기간  	</td>";
			$buffer .="<td class=str_item>	$title_data[start_date] ~ $title_data[end_date]</td>";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
			$buffer .="<td class=header_item>	판매처  </td>";
			$buffer .="<td class=header_item>	옵션   	</td>";
			$buffer .="<td class=header_item>	개수  	</td>";
        $buffer .= "</tr>\n";
        			
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	if($key == 'qty')
            		$buffer .= "<td class=num_item>$v</td>\n";
				else
                	$buffer .= "<td class=str_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    function download_F306()
    {
        global $filename;
        global $start_date	,	$end_date	,$product_id;
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date." ".$product_id."_F306.xls");
    } 
    
    // 단순 출력 2
    // 2010.1.5 - jk
    function F307()
    {
        global $connect, $template, $shop_id, $date_type, $start_date, $end_date, $product_id, $ex_cancel, $ex_gift, $ex_hold;
			
        $arr_data = $this->shop_product_list();
        
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //*****************************
    //
    function shop_product_list($type ='')
    {
        global $connect, $date_type, $start_date, $end_date, $product_id, $shop_id, $ex_cancel, $ex_gift, $ex_hold;
        
        $arra_data = array();                
        $arr_data['total_rows'] = 10;    
        
        // get product_ids
        $query = "select a.shop_id, 
                         c.name ,
                         sum(a.qty) qty,
                         c.supply_code,
                         max(a.org_price/a.qty) a_org_price,
                         if(c.org_id>0,c.org_id,c.product_id) new_org
                    from order_products a,
                         orders b,
                         products c
                   where a.order_seq  = b.seq 
                     and b.$date_type >= '$start_date 00:00:00'
                     and b.$date_type <= '$end_date 23:59:59'";

        if( $ex_cancel )
            $query .= "  and a.order_cs not in (1,2,3,4) ";
                     
        if( $ex_gift )
            $query .= "  and a.is_gift = 0 ";
            
        if( $ex_hold )
            $query .= "  and b.hold = 0 ";    
            
		if($type == "all")
		{
			$query .= "  and a.product_id = c.product_id
	                     and b.c_seq = 0
	                group by new_org, shop_id
	                order by shop_id desc, qty desc";	
		}
		else
		{
			$query .= "  and a.product_id = c.product_id
	                     and a.shop_id    = $shop_id
	                     and b.c_seq = 0
	                group by new_org
	                order by qty desc";	
		}
		
        $result = mysql_query( $query, $connect );        
        
        $i=0;
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            if ( $i == 0 )
            {
                $arr_data['name']      = $data['name'];
                $arr_data['supply_id'] = $data['supply_code'];
                $arr_data['shop_name'] = $this->get_shop_name( $data[shop_id] );
            }
            
            // 
            $supply_name = $this->get_supply_name2( $data[supply_code] );
            $arr_data['list'][] = array( 
                                      product_name  => $data[name]
                                      ,supply_name  => $supply_name
                                      ,options      => $data[options]
                                      ,qty          => $data[qty]
                                      ,org_price    => $data[a_org_price]
                                      ,shop_id    	=> $data[shop_id]
                                  );
            $i++;
        }
        return $arr_data;
    }
    
    // 
    // 상품판매 내역 - 2009.11.18 - jk
    function stat_product_list2($is_download = 0)
    {
        global $connect, $date_type, $start_date, $end_date, $product_id, $ex_cancel, $ex_gift, $ex_hold, $page;
        $start = ($page - 1) * 30;
        
        $arra_data = array();                
        // total 개수
        $arr_data['total_rows'] = 10;
        
        // 상품이 옵션 없는 상품일 경우
        $query = "select product_id from products where org_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
            $pid_str = " and c.org_id='$product_id' ";
        else
            $pid_str = " and c.product_id='$product_id' ";
        
        // list
        $query = "select a.shop_id, c.name ,c.options,sum(a.qty) qty, c.supply_code supply_id
                    from order_products a, 
                         orders b,
                         products c
                   where a.order_seq  = b.seq 
                     and b.$date_type >= '$start_date 00:00:00'
                     and b.$date_type <= '$end_date 23:59:59'
                     and b.c_seq = 0
                     and a.product_id = c.product_id ";
        if( $ex_cancel )
            $query .= " and a.order_cs not in (1,2,3,4) ";

        if( $ex_gift )
            $query .= " and a.is_gift = 0 ";
            
        if( $ex_hold )
            $query .= " and b.hold = 0 ";

        $query .= " $pid_str
                group by b.shop_id, a.product_id";
debug("당일판매 상품 상세 : " . $query);
        $result = mysql_query( $query, $connect );
        
        $i=0;
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            if ( $i == 0 )
            {
                $arr_data['name']      = $data['name'];
                $arr_data['supply_id'] = $data['supply_id'];
            }
            
            $_shop_name = $this->get_shop_name( $data[shop_id] );
            
            $arr_data['list'][] = array( 
                                      shop_name    => $_shop_name
                                      ,options      => $data[options]
                                      ,qty          => $data[qty]
                                  );
            $i++;
        }
        
        // get supply_code
        if ( $arr_data['supply_id'] )
        {
            $query = "select name from userinfo where code=" . $arr_data['supply_id'];
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            
            $arr_data['supply_name'] = $data[name];
        }
        
        // shop_code
        
        if($is_download)
        	return $arr_data;
        else
        	echo json_encode( $arr_data );
    }
    
    
    // 상품판매 내역 - 2009.11.18 - jk
    function stat_product_list()
    {
        global $connect, $start_date, $end_date, $product_id, $page;
        $start = ($page - 1) * 30;
        
        $arra_data = array();                
        // total 개수
        $query = "select count(*) cnt
                    from order_products a, 
                         orders b,
                         products c
                   where a.order_seq  = b.seq 
                     and b.collect_date >= '$start_date'
                     and b.collect_date <= '$end_date'
                     and c.org_id='$product_id'
                     and a.product_id = c.product_id";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );        
        $arr_data['total_rows'] = $data[cnt];
        
        // list
        $query = "select a.*, c.name ,c.options,b.collect_date,b.order_id,c.supply_code,b.recv_name, b.status
                    from order_products a, 
                         orders b,
                         products c
                   where a.order_seq  = b.seq 
                     and b.collect_date >= '$start_date'
                     and b.collect_date <= '$end_date'
                     and c.org_id='$product_id'
                     and a.product_id = c.product_id
                     order by b.seq desc 
                     limit $start, 30";
            
        
        $result = mysql_query( $query, $connect );
        
        $i=0;
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            if ( $i == 0 )
            {
                $arr_data['name']      = $data['name'];
                $arr_data['supply_id'] = $data['supply_id'];
            }
            
            $_shop_name = $this->get_shop_name( $data[shop_id] );
            
            $arr_data['list'][] = array( 
                                      order_id      => $data[order_id]
                                      ,shop_id      => $data[shop_id]
                                      ,shop_name    => $_shop_name
                                      ,collect_date => $data[collect_date]
                                      ,options      => $data[options]
                                      ,status       => $data[status]
                                      ,order_cs     => $data[order_cs]
                                      ,qty          => $data[qty]
                                      
                                  );
            $i++;
        }
        
        // get supply_code
        if ( $arr_data['supply_id'] )
        {
            $query = "select name from userinfo where code=" . $arr_data['supply_id'];
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            
            $arr_data['supply_name'] = $data[name];
        }
        
        // shop_code
        
        echo json_encode( $arr_data );
    }
    
    function get_shop_name( $shop_id )
    {
        global $connect;
        
        $query  = "select * from shopinfo where shop_id=$shop_id";
        $result = mysql_query( $query, $connect );        
        $data   = mysql_fetch_assoc( $result );
        return $data['shop_name'];
    }
    
    //********************************
    // 단순 출력 - jk
    function F304()
    {
        global $connect;
        global $template, $line_per_page, $date_type, $start_date, $end_date, $ex_cancel, $ex_gift, $ex_hold;
    
    
        //$obj        = new class_statconfig();
        //$arr_config = $obj->get_config();

		if ( _DOMAIN_ == "box4u" )
		{
			if (!$date_type)
				$date_type = "trans_date_pos";			
		}
		else
			$date_type = "collect_date";	

        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //
    // 당일판매분 요약표 파일 생성
    function save_file()
    {
        $obj = new class_statrule();
        // product_list
        $is_download = 1;
if( $_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2 ) {
        $_arr_product = $obj->get_product_list( $is_download );
}else{
        $_arr_product = $obj->get_product_list2( $is_download );
}        
        $fn = $this->make_file( $_arr_product );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file( $_arr_product )
    {
        
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_summary_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
        
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        
        fwrite($handle, $buffer);
 
        //******************************
        // step 1. begin of arr_product
if( $_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2 ) {
        $_arr = array(
            "상품명"
            ,"수량"
            ,"원가"
        );
}else{
        $_arr = array(
            "수량",
            "상품명"
        );
}            
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'> " . $value . " </td>";
            
        $buffer .= "</tr>\n";        
        fwrite($handle, $buffer);

        // for row
        foreach( $_arr_product as $row )
        {
            $buffer = "<tr>\n";
            $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
            $style2 = 'font:9pt "굴림"; white-space:nowrap;';
           
if( $_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2 ) {
            $buffer .= "
                    <td style='$style1'>" . $row['name']      . "</td>
                    <td style='$style1'>" . $row['qty']       . "</td>
                    <td style='$style1'>" . $row['org_price'] . "</td>
            ";
}else{
            $buffer .= "
                    <td style='$style1'>" . $row['qty']       . "</td>
                    <td style='$style1'>" . $row['name']      . "</td>
            ";
}            
            $buffer .= "</tr>\n";
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
        
        if( !$new_name )  $new_name = str_replace("+", "%20", urlencode("당일판매분 요약표.xls"));
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }  
    
    //********************************
    // 단순 출력 - jk
    function F301()
    {
        global $connect;
        global $template, $line_per_page;
    
        $obj        = new class_statconfig();
        $arr_config = $obj->get_config();

        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //********************************
    // 단순 출력 - jk
    // 주문 리스트 상세보기
    function F302()
    {
        global $connect;
        global $template, $shop_id, $type, $start_date, $end_date, $query_type, $except_add_order;
    
        //****************************************************
        //
        // get_sale_info
        //
        // 전체 주문 수량 정보
        if ( $type == "tot_order" )
        {
            $ex_add_order = ($except_add_order ? 1 : 0);
            $arr_info = array( 
                        shop_id     => $shop_id
                        ,date_type  => $query_type
                        ,start_date => $start_date
                        ,end_date   => $end_date
                        ,ex_add_order => $ex_add_order ); 
                        
            $obj_order = new class_order();
            $result    = $obj_order->get_list( $arr_info );
        }
        else if ( $type == "cancel" )
        {
            $obj_rule   = new class_statrule();    
            if ( $obj_rule->m_arr_config['cancel'] == "refund_date" )
            {
                 $query = "select a.shop_id,a.seq,a.collect_date,b.cancel_date, b.product_id, b.refund_price, b.order_cs, b.qty, a.amount, a.pay_type
                                 ,a.supply_price,a.order_id,a.shop_id,a.code11,a.code12,a.code13,code14,code15,code16,code17,code18,code19,code20
                             from orders a, 
                                  order_products b
                            where a.seq = b.order_seq
                              and b.cancel_date >= '$start_date 00:00:00' 
                              and b.cancel_date <= '$end_date 23:59:59'
                              and b.order_cs in (1,2,3,4) 
                              and a.c_seq = 0";
                                    
                if ( $shop_id )
                    $query .= " and a.shop_id = '$shop_id'";
                
                if( $query_type == "trans_date_pos" )
                {
                    $min_trans_date_pos = $this->get_min_cancel_trans_date_pos( $start_date, $end_date, $str_shop_id  );                    
                    $query .= " and a." . $query_type . " >= '$min_trans_date_pos 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' ";
                }

                $query .= " group by a.seq";
                
                debug( "f302 cancel: $query" );
                
                $result = mysql_query( $query, $connect );
            }
            else
            {   
                $arr_info = array( 
                            shop_id       => $shop_id
                            ,date_type    => $query_type
                            ,start_date   => $start_date
                            ,end_date     => $end_date
                            ,order_cs     => '1,2,3,4'); 
                            
                $obj_order = new class_order();
                $result    = $obj_order->get_list( $arr_info );
            }
        }
        
        //*****
        //$obj_rule   = new class_statrule();        
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_info = class_product::get_product_infos($data[product_id]);
            $extra_money = class_order_products::get_extra_money( $data[seq] );
            
            // 배송 후 교환건 제외 2014.1.10 - jkryu
            if ( $data[c_seq] > 0 )
                continue;
            
            $arr_result[] = array(
                order_id      => $data[order_id]
                ,shop_id      => $data[shop_id]
                ,seq          => $data[seq]
                ,collect_date => $data[collect_date]
                ,pay_type	  => $data[pay_type]
                ,product_name => $arr_info[name]
                ,amount       => $data[amount] + $extra_money
                ,supply_price => $data[supply_price] + $extra_money
                ,qty          => $data[qty]
                //,amount       => $obj_rule->get_price($data,"amount")
                //,supply_price => $obj_rule->get_price($data,"supply_price")
            ); 
        }
        
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //********************************
    // 단순 출력 - jk
    // 상품 리스트 상세보기
    function F303()
    {
        global $connect;
        global $template, $shop_id, $type, $start_date, $end_date, $query_type;
        
        $obj_rule   = new class_statrule();        
        $obj_order_products= new class_order_products();
        $arr_result = array();
        
        // set
        $obj = new class_statconfig();
        $this->m_arr_config = $obj->get_config(); 
        
        //****************************************************
        //
        // get_sale_info
        //
        // 전체 주문 수량 정보
        if ( $type == "tot_order" )
        {
            $arr_info = array( 
                        shop_id     => $shop_id
                        ,date_type  => $query_type
                        ,start_date => $start_date
                        ,end_date   => $end_date); 
                        
            $obj_order = new class_order();
            $result    = $obj_order->get_product_list2( $arr_info );
        }
        // 상품 취소
        else if ( $type == "cancel" )
        {
            if ( $obj_rule->m_arr_config['cancel'] == "refund_date" )
            {
                 $query = "select b.order_seq,a.seq,a.collect_date,b.cancel_date, b.refund_price, b.product_id, b.order_cs
                                , b.qty, a.amount,a.supply_price,a.order_id,a.shop_id,a.code11,a.code12,a.code13
                                ,code14,code15,code16,code17,code18,code19,code20,a.status status,a.c_seq,b.org_price
                             from orders a, 
                                  order_products b
                            where a.seq = b.order_seq
                              and b.cancel_date >= '$start_date 00:00:00'
                              and b.cancel_date <= '$end_date 23:59:59'
                              and b.order_cs in (1,2,3,4)";
                                    
                if ( $shop_id )
                    $query .= " and a.shop_id = '$shop_id'";
                
                if( $query_type == "trans_date_pos" )
                {
                    $min_trans_date_pos = $this->get_min_cancel_trans_date_pos( $start_date, $end_date, $str_shop_id  );                    
                    $query .= " and a." . $query_type . " >= '$min_trans_date_pos 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' ";
                }

                $query .= " order by a.seq, b.is_gift";
                $result = mysql_query( $query, $connect );
            }
            else
            {
                $arr_info = array( 
                            shop_id     => $shop_id
                            ,date_type  => $query_type
                            ,start_date => $start_date
                            ,end_date   => $end_date
                            ,order_cs   => "1,2,3,4"); 
                            
                $obj_order = new class_order();
                $result    = $obj_order->get_product_list2( $arr_info );
            }
        }
        else if ( $type == "change" )
        {
            $arr_info = array( 
                        shop_id       => $shop_id
                        ,date_type    => $query_type
                        ,start_date   => $start_date
                        ,end_date     => $end_date
                        ,order_cs     => '5,6,7,8'); 
                        
            $obj_order = new class_order();
            $result    = $obj_order->get_product_list2( $arr_info );
        }
        
        //*****
        // 
        $_before_seq = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_info  = class_product::get_info($data[product_id]);
            $arr_price = class_product::get_price_arr( $data[product_id], $data[shop_id]);
            
            // 배송 후 교환건 제외 2014.1.10 - jkryu
            if ( $data[c_seq] > 0 )
                continue;
    
            if( $data[org_price] > 0 && _DOMAIN_ != 'mooas')
                $org_price = $data[org_price];
            else
                $org_price = $arr_price[org_price] * $data[qty];            
                  
            $arr_data = array(
                order_id      => $data[order_id]
                //,seq          => $data[seq]
                ,order_seq    => $data[order_seq]
                ,collect_date => $data[collect_date]
                ,cancel_date  => $data[cancel_date]
                ,product_id   => $data[product_id]
                ,product_name => $arr_info[name]
                ,brand        => $arr_info[brand]
                ,options      => $arr_info[options]
                ,origin       => $arr_info[origin]
                ,weight       => $arr_info[weight]
                ,qty          => $data[qty]
                ,status       => $data[status]
                ,order_cs     => $data[order_cs]
                ,extra_money  => $data[extra_money]
                ,org_price    => $org_price
            ); 
            
            // F303 취소로직 계산..
            if ( $data[seq] != $_before_seq )            
            {
                // 전체 취소의 경우 정산 금액 전체를 취소 함.
                if ( $data[order_cs]  )
                {
                    // 원주문 포함 신규주문 누락
                    if ( $this->m_arr_config[change] == "org" && $data[c_seq] > 0 )
                    {
                        $arr_data['refund_price'] = 0;
                    }
                    else
                    {
                        // child 의 refund_price의 합
                        if ( $obj_rule->m_arr_config['cancel'] == "refund_date" )
                            $arr_data['refund_price'] = $obj_order_products->get_refund_price( $data[seq], $start_date, $end_date );
                        else
                            $arr_data['refund_price'] = $obj_order_products->get_refund_price( $data[seq] );        
                    }
                }
                else
                    $arr_data['refund_price'] = 0;
                    
                //$arr_data[amount]         = $obj_rule->get_price($data,"amount");
                //$arr_data[supply_price]   = $obj_rule->get_price($data,"supply_price");
                $arr_data[supply_price]   = $data[supply_price];
                $arr_data[amount]         = $data[amount];
                
            }
            else
            {
                $arr_data['refund_price'] = 0;
                $arr_data[amount]       = 0;
                $arr_data[supply_price] = 0;
            }
            
            $_before_seq = $data[seq];            
            $arr_result[] = $arr_data;
        }
        
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    //****
    // rule을 save.
    function save_rule()
    {
        global $connect,$shop_id,$amount,$prepay_trans,$supply_price;
        
        $shop_code = $shop_id % 100;
        $query = "delete from stat_rule where shop_code=$shop_code";
        mysql_query ($query, $connect);
        
        $arr_items = array( "amount", "prepay_trans", "supply_price" );
        $query = "insert into stat_rule 
                     set shop_code='$shop_code'
                         ,amount       = '$amount'
                         ,supply_price = '$supply_price'
                         ,prepay_trans = '$prepay_trans'";
        
        
        mysql_query ($query, $connect);
    
        $arr_result = array( "success" => 1 );
        echo json_encode( $arr_result );
    }

    //****
    // rule을 load함.
    function load_rule()
    {
        global $connect,$shop_id;
        
        // 값이 없다고 가정..
        $arr_result = array( success=>0);
               
        if ( $shop_id )
        {
            //$shop_code = $shop_id % 100;
            //$query      = "select * from stat_rule where shop_code=$shop_code"; 
            $query      = "select * from stat_rule2 where shop_id = $shop_id";             
            $result     = mysql_query( $query, $connect );
            $data       = mysql_fetch_assoc( $result );
            
            // 값이 있을때만 성공
            if ( $data )
            {
                $arr_result['rule']           = $data;
                $arr_result['success'] = 1;
            }
        }
        
        // shopheader에서 값을 가져온다.
        $query = "select * 
                    from shopheader 
                   where shop_id=$shop_id
                     and field_id in ( 'code11','code12','code13','code14','code15','code16','code17','code18','code19','code20'
                                      ,'code31','code32','code33','code34','code35','code36','code37','code38','code39','code40' )
                     order by field_id";

        $result = mysql_query( $query, $connect );
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_result['header'][] = array( 'key' => $data[shop_header], 'value' => $data[field_id] );
            $arr_result['success_header'] = 1;
        }
        
        echo json_encode( $arr_result );
    }

    // config update
    function update_config()
    {
        global $connect;
        $arr_items = array( "change", "cancel", "usertrans_price" );

        $obj = new class_statconfig();
        foreach ( $arr_items as $key )
        {
            global $$key;
            $obj->save_config( $key, $$key );
        }    

        // F301 page로 이동
        $this->redirect('?template=F301');    
    }

    //********************************
    // 단순 출력 - jk
    function F300()
    {   
        global $connect;
        global $template, $line_per_page;

        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //********************************
    // 검색 
    // 판매처별 정산통계2 ...
    function query2()
    {
        global $connect, $template;
        global $query_type, $start_date, $end_date, $apply_cancel_date, $shop_id, $bck_search;

        
        // echo $str_shop_id;
        
        $this->show_wait();
              
        // 판매처별 정산통계2 ...
debug("### get_sale_info START ###");
        $arr_shopinfo = $this->get_sale_info();
//debug("### get_sale_info END ###");
        $this->hide_wait();

        $file = "";
            
        // open file
        $_file = _upload_dir . "F308_판매처별정산통계_" . $_SESSION[LOGIN_ID] . ".xls" ;
        $handle = fopen ( $_file, "w");

        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        echo "<script language=javascript>set_download('/data/" . _DOMAIN_ . "/F308_판매처별정산통계_" . $_SESSION[LOGIN_ID] . ".xls')</script>";       
        fclose( $handle );
    }
    
    //**************************************
    // sale info
    // date: 2010.1.25 - jk
    //
    var $m_arr_config = array();
    
    function get_sale_info()
    {
        global $connect;        
        global $query_type, $start_date, $end_date, $apply_cancel_date,$shop_id;
        
        // 배송마진 계산을 위한 array
        $arr_margin_trans = array();
        
        $str_shop_id = "";    
        foreach ( $shop_id as $_id )
        {
            $str_shop_id .= $str_shop_id ? "," : "";
            $str_shop_id .=  $_id;
        }
        
        // m_arr_config를 set
        $obj = new class_statconfig();
        $this->m_arr_config = $obj->get_config();    
        

        ///////////////////////////// 
        //
        // 1. 전체 주문 정보
        //
        // 주문정보 
        $arr_shopinfo = array();    

  		$this->show_txt( "1단계 작업중입니다(1/4 STEP)" );

		//-----------------------------------------------
        // 선결제 택배수량 (속도개선 * -> 개별필드로 수정 (syhwang 2011.7.31)
        // $query = "select * from orders ";
        
        //
        // $query_type이 collect_date일 경우에는 합포 수가 기준
        // ,if(pack<>null||pack<>0,pack, seq ) xx
        // $query_type이 collect_date가 아닐경우에는 송장 수가 기준..
        //
        if ( $query_type == "collect_date" )
        {
            $query = "select shop_id, count(*) cnt,if(pack<>null||pack<>0,pack, seq ) xx,pack,trans_who
                        from orders 
                       where $query_type >= '$start_date' and $query_type <= '$end_date'";
            if ( $str_shop_id )
                $query .= " and shop_id in ($str_shop_id)";     
                
            //$query .= " and order_cs<>1 and trans_who='선불' group by xx";
            $query .= " and order_cs<>1 group by xx";
        }
        else
        {
            $query = "select distinct trans_no, shop_id, count(*) cnt, if(pack<>null||pack<>0,pack, seq ) xx, pack,trans_who
                        from orders 
                       where $query_type >= '$start_date 00:00:00' and $query_type <= '$end_date 23:59:59'";
            
            if ( $str_shop_id )
                $query .= " and shop_id in ($str_shop_id)";     
            
            //$query .= " and order_cs<>1 and trans_who='선불' group by trans_no";
            $query .= " and order_cs<>1 group by trans_no";
        }
        if(_DOMAIN_ == "box4u")
        {
        	$query = "select shop_id, count(*) cnt, sum(qty) sum_qty, if(pack<>null||pack<>0,pack, seq ) xx, pack,trans_who
                        from orders 
                       where $query_type >= '$start_date 00:00:00' and $query_type <= '$end_date 23:59:59' AND trans_corp NOT IN(30019, 30067)";
            if ( $str_shop_id )
                $query .= " and shop_id in ($str_shop_id)";
                
            $query .= " and order_cs<>1 group by shop_id";
        }
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc($result))
        {
            if ( $data['trans_who'] == "선불" )
            {
            	if(_DOMAIN_ == "box4u")
            	{
            		$arr_shopinfo[$data[shop_id]][usertrans_cnt] = $data[sum_qty];
            	}
            	else
                	$arr_shopinfo[$data[shop_id]][usertrans_cnt]++;
            }
            
            // 배송 마진 계산을 위한 부분.
            $arr_margin_trans[] = array(
                seq        => $data['xx']
                ,shop_id   => $data['shop_id']
                ,is_pack   => $data['pack']
                ,trans_who => $data['trans_who']
                ,price     => 0
            );
        }


  		$this->show_txt( "2단계 작업중입니다(2/4 STEP)" );

		//-----------------------------------------------
        // 정산  (속도개선을 위하여 * -> 개별필드로 수정 (syhwang 2011.7.31)
        // $query = "select * from orders ";
        $query = "select  seq
                        , order_id
						, order_cs
						, shop_id
						, amount
						, supply_price
						, refund_price
						, extra_money
						, prepay_cnt
						, prepay_price 
						, c_seq
				   from   orders ";

        if( $query_type == "collect_date" )
            $query .= "where $query_type >= '$start_date' and $query_type <= '$end_date'";
        else
            $query .= "where $query_type >= '$start_date 00:00:00' and $query_type <= '$end_date 23:59:59'";
        
        if ( $str_shop_id )
            $query .= " and shop_id in ( $str_shop_id )";                     

        $result = mysql_query( $query, $connect );
        
        // 선결제를 count하기 위한 주문번호 2011.11.29 - jk
        $prepay_org_id = "";
        while ( $data = mysql_fetch_assoc($result))
        {
            //*************************
            // 배송후 교환 주문
            //*************************
            if ( $this->m_arr_config[change] == "org")
            {
                // 원주문 포함 신규주문 누락
                if( $data[c_seq] > 0 )  continue;
            }
            else
            {
                // 원주문 누락 신규주문 포함 
                if( $data[order_cs] == 8 )  continue;
            }

            // 주문수량, 판매가, 정산가
            $arr_shopinfo[$data[shop_id]][tot_order]++;
            $arr_shopinfo[$data[shop_id]][tot_amount] += $data[amount];
            $arr_shopinfo[$data[shop_id]][tot_supply_price] += $data[supply_price];

            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            // case1 : 취소
            // 환경설정 : 취소 기준이 발주일일 경우.
            // echo "$this->m_arr_config[cancel]";
            
            if ( $this->m_arr_config[cancel] == "collect_date")
            {
                // 1.1 전체취소
                // 공급가 + extramoney
                if ( $data[order_cs] == 1 || $data[order_cs] == 3 )
                {
                    // is_all을 1로 설정
                    $extra_money = class_order_products::get_extra_money( $data[seq], "1" );

                    $arr_shopinfo[$data[shop_id]][cancel_order]++;                
                    $arr_shopinfo[$data[shop_id]][cancel_price] += $data[supply_price] + $extra_money;
                    $arr_shopinfo[$data[shop_id]][cancel_amount_price] += $data[amount] + $extra_money;
                }
                // 부분취소
                // 
                else if ( $data[order_cs] == 2 || $data[order_cs] == 4 )
                {
                    $refund_price = class_order_products::get_refund_price( $data[seq] );
                    
                    $arr_shopinfo[$data[shop_id]][cancel_order]++;                
                    //$arr_shopinfo[$data[shop_id]][cancel_price] += $data[refund_price];
                    $arr_shopinfo[$data[shop_id]][cancel_price] += $refund_price;
                    $arr_shopinfo[$data[shop_id]][cancel_amount_price] += $refund_price;
                }
            }

            

            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
            //
            // case 2 : 교환 금액
            //
            if ( $this->m_arr_config[change] == "org")
            {
                // 원주문 포함 신규주문 누락
                if( $data[c_seq] > 0 )  continue;
            }
            else
            {
                // 원주문 누락 신규주문 포함 
                if( $data[order_cs] == 8 )  continue;
            }

            //extra_money를 계산한다.
            // is_all을 1로 설정
            $arr_shopinfo[$data[shop_id]][extra_money] += class_order_products::get_extra_money( $data[seq],"1" );;
            
            //*************************************************************
            // case 3. 택배비 => 택배비는 배송후 교환건도 모두 포함한다. 2011-07-07 장경희
            if ( $data[order_cs] != 1 )
            {
                // makeshop도 추가
                // 쿠팡도 추가 2013.12.6 - jkryu || $data[shop_id]%100 == 53
                if (   $data[shop_id]%100 == 1
                    || $data[shop_id]%100 == 68
                    || $data[shop_id]%100 == 53
                    || $data[shop_id]%100 == 49
                )
                {
                    if ( $prepay_org_id != $data[order_id] )
                    {
                        $arr_shopinfo[$data[shop_id]][prepay_cnt]   += $data[prepay_cnt];
                        $arr_shopinfo[$data[shop_id]][prepay_price] += $data[prepay_price];
                    }
                }
                else
                {
                    $arr_shopinfo[$data[shop_id]][prepay_cnt]   += $data[prepay_cnt];
                    $arr_shopinfo[$data[shop_id]][prepay_price] += $data[prepay_price];
                }
                    
                $prepay_org_id = $data[order_id];
            }

            // 총 결제
            $arr_shopinfo[$data[shop_id]][tot_amount]       += $amount;

            // 총 공급가(정산예정가)
            $arr_shopinfo[$data[shop_id]][tot_supply_price] += $supply_price;
        }
        // end of orders

  		$this->show_txt( "3단계 작업중입니다(3/4 STEP)" );

        ///////////////////////////// 
        //
        // 2. 전체 상품 정보
        //
        if( _DOMAIN_ == 'mooas' )
        {
            $query = "select a.shop_id a_shop_id,
                             a.order_id a_order_id,
                             a.order_cs a_order_cs,
                             a.c_seq a_c_seq,
                             b.order_cs b_order_cs,
                             b.qty b_qty,
                             c.org_price c_org_price
                        from orders a, order_products b, products c
                       where a.seq = b.order_seq and 
                             b.product_id = c.product_id and ";
        }
        else
        {
            $query = "select a.seq,
                             a.shop_id a_shop_id,
                             a.order_id a_order_id,
                             a.order_cs a_order_cs,
                             a.c_seq a_c_seq,
                             b.order_cs b_order_cs,
                             b.qty b_qty,
                             if( b.org_price * b.qty > 0, b.org_price / b.qty, c.org_price) c_org_price
                        from orders a, order_products b, products c
                       where a.seq = b.order_seq and 
                             b.product_id = c.product_id and ";
        }

        if( $query_type == "collect_date" )
            $query .= " $query_type >= '$start_date' and $query_type <= '$end_date' ";
        else
            $query .= " $query_type >= '$start_date 00:00:00' and $query_type <= '$end_date 23:59:59' ";

        if ( $str_shop_id )
            $query .= " and a.shop_id in ( $str_shop_id )";

        $result = mysql_query( $query, $connect );
		$rows   = mysql_num_rows($result);
		
		debug( "****rows: " . $rows );
		
		$dd     = 0;
		$j      = 0;
        while ( $data = mysql_fetch_assoc($result))
        {
            //*************************
            // 배송후 교환 주문
            //*************************
            if ( $this->m_arr_config[change] == "org")
            {
                // 원주문 포함 신규주문 누락
                if( $data[a_c_seq] > 0 )  
                {
                    continue;
                }
            }
            else
            {
                // 원주문 누락 신규주문 포함 
                if( $data[a_order_cs] == 8 )
                {
                    continue;
                }
            }

            // 전체 상품 수
            $arr_shopinfo[$data[a_shop_id]][tot_products] += $data[b_qty];

            // 원가
            $arr_shopinfo[$data[a_shop_id]][org_price] += $data[c_org_price] * $data[b_qty];

            //debug( $dd++ . "/" . $j . ")" . $data[a_order_id] ." sum_org_price: " . $arr_shopinfo[$data[a_shop_id]][org_price] . " / org_price: " . $data[c_org_price] . "/qty:" . $data[b_qty] );

            // 취소 상품 수
            if ( $this->m_arr_config[cancel] == "collect_date")
            {
                if ( $data[b_order_cs] == 1 || $data[b_order_cs] == 2 || $data[b_order_cs] == 3 || $data[b_order_cs] == 4 )
                {
                    $arr_shopinfo[$data[a_shop_id]][cancel_cnt] += $data[b_qty];
                    
                    // 취소원가
                    $arr_shopinfo[$data[a_shop_id]][cancel_org_price] += $data[c_org_price] * $data[b_qty];
                }
            }

            // 교환 상품 수
            if ( $data[b_order_cs] == 5 || $data[b_order_cs] == 6 || $data[b_order_cs] == 7 || $data[b_order_cs] == 8 )
                $arr_shopinfo[$data[a_shop_id]][change_cnt] += $data[b_qty];
        }
        
  		$this->show_txt( "최종단계 작업중입니다(4/4 STEP)" );

        ///////////////////////////// 
        //
        // 3. 취소일 기준
        //
        $before_seq = "";
        
        // debug("cancel tpe:" . $this->m_arr_config[cancel] );
        if ( $this->m_arr_config[cancel] == "refund_date")
        {
            //**********************
            // 취소 주문 정보
            // fix 2012.11.30 - jkryu
            // a.extra_money => b.extra_money로 변경 order_products에도 extra_money가 있음.
            // a.refund_price => b.refund_price
            $query = "select a.seq, a.shop_id a_shop_id,
                             a.order_id     a_order_id,
                             a.order_cs     a_order_cs,
                             a.c_seq        a_c_seq,
                             a.extra_money  a_extra_money,
                             a.supply_price a_supply_price,
                             a.amount		a_amount,
                             b.refund_price a_refund_price,
                             b.extra_money  extra_money,
                             if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                        from orders a, order_products b
                       where a.seq = b.order_seq and 
                             b.cancel_date >= '$start_date 00:00:00' and 
                             b.cancel_date <= '$end_date 23:59:59' ";
            if ( $str_shop_id )
                $query .= " and a.shop_id in ( $str_shop_id )";

            // 발주일 경우엔 발주일만            
            // 배송일 경우엔 배송일
            if( $query_type == "trans_date_pos" )
            {
                $min_trans_date_pos = $this->get_min_cancel_trans_date_pos( $start_date, $end_date, $str_shop_id  );
                $query .= " and a." . $query_type . " >= '$min_trans_date_pos 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' ";
            }

            // 취소 주문만 
            $query .= " and a.order_cs in (1,2,3,4) order by a.seq";
            debug ( "cancel_query 111: $query ");
            
            $result = mysql_query( $query, $connect );
            $__cnt = 0;
            $before_seq = 0;
            $obj_order_products= new class_order_products();
            
            while ( $data = mysql_fetch_assoc($result))
            {
                $__cnt++;
                //*************************
                // 배송후 교환 주문
                //*************************
                if ( $this->m_arr_config[change] == "org")
                {
                    // 원주문 포함 신규주문 누락
                    if( $data[a_c_seq] > 0 )  continue;
                }
                else
                {
                    // 원주문 누락 신규주문 포함 
                    if( $data[a_order_cs] == 8 )  continue;
                }

                if ( $before_seq != $data[seq] )
                {
                    // 1.1 전체취소
                    $refund_price = 0;
                    if ( $data[a_order_cs] == 1 || $data[a_order_cs] == 3 )
                    {
                        // 동일한 주문 번호에 취소가 여럿이면 오류발생. - fix 2012.12.14
                        $refund_price = $data[a_supply_price] + $data[a_extra_money];
                        $arr_shopinfo[$data[a_shop_id]][cancel_price] += $data[a_supply_price] + $data[a_extra_money];
                        $arr_shopinfo[$data[a_shop_id]][cancel_amount_price] += $data[a_amount] + $data[a_extra_money];
                        
                    }
                    // 부분 취소
                    else if ( $data[a_order_cs] == 2 || $data[a_order_cs] == 4 )
                    {
                        //$arr_shopinfo[$data[a_shop_id]][cancel_price] += $data[a_refund_price];
                        
                        $refund_price = $obj_order_products->get_refund_price( $data[seq] );        
                        $arr_shopinfo[$data[a_shop_id]][cancel_price] += $refund_price;
                        $arr_shopinfo[$data[a_shop_id]][cancel_amount_price] += $refund_price;
                    }
                    
                    // 취소주문수량
                    $arr_shopinfo[$data[a_shop_id]][cancel_order]++;
                    
                    // seq, cancel_price
                    debug("refund_price_vx,$data[seq],$refund_price");
                }
                
                $before_seq = $data[seq];
            }

            //**********************
            // 취소 상품 정보
            $query = "select a.shop_id a_shop_id,
                             a.order_id a_order_id,
                             a.order_cs a_order_cs,
                             a.c_seq a_c_seq,
                             b.order_cs b_order_cs,
                             b.qty b_qty,
                             c.org_price c_org_price
                        from orders a, order_products b, products c
                       where a.seq = b.order_seq and 
                             b.product_id = c.product_id and 
                             b.cancel_date >= '$start_date 00:00:00' and 
                             b.cancel_date <= '$end_date 23:59:59' ";
            if ( $str_shop_id )
                $query .= " and a.shop_id in( $str_shop_id )";
    
            // 취소 주문만 
            $query .= " and b.order_cs in (1,2,3,4) ";
    
            if( $query_type == "trans_date_pos" )
            {
                $min_trans_date_pos = $this->get_min_cancel_trans_date_pos( $start_date, $end_date, $str_shop_id  );
                $query .= " and a." . $query_type . " >= '$min_trans_date_pos 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' ";
            }
            
            debug( "xx cancel: " . $query );
            
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc($result))
            {
                //*************************
                // 배송후 교환 주문
                //*************************
                if ( $this->m_arr_config[change] == "org")
                {
                    // 원주문 포함 신규주문 누락
                    if( $data[a_c_seq] > 0 )  continue;
                }
                else
                {
                    // 원주문 누락 신규주문 포함 
                    if( $data[a_order_cs] == 8 )  continue;
                }
    
                // 전체 상품 수
                $arr_shopinfo[$data[a_shop_id]][cancel_cnt] += $data[b_qty];
    
                debug("cancel_org_price: tot:" . $arr_shopinfo[$data[a_shop_id]][cancel_org_price] . "/price:" . $data[c_org_price] . "/qty:" . $data[b_qty] );
    
                // 원가
                $arr_shopinfo[$data[a_shop_id]][cancel_org_price] += $data[c_org_price] * $data[b_qty];
            }
        }
        
        $obj_rule = new class_statrule2();
        
        // 실정산, 선결제금액
        foreach ( $arr_shopinfo as $_shop_id => $shopinfo )
        {
            $arr_shopinfo[$_shop_id][real_supply_price] = $arr_shopinfo[$_shop_id][tot_supply_price]
                                                          + $arr_shopinfo[$_shop_id][extra_money]
                                                          - $arr_shopinfo[$_shop_id][cancel_price];
            
            // 선불 택배비
            $arr_shopinfo[$_shop_id][usertrans_price] = $arr_shopinfo[$_shop_id][usertrans_cnt] * $this->m_arr_config[usertrans_price];
            
            // 택배마진 2011.12.15 - jk
            // _get_price => get_price로 변경..
            // _get_price는 rule도 가져오는 get_price임.
            // $arr_shopinfo[$_shop_id][margin_trans] = $obj_rule->_get_price("margin_trans",$arr_shopinfo[$_shop_id],$_shop_id);
            $arr_shopinfo[$_shop_id][margin_trans] = $obj_rule->get_margin_trans( $arr_margin_trans, $_shop_id );
        }

        asort($arr_shopinfo );
                
        return $arr_shopinfo;
    }
    
    
    
    //**********************
    // 상품의 원가 구하기
    var $m_arr_price = array();
    function get_org_price( $data )
    {
        global $connect;
        if ( !array_key_exists( $data[product_id], $this->m_arr_price) )
        {
            $query  = "select org_price from products where product_id='$data[product_id]'";
            $result = mysql_query( $query, $connect );
            $info   = mysql_fetch_assoc( $result );
            $this->m_arr_price[ $data[product_id] ] = $info[org_price];
        }
        return $this->m_arr_price[ $data[product_id] ];
    }
    
    //********************************
    // 검색 
    //
    function query()
    {
        global $connect, $template;
        global $query_type, $start_date, $end_date, $apply_cancel_date, $shop_id;
                
        $this->show_wait();
        $obj_rule = new class_statrule();
        // for test
        # $arr = $obj_rule->get_price_all( 558 );
        # print_r ( $arr );

        // $arr_shopname = $this->get_shop_name_arr();
        //****************************************************
        //
        // get_sale_info
        //        
        $arr_shopinfo = $obj_rule->get_sale_info();
        $this->hide_wait();

        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // shop_name을 저장
    function get_shop_name_arr()
    {
        global $connect;
        $arr_infos = array();
        $query = "select shop_id, shop_name from shopinfo";
        $result = mysql_query( $query, $connect );

        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_infos[$data[shop_id]] = $data[shop_name];    
        }
        return $arr_infos;
    }


    //#######################################
    //#######################################
    //
    // 신 정산
    //
    //#######################################
    //#######################################

    //**************************
    // 판매처 정산
    //**************************
    function F310()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date;
        
        // page 출력
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //**************************
    // 검색 
    //**************************
    function query3()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date;
                
        $this->show_wait();

        $arr_stat = $this->get_stat_info();

        $this->hide_wait();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //**************************
    // 정산 계산
    //**************************
    function get_stat_info()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date;

        $val = array();

        //++++++++++++++++++++++++++++
        // 정산 설정
        //++++++++++++++++++++++++++++
        $obj_rule   = new class_statrule();
        
        // 취소주문 기준
        if( $obj_rule->m_arr_config['cancel'] == "refund_date" )
            $cancel_refund_date = true;  // 취소일 기준
        else
            $cancel_refund_date = false; // 발주일(배송일) 기준
            
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $trans_change_org = true;   // 원주문
        else
            $trans_change_org = false;  // 교환주문
            
        // 선불 택배비
        $before_trans_price = $obj_rule->m_arr_config['usertrans_price'];
        // 착불 택배비 수수료
        $after_trans_price  = $_SESSION[AFTER_TRANS_PRICE];

        //++++++++++++++++++++++++++++
        // 판매처
        //++++++++++++++++++++++++++++
        $shop_id_str = "";
        $query = "select * from shopinfo where disable=0 " . ( $shop_id ? " and shop_id=$shop_id " : "" ) . " order by sort_name";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $shop_id_str .= ( $shop_id_str ? "," : "" ) . $data[shop_id];
            
            $val[$data[shop_id]] = array(
                shop_name        => $data[shop_name],
                balju_order_arr  => array(),
                change_order_arr => array(),
                new_order_arr    => array(),
                copy_order_arr   => array(),
                add_order_arr    => array(),
                order_all_arr    => array(),
                b_change_arr     => array(),
                a_change_arr     => array(),
                change_arr       => array(),
                b_cancel_arr     => array(),
                a_cancel_arr     => array(),
                cancel_arr       => array(),
                sell_order_arr   => array(),
                trans_sun_arr    => array(),
                trans_hoo_arr    => array()
            );
        }

        //++++++++++++++++++++++++++++
        // 검색 범위
        //++++++++++++++++++++++++++++
        if( $query_type == 'collect_date' )
            $query_date = " a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query_date = " a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";


        //#####################################
        //  전체 주문 
        //#####################################
        
        // 전체 주문 쿼리
        $query = "select a.seq               a_seq,
                         a.pack              a_pack,
                         a.status            a_status,
                         a.shop_id           a_shop_id,
                         a.create_type       a_create_type,
                         a.trans_who         a_trans_who,
                         b.product_id        b_product_id,
                         b.product_type      b_product_type,
                         b.prd_amount        b_prd_amount,
                         b.prd_supply_price  b_prd_supply_price,
                         b.qty               b_qty,
                         b.order_cs          b_order_cs,
                         b.cancel_date       b_cancel_date,
                         b.cancel_type       b_cancel_type,
                         b.refund_price      b_refund_price,
                         b.is_changed        b_is_changed,
                         b.change_date       b_change_date,
                         b.extra_money       b_extra_money,
                         b.org_price         b_org_price,
                         b.is_gift           b_is_gift
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.status >= 1 and 
                         a.shop_id in ($shop_id_str) and
                         $query_date";
            
        // 배송후 교환 주문 처리
        if( $trans_change_org )
            $query .= " and a.create_type<>3 ";  // 원주문
        else
            $query .= " and b.is_changed not in (7,8) ";  // 생성주문

        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 판매처 코드
            $shop = $data[a_shop_id];

            // 주문 수량 array
            $val[$shop][all_order_arr][]    = $data[a_seq];

            // 추가상품 아니고
            if( $data[b_product_type] == 0 )
            {
                //++++++++++++++++++++++++++++
                // 발주 주문
                //++++++++++++++++++++++++++++
                if( $data[a_create_type] == 0 )
                {
                    $val[$shop][balju_order_arr][]  = $data[a_seq];
                    $val[$shop][balju_product_qty] += $data[b_qty];
                    $val[$shop][balju_amount]      += $data[b_prd_amount];
                }
                
                //++++++++++++++++++++++++++++
                // 생성 주문
                //++++++++++++++++++++++++++++
                else if( $data[a_create_type] == 1 )
                {
                    $val[$shop][new_order_arr][]  = $data[a_seq];
                    $val[$shop][new_product_qty] += $data[b_qty];
                    $val[$shop][new_amount]      += $data[b_prd_amount];
                }
                
                //++++++++++++++++++++++++++++
                // 복사 주문
                //++++++++++++++++++++++++++++
                else if( $data[a_create_type] == 2 )
                {
                    $val[$shop][copy_order_arr][]  = $data[a_seq];
                    $val[$shop][copy_product_qty] += $data[b_qty];
                    $val[$shop][copy_amount]      += $data[b_prd_amount];
                }
                
                //++++++++++++++++++++++++++++
                // 배송후 교환 주문
                //++++++++++++++++++++++++++++
                else if( $data[a_create_type] == 3 )
                {
                    $val[$shop][change_order_arr][]  = $data[a_seq];
                    $val[$shop][change_product_qty] += $data[b_qty];
                    $val[$shop][change_amount]      += $data[b_prd_amount];
                }
            }
            else
            {
                //++++++++++++++++++++++++++++
                // 추가상품
                //++++++++++++++++++++++++++++
                $val[$shop][add_order_arr][]  = $data[a_seq];
                $val[$shop][add_product_qty] += $data[b_qty];
                $val[$shop][add_amount]      += $data[b_prd_amount];
            }
            
            //****************************
            //  교환
            //****************************
            if( $trans_change_org )  // 원주문(발주일)
            {
                // 배송전 교환
                if( $data[b_is_changed] == 5 || $data[b_is_changed] == 6 )
                {
                    $val[$shop][change_arr][]    = $data[a_seq];
                    $val[$shop][b_change_arr][]  = $data[a_seq];
                    $val[$shop][b_change_qty]   += $data[b_qty];
                    $val[$shop][b_extra_money]  += $data[b_extra_money];
                }
                // 배송후 교환
                else if( $data[b_is_changed] == 7 || $data[b_is_changed] == 8 )
                {
                    $val[$shop][change_arr][]    = $data[a_seq];
                    $val[$shop][a_change_arr][]  = $data[a_seq];
                    $val[$shop][a_change_qty]   += $data[b_qty];
                    // 교환 - 원주문(발주일) 기준일 경우, 배송후 교환 extra_money는 계산하지 않는다.
                }
            }
            else  // 교환주문
            {
                // 배송전 교환
                // 교환 - 교환주문 에서 배송전 교환 주문건은 별도 쿼리 필요

                // 배송후 교환
                if( $data[a_create_type] == 3 && ( $data[b_is_changed] == 7 || $data[b_is_changed] == 8 ) )
                {
                    $val[$shop][change_arr][]    = $data[a_seq];
                    $val[$shop][a_change_arr][]  = $data[a_seq];
                    $val[$shop][a_change_qty]   += $data[b_qty];
                    $val[$shop][a_extra_money]  += $data[b_extra_money];
                }
            }

            //******************
            //  취소 
            //******************
            if( $data[b_order_cs]==1 || $data[b_order_cs]==2 || $data[b_order_cs]==3 || $data[b_order_cs]==4 )
            {
                //  취소 - 발주일 기준 (* 취소일 기준은 별도 쿼리 필요)
                if( !$cancel_refund_date )
                {
                    // 배송전 취소
                    if( $data[b_order_cs] == 1 || $data[b_order_cs] == 2 )
                    {
                        $val[$shop][cancel_arr][]    = $data[a_seq];
                        $val[$shop][b_cancel_arr][]  = $data[a_seq];
                        $val[$shop][b_cancel_qty]   += $data[b_qty];
                        $val[$shop][b_refund_price] += $data[b_refund_price];
                    }
                    // 배송후 취소
                    else
                    {
                        $val[$shop][cancel_arr][]    = $data[a_seq];
                        $val[$shop][a_cancel_arr][]  = $data[a_seq];
                        $val[$shop][a_cancel_qty]   += $data[b_qty];
                        $val[$shop][a_refund_price] += $data[b_refund_price];
                    }
                }
            }
            //******************
            //  정상
            //******************
            else
            {
                // 순매출
                $val[$shop][sell_order_arr][]  = $data[a_seq];
                $val[$shop][sell_product_qty] += $data[b_qty];
                $val[$shop][sell_amount]      += $data[b_prd_amount] + $data[b_extra_money];

                // 원가
                $val[$shop][org_price] += $data[b_org_price];
            }

            // 수수료 : 발주주문 또는 배송후 교환주문 중에서, 상품추가 아니고, 고객환불 또는 취소가 아닌경우
            if( ( $data[a_create_type] == 0 || $data[a_create_type] == 3 ) && $data[b_product_type] == 0 &&
                ( $data[b_cancel_type] == 2 || ( $data[b_order_cs] != 1 ||
                                                 $data[b_order_cs] != 2 ||
                                                 $data[b_order_cs] != 3 ||
                                                 $data[b_order_cs] != 4 ) ) )
                $val[$shop][sell_charge] += $data[b_prd_amount] - $data[b_prd_supply_price];
            
            // 사은품
            if( $data[b_is_gift] )
                $val[$shop][gift_qty] += $data[b_qty];

            // 택배비
            if( $data[b_order_cs] != 1 && $data[b_order_cs] != 2 ) 
            {
                if( $data[a_pack] > 0 )
                    $trans = $data[a_pack];
                else
                    $trans = $data[a_seq];

                // 택배비 수량
                if( $data[a_trans_who] == "선불" )
                    $val[$shop][trans_sun_arr][] = $trans;
                else
                    $val[$shop][trans_hoo_arr][] = $trans;
            }
            
        }

        // 날짜 조건에 따라 주문상태 조건 설정
        if( $query_type == 'collect_date' )
            $status = "a.status >= 1 and ";
        else if( $query_type == 'trans_date' )
            $status = "a.status >= 7 and ";
        else if( $query_type == 'trans_date_pos' )
            $status = "a.status >= 8 and ";

        //************************************************
        //  교환 주문(배송전 교환)
        //************************************************
        if( !$trans_change_org )
        {
            // 전체 주문 쿼리
            $query = "select a.seq               a_seq,
                             a.shop_id           a_shop_id,
                             b.qty               b_qty,
                             b.extra_money       b_extra_money
                        from orders a,
                             order_products b
                       where a.seq = b.order_seq and
                             a.shop_id in ($shop_id_str) and
                             $status
                             b.change_date >= '$start_date 00:00:00' and
                             b.change_date <= '$end_date 23:59:59' and
                             b.is_changed in (5,6)";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                // 판매처 코드
                $shop = $data[a_shop_id];
    
                $val[$shop][change_arr][]    = $data[a_seq];
                $val[$shop][b_change_arr][]  = $data[a_seq];
                $val[$shop][b_change_qty]   += $data[b_qty];
                $val[$shop][b_extra_money]  += $data[b_extra_money];
            }
        }

        //**********************************************
        //  취소(취소일 기준)
        //**********************************************
        if( $cancel_refund_date )
        {   
            // 전체 주문 쿼리
            $query = "select a.seq               a_seq,
                             a.shop_id           a_shop_id,
                             b.qty               b_qty,
                             b.order_cs          b_order_cs,
                             b.refund_price      b_refund_price
                        from orders a,
                             order_products b
                       where a.seq = b.order_seq and
                             a.shop_id in ($shop_id_str) and
                             $status
                             b.order_cs in (1,2,3,4) and
                             b.cancel_date >= '$start_date 00:00:00' and
                             b.cancel_date <= '$end_date 23:59:59'";

            if( $query_type == "trans_date_pos" )
            {
                // minimum 배송일을 구해와야 함.
                $min_trans_date_pos = $this->get_min_cancel_trans_date_pos( $start_date, $end_date,$shop_id_str );
                $query .= " and a." . $query_type . " >= '$min_trans_date_pos 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' "; 
            }
            else
            {
                $query .= " and a." . $query_type . " >= '$start_date 00:00:00' and a." . $query_type . " <= '$end_date 23:59:59' ";   
            }

            debug("cancel_refund_date: $query");
                             
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                // 배송전 취소
                if( $data[b_order_cs] == 1 || $data[b_order_cs] == 2 )
                {
                    $val[$shop][cancel_arr][]    = $data[a_seq];
                    $val[$shop][b_cancel_arr][]  = $data[a_seq];
                    $val[$shop][b_cancel_qty]   += $data[b_qty];
                    $val[$shop][b_refund_price] += $data[b_refund_price];
                }
                // 배송후 취소
                else if( $data[b_order_cs] == 3 || $data[b_order_cs] == 4 )
                {
                    $val[$shop][cancel_arr][]    = $data[a_seq];
                    $val[$shop][a_cancel_arr][]  = $data[a_seq];
                    $val[$shop][a_cancel_qty]   += $data[b_qty];
                    $val[$shop][a_refund_price] += $data[b_refund_price];
                }
            }
        }
        
        // 주문 수량 배열에서 중복 제거후 개수 구하기
        foreach( $val as $key => $shop_val )
        {
            $val[$key][balju_order_qty]  = count( array_unique($shop_val[balju_order_arr]  ) );
            $val[$key][change_order_qty] = count( array_unique($shop_val[change_order_arr] ) );
            $val[$key][new_order_qty]    = count( array_unique($shop_val[new_order_arr]    ) );
            $val[$key][copy_order_qty]   = count( array_unique($shop_val[copy_order_arr]   ) );
            $val[$key][add_order_qty]    = count( array_unique($shop_val[add_order_arr]    ) );
            $val[$key][order_all_qty]    = count( array_unique($shop_val[order_all_arr]    ) );
            $val[$key][b_change_qty]     = count( array_unique($shop_val[b_change_arr]     ) );
            $val[$key][a_change_qty]     = count( array_unique($shop_val[a_change_arr]     ) );
            $val[$key][change_qty]       = count( array_unique($shop_val[change_arr]       ) );
            $val[$key][b_cancel_qty]     = count( array_unique($shop_val[b_cancel_arr]     ) );
            $val[$key][a_cancel_qty]     = count( array_unique($shop_val[a_cancel_arr]     ) );
            $val[$key][cancel_qty]       = count( array_unique($shop_val[cancel_arr]       ) );
            $val[$key][sell_order_qty]   = count( array_unique($shop_val[sell_order_arr]   ) );
            $val[$key][trans_sun_qty]    = count( array_unique($shop_val[trans_sun_arr]    ) );
            $val[$key][trans_hoo_qty]    = count( array_unique($shop_val[trans_hoo_arr]    ) );
        }
        
        // 합계
        foreach( $val as $key => $sval )
        {
            //++++++++++++++++
            // 주문
            $val['total'][balju_order_qty]    += $sval[balju_order_qty]   ;
            $val['total'][balju_product_qty]  += $sval[balju_product_qty] ;
            $val['total'][balju_amount]       += $sval[balju_amount]      ;
            $val['total'][new_order_qty]      += $sval[new_order_qty]     ;
            $val['total'][new_product_qty]    += $sval[new_product_qty]   ;
            $val['total'][new_amount]         += $sval[new_amount]        ;
            $val['total'][copy_order_qty]     += $sval[copy_order_qty]    ;
            $val['total'][copy_product_qty]   += $sval[copy_product_qty]  ;
            $val['total'][copy_amount]        += $sval[copy_amount]       ;
            $val['total'][change_order_qty]   += $sval[change_order_qty]  ;
            $val['total'][change_product_qty] += $sval[change_product_qty];
            $val['total'][change_amount]      += $sval[change_amount]     ;
            $val['total'][add_order_qty]      += $sval[add_order_qty]     ;
            $val['total'][add_product_qty]    += $sval[add_product_qty]   ;
            $val['total'][add_amount]         += $sval[add_amount]        ;
            
            $val[$key][all_order_qty]   = $sval[balju_order_qty]   + $sval[new_order_qty]   + $sval[copy_order_qty]   + $sval[change_order_qty]   + $sval[add_order_qty]  ;
            $val[$key][all_product_qty] = $sval[balju_product_qty] + $sval[new_product_qty] + $sval[copy_product_qty] + $sval[change_product_qty] + $sval[add_product_qty];
            $val[$key][all_amount]      = $sval[balju_amount]      + $sval[new_amount]      + $sval[copy_amount]      + $sval[change_amount]      + $sval[add_amount]     ;

            $val['total'][all_order_qty]   += $val[$key][all_order_qty]  ;
            $val['total'][all_product_qty] += $val[$key][all_product_qty];
            $val['total'][all_amount]      += $val[$key][all_amount]     ;

            //++++++++++++++++
            // 교환
            $val['total'][b_change_qty]       += $sval[b_change_qty]    ;
            $val['total'][b_change_prd_qty]   += $sval[b_change_prd_qty];
            $val['total'][b_extra_money]      += $sval[b_extra_money]   ;
            $val['total'][a_change_qty]       += $sval[a_change_qty]    ;
            $val['total'][a_change_prd_qty]   += $sval[a_change_prd_qty];
            $val['total'][a_extra_money]      += $sval[a_extra_money]   ;

            $val[$key][change_qty]     += $sval[b_change_qty]     + $sval[a_change_qty]    ;
            $val[$key][change_prd_qty] += $sval[b_change_prd_qty] + $sval[a_change_prd_qty];
            $val[$key][extra_money]    += $sval[b_extra_money]    + $sval[a_extra_money]   ;

            debug( "a_extra_money: $sval[b_extra_money]    / a_extra_money: $sval[a_extra_money]  ");

            $val['total'][change_qty]     += $val[$key][change_qty]    ;
            $val['total'][change_prd_qty] += $val[$key][change_prd_qty];
            $val['total'][extra_money]    += $val[$key][extra_money]   ;

            //++++++++++++++++
            // 취소
            $val['total'][b_cancel_qty]     += $sval[b_cancel_qty]    ;
            $val['total'][b_cancel_prd_qty] += $sval[b_cancel_prd_qty];
            $val['total'][b_refund_price]   += $sval[b_refund_price]  ;
            $val['total'][a_cancel_qty]     += $sval[a_cancel_qty]    ;
            $val['total'][a_cancel_prd_qty] += $sval[a_cancel_prd_qty];
            $val['total'][a_refund_price]   += $sval[a_refund_price]  ;

            $val[$key][cancel_qty]     = $sval[b_cancel_qty]     + $sval[a_cancel_qty]    ;
            $val[$key][cancel_prd_qty] = $sval[b_cancel_prd_qty] + $sval[a_cancel_prd_qty];
            $val[$key][refund_price]   = $sval[b_refund_price]   + $sval[a_refund_price]  ;

            $val['total'][cancel_qty]       += $val[$key][cancel_qty]      ;
            $val['total'][cancel_prd_qty]   += $val[$key][cancel_prd_qty]  ;
            $val['total'][refund_price]     += $val[$key][refund_price]    ;

            //++++++++++++++++
            // 순매출
            $val['total'][sell_order_qty]    += $sval[sell_order_qty]   ;
            $val['total'][sell_product_qty]  += $sval[sell_product_qty] ;
            $val['total'][sell_amount]       += $sval[sell_amount]      ;

            // 수수료
            $val['total'][sell_charge] += $sval[sell_charge];
            
            // 사은품
            $val['total'][gift_qty] += $sval[gift_qty];
            
            // 원가
            $val['total'][org_price] += $sval[org_price];
            
            // 택배비
            $val[$key][trans_sun] = $sval[trans_sun_qty] * $before_trans_price;
            $val[$key][trans_hoo] = $sval[trans_hoo_qty] * $after_trans_price;
            $val['total'][trans_sun] += $val[$key][trans_sun];
            $val['total'][trans_hoo] += $val[$key][trans_hoo];
        }
        $val['total']['shop_name'] = "합계";

        // 순수익, 마진
        foreach( $val as $key => $sval )
        {
            // 순수익
            $val[$key][profit] = $sval[sell_amount] - $sval[sell_charge] - $sval[org_price] - $sval[trans_sun] + $sval[trans_hoo];
            
            // 마진
            if( $sval[sell_amount] > 0 )
                $val[$key][margin] = $val[$key][profit] / $sval[sell_amount];
            else
                $val[$key][margin] = 0;
        }

        // 천단위콤마
        foreach( $val as $key => $sval )
        {
            $val[$key][balju_order_qty]     = number_format( $sval[balju_order_qty]     );
            $val[$key][balju_product_qty]   = number_format( $sval[balju_product_qty]   );
            $val[$key][balju_amount]        = number_format( $sval[balju_amount]        );
            $val[$key][new_order_qty]       = number_format( $sval[new_order_qty]       );
            $val[$key][new_product_qty]     = number_format( $sval[new_product_qty]     );
            $val[$key][new_amount]          = number_format( $sval[new_amount]          );
            $val[$key][copy_order_qty]      = number_format( $sval[copy_order_qty]      );
            $val[$key][copy_product_qty]    = number_format( $sval[copy_product_qty]    );
            $val[$key][copy_amount]         = number_format( $sval[copy_amount]         );
            $val[$key][change_order_qty]    = number_format( $sval[change_order_qty]    );
            $val[$key][change_product_qty]  = number_format( $sval[change_product_qty]  );
            $val[$key][change_amount]       = number_format( $sval[change_amount]       );
            $val[$key][add_order_qty]       = number_format( $sval[add_order_qty]       );
            $val[$key][add_product_qty]     = number_format( $sval[add_product_qty]     );
            $val[$key][add_amount]          = number_format( $sval[add_amount]          );
            $val[$key][all_order_qty]       = number_format( $sval[all_order_qty]       );
            $val[$key][all_product_qty]     = number_format( $sval[all_product_qty]     );
            $val[$key][all_amount]          = number_format( $sval[all_amount]          );

            $val[$key][b_change_qty]        = number_format( $sval[b_change_qty]        );
            $val[$key][b_change_prd_qty]    = number_format( $sval[b_change_prd_qty]    );
            $val[$key][b_extra_money]       = number_format( $sval[b_extra_money]       );
            $val[$key][a_change_qty]        = number_format( $sval[a_change_qty]        );
            $val[$key][a_change_prd_qty]    = number_format( $sval[a_change_prd_qty]    );
            $val[$key][a_extra_money]       = number_format( $sval[a_extra_money]       );
            $val[$key][change_qty]          = number_format( $sval[change_qty]          );
            $val[$key][change_prd_qty]      = number_format( $sval[change_prd_qty]      );
            $val[$key][extra_money]         = number_format( $sval[extra_money]         );

            $val[$key][b_cancel_qty]        = number_format( $sval[b_cancel_qty]        );
            $val[$key][b_cancel_prd_qty]    = number_format( $sval[b_cancel_prd_qty]    );
            $val[$key][b_refund_price]      = number_format( $sval[b_refund_price]      );
            $val[$key][a_cancel_qty]        = number_format( $sval[a_cancel_qty]        );
            $val[$key][a_cancel_prd_qty]    = number_format( $sval[a_cancel_prd_qty]    );
            $val[$key][a_refund_price]      = number_format( $sval[a_refund_price]      );
            $val[$key][cancel_qty]          = number_format( $sval[cancel_qty]          );
            $val[$key][cancel_prd_qty]      = number_format( $sval[cancel_prd_qty]      );
            $val[$key][refund_price]        = number_format( $sval[refund_price]        );

            $val[$key][sell_order_qty]      = number_format( $sval[sell_order_qty]      );
            $val[$key][sell_product_qty]    = number_format( $sval[sell_product_qty]    );
            $val[$key][sell_amount]         = number_format( $sval[sell_amount]         );

            $val[$key][sell_charge]         = number_format( $sval[sell_charge]         );
            $val[$key][gift_qty]            = number_format( $sval[gift_qty]            );
            $val[$key][org_price]           = number_format( $sval[org_price]           );
            $val[$key][trans_sun]           = number_format( $sval[trans_sun]           );
            $val[$key][trans_hoo]           = number_format( $sval[trans_hoo]           );
            $val[$key][profit]              = number_format( $sval[profit]              );
            $val[$key][margin_per]          = number_format( $sval[margin] * 100 ) . "%";
        }

        // 판매처가 1개면( 합계 포함 2개 ) 마지막 합계 제거
        if( count( $val ) == 2 )
            array_pop( $val );
            
        return $val;
    }

    //-----------------------------------
    // 취소건 중에 최초일을 가져온다
    //-----------------------------------
    function get_min_cancel_trans_date_pos( $start_date, $end_date, $str_shop_ids = "")
    {
        global $connect;
        
        $query = "select min(a.trans_date_pos) min_trans_date_pos 
                    from orders a, order_products b
                   where a.seq = b.order_seq
                     and a.status = 8
                     and b.cancel_date >= '$start_date 00:00:00'
                     and b.cancel_date <= '$end_date 23:59:59'";

        if ( $str_shop_ids )
            $query .= " and a.shop_id in ( $str_shop_ids )";

        debug( "get min trans_data: $query");
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[min_trans_date_pos] ? $data[min_trans_date_pos] : "$start_date 00:00:00"; 
    }

    // 주문 타입 문자열    
    function get_order_type_str($par)
    {
        if( $par == 0 )
            return "발주";
        else if( $par == 1 )
            return "생성";
        else if( $par == 2 )
            return "복사";
        else if( $par == 3 )
            return "배송후교환";
    }

    // 상태 문자열
    function get_status_str($par)
    {
        if( $par == 0 )
            return "발주";
        else if( $par == 1 )
            return "접수";
        else if( $par == 7 )
            return "송장";
        else if( $par == 8 )
            return "배송";
    }

    // CS 문자열
    function get_cs_str($par)
    {
        if( $par == 0 )
            return "";
        else if( $par == 1 )
            return "배송전 전체취소";
        else if( $par == 2 )
            return "배송전 부분취소";
        else if( $par == 3 )
            return "배송후 전체취소";
        else if( $par == 4 )
            return "배송후 부분취소";
        else if( $par == 5 )
            return "배송전 전체교환";
        else if( $par == 6 )
            return "배송전 부분교환";
        else if( $par == 7 )
            return "배송후 전체교환";
        else if( $par == 8 )
            return "배송후 부분교환";
    }

    // 사용중 판매처 구하기
    function get_shop_list($shop_id)
    {
        global $connect;
        
        if( $shop_id )
            return $shop_id;
        else
        {
            $shop_arr = array();
            $query_shop = "select shop_id from shopinfo where disable=0";
            $result_shop = mysql_query($query_shop, $connect);
            while( $data_shop = mysql_fetch_assoc($result_shop) )
                $shop_arr[] = $data_shop[shop_id];
                
            return implode(",", $shop_arr);
        }
    }

    // 파일 만들기
    function save_file_F310()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $arr_datas = $this->get_stat_info();

        $this->make_file_F310( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F310( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= $this->default_header;
        
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td rowspan=2 class=header_item>판매처</td>\n";
        
        if( $include_detail )
        {
            $buffer .= "<td colspan=3 class=header_item>발주 주문</td>\n";
            $buffer .= "<td colspan=3 class=header_item>생성 주문</td>\n";
            $buffer .= "<td colspan=3 class=header_item>복사 주문</td>\n";
            $buffer .= "<td colspan=3 class=header_item>배송후 교환 주문</td>\n";
            $buffer .= "<td colspan=3 class=header_item>추가 상품</td>\n";
        }
        $buffer .= "<td colspan=3 class=header_item>주문 합계</td>\n";

        if( $include_detail )
        {
            $buffer .= "<td colspan=3 class=header_item>배송전 교환</td>\n";
            $buffer .= "<td colspan=3 class=header_item>배송후 교환</td>\n";
        }
        $buffer .= "<td colspan=3 class=header_item>교환 합계</td>\n";

        if( $include_detail )
        {
            $buffer .= "<td colspan=3 class=header_item>배송전 취소</td>\n";
            $buffer .= "<td colspan=3 class=header_item>배송후 취소</td>\n";
        }
        $buffer .= "<td colspan=3 class=header_item>취소 합계</td>\n";
        
        $buffer .= "<td rowspan=2 class=header_item>순매출</td>\n";
        $buffer .= "<td rowspan=2 class=header_item>수수료</td>\n";
        $buffer .= "<td rowspan=2 class=header_item>사은품</td>\n";
        $buffer .= "<td rowspan=2 class=header_item>원가</td>\n";
        $buffer .= "<td colspan=4 class=header_item>택배비</td>\n";
        $buffer .= "<td rowspan=2 class=header_item>순수익</td>\n";
        $buffer .= "<td rowspan=2 class=header_item>마진</td>\n";
        $buffer .= "</tr>\n";

        $buffer .= "<tr>\n";
        if( $include_detail )
        {
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>금액</td>\n";
        }
        $buffer .= "<td class=header_item>주문</td>\n";
        $buffer .= "<td class=header_item>상품</td>\n";
        $buffer .= "<td class=header_item>금액</td>\n";
        
        if( $include_detail )
        {
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>추가금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>추가금액</td>\n";
        }
        $buffer .= "<td class=header_item>주문</td>\n";
        $buffer .= "<td class=header_item>상품</td>\n";
        $buffer .= "<td class=header_item>추가금액</td>\n";
        
        if( $include_detail )
        {
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>취소금액</td>\n";
            $buffer .= "<td class=header_item>주문</td>\n";
            $buffer .= "<td class=header_item>상품</td>\n";
            $buffer .= "<td class=header_item>취소금액</td>\n";
        }
        $buffer .= "<td class=header_item>주문</td>\n";
        $buffer .= "<td class=header_item>상품</td>\n";
        $buffer .= "<td class=header_item>취소금액</td>\n";
        
        $buffer .= "<td class=header_item>선결제</td>\n";
        $buffer .= "<td class=header_item>신용</td>\n";
        $buffer .= "<td class=header_item>착불</td>\n";
        $buffer .= "<td class=header_item>회수</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach ( $arr_datas as $row) 
        {
            $buffer = "<tr>\n";

            $buffer .= "<td class=str_item_center>" . $row[shop_name] . "</td>\n";

            if( $include_detail )
            {
                $buffer .= "<td class=num_item>" . $row[balju_order_qty]     . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[balju_product_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[balju_amount]        . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[new_order_qty]       . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[new_product_qty]     . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[new_amount]          . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[copy_order_qty]      . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[copy_product_qty]    . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[copy_amount]         . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[change_order_qty]    . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[change_product_qty]  . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[change_amount]       . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[add_order_qty]       . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[add_product_qty]     . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[add_amount]          . "</td>\n";
            }
            $buffer .= "<td class=num_item>" . $row[order_qty]           . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[product_qty]         . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[amount]              . "</td>\n";
            
            if( $include_detail )
            {
                $buffer .= "<td class=num_item>" . $row[before_change_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[before_extra_money]  . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[after_change_qty]    . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[after_extra_money]   . "</td>\n";
            }
            $buffer .= "<td class=num_item>" . $row[change_qty]          . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[extra_money]         . "</td>\n";
            
            if( $include_detail )
            {
                $buffer .= "<td class=num_item>" . $row[b_shop_cancel_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[b_shop_refund_price] . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[b_cust_cancel_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[b_cust_refund_price] . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[a_shop_cancel_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[a_shop_refund_price] . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[a_cust_cancel_qty]   . "</td>\n";
                $buffer .= "<td class=num_item>" . $row[a_cust_refund_price] . "</td>\n";
            }
            $buffer .= "<td class=num_item>" . $row[cancel_qty]          . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[refund_price]        . "</td>\n";
            
            $buffer .= "<td class=num_item>" . $row[gift_qty]            . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[sell_charge]         . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[org_price]           . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[trans_sun]           . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[trans_hoo]           . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[sell_price]          . "</td>\n";
            $buffer .= "<td class=num_item>" . $row[profit]              . "</td>\n";
            $buffer .= "<td class=per_item>" . $row[margin]              . "</td>\n";

            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($handle);

        return $filename; 
    }

    // 파일 다운받기
    function download_F310()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산.xls"));
    }    

    
    /////////////////////////////////////
    // 판매처 주문 상세
    /////////////////////////////////////
    function F311()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        $page_type = 0;

        // 작업중
        $this->show_wait();

        // 페이지
        if( !$page )  $page = 1;
        $line_per_page = 50;

        // link url
        $par = array('template','shop_id','query_type','start_date','end_date');
        $link_url = $this->build_link_url3( $par );
        
        // 날짜
        if( $query_type == "collect_date" )
            $date_type = "발주일";
        else if( $query_type == "trans_date" )
            $date_type = "송장등록일";
        else if( $query_type == "trans_date_pos" )
            $date_type = "배송일";

        //******************************************
        // 데이터 전체 개수, 총수량, 총판매금액
        //******************************************
        $query = "select a.seq               a_seq,
                         a.create_org        a_create_org,
                         a.qty               a_qty,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        // 교환주문
        $change_arr = array();
        $obj_rule = new class_statrule();
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        $query .= " group by a.seq";

        // 전체 개수
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        // 총수량, 총판매금액
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[a_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
        
        $result = $this->get_F311();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_F311($is_download=0)
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        //*******************
        // 전체 데이터
        //*******************
        $query = "select a.seq               a_seq,
                         a.order_id          a_order_id,
                         a.create_type       a_create_type,
                         a.status            a_status,
                         a.order_cs          a_order_cs,
                         a.collect_date      a_collect_date,
                         a.trans_date        a_trans_date,
                         a.trans_date_pos    a_trans_date_pos,
                         a.recv_name         a_recv_name,
                         a.shop_product_id   a_shop_product_id, 
                         a.qty               a_qty,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount,
                         c.shop_name         c_shop_name
                    from orders a,
                         order_products b,
                         shopinfo c
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.shop_id = c.shop_id and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        $obj_rule   = new class_statrule();
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        // 정렬
        $query .= " group by a.seq order by c.sort_name, a.seq desc";
        if( !$is_download )
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";

        $result = mysql_query($query, $connect);

        return $result;
    }   

    function save_file_F311()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $result = $this->get_F311(1);

        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );

            $arr_datas[] = array(
                'shop_name'    => $data[c_shop_name]      ,
                'seq'          => $data[a_seq]            ,
                'collect_date' => $data[a_collect_date]   ,
                'order_id'     => $data[a_order_id]       ,
                'order_type'   => $order_type             ,
                'status'       => $status                 ,
                'cs'           => $cs                     ,
                'shop_pid'     => $data[a_shop_product_id],
                'recv_name'    => $data[a_recv_name]      ,
                'qty_val'      => $data[a_qty]            ,
                'amount_val'   => $data[sum_b_prd_amount] ,
                'qty'          => number_format($data[a_qty]),
                'amount'       => number_format($data[sum_b_prd_amount])
            );
        }

        $this->make_file_F311( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F311( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item>판매처</td>\n";
        $buffer .= "<td class=header_item>관리번호</td>\n";
        $buffer .= "<td class=header_item>발주일</td>\n";
        $buffer .= "<td class=header_item>주문번호</td>\n";
        $buffer .= "<td class=header_item>주문타입</td>\n";
        $buffer .= "<td class=header_item>상태</td>\n";
        $buffer .= "<td class=header_item>CS</td>\n";
        $buffer .= "<td class=header_item>판매처 상품코드</td>\n";
        $buffer .= "<td class=header_item>수령자</td>\n";
        $buffer .= "<td class=header_item>주문수량</td>\n";
        $buffer .= "<td class=header_item>판매금액</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_qty = 0;
        $total_amount = 0;
        foreach ( $arr_datas as $row ) 
        {
            $buffer = "<tr>\n";
            $buffer .= "<td class=str_item_center>" . $row[shop_name]    . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[seq]          . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[collect_date] . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[order_id]     . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[order_type]   . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[status]       . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[cs]           . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[shop_pid]     . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[recv_name]    . "</td>\n";
            $buffer .= "<td class=num_item>"        . $row[qty]          . "</td>\n";
            $buffer .= "<td class=num_item>"        . $row[amount]       . "</td>\n";
            $buffer .= "</tr>\n";
        
            $total_qty += $row[qty_val];
            $total_amount += $row[amount_val];
            
            fwrite($handle, $buffer);
        }

        // 합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=9>합계</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_qty   ) . "</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_amount) . "</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_F311()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산 - 판매처 주문상세.xls"));
    }    

    /////////////////////////////////////
    // 어드민 상품 상세
    /////////////////////////////////////
    function F312()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        $page_type = 0;

        // 작업중
        $this->show_wait();

        // 페이지
        if( !$page )  $page = 1;
        $line_per_page = 50;

        // link url
        $par = array('template','shop_id','query_type','start_date','end_date');
        $link_url = $this->build_link_url3( $par );
        
        // 날짜
        if( $query_type == "collect_date" )
            $date_type = "발주일";
        else if( $query_type == "trans_date" )
            $date_type = "송장등록일";
        else if( $query_type == "trans_date_pos" )
            $date_type = "배송일";

        //******************************************
        // 데이터 전체 개수, 총수량, 총판매금액
        //******************************************
        $query = "select a.seq               a_seq,
                         a.create_org        a_create_org,
                         a.qty               a_qty,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        // 교환주문
        $change_arr = array();
        $obj_rule = new class_statrule();
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        $query .= " group by a.seq";

        // 전체 개수
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        // 총수량, 총판매금액
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
        
        $result = $this->get_F312();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function get_F312($is_download=0)
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        //*******************
        // 전체 데이터
        //*******************
        $query = "select a.seq               a_seq,
                         d.name              d_name,
                         c.product_id        c_product_id,
                         c.name              c_name,
                         c.options           c_options,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b,
                         products c,
                         userinfo d
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         b.product_id = c.product_id and
                         c.supply_code = d.code and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        $obj_rule   = new class_statrule();
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        // 정렬
        $query .= " group by c.product_id order by d.name, c.name, c.options ";
        if( !$is_download )
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";

        $result = mysql_query($query, $connect);

        return $result;
    }   

    function save_file_F312()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $result = $this->get_F312(1);

        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_datas[] = array(
                'd_name'       => $data[d_name]          ,
                'c_product_id' => $data[c_product_id]    ,
                'c_name'       => $data[c_name]          ,
                'c_options'    => $data[c_options]       ,
                'qty_val'      => $data[sum_b_qty]       ,
                'amount_val'   => $data[sum_b_prd_amount],
                'qty'          => number_format($data[sum_b_qty]       ),
                'amount'       => number_format($data[sum_b_prd_amount])
            );
        }

        $this->make_file_F312( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F312( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item>공급처</td>\n";
        $buffer .= "<td class=header_item>상품코드</td>\n";
        $buffer .= "<td class=header_item>상품명</td>\n";
        $buffer .= "<td class=header_item>옵션</td>\n";
        $buffer .= "<td class=header_item>상품수량</td>\n";
        $buffer .= "<td class=header_item>판매금액</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_qty = 0;
        $total_amount = 0;
        foreach ( $arr_datas as $row ) 
        {
            $buffer = "<tr>\n";
            $buffer .= "<td class=str_item_center>" . $row[d_name]       . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[c_product_id] . "</td>\n";
            $buffer .= "<td class=str_item>"        . $row[c_name]       . "</td>\n";
            $buffer .= "<td class=str_item>"        . $row[c_options]    . "</td>\n";
            $buffer .= "<td class=num_item>"        . $row[qty]          . "</td>\n";
            $buffer .= "<td class=num_item>"        . $row[amount]       . "</td>\n";
            $buffer .= "</tr>\n";
        
            $total_qty += $row[qty_val];
            $total_amount += $row[amount_val];
            
            fwrite($handle, $buffer);
        }

        // 합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=4>합계</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_qty   ) . "</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_amount) . "</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_F312()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산 - 어드민 상품상세.xls"));
   }    


    /////////////////////////////////////
    // 판매처 상품 상세 (I)
    /////////////////////////////////////
    function F313()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date, $page_type;

        // 작업중
        $this->show_wait();
        
        $page_type = 0;

        // 페이지
        if( !$page )  $page = 1;
        $line_per_page = 50;

        // link url
        $par = array('template','shop_id','query_type','start_date','end_date');
        $link_url = $this->build_link_url3( $par );
        
        // 날짜
        if( $query_type == "collect_date" )
            $date_type = "발주일";
        else if( $query_type == "trans_date" )
            $date_type = "송장등록일";
        else if( $query_type == "trans_date_pos" )
            $date_type = "배송일";

        $query = $this->get_F313();
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result );
        
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_qty];
            $total_amount += $data[amount];
        }

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_F313()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        //=============
        // sub 쿼리
        //=============
        $query = "select a.shop_id           a_shop_id,
                         a.shop_product_id   a_shop_product_id, 
                         a.product_name      a_product_name,
                         a.qty               a_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        $obj_rule   = new class_statrule();
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
            
        // 정렬
        $query .= " group by a.seq ";
        
        //=============
        // main 쿼리
        //=============
        $query_main =   "select temp.a_shop_product_id       shop_product_id,
                                temp.a_product_name          product_name, 
                                sum( temp.a_qty )            sum_qty,
                                sum( temp.sum_b_prd_amount ) amount,
                                shopinfo.shop_name           shop_name
                           from ( $query ) as temp,
                                shopinfo 
                          where temp.a_shop_id = shopinfo.shop_id
                          group by shop_product_id
                          order by shopinfo.sort_name, temp.a_product_name";
        
        return $query_main;
    }   

    function save_file_F313()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $result = $this->get_F312(1);

        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );

            $arr_datas[] = array(
                'shop_name'    => $data[c_shop_name]      ,
                'seq'          => $data[a_seq]            ,
                'collect_date' => $data[a_collect_date]   ,
                'order_id'     => $data[a_order_id]       ,
                'order_type'   => $order_type             ,
                'status'       => $status                 ,
                'cs'           => $cs                     ,
                'shop_pid'     => $data[a_shop_product_id],
                'recv_name'    => $data[a_recv_name]      ,
                'qty_val'      => $data[sum_b_qty]        ,
                'amount_val'   => $data[sum_b_prd_amount] ,
                'qty'          => number_format($data[sum_b_qty]),
                'amount'       => number_format($data[sum_b_prd_amount])
            );
        }

        $this->make_file_F313( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F313( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<th width= 6%>판매처</th>\n";
        $buffer .= "<th width= 7%>판매처 상품코드</th>\n";
        $buffer .= "<th width=15%>판매처 상품명</th>\n";
        $buffer .= "<th width=12%>판매처 옵션</th>\n";
        $buffer .= "<th width= 4%>어드민 상품코드</th>\n";
        $buffer .= "<th width=10%>어드민 상품명</th>\n";
        $buffer .= "<th width= 8%>어드민 옵션</th>\n";
        $buffer .= "<th width= 3%>주문수량</th>\n";
        $buffer .= "<th width= 6%>판매금액</th>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_qty = 0;
        $total_amount = 0;

        $old_a_shop_product_id = "";
        $old_a_product_name    = "";
        $old_a_options         = "";
        $arr = array();
        $rowspan_pos1 = 0;
        $rowspan_pos2 = 0;
        $rowspan_pos3 = 0;
        $i = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );
            
            $arr[] = array(
                'shop_name'     => $data[c_shop_name]                    ,
                'shop_pid'      => $data[a_shop_product_id]              ,
                'shop_prd_name' => $data[a_product_name]                 ,
                'shop_options'  => $data[a_options]                      ,
                'ez_product_id' => $data[b_product_id]                   ,
                'ez_prd_name'   => $data[d_name]                         ,
                'ez_options'    => $data[d_options]                      ,
                'qty'           => number_format($data[sum_b_qty]       ),
                'amount'        => number_format($data[sum_b_prd_amount]),
                'rowspan1'      => 0,
                'rowspan2'      => 0,
                'rowspan3'      => 0,
            );
    
            if( $old_a_shop_product_id != $data[a_shop_product_id] )
            {
                $rowspan_pos1 = $i;
                $rowspan_pos2 = $i;
                $rowspan_pos3 = $i;
            }
            else
            {
                if( $old_a_product_name != $data[a_product_name] )
                {
                    $rowspan_pos2 = $i;
                    $rowspan_pos3 = $i;
                }
                else
                {
                    if( $old_a_options != $data[a_options] )
                    {
                        $rowspan_pos3 = $i;
                    }
                }
            }
            $arr[$rowspan_pos1]['rowspan1']++;
            $arr[$rowspan_pos2]['rowspan2']++;
            $arr[$rowspan_pos3]['rowspan3']++;
            
            $old_a_shop_product_id = $data[a_shop_product_id];
            $old_a_product_name    = $data[a_product_name]   ;
            $old_a_options         = $data[a_options]        ;
    
            $i++;
            
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
    
        foreach( $arr as $val )
        {
            if( $val['rowspan1'] > 1 )
                $rowspan1 = "rowspan = $val[rowspan1]";
            if( $val['rowspan1'] == 1 )
                $rowspan1 = "";
            
            if( $val['rowspan2'] > 1 )
                $rowspan2 = "rowspan = $val[rowspan2]";
            if( $val['rowspan2'] == 1 )
                $rowspan2 = "";
            
            if( $val['rowspan3'] > 1 )
                $rowspan3 = "rowspan = $val[rowspan3]";
            if( $val['rowspan3'] == 1 )
                $rowspan3 = "";
            
            $buffer = "<tr>\n";
            $buffer .= "<td                        >" . $val['shop_name']     . "&nbsp;</td>";
            if( $val['rowspan1'] > 0 )
                $buffer .= "<td              $rowspan1>" . $val['shop_pid']      . "&nbsp;</td>";
            if( $val['rowspan2'] > 0 )
                $buffer .= "<td class='left' $rowspan2>" . $val['shop_prd_name'] . "&nbsp;</td>";
            if( $val['rowspan3'] > 0 )
                $buffer .= "<td class='left' $rowspan3>" . $val['shop_options']  . "&nbsp;</td>";
            $buffer .= "<td                        >" . $val['ez_product_id'] . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_prd_name']   . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_options']    . "&nbsp;</td>";
            $buffer .= "<td class='right'          >" . $val['qty']           . "&nbsp;</td>";
            $buffer .= "<td class='right borderEnd'>" . $val['amount']        . "&nbsp;</td>";
            $buffer .= "</tr>";
            fwrite($handle, $buffer);
        }

        // 합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=7>합계</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_qty   ) . "</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_amount) . "</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_F313()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산 - 판매처 상품상세(I).xls"));
    }    
    
    /////////////////////////////////////
    // 판매처 상품 상세 (II)
    /////////////////////////////////////
    function F314()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date, $page_type;

        // 작업중
        $this->show_wait();
        
        $page_type = 1;

        // 페이지
        if( !$page )  $page = 1;
        $line_per_page = 50;

        // link url
        $par = array('template','shop_id','query_type','start_date','end_date');
        $link_url = $this->build_link_url3( $par );
        
        // 날짜
        if( $query_type == "collect_date" )
            $date_type = "발주일";
        else if( $query_type == "trans_date" )
            $date_type = "송장등록일";
        else if( $query_type == "trans_date_pos" )
            $date_type = "배송일";

        //******************************************
        // 데이터 전체 개수, 총수량, 총판매금액
        //******************************************
        $query = "select a.seq               a_seq,
                         a.create_org        a_create_org,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        // 교환주문
        $change_arr = array();
        $obj_rule = new class_statrule();
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        $query .= " group by a.shop_product_id, b.product_id";

        // 전체 개수
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        // 총수량, 총판매금액
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
        
        $result = $this->get_F314();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_F314($is_download=0)
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        //*******************
        // 전체 데이터
        //*******************
        $query = "select a.seq               a_seq,
                         a.shop_product_id   a_shop_product_id, 
                         a.product_name      a_product_name,
                         a.options           a_options,
                         b.product_id        b_product_id,
                         b.shop_options      b_shop_options,
                         d.name              d_name,
                         d.options           d_options,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount,
                         c.shop_name         c_shop_name
                    from orders a,
                         order_products b,
                         shopinfo c,
                         products d
                   where a.seq = b.order_seq and
                         a.shop_id = c.shop_id and
                         b.product_id = d.product_id and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        $obj_rule   = new class_statrule();
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        // 정렬
        $query .= " group by a.shop_product_id, b.shop_options, b.product_id order by c.sort_name, a.shop_product_id, b.shop_options, d.name, d.options";
        if( !$is_download )
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";

        $result = mysql_query($query, $connect);

        return $result;
    }   

    function save_file_F314()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $result = $this->get_F312(1);

        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );

            $arr_datas[] = array(
                'shop_name'    => $data[c_shop_name]      ,
                'seq'          => $data[a_seq]            ,
                'collect_date' => $data[a_collect_date]   ,
                'order_id'     => $data[a_order_id]       ,
                'order_type'   => $order_type             ,
                'status'       => $status                 ,
                'cs'           => $cs                     ,
                'shop_pid'     => $data[a_shop_product_id],
                'recv_name'    => $data[a_recv_name]      ,
                'qty_val'      => $data[sum_b_qty]        ,
                'amount_val'   => $data[sum_b_prd_amount] ,
                'qty'          => number_format($data[sum_b_qty]),
                'amount'       => number_format($data[sum_b_prd_amount])
            );
        }

        $this->make_file_F314( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F314( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<th width= 6%>판매처</th>\n";
        $buffer .= "<th width= 7%>판매처 상품코드</th>\n";
        $buffer .= "<th width=15%>판매처 상품명</th>\n";
        $buffer .= "<th width=12%>판매처 옵션</th>\n";
        $buffer .= "<th width= 4%>어드민 상품코드</th>\n";
        $buffer .= "<th width=10%>어드민 상품명</th>\n";
        $buffer .= "<th width= 8%>어드민 옵션</th>\n";
        $buffer .= "<th width= 3%>수량</th>\n";
        $buffer .= "<th width= 6%>판매금액</th>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_qty = 0;
        $total_amount = 0;

        $old_a_shop_product_id = "";
        $old_a_product_name    = "";
        $old_a_options         = "";
        $arr = array();
        $rowspan_pos1 = 0;
        $rowspan_pos2 = 0;
        $rowspan_pos3 = 0;
        $i = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );
            
            $arr[] = array(
                'shop_name'     => $data[c_shop_name]                    ,
                'shop_pid'      => $data[a_shop_product_id]              ,
                'shop_prd_name' => $data[a_product_name]                 ,
                'shop_options'  => $data[a_options]                      ,
                'ez_product_id' => $data[b_product_id]                   ,
                'ez_prd_name'   => $data[d_name]                         ,
                'ez_options'    => $data[d_options]                      ,
                'qty'           => number_format($data[sum_b_qty]       ),
                'amount'        => number_format($data[sum_b_prd_amount]),
                'rowspan1'      => 0,
                'rowspan2'      => 0,
                'rowspan3'      => 0,
            );
    
            if( $old_a_shop_product_id != $data[a_shop_product_id] )
            {
                $rowspan_pos1 = $i;
                $rowspan_pos2 = $i;
                $rowspan_pos3 = $i;
            }
            else
            {
                if( $old_a_product_name != $data[a_product_name] )
                {
                    $rowspan_pos2 = $i;
                    $rowspan_pos3 = $i;
                }
                else
                {
                    if( $old_a_options != $data[a_options] )
                    {
                        $rowspan_pos3 = $i;
                    }
                }
            }
            $arr[$rowspan_pos1]['rowspan1']++;
            $arr[$rowspan_pos2]['rowspan2']++;
            $arr[$rowspan_pos3]['rowspan3']++;
            
            $old_a_shop_product_id = $data[a_shop_product_id];
            $old_a_product_name    = $data[a_product_name]   ;
            $old_a_options         = $data[a_options]        ;
    
            $i++;
            
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
    
        foreach( $arr as $val )
        {
            if( $val['rowspan1'] > 1 )
                $rowspan1 = "rowspan = $val[rowspan1]";
            if( $val['rowspan1'] == 1 )
                $rowspan1 = "";
            
            if( $val['rowspan2'] > 1 )
                $rowspan2 = "rowspan = $val[rowspan2]";
            if( $val['rowspan2'] == 1 )
                $rowspan2 = "";
            
            if( $val['rowspan3'] > 1 )
                $rowspan3 = "rowspan = $val[rowspan3]";
            if( $val['rowspan3'] == 1 )
                $rowspan3 = "";
            
            $buffer = "<tr>\n";
            $buffer .= "<td                        >" . $val['shop_name']     . "&nbsp;</td>";
            if( $val['rowspan1'] > 0 )
                $buffer .= "<td              $rowspan1>" . $val['shop_pid']      . "&nbsp;</td>";
            if( $val['rowspan2'] > 0 )
                $buffer .= "<td class='left' $rowspan2>" . $val['shop_prd_name'] . "&nbsp;</td>";
            if( $val['rowspan3'] > 0 )
                $buffer .= "<td class='left' $rowspan3>" . $val['shop_options']  . "&nbsp;</td>";
            $buffer .= "<td                        >" . $val['ez_product_id'] . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_prd_name']   . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_options']    . "&nbsp;</td>";
            $buffer .= "<td class='right'          >" . $val['qty']           . "&nbsp;</td>";
            $buffer .= "<td class='right borderEnd'>" . $val['amount']        . "&nbsp;</td>";
            $buffer .= "</tr>";
            fwrite($handle, $buffer);
        }

        // 합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=7>합계</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_qty   ) . "</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_amount) . "</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_F314()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산 - 판매처 상품상세(II).xls"));
    }    

    /////////////////////////////////////
    // 판매처 상품 상세 (III)
    /////////////////////////////////////
    function F315()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date, $page_type;

        // 작업중
        $this->show_wait();
        
        $page_type = 2;

        // 페이지
        if( !$page )  $page = 1;
        $line_per_page = 50;

        // link url
        $par = array('template','shop_id','query_type','start_date','end_date');
        $link_url = $this->build_link_url3( $par );
        
        // 날짜
        if( $query_type == "collect_date" )
            $date_type = "발주일";
        else if( $query_type == "trans_date" )
            $date_type = "송장등록일";
        else if( $query_type == "trans_date_pos" )
            $date_type = "배송일";

        //******************************************
        // 데이터 전체 개수, 총수량, 총판매금액
        //******************************************
        $query = "select a.seq               a_seq,
                         a.create_org        a_create_org,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        // 교환주문
        $change_arr = array();
        $obj_rule = new class_statrule();
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        $query .= " group by a.shop_product_id, b.product_id";

        // 전체 개수
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        // 총수량, 총판매금액
        $total_qty = 0;
        $total_amount = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
        
        $result = $this->get_F315();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_F315($is_download=0)
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $shop_id, $query_type, $start_date, $end_date;

        //*******************
        // 전체 데이터
        //*******************
        $query = "select a.seq               a_seq,
                         a.shop_product_id   a_shop_product_id, 
                         a.product_name      a_product_name,
                         a.options           a_options,
                         b.product_id        b_product_id,
                         b.shop_options      b_shop_options,
                         d.name              d_name,
                         d.options           d_options,
                         sum(b.qty)          sum_b_qty,
                         sum(b.prd_amount)   sum_b_prd_amount,
                         c.shop_name         c_shop_name
                    from orders a,
                         order_products b,
                         shopinfo c,
                         products d
                   where a.seq = b.order_seq and
                         a.shop_id = c.shop_id and
                         b.product_id = d.product_id and
                         a.shop_id in (" . $this->get_shop_list($shop_id) . ") and
                         a.status >= 1 ";
    
        if( $query_type == 'collect_date' )
            $query .= " and a.$query_type >= '$start_date' and a.$query_type <= '$end_date' ";
        else
            $query .= " and a.$query_type >= '$start_date 00:00:00' and a.$query_type <= '$end_date 23:59:59' ";
    
        $obj_rule   = new class_statrule();
        // 교환주문
        if ( $obj_rule->m_arr_config['change'] == "org")
            $query .= " and a.create_type<>3 ";
        else
            $query .= " and b.is_changed not in (7,8) ";
    
        // 정렬
        $query .= " group by a.shop_product_id, b.shop_options, b.product_id order by c.sort_name, a.shop_product_id, b.shop_options, d.name, d.options";
        if( !$is_download )
            $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";

        $result = mysql_query($query, $connect);

        return $result;
    }   

    function save_file_F315()
    {
        global $template, $connect, $shop_id, $query_type, $start_date, $end_date, $include_detail;

        $result = $this->get_F312(1);

        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );

            $arr_datas[] = array(
                'shop_name'    => $data[c_shop_name]      ,
                'seq'          => $data[a_seq]            ,
                'collect_date' => $data[a_collect_date]   ,
                'order_id'     => $data[a_order_id]       ,
                'order_type'   => $order_type             ,
                'status'       => $status                 ,
                'cs'           => $cs                     ,
                'shop_pid'     => $data[a_shop_product_id],
                'recv_name'    => $data[a_recv_name]      ,
                'qty_val'      => $data[sum_b_qty]        ,
                'amount_val'   => $data[sum_b_prd_amount] ,
                'qty'          => number_format($data[sum_b_qty]),
                'amount'       => number_format($data[sum_b_prd_amount])
            );
        }

        $this->make_file_F315( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_F315( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<th width= 6%>판매처</th>\n";
        $buffer .= "<th width= 7%>판매처 상품코드</th>\n";
        $buffer .= "<th width=15%>판매처 상품명</th>\n";
        $buffer .= "<th width=12%>판매처 옵션</th>\n";
        $buffer .= "<th width= 4%>어드민 상품코드</th>\n";
        $buffer .= "<th width=10%>어드민 상품명</th>\n";
        $buffer .= "<th width= 8%>어드민 옵션</th>\n";
        $buffer .= "<th width= 3%>수량</th>\n";
        $buffer .= "<th width= 6%>판매금액</th>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $total_qty = 0;
        $total_amount = 0;

        $old_a_shop_product_id = "";
        $old_a_product_name    = "";
        $old_a_options         = "";
        $arr = array();
        $rowspan_pos1 = 0;
        $rowspan_pos2 = 0;
        $rowspan_pos3 = 0;
        $i = 0;
        while( $data = mysql_fetch_assoc($result) )
        {
            // 주문 타입
            $order_type = $this->get_order_type_str( $data[a_create_type] );
            
            // 상태
            $status = $this->get_status_str( $data[a_status] );
    
            // CS
            $cs = $this->get_cs_str( $data[a_order_cs] );
            
            $arr[] = array(
                'shop_name'     => $data[c_shop_name]                    ,
                'shop_pid'      => $data[a_shop_product_id]              ,
                'shop_prd_name' => $data[a_product_name]                 ,
                'shop_options'  => $data[a_options]                      ,
                'ez_product_id' => $data[b_product_id]                   ,
                'ez_prd_name'   => $data[d_name]                         ,
                'ez_options'    => $data[d_options]                      ,
                'qty'           => number_format($data[sum_b_qty]       ),
                'amount'        => number_format($data[sum_b_prd_amount]),
                'rowspan1'      => 0,
                'rowspan2'      => 0,
                'rowspan3'      => 0,
            );
    
            if( $old_a_shop_product_id != $data[a_shop_product_id] )
            {
                $rowspan_pos1 = $i;
                $rowspan_pos2 = $i;
                $rowspan_pos3 = $i;
            }
            else
            {
                if( $old_a_product_name != $data[a_product_name] )
                {
                    $rowspan_pos2 = $i;
                    $rowspan_pos3 = $i;
                }
                else
                {
                    if( $old_a_options != $data[a_options] )
                    {
                        $rowspan_pos3 = $i;
                    }
                }
            }
            $arr[$rowspan_pos1]['rowspan1']++;
            $arr[$rowspan_pos2]['rowspan2']++;
            $arr[$rowspan_pos3]['rowspan3']++;
            
            $old_a_shop_product_id = $data[a_shop_product_id];
            $old_a_product_name    = $data[a_product_name]   ;
            $old_a_options         = $data[a_options]        ;
    
            $i++;
            
            $total_qty += $data[sum_b_qty];
            $total_amount += $data[sum_b_prd_amount];
        }
    
        foreach( $arr as $val )
        {
            if( $val['rowspan1'] > 1 )
                $rowspan1 = "rowspan = $val[rowspan1]";
            if( $val['rowspan1'] == 1 )
                $rowspan1 = "";
            
            if( $val['rowspan2'] > 1 )
                $rowspan2 = "rowspan = $val[rowspan2]";
            if( $val['rowspan2'] == 1 )
                $rowspan2 = "";
            
            if( $val['rowspan3'] > 1 )
                $rowspan3 = "rowspan = $val[rowspan3]";
            if( $val['rowspan3'] == 1 )
                $rowspan3 = "";
            
            $buffer = "<tr>\n";
            $buffer .= "<td                        >" . $val['shop_name']     . "&nbsp;</td>";
            if( $val['rowspan1'] > 0 )
                $buffer .= "<td              $rowspan1>" . $val['shop_pid']      . "&nbsp;</td>";
            if( $val['rowspan2'] > 0 )
                $buffer .= "<td class='left' $rowspan2>" . $val['shop_prd_name'] . "&nbsp;</td>";
            if( $val['rowspan3'] > 0 )
                $buffer .= "<td class='left' $rowspan3>" . $val['shop_options']  . "&nbsp;</td>";
            $buffer .= "<td                        >" . $val['ez_product_id'] . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_prd_name']   . "&nbsp;</td>";
            $buffer .= "<td class='left'           >" . $val['ez_options']    . "&nbsp;</td>";
            $buffer .= "<td class='right'          >" . $val['qty']           . "&nbsp;</td>";
            $buffer .= "<td class='right borderEnd'>" . $val['amount']        . "&nbsp;</td>";
            $buffer .= "</tr>";
            fwrite($handle, $buffer);
        }

        // 합계
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item colspan=7>합계</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_qty   ) . "</td>\n";
        $buffer .= "<td class=num_item>" . number_format($total_amount) . "</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_F315()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"판매처정산 - 판매처 상품상세(III).xls"));
    }    

    //
    // 당일판매분 판매처별 요약표 파일 생성
    function save_file_F307_all()
    {
        $obj = new class_statrule();
        // product_list
        $is_download = 1;
        
        $arr_data = $this->shop_product_list("all");
               	
        $fn = $this->make_file2( $arr_data['list'] ,"all");
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    //
    // 당일판매분 요약표 파일 생성
    function save_file_F307()
    {
        $obj = new class_statrule();
        // product_list
        $is_download = 1;
        
        $arr_data = $this->shop_product_list();

        $fn = $this->make_file2( $arr_data['list'] );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file2( $_arr_product , $type ="")
    {
        
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_summary_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
        
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        
        fwrite($handle, $buffer);
 
        //******************************
        // step 1. begin of arr_product
        $_arr = array();

        if($type=="all")
            $_arr[] = "판매처";

        $_arr[] = "공급처";
        $_arr[] = "상품명";
        $_arr[] = "상품수량";
        $_arr[] = "원가";
        $_arr[] = "원가합계";
        
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'> " . $value . " </td>";
            
        $buffer .= "</tr>\n";        
        fwrite($handle, $buffer);

        $style1 = "font:12px \"굴림\"; white-space:nowrap; mso-number-format:" . '"0_ \;\[Red\]\\-0\\ "' . ";";
        $style2 = 'font:9pt "굴림"; white-space:nowrap;';

        // for row
        foreach( $_arr_product as $row )
        {
            $buffer = "<tr>\n";
            if($type=="all")
        		$buffer .= "<td style='$style2'>" . class_shop::get_shop_name($row['shop_id']) . "</td>";

            $buffer .= "<td style='$style2'>" . $row['supply_name']      . "</td>
                        <td style='$style2'>" . $row['product_name']      . "</td>
                        <td style='$style1'>" . (int)$row['qty']      . "</td>
                        <td style='$style1'>" . (int)$row['org_price']       . "</td>
                        <td style='$style1'>" . (int)$row['qty'] * $row['org_price'] . "</td>";
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download3()
    {
        global $filename, $new_name;
        
        if( !$new_name )  $new_name = str_replace("+", "%20", urlencode("shop_sell_list.xls"));
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }  
    
}

?>
