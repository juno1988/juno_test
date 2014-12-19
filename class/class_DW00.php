<?
//====================================
//
// date: 2007.10.11
// desc: ckcompany를 위한 배송검증
//
require_once "class_top.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_DW00
//
class class_DW00 extends class_top {

  function DW00()
  {
	global $connect;
	global $template, $page;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //========================================
  // 요청한 list출력
  // date: 2007.10.19 - jk
  function get_list()
  {
	global $connect, $title_id;

	$query = "select * from packing_list where reg_date = '$title_id'";
	$result = mysql_query( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$transno_list = $data[transno_list];
?>
<script language=javascript>

  function print_page()
  {
	self.focus();
	self.print();
  }

  function init()
  {
	obj = document.getElementById("content")
	obj.innerHTML = "";
	
  }
</script>
<?
	// 상품 관련 정보..

	if ( !$transno_list )
	{
		echo "<div id='content'>데이터가 없습니다.</div>";
		exit;
	}

	// 마지막 ,를 삭제
	$pattern = "/(\D+)$/";
	$replace = "";
	$transno_list = preg_replace( $pattern, $replace, $transno_list );

	$query = "select seq, product_id, qty from orders where trans_no in ( $transno_list )";
	$result = mysql_query ( $query, $connect );
?>

<div id='content'>
<table border=1 width=100%>
<tr class='title'>
  <td width=100>상품코드</td> 
  <td>상품명</td>
  <td width=150>옵션</td>
  <td width=50>수량</td>
</tr>

<?
	while ( $data = mysql_fetch_array ( $result ) )
	{
		$product_name = "";
		$option = "";
  		class_product::get_product_name_option($data[product_id], &$product_name, &$option);
echo "<tr><td> $data[product_id] </td>
<td>$product_name</td>
<td>$option</td>
<td>$data[qty]</td>
</tr>";

	}
echo "</table></div>";
  }

  function search()
  {
	global $connect, $start_date, $end_date, $page;

	$page = $page ? $page : 1;
	$start = ($page - 1) * 30;
	$query = "select *, DATE_FORMAT(reg_date, '%Y-%m%d %H:%i:%s') d ";
	$query_cnt = "select count(*) cnt ";

	$query_option = "from packing_list where reg_date >= '$start_date 00:00:00' and reg_date <'$end_date 23:59:59'";

	$limiter = " order by reg_date desc limit $start, 30";

	// 총 개수 출력
	$total_count = 0;
	$result = mysql_query ( $query_cnt . $query_option, $connect );
	$data = mysql_fetch_array ( $result );
	$total_count = $data[cnt];
	
	// 결과 출력
	$result = mysql_query ( $query . $query_option . $limiter, $connect );
	echo "<ul><li id='total_count'>$total_count</li>";
	echo "<li id='content'>";
	while ( $data = mysql_fetch_array ( $result ) )
	{
		echo "<a href=\"javascript:detail('$data[reg_date]')\">[" . $data[d] . "]: $data[name] </a><br>";
	}
	echo "</li>";
  }
}
?>
