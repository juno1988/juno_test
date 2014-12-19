<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_B.php";

//////////////////////////////////////////////
// get_list : ��ǰ ����Ʈ
// get_detail : ��ǰ �� ����

class class_CC00 extends class_top
{  


   function CC00()
   {
      global $template, $page, $start_date, $end_date;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-5 day'));

      $userid = $_SESSION["LOGIN_ID"];
      $link_url = "?" . $this->build_link_url();

      if ( $page ) 
         $result = $this->get_list ( &$total_rows );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function get_list( &$total_rows , $stock=0, $download=0)
   {
      global $connect, $page, $start_date, $end_date, $shop_id;

      if ( !$page ) $page = 1;
      $starter = ( $page - 1 ) * _line_per_page;  
 
      $query = "select a.*, b.*";
      $query_cnt = "select count(*) cnt ";
      $option = " from products a, code_match b
                 where a.product_id = b.id
                   and a.enable_sale = 0 
                   and a.sale_stop_date >= '$start_date'
                   and a.sale_stop_date <= '$end_date' ";
    
      if ( $shop_id )
         $option .= " and b.shop_id = '$shop_id' "; 


      ///////////////////////////////////////////////
      // query for count
      $result = mysql_query ( $query_cnt . $option, $connect );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];

      ////////////////////////////////////////////////
      // query for list
      if ( !$download )
         $option .= " limit $starter, " . _line_per_page;

      $result = mysql_query ( $query . $option, $connect );
      return $result;
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
         return "<span class=red>��Ȯ��</span>";
   }  
   

   // download
   function download()
   {
      global $connect, $saveTarget;

      $transaction = $this->begin("��ǰ �ٿ�ε� (C700)");

      $handle = fopen ($saveTarget, "w");

      $download = 1;
      $stock = 0;
      $result = $this->get_list( &$total_rows , $stock, $download);

      ////////////////////////////////////////
      // writing datas to file
      $i = 1;
 
      $download_items = array ( 
                                "�Ǹ�ó"=>"shop_id",
                                "���̵�"=>"product_id",
                                "�Ǹ�ó ��ǰ�ڵ�"=>"shop_code",
                                "����"=>"enable_sale",
                                "��ǰ��"=>"name",
                                "�ɼ�"=>"options",
                                "����ó"=>"supply_code",
                                "����"=>"org_price",
                                "��ǰ��"=>"supply_price",
                                "�ǸŰ�"=>"shop_price",
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
               case "shop_id":
                  $buffer .= class_C::get_shop_name( $data[$value] ) . " \t";
               break;
               case "supply_code":
                  $buffer .= class_C::get_supply_name2 ( $data[$value] ) . " \t";
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
