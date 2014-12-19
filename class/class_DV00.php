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
// class name: class_DV00
//
class class_DV00 extends class_top {

  function DV00()
  {
	global $connect;
	global $template, $page;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  // list 저장
  function save_list()
  {
	global $connect, $title_id, $trans_no_list;

	$pattern = "/(\D+)/";
	$replacement = "";
	$title_id = preg_replace($pattern, $replacement, $title_id );

	$query = "update packing_list set transno_list='$trans_no_list' where reg_date='$title_id'";
	mysql_query ( $query, $connect );

	echo "출력 완료";
  }

  //=========================================
  // title 저장
  function save_title()
  {
	global $title, $connect;
	$title= iconv("UTF-8", "CP949", $title);
	$query = "insert into packing_list set name='$title'";
	$result = mysql_query( $query, $connect );

	// key값인 reg_date를 가져온다
	$query = "select reg_date from packing_list order by reg_date desc limit 1";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	echo $data[reg_date];
  }

  //==================================
  // 배송 요청
  // date: 2007.10.17
  function sys_pos_transfer()
  {
	$sys_connect = sys_db_connect();

	$query = "update sys_domain set tx_status=1 where id='" ._DOMAIN_ . "'";
	mysql_query ( $query, $sys_connect );
	// echo $query;
	echo "송장 전송 요청 완료";
  }

  function set_change_confirm()
  {
	global $connect, $seq;

	$query = "update orders set order_cs=13 where seq=$seq";
	mysql_query ( $query, $connect );
  }

  //============================
  // 배송 완료 상태로 변경
  function reconfirm()
  {
	global $connect, $trans_no;

	$query = "update orders set status=8 ,trans_date_pos=Now() where trans_no='$trans_no'";
	$result = mysql_query ( $query, $connect );
	$rows = mysql_affected_rows();

	$query = "update orders set order_cs=7 where trans_no='$trans_no' and order_cs=13";
	$result = mysql_query ( $query, $connect );

echo "<ul>";

	if ( $rows >= 1 )
		echo "<li id='status'>success</li>";	
	else
		echo "<li id='status'>fail</li>";	

echo "</ul>";
  }

  //=============================
  // 송장 확인
  // date: 2007.10.12
  function confirm()
  {
	global $connect, $trans_no;

	$info = array();

	$query = "select * from orders where trans_no='$trans_no'";
	$result = mysql_query ( $query, $connect );
	$row = mysql_num_rows( $result );
	
	if ( !$row )
	{
		$info[status] = "fail";	
		$this->build_result( $info );
		exit;
	}

	$i = 0;
	$info[status] = "success";	
	
	while ( $data = mysql_fetch_array ( $result ) )
	{
		if ( $i == 0 )
		{
			$info[order_id]     = $data[order_id];
			$info[shop_name]    = $data[shop_id];
			$info[collect_date] = $data[collect_date];
			$info[order_name]   = $data[order_name];
			$info[recv_address] = $data[recv_address];
			$info[recv_mobile] = $data[recv_mombile];
			$info[trans_price]  = $data[trans_price];
		}
		$i++;

		// 이미 배송 함
		class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );

		$info[product_list] .= "<tr class='content1'><td>$data[product_id]</td><td>$product_name</td><td>$product_option</td><td>$data[qty]</td></tr>";
	}

	// cs info 조회
	$info[cs_info] = "보류, 개발중입니다. <br>";
	/*
	$query = "select b.* from orders a, csinfo b where a.seq = b.order_seq and a.trans_no = '$trans_no'";
	$result = mysql_query ( $query, $connect );
	while ( $data = mysql_fetch_array ( $result ) )
		$info[cs_info] .= "$data[input_date] $data[input_time]<br> $data[content]<br>";
	*/	

	$this->build_result( $info );
  }

  // 결과값 출력
  function build_result( $info )
  {
?>
<ul>
  <li id="status"><?= $info[status] ?></li>
  <li id="order_id"><?= $info[order_id] ?></li>
  <li id="shop_name"><?= $info[shop_name] ?></li>
  <li id="collect_date"><?= $info[collect_date] ?></li>
  <li id="order_name"><?= $info[order_name] ?></li>
  <li id="recv_address"><?= $info[recv_address] ?></li>
  <li id="recv_mobile"><?= $info[recv_mobile] ?></li>
  <li id="trans_price"><?= $info[trans_price] ?></li>
  <li id="cs_info"><?= $info[cs_info] ?></li>
  <li id="product_list"><?= $info[product_list] ?></li>
</ul>
<?
  }
}
?>
