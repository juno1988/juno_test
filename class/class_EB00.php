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
  // 회수 접수
  //   date: 2007-02-14
  //
  function apply_takeback()
  {
	global $connect;

	class_takeback::apply_takeback();
	$this->jsAlert ("회수 접수 완료" );
	$this->redirect("?template=EB00");	
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


	//=======================================
	//
	// download_type	
	// req_takeback: 미처리 회수 요청
	// trans_no: 송장 입력 회수 요청
	//
	$download_type="req_error";	
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
	$arr_format = array (
                "order_id"         => "관리번호",
                "shop_product_id"  => "판매처 상품번호",
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
	      fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
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
	    $alert_msg = "지원하지 않은 엑셀(Excel)포맷입니다.[HTML형식]\\n\\n처리방법 : 파일을 열고 다른이름으로 저장을 선택
	하여\\n엑셀형식(XLS)으로 저장한 후 다시 업로드 하시기 바랍니다";
	    echo "<script>alert('$alert_msg');history.back();</script>";
	    exit;
	}
	else if ($file_ext == "")
	{
	    $alert_msg = "알수없는 엑셀(Excel)포맷입니다.\\n\\n 처리방법 : 이지어드민 고객센터로 문의하시면 즉시 조치해 드리
	黴윱求? Tel: (02)521-1774/5";
	    echo "<script>alert('$alert_msg');history.back();</script>";
	    exit;
	}

	# temp talbe 초기화
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
	// db에 자료 입력
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
		// 상태 확인
  		// status: 0: 오류, 1: 배송전 취소처리, 2: 회수 처리
		//
		$order_id   = trim( $data_array[1 - $x] );
		$product_id = trim( $data_array[2 - $x] );

print "<br> order id : $order_id <br> ";

		// 값이 없으면 정지
		if ( $order_id == '' or $order_id == "주문번호" )
			continue;

		class_takeback::validate( $order_id, $product_id, &$status, &$return_message, &$order_seq );

		// 배송 전 취소 처리
		//if ( $status == 1 )
		//{
			// 주문
		//}
		// 배송 후 회수 처리	
		//else 
		//{
			global $trans_corp;

			//----------------------------------
			// insert 쿼리 생성
			// data_type value: 
			//	1: 회수 요청 파일
			//	2: 회수 송장 파일
			//
			// 송장 번호가 입력된 경우

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
		alert('업로드 완료~!!');
	</script>";
	$this->redirect("?template=EB00");	
  }
}

?>
