<?
// class_EL00
// date: 2010.10.14

require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_db.php";
require_once "class_stock.php";

class class_EL00 extends class_top {

    ///////////////////////////////////////////
    // 
    function EL00()
    {
        global $connect;
        global $template;
     
        
     
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function EL02()
    {
        global $connect;
        global $template;
     
        
     
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function EL03()
    {
        global $connect, $template;
     
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    ///////////////////////////////////////////
    // 
    function EL01()
    {
        global $connect, $sms_seq;
     
        $query = "select * from multi_sms_title where sms_seq=$sms_seq order by crdate desc limit 20";    
        $result = mysql_query( $query, $connect );
        $row    = mysql_num_rows( $result );
        include "template/E/EL01.htm";
    }
    
    // 주문 상태 상세 출력
    function expand_desc()
    {
        global $connect, $seq;
        
        $query = "select order_id,shop_id, product_name, options, qty,recv_tel,recv_mobile from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        
        $query = "select * from csinfo where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $data["csinfo"]="";
        while($cs_data = mysql_fetch_assoc( $result ))
        {
        	$cs_data[cs_type] = $this->get_cs_type_str($t);
        	$data["csinfo"][] = $cs_data;
    	}
    	
        echo json_encode( $data );
    }
    function query_detail_list()
    {
        global $connect, $sms_seq, $page;
        
        $query     = "select *, b.memo b_memo, b.recv_mobile b_recv_mobile, b.status b_status from ";
        $query_cnt = "select count(*) cnt from ";
        
        $option .= " multi_sms_list b left outer join orders a on a.seq=b.order_seq
                      where b.sms_seq = $sms_seq
                        ";

        // count
        $result = mysql_query( $query_cnt . $option, $connect );
        $data   = mysql_fetch_assoc( $result );
        $arr_result = array();
        $arr_result['total'] = $data[cnt];
        
        // list 
        $line_per_page = 30;
        $starter = $page ? ($page - 1 )* $line_per_page: 0;
        
        $result = mysql_query( $query. $option . " order by b.status,b.seq ", $connect );
        
        $arr_error = array(
                  '0' => "성공"
                 ,'1' => "Time out"
                 ,'A' => "핸드폰 호 처리중"
                 ,'B' => "음영지역"
                 ,'C' => "Power Off"
                 ,'D' => "메시지 저장개수 초과"
                 ,'2' => "잘못된 전화번호"
                 ,'a' => "일시 서비스 중지"
                 ,'b' => "기타 단발기 문제"
                 ,'c' => "착신거절"
                 ,'d' => "기타"
                 ,'e' => "이통사 SMC 형식오류"
                 ,'f' => "IB 자체 형식 오류"
                 ,'g' => "SMS 서비스 단발 불가 단말기"
                 ,'h' => "핸드폰 호 불가 상태"
                 ,'i' => "SMC 운영자 메시지 삭제"
                 ,'j' => "이통사 내부 메시지 Que full"
                 ,'k' => "이통사 스팸처리"
                 ,'l' => "스팸처리"   
                 ,'m' => "전송사내부스팸처리"   
                 ,'n' => "건수제한오류"   
                 ,'o' => "메시지길이제한오류"   
                 ,'p' => "폰번호형식오류"   
                 ,'q' => "필드형식(내용)오류"   

				,  -1 => "잘못된 데이터 형식"
				,9903 => "선불사용자 사용금지"
				,9904 => "Block time ( 날짜제한 )"
				,9082 => "발송해제"
				,9083 => "IP차단"
				,9023 => "Callback error"
				,9905 => "Block time ( 요일제한 )"
				,9010 => "아이디틀림"
				,9011 => "비밀번호 틀림"
				,9012 => "중복접속량 많음"
				,9013 => "발송시간 지난 데이터"
				,9014 => "시간제한 (리포트 수신대기timeout)"
				,9020 => "Wrong Data Format"
				,9021 => "Wrong Data Format"
				,9022 => "Wrong Data Format"
				,9080 => "Deny User Ack"
				,9214 => "Wrong Phone Num"
				,9311 => "Fax File Not Found"
				,9908 => "PHONE,FAX선불사용자 제한기능"
				,9090 => "기타에러"

				,4100 => "전달"
				,4400 => "음영 지역"
				,4410 => "잘못된 번호"
				,4420 => "기타 에러"
				,4430 => "스팸"
				,4431 => "발송제한 수신거부"
				,4411 => "NPDB에러"
				,4412 => "착신거절"
				,4413 => "SMSC형식오류"
				,4414 => "비가입자,결번,서비스정지"
				,4421 => "타임아웃"
				,4422 => "단말기일시정지"
				,4423 => "단말기착신거부"
				,4424 => "URL SMS 미지원폰"
				,4425 => "단말기 호 처리중"
				,4426 => "재시도한도초과"
				,4427 => "기타 단말기 문제"
				,4428 => "시스템에러"
				,4401 => "단말기 전원꺼짐"
				,4402 => "단말기 메시지 저장 초과"
				,4403 => "메시지 삭제됨"
				,4404 => "가입자 위치정보 없음"
				,4405 => "단말기busy"

				,6600 => "전달"
				,6601 => "타임 아웃"
				,6602 => "핸드폰 호 처리 중"
				,6603 => "음영 지역"
				,6604 => "전원이 꺼져 있음"
				,6605 => "메시지 저장개수 초과"
				,6606 => "잘못된 번호"
				,6607 => "서비스 일시 정지"
				,6608 => "기타 단말기 문제"
				,6609 => "착신 거절"
				,6610 => "기타 에러"
				,6611 => "통신사의 SMC 형식 오류" 
				,6612 => "게이트웨이의 형식 오류"
				,6613 => "서비스 불가 단말기"
				,6614 => "핸드폰 호 불가 상태"
				,6615 => "SMC 운영자에 의해 삭제"
				,6616 => "통신사의 메시지 큐 초과"
				,6617 => "통신사의 스팸 처리"
				,6618 => "공정위의 스팸 처리"
				,6619 => "게이트웨이의 스팸 처리"
				,6620 => "발송 건수 초과"
				,6621 => "메시지의 길이 초과"
				,6622 => "잘못된 번호 형식"
				,6623 => "잘못된 데이터 형식"
				,6624 => "MMS 정보를 찾을 수 없음"
		        ,6625 => "번호이동사 아직 미등록" 
				,6670 => "첨부파일 사이즈 초과(60K)"

				,'00'     => "결과수신대기"
				,'01'    => "시스템 장애"
				,'02'    => "인증실패, 직후 연결을 끊음"
				,'03'    => "BIND 안됨"
				,'05'    => "착신가입자 없음(미등록)"
				,'06'    => "전송 성공"
				,'07'    => "비가입자,결번,서비스정지"
				,'08'    => "단말기 Power-off 상태"
				,'09'    => "음영"
				,'10'    => "단말기 메시지 FULL"
				,'11'    => "타임아웃"
				,'13'    => "번호이동"
				,'14'    => "무선망에러"
				,'17'    => "CallbackURL 사용자 아님"
				,'18'    => "메시지 중복 발송"
				,'19'    => "월 송신 건수 초과"
				,'20'    => "기타에러"
				,'21'    => "착신번호 에러(자리수에러)"
				,'22'    => "착신번호 에러(없는 국번)"
				,'23'    => "수신거부 메시지 없음"
				,'24'    => "21시 이후 광고"
				,'25'    => "성인광고, 대출광고 등 기타 제한"
				,'26'    => "데이콤 스팸 필터링"
				,'27'    => "야간발송차단"
				,'40'    => "단말기착신거부(스팸등)"
				,'70'    => "기타오류 - KTF URL"
				,'80'    => "결번(이통사 Nack) - SKT URL"
				,'81'    => "전송실패(정지고객등) - SKT URL"
				,'82'    => "번호이동 DB 조회불가 - SKT URL"
				,'83'    => "번호이동번호 - SKT URL"
				,'84'    => "타임아웃(이통사) - SKT URL"
				,'85'    => "전송실패(기타에러) - SKT URL"
				,'91'    => "발송 미허용 시간 때 발송 실패 처리"
				,'99'    => "중복실패처리"
        );
        
        $arr_status = array(
            '0'   => "대기"
            ,'1'  => "응답대기"
            ,'2'  => "전송완료"
            ,'-1' => "잔고부족"
            ,'-2' => "오류발생"
        );
        
        $idx = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
			if ($data['errcode'] == '0' || $data['errcode'] == '06') {
				$msg = "";
				$errmsg = "&nbsp;";
			} else {
				$msg = "(" . $data['errcode'] . ")" . $arr_error[$data['errcode']]; 
				$errmsg = "<img src='/images2/sms_error_icon.gif' title='$msg'/>";
			}

            $idx++;
            $data[idx]         = $idx;
            $data['error']     = $errmsg;
            $data['status']    = $arr_status[$data['b_status']];

            // 입고요청전표에서 공급처에 요청문자 보내기
            if( $data[order_seq] == 0 )  
            {
                $data['options'] 			= $data[b_memo];
                $data['recv_mobile'] 		= $data[b_recv_mobile];

                $data['shop_name'] 			= "&nbsp;";            
                $data['collect_date'] 		= "";
                $data['shop_product_id'] 	= "&nbsp;";
                $data['product_name'] 		= "&nbsp;";
                $data['qty'] 				= "&nbsp;";
                $data['order_name'] 		= "&nbsp;";
                $data['recv_name'] 			= "&nbsp;";
            }
            else
                $data['shop_name'] = class_shop::get_shop_name( $data[shop_id] );            

            $arr_result["lists"][] = $data;   
        }
        
        echo json_encode( $arr_result );
    }
    
    // 0: 대기
    // 1: 전송중
    // 2: 전송완료
    //-1: 잔고부족
    //-2: 오류발생
    function get_status( $sms_seq )
    {
        global $connect;
        
        $query = "select status,count(*) cnt from  multi_sms_list
                     where sms_seq = $sms_seq
                       group by status";

        $result = mysql_query( $query, $connect );     
        $row    = mysql_num_rows( $result );
        
        $arr_status["sum"] = 0;
        $arr_status["1"] = 0;
        $arr_status["2"] = 0;
        $arr_status["-1"] = 0;
        $arr_status["-2"] = 0;
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            //$arr_status[$arr_key[$data[status]]] =  $data[cnt];
            $arr_status[$data[status]] =  $data[cnt];
            $arr_status["sum"]         += $data[cnt];
        }
        
        $arr_status['status'] = "접수";
        
		//----------------------------
		// 전송중(1) 하나라도 있으면 전송중
		// 0인게 있으면 접수 (실제로 1이 하나도 없는 경우임)
		// 1, 0 모두 하나도 없으면 전송완료
		
        
        if ( $arr_status['-1'] > 0)
            $arr_status['status'] = "<font color=red><b>잔고부족</b></font>";
        else if ( $arr_status['0'] == 0  && $arr_status['1'] == 0)
            $arr_status['status'] = "전송완료";
        else if ( $arr_status['2'] > 0 )
            $arr_status['status'] = "결과수신중";
        else if ( $arr_status['1'] > 0 )
            $arr_status['status'] = "전송중";
        else if ( $arr_status['0'] > 0 )
            $arr_status['status'] = "접수";

        return $arr_status;
    }

    // 해당 SMS_SEQ에 대하여 사용된 SMS 개수를 리턴한다.(syhwang)
    function get_sent_sms ($sms_seq)
	{
		global $connect;

		$sql = "select sum(sent) sent from multi_sms_list where sms_seq = $sms_seq";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if ($list[sent] == null) return 0;
		else return $list[sent];
	}
    
    // multi_sms_list의 성공(2)가 아닌 sms전송 요청을 전부 대기(0)으로 변경한다.
    function re_send()
    {
        global $connect,$seq;

		// 9 번 서버에 connect
        // sys_sms_multi
        // db_name : ezadmin, db_pwd : ez8282, user : ezadmin
		$_connect = sys_db_connect();

        $query = "update sys_sms_multi
                     set status    = 0
                         ,reg_date  = Now()
					where sms_seq   = $seq
                      and domain     = '" . _DOMAIN_ . "'";
	    debug( "re_send: $query");
        mysql_query( $query, $_connect );       

		// 갱신 
        $query = "update multi_sms_list set status=0 where sms_seq=$seq and status <> 2";
	    debug( "re_send: $query");
        mysql_query( $query, $connect );
    }
    
    // multi_sms_list의 성공(2)가 아닌 sms전송 요청을 전부 삭제.
    function cancel_send()
    {
        global $connect;

		$seq = $_REQUEST[seq];
        
        $query = "delete from multi_sms_list where sms_seq=$seq and status <> 2";
        mysql_query($query, $connect);
    }
    
    // multi_sms_title, multi_sms_list의 자료 삭제
    // 성공건이 1건이라도 있는경우는..title은 삭제 하지 않는다.
    function del_send()
    {
        global $connect,$seq;
        
        // 성공건이 있는지 확인
        $query = "select count(*) cnt from multi_sms_list where sms_seq=$seq and status =2";
        $result = mysql_query ($query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $arr_result = array();
        $arr_result["error"] = 0;
        $arr_result["query"] = 0;
        $arr_result["cnt"]   = 0;
        
        if ( $data[cnt] > 0 )
        {
            $arr_result["error"] = 1;
            $arr_result["error_message"] = "전송완료가 있는경우 삭제 할 수 없습니다";
        }
        else
        {
            // multi_sms_title삭제
            $query = "delete from multi_sms_title where sms_seq=$seq";
            mysql_query( $query, $connect );
            
            // multi_sms_list 삭제   
            $query = "delete from multi_sms_list where sms_seq=$seq";
            mysql_query( $query, $connect );
        }
        
        echo json_encode( $arr_result );
    }

	function send_sms_excel_massive()
	{
		global $connect, $memo, $sender_mobile, $mobile_list, $name_list, $coupon_list;

        debug("=============== send_sms() START ====================");                 
        $memo = str_replace( "'","", $memo );

        // multi_sms_title 저장
        $query = "insert into multi_sms_title 
                     set crdate  	  = now(),
                         owner 		  = '" . $_SESSION[LOGIN_NAME] . "',
						 product_name = 'SMS Excel 대량 전송',
						 start_date   = now(),
                         memo 		  = '$memo'";
        debug( $query );                 
        mysql_query( $query, $connect );

        $result = mysql_query("SELECT LAST_INSERT_ID() sms_seq", $connect);
        $data   = mysql_fetch_assoc($result );
        $sms_seq = $data[sms_seq];

		$mobile_list = str_replace("&nbsp;", "", $mobile_list);
		$mobile_list = explode(",", $mobile_list);

		$name_list = str_replace("&nbsp;", "", $name_list);
		$name_list = explode(",", $name_list);

		$coupon_list = str_replace("&nbsp;", "", $coupon_list);
		$coupon_list = explode(",", $coupon_list);
	
		$len = sizeof( $mobile_list ) - 1;

		for ( $i = 0; $i < $len ; $i++ )
		{
			$memo_each = str_replace("[이름]", $name_list[$i], $memo);
			$memo_each_ = str_replace("[쿠폰]", $coupon_list[$i], $memo_each);
	
	        $query = "insert into multi_sms_list 
    	                 set sms_seq       = $sms_seq,
        	                 recv_mobile   = '$mobile_list[$i]',
            	             send_mobile   = '$sender_mobile',
                	         memo          = '$memo_each_',
                    	     status        = 0,
	                         reg_date      = Now()";   
	        debug( $query );
			$total_cnt++;
	        // cs에 insert
    	    mysql_query( $query, $connect );  
		}	
	
		// 
		$query = "insert into tbl_sms_history 
					 set crdate = now(),
						 msg  = '$memo',
						 sent = $total_cnt";
		mysql_query( $query, $connect );

        // 9 번 서버에 connect
        // sys_sms_multi
   	    // db_name : ezadmin, db_pwd : ez8282, user : ezadmin
   	    $_connect = sys_db_connect();
        
        $query = "insert into sys_sms_multi
   	                 set domain    = '" . _DOMAIN_ . "',
       	                 sms_seq   = $sms_seq,
           	             req_count = $total_cnt,
               	         status    = 0,
                   	     reg_date  = now()";

		@mysql_query($query, $_connect);

		echo "전송되었습니다";
	}

    //
    function send_sms()
    {
        global $connect, $memo, $send_single, $sender_mobile,$update_sender_mobile;
        
        debug("=============== send_sms() START ====================");                 
        $memo = str_replace( "'","", $memo );
        
        // multi_sms_title 저장
        $query = "insert into multi_sms_title 
                     set crdate= Now()
                        ,owner='" . $_SESSION[LOGIN_NAME] . "'
                        ,memo = '$memo'";
        
        if ( $send_single == 1)
            $query .= ",send_single=$send_single";                       

        debug( $query );                 
        mysql_query( $query, $connect );
        
        $result = mysql_query("SELECT LAST_INSERT_ID() sms_seq", $connect);
        $data   = mysql_fetch_assoc($result );
        $sms_seq = $data[sms_seq];
        
        // 조회 조건 저장.
        $this->save_search_option( $sms_seq );
                
        // multi_sms_list 저장
        $no_limit = 1;
        $query = $this->build_query( &$total_count, $no_limit );      
        
        debug( "<send sms> : $query" );  
        
        $result = mysql_query ($query, $connect );
        
        $arr_result = array();
        $arr_result["total"] = $total_count;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->insert_sms( $data, $sms_seq, $memo );
        }
                        
        // 전화 번호 저장
        // 전송자 전화번호 수정을 체크해야지만 수정됨.
        if ( $update_sender_mobile == "1")
        {
            $query = "update multi_sms_macro set send_mobile='$sender_mobile'";
            mysql_query( $query, $connect );
        }


        // 9 번 서버에 connect
        // sys_sms_multi
        // db_name : ezadmin, db_pwd : ez8282, user : ezadmin
        $_connect = sys_db_connect();
        
        $query = "insert into sys_sms_multi
                     set domain     = '" . _DOMAIN_ . "'
                         ,sms_seq   = $sms_seq
                         ,req_count = $total_count
                         ,status    = 0
                         ,reg_date  = Now()
                         ";
		debug($query);
        $rslt = mysql_query( $query, $_connect ); 
		if ($rslt === FALSE) {
			debug(mysql_error($_connect));
		}
        
    }
   
 
    function load_macro()
    {
        global $connect;
        
        $query = "select * from multi_sms_macro order by seq desc";   
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    function del_macro()
    {
        global $seq, $connect;
        
        $query = "delete from multi_sms_macro where seq=$seq";
        mysql_query( $query, $connect );
    }
    
    function save_macro()
    {
        global $connect, $sender_mobile,$memo;
        
        $memo = str_replace( "'","", $memo );
        $query = "insert multi_sms_macro set memo='$memo', send_mobile='$sender_mobile',crdate=Now()";
        mysql_query( $query, $connect );
    }
    
    function insert_sms( $data, $sms_seq, $memo )
    {
        global $connect, $sender_mobile, $send_single;        
        $memo = $this->convert_macro($data,$memo);
        $memo = str_replace( "'","", $memo );
        
        if ( $send_single == 1 )
        {
            $arr_memo = $this->substr_kor( $memo, 90 );    
            $memo = $arr_memo[0];
        }
        
        $recv_mobile = preg_replace("/[^0-9]/","",$data[recv_mobile]);
        
        $query = "insert into multi_sms_list 
                     set sms_seq       = $sms_seq
                        ,order_seq     = $data[seq]
                        ,recv_mobile   = '$recv_mobile'
                        ,send_mobile   = '$sender_mobile'
                        ,memo          = '$memo'
                        ,status        = 0
                        ,reg_date      = Now()
                        ";   
        //debug( $query );
        
        // cs에 insert
        mysql_query( $query, $connect );                     
    }
    
    function convert_macro( $data, $memo )
    {
    	//최웅
    	global $is_packed, $connect;
    	
        $shop_name    = class_shop::get_shop_name( $data[shop_id] );        
        
        // lalael2는 상품명을 10 자로 보내달라 - 김보애 요청 2012.09.05 요청
		// lalael2 10->20으로 수정 syhwang 2012.11.29 (20byte -> 40byte)
		// tobbyous 16자 (16byte) syhwang 2012.12.11
		// ilovej 요청 (20byte) syhwang 2013.8.28

        if ( _DOMAIN_ == "lalael2" )
            $_arr         = $this->substr_kor( $data[name], 40 );
        else if ( _DOMAIN_ == "tobbyous" )
            $_arr         = $this->substr_kor( $data[name], 16 );
        else if ( _DOMAIN_ == "ilovej" )
            $_arr         = $this->substr_kor( $data[name], 20 );
        else if ( _DOMAIN_ == "alice"  ||  _DOMAIN_ == "polotown"  )
            $_arr[0]      = $data[name];
        else
            $_arr         = $this->substr_kor( $data[name], 10 );
       
        //합포검색 이면서 합포인경우
        if(( _DOMAIN_ == "ezadmin"  || _DOMAIN_ == "alice"  ||  _DOMAIN_ == "polotown")  && $is_packed == 1 )
        {
        	if( $data[pack] > 0)
        		$__query = "select c.name, c.options from orders a, order_products b , products c where a.seq = b.order_seq and b.product_id = c.product_id and a.pack = '$data[pack]'";
        	else 
        	 	$__query = "select c.name, c.options from orders a, order_products b , products c where a.seq = b.order_seq and b.product_id = c.product_id and b.order_seq = '$data[seq]'";
debug($__query);
        	$__result = mysql_query ($__query, $connect );
        	$_arr[0] = "";
	        while ( $__data = mysql_fetch_assoc( $__result ) )
	            $_arr[0] .= $__data[name].$__data[options].", ";
	        $_arr[0] = substr($_arr[0], 0, -2); //맨뒤의 ", " 제거
        }
        
        $product_name = $_arr[0] . "..";
        
        // 상품 메모 추가 2011.1.25 - jkryu
        $_arr         = $this->substr_kor( $data[memo], 14 );
        $product_memo = $_arr[0] . "..";
        
        $memo = preg_replace("/\[판매처\]/",   $shop_name       , $memo );
        $memo = preg_replace("/\[수령자\]/",   $data[recv_name] , $memo );
        $memo = preg_replace("/\[옵션\]/"  ,   $data[options]   , $memo );
        $memo = preg_replace("/\[주문자\]/",   $data[order_name], $memo );
        $memo = preg_replace("/\[상품명\]/",   $product_name    , $memo );
        $memo = preg_replace("/\[상품메모\]/", $product_memo    , $memo );
        $memo = preg_replace("/\[주문번호\]/", $data[order_id]  , $memo );
        
        // 송장번호 추가 2011.1.28 - jkryu
        $memo = preg_replace("/\[송장번호\]/", $data[trans_no], $memo );
        
        debug( "sms전송내용:" . $memo );

        return $memo;
    }
    
    // 전송 결과 조회
    function query_result()
    {
        global $connect, $page, $product_name, $options,$product_id,$shop_product_id,$shop_id,$supply_id,$status,$order_cs,$start_date,$end_date,$sms_status;
        
        $query = "select * ";
        
        $option = "from multi_sms_title 
                   where crdate >= '$start_date 00:00:00'
                     and crdate <= '$end_date 23:59:59'
                     ";

        if ( $product_name )
            $option .= " and product_name like '%$product_name%'";                     
        
        if ( $options )
            $option .= " and options like '%$options%'";
            
        if ( $product_id )
            $option .= " and product_id = '$product_id'";
            
        if ( $shop_product_id )
            $option .= " and shop_product_id = '$shop_product_id'";
            
        
        if ( $shop_id )
            $option .= " and shop_id = '$shop_id'";
            
        
        if ( $supply_id )
            $option .= " and supply_id = '$supply_id' ";
        
        // total count
        $arr_result = array();
        
        
        $query_cnt = "select count(*) cnt ";
        
        //echo ($query_cnt . $option);
        
        $result = mysql_query( $query_cnt . $option, $connect );
        $data   = mysql_fetch_assoc( $result );
        $total  = $data[cnt];
        $arr_result["total"] = $total;
        
        // 실제 row정보        
        $line_per_page = 30;
        $starter = $page ? ($page - 1)*$line_per_page : 0;
        $result = mysql_query( $query . $option . " order by sms_seq desc", $connect );
        

        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_status 	   = $this->get_status( $data[sms_seq] );

			//-- 전송완료 출력 제외 2013.8.1 syhwang
			if ($sms_status == -1 && $arr_status[status] == '전송완료') {
				continue;
			}

			//-------------
            $data[status]      = $arr_status[status];
            $data[cnt]         = $arr_status[sum];
            $data[success_cnt] = $arr_status["2"];
            $data[sent] 	   = $this->get_sent_sms($data[sms_seq]);

            $data[product_name]    = $data[product_name] ? $data[product_name] : "&nbsp;";
            $data[options]         = $data[options] ? $data[options] : "&nbsp;";
            $data[shop_product_id] = $data[shop_product_id] ? $data[shop_product_id] : "&nbsp;";
            $data[product_id]      = $data[product_id] ? $data[product_id] : "&nbsp;";
                        

            $arr_result["lists"][] = $data;
        }
                
        echo json_encode( $arr_result );
    }
    
    function query()
    {
        global $connect;
        
        $query = $this->build_query( &$total_count );        
        $result = mysql_query ($query, $connect );
        
        $arr_result = array();
        $arr_result["total"] = $total_count;
        $obj_stock = new class_stock();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[shop_id]     = class_shop::get_shop_name( $data[shop_id] );
            $data[supply_name] = $this->get_supply_name2( $data[supply_code] );
            $data[stock]       = $obj_stock->get_current_stock( $data[product_id] );
            
            switch( $data[status] )
            {
                case "8": $data[status]="<img src=images/icon_04.gif>";break;
                case "7": $data[status]="<img src=images/icon_06.gif>";break;
                default: 
                    // icon_15.gif 발주
                    if ( $data[status] == 0 )
                        $data[status] = "<img src=images/icon_15.gif>";
                    else    
                        $data[status] = "<img src=images/icon_05.gif>"; 
                    break;
            }    
            $arr_result["list"][] = $data;   
        }
        
        echo json_encode( $arr_result );
    }
    
    function save_search_option( $sms_seq )
    {
        global $connect,$product_name, $options,$product_id,$shop_product_id,$shop_id,$supply_id,$status,$order_cs,$start_date,$end_date;
        $query = "update multi_sms_title 
                     set product_name     ='$product_name'
                         ,options         = '$options'
                         ,product_id      = '$product_id'
                         ,shop_product_id = '$shop_product_id'
                         ,shop_id         = '$shop_id'
                         ,supply_id       = '$supply_id'
                         ,status          = $status
                         ,order_cs        = $order_cs
                         ,start_date      = '$start_date'
                         ,end_date        = '$end_date'
                   where sms_seq=$sms_seq
                         ";
                         
        debug( $query );                         
        mysql_query( $query, $connect );                         
    }
    
    //
    // 부분 배송 정보가 있는지 확인. 2011.5.23 - jk
    // part_seq가 동일한 seq혹은 pack이 다른 정상 주문이 있는경우 부분 배송.
    // 정상 주문이 없는경우 완료배송..
    //
    function check_part_deliv( $seq, $pack, $part_seq )
    {
        global $connect,$status;
        $query = "select count(distinct(pack)) cnt_pack,count(distinct(status)) cnt_status
                    from orders 
                   where part_seq=$part_seq 
                   group by part_seq";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[cnt_pack] >=2 || $data[cnt_status] >= 2 )
            return 1;
        else
            return 0;
    }
    
    //
    // 원래 로직 수정됨..2013.5.3 - jkryu
    //
    function check_part_deliv_org( $seq, $pack, $part_seq )
    {
        global $connect,$status;
        
        $_arr_status = array( 1=>"0", 2=>"1",3=>"7",4=>"8",5=>"1,2,7");        
        
        // 접수로 검색하면 배송 나간것이 있는지 확인
        // 배송, 송장으로 검색하면 접수건이 있는지 확인..        
        // 배송
        if ( $status == 2 )
            $str_status = "7,8";
        else
            $str_status = "1,7";
            
        $query = "select count(*) cnt 
                    From orders 
                   where part_seq = $part_seq 
                     and status in ( $str_status ) 
                     and order_cs not in (1,2,3,4) ";
                
        /*
        if ( $pack )
            $query .= " and pack <> $pack";
        else
            $query .= " and seq <> $seq";
        */
        
        //debug( "check_part_deliv: $query ");
            
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[cnt] ? 1 : 0;
    }
    
    function build_query( &$total_count = 0, $no_limit=0)
    {
        global $page,$connect,$product_name, $options,$product_id,$shop_product_id,$shop_id,$supply_id,$status,$order_cs,$start_date,$end_date,$str_seqs,$product_match, $enable_sale,$enable_sale_only,$date_type,$part_deliv,$is_packed, $is_hold;
        global $start_hour, $end_hour,$location;
        
        // 원 상품코드, 옵션 상품코드 여부 확인
        $str_product_id = "";
		if ( $product_id )
            $str_product_id = "'$product_id'";
       
        if ( substr( $product_id , 0,1 ) != "S" )
        {
            $query = "select product_id from products where org_id=" . $product_id;
 
            if ( $enable_sale == "1")
                $query .= " and enable_sale=0";
            
            if ( $enable_sale_only == "1")
                $query .= " and b.enable_sale=1";
            
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $str_product_id = $str_product_id ? "," : "";
                $str_product_id = "'$data[product_id]'";
            }
        }
       
        // location 2014.10.13 - jkryu 수정
        if ( $location )
        {
            $query = "select product_id from products where location='" . $location. "'";

debug("enable_sale:" . $enable_sale );           
            
            if ( $enable_sale == "1")
                $query .= " and enable_sale=0";
            
            if ( $enable_sale_only == "1")
                $query .= " and b.enable_sale=1";
            
debug( $query );

            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
				if ( $data[product_id] !== "" )
                {
                    $str_product_id .= $str_product_id ? "," : "";
                    $str_product_id .= "'$data[product_id]'";
                }
            }
        }

 
debug( "str_product_id" . $str_product_id );

        // 실제 쿼리 생성
        $query = "select a.order_id,a.shop_id, a.collect_date, c.product_id,a.qty,a.status,a.order_cs, a.trans_no
                        ,a.recv_name, a.recv_mobile,a.seq, a.pack,a.order_name,a.seq
                        ,b.supply_code ,b.name,b.options,b.memo, if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx ";

        // start_hour, end_hour 추가
        if ( $date_type != "collect_date")
        {
            //$start_date = $start_date . " 00:00:00";
            //$end_date   = $end_date . " 23:59:59";
            
            $start_date = $start_date . " " . $start_hour . ":00:00";
            $end_date   = $end_date   . " " . $end_hour   . ":59:59";
        }
        
        
        // 주문 상태 부분 배송 사용..
        if ( $status == 6 )
        {
            $_opt = "  from orders a, products b, order_products c
                       where a.seq        = c.order_seq
                         and c.product_id = b.product_id ";
            
        }
        else
        {
            $_opt = "  from orders a, products b, order_products c
                       where a.seq        = c.order_seq
                         and c.product_id = b.product_id ";

            // start_hour, end_hour추가.                         
            if ( $date_type == "collect_date" )
            {
                $_opt .= " and ( a." . $date_type . " >= '$start_date' and a.collect_time >='" . $start_hour . ":00:00' )
                           and ( a." . $date_type . " <= '$end_date'   and a.collect_time <='" . $end_hour   . ":59:59')";
            }
            else
            {
                $_opt .= " and a." . $date_type . " >= '$start_date'
                           and a." . $date_type . " <= '$end_date'";
            }
        }
        
        
        
        
        // 부분 배송상태로 조회, 조회 날짜는 배송일.
        // 오늘 배송 건중에 주문번호가 동일한 접수 상태의 주문이 있는지 조회.
        if ( $status == 6 )
        {
            // 배송 상태의 주문을 조회..해서 동일한 주문 번호의 접수 상태 주문이 있는지 확인.
            if ( $date_type == "collect_date" )
            {
                $_query = "select seq from orders 
                          where status=1 
                            and order_id in (
                                select order_id 
                                  from orders 
                                 where ( collect_date >= '$start_date' and collect_time >='" . $start_hour . ":00:00' )
                                   and ( collect_date <= '$end_date'   and collect_time <='" . $end_hour   . ":00:00' )
                                   and status = 8)";
            }
            else
            {
                $_query = "select seq from orders 
                          where status=1 
                            and order_id in (
                                select order_id 
                                  from orders 
                                 where $date_type >='$start_date'
                                   and $date_type <= '$end_date'
                                   and status = 8)";
            }
            
            $result = mysql_query( $_query, $connect );
            $seqs = "";
            while ( $data = mysql_fetch_assoc( $result ) )
            {   
                $seqs .= $seqs ? "," : "";
                $seqs .= $data[seq];         
            }                        

            $_opt .= " and a.seq in ( $seqs ) "; 
                                      
        }
        
        //    part_deliv    
        // 부분배송: 1, 완전 배송: 2
        // 
        if( $part_deliv == 1 || $part_deliv == 2 || $part_deliv == 3 )
        {
            $_arr_status = array( 1=>"0", 2=>"1",3=>"7",4=>"8",5=>"1,2,7",6=>"8");
            //$_opt .= " and a.status in ( " . $_arr_status[$status] . ") ";
            
            $part_query = "select a.part_seq,a.seq,a.pack 
                             from orders a, order_products b
                            where a.seq = b.order_seq
                              and a.$date_type >= '$start_date'
                              and a.$date_type <= '$end_date'";
            
            if ( $_arr_status[$status] )
                $part_query .= " and a.status in (" . $_arr_status[$status] . ")";
                              
            $part_query .= " and a.part_seq <> 0";
            
            if ( $is_packed == 1 )
                $part_query .= " group by a.part_seq"; 

            debug( "part_deliv vxvx:" . $part_query );
            
            $idx = 1;           // 부분배송
            if ( $part_deliv == 2 )
                $idx = 0;       // 완전배송
            
            $seqs = "";
            $result = mysql_query( $part_query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) ) 
            {                
                // 값이 1이면 부분 배송...
                // 0 이면 완전 배송..                
                if ( $this->check_part_deliv( $data[seq],$data[pack], $data[part_seq] ) == $idx)
                {
                    $seqs .= $seqs ? "," : "";
                    $seqs .= $data[seq];   
                }
            }
            
            if( $part_deliv == 1 && $idx = 1)
            	$_opt .= " and a.seq in ( $seqs ) ";   
            else if( $part_deliv == 3 && $idx = 1)
            	$_opt .= " and a.seq not in ( $seqs ) ";   
        }
        

        //
        // 제외할 번호
        if ( $str_seqs )
        {
            $_opt .= " and a.seq not in ( $str_seqs ) ";
        }

        // 이지 상품코드
        if ( $str_product_id )
        {
            $_opt .= " and c.product_id in ( $str_product_id )";
        }

        // 판매처 상품코드
        if ( $shop_product_id )
        {
            $_opt .= " and a.shop_product_id = '$shop_product_id'";
        }        
        
        // 판매가능 여부
        if ( $enable_sale == 1)
            $_opt .= " and b.enable_sale=0";
            
        if ( $enable_sale_only == 1)
            $_opt .= " and b.enable_sale=1";
                
        // 상품명( 어드민 or 판매처 )
        if ( $product_name )
        {
        	if($product_match == 1)
            	$_opt .= " and ( b.name = '$product_name' )";   
            else 
            	$_opt .= " and ( b.name like '%$product_name%' or a.product_name like '%$product_name%' )";   
        }
        
        // 옵션( 어드민 or 판매처 )
        if ( $options )
        {
            $options = str_replace( " ", "%", $options );
            $_opt .= " and ( c.shop_options like '%$options%' or b.options like '%$options%' or a.options like '%$options%')";   
        }
        
        //debug( $_opt );
        
        // 판매처
        if ( $shop_id )
            $_opt .= " and a.shop_id = $shop_id";
        
        // 공급처
        if ( $supply_id )
            $_opt .= " and b.supply_code = $supply_id";
        
        // 주문 상태
        if ( $status && $status<>6 && $status <> 7 )
        {
            // 0: 전체
            // 1: 접수
            // 2: 정상.
            // 3: 송장
            // 4: 배송
            // 5: 접수 + 송장 
            $_arr_status = array( 1=>"0", 2=>"1",3=>"7",4=>"8",5=>"1,2,7",6=>"8");
            
            $_opt .= " and a.status in ( " . $_arr_status[$status] . ") ";
            
        }
            
        debug( "order_cs: $order_cs ");

        // cs 상태
        if ( $order_cs )
        {
            // 정상
            if ( $order_cs == 1 )
                $_opt .= " and a.order_cs = 0";
            // 정상 + 배송전 교환 + 배송후 교환
            else if ( $order_cs == 11 )
                $_opt .= " and a.order_cs in (0,5,6,7,8)";
            // 배송전 배송 후 취소
            else if ( $order_cs == 2 )
                $_opt .= " and a.order_cs in (1,2,3,4)";
            // 교환
            else if ( $order_cs == 3 )
                $_opt .= " and a.order_cs in (5,6,7,8)";
            //배송전 취소
            else if ( $order_cs == 4 )
                $_opt .= " and a.order_cs in (1,2)";
            //배송후 취소
            else if ( $order_cs == 5 )
                $_opt .= " and a.order_cs in (3,4)";
            // 배송전 교환
            else if ( $order_cs == 6 )
                $_opt .= " and a.order_cs in (5,6)";
            // 배송후 교환
            else if ( $order_cs == 7 )
                $_opt .= " and a.order_cs in (7,8)";
            // 보류                
            else if ( $order_cs == 8 )
                $_opt .= " and a.hold > 0";
            // 미송 , for dabagirl 2011.1.27 추가
            else if ( $order_cs == 9 )
            {
                $_opt .= " and c.misong = 2";
            }
            // 배송후 교환건 포함 안함    2012.11.5 - jk
            else if ( $order_cs == 10 )
            {
                $_opt .= " and a.c_seq = 0 ";
                $_opt .= " and a.order_cs = 0";
            }
        }
        if ( $is_hold == 1 )
        {
        	$_opt .= " and a.hold = 0";
    	}
        //
        // enable_sale이 1일경우 품절 상품
        // 품절 상품의 경우 합포와 상관없이 상품별로 물량을 배송해야 함..
        //
        if ( $is_packed != 1  )
        {
            // total count
            //$query_cnt   = "select count(distinct recv_mobile) cnt ";
            $query_cnt   = "select count( * ) cnt ";
            $result      = mysql_query( $query_cnt . $_opt, $connect );
            $data        = mysql_fetch_assoc( $result );
            $total_count = $data["cnt"];
                    
            // $_opt .= " group by a.recv_mobile";
            // $_opt .= " group by b.product_id";
        }
        else
        {
            // total count
            //$query_cnt   = "select count(distinct recv_mobile) cnt ";
            $query_cnt   = "select count( distinct( if(a.pack<>null||a.pack<>0,a.pack, a.seq ) ) ) cnt ";
            $result      = mysql_query( $query_cnt . $_opt, $connect );
            $data        = mysql_fetch_assoc( $result );
            $total_count = $data["cnt"];
                    
            //$_opt .= " group by a.recv_mobile";
            //$_opt .= " group by xx";
        }
        
        if ( $is_packed == 1 )
            $_opt .= " group by xx";
        
        $start = ($page - 1) * 30;
        
        // limit
        if ( !$no_limit )
            $_opt .= " limit $start, 30";

        //echo $query . $_opt;
        debug( "is_packed in build_query: $is_packed ");
        debug( "build_query: " . $query . $_opt );
        return $query . $_opt;
    }
    
	//-------------------------------------------------
	// SMS
    function upload_csv()
    {
		global $connect;

		$file = $_REQUEST[pop3_file];
		$csv_file = $_FILES['pop3_file']['name'];


		if ($csv_file) {
			$file_params = pathinfo($_FILES['pop3_file']['name']);
			$file_ext = strtoupper($file_params['extension']);
			if ($file_ext != "CSV") {
				echo "<script>alert('잘못된 파일포맷입니다. csv파일만 지원하니다.');</script>";
				exit;
			}
		}

		$upload_dir = "/home/ezadmin/public_html/shopadmin/data/" . _DOMAIN_ . "/";
		if (!move_uploaded_file($_FILES['pop3_file']['tmp_name'], $upload_dir.$_FILES['pop3_file']['name'])) {
			echo "<script>alert('업로드 오류.');</script>";
			exit;
		}

		$strHTML = "";
		$data = file($upload_dir.$csv_file);
		for ($i=0; $i < count($data); $i++) 
		{
			$array = explode(",", $data[$i]);

			$name = trim(iconv('euckr', 'utf-8', $array[0]), "\r\n");
			$mobile = trim($array[1], "\r\n");

			if (substr($mobile,0,1 != '0')) continue;

			$coupon = trim(iconv('euckr', 'utf-8', $array[2]), "\r\n");

			$strHTML .= "<div class=item id=$mobile><div class=mobile>$mobile</div><div class=name>$name</div><div class=coupon>$coupon</div><div class=chk>&nbsp;<a href=javascript:del_item(\"$mobile\");><img src=/images/sms/icon_del_small.gif align=absmiddle></a></div></div>";
		}

		echo "<script>window.top.window.upload_completed('$strHTML');</script>";
		exit;
    }

/*
    function send_sms_excel()
    {
			global $connect;

			$template 	= $_REQUEST[template];
			$message    = $_REQUEST[ta2];
//			$msglen 	= $_REQUEST[msglen];
			$sender 	= $_REQUEST[sender_mobile];

			$mobilelist	= str_replace("&nbsp;", "", $_REQUEST[mobilelist]);
			$mobilelist 	= explode(",", $mobilelist);

			$namelist	= str_replace("&nbsp;", "", $_REQUEST[namelist]);
			$namelist 	= explode(",", $namelist);

			debug("mobile=($_REQUEST[mobilelist]) name=($_REQUEST[namelist])");

			$sent = 0;
			for ($i=0; $i < sizeof($mobilelist); $i++)
			{
				$receiver = trim($mobilelist[$i]);
				$name     = trim($namelist[$i]);
				if ($mobile == "") continue;
				if (strlen($mobile) < 10) continue;

				$msg = str_replace("[이름]", $name, $message);
//				$len = strlen(iconv('utf-8', 'euckr', $msg));

				$sent++;
//				class_sms::send($len, $mobile, $sender, $message);
				class_sms::send();
			}

			$ins_sql = "insert into tbl_sms_history set
							msg  = '$message',
							sent = $sent";
			@mysql_query($ins_sql, $connect);

			echo "<script>alert('전송되었습니다.');</script>";
	}
*/

    function pop1_search()
    {
			global $connect;

			$keyword = $_REQUEST[keyword];

				switch ($_REQUEST[mtype])
				{
				case 0 : $where_options = " and memb_type = 0 and comm_type = 0";
						 break;
				case 1 : $where_options = " and memb_type = 0 and comm_type = 1";
						 break;
				case 2 : $where_options = " and memb_type = 0 and comm_type = 2";
						 break;
				case 3 : $where_options = " and memb_type = 1";
						 break;
			default : $where_options = ""; break;
				}   

			if ($keyword) {
				$where_options .= " and name like '%$keyword%'";
			}   
			  
			$strHTML = "";
			$sql = "select name, mobile from tbl_member
				 where mobile != '' ${where_options}";
			$result = mysql_query($sql, $connect) or die(mysql_error());
			while ($list = mysql_fetch_assoc($result))
			{     
					$strHTML .= "<div class=pitem><div style='width:100px;float:left;' class=pname>$list[name]</div><div style='' class=pmobile>$list[mobile]</div></div>";
			}   

			echo $strHTML;
    }

    function save_address()
    {
		global $connect;

			$group_name = $_REQUEST[group_name];
			$mobilelist = $_REQUEST[mobilelist];
			$namelist = $_REQUEST[namelist];

			$sql = "select name from tbl_sms_group where name = '$group_name'";
			$list = mysql_fetch_assoc(mysql_query($sql, $connect));
			if (!$list) {
				mysql_query("insert into tbl_sms_group values('$group_name')", $connect) or die(mysql_error());
			}
			
			
			$mobilelist 	= explode(",", $mobilelist);
			$namelist 	= explode(",", $namelist);

			for ($i=0; $i < sizeof($mobilelist); $i++)
			{
				$mobile = trim($mobilelist[$i]);
				$name   = trim(iconv("euc-kr", "utf-8", $namelist[$i]));

				$ins_sql = "insert into tbl_sms_address (
						group_name, 
						mobile, 
						name
					) values (
						'$group_name',
						'$mobile',
						'$name'
					)";
				mysql_query($ins_sql, $connect) or die(mysql_error());
			}
    }

    function get_address_list()
    {
			global $connect;

			$sql = "select * from tbl_sms_group";
			$result = mysql_query($sql, $connect);
			while ($list = mysql_fetch_assoc($result)) {
				$strHTML .= "<a href=\"javascript:get_address_item('$list[name]')\">$list[name]</a><br/>";
			}

			echo $strHTML;
    }

    function get_address_item()
    {
			global $connect;

			$group = $_REQUEST[group];
			$sql = "select * from tbl_sms_address where group_name = '$group'";
			$result = mysql_query($sql, $connect);

			while ($list = mysql_fetch_assoc($result)) {
				$strHTML .= "<div><div class=mobile style='float:left; width:100px'>$list[mobile]</div><div class=name style='float:left; width:100px'>$list[name]</div></div>";
			}

			echo $strHTML;
    }

    function view_sms()
    {
			global $connect;

			$strHTML = "<table cellspacing=5>";

			$sql = "select * from tbl_sms_history order by no desc limit 10";
			$result = mysql_query($sql, $connect);

			while ($list = mysql_fetch_assoc($result))
			{
				$msg = "<a href=\"javascript:fill_msg('$list[msg]')\">" . cutstr_utf8($list[msg], 45) . "&nbsp&nbsp</a>";

				$msg .= "<img class = del_msg src=/images/sms/icon_del_small.gif align=absmiddle>";
				$strHTML .= "<tr><td class=sms><b class='crdate'>$list[crdate]</b> ($list[sent]건)<br>" . $msg . "</td></tr>";
			}

			$strHTML .= "</table>";

			echo $strHTML;
    }

	function delete_msg()
	{
		global $connect, $msg_crdate;

		$query = "delete from tbl_sms_history where crdate = '$msg_crdate'";
		mysql_query( $query, $connect );
	}
	
    function get_select_group()
    {
			global $connect;

			$strHTML = "<select id=group_name name=group_name style='width:150px' onchange='change_group_name()'>";
			$strHTML .= "<option value=''>그룹을 선택하세요</option>";
			$sql = "select * from tbl_sms_group";
			$result = mysql_query($sql, $connect) or die(mysql_error());
			while ($list = mysql_fetch_assoc($result))
			{
				$strHTML .= "<option value='$list[name]'>$list[name]</option>";
			}
			$strHTML .= "<option value='new'>[새그룹에 저장]</option>";
			$strHTML .= "</select>";

			echo $strHTML;
    }
}
?>
