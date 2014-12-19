<?
//====================================
//
// name: class_K200
// date: 2007.11.10 - jk
//
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_ui.php";

class class_K200 extends class_top {

    var $m_items = "";
    function class_K200()
    {
	$this->m_items = array (
                "supply_code"  => "",
                "product_id"   => "",
                "use_3pl"      => "",
                "name"         => "like",
                "options"      => "like",
        );
    }

    function K200()
    {
	global $template, $connect, $primary_product, $barcode_type;
	$top_url = base64_encode( $this->build_link_url2() );
	$primary_product = 1;
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function K202()
    {
	global $template, $top_url;
        $master_code = substr( $template, 0,1);
        include "template/K/K202.htm";
    }

    ///////////////////////////////////////////////
    // �� ����
    function K203()
    {
	global $template, $top_url, $product_id;

	$obj_product = new class_product();
	$infos       = $obj_product->get_info( $product_id );
	$arr_items   = array ( org_id => $product_id );
	$result      = $obj_product->get_list( $arr_items );

	//while ( $data = mysql_fetch_array ( $result ) )
	 //   print_r ( $data );

        $master_code = substr( $template, 0,1);
        include "template/K/K203.htm";
    }




    function upload_form()
    {
	global $template, $top_url;
        include "template/K/K201.htm";
    }

    //======================================
    // file upload�� �۾�
    // ��ǰ ������ excel�� �������� update��
    // date: 2007.11.21 - jk
    function upload()
    {
	$this->show_wait();

	global $connect, $_file, $top_url;
	$obj = new class_file();
	$arr_result = $obj->upload();

	$total_rows = sizeof ( $arr_result );
	$obj = new class_product();

	$rows = 0;
	foreach ( $arr_result as $row )
	{
	    $rows++;
	    if ( $rows == 1 ) continue;
 
	    $infos[product_id_3pl] = $row[0];
	    $infos[product_id] 	   = $row[1];
	    $infos[barcode] 	   = $row[2];
	    $infos[name]       	   = addslashes($row[3]);
	    $infos[options]        = addslashes($row[4]);
	    $infos[supply_code]    = $row[5];
	    $infos[enable_sale]    = $row[6];
	    $infos[use_3pl]        = $row[7];
	    $infos[barcode_type]   = $row[8];	// barcode type �߰� 2008.4.5

	    ///////////////////////////////
	    // sync product 
	    $obj->sync_product( $infos, $row[0] );

	    $str = "${rows} / ${total_rows}��° �۾����Դϴ�.";
	    echo "<script>show_txt('$str');</script>";
	    flush();
	}

	$this->hide_wait();
	$this->jsAlert ( "����: $rows���� �۾� �Ϸ�" );

	$this->redirect ("?". base64_decode ( $top_url ) );
	exit;
    }

    /////////////////////////////////////////////
    // barcode type ���� ����
    // 2008.4.2 - jk 
    function modify_barcode_type()
    {
	global $connect,$product_id,$barcode_type;
	$data[barcode_type] = $barcode_type;

	$obj     = new class_product();
	$obj->sync_product( $data, $product_id );	

	$obj_3pl = new class_3pl();
	$obj_3pl->_update( $data, $product_id );
    }

    //===============================
    // sync�۾� ����
    // date: 2007.11.21 - jk
    function do_sync()
    {
	global $top_url;

	$this->show_wait();
	$obj     = new class_product();
	$obj_3pl = new class_3pl();

        ////////////////////////////////////////////////////////
	// 3pl�� ����ϸ� �������� ���� ��ǰ
	$arr_items = array ( "use_3pl" => 1, "is_delete" => "zero" );
	$tot_rows  = $obj->get_count ( $arr_items );
	$result    = $obj->get_total_list( $arr_items );

	$_tot_cnt  = 0;
	$_update   = 0;
	$_reg      = 0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $_product_id = $data[product_id_3pl] ? $data[product_id_3pl] : $data[product_id];	

	    //////////////////////////////////////
	    // 1. is_reg_product ���� Ȯ���ؼ�
	    if ( $obj_3pl->check_reg ( $_product_id ) )
	    {
		$_tot_cnt++;
		$_update++;
	        // ���� ��� update
		// echo "���� : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->_update( $data, $_product_id );
	    }
	    else
	    {
		$_tot_cnt++;
		$_reg++;
	        // ���� ��� do_reg
		// echo "���� : $data[product_id_3pl] / $data[product_id] <br>";
		$obj_3pl->product_reg( $data[product_id], $data );
	    }

	    //////////////////////////////////////
	    $msg = " $i / $tot_rows �۾���";	
	    $this->show_txt ( $msg );
  	    $i++;
	}	
	$this->hide_wait();
        echo "\n\n";
	$this->jsAlert ( " ����: $_update ���: $_reg ��: $tot_rows ���� �۾� �Ϸ� ");
	$this->redirect( "?template=K202&top_url=$top_url" );
    }

    //================================
    //
    // ��ǰ ��ȸ
    // 2007.11.20
    //
    function query()
    {
	global $template, $connect, $name, $supply_code, $options, $product_id, $primary_product, $page;

	$top_url = base64_encode( $this->build_link_url2() );
	$total_rows = $this->get_count();	
	$result = $this->get_list();


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //=====================================
    // ��ǰ ���� ��ȸ ����Ʈ
    // 2007.11.21 - jk
    function get_list( $switch=0 )
    {
	global $connect, $page, $use_3pl, $primary_product, $barcode_type;
	$page = $page ? $page : 1;
	$_starter = ($page - 1) * 20;

	$query  = "select * from products";
	if ( $use_3pl == 2 )
	{
	    $use_3pl = 0;
	    $this->m_items[use_3pl] = 0;
	    $query .= $this->build_option( $this->m_items );	
	    $query .= " and use_3pl=0 ";
	    $use_3pl = 2;
	}else
	    $query .= $this->build_option( $this->m_items );	

	if ( $primary_product )
	    $query .= " and org_id='' ";

	if ( $barcode_type)
	    $query .= " and barcode_type = '$barcode_type'";

	if ( !$switch )
	    $query .= " limit $_starter, 20";
	return mysql_query ( $query, $connect );
    }

    //=====================================
    // download2 
    // 2007.11.20 - jk
    function download2()
    {
	global $saveTarget;
	$result = $this->get_list( 1 );

	// file open
        $handle = fopen ($saveTarget, "w");

	$buffer = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"
xmlns:x=\"urn:schemas-microsoft-com:office:excel\"
xmlns=\"http://www.w3.org/TR/REC-html40\">
<head>
<meta http-equiv=Content-Type content=\"text/html; charset=euc-kr\">
<xml>
<x:ExcelWorkbook>
  <x:ExcelWorksheets>
  <x:ExcelWorksheet>
   <x:Name>product list</x:Name>
   <x:WorksheetOptions>
    <x:Selected/>
   </x:WorksheetOptions>
  </x:ExcelWorksheet>
  </x:ExcelWorksheets>
</x:ExcelWorkbook>
</xml>
</head>
<body>
<table border=1>
<tr>
  <td style='mso-number-format:\"\@\"'><font color=red>����ǰ�ڵ�</font></td>
  <td style='mso-number-format:\"\@\"'>�����ǰ�ڵ�</td>
  <td style='mso-number-format:\"\@\"'>���ڵ�</td>
  <td style='mso-number-format:\"\@\"'>��ǰ��</td>
  <td style='mso-number-format:\"\@\"'>�ɼ�</td>
  <td style='mso-number-format:\"\@\"'>����ó</td>
  <td style='mso-number-format:\"\@\"'>����</td>
  <td style='mso-number-format:\"\@\"'>3PL����</td>
  <td style='mso-number-format:\"\@\"'>���ڵ�Ÿ��</td>
</tr>
";
	//$buffer = iconv( 'cp949', 'utf-8', $buffer );
        fwrite($handle, $buffer );

	while ( $data = mysql_fetch_array ( $result ) )
	{
	    $buffer = "<tr>
			<td style='mso-number-format:\"\@\"'>$data[product_id]</td>
			<td style='mso-number-format:\"\@\"'>&nbsp;</td>
			<td style='mso-number-format:\"\@\"'>$data[barcode]</td>
			<td style='mso-number-format:\"\@\"'>$data[name]</td>
			<td style='mso-number-format:\"\@\"'>$data[options]</td>
			<td style='mso-number-format:\"\@\"'>$data[supply_code]</td>
			<td style='mso-number-format:\"\@\"'>$data[enable_sale]</td>
			<td style='mso-number-format:\"\@\"'>$data[use_3pl]</td>
			<td style='mso-number-format:\"\@\"'>$data[barcode_type]</td>
 	    	    </tr>
			";
	    //$buffer = iconv( 'cp949', 'utf-8', $buffer );
            fwrite($handle, $buffer );
	}

	// footer ���
        fwrite($handle, "</table></html>");

	// excel��ȯ �۾�
	$saveTarget2 = $saveTarget . "_[products].xls";

	header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=product_list.xls" );
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");

	/*
       	$run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2"; 
	exec( $run_module );

	fclose($handle);
	*/
	if (is_file($saveTarget)) {
	    $fp = fopen($saveTarget, "r");
            fpassthru($fp);
	    fclose($fp);
	}

	// del file 
        unlink($saveTarget);
        unlink($saveTarget2);
    }

    //========================================
    // 3pl ��ǰ�� ����
    function get_count_3pl()
    {
	$obj = new class_product();

	// ����
	$arr_items = array ( "use_3pl" => 1, "packed"=>'' );
	return $obj->get_count( $arr_items );
    }

    //==================================
    // 3pl���� �����ǰ� �ִ� ��ǰ�� ����
    function get_count_3pl_manage()
    {
	$obj = new class_3pl();

	$arr_items = array ( "domain" => _DOMAIN_ );
	return $obj->product_count( $arr_items );
    }

    //=====================================
    // ����
    function get_count()
    {
	global $connect;
	
	$query  = "select count(*) cnt from products";
	$query .= $this->build_option( $this->m_items );	
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }


}

?>
