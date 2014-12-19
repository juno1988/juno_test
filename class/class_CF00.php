<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_combo.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_CF00 extends class_top
{
   function CF00()
   {
      global $template, $id, $d_date;

      $link_url = "?" . $this->build_link_url();     

      if ( $_REQUEST["page"] )
         $result = $this->get_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //////////////////////////////
   // 상품 리스트
   function get_list( &$max_row, $page, $download=0 )
   {
      global $connect;
      global $string, $disp_copy, $enable_sale;

      /// logic
      $query = "select * ";

      $option = " from products 
                 where is_delete = 1 and is_represent=0 ";

      switch ( $disp_copy )
      {
         case "2": // 원본
            $option .= " and org_id='' ";
         break;
         case "3": // 복사본
            $option .= " and org_id<>'' ";
         break;
      }

      // session level이 0이면 업체임
      if ( !$_SESSION["LOGIN_LEVEL"] )
          $option .= " and supply_code = '" . $_SESSION["LOGIN_CODE"] . "'";

      // id 인지 name인지 결정해야 함
      if ( $string )
      {
         if ( $_REQUEST["string_type"] == "id" )
            $option .= " and product_id = '$string' ";
         else if ( $_REQUEST["string_type"] == "name" )
            $option .= " and name like '%$string%' ";
      }

      switch ( $_REQUEST["e_sale"] )
      {
         case 1:  // 판매가능
            $option .= " and enable_sale = '1'";
         break;
         case 2:  // 판매 불가
            $option .= " and enable_sale = '0'";
         break;
      }

      if ( !$page )
         $page = 1;
      $starter = ($page-1) * _line_per_page;

      $order = " order by delete_date desc, name, options";

      if ( $download )
         $limit = "";
      else
         $limit = " limit $starter, 1000";

      $result = mysql_query ( $query . $option . $order . $limit, $connect );

      return $result;
   }   

    function cancel_delete()
    {
        global $connect, $product_list;
        
        $product_list = stripslashes($product_list);
        $org_list = "";
        
        // 대표상품 복구
        $query = "select distinct org_id from products where product_id in ($product_list) and org_id>'' ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) ) 
            $org_list .= ($org_list ? "," : "") . "'" . $data[org_id] . "'";
        
        $query = "update products set is_delete=0, delete_date=0 where product_id in ($org_list)";
        mysql_query($query, $connect);

        // 상품 복구
        $query = "update products set is_delete=0, delete_date=0 where product_id in ($product_list)";
debug("상품복구3 : " . $query);
        mysql_query($query, $connect);
    }
    
    function clear_trash()
    {
        global $connect;
        
        $query = "delete from products where is_delete=1";
        mysql_query($query, $connect);
    }

    function clear_products()
    {
        global $connect;
        
        $val = array();
        
        $query = "select count(*) cnt from orders";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[cnt] == 0 )
        {
            $query = "truncate products";
            mysql_query($query, $connect);

            $query = "truncate price_history";
            mysql_query($query, $connect);

            $query = "truncate org_price_history";
            mysql_query($query, $connect);

            $val['error'] = 0;
        }
        else
            $val['error'] = 1;
            
        echo json_encode($val);
    }
}
 
