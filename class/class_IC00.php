<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_ui.php";
require_once "class_multicategory.php";
require_once "class_category.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_IC00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고 조회
    function IC00()
    {
        global $template;

        $link_url = "?" . $this->build_link_url();     
        
        // 판매처별 상품 리스트를 가져온다 
        if ( $_REQUEST["page"] )
        {
         // 재고 처리
         $stock_option = 1;
         $result = class_C::get_product_supply_list( &$total_rows, $stock_option );
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    

    // 재고조정 데이터 삭제
    function init()
    {
        global $connect;
        class_stock::clear_template();
    }

    function upload()
    {
        $obj = new class_file();
        $arr_data =  $obj->upload();
        
        class_stock::clear_template();
        foreach ($arr_data as $data )
        {
            if ( $data[11] )
            {
                $_data = array( 
                    product_id     => $data[1]
                    ,name          => $data[2]
                    ,options       => $data[3]
                    ,current_stock => $data[5]
                    ,stock         => $data[10]
                    ,command       => $data[11]
                );   
                class_stock::insert_template( $_data );
            }
        }
        
        echo "
        <script language='javascript'>
        parent.load_data()
        </script>
        ";        
    }

    //******************************
    // 재고 적용
    function apply()
    {
        $arr_data = class_stock::get_stock_template();
        
        print_r ( $arr_data );
        
        $i = 0;
        $obj = new class_stock();
        
        foreach ( $arr_data['list'] as $data )
        {
            $command = mb_convert_case( $data['command'], MB_CASE_UPPER, "UTF-8");
            
            // 입고
            if ( $command == "A" )
            {
                $obj->stock_in( $data[product_id], $data[stock]);        
            }
            // 조정
            else if ( $command == "M" )
            {
                $obj->stock_arrange( $data[product_id], $data[stock]);        
            }
            // 출고
            else if ( $command == "D" )
            {
                $obj->stock_out( $data[product_id], $data[stock]);        
            }
            
            $i++;
            echo "
            <script language='javascript'>
            parent.show_txt('$i/" . $arr_data['cnt'] . " - " . $data['command'] . "/" . $data['stock'] . "')
            </script>
            ";  
        }   
        
        // 재고 테이블 초기화
        class_stock::clear_template();
        
        echo "
        <script language='javascript'>
        parent.hide_waiting()
        parent.load_data()
        </script>
        ";       
    }

    //******************************
    // template data 읽어온다.
    // 2009.7.31
    function load_template_data()
    {
        global $connect;
        
        $arr_data = array();
        
        // 총 개수
        $query  = "select count(*) cnt from stock_template where login_id='$_SESSION[LOGIN_ID]'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );        
        $arr_data['total_rows'] = $data['cnt'];
        
        // 200개에 대한 자료
        $query  = "select * from stock_template where login_id='$_SESSION[LOGIN_ID]' limit 200";
        $result = mysql_query( $query, $connect );
        
        while ( $data=mysql_fetch_assoc( $result )  )
        {
            $arr_data['list'][] = $data;   
        }
        echo json_encode($arr_data);
    }

    // 하위 상품 목록
    function expand()
    {
        global $connect,$product_id;
        $obj = new class_stock();
        
        $query = "select * from products where org_id='$product_id' and enable_stock=1";
        $result = mysql_query( $query, $connect );
        $arr_data = array();
        $arr_data['total'] = 0;
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data['total']++;
            $stock_info = $obj->get_stock( $data[product_id],"child" );
            
            $arr_data['list'][] = array( 
                supply_name       => ""
                ,product_id       => $data[product_id]
                ,product_name     => ""
                ,options          => $data[options]
                ,stock            => $stock_info[stock]
                ,yesterday_stock  => $stock_info[yesterday_stock]
                ,stock_in         => $stock_info[in]
                ,stock_out        => $stock_info[out]
                ,trans_cnt        => $stock_info[trans]
                ,org_price        => $data[org_price]
            );
        }
        echo json_encode($arr_data);
    }

    // get list
    function get_list( &$total_rows, $is_download=0 )
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $origin, $page, $cancel_trans,
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
               
               
        global $supply_tel, $supply_brand, $supply_options, $org_price,$str_supply_code;
        global $multi_supply_group, $multi_supply;
        
        global $group_id, $s_group_id, $shop_id, $supply_code;
		global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $category, $enable_sale;
        
        /////////////////////////
        // products 테이블
        $use_products = 0;
        $opt_products = "";

        /*
        if( $supply_code )
        {
            $opt_products .= ($opt_products?" and ":"") . " supply_code = '$supply_code' ";
            $use_products = 1;
        }
        */
        
        if($m_sub_category_1 + $m_sub_category_2 + $m_sub_category_3 > 0 )
        {
	        $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
	        if ( $arr_search_id[$m_sub_category_1] )
	            $opt_products .= ($opt_products?" and ":"") ."c.m_category1=" . $arr_search_id[$m_sub_category_1];
	        
	        if ( $arr_search_id[$m_sub_category_2] )
	            $opt_products .= ($opt_products?" and ":"") ."c.m_category2=" . $arr_search_id[$m_sub_category_2];
	        
	        if ( $arr_search_id[$m_sub_category_3] )
	            $opt_products .= ($opt_products?" and ":"") ."c.m_category3=" . $arr_search_id[$m_sub_category_3];
	        $use_products = 1;
	    }
	    
	    if( $category )
	    {
            $opt_products .=  ($opt_products?" and ":"") ."c.category = '$category' ";
            $use_products = 1;
        }
        
        if( $enable_sale )
        {
        	$opt_products .=  ($opt_products?" and ":"") ."c.enable_sale > 0 ";
            $use_products = 1;
        }
        
        if( $str_supply_code )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.supply_code in ( $str_supply_code) ";
            $use_products = 1;
        }
        
        if( $multi_supply )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.supply_code in ( $multi_supply) ";
            $use_products = 1;
        }
        
        if ( $group_id )
        {
            $shop_id = $this->get_group_shop( $group_id );      
            $use_products = 1;
        }
        
        if ( $shop_id )
        {
            $opt_products .= ($opt_products?" and ":"") ." a.shop_id in ( $shop_id ) ";
            $use_products = 1;
        }
        
        if( $product_id )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.product_id = '$product_id' ";
            $use_products = 1;
        }

        // 본상품 제외 & 삭제상품 제외
        if( true )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.is_represent=0 and is_delete=0 ";
            $use_products = 1;
        }
        
        if( $name )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.name like '%$name%' ";
            $use_products = 1;
        }

        if( $options )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.options like '%$options%' ";
            $use_products = 1;
        }
        
        if( $origin )
        {
            $opt_products .= ($opt_products?" and ":"") . " c.origin like '%$origin%' ";
            $use_products = 1;
        }
        
        
        // enable stock 추가
        // 2009.1.6
        $opt_products .= ($opt_products?" and ":"") . " c.enable_stock=1 ";
        
        // products query
        if( $use_products )
        {
        	if ( $shop_id || $m_sub_category_1 + $m_sub_category_2 + $m_sub_category_3 > 0  || $category )
        		$query_products = "select c.product_id product_id, c.stock_alarm1 stock_alarm1, c.stock_alarm2 stock_alarm2 from products c left outer join order_products b ON c.product_id = b.product_id left outer join orders a on b.order_seq = a.seq where $opt_products group by c.product_id";
        	else
            	$query_products = "select product_id, stock_alarm1, stock_alarm2 from products c where $opt_products ";

            // products 임시테이블
            $t = gettimeofday();
            $products_temp_table = "products_temp_" . substr($t[sec],-3) . $t[usec];
debug( "products_temp_table : " . $query_products );
            mysql_query("create table $products_temp_table (primary key (product_id)) engine=memory $query_products", $connect );
        }
        
        /////////////////////////
        // current_stock 테이블
        $use_current_stock = 0;
        $opt_current_stock = "";
        if( $stock_start )
        {
            $opt_current_stock .= ($opt_current_stock?" and ":" having ") . " stock >= '$stock_start' ";
            $use_current_stock = 1;
        }
        
        if( $stock_end )
        {
            $opt_current_stock .= ($opt_current_stock?" and ":" having ") . " stock <= '$stock_end' ";
            $use_current_stock = 1;
        }

        // stock_status
        if( $stock_status )
            $use_current_stock = 1;

        // current_stock query
        if( $use_current_stock )
        {
            $query_current_stock = "select product_id, sum(stock) as stock from current_stock 
                                     where bad = $stock_type 
                                     group by product_id $opt_current_stock";

            // current_stock 임시테이블
            $t = gettimeofday();
            $current_stock_temp_table = "current_stock_temp_" . substr($t[sec],-3) . $t[usec];
//debug( "current_stock_temp_table : " . $query_current_stock );
            mysql_query("create table $current_stock_temp_table (primary key (product_id)) engine=memory $query_current_stock", $connect );
        }
        
        /////////////////////////
        // orders 테이블 - 미배송
        $use_notrans = 0;
        if( $notrans_day || $notrans_cnt || $stock_status == 3 )
        {
            $use_notrans = 1;
            $query_notrans = "select order_products.product_id, sum(order_products.qty) sum from orders, order_products 
                              where orders.status in (1,7) and order_products.order_cs not in (1,2,3,4) ";
            if( $notrans_day )
                $query_notrans .= " and collect_date<=date_sub(now(), interval $notrans_day day)";
            
            $query_notrans .= " and orders.seq=order_products.order_seq group by order_products.product_id ";
            
            if( $notrans_cnt )
                $query_notrans .= " having sum >= $notrans_cnt";

            // orders 임시테이블
            $t = gettimeofday();
            $orders_temp_table = "orders_temp_" . substr($t[sec],-3) . $t[usec];
//debug( "orders_temp_table : " . $query_notrans );
            mysql_query("create table $orders_temp_table (primary key (product_id)) engine=memory $query_notrans", $connect );
        }

        /////////////////////////
        // stock_tx 테이블
        $use_stock_tx = 0;
        if( $work_start || $work_end )
        {
        	$use_stock_tx = 1;
        	
        	if($work_type == "collect")
        	{
        		$query_stock_tx = "SELECT b.product_id, sum(b.qty) qty from orders a, order_products b where a.seq = b.order_seq ";
        		$query_stock_tx .= "and a.collect_date >= '$start_date' and a.collect_date <= '$end_date'";
        		
  				$_data = mysql_fetch_assoc(mysql_query("select * from stat_config where code='change'", $connect));
  				
  				switch($_data[value])
  				{
  					case "org":
  						$query_stock_tx .= " and a.c_seq =0 ";
  					break;	
  					
  					case "new":
  						$query_stock_tx .= " and a.order_cs NOT IN (7,8) ";
  					break;
  				}
  				
        		$query_stock_tx .= " group by b.product_id having ";
        	}
        	else 
        	{
	        	$query_stock_tx = "select product_id, sum($work_type) as qty from stock_tx 
	                               where crdate >= '$start_date' and crdate <= '$end_date' and bad='$stock_type' ";
	            $query_stock_tx .= " group by product_id having";
        	}   
        	
            if( $work_start )
            {
                $query_stock_tx .= " qty >= $work_start ";
                if( $work_end )
                    $query_stock_tx .= " and qty <= $work_end ";
            }            
            else if( $work_end )
                $query_stock_tx .= " qty <= $work_end ";
                
            // stock_tx 임시테이블
            $t = gettimeofday();
            $stock_tx_temp_table = "stock_tx_temp_" . substr($t[sec],-3) . $t[usec];
debug( "stock_tx_temp_table : " . $query_stock_tx );
            mysql_query("create table $stock_tx_temp_table (primary key (product_id)) engine=memory $query_stock_tx", $connect );
        }

        //////////////////////
        // 전체쿼리 만들기
        if( $use_products || $use_current_stock || $use_notrans || $use_stock_tx )
        {
            $select_opt = "";
            $from_opt = "";
            $where_opt = "";
            
            // select 
            if( $use_products ) 
                $select_opt = " a.product_id as product_id ";
                
            if( !$select_opt && $use_current_stock )
                $select_opt = " b.product_id as product_id ";

            if( !$select_opt && $use_notrans ) 
                $select_opt = " c.product_id as product_id ";

            if( !$select_opt && $use_stock_tx ) 
                $select_opt = " d.product_id as product_id ";

            // from
            if( $use_products ) 
                $from_opt = " $products_temp_table a ";
                
            if( $use_current_stock )
                $from_opt .= ($from_opt?",":"") . " $current_stock_temp_table b ";

            if( $use_notrans ) 
                $from_opt .= ($from_opt?",":"") . " $orders_temp_table c ";

            if( $use_stock_tx ) 
                $from_opt .= ($from_opt?",":"") . " $stock_tx_temp_table d ";
            
            // where
            if( $use_products + $use_current_stock + $use_notrans + $use_stock_tx > 1 )
            {
                if( $use_products ) 
                {
                    if( $use_current_stock )
                        $where_opt .= ($where_opt?" and ":"") . " a.product_id = b.product_id ";
                    if( $use_notrans )
                        $where_opt .= ($where_opt?" and ":"") . " a.product_id = c.product_id ";
                    if( $use_stock_tx )
                        $where_opt .= ($where_opt?" and ":"") . " a.product_id = d.product_id ";
                }
                else if( $use_current_stock )
                {
                    if( $use_notrans )
                        $where_opt .= ($where_opt?" and ":"") . " b.product_id = c.product_id ";
                    if( $use_stock_tx )
                        $where_opt .= ($where_opt?" and ":"") . " b.product_id = d.product_id ";
                }
                else if( $use_notrans )
                {
                    if( $use_stock_tx )
                        $where_opt .= ($where_opt?" and ":"") . " b.product_id = d.product_id ";
                }
                
                // 재고 상태 - 경고수량
                if( $stock_status == 1 )
                    $where_opt .= ($where_opt?" and ":"") . "a.stock_alarm2 < b.stock and b.stock <= a.stock_alarm1 ";
                // 재고 상태 - 위험수량
                else if( $stock_status == 2 )
                    $where_opt .= ($where_opt?" and ":"") . " b.stock <= a.stock_alarm2 ";
                // 재고 상태 - 재고부족
                else if( $stock_status == 3 )
                    $where_opt .= ($where_opt?" and ":"") . " b.stock < c.sum ";
                
                $where_opt = " where " . $where_opt;
            }
            $query = "select $select_opt from $from_opt $where_opt order by product_id";
            
        }
        else
        {
            $query = "select product_id from products";
        }
            
debug( "[stock_query] : " . $query );
        $result = mysql_query( $query, $connect );
        if( !$is_download )
        {
            $page  = $page ? $page : 1;
            $limit = 30;
            $start = ($page - 1) * $limit;
            $condition_page = " limit $start, $limit";
        
            $total_rows = mysql_num_rows($result);
            $result = mysql_query( $query . $condition_page, $connect );
        }   

        if( $is_download )
            ini_set("memory_limit", "400M");

        $_arr   = array();
        $i      = 0;
        $_arr['total'] = $total_rows;
/*
        // 송장 임시테이블
        $t = gettimeofday();
        $trans_ready_table = "trans_ready_" . substr($t[sec],-3) . $t[usec];
        mysql_query("create table $trans_ready_table engine=memory
                     select b.product_id as product_id,
                            sum(b.qty) as qty
                       from orders a use index (orders_idx22),
                            order_products b use index (order_products_idx7)
                      where a.status=7 and
                            b.order_cs not in (1,2,3,4) and
                            a.seq = b.order_seq
                   group by b.product_id", $connect );
        
        // 접수 임시테이블
        $t = gettimeofday();
        $before_trans_table = "before_trans_" . substr($t[sec],-3) . $t[usec];
        mysql_query("create table $before_trans_table engine=memory
                     select b.product_id as product_id,
                            sum(b.qty) as qty
                       from orders a use index (orders_idx22),
                            order_products b use index (order_products_idx7)
                      where a.status=1 and
                            b.order_cs not in (1,2,3,4) and
                            a.seq = b.order_seq
                   group by b.product_id", $connect );
*/
        // 원가조회불가
        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
            $enable_org_price = true;
        else 
            $enable_org_price = false;

        $obj = new class_stock();        
        while ( $data = mysql_fetch_array( $result ) )
        {   
            $i++;
            // 상품정보
            $product_info = class_product::get_info( $data[product_id] );

            // 과거재고-정상
            $query_stock = "select * from stock_tx where product_id='$data[product_id]' and crdate <= '$end_date' and bad=0 order by crdate desc limit 1";
            $result_stock = mysql_query($query_stock, $connect);
            if( mysql_num_rows($result_stock) )
            {
                $data_stock = mysql_fetch_assoc($result_stock);
                $stock1 = $data_stock[stock];
            }
            else 
                $stock1 = 0;
            
            // 과거재고-불량
            $query_stock = "select * from stock_tx where product_id='$data[product_id]' and crdate <= '$end_date' and bad=1 order by crdate desc limit 1";
            $result_stock = mysql_query($query_stock, $connect);
            if( mysql_num_rows($result_stock) )
            {
                $data_stock = mysql_fetch_assoc($result_stock);
                $stock2 = $data_stock[stock];
            }
            else 
                $stock2 = 0;
/*            
            // 송장
            $query = "select qty from $trans_ready_table where product_id='$data[product_id]'";
            $result_sum = mysql_query($query, $connect);
            $data_sum = mysql_fetch_assoc($result_sum);
            $trans_ready = $data_sum[qty];
                             
            // 접수
            $query = "select qty from $before_trans_table where product_id='$data[product_id]'";
            $result_sum = mysql_query($query, $connect);
            $data_sum = mysql_fetch_assoc($result_sum);
            $before_trans = $data_sum[qty];
*/

/*            
            // 배송예정 - jk 2009-12-9
            $query       = "select sum(qty) qty from print_enable where product_id='$data[product_id]' and status=3";
            $result_sum  = mysql_query($query, $connect);
            $data_sum    = mysql_fetch_assoc($result_sum);
            $ready_trans = $data_sum[qty];
*/            
            
			//입고, 반품입고, 출고
            $query = "select sum(stockin) stockin,sum(stockout) stockout, sum(retin) retin, sum(retout) retout, sum(trans) trans 
                        from stock_tx 
                       where product_id='$data[product_id]' 
                         and crdate >= '$start_date'     
                         and crdate <= '$end_date'
                         and bad = '$stock_type'";
                         
			$_result = mysql_query( $query, $connect );
			$_data   = mysql_fetch_assoc( $_result );
			

			if($group_id || $shop_id)
			{
				//배송
	            $query = "SELECT a.job, sum(a.qty) qty 
	            		    FROM stock_tx_history a 
	            		       , orders b 
	            		   WHERE a.order_seq = b.seq 
	            		   	 AND a.product_id = '$data[product_id]' 
	            		   	 AND a.crdate>='$start_date 00:00:00'  
	                         AND a.crdate<='$end_date 23:59:59' 
	                         AND a.bad = '$stock_type' 
	                         AND a.job = 'trans' ";
	                         
				if( $group_id )
				    $shop_id = $this->get_group_shop( $group_id );
				
				if( $shop_id )
				    $query .= "AND b.shop_id in ( $shop_id ) ";
				    
				$query .= " GROUP BY a.job";
				
	            $_result = mysql_query( $query, $connect );
	            
	            while($_temp   = mysql_fetch_assoc( $_result ))
	            	$_data[$_temp[job]] = $_temp[qty];
	        }

			//발주
    		$query = "SELECT b.product_id, sum(b.qty) collect from orders a, order_products b where a.seq = b.order_seq ";
    		$query .= "and a.collect_date >= '$start_date' and a.collect_date <= '$end_date'";
    		$query .= "and b.product_id='$data[product_id]' ";
    		
			if( $shop_id )
			    $query .= "AND a.shop_id in ( $shop_id ) ";
			    
			$__data = mysql_fetch_assoc(mysql_query("select * from stat_config where code='change'", $connect));			
			switch($__data[value])
			{
				case "org":
					$query .= " and a.c_seq =0 ";
				break;	
				
				case "new":
					$query .= " and a.order_cs NOT IN (7,8) ";
				break;
			}
			
    		$query .= " group by b.product_id";
			$__result = mysql_query( $query, $connect );
            $__data   = mysql_fetch_assoc( $__result );
            
            
global $check_option;            
            $arr_supply_info = class_supply::get_info($product_info[supply_code] );
            
            $temp_arr = array( 
                supply_name      => $arr_supply_info[name]
                ,product_id      => $data[product_id]
                ,product_name    => $product_info[name]
                ,brand           => $product_info[brand]
                ,options         => $product_info[options]
                ,current_stock     => $obj->get_current_stock($data[product_id], 0)
                ,current_bad_stock => $obj->get_current_stock($data[product_id], 1)
                ,stock           => ($stock1?$stock1:0)
                ,bad_stock       => ($stock2?$stock2:0)
                ,stockin         => $_data[stockin] ? $_data[stockin]  : 0
                ,stockin_price   => number_format( ( $_data[stockin] ? $_data[stockin]  : 0 ) * $product_info[org_price] )
                ,retin           => $_data[retin]   ? $_data[retin]    : 0
                ,retin_price     => number_format( ( $_data[retin]? $_data[retin] :0 ) * $product_info[org_price] )                
                ,stockout        => $_data[stockout]? $_data[stockout] :0
                ,stockout_price  => number_format( ( $_data[stockout]? $_data[stockout] :0 ) * $product_info[org_price] )
                ,retout           => $_data[retout]   ? $_data[retout]    : 0
                ,retout_price     => number_format( ( $_data[retout]? $_data[retout] :0 ) * $product_info[org_price] )
                ,collect         => $__data[collect] ? $__data[collect]    : 0
                ,collect_price   => number_format( ( $__data[collect]? $__data[collect] :0 ) * $product_info[org_price] )
                ,trans           => $_data[trans]   ? $_data[trans]    : 0
                ,trans_price     => number_format( ( $_data[trans]? $_data[trans] :0 ) * $product_info[org_price] )
                ,org_price       => number_format( $product_info[org_price] )
                ,address         => $arr_supply_info[address1] . $arr_supply_info[address2]
                ,supply_brand    => $product_info[brand]
                ,barcode         => $product_info[barcode]
            );

            if( !$enable_org_price )
                unset( $temp_arr[org_price] );

            $_arr['list'][] = $temp_arr;

            
            if ( $i % 100 == 0)
            {
                echo "
                <script language='javascript'>
                parent.show_txt( $i )
                </script>
                ";
                flush();
            }
        }
//debug( "delete stock temp table start" );
        // 임시 테이블 삭제
        mysql_query( "drop table if exists $products_temp_table");
        mysql_query( "drop table if exists $current_stock_temp_table");
        mysql_query( "drop table if exists $orders_temp_table");
        mysql_query( "drop table if exists $stock_tx_temp_table");
//        mysql_query( "drop table if exists $trans_ready_table");
//        mysql_query( "drop table if exists $before_trans_table");
//debug( "delete stock temp table end" );
        return $_arr;
    }


    // 
    // 해당 그룹의 판매처 리스트..
    function get_group_shop( $group_id )
    {
        global $connect,$shop_id;
        
        $query = "select shop_id from shopinfo where group_id=$group_id"; 
        
        if ( $shop_id )
            $query .= " and shop_id=$shop_id";

        $result = mysql_query( $query, $connect );
        $shop_ids = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_ids .= $shop_ids ? "," : "";
            $shop_ids .= $data[shop_id];
        }
        
debug("shop_ids : " . $shop_ids );
        return $shop_ids;
    }

    // search
    // 2009.7.27
    function search()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $origin, $cancel_trans,
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock,
               $group_id, $s_group_id, $shop_id, $supply_code;
		global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $category, $enable_sale;
        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();
        
        // 공통 모듈에서 data 가져온다.
        $total_rows = 0;
        $_arr = $this->get_list( &$total_rows );
        
        $_json         = json_encode( $_arr );
        
        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "기간별재고조회[$start_date ~ $end_date]");

        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
        </script>
        ";
    }   


   function get_shop_name( $shop_id )
   {
      return class_C::get_shop_name($shop_id);
   }

    //****************************
    // show detail
    // date: 2009.7.29 - jk
    function show_detail()
    {
        global $product_id,$is_org;
        
        $obj_stock = new class_stock();        
        $arr_data  = array();
        
        /*
        $arr_data['product']    = array( name=>'xxx', options=>'bbb');
        $arr_data['stock_tx'][] = array( date=>'2009-7-29', stock=>'4', in=>'3',out=>2,trans=>4);
        $arr_data['stock_tx'][] = array( date=>'2009-7-27', stock=>'4', in=>'3',out=>2,trans=>4);
        
        $arr_data['history'][] = array( date=>'2009-7-27', time=>'13:00:00', job=>'in', qty=>'4',owner=>'aa');
        $arr_data['history'][] = array( date=>'2009-7-27', time=>'13:00:00', job=>'in', qty=>'2',owner=>'aa');
        */
        
        // product name, options
        $info = class_product::get_info( $product_id );
        $arr_data['product']    = array( name=> $info['name'], options=>$info['options']);
        
        // stock_tx list
        $arr_data['stock_tx']   = $obj_stock->stock_tx_list($product_id,$is_org);
        
        // stock_tx_history
        $arr_data['history'] = $obj_stock->get_tx_history($product_id,$is_org);
                
        echo json_encode( $arr_data );
    }
    
    // stock_tx_history 조회
    function get_tx_history()
    {
        global $product_id, $page, $start_date, $end_date,$connect,$show_trans;
        
        $arr_result = array();
        
        // count
        $query = "select count(*) cnt from stock_tx_history 
                   where crdate >= '$start_date 00:00:00'
                     and crdate <= '$end_date 23:59:59'
                     and product_id = '$product_id'";
             
        if ( $show_trans != 1 )
        {
            $query .= " and job <> 'trans'";               
        }
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );        
        $arr_result[total_rows] = $data[cnt];
        
        // list
        $start = ($page - 1) * 30 ;
        $query = "select * from stock_tx_history 
                   where crdate >= '$start_date 00:00:00'
                     and crdate <= '$end_date 23:59:59'
                     and product_id = '$product_id'";
                     
        if ( $show_trans != 1 )
        {
            $query .= " and job <> 'trans'";               
        }
                 
        $query .= " order by seq desc limit $start, 30";
        $result = mysql_query( $query, $connect );
        
        $arr_code = array( arrange => "재고조정",out => "출고",trans => "배송",retin => "반품입고",in => "입고");
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data["job"] = $arr_code[$data[job]];
            $arr_result['list'][] = $data;   
        }
        
        echo json_encode( $arr_result );
    }
    
    //****************************
    // show detail
    // date: 2009.7.29 - jk
    function show_detail2()
    {
        global $product_id,$is_org;
        
        $obj_stock = new class_stock();        
        $arr_data  = array();
        
        // product name, options
        $info = class_product::get_info( $product_id );
        $arr_data['product']  = array( name=> $info['name'], options=>$info['options']);
        
        $_loc = $obj_stock->get_location($product_id);
        $arr_data['location'] = $_loc['list'];
        
        // stock_tx list
        $arr_data['stock_tx'] = $obj_stock->stock_tx_location($product_id);
        
        echo json_encode( $arr_data );
    }
    
    //*******************************
    // 입고
    // 
    function stock_in()
    {
        global $product_id,$qty,$location;
        
        
        $obj = new class_stock();        
        $obj->stock_in($product_id,$qty,$location);
        
    }
    
    //*******************************
    // 출고
    // 
    function stock_out()
    {
        global $product_id,$qty,$location;
        
        $obj = new class_stock();
        
        $obj->stock_out($product_id,$qty,$location);
        
    }
    //*******************************
    // 조정
    // 
    function stock_arrange()
    {
        global $product_id,$qty,$location;
        
        $obj = new class_stock();
        $obj->stock_arrange($product_id,$qty,$location);
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options , $origin, 
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;

		global $multi_supply_group, $multi_supply;
		global $group_id, $s_group_id, $shop_id, $supply_code, $str_supply_code;
		global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $category, $enable_sale;
        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();
        
        // get list from common module
        $is_download = 1;
        $_arr = $this->get_list( &$total_rows, $is_download );        
    
        $fn = $this->make_file( $_arr['list'] );

        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "기간별재고조회다운로드[$start_date ~ $end_date]");

        echo "<script language='javascript'>parent.set_file('$fn')</script>";      
    }
    
    function make_file( $arr_datas )
    {                
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
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
          
         global $check_option, $stock_status, $end_date;

        $today_stock_title = "현재정상재고";
        $today_bad_stock_title = "현재" . $_SESSION[EXTRA_STOCK_TYPE] . "재고";
        $search_stock_title = "조회일(" . $end_date . ")정상재고";
        $search_bad_stock_title = "조회일(" . $end_date . ")" . $_SESSION[EXTRA_STOCK_TYPE] . "재고";

        $_arr = array(
            "공급처"
            ,"상품코드"
            ,"상품명"
            ,"사입처상품명"
            ,"옵션"
            ,$today_stock_title
            ,$today_bad_stock_title
            ,$search_stock_title
            ,$search_bad_stock_title
            ,"입고"
            ,"입고금액"
            ,"반품입고"
            ,"반품금액"
            ,"출고"
            ,"출고금액"
            ,"반품출고"
            ,"반품금액"
            ,"발주"
            ,"발주금액"
            ,"배송"
            ,"배송금액"
            ,"원가"
            ,"주소"
            ,"공급처상품명"
            ,"바코드");

        if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) !== FALSE )
            unset($_arr[13]);
        
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
            $style2 = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $key == 'product_id' || $key == 'product_name' )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
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
        
        if( !$new_name )  $new_name = "stock_data.xls";
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }    

    //////////////////////////////////////
    // 재고이력 다운로드 - 파일 만들기
    function save_file2()
    {
        global $template, $connect;
        global $product_id, $bad;
        
        // 재고이력 구하기
        $info_arr = array(
            product_id => $product_id,
            bad        => $bad
        );
        $obj = new class_stock();

        // 이력
        $_arr = $obj->get_date_stock_download($product_id, $bad);
        // 합
        $stock_all = $obj->get_sum( $info_arr );
        $_arr[] = array(
            crdate  => "합",
            in      => ( $stock_all[$bad][in     ] ? $stock_all[$bad][in     ] : "" ),
            out     => ( $stock_all[$bad][out    ] ? $stock_all[$bad][out    ] : "" ),
            trans   => ( $stock_all[$bad][trans  ] ? $stock_all[$bad][trans  ] : "" ),
            arrange => ( $stock_all[$bad][arrange] ? $stock_all[$bad][arrange] : "" ),
            retin   => ( $stock_all[$bad][retin  ] ? $stock_all[$bad][retin  ] : "" ),
            retout  => ( $stock_all[$bad][retout ] ? $stock_all[$bad][retout ] : "" ),
            stock   => "-"
        );
        
        $fn = $this->make_file2( $_arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file2( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
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
 
        $_arr = array(
            "날짜"
            ,"입고"
            ,"출고"
            ,"배송"
            ,"조정"
            ,"반품입고"
            ,"반품출고"
            ,"재고"
        );
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
                $buffer .= "<td style='$style'>" . $value . "</td>";
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 미배송주문 다운로드 - 파일 만들기
    function save_file3()
    {
        global $template, $connect;
        global $product_id, $status;
        
        // 주문정보
        $query = "select a.seq as seq, 
                         a.collect_date as collect_date, 
                         a.shop_id as shop_id, 
                         a.recv_name as recv_name,
                         a.recv_tel as recv_tel,
                         a.recv_mobile as recv_mobile,
                         b.qty as qty
                    from orders a, order_products b
                   where a.status = $status and
                         b.product_id = '$product_id' and
                         b.order_cs not in (1,2,3,4) and
                         a.seq = b.order_seq";
        $result = mysql_query($query, $connect);
        
        $orders = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $orders[] = array(
                seq          => $data[seq],
                collect_date => $data[collect_date],
                shop_name    => class_shop::get_shop_name($data[shop_id]),
                recv_name    => $data[recv_name],
                tel          => $data[recv_tel] . " / " . $data[recv_mobile],
                qty          => $data[qty]
            );
        }
        
        $fn = $this->make_file3( $orders );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file3( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_order_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        $_arr = array(
            "관리번호"
            ,"발주일"
            ,"판매처"
            ,"고객명"
            ,"연락처"
            ,"수량"
        );
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
                $buffer .= "<td style='$style'>" . $value . "</td>";
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    // 재고 작업 팝업에서 일반정보를 가져온다
    function get_stock_info()
    {
        global $connect, $template, $product_id;
        
        $val = array();
        
        $product_info = class_product::get_info( $product_id );
        $val['supply'] = class_supply::get_name( $product_info[supply_code] );
        $val['name'] = $product_info[name];
        $val['options'] = $product_info[options];        
        
        $obj = new class_stock();
        $input_info = array( product_id => $product_id );
        $stock_info = $obj->get_current_stock_all( $input_info );

        $stock1 = 0; // 정상재고
        $stock2 = 0; // 불량재고
        foreach( $stock_info as $loc )
        {
            foreach( $loc as $key => $value )
            {
                if( $key == 0 && $value[success] == 1 )  $stock1 += $value[stock];
                if( $key == 1 && $value[success] == 1 )  $stock2 += $value[stock];
            }
        }
        
        $val['stock'] = $stock1;
        $val['bad_stock'] = $stock2;
        
        echo json_encode($val);
    }
    
    // 재고 작업 실행
    function set_stock_data()
    {
        global $connect, $template, $product_id, $bad, $type, $qty, $memo;
        
        // input parameter
        $info_arr = array(
            type       => $type,
            product_id => $product_id,
            bad        => $bad,
            location   => 'Def',
            qty        => $qty,
            memo       => $memo
        );

        $obj = new class_stock();
        $obj->set_stock($info_arr);
    }

    // chart를 그리기 위한 재고 이력 조회
    function get_stock_history()
    {
        global $connect, $product_id, $start_date, $end_date;
        
        $chart_title1 = iconv('utf-8','cp949',"재고");
        $chart_title2 = iconv('utf-8','cp949',"입고");
        $chart_title3 = iconv('utf-8','cp949',"배송");
        $chart_title4 = iconv('utf-8','cp949',"미배송");
        
        echo "<chart bgColor            = 'F7F7F7, E9E9E9' 
                     showValues         = '0' 
                     numVDivLines       = '10' 
                     divLineAlpha       = '30'  
                     labelPadding       = '10' 
                     yAxisValuesPadding = '10' >";

        //=====================================================        
        // date 부분 category 생성
        $_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);
        $_start    = round( abs(strtotime(date('y-m-d'))-strtotime($end_date)) / 86400, 0 );
        $_interval = $_start + $_interval;

        echo "<categories>";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('d', strtotime("-$i day"));
                echo "<category label='$_date' />\n ";
            }
        }
        echo "</categories>";

        //////////////////////////////////////////////////////////
        // data 생성
        $_tot_deliv = 0;  // 총 배송
        $_tot_input = 0;  // 총 입고
        $query = "select crdate, 
                         sum(stock) as stock,
                         sum(trans) as trans,
                         sum(stockin) as input
                    from stock_tx 
                   where crdate     >= '$start_date' and
                         crdate     <= '$end_date' and
                         product_id = '$product_id' and
                         bad        = 0
                group by crdate";
        $result = mysql_query($query, $connect);
        $dataset = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $dataset[$data[crdate]]['stock'] = $data[stock];
            $dataset[$data[crdate]]['trans'] = $data[trans];
            $dataset[$data[crdate]]['input'] = $data[input];
            $_tot_deliv += $data[trans];
            $_tot_input += $data[input];
        }

        // 재고
        echo "<dataset seriesName='$chart_title1' color='daa530' >\n";
        if ( $_interval >= 0 )
        {
            $_last_val = 0;
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['stock'] ? $dataset[$_date]['stock'] : $_last_val;
                $_last_val = $_val;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 입고
        echo "<dataset seriesName='$chart_title2' color='3cd371'>\n";
        if ( $_interval >= 0 )
            {
            $_last_val = 0;
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['input'] ? $dataset[$_date]['input'] : 0;
                $_last_val = $_val;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 배송
        echo "<dataset seriesName='$chart_title3' color='00afff'>\n";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['trans'] ? $dataset[$_date]['trans'] : 0;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 미배송
        $query = "select a.collect_date as collect_date,
                         count(b.qty) as qty
                    from orders a,
                         order_products b
                   where a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and
                         b.product_id='$product_id' and
                         a.status in (1,7 )                  
                         b.order_cs not in (1,2,3,4)                  
                group by a.collect_date";
        $result = mysql_query ( $query, $connect );
        $dataset = array();
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[collect_date]] = $data[qty];
        }

        $sum = 0;
        echo "<dataset seriesName='$chart_title4' color='dc143c'>\n";
        if ( $_interval >= 0 )
            {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                if( $dataset[$_date] )
                     $sum = $sum + $dataset[$_date];
                else
                     $sum = $sum;
                echo "<set value='$sum' />\n ";
            }
        }
        echo "</dataset>\n";
        echo "</chart>";
    }
    
    // 입고, 배송 평균 구하기
    function get_stock_average()
    {
        global $connect, $product_id, $start_date, $end_date;
        
        $query = "select sum(trans) as trans,
                         sum(stockin) as stockin
                    from stock_tx 
                   where crdate     >= '$start_date' and
                         crdate     <= '$end_date' and
                         product_id = '$product_id' and
                         bad        = 0";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc($result);
        
        // 날짜 차이 구하기
        $arr_start = explode('-', $start_date);
        $arr_end = explode('-', $end_date);
        
        $start_time = mktime(0,0,0,$arr_start[1],$arr_start[2],$arr_start[0]);
        $end_time = mktime(0,0,0,$arr_end[1],$arr_end[2],$arr_end[0]);
        
        $date_sub = NUMBER_FORMAT(intval(($end_time-$start_time)/86400)) + 1;
        
        $val['stockin'] = NUMBER_FORMAT($data[stockin] / $date_sub);
        $val['trans'] = NUMBER_FORMAT($data[trans] / $date_sub);
        
        echo json_encode($val);
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

}
?>