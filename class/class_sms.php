<?
require_once "class_cs.php";
require_once "xmlrpc.inc";
require_once "xmlrpcs.inc";
require_once "xmlrpc_wrappers.inc";

require_once "lib/sms_lib.php";
//==================================================
//
// date: 2007.11.7 - jk
//
class class_sms
{
    //-------------------------------------                   
    // 한글로 자르기 (return arr[])                           
    function substr_kor($msg, $len)
    {
        $msg = iconv('utf-8','cp949',$msg);
        if (strlen($msg) > $len) {
            $submsg = substr($msg,0,$len);
            preg_match('/^([\x00-\x7e]|.{2})*/', $submsg, $z);
    
            $arr[] = iconv('cp949','utf-8',$z[0]);
            $arr[] = iconv('cp949','utf-8',str_replace($z[0], "", $msg));
    
            return $arr;
        } else {
            $arr[] = iconv('cp949','utf-8',$msg);
            return $arr;
        }
    }    

    //====================================
    // date: 2008.4.29 - jk
    function get_macro()
    {
         global $connect;
        $val = array();

        $val[error] = 0; // error가 없음
        //$val[msg]   = iconv("cp949", "utf-8", "정상 전송!");
        $val[msg]   = "정상 전송!";

        if ( _DOMAIN_ == "soramam" || _DOMAIN_ == "ccstars")
            $query = "select * From sms_config where type='macro' order by title";
        else
            $query = "select * From sms_config where type='macro'";
            
        $result = mysql_query ( $query, $connect );

echo "<table>";
        while ( $data = mysql_fetch_array ( $result ) )
        {
        echo " <tr> <td><a href=javascript:sms_load($data[seq])>";
        //echo iconv('utf-8', 'cp949' , $data[title] );
        echo $data[title];
        echo "</a></td> </tr> ";        
        }
echo "<table>";

    }

    /////////////////////////////////////////
    // 특정 매크로 값을 load
    // date: 2008.5.19 - jk
    function load()
    {
        global $connect, $seq;

        $query = "select * from sms_config where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );

        $val = array();
        //$val[title] = iconv('cp949', 'utf-8', $data[title]);
        //$val[macro] = iconv('cp949', 'utf-8', $data[value]);
        $val[title] = $data[title];
        $val[macro] = $data[value];
        $val[query] = $query;

        echo json_encode( $val );
    } 

    //////////////////////////////////////////
    // sms_config에 macro type으로 값 저장
    // date: 2008.5.20 - jk
    function macro_delete()
    {
        global $connect, $name, $text, $seq;

        //$name = iconv ( 'utf-8', 'cp949', $name );
        //$text = addslashes(iconv ( 'utf-8', 'cp949', $text ));
        $text = addslashes ( $text );

        $query = "delete from sms_config where seq=$seq";
        mysql_query ($query , $connect );

        $val = array();
        $val[query] = $query;
        echo json_encode( $val );
    }

    //////////////////////////////////////////
    // sms_config에 macro type으로 값 저장
    // date: 2008.5.20 - jk
    function macro_modify()
    {
        global $connect, $name, $text, $seq;

        //$name = iconv ( 'utf-8', 'cp949', $name );
        //$text = addslashes(iconv ( 'utf-8', 'cp949', $text ));
        $text = addslashes ( $text );

        $query = "update sms_config set type='macro', title='$name', value='$text' where seq=$seq";
        mysql_query ($query , $connect );

        $val = array();
        $val[query] = $query;
        echo json_encode( $val );
    }

    //////////////////////////////////////////
    // sms_config에 macro type으로 값 저장
    // date: 2008.5.20 - jk
    function macro_save()
    {
        global $connect, $name, $text;

        //$name = iconv ( 'utf-8', 'cp949', $name );
        //$text = addslashes(iconv ( 'utf-8', 'cp949', $text ));
        $text = addslashes( $text );

        $query = "insert into sms_config set type='macro', title='$name', value='$text'";
        mysql_query ($query , $connect );

        $val = array();
        $val[query] = $query;
        echo json_encode( $val );
    }

	//-- for khjang
	function send_ums($msg, $sender, $receiver, $seq="")
    {

debug("msg=$msg sender=$sender receiver=$receiver seqs=$seq");

		$receivers = explode(",", $receiver);
		foreach ($receivers as $one) {
			class_sms::send_ums_one($msg, $sender, $one, $seq);
		}
    }


	function send_ums_one($msg, $sender, $receiver, $seq="")
    {
        // sener, receiver 필터링
        $pattern     = "/[\'|\-]/";
        $replacement = "";
        $sender      = preg_replace($pattern, $replacement, $sender);
        $receiver    = preg_replace($pattern, $replacement, $receiver);

        $sys_connect = sys_db_connect();
        $val = array();
        $val[error] = 1; // error가 없음
        $val[msg]     = "문자 충전 요망!";
        $val[result]  = "fail";
        
        // sms 개수 체크
        $query  = "select (sms+paid_sms) as sms from sys_domain where id='"._DOMAIN_."'"; 
debug($query);
        $result = mysql_query ( $query, $sys_connect );
        $data   = mysql_fetch_array ( $result );
        $val['sms'] = $data[sms];

		// iconv bug fix
    	$msg = str_replace("잌", "익", $msg);
    	$msg = str_replace("샾", "샵", $msg);
    	$msg = str_replace("랲", "랩", $msg);
        
        if ( $data[sms] > 0 )
        {   
			$uuid = date("His") . "_" . $seq;

			$len = strlen(stripslashes(iconv('utf-8', 'euckr', $msg)));

			if ($len > 90) {

				//-- ONLY SMS --
				if (substr($receiver, 0, 3) == "050") {

				    sms_send_slice($receiver, $sender, $msg, $uuid);
				    debug("sms_send_slice($receiver:$sender): $msg");

				} else if (_DOMAIN_ == "flora2") {

					// flora2는 무조건 SMS
				    sms_send_slice($receiver, $sender, $msg, $uuid);
				    debug("sms_send_slice for flora2($receiver:$sender): $msg");

				} else {
					// 새로운 uds 모듈로 전송
					mms_send_uds($receiver, $sender, $msg, $uuid);
					debug("mms_send_uds($receiver:$sender): $msg");

				}

				$minus = 2;

			} else {

				// 새로운 uds 모듈로 전송
				sms_send_uds($receiver, $sender, $msg, $uuid);
				debug("sms_send_uds($receiver:$sender): $msg");

				$minus = 1;
			}

			$val['result'] = $_dummy;
		    $val[error] = 0; // No error
			$val[msg]   = "정상 전송!";
                    
            // 2. upate sys_domain
			$upd_sql = "update sys_domain set  
							   paid_sms = if (sms <= 0, paid_sms-${minus}, paid_sms)
							 , sms      = if (sms > 0, sms-${minus}, sms) 
						 where id = '"._DOMAIN_."'";
			debug($upd_sql);
			mysql_select_db("ezadmin", $sys_connect);
			@mysql_query($upd_sql, $sys_connect);

			//-- save to sms_msg_history
			// $this->save_msg_history($receiver, $sender, $msg, $seq);
        }
        else
        {
            // echo "no sms";   
        }
        
        // echo json_encode( $val );    
    }

    // db연결하는 신 모듈
    function send()
    {
        global $msg, $sender, $receiver, $domain, $user, $seq;
        global $connect;
        // sener, receiver 필터링
		$msg = trim($msg);

        $pattern     = "/[\'|\-]/";
        $replacement = "";
        $sender      = preg_replace($pattern, $replacement, $sender);
        $receiver    = preg_replace($pattern, $replacement, $receiver);

        $sys_connect = sys_db_connect();
        $val = array();
        $val[error] = 1; // error가 없음
        $val[msg]     = "문자충전을 하셔야 전송가능합니다!";
        $val[result]  = "fail";
        
        // sms 개수 체크
        $query  = "select (sms+paid_sms) as sms from sys_domain where id='"._DOMAIN_."'"; 
        $result = mysql_query ( $query, $sys_connect );
        $data   = mysql_fetch_array ( $result );
        $val['sms'] = $data[sms];

        //echo "smsS:" . $val['sms'];
        debug( "smsS:" . $val['sms'] );

		// iconv bug fix
    	$msg = str_replace("잌", "익", $msg);
    	$msg = str_replace("샾", "샵", $msg);
    	$msg = str_replace("랲", "랩", $msg);
        
        if($seq)
    	{
    		//최웅
    		
	    	$_query = "SELECT seq, pack, order_id, trans_no, recv_name, order_name, shop_id, product_name, options FROM orders WHERE seq=$seq";
	    	$_result = mysql_query ($_query , $connect );
	    	$_data = mysql_fetch_assoc($_result);
	    	
	    	$shop_id = $_data[shop_id];
			$_query = "select shop_name from shopinfo where shop_id='$shop_id'";
			$r = mysql_query ( $_query, $connect );
			$d = mysql_fetch_array ( $r );	
	    	
	    	$_arr         = $this->substr_kor( $_data[product_name], 14 );
	    	$product_name = $_arr[0] . "..";
	    	
	    	//alice의 경우 합포된 주문의 상품명 전체를 출력
	        if(( _DOMAIN_ == "ezadmin"  || _DOMAIN_ == "alice"  ||  _DOMAIN_ == "polotown") )
	        {
	        	if( $_data[pack] > 0)
	        		$__query = "select c.name, c.options  from orders a, order_products b , products c where a.seq = b.order_seq and b.product_id = c.product_id and a.pack = '$_data[pack]'";
	        	else 
	        	 	$__query = "select c.name, c.options  from orders a, order_products b , products c where a.seq = b.order_seq and b.product_id = c.product_id and b.order_seq = '$_data[seq]'";
	
	        	$__result = mysql_query ($__query, $connect );
	        	$_arr[0] = "";
		        while ( $__data = mysql_fetch_assoc( $__result ) )
		            $_arr[0] .= $__data[name].$__data[options].", ";
		        $_arr[0] = substr($_arr[0], 0, -2);
		        $product_name = $_arr[0] . "..";
	        }
	    	
	    	
	    	$_arr         = $this->substr_kor( $d[shop_name], 14 );
	    	$shop_name = $_arr[0];
	    	
	    	$option_name = $_data[options];
	    	
	    	//옵션 rigth 함수 -20 byte ..
	    	//substr($_data[options], (strlen($_data[options]) - 20), strlen($_data[options])); 
	    	
	    	$msg = preg_replace("/\[판매처\]/"	,   $shop_name			,	$msg );
			$msg = preg_replace("/\[수령자\]/"	,   $_data[recv_name]	,	$msg );
	        $msg = preg_replace("/\[상품명\]/"	,   $product_name		,	$msg );
	        $msg = preg_replace("/\[옵션\]/"	,   $option_name		,	$msg );
			$msg = preg_replace("/\[주문자\]/"	,   $_data[order_name]	,	$msg );
			$msg = preg_replace("/\[주문번호\]/", 	$_data[order_id]  	, 	$msg );
			$msg = preg_replace("/\[송장번호\]/", 	$_data[trans_no]	,	$msg );
	    }

        if ( $data[sms] > 0 )
        {   
			$uuid = date("His") . "_" . $seq;

			$len = strlen(stripslashes(iconv('utf-8', 'euckr', $msg)));

			// MMS
			if ($len > 90) {

				//-- ONLY SMS --
				if (substr($receiver, 0, 3) == "050") {

				    sms_send_slice($receiver, $sender, $msg, $uuid);
				    debug("sms_send_slice($receiver:$sender): $msg");

				} else if (_DOMAIN_ == "flora2") {

					// flora2는 무조건 SMS
				    sms_send_slice($receiver, $sender, $msg, $uuid);
				    debug("sms_send_slice for flora2($receiver:$sender): $msg");
					
				} else {

					// mms는 새로운 uds 모듈로 전송
					mms_send_uds($receiver, $sender, $msg, $uuid);
					debug("mms_send_uds($receiver:$sender): $msg");
				}

				$minus = 2;

			// SMS
			} else {

				if (1) {
					//-- SMS --
					sms_send_uds($receiver, $sender, $msg, $uuid);
					debug("sms_send_uds($receiver:$sender): $msg");
				}

				$minus = 1;
			}

			$val['result'] = $_dummy;
		    $val[error] = 0; // No error
			$val[msg]   = "정상 전송!";
                    
            // 2. upate sys_domain
			$upd_sql = "update sys_domain set  
							   paid_sms = if (sms <= 0, paid_sms-${minus}, paid_sms)
							 , sms      = if (sms > 0, sms-${minus}, sms) 
						 where id = '"._DOMAIN_."'";
			debug($upd_sql);

			mysql_select_db("ezadmin", $sys_connect);
			@mysql_query($upd_sql, $sys_connect);
            
                    
			// 4. csinfo에 내용 저장
			if ( $seq )
			{
                global $connect;
                $sms_sql = "insert csinfo 
                           set order_seq  = '$seq',
                               pack       = '$seq',
                               input_date = now(),
                               input_time = now(),
                               writer     = '$_SESSION[LOGIN_NAME]',
                               cs_type    = '31',
                               cs_reason  = '$uuid',
                               cs_result  = '0',
                               content    = '[SMS전송] $msg'";
                mysql_query ( $sms_sql, $connect );

				$this->modify_sender( $sender );

			}
			//-- save to sms_msg_history
			$this->save_msg_history($receiver, $sender, $msg, $seq);
        }
        
        // sms개수 재 계산을 해서 보여줘야 함 
        $query  = "select (sms+paid_sms) as sms from sys_domain where id='"._DOMAIN_."'"; 
        
        debug( $query );
        $result = mysql_query ( $query, $sys_connect );
        $data   = mysql_fetch_array ( $result );
        $val['sms'] = $data[sms];
        
        debug( "sms: $data[sms]" );
        
        
debug_array($val);	
        
        echo json_encode( $val );    
    }


	//--------------------------------------------
	// 2013.10.2 syhwang
	function save_msg_history($receiver, $sender, $msg, $key_string="")
	{
		global $connect;

		//--------------------------------------------
		//-- save to sms_msg_history 2013.10.2
		$ins_sql = "insert into sms_msg_history (
						  recv_mobile
						, crdate
						, send_mobile
						, send_time
						, msg_type
						, msg
						, key_string
						, is_done
					) values (
						  '$receiver'
						, now()
						, '$sender'
						, now()
						, 'TX'
						, '$msg'
						, '$key_string'
						, 1
					)";
		debug($ins_sql);
		@mysql_query($ins_sql, $connect);
	}

  //--------------------------------------------
  function modify_sender( $sender )
  {
        global $connect;
        
        $query = "select * from sms_config where type='sender'";
        $result = mysql_query( $query, $connect );
        //$row    = mysql_num_rows( $result );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data )
        {
            $query = "update sms_config set value='$sender' where type='sender'";
            mysql_query ( $query, $connect );
        }
        else
        {
            $query = "insert into sms_config set value='$sender',type='sender'";
            $result = mysql_query ( $query, $connect );
        }
  }

  //=====================================
  //
  // 전송 transaction에 입력
  //
  function insert_transaction( $msg )
  {
        global $connect;
        $query = "insert into tbl_sms_transaction ";
  }

  //=====================================
  //
  // get_sender
  // date: 2008.4.30 - jk
  function get_sender()
  {
        global $connect;
        $query = "select * from sms_config where type='sender'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        echo $data[value];
  }
  
  //--------------------------------------
  // 2013.10.10 syhwang
  // get tx/rx history
  // move to class_ER00.php
/*
  function get_history()
  {
        global $connect, $msg, $receiver, $seq, $mode;
        
		$strHTML = "<div id='sms_history'><table class='sms'>";
		$receiver = str_replace("-", "", $receiver);

		$sql = "select * from sms_msg_history where recv_mobile = '$receiver' order by seq limit 200";
		$result = mysql_query($sql, $connect) or die(mysql_error());
		while ($list = mysql_fetch_assoc($result))
		{
			$pos = ($list[msg_type] == 'TX') ? "right" : "left";

			if ($list[status] == 0) $status = "전송중";
			else if ($list[status] == 2) $status = "전송완료";
			else if ($list[status] == -2) $status = "전송오류";

			//-- unread mark
			if ($list[msg_type] == "RX" && $list[status] == 0) 
				$unread = "unread";
			else 
				$unread = "";
			
			$today = date("Y-m-d");
			$day = ($today == substr($list[crdate],0,10)) ? "오늘" : str_replace("-", "/", substr($list[crdate],5,5));

			$crdate = $day . " " . substr($list[crdate],10,6) . "&nbsp;&nbsp;";

			if ($mode == "chat" && $list[msg_type] == "RX" && $list[write_cs] == 0) {
				$write_cs = "<a href=\"javascript:write_cs('$list[recv_mobile]', '$list[seq]')\"><img src='/images2/btn_add_cs.gif' title='C/S에 내용 추가' align=absmiddle></a> <a href=\"javascript:del_cs('$list[recv_mobile]', '$list[seq]')\"><img src='/images2/btn_del_cs.gif' title='삭제' align=absmiddle></a>";
			} else {
				$write_cs = "";
			}


			$list[msg] = nl2br($list[msg]);

			$strHTML .= "<tr>";
			$strHTML .= "<td align=${pos}><span class='title'>$crdate</span><span id='cs_$list[seq]'>$write_cs</span></td>";
			$strHTML .= "</tr>";
			$strHTML .= "<tr>";
			$strHTML .= "<td align=${pos}><div class='box ${pos} ${unread}'>$list[msg]</div></td>";
			$strHTML .= "</tr>";
		}
		$strHTML .= "</table>";
		$strHTML .= "</div>";

		$strHTML .= "<script>$('#sms_history').scrollTop($('#sms_history')[0].scrollHeight);</script>";
		echo $strHTML;
  }

  function set_read_all()
  {
	global $connect, $receiver;

	$upd_sql = "update sms_msg_history set status = 2 
				 where recv_mobile = '$receiver'
				   and msg_type = 'RX'
			  	   and status = 0";
    @mysql_query($upd_sql, $connect);
  }
*/

}
?>
