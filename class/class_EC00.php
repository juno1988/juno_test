<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require_once "class_cs.php";

////////////////////////////////
// class name: class_EC00
//
class class_EC00 extends class_top {

  function EC00()
  {
	global $connect;
	global $template, $page;

	if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
	if (!$end_date)   $end_date = date('Y-m-d');

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //==========================================
  //
  // 회수 송장 번호 조회
  // 
  function search()
  {
	global $connect, $trans_no, $prev_trans_no;


	$query = "select a.order_seq, b.*, a.trans_no takeback_transno, a.trans_corp takeback_transcorp ,
                         a.reg_date takeback_regdate, a.trans_date takeback_transdate, a.qty takeback_qty, a.status takeback_status,
			 a.complete_date, a.who_complete
                    from order_takeback a ,
                         orders b
                   where a.order_seq = b.seq
	             and a.trans_no='$trans_no'";

// echo $query;

	//if ( $prev_trans_no != $trans_no )
	//	$prev_trans_no = $trans_no;

	$result = mysql_query ( $query, $connect );

	//==============================================
	// date: 2007.2.28 - jk.ryu
	// trans_no == prev_trans_no 일 경우는 모든 회수 상품이 정상적으로 회수 됨을 의미
	$i = 0;
	while ( $data = mysql_fetch_array ( $result ))
	{

		// 같은 번호를 두 번 scan할 경우 완료
		// 한번만 scan해도 회수 확인 됨 변경 clip 이선희 과장 요청 사항
		// date: 2007.3.22 - jk
		//if ( $trans_no == $prev_trans_no )
		//{
		if ( $data[takeback_status] == 2 )
		{
			echo "<br><span class=red>====================이미 확인된 회수 요청 입니다.=================</span><br>";
		}
		else
		{
			class_takeback::complete_item( $data[seq] );
			echo "<br><span class=red>===================== 정상 확인 ======================</span><br>";
			//$this->re_disp_items ( $data[seq] );
		}

		//else
			$this->disp_items( $data );

		$i++;
	}

	// 조회된 data가 없는 경우
	if ( $i == 0 )
	{
		echo "<span class=red>조회된 결과가 없습니다</span>";
	}
  }

  function re_disp_items ( $order_seq )
  {
	global $connect;

	$query = "select a.order_seq, b.*, a.trans_no takeback_transno, a.trans_corp takeback_transcorp ,
                         a.reg_date takeback_regdate, a.trans_date takeback_transdate, a.qty takeback_qty, a.status takeback_status,
			 a.complete_date, a.who_complete
                    from order_takeback a ,
                         orders b
                   where a.order_seq = b.seq
	             and a.order_seq='$order_seq'";
	
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	$this->disp_items( $data );	

  }

  function disp_items ( $data )
  {
?>

<br>
<table width=600 border=0 cellpadding=0 cellspacing=1 bgcolor=cccccc>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>고객정보</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= $data[recv_name] ?> / <?= $data[recv_mobile] ?> <br> &nbsp;[<?= $data[recv_zip]?>]<?= $data[recv_address] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>주문</td>
        <td height=25 width=400 bgcolor=ffffff>&nbsp;관리번호: <?= $data[seq] ?> / 주문번호: <?= $data[order_id] ?> / 판매처: <?= class_C::get_shop_name($data[shop_id]) ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>주문 상태</td>
        <td height=25 bgcolor=ffffff>&nbsp; <?= $this->get_order_status($data[status],1) ?>/ <?= $this->get_order_cs( $data[order_cs],1 ) ?> </td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>택배</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= class_E::get_trans_name( $data[takeback_transcorp] ) ?> - <?= $data[takeback_transno] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>회수요청일</td>
        <td height=25 bgcolor=ffffff>&nbsp;(회수 요청일)<?= $data[takeback_regdate] ?>  / (회수송장 등록일)<?= $data[takeback_transdate] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>회수 상태</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= $data[takeback_status] ?>(회수완료일: <?= $data[complete_date] ?> ) - <?= $data[who_complete] ?> </td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>상품</td>
        <td height=25 bgcolor=ffffff>&nbsp;( <span class=red><?= $data[takeback_qty] ?></span>개) 상품명: <?= class_D::get_product_name( $data[product_id] )?> <br>&nbsp;&nbsp;&nbsp; - 옵션: <?= $data[options] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>CS정보</td>
        <td height=25 bgcolor=ffffff>
            <table width=98% align=center>
                <tr>
                    <td> <?= class_cs::disp_cshistory( $data[seq] ) ?> </td>
                </tr>
            </table>
        </td>
    </tr>

</table>
<table width=600>
    <tr>
        <td align=right> [확인] </td>
    </tr>
</table>
	 

<?
  }

  // download
  function download2()
  {
	global $connect, $saveTarget, $download_type;

	// download format에 대한 정보를 가져온다
	$result = $this->get_format();
	$download_items = array (); 

	foreach ( $result as $key=>$name )
	{
		$download_items[$key] = $name;
	}

	// file open
	$handle = fopen ($saveTarget, "w");

	$result = class_takeback::get_list( $download_type ); 
	
	// header 출력 부분
	$buffer .= "<html><table border=1><tr>";
	foreach ( $download_items as $key=>$value )
	    $buffer .= "<td>" . $value. "</td>";

	$buffer .= "</tr>\n";
	fwrite($handle, $buffer);

	while ( $data = mysql_fetch_array ( $result ) )
	{	
		$buffer = "<tr>\n";
		foreach ( $download_items as $key=>$value )
		{
		    $buffer .= "<td>";
		    $buffer .= $this->get_data( $data, $key );
		    $buffer .= "</td>";
		}

		$buffer .= "</tr>\n";
		fwrite($handle, $buffer);
		$buffer = "";
	}

	// footer 기록
	fwrite($handle, "</table>");

	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=회수_" . $start_date . "_" . $end_date . ".xls");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");

      if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
      } 

      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);
      unlink($saveTarget);
  }

  function get_data( $data, $key )
  {
	return $data[$key];
  }	

  function get_format()
  {
	// 택배사, 
	
	return $arr_format;
  }
}

?>
