<?
///////////////////////////////////////////
//
//	date : 2014.02.13
//	worker : icy
//	name : class_auto_stock_sync
//	table : auto_stock_sync
//
//

include_once "/home/ezadmin/public_html/shopadmin/lib/config_api.php";
include_once "class_db.php" ;

class class_auto_stock_sync
{
	public $connect;					// member connection
	public $start_date;					
	public $start_time;
	public $end_date;
	public $end_time;
	public $from;						// yyyy-MM-dd HH:mm:ss
	public $to;							// yyyy-MM-dd HH:mm:ss
	public $worker;	
	public $config;

	// 생성자 
	function __construct( $start_date="", $start_time="", $end_date="", $end_time="", $domain = "", $worker = "" )
	{
		// DB 연결 
		if ( $domain )
			$this->connect = $this->connect_manual( $domain );
		else
		{
			$odb = new class_db();
			$connect = $odb->connect( _MYSQL_HOST_, _MYSQL_ID_, _MYSQL_PASSWD_ );
			$this->connect = $connect;	
		}

		// 작업자 
		if ( !$worker )
			$worker = $_SESSION['LOGIN_ID'] ? $_SESSION['LOGIN_ID'] : "UNKNOWN" ;		
		$this->worker = $worker;

		// 기간 지정 
        if ( !$start_date ) $start_date = date('Y-m-d', strtotime('-10 day'));
		if ( !$start_time ) $start_time = "00:00:00";
		if ( strlen( $start_time ) == 2 ) $start_time .= ":00:00";
        if ( !$end_date ) $end_date = date('Y-m-d');
		if ( !$end_time ) $end_time = "23:59:59";
		if ( strlen( $end_time ) == 2 ) $end_time .= ":59:59";		
		$this->start_date = $start_date;
		$this->start_time = $start_time;
		$this->end_date = $end_date;
		$this->end_time = $end_time;
		$this->from = $start_date . " " . $start_time;
		$this->to = $end_date . " " . $end_time;

		// 설정 
		$this->config = $this->get_auto_stock_sync_config();
	}

	function debug($str)
	{
        $logfile = "/tmp/class_auto_stock_sync.log";
        $fp = @fopen($logfile, "a+");
        $output = date("Y/m/d H:i:s")." ". $str."\n";
        @fwrite($fp, $output);
        @fclose($fp);
	}

	function connect_manual( $domain )
	{
		$sys_connect = mysql_connect(_MYSQL_SYS_HOST_, _MYSQL_SYS_ID_, _MYSQL_SYS_PASSWD_);
		mysql_select_db(_MYSQL_SYS_DB_, $sys_connect);

		$sql = "select * from sys_domain where id = '$domain'";
		$syscon = mysql_fetch_assoc(mysql_query($sql, $sys_connect));

		$odb = new class_db();
		$connect = $odb->connect( $syscon['host'], $syscon['id'], $syscon['db_pwd'] );

		return $connect;
	}

	// 설정 가져오기 
	function get_auto_stock_sync_config()
	{
		$config = array();
		$query = "select *
					from auto_stock_sync_config";

		$result = mysql_query( $query, $this->connect );
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$config[ $list['name'] ] = $list['value'];
		}

		return $config;
	}

	// 재고변화가 있는 작업 대상 상품을 찾아서 
	// auto_stock_sync 테이블에 입력 
	function collect_target_products( )
	{
		$query = "select * 
					from stock_tx_history
				   where crdate >= '$this->from'
					 and crdate <= '$this->to'
				group by product_id ";

		$result = mysql_query ( $query, $this->connect );
		
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$product_id = $list['product_id'];		
			$this->register_auto_stock_sync( $product_id );
		}
	}

	// code_match 를 읽어서 
	// auto_stock_sync_shoplist 에 입력 
	function insert_auto_stock_sync_shoplist( $product_info, $start_date, $end_date )
	{
		$product_id = $product_info["product_id"];
		$current_stock = $product_info["current_stock"];
		$stock_alarm1 = $product_info["stock_alarm1"];
		$stock_alarm2 = $product_info["stock_alarm2"];

		//
		$soldout_type = $this->config["basic_soldout_type"];
		$is_fixed = $this->config["basic_is_fixed"];
		$update_virtual_stock = $this->config["update_virtual_stock"];
		$updatE_stock_alarm = $this->config["update_stock_alarm"];


		// 판매처별 
		$shoplist = $this->show_shoplist( $product_id, $start_date, $end_date );
		
		foreach ( $shoplist as $key => $val )
		{
			$shop_code = $val["shop_id"] % 100;

			if ( $shop_code == "68" || $shop_code == "72" )
				continue; 

			$query = "select *
						from auto_stock_sync_shoplist
					   where product_id = '$val[id]'
						 and shop_id = '$val[shop_id]'
						 and shop_product_name = '$val[shop_product_name]'
						 and shop_option = '$val[shop_option]' ";

			$this->debug($query);
			$result = mysql_query ( $query, $this->connect );

			// 이미 등록된 정보가 있다면 
			// update
			if ( mysql_num_rows($result) > 0 )
			{
				$list = mysql_fetch_assoc( $result );

				// 값 고정 사용시 업데이트 하지 않음 
				if ( $list["fix_value"] == "1" )
					continue;

				$query = "update auto_stock_sync_shoplist set 
							product_id = product_id ";	

				// 가상 재고 업데이트 사용 시
				if ( $update_virtual_stock == "yes" )
					$query .= " virtual_stock = '$current_stock', ";
			
				// 위험, 경고 재고 업데이트 사용 시 
				if ( $update_stock_alarm == "yes" )
				{	
					$query .= " stock_alarm1 = '$stock_alarm1', 
								stock_alarm2 = '$stock_alarm2',	";
				}

				$query .= "	 shop_id = shop_id
							where product_id = '$val[id]'
							  and shop_id = '$val[shop_id]'
							  and shop_product_name = '$val[shop_product_name]'
							  and shop_option = '$val[shop_option]' ";
			}

			// 등록된 정보가 없다면 새로 등록 
			// insert
			else
			{
				$query = "insert into auto_stock_sync_shoplist set
							product_id = '$val[id]',
							shop_id = '$val[shop_id]',
							shop_code = '$val[shop_code]',
							shop_option = '$val[shop_option]',
							shop_product_name = '$val[shop_product_name]',
							shop_product_price = '$val[shop_price]',
							soldout_type = '$soldout_type',
							price_change = '0',
							virtual_stock = '$current_stock',
							stock_alarm1 = '$val[stock_alarm1]',
							stock_alarm2 = '$val[stock_alarm2]',
							is_fixed = '$is_fixed' ";
			}

			$this->debug($query);
			mysql_query ( $query, $this->connect );
		}
		
		// 동기화된 상품은 code_match 에 없음
		// cafe24, makeshop 
		// 동기화가 된 상품만 해당 
		if ( $product_info['link_id'] != "" && $product_info['is_sync'] != "" )	
		{
			//
			$shop_code = 0;
			switch ( $product_info['is_sync'] ) 
			{
				case "cafe API" :
					$shop_code = 72;
					break;
				case "makeshop API" :
					$shop_code = 68;
					break;
			}

			// 판매처 찾기 
			$query = "select * 
						from shopinfo 
					   where disable = 0
						 and shop_id % 100 = $shop_code";
			$result = mysql_query( $query, $this->connect );

			while ( $shop_info = mysql_fetch_assoc( $result ) )
			{
				$query = "select * 
							from auto_stock_sync_shoplist
						   where product_id = '$product_info[product_id]'
							 and shop_id = '$shop_info[shop_id]'
							 and shop_option = '$product_info[options]',
							 and shop_product_name = '$product_info[name]' ";
				$result2 = mysql_query( $query, $this->connect );

				// 이미 등록된 정보가 있다면 
				// update
				if ( mysql_num_rows( $result2) > 0 )
				{
					$list = mysql_fetch_assoc( $result2 );

					// 값 고정 사용시 업데이트 하지 않음 
					if ( $list["fix_value"] == "1" )
						continue;

					$query = "update auto_stock_sync_shoplist set 
								product_id = product_id ";	
					// 가상 재고 업데이트 사용 시
					if ( $update_virtual_stock == "yes" )
						$query .= " virtual_stock = '$current_stock', ";
					
					// 위험, 경고 재고 업데이트 사용 시 
					if ( $update_stock_alarm == "yes" )
					{	
						$query .= " stock_alarm1 = '$stock_alarm1', 
									stock_alarm2 = '$stock_alarm2',	";
					}

					$query .= "	 shop_id = shop_id
								where product_id = '$product_info[product_id]'
								  and shop_id = '$shop_info[shop_id]'
								  and shop_product_name = '$product_info[name]'
								  and shop_option = '$product_info[options]' ";
				}

				// 등록된 정보가 없다면 새로 등록 
				// insert
				else
				{			
					$query = "insert into auto_stock_sync_shoplist set
								product_id = '$product_info[product_id]',
								shop_id = '$shop_info[shop_id]',
								shop_code = '$product_info[link_id]',
								shop_option = '$product_info[options]',
								shop_product_name = '$product_info[name]',
								shop_product_price = '$product_info[shop_price]',
								soldout_type = '$soldout_type',
								price_change = '0',
								virtual_stock = '$current_stock',
								stock_alarm1 = '$stock_alarm1',
								stock_alarm2 = '$stock_alarm2',
								is_fixed = '$is_fixed'";
				}

				$this->debug($query);
				mysql_query ( $query, $this->connect );
			}
		}
	}

	// auto_stock_sync 에 등록 
	function register_auto_stock_sync( $product_id )
	{
		$current_stock = $this->get_current_stock( $product_id );		
		$product_info = $this->get_product_info( $product_id );
		$product_info["current_stock"] = $current_stock;			

/*
		// 현재 재고가 위험 재고보다 작거나 같으면 
		// 품절  
		if ( $current_stock <= $product_info["stock_alarm1"] )
			$product_info['soldout'] = "1";

		// 현재 재고가 위험 재고보다 크면
		// 판매  
		else
			$product_info['soldout'] = "0";
*/
//		$checked = $this->check_auto_stock_sync( $product_info );

//		if ( $checked )
//		{
			$this->insert_auto_stock_sync($product_info);
			$this->insert_auto_stock_sync_shoplist($product_info);	
			$this->insert_auto_stock_sync_log($product_info);
//		}
	}

	// auto_stock_sync 에 등록된 작업이 같은작업인지 체크 
	function check_auto_stock_sync( $list )
	{
		$checked = false;

		$query = "select * 
					from auto_stock_sync 
					where product_id = '$list[product_id]' ";

		$result = mysql_query( $query, $this->connect );

		if ( $result )
		{
			$data = mysql_fetch_assoc( $result );

			// 작업이 다르면 
			if ( $data['work_type'] != $list['work_type'] )
				$checked = true;
		}
		else
			$checked = true;

		return $checked;
	}

	// 상품정보 가져오기 
	function get_product_info( $product_id )
	{
		$query = "select * 
					from products 
				   where product_id = '$product_id'";
		$result = mysql_query ( $query, $this->connect );
		$data = mysql_fetch_assoc( $result );

		return $data;
	}

	// 작업 구분 가져오기 
	function get_work_type( $product_id )
	{
		$query = "select * from auto_stock_sync where product_id='$product_id'";
		$result = mysql_query ( $query, $this->connect );
		$data = mysql_fetch_assoc( $result );
	
		return $data["work_type"];
	}

	function get_sales_stock( $product_id, $shop_id, $days )
	{
		$day_before = date('Y-m-d', strtotime('-'.$days.' day'));
		$data = array();
		$query = "select sum(qty) as qty
					from order_products 
				   where product_id = '$product_id'";
		if ( $shop_id )
			$query .= " and shop_id = '$shop_id' ";
		
		$query .= " and date(match_date) >= '$day_before'";

		$result = mysql_query( $query, $this->connect );	
		$list = mysql_fetch_assoc( $result ) ;

		return $list["qty"];
	}

	// auto_stock_sync_shoplist 에 있는 정보 가져오기 
	function get_shoplist( $product_id, $shop_id = "" ) 
	{
		$shoplist = array();

		$query = "select * 
					from auto_stock_sync_shoplist
				   where product_id = '$product_id'";
	
		if ( $shop_id )
			$query .= " and shop_id = '$shop_id'";
		
		$query .= " order by shop_id % 100 ";

		$result = mysql_query( $query, $this->connect );
		$i = 0;
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$list["shop_name"] = $this->get_shop_name( $list["shop_id"] );
			$shoplist[$i] = $list;			
			$i++;
		}

		return $shoplist;
	}

	// 판매처 이름 가져오기 
	function get_shop_name( $shop_id )
	{
		$query = "select shop_name from shopinfo where shop_id = '$shop_id'";
		$result = mysql_query ( $query, $this->connect );
		$list = mysql_fetch_assoc( $result );
		
		return $list["shop_name"];
	}

	//
	function insert_auto_stock_sync( $list )
	{
		$query = "insert into auto_stock_sync set
					product_id = '$list[product_id]',
					work_date = now(),
					work_count = 0,
					work_result = 0,
					msg = '$list[msg]'
				  on duplicate key update 
					work_date = now(),
					work_count = 0,
					work_result = 0,
					msg = '$list[msg]'";
		mysql_query ( $query, $this->connect );
	}

	//
	function insert_auto_stock_sync_log ( $list )
	{
		$query = "insert into auto_stock_sync_log set
					product_id = '$list[product_id]',
					shop_id = '$list[shop_id]',
					work_date = now(),
					work_type = '$list[work_type]',
					work_detail = '$list[work_detail]',
					work_result = '$list[work_result]',
					worker = '$this->worker'";

		mysql_query ( $query, $this->connect );
	}

	// 이지오토에서 작업할 상품 내역 가져오기 
	function get_product_list_client( $work_type, $work_result, $search_type, $search_keyword )
	{
		$product_list = array();

		$query = "select * 
					from auto_stock_sync 
					where work_date >= '$this->from' 
					and work_date <= '$this->to'";

		if ( $work_type == "0" || $work_type == "1" ) $query .= " and work_type = '$work_type'";
		if ( $work_result == "0" || $work_result == "1" ) $query .= " and work_result = '$work_result'";

		$result = mysql_query( $query, $this->connect );

		$i = 0;
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$product_id = $list['product_id'];
			$product_info = $this->get_product_info( $product_id );

			$list['product_name'] = $product_info["name"];
			$list['stock_alarm1'] = $product_info["stock_alarm1"];
			$list['stock_alarm2'] = $product_info["stock_alarm2"];
			$list['link_id'] = $product_info['link_id'];
			$list['new_link_id'] = $product_info['new_link_id'];
			$list['current_stock'] = $this->get_current_stock( $product_id );
			$list["shoplist"] = $this->get_shoplist( $product_id );

			$product_list[$i] = $list;
			$i++;
		}

		return $product_list;
	}

	// 
	function get_product_list( $work_type, $work_result, $search_type, $search_keyword, $page )
	{
		$product_list = array();

		// 한 페이지에 출력되는 개수 
		$list_per_page = 20;

		$query = "select * 
					from auto_stock_sync 
					where work_date >= '$this->from' 
					and work_date <= '$this->to'";

		if ( $work_type == "0" || $work_type == "1" ) $query .= " and work_type = '$work_type'";
		if ( $work_result == "0" || $work_result == "1" ) $query .= " and work_result = '$work_result'";

		$result = mysql_query ( $query, $this->connect );
		if ( $result )
		{
			// 총 검색 개수 
			$product_count = mysql_num_rows( $result );			

			if ( $product_count > 300 )
			{
				
			}		
			else
			{	
				// 마지막 페이지 수 
				if ( $product_count >= $list_per_page )
				{
					$temp = $product_count - ( $product_count % $list_per_page );
					$max_page = ($temp / $list_per_page) + 1 ;		
				}
				else 
					$max_page = 1;

				$from = ($page - 1) * $list_per_page ;
				$query .= " limit $from, $list_per_page";

				$result = mysql_query( $query, $this->connect );

				$i = 0;
				while ( $list = mysql_fetch_assoc( $result ) )
				{
					$product_id = $list['product_id'];
					$product_info = $this->get_product_info ( $product_id );
					$list['product_name'] = $product_info["name"];
					$list['stock_alarm1'] = $product_info["stock_alarm1"];
					$list['stock_alarm2'] = $product_info["stock_alarm2"];
					$list['current_stock'] = $this->get_current_stock( $product_id );		
	
					$product_list[$i] = $list;
					$i++;
				}
			}

			$data["auto_stock_sync_list"] = $product_list;
			$data["auto_stock_sync_count"] = $product_count;
			$data["max_page"] = $max_page;			
		}

		return $data;
	}

	// 등록된 재고 자동화 삭제 
	function delete_auto_stock_sync( $product_id )
	{
		$query = "delete from auto_stock_sync
					where product_id = '$product_id'";
		mysql_query( $query, $this->connect );

		$query = "delete from auto_stock_sync_shoplist
					where product_id = '$product_id'";
		mysql_query( $query, $this->connect );

		$query = "delete from auto_stock_sync_log
					where product_id = '$product_id'";
		mysql_query( $query, $this->connect );
	}

	// 로그 보기 
	function show_log( $product_id )
	{
		$list_log = array();
		$query = "select * 
					from auto_stock_sync_log
					where product_id = '$product_id' ";

		$result = mysql_query( $query, $this->connect );

		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$list_log[$i] = $list;
			$i++;
		}

		return $list_log;
	}

	// 현재재고 가져오기 
    function get_current_stock($product_id, $bad=0 )
    {
        $query  = "select sum(stock) stock 
					from current_stock 
					where product_id='$product_id' 
					and bad=$bad";
        $result = mysql_query( $query, $this->connect );
        $data   = mysql_fetch_assoc( $result );

        return ($data[stock] ? $data[stock] : "0");
    }

	// code_match 에 있는 정보 가져오기 
	function show_shoplist( $product_id, $start_date = "", $end_date = "" )
	{
		$shoplist = array();

		$query = "select * 
					from code_match
					where id = '$product_id'";

		if ( $start_date )
			$query .= " and input_date >= '$start_date' ";
		if ( $end_date )
			$query .= " and input_date <= '$end_date' ";

		$result = mysql_query ( $query, $this->connect );

		$i = 0;
		while ( $list = mysql_fetch_assoc ( $result ) )
		{
			$shoplist[$i] = $list;
			$i++;
		}

		return $shoplist;
	}

	// 작업 구분 변경 
	function change_auto_stock_sync( $product_id, $work_type )
	{
		$query = "update auto_stock_sync set
					work_type = '$work_type'
				   where product_id = '$product_id'";

		$result = mysql_query ( $query, $this->connect );

		if (!$result)
			return "fail";
		else
			return "success";
	}

	// 품절 처리 방식 변경 
	function change_soldout_type( $product_id, $shop_id, $soldout_type )
	{
		$query = "update auto_stock_sync_shoplist set
					soldout_type = '$soldout_type'
				   where product_id = '$product_id'
					 and shop_id = '$shop_id' ";
	
		$result = mysql_query ( $query, $this->connect );

		if (!$result)
			return "fail";
		else
			return "success";
	}

	// 가격 변경 방식 변경 
	function change_price_change( $product_id, $shop_id, $price_change )
	{
		$query = "update auto_stock_sync_shoplist set
					price_change = '$price_change'
				   where product_id = '$product_id'
					 and shop_id = '$shop_id' ";
	
		$result = mysql_query ( $query, $this->connect );

		if (!$result)
			return "fail";
		else
			return "success";
	}
}
?>
