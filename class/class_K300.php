<?
//====================================
//
// 배송 요청
// name: class_K300
// date: 2007.11.9 - jk
//
require_once "class_top.php";
require_once "class_order.php";
require_once "class_3pl.php";
require_once "class_product.php";

class class_K300 extends class_top {
    var $m_obj_3pl = "";

    // init class
    function class_K300()
    {
    	$this->m_obj_3pl = new class_3pl();
    }

    function K300()
    {
	global $template, $start_date, $end_date;
	$link_url = base64_encode( $this->build_link_url() );

	if ( !$start_date )
            $start_date = date("Y-m-d", mktime (0,0,0,date("m")  , date("d")-6, date("Y")));

	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //======================================
    //  조회
    function query()
    {
	global $template, $start_date, $end_date;
	$link_url = base64_encode ( $this->build_link_url() );

	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function request_detail()
    {
	global $_date;
	$result = $this->m_obj_3pl->request_detail( $_date );

	echo "<table border=1 width=500>
		<tr>
		    <td>시각</td>
		    <td>개수</td>
		    <td>msg</td>
		    <td>사용자</td>
		    <td>상태</td>
		</tr>";

	if ( $result )
	while ( $data = mysql_fetch_array ( $result ) )
	{
		echo "
		<tr>
		    <td>$data[reg_date]</td>
		    <td>$data[req_cnt]</td>
		    <td>$data[msg]</td>
		    <td>$data[req_user]</td>
		    <td>$data[status]</td>
		</tr>
		";
	}
	echo "</table>";
	
    }

    //=========================================
    // 주문의 개수 파악.
    // date: 2007.11.13 - jk
    function get_count ( $_switch, $arr_options )
    {
	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	switch ( $_switch )
	{
	    case "tot_orders":
		return $this->cnt_tot_orders( $arr_options );
		break;
	    case "trans_request":
		return $obj_3pl->cnt_req_orders( $arr_options );
		break;
	}

    }

    //=============================
    // 전체 주문 개수
    function cnt_tot_orders( $arr_options )
    {
	global $connect;
	$query = "select count(*) cnt 
                   from orders a, products b
                   where a.product_id = b.product_id
                     and b.use_3pl = 1
                     and a.collect_date = '$arr_options[collect_date]'";

	if ( $arr_options[status] )
	    $query .= " and a.status = $arr_options[status]";
	    // $query .= " and a.status = 1";

	if ( $arr_options[order_cs] )
	    $query .= " and a.order_cs in ( $arr_options[order_cs] ) ";
	    //$query .= " and a.order_cs not in (1,2,3,4,12) ";

//echo $query;
	$result = mysql_query ( $query , $connect );
	$data = mysql_fetch_array ( $result );
	// echo $data[cnt];
	return $data[cnt];
    }

    ///////////////////////////
    function tot_sync()
    {
	global $_date, $connect, $start_date, $end_date, $link_url;

	$this->show_wait();

	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	$query = "select a.* from orders a, products b 
		   where a.product_id = b.product_id
                     and b.use_3pl = 1
                     and collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and a.status in (1,2,7)";

debug ( "[tot_sync] $query" );

	$result = mysql_query ( $query, $connect ) or die(mysql_error());
	$total_rows = mysql_num_rows($result);
	$cnt = 0;	
	$_date = "";

	while ( $data = mysql_fetch_array( $result ) )
	{
            if ( $data[packed] )
	    {
		$arr_product_id = split( ",", $data[pack_list] );
	        $i=0;

		//foreach ( $arr_product_id as $product_id )
		// 변경 2008.7.2 - jk
		for ( $_k=0; $_k < count( $arr_product_id); $_k++ )
		{
//debug ( "[packed: $data[seq]] $data[pack_list] ");
		    $product_id = $arr_product_id[$_k];
// print "add_row: $product_id \n";
		    $i++;
	            $this->add_row( $obj_3pl, $data, $product_id, $i ); 
		}
            }
	    else
	        $this->add_row( $obj_3pl, $data ); 

	     $cnt++;
	     $this->show_txt( "$cnt / $total_rows 작업중" );
  	}

	$this->hide_wait();

	// $this->jsAlert( "$cnt건 요청 완료");
	// $this->redirect( "?" . base64_decode( $link_url ) );
	echo "<script>alert('작업이 완료되었습니다.');</script>";
	echo "<script>document.location.href='template.htm?template=K300';</script>";
	exit;
    }

    //=============================
    //
    // 배송 요청
    //
    function trans_request()
    {
	global $_date, $connect, $link_url;

	$this->show_wait();

	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	$query = "select a.* from orders a, products b 
		   where a.product_id = b.product_id
                     and b.use_3pl = 1
                     and a.collect_date = '$_date'
                     and status in (1,2,7)";

	$result = mysql_query ( $query, $connect );

	$cnt = 0;	
	$_date = "";

	while ( $data = mysql_fetch_array ( $result ) )
	{
	    debug ( "[packed] $data[packed] / $data[pack_list] " );
	    if ( $data[packed] )
	    {
		$arr_product_id = split( ",", $data[pack_list] );
                print_r ( $arr_product_id );
		foreach ( $arr_product_id as $product_id )
		{
	            // $this->add_row( $obj_3pl, $data, $product_id ); 
		}
		exit;
            }
	    else
	        $this->add_row( $obj_3pl, $data ); 

	    $cnt = $cnt++;
	    $this->show_txt( "$cnt 개 완료" );
	    $_date = $data[collect_date];
  	}
	
	// 3pl_tx에 data 등록
	// type: Delivery
	$obj_3pl->regist_tx( "D", $cnt, "배송요청", $_SESSION[LOGIN_ID] , $_date );

	$this->hide_wait();
	$this->jsAlert( "${cnt}건 요청 완료");
	$this->redirect( "?" . base64_decode( $link_url ) );
    }

    function add_row($obj_3pl, $data, $product_id="", $seq_subid=1 )
    {
	$product_id = $product_id ? $product_id : $data[product_id];
	
	// name과 option을 가져와야 함
	class_product::get_product_name_option($product_id, &$name, &$option);

	// 묶음 상품의 경우 합포 처리
        // 묶음 상품과 일반 상품이 합포 될 경우 버그 발생 -> fix: 2008.2.15 - jk
	if ( $data[packed] )
	    $data[pack] = $data[pack] ? $data[pack] : $data[seq];

	$data[product_id]   = $product_id;
	$data[product_name] = $name;
	$data[options]      = $option;

	if ( $obj_3pl->order_reg( $data, $seq_subid ) )
	{
	    return 1; 
        }
    }
}


?>
