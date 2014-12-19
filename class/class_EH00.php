<?
//====================================
//
// date: 2007.10.11
// desc: ckcompany를 위한 배송검증
//
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_DT00
//
class class_EH00 extends class_top {

  function EH00()
  {
	global $connect;
	global $template, $page;

	$start_date = date('Y-m-d', strtotime('-20 day'));

    $par_arr = array("template","action","search_type","keyword","start_date","end_date","shop_id","page");
    $link_url_list = $this->build_link_par($par_arr);  

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  function send_message()
  {
	echo "send message";
	sleep(0.5);
  }

  function get_list()
  {
	global $connect, $product_name, $options, $start_date, $end_date, $order_status, $order_cs, $shop_id, $supply_id, $page;
	$product_name= iconv("UTF-8", "CP949", $product_name);
	$options = iconv("UTF-8", "CP949", $options);

	// echo "get_list $product_name, $options, $start_date, $end_date, $order_status, $order_cs, $shop_id, $supply_id";

	$query_cnt = "select count(*) cnt ";
	$query= "select a.*,b.name,a.options order_opt, b.options product_opt ";

	if ( $_SESSION[STOCK_MANAGE_USE] )
	{
		$query= "select a.*,b.name,a.options order_opt, b.options product_opt ";
		$query_option = " from orders a, products b
				where a.product_id = b.product_id
				and a.collect_date >= '$start_date'
				and a.collect_date <= '$end_date'";

		if ( $product_name )
			$query_option .= " and b.name like '%$product_name%'";

		if ( $options )
			$query_option .= " and b.options like '%$options%'";
	}
	else
	{
		$query= "select a.* ";
		$query_option = " from orders a
				where a.collect_date >= '$start_date'
				and a.collect_date <= '$end_date'";
			
		if ( $product_name )
			$query_option .= " and a.product_name like '%$product_name%'";

		if ( $options )
			$query_option .= " and a.options like '%$options%'";

	}

	if ( $shop_id )
		$query_option .= " and a.shop_id = '$shop_id'";

	if ( $supply_id )
		$query_option .= " and a.supply_id = '$supply_id'";

	if ( $order_status )
		$query_option .=" and a.status = '$order_status'";

	// cs 상태
	if ( $order_cs )
	{
		switch ( $order_cs )
		{
			case 1: // 정상
				$query_option .= " and a.order_cs =0";
			break;
			case 2: // 취소
				$query_option .= " and a.order_cs in (1,2,3,4,12)";
			break;
			case 3: // 교환
				$query_option .= " and a.order_cs in (5,6,7,8,11,13,9,10)";
			break;
		}
	}

	$page = $page ? $page : 1;
	$start = ($page-1) * 30;
	$limit = " order by collect_date limit $start, 30";

	$total = 0;
	// count 조회
//echo $query_cnt . $query_option;

	$result = mysql_query( $query_cnt . $query_option, $connect );
	$data = mysql_fetch_array ( $result );
	$_total_count = $data[cnt];

	$_result = "<table>
  <tr>
    <td>No</td>
    <td>판매처</td>
    <td>고객</td>
    <td>연락처</td>
    <td>상품명</td>
    <td>옵션</td>
    <td>발주일</td>
  </td>
";
/*
  <tr>
    <td colspan=5> $query . $query_option . $limit  </td>
  </tr>
*/
	// 전체 data 조회
	$result = mysql_query ( $query. $query_option . $limit, $connect );
	$i = 1;
	while ( $data = mysql_fetch_array ( $result ) )
	{
		if ( $_SESSION[STOCK_MANAGE_USE] )
		{
			$product_name = $data[name];
			$options = $data[product_opt];
		}
		else
		{
			$product_name = $data[product_name];
			$options = $data[options];
		}

		$_result .= "
<tr>
  <td> $i </td>
  <td> $data[shop_id] </td>
  <td> $data[order_name] </td>
  <td> $data[order_mobile] </td>
  <td> $product_name </td>
  <td> $options </td>
  <td> $data[collect_date] </td>
</tr>
";
		$i++;
	}

		$_result .= "</table>";

echo "<ul><li id='total_count'>$_total_count </li>";
echo "<li id='content'>$_result</li></ul>";
  }
}
?>
