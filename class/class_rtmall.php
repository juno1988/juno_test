<?
//========================================
//
// ezadmin에서 rtmall을 사용하기 위해 
// date: 2007.11.9 - jk.ryu
// unit test: unit_test/test_rtmall.php
//
require_once "class_db.php";
require_once "class_order.php";
require_once "class_supply.php";
require_once "class_product.php";

class class_rtmall{
    var $m_connect = "";

    // rtmall 객체를 생성하면 바로 rtmall서버에 connect함
    function class_rtmall()
    {
    	if ( $_SESSION )
	{
		//$_server = "121.156.52.85";
		//$_name   = "rt";
		//$_pass   = "skatjdqlfeld";
		//$this->connect( $_server, $_name, $_pass );
	}
    }

    function connect( $host, $name, $pass )
    {
	$obj = new class_db();

	$this->m_connect = $obj->connect( $host, $name, $pass );
	return $this->m_connect;
    }

    // 단순 정보 가져옴
    function get_info( $seq )
    {
	$query = "select * 
                    from rtmall_orders 
                   where seq    = '$seq' 
                     and domain = '" . _DOMAIN_ . "'";

        $result = mysql_query ( $query, $this->m_connect );
	$data   = mysql_fetch_array ( $result );
	return $data;
    }

    //===================================================
    // set normal: 개별 주문 정상 처리
    // 2007.11.20 - jk
    // unit test: 
    function set_normal( $seq )
    {
    	// 등록된 rtmall 주문인지 여부 확인
	$this->is_rtmall_order( $seq );    	

debug ( "3PL ($seq) 개별 정상처리");

	//=========================================
	// normal로 변경하는 것이 가능한지 check 
	if ( $this->enable_normal( $seq ) )
	{
	    // status가 8인 케이스는 변경하지 않는다.
	    $query = "select qty 
                        from rtmall_orders 
	    	       where domain='" . _DOMAIN_ . "' 
		         and seq='$seq' 
                         and status <> 8";
   
	    $result = mysql_query ( $query, $this->m_connect );
    
	    // 전체 취소 요청
	    $_qty = 0;
	    $_child = 0;
	    while ( $data = mysql_fetch_array ( $result ) )
	    {
	        $_qty = $_qty + $data[qty];
	        $infos[order_cs] = -1;
	        // $this->sync_infos( $infos, $seq, $data[seq_subid] );	
	        $this->sync_infos( $infos, $seq );	
	        $_child++;
	    }
    
	    if ( $_child >  1 )
	        $infos[qty] = $_qty;

	    // 원 주문을 정상 처리
	    $infos[order_cs] = "0";
	    $this->sync_infos( $infos, $seq, 1 );	
	}
    }

    //=========================================
    //
    // 언제 normal로 될 수 없는지 어떻게 알 수 있나?
    // 새로운 rule를 생성 함 -> normal화가 실행되면 order_cs에 -1이 입력됨
    // date: 2007.11.20 -> 취소와 동일함
    //
    function enable_normal( $seq )
    {
	$query = "select order_cs 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ ."' 
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );

	$_result = 1;
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    if ( $data[order_cs] == -1 )
	    {
		$_result = 0;
		break;
	    }
	}
	return $_result;
    }

    //===================================================
    // set normal_all: 전체 주문 정상 처리
    // 2007.11.20 - jk
    // unit test: 
    function set_normal_all ( $pack )
    {
    	echo "set normal all";

	$query = "select seq,status from rtmall_orders 
	           where domain='" . _DOMAIN_ . "'
		     and pack='$pack'";

debug ( "set normal all: $query ");

	$result = mysql_query ( $query, $this->m_connect );

	while ( $data = mysql_fetch_array ( $result ) )
            if ( $data[status] != 8 )
	        $this->set_normal( $data[seq] );
	    else
                debug ( "set normal fail: $data[seq] / $data[status] / $data[trans_no] / $data[trans_date_pos]");
    }
   
    //====================================================
    // 교환 작업 수행
    // 2007.11.17 - jk
    // seq: 원 주문
    // new_product_id: 변경된 주문 번호
    // new_qty: 변경된 개수
    // new_seq: 신규 주문 
    function change_product( $seq, $org_qty, $new_product_id ,$new_qty, $new_seq=0 )
    {
debug ( "3PL ($seq) 교환 처리");
	//=====================================================
        // 교환 작업 전 진행 작업
        // date: 2007.11.19 - jk
	$this->change_precheck( $new_seq, $seq );

	//==================================================
	// 이미 배송된 주문인지 check
	// 배송: 1 미배송: 0
	// 배송 후는 교환이 발생해도 원 주문의 배송 개수는 변경이 없음
	if ( $this->check_trans( $seq ) )
	{
	    $infos[order_cs]     = 6;      // 배송 후 교환 요청
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
	    // 미배송일 경우만 실행..
	    //===================================================
	    // 교환 로직 새로 design
	    // 2007.11.19 - jk
	    // 
	    $_rtmall_order   = $this->is_rtmall_order( $seq );
	    $_rtmall_product = $this->is_rtmall_product( $new_product_id );

debug ( " seq: $seq, new_product_id: $new_product_id " );   
 
	    //===========================================
	    // 어떤 function을 사용할 것인지 선택  
	    $_func = $this->_change_selector ( $_rtmall_order, $_rtmall_product );
    
            echo " rtmall_order: $_rtmall_order / $_rtmall_product \n";
            echo "function [change_product]: $_func \n";
  	    //============================================
	    // return된 결과를 실행 
            //
	    // $_func[1][1] = "change_pl2pl";      rtmall 상품을 rtmall 상품으로 교환
	    // $_func[1][0] = "change_pl2self";    rtmall 상품을 자체 상품으로 교환 - 취소 함
	    // $_func[0][1] = "change_self2pl";    자체 상품을 rtmall 상품으로 교환 - 신 주문 생성
	    // $_func[0][0] = "change_self2self";  자체 상품을 자체 상품으로 교환 - 아무것도 안 함
	    $this->${_func}( $seq, $org_qty, $new_product_id, $new_qty );
	
	}
    }

    //========================================
    //
    // 배송 여부 check 2007.11.19 - jk
    function check_trans( $seq )
    {
    	global $connect;
	$query = "select status from orders where seq='$seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	if ( $data[status] == 8 ) // 배송
	    return 1;
	else
	    return 0;
    }

    // is_rtmall_order에서 자동으로 등록 되는데~~
    function change_self2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
	if ( !$this->is_rtmall_reg( $seq ) )
	    $this->order_reg( $seq );
    }

    //==============
    function change_self2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
	//작업 해야 하나?
    }

    //================================
    // rtmall 상품을 rtmall로 변경
    // 2007.11.19 - jk
    // unit test: _rtmall_test.php 11.19 - jk
    function change_pl2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
debug ( "rtmall상품을 rtmall상품으로 변경");
	if ( $org_qty == $new_qty )
	{
	    $data = class_product::get_product_infos( $new_product_id );
            $infos[product_id]   = $new_product_id;
            $infos[product_name] = $data[name];
            $infos[options]      = $data[option];
	    $infos[order_cs]     = 5;      // 배송 전 교환 요청
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
    	    //========================================
    	    // 1. 기존 주문의 정보 변경
    	    // 2. 기존 주문으로 신규 주문 생성 후 취소 요청
	    // 기존 주문의 status
    	    $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
	}
    }

    //================================
    // rtmall 상품을 자사 상품으로 변경
    // 2007.11.19 - jk
    // unit test: _rtmall_test.php 11.19 - jk
    function change_pl2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
debug ( "배송 전 변경");

	if ( $org_qty == $new_qty )
	{
	    $infos[order_cs]     = 1;      // 배송 전 취소 요청
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
    	    //========================================
    	    // 1. 기존 주문의 정보 변경
    	    // 2. 기존 주문으로 신규 주문 생성 후 취소 요청
	    // 기존 주문의 status
    	    $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
	}
    }

    //========================================
    // 1. 기존 주문의 정보 변경
    // 2. 기존 주문으로 신규 주문 생성 후 취소 요청
    function part_cancel( $seq, $org_qty, $new_product_id, $new_qty )
    {
        $data = class_product::get_product_infos( $new_product_id );
        $infos[pack] = $seq;

        // 1. 기존 주문의 정보 변경
        $infos[qty] = $org_qty - $new_qty;
        $this->sync_infos( $infos, $seq );	
    
        // 2. 기존 주문으로 신규 주문 생성 후 취소 요청
        $new_info = $this->copy_order ( $seq );

        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];
        $infos[qty]          = $new_qty;
        $infos[order_cs]     = 1;

	// seq_subid 사라짐 2007.12.31 - jk
        //$this->sync_infos( $infos, $new_info[seq], $new_info[seq_subid] );	
        $this->sync_infos( $infos, $new_info[seq] );	
    }

    //====================================
    // function 선택..
    // 2007.11.19 - jk
    //
    function _change_selector ( $_rtmall_order, $_rtmall_product )
    {
	$_func[1][1] = "change_pl2pl";
	$_func[1][0] = "change_pl2self";
	$_func[0][1] = "change_self2pl";
	$_func[0][0] = "change_self2self";

	return $_func[$_rtmall_order][$_rtmall_product];
    }

    //=============================================
    // 교환 작업 전 수행 작업
    function change_precheck( $new_seq, $org_seq )
    {
	//=====================================================
	// new_seq가 있는 경우 새로운 주문을 가져온다
	if ( $new_seq )
	{
	    // rtmall 상품 인지 여부 check 
	    if ( $this->is_rtmall_use ( $new_seq ) )
	    {
	        $data = class_order::get_order( $new_seq );
		$data[pack] = $org_seq;
	        $this->order_reg( $data );
	    }
	}
    }

    //============================
    // 자체 배송 상품으로 변경f
    // 2007.11.17 - jk
    function change_self_product( $data, $qty, $new_product_id )
    {
	echo "change self product";
    }

    //========================================================
    // rtmall 상품에서 rtmall 상품으로 변경
    // change rtmall product to rtmall product - 2007.11.17 - jk
    function change_rtmall_product( $data, $qty, $new_product_id )
    {
    	if ( $data[qty] < $qty )
	{
	    echo "수량 오류";
	    exit;
	}

	if ( $data[status] == 8 )
	    $this->_change_rtmall_product_after( $data[seq] ); // 배송 후인 경우
	else
	    $this->_change_rtmall_product_before( $data[seq], $data[qty], $new_product_id, $qty ); // 배송 전인 경우
    }

    //==========================================
    // 주문을 복사
    // 2007.11.17 - jk
    function copy_order( $seq )
    {
	$query  = "select * 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ . "' 
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data   = mysql_fetch_array ( $result );
	$fields = mysql_num_fields( $result );
	$data[seq_subid] = $this->get_max_subid( $seq );

	$out="";
	for ( $i = 0; $i < $fields; $i++ ) {
	    $fname = mysql_field_name( $result, $i );
	    if ( $fname == "seq_subid" )
	        $arr_datas[$fname] = $data[$fname] + 1;
	    else
	        $arr_datas[$fname] = $data[$fname];
	}

	// 복사 실행
	// arr[seq] / arr[seq_subid] return
	return $this->insert_order( $arr_datas );
    }

    function get_max_subid( $seq )
    {
	// max sub id 
	$query = "select max(seq_subid) seq_subid 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ . "' 
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data   = mysql_fetch_array ( $result );
	return $data[seq_subid];
    }

    //=================================
    // 주문 입력
    // 2007.11.17 - jk
    function insert_order( $arr_datas )
    {
	$query = "insert into rtmall_orders set ";
	
	$i = 0;
	foreach ( $arr_datas as $key=>$value )
	{
	    if ( $value )
	    {
	    	if ( $i != 0 ) 
		    $query .= ",";

	    	$query .= " $key='$value' ";
	    	$i++;
	    }
	}
	mysql_query ( $query, $this->m_connect );

	return array( seq => $arr_datas[seq], seq_subid => $arr_datas[seq_subid] );
    }

    //===========================================
    //
    // 배송 전 3자 물류 상품으로 교환
    // date: 2007.11.17 - jk
    function _change_rtmall_product_before( $seq, $qty, $new_product_id, $new_qty )
    {
debug ( "배송 전 3자 물류로 교환");
    	// name, option, enable_sale 정보가 전송됨
	$data = class_product::get_product_infos( $new_product_id );
        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];

	if ( $qty != $new_qty )
	{
	    // 교환 상품을 만들어 냄..
	    $new_seq = $this->copy_order( $seq );

	    //======================================
	    // 새로 만들어진 주문의 정보를 변경
	    // 신 상품의 정보로 신규 주문 생성 
	    $infos[order_cs]     = 5;      // 배송 전 교환 요청
	    $infos[qty]          = $new_qty;      
	    $this->sync_infos( $infos, $new_seq );	

	    //================================
	    // 기존 주문의 정보 변경
	    // 기존 주문은 수량이 변경 됨
	    // 기존 개수 - 신규 주문 개수 
	    // 개수가 다를 경우 신규 주문이 생성 된다. 
	    $infos2[order_cs]     = 5;      // 배송 전 교환 요청
	    $infos2[qty]          = $qty - $new_qty;      
	    $this->sync_infos( $infos2, $seq );	
	}
	else
	{
	    //==================================================
	    // set infos that would save at rtmall_order table
	    $infos[order_cs]     = 5;      // 배송 전 교환 요청
	    $this->sync_infos( $infos, $seq );	
	}
    }

    //=======================================
    //
    // 배송 후 3자 물류 상품으로 교환
    // 2007.11.17 - jk
    function _change_rtmall_product_after( $seq )
    {
debug ( "배송 후 3자 물류로 교환");

	echo "배송 후 3자 물류 교환";
	$infos[order_cs]     = 6;      // 배송 후 교환 요청
	$this->sync_infos( $infos, $seq );	
    }


    // 정보를 맞춰 줌
    function sync_infos( $arr_datas, $seq, $seq_subid=0 )
    {
	$query = "update rtmall_orders set ";
	
	$i = 0;
	foreach ( $arr_datas as $key=>$value )
	{
	    if ( $value )
	    {
	    	if ( $i != 0 ) 
		    $query .= ",";

		if ( $value == "NULL" )
	    	    $query .= " $key=''";
		else if ( $value == "now" )
	    	    $query .= " $key=Now() ";
		else
	    	    $query .= " $key='$value' ";

	    	$i++;
	    }
	}
	$query .= " where domain='" ._DOMAIN_ ."'
                     and seq='$seq' ";

	// if ( $seq_subid )
	//    $query .= " and seq_subid='$seq_subid'";	

debug ( "[sync infos] $query");

//echo "$query<br>";
	if ( $i > 0 )
	{
	    mysql_query ( $query, $this->m_connect ) or die( $query . mysql_error() );
	}
    }
    // 전체 송장 삭제
    function del_trans_all( $pack )
    {
	$query = "update rtmall_orders 
	             set trans_corp     = '', 
                         trans_date     = '',
                         trans_no       = '',
                         trans_date_pos = '',
                         status         = 1,
			 warehouse      = null
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and ( seq = $pack or pack = $pack )";
	debug ( "[del_trasn_all] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    // 개별 송장 삭제
    function del_trans_info( $seq )
    {
	$query = "update rtmall_orders 
	             set trans_corp    = '', 
                         trans_date    = '',
                         trans_no      = '',
                         trans_date_pos= '',
                         status        = '1',
			 warehouse     = null
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and seq = $seq";
	debug ( "[del_trasn_info] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // pack정보 변경
    function sync_pack( $old_pack, $new_pack )
    {
        if ( $old_pack)
        {
	    $query = "update rtmall_orders set pack=$new_pack 
                       where domain='" . _DOMAIN_ . "'
                         and pack=$old_pack";
	    mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
        }
    }

    ///////////////////////////////////////////////////
    // pack정보 변경
    function do_pack( $seq, $pack )
    {
	$query = "update rtmall_orders set pack=$pack 
                   where domain='" . _DOMAIN_ . "'
                     and seq=$seq";
debug( "[do pack] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // pack정보 변경
    function remove_pack( $seq )
    {
	$query = "update rtmall_orders set pack=null 
                   where domain='" . _DOMAIN_ . "'
                     and seq=$seq";
debug ( "[unpack] $query");
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    //======================================================
    // cs를 sync함
    // 2007.11.16 - jk
    function sync_cs ( $seq, $order_cs )
    {
	if ( $order_cs )
	{
	    $query = "update rtmall_orders set order_cs=$order_cs 
                   where domain='" ._DOMAIN_ ."'
                     and seq='$seq'";	
	    mysql_query ( $query, $this->m_connect );
	}
    }

    //==================================
    // rtmall 주문의 송장 번호 입력
    function insert_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no)
    {
	if ( $this->is_rtmall_use ( $seq ) )
	{
	    //$pack = $this->check_pack( $seq );
	    //if ( $pack )
	    //	$this->_update_pack_trans_no( $pack, $trans_corp, $trans_no, $warehouse);
	    //else
	    $this->_update_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no);
	}
    }

    //=========================================
    // 합포 인지 여부 check
    function check_pack ( $seq ) 
    {
	global $connect;
	$query = "select pack from rtmall_orders 
                   where domain='" ._DOMAIN_ ."'
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );

	if ( $data[pack] )
	    return $data[pack];
	else
	    return 0;
    }

    //=======================================
    // 개별 송장 입력
    // 2007.11.16 - jk
    // 합포와 일반을 모두 처리 함
    function _update_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no )
    {
        global $connect;
	$query    = "select status,order_cs,pack 
                       from orders 
                      where seq=$seq";
        $result   = mysql_query ( $query, $connect ) or die( mysql_error() );
        $data     = mysql_fetch_array( $result );
	$status   = $data[status];
	$order_cs = $data[order_cs];
	$pack     = $data[pack];

	/////////////////////////////////////////////
	$query = "update rtmall_orders 
			     set trans_corp='$trans_corp', 
				 trans_no='$trans_no', 
				 warehouse='$warehouse', 
				 order_cs ='$order_cs', 
				 status   ='$status', 
				 pack     ='$pack', 
				 trans_date=Now()
			   where domain='" ._DOMAIN_ ."'";

	// 합포 번호가 있을땐 한 번 더 돌린다
	if ( $insert_all_trans_no )
	{
	    if ( $pack )
	    {
	        mysql_query ( $query . " and pack=$pack", $this->m_connect );
                debug( "[update_trans_no1] $query and pack=$pack" );
	    }
	    else
                debug( "[update_trans_no no pack no] $query and pack=$pack" );
	}

	mysql_query ( $query ." and seq=$seq", $this->m_connect );
debug( "[update_trans_no2] $query and seq=$seq" );
	echo " change rtmall trans no";
    }

    //=======================================
    // 합포 송장 입력
    // 2007.11.16 - jk
    function _update_pack_trans_no( $pack, $trans_corp, $trans_no, $warehouse)
    {
        global $connect;
	$query    = "select status,order_cs,pack
                       from orders 
                      where seq=$pack";
        $result   = mysql_query ( $query, $connect );
        $data     = mysql_fetch_array( $result );
	$status   = $data[status];
	$order_cs = $data[order_cs];
	$pack     = $data[pack];

	$query    = "update rtmall_orders 
			     set trans_corp= '$trans_corp', 
				 trans_no  = '$trans_no', 
				 warehouse = '$warehouse', 
				 status    = '$status', 
				 order_cs  = '$order_cs', 
				 pack      = '$pack', 
				 trans_date= Now()
			   where domain='" ._DOMAIN_ ."'
			     and pack=$pack";	
//echo $query;	
debug( "[pack pack trans no] $query" );
	mysql_query ( $query, $this->m_connect );
	echo "all update";
    }

    //=====================================
    // rtmall 주문으로 등록되었는지 check
    function is_rtmall_order( $seq )
    {
    	$_return = 0;
	if ( $this->is_rtmall_use( $seq ) )
	{
	    if ( !$this->is_rtmall_reg( $seq ) )
	    {
	    	// $this->order_reg( $seq );
		// 작업 하지 않는다
		exit;
	    }

	    $_return = 1;	
	}
	return $_return;
    }

    function is_rtmall_reg( $seq )
    {
	$query = "select count(*) cnt 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ ."'
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt] ? 1 : 0;
    }

    //==================================
    // rtmall 주문의 송장 번호 입력
    // local connect 정보를 사용해야 함
    function is_rtmall_use( $seq )
    {
	global $connect;
	$query = "select product_id from orders where seq='$seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	$product_id = $data[product_id];

	// use_rtmall 값 check
	if ( $product_id )
	{
	    // 해당 상품이 rtmall 상품인지 check
	    $_result = $this->is_rtmall_product( $product_id );
	    echo "rtmall 사용";
	}
	else
	{
	    $_result = 0;
	}	

	return $_result;
    }

    function is_rtmall_product( $product_id )
    {
    	global $connect;
	$query = "select use_rtmall from products where product_id='$product_id'";	
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$_result = $data[use_rtmall];
	return $_result;	
    }

    //======================================================
    // cs를 sync함
    // 2007.11.16 - jk
    function sync_status ( $seq, $status )
    {
	if ( $status )
	{
	    $query = "update rtmall_orders set status = $status
			   where domain='" ._DOMAIN_ ."'
			     and seq='$seq'";	
	    mysql_query ( $query, $this->m_connect );
	}
    }


    //========================================
    // rtmall_regist에 작업 등록 2007.11.15 - jk
    function regist_tx( $type, $cnt, $msg, $user, $_date )
    {
	if ( $cnt )
	{
	    //================================================================
            // Q.
	    // req_date는 어떻게 해야 하지? 14일 발주를 17일날 요청 잡을 경우
            //
	    $query = "insert into rtmall_tx set 
				domain='" . _DOMAIN_ ."',
				req_user = '$user',
				req_date= Now(),
				req_cnt = '$cnt',
				msg = '$msg',
				status=0,
				type='$type'";

	    mysql_query ( $query, $this->m_connect );
	}
    }

    //========================================
    // 배송 요청 상세 
    function request_detail( $_date )
    {
	$query = "select * from rtmall_tx 
		   where req_date >= '$_date 00:00:00'
                     and req_date <= '$_date 23:59:59'
                     and domain='" . _DOMAIN_ . "'";
// echo $query;
	$result = mysql_query ( $query , $this->m_connect );
	return $result;
    }

    function cnt_req_orders( $arr_options )
    {
	//$sm=1; 
	//$sd=23; 
	//$sy=2008; 

        list( $sy, $sm, $sd ) = split("-", $arr_options[collect_date] );
	$start = mktime(0,0,0,$sm, $sd, $sy); 
	$end   = mktime(23,59,59,$sm, $sd, $sy); 

	$query = "select count(*) cnt from tbl_order
		   where date >=  $start 
                     and date <= $end 
                     and id = 'ezadmin' ";
	
	$result = mysql_query ( $query , $this->m_connect ) or die ( mysql_error() );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    /////////////////////////////////////////////////////
    // 관리자만 쓰는 update ezadmin 관리자를 위한 부분
    function update_order( $data )
    {
	if ( $data[seq] )
	{
	    $shop_name = class_C::get_shop_name( $data[shop_id] );

            $query   = "update rtmall_orders ";
            $options = "
                            set 
			    product_id      = '$data[product_id]',
			    pack            = '$data[pack]',
			    product_name    = '$data[product_name]',
			    options 	    = '$data[options]',
			    qty             = '$data[qty]',
			    status          = '$data[status]',
			    order_cs        = '$data[order_cs]',
                            collect_date    = '$data[collect_date]',
                            order_name      = '$data[order_name]',
                            recv_name       = '$data[recv_name]',
                            recv_tel        = '$data[recv_tel]',
                            recv_mobile     = '$data[recv_mobile]',
                            trans_who       = '$data[trans_who]',
                            recv_zip        = '$data[recv_zip]',
                            recv_address    = '$data[recv_address]',
                            memo            = '$data[memo]',
                            shop_id         = '$data[shop_id]',
                            shop_name       = '$shop_name',
			    priority        = '$data[priority]'";

	    if ( $data[warehouse] )
                $options .= " ,warehouse       = '$data[warehouse]'";

	    if ( $data[trans_no] )
		$options .= ", trans_no        = '$data[trans_no]'";

	    if ( $data[trans_corp] )
		$options .= ", trans_corp      = '$data[trans_corp]'";

	    if ( $data[trans_date] )
		$options .= ", trans_date      = '$data[trans_date]'";

	    if ( $data[trans_date_pos] )
		$options .= ", trans_date_pos  = '$data[trans_date_pos]'";

	    $query .= $options . " where domain='" . _DOMAIN_ . "' and seq='$data[seq]';";

	    mysql_query ( $query, $this->m_connect ) ;
	    $_result = mysql_affected_rows();
            debug( "[update_order] $query" . "/" .  $_result );
	 
	    // update가 안 될 경우 
	    if ( $_result == 0 )
            {
	        $query2 = "insert into rtmall_orders " . $options;
	        $query2 .= " ,seq   = $data[seq]
                             ,domain='" ._DOMAIN_ . "'";

                debug( "[update_order] $query2" );
	        mysql_query ( $query2, $this->m_connect ) ;
            }
	}
 
    }

    ////////////////////////////////////////////
    // 등록 여부 확인
    //
    function is_reg ( $seq )
    {
	$query  = "select ordernum from tbl_order where ordernum=$seq";

	$result = mysql_query ( $query, $this->m_connect ) ;
	$data   = mysql_fetch_array ( $result );

	return $data[ordernum] ? $data[ordernum] : 0;
    }

    //=======================================
    // 주문 삭제 
    // date: 2007.11.13
    function del_orders ( $seqs )
    {
    	$query = "delete from tbl_order where ordernum in ( $seqs ) and status=1";
	mysql_query ( $query, $this->m_connect );
	
    	$query = "delete from tbl_orderlist where orderno in ( $seqs ) and status=1";
	mysql_query ( $query, $this->m_connect );
    }

    function get_transno( $seq , &$tot_rows)
    {
	$query = "select a.isinvoice, b.delivery 
                    from tbl_orderlist a, tbl_agent_member b
                   where a.isagencyid = b.id
                     and a.orderno = $seq";	

	$result   = mysql_query ( $query, $this->m_connect );
        $tot_rows = mysql_num_rows( $result );

        $data     = mysql_fetch_array ( $result );
	return $data;
    }

    //=======================================
    // 주문 등록
    // date: 2007.11.13
    function order_reg ( $data )
    {
	if ( !$this->is_reg( $data[seq] ) )
	{
	    $supply_id = class_supply::get_supplyid( $data[supply_id] );
	    // $pnum      = class_product::get_barcode( $data[product_id] );
	    $pnum      = $data[product_id]; // 변경

            list( $sy, $sm, $sd ) = split("-", $data[collect_date] );
	    $collect_date         = mktime(0,0,0,$sm, $sd, $sy); 

	    if ( $data[trans_who] == "선불" )
		$prepay = 2500;  
 
	    // 주문 등록 
	    $cost      = class_product::get_shop_price( $data[product_id] );
	    $price     = $data[qty] * $cost;
	    $recv_zip  = str_replace( "-", "", $data[recv_zip] );
	    $order_zip = str_replace( "-", "", $data[order_zip] );

	    $tel = $data[order_tel]?$data[order_tel]:$data[recv_tel];
	    $hp  = $data[order_mobile]?$data[order_mobile]:$data[recv_mobile];

	    $query = "insert tbl_order set 
			    date        = $collect_date,
			    ordernum    = $data[seq],
                            id		= 'ezadmin',
			    name        = '$data[order_name]',
			    post	= '$order_zip',
			    addr	= '$data[order_address]',
			    tel		= '$tel',
			    hp		= '$hp',
			    receiver	= '$data[recv_name]',
			    repost      = '$recv_zip',
			    readdr      = '$data[recv_address]',
			    retel       = '$data[recv_tel]',
			    rehp        = '$data[recv_mobile]',
			    comment	= '$data[memo]',
			    status	= 1,
			    ispay	= 1,
			    paytype	= 2,
			    isagency	= 1,
			    price       = '$price',
			    isagencyid	= '$supply_id'";
	    if ( $prepay )
                 $query .= ",prepay=$prepay";

	    debug ( $query );
	    mysql_query ( $query, $this->m_connect );
	    //$affect = mysql_affected_rows();

	    // 상품 등록 
            // pnum은 barcode의 값이 들어감
            // pnum은 rtmall의 idx에 들어 있는 값임

	    //////////////////////////////////////////
	    // cost : 단가
            // price : 총 금액
	    // 2008.3.4 - jk
  	    $query = "insert tbl_orderlist set 
			    date        = $collect_date,
			    orderno     = $data[seq],
			    id		= 'ezadmin',
			    pcode	= '$data[product_id]',
			    pnum        = '$pnum',
			    name	= '$data[product_name]',
			    opt         = '$data[options]',
			    cost	= '$cost',
			    ea		= '$data[qty]',
			    price	= '$price',
			    status	= 1,
			    isagency	= 1,
			    isagencyid	= '$supply_id',
			    paytype     = 2,
			    ispay	= 1,
			    island	= '$data[island]'";
	    if ( $prepay )
                 $query .= ",prepay=$prepay";

	    debug ( $query );
	    mysql_query ( $query, $this->m_connect );
	    // $affect = mysql_affected_rows();
	}
	else
	{
	    debug ( " $data[seq] already reg " );
	    $result = 0; 	// fail
	}
	   
	return $affect; 
    }
    
    //=======================================
    // 등록된 상품을 rtmall에 복사 함
    function product_reg( $product_id , $data="")
    {
    	global $connect;

	if ( !$data )
	    $data = class_product::get_product_info ( $connect, $product_id );

	// rtmall상품인 경우에만 등록
	if ( $data[use_rtmall] ) 
	{
		$options = str_replace( array("\r","\n","\r\n"),"", $data[options] );

		//===================================
		// rtmall에 상품 data 전송
		$query = "insert rtmall_products set 
					domain      = '" . _DOMAIN_ ."',
					product_id  = '$data[product_id]',
					product_name= '$data[name]',
					options     = '$options',
					crdate      = Now(),
					enable_sale = '$data[enable_sale]',
					barcode     = '$data[barcode]',
					org_id      = '$data[org_id]',
					reg_date    = Now() ";
debug ( $query );
		mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
		// exit;
	}
    }

    //////////////////////////////////////
    //초기화
    function init_priority()
    {
	$query = "update rtmall_orders 
                     set priority=null 
                   where domain='" . _DOMAIN_ ."'
                     and status=1 and priority <> 99";
	mysql_query ( $query , $this->m_connect) or die( mysql_error() );
	return mysql_affected_rows();
    }


    function product_count( $arr_items )
    {
	$query = "select count(*) cnt from rtmall_products ";
	$query.= $this->build_option( $arr_items );

	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    //=============================================
    // query를 위한 option생성
    // date: 2007.11.21 - jk
    function build_option( $arr_items )
    {
	$_options = "";
	$i = 0;
        foreach ( $arr_items as $item=>$_opt )
        {
            global  $$item;
	    $$item = $$item ? $$item : $_opt;
            if ( $$item )
            {
                if ( $_cnt == 0 )
                        $_options .= " where ";
                else
                        $_options .= " and ";

                if ( $_opt == "like" )
                        $_options .= "$item like '%". $$item."%'";
                else
                        $_options .= "$item = '". $$item."'";

                $_cnt++;
            }
        }
	return $_options;
    }
   

    //=======================================
    //
    // 등록된 상품의 변경이 있을 경우 내용 update
    // 2007.11.12
    //
    function product_update( $product_id )
    {
    	global $connect;
	$data = class_product::get_product_info ( $connect, $product_id );

	// rtmall상품인 경우에만 등록
	if ( $data[use_rtmall] ) 
	    if ( $this->check_reg( $product_id ) )   // 실제 등록되어 있는 상품인지 여부 check해야 함
	    {
		$query = "select * From products where product_id='$product_id' or org_id='$product_id'";
                $result = mysql_query ( $query, $connect ) or die( mysql_error() );
                while ( $data = mysql_fetch_array ( $result ) )
		{
	    	    $this->_update( $data, $data[product_id] );
                }
	    }
	    else
	    {
	        $this->product_reg( $product_id );
	    }
    }

    //=========================================
    // 옵션의 수정 혹은 등록
    // org_id: 원 상품 id
    // option_id: 변경된 id
    function option_update( $org_product_id, $new_product_id )
    {
    	global $connect;
	$data = class_product::get_product_info ( $connect, $new_product_id );

	// 실제 등록되어 있는 상품인지 여부 check해야 함
	if ( $data[use_rtmall] ) 
	    if ( $this->check_reg( $org_product_id ) )               
	    	$this->_update( $data, $org_product_id );
	    else
	    	$this->product_reg( $new_product_id );
    }

    //=====================================
    // 실제 등록 여부 체크
    // 등록이 확인될 경우 1을 반환
    // 등록이 확인 안될 경우 0을 반환
    function check_reg( $product_id )
    {
    	$query = "select count(*) cnt 
                    from rtmall_products 
                   where product_id='$product_id' 
                     and domain='" . _DOMAIN_ . "'";

	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );

	if ( $data[cnt] )
		$result = 1;
	else
		$result = 0;

	return $result;
    }

    function _update( $data, $org_id )
    {
        //===================================
        // rtmall에 상품 data 전송
        $query = "update rtmall_products set 
				product_id  = '$data[product_id]',
    				product_name= '$data[name]',
    				options     = '$data[options]',
    				barcode     = '$data[barcode]',
				enable_sale = '$data[enable_sale]'
			where	domain      = '" . _DOMAIN_ ."'
			  and   product_id  = '$org_id'";
        //debug ( $query );
        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }

    //////////////////////////////////////////////////////
    // 배송 보류 취소 
    function cancel_hold()
    {
	global $connect, $seq;
	$query = "update rtmall_orders 
                     set hold=0
		   where domain = '" . _DOMAIN_ ."'
                     and (seq=$seq or pack=$seq)";
        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }


}

?>
