<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
//require_once "class_C100.php";

class class_CA00 extends class_top
{ 
   var $items;
   var $val_items;

   function CA00()
   {
      global $template;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   ///////////////////////////////////
   // ��ǰ data�� upload�Ѵ�
   function upload()
   {
      global $admin_file;

      $transaction = $this->begin("�뷮 ��ǰ ����");

      $data = file ( $admin_file );  // file�� �о�´�.
      $num_rows = count ( $data ); 

       for ( $i = 0; $i < $num_rows; $i++ )
       {
          $text = "$i / " . number_format($num_rows) . "��° ��ǰ �Է���";
          echo "<script language=javascript>
          show_waiting();
          show_txt('$text')
          </script>";
          flush();

          $d = split ( ",", $data[$i] );
          $this->update( $d );
       }
       
       echo "<script language=javascript>hide_waiting()
             alert( '" . number_format($num_rows) . "���� ��ǰ ������ ����Ǿ����ϴ�')
             </script>";

       $transaction = $this->end( $transaction );
       $this->redirect("?template=CA00");
   }

   ////////////////////////////////////////////// 
   // ��ǰ ���� format
   // id, name, origin, brand, org_price, supply_price, shop_price, options, product_desc, supply_code
   function update( $data )
   {
      global $connect;

      ////////////////////////////////////////
      // query ����
      // �ǸŰ��� ����
      $query = "update products set last_update_date=Now() ";

      $items = array( "name", "origin", "brand", "org_price", "supply_price",
                      "options", "product_desc", "supply_code");

      $product_id = $data[0]; // image name���� �� key��
      $max = count($items);
      for($i = 1; $i <= $max; $i++)
      {
           $j = $i - 1;

           if ( $data[$i] )
              $query .= ",$items[$j]='" . addslashes( $data[$i] ) . "'";
      }

      // product_id�� ������ ������ 1�� ���� ��� ��
      $max = count($items) + 1;

      ///////////////////////////////////////////
      // remote image ����
      $image_items = array ( "500"=>"img_500", "desc1"=>"img_desc1", 
                             "desc2"=>"img_desc2", "desc3"=>"img_desc3", "desc4"=>"img_desc4" );

      //////////////////////////////////////////
      // parsing data
      // 0: ��ü
      // 1: protocol
      // 2: host
      // 3: path
      // 4: Ȯ���� 
      $i = 0;
      while (!is_null($key = key( $image_items ) ) ) 
      {
         //$key = key($image_items);
         $image = $image_items[$key]; 
         $path = trim($data[$max + $i]);

         // case insensitive
         eregi("(.*)//([a-z+.]+)+(.*)([a-zA-Z]{3}$)", $path, $matched);

         $host = $matched[2];
         $image_link = $matched[3] . $matched[4];
         $ext = $matched[4];

         $file_name = $product_id . "_" . $key . "." . $ext;

         if ( !$path )
         {
            $i++;
            next ( $image_items );
            continue;
         }

         if ( class_file::write( $host, $image_link, $file_name ))
         {
           $query .= ",";
           $query .= $image . "='$file_name'";
         }

         $i++;
         next ( $image_items );
      }

      $query .= " where product_id = '$product_id'";

      /////////////////////////////////////////
      // ����
      mysql_query( $query, $connect );
echo $query;
exit;
   }

}
?>
