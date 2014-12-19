<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_E400
//
class class_E400 extends class_top {

    ///////////////////////////////////////////

    function E400()
    {
	global $connect;
	global $template;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;

	$line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-2 day'));

        $this->cs_list( &$total_rows, &$r_cs );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////////////
    // cs list 품절 주문 리스트 
    // date : 2005.9.26
    function cs_list( &$total_rows, &$result )
    {
       global $connect; 
       global $start_date;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = $_REQUEST[keyword];
       $end_date = $_REQUEST[end_date];
       $order_cs = $_REQUEST[order_cs];
       $shop_id = $_REQUEST[shop_id];
       $supply_id = $_REQUEST[supply_id];
       $page = $_REQUEST[page];
       $act = $_REQUEST[act];
       $line_per_page = _line_per_page;

  //////////////////////////////////////////////
  // 검색
  if ( !$page )
     $page = 1;
  $starter = ($page-1) * $line_per_page;

  $options = "";

  // 검색키워드
  if ($keyword)
  {
    if ($search_type == 1) // 주문자
        $options .= "and a.order_name = '${keyword}'" ;
    else if ($search_type == 2) // 주문번호
        $options .= "and a.order_id = '${keyword}'" ;
    else if ($search_type == 3) // 상품명
        $options .= "and a.product_name like '%${keyword}%' " ;
    else if ($search_type == 4) // 전화번호
        $options .= "and (a.recv_tel = '$keyword' or a.recv_mobile = '$keyword') " ;
  }

  // 주문상태
  if ($order_cs != '')
    $options .= "and a.order_cs = '${order_cs}'" ;

  // 판매처
  if ($shop_id != '')
    $options .= "and a.shop_id = '${shop_id}'" ;

  // 공급처
  if ($supply_id != '')
    $options .= "and a.supply_id = '${supply_id}'" ;


  /////////////////////////////////////////////////////
  $sql = "select * from orders a, products b
            where a.product_id = b.product_id";

  $count_sql = "select count(*) cnt from orders a, products b
                 where a.product_id = b.product_id";

  $where_clause = "
                 ${options}
             and a.collect_date >= '$start_date'
             and a.collect_date <= '$end_date'
             and (b.enable_sale = 0 or b.enable_sale is null)
             order by a.order_date desc
           ";

  $limit_clause = " limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;
  $result = mysql_query($query, $connect) or die(mysql_error());
  $data = mysql_fetch_array ( $result );
  $total_rows = $data[cnt];

//echo $sql . $where_clause . $limit_clause;
  $query = $sql.$where_clause.$limit_clause;
  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

    function get_shop_name( $shop_id )
    {
       return class_C::get_shop_name ( $shop_id );
    }
}

?>
