<?
/*////////////////////////////////////////////////////////////
 web image save
 author: jk.ryu
 last modify date: 2005.9.12
 history:
 2005.9.12 
   save image through http connect
   
save_file($arr_data) 
    : file 저장

download_file($file, $name)
    : function.htm?template=xx&action=download_file&file=xxx&name=bbb   
    
//////////////////////////////////////////////////////////////*/      
require_once "/home/ezadmin/public_html/shopadmin/ExcelReader/reader.php";
include_once "/home/ezadmin/public_html/shopadmin/ExcelParserPro/excelparser.php";

define( "_HTML2XLS_" , "/home/ezadmin/public_html/shopadmin/html2xls.pl ");

class class_file
{
    //
    // tab separate format 추가.
    // 2011.5.17 - jkryu
   function download_tsv( $arr_datas, $filename = "download_data.tsv", $is_html = 1 )
   {
        $this->download_txt( $arr_datas, $filename,0);    
   }
   
   function download_txt( $arr_datas, $filename = "download_data.txt", $is_html = 1 )
   {
        Header("Content-type: application/vnd.ms-excel");
        Header("Content-Disposition: attachment; filename=" . $filename );
        Header("Expires: 0");
        Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        Header("Pragma: public");
        
	    //$saveTarget = _upload_dir . $filename; 
        // file open
        // $handle = fopen ($saveTarget, "w");
        
        // for row        
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            
            // for column
            $j = 0;
            
            //if ( count($row) > 2 )
            //{
                foreach ( $row as $key=>$value) 
                {
                    //if ( $valule )
                    //{
                        $buffer .= $j ? "\t" : "";
                        // $buffer .= "\"" . iconv('utf-8','cp949', $value) . "\"";
                        $buffer .= iconv('utf-8','cp949', $value);
                        //$buffer .= "\"";
                        $j++;
                    //}
                }
            //}
            //else
            //    $buffer = " ";
        
            if ( $buffer )
            {
                $buffer .= "\n";
                echo $buffer;
            }
            //fwrite($handle, $buffer);
            $buffer = "";
        }
        
        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        // fclose($fp);
        //if (is_file($saveTarget)) {
        //    $fp = fopen($saveTarget, "r");
        //    fpassthru($fp);
        // }
   }
    
    // csv포맷으로 저장
   function download_csv( $arr_datas, $filename = "download_data.csv", $is_html = 1 )
   {
        Header("Content-type: application/vnd.ms-excel");
        Header("Content-Disposition: attachment; filename=" . $filename );
        Header("Expires: 0");
        Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        Header("Pragma: public");
        
	    //$saveTarget = _upload_dir . $filename; 
        // file open
        // $handle = fopen ($saveTarget, "w");
        
        // for row        
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            
            // for column
            $j = 0;
            
            //if ( count($row) > 2 )
            //{
//debug_array($row);
                foreach ( $row as $key=>$value) 
                {
                    //if ( $valule )
                    //{
                        $buffer .= $j ? "," : "";
                        // $buffer .= "\"" . iconv('utf-8','cp949', $value) . "\"";
                        $value = str_replace(",", ".", $value); // syhwang 2012.11.20
                        $buffer .= iconv('utf-8','cp949', $value);
                        //$buffer .= "\"";
                        $j++;
                    //}
                }
            //}
            //else
            //    $buffer = " ";
        
            if ( $buffer )
            {
                $buffer .= "\n";
                echo $buffer;
            }
            //fwrite($handle, $buffer);
            $buffer = "";
        }
        
        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        // fclose($fp);
        //if (is_file($saveTarget)) {
        //    $fp = fopen($saveTarget, "r");
        //    fpassthru($fp);
        // }
   }
    
   function save_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            // for column
            foreach ( $row as $key=>$value) 
            {
                if ( is_numeric( $value ) )
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
                else
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
            }

            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
   }

   //******************************************************
   // function.htm?template=file&file=xxx&name=bbb
   function download_file($file, $name)
   {
        // global $file, $name;
	    Header("Content-type: application/vnd.ms-excel; charset=utf-8");
        Header("Content-Disposition: attachment; filename=" . $name );
        Header("Expires: 0");
        Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        Header("Pragma: public");

	    $saveTarget = _upload_dir . $file; 

	 	if (is_file($saveTarget)) {
        	$fp = fopen($saveTarget, "r");
            fpassthru($fp);
	 	}
	 	
	 	//unlink($saveTarget);
   }

   //====================================================
   // arr_data를 xls방식으로 내려 줌
   // (2007.11.29) 서버를 8번으로 이전하며 is_html만 사용하기로 함
   function download( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
Header("Content-type: application/vnd.ms-excel");
Header("Content-Disposition: attachment; filename=" . $filename );
Header("Expires: 0");
Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
Header("Pragma: public");

        $saveTarget = _upload_dir . "temp_" . date('YmdHis');
        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            // for column
            for ( $j=0; $j < count( $row ); $j++ )
                $buffer .= "<td style='mso-number-format:\\@'>" . $row[$j] . "</td>";

            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($handle);
        
        if ( !$is_html )
        {
            $saveTarget2 = $saveTarget . "_temp.xls";
			$converter_path = "/home/ezadmin/public_html/shopadmin/html2xls.pl";
            $run_module = "/usr/bin/perl " . $converter_path . " -o $saveTarget -o $saveTarget2";

			debug( $run_module );

            session_write_close();  //Close the session before proc_open()
            exec( $run_module );
            session_start(); //restore session
        }
        else
            $saveTarget2 = $saveTarget;

           if (is_file($saveTarget2)) {
            $fp = fopen($saveTarget2, "r");
            fpassthru($fp);
        }

        if ( !$is_html )
        {
            unlink($saveTarget);
            //unlink($saveTarget2);
        }
        //else
          //  unlink($saveTarget2);
   }


   /////////////////////////////////////////////////////////////
   // remote의 image file을 저장함
   // date: 2005.9.13
   function write($host, $image_location, $filename="test.gif")
   {
      // error가 생겨도 출력 안함
      ini_set("display_errors", "Off");

      // open source
      $in_file  = fopen ("http://" . $host . $image_location, "rb");

      // return false when file opening is failed
      if ( !$in_file ) return 0;

      // open target
      $out_file = fopen( _save_dir . $filename, "wb");        // binary를 열때는 b옵션을 붙인다? 

      // copy image from source to garget 
      while (!feof ($in_file)) {
         $buffer = fgets ($in_file, 4096);
         fwrite ($out_file, $buffer, 4096);
      }

      // close file handles
      fclose($in_file); 
      fclose($out_file);

      return 1;
   }

   function save_from_file ( $host, $image_location )
   {
      echo file ("http://" .  $host . $image_location );
      
   }

   //===============================
   // file upload_ref
   // 2007.11.20 - jk
   // arr_result를 return함
   function upload_ref( $ref='', $method, $upload_file2="")
   {
        global $_file;

        // php 숫자 자리수
        ini_set("precision", "20");
        
        //===================================
        // part 1. file save 
        $excel_file = $_FILES['_file'];
        if ($excel_file)
        {
            $file_params = pathinfo($_FILES['_file']['name']);
            $file_ext = strtoupper($file_params["extension"]);
            if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT")
            {
                fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
            }

            $upload_dir = _upload_dir;  // lib_common define
            if( $upload_file2 )
            {
                $upload_file = $upload_file2;
                // $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;
            }
            else
            {
                $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;
            }

            if (!move_uploaded_file($_FILES['_file']['tmp_name'], $upload_dir.$upload_file))
            {
               
                fatal("file upload failed111");
            }
            $excel_file = $upload_dir.$upload_file;
        }
        if ($excel_file == '') fatal("No file uploaded");

//debug ( "uploaded" );

        //===================================
        // part 2. data transaction 
        switch ($file_ext)
        {
            case "XLS" :
                //////////////////////////////////////////
                // Using ExcelParserPro
                //$exc = new ExcelFileParser("tmp/debug.log", ABC_ERROR);
                $exc = new ExcelFileParser();
                $res = $exc->ParseFromFile($excel_file);
                $ws = $exc->worksheet['data'][0];
                $num_rows = $ws['max_row'];
                $num_cols = $ws['max_col'];

                break;
            case "CSV" :
            case "TXT" :
                $data = file($excel_file);
                $num_rows = count($data);
                break;
        }

//debug ( "begin parse" );

        $_result = array();
        for ($i = 0; $i <= $num_rows; $i++)
        {
            switch ($file_ext)
            {
              case "XLS" :
                    $x = 1;
                    for ($j = 0; $j <= $num_cols; $j++)
                    {
                        $data_array[$j] = $this->parse_excel_ex($exc, $ws, $i, $j);
                    }
                break;
              case "CSV" :
                $x = 1;
                $data_array = explode(",", str_replace("\"","",$data[$i]));
                break;
              case "TXT" :
                $x = 1;
                $data_array = explode("\t", str_replace("\"","",$data[$i]));
                break;
            }

	    //debug ( "$i / $num_rows " );
	    $ref->${method}( $data_array );
        }
    }

    function upload_massive()
    {
        global $connect;

        ini_set("memory_limit", "400M");
        
        $arr = array();
$i=0;
        $query = "select * from massive_file_upload";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr[] = array(
                $data['c01'],
                $data['c02'],
                $data['c03'],
                $data['c04'],
                $data['c05'],
                $data['c06'],
                $data['c07'],
                $data['c08'],
                $data['c09'],
                $data['c10'],
                $data['c11'],
                $data['c12'],
                $data['c13'],
                $data['c14'],
                $data['c15'],
                $data['c16'],
                $data['c17'],
                $data['c18'],
                $data['c19'],
                $data['c20'],
                $data['c21'],
                $data['c22'],
                $data['c23'],
                $data['c24'],
                $data['c25'],
                $data['c26'],
                $data['c27'],
                $data['c28'],
                $data['c29'],
                $data['c30'],
                $data['c31'],
                $data['c32'],
                $data['c33'],
                $data['c34'],
                $data['c35'],
                $data['c36'],
                $data['c37'],
                $data['c38'],
                $data['c39'],
                $data['c40'],
                $data['c41'],
                $data['c42'],
                $data['c43'],
                $data['c44'],
                $data['c45'],
                $data['c46'],
                $data['c47'],
                $data['c48'],
                $data['c49'],
                $data['c50']
            );
debug($i++);
        }
        return $arr;
    }

    // ExcelReader를 이용한 upload
   function upload($upload_file2="", $is_box4u_FU00=false, $new_file_name="")
   {
        global $_file;

        // php 숫자 자리수
        ini_set("precision", "20");
        
        //===================================
        // part 1. file save 
        $excel_file = $_FILES['_file'];
        if ($excel_file)
        {
            $file_params = pathinfo($_FILES['_file']['name']);
            $file_ext = strtoupper($file_params["extension"]);
            if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT")
                fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
			
			if($is_box4u_FU00 )			
	             $upload_dir = "/home/ezadmin/public_html/shopadmin/upload/box4u/";  // lib_common define
			else
	            $upload_dir = _upload_dir;  // lib_common define
	        
            if( $upload_file2 )
                $upload_file = $upload_file2;
            else if($is_box4u_FU00)
            	$upload_file = $new_file_name;
            else
                $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;
            
            if (!move_uploaded_file($_FILES['_file']['tmp_name'], $upload_dir.$upload_file))
                fatal("file upload failed!!!");
            
            $excel_file = $upload_dir.$upload_file;
        }
        if ($excel_file == '') fatal("No file uploaded");

        //===================================
        // part 2. data transaction 
        $_result = array();
        switch ($file_ext)
        {
            case "XLS" :
                if( 1 || _DOMAIN_ == 'ilovejchina' )
                {
                    require_once "Classes/PHPExcel.php";

                    $objReader = new PHPExcel_Reader_Excel5();
            
                    $objReader->setReadDataOnly(true);
                    $objPHPExcel = $objReader->load($excel_file);
            
                    $objPHPExcel->setActiveSheetIndex(0);
                    $_result = $objPHPExcel->getActiveSheet()->toArray();
                }
                else
                {
                    $exc = new Spreadsheet_Excel_Reader();
                    $exc->setOutputEncoding('cp949');
                    $exc->read($excel_file);
                    
                    $num_rows = $exc->sheets[0]['numRows'];
                    $num_cols = $exc->sheets[0]['numCols'];
    
    				for ($i = 0; $i <= $num_rows; $i++)
                    {
                        if( $i > 20000 )
                            fatal("파일이 최대 20000행을 넘었습니다. ");
    
                        for ($j = 0; $j < $num_cols; $j++)
                        {
                            $data_array[$j] = iconv( 'cp949', 'utf-8', $exc->sheets[0]['cells'][$i+1][$j+1] );
                        }
                            
                        array_push( $_result, $data_array ); 
                    }
                }
                break;
            case "CSV" :
								$handle = fopen($excel_file, "r");
								setlocale(LC_ALL, 'ko_KR.eucKR');
						    while( ($dataArr = fgetcsv($handle, 4096, ',')) !== FALSE ) 
						    {
                    if( $i > 50000 )
                        fatal("파일이 최대 20000행을 넘었습니다. ");
			                      $new_arr = array();
						    	  foreach( $dataArr as $kk => $data )
						    	  {
		                                $new_arr[] = iconv('cp949','utf-8',$data);
		                          }

		                        array_push( $_result, $new_arr ); 
						    }
						    fclose($handle);
								break;
            case "TXT" :
                $handle = @fopen($excel_file, "r"); 
                $data;
                if ($handle) { 
                   $i = 0;
                   while (!feof($handle)) { 
                       $data[$i] = fgets($handle, 4096); 
                       $i++;
                   } 
                   fclose($handle); 
                }                 
                $num_rows = count($data);
                
				        for ($i = 0; $i <= $num_rows; $i++)
				        {

                    if( $i > 50000 )
                        fatal("파일이 최대 20000행을 넘었습니다. ");

			              $new_arr = array();
		                $data_array = explode("\t", str_replace("\"","",$data[$i]));
		                foreach( $data_array as $data_each )
		                    $new_arr[] = iconv('cp949','utf-8',$data_each);
		                array_push( $_result, $new_arr ); 
				        }
                break;
        }
        return $_result;
    }

    // ExcelReader를 이용한 upload - 20000 행을 넘는 발주서를 올릴 경우 오류!!!
   function upload3($upload_file2="", &$ret, $encoding="cp949")
   {
        global $_file;

        $ret = 0;
        
        // php 숫자 자리수
        ini_set("precision", "20");

        // 
        ini_set("memory_limit", "600M");
        
        // 
        ini_set("post_max_size", "20");
        
        //===================================
        // part 1. file save 
        $excel_file = $_FILES['_file'];
        if ($excel_file)
        {
            $file_params = pathinfo($_FILES['_file']['name']);
            $file_ext = strtoupper($file_params["extension"]);
            if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT" && $file_ext != "HTML")
            {
                fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
            }

            $upload_dir = _balju_dir;  // lib_common define
            if( $upload_file2 )
            {
                $upload_file = $upload_file2;
                // $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;
            }
            else
            {
                $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;
            }
            if (!move_uploaded_file($_FILES['_file']['tmp_name'], $upload_dir.$upload_file))
            {
                fatal("file upload failed111");
            }
            $excel_file = $upload_dir.$upload_file;
        }
        if ($excel_file == '') fatal("No file uploaded");

        //===================================
        // part 2. data transaction 
        $_result = array();
        switch ($file_ext)
        {
            case "XLS" :
                if( 1 )
                {
                    require_once "Classes/PHPExcel.php";

                    $objReader = new PHPExcel_Reader_Excel5();

                    $objReader->setReadDataOnly(true);
                    $objPHPExcel = $objReader->load($excel_file);

                    $objPHPExcel->setActiveSheetIndex(0);
                    $_result = $objPHPExcel->getActiveSheet()->toArray();
                }
                else
                {
                    $exc = new Spreadsheet_Excel_Reader();
                    $exc->setOutputEncoding($encoding);
                    $exc->read($excel_file);
                    
                    $num_rows = $exc->sheets[0]['numRows'];
                    $num_cols = $exc->sheets[0]['numCols'];
    
    				for ($i = 0; $i <= $num_rows; $i++)
                    {
                        if( $i > 20000 )
                        {
                            $ret = 1;
                            return;
                        }
    
                        for ($j = 0; $j < $num_cols; $j++)
                        {
                            $data_array[$j] = iconv( $encoding, 'utf-8', $exc->sheets[0]['cells'][$i+1][$j+1] );
                        }
                            
                        array_push( $_result, $data_array ); 
                    }
                }
                break;
            case "CSV" :
                $handle = fopen($excel_file, "r");
                
                // 2014-06-11 장경희
                // cacao는 utf16 포멧으로 앞에 3byte 제거해야함
                if( $encoding == "cacao" )
                    fgets( $handle, 3 );
                else
                    setlocale(LC_ALL, 'ko_KR.eucKR');

                while( ($dataArr = fgetcsv($handle, 0, ',')) !== FALSE )
                {
                    if( $i > 50000 )
                    {
                        $ret = 1;
                        return;
                    }
                    $new_arr = array();
                    foreach( $dataArr as $kk => $data )
                    {
                        // 2014-06-11 장경희
                        // cacao는 utf16 포멧으로 iconv 안함
                        if( $encoding == "cacao" )
                            $new_arr[] = $data;
                        else
                            $new_arr[] = iconv('cp949','utf-8',$data);
                    }
                    
                    array_push( $_result, $new_arr ); 
                }
                fclose($handle);
                break;
            case "TXT" :
                $handle = @fopen($excel_file, "r"); 
                $data;
                if ($handle) { 
                   $i = 0;
                   while (!feof($handle)) { 
                       $data[$i] = fgets($handle); 
                       $i++;
                   } 
                   fclose($handle); 
                }                 
                $num_rows = count($data);
                
				        for ($i = 0; $i <= $num_rows; $i++)
				        {

                    if( $i > 50000 )
                    {
                        $ret = 1;
                        return;
                    }

			              $new_arr = array();
		                $data_array = explode("\t", str_replace("\"","",$data[$i]));
		                foreach( $data_array as $data_each )
		                    $new_arr[] = iconv('cp949','utf-8',$data_each);
		                array_push( $_result, $new_arr ); 
				        }
                break;
            case "HTML" :
                include_once "class_html_parse.php";
                $_result = class_html_parse::parse( $excel_file );
                break;
        }
        return $_result;
    }

    function js_alert($msg)
    {
        ?>
        <script language=javascript>
            alert("<?=$msg?>");
        </script>
        <?
    }
    
   // ExcelReader를 이용한 upload ** fatal 미사용
   function upload2($upload_file2="", &$_result)
   {
        global $_file;

        // php 숫자 자리수
        ini_set("precision", "20");
        
        //===================================
        // part 1. file save 
        $excel_file = $_FILES['_file'];
        if ($excel_file)
        {
            $file_params = pathinfo($_FILES['_file']['name']);
            $file_ext = strtoupper($file_params["extension"]);
            if ($file_ext != "XLS" && $file_ext != "CSV" && $file_ext != "TXT")
            {
                $this->js_alert("잘못된 파일포맷입니다. 지원가능한 파일포맷은 xls, csv, txt 입니다.");
                return -1;
            }

            $upload_dir = _upload_dir;  // lib_common define
            if( $upload_file2 )
                $upload_file = $upload_file2;
            else
                $upload_file = "upload_class-" . date("Ymd_His"). "_." . $file_ext;


            if (!move_uploaded_file($_FILES['_file']['tmp_name'], $upload_dir.$upload_file))
            {
                $this->js_alert("file upload fail ".$upload_dir.$upload_file);
                return -1;
            }
            $excel_file = $upload_dir.$upload_file;
        }
        
        if ($excel_file == '')
        {
            $this->js_alert("No file uploaded");
            return -1;
        }

        //===================================
        // part 2. data transaction 
        $_result = array();
        switch ($file_ext)
        {
            case "XLS" :
                if( 1 || $encoding == "euccn" )
                {
                    require_once "Classes/PHPExcel.php";

                    $objReader = new PHPExcel_Reader_Excel5();
            
                    $objReader->setReadDataOnly(true);
                    $objPHPExcel = $objReader->load($excel_file);
            
                    $objPHPExcel->setActiveSheetIndex(0);
                    $_result = $objPHPExcel->getActiveSheet()->toArray();
                }
                else
                {
                    $exc = new Spreadsheet_Excel_Reader();
                    $exc->setOutputEncoding('utf-8');
                    $exc->read($excel_file);
                    
                    $num_rows = $exc->sheets[0]['numRows'];
                    $num_cols = $exc->sheets[0]['numCols'];
    
    				for ($i = 0; $i <= $num_rows; $i++)
                    {
                        for ($j = 0; $j < $num_cols; $j++)
                        {
                            $data_array[$j] = $exc->sheets[0]['cells'][$i+1][$j+1];
                        }
                        array_push( $_result, $data_array ); 
                    }
                }
                break;
            case "CSV" :
                $handle = fopen($excel_file, "r");
                setlocale(LC_ALL, 'ko_KR.eucKR');
                while( ($dataArr = fgetcsv($handle, 4096, ',')) !== FALSE ) 
                {
                    $new_arr = array();
                    foreach( $dataArr as $kk => $data )
                    {
                        $new_arr[] = iconv('cp949','utf-8',$data);
                    }
                    array_push( $_result, $new_arr ); 
                }
                fclose($handle);
                break;
            case "TXT" :
                $handle = @fopen($excel_file, "r"); 
                $data;
                if ($handle) { 
                   $i = 0;
                   while (!feof($handle)) { 
                       $data[$i] = fgets($handle, 4096); 
                       $i++;
                   } 
                   fclose($handle); 
                }                 
                $num_rows = count($data);
                
                for ($i = 0; $i <= $num_rows; $i++)
                {
                    $new_arr = array();
                    $data_array = explode("\t", str_replace("\"","",$data[$i]));
                    foreach( $data_array as $data_each )
                        $new_arr[] = iconv('cp949','utf-8',$data_each);
                    array_push( $_result, $new_arr ); 
                }
                break;
        }
        return 0;
    }

    ////////////////////////////////////
    // USING ExcelParserPro 4.4
    // 첫번째 CELL : (0,0)부터 시작
    function parse_excel_ex($exc, $ws, $row, $col)
    {
        $data = $ws['cell'][$row][$col];

        if ($data['type'] == 0)
        {
            $ind = $data['data'];

            if ($exc->sst['unicode'][$ind])
            {
                $str = $this->uc2html($exc->sst['data'][$ind]);
            }
            else
            {
                $str = $exc->sst['data'][$ind];
            }

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

    ////////////////////////////////////
    // USING ExcelParserPro 4.4
    function uc2html($str) {
        $ret = '';

        if (function_exists("iconv")) {
                $ret = iconv("UCS-2LE","CP949",$str);

        }
        else
        {
                for( $i=0; $i<strlen($str)/2; $i++ ) {
                        $charcode = ord($str[$i*2])+256*ord($str[$i*2+1]);
                        $ret .= '&#'.$charcode;
                }
        }
        return $ret;
    }

   ////////////////////////////////////////////
   // 파일이 존재한는지 여부를 return함
   function is_exist($location)
   {
      return file_exists($location);
   }

   function del( $file )
   {
      $file = _save_dir . $file;

      if(file_exists( $file ))
      {
        unlink( $file );
        return true;
      }
      else
        return false;
   }

    function save($file, $filename, $id, $index)
    {
        $filename = explode(".", $filename);
        $ext = $filename[1];
        $filename = $id . "_" . $index . "." . $ext; 
        
        copy($file,  _save_dir . $filename);
        
        // 2012-08-14 [JKH] 서버증설 상품 이미지 동기화
		// 2014.7.10 syhwang 막았음 서버 장애로 추정됨
        // $_cmd = "lib/rsync_img.sh " . _DOMAIN_ . " $filename";
        // @exec($_cmd);
        // debug( "서버증설 상품 이미지 동기화 : " . $_cmd );
        
        // 자동으로 THUMB 파일 만들기
        if ($index == "500")
        {
            $dest = str_replace("_500.", "_100.", $filename);
            
            // Usage : make_thumb(원본파일, 저장파일, 가로, 세로);
            class_file::make_thumb($filename, $dest, 100, 100);
        }
        
        if(file_exists($file))
            unlink($file);
        else
            $file = "";
        
        return $filename; 
    }

   function make_thumb($target_image, $dest_image, $width, $height)
   {
        $target_path = _save_dir; 
        $dest_path = _save_dir;
        $image_quality = 75; 

        static $target_ext; 
        static $src; 
        static $thumb; 

        $target_ext = strtolower( substr( trim($target_image), -3 ) ); 

        switch($target_ext) { 
            case 'peg' : 
            case 'jpg' : 
                $src = ImageCreateFromJPEG($target_path . '/' . $target_image) or die('Cannot Open File!'); 
                break; 
            case 'gif' : 
                $src = ImageCreateFromGIF($target_path . '/' . $target_image) or die('Cannot Open File!'); 
                break; 
            case 'png' : 
                $src = ImageCreateFromPNG($target_path . '/' . $target_image) or die('Cannot Open File!'); 
                break; 
            default : 
                return;
        } 

        // jk.ryu 추가 2006.12.7
        if (!$src)
        {
                echo "이미지 생성 실패";
                exit;
        }

        $thumb = ImageCreateTrueColor($width, $height); 
        ImageCopyResampled($thumb, $src, 0,0,0,0, $width, $height, ImageSX($src), ImageSY($src) ); 

        $dest_ext = strtolower( substr( trim($dest_image), -3 ) ); 

        // 오류 발생 부분 -jk.ryu 
        // 2006.12.7
        switch($dest_ext) { 
            case 'peg' : 
            case 'jpg' : 
                if (function_exists("imagejpeg"))  
                        ImageJPEG($thumb, $dest_path . '/' . $dest_image, $image_quality); 
                else
                        echo "no image support in this server";
                break; 
            case 'gif' : 
                if (function_exists("imagegif"))  
                        imagegif($thumb, $dest_path . '/' . $dest_image, $image_quality); 
                else
                        echo "no image support in this server";
                break; 
            case 'png' : 
                ImagePNG($thumb, $dest_path . '/' . $dest_image, $image_quality) or die('Writing Error : Check - Directory and Filename.'); 
                break; 
            default : 
                return;
        } 

        // 2012-08-14 [JKH] 서버증설 상품 이미지 동기화
		// 2014.7.10 syhwang 막았음 서버 장애로 추정됨
        // $_cmd = "lib/rsync_img.sh " . _DOMAIN_ . " $dest_image";
        // @exec($_cmd);
        // debug( "서버증설 상품 이미지 동기화 : " . $_cmd );
        
        ImageDestroy($src); 
        ImageDestroy($thumb); 
   } 


   function read($file)
   {
       
      $fp = fopen ($file , "r");
      $html = fread($fp, filesize( $file ));

      return $html;        
   }

   function replace( $arr_datas, $html )
   {
      $source = array();
      $target = array();
      foreach($arr_datas as $key=>$val)
      {
        $s_key = "{".$key."}";
        $t_key = stripslashes($val);

        array_push($source , $s_key);
        array_push($target, $t_key);
      }

      //////////////////////////////////////////////////////////
      // db data 이외의 부분
      $result= str_replace($source,$target,$html);
      return $result; 
   }
   
    function download_new($data_all, $fn, $shop_id=0)
    {
        global $connect;
        
ini_set("memory_limit", "400M");

        require_once "class_table.php";
        require_once "Classes/PHPExcel.php";

        $filename = _upload_dir . $fn;

Header("Content-type: application/vnd.ms-excel");
Header("Content-Disposition: attachment; filename=" . $fn );
Header("Expires: 0");
Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
Header("Pragma: public");

        $excel = new PHPExcel();
        if( $shop_id  % 100 == 15 )
			$sheet = $excel->getActiveSheet()->setTitle("Sheet1");
		else if( $shop_id  % 100 == 2 )
			$sheet = $excel->getActiveSheet()->setTitle("발송확인");
		else if( $shop_id  % 100 == 74 )
			$sheet = $excel->getActiveSheet()->setTitle("Table");
		else
        	$sheet = $excel->getActiveSheet();
        	
        $col = 0;
        $row = 0;

        $end_col = PHPExcel_Cell::stringFromColumnIndex($col-1);
        
        foreach ($data_all as $data_val) {
            $row++;
            $col = 0;

            $is_num = false;
            foreach( $data_val as $d_val )
            {
                class_table::print_xls($d_val, $is_num, &$sheet, $col, $row);
                $col++;
            }
        }

        //$sheet->mergeCells('A2:A3');
        
        $objPageSetup = new PHPExcel_Worksheet_PageSetup();
        $objPageSetup->setFitToPage(true);
        $objPageSetup->setFitToWidth(1);
        $objPageSetup->setFitToHeight(0);

        $sheet->setPageSetup($objPageSetup);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        $fp = fopen($filename, "r");
        fpassthru($fp);
        unlink($filename);
    }

    function download_new_hmall($data_all, $fn)
    {
        global $connect;
        
        require_once "class_table.php";
        require_once "Classes/PHPExcel.php";

        $filename = _upload_dir . $fn;

Header("Content-type: application/vnd.ms-excel");
Header("Content-Disposition: attachment; filename=" . $fn );
Header("Expires: 0");
Header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
Header("Pragma: public");

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet()->setTitle("출고요청확정");

        $col = 0;
        $row = 0;

        $end_col = PHPExcel_Cell::stringFromColumnIndex($col-1);
        
        foreach ($data_all as $data_val) {
            $row++;
            $col = 0;

            $is_num = false;
            foreach( $data_val as $d_val )
            {
                class_table::print_xls($d_val, $is_num, &$sheet, $col, $row);
                $col++;
            }
        }

        // A1 셀에 공백이있는데 이걸 제거한다.
        $sheet->getCellByColumnAndRow("A", 1)->setValueExplicit("", PHPExcel_Cell_DataType::TYPE_STRING);
        
        $sheet->mergeCells('A2:A3');
        $sheet->mergeCells('B2:B3');
        $sheet->mergeCells('C2:C3');
        $sheet->mergeCells('D2:D3');
        $sheet->mergeCells('E2:E3');

        $objPageSetup = new PHPExcel_Worksheet_PageSetup();
        $objPageSetup->setFitToPage(true);
        $objPageSetup->setFitToWidth(1);
        $objPageSetup->setFitToHeight(0);

        $sheet->setPageSetup($objPageSetup);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        $fp = fopen($filename, "r");
        fpassthru($fp);
        unlink($filename);
    }
}
?>
