<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_3pl_api.php";

////////////////////////////////
// class name: class_KF00
//

class class_KF00 extends class_top {

    ///////////////////////////////////////////

    function KF00()
    {
	global $connect, $start_date, $end_date, $shop_id, $status,$over_one;
	$sel_over_one[$over_one] = "checked";

	// pack_list가 null이 아닌 주문이 있으면 null로 만들어 준다
	// 2008.11.14
	$query = "select count(*) cnt from orders where packed=0 and pack_list is not null";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array( $result );

	if ( $_SESSION[USE_3PL] )
	    $api_3pl = new class_3pl_api();

	if ( $data[cnt] )
	{
	    $query = "update orders set pack_list = null where packed=0 and pack_list is not null";
	    $result = mysql_query ( $query, $connect );
	}

        if ( !$start_date )
        {
	    $timestamp = strtotime("-15 days");
            $start_date = date("Y-m-d",$timestamp);
        }

	global $template;
        $transaction = $this->begin("발주요약표");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    function get_price( $product_id )
    {
	global $shop_id, $connect;

	if ( substr( $product_id,0,1) == 'S' )
	{
	    $query = "select org_id from products where product_id='$product_id'";
	    $result = mysql_query( $query, $connect );
	    $data   = mysql_fetch_array( $result );
	    $product_id = $data[org_id];
	}

	$query = "select * from price_history where product_id='$product_id'";
	if ( $shop_id )
    	    $query .= " and shop_id='$shop_id'";

	$result = mysql_query( $query, $connect );

	if ( $shop_id && mysql_num_rows( $result ) == 0 )
	{
	    $query = "select * from price_history where product_id='$product_id'";
	    $result = mysql_query( $query, $connect );
debug ( "no shop code: $query " );
	}

	$data   = mysql_fetch_array( $result );

	return $data;	
    }

  //==================================
  // rianrose를 위한 download
  //
  // order_status: 0: 전체, 1: 접수, 7: 송장, 8: 발주, 99: 접수 + 송장
  // query_type : 1: 발주일 기준, 3: pos출고일 기준
  function download2()
  {
	global $connect, $saveTarget, $download_type;
	global $order_status, $query_type;
	global $start_date, $end_date, $shop_id;

	//======================================
	// query 생성
	if ( $query_type == 1 )
	{
		$_d_index = "collect_date";
		$query = "select * from orders where $_d_index >= '$start_date' and $_d_index <= '$end_date' ";
	}
	else
	{
		$_d_index = "trans_date_pos";
		$query = "select * from orders where $_d_index >= '$start_date 00:00:00' and $_d_index <= '$end_date 23:59:59' ";
	}
		

// echo $query;

	// 2007.10.8 추가
	if ( $shop_id )
		$query .= " and shop_id=$shop_id ";

	if ( $order_status )
		if ( $order_status == 99 )
			$query .= " and status in (7,8)";
		else
			$query .= " and status = $order_status";

	$query .= "  and order_cs not in ( 1,3 )";
	$result = mysql_query ( $query, $connect );

	//===================================
	// 저장할 file을 생성
	// file open
	$handle = fopen ($saveTarget, "w");

	$_format = array (
		"출고일"	=> "trans_date_pos",
		"매장코드"	=> "_shop_code", 	// get_shopcode()
		"출반구분"	=> "_deliv_code", 	// get_delivcode()
		"상품코드"	=> "product_id",
		"수수료구분"	=> "_rate_type",
		"수수료율"	=> "_rate",
		"현재판매가"	=> "shop_price",
		"원가"	 	=> "org_price",
		"출고단가"	=> "_deliv_price", 	// get_delivprice() shop_price * 0.8
		"출고수량"	=> "qty",
		"주문번호"	=> "order_id",
		"고객명"	=> "order_name",
		"핸드폰1"	=> "order_mobile",
		"핸드폰2"	=> "recv_mobile",
		"전화번호1"	=> "order_tel",
		"전화번호2"	=> "recv_tel",
		"주소1"		=> "order_address",
		"주소2"		=> "recv_address",
		"셋트번호"	=> "_set_no"		// get_setno();
	);

	//====================================================
	//	
	// header 출력 부분 결정
	// date: 2007.3.19 - jk
	//
	$buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=euc-kr'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

	//======================================================
	// header 출력
	$buffer .= "<tr>";
	foreach ( $_format as $key=>$value )
	{
		$buffer .= "<td>$key</td>";
	} 
	$buffer .= "</tr>";

	fwrite($handle, $buffer);
	$buffer = "";

	//===================================
	// save to file
	while ( $data = mysql_fetch_array ( $result ) ) 
	{
		$data["_rate"] = "20%";
		$data["_rate_type"] = "0";
		$data["_deliv_price"] = $data[shop_price] * 0.8 ;

		if ( preg_match("/SET_\[(.*)\]/", $data[product_name], $matches) )
			$data["_set_no"] = $matches[1];
		else
			$data["_set_no"] = "&nbsp;";

		switch ( $data[shop_id] )
		{
			case 10003: $data[_shop_code] = "R0001"; break;
			case 10009: $data[_shop_code] = "R0002"; break;
			case 10014: $data[_shop_code] = "R0003"; break;
			case 10015: $data[_shop_code] = "R0004"; break;	// 신세계 
			case 10038: $data[_shop_code] = "R0005"; break;	// 패션 플러스
			case 10043: $data[_shop_code] = "R0006"; break;	// 패션 플러스
			case 10076: $data[_shop_code] = "R0007"; break;	// 오가게 
			case 10026: $data[_shop_code] = "R0008"; break;	// cj
			case 10007: $data[_shop_code] = "R0009"; break;	// cj
			case 10049: $data[_shop_code] = "R0010"; break;	// cj
		}

		// _shop_code가 없을 경우
		if ( !$data[_shop_code] )
			$data[_shop_code] = $data[shop_id];

		$data["_deliv_code"] = "0";	// 0: 출고, 1: 반품
		if ( $data[order_cs] == 3 )  	// 배송후 취소완료
			$data["_deliv_code"] = "1";	// 0: 출고, 1: 반품

		//=======================================
		// 묶음 상품 여부 check함
		if ( $data[packed] )
		{		
			// 묶음 상품일 경우 write
			$arr_pack_list = split ( ",", $data["pack_list"] );

			$i=0;
			$pack_count = count( $arr_pack_list );
			foreach ( $arr_pack_list as $product_id )
			{
				$data["options"] = $this->get_options( $product_id );
				$data["product_id"] = $product_id;

				// $i가 0이상이면 수수료율: 0, 현재판매가: 0, 출고단가: 0	
				if ( $i == 0 )
				{
					
					$data["shop_price"] =  $data["shop_price"] / $pack_count;	// 판매가
					$data["_deliv_price"] =  $data["_deliv_price"] / $pack_count;	// 출고 단가
					/*
					$data["_shop_code"] = "&nbsp; ";
					$data["_deliv_code"] = "&nbsp; ";
					$data["_rate"] = "&nbsp; ";
					$data["trans_date_pos"] = "&nbsp; ";
					$data["order_id"] = "&nbsp; ";
					$data["order_name"] = "&nbsp; ";
					$data["order_tel"] = "&nbsp; ";
					$data["order_mobile"] = "&nbsp; ";
					$data["order_address"] = "&nbsp; ";
					$data["recv_tel"] = "&nbsp; ";
					$data["recv_mobile"] = "&nbsp; ";
					$data["recv_address"] = "&nbsp; ";
				//	$data["_set_no"] = "set no";
				//	$data["_shop_code"] = $data[shop_id];
				//	$data["_deliv_code"] = "_deliv_code";
					*/
				}

				// 묶음 상품이 아닐 경우
				$buffer .= "<tr>";
				foreach ( $_format as $key=>$value )
				{
					if ( $value == "trans_date_pos" )
					    $data[$value] = substr( $data[$value], 0, 10 );
					else if ( $value == "org_price" )
					{
					    $_pinfos = class_product::get_info( $data[product_id] );
					    $data[$value] = $_pinfos[org_price];
					}

	
					$buffer .= "<td>$data[$value]</td>";
				} 
				$buffer .= "</tr>";

				$i++;
			}
		}
		else
		{
			// 묶음 상품이 아닐 경우
			$buffer = "<tr>";
			foreach ( $_format as $key=>$value )
			{
				if ( $value == "trans_date_pos" )
					$data[$value] = substr( $data[$value], 0, 10 );
				else if ( $value == "org_price" )
				{
				    $_pinfos = class_product::get_info( $data[product_id] );
				    $data[$value] = $_pinfos[org_price];
				}

				$buffer .= "<td>$data[$value]</td>";
			} 
			$buffer .= "</tr>";
		}

		fwrite($handle, $buffer);
		$buffer = "";
	}
	fwrite( $handle, "</table>" );
	fclose( $handle );	

	//========================================
	// download	
	//
	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=주문다운로드_" . date('Ymd') . ".xls");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");

        if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
        } 

        ////////////////////////////////////// 
        // file close and delete it 
	// file은 보관함
        fclose($fp);
	unlink($saveTarget);
  }

  //===========================================
  //
  // date: 2007.9.11
  // 묶음 상품 정보 가져오기
  //
  function get_options( $product_id )
  {
	global $connect;
	$query = "select options from products where product_id ='$product_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	
	return $data[options];
  }

  /////////////////////////////////////
  //
  // date: 2008.6.10 - jk
  // 조건 중 묶음 상품 count
  //
  function get_packed_count( $product_id, $query_option )
  {
	global $connect;

	$query = "select count(*) cnt from orders a where a.packed=1 $query_option and a.pack_list like '%$product_id%'";

    //debug ( "[get_packed_count] $query" );

// debug
// if ( $_SESSION[LOGIN_LEVEL] == 9 ) echo $query . "<br>";

	$result = mysql_query( $query, $connect );
	$data  = mysql_fetch_array ( $result );

	return $data[cnt];
  }
 
  /////////////////////////////////////
  //
  // date: 2008.6.10 - jk
  // 조건 중 묶음 상품 count
  //
  function get_single_count( $product_id, $query_option )
  {
	global $connect;

	$query = "select count(*) cnt from orders a where a.packed=0 $query_option and a.product_id = '$product_id'";

//if ( $product_id == "S00992" )
    //debug ( "[get_single_count] $query" );

// debug
// if ( $_SESSION[LOGIN_LEVEL] == 9 ) echo $query . "<br>";
	$result = mysql_query( $query, $connect );
	$data  = mysql_fetch_array ( $result );

	return $data[cnt];
  }
 
}

?>
