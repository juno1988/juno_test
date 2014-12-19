<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
class class_I500 extends class_top
{
  //////////////////////////////////////////////////////
  // 상품 리스트 
  function I500()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I501()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I502()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I503()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I504()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I505()
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

	
	$sql = "insert into jaego_inout set
			product_id = '$product_id',
			start_date = '$start_date',
			type = '$type',
			qty = '$qty',
			memo = '$memo'";
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>self.close();</script>";
	// $this->opener_redirect("template.htm?template=I500&act=query");
	exit;
  }

  function delete()
  {
	global $template;
	global $connect;

	$no = $_REQUEST[no];
	$product_id = $_REQUEST[product_id];

	$sql = "delete from jaego_inout where no = '$no'";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href='popup.htm?template=$template&product_id=$product_id';</script>";
	exit;
  }
}
