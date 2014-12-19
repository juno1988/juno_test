<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_product.php";

class class_DX00 extends class_top {

    ///////////////////////////////////////////
    // shop들의 list출력
    var $m_debug = 0;

    function class_DX00( $debug = 0)
    {
	// 디버그 모드
	if ( $debug )
	{
		$this->m_debug = 1;
	}
    }

    function DX00( $debug=0 )
    {
	global $connect;
	global $template;

	$line_per_page = _line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

 
    //////////////////////////////
    function get_warehouse()
    {
	global $connect;
	
	$arrs = array();
	$sql = "select * from tbl_warehouse order by priority";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
		array_push($arrs, $list[id]);
	}
	return $arrs;
    }

    function get_noprint_qty($product_id)
    {
	global $connect;

	$sql = "select sum(qty) qty from orders
		 where product_id = '$product_id'
		   and status in (1)
		   and order_cs in (0, 5, 11)
		   and (trans_no <= 0 or trans_no is null)
	";
debug($sql);
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$list = mysql_fetch_array($result);
	
	return $list[qty];
    }

    //////////////////////////////
    function get_printable_stock($product_id, $warehouse)
    {
	global $connect;
	
	$sql = "select sum(qty) total_qty from tbl_print_enable
		 where product_id = '$product_id'
		   and warehouse = '$warehouse'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	
	return $list[total_qty];
    }

    //////////////////////////////
    function get_current_stock($product_id, $warehouse)
    {
	global $connect;

	$print_stock = class_DX00::get_printable_stock($product_id, $warehouse);

	$sql = "select stock from tbl_current_stock 
		 where product_id = '$product_id'
		   and warehouse = '$warehouse'";

	$list = mysql_fetch_array(mysql_query($sql, $connect));
	
	return ( $list[stock] - $print_stock );
    }

    //////////////////////////////
    function get_stock($product_id, $warehouse)
    {
	global $connect;

	$today = date("Y-m-d");

	// 입고 기준일 게산
	$sql = "select start_date from ez_jaego_inout 
		 where product_id = '$product_id' and type = 1 
		   and warehouse = '$warehouse'
		 order by no limit 1";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$base_date = $list[start_date];

	if (!$base_date) $base_date = date("Y-m-d");


	// 입고량
	$sql = "select sum(qty) in_qty from ez_jaego_inout 
		 where product_id = '$product_id' and type = 1
		   and warehouse = '$warehouse'
		   and start_date >= '$base_date'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$in_qty = $list[in_qty];

	// 출고량
	$sql = "select sum(qty) out_qty from ez_jaego_inout 
		 where product_id = '$product_id' and type = 2
		   and warehouse = '$warehouse'
		   and start_date >= '$base_date'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$out_qty = $list[out_qty];

	// 판매량
	$sql = "select sum(qty) sale_qty from orders
		 where product_id = '$product_id'
		   and order_cs not in (1,3,4,6,8,9,10,12)
		   and warehouse = '$warehouse'
		   and status in (7,8)
		   and trans_no > ''
		   and substring(trans_date,1,10) >= '$base_date'
		   and substring(trans_date,1,10) <= '$today'";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$sale_qty = $list[sale_qty];

	$stock = $in_qty - $out_qty - $sale_qty;

	return $stock;
    }

    //////////////////////////////
    //  for sy.hwang
    function make_stock()
    {
	global $connect;
	
	echo "<script>show_waiting();</script>";

	mysql_query("truncate table tbl_current_stock") or die(mysql_error());

	////////////////////////
	$sql = "select * from products order by product_id";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$total_rows = mysql_num_rows($result);

	$warehouse_arrs = class_DX00::get_warehouse();
	$rows = 0;

	while ($list = mysql_fetch_array($result))
	{
		$product_id = trim($list[product_id]);
		$rows++;
		foreach ($warehouse_arrs as $warehouse)
		{
			$stock = class_DX00::get_stock($product_id, $warehouse);

			$ins_sql = "insert into tbl_current_stock (product_id, warehouse, stock) 
				    values ('$product_id', '$warehouse', '$stock')";
			mysql_query($ins_sql, $connect) or die(mysql_error());
		}

    		$str = "${rows} / ${total_rows}번째 작업중입니다.";
    		echo "<script>show_txt('$str');</script>";
	}

	echo "<script>hide_waiting();</script>";
	echo "<script>alert('작업 #1이 완료되었습니다.');</script>";
	$this->redirect ( "?template=DX00" );
    }


    //////////////////////////////////////////////////////////////
    //  for JK.ryu
    //===================================================
    // 출력 가능한지 check하는 engine
    // 전체 주문 seq = pack or pack is null인 주문에 대해 배송 여부 체크
    function make_printable()
    {
	global $connect;

	return;


	// NOT USED ... 2007.12.14

	echo "<script>show_waiting();</script>";

	// tbl_print_enable을 초기화
	$query = "truncate tbl_print_enable";
	mysql_query ( $query, $connect );

	// order의 status가 1인 모든 warehouse를 삭제 함

	$query = "update orders set warehouse=null where trans_no = ''";

	if ( !$this->m_debug )
		mysql_query ( $query, $connect );
	else
		echo "debug mode: $query";

	//=================================================
	// 합포 K, K check
	$arr_warehouse = array ( "K" );
	$this->scan_pack_orders( $arr_warehouse );

	//=================================================
	// 단품 K check
	$arr_warehouse = array ( "K" );
	$this->scan_one_orders( $arr_warehouse );

	//================================================
	// 합포 C, C check
	$arr_warehouse = array ( "C" );
	$this->scan_pack_orders( $arr_warehouse );

	//=================================================
	// 합포 C, K check
	$arr_warehouse = array ( "C", "K" );
	$this->scan_pack_orders( $arr_warehouse );


	//=================================================
	// 단품 C check
	$arr_warehouse = array ( "C" );
	$this->scan_one_orders( $arr_warehouse );

	echo "<script>hide_waiting();</script>";
	echo "<script> alert('작업 #2이 완료되었습니다.');</script>";
	$this->redirect ( "?template=DX00" );
    }

    //===============================================
    // 합포 조회
    function scan_pack_orders( $arr_warehouse )
    {
	global $connect;

	// 배송 상태가 접수이고 CS 상태 0(정상), 5(배송전 교환요청), 13(배송전 교환확인)
	$query = "select seq, product_id, qty, pack 
                    from orders 
                   where status=1 
                     and order_cs in (0,11,9,5,7,13)
                     and seq=pack
                   order by pack";

	$result = mysql_query ( $query, $connect );
	$total_rows = mysql_num_rows( $result );
	
	while ( $data = mysql_fetch_array ( $result ) )
	{
		$rows++;
		$this->check_pack( $data, $arr_warehouse );

		$str = "${rows} / ${total_rows}번째 작업중입니다.";
		echo "<script>show_txt('$str');</script>";
	}
    }

    // 단품 check
    function scan_one_orders( $arr_warehouse )
    {
	global $connect;

	// 배송 상태가 접수이고 CS 상태 0(정상), 5(배송전 교환요청), 13(배송전 교환확인)
	$query = "select seq, product_id, qty, pack 
                    from orders 
                   where status=1 
                     and order_cs in (0,11,9,5,7,13)
                     and pack is null
                   order by seq";

	$result = mysql_query ( $query, $connect );
	$total_rows = mysql_num_rows( $result );
	
	while ( $data = mysql_fetch_array ( $result ) )
	{
		$rows++;
		$this->check_one( $data, $arr_warehouse );

		$str = "${rows} / ${total_rows}번째 작업중입니다.";
		echo "<script>show_txt('$str');</script>";
	}

    }

    // 1개의 주문만 check
    function scan_one( $seq, $arr_warehouse )
    {
	global $connect;
	$query = "select seq, product_id, qty, pack 
                    from orders 
                   where seq=$seq";

	$result = mysql_query ( $query, $connect );
	$total_rows = mysql_num_rows( $result );
	
	while ( $data = mysql_fetch_array ( $result ) )
	{
		$rows++;
		return $this->check_one( $data, $arr_warehouse );
	}
    }

    function check_one ( $data, $arr_warehouse )
    {
	$current_info = "";

	// 품절인지 여부 check
	if ( !$this->enable_sale( $data[product_id] ) )
	{
		$_result = array ( product_id=>$data[product_id], enable=>0 , error=>"disable sale");
	}
	else
	{
	// 품절이 아닐 경우 재고가 있는지 check
		$current_info = $this->find_stock ( $data[product_id], $arr_warehouse );	

		if ( $current_info[stock] >= $data[qty] )
		{
			$_result = array ( 
				product_id  => $data[product_id], 
				enable      => 1, 
				qty         => $data[qty], 
				warehouse   => $current_info[warehouse], 
				seq         => $data[seq] );
		}
		else
		{
			$_result = array ( 
				product_id  => $data[product_id], 
				enable      => 0 ,
				error       => "out of stock current stock: " . $current_info[stock] . "개" );
		}
	}

	if ( $_result[enable] == 1)
	{
		$this->insert_print_enable( $_result[seq], $_result[product_id] , $_result[qty], $_result[warehouse] );
	}

	debug ( "DX00 check_one: $_result[seq] / enable: $_result[enable],product_id: $_result[product_id],seq: $_result[seq], qty: $_result[qty], stock: $_current_info[stock],warehouse: $_result[warehouse]\n" );

	return $_result;
    }

    //============================
    // 합포 check
    function check_pack( $data, $arr_warehouse )
    {
	global $connect;
	$_arr_result = array();
	$_pack = $data[pack];

	$query = "select seq, product_id, sum(qty) qty from orders where pack='$_pack' group by product_id";
	$result = mysql_query ( $query, $connect );

	while ( $data = mysql_fetch_array ( $result ) )
	{
		// 품절인지 여부 check
		// 품절은 테스트 안 함 잠시..
		if ( !$this->enable_sale( $data[product_id] ) )
		{
			$_arr_result[] = array ( 
					product_id  =>$data[product_id], 
					enable      =>0, 
					error       => "disable sale" );
		}
		else
		{
		// 품절이 아닐 경우 재고가 있는지 check
			$current_info = $this->find_stock ( $data[product_id], $arr_warehouse );	

			if ( $current_info[stock] >= $data[qty] )
			{
				$_arr_result[] = array ( 
					product_id  => $data[product_id], 
					enable      => 1, 
					qty         => $data[qty], 
					warehouse   => $current_info[warehouse], 
					seq         => $data[seq],
					pack        => $_pack );
			}
			else
			{
				$_arr_result[] = array ( 
					product_id  => $data[product_id], 
					enable      => 0,
					error       => "out of stock" );
			}
		}

	}

// print_r ( $_arr_result );

	//=======================================================
	// 모든 상품이 배송 가능한지 여부 check
	$_all_printable = 1;
	for($i = 0; $i < count($_arr_result); $i++)
	{
		$_result = $_arr_result[$i];


		if ( $_result[enable] == 0 )
		{
			$_all_printable = 0;
			break;
		}
	}

	//=======================================================
	// 전체 주문을 orders와 tbl_print_enable에 입력함
	if ( $_all_printable )
	{
		for($i = 0; $i < count($_arr_result); $i++)
		{
			$_result = $_arr_result[$i];

			// 개별창고에 대한 검색이 끝난 후 반포 출력
			if ( count( $arr_warehouse) > 1 )
				$pack_warehouse = "P";
			else
				$pack_warehouse = $_result[warehouse]; // bug가 생겼던 부분 - 2007.11.6

			// product_id와 pack을 넘겨야 함 -jk
			// bug fix
			$this->insert_print_enable( $_result[seq], $_result[product_id] , $_result[qty], $_result[warehouse], $pack_warehouse );
			// bug fix


			debug ( "DX00 check_pack: enable: $_result[enable],product_id: $_result[product_id],seq: $_result[seq], qty: $_result[qty], stock: $_current_info[stock],warehouse: $_result[warehouse]\n" );
		}
	}
// print_r ( $_arr_result );
	return $_arr_result;
    }

    function find_stock ( $product_id, $arr_warehouse )
    {
	// 재고가 어떤 창고에 있는지 찾는다.
	//$_stock_info = array( warehouse=>'C', stock=>10 );
	//return $_stock_info;

	for ( $i = 0; $i < count( $arr_warehouse ); $i++ )
	{
		$_warehouse = $arr_warehouse[$i];

		$current_stock = $this->get_current_stock( $product_id,  $_warehouse);

		if ( $current_stock > 0 )
		{
			$_stock_info[warehouse] = $_warehouse;
			$_stock_info[stock] = $current_stock;
			break;
		}
	}

	return $_stock_info;
    }

    //=============================================
    // 품절 여부 check
    // 품절 check 안 함
    function enable_sale( $product_id )
    {
	return 1;

	global $connect;
	$query = "select enable_sale from products where product_id='$product_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[enable_sale];

    }

    //==========================================
    //
    // tbl_print_enable table에 값 입력
    //
    function insert_print_enable( $seq, $product_id , $qty, $warehouse , $pack_warehouse = 0)
    {
	global $connect;

	return;

	// NOT USED ... 2007.12.14
	
	
	$product_name = class_product::get_product_name( $product_id );
	
	//============================================
	// warehouse 선택 - 이 부분은 업체 마다 다를 수 있음
	// $warehouse = substr( $product_name, 1,1);
	if ( $pack_warehouse )
		$query = "insert into tbl_print_enable set seq='$seq', product_id='$product_id', warehouse='$pack_warehouse', qty='$qty'";
	else
		$query = "insert into tbl_print_enable set seq='$seq', product_id='$product_id', warehouse='$warehouse', qty='$qty'";

	debug ( "print enable : $query " );

	if ( !$this->m_debug )
		$result = mysql_query ( $query, $connect );
	else
		"debug mode $query \n";

	if ( mysql_affected_rows() == 1)
	{
		$query = "update orders set warehouse='$warehouse' 
                           where (seq='$seq' or pack='$seq') 
                             and product_id='$product_id'";
		if ( !$this->m_debug );
			mysql_query ( $query, $connect );
	}
    }

}

