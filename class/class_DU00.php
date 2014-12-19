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
require_once "class_order.php";

////////////////////////////////
// class name: class_DU00
//
class class_DU00 extends class_top {

  function DU00()
  {
	global $connect;
	global $template, $page;
	$this->begin("DU00 시작");
	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //==================================
  // 배송 요청
  // date: 2007.10.17
  function sys_pos_transfer()
  {
	global $domain;
	$sys_connect = sys_db_connect();
	$domain = $domain ? $domain : _DOMAIN_;

	$query = "update sys_domain set tx_status=1 where id='$domain'";
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

	//취소 완료건은 상태를 바꾸지 않는다
	$query = "update orders set status=8 ,trans_date_pos=Now() where trans_no='$trans_no' and order_cs <> '3'";
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

	$obj_order = new class_order();

	$pattern = "/(\D+)/";
	$replacement = "";
	$trans_no = preg_replace($pattern, $replacement, $trans_no);
	$info = array();

	// 배송 상태가 아닌 모든 주문
	$query = "select * from orders where trans_no='$trans_no' and order_cs <> 3";
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
			$info[order_name]   = $data[order_name] . "/" . $data[recv_name];
			$info[recv_address] = $data[recv_address];
			$info[recv_mobile] = $data[recv_mombile];
			$info[trans_price]  = $data[trans_price];
			$info[hold]         = $data[hold];
		}
		$i++;

		// 이미 배송 함
		if ( $data[status] == 8 )
			$info[status] = "already_trans";	
		
		class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );

		// 부분 취소 처리
		if ( $obj_order->get_part_cancel ( $data[seq] ) )
		{
		    $data[order_cs] = 1;// 취소 처리
		}

		switch ( $data[order_cs] )
		{
			case 0:
				$status = "정상";
				break;
			case 3:
				$status = "취소완료";
				break;
			case 1:
			case 2:
			case 4:
			case 12:
				$status = "취소";
				break;
			case 5:
				$status = "교환요청";
				break;
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
			case 11:
				$status = "교환";
				break;
			case 13:
				$status = "교환확인";
				break;
			default:
				$status = $data[order_cs];
		}

		if ( $data[status] == 8 )
			$trans_status = "배송 후";	 
		else
			$trans_status = "배송 전";

		$info[product_list] .= "<tr class='content1' ";

		if ( $data_status==8) 
			$info[product_list] .= " class='gray'" . ">";
		else 
			$info[product_list] .= "><td>$data[seq]</td><td>$data[location]</td><td>$product_name</td><td>$product_option</td><td>$trans_status</td><td>$status</td></tr>";
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
  <li id="hold"><?= $info[hold] ?></li>
</ul>
<?
  }
}
?>
