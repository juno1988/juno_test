<?
class class_notyet{
    function get_infos( $seq )
    {
	global $connect;
	$query = "select * from notyet_deliv where seq=$seq";	
	$result = mysql_query ( $query , $connect);
	return mysql_fetch_array ( $result );
    }

    ////////////////////////////////////
    // 미송 건 조회
    function notyet_list()
    {
        global $connect, $trans_who, $order_status, $shop_id, $trans_who;

        $search_type   = $_REQUEST[search_type];
        $keyword       = $_REQUEST[keyword];
        $trans_who     =  $trans_who;
        $start_date    = $_REQUEST[start_date];
        $end_date      = $_REQUEST[end_date];
        $order_cs      = $_REQUEST[order_cs];
        $shop_id       = $_REQUEST[shop_id];
        $supply_id     = $_REQUEST[supply_id];
        $page          = $_REQUEST[page];
        $act           = $_REQUEST[act];
        $line_per_page = _line_per_page;
 
        $keyword =  $keyword;

	$query = "select a.* 
                   from orders a, notyet_deliv b
                  where a.seq = b.seq ";	

	switch ( $order_status )
	{
	    case "97":	// 접수
		$query .= " and b.status=1 ";
		break;
	    case "96":	// 송장
		$query .= " and b.status=7 ";
		break;
	    case "95": 	// 배송
		$query .= " and b.status=8 ";
		break;
	}

	//=======================================
	// 검색키워드
	if ($keyword)
	{
	    switch ( $search_type )
	    {
		case 1: // 주문자
		    if ( _DOMAIN_ == "leedb" )
			$query .= "and a.order_name like '%${keyword}%'" ;
		    else
			$query .= "and a.order_name = '${keyword}'" ;
		    break;
		case 2: // 주문번호
		    $query .= "and a.order_id = '${keyword}'" ;
		    break;
		case 3: // 상품명
		    $query .= "and a.product_name like '%${keyword}%' " ;
		    break;
		
		case 10:	// 일반 전화
		       $query .= "and a.recv_tel = '$keyword' ";
		break;

		case 4: // 전화번호

		    $arr = array();
		    $arr = split( "-", $keyword );
		    $length = sizeof( $arr );

		    if (_DOMAIN_ == "nak21" or _DOMAIN_ == "ezadmin" )
		    {
			if ( $length <= 2 )
			    $query .= "and a.recv_mobile2 = '$keyword' ";
			else
			    $query .= "and a.recv_mobile = '$keyword' ";
		    }
		    else
			$query .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;

		    break;
		case 5: // 수령자
		    if ( _DOMAIN_ == "leedb" )
			$query .= "and (a.recv_name like '%$keyword%') " ;
		    else
			$query .= "and (a.recv_name = '$keyword') " ;
		    break;
		case 6:  // 송장번호
		    $query .= "and a.trans_no = '$keyword' "; 
		    break;
		case 7: // 어드민 코드
		    $query .= "and a.seq = '$keyword' ";
		    break;
		case 8: // 주문자 전화
		    $query .= "and (a.order_tel like '%$keyword%' or a.order_mobile like '%$keyword%') " ;
		    break;
		case 9: // 상품 코드 
		    $query .= "and a.product_id = '$keyword' " ;
		    break;
	    }
	  }
	$query .= " group by b.pack";

	$result = mysql_query ( $query, $connect );
	return $result;
    }
}
?>
