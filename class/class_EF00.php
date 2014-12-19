<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require "ExcelReader/reader.php";

////////////////////////////////
// class name: class_EF00
//
class class_EF00 extends class_top {

  function EF00()
  {
	global $connect;
	global $template, $page;

	if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
	if (!$end_date)   $end_date = date('Y-m-d');



	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //==============================================
  //
  // ��ó�� �Խù� ����
  // status: 1: ��ó��, 2: ó�� �Ϸ�, 3: ���� �Ϸ�
  // date: 2007.4.26 - jk.
  function get_count( $option = "" )
  {
	global $connect;
	$query = "select count(*) cnt from cs_index where status=1";

	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	if ( $option == "secret" )
		return $data[cnt];
	else
		echo $data[cnt];
  }
  //===========================================
  // 
  // ������ ���� �亯 ��� cs_result
  //
  // status: 
  //     1: ���, 2:�亯, 3.�Ǹ�ó ��ϿϷ�
  function write()
  {
	global $connect, $cs_result, $cs_id;

	$cs_result = iconv("UTF-8", "CP949", $cs_result);
	$cs_title = $this->cutstr( $cs_result, 30 );

	$query = "update cs_index 
			set status=2, 
			cs_title='" . addslashes($cs_title) . "', 
			cs_result='" . addslashes($cs_result) . "'
		where cs_id='$cs_id'";

// echo $query;

	mysql_query ( $query, $connect );
  }

  //==========================================
  //
  // div_cs �κ��� ���� ���
  // date: 2007.4.10 - jk.ryu
  //
  function search()
  {
	global $connect, $status, $page;

	if ( !$page )
		$page = 1;

	$limit = 20;
	$start = ( $page - 1 ) * $limit;

	$query = "select status, shop_id, count(*) cnt, item_id, cs_id from cs_index ";

	if ( $status )
		$query .= " where status=$status ";

	$query .= " group by item_id, status";
	$query .= " limit $start, $limit";

	$result = mysql_query ( $query, $connect );

echo "<table border=0 cellpadding=0 cellspacing=0 width=100%>";

	$count = 0;
	while ( $data = mysql_fetch_array ( $result ) ) 
	{
		if ( $data[status] == 1 )
			$status ='����';
		elseif ( $data[status] == 2 )
			$status ='�亯';
		elseif ( $data[status] == 3 )
			$status ='����';

		echo "<tr onClick=\"javascript:get_list('$data[item_id]', '$data[status]', this)\" style='cursor:hand;' onmouseover='javakscript:over(this)' onmouseout='javascript:out(this)' >
                <td width=40 height=25 align=center> $status </td>
		<td width=50 align=center>" . class_C::get_shop_name($data[shop_id]) . "</td>
		<td width=40 align=center>$data[cnt]��</td>
		<td>" . $this->order_product_name( $data[item_id] ) . "</td>";
		echo "<td width=35><a href=http://itempage.auction.co.kr/detailview.aspx?itemno=$data[item_id] target=_new>��ũ</a></td>";
		echo "</tr>";
		$count++;
	}	

echo "</table>";

	//=================================
	// page ���
	//=================================

	$line_per_page = 20;
	$total_rows = $this->get_count( "secret" );
	$link_url = "javascript:search";
	include "./template/inc/page_count2.inc";
  }

  function order_product_name( $product_id )
  {
	global $connect;

	$query1 = "select id from code_match where shop_code='$product_id'";
	$result = mysql_query( $query1, $connect );
	$data = mysql_fetch_array ( $result ); 

	$query = "select product_name from orders where product_id='$data[id]'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[product_name] ? $data[product_name] : $product_id;
  }

  //======================================
  // 
  // Ư�� item�� �Խ��� ������ �����ش�
  // date: 2007.4.10 -jk
  //
  function get_list()
  {
	global $connect, $item_id, $status;

	$query = "select * from cs_index a, cs_param b
			 where a.cs_id = b.cs_id 
		           and a.item_id='$item_id'
		           and a.status ='$status'
			   and b.idx = 'T'";

	$result = mysql_query ( $query, $connect );
	$count = 0;

echo "<table border=0 cellpadding=0 cellspacing=0 width=100%>";
        while ( $data = mysql_fetch_array ( $result ) )
        {
                echo "<tr onClick=\"javascript:get_detail('$data[cs_id]')\" style='cursor:hand;' onmouseover=this.className=\"yellowThing\"; onmouseout=this.className='whiteThing' >
		<td width=150 height=25 align=center>$data[crdate]</td>
		<td width=100 align=center>$data[order_id]</td>
		<td>$data[value]</td>
		</tr>";
		$count++;        
        }

	if ( !$count )
	{
		echo "<tr><td align=center><span class=red>��� ó�� �Ϸ�</span></td></tr>";
	}

echo "</table>";
  } 

  
  //======================================
  // 
  // Ư�� item�� �Խù� �� ������ �����ش�
  // date: 2007.4.11 -jk.ryu
  //  �ܼ��� get_detail�� ȣ���Ѵ�
  //
  function get_detail2()
  {
	$this->get_detail();
  }

  //======================================
  // 
  // Ư�� item�� �Խù� �� ������ �����ش�
  // date: 2007.4.10 -jk
  //
  function get_detail()
  {
	global $connect, $cs_id;

	$query = "select * from cs_param where cs_id='$cs_id' and idx in ('T','W','C')";
	$result = mysql_query ( $query, $connect );

	while ( $data = mysql_fetch_array ( $result ))
	{
		switch ( $data[idx] )
		{
			case "T": $title   = $data[value]; break;
			case "W": $writer  = $data[value]; break;
			case "C": $content = $data[value]; break;

		}
		$cs_id = $data[cs_id];
	}

	$query = "select * from cs_index where cs_id='$cs_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

		
	$cs_result = $data[cs_result];
?>
	<table border=0 width=100% cellpadding=0 cellspacing=1>
		<tr>
			<td colspan=3 height=1 bgcolor=cccccc></td>
		</tr>
		<tr bgcolor=ffffff>
			<td width=100 align=right>�ֹ���ȣ:</td>
			<td width=10>&nbsp;</td>
			<td width=100> <?= $data[order_id] ?></td>
		</tr>
		<tr>
			<td colspan=3 height=1 bgcolor=cccccc></td>
		</tr>
		<tr>
			<td width=100 align=right>Title:</td>
			<td width=10>&nbsp;</td>
			<td><?= $title?> ( <?= $writer ?> )</td>
		</tr>
		<tr>
			<td colspan=3 height=1 bgcolor=cccccc></td>
		</tr>
		<tr bgcolor=ffffff>
			<td width=100 align=right>Content:</td>
			<td width=10>&nbsp;</td>
			<td><?= $content ?></td>
		</tr>
		<tr bgcolor=ffffff>
			<td width=100 align=right>�亯 :</td>
			<td width=10>&nbsp;</td>
			<td><textarea id="cs_result" cols=50 rows=5><?= $this->disp_result( ) ?></textarea></td>
		</tr>
		<tr>
			<td align=center height=20 colspan=3><a href=javascript:write('<?= $cs_id ?>') class=btn1> ��� </a></td>
		</tr>
	</table>
<?
  } 

  //======================================
  //
  // �� ��� ���
  // date: 2007.4.11 - jk
  //
  function disp_result(  )
  {
	global $connect, $cs_id;
	$query = "select cs_result from cs_index where cs_id='$cs_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[cs_result];
  }

  //-----------------------------------------
  //
  // ���� ����
  //   date: 2007-02-15
  //
  function apply_transno()
  {
	global $connect;

	class_takeback::apply_transno();
	$this->redirect( "?template=EF00");
  }

  //-----------------------------------------
  //
  // ȸ�� ����
  //   date: 2007-02-14
  //
  function apply_takeback()
  {
	global $connect;

	class_takeback::apply_takeback();
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
	$buffer .= "<html><table border=0 cellpadding=0 cellspacing=0><tr>";
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

  //=========================
  // 
  // file upload
  // date: 2007.2.13 - jk.ryu
  //
  function upload_transno()
  {
	$shop_id = $_POST["shop_id"];
	$excel_file = $_FILES['excel_file'];

	if ($excel_file)
	{
	    $file_params = pathinfo($_FILES['excel_file']['name']);
	    $file_ext = strtoupper($file_params["extension"]);
	    if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT") {
	      fatal("�߸��� ���������Դϴ�. ���������� ���������� (.xls | .csv | .txt)�Դϴ�.");
	}

	$upload_dir = _upload_dir;
	$upload_file = "C".date("YmdHis")."_".$_FILES['excel_file']['name'];

	if (!move_uploaded_file($_FILES['excel_file']['tmp_name'], $upload_dir.$upload_file))
	{
		fatal("file upload failed");
	}
	$excel_file = $upload_dir.$upload_file;
	}

	if ($excel_file == '') fatal("No file uploaded");
	if ($file_ext == "XLS")
	    $file_ext = $this->get_file_ext($excel_file);

	///////////////////////////////////////////
	if ($file_ext == "HTML")
	{
	    $alert_msg = "�������� ���� ����(Excel)�����Դϴ�.[HTML����]\\n\\nó����� : ������ ���� �ٸ��̸����� ������ ����
	�Ͽ�\\n��������(XLS)���� ������ �� �ٽ� ���ε� �Ͻñ� �ٶ��ϴ�";
	    echo "<script>alert('$alert_msg');history.back();</script>";
	    exit;
	}
	else if ($file_ext == "")
	{
	    $alert_msg = "�˼����� ����(Excel)�����Դϴ�.\\n\\n ó����� : �������� �����ͷ� �����Ͻø� ��� ��ġ�� �帮
	ڽ��ϴ? Tel: (02)521-1774/5";
	    echo "<script>alert('$alert_msg');history.back();</script>";
	    exit;
	}

	# temp talbe �ʱ�ȭ
	global $connect;
	$query = "delete from takeback_temp where data_type=2";
        @mysql_query ( $query, $connect );

	# data insert
	switch ($file_ext)
	{
	    case "XLS" :
		$data = new Spreadsheet_Excel_Reader();
		$data->setOutputEncoding('CP949');
		$data->read($excel_file);
		$num_rows = $data->sheets[0]['numRows'];
		break;

	    case "CSV" :
		$data = file($excel_file);
		$num_rows = count($data) + 1;
		$fp = fopen($excel_file, "r");
		break;
	    case "TXT" :
		$data = file($excel_file);
		$num_rows = count($data);
		break;
	}
 
 
	$rows = 0;
	// db�� �ڷ� �Է�
  	for ($i = 1; $i <= $num_rows; $i++)
  	{
		switch( $file_ext )
		{
			case "XLS" :
				$x = 0;
				$data_array = $data->sheets[0]['cells'][$i];
				break;
			case "CSV" :
				$x = 1;
				$data_array = fgetcsv($fp, 1000, ",");
				break;
			case "TXT" :
				$x = 1;
				$data_array = explode("\t", $rec);
				break;
		}

		//----------------------------------
		//
		// ���� Ȯ��
  		// status: 0: ����, 1: ����� ���ó��, 2: ȸ�� ó��
		//
		global $trans_corp;
		$order_seq = $data_array[1 - $x];
		$trans_no  = $data_array[2 - $x];

		// ���� ������ ����
		 if ( $order_seq == '' or $order_seq == "������ȣ" )
			continue;

		class_takeback::transno_validate( $order_seq, &$status, &$return_message );

		//----------------------------------
		// insert ���� ����
		// data_type value: 
		//	1: ȸ�� ��û ����
		//	2: ȸ�� ���� ����
		//
		$query = "insert into takeback_temp set 
				order_id = '$order_seq',
				order_seq = '$order_seq',
				trans_corp = '$trans_corp',
				trans_no = '$trans_no',
				data_type = 2,
				status = $status,
				result_message = '$return_message'";
		mysql_query ( $query, $connect );

		//echo "<br>---------------------<br>";
  	}
	$this->jsAlert( "��ϿϷ�");
	$this->redirect ( "?template=EF00" );
  }
}

?>
