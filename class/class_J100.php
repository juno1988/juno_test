<?
//=============================================
//
// name: 배송대행 
// class_J100.php
// first build date: 2007.3.7 jk.ryu
// 
// History

require_once "class_top.php";
require_once "class_global.php";

class class_J100 extends class_top
{
  //================================================
  //
  // 상품 리스트 
  // date: 2007.3.7 - jk.ryu
  //
  function J100()
  {
	# global_domain: 배송 대행 업체 id
	global $template, $global_domain;

	$link_url = "?" . $this->build_link_url();     

	# default 배송: glob
	$global_domain = $global_domain ? $global_domain : "glob";

	$obj_global = new class_global( $global_domain );
	$result = $obj_global->get_list();

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
  }

  //================================================
  //
  // 상품 상세 
  // date: 2007.3.7 - jk.ryu
  //
  function J101()
  {
	# global_domain: 배송 대행 업체 id
	global $template, $global_domain, $product_id;

	$link_url = "?" . $this->build_link_url();     

	# default 배송: glob
	$global_domain = $global_domain ? $global_domain : "glob";

	$obj_global = new class_global( $global_domain );
	$result = $obj_global->get_detail( $product_id );
	$data = mysql_fetch_array ( $result );

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
 
  }

}  

?>
