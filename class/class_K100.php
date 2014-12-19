<?
//====================================
//
// name: class_K100
// date: 2007.11.8 - jk
//
require_once "class_top.php";

class class_K100 extends class_top {

  function K100()
  {
	global $template;
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }
}

?>
