<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_combo.php";

class class_CJ00 extends class_top
{ 
    function CJ00()
    {
	global $template, $connect;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
    }

    /////////////////////////////////////////
    // 묶음 상품저장. 2006.6.6
    function save()
    {
	global $template, $connect;

	$supply_code = $_REQUEST[supply_code];
	$product_name = $_REQUEST[product_name];
	$org_price = $_REQUEST[org_price];
	$supply_price = $_REQUEST[supply_price];
	$shop_price = $_REQUEST[shop_price];
	$is_free_deliv = $_REQUEST[is_free_deliv];
	$tax = $_REQUEST[tax];
	$product_desc = $_REQUEST[product_desc];
	$pack_mgr = $_REQUEST[pack_mgr];
	$use_3pl  = $_REQUEST[use_3pl];

	$p1 = $_REQUEST[p1];
	$p2 = $_REQUEST[p2];
	$p3 = $_REQUEST[p3];
	$p4 = $_REQUEST[p4];
	$p5 = $_REQUEST[p5];
	$p6 = $_REQUEST[p6];
	$p7 = $_REQUEST[p7];
	$p8 = $_REQUEST[p8];
	$p9 = $_REQUEST[p9];
	$p10 = $_REQUEST[p10];
	$p11 = $_REQUEST[p11];
	$p12 = $_REQUEST[p12];
	$p13 = $_REQUEST[p13];
	$p14 = $_REQUEST[p14];
	$p15 = $_REQUEST[p15];
	$p16 = $_REQUEST[p16];

	$pack_list = $p1.",".$p2.",".$p3.",".$p4.",".$p5.",".$p6.",".$p7.",".$p8.",".$p9.",".$p10.",".$p11.",".$p12.",".$p13.",".$p14.",".$p15.",".$p16;

	// jk 추가 - 30개 까지 늘릴 수 있도록 변경 함
	for ( $i = 17; $i <= 50; $i++ )
	{
	    $key = "p" . $i;
	    global $$key;
	    $pack_list .= ',' . $$key;
	}


	$sql = "select max(max) max_id from products";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$max_id = $list[max_id] + 1;
	$product_id = sprintf("%05d", $max_id);

	$sql = "insert into products set
			product_id = '$product_id',
			max = '$max_id',
			supply_code = '$supply_code',
			reg_date = now(),
			reg_time = now(),
			name = '$product_name',
			org_price = '$org_price',
			supply_price = '$supply_price',
			shop_price = '$shop_price',
			is_free_deliv = '$is_free_deliv',
			tax = '$tax',
			product_desc = '$product_desc',
			enable_sale = '1',
			packed = 1,
			use_3pl = '$use_3pl',
			pack_list = '$pack_list',
			pack_mgr = 1
	  ";
	mysql_query($sql, $connect) or die(mysql_error());

	$start_date = date("Y-m-d");
        $end_date= date('Y-m-d', strtotime('2 year'));
	$sql = "insert into price_history set
			supply_code = '$supply_code',
			org_price = '$org_price',
			supply_price = '$supply_price',
			shop_price = '$shop_price',
			is_free_deliv = '$is_free_deliv',
			product_id = '$product_id',
			tax = '$tax',
			start_date = '$start_date',
			end_date = '$end_date',
			update_time = now()
		";
	mysql_query($sql, $connect) or die(mysql_error());

	$this->redirect("template.htm?template=C200");
	exit;
    }

    function CJ01()
    {
	global $template, $connect;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
    }
}
