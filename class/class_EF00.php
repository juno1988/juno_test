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
  // 미처리 게시물 개수
  // status: 1: 미처리, 2: 처리 완료, 3: 전송 완료
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
  // 질문에 대한 답변 등록 cs_result
  //
  // status: 
  //     1: 등록, 2:답변, 3.판매처 등록완료
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
  // div_cs 부분의 내용 출력
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
			$status ='접수';
		elseif ( $data[status] == 2 )
			$status ='답변';
		elseif ( $data[status] == 3 )
			$status ='전송';

		echo "<tr onClick=\"javascript:get_list('$data[item_id]', '$data[status]', this)\" style='cursor:hand;' onmouseover='javakscript:over(this)' onmouseout='javascript:out(this)' >
                <td width=40 height=25 align=center> $status </td>
		<td width=50 align=center>" . class_C::get_shop_name($data[shop_id]) . "</td>
		<td width=40 align=center>$data[cnt]개</td>
		<td>" . $this->order_product_name( $data[item_id] ) . "</td>";
		echo "<td width=35><a href=http://itempage.auction.co.kr/detailview.aspx?itemno=$data[item_id] target=_new>링크</a></td>";
		echo "</tr>";
		$count++;
	}	

echo "</table>";

	//=================================
	// page 출력
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
  // 특정 item의 게시판 내역을 보여준다
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
		echo "<tr><td align=center><span class=red>모든 처리 완료</span></td></tr>";
	}

echo "</table>";
  } 

  
  //======================================
  // 
  // 특정 item의 게시물 상세 내역을 보여준다
  // date: 2007.4.11 -jk.ryu
  //  단순히 get_detail을 호출한다
  //
  function get_detail2()
  {
	$this->get_detail();
  }

  //======================================
  // 
  // 특정 item의 게시물 상세 내역을 보여준다
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
			<td width=100 align=right>주문번호:</td>
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
			<td width=100 align=right>답변 :</td>
			<td width=10>&nbsp;</td>
			<td><textarea id="cs_result" cols=50 rows=5><?= $this->disp_result( ) ?></textarea></td>
		</tr>
		<tr>
			<td align=center height=20 colspan=3><a href=javascript:write('<?= $cs_id ?>') class=btn1> 등록 </a></td>
		</tr>
	</table>
<?
  } 

  //======================================
  //
  // 상세 결과 출력
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
  // 송장 접수
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

		class_takeback::transno_validate( $order_seq, &$status, &$return_message );

		//----------------------------------
		// insert 쿼리 생성
		// data_type value: 
		//	1: 회수 요청 파일
		//	2: 회수 송장 파일
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
	$this->jsAlert( "등록완료");
	$this->redirect ( "?template=EF00" );
  }
}

?>
