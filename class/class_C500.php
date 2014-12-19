<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_C500 extends class_top
{
   function C500()
   {
      global $template;
      $userid = $_SESSION["LOGIN_ID"];
      $link_url = "?" . $this->build_link_url();

      $result = class_C::get_product_modify_list( &$total_rows );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function C501()
   {
      global $template;

      if ( !$_REQUEST["link_url"] )
         $link_url = urlencode("template=C500&start_date=" . $_REQUEST["start_date"] . "&end_date=" . $_REQUEST["end_date"]);

      $userid = $_SESSION["LOGIN_ID"];

      $data = $this->get_detail( $_REQUEST["id"] );
      $result1 = $this->get_list ($data[confirm_date]);

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   ////////////////////////////////////
   // list
   // 변경 시간을 넘겨야 함
   function get_list ( $mod_time )
   {
      global $connect;
      $id = $_REQUEST["id"];

      $query = "select *
                  from code_match
                 where id='$id'";

      return mysql_query ( $query , $connect );
   } 

   ///////////////////////////////////
   // 상세 정보
   function get_detail ( $id )
   {
      global $connect;
      $query = "select *,count(*) cnt from products a, code_match b ";

      $option .= " where a.product_id = b.id
                     and a.product_id = '$id'
                   group by a.product_id";

      $result = mysql_query ( $query . $option, $connect );
      $data = mysql_fetch_array ( $result );
      return $data;
   } 

   ////////////////////////////////////////////
   // 판매처 작업 완료 count
   function confirmed_count( $product_id, $last_modify_date )
   {
      global $connect;

      $query = "select count(*) as cnt from code_match 
                 where id='$product_id'
                   and confirm_date > '$last_modify_date'";

      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );
      echo $data[cnt];
   } 

   function confirm ()
   {
      global $connect;

      $query = "update code_match set confirm_date = Now(), userid=' " . $_SESSION["LOGIN_ID"] . "'
                 where shop_id='" . $_REQUEST["shop_id"]  . "' and shop_code='" . $_REQUEST["shop_code"] . "'";

      mysql_query ( $query, $connect );

      $this->redirect("?" . $this->build_link_url());
      exit;
   }
  
}

?>
