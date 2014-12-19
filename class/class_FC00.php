<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_FC00
//

class class_FC00 extends class_top {

    ///////////////////////////////////////////

    function FC00()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function FC01()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function download()
    {
	global $connect;
	global $template;

	$start_date = $_REQUEST[start_date];
	$end_date = $_REQUEST[end_date];
	$type = $_REQUEST[type];
	$query_date = $_REQUEST[query_date];
	$shop_id = $_REQUEST[shop_id];

	$today = date("Y-m-d");
	require_once 'Spreadsheet/Excel/Writer.php';

	$xls_name = $today."-Settlement.xls";

	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('�����ڷ�');

	$worksheet->setColumn(3, 3, 15);
	$worksheet->setColumn(5, 5, 30);
	$worksheet->setColumn(6, 6, 20);
	$format_bold =& $workbook->addFormat();
	$format_bold->setAlign('center');
	$format_bold->setBold();

	$format_bg =& $workbook->addFormat();
	$format_bg->setFgColor('yellow');
	$format_bg->setBgColor('black');
        $format_bg->setPattern(1);

	////////////////////////////////////////////////////////////////
	$header_items = array ("�Ǹ�ó", "��������", "�������", "�ֹ���ȣ", "SUBNO", "��ǰ��", "�ɼ�", "����", "����", "�ֹ���", "������", "�Ǹž�", "������", "�ù��", "����ݾ�", "����Ȯ����",  "�ֹ�����", "CS����",  "�������", "��ҿ���", "�������", "����������");


	////////////////////////////////////////
	// js�� ���ް���,������,�������� ������ (2007.12.25 syhwang)
	if (_DOMAIN_ == "js" or _DOMAIN_ == "czone")
	{
	    array_push($header_items, "���ް���", "������", "������","��ǰ�ڵ�");
	}


	$col = 0;
	foreach ($header_items as $item)
	{
	    $worksheet->write(0, $col, $item, $format_bold);
	    $col++;
	}

	/////////////////////////////////////////////////////////        
	$query_option = "";
	if ($shop_id) $query_option = " and shop_id = '$shop_id'";

	if ($query_date == 1)       // ������ ����
	{
	    $query_option .= " and collect_date >= '$start_date' 
			  and collect_date <= '$end_date'";
	} 
	else if ($query_date == 2)  // �����Է��� ����
	{
	    $query_option .= " and substring(trans_date,1,10) >= '$start_date' 
			  and substring(trans_date,1,10) <= '$end_date'";

	}
	else if ($query_date == 3) // POS ����� ����
	{
	    $query_option .= " and status = 8
			  and substring(trans_date_pos,1,10) >= '$start_date' 
			  and substring(trans_date_pos,1,10) <= '$end_date'";
	}

	if ($type > 0) $query_option .= "and settle_ok = '$type'";
	else if ($type == 0) 
	    $query_option .= "and settle_ok = 0 and substring(order_id,1,1) != 'C' and order_cs not in (1,2,3,4,12)";	// ����� �������
  	else if ($type == -1) 
	    $query_option .= "and settle_cancel = 1 and settle_ok > 0";
	////////////////////////////////////////////////////////////////
        $row = 1;
	$sql = "select * from orders 
		 where shop_id != ''
		       $query_option
		 order by collect_date";

	$result = mysql_query($sql, $connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
 	  if ($list[settle_pay]) $settle_pay = $list[settle_pay];
	  else $settle_pay = $list[qty] * $list[shop_price];
	  if ($list[settle_ok] == 0) $settle_ok = '������';
	  else if ($list[settle_ok] == 1) $settle_ok = '����Ȯ�����';
	  else if ($list[settle_ok] == 2) $settle_ok = '����Ȯ��';

	  if ($list[order_cs] != 0) $format = $format_bg;
	  else $format = "";

	  if ($list[settle_cancel]) 
	  {
	      	$settle_cancel = "ȯ��";
		$settle_date = $list[settle_date];
		if ($list[settle_ok] != 2) $settle_date = "";
	  }
	  else 
	  {
		$settle_cancel = "";
		$settle_date = "";
	  }

	  if ($list[settle_ok] == 2) $last_settle_date = $list[settle_date];
	  else $last_settle_date = "";


	  $worksheet->write($row, 0, class_C::get_shop_name( $list[shop_id]));
	  $worksheet->write($row, 1, $list[collect_date]);
	  $worksheet->write($row, 2, $list[trans_date_pos]);
	  $worksheet->write($row, 3, $list[order_id]);
	  $worksheet->write($row, 4, $list[order_subid]);
	  $worksheet->write($row, 5, $list[product_name]);
	  $worksheet->write($row, 6, $list[options]);
	  $worksheet->write($row, 7, $list[qty]);
	  $worksheet->write($row, 8, $list[shop_price]);
	  $worksheet->write($row, 9, $list[order_name]);
	  $worksheet->write($row, 10, $list[recv_name]);
	  $worksheet->write($row, 11, $settle_pay);
	  $worksheet->write($row, 12, $list[settle_fee]);
	  $worksheet->write($row, 13, $list[trans_fee]);
	  $worksheet->write($row, 14, $list[settle_money]);
	  $worksheet->write($row, 15, $last_settle_date);
	  $worksheet->write($row, 16, $this->get_order_status2($list[status]));
	  $worksheet->write($row, 17, $this->get_order_cs($list[order_cs],2), $format);
	  $worksheet->write($row, 18, $settle_ok);
	  $worksheet->write($row, 19, $settle_cancel);
	  $worksheet->write($row, 20, $list[settle_c_date]);
	  $worksheet->write($row, 21, $settle_date);

	  ////////////////////////////////////////
	  // js�� ���ް���,������,�������� ������ (2007.12.25 syhwang)
	  if (_DOMAIN_ == "js" or _DOMAIN_ == "czone")
	  {
	    $supply_amount = $list[supply_price] * $list[qty];
	    $org_amount    = $list[org_price] * $list[qty];
	    $margin = ($supply_amount - $org_amount) * 100 / $supply_amount;

	    $worksheet->write($row, 22, $supply_amount);
	    $worksheet->write($row, 23, $org_amount);
	    $worksheet->write($row, 24, round($margin,1));
	    $worksheet->write($row, 25, $list[product_id]);
	  }


	  $row++;
	}

	////////////////////////////////////
	// Let's send the file
	// sending HTTP headers
	$workbook->send($xls_name);
	$workbook->close();
    }

    function confirm()
    {
	global $connect;
	global $template;

	$start_date = $_REQUEST[start_date];
	$end_date = $_REQUEST[end_date];
	$query_date = $_REQUEST[query_date];
	$shop_id = $_REQUEST[shop_id];

	/////////////////////////////////////////////////////////        
	$query_option = "";
	if ($shop_id) $query_option = " and shop_id = '$shop_id'";

	if ($query_date == 1)       // ������ ����
	{
	    $query_option .= " and collect_date >= '$start_date' 
			  and collect_date <= '$end_date'";
	} 
	else if ($query_date == 2)  // �����Է��� ����
	{
	    $query_option .= " and substring(trans_date,1,10) >= '$start_date' 
			  and substring(trans_date,1,10) <= '$end_date'";

	}
	else if ($query_date == 3) // POS ����� ����
	{
	    $query_option .= " and status = 8
			  and substring(trans_date_pos,1,10) >= '$start_date' 
			  and substring(trans_date_pos,1,10) <= '$end_date'";
	}

	//////////////////////////////////////////////////////////
	$sql = "update orders set settle_ok = 2 
		 where settle_ok = 1
		       $query_option
	";

	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>alert('����Ȯ�� ó���� �Ϸ�Ǿ����ϴ�.');</script>";
	$this->redirect("?template=FC00&start_date=$start_date&end_date=$end_date&query_date=$query_date&shop_id=$shop_id&act=query");
	exit;
    }

    function delete()
    {
	global $connect;
	global $template;

	$start_date = $_REQUEST[start_date];
	$end_date = $_REQUEST[end_date];
	$query_date = $_REQUEST[query_date];
	$shop_id = $_REQUEST[shop_id];

	/////////////////////////////////////////////////////////        
	$query_option = "";

	if ($query_date == 1)       // ������ ����
	{
	    $query_option .= " and collect_date >= '$start_date' 
			  and collect_date <= '$end_date'";
	} 
	else if ($query_date == 2)  // �����Է��� ����
	{
	    $query_option .= " and substring(trans_date,1,10) >= '$start_date' 
			  and substring(trans_date,1,10) <= '$end_date'";

	}
	else if ($query_date == 3) // POS ����� ����
	{
	    $query_option .= " and status = 8
			  and substring(trans_date_pos,1,10) >= '$start_date' 
			  and substring(trans_date_pos,1,10) <= '$end_date'";
	}

	//////////////////////////////////////////////////////////
	$sql = "update orders set settle_ok = 0 
		 where settle_ok = 1
		   and shop_id = '$shop_id'
		       $query_option
	";

	mysql_query($sql, $connect) or die(mysql_error());
	$this->redirect("?template=FC00&start_date=$start_date&end_date=$end_date&query_date=$query_date&shop_id=$shop_id&act=query");
	exit;
    }
}

?>
