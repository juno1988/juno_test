<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
// get_list : ��ǰ ����Ʈ
// get_detail : ��ǰ �� ����

class class_CE00 extends class_top
{
   //////////////////////////////////////////////////////
   // ��ǰ ����Ʈ 
   function CE00()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      // �Ǹ�ó�� ��ǰ ����Ʈ�� �����´� 
      if ( $_REQUEST["page"] )
         $result = class_C::get_product_supply_list( &$total_rows, 2 );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function get_shop_name( $shop_id )
   {
      return class_C::get_shop_name($shop_id);
   }

   function download()
   {
      global $connect, $saveTarget;

      $transaction = $this->begin("���̳ʽ� �����ǰ �ٿ�ε� (CE00)");

      $handle = fopen ($saveTarget, "w");

      $download = 1;
      $stock = 2;
      $result = class_C::get_product_supply_list( &$total_rows , $stock, $download);

      ////////////////////////////////////////
      // writing datas to file
      $i = 1;
 
      $download_items = array ( "���̵�"=>"product_id",
                                "����"=>"enable_sale",
                                "��ǰ��"=>"name",
                                "�ɼ�"=>"options",
                                "����ó"=>"supply_code",
                                "����"=>"org_price",
                                "��ǰ��"=>"supply_price",
                                "�ǸŰ�"=>"shop_price",
                                "�������"=>"safe_stock",
                                "�������"=>"current_stock",
                                "��ǰ����"=>"product_desc",
                              );

      while ( $data = mysql_fetch_array ( $result ) )
      {
         if ( $i == 1 )
         {
            foreach ( $download_items as $key=>$value )
               $buffer .= $key . "\t";
            $buffer .= "\n";
         }

         foreach ( $download_items as $value )
         {
            switch ( $value )
            {
               case "supply_code":
                  $buffer .= $this->get_supply_name2 ( $data[$value] ) . " \t";
               break;
               case "enable_sale";
                  $buffer .=  $data[enable_sale] ? "�ǸŰ��� \t" : "�ǸźҰ� \t";
               break;
               default:
                  $val = $data[$value] ? $data[$value] : ".";
                  $buffer .= str_replace( array("\r", "\n", "\r\n","\t" ), " ", $val ) . " \t";
               break;
            }
         }

         fwrite($handle, $buffer . "\n" );
         $buffer = "";
         $i++;
      }

      // file ����
      fclose($handle);

      if (is_file($saveTarget)) {
          $fp = fopen($saveTarget, "r");
          fpassthru($fp);
      }

      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);
      unlink($saveTarget);

      $transaction = $this->end( $transaction );

      exit;
   }

}
?>