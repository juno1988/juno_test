<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_E.php";

////////////////////////////////
// class name: class_D400
//

class class_D400 extends class_top 
{
   var $g_memo = "";
   var $g_index = "";
   var $g_count = "";
   function D400()
   {
      global $template, $start_date;

      $line_per_page = _line_per_page;

      $link_url = "?" . $this->build_link_url();

      $start_date = $start_date ? $start_date : Date("Y-m-d",strtotime("-3 days"));
      $end_date = $_REQUEST["end_date"];

      
      if ( $_SESSION[LOGIN_LEVEL] == 0 )  // 공급체
         $supply_code = $_SESSION[LOGIN_CODE];
      else // 내부 사용자
         $supply_code = _MASTER_CODE;

      if ( $_REQUEST["page"] )
         $result = $this->get_order_list( &$total_rows ); 

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function D401()
   {
      global $template;

      $result = $this->get_format(); 

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }


   function download_confirm ()
   {
      $link_url = "?" . $this->build_link_url();

      $transaction = $this->begin("주문확인");
      $result = class_D::confirm_order(); 
      $this->end( $transaction );

      $this->redirect ( $link_url );
      exit;
   }

   ////////////////////////////////////////
   // excel download
   function download2()
   {
      global $connect, $saveTarget, $filename, $trans_corp;

      //=====================================================
      // 
      // download format에 대한 정보를 가져온다
      // 
      $result = $this->get_format();
      $download_items = array (); 

      foreach ( $result as $key=>$name )
      {
         $download_items[$key] = $name;
      }

      $handle = fopen ($saveTarget, "w");

      /*****************************************/
      // 합포만 download받는다
      $result = $this->get_order_list( &$total_rows , 1, 1); 

      ////////////////////////////////////////
      // writting datas to file
      $i = 1;
      $header = "false";	// header는 출력되지 않았음

      // header 출력 부분
      // 대한 통운은 header출력하지 않는다.
      // 아주 택배도 header 출력하지 않는다.
      // 트라넷 택배도 header 출력하지 않는다.
      if ( $trans_corp == '30022' 
        or $trans_corp == '30084' 
        or $trans_corp == '30050'	// 아주 택배
        or $trans_corp == '30074'	// 트라넷 택배
      )
      $trans_header = "false";	        // 택배사 헤더 없음

      if ( _DOMAIN_ == "nak21" )
          $trans_header = "true";	        // mantan은 대통 택배사 헤더 없음 - 2007.2.22 - jk.ryu
	
      if ( _DOMAIN_ == "mantan" )
          $trans_header = "false";	        // mantan은 대통 택배사 헤더 없음 - 2007.2.22 - jk.ryu

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $is_pack_disp = "true";	// 합포 부분 출력됐음을 기록

         if ( $trans_header == "false" )
         {
	     $buffer .= "<html><table border=1>";
             $header = "true";	// header가 출력됐음을 기록
         }
         else
         {
  	    if ( $i == 1 )
	    {
	        $buffer .= "<html><table border=1><tr>";
	        foreach ( $download_items as $key=>$value )
	            $buffer .= "<td>" . $value. "</td>";

	        $buffer .= "</tr>\n";
                $header = "true";	// header가 출력됐음을 기록
	    }

         }

	 foreach ( $download_items as $key=>$value )
	 {
	    $buffer .= "<td>";
	    $buffer .= $this->get_data( $data, $key, $i );
	    $buffer .= "</td>";
	 }
  
         if ( $i > 1 ) 
             $result_buffer = "<tr>" . $buffer . "</tr>\n";
	 else
	     $result_buffer = $buffer;

         $i++;

	 fwrite($handle, $result_buffer);
	 $buffer = "";
         $result_buffer = "";
      }

      /////////////////////////////////////////////////////////
      // 합포를 제외한 data를 download받는다
      $result = $this->get_order_list( &$total_rows , 1); 

      ////////////////////////////////////////
      // writting datas to file
      $i = 1;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 합포 출력 안된 경우, header출력

         // 합포 출력 안된 경우, header출력 x
 
	 if ( $i == 1 && $header == "false" )
	 {
	    $buffer .= "<html><table border=1><tr>\n";
	    foreach ( $download_items as $key=>$value )
	       $buffer .= "<td>". $value. "</td>";
	    $buffer .= "</tr>\n";
            $header = "true";	// header가 출력됐음을 기록
	 }
         else
            $i++;

	 foreach ( $download_items as $key=>$value )
	 {
	     $buffer .= "<td bgcolor=fcfcfc>";
	     $buffer .= $this->get_data( $data, $key, $i );
	     $buffer .= "</td>";
	 }

         if ( $i > 1 ) 
             $result_buffer = "<tr>" . $buffer . "</tr>\n";
	 else
	     $result_buffer = $buffer;

         $i++;
	 fwrite($handle, $result_buffer );

	 $buffer = "";
         $result_buffer = "";
      }

      fwrite($handle, $result_buffer . "</table></html>\n" );

      ///////////////////////////////////////
      // file close 
      fclose($handle);

      //////////////////////////////////////
      // 
      // 파일 변환을 해야 할 경우 여기서 해야 함
      //
      $saveTarget2 = $saveTarget . "_";
/*
echo $saveTarget;
echo "<br>";
echo $saveTarget2;
exit;
*/

      $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      exec( $run_module ); 
     
      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=오늘의발주_대박나세요.xls");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
      header("Pragma: public");

      // test
//      $saveTarget2 = $saveTarget;

      if (is_file($saveTarget2)) { 
          $fp = fopen($saveTarget2, "r");   
          fpassthru($fp);  

      } 
      fclose($fp);

      ////////////////////////////////////// 
      // file close and delete it 
      unlink($saveTarget2);
      unlink($saveTarget);

      exit;
/*****************************************./


      /*
      // Creating a workbook
      require_once 'Spreadsheet/Excel/Writer.php';
      $workbook = new Spreadsheet_Excel_Writer();

      // sending HTTP headers
      $workbook->send( $filename . ".xls" );

      // Creating a worksheet
      if ( $trans_corp == "30090" )
          $worksheet =& $workbook->addWorksheet('order');
      else
          $worksheet =& $workbook->addWorksheet('발송확인');

      //while ( $data = mysql_fetch_array ( $result ) )
      // {
      //   $download_items[$data[id]] = $data[name];
      // }

      foreach ( $result as $key=>$name )
      {
         $download_items[$key] = $name;
      }

      //////////////////////////////////////////////
      // step 1. 합포 data send
      $result = $this->get_order_list( &$total_rows , 1, 1); 
      $this->write_excel ( $worksheet, $result, $download_items );
      $rows = $total_rows;

      //////////////////////////////////////////////
      // step 1. 일반 data send
      $result = $this->get_order_list( &$total_rows , 1); 
      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
      */
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
         /*
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
         */
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
      $arr_chars = array("`","=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'","<br>" );

      // myking 예외 조건
      if ( _DOMAIN_ == "myking" )
          if ( $key == "hanjin_product" )
              $key = "hanjin_product2";

//echo $key;

      switch ( $key )
      {	
	   // for eleven
           case "eleven_code":
		// date : 2007.3.20 전부 2로 나오게 해달라 요청 - jk.ryu
		//if ( 
		//$data[shop_id] == 10002 
		//or $data[shop_id] == 10102 
		//or $data[shop_id] == 10165
		//)
		//	return 1;
		//else
			return 2;
           break;
           // mam8872를 위한 부분
           case "empty1":
             if ( _DOMAIN_ == "mam8872" )
             {
               $shop_xp = (int)($data[shop_id]%100);
               if ( $shop_xp == 1 
                 or $shop_xp == 98
		or $data[shop_id] == 10013
		or $data[shop_id] == 10102
		or $data[shop_id] == 10104 
		)
                 return "필스타"; 
	       else if (
		    $data[shop_id] == 10201 or
		    $data[shop_id] == 10202 or
		    $data[shop_id] == 10125
		)
                    return "아이렌"; 
		else 
		{
		    // 2007.11.19 - jk
		    return class_C::get_shop_name($data[shop_id]);
		}
             }
           break;
           case "empty5":
             if ( _DOMAIN_ == "mam8872" )
             {
		 // 2007.11.19 - jk
                 $shop_xp = (int)($data[shop_id]%100);

		//===========================
		// 2007.11.26 10002는 051-895-8872로 변경 요청
	         if (
		    $data[shop_id] == 10201 or
		    $data[shop_id] == 10202 or
		    $data[shop_id] == 10125
		 )
		    return "016-9665-7902";
		else if ( 
		    $data[shop_id] == 10002 
		)
                    return "051-895-8872"; 
		 else
                    return "051-891-3003"; 
             }
           break;
           case "no":
                return $i;
           break;
	   case "pack":
		return $data[pack] ? $data[pack] : $data[seq];
           break;
	   case "pack2":
		return $data[pack] ? $data[pack] : $data[seq];
           break;
	   case "is_pack":
		return $data[pack] ? "합포": "";
           break;

           // yellow cap : 30057
           case "yellow_seq":
		return $data[pack] ? $data[pack] : $data[seq];
           break;

           /////////////////////////////////////////////
           // 삼섬택배 
           case "hth_trans_price":
               if ( $data[trans_who] == "선불" )
                   return 2200;
               else
                   return 2500;
               break;
          case "hth_trans_who":
               if ( $data[trans_who] == "선불" )
                   return "신용";
               else
                   return "착불";
               break;
           
           /////////////////////////////////////////////
           // 아주택배
           case "aju_trans_price":
               if ( $data[trans_who] == "선불" )
               {
                   switch( _DOMAIN_ )
                   {
                     case "dorosi":
                       return 2500;
                     break;
                     case "mambo74":
                     case "femiculine":
                       return 2000;
                     break;
                     default:
                       return 1700;
                   }
               }
               else
                   return 2500;
               break;
           
           /////////////////////////////////////////////
           // cj for kayoung
           case "kayoung_cj_etc":
              if ( $data[pack] )
              {
                // 2번 상품 부터 6번 상품 까지 출력
                return $this->get_kayoung_cj_etc($data[pack]);
              }
              else
              {
                if ( 
                   _DOMAIN_ == "kayoung" or
                   _DOMAIN_ == "color250" or
                   _DOMAIN_ == "seongeun" 
                   )
                  return " \t \t \t \t \t ";

                //if ( _DOMAIN_ == "soocargo"
                //  ) // soocargo 5개
                //  return " \t \t \t \t \t ";
                
                if ( _DOMAIN_ == "ds153" ) // 9개
                  return " \t \t \t \t \t \t \t \t \t";
              }

              break;
           case "kayoung2":
           case "kayoung3":
           case "kayoung4":
           case "kayoung5":
           case "kayoung6":
           case "kayoung7":
           case "kayoung8":
           case "kayoung9":
           case "pass":
              break;

           case "gabang":
             return "가방";
             break;

           /////////////////////////////////////////////
           // tranet
           case "tranet_trans_who2":
               if ( $data[trans_who] == "선불" )
                   return "신용";
               else
                   return "착불";
           break;
           case "tranet_amount":
           case "tranet_trans_who":
               if ( $data[trans_who] == "선불" )
                   return 1;
               else
                   return 2;
           break;
           case "tranet_size":
               if ( $data[trans_who] == "선불" )
                   return 1;
               else
                   return 2;
           break;
           case "collect_date2":
               return str_replace("-","",$data[collect_date] );
           break;
           case "tranet_products2":
              if ( $data[pack] )
                return $this->get_tranet_pack2( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
              }
              return $temp;

           break;
 
           case "tranet_products":
              if ( $data[pack] )
                return $this->get_tranet_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
		{
			if ( _DOMAIN_ == "dmnet" )
                    		$temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]) . "/" . $data[options] . "/" . $data[memo] ) ;
			else
                    		$temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
		}
                
              }
              return $temp;

           break;
           //////////////////////////////////////////////
           // logen
           case "logen_products":
              if ( $data[pack] )
                return $this->get_pack_product_only2( $data[pack], $sep="\t", $str_cnt=60 );
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                {
                  $product_name = "";
                  $product_option = "";
                  $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
                  $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $product_name) . "-" . str_replace( $arr_chars, " ", $product_option ) ;
                }
                else
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $product_name) . "-" . str_replace( $arr_chars, " ", $data[options]) ;
                
              }
              return $temp;
           break;
           case "station":
               if ( _DOMAIN_ == "jyms" )
                   return "1030001";
                   //return "1013032"; // 코드변경 2006.11.23 -jk 미경씨 오쳥
           break;
           case "logen_code1":
		switch ( $data[trans_who] )
                {
                    case "선불": return "l"; break;
		    case "착불": return "l"; break;
                }
           break;
           case "logen_code2":
		switch ( $data[trans_who] )
                {
                    case "선불": return "1"; break;
		    case "착불": return "2"; break;
                }
           break;
           case "hyundae_code":
		switch ( $data[trans_who] )
                {
                    case "선불": return "3"; break;
		    case "착불": return "2"; break;
                }
           break;
           // 고려택배 제주도 체크
           case "jeju_check":
             //echo "$data[recv_zip]";
             if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
		switch ( $data[trans_who] )
                {
                    case "선불": return "03"; break;
		    case "착불": return "02"; break;
                }
             else
               return "";

           break;

           // 제주도는 5000원
           case "jeju_price":

             if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
		return 5000;
             else
                return 2500;

           break;
           //////////////////////////////////////////////
           // 대한 통운 선착불 추가
           case "daehan_amount":
             if ( _DOMAIN_ == "eleven" )
             {
		switch ( $data[trans_who] )
                {
                    case "선불": 
			if ( _DOMAIN_ == "partyparty" )
                            return 2000;
                        else
                            return "1900"; 
                    break;
		    case "착불": return "2500"; break;
                }
             }
	     else if ( _DOMAIN_ == "younggun" )
		switch ( $data[trans_who] )
                {
                    case "선불": return "1700"; break;
		    case "착불": return "2500"; break;
                }
             else
             {
		switch ( $data[trans_who] )
                {
                    case "선불": return "0"; break;
		    case "착불": return "2500"; break;
                }
             }
           break;
           case "daehan_trans_who":
		switch ( $data[trans_who] )
                {
                    case "선불": return "03"; break;
		    case "착불": return "02"; break;
                }
           break;
           case "daehan_trans_who2":
		switch ( $data[trans_who] )
                {
                    case "선불": return "03"; break;
		    case "착불": return "02"; break;
                }
           break;

           //////////////////////////////////////////////
           // femiculine 주소
           case "recv_address2":
                 return $data[recv_address];
           break;

           //////////////////////////////////////////////
           // 우체국
	   case "post_trans_who":
		switch ( $data[trans_who] )
                {
                    case "선불": return "즉납"; break;
		    case "착불": return "수취인부담"; break;
                }
           break;
           case "post_product":
              if ( $data[pack] )
                return $this->get_post_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

		// 사은품 check 2006.4.18 - jk
		if ( $data[gift] )
			$temp .= "\n사은품: " . $data[gift];

                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;
           //////////////////////////////////////////////
	   // CJGLS
	   case "sender":	 
             if ( _DOMAIN_ == "mago" )
	     {
		if ( 
		    $data[shop_id] == 10101 or
		    $data[shop_id] == 10103 or
		    $data[shop_id] == 10107 or
		    $data[shop_id] == 10109 or
		    $data[shop_id] == 10114 or
		    $data[shop_id] == 10115 or
		    $data[shop_id] == 10149 or
		    $data[shop_id] == 10165 or
		    $data[shop_id] == 10076 or
		    $data[shop_id] == 10168 
		)
		    return "피기";
		else
		    return "마고";
	     } 
             else if ( _DOMAIN_ == "jyms" )
               return "짐스인터네셔널";
             else if ( _DOMAIN_ == "ds" )
               return "대성트레이딩�";
             else if ( _DOMAIN_ == "kdykiss" )
               return "M슈즈";
             else if ( _DOMAIN_ == "lsy1115" )
               return "신한통상";
             else if ( _DOMAIN_ == "peggy" )
               return "Peggy";
             else if ( _DOMAIN_ == "rapa1196" )
               return "서진어패럴";
             else if ( _DOMAIN_ == "younggun" )
               return "영건";
             else if ( _DOMAIN_ == "mangosteen" )
               return "㈜망고스틴";
             else if ( _DOMAIN_ == "rianrose" )
               return "(주)리안인터내셔널";
             else if ( _DOMAIN_ == "tne" )
               return "T&E";
             else if ( _DOMAIN_ == "honny" )
               return "호빵걸";
             else if ( _DOMAIN_ == "lbgjjang" )
               return "나들이/$data[shop_name] ";
             else
               return $this->get_sender();	
           break;
	   case "sender_tel":
             	if ( _DOMAIN_ == "younggun" )
               		return "02-6206-7730";
             	else if ( _DOMAIN_ == "honny" )
               		return "02-2232-5848";
             	else if ( _DOMAIN_ == "mangosteen" )
               		return "02-594-4290~2";
             	else if ( _DOMAIN_ == "rianrose" )
               		return "02-597-1107";
             	else if ( _DOMAIN_ == "mam8872" )
		{
			
               		return "02-597-1107";
		}
           	break;
	   case "product_type":	 return "의류"; 	break;
	   case "box_type":	 
             if ( $data[trans_who] == "선불" )
               return "1";
             else
               return "1";
    	   break;
	   case "trans_fee_etc": return " ";	break;
	   case "trans_fee": 	 return $_SESSION[BASE_TRANS_PRICE];	break;
	   case "etc1":		 return " ";	break;
	   case "etc2":		 return " ";	break;
	   case "etc3":		 return " ";	break;
	   case "etc3":		 return " ";	break;
	   case "etc4":		 return " ";	break;
	   case "brand":	 return " ";	break;
           case "stock_place":	 return " ";	break;
           case "cj_seq" : return $data[pack] ? $data[pack] : $data[seq]; break;
           case "cj_amount" : return $data[amount]; break;
           case "cj_memo":
		return str_replace( $arr_chars, " ", $data[message] . $data[memo]); 
           break;	
	   case "cj_product_name" :
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - $cnt;

                if ( $_SESSION[STOCK_MANAGE_USE] )
                  $vOption = class_D::get_product_option( $data[product_id]);
                else
                  $vOption = $data[options];
		
		if ( _DOMAIN_ == "hanlin829" )
                	$temp = $data[order_name] . "/" . $cnt."개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $vOption );
		elseif ( _DOMAIN_ == "misogirl" )
                	$temp = $cnt."개:" . str_replace( $arr_chars, " " , str_replace( $arr_chars, " ", $vOption ));
		elseif ( _DOMAIN_ == "hj2526" )
                	$temp = $cnt."개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));
		elseif ( _DOMAIN_ == "emenes" )
                	$temp = $cnt."개:" . str_replace( $arr_chars, " " , str_replace( $arr_chars, " ", $data[options] ));
		else
                	$temp = $cnt."개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $vOption );
		return $temp;
           break;


           // panty bank만 사용함
           // 패션70 만 사용함
	   case "cj_product_name2" :
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - $cnt;
                $temp = $cnt."개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", "[" . $data[options]  . "]" . $data[memo]);
		return $temp;
           break;
           //////////////////////////////////////////////
           // tranet 택배
	   case "tranet_products":
              if ( $data[pack] )
	      {
                 $str_buffer =  $this->tranet_pack_product( $data[seq] ) ;
		 return $str_buffer;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $product_name = "";
                 $product_option = "";
                 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

                 $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
                 $str1 = $this->pack_string( $temp, ";", 50 );
                 $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,";",50) ) . "";

                 return $str1;
              }
		break;
	   case "tranet_box":
	        if ($data[trans_who] == "착불") return "2";
	        else if ($data[trans_who] == "선불") return "1";
		break;
           case "tranet_trans_who":
	        if ($data[trans_who] == "착불") return "2";
	        else if ($data[trans_who] == "선불") return "3";
                break;
           //////////////////////////////////////////////
           // kgb 택배
           case "mambo74_recv_name";
               return "[" . class_C::get_shop_name($data[shop_id]) . "]" . $data[recv_name];
               break;
           case "kgb_products2":
              if ( $data[pack] )
                return $this->get_kgb_pack2( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;

                if ( $_SESSION[STOCK_MANAGE_USE] )
                  $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                  $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
                return $this->pack_string( $temp, "\t", 40 );
              }
		break;
           case "kgb_products":
              if ( $data[pack] )
                return $this->get_kgb_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;

                if ( $_SESSION[STOCK_MANAGE_USE] )
                  $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                  $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
                return $temp;
              }
		break;
           case "kgb_trans_who": // 선불: 3, 착불: 2
	        if (trim($data[trans_who]) == "착불") return "2";
	        else if (trim($data[trans_who]) == "선불") return "3";
           	break;
           case "kgb_box":	// 선불: box=1, 착불 :2
	        if ($data[trans_who] == "착불") return "2";
	        else if ($data[trans_who] == "선불") return "1";
           	break;
           // 5개짜리 상품 = 신세계택배
           case "products_5":

              break;

           // 한진 택배
           case "hanjin_product3":
              if ( $data[pack] )
                return $this->get_hanjin_pack3( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                {
                    $cnt = $data[qty] - (int)$cnt;
                    $temp =  str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));
                    $option = str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                    if ( $option == "" )
                    {
                        $option = $data[options];
                    }

                    if (   _DOMAIN_ == "sweetbox"
                     	or _DOMAIN_ == "eleven"  
                     	or _DOMAIN_ == "gmark"  
	 	    ) 
                      $temp = $cnt . "개: " . $temp . $option; 
                    else
                      $temp .= $option . " X" . $cnt;
                }
                else
                {
                    $cnt = $data[qty] - (int)$cnt;

                    if (_DOMAIN_ == "sweetbox" 
                     or _DOMAIN_ == "eleven" 
                     or _DOMAIN_ == "gmark" 
                     ) 
                    {
                      	$temp  = $cnt . "개: " . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));
			$temp .= str_replace( $arr_chars, " ", $data[options]);
                    }
                    else if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang" || _DOMAIN_ == "newpacosue" || _DOMAIN_ == "metrocd")
                    {
                    	if ( $cnt > 1 )
                        	$temp = str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."개";
                    	else
                        	$temp = str_replace( $arr_chars, " ", $data[options]);
                    }
                    else
                    {
                      	if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang" || _DOMAIN_ == "newpacosue" || _DOMAIN_ == "metrocd")
                      	{
                          	if ( $cnt > 1 )
                              		$temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."개";
                      	}
                      	else 
                        {
                          	$temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."개";
                        }
                    }
                }

		if ( _DOMAIN_ == "younggun" )
                	return "[총". $cnt. "개]". $temp;
		else
                	return $temp;

                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;
           // 한진 택배
           case "hanjin_product2":
              if ( $data[pack] )
                return $this->get_hanjin_pack2( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;

                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;
           // 한진 택배
           case "hanjin_product":
              if ( $data[pack] )
                return $this->get_hanjin_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "개:" . $this->get_product_name($data[product_id]) . class_D::get_product_option($data[product_id]);
                $temp = $this->pack_string( $temp, " \t", 100, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;

           case "hanjin_product5":	// 옵션별 발주를 사용하지 않는 업체의 경우
              if ( $data[pack] )
                return $this->get_hanjin_pack5( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options];
                $temp = $this->pack_string2( $temp, " \t", 50, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }

              break;

           case "hanjin_product4":	// 옵션별 발주를 사용하지 않는 업체의 경우
              if ( $data[pack] )
                return $this->get_hanjin_pack4( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = $this->pack_string( $temp, " \t", 100, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;

           case "etc":

	         if ( $data[order_cs] == 5 or $data[order_cs] == 11)
		     $str_result = "[[교환]] ";
	         if ( $data[order_cs] == 9 )
		     $str_result = "[[맞교환]] ";

		//=================================================
		//
		// 교환일 경우
		// date: 2007.5.2 -jk
		if ( substr($data[order_id],0,1) == "C" )
			$str_result = "[[교환]] ";


		 $str_result .= str_replace( $arr_chars , " ", $data[memo] );

                 return $str_result;
                 break;
           case "recv_zip":
                 $arr_chars = array("-","`","/","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":", " ", chr(13),"\"","'","<br>" );
                 $recv_zip = str_replace( $arr_chars , "", $data[recv_zip] );

                 if ( _DOMAIN_ == "dmnet" 
                  or _DOMAIN_ == "misogirl" 
                  or _DOMAIN_ == "basickorea" 
                  or _DOMAIN_ == "sccompany" 
                  or _DOMAIN_ == "mambo74" 
                 )
		     return substr( $recv_zip,0,3 ) . "-" . substr( $recv_zip,3,3);
                 else
                     return $recv_zip;
           break;
           case "family_recv_zip":
                 return $data[recv_zip];
           break;
           case "A":
              return "A";
           break;
           case "B":
              return "B";
           break;
           case "x":
              return " ";
           break;
           case "zero":
              return "0";
              break;
           case "one":
              return "1";
              break;
           case "two":
              return "2";
              break;
           case "001":
              return "001";
              break;
           case "collect_date_shop":
              return "$data[shop_name] / $data[collect_date]" ;
              break;
           case "recv_name_shop":
              return "$data[recv_name] / $data[shop_name]";
           break;
           case "recv_name_shop2":
              return "$data[recv_name] / $data[shop_name] / " . $data[code2] . $data[code7];
           break;
           case "shop_recv_name":
              return "$data[shop_name] / $data[recv_name]";
           break;
           case "order_type":
              return  "합포";
           break;
           case "supply_code":
              return  $this->get_supply_name2 ( $data[$value] );
           break;
           case "enable_sale":
              return   $data[enable_sale] ? "판매가능" : "판매불가";
           break;
           case "memo_only": // for yangpa 가장 밑에 줄만 memo 출력
              if ( $data[pack] )
              {
                if ( $data[pack] == $data[seq] )
                  return $this->get_kgb_pack_memo( $data[seq] );
              }
              else
                 return $this->cutstr(str_replace( $arr_chars , ".", $data[memo] ? $data[memo] : "." ), 50);
           break;
           case "total_count2": 
              if ( $data[pack] )
              {
                 if ( $data[pack] == $data[seq] )  // for yangpa
                     return $this->get_total_count2( $data[pack] ) . "합포";
                 else
                     return "";
              }
              else
                 return "[총". $data[qty] . "개]";
           break;

           case "total_count": 
              if ( $data[pack] )
                 if ( $data[pack] == $data[seq] )  // for yangpa
                   return $this->get_total_count( $data[pack] ) . "합포";
                 else
                   return "";
              else
              {
                 return "[총". $data[qty] . "개]";
              }
           break;
           case "memo":
              if ( $data[pack] )
                 return $this->get_total_count( $data[pack] ) . "합포";
              else
              {
                 return str_replace( $arr_chars , ".", $data[product_name] );
              }
           break;
           case "memos":    // memo만 모음 aju_old_memo와 비슷
              if ( $data[pack] )
                 return $this->get_total_count( $data[pack] ) . "합포";
              else
              {
                 return $this->cutstr(str_replace( $arr_chars , ".", $data[memo] ? $data[memo] : "." ), 50);
              }
                
                break;
           case "aju_memo":
              if ( $data[pack] )
                return $this->get_aju_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;
                $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = str_replace( $arr_chars , ".", $temp );
                return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           // box on 관련
	   // date: 2006.12.7 jk.ryu
	   // for younggun
           case "boxon_trans_who":
		if ( $data[trans_who] == "선불" )
			return 3;
		else
			return 2;
		break;
           case "boxon_product":
		return $data[product_name];
		break;
           case "boxon_return":
		// for younggun
		if ( _DOMAIN_ == "younggun" )
			return "로젠택배 안성센타3층 영건물류센타 코드101";
		else if (_DOMAIN_ == "honny" )
			return "로젠택배 안성센타3층 호빵걸 코드101";
		else if (_DOMAIN_ == "mangosteen" )
			return "로젠택배 안성센터 3층 ㈜망고스틴 물류 센터 (코드101)";
		else if (_DOMAIN_ == "rianrose" )
			return "경기 안성시 로젠택배 안성센타 3층 (주)리안인터내셔널 물류센타 (코드101)";
	
		break;
	   case "boxon_products":
              	if ( $data[pack] )
                	return $this->boxon_pack( $data[seq] ) ;
		else
		{
			return $data[product_id];
		}
           	break;
	   case "boxon_products2":
	   case "boxon_products3":
	   case "boxon_products4":
	   case "boxon_products5":
	   case "boxon_products6":
	   case "boxon_products7":
	   case "boxon_products8":
	   case "boxon_products9":
	   case "boxon_products10":
	   case "boxon_products11":
	   case "boxon_products12":
	   case "boxon_products13":
	   case "boxon_products14":
	   case "boxon_products15":
	   case "boxon_products16":
	   case "boxon_products17":
	   case "boxon_products18":
	   case "boxon_products19":
	   case "boxon_products20":
		break;

	   // 사가와 택배
           case "kdykiss_memo1";
               return "  ( ♥ 반품,교환시 판매처에 ";
               break;
           case "kdykiss_memo2";
               return " 꼭 연락 후 반송 )";
               break;
	   // 2006.12.12
	   case "category":
		if ( _DOMAIN_ == "kdykiss" 
		or _DOMAIN_ == "lsy1115" )
			return "신발";
		else
			return "의류";
		break;
	   case "sw_trans_who":
		switch ( $data[trans_who] )
                {
                    case "선불": return "3"; break;
		    case "착불": return "2"; break;
                }
           break;

 	   case "sw_products":
                if ( $data[pack] )
                        return $this->sw_pack( $data[seq] ) ;
                else
                {
			$cnt = class_E::get_part_cancel_count ( $data[seq] );
			$cnt = $data[qty] - (int)$cnt;
		   
			if ( $_SESSION[STOCK_MANAGE_USE] )
			{
			   $product_name = "";
			   $product_option = "";
			   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
			   if ( $product_option == "" )
			       $product_option = $data[options];
			
			   $temp = str_replace( $arr_chars, ".", $product_name );
			   $temp .= "/[" . str_replace( $arr_chars, ".", $product_option ) . "]\t" . $cnt . "\t";
			 }      
			 else
			 {
			   // mago 원상 복귀 함 2008.1.24 - jk
			   $product_name = $this->get_product_name($data[product_id]);
			   // $product_name = $data[product_name];
			   $temp = str_replace( $arr_chars, ".", $product_name . "/[" . $data[options] ."]" ) . "\t" . $cnt . "\t";
			 }
			 $str1 = $temp. " \t";
			 return $str1;
                }
                break;
           case "sw_products2":
           case "sw_products3":
           case "sw_products4":
           case "sw_products5":
           case "sw_products6":
           case "sw_products7":
           case "sw_products8":
           case "sw_products9":
           case "sw_products10":
           case "sw_products11":
           case "sw_products12":
           case "sw_products13":
           case "sw_products14":
           case "sw_products15":
           case "sw_products16":
           case "sw_products17":
           case "sw_products18":
           case "sw_products19":
           case "sw_products20":
	   case "sw_qty1":
	   case "sw_qty2":
           case "sw_qty3":
           case "sw_qty4":
           case "sw_qty5":
           case "sw_qty6":
           case "sw_qty7":
           case "sw_qty8":
           case "sw_qty9":
           case "sw_qty10":
           case "sw_qty11":
           case "sw_qty12":
           case "sw_qty13":
           case "sw_qty14":
           case "sw_qty15":
           case "sw_qty16":
           case "sw_qty17":
           case "sw_qty18":
           case "sw_qty19":
           case "sw_qty20":
           case "sw_price1":
	   case "sw_price2":
           case "sw_price3":
           case "sw_price4":
           case "sw_price5":
           case "sw_price6":
           case "sw_price7":
           case "sw_price8":
           case "sw_price9":
           case "sw_price10":
           case "sw_price11":
           case "sw_price12":
           case "sw_price13":
           case "sw_price14":
           case "sw_price15":
           case "sw_price16":
           case "sw_price17":
           case "sw_price18":
           case "sw_price19":
           case "sw_price20":
                break;

           case "aju_product_only":
              if ( $data[pack] )
                //return $this->get_aju_pack( $data[seq] ) ;
                return $this->get_aju_product_only_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;
                $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options];
                $temp = str_replace( $arr_chars , ".", $temp );
                return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           case "aju_primary_product":
                $temp = str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));
                if ( _DOMAIN_ == "femiculine" )
                  $temp .= str_replace( $arr_chars, ".", $data[options]);
		return $temp . "\t";
              break;
           // 묶음 상품 출력
           case "aju_old_product3":
              if ( $data[pack] )
              {
                // 합포건 처리 부분
                return $this->get_aju_old_pack3( $data[seq] ) ;
              }
              else
              {
                // 묶음 상품인지 체크 하는 부분을 추가
                if ( $data[packed] )
                {
                  $cnt = class_E::get_part_cancel_count ( $data[seq] );
                  $cnt = $data[qty] - (int)$cnt;
                  return $this->get_packed_list( $data[pack_list] , $cnt ); 
                }
                else
                {
                  $cnt = class_E::get_part_cancel_count ( $data[seq] );
                  $cnt = $data[qty] - (int)$cnt;

                  $temp = $cnt . "개:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));
               
                  if ( $_SESSION[STOCK_MANAGE_USE] )
                      $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
                  else
                      $option_string = str_replace( $arr_chars, ".", $data[options] );

                  // set: 2006.12.22 -jk
                  if ( _DOMAIN_ == "midan" )
                  {
                      return $this->pack_string( $temp . $option_string , " \t\t\t 0 \t",42 );
                  }
                  else
                  {
//                    $temp .= $this->aju_option_pack_string($option_string);
                      $temp .= $option_string . " \t\t\t 0 \t";
                   
                      return $temp;
                  }
                }
                //return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;

           case "aju_old_product2":
              if ( $data[pack] )
              {
                return $this->get_aju_old_pack2( $data[seq] ) ;
              }
              else
              {
                  $cnt = class_E::get_part_cancel_count ( $data[seq] );
                  $cnt = $data[qty] - (int)$cnt;

                  $temp = $cnt . "개:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));
               
                  if ( $_SESSION[STOCK_MANAGE_USE] )
                  {
                      if ( _DOMAIN_ == "femiculine" )
                        $option_string = str_replace( $arr_chars, ".", $data[options] );
                      else
                        $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
                  }
                  else
                      $option_string = str_replace( $arr_chars, ".", $data[options] );

//                $temp .= $this->aju_option_pack_string($option_string);
                  $temp .= $option_string . " \t\t\t 0 \t";

                  return $temp;
              }
           break;
 
           case "aju_old_product":
              if ( $data[pack] )
                return $this->get_aju_old_pack( $data[seq] ) ;
              else
              {
                $temp = str_replace( $arr_chars, ".", $this->get_product_name($data[product_id])) ."\t";

                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
                else
                    $option_string = str_replace( $arr_chars, ".", $data[options] );

                $temp .= $this->aju_option_pack_string($option_string);

                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;

                $temp .= $cnt . "\t";

                return $temp;
                //return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           case "aju_old_memo":
              if ( _DOMAIN_ == "jbtech" or 
                   _DOMAIN_ == "ezadmin" or
                   _DOMAIN_ == "kayoung" or
                   _DOMAIN_ == "wishe" or
                   _DOMAIN_ == "kjplus" or
                   _DOMAIN_ == "ds153"
                 )
              {
                  if ( $data[order_cs] == 5 or $data[order_cs] == 11)
                     $str_result = "[[교환]] ";
                  if ( $data[order_cs] == 9 )
                     $str_result = "[[맞교환]] ";
              }

              // if ( $_SESSION[STOCK_MANAGE_USE] )
		      if ( $data[pack] )
			return $data[gift] . " " . $str_result . $this->get_aju_pack_memo( $data[seq] ) ;
		      else
		      {
                        if ( $data[packed] )
                        {
                          // 묶음 상품인 경우
                          // date: 2006.11.10
			  if ( _DOMAIN_ != "younggun" 
                           and _DOMAIN_ != "honny"
                          )
                          	$qty_str= "[총" . count( split ( "," , $data[pack_list] ) ) * $data[qty] . "개]";

			  $temp = $data[message]. $data[memo];
		  	  $temp = str_replace( $arr_chars, ".", $temp );
                          $temp = $temp ? $temp : "메모없음";
                        }
                        else
                        {
                          // 묶음 상품이 아닌 경우
                          // date: 2006.11.10
			  if ( _DOMAIN_ != "younggun"
                           and _DOMAIN_ != "honny"
                          )
                          	$qty_str= "[총" . $data[qty] . "개]";

			  if ( _DOMAIN_ == "mago" or _DOMAIN_ == "peggy" )
			        $price_str = "[총" . number_format( $data[shop_price]) . "원]";

			  $temp = $data[message]. $data[memo];
		  	  $temp = str_replace( $arr_chars, ".", $temp );
                          
                          // 메모에 

			  if ( _DOMAIN_ != "nak21" )
                          	$temp = $temp ? $temp : "메모없음";
                        }
                        // 현대택배는 25자 이상 안나옴
                        global $trans_corp;

			// 당분간 빼달라 함 2006.12.15 - jk
			//if ( _DOMAIN_ == "nak21" )
			//		$qty_str = "";

			// date: 2007.1.12
			//if ( _DOMAIN_ == "hanlin829" )
                        // 	return $this->pack_string( $qty_str . $str_result . $temp, "\t", 30, 1 );
                	//$temp = $this->pack_string( $temp, " \t", 100, 2 );

                        if ( $trans_corp == '30079' )
                          return $this->cutstr( $price_str . $qty_str . $str_result . $temp, 60 );
                        else
			  return $data[gift] . " " .  $price_str . $qty_str . $str_result . $temp;
		      }
           break;
	   case "aju_old_memo2":
	      if ( $data[pack] )
		return $this->get_aju_pack_memo( $data[seq] ) ;
	      else
	      {
		$temp = $data[message] . " " . $data[memo];
		$temp = str_replace( $arr_chars, ".", $temp );
		return "[총" . $data[qty] . "개 ] $temp";
	      }
           break;
           case "kgb_memo":
              if ( $data[pack] )
                return $this->get_kgb_pack_memo( $data[seq] ) ;
              else
              {
                $temp = $data[memo];
                $temp = str_replace( $arr_chars, ".", $temp );
                return $temp;
              }
           break;
           case "family_product_option":
              if ( $data[pack] )
                return $this->get_family_pack_option( $data[seq] ) ;
              else
              {
		//==================================================
		//
		// 묶음 상품 여부 check
		//
		$cnt = class_E::get_part_cancel_count ( $data[seq] );
		$cnt = $data[qty] - (int)$cnt;

		if ( $data[packed] )
		{
			$temp = $this->get_packed_list2( $data[pack_list] , $cnt, "``$" );
		}
		else
		{
			$this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
			$temp = $cnt . "개:" . str_replace( $arr_chars,"", $product_name);
			$temp .= str_replace( $arr_chars, ".", $product_option ) . "``$";

		}
                // return $this->pack_string( $temp, "``$", 50 );
                return $temp;
              }
           break;
           case "family_product":
              if ( $data[pack] )
                return $this->get_family_pack( $data[seq] ) ;
              else
              {

                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - (int)$cnt;
		
                $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options];
                return $this->pack_string( $temp, "``$", 50 );
              }
           break;
           case "options":  // 옵션 사항
              return $data[options];
           break;
           case "box":
              return "1";
           break;
           case "trans_who2":
              if ( _DOMAIN_ == "jyms" )
              {
	        if ($data[trans_who] == "착불") return "002";
	        else if ($data[trans_who] == "선불") return "003";
	        else return "002";
              }
              else if ( _DOMAIN_ == "ds153" )
              {
	        if ($data[trans_who] == "착불") return "2";
	        else if ($data[trans_who] == "선불") return "3";
	        else return "2";
              }
	      if ($data[trans_who] == "착불") return "'002";
	      else if ($data[trans_who] == "선불") return "'003";
	      else return "'002";

	      break;
	   case "air_pay":
		return "1";
              break;
           case "trans_who_yellow":
	      if ($data[trans_who] == "착불") return "002";
	      else if ($data[trans_who] == "선불") return "003";
	      else return "002";
	      break;
           case "trans_who_yellow2":
	      if ($data[trans_who] == "착불") return "002";
	      else if ($data[trans_who] == "선불") return "";
	      else return "002";
	      break;
           case "deliv_who":
              // 제주일 경우 무조건 착불 4000원 - 양파 요청
              if ( _DOMAIN_ == "yangpa" )
              {
                if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
                  return "착불";

                if ( preg_match ("/^(690)-\d/", $data[recv_zip]) )
                  return "착불";
              }

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
           case "amount":
               // lovehouse는 선불 2000원 - 2006.11.6 - jk.ryu
               if ( _DOMAIN_ == "lovehouse" 
                 or _DOMAIN_ == "ymy2875" )
               {
                 if ( $data[trans_who] == "선불" )
                   return 2000;
                 else
                   return 2500; 
               }
               else if ( _DOMAIN_ == "kkt114" )
               {
                 if ( $data[trans_who] == "선불" )
                   return 2300;
                 else
                   return 2300; 
               }
	       else if ( _DOMAIN_ == "ozen" )
               {
                 if ( $data[trans_who] == "선불" )
                   return 2200;
                 else
                   return 2500; 
               }
               else
               {
                 $trans_price = $this->get_trans_price($data[product_id]);
                 return $trans_price ? $trans_price : $_SESSION[BASE_TRANS_PRICE];
               }
               break;
           case "cj_qty_product_only": // 수량 + 옵션 개행은 $
             if ( $data[pack] )
	      {
                 $str_buffer =  $this->get_pack_product_only2( $data[seq], "\$", 500 ) ;
		 return $str_buffer;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
                   $product_name = "";
                   $product_option = "";
                   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
                   if ( $product_option == "" )
                       $product_option = $data[options];

                   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
                   $temp .= "-" . str_replace( $arr_chars, ".", $product_option );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . $data[options] );
                 }
                 $str1 = $temp;
                 // $str1 = $this->pack_string( $temp, "\$", 46 );
                 return $str1;
              }
               break;

           // 상품 출력 순서가 개수 + 상품명 + 옵션
           case "qty_product_only4": // 수량 + 옵션 상품 옵션에 내용이 없을 경우 주문의 내용이 출력됨
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only4( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
                   $product_name = "";
                   $product_option = "";
                   if ( $product_option == "" )
                       $product_option = $data[options];

                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $product_option . "♥" . $cnt );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   // $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . $data[options] );
                   if ( $cnt > 1 ) 
                     $temp  = "♥" . $cnt; 
                   else
                     $temp  = $cnt; 

                   $temp .= "개:";
                   $temp .= str_replace( $arr_chars, ".", $product_name . "-" . $data[options] );

                }

                 $str1 = $this->pack_string( $temp, "|", 46 );

                 return $str1;
              }


           // 상품 출력 순서가 상품명 + 옵션 + 개수
           case "qty_product_only3": // 수량 + 옵션 상품 옵션에 내용이 없을 경우 주문의 내용이 출력됨
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only3( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
                   $product_name = "";
                   $product_option = "";
                   if ( $product_option == "" )
                       $product_option = $data[options];

                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $product_option . "♥" . $cnt );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   // $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . $data[options] );
                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $data[options] );
                   if ( $cnt > 1 ) 
                     $temp  .= "♥" . $cnt; 
                   else
                     $temp  .= $cnt; 
                   $temp .= "개";
                 }

                 $str1 = $this->pack_string( $temp, "|", 46 );
                 // $str1 .= "   " . $product_option . "|";
                 //$str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|",46) ) . "";

                 return $str1;
              }
           break;

           // 한줄에 개수 + 상품 + 옵션이 출력
           case "qty_product_only2": // 수량 + 옵션 상품 옵션에 내용이 없을 경우 주문의 내용이 출력됨
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only2( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
                   $product_name = "";
                   $product_option = "";
                   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
                   if ( $product_option == "" )
                       $product_option = $data[options];

                   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . "-" . $product_option );
                 }
                 else
                 {
		    if ( _DOMAIN_ == "nak21" )
		    {
                    	$product_name = $this->get_product_name($data[product_id]);
                    	$temp = str_replace( $arr_chars, ".",  $product_name ."[". $data[options] ."] x" . $cnt );
		    }
                    else if ( _DOMAIN_ == "mantan" 
                    or _DOMAIN_ == "r2046008" )
                    {
                    	$product_name = $this->get_product_name($data[product_id]);
                    	$temp = str_replace( $arr_chars, ".", $product_name . $data[options] . "X $cnt"  );
                    }
		    else
		    {
                    	$product_name = $this->get_product_name($data[product_id]);
                    	$temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . $data[options] );
		    }
                 }

                 if ( _DOMAIN_ == "r2046008" )
                 	$str1 = $this->pack_string( $temp, "|", 40 );
                 else
                 	$str1 = $this->pack_string( $temp, "|", 46 );
                 // $str1 .= "   " . $product_option . "|";
                 //$str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|",46) ) . "";

                 return $str1;
              }
           break;


           case "qty_product_only": // 수량 + 옵션 상품 옵션에 내용이 없을 경우 주문의 내용이 출력됨
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only( $data[seq] ) ;

		if ( _DOMAIN_ == "jsclub" )
		    $str_buffer .= "(" . $this->get_total_price( $data[pack] ). "원 ) ";

		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
                   $product_name = "";
                   $product_option = "";
                   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
                   if ( $product_option == "" )
                       $product_option = $data[options];

                   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name . $data[options] );
                 }

		if ( _DOMAIN_ == "jsclub" )
		    $temp .= "(" . number_format( $data[shop_price] * $data[qty] ) . "원 ) ";

                 $str1 = $this->pack_string( $temp, "|", 46 );
                // $str1 .= "   " . $product_option . "|";
                 $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|",46) ) . "";

                 return $str1;
              }
           break;

           // 집하 영업소 
           case "yellow_collect_m":
             if (_DOMAIN_ == "lovehouse" )
               return "8070036";
           break;

           // 집하 지점 
           case "yellow_collect_ap":
             if (_DOMAIN_ == "lovehouse" )
               return "807";
           break;

           // 당당사원
           case "yellow_worker":
             if (_DOMAIN_ == "lovehouse" )
               return "8070036";
           break;
           ///////////////////////////////////////////
	   //
           // yellow 택배 상품 리스트 출력 2번
           // 줄을 내리는 시그널은 ; 임
	   // 복수 내품 양식
   	   //
           case "yellow_product3":
	      if ( $data[pack] )
	      {
                $str_buffer =  $this->get_pack_product_only2( $data[seq] , ";" ) ;
		return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $temp = str_replace( $arr_chars, ".", stripslashes( $cnt . "개:" . $this->get_product_name($data[product_id]))) . "->";
                 $temp .= class_D::get_product_option( $data[product_id]);

                 return $this->pack_string( $temp, ";" );
              }
  	      break;

	   case "yellow_product2":
	      // if ( $data[pack] )
	      // {
                // $str_buffer =  $this->get_pack_memo( $data[seq] , ";" ) ;
		// return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      //}
              // else
              //{
		// 양파는 상품에 총 개수가 나온다
		// jk.ryu
		// 2006.12.12
		// 총 개수를 기록함
		$temp = "";

                // get_total_count에서 g_count값이 결정된다
                // 합포가 아닌경우 g_count값은 1
		if ( _DOMAIN_ == "yangpa" )
                {
                    if ( $data[pack] )
                    {
                    	if ( $this->g_index == 0 )
				$this->get_total_count( $data[pack] );
                    }
                    else
                        $this->g_count = 1;
                }

                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 if ( _DOMAIN_ == "yangpa" )
                   $temp .= str_replace( $arr_chars, ".", stripslashes( $this->get_product_name($data[product_id])));
		 else
                   $temp .= str_replace( $arr_chars, ".", stripslashes( $cnt . "개:" . $this->get_product_name($data[product_id])));

		 $temp .= "[" . $data[options] ? str_replace( $arr_chars, "." , $data[options] ) : " " . "]";

		if ( _DOMAIN_ == "lovehouse" )
			$temp .= "/" . $data[options];

                 if ( _DOMAIN_ == "yangpa" )
                   $temp .= "X ". $cnt;

		 // 조철훈씨 요청으로 메모 나옴 - jk.ryu 12.12
                 if ( _DOMAIN_ != "yangpa" )
		   if ( $this->g_memo != $data[memo] )
		   {
                     $temp .= $data[memo] ? str_replace( $arr_chars, ".", $data[memo] ) : " ";
		     $this->g_memo = $data[memo];
		   }

		 //----------------------------------------------------------
		 // 
		 // 가장 마지막 자료에 메모를 넣는다
		 //
		 if ( _DOMAIN_ == "yangpa" )
		 {
                     if ( $this->g_count == 1 )
                     {
                         $temp .= "-";
                         $temp .= $data[memo] ? str_replace( $arr_chars, " ", $data[memo] ) : " ";
                     }
                     else
                     {
			 $this->g_index++;
			 if ( $this->g_index == $this->g_count )
			 {
			   $temp .= "-" . $this->get_aju_pack_memo( $data[pack] );
			   $this->g_index = 0;
			 }
                     }
		 }
                 // return $this->pack_string( $temp, ";" );
 		 return $temp;
              //}
  	      break;

           // 합포가 한건씩 출력되는 옵션별 발주용
	   case "yellow_product4":
	      // if ( $data[pack] )
	      // {
                // $str_buffer =  $this->get_pack_memo( $data[seq] , ";" ) ;
		// return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      //}
              // else
              //{
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

		 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

                 $temp = str_replace( $arr_chars, ".", $cnt . "개:$product_name - $product_option" );

                 // return $this->pack_string( $temp, ";" );
 		 return $temp;
              //}
  	      break;


           ///////////////////////////////////////////
           // yellow 택배 상품 리스트 출력
           // 줄을 내리는 시그널이 없음
           case "yellow_product":
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only2( $data[seq] ) ;
		 return $str_buffer;
                 // return $this->get_pack_memo( $data[seq] ) ;
	      }
              else
              {
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $product_name = "";
                 $product_option = "";
                 if ( $_SESSION[STOCK_MANAGE_USE] )
                 {
			 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

			 $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
			 $str2 = "->" . str_replace( $arr_chars, "", $product_option );
                 }
                 else
                 {
			$temp = $cnt . "개:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options] . "/" . $data[memo] . "|| ";
                        $temp = str_replace( $arr_chars, " ", $temp );
                 }

                 return $temp . $str2;
              }
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
                 $cnt = class_E::get_part_cancel_count ( $data[seq] );
                 $cnt = $data[qty] - (int)$cnt;

                 $temp = str_replace( $arr_chars, ".", stripslashes( $cnt . "개:" . $this->get_product_name($data[product_id])));
		 $temp .= "[";
		 $temp .= $data[options] ? str_replace( $arr_chars, "." , $data[options] ) : " ";
		 $temp .= "]";
                 $temp .= $data[memo] ? str_replace( $arr_chars, ".", $data[memo] ) : " ";

                 return $this->pack_string( $temp );
              }
           break;
           case "recv_tel2":
	       $recv_tel =$data[recv_tel] ? $data[recv_tel] : $data[recv_mobile]; 
               return str_replace("-","",$recv_tel);
               break;
           case "recv_mobile2":
	       $recv_mobile =$data[recv_mobile] ? $data[recv_mobile] : $data[recv_tel]; 
               return str_replace("-","",$recv_mobile);
               break;
           default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( $arr_chars, ".", $val );
           break; 
      }
   }

   ///////////////////////////////////////////////
   // 상품별 배송비가 다른 경우가 있음
   // 상품별 배송비를 가져옴
   function get_trans_price ( $product_id )
   {
      global $connect;

      // $query = "select trans_fee from products where product_id = '$product_id'";
      // $result = mysql_query ( $query, $connect );
      // $data = mysql_fetch_array ( $result );
      // return $data[trans_fee];
      return 2500;
   }

   

   function get_product_name( $product_id )
   {
       global $connect;
       $query = "select name from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       return $data[name] . "-"; 
   }

   function get_product_option( $product_id )
   {
       global $connect;
       $query = "select options from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );
       return $data[options] . "-"; 
   }

   function download()
   {
      global $connect, $saveTarget;

      $transaction = $this->begin("주문다운로드");

      ///////////////////////////////////
      // open file to get file handle 
      $handle = fopen ($saveTarget, "w");

      // download format에 대한 정보를 가져온다
      $result = $this->get_format();

      $download_items = array (); 
      /*
      while ( $data = mysql_fetch_array ( $result ) )
      {
         $download_items[$data[id]] = $data[name];
      }
      */
      foreach ( $result as $key=>$name )
      {
         $download_items[$key] = $name;
      }

      ////////////////////////////////////////////////////////
      // 전체 data를 download받는다
      // cj 택배는 전체를 받아야 함
      // 30003 : cj택배 
      // 30057 : 옐로우택배
      // 30037 : 삼성 택배
      // 30095 : 사가와 택배
      // 30018 : 고려택배
      // 30026 : 로젠 - 전체 받는다 (ds와 ozen만)
      global $trans_corp;
      // 위의 택배사를 사용중 이지만 합포를 한출에 처리하는 업체 리스트
      $header = false;	// header 출력 안 함

      if ( 
           (
           _DOMAIN_ != "kayoung" and
           _DOMAIN_ != "seongeun" and
           _DOMAIN_ != "color250" and
           _DOMAIN_ != "mambo74" and
           _DOMAIN_ != "beachnaboom" and
           _DOMAIN_ != "bmkorea" and
           _DOMAIN_ != "bbada" and
           _DOMAIN_ != "eden" and
           _DOMAIN_ != "cw2995" and
           _DOMAIN_ != "jyms" and
           _DOMAIN_ != "younggun" and
           _DOMAIN_ != "mangosteen" and
           _DOMAIN_ != "rianrose" and
           _DOMAIN_ != "honny" and
           _DOMAIN_ != "ezadmin" and
           _DOMAIN_ != "mago" and
           _DOMAIN_ != "peggy" and
           _DOMAIN_ != "goview" and
           _DOMAIN_ != "sshin" and
           _DOMAIN_ != "caramel" and
           _DOMAIN_ != "kdykiss" and
           _DOMAIN_ != "lsy1115" and
           _DOMAIN_ != "m9466" and
           _DOMAIN_ != "bose5546"
           ) 
           and
           (
           $trans_corp == "30003" or
           $trans_corp == "30057" or
           $trans_corp == "30026" or
           $trans_corp == "30037" or
           $trans_corp == "30095" or
           $trans_corp == "30018"
           )
         )
      {
	      $option = 1;
	      $pack_option = 2;
              $result = $this->get_order_list( &$total_rows , $option, $pack_option);

              ////////////////////////////////////////
              // writting datas to file
              $i = 1;
              while ( $data = mysql_fetch_array ( $result ) )
              {
		 // header를 출력하지 않을 경우
		 if ( $i == 1 )
                   if (  
                     ( $trans_corp != "30018" or
                     _DOMAIN_ != "dmnet" ) and 
                     _DOMAIN_ != "ds153" or
                     _DOMAIN_ != "nak21"
                   )
                   {
                    foreach ( $download_items as $key=>$value )
                       $buffer .= $value. "\t";
                    $buffer .= "\n";
                   }

                 // younggun의 경우 단품의 코드가 00736인경우 누락 시켜야 한다
                 // date: 2006.12.18 - jk.ryu
		 if ( _DOMAIN_ == "younggun" and $data[pack] == "null" and $data[product_id] == "00736" )
			continue;

                 foreach ( $download_items as $key=>$value )
                 {
                    $buffer .= $this->get_data( $data, $key, $i );

                    if ( $key != "kayoung2" and 
                         $key != "kayoung3" and
                         $key != "kayoung_cj_etc" and 
                         $key != "kayoung4" and 
                         $key != "kayoung5" and 
                         $key != "kayoung6" and 
                         $key != "kayoung7" and 
                         $key != "kayoung8" and 
                         $key != "kayoung9" ) 
                      $buffer .= "\t";

                 }

                 fwrite($handle, $buffer . "\n" );
                 $buffer = "";
		 $i++;
             }

      }
      ///////////////////////////////////////////////////
      //
      // cj, yellow 택배가 아닌경우 처리
      //   => 합포를 한 줄에 처리 하지 않을 경우 
      //
      else
      {
	      /////////////////////////////////////////////////////////
	      // 합포만 download받는다
	      $result = $this->get_order_list( &$total_rows , 1, 1); 
	 
	      ////////////////////////////////////////
	      // writting datas to file
	      $i = 1;
	      while ( $data = mysql_fetch_array ( $result ) )
	      {
                 // header 출력 부분
		 if ( $i == 1 )
                   if (  
                     	$trans_corp != "30084"
                     and $trans_corp != "30050" ) // 대한통운이면 header출력 안 함
		 {
                    // 로젠 ds153은 헤더 출력 안 함
		    if ( _DOMAIN_ == "ds153" )
		    {
	      	    	$header = true;	// header 출력 함
                    }
                    else
                    {	
		       foreach ( $download_items as $key=>$value )
		           $buffer .= $value. "\t";
		       $buffer .= "\n";
	      	       $header = true;	// header 출력 함
                    }
		 }

		 foreach ( $download_items as $key=>$value )
		 {
		    $buffer .= $this->get_data( $data, $key, $i );

                    if ( $key != "kayoung2" and 
                         $key != "kayoung3" and
                         $key != "kayoung_cj_etc" and
                         $key != "kayoung4" and 
                         $key != "kayoung5" and 
                         $key != "kayoung6" and 
                         $key != "kayoung7" and 
                         $key != "kayoung8" and 
                         $key != "kayoung9" ) 
                      $buffer .= "\t";
		 }

		 fwrite($handle, $buffer . "\n" );
		 $buffer = "";
		 $i++;
	      }

              // 로젠 ds153은 헤더 출력 안 함
              if ( _DOMAIN_ == "ds153" and $trans_corp == "30026" )
                  if ( $i == 1 )
                      $header = true;	
 
		if ( _DOMAIN_ == "nak21" )
			$header = true;

	      /////////////////////////////////////////////////////////
	      // 합포를 제외한 data를 download받는다
	      $result = $this->get_order_list( &$total_rows , 1); 

	      ////////////////////////////////////////
	      // writting datas to file

	      while ( $data = mysql_fetch_array ( $result ) )
	      {
		 // header 출력 부분
                 // 합포부분에서 출력을 안 했을 경우 출력 함
		 if ( $i == 1 && $header == false)
                   if (  $trans_corp != "30022" 
		     and _DOMAIN_ != "nak21"
                     and $trans_corp != "30084"
                     and $trans_corp != "30050" ) // 대한통운이면 header출력 안 함
		 {
		    foreach ( $download_items as $key=>$value )
		       $buffer .= $value. "\t";
		    $buffer .= "\n";
		 }

		 foreach ( $download_items as $key=>$value )
		 {
		     $buffer .= $this->get_data( $data, $key, $i );
                     if ( $key != "kayoung2" and
                         $key != "kayoung3" and 
                         $key != "kayoung_cj_etc" and
                         $key != "kayoung4" and 
                         $key != "kayoung5" and 
                         $key != "kayoung6" and 
                         $key != "kayoung7" and 
                         $key != "kayoung8" and 
                         $key != "kayoung9" ) 
		     $buffer .= "\t";
		     $i++;
		 }

		 fwrite($handle, $buffer . "\n" );
		 $buffer = "";
		 $i++;
	      }
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

      $transaction = $this->end( $transaction );

      exit; 
   }


   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_kgb_pack_memo( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $query = "select product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 
      $before_memo = "";
      $qty = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         if ( $before_memo != $data[memo] )
         {  
           $temp = str_replace( $arr_chars, ".", $data[memo]);
           $temp .= " / ";
           $str .= $temp;
         }
         $before_memo = $data[memo];
         $qty = $qty + $data[qty];
     }
     return "[총" . $qty . "개]" . $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_aju_pack_memo( $pack )
   {
      global $connect, $trans_corp;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      // 배송전 교환 상태도 포함시킴 - jk.ryu 2006.11.28
      $query = "select product_id, product_name, memo, qty, options, message,packed, pack_list, shop_price from orders where pack='$pack' and order_cs in (0,9,5,7,13,9,10,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 
      $old_data = "";
      $count = 0;
      $tot_price = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         $temp_data = str_replace( $arr_chars, ".", $data[message] . $data[memo]);

	// 총 금액이 필요함 = 2006.12.18 - jk.ryu
         if ( _DOMAIN_ == "mago" or _DOMAIN_ == "peggy" )
		$tot_price = $tot_price + $data[shop_price];	

	//=================================================
	// nak21은 같은 주문도 나오지 않게 해달라고 함
	// 2007.8.8 임아진 요청
	//
	if ( _DOMAIN_ == "nak21" )
	{
		$temp = $temp_data;
		$temp .= " / ";
             	$str .= $temp;
	} 
	else
	{
		 if ( $old_data != $temp_data  )
		 {
		     $temp = $temp_data;
		     $temp .= " / ";

		     //$str1 = $this->pack_string( $temp, " \t\t\t\t" );
		     $str .= $temp;
		 }
	}

        $old_data = $temp_data;

         // packed (묶음 상품인 경우) 처리 방안
         // date: 2006.11.10 - jk.ryu
         if ( $data[packed] )
         {
           // 묶음 상품 개수처리
           $count = $count + count( split(",",$data[pack_list] )) * $data[qty];
         }
         else
         {
           // 총수량 
           if ( _DOMAIN_ == "yangpa" 
             or _DOMAIN_ == "orange"
             or _DOMAIN_ == "myking"
             or _DOMAIN_ == "mam8872"
             or _DOMAIN_ == "sweetbox"
             or _DOMAIN_ == "eleven"
             or _DOMAIN_ == "gmark"
             or _DOMAIN_ == "jackpot"
             or _DOMAIN_ == "hejim"
             or _DOMAIN_ == "cunsung"
             or _DOMAIN_ == "elelgp"
             or _DOMAIN_ == "femi"
             or _DOMAIN_ == "saleplus"
             or _DOMAIN_ == "nak21"
             or _DOMAIN_ == "dmnet"
             or _DOMAIN_ == "shala"
             or _DOMAIN_ == "metrocd"
             or _DOMAIN_ == "newpacosue"
             or _DOMAIN_ == "nicekang"
             or _DOMAIN_ == "biashop"
             or _DOMAIN_ == "tne"
             or _DOMAIN_ == "m9466"
             or _DOMAIN_ == "poison2007"
           )
             $count = $count + $data[qty];
           else
             $count++;
         }
     }

     // 조철훈이가 메모없음 나오게 해달라 다시 요청 2006.12.14
     //if ( _DOMAIN_ == "yangpa" || _DOMAIN_ == "ezadmin" )
     //  $str = $str ? $str : "";
     //else
       $str = $str ? $str : "메모없음";

     // 총 개수도 나와달라고 함
     if ( $count > 1 ) 
     {
	if ( _DOMAIN_ != "younggun" 
         and _DOMAIN_ != "honny"
        )
          $str = "[총" . $count . "개] $str";

	if ( _DOMAIN_ == "mago"  or _DOMAIN_ == "peggy" )
          $str = "[총" . number_format( $tot_price) . "원] $str";

	global $trans_corp;
        if ( $trans_corp == '30079' )
          return $this->cutstr( $str, 60 );
        else
	  return $str;
     }
     else
     {
	global $trans_corp;
        if ( $trans_corp == '30079' )
          return $this->cutstr( $str, 60 );
        else
	  return $str;
     }
   }

   function get_total_count2( $pack )
   {
       global $connect;

       $query = "select sum(qty) cnt from orders where pack='$pack' and order_cs in (0,11,9)";

       $result = mysql_query ( $query, $connect );

       $data = mysql_fetch_array ( $result );

 	$this->g_count = $data[cnt];

       return "[총" . $data[cnt] . "개] ";
   }

   function get_total_count( $pack )
   {
       global $connect;

       $query = "select count(*) cnt from orders where pack='$pack' and order_cs in (0,11,9)";

       $result = mysql_query ( $query, $connect );

       $data = mysql_fetch_array ( $result );

 	$this->g_count = $data[cnt];

       return "[총" . $data[cnt] . "개] ";
   }
   //////////////////////////////////////////////
   // 옵션별 발주를 사용하지 않는 업체의 경우 

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_hanjin_pack4( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
   
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         //$temp = str_replace( $arr_chars, ".", $cnt . "개:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
         $temp = str_replace( $arr_chars, ".", $temp);

         $str1 = $this->pack_string( $temp, " \t", 100, 2 );
         $str .= $str1;
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력 안함
   // name: memo사항
   // name: memo사항
   function get_hanjin_pack5( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
   
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         //$temp = str_replace( $arr_chars, ".", $cnt . "개:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
	   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
           $opt = $product_option ? $product_option : $data[options];
           $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $opt;
         }
         else
           $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];

         // $temp = str_replace( $arr_chars, ".", $temp) . " \t" . str_replace( $arr_chars, ".", $data[memo] );
         $temp = str_replace( $arr_chars, ".", $temp) ;

         $str1 = $this->pack_string2( $temp, " \t", 50, 2 );
         $str .= $str1;
     }
     return $str;
   }

   //////////////////////////////////////////////
   // kgb 합포 정보 출력
   // 2006.8.9 - jk.ryu
   // 40자마다 \t를 넣어 준다
   function get_kgb_pack2( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;
         $cnt = $data[qty];

         if ( $_SESSION[STOCK_MANAGE_USE] )
           $temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
         else
           $temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
         
         $str .= "$data[qty]개" . $this->pack_string( $temp, "\t", 40 );
         // $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // kgb 합포 정보 출력
   function get_kgb_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
           $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
         else
           $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

         $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_tranet_pack2( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options,gift from orders where pack='$pack'";

      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      $i = 0;

      // 개수
      $total_cnt = mysql_num_rows( $result );

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         	$temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
                $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;


        if ( $i )
            $temp = "<td>" . $temp;

        $i++;

        // total_cnt가 아니면 상품과 상품의 경계를 만든다
        if ( $i != $total_cnt )
            $temp = $temp. "</td><td> </td><td> </td>";
 
        $str .= $temp;

     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_tranet_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options,gift from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      $i = 0;

      // 개수
      $total_cnt = mysql_num_rows( $result );

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         	$temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
		if ( _DOMAIN_ == "dmnet" )
               		$temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]) . "/" . $data[options] . "/" . $data[memo] ) ;
		else
               		$temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;


        if ( $i )
            $temp = "<td>" . $temp;

        $i++;

        if ( $i != $total_cnt )
            $temp = $temp. "</td>";
 
        $str .= $temp;
     }

// echo $str;
// exit;

     return $str;
   }



   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_post_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options,gift from orders where pack='$pack' and status in (1,2,11)";
//echo $query;
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         	$temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
                $temp = $cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

	// 사은품 check 2006.4.18 - jk
	if ( $data[gift] )
		$temp .= "\n사은품: " . $data[gift];

        $str .= $temp . "\n";
     }
     return $str;
   }



   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_hanjin_pack3( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      $tot = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
                if ( _DOMAIN_ != "shala" and _DOMAIN_ != "nicekang" and _DOMAIN_ != "metrocd" and _DOMAIN_ != "newpacosue")
         	    $temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));

                // 옵션 발주와 비 옵션 발주를 혼합해서 사용함
                $temp_option = class_D::get_product_option( $data[product_id]);
                if ( $temp_option )
                  $temp .= str_replace( $arr_chars, " ", $temp_option );
                else
                {
                  if ( 	_DOMAIN_ == "sweetbox" 
			or _DOMAIN_ == "eleven" 
			or _DOMAIN_ == "gmark" 
			or _DOMAIN_ == "younggun" 
			or _DOMAIN_ == "mangosteen" 
			or _DOMAIN_ == "rianrose" 
			or _DOMAIN_ == "honny" 
                      )
                    $temp .= str_replace( $arr_chars, " ", $data[options] ) ;
                  else
                    $temp .= str_replace( $arr_chars, " ", $data[options] . "/" . $data[memo]);
                }
                if ( _DOMAIN_ == "sweetbox"  
                  or _DOMAIN_ == "eleven" 
                  or _DOMAIN_ == "gmark" 
                  )
                  $temp = $cnt . "개: " . $temp;
                else
                  if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang"  || _DOMAIN_ != "metrocd" || _DOMAIN_ != "newpacosue")
                  {
                    if ( $cnt > 1 )
                      $temp .= " X" . $cnt ;
                  }
                  else
                    $temp .= " X" . $cnt ;
         }
	 else
         {
             if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang"  || _DOMAIN_ == "metrocd" || _DOMAIN_ == "newpacosue")
             {
                 if ( $cnt > 1 )
                    $temp = str_replace( $arr_chars, " ", $data[options]) . " X". $cnt;
                 else
                    $temp = str_replace( $arr_chars, " ", $data[options]);
             }
             else
                 $temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) . " X". $cnt;
         }

         $str .= $temp . "|";
	 $tot = $tot + $cnt;
     }
     if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang"  || _DOMAIN_ == "metrocd" || _DOMAIN_ == "newpacosue")
	return $str;
     else
        return "[총" . $tot. "개] $str";
   }


   function get_kayoung_cj_etc( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and pack <> seq";
      $result = mysql_query ( $query, $connect );
      $str = ""; 
      $i = 0;

      // ds153은 10개
      $cnt = 9; 
      // kayoung은 6개
      if ( 
         _DOMAIN_ == "kayoung" or
         _DOMAIN_ == "seongeun" or
         _DOMAIN_ == "color250"
         )
        $cnt = 5;
      else if ( _DOMAIN_ == "kkt114" )
        $cnt = 4;	// 광개토는 4개
      // soocargo는 한줄에서 여러줄로 변경
      //else if ( _DOMAIN_ == "soocargo" )
      //  $cnt = 5;	// 수카고 5개

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         if ( $i == $cnt ) break;
         $c_cnt = class_E::get_part_cancel_count ( $data[seq] );

	if ( $_SESSION[STOCK_MANAGE_USE] )
	    $temp = ($data[qty] - (int)$c_cnt) . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	else
        {
          if ( _DOMAIN_ == "ymy2875" or
               _DOMAIN_ == "color250" )
	    $temp = $data[qty] - (int)$c_cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", $data[options] ) ;
          else
	    $temp = $data[qty] - (int)$c_cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;
        }

        $str .= $temp . "\t";
        $i++;
     }

     for ( $j = $i; $j < $cnt; $j++ )
     {
         $str .= " \t";
     }
     return $str;
   }
   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_hanjin_pack2( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
   
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         // $cnt = class_E::get_part_cancel_count ( $data[seq] );
         // $cnt = $data[qty] - (int)$cnt;

	if ( $_SESSION[STOCK_MANAGE_USE] )
	    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	else
	    $temp = $data[qty] - (int)$cnt . "개:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;

         $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_hanjin_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
   
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         //$temp = str_replace( $arr_chars, ".", $cnt . "개:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . class_D::get_product_option($data[product_id]);
         $temp = str_replace( $arr_chars, ".", $temp);

         $str1 = $this->pack_string( $temp, " \t", 100, 2 );
         $str .= $str1;
     }
     if ( _DOMAIN_ == "wishe" )
     {
	     if ( $data[order_cs] == 5 or $data[order_cs] == 11)
		$str_result = "[[교환]] ";
	     if ( $data[order_cs] == 9 )
		$str_result = "[[맞교환]] ";
     }

     return $str_result . $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_family_pack_option( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options,pack_list from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;
	
 
         //$temp = $cnt . "개:" . str_replace( $arr_chars,".", $this->get_product_name($data[product_id])) . "``$";
         //$temp .= str_replace( $arr_chars , ".", $this->get_product_option( $data[product_id] ) ) . "``$";
         // return $this->pack_string( $temp, "``$", 50 );

         if ( $data[pack_list] )
	 {
	     $temp = $this->get_packed_list2( $data[pack_list] , $cnt, "``$" );
	 }
	 else
	 {
	     $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
	     $temp = $cnt . "개:" . str_replace( $arr_chars,"", $product_name);
	     $temp .= str_replace( $arr_chars, ".", $product_option ) . "``$";
	 }

         $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_family_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;
	 
         $temp = $cnt . "개:" . $this->get_product_name($data[product_id]) . $data[options];
         $str1 = $this->pack_string( $temp, "``$", 50 );
         $str .= $str1;
     }

      return $str;
   }

   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function boxon_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
         //$cnt = class_E::get_part_cancel_count ( $data[seq] );
         //$cnt = $data[qty] - (int)$cnt;

         //$temp =  str_replace( $arr_chars, ".", $cnt."개:" . $this->get_product_name( $data[product_id]) . "$data[options]" );
         //$str1 = $this->pack_string( $temp, " \t\t\t\t" );

	 // younggun의 경우 00736의 경우 확인이 요하는 상품임
         // date: 2006.12.18 - jk.ryu 
         if ( (_DOMAIN_ == "younggun" and $data[product_id] != "00736")
            or _DOMAIN_ == "honny"
            or _DOMAIN_ == "mangosteen"
            or _DOMAIN_ == "rianrose"
          )
         {
	     $str1 = $data[product_id] . "\t";
             $str .= $str1;
         }
     }

      return $str;
   }

   //====================================================
   //
   // sakawa 택배 합포 출력
   // date: 2006.12.11 - jk.ryu
   // 
   function sw_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
		$cnt = class_E::get_part_cancel_count ( $data[seq] );
		$cnt = $data[qty] - (int)$cnt;
   
		if ( $_SESSION[STOCK_MANAGE_USE] )
		{
		   $product_name = "";
		   $product_option = "";
		   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
		   if ( $product_option == "" )
		       $product_option = $data[options];
	
		   $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
		   $temp .= "/[" . str_replace( $arr_chars, ".", $product_option ) . "]\t" . $cnt . "\t";
		}
		else
		{
		   $product_name = $this->get_product_name($data[product_id]);
		   //$product_name = $data[product_name];
		   $temp = str_replace( $arr_chars, ".", $product_name ."/[". $data[options] . "]" ) . "\t" . $cnt . "\t";
		}
		$str1 = $temp. " \t";
         	$str .= $str1;
      }

      return $str;
   }



   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_aju_product_only_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         $temp =  str_replace( $arr_chars, ".", $cnt."개:" . $this->get_product_name( $data[product_id]) . "$data[options]" );
         $str1 = $this->pack_string( $temp, " \t\t\t\t" );
	 
         $str .= $str1;
     }

      return $str;
   }

   
   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_aju_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         $temp =  str_replace( $arr_chars, ".", $cnt."개:" . $this->get_product_name( $data[product_id]) . "$data[options]" . $data[memo]);
	
         $str1 = $this->pack_string( $temp, " \t\t\t\t" );
	 
         $str .= $str1;
     }

      return $str;
   }

   //////////////////////////////////////////
   //
   // 묶음 상품을 위한 부분 
   // 상품 구분자를 설정 할 수 있음
   // 
   //
   function get_packed_list2( $pack_list, $cnt, $sep="\`\`\$" )
   {
      global $connect;
      $arr_chars = array("/","<br>","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $list = split(",", $pack_list);

      $i=0;
      foreach ( $list as $id )
      {
        if ( $i != 0 ) $temp .= ",";
        $temp .= "'$id'";
        $i++;
      }

      $query = "select product_id, name, options from products where product_id in ( $temp )";
      $result = mysql_query ( $query, $connect );
 
// test를 위한 부분  
// if ( _DOMAIN_ == "sccompany" )
// echo $query;
 
      $str = ""; 
      $temp = "";

      while ( $data2 = mysql_fetch_array ( $result ) ) 
      {
          $temp = "$cnt 개:" . str_replace( $arr_chars, ".", $data2[name] );
          $option_string = str_replace( $arr_chars, ".", $data2[options] );
          $temp .= "[$option_string]";
          $temp .= $sep;
          $str .= $temp;
     }

	// echo $str;

      return $str;
   }

   //////////////////////////////////////////
   // 묶음 상품을 위한 부분 
   // date: 2006.11.8 - jk
   function get_packed_list( $pack_list, $cnt )
   {
      global $connect;
      $arr_chars = array("/","<br>","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $list = split(",", $pack_list);

      $i=0;
      foreach ( $list as $id )
      {
        if ( $i != 0 ) $temp .= ",";
        $temp .= "'$id'";
        $i++;
      }

      $query = "select product_id, name, options from products where product_id in ( $temp )";
      $result = mysql_query ( $query, $connect );
 
// test를 위한 부분  
//if ( _DOMAIN_ == "femi" )
//echo $query;
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) ) 
      {
          $temp = "$cnt 개:" . str_replace( $arr_chars, ".", $data[name] );
          $option_string = str_replace( $arr_chars, ".", $data[options] );
          $temp .= "[$option_string]";
          $temp .= "\t \t \t 0 \t";
          $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////
   // 묶음 상품 출력을 위한 부분
   // aju택배를 위한 부분 - 구분자가 \t \t \t 0 \t
   // date: 2006.11.10 - jk
   function get_aju_old_pack3( $pack )
   {
      global $connect;
      $arr_chars = array("/","<br>","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options, packed,pack_list from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
        $cnt = class_E::get_part_cancel_count ( $data[seq] );
        $cnt = $data[qty] - (int)$cnt;

        // 묶음 상품 여부 check함
        if ( $data[packed] )
        {
           $temp = $this->get_packed_list( $data[pack_list] , $cnt ); 
        }
        else
        {
          $temp = $cnt . "개:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));

          if ( $_SESSION[STOCK_MANAGE_USE] )
              $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
          else
              $option_string = str_replace( $arr_chars, ".", $data[options] );

          if ( _DOMAIN_ == "midan" )
              $temp =  $this->pack_string( $temp . $option_string, " \t\t\t 0 \t",42 );
          else
          {
              $temp .= $option_string;
              $temp .= "\t \t \t 0 \t";
          }
        }

        $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////
   // 구형 스타일의 프린트 용지를 위한 옵션 return
   // old pack
   // date: 2006.7.6 - jk
   function get_aju_old_pack2( $pack )
   {
      global $connect;
      $arr_chars = array("/","<br>","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options,packed from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
        $cnt = class_E::get_part_cancel_count ( $data[seq] );
        $cnt = $data[qty] - (int)$cnt;

        $temp = "$cnt 개: " . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));

        if ( $_SESSION[STOCK_MANAGE_USE] )
        {
            // option_string이 없는경우는 실제 옵션값을 출력한다
            // date: 2006.11.15 - jk.ryu
            // if ( !$option_string )
            // -----
            // 이민우씨 요청 -> 묶음 상품일 경우에만 옵션을 전체 출력
            // 2006.11.30 - jk
            if ( _DOMAIN_ == "femiculine" and $data[packed] == 1)
              $option_string = str_replace( $arr_chars, ".", $data[options] );
            else
              $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
        }
        else
            $option_string = str_replace( $arr_chars, ".", $data[options] );

        $temp .= $option_string;
        $temp .= "\t \t \t 0 \t";

        $str .= $temp;
     }

      return $str;
   }


   //////////////////////////////////////////
   // 구형 스타일의 프린트 용지를 위한 옵션 return
   // old pack
   // date: 2005.11.30
   function get_aju_old_pack( $pack )
   {
      global $connect;
      $arr_chars = array("/","<br>","=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      $query = "select seq,product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 

      while ( $data = mysql_fetch_array ( $result ) )
      {
          $temp = str_replace( $arr_chars, ".", $this->get_product_name($data[product_id])) ."\t";

          $cnt = class_E::get_part_cancel_count ( $data[seq] );
          $cnt = $data[qty] - (int)$cnt;

          if ( $_SESSION[STOCK_MANAGE_USE] )
              $option_string = str_replace( $arr_chars, ".", $this->get_product_option($data[product_id] ));
          else
              $option_string = str_replace( $arr_chars, ".", $data[options] );

          $temp .= $this->aju_option_pack_string($option_string);
          $temp .= $cnt . "\t";

         $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////////
   // date: 2006.8.31 - jk
   //    상품과 option만 출력하는 case
   //    상품명 , 옵션, 개수의 순서로 출력
   function get_pack_product_only4( $pack, $sep="|", $str_cnt=44 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'  and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

	 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";

		 $temp =  $cnt . "개:" . str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $product_option ); 

                 // pack string 
                 $str2 = $this->pack_string( $temp ) . $sep;
         }
         else
         {
                 if ( $cnt > 1 ) 
                     $temp  = "♥" . $cnt; 
                 else
                     $temp  = $cnt; 

                 $temp .= "개:";

	         $temp .= str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $data[options]);

		 $str2 = str_replace( $arr_chars, "", $temp ); 
                 $str2 = $this->pack_string( $str2 );
         }
         $str .= $str2;
     }
     return $str;
   }



   //////////////////////////////////////////////
   // date: 2006.8.31 - jk
   //    상품과 option만 출력하는 case
   //    상품명 , 옵션, 개수의 순서로 출력
   function get_pack_product_only3( $pack, $sep="|", $str_cnt=44 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'  and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

	 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";

		 $temp = str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $product_option ) . "♥" . $cnt; 
                 // pack string

                 $str2 = $this->pack_string( $temp ) . $sep;
                 //$str2 = $str1 . $sep;
         }
         else
         {
		 $temp = str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $data[options]);

                 if ( $cnt > 1 ) 
                   $temp  .= "♥" . $cnt; 
                 else
                   $temp  .= $cnt; 

                 $temp .= "개";

		 $str2 = str_replace( $arr_chars, "", $temp ); 

                 $str2 = $this->pack_string( $str2 );
         }
         $str .= $str2;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // date: 2006.1.4 - jk
   //    상품과 option만 출력하는 case
   //    delimiter로 줄을 내리는 부분이 없음
   function get_pack_product_only2( $pack, $sep="|", $str_cnt=46 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11) and order_cs in (0, 11, 9,13,7,5,10) order by product_id, options";
      $result = mysql_query ( $query, $connect );

      // date:2007.3.12 너무 길다고 요청
      if ( _DOMAIN_ == "r2046008" )
         $str_cnt = 40;

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";
		 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

		 $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $product_option ); 
                 // pack string

                 if ( _DOMAIN_ == "onff" ) $str_cnt = 56;

                 $str2 = $this->pack_string( $temp, $sep, $str_cnt );
                 //$str2 = $str1 . $sep;
         }
         else
         {
		if ( _DOMAIN_ == "mam8872" 
		or _DOMAIN_ == "nomsjy" )
                {
	           $temp = $cnt . "개:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options];
                   $str2 = $this->pack_string( $temp, $sep, $str_cnt );
                }
                else if ( _DOMAIN_ == "mantan" 
		or _DOMAIN_ == "r2046008" )
                {
                   $product_name = $this->get_product_name($data[product_id]);
                   $temp = str_replace( $arr_chars, ".", $product_name . $data[options] . "X $cnt"  );
                   $str2 = $this->pack_string( $temp, $sep, $str_cnt );
                }
		else if (_DOMAIN_ == "nak21" )
		{
	           $temp = $this->get_product_name( $data[product_id] ) . "[". $data[options] . "] x" . $cnt;
                   $str2 = $this->pack_string( $temp, $sep, $str_cnt );
		}
                else
                {
	           $temp = $cnt . "개:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options] . "/" . $data[memo];
		   $temp = str_replace( $arr_chars, "", $temp ). $sep; 
                   $str2 = $this->pack_string( $temp, $sep, $str_cnt );
                }
         }
         $str .= $str2;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // date: 2006.1.2 - jk
   // 상품과 option만 출력하는 case
   function tranet_pack_product( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         $product_name = "";
         $product_option = "";
         $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

         $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
         $str1 = $this->pack_string( $temp, ";", 50 );
         $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,";",50) ) . "";

         $str .= $str1;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // date: 2006.1.2 - jk
   // 상품과 option만 출력하는 case
   function get_pack_product_only( $pack, $se = "|", $str_cnt=44 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      if ( _DOMAIN_ == "saleplus" 
        or _DOMAIN_ == "myking" )
          $str_cnt = 44;

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
           $product_name = "";
           $product_option = "";
           $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
           $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
         }
         else
         {
           $product_name = $this->get_product_name($data[product_id]);
           $temp = str_replace( $arr_chars, ".", $cnt . "개:" . $product_name );
         }

         if ( $product_option == "" )
             $product_option = $data[options];

         $str1 = $this->pack_string( $temp, $se, $str_cnt );
        // $str1 .= "   " . str_replace( $arr_chars, "", $product_option ) . "|";
         $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|", $str_cnt) ) . "";

         $product_option = "";
         $str .= $str1;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // pack memo출력
   // name: memo사항
   // name: memo사항
   function get_pack_memo( $pack , $seperator = "|")
   {
      global $connect;

      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // 대한통운에서 제공하는 양식
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         $temp =  str_replace( $arr_chars, ".", stripslashes( $cnt . "개:" . $this->get_product_name($data[product_id])));
         $temp .= "[";
         $temp .= $data[options] ? str_replace( $arr_chars, "." , $data[options] ) : " ";
         $temp .= "]";
         $temp .= $data[memo] ? str_replace( $arr_chars, ".", $data[memo] ) : " ";

         $str1 = $this->pack_string( $temp, $seperator, 38 );
         $str .= $str1;
     }
     return $str;
   }

   ///////////////////////////////////////////////////
   // 아주택배위한 옵션 설정
   function aju_option_pack_string($option_string)
   {
      $temp = str_replace(" ", "", $option_string);

      $length = 10;
      $pos = 0;
      $max_length = 10;
      $str = "";
      $seperator = "\t";
      $str = $temp;
      $j = 0;

      $length = strlen ( $temp );

       if ( $length  > $max_length )
       {
            $pos = 0;
            $str = "";

            while ( $pos < $length )
            {
               if ( $j == 2 ) break;
               $end_pos = $pos + $max_length; // max가 50

               if ( $end_pos > $length )
                  $end_pos = $length;

               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;
               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               $j++;

               //$pos = $end_pos + 1;
               $pos = $pos + $left;

               //if ( $end_pos != $length ) // 줄 바꿈 표시
                   $str .= $seperator;
            }
        }
	else
        {
            $str .= "\t";
            $j=1;
        }

        // 공백 매워 줌
        for ( $count = $j; $count < 2; $count++ )
            $str .= " ". $seperator;
 
        return $str;
   }



   ///////////////////////////////////////////////
   // date: 2006.4.5
   // max_length보다 작거나 같을 경우 seperator를 하나씩 넣어 준다.
   // 정해진 개수로 string 자름
   function pack_string2( $temp, $seperator = "|", $max_length = 44, $max_row = 0 )
   {
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $temp = str_replace( $arr_chars, ".", $temp );

      // 무조건 50자 이하로 나누기
      $length = strlen ( $temp );
        
      $str = $temp;

       ///////////////////////////////////////
       // 클 경우만 탄다
       if ( $length  > $max_length )
       {
            $pos = 0;
            $str = "";
            $j = 0;
           
            //////////////////////////////////////////////// 
            // added by jk 2006.4.5
            // 정해진 max_length마다 seperator를 입력
            while ( $pos < $length )
            {

               // 정해진 개수만큼 돌고 끝냄
               if ( $max_row )
                   if ( $j == $max_row ) break;

               $end_pos = $pos + $max_length; // max가 50

               if ( $end_pos > $length )
                  $end_pos = $length;
             
               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;

               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               //$pos = $end_pos + 1;
               $pos = $pos + $left;
 
               // if ( $end_pos != $length ) // 줄 바꿈 표시
            	   $str .= $seperator;

               $j++;
            }
        }
        else
        {
		// 공백 매워 줌
		if ( $max_row )
		{
		    if ( $max_row != $j )
			for ( $count = $j; $count < $max_row; $count++ )
			    $str .= " ". $seperator;
		    else
			$str .= " ". $seperator;
		}
        }

        return $str; 
   }
 
 

   ///////////////////////////////////////////////
   // 정해진 개수로 string 자름
   function pack_string( $temp, $seperator = "|", $max_length = 44, $max_row = 0 )
   {
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $temp = str_replace( $arr_chars, ".", $temp );

      // 보세나라 bose5546은 늘려 달라 함
      if ( _DOMAIN_ == "bose5546" )
        $max_length = 44;

      // 무조건 50자 이하로 나누기
      $length = strlen ( $temp );
        
      $str = $temp;

       if ( $length  >= $max_length )
       {
            $pos = 0;
            $str = "";
            $j = 0;
           
            //////////////////////////////////////////////// 
            // 정해진 max_length마다 seperator를 입력
            while ( $pos < $length )
            {

               // 정해진 개수만큼 돌고 끝냄
               if ( $max_row )
                   if ( $j == $max_row ) break;

               $end_pos = $pos + $max_length; // max가 50

               if ( $end_pos > $length )
                  $end_pos = $length;
             
               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;

               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               //$pos = $end_pos + 1;
               $pos = $pos + $left;
 
               if ( $end_pos != $length ) // 줄 바꿈 표시
            	   $str .= $seperator;

               $j++;
            }
        }

	// 공백 매워 줌
        if ( $max_row )
        {
            if ( $max_row != $j )
                for ( $count = $j; $count < $max_row; $count++ )
                    $str .= " ". $seperator;
            else
       	        $str .= " ". $seperator;
        }
        else
       	    $str .= " ". $seperator;
       
        return $str; 
   }
 
    // 합포 건수 출력
    function get_pack_count()
    {
       global $connect, $start_date, $end_date,$shop_id, $supply_code, $confirm, $trans_who;
       
       $query_cnt = "select count(*) cnt ";

       $options = " from orders a 
                    where a.order_id != '' ";

	if ($start_date)
	  $options .= " and a.collect_date >= '$start_date 00:00:00' ";
	if ($end_date)
	  $options .= " and a.collect_date <= '$end_date 23:59:59' ";

        ///////////////////////////////////////////
        // shop_id 가 있는 경우
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        ///////////////////////////////////////////
        // supply_code 가 있을 경우
        if ( $supply_code )
           $options .= " and a.supply_id = '$supply_code'";

        ///////////////////////////////////////////
        // 정상, 교환, 맞교환 
        $options .= " and a.status in ( 1, 2, 11 ) 
                      and order_cs in ( 0, 9, 11)";

        //////////////////////////////
        // trans_who가 있는 경우
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        if ( $confirm )
           $options .= " and a.download_date is not NULL";
        else
           $options .= " and a.download_date is NULL";

        $options .= " and pack = seq";	        // 합포인 넘들만 검색

debug("[get_pack_count] $query_cnt . $options");

        $result = mysql_query ( $query_cnt . $options );
        $data = mysql_fetch_array ( $result );

        return $data[cnt];
    }

    ///////////////////////////////////////////////////////////
    // limit_option 이 0 일 경우는 전체 출력 주로 download받을때 사용
    // 검색 기준일이 주문일일 경우 : 
    // 검색 기준일이 송장 입력일
    // pack=0: 합포 검색 안 함
    // pack=1: 합포만 검색 함
    function get_order_list( &$total_rows , $limit_option=0, $pack =0)
    {
	global $connect, $confirm, $trans_who;

        $search_date = "collect_date";
	$line_per_page = _line_per_page;
	$keyword = $_REQUEST["keyword"];
	$page = $_REQUEST["page"];
	$start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
	$end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));
        $supply_code = $_REQUEST["supply_code"];// 공급처
        $shop_id = $_REQUEST["shop_id"];        // 판매처

	$query = "select a.*, b.name as supply_name, c.shop_name as shop_name ";
	$query_cnt = "select count(*) cnt ";

	$options = " from orders a, userinfo b , shopinfo c 
                    where a.order_id != '' 
                      and a.supply_id = b.code
                      and a.shop_id = c.shop_id";

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
        if ( $supply_code )
           $options .= " and a.supply_id = '$supply_code'";

        //////////////////////////////
        // trans_who가 있는 경우
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        ///////////////////////////////////////////
        // 정상, 교환, 맞교환 
        $options .= " and a.status in ( 1, 2, 11 )";
        //$options .= " and a.status in ( 1, 2, 11, 7 )";

        ///////////////////////////////////////////////////////
        // 배송전 취소 요청은 나오는거 맞음 
        // 배송전 취소 요청도 나오지 않음 - jk. 2006-11-10
        // 배송전 취소 완료가 되어야 나오지 않는다 
        // date: 2006.1.6
        $options .= " and a.order_cs not in ( 1, 2, 3, 4, 12 )";

        //////////////////////////////////////////
        // pack check      
        if ( $pack != 2 )
		if ( !$pack )
		   $options .= " and ( a.pack is null or a.pack=0 ) ";		// 합포가 아닌 넘들만 검색
		else
		   $options .= " and a.pack = seq ";	        // 합포인 케이스만 검색


        // download_date에 날짜가 있으면 download안됨
        if ( !$confirm )
           $options .= " and a.download_date is NULL";   // 확인 전
        else
           $options .= " and a.download_date is not NULL"; // 확인 후

        /*
	if ( $pack == 2 )
		$options .= " order by a.pack desc ";
	else
		$options .= " order by a.seq desc ";
        */

        if ( _DOMAIN_ == "mam8872" )
          $options .= " order by a.shop_id, a.recv_name, a.product_name";
        else if ( _DOMAIN_ == "kjplus")
          $options .= " order by a.pack desc, a.product_id";
        else if (_DOMAIN_ == "orange" )
          $options .= " order by a.product_id";
        else if (_DOMAIN_ == "yangpa" )
          $options .= " order by a.pack desc, a.product_id, a.qty desc, a.seq desc";
      	else if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang"  || _DOMAIN_ == "metrocd" || _DOMAIN_ == "newpacosue")
          $options .= " order by a.shop_id, a.options";
        else if ( _DOMAIN_ == "dmnet" 
	)
          $options .= " order by a.pack desc, a.product_id, a.options, a.qty desc";
	else if( _DOMAIN_ == "r2046008") 
          $options .= " order by a.order_date, a.pack desc";
	else if( _DOMAIN_ == "nak21") 
          $options .= " order by a.product_id, a.options, a.qty desc";
        else if (  _DOMAIN_ == "ezadmin" 
              	or _DOMAIN_ == "imjlove" 
              	or _DOMAIN_ == "soocargo" 
              	or _DOMAIN_ == "jbtech" 
              	or _DOMAIN_ == "lrj0430" 
          )
          $options .= " order by a.pack desc, a.product_id, a.seq desc ";
        else
          $options .= " order by a.pack, a.product_id, a.options";

        if ( !$limit_option )
        {
	   $starter = $page ? ($page-1) * $line_per_page : 0;
	   $limit = " limit $starter, $line_per_page";
        }

	////////////////////////////////////////////////// 
	// total count 가져오기
	$list = mysql_fetch_array(mysql_query($query_cnt . $options, $connect));
	$total_rows = $list[cnt];

 //       if ( _DOMAIN_ == "shala")
//echo $query. $options. $limit;

	$result = mysql_query($query . $options . $limit, $connect);
	return $result;
    }

    function set_format()
    {
       global $connect;
       global $order_type;

       $query = "delete from set_format";
       mysql_query ( $query, $connect);

        // 대한 통운 data type
        $arr_name = array ( 
                     "order_name"        =>"주문자",
                     "box"               =>"박스",
                     "recv_name"        =>"수령자",
                     "recv_zip"         =>"우편번호",
                     "recv_address"   =>"수령지",        
                     "shop_name"      =>"사이트이름",
                     "recv_tel"       =>"수령자연락처",
                     "recv_mobile"    =>"수령자핸드폰",
                     "empty1"         =>"공백",
                     "empty2"         =>"공백",
                     "empty3"         =>"공백",
                     "empty4"         =>"공백",
                     "empty5"         =>"공백",          
                     "empty6"         =>"공백",
                     "count"          =>"송장개수",
                     "seq"            =>"주문번호", 
                     "trans_no"       =>"운송장번호",
                     "qty"            =>"수량",          
                     "ds_qty"         =>"수량",          
                     "empty7"         =>"공백",          
                     "empty8"         =>"공백",
                     "empty9"         =>"공백",
                     "memo"           =>"주문메모",
                     "options"        =>"옵션",
                     "deliv_price"    =>"배송비",
                     "deliv_who"      =>"선착불구분",
                     "product_name"   =>"상품명",
                     "collect_date"   =>"발주일",
                     "order_type"=>"주문타입",
                     "qty_product_name"=>"수량+품목",
                     "amount" => "금액",
                    );

       foreach ( $_REQUEST as $key=>$value )
       {
	  if ( $key == "popup1" ) continue;
	  if ( $key == "popup2" ) continue;
	  if ( $key == "popup3" ) continue;
	  if ( $key == "popup4" ) continue;

          if ( $value )
          {
             $query = "insert set_format set id = '$key' ";

             if ( $key != "template" && $key != "action" && $key != "PHPSESSID" )
             {
                $query .= ", order_num ='" . $value . "', name='" . $arr_name[$key] . "'";
                mysql_query ( $query, $connect);
             }
          }
       }

       $this->redirect ( "?template=D401" );
       exit;
    }

   /////////////////////////////////////////////
   // download format의 설정값을 가져온다
   function get_format()
   {
      $lib_name = "lib/ez_trans_lib_" . _DOMAIN_ . ".php";

      //////////////////////////////////////////
      // 
      // domain별로 trans_lib를 생성함..같은 택배사를 사용한다고 해도
      // 요구조건은 각 사이트 별로 다르다.
      // date: 2006.1.2
      // jk.ryu
      // ez_trans_lib_[DOMAIN].php
      // 
      if ( file_exists( $lib_name) )
          require $lib_name;
      else 
          require "lib/ez_trans_lib.php";

      global $trans_corp;

      $val = "format_" . $trans_corp;

      if ( empty( $$val ) )
          $val = "format_default";

      return $$val; 
   }
  
   // 배송 정보 
   function  get_sender()
   {
        global $connect;
        $sql  = "select * from userinfo where id = '$_SESSION[LOGIN_ID]'";
        $result = mysql_query($sql, $connect);
        $list = mysql_fetch_array($result);

        return $list[boss];
   }

   function get_product_name_option( $product_id, &$product_name, &$product_option )
   {
       global $connect;
       $query = "select name,options from products where product_id='$product_id'";
       $result = mysql_query ( $query );
       $data = mysql_fetch_array( $result );

       $product_name = $data[name];
       $product_option = $data[options];
   }

   function get_total_price( $pack )
   {
	global $connect;
	$query  = "select shop_price,qty from orders where pack=$pack";
	$result = mysql_query ( $query, $connect );
	while ( $data = mysql_fetch_array ( $result ) )
        {
	    $total = $total + ($data[shop_price] * $data[qty]);
	}
	return $total;
   }
}

?>
