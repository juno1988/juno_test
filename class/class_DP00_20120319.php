<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
//require_once "class_C100.php";

class class_DP00 extends class_top
{ 
   var $items;
   var $val_items;

   function DP00()
   {
      global $template;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   ///////////////////////////////////
   // ��ǰ data�� upload�Ѵ�
   // CSV�� ��ǰ data�� upload�ؾ� �� 
   function upload()
   {
      global $admin_file;

      $transaction = $this->begin("�뷮���");

      $data = file ( $admin_file );  // file�� �о�´�.
      $num_rows = count ( $data ); 

       for ( $i = 1; $i < $num_rows; $i++ )
       {
          $text = "$i / " . number_format($num_rows) . "��° ��ǰ �Է���";
          echo "<script language=javascript>
             show_waiting();
             show_txt('$text')
          </script>";
          flush();

          $d = split ( "\t", $data[$i] );


          $this->save( $d );

          if ( $i % 10 == 0 )
          {
              echo "<br>-------------------------<br>";
              flush();
          }
       }
     
        
       echo "<script language=javascript>hide_waiting()
             // alert( '" . number_format($num_rows) . "���� ��ǰ�� �ԷµǾ����ϴ�')
        </script>";

       // $this->redirect("?template=DP00");
   }

   ////////////////////////////////////////////// 
   // ��ǰ ���� format
   // id 0, name 1, desc 2, shop_price 3, supply_price 4, org_price 5, brand 6, supply_code 7, 
   // options 8 | �� ����, options 9, options 10, desc1 11
   function save( $data )
   {
      global $connect;
      $arr_items = array ( 
			"shop_id" 	=> 	"0",	// A
			"order_date" 	=> 	"1",	// B
			"order_id"	=> 	"2",	// C
			"shop_product_id"	=>	"3", // D
			"product_id"	=>	"4",	// E
			"supply_id"	=>	"5",	// F
			"product_name"	=>	"6",	// G
			"qty"		=>	"7",	// H
			"shop_price"	=>	"8",	// I
			"options"	=>	"9",	// J
			"memo"		=>	"10",	// K
			"order_name"	=>	"11",	// L
			"order_tel"	=>	"12",	// M
			"order_mobile"	=>	"13",	// N
			"recv_name"	=>	"14",	// O
			"recv_tel"	=>	"15",	// P
			"recv_mobile"	=>	"16",	// Q
			"recv_zip"	=>	"17",	// R
			"recv_address"	=>	"18",	// S
			"trans_no"	=>	"19",	// T
			"trans_who"	=>	"20"	// U
      );

      $query = "insert into orders set ";
      $query = "update orders set ";

      $i = 0;
      $order_id;
      foreach ( $arr_items as $key=>$val )
      {
          if ( $i != 0 ) $query .= ",";
 
          switch ( $key )
          {
            case "trans_who":
              if ( $data[$val] == 1 ) $value = "����";
              else $value = "����";
              break;
            case "product_id":
               $value= sprintf ( "%05d",$data[$val] );
              break;
            case "order_id":
              $order_id = $value;
            break;
            default :
              $value = addslashes($data[$val]);
          }
          
          $query .= $key . "=\"" . $value . "\"";
          $i++;
      }
      $query .= ",collect_date = '2006-09-19', status=7,order_cs='0', trans_date=Now(),trans_corp=30078 
                  where order_id='$order_id'";

echo $query;
exit;

      /////////////////////////////////////////
      // ����
      mysql_query( $query, $connect ) or die ("�߸��� ���Ǹ� �����߽��ϴ�!! / $query ");

   }

}
?>
