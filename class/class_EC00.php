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
  // ȸ�� ���� ��ȣ ��ȸ
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
	// trans_no == prev_trans_no �� ���� ��� ȸ�� ��ǰ�� ���������� ȸ�� ���� �ǹ�
	$i = 0;
	while ( $data = mysql_fetch_array ( $result ))
	{

		// ���� ��ȣ�� �� �� scan�� ��� �Ϸ�
		// �ѹ��� scan�ص� ȸ�� Ȯ�� �� ���� clip �̼��� ���� ��û ����
		// date: 2007.3.22 - jk
		//if ( $trans_no == $prev_trans_no )
		//{
		if ( $data[takeback_status] == 2 )
		{
			echo "<br><span class=red>====================�̹� Ȯ�ε� ȸ�� ��û �Դϴ�.=================</span><br>";
		}
		else
		{
			class_takeback::complete_item( $data[seq] );
			echo "<br><span class=red>===================== ���� Ȯ�� ======================</span><br>";
			//$this->re_disp_items ( $data[seq] );
		}

		//else
			$this->disp_items( $data );

		$i++;
	}

	// ��ȸ�� data�� ���� ���
	if ( $i == 0 )
	{
		echo "<span class=red>��ȸ�� ����� �����ϴ�</span>";
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
	<td width=100 align=center>������</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= $data[recv_name] ?> / <?= $data[recv_mobile] ?> <br> &nbsp;[<?= $data[recv_zip]?>]<?= $data[recv_address] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>�ֹ�</td>
        <td height=25 width=400 bgcolor=ffffff>&nbsp;������ȣ: <?= $data[seq] ?> / �ֹ���ȣ: <?= $data[order_id] ?> / �Ǹ�ó: <?= class_C::get_shop_name($data[shop_id]) ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>�ֹ� ����</td>
        <td height=25 bgcolor=ffffff>&nbsp; <?= $this->get_order_status($data[status],1) ?>/ <?= $this->get_order_cs( $data[order_cs],1 ) ?> </td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>�ù�</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= class_E::get_trans_name( $data[takeback_transcorp] ) ?> - <?= $data[takeback_transno] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>ȸ����û��</td>
        <td height=25 bgcolor=ffffff>&nbsp;(ȸ�� ��û��)<?= $data[takeback_regdate] ?>  / (ȸ������ �����)<?= $data[takeback_transdate] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>ȸ�� ����</td>
        <td height=25 bgcolor=ffffff>&nbsp;<?= $data[takeback_status] ?>(ȸ���Ϸ���: <?= $data[complete_date] ?> ) - <?= $data[who_complete] ?> </td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>��ǰ</td>
        <td height=25 bgcolor=ffffff>&nbsp;( <span class=red><?= $data[takeback_qty] ?></span>��) ��ǰ��: <?= class_D::get_product_name( $data[product_id] )?> <br>&nbsp;&nbsp;&nbsp; - �ɼ�: <?= $data[options] ?></td>
    </tr>
    <tr bgcolor=f8f8f8>
	<td width=100 align=center>CS����</td>
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
        <td align=right> [Ȯ��] </td>
    </tr>
</table>
	 

<?
  }

  // download
  function download2()
  {
	global $connect, $saveTarget, $download_type;

	// download format�� ���� ������ �����´�
	$result = $this->get_format();
	$download_items = array (); 

	foreach ( $result as $key=>$name )
	{
		$download_items[$key] = $name;
	}

	// file open
	$handle = fopen ($saveTarget, "w");

	$result = class_takeback::get_list( $download_type ); 
	
	// header ��� �κ�
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

	// footer ���
	fwrite($handle, "</table>");

	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=ȸ��_" . $start_date . "_" . $end_date . ".xls");
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
	// �ù��, 
	
	return $arr_format;
  }
}

?>
