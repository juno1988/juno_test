<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_D400
//

class class_DA00 extends class_top 
{

   function DA00()
   {
      global $template, $page;
      $line_per_page = _line_per_page;
      $link_url = "?" . $this->build_link_url();
      $start_date = $_REQUEST["start_date"];
      $end_date = $_REQUEST["end_date"];

      if ( $page )
         $result_order = class_D::get_order_list( &$total_rows , 0 , "trans_date"); // ���� �Է��� �������� �˻�

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function download_confirm ()
   {
      $link_url = "?" . $this->build_link_url();

      $result = class_D::confirm_order(); 
      $this->redirect ( $link_url );
      exit;
   }

   function download()
   {
      global $connect, $saveTarget;

      ///////////////////////////////////
      // open file to get file handle 
      $handle = fopen ($saveTarget, "w");
      $result_order = class_D::get_order_list( &$total_rows , 1 , "trans_date"); // ���� �Է��� �������� �˻�

      ////////////////////////////////////////
      // writting datas to file
//      $buf = "�ֹ���ȣ\t�����ȣ\t�Ǹ�ó\t������\t��ǰ��\t�����ȣ\t�ּ�\t��ȭ\t��ȭ2\t���";
//      fwrite($handle, $buf); 

      while ( $data = mysql_fetch_array ( $result_order ) )
      {
         $buffer = "$data[order_id]\t \t$data[shop_name]\t$data[product_name] $data[options]\t$data[recv_name]\t";
         $buffer .= "$data[recv_zip]\t";
         $buffer .= "$data[recv_address]\t$data[recv_tel]\t$data[recv_mobile]\t$data[memo] $data[message]\r\n";

         fwrite($handle, $buffer); 
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
      exit; 
   }
}

?>
