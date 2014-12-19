<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require_once "ExcelReader/reader.php";

////////////////////////////////
// class name: class_EB00
//
class class_EB00 extends class_top {

  function EB00()
  {
	global $connect;
	global $template, $page;

	if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
	if (!$end_date)   $end_date = date('Y-m-d');

	include "template/E/EB00.htm";
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
	$this->jsAlert ("ȸ�� ���� �Ϸ�" );
	$this->redirect("?template=EB00");	
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


	//=======================================
	//
	// download_type	
	// req_takeback: ��ó�� ȸ�� ��û
	// trans_no: ���� �Է� ȸ�� ��û
	//
	$download_type="req_error";	
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
	$arr_format = array (
                "order_id"         => "������ȣ",
                "shop_product_id"  => "�Ǹ�ó ��ǰ��ȣ",
                "message"          => "message",
                "result_message"   => "result",
        );

	return $arr_format;
  }

  //=========================
  // 
  // file upload
  // date: 2007.2.13 - jk.ryu
  //
  function upload_takeback()
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
	$upload_file = "takeback_".date("YmdHis")."_".$_FILES['excel_file']['name'];

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
	$query = "delete from takeback_temp where data_type=1";
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
	$arr_items = array ( "order_id","shop_product_id","trans_who","message" );

echo "num_rows:$num_rows";

  	for ($i = 1; $i <= $num_rows; $i++)
  	{
//echo "num_rows: $i: ";

		switch( $file_ext )
		{
			case "XLS" :
				$x = 0;
				$data_array = $data->sheets[0]['cells'][$i];
				break;
			case "CSV" :
				$x = 1;
				$data_array = fgetcsv($fp, 1000, ",");
//echo " csv| ";
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
		$order_id   = trim( $data_array[1 - $x] );
		$product_id = trim( $data_array[2 - $x] );

print "<br> order id : $order_id <br> ";

		// ���� ������ ����
		if ( $order_id == '' or $order_id == "�ֹ���ȣ" )
			continue;

		class_takeback::validate( $order_id, $product_id, &$status, &$return_message, &$order_seq );

		// ��� �� ��� ó��
		//if ( $status == 1 )
		//{
			// �ֹ�
		//}
		// ��� �� ȸ�� ó��	
		//else 
		//{
			global $trans_corp;

			//----------------------------------
			// insert ���� ����
			// data_type value: 
			//	1: ȸ�� ��û ����
			//	2: ȸ�� ���� ����
			//
			// ���� ��ȣ�� �Էµ� ���

//echo "trans no: /" . $data_array[6-$x] . "/";
//exit;
			$status = $status ? 1 : 0;
			$query = "insert into takeback_temp set 
					order_id = '" . $data_array[1 - $x]. "',
					shop_product_id = '" . $data_array[2 - $x] . "',
					trans_who= '" . $data_array[3 - $x] . "',
					message = '" . addslashes($data_array[4 - $x]) . "',
					qty = '" . $data_array[5 - $x] . "',
					trans_no = '" . $data_array[6 - $x] . "',
					trans_corp = '$trans_corp',
					status='$status',
					result_message = '$return_message',
					order_seq = '$order_seq',
					data_type = 1";
		//}
// echo "<br>";
// echo $query;
// exit;
		mysql_query ( $query, $connect );

		// echo "<br>---------------------<br>";

  	}
//exit;
	unlink( $excel_file );
	echo "<script language=javascript>
		hide_waiting();
		alert('���ε� �Ϸ�~!!');
	</script>";
	$this->redirect("?template=EB00");	
  }
}

?>
