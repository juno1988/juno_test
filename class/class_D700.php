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
         $result = $this->get_order_list( &$total_rows ); // 송장 입력일 기준으로 검색

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
   // 주문 정보 query
   function search()
   {
      global $search_date, $order_cs;

      ///////////////////////////////////////////////////////////
      // query data 
      $limit_option = 0;

      /////////////////////////////////////////////////
      // 20개만 출력 
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
      $result_order = class_D::get_order_list( &$total_rows , 1 , $search_date, $no_cancel, $groupby_transno ); // 송장 입력일 기준으로 검색

      ////////////////////////////////////////
      // writting datas to file
//      $buf = "주문번호\t송장번호\t판매처\t수령자\t상품명\t사입처상품명\t우편번호\t주소\t전화\t전화2\t비고";
//      fwrite($handle, $buf); 

      while ( $data = mysql_fetch_array ( $result_order ) )
      {
         $this->get_product_name2($data[product_id], &$product_name, &$brand );
         $buffer = "$data[order_id]\t \t$data[shop_name]\t$product_name\t$brand\t" . class_D::get_product_option( $data[product_id] ) . "\t$data[recv_name]\t";

         $buffer .= "$data[recv_zip]\t";
         $buffer .= "$data[recv_address]\t$data[recv_tel]\t$data[recv_mobile]\t$data[memo] $data[message] \r\n";
         fwrite($handle, $buffer); 
      }

      // file 삭제
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
          "seq"		        => "거래번호",
	  "order_name"		=> "주문자",
	  "order_mobile"	=> "연락처",  // C
          "recv_name"		=> "수취인",  // D
          "recv_mobile"		=> "수취인연락처", // E
          "recv_address"	=> "주소",    // F
          "recv_zip"		=> "우편번호",
	  "product_name"	=> "상품명",
          "options"		=> "옵션",
	  "qty"			=> "수량",
	  "shop_price"		=> "판매가",
	  "memo"		=> "요구사항",
	  "trans_no"		=> "송장번호",
        );
 
      }
      else
      {
        // download format에 대한 정보를 가져온다
        $download_items = array(
          "order_id"		=> "주문번호",
          "status"		=> "상태",
          "order_cs"		=> "CS 상태",
	  "trans_name"		=> "택배사",
	  "trans_no"		=> "운송장번호",
          "trans_date_pos"	=> "배송일",
          "collect_date"        => "발주일",
          "collect_time"        => "발주시간",
          "order_date"		=> "주문일",
          "order_time"		=> "주문시간",
          "trans_who"		=> "배송구분",
          "product_id"		=> "상품아이디",
          "shop_product_id"	=> "원상품아이디",
          "product_name" 	=> "상품명",
          "brand" 		=> "사입처 상품명",
          "options"		=> "선택사항",
          "supply_id"		=> "공급처",
	  "memo"		=> "메모",	
	  "x"			=> "빈칸",
          "qty"			=> "판매개수",
          "shop_name"		=> "판매처",
          "order_name"		=> "주문자",
          "recv_name"		=> "수령자",
          "recv_tel"		=> "수령자전화",
          "recv_mobile"		=> "수령자핸드폰",
          "recv_address"	=> "배송지주소",
          "message"		=> "배송메시지",
          "supply_price"	=> "공급가",
          "shop_price"		=> "판매가격",
          "org_price"		=> "원가",
          "amount"		=> "총합",
          "pre_paid"		=> "선결제",
          "trans_price"		=> "배송비",
          "seq"		        => "거래번호",
        );
   }


      //////////////////////////////////////////////
      // step 1.전체 출력 
      global $trans_no_only;
      if ( !$trans_no_only)
      {
          //////////////////////////////////////////////
          //
          // 묶음 상품 출력
          // 2006.11.27 - jk.ryu
          $result = $this->get_order_list( &$total_rows , 1, -1); 
          $this->write_excel ( $worksheet, $result, $download_items, $rows );
      }
      else
      {
          //////////////////////////////////////////////
          //
          // 묶음 상품 출력
          // 2006.11.27 - jk.ryu

          $result = $this->get_order_list( &$total_rows , 1, 2); 
          $this->write_excel ( $worksheet, $result, $download_items, $rows );
      }


      // Let's send the file
      $workbook->close();

   }
  
   /////////////////////////////////////////////////////// 
   // excel에 write 함
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
               // alt=" "의 문구만 출력해야 함
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
              return  "합포";
           break;
           case "supply_id":
              return  $this->get_supply_name2 ( $data[supply_id] );
           break;
           case "enable_sale":
              return   $data[enable_sale] ? "판매가능" : "판매불가";
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
                $temp = $data[qty] . "개-" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = str_replace( array ("=","\r", "\n", "\r\n","\t" ), "", $temp );
                return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           case "family_product":
              if ( $data[pack] )
                return $this->get_aju_pack( $data[seq] ) ;
              else
              {
                $temp = $data[qty] . "개-" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                return $this->pack_string( $temp, "``$", 50 );
              }
           break;
           case "options":  // 옵션 사항
              return $data[options];
           break;
           case "box":
              return "1";
           break;
           case "deliv_who":
              if ( $data[trans_who] == "선불" )
                 return "신용";
              else
                 return "착불";
               break;
           case "deliv_price":
              return $_SESSION[BASE_TRANS_PRICE];
               break;
           case "ds_qty":
              return "1";
               break;
           case "qty_product_name": // 수량 + 품목
              if ( $data[pack] )
	      {
                 $str_buffer =  $this->get_pack_memo( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $temp = str_replace( array("=","\r","\t","\n","\r\n"), "", stripslashes( strip_tags( "$data[qty]개-" . $this->get_product_name($data[product_id]) . "$data[options]" . $data[memo])));
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
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_pack_memo( $pack )
   {
      global $connect;
      
      $limit = 70; 
      $query = "select product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         if ( $str != "" ) $str .= "|";
       
         $str .= strip_tags( str_replace( array(" ","\n","\r\n"), "", "$data[qty]개-" . $data[product_name] . "$data[options]" . $data[memo]));

      }

      return $str;
   }

   /////////////////////////////////////////////
   // download format의 설정값을 가져온다
   function get_format()
   {
      global $connect;

      $query = "select * from set_format order by order_num";
      $result = mysql_query ( $query , $connect );

      return $result;
   }

    ///////////////////////////////////////////////////////////
    // limit_option 이 0 일 경우는 전체 출력 주로 download받을때 사용
    // 검색 기준일이 주문일일 경우 : 
    // 검색 기준일이 송장 입력일
    // pack=0: 합포 검색 안 함
    // pack=1: 합포만 검색 함
    // pack=2: 송장 번호별 출력
    // packed =0: 일반 상품 출력 
    // packed =1: 묶음 상품 출력 
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
        $supply_code = $_REQUEST["supply_code"];// 공급처
        $supply_id   = $_REQUEST["supply_id"];// 공급처
        $shop_id     = $_REQUEST["shop_id"];        // 판매처

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
        // shop_id 가 있는 경우
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        ///////////////////////////////////////////
        // supply_code 가 있을 경우
        if ( $supply_id)
           $options .= " and a.supply_id = '$supply_id'";

        //////////////////////////////
        // trans_who가 있는 경우
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        ///////////////////////////////////////////
        // 정상, 교환, 맞교환 
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

	// 교환 배송만 보고자 할 경우
	if ( $change_only )
		$options .= " and order_id like ('C%') ";

        if ( $order_cs >= 0 )
              $options .= " and a.order_cs = '$order_cs'";

        // 묶음 상품 체크
//        if ( $packed == 0 )
//           $options .= " and a.packed is null ";		// 합포가 아닌 넘들만 검색

        //////////////////////////////////////////
        // pack check      
        if ( $pack == 0 )
           $options .= " and a.pack is null ";		// 합포가 아닌 넘들만 검색
        else if ( $pack == 1 )
           $options .= " and a.pack = seq ";	        // 합포인 케이스만 검색
        else if ( $pack == 2 )
           $options .= " group by trans_no";	        // 

        // download_date에 날짜가 있으면 download안됨
        /*
        if ( !$confirm )
           $options .= " and a.download_date is NULL";   // 확인 전
        else
           $options .= " and a.download_date is not NULL"; // 확인 후
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
	// total count 가져오기
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
