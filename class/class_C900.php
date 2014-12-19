<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_C900 extends class_top
{
   //////////////////////////////////////////////////////
   // 상품 리스트 
   function C900()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      echo "<script> show_waiting() </script>";
     
      // 판매처별 상품 리스트를 가져온다 
      if ( $_REQUEST["page"] )
         $result = class_C::get_product_supply_list( &$total_rows );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      echo "<script> hide_waiting() </script>";
   }

}
?>
