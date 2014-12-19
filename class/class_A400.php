<?
require_once "class_A.php";
require_once "class_top.php";

class class_A400 extends class_top
{
   var $arr_items;
   var $val_items;  // 반듯이 입력해야 하는 item

    function A400()
    {
	global $template;
	global $connect;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
    }

    function modify()
    {
	global $connect;

	$stock_manage_use 	= $_REQUEST[stock_manage_use];
	$island_use 		= $_REQUEST[island_use];
	$base_trans_code 	= $_REQUEST[base_trans_code];
	$base_trans_price 	= $_REQUEST[base_trans_price];
	$jaego_use 		= $_REQUEST[jaego_use];
	$pack_diff_supply 	= $_REQUEST[pack_diff_supply];
	$bracket_match_use 	= $_REQUEST[bracket_match_use];
	$pack_bracket 		= $_REQUEST[pack_bracket];
	$jaego_basedt 		= $_REQUEST[jaego_basedt];
	$ez_version 		= $_REQUEST[ez_version];
      			
	
	if ($base_trans_code) $option1 = " base_trans_code = '$base_trans_code',";
	else $options1 = "";
 
	$sql = "update ez_config set
			stock_manage_use  = '$stock_manage_use',
			jaego_use 	  = '$jaego_use',
			island_use 	  = '$island_use',
			pack_diff_supply  = '$pack_diff_supply',
			bracket_match_use = '$bracket_match_use',
			pack_bracket 	  = '$pack_bracket',
			jaego_basedt 	  = '$jaego_basedt',
			${option1}
			base_trans_price  = '$base_trans_price',
			version  	  = '$ez_version'";

	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>document.location.href = '?template=A400';</script>";
	exit;
    }
}

?>
