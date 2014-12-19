<?
/**************************************
* date: 2009.6.25 - jk
*  
**************************************/
require_once "class_top.php";
require_once "class_statconfig.php";
require_once "class_order_products.php";
require_once "class_product.php";
require_once "class_shop.php";

class class_statrule extends class_top {
    ///////////////////////////////////////////
	var $m_arr_rule   = array();
	var $m_user_trans_price = 0;
	var $m_arr_config = array();
    function class_statrule()
    {
		global $connect;

		// class생성과 동시에 rule정보를 읽어온다
		$query  = "select * from stat_rule";

		$result = mysql_query( $query, $connect );
		while ( $data = mysql_fetch_assoc( $result ) )
		{
			$this->m_arr_rule[ $data[shop_code] ] = array(
				amount             => $data[amount]			        // 판매가 rule
				,supply_price      => $data[supply_price]	        // 공급가 rule
				,prepay_trans      => $data[prepay_trans]	        // 선결제 rule
				,user_trans_price  => $data[user_trans_price]       // 선불 택배 금액
			);
			//$this->m_user_trans_price = $data[user_trans_price];
		}

		$obj                      = new class_statconfig();
		$this->m_arr_config       = $obj->get_config();		
		$this->m_user_trans_price = $this->m_arr_config['usertrans_price']; // 선불 택배비는 환경설정
	}


	var $m_arr_price = array();
	
	/*************************************	
	* 송장 정보.. 2009.11.16 - jk
	*************************************/
	function get_trans_info()
	{
	    global $start_date, $end_date, $connect, $ex_cancel, $ex_gift, $ex_hold;
	    
        $arr_result = array();

        //*******************
        // 일반/합포
        //*******************
	    $query = "select pack
                    from orders 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and status>1 ";
    	    
    	if( $ex_cancel )
            $query .= " and order_cs not in (1,3) ";
        
        if( $ex_hold )
            $query .= " and hold = 0 ";

	    $query .= " group by trans_no";

        $result = mysql_query( $query, $connect );
        
        $i = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[pack] > 0 )
                $arr_result['total']['trans_pack_cnt']++;
            else
                $arr_result['total']['trans_cnt']++;
        }

        //*******************
        // 선착불
        //*******************
	    $query = "select trans_who,trans_no
	                        from orders 
	                       where collect_date >= '$start_date'
	                         and collect_date <= '$end_date'
	                         and status>1";
        
        if( $ex_cancel )
            $query .= " and order_cs not in (1,3) ";
        
        if( $ex_hold )
            $query .= " and hold = 0 ";
	    $query .= " group by trans_no";
debug("당일판매요약 송장 : " . $query);           
        $result = mysql_query( $query, $connect );
        
        $i = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[trans_who] == "선불" )
                $arr_result['pre']['cnt']++;
            else
                $arr_result['post']['cnt']++;
                
            $arr_result['total']['cnt']++;
        }
        
        $arr_result['total']['cnt'] = number_format( $arr_result['total']['cnt'] );
        $arr_result['total']['trans_cnt'] = number_format( $arr_result['total']['trans_cnt'] );
        $arr_result['total']['trans_pack_cnt'] = number_format( $arr_result['total']['trans_pack_cnt'] );
        $arr_result['post']['cnt'] = number_format( $arr_result['post']['cnt'] );
        $arr_result['pre']['cnt'] = number_format( $arr_result['pre']['cnt'] );
        
	    echo json_encode( $arr_result );    
	}
	
	/*************************************	
	* 주문 정보.. 2009.11.16 - jk
	*************************************/
	function get_order_info()
	{
	    global $start_date, $end_date, $connect, $ex_cancel, $ex_gift, $ex_hold;
	    
        $arr_result = array();

        //*************************
        // 주문
        //*************************
	    $query = "select qty,seq,pack
	                        from orders 
	                       where collect_date >= '$start_date'
	                         and collect_date <= '$end_date'";
        if( $ex_cancel )
            $query .= " and order_cs not in (1,3) ";
        
        if( $ex_hold )
            $query .= " and hold = 0 ";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[pack] > 0 )
            {
                $arr_result['pack']['cnt']++;
                $arr_result['pack']['qty'] += $data[qty];
            }
            else
            {
                $arr_result['order']['cnt']++;
                $arr_result['order']['qty'] += $data[qty];
            }
            
            $arr_result['total']['cnt']++;
            $arr_result['total']['qty'] += $data[qty];
        }
        
        //*************************
        // 배송
        //*************************
        $arr_result['pack']['today_trans'] = 0;
        $arr_result['pack']['total_trans'] = 0;
	    $query = "select trans_date 
	                from orders
                   where status = 8
                     and trans_date_pos >= '$start_date 00:00:00'
                     and trans_date_pos <= '$end_date 23:59:59'";
        $query .= " group by trans_no";
        
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // 당일출력 당일배송
            if ( $data['trans_date'] >= "$start_date 00:00:00" )
                $arr_result['pack']['today_trans']++;

            // 당일배송 전체
            $arr_result['pack']['total_trans']++;
        }
        
        //*************************
        // 배송수량
        //*************************
	    $query = "select sum(b.qty) sum_b_qty
	                from orders a,
	                     order_products b
                   where a.seq = b.order_seq 
                     and a.status = 8
                     and b.order_cs not in (1,2)
                     and a.trans_date_pos >= '$start_date 00:00:00'
                     and a.trans_date_pos <= '$end_date 23:59:59'";
                     
                     
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );

        // 당일배송 전체
        if( $data[sum_b_qty] )
            $arr_result['pack']['total_products'] = $data[sum_b_qty];
        else
            $arr_result['pack']['total_products'] = 0;
        
        $arr_result['total']['cnt'] = number_format( $arr_result['total']['cnt'] );
        $arr_result['order']['cnt'] = number_format( $arr_result['order']['cnt'] );
        $arr_result['pack']['cnt'] = number_format( $arr_result['pack']['cnt'] );
        
        $arr_result['total']['today_trans'] = number_format( $arr_result['total']['today_trans'] );
        $arr_result['total']['total_trans'] = number_format( $arr_result['total']['total_trans'] );
        $arr_result['total']['total_products'] = number_format( $arr_result['total']['total_products'] );

	    echo json_encode( $arr_result );    
	}
	
	/*************************************
	* 상품 매출리스트 구하기 로직.. 2009.11.12 - jk
	*
	*************************************/
	function get_product_list( $is_download=0 )
	{
	    global $start_date, $date_type, $end_date, $connect, $ex_cancel, $ex_gift, $ex_hold;
	    	
	    $query = "select a.name, 
	                     a.img_500,
	                     sum(b.qty) qty,
	                     a.org_price,
	                     a.shop_price,
	                     if(a.org_id='',a.product_id,a.org_id) as p_id,
	                     d.name d_name,
	                     a.brand a_brand
	                from products a, 
	                     order_products b,
	                     orders c,
	                     userinfo d
	               where b.order_seq = c.seq and
	                     a.supply_code = d.code and 
	                     c.$date_type >= '$start_date 00:00:00' and
	                     c.$date_type <= '$end_date 23:59:59' and
	                     b.product_id = a.product_id and
	                     c.c_seq = 0 ";
        if( $ex_cancel )
            $query .= " and b.order_cs not in (1,2,3,4) ";
            
        if( $ex_gift )
            $query .= " and b.is_gift = 0 ";
        
        if( $ex_hold )
            $query .= " and c.hold = 0 ";    
            
	    $query .= "group by p_id ";

        if( _DOMAIN_ == "pnx1748" )
	        $query .= "order by a.name, qty desc";
        else
	        $query .= "order by qty desc";
debug("당일판매분요약표 : " . $query);
		
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if( _DOMAIN_ == 'soramam' || _DOMAIN_ == 'lalael2'  || _DOMAIN_ == 'hckim1515')
            {
                if ( $data[img_500] )
                {
                    $img_str = $this->disp_image5( $data[p_id], $data[img_500] );
                }
                else
                {
                    $img_str  = "<a href='javascript:openwin2_1(\"popup.htm?template=C209&id=$product_id\",\"descwin\", 900, 500)'>";
                    $img_str .= 	"<img src='/images/noimage.gif' align=absmiddle>";  
                    $img_str .= "<a>";  
                }
            }
            else
                $img_str = "";

            $arr_result[] = array( name         => $data[name]
                                   ,product_id  => $data[p_id]
                                   ,qty         => number_format( $data[qty] )
                                   ,org_price   => number_format( $data[org_price] )
                                   ,shop_price  => number_format( $data[shop_price] )
                                   ,img         => $img_str
                                   ,supply_name => $data[d_name]
                                   ,brand => $data[a_brand]
            );
        }
        
        if ( $is_download)
            return $arr_result;
        else
	        echo json_encode( $arr_result );
	}
	
	function get_product_list2( $is_download=0 )
	{
	    global $date_type, $start_date, $end_date, $connect, $ex_cancel, $ex_gift, $ex_hold;
	    
	    $query = "select a.product_name,
	                     b.shop_options, 
	                     sum(b.qty) qty
	                from orders a,
	                     order_products b
	               where a.seq = b.order_seq and
	                     a.$date_type >= '$start_date 00:00:00' and
	                     a.$date_type <= '$end_date 23:59:59' and
	                     a.c_seq = 0 ";

        if( $ex_cancel )
            $query .= " and b.order_cs not in (1,2,3,4) ";

        if( $ex_gift )
            $query .= " and b.is_gift = 0 ";
            
        if( $ex_hold )
            $query .= " and a.hold = 0 ";    

	    $query .= "group by b.shop_options
	               order by qty desc, b.shop_options";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = array( qty  => number_format( $data[qty] ),
                                   name => ( $_SESSION[PRODUCT_NAME_EXP] ? $data[product_name] . " " : "" ) . $data[shop_options] . "&nbsp"
            );
        }
        
        if ( $is_download)
            return $arr_result;
        else
	        echo json_encode( $arr_result );
	}
	
	/*************************************
	* 판매처 매출 로직 2009.11.12 - jk
	*
	*************************************/

	function get_shop_list()
	{
	    global  $date_type, $start_date, $end_date, $ex_cancel, $ex_gift, $ex_hold, $connect;
	    
	    
        $arr_result = array();
        $arr_result['ex_cancel'] = ( $ex_cancel ? 1 : 0 );
        $arr_result['ex_gift'] = ( $ex_gift ? 1 : 0 );
        $arr_result['ex_hold'] = ( $ex_hold ? 1 : 0 );

	    $query = "select shop_id, 
	                     sum(amount) sum_amount, 
	                     sum(extra_money) sum_extra_money,
	                     sum(qty) sum_qty,
	                     trans_corp,
	                     count(status) status_cnt,
	                     status
	                from orders
	               where $date_type >= '$start_date 00:00:00'
	                 and $date_type <= '$end_date 23:59:59'";

        // 배송후교환 제외
        if( $this->m_arr_config[change] == "org" )
            $query .= " and c_seq=0 ";
        else
            $query .= " and order_cs not in (7,8) ";

        if( $ex_cancel )
	        $query .= " and order_cs not in (1,3) ";
	        
	        
	    if( $ex_hold )
	        $query .= " and hold = 0 ";    

	    if(_DOMAIN_ == "box4u")
	    {
		    $query .= " AND status = 8 
		    			group by shop_id,trans_corp
			           	order by sum_amount desc";
	    }
	    else if(_DOMAIN_ == "mam24")
	    {
		    $query .= " 
		    			group by shop_id,status
			           	order by sum_amount desc";
	    }
	    else
	    {
		    $query .= "group by shop_id
		               order by sum_amount desc";
	    }
	    debug("당일판매분요약표 판매처 : ". $query);
        //*********************************************************************************************** 
        // 판매금액으로 정렬해도, key 가 숫자면 html에서 json parse 시에 판매처코드로 정렬되어버림
        // 이를 방지하기위해 앞에 "S" 붙임 - 2012-06-25 JKH
        //*********************************************************************************************** 
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result['list']["S$data[shop_id]"][amount]  += $data[sum_amount] + $data[sum_extra_money];
            $arr_result['list']["S$data[shop_id]"][qty] 	+= $data[sum_qty];
			
			if(_DOMAIN_ == "mam24")
	    	{
	    		if($data[status] == 7)
	    			$arr_result['list']["S$data[shop_id]"]['status_7'] += $data[status_cnt];
	    		else if($data[status] == 8)
	    			$arr_result['list']["S$data[shop_id]"]['status_8']  += $data[status_cnt];
	    	}
			if(_DOMAIN_ == "box4u")
	    	{
				if($arr_result['list']["S$data[shop_id]"]['trans_17'] <= 0) $arr_result['list']["S$data[shop_id]"]['trans_17'] = 0;
				if($arr_result['list']["S$data[shop_id]"]['trans_67'] <= 0) $arr_result['list']["S$data[shop_id]"]['trans_67'] = 0;
			
	            if($data[trans_corp] == 30017)
	            	$arr_result['list']["S$data[shop_id]"]['trans_17']  += ($data[sum_qty]);
	            else if($data[trans_corp] == 30067)
	        		$arr_result['list']["S$data[shop_id]"]['trans_67']  += ($data[sum_qty]);
	        
	        	$arr_result['list']["S$data[shop_id]"]['trans_corp']  += ($data[sum_qty]);
	        }
        }
        $arr_result['cnt'] = count($arr_result['list']);

        // 판매처 명 입력
        $m_qty    = 0;
        $m_amount = 0;
        $m_trans_17 = 0;
        $m_trans_67 = 0;
        $m_status_7 = 0;
        $m_status_8 = 0;
        foreach ( $arr_result['list'] as $shop_id=>$hValue )
        {
            $m_qty    = $m_qty    + $arr_result['list'][$shop_id]['qty'];
            $m_amount = $m_amount + $arr_result['list'][$shop_id]['amount'];
            
            $m_trans_17 = $m_trans_17 + $arr_result['list'][$shop_id]['trans_17'];
            $m_trans_67 = $m_trans_67 + $arr_result['list'][$shop_id]['trans_67'];
            
            $m_status_7 = $m_status_7 + $arr_result['list'][$shop_id]['status_7'];
            $m_status_8 = $m_status_8 + $arr_result['list'][$shop_id]['status_8'];
            
            
            $arr_result['list']["$shop_id"]['shop_id']= substr($shop_id,1);
            $arr_result['list']["$shop_id"]['name']   = class_shop::get_shop_name( substr($shop_id,1) );
            $arr_result['list']["$shop_id"]['amount'] = number_format( $arr_result['list'][$shop_id]['amount'] );
            $arr_result['list']["$shop_id"]['qty']    = number_format( $arr_result['list'][$shop_id]['qty'] );
            
            $arr_result['list']["$shop_id"]['trans_17'] = number_format( $arr_result['list'][$shop_id]['trans_17'] );
            $arr_result['list']["$shop_id"]['trans_67'] = number_format( $arr_result['list'][$shop_id]['trans_67'] );
            
            $arr_result['list']["$shop_id"]['status_7'] = number_format( $arr_result['list'][$shop_id]['status_7'] );
            $arr_result['list']["$shop_id"]['status_8'] = number_format( $arr_result['list'][$shop_id]['status_8'] );
        }
        
        //
        // total product qty를 구한다.
        // 2012.5.29 - jkryu
        //
        $this->get_product_qty( &$arr_result );
        
        $arr_result['list']['total']['shop_id']= "0";
        $arr_result['list']['total']['name']   = "총 합";	
        $arr_result['list']['total']['amount'] = number_format( $m_amount );
        $arr_result['list']['total']['qty']    = number_format( $m_qty );
        
        $arr_result['list']['total']['trans_17'] = number_format( $m_trans_17 );
        $arr_result['list']['total']['trans_67'] = number_format( $m_trans_67 );
        
        $arr_result['list']['total']['status_7'] = number_format( $m_status_7 );
        $arr_result['list']['total']['status_8'] = number_format( $m_status_8 );
        
        $arr_result['list']['total']['trans_corp'] = number_format( $m_trans_67 + $m_trans_17 );
        
	    echo json_encode( $arr_result );
	    
	}

	//
	// 상품의 개수..
	//
	function get_product_qty( &$arr_result )
	{
	    global $date_type, $start_date, $end_date, $ex_cancel, $ex_gift, $ex_hold, $connect;
	    
        $arr_result['ex_cancel'] = ( $ex_cancel ? 1 : 0 );
        $arr_result['ex_gift'] = ( $ex_gift ? 1 : 0 );
        $arr_result['ex_hold'] = ( $ex_hold ? 1 : 0 );

	    $query = "select a.shop_id, 
	                     sum(b.qty) sum_qty 
	                from orders a, order_products b
	               where a.seq          =  b.order_seq
	                 and a.$date_type >= '$start_date 00:00:00'
	                 and a.$date_type <= '$end_date 23:59:59' ";

        // 배송후교환 제외
        if( $this->m_arr_config[change] == "org" )
            $query .= " and a.c_seq=0 ";

        if( $ex_cancel )
	        $query .= " and a.order_cs not in (1,3) ";
	         
        if( $ex_gift )
	        $query .= " and b.is_gift = 0 ";
	    if( $ex_hold )
	        $query .= " and a.hold = 0 ";     
	         
	    $query .= "group by a.shop_id";

        debug("당일판매분요약표 상품 : ". $query);
	               
        $result = mysql_query( $query, $connect );
        $total = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $total += $data[sum_qty];
            $arr_result['list']["S$data[shop_id]"]['product_qty'] = number_format( $data[sum_qty] );   
        }

        $arr_result['list']['total']['product_qty']    = number_format( $total );        	               
	}
	
	/*************************************
	* 원가 구하기 로직.. 2009.7.2 - jk
	*
	*************************************/
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

    //*************************************
    // 공급가 구하기.. 2009.9.25 - jk
    // 판매처의 공급가를 price_history에서 찾는다.
    // 없으면 기본 가격을 가져온다.
    function get_supply_price( $data )
    {
        global $connect;
        $shop_id    = $data[shop_id];
        $product_id = $data[product_id];
        $_today     = date("Y-m-d"); 
        
        // price_history에서 가격을 찾는다.
        $query = "select supply_price 
                    from price_history 
                   where product_id = '$product_id' 
                     and shop_id    = '$shop_id'
                     and start_date <= '$_today'
                     and end_date   <= '$_today'
                     order by seq desc limit 1";
debug("판매처의 공급가를 price_history에서 찾는다. : " . $query);
        $result = mysql_query($query, $connect);
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[supply_price] )
            return $data[supply_price];
        else
        { 
            // 기간을 초월해서 price_history를 구한다.
            $query = "select supply_price 
                        from price_history 
                       where product_id = '$product_id' 
                         and shop_id    = '$shop_id'";        
            $result = mysql_query($query, $connect);
            $data   = mysql_fetch_assoc( $result );
            
            if ( $data[supply_price] )
                return $data[supply_price];
            else
            {
                // 기간을 초월해서 price_history를 구한다.
                $query = "select supply_price 
                            from price_history 
                           where product_id = '$product_id' 
                             and (shop_id    = '' or shop_id is null)";        
                $result = mysql_query($query, $connect);
                $data   = mysql_fetch_assoc( $result );
                
                if ( $data[supply_price] )
                    return $data[supply_price];
                else
                {
                    // 이도 저도 없으면 상품에 셋팅되어 있는 값을 출력
                    $query = "select supply_price 
                                from products 
                               where product_id = '$product_id' ";        
                    $result = mysql_query($query, $connect);
                    $data   = mysql_fetch_assoc( $result );
                    
                    return $data[supply_price];
                }
            }
                    
        }
    }

	//***********************************
	// 개수 return
	// 2009.6.30 - jk
	// 선결제 개수를 센다
	// 선불 개수를 센다
    function get_count2( $data,$code )
	{
		$shop_code = $data[shop_id] % 100;
		$_val = 0;

		if ( array_key_exists( $shop_code, $this->m_arr_rule ) )
		{
		    $rule = $this->m_arr_rule[$shop_code][$code];	
			$arr_code = split(':', $rule); 
			//echo $data[shop_id] . ":" . $data[$arr_code[0]]. " == " . $arr_code[1] . "<br>";
			if ( $data[$arr_code[0]] == $arr_code[1] )
			   $_val = 1;
		}
		else
		{
			$arr_code = split(':', $code ); 
			//echo $arr_code[0] . ":" . $data[$arr_code[0]]. " == " . $arr_code[1] . "<br>";
			if ( $data[$arr_code[0]] == $arr_code[1] )
			   $_val = 1;
		}

		return $_val;
	}

	//***********************************
	// 가격 return
	// 2009.6.25
	function get_count( $data,$code )
	{
		$shop_code = $data[shop_id] % 100;
		$_val = 0;

		if ( array_key_exists( $shop_code, $this->m_arr_rule ) )
		{
		    $rule = $this->m_arr_rule[$shop_code][$code];	

			$arr_code = split('[^a-z^0-9_]', $rule);
			$_arr = array();	

			// filtering
			foreach ( $arr_code as $key )
			{
				if ( $key && !is_numeric($key))
			        $_arr[$key] = $key;
			   
			}	
			
			foreach ( $_arr as $key )
			{
			    $rule = str_replace( $key, "\$data[" . $key ."]", $rule );
			}
			$str = "\$_val = " . $rule . ";";
			eval( $str );
		}
		else
			$_val = $data[$code];

		// 값이 있으면 1개 없으면 0개
		return $_val?1:0;
	}	

	// cs에서 사용하는 전체 금액
	// 2009.7.9
	function get_price_all( $seq )
	{
		global $connect;
		$query = "select * from orders where seq='$seq'";
		$result = mysql_query( $query, $connect );
		$data   = mysql_fetch_assoc( $result );

        // product_id 구하기
        $query = "select product_id,qty from order_products where order_seq='$seq'";
        $result = mysql_query( $query, $connect );
        $arr_data = array();
        while ( $_data = mysql_fetch_assoc( $result ) )
        {
            $data[qty]        = $_data[qty];
            $data[product_id] = $_data[product_id];
            
            debug ( "amount1: ---------------------- $seq / $data[product_id] ---------------" );
		    if ( !$arr_data[amount ] )
		        $arr_data[amount]       = $this->get_price($data, "amount");
            debug ( "amount2: $arr_data[amount]" );
		    $arr_data[supply_price] += $this->get_price($data, "supply_price");
            debug ( "amount3: $arr_data[supply_price]" );
		    $arr_data[prepay_trans] += $this->get_price( $data,"prepay_trans" );
            debug ( "amount4: $arr_data[prepay_trans]" );
        }

		return $arr_data;
	}

	//***********************************
	// 가격 return
	// 2009.6.25
	// 발주서 원본에 가격 정보가 없는 경우..가격 테이블의 값을 가져온다.
	// order_products에서 상품을 가져다 합을 구해준다.
	// [products:shop_price] / [products:supply_price] / [products:org_price] 
	// [orders:amount]
	// 발주서 원본에 가격이 없는 경우 설정값을 가져온다.
	// [user_define:30]
	function get_price( $data,$code )
	{
		$shop_code = $data[shop_id] % 100;
		$_val = 0;

        // rule이 있는지 여부 확인
		if ( array_key_exists( $shop_code, $this->m_arr_rule ) )
		{
		    $rule = $this->m_arr_rule[$shop_code][$code];	
            
            //debug ( "rule: $rule");

            // rule macro check - 
            // 2009.8.10
            // 사용자 정의 값을 사용할 것인지, price의 값을 사용할 것인지 결정 - jk
            $arr_return = $this->check_user_defined( $data, $rule );
            
            if ( $arr_return['is_set'] )
                $_val = $arr_return['value'];
            else
            {
                // rule을 사용한 계산 값 사용
    			$arr_code = split('[^a-z^0-9^_^.]', $rule);
    			$_arr = array();	
    
    			// filtering
    			// $_arr을 만들어 낸다.
    			foreach ( $arr_code as $key )
    			{
    				if ( $key )
    			        $_arr[$key] = $key;
    			}	
    
    			foreach ( $_arr as $key )
    			{
    			    if ( !is_numeric ( $key ) )
    			        $rule = str_replace( $key, "\$data[" . $key ."]", $rule );
    			}
    			$str = "\$_val = " . $rule . ";";
    			//debug( "[get_price] shop_id: $data[shop_id] / shop_code: $shop_code / rule: $rule / str: $str <br>" );
    			    
    			eval( $str );
    	    }
		}
		else
			$_val = $data[$code];

        // 공급가 supply_price가 0일경우 등록된 상품의 가격을 가져와야 함
        if ( $code == "supply_price" && $_val == 0)
        {
            $_val = $this->get_supply_price( $data );
        }

		return $_val;
	}	

    //**********************************
    // 사용자 정의 값 check
    // 2009.8.10 - jk
    function check_user_defined( $data, $rule )
    {
        global $connect;
        
        $arr_return = array();
        $arr_return['is_set'] = 0;
        
        if (preg_match('/^[\[](.*)[\]]$/', $rule, $match))
        {
            $_val = split(":", $match[1]);
            
            if ( $_val[0] == "user_defined" )
            {
                $arr_return['is_set'] = 1;
                $arr_return['value']  = $_val[1];
            }
            else if ( $_val[0] == "products" )
            {
                // 사용자 정의값의 형태로 값이 셋팅되었음을 정의 함
                $arr_return['is_set'] = 1;              
                
                // arr_produts의 구조
                // [0] => array(product_id, qty), [1] => array(product_id, qty)
                $arr_products = class_order_products::get_arr_products( $data[seq] );
                
                // $_val1에는 정의 값이 들어감 [products:supply_price] 라 가정하면
                // $_val1에는 supply_price가 들어감.
                foreach ( $arr_products as $_product )
                {
                    $arr_price = class_product::get_price_arr( $_product[product_id], $data[shop_id] );
                    $arr_return['value']  += $arr_price[ $_val[1] ] * $_product[qty];
                }
            } 
            else if ( $_val[0] == "orders" )
            {
                // 사용자 정의값의 형태로 값이 셋팅되었음을 정의 함
                $arr_return['is_set'] = 1;
                $arr_return['value']  = $data[ $_val[1] ];
            } 
        }
        
        return $arr_return;
    }

	function get_trans_price( $qty )
	{
		return $qty * $this->m_user_trans_price;
	}

	// 주문 정보 
	// total_amount      : 총 판매액
	// total_supply_price: 총 정산예정금액
	// cnt_user_trans    : 선불 개수 
	// pre_paid_price    : 선결제 금액
	// cnt_paid_price    : 선결제 개수 
	// 교환 주문 처리
	//      배송 전: 일반 주문과 동일
	// 		배송 후: 원 주문은 취소
	// 합포 주문은 배송비를 1회만 계산해야 함
	function get_sale_info()
	{
		global $connect;
		global $query_type, $start_date, $end_date, $apply_cancel_date,$shop_id;


        $obj_order_products = new class_order_products();

		// step 1
		// 주문 
		$query = "select shop_id,seq,pack,amount,supply_price,qty,code10,code11,code12,code13,code14,code15,code16,code17,code18
						,code19,org_trans_who,trans_who,extra_money,order_id,order_cs
					from orders 
			 	   where $query_type >= '$start_date 00:00:00'
					 and $query_type <= '$end_date 23:59:59'";
        
        if ( $shop_id )
            $query .= " and shop_id=$shop_id";					 
	
		$result = mysql_query( $query, $connect );
		$arr_shopinfo = array();	
		
		while ( $data = mysql_fetch_assoc($result))
		{
			$i++;
			if ( $i%400 == 0 )
		        $this->show_txt( $i );

			$amount       = 0;
			$supply_price = 0;

			// 수량
			$arr_shopinfo[$data[shop_id]][tot_order] = $arr_shopinfo[$data[shop_id]][tot_order] + 1;

			// 기본 rule적용 값
			$supply_price = $this->get_price( $data,"supply_price" );
			$amount       = $this->get_price( $data,"amount" ); 
						
			// 배송 전 전체 취소일 경우만..제외함
			// 2010.8.19
			if ( $data[order_cs] == 1 )
			{
    			// 선결제 건수 ??
    			// 로직이 이상 야릇 함...
    			$prepay_cnt    = $this->get_count( $data,"prepay_trans" );
    			
                // 선결제 금액 - get_price에서 뭘 해야하지?
    			$prepay_price  = $prepay_cnt * $this->get_price($data,"prepay_trans");
		    }
		    
			// case1.1 
			// 전체 취소
			// 환경설정 : 취소가 refund_date가 아닐 경우만 로직을 수행함..
			if ( $this->m_arr_config[cancel] != "refund_date")
			{
    			if ( $data[order_cs] == 1 || $data[order_cs] == 3 )
    			{
    			    // 취소 주문건
    			    $arr_shopinfo[$data[shop_id]][cancel_order]++;
    			    
    			    // 취소 상품 개수 - step 2에서 상품 취소 정보 구함
    			    $arr_shopinfo[$data[shop_id]][cancel_price] += $supply_price;
    			}
    			// case 1.2 
    			// 부분 취소
    			// 부분 취소의 상품개수는 밑의 step 2에서 구함.
    			else if ( $data[order_cs] == 2 || $data[order_cs] == 4 )
    			{
    			    // 취소 주문 건수
    			    // 부분 취소의 취소 금액은 밑에서 결정한다.
    			    $arr_shopinfo[$data[shop_id]][cancel_order]++;			    
    			}
    	    }
			
			//echo $arr_shopinfo[$data[shop_id]][cancel_cnt] . "<br>";
			
			//************************
			// case 2 : 교환
			// case 2.1
			// 배송 전 전체 교환 , 배송 후 부분 교환과 로직이 동일?
			else if ( $data[order_cs] == 5 )
			{
			    // 환경 설정에 따라 달라지는 부분
			    // 원 주문을 정산에 포함..
			    if ( $this->m_arr_config[change] == "org"){
				    if  ( substr($data[order_id],0,1) != "C" )
				    {
			            $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];
					    $amount       = $amount;
					    $supply_price = $supply_price;
				    }
					else
					{
						$amount       = 0;
						$supply_price = 0;
					}
			    }
			    else if ( $this->m_arr_config[change] == "new")
			    {
					// C붙은 신규 주문이 다시 교환인 상태
					// 원 주문이 있다면 배송 후 교환 처리로직에서 처리 해 준다
			        $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];

					$amount       = $amount;
					$supply_price = $supply_price;
			    }
			}	
			// case 2.2
			// 배송 전 부분교환
			// order_products에 정보가 저장됨
			else if ( $data[order_cs] == 6 )
			{
				// 환경 설정에 따라 달라지는 부분
				// 원 주문을 정산에 포함..
			    if ( $this->m_arr_config[change] == "org"){
				    if  ( substr($data[order_id],0,1) == "C" )
					{
					    $amount       = 0;
					    $supply_price = 0;
					}
					else
				    {
			            $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];
					    $amount       = $amount;
					    $supply_price = $supply_price;
				    }
			    }
			    // 신 주문을 정산에 포함..
				// 교환된 주문이 부분 교환일 경우
			    else if ( $this->m_arr_config[change] == "new")
			    {
					// 원 주문이 있다면 배송 후 교환 - 배송 후 교환 처리로직에서 처리 해 준다
					// C붙은 신규 주문은 다시 교환인 상태
			        $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];
					// 총 결제금액
					$amount       = $amount;
	    	
					// 정산 예정금액
					$supply_price = $supply_price;
			    }
			}
			// case 2.3
			// 배송 후 전체 교환
			else if ( $data[order_cs] == 7 )
			{
			    // 환경 설정에 따라 달라지는 부분
				// 원 주문을 정산에 포함..
			    if ( $this->m_arr_config[change] == "org"){
				    if  ( substr($data[order_id],0,1) != "C" )
				    {
			            $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];
					    $amount       = $amount;
					    $supply_price = $supply_price;
				    }
					else
					{
						$amount       = 0;
						$supply_price = 0;
					}
			    }
			    // 신 주문을 정산에 포함..
			    else if ( $this->m_arr_config[change] == "new")
			    {
					// 무조건 뺀다.	
					$amount       = 0;
					$supply_price = 0;
			    }
			}
			// case 2.4
			// 배송 후 부분 교환
			else if ( $data[order_cs] == 8 )
			{
				// 원 주문을 정산에 포함..
				// 신규 주문에 추가 입금된 extra_money는 누락
				// 신규 주문에 extra_money가 추가 됨
			    if ( $this->m_arr_config[change] == "org"){
				    if  ( substr($data[order_id],0,1) != "C" )
				    {
					    $amount       = $amount;
					    $supply_price = $supply_price;
				    }
					else
					{
					    $amount       = 0;
					    $supply_price = 0;
					}
			    }
			    // 신 주문을 정산에 포함..
				// extra_money는 (-) 값이 들어가 있음
			    else if ( $this->m_arr_config[change] == "new")
			    {
			        // change_price
			        $arr_shopinfo[$data[shop_id]][extra_money] += $data[extra_money];

					$amount       = $amount       + $data[extra_money];
					$supply_price = $supply_price + $data[extra_money];
			    }
			}
			// case 3 
			// 정상
			// 교환건(Cxxx) + 정상 주문
			else{
			    if ( $this->m_arr_config[change] == "org"){
				    if  ( substr($data[order_id],0,1) != "C" )
				    {
			            $arr_shopinfo[$data[shop_id]][extra_money] = $arr_shopinfo[$data[shop_id]][extra_money] + $data[extra_money];
					    // 총 결제금액
					    $amount       = $amount;
	    	
					    // 정산 예정금액
					    $supply_price = $supply_price;
				    }
			    }
			    // 신 주문을 정산에 포함..
				// extra_money는 (-) 값이 들어가 있음
			    else if ( $this->m_arr_config[change] == "new")
			    {
			        $arr_shopinfo[$data[shop_id]][extra_money] = $arr_shopinfo[$data[shop_id]][extra_money] + $data[extra_money];

					// 총 결제금액
					$amount       = $amount;
	    	
					// 정산 예정금액
					$supply_price = $supply_price;
			    }
			}
			// end of step 1
			////
			
			
			
			//*************************************************************
			//
			// 선불 건수
			// 합포 주문 혹은 단독 배송 상품의 수량을 센다
			if ( $data[seq] == $data[pack] || $data[pack] == '' || $data[pack] == 'NULL' || $data[pack] == 0)
			{
			    // 취소건 check
			    echo "order_cs: $data[order_cs]";
			    
			    $usertrans_cnt = $this->get_count2( $data,"trans_who:선불" );	// transwho 필드가 "선불"인경우 count
			    // 선불 건수
			    // 선불 건수 + 선결제
			    $arr_shopinfo[$data[shop_id]][usertrans_cnt] = $arr_shopinfo[$data[shop_id]][usertrans_cnt] + $usertrans_cnt;
			}
            
			// 선결제 건수
			// $arr_shopinfo[$data[shop_id]][usertrans_cnt]    = $arr_shopinfo[$data[shop_id]][usertrans_cnt] + $prepay_cnt; // 선불 건수에 더해줌
			$arr_shopinfo[$data[shop_id]][prepay_cnt]       = $arr_shopinfo[$data[shop_id]][prepay_cnt]   + $prepay_cnt;
		    $arr_shopinfo[$data[shop_id]][prepay_price]     = $arr_shopinfo[$data[shop_id]][prepay_price] + $prepay_price;
		    
			// 총 결제
			$arr_shopinfo[$data[shop_id]][tot_amount]       = $arr_shopinfo[$data[shop_id]][tot_amount] + $amount;

			// 총 공급가(정산예정가)
			$arr_shopinfo[$data[shop_id]][tot_supply_price] = $arr_shopinfo[$data[shop_id]][tot_supply_price] + $supply_price;
		}
        // end of orders
    	    

        //***********************
        //
		// step 2
		// 상품 개수 구하기
		$query = "select b.*,a.amount,a.supply_price,a.order_id,a.shop_id
						,a.code11,a.code12,a.code13,code14,code15,code16,code17,code18,code19,code20
                    from orders a, order_products b
                   where a.seq = b.order_seq
			 	     and $query_type >= '$start_date 00:00:00'
					 and $query_type <= '$end_date 23:59:59' ";

        if ( $shop_id )
            $query .= " and a.shop_id = $shop_id";					 


		$result = mysql_query( $query, $connect );
		while ( $data = mysql_fetch_assoc($result))
		{
			//$arr_shopinfo[$data[shop_id]][tot_products] = $arr_shopinfo[$data[shop_id]][tot_products] + $data[qty];
			$arr_shopinfo[$data[shop_id]][tot_products] += $data[qty];
			// 취소 주문 처리
			// 취소 상품 수, 취소 금액
			// refund_price: 취소금액 합계
			// 신발주 시스템에서는 order_products.order_cs가 생성됨
			// 1,2,3,4: 취소

            // refund_date가 아닐 경우만...작업.
            // refund_date일 경우 따로 작업 해야 한다.
            if ( $this->m_arr_config[cancel] != "refund_date")
			{
    			// 전체 취소는 orders에서 계산해야 함
    			// 취소 개수 처리
    			if ( $data[order_cs] == 2 || $data[order_cs] == 4 || $data[order_cs] == 1 || $data[order_cs] == 2 )
    			{
    			    $arr_shopinfo[$data[shop_id]][cancel_cnt]   += $data[qty];
    			    // $arr_shopinfo[$data[shop_id]][cancel_price] += $data[refund_price];
    			}
    
                // 부분취소만 refund_price를 뺀다.
                if ( $data[order_cs] == 2 || $data[order_cs] == 4 )
    			{
    			    $arr_shopinfo[$data[shop_id]][cancel_price] += $data[refund_price];
    			}
		    }

			// 교환 주문 처리
			// 신 주문이 정산에 포함될 경우 ..
			// change_price는 supply_price에서 차감될 금액임.. 
			// * 금액에 대한 계산은 윗쪽(orders) 에서 진행됨
			if ( $this->m_arr_config[change] == "new" )
			{
			    // 교환 4,5,6,7
			    if ( $data[order_cs] <= 8 and $data[order_cs] >= 4 ) 
			    {
			        $arr_shopinfo[$data[shop_id]][change_cnt] += $data[qty];
			    }
			}
			// 원 주문이 정산에 포함될 경우..
			else
			{
				//원 주문이 정산에 포함될 경우는 교환 주문의 값이 존재하지만 정산금액에서 차감하지 않는다. - 
				if ( substr( $data[order_id],0,1) == "C" )
			        $arr_shopinfo[$data[shop_id]][change_cnt] = $arr_shopinfo[$data[shop_id]][change_cnt] + $data[qty];
			}

			$org_price = $this->get_org_price( $data );
			$arr_shopinfo[$data[shop_id]][org_price]   = $arr_shopinfo[$data[shop_id]][org_price]   + ($org_price * $data[qty]);
		}

        
        //***********************************
        // step3 
        // 취소일 일 경우 취소일로 주문을 조회 해야 함..
        // 배송일인 경우 ..
        // 정산에는 검색 조건의 취소 주문이 포함 됨
        // 취소 금액은 금일 취소 주문의 금액이 포함 됨
        $before_seq = "";
        if ( $this->m_arr_config[cancel] == "refund_date")
		{
			//
			// step 3.2 취소 상품
			$query = "select a.seq,b.refund_price, b.order_cs, b.qty, a.amount,a.supply_price,a.order_id,a.shop_id,a.code11,a.code12,a.code13,code14,code15,code16,code17,code18,code19,code20
                        from orders a, 
                            ( select order_seq,product_id,order_cs,refund_price,qty from order_products 
                                    where cancel_date >= '$start_date 00:00:00' 
                                      and cancel_date <= '$end_date 23:59:59'  ) b
                        where a.seq = b.order_seq
                            and b.order_cs in (1,2,3,4)";
            if ( $shop_id )
                $query .= " and a.shop_id = $shop_id";

//echo $query;
                
			$result = mysql_query( $query, $connect );
			
			$before_seq = "";
			while ( $data = mysql_fetch_assoc( $result ) )
			{
			    /*
			    if($_SESSION[LOGIN_LEVEL] == 9 )
			    {
			        echo "b: $before_seq / $data[seq] / $data[order_cs] / $data[refund_price] / " . $this->get_price($data, "supply_price") . "<br>";   
			    }
			    */
			    
			    // 취소 개수
			    if ( $before_seq != $data[seq] )
			    {
                    $before_seq = $data[seq];
                    $arr_shopinfo[$data[shop_id]][cancel_order]++;
			    }
			    
			    // 전체 취소
			    if ( $data[order_cs] == 1 || $data[order_cs] == 3 )
    			{
    			    $arr_shopinfo[$data[shop_id]][cancel_price] += $this->get_price($data, "supply_price");
    			    $arr_shopinfo[$data[shop_id]][cancel_cnt]   += $data[qty];   
    			}
    
                // 부분취소만 refund_price를 뺀다.
                if ( $data[order_cs] == 2 || $data[order_cs] == 4 )
    			{
    			    $arr_shopinfo[$data[shop_id]][cancel_price] += $data[refund_price];
    			    $arr_shopinfo[$data[shop_id]][cancel_cnt]   += $data[qty];   
    			}
    	    }
			// enf of 취소 상품
	    }

		// 실정산, 선결제금액
		foreach ( $arr_shopinfo as $_shop_id => $shopinfo )
		{
		    $arr_shopinfo[$_shop_id][real_supply_price] = $arr_shopinfo[$_shop_id][tot_supply_price] + $arr_shopinfo[$_shop_id][extra_money] - $arr_shopinfo[$_shop_id][cancel_price] - $arr_shopinfo[$_shop_id][change_price];
			
			// 선불 택배비
			$arr_shopinfo[$_shop_id][usertrans_price] = $this->get_trans_price( $arr_shopinfo[$_shop_id][usertrans_cnt] );
		}
				
		//*******************************************************
		// 취소 원가 계산
		// 2010.2.5 
        // 전체 원가에서 취소 원가를 빼준다 -> 업체 수익이 증가한다.
        if ( $this->m_arr_config[cancel] == "refund_date")
		{
		    $query = "select sum(a.org_price) cancel_org_price , b.shop_id
		                from products a, (
		                     select order_seq,product_id,order_cs,refund_price,qty,shop_id from order_products 
                                    where cancel_date >= '$start_date 00:00:00' 
                                      and cancel_date <= '$end_date 23:59:59'
                                      and order_cs in (3,4)) b
                       where a.product_id = b.product_id
                       group by b.shop_id";
	    }
	    else
	    {
	        $query = "select sum(a.org_price) cancel_org_price, b.shop_id
		                from products a, (
		                     select order_seq,product_id,order_cs,refund_price,qty,shop_id from order_products 
                                    where match_date >= '$start_date 00:00:00' 
                                      and match_date <= '$end_date 23:59:59'
                                      and order_cs in (3,4)) b
                       where a.product_id = b.product_id
                       group by b.shop_id";    
	    }
		
		$result = mysql_query( $query, $connect );
		while ( $data = mysql_fetch_assoc( $result ) )
		{
		    $arr_shopinfo[ $data[shop_id] ]['cancel_org_price'] = $data[cancel_org_price];
		}
		
        //echo "<pre>";
        //print_r ( $arr_shopinfo );
        //echo "</pre>";

		return $arr_shopinfo;
	}


}
?>
