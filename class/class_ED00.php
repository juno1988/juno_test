<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require_once "ExcelReader/reader.php";

////////////////////////////////
// class name: class_ED00
//
class class_ED00 extends class_top {

  function ED00()
  {
	global $connect;
	global $template, $page;

	include "template/E/ED00.htm";
  }
  
  //-----------------------------------------
  //
  // 송장 접수
  //   date: 2007-02-15
  //
  function apply_transno()
  {
	global $connect;

	class_takeback::apply_transno();
	$this->redirect( "?template=ED00");
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
	// db에 자료 입력
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
		// 상태 확인
  		// status: 0: 오류, 1: 배송전 취소처리, 2: 회수 처리
		//
		global $trans_corp;
		$order_seq = $data_array[1 - $x];
		$trans_no  = $data_array[2 - $x];

		// 값이 없으면 정지
		 if ( $order_seq == '' or $order_seq == "관리번호" )
			continue;

		// transno_validate의 status가 의미 하는 것
  		// status: 0: 회수 미요청, 1: 회수 요청 정상
		class_takeback::transno_validate( $order_seq, &$status, &$return_message );

		//----------------------------------
		// insert 쿼리 생성
		// data_type value: 
		//	1: 회수 요청 파일
		//	2: 회수 송장 파일
		// status가 1인 건들만 등록을 한다고 함...이상함
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
	$this->jsAlert( "등록완료");
	$this->redirect ( "?template=ED00" );
  }
}

?>
