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

      
      if ( $_SESSION[LOGIN_LEVEL] == 0 )  // °ø±ÞÃ¼
         $supply_code = $_SESSION[LOGIN_CODE];
      else // ³»ºÎ »ç¿ëÀÚ
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

      $transaction = $this->begin("ÁÖ¹®È®ÀÎ");
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
      // download format¿¡ ´ëÇÑ Á¤º¸¸¦ °¡Á®¿Â´Ù
      // 
      $result = $this->get_format();
      $download_items = array (); 

      foreach ( $result as $key=>$name )
      {
         $download_items[$key] = $name;
      }

      $handle = fopen ($saveTarget, "w");

      /*****************************************/
      // ÇÕÆ÷¸¸ download¹Þ´Â´Ù
      $result = $this->get_order_list( &$total_rows , 1, 1); 

      ////////////////////////////////////////
      // writting datas to file
      $i = 1;
      $header = "false";	// header´Â Ãâ·ÂµÇÁö ¾Ê¾ÒÀ½

      // header Ãâ·Â ºÎºÐ
      // ´ëÇÑ Åë¿îÀº headerÃâ·ÂÇÏÁö ¾Ê´Â´Ù.
      // ¾ÆÁÖ ÅÃ¹èµµ header Ãâ·ÂÇÏÁö ¾Ê´Â´Ù.
      // Æ®¶ó³Ý ÅÃ¹èµµ header Ãâ·ÂÇÏÁö ¾Ê´Â´Ù.
      if ( $trans_corp == '30022' 
        or $trans_corp == '30084' 
        or $trans_corp == '30050'	// ¾ÆÁÖ ÅÃ¹è
        or $trans_corp == '30074'	// Æ®¶ó³Ý ÅÃ¹è
      )
      $trans_header = "false";	        // ÅÃ¹è»ç Çì´õ ¾øÀ½

      if ( _DOMAIN_ == "nak21" )
          $trans_header = "true";	        // mantanÀº ´ëÅë ÅÃ¹è»ç Çì´õ ¾øÀ½ - 2007.2.22 - jk.ryu
	
      if ( _DOMAIN_ == "mantan" )
          $trans_header = "false";	        // mantanÀº ´ëÅë ÅÃ¹è»ç Çì´õ ¾øÀ½ - 2007.2.22 - jk.ryu

      while ( $data = mysql_fetch_array ( $result ) )
      {
         $is_pack_disp = "true";	// ÇÕÆ÷ ºÎºÐ Ãâ·ÂµÆÀ½À» ±â·Ï

         if ( $trans_header == "false" )
         {
	     $buffer .= "<html><table border=1>";
             $header = "true";	// header°¡ Ãâ·ÂµÆÀ½À» ±â·Ï
         }
         else
         {
  	    if ( $i == 1 )
	    {
	        $buffer .= "<html><table border=1><tr>";
	        foreach ( $download_items as $key=>$value )
	            $buffer .= "<td>" . $value. "</td>";

	        $buffer .= "</tr>\n";
                $header = "true";	// header°¡ Ãâ·ÂµÆÀ½À» ±â·Ï
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
      // ÇÕÆ÷¸¦ Á¦¿ÜÇÑ data¸¦ download¹Þ´Â´Ù
      $result = $this->get_order_list( &$total_rows , 1); 

      ////////////////////////////////////////
      // writting datas to file
      $i = 1;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // ÇÕÆ÷ Ãâ·Â ¾ÈµÈ °æ¿ì, headerÃâ·Â

         // ÇÕÆ÷ Ãâ·Â ¾ÈµÈ °æ¿ì, headerÃâ·Â x
 
	 if ( $i == 1 && $header == "false" )
	 {
	    $buffer .= "<html><table border=1><tr>\n";
	    foreach ( $download_items as $key=>$value )
	       $buffer .= "<td>". $value. "</td>";
	    $buffer .= "</tr>\n";
            $header = "true";	// header°¡ Ãâ·ÂµÆÀ½À» ±â·Ï
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
      // ÆÄÀÏ º¯È¯À» ÇØ¾ß ÇÒ °æ¿ì ¿©±â¼­ ÇØ¾ß ÇÔ
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
      header("Content-Disposition: attachment; filename=¿À´ÃÀÇ¹ßÁÖ_´ë¹Ú³ª¼¼¿ä.xls");
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
          $worksheet =& $workbook->addWorksheet('¹ß¼ÛÈ®ÀÎ');

      //while ( $data = mysql_fetch_array ( $result ) )
      // {
      //   $download_items[$data[id]] = $data[name];
      // }

      foreach ( $result as $key=>$name )
      {
         $download_items[$key] = $name;
      }

      //////////////////////////////////////////////
      // step 1. ÇÕÆ÷ data send
      $result = $this->get_order_list( &$total_rows , 1, 1); 
      $this->write_excel ( $worksheet, $result, $download_items );
      $rows = $total_rows;

      //////////////////////////////////////////////
      // step 1. ÀÏ¹Ý data send
      $result = $this->get_order_list( &$total_rows , 1); 
      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
      */
   }
  
   /////////////////////////////////////////////////////// 
   // excel¿¡ write ÇÔ
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

      // myking ¿¹¿Ü Á¶°Ç
      if ( _DOMAIN_ == "myking" )
          if ( $key == "hanjin_product" )
              $key = "hanjin_product2";

//echo $key;

      switch ( $key )
      {	
	   // for eleven
           case "eleven_code":
		// date : 2007.3.20 ÀüºÎ 2·Î ³ª¿À°Ô ÇØ´Þ¶ó ¿äÃ» - jk.ryu
		//if ( 
		//$data[shop_id] == 10002 
		//or $data[shop_id] == 10102 
		//or $data[shop_id] == 10165
		//)
		//	return 1;
		//else
			return 2;
           break;
           // mam8872¸¦ À§ÇÑ ºÎºÐ
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
                 return "ÇÊ½ºÅ¸"; 
	       else if (
		    $data[shop_id] == 10201 or
		    $data[shop_id] == 10202 or
		    $data[shop_id] == 10125
		)
                    return "¾ÆÀÌ·»"; 
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
		// 2007.11.26 10002´Â 051-895-8872·Î º¯°æ ¿äÃ»
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
		return $data[pack] ? "ÇÕÆ÷": "";
           break;

           // yellow cap : 30057
           case "yellow_seq":
		return $data[pack] ? $data[pack] : $data[seq];
           break;

           /////////////////////////////////////////////
           // »ï¼¶ÅÃ¹è 
           case "hth_trans_price":
               if ( $data[trans_who] == "¼±ºÒ" )
                   return 2200;
               else
                   return 2500;
               break;
          case "hth_trans_who":
               if ( $data[trans_who] == "¼±ºÒ" )
                   return "½Å¿ë";
               else
                   return "ÂøºÒ";
               break;
           
           /////////////////////////////////////////////
           // ¾ÆÁÖÅÃ¹è
           case "aju_trans_price":
               if ( $data[trans_who] == "¼±ºÒ" )
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
                // 2¹ø »óÇ° ºÎÅÍ 6¹ø »óÇ° ±îÁö Ãâ·Â
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
                //  ) // soocargo 5°³
                //  return " \t \t \t \t \t ";
                
                if ( _DOMAIN_ == "ds153" ) // 9°³
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
             return "°¡¹æ";
             break;

           /////////////////////////////////////////////
           // tranet
           case "tranet_trans_who2":
               if ( $data[trans_who] == "¼±ºÒ" )
                   return "½Å¿ë";
               else
                   return "ÂøºÒ";
           break;
           case "tranet_amount":
           case "tranet_trans_who":
               if ( $data[trans_who] == "¼±ºÒ" )
                   return 1;
               else
                   return 2;
           break;
           case "tranet_size":
               if ( $data[trans_who] == "¼±ºÒ" )
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
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
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
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
		{
			if ( _DOMAIN_ == "dmnet" )
                    		$temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]) . "/" . $data[options] . "/" . $data[memo] ) ;
			else
                    		$temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
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
                  $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $product_name) . "-" . str_replace( $arr_chars, " ", $product_option ) ;
                }
                else
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $product_name) . "-" . str_replace( $arr_chars, " ", $data[options]) ;
                
              }
              return $temp;
           break;
           case "station":
               if ( _DOMAIN_ == "jyms" )
                   return "1030001";
                   //return "1013032"; // ÄÚµåº¯°æ 2006.11.23 -jk ¹Ì°æ¾¾ ¿À«Š
           break;
           case "logen_code1":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "l"; break;
		    case "ÂøºÒ": return "l"; break;
                }
           break;
           case "logen_code2":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "1"; break;
		    case "ÂøºÒ": return "2"; break;
                }
           break;
           case "hyundae_code":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "3"; break;
		    case "ÂøºÒ": return "2"; break;
                }
           break;
           // °í·ÁÅÃ¹è Á¦ÁÖµµ Ã¼Å©
           case "jeju_check":
             //echo "$data[recv_zip]";
             if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "03"; break;
		    case "ÂøºÒ": return "02"; break;
                }
             else
               return "";

           break;

           // Á¦ÁÖµµ´Â 5000¿ø
           case "jeju_price":

             if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
		return 5000;
             else
                return 2500;

           break;
           //////////////////////////////////////////////
           // ´ëÇÑ Åë¿î ¼±ÂøºÒ Ãß°¡
           case "daehan_amount":
             if ( _DOMAIN_ == "eleven" )
             {
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": 
			if ( _DOMAIN_ == "partyparty" )
                            return 2000;
                        else
                            return "1900"; 
                    break;
		    case "ÂøºÒ": return "2500"; break;
                }
             }
	     else if ( _DOMAIN_ == "younggun" )
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "1700"; break;
		    case "ÂøºÒ": return "2500"; break;
                }
             else
             {
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "0"; break;
		    case "ÂøºÒ": return "2500"; break;
                }
             }
           break;
           case "daehan_trans_who":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "03"; break;
		    case "ÂøºÒ": return "02"; break;
                }
           break;
           case "daehan_trans_who2":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "03"; break;
		    case "ÂøºÒ": return "02"; break;
                }
           break;

           //////////////////////////////////////////////
           // femiculine ÁÖ¼Ò
           case "recv_address2":
                 return $data[recv_address];
           break;

           //////////////////////////////////////////////
           // ¿ìÃ¼±¹
	   case "post_trans_who":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "Áï³³"; break;
		    case "ÂøºÒ": return "¼öÃëÀÎºÎ´ã"; break;
                }
           break;
           case "post_product":
              if ( $data[pack] )
                return $this->get_post_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

		// »çÀºÇ° check 2006.4.18 - jk
		if ( $data[gift] )
			$temp .= "\n»çÀºÇ°: " . $data[gift];

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
		    return "ÇÇ±â";
		else
		    return "¸¶°í";
	     } 
             else if ( _DOMAIN_ == "jyms" )
               return "Áü½ºÀÎÅÍ³×¼Å³Î";
             else if ( _DOMAIN_ == "ds" )
               return "´ë¼ºÆ®·¹ÀÌµùî";
             else if ( _DOMAIN_ == "kdykiss" )
               return "M½´Áî";
             else if ( _DOMAIN_ == "lsy1115" )
               return "½ÅÇÑÅë»ó";
             else if ( _DOMAIN_ == "peggy" )
               return "Peggy";
             else if ( _DOMAIN_ == "rapa1196" )
               return "¼­Áø¾îÆÐ·²";
             else if ( _DOMAIN_ == "younggun" )
               return "¿µ°Ç";
             else if ( _DOMAIN_ == "mangosteen" )
               return "¢ß¸Á°í½ºÆ¾";
             else if ( _DOMAIN_ == "rianrose" )
               return "(ÁÖ)¸®¾ÈÀÎÅÍ³»¼Å³Î";
             else if ( _DOMAIN_ == "tne" )
               return "T&E";
             else if ( _DOMAIN_ == "honny" )
               return "È£»§°É";
             else if ( _DOMAIN_ == "lbgjjang" )
               return "³ªµéÀÌ/$data[shop_name] ";
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
	   case "product_type":	 return "ÀÇ·ù"; 	break;
	   case "box_type":	 
             if ( $data[trans_who] == "¼±ºÒ" )
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
                	$temp = $data[order_name] . "/" . $cnt."°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $vOption );
		elseif ( _DOMAIN_ == "misogirl" )
                	$temp = $cnt."°³:" . str_replace( $arr_chars, " " , str_replace( $arr_chars, " ", $vOption ));
		elseif ( _DOMAIN_ == "hj2526" )
                	$temp = $cnt."°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));
		elseif ( _DOMAIN_ == "emenes" )
                	$temp = $cnt."°³:" . str_replace( $arr_chars, " " , str_replace( $arr_chars, " ", $data[options] ));
		else
                	$temp = $cnt."°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $vOption );
		return $temp;
           break;


           // panty bank¸¸ »ç¿ëÇÔ
           // ÆÐ¼Ç70 ¸¸ »ç¿ëÇÔ
	   case "cj_product_name2" :
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $cnt = $data[qty] - $cnt;
                $temp = $cnt."°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", "[" . $data[options]  . "]" . $data[memo]);
		return $temp;
           break;
           //////////////////////////////////////////////
           // tranet ÅÃ¹è
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

                 $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
                 $str1 = $this->pack_string( $temp, ";", 50 );
                 $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,";",50) ) . "";

                 return $str1;
              }
		break;
	   case "tranet_box":
	        if ($data[trans_who] == "ÂøºÒ") return "2";
	        else if ($data[trans_who] == "¼±ºÒ") return "1";
		break;
           case "tranet_trans_who":
	        if ($data[trans_who] == "ÂøºÒ") return "2";
	        else if ($data[trans_who] == "¼±ºÒ") return "3";
                break;
           //////////////////////////////////////////////
           // kgb ÅÃ¹è
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
                  $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                  $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
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
                  $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                  $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;
                
                return $temp;
              }
		break;
           case "kgb_trans_who": // ¼±ºÒ: 3, ÂøºÒ: 2
	        if (trim($data[trans_who]) == "ÂøºÒ") return "2";
	        else if (trim($data[trans_who]) == "¼±ºÒ") return "3";
           	break;
           case "kgb_box":	// ¼±ºÒ: box=1, ÂøºÒ :2
	        if ($data[trans_who] == "ÂøºÒ") return "2";
	        else if ($data[trans_who] == "¼±ºÒ") return "1";
           	break;
           // 5°³Â¥¸® »óÇ° = ½Å¼¼°èÅÃ¹è
           case "products_5":

              break;

           // ÇÑÁø ÅÃ¹è
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
                      $temp = $cnt . "°³: " . $temp . $option; 
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
                      	$temp  = $cnt . "°³: " . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]));
			$temp .= str_replace( $arr_chars, " ", $data[options]);
                    }
                    else if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang" || _DOMAIN_ == "newpacosue" || _DOMAIN_ == "metrocd")
                    {
                    	if ( $cnt > 1 )
                        	$temp = str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."°³";
                    	else
                        	$temp = str_replace( $arr_chars, " ", $data[options]);
                    }
                    else
                    {
                      	if ( _DOMAIN_ == "shala" || _DOMAIN_ == "nicekang" || _DOMAIN_ == "newpacosue" || _DOMAIN_ == "metrocd")
                      	{
                          	if ( $cnt > 1 )
                              		$temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."°³";
                      	}
                      	else 
                        {
                          	$temp = str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) . " X" . $cnt ."°³";
                        }
                    }
                }

		if ( _DOMAIN_ == "younggun" )
                	return "[ÃÑ". $cnt. "°³]". $temp;
		else
                	return $temp;

                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;
           // ÇÑÁø ÅÃ¹è
           case "hanjin_product2":
              if ( $data[pack] )
                return $this->get_hanjin_pack2( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                if ( $_SESSION[STOCK_MANAGE_USE] )
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
                else
                    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;

                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;
           // ÇÑÁø ÅÃ¹è
           case "hanjin_product":
              if ( $data[pack] )
                return $this->get_hanjin_pack( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "°³:" . $this->get_product_name($data[product_id]) . class_D::get_product_option($data[product_id]);
                $temp = $this->pack_string( $temp, " \t", 100, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;

           case "hanjin_product5":	// ¿É¼Çº° ¹ßÁÖ¸¦ »ç¿ëÇÏÁö ¾Ê´Â ¾÷Ã¼ÀÇ °æ¿ì
              if ( $data[pack] )
                return $this->get_hanjin_pack5( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options];
                $temp = $this->pack_string2( $temp, " \t", 50, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }

              break;

           case "hanjin_product4":	// ¿É¼Çº° ¹ßÁÖ¸¦ »ç¿ëÇÏÁö ¾Ê´Â ¾÷Ã¼ÀÇ °æ¿ì
              if ( $data[pack] )
                return $this->get_hanjin_pack4( $data[seq] ) ;
              else
              {
                $cnt = class_E::get_part_cancel_count ( $data[seq] );
                $temp = $data[qty] - (int)$cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = $this->pack_string( $temp, " \t", 100, 2 );
                return $temp;
                //return $this->pack_string( $temp, "``$", 50 );
              }
                 break;

           case "etc":

	         if ( $data[order_cs] == 5 or $data[order_cs] == 11)
		     $str_result = "[[±³È¯]] ";
	         if ( $data[order_cs] == 9 )
		     $str_result = "[[¸Â±³È¯]] ";

		//=================================================
		//
		// ±³È¯ÀÏ °æ¿ì
		// date: 2007.5.2 -jk
		if ( substr($data[order_id],0,1) == "C" )
			$str_result = "[[±³È¯]] ";


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
              return  "ÇÕÆ÷";
           break;
           case "supply_code":
              return  $this->get_supply_name2 ( $data[$value] );
           break;
           case "enable_sale":
              return   $data[enable_sale] ? "ÆÇ¸Å°¡´É" : "ÆÇ¸ÅºÒ°¡";
           break;
           case "memo_only": // for yangpa °¡Àå ¹Ø¿¡ ÁÙ¸¸ memo Ãâ·Â
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
                     return $this->get_total_count2( $data[pack] ) . "ÇÕÆ÷";
                 else
                     return "";
              }
              else
                 return "[ÃÑ". $data[qty] . "°³]";
           break;

           case "total_count": 
              if ( $data[pack] )
                 if ( $data[pack] == $data[seq] )  // for yangpa
                   return $this->get_total_count( $data[pack] ) . "ÇÕÆ÷";
                 else
                   return "";
              else
              {
                 return "[ÃÑ". $data[qty] . "°³]";
              }
           break;
           case "memo":
              if ( $data[pack] )
                 return $this->get_total_count( $data[pack] ) . "ÇÕÆ÷";
              else
              {
                 return str_replace( $arr_chars , ".", $data[product_name] );
              }
           break;
           case "memos":    // memo¸¸ ¸ðÀ½ aju_old_memo¿Í ºñ½Á
              if ( $data[pack] )
                 return $this->get_total_count( $data[pack] ) . "ÇÕÆ÷";
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
                $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
                $temp = str_replace( $arr_chars , ".", $temp );
                return $this->pack_string( $temp, " \t\t\t\t",42 );
              }
           break;
           // box on °ü·Ã
	   // date: 2006.12.7 jk.ryu
	   // for younggun
           case "boxon_trans_who":
		if ( $data[trans_who] == "¼±ºÒ" )
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
			return "·ÎÁ¨ÅÃ¹è ¾È¼º¼¾Å¸3Ãþ ¿µ°Ç¹°·ù¼¾Å¸ ÄÚµå101";
		else if (_DOMAIN_ == "honny" )
			return "·ÎÁ¨ÅÃ¹è ¾È¼º¼¾Å¸3Ãþ È£»§°É ÄÚµå101";
		else if (_DOMAIN_ == "mangosteen" )
			return "·ÎÁ¨ÅÃ¹è ¾È¼º¼¾ÅÍ 3Ãþ ¢ß¸Á°í½ºÆ¾ ¹°·ù ¼¾ÅÍ (ÄÚµå101)";
		else if (_DOMAIN_ == "rianrose" )
			return "°æ±â ¾È¼º½Ã ·ÎÁ¨ÅÃ¹è ¾È¼º¼¾Å¸ 3Ãþ (ÁÖ)¸®¾ÈÀÎÅÍ³»¼Å³Î ¹°·ù¼¾Å¸ (ÄÚµå101)";
	
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

	   // »ç°¡¿Í ÅÃ¹è
           case "kdykiss_memo1";
               return "  ( ¢¾ ¹ÝÇ°,±³È¯½Ã ÆÇ¸ÅÃ³¿¡ ";
               break;
           case "kdykiss_memo2";
               return " ²À ¿¬¶ô ÈÄ ¹Ý¼Û )";
               break;
	   // 2006.12.12
	   case "category":
		if ( _DOMAIN_ == "kdykiss" 
		or _DOMAIN_ == "lsy1115" )
			return "½Å¹ß";
		else
			return "ÀÇ·ù";
		break;
	   case "sw_trans_who":
		switch ( $data[trans_who] )
                {
                    case "¼±ºÒ": return "3"; break;
		    case "ÂøºÒ": return "2"; break;
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
			   // mago ¿ø»ó º¹±Í ÇÔ 2008.1.24 - jk
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
                $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options];
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
           // ¹­À½ »óÇ° Ãâ·Â
           case "aju_old_product3":
              if ( $data[pack] )
              {
                // ÇÕÆ÷°Ç Ã³¸® ºÎºÐ
                return $this->get_aju_old_pack3( $data[seq] ) ;
              }
              else
              {
                // ¹­À½ »óÇ°ÀÎÁö Ã¼Å© ÇÏ´Â ºÎºÐÀ» Ãß°¡
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

                  $temp = $cnt . "°³:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));
               
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

                  $temp = $cnt . "°³:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));
               
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
                     $str_result = "[[±³È¯]] ";
                  if ( $data[order_cs] == 9 )
                     $str_result = "[[¸Â±³È¯]] ";
              }

              // if ( $_SESSION[STOCK_MANAGE_USE] )
		      if ( $data[pack] )
			return $data[gift] . " " . $str_result . $this->get_aju_pack_memo( $data[seq] ) ;
		      else
		      {
                        if ( $data[packed] )
                        {
                          // ¹­À½ »óÇ°ÀÎ °æ¿ì
                          // date: 2006.11.10
			  if ( _DOMAIN_ != "younggun" 
                           and _DOMAIN_ != "honny"
                          )
                          	$qty_str= "[ÃÑ" . count( split ( "," , $data[pack_list] ) ) * $data[qty] . "°³]";

			  $temp = $data[message]. $data[memo];
		  	  $temp = str_replace( $arr_chars, ".", $temp );
                          $temp = $temp ? $temp : "¸Þ¸ð¾øÀ½";
                        }
                        else
                        {
                          // ¹­À½ »óÇ°ÀÌ ¾Æ´Ñ °æ¿ì
                          // date: 2006.11.10
			  if ( _DOMAIN_ != "younggun"
                           and _DOMAIN_ != "honny"
                          )
                          	$qty_str= "[ÃÑ" . $data[qty] . "°³]";

			  if ( _DOMAIN_ == "mago" or _DOMAIN_ == "peggy" )
			        $price_str = "[ÃÑ" . number_format( $data[shop_price]) . "¿ø]";

			  $temp = $data[message]. $data[memo];
		  	  $temp = str_replace( $arr_chars, ".", $temp );
                          
                          // ¸Þ¸ð¿¡ 

			  if ( _DOMAIN_ != "nak21" )
                          	$temp = $temp ? $temp : "¸Þ¸ð¾øÀ½";
                        }
                        // Çö´ëÅÃ¹è´Â 25ÀÚ ÀÌ»ó ¾È³ª¿È
                        global $trans_corp;

			// ´çºÐ°£ »©´Þ¶ó ÇÔ 2006.12.15 - jk
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
		return "[ÃÑ" . $data[qty] . "°³ ] $temp";
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
		// ¹­À½ »óÇ° ¿©ºÎ check
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
			$temp = $cnt . "°³:" . str_replace( $arr_chars,"", $product_name);
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
		
                $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options];
                return $this->pack_string( $temp, "``$", 50 );
              }
           break;
           case "options":  // ¿É¼Ç »çÇ×
              return $data[options];
           break;
           case "box":
              return "1";
           break;
           case "trans_who2":
              if ( _DOMAIN_ == "jyms" )
              {
	        if ($data[trans_who] == "ÂøºÒ") return "002";
	        else if ($data[trans_who] == "¼±ºÒ") return "003";
	        else return "002";
              }
              else if ( _DOMAIN_ == "ds153" )
              {
	        if ($data[trans_who] == "ÂøºÒ") return "2";
	        else if ($data[trans_who] == "¼±ºÒ") return "3";
	        else return "2";
              }
	      if ($data[trans_who] == "ÂøºÒ") return "'002";
	      else if ($data[trans_who] == "¼±ºÒ") return "'003";
	      else return "'002";

	      break;
	   case "air_pay":
		return "1";
              break;
           case "trans_who_yellow":
	      if ($data[trans_who] == "ÂøºÒ") return "002";
	      else if ($data[trans_who] == "¼±ºÒ") return "003";
	      else return "002";
	      break;
           case "trans_who_yellow2":
	      if ($data[trans_who] == "ÂøºÒ") return "002";
	      else if ($data[trans_who] == "¼±ºÒ") return "";
	      else return "002";
	      break;
           case "deliv_who":
              // Á¦ÁÖÀÏ °æ¿ì ¹«Á¶°Ç ÂøºÒ 4000¿ø - ¾çÆÄ ¿äÃ»
              if ( _DOMAIN_ == "yangpa" )
              {
                if ( preg_match ("/^(697)-\d/", $data[recv_zip]) )
                  return "ÂøºÒ";

                if ( preg_match ("/^(690)-\d/", $data[recv_zip]) )
                  return "ÂøºÒ";
              }

              if ( $data[trans_who] == "¼±ºÒ" )
                 return "½Å¿ë";
              else
                 return "ÂøºÒ";
               break;
           case "deliv_price":
              return $_SESSION[BASE_TRANS_PRICE];
               break;
           case "ds_qty":
              return "1";
               break;
           case "amount":
               // lovehouse´Â ¼±ºÒ 2000¿ø - 2006.11.6 - jk.ryu
               if ( _DOMAIN_ == "lovehouse" 
                 or _DOMAIN_ == "ymy2875" )
               {
                 if ( $data[trans_who] == "¼±ºÒ" )
                   return 2000;
                 else
                   return 2500; 
               }
               else if ( _DOMAIN_ == "kkt114" )
               {
                 if ( $data[trans_who] == "¼±ºÒ" )
                   return 2300;
                 else
                   return 2300; 
               }
	       else if ( _DOMAIN_ == "ozen" )
               {
                 if ( $data[trans_who] == "¼±ºÒ" )
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
           case "cj_qty_product_only": // ¼ö·® + ¿É¼Ç °³ÇàÀº $
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

                   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
                   $temp .= "-" . str_replace( $arr_chars, ".", $product_option );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . $data[options] );
                 }
                 $str1 = $temp;
                 // $str1 = $this->pack_string( $temp, "\$", 46 );
                 return $str1;
              }
               break;

           // »óÇ° Ãâ·Â ¼ø¼­°¡ °³¼ö + »óÇ°¸í + ¿É¼Ç
           case "qty_product_only4": // ¼ö·® + ¿É¼Ç »óÇ° ¿É¼Ç¿¡ ³»¿ëÀÌ ¾øÀ» °æ¿ì ÁÖ¹®ÀÇ ³»¿ëÀÌ Ãâ·ÂµÊ
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

                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $product_option . "¢¾" . $cnt );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   // $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . $data[options] );
                   if ( $cnt > 1 ) 
                     $temp  = "¢¾" . $cnt; 
                   else
                     $temp  = $cnt; 

                   $temp .= "°³:";
                   $temp .= str_replace( $arr_chars, ".", $product_name . "-" . $data[options] );

                }

                 $str1 = $this->pack_string( $temp, "|", 46 );

                 return $str1;
              }


           // »óÇ° Ãâ·Â ¼ø¼­°¡ »óÇ°¸í + ¿É¼Ç + °³¼ö
           case "qty_product_only3": // ¼ö·® + ¿É¼Ç »óÇ° ¿É¼Ç¿¡ ³»¿ëÀÌ ¾øÀ» °æ¿ì ÁÖ¹®ÀÇ ³»¿ëÀÌ Ãâ·ÂµÊ
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

                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $product_option . "¢¾" . $cnt );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   // $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . $data[options] );
                   $temp = str_replace( $arr_chars, ".", $product_name . "-" . $data[options] );
                   if ( $cnt > 1 ) 
                     $temp  .= "¢¾" . $cnt; 
                   else
                     $temp  .= $cnt; 
                   $temp .= "°³";
                 }

                 $str1 = $this->pack_string( $temp, "|", 46 );
                 // $str1 .= "   " . $product_option . "|";
                 //$str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|",46) ) . "";

                 return $str1;
              }
           break;

           // ÇÑÁÙ¿¡ °³¼ö + »óÇ° + ¿É¼ÇÀÌ Ãâ·Â
           case "qty_product_only2": // ¼ö·® + ¿É¼Ç »óÇ° ¿É¼Ç¿¡ ³»¿ëÀÌ ¾øÀ» °æ¿ì ÁÖ¹®ÀÇ ³»¿ëÀÌ Ãâ·ÂµÊ
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

                   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . "-" . $product_option );
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
                    	$temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . $data[options] );
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


           case "qty_product_only": // ¼ö·® + ¿É¼Ç »óÇ° ¿É¼Ç¿¡ ³»¿ëÀÌ ¾øÀ» °æ¿ì ÁÖ¹®ÀÇ ³»¿ëÀÌ Ãâ·ÂµÊ
              if ( $data[pack] )
	      {
                 //$str_buffer =  $this->get_pack_memo( $data[seq] ) ;
                 $str_buffer =  $this->get_pack_product_only( $data[seq] ) ;

		if ( _DOMAIN_ == "jsclub" )
		    $str_buffer .= "(" . $this->get_total_price( $data[pack] ). "¿ø ) ";

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

                   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
                 }
                 else
                 {
                   $product_name = $this->get_product_name($data[product_id]);
                   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name . $data[options] );
                 }

		if ( _DOMAIN_ == "jsclub" )
		    $temp .= "(" . number_format( $data[shop_price] * $data[qty] ) . "¿ø ) ";

                 $str1 = $this->pack_string( $temp, "|", 46 );
                // $str1 .= "   " . $product_option . "|";
                 $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,"|",46) ) . "";

                 return $str1;
              }
           break;

           // ÁýÇÏ ¿µ¾÷¼Ò 
           case "yellow_collect_m":
             if (_DOMAIN_ == "lovehouse" )
               return "8070036";
           break;

           // ÁýÇÏ ÁöÁ¡ 
           case "yellow_collect_ap":
             if (_DOMAIN_ == "lovehouse" )
               return "807";
           break;

           // ´ç´ç»ç¿ø
           case "yellow_worker":
             if (_DOMAIN_ == "lovehouse" )
               return "8070036";
           break;
           ///////////////////////////////////////////
	   //
           // yellow ÅÃ¹è »óÇ° ¸®½ºÆ® Ãâ·Â 2¹ø
           // ÁÙÀ» ³»¸®´Â ½Ã±×³ÎÀº ; ÀÓ
	   // º¹¼ö ³»Ç° ¾ç½Ä
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

                 $temp = str_replace( $arr_chars, ".", stripslashes( $cnt . "°³:" . $this->get_product_name($data[product_id]))) . "->";
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
		// ¾çÆÄ´Â »óÇ°¿¡ ÃÑ °³¼ö°¡ ³ª¿Â´Ù
		// jk.ryu
		// 2006.12.12
		// ÃÑ °³¼ö¸¦ ±â·ÏÇÔ
		$temp = "";

                // get_total_count¿¡¼­ g_count°ªÀÌ °áÁ¤µÈ´Ù
                // ÇÕÆ÷°¡ ¾Æ´Ñ°æ¿ì g_count°ªÀº 1
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
                   $temp .= str_replace( $arr_chars, ".", stripslashes( $cnt . "°³:" . $this->get_product_name($data[product_id])));

		 $temp .= "[" . $data[options] ? str_replace( $arr_chars, "." , $data[options] ) : " " . "]";

		if ( _DOMAIN_ == "lovehouse" )
			$temp .= "/" . $data[options];

                 if ( _DOMAIN_ == "yangpa" )
                   $temp .= "X ". $cnt;

		 // Á¶Ã¶ÈÆ¾¾ ¿äÃ»À¸·Î ¸Þ¸ð ³ª¿È - jk.ryu 12.12
                 if ( _DOMAIN_ != "yangpa" )
		   if ( $this->g_memo != $data[memo] )
		   {
                     $temp .= $data[memo] ? str_replace( $arr_chars, ".", $data[memo] ) : " ";
		     $this->g_memo = $data[memo];
		   }

		 //----------------------------------------------------------
		 // 
		 // °¡Àå ¸¶Áö¸· ÀÚ·á¿¡ ¸Þ¸ð¸¦ ³Ö´Â´Ù
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

           // ÇÕÆ÷°¡ ÇÑ°Ç¾¿ Ãâ·ÂµÇ´Â ¿É¼Çº° ¹ßÁÖ¿ë
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

                 $temp = str_replace( $arr_chars, ".", $cnt . "°³:$product_name - $product_option" );

                 // return $this->pack_string( $temp, ";" );
 		 return $temp;
              //}
  	      break;


           ///////////////////////////////////////////
           // yellow ÅÃ¹è »óÇ° ¸®½ºÆ® Ãâ·Â
           // ÁÙÀ» ³»¸®´Â ½Ã±×³ÎÀÌ ¾øÀ½
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

			 $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
			 $str2 = "->" . str_replace( $arr_chars, "", $product_option );
                 }
                 else
                 {
			$temp = $cnt . "°³:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options] . "/" . $data[memo] . "|| ";
                        $temp = str_replace( $arr_chars, " ", $temp );
                 }

                 return $temp . $str2;
              }
                 break;
           case "qty_product_name": // ¼ö·® + Ç°¸ñ
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

                 $temp = str_replace( $arr_chars, ".", stripslashes( $cnt . "°³:" . $this->get_product_name($data[product_id])));
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
   // »óÇ°º° ¹è¼Ûºñ°¡ ´Ù¸¥ °æ¿ì°¡ ÀÖÀ½
   // »óÇ°º° ¹è¼Ûºñ¸¦ °¡Á®¿È
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

      $transaction = $this->begin("ÁÖ¹®´Ù¿î·Îµå");

      ///////////////////////////////////
      // open file to get file handle 
      $handle = fopen ($saveTarget, "w");

      // download format¿¡ ´ëÇÑ Á¤º¸¸¦ °¡Á®¿Â´Ù
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
      // ÀüÃ¼ data¸¦ download¹Þ´Â´Ù
      // cj ÅÃ¹è´Â ÀüÃ¼¸¦ ¹Þ¾Æ¾ß ÇÔ
      // 30003 : cjÅÃ¹è 
      // 30057 : ¿»·Î¿ìÅÃ¹è
      // 30037 : »ï¼º ÅÃ¹è
      // 30095 : »ç°¡¿Í ÅÃ¹è
      // 30018 : °í·ÁÅÃ¹è
      // 30026 : ·ÎÁ¨ - ÀüÃ¼ ¹Þ´Â´Ù (ds¿Í ozen¸¸)
      global $trans_corp;
      // À§ÀÇ ÅÃ¹è»ç¸¦ »ç¿ëÁß ÀÌÁö¸¸ ÇÕÆ÷¸¦ ÇÑÃâ¿¡ Ã³¸®ÇÏ´Â ¾÷Ã¼ ¸®½ºÆ®
      $header = false;	// header Ãâ·Â ¾È ÇÔ

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
		 // header¸¦ Ãâ·ÂÇÏÁö ¾ÊÀ» °æ¿ì
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

                 // younggunÀÇ °æ¿ì ´ÜÇ°ÀÇ ÄÚµå°¡ 00736ÀÎ°æ¿ì ´©¶ô ½ÃÄÑ¾ß ÇÑ´Ù
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
      // cj, yellow ÅÃ¹è°¡ ¾Æ´Ñ°æ¿ì Ã³¸®
      //   => ÇÕÆ÷¸¦ ÇÑ ÁÙ¿¡ Ã³¸® ÇÏÁö ¾ÊÀ» °æ¿ì 
      //
      else
      {
	      /////////////////////////////////////////////////////////
	      // ÇÕÆ÷¸¸ download¹Þ´Â´Ù
	      $result = $this->get_order_list( &$total_rows , 1, 1); 
	 
	      ////////////////////////////////////////
	      // writting datas to file
	      $i = 1;
	      while ( $data = mysql_fetch_array ( $result ) )
	      {
                 // header Ãâ·Â ºÎºÐ
		 if ( $i == 1 )
                   if (  
                     	$trans_corp != "30084"
                     and $trans_corp != "30050" ) // ´ëÇÑÅë¿îÀÌ¸é headerÃâ·Â ¾È ÇÔ
		 {
                    // ·ÎÁ¨ ds153Àº Çì´õ Ãâ·Â ¾È ÇÔ
		    if ( _DOMAIN_ == "ds153" )
		    {
	      	    	$header = true;	// header Ãâ·Â ÇÔ
                    }
                    else
                    {	
		       foreach ( $download_items as $key=>$value )
		           $buffer .= $value. "\t";
		       $buffer .= "\n";
	      	       $header = true;	// header Ãâ·Â ÇÔ
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

              // ·ÎÁ¨ ds153Àº Çì´õ Ãâ·Â ¾È ÇÔ
              if ( _DOMAIN_ == "ds153" and $trans_corp == "30026" )
                  if ( $i == 1 )
                      $header = true;	
 
		if ( _DOMAIN_ == "nak21" )
			$header = true;

	      /////////////////////////////////////////////////////////
	      // ÇÕÆ÷¸¦ Á¦¿ÜÇÑ data¸¦ download¹Þ´Â´Ù
	      $result = $this->get_order_list( &$total_rows , 1); 

	      ////////////////////////////////////////
	      // writting datas to file

	      while ( $data = mysql_fetch_array ( $result ) )
	      {
		 // header Ãâ·Â ºÎºÐ
                 // ÇÕÆ÷ºÎºÐ¿¡¼­ Ãâ·ÂÀ» ¾È ÇßÀ» °æ¿ì Ãâ·Â ÇÔ
		 if ( $i == 1 && $header == false)
                   if (  $trans_corp != "30022" 
		     and _DOMAIN_ != "nak21"
                     and $trans_corp != "30084"
                     and $trans_corp != "30050" ) // ´ëÇÑÅë¿îÀÌ¸é headerÃâ·Â ¾È ÇÔ
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
      // file »èÁ¦
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
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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
     return "[ÃÑ" . $qty . "°³]" . $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
   function get_aju_pack_memo( $pack )
   {
      global $connect, $trans_corp;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", ":" , chr(13),"\"","'" );

      // ¹è¼ÛÀü ±³È¯ »óÅÂµµ Æ÷ÇÔ½ÃÅ´ - jk.ryu 2006.11.28
      $query = "select product_id, product_name, memo, qty, options, message,packed, pack_list, shop_price from orders where pack='$pack' and order_cs in (0,9,5,7,13,9,10,11)";
      $result = mysql_query ( $query, $connect );
    
      $str = ""; 
      $old_data = "";
      $count = 0;
      $tot_price = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         $temp_data = str_replace( $arr_chars, ".", $data[message] . $data[memo]);

	// ÃÑ ±Ý¾×ÀÌ ÇÊ¿äÇÔ = 2006.12.18 - jk.ryu
         if ( _DOMAIN_ == "mago" or _DOMAIN_ == "peggy" )
		$tot_price = $tot_price + $data[shop_price];	

	//=================================================
	// nak21Àº °°Àº ÁÖ¹®µµ ³ª¿ÀÁö ¾Ê°Ô ÇØ´Þ¶ó°í ÇÔ
	// 2007.8.8 ÀÓ¾ÆÁø ¿äÃ»
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

         // packed (¹­À½ »óÇ°ÀÎ °æ¿ì) Ã³¸® ¹æ¾È
         // date: 2006.11.10 - jk.ryu
         if ( $data[packed] )
         {
           // ¹­À½ »óÇ° °³¼öÃ³¸®
           $count = $count + count( split(",",$data[pack_list] )) * $data[qty];
         }
         else
         {
           // ÃÑ¼ö·® 
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

     // Á¶Ã¶ÈÆÀÌ°¡ ¸Þ¸ð¾øÀ½ ³ª¿À°Ô ÇØ´Þ¶ó ´Ù½Ã ¿äÃ» 2006.12.14
     //if ( _DOMAIN_ == "yangpa" || _DOMAIN_ == "ezadmin" )
     //  $str = $str ? $str : "";
     //else
       $str = $str ? $str : "¸Þ¸ð¾øÀ½";

     // ÃÑ °³¼öµµ ³ª¿Í´Þ¶ó°í ÇÔ
     if ( $count > 1 ) 
     {
	if ( _DOMAIN_ != "younggun" 
         and _DOMAIN_ != "honny"
        )
          $str = "[ÃÑ" . $count . "°³] $str";

	if ( _DOMAIN_ == "mago"  or _DOMAIN_ == "peggy" )
          $str = "[ÃÑ" . number_format( $tot_price) . "¿ø] $str";

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

       return "[ÃÑ" . $data[cnt] . "°³] ";
   }

   function get_total_count( $pack )
   {
       global $connect;

       $query = "select count(*) cnt from orders where pack='$pack' and order_cs in (0,11,9)";

       $result = mysql_query ( $query, $connect );

       $data = mysql_fetch_array ( $result );

 	$this->g_count = $data[cnt];

       return "[ÃÑ" . $data[cnt] . "°³] ";
   }
   //////////////////////////////////////////////
   // ¿É¼Çº° ¹ßÁÖ¸¦ »ç¿ëÇÏÁö ¾Ê´Â ¾÷Ã¼ÀÇ °æ¿ì 

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         //$temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];
         $temp = str_replace( $arr_chars, ".", $temp);

         $str1 = $this->pack_string( $temp, " \t", 100, 2 );
         $str .= $str1;
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â ¾ÈÇÔ
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         //$temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
	   $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
           $opt = $product_option ? $product_option : $data[options];
           $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $opt;
         }
         else
           $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options] . $data[memo];

         // $temp = str_replace( $arr_chars, ".", $temp) . " \t" . str_replace( $arr_chars, ".", $data[memo] );
         $temp = str_replace( $arr_chars, ".", $temp) ;

         $str1 = $this->pack_string2( $temp, " \t", 50, 2 );
         $str .= $str1;
     }
     return $str;
   }

   //////////////////////////////////////////////
   // kgb ÇÕÆ÷ Á¤º¸ Ãâ·Â
   // 2006.8.9 - jk.ryu
   // 40ÀÚ¸¶´Ù \t¸¦ ³Ö¾î ÁØ´Ù
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
         
         $str .= "$data[qty]°³" . $this->pack_string( $temp, "\t", 40 );
         // $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // kgb ÇÕÆ÷ Á¤º¸ Ãâ·Â
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
           $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
         else
           $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

         $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
   function get_tranet_pack2( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options,gift from orders where pack='$pack'";

      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      $i = 0;

      // °³¼ö
      $total_cnt = mysql_num_rows( $result );

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         	$temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
                $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;


        if ( $i )
            $temp = "<td>" . $temp;

        $i++;

        // total_cnt°¡ ¾Æ´Ï¸é »óÇ°°ú »óÇ°ÀÇ °æ°è¸¦ ¸¸µç´Ù
        if ( $i != $total_cnt )
            $temp = $temp. "</td><td> </td><td> </td>";
 
        $str .= $temp;

     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
   function get_tranet_pack( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options,gift from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );
 
      $str = ""; 
      $i = 0;

      // °³¼ö
      $total_cnt = mysql_num_rows( $result );

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt;

         if ( $_SESSION[STOCK_MANAGE_USE] )
         	$temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
		if ( _DOMAIN_ == "dmnet" )
               		$temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id]) . "/" . $data[options] . "/" . $data[memo] ) ;
		else
               		$temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;


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
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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
         	$temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	 else
                $temp = $cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) . str_replace( $arr_chars, " ", $data[options]) ;

	// »çÀºÇ° check 2006.4.18 - jk
	if ( $data[gift] )
		$temp .= "\n»çÀºÇ°: " . $data[gift];

        $str .= $temp . "\n";
     }
     return $str;
   }



   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

                // ¿É¼Ç ¹ßÁÖ¿Í ºñ ¿É¼Ç ¹ßÁÖ¸¦ È¥ÇÕÇØ¼­ »ç¿ëÇÔ
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
                  $temp = $cnt . "°³: " . $temp;
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
        return "[ÃÑ" . $tot. "°³] $str";
   }


   function get_kayoung_cj_etc( $pack )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'" );

      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and pack <> seq";
      $result = mysql_query ( $query, $connect );
      $str = ""; 
      $i = 0;

      // ds153Àº 10°³
      $cnt = 9; 
      // kayoungÀº 6°³
      if ( 
         _DOMAIN_ == "kayoung" or
         _DOMAIN_ == "seongeun" or
         _DOMAIN_ == "color250"
         )
        $cnt = 5;
      else if ( _DOMAIN_ == "kkt114" )
        $cnt = 4;	// ±¤°³Åä´Â 4°³
      // soocargo´Â ÇÑÁÙ¿¡¼­ ¿©·¯ÁÙ·Î º¯°æ
      //else if ( _DOMAIN_ == "soocargo" )
      //  $cnt = 5;	// ¼öÄ«°í 5°³

      while ( $data = mysql_fetch_array ( $result ) )
      { 
         if ( $i == $cnt ) break;
         $c_cnt = class_E::get_part_cancel_count ( $data[seq] );

	if ( $_SESSION[STOCK_MANAGE_USE] )
	    $temp = ($data[qty] - (int)$c_cnt) . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	else
        {
          if ( _DOMAIN_ == "ymy2875" or
               _DOMAIN_ == "color250" )
	    $temp = $data[qty] - (int)$c_cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", $data[options] ) ;
          else
	    $temp = $data[qty] - (int)$c_cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) .":" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;
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
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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
	    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", class_D::get_product_option( $data[product_id])) ;
	else
	    $temp = $data[qty] - (int)$cnt . "°³:" . str_replace( $arr_chars, " " , $this->get_product_name($data[product_id])) ."\t" . str_replace( $arr_chars, " ", $data[options] . $data[memo] ) ;

         $str .= $temp . "\t";
     }
     return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         //$temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $this->get_product_name( $data[product_id]) . $data[options] ) ;

         $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . class_D::get_product_option($data[product_id]);
         $temp = str_replace( $arr_chars, ".", $temp);

         $str1 = $this->pack_string( $temp, " \t", 100, 2 );
         $str .= $str1;
     }
     if ( _DOMAIN_ == "wishe" )
     {
	     if ( $data[order_cs] == 5 or $data[order_cs] == 11)
		$str_result = "[[±³È¯]] ";
	     if ( $data[order_cs] == 9 )
		$str_result = "[[¸Â±³È¯]] ";
     }

     return $str_result . $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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
	
 
         //$temp = $cnt . "°³:" . str_replace( $arr_chars,".", $this->get_product_name($data[product_id])) . "``$";
         //$temp .= str_replace( $arr_chars , ".", $this->get_product_option( $data[product_id] ) ) . "``$";
         // return $this->pack_string( $temp, "``$", 50 );

         if ( $data[pack_list] )
	 {
	     $temp = $this->get_packed_list2( $data[pack_list] , $cnt, "``$" );
	 }
	 else
	 {
	     $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
	     $temp = $cnt . "°³:" . str_replace( $arr_chars,"", $product_name);
	     $temp .= str_replace( $arr_chars, ".", $product_option ) . "``$";
	 }

         $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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
	 
         $temp = $cnt . "°³:" . $this->get_product_name($data[product_id]) . $data[options];
         $str1 = $this->pack_string( $temp, "``$", 50 );
         $str .= $str1;
     }

      return $str;
   }

   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         //$temp =  str_replace( $arr_chars, ".", $cnt."°³:" . $this->get_product_name( $data[product_id]) . "$data[options]" );
         //$str1 = $this->pack_string( $temp, " \t\t\t\t" );

	 // younggunÀÇ °æ¿ì 00736ÀÇ °æ¿ì È®ÀÎÀÌ ¿äÇÏ´Â »óÇ°ÀÓ
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
   // sakawa ÅÃ¹è ÇÕÆ÷ Ãâ·Â
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
	
		   $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
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
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         $temp =  str_replace( $arr_chars, ".", $cnt."°³:" . $this->get_product_name( $data[product_id]) . "$data[options]" );
         $str1 = $this->pack_string( $temp, " \t\t\t\t" );
	 
         $str .= $str1;
     }

      return $str;
   }

   
   //////////////////////////////////////////////
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
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

         $temp =  str_replace( $arr_chars, ".", $cnt."°³:" . $this->get_product_name( $data[product_id]) . "$data[options]" . $data[memo]);
	
         $str1 = $this->pack_string( $temp, " \t\t\t\t" );
	 
         $str .= $str1;
     }

      return $str;
   }

   //////////////////////////////////////////
   //
   // ¹­À½ »óÇ°À» À§ÇÑ ºÎºÐ 
   // »óÇ° ±¸ºÐÀÚ¸¦ ¼³Á¤ ÇÒ ¼ö ÀÖÀ½
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
 
// test¸¦ À§ÇÑ ºÎºÐ  
// if ( _DOMAIN_ == "sccompany" )
// echo $query;
 
      $str = ""; 
      $temp = "";

      while ( $data2 = mysql_fetch_array ( $result ) ) 
      {
          $temp = "$cnt °³:" . str_replace( $arr_chars, ".", $data2[name] );
          $option_string = str_replace( $arr_chars, ".", $data2[options] );
          $temp .= "[$option_string]";
          $temp .= $sep;
          $str .= $temp;
     }

	// echo $str;

      return $str;
   }

   //////////////////////////////////////////
   // ¹­À½ »óÇ°À» À§ÇÑ ºÎºÐ 
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
 
// test¸¦ À§ÇÑ ºÎºÐ  
//if ( _DOMAIN_ == "femi" )
//echo $query;
 
      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) ) 
      {
          $temp = "$cnt °³:" . str_replace( $arr_chars, ".", $data[name] );
          $option_string = str_replace( $arr_chars, ".", $data[options] );
          $temp .= "[$option_string]";
          $temp .= "\t \t \t 0 \t";
          $str .= $temp;
     }

      return $str;
   }

   //////////////////////////////////////////
   // ¹­À½ »óÇ° Ãâ·ÂÀ» À§ÇÑ ºÎºÐ
   // ajuÅÃ¹è¸¦ À§ÇÑ ºÎºÐ - ±¸ºÐÀÚ°¡ \t \t \t 0 \t
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

        // ¹­À½ »óÇ° ¿©ºÎ checkÇÔ
        if ( $data[packed] )
        {
           $temp = $this->get_packed_list( $data[pack_list] , $cnt ); 
        }
        else
        {
          $temp = $cnt . "°³:" . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));

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
   // ±¸Çü ½ºÅ¸ÀÏÀÇ ÇÁ¸°Æ® ¿ëÁö¸¦ À§ÇÑ ¿É¼Ç return
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

        $temp = "$cnt °³: " . str_replace( $arr_chars, ".", $this->get_product_name($data[product_id]));

        if ( $_SESSION[STOCK_MANAGE_USE] )
        {
            // option_stringÀÌ ¾ø´Â°æ¿ì´Â ½ÇÁ¦ ¿É¼Ç°ªÀ» Ãâ·ÂÇÑ´Ù
            // date: 2006.11.15 - jk.ryu
            // if ( !$option_string )
            // -----
            // ÀÌ¹Î¿ì¾¾ ¿äÃ» -> ¹­À½ »óÇ°ÀÏ °æ¿ì¿¡¸¸ ¿É¼ÇÀ» ÀüÃ¼ Ãâ·Â
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
   // ±¸Çü ½ºÅ¸ÀÏÀÇ ÇÁ¸°Æ® ¿ëÁö¸¦ À§ÇÑ ¿É¼Ç return
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
   //    »óÇ°°ú option¸¸ Ãâ·ÂÇÏ´Â case
   //    »óÇ°¸í , ¿É¼Ç, °³¼öÀÇ ¼ø¼­·Î Ãâ·Â
   function get_pack_product_only4( $pack, $sep="|", $str_cnt=44 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'  and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // ´ëÇÑÅë¿î¿¡¼­ Á¦°øÇÏ´Â ¾ç½Ä
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

	 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";

		 $temp =  $cnt . "°³:" . str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $product_option ); 

                 // pack string 
                 $str2 = $this->pack_string( $temp ) . $sep;
         }
         else
         {
                 if ( $cnt > 1 ) 
                     $temp  = "¢¾" . $cnt; 
                 else
                     $temp  = $cnt; 

                 $temp .= "°³:";

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
   //    »óÇ°°ú option¸¸ Ãâ·ÂÇÏ´Â case
   //    »óÇ°¸í , ¿É¼Ç, °³¼öÀÇ ¼ø¼­·Î Ãâ·Â
   function get_pack_product_only3( $pack, $sep="|", $str_cnt=44 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack'  and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // ´ëÇÑÅë¿î¿¡¼­ Á¦°øÇÏ´Â ¾ç½Ä
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

	 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";

		 $temp = str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $product_option ) . "¢¾" . $cnt; 
                 // pack string

                 $str2 = $this->pack_string( $temp ) . $sep;
                 //$str2 = $str1 . $sep;
         }
         else
         {
		 $temp = str_replace( $arr_chars, ".", $product_name );
		 $temp .= "-" . str_replace( $arr_chars, "", $data[options]);

                 if ( $cnt > 1 ) 
                   $temp  .= "¢¾" . $cnt; 
                 else
                   $temp  .= $cnt; 

                 $temp .= "°³";

		 $str2 = str_replace( $arr_chars, "", $temp ); 

                 $str2 = $this->pack_string( $str2 );
         }
         $str .= $str2;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // date: 2006.1.4 - jk
   //    »óÇ°°ú option¸¸ Ãâ·ÂÇÏ´Â case
   //    delimiter·Î ÁÙÀ» ³»¸®´Â ºÎºÐÀÌ ¾øÀ½
   function get_pack_product_only2( $pack, $sep="|", $str_cnt=46 )
   {
      global $connect;
      $arr_chars = array("=","\r", "\n", "\r\n","\t", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11) and order_cs in (0, 11, 9,13,7,5,10) order by product_id, options";
      $result = mysql_query ( $query, $connect );

      // date:2007.3.12 ³Ê¹« ±æ´Ù°í ¿äÃ»
      if ( _DOMAIN_ == "r2046008" )
         $str_cnt = 40;

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // ´ëÇÑÅë¿î¿¡¼­ Á¦°øÇÏ´Â ¾ç½Ä
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
		 $product_name = "";
		 $product_option = "";
		 $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );

		 $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
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
	           $temp = $cnt . "°³:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options];
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
	           $temp = $cnt . "°³:" . $this->get_product_name( $data[product_id] ) . "/" .  $data[options] . "/" . $data[memo];
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
   // »óÇ°°ú option¸¸ Ãâ·ÂÇÏ´Â case
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

         $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
         $str1 = $this->pack_string( $temp, ";", 50 );
         $str1 .= "   " . str_replace( $arr_chars, "", $this->pack_string($product_option,";",50) ) . "";

         $str .= $str1;
     }
     return $str;
   }


   //////////////////////////////////////////////
   // date: 2006.1.2 - jk
   // »óÇ°°ú option¸¸ Ãâ·ÂÇÏ´Â case
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
         // ´ëÇÑÅë¿î¿¡¼­ Á¦°øÇÏ´Â ¾ç½Ä
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         if ( $_SESSION[STOCK_MANAGE_USE] )
         {
           $product_name = "";
           $product_option = "";
           $this->get_product_name_option( $data[product_id], &$product_name, &$product_option );
           $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
         }
         else
         {
           $product_name = $this->get_product_name($data[product_id]);
           $temp = str_replace( $arr_chars, ".", $cnt . "°³:" . $product_name );
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
   // pack memoÃâ·Â
   // name: memo»çÇ×
   // name: memo»çÇ×
   function get_pack_memo( $pack , $seperator = "|")
   {
      global $connect;

      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $query = "select seq, product_id, product_name, memo, qty, options from orders where pack='$pack' and status in (1,2,11)";
      $result = mysql_query ( $query, $connect );

      $str = ""; 
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // ´ëÇÑÅë¿î¿¡¼­ Á¦°øÇÏ´Â ¾ç½Ä
         // if ( $str != "" ) $str .= "|";

         $cnt = class_E::get_part_cancel_count ( $data[seq] );
         $cnt = $data[qty] - (int)$cnt; 

         $temp =  str_replace( $arr_chars, ".", stripslashes( $cnt . "°³:" . $this->get_product_name($data[product_id])));
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
   // ¾ÆÁÖÅÃ¹èÀ§ÇÑ ¿É¼Ç ¼³Á¤
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
               $end_pos = $pos + $max_length; // max°¡ 50

               if ( $end_pos > $length )
                  $end_pos = $length;

               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;
               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               $j++;

               //$pos = $end_pos + 1;
               $pos = $pos + $left;

               //if ( $end_pos != $length ) // ÁÙ ¹Ù²Þ Ç¥½Ã
                   $str .= $seperator;
            }
        }
	else
        {
            $str .= "\t";
            $j=1;
        }

        // °ø¹é ¸Å¿ö ÁÜ
        for ( $count = $j; $count < 2; $count++ )
            $str .= " ". $seperator;
 
        return $str;
   }



   ///////////////////////////////////////////////
   // date: 2006.4.5
   // max_lengthº¸´Ù ÀÛ°Å³ª °°À» °æ¿ì seperator¸¦ ÇÏ³ª¾¿ ³Ö¾î ÁØ´Ù.
   // Á¤ÇØÁø °³¼ö·Î string ÀÚ¸§
   function pack_string2( $temp, $seperator = "|", $max_length = 44, $max_row = 0 )
   {
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $temp = str_replace( $arr_chars, ".", $temp );

      // ¹«Á¶°Ç 50ÀÚ ÀÌÇÏ·Î ³ª´©±â
      $length = strlen ( $temp );
        
      $str = $temp;

       ///////////////////////////////////////
       // Å¬ °æ¿ì¸¸ Åº´Ù
       if ( $length  > $max_length )
       {
            $pos = 0;
            $str = "";
            $j = 0;
           
            //////////////////////////////////////////////// 
            // added by jk 2006.4.5
            // Á¤ÇØÁø max_length¸¶´Ù seperator¸¦ ÀÔ·Â
            while ( $pos < $length )
            {

               // Á¤ÇØÁø °³¼ö¸¸Å­ µ¹°í ³¡³¿
               if ( $max_row )
                   if ( $j == $max_row ) break;

               $end_pos = $pos + $max_length; // max°¡ 50

               if ( $end_pos > $length )
                  $end_pos = $length;
             
               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;

               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               //$pos = $end_pos + 1;
               $pos = $pos + $left;
 
               // if ( $end_pos != $length ) // ÁÙ ¹Ù²Þ Ç¥½Ã
            	   $str .= $seperator;

               $j++;
            }
        }
        else
        {
		// °ø¹é ¸Å¿ö ÁÜ
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
   // Á¤ÇØÁø °³¼ö·Î string ÀÚ¸§
   function pack_string( $temp, $seperator = "|", $max_length = 44, $max_row = 0 )
   {
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $temp = str_replace( $arr_chars, ".", $temp );

      // º¸¼¼³ª¶ó bose5546Àº ´Ã·Á ´Þ¶ó ÇÔ
      if ( _DOMAIN_ == "bose5546" )
        $max_length = 44;

      // ¹«Á¶°Ç 50ÀÚ ÀÌÇÏ·Î ³ª´©±â
      $length = strlen ( $temp );
        
      $str = $temp;

       if ( $length  >= $max_length )
       {
            $pos = 0;
            $str = "";
            $j = 0;
           
            //////////////////////////////////////////////// 
            // Á¤ÇØÁø max_length¸¶´Ù seperator¸¦ ÀÔ·Â
            while ( $pos < $length )
            {

               // Á¤ÇØÁø °³¼ö¸¸Å­ µ¹°í ³¡³¿
               if ( $max_row )
                   if ( $j == $max_row ) break;

               $end_pos = $pos + $max_length; // max°¡ 50

               if ( $end_pos > $length )
                  $end_pos = $length;
             
               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;

               //$str .= $j . "/" .  $left;
               $str .= substr( $temp, $pos, $left);

               //$pos = $end_pos + 1;
               $pos = $pos + $left;
 
               if ( $end_pos != $length ) // ÁÙ ¹Ù²Þ Ç¥½Ã
            	   $str .= $seperator;

               $j++;
            }
        }

	// °ø¹é ¸Å¿ö ÁÜ
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
 
    // ÇÕÆ÷ °Ç¼ö Ãâ·Â
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
        // shop_id °¡ ÀÖ´Â °æ¿ì
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        ///////////////////////////////////////////
        // supply_code °¡ ÀÖÀ» °æ¿ì
        if ( $supply_code )
           $options .= " and a.supply_id = '$supply_code'";

        ///////////////////////////////////////////
        // Á¤»ó, ±³È¯, ¸Â±³È¯ 
        $options .= " and a.status in ( 1, 2, 11 ) 
                      and order_cs in ( 0, 9, 11)";

        //////////////////////////////
        // trans_who°¡ ÀÖ´Â °æ¿ì
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        if ( $confirm )
           $options .= " and a.download_date is not NULL";
        else
           $options .= " and a.download_date is NULL";

        $options .= " and pack = seq";	        // ÇÕÆ÷ÀÎ ³Ñµé¸¸ °Ë»ö

debug("[get_pack_count] $query_cnt . $options");

        $result = mysql_query ( $query_cnt . $options );
        $data = mysql_fetch_array ( $result );

        return $data[cnt];
    }

    ///////////////////////////////////////////////////////////
    // limit_option ÀÌ 0 ÀÏ °æ¿ì´Â ÀüÃ¼ Ãâ·Â ÁÖ·Î download¹ÞÀ»¶§ »ç¿ë
    // °Ë»ö ±âÁØÀÏÀÌ ÁÖ¹®ÀÏÀÏ °æ¿ì : 
    // °Ë»ö ±âÁØÀÏÀÌ ¼ÛÀå ÀÔ·ÂÀÏ
    // pack=0: ÇÕÆ÷ °Ë»ö ¾È ÇÔ
    // pack=1: ÇÕÆ÷¸¸ °Ë»ö ÇÔ
    function get_order_list( &$total_rows , $limit_option=0, $pack =0)
    {
	global $connect, $confirm, $trans_who;

        $search_date = "collect_date";
	$line_per_page = _line_per_page;
	$keyword = $_REQUEST["keyword"];
	$page = $_REQUEST["page"];
	$start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
	$end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));
        $supply_code = $_REQUEST["supply_code"];// °ø±ÞÃ³
        $shop_id = $_REQUEST["shop_id"];        // ÆÇ¸ÅÃ³

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
        // shop_id °¡ ÀÖ´Â °æ¿ì
        if ( $shop_id)
           $options .= " and a.shop_id= '$shop_id'";

        ///////////////////////////////////////////
        // supply_code °¡ ÀÖÀ» °æ¿ì
        if ( $supply_code )
           $options .= " and a.supply_id = '$supply_code'";

        //////////////////////////////
        // trans_who°¡ ÀÖ´Â °æ¿ì
        if ( $trans_who )
           $options .= " and a.trans_who = '$trans_who'";

        ///////////////////////////////////////////
        // Á¤»ó, ±³È¯, ¸Â±³È¯ 
        $options .= " and a.status in ( 1, 2, 11 )";
        //$options .= " and a.status in ( 1, 2, 11, 7 )";

        ///////////////////////////////////////////////////////
        // ¹è¼ÛÀü Ãë¼Ò ¿äÃ»Àº ³ª¿À´Â°Å ¸ÂÀ½ 
        // ¹è¼ÛÀü Ãë¼Ò ¿äÃ»µµ ³ª¿ÀÁö ¾ÊÀ½ - jk. 2006-11-10
        // ¹è¼ÛÀü Ãë¼Ò ¿Ï·á°¡ µÇ¾î¾ß ³ª¿ÀÁö ¾Ê´Â´Ù 
        // date: 2006.1.6
        $options .= " and a.order_cs not in ( 1, 2, 3, 4, 12 )";

        //////////////////////////////////////////
        // pack check      
        if ( $pack != 2 )
		if ( !$pack )
		   $options .= " and ( a.pack is null or a.pack=0 ) ";		// ÇÕÆ÷°¡ ¾Æ´Ñ ³Ñµé¸¸ °Ë»ö
		else
		   $options .= " and a.pack = seq ";	        // ÇÕÆ÷ÀÎ ÄÉÀÌ½º¸¸ °Ë»ö


        // download_date¿¡ ³¯Â¥°¡ ÀÖÀ¸¸é download¾ÈµÊ
        if ( !$confirm )
           $options .= " and a.download_date is NULL";   // È®ÀÎ Àü
        else
           $options .= " and a.download_date is not NULL"; // È®ÀÎ ÈÄ

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
	// total count °¡Á®¿À±â
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

        // ´ëÇÑ Åë¿î data type
        $arr_name = array ( 
                     "order_name"        =>"ÁÖ¹®ÀÚ",
                     "box"               =>"¹Ú½º",
                     "recv_name"        =>"¼ö·ÉÀÚ",
                     "recv_zip"         =>"¿ìÆí¹øÈ£",
                     "recv_address"   =>"¼ö·ÉÁö",        
                     "shop_name"      =>"»çÀÌÆ®ÀÌ¸§",
                     "recv_tel"       =>"¼ö·ÉÀÚ¿¬¶ôÃ³",
                     "recv_mobile"    =>"¼ö·ÉÀÚÇÚµåÆù",
                     "empty1"         =>"°ø¹é",
                     "empty2"         =>"°ø¹é",
                     "empty3"         =>"°ø¹é",
                     "empty4"         =>"°ø¹é",
                     "empty5"         =>"°ø¹é",          
                     "empty6"         =>"°ø¹é",
                     "count"          =>"¼ÛÀå°³¼ö",
                     "seq"            =>"ÁÖ¹®¹øÈ£", 
                     "trans_no"       =>"¿î¼ÛÀå¹øÈ£",
                     "qty"            =>"¼ö·®",          
                     "ds_qty"         =>"¼ö·®",          
                     "empty7"         =>"°ø¹é",          
                     "empty8"         =>"°ø¹é",
                     "empty9"         =>"°ø¹é",
                     "memo"           =>"ÁÖ¹®¸Þ¸ð",
                     "options"        =>"¿É¼Ç",
                     "deliv_price"    =>"¹è¼Ûºñ",
                     "deliv_who"      =>"¼±ÂøºÒ±¸ºÐ",
                     "product_name"   =>"»óÇ°¸í",
                     "collect_date"   =>"¹ßÁÖÀÏ",
                     "order_type"=>"ÁÖ¹®Å¸ÀÔ",
                     "qty_product_name"=>"¼ö·®+Ç°¸ñ",
                     "amount" => "±Ý¾×",
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
   // download formatÀÇ ¼³Á¤°ªÀ» °¡Á®¿Â´Ù
   function get_format()
   {
      $lib_name = "lib/ez_trans_lib_" . _DOMAIN_ . ".php";

      //////////////////////////////////////////
      // 
      // domainº°·Î trans_lib¸¦ »ý¼ºÇÔ..°°Àº ÅÃ¹è»ç¸¦ »ç¿ëÇÑ´Ù°í ÇØµµ
      // ¿ä±¸Á¶°ÇÀº °¢ »çÀÌÆ® º°·Î ´Ù¸£´Ù.
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
  
   // ¹è¼Û Á¤º¸ 
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
