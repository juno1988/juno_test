<?
require_once "class_top.php";
require_once "class_A.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_A700
//

class class_A700 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function A700()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function download()
    {
	$act = $_REQUEST[act];
	if ($act == "OLD")
	{
	    $this->make_excel("orders_old", "과거주문정보");
	}
	else if ($act == "NEW")
	{
	    $this->make_excel("orders", "최근주문정보");
	}
    }

    function make_excel($table_name, $xls_title)
    {
	global $connect;
	global $template;

	$today = date("Y-m-d");
	require_once 'Spreadsheet/Excel/Writer.php';
	$xls_name = $today."-".$xls_title.".xls";

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('주문정보');

	$worksheet->setColumn(1, 2, 15);	# 발주일자,주문번호
	$worksheet->setColumn(3, 3, 30);	# 상품명
	$worksheet->setColumn(10, 10, 15);	# 전화번호
	$worksheet->setColumn(11, 11, 15);	# 휴대폰번호
	$worksheet->setColumn(13, 13, 50);	# 주소
	$worksheet->setColumn(14, 14, 20);	# 메모
	$format_bold =& $workbook->addFormat();
	$format_bold->setAlign('center');
	$format_bold->setBold();

	////////////////////////////////////////////////////////////////
	$header_items = array ("발주일자", "주문번호", "상품코드", "상품명", "옵션명", "판매처", "가격", "수량", "주문자", "수령자", "전화", "휴대폰", "우편번호", "주소", "메모", "선착불");
	$col = 0;
	foreach ($header_items as $item)
	{
	    $worksheet->write(0, $col, $item, $format_bold);
	    $col++;
	}

	////////////////////////////////////////////////////////////////
        $row = 1;
	$sql = "select * from ${table_name} where collect_date >= '2007-01-01' and collect_date <= '2007-04-31' order by collect_date, seq";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
	  //$sql = "select * from csinfo where order_seq = '$list[seq]'";
	  //$result2 = mysql_query($sql, $connect) or die(mysql_error());
	  //$csinfo = "";
	  //while ($list2 = mysql_fetch_array($result2))
	  //{
	   // $csinfo .= "($list2[input_date]) $list2[content]\n";
	  //}

	  $worksheet->write($row, 0, $list[collect_date]);
	  $worksheet->write($row, 1, $list[order_id]);
	  $worksheet->writeString($row, 2, $list[product_id]);
	  $worksheet->write($row, 3, $list[product_name]);
	  $worksheet->write($row, 4, $list[options]);
	  $worksheet->write($row, 5, class_C::get_shop_name( $list[shop_id]));
	  $worksheet->write($row, 6, $list[shop_price]);
	  $worksheet->write($row, 7, $list[qty]);
	  $worksheet->write($row, 8, $list[order_name]);
	  $worksheet->write($row, 9, $list[recv_name]);
	  $worksheet->write($row, 10, $list[recv_tel]);
	  $worksheet->write($row, 11, $list[recv_mobile]);
	  $worksheet->write($row, 12, $list[recv_zip]);
	  $worksheet->write($row, 13, $list[recv_address]);
	  $worksheet->write($row, 14, $list[memo]);
	  $worksheet->write($row, 15, $list[trans_who]);
	  // $worksheet->write($row, 16, $csinfo);
	  $row++;
	}

	////////////////////////////////////
	// Let's send the file
	// sending HTTP headers
	$workbook->send($xls_name);
	$workbook->close();
    }
}

?>
