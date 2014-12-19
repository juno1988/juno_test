<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
class class_I600 extends class_top
{
  //////////////////////////////////////////////////////
  // 상품 리스트 
  function I600()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I601()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I602()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I603()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I604()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I605()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function add()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[product_id];
	$start_date = $_REQUEST[start_date];
	$type = $_REQUEST[type];
	$qty = $_REQUEST[qty];
	$memo = $_REQUEST[memo];

	
	$sql = "insert into jaego_inout_pack set
			product_id = '$product_id',
			start_date = '$start_date',
			type = '$type',
			qty = '$qty',
			memo = '$memo'";
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>self.close();</script>";
	// $this->opener_redirect("template.htm?template=I600&act=query");
	exit;
  }

  function delete()
  {
	global $template;
	global $connect;

	$no = $_REQUEST[no];
	$product_id = $_REQUEST[product_id];

	$sql = "delete from jaego_inout_pack where no = '$no'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href='popup.htm?template=$template&product_id=$product_id';</script>";
	exit;
  }
}
