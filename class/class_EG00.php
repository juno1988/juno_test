<?
//=================================
//
// �뷮 �޽��� ���� ����
// date: 2007.10.4 -jk
//
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require "ExcelReader/reader.php";
include "lib/xmlrpc.inc";
include "lib/xmlrpcs.inc";
include "lib/xmlrpc_wrappers.inc";

////////////////////////////////
// class name: class_EF00
//
class class_EG00 extends class_top {

  function EG00()
  {
	global $connect;
	global $template, $page;

	if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
	if (!$end_date)   $end_date = date('Y-m-d');

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //===============================
  // list ����
  // date: 2007.10.6
  function modify_list()
  {
	global $_data, $title_id, $connect;

	//=====================================
	// $title_id�� ����� ���� ����
	// ���� �� ���� �����Ѵ� - 2007.10.10 jk
	$query = "delete from sms_sendList where title_id='$title_id'";
	mysql_query ( $query, $connect );

	$_data = iconv("UTF-8", "CP949", $_data );

	$length = strlen( $_data );

	$begin_pos = true;
	$end_pos = 0;
	$i=0;
	
	// ��ȸ�� �Ǵµ��� 
	while ( $begin_pos != 0 )
        {
		// ul�� ��Ī�� str�� �����´�.
		$begin_pos = strpos($_data, "<UL>", $end_pos );
		$end_pos   = strpos($_data, "</UL>", $begin_pos );	
		$key_len = $end_pos - $begin_pos;

		$this_key = substr($_data ,$begin_pos ,$key_len);

		//=======================================
		// �Է�
		// �� ��° ��ȸ�� �˻� ���� ���� ��� ����
		if ( $i >= 1 and $begin_pos == 0 )
			break;
		else
		{
			echo "[ $i] =======================\n";
			$this->insertData( $title_id, $this_key );
		}

		//===========================================
		// $i�� 0�̸� ������ begin_pos = $end_pos��
		// $i�� 0���� ũ�ų� 
		if ( $i == 0 or $begin_pos != 0 )
			$begin_pos = $end_pos;

		// ù ��° data�� ���� ���� ���
		if ( $i == 0  and $begin_pos == 0 )
			break;
		$i++;
	}
  }

  function insertData( $title_id, $_data )
  {
	global $connect;

	$begin_pos = true;
        $end_pos = 0;
        $i=0;

	$_info = array ();

	//=========================================
        // data parsing
        while ( $begin_pos != 0 )
        {
		// seq, user, mobile�� ����
                if ( $i == 3 ) break;

                // ul�� ��Ī�� str�� �����´�.
                $begin_pos = strpos($_data, "<LI>", $end_pos ) + 4;
                $end_pos   = strpos($_data, "</LI>", $begin_pos );
                $key_len = $end_pos - $begin_pos;

                $this_key = substr($_data ,$begin_pos ,$key_len);

		$_info[ $i ] = $this_key;

		// echo "val: $begin_pos / $end_pos / $key_len : $this_key\n";

                // $i�� 0���� ũ�ų� 
                if ( $i == 0 or $begin_pos != 0 )
                        $begin_pos = $end_pos;
                $i++;
        }

	//==================================
	//
	// �ֹ��ڿ��� sms�� ������.
	//
	$seq = $_info[0];
	$user = $_info[1];
	$number = $_info[2];

	// seq�� ���ڸ�..
	$seq = str_replace( "val_", "", $seq );
	$query = "insert into sms_sendList 
			set title_id='$title_id', order_seq=$seq, order_name='$user', mobile='$number'";

	// echo $query . "\n";
	mysql_query ( $query, $connect );
  }

  //========================
  // for parse xml
  // date: 2007.10.6
  function parseMol($mvalues) 
  {
    for ($i=0; $i < count($mvalues); $i++) {
        $mol[$mvalues[$i]["tag"]] = $mvalues[$i]["value"];
    }
    return new AminoAcid($mol);
  }


  //===============================
  // action ����
  // date: 2007.09.27 - jk
  function reg_action()
  {
	global $connect, $_action, $message, $title_id;
	
	$_action = iconv("UTF-8", "CP949", $_action );
	$message = iconv("UTF-8", "CP949", $message);
	$query = "insert into sms_transaction set action='$_action', message='$message',title_id='$title_id' ";
	echo $query;
	mysql_query ( $query, $connect );
  }

  //===============================
  // transaction history��� 
  // date: 2007.09.27 - jk
  function get_tx_history()
  {
	global $connect, $title_id;

	$_tx = $this->get_transaction( $title_id );
	echo $this->build_tx_table( $_tx );

	/*
	$query = "select * from sms_transaction where title_id='$title_id'";
	$result = mysql_query ( $query, $connect );
	while ( $data = mysql_fetch_array ( $result )) 
	{
		echo "$data[action]<br>";
	}
	*/
  }

  //===============================
  // title ����
  // date: 2007.09.20 - jk
  function add_title()
  {
	global $connect, $txt_title;

	$txt_title = iconv("UTF-8", "CP949", $txt_title);
	$query = "insert into sms_title set name='$txt_title'";
	mysql_query ( $query, $connect );

	$query = "select max(title_id) from sms_title";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_row( $result );
?>

<ul>
	<li id='query'><?= $query?></li>
	<li id='success'>true</li>
	<li id='title_id'><?= $data[0] ?></li>
</ul>

<?
  }

  //===============================
  // ��� ��� ����
  // date: 2007.09.27 - jk
  function save_list()
  {
	global $connect, $send_list, $title_id, $order_name, $shop_name, $mobile;

	$order_name = iconv("UTF-8", "CP949", $order_name);
	$shop_name  = iconv("UTF-8", "CP949", $shop_name);

	$success = "success";

	// ������ ��ȣ�� �ִ��� ���� check
	$query = "select * from sms_sendList where title_id='$title_id' and mobile='$mobile'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_row ( $result );

	if ( $data[0] )
		$success = "fail";

	// ������ ��ȣ�� ���� ��쿡�� �Է� �۾� ����
	if ( $success == "success" )
 	{
		$query = "insert into sms_sendList set title_id='$title_id', order_name='$order_name', mobile='$mobile', shop_name='$shop_name'";
		mysql_query ( $query, $connect );
	}
?>

<ul>
	<li id='query'><?= $query?></li>
	<li id='success'><?= $success ?></li>
</ul>


<?
  }

  //===============================
  // #mail_history�� �˻� ��� ���
  // date: 2007.09.20 - jk
  function search_history()
  {
	global $connect, $str_query;
	$str_query = iconv("UTF-8", "CP949", $str_query);

	$query = "select *, DATE_FORMAT( crdate, '%Y-%m-%d %H:%i:%s') fcrdate 
		    from sms_title where name like '%$str_query%' order by crdate desc";
	$result = mysql_query ( $query, $connect );

	$rows= array();
	while ( $data = mysql_fetch_array ( $result ) )	
	{
		$_tx = $this->get_transaction( $data[title_id] );
		$rows[] = array(
			'title_id'    => $data[title_id],
			'name'        => $data[name],
			'date'        => $data[fcrdate],
			'transaction' => $_tx,
		);
	}

	// print_r ( $rows );

 	//========================================
	// �۾� �̷� ��ȸ�� ���� table����
	$this->build_table2( $rows );
  }

  //=============================================
  // date: 2007.9.21 jkryu
  // ��۸���� transaction���� ���� ����
  // ���� ���� �̷�
  function get_transaction( $title_id )
  {
	global $connect;

	$query = "select *, DATE_FORMAT( senddate, '%Y-%m-%d %H:%i:%s') senddate  from sms_transaction where title_id='$title_id' order by senddate desc";
// return "debug q1:" . $query;
	$result = mysql_query ( $query, $connect );
	
	$rows= array();
	while ( $data = mysql_fetch_array ( $result ) )	
	{
		$rows[] = array(
			'tx_id'     => $data[tx_id],
			'action'    => $data[action],
			'message'   => $data[message],
			'senddate'  => $data[senddate]
		);
	}		
	return $rows;
  }

  //====================================================
  // title�� Ŭ�� �� ���..
  // date: 2007.09.20 - jk
  //
  function get_sendInfo()
  {
	global $title_id, $connect;

	$query = "select * from sms_sendList where title_id='$title_id'";
	$result = mysql_query( $query, $connect );
	
	$rows= array();
	while ( $data = mysql_fetch_array ( $result ) )	
	{
		$rows[] = array(
			'seq'        => $data[order_seq],
			'order_name' => $data[order_name],
			'shop_name'  => $data[shop_name],
			'mobile'     => $data[mobile],
		);
	}

	// list ��ȸ
	$this->build_table(  $rows, $title_id );
  } 


  //=================================================
  // ��ü �ֹ� ��ȸ..
  // date: 2007.09.18 - jk
  function get_list()
  {
	global $connect, $query_string, $_type,$order_status, $order_cs, $term_date, $shop_id, $supply_id, $disp_cnt, $option_string;

	$query_string = iconv("UTF-8", "CP949", $query_string);
	$option_string = iconv("UTF-8", "CP949", $option_string);

	// term date
	$term_date = "-$term_date day";
	$begin_date = date('Y-m-d', strtotime( $term_date ));;
	$end_date = date('Y-m-d');;


	$query = "select a.order_mobile,a.recv_mobile, a.order_name, a.shop_id, a.recv_name, a.collect_date, 
			 a.status, a.order_cs,b.name, a.seq, b.options b_opt, a.options a_opt
		    from orders a, products b
		   where a.product_id = b.product_id 
                     and a.collect_date >= '$begin_date' and a.collect_date <= '$end_date'";

	if ( $query_string )	
	{
		switch( $_type )
		{
			case "0":	// ��ǰ��
				$query .= " and b.name like '%$query_string%'";
			break;
			case "1":	// �ֹ���
				$query .= " and a.order_name = '$query_string'";
			break;
		}
	}

	if ( $option_string )
	{
		if ( $_SESSION[STOCK_MANAGE_USE] )
			$query .= " and b.options like '%$option_string%'";
		else
			$query .= " and a.options like '%$option_string%'";
	}

	// echo "_type: $_type / query_string: $query_string / order_status: $order_status / order_cs: $order_cs / term_date: $term_date/ shop_id: $shop_id / supply_id: $supply_id ";

	// �ֹ� ����
	if ( $order_status )
		$query .=" and a.status = '$order_status'";

	// cs ����
	if ( $order_cs )
	{
		switch ( $order_cs )
		{
			case 1: // ����
				$query .= " and a.order_cs =0";
			break;
			case 2: // ���
				$query .= " and a.order_cs in (1,2,3,4,12)";
			break;
			case 3: // ��ȯ
				$query .= " and a.order_cs in (5,6,7,8,11,13,9,10)";
			break;
		}
	}

// echo $query;

	// �Ǹ�ó
	if ( $shop_id )
		$query .= " and a.shop_id='$shop_id'";

	// ����ó
	if( $supply_id )
		$query .= " and a.supply_id='$supply_id'";
		

	// limit
	$query .= " group by pack, seq limit $disp_cnt";

	// echo $query;

	$result = mysql_query( $query, $connect );
	
	$rows= array();
	while ( $data = mysql_fetch_array ( $result ) )	
	{
		$product_name = cutStr ( $data[name], 20 ) . "..";

		if ( $_SESSION[STOCK_MANAGE_USE] )
			$product_options = cutStr ( $data[b_opt], 10 ) . ".";
		else
			$product_options = cutStr ( $data[a_opt], 10 ) . ".";

		$rows[] = array(
			'seq'              => $data[seq],
			'order_name'       => $data[order_name],
			'shop_name'        => $data[shop_id],
			'mobile'           => $data[order_mobile] ? $data[order_mobile] : $data[recv_mobile],
			'product_name'     => $product_name,
			'product_options'     => $product_options,
		);
	}


	$this->build_table( $rows );
  }

  //================================
  // mail_history�� table����
  function build_table2( $rows )
  {

?>
<table border='0' cellpadding='0' cellspacing='0'>
  <?php
 
  $i = 0; 
  foreach( $rows as $row )
  {
      $title_id   = htmlentities( $row['title_id'], ENT_QUOTES );
      //$name = htmlentities( $row['name'], ENT_QUOTES );
      $name = $row['name'];
      // $date = htmlentities( $row['date'], ENT_QUOTES );
      $date = $row['date'];
  ?>
  <tr onClick="javascript:get_sendInfo('<?php echo( $title_id); ?>','<?php echo( $name); ?>')" class="clickable">
  <td><?php echo( $date); ?> </td>
  <td class="title">&nbsp;<?php echo( $name ); ?> </td>
  </tr>
  <?php
    //=======================================================
    // transaction�� ���� ��� transaction�� ���� table����  
    // $this->get_transaction() ���� �޾ƿ�
    //
    //if ( $row['transaction'] )
    //	$this->build_tx_table( $row['transaction'] );
  }
  ?>
</table>

<?
  }

  function get_sms_count()
  {
	// sms ������ ���� Ȯ��
	$sys_connect = sys_db_connect();
	$query = "select sms from sys_domain where id='" ._DOMAIN_ . "'";	

	$result = mysql_query ( $query, $sys_connect );
	$data = mysql_fetch_array ( $result );
	echo $data[sms];
  }

  //===================================
  // message ����
  // date: 2007.9.28
  function send_message()
  {
	global $mobile, $message, $sender, $receiver;

	// sms ������ ���� Ȯ��
	$sys_connect = sys_db_connect();
	$query = "select sms from sys_domain where id='" ._DOMAIN_ . "'";	

	$result = mysql_query ( $query, $sys_connect );
	$data = mysql_fetch_array ( $result );
	$sms_count =  $data[sms];

echo "<ul>";
	if ( $sms_count <= 0 )
	{
		echo "<li id='result'>fail</li><li id='message'>������ �ʿ��մϴ�.</li><li id='sms_count'>0</li></ul>";
		exit;
	}
 
	$msg = iconv("UTF-8", "CP949", $message);

	// echo "[$mobile] $msg";

	// xml-rpc�� ����� �����Ѵ�
	// 1.init
	$xmlrpc_client = new xmlrpc_client('/dashboard/services/sms.cgi','dashboard.ezadmin.co.kr',80);

	$auth_code = "123";
	$host = "http://dashboard.ezadmin.co.kr";
	$port = 80;

	$pattern = "/(\D+)/";
	$replacement = "";
	$sender   = $sender ? $sender : 0;
	$sender   = preg_replace($pattern, $replacement, $sender);
	$receiver = preg_replace($pattern, $replacement, $mobile);

	$domain   = "RPC";
	$user     = "service";

	// 2 create message with a parameter
	$_msg= new xmlrpcmsg('sendSms',
				array(
					new xmlrpcval($auth_code,'string'),
					new xmlrpcval($sender,'string'),
					new xmlrpcval($receiver,'string'),
					new xmlrpcval($msg,'base64'),
					new xmlrpcval($domain,'string'),
					new xmlrpcval($user,'string'),
			)
		);

	// 3 send
	// ������ �κ��� ��� ����
	$_response = $xmlrpc_client->send($_msg);

	// 4 response üũ
	$struct = $_response->value();
	$_dummy = php_xmlrpc_decode( $struct );

	//echo "\n-result ----------------------------\n";
	//echo $_dummy;

	// sms�� ������ �ϳ� �ٿ��� ��
	$query = "update sys_domain set sms=sms-1 where id='" . _DOMAIN_ . "'";
	mysql_query ( $query, $sys_connect );

	if ( $_dummy == "ok " )
		echo "<li id='result'>success</li><li id='count'>" . --$sms_count . "</li><li id='message'>$sender/$receiver/$msg/$_dummy</li></ul>";
	else
		echo "<li id='result'>fail</li><li id='count'>" . $sms_count . "</li><li id='message'>$_dummy</li></ul>";
 }

  //==================================
  // message ���
  function get_txinfo()
  {
	global $connect, $tx_id;
	$query = "select message from sms_transaction where tx_id=$tx_id";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_row( $result );
	echo $data[0];
  }

  //=======================================================
  // date: 2007.9.21 - jk
  // transaction�� ���� table����
  //
  function build_tx_table( $rows )
  {

  ?>
<!--table class="transaction"-->
  <?php
 
  $i = 0; 
  foreach( $rows as $row )
  {
      $action   = $row['action'];
      $tx_id    = htmlentities( $row['tx_id'], ENT_QUOTES );
      $senddate = htmlentities( $row['senddate'], ENT_QUOTES );
  ?>
  <div><?php echo( $senddate); ?> </td><td><?= $action ?></div>
  <div class='content'><?= $row[message] ?></div>

  <?php
  }
  ?>

<?
  }

  //================================
  //
  function build_table( $rows, $title_id=0 )
  {
	global $connect;

	// title check
	$title = "";

	// history check	
	if( $title_id )
	{
		$_tx = $this->get_transaction( $title_id );
	}
?>

<ul id="history">
  <li id="title"><?= $title ?></li>
  <li id="tx"><? if( $_tx) echo( $this->build_tx_table( $_tx ) ); ?></li>
  <li id="list">
<table border='0' cellspacing='0' cellpadding='0'>
  <?php
 
  $i = 0; 
  foreach( $rows as $row )
  {
	$i++;

      $seq        = $row['seq'];
      $order_name = $row['order_name'];
      $shop_name  = $row['shop_name'];
      $mobile     = $row['mobile'];
      $product_name = $row['product_name'];
      $product_options= $row['product_options'];
  ?>
  <tr id="val_<?= $seq ?>">
  <td><input type='checkbox' class='check_box' value='val_<?= $seq ?>' onClick='javascript:_checked( this )'></td>
  <td><?= $order_name ?></td>
  <td><?= $mobile ?> </td>
  <!--td><?= $shop_name?> </td-->
  <td><?= $product_name?> </td>
  <td><?= $product_options?> </td>
  </tr>
  <?php
  }
  ?>
</table>
  </li>
</ul>
<?
  }
}

class AminoAcid {
    var $name;  // aa name
    var $symbol;    // three letter symbol
    var $code;  // one letter code
    var $type;  // hydrophobic, charged or neutral
    
    function AminoAcid ($aa) 
    {
        foreach ($aa as $k=>$v)
            $this->$k = $aa[$k];
    }
}

