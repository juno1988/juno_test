<?
//=================================
//
// 대량 메시지 전송 관련
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
  // list 저장
  // date: 2007.10.6
  function modify_list()
  {
	global $_data, $title_id, $connect;

	//=====================================
	// $title_id의 목록을 전부 삭제
	// 삭제 후 새로 저장한다 - 2007.10.10 jk
	$query = "delete from sms_sendList where title_id='$title_id'";
	mysql_query ( $query, $connect );

	$_data = iconv("UTF-8", "CP949", $_data );

	$length = strlen( $_data );

	$begin_pos = true;
	$end_pos = 0;
	$i=0;
	
	// 조회가 되는동안 
	while ( $begin_pos != 0 )
        {
		// ul에 매칭된 str을 가져온다.
		$begin_pos = strpos($_data, "<UL>", $end_pos );
		$end_pos   = strpos($_data, "</UL>", $begin_pos );	
		$key_len = $end_pos - $begin_pos;

		$this_key = substr($_data ,$begin_pos ,$key_len);

		//=======================================
		// 입력
		// 두 번째 조회시 검색 값이 없을 경우 정지
		if ( $i >= 1 and $begin_pos == 0 )
			break;
		else
		{
			echo "[ $i] =======================\n";
			$this->insertData( $title_id, $this_key );
		}

		//===========================================
		// $i가 0이면 무조건 begin_pos = $end_pos임
		// $i가 0보다 크거나 
		if ( $i == 0 or $begin_pos != 0 )
			$begin_pos = $end_pos;

		// 첫 번째 data가 값이 없는 경우
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
		// seq, user, mobile만 저장
                if ( $i == 3 ) break;

                // ul에 매칭된 str을 가져온다.
                $begin_pos = strpos($_data, "<LI>", $end_pos ) + 4;
                $end_pos   = strpos($_data, "</LI>", $begin_pos );
                $key_len = $end_pos - $begin_pos;

                $this_key = substr($_data ,$begin_pos ,$key_len);

		$_info[ $i ] = $this_key;

		// echo "val: $begin_pos / $end_pos / $key_len : $this_key\n";

                // $i가 0보다 크거나 
                if ( $i == 0 or $begin_pos != 0 )
                        $begin_pos = $end_pos;
                $i++;
        }

	//==================================
	//
	// 주문자에게 sms를 보낸다.
	//
	$seq = $_info[0];
	$user = $_info[1];
	$number = $_info[2];

	// seq는 숫자만..
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
  // action 저장
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
  // transaction history출력 
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
  // title 저장
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
  // 배송 목록 저장
  // date: 2007.09.27 - jk
  function save_list()
  {
	global $connect, $send_list, $title_id, $order_name, $shop_name, $mobile;

	$order_name = iconv("UTF-8", "CP949", $order_name);
	$shop_name  = iconv("UTF-8", "CP949", $shop_name);

	$success = "success";

	// 동일한 번호가 있는지 여부 check
	$query = "select * from sms_sendList where title_id='$title_id' and mobile='$mobile'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_row ( $result );

	if ( $data[0] )
		$success = "fail";

	// 동일한 번호가 없는 경우에만 입력 작업 수행
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
  // #mail_history에 검색 결과 출력
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
	// 작업 이력 조회를 위한 table생성
	$this->build_table2( $rows );
  }

  //=============================================
  // date: 2007.9.21 jkryu
  // 배송목록의 transaction관련 정보 전달
  // 과거 전송 이력
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
  // title을 클릭 할 경우..
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

	// list 조회
	$this->build_table(  $rows, $title_id );
  } 


  //=================================================
  // 전체 주문 조회..
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
			case "0":	// 상품명
				$query .= " and b.name like '%$query_string%'";
			break;
			case "1":	// 주문자
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

	// 주문 상태
	if ( $order_status )
		$query .=" and a.status = '$order_status'";

	// cs 상태
	if ( $order_cs )
	{
		switch ( $order_cs )
		{
			case 1: // 정상
				$query .= " and a.order_cs =0";
			break;
			case 2: // 취소
				$query .= " and a.order_cs in (1,2,3,4,12)";
			break;
			case 3: // 교환
				$query .= " and a.order_cs in (5,6,7,8,11,13,9,10)";
			break;
		}
	}

// echo $query;

	// 판매처
	if ( $shop_id )
		$query .= " and a.shop_id='$shop_id'";

	// 공급처
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
  // mail_history의 table생성
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
    // transaction이 있을 경우 transaction을 위한 table생성  
    // $this->get_transaction() 에서 받아옴
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
	// sms 문자의 개수 확인
	$sys_connect = sys_db_connect();
	$query = "select sms from sys_domain where id='" ._DOMAIN_ . "'";	

	$result = mysql_query ( $query, $sys_connect );
	$data = mysql_fetch_array ( $result );
	echo $data[sms];
  }

  //===================================
  // message 전송
  // date: 2007.9.28
  function send_message()
  {
	global $mobile, $message, $sender, $receiver;

	// sms 문자의 개수 확인
	$sys_connect = sys_db_connect();
	$query = "select sms from sys_domain where id='" ._DOMAIN_ . "'";	

	$result = mysql_query ( $query, $sys_connect );
	$data = mysql_fetch_array ( $result );
	$sms_count =  $data[sms];

echo "<ul>";
	if ( $sms_count <= 0 )
	{
		echo "<li id='result'>fail</li><li id='message'>충전이 필요합니다.</li><li id='sms_count'>0</li></ul>";
		exit;
	}
 
	$msg = iconv("UTF-8", "CP949", $message);

	// echo "[$mobile] $msg";

	// xml-rpc를 사용해 전송한다
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
	// 보내는 부분을 잠시 막음
	$_response = $xmlrpc_client->send($_msg);

	// 4 response 체크
	$struct = $_response->value();
	$_dummy = php_xmlrpc_decode( $struct );

	//echo "\n-result ----------------------------\n";
	//echo $_dummy;

	// sms의 개수를 하나 줄여야 함
	$query = "update sys_domain set sms=sms-1 where id='" . _DOMAIN_ . "'";
	mysql_query ( $query, $sys_connect );

	if ( $_dummy == "ok " )
		echo "<li id='result'>success</li><li id='count'>" . --$sms_count . "</li><li id='message'>$sender/$receiver/$msg/$_dummy</li></ul>";
	else
		echo "<li id='result'>fail</li><li id='count'>" . $sms_count . "</li><li id='message'>$_dummy</li></ul>";
 }

  //==================================
  // message 출력
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
  // transaction을 위한 table생성
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

