<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
// 상품 검색
class class_CB00 extends class_top
{
   //////////////////////////////////////////////////////
   // 상품 리스트 
   function CB00()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();     
    
      // 판매처별 상품 리스트를 가져온다 
      if ( $_REQUEST["page"] )
         $result = class_C::get_product_shop_list( &$total_rows );

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

      $transaction = $this->begin("상품 다운로드 (C700)");

      $handle = fopen ($saveTarget, "w");

      $download = 1;
      $stock = 0;
      $result = class_C::get_product_shop_list2( &$total_rows , $stock, $download);

      ////////////////////////////////////////
      // writing datas to file
      $i = 1;
 
      $download_items = array ( 
                                "판매처"=>"shop_id",
                                "아이디"=>"product_id",
                                "판매처 상품코드"=>"shop_code",
                                "상태"=>"enable_sale",
                                "상품명"=>"name",
                                "옵션"=>"options",
                                "공급처"=>"supply_code",
                                "원가"=>"org_price",
                                "납품가"=>"supply_price",
                                "판매가"=>"shop_price",
                                "상품설명"=>"product_desc",
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
               case "shop_id":
                  $buffer .= $this->get_shop_name( $data[$value] ) . " \t";
               break;
               case "supply_code":
                  $buffer .= $this->get_supply_name2 ( $data[$value] ) . " \t";
               break;
               case "enable_sale";
                  $buffer .=  $data[enable_sale] ? "판매가능 \t" : "판매불가 \t";
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

      // file 삭제
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
