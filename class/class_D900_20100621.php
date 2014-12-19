<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_B.php";
require "../ExcelReader/reader.php";
require "../neoadmin/ez_excel_lib.php";

////////////////////////////////
// class name: class_D900
//
// �Ǹ�ó �ڵ�
// 10001 : ����
// 10002 : G����
// 10003 : ����
// 10005 : ������ũ 
// 10006 : ������ũ ����
// 10007 : GS Eshop 
// 10009 : �Ե�
// 10010 : ���θ��� 
// 10011 : ���� 
// 10012 : �Ϳ� 
// 10013 : ����Ʈ�� 
// 10014 : �츮Ȩ 
// 10016 : ����� 
class class_D900 extends class_top 
{
   var $g_order_id;
   var $debug = "off"; // ��ü download: on/off
   /////////////////////////////////////
   // type : xls | tab | comma
   // header : download ������ header
   // start_index : �� ��° data���� �������� ����
   // data_type : same | diff
   // data_format : array ( "1"=>"3" ) 1��° column�� upload�� ���° data�� ����? 
   // trans_corp : ���° column�� ����?
   // trans_no : ���° column�� ����?
   // order_id

   var $type, $header, $start_index, $data_type, $data_format, $trans_corp, $trans_name, $trans_no, $order_id;
   
   function D900()
   {
      global $template;
      $line_per_page = _line_per_page;

      $link_url = "?" . $this->build_link_url();
      $result_order = class_D::get_order_list( &$total_rows ); 

      /////////////////////////////////////////
      $result_history = $this->get_order_download_transaction( &$total_rows );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   /////////////////////////////
   // ���� 
   function init_10001()
   {
      $this->type = "tab";
      $this->header = "[��Ź�ȣ] ��ǰ��,������ȣ,������ID,�ּ�,�����ڸ�,�����/����ȣ";
      $this->start_index = 1;
      $this->data_type = "diff";
      $this->data_format = array(2, 3, 7, 13, 8, "trans_no");
      $this->trans_corp = -1;  // not use 
      $this->trans_name = -1;  // not use
      $this->trans_no = 5;
      $this->order_id = 3;     // ���� ��ȣ ��ġ
   }

   ////////////////////////////////////////////////////////////
   // G ���� 
   function init_10002()
   {
      $this->type = "tab";
      $this->header = "����,��ۻ���,�߼�Ȯ����,�߼ۿ�����,�����,�ù��,�����ȣ,��������ڵ�,���ּ��߱���,ü����,��ٱ��Ϲ�ȣ,ü���ȣ,��ǰ��ȣ,��ǰ��,�Ǹ��ڻ�ǰ�ڵ�,��������,����,�����ڰ����ݾ�,ü�ᰡ,��ü���,�Ѱ��޾�,�����ڸ�,�����ڿ���ó1,�����ڿ���ó2,�����θ�,�����ο���ó1,�����ο���ó2,�����,���ݰ�꼭��û����,���ݰ�꼭�߱޿���,����������ȣ,��������ּ�,��������ּ�,��������,�����ڸ޸�,G���ϸ޸�,��û��,����ڵ�Ϲ�ȣ,ȸ���,��ǥ��,����������,�����ȣ,���ݰ�꼭������,����,����,�߱���,TRANS_NO,���ݰ�꼭�߱޿���,��۸޸�,��۸޸���ġ,�ֹ���ȣ,ORDERSPPL,stat,��������,�귣��,�߼ۿ�����,�߼���������,���ּ�Ȯ��ó���ڵ�,���ּ�Ȯ��ó����,ACNT_WAY,CCONTR_DT,SEQ_NO,����ǰ,CLAIM_TYPE,b2e,���ּ�Ȯ������,����ſ���,��ۺ�����,�ù豸��,GMARKET_ORD_NO,IP_TRY_YN,delivery_group_no";
      $this->start_index = 1;    // header data�� ������
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 5;     // ������ 0����
      $this->trans_no = 6;       // ������ 0����
      $this->order_id = 11;      // ������ 0����
   }

   /////////////////////////////
   // ����
   function init_10003()
   {
      $this->type = "tab";
      // header����
      $this->start_index = 1;
      $this->data_type = "diff";
      $this->data_format = array(0, 3, 6, "trans_corp", "trans_no");
      // data_type: diff�� ��쿣 ������� �ʴ´� 
      //$this->trans_corp = 3;
      //$this->trans_no = 4;
      $this->order_id = 0;
   }

   /////////////////////////////
   // ���� ����
   function init_10004()
   {
      $this->type = "xls";
      // header����
      $this->header = "�ŷ���ȣ,��۹��,��ۻ�,����������,�����ȣ";
      $this->start_index = 1;
      $this->data_type = "same";
      //$this->data_format = array(0, 3, 6, "trans_corp", "trans_no");
      // data_type: diff�� ��쿣 ������� �ʴ´� 
      $this->trans_corp = -1;
      $this->trans_no = 5;
      $this->trans_name = -1;
      $this->order_id = 1;
   }

   /////////////////////////////////
   // ������ũ
   function init_10005()
   {
      $this->type = "xls";
      $this->header = "�����ȣ,�ù��ü�ڵ�,�ֹ��Ϸù�ȣ,�ֹ���ȣ,�ֹ���,�ֹ����,��ǰ�ڵ�,ISBN(Lot),���޻� ��ǰ�ڵ�,��ǰ��,��ǰ�ɼ�,����,����,�ܰ�,�ݾ�,�ֹ���,�ֹ�����ȭ��ȣ,�ֹ����޴���ȭ,������,��������ȭ��ȣ,�������޴���ȭ,�����ȣ,�ּ�,��۸޼���,�����޼���,��û�Ͻ�,��������ȣ,��û�Ͻ�,��������ȣ";

      $this->start_index = 1; // header data�� ������
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 2; // ������ 1����
      $this->trans_no = 1;   // ������ 1����
      $this->order_id = 4;    // ������ 1����
   }

   /////////////////////////////////
   // ������ũ ����
   function init_10006()
   {
      $this->type = "xls";
      $this->header = "�����ȣ,�ù��ü�ڵ�,�ֹ��Ϸù�ȣ,�ֹ���ȣ,�ֹ�����,�ֹ������Ͻ�,�Ա��Ͻ�,����߼۸����Ͻ�,�ֹ��ڸ�,�ֹ��ڿ���ó1,�ֹ��ڿ���ó2,�ֹ����̸���,�����ڸ�,�����ڿ���ó1,�����ڿ���ó2,������ �����ȣ,������ ���ּ�,��۽� ���ǻ���,��ǰ�ڵ�,��ǰ��,�ɼ�,��ǰ�ܰ�,�ɼǱݾ�,�ֹ��Ѿ�,�Ǹż�����,�Ǹż�������,�����ݹ߱޾�,�����ݼ�����,�������Һμ�����";

      $this->start_index = 1; // header data�� ������

      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 2; // ������ 1����
      $this->trans_no = 1;   // ������ 1����
      $this->order_id = 4;    // ������ 1����
   }

   /////////////////////////////////
   // gs eshop 
   function init_10007()
   {
      $this->type = "xls";
      $this->header = "����,�ֹ���ȣ,������ȣ";
      $this->start_index = 1; // header data�� ������
      $this->data_type = "diff";
      $this->data_format = array ( "No", 1, "trans_no" );
      $this->trans_corp = -1; // ������ 1����
      $this->trans_no = 3;    // ������ ��ġ
      $this->order_id = 1;    // �������� �ֹ� ��ȣ�� ��ġ
   }

   ////////////////////////////////////////////////////////////
   // �Ե� 
   function init_10009()
   {
      $this->type = "xls";
      $this->header = "�ֹ���ȣ,���ֹ���ȣ,�ֹ���ǰ��ȣ,���ֹ���ǰ����,�ֹ���,��������,�ֹ���,ȸ��ID,ȸ����ȭ��ȣ1,ȸ����ȭ��ȣ2,������,�����ο����ȣ,�������ּ�1,�������ּ�2,��������ȭ��ȣ1,��������ȭ��ȣ2,�븮������,�븮��������ȭ��ȣ,�޴»��,�����»��,�޽���,�޸�1,�޸�2,��ǰ��,��ǰ�ڵ�,�ɼǰ�,�귣���,�𵨹�ȣ,�ǸŰ�,�ֹ��ݾ�,������,��������,���ֹ�ȣ,���ּ���,�ֹ�����,�߼ۿϷ����,�߼ۺҰ�����,��ó������,�߼ۿϷ�����,�ù��,�����ȣ,�߼ۿ�����,��ó������,��ȯ��ǰ����,���Դܰ�";

      $this->start_index = 1; // header data�� ������
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 40; // ������ 1����
      $this->trans_no = 41;   // ������ 1����
      $this->order_id = 1;    // ������ 1����
   }

   ////////////////////////////////////////////////////////////
   // ���� ����
   function init_10010()
   {
      $this->type = "csv";
      $this->start_index = 1; // header data�� ������
      $this->data_type = "diff";
      $this->data_format = array(1, "trans_no");
      $this->order_id = 1;    // ������ 1����
   }

   ////////////////////////////////////////////////////////////
   // �Ϳ� 
   function init_10012()
   {
      $this->type = "csv";
      $this->header = "�ֹ���ȣ,�Ϸù�ȣ,��ü��,��üID,�Ǹű���,ī�װ�,�ֹ���,�ֹ���,�ֹ�����ȭ,�ֹ���HP,������,��������ȭ,������HP,�����ο����ȣ,�������ּ�,ó������,�ֹ�����,��ǰID,��ǰID,��ǰ��,��ǰ��,�𵨸�,���ް�,�ǸŰ�,���Ǹűݾ�,�����ݾ�,������,�������Һμ�����,�ù��޸�,�������,��ۺ�,��������,�ù��,������ȣ,����Է���";
      
      $this->start_index = 1; // header data�� ������
      $this->data_type = "same";
      $this->trans_corp = 31; // ������ 1����
      $this->trans_no = 32;   // ������ 1����
      $this->order_id = 1;    // ������ 1����
   }

   ////////////////////////////////////////////////////////////
   // ����Ʈ�� 
   function init_10013()
   {
      $this->type = "csv";
      $this->start_index = 2; // header data�� ������
      $this->data_type = "same";
      $this->trans_corp = 31; // ������ 1����
      $this->trans_no = 32;   // ������ 1����
      $this->order_id = 1;    // ������ 1����
   }

   ////////////////////////////////////////////////////////////
   // �츮Ȩ 
   function init_10014()
   {
      $this->type = "csv";
      $this->header = "\n\n\nNo,����������,�ֹ���ȣ,��ۻ�,��Ÿ,������ȣ,�����,�������,������Ȳ,VIP����,����,������,����ó,�ڵ���,�ֹ�����,��������,��۱���,��ǰ����,��ǰ�ڵ�,��ǰ�ڵ�,��ǰ��,��ǰ��,����,�����ȣ,�����,�ǸŰ�,����";
      $this->start_index = 4; // header data�� ������
      $this->data_type = "same";
      $this->trans_corp = 3; // ������ 0����
      $this->trans_no = 5;   // ������ 0����
      $this->order_id = 3;    // ������ 1����
   }


   ////////////////////////////////////////////////////////////
   // ����� 
   function init_10016()
   {
      $this->type = "xls";
      $this->start_index = 1; // header data�� ������
      $this->data_type = "diff";
      $this->data_format = array("No", 2, 3,"trans_no");
      $this->order_id = 2;    // ������ 1����
   }


   ////////////////////////////////////////////////////////////
   //�պ�
   function init_10018()
   {
      $this->type = "csv";
      $this->header = "�ֹ���ȣ,�ֹ��󼼹�ȣ,�����ȣ�Է¶�,�Ǹ�ó,��ǰ��,��ǰ����,����,����,�ֹ���,������,���ڿ���,�����ȣ,�ּ�,��ȭ��ȣ,�޴���,������,���������,�ֹ���,�ǸŴܰ�,������,���ް�,������,����û,��ǰ�ڵ�";
      $this->start_index = 1; // header data�� ������
      $this->data_type = "same";
      $this->trans_corp = -1; // ������ 0����
      $this->trans_no = 2;   // ������ 0����
      $this->order_id = 0;    // ������ 0����
   }


   ////////////////////////////////////////////////////////////
   // ���� 
   function init_10020()
   {
      $this->type = "xls";
      $this->start_index = 1; // header data�� ������
      $this->header = "���CHK,�ֹ���ȣ,�ֹ��ڸ�,��ǰ��,��ǰ�ɼ�,�ֹ���,���,�ù��,�����ȣ,�ֹ��󼼹�ȣ,�ֹ�����,�ֹ�����,��ȭ��ȣ,�޴�����ȣ,��ǰ�ڵ�,������,�����ȣ,�ּ�,�������ȭ��ȣ,������޴�����ȣ,��۸޽���,����,�Ǹž�,�ù���ڵ�,�Ա�Ȯ����";
      $this->data_type = "diff";
      $this->data_format = array("check",2,3,4,5,6,7,"trans_corp","trans_no",10,11,12,13,14,15,16,17,18,19,20,21,22,23);
      $this->order_id = 2;    // ������ 1����
   }


   ///////////////////////////////////////////////////////////
   // file�� upload�� download��
   // file�� download�� �� ����
   // date: 2005.8.26
   function upload()
   {
      global $connect, $shop_id, $admin_file, $admin_file_name;

      $transaction = $this->begin("�Ǹ�ó����ٿ�ε�");
      
      $shop = "init_" . $shop_id;
      $this->{$shop}(); // �Ե�

      // �д� �κ�
      switch ( $this->type )
      {
         case "xls":
             $data = $this->excel_read ( $admin_file, $admin_file_name , &$num_rows );
         break;
         default :
            $data = file ( $admin_file );  // file�� �о�´�.
            $num_rows = count ( $data ); 
         break;
      }

      // ���
      $this->write( $data, $num_rows, &$filename );

      $this->end( $transaction );

      // redirect
      $this->redirect( "?template=D900&filename=$filename" );
      exit;
   }

   function write ( $datas, $num_rows , &$filename)
   {
       global $shop_id;

       // ����� write�� ���ο� data�� open
       $filename = $_SESSION["LOGIN_ID"] . $shop_id . ".csv";
       $saveTarget = _save_dir . $filename;
       $handle = fopen ($saveTarget, "w");

       // header ����
       if ( $this->header )
          fwrite($handle, $this->header . "\n");

       // ����� ����
       $start_index = $this->start_index ? $this->start_index : 0;
       for ( $i = $start_index; $i <= $num_rows; $i++ )
       {
            switch ( $this->type)
            {
               // excel�� ó��
               case "xls": 
                  $j = $i + 1; // excel reader�� ������ 1����
                  $data = $datas->sheets[0]['cells'][$j];
                  $buffer = $this->parse_data ( $data, $i );
               break;
               case "tab":
                  $data = $datas[$i];

//echo "1 . data->" . $datas[0] . "<br>";
//echo "--------------------------<br>";
//echo "2 . data->" . $datas[1] . "<br>";

                  $data = split ( "\t", $data );
                  $buffer = $this->parse_data ( $data,$i );
               break;
               case "csv":
                  $data = $datas[$i];
                  $data = split ( ",", $data );
                  $buffer = $this->parse_data ( $data, $i );
               break;
            }

            ///////////////////////////////////////o /
            // ���� �������� ������
            if ( $buffer )
               fwrite($handle, $buffer . "\n");
       }

       // file handle close
       fclose($handle);
   }

   // order_subid�� �������� ����� �־�� �� 
   function parse_data ( $data , $no)
   {
      $order_id = $data[$this->order_id];
      $order_subid = 1;

      // ��������� �����´�.
      $this->get_transinfo ( $order_id, $order_subid, &$trans_corp, &$trans_no );

      $column_count = count ( $data );
      $end_index = $column_count;
      $start_index = 0;

      if ( $this->type == "xls" )
      {
         $start_index = 1;
         $end_index = $column_count + 1;
      }

      $rep = array(",", "\n", "\r");

      // same ���� diff���� Ȯ�� ��     
      if ( $this->data_type == "diff" )
      {
          $start_index = 0;
          $end_index = count( $this->data_format );

          if ( $this->type == "xls" )
             $end_index++;

          // ���� ���
          for ( $i = $start_index; $i < $end_index; $i++ )
          {
            // �Ϸ� ��ȣ�� ���� ��찡 ���� gseshop
            if ( $this->data_format[$i] == "No")
               $str .= $no;
            else if ( $this->data_format[$i] == "trans_no")
               $str .= $trans_no;
            else if ( $this->data_format[$i] == "trans_corp")
               $str .= $trans_corp;
            else if ( $this->data_format[$i] == "check")
               $str .= "v";
            else if ( $i == $this->order_id)
            {
               $str .= $data[$i];

               // order id�� ���� ���� return
               if ( !$data[$i] ) return 0;
            }
            else
               $str .= str_replace( $rep,"",$data[$this->data_format[$i]] );

            if ( $i != $end_index - 2 )
               $str .= ",";
         }        
      } 
      else
      {
         if ( $this->type == "xls" )
            $end_index++;

         // ���� ���
         // ������ 1����
         for ( $i = $start_index; $i < $end_index; $i++ )
         {
            if ( $i == $this->trans_no )
               $str .= $trans_no;
            else if ( $i == $this->trans_corp )
               $str .= $trans_corp;
            else if ( $i == $this->order_id)
            {
               $str .= $data[$i];
               
               // order id�� ���� ���� return
               if ( !$data[$i] ) return 0;
            }
            else 
            {
               $str .= str_replace( $rep,"",$data[$i] );
            }

            if ( $i != $end_index - 1)
               $str .= ",";
         }        
      }

      return $str;
   } 


   ///////////////////////////////////////////////////
   // �ù��� �����ȣ ������
   // date: 2005.9.5
   function get_transinfo ( $order_id, $order_subid, &$trans_corp, &$trans_no )
   {
      global $connect, $shop_id;
 
      $query = "select a.trans_no, a.trans_corp, b.trans_corp as trans_name
                  from orders a, trans_info b
                 where a.trans_corp = b.id
                   and a.order_id='$order_id' 
                   and a.order_subid='$order_subid'";

      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $trans_name = $data[trans_name];	// ��۾�ü �̸�
      $trans_corp = $data[trans_corp];	// ��۾�ü ��ȣ
      $trans_no = $data[trans_no];	// ���� ��ȣ

      ////////////////////////////////////////////////////////
      // �����ȣ�� �������� ���ķ����� ó������ ���� 
      if ( !$trans_no && $this->debug == "off" )
         return;

      //////////////////////////////////////////////////////
      // code�� �ִ� ��ü�� ���� code�� �����´�.
      $query = "select code from trans_shop where shop_id = '$shop_id' and trans_corp = '$trans_corp'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      // return���� trans_corp�� �Ǹ�ó �ڵ� Ȥ�� �ù�� ���� �ѱ��
      $trans_corp = $data[code] ? $data[code] : $trans_name;

      if ( $this->debug == "on" )
      {
         $trans_corp = "�����ù�";
         $trans_no = "123-123-123";
      }

   }


///////////////////////////////////////////////////////////////////////////////////////////////////
   function excel_read ( $excel_file, $excel_file_name , &$num_rows)
   {

      if ($excel_file)
      {
         $file_params = pathinfo($excel_file_name);
         $file_ext = strtoupper($file_params["extension"]);
         if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT") 
         {
            fatal("�߸��� ���������Դϴ�. ���������� ���������� (.xls | .csv | .txt)�Դϴ�.");
         }
      }

      if ($excel_file == '') fatal("No file uploaded");

      $data = new Spreadsheet_Excel_Reader();
      $data->setOutputEncoding('CP949');
      $data->read($excel_file);
      $num_rows = $data->sheets[0]['numRows'];     
      return $data;
   }

   ////////////////////////////////////////////////////
   // download
   function download()
   {
      global $saveTarget;

      if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
      } else {
          echo "can not open it ";
      }
      
      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);
      unlink($saveTarget);

      exit; 
   }

    function get_order_download_transaction( &$total_rows )
    {
       global $connect, $page;
       global $type, $string;

       $line_per_page = _line_per_page;

       if ( !$page ) $page = 1;
       $starter = ( $page - 1 ) * $line_per_page;

       $query_cnt = "select count(*) cnt ";
       $query = "select * ";
       $option = " from transaction 
                  where template = 'D900'";

       $limit = " order by no desc limit $starter, $line_per_page";

       ///////////////////////////////////////////////
       // total count
       $result = mysql_query ( $query_cnt . $option, $connect );
       $data = mysql_fetch_array ( $result );       
       $total_rows = $data[cnt];

//echo $query . $option . $limit;

       ///////////////////////////////////////////////
       // result
       $result = mysql_query ( $query . $option . $limit, $connect );
       return $result;
    }

}

?>
