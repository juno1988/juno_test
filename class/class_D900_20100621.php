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
// 판매처 코드
// 10001 : 옥션
// 10002 : G마켓
// 10003 : 다음
// 10005 : 인터파크 
// 10006 : 인터파크 오픈
// 10007 : GS Eshop 
// 10009 : 롯데
// 10010 : 제로마켓 
// 10011 : 레떼 
// 10012 : 와와 
// 10013 : 네이트몰 
// 10014 : 우리홈 
// 10016 : 샌디몰 
class class_D900 extends class_top 
{
   var $g_order_id;
   var $debug = "off"; // 전체 download: on/off
   /////////////////////////////////////
   // type : xls | tab | comma
   // header : download 포멧의 header
   // start_index : 몇 번째 data부터 시작인지 설정
   // data_type : same | diff
   // data_format : array ( "1"=>"3" ) 1번째 column에 upload한 몇번째 data가 저장? 
   // trans_corp : 몇번째 column에 저장?
   // trans_no : 몇번째 column에 저장?
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
   // 옥션 
   function init_10001()
   {
      $this->type = "tab";
      $this->header = "[경매번호] 물품명,낙찰번호,구매자ID,주소,수령자명,운송장/등기번호";
      $this->start_index = 1;
      $this->data_type = "diff";
      $this->data_format = array(2, 3, 7, 13, 8, "trans_no");
      $this->trans_corp = -1;  // not use 
      $this->trans_name = -1;  // not use
      $this->trans_no = 5;
      $this->order_id = 3;     // 낙찰 번호 위치
   }

   ////////////////////////////////////////////////////////////
   // G 마켓 
   function init_10002()
   {
      $this->type = "tab";
      $this->header = "선택,배송상태,발송확인일,발송예정일,배송일,택배사,송장번호,묶음배송코드,발주서발급일,체결일,장바구니번호,체결번호,상품번호,상품명,판매자상품코드,선택정보,수량,구매자결제금액,체결가,총체결액,총공급액,구매자명,구매자연락처1,구매자연락처2,수취인명,수취인연락처1,수취인연락처2,배송지,세금계산서요청여부,세금계산서발급여부,배송지우편번호,배송지앞주소,배송지뒷주소,배송희망일,구매자메모,G마켓메모,요청일,사업자등록번호,회사명,대표자,사업장소재지,우편번호,세금계산서수령지,업태,종목,발급일,TRANS_NO,세금계산서발급여부,배송메모,배송메모위치,주문번호,ORDERSPPL,stat,선택정보,브랜드,발송예정일,발송지연사유,발주서확인처리코드,발주서확인처리일,ACNT_WAY,CCONTR_DT,SEQ_NO,사은품,CLAIM_TYPE,b2e,발주서확인유무,행운경매여부,배송비형태,택배구분,GMARKET_ORD_NO,IP_TRY_YN,delivery_group_no";
      $this->start_index = 1;    // header data는 버린다
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 5;     // 기준은 0부터
      $this->trans_no = 6;       // 기준은 0부터
      $this->order_id = 11;      // 기준은 0부터
   }

   /////////////////////////////
   // 다음
   function init_10003()
   {
      $this->type = "tab";
      // header없음
      $this->start_index = 1;
      $this->data_type = "diff";
      $this->data_format = array(0, 3, 6, "trans_corp", "trans_no");
      // data_type: diff일 경우엔 사용하지 않는다 
      //$this->trans_corp = 3;
      //$this->trans_no = 4;
      $this->order_id = 0;
   }

   /////////////////////////////
   // 다음 온켓
   function init_10004()
   {
      $this->type = "xls";
      // header없음
      $this->header = "거래번호,배송방법,배송사,도착예정일,송장번호";
      $this->start_index = 1;
      $this->data_type = "same";
      //$this->data_format = array(0, 3, 6, "trans_corp", "trans_no");
      // data_type: diff일 경우엔 사용하지 않는다 
      $this->trans_corp = -1;
      $this->trans_no = 5;
      $this->trans_name = -1;
      $this->order_id = 1;
   }

   /////////////////////////////////
   // 인터파크
   function init_10005()
   {
      $this->type = "xls";
      $this->header = "송장번호,택배업체코드,주문일련번호,주문번호,주문량,주문장소,상품코드,ISBN(Lot),공급사 상품코드,상품명,상품옵션,수량,원가,단가,금액,주문자,주문자전화번호,주문자휴대전화,수령인,수령인전화번호,수령인휴대전화,우편번호,주소,배송메세지,선물메세지,요청일시,영수증번호,요청일시,영수증번호";

      $this->start_index = 1; // header data는 버린다
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 2; // 기준은 1부터
      $this->trans_no = 1;   // 기준은 1부터
      $this->order_id = 4;    // 기준은 1부터
   }

   /////////////////////////////////
   // 인터파크 오픈
   function init_10006()
   {
      $this->type = "xls";
      $this->header = "송장번호,택배업체코드,주문일련번호,주문번호,주문수량,주문접수일시,입금일시,정상발송마감일시,주문자명,주문자연락처1,주문자연락처2,주문자이메일,수령자명,수령자연락처1,수령자연락처2,수령자 우편번호,수령자 상세주소,배송시 유의사항,상품코드,상품명,옵션,상품단가,옵션금액,주문총액,판매수수료,판매수수료율,적립금발급액,적립금수수료,무이자할부수수료";

      $this->start_index = 1; // header data는 버린다

      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 2; // 기준은 1부터
      $this->trans_no = 1;   // 기준은 1부터
      $this->order_id = 4;    // 기준은 1부터
   }

   /////////////////////////////////
   // gs eshop 
   function init_10007()
   {
      $this->type = "xls";
      $this->header = "순번,주문번호,운송장번호";
      $this->start_index = 1; // header data는 버린다
      $this->data_type = "diff";
      $this->data_format = array ( "No", 1, "trans_no" );
      $this->trans_corp = -1; // 기준은 1부터
      $this->trans_no = 3;    // 송장의 위치
      $this->order_id = 1;    // 원본에서 주문 번호의 위치
   }

   ////////////////////////////////////////////////////////////
   // 롯데 
   function init_10009()
   {
      $this->type = "xls";
      $this->header = "주문번호,부주문번호,주문상품번호,부주문상품순번,주문일,배송희망일,주문자,회원ID,회원전화번호1,회원전화번호2,수취인,수취인우편번호,수취인주소1,수취인주소2,수취인전화번호1,수취인전화번호2,대리수취인,대리수취인전화번호,받는사람,보내는사람,메시지,메모1,메모2,상품명,상품코드,옵션값,브랜드명,모델번호,판매가,주문금액,발주일,발주차수,발주번호,발주순번,주문수량,발송완료수량,발송불가수량,미처리수량,발송완료일자,택배사,송장번호,발송예정일,미처리사유,교환상품여부,매입단가";

      $this->start_index = 1; // header data는 버린다
      $this->data_type = "same";
      $this->data_format = "";
      $this->trans_corp = 40; // 기준은 1부터
      $this->trans_no = 41;   // 기준은 1부터
      $this->order_id = 1;    // 기준은 1부터
   }

   ////////////////////////////////////////////////////////////
   // 제로 마켓
   function init_10010()
   {
      $this->type = "csv";
      $this->start_index = 1; // header data는 버린다
      $this->data_type = "diff";
      $this->data_format = array(1, "trans_no");
      $this->order_id = 1;    // 기준은 1부터
   }

   ////////////////////////////////////////////////////////////
   // 와와 
   function init_10012()
   {
      $this->type = "csv";
      $this->header = "주문번호,일련번호,업체명,업체ID,판매구분,카테고리,주문일,주문인,주문인전화,주문인HP,수령인,수령인전화,수령인HP,수령인우편번호,수령인주소,처리상태,주문수량,상품ID,단품ID,상품명,단품명,모델명,공급가,판매가,총판매금액,쿠폰금액,적립금,무이자할부수수료,택배사메모,배송형식,배송비,결제수단,택배사,운송장번호,배송입력일";
      
      $this->start_index = 1; // header data는 버린다
      $this->data_type = "same";
      $this->trans_corp = 31; // 기준은 1부터
      $this->trans_no = 32;   // 기준은 1부터
      $this->order_id = 1;    // 기준은 1부터
   }

   ////////////////////////////////////////////////////////////
   // 네이트몰 
   function init_10013()
   {
      $this->type = "csv";
      $this->start_index = 2; // header data는 버린다
      $this->data_type = "same";
      $this->trans_corp = 31; // 기준은 1부터
      $this->trans_no = 32;   // 기준은 1부터
      $this->order_id = 1;    // 기준은 1부터
   }

   ////////////////////////////////////////////////////////////
   // 우리홈 
   function init_10014()
   {
      $this->type = "csv";
      $this->header = "\n\n\nNo,출하지시일,주문번호,배송사,기타,운송장번호,담당자,실출고일,진행현황,VIP여부,고객명,수취인,연락처,핸드폰,주문구분,지정구분,배송구분,상품구분,상품코드,단품코드,상품명,단품명,수량,우편번호,배송지,판매가,전언";
      $this->start_index = 4; // header data는 버린다
      $this->data_type = "same";
      $this->trans_corp = 3; // 기준은 0부터
      $this->trans_no = 5;   // 기준은 0부터
      $this->order_id = 3;    // 기준은 1부터
   }


   ////////////////////////////////////////////////////////////
   // 샌디몰 
   function init_10016()
   {
      $this->type = "xls";
      $this->start_index = 1; // header data는 버린다
      $this->data_type = "diff";
      $this->data_format = array("No", 2, 3,"trans_no");
      $this->order_id = 2;    // 기준은 1부터
   }


   ////////////////////////////////////////////////////////////
   //붐붐
   function init_10018()
   {
      $this->type = "csv";
      $this->header = "주문번호,주문상세번호,송장번호입력란,판매처,상품명,단품정보,수량,착불,주문인,수취인,전자우편,우편번호,주소,전화번호,휴대폰,결제일,배송접수일,주문액,판매단가,쿠폰액,공급가,결제일,고객요청,상품코드";
      $this->start_index = 1; // header data는 버린다
      $this->data_type = "same";
      $this->trans_corp = -1; // 기준은 0부터
      $this->trans_no = 2;   // 기준은 0부터
      $this->order_id = 0;    // 기준은 0부터
   }


   ////////////////////////////////////////////////////////////
   // 야후 
   function init_10020()
   {
      $this->type = "xls";
      $this->start_index = 1; // header data는 버린다
      $this->header = "출고CHK,주문번호,주문자명,상품명,상품옵션,주문량,출고량,택배사,송장번호,주문상세번호,주문일자,주문상태,전화번호,휴대폰번호,상품코드,수령자,우편번호,주소,배송지전화번호,배송지휴대폰번호,배송메시지,원가,판매액,택배사코드,입금확인후";
      $this->data_type = "diff";
      $this->data_format = array("check",2,3,4,5,6,7,"trans_corp","trans_no",10,11,12,13,14,15,16,17,18,19,20,21,22,23);
      $this->order_id = 2;    // 기준은 1부터
   }


   ///////////////////////////////////////////////////////////
   // file을 upload후 download함
   // file을 download할 수 있음
   // date: 2005.8.26
   function upload()
   {
      global $connect, $shop_id, $admin_file, $admin_file_name;

      $transaction = $this->begin("판매처송장다운로드");
      
      $shop = "init_" . $shop_id;
      $this->{$shop}(); // 롯데

      // 읽는 부분
      switch ( $this->type )
      {
         case "xls":
             $data = $this->excel_read ( $admin_file, $admin_file_name , &$num_rows );
         break;
         default :
            $data = file ( $admin_file );  // file을 읽어온다.
            $num_rows = count ( $data ); 
         break;
      }

      // 기록
      $this->write( $data, $num_rows, &$filename );

      $this->end( $transaction );

      // redirect
      $this->redirect( "?template=D900&filename=$filename" );
      exit;
   }

   function write ( $datas, $num_rows , &$filename)
   {
       global $shop_id;

       // 결과를 write할 새로운 data를 open
       $filename = $_SESSION["LOGIN_ID"] . $shop_id . ".csv";
       $saveTarget = _save_dir . $filename;
       $handle = fopen ($saveTarget, "w");

       // header 저장
       if ( $this->header )
          fwrite($handle, $this->header . "\n");

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
            {
               $str .= $data[$i];

               // order id가 없는 경우는 return
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

         // 같은 경우
         // 기준은 1부터
         for ( $i = $start_index; $i < $end_index; $i++ )
         {
            if ( $i == $this->trans_no )
               $str .= $trans_no;
            else if ( $i == $this->trans_corp )
               $str .= $trans_corp;
            else if ( $i == $this->order_id)
            {
               $str .= $data[$i];
               
               // order id가 없는 경우는 return
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
   // 택배사와 송장번호 가져옴
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

      $trans_name = $data[trans_name];	// 배송업체 이름
      $trans_corp = $data[trans_corp];	// 배송업체 번호
      $trans_no = $data[trans_no];	// 송장 번호

      ////////////////////////////////////////////////////////
      // 송장번호가 없을경우는 이후로직을 처리하지 않음 
      if ( !$trans_no && $this->debug == "off" )
         return;

      //////////////////////////////////////////////////////
      // code가 있는 업체의 경우는 code를 가져온다.
      $query = "select code from trans_shop where shop_id = '$shop_id' and trans_corp = '$trans_corp'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      // return값인 trans_corp에 판매처 코드 혹은 택배사 명을 넘긴다
      $trans_corp = $data[code] ? $data[code] : $trans_name;

      if ( $this->debug == "on" )
      {
         $trans_corp = "토인택배";
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
            fatal("잘못된 파일포맷입니다. 지원가능한 파일포맷은 (.xls | .csv | .txt)입니다.");
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
