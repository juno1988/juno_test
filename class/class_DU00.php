<?
//====================================
//
// date: 2007.10.11
// desc: ckcompany�� ���� ��۰���
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
	$this->begin("DU00 ����");
	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //==================================
  // ��� ��û
  // date: 2007.10.17
  function sys_pos_transfer()
  {
	global $domain;
	$sys_connect = sys_db_connect();
	$domain = $domain ? $domain : _DOMAIN_;

	$query = "update sys_domain set tx_status=1 where id='$domain'";
	mysql_query ( $query, $sys_connect );
	// echo $query;
	echo "���� ���� ��û �Ϸ�";
  }

  function set_change_confirm()
  {
	global $connect, $seq;

	$query = "update orders set order_cs=13 where seq=$seq";
	mysql_query ( $query, $connect );
  }

  //============================
  // ��� �Ϸ� ���·� ����
  function reconfirm()
  {
	global $connect, $trans_no;

	//��� �Ϸ���� ���¸� �ٲ��� �ʴ´�
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
  // ���� Ȯ��
  // date: 2007.10.12
  function confirm()
  {
	global $connect, $trans_no;

	$obj_order = new class_order();

	$pattern = "/(\D+)/";
	$replacement = "";
	$trans_no = preg_replace($pattern, $replacement, $trans_no);
	$info = array();

	// ��� ���°� �ƴ� ��� �ֹ�
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

		// �̹� ��� ��
		if ( $data[status] == 8 )
			$info[status] = "already_trans";	
		
		class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );

		// �κ� ��� ó��
		if ( $obj_order->get_part_cancel ( $data[seq] ) )
		{
		    $data[order_cs] = 1;// ��� ó��
		}

		switch ( $data[order_cs] )
		{
			case 0:
				$status = "����";
				break;
			case 3:
				$status = "��ҿϷ�";
				break;
			case 1:
			case 2:
			case 4:
			case 12:
				$status = "���";
				break;
			case 5:
				$status = "��ȯ��û";
				break;
			case 6:
			case 7:
			case 8:
			case 9:
			case 10:
			case 11:
				$status = "��ȯ";
				break;
			case 13:
				$status = "��ȯȮ��";
				break;
			default:
				$status = $data[order_cs];
		}

		if ( $data[status] == 8 )
			$trans_status = "��� ��";	 
		else
			$trans_status = "��� ��";

		$info[product_list] .= "<tr class='content1' ";

		if ( $data_status==8) 
			$info[product_list] .= " class='gray'" . ">";
		else 
			$info[product_list] .= "><td>$data[seq]</td><td>$data[location]</td><td>$product_name</td><td>$product_option</td><td>$trans_status</td><td>$status</td></tr>";
	}

	// cs info ��ȸ
	$info[cs_info] = "����, �������Դϴ�. <br>";
	/*
	$query = "select b.* from orders a, csinfo b where a.seq = b.order_seq and a.trans_no = '$trans_no'";
	$result = mysql_query ( $query, $connect );
	while ( $data = mysql_fetch_array ( $result ) )
		$info[cs_info] .= "$data[input_date] $data[input_time]<br> $data[content]<br>";
	*/	

	$this->build_result( $info );
  }

  // ����� ���
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
