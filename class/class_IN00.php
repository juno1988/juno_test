<?
/*
name: class_IN00
date: 2012-8-16 
author: jkryu
*/

// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
require_once "class_ui.php";
require_once "class_cafe24.php";        // 2012.3.6 jk추가
require_once "class_godo.php";          // 2013.10.24 cy추가


class class_IN00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고 조회
    function IN00()
    {
        global $template, $connect;
        $link_url = "?" . $this->build_link_url();     
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 로그 이력
    function IN01()
    {
        global $template, $connect;
        $link_url = "?" . $this->build_link_url();     
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
   
    // 동기화 이력
    function search_history()
    {
        global $connect, $query_str, $start_date,$end_date;
        
        $query = "select a.* 
                    from stock_sync_log a,
                         products       b
                   where a.product_id = b.product_id
                     and a.crdate     >= '$start_date 00:00:00'
                     and a.crdate     <= '$end_date 23:59:59'";
        
        if ( $query_str )
        {
            $query .= " and b.name like '%$query_str%'";   
        }
                     
        //$query .= " order by crdate desc limit 2000";   
        
        debug( "get p: $query " );
        
        $result = mysql_query( $query,$connect );
        $arr = array();
        $i   = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $i++;
            $pinfo = $this->get_pinfo( $data['product_id'] );
            
            $enable_sale = "&nbsp;";
            
            if ( $pinfo["enable_sale"] == 0 )
                $enable_sale = "<span class=red>품절</span>";
            
            $arr[] = array(
                        no            => $i 
                        ,crdate        => $data['crdate']
                        ,enable_sale  => $enable_sale
                        ,product_id   => $data['product_id']
                        ,link_id      => $data['link_id']
                        ,product_name => $pinfo['name']
                        ,options      => $pinfo['options']
                        ,stock        => $data['stock']
                        ,worker       => $data['worker'] ? $data['worker'] : "&nbsp;"
                        
            );
        }
        
        echo json_encode( $arr );
    }
   
    function get_pinfo( $product_id )
    {
        global $connect;
        
        $query = "select name,options,enable_sale from products where product_id = '$product_id'";
        
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        
        return $data;   
    }
   
	function get_shop_list()
	{
		global $connect, $type;

		if ( $type == "cafe24" )
			$code = "72, 73" ;
		else if ( $type == "godo" )
			$code = "29, 30, 58";

		$query = "select * from shopinfo 
				   where shop_id % 100 in ( $code )
					 and disable = 0 ";

        debug( $query );

		$result = mysql_query( $query, $connect );
		if ( mysql_num_rows( $result ) > 0 )
		{
			while ( $data = mysql_fetch_assoc ( $result ) )
			{
				$shop_list[$data["shop_id"]] = $data["shop_name"];
			}
		}
		else
			$shop_list[""] = "판매처 없음";

		echo json_encode ( $shop_list );
	}
 
    function stock_sync()
    {
        global $connect, $supply_id, $shop_id, $query_type, $query_str,$stock_type,$except_soldout,$start_date,$end_date, $type;
        
        // 2개월 지난 주문은 로그를 삭제 한다. - 2014.11.24
        $_dd = Date("Y-m-d",strtotime("-2 months"));
        $query = "select count(*) cnt from stock_sync_log where crdate <='$_dd'";
        
        debug("[stock_sync]" . $query );
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[cnt] > 0 )
        {
            $query = "delete from stock_sync_log where crdate <='$_dd'";   
            mysql_query( $query, $connect );
        }
        
        
        debug( "sync type: $type" );
        
        $arr_result = $this->search_core();
        
        echo "<script language=javascript>parent.show_waiting()</script>";   
        
        //print_r ( $arr_result );
        
        if ( count( $arr_result ) == 0 )
        {
            echo "<script language=javascript>parent.alert('동기화할 데이터가 없습니다')</script>";   
        }
        else
        {
            $total = count( $arr_result );
            
			if ( $type == "cafe24" )
			{
            	$obj = new class_cafe24( $connect );
            
        	    $i=0;
    	        foreach( $arr_result as $product_info )
	            {
            	    $i++;
            	    
            	    // 대표 상품의 경우 재고의 총 합을 update한다.
            	    if ( $product_info['is_represent'] == "1" )
            	    {
            	        $total_stock = $this->get_total_stock( $product_info[product_id] );
            	        
            	        debug( "total_stock:" . $total_stock );
            	        
            	        $obj->stock_sync( $product_info[product_id], $shop_id, $total_stock );     
            	    }
            	    else
            	    {
        	            $obj->stock_sync( $product_info[product_id], $shop_id, $product_info[stock] );
        	        }
                
	                if ( $i % 20 == 0 )
    	            {
                	    echo "<script language=javascript>parent.show_txt('$i / $total')</script>";   
            	        flush();
        	        }
    	        }
            
			}
			else if ( $type == "godo" )
			{
				$obj = new class_godo( $connect );
				
				$i = 0;
				foreach( $arr_result as $product_info )
				{
					$i++;
        	        $obj->stock_sync( $product_info[product_id], $shop_id, $product_info[stock] );
                
	                if ( $i % 20 == 0 )
    	            {
                	    echo "<script language=javascript>parent.show_txt('$i / $total')</script>";   
            	        flush();
        	        }
				}
			}
            echo "<script language=javascript>parent.hide_waiting()</script>";   
        }
        
        echo "<script language='javascript'>parent.hide_waiting()</script>";   
        flush();
    }
    
    
    //
    // 하부 상품의 총 재고.
    //
    function get_total_stock( $product_id )
    {
        global $connect;
        
        $query = "select product_id from products where org_id='$product_id'";
        
        debug( "total_stock: " . $query );
        
        $result = mysql_query( $query, $connect );
        $_str = "";
        while ( $_data = mysql_fetch_assoc( $result ) )
        {
            debug( "ddd:" . $_data[product_id] );
            
            $_str .= $_str ? "," : "";
            $_str .= "'" . $_data['product_id'] . "'";
        }
        
        $query = "select sum(stock) ss from current_stock where product_id in ( $_str )";
        debug( $query );
        $result = mysql_query( $query, $connect );
        $_data  = mysql_fetch_assoc( $result );
        
        return $_data['ss'];
        
    }
    
    function search()
    {
        $arr_result = $this->search_core();
        
        echo json_encode( $arr_result );
           
    }
    
    // 전체 공급처명을 가져온다.
    var $m_supply_info;
    
    function load_supply_name()
    {
        global $connect;
        
        $query = "select * from userinfo where level=0";    
        $result = mysql_query( $qeury, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $m_supply_info[ $data[code] ] = $data[name];
        }
    }
    
    
    
    ////////////////////////////////////////////////////
    // 검색
    function search_core()
    {
        global $connect, $supply_id, $shop_id, $query_type, $query_str,$stock_type,$except_soldout,$start_date,$end_date,$chk_real_stock;
        $arr_result = array();
        
        // test
        /*
        for( $i=0; $i < 100; $i++)
        {
            $arr_data = array( "seq" => $i, "is_first_row" => 1, "row_cnt" => 1 );
            $arr_result[] = $arr_data;
        }
		return $arr_result;
        */
        // step1. 재고 조회        
        // stock_type: stock_in, stock_out, stock_current
        $product_ids = "";
        
        debug( "stock_type: " . $stock_type . " / query_str: $query_str" );
        
        if ( $stock_type == "stock_in" || $stock_type == "stock_out" || $stock_type == "sold_change" )
        {
            $query = "select product_id from stock_tx_history 
                       where crdate >= '$start_date 00:00:00'
                         and crdate <= '$end_date 23:59:59'
                       ";
            
            if ( $stock_type == "stock_in" )
            {
                $query .= " and job in ('in','retin','arrange') ";   
            }
            else if ( $stock_type == "stock_out" )
            {
                $query .= " and job in ('out','retout','trans','arrange') ";   
            }
            else if ( $stock_type == "stock_change" )
            {
                $query .= " and job in ('in','retin','out','retout','trans','arrange') ";   
            }

            debug( "stock_type( $stock_type ): " . $query );
            
            // 500개 까지만 조회 하도록 한다.
            $query .= " limit 2000";
            
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $product_ids .= $product_ids ? "," : "";
                $product_ids .= "'$data[product_id]'";      
            }
            
            if ( $product_ids == "" )
                $product_ids = "-1";
        }
        else
        {
            // 전체 재고 동기화..   
            $product_ids = "";
        }

        debug("product_ids: $product_ids");

        //
        // -1 일경우엔 자료가 없음.
        //
        if ( $product_ids != "-1" )
        {
            //
            // step2 상품검색
            // no_stock_sync 추가 2012.9.11
            $query_str = str_replace("\'","",$query_str );
            $query = "select product_id,name,options,org_price,shop_price,supply_code, link_id,is_represent
                        from products 
                       where enable_stock  = 1 
                         and is_delete     = 0 
                         and no_stock_sync = 0 ";
            
            if ( $query_str )
            {
                $query .= " and $query_type like ('%$query_str%') ";
            }
            
            if ( $product_ids )
            {
                $query .= " and product_id in ( $product_ids ) ";   
            }
           
			// 품절 처리 일자로 검색 
			if ( $stock_type == "soldout" )
			{
				$query .= " and enable_sale=0 
							and sale_stop_date >= '$start_date 00:00:00'
							and sale_stop_date <= '$end_date 23:59:59' ";
			}
			else
			{
	            // 품절 제외
    	        if ( $except_soldout == 1 )
        	    {   
	                $query .= " and enable_sale=1 ";
    	        }
	            // 품절
    	        else if ( $except_soldout == 2 )
        	    {
	                $query .= " and enable_sale=0 ";
    	        }
			}

            $query .= "and link_id <> '' ";
            
            // 조회일 경우엔 limit를 1000개, 
            if ( $_REQUEST['action'] == "search" )
                $query .= " order by name, options limit 100";
           
           
            debug("IN00 ) " . $query );
           
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $_supply_name = $m_supply_info[ $data[supply_code] ] ? $m_supply_info[ $data[supply_code] ] : $data[supply_code];
                $arr_data = array( 
                          "seq"          => $i
                        , "is_first_row" => 1
                        , "row_cnt"      => 1 
                        , "product_id"   => $data[product_id]
                        , "link_id"      => $data[link_id] ? $data[link_id] : "&nbsp"
                        , "product_name" => $data[name]
                        , "options"      => $data[options] ? $data[options] : "&nbsp;"
                        , "shop_price"   => number_format($data[shop_price])
                        , "org_price"    => number_format($data[org_price] )
                        , "supply_name"  => $_supply_name
                        , "is_represent" => $data[is_represent]
                        );
                
                if ( $chk_real_stock == 1)
                    $arr_data['stock'] = class_stock::get_real_stock2( $data[product_id] );
                else
                    $arr_data['stock'] = class_stock::get_current_stock( $data[product_id] );
                
                $arr_result[] = $arr_data;
            }
        }
        
        return  $arr_result ;
    }
}
?>
