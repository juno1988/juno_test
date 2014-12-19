<?

   ////////////////////////////////////////////////
   // gseshop 
   // 
   function init_7 ( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array (
         "code1" 	=> "주문번호",
         "trans_no" 	=> "운송장번호",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // 프라이스앤지오: 24
   // date: 2005.10.21
   function init_24( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
          "code1" 	=> "배송주문번호",
          "trans_no" 	=> "배송장번호",
          "order_name" 	=> "주문자",
          "deliv_or_not" => "배송구분",
          "recv_date" => "입력일",
          "shop_product_id" => "상품번호",
          "product_name" => "상품명",
          "trans_who" 	=> "모델명",
          "options" 	=> "선택사항",
          "qty" 	=> "수량",
          "recv_name" 	=> "수령자",
          "recv_zip" 	=> "우편번호",
          "recv_address" => "수령주소",
          "recv_tel" 	=> "연락처",
          "recv_mobile" => "핸드폰",
          "message" 	=> "배송메모",
          "supply_price" => "공급원가",
          "order_id" 	=> "주문번호",
          "order_subid" => "주문보조번호",
          "pay" 	=> "주문구분",
          "code2" 	=> "주문순번",
          "code3" 	=> "접수일",
      ); 
      return $arr_items;
   } 
   ////////////////////////////////////
   // 롯데: 09
   // date: 2005.10.21
   function init_9( &$header, &$file_format )
   {
      $file_format = "xls";

        $arr_items = array ( 
	"collect_date"	=> "발주일",		// a
	"code7"		=> "발주차수", 		// B
	"order_id"	=> "주문번호",		// C
	"code1"		=> "부주문번호",	// D
        "code2" 	=> "주문상품번호",	// e
        "code3" 	=> "부주문상품순번",	// f
        "trans_code" 	=> "택배사",		// g
	"trans_no"	=> "송장번호",		// h
	"trans_date_pos"=> "발송예정일",	// I
	"code5"		=> "미처리사유",	// j
	"code6"		=> "협력사처리지연사유",	// k
	"product_name"	=> "상품명",		// l
	"recv_name"	=> "수취인",		// m
	"recv_zip"	=> "수취인우편번호",	// n
	"recv_address"	=> "수취인주소",	// o
	"recv_tel"	=> "수취인전화번호1",	// p
	"recv_mobile"	=> "수취인전화번호2",	// q
	"empty1"	=> "보내는사람(메시지카드)",		// r
	"empty2"	=> "받는사람(메시지카드)",		// s
	"memo"		=> "메시지",		// T
	"recv_name"	=> "수취인",		// u

	"order_tel"	=> "회원전화번호1",	// v
	"order_mobile"	=> "회원전화번호2",	// w
	"empty3"	=> "고객센터전달 메모",	// x
	"empty4"	=> "고객메모",	// y

	"shop_produt_id"=> "상품코드",		// z
	"code8"		=> "브랜드명",		// aa
	"code10"	=> "모델번호",		// ab
	"options"	=> "옵션값",		// ac
	"su_price"	=> "매입단가",		// ad
	"empty1"	=> "판매단가",		// ae
	"empty2"	=> "주문금액",		// af
	"qty"		=> "주문수량",		// ag
	"code17"	=> "발송완료수량",	// ah
	"code18"	=> "발송불가수",	// ai
	"code19"	=> "미처리수량",	// aj
	"code20"	=> "발송완료일",	// ak
	"code21"	=> "교환여부",		// al
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 다음 
   // date: 2005.12.7 - jk.ryu
   function init_3( &$header, &$file_format )
   {
      $file_format = "csv";
	if ( _DOMAIN_ == "yonbang" )
	{
	      $arr_items = array ( 
		  "order_id" 	=> "주문번호",
		  "code1"	=> "협력사번호",
		  "code4"	=> "상점번호",
		  "trans_code"	=> "택배코드",
		  "trans_no"	=> "송장번호",
	      );
	}
	else 
	{
	      $arr_items = array ( 
		  "order_id" 	=> "주문번호",
		  "code1"	=> "협력사번호",
		  "code4"	=> "상점번호",
		  "trans_code"	=> "택배코드",
		  "trans_no"	=> "송장번호",
		  "order_name" 	=> "주문인",
	      );
	}
      return $arr_items;
   }

   ////////////////////////////////////
   // 옥션 
   // date: 2005.10.19
   // 옥션
   // date: 2006.12.9 - jk
   function init_1( &$header, &$file_format )
   {
      // $header = -99; //header 없음 테스트
      $file_format = "xls";
      $arr_items = array ( 
        "no"			=> "일련번호",
	"code3"			=> "구분",
	"shop_product_id"	=> "경매번호",	
        "order_id"		=> "낙찰번호",
	"product_name"		=> "물품명",
	"qty"			=> "수량",
	"amount"		=> "금액",
	"order_name"		=> "구매자",
	"recv_name"		=> "수령자",
	"recv_tel"		=> "전화번호",
	"recv_mobile"		=> "휴대폰",
	"trans_who"		=> "운송비부담",
	"recv_zip"		=> "우편번호",
	"recv_address"		=> "주소",
	"options"		=> "주문선택 사항",
	"memo"			=> "주문요구사항",
        "trans_no"		=> "운송장/등기번호", 
	"code1"			=> "영수증발행",
	"code2"			=> "입금일(입금방법)",
      );
       
      return $arr_items;
   }

   ////////////////////////////////////
   // G 마켓 
   // date: 2005.10.19
   // date: 2006.12.9	// 변경됨 신규 룰
   function init_2( &$header , &$file_format)
   {
      $file_format = "xls";
      $arr_items = array ( 
         "recv_date"=>"배송일",
         "trans_name"=>"택배사", 
         "trans_no"=>"송장번호", 
         "order_id"=>"체결번호",
      );

      return $arr_items;
   }

   //////////////////////////////////
   // 우리홈쇼핑 14
   // date: 2006.12.9	// 변경됨 신규 룰
   function init_14( &$header , &$file_format)
   {
      $file_format = "csv";

      $header = "자가배송 출고확정(출력시간 : 2006/11/03 10:06),,,업체명 : (),,,,,,,,,,,,,,,,,,,,,,,,
[배송사코드],,11:현대택배 12:대한통운 15:한진택배 16:CJGLS 17:천일택배 18:일양택배 19:기타택배 22:HTH 24:로젠택배 26:훼미리특배 31:우체국 32:옐로우 34:아주 35:건영 36:트라넷 37:한국 38:대신 40:KGB 41:이젠택배 99:기타 ☜배송사코드에는 이와같은 코드만 입력할 수 있습니다. 다른 값을 입력하면 운송장이 등록되지 않습니다.,,,,,,,,,,,,,,,,,,,,,,,,,
총 개,,,,,,,,,,,,,,,,,,,,,,,,,,,
No,출하지시일,주문번호,배송사,기타,운송장번호,담당자,실출고일,출고예정일,진행현황,VIP여부,고객명,수취인,연락처,핸드폰,주문구분,지정구분,배송구분,상품구분,상품코드,단품코드,상품명,단품명,수량,우편번호,배송지,판매가,전언
";

      $arr_items = array ( 
        "no"		=>"순번",		// A
        "trans_date" 	=> "출하지시일",	// B
        "order_id"	=> "주문번호", 		// C
        "trans_code" 	=> "배송사",		// D
        "etc"		=> "기타",		// E
	"trans_no"	=> "운송장번호",	// F
 	"code2"		=> "담장자",		// G
        "trans_date_pos"=> "실출고일",		// H
        "code7" 	=> "출고예정일",	// I
        "code3"		=> "진행현황",		// J
	"code4"		=> "vip여부",		// K
	"order_name"	=> "고객명",		// L
	"recv_name"	=> "수취인",		// M
        "recv_tel"	=> "연락처",		// N
	"recv_mobile"	=> "핸드폰",		// O
	"code4"		=> "주문구분",		// P
	"code5"		=> "지정구분",		// Q
	"code6"		=> "배송구분",		// R
	"code8"		=> "상품구분",		// S
	"shop_product_id"=> "상품코드",		// T
	"code9"		=> "단품코드",		// U
	"product_name"	=> "상품명",		// V
	"options"	=> "단품명",		// W
	"qty"		=> "수량",		// X
	"recv_zip"	=> "우편번호",		// Y
	"recv_address"	=> "배송지",		// Z
	"shop_price"	=> "판매가",		// AA
	"memo"		=> "전언",		// AB
      );
      return $arr_items;
   }

   //////////////////////////////////
   // 네이트 13
   // date: 2006.12.9	// 변경됨 신규 룰
   function init_13( &$header , &$file_format)
   {
      $file_format = "csv";

      $header = ",▶확인LIST◀
,▷택배업체코드
,※해당택배업체가 위 택배업체코드항목값에 존재하면 택배사코드에 해당 코드를 입력하고 택배사명은 입력하지 않아도 됩니다
,※택배회사코드가 위 택배업체코드에 없으면 아래 택배사 코드 입력란에(-1)을 입력하고 택배사명입력란에 해당 택배사를 직접 입력합니다.(이 경우 배송 추적이 되지 않습니다.)
,▷발송여부입력코드 ==> ◆발송 : 1 ◆품절 : 2 ◆배송지연 : 3
,※정상발송상태이면 1,해당상품이 품절 상태이면 2,해당 상품이 배송지연이면 3을 발송여부입력란에 입력합니다.
,※설명 라인과 아래 항목중 [파일업로드용] 이 아닌 결제일시 부터는 선택하셔서 마우스 우측키 클릭하셔서 [삭제]를 선택하셔서 삭제해 주십시요. (H열에서 뒤쪽의 데이터는 삭제요망)
파일업로드용,파일업로드용,파일업로드용
";

      $arr_items = array ( 
         "order"=>"순번",
         "order_id"=>"주문번호0", 
         "order_seq"=>"주문내역SEQ", 
         "1"=>"발송여부입력",
         "trans_name"=>"택배사명입력", 
         "trans_code"=>"택배사코드입력",
         "trans_no"=>"송장번호입력",
      );

      return $arr_items;
   }

   ////////////////////////////////////////////////
   // 신세계몰
   // date: 2005.10.24
   // date: 2005.12.23 - 완전 변경
   // date: 2006.12.9	// 변경됨 신규 룰
   function init_15( &$header , &$file_format)
   {
      $file_format = "csv";

      # $header = "배송ID,택배업체,송장번호,담당자\n"; // header 생김 - 2007.5.29
      $header='';
      $arr_items = "";

      if ( _DOMAIN_ == "pnb" )
      {
          $arr_items = array ( 
             "code1"	        => "배송ID",
             "trans_code"	=> "택배업체",
             "trans_no"	        => "송장번호",
             "user_defined"     => "담당자:최윤경"
          );
      }
      else if ( _DOMAIN_ == "metaphor" )
      {
          $arr_items = array ( 
             "code1"	        => "배송ID",
             "user_defined1"	=> "택배업체:10000",
             "trans_no"	        => "송장번호",
             "user_defined"     => "담당자:최윤경"
          );
      }
      else
      {
          $arr_items = array ( 
             "code1"	=> "배송ID",
             "trans_code"	=> "택배업체",
             "trans_no"	=> "송장번호",
             "recv_name"	=> "담당자"
          );
      }

      return $arr_items;
   }
   
   // 인터파크 변경됨 - jk 2006.4.12
   // 주문 일련번호가 발주에서 내려오지 않음 .. 문제 있음
   //
   // date: 2006.12.9	// 변경됨 신규 룰
   function init_5( &$header , &$file_format)
   {
      $file_format = "csv";
      $arr_items = array (
	 "no"		=> "순번",
         "order_id"	=> "주문번호",
         "code1"	=> "주문일련번호",
	 "product_name"	=> "상품명",
	 "collect_date"	=> "입금확인일",
         "trans_code"   => "택배업체코드",
         "1"		=> "발송량",
         "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }

   // 인터파크 오픈 변경됨 - jk 2006.4.27
   function init_6( &$header , &$file_format)
   {
      $file_format = "csv";
      $arr_items = array (
	 "no"		=> "순번",
         "order_id"	=> "주문번호",
         "code3"	=> "주문일련번호",
	 "product_name"	=> "상품명",
	 "collect_date2"=> "입금확인일",
         "trans_code"   => "택배업체",
         "1"		=> "발송량",
         "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }
 
   /////////////////////////////////////////
   // 21. 와와
   // date : 2005.12.22 - jk.ryu
   function init_12( &$header , &$file_format)
   {
      $file_format = "csv";

      $arr_items = array (
         "order_id"	=> "주문번호",
         "code1" 	=> "일련번호",
	 "trans_code"	=> "택배사",
         "trans_no"	=> "운송장번호",
      );
      return $arr_items;
   }

   /////////////////////////////////////////
   // cs클럽 
   // date : 2005.12.22 - jk.ryu
   function init_22( &$header , &$file_format)
   {
      $file_format = "xls";

      $arr_items = array (
         "code3"	=> "PO번호",		// A
         "code4"	=> "PO순번",		// B
         "trans_code"	=> "배송회사코드번호",	// C
         "trans_no"	=> "운송장번호",	// D
         "code1"	=> "회원번호",		// E
         "order_name"	=> "주문자",		// F
         "order_phone"	=> "주문자 전화번호",	// G
         "order_id"	=> "주문번호",		// H
         "order_date"	=> "주문일자",		// I
         "product_id"	=> "상품코드",		// J
         "code2"	=> "상품 상세코드",	// K
	 "trans_who"	=> "배송조건",		// L
         "product_name"	=> "상품명",		// M
         "options"	=> "상품특성",		// N
         "supply_price"	=> "공급가",		// O
         "qty"		=> "주문수량",		// P
         "amount"	=> "주문금액",		// Q
         "order_date"	=> "매출일자",		// R
	 "code5"	=> "회원구분",		// S
         "recv_name"	=> "수취인명",		// T
         "recv_phone"	=> "수취인전화",	// U
         "recv_mobile"	=> "수취인이동통신",	// V
         "recv_zip"	=> "우편번호",		// W
         "recv_address"	=> "주소",		// X
         "message"	=> "참조1",		// Y
         "code6"	=> "참조2",		// Z
         "code7"	=> "참조3",		// AA
         "code8"	=> "약도",		// AB
         "memo"		=> "주문자메시지", 	// AC
         ""		=> "업체코드",
         ""		=> "구분",
         ""		=> "취소코드",
         ""		=> "운송장등록여부",
         ""		=> "바코드",
         ""		=> "취소사유",
         ""		=> "취소일자",
         ""		=> "배송기한",
         ""		=> "배송완료예정일",
         ""		=> "판매가",
      );

      return $arr_items;
   }

   /////////////////////////////////////////
   // kt몰 : 21 
   // date : 2005.12.22 - jk.ryu
   function init_21( &$header , &$file_format)
   {
      $file_format = "xls";

      $arr_items = array (
         "trans_name"	=> "택배사",
         "trans_no"	=> "운송장번호",
         "no"		=> "일련번호",
         "order_id"	=> "주문번호",
         "order_name"	=> "주문인",
         "recv_name"	=> "수령인",
         "product_name"	=> "상품명",
         "options"	=> "단품명",
         "shop_product_id"=> "모델명",
         "shop_price"	=> "판매단가",
         "amount"	=> "판매금액"
      );
      return $arr_items;
   }

   /////////////////////////////////////////
   // 아이세이브존  - 19
   // date : 2005.12.22 - jk.ryu
   function init_19( &$header , &$file_format)
   {
      $file_format = "xls";

      $header = "일괄등록";

      $arr_items = array (
         "order_id"	=> "주문번호",
         "order_subid" 	=> "주문일련번호",
         "code1"	=> "업체명",
         "product_id"	=> "상품코드",
         "product_name"	=> "상품명",
         "options"	=> "단품명",
         "recv_name"	=> "수령인",
         "recv_tel"	=> "수령인 전화번호",
         "recv_mobile"	=> "수령인 전화번호2",
         "recv_zip"	=> "우편번호",
         "recv_address"	=> "주소",
	 ""		=> "배송메시지",
         "trans_no"	=> "운송장번호",
      );
      return $arr_items;
   }

   /////////////////////////////////////////////
   // 동대문 공구 - 25
   function init_25 ( &$header , &$file_format)
   {
      ////////////////////////////////////////
      // header 가 없이 바로 내용이 시작됨
      $header = -99;
      // file format은 csv
      $file_format = "csv";
      $arr_items = array ( 
         "empty"=>"공백",
         "code1"=>"주문번호",
         "trans_no"=>"송장번호",
         "trans_name"=>"택배사명", 
      );
      return $arr_items;
   }

   /////////////////////////////////////////////
   // 온캣 - 
   function init_4 ( &$header , &$file_format)
   {
      $file_format = "xls";
      $arr_items = array (
         "order_id"=>"거래번호",
         "onket_deliv_way"=>"배송방법",
         "trans_name"=>"배송사",
         "onket_arrive_date"=>"도착예정일",
         "trans_no"=>"송장번호",
      );
      return $arr_items;
   }

   ///////////////////////////////////////////////
   // 붐붐
   // date: 2005.10.24
   function init_18( &$header, &$file_format )
   {
      ////////////////////////////////////////
      // header 가 없이 바로 내용이 시작됨
      // $header = -99;

      $file_format = "xls";
      $arr_items = array (
         "order_id" => "주문번호",
         "code1" => "주문상세번호",
         "trans_no" => "송장번호입력란",
         "code2" => "판매처",
         "product_name" => "상품명",
         "options" => "단품정보",
         "qty" => "수량",
         "trans_price" => "착불",
         "order_name" => "주문인",
         "recv_name" => "수취인",
         "order_email" => "전자우편",
         "recv_zip" => "우편번호",
         "recv_address" => "주소",
         "recv_tel" => "전화번호",
         "recv_mobile" => "휴대폰",
         "pay_date" => "결제일",
         "collect_date" => "배송접수일",
         "amount" => "주문액",
         "shop_price" => "판매단가",
         "code3" => "쿠폰액",
         "supply_price" => "공급가",
         "pay_date" => "결제일",
         "memo" => "고객요청",
         "shop_product_id" => "상품코드",
      );
      return $arr_items;
   } 

   ///////////////////////////////////////////////
   // 야후
    // date: 2005.10.24
   function init_20( &$header, &$file_format )
   {
      ////////////////////////////////////////
      // header 가 없이 바로 내용이 시작됨
      // $header = -99;

      $file_format = "xls";
      $arr_items = array (
         "v" => "출고CHK",
         "order_id" => "주문번호",
         "order_name" => "주문자명",
         "product_name" => "상품명",
         "options" => "상품옵션",
         "qty" => "주문량",
         "qty" => "출고량",
         "trans_name" => "택배사",
         "trans_no" => "송장번호",
         "code1" => "주문상세번호",
         "order_date" =>  "주문일자",
         "출고준비중" => "주문상태",
         "order_tel" => "전화번호",
         "order_mobile" => "휴대폰번호",
         "shop_product_id" => "상품코드",
         "recv_name" => "수령자",
         "recv_zip" => "우편번호",
         "recv_address" => "주소",
         "recv_tel" => "배송지전화번호",
         "recv_mobile" => "배송지휴대폰번호",
         "memo" => "배송메시지",
         "supply_price" => "원가",
         "shop_price" => "판매액",
         "trans_code" => "택배사코드",
         "0" => "입금확인후",
      );
      return $arr_items;
   } 

 ////////////////////////////////////
   // 가비아/ 꿈이있는: 45
   // date: 2006.3.16
   // 
   function init_45( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "";
      $arr_items = array (
          "order_id"  		=> "주문번호",	
          "shop_product_id"	=> "상품번호",	
          "trans_name" 		=> "택배사",	
          "trans_no"  		=> "송장번호",	
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 무디스: 46
   // date: 2006.3.14
   // 
   function init_46( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
	"order_id"	=>"주문번호",
	"recv_zip"	=>" 우편",
	"code1"		=>"합계", 
	"product_name"	=>"품목",
	"qty"		=>"수량", 
	"recv_name"	=>"고객명", 
	"recv_tel"	=>"전화번호", 
	"recv_mobile"	=>"휴대폰", 
	"recv_address "	=>"주소", 
	"code2"		=>"주소2",
	"memo "		=>"메모",
	"options"	=>"옵션1", 
	"code3"		=>"옵션2",
	"trans_no"	=>"송장번호 "
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // k마트: 47
   // date: 2006.3.14
   // 
   function init_47( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
        "code1"		=>"순번",
 	"code2"		=>"발주서 확인여부",
	"order_id"	=>"주문번호",
	"trans_who"	=>"배송비",
	"product_name"	=>"상품명",
	"options"	=>"선택정보", 
	"qty"		=>"수량", 
	"recv_name"	=>"수취인", 
	"recv_zip"	=>"배송지 우편번호",
	"recv_address "	=>"배송지", 
	"recv_tel"	=>"수취인연락처1", 
	"recv_mobile"	=>"수취인연락처2", 
	"memo "		=>"구매자메모",
	"shop_price"	=>"체결가",
	"order_name"	=>"구매자명",
	"trans_no"	=>"송장번호 "
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 11번가: 50 
   // date: 2006.3.14
   // 
   function init_50( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "발송처리";
      $arr_items = array (
        "no"		=>"번호",
        "code1"		=>"배송번호",
	"order_id"	=>"주문번호",
	"product_id"	=>"상품번호",
	"product_name"	=>"상품명",
	"options"	=>"옵션", 
	"shop_product_id"=>"판매자상품코드",	// G
	"empty1"	=>"판매단가",
	"qty"		=>"수량", 
	"shop_price"	=>"주문금액",
	"empty2"	=>"추가상품",
	"empty3"	=>"추가액",
	"amount"	=>"총 주문금액",
	"empty4"	=>"배송비구분",
	"trans_who"	=>"배송비",
	"empty5"	=>"판매자할인",
	"empty6"	=>"결재액",	// Q
	"order_name"	=>"구매자",	// R
	"recv_id"	=>"구매자ID",	// S
	"recv_name"	=>"수취인",
	"recv_tel"	=>"전화번호",	// T
	"recv_mobile"	=>"휴대폰",
	"recv_zip"	=>"우편번호",
	"recv_address"	=>"배송시주소",
	"memo"		=>"배송시요구사항",
	"empty7"	=>"판매방식",
	"order_date"	=>"주문일시",
	"collect_date"	=>"결제완료",
	"empty8"	=> "배송방법",
	"trans_code"	=> "택배사코드",
	"trans_no"	=> "송장/등기번호",
      );

      return $arr_items;
   }



   ////////////////////////////////////
   // Hmall: 43
   // date: 2006.1.26
   // 
   function init_43( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "order_id"  	=> "주문번호",
          "trans_code"  => "택배사코드",	
          "trans_no"  	=> "송장번호",	
      );

      return $arr_items;
   }


   ////////////////////////////////////
   //
   // 오케이베스트: 41
   // date: 2006.2.9
   //
   function init_41( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
          "code1"  	=> "번호",
          "order_id" 	=> "주문번호",
          "x" 		=> "회원아이디",
	  "order_name"	=> "주문자 명",
          "order_email"	=> "주문자 email",
	  "order_mobile"=> "주문자 핸드폰",
	  "order_zip"	=> "주문자 우편번호",
	  "order_address" => "주문자 주소 1",
	  "x"		=> "주문자 주소2",
	  "recv_name"	=> "수취인명",
	  "recv_tel"	=> "수취인 전화번호",
          "recv_mobile"	=> "수취인 핸드폰",
 	  "recv_zip"	=> "수취인 우편번호",
	  "recv_address" => "수취인 주소1",
	  "x"		=> "수취인 주소2",
	  "memo"	=> "요청사항",
	  "message"	=> "전달메세지",
	  "x"		=> "회원 구매여부",
          "x"		=> "판매방식",
          "amount"	=> "총금액",
	  "trans_who"	=> "배송비",
	  "x"		=> "카드결제금액",
	  "x"		=> "무통장 결제금액",
	  "x"	 	=> "포인트 결제금액",
	  "x"		=> "무통장 결제정보",
	  "x"		=> "입금예정일",
          "x"		=> "입금자명",
	  "trans_name"	=> "배송회사",
          "trans_no"	=> "배송번호",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   //
   // 패션 플러스 : 38
   // date: 2006.11.3
   // 
   function init_38( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "trans_no" => "송장번호",	
          "order_date" => "지불일자",	
          "order_id" => "주문번호",
	"code1"		=> "주문번호(교환)",
	"no"		=> "순번",
	"code2"		=> "상태",	// F
	"code3"		=> "지불방법",	// G
	"order_name"	=> "주문자명",	// H
	"shop_product_id"	=> "품번",	// I
	"product_name"	=> "상품명",
	"options"	=> "속성",		// K
	"qty"		=> "수량",
	"shop_price"	=> "판매단가",
	"amount"	=> "판매금액",	// N

	"payer_name"	=> "지불자명",	// O
	"order_address"	=> "지불자주소", // P
	"recv_name"	=> "받는사람",  // Q

	"recv_address"	=> "배송지",    // R
	"recv_zip"	=> "우편번호",  // S


	"code5"		=> "배송료착불여부",	// T
	"code6"		=> "착불배송료",	// U
	"recv_tel"	=> "받는 사람 전화", // V
	"recv_mobile"	=> "휴대전화",	// W

	"memo"		=> "메시지",    // X
	"code4"		=> "경품",	// Y 
	"trans_date"	=> "배송접수일자", // Z
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 이모찌오: 39
   // date: 2006.1.2
   // 
   function init_39( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "";
      $arr_items = array (
          "order_id" => "주문번호",
          "trans_no" => "송장번호",	
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // cj mall: 26
   // date: 2005.12.14
   // 
   function init_26( &$header, &$file_format )
   {
      $file_format = "xls";
      //$header = -99;
      $arr_items = array (
          "order_id" 	=> "주문번호",
          "code1" 	=> "운송장식별번호",
          "trans_no" 	=> "운송장번호",	
          "1" 		=> "출고수량",
      );

      return $arr_items;
   }

   /////////////////////////////////////////////////
   // GSEStore (오픈 마켓)
   function init_8 ( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array (
         "no" 		=> "순번",
         "order_id" 	=> "주문번호",
         "trans_no" 	=> "운송장번호",   
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // 멜투 
   // date: 2006.4.21 -jk
   function init_59( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "주문번호",
	  "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }

   // fashion story
   function init_75( &$header, &$file_format )
   {
   $file_format = "csv";

      $arr_items = array ( 
		"order_id"      => "주문번호",
                "code1"         => "순번",
                "trans_no"      => "송장번호",
                "code2"         => "업체코드",
                "code3"         => "물류지 번호",
                "order_name"    => "주문자이름",
                "order_tel"     => "주문자연락처",
                "recv_name"     => "수취인이름",
                "recv_mobile"   => "수취인연락처",
                "zip"           => "수취인우편번호",
                "address1"      => "수취인주소",
                "trans_who"     => "택배비",
                "product_name"  => "상품명",
                "option1"       => "색상",
                "option2"       => "사이즈 (o)",
                "qty"           => "수량",
                "price"         => "금액",
                "memo"          => "메시지"	
      );
      return $arr_items;

   }

   //////////////////////////////////////////////////
   // ezAdmin 
   // 아코아 
   // date: 2006.4.29 -jk
   function init_58( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
          "code1"	=> "순번",
          "order_id"	=> "발주일(번호)",
 	  "product_name" => "상품명",
	  "qty"		=> "수량",
	  "options"	=> "색상",		// H
	  "code2"	=> "사이즈",		// H
	  "recv_name"	=> "고객명(수취인)",
	  "recv_address"=> "주소",
	  "recv_tel"	=> "전화번호",
	  "recv_mobile"	=> "전화번호2",
	  "recv_zip"	=> "우편번호",
	  "memo"	=> "배송메모",
	  "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // makeshop
   // date: 2008.6.26 -jk
   // 
   function init_68( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
        "order_id"	        => "거래번호",
	"order_name"		=> "주문자",
	"trans_no"		=> "송장번호",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // 샵링커 
   // date: 2006.11.3 -jk
   // 
   function init_66( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
        "code1"			=> "주문번호",
	"shop_product_id"	=> "주문상품번호",
	"trans_code"		=> "택배사",
	"trans_no"		=> "송장번호",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // 샵링커 for mammacall
   // date: 2007.3.23 -jk
   // 
   function init_82( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
        "code1"			=> "주문번호",
	"shop_product_id"	=> "주문상품번호",
	"trans_code"		=> "택배사",
	"trans_no"		=> "송장번호",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2006.2.1 -jk
   function init_98( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "주문번호",
	  "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }

   //================================================
   //
   // play auto 97
   // date: 2006.12.26 - jk.ryu
   //
   function init_playauto( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
	order_id	=> "DB",
	order_date    	=> "등록일",
	product_name  	=> "상품명",
	option1       	=> "옵션  ",
	qty           	=> "수량  ",
	su_price      	=> "공급가",
	trans_fee     	=> "배송료",
	trans_who     	=> "착불  ",
	order_name    	=> "주문자",
	order_tel     	=> "주문자전화",
	order_mobile  	=> "주문자핸드폰",
	code10		=> "주문자이메일",
	recv_name     	=> "수령자",
	recv_tel      	=> "전화  ",
	recv_mobile   	=> "핸드폰",
	zip           	=> "우편번호",
	address1      	=> "주소",
	memo          	=> "배송메세지",
	code2		=> "C/S메세지 ",
	code3		=> "발주확인",
	code4		=> "교환접수",
	code5		=> "반품확인",
	code6		=> "취소확인",
	trans_name	=> "배송사",
	trans_no	=> "송장번호",
	code7		=> "CS완결",
	code8		=> "구분  ",
	code1         	=> "판매처",
	price         	=> "판매가",
	code9		=> "주문번호",
	);

      return $arr_items;
   }

   //////////////////////////////////////////////////
   // 제로마켓
   // date: 2005.12.8 -jk
   function init_10( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
          "order_id"	=> "주문번호",
	  "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // 네오디샵: 28
   // date: 2006.01.04
   function init_28( &$header, &$file_format )
   {
      $file_format = "csv";
      $arr_items = array ( 
         "no"		=> "일련번호",		// A
         "code1"	=> "관리번호",		// B
         "x"		=> "구분",		// C
         "order_id" 	=> "주문번호",	// D
         "order_date"	=> "주문일자",	// E
         "trans_price"  => "택배비",	// F
         "product_name"	=> "상품명",        // G
         "options"      => "상품옵션",      // H
         "qty"        	=> "상품수량",      // I
         "order_name"   => "주문인",        // J
         "order_tel"    => "연락처1",       // K
         "order_mobile" => "연락처2",       // L
         "recv_name"    => "수취인",        // M
         "recv_address" => "주소",          // N
         "recv_tel"        => "연락처1",       // O
         "recv_mobile"        => "연락처2",       // P
         "memo"        => "요구사항",      // Q
         "trans_no" => "송장번호",
         "trans_name" => "택배사",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // 하프클럽: 27
   // date: 2006.5.16 변경됨
   function init_27( &$header, &$file_format )
   {
     $file_format = "csv";

     $arr_items = array (
       "halfclub_code" => "거래처코드",
       "order_id"      => "주문번호",
       "code5"         => "주문순번",
       "qty"           => "주문수량",
       "1"             => "출고수량",
       "zero"          => "품절수량",
       "trans_code"    => "택배사코드",
       "trans_no"      => "운송장번호",
     );

     return $arr_items;
   }
 

   ////////////////////////////////////
   // 이지켓: 32
   // date: 2006.5.01
   // 
   function init_32( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "trans_no" 	=> "송장번호",	
          "trans_code"	=> "택배사코드",
          "order_id" 	=> "주문번호",
	  "code1"	=> "코드",
	  "qty"		=> "수량",
	  "code2"	=> "구분",	// F
	  "code3"	=> "묶음배송",
	  "shop_product_id" => "상품코드",
	  "code4"	=> "상품코드(업체)",
	  "product_name" => "상품명",
	  "options"	=> "옵션명",
	  "amount"	=> "주문금액",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 여인 닷컴: 40
   // date: 2006.1.26
   // 
   function init_40( &$header, &$file_format )
   {
      $file_format = "txt";
      $header = "";
      $arr_items = array (
          "trans_no" => "송장번호",	
          "order_id" => "주문번호",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 삼성몰: 42
   // date: 2006.1.26
   // code1과 order_id의 위치 변경 - 
   function init_42( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "\n\n \n\n";
      $arr_items = array (
          "user_defined"   => "업체번호:24060",
          "order_id"  	   => "주문번호",	
          "code1"          => "주문상세번호",	
          "trans_no"  	   => "송장번호",	
          "recv_date"	   => "집하일자",
          "trans_date"	   => "집하예정일",
          "none1"	   => "비고",
	  "none2"          => "집하구분",
	  "trans_code"	   => "택배사코드",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 싸이마켓: 65
   // date: 2006.6.15
   // 
   function init_65( &$header, &$file_format )
   {
      $file_format = "csv";
      $arr_items = array (
          "cy_order_date"  	=> "결제일",	
          "cy_collect_date"  	=> "발주확인일",	
          "order_id"  		=> "주문상세번호",	//
          "cy_trans_date"  	=> "발송일",	
          "cy_trans_how"	=> "배송방식",		// E
	  "trans_code"		=> "택배사코드",
	  "trans_no"		=> "송장번호",		// G
          "code3"		=> "묶음배송코드",	// code2에 묶음 배송 코드가 들어 있지 않음..
	  "order_name"		=> "구매자명",
          "code1"		=> "일촌여부",		// J
	  "order_tel"		=> "연락처번호",
	  "order_mobile"	=> "휴대폰번호",	// L
	  "recv_name"		=> "수취인명",		// M
	  "recv_tel"		=> "수취인연락처",	// N
	  "recv_mobile"		=> "수취인휴대폰",	// O
          "recv_zip"		=> "우편번호",
          "recv_address"	=> "배송지",
	  "product_id"		=> "상품번호",
          "empty1"		=> "업체 상품번호",
	  "product_name"	=> "관리상품명",
	  "options"		=> "선택사항",		// T
          "code2"		=> "옵션",		// U
	  "code4"		=> "사은품",		// V
	  "qty"			=> "수량",		// W
	  "amount"		=> "금액",		// X
	  "trans_who"		=> "택배비",		// Y
	  "trans_date_pos"	=> "발송예정일",	// Z
          "code5"		=> "사이트",		// AA 
          "memo"		=> "배송시요청사항",	// AB
          "code6"		=> "선물포장여부",	// AC
          "code7"		=> "선물포장메시지",	// AD
          "match_product_name"	=> "전시상품명",	// AE
      );

      return $arr_items;
   }


//////////////////////////////////////////////////
   // ezAdmin 
   // date: 2007.2.24 - jk.ryu
   // 맘마콜 : 하나파이브
   function init_84( &$header, &$file_format )
   {

	// ================================
        // date: 2008.3.19 -jk.ryu
        if ( _DOMAIN_ == "codipia" )
        {
                $file_format = "xls";
                $arr_items = array (
                        "order_date"            => "주문일시",		// A
                        "product_name"          => "상품명", 		// B
                        "empaty1"       	=> "옵션",	        // C
                        "qty"       		=> "수량",	        // D
			"recv_name"		=> "수령자명",    	// E
			"recv_tel"		=> "수령자전화번호",	// F
			"recv_mobile"		=> "수령자휴대폰번호",  // G
			"recv_address"		=> "주소",
			"memo"			=> "배송메시지",        // I
			"trans_price"		=> "배송료",
			"trans_name"		=> "택배사명",
			"trans_no"		=> "송장번호",
			"trans_date_pos"	=> "배송일시",
			"order_id"		=> "주문일련번호"
                );   

                return $arr_items;
        }
    }

   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2007.2.24 - jk.ryu
   // 맘마콜 : 하나파이브
   function init_81( &$header, &$file_format )
   {

	// ================================
        // init_81과 동일함
        // date: 2007.4.6 -jk.ryu
        if ( _DOMAIN_ == "zen" )
        {

                $file_format = "xls";
                $arr_items = array (
                        "code1"                 => "주문번호",
                        "shop_product_id"       => "주문상품번호",
                        "trans_code"            => "택배사",
                        "trans_no"              => "송장번호",
                );   

                return $arr_items;
        }
	else if ( _DOMAIN_ == "milkcoco" )
	{
		$file_format = "xls";
	      	$arr_items = array ( 
		    	no		=> "No",
		    	shop_product_id => "상품코드",
			order_id	=> "주문번호",
			product_name	=> "상품명",
			order_name	=> "상품명",
			collect_date    => "주문확인일",
			user_defined 	=> "업체아이디:han523",
			trans_name      => "기본택배사",
			trans_no	=> "송장번호"
		);
                return $arr_items;
	}
	else if ( _DOMAIN_ == "sj" )
	{
		// 패션 밀
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "order_date"     => "주문날짜",
		  "order_id"	   => "주문번호", // B
		  "shop_product_id"	   => "주문상품번호", // C
		  "order_name"	   => "주문인",   // D
		  "recv_name"	   => "수취인",	 // E
		  "recv_tel"	   => "수취인전화번호1",	 // F
		  "recv_mobile"	   => "수취인전화번호2",	 // G
		  "recv_zip"	   => "우편번호",	 // G
		  "recv_address"   => "수취인주소",	 // I
		  "code1"	   => "브랜드명",	// J
		  "product_name"   => "상품명",		// K
		  "options"	   => "옵션",		// L
		  "qty"		   => "수량",		// M
		  "shop_price"	   => "판매가",		// N
 		  "supply_price"   => "공급가",		// O
		  "amount"	   => "총판매가",  	// P
 		  "supply_price"   => "총공급가", 	// Q
		  "memo"	   => "고객요구사항",   // R
		  "org_trans_who"	   => "배송비",		// S
		  "trans_name"=> "택배사명",
		  "trans_no"	   => "송장번호"
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "purdream" )
	{
	    $file_format = "csv";
	    $arr_items = array ( 
          	  "order_id"     => "주문번호",
		  "trans_no"     => "송장번호",
	      	);
	    return $arr_items;
	}
	else if ( _DOMAIN_ == "hanlin829" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"            => "주문번호",
          	  	"user_defined"        => "품목번호:1",
          	  	"product_name"        => "상품명",
          	  	"user_defined1"       => "택배코드:1500",
			"trans_no"            => "송장번호",
			"recv_name"           => "수령인",
			"recv_address"        => "배송지주소",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "jnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
          	  "shop_name"      => "No",
		  "product_id"     => "상품코드",
		  "order_id"	   => "주문번호", // C
		  "product_name"   => "상품명",
		  "order_name"	   => "주문인",
		  "collect_date"   => "주문확인일",
		  "empty1"	   => "업체아이디",
          	  "user_defined"   => "기본택배사:동부익스프레스택배",
		  "trans_no"	   => "송장번호",
		  "code1"	   => "업체명"
	      	);
	    return $arr_items;
	}
        else if ( _DOMAIN_ == "codipia" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"      => "주문번호",
			"code2"       	=> "발주처",
			"user_defined"  => "배송사명:사가와익스프레스",
			"trans_no"      => "송장번호",
			""              => "삭제후등록",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "bigtree" )
	{
		$file_format = "csv";
		$header = "";
		/*
		$arr_items = array ( 
			"code3"	=> "SeqNo",
			"order_id" => "주문번호",
			"code1"	=> "주문순번",
			"shop_product_id" => "상품코드",
			"shop_product_name" => "상품명",
			"options"	=> "옵션",
			"shop_price"	=> "판매가",
			"qty"	=> "수량",
			"code2" => "구매자ID",
			"order_name" => "이름",
			"empty1"	=> "결제방법",
			"recv_address"	=> "수령지주소",
			"recv_name"	=> "수령자",
			"recv_tel"	=> "전화번호",
			"recv_mobile"	=> "핸드폰",
			"memo"	=> "주문요청사항",
			"trans_no" => "송장번호",
			"order_date" => "결제(입금)일자",
		);
		*/
	
		$arr_items = array (
			"order_id" 	=> "주문번호",
			"code1"		=> "은행코드",
			"order_date"	=> "입금확인 시간",
			"order_name"	=> "입금자",
			"32"		=> "택배코드",
			"trans_no"	=> "송장번호",
			"trans_date"	=> "배송일자",
			"1"		=> "이메일",
			"0"		=> "SMS",
		);

		return $arr_items;
	}
        else
	{
      		$file_format = "xls";
	      	$arr_items = array ( 
		  "code1"	=> "No",
		  "shop_product_id"=> "상품코드",
		  "order_id"	=> "주문번호",
		  "product_name" => "상품명",
		  "order_name"	=> "주문인",
		  "collect_date" => "주문확인일",
		  "hana_id"	=> "업체아이디",
		  "trans_name"	=> "기본택배사",
		  "trans_no"	=> "송장번호",
	      );
	      return $arr_items;
	}
   }


   //////////////////////////////////////////////////
   // 망고스틴 아이스타일
   // date: 2007.10.1
   function init_74( &$header, &$file_format )
   {
        $file_format = "xls";
        $header = "";
        $arr_items = array (
                "code4"         => "중복지시",          // A
                "code3"         => "배송지시번호",
                "order_id"      => "주문번호",
                "trans_date_pos" => "배송지시일자",     // D
                "code5"         => "문류정보",          // E
                "order_name"    => "주문자명",
                "recv_name"     => "수취인명",          // G
                "recv_tel"      => "수취인연락처",
                "recv_mobile"   => "수취인휴대폰",
                "code6"         => "배송항목번호",      // J
                "code7"         => "배송상품코드",
                "code8"         => "배송상품명",        // L
                "code9"         => "관리상품명",        // M
                "code10"        => "스타일No",          // N
                "code1"         => "배송상품SKU",       // O
                "code2"         => "상품구분",
                "shop_price"    => "판매가격",
                "qty"           => "배송수량",
                "recv_zip"      => "우편번호",
                "recv_address"  => "배송지주소1",
                "recv_address2" => "배송지주소2",
                "memo"          => "배송메모",
                "code11"         => "배송상태",
                "trans_no"      => "송장번호",
                "trans_name"    => "택배사",
                "memo"          => "관리메모",
        );

        return $arr_items;
   }

//////////////////////////////////////////////////
   // 망고스틴 오가게 
   // date: 2007.10.1
   function init_76( &$header, &$file_format )
   {
        $file_format = "csv";
        $header = "";

        $arr_items = array (
                "code3" => "SeqNo",
                "order_id" => "주문번호",
                "code1" => "주문순번",
                "shop_product_id" => "상품코드", // D
                "shop_product_name" => "상품명",
                "options"       => "옵션",
                "shop_price"    => "판매가",
                "qty"   => "수량",  		// H
                "code2" => "구매자ID",
                "order_name" => "이름",
                "empty1"        => "결제방법", // K
		"recv_zip"	=> "우편번호",
                "recv_address"  => "수령지주소",
                "recv_name"     => "수령자",
                "recv_tel"      => "전화번호", // O
                "recv_mobile"   => "핸드폰",   // P 
                "memo"  => "주문요청사항",
                "trans_no" => "송장번호",
                "order_date" => "결제(입금)일자",
        );

        return $arr_items;

   }

   

   //////////////////////////////////////////////////
   // wizwid 
   // date: 2006.3.31
   function init_48( &$header, &$file_format )
   {
      $file_format = "csv";

      $header = "<!-- ICG Tempate: /venderdelivery/DeliveryListDetailFile.icm -->\n \n";

      $arr_items = array ( 
          "trans_code"	=> "택배사코드",
	  "trans_no"	=> "송장번호",
	  "order_date2"	=> "출고의뢰일",
	  "order_id"	=> "출고번호",
	  "code1"	=> "주문번호",
	  "code2"	=> "상태",
	  "shop_product_id"	=> "상품코드",
	  "product_name" => "상품명",
	  "code3"	=> "모델",
	  "options"	=> "속성 1-2-3",
	  "trans_who"   => "착불여부",	 	// 2009.4.2 추가
	  "qty"		=> "수량",
	  "supply_price"=> "납품가",
          "shop_price"  => "판매가",
          "order_name"  => "고객명",
          "recv_name"	=> "수취인",
	  "recv_address" => "수취인주소",
          "recv_tel"	=> "수취인TEL",
	  "recv_mobile"	=> "수취인HP",
      );


      return $arr_items;
   }

   //////////////////////////////////////////////////
   // Lotte rootl openmarket
   // date: 2007.4.27
   // 2006.12.9
   function init_77( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
                "order_no"      => "주문번호",
                "NULL"          => "주문상품번호",
                "order_date"    => "주문일",
                "NULL"          => "결제일",
                "product_no"    => "상품코드",
                "product_name"  => "상품명",
                "option1"       => "상품옵션정보",
                "order_name"    => "구매자성명",
                "NULL"          => "구매자로그인ID",
                "recv_name"     => "수령인이름",
                "recv_tel"      => "수령인전화",
                "recv_mobile"   => "수령인핸드폰",
                "zip"           => "우편번호",
                "address1"      => "수령인주소",
                "price"         => "상품단가",
                "qty"           => "수량",
                "amount"        => "주문금액",
                "trans_who"     => "배송비부담",
                "code1"         => "배송비선납여부",
                "NULL"          => "배송비",
                "memo"          => "고객요구",
                "trans_corp"    => "택배사",
                "trans_no"      => "운송장번호",
      );

      return $arr_items;
   }
   //////////////////////////////////////////////////
   // mple
   // date: 2006.5.25
   // 2006.12.9
   function init_49( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "배송대기목록\n";
      $arr_items = array (
          "no"           => "일련번호",       // A
          "product_id"   => "등록번호",       // B
          "order_id"     => "거래번호",       // C
          "sale_type"    => "판매방식",       // D
          "code1"        => "판매자상품코드", // E
          "product_name" => "상품명",         // F
          "qty"          => "수량",           // G
          "amount"       => "금액",           // H
          "order_date"   => "결제일시",       // I
          "x"		 => "총주문액",          
          "su_price"     => "정산예정금액",          
          "code2"        => "매매수수료",
          "code3"        => "구매자ID",       // M
          "order_name"   => "구매자이름",     // N
          "order_tel"    => "연락처1",        // O
          "order_mobile" => "연락처2",        // P
          "recv_name"    => "수령자",         // Q
          "recv_tel"     => "연락처1",        // R
          "recv_mobile"  => "연락처2",        // S
          "recv_zip"     => "우편번호",       // T
          "recv_address" => "주소",           // U
          "options"      => "선택사항",       // V
          "memo1"        => "추가주문사항",   // W
          "code4"        => "주문시요청사항", // X
          "code5"        => "영수증 발행",    // Y
          "trans_who"    => "배송비부담",     // Z
          "code6"        => "선물배송비",     // AA
          "code7"        => "기준배송일",     // AB
          "recv_date"    => "도착예정일",     // AC
          "trans_no"     => "송장번호",       // AD
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2006.4.21 -jk
   function init_90( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "주문번호",
	  "trans_no"	=> "송장번호",
      );
      return $arr_items;
   }


   function init_83( &$header, &$file_format )
   {
	if ( _DOMAIN_ == "codipia" )
	{
		$file_format = "xls";
		$arr_items = array (
			"order_id"              => "주문번호(상품별)",
			"1"                     => "주문변경코드",
			"user_defined"          => "배송택배사:하나로택배",
			"trans_no"              => "송장번호",
		);  
	}
	else if ( _DOMAIN_ == "midan" )
	{
	        $file_format = "xls";
		$arr_items = array (
			trans_no 	=> "송장번호",
			user_defined    => "택배사:130",
			trans_date 	=> "출고준비일",
			order_id	=> "주문번호",
			order_date	=>"주문일",
			order_name	=>"주문자",
			product_name	=>"상품명",
			options 	=> "옵션",
			qty     	=> "수량",
			shop_product_id => "상품코드",
			user_defined1 	=> "공급업체:(주)미단라임",	// K
			user_defined2    => "입금상태:결제완료",
			user_defined3    => "주문금액:9900",
			x4  		=> "결재여부",
			x5  		=> "결재형태",
			recv_name 	=> "수취인",
			recv_tel   	=> "전화번호",
			recv_mobile 	=> "휴대폰",
			recv_zip 	=> "우편번호",
			recv_address 	=> "주소",
			code5        	=> "상세주소",
			memo        	=> "배송요구사항",
			user_defined4    => "이메일:gosajang@midan.com",
			code1      	=> "주문상세번호",
			code2 		=> "주문ID",
		);
	}
        else
	{
	    $file_format = "xls";
	    $arr_items = $this->init_6( &$header, &$file_format );
	}
	return $arr_items;
   }

   //===============================================
   // date: 2007.3.8 
   function init_89( &$header, &$file_format )
   {
	// ================================
	// init_81과 동일함
	// date: 2007.7.8 -jk.ryu
	if ( _DOMAIN_ == "js" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"              => "주문번호",
			"code2"                 => "주문 전표순번",
			"order_name"            => "주문자 성명",
			"recv_name"             => "수취인 이름",
			"user_defined"     => "택배사 코드:9006",
			"trans_no"              => "송장번호",
		);  

		return $arr_items;
	}
    }

   //===============================================
   // 오가게
   // date: 2007.3.8 
   function init_80( &$header, &$file_format )
   {

	// ================================
	// init_81과 동일함
	// date: 2007.4.6 -jk.ryu
	if ( _DOMAIN_ == "zen" )
	{

      		$file_format = "xls";
		$arr_items = array (
			"code1"                 => "주문번호",
			"shop_product_id"       => "주문상품번호",
			"trans_code"            => "택배사",
			"trans_no"              => "송장번호",
		);  

		return $arr_items;
	}
	else if ( _DOMAIN_ == "codipia" || _DOMAIN_ == "bigtree" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"            => "관리번호",
			"code3"               => "발주처",
			"trans_name"          => "배송사명",
			"trans_no"            => "송장번호",
			""                    => "삭제후등록",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "milkcoco" )
 	{
		// shop name: 1300k
		$file_format = "csv";
		$arr_items = array (
		    order_id 	  =>"주문번호"
		    ,shop_product_id   =>"상품코드"
		    ,trans_no     =>"송장번호"
		    ,trans_date_pos=>"배송일자"
		);
	
		return $arr_items;

	}
	else if ( _DOMAIN_ == "limegn" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_date"	=> "주문날짜",
			"order_id"	=> "주문번호",  // B
			"shop_product_id"	=> "주문상품번호",  // C
			"order_name"	=> "주문인",  // D
			"recv_name"		=> "수취인",	// e
			"recv_tel"		=> "수취인전화번호1",	// f
			"recv_mobile"		=> "수취인전화번호2",	// g
			"recv_zip"		=> "우편번호",		// h
			"recv_address"		=> "수취인주소",		// i
			"user_defined"        	=> "브랜드명:LimeGreen", // j
			"product_name"		=> "상품명", // k
			"options"		=> "옵션", // l
			"qty"			=> "수량", // m
			"shop_price"		=> "판매가",
			"supply_price"		=> "공급가",
			"amount"		=> "총판매가",
			"total_amount"		=> "총공급가",	// q
			"memo"			=> "고객요구사항",
			"trans_who"		=> "배송비",	// s
			"trans_name"		=> "택배사명",
			"trans_no"		=> "송장번호",
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "cbj0111" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"no"        	=> "번호",
			"order_id"	=> "주문번호",
			"order_name"	=> "주문자명",  // C
			"email"        	=> "이메일",	// D
			"order_tel"		=> "주문자전화번호",   	// e
			"order_mobile"		=> "주문자핸드폰",   	// f
			"recv_name"		=> "받는분이름",	// g
			"recv_tel"		=> "받는분전화번호",	// h
			"recv_mobile"		=> "받는분핸드폰",	// i
			"recv_zip"		=> "우편번호",		// j
			"recv_address"		=> "주소",		// k
			"memo"			=> "배송메세지",	// L
			"empty2"		=> "결제수단",		// m
			"shop_price"		=> "결제금액",		// n
			"order_date"        	=> "주문일자",
			"user_defined"		=> "주문상태:배송준비중",
			"user_defined1"		=> "배송코드:6",
			"trans_no"		=> "송장번호",
			"cy_trans_date"	        => "배송일",	// yyyy-mm-dd
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "shophouse" )
	{
      		$file_format = "xls";
		/*
		$arr_items = array (
			"code10"            	=> "처리상태",
			"trans_date_pos"	=> "출고/반품지시일자",
			"trans_name"		=> "택배사",	
			"trans_no"		=> "운송장번호",
			"product_name"		=> "상품명",
			"product_id"		=> "출고/반품지시일자",
			"options"		=> "Color",
			"options2"		=> "Size",
			"qty" 			=> "수량",
			"market_price"		=> "판매가",
			"order_name"		=> "주문자",
			"recv_name"		=> "수령자",
		);  
		*/
		$arr_items = array (
			"order_date"        => "출고지시일자",
			"shop_product_id"	=> "Item No",
			"trans_name"		=> "택배사",	
			"trans_no"		=> "운송장번호",
			"order_id"		=> "Invoice번호",
			"product_name"		=> "상품명",
			"options"		=> "Color",
			"options2"		=> "Size",
			"qty" 			=> "수량",
			"shop_price"		=> "판매가",
			"order_name"		=> "주문자",   		// K
			"order_tel"		=> "주문자 연락처",   	// L
			"order_mobile"		=> "주문자 휴대폰",   	// L
			"recv_name"		=> "수령자",
			"recv_zip"		=> "수령자 우편번호",
			"recv_address"		=> "수령자 주소",
			"recv_tel"		=> "수령자 연락처",
			"recv_mobile"		=> "수령자 휴대폰",
			"memo"			=> "배달메시지",
			"empty1"		=> "물류메시지",
			"empty2"		=> "받는사람메시지",
			"user_defined"		=> "확인유무:확인",
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "ssueim" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"            => "주문번호",
			"trans_no"            => "송장번호",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "sccompany" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"            => "관리번호",
			"trans_no"            => "송장번호",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "leedb" )
	{
		$file_format = "xls";
		$header = -99;
		$arr_items = array (
			"trans_no"	=> "송장번호",
			"trans_name"	=> "택배사",
			"user_defined"	=> "배송",
			"order_id"	=> "주문번호",
			"recv_name"	=> "수령자",
		);
		return $arr_items;
	}
	else if ( _DOMAIN_ == "pnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
		"trans_no"	=> "송장번호",		// a
		"user_defined"  => "택배사:170",	// b
		"collect_date"  => "출고준비일",	// c
		"order_id"	=> "주문번호",		// d
		"order_date"	=> "주문일",		// e
		"order_name"	=> "주문자",		// f
		"product_name"   => "상품명",		// g
		"options"	=> "옵션",		// h
		"qty"		=> "수량",		// i
		"shop_product_id"	=> "상품코드",		// j
		"user_defined2"	=> "공급업체:(주)피앤비코프", // k
		"user_defined3" => "입금상태:결제완료",  // l
		"shop_price"	=> "주문금액",		// m
		"user_defined4" => "결재여부:결제완료", // n
		"x"		=> "결제형태",		// o
		"recv_name"	=> "수취인",		// p
		"recv_tel"	=> "전화번호",		// q
		"recv_mobile"	=> "휴대폰",		// r
		"recv_zip"	=> "우편번호",		// s
		"recv_address"	=> "주소",		// t
		"user_defined5" => "상세주소:-",	// u
		"memo"		=> "배송시요구사항",	// v
		"email"		=> "이메일",
		"code1"		=> "주문상세번호",	// x
		"code2"		=> "주문ID",		// y
	      	);
	    return $arr_items;
	}

	else if ( _DOMAIN_ == "jnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
          	  "shop_name"      => "No",
		  "product_id"     => "상품코드",
		  "order_id"	   => "주문번호", // C
		  "product_name"   => "상품명",
		  "order_name"	   => "주문인",
		  "collect_date"   => "주문확인일",
		  "empty1"	   => "업체아이디",
          	  "user_defined"   => "기본택배사:동부익스프레스택배",
		  "trans_no"	   => "송장번호",
		  "code1"	   => "업체명"
	      	);
	    return $arr_items;
	}
        else if ( _DOMAIN_ == "alicegohome" )
	{
	        // 후이즈 몰
		$file_format = "xls";
	      	$arr_items = array ( 
		  "order_id"	   => "주문번호", // D
                  "code7"	   => "은행코드",
                  "code8"	   => "입금확인시간",
		  "order_name"	   => "입금자명",
           	  "user_defined"   => "택배코드:17",
		  "trans_no"       => "송장번호",
		  "trans_date_pos" => "배송일자",
           	  "user_defined1"   => "이메일:1",
           	  "user_defined2"   => "SMS:1",
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "yokkun" or _DOMAIN_ == "sj" )
	{
		// 패션 밀
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "order_date"     => "주문날짜",
		  "order_id"	   => "주문번호", // B
		  "shop_product_id"	   => "주문상품번호", // C
		  "order_name"	   => "주문인",   // D
		  "recv_name"	   => "수취인",	 // E
		  "recv_tel"	   => "수취인전화번호1",	 // F
		  "recv_mobile"	   => "수취인전화번호2",	 // G
		  "recv_zip"	   => "우편번호",	 // G
		  "recv_address"   => "수취인주소",	 // I
		  "code1"	   => "브랜드명",	// J
		  "product_name"   => "상품명",		// K
		  "options"	   => "옵션",		// L
		  "qty"		   => "수량",		// M
		  "shop_price"	   => "판매가",		// N
 		  "supply_price"   => "공급가",		// O
		  "amount"	   => "총판매가",  	// P
 		  "none1"   => "총공급가", 	// Q
		  "memo"	   => "고객요구사항",   // R
		  "org_trans_who"	   => "배송비",		// S
		  "trans_name"	   => "택배사명",
		  "trans_no"	   => "송장번호"
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "andstyle" || _DOMAIN_ == "asa" )
	{
	        // 인조이 뉴옥
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "user_defined"   => "택배사:20995",
		  "trans_no"       => "운송장번호",
                  "code7"	   => "일련번호",
		  "order_id"	   => "주문번호", // D
		  "order_name"	   => "주문인",
		  "recv_name"	   => "수령인",
		  "product_name"   => "상품명",
                  "options"	   => "단품명",
		  "empty2" 	   => "모델명",
		  "shop_price" 	   => "판매단가",
		  "amount" 	   => "판매금액",
	      	);
	      	return $arr_items;
	}
	else
	{
		$file_format = "csv";
		$header = "";

		$arr_items = array ( 
			"code3"	=> "SeqNo",    		// a
			"order_id" => "주문번호",
			"code1"	=> "주문순번",
			"shop_product_id" => "상품코드",
			"shop_product_name" => "상품명",
			"options"	=> "옵션",
			"shop_price"	=> "판매가",
			"qty"	=> "수량",
			"code2" => "구매자ID",
			"order_name" => "이름",        // j
			"empty1"	=> "결제방법", // k
			"recv_zip"	=> "수령지우편번호", // l
			"recv_address"	=> "수령지주소", // m
			"recv_name"	=> "수령자",	 // n
			"recv_tel"	=> "전화번호",
			"recv_mobile"	=> "핸드폰",
			"memo"	=> "주문요청사항",
			"trans_no" => "송장번호",
			"order_date" => "결제(입금)일자", // s
		);

		return $arr_items;
	}
   }


?>
