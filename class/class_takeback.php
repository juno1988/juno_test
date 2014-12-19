<?
/*--------------------------------------

name: class_takeback
date: 2008-11-28 

------------------------------------*/
require_once "class_order.php";
require_once "class_transcorp.php";

class class_takeback
{
    // 회수 정보를 가져온다. - seq
    function get_takeback_info_seq($seq)
    {
    	global $connect;
        
	    $query = "select * from order_takeback where order_seq='$seq'";
    	$result = mysql_query( $query, $connect );
    	if( mysql_num_rows( $result ) > 0 )
        {
        	$data = mysql_fetch_array( $result );

    		$val["number"           ] = $data[number];
    		$val["status"           ] = $data[status];
    		$val["invoice"          ] = $data[invoice];
    		$val["address"          ] = iconv('cp949', 'utf-8', $data[address]);
    
    		$val["trans_corp"       ] = $data[trans_corp    ];
    		$val["trans_no"         ] = $data[trans_no      ];

    		$val["receive_date"     ] = $data[receive_date  ];
    		$val["request_date"     ] = $data[request_date  ];
    		$val["regist_date"      ] = $data[regist_date   ];
    		$val["pos_date"         ] = $data[pos_date      ];
    		$val["box_date"         ] = $data[box_date      ];
    		$val["complete_date"    ] = $data[complete_date ];
    
    		$val["who_receive"      ] = iconv('cp949', 'utf-8', $data[who_receive ]);
    		$val["who_request"      ] = iconv('cp949', 'utf-8', $data[who_request ]);
    		$val["who_regist"       ] = iconv('cp949', 'utf-8', $data[who_regist  ]);
    		$val["who_pos"          ] = iconv('cp949', 'utf-8', $data[who_pos     ]);
    		$val["who_box"          ] = iconv('cp949', 'utf-8', $data[who_box     ]);
    		$val["who_complete"     ] = iconv('cp949', 'utf-8', $data[who_complete]);
    
    		$val["trans_who"        ] = iconv('cp949', 'utf-8', $data[trans_who]);
    		$val["trans_get"        ] = iconv('cp949', 'utf-8', $data[trans_get]);
    		$val["refund_req"       ] = $data[refund_req];
    		$val["refund_get"       ] = $data[refund_get];
    		$val["qty_req"          ] = $data[qty_req   ];
    		$val["qty_get"          ] = $data[qty_get   ];
    		$val["bank_req"         ] = $data[bank_req  ];
    		$val["bank_get"         ] = $data[bank_get  ];

    		$val["product_status"   ] = $data[product_status];
    		$val["reason_req"       ] = $data[reason_req    ];
    		$val["reason_get"       ] = $data[reason_get    ];

    		$val["error"            ] = 0;
        }
        else
    		$val["error"            ] = 1;
/*
        $data_pack = class_order::get_order( $seq );
		$val["seq"] 	  = $seq;
        $val['pack']      = $data_pack[pack];
        $val['productid'] = $data_pack[product_id];
*/
    	return $val;
    }	

    // 회수 정보를 가져온다. - number
    function get_takeback_info_number($number)
    {
    	global $connect;
        
	    $query = "select * from order_takeback where number=$number";
    	$result = mysql_query( $query, $connect );
    	if( mysql_num_rows( $result ) > 0 )
        {
        	$data = mysql_fetch_array( $result );

    		$val["number"           ] = $data[number];
    		$val["status"           ] = $data[status];
    		$val["invoice"          ] = $data[invoice];
    		$val["address"          ] = iconv('cp949', 'utf-8', $data[address]);
    
    		$val["receive_date"     ] = $data[receive_date  ];
    		$val["request_date"     ] = $data[request_date  ];
    		$val["regist_date"      ] = $data[regist_date   ];
    		$val["pos_date"         ] = $data[pos_date      ];
    		$val["complete_date"    ] = $data[complete_date ];
    
    		$val["who_receive"      ] = iconv('cp949', 'utf-8', $data[who_receive ]);
    		$val["who_request"      ] = iconv('cp949', 'utf-8', $data[who_request ]);
    		$val["who_regist"       ] = iconv('cp949', 'utf-8', $data[who_regist  ]);
    		$val["who_pos"          ] = iconv('cp949', 'utf-8', $data[who_pos     ]);
    		$val["who_complete"     ] = iconv('cp949', 'utf-8', $data[who_complete]);
    
    		$val["trans_who"        ] = iconv('cp949', 'utf-8', $data[trans_who]);
    		$val["trans_get"        ] = iconv('cp949', 'utf-8', $data[trans_get]);
    		$val["refund_req"       ] = $data[refund_req];
    		$val["refund_get"       ] = $data[refund_get];
    		$val["qty_req"          ] = $data[qty_req   ];
    		$val["qty_get"          ] = $data[qty_get   ];
    		$val["bank_req"         ] = $data[bank_req  ];
    		$val["bank_get"         ] = $data[bank_get  ];

    		$val["product_status"   ] = $data[product_status];
    		$val["reason_req"       ] = $data[reason_req    ];
    		$val["reason_get"       ] = $data[reason_get    ];
    		
    		$val["error"            ] = 0;
        }
        else
    		$val["error"            ] = 1;

    	return $val;
    }	

    // 회수 상태 최대값을 가져온다.
    function get_takeback_status($seq, $pack)
    {
    	global $connect;

        $seqList = class_order::get_seqList_byPack($seq, $pack);
        $query = "select max(status) as max_status from order_takeback where order_seq in $seqList";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        return ($data[max_status]==null)?0:$data[max_status];
    }	

    // 회수 번호 새 값을 구한다.
    function get_new_number()
    {
    	global $connect;
        $query = "select max(number) as max_num from order_takeback";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        if( $data[max_num] == null )
            $num_new = 1;
        else
            $num_new = $data[max_num]+1;
            
        return $num_new;
    }

    // 새 번호로 회수 번호를 변경한다.
    function change_number( $seq, $num_new )
    {
    	global $connect;
        $query = "update order_takeback set number='$num_new' where order_seq=$seq";
        mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경] (" . $num_new . ") 회수번호변경", $seq );
    }
    
    // 회수 접수한다. 
    function set_regist( $pack, 
                         $seq, 
                         $product_id, 
                         $number, 
                         $invoice, 
                         $trans_who,
                         $refund_req, 
                         $bank_req, 
                         $qty, 
                         $return )
    {
        global $connect;
        
    	$query = "insert order_takeback 
    	             set order_seq      = '$seq', 
                         product_id     = '$product_id',
                         number         = '$number', 
                         invoice        = '$invoice', 
                         receive_date   = now(),
                         who_receive    = '$_SESSION[LOGIN_NAME]', 
                         trans_who      = '$trans_who', 
                         refund_req     = '$refund_req', 
                         bank_req       = '$bank_req', 
                         qty_req        = '$qty', 
                         reason_req     = '$return',
                         status         = 1";
    	mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경]  (" . $number . ") 회수접수", $seq );
    }

    // 회수 접수 취소한다.
    function cancel_takeback($seq)
    {
    	global $connect;
    
    	$query = "delete from order_takeback where order_seq=$seq and status=1";
    	mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경]" . " 접수취소", $seq );

    	if ( $_SESSION[USE_3PL] )
    	{
    	    $obj = new class_3pl();
    	    $obj->cancel_takeback( $seq );
    	}
    }	

    // 회수 접수건, 회수번호 전체 도착 확인 update
    function pos_takeback_update($number, $corp, $transno)
    {
    	global $connect;
    
    	$query = "update order_takeback 
    	             set trans_corp = '$corp',
    	                 trans_no   = '$transno',
    	                 pos_date   = now(),
    	                 who_pos    = '$_SESSION[LOGIN_NAME]',
    	                 status     = 4
    	           where number = $number";
    	mysql_query( $query, $connect );
    	
    	$query = "select order_seq from order_takeback where number=$number";
    	$result = mysql_query( $query, $connect );
    	while( $data = mysql_fetch_array( $result ) )
    	{
    	    $trans_info = class_transcorp::get_corp_name($corp) . "] " . $transno;
    	    $this->begin( "[회수정보변경]  (" . $number . ") 도착확인 [" . $trans_info , $data[order_seq] );
    	}
    }	

    // 회수 미접수건, 개별 도착 확인 insert
    function pos_takeback_insert($pack, $seq, $product_id, $number, $corp, $transno)
    {
    	global $connect;
    
    	$query = "insert order_takeback 
    	             set order_seq  = '$seq',
    	                 product_id = '$product_id',
    	                 number     = '$number',
    	                 trans_corp = '$corp',
    	                 trans_no   = '$transno',
    	                 pos_date   = now(),
    	                 who_pos    = '$_SESSION[LOGIN_NAME]',
    	                 status     = 4";
    	mysql_query( $query, $connect );
  	    $trans_info = class_transcorp::get_corp_name($corp) . "] " . $transno;
	    $this->begin( "[회수정보변경]  (" . $number . ") 도착확인 [" . $trans_info , $seq );
    }	

    // 회수 미접수건, 합포 전체 도착 확인 insert
    function pos_takeback_insert_all($pack, $seq, $product_id, $number, $corp, $transno)
    {
    	global $connect;
        
        // pack 값으로 이미 배송된, 합포 주문을 가져온다.
        $query = "select * from orders where pack <> 0 and pack = $pack and status=8";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 이미 회수 진행중인 주문은 건너뛴다.
            $query_tb = "select * from order_takeback where seq=$data[seq] and status>0";
            $result_tb = mysql_query( $query_tb, $connect );
            if( mysql_num_rows( $result_tb ) > 0 )  continue;
            
        	$query = "insert order_takeback 
        	             set order_seq  = '$data[seq]',
        	                 product_id = '$data[product_id]',
        	                 number     = '$number',
        	                 trans_corp = '$corp',
        	                 trans_no   = '$transno',
        	                 pos_date   = now(),
        	                 who_pos    = '$_SESSION[LOGIN_NAME]',
        	                 status     = 4";
        	mysql_query( $query, $connect );
    	    $trans_info = class_transcorp::get_corp_name($corp) . "] " . $transno;
    	    $this->begin( "[회수정보변경]  (" . $number . ") 도착확인 [" . $trans_info , $data[seq] );
        }
    }	

    // 도착 확인 정보를 삭제한다.
    function cancel_tb_pos( $seq, $number, $regist_date, $request_date, $receive_date )
    {
    	global $connect;

        if( $regist_date ){  // 송장 등록 상태로
            $query = "update order_takeback 
                         set trans_corp = null,
                             trans_no   = null,
                             pos_date   = null,
                             who_pos    = null,
                             status     = 3
                       where order_seq = $seq";
        }else if( $request_date ){  // 회수 요청 상태로
            $query = "update order_takeback 
                         set trans_corp = null,
                             trans_no   = null,
                             pos_date   = null,
                             who_pos    = null,
                             status     = 2
                       where order_seq = $seq";
        }else if( $receive_date ){  // 회수 접수 상태로
            $query = "update order_takeback 
                         set trans_corp = null,
                             trans_no   = null,
                             pos_date   = null,
                             who_pos    = null,
                             status     = 1
                       where order_seq = $seq";
        }else{  // 접수 기록이 없으면, 주문 삭제
            $query = "delete from order_takeback where order_seq = $seq";
        }
        mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경]  (" . $number . ") 도착취소", $seq );
    }
    
    // 박스 확인 정보를 저장한다.
    function set_box_info($seq, $number, $num_new, $trans_get, $refund_get, $qty_get, $reason_get, $prd_status)
    {
    	global $connect;
        
        // $num_new가 0이 아니면, $number 수정
        if( $num_new > 0 )  $number = $num_new;
        
        $query = "update order_takeback
                     set number         = '$number',
                         box_date       = now(),
                         who_box        = '$_SESSION[LOGIN_NAME]',
                         trans_get      = '$trans_get',
                         refund_get     = '$refund_get',
                         qty_get        = '$qty_get',
                         reason_get     = '$reason_get',
                         product_status = '$prd_status',
                         status         = 5
                   where number = $number and status=4";
        mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경]  (" . $number . ") 박스개봉", $seq );
    }	

    // 박스 개봉 정보를 삭제한다.
    function cancel_tb_box( $seq, $number )
    {
    	global $connect;
        
        $query = "update order_takeback 
                     set box_date   = null,
                         who_box    = null,
                         trans_get  = null,
                         refund_get = null,
                         qty_get    = null,
                         reason_get = 0,
                         product_status = 0,
                         status     = 4
                   where order_seq = $seq";
        mysql_query( $query, $connect );
    	$this->begin( "[회수정보변경]  (" . $number . ") 개봉취소", $seq );
    }

    // 회수 완료 정보를 저장한다.
    function set_complete($number)
    {
    	global $connect;
        
        $query = "select order_seq from order_takeback where number=$number";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $query = "update order_takeback
                         set complete_date  = now(),
                             who_complete   = '$_SESSION[LOGIN_NAME]',
                             status         = 6
                       where order_seq = $data[order_seq]";
            mysql_query( $query, $connect );
        	$this->begin( "[회수정보변경]  (" . $number . ") 회수완료", $data[order_seq] );
        }
    }	


//###############################################################################################################
// 이 아래는 이전 소스... 일단 냅둠
//###############################################################################################################

  //===================================
  //
  // 회수 상세 정보
  // date: 2007.2.28 - jk.ryu
  //
  function get_detail( $seq )
  {
	global $connect;
	$query = "select * from order_takeback where order_seq='$seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data;
  }

  // 회수 요청 등록
  function regist_takeback( $_seq='', $_trans_no='')
  {
	global $connect, $seq, $trans_who;
	$seq = $_seq ? $_seq : $seq;
	
	$reg_who = $_SESSION["LOGIN_NAME"];

	$query = "select seq from order_takeback where order_seq=$seq";
	$result = mysql_query ( $query, $connect );

	if ( mysql_num_rows( $result ) == 0 )
	{
	    $query = "insert into order_takeback 
                              set order_seq='$seq', trans_who='$trans_who', who_request='$reg_who', reg_date=Now()";
	    mysql_query ( $query, $connect );
	}
	//============================================
	// 주문을 취소 요청으로 만들어야 함
	// date: 2007.2.28 - jk.ryu
	// 취소 되지 않은 건들도 회수 요청 가능 - 4.2 - jk
	//$query = "update orders set order_cs=2 where seq='$seq'";
	//mysql_query ( $query, $connect );

	return "[회수 요청] ";
  }

  // 회수 요청 삭제
  function delete_takeback()
  {
	global $connect, $seq;
	$query = "delete from order_takeback where order_seq='$seq'";	
	mysql_query ( $query, $connect );
	return "[회수 삭제]";
  }

  //========================================
  // 
  // 완료 처리
  //   date: 2007.2.28 - jk.ryu
  // 
  function complete_item( $order_seq )
  {
	global $connect;

	$query  = "select complete_date,complete from order_takeback where order_seq=$order_seq";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );
	$_return[complete_date] = $data[complete_date];

	if ( !$data[complete] )
    	{
	    $query = "update order_takeback 
			    set complete_date=Now(), 
                                who_complete='". $_SESSION[LOGIN_NAME] . "', 
                                status=2 ,
                                complete=1
                            where order_seq='$order_seq' and complete=0";
	    mysql_query ( $query , $connect );
    
            $_return[affected_row] = mysql_affected_rows(); 
    
	    if ( mysql_affected_rows()  )
	    {
	        $_return[error] = 0;
	        $_return[msg]   = "등록완료";
    
	        // 주문의 상태를 배송 후 취소 완료로 변경
		// 취소 완료로 변경하지 않음 - 2008.4.18
	        // $query = "update orders set order_cs = 4 where seq='$order_seq'";
	        mysql_query ( $query , $connect );
	    }
	    else
	    {
	        $_return[error] = 1;
	        $_return[msg]   = "오류";
	    }
	}
	else
	{
	    $_return[error] = 1;
	    $_return[msg]   = "이미 회수 완료 되었습니다.";
	}
	return $_return;
  }

  //-----------------------------------------
  //
  // 송장 접수
  //   date: 2007-02-15 - jk.ryu
  //
  function apply_transno()
  {
	global $connect;

	// takeback_temp에서 data_type = 1 and status=1 인 데이터를 읽어온다.
	$query = "select * from takeback_temp where data_type=2 and status=1";
	$result = mysql_query ( $query, $connect );

	while ( $data = mysql_fetch_array ( $result ) )
	{
	    // 회수 등록 여부 확인 함
	    // class_takeback::regist_takeback( $data[order_seq] );
	    class_takeback::add_transno( $data, $connect );
	}
  }

  //=========================================
  // 
  // 회수 송장 번호 update
  // date: 2008.2.28 - jk.ryu
  //
  function update_transinfo($seq, $trans_no, $trans_corp, $trans_who)
  {
	global $connect;
	$_trans_who[0] = "고객부담";	//
	$_trans_who[1] = "자사부담";

	$query = "update order_takeback set
		trans_corp = '$trans_corp',
		trans_no   = '$trans_no',
		trans_who  = '" . $_trans_who[$trans_who] ."',
		trans_date = Now(),
		status = 1
	where order_seq = '$seq'";

//echo $query;
debug ( $query );

	mysql_query ( $query, $connect );

  }

  // 회수 상태 출력
  function disp_status( $data )
  {
	if( $data[status] >= 0 )
		echo "회수 등록 (등록일: $data[reg_date])";

	if( $data[status] >= 1 )
		echo "<br> 송장 등록 (등록일: $data[trans_date] )";

	if( $data[status] >= 2 )
		echo "<br> 회수 완료 (등록일: $data[complete_date] )";
  }

  function add_transno( $data, $connect )
  {
	global $status;

	// 바로 완료로 처리 할 수도 있음
	if ( $status == 2 )
	{
	    $query = "update order_takeback set
			trans_corp = '$data[trans_corp]',
			trans_no   = '$data[trans_no]',
			trans_date = Now(),
			status     = 2,
			complete   = 1,
			complete_date = Now()	
		where order_seq = '$data[order_seq]'";
	}
	else
	{
	    $query = "update order_takeback set
			trans_corp = '$data[trans_corp]',
			trans_no = '$data[trans_no]',
			trans_date = Now(),
			status = 1
		where order_seq = '$data[order_seq]'";
	}

//echo "<br> $query";
//exit;
	mysql_query ( $query, $connect );	

	// takeback_temp에서 data 삭제
	$query = "delete from takeback_temp where order_seq = '$data[order_seq]'";
	mysql_query ( $query, $connect );	

  }

  //-----------------------------------------
  //
  // 회수 접수
  //   date: 2007-02-14
  //
  function apply_takeback()
  {
	global $connect;
	echo "apply_takeback";

	// takeback_temp에서 data_type = 1 and status=1 인 데이터를 읽어온다.
	$query = "select * from takeback_temp where data_type=1 and status=1";
	$result = mysql_query ( $query, $connect );

	while ( $data = mysql_fetch_array ( $result ) )
	{
		class_takeback::add_takeback ( $data, $connect );
	}
  }

  function add_takeback ( $data, $connect )
  {
	$status = 0;
	// 송장 번호가 있을 경우 상태는 1:송장입력
	if ( $data[trans_no] )
	{
		$status = 1;
	}

	$query = "insert into order_takeback set
			order_seq = '$data[order_seq]',
			trans_who = '$data[trans_who]',
			trans_corp = '$data[trans_corp]',
			trans_no = '$data[trans_no]',
			reg_date = Now(),
			who_request = '" . $_SESSION[LOGIN_NAME] . "',
			status = '$status',
			qty = $data[qty]";

	// 송장 번호가 있는 경우 송장 입력 시간도 있어야 함
	// 2007.2.20 - jk.ryu
	if ( $status == 1 )
		$query .= ",trans_date=Now()";

	mysql_query ( $query, $connect );	

	// takeback_temp에서 data 삭제
	$query = "delete from takeback_temp where order_seq = '$data[order_seq]'";
	mysql_query ( $query, $connect );	

	// 주문 정보를 배송 후 취소로 만들어야 함
	$query = "update orders set order_cs=2 where seq='$data[order_seq]'";
	mysql_query ( $query, $connect );	
  }

  //-----------------------------------------
  //
  // takeback_temp의 개수
  // data_type : 1: 회수 데이터, 2: 송장 데이터
  // 
  function get_count2 ( $parameter )
  {
	global $connect;
	
	$query = "select count(*) as cnt from takeback_temp where ";

	switch ( $parameter )
	{
		case "normal":	// 정상 케이스
			$query .= " data_type=1 and status=1";
			break;
		case "error":
			$query .= " data_type=1 and status=0";
			break;
		case "trans_normal":
			$query .= " data_type = 2 and status=1";
			break;
		case "trans_error":
			$query .= " data_type = 2 and status=0";
			break;
	}
//echo $query;
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[cnt];
  }

  // 개수
  function get_count( $par='' , $s_date, $e_date)
  {
	global $connect, $download_type, $start_date, $end_date;

	$download_type = $par ? $par : $download_type;
	$start_date = $s_date ? $s_date : $start_date;
	$end_date = $e_date ? $e_date : $end_date;

	$query = "select count(*) as cnt from order_takeback where  " ;

	switch ( $download_type )
	{
		case "req_takeback" :	// 미처리 회수
			$query .= " reg_date >= '$start_date' and reg_date <= '$end_date 23:59:59' and status=0 and complete = 0";
			break;
		case "transno_takeback" : // 송장입력 회수
			$query .= " reg_date >= '$start_date' and reg_date <= '$end_date 23:59:59' and status=1 and complete = 0 and trans_no is not null";
			break;
		case "comp_takeback" :	// 회수 완료
			$query .= " complete_date >= '$start_date' and complete_date <= '$end_date 23:59:59' and status=2 and complete = 1";
			break;
	}
// echo $query;
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[cnt];
  }


  //=======================================
  //
  // download_type	
  // req_takeback: 미처리 회수 요청
  // trans_no: 송장 입력 회수 요청
  //
  function get_list( $download_type )
  {
	global $connect, $start_date, $end_date ;
	switch ( $download_type )
	{
		case "req_error":
			$query = "select * from takeback_temp where  data_type=1 and status=0";
			break;
		case "req_takeback":
			$query = "select a.* from orders a, order_takeback b
					where a.seq = b.order_seq
					and b.status = 0
					and b.reg_date>= '$start_date 00:00:00'
					and b.reg_date<= '$end_date 23:59:59'";
			break;
		case "trans_no":
			$query = "select a.*,b.trans_corp tb_trans_corp, b.trans_no tb_trans_no, b.trans_who tb_trans_who
					from orders a, order_takeback b
					where a.seq = b.order_seq
					and b.status = 1
					and b.trans_no <> ''
					and b.complete_date>= '$start_date 00:00:00'
					and b.complete_date<= '$end_date 23:59:59'";
			break;
		case "comp_takeback":	// 회수 완료
			$query = "select a.*,b.trans_corp tb_trans_corp, b.trans_no tb_trans_no, b.trans_who tb_trans_who
					from orders a, order_takeback b
					where a.seq = b.order_seq
					and b.status = 2 
					and b.complete = 1
					and b.complete_date>= '$start_date 00:00:00'
					and b.complete_date<= '$end_date 23:59:59'";
			break;
	}

debug ( $query );
// echo $query;

	$result = mysql_query ( $query, $connect );
	return $result;
  }

  // =======================================
  //
  // 송장 등록 가능한 주문인지 여부 check
  // date: 2007.2.13 - jk.ryu
  // status: 0: 오류, 1: 배송전 취소처리, 2: 회수 처리
  //	$order_seq가 order_takeback에 없는 경우 오류 처리
  function transno_validate ( $order_seq , &$status, &$return_message )
  {
	global $connect;
	$query = "select order_seq from order_takeback where order_seq='$order_seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	if ( $data[order_seq] )
	{
	    $status = 1;		// 정상
	    $return_message = "[정상] 정상 등록 되었습니다.";
	}
	else
	{
	    $status = 0;
	    $return_message = "[오류] 미등록 송장";

	    // 오류 발생시 회수 요청 등록 함
	    // 2008.5.2 - jk
	    $query = "select seq from orders where seq=$order_seq";

	    $result = mysql_query ( $query, $connect );
	    $data = mysql_fetch_array ( $result );

	    if ( $data[seq] ) 
	    {
	        class_takeback::regist_takeback( $order_seq ); 
		$status = 1;
	    }
		

	}
  }

  // =======================================
  //
  // 회수 가능한 주문인지 여부 check
  // date: 2007.2.13 - jk.ryu
  // status: 0: 오류, 1: 배송전 취소처리, 2: 회수 처리
  //
  function validate( $order_id, $product_id, &$status, &$return_message, &$order_seq )
  {
  	global $connect;
  	$query = "select status, order_cs,seq from orders where order_id='$order_id' and shop_product_id='$product_id'";

//echo $query;
//echo "<br>";

  	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	$order_seq = $data[seq];

	//---------------------------------
	// 상태는 기본 오류 상태
	// 배송 전일 경우에만 회수 가능
	//
	$status = 0;		// default 오류
	$return_message = "";

	// 배송 전 처리
	if ( $data[status] == 1 
	  or $data[status] == 2 
	  or $data[status] == 11 
	  or $data[status] == 7 
        )
	{
		if ( $data[order_cs] == 0 )	
		{
			$status = 1;
			$return_message = "[정상] 배송 전 취소 처리";
		}
		else
		{
			$status = 0;
			$return_message = "[오류] 이미 취소/교환 처리된 주문!";
		}
		
	}
	// 배송 후 회수 처리
	else if ( $data[status] == 8 )
	{
		if ( $data[order_cs] == 0 )	
		{
			$status = 2;
			$return_message = "[정상] 회수 처리";
		}
		else
			$return_message = "[오류] 이미 취소/교환 처리된 주문!!!";
	}
	else
	{
		$status = 0;
		$return_message = "[오류] 조회가 안됩니다";
	}

	// 배송 후 회수 처리의 경우 이미 등록 되어 있는지 여부를 체크 해야 함
	// date: 2007.2.20 - jk.ryu
	if ( $status == 2 )
	{
		$query = "select * from order_takeback where order_seq='$order_seq'";
		$result = mysql_query ( $query, $connect );
		$data = mysql_fetch_array ( $result );

		if ( $data[order_seq] )
		{
			$status = 0; // error처리
			$return_message = "[오류] 이미 회수 등록된 주문!!!";
		}
	}

  }

}

?>
