<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_C400 extends class_top
{
   function C400()
   {
      global $template, $start_date, $end_date;
      $userid = $_SESSION["LOGIN_ID"];
      $link_url = $this->build_link_url();

      $result = class_C::get_product_soldout_list ( $userid, &$max_row );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   function confirm()
   {
      global $connect;
      global $template;
      global $link_url;

      $userid = $_SESSION["LOGIN_ID"];
      $product_id = $_GET["id"];

      $query = "insert product_soldout_confirm set product_id='$product_id', userid='$userid', confirm_date=Now()";
      mysql_query ( $query , $connect );
     
      $this->redirect("?template=C400&" . $link_url);
      exit;
   }


   function check_confirm ( $product_id, $sale_stop_date )
   {
      global $connect;
      $query = "select *, if ( confirm_date > '$sale_stop_date', 1, 0 ) as confirmed 
                  from product_soldout_confirm 
                 where userid='". $_SESSION["LOGIN_ID"] ."' 
                   and product_id='$product_id'";

      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array ( $result );

      if ( $data[confirmed] )
         return str_replace(" ", "<br>", $data[confirm_date] );
      else
         return "<span class=red>미확인</span>";
   }  
}

?>
