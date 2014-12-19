<?
//====================================
//
// 배송 요청
// name: class_K800
// date: 2007.11.9 - jk
//
require_once "class_top.php";
require_once "class_order.php";
require_once "class_3pl.php";
require_once "class_product.php";

class class_K800 extends class_top {
    var $m_obj_3pl = "";

    // init class
    function class_K800()
    {
    	$this->m_obj_3pl = new class_3pl();
    }

    function K800()
    {
	global $template, $start_date, $end_date;
	$link_url = base64_encode( $this->build_link_url() );

	if ( !$start_date )
            $start_date = date("Y-m-d", mktime (0,0,0,date("m")  , date("d")-1, date("Y")));

	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function do_sync()
    {
        global $connect, $seq, $pack;

        // 3pl 객체 생성	
	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();


	$query = "select * from orders where ";
	if ( $pack )
	    $query .= " pack = $pack ";
        else
	    $query .= " seq = $seq ";

echo $query;

	$result = mysql_query ( $query, $connect );

	while ( $data = mysql_fetch_array ( $result ) )
        {
            $obj_3pl->update_order( $data );
        }
    }


    //======================================
    //  조회
    function query()
    {
	global $template, $connect;
	global $start_date, $end_date, $switch_date, $status, $order_cs;
	$link_url = base64_encode ( $this->build_link_url() );

	$this->show_wait();

	// 3pl 객체 생성	
	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	// query수행
	$query = $this->build_query();
	$result = mysql_query( $query, $connect );
	$arr_result = array();	
	$i = 0;
	$j = 0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $_is_error = 0;
	    // 3pl의 상품 정보 가져오기
	    $infos = $obj_3pl->get_info( $data[seq] );

	    // status check
	    if ( $infos[status] != $data[status] )
		$_is_error = 1;

	    // order_cs check
	    if ( $infos[order_cs] != $data[order_cs] )
		$_is_error = 1;

	    // trans_no check
	    if ( $infos[trans_no] != $data[trans_no] )
		$_is_error = 1;
	    
	    if ( $infos[trans_who] != $data[trans_who] )
		$_is_error = 1;

            $data[pack] = (int)$data[pack];
	    if ( $infos[pack] != $data[pack]  )
		$_is_error = 1;

            // 송장 입력 상태 인데 warehouse가 없는 경우
	    if ( $infos[status] >= 7 && $infos[warehouse]=='' )
		$_is_error = 1;

	    if ( $_is_error )
	    {
		$arr_result[] = array( "$data[seq]/$data[pack]/$infos[pack] ($data[warehouse]/$infos[warehouse]) $data[priority]",
                                       $data[collect_date],
                                       $data[status],
                                       $data[order_cs],
                                       $data[trans_no],
                                       $data[trans_date],
                                       $data[trans_date_pos],
                                       $infos[collect_date],
                                       $infos[status],
                                       $infos[order_cs],
                                       $infos[trans_no],
                                       $infos[trans_date],
                                       $infos[trans_date_pos],
                                       $data[seq],
                                       $data[pack],
                                       "$data[trans_who]/$infos[trans_who]",
                                );
		$j++;
	    }	
	    $i++;
	    $this->show_txt ( " $j / $i 진행중 " );
	}	

	$this->hide_wait();
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function build_query()
    {
	global $start_date, $end_date, $switch_date, $status, $order_cs;

	$query = "select a.*
                    from orders a, products b
                   where a.product_id = b.product_id
		     and b.use_3pl = 1
                     and a.${switch_date} >= '$start_date 00:00:00'
                     and a.${switch_date} <= '$end_date 23:59:59'";

	if ( $status == "X" )
	    $query .= " and a.status not in ( 1,7,8) ";
        else
            if ( $status )
	        $query .= " and a.status = $status ";

	if ( $order_cs == "X")
	    $query .= " and a.order_cs not in ( 0,1,2,3,4,5,6,7,8,9,10,11,12,13 )";
        else
	{
	    switch( $order_cs )
	    {
		case "1": // 정상
		    $query .= " and a.order_cs = 0";
                    break;
		case "2": // 취소
		    $query .= " and a.order_cs in ( 1,12,3,4,2 )";
		    break;
		case "3": // 교환
		    $query .= " and a.order_cs in ( 11,5,7,13,6,8 )";
		    break;
	    }
	}
	return $query;
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

    //
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
                     and a.status in (1,7)";

	$result = mysql_query ( $query, $connect );
	$cnt = 0;	
	$_date = "";

	while ( $data = mysql_fetch_array( $result ) )
	{
		// name과 option을 가져와야 함
		class_product::get_product_name_option($data[product_id], &$name, &$option);

		$data[product_name] = $name;
		$data[options] = $option;

		if ( $obj_3pl->order_reg( $data ) )
		{
		    $_date = $data[collect_date];
		}
		$cnt++;
		$this->show_txt( "$cnt 개 완료" );
  	}

	$this->hide_wait();

	$this->jsAlert( "$cnt건 요청 완료");
	$this->redirect( "?" . base64_decode( $link_url ) );
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
                     and status in (1,7)";

	$result = mysql_query ( $query, $connect );

	$cnt = 0;	
	$_date = "";
	while ( $data = mysql_fetch_array ( $result ) )
	{
		// name과 option을 가져와야 함
		class_product::get_product_name_option($data[product_id], &$name, &$option);

		$data[product_name] = $name;
		$data[options] = $option;

		if ( $obj_3pl->order_reg( $data ) )
		{
		    //echo "     cnt: $cnt<br>";
		    $_date = $data[collect_date];
		    $cnt++;
		}
		$this->show_txt( "$cnt 개 완료" );
  	}
	
	// 3pl_tx에 data 등록
	// type: Delivery
	$obj_3pl->regist_tx( "D", $cnt, "배송요청", $_SESSION[LOGIN_ID] , $_date );

	$this->hide_wait();
	$this->jsAlert( "${cnt}건 요청 완료");
	$this->redirect( "?" . base64_decode( $link_url ) );
    }
}

?>
