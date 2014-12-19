<?
require_once "class_top.php";
require_once "class_ui.php";
require_once "class_stock.php";
require_once "class_auto_stock_sync.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_IP00
// 
//
class class_IP00 extends class_top 
{
	function IP00()
	{
		global $template;

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IP01()
	{
		global $template;

        if ( !$start_date ) $start_date = date('Y-m-d', strtotime('-10 day'));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function search()
	{
		global $start_date, $start_time, $end_date, $end_time, $page;
		global $work_type, $work_result, $search_type, $search_keyword;

        if ( !$start_date ) $start_date = date('Y-m-d', strtotime('-1 day'));
		if ( !$end_date ) $end_date = date('Y-m-d');

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		if ( !$page ) $page = 1;
		$data = $obj->get_product_list( $work_type, $work_result, $search_type, $search_keyword, $page );

		echo json_encode( $data );
	}

	function IP02()
	{
		global $template, $product_id;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		$data = $obj->show_log( $product_id );

		$product_info = $obj->get_product_info( $product_id );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IP03()
	{
		global $template, $product_id, $registered;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );

		$product_info = $obj->get_product_info( $product_id );
		$obj->insert_auto_stock_sync_shoplist( $product_info );		
		$data = $obj->get_shoplist( $product_id );

		$work_type = $obj->get_work_type( $product_id );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IP04()
	{
		global $template, $str_supply_code, $supply_code;

		$supply_code = split(",", $str_supply_code );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IP05()
	{
		global $template, $str_supply_code, $supply_code;

		$supply_code = split(",", $str_supply_code );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function IP06()
	{
		global $template, $str_supply_code, $supply_code;

		$supply_code = split(",", $str_supply_code );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	function IP07()
	{
		global $template;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		$config = $obj->config;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function register_auto_stock_sync_mass()
	{
		global $product_ids;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		
		$array_product_ids = explode( "," , $product_ids );
		
		if ( count( $array_product_ids ) > 0 )
		{
			foreach ( $array_product_ids as $key => $product_id )
			{
				$obj->register_auto_stock_sync( $product_id );
			}
		}
	}

	function register_auto_stock_sync()
	{
		global $product_id;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );

		$obj->register_auto_stock_sync( $product_id );
	}

	function change_auto_stock_sync()
	{
		global $product_id, $work_type;
		global $start_date, $start_time, $end_date, $end_time;

        if ( !$start_date ) $start_date = date('Y-m-d', strtotime('-10 day'));
		if ( !$end_date ) $end_date = date('Y-m-d');

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );

		$obj->change_auto_stock_sync( $product_id, $work_type );
	}
	
	function delete_auto_stock_sync()
	{
		global $product_id;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		$obj->delete_auto_stock_sync( $product_id );
	}

	function change_soldout_type()
	{
		global $product_id, $shop_id, $soldout_type;
		global $start_date, $start_time, $end_date, $end_time;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		$data = $obj->change_soldout_type( $product_id, $shop_id, $soldout_type );

		$list = array();
		$list["product_id"] = $product_id;
		$list["shop_id"] = $shop_id;
		$list["work_type"] = $soldout_type;

		$obj->insert_auto_stock_sync_log ( $list );

		echo $data;
	}

	function change_price_change()
	{
		global $product_id, $shop_id, $price_change;
		global $start_date, $start_time, $end_date, $end_time;

        if ( !$start_date ) $start_date = date('Y-m-d', strtotime('-10 day'));
		if ( !$end_date ) $end_date = date('Y-m-d');

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );
		$data = $obj->change_price_change( $product_id, $shop_id, $price_change );

		$list = array();
		$list["product_id"] = $product_id;
		$list["shop_id"] = $shop_id;
		$list["work_type"] = 10 + $price_change;

		$obj->insert_auto_stock_sync_log ( $list );
	
		echo $data;
	}

	function load_supply_info()
	{
		global $connect;

		$query = "select * from userinfo where level = 0";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $supply_info[ $data[code] ] = $data[name];
        }

		return $supply_info;
	}

	function search_products_group()
	{
		global $supply_id, $query_type, $query_str;
		global $connect;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );

		// 검색할때는 auto_stock_sync_shoplist 에 
		// update 하지 않는다
		$obj->config["update_virtual_stock"] = "no";
		$obj->config["update_stock_alarm"] = "no";

		$supply_info = $this->load_supply_info();
		$arr_result = array();

		$query = "select *
					from products
				   where enable_stock = 1
					 and is_delete = 0 ";

        if ( $query_str )
			$query .= " and $query_type like ('%$query_str%') ";

		$result = mysql_query ( $query, $connect );

		$arr_product_id = array();	
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			if ( !$list["org_id"] )
				continue;

			$arr_product_info[ $list["product_id"] ] = $list;

			$arr_result[ $list["org_id"] ] = array(
				"name"        => $list["name"],
				"supply_code" => $list["supply_code"],
				"supply_name" => class_C::get_supplyname( $list["supply_code"] )				
			);
		}

		foreach ( $arr_product_info as $product_id => $product_info )
		{
			$org_id = $product_info["org_id"];		
			$obj->insert_auto_stock_sync_shoplist( $product_info );		
			$shoplist = $obj->get_shoplist( $product_id ); 

			foreach ( $shoplist as $key => $val )
			{
				$sales_stock = $obj->get_sales_stock( $product_id, $val["shop_id"], $this->start_date );

				$val["sales_stock"] = $sales_stock;
				$shoplist[$key] = $val;
			}

			$shopinfo = array();

			$shopinfo["options"]	   = $product_info["options"];
			$shopinfo["current_stock"] = class_stock::get_current_stock($product_id);
			$shopinfo["sales_stock"]   = $obj->get_sales_stock ( $product_id, "", $this->start_date );
			$shopinfo["stock_alarm1"]  = $product_info['stock_alarm1'];
			$shopinfo["stock_alarm2"]  = $product_info['stock_alarm2'];
			$shopinfo["enable_sale"]   = $product_info['enable_sale'];
			$shopinfo["shoplist"]	   = $shoplist;

			$arr_result[ $org_id ]["data"][ $product_id ] = $shopinfo;
		}

		echo json_encode( $arr_result );
	}

	function search_products_shop()
	{
		global $supply_id, $query_type, $query_str, $shop_id, $start_date, $end_date;
		global $connect;

		$obj = new class_auto_stock_sync( $start_date, $start_time, $end_date, $end_time );

		$supply_info = $this->load_supply_info();
		$arr_result = array();

		$query = "select *
					from products
				   where enable_stock = 1
					 and is_delete = 0 ";

        if ( $query_str )
			$query .= " and $query_type like ('%$query_str%') ";

		$result = mysql_query ( $query, $connect );

		$arr_product_id = array();	
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			if ( !$list["org_id"] )
				continue;

			$arr_product_info[ $list["product_id"] ] = $list;

			$arr_result[ $list["org_id"] ] = array(
				"name"        => $list["name"],
				"supply_code" => $list["supply_code"],
				"supply_name" => class_C::get_supplyname( $list["supply_code"] ),
				"rowspan" => 0
			);
		}

		foreach ( $arr_product_info as $product_id => $product_info )
		{
			$org_id = $product_info["org_id"];
		
			$obj->insert_auto_stock_sync_shoplist( $product_info, $start_date, $end_date );		
			$shoplist = $obj->get_shoplist( $product_id, $shop_id ); 

			$shopinfo = array();

			$shopinfo["options"]	   = $product_info["options"];
			$shopinfo["current_stock"] = class_stock::get_current_stock($product_id);
			$shopinfo["stock_alarm1"]  = $product_info['stock_alarm1'];
			$shopinfo["stock_alarm2"]  = $product_info['stock_alarm2'];
			$shopinfo["enable_sale"]   = $product_info['enable_sale'];
			$shopinfo["shoplist"]	   = $shoplist;

			$arr_result[ $org_id ]["data"][ $product_id ] = $shopinfo;
			$arr_result[ $org_id ][ "rowspan" ] += count( $shoplist );
		}

		echo json_encode( $arr_result );
	}

	function search_products()
	{
		global $supply_id, $query_type, $stock_type, $except_soldout, $query_str;
		global $start_date, $end_date, $connect;
		global $is_synced;	
	
		$supply_info = $this->load_supply_info();
		$arr_result = array();
		$product_ids = "";

        if ( $stock_type == "stock_in" || $stock_type == "stock_out" )
        {
            $query = "select product_id 
						from stock_tx_history 
                       where crdate >= '$start_date 00:00:00'
                         and crdate <= '$end_date 23:59:59' ";

            if ( $stock_type == "stock_in" )
                $query .= " and job in ('in','retin','arrange') ";
            else if ( $stock_type == "stock_out" )
                $query .= " and job in ('out','retout','trans','arrange') ";

            $query .= " limit 1000";

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

        //
        // -1 일경우엔 자료가 없음.
        //
        if ( $product_ids != "-1" )
        {
            //
            // step2 상품검색
            // no_stock_sync 추가 2012.9.11
            $query_str = str_replace("\'","",$query_str );
            $query = "select * 
                        from products 
                       where enable_stock = 1 
                         and is_delete    = 0 ";

            if ( $query_str )
                $query .= " and $query_type like ('%$query_str%') ";

            if ( $product_ids )
                $query .= " and product_id in ( $product_ids ) ";

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
                    $query .= " and enable_sale = 1 ";
                // 품절
                else if ( $except_soldout == 2 )
                    $query .= " and enable_sale = 0 ";
            }

			// 동기화 여부로 검색 
			if ( $is_synced == "1" )
				$query .= " and is_sync <> '' ";
			else if ( $is_synced == '2' )
				$query .= " and is_sync = '' ";

			//
			$query .= "	order by name, options 
						limit 1000";

            $result = mysql_query( $query, $connect );
			
			$seq = 1;
            while ( $data = mysql_fetch_assoc( $result ) )
            {			
                $supply_name = $supply_info[ $data['supply_code'] ] 
					? $supply_info[ $data['supply_code'] ] : $data['supply_code'];

                $arr_data = array(
					"seq"			=> $seq,
                    "product_name"  => $data['name'],
                    "options"       => $data['options'] ? $data['options'] : "&nbsp;",
					"product_id"    => $data['product_id'],
                    "link_id"       => $data['link_id'] ? $data['link_id'] : "&nbsp",
                    "shop_price"    => number_format($data['shop_price']),
                    "org_price"     => number_format($data['org_price']),
                    "supply_name"   => $supply_name,
                    "is_represent"  => $data['is_represent'],
					"current_stock" => class_stock::get_current_stock($data['product_id']),
					"stock_alarm1"  => $data['stock_alarm1'],
					"stock_alarm2"  => $data['stock_alarm2'],
					"enable_sale"	=> $data['enable_sale'] 
				);

                $arr_result[$seq] = $arr_data;
				$seq++;
            }
        }

		echo json_encode( $arr_result );
	}

	function update_auto_stock_sync_shoplist()
	{
		global $product_id, $shop_id, $field_name, $field_value;
		global $shop_product_name, $shop_option;

		$obj = new class_auto_stock_sync();

		$query = "update auto_stock_sync_shoplist set
					$field_name = '$field_value'
					where product_id = '$product_id'
					and shop_id = '$shop_id'
					and shop_product_name = '$shop_product_name'
					and shop_option = '$shop_option'";
		$result = mysql_query ( $query, $obj->connect );

		if ( $result )
			echo "success";
		else 
			echo "fail";
	}
}

