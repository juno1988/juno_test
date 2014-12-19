<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_E.php";
require "ExcelReader/reader.php";
require "ExcelParserPro/excelparser.php";
//require "lib/ez_trans_lib.php";

class class_DO00 extends class_top 
{
   var $g_order_id;
   
   function DO00()
   {
      global $template;
      $line_per_page = _line_per_page;

      $link_url = "?" . $this->build_link_url();
      $result = $this->count_list();

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
 
   function DO01()
   {
      global $template;

      $link_url = "?" . $this->build_link_url();

      $result = $this->trans_today2( &$total_rows ); 

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    ///////////////////////////////////////////////////////////
    // FILE UPLOAD
    // date: 2005.10.31 modified by sy.hwang
    function upload()
    {
	global $connect, $shop_id, $admin_file, $admin_file_name, $trans_corp, $default_format;

	$excel_file = $_FILES['admin_file'];
	if ($excel_file)
	{
	    $file_params = pathinfo($_FILES['admin_file']['name']);
	    $file_ext = strtoupper($file_params["extension"]);
	    if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT") 
	    {
		fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
	    }

	    $upload_dir = _upload_dir;	// lib_common define
	    $upload_file = "송장-" . date("YmdHis")."_".$_FILES['admin_file']['name'];
	    if (!move_uploaded_file($_FILES['admin_file']['tmp_name'], $upload_dir.$upload_file))
	    {
		fatal("file upload failed");
	    }
	    $excel_file = $upload_dir.$upload_file;
	}

	if ($excel_file == '') fatal("No file uploaded");

        ////////////////////////////////////////////
        // excel file upload
        // cj 직택배 file을 읽어들인다
	$data = new Spreadsheet_Excel_Reader();
        $data->setOutputEncoding('CP949');
	$data->read($excel_file);
	$num_rows = $data->sheets[0]['numRows'];

	//////////////////////////////////////////
	// Using ExcelParserPro
	$exc = new ExcelFileParser("debug.log", ABC_ERROR);
	$res = $exc->ParseFromFile($excel_file);
	$ws = $exc->worksheet['data'][0];

	    if ($num_rows)
	        mysql_query("truncate table trans_temp", $connect) or die(mysql_error());

	    for ($i = 1; $i <= $num_rows; $i++)
	    {
        	$trans_no = $this->parse_excel_ex($exc, $ws, $i, 18);   // S
        	$order_id = $this->parse_excel_ex($exc, $ws, $i, 5);	// F

                if ( $i == 1 ) continue;

                $query = "insert into trans_temp set 
                                      seq = '$i',
	  		              order_id = '$order_id',
	  	                      trans_corp = '$trans_corp', 
		                      trans_no='$trans_no'";
	        @mysql_query ($query, $connect);

		$str = " $i / $num_rows 번 데이터 처리중";
		echo "<script language=javascript> 
			  show_waiting() 
			  show_txt ( '$str' );
		       </script>";
		flush();
	     }

            $this->redirect( "?template=DO01" );
	    return;

      $this->redirect( "?template=DO01" );
      exit;
    }

    ////////////////////////////////////
    // USING ExcelParserPro 4.4
    function uc2html($str) {
	$ret = '';
	for( $i=0; $i<strlen($str)/2; $i++ ) {
		$charcode = ord($str[$i*2])+256*ord($str[$i*2+1]);
		$ret .= '&#'.$charcode;
	}
	return $ret;
    }

    ////////////////////////////////////
    // USING ExcelParserPro 4.4
    // 첫번째 CELL : (0,0)부터 시작
    function parse_excel_ex($exc, $ws, $row, $col)
    {
	$data = $ws['cell'][$row-1][$col];

	if ($data['type'] == 0)
	{
	    $ind = $data['data'];
	    if ($exc->sst['unicode'][$ind])
	        $str = $this->uc2html($exc->sst['data'][$ind]);
	    else
	        $str = $exc->sst['data'][$ind];
	    return $str;
	}
	else if ($data['type'] == 3)
	{
	    list($month,$day,$year) = explode(".", $data[data]);
	    return "20".$year."-".$month."-".$day;
	}
	else
	    return $data[data];
    }

   //////////////////////////////////////////////////////////
   // 송장 업로드 확인 후 실제로 입력
   // date: 2005.9.1
   function upload_confirm()
   {
      global $connect, $status;

      $transaction = $this->begin("송장입력");

      $query = "select * from trans_temp";
      $result = mysql_query ( $query, $connect );
      $max = mysql_num_rows( $result );
 
      $i = 0; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         $i++;
         $query = "update orders 
                      set trans_corp = '$data[trans_corp]',
                          trans_no = '$data[trans_no]',
                          trans_date = Now(),";

         if ( $status )
	     $query .= " status = " . _trans_confirm . ", trans_date_pos=Now()";
         else
	     $query .= " status = 7 ";

         $query .= " where order_id='$data[order_id]'
                       and ( trans_no = 0 or trans_no is null )";

//debug ( $query );
//echo $query;
//exit;
         mysql_query ( $query, $connect );                  

         // add by sy.hwang 2005.10.28
         /* blocked by jk.ryu 2006.0.18
         $query = "update orders 
                      set trans_corp = '$data[trans_corp]',
                          trans_no = '$data[trans_no]',
                          trans_date = Now(),";

         if ( $_SESSION[LOGIN_LEVEL] )
	     $query .= " status = 7 ";
	 else
	     $query .= " status = " . _trans_confirm . ", trans_date_pos=Now()";
	 
 	 //$query .= " where pack = '-$data[seq]' or pack = '$data[seq]' and trans_no != ''";
 	 $query .= " where pack = '-$data[seq]' or pack = '$data[seq]' 
                       and ( trans_no is null or trans_no= 0)";

         mysql_query ( $query, $connect );                  
         */

         $str = " $i / $max 번 데이터 처리중";
         echo "<script language=javascript> 
                  show_waiting() 
                  show_txt ( '$str' );
               </script>";
         flush();
      }
      $this->end( $transaction );

      $this->jsAlert( $i . "개의 Data가 입력되었습니다");
      $this->redirect ( "?template=DO00" );
   }

   // 송장 미입력 count
   function count_list()
   {
      global $connect;
      $query = "select order_date, date_format(collect_date,'%Y-%m-%d') collect_date, count(*) cnt
                from orders 
                where trans_no is null 
                  and status = 1 
                  and order_cs in ( 0, 5, 11 )";
       
      if ( !$_SESSION[LOGIN_LEVEL] ) 
          $query .= " and supply_id = '". $_SESSION[LOGIN_CODE] . "'";      

      $query .= " group by order_date, date_format(collect_date,'%Y-%m-%d')";

//echo $query;

      $result = mysql_query ( $query, $connect );
      return $result;
   }

   //////////////////////////////////////////////////////
   // 금일 송장입력한 결과
   function trans_today2 ( &$total_rows )
   {
      global $connect;

      $query_cnt = "select count(*) as cnt from trans_temp";
      $result = mysql_query ( $query_cnt, $connect);
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];
 
      ////////////////////////////////////////////////////////////////
      $query = "select * from trans_temp limit 0, 5";
 
      $result = mysql_query ( $query, $connect );
      
      return $result;
   }


   //////////////////////////////////////////////////////
   // 금일 송장입력한 결과
   function trans_today ( &$total_rows )
   {
      global $connect;
      global $page;

      $line_per_page = _line_per_page;
      $page = $_REQUEST["page"];
      $today= date('Ymd', strtotime("now"));

      if ( !$page ) $page = 1;
      $start = ( $page - 1 ) * 20;

      $query_cnt = "select count(*) as cnt from orders where trans_date > '$today'";
      $result = mysql_query ( $query_cnt, $connect);
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];
 
      ////////////////////////////////////////////////////////////////
      $query = "select *, date_format( trans_date, '%Y-%m-%d') trans_date 
                  from orders , shopinfo
                 where orders.shop_id = shopinfo.shop_id
                   and trans_date > '$today'
                 order by order_date desc
                 limit $start, $line_per_page";
 
      $result = mysql_query ( $query, $connect );
      
      return $result;
   }


   function write ( $datas, $num_rows , &$filename)
   {
       global $shop_id;

       // 결과를 write할 새로운 data를 open
       $filename = $_SESSION["LOGIN_ID"] . $shop_id . ".csv";
       $saveTarget = _save_dir . $filename;
       $handle = fopen ($saveTarget, "w");

       // 결과를 저장
       $start_index = $this->start_index ? $this->start_index : 0;
       for ( $i = $start_index; $i <= $num_rows; $i++ )
       {
            switch ( $this->type)
            {
               // excel의 처리
               case "xls": 
                  $j = $i + 1; // excel reader의 시작은 1부터
                  $data = $datas->sheets[0]['cells'][$j];
                  $buffer = $this->parse_data ( $data, $i );
               break;
               case "tab":
                  $data = $datas[$i];
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
            // 값이 있을때만 저장함
            if ( $buffer )
               fwrite($handle, $buffer . "\n");
       }

       // file handle close
       fclose($handle);
   }

   // order_subid를 가져오는 계산이 있어야 함 
   function parse_data ( $data , $no)
   {
      $order_id = $data[$this->order_id];
      $order_subid = 1;

      // 배송정보를 가져온다.
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

      // same 인지 diff인지 확인 함     
      if ( $this->data_type == "diff" )
      {
          $start_index = 0;
          $end_index = count( $this->data_format );

          if ( $this->type == "xls" )
             $end_index++;

          // 같은 경우
          for ( $i = $start_index; $i < $end_index; $i++ )
          {
               // 일련 번호가 오는 경우가 있음 gseshop
            if ( $this->data_format[$i] == "No")
               $str .= $no;
            else if ( $this->data_format[$i] == "trans_no")
               $str .= $trans_no;
            else if ( $this->data_format[$i] == "trans_corp")
               $str .= $trans_corp;
            else if ( $this->data_format[$i] == "check")
               $str .= "v";
            else if ( $i == $this->order_id)
               $str .= "'" . $data[$i];
            else
               $str .= str_replace( $rep,"",$data[$this->data_format[$i]] );

            if ( $i != $end_index - 1 )
               $str .= ",";
         }        
      } 
      else
      {
         if ( $this->type == "xls" )
            $end_index++;

         // 같은 경우
         // 기준은 1부터
         for ( $i = $start_index; $i < $end_index; $i++ )
         {
            if ( $i == $this->trans_no )
               $str .= $trans_no;
            else if ( $i == $this->trans_corp )
               $str .= $trans_corp;
            else if ( $i == $this->order_id)
               $str .= "'" . $data[$i];
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

   function get_transinfo ( $order_id, $order_subid, &$trans_corp, &$trans_no )
   {
      global $connect;
      $query = "select trans_no, trans_corp from orders where order_id='$order_id' and  order_subid='$order_subid'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $trans_corp = $data[trans_corp];
      $trans_no = $data[trans_no];

      if ( $this->debug == "on" )
      {
         $trans_corp = "토인택배";
         $trans_no = "123-123-123";
      }

   }


   //////////////////////////////////////////////////////////////////////

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


}

?>
