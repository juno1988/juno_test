<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_E600
//

class class_E600 extends class_top {

    ///////////////////////////////////////////

    function E600()
    {
	global $connect;
	global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;


	$line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 day'));
        $end_date = $_REQUEST["end_date"];

        if ( $page )
        {
	   echo "<script>show_waiting();</script>";
           $this->cs_list( &$total_rows, &$r_cs );
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        if ( $page )
	   echo "<script>hide_waiting();</script>";
    }

    function E601()
    {
	global $connect;
	global $template;

        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $content = "==반품정보==\n반품택배사:\n반품송장번호:\n";
        $content .= "환불계좌:\n환불은행:\n예금주:\n ";

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////////////
    // cs list 
    // date : 2005.9.14
    function cs_list( &$total_rows, &$result )
    {
       global $connect, $status;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = $_REQUEST[keyword];
       $start_date = $_REQUEST[start_date];
       $end_date = $_REQUEST[end_date];
       $order_cs = $_REQUEST[order_cs];
       $shop_id = $_REQUEST[shop_id];
       $supply_id = $_REQUEST[supply_id];
       $page = $_REQUEST[page];
       $act = $_REQUEST[act];
       $line_per_page = _line_per_page;

   //////////////////////////////////////////////
  // 검색
  $starter = $page ? ($page-1) * $line_per_page : 0;

  $options = "";

  // 검색키워드
  if ($keyword)
  {
    if ($search_type == 1) // 주문자
        $options .= "and a.order_name like '%${keyword}%'" ;
    else if ($search_type == 2) // 주문번호
        $options .= "and a.order_id = '${keyword}'" ;
    else if ($search_type == 3) // 상품명
        $options .= "and a.product_name like '%${keyword}%' " ;
    else if ($search_type == 4) // 전화번호
        $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;
    else if ($search_type == 5) // 수령자 
        $options .= "and (a.recv_name like '%$keyword%') " ;
  }

  // 판매처
  if ($shop_id != '')
    $options .= "and a.shop_id = '${shop_id}'" ;

  // 공급처
  if ($supply_id != '')
    $options .= "and a.supply_id = '${supply_id}'" ;

  // 취소 상태
  if ( $status )
    $options .= " and d.status = '$status'";

  /////////////////////////////////////////////////////
  $sql = "select a.*, b.shop_name, c.name supply_name, d.qty cancel_qty, d.order_status, d.status as cancel_status, d.cancel_req_date
            from orders a, shopinfo b, userinfo c, part_cancel d";

  $count_sql = "select count(*) cnt from orders a, shopinfo b, userinfo c, part_cancel d ";

  $where_clause = "
           where a.seq = d.order_seq
             and d.cancel_req_date >= '$start_date'
             and d.cancel_req_date <= '$end_date'
             and a.shop_id = b.shop_id
             and a.supply_id = c.code
                 ${options}
           order by d.seq desc";

  $limit_clause = " limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;

  $result_cnt = mysql_query($count_sql.$where_clause, $connect) or die(mysql_error());

  $list = mysql_fetch_array($result_cnt);
  $total_rows = $list[cnt];

  $query = $sql.$where_clause.$limit_clause;
//echo $query;

  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

    ////////////////////////////////////////////////
    // CS 상세 정보
    function get_detail ()
    {
       global $connect, $seq;

       ///////////////////////////////////////
       $sql = "select a.*, b.status order_status, b.seq as cancel_seq
                 from orders a, part_cancel b where a.seq = b.order_seq and a.seq = '$seq'";
       $list = mysql_fetch_array(mysql_query($sql, $connect));

       return $list;
    }
    
    function change_status()
    {
       global $connect, $cancel_seq, $status, $link_url;

       $transaction = $this->begin( "부분취소 상태변경" );

       $query = "update part_cancel set status='$status' where seq='$cancel_seq'";
       mysql_query( $query, $connect );

       $this->end( $transaction );
       $this->redirect ( base64_decode( $link_url ));
       exit; 
    }

    ////////////////////////////////////////
    //
    function csinsert()
    {
	global $connect;
	global $seq, $template, $order_seq, $cs_type, $cs_reason, $cs_result, $content, $trans_who, $trans_fee;

        $transaction = $this->begin( "부분취소 일반상담" );

	$writer = $_SESSION[LOGIN_NAME];

        $link_url = "?template=$template&seq=$seq";

	$sql = "insert into csinfo set 
		  order_seq = '$seq',
		  input_date = now(),
		  input_time = now(),
		  writer = '$writer',
		  cs_type = '$cs_type',
		  cs_reason = '$cs_reason',
		  cs_result = '1',
		  content = '" . addslashes( $content) ."'";

//echo $sql;
//exit;
 	mysql_query($sql, $connect) or die(mysql_error());

        $this->end( $transaction );

        $this->redirect ( $link_url );
    }

}

?>
