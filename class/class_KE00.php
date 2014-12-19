<?
//====================================
//
// name: class_KE00
// date: 2007.11.10 - jk
//
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_3pl_api.php";
require_once "class_ui.php";

class class_KE00 extends class_top {

    var $m_items = "";
    function class_KE00()
    {
	$this->m_items = array (
                "supply_code"  => "",
                "product_id"   => "",
                "use_3pl"      => "",
                "name"         => "like",
                "options"      => "like",
        );
    }

    function KE01()
    {
	global $template, $connect;
	$start_date = date('Y-m-d', strtotime("today"));
	$end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/KE01.htm";
    }


    function KE00()
    {
	global $template, $connect;
	$start_date = date('Y-m-d', strtotime("today"));
	$end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/KE00.htm";
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
    // file upload�� �۾�
    // ��ǰ ������ excel�� �������� update��
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

	    $str = "${rows} / ${total_rows}��° �۾����Դϴ�.";
	    echo "<script>show_txt('$str');</script>";
	    flush();
	}

	$this->hide_wait();
	$this->jsAlert ( "����: $rows���� �۾� �Ϸ�" );

	$this->redirect ("?". base64_decode ( $top_url ) );
	exit;
    }

    //===============================
    // sync�۾� ����
    // date: 2007.11.21 - jk
    function do_sync()
    {
	global $top_url;

	$this->show_wait();
	$obj     = new class_product();
	$obj_3pl = new class_3pl();

        ////////////////////////////////////////////////////////
	// 3pl�� ����ϸ� �������� ���� ��ǰ
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
	    // 1. is_reg_product ���� Ȯ���ؼ�
	    if ( $obj_3pl->check_reg ( $_product_id ) )
	    {
		$_tot_cnt++;
		$_update++;
	        // ���� ��� update
		// echo "���� : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->_update( $data, $_product_id );
	    }
	    else
	    {
		$_tot_cnt++;
		$_reg++;
	        // ���� ��� do_reg
		// echo "���� : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->product_reg( $data[product_id], $data );
	    }

	    //////////////////////////////////////
	    $msg = " $i / $tot_rows �۾���";	
	    $this->show_txt ( $msg );
  	    $i++;
	}	
	$this->hide_wait();
        echo "\n\n";
	$this->jsAlert ( " ����: $_update ���: $_reg ��: $tot_rows ���� �۾� �Ϸ� ");
	$this->redirect( "?template=K902&top_url=$top_url" );
    }

    // chart�� �׸��� ���� ��� �̷� ��ȸ
    function get_stock_history()
    {
	global $connect, $product_id, $start_date, $end_date;
	
	echo "<chart caption='���' yAxisName='����' bgColor='F7F7F7, E9E9E9' showValues='0' numVDivLines='10' divLineAlpha='30'  labelPadding ='10' yAxisValuesPadding ='10'>";


	//=====================================================	
        //
	// date �κ� category ����
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
        // ��� data ����
	$obj          = new class_3pl();
	$result = $obj->get_stock_history( $product_id, $start_date, $end_date );
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[crdate]] = $data[qty];
	}

	echo "<dataset seriesName='���' color='A66EDD' >\n";
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
	// �Ǹ� data
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

	echo "<dataset seriesName='���' color='FF0000'>\n";
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
	// �԰�
	$result = $obj->get_stock_in_history( $product_id, $start_date, $end_date );
	$dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
	{
	    $dataset[$data[crdate]] = $data[qty];
	}

	echo "<dataset seriesName='�԰�' color='F99998'>\n";
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
	// �̹�� data
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
	echo "<dataset seriesName='�̹�� �հ�' color='F6BD0F'>\n";
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
    // ��ǰ ��ȸ
    // 2007.11.20
    //
    function query()
    {
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;

        $arr_return = array();
	$obj     = new class_3pl();
	$obj_api = new class_3pl_api();

	// �԰� ���� ��ȸ
	$obj->get_stockin_list( &$arr_return );
        $total_rows = $arr_return[total_rows];
        $result     = $arr_return[result];

	// json�������� ���
	$val = array();
	$val['total_rows'] = $total_rows;
        $val['list']       = array();

	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $qty_stock = $obj_api->get_current_stock3( $data[product_id] );	
	    //$reg_qty    = $obj_api->get_reg_deliv( $data[product_id] );	// ��� ���� ���� 2008.7.24 - jk
	    //$notyet_qty = $obj_api->get_notyet_qty( $data[product_id] );	// �̹�� 2008.7.24 - jk 
	    $val['list'][] = array( 
			product_id       => $data[product_id],
			product_name     => iconv("CP949", "UTF-8", $data[product_name] ) ,
			options          => iconv("CP949", "UTF-8", $data[options] ) ,
			qty_in           => $data[qty],
			qty_out          => $qty_out,
			qty_stock        => $qty_stock,
            );
	}
	echo json_encode( $val );
    }

    //=====================================
    // download2 
    // 2008.3.20 - jk
    function download2()
    {
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;

        $arr_return = array();	// order ���� ����
	$arr_datas = array();	// save �ؾ��� data����
	$obj_file = new class_file();

	$this->get_list( &$arr_return, "download" );
        $result = $arr_return[result];

	$arr_field = array ( 
		"product_id" => "��ǰ��ȣ",
		"name"	     => "��ǰ��",
		"options"    => "�ɼ�",
		"stock"	     => "���"
	);

	$_row = array();
	foreach( $arr_field as $key=>$title )
	{
	    $_row[] = $title;
	}
	$arr_datas[] = $_row;

	//////////////////////////////////////////////////
	// download���� data����
	$obj = new class_3pl_api();
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $i    = 0;
	    $_row = array();
	    foreach( $arr_field as $key=>$title )
	    {
		if ( $key == "stock" )
	            $_row[] = $obj->get_current_stock3 ( $data[product_id] );
		else
	            $_row[] = $data[$key];
	    }
	    $arr_datas[] = $_row;
	}

	$obj_file->download( $arr_datas );
    }

    ////////////////////////////////////////
    // ��ǰ�� �� ���� ���
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
    // ��ǰ ���� ��ȸ ����Ʈ
    // 2007.11.21 - jk
    function get_list( &$arr_return, $_flag="limit" )
    {
debug ( "[get_list] $_flag" );
	global $connect, $page, $use_3pl;
	global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;
        $name    = iconv("UTF-8", "CP949", $name );
        $options = iconv("UTF-8", "CP949", $options );

	$page = $page ? $page : 1;
	$_starter = ($page - 1) * 20;

	///////////////////////////////////////////////////////////
	// �����ʹ� ���� �ִٰ� ������
	// ��� ��(Logic 1)�� ��� ����(Logic 2)�� ���� ���� ��쿡�� is_nodata=1
	$is_nodata = 0;

	/////////////////////////////////////////////////////////
        //
	// ���� ��ǰ ���� query�ϴ� �κ�
	$query  = "select * 
                     from products a 
                    where a.org_id<>'' ";

	if ( $name )
	    $option .= " and a.name like '%$name%'";

	// �ɼ� ���� ���� ��� 
	if ( $options )
	    $option .= " and a.options like '%$options%'";


	// ���޾�ü �ڵ尡 �ִ� ���
	if ( $supply_code )
	    $option .= " and a.supply_code = '$supply_code'";


	// ��ǰ �ڵ� ����Ʈ ���� �ִ� ���
	if ( $product_id )
	    $option .= " and a.product_id = '$product_id'"; 

	
	//////////////////////////////////////////////////////////
	// count 
	$query_cnt  = "select count(*) cnt 
                     from products a where a.org_id<>''";

	$result    = mysql_query ( $query_cnt . $option, $connect );
	$data      = mysql_fetch_array( $result );
	$arr_return[total_rows] = $data[cnt];

	///////////////////////////////////////////////////////////
	if ( $_flag == "limit" )
	{
	    global $start;
	    $start = $start ? $start : 0;		
	    $option .= " limit $start, 20";
	}

	$result = mysql_query ( $query . $option , $connect );
	$arr_return[result] = $result;
    }

    //========================================
    // 3pl ��ǰ�� ����
    function get_count_3pl()
    {
	$obj = new class_product();

	// ����
	$arr_items = array ( "use_3pl" => 1 );
	return $obj->get_count( $arr_items );
    }

    //==================================
    // 3pl���� �����ǰ� �ִ� ��ǰ�� ����
    function get_count_3pl_manage()
    {
	$obj = new class_3pl();

	$arr_items = array ( "domain" => _DOMAIN_ );
	return $obj->product_count( $arr_items );
    }

    //=====================================
    // ����
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
