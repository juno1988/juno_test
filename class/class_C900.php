<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
// get_list : ��ǰ ����Ʈ
// get_detail : ��ǰ �� ����

class class_C900 extends class_top
{
   //////////////////////////////////////////////////////
   // ��ǰ ����Ʈ 
   function C900()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      echo "<script> show_waiting() </script>";
     
      // �Ǹ�ó�� ��ǰ ����Ʈ�� �����´� 
      if ( $_REQUEST["page"] )
         $result = class_C::get_product_supply_list( &$total_rows );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      echo "<script> hide_waiting() </script>";
   }

}
?>
