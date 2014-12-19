<?

/************************************************************************************

[ lock_ezadmin.xml ]

<?xml version="1.0" encoding="utf8"?>
<root>
  <proc>
    <code>109</code>
    <user_id>root2</user_id>
    <session_id>138bc0c6448ef060534bbee4f54e125d</session_id>
    <work>CS 주문 삭제</work>
    <par>1292921627</par>
    <start_time>2010-12-21 17:53:47</start_time>
    <update_time>2010-12-21 17:53:51</update_time>
  </proc>
</root>

************************************************************************************/



class class_lock
{

    //==================================
    //  << 작업 코드 >>
    //==================================
    //
    
    private $lock_101 = array(102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_102 = array(101,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_103 = array(101,102,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_104 = array(101,102,103,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_105 = array(101,102,103,104,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_106 = array(101,102,103,104,105,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_107 = array(101,102,103,104,105,106,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_108 = array(101,102,103,104,105,106,107,109,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_109 = array(101,102,103,104,105,106,107,108,110,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_110 = array(101,102,103,104,105,106,107,108,109,111,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_111 = array(101,102,103,104,105,106,107,108,109,110,112,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_112 = array(101,102,103,104,105,106,107,108,109,110,111,113,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_113 = array(101,102,103,104,105,106,107,108,109,110,111,112,114,115,116,117,205,206,301,302,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_114 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,115,116,117,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,309,401,402);
    private $lock_115 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,116,117,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,309,401,402);
    private $lock_116 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,117,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,309,401,402);
    private $lock_117 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,205,206,301,302,303,304,305,306,307,309,503,504,505,507,508,509,510);
    private $lock_201 = array(114,115,116,202,203,204,205,206,207,208,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);
    private $lock_202 = array(114,115,116,201,203,204,205,206,207,208,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);
    private $lock_203 = array(114,115,116,201,202,204,205,206,207,208,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);
    private $lock_204 = array(114,115,116,201,202,203,205,206,207,208,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);

    // 301 주문다운로드2 제외 2013-06-07
    private $lock_205 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,206,207,208,302,303,304,305,306,307,309,401,402);

    private $lock_206 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,207,208,301,302,303,304,305,306,307,309,401,402);
    private $lock_207 = array(114,115,116,201,202,203,204,205,206,208,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);
    private $lock_208 = array(114,115,116,201,202,203,204,205,206,207,301,305,306,307,309,401,402,403,404,405,406,407,408,409,410);
    private $lock_301 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,206,207,208,302,303,304,305,306,307,308,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_302 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,303,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_303 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,304,305,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_304 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,205,206,301,302,303,305,306,307,309,401,402,403,404,405,406,407,408,409,410,501,502,503,504,505,506,507,508,509,510,511,512,513,514,515);
    private $lock_305 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,206,207,208,301,302,303,304,306,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_306 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,206,207,208,301,302,303,304,305,307,309,401,402,403,404,405,406,407,408,409,410,503,504,505,507,508,509,510);
    private $lock_307 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,206,207,208,301,302,303,304,305,306,309,401,402);
    private $lock_308 = array(301);
    private $lock_309 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,401,402);
    private $lock_310 = array(503);
    private $lock_401 = array(107,108,109,110,111,112,113,114,115,116,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,309,402,403,404,405,406,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_402 = array(107,108,109,110,111,112,113,114,115,116,201,202,203,204,205,206,207,208,301,302,303,304,305,306,307,309,401,403,404,405,406,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_403 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,404,405,406,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_404 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,405,406,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_405 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,406,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_406 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,405,407,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_407 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,405,406,408,409,410,501,502,503,504,505,513,514,515);
    private $lock_408 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,405,406,407,409,410,501,502,503,504,505,513,514,515);
    private $lock_409 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,405,406,407,408,410,501,502,503,504,505,513,514,515);
    private $lock_410 = array(107,108,109,110,111,112,113,201,202,203,204,207,208,301,302,303,304,305,306,401,402,403,404,405,406,407,408,409,501,502,503,504,505,513,514,515);
    private $lock_501 = array(304,401,402,403,404,405,406,407,408,409,410,502,503,504,505,506,507,508,509,510,511,512,513,514,515);
    private $lock_502 = array(304,401,402,403,404,405,406,407,408,409,410,501,503,504,505,506,507,508,509,510,511,512,513,514,515);
    private $lock_503 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,310,401,402,403,404,405,406,407,408,409,410,501,502,504,505,506,507,508,509,510,511,512,513,514,515);
    private $lock_504 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,401,402,403,404,405,406,407,408,409,410,501,502,503,505,506,507,508,509,510,511,512,513,514,515);
    private $lock_505 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,401,402,403,404,405,406,407,408,409,410,501,502,503,504,506,507,508,509,510,511,512,513,514,515);
    private $lock_506 = array(304,501,502,503,504,505,507,508,509,510,511,512,513,514,515);
    private $lock_507 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,501,502,503,504,505,506,508,509,510,511,512,513,514,515);
    private $lock_508 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,501,502,503,504,505,506,507,509,510,511,512,513,514,515);
    private $lock_509 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,501,502,503,504,505,506,507,508,510,511,512,513,514,515);
    private $lock_510 = array(101,102,103,104,105,106,107,108,109,110,111,112,113,117,301,302,303,304,305,306,501,502,503,504,505,506,507,508,509,511,512,513,514,515);
    private $lock_511 = array(304,501,502,503,504,505,506,507,508,509,510,512,513,514,515);
    private $lock_512 = array(304,501,502,503,504,505,506,507,508,509,510,511,513,514,515);
    private $lock_513 = array(304,401,402,403,404,405,406,407,408,409,410,501,502,503,504,505,506,507,508,509,510,511,512,514,515);
    private $lock_514 = array(304,401,402,403,404,405,406,407,408,409,410,501,502,503,504,505,506,507,508,509,510,511,512,513,515);
    private $lock_515 = array(304,401,402,403,404,405,406,407,408,409,410,501,502,503,504,505,506,507,508,509,510,511,512,513,514);
    private $lock_516 = array();
    private $lock_601 = array();
    private $lock_602 = array();

    private $work_101 = "송장 입력";
    private $work_102 = "송장 삭제";
    private $work_103 = "합포 추가";
    private $work_104 = "합포 제외";
    private $work_105 = "전체 취소";
    private $work_106 = "전체 정상 복귀";
    private $work_107 = "주문 복사"; // 중복가능(seq)
    private $work_108 = "주문 생성";
    private $work_109 = "주문 삭제";
    private $work_110 = "개별 취소";
    private $work_111 = "개별 정상 복귀";
    private $work_112 = "상품 교환"; // 중복가능(seq)
    private $work_113 = "상품 추가"; // 중복가능(seq)
    private $work_114 = "배송처리";
    private $work_115 = "배송취소";
    private $work_116 = "미송설정";
    private $work_117 = "합포금지";

    private $work_201 = "재고 작업";
    private $work_202 = "재고 일괄 조정";
    private $work_203 = "프리미엄 POS 입고";
    private $work_204 = "프리미엄 POS 출고";
    private $work_205 = "프리미엄 POS 배송";
    private $work_206 = "이지오토 배송";
    private $work_207 = "재고작업 취소";
    private $work_208 = "재고작업 취소복구";

    private $work_301 = "주문다운로드2";
    private $work_302 = "송장입력";
    private $work_303 = "송장출력(자체송장프로그램)";
    private $work_304 = "주문일괄삭제";
    private $work_305 = "송장일괄삭제(파일)";
    private $work_306 = "송장일괄삭제(조회)";
    private $work_307 = "배송일괄취소(파일)";
    private $work_308 = "주문다운로드2 초기화";
    private $work_309 = "배송취소";
    private $work_310 = "송장출력(자체송장프로그램)-연속";

    private $work_401 = "중복상품 삭제";
    private $work_402 = "중복상품 일괄삭제";
    private $work_403 = "옵션관리설정";
    private $work_404 = "옵션관리취소";
    private $work_405 = "상품삭제";
    private $work_406 = "옵션삭제";
    private $work_407 = "상품일괄수정";
    private $work_408 = "상품일괄선택수정";
    private $work_409 = "매칭전체삭제";
    private $work_410 = "매칭개별삭제";
    private $work_501 = "자동일괄매칭";
    private $work_502 = "개별매칭"; // 중복가능(seq)
    private $work_503 = "발주전체합포";
    private $work_504 = "발주개별합포";
    private $work_505 = "발주완료";
    private $work_506 = "메모다시보기";
    private $work_507 = "전체발주삭제";
    private $work_508 = "개별발주삭제";
    private $work_509 = "cs발주삭제";
    private $work_510 = "개별cs발주삭제";  // 사용안함
    private $work_511 = "개별메모";
    private $work_512 = "전체메모";
    private $work_513 = "추가옵션주문관리";
    private $work_514 = "추가옵션주문관리리셋";
    private $work_515 = "매칭내역취소";
    private $work_516 = "발주업로드";
    private $work_601 = "롯데아이몰주문확인";
    private $work_602 = "롯데닷컴api 다운로드";

    private $work_999 = "오류";

    private $timeout_101 = 60; // "송장 입력";
    private $timeout_102 = 60; // "송장 삭제";
    private $timeout_103 = 60; // "합포 추가";
    private $timeout_104 = 60; // "합포 제외";
    private $timeout_105 = 60; // "전체 취소";
    private $timeout_106 = 60; // "전체 정상 복귀";
    private $timeout_107 = 60; // "주문 복사 - 중복가능(seq)";
    private $timeout_108 = 60; // "주문 생성";
    private $timeout_109 = 60; // "주문 삭제";
    private $timeout_110 = 60; // "개별 취소";
    private $timeout_111 = 60; // "개별 정상 복귀";
    private $timeout_112 = 60; // "상품 교환 - 중복가능(seq)";
    private $timeout_113 = 60; // "상품 추가 - 중복가능(seq)";
    private $timeout_114 = 60; // "배송처리";
    private $timeout_115 = 60; // "배송취소";
    private $timeout_116 = 60; // "미송설정";
    private $timeout_117 = 60; // "합포금지";

    private $timeout_201 = 60; // "재고 작업";
    private $timeout_202 = 300; // "재고 일괄 조정";
    private $timeout_203 = 60; // "프리미엄 POS 입고";
    private $timeout_204 = 60; // "프리미엄 POS 출고";
    private $timeout_205 = 600; // "프리미엄 POS 배송";
    private $timeout_206 = 60; // "이지오토 배송";
    private $timeout_207 = 60; // "재고작업 취소";
    private $timeout_208 = 60; // "재고작업 취소복구";

    private $timeout_301 = 600; // "주문다운로드2";
    private $timeout_302 = 300; // "송장입력";
    private $timeout_303 = 60; // "송장출력(자체송장프로그램)";
    private $timeout_304 = 60; // "주문일괄삭제";
    private $timeout_305 = 60; // "송장일괄삭제(파일)";
    private $timeout_306 = 60; // "송장일괄삭제(조회)";
    private $timeout_307 = 60; // "배송일괄취소(파일)";
    private $timeout_308 = 60; // "주문다운로드2 초기화";
    private $timeout_309 = 60; // "배송취소";
    private $timeout_310 = 5; // "송장출력(자체송장프로그램)-연속";

    private $timeout_401 = 60; // "중복상품 삭제";
    private $timeout_402 = 300; // "중복상품 일괄삭제";
    private $timeout_403 = 60; // "옵션관리설정";
    private $timeout_404 = 60; // "옵션관리취소";
    private $timeout_405 = 60; // "상품삭제";
    private $timeout_406 = 60; // "옵션삭제";
    private $timeout_407 = 300; // "상품일괄수정";
    private $timeout_408 = 300; // "상품일괄선택수정";
    private $timeout_409 = 60; // "매칭전체삭제";
    private $timeout_410 = 60; // "매칭개별삭제";
    private $timeout_501 = 1800; // "자동일괄매칭";
    private $timeout_502 = 60; // "개별매칭 - 중복가능(seq)";
    private $timeout_503 = 300; // "발주전체합포";
    private $timeout_504 = 60; // "발주개별합포";
    private $timeout_505 = 300; // "발주완료";
    private $timeout_506 = 60; // "메모다시보기";
    private $timeout_507 = 60; // "전체발주삭제";
    private $timeout_508 = 60; // "개별발주삭제";
    private $timeout_509 = 60; // "전체cs발주삭제";
    private $timeout_510 = 60; // "개별cs발주삭제";
    private $timeout_511 = 60; // "개별메모";
    private $timeout_512 = 60; // "전체메모";
    private $timeout_513 = 60; // "추가옵션주문관리";
    private $timeout_514 = 60; // "추가옵션주문관리리셋";
    private $timeout_515 = 60; // "매칭내역취소";
    private $timeout_516 = 300; // "발주업로드";
    private $timeout_601 = 300; // "롯데아이몰주문확인";
    private $timeout_602 = 60; // "롯데닷컴api 다운로드";

    private $code;
    private $par;
    
    //*****************************
    //  생성자
    //*****************************
    function class_lock($code="999", $par="")
    {
        $this->code = $code;
        $this->par = $par;
    }

    //*****************************
    //  작업 시작
    //*****************************
    function set_start(&$msg, $wait=5)
    {
        global $sys_connect;

        // admin4 는 lock 안함
        if( $_SERVER[HTTP_HOST] == '_admin4.ezadmin.co.kr' )
            return 1;

        $work = $this->{"work_" . $this->code};
        $work_info = "set_start [$this->code][$this->par][" . session_id() . "][$work]";
        $logfile = $_SERVER[DOCUMENT_ROOT] . "/info/" . "lock_" . _DOMAIN_ . ".xml";

        if( $this->code == "999" )
        {
            $msg = "Lock 오류";
            debug( $work_info . $msg );
            return 0;
        }
        
        $val = array();

        // 1초씩 5번 체크
        for( $k=0; $k<$wait; $k++ )
        {
            // Lock 파일 읽기
            if( !$fp = @fopen($logfile, "r+") )
            {
                // Lock 파일 읽기 실패. 최초 실행
                if( !$fp = @fopen($logfile, "w") )
                {
                    // 파일 생성 실패
                    $msg = "Lock 파일 생성에 실패했습니다. 고객센터로 문의바랍니다.";
                    debug( $work_info . $msg );
                    return 0;
                }
    
                // 파일 만들기 성공. 빈 xml
                fwrite( $fp, '<?xml version="1.0" encoding="utf8"?><root/>' );
                fseek($fp, 0);
            }
    
            // Lock 파일 잠금
            if( !flock($fp, LOCK_EX) )
            {
                // Lock 파일 잠금 실패
                fclose($fp);
                $msg = "Lock 파일 잠금에 실패했습니다. 잠시후 다시 시도해주십시요.";
                debug( $work_info . $msg );
                return 0;
            }
    
            // Lock 파일이 빈 파일인 경우
            $f_data = trim(fread($fp, 256));
            if( $f_data == "" || $f_data == '<?xml version="1.0" encoding="utf8"?><root/>>' )
            {
                fseek($fp, 0);
                ftruncate($fp, 0);
                fwrite( $fp, '<?xml version="1.0" encoding="utf8"?><root/>' );
                fseek($fp, 0);
            }

            // 잠금 성공하면 파일 읽기 xml
            debug( $work_info . " Lock 파일 읽기 " . $k );
    
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->load( $logfile );
            $doc->formatOutput = true;
    
            $root = $doc->getElementsByTagName( "root" )->item(0);
            $proc = $root->getElementsByTagName( "proc" );
    
            // check code list
            $check_list = $this->{"lock_" . $this->code};
    
            ///////////////////
            // code 찾기
            $using = false;
            $file_write = false;
            for($i=0; $i<$proc->length; $i++)
            {
                $proc_code        = $proc->item($i)->getElementsByTagName( "code"        )->item(0)->nodeValue;
                $proc_user_id     = $proc->item($i)->getElementsByTagName( "user_id"     )->item(0)->nodeValue;
                $proc_session_id  = $proc->item($i)->getElementsByTagName( "session_id"  )->item(0)->nodeValue;
                $proc_work        = $proc->item($i)->getElementsByTagName( "work"        )->item(0)->nodeValue;
                $proc_par         = $proc->item($i)->getElementsByTagName( "par"         )->item(0)->nodeValue;
                $proc_start_time  = $proc->item($i)->getElementsByTagName( "start_time"  )->item(0)->nodeValue;
                $proc_update_time = $proc->item($i)->getElementsByTagName( "update_time" )->item(0)->nodeValue;

                // 현재시간과 update_time을 비교.
                $timeout = $this->{"timeout_" . $proc_code};
                $update_time = strtotime( $proc_update_time );
                if( time() - $update_time > $timeout )
                {
                    debug( "Lock Timeout $work_info: 
                            proc_code        = $proc_code        
                            proc_user_id     = $proc_user_id     
                            proc_session_id  = $proc_session_id  
                            proc_work        = $proc_work        
                            proc_par         = $proc_par         
                            proc_start_time  = $proc_start_time  
                            proc_update_time = $proc_update_time ");

                    // event
                    $query_event = "insert sys_event_list 
                                       set domain = '" . _DOMAIN_ . "', 
                                           who = '" . $_SESSION[LOGIN_ID] . "',
                                           event = 'Lock Timeout',
                                           cmt = '$proc_work'";
                    mysql_query($query_event, $sys_connect);

                    $root->removeChild( $proc->item($i--) );
                    $file_write = true;
                    continue;
                }

                // 확인 대상이 아니고, 동일 작업도 아니면 넘어간다.
                if( array_search($proc_code, $check_list) === false && $proc_code != $this->code )  continue;
    
                // 동일 작업일 경우
                if( $proc_code == $this->code )
                {
                    // par 틀리면 넘어간다. 작업 가능
                    if( $proc_par != $this->par )  
                        continue;
                }
    
                // par 같은 동일 작업이거나, check 대상 작업이 실행중. 
                $using = true;
                break;
            }
            
            // par 같은 동일 작업이거나, check 대상 작업이 실행중. 
            if( $using )
                sleep(1);
            else
                break;
        }

        // par 같은 동일 작업이거나, check 대상 작업이 실행중. 
        if( $using )
        {
            $msg = "작업자 [" . $proc_user_id . "] 님이 " . $proc_work . " 작업중입니다.";
            debug( $work_info . $msg);
        }
        else
        {
            $dom_code        = $doc->createElement("code"       );
            $dom_user_id     = $doc->createElement("user_id"    );
            $dom_session_id  = $doc->createElement("session_id" );
            $dom_work        = $doc->createElement("work"       );
            $dom_par         = $doc->createElement("par"        );
            $dom_start_time  = $doc->createElement("start_time" );
            $dom_update_time = $doc->createElement("update_time");
            
            $dom_code       ->appendChild($doc->createTextNode( $this->code         ));
            $dom_user_id    ->appendChild($doc->createTextNode( $_SESSION[LOGIN_ID] ));
            $dom_session_id ->appendChild($doc->createTextNode( session_id()        ));
            $dom_work       ->appendChild($doc->createTextNode( $work               ));
            $dom_par        ->appendChild($doc->createTextNode( $this->par          ));
            $dom_start_time ->appendChild($doc->createTextNode( date("Y-m-d H:i:s") ));
            $dom_update_time->appendChild($doc->createTextNode( date("Y-m-d H:i:s") ));

            $proc = $doc->createElement( "proc" );
            $proc->appendChild( $dom_code        );
            $proc->appendChild( $dom_user_id     );
            $proc->appendChild( $dom_session_id  );
            $proc->appendChild( $dom_work        );
            $proc->appendChild( $dom_par         );
            $proc->appendChild( $dom_start_time  );
            $proc->appendChild( $dom_update_time );

            $root->appendChild( $proc );
            $file_write = true;

            $val['possible'] = 1;
            $val['code'] = $this->code;
            $val['par'] = $this->par;
            $val['user'] = $_SESSION[LOGIN_ID];
            $val['session_id'] = session_id();
            debug( $work_info . " Lock 파일 작업 가능");
        }

        // 파일 변경
        if( $file_write )
        {
            // 파일 truncate
            fseek($fp, 0);
            ftruncate($fp, 0);
            fwrite($fp, $doc->saveXML() );
        }

        // 잠금해제. 파일 닫기
        flock( $fp, LOCK_UN );
        fclose($fp);
        debug( $work_info . " Lock 파일 닫기");
        
        return ( $using ? 0 : 1 );
    } 

    //*****************************
    //  작업 완료
    //*****************************
    function set_end(&$msg)
    {
        // admin4 는 lock 안함
        if( $_SERVER[HTTP_HOST] == '_admin4.ezadmin.co.kr' )
            return 1;

        $work = $this->{"work_" . $this->code};
        $work_info = "set_end   [$this->code][$this->par][" . session_id() . "][$work]";
        $logfile = $_SERVER[DOCUMENT_ROOT] . "/info/" . "lock_" . _DOMAIN_ . ".xml";

        // Lock 파일 읽기
        if( !$fp = @fopen($logfile, "r+") )
        {
            $msg = "Lock 파일 읽기에 실패했습니다. 고객센터로 문의바랍니다.";
            debug( $work_info . $msg );
            return 0;
        }

        // Lock 파일 잠금
        if( !flock($fp, LOCK_EX) )
        {
            fclose($fp);
            $msg = "Lock 파일 잠금에 실패했습니다. 고객센터로 문의바랍니다.";
            debug( $work_info . $msg );
            return 0;
        }

        // 잠금 성공하면 파일 읽기 xml
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->load( $logfile );
        $doc->formatOutput = true;

        $root = $doc->getElementsByTagName( "root" )->item(0);
        $proc = $root->getElementsByTagName( "proc" );
        $proc_len = $proc->length;

        ///////////////////
        // code 찾기
        for($i=0; $i<$proc_len; $i++)
        {
            $proc_code        = $proc->item($i)->getElementsByTagName( "code"        )->item(0)->nodeValue;
            $proc_user_id     = $proc->item($i)->getElementsByTagName( "user_id"     )->item(0)->nodeValue;
            $proc_session_id  = $proc->item($i)->getElementsByTagName( "session_id"  )->item(0)->nodeValue;
            $proc_work        = $proc->item($i)->getElementsByTagName( "work"        )->item(0)->nodeValue;
            $proc_par         = $proc->item($i)->getElementsByTagName( "par"         )->item(0)->nodeValue;
            $proc_start_time  = $proc->item($i)->getElementsByTagName( "start_time"  )->item(0)->nodeValue;
            $proc_update_time = $proc->item($i)->getElementsByTagName( "update_time" )->item(0)->nodeValue;

            // code, userid, session_id 일치 발견
            if( $proc_code       == $this->code         && 
                $proc_par        == $this->par          && 
                $proc_user_id    == $_SESSION[LOGIN_ID] )
            {
                // 이지오토에서 실행하는 롯데 lock은 세션 비교 안함
                if( $proc_code == 601 || $proc_code == 602 || $proc_session_id == session_id() )
                {
                    $root->removeChild( $proc->item($i) );
    
                    // 파일 truncate
                    fseek($fp, 0);
                    ftruncate($fp, 0);
                    fwrite($fp, $doc->saveXML() );
                    break;
                }
            }
        }

        // 잠금해제. 파일 닫기
        flock( $fp, LOCK_UN );
        fclose($fp);
        debug( $work_info . " Lock 작업 완료");
        
        return 1;
    }

}
?>
