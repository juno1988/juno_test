<?
require_once "class_top.php";
require_once "class_D.php";

//////////////////////////////////////////////
//
// ilovejchina 중국 송장출력
//
class class_DAA0 extends class_top
{
   function DAA0()
   {
      global $template, $page, $start_date, $end_date;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }


}

?>
