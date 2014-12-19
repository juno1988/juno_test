<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

require_once "class_stock.php";
require_once "class_DX00.php";

//////////////////////////////////////////////
class class_I800 extends class_top
{
  //////////////////////////////////////////////////////
  // 상품 리스트 
  function I800()
  {
      global $template;
      global $connect, $supply_code;

      $query = "select count(*) cnt from orders where pack=''";
      $result = mysql_query( $query, $connect );
      $data   = mysql_fetch_assoc( $result );
      if ( $data[cnt] > 0 )
      {
          $query = "select update orders set pack=null where pack=''";
          mysql_query( $query, $connect );
      }

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  //****
  // 재고 조회 new
  // 2009.3.2 - jk
  function show_sublist()
  {
	global $connect, $product_id, $start_date;

  	$sql        = "select alarm_qty, start_date from ez_jaegolist where product_id = '$product_id'";
  	$list       = mysql_fetch_array(mysql_query($sql, $connect));
  	$alarm_qty  = $list[alarm_qty];
  	$start_date = $list[start_date];
  	$base_date  = $start_date;
  	$today      = date("Y-m-d");

	//********************************
	// 재고조회 환경 설정값 조회 
	$sql = "select * from ez_config";
  	$config = mysql_fetch_array(mysql_query($sql, $connect));

  	if ($config[jaego_basedt] == 0) 
      	    $options = "and collect_date >= '$start_date'";
  	else if ($config[jaego_basedt] == 1)
      	    $options = "and substring(trans_date,1,10) >= '$start_date'";
  	else if ($config[jaego_basedt] == 2)
      	    $options = "and substring(trans_date_pos,1,10) >= '$start_date' and status = 8";

	if (_DOMAIN_ == "ozen") $orderby = "options";
  	    else $orderby = "product_id";

	// array for return 
	$arr_info = array();

	//****************************
	// 하부 상품 검색
	$sql = "select * from products 
		where org_id = '$product_id' and is_delete = 0 order by ${orderby}";
	// $arr_info[sql]  = $sql;

  	$result = mysql_query($sql, $connect) or die(mysql_error());
  	$rows = @mysql_num_rows($result2);

  	if ($rows == 0 && (_DOMAIN_ == "sabina" || _DOMAIN_ == "lovehouse" || _DOMAIN_ == "whales"))
  	{
    	    $sql = "select * from products where product_id = '$product_id' and is_delete = 0 order by ${orderby}";
    	    $result = mysql_query($sql, $connect) or die(mysql_error());
  	}

	//**************
	//
	// logic start 2009.3.2 - jk
	//
	$arr_info[products] = array();
        $i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{	
	    $i++;
	    if ( $i == 1 )
	        $arr_info[product_name] = iconv('cp949', 'utf-8', $data[name]);

	    $_info = array();
	    $_info['options']      = iconv('cp949', 'utf-8', $data[options]);
	    $_info['product_id']   = $data['product_id'];

	    // 1. 입고
	    // 1. end of 입고
	    $_info[stock_in] = $this->get_stockin( $data[product_id] );

	    // 2. 출고
	    $_info[stock_out] = $this->get_stockout( $data[product_id] );

	    // 3. 매출
	    // 3.1 일반
	    $_info[sale] = $this->get_sale( $data[product_id], $options );
	        
	    // 3.2 묶음 
	    $_info[sale_pack ] = $this->get_sale_pack( $data[product_id], $options );
	    $_info[sale]       = $_info[sale] + $_info[sale_pack];

	    // 4. 재고수량
	    $_info[remain]   = $_info[stock_in] - $_info[stock_out] - $_info[sale];

	    $arr_info[products][] = $_info;
	}
	echo json_encode( $arr_info );
    }


    // 묶음 판매 정보
    function get_sale_pack( $product_id, $query_options )
    {
	global $connect;

	$query = "select pack_list, qty
                    from orders
                   where pack_list is not null and pack_list like '%$product_id%' 
		     and order_cs not in (1,3,12)
		         $query_options";

	$result = mysql_query( $query, $connect );

	$qty = 0;	
	while( $data = mysql_fetch_array( $result ) )
	{
	    $_arr = split(',', $data[pack_list] );
	    $_qty = 0;
    	    foreach ( $_arr as $p )
    	    {
                if ( $p == $product_id )
                    $_qty++;    
    	    }
    	    $qty = $qty + $_qty * $data[qty];
	}
	return $qty;
    }

    function get_sale( $product_id , $query_options )
    {
	global $connect;

	$query = "select sum(qty) qty
                    from orders
                   where product_id = '$product_id' 
		     and packed=0
		     and order_cs not in (1,3,12)
		         $query_options";

	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_array( $result );

	return $data[qty] ? $data[qty] : 0;
    }

    function get_stockout( $product_id )
    {
	global $connect;

        $sql  = "select sum(qty) qty 
                   from ez_jaego_inout 
                  where product_id = '$product_id' and type = 2";	// 출고량 합계
        $data = mysql_fetch_array(mysql_query($sql, $connect));
	return $data[qty] ? $data[qty] : 0;
    } 

    function get_stockin( $product_id )
    {
      	global $connect;

      	$sql = "select sum(qty) qty 
          from ez_jaego_inout 
          where product_id = '" . $product_id . "' 
          and type = 1";	// 입고량 합계
      	$data = mysql_fetch_array(mysql_query($sql, $connect));
	return $data[qty] ? $data[qty] : 0;
    }

    // 재고 추가
    // jkryu 2009.4.8
    function add()
    {
	global $connect,$product_id, $start_date, $qty, $type, $memo, $warehouse;

	$memo = iconv('utf-8', 'cp949', $memo );

	$arr_data = array();
	$arr_data[product_id] = $product_id;
	$arr_data[start_date] = $start_date;


	$query = "insert into ez_jaego_inout 
                     set product_id = '$product_id'
                         ,start_date = '$start_date'
                         ,type       = '$type'
                         ,qty        = '$qty'
			 ,memo       = '$memo'
			";

	if ( _DOMAIN_ == "ckcompany" )
            $query .= ",warehouse  = '$warehouse'";
debug ( $query );

	mysql_query( $query, $connect );
	$arr_data[query] = $query;
	echo json_encode( $arr_data );
    }

  function addall()
  {
	global $template;
	global $connect;

	$id_list = $_REQUEST[id_list];
	$start_date = $_REQUEST[start_date];
	$alarm_qty = $_REQUEST[alarm_qty];

	$id_list = str_replace("'", "", stripslashes($id_list));
	$arr_id = split(",", $id_list);

	for ($i = 0; $i < sizeof($arr_id); $i++)
	{
	    $product_id = $arr_id[$i];
	    $sql = "select product_id from ez_jaegolist where product_id = '$product_id' and is_delete = 0";
	    $result = mysql_query($sql, $connect) or die(mysql_error());
	    $list = mysql_fetch_array($result);

	    if (!$list)
	    {
	      $sql = "insert into ez_jaegolist set
			product_id = '$product_id',
			start_date = '$start_date',
			input_time = now(),
			alarm_qty = '$alarm_qty'";
	      mysql_query($sql, $connect) or die(mysql_error());
	    }
	}
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I800&show_status=0");
	exit;
  }   

  function I801()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I802()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I803()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I804()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I805()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I806()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I807()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I808()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I809()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }
  function I810()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function save()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[product_id];
	$start_date = $_REQUEST[start_date];
	$alarm_qty = $_REQUEST[alarm_qty];
  	$sql = "update ez_jaegolist set
			start_date = '$start_date',
			alarm_qty = '$alarm_qty'
		 where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I800");
	exit;
  }

  function delete()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[id];
	$sql = "delete from ez_jaegolist where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());

	$sql = "delete from ez_jaego_inout where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());

	$this->redirect("template.htm?template=I800");
	exit;
  }

  function deleteall()
  {
	global $template;
	global $connect;

	$id_list = $_REQUEST[id_list];
	$id_list = str_replace("'", "", stripslashes($id_list));
	$arr_id = split(",", $id_list);

	for ($i = 0; $i < sizeof($arr_id); $i++)
	{
	    $product_id = $arr_id[$i];

	    $sql = "delete from ez_jaegolist where product_id = '$product_id'";
	    mysql_query($sql, $connect) or die(mysql_error());

	    $sql = "delete from ez_jaego_inout where product_id = '$product_id'";
	    mysql_query($sql, $connect) or die(mysql_error());

	    // S0000 과 같은 하부상품들도 다 지운다.
	    $sql = "select product_id from products where org_id = '$product_id'";
	    $result = mysql_query($sql, $connect) or die(mysql_error());
	    while ($list = mysql_fetch_array($result))
	    {
		$sql = "delete from ez_jaegolist where product_id = '$list[product_id]'";
		mysql_query($sql, $connect) or die(mysql_error());

		$sql = "delete from ez_jaego_inout where product_id = '$list[product_id]'";
		mysql_query($sql, $connect) or die(mysql_error());
	    }
	}
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I800");
	exit;
  }   

  function delete_jaego()
  {
	global $template;
	global $connect;

	$no = $_REQUEST[no];
	$product_id = $_REQUEST[product_id];

	$sql = "delete from ez_jaego_inout where no = '$no'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href='popup.htm?template=$template&product_id=$product_id';</script>";
	exit;
  }

  function save_file()
  {
	global $connect;
	$obj_file   = new class_file();

	$header_items = array ("관리시작일", "공급처이름", "사입처상품명", "상품코드", "상품명", "판매가", "공급가", "원가",  "입고", "출고", "판매", "재고",   "경고잔량", "등록일시", "상태");

	$arr_datas[] = $header_items;

	/////////////////////////////////////////////////////////        
	// 1. 기본 상품 리스트
	if ($keyword) 
	{
	    $cmp_option = "";
	    if ($key_type == 1)         // 상품명
	    {
		$key_option = " and b.name like '%$keyword%'";
		$cmp_option = " and a.product_id = b.product_id";
	    }
	    else                        // 상품코드
	    {
		$key_option = " and b.product_id = '$keyword'";
		if (substr($keyword,0,1) == "S")
		  $cmp_option = " and a.product_id = b.org_id";
		else
		  $cmp_option = " and a.product_id = b.product_id";
	    }

	    if ($show_status == 1) $options = " and a.status = 1";

	    $sql = "select a.* from ez_jaegolist a, products b 
	     	     where b.is_delete = 0 
			   ${key_option}
			   ${cmp_option}
			   ${options}
	     	     order by a.product_id asc";
	}
	else
	{
	    if ($show_status == 1) $options = " and status = 1";
	    $sql = "select * from ez_jaegolist 
		     where is_delete = 0 ${options} 
		     order by input_time desc, product_id asc";
	}

	// $sql .= " limit 10";

	/////////////////////////////////////////////////////////////
	$record = 0;
	$row = 1;
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$total_rows = @mysql_num_rows($result);
	while ($list = mysql_fetch_array($result))
	{

	    echo "#";
	    $record++;
	    $sql = "select * from products where product_id = '$list[product_id]'";
	    $list1 = mysql_fetch_array(mysql_query($sql, $connect));

	    $sql = "select * from price_history where product_id = '$list[product_id]' 
 			and  (shop_id = '' or shop_id is NULL) order by seq desc limit 1";
	    $pricelist = mysql_fetch_array(mysql_query($sql, $connect));

	    $product_id = $list[product_id];
	    $product_name = $list1[name];
	    $stock_manage = $list1[stock_manage];
	    $alarm_qty    = $list[alarm_qty];
	    $start_date   = $list[start_date];
	    $vendor_name  = $this->get_supply_name2($list1[supply_code]);
	    $brand 	  = $list1[brand];
	    if ($list[status] == 1) 
	    {
		$alarm_status = "경고";
		$format = $format_alarm;
	    }
	    else 
	    {
		$alarm_status = "";
		$format = "";
	    }

	    $_row = array( 
		$start_date
		,$vendor_name
		,$brand
		,$product_id
		,$product_name
		,$pricelist[shop_price]
		,$pricelist[supply_price]
		,$pricelist[org_price]
		,""
		,""
		,""
                , $alarm_qty
		,substr($list[input_time],0,10)
		,"");

	    $arr_datas[] = $_row;

	    // 하부 리스트 출력
	    $sql = "select * from ez_config";
	    $config = mysql_fetch_array(mysql_query($sql, $connect));

	    // 0 :발주기준 , 1:송장입력기준, 2:포스출고기준
	    if ($config[jaego_basedt] == 0)
		$options = "and collect_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 1)
		$options = "and trans_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 2)
		$options = "and trans_date_pos >= '$start_date' and status = 8";
	
	    // 옵션별 발주인 경우...
	    if ($stock_manage == 1)
	    {
		$total_in_qty = "";
		$total_out_qty = "";
		$total_sale_qty = "";
		$total_remain = "";
		$sql = "select * from products where org_id = '$product_id' and is_delete = 0 order by product_id";
		$result2 = mysql_query($sql, $connect) or die(mysql_error());
		while ($list2 = mysql_fetch_array($result2))
		{
            	    //***********************************************************************
            	    // 파일 다운로드 수정... 2009-04-01 jkh

    	    	    // 1. 입고
    	    	    // 1. end of 입고
    	    	    $in_qty = $this->get_stockin( $list2[product_id] );
   	     
    	    	    // 2. 출고
    	    	    $out_qty = $this->get_stockout( $list2[product_id] );
   	     
    	    	    // 3. 매출
    	    	    // 3.1 일반
    	    	    $sale_qty1 = $this->get_sale( $list2[product_id], $options );
    	       	     
    	    	    // 3.2 묶음 
    	    	    $sale_qty2 = $this->get_sale_pack( $list2[product_id], $options );
    	    	    $sale_qty       = $sale_qty1 + $sale_qty2;
    
		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

		    // sublist #1
		    $_row = array( ""
			, $vendor_name
			, $brand
			, $list2[product_id]
			, $list2[options]
			, ""
			, ""
			, ""
			, $in_qty
			, $out_qty
			, $sale_qty
			, $remain
			, ""
			, ""
			, $status); 
	    	    $arr_datas[] = $_row;

		    $row++;
		    $total_in_qty += $in_qty;
		    $total_out_qty += $out_qty;
		    $total_sale_qty += $sale_qty;
		    $total_remain += $remain;
		}

		// 합계 출력
	  	$_row = array( ""
			, $vendor_name
			, ""
			, "합계"
			, ""
			, $pricelist[shop_price] * $total_sale_qty
			, ""
			, ""
			, $total_in_qty
			, $total_out_qty
			, $total_sale_qty
			, $total_remain);

	    	$arr_datas[] = $_row;
		$row++;
	    }
	    else
	    {
		    //***********************************************************************
		    // 파일 다운로드 수정... 2009-04-01 jkh

		    // 1. 입고
		    // 1. end of 입고
		    $in_qty = $this->get_stockin( $product_id );
	    
		    // 2. 출고
		    $out_qty = $this->get_stockout( $product_id );
	    
		    // 3. 매출
		    // 3.1 일반
		    $sale_qty1 = $this->get_sale( $product_id, $options );
			
		    // 3.2 묶음 
		    $sale_qty2 = $this->get_sale_pack( $product_id, $options );
		    $sale_qty       = $sale_qty1 + $sale_qty2;

		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

		    // sublist #1
		    $_row = array(
				""
				,$vendor_name
				,$brand
				,$product_id
				,"[원본]".$product_name
				,""
				,""
				,""
				,$in_qty
				,$out_qty
				, $sale_qty
				, $remain
				, ""
				, ""
				, $status
				);
	    	    $arr_datas[] = $_row;
	    	    $row++;
	    }
	}

        return $obj_file->save_file( $arr_datas, "$user_id/stock_list.xls" );
  }

  //////////////////////////////////
  // 재고 다운로드 로직
  // 2009.2.2 - jk 확인
  function download()
  {
	global $connect;
	global $template;

	$show_status = $_REQUEST[show_status];
	$keyword = $_REQUEST[keyword];
	$key_type = $_REQUEST[key_type];

	$today = date("Y-m-d");
	require_once 'Spreadsheet/Excel/Writer.php';

	$xls_name = $today."-MyJaego.xls";

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('재고자료');

	$worksheet->setColumn(2, 2, 30);
	$format_header =& $workbook->addFormat();
	$format_header->setAlign('center');
	$format_header->setBold();

	$format_header->setFgColor('cyan');
	$format_header->setBgColor('black');
	$format_header->setPattern(1);

	////////////////////////////////////////////////////////////////
	$header_items = array ("관리시작일", "공급처이름", "사입처상품명", "상품코드", "상품명", "판매가", "공급가", "원가",  "입고", "출고", "판매", "재고",   "경고잔량", "등록일시", "상태");
	$col = 0;
	foreach ($header_items as $item)
	{
	    $worksheet->write(0, $col, $item, $format_header);
	    $col++;
	}

	/////////////////////////////////////////////////////////        
	// 1. 기본 상품 리스트
	if ($keyword) 
	{
	    $cmp_option = "";
	    if ($key_type == 1)         // 상품명
	    {
		$key_option = " and b.name like '%$keyword%'";
		$cmp_option = " and a.product_id = b.product_id";
	    }
	    else                        // 상품코드
	    {
		$key_option = " and b.product_id = '$keyword'";
		if (substr($keyword,0,1) == "S")
		  $cmp_option = " and a.product_id = b.org_id";
		else
		  $cmp_option = " and a.product_id = b.product_id";
	    }

	    if ($show_status == 1) $options = " and a.status = 1";

	    $sql = "select a.* from ez_jaegolist a, products b 
	     	     where b.is_delete = 0 
			   ${key_option}
			   ${cmp_option}
			   ${options}
	     	     order by a.product_id asc";
	}
	else
	{
	    if ($show_status == 1) $options = " and status = 1";
	    $sql = "select * from ez_jaegolist 
		     where is_delete = 0 ${options} 
		     order by input_time desc, product_id asc";
	}

	$format_alarm =& $workbook->addFormat();
	$format_alarm->setFgColor(51);
	$format_alarm->setBgColor('black');
	$format_alarm->setPattern(1);

	$format_alarm2 =& $workbook->addFormat();
	$format_alarm2->setFgColor(43);
	$format_alarm2->setBgColor('black');
	$format_alarm2->setPattern(1);

	/////////////////////////////////////////////////////////////
	$record = 0;
	$row = 1;
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$total_rows = @mysql_num_rows($result);
	while ($list = mysql_fetch_array($result))
	{
	    $record++;
	    debug("$record 건 / 전체 $total_rows");
	    $sql = "select * from products where product_id = '$list[product_id]'";
	    $list1 = mysql_fetch_array(mysql_query($sql, $connect));

	    $sql = "select * from price_history where product_id = '$list[product_id]' 
 			and  (shop_id = '' or shop_id is NULL) order by seq desc limit 1";
	    $pricelist = mysql_fetch_array(mysql_query($sql, $connect));

	    $product_id = $list[product_id];
	    $product_name = $list1[name];
	    $stock_manage = $list1[stock_manage];
	    $alarm_qty    = $list[alarm_qty];
	    $start_date   = $list[start_date];
	    $vendor_name  = $this->get_supply_name2($list1[supply_code]);
	    $brand 	  = $list1[brand];
	    if ($list[status] == 1) 
	    {
		$alarm_status = "경고";
		$format = $format_alarm;
	    }
	    else 
	    {
		$alarm_status = "";
		$format = "";
	    }

	    // 원본 #1
	    $worksheet->write($row, 0, $start_date, $format);
	    $worksheet->write($row, 1, $vendor_name, $format);
	    $worksheet->write($row, 2, $brand, $format);
	    $worksheet->writeString($row, 3, $product_id, $format);
	    $worksheet->write($row, 4, $product_name, $format);
	    $worksheet->write($row, 5, $pricelist[shop_price], $format);
	    $worksheet->write($row, 6, $pricelist[supply_price], $format);
	    $worksheet->write($row, 7, $pricelist[org_price], $format);
	    $worksheet->write($row, 8, "", $format);
	    $worksheet->write($row, 9, "", $format);
	    $worksheet->write($row, 10, "", $format);
	    $worksheet->write($row, 11, "", $format);
	    $worksheet->write($row, 12, $alarm_qty, $format);
	    $worksheet->write($row, 13, substr($list[input_time],0,10), $format);
	    $worksheet->write($row, 14, "", $format);
	    $row++;

	    // 하부 리스트 출력
	    $sql = "select * from ez_config";
	    $config = mysql_fetch_array(mysql_query($sql, $connect));

	    // 0 :발주기준 , 1:송장입력기준, 2:포스출고기준
	    if ($config[jaego_basedt] == 0)
		$options = "and collect_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 1)
		$options = "and trans_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 2)
		$options = "and trans_date_pos >= '$start_date' and status = 8";
	
	    // 옵션별 발주인 경우...
	    if ($stock_manage == 1)
	    {
		$total_in_qty = "";
		$total_out_qty = "";
		$total_sale_qty = "";
		$total_remain = "";
		$sql = "select * from products where org_id = '$product_id' and is_delete = 0 order by product_id";
		$result2 = mysql_query($sql, $connect) or die(mysql_error());
		while ($list2 = mysql_fetch_array($result2))
		{
/*
		    $sql = "select sum(qty) in_qty from ez_jaego_inout where product_id = '$list2[product_id]' and type = 1"; // 입고량 합계
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $in_qty = $list3[in_qty];

		    // 출고량 합계
		    $sql     = "select sum(qty) out_qty from ez_jaego_inout 
                                 where product_id = '$list2[product_id]' and type = 2";        		    
		    $list3   = mysql_fetch_array(mysql_query($sql, $connect));
		    $out_qty = $list3[out_qty];

		    // jk 수정 / 취소 완료건만 제외
		    $sql = "select sum(qty) sale_qty from orders 
                             where product_id = '$list2[product_id]' 
                               and shop_id   != '' 
                               and order_cs not in (1,3,12) ${options}";

		    // 출고 합계에서 주문의 취소건을 뺀다

		    // $sql = "select sum(qty) sale_qty from orders where product_id = '$list2[product_id]' and shop_id != '' and order_cs not in (1,3,12) ${options}";
// debug($sql);
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));

		    // 묶음 상품 판매량 추가
		    $sql = "select sum(qty) sale_qty from orders where pack_list is not null and pack_list like '%${list2[product_id]}%' and shop_id != '' and order_cs not in (1,3,12) ${options}";
		    $list4 = mysql_fetch_array(mysql_query($sql, $connect));

		    $sale_qty = $list3[sale_qty] + $list4[sale_qty];
*/
            //***********************************************************************
            // 파일 다운로드 수정... 2009-04-01 jkh

    	    // 1. 입고
    	    // 1. end of 입고
    	    $in_qty = $this->get_stockin( $list2[product_id] );
    
    	    // 2. 출고
    	    $out_qty = $this->get_stockout( $list2[product_id] );
    
    	    // 3. 매출
    	    // 3.1 일반
    	    $sale_qty1 = $this->get_sale( $list2[product_id], $options );
    	        
    	    // 3.2 묶음 
    	    $sale_qty2 = $this->get_sale_pack( $list2[product_id], $options );
    	    $sale_qty       = $sale_qty1 + $sale_qty2;
    

		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

		    // sublist #1
		    $worksheet->write($row, 0, "");
		    $worksheet->write($row, 1, $vendor_name, $format);
		    $worksheet->write($row, 2, $brand, $format);
		    $worksheet->writeString($row, 3, $list2[product_id], $format);
		    $worksheet->write($row, 4, $list2[options], $format);
		    $worksheet->write($row, 5, "", $format);
		    $worksheet->write($row, 6, "", $format);
		    $worksheet->write($row, 7, "", $format);
		    $worksheet->write($row, 8, $in_qty, $format);
		    $worksheet->write($row, 9, $out_qty, $format);
		    $worksheet->write($row, 10, $sale_qty, $format);
		    $worksheet->write($row, 11, $remain, $format);
		    $worksheet->write($row, 12, "", $format);
		    $worksheet->write($row, 13, "", $format);
		    $worksheet->write($row, 14, $status, $format);

		    $row++;
		    $total_in_qty += $in_qty;
		    $total_out_qty += $out_qty;
		    $total_sale_qty += $sale_qty;
		    $total_remain += $remain;
		}

		// 합계 출력
		$worksheet->write($row, 0, "");
		$worksheet->write($row, 1, $vendor_name, $format);
		$worksheet->write($row, 2, "");
		$worksheet->writeString($row, 3, "합계", $format);
		$worksheet->write($row, 4, "", $format);
		$worksheet->write($row, 5, $pricelist[shop_price] * $total_sale_qty, $format);
		$worksheet->write($row, 6, "", $format);
		$worksheet->write($row, 7, "", $format);
		$worksheet->write($row, 8, $total_in_qty, $format);
		$worksheet->write($row, 9, $total_out_qty, $format);
		$worksheet->write($row, 10, $total_sale_qty, $format);
		$worksheet->write($row, 11, $total_remain, $format);
		$row++;
	    }
	    else
	    {
/*
		    $sql = "select sum(qty) in_qty from ez_jaego_inout where product_id = '$product_id' and type = 1";        // 입고량 합계
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $in_qty = $list3[in_qty];

		    $sql = "select sum(qty) out_qty from ez_jaego_inout where product_id = '$product_id' and type = 2";       // 출고량 합계
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $out_qty = $list3[out_qty];

		    $sql = "select sum(qty) sale_qty from orders where product_id = '$product_id' and shop_id != '' and order_cs not in (1,3,12) ${options}";
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));

		    // 묶음 상품 판매량 추가
		    $sql = "select sum(qty) sale_qty from orders where pack_list is not null and pack_list like '%${product_id}%' and shop_id != '' and order_cs not in (1,3,12) ${options}";
		    $list4 = mysql_fetch_array(mysql_query($sql, $connect));

		    $sale_qty = $list3[sale_qty] + $list4[sale_qty];
*/
            //***********************************************************************
            // 파일 다운로드 수정... 2009-04-01 jkh

    	    // 1. 입고
    	    // 1. end of 입고
    	    $in_qty = $this->get_stockin( $product_id );
    
    	    // 2. 출고
    	    $out_qty = $this->get_stockout( $product_id );
    
    	    // 3. 매출
    	    // 3.1 일반
    	    $sale_qty1 = $this->get_sale( $product_id, $options );
    	        
    	    // 3.2 묶음 
    	    $sale_qty2 = $this->get_sale_pack( $product_id, $options );
    	    $sale_qty       = $sale_qty1 + $sale_qty2;

		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

		    // sublist #1
		    $worksheet->write($row, 0, "");
		    $worksheet->write($row, 1, $vendor_name, $format);
		    $worksheet->write($row, 2, $brand, $format);
		    $worksheet->writeString($row, 3, $product_id, $format);
		    $worksheet->write($row, 4, "[원본]".$product_name, $format);
		    $worksheet->write($row, 5, "", $format);
		    $worksheet->write($row, 6, "", $format);
		    $worksheet->write($row, 7, "", $format);
		    $worksheet->write($row, 8, $in_qty, $format);
		    $worksheet->write($row, 9, $out_qty, $format);
		    $worksheet->write($row, 10, $sale_qty, $format);
		    $worksheet->write($row, 11, $remain, $format);
		    $worksheet->write($row, 12, "", $format);
		    $worksheet->write($row, 13, "", $format);
		    $worksheet->write($row, 14, $status, $format);

	    	    $row++;
	    }
	}

	////////////////////////////////////
	// Let's send the file
	// sending HTTP headers
	$workbook->send($xls_name);
	$workbook->close();
  }



  //////////////////////////////////
  function download2()
  {
	global $connect;
	global $template;

	$show_status = $_REQUEST[show_status];
	$keyword = $_REQUEST[keyword];
	$key_type = $_REQUEST[key_type];

	$today = date("Y-m-d");
	require_once 'Spreadsheet/Excel/Writer.php';

	$xls_name = $today."-MyJaego.xls";

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('재고자료');

	$worksheet->setColumn(4, 4, 30);
	$format_header =& $workbook->addFormat();
	$format_header->setAlign('center');
	$format_header->setBold();

	$format_header->setFgColor('cyan');
	$format_header->setBgColor('black');
	$format_header->setPattern(1);

	$table_name = "I800_jaego_tbl";
	$sql = "select * from $table_name order by row";
	$result = mysql_query($sql, $connect) or die(mysql_error());

	////////////////////////////////////////////////////////////////
	$header_items = array ("관리시작일", "공급처이름", "사입처상품명", "상품코드", "상품명", "판매가", "공급가", "원가",  "입고", "출고", "판매", "재고",   "경고잔량", "등록일시", "상태");
	$col = 0;
	foreach ($header_items as $item)
	{
	    $worksheet->write(0, $col, $item, $format_header);
	    $col++;
	}

	// 1. 기본 상품 리스트
	$format_alarm =& $workbook->addFormat();
	$format_alarm->setFgColor(51);
	$format_alarm->setBgColor('black');
	$format_alarm->setPattern(1);

	$format_alarm2 =& $workbook->addFormat();
	$format_alarm2->setFgColor(43);
	$format_alarm2->setBgColor('black');
	$format_alarm2->setPattern(1);

	/////////////////////////////////////////////////////////////
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$total_rows = @mysql_num_rows($result);
	while ($list = mysql_fetch_array($result))
	{
	    $shop_price   = $list[shop_price]   ? $list[shop_price]   : "";
	    $supply_price = $list[supply_price] ? $list[supply_price] : "";
	    $org_price    = $list[org_price]    ? $list[org_price]    : "";

	    $stock_in     = $list[stock_in]     ? $list[stock_in] : "";
	    $stock_out    = $list[stock_out]    ? $list[stock_out] : "";
	    $sale_stock   = $list[sale_stock]   ? $list[sale_stock] : "";
	    $alert_stock  = $list[alert_stock]  ? $list[alert_stock] : "";
	    $reg_date     = ($list[reg_date] == "0000-00-00") ? "" : $list[reg_date];
	    
	    // Row Data
	    $worksheet->write($list[row], 0,  $list[start_date], $format);
	    $worksheet->write($list[row], 1,  $list[vendor_name], $format);
	    $worksheet->write($list[row], 2,  $list[brand], $format);
	    $worksheet->writeString($list[row], 3, $list[product_id], $format);
	    $worksheet->write($list[row], 4,  $list[product_name], $format);
	    $worksheet->write($list[row], 5,  "." . $shop_price, $format);
	    $worksheet->write($list[row], 6,  $supply_price, $format);
	    $worksheet->write($list[row], 7,  $org_price, $format);
	    $worksheet->write($list[row], 8,  $stock_in, $format);
	    $worksheet->write($list[row], 9,  $stock_out, $format);
	    $worksheet->write($list[row], 10, $sale_stock, $format);
	    $worksheet->write($list[row], 11, $list[current_stock], $format);
	    $worksheet->write($list[row], 12, $alert_stock, $format);
	    $worksheet->write($list[row], 13, $reg_date, $format);
	    $worksheet->write($list[row], 14, $list[status], $format);

	}

	////////////////////////////////////
	// Let's send the file
	// sending HTTP headers
	$workbook->send($xls_name);
	$workbook->close();
  }

  function make_jaego_current()
  {
	global $connect;
	global $template;

	$table_name = "I800_jaego_tbl";

        echo "<script>show_waiting()</script>";
        flush();

	///////////////////////////////////
	// $header_items = array ("관리시작일", "공급처이름", "사입처상품명", "상품코드", "상품명", "판매가", "공급가", "원가",  "입고", "출고", "판매", "재고",   "경고잔량", "등록일시", "상태");

	//////////////////////////////////////////////
	// 1. make temporary table
	@mysql_query("truncate table ${table_name}", $connect);

	/////////////////////////////////////////////////////////        
	// 1. 기본 상품 리스트
	if ($keyword) 
	{
	    $cmp_option = "";
	    if ($key_type == 1)         // 상품명
	    {
		$key_option = " and b.name like '%$keyword%'";
		$cmp_option = " and a.product_id = b.product_id";
	    }
	    else                        // 상품코드
	    {
		$key_option = " and b.product_id = '$keyword'";
		if (substr($keyword,0,1) == "S")
		  $cmp_option = " and a.product_id = b.org_id";
		else
		  $cmp_option = " and a.product_id = b.product_id";
	    }

	    if ($show_status == 1) $options = " and a.status = 1";

	    $sql = "select a.* from ez_jaegolist a, products b 
	     	     where b.is_delete = 0 
			   ${key_option}
			   ${cmp_option}
			   ${options}
	     	     order by a.product_id asc";
	}
	else
	{
	    if ($show_status == 1) $options = " and status = 1";
	    $sql = "select * from ez_jaegolist 
		     where is_delete = 0 ${options} 
		     order by input_time desc, product_id asc";
	}


	/////////////////////////////////////////////////////////////
	$record = 0;
	$row = 1;
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$total_rows = @mysql_num_rows($result);
	while ($list = mysql_fetch_array($result))
	{
	    $record++;

	    debug("$record 건 / 전체 $total_rows : $product_id");
	    $sql = "select * from products where product_id = '$list[product_id]'";
	    $list1 = mysql_fetch_array(mysql_query($sql, $connect));

	    $sql = "select * from price_history where product_id = '$product_id' 
 			and  (shop_id = '' or shop_id is NULL) order by seq desc limit 1";
	    $pricelist = mysql_fetch_array(mysql_query($sql, $connect));

	    $product_id   = $list[product_id];
	    $product_name = $list1[name];
	    $stock_manage = $list1[stock_manage];
	    $alarm_qty    = $list[alarm_qty];
	    $start_date   = $list[start_date];
	    $vendor_name  = $this->get_supply_name2($list1[supply_code]);
	    $brand 	  = $list1[brand];

	    if ($list[status] == 1) 
	    {
		$alarm_status = "경고";
		$format = $format_alarm;
	    }
	    else 
	    {
		$alarm_status = "";
		$format = "";
	    }

	    // 원본 #1
	    $ins_sql = "insert into ${table_name} set
			row		= '$row',
			start_date	= '$start_date',		
			vendor_name	= '$vendor_name',
			brand		= '$brand',
			product_id	= '$product_id',
			product_name	= '$product_name',
			shop_price	= '$pricelist[shop_price]',
			supply_price	= '$pricelist[supply_price]',	
			org_price	= '$pricelist[org_price]',
			stock_in	= '',
			stock_out	= '',
                        sale_stock      = '',   
                        current_stock   = '',      
                        alert_stock     = '$alarm_qty',      
                        reg_date        = '$list[input_time]',       
                        status          = ''";
	    mysql_query($ins_sql, $connect) or die(mysql_error());

	    $row++;

	    // 하부 리스트 출력
	    $sql = "select * from ez_config";
	    $config = mysql_fetch_array(mysql_query($sql, $connect));

	    // 0 :발주기준 , 1:송장입력기준, 2:포스출고기준
	    if ($config[jaego_basedt] == 0)
		$options = "and collect_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 1)
		$options = "and trans_date >= '$start_date'";
	    else if ($config[jaego_basedt] == 2)
		$options = "and trans_date_pos >= '$start_date' and status = 8";
	
	    // 옵션별 발주인 경우...
	    if ($stock_manage == 1)
	    {
		$total_in_qty = "";
		$total_out_qty = "";
		$total_sale_qty = "";
		$total_remain = "";

		$sql = "select * from products where org_id = '$product_id' and is_delete = 0 order by product_id";
if ($product_id  == "09992") debug($sql);
		$result2 = mysql_query($sql, $connect) or die(mysql_error());
		while ($list2 = mysql_fetch_array($result2))
		{
/*		    
		    $sql = "select sum(qty) in_qty from ez_jaego_inout where product_id = '$list2[product_id]' and type = 1"; // 입고량 합계
if ($product_id  == "09992") debug($sql);
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $in_qty = $list3[in_qty];

		    $sql = "select sum(qty) out_qty from ez_jaego_inout where product_id = '$list2[product_id]' and type = 2";        // 출고량 합계
if ($product_id  == "09992") debug($sql);
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $out_qty = $list3[out_qty];

		    $sql = "select sum(qty) sale_qty from orders where product_id = '$list2[product_id]' and shop_id != '' and order_cs not in (1,3,12) ${options}";
if ($product_id  == "09992") debug($sql);
// debug($sql);
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));

		    // 묶음 상품 판매량 추가
		    $sql = "select sum(qty) sale_qty from orders where pack_list is not null and pack_list like '%${list2[product_id]}%' and shop_id != '' and order_cs not in (1,3,12) ${options}";
if ($product_id  == "09992") debug($sql);
		    $list4 = mysql_fetch_array(mysql_query($sql, $connect));

		    $sale_qty = $list3[sale_qty] + $list4[sale_qty];
*/

            //***********************************************************************
            // 파일 다운로드 수정... 2009-04-01 jkh
            
        	//********************************
        	// 재고조회 환경 설정값 조회 
        	$sql_config = "select * from ez_config";
          	$config = mysql_fetch_array(mysql_query($sql_config, $connect));
        
          	if ($config[jaego_basedt] == 0) 
              	    $options = "and collect_date >= '$start_date'";
          	else if ($config[jaego_basedt] == 1)
              	    $options = "and substring(trans_date,1,10) >= '$start_date'";
          	else if ($config[jaego_basedt] == 2)
              	    $options = "and substring(trans_date_pos,1,10) >= '$start_date' and status = 8";

    	    // 1. 입고
    	    // 1. end of 입고
    	    $in_qty = $this->get_stockin( $list2[product_id] );
    
    	    // 2. 출고
    	    $out_qty = $this->get_stockout( $list2[product_id] );
    
    	    // 3. 매출
    	    // 3.1 일반
    	    $sale_qty1 = $this->get_sale( $list2[product_id], $options );
    	        
    	    // 3.2 묶음 
    	    $sale_qty2 = $this->get_sale_pack( $list2[product_id], $options );
    	    $sale_qty       = $sale_qty1 + $sale_qty2;
    
		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

	// $header_items = array ("관리시작일", "공급처이름", "사입처상품명", "상품코드", "상품명", "판매가", "공급가", "원가",  "입고", "출고", "판매", "재고",   "경고잔량", "등록일시", "상태");
	    	    // sublist #1
	    	    $ins_sql = "insert into ${table_name} set
			row		= '$row',
			start_date	= '',		
			vendor_name	= '$vendor_name',
			brand		= '$brand',
			product_id	= '$list2[product_id]',
			product_name	= '$list2[options]',
			shop_price	= '',
			supply_price	= '',
			org_price	= '',
			stock_in	= '$in_qty',
			stock_out	= '$out_qty',
                        sale_stock      = '$sale_qty',   
                        current_stock   = '$remain',      
                        alert_stock     = '',      
                        reg_date        = '',       
                        status          = '$status'";
	    	    mysql_query($ins_sql, $connect) or die(mysql_error());

		    $row++;
		}
	    }
	    else
	    {
/*	        
		    $sql = "select sum(qty) in_qty from ez_jaego_inout where product_id = '$product_id' and type = 1";        // 입고량 합계
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $in_qty = $list3[in_qty];

		    $sql = "select sum(qty) out_qty from ez_jaego_inout where product_id = '$product_id' and type = 2";       // 출고량 합계
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));
		    $out_qty = $list3[out_qty];

		    $sql = "select sum(qty) sale_qty from orders where product_id = '$product_id' and shop_id != '' and order_cs not in (1,3,12) ${options}";
		    $list3 = mysql_fetch_array(mysql_query($sql, $connect));

		    // 묶음 상품 판매량 추가
		    $sql = "select sum(qty) sale_qty from orders where pack_list is not null and pack_list like '%${product_id}%' and shop_id != '' and order_cs not in (1,3,12) ${options}";
		    $list4 = mysql_fetch_array(mysql_query($sql, $connect));

		    $sale_qty = $list3[sale_qty] + $list4[sale_qty];
*/
            //***********************************************************************
            // 파일 다운로드 수정... 2009-04-01 jkh
            
        	//********************************
        	// 재고조회 환경 설정값 조회 
        	$sql_config = "select * from ez_config";
          	$config = mysql_fetch_array(mysql_query($sql_config, $connect));
        
          	if ($config[jaego_basedt] == 0) 
              	    $options = "and collect_date >= '$start_date'";
          	else if ($config[jaego_basedt] == 1)
              	    $options = "and substring(trans_date,1,10) >= '$start_date'";
          	else if ($config[jaego_basedt] == 2)
              	    $options = "and substring(trans_date_pos,1,10) >= '$start_date' and status = 8";

    	    // 1. 입고
    	    // 1. end of 입고
    	    $in_qty = $this->get_stockin( $product_id );
    
    	    // 2. 출고
    	    $out_qty = $this->get_stockout( $product_id );
    
    	    // 3. 매출
    	    // 3.1 일반
    	    $sale_qty1 = $this->get_sale( $product_id, $options );
    	        
    	    // 3.2 묶음 
    	    $sale_qty2 = $this->get_sale_pack( $product_id, $options );
    	    $sale_qty       = $sale_qty1 + $sale_qty2;

		    $remain = $in_qty - $out_qty - $sale_qty;

		    if ($remain < $alarm_qty) 
		    {
			$status = "경고";
			$format = $format_alarm2;
		    }
		    else 
		    {
			$status = "";
			$format = "";
		    }

	    	    // sublist #1
	    	    $ins_sql = "insert into ${table_name} set
			row		= '$row',
			start_date	= '',		
			vendor_name	= '$vendor_name',
			brand		= '$brand',
			product_id	= '$$product_id',
			product_name	= '원본'||'$product_name',
			shop_price	= '',
			supply_price	= '',
			org_price	= '',
			stock_in	= '$in_qty',
			stock_out	= '$out_qty',
                        sale_stock      = '$sale_qty',   
                        current_stock   = '$remain',      
                        alert_stock     = '',      
                        reg_date        = '',       
                        status          = '$status'";
	    	    mysql_query($ins_sql, $connect) or die(mysql_error());

	    	    $row++;
	    }
	    $txt = "총 $total_rows 건중 $record 번째 작업중입니다";
	    $this->show_txt( "$txt" );
	    flush();
	}
	

        echo "<script>hide_waiting()</script>";
	echo "<script>alert('작업이 완료되었습니다.');</script>";
	$this->redirect("template.htm?template=I800");
	exit;
  }

  // 창고별 입고량 리턴
  function get_in_stock($product_id, $warehouse, $base_date="")
  {
    global $connect;

    $sql = "select sum(qty) qty from ez_jaego_inout
             where product_id = '$product_id'
               and warehouse = '$warehouse'
               and type = 1
               and start_date >= '$base_date'";
    $list = mysql_fetch_array(mysql_query($sql, $connect));

    return $list[qty];
  }

  // 창고별 출고량 리턴
  function get_out_stock($product_id, $warehouse, $base_date="")
  {
    global $connect;

    $sql = "select sum(qty) qty from ez_jaego_inout
             where product_id = '$product_id'
               and warehouse = '$warehouse'
               and type = 2
               and start_date >= '$base_date'";
    $list = mysql_fetch_array(mysql_query($sql, $connect));

    return $list[qty];
  }


  // 창고별 판매량 리턴
  function get_sale_stock($product_id, $warehouse, $base_date="")
  {
    global $connect;

    $today = date("Y-m-d");
    if (!$base_date) $base_date = $today;


        // 판매량
    $sql = "select sum(qty) sale_qty from orders
             where product_id = '$product_id'
               and order_cs not in (1,3,12)
               and warehouse = '$warehouse'
               and status in (7,8)
               and trans_no > ''
               and substring(trans_date,1,10) >= '$base_date'
               and substring(trans_date,1,10) <= '$today'";
    $list = mysql_fetch_array(mysql_query($sql, $connect));

    return $list[sale_qty];
  }

  //////////////////////////////////
  function ck_download()
  {
	global $connect;
	global $template;

	$show_status = $_REQUEST[show_status];
	$keyword = $_REQUEST[keyword];
	$key_type = $_REQUEST[key_type];

	$today = date("Y-m-d");
	require_once 'Spreadsheet/Excel/Writer.php';

	$xls_name = $today."-CurrentJaego.xls";

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('현재재고');

	$worksheet->setColumn(2, 2, 30);
	$format_header =& $workbook->addFormat();
	$format_header->setAlign('center');
	$format_header->setBold();

	$format_header->setFgColor('cyan');
	$format_header->setBgColor('black');
	$format_header->setPattern(1);

	////////////////////////////////////////////////////////////////
	$header_items = array ("관리시작일", "상품코드", "상품명",  "입고(C)", "입고(K)", "출고(C)", "출고(K)", "판매(C)", "판매(K)", "재고(C)", "재고(K)", "현재재고", "미출고수량", "재고부족분");
	$col = 0;
	foreach ($header_items as $item)
	{
	    $worksheet->write(0, $col, $item, $format_header);
	    $col++;
	}

	/////////////////////////////////////////////////////////        
	// 1. 기본 상품 리스트
	    $sql = "select * from ez_jaegolist 
		     where is_delete = 0 ${options} 
		       and length(product_id) = 5
		     order by input_time desc, product_id asc";


	$format_alarm =& $workbook->addFormat();
	$format_alarm->setFgColor('yellow');
	$format_alarm->setBgColor('black');
	$format_alarm->setPattern(1);

	$format_alarm2 =& $workbook->addFormat();
	$format_alarm2->setFgColor(43);
	$format_alarm2->setBgColor('black');
	$format_alarm2->setPattern(1);

	$format_bold =& $workbook->addFormat();
	$format_bold->setBold();

	$format_wait =& $workbook->addFormat();
	$format_wait->setBold();
	$format_wait->setFgColor(43);
	$format_wait->setBgColor('black');

	/////////////////////////////////////////////////////////////
	$row = 1;
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	    $sql = "select * from products where product_id = '$list[product_id]'";
	    $list1 = mysql_fetch_array(mysql_query($sql, $connect));

	    $product_id = $list[product_id];
	    $product_name = $list1[name];
	    $stock_manage = $list1[stock_manage];
	    $base_date   = $list[start_date];

	    // 원본 #1
	    $worksheet->write($row, 0, $list[start_date], $format_alarm);
	    $worksheet->writeString($row, 1, $product_id, $format_alarm);
	    $worksheet->writeString($row, 2, $product_name, $format_alarm);
	    $worksheet->write($row, 3, "", $format_alarm);
	    $worksheet->write($row, 4, "", $format_alarm);
	    $worksheet->write($row, 5, "", $format_alarm);
	    $worksheet->write($row, 6, "", $format_alarm);
	    $worksheet->write($row, 7, "", $format_alarm);
	    $worksheet->write($row, 8, "", $format_alarm);
	    $worksheet->write($row, 9, "", $format_alarm);
	    $worksheet->write($row, 10, "", $format_alarm);
	    $worksheet->write($row, 11, "", $format_alarm);
	    $worksheet->write($row, 12, "", $format_alarm);
	    $worksheet->write($row, 13, "", $format_alarm);
	    $row++;

	    // 하부 리스트 출력


        // 옵션별 발주인 경우만 적용
	$total_in_qty = "";
	$total_out_qty = "";
	$total_sale_qty = "";
	$total_remain = "";

	$sql = "select * from products where org_id = '$product_id' and is_delete = 0 order by product_id";
	$result2 = mysql_query($sql, $connect) or die(mysql_error());
	while ($list2 = mysql_fetch_array($result2))
	{
		///////////////////////////////////////////////////////
		$sql = "select * from ez_jaego_inout 
		         where product_id = '$list2[product_id]' and type = 1 order by no limit 1";
		$list3 = mysql_fetch_array(mysql_query($sql, $connect));
		$start_date = $list3[start_date];

		if (!$start_date) $start_date = $base_date;

		// 입고량
		$C_in_qty = class_I800::get_in_stock($list2[product_id], 'C', $start_date);
		$K_in_qty = class_I800::get_in_stock($list2[product_id], 'K', $start_date);

	
		// 출고량
		$C_out_qty = class_I800::get_out_stock($list2[product_id], 'C', $start_date);
		$K_out_qty = class_I800::get_out_stock($list2[product_id], 'K', $start_date);

		// 판매량
		$C_sale_qty = class_I800::get_sale_stock($list2[product_id], 'C', $start_date);
		$K_sale_qty = class_I800::get_sale_stock($list2[product_id], 'K', $start_date);

		// 재고량
		$remain = ($C_in_qty + $K_in_qty) - ($C_out_qty + $K_out_qty) - ($C_sale_qty + $K_sale_qty);
		$C_remain = $C_in_qty - $C_out_qty - $C_sale_qty;
		$K_remain = $K_in_qty - $K_out_qty - $K_sale_qty;

		$noprint = class_DX00::get_noprint_qty($list2[product_id]);

		if (!$noprint) $noprint = "";

		if (!$K_remain) $K_remain = "";
		if (!$C_remain) $C_remain = "";
		if (!$remain) $remain = "";

		$diff = $remain - $noprint;
		if ($diff >= 0) $diff = "";

		if ($remain < 0) 
		{
			$status = "경고";
		}
		else 
		{
			$status = "";
		}

		    // sublist #1
		$worksheet->write($row, 0, $start_date, $format);
		$worksheet->writeString($row, 1, $list2[product_id], $format);
		$worksheet->writeString($row, 2, $list2[options], $format);
		$worksheet->write($row, 3, $C_in_qty, $format);
		$worksheet->write($row, 4, $K_in_qty, $format);
		$worksheet->write($row, 5, $C_out_qty, $format);
		$worksheet->write($row, 6, $K_out_qty, $format);
		$worksheet->write($row, 7, $C_sale_qty, $format);
		$worksheet->write($row, 8, $K_sale_qty, $format);
		$worksheet->write($row, 9, $C_remain, $format);
		$worksheet->write($row, 10, $K_remain, $format);
		$worksheet->write($row, 11, $remain, $format_bold);
		$worksheet->write($row, 12, $noprint, $format_bold);
		$worksheet->write($row, 13, $diff, $format_wait);

		$row++;
	    }
	}

	////////////////////////////////////
	// Let's send the file
	// sending HTTP headers
	$workbook->send($xls_name);
	$workbook->close();
  }
}
?>
