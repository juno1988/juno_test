<?
// stat rule2
// date: 2010.1.15 - jk
// 정산룰 관련 table
//
require_once "class_top.php";
require_once "class_product.php";
require_once "class_order_products.php";

class class_statrule2 extends class_top {
    ///////////////////////////////////////////
	var $m_rule   = array();
	var $m_user_trans_price = 0;
	var $m_arr_config = array();
	var $m_debug      = 0;      // 1: debug mode on 0: off
	
	// rule 을 올린다.
	function load_rule( $rule )
	{
	    $this->m_rule = $rule;
	}

    //
    // $data를 기준으로 $key값을 구한다.
    // 2011.12.27 발주시 사용하는 자료..
    /*
        $query = "select *from orders where status=0 and order_status=50";
        ...
        while( $data = mysql_fetch_assoc($result) )
        {
            $stat_info[amount] = $obj->get_price2( "amount", $data );
            ...
        }
    */
    function get_price2( $key, $data )
    {
        //
        // data에 supply_id, product_id가 없으므로 추가한다.
        // 
        $arr_products     = class_order_products::get_arr_products( $data[seq] );

        $data[supply_id]  = $arr_products[0][supply_id];
        $data[product_id] = $arr_products[0][product_id];
        $data[shop_price] = $arr_products[0][shop_price];
        
        // 조건에 맞는 룰을 가져온다.
        $arr_rule_data = $this->get_rule( $data );   
    
        $this->debug_array_ex( $arr_rule_data );
        
        // 조회 성공인 경우...
        if ( $arr_rule_data[success] == 1 )
        {
            $this->load_rule( $arr_rule_data['rule'] );
            
            $price = $this->get_price( $key, $data );
            
            return $price;
        }
        else
        {
            return $data[$key];    
        }
    }
    
    //
    // 배송 마진 계산을 위해서 사용
    // 2013.8.1 - jkryu
    // makeshop, 쿠팡의 경우 여러줄에 선결제 정보가 있음. - 1개만 계산
    //
    // arr_margin_trans는 class_F300.php에서 pass됨
    // $arr_margin_trans[] = array(
    //            seq        => $data['xx']
    //            ,shop_id   => $data['shop_id']
    //            ,is_pack   => $data['pack']
    //            ,trans_who => $data['trans_who']
    //            ,price     => 0
    //        );
    function get_margin_trans( $arr_margin_trans, $shop_id )
    {
        global $connect, $query_type, $start_date, $end_date;
     
        if ( !$end_date )
            $end_date = $_REQUEST['end_date'];
     
        // rule을 가져온다
        $query = "select *
                    from stat_rule2 
                   where shop_id   =  '$shop_id' 
                     and from_date <= '$start_date'
                     and to_date   >= '$end_date' 
                     and enable = 1
                     order by priority desc limit 1";  
        
        //debug( $query );
        
        $result    = mysql_query( $query, $connect );
        $data_rule = mysql_fetch_assoc( $result );
        
        // 배송마진 계산에 대한 룰이 있는지 확인.
        $total_price = 0;
        
        debug("\n margin_trans: " . $data_rule['margin_trans']);
        
        if ( $data_rule['margin_trans'] )
        {
            // 사용자 정의 값을 사용하는지..check.
            // [products:trans_fee] 사용
            $sub_total_price = 0;
            
            // [products:trans_fee] 처리 룰..
            // arr의 struct
            /*
             seq        => $data['xx']
            ,shop_id   => $data['shop_id']
            ,is_pack   => $data['pack']
            ,trans_who => $data['trans_who']
            ,price     => 0
            */
            if( strpos($data_rule['margin_trans'],"[products:trans_fee]") !== false )
            {
                // [products:trans_fee]를 상품의 가격으로 변경한다.
                foreach( $arr_margin_trans as $_arr )
                {
                    if ( $_arr['shop_id'] == $shop_id )
                    {
                        $rule = $data_rule['margin_trans'];
                        
                        // 상품의 배송비를 가져온다.
                        $trans_fee = $this->get_order_products_trans_fee( $_arr );
                        
                        // replace trans_fee
                        $rule = str_replace( "[products:trans_fee]",$trans_fee,$rule);
                        
                        $str  = "\$_price += " . $rule . ";";
                        
                        //debug( $str );
                        // rule실행.
                        eval( $str );
                    }
                }
            }
            else if ( 1 )
            {
                // 다른 룰..
                //debug("false 1112233");
            }
            
            $total_price += $_price;
        }
        
        debug( "total_price: $total_price");
        return $total_price;
    }
    
    
    // arr의 struct
    // order_products의 상품중 배송비가 가장싼 상품을 가져온다.
    /*
     seq        => $data['xx']
    ,shop_id   => $data['shop_id']
    ,is_pack   => $data['pack']
    ,trans_who => $data['trans_who']
    ,price     => 0
    */
    function get_order_products_trans_fee( $_arr )
    {
        global $connect;
        
        $_seq = $_arr['seq'];
        if ( $_arr['is_pack'] > 0 )
        {
            $query = "select seq from orders where pack='$_seq'";
            $result = mysql_query( $query, $connect );
            $_seq = "";
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $_seq .= $_seq ? "," : "";
                $_seq .= $data['seq'];
            }
        }
        
        $query = "select product_id from order_products where order_seq in ( $_seq ) and order_cs <> 1";
        $result = mysql_query( $query, $connect );
        $str_product_id = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $str_product_id .= $str_product_id ? "," : "";
            $str_product_id .= "'" . $data['product_id'] . "'";   
        }
        
        $query = "select trans_fee from products 
                   where product_id in ( $str_product_id ) order by trans_fee limit 1";        
        
        //debug( "get_trans_fee: $query ");
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data['trans_fee'];
    }
    
    //
    // margin_trans 배송 마진의 계산에서만 사용된다. - 계산안됨.
    // 왜??
    // 2012.9.21 - jkryu
    //
    function _get_price( $key, $data, $shop_id )
    {
        global $connect, $query_type, $start_date, $end_date;
        
        //debug("_get_price 1: $key");
        
        // rule을 가져온다
        $query = "select *
                    from stat_rule2 
                   where shop_id   =  '$shop_id' 
                     and from_date <= '$start_date'
                     and to_date   >= '$end_data' 
                     and enable = 1
                     order by priority desc limit 1";  
        
        $result    = mysql_query( $query, $connect );
        $data_rule = mysql_fetch_assoc( $result );

        if ( $data_rule[$key] )
        {        
            $rule = $data_rule[$key];
            // 발주일이 정산 적용일 사이에 있는 지 확인한다.
            // 
            
            // rule macro check - 
            // 2009.8.10
            // 사용자 정의 값을 사용할 것인지, price의 값을 사용할 것인지 결정 - jk            
            //debug( "_get_price 2: $rule ");
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
    			    // key에 [code]_c 가 들어갈 경우 text의 숫자 중 [] 에 묶인 내용을 가져와서 숫자로 치환한다.
    				if ( $key )
    				{
    				    // [] 사이의 숫자를 가져온다.
    				    if ( strpos( $key, "_c") > 0 && $key != "usertrans_cnt")
    				    {
    				        list( $a, $b ) = split( "_", $key );
    				        $_str = $data[$a];
    				        
    				        preg_match( "/\[(.*)\]/",$_str , $matches, PREG_OFFSET_CAPTURE );
    				        //print_r ( $matches );
    				        
    				        $_arr[$key] = $matches[1][0]?$matches[1][0]:0;
    				    }
    				    // 주문 개수를 구한다.
    				    else 
    				    {
    				        // usertrans_cnt는 F300에서 실시간 계산한다.
    				        // 2012.9.21 - jkryu
        				    if ( $key == "cnt_order" )
        				    {
        				        $order_cnt  = $this->get_order_cnt( $data );   
        				        $_arr[trim($key)] = $order_cnt;
        				    }
        				    else  if ( $key == "cnt_order_seq" )
        				    {
        				        $order_cnt_seq  = $this->get_order_cnt_seq( $data );   
        				        $_arr[trim($key)] = $order_cnt_seq;
        				    }
        				    else if ( $key == "tot_products" )
        				    {
                                $_arr['tot_products'] = $this->get_tot_products( $data[seq] );
        				    }
        				    else
        				    {
        			            $_arr[$key] = $key;
        			        }
    			        }
    			    }
    			}	
                
                // sort를 하지 않으면 code1이 code11을 수정해 버림..핵심!!!
                ksort( $_arr , SORT_STRING);
                
                
    			foreach ( $_arr as $key => $val )
    			{
    			    if ( !is_numeric ( $key ) )
    			    {   
    			        $key = trim( $key );
    			        
    			        // _get_price 임..1x1x
    			        if ( strpos( $key, "_c") > 0 && $key != "usertrans_cnt")
    			        {
    			            $rule = str_replace( $key,  $val, $rule );
    			        }
    			        else if ( $key == "cnt_order" || $key == "cnt_order_seq" )
    			        {
    				        $rule = str_replace( $key,  $val, $rule );
    				    }
    				    else if ( $key == "tot_products" )
    				    {
                            $rule = str_replace( "tot_products",  $val, $rule );
    				    }
    			        else
    			        {
    			            //$rule = str_replace( $key, "\$data[" . $key ."]", $rule );
    			            
    			            //$pattern = "/" . $key . "([^0-9])/";
    			            //$rule = preg_replace(  $pattern, "\$data[" . $key ."]", $rule );
    			            $pattern = "/(".$key.")([^0-9]|$)/";
    			            $replacement = '$data[${1}]${2}';
    			            $rule = preg_replace(  $pattern, $replacement, $rule );
    			            
    			        }
    			    }
    			}
    			$str = "\$_val = " . $rule . ";";
    			
    			//debug( $str );
    			
    			eval( $str );
    	    }
        }
        
		return $_val ? $_val : 0;   
        
    }
    
    //*************************************************
    //
    // stat_rule2 테이블에서 적절한 rule을 읽어온다.	
    // get_price2에서만 사용할 목적 2011.12.27 - jk
    // product_id, supply_id가 들어온다.
    //
    function _get_rule( $data )
    {
        // 판매처 + 기간에 맞는 rule을 가져온다.
        global $connect;
        
        $query = "select * 
                    from stat_rule2 
                   where shop_id   =  '$data[shop_id]' 
                     and from_date <= '$data[collect_date]'
                     and to_date   >= '$data[collect_date]' 
                     and enable = 1";
                   
        $opt = "";   
        /*                                       
        if ( $data[product_id] )    
        {
            $opt .= " and product_id = '$data[product_id]'";
        }
        */
        if ( $data[supply_id] )
        {
            $opt .= $opt ? " or " : " and ";
            $opt .= " supply_id='$data[supply_id]'";
        }

        $query .= $opt;
        $query .= " order by priority desc limit 1";

        $result = mysql_query( $query, $connect );
        
        $arr_rules    = array();
        $arr_result   = array(success=>0); // 기본 실패
        while ( $_data = mysql_fetch_assoc( $result ) )
        {
            $arr_rules[] = $_data;
        }
        
        if ( count( $arr_rules ) == 1 )
        {
            $arr_result['success'] = 1;
            $arr_result['rule']    = $arr_rules[0];
        }
        else
        {
            // 기본 룰
            $query = "select * 
                    from stat_rule2 
                   where shop_id   =  '$data[shop_id]' 
                     and from_date <= '$data[collect_date]'
                     and to_date   >= '$data[collect_date]' 
                     and enable = 1
                     and shop_product_id=''
                     and product_id     =''
                     and supply_id      =''
                     limit 1";
            
            $result = mysql_query( $query, $connect );
        
            while ( $_data = mysql_fetch_assoc( $result ) )
            {
                $arr_rules[] = $_data;
            }
            
            if ( count( $arr_rules ) == 1 )
            {
                $arr_result['success'] = 1;
                $arr_result['rule']    = $arr_rules[0];
            }             
        }
        
        return $arr_result;
    }
    
    //*************************************************
    //
    // stat_rule2 테이블에서 적절한 rule을 읽어온다.	
    // get_rule -> _get_rule생성  2011.12.27 - jk
    function get_rule( $data )
    {
        // 판매처 + 기간에 맞는 rule을 가져온다.
        global $connect;
        
        // get rule을 하면서 
        // shop_product_id
        // product_id
        // supply_id 추가 2013.5.13 - jkryu bug fix
        $query = "select * 
                    from stat_rule2 
                   where shop_id   =  '$data[shop_id]' 
                     and from_date <= '$data[collect_date]'
                     and to_date   >= '$data[collect_date]' 
                     and enable = 1
                     order by priority";                     
        
        $result = mysql_query( $query, $connect );
        
        $arr_rules    = array();
        $arr_result   = array(success=>0); // 기본 실패
        while ( $_data = mysql_fetch_assoc( $result ) )
        {
            $arr_rules[] = $_data;
        }

//debug( "start_get_rule");
//debug ( "data seq: " . $data[seq] );
        
        //******************************************
        //
        // rule이 0개 이상일 경우
        //
        // shop_product_id 찾는다.
        $is_matched_cnt = 0;
        if ( count( $arr_rules ) > 1 )
        {
            $arr_temp = array();
            foreach ( $arr_rules as $rule )
            {   
                $pos = strpos( $rule[ shop_product_id ], $data[shop_product_id] );

                if ( $pos === 0 || $pos >= 1 )
                { 
                    $arr_temp[] = $rule;
                    $is_matched_cnt++;
                }   
            }
            
            if ( count( $arr_temp ) > 0 )
                $arr_rules = $arr_temp;
        }
        
        // product_id 찾는다.
        if ( count( $arr_rules ) > 1 )
        {
            $arr_temp = array();
            foreach ( $arr_rules as $rule )
            {
                if ( $rule[product_id] == $data[product_id] )
                {   
                    $arr_temp[] = $rule;
                    $is_matched_cnt++;
                }   
            }
            if ( count( $arr_temp ) > 0 )
                $arr_rules = $arr_temp;
        }
        
        //
        // supply_id 찾는다
        //
        if ( count( $arr_rules ) > 1 )
        {
            $arr_temp = array();
            foreach ( $arr_rules as $rule )
            {
                // 위에서 이미 검증함
                if ( $rule[product_id] != "" || $rule[shop_product_id] != "")
                {
                    continue;
                }
                
                if ( $rule[supply_id] == $data[supply_id] )
                {   
                    $arr_temp[] = $rule;
                    $is_matched_cnt++;
                }   
            }
            
            if ( count( $arr_temp ) > 0 )
                $arr_rules = $arr_temp;   
        }
        
        //
        // 매칭된 값이 하나도 없는경우 rule중 설정값이 없는 rule을 사용해야 함
        // 2014.10.16 - jkryu
        //
        if ( $is_matched_cnt == 0 )
        {
            $arr_temp = array();
            
            foreach ( $arr_rules as $rule )
            {   
                if ( !$rule[supply_id] && !$rule[product_id]  && !$rule[ shop_product_id ] )
                {
                    $arr_temp[] = $rule;
                }   
            }
            
            if ( count( $arr_temp ) > 0 )
                $arr_rules = $arr_temp;    
        }
        
        // 결과..
        $arr_result['success'] = 1;
        $arr_result['rule']    = $arr_rules[0];
        
        return $arr_result;
    }
        	
	function get_price( $key, $data )
	{

        $this->debug_ex( "start get_price\n" );

	    // rule이 있는지 여부 확인
	    $rule = $this->m_rule[$key];

        $this->debug_ex( "key $key \n" );

	    $rule = trim( $rule );
	    
        $this->debug_ex( "rule $rule \n" );

        if ( $rule )
        {   
            // 발주일이 정산 적용일 사이에 있는 지 확인한다.
            // 2009.8.10
            // 사용자 정의 값을 사용할 것인지, price의 값을 사용할 것인지 결정 - jk            
            $arr_return = $this->check_user_defined( $data, $rule );
            
            if ( $arr_return['is_set'] )
            {
                $_val = $arr_return['value'];
            }
            else
            {
                // rule을 사용한 계산 값 사용
    			$arr_code = split('[^a-z^0-9^_^.]', $rule);
    			$_arr = array();	
    
    			// filtering
    			// $_arr을 만들어 낸다.
    			foreach ( $arr_code as $key )
    			{
    			    
    			    // key에 [code]_c 가 들어갈 경우 text의 숫자 중 [] 에 묶인 내용을 가져와서 숫자로 치환한다.
    				if ( $key )
    				{
    					//2014.03.05 최웅 - 정산룰에 PHP 함수가 적용될경우 깨짐방지.
    					if( $key == 'ceil' || $key == 'floor' || $key == 'round' )  continue;
    					    					
    				    // [] 사이의 숫자를 가져온다.
    				    if ( strpos( $key, "_c") > 0  && $key != "usertrans_cnt" )
    				    {
    				        list( $a, $b ) = split( "_", $key );
    				        $_str = $data[$a];
    				        
    				        preg_match( "/\[(.*)\]/",$_str , $matches, PREG_OFFSET_CAPTURE );
    				        //print_r ( $matches );
    				        
    				        $_arr[$key] = $matches[1][0]?$matches[1][0]:0;
    				    }
    				    // 주문 개수를 구한다.
    				    else 
    				    {
    				        // usertrans_cnt는 F300에서 실시간 계산한다.
    				        // 2012.9.21 - jkryu
        				    if ( $key == "cnt_order" )
        				    {
        				        $order_cnt  = $this->get_order_cnt( $data );   
        				        $_arr[trim($key)] = $order_cnt;
        				    }
        				    else if ( $key == "cnt_order_seq" )
        				    {
        				        $order_cnt_seq  = $this->get_order_cnt_seq( $data );   
        				        $_arr[trim($key)] = $order_cnt_seq;
        				    }
        				    else
        				    {
        			            $_arr[$key] = $key;
        			        }
    			        }
    			    }
    			}	
                
                // sort를 하지 않으면 code1이 code11을 수정해 버림..핵심!!!
                ksort( $_arr , SORT_STRING);
                
    			foreach ( $_arr as $key => $val )
    			{
    			    if ( !is_numeric ( $key ) )
    			    {   
    			        $key = trim( $key );
    			        
    			        if ( strpos( $key, "_c") > 0 )
    			        {
    			            $rule = str_replace( $key,  $val, $rule );
    			        }
    			        else if ( $key == "cnt_order" || $key == "cnt_order_seq" )
    			        {
    				        $rule = str_replace( $key,  $val, $rule );
    				    }
    			        else
    			        {
    			            $pattern = "/(".$key.")([^0-9]|$)/";
    			            $replacement = '$data[${1}]${2}';
    			            $rule = preg_replace(  $pattern, $replacement, $rule );
    			        }
    			    }
    			}
    			
    			$str = "\$_val = " . $rule . ";";
    		    $this->debug_ex("code:" . $str );
    			eval( $str );
    	    }
        }
        
        $this->debug_ex( "val: " . $_val . "/seq: $data[seq]/");         
        
        // 쇼셜같은경우 정산예정금액에서 소수점아래 버림으로 해야하는데 이지어드민에서는 올림으로 처리가 됩니다.버림이 될수있게끔 수정이 가능할까요?
        // 2014.10.27 요청사항 - jkryu
        if ( _DOMAIN_ == "memorette" )
        {
            if ( $data[shop_id] % 100 == 53 || $data[shop_id] % 100 == 54 || $data[shop_id] % 100 == 55 )
                $_val = floor($_val);
        }
        
		return $_val ? $_val : 0;    
	}
	
	//
	// test를 위한 debug부분
	//
	function debug_ex( $str )
	{
	    if ( $this->m_debug == 1 )
	        echo( "debug mode> " . $str . "\n" );   
	    else
	        debug( $str . "\n" );
	}
	
	function debug_array_ex( $arr )
	{
	    if ( $this->m_debug == 1 )
	        print_r ( $arr );   
	}
	// 판매처별 선불 배송 개수(기준 송장번호)
	function get_usertrans_cnt( $data )
	{
	    global $connect;
	    $query = "select seq,pack from orders where seq=$data[seq]";
	    $result = mysql_query( $query, $connect );
	    $data   = mysql_fetch_assoc( $result );

        $cnt = 1;	    
        
	    if ( $data[pack] )
	    {
    	    $query = "select count(*) cnt from orders 
    	               where pack = $data[pack] 
    	                 and trans_who='선불'";

            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );    	                 
            $cnt    = $data[cnt];
        }
        return $cnt;
	}
	
	//
	// 주문의 개수
	//
	function get_order_cnt( $data )
	{
	    global $connect;
	    $query  = "select count(*) cnt from orders where order_id='$data[order_id]'";
	    $result = mysql_query( $query, $connect );
	    $_data   = mysql_fetch_assoc( $result );
	    return $_data[cnt];
	}
	
	//
	//  order_id_seq 의 개수
	//
	function get_order_cnt_seq( $data )
	{
	    global $connect;
	    $query  = "select count(*) cnt from orders where order_id_seq='$data[order_id_seq]'";
	    $result = mysql_query( $query, $connect );
	    $_data   = mysql_fetch_assoc( $result );
	    return $_data[cnt];   
	}
	
	//
	//  상품의 택배비 
	//
	function get_trans_fee( $product_id )
	{
	    global $connect;
	    
	    $query = "select trans_fee from products where product_id='$product_id'";
	    $result = mysql_query( $query, $connect );
	    $data   = mysql_fetch_assoc( $result );
	    
	    return $data[trans_fee];
	}
	
	//**********************************
    // 사용자 정의 값 check
    // 2009.8.10 - jk
    function check_user_defined( $data, $rule )
    {
        global $connect;
        
        $org_rule = $rule;
        
        $arr_return = array();
        $arr_return['is_set'] = 0;
        
        //if (preg_match('/^[\[](.*)[\]]$/', $rule, $match))
        // preg_match('/^[\[](.*)[\]]/', $rule, $match) 에서 변경 2012.9.10 - jkryu
        if (preg_match('/[\[](.*)[\]]/', $rule, $match))
        {
            $_val = split(":", $match[1]);
            
            if ( $_val[0] == "user_defined" )
            {
                $arr_return['is_set'] = 1;
                $arr_return['value']  = $_val[1];
            }
            else if ( $_val[0] == "products" || $_val[0] == "product" )
            {
                //debug("products: $data[seq]");
                
                // 사용자 정의값의 형태로 값이 셋팅되었음을 정의 함
                $arr_return['is_set'] = 1;              
                
                // arr_produts의 구조
                // [0] => array(product_id, qty), [1] => array(product_id, qty)                
                // product_id, supply_id, qty를 알 수 있다.
                $arr_products = class_order_products::get_arr_products( $data[seq] );
                
                // $_val1에는 정의 값이 들어감 [products:supply_price] 라 가정하면 
                // 03413
                // $_val1에는 supply_price가 들어감.
                $_code = $_val[1];
                $product_cnt = 0;
                foreach ( $arr_products as $_product )
                {
                    $rule = $org_rule;
                    //debug( "begin rule: $rule " );
                    
                    $product_cnt++; // 상품의 개수
                    
                    //debug( "Qty: " . $_product["qty"] );
                    
                    $arr_price = class_product::get_price_arr( $_product[product_id], $data[shop_id] , $data[collect_date]);                    
                    
                    debug("---$data[seq] / product_id: $_product[product_id]");
                    //debug("   supply_price: $arr_price[supply_price]");
                    //debug("   shop_price  : $arr_price[shop_price]");
                    
                    // trans_fee가 있는경우..
                    // 황실장 요청사항 - 2012.1.31 - jk
                    
                    if ( $_code == "trans_fee" )
                    {
                        $arr_price["trans_fee"] = $this->get_trans_fee( $_product[product_id] );  
                    }
                    
                    //debug ( "code: $_code" );
                    
                    $vv = $arr_price[ $_code ];
                    
                    // 추가 rule실행
                    // [rule] * 0.9 와 같은 형식이 되면 됨.
                    // 사용자 정의값은 [ ] 를 쳐야 함
                    //$_rule = preg_replace('/^[\[](.*)[\]]/',$arr_price[ $_code ], $rule);
                    //$_rule = preg_replace('/[\[](.*)[\]]/',$arr_price[ $_code ], $rule);
                    
                    // 값을 변경한다.
                    // shop_price
                    $arr_product_key = array("shop_price","org_price","supply_price","trans_fee");
                    
                    foreach($arr_product_key as $_product_key )
                    {
                        //debug("($_product_key):" . $arr_price[ $_product_key ] );
                        //debug( "before: " . $rule );
                        
                        $arr_price[ $_product_key ] = $arr_price[ $_product_key ] ? $arr_price[ $_product_key ] : 0;
                        $rule = str_replace("[products:" . $_product_key . "]", $arr_price[$_product_key], $rule );
                        
                        
                        //debug( "after: " . $rule );
                    }
                    
                    
                    //debug( "rule113: " . $rule );
                    
                    // rule을 사용한 계산 값 사용
        			$arr_code = split('[^a-z^0-9^_^.]', $rule);
        			$_arr = array();	
        
        			// filtering
        			// $_arr을 만들어 낸다.
        			// tot_products추가..2012.9.25 jkryu
        			// 공통로직 같지만 get_price와 user_defined는 미묘하게 다름.
        			// 두 번 작업 해야 한다.
        			foreach ( $arr_code as $key )
        			{
        			    if ( $key )
        			    {
        			        // 전체 상품        			        
        			        if ( $key == "cnt_order" )
        				    {
        				        $order_cnt  = $this->get_order_cnt( $data );   
        				        $_arr[trim($key)] = $order_cnt;
        				    }
            			    else             			    
            			    if ( $key == "tot_products" )
            			    {
            			        $_arr["tot_products"] = $this->get_tot_products( $data[seq] );
            			    }
            			    // 개별 상품 수
            			    else if ( $key == "per_products" )
            			    {
            			        $_arr["per_products"] = $_product[qty];
            			    }
            			    else
            				{
            			        $_arr[$key] = $key;
            			    }
        			    }
        			}	
        
                    // error fix - 2012.9.25 - jkryu
                    // tot_products추가..
        			foreach ( $_arr as $key => $value )
        			{
        			    //debug( "key: " . $key );
        			    
        			    if ( $key == "tot_products" || $key == "per_products" || $key == "cnt_order")
        			    {
        			        //$_rule = str_replace( "tot_products", $_arr["tot_products"], $_rule );
        			        $rule = str_replace( $key, $_arr[ $key ], $rule );
        			    }        			    
        			    else if ( !is_numeric ( $key ) )
        			        $rule = str_replace( $key, "\$data[" . $key ."]", $rule );
        			    
            			//
                        // mkh2009 위메이크프라이스 code11의 값은 무조건 $product_cnt가 1일때 한 번만 계산한다.
                        // 2013.12.10 - jkryu     
        			    if ( _DOMAIN_ == "mkh2009" && $key == "code11" && $product_cnt > 1)
                        {
                            $rule = 0;
                        }
        			}
                    
                    $str = "\$_val = " . $rule  . ";";
                    debug("check_user_defined:" . $str . " / qty: " . $data[qty] . "/seq: " . $data[seq] );
    			    eval( $str ); 
                    debug(" => value: " . $_val );
                    
                    $arr_return['value'] += $_val;
                }
            } 
            else if ( $_val[0] == "orders" )
            {
                // 사용자 정의값의 형태로 값이 셋팅되었음을 정의 함
                $arr_return['is_set'] = 1;
                $arr_return['value']  = $data[ $_val[1] ];
            } 
        }
        else
        {
            //debug( "no match:  $rule");   
        }
        //debug(" ---- tot: " . $arr_return['value'] . "---------------");
        
        return $arr_return;
    }
    
    //
    function get_tot_products( $seq )
    {
        global $connect;   
        
        $query = "select pack from orders where seq='$seq'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $seqs = $seq;
        
        // 합포일 경우
        if ( $data[pack] )
        {
            $seqs = "";
            $query = "select seq from orders where pack=$data[pack]";
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $seqs .= $seqs ? "," : "";
                $seqs .= $data[seq];   
            }  
        }
        
        // 개수
        // 취소는 따로 계산을 하기 때문에 일단은 총 금액 기준으로 처리한다.
        //$query = "select sum(qty) qty from order_products where order_seq in ( $seqs ) and order_cs not in (1,2,3,4)";
        $query = "select sum(qty) qty from order_products where order_seq in ( $seqs ) ";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[qty] ? $data[qty] : 0;
    }
    
	//***********************************
	//
	// 개수 return
	// 2009.6.30 - jk
	// 선결제 개수를 센다
	// 선불 개수를 센다
	//
    function get_count( $code, $data )
	{
		// rule이 있는지 여부 확인
	    $rule = $this->m_rule[$key];	
        
        if ( $rule )
        {        
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
    			
    			//debug( "get_count:". $str );
    			eval( $str );
    	    }
        }
        
        // 값이 있으면 1, 없으면 0
		return $_val ? 1 : 0;   
	}
}

?>
