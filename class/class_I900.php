<?
//====================================
//
// name: class_I900
// date: 2007.11.10 - jk
//
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_3pl_api.php";
require_once "class_ui.php";

class class_I900 extends class_top {

    var $m_items = "";
    function class_I900()
    {
	$this->m_items = array (
                "supply_code"  => "",
                "product_id"   => "",
                "use_3pl"      => "",
                "name"         => "like",
                "options"      => "like",
        );
    }

    function K901()
    {
	global $template, $connect;
	$start_date = date('Y-m-d', strtotime("-7 day"));
	$end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/K901.html";
    }


    function I900()
    {
	global $template, $connect;
	$start_date = date('Y-m-d', strtotime("-7 day"));
	$end_date   = date('Y-m-d', strtotime("today"));

        include "template/I/I900.htm";
    }

    function confirm_stockin()
    {
	global $product_id, $qty, $connect;
	$today = date('Y-m-d', strtotime("today"));

	if ( $qty ){
	    $query = "select qty from stockin_req where product_id='$product_id' and crdate='$today'";
	    $result = mysql_query ( $query, $connect );
	    $data   = mysql_fetch_array ( $result );
	
	    if ( $data[qty] )
	    {
	        $qty = $data[qty] + $qty;
	        $query = "update stockin_req set qty=$qty 
                           where product_id='$product_id' and crdate='$today'";
	        mysql_query ( $query, $connect );
	    }
	    else
	    {
	        $query = "insert into stockin_req set qty=$qty, product_id='$product_id', crdate=Now()";
	        mysql_query ( $query, $connect );
	    }
	}

	$val = array();
	$val[crdate] = $today;
	$val[qty]    = $qty;

	if ( mysql_affected_rows() != -1 )
	    $val[result] = "ok";
	else
	    $val[result] = "fail";

	echo json_encode( $val );
    }


    //======================================
    // file upload후 작업
    // 상품 정보를 excel의 내용으로 update함
    // date: 2007.11.21 - jk
    function upload()
    {
	$this->show_wait();

	global $connect, $_file, $top_url;
	$obj = new class_file();
	$arr_result = $obj->upload();

	$total_rows = sizeof ( $arr_result );
	$obj = new class_product();

	$rows = 0;
	foreach ( $arr_result as $row )
	{
	    $rows++;
	    if ( $rows == 1 ) continue;
 
	    $infos[product_id_3pl] = $row[0];
	    $infos[product_id] 	   = $row[1];
	    $infos[barcode] 	   = $row[2];
	    $infos[name]       	   = $row[3];
	    $infos[options]        = $row[4];
	    $infos[supply_code]    = $row[5];
	    $infos[enable_sale]    = $row[6];
	    $infos[use_3pl]        = $row[7];

	    ///////////////////////////////
	    // sync product 
	    $obj->sync_product( $infos, $row[0] );

	    $str = "${rows} / ${total_rows}번째 작업중입니다.";
	    echo "<script>show_txt('$str');</script>";
	    flush();
	}

	$this->hide_wait();
	$this->jsAlert ( "변경: $rows개의 작업 완료" );

	$this->redirect ("?". base64_decode ( $top_url ) );
	exit;
    }

    //===============================
    // sync작업 수행
    // date: 2007.11.21 - jk
    function do_sync()
    {
	global $top_url;

	$this->show_wait();
	$obj     = new class_product();
	$obj_3pl = new class_3pl();

        ////////////////////////////////////////////////////////
	// 3pl을 사용하며 삭제되지 않은 상품
	$arr_items = array ( "use_3pl" => 1, "is_delete" => "zero" );
	$tot_rows  = $obj->get_count ( $arr_items );
	$obj->get_list( $arr_items );

	$_tot_cnt  = 0;
	$_update   = 0;
	$_reg      = 0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $_product_id = $data[product_id_3pl] ? $data[product_id_3pl] : $data[product_id];	

	    //////////////////////////////////////
	    // 1. is_reg_product 인지 확인해서
	    if ( $obj_3pl->check_reg ( $_product_id ) )
	    {
		$_tot_cnt++;
		$_update++;
	        // 있을 경우 update
		// echo "있음 : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->_update( $data, $_product_id );
	    }
	    else
	    {
		$_tot_cnt++;
		$_reg++;
	        // 없을 경우 do_reg
		// echo "없음 : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->product_reg( $data[product_id], $data );
	    }

	    //////////////////////////////////////
	    $msg = " $i / $tot_rows 작업중";	
	    $this->show_txt ( $msg );
  	    $i++;
	}	
	$this->hide_wait();
        echo "\n\n";
	$this->jsAlert ( " 변경: $_update 등록: $_reg 총: $tot_rows 개의 작업 완료 ");
	$this->redirect( "?template=K902&top_url=$top_url" );
    }

    // chart를 그리기 위한 재고 이력 조회
    function get_stock_history()
    {
	global $connect, $product_id, $start_date, $end_date;
	
	echo "<chart caption='재고' yAxisName='수량' bgColor='F7F7F7, E9E9E9' showValues='0' numVDivLines='10' divLineAlpha='30'  labelPadding ='10' yAxisValuesPadding ='10'>";


	//=====================================================	
        //
	// date 부분 category 생성
        //
	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);
	$_start    = round( abs(strtotime(date('y-m-d'))-strtotime($end_date)) / 86400, 0 );
        $_interval = $_start + $_interval;

	echo "<categories>";
	if ( $_interval >= 0 )
    	{
            for ( $i = $_interval; $i >= $_start; $i-- )
            {	
		$_date = date('Y-m-d', strtotime("-$i day"));
		echo "<category label='$_date' />\n ";
	    }
	}
	echo "</categories>";

	//////////////////////////////////////////////////////////
        // 재고 data 생성
	$obj          = new class_3pl();
	$result       = $obj->get_stock_history( $product_id, $start_date, $end_date );
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[crdate]] = $data[qty];
	}

	echo "<dataset seriesName='재고' color='A66EDD' >\n";
	if ( $_interval >= 0 )
    	{
	    $_last_val = 0;
            for ( $i = $_interval; $i >= $_start; $i-- )
            {	
		$_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date] ? $dataset[$_date] : $_last_val;
		$_last_val = $_val;
		echo "<set value='$_val' />\n ";
	    }
	}
	echo "</dataset>\n";

	////////////////////////////////////////////////////////////////
	//
	// 판매 data
	//
	$query = "select date_format(trans_date_pos,'%Y-%m-%d') pos_date, count(*) qty 
                    from orders                                 
                   where trans_date_pos >= '$start_date 00:00:00'
                     and trans_date_pos <= '$end_date 23:59:59'
                     and product_id='$product_id'                  
		     and status=8
                   group by date_format(trans_date_pos,'%Y-%m-%d') ";

	$result = mysql_query ( $query, $connect );
	$dataset = "";

	// 총 배송
	$_tot_deliv = 0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[pos_date]] = $data[qty];
	    $_tot_deliv = $_tot_deliv + $data[qty];
	}

	echo "<dataset seriesName='배송' color='FF0000'>\n";
	if ( $_interval >= 0 )
    	{
            for ( $i = $_interval; $i >= $_start; $i-- )
            {	
		$_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date] ? $dataset[$_date] : 0;
		echo "<set value='$_val' />\n ";
	    }
	}
	echo "</dataset>\n";


	/////////////////////////////////////////////////////
	// 입고
	// 총 입고
	$_tot_stockin = 0;
	$result = $obj->get_stock_in_history( $product_id, $start_date, $end_date );
	$dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[start_date]] = $data[sum_qty];
	    $_tot_stockin = $_tot_stockin + $data[sum_qty]; 
	}

	echo "<dataset seriesName='입고' color='F99998'>\n";
	if ( $_interval >= 0 )
    	{
	    $_last_val = 0;
            for ( $i = $_interval; $i >= $_start; $i-- )
            {	
		$_date = date('Y-m-d', strtotime("-$i day"));
                //$_val  = $dataset[$_date] ? $dataset[$_date] : $_last_val;
                $_val  = $dataset[$_date] ? $dataset[$_date] : 0;
		$_last_val = $_val;
		echo "<set value='$_val' />\n ";
		// debug ("<set value='$_val' />\n ");
	    }
	}
	echo "</dataset>\n";

	////////////////////////////////////////////////////////////////
	//
	// 미배송 data
	//
	$query = "select collect_date, count(*) qty 
                    from orders                                 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and product_id='$product_id'                  
                     and status in (1,2,11 )                  
                     and order_cs not in (1,2,3,4,12 )                  
                   group by collect_date";

	$result = mysql_query ( $query, $connect );
	$sum = 0;
	$dataset = "";
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[collect_date]] = $data[qty];
	}

	$sum = 0;
	echo "<dataset seriesName='미배송 합계' color='F6BD0F'>\n";
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
	echo "<dataset id='common' seriesName='common' tot_stockin='" . ceil($_tot_stockin/$_interval) . "' tot_deliv='" . ceil($_tot_deliv/$_interval) . "'></dataset>";
?>
</chart>
<?
    }

    //================================
    //
    // 상품 조회
    // 2007.11.20
    //
    function query()
    {
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;

	$top_url = base64_encode( $this->build_link_url2() );

        $arr_return = array();
	//////////////////////////////////////////
	$this->get_list( &$arr_return );
        $total_rows = $arr_return[total_rows];
        $result     = $arr_return[result];

	// json형식으로 출력
	$val = array();
	$val['total_rows'] = $total_rows;
        $val['list']       = array();

	$obj = new class_3pl_api();
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $qty        = $obj->get_current_stock3( $data[product_id] );	
	    $reg_qty    = $obj->get_reg_deliv( $data[product_id] );	// 배송 예정 개수 2008.7.24 - jk
	    $notyet_qty = $obj->get_notyet_qty( $data[product_id] );	// 미배송 2008.7.24 - jk 

	    $val['list'][] = array( 
			product_id   => $data[product_id],
			product_name => iconv("CP949", "UTF-8", $data[name] ) ,
			options      => iconv("CP949", "UTF-8", $data[options] ) ,
			qty              => $qty,
			reg_qty          => $reg_qty,
			notyet_qty       => $notyet_qty,
			enable_sale  => $data[enable_sale],
            );
	}
	echo json_encode( $val );
    }

    ////////////////////////////////////////
    // 상품의 상세 정보 출력
    // 2008.3.14 - jk
    function get_detail()
    {
	global $product_id, $connect;
	$val         = array();

	$query = "select crdate,qty from stockin_req where product_id='$product_id' order by crdate desc limit 1";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array( $result );
	$val['last_stockin_req']   = $data[crdate];
	$val['last_stockin_qty']   = $data[qty];


	$obj_product = new class_product();
	$data        = $obj_product->get_info( $product_id );

	$val['product_id']   = $data[product_id];
        $val['name']         = iconv("CP949", "UTF-8", $data[name] );
        $val['options']      = iconv("CP949", "UTF-8", $data[options] );
        $val['supply_name']  = iconv("CP949", "UTF-8", $data[supply_name] );
        $val['org_price']    = iconv("CP949", "UTF-8", $data[org_price] );
        $val['supply_price'] = iconv("CP949", "UTF-8", $data[supply_price] );
        $val['shop_price']   = iconv("CP949", "UTF-8", $data[shop_price] );
        $val['barcod3']      = iconv("CP949", "UTF-8", $data[barcode] );

	echo json_encode( $val );
    }

    //=====================================
    // 상품 관련 조회 리스트
    // 2007.11.21 - jk
    function get_list( &$arr_return )
    {
	global $connect, $page, $use_3pl;
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;
	global $current_stock_over, $current_stock_below;

        $name    = iconv("UTF-8", "CP949", $name );
        $options = iconv("UTF-8", "CP949", $options );

	$page = $page ? $page : 1;
	$_starter = ($page - 1) * 20;

	//////////////////////////////////////////
	// 조건에 맞는 상품..
	// 2008.9.26 - jk
	$arr_product1  = array();	// 최종 쿼리 작업을 위한 상품 저장 2008.4.17 
	if ( $name || $options || $supply_code || $product_id )
	{
	    $query  = "select product_id from products where org_id <> '' ";
	    if ( $name )
	        $option .= " and name like '%$name%'";
	    if ( $options )
	        $option .= " and options like '%$options%'";
	    // 공급업체 코드가 있는 경우
	    if ( $supply_code )
	        $option .= " and supply_code = '$supply_code'";
	    // 상품 코드 리스트 값이 있는 경우
	    if ( $product_id )
	        $option .= " and product_id = '$product_id' "; 
   
debug ("[haha] $query $option ");

	    $result = mysql_query ( $query . $option, $connect );
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
	        $arr_product1[] = $data[product_id];
	    }
	}
	///////////////////////////////////////////////////////////
	// 데이터는 값이 있다고 가정함
	// 재고 값(Logic 1)과 배송 개수(Logic 2)가 값이 없을 경우에는 is_nodata=1
	$is_nodata = 0; // 일단 데이터는 있다는 가정하에서...로직을 탔는데 is_nodata가 있으면 이후론 로직 pass..
	$arr_product  = array();	// 최종 쿼리 작업을 위한 상품 저장 2008.4.17 

	///////////////////////////////////////////////////////////
	// 재고 값을 보고자 한다면
        // date: 2008.3.4 - jk
	// 현재 재고 X개 이상 X개 이하를 처리 함
	// logic 1
	$obj = new class_3pl();
	if ( $current_stock_over > 0 || $current_stock_below > 0 )
	{
	    $arr_info[current_stock_over]  = $current_stock_over;
	    $arr_info[current_stock_below] = $current_stock_below;
	    $arr_info[arr_product]         = $arr_product1;	// 조건에 맞는 상품

	    // 금일 발주 보면 됨
	    //$arr_info[start_date]          = date('Y-m-d', strtotime("today"));
	    $arr_info[start_date]          = date('Y-m-d', strtotime("-1 day"));
	    $result = $obj->get_stock_history2( $arr_info );

	    // 재고 수량으로 해당 상품의 리스트를 가져온다
	    $i = 0;
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
		$arr_product[] = $data[product_id];
		$i++;
	    }

	    // data값이 있는지 여부 결정
	    // is_nodata 가 1인 경우엔 조건을 타야 함
	    if ( $i == 0 ) $is_nodata = 1;
	}	

	///////////////////////////////////////////////////////////
	// 미배송 개수 X개 처리 함
	// 발주 개수 중 미배송 발생 개수 확인
	// logic 2
	global $trans_term, $not_trans_count;
	$start_date = date('Y-m-d', strtotime("-$trans_term day"));
	$not_trans_product = "";

	if ( $not_trans_count && !$is_nodata)
	{
	    $query = "select product_id 
                        from orders 
                       where collect_date >= '$start_date 00:00:00'
			 and status=1 and order_cs not in (1,2,3,4,12)";

	    ////////////////////////////////////////////////////
	    for ( $j=0; $j < count( $arr_product); $j++ )
	    {
		if ( $j == 0 ) $query .= " and product_id in (";
		if ( $j > 0 ) $query .= ",";
		$query.= "'" . $arr_product[$j] . "'";
		if ( $j == count($arr_product) - 1 ) $query .= ") ";
	    }
	    ////////////////////////////////////////////////////

            $query .= " group by product_id 
		      having sum(qty) > $not_trans_count";

// debug ( "[not_trans_count] $query " );
//if ( $_SESSION[LOGIN_LEVEL] == 9 ) exit;

            $result = mysql_query ( $query, $connect );
	    // 재고 수량으로 해당 상품의 리스트를 가져온다

	    $i = 0;
	    $arr_product = array();
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
		$arr_product[] = $data[product_id];
		$i++;
	    }

	    // 값이 없을 경우
	    if ( $i == 0 ) $is_nodata = 1;
	}


	///////////////////////////
    	// 입고 개수를 체크 2008.4.17
	// 작업 중	
	// logic 3
	global $stock_in;	// 입고 수량
	if ( $stock_in && !$is_nodata)
	{
	    $arr_info = array();
	    $end_date = date('Y-m-d', strtotime("today") );

	    // 3pl object로 부터 값을 가져와야 함
            $result = $obj->get_stock_in_history2( $start_date, $end_date, $stock_in, $arr_product );

	    // 재고 수량으로 해당 상품의 리스트를 가져온다
	    $i = 0;
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
		$arr_product[] = $data[product_id];
		$i++;
	    }
	    // data값이 있는지 여부 결정
	    if ( $i == 0 ) $is_nodata = 1;
	}

	///////////////////////////////////////////////////////////
   	// 
	// 최근 trans_term 동안 배송 개수 trans_count개 이상
	//
	// r1: 1 => 평균 배송 개수
	// r1: 2 => 금일 미 배송
	global $tot_trans_count, $avg_trans_count, $not_trans_count;

if ( $_SESSION[LOGIN_LEVEL] == 9 ) 
{
	//debug ( "tot_trans_count: $tot_trans_count" );
}

	if ( $trans_term && !$is_nodata )
	if ( $tot_trans_count || $avg_trans_count )
	{
	    $query = "select product_id 
                        from orders 
                       where trans_date_pos >= '$start_date 00:00:00' ";

	    ////////////////////////////////////////////////////
	    for ( $j=0; $j < count( $arr_product); $j++ )
	    {
		if ( $j == 0 ) $query .= " and product_id in (";
		if ( $j > 0 ) $query .= ",";
		$query.= "'" . $arr_product[$j] . "'";
		if ( $j == count($arr_product) - 1 ) $query .= ") ";
	    }
	    ////////////////////////////////////////////////////
            $query .= " group by product_id ";

	    ////////////////////////////////////////////////////////
	    // 1.tot trans count
	    if ( $tot_trans_count )
	 	$query .= " having sum(qty) > $tot_trans_count";

	    ////////////////////////////////////////////////////////
	    // 2.avg trans count
	    if ( $tot_trans_count && $avg_trans_count )
		$query .= " and ";
	    else
		if ( $avg_trans_count )
		    $query .= " having ";

	    if ( $avg_trans_count )
	 	$query .= " sum(qty) / $trans_term >= $avg_trans_count";

if ( $_SESSION[LOGIN_LEVEL] == 9 ) 
{
	//debug ( "\n[trans_count] $query \n");
}


	    $result = mysql_query ( $query, $connect );
		    // 재고 수량으로 해당 상품의 리스트를 가져온다
	    $i = 0;
	    $arr_product = array();
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
		if ( $i != 0 ) $product_list2 .= ",";
		$product_list2 .= "'$data[product_id]'";
	 	// $product_list2 = $product_list3;
		$arr_product[] = $data[product_id];
		$i++;
	    }

	    // data값이 있는지 여부 결정
	    if ( $i == 0 ) 
	    {
		$is_nodata = 1;
	 	$product_list2 = $product_list3;
	    }

if ( $_SESSION[LOGIN_LEVEL] == 9 ) 
{
	//debug( "배송개수 nodata: $is_nodata " );
	//debug( "\nproduct_list2: i=> $i / $product_list2 " );
}
	}

	///////////////////////////////////////////////////
	//
	// data가 있는 경우에만 
	//
	if ( $is_nodata == 1 )
	{
	    $arr_return[total_count] = 0;
	    $arr_return[result] = null;
	    return;	
	}
	else
	{
	/////////////////////////////////////////////////////////
        //
	// 실제 상품 정보 query하는 부분
	$query  = "select * from products where org_id <> '' ";

	if ( $name )
	    $option .= " and name like '%$name%'";

	if ( $options )
	    $option .= " and options like '%$options%'";

	// 공급업체 코드가 있는 경우
	if ( $supply_code )
	    $option .= " and supply_code = '$supply_code'";

	// 상품 코드 리스트 값이 있는 경우
	if ( $product_id )
	    $option .= " and product_id = '$product_id' "; 

        ////////////////////////////////////////////////////
	for ( $j=0; $j < count( $arr_product); $j++ )
	{
	    if ( $j == 0 ) $option .= " and product_id in (";
	    if ( $j > 0 ) $option .= ",";
	    $option .= "'" . $arr_product[$j] . "'";
	    if ( $j == count($arr_product) - 1 ) $option .= ") ";
	}
	////////////////////////////////////////////////////
 
	//////////////////////////////////////////////////////////
	// count 
	$query_cnt = "select count(*) cnt from products where org_id <> '' ";

if ( $_SESSION[LOGIN_LEVEL] == 9 ) 
{
	//debug ( $query. $option );
}

	$result    = mysql_query ( $query_cnt . $option, $connect );
	$data      = mysql_fetch_array( $result );
	$arr_return[total_rows] = $data[cnt];

	///////////////////////////////////////////////////////////
	if ( !$arr_return[download] )
	{
	    global $start;
	    $start = $start ? $start : 0;		
	    $option .= " limit $start, 20";
debug ( "[query stock] $query $option" );
	}
	$result = mysql_query ( $query . $option , $connect ) or die(debug(mysql_error()));;
	$arr_return[result] = $result;
	}
    }

    //=====================================
    // download2 
    // 2007.11.20 - jk
    function download2()
    {
	global $saveTarget;

	// download floag
	$arr_return[download] = 1;
	$this->get_list( &$arr_return );
        $total_rows = $arr_return[total_rows];
        $result     = $arr_return[result];

	// file open
        $handle = fopen ($saveTarget, "w");
	$buffer .= "<meta http-equiv='Content-Type' content='text/html; charset=euc-kr'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
<tr>
  <td>상품코드</td>
  <td>이름</td>
  <td>옵션</td>
  <td>재고</td>
  <td>배송예정</td>
  <td>미배송</td>
</tr>
";
        fwrite($handle, $buffer );

	$obj = new class_3pl_api();
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    //$qty        = $obj->get_current_stock3( $data[product_id] );	
	    $qty        = $obj->get_location_stock_tot( $data[product_id] );	
	    $qty        = str_replace( "<br>", " / ", $qty );
	    $reg_qty    = $obj->get_reg_deliv( $data[product_id] );	// 배송 예정 개수 2008.7.24 - jk
	    $reg_qty    = str_replace( "<br>", " / ", $reg_qty );
	    $notyet_qty = $obj->get_notyet_qty( $data[product_id] );	// 미배송 2008.7.24 - jk 
	    $notyet_qty    = str_replace( "<br>", " / ", $notyet_qty );

	debug ( "[download2] $qty / $reg_qty / $notyet_qty" );

	    $buffer = "<tr>
			<td>$data[product_id]</td>
			<td>$data[name]</td>
			<td>$data[options]</td>
			<td>$qty</td>
			<td>$reg_qty</td>
			<td>$notyet_qty</td>
 	    	    </tr>
			";
            fwrite($handle, $buffer );
	}

	// footer 기록
        fwrite($handle, "</table></html>");

	// excel변환 작업
	$saveTarget2 = $saveTarget . "_[products].xls";

	header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=재고_list.xls" );
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");

       	$run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2"; 
	fclose($handle);

    session_write_close();  //Close the session before proc_open()
	exec( $run_module );
    session_start(); //restore session

	if (is_file($saveTarget2)) {
	    $fp = fopen($saveTarget2, "r");
            fpassthru($fp);
	    fclose($fp);
	}

	// del file 
        unlink($saveTarget);
        unlink($saveTarget2);
    }

    //========================================
    // 3pl 상품의 개수
    function get_count_3pl()
    {
	$obj = new class_product();

	// 조건
	$arr_items = array ( "use_3pl" => 1 );
	return $obj->get_count( $arr_items );
    }

    //==================================
    // 3pl에서 관리되고 있는 상품의 개수
    function get_count_3pl_manage()
    {
	$obj = new class_3pl();

	$arr_items = array ( "domain" => _DOMAIN_ );
	return $obj->product_count( $arr_items );
    }

    //=====================================
    // 개수
    function get_count()
    {
	global $connect;
	
	$query  = "select count(*) cnt from products";
	$query .= $this->build_option( $this->m_items );	
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }


}

?>
