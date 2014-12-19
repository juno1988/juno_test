<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";

////////////////////////////////
// class name: class_EA00
//
class class_EA00 extends class_top {

  function EA00()
  {
	global $connect;
	global $template, $page;
	global $start_date, $end_date;

	if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
	if (!$end_date)   $end_date = date('Y-m-d');

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //=====================================
  //
  // download
  // download_type
  //	req_takeback: 회수 요청
  //	trans_no: 송장 입력 회수 요청
  //	comp_takeback : 회수 완료 , 2007.3.15 추가 됨
  //      => download format이 달라야 함 
  function download2()
  {
	global $connect, $saveTarget, $download_type, $start_date, $end_date;

	global $trans_corp, $trans_format;

//echo $download_type;

	// download format에 대한 정보를 가져온다
        if ( $download_type == "req_takeback" )
	{
		$download_items = $this->get_format2( $download_type );
	}
	else	
		$download_items = $this->get_format( $download_type );

	/*
	$download_items = array (); 
	foreach ( $result as $key=>$name )
	{
		$download_items[$key] = $name;
	}
	*/

	// file open
	$handle = fopen ($saveTarget, "w");
	$result = class_takeback::get_list( $download_type ); 
	
	// header 출력 부분
	$buffer .= "<html><table border=1><tr>";
	foreach ( $download_items as $key=>$value )
	    $buffer .= "<td>" . $value. "</td>";

//echo "buf->" . $buffer;
//exit;

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
	switch ( $key )
	{	
		case "shop_name":
			return class_C::get_shop_name( $data["shop_id"] );
			break;
		case "cs_history":
			return $this->cs_history( $data[seq] );	
			break;
		default : 
			return $data[$key];
	}
  }	

  function cs_history( $seq )
  {
	// cs list
         global $connect;
         $query = "select * from csinfo where order_seq='$seq'";

         $result_cs = mysql_query ( $query, $connect );
	 $string = "";
         while ( $data = mysql_fetch_array ( $result_cs ) )
         {
		$string .= "Date: $data[input_date] $data[input_time]\r\n " . strip_tags( $data[content] ) . "\r\n";
         }
	return $string;
  }

  //=============================================
  //
  // trans_format table로부터 data가져옴
  // date: 2007.2.27 - jk.ryu
  //
  function get_format2()
  {
	global $connect, $trans_corp, $trans_format;
	
	// format 정보 가져온다
	// trans_format의 값은 unique함
	$query  = "select * from trans_format 
                    where format_id='$trans_format' and trans_id='$trans_corp' order by seq";

	$result = mysql_query ( $query, $connect );

echo "<br>";
echo $query;
echo "<br>";

	$arr_format = array();
	$i = 0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
		// echo htmlspecialchars($data[macro_value]);
		$this->convert_macrotokey ( $data[macro_value], &$key, &$value );
		// echo " : key->$key / $value <br>";

		//$arr_format[$key] =  "test";	
		$arr_format[$key] =  $value;	
		$i++;
	}

	return $arr_format;
  }

  //=============================================
  //
  // macro 값을 $key와 $value로 변경
  //
  function convert_macrotokey( $macro , &$key, &$value )
  {
	switch ( $macro )
	{
		case "<order_id>";
			$key = "order_id";
			$value = "주문번호";
		break;
		default :  // < > 사이의 값이 나옴
			if ( preg_match( "/[\<](.*)[\>]/", $macro, $matches ) )
			{
				$key   = $matches[1];
				$value = $matches[1];
			}
			else
				echo "입력값 오류";
	}
  }

  //============================================
  //
  // 회수 완료 포맷 저장
  // date: 2007.3.15 -jk.ryu
  //
  function get_format( $download_type )
  {
	# $lib_name = "lib/ez_trans_lib_" . _DOMAIN_ . ".php";

	$arr_format = array ( 
		"seq" 		=> "관리번호",
		"tb_trans_who"	=> "선착불",
		"tb_trans_corp"	=> "택배사",
		"tb_trans_no"	=> "송장번호",
		"order_id"	=> "주문번호",
		"shop_name"	=> "판매처",
		"product_name"	=> "상품명",
		"options"	=> "옵션",
		"order_name"	=> "고객명",
		"recv_name"	=> "수령자",
		"recv_zip"	=> "수령지 우편번호",
		"recv_address"	=> "수령지",
		"recv_tel"	=> "수령전화",
		"recv_mobile"	=> "수령핸드폰",
		"shop_price"	=> "판매금액",
		"supply_price"	=> "정산금액",	
		"cs_history"	=> "CS 정보",
	);
	return $arr_format;
  }

}

?>
