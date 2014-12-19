<?
//====================================
//
// name: class_KA00
// date: 2007.11.10 - jk
//
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_ui.php";

class class_KA00 extends class_top {

    var $m_items = "";
    function class_KA00()
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


    function KA00()
    {
	global $template, $connect;
	$start_date = date('Y-m-d', strtotime("today"));
	$end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/KA00.htm";
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
	$result = $obj->get_stock_history( $product_id, $start_date, $end_date );
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[crdate]] = $data[qty];
	}

	echo "<dataset seriesName='재고' color='A66EDD' >\n";
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
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[pos_date]] = $data[qty];
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
	$result = $obj->get_stock_in_history( $product_id, $start_date, $end_date );
	$dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[crdate]] = $data[qty];
	}

	echo "<dataset seriesName='입고' color='F99998'>\n";
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

        $arr_return = array();
	$this->get_list( &$arr_return );
        $total_rows = $arr_return[total_rows];
        $result     = $arr_return[result];

	// json형식으로 출력
	$val = array();
	$val['total_rows'] = $total_rows;
        $val['list']       = array();

	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $val['list'][] = array( 
			product_id   => $data[product_id],
			product_name => iconv("CP949", "UTF-8", $data[name] ) ,
			options      => iconv("CP949", "UTF-8", $data[options] ) ,
			qty          => $data[qty],
			enable_sale  => $data[enable_sale],
            );
	}
	echo json_encode( $val );
    }

    function del()
    {
	global $connect, $start_date, $end_date;

	$query = "delete from stockin_req where crdate >= '$start_date' and crdate <= '$end_date'";
	mysql_query( $query, $connect );
	echo "삭제완료 ";
    }

    //=====================================
    // download2 
    // 2008.3.20 - jk
    function download2()
    {
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;

        $arr_return = array();	// order 정보 저장
	$arr_datas = array();	// save 해야할 data저장

	// header지정
	$arr_datas[] = array("공급처", "상품코드", "상품명", "옵션","원가","요청일","요청개수" );

	$obj_file = new class_file();

	$is_download = 1;
	$this->get_list( &$arr_return, $is_download );
        $result = $arr_return[result];


	//////////////////////////////////////////////////
	// download받을 data생성
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $i    = 0;
	    $_row = array();
	    while ($i < mysql_num_fields($result)) 
	    {
		$index = mysql_field_name( $result, $i );
		//$_row[$index] = $data[$index];

		// image 관련된 항목은 다운받을 필요 없음.
		if ( !preg_match ("/img/", $index ) && !preg_match("/desc/", $index) )
		{
		    debug ( $index );
		    $_row[] = $data[$index];
		}

		$i++;
	    }
	    $arr_datas[] = $_row;
	}

	$obj_file->download( $arr_datas );
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
    function get_list( &$arr_return, $is_download=0 )
    {
	global $connect, $page, $use_3pl;
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;
        $name    = iconv("UTF-8", "CP949", $name );
        $options = iconv("UTF-8", "CP949", $options );

	$page = $page ? $page : 1;
	$_starter = ($page - 1) * 20;

	///////////////////////////////////////////////////////////
	// 데이터는 값이 있다고 가정함
	// 재고 값(Logic 1)과 배송 개수(Logic 2)가 값이 없을 경우에는 is_nodata=1
	$is_nodata = 0;

	/////////////////////////////////////////////////////////
        //
	// 실제 상품 정보 query하는 부분
	$query  = "select a.supply_code, a.product_id,a.name,a.options, a.org_price, b.crdate, b.qty
                     from products a, stockin_req b
                    where a.product_id = b.product_id  
                      and b.crdate >= '$start_date'
                      and b.crdate <= '$end_date'";

	if ( $name )
	    $option .= " and a.name like '%$name%'";

	if ( $options )
	    $option .= " and a.options like '%$options%'";

	// 공급업체 코드가 있는 경우
	if ( $supply_code )
	    $option .= " and a.supply_code = '$supply_code'";

	// 상품 코드 리스트 값이 있는 경우
	if ( $product_list )
	    $option .= " and a.product_id = '$product_id'"; 

	
	//////////////////////////////////////////////////////////
	// count 
	$query_cnt  = "select count(*) cnt 
                     from products a, stockin_req b
                    where a.product_id = b.product_id  
                      and b.crdate >= '$start_date'
                      and b.crdate <= '$end_date'";

	$result    = mysql_query ( $query_cnt . $option, $connect );
	$data      = mysql_fetch_array( $result );
	$arr_return[total_rows] = $data[cnt];

	///////////////////////////////////////////////////////////
	if ( !$is_download )
	{
	    global $start;
	    $start = $start ? $start : 0;		
	    $option .= " limit $start, 20";
	}

	$result = mysql_query ( $query . $option , $connect );
	$arr_return[result] = $result;
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
