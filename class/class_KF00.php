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

	// pack_list�� null�� �ƴ� �ֹ��� ������ null�� ����� �ش�
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
        $transaction = $this->begin("���ֿ��ǥ");
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
  // rianrose�� ���� download
  //
  // order_status: 0: ��ü, 1: ����, 7: ����, 8: ����, 99: ���� + ����
  // query_type : 1: ������ ����, 3: pos����� ����
  function download2()
  {
	global $connect, $saveTarget, $download_type;
	global $order_status, $query_type;
	global $start_date, $end_date, $shop_id;

	//======================================
	// query ����
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

	// 2007.10.8 �߰�
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
	// ������ file�� ����
	// file open
	$handle = fopen ($saveTarget, "w");

	$_format = array (
		"�����"	=> "trans_date_pos",
		"�����ڵ�"	=> "_shop_code", 	// get_shopcode()
		"��ݱ���"	=> "_deliv_code", 	// get_delivcode()
		"��ǰ�ڵ�"	=> "product_id",
		"�����ᱸ��"	=> "_rate_type",
		"��������"	=> "_rate",
		"�����ǸŰ�"	=> "shop_price",
		"����"	 	=> "org_price",
		"���ܰ�"	=> "_deliv_price", 	// get_delivprice() shop_price * 0.8
		"������"	=> "qty",
		"�ֹ���ȣ"	=> "order_id",
		"����"	=> "order_name",
		"�ڵ���1"	=> "order_mobile",
		"�ڵ���2"	=> "recv_mobile",
		"��ȭ��ȣ1"	=> "order_tel",
		"��ȭ��ȣ2"	=> "recv_tel",
		"�ּ�1"		=> "order_address",
		"�ּ�2"		=> "recv_address",
		"��Ʈ��ȣ"	=> "_set_no"		// get_setno();
	);

	//====================================================
	//	
	// header ��� �κ� ����
	// date: 2007.3.19 - jk
	//
	$buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=euc-kr'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

	//======================================================
	// header ���
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
			case 10015: $data[_shop_code] = "R0004"; break;	// �ż��� 
			case 10038: $data[_shop_code] = "R0005"; break;	// �м� �÷���
			case 10043: $data[_shop_code] = "R0006"; break;	// �м� �÷���
			case 10076: $data[_shop_code] = "R0007"; break;	// ������ 
			case 10026: $data[_shop_code] = "R0008"; break;	// cj
			case 10007: $data[_shop_code] = "R0009"; break;	// cj
			case 10049: $data[_shop_code] = "R0010"; break;	// cj
		}

		// _shop_code�� ���� ���
		if ( !$data[_shop_code] )
			$data[_shop_code] = $data[shop_id];

		$data["_deliv_code"] = "0";	// 0: ���, 1: ��ǰ
		if ( $data[order_cs] == 3 )  	// ����� ��ҿϷ�
			$data["_deliv_code"] = "1";	// 0: ���, 1: ��ǰ

		//=======================================
		// ���� ��ǰ ���� check��
		if ( $data[packed] )
		{		
			// ���� ��ǰ�� ��� write
			$arr_pack_list = split ( ",", $data["pack_list"] );

			$i=0;
			$pack_count = count( $arr_pack_list );
			foreach ( $arr_pack_list as $product_id )
			{
				$data["options"] = $this->get_options( $product_id );
				$data["product_id"] = $product_id;

				// $i�� 0�̻��̸� ��������: 0, �����ǸŰ�: 0, ���ܰ�: 0	
				if ( $i == 0 )
				{
					
					$data["shop_price"] =  $data["shop_price"] / $pack_count;	// �ǸŰ�
					$data["_deliv_price"] =  $data["_deliv_price"] / $pack_count;	// ��� �ܰ�
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

				// ���� ��ǰ�� �ƴ� ���
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
			// ���� ��ǰ�� �ƴ� ���
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
	header("Content-Disposition: attachment; filename=�ֹ��ٿ�ε�_" . date('Ymd') . ".xls");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");

        if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
        } 

        ////////////////////////////////////// 
        // file close and delete it 
	// file�� ������
        fclose($fp);
	unlink($saveTarget);
  }

  //===========================================
  //
  // date: 2007.9.11
  // ���� ��ǰ ���� ��������
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
  // ���� �� ���� ��ǰ count
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
  // ���� �� ���� ��ǰ count
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
