<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_D700
//

class class_D700 extends class_top 
{

   function D700()
   {
      global $template, $start_date, $end_date, $order_cs;

      $line_per_page = _line_per_page;

      $link_url = "?" . $this->build_link_url();

      echo "<script>show_waiting()</script>";
      flush();

      if ( $_REQUEST["page"] )
         $result = $this->get_order_list( &$total_rows ); // ���� �Է��� �������� �˻�

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
      echo "<script>hide_waiting()</script>";
   }

   function download_confirm ()
   {
      $link_url = "?" . $this->build_link_url();

      $result = class_D::confirm_order(); 
      $this->redirect ( $link_url );
      exit;
   }

   ////////////////////////////////////////
   // �ֹ� ���� query
   function search()
   {
      global $search_date, $order_cs;

      ///////////////////////////////////////////////////////////
      // query data 
      $limit_option = 0;

      /////////////////////////////////////////////////
      // 20���� ��� 
      $limit_option = 0;
      $result_order = $this->get_order_list( &$total_rows , $limit_option, -1); 

      global $template, $start_date, $end_date;
      $line_per_page = _line_per_page;

      $link_url = "?action=search&" . $this->build_link_url();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function download()
   {
      global $connect, $saveTarget, $search_date;

      if ( !$search_date )
          $search_date = "trans_date";

echo "download start";
exit;

      ///////////////////////////////////
      // open file to get file handle 
      $handle = fopen ($saveTarget, "w");
      $no_cancel = 0;
      $groupby_transno = 1;
      $result_order = class_D::get_order_list( &$total_rows , 1 , $search_date, $no_cancel, $groupby_transno ); // ���� �Է��� �������� �˻�

      ////////////////////////////////////////
      // writting datas to file
//      $buf = "�ֹ���ȣ\t�����ȣ\t�Ǹ�ó\t������\t��ǰ��\t����ó��ǰ��\t�����ȣ\t�ּ�\t��ȭ\t��ȭ2\t���";
//      fwrite($handle, $buf); 

      while ( $data = mysql_fetch_array ( $result_order ) )
      {
         $this->get_product_name2($data[product_id], &$product_name, &$brand );
         $buffer = "$data[order_id]\t \t$data[shop_name]\t$product_name\t$brand\t" . class_D::get_product_option( $data[product_id] ) . "\t$data[recv_name]\t";

         $buffer .= "$data[recv_zip]\t";
         $buffer .= "$data[recv_address]\t$data[recv_tel]\t$data[recv_mobile]\t$data[memo] $data[message] \r\n";
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

   ////////////////////////////////////////
   // excel download
   function download2()
   {
      global $supply_id;

      require_once 'Spreadsheet/Excel/Writer.php';

      global $connect, $saveTarget, $filename, $search_date;

      // Creating a workbook
      $workbook = new Spreadsheet_Excel_Writer();

      // sending HTTP headers
      $workbook->send( $filename . ".xls" );

      // Creating a worksheet
      $worksheet =& $workbook->addWorksheet('Sheet1');

      if ( _DOMAIN_ == "unicon77" && $supply_id == "20008" )
      { 
        $download_items = array(
          "seq"		        => "�ŷ���ȣ",
	  "order_name"		=> "�ֹ���",
	  "order_mobile"	=> "����ó",  // C
          "recv_name"		=> "������",  // D
          "recv_mobile"		=> "�����ο���ó", // E
          "recv_address"	=> "�ּ�",    // F
          "recv_zip"		=> "�����ȣ",
	  "product_name"	=> "��ǰ��",
          "options"		=> "�ɼ�",
	  "qty"			=> "����",
	  "shop_price"		=> "�ǸŰ�",
	  "memo"		=> "�䱸����",
	  "trans_no"		=> "�����ȣ",
        );
 
      }
      else
      {
        // download format�� ���� ������ �����´�
        $download_items = array(
          "order_id"		=> "�ֹ���ȣ",
          "status"		=> "����",
          "order_cs"		=> "CS ����",
	  "trans_name"		=> "�ù��",
	  "trans_no"		=> "������ȣ",
          "trans_date_pos"	=> "�����",
          "collect_date"        => "������",
          "collect_time"        => "���ֽð�",
          "order_date"		=> "�ֹ���",
          "order_time"		=> "�ֹ��ð�",
          "trans_who"		=> "��۱���",
          "product_id"		=> "��ǰ���̵�",
          "shop_product_id"	=> "����ǰ���̵�",
          "product_name" 	=> "��ǰ��",
          "brand" 		=> "����ó ��ǰ��",
          "options"		=> "���û���",
          "supply_id"		=> "����ó",
	  "memo"		=> "�޸�",	
	  "x"			=> "��ĭ",
          "qty"			=> "�ǸŰ���",
          "shop_name"		=> "�Ǹ�ó",
          "order_name"		=> "�ֹ���",
          "recv_name"		=> "������",
          "recv_tel"		=> "��������ȭ",
          "recv_mobile"		=> "�������ڵ���",
          "recv_address"	=> "������ּ�",
          "message"		=> "��۸޽���",
          "supply_price"	=> "���ް�",
          "shop_price"		=> "�ǸŰ���",
          "org_price"		=> "����",
          "amount"		=> "����",
          "pre_paid"		=> "������",
          "trans_price"		=> "��ۺ�",
          "seq"		        => "�ŷ���ȣ",
        );
   }


      //////////////////////////////////////////////
      // step 1.��ü ��� 
      global $trans_no_only;
      if ( !$trans_no_only)
      {
          //////////////////////////////////////////////
          //
          // ���� ��ǰ ���
          // 2006.11.27 - jk.ryu
          $result = $this->get_order_list( &$total_rows , 1, -1); 
          $this->write_excel ( $worksheet, $result, $download_items, $rows );
      }
      else
      {
          //////////////////////////////////////////////
          //
          // ���� ��ǰ ���
          // 2006.11.27 - jk.ryu

          $result = $this->get_order_list( &$total_rows , 1, 2); 
          $this->write_excel ( $worksheet, $result, $download_items, $rows );
      }


      // Let's send the file
      $workbook->close();

   }
  
   /////////////////////////////////////////////////////// 
   // excel�� write ��
   // date: 2005.10.20
   function write_excel ( $worksheet, $result, $download_items, $rows = 0 )
   {
      $i = $rows ? $rows : 0;
      $j = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // header
         if ( $i == 0 && $header != -99 )
         {
            $j = 0;
            foreach ( $download_items as $key=>$value )
            {
               $worksheet->write($i, $j, $value );
               $j++;
            }
            $i++;
         }

         // increase column
         $j = 0;
         foreach ( $download_items as $key=>$value )
         {
            $worksheet->write($i, $j, $this->get_data( $data, $key, $i ) );
            $j++;
         }
         // increase row
         $i++;
      }
   }

   function get_data ( $data, $key, $i )
   {
      switch ( $key )
      {
           // case "status":
               // alt=" "�� ������ ����ؾ� ��
               // eregi ([a-zA-Z]+ 
           //    break;
       	   case "brand":
               class_D::get_product_name2( $data[product_id], &$product_name, &$brand );
		return $brand;
               break;
       	   case "product_name":
               return $this->get_product_name( $data[product_id] );
               break;
           case "options":
               if ( $_SESSION[STOCK_MANAGE_USE] )
                 return class_D::get_product_option( $data[product_id] );
               else
                 return $data[options];
               break;
           case "x":
              return " ";
           break;
           case "shop_recv_name":
              return "$data[shop_name] / $data[recv_name]";
           break;
           case "order_type":
              return  "����";
           break;
           case "supply_id":
              return  $this->get_supply_name2 ( $data[supply_id] );
           break;
           case "enable_sale":
              return   $data[enable_sale] ? "�ǸŰ���" : "�ǸźҰ�";
           break;
           case "memo":
                 return str_replace( array("=","\r", "\n", "\r\n","\t" ), "", $data[qty] . $data[product_name] );
           break;
           case "message":
                 return str_replace( array("=","\r", "\n", "\r\n","\t" ), "", $data[memo] . $data[message] );
           break;
           case "aju_memo":
              if ( $data[pack] )
                return $this->get_aju_pack( $data[seq] ) ;
              else
              {
                $temp = $data[qty] . "��-" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = str_replace( array ("=","\r", "\n", "\r\n","\t" ), "", $temp );
                return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           case "family_product":
              if ( $data[pack] )
                return $this->get_aju_pack( $data[seq] ) ;
              else
              {
                $temp = $data[qty] . "��-" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                return $this->pack_string( $temp, "``$", 50 );
              }
           break;
           case "options":  // �ɼ� ����
              return $data[options];
           break;
           case "box":
              return "1";
           break;
           case "deliv_who":
              if ( $data[trans_who] == "����" )
                 return "�ſ�";
              else
                 return "����";
               break;
           case "deliv_price":
              return $_SESSION[BASE_TRANS_PRICE];
               break;
           case "ds_qty":
              return "1";
               break;
           case "qty_product_name": // ���� + ǰ��
              if ( $data[pack] )
	      {
                 $str_buffer =  $this->get_pack_memo( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $temp = str_replace( array("=","\r","\t","\n","\r\n"), "", stripslashes( strip_tags( "$data[qty]��-" . $this->get_product_name($data[product_id]) . "$data[options]" . $data[memo])));
                 return $this->pack_string( $temp );
              }
           break;
           case "org_price":
               global $connect;
               $query = "select org_price from products where product_id='$data[product_id]'";
               $result = mysql_query ( $query, $connect );
               $data = mysql_fetch_array ( $result );
               return $data[org_price];
           break;
           case "trans_name":
              require_once "class_E.php";
              return class_E::get_trans_name($data[trans_corp]);
              break;
           case "status":
              return $this->get_order_status2($data[status]);
              break;
           case "order_cs":
              return $this->get_order_cs2($data[order_cs]);
              break;
           case "shop_name":
              return class_C::get_shop_name($data[shop_id]);
              break;
           default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("=","\r", "\n", "\r\n","\t" ), " ", $val );
           break; 
      }
   }

   //////////////////////////////////////////////
   // pack memo���
   // name: memo����
   // name: memo����
   function get_pack_memo( $pack )
   {
      global $connect;
      
      $limit = 70; 
      $query = "select product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // �������� �����ϴ� ���
         if ( $str != "" ) $str .= "|";
       
         $str .= strip_tags( str_replace( array(" ","\n","\r\n"), "", "$data[qty]��-" . $data[product_name] . "$data[options]" . $data[memo]));

      }

      return $str;
   }

   /////////////////////////////////////////////
   // download format�� �������� �����´�
   function get_format()
   {
      global $connect;

      $query = "select * from set_format order by order_num";
      $result = mysql_query ( $query , $connect );

      return $result;
   }

    ///////////////////////////////////////////////////////////
    // limit_option �� 0 �� ���� ��ü ��� �ַ� download������ ���
    // �˻� �������� �ֹ����� ��� : 
    // �˻� �������� ���� �Է���
    // pack=0: ���� �˻� �� ��
    // pack=1: ������ �˻� ��
    // pack=2: ���� ��ȣ�� ���
    // packed =0: �Ϲ� ��ǰ ��� 
    // packed =1: ���� ��ǰ ��� 
    function get_order_list( &$total_rows , $limit_option=0, $pack =0, $packed=0)
    {
	global $connect, $confirm, $trans_who, $status, $search_date, $order_cs;
	global $change_only;

//echo "order_cs->$order_cs";

        if ( !$search_date )
            $search_date = "trans_date_pos";

	$line_per_page = _line_per_page;
	$keyword     = $_REQUEST["keyword"];
	$page        = $_REQUEST["page"];
	$start_date  = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
	$end_date    = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));
        $supply_code = $_REQUEST["supply_code"];// ����ó
        $supply_id   = $_REQUEST["supply_id"];// ����ó
        $shop_id     = $_REQUEST["shop_id"];        // �Ǹ�ó

	$query = "select * ";
	$query_cnt = "select count(*) cnt ";

	$options = " from orders a 
                    where a.order_id != '' ";

	if ($keyword)
	  $options .= " and (a.order_id = '$keyword' or a.order_name = '$keyword' or a.product_name like '%$keyword%') ";

	if ($start_date)
	  $options .= " and a." . $search_date . ">= '$start_date 00:00:00' ";
	if ($end_date)
	  $options .= " and a." . $search_date . "<= '$end_date 23:59:59' ";

        ///////////////////////////////////////////
        // shop_id �� �ִ� ���
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        ///////////////////////////////////////////
        // supply_code �� ���� ���
        if ( $supply_id)
           $options .= " and a.supply_id = '$supply_id'";

        //////////////////////////////
        // trans_who�� �ִ� ���
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        ///////////////////////////////////////////
        // ����, ��ȯ, �±�ȯ 
        switch ( $status )
        {            
           case "98":
              $options .= " and a.status = 1";
           break;
           case "99":
              $options .= " and a.status in ( 1, 7 )";
           break;
           case "96":
              $options .= " and a.status in ( 7,8 )";
           break;
           case "97":
              $options .= " and a.status in ( 1, 7,8 )";
           break;
           default:
              if ( $status )
              $options .= " and a.status = '$status'";
	   break;
        }

	// ��ȯ ��۸� ������ �� ���
	if ( $change_only )
		$options .= " and order_id like ('C%') ";

        if ( $order_cs >= 0 )
              $options .= " and a.order_cs = '$order_cs'";

        // ���� ��ǰ üũ
//        if ( $packed == 0 )
//           $options .= " and a.packed is null ";		// ������ �ƴ� �ѵ鸸 �˻�

        //////////////////////////////////////////
        // pack check      
        if ( $pack == 0 )
           $options .= " and a.pack is null ";		// ������ �ƴ� �ѵ鸸 �˻�
        else if ( $pack == 1 )
           $options .= " and a.pack = seq ";	        // ������ ���̽��� �˻�
        else if ( $pack == 2 )
           $options .= " group by trans_no";	        // 

        // download_date�� ��¥�� ������ download�ȵ�
        /*
        if ( !$confirm )
           $options .= " and a.download_date is NULL";   // Ȯ�� ��
        else
           $options .= " and a.download_date is not NULL"; // Ȯ�� ��
        */

	$options .= " order by a.seq desc ";

        if ( !$limit_option )
        {
	   $starter = $page ? ($page-1) * $line_per_page : 0;
	   $limit = " limit $starter, $line_per_page";
        }

// echo $query . $options . $limit;
	$result = mysql_query($query . $options . $limit, $connect);

	////////////////////////////////////////////////// 
	// total count ��������
	$list = mysql_fetch_array(mysql_query($query_cnt . $options, $connect));
	$total_rows = $list[cnt];

	return $result;
    }


   function get_product_name( $product_id )
   {
       global $connect;
       $query = "select name from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       return $data[name] . "-"; 
   }

}

?>
