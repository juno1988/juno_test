<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_star.php";
require_once "class_vendor.php";
require_once "class_DX00.php";
require_once "class_3pl.php";
require_once "class_takeback.php";
require_once "class_notyet.php";
require_once "class_file.php";
require_once "class_cs.php";
require_once "class_transcorp.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_shop.php";
require_once "class_CI00.php";
require_once "class_lock.php";
require_once "class_sms.php";
require_once "class_ui.php";
require_once "class_multicategory.php"; // 2011.7.6 jk추가

require_once "class_ordercancel.php"; //최웅 추가.. 이지오토 부분취소 테스트중


 include "template/inc/box4u_func.inc";

$bck_connect = bck_db_connect();

////////////////////////////////
// class name: class_E900
//
class class_E900 extends class_top {

    function auto_cancel_test_ung()
    {
    	class_ordercancel::ordercancel();
    	
    }


    function E900()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type, $order_status;
        global $supply_code;


        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date)
        {
            if( $_SESSION[IS_DB_ALONE] )
                $start_date = date('Y-m-d', strtotime('-120 day'));
            else
                $start_date = date('Y-m-d', strtotime('-30 day'));
        }
        $end_date = $_REQUEST["end_date"];

        if ( $page )
        {
           echo "<script>show_waiting();</script>";
           $this->cs_list( &$total_rows, &$r_cs );
        }

        $master_code = substr( $template, 0,1);
        include "template/E/E900.htm";

        if ( $page )
           echo "<script>hide_waiting();</script>";
    }


    function E903()
    {
        global $connect, $prd_seq, $page_type;

        // order_products
        $query = "select * from order_products where seq=$prd_seq";
        $result = mysql_query($query, $connect);
        $data_order_products = mysql_fetch_assoc($result);
        
        // orders
        $query = "select * from orders where seq=$data_order_products[order_seq]";
        $result = mysql_query($query, $connect);
        $data_orders = mysql_fetch_assoc($result);
        
        // products
        $query = "select * from products where product_id='$data_order_products[product_id]'";
        $result = mysql_query($query, $connect);
        $data_products = mysql_fetch_assoc($result);

        // 발주서 field
        $code = array();
        $query = "select * from shopheader where shop_id=$data_orders[shop_id]";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result) )
            $code[$data[field_id]] = $data[shop_header];
            
        include "template/E/E903.htm";
    }
    function E906()
    {
        global $connect, $prd_seq;
            
        include "template/E/E906.htm";
    }
    
    function E904()
    {
        global $connect, $template, $page;
        global $shop_id, $start_date, $end_date, $keyword, $search_type, $date_type, $seq;

        if( !$start_date )
            $start_date = date("Y-m-d", strtotime("-30 day"));
        
        // parklon은 자동으로 수령자명 검색
        if( _DOMAIN_ == 'parklon' && !$page)
        {
            $query = "select recv_name from orders where seq=$seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $page = 1;
            $keyword = $data[recv_name];
            
            $end_date = date("Y-m-d");
        }
        
        if( $page )
        {
            $query = "SELECT a.seq			a_seq
							,a.collect_date a_collect_date
							,a.shop_id		a_shop_id
							,a.order_id		a_order_id
							,c.name			c_name
							,a.recv_name	a_recv_name
							,a.recv_tel		a_recv_tel
							,a.recv_mobile	a_recv_mobile
							,a.recv_address	a_recv_address
							,a.qty          a_qty
                        FROM orders a, order_products b, products c 
                       WHERE a.seq = b.order_seq AND b.product_id = c.product_id AND";

//			$query = "SELECT * 
//                        FROM orders a
//                       WHERE ";
            switch( $date_type )
            {
                case "collect_date":
                    $query .= " a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
                    break;
                default:
                    $query .= " a.collect_date >= '$start_date' and a.collect_date <= '$end_date' ";
                    break;
            }
            
            $query .= "  and a.status = 1
                         and a.order_cs not in (1,3)
                         and a.pack_lock = 0";
            if( $shop_id )
                $query .= " and a.shop_id = '$shop_id' ";                         
            if( $keyword )
            {
                switch( $search_type )
                {
                    // 수령자
                    case 0: 
                        if( strpos($keyword, "+") )
                        {
                            $key_arr = explode("+", $keyword);
                            $query .= " and a.recv_name = '$key_arr[0]' and (a.recv_tel like '%$key_arr[1]' or a.recv_mobile like '%$key_arr[1]') ";
                        }
                        else
                            $query .= " and a.recv_name = '$keyword' ";
                        break;
                    // 수령자 부분
                    case 14: 
                        $query .= " and a.recv_name like '%$keyword%' ";
                        break;
                    // 수령자 전화
                    case 1: 
                        if( strlen($keyword) == 4 )
                            $query .= " and substring(a.recv_tel,-4) = '$keyword' ";
                        else if( strpos($keyword, "-") === false )
                            $query .= " and replace(a.recv_tel,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                        else
                            $query .= " and a.recv_tel = '$keyword' ";
                        break;
                    // 수령자 핸드폰
                    case 2: 
                        if( strlen($keyword) == 4 )
                            $query .= " and substring(a.recv_mobile,-4) = '$keyword' ";
                        else if( strpos($keyword, "-") === false )
                            $query .= " and replace(a.recv_mobile,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                        else
                            $query .= " and a.recv_mobile = '$keyword' ";
                        break;
                    // 주소
                    case 16: 
                        $query .= " and a.recv_address like '%$keyword%' ";
                        break;
                    // 주문자
                    case 3: 
                        $query .= " and a.order_name = '$keyword' ";
                        break;
                    // 주문자 부분
                    case 15: 
                        $query .= " and a.order_name like '%$keyword%' ";
                        break;
                    // 주문자 전화
                    case 4: 
                        if( strlen($keyword) == 4 )
                            $query .= " and substring(a.order_tel,-4) = '$keyword' ";
                        else if( strpos($keyword, "-") === false )
                            $query .= " and replace(a.order_tel,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                        else
                            $query .= " and a.order_tel = '$keyword' ";
                        break;
                    // 주문자 핸드폰
                    case 5: 
                        if( strlen($keyword) == 4 )
                            $query .= " and substring(a.order_mobile,-4) = '$keyword' ";
                        else if( strpos($keyword, "-") === false )
                            $query .= " and replace(a.order_mobile,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                        else
                            $query .= " and a.order_mobile = '$keyword' ";
                        break;
    
                    // 관리번호
                    case 6: 
                        $query .= " and a.seq='$keyword' ";
                        break;
                    // 주문번호
                    case 7: 
                        $query .= " and (a.order_id = '$keyword' or a.order_id = 'C$keyword' or a.order_id = 'CC$keyword' or a.order_id = 'CCC$keyword' or a.order_id = 'CCCC$keyword' or a.order_id = 'CCCCC$keyword' or a.order_id = '*$keyword' or a.order_id like '$keyword%') ";
                        break;
                    // 주문번호부분
                    case 27: 
                        $query .= " and a.order_id like '%$keyword%' ";
                        break;
                    // 구매자ID
                    case 13: 
                        $query .= " and a.cust_id='$keyword' ";
                        break;
                }
            }
            
            $query .= " group by if(a.pack>0,a.pack,a.seq) order by a.seq desc limit 500";
        }
        
debug($query);
        $result = mysql_query($query, $connect);

        $master_code = substr( $template, 0,1);
        include "template/E/E904.htm";
    }

    function E905()
    {
        global $connect, $seq;

        // order_products
        
        $query = "SELECT seq, pack FROM orders WHERE seq = $seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $seq_list = array();
    	if($data[pack] > 0)
    	{	
    		$query = "SELECT seq FROM orders WHERE pack = $data[pack]";
    		$result = mysql_query($query, $connect);
        	while($data = mysql_fetch_assoc($result))
        		$seq_list[] = $data[seq];
    	}
    	else
    	 	$seq_list[] = $data[seq];
        
        $seq_list_str = implode(",", $seq_list);        
        $query = "SELECT    a.seq
					      , order_id
					      , order_id_seq
					      , c.name
					      , c.options
					      , b.qty
					      , now() print_date
					      , month(a.collect_date) m_collect_date
					      , day(a.collect_date) d_collect_date
					      , a.amount
					      , a.extra_money
					      , order_name
					      
					   FROM orders a
					   	  , order_products b
					   	  , products c
					   	  
					  WHERE a.seq = b.order_seq
					    AND b.product_id = c.product_id
					    AND a.seq IN ($seq_list_str)
					    AND b.order_cs NOT IN (1,2,3,4)
					    
			       ORDER BY a.seq
			       		  ,	a.order_id
			              , a.order_id_seq";
        $result = mysql_query($query, $connect);
debug($query);
        include "template/E/E905.htm";
    }
    // 판매처 cs link
    // 2008.8.13 - jk
    function cs_link()
    {
        global $shop_id, $order_id;
        $shop_code = $shop_id % 100;
        $func = "shop_" . $shop_code;

        $obj = new class_cs;

        if (in_array( $func, get_class_methods($obj))) 
        {
           $obj->${func}();
        }
        else
           echo "non exist";

    }

    ///////////////////////////////////
    // 회수 완료 2008.3.31 - jk
    function return_complete()
    {
        global $connect, $seq;
        $transaction = $this->begin("회수 완료");

        $val = array();
        $val['error'] = 0;

        // 회수 등록 여부 확인
        $data = class_takeback::get_detail( $seq );

        // data가 0 일 경우만 처리
        if ( !isset( $data[status] ) )
        {
            $val['result']        =  "회수 접수되지 않았습니다.";
            $val['reg_date']      = $data[reg_date];
            $val['trans_date']    = $data[trans_date] ? $data[trans_date] : "";
            if ( $data[complete] )
                $val['complete_date'] = $data[complete_date];
            else
                $val['complete_date'] = "";
        }
        else
        {
            $_result = class_takeback::complete_item( $seq );
            $val[error]         = $_result[error];
            $val[msg]           =  $_result[msg] ;
            $val[complete_date] = $_result[complete_date];
            $val[affected_row]  = $_result[affected_row];
        
        }
        echo json_encode( $val );

        // 회수 완료
        if ( $_SESSION[USE_3PL] )
        {
            $obj = new class_3pl();
            $obj->takeback_complete( $seq );
        }
    }

    /////////////////////////////////////
    // 택배사 code 2008.4.1
    function reg_return_transno()
    {
        global $connect, $seq, $trans_no, $trans_corp, $trans_who;
        $transaction = $this->begin("회수 송장 변경");
        class_takeback::update_transinfo($seq, $trans_no, $trans_corp, $trans_who);

debug ( "[reg_return_transno] 회수 송장 등록 / $trans_who" );

        $_trans_who[0] = "고객부담";        //
        $_trans_who[1] = "자사부담";

        $data = class_takeback::get_detail( $seq );
        $val = array();
        $val[trans_date] = $data[trans_date];
        $val[trans_corp] = $data[trans_corp];
        $val[trans_no]   = $data[trans_no];

        echo json_encode( $val );

        // 3pl업체의 경우 3pl정보 전송
        // 송장 등록 - 2008.9.29 - jk
        if ( $_SESSION[USE_3PL] )
        {
            $obj = new class_3pl();
            $obj->regist_takeback_transno( $seq, $trans_no, $_trans_who[$trans_who], $trans_corp );
        }
    }

    /////////////////////////////////////
    // 택배사 code 2008.4.1
    function get_transcode()
    {
        global $connect;
        $query = "select * from trans_info order by trans_corp";
        $result = mysql_query ( $query, $connect );
        $val = array();
        $val['corp_list'] = array();
        $i=0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $i++;
            $val['corp_list'][] = array( id=> $data[id], trans_corp=> $data[trans_corp] );        
        }

        echo json_encode( $val );
    }

    // del 2008.4.4 - jk
    function return_cancel()
    {
        global $seq;
        $transaction = $this->begin("회수 취소");
        $str = class_takeback::delete_takeback();
        echo "result";

        // 3pl업체의 경우 3pl정보 전송
        if ( $_SESSION[USE_3PL] )
        {
            $obj = new class_3pl();
            $obj->cancel_takeback( $seq );
        }
    }

    ///////////////////////////////////
    // 미송 취소 2008.4.23 - jk
    function notyet_cancel()
    {
        global $connect, $seq;
        $transaction = $this->begin("미송 취소");

        $_date = date('Y-m-d h:i:s', strtotime('today')); 
        $query = "delete from notyet_deliv where seq='$seq'";
        mysql_query ( $query, $connect );

        $val = array();
        $val['error']    = 0;
        $val['result']   = "취소 완료";
        if ( mysql_affected_rows() <= 0 )
        {
            $val['error'] = 1;
            $val['result'] = '이미 취소 되어 있습니다.';
        }
        echo json_encode( $val );
    }

    ///////////////////////////////////
    // 미송 요청 2008.4.23 - jk
    function notyet_req()
    {
        global $connect, $seq;
        $transaction = $this->begin("미송 요청");

//debug ( "notyet deliv" );

        $query = "select pack from orders where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        $_pack = $data[pack] ? $data[pack] : $seq;

        $_date = date('Y-m-d h:i:s', strtotime('today')); 
        $query = "insert notyet_deliv set seq='$seq', status=1, crdate=Now()";
        if ( $_pack ) $query .=" , pack='$_pack'";

//debug ( $query );

        mysql_query ( $query, $connect );

        $val = array();
        $val['error']    = 0;
        $val['result']   =  "등록 완료";

        if ( mysql_affected_rows() <= 0 )
        {
            $val['error'] = 1;
            $val['result'] = '이미 등록 되어 있습니다.';
        }

        // 등록된 시간 검색
        $query   = "select crdate from notyet_deliv where seq=$seq";
        $result  = mysql_query ( $query, $connect );
        $_crdate = mysql_result ( $result,0 );
        $val['reg_date'] = $_crdate;

        echo json_encode( $val );
    }

    ///////////////////////////////////
    // 매칭정보 삭제 2008.5.15 - jk
    function del_match()
    {
        global $connect, $seq;
        $transaction = $this->begin("매칭정보 삭제");

        $val = array();
        $val['error'] = 0;

        // orders에서 product_id 가져오기
        $query = "select shop_id, product_id from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );

        // code_match에서 
        $id      = $data[product_id];
        $shop_id = $data[shop_id];
        $query   = "delete from code_match where id='$id' and shop_id=$shop_id";
        $result = mysql_query( $query, $connect );

        $val['query'] = $query;
        $val['msg']   =  "매칭정보 삭제 완료";

        echo json_encode( $val );
    }

    ///////////////////////////////////
    // 회수 요청 2008.3.31 - jk
    function return_req()
    {
        global $connect, $seq;
        $transaction = $this->begin("회수 요청");

        $val = array();
        $val['error'] = 0;

        // 회수 등록 여부 확인
        $data = class_takeback::get_detail( $seq );

        // data가 0 일 경우만 처리
        if ( isset( $data[order_seq] ) )
        {
            $val['result']        =  "이미 회수 등록 되었습니다.";
            $val['reg_date']      = $data[reg_date];
            $val['trans_date']    = $data[trans_date] ? $data[trans_date] : "";
            if ( $data[complete] )
                $val['complete_date'] = $data[complete_date];
            else
                $val['complete_date'] = "";
        }
        else
        {
            // order_cs가 2,4일 경우만 회수 가능
            $query = "select product_id, status,qty from orders where seq=$seq";
debug ( $query );
            $result = mysql_query( $query, $connect );
            $data  = mysql_fetch_array ( $result );
            $_qty = $data[qty];    
debug ( "qty: $data[qty] " );
            if ( $data[status] == 8 )
            {
                $pid = $data[product_id];

                $str_takeback = class_takeback::regist_takeback(); 
                $val['result'] =  $str_takeback ;

                $data = class_takeback::get_detail( $seq );
                $val['reg_date']      = $data[reg_date];
                $val['trans_date']    = $data[trans_date] ? $data[trans_date] : "";
                if ( $data[complete] )
                    $val['complete_date'] = $data[complete_date];
                else
                    $val['complete_date'] = "";

                // 3pl업체의 경우 3pl정보 전송
                if ( $_SESSION[USE_3PL] )
                {
                    $obj = new class_3pl();
                    $obj->regist_takeback( $seq, $pid, $_qty );
                }
            }
            else
            {
                $val['error'] = 1;
                $val['result'] =  "배송된 건만 회수 등록이 가능합니다";
            }
        }
        echo json_encode( $val );

    }
    /////////////////////////////////
    //
    // orders table 정보 검색
    //
    function get_orders($seq)
    {
        global $connect;
        // 정보 검색
        $query  = "select * from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );   
    
        return $data;
    }
    /////////////////////////////////
    //
    // products table 정보 검색
    //
    function get_products($product_id)
    {
        global $connect;
        // 정보 검색
        $query  = "select * from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );   
        return $data;
    }    
    /////////////////////////////////
    //
    // order_products table 정보 검색
    //
    function get_order_products($seq)
    {
        global $connect;
        // 정보 검색
        $query  = "select * from order_products where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );   
    
        return $data;
    }
    
    ////////////////////////////////////////////////////////
    //
    // C/S 보류 설정 : 
    //
    //
    function set_cs_hold( $hold, $seq_str, $force=false )
    { 
        global $connect;
        
        switch($hold)
        {
            // 일반 보류 
            case 1: 
                $query = "update orders set hold=1 where seq in ($seq_str) and status <> 8 and hold not in (2,3,4,5,6)";
                break;
            // 주소 변경
            case 2: 
                $query = "update orders set hold=2 where seq in ($seq_str) and status <> 8 and hold not in (3,4,5,6)";
                break;
            // 교환
            case 3:
                $query = "update orders set hold=3 where seq in ($seq_str) and status <> 8 and hold not in (4,5,6)";
                break;
            // 전체 취소
            case 4:
                $query = "update orders set hold=4 where seq in ($seq_str) and status <> 8";
                break;
            // 부분 취소
            case 5:    
                $query = "update orders set hold=5 where seq in ($seq_str) and status <> 8";
                break;
            // 합포 변경
            case 6:
                $query = "update orders set hold=6 where seq in ($seq_str) and status <> 8" . 
                         ($force ? "" : " and hold not in (4,5)");
                break;
            default:
                return false;
        }   
        return mysql_query($query, $connect);
    }


    //////////////////////////////////////////////////////
    // 보류 설정
    function set_hold()
    {
        global $connect, $seq, $content;
        $val = array();

        $transaction = $this->begin("보류설정");

        // 주문 정보
        $data_orders = $this->get_orders($seq);

        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
        
        // 이미 배송된 경우
        if( $data_orders[status] == 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        if( $this->set_cs_hold(1, $seq_list ) )
        {
            $val['error'] = 0;
            $this->csinsert3($data_orders[pack], 1, $content, '', $data_orders[is_bck]);

            // BCK
            if( $data_orders[is_bck] )
                $this->save_to_bck($seq_list);
        }
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }  

    //////////////////////////////////////////////////////
    // 보류 취소
    function cancel_hold()
    {
        global $connect, $seq, $content;
        $val = array();

        $transaction = $this->begin("보류해제");

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 주문이 합포일 경우
        if ( $data_orders[pack] )
            $query = "update orders set hold=0 where pack=$data_orders[pack] and status <> 8";
        // 아닐 경우
        else
            $query = "update orders set hold=0 where seq=$data_orders[seq] and status <> 8";

        if( mysql_query ( $query, $connect ) )
        {
            $val['error'] = 0;
            $this->csinsert3($data_orders[pack], 2, $content, '', $data_orders[is_bck]);

            // BCK
            if( $data_orders[is_bck] )
            {
                $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
                $this->save_to_bck($seq_list);
            }
        }
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }

    /////////////////////////////////
    // 배송 정보 변경
    function change_deliv_info()
    {
        global $connect, $seq, $name, $tel, $mobile, $address, $zip, $memo, $gift, $trans_who, $cross_change, $content, $trans_fee, $pre_trans;
        $val = array();

        $transaction = $this->begin("배송변경");

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
        
        // 이미 배송된 경우
        if( $data_orders[status] == 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // 이전 정보와 비교하여 바뀐 내용 검색
        $disable_change = false; 
        $change = '<';
        if( $data_orders[recv_name]    != $name         ){  $change .= "수령자 변경:"         . $data_orders[recv_name]    . "->" . $name         . ","; $disable_change = true; } 
        if( $data_orders[recv_tel]     != $tel          ){  $change .= "수령자 연락처1 변경:" . $data_orders[recv_tel]     . "->" . $tel          . ","; $disable_change = true; } 
        if( $data_orders[recv_mobile]  != $mobile       ){  $change .= "수령자 연락처2 변경:" . $data_orders[recv_mobile]  . "->" . $mobile       . ","; $disable_change = true; } 
        if( $data_orders[recv_zip]     != $zip          ){  $change .= "우편번호 변경:"       . $data_orders[recv_zip]     . "->" . $zip          . ","; $disable_change = true; } 
        if( $data_orders[recv_address] != $address      ){  $change .= "주소 변경:"           . $data_orders[recv_address] . "->" . $address      . ","; $disable_change = true; } 
        if( $data_orders[memo]         != $memo         ){  $change .= "메모 변경:"           . $data_orders[memo]         . "->" . $memo         . ","; } 
        if( $data_orders[gift]         != $gift         ){  $change .= "사은품 변경:"         . $data_orders[gift]         . "->" . $gift         . ","; } 
        if( $data_orders[trans_who]    != $trans_who    ){  $change .= "선착불 변경:"         . $data_orders[trans_who]    . "->" . $trans_who    . ","; $disable_change = true; } 
        if( $data_orders[trans_fee]    != $trans_fee    ){  $change .= "배송비 변경:"         . $data_orders[trans_fee]    . "->" . $trans_fee    . ","; } 
        if( $data_orders[cross_change] != $cross_change ){  $change .= "맞교환 변경:"         . $data_orders[cross_change] . "->" . $cross_change . ","; } 
        if( $data_orders[pre_trans]    != $pre_trans    ){  $change .= "부분배송 변경:"       . $data_orders[pre_trans]    . "->" . $pre_trans    . ","; } 
        $change .= '>';
        
        // 송장상태인경우
        // 2014-02-14 장경희. cellogirl은 예외. 박진영 요청.
        // 2014-03-10 장경희. makoto 예외. 게시판 요청
        // 2014-11-12 최웅 환경설정으로 뺌. cellogirl candyglow makoto donnandeco dearjane bjstolo 하드코딩 되있었음
        if( $data_orders[status] == 7 && $disable_change && $_SESSION[IS_TRANSINFO_CHANGE] == 0 )
        {
            $val['error'] = 3;
            echo json_encode( $val );
            return;
        }

        $query = "update orders 
                     set recv_name    = '$name',
                         recv_tel     = '$tel',
                         recv_mobile  = '$mobile',
                         recv_address = '$address',
                         recv_zip     = '$zip',
                         memo         = '$memo',
                         gift         = '$gift',
                         trans_who    = '$trans_who',
                         trans_fee    = '$trans_fee',
                         cross_change = '$cross_change',
                         pre_trans    = '$pre_trans'
                   where seq in ($seq_list) and status <> 8";
        if( mysql_query ( $query, $connect ) )
        {
            $val['error'] = 0;
            
            // 새정보 입력
            foreach( explode(",", $seq_list) as $_seq )
                $this->inset_tel_info($_seq, array($tel,$mobile));
            
            //CS자동완료처리 [배송정보변경]
            if($_SESSION[CS_AUTO_COMPLETE2] == 1)            
            	$cs_result = 1;
            else
            	$cs_result = 0;
            	
            $this->csinsert8($data_orders[pack], 3, $content."", $change , '', $data_orders[is_bck], $cs_result);
            
            // 자동보류
            if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
                 ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
                $this->set_cs_hold(2, $seq_list);

            // BCK
            if( $data_orders[is_bck] )
                $this->save_to_bck($seq_list);
        }            
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }

    //////////////////////////////////////
    // 송장관리 - 입력 
    function insert_trans_no()
    {
        global $connect, $seq, $pack, $trans_corp, $trans_no, $content;

        $val = array();
        
        $transaction = $this->begin("송장입력");

        // 송장번호에서 숫자 아닌 문자는 제거
        if( _DOMAIN_ != 'cherryspoon' && _DOMAIN_ != 'dabagirl2' && _DOMAIN_ != 'jkhdev'  && _DOMAIN_ != 'onseason' )
            $trans_no = preg_replace("/[^0-9]/", "", $trans_no );

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 접수상태 아님
        if( $data_orders[status] != 1 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        // 택배사 오류
        if( !$trans_corp )
        {
            $val['error'] = 4;
            echo json_encode( $val );
            return;
        }

        if( $_SESSION[CS_MULTI_TRANS_NO] == 0 )
        {
            // 이미 사용된 송장번호
            $query_tr = "select seq from orders where trans_no='$trans_no'";
            $result_tr = mysql_query($query_tr, $connect);
            if( mysql_num_rows($result_tr) )
            {
                $val['error'] = 3;
                echo json_encode( $val );
                return;
            }
        }
       
        // Lock Check
        $obj_lock = new class_lock(101);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 송장정보 업데이트 쿼리
        $query = "update orders set trans_corp='$trans_corp', trans_no='$trans_no', trans_date=now(), status=7 ";
        if( $data_orders[pack] > 0 )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";

        // 접수상태의 주문만
        $query .= " and status=1";

        if( mysql_query ( $query, $connect ) )
        {
            // 송장정보
           $query_tr = "select * from trans_info where id=$trans_corp";
           $result_tr = mysql_query ( $query_tr , $connect );
           $data_tr = mysql_fetch_assoc($result_tr);
           
           	$sys_content = "[택배사:$data_tr[trans_corp], 송장번호:$trans_no] ";

            $val['error'] = 0;
            $this->csinsert8($data_orders[pack], 4, $content, $sys_content, '', $data_orders[is_bck]);

            // 송장로그
            $seq_str = $this->get_seq_list2($seq, $data_orders[pack]);
            $seq_arr = explode(",", $seq_str);
            foreach( $seq_arr as $seq_log )
            {
                $query_log = "insert trans_upload_log
                                 set order_seq   = $seq_log
                                     ,trans_no   = '$trans_no'
                                     ,trans_corp = '$trans_corp'
                                     ,owner      = '" . $_SESSION[LOGIN_NAME] . "'
                                     ,reg_type   = 1";
                mysql_query($query_log, $connect);
            }
            // BCK
            if( $data_orders[is_bck] )
                $this->save_to_bck($seq_str);
        }            
        else
            $val['error'] = 1;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////
    // 송장관리 - 입력 : box4u 전용 택배사만 지정함
    function insert_trans_no_corp()
    {
        global $connect, $seq, $pack, $trans_corp, $trans_no, $content;
        
        $val = array();
        
        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 접수상태 아님
        if( $data_orders[status] != 1 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        
        // 송장정보 업데이트 쿼리
        $query = "update orders set trans_corp='$trans_corp' ";
        if( $data_orders[pack] > 0 )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";

        // 접수상태의 주문만
        $query .= " and status=1";

        if( mysql_query ( $query, $connect ) )
        {
            // 송장정보
           $query_tr = "select * from trans_info where id=$trans_corp";
           $result_tr = mysql_query ( $query_tr , $connect );
           $data_tr = mysql_fetch_assoc($result_tr);
           
           $sys_content = "[택배사지정:$data_tr[trans_corp]] ";

            $val['error'] = 0;
            $this->csinsert8($data_orders[pack], 4, $content,$sys_content, '', $data_orders[is_bck]);
        }            
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }
    
    //////////////////////////////////////
    // 송장 관리 - 수정 box4u일경우만 수정가능 
    function update_trans_no()
    {
        global $connect, $seq, $pack, $trans_corp, $trans_no, $content;
        
        $val = array();
        
        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 송장정보 업데이트 쿼리
        $query = "update orders set trans_corp='$trans_corp', trans_no='$trans_no' ";
        if( $data_orders[pack] > 0 && _DOMAIN_ != 'box4u' )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";

        if( mysql_query ( $query, $connect ) )
        {
            // 송장정보
            $query_tr = "select * from trans_info where id=$trans_corp";
            $result_tr = mysql_query ( $query_tr , $connect );
            $data_tr = mysql_fetch_assoc($result_tr);
            
            $content = "송장번호 강제변경[택배사:$data_tr[trans_corp], 송장번호:$trans_no] " . $content;

            $val['error'] = 0;
            $this->csinsert3($data_orders[pack], 4, $content, '', $data_orders[is_bck]);

            // 송장로그
            if( _DOMAIN_ == 'box4u' )
            	$seq_str = $seq;
            else
            	$seq_str = $this->get_seq_list2($seq, $data_orders[pack]);

            $seq_arr = explode(",", $seq_str);
            foreach( $seq_arr as $seq_log )
            {
                $query_log = "insert trans_upload_log
                                 set order_seq   = $seq_log
                                     ,trans_no   = '$trans_no'
                                     ,trans_corp = '$trans_corp'
                                     ,owner      = '" . $_SESSION[LOGIN_NAME] . "'
                                     ,reg_type   = 1";
                mysql_query($query_log, $connect);
            }

            // BCK
            if( $data_orders[is_bck] )
                $this->save_to_bck($seq_str);
        }            
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }
    
    
    //////////////////////////////////////
    // 송장관리 - 삭제
    function delete_trans_no()
    {
        global $connect, $seq, $pack, $content;
        
        $transaction = $this->begin("송장삭제");

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 송장상태 아님
        if( $data_orders[status] != 7 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        
        // Lock Check
        $obj_lock = new class_lock(102);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 송장정보 업데이트 쿼리
        $query = "update orders set trans_corp='', trans_no='', trans_date=null, status=1, trans_key=0, auto_trans=0 ";
        if( $data_orders[pack] > 0 )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";

        // 송장상태의 주문만
        $query .= " and status=7";

        if( mysql_query ( $query, $connect ) )
        {
            $val['error'] = 0;
            $sys_content = "<택배사:" . $data_orders[trans_corp] . "><송장번호:" . $data_orders[trans_no] . "><송장입력일:" . $data_orders[trans_date] . "> ";
            $this->csinsert8($data_orders[pack], 5, $content, $sys_content, '', $data_orders[is_bck]);

            // BCK
            if( $data_orders[is_bck] )
            {
                $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
                $this->save_to_bck($seq_list);
            }
        }
        else
            $val['error'] = 1;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
        echo json_encode( $val );
    }

    ////////////////////////////////////////////////
    // 배송처리 - 확인
    function confirm_trans()
    {
        global $connect, $seq, $pack, $content, $trans_date;
        
        $val = array();
        
        $transaction = $this->begin("배송확인");

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 송장상태 아님
        if( $data_orders[status] != 7 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(114);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 합포 전체 취소 확인
        if( $data_orders[pack] > 0 )
        {
            $query_cs = "select order_cs from orders where pack=$data_orders[pack] group by order_cs";
            $result_cs = mysql_query($query_cs, $connect);
            
            $cancel_pack = true;
            while( $data_cs = mysql_fetch_assoc($result_cs) )
            {
                // 합포 중에 배송전 전체취소 아닌 주문이 하나라도 있으면 취소 아님
                if( $data_cs[orders_cs] != 1 )
                {
                    $cancel_pack = false;
                    break;
                }
            }
        }
        // 합포 아닌 경우, 전체취소 확인
        elseif( $data_orders[order_cs] == 1 )
            $cancel_pack = true;
        
        // 취소된 주문
        if( $cancel_pack )
        {
            $val['error'] = 3;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        // 배송확인 업데이트 쿼리
        if( $trans_date && $trans_date != date("Y-m-d") )
            $query = "update orders set trans_date_pos='$trans_date 00:00:00', status=8 ";
        else
            $query = "update orders set trans_date_pos=now(), status=8 ";

        if( $data_orders[pack] > 0 )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";

        // 취소인 주문 제외
        $query .= " and status=7 and order_cs <> 1";
        if( mysql_query ( $query, $connect ) )
        {
            $val['error'] = 0;
            $this->csinsert3($data_orders[pack], 6, $content, '', $data_orders[is_bck]);
            
            //정산 내역관리에 삽입
            if(_DOMAIN_ == 'box4u' || _DOMAIN_ == 'ezadmin' )
            {
				//box4u_func.inc include  $data_orders[shop_id] $data_orders[seq]
								
				shop_stat_upload_confirm_trans($data_orders); //정산내역관리에 삽입
		    	revenue_modify_confirm_trans($data_orders);   //일자별매출조정에 삽입
		    	stat_month_confirm_trans($data_orders[seq],"E900");  //판매처별매출조정에 삽입
		    	
		    	//자체배송만 상차지시서에 INSERT
		    	if($data_orders[trans_corp] == "30067")
		    		upload_direction_confirm_trans($data_orders);
        	}
            
            // 재고 차감
            if( $data_orders[pack] > 0 )
                $query = "select seq from orders where pack=$data_orders[pack] and status=8 and order_cs <> 1";
            else
                $query = "select seq from orders where seq=$seq and status=8 and order_cs <> 1";

            $result = mysql_query($query, $connect);
            while( $data_stock = mysql_fetch_assoc($result) )
            {
                $query_prd = "select product_id, qty, order_seq from order_products where order_seq=$data_stock[seq] and order_cs not in (1,2) and no_stock=0";
                $result_prd = mysql_query($query_prd, $connect);
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    $obj = new class_stock();
                    $obj->set_stock( array( type       => 'trans',
                                            product_id => $data_prd[product_id], 
                                            bad        => 0,
                                            location   => 'Def',
                                            qty        => $data_prd[qty],
                                            memo       => 'cs 배송처리',
                                            order_seq  => $data_prd[order_seq] ) );
                }
            }

            // BCK
            if( $data_orders[is_bck] )
            {
                $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
                $this->save_to_bck($seq_list);
            }
        }            
        else
            $val['error'] = 1;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    } 

    ////////////////////////////////////////////////
    // 배송처리 - 취소
    function cancel_trans()
    {
        global $connect, $seq, $pack, $content;
        
        $transaction = $this->begin("배송취소");

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);

        $trans_date_pos = $data_orders[trans_date_pos];
        
        // 배송상태 아님
        if( $data_orders[status] != 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(115);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 배송취소 업데이트 쿼리
        $query = "update orders set trans_date_pos=null, status=7 ";
        if( $data_orders[pack] > 0 )
            $query .= "where pack=$data_orders[pack]";
        else
            $query .= "where seq=$seq";
        $query .= " and status=8";
        if( mysql_query ( $query, $connect ) )
        {
            $val['error'] = 0;
            $sys_content = "<배송pos일:" . $trans_date_pos . ">" ;

            $this->csinsert8($data_orders[pack], 7, $content, $sys_content, '', $data_orders[is_bck]);

            // 재고 다시 살리기
            $obj = new class_stock();
            if( $data_orders[pack] > 0 )
                $query = "select seq from orders where pack=$data_orders[pack] and status=7 and order_cs <> 1";
            else
                $query = "select seq from orders where seq=$seq and status=7 and order_cs <> 1";
            $result = mysql_query($query, $connect);
            while( $data_stock = mysql_fetch_assoc($result) )
            {
                $query_prd = "select product_id, qty, order_seq from order_products where order_seq=$data_stock[seq] and order_cs not in (1,2) and no_stock=0";
                $result_prd = mysql_query($query_prd, $connect);
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    $obj->set_stock( array( type       => 'trans',
                                            product_id => $data_prd[product_id], 
                                            bad        => 0,
                                            location   => 'Def',
                                            qty        => -1 * $data_prd[qty],
                                            memo       => 'cs 배송취소',
                                            order_seq  => $data_prd[order_seq] ) );
                                            
                    if(_DOMAIN_ == "box4u" || _DOMAIN_ == "ezadmin")
                    {
						//box4u_func 배송처리취소
	                    trans_cancel($data_prd[order_seq],"","cancel_trans",$trans_date_pos);
	                    upload_direction_trans_cancel($seq, $data_orders[pack]);
	                }
                }
            }
            
            //++++++++++++++++++++++++++++++++++++++++++++++++++++++
            // order_cs 가 배송후 처리일 경우(3,4,7,8)
            //++++++++++++++++++++++++++++++++++++++++++++++++++++++
/*
            $seq_arr = explode(",", $seq_str);
            for($i=0; $i<count($seq_arr); $i++)
            {
                $query
            }
*/            
            $query_order_cs = "update orders set order_cs = order_cs - 2 where seq in ($seq_list) and order_cs in (3,4,7,8)";
            mysql_query($query_order_cs, $connect);
            $query_order_cs = "update order_products set order_cs = order_cs-2 where order_seq in ($seq_list) and order_cs in (3,4,7,8)";
            mysql_query($query_order_cs, $connect);

            // BCK
            if( $data_orders[is_bck] )
            {
                $this->save_to_bck($seq_list);
            }
        }            
        else
        {
            $val['error'] = 1;
            debug("배송취소 실패 : " . $query);
        }

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    } 

    // 합포 추가
    function add_pack()
    {
        global $connect, $seq, $pack_seq, $content;

        $transaction = $this->begin("합포 추가 seq:$seq / pack:$pack_seq");

        // 합포 기준 주문 정보
        $data_orders = $this->get_orders($seq);

        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
        
        // 오류 : 이미 배송 
        if( $data_orders[status] == 8 )
        {
            $val['error'] = 4;
            echo json_encode( $val );
            return;
        }

        if( $data_orders[pack_lock] == 1 )
            $pack_lock = 1;
        else
            $pack_lock = 0;

        // 합포될 seq
        $added_seq_all = array();

        $priority = $data_orders[cs_priority];
        $trans_key = $data_orders[trans_key];
        $new_trans_who = $data_orders[trans_who];

        $trans_who_cod = array();
        if( $data_orders[trans_who] == '착불' )  $trans_who_cod[] = $seq;
        
        $pack_seq_arr = explode(",", $pack_seq);
        $_arr = array();
        foreach( $pack_seq_arr as $v_seq )
            $_arr[] = trim( $v_seq );
        
        // 합포될 주문 정보
        foreach( array_unique($_arr) as $v_seq )
        {
            $query = "select * from orders where seq=$v_seq";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) > 0 )
            {
                $data_added = mysql_fetch_assoc($result);
                
                if( $data_added[pack] > 0 )
                {
                    $query_pack_seq = "select * from orders where pack=$data_added[pack]";
                    $result_pack_seq = mysql_query($query_pack_seq, $connect);
                    while( $data_pack_seq = mysql_fetch_assoc($result_pack_seq) )
                        $added_seq_all[] = $data_pack_seq[seq];
                }
                else
                    $added_seq_all[] = $v_seq;

                $priority = ($priority < $data_added[cs_priority] ? $data_added[cs_priority] : $priority);
                $trans_key = ($trans_key < $data_added[trans_key] ? $data_added[trans_key] : $trans_key);
                if( $data_added[trans_who] == '선불' )
                    $new_trans_who = '선불';
                else
                    $trans_who_cod[] = $data_added[seq];
            }
            // 오류 : 주문정보 없음. 추가 주문의 seq 오류
            else
                $val['error'] = 2;

            // 현재 합포인 주문 리스트
            if( $data_orders[pack] > 0 )
            {
                // 오류 : 추가할 주문이 이미 합포
                if( array_search($v_seq, explode(",",$seq_list)) !== false )
                    $val['error'] = 3;
            }
                       
            // 오류 : 발주상태의 주문일 경우
            if( $data_added[status] == 0 )
                $val['error'] = 5;
    
            // 오류 : 송장상태의 주문일 경우
            if( $data_added[status] == 7 )
                $val['error'] = 6;
    
            // 오류 : 배송상태의 주문일 경우
            if( $data_added[status] == 8 )
                $val['error'] = 7;
    
            // 오류 : 합포금지 주문일 경우
            if( $data_orders[pack_lock] == 1 || $data_added[pack_lock] == 1 )
                $val['error'] = 8;
    
            // 오류 : 기준주문이 착불 송장상태이고 추가 주문이 선불인 경우
            if( $data_orders[status] == 7 && $data_orders[trans_who] == "착불" && $data_added[trans_who] == "선불" )
                $val['error'] = 9;

            if( $val['error'] > 0 )
            {
                $val['error_seq'] = $v_seq;
                echo json_encode( $val );
                return;
            }
        }

        // Lock Check
        $obj_lock = new class_lock(103);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 쿼리 실행 결과
        $result1 = true;


        // 기존 주문의 pack 또는 seq
        if( $data_orders[pack]> 0 )
            $orders_pack_seq = $data_orders[pack];
        else
        {
            $orders_pack_seq = $data_orders[seq];

            // 원 주문에 pack
            $query = "update orders set pack=$seq where seq=$seq";
            $result1 = $result1 && mysql_query($query, $connect);
        }

        // 송장상태의 주문에 접수상태의 주문을 합포할 경우, 합포되는 주문도 송장상태로 변경
        if( $data_orders[status] == 7 )
        {
            $set_str = "set pack       = $orders_pack_seq, 
                            trans_no   = '$data_orders[trans_no]', 
                            trans_corp = $data_orders[trans_corp],
                            trans_date = '$data_orders[trans_date]', 
                            status     = 7";
        }
        else
            $set_str = "set pack=$orders_pack_seq";
            
        $query = "update orders $set_str where seq in (" . implode(",", $added_seq_all) . ")";
debug("query 1 : " . $query);
        $result1 = $result1 && mysql_query($query, $connect);

        if( $new_trans_who == '선불' && count($trans_who_cod) > 0 )
            $change_trans_who = "[선착불정보 변경 - 착불주문 : " . implode(",", $trans_who_cod) . "]";
        else
            $change_trans_who = "";

        // 우선순위와 trans_key, trans_who 변경
        $pack = ( $data_orders[pack]> 0 ? $data_orders[pack] : $data_orders[seq] );
        
        $query = "update orders set cs_priority = $priority, trans_key = $trans_key, trans_who='$new_trans_who' where pack = $pack";
debug("query 2 : " . $query);
        $result1 = $result1 && mysql_query($query, $connect);
        
        if( $result1 )
        {
            $val['error'] = 0;
            $sys_content = "<추가주문:" . $pack_seq . ">$change_trans_who";
            
            
			//CS자동완료처리 [합포추가]
            if($_SESSION[CS_AUTO_COMPLETE3] == 1)            
            	$cs_result = 1;
            else
            	$cs_result = 0;
            $this->csinsert8($seq_pack, 8, $content, $sys_content, '',$data_orders[is_bck],$cs_result);

            // 추가 시킬 주문의 리스트를 더함
            $seq_list .= "," . implode(",", $added_seq_all);

            // 합포 주문 보류 확인
            $pack_hold = 0;
            $query_hold = "select seq, hold from orders where seq in ($seq_list)";
            $result_hold = mysql_query($query_hold, $connect);
            while( $data_hold = mysql_fetch_assoc($result_hold) )
            {
                switch( $data_hold[hold] )
                {
                    case 1:
                    case 2:
                    case 3:
                        if( $data_hold[hold] > $pack_hold )
                            $pack_hold = $data_hold[hold];
                        break;
                    case 4:
                        $pack_hold = 4;
                        break 2;
                    case 5:
                        $pack_hold = 5;
                        break;
                    case 6:
                        if( $pack_hold != 5 )
                            $pack_hold = 6;
                        break;
                }
            }
            
            if( $pack_hold > 0 )
            {
                $query_pack_update = "update orders set hold = $pack_hold where seq in ($seq_list)";
                mysql_query($query_pack_update, $connect);
            }

            // 자동보류
            if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
                 ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
            {
                $this->set_cs_hold( 6, $seq_list );
            }

            // BCK
            if( $data_orders[is_bck] )
                $this->save_to_bck($seq_list);
        }            
        else
            $val['error'] = 1;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
            
        echo json_encode( $val );
    }
    
    // 합포 제외
    // 2014-12-15 장경희. 재고할당 화면에서 일괄합포제외 실행을 위해 echo 및 lock 코드에 $drop_pack_all 플래그를 걸어둔다.
    function drop_pack()
    {
        global $connect, $checked_data, $content, $org_pack_lock, $drop_pack_lock, $org_pre_trans, $drop_pre_trans, $stock_assign, $drop_pack_all;

        $val = array();
        $transaction = $this->begin("합포 제외");

        //************************
        // checked data 확인
        //************************
        // 
        // $checked_seq_arr
        // $checked_seq_qty_arr
        // $checked_seq_str
        // 

        // 체크한 order_products의 seq 배열
        $checked_seq_arr = array();
        // 체크한 order_products의 seq & qty 배열
        $checked_seq_qty_arr = array();
        foreach( explode(",", $checked_data) as $checked_val )
        {
            if( $checked_val )
            {
                list($_seq, $_qty) = explode(":", $checked_val);
                
                // checked seq 배열
                $checked_seq_arr[] = $_seq;
                
                // 수량 배열
                $checked_seq_qty_arr[$_seq] = $_qty;
            }
        }
        // 체크한 order_products의 seq 리스트
        $checked_seq_str = implode(",", $checked_seq_arr);

        //************************
        // 전체 주문 data
        //************************

        // 전체 주문 pack 배열
        $pack_all_arr = array();
        // 전체 주문 seq 배열
        $seq_all_arr = array();
        
        $check_is_bck = false;

        $query = "select a.seq a_seq, 
                         a.pack a_pack,
                         a.is_bck a_is_bck
                    from orders a, 
                         order_products b 
                   where a.seq = b.order_seq and 
                         b.seq in ($checked_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $pack_all_arr[] = $data[a_pack];
            $seq_all_arr[] = $data[a_seq];
            
            if( $data[a_is_bck] )
                $check_is_bck = true;
        }
        $pack_all_arr = array_unique( $pack_all_arr );
        $seq_all_arr = array_unique( $seq_all_arr );
        
        $bck_seq_list = implode(",", $seq_all_arr);

        //---------------------
        // 복수 합포 오류 
        //---------------------
        if( count($pack_all_arr) > 1 )
        {
            $val['error'] = 5;

            if( !$drop_pack_all )
                echo json_encode( $val );

            return;
        }
        
        //+++++++++++++++++++
        // 기존 합포 pack 번호
        //+++++++++++++++++++
        //
        // $old_pack
        //

        $old_pack = $pack_all_arr[0];
        //---------------------
        // 합포 오류 
        //---------------------
        if( $old_pack == 0 && count($seq_all_arr) > 1 )
        {
            $val['error'] = 5;
            
            if( !$drop_pack_all )
                echo json_encode( $val );

            return;
        }
        
        //+++++++++++++++++++
        // 기존 seq list
        //+++++++++++++++++++
        //
        // $old_seq_arr
        // $old_seq_str
        // 
        
        $old_seq_arr = array();
        $old_seq_str = "";
        
        if( $old_pack == 0 )
            $old_seq_arr = $seq_all_arr;
        else
        {
            $query = "select seq from orders where pack=$old_pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $old_seq_arr[] = $data[seq];
        }
        $old_seq_str = implode(",", $old_seq_arr);
        
        //************************
        // 전체 모든 데이터
        //************************
        //
        // $pack_all = array();
        //
        
        $pack_all = array();

        $query = "select * from orders where seq in ($old_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $_temp_arr = $data;
            $_temp_arr["work_type"] = 0;
            $_temp_arr["checked_type"] = "";
            
            $query_p = "select * from order_products where order_seq = $data[seq]";
            $result_p = mysql_query($query_p, $connect);
            while( $data_p = mysql_fetch_assoc($result_p) )
            {
                $_temp_arr2 = $data_p;
                $_temp_arr2["work_type"] = 0;
                $_temp_arr2["checked_qty"] = 0;

                $_temp_arr["order_products"][] = $_temp_arr2;
            }
                
            $pack_all[] = $_temp_arr;
        }

        //************************
        // 합포제외 오류 체크
        //************************
        $drop_all_error = true;
        foreach( $pack_all as $pack_key => $pack_val )
        {
            //---------------------
            // 합포금지 오류
            //---------------------
            if( $pack_val[pack_lock] )
            {
                $val['error'] = 3;

                if( !$drop_pack_all )
                    echo json_encode( $val );

                return;
            }

            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크되지 않은 order_products 있는경우
                if( array_search($seq_val[seq], $checked_seq_arr) === false )
                    $drop_all_error = false;
                // 체크된 order_products
                else
                {
                    //---------------------
                    // 배송상태 오류
                    //---------------------
                    if( $pack_val[status] == 8 )
                    {
                        $val['error'] = 4;

                        if( !$drop_pack_all )
                            echo json_encode( $val );

                        return;
                    }
            
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = $checked_seq_qty_arr[$seq_val[seq]];

                    // 수량이 작을 경우
                    if( $seq_val[qty] > $checked_seq_qty_arr[$seq_val[seq]] )
                        $drop_all_error = false;
                    else
                    {
                        //---------------------
                        // 합포제외 수량 오류
                        //---------------------
                        if( $seq_val[qty] < $checked_seq_qty_arr[$seq_val[seq]] )
                        {
                            $val['error'] = 1;

                            if( !$drop_pack_all )
                                echo json_encode( $val );

                            return;
                        }
                    }
                }
            }
        }
        //---------------------
        // 전체 합포 제외 오류
        //---------------------
        if( $drop_all_error )
        {
            $val['error'] = 2;

            if( !$drop_pack_all )
                echo json_encode( $val );

            return;
        }

        // Lock Check
        if( !$drop_pack_all )
        {
            $obj_lock = new class_lock(104);
            if( !$obj_lock->set_start(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
    
                if( !$drop_pack_all )
                    echo json_encode( $val );
    
                return;
            }
        }
        
        //+++++++++++++++++
        // 합포제외 로그 
        //+++++++++++++++++
        
        // last drop_no
        $query = "select max(drop_no) max_drop_no from orders_drop";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $new_drop_no = $data[max_drop_no] + 1;
        
        $query = "select * from orders where seq in ($old_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_new = "insert orders_drop set ";
            foreach($data as $data_key => $data_val)
            {
                $new_val = str_replace("'", "\\'", $data_val);
                $query_new .= " $data_key = '$new_val',";
            }
            $query_new .= "drop_no=$new_drop_no, drop_worker='$_SESSION[LOGIN_NAME]', drop_date=now()";
            mysql_query($query_new, $connect);
            
            $query_prd = "select * from order_products where order_seq=$data[seq]";
            $result_prd = mysql_query($query_prd, $connect);
            while( $data_prd = mysql_fetch_assoc($result_prd) )
            {
                $query_new = "insert order_products_drop set ";
                foreach($data_prd as $data_key => $data_val)
                {
                    $new_val = str_replace("'", "\\'", $data_val);
                    $query_new .= " $data_key = '$new_val',";
                }
                $query_new .= "drop_no=$new_drop_no ";
                mysql_query($query_new, $connect);
            }
        }
        

        //+++++++++++++++++
        // 합포제외 시작
        //+++++++++++++++++

        //+++++++++++
        // 수량분리
        foreach( $pack_all as $pack_key => $pack_val )
        {
            $is_checked = false;
            $is_unchecked = false;
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크되지 않은 order_products 
                if( array_search($seq_val[seq], $checked_seq_arr) === false )
                {
                    $is_unchecked = true;
                    continue;
                }
                else
                    $is_checked = true;

                // 체크된 order_products의 수량이 작을 경우
                if( $seq_val[qty] > $seq_val[checked_qty] )
                {
                    // 사은품은 금액 나누기 제외
                    if( $seq_val[is_gift] )
                        $seq_val_checked_qty = 0;
                    else
                        $seq_val_checked_qty = $seq_val[checked_qty];

                    $qty_ratio = $seq_val_checked_qty / $seq_val[qty];

                    // order_products 복사( insert )
                    $new_seq = $seq_val;
                    $new_seq[work_type] = 1;

                    // 기존 order_products update
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = 0;
                    
                    // qty
                    $new_seq[qty] = $seq_val[checked_qty];
                    $pack_all[$pack_key][order_products][$seq_key][qty] = $seq_val[qty] - $new_seq[qty];

                    // price update
                    $price_update_array = array("org_price","refund_price","extra_money","prd_amount","prd_supply_price");
                    foreach( $price_update_array as $price_val )
                    {
                        // 기존 금액
                        $old_price = $seq_val[$price_val];
                        // 수량 비율로 나눈 새 금액
                        $new_seq[$price_val] = round($old_price * $qty_ratio);
                        // 나머지 금액
                        $pack_all[$pack_key][order_products][$seq_key][$price_val] = $old_price - $new_seq[$price_val];
                    }

                    // order_products 추가
                    $pack_all[$pack_key][order_products][] = $new_seq;
                    $is_unchecked = true;
                }
            }

            // check type
            if( $is_checked && $is_unchecked )
                $pack_all[$pack_key][checked_type] = "part";
            else if( $is_checked && !$is_unchecked )
                $pack_all[$pack_key][checked_type] = "all";
            else
                $pack_all[$pack_key][checked_type] = "none";
        }

        //+++++++++++
        // orders 분리
        foreach( $pack_all as $pack_key => $pack_val )
        {
            // 부분 check 된 주문만 처리
            if( $pack_val[checked_type] != "part" )  continue;

            // orders 복사
            $new_order = $pack_val;
            $new_order[work_type] = 1;
            $new_order[checked_type] = "all";

            // check 안된 order_products 지우기
            $new_qty_sum = 0;
            $new_org_price_sum = 0;
            foreach( $new_order[order_products] as $seq_key => $seq_val )
            {
                if( $seq_val[checked_qty] == 0 )
                    unset( $new_order[order_products][$seq_key] );
                else
                {
                    // 사은품이 아닌 상품만
                    if( $seq_val[is_gift] == 0 )
                    {
                        $new_qty_sum += $seq_val[qty];
                        
                        // 2014-02-28 장경희. 원가가 모두 0일 경우 정산금액 나누기에서 문제 발생.
                        $new_org_price_sum += ($seq_val[org_price] > 0 ? $seq_val[org_price] : 1);
                    }
                }
            }
            
            // 기존 orders update
            $pack_all[$pack_key][checked_type] = "none";
        
            // check 된 order_products 지우기
            $old_qty_sum = 0;
            $old_org_price_sum = 0;
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                if( $seq_val[checked_qty] > 0 )
                    unset( $pack_all[$pack_key][order_products][$seq_key] );
                else
                {
                    // 사은품이 아닌 상품만
                    if( $seq_val[is_gift] == 0 )
                    {
                        $old_qty_sum += $seq_val[qty];

                        // 2014-02-28 장경희. 원가가 모두 0일 경우 정산금액 나누기에서 문제 발생.
                        $old_org_price_sum += ($seq_val[org_price] > 0 ? $seq_val[org_price] : 1);
                    }
                }
            }
            
            
            // 2012-09-20
            // 정산에서 문제가 되어 0을 1로 한다.
            
            // orders 수량 나누기
            if( $pack_val[qty] == 0 )
            {
                // 기존 orders 수량이 0 이면 둘다 0
                $new_qty = 1;
                $old_qty = 1;
            }
            else if( $pack_val[qty] == 1 )
            {
                // 기존 orders 수량이 1 이면, old_qty=1, new_qty=0
                $new_qty = 1;
                $old_qty = 1;
            }
            else
            {
                // 나머지 경우는 비율대로
                $old_qty = round( $pack_val[qty] * $old_qty_sum / ( $new_qty_sum + $old_qty_sum ) );
                if( $old_qty < 1 )  $old_qty = 1;
                
                $new_qty = $pack_val[qty] - $old_qty;
            }

            // orders 가격 나누기
            $org_price_ratio = $new_org_price_sum / ( $new_org_price_sum + $old_org_price_sum );
            foreach( $pack_val as $pack_item_key => $pack_item_val )
            {
                if( $pack_item_key == "qty" )
                {
                    $new_order[$pack_item_key] = $new_qty;
                    $pack_all[$pack_key][$pack_item_key] = $old_qty;
                }
                else if( $pack_item_key == "refund_price" || 
                         $pack_item_key == "extra_money"  || 
                         $pack_item_key == "amount"       || 
                         $pack_item_key == "supply_price" )
                {
                    $new_order[$pack_item_key] = round($pack_val[$pack_item_key] * $org_price_ratio);
                    $pack_all[$pack_key][$pack_item_key] -= $new_order[$pack_item_key];
                }
                else if( $pack_item_key == "code11"       ||
                         $pack_item_key == "code12"       ||
                         $pack_item_key == "code13"       ||
                         $pack_item_key == "code14"       ||
                         $pack_item_key == "code15"       ||
                         $pack_item_key == "code16"       ||
                         $pack_item_key == "code17"       ||
                         $pack_item_key == "code18"       ||
                         $pack_item_key == "code19"       ||
                         $pack_item_key == "code20"       ||
                         $pack_item_key == "code31"       ||
                         $pack_item_key == "code32"       ||
                         $pack_item_key == "code33"       ||
                         $pack_item_key == "code34"       ||
                         $pack_item_key == "code35"       ||
                         $pack_item_key == "code36"       ||
                         $pack_item_key == "code37"       ||
                         $pack_item_key == "code38"       ||
                         $pack_item_key == "code39"       ||
                         $pack_item_key == "code40"       )
                {
                    // AK몰은 code 값에 수량 곱하기 안되있음. 합포제외시 금액 나누지 않음
                    // 인터파크 code14 값에 수량 곱하기 안되있음. 합포제외시 금액 나누지 않음
                    if( 
                        $pack_val['shop_id'] % 100 != 42 &&
                        ($pack_val['shop_id'] % 100 != 6 || ($pack_item_key != "code12" && $pack_item_key != "code14") )
                    )
                    {
                        $new_order[$pack_item_key] = round($pack_val[$pack_item_key] * $org_price_ratio);
                        $pack_all[$pack_key][$pack_item_key] -= $new_order[$pack_item_key];
                    }
                }
            }
            
            // orders 추가
            $pack_all[] = $new_order;
        }
        
        //+++++++++++
        // pack 분리
        $pack_drop = array();
        $pack_remain = array();
        $pack_no_drop = -1;
        $pack_no_remain = 0;
        
        // 분리되는 합포의 예비 pack 
        $pack_no_drop_ready = array();
        
        $remain_hold = 0;
        $remain_status = 0;
        $remain_trans_key = 0;

        $drop_seq_arr = array();
        foreach( $pack_all as $pack_key => $pack_val )
        {
            if($pack_val[checked_type] == "all")
            {
                $pack_drop[] = $pack_val;
                $drop_seq_arr[] = $pack_val[seq];
                // 기본 합포 포함여부(insert가 아닌 경우만)
                if( $pack_val[seq] == $old_pack && $pack_val[work_type] != 1 )  $pack_no_drop = $old_pack;
                
                // insert 대상이 아닌 orders의 seq는 새로 생서되는 합포의 pack 번호가 될 수 있다.
                if( $pack_val[work_type] != 1 )
                    $pack_no_drop_ready[] = $pack_val[seq];
            }
            else
            {
                $pack_remain[] = $pack_val;

                // 기본 합포 포함여부
                if( $pack_val[seq] == $old_pack )  $pack_no_remain = $old_pack;
                
                // 남는 주문의 보류
                if( $pack_val[hold] == 5 || $remain_hold == 5 )
                    $remain_hold = 5;
                else if( $pack_val[hold] == 4 || $remain_hold == 4 )
                    $remain_hold = 4;
                else
                    $remain_hold = $pack_val[hold];

                // 남는 주문의 상태 최대값. (보류 설정)
                if( $pack_val[status] > $remain_status )  $remain_status = $pack_val[status];
                // 남는 주문의 송장다운로드 여부 최대값. (보류 설정)
                if( $pack_val[trans_key] > $remain_trans_key )  $remain_trans_key = $pack_val[trans_key];
            }
        }
        
        // order_cs
        $pack_drop = $this->change_cs_new_drop($pack_drop);
        $pack_remain = $this->change_cs_new_drop($pack_remain);
        
        // 합포번호 - drop
        if( count($pack_drop) == 1 )
            $pack_no_drop = 0;
        else
        {
            // pack 주문이 쪼개지는 경우, 남는 주문의 합포 번호가 기존 합포번호가 되므로, drop 주문의 pack은 -1 처리
            if( $pack_no_drop != $old_pack || $pack_no_remain == $old_pack )
            {
                if( count( $pack_no_drop_ready ) )
                    $pack_no_drop = $pack_no_drop_ready[0];
                else
                    $pack_no_drop = -1;
            }
        }

        // 합포번호 - remain
        if( count($pack_remain) == 1 )
            $pack_no_remain = 0;
        else if( $pack_no_remain == 0 )
            $pack_no_remain = $pack_remain[0][seq];

        // 2014-06-02 장경희. 합포제외시 제외된 주문도 기존 주문과 보류설정 동일하게
        if( $remain_hold == 0 )
        {
            if ( ($_SESSION[AUTO_HOLD] >= 1 && $remain_status == 7) || 
                 ($_SESSION[AUTO_HOLD] == 1 && $remain_status == 1 && $remain_trans_key > 0) )
            {
                $remain_new_hold = 6;
            }
            else
                $remain_new_hold = 0;
        }
        else
            $remain_new_hold = $remain_hold;
        
        //++++++++++++++++++++++++++++++
        // orders drop insert & update
        foreach( $pack_drop as $pack_key => $pack_val )
        {
            // orders drop insert | update
            if( $pack_val[work_type] == 1 )
                $query = "insert orders set ";
            else
                $query = "update orders set ";

            foreach( $pack_val as $pack_item_key => $pack_item_val )
            {
                if( $pack_item_key == "seq"            ||
                    $pack_item_key == "work_type"      ||
                    $pack_item_key == "checked_type"   ||
                    $pack_item_key == "order_products" )  continue;
                
                if( $pack_item_key == "pack" )
                    $query .= " $pack_item_key = '$pack_no_drop',";
                else if( $pack_item_key == "status" )
                    $query .= " $pack_item_key = 1,";
                else if( $pack_item_key == "trans_no" )
                    $query .= " $pack_item_key = '',";
                else if( $pack_item_key == "trans_date" )
                    $query .= " $pack_item_key = 0,";
                else if( $pack_item_key == "trans_corp" )
                    $query .= " $pack_item_key = '',";
                else if( $pack_item_key == "trans_key" )
                    $query .= " $pack_item_key = 0,";
                // 2014-06-02 장경희. 합포제외시 제외된 주문도 기존 주문과 보류설정 동일하게
//                else if( $pack_item_key == "hold" )
//                    $query .= " $pack_item_key = $remain_new_hold,";
                else if( $pack_item_key == "prepay_cnt" )
                    $query .= " $pack_item_key = 0,";
                else if( $pack_item_key == "prepay_price" )
                    $query .= " $pack_item_key = 0,";
                else if( $pack_item_key == "pack_lock" && $drop_pack_lock )
                    $query .= " $pack_item_key = 1,";
                else if( $pack_item_key == "pre_trans" && $drop_pre_trans )
                    $query .= " $pack_item_key = 1,";
                else
                {
                    $pack_item_val = str_replace("'", "\\'", $pack_item_val);
                    $query .= " $pack_item_key = '$pack_item_val',";
                }
            }
            if( $pack_val[work_type] == 1 )
                $query = substr($query,0,-1);
            else
                $query = substr($query,0,-1) . " where seq = $pack_val[seq]";
            mysql_query($query, $connect);

            if( $pack_val[work_type] == 1 )
            {
                $query = "select seq, order_id, recv_tel, recv_mobile, order_tel, order_mobile from orders where order_id='$pack_val[order_id]' order by seq desc limit 1";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);
                
                // 전화검색
                $this->inset_tel_info($data[seq], array($data[recv_tel],$data[recv_mobile],$data[order_tel],$data[order_mobile]));
                
                // 생성된 seq
                $new_drop_seq = $data[seq];
                // 분리, 생성된 seq
                $pack_no_drop_ready[] = $data[seq];

                // cs 남기기용 seq
                $drop_seq_arr[] = $data[seq];
                
                // 새 합포 번호가 필요한 경우( 전부 insert )
                if( $pack_no_drop == -1 )
                {
                    $pack_no_drop = $data[seq];
                
                    // 구한 seq로 다시 pack 설정
                    $query = "update orders set pack=$pack_no_drop where seq=$data[seq]";
                    mysql_query($query, $connect);
                }
            }            
            else
                $new_drop_seq = $pack_val[seq];

            // 사은품만 있는 주문 검색
            $gift_cnt = 0;
            $normal_cnt = 0;
            
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // insert | update
                if( $seq_val[work_type] == 1 )
                    $query = "insert order_products set ";
                else
                    $query = "update order_products set ";

                foreach( $seq_val as $seq_item_key => $seq_item_val )
                {
                    if( $seq_item_key == "seq"         ||
                        $seq_item_key == "work_type"   ||
                        $seq_item_key == "checked_qty" )  continue;
                    
                    if( $seq_item_key == "order_seq" )
                        $query .= " $seq_item_key = '$new_drop_seq',";
                    else
                    {
                        $seq_item_val = str_replace("'", "\\'", $seq_item_val);
                        $query .= " $seq_item_key = '$seq_item_val',";
                    }
                }

                // 사은품만 있는 주문 검색
                if( $seq_val["is_gift"] > 0 )
                    $gift_cnt++;
                else
                    $normal_cnt++;

                if( $seq_val[work_type] == 1 )
                    $query = substr($query,0,-1);
                else
                    $query = substr($query,0,-1) . " where seq=$seq_val[seq]";

                mysql_query($query, $connect);
            }

            // 사은품만 있는 주문 검색. order_id 뒤에 "_gift" 추가
            if( $pack_val[work_type] == 1 && $gift_cnt > 0 && $normal_cnt == 0 && substr($data[order_id],-5)<>"_gift" )
            {
                $query = "update orders set order_id = concat(order_id, '_gift') where seq=$new_drop_seq";
                mysql_query($query, $connect);
            }
        }
        
        //+++++++++++++++++++++++
        // orders remain update
        foreach( $pack_remain as $pack_key => $pack_val )
        {
            // orders update
            $query = "update orders set ";
            foreach( $pack_val as $pack_item_key => $pack_item_val )
            {
                if( $pack_item_key == "seq"            ||
                    $pack_item_key == "work_type"      ||
                    $pack_item_key == "checked_type"   ||
                    $pack_item_key == "order_products" )  continue;
                
                if( $pack_item_key == "pack" )
                    $query .= " $pack_item_key = '$pack_no_remain',";
                else if( $pack_item_key == "hold" && $remain_new_hold )
                    $query .= " $pack_item_key = '$remain_new_hold',";
                else if( $pack_item_key == "pack_lock" && $org_pack_lock )
                    $query .= " $pack_item_key = 1,";
                else if( $pack_item_key == "pre_trans" && $org_pre_trans )
                    $query .= " $pack_item_key = 1,";
                else
                {
                    $pack_item_val = str_replace("'", "\\'", $pack_item_val);
                    $query .= " $pack_item_key = '$pack_item_val',";
                }
            }
            $query = substr($query,0,-1) . " where seq=$pack_val[seq]";
            mysql_query($query, $connect);
         
            // 사은품만 남은 주문 확인
            $gift_cnt = 0;
            $normal_cnt = 0;
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // order_products update
                $query = "update order_products set ";
                foreach( $seq_val as $seq_item_key => $seq_item_val )
                {
                    if( $seq_item_key == "seq"         ||
                        $seq_item_key == "work_type"   ||
                        $seq_item_key == "checked_qty" )  continue;
                    
                    $seq_item_val = str_replace("'", "\\'", $seq_item_val);
                    $query .= " $seq_item_key = '$seq_item_val',";
                }
                $query = substr($query,0,-1) . " where seq=$seq_val[seq]";
                mysql_query($query, $connect);
                
                // 사은품만 남은 주문 확인
                if( $seq_val["is_gift"] > 0 )
                    $gift_cnt++;
                else
                    $normal_cnt++;
            }
            
            // 사은품만 남은 주문 검색. order_id 뒤에 "_gift" 추가
            if( $gift_cnt > 0 && $normal_cnt == 0 && substr($pack_val[order_id],-5)<>"_gift" )
            {
                $query = "update orders set order_id = concat(order_id, '_gift') where seq=$pack_val[seq]";
                mysql_query($query, $connect);
            }
        }
        
        // order_cs
        global $seq;
        
        $drop_seq_list = implode(",", $pack_no_drop_ready);
        $sys_content = "($old_seq_str) 에서 ($drop_seq_list) 합포제외" . ($stock_assign ? " (일괄합포제외)" : "") . ".<br>" ;
        
        $seq = $pack_remain[0][seq];
        
        
		//CS자동완료처리 [합포제외]
        if($_SESSION[CS_AUTO_COMPLETE3] == 1)            
        	$cs_result = 1;
        else
        	$cs_result = 0;
        //CS이력생성[기존주문]
        $this->csinsert8($pack_no_remain, 9, $content,$sys_content,'',$check_is_bck, $cs_result);

        // 남는 주문의 seq는 제외
        foreach( $pack_remain as $p_val )
        {
            $r = array_search($p_val[seq],$drop_seq_arr);
            if( $r !== false )
                array_splice($drop_seq_arr, $r,1);
        }
        $seq = $drop_seq_arr[0];
        //CS이력생성[갈라진 주문]
        $this->csinsert8($pack_no_drop, 9, $content,$sys_content,'',$check_is_bck, $cs_result);

        // BCK
        if( $check_is_bck )
            $this->save_to_bck("$old_seq_str,$drop_seq_list");

        // Lock End
        if( !$drop_pack_all )
        {
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }
        }
        
        $val['error'] = 0;

        if( !$drop_pack_all )
            echo json_encode( $val );
    }

    // 합포 금지 설정
    function set_lock_pack()
    {
        global $connect, $seq, $content;
        $val = array();

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // Lock Check
        $obj_lock = new class_lock(117);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 주문이 주문이 이미 배송된 경우
        if( $data_orders[status] == 8 )
            $val['error'] = 2;
        // 주문이 주문이 이미 합포 금지일 경우
        else if( $data_orders[pack_lock] )
            $val['error'] = 3;
        else
        {
            // 합포
            if( $data_orders[pack] > 0 )
                $query = "update orders set pack_lock=1 where pack=$data_orders[pack] and status < 8";
            else
                $query = "update orders set pack_lock=1 where seq=$data_orders[seq] and status < 8";
            
            if( mysql_query ( $query, $connect ) )
            {
                $val['error'] = 0;
                $this->csinsert3($data_orders[pack], 29, $content,'',$data_orders[is_bck]);

                if( $data_orders[is_bck] )
                {
                    $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
                    $this->save_to_bck($seq_list);
                }
            }
            else
                $val['error'] = 1;
        }

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 합포 금지 해제
    function cancel_lock_pack()
    {
        global $connect, $seq, $content;
        $val = array();

        // 정보 검색
        $data_orders = $this->get_orders($seq);

        // 주문이 주문이 이미 배송된 경우
        if( $data_orders[status] == 8 )
            $val['error'] = 2;
        // 주문이 주문이 합포 금지가 아닌 경우
        else if( !$data_orders[pack_lock] )
            $val['error'] = 3;
/*
        // 복구된 주문인 경우
        else if( $data_orders[is_bck] )
            $val['error'] = 4;
*/
        else
        {
            // 합포
            if( $data_orders[pack] > 0 )
                $query = "update orders set pack_lock=0 where pack=$data_orders[pack] and status < 8";
            else
                $query = "update orders set pack_lock=0 where seq=$data_orders[seq] and status < 8";
            
            if( mysql_query ( $query, $connect ) )
            {
                $val['error'] = 0;
                $this->csinsert3($data_orders[pack], 30, $content, '',$data_orders[is_bck]);

                if( $data_orders[is_bck] )
                {
                    $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
                    $this->save_to_bck($seq_list);
                }
            }
            else
                $val['error'] = 1;
        }
        echo json_encode( $val );
    }

    // 전체 취소
    function cancel_pack()
    {
        global $connect, $seq, $prdSeq, $cs_reason, $retstockin, $content;
        global $restockin,$restockin_bad,$return_comp,$site_p,$envelop_p,$account_p,$notget_p,$trans_corp,$trans_no,$trans_who, $trans_price, $do_complete, $memo;

        $val = array();
        
        $transaction = $this->begin("전체취소");

        // 주문 정보
        $data_orders = $this->get_orders($seq);
        $seq_str = $this->get_seq_list2($seq, $data_orders[pack]);
        
        // Lock Check
        $obj_lock = new class_lock(105);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 상태 구하기
        if( $data_orders[pack] > 0 )
        {
            // 합포인 경우 상태가 젤 높은 주문의 상태
            $query = "select * from orders where pack=$data_orders[pack] order by status desc";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $sts = $data[status];
        }
        else
            $sts = $data_orders[status];

        // CTI cancel_qty
        if( $_REQUEST[callid] )
        {
            $query_cti = "select sum(qty) sum_qty 
                            from order_products 
                           where order_seq in ($seq_str) and
                                 order_cs not in (1,2,3,4)";
            $result_cti = mysql_query($query_cti, $connect);
            $data_cti = mysql_fetch_assoc($result_cti);
            
            $query_cti = "update cti_call_history set cancel_qty = cancel_qty + $data_cti[sum_qty] where callid='$_REQUEST[callid]'";
            mysql_query( $query_cti, $connect );
        }
     
        // 배송전 전체취소
        if( $sts <  8 )
        {   
            // orders
            $query = "update orders set order_cs=1 where seq in ($seq_str)";
            $result1 = mysql_query($query, $connect);
            
            // order_products - 취소상태인 상품
            $query = "update order_products set order_cs=1 where order_seq in ($seq_str) and order_cs in (1,2,3,4)";
            $result3 = mysql_query($query, $connect);
            
            // order_products - 취소상태가 아닌 상품
            $query = "update order_products set order_cs=1, refund_price=shop_price, cancel_date=now() where order_seq in ($seq_str) and order_cs not in (1,2,3,4)";
            $result2 = mysql_query($query, $connect);
            
            // 자동보류
            if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
                 ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
                $this->set_cs_hold( 4, $seq_str );  //hold = 4
        }
        else
        {
        	// 배송후 취소 box4u일경우 정산리스트에서 제거
        	if(_DOMAIN_ == 'box4u' || _DOMAIN_ == 'ezadmin' )
        	{        		
        		// box4u_func.inc include $seq_str
        		trans_cancel($seq_str,"");
			}
			
            // 배송후 취소. 자동반품입고
            if( $restockin )
            {
                $obj = new class_stock();
                $query_s = "select order_seq, product_id, qty from order_products where order_seq in ($seq_str) and order_cs not in (1,2,3,4)";
                $result_s = mysql_query($query_s, $connect);
                while( $data_s = mysql_fetch_assoc($result_s) )
                {
                    $obj->set_stock( array( type       => 'retin',
                                            product_id => $data_s[product_id], 
                                            bad        => $restockin_bad,
                                            location   => 'Def',
                                            qty        => $data_s[qty],
                                            memo       => 'CS 자동반품입고',
                                            order_seq  => $data_s[order_seq] ) );
                }
            }
            
            //*********************************
            // 배송후 전체 취소, 반품정보
            //*********************************
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                $site_p    = (int)$site_p   ;   
                $envelop_p = (int)$envelop_p;
                $account_p = (int)$account_p;
                $notget_p  = (int)$notget_p ;
                $restockin = (int)$restockin;
                
                if( $restockin > 0 )
                    $restockin_val = 1 + $restockin_bad;
                else
                    $restockin_val = 0;
                
                // 전체상품 남기기
                if( $_SESSION[USE_RETURN_PRD_ALL] )
                {
                    $query_p = "select seq from order_products where order_seq in ($seq_str) and order_cs not in (1,2,3,4)";
debug("전체상품 남기기 : " . $query_p);
                }
                else
                    $query_p = "select seq from order_products where seq = $prdSeq and order_cs not in (1,2,3,4)";

                // org prdSeq
                $org_prdSeq = $prdSeq;

                // org price
                $org_site_p      = $site_p     ;
                $org_envelop_p   = $envelop_p  ;
                $org_account_p   = $account_p  ;
                $org_notget_p    = $notget_p   ;
                $org_trans_price = $trans_price;
                

                $result_p = mysql_query($query_p, $connect);
                while( $data_p = mysql_fetch_assoc($result_p) )
                {
                    $prdSeq = $data_p[seq];

                    if( $org_prdSeq != $prdSeq )
                    {
                        $site_p      = 0;
                        $envelop_p   = 0;
                        $account_p   = 0;
                        $notget_p    = 0;
                        $trans_price = 0;
                    }
                    else
                    {
                        $site_p      = $org_site_p     ;
                        $envelop_p   = $org_envelop_p  ;
                        $account_p   = $org_account_p  ;
                        $notget_p    = $org_notget_p   ;
                        $trans_price = $org_trans_price;
                    }
                    
                    // 반품정보 있는지 확인
                    $query = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
                    $result = mysql_query($query, $connect);
                    if( mysql_num_rows($result) )
                    {
                        $data = mysql_fetch_assoc($result);
                        
                        if( $org_prdSeq != $prdSeq )
                        {
                            $data[expect_site]    = 0;
                            $data[expect_envelop] = 0;
                            $data[expect_account] = 0;
                        }

                        // 교환인 경우
                        if( $data[return_type] == 2 )
                        {
                            // 이전 반품정보 삭제처리
                            $query_return = "update return_money 
                                                set is_delete     = 1,
                                                    delete_date   = now(),
                                                    delete_worker = '$_SESSION[LOGIN_NAME]'";
                            $query_return .= " where seq = $data[seq]";
                            mysql_query($query_return, $connect);
        
                            // 로그 남기기
                            $query_return_log = "insert return_money_log
                                                    set seq                = '$data[seq]',
                                                        log_type           = 'delete',
                                                        log_contents       = '취소처리 삭제',
                                                        log_date           = now(),
                                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
                            mysql_query($query_return_log, $connect);
                            
                            // 반품정보 입력
                            $query_ret_insert = "insert return_money 
                                                    set order_seq          = '$data[order_seq]',         
                                                        order_products_seq = '$data[order_products_seq]',
                                                        collect_date       = '$data[collect_date]',      
                                                        trans_date         = '$data[trans_date]',        
                                                        shop_id            = '$data[shop_id]',           
                                                        supply_id          = '$data[supply_id]',         
                                                        recv_name          = '$data[recv_name]',         
                                                        shop_product_id    = '$data[shop_product_id]',   
                                                        shop_product_name  = '$data[shop_product_name]', 
                                                        shop_options       = '$data[shop_options]',      
                                                        ez_product_id      = '$data[ez_product_id]',     
                                                        ez_product_name    = '$data[ez_product_name]',   
                                                        ez_options         = '$data[ez_options]',        
                                                        qty                = '$data[qty]',               
                                                        org_trans_no       = '$data[org_trans_no]',      
                                                        return_type        = 0,       
                                                        cancel_type        = '$cs_reason',       
                                                        change_type        = '',       
                                                        expect_site        = '$data[expect_site]',       
                                                        expect_envelop     = '$data[expect_envelop]',    
                                                        expect_account     = '$data[expect_account]',    
                                                        expect_trans_who   = '$data[expect_trans_who]',  
                                                        memo               = '$memo',
                                                        return_site        = $site_p,      
                                                        return_envelop     = $envelop_p,   
                                                        return_account     = $account_p,   
                                                        return_notget      = $notget_p,    
                                                        return_trans_who   = '$trans_who', 
                                                        return_trans_corp  = '$trans_corp',
                                                        return_trans_no    = '$trans_no',  
                                                        restockin_auto     = '$restockin_val', 
                                                        is_expect          = '$data[is_expect]',         
                                                        expect_date        = '$data[expect_date]',       
                                                        expect_worker      = '$data[expect_worker]',     
                                                        is_return          = 1,         
                                                        return_date        = now(),       
                                                        return_worker      = '$_SESSION[LOGIN_NAME]',
                                                        return_trans_price = '$trans_price'";
                            // 반품택배비 정산완료
                            if( $return_comp )
                            {
                                $query_ret_insert .= " ,is_complete       = 1,
                                                        complete_date     = now(),
                                                        complete_worker   = '$_SESSION[LOGIN_NAME]'";
                            }
            
                            mysql_query($query_ret_insert, $connect);
            
                            // 로그 
                            $query = "select max(seq) max_seq
                                        from return_money
                                       where is_return = 1 and  
                                             is_delete = 0 and
                                             return_worker = '$_SESSION[LOGIN_NAME]'";
                            $result = mysql_query($query, $connect);
                            $data = mysql_fetch_assoc($result);
            
                            $log_seq = $data[max_seq];
                        }
                        // 단순 반품예정인 경우
                        else
                        {
                            $query_return = "update return_money 
                                                set return_type       = 0,
                                                    cancel_type       = '$cs_reason',
                                                    memo              = '$memo',
                                                    return_site       = '$site_p',
                                                    return_envelop    = '$envelop_p',
                                                    return_account    = '$account_p',
                                                    return_notget     = '$notget_p',
                                                    return_trans_corp = '$trans_corp',
                                                    return_trans_no   = '$trans_no',
                                                    return_trans_who  = '$trans_who',
                                                    restockin_auto    = '$restockin_val',
                                                    is_return         = 1,
                                                    return_date       = now(),
                                                    return_worker     = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price= '$trans_price'";
            
                            // 반품택배비 정산완료
                            if( $return_comp )
                            {
                                $query_return .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                            }
                            
                            $query_return .= " where seq = $data[seq]";
                            mysql_query($query_return, $connect);
                            
                            $log_seq = $data[seq];
                        }
                    }
                    else
                    {
                        // 반품정보
                        $query_ret_order = "select a.seq             a_seq,
                                                   a.collect_date    a_collect_date,
                                                   a.collect_time    a_collect_time,
                                                   a.trans_date_pos  a_trans_date_pos,
                                                   a.shop_id         a_shop_id,
                                                   a.recv_name       a_recv_name,
                                                   a.shop_product_id a_shop_product_id,
                                                   a.product_name    a_product_name,
                                                   a.options         a_options,
                                                   a.trans_no        a_trans_no,
                                                   b.seq             b_seq,
                                                   b.supply_id       b_supply_id,
                                                   b.qty             b_qty,
                                                   c.product_id      c_product_id,
                                                   c.name            c_name,
                                                   c.options         c_options
                                              from orders a,
                                                   order_products b,
                                                   products c
                                             where a.seq = b.order_seq and
                                                   b.product_id = c.product_id and
                                                   b.seq = $prdSeq";
                        $result_ret_order = mysql_query($query_ret_order, $connect);
                        $data_ret_order = mysql_fetch_assoc($result_ret_order);
        
                        // 반품정보 입력
                        $query_ret_insert = "insert return_money 
                                                set order_seq          = '$data_ret_order[a_seq]',
                                                    order_products_seq = '$data_ret_order[b_seq]',
                                                    collect_date       = '$data_ret_order[a_collect_date] $data_ret_order[a_collect_time]',
                                                    trans_date         = '$data_ret_order[a_trans_date_pos]',
                                                    shop_id            = '$data_ret_order[a_shop_id]',
                                                    supply_id          = '$data_ret_order[b_supply_id]',
                                                    shop_product_id    = '$data_ret_order[a_shop_product_id]',
                                                    ez_product_id      = '$data_ret_order[c_product_id]',
                                                    recv_name          = '" . addslashes($data_ret_order[a_recv_name]   ) . "',
                                                    shop_product_name  = '" . addslashes($data_ret_order[a_product_name]) . "',
                                                    shop_options       = '" . addslashes($data_ret_order[a_options]     ) . "',
                                                    ez_product_name    = '" . addslashes($data_ret_order[c_name]        ) . "',
                                                    ez_options         = '" . addslashes($data_ret_order[c_options]     ) . "',
                                                    qty                = '$data_ret_order[b_qty]',
                                                    org_trans_no       = '$data_ret_order[a_trans_no]',
                                                    return_type        = 0,
                                                    cancel_type        = '$cs_reason',
                                                    change_type        = '',
                                                    memo               = '$memo',
                                                    return_site        = $site_p,
                                                    return_envelop     = $envelop_p,
                                                    return_account     = $account_p,
                                                    return_notget      = $notget_p,
                                                    return_trans_who   = '$trans_who',
                                                    return_trans_corp  = '$trans_corp',
                                                    return_trans_no    = '$trans_no',
                                                    restockin_auto     = '$restockin_val',
                                                    is_return          = 1,
                                                    return_date        = now(),
                                                    return_worker      = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price = '$trans_price'";
debug("반품정보 입력 : " . $query_ret_insert);
    
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_ret_insert .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
        
                        mysql_query($query_ret_insert, $connect);
        
                        // 로그 
                        $query = "select seq
                                    from return_money
                                   where is_expect = 0 and
                                         is_return = 1 and  
                                         is_delete = 0 and
                                         return_worker = '$_SESSION[LOGIN_NAME]'
                                   order by seq desc limit 1";
                        $result = mysql_query($query, $connect);
                        $data = mysql_fetch_assoc($result);
        
                        $log_seq = $data[seq];
                    }
                }
                
                // 반품도착 로그
                $log_contents = "";
                if( $site_p )
                    $log_contents .= "사이트결제 : " . number_format($site_p) . " 원, ";
                if( $envelop_p )
                    $log_contents .= "동봉 : " . number_format($envelop_p) . " 원, ";
                if( $account_p )
                    $log_contents .= "계좌 : " . number_format($account_p) . " 원, ";
                if( $notget_p )
                    $log_contents .= "미수 : " . number_format($notget_p) . " 원, ";
                if( $trans_no )
                {
                    $log_contents .= "택배사 : " . $this->get_trans_corp_name($trans_corp) . ", ";
                    $log_contents .= "송장번호 : $trans_no, ";
                    $log_contents .= "선착불 : " . ($trans_who ? "착불" : "선불") . ", ";
                }
                if( $restockin_val == 1 )
                    $log_contents .= "자동반품입고 : 설정-정상";
                else if( $restockin_val == 2 )
                    $log_contents .= "자동반품입고 : 설정-$_SESSION[EXTRA_STOCK_TYPE]";

                $query_return_log = "insert return_money_log
                                        set seq                = '$log_seq',
                                            log_type           = 'return',
                                            log_contents       = '$log_contents',
                                            log_date           = now(),
                                            log_worker         = '$_SESSION[LOGIN_NAME]'";
                mysql_query($query_return_log, $connect);
    
                // 반품택배비 정산완료 로그
                if( $return_comp )
                {
                    $query_return_log = "insert return_money_log
                                            set seq                = '$log_seq',
                                                log_type           = 'complete',
                                                log_date           = now(),
                                                log_worker         = '$_SESSION[LOGIN_NAME]'";
                    mysql_query($query_return_log, $connect);
                }
            //=========================
            // 반품택배비 완료
            //=========================
            }

            // 반품확인시 자동 sms 전송
            $this->send_return_sms($seq, $data_orders);
        
            // orders
            $query = "update orders set order_cs=3 where seq in ($seq_str)";
            $result1 = mysql_query($query, $connect);

            // order_products
            $query = "update order_products 
                         set order_cs = 3, 
                             refund_price    = shop_price    , 
                             cancel_date     = now()
                       where order_seq in ($seq_str) and 
                             order_cs not in (1,2,3,4)";
            $result2 = mysql_query($query, $connect);
        }

        if( $result1 && $result2 )
        {
            $val['error'] = 0;
            if( $sts == 8 )
            {
                if( $_SESSION[USE_RETURN_MONEY] )
                {
                    if( $return_comp )
                        $sys_content = $log_contents . ", 반품택배 정산 : 완료<br>";
                    else
                        $sys_content = $log_contents . "<br>";
                }
            }
            $this->csinsert8($data[pack], 10, $content, $sys_content, $cs_reason,  $data_orders[is_bck], $do_complete);
        }
        else
            $val['error'] = 1;

        // BCK
        if( $data_orders[is_bck] )
        {
            $this->save_to_bck($seq_str);
        }

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }
    
    // 전체 정상 복귀
    function normal_pack()
    {
        global $connect, $seq, $content, $cancel_re, $cancel_re_bad;

        $transaction = $this->begin("전체정상복귀");

        // orders 데이터 정보
        $data_orders = $this->get_orders($seq);
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
        
        // 배송전
        if( $data_orders[status] < 8 )
        {
            // 합포이고 배송후 주문 있는지 확인 
            // => 현재 선택된 주문은 배송전(취소)이지만, 합포 주문중에 배송후 주문이 있는경우, 정상복귀 불가.
            if( $data_orders[pack] > 0 )
            {
                $query_trans = "select seq from orders where status=8 and seq in ($seq_list)";
                $result_trans = mysql_query($query_trans, $connect);
                if( mysql_num_rows($result_trans) )
                {
                    $val['error'] = 4;
                    echo json_encode( $val );
                    return;
                }
            }
            
            // 배송후 주문이 없는 경우, 취소건만 복귀.
            $query = "select seq as prd_seq from order_products where order_seq in ($seq_list) and order_cs in (1,2,3,4)";
        }
        // 배송후
        else
        {
            // 배송후 주문에 대해서, 배송후 취소건만 복귀.
            $query = "select b.seq as prd_seq
                        from orders a, 
                             order_products b 
                       where a.seq = b.order_seq and
                             a.status = 8 and 
                             b.order_seq in ($seq_list) and 
                             b.order_cs in (3,4)";
			
			if(_DOMAIN_ == "box4u" || _DOMAIN_  == "ezadmin")
            {
            	//전체 정상복귀
            	//box4u_func.inc
            	trans_cancel_return($seq_list,"normal_pack");
            }
        }
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) == 0 )
        {
            $val['error'] = 3;
            echo json_encode( $val );
            return;
        }
        else
        {
            $prd_seq_list = '';
            while( $data = mysql_fetch_assoc($result) )
                $prd_seq_list .= ($prd_seq_list ? "," : "") . $data[prd_seq];
        }

        // Lock Check
        $obj_lock = new class_lock(106);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // CTI normal_qty
        if( $_REQUEST[callid] )
        {
            $query_cti = "select sum(qty) sum_qty 
                            from order_products 
                           where order_seq in ($seq_list) and
                                 order_cs in (1,2,3,4)";
            $result_cti = mysql_query($query_cti, $connect);
            $data_cti = mysql_fetch_assoc($result_cti);
            
            $query_cti = "update cti_call_history set cancel_qty = cancel_qty - $data_cti[sum_qty] where callid='$_REQUEST[callid]'";
            mysql_query( $query_cti, $connect );
        }

        // 취소 아닌 건이 한개 이상 있을 경우만 보류설정
        $hold_enable = 0;
        $query_check = "select seq from order_products where order_seq in ($seq_list) and order_cs not in (1,2,3,4)";
        $result_check = mysql_query($query_check, $connect);
        if( mysql_num_rows($result_check) > 0 )
            $hold_enable = 1;
        
        // order_products 정상 복귀
        $query = "update order_products set order_cs=0, refund_price=0 where seq in ($prd_seq_list)";
        if( mysql_query($query, $connect) )
        {
            $seq_arr = explode( ',', $seq_list );
            foreach( $seq_arr as $seq_each )
                $this->modify_orders_cs( $seq_each );
            
            if( $cancel_re )
                $sys_content = "반품입고 취소 : 설정-" . ($cancel_re_bad ? "불량" : "정상") ;
            
            $val['error'] = 0;
            $this->csinsert8($data_orders[pack], 12, $content,$sys_content, '', $data_orders[is_bck]);

            // 자동보류
            if( $hold_enable )
            {
                if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
                     ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
                    $this->set_cs_hold( 6, $seq_list, true );
            }
        }
        else
            $val['error'] = 1;

        // 배송후 반품정보 되돌리기 & 반품입고 자동취소
        if( $data_orders[status] == 8 )
        {
            foreach( explode(",", $prd_seq_list) as $prd_seq )
            {
                $this->cancel_return($prd_seq, 0);
    
                // 반품입고 취소
                if( $cancel_re )
                {
                    $obj = new class_stock();
                    $query_s = "select order_seq, product_id, qty from order_products where seq=$prd_seq";
                    $result_s = mysql_query($query_s, $connect);
                    $data_s = mysql_fetch_assoc($result_s);
                    $obj->set_stock( array( type       => 'retin',
                                            product_id => $data_s[product_id], 
                                            bad        => $cancel_re_bad,
                                            location   => 'Def',
                                            qty        => $data_s[qty] * -1,
                                            memo       => 'CS 반품입고취소',
                                            order_seq  => $data_s[order_seq] ) );
                }                    
            }
        }

        // BCK
        if( $data_orders[is_bck] )
        {
            $this->save_to_bck($seq_list);
        }

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 주문 복사 - 옵션/상품 매칭발주
    function copy_order()
    {
        global $connect, $seq, $shop_id, $product_id, $options, $qty, $extra_money, $content;

        // 복사권한 확인
        $arr_auth = split(",", $_SESSION[AUTH]);
        if( array_search("E4", $arr_auth) !== false )
        {
            $val['error'] = 6;
            echo json_encode( $val );
            return;
        }

        $transaction = $this->begin("주문복사");
        
        // orders 복사
        $data = $this->get_orders($seq);

        // Lock Check
        $obj_lock = new class_lock(107, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 배송비
        $query_trans_fee = "select trans_fee from products where product_id='$product_id'";        
        $result_trans_fee = mysql_query($query_trans_fee, $connect);
        $data_trans_fee = mysql_fetch_assoc($result_trans_fee);
        
        $ins_query = "insert into orders set ";
        $i = 0;
        foreach ( $data as $key=>$value)
        {
            $i++;
            if ( $key == "seq" 
              || $key == "pack" 
              || $key == "trans_no" 
              || $key == "trans_date" 
              || $key == "trans_date_pos" 
              || $key == "refund_cs_date" 
              || $key == "refund_date" 
              || $key == "hold" 
              || $key == "part_seq" 
              || $key == "trans_corp" ) continue;

            if ( $key == "shop_id"      ) $value = $shop_id;
            if ( $key == "status"       ) $value = 1;
            if ( $key == "order_cs"     ) $value = 0;
            if ( $key == "order_subid"  ) $value = $new_subid;
            if ( $key == "collect_date" ) $value = date('Y-m-d');
            if ( $key == "qty"          ) $value = $qty;
            if ( $key == "trans_fee"    ) $value = $data_trans_fee[trans_fee];

            if ( $key == "amount"       ) $value = 0;
            if ( $key == "supply_price" ) $value = 0;
            if ( $key == "prepay_cnt"   ) $value = 0;
            if ( $key == "prepay_price" ) $value = 0;
            if ( $key == "extra_money"  ) $value = $extra_money;
            if ( $key == "c_seq"        ) $value = 0;
            if ( $key == "copy_seq"     ) $value = ($data[c_seq] ? $data[c_seq] : ($data[copy_seq] ? $data[copy_seq] : $data[seq]));

            $ins_query .= "$key='" . addslashes($value) . "'";
            if ( $i < count( $data ) ) $ins_query .= ",";
        }
        $result1 = mysql_query($ins_query, $connect);
        
        // 복사 생성된 주문번호 가져온다.
        $query = "select * from orders where status=1 and shop_id=$shop_id order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $copy_seq = $data[seq];

        $copy_order_recv_tel     = $data[recv_tel];
        $copy_order_recv_mobile  = $data[recv_mobile];
        $copy_order_order_tel    = $data[order_tel];
        $copy_order_order_mobile = $data[order_mobile];

        // 상품정보
        $query = "select * from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        // 삭제된 상품
        if( $data[is_delete] == 1 )
        {
            $val['error'] = 7;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        $supply_id = $data[supply_code];
        $shop_price = $data[shop_price];
        $org_price = $data[org_price];
        
        // order_products 복사
        $query = "select * from order_products where order_seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $ins_query = "insert into order_products set ";
        $i = 0;
        foreach ( $data as $key=>$value)
        {
            $i++;
            if ( $key == "seq" 
              || $key == "cancel_date" 
              || $key == "change_date" 
              || $key == "refund_price" ) continue;

            if ( $key == "order_seq"    ) $value=$copy_seq;
            if ( $key == "product_id"   ) $value=$product_id;
            if ( $key == "shop_id"      ) $value=$shop_id;
            if ( $key == "order_cs"     ) $value=0;
            if ( $key == "match_date"   ) $value=date('Y-m-d');
            if ( $key == "match_type"   ) $value=5;
            if ( $key == "match_worker" ) $value=$_SESSION[LOGIN_NAME];
            if ( $key == "supply_id"    ) $value=$supply_id;
            if ( $key == "shop_price"   ) $value=$extra_money;
            if ( $key == "org_price"    ) $value=$org_price * $qty;
            if ( $key == "qty"          ) $value=$qty;
            if ( $key == "extra_money"  ) $value=$extra_money;
            if ( $key == "c_seq"        ) $value = 0;
            if ( $key == "copy_seq"     ) $value = ($data[c_seq] ? $data[c_seq] : ($data[copy_seq] ? $data[copy_seq] : $data[seq]));

            // 상품매칭 옵션
            if ( $key == "shop_options" && $_SESSION[STOCK_MANAGE_USE] == 2 )
                $value = $options;
            
            $ins_query .= "$key='" . addslashes($value) . "'";
            if ( $i < count( $data ) ) $ins_query .= ",";
        }
        $result2 = mysql_query($ins_query, $connect);

        if( $result1 && $result2 )
        {
            $seq = $copy_seq;

            $val['error'] = 0;
            $this->csinsert3(0, 14, $content);
        }
        else
            $val['error'] = 1;

        // 전화검색
        $this->inset_tel_info($copy_seq, array($copy_order_recv_tel,$copy_order_recv_mobile,$copy_order_order_tel,$copy_order_order_mobile));

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 주문 복사 - 일반발주
    function copy_order2()
    {
        global $connect, $seq, $shop_id, $name, $options, $qty, $extra_money, $content;

        // 복사권한 확인
        $arr_auth = split(",", $_SESSION[AUTH]);
        if( array_search("E4", $arr_auth) !== false )
        {
            $val['error'] = 6;
            echo json_encode( $val );
            return;
        }

        $transaction = $this->begin("주문복사");
        
        // orders 복사
        $data = $this->get_orders($seq);

        // Lock Check
        $obj_lock = new class_lock(107, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $ins_query = "insert into orders set ";
        $i = 0;
        foreach ( $data as $key=>$value)
        {
            $i++;
            if ( $key == "seq" 
              || $key == "pack" 
              || $key == "trans_no" 
              || $key == "trans_date" 
              || $key == "trans_date_pos" 
              || $key == "refund_cs_date" 
              || $key == "refund_date" 
              || $key == "hold" 
              || $key == "trans_corp" ) continue;

            if ( $key == "shop_id"      ) $value = $shop_id;
            if ( $key == "status"       ) $value = 1;
            if ( $key == "order_cs"     ) $value = 0;
            if ( $key == "order_subid"  ) $value = $new_subid;
            if ( $key == "collect_date" ) $value = date('Y-m-d');
            if ( $key == "qty"          ) $value = $qty;
            if ( $key == "product_name" ) $value = $name;
            if ( $key == "options"      ) $value = $options;

            if ( $key == "amount"       ) $value = 0;
            if ( $key == "supply_price" ) $value = 0;
            if ( $key == "prepay_cnt"   ) $value = 0;
            if ( $key == "prepay_price" ) $value = 0;
            if ( $key == "extra_money"  ) $value = $extra_money;
            if ( $key == "c_seq"        ) $value = 0;
            if ( $key == "copy_seq"     ) $value = ($data[c_seq] ? $data[c_seq] : ($data[copy_seq] ? $data[copy_seq] : $data[seq]));

            $ins_query .= "$key='$value'";
            if ( $i < count( $data ) ) $ins_query .= ",";
        }

        $result1 = mysql_query($ins_query, $connect);
        
        // 복사 생성된 주문번호 가져온다.
        $query = "select * from orders where status=1 and shop_id=$shop_id order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $copy_seq = $data[seq];
        
        $copy_order_recv_tel     = $data[recv_tel];
        $copy_order_recv_mobile  = $data[recv_mobile];
        $copy_order_order_tel    = $data[order_tel];
        $copy_order_order_mobile = $data[order_mobile];

        // order_products 복사
        $query = "select * from order_products where order_seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $ins_query = "insert into order_products set ";
        $i = 0;
        foreach ( $data as $key=>$value)
        {
            $i++;
            if ( $key == "seq" 
              || $key == "cancel_date" 
              || $key == "change_date" 
              || $key == "refund_price" ) continue;

            if ( $key == "order_seq"  ) $value=$copy_seq;
            if ( $key == "shop_id"    ) $value=$shop_id;
            if ( $key == "shop_options") $value=$options;
            if ( $key == "order_cs"   ) $value=0;
            if ( $key == "match_date" ) $value=date('Y-m-d');
            if ( $key == "shop_price" ) $value=$extra_money;
            if ( $key == "qty"        ) $value=$qty;
            if ( $key == "extra_money") $value=$extra_money;

            $ins_query .= "$key='$value'";
            if ( $i < count( $data ) ) $ins_query .= ",";
        }
        $result2 = mysql_query($ins_query, $connect);

        if( $result1 && $result2 )
        {
            $seq = $copy_seq;

            $val['error'] = 0;
            $this->csinsert3(0, 14, $content);
        }
        else
            $val['error'] = 1;

        // 전화검색
        $this->inset_tel_info($copy_seq, array($copy_order_recv_tel,$copy_order_recv_mobile,$copy_order_order_tel,$copy_order_order_mobile));

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 신규 주문 생성
    function create_new_order()
    {
        global $connect, $seq, $shop_id, $supply_id, $product_id, $product_name, $options, $org_price, $price,
               $qty, $trans_who, $trans_price, $order_name, $order_tel, $order_mobile, $order_zip, $order_address, $recv_name, 
               $recv_tel, $recv_mobile, $recv_zip, $recv_address, $memo, $content,
               $recv_zip1, $recv_zip2, $recv_address1, $recv_address2;

        $transaction = $this->begin("주문생성");
        
        // 생성권한 확인
        $arr_auth = split(",", $_SESSION[AUTH]);
        if( array_search("E2", $arr_auth) !== false )
        {
            $val['error'] = 6;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(108);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 일반발주일 경우 임의 상품코드 
        if( $_SESSION[BASIC_VERSION] && $_SESSION[STOCK_MANAGE_USE] == 0 )
        {
            $query = "select supply_code, product_id from products limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $supply_id  = $data[supply_code];
            $product_id = $data[product_id];
        }
        
        // 배송비
        $query_trans_fee = "select trans_fee, org_price from products where product_id='$product_id'";
        $result_trans_fee = mysql_query($query_trans_fee, $connect);
        $data_trans_fee = mysql_fetch_assoc($result_trans_fee);
        
        $query = "insert orders
                     set order_id        = -1,
                         shop_id         = '$shop_id',
                         shop_product_id = '$product_id',
                         product_name    = '$product_name',
                         options         = '$options',
                         qty             = '$qty',
                         amount          = 0,
                         supply_price    = 0,
                         extra_money     = '$price',
                         trans_who       = '$trans_who',
                         status          = 1,
                         order_cs        = 0,
                         order_date      = now(),
                         order_time      = now(),
                         collect_date    = now(),
                         collect_time    = now(),
                         order_name      = '$order_name',
                         order_tel       = '$order_tel',
                         order_mobile    = '$order_mobile',
                         order_zip       = '$order_zip',
                         order_address   = '$order_address',
                         recv_name       = '$recv_name',
                         recv_tel        = '$recv_tel',
                         recv_mobile     = '$recv_mobile',
                         recv_zip        = '$recv_zip',
                         recv_address    = '$recv_address',
                         memo            = '$memo',
                         order_status    = 50,
                         trans_fee       = '$data_trans_fee[trans_fee]'";
if( _DOMAIN_ == 'jbstar' )
    $query .= ", code30='" . class_shop::get_shop_name($shop_id) . "'";

debug( "신규주문생성:".$query);                         
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;
            $val['query'] = $query;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        // order_id에 seq를 넣는다.
        $query_seq = "select seq from orders where status=1 and order_id=-1 order by seq desc limit 1";
        $result_seq = mysql_query($query_seq, $connect);
        $data_seq = mysql_fetch_assoc($result_seq);

        $query = "update orders set order_id=$data_seq[seq] where seq=$data_seq[seq]";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

		
		$_org_price = $data_trans_fee[org_price]*$qty;
        // order_products에 추가하기
        $query = "insert order_products
                     set order_seq       = '$data_seq[seq]',
                         product_id      = '$product_id',
                         qty             = '$qty',
                         order_cs        = 0,
                         extra_money     = '$price',
                         shop_id         = '$shop_id',
                         shop_options    = '$options',
                         match_date      = now(),
                         status          = 1,
                         supply_id       = '$supply_id',
                         shop_price      = '$price',
                         org_price		 = '$_org_price',
                         match_type      = 5,
                         match_worker    = '$_SESSION[LOGIN_NAME]'";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        // 전화번호 검색 추가
        $this->inset_tel_info($data_seq[seq], array($order_tel,$order_mobile,$recv_tel,$recv_mobile));
        
        // 최근배송정보 추가하기
        $query = "insert new_order_recent
                     set recv_name = '$recv_name',
                         recv_tel = '$recv_tel',
                         recv_mobile = '$recv_mobile',
                         recv_zip1 = '$recv_zip1',
                         recv_zip2 = '$recv_zip2',
                         recv_address1 = '$recv_address1',
                         recv_address2 = '$recv_address2'";
        mysql_query($query, $connect);
        
        $seq = $data_seq[seq];
        $val['error'] = 0;
        $this->csinsert3(0, 15, $content);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }                    
    
    // 주문삭제
    function delete_order()
    {
        global $connect, $seq, $content;
        
        // 삭제권한 확인
        $arr_auth = split(",", $_SESSION[AUTH]);
        if( array_search("E1", $arr_auth) !== false )
        {
            $val['error'] = 6;
            echo json_encode( $val );
            return;
        }

        $transaction = $this->begin("주문삭제");
        debug( "주문삭제 : " . $seq );

        // pack인지 검사
        $data = $this->get_orders($seq);
        
        // 배송된 주문
        if( $data[status] == 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(109);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $pack = $data[pack];

        // 합포 기준 주문일 경우
        if( $data[seq] == $data[pack] )
            $pack_order = true;
        else
            $pack_order = false;

        // 합포 주문 수
        if( $pack > 0 )
        {
            $query = "select seq from orders where pack = $pack";
            $result = mysql_query($query, $connect);
            $pack_cnt = mysql_num_rows($result);
        }
        
        // orders_del로 이동
        $query = "insert orders_del select * from orders where seq=$seq";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // orders에서 지우기
        $query = "delete from orders where seq=$seq";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 3;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // order_products_del로 이동
        $query = "insert order_products_del select * from order_products where order_seq=$seq";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 4;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // order_products에서 지우기
        $query = "delete from order_products where order_seq=$seq";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 5;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        // 남은 주문이 더이상 합포가 아닐 경우
        if( $pack > 0 && $pack_cnt == 2 )
        {
            $query = "update orders set pack=0 where pack=$pack";
            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 6;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
        }

        // 세주문 이상 합포이고, 합포 기준 주문이었을 경우 변경하기
        if( $pack > 0 && $pack_cnt > 2 && $pack_order )
        {
            // 새 pack 번호 구하기
            $query = "select seq from orders where pack=$pack order by seq limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 새 pack 번호로 변경하기
            $query = "update orders set pack=$data[seq] where pack=$pack";
            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 7;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
        }

        // 삭제 완료 후, 그리드 리프레시 옵션
        if( $pack > 0 && !$pack_order )
            $val['is_pack'] = 1;
        else
            $val['is_pack'] = 0;

        $val['error'] = 0;
        $this->csinsert3(0, 33, $content);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }
                         
    // 합포삭제
    function delete_pack()
    {
        global $connect, $seq, $content;
        
        $transaction = $this->begin("합포삭제");
        debug( "합포삭제 : " . $seq );

        // 배송된 주문
        $data = $this->get_orders($seq);
        if( $data[status] == 8 )
        {
            $val['error'] = 3;
            echo json_encode( $val );
            return;
        }

        $pack=0;
        $seq_list = $this->get_seq_list($seq, &$pack); 
        if( $pack == 0 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        
        // Lock Check
        $obj_lock = new class_lock(109);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // orders_del로 이동
        $query = "insert orders_del select * from orders where seq in ($seq_list)";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // orders에서 지우기
        $query = "delete from orders where seq in ($seq_list)";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // order_products_del로 이동
        $query = "insert order_products_del select * from order_products where order_seq in ($seq_list)";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // order_products에서 지우기
        $query = "delete from order_products where order_seq in ($seq_list)";
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        $val['is_pack'] = 0;
        $val['error'] = 0;
        $this->csinsert3($pack, 33, $content);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 우선순위 목록 가져오기
    function get_priority_list()
    {
        global $connect, $seq, $content;

        // seq list
        $seq_str = $this->get_seq_list($seq, &$pack);
        
        // product_id list
        $prd_arr = array();
        $query = "select product_id from order_products where order_seq in ($seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $prd_arr[] = "'" . $data[product_id] . "'";
        $prd_str = implode(",", $prd_arr);

        // priority list
        $val = array();
        $val['list'] = array();
        $query = "select if( a.pack>0, a.pack, a.seq )  a_seq, 
                         a.collect_date a_collect_date,
                         c.shop_name   c_shop_name, 
                         a.order_id    a_order_id, 
                         a.recv_name   a_recv_name,
                         a.cs_priority a_cs_priority
                    from orders a,
                         order_products b,
                         shopinfo c
                   where a.seq = b.order_seq and
                         a.shop_id = c.shop_id and
                         a.status = 1 and
                         b.order_cs not in (1,2) and
                         b.product_id in ($prd_str)
                   group by a_seq
                   order by cs_priority desc, a_collect_date";
                                            
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 작업자와 작업시간 구하기
            $seq_str = $this->get_seq_list($data[a_seq], &$pack);
            $query_work = "select * from csinfo where order_seq in ($seq_str) and cs_type=23 order by seq desc limit 1";
            $result_work = mysql_query($query_work, $connect);
            $data_work = mysql_fetch_assoc($result_work);
            
            $val['list'][] = array(
                'priority'  => $data[a_cs_priority],
                'seq'       => $this->popupcs($data[a_seq]),
                'collect_date' => $data[a_collect_date],
                'shop_name' => $data[c_shop_name],
                'recv_name' => $this->popupcs($data[a_seq], $data[a_recv_name]),
                'crdate'    => $data_work[input_date],
                'worker'    => $data_work[writer]
            );
        }
        
        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 우선순위 설정
    function set_priority()
    {
        global $connect, $seq, $sel, $pos, $prdSeq, $content;
        
        $transaction = $this->begin("우선순위 설정");

        if( $pack = $this->get_pack($seq) )
            $pack_str = "pack=$pack";
        else
            $pack_str = "seq=$seq";
        
        // 현재 최대 우선순위값 구하기
        $query = "select cs_priority from orders order by cs_priority desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $max_priority = $data[cs_priority];

        // 우선순위 최상
        if( $sel == 0 )
        {
            // 최대 우선순위값 + 1로 설정
            $query = "update orders set cs_priority=$max_priority+1 where $pack_str";
        }
        // 우선순위 최하
        else if( $sel == 1 )
        {
            // 기존 우선순위 모두 1 증가
            $query = "update orders set cs_priority=cs_priority+1 where cs_priority>0";
            mysql_query($query, $connect);
            
            // 현재 주문에 우선순위 1 설정
            $query = "update orders set cs_priority=1 where $pack_str";
        }
        // 위치지정
        else
        {
            $new_priority = ( $pos >= 1 ? $pos : $max_priority + 1 );

            // 기존 우선순위 중에서 $new_priority 보다 높은 주문은 1 증가
            $query = "update orders set cs_priority=cs_priority+1 where cs_priority>=$new_priority";
            mysql_query($query, $connect);
            
            // 현재 주문에 우선순위 1 설정
            $query = "update orders set cs_priority=$new_priority where $pack_str";
        }
        mysql_query($query, $connect);

        $val['error'] = 0;
        $this->csinsert3(0, 23, $content);

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 우선순위 해제
    function cancel_priority()
    {
        global $connect, $seq, $prdSeq, $content;
        
        $transaction = $this->begin("우선순위 해제");

        // 최대 우선순위값 0으로 설정
        if( $pack = $this->get_pack($seq) )
            $query = "update orders set cs_priority=0 where pack=$pack";
        else
            $query = "update orders set cs_priority=0 where seq=$seq";
            
        if( mysql_query($query, $connect) )
        {
            $val['error'] = 0;
            $this->csinsert3(0, 24, $content);
        }
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }
    
    ///////////////////////////////////////
    // pack 다운로드 
    function download_pack()
    {
        global $connect, $seq;

        $val = array();

        $pack = 0;
        $seq_list = $this->get_seq_list($seq, &$pack);
        $query = "select a.seq             as a_seq              ,   
                         a.pack            as a_pack             ,   
                         b.shop_id         as b_shop_id          ,
                         a.order_id        as a_order_id         ,   
                         a.status          as a_status           ,   
                         b.order_cs        as b_order_cs         ,   
                         b.is_gift         as b_is_gift          ,   
                         a.hold            as a_hold             ,   
                         a.trans_who       as a_trans_who        ,   
                         a.org_trans_who   as a_org_trans_who    ,   
                         b.product_id      as b_product_id       ,   
                         c.barcode         as c_barcode          ,   
                         b.supply_id       as b_supply_id        ,
                         a.shop_product_id as a_shop_product_id  ,   
                         c.name            as c_name             ,
                         c.options         as c_options          ,   
                         a.product_name    as a_product_name     ,
                         a.options         as a_options          ,
                         b.qty             as b_qty              ,   
                         a.amount          as a_amount           ,   
                         a.supply_price    as a_supply_price     ,   
                         c.org_price       as c_org_price        ,   
                         a.trans_price     as a_trans_price      ,   
                         a.order_date      as a_order_date       ,   
                         b.cancel_date     as b_cancel_date      ,   
                         a.collect_date    as a_collect_date     ,   
                         a.collect_time    as a_collect_time     ,   
                         a.trans_date      as a_trans_date       ,   
                         a.trans_no        as a_trans_no         ,   
                         a.recv_zip        as a_recv_zip         ,   
                         a.recv_address    as a_recv_address     ,   
                         a.trans_date_pos  as a_trans_date_pos   ,   
                         a.priority        as a_priority         ,   
                         a.order_name      as a_order_name       ,   
                         a.order_tel       as a_order_tel        ,   
                         a.order_mobile    as a_order_mobile     ,   
                         a.recv_name       as a_recv_name        ,   
                         a.recv_tel        as a_recv_tel         ,   
                         a.recv_mobile     as a_recv_mobile      ,   
                         a.memo            as a_memo             ,   
                         c.weight          as c_weight           ,   
                         c.brand           as c_brand            ,   
                         c.origin          as c_origin           ,   
                         c.enable_sale     as c_enable_sale      ,
                         c.supply_options  as c_supply_options   ,
                         c.location        as c_location   
                    from orders a, order_products b, products c
                   where a.seq=b.order_seq and 
                         b.product_id = c.product_id and 
                         a.seq in ($seq_list)";
        $result = mysql_query( $query, $connect );
        $i = 1;
        while ( $data = mysql_fetch_array( $result ) )
        {
				$val[$i] = array();
                $val[$i]["seq"                 ]= $data[a_seq];
                $val[$i]["pack"                ]= $data[a_pack];
                $val[$i]["shop_name"           ]= class_D::get_shop_name($data[b_shop_id]);
                $val[$i]["order_id"            ]= $data[a_order_id];
                $val[$i]["status"              ]= $this->get_order_status2( $data[a_status] );
                $val[$i]["order_cs"            ]= $this->get_order_cs2( $data[b_order_cs] );
                $val[$i]["enable_sale"         ]= $data[c_enable_sale] ? "" : "품절";
                $val[$i]["gift"                ]= $data[b_is_gift] ? "사은품" : "";
                $val[$i]["hold"                ]= $data[a_hold] ? "보류" : "";
                $val[$i]["trans_who"           ]= $data[a_trans_who];
                $val[$i]["org_trans_who"       ]= $data[a_org_trans_who];
                $val[$i]["product_id"          ]= $data[b_product_id];
                $val[$i]["barcode"             ]= $data[c_barcode];
                $val[$i]["supply_name"         ]= $this->get_supply_name2($data[b_supply_id]);
                $val[$i]["shop_product_id"     ]= $data[a_shop_product_id];
                $val[$i]["product_name"        ]= $data[c_name];
                $val[$i]["options"             ]= $data[c_options];
                $val[$i]["real_product_name"   ]= $data[a_product_name];
                $val[$i]["real_options"        ]= $data[a_options];
                $val[$i]["qty"                 ]= $data[b_qty];
                if($_SESSION[LOGIN_ID] != 'pinkage100')
                {
	                $val[$i]["amount"              ]= $data[a_is_gift] ? 0 : $data[a_amount];
	                $val[$i]["supply_price"        ]= $data[a_is_gift] ? 0 : $data[a_supply_price];
                }
                if(_DOMAIN_ == "uuzone" || _DOMAIN_ == "flyday")
                	$val[$i]["org_price"           	]= $data[a_is_gift] ? 0 : $data[c_org_price];
                $val[$i]["trans_price"         ]= $data[a_trans_price];
                $val[$i]["order_date"          ]= $data[a_order_date];
                $val[$i]["refund_date"         ]= $data[b_cancel_date];
                $val[$i]["collect_date"        ]= $data[a_collect_date];
                $val[$i]["collect_time"        ]= $data[a_collect_time];
                $val[$i]["trans_date"          ]= $data[a_trans_date];
                $val[$i]["trans_no"            ]= $data[a_trans_no];
                $val[$i]["recv_zip"            ]= $data[a_recv_zip];
                $val[$i]["recv_address"        ]= $data[a_recv_address];
                $val[$i]["trans_date_pos"      ]= $data[a_trans_date_pos];
                $val[$i]["priority"            ]= $data[a_priority];
                $val[$i]["order_name"          ]= $data[a_order_name];
                $val[$i]["order_tel"           ]= $data[a_order_tel]; 
                $val[$i]["order_mobile"        ]= $data[a_order_mobile];   
                $val[$i]["recv_name"           ]= $data[a_recv_name];
                $val[$i]["recv_tel"            ]= $data[a_recv_tel];  
                $val[$i]["recv_mobile"         ]= $data[a_recv_mobile];    
                $val[$i]["memo"                ]= $data[a_memo];
                $val[$i]["weight"              ]= $data[c_weight];
                $val[$i]["brand"               ]= $data[c_brand];
                $val[$i]["supply_options"      ]= $data[c_supply_options];
                $val[$i]["origin"              ]= $data[c_origin]; 
                $val[$i]["location"            ]= $data[c_location]; 
                $i++;
        }

        $data[fn] = $this->make_file( $val );
        // echo "<script type='text/javascript'>set_file('$fn')</script>";
        echo json_encode($data);
    }
    
    function make_file( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
$_arr = array();
        $_arr[] = "관리번호";
        $_arr[] = "합포번호";
        $_arr[] = "판매처";
        $_arr[] = "주문번호";
        $_arr[] = "배송상태";
        $_arr[] = "CS상태";
        $_arr[] = "품절";
        $_arr[] = "사은품";
        $_arr[] = "보류여부";
        $_arr[] = "선착불";
        $_arr[] = "원본선착불";
        $_arr[] = "상품아이디";
        $_arr[] = "바코드";
        $_arr[] = "공급처";
        $_arr[] = "업체상품코드";
        $_arr[] = "상품명";
        $_arr[] = "선택사항";
        $_arr[] = "실제 상품명";
        $_arr[] = "실제 옵션";
        $_arr[] = "판매개수";
        if($_SESSION[LOGIN_ID] != 'pinkage100')
        {
	        $_arr[] = "구매자결제금액";
	        $_arr[] = "정산예정금액";
    	}
        if(_DOMAIN_ == "uuzone" || _DOMAIN_ == "flyday")
        	$_arr[] = "원가"; 
        $_arr[] = "추가결제금액";
        $_arr[] = "주문일";
        $_arr[] = "취소일";
        $_arr[] = "발주일";
        $_arr[] = "발주시간";
        $_arr[] = "송장입력일";
        $_arr[] = "송장번호";
        $_arr[] = "배송지우편번호";
        $_arr[] = "배송지주소";
        $_arr[] = "배송일";
        $_arr[] = "우선순위";        
        $_arr[] = "주문자";        
        $_arr[] = "주문자전화";        
        $_arr[] = "주문자전화2";        
        $_arr[] = "수령자";        
        $_arr[] = "수령자전화";        
        $_arr[] = "수령자전화2";        
        $_arr[] = "메모";
        $_arr[] = "중량";
        $_arr[] = "공급처상품명";
        $_arr[] = "공급처옵션";
        $_arr[] = "원산지";
        $_arr[] = "로케이션";
        
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
            $style2 = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $key == 'product_id' )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
            }
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download3()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "pack_data.xls");
    }    

    function download_file()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "pack_data" . date("mdHis") . ".xls");
        
    }
    

    ///////////////////////////////////////
    // 개별 취소
    function cancel_product()
    {
        global $connect, $checked_data, $seq, $reason, $content;
        global $restockin,$restockin_bad,$site_p,$envelop_p,$account_p,$notget_p,$trans_corp,$trans_no,$trans_who, $return_comp, $trans_price, $do_complete, $memo;

        $val = array();
        $transaction = $this->begin("개별취소");

        //************************
        // checked data 확인
        //************************
        // 
        // $checked_seq_arr
        // $checked_seq_qty_arr
        // $checked_seq_str
        // 
        
        // 체크한 order_products의 seq 배열
        $checked_seq_arr = array();
        // 체크한 order_products의 seq & qty 배열
        $checked_seq_qty_arr = array();
        foreach( explode(",", $checked_data) as $checked_val )
        {
            if( $checked_val )
            {
                list($_seq, $_qty) = explode(":", $checked_val);
                
                // checked seq 배열
                $checked_seq_arr[] = $_seq;
                
                // 수량 배열
                $checked_seq_qty_arr[$_seq] = $_qty;
            }
        }
        // 체크한 order_products의 seq 리스트
        $checked_seq_str = implode(",", $checked_seq_arr);

        //************************
        // 전체 주문 data
        //************************

        // 전체 주문 pack 배열
        $pack_all_arr = array();
        // 전체 주문 seq 배열
        $seq_all_arr = array();
        
        $query = "select a.seq a_seq, 
                         a.pack a_pack
                    from orders a, 
                         order_products b 
                   where a.seq = b.order_seq and 
                         b.seq in ($checked_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $pack_all_arr[] = $data[a_pack];
            $seq_all_arr[] = $data[a_seq];
        }
        $pack_all_arr = array_unique( $pack_all_arr );
        $seq_all_arr = array_unique( $seq_all_arr );

        //---------------------
        // 복수 합포 오류 
        //---------------------
        if( count($pack_all_arr) > 1 )
        {
            $val['error'] = 5;
            echo json_encode( $val );
            return;
        }
        
        //+++++++++++++++++++
        // 기존 합포 pack 번호
        //+++++++++++++++++++
        //
        // $old_pack
        //

        $old_pack = $pack_all_arr[0];
        //---------------------
        // 합포 오류 
        //---------------------
        if( $old_pack == 0 && count($seq_all_arr) > 1 )
        {
            $val['error'] = 5;
            echo json_encode( $val );
            return;
        }
        
        //+++++++++++++++++++
        // 기존 seq list
        //+++++++++++++++++++
        //
        // $old_seq_arr
        // $old_seq_str
        // 
        
        $old_seq_arr = array();
        $old_seq_str = "";
        
        if( $old_pack == 0 )
            $old_seq_arr = $seq_all_arr;
        else
        {
            $query = "select seq from orders where pack=$old_pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $old_seq_arr[] = $data[seq];
        }
        $old_seq_str = implode(",", $old_seq_arr);

        //************************
        // 전체 모든 데이터
        //************************
        //
        // $pack_all = array();
        //
        
        $pack_all = array();

        // 복구된 주문
        $check_is_bck = false;
        
        $i = 0;
        $query = "select * from orders where seq in ($old_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[is_bck] )  $check_is_bck = true;
            
            if( $i++ == 0 )
            {
                $seq = $data[seq];
                $data_orders = $data;
            }
            
            $_temp_arr = $data;
            $_temp_arr["work_type"] = 0;
            $_temp_arr["checked_type"] = "";
            
            $query_p = "select * from order_products where order_seq = $data[seq]";
            $result_p = mysql_query($query_p, $connect);
            while( $data_p = mysql_fetch_assoc($result_p) )
            {
                $_temp_arr2 = $data_p;
                $_temp_arr2["work_type"] = 0;
                $_temp_arr2["checked_qty"] = 0;

                $_temp_arr["order_products"][] = $_temp_arr2;
            }
                
            $pack_all[] = $_temp_arr;
        }
        
        //************************
        // 취소 오류 체크
        //************************
        $cancel_no_error = true;
        foreach( $pack_all as $pack_key => $pack_val )
        {
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크된 상품 아니면 넘어감
                if( !$checked_seq_qty_arr[$seq_val[seq]] )  
                    continue;
                else
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = $checked_seq_qty_arr[$seq_val[seq]];
                
                // 이미 취소상태 아니면 OK
                if( $seq_val[order_cs] == 0 || $seq_val[order_cs] >= 5 )
                {
                    // 취소 수량이 상품 수량보다 크면 오류
                    if( $checked_seq_qty_arr[$seq_val[seq]] > $seq_val[qty] )
                    {
                        $val['error'] = 1;
                        echo json_encode( $val );
                        return;
                    }
                    $cancel_no_error = false;
                }
            }
        }
        //---------------------
        // 이미 모두 취소 오류
        //---------------------
        if( $cancel_no_error )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        
        // Lock Check
        $obj_lock = new class_lock(110);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 기준상품
        $is_first = true;
        $status = 0;
        $seq = 0;
        $prdSeq = 0;
        $trans_key = 0;

        // 취소된 상품 배열
        $canceled_arr = array();
        
        $obj = new class_stock();

        // 수량 취소
        foreach( $pack_all as $pack_key => $pack_val )
        {
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크된 상품 아니면 넘어감
                if( !$checked_seq_qty_arr[$seq_val[seq]] )  continue;
                
                // 이미 취소상태면 넘어감
                if( $seq_val[order_cs] >= 1 && $seq_val[order_cs] <= 4 )  continue;
                
                // 취소 수량이 상품 수량보다 작으면 분리
                if( $seq_val[checked_qty] >= 1 && $seq_val[checked_qty] < $seq_val[qty] )
                {
                    $qty_ratio = $seq_val[checked_qty] / $seq_val[qty];

                    // order_products 복사( insert )
                    $new_seq = $seq_val;
                    $new_seq[work_type] = 1;

                    // 기존 order_products update
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = 0;
                    $pack_all[$pack_key][order_products][$seq_key][work_type] = 2;
                    
                    // qty
                    $new_seq[qty] = $seq_val[checked_qty];
                    $pack_all[$pack_key][order_products][$seq_key][qty] = $seq_val[qty] - $new_seq[qty];

                    // price update
                    $price_update_array = array("org_price","refund_price","extra_money","prd_amount","prd_supply_price");
                    foreach( $price_update_array as $price_val )
                    {
                        // 기존 금액
                        $old_price = $seq_val[$price_val];
                        // 수량 비율로 나눈 새 금액
                        $new_seq[$price_val] = round($old_price * $qty_ratio);
                        // 나머지 금액
                        $pack_all[$pack_key][order_products][$seq_key][$price_val] = $old_price - $new_seq[$price_val];
                    }

                    // 부분취소처리
                    if( $pack_val[status] == 8 )
                    {
                        $new_seq[order_cs] = 4;
                        
                        // 자동반품입고
                        if( $restockin )
                        {
                            $obj->set_stock( array( type       => 'retin',
                                                    product_id => $seq_val[product_id], 
                                                    bad        => $restockin_bad,
                                                    location   => 'Def',
                                                    qty        => $new_seq[qty],
                                                    memo       => 'CS 자동반품입고',
                                                    order_seq  => $pack_val[seq] ) );
                        }
                    }
                    else
                        $new_seq[order_cs] = 2;
                    
                    if( _DOMAIN_ == 'parklon' )
                        $new_seq[refund_price] = 0;
                    else if( _DOMAIN_ == 'elkara' )
                        $new_seq[refund_price] = $new_seq[prd_supply_price] + $new_seq[extra_money];
                    else
                        $new_seq[refund_price] = $new_seq[prd_amount] + $new_seq[extra_money];

                    $new_seq[cancel_date] = date("Y-m-d h:i:s");
                    
                    // order_products 추가
                    $pack_all[$pack_key][order_products][] = $new_seq;
                    $pack_all[$pack_key][refund_price] += $new_seq[refund_price];
                    $pack_all[$pack_key][work_type] = 2;
                }
                // 수량 전체 취소
                else
                {
                    // 부분취소처리
                    if( $pack_val[status] == 8 )
                    {
                        $pack_all[$pack_key][order_products][$seq_key][order_cs] = 4;

                        // 자동반품입고
                        if( $restockin )
                        {
                            $obj->set_stock( array( type       => 'retin',
                                                    product_id => $seq_val[product_id], 
                                                    bad        => $restockin_bad,
                                                    location   => 'Def',
                                                    qty        => $seq_val[qty],
                                                    memo       => 'CS 자동반품입고',
                                                    order_seq  => $pack_val[seq] ) );
                        }
                    }
                    else
                        $pack_all[$pack_key][order_products][$seq_key][order_cs] = 2;
                        
                    if( _DOMAIN_ == 'parklon' )
                        $pack_all[$pack_key][order_products][$seq_key][refund_price] = 0;
                    else if( _DOMAIN_ == 'elkara' )
                        $pack_all[$pack_key][order_products][$seq_key][refund_price] = $seq_val[prd_supply_price] + $seq_val[extra_money];
                    else
                        $pack_all[$pack_key][order_products][$seq_key][refund_price] = $seq_val[prd_amount] + $seq_val[extra_money];

                    $pack_all[$pack_key][order_products][$seq_key][work_type] = 2;
                    $pack_all[$pack_key][order_products][$seq_key][cancel_date] = date("Y-m-d h:i:s");

                    $pack_all[$pack_key][refund_price] += $seq_val[prd_amount] + $seq_val[extra_money];
                    $pack_all[$pack_key][work_type] = 2;
                }

                    
                $canceled_arr[] = array(
                    "seq"        => $pack_val[seq],
                    "product_id" => $seq_val[product_id],
                    "qty"        => $seq_val[checked_qty]
                );

                if( $is_first )
                {
                    $is_first = false;
                    $seq = $pack_val[seq];
                    $status = $pack_val[status];
                    $prdSeq = $seq_val[seq];
                    $trans_key = $pack_val[trans_key];
                }
            }
        }

        //++++++++++++++++++++++++++++++
        // orders drop insert & update
        foreach( $pack_all as $pack_key => $pack_val )
        {
            // orders drop update
            if( $pack_val[work_type] == 2 )  
            {
                $query = "update orders set refund_price = '$pack_val[refund_price]' where seq = $pack_val[seq]";
debug( "cancel_product(update refund_price) : $query");
                mysql_query($query, $connect);
            }
            
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // prdSeq가 분리되는 경우, 새로 insert된 seq를 구한다.
                $is_new_prdSeq = false;
                
                // insert | update
                if( $seq_val[work_type] == 1 )
                {
                    $query = "insert order_products set ";
                    
                    // prdSeq가 분리되는 경우, 새로 insert된 seq를 구한다.
                    if( $seq_val["seq"] == $prdSeq )
                        $is_new_prdSeq = true;
                }
                else if( $seq_val[work_type] == 2 )
                    $query = "update order_products set ";
                else
                    continue;

                foreach( $seq_val as $seq_item_key => $seq_item_val )
                {
                    if( $seq_item_key == "seq"         ||
                        $seq_item_key == "work_type"   ||
                        $seq_item_key == "checked_qty" )  continue;
                    
                    $seq_item_val = str_replace("'", "\\'", $seq_item_val);
                    $query .= " $seq_item_key = '$seq_item_val',";
                }

                if( $seq_val[work_type] == 1 )
                    $query = substr($query,0,-1);
                else
                    $query = substr($query,0,-1) . " where seq=$seq_val[seq]";

debug( "cancel_product(insert/update order_products) : $query");
                mysql_query($query, $connect);
                
                // prdSeq가 분리되는 경우, 새로 insert된 seq를 구한다.
                if( $is_new_prdSeq )
                {
                    $query_new = "select seq from order_products order by seq desc limit 1";
                    $result_new = mysql_query($query_new, $connect);
                    $data_new = mysql_fetch_assoc($result_new);
                    
                    $prdSeq = $data_new[seq];
                }
            }

            // orders의 order_cs 수정
            $this->modify_orders_cs($pack_val[seq]);
        }
        
        // 배송후 취소. 

        if( $status == 8 )
        {
        	// 배송후 취소 box4u일경우 정산리스트에서 제거
        	if(_DOMAIN_ == 'box4u' || _DOMAIN_ == 'ezadmin' )
        	{
        		// box4u_func.inc include $checked_seq_str        		
        		trans_cancel("",$checked_seq_str);
			}
        
        
        
            //*********************************
            // 배송후 개별 취소, 반품정보
            //*********************************
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                $site_p    = (int)$site_p   ;   
                $envelop_p = (int)$envelop_p;
                $account_p = (int)$account_p;
                $notget_p  = (int)$notget_p ;
                $restockin = (int)$restockin;
    
                if( $restockin > 0 )
                    $restockin_val = 1 + $restockin_bad;
                else
                    $restockin_val = 0;
                
                // org_prdSeq
                $org_prdSeq = $prdSeq;

                // org price
                $org_site_p      = $site_p     ;
                $org_envelop_p   = $envelop_p  ;
                $org_account_p   = $account_p  ;
                $org_notget_p    = $notget_p   ;
                $org_trans_price = $trans_price;
                
                if( $_SESSION[USE_RETURN_PRD_ALL] )
                    $query_p = "select seq from order_products where order_seq in (" . implode(",", $seq_all_arr) . ") and order_cs in (3,4)";
                else
                    $query_p = "select seq from order_products where seq = $prdSeq";
                $result_p = mysql_query($query_p, $connect);
                while( $data_p = mysql_fetch_assoc($result_p) )
                {
                    $prdSeq = $data_p[seq];
                    
                    if( $org_prdSeq != $prdSeq )
                    {
                        $site_p      = 0;
                        $envelop_p   = 0;
                        $account_p   = 0;
                        $notget_p    = 0;
                        $trans_price = 0;
                    }
                    else
                    {
                        $site_p      = $org_site_p     ;
                        $envelop_p   = $org_envelop_p  ;
                        $account_p   = $org_account_p  ;
                        $notget_p    = $org_notget_p   ;
                        $trans_price = $org_trans_price;
                    }

                    // 반품예정 있는지 확인
                    $query = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
                    $result = mysql_query($query, $connect);
                    if( mysql_num_rows($result) )
                    {
                        $data = mysql_fetch_assoc($result);
        
                        if( $org_prdSeq != $prdSeq )
                        {
                            $data[expect_site]    = 0;
                            $data[expect_envelop] = 0;
                            $data[expect_account] = 0;
                        }

                        // 교환인 경우
                        if( $data[return_type] == 2 )
                        {
                            // 이전 반품정보 삭제처리
                            $query_return = "update return_money 
                                                set is_delete     = 1,
                                                    delete_date   = now(),
                                                    delete_worker = '$_SESSION[LOGIN_NAME]'";
                            $query_return .= " where seq = $data[seq]";
                            mysql_query($query_return, $connect);
        
                            // 로그 남기기
                            $query_return_log = "insert return_money_log
                                                    set seq                = '$data[seq]',
                                                        log_type           = 'delete',
                                                        log_contents       = '취소처리 삭제',
                                                        log_date           = now(),
                                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
                            mysql_query($query_return_log, $connect);
        
                            // 반품정보 입력
                            $query_ret_insert = "insert return_money 
                                                    set order_seq          = '$data[order_seq]',         
                                                        order_products_seq = '$data[order_products_seq]',
                                                        collect_date       = '$data[collect_date]',      
                                                        trans_date         = '$data[trans_date]',        
                                                        shop_id            = '$data[shop_id]',           
                                                        supply_id          = '$data[supply_id]',         
                                                        recv_name          = '$data[recv_name]',         
                                                        shop_product_id    = '$data[shop_product_id]',   
                                                        shop_product_name  = '$data[shop_product_name]', 
                                                        shop_options       = '$data[shop_options]',      
                                                        ez_product_id      = '$data[ez_product_id]',     
                                                        ez_product_name    = '$data[ez_product_name]',   
                                                        ez_options         = '$data[ez_options]',        
                                                        qty                = '$data[qty]',               
                                                        org_trans_no       = '$data[org_trans_no]',      
                                                        return_type        = 1,       
                                                        cancel_type        = '$reason',       
                                                        change_type        = '',       
                                                        expect_site        = '$data[expect_site]',       
                                                        expect_envelop     = '$data[expect_envelop]',    
                                                        expect_account     = '$data[expect_account]',    
                                                        expect_trans_who   = '$data[expect_trans_who]',  
                                                        memo               = '$memo',
                                                        return_site        = $site_p,      
                                                        return_envelop     = $envelop_p,   
                                                        return_account     = $account_p,   
                                                        return_notget      = $notget_p,    
                                                        return_trans_who   = '$trans_who', 
                                                        return_trans_corp  = '$trans_corp',
                                                        return_trans_no    = '$trans_no',  
                                                        restockin_auto     = '$restockin_val', 
                                                        is_expect          = '$data[is_expect]',         
                                                        expect_date        = '$data[expect_date]',       
                                                        expect_worker      = '$data[expect_worker]',     
                                                        is_return          = 1,         
                                                        return_date        = now(),       
                                                        return_worker      = '$_SESSION[LOGIN_NAME]',
                                                        return_trans_price = '$trans_price'";
        
                            // 반품택배비 정산완료
                            if( $return_comp )
                            {
                                $query_ret_insert .= " ,is_complete       = 1,
                                                        complete_date     = now(),
                                                        complete_worker   = '$_SESSION[LOGIN_NAME]'";
                            }
            
                            mysql_query($query_ret_insert, $connect);
            
                            // 로그용 seq 구하기
                            $query = "select max(seq) max_seq
                                        from return_money
                                       where is_return = 1 and  
                                             is_delete = 0 and
                                             return_worker = '$_SESSION[LOGIN_NAME]'";
                            $result = mysql_query($query, $connect);
                            $data = mysql_fetch_assoc($result);
            
                            $log_seq = $data[max_seq];
                        }
                        // 단순 반품예정인 경우 (취소인 경우는 없다. 왜냐하면 취소인 경우에는 다시 취소를 걸수 없으므로)
                        else
                        {
                            $query_return = "update return_money 
                                                set return_type       = 1,
                                                    cancel_type       = '$reason',
                                                    memo              = '$memo',
                                                    return_site       = '$site_p',
                                                    return_envelop    = '$envelop_p',
                                                    return_account    = '$account_p',
                                                    return_notget     = '$notget_p',
                                                    return_trans_corp = '$trans_corp',
                                                    return_trans_no   = '$trans_no',
                                                    return_trans_who  = '$trans_who',
                                                    restockin_auto    = '$restockin_val',
                                                    is_return         = 1,
                                                    return_date       = now(),
                                                    return_worker     = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price = '$trans_price'";
            
                            // 반품택배비 정산완료
                            if( $return_comp )
                            {
                                $query_return .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                            }
                            
                            $query_return .= " where seq = $data[seq]";
                            mysql_query($query_return, $connect);
                            
                            $log_seq = $data[seq];
                        }
                    }
                    // 반품정보가 없는 경우
                    else
                    {
                        // 반품정보
                        $query_ret_order = "select a.seq             a_seq,
                                                   a.collect_date    a_collect_date,
                                                   a.collect_time    a_collect_time,
                                                   a.trans_date_pos  a_trans_date_pos,
                                                   a.shop_id         a_shop_id,
                                                   a.recv_name       a_recv_name,
                                                   a.shop_product_id a_shop_product_id,
                                                   a.product_name    a_product_name,
                                                   a.options         a_options,
                                                   a.trans_no        a_trans_no,
                                                   b.seq             b_seq,
                                                   b.supply_id       b_supply_id,
                                                   b.qty             b_qty,
                                                   c.product_id      c_product_id,
                                                   c.name            c_name,
                                                   c.options         c_options
                                              from orders a,
                                                   order_products b,
                                                   products c
                                             where a.seq = b.order_seq and
                                                   b.product_id = c.product_id and
                                                   b.seq = $prdSeq";
                        $result_ret_order = mysql_query($query_ret_order, $connect);
                        $data_ret_order = mysql_fetch_assoc($result_ret_order);
        
                        // 반품정보 입력
                        $query_ret_insert = "insert return_money 
                                                set order_seq          = '$data_ret_order[a_seq]',
                                                    order_products_seq = '$data_ret_order[b_seq]',
                                                    collect_date       = '$data_ret_order[a_collect_date] $data_ret_order[a_collect_time]',
                                                    trans_date         = '$data_ret_order[a_trans_date_pos]',
                                                    shop_id            = '$data_ret_order[a_shop_id]',
                                                    supply_id          = '$data_ret_order[b_supply_id]',
                                                    shop_product_id    = '$data_ret_order[a_shop_product_id]',
                                                    ez_product_id      = '$data_ret_order[c_product_id]',
                                                    recv_name          = '" . addslashes($data_ret_order[a_recv_name]   ) . "',
                                                    shop_product_name  = '" . addslashes($data_ret_order[a_product_name]) . "',
                                                    shop_options       = '" . addslashes($data_ret_order[a_options]     ) . "',
                                                    ez_product_name    = '" . addslashes($data_ret_order[c_name]        ) . "',
                                                    ez_options         = '" . addslashes($data_ret_order[c_options]     ) . "',
                                                    qty                = '$data_ret_order[b_qty]',
                                                    org_trans_no       = '$data_ret_order[a_trans_no]',
                                                    return_type        = 1,
                                                    cancel_type        = '$reason',
                                                    change_type        = '',
                                                    memo               = '$memo',
                                                    return_site        = $site_p,
                                                    return_envelop     = $envelop_p,
                                                    return_account     = $account_p,
                                                    return_notget      = $notget_p,
                                                    return_trans_who   = '$trans_who',
                                                    return_trans_corp  = '$trans_corp',
                                                    return_trans_no    = '$trans_no',
                                                    restockin_auto     = '$restockin_val',
                                                    is_return          = 1,
                                                    return_date        = now(),
                                                    return_worker      = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price = '$trans_price'";
        
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_ret_insert .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
                        mysql_query($query_ret_insert, $connect);
        
                        // 로그 
                        $query = "select seq
                                    from return_money
                                   where is_expect = 0 and
                                         is_return = 1 and  
                                         is_delete = 0 and
                                         return_worker = '$_SESSION[LOGIN_NAME]'
                                   order by seq desc limit 1";
                        $result = mysql_query($query, $connect);
                        $data = mysql_fetch_assoc($result);
        
                        $log_seq = $data[seq];
                    }
                }
                
                // 반품도착 로그
                $log_contents = "";
                if( $site_p )
                    $log_contents .= "사이트결제 : " . number_format($site_p) . " 원, ";
                if( $envelop_p )
                    $log_contents .= "동봉 : " . number_format($envelop_p) . " 원, ";
                if( $account_p )
                    $log_contents .= "계좌 : " . number_format($account_p) . " 원, ";
                if( $notget_p )
                    $log_contents .= "미수 : " . number_format($notget_p) . " 원, ";
                if( $trans_no )
                {
                    $log_contents .= "택배사 : " . $this->get_trans_corp_name($trans_corp) . ", ";
                    $log_contents .= "송장번호 : $trans_no, ";
                    $log_contents .= "선착불 : " . ($trans_who ? "착불" : "선불") . ", ";
                }
                if( $restockin_val == 1 )
                    $log_contents .= "자동반품입고 : 설정-정상";
                else if( $restockin_val == 2 )
                    $log_contents .= "자동반품입고 : 설정-$_SESSION[EXTRA_STOCK_TYPE]";
    
                $query_return_log = "insert return_money_log
                                        set seq                = '$log_seq',
                                            log_type           = 'return',
                                            log_contents       = '$log_contents',
                                            log_date           = now(),
                                            log_worker         = '$_SESSION[LOGIN_NAME]'";
                mysql_query($query_return_log, $connect);
    
                // 반품택배비 정산완료 로그
                if( $return_comp )
                {
                    $query_return_log = "insert return_money_log
                                            set seq                = '$log_seq',
                                                log_type           = 'complete',
                                                log_date           = now(),
                                                log_worker         = '$_SESSION[LOGIN_NAME]'";
                    mysql_query($query_return_log, $connect);
                }
            }
            
            // 반품확인시 자동 sms 전송
            $this->send_return_sms($seq, $data_orders);
            
        }

        // 취소 로그
        $total_cancel_qty = 0;
        $cancel_log = "<";
        foreach( $canceled_arr as $canceled_val )
        {
            $cancel_log .= "$canceled_val[seq]($canceled_val[product_id]):$canceled_val[qty],";
            $total_cancel_qty += $canceled_val[qty];
        }
        $cancel_log .= ">";
        
        // CTI
        if( $_REQUEST[callid] )
        {
            $query = "update cti_call_history set cancel_qty = cancel_qty + $total_cancel_qty where callid='$_REQUEST[callid]'";
            mysql_query( $query, $connect);
        }
        
        $val['error'] = 0;
        if( $status == 8 && $_SESSION[USE_RETURN_MONEY] )
        {
            if( $return_comp )
                $sys_content = $cancel_log . " " . $log_contents . ", 반품정보 정산 : 완료<br>";
            else
                $sys_content = $cancel_log . " " . $log_contents . "<br>";
        }
        else
            $content = $cancel_log . "<br>" .  $content;

        $this->csinsert8(0, 16, $content,$sys_content, $reason, $check_is_bck, $do_complete);

        // 자동보류
        if ( ($_SESSION[AUTO_HOLD] >= 1 && $status == 7) || 
             ($_SESSION[AUTO_HOLD] == 1 && $status == 1 && $trans_key > 0) )
            $this->modify_orders_hold($old_seq_str);

        // BCK
        if( $check_is_bck )
            $this->save_to_bck($old_seq_str);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 개별 정상 복귀
    function normal_product()
    {
        global $connect, $checked_data, $content, $cancel_re, $cancel_re_bad;

        $val = array();
        $transaction = $this->begin("상품정상복귀");

        //************************
        // checked data 확인
        //************************
        // 
        // $checked_seq_arr
        // $checked_seq_qty_arr
        // $checked_seq_str
        // 
        
        // 체크한 order_products의 seq 배열
        $checked_seq_arr = array();
        // 체크한 order_products의 seq & qty 배열
        $checked_seq_qty_arr = array();
        foreach( explode(",", $checked_data) as $checked_val )
        {
            if( $checked_val )
            {
                list($_seq, $_qty) = explode(":", $checked_val);
                
                // checked seq 배열
                $checked_seq_arr[] = $_seq;
                
                // 수량 배열
                $checked_seq_qty_arr[$_seq] = $_qty;
            }
        }
        // 체크한 order_products의 seq 리스트
        $checked_seq_str = implode(",", $checked_seq_arr);

        //************************
        // 전체 주문 data
        //************************

        // 전체 주문 pack 배열
        $pack_all_arr = array();
        // 전체 주문 seq 배열
        $seq_all_arr = array();
        
        $query = "select a.seq a_seq, 
                         a.pack a_pack
                    from orders a, 
                         order_products b 
                   where a.seq = b.order_seq and 
                         b.seq in ($checked_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $pack_all_arr[] = $data[a_pack];
            $seq_all_arr[] = $data[a_seq];
        }
        $pack_all_arr = array_unique( $pack_all_arr );
        $seq_all_arr = array_unique( $seq_all_arr );

        //---------------------
        // 복수 합포 오류 
        //---------------------
        if( count($pack_all_arr) > 1 )
        {
            $val['error'] = 5;
            echo json_encode( $val );
            return;
        }
        
        //+++++++++++++++++++
        // 기존 합포 pack 번호
        //+++++++++++++++++++
        //
        // $old_pack
        //

        $old_pack = $pack_all_arr[0];
        //---------------------
        // 합포 오류 
        //---------------------
        if( $old_pack == 0 && count($seq_all_arr) > 1 )
        {
            $val['error'] = 5;
            echo json_encode( $val );
            return;
        }
        
        //+++++++++++++++++++
        // 기존 seq list
        //+++++++++++++++++++
        //
        // $old_seq_arr
        // $old_seq_str
        // 
        
        $old_seq_arr = array();
        $old_seq_str = "";
        
        if( $old_pack == 0 )
            $old_seq_arr = $seq_all_arr;
        else
        {
            $query = "select seq from orders where pack=$old_pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $old_seq_arr[] = $data[seq];
        }
        $old_seq_str = implode(",", $old_seq_arr);

        //************************
        // 전체 모든 데이터
        //************************
        //
        // $pack_all = array();
        //
        
        $pack_all = array();
        $max_status = 0;
        $trans_info = array();

        $check_is_bck = false;
        
        $query = "select * from orders where seq in ($old_seq_str)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[is_bck] )  $check_is_bck = true;
            
            $_temp_arr = $data;
            $_temp_arr["work_type"] = 0;
            $_temp_arr["checked_type"] = "";
            
            $query_p = "select * from order_products where order_seq = $data[seq]";
            $result_p = mysql_query($query_p, $connect);
            while( $data_p = mysql_fetch_assoc($result_p) )
            {
                $_temp_arr2 = $data_p;
                $_temp_arr2["work_type"] = 0;
                $_temp_arr2["checked_qty"] = 0;

                $_temp_arr["order_products"][] = $_temp_arr2;
            }
                
            $pack_all[] = $_temp_arr;
            $max_status = max($max_status, $data[status]);
            
            if( $data[status] == 7 )
            {
                $trans_info = array(
                    "trans_no"   => $data[trans_no],
                    "trans_date" => $data[trans_date],
                    "trans_corp" => $data[trans_corp]
                );
            }
        }
        
        //************************
        // 정상복귀 오류 체크
        //************************
        $normal_no_error = true;
        foreach( $pack_all as $pack_key => $pack_val )
        {
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크된 상품 아니면 넘어감
                if( !$checked_seq_qty_arr[$seq_val[seq]] )  
                    continue;
                else
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = $checked_seq_qty_arr[$seq_val[seq]];
                
                // 배송된 주문인데, 배송전 취소를 정상복귀 할 수 없음
                if( $max_status == 8 )
                {
                    if( $pack_val[status] < 8 || $seq_val[order_cs] == 1 || $seq_val[order_cs] == 2 )
                    {
                        $val['error'] = 6;
                        echo json_encode( $val );
                        return;
                    } 
                }
                
                // 취소상태면 OK
                if( $seq_val[order_cs] >= 1 && $seq_val[order_cs] <= 4 )
                {
                    // 정상복귀 수량이 상품 수량보다 크면 오류
                    if( $checked_seq_qty_arr[$seq_val[seq]] > $seq_val[qty] )
                    {
                        $val['error'] = 1;
                        echo json_encode( $val );
                        return;
                    }
                    $normal_no_error = false;
                }
            }
        }
        //---------------------
        // 이미 모두 정상 오류
        //---------------------
        if( $normal_no_error )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        
        // Lock Check
        $obj_lock = new class_lock(111);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 기준상품
        $is_first = true;
        $status = 0;
        $seq = 0;
        $prdSeq = 0;
        $trans_key = 0;

        // 정상복귀된 상품 배열
        $normaled_arr = array();
        $normaled_prd_arr = array();
        
        $obj = new class_stock();

        // 수량 정상복귀
        foreach( $pack_all as $pack_key => $pack_val )
        {
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // 체크된 상품 아니면 넘어감
                if( !$checked_seq_qty_arr[$seq_val[seq]] )  continue;
                
                // 이미 정상상태면 넘어감
                if( $seq_val[order_cs] == 0 || $seq_val[order_cs] >= 5 )  continue;
                
                // 정상복귀 수량이 상품 수량보다 작으면 분리
                if( $seq_val[checked_qty] >= 1 && $seq_val[checked_qty] < $seq_val[qty] )
                {
                    $qty_ratio = $seq_val[checked_qty] / $seq_val[qty];

                    // order_products 복사( insert )
                    $new_seq = $seq_val;
                    $new_seq[work_type] = 1;

                    // 기존 order_products update
                    $pack_all[$pack_key][order_products][$seq_key][checked_qty] = 0;
                    $pack_all[$pack_key][order_products][$seq_key][work_type] = 2;
                    
                    // qty
                    $new_seq[qty] = $seq_val[checked_qty];
                    $pack_all[$pack_key][order_products][$seq_key][qty] = $seq_val[qty] - $new_seq[qty];

                    // price update
                    $price_update_array = array("org_price","refund_price","extra_money","prd_amount","prd_supply_price");
                    foreach( $price_update_array as $price_val )
                    {
                        // 기존 금액
                        $old_price = $seq_val[$price_val];
                        // 수량 비율로 나눈 새 금액
                        $new_seq[$price_val] = round($old_price * $qty_ratio);
                        // 나머지 금액
                        $pack_all[$pack_key][order_products][$seq_key][$price_val] = $old_price - $new_seq[$price_val];
                    }

                    // 부분 정상복귀 처리
                    $new_seq[order_cs] = 0;
                    $new_seq[refund_price] = 0;

                    // order_products 추가
                    $pack_all[$pack_key][order_products][] = $new_seq;
                    $pack_all[$pack_key][refund_price] = $pack_all[$pack_key][order_products][$seq_key][refund_price];
                    $pack_all[$pack_key][work_type] = 2;
                    
                }
                // 수량 전체 정상복귀
                else
                {
                    $pack_all[$pack_key][order_products][$seq_key][order_cs] = 0;
                    $pack_all[$pack_key][order_products][$seq_key][refund_price] = 0;
                    $pack_all[$pack_key][order_products][$seq_key][work_type] = 2;
                    $pack_all[$pack_key][refund_price] -= $seq_val[prd_amount] + $seq_val[extra_money];
                    $pack_all[$pack_key][work_type] = 2;
                }
                    
                // 합포는 송장상태인데, 정상복귀 상품이 접수인 경우 송장상태로 만들어줌
                if( $max_status == 7 && $pack_val[status] == 1)
                {
                    $pack_all[$pack_key][work_type]   = 3;
                    $pack_all[$pack_key][trans_no]   = $trans_info[trans_no];
                    $pack_all[$pack_key][trans_corp] = $trans_info[trans_corp];
                    $pack_all[$pack_key][trans_date] = $trans_info[trans_date];
debug("송장상태 만들기 : $trans_info[trans_no], $trans_info[trans_corp], $trans_info[trans_date]");
                }

                $normaled_arr[] = array(
                    "seq"        => $pack_val[seq],
                    "product_id" => $seq_val[product_id],
                    "qty"        => $seq_val[checked_qty]
                );
                $normaled_prd_arr[] = $seq_val[seq];

                if( $is_first )
                {
                    $is_first = false;
                    $seq = $pack_val[seq];
                    $status = $pack_val[status];
                    $prdSeq = $seq_val[seq];
                    $trans_key = $pack_val[trans_key];
                }
            }
        }

        //++++++++++++++++++++++++++++++
        // orders drop insert & update
        foreach( $pack_all as $pack_key => $pack_val )
        {
            // orders drop update
            if( $pack_val[work_type] == 2 )  
            {
                $query = "update orders set refund_price = '$pack_val[refund_price]' where seq = $pack_val[seq]";
debug( "normal_product(update refund_price) : $query");
                mysql_query($query, $connect);
            }
            else if( $pack_val[work_type] == 3 )  
            {
                $query = "update orders 
                             set status = 7,
                                 trans_no = '$pack_val[trans_no]',
                                 trans_date = '$pack_val[trans_date]',
                                 trans_corp = '$pack_val[trans_corp]'
                           where seq = $pack_val[seq]";
                mysql_query($query, $connect);
            }
            
            
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                // insert | update
                if( $seq_val[work_type] == 1 )
                    $query = "insert order_products set ";
                else if( $seq_val[work_type] == 2 )
                    $query = "update order_products set ";
                else
                    continue;

                foreach( $seq_val as $seq_item_key => $seq_item_val )
                {
                    if( $seq_item_key == "seq"         ||
                        $seq_item_key == "work_type"   ||
                        $seq_item_key == "checked_qty" )  continue;
                    
                    $seq_item_val = str_replace("'", "\\'", $seq_item_val);
                    $query .= " $seq_item_key = '$seq_item_val',";
                }

                if( $seq_val[work_type] == 1 )
                    $query = substr($query,0,-1);
                else
                    $query = substr($query,0,-1) . " where seq=$seq_val[seq]";

debug( "normal_product(insert/update order_products) : $query");
                mysql_query($query, $connect);
            }

            // orders의 order_cs 수정
            $this->modify_orders_cs($pack_val[seq]);
        }
        
        // 보류설정
        if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
             ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
            $this->modify_orders_hold($old_seq_str);

        //************************************
        // 배송후 반품정보 되돌리기
        //************************************
        if( $status == 8 )
        {
            // 반품정보가 있으면
            $normaled_prd_str = implode(",", $normaled_prd_arr);

            if(_DOMAIN_ =="box4u" || _DOMAIN_ =="ezadmin")
            {
            	//box4u_func 개별정상복귀..
            	trans_cancel_return($normaled_prd_str, "normal_product");
//            	trans_cancel_return($checked_seq_str, "normal_product");
            	
            }

            $query_return = "select * from return_money where order_products_seq in ($normaled_prd_str) and is_delete = 0";
            $result_return = mysql_query($query_return, $connect);
            while( $data_return = mysql_fetch_assoc($result_return) )
            {
                // 반품정보 이동
                $return_move_check = false;
                
                // 반품정보가 전체취소이면
                if( $data_return[return_type] == 0 )
                {
                    // 합포 내에 다른 취소건 중에서 반품정보 없는 상품이 있으면
                    $query_check = "select b.order_seq       b_order_seq,
                                           b.seq             b_seq,
                                           a.status          a_status, 
                                           a.collect_date    a_collect_date,
                                           a.collect_time    a_collect_time,
                                           a.trans_date_pos  a_trans_date_pos,
                                           a.shop_id         a_shop_id,
                                           b.supply_id       b_supply_id,
                                           a.recv_name       a_recv_name,
                                           a.shop_product_id a_shop_product_id,
                                           a.product_name    a_product_name,
                                           a.options         a_options,
                                           b.product_id      b_product_id,
                                           c.name            c_name,
                                           c.options         c_options,
                                           b.qty             b_qty,
                                           a.trans_no        a_trans_no,
                                           b.order_cs        b_order_cs
                                      from orders a, 
                                           products c, 
                                           order_products b
                                           left outer join
                                           return_money d
                                           on b.seq = d.order_products_seq and
                                              d.is_delete = 0
                                     where a.seq = b.order_seq and
                                           b.product_id = c.product_id and
                                           a.seq in ($old_seq_str) and
                                           a.status = 8 and
                                           b.order_cs in (3,4) and
                                           d.seq is null";
                    $result_check = mysql_query($query_check, $connect);
                    if( mysql_num_rows($result_check) )
                    {
                        $data_check = mysql_fetch_assoc($result_check);
                        
                        // 기존 반품 정보를 이전한다.
                        $query_move = "insert return_money 
                                          set order_seq          = '$data_check[b_order_seq]',
                                              order_products_seq = '$data_check[b_seq]',
                                              collect_date       = '$data_check[a_collect_date] $data_check[a_collect_time]',
                                              trans_date         = '$data_check[a_trans_date_pos]',
                                              shop_id            = '$data_check[a_shop_id]',
                                              supply_id          = '$data_check[a_supply_id]',
                                              shop_product_id    = '$data_check[a_shop_product_id]',
                                              ez_product_id      = '$data_check[b_product_id]',
                                              recv_name          = '" . addslashes($data_check[a_recv_name]   ) . "',
                                              shop_product_name  = '" . addslashes($data_check[a_product_name]) . "',
                                              shop_options       = '" . addslashes($data_check[a_options]     ) . "',
                                              ez_product_name    = '" . addslashes($data_check[c_name]        ) . "',
                                              ez_options         = '" . addslashes($data_check[c_options]     ) . "',
                                              qty                = '$data_check[b_qty]',
                                              org_trans_no       = '$data_check[a_trans_no]',
                                              return_type        = '$data_return[return_type]',
                                              cancel_type        = '$data_return[cancel_type]',
                                              change_type        = '$data_return[change_type]',
                                              expect_site        = '$data_return[expect_site]',
                                              expect_envelop     = '$data_return[expect_envelop]',
                                              expect_account     = '$data_return[expect_account]',
                                              expect_trans_who   = '$data_return[expect_trans_who]',
                                              return_site        = '$data_return[return_site]',
                                              return_envelop     = '$data_return[return_envelop]',
                                              return_account     = '$data_return[return_account]',
                                              return_notget      = '$data_return[return_notget]',
                                              return_trans_who   = '$data_return[return_trans_who]',
                                              return_trans_corp  = '$data_return[return_trans_corp]',
                                              return_trans_no    = '$data_return[return_trans_no]',
                                              restockin_auto     = '$data_return[restockin_auto]',
                                              is_expect          = '$data_return[is_expect]',
                                              expect_date        = '$data_return[expect_date]',
                                              expect_worker      = '$data_return[expect_worker]',
                                              is_return          = '$data_return[is_return]',
                                              return_date        = '$data_return[return_date]',
                                              return_worker      = '$data_return[return_worker]',
                                              is_complete        = '$data_return[is_complete]',
                                              complete_date      = '$data_return[complete_date]',
                                              complete_worker    = '$data_return[complete_worker]'";
                        mysql_query($query_move);

                        $query_move_sel = "select max(seq) max_seq from return_money where order_products_seq = $data_check[b_seq] and is_delete = 0";
                        $result_move_sel = mysql_query($query_move_sel, $connect);
                        $data_move_sel = mysql_fetch_assoc($result_move_sel);
                        
                        // 로그 남기기
                        $query_return_log = "insert return_money_log
                                                set seq                = '$data_move_sel[max_seq]',
                                                    log_type           = 'move',
                                                    log_contents       = '개별정상복귀실행 반품정보 이동($data_check[b_order_seq])',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);

                        // 기존 반품정보는 삭제 처리한다.
                        $query_return = "update return_money 
                                            set is_delete          = 1,
                                                delete_date        = now(),
                                                delete_worker      = '$_SESSION[LOGIN_NAME]'
                                          where seq = $data_return[seq]";
                        mysql_query($query_return, $connect);
        
                        // 로그 남기기
                        $query_return_log = "insert return_money_log
                                                set seq                = '$data_return[seq]',
                                                    log_type           = 'delete',
                                                    log_contents       = '개별정상복귀실행 반품정보 삭제',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);
                        
                        $return_move_check = true;
                    }
                }
                // 반품정보 이동이 아닌경우
                if( !$return_move_check )
                    $this->cancel_return($data_return[order_products_seq], 1);
            }
            
        }

        // 정상복귀 로그
        $total_normal_qty = 0;
        $normal_log = "<";
        foreach( $normaled_arr as $normaled_val )
        {
            $normal_log .= "$normaled_val[seq]($normaled_val[product_id]):$normaled_val[qty],";
            $total_normal_qty += $normaled_val[qty];

            // 반품입고 취소
            if( $cancel_re && $status == 8 )
            {
                $obj = new class_stock();
                $obj->set_stock( array( type       => 'retin',
                                        product_id => $normaled_val[product_id], 
                                        bad        => $cancel_re_bad,
                                        location   => 'Def',
                                        qty        => $normaled_val[qty] * -1,
                                        memo       => 'CS 반품입고취소',
                                        order_seq  => $normaled_val[seq] ) );
            }                    
        }
        $normal_log .= ">";

        // CTI
        if( $_REQUEST[callid] )
        {
            $query = "update cti_call_history set cancel_qty = cancel_qty - $total_normal_qty where callid='$_REQUEST[callid]'";
            mysql_query( $query, $connect);
        }
        
        // BCK
        if( $check_is_bck )
            $this->save_to_bck($old_seq_str);

        global $seq;
        $seq = $normaled_arr[0][seq];
        $sys_content = $normal_log . "<br>";
        
        if( $cancel_re )
            $content = "반품입고 취소 : 설정-" . ($cancel_re_bad ? "불량" : "정상") . $content;
        
        $this->csinsert8(0, 18, $content,$sys_content, $reason, $check_is_bck, $do_complete);
        
        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 상품 교환 - 옵션발주
    function change_product()
    {
        global $connect, $seq, $prdSeq, $product_id, $old_product_id, $options, $qty, $extra_money, $reason, $content, $match_type;
        global $restockin,$restockin_bad,$site_p,$envelop_p,$account_p,$notget_p,$trans_corp,$trans_no, $trans_who, $return_comp, $trans_price, $miss_match, $memo;
        
        $transaction = $this->begin("상품교환");

        // orders 정보
        $data_orders = $this->get_orders($seq);
        // 주문 목록
        $seq_list = $this->get_seq_list($seq, $data_orders[pack]);
        // order_products 데이터 정보
        $data_order_products = $this->get_order_products($prdSeq);
        // , 삭제
        $extra_money = str_replace(',','',$extra_money);
        $qty = str_replace(',','',$qty);
        
        if( !$extra_money )  $extra_money = 0;

        // 취소 확인        
        if( $data_order_products[order_cs] == 1 || 
            $data_order_products[order_cs] == 2 || 
            $data_order_products[order_cs] == 3 || 
            $data_order_products[order_cs] == 4 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
         
        // Lock Check
        $obj_lock = new class_lock(112, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }
        
        // CTI cancel_qty
        if( $_REQUEST[callid] )
        {
            $query_cti = "update cti_call_history set change_qty = change_qty + $qty where callid='$_REQUEST[callid]'";
            mysql_query( $query_cti, $connect );
        }

        // 오매칭 교환
        if( $miss_match == 1 )
        {
            // 매칭정보
            $query = "select * from order_products where seq='$prdSeq'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $seq_arr = array();
            
            // 매칭삭제
            if( $_SESSION[STOCK_MANAGE_USE] == 2 )
            {
                $query = "select seq 
                            from code_match
                           where shop_id = '$data[shop_id]' and
                                 shop_code = '$data[shop_product_id]'";
            }
            else
            {
                // 상품코드 안보는 매칭
                if( $_SESSION[MATCH_OPTION] == 1 )
                {
                    $query = "select seq 
                                from code_match
                               where shop_id = '$data[shop_id]' and
                                     shop_option = '$data[shop_options]'";
                }
                // 상품명 보는 매칭
                else if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
                {
                    // 상품명 구하기
                    $query_name = "select product_name from orders where seq=$data[order_seq]";
                    $result_name = mysql_query($query_name, $connect);
                    $data_name = mysql_fetch_assoc($result_name);
                    
                    $query = "select seq 
                                from code_match
                               where shop_id = '$data[shop_id]' and
                                     shop_code = '$data[shop_product_id]' and
                                     shop_product_name = '$data_name[product_name]' and
                                     shop_option = '$data[shop_options]'";
                }
                // 상품코드, 옵션 보는 매칭
                else
                {
                    $query = "select seq 
                                from code_match
                               where shop_id = '$data[shop_id]' and
                                     shop_code = '$data[shop_product_id]' and
                                     shop_option = '$data[shop_options]'";
                }
            }
            $result = mysql_query($query, $connect);
            while( $data_seq = mysql_fetch_assoc($result) )
                $seq_arr[] = $data_seq[seq];
                
            $seq_list = implode(",", $seq_arr);
    
            // 삭제로그
            class_CI00::match_delete_log2($seq_list);
    
            // 삭제 query
            $query = "delete from code_match where seq in ($seq_list)";
            mysql_query($query, $connect);


            
            // 접수/송장 교환처리
            $seq_arr_single = array();
            $seq_arr_pack = array();
            
            // 교환상품 정보
            $new_prd_info = class_product::get_info( $product_id );
            
            $query = "select a.seq a_seq,
                             a.pack a_pack, 
                             b.seq b_seq,
                             b.order_cs b_order_cs
                        from orders a
                            ,order_products b
                       where a.seq = b.order_seq and
                             a.status in (1,7) and
                             b.product_id = '$old_product_id' and
                             b.shop_id = '$data[shop_id]' ";

            // 매칭조건
            if( $_SESSION[STOCK_MANAGE_USE] == 2 )
            {
                $query .= " and b.shop_product_id = '$data[shop_product_id]' ";
            }
            else
            {
                // 상품코드 안보는 매칭
                if( $_SESSION[MATCH_OPTION] == 1 )
                {
                    $query .= " and b.shop_options = '$data[shop_options]' ";
                }
                // 상품명 보는 매칭
                else if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
                {
                    $query .= " and b.shop_product_id = '$data[shop_product_id]' 
                                and a.product_name = '$data_orders[product_name]' 
                                and b.shop_options = '$data[shop_options]'";
                }
                // 상품코드, 옵션 보는 매칭
                else
                {
                    $query .= " and b.shop_product_id = '$data[shop_product_id]' 
                                and b.shop_options = '$data[shop_options]' ";
                }
            }
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_change = "update order_products 
                                    set product_id='$product_id' 
                                       ,supply_id = '$new_prd_info[supply_code]'
                                       ,org_price = $new_prd_info[org_price] * qty
                                       ,shop_price = $new_prd_info[shop_price] * qty ";
                // 배송전 교환 cs
                if( $data[b_order_cs] == 0 )
                    $query_change .= " ,order_cs=6 ";

                $query_change .= " where seq=$data[b_seq]";
                mysql_query($query_change, $connect);

                // orders의 order_cs 수정
                $this->modify_orders_cs($data[a_seq]);
                $sys_content = "오매칭 자동교환[$old_product_id=>$product_id]";
                $this->csinsert10(0, 17, $content,$sys_content, $reason, 0, 0, $data[a_seq]);

                if( $data[a_pack] == 0 )
                    $seq_arr_single[] = $data[a_seq];
                else
                    $seq_arr_pack[] = $data[a_seq];
            }
            
            // 보류설정-단품
            $query_hold = "select * from orders where seq in (" . implode(",", $seq_arr_single) . ")";
            $result_hold = mysql_query($query_hold, $connect);
            while( $data_hold = mysql_fetch_assoc($result_hold) )
            {
                $seq_list = $data_hold[seq];
                if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_hold[status] == 7) || 
                     ($_SESSION[AUTO_HOLD] == 1 && $data_hold[status] == 1 && $data_hold[trans_key] > 0) )
                    $this->set_cs_hold(3,$seq_list);
            }
            
            // 보류설정-합포
            $seq_hold_list = implode(",", array_unique( $seq_arr_pack ));
            $query_hold = "select * from orders where seq in ($seq_hold_list)";
            $result_hold = mysql_query($query_hold, $connect);
            while( $data_hold = mysql_fetch_assoc($result_hold) )
            {
                $seq_list = "";
                $query_list = "select seq from orders where pack=$data_hold[pack]";
                $result_list = mysql_query($query_list, $connect);
                while( $data_list = mysql_fetch_assoc($result_list) )
                    $seq_list .= ($seq_list ? "," : "") . $data_list[seq];

                if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_hold[status] == 7) || 
                     ($_SESSION[AUTO_HOLD] == 1 && $data_hold[status] == 1 && $data_hold[trans_key] > 0) )
                    $this->set_cs_hold(3,$seq_list);
            }
        }

        // 부분교환 => order_products를 분리한다.
        // 배송전일 경우만 유효
        if( $data_order_products[qty] > $qty && $data_orders[status] < 8 )
        {
            // 교환되지 않을 상품 수량 변경
            $new_shop_price       = $data_order_products[shop_price]       * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            $new_extra_money      = $data_order_products[extra_money]      * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            $new_refund_price     = $data_order_products[refund_price]     * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            $new_org_price        = $data_order_products[org_price]        * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            $new_prd_amount       = $data_order_products[prd_amount]       * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            $new_prd_supply_price = $data_order_products[prd_supply_price] * ( $data_order_products[qty] - $qty ) / $data_order_products[qty];
            
            $query = "update order_products 
                         set qty              = qty-$qty, 
                             shop_price       = $new_shop_price, 
                             extra_money      = $new_extra_money, 
                             refund_price     = $new_refund_price, 
                             org_price        = $new_org_price, 
                             prd_amount       = $new_prd_amount, 
                             prd_supply_price = $new_prd_supply_price 
                       where seq = $prdSeq";
            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 1;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // order_products에 교환상품 추가
            $ins_query = "insert order_products set ";
            $i = 0;
            foreach ( $data_order_products as $key=>$value)
            {
                $i++;
                if ( $key == "seq" )  continue;
                
                if     ( $key == "qty"              )  $value  = $qty;
                else if( $key == "shop_price"       )  $value -= $new_shop_price;
                else if( $key == "extra_money"      )  $value -= $new_extra_money;
                else if( $key == "refund_price"     )  $value -= $new_refund_price;
                else if( $key == "org_price"        )  $value -= $new_org_price;
                else if( $key == "prd_amount"       )  $value -= $new_prd_amount;
                else if( $key == "prd_supply_price" )  $value -= $new_prd_supply_price;
                
                $ins_query .= "$key='$value'";
                if ( $i < count( $data_order_products ) ) $ins_query .= ",";
            }
            if( !mysql_query($ins_query, $connect) )
            {
                $val['error'] = 1;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // 새로 추가된 상품으로 $prdSeq를 바꾼다.
            $query_seq= "select seq from order_products where order_seq=$seq order by seq desc limit 1";
            $result_seq = mysql_query($query_seq, $connect);
            $data_seq = mysql_fetch_assoc($result_seq);
           
            // new $prdSeq
            $prdSeq = $data_seq[seq];
        }
        
        // 배송전
        if( $data_orders[status] < 8 )
            $order_cs = 6;
        // 배송후
        else
            $order_cs = 8;

        // 기존 상품정보 수정
        if( $data_orders[status] < 8 )
        {
            // 배송전 교환 => 상품 변경
            $new_shop_price  = $data_order_products[shop_price]  * $qty / $data_order_products[qty];
            $new_extra_money = $data_order_products[extra_money] * $qty / $data_order_products[qty];

            $query = "update order_products 
                         set order_cs    = $order_cs, 
                             product_id  = '$product_id',
                             supply_id   = " . class_product::get_supply_id($product_id) . ",
                             shop_price  = $new_shop_price + $extra_money,
                             extra_money = $new_extra_money + $extra_money,";

            // 상품매칭
            if( $_SESSION[STOCK_MANAGE_USE] == 2 )
            {
                $query .=   "shop_options = '$options',";
                debug("상품매칭??????????????");
            }
                             
            $query .=       "change_date = now()
                       where seq=$prdSeq";
                       
            
        }
        else
        {
            // 자동반품입고
            if( $restockin )
            {
                $obj = new class_stock();
                $obj->set_stock( array( type       => 'retin',
                                        product_id => $old_product_id, 
                                        bad        => $restockin_bad,
                                        location   => 'Def',
                                        qty        => $qty,
                                        memo       => 'CS 자동반품입고',
                                        order_seq  => $seq ) );
            }

            //*********************************
            // 배송교환, 반품정보
            //*********************************
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                $site_p    = (int)$site_p   ;   
                $envelop_p = (int)$envelop_p;
                $account_p = (int)$account_p;
                $notget_p  = (int)$notget_p ;
                $restockin = (int)$restockin;
                
                $log_seq = 0;
    
                if( $restockin > 0 )
                    $restockin_val = 1 + $restockin_bad;
                else
                    $restockin_val = 0;

                // 반품예정 있는지 확인
                $is_return = false;
                $query = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) )
                {
                    $data = mysql_fetch_assoc($result);
    
                    // 교환인 경우
                    if( $data[return_type] == 2 )
                    {
                        // 이전 반품정보 삭제처리
                        $query_return = "update return_money 
                                            set is_delete     = 1,
                                                delete_date   = now(),
                                                delete_worker = '$_SESSION[LOGIN_NAME]'";
                        $query_return .= " where seq = $data[seq]";
    
                        mysql_query($query_return, $connect);
    
                        // 로그 남기기
                        $query_return_log = "insert return_money_log
                                                set seq                = '$data[seq]',
                                                    log_type           = 'delete',
                                                    log_contents       = '교환처리 삭제',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);
    
                        // 반품정보 입력
                        $query_ret_insert = "insert return_money 
                                                set order_seq          = '$data[order_seq]',         
                                                    order_products_seq = '$data[order_products_seq]',
                                                    collect_date       = '$data[collect_date]',      
                                                    trans_date         = '$data[trans_date]',        
                                                    shop_id            = '$data[shop_id]',           
                                                    supply_id          = '$data[supply_id]',         
                                                    recv_name          = '$data[recv_name]',         
                                                    shop_product_id    = '$data[shop_product_id]',   
                                                    shop_product_name  = '$data[shop_product_name]', 
                                                    shop_options       = '$data[shop_options]',      
                                                    ez_product_id      = '$data[ez_product_id]',     
                                                    ez_product_name    = '$data[ez_product_name]',   
                                                    ez_options         = '$data[ez_options]',        
                                                    qty                = '$qty',               
                                                    org_trans_no       = '$data[org_trans_no]',      
                                                    return_type        = 2,       
                                                    cancel_type        = '',       
                                                    change_type        = '$reason',       
                                                    expect_site        = '$data[expect_site]',       
                                                    expect_envelop     = '$data[expect_envelop]',    
                                                    expect_account     = '$data[expect_account]',    
                                                    expect_trans_who   = '$data[expect_trans_who]',  
                                                    memo               = '$memo',  
                                                    return_site        = $site_p,      
                                                    return_envelop     = $envelop_p,   
                                                    return_account     = $account_p,   
                                                    return_notget      = $notget_p,    
                                                    return_trans_who   = '$trans_who', 
                                                    return_trans_corp  = '$trans_corp',
                                                    return_trans_no    = '$trans_no',  
                                                    restockin_auto     = '$restockin_val', 
                                                    is_expect          = '$data[is_expect]',         
                                                    expect_date        = '$data[expect_date]',       
                                                    expect_worker      = '$data[expect_worker]',     
                                                    is_return          = 1,         
                                                    return_date        = now(),       
                                                    return_worker      = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price = '$trans_price'";
 
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_ret_insert .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
        
                        mysql_query($query_ret_insert, $connect);
        
                        // 로그용 seq 구하기
                        $query = "select max(seq) max_seq
                                    from return_money
                                   where is_return = 1 and  
                                         is_delete = 0 and
                                         return_worker = '$_SESSION[LOGIN_NAME]'";
                        $result = mysql_query($query, $connect);
                        $data = mysql_fetch_assoc($result);
        
                        $log_seq = $data[max_seq];
                    }
                    // 단순 반품예정인 경우 (취소인 경우는 없다. 왜냐하면 취소인 경우에는 교환을 걸수 없으므로)
                    else
                    {
                        $query_return = "update return_money 
                                            set return_type       = 2,
                                                change_type       = '$reason',
                                                memo              = '$memo',
                                                return_site       = '$site_p',
                                                return_envelop    = '$envelop_p',
                                                return_account    = '$account_p',
                                                return_notget     = '$notget_p',
                                                return_trans_corp = '$trans_corp',
                                                return_trans_no   = '$trans_no',
                                                return_trans_who  = '$trans_who',
                                                restockin_auto    = '$restockin_val',
                                                qty               = '$qty',
                                                is_return         = 1,
                                                return_date       = now(),
                                                return_worker     = '$_SESSION[LOGIN_NAME]',
                                                return_trans_price = '$trans_price'";
        
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_return .= " ,is_complete       = 1,
                                                complete_date     = now(),
                                                complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
                        
                        $query_return .= " where seq = $data[seq]";
                        mysql_query($query_return, $connect);
                        
                        $log_seq = $data[seq];
                    }
                }
                // 반품정보가 없는 경우
                else
                {
                    // 반품정보
                    $query_ret_order = "select a.seq             a_seq,
                                               a.collect_date    a_collect_date,
                                               a.collect_time    a_collect_time,
                                               a.trans_date_pos  a_trans_date_pos,
                                               a.shop_id         a_shop_id,
                                               a.recv_name       a_recv_name,
                                               a.shop_product_id a_shop_product_id,
                                               a.product_name    a_product_name,
                                               a.options         a_options,
                                               a.trans_no        a_trans_no,
                                               b.seq             b_seq,
                                               b.supply_id       b_supply_id,
                                               b.qty             b_qty,
                                               c.product_id      c_product_id,
                                               c.name            c_name,
                                               c.options         c_options
                                          from orders a,
                                               order_products b,
                                               products c
                                         where a.seq = b.order_seq and
                                               b.product_id = c.product_id and
                                               b.seq = $prdSeq";
                    $result_ret_order = mysql_query($query_ret_order, $connect);
                    $data_ret_order = mysql_fetch_assoc($result_ret_order);
    
                    // 반품정보 입력
                    $query_ret_insert = "insert return_money 
                                            set order_seq          = '$data_ret_order[a_seq]',
                                                order_products_seq = '$data_ret_order[b_seq]',
                                                collect_date       = '$data_ret_order[a_collect_date] $data_ret_order[a_collect_time]',
                                                trans_date         = '$data_ret_order[a_trans_date_pos]',
                                                shop_id            = '$data_ret_order[a_shop_id]',
                                                supply_id          = '$data_ret_order[b_supply_id]',
                                                shop_product_id    = '$data_ret_order[a_shop_product_id]',
                                                ez_product_id      = '$data_ret_order[c_product_id]',
                                                recv_name          = '" . addslashes($data_ret_order[a_recv_name]   ) . "',
                                                shop_product_name  = '" . addslashes($data_ret_order[a_product_name]) . "',
                                                shop_options       = '" . addslashes($data_ret_order[a_options]     ) . "',
                                                ez_product_name    = '" . addslashes($data_ret_order[c_name]        ) . "',
                                                ez_options         = '" . addslashes($data_ret_order[c_options]     ) . "',
                                                qty                = '$qty',
                                                org_trans_no       = '$data_ret_order[a_trans_no]',
                                                return_type        = 2,
                                                cancel_type        = '',
                                                change_type        = '$reason',
                                                memo               = '$memo',
                                                return_site        = $site_p,
                                                return_envelop     = $envelop_p,
                                                return_account     = $account_p,
                                                return_notget      = $notget_p,
                                                return_trans_who   = '$trans_who',
                                                return_trans_corp  = '$trans_corp',
                                                return_trans_no    = '$trans_no',
                                                restockin_auto     = '$restockin_val',
                                                is_return          = 1,
                                                return_date        = now(),
                                                return_worker      = '$_SESSION[LOGIN_NAME]',
                                                return_trans_price = '$trans_price'";
    
                    // 반품택배비 정산완료
                    if( $return_comp )
                    {
                        $query_ret_insert .= " ,is_complete       = 1,
                                                complete_date     = now(),
                                                complete_worker   = '$_SESSION[LOGIN_NAME]'";
                    }
                    mysql_query($query_ret_insert, $connect);
    
                    // 로그 
                    $query = "select max(seq) max_seq
                                from return_money
                               where is_expect = 0 and
                                     is_return = 1 and  
                                     is_delete = 0 and
                                     return_worker = '$_SESSION[LOGIN_NAME]'";
                    $result = mysql_query($query, $connect);
                    $data = mysql_fetch_assoc($result);
    
                    $log_seq = $data[max_seq];
                }
    
                if( !$is_return )
                {
                    // 반품도착 로그
                    $log_contents = "";
                    if( $site_p )
                        $log_contents .= "사이트결제 : " . number_format($site_p) . " 원, ";
                    if( $envelop_p )
                        $log_contents .= "동봉 : " . number_format($envelop_p) . " 원, ";
                    if( $account_p )
                        $log_contents .= "계좌 : " . number_format($account_p) . " 원, ";
                    if( $notget_p )
                        $log_contents .= "미수 : " . number_format($notget_p) . " 원, ";
                    if( $trans_no )
                    {
                        $log_contents .= "택배사 : " . $this->get_trans_corp_name($trans_corp) . ", ";
                        $log_contents .= "송장번호 : $trans_no, ";
                        $log_contents .= "선착불 : " . ($trans_who ? "착불" : "선불") . ", ";
                    }
                    if( $restockin_val == 1 )
                        $log_contents .= "자동반품입고 : 설정-정상";
                    else if( $restockin_val == 2 )
                        $log_contents .= "자동반품입고 : 설정-$_SESSION[EXTRA_STOCK_TYPE]";

                    $query_return_log = "insert return_money_log
                                            set seq                = '$log_seq',
                                                log_type           = 'return',
                                                log_contents       = '$log_contents',
                                                log_date           = now(),
                                                log_worker         = '$_SESSION[LOGIN_NAME]'";
                    mysql_query($query_return_log, $connect);
        
                    // 반품택배비 정산완료 로그
                    if( $return_comp )
                    {
                        $query_return_log = "insert return_money_log
                                                set seq                = '$log_seq',
                                                    log_type           = 'complete',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);
                    }
                }
            }

            // 반품확인시 자동 sms 전송
            $this->send_return_sms($seq, $data_orders);
            // 배송후 교환 => 상품 변경 없음
            $query = "update order_products 
                         set order_cs    = $order_cs, 
                             change_date = now()
                       where seq=$prdSeq";
        }
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // 자동보류
        if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
             ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
            $this->set_cs_hold(3,$seq_list);
        
        // orders의 order_cs 수정
        if( $this->modify_orders_cs($seq) )
        {
            $val['error'] = 0;
            $_old_prd_info = class_product::get_info( $old_product_id );
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                if( $return_comp && $data_orders[status]==8 )
                    $sys_content = "<본상품:(" . $old_product_id . ")".$_old_prd_info[name]." ".$_old_prd_info[options]."><수량:" . $qty . "><추가금액:" . $extra_money . "><br>" . $log_contents . ", 반품정보 정산 : 완료<br>" ;
                else
                	$sys_content = "<본상품:(" . $old_product_id . ")".$_old_prd_info[name]." ".$_old_prd_info[options]."><수량:" . $qty . "><추가금액:" . $extra_money . "><br>" . $log_contents . "<br>";
                    
            }
            else
                $sys_content = "<본상품:(" . $old_product_id . ")".$_old_prd_info[name]." ".$_old_prd_info[options]."><수량:" . $qty . "><추가금액:" . $extra_money . ">";

            $this->csinsert8(0, 17, $content,$sys_content, $reason);
        }
        else
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        /////////////////////////////////////////////
        // 배송 상태일 경우 C 주문 생성
        if( $data_orders[status] == 8 )
        {
            // 원주문이 부분교환인지 전체교환인지 확인
            $change_all = true;
            if( $data_order_products[qty] > $qty )
                $change_all = false;
            else
            {
                $query = "select seq from order_products where order_seq=$seq and seq<>$prdSeq";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) > 0 )
                    $change_all = false;
            }
            
            // 배송비
            $query_trans_fee = "select trans_fee from products where product_id='$product_id'";        
            $result_trans_fee = mysql_query($query_trans_fee, $connect);
            $data_trans_fee = mysql_fetch_assoc($result_trans_fee);

            // 교환주문(orders) 추가
            $ins_query = "insert orders set ";
            $i = 0;
            foreach ( $data_orders as $key=>$value )
            {
                $i++;
                if( $key == "seq"             ||
                    $key == "trans_no"        ||
                    $key == "trans_corp"      ||
                    $key == "trans_date"      ||
                    $key == "trans_date_pos"  ||
                    $key == "refund_cs_date"  ||
                    $key == "gift"            ||
                    $key == "refund_date"        )  continue;
                
                // 2013-10-14 사은품 붙은 주문이 부분교환 처리되면서 생성 주문에 정산이 안들어가는 오류. 그래서 막음.
                /*
                if ( !$change_all )
                {
                    if ( $key == "amount"       )  $value = 0;
                    if ( $key == "supply_price" )  $value = 0;
                }
                */
                
                if ( $key == "order_id"     )  
                {
                    $value = "C" . $value;

                    // 2014-09-11 장경희
                    if( _DOMAIN_ == 'maru' && substr($value, -8) != "[교환]" )
                        $value .= "[교환]";
                        
                    $new_order_id = $value;
                }                
                
                if ( $key == "qty"          )  $value = $qty;
                if ( $key == "status"       )  $value = 1;
                if ( $key == "order_cs"     )  $value = 0;
                if ( $key == "pack_lock"    )  $value = 0;
                if ( $key == "collect_date" )  $value = date("Y-m-d");
                if ( $key == "collect_time" )  $value = date("H:i:s");
                if ( $key == "owner"        )  $value = $_SESSION[LOGIN_NAME];
                if ( $key == "pack"         )  $value = 0;
                if ( $key == "trans_fee"    )  $value = $data_trans_fee[trans_fee];
                if ( $key == "trans_key"    )  $value = 0;
                if ( $key == "org_seq"      )  $value = $seq;
                if ( $key == "cs_priority"  )  $value = 0;
                if ( $key == "part_seq"     )  $value = 0;
                if ( $key == "c_seq"        )  $value = ($data_orders[c_seq] ? $data_orders[c_seq] : ($data_orders[copy_seq] ? $data_orders[copy_seq] : $data_orders[seq]));
                if ( $key == "copy_seq"     )  $value = 0;
                
                // 배송후교환 자동으로 합포금지 설정
                if ( $key == "pack_lock"    )
                {
                    if ( $_SESSION[RETURN_PACK_LOCK] )  
                        $value = 1;
                    else
                        $value = 0;
                }
    
                // 배송비 선착불
                else if ( $key == "trans_who"    )
                {
                    if( $_SESSION[AFTER_CHANGE_WHO] == 0 )
                        $value = $value;
                    else if( $_SESSION[AFTER_CHANGE_WHO] == 1 )
                        $value = '선불';
                    else if( $_SESSION[AFTER_CHANGE_WHO] == 2 )
                        $value = '착불';
                }
    
                $ins_query .= "$key='$value'";
                if ( $i < count( $data_orders ) ) $ins_query .= ",";
            }
            if( !mysql_query($ins_query, $connect) )
            {
                $val['error'] = 3;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // 생성된 주문번호 가져오기
            $query_new = "select * from orders where status=1 and order_id='$new_order_id' and c_seq>0 order by seq desc limit 1";
            $result_new = mysql_query($query_new, $connect);
            $data_new = mysql_fetch_assoc($result_new);
            
            $new_seq = $data_new[seq];
            
            // 전화검색
            $this->inset_tel_info($new_seq, array($data_new[recv_tel],$data_new[recv_mobile],$data_new[order_tel],$data_new[order_mobile]));
            
            // 수량이 부분 교환인 경우, 교환된 수량 비율만큼 상품가를 조정한다.
            $new_shop_price = $data_order_products[shop_price] * $qty / $data_order_products[qty];

            // 일반발주
            if( $match_type == 2 )
                $shop_options = $options;
            else
                $shop_options = $data_order_products[shop_options];
            
            // 교환주문의 상품(order_products) 추가
            $query = "insert order_products
                         set order_seq       = '$new_seq',
                             product_id      = '$product_id', 
                             qty             = '$qty',
                             order_cs        = 0,
                             shop_id         = '$data_order_products[shop_id]',
                             shop_product_id = '$data_order_products[shop_product_id]',
                             shop_options    = '$shop_options',
                             status          = 1,
                             match_date      = now(),
                             supply_id       = '" . class_product::get_supply_id($product_id) . "',
                             shop_price      = $new_shop_price,
                             extra_money     = $data_order_products[extra_money] + $extra_money";
debug("배송후 교환 상품 추가 : " . $query);
            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 3;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            if( _DOMAIN_ == 'eshealthvill' || _DOMAIN_ == 'ezadmin' )
            {
                $seq = $new_seq;
                $this->csinsert8(0, 17, $content,$sys_content, $reason);
            }
        }
        
        // BCK
        if( $data_orders[is_bck] )
            $this->save_to_bck($seq_list);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 상품 교환 - 일반발주
   ///////////////////////////////////////
    // 상품 교환 - 일반발주
    function change_product2()
    {
        global $connect, $seq, $prdSeq, $qty, $order_option, $price, $reason, $content;
        global $site_p,$envelop_p,$account_p,$notget_p,$trans_corp,$trans_no, $trans_who, $return_comp, $trans_price, $memo;
        //$order_product
        $transaction = $this->begin("상품교환");

        // orders 정보
        $data_orders = $this->get_orders($seq);
        // 주문 목록
        $seq_list = $this->get_seq_list($seq, $data_orders[pack]);
        // order_products 데이터 정보
        $data_order_products = $this->get_order_products($prdSeq);

        // , 삭제
        $price = str_replace(',','',$price);
        $qty   = str_replace(',','',$qty);
        
        if( $price == '-' )  $price = 0;
        
        // 취소 확인        
        if( $data_order_products[order_cs] == 1 || 
            $data_order_products[order_cs] == 2 || 
            $data_order_products[order_cs] == 3 || 
            $data_order_products[order_cs] == 4 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(112, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 부분교환 => order_products를 분리한다.
        // 배송전일 경우만 유효
        if( $data_order_products[qty] > $qty && $data_orders[status] < 8 )
        {
            // 교환되지 않을 상품 수량 변경
            $query = "update order_products set qty = qty - $qty where seq=$prdSeq";
            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 1;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // order_products에 교환상품 추가
            $ins_query = "insert order_products set ";
            $i = 0;
            foreach ( $data_order_products as $key=>$value)
            {
                $i++;
                if ( $key == "seq" )  continue;

                if ( $key == "qty" )  $value=$qty;
    
                $ins_query .= "$key='$value'";
                if ( $i < count( $data_order_products ) ) $ins_query .= ",";
            }
            if( !mysql_query($ins_query, $connect) )
            {
                $val['error'] = 1;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // 새로 추가된 상품으로 $prdSeq를 바꾼다.
            $query_seq= "select seq from order_products where order_seq=$seq order by seq desc limit 1";
            $result_seq = mysql_query($query_seq, $connect);
            $data_seq = mysql_fetch_assoc($result_seq);
           
            // new $prdSeq
            $prdSeq = $data_seq[seq];
        }
        
        // 배송전
        if( $data_orders[status] < 8 )
            $order_cs = 6;
        // 배송후
        else
            $order_cs = 8;

        if( $price > 0 )
            $extra_money = ( $price - $data_order_products[prd_amount] ) * $qty;
        else
            $extra_money = 0;

debug("추가금액 : $extra_money = ( $price - $data_order_products[prd_amount] ) * $qty");

        // 변경상품 가격정보
        $price_arr = class_product::get_price_arr($product_id);
        
        // 기존 상품정보 수정
        if( $data_orders[status] < 8 )
        {
            // 배송전 교환 => 상품 변경
            $query = "update order_products 
                         set order_cs    = $order_cs, 
                             shop_price  = $price,
                             extra_money = extra_money + $extra_money,
                             shop_options= '$order_option',
                             change_date = now()
                       where seq=$prdSeq";                       
           
        }
        else
        {
            //*********************************
            // 배송교환, 반품정보
            //*********************************
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                $site_p    = (int)$site_p   ;   
                $envelop_p = (int)$envelop_p;
                $account_p = (int)$account_p;
                $notget_p  = (int)$notget_p ;
                $restockin = (int)$restockin;
                
                $log_seq = 0;
    
                // 반품예정 있는지 확인
                $is_return = false;
                $query = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) )
                {
                    $data = mysql_fetch_assoc($result);
    
                    // 교환인 경우
                    if( $data[return_type] == 2 )
                    {
                        // 이전 반품정보 삭제처리
                        $query_return = "update return_money 
                                            set is_delete     = 1,
                                                delete_date   = now(),
                                                delete_worker = '$_SESSION[LOGIN_NAME]'";
                        $query_return .= " where seq = $data[seq]";
    
                        mysql_query($query_return, $connect);
    
                        // 로그 남기기
                        $query_return_log = "insert return_money_log
                                                set seq                = '$data[seq]',
                                                    log_type           = 'delete',
                                                    log_contents       = '교환처리 삭제',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);
    
                        // 반품정보 입력
                        $query_ret_insert = "insert return_money 
                                                set order_seq          = '$data[order_seq]',         
                                                    order_products_seq = '$data[order_products_seq]',
                                                    collect_date       = '$data[collect_date]',      
                                                    trans_date         = '$data[trans_date]',        
                                                    shop_id            = '$data[shop_id]',           
                                                    supply_id          = '$data[supply_id]',         
                                                    recv_name          = '$data[recv_name]',         
                                                    shop_product_id    = '$data[shop_product_id]',   
                                                    shop_product_name  = '$data[shop_product_name]', 
                                                    shop_options       = '$data[shop_options]',      
                                                    ez_product_id      = '$data[ez_product_id]',     
                                                    ez_product_name    = '$data[ez_product_name]',   
                                                    ez_options         = '$data[ez_options]',        
                                                    qty                = '$data[qty]',               
                                                    org_trans_no       = '$data[org_trans_no]',      
                                                    return_type        = 2,       
                                                    cancel_type        = '',       
                                                    change_type        = '$reason',       
                                                    expect_site        = '$data[expect_site]',       
                                                    expect_envelop     = '$data[expect_envelop]',    
                                                    expect_account     = '$data[expect_account]',    
                                                    expect_trans_who   = '$data[expect_trans_who]',  
                                                    memo               = '$memo',
                                                    return_site        = $site_p,      
                                                    return_envelop     = $envelop_p,   
                                                    return_account     = $account_p,   
                                                    return_notget      = $notget_p,    
                                                    return_trans_who   = '$trans_who', 
                                                    return_trans_corp  = '$trans_corp',
                                                    return_trans_no    = '$trans_no',  
                                                    restockin_auto     = '$restockin', 
                                                    is_expect          = '$data[is_expect]',         
                                                    expect_date        = '$data[expect_date]',       
                                                    expect_worker      = '$data[expect_worker]',     
                                                    is_return          = 1,         
                                                    return_date        = now(),       
                                                    return_worker      = '$_SESSION[LOGIN_NAME]',
                                                    return_trans_price = '$trans_price'";
    
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_ret_insert .= " ,is_complete       = 1,
                                                    complete_date     = now(),
                                                    complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
        
                        mysql_query($query_ret_insert, $connect);
        
                        // 로그용 seq 구하기
                        $query = "select max(seq) max_seq
                                    from return_money
                                   where is_return = 1 and  
                                         is_delete = 0 and
                                         return_worker = '$_SESSION[LOGIN_NAME]'";
                        $result = mysql_query($query, $connect);
                        $data = mysql_fetch_assoc($result);
        
                        $log_seq = $data[max_seq];
                    }
                    // 단순 반품예정인 경우 (취소인 경우는 없다. 왜냐하면 취소인 경우에는 교환을 걸수 없으므로)
                    else
                    {
                        $query_return = "update return_money 
                                            set return_type       = 2,
                                                change_type       = '$reason',
                                                memo              = '$memo',
                                                return_site       = '$site_p',
                                                return_envelop    = '$envelop_p',
                                                return_account    = '$account_p',
                                                return_notget     = '$notget_p',
                                                return_trans_corp = '$trans_corp',
                                                return_trans_no   = '$trans_no',
                                                return_trans_who  = '$trans_who',
                                                restockin_auto    = '$restockin',
                                                is_return         = 1,
                                                return_date       = now(),
                                                return_worker     = '$_SESSION[LOGIN_NAME]',
                                                return_trans_price = '$trans_price'";
        
                        // 반품택배비 정산완료
                        if( $return_comp )
                        {
                            $query_return .= " ,is_complete       = 1,
                                                complete_date     = now(),
                                                complete_worker   = '$_SESSION[LOGIN_NAME]'";
                        }
                        
                        $query_return .= " where seq = $data[seq]";
                        mysql_query($query_return, $connect);
                        
                        $log_seq = $data[seq];
                    }
                }
                // 반품정보가 없는 경우
                else
                {
                    // 반품정보
                    $query_ret_order = "select a.seq             a_seq,
                                               a.collect_date    a_collect_date,
                                               a.collect_time    a_collect_time,
                                               a.trans_date_pos  a_trans_date_pos,
                                               a.shop_id         a_shop_id,
                                               a.recv_name       a_recv_name,
                                               a.shop_product_id a_shop_product_id,
                                               a.product_name    a_product_name,
                                               a.options         a_options,
                                               a.trans_no        a_trans_no,
                                               b.seq             b_seq,
                                               b.supply_id       b_supply_id,
                                               b.qty             b_qty,
                                               c.product_id      c_product_id,
                                               c.name            c_name,
                                               c.options         c_options
                                          from orders a,
                                               order_products b,
                                               products c
                                         where a.seq = b.order_seq and
                                               b.product_id = c.product_id and
                                               b.seq = $prdSeq";
                    $result_ret_order = mysql_query($query_ret_order, $connect);
                    $data_ret_order = mysql_fetch_assoc($result_ret_order);
    
                    // 반품정보 입력
                    $query_ret_insert = "insert return_money 
                                            set order_seq          = '$data_ret_order[a_seq]',
                                                order_products_seq = '$data_ret_order[b_seq]',
                                                collect_date       = '$data_ret_order[a_collect_date] $data_ret_order[a_collect_time]',
                                                trans_date         = '$data_ret_order[a_trans_date_pos]',
                                                shop_id            = '$data_ret_order[a_shop_id]',
                                                supply_id          = '$data_ret_order[b_supply_id]',
                                                shop_product_id    = '$data_ret_order[a_shop_product_id]',
                                                ez_product_id      = '$data_ret_order[c_product_id]',
                                                recv_name          = '" . addslashes($data_ret_order[a_recv_name]   ) . "',
                                                shop_product_name  = '" . addslashes($data_ret_order[a_product_name]) . "',
                                                shop_options       = '" . addslashes($data_ret_order[a_options]     ) . "',
                                                ez_product_name    = '" . addslashes($data_ret_order[c_name]        ) . "',
                                                ez_options         = '" . addslashes($data_ret_order[c_options]     ) . "',
                                                qty                = '$data_ret_order[b_qty]',
                                                org_trans_no       = '$data_ret_order[a_trans_no]',
                                                return_type        = 2,
                                                cancel_type        = '',
                                                change_type        = '$reason',
                                                memo               = '$memo',
                                                return_site        = $site_p,
                                                return_envelop     = $envelop_p,
                                                return_account     = $account_p,
                                                return_notget      = $notget_p,
                                                return_trans_who   = '$trans_who',
                                                return_trans_corp  = '$trans_corp',
                                                return_trans_no    = '$trans_no',
                                                restockin_auto     = '$restockin',
                                                is_return          = 1,
                                                return_date        = now(),
                                                return_worker      = '$_SESSION[LOGIN_NAME]',
                                                return_trans_price = '$trans_price'";
    
                    // 반품택배비 정산완료
                    if( $return_comp )
                    {
                        $query_ret_insert .= " ,is_complete       = 1,
                                                complete_date     = now(),
                                                complete_worker   = '$_SESSION[LOGIN_NAME]'";
                    }
                    mysql_query($query_ret_insert, $connect);
    
                    // 로그 
                    $query = "select max(seq) max_seq
                                from return_money
                               where is_expect = 0 and
                                     is_return = 1 and  
                                     is_delete = 0 and
                                     return_worker = '$_SESSION[LOGIN_NAME]'";
                    $result = mysql_query($query, $connect);
                    $data = mysql_fetch_assoc($result);
    
                    $log_seq = $data[max_seq];
                }
    
                if( !$is_return )
                {
                    // 반품도착 로그
                    $log_contents = "";
                    if( $site_p )
                        $log_contents .= "사이트결제 : " . number_format($site_p) . " 원, ";
                    if( $envelop_p )
                        $log_contents .= "동봉 : " . number_format($envelop_p) . " 원, ";
                    if( $account_p )
                        $log_contents .= "계좌 : " . number_format($account_p) . " 원, ";
                    if( $notget_p )
                        $log_contents .= "미수 : " . number_format($notget_p) . " 원, ";
                    if( $trans_no )
                    {
                        $log_contents .= "택배사 : " . $this->get_trans_corp_name($trans_corp) . ", ";
                        $log_contents .= "송장번호 : $trans_no, ";
                        $log_contents .= "선착불 : " . ($trans_who ? "착불" : "선불") . ", ";
                    }
                    if( $restockin )
                        $log_contents .= "자동반품입고 : 설정";
                    $query_return_log = "insert return_money_log
                                            set seq                = '$log_seq',
                                                log_type           = 'return',
                                                log_contents       = '$log_contents',
                                                log_date           = now(),
                                                log_worker         = '$_SESSION[LOGIN_NAME]'";
                    mysql_query($query_return_log, $connect);
        
                    // 반품택배비 정산완료 로그
                    if( $return_comp )
                    {
                        $query_return_log = "insert return_money_log
                                                set seq                = '$log_seq',
                                                    log_type           = 'complete',
                                                    log_date           = now(),
                                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
                        mysql_query($query_return_log, $connect);
                    }
                }
            }
            
            // 반품확인시 자동 sms 전송
            $this->send_return_sms($seq, $data_orders);
            
            // 배송후 교환 => 상품 변경 없음
            $query = "update order_products 
                         set order_cs    = $order_cs, 
                             change_date = now()
                       where seq=$prdSeq";
        }
        if( !mysql_query($query, $connect) )
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // 자동보류
        if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
             ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
        $this->set_cs_hold(3,$seq_list);
    
        // orders의 order_cs 수정
        if( $this->modify_orders_cs($seq) )
        {
            $val['error'] = 0;
            if( $_SESSION[USE_RETURN_MONEY] )
            {
                if( $return_comp )
                    $sys_content = "<수량:" . $qty . "><원옵션:" . $data_order_products[shop_options] . "><추가금액:" . $extra_money . "><br>" . $log_contents . ", 반품정보 정산 : 완료<br>";
                else
                    $sys_content = "<수량:" . $qty . "><원옵션:" . $data_order_products[shop_options] . "><추가금액:" . $extra_money . "><br>" . $log_contents . "<br>";
            }
            else
                $sys_content = "<수량:" . $qty . "><원옵션:" . $data_order_products[shop_options] . "><추가금액:" . $extra_money . ">";
            $this->csinsert8(0, 17, $content,$sys_content, $reason,'',$data_orders[is_bck]);
        }
        else
        {
            $val['error'] = 1;

            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }

        /////////////////////////////////////////////
        // 배송 상태일 경우 C 주문 생성
        if( $data_orders[status] == 8 )
        {
            // 배송후 전체교환인지 부분교환인지 확인
            $data_c = $this->get_orders($seq);
                
            // 생성할 주문의 amount와 supply_price를 구한다.
            if( $data_c[order_cs] == 7 )  // 전체교환
            {
                $new_amount      = $data_c[amount]       + $data_c[extra_money];
                $new_supply_price = $data_c[supply_price] + $data_c[extra_money];
                $new_extra_money = $price - $new_amount;
            }
            else
            {
                $new_amount      = $price;
                $new_supply_price = $price;
                $new_extra_money = 0;
            }
                
            // 교환주문(orders) 추가
            $ins_query = "insert orders set ";
            $i = 0;
            foreach ( $data_orders as $key=>$value)
            {
                $i++;
                if( $key == "seq"             ||
                    $key == "trans_no"        ||
                    $key == "trans_corp"      ||
                    $key == "trans_date"      ||
                    $key == "trans_date_pos"  ||
                    $key == "refund_cs_date"  ||
                    $key == "gift"            ||
                    $key == "refund_date"        )  continue;
                
                if ( $key == "order_id"     )  $value = "C" . $value;
                if ( $key == "qty"          )  $value = $qty;
                if ( $key == "amount"       )  $value = $new_amount;
                if ( $key == "supply_price" )  $value = $new_supply_price;
                if ( $key == "status"       )  $value = 1;
                if ( $key == "order_cs"     )  $value = 0;
                if ( $key == "collect_date" )  $value = date("Y-m-d");
                if ( $key == "collect_time" )  $value = date("H:i:s");
                if ( $key == "owner"        )  $value = $_SESSION[LOGIN_NAME];
                if ( $key == "pack"         )  $value = 0;
                if ( $key == "extra_money"  )  $value = $new_extra_money;
                if ( $key == "trans_key"    )  $value = 0;
                if ( $key == "c_seq"        )  $value = ($data_orders[c_seq] ? $data_orders[c_seq] : ($data_orders[copy_seq] ? $data_orders[copy_seq] : $data_orders[seq]));
                if ( $key == "copy_seq"     )  $value = 0;
    
                // 배송후교환 자동으로 합포금지 설정
                if ( $key == "pack_lock"    )
                {
                    if ( $_SESSION[RETURN_PACK_LOCK] )  
                        $value = 1;
                    else
                        $value = 0;
                }

                // 배송비 선착불
                else if ( $key == "trans_who"    )
                {
                    if( $_SESSION[AFTER_CHANGE_WHO] == 0 )
                        $value = $value;
                    else if( $_SESSION[AFTER_CHANGE_WHO] == 1 )
                        $value = '선불';
                    else if( $_SESSION[AFTER_CHANGE_WHO] == 2 )
                        $value = '착불';
                }
    
                $ins_query .= "$key='$value'";
                if ( $i < count( $data_orders ) ) $ins_query .= ",";
            }
            if( !mysql_query($ins_query, $connect) )
            {
                $val['error'] = 3;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
            
            // 생성된 주문번호 가져오기
            $query_new = "select * from orders where status=1 and order_id='C".$data_orders[order_id]."' and c_seq>0 order by seq desc limit 1";
            $result_new = mysql_query($query_new, $connect);
            $data_new = mysql_fetch_assoc($result_new);
            
            $new_seq = $data_new[seq];

            // 전화검색
            $this->inset_tel_info($new_seq, array($data_new[recv_tel],$data_new[recv_mobile],$data_new[order_tel],$data_new[order_mobile]));
            
            // 교환주문의 상품(order_products) 추가
            $query = "insert order_products
                         set order_seq       = '$new_seq',
                             product_id      = '$data_order_products[product_id]', 
                             qty             = '$qty',
                             order_cs        = 0,
                             shop_id         = '$data_order_products[shop_id]',
                             shop_product_id = '$data_order_products[shop_product_id]',
                             shop_options    = '$order_option',
                             prd_amount      = '$data_order_products[prd_amount]',
                             prd_supply_price= '$data_order_products[prd_supply_price]',
                             status          = 1,
                             supply_id       = 20001,
                             shop_price      = '" . $price . "'";

            if( !mysql_query($query, $connect) )
            {
                $val['error'] = 3;

                // Lock End
                if( !$obj_lock->set_end(&$msg) )
                {
                    $val['error'] = -9;
                    $val['lock_msg'] = $msg;
                }

                echo json_encode( $val );
                return;
            }
        }
        
        // BCK
        if( $data_orders[is_bck] )
            $this->save_to_bck($seq_list);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 상품 추가 - 옵션매칭
    function add_product()
    {
        global $connect, $seq, $prdSeq, $option_ver, $p_info_json, $content, $sys_content;

        $transaction = $this->begin("상품추가");

        // 주문정보
        $data_orders = $this->get_orders($seq);
        
        
        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);

        // 배송 확인
        if( $data_orders[status] == 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // 합포금지
        if( $data_orders[pack_lock] == 1 )
        {
            $val['error'] = 3;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(113, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $p_arr = json_decode( stripslashes( $p_info_json ) );
        
        $price_all = 0;
        for( $i=0; $i< count($p_arr); $i++ )
        {
            $p_info = (array)$p_arr[$i];

            $product_id = $p_info[product_id];
            $qty        = $p_info[qty];
            $price      = $p_info[price] * $p_info[qty];
            $is_gift    = $p_info[gift];
            
            $products_infos       = $this->get_products( $product_id );
            $org_price = $products_infos[org_price] * $qty;
            
            // 추가금액 총합
            $price_all += $price;
            
            if( !$product_id ) continue;
            
            // option
            if( $option_ver == 1 )
                $option = $data_orders[options];
            else
                $option = str_replace( "'", "\\'", $p_info[option]);

            // 사은품일 경우 가격은 0
            $price = ( $is_gift ? 0 : $price );
            
            // 상품 추가
            $query = "insert order_products 
                         set order_seq       = '$seq',
                             product_id      = '$product_id',
                             qty             = '$qty',
                             org_price       = '$org_price',
                             order_cs        = 0,
                             status          = 1,
                             match_date      = now(),
                             shop_id         = '$data_orders[shop_id]',
                             shop_product_id = '$data_orders[shop_product_id]',
                             shop_options    = '$option',
                             supply_id       = '" . class_product::get_supply_id($product_id) . "',
                             shop_price      = '$price',
                             extra_money     = '$price',
                             is_gift         = '$is_gift',
                             product_type    = 1,
                             match_type      = 5,
                             match_worker    = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query, $connect);
        }
        
        if( $price_all > 0 )
        {
            $query_order_extra = "update orders set extra_money = extra_money + $price_all where seq=$seq";
            mysql_query($query_order_extra, $connect);
        }
        
        // 상품추가 성공
        $val['error'] = 0;
        $this->csinsert8( 0, 19, $content , $sys_content,'', $data_orders[is_bck] );

        // 자동보류
        if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
             ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
        {
            // 추가 될 주문에 order_cs 여부에 따라 보류 설정
            $this->modify_orders_cs($seq);            
            $this->modify_orders_hold($seq_list);
        }

        // BCK
        if( $data_orders[is_bck] )
            $this->save_to_bck($seq_list);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 상품 추가 - 일반매칭
    function add_product2()
    {
        global $connect, $seq, $prdSeq, $option, $qty, $price, $content;
        
        $transaction = $this->begin("상품추가");

        // 배송 확인
        $data_orders = $this->get_orders($seq);
        // 주문 목록
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);
        // order_products 데이터 정보
        $data_order_products = $this->get_order_products($prdSeq);

        $price = str_replace(',','',$price);
        
        if( $data_orders[status] == 8 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }

        // 합포금지
        if( $data_orders[pack_lock] == 1 )
        {
            $val['error'] = 3;
            echo json_encode( $val );
            return;
        }

        // Lock Check
        $obj_lock = new class_lock(113, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 상품 추가
        $query = "insert order_products 
                     set order_seq       = '$seq',
                         product_id      = '$data_order_products[product_id]',
                         qty             = '$qty',
                         order_cs        = 0,
                         status          = 1,
                         match_date      = now(),
                         shop_options    = '$option',
                         supply_id       = '$data_order_products[supply_id]',
                         shop_price      = '$price',
                         extra_money     = '$price',
                         is_gift         = '$data_order_products[is_gift]',
                         product_type    = 1";
        if( mysql_query($query, $connect) )
        {
            $val['error'] = 0;
            $this->csinsert3(0, 19, $content,'',$data_orders[is_bck]);

            // 자동보류
            if ( ($_SESSION[AUTO_HOLD] >= 1 && $data_orders[status] == 7) || 
                 ($_SESSION[AUTO_HOLD] == 1 && $data_orders[status] == 1 && $data_orders[trans_key] > 0) )
            {
                // 추가 될 주문에 order_cs 여부에 따라 보류 설정
                $this->modify_orders_cs($seq);            
                $this->modify_orders_hold($seq_list);
            }
        }
        else
            $val['error'] = 1;

        // BCK
        if( $data_orders[is_bck] )
            $this->save_to_bck($seq_list);

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 매칭 삭제
    function delete_match()
    {
        global $connect, $seq, $prdSeq, $content;
        
        $transaction = $this->begin("매칭삭제");

        // 매칭정보
        $query = "select * from order_products where seq='$prdSeq'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $seq_arr = array();
        
        // 매칭삭제
        if( $_SESSION[STOCK_MANAGE_USE] == 2 )
        {
            $query = "select seq 
                        from code_match
                       where shop_id = '$data[shop_id]' and
                             shop_code = '$data[shop_product_id]'";
        }
        else
        {
            // 상품코드 안보는 매칭
            if( $_SESSION[MATCH_OPTION] == 1 )
            {
                $query = "select seq 
                            from code_match
                           where shop_id = '$data[shop_id]' and
                                 shop_option = '$data[shop_options]'";
            }
            // 상품코드 안보는 매칭
            else if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
            {
                // 상품명 구하기
                $query_name = "select product_name from orders where seq=$data[order_seq]";
                $result_name = mysql_query($query_name, $connect);
                $data_name = mysql_fetch_assoc($result_name);
                
                $query = "select seq 
                            from code_match
                           where shop_id = '$data[shop_id]' and
                                 shop_code = '$data[shop_product_id]' and
                                 shop_product_name = '$data_name[product_name]' and
                                 shop_option = '$data[shop_options]'";
            }
            // 상품코드, 옵션 보는 매칭
            else
            {
                $query = "select seq 
                            from code_match
                           where shop_id = '$data[shop_id]' and
                                 shop_code = '$data[shop_product_id]' and
                                 shop_option = '$data[shop_options]'";
            }
        }
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $seq_arr[] = $data[seq];
            
        $seq_list = implode(",", $seq_arr);

        // 삭제로그
        class_CI00::match_delete_log2($seq_list);

        // 삭제 query
        if( $seq_list > '' )
        {
            $query = "delete from code_match where seq in ($seq_list)";
            if( mysql_query($query, $connect) )
            {
                $val['error'] = 0;
                $this->csinsert3(0, 20, $content);
            }
            else
                $val['error'] = 1;
        }
        else
            $val['error'] = 2;
        
        echo json_encode( $val );
    }

    ///////////////////////////////////////
    // 반품예정 설정
    function return_expect()
    {
        global $connect, $seq, $prdSeq, $r_trans_who, $ret_site, $ret_envelop, $ret_account, $content, $exp_trans_price;

        $val = array();
        
        // 주문정보
        $query = "select a.status          a_status, 
                         a.collect_date    a_collect_date,
                         a.collect_time    a_collect_time,
                         a.trans_date_pos  a_trans_date_pos,
                         a.shop_id         a_shop_id,
                         b.supply_id       b_supply_id,
                         a.recv_name       a_recv_name,
                         a.shop_product_id a_shop_product_id,
                         a.product_name    a_product_name,
                         a.options         a_options,
                         b.product_id      b_product_id,
                         c.name            c_name,
                         c.options         c_options,
                         b.qty             b_qty,
                         a.trans_no        a_trans_no,
                         b.order_cs        b_order_cs 
                    from orders a, 
                         order_products b,
                         products c
                   where a.seq = b.order_seq and 
                         b.product_id = c.product_id and 
                         b.seq = $prdSeq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        // 배송 아님
        if( $data[a_status] != 8 )
        {
            $val['error'] = 1;
            echo json_encode( $val );
            return;
        }
        // 취소 상품
        if( $data[b_order_cs] == 1 || $data[b_order_cs] == 2 || $data[b_order_cs] == 3 || $data[b_order_cs] == 4 )
        {
            $val['error'] = 2;
            echo json_encode( $val );
            return;
        }
        // 배송후 교환
        // 2014-08-21 장경희. 일단 배송후교환도 반품예정 가능하게.
        // if( $data[b_order_cs] == 7 || $data[b_order_cs] == 8 )
        // {
        //     $val['error'] = 3;
        //     echo json_encode( $val );
        //     return;
        // }

        // 이미 반품예정 정보 있는지 확인
        $query_return = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
        $result_return = mysql_query($query_return, $connect);
        if( mysql_num_rows($result_return) )
        {
            $data_return = mysql_fetch_assoc($result_return);
            
            // 이미 반품처리된 상품
            if( $data_return['is_return'] == 1 )
            {
                $val['error'] = 4;
                echo json_encode( $val );
                return;
            }

            // 반품예정 update
            $query = "update return_money 
                         set expect_site        = '$ret_site',
                             expect_envelop     = '$ret_envelop',
                             expect_account     = '$ret_account',
                             expect_trans_who   = '$r_trans_who',
                             return_trans_price = '$exp_trans_price'
                       where seq = $data_return[seq]";
            mysql_query($query, $connect);

            // 반품예정 로그
            $log_contents  = "사이트결제 : " . number_format($ret_site) . " 원, ";
            $log_contents .= "동봉 : " . number_format($ret_envelop) . " 원, ";
            $log_contents .= "계좌 : " . number_format($ret_account) . " 원, ";

            if( $r_trans_who == 0 )
                $log_contents .= "선착불 : 선불(고객선결제)";
            else if( $r_trans_who == 1 )
                $log_contents .= "선착불 : 착불(계약택배)";
            else if( $r_trans_who == 2 )
                $log_contents .= "선착불 : 착불(타택배) - " . number_format($exp_trans_price) . " 원";

            $query_return_log = "insert return_money_log
                                    set seq                = '$data_return[seq]',
                                        log_type           = 'update',
                                        log_contents       = '$log_contents',
                                        log_date           = now(),
                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query_return_log, $connect);
        }
        
        // 없으면 새로 insert
        else
        {
            // 반품예정
            $query = "insert return_money 
                         set order_seq          = '$seq',
                             order_products_seq = '$prdSeq',
                             collect_date       = '$data[a_collect_date] $data[a_collect_time]',
                             trans_date         = '$data[a_trans_date_pos]',
                             shop_id            = '$data[a_shop_id]',
                             supply_id          = '$data[b_supply_id]',
                             shop_product_id    = '$data[a_shop_product_id]',
                             ez_product_id      = '$data[b_product_id]',
                             recv_name          = '" . addslashes($data[a_recv_name]   ) . "',
                             shop_product_name  = '" . addslashes($data[a_product_name]) . "',
                             shop_options       = '" . addslashes($data[a_options]     ) . "',
                             ez_product_name    = '" . addslashes($data[c_name]        ) . "',
                             ez_options         = '" . addslashes($data[c_options]     ) . "',
                             qty                = '$data[b_qty]',
                             org_trans_no       = '$data[a_trans_no]',
                             expect_site        = '$ret_site',
                             expect_envelop     = '$ret_envelop',
                             expect_account     = '$ret_account',
                             expect_trans_who   = '$r_trans_who',
                             return_trans_price = '$exp_trans_price',
                             is_expect          = 1,
                             expect_date        = now(),
                             expect_worker      = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query, $connect);
            
            // 로그 
            $query = "select seq
                        from return_money
                       where is_expect = 1 and
                             is_return = 0 and  
                             is_delete = 0 and
                             expect_worker = '$_SESSION[LOGIN_NAME]'
                       order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 반품예정 로그
            $log_contents  = "사이트결제 : " . number_format($ret_site) . " 원, ";
            $log_contents .= "동봉 : " . number_format($ret_envelop) . " 원, ";
            $log_contents .= "계좌 : " . number_format($ret_account) . " 원, ";

            if( $r_trans_who == 0 )
                $log_contents .= "선착불 : 선불(고객선결제)";
            else if( $r_trans_who == 1 )
                $log_contents .= "선착불 : 착불(계약택배)";
            else if( $r_trans_who == 2 )
                $log_contents .= "선착불 : 착불(타택배) - " . number_format($exp_trans_price) . " 원";

            $query_return_log = "insert return_money_log
                                    set seq                = '$data[seq]',
                                        log_type           = 'expect',
                                        log_contents       = '$log_contents',
                                        log_date           = now(),
                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query_return_log, $connect);
        }

        $val['error'] = 0;
        
        $content_info  = "";
        if( $ret_site )
            $content_info .= "반품예정 사이트결제 : " . number_format($ret_site) . " 원, ";
        if( $ret_envelop )
            $content_info .= "동봉 : " . number_format($ret_envelop) . " 원, ";
        if( $ret_account )
            $content_info .= "계좌 : " . number_format($ret_account) . " 원, ";

        if( $r_trans_who == 0 )
            $content_info .= "선착불 : 선불(고객선결제)";
        else if( $r_trans_who == 1 )
            $content_info .= "선착불 : 착불(계약택배)";
        else if( $r_trans_who == 2 )
            $content_info .= "선착불 : 착불(타택배) - " . number_format($exp_trans_price) . " 원";

        
        $this->csinsert8(0, 35, $content, $content_info);

        echo json_encode( $val );
    }

    // 반품예정 정보확인
    function get_return_expect()
    {
        global $connect, $seq, $prdSeq;
        
        $query = "select * from return_money where order_products_seq = $prdSeq and is_delete = 0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            $data['is_exist'] = 1;
        }
        else
            $data['is_exist'] = 0;
        $data['base_trans_code'] = $_SESSION[BASE_TRANS_CODE];

        echo json_encode($data);
    }
    
    // 반품 정보확인 (회수 팝업에서 사용)
    function get_return_info($prdSeq)
    {
        global $connect;
        
        $query = "select * from return_money where order_products_seq = $prdSeq and is_delete = 0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            $val = array(
                "info"      => 1,
                "trans_who" => $data[expect_trans_who],
                "site"      => $data[expect_site],
                "envelop"   => $data[expect_envelop],
                "account"   => $data[expect_account]
            );
        }
        else
        {
            $val = array(
                "info"      => 0
            );
        }

        return $val;
    }
    
    // 반품 정보설정 (회수 팝업에서 사용)
    //    
    //    $info = array(
    //        "seq"       => 관리번호,       
    //        "prdSeq"    => order_products 관리번호,
    //        "site"      => 사이트,
    //        "envelop"   => 동봉,
    //        "account"   => 계좌,
    //        "trans_who" => 선착불(0:선불, 1:착불)
    //    )
    //
    function set_return_info($info)
    {
        global $connect;
        
        $query = "select * from return_money where order_products_seq = $info[prdSeq] and is_delete = 0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            // 이미 도착했으면 설정하지 않는다.
            if( !$data[is_return] )
            {
                $query_update = "update return_money
                                    set site = '$info[site]',
                                        envelop = '$info[envelop]',
                                        account = '$info[account]',
                                        expect_trans_who = '$info[trans_who]'
                                  where seq = $data[seq] ";
                mysql_query($query_update, $connect);

                // 반품예정 로그
                $log_contents  = "사이트결제 : " . number_format($infop[site]) . " 원, ";
                $log_contents .= "동봉 : " . number_format($info[envelop]) . " 원, ";
                $log_contents .= "계좌 : " . number_format($info[account]) . " 원, ";
                $log_contents .= "선착불 : " . ($info[trans_who] ? "착불" : "선불");
                $log_contents .= " [회수]";
                $query_return_log = "insert return_money_log
                                        set seq                = '$data[seq]',
                                            log_type           = 'update',
                                            log_contents       = '$log_contents',
                                            log_date           = now(),
                                            log_worker         = '$_SESSION[LOGIN_NAME]'";
                mysql_query($query_return_log, $connect);
            }
        }
        
        // 기존 반품정보 없으면 새로 생성
        else
        {
            // 주문정보
            $query_orders = "select a.collect_date      a_collect_date   ,   
                                    a.collect_time      a_collect_time   ,   
                                    a.trans_date_pos    a_trans_date_pos ,  
                                    a.shop_id           a_shop_id        ,  
                                    a.shop_product_id   a_shop_product_id,  
                                    a.recv_name         a_recv_name      ,  
                                    a.product_name      a_product_name   ,
                                    a.options           a_options        ,
                                    a.trans_no          a_trans_no       ,
                                    b.product_id        b_product_id     ,
                                    b.qty               b_qty            ,  
                                    c.supply_code       c_supply_code    ,  
                                    c.name              c_product_name   ,
                                    c.options           c_options
                               from orders a, 
                                    order_products b,
                                    products c
                              where a.seq = b.order_seq and
                                    b.product_id = c.product_id and 
                                    b.seq = $info[prdSeq]";
            $result_orders = mysql_query($query_orders, $connect);
            $data_orders = mysql_fetch_assoc($result_orders);

            // 반품예정
            $query = "insert return_money 
                         set order_seq          = '$info[seq]',
                             order_products_seq = '$info[prdSeq]',
                             collect_date       = '$data_orders[a_collect_date] $data_orders[a_collect_time]',
                             trans_date         = '$data_orders[a_trans_date_pos]',
                             shop_id            = '$data_orders[a_shop_id]',
                             supply_id          = '$data_orders[c_supply_code]',
                             shop_product_id    = '$data_orders[a_shop_product_id]',
                             ez_product_id      = '$data_orders[b_product_id]',
                             recv_name          = '" . addslashes($data_orders[a_recv_name]   ) . "',
                             shop_product_name  = '" . addslashes($data_orders[a_product_name]) . "',
                             shop_options       = '" . addslashes($data_orders[a_options]     ) . "',
                             ez_product_name    = '" . addslashes($data_orders[c_product_name]) . "',
                             ez_options         = '" . addslashes($data_orders[c_options]     ) . "',
                             qty                = '$data_orders[b_qty]',
                             org_trans_no       = '$data_orders[a_trans_no]',
                             expect_site        = '$info[site]',
                             expect_envelop     = '$info[envelop]',
                             expect_account     = '$info[account]',
                             expect_trans_who   = '$info[trans_who]',
                             is_expect          = 1,
                             expect_date        = now(),
                             expect_worker      = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query, $connect);

            // 로그 
            $query = "select max(seq) max_seq
                        from return_money
                       where is_expect = 1 and
                             is_return = 0 and  
                             is_delete = 0 and
                             expect_worker = '$_SESSION[LOGIN_NAME]'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 반품예정 로그
            $log_contents  = "사이트결제 : " . number_format($info[site]   ) . " 원, ";
            $log_contents .= "동봉 : "       . number_format($info[envelop]) . " 원, ";
            $log_contents .= "계좌 : "       . number_format($info[account]) . " 원, ";
            $log_contents .= "선착불 : " . ($info[trans_who] ? "착불" : "선불");
            $log_contents .= " [회수]";
            $query_return_log = "insert return_money_log
                                    set seq                = '$data[max_seq]',
                                        log_type           = 'expect',
                                        log_contents       = '$log_contents',
                                        log_date           = now(),
                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query_return_log, $connect);
        }

        return $val;
    }
    
    // 미송 설정
    function set_misong()
    {
        global $connect, $seq, $prdSeq, $content, $lock;
        
        // Lock Check
        $obj_lock = new class_lock(116, $seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $val = array();
        $val['error'] = 1;
        
        // 이미 미송 설정한주문인지 확인
        $query = "select misong from order_products where seq=$prdSeq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[misong] == 1 )
        {
            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }
    
            echo json_encode( $val );
            exit;
        }
        
        $transaction = $this->begin("미송설정");

        // orders 정보
        $data_orders = $this->get_orders($seq);
        // 주문 목록
        $seq_list = $this->get_seq_list($seq, $data_orders[pack]);
        // order_products 데이터 정보
        $data_order_products = $this->get_order_products($prdSeq);
        
        // 원 주문 미송설정, no_stock 설정
        $query = "update order_products set misong = 1, no_stock=1 where seq=$prdSeq";
        mysql_query($query, $connect);

        // 원주문 이미 배송일 경우, 재고 되돌리기
        if( $data_orders[status] == 8 )
        {
            $obj = new class_stock();
            $obj->set_stock( array( type       => 'trans',
                                    product_id => $data_order_products[product_id], 
                                    bad        => 0,
                                    location   => 'Def',
                                    qty        => -1 * $data_order_products[qty],
                                    memo       => 'cs 미송설정',
                                    order_seq  => $data_order_products[order_seq] ) );
        }
        
        // 교환주문(orders) 추가
        $ins_query = "insert orders set ";
        $i = 0;
        foreach ( $data_orders as $key=>$value)
        {
            $i++;
            if( $key == "seq"             ||
                $key == "trans_no"        ||
                $key == "trans_corp"      ||
                $key == "trans_date"      ||
                $key == "trans_date_pos"  ||
                $key == "prepay_cnt"      ||
                $key == "prepay_price"    ||
                $key == "priority"        || 
                $key == "hold"            || 
                $key == "extra_money"     || 
                $key == "cross_change"    || 
                $key == "refund_price"    || 
                $key == "auto_count"      || 
                $key == "cs_priority"     || 
                $key == "trans_key"       || 
                $key == "order_type"      || 
                $key == "trans_fee"        )  continue; 

            if ( $key == "amount"       )  $value = 0;
            if ( $key == "supply_price" )  $value = 0;
            if ( $key == "order_id"     )  $value = "M" . $value;
            if ( $key == "status"       )  $value = 1;
            if ( $key == "order_cs"     )  $value = 0;
            if ( $key == "collect_date" )  $value = date("Y-m-d");
            if ( $key == "collect_time" )  $value = date("H:i:s");
            if ( $key == "owner"        )  $value = $_SESSION[LOGIN_NAME];
            if ( $key == "pack"         )  $value = 0;
            if ( $key == "trans_who"    )  $value = "선불";
            if ( $key == "trans_fee"    )  $value = 0;
            if ( $key == "auto_trans"   )  $value = 1;

            if ( $key == "pack_lock" && $lock )  $value = 1;
            if ( $key == "order_type"   )  $value = 3;  // 배송후 교환
            if ( $key == "org_seq"      )  $value = $seq;
            if ( $key == "part_seq"     )  $value = 0;
            if ( $key == "c_seq"        )  $value = 0;
            if ( $key == "copy_seq"     )  $value = 0;

            // 미송일
            if( $key == "code30" ) $value = substr($data_orders[trans_date_pos],0,10);

            if( $key == "code11" ) $value = 0;
            if( $key == "code12" ) $value = 0;
            if( $key == "code13" ) $value = 0;
            if( $key == "code14" ) $value = 0;
            if( $key == "code15" ) $value = 0;
            if( $key == "code16" ) $value = 0;
            if( $key == "code17" ) $value = 0;
            if( $key == "code18" ) $value = 0;
            if( $key == "code19" ) $value = 0;
            if( $key == "code20" ) $value = 0;
            if( $key == "code31" ) $value = 0;
            if( $key == "code32" ) $value = 0;
            if( $key == "code33" ) $value = 0;
            if( $key == "code34" ) $value = 0;
            if( $key == "code35" ) $value = 0;
            if( $key == "code36" ) $value = 0;
            if( $key == "code37" ) $value = 0;
            if( $key == "code38" ) $value = 0;
            if( $key == "code39" ) $value = 0;
            if( $key == "code40" ) $value = 0;

            $ins_query .= "$key='$value'";
            if ( $i < count( $data_orders ) ) $ins_query .= ",";
        }
        mysql_query($ins_query, $connect);
        
        // 생성된 주문번호 가져오기
        $query_new = "select * from orders where order_id = 'M$data_orders[order_id]' order by seq desc limit 1";
        $result_new = mysql_query($query_new, $connect);
        $data_new = mysql_fetch_assoc($result_new);
        
        $new_seq = $data_new[seq];

        $this->inset_tel_info($new_seq, array($data_new[recv_tel],$data_new[recv_mobile],$data_new[order_tel],$data_new[order_mobile]));            
        
        // 교환주문의 상품(order_products) 추가
        $product_id = $data_order_products[product_id];
        $query = "insert order_products
                     set order_seq       = '$new_seq',
                         product_id      = '$product_id', 
                         qty             = '$data_order_products[qty]',
                         order_cs        = 0,
                         shop_id         = '$data_order_products[shop_id]',
                         shop_product_id = '$data_order_products[shop_product_id]',
                         shop_options    = '$data_order_products[shop_options]',
                         status          = 1,
                         supply_id       = '" . class_product::get_supply_id($product_id) . "',
                         shop_price      = '$data_order_products[shop_id]',
                         extra_money     = '$data_order_products[extra_money]',
                         misong          = 2";
        mysql_query($query, $connect);

        $this->csinsert3(0, 21, $content);
        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    // 주문 번호 정보 설정 및 변경
    // date: 2006.12.18 - jk.ryu
    function change_order_id()
    {
      global $connect, $seq, $order_id;

      $query = "select order_id from orders where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $str = "주문번호 변경 : " .  $data[order_id] . " -> $order_id";
      $transaction = $this->begin( $str );

echo $data[order_id];

      $query = "update orders set order_id='$order_id' where seq='$seq'";
      mysql_query ( $query , $connect );
      $this->end( $transaction );
echo "주문 정보 변경완료"; 
      exit;
    }

    
    // 전체 송장 삭제
    // date: 2006.8.28 - jk.ryu
    function del_trans_all()
    {
      global $connect, $seq;

      $query = "select trans_no, trans_date,status,pack,seq from orders where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data   = mysql_fetch_array ( $result );        
      $content     = "전체 송장삭제 /stat:$data[status]/L:$_SESSION[LOGIN_LEVEL]/ $data[trans_no] / $data[trans_date]";

      $transaction = $this->begin($content);
      $this->csinsert2( $seq, $content ); 

      // 합포인지 여부 check
      // 송장이 삭제 되도 합포는 풀리지 않는다
      $query = "update orders set trans_corp = null, 
                                  trans_no=null, 
                                  trans_date=null, 
                                  trans_date_pos=null, 
                                  status='1'";

        // =====
        // bug fix at 2007.11.22 - jk
        if ( $_SESSION[USE_3PL] )
            $query .= " , warehouse=null ";

        // 배송이면 삭제 하지 않는다
        if ( $_SESSION[LOGIN_LEVEL] >= 8 )
        {
            if ( $data[pack] )
                 $query .= " where pack='$data[pack]'";
            else
                 $query .= " where seq='$data[seq]'";
        }
        else
        {
            if ( $data[pack] )
                 $query .= " where status<>8 and pack='$data[pack]'";
            else
                 $query .= " where status<>8 and seq='$data[seq]'";
        }

        //debug ( $query );

        mysql_query ( $query , $connect );

        //////////////////////////////////////
        // 3pl용 정보
        // 작업 중
        if ( $_SESSION[USE_3PL] and mysql_affected_rows() > 0 )
        {
            $obj = new class_3pl();
            $obj->del_trans_all( $data[pack] ? $data[pack] : $data[seq] );
        }
      
        echo "전체 송장 삭제";
        $this->end( $transaction );
        exit;
    }


    // 주문 취소
    // Date: 2007.2.8 - jk.ryu
    function del_order( $seq= 0 )
    {

      // 권한 check
      if ( $_SESSION["LOGIN_LEVEL"] < 8 )
      {
          echo "권한이 없습니다 - 관리자에게 문의 하십시요";
          exit;
      }

      global $connect;
      if ( $seq == 0 )
        global $seq;

      $transaction    = $this->begin(" 주문 삭제 $seq");

      // pack = seq가 합포 취소될 경우 pack 번호를 바꿔야 함
      $query = "select count(*) cnt from orders where pack=seq and seq='$seq'";
      $result = mysql_query($query, $connect );
      $data = mysql_fetch_array ( $result );

      // 묶여 있는 다른 seq를 찾아 온다
      if ( $data[cnt] )
      {
        $query = "select seq from orders where pack <> seq and pack = '$seq'";
        $result = mysql_query($query, $connect );
        $data = mysql_fetch_array ( $result );
        $new_pack = $data[seq]; 

        // pack을 update한다 
        $query = "update orders set pack=$new_pack where pack='$seq'";
        mysql_query ( $query, $connect );

        // transacrion의 data도 변경..
        $query = "update transaction set target_id=$new_pack and target_id='$seq'";
        mysql_query ( $query, $connect );
      }

      $query = "delete from orders where seq='$seq'";
      mysql_query ( $query, $connect );
      echo "주문 삭제 완료";
    }


    // 합포 취소
    // Date: 2006.8.24 - jk.ryu
    function unpack( $seq= 0 )
    {
      global $connect;
      if ( $seq == 0 )
        global $seq;

      if ( $_SESSION[USE_3PL] )
          $obj = new class_3pl();

      $transaction    = $this->begin(" 합포 취소 $seq");

      ////////////////////////////////////////////////////////
      // 합포 대표 번호가 합포 취소될 경우..
      // pack = seq가 합포 취소될 경우 pack 번호를 바꿔야 함
      $query = "select recv_name,seq,pack from orders where seq='$seq'";
      $result = mysql_query($query, $connect ) or die( mysql_error() );
      $data = mysql_fetch_array ( $result );
      $recv_name = $data[recv_name];
      $_pack  = $data[pack];        // pack 번호

//debug ( "[unpack] $query / $recv_name" );

      // 묶여 있는 다른 seq를 찾아 온다
      if ( $data[seq] == $data[pack] )
      {
        $query = "select seq,recv_name from orders where pack <> seq and pack = $seq";
        $result = mysql_query($query, $connect );
        $data = mysql_fetch_array ( $result );
        $new_pack  = $data[seq]; 

        // pack을 update한다 
        $new_pack = $new_pack ? $new_pack : 0;
        $query = "update orders set pack=$new_pack where pack=$seq";
        mysql_query ( $query, $connect );

        /////////////////////////////////////
        // 3pl data sync        
        if ( $_SESSION[USE_3PL] )
        {
            $old_pack = $seq;
            $obj->sync_pack( $old_pack, $new_pack);
        }
      }

      // 실제 합포 취소 실행..so simple
      $query = "update orders set pack=null where seq='$seq'";
//debug ( "[unpack] $query" );
      mysql_query ( $query, $connect );

      // 합포 풀린 seq와 새로운 합포 번호가 필요함

      /////////////////////////////////////
      // 3pl data sync        
      if ( $_SESSION[USE_3PL] )
            $obj->remove_pack( $seq );

        $val = array();
        $val["msg"]       = "합포 취소 완료";
        $val["pack"]      = $new_pack ? $new_pack : $_pack;
        $val["unpack_seq"] = $seq;
        $val["recv_name"] =  $recv_name;
        echo json_encode( $val );
    }





    ///////////////////////////////////////////
    // 상품과 옵션을 함께 변경함
    // date: 2006.8.10 - jk.ryu
    // 배송전, 배송후
    // 배송전 교환요청과 배송후 교환요청으로 나눌 수 있음
    // 1. 상태 체크
    // 2. cs_info에 정보 입력
    // 3. orders 정보 update
    function change_option_string()
    {
        // $new_product_id가 신규로 추가 됨
        global $connect, $new_product_id, $change_option, $seq, $qty, $change_supply_id,$change_shop_price,$org_product_id;

        $_new_seq="";
        $_org_qty = 0;
        $_new_qty = $qty;
        $_new_product_id = $new_product_id;
        $change_option =  $change_option;

        // date: 2006.9.12 - jk.ryu
        // step 1. 교환 가능 여부 check - 취소나 교환 상태 주문은 이 부분을 탈 수 없음

        // step 2. 전체 교환 여부
        $query   = "select * from orders where seq='$seq'";
        $result  = mysql_query ( $query, $connect );
        $data    = mysql_fetch_array ( $result );

        $infos   = class_product::get_product_infos( $data[product_id] );
        $content = "변경 전 상품 정보:" . $infos[name]  . "/" . $infos[option];

        //////////////////////////////////////////////////////////////////////////////////////
        // 2008.2.12 - jk
        // 묶음 상품일 경우 교환 check
        // 2008.3.3 ~ 4 upgrade 로직 수정함
        // 두개의 묶음 상품을 동일한 상품으로 수정 불가.

        // 배송이 아닐 경우에만 조건 추가 : 2008.12.18-jk
        if ( $data[status] != 8 )
        {
            if ( $data[packed] )
            {
                $pos = strpos($data[pack_list], $new_product_id);
                if ( $pos === false )
                {
                    $pack_list = str_replace( $org_product_id, $new_product_id, $data[pack_list] ); 
                }
                else
                {
                    echo "묶음 상품은 동일한 상품으로 변경 불가 - 관리자에게 연락하십시요";
                    exit;
                }
            }
        }
        $part_cancel   = $this->get_part_cancel_count( $seq );
        $org_qty       = $data[qty] - $part_cancel;
        $_org_qty      = $org_qty;

        // 오류 !!!
        if ( $qty > $org_qty )
        {
          echo "$qty / $org_qty / $part_cancel 변경 개수가 원 주문의 개수보다 큽니다";
          exit;
        }


        // step 2. 전체 교환의 경우
        if ( $org_qty == $qty )
        {
          ////////////////////////////////////////////////////////////////
          //
          // step 2.1 동일한 공급처 일 경우 처리
          //
//$this->root_debug( "하부:" . $_SESSION[VENDORING] );

          if ( $data[supply_id] == $change_supply_id or $_SESSION[VENDORING] == 0)
          {
            $transaction = $this->begin(" 상품 교환 [code:$seq]");
            echo "동일한 공급처"; 

//$this->root_debug( "동일 " );
            //////////////////////////////////////////////////////
            //
            // step 2.1.1 배송일 경우 신규 주문 생성
            //            원 주문은 교환 상태 
            if ( $data[status] == 8 )
            {
              // 배송 후 교환 요청
              $order_cs=6; 
              $this->change_status2( $seq, $data[status], $order_cs ); 

              // 신규 주문 생성
              $_new_seq = $this->copy_order_info($seq, $new_product_id, $change_supply_id, $change_option, $qty,$change_shop_price,$packed );
              echo "변경 완료";
              $content .= "배송 후 주문 정보 교환요청 신 주문 생성됨"; 

            }
            //////////////////////////////////////////////////////
            //
            // step 2.1.2 배송하지 않았을 경우 => 변경
            //        product_id, supply_id, options 만 변경
            else
            {
              $product_name = class_D::get_product_name($new_product_id);

              // 배송 전 교환 요청
              if ( $_SESSION[STOCK_MANAGE_USE] )
              {
                  $query = "update orders set product_id = '$new_product_id',
                                              supply_id  = '$change_supply_id',
                                              shop_price = '$change_shop_price',
                                              order_cs   = 5,
                                                 pack_list  = '$pack_list'
                                        where seq        = '$seq'";
              }
              else
              {
                  $query = "update orders 
                               set product_id   = '$new_product_id',
                                   supply_id    = '$change_supply_id',
                                   product_name = '$product_name',
                                   options      = '$change_option',
                                   shop_price   = '$change_shop_price',
                                   order_cs     = 5,
                                   pack_list    = '$pack_list'
                             where seq          = '$seq'";
              }
//debug ( "[교환] $query " );
              mysql_query ( $query, $connect);
              echo "변경 완료";
              $content .= "배송 전 주문 정보 교환"; 

            }
          }

          ////////////////////////////////////////////////////////////////
          //
          // step 2.2 동일하지 않은 공급처일 경우 처리
          // vendor version일 경우는 취소 후 주문 생성
          // 부분 교환은 vendor가 같고 다르고를 떠나서 신규 주문이 생성되야 함 - 상품이 다르기 때문임
          //
          else
          {
            $transaction = $this->begin(" 부분 상품 교환  [code:$seq]");
            echo "다른 공급처 $data[supply_id] / $change_supply_id";

            // 하부업체 버젼일 경우
            //
            if ( $_SESSION[VENDORING] )
            {
              $this->part_cancel_action($seq, $status, $qty);          

              // 배송 후 교환 요청
              if ( $data[status] == 8 ) 
                $order_cs=6; 
              else 
                $order_cs = 5;

              $this->change_status2( $seq, $data[status], $order_cs ); 

              // 신규 주문 생성
              $_new_seq = $this->copy_order_info($seq, $new_product_id, $change_supply_id, $change_option, $qty, $change_shop_price,$packed );
              $content .= "부분 교환 완료"; 
            }

            // 하부업체 버젼이 아닐 경우
            // -> 정보 변경
            else
            {
              $transaction = $this->begin(" 교환 [code:$seq] ");
              $product_name = class_D::get_product_name($new_product_id);

              // 배송 전 교환 요청
              $query = "update orders set product_name='" . addslashes($product_name) . "',
                                          product_id='$new_product_id',
                                          options = '". addslashes($change_option) . "',
                                          shop_price = '$change_shop_price',
                                          order_cs = 5,
                                          packed='$packed',
                                          pack_list=null
                                     where seq='$seq'";
              mysql_query ( $query, $connect);
              echo "변경 완료";
         
              $content .= "주문 정보 교환 완료"; 
            }
          }
        }

        /////////////////////////////////////////////////////////////////
        // 
        // step 4. 전체 교환이 아닌 경우
        //
        else
        {
          echo "부분 교환";
          $transaction = $this->begin(" 부분 교환 [code:$seq] ");
          $this->part_cancel_action($seq, $status, $qty);          
          $part_cancel = 1; // true

          // 신규 주문 생성
          $_new_seq = $this->copy_order_info($seq, $new_product_id, $change_supply_id, $change_option, $qty, $change_shop_price, $packed );
          $this->change_status2( $seq, $data[status], $order_cs ); 
          echo " 완료";
          $content = "부분 교환 완료 신규 주문 생성됨"; 
        }

        $this->csinsert2( $seq, $content );

        // 
        // 교환 보류 처리 hold값을 5로 처리
        // 2008.5.20 - jk
        $this->set_hold2( $seq, 4 );

         //==========================
        // 3pl 교환 처리       
        // 2007.11.17 - jk
        if ( $_SESSION[USE_3PL] )
        {
           $_obj = new class_3pl();
//debug ( "[change 3pl] seq: $seq, org_qty: $_org_qty, new_product_id: $_new_product_id , new_qty: $_new_qty, new_seq: $_new_seq " );

            if ( $data[packed] )
            {
               //$_obj->change_packed_product( $seq, $_org_qty, $_new_product_id, $_new_qty, $_new_seq, $org_product_id, $data[pack_list] );
               $_obj->change_packed_product2( $seq );
            }
            else
               $_obj->change_product( $seq, $_org_qty, $_new_product_id, $_new_qty, $_new_seq, $org_product_id );
        }
exit;

    }

    // 주문 정보를 복사해서 새로운 주문 생성
    function copy_order_info($seq, $new_product_id, $new_supply_id, $change_option, $qty, $shop_price=0 , $packed=0)
    {
       global $connect;

       // status, product_id, options, qty, collect_date
       $items = array ( "order_id", "shop_id","shop_product_id","pay_type","amount","shop_price","supply_price",
                        "trans_price", "trans_who", "order_date", "order_time", "pay_date", "order_name", "order_tel",
                        "order_mobile", "order_zip", "order_address", "recv_name", "recv_tel", "recv_mobile", "recv_zip",
                        "recv_address", "org_price", "o_shop_price", "o_supply_price", "o_org_price" , "recv_mobile2" ); 

       $query = "select * from orders where seq='$seq'";

// debug( "new cs1: $query \n" );

       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array( $result );


       $query = "insert into orders set ";
       $i = 0;
       foreach ( $items as $item )
       {
         $query .= $i ? "," : "";
         $query .= "$item='";

         if ( $item == "order_id" )
           $query .= "C";

         if ( $item == "recv_mobile2" )
         {
             $numbers = split( "-" , $data[recv_mobile] );
             $query .= $numbers[1] . "-" . $numbers[2] . "'";
         }
         else if ( $item == "shop_price" )
         {
             if ( $shop_price )
             {
                 echo "[판매금액 변경] ";
                 $query .= $shop_price . "'";
             }
             else
                 $query .= $data[$item] . "'";
         }
         else
             $query .= $data[$item] . "'";

         $i++;
       }

       $product_name = class_D::get_product_name($new_product_id);

       // 교환 발주 생성 status: 2
       $query .= ", product_id='$new_product_id', product_name='$product_name', supply_id='$new_supply_id', 
                    options='$change_option', qty='$qty', org_seq='$seq', packed='$packed', pack_list=null, collect_date=Now(),status=2,owner='" . $_SESSION[LOGIN_NAME] . "'";

       //debug( "new cs2: $query \n" );
// echo $query;

       mysql_query ( $query, $connect );

        // 최근 등록된 seq값을 return함
         $query = "select max(seq) seq from orders";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        /////////////////////////////////
        // 3pl 로직 누락
        // 배송 요청시 C붙은 주문은 전송된다. 이 부분에 없어도 됨

        return $data[seq];
    }

    ///////////////////////////////////////////
    // 교환을 위한 상품 검색
    // date: 2006.8.8 - jk.ryu
    function search_product()
    {
        global $connect, $product_name, $product_option, $search_option, $method;

        $product_name =  $product_name;
        $product_option =  $product_option;
        $product_option = str_replace(" ", "%", $product_option );

        $query = "select * from products where name like '%$product_name%' ";

        if ( $search_option )
            $query .= " and org_id <> '' ";

        if ( $product_option )
                $query .= "and options like '%$product_option%' ";

        if ( $_SESSION[USE_3PL] )
            $query .= " and (org_id <> '' or packed=1) ";

        $query .= " and is_delete=0 limit 100";

        $result = mysql_query ( $query, $connect );

echo "<table width=100% align=center border=0 cellpadding=0 cellspacing=0>
        <tr><td align=center width=300>상품명</td><td align=center width=200>옵션</td><td align=center>업체</td><td width=40>&nbsp;</td></tr>";

        $bgcolor[1] = "E6EDFD";
        $bgcolor[0] = "ffffff";
        $i = 1;
        while ( $data = mysql_fetch_array ( $result ) )
        {
          $l = $i % 2;

          $supply_name = class_vendor::get_name($data[supply_code]);

          echo "<tr bgcolor=" . $bgcolor[$l] . ">
                <td width=40% height=25>&nbsp; $i.[" . $data[product_id] . "] $data[name] </td>
                <td width=30% alt='$data[options]'> "; 

          echo $this->cutstr2($data[options],30);

          echo " </td>
                <td width=20% align=center> $supply_name </td>
                <td width=10% align=right>
                  <a href=\"javascript:set_product";

          echo $method;

          echo "('$data[product_id]','$data[name]', '$data[supply_code]', '" . str_replace("\"", "", $data[options] ). "','$data[packed]')\"><img src="._IMG_SERVER_."/images/btn_select.gif border=0></a>&nbsp;</td></tr>";
          $i++;
        }

echo "</table>";
    }

    ///////////////////////////////////////////
    // 합포 상태의 주문정보를 보여준다.
    // date: 2006.8.3 - jk.ryu
    function get_packlist()
    {
        global $pack, $connect;

        // 묶음 상품인지 여부를 파악해야 함
        $query = "select pack,order_id from orders where seq='$pack'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        $query = "select packed, pack_list, order_id, product_id, seq, options, qty, supply_id,order_cs, shop_id,
                           collect_date,status,order_cs,trans_corp,trans_no, shop_price
                      from orders 
                     where";
        
        
        // pack == null일 경우가 있음
        // pack에 값이 있을 경우
        if ( $data[pack] )
        {
            $pack = $data[pack];
            $query .= " pack = '$pack' ";
        }
        else
            $query .= " seq  = '$pack' ";

        $query .= "  order by order_cs desc";

        $result = mysql_query ( $query, $connect );

        echo "<table width=100% align=center border=0 cellpadding=0 cellspacing=0 id='table_pack_list'>";

        $cnt = 0;
        $tot_price = 0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $cnt++;

            // 묶음 상품일 경우
            if ( $data[packed] )
                $tot_price = $this->disp_pack_row( $data, &$tot_price, $cnt );
            else
                $this->disp_row( $data, &$tot_price, $data[product_id], $cnt );
        }    

        echo "<tr><td colspan=10 align=right>금액 <span class=red>" . number_format($tot_price) . "</span>원 </td><td>&nbsp;</td></tr>";

        echo "</table>";
    }

    // 묶음 row 출력
    function disp_pack_row( $data, $cnt )
    {
        global $connect;
        $arr_product_id = split( ",", $data[pack_list] );

        foreach ( $arr_product_id as $product_id )
        {
            $this->disp_row( $data, &$tot_price, $product_id , $cnt );
        }
        return $data[shop_price] * $data[qty] ;
    }

    ////////////////////////////////////////////////////
    // row 출력
    function disp_row( $data, &$tot_price, $product_id='', $cnt )
    {
        $product_id  = $product_id ? $product_id : $data[product_id];
            $infos       = class_product::get_product_infos( $product_id );
            $name        = $infos["name"];
            $option      = $infos["option"];   
            $enable_sale = $infos["enable_sale"] ? "" : "<span class=red>품절</span>";
             
        if ( $_SESSION[STOCK_MANAGE_USE] )
        $str_option  = $option ? $option : $data[options];
        else
            $str_option  = $data[options];
        $supply_name = class_vendor::get_name($data[supply_id]);
        $shop_name = htmlspecialchars( class_C::get_shop_name($data[shop_id]) );
        $str_status  = $this->get_order_status($data[status], 0);
        $str_cs      = $this->get_order_cs($data[order_cs]);
        $qty         = $data[qty] - $this->get_part_cancel_count( $data[seq] );

        if ( $data[packed] )
            $bg = "f8f822";
        else
            $bg = "ffffff";
        echo "<tr bgcolor='$bg' onClick='javascript:clicked(this, \"$data[seq]\",\"$product_id\")' onMouseOver='javascript:click_ready3(this)'>
                  <td height=23 width=14 align=center>
                    <a href=\"javascript:unpack('$data[seq]')\">
                    <img src="._IMG_SERVER_."/images/btn_x.gif alt='합포해지' onMouseOver=\"rollup (this, 'btn_x')\" onMouseOut=\"rolldown(this, 'btn_x')\"></a></td>
                  <td height=23 width=20 align=center>$cnt";

        if ( $data[packed] )
            echo "<img src='"._IMG_SERVER_."/images/R_hap.gif'>";

        echo "</td>
                  <td height=23 width=110 align=center>Code: $data[seq]</td>
                  <td height=23 width=70 align=center>$shop_name</td>
                  <td align=center width=100 align=center>$data[collect_date]</td>
                  <td height=23 width=60 align=center>$str_status&nbsp;$str_cs</td>
                  <td>$enable_sale</td>
                  <td align=center width=40 align=center>$qty 개</td>
                  <td width=400>";

        if ( $data[packed] )
            echo "<a href=javascript:get_detail_pack('$data[seq]','$product_id')";
        else
            echo "<a href=javascript:get_detail2('$data[seq]')";
        echo "> $name</a> </td>";

        if ( $_SESSION[USE_3PL] )
            echo "<td width=130>$product_id</td>";

        if ( _DOMAIN_ == "nak21" )
            echo "<td>&nbsp; " . $data[order_id] . "</td>";

            echo "<td>&nbsp;$str_option</td>
                  <td>&nbsp;" . number_format( $data[shop_price] ) . "원</td>
                  <td align=center width=140>$supply_name</td>
            </tr>"; 

        if ( $data[order_cs] != 1
        and  $data[order_cs] != 2
        and  $data[order_cs] != 3
        and  $data[order_cs] != 4
        and  $data[order_cs] != 12
        )
        $tot_price = $tot_price + ( $data[shop_price] * $data[qty] );
    }
    ///////////////////////////////////////////
    // 검색 2
    // 결과를 xml로 return함
    // date: 2006.8.7 - jk.ryu
    // 
    function query2()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type, $order_status,$shop_id;
        global $supply_code, $query_type;
        global $keyword, $pack_only;

        // pack_only
        if (!$start_date) 
            $start_date = date('Y-m-d', strtotime('-60 day'));
        $end_date = $_REQUEST["end_date"];
        $keyword  =  $keyword;

        /////////////////////////////////////////////////////////////////
        // 주문 조회
        if ( $query_type == "order" )
        {
           echo "<table width=100% border=0 cellpadding=0 cellspacing=0>";

            ///////////////////////////////////////////////////////////
            // 합포일 경우와 일반일 경우 분리함
            // 미송건 검색
            if ( $order_status == 98 || $order_status == 97 || $order_status == 96 || $order_status == 95 )
                $r_cs = class_notyet::notyet_list();        
            else
                $this->cs_list( &$total_rows, &$r_cs , 1);

            $i=1;
            while ( $data = mysql_fetch_array ( $r_cs ) )
            {
               $name         = class_product::get_product_name($data[product_id]);
               $status       = $this->get_order_status2($data[status]);
               $csStatus     = $this->get_order_cs2($data[order_cs]);
               $customerInfo = $data[order_name] . "/" . $data[recv_name];
               $customerInfo = htmlspecialchars( $customerInfo );
               $shop_name    = htmlspecialchars( class_C::get_shop_name($data[shop_id]) );
 
               if ( $data[pack] )
                 $order_id = "[ " . $data[order_id] . " ]";
               else
                 $order_id = $data[order_id];
 
               echo "
                <tr onClick=\"javascript:click_action(this, $data[seq], '$data[product_id]')\" onMouseOver=\"javascript:click_ready(this)\">
                         <td width=60> $data[collect_date] </td>
                         <td width=50> ($data[seq]) </td>
                         <td width=50> $shop_name </td>
                         <td width=110> $order_id </td>
                         <td>&nbsp;" . htmlspecialchars( $data[recv_address] ) . "</td>
                         <td width=200>$data[recv_tel]/$data[recv_mobile]</td>
                         <td> $customerInfo </td>
                         <td width=70> $status </td>
                         <td width=70> $csStatus </td>
                </tr>";
                $i++;
                $is_hap = "";
            }

            echo "</table>"; 
        }
        else
        {
           echo "<table width=99% border=0 cellpadding=0 cellspacing=0 align=center>";
           $query = "select a.* from  csinfo a, orders b
                              where a.order_seq = b.seq and a.cs_result = 0  ";

           if ( $order_status)
               $query .= " and b.status = $order_status ";

           if ( $order_cs )
           {
               switch ( $order_cs )
               {
                   case 1:  // 정상
                      $query .= "and b.order_cs = 0 ";
                   break;
                   case 2: // 취소
                      $query .= "and b.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
                   break;
                   case 3: // 교환 
                      $query .= "and b.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
                   break;
               }
           }

           $query .= " group by a.order_seq order by a.input_date desc,a.input_time desc";

//echo $query;

           $result = mysql_query ( $query, $connect );
           $i=1;
           while ( $data = mysql_fetch_array ( $result) )
           {
              echo "
               <tr onClick='javascript:click_action2(\"$data[order_seq]\",this)' onMouseOver='javascript:click_ready2(this)'>
                        <td width=100 align=center>$data[input_date]</td>
                        <td>$data[order_seq]</td>
                        <td>" . htmlspecialchars( $data[content] ) . "</td>
                        <td width=150 align=center>$data[writer]</td>
               </tr>";
               $i++;
           }
           echo "</table>";
        }

    }

    //////////////////////////////
    //
    // download처리
    // date: 2008.5.16 -jk
    //
    function download2()
    {
        global $order_status, $chk_csresult, $act, $pack;

        if ( $act == "packlist" )
        {
            $val[] = array("공란", "상품코드", "상품명","옵션","개수");
            $this->get_pack_list( $pack, &$val );
        }
        else
        {
            // 미처리 cs조회
            if ( $chk_csresult == 1 )
            {         
                $result = class_cs::get_list(0);
            }
            else
            {
                if ( $order_status == 98 || $order_status == 97 || $order_status == 96 || $order_status == 95 )
                    $result= class_notyet::notyet_list();        
                else
                    $this->cs_list( &$total_rows, &$result, -1);        // download -1 flag
            }
            
            $val = array();
            $val[] = array("관리번호", 
                            "합포번호", 
                            "판매처",
                            "주문번호", 
                            "발주일",
                            "송장등록",
                            "배송일",
                            "주문자",
                            "수령자",
                            "수령자주소",
                            "수령자전화",
                            "배송상태",
                            "CS상태"
            );
    
            while ( $data = mysql_fetch_array ( $result ) )
            {
                $shop_name = class_C::get_shop_name( $data[shop_id] );
                $_order_cs = $this->get_order_cs( $data[order_cs] , 2 );
                $_status   = $this->get_order_status2( $data[status] );

                $val[] = array( 
                            $data[seq],
                            $data[pack],
                            $shop_name,
                            $data[order_id],
                            $data[collect_date],
                            $data[trans_date],
                            $data[trans_date_pos],
                            $data[order_name],
                            $data[recv_name],
                            $data[recv_address],
                            $data['recv_tel'],
                            $_status,
                            $_order_cs);
    
                if ( $data[pack] )
                    $this->get_pack_list( $data[pack], &$val );        // 합포 정보
                else
                    $this->get_one_list( $data[seq], &$val );        // 일반
            }
           }
        # end of if
 
        $obj = new class_file();
        $obj->download( $val );
    }

    ///////////////////////////////////////
    // 합포 정보 출력 - 2008.9.22 - jk
    function get_one_list( $seq, &$val )
    {
        global $connect;
        $query = "select product_id, product_name, options, qty 
                    from orders where seq='$seq'";

        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_infos = class_product::get_info( $data[product_id] );
            $product_name = $_infos[name];
            if ( $_SESSION[STOCK_MANAGE_USE] )
            {
                $options = $_infos[options];
            }
            else
               {
                $options = $data[options];
            }

            $val[] = array( " ", $data[product_id], $product_name, $options, $data[qty]);        
        }
    }

    ///////////////////////////////////////
    // 합포 정보 출력 - 2008.9.22 - jk
    function get_pack_list( $pack, &$val )
    {
        global $connect;
        $query = "select seq, pack from orders where seq='$pack'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );

        if ( $data[pack] )
            $query = "select seq, product_id, product_name, options, qty from orders where pack='$data[pack]'";
        else
            $query = "select seq, product_id, product_name, options, qty from orders where seq='$data[seq]'";

        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_infos = class_product::get_info( $data[product_id] );
            $product_name = $_infos[name];
            if ( $_SESSION[STOCK_MANAGE_USE] )
            {
                $options = $_infos[options];
            }
            else
               {
                $options = $data[options];
            }

            $qty = $data[qty] - $this->get_part_cancel_count( $data[seq] );
            $val[] = array( " ", $data[product_id], $product_name, $options, $qty);        
        }
    }

    //////////////////////////////
    //
    // json 형식으로 query함  , 합포주문들의 정보
    //
    function packlist_json()
    {
        global $stock, $pack, $connect, $start, $limit;

        $start = ($_REQUEST[start] ? $_REQUEST[start] : 0);
        $limit = ($_REQUEST[limit] ? $_REQUEST[limit] : 1000);

        $val = array();
        $val['list'] = array();

        // 재고표시 정보 저장
        $query = "update ez_config set show_stock=$stock";
        mysql_query($query, $connect);

        $query = "select * from orders where pack=$pack";
        $result = mysql_query ( $query , $connect );

        // 합포가 아닌 경우
        if ( mysql_num_rows( $result ) == 0 )
        {
            $query  = "select * from orders where seq=$pack";
            $result = mysql_query ( $query , $connect );
            $val["total_rows"] = mysql_num_rows( $result );
        }
        // 합포인 경우 기준 주문이 젤 위에 나오게
        else
        {
            $val["total_rows"] = mysql_num_rows( $result );

            $query = "select * from orders where pack=$pack order by seq=$pack desc, seq limit $start, $limit";
            $result = mysql_query ( $query , $connect );
        }
//debug("합포 상품 조회 : " . $query);

        $productTotalQty = 0;
        $productTotalPrice = 0;

        // CS부분배송
        // 2014-09-15 장경희
        if( $_SESSION[USE_PRE_TRANS] )
        {
            $query_pre_trans = "select max(pre_trans) max_pre_trans from orders where seq=$pack or pack=$pack";
            $result_pre_trans = mysql_query($query_pre_trans, $connect);
            $data_pre_trans = mysql_fetch_assoc($result_pre_trans);
            
            $pre_trans = $data_pre_trans[max_pre_trans];
        }
        else
            $pre_trans = 0;
        
        $i = 1;
        // orders
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $query_prd = "select * from order_products where order_seq = $data[seq] order by seq";
            $result_prd = mysql_query($query_prd, $connect);

            $query_shop = "select shop_name, code_url, url from shopinfo where shop_id=$data[shop_id]";
            $result_shop = mysql_query($query_shop, $connect);
            $data_shop = mysql_fetch_assoc($result_shop);
            
            $shop_name = $data_shop[shop_name];
            $code_url = $data_shop[code_url];
            $url = $data_shop[url];

            // 주문금액( 지마켓, 지마켓일본, 지마켓싱가폴, 인터파크, cj몰 )
            $shop_id_part = $data[shop_id] % 100;
            if( $shop_id_part == 2 || $shop_id_part == 6 || $shop_id_part == 13 || $shop_id_part == 17 || $shop_id_part == 26 )
                $order_price = $data[code11];
            else if( $shop_id_part == 58 )
                $order_price = $data[code13];
            else
                $order_price = "";
            
            // 배송지연 - 상태:접수 또는 송장, order_cs:정상 또는 교환 
            $delay_status = 0;
            $delay_date1 = date('Y-m-d', strtotime('-' . $_SESSION[CS_DELAY1] . ' day'));
            $delay_date2 = date('Y-m-d', strtotime('-' . $_SESSION[CS_DELAY2] . ' day'));
            if( $_SESSION[USE_CS_DELAY] && ($data[status]==1 || $data[status]==7) && $data[order_cs] != 1 && $data[order_cs] != 2 )
            {
                if( $data[collect_date] <= $delay_date2 )
                    $delay_status = 2;
                else if( $data[collect_date] <= $delay_date1 )
                    $delay_status = 1;
            }

            // 발주 단계에서 아직 order_products가 생성되지 않은 경우
            if( mysql_num_rows($result_prd) == 0 )
            {
                $val["list"][] = array( 
                    no                => $i,
                    list_seq          => $data[seq],
                    list_collect_date => $data[collect_date],
                    list_shop_name    => $shop_name,
                    list_order_id     => $data[order_id],
                    seq               => $data[seq],
                    pack              => $data[pack],
                    prd_seq           => 0,
                    collect_date      => $data[collect_date],
                    collect_time      => $data[collect_time],
                    order_date        => $data[order_date],
                    order_time        => $data[order_time],
                    shop_id           => $data[shop_id],
                    shop_name         => $shop_name,
                    order_id          => $data[order_id],
                    order_id_seq      => $data[order_id_seq],
                    cust_id           => $data[cust_id],
                    qty               => '',
                    product_id        => '',
                    product_name      => '',
                    options           => '',
                    enable_sale       => 1,
                    shop_price        => '',
                    status            => $data[status],
                    order_cs          => $data[order_cs],
                    hold              => $data[hold],
                    not_trans         => $data[trans_status],
                    stock_format      => '',
                    supply_name       => '',
                    cart_no           => 0,
                    shop_product_id   => htmlspecialchars($data[shop_product_id]),
                    shop_product_name => htmlspecialchars($data[product_name]),
                    shop_options      => htmlspecialchars($data[options]),
                    shop_qty          => $data[qty],
                    shop_trans_who    => $data[org_trans_who],
                    shop_trans_price  => $data[trans_price],
                    settle_price      => 0,
                    amount            => $data[amount],
                    supply_price      => $data[supply_price],
                    order_price       => $order_price,
                    order_name        => htmlspecialchars($data[order_name]),
                    order_tel1        => htmlspecialchars($data[order_tel]),
                    order_tel2        => htmlspecialchars($data[order_mobile]),
                    recv_name         => htmlspecialchars($data[recv_name]),
                    recv_tel1         => htmlspecialchars($data[recv_tel]),
                    recv_tel2         => htmlspecialchars($data[recv_mobile]),
                    recv_add          => htmlspecialchars($data[recv_address]),
                    recv_zip          => $data[recv_zip],
                    memo              => htmlspecialchars($data[memo]),
                    trans_date        => '',
                    trans_no          => '',
                    trans_corp_id     => '',
                    trans_corp_name   => '',
                    trans_corp_link   => '',
                    trans_who         => $data[trans_who],
                    trans_date_pos    => '',
                    trans_date_pos2   => '',
                    gift              => $data[gift],
                    dvided_options    => '',
                    org_price         => 0,
                    org_trans_who     => $data[org_trans_who],
                    cs_priority       => $data[cs_priority],
                    cross_change      => $data[cross_change],
                    tb_no             => 0,
                    tb_status         => 0,
                    trans_key         => 0,
                    is_gift           => 0,
                    trans_fee         => 0,
                    pack_lock         => $data[pack_lock],
                    delay_status      => $delay_status,
                    pack_disable      => '',
                    code_url          => $code_url,
                    url          	  => $url,
                    misong            => 0,
                    product_memo      => '',
                    pay_type          => $data[pay_type],
                    return_info       => '',
                    server_time       => Date("Y-m-d H:m:s"),
                    memo_check        => $data[memo_check],
                    match_type        => 0,
                    match_worker      => '',
                    match_date        => '',
                    is_delete         => 0,
                    category          => '',
                    pre_trans         => 0,
                    code2	          => $data[code2]
                );
            }
            else
            {
                // order_products
                $first_product = true;

                // 정산 : 주문정보
				if( _DOMAIN_ == "ellse1205" && $data[c_seq] > 0 )
				{
                    $calc_amount       = 0;
                    $calc_supply_price = 0;
                }
                else
                {
                    $calc_amount       = $data[amount];
                    $calc_supply_price = $data[supply_price];
                }
                
                $li = 0;
                while( $data_prd = mysql_fetch_array($result_prd) )
                {
                    // 상품 정보
                    $_infos = class_product::get_info( $data_prd[product_id] );
                    if( !$data_prd[product_id] )
                    {
                        $_infos[enable_sale] = 1;
                    }                        

                    // 카테고리
                    if( !$_SESSION[MULTI_CATEGORY] ) {
                        $prd_category = $this->get_category( $_infos[category] );
                    }
                    else
                        $prd_category = class_multicategory::get_category_str($_infos[str_category]);
                    
                    // 재고
                    if( $_infos[enable_stock] && $stock )
                    {
                        if( $_SESSION[USE_STOCK_OX] )
                        {
                            $stock_icon = "<span style='font-size:12px; font-weight:bold; color:red;' title='배송불가'>X </span>";
    
                            $query_enable = "select * from print_enable where order_seq=$data[seq] and product_seq=$data_prd[seq]";
                            $result_enable = mysql_query($query_enable, $connect);
                            if( mysql_num_rows($result_enable) )
                            {
                                $data_enable = mysql_fetch_assoc($result_enable);
                                if( $data_enable[status] == 3 and $data_enable[is_deliv_all] = 1 )
                                    $stock_icon = "<span style='font-size:12px; font-weight:bold; color:blue;' title='배송가능'>O </span>";
                            }
                        }
                        else
                            $stock_icon = "";    
                        $stock_format = $stock_icon . $this->get_stock_format($data_prd[product_id]);
                    }
                    else
                        $stock_format = '';
        
                    $supply_name = class_vendor::get_name( $data_prd[supply_id] );
                    
                    // 택배사 송장 링크정보 포함
                    $trans_name = class_transcorp::get_corp_name( $data[trans_corp] );
                    $trans_name_all = class_top::print_delivery($trans_name,$data[trans_no]);
                    $trans_no_all = class_top::print_delivery($trans_name,$data[trans_no],0,"cs_trans_no");

                    // 총 상품 수량
                    $productTotalQty += $data_prd[qty];

                    // 상품 목록에서 가격
                    if( _DOMAIN_ == 'undoco' || _DOMAIN_ == 'changsin')
                    {
                        if( _DOMAIN_ == 'changsin' && $data_prd[is_gift] )
                            $_temp_price = 0;
                        else
                            $_temp_price = $data_prd[prd_amount] + $data_prd[extra_money];

                        $product_price = number_format($_temp_price);
                        // 총 금액
                        $productTotalPrice += $_temp_price;
                    }
                    else if( $_SESSION[CS_PRICE] == 1 )
                    {
                        // 2013-11-25 문답게시판
                        if( _DOMAIN_ == 'ellse1205' && 
                            ( $data_prd[order_cs] == 1 ||
                              $data_prd[order_cs] == 2 ||
                              $data_prd[order_cs] == 3 ||
                              $data_prd[order_cs] == 4 ))  
                        {
                            $data[amount] = 0;
                            $data_prd[extra_money] = 0;
                        }

                        // 2014-06-13 문답게시판
                        if( _DOMAIN_ == 'ellse1205' && $data[c_seq] > 0 )  
                        {
                            $data[amount] = 0;
                            $data[supply_price] = 0;
                            $data_prd[prd_amount] = 0;
                            $data_prd[prd_supply_price] = 0;
                        }

                        if( $first_product )
                        {
                            $_temp_money1 = $data[amount] + $data_prd[extra_money];
                            $_temp_money2 = $_temp_money1;
                            
                            if( _DOMAIN_ == 'dammom' )  $_temp_money2 -= $data_prd[refund_price];
                            
                            $product_price = number_format($_temp_money2);
                            
                            // 총 금액
                            $productTotalPrice += $_temp_money1;
                        }
                        // 두번째 이하에서 상품이 extra_money가 0 이상이면 "+"
                        else if( $data_prd[extra_money] > 0 )
                        {
                            $product_price = "+" . number_format( $data_prd[extra_money] );
                            
                            // 총 금액
                            $productTotalPrice += $data_prd[extra_money];
                        }
                        // 두번째 이하에서 상품이 extra_money가 0 이하이면 그대로
                        else if( $data_prd[extra_money] < 0 )
                        {
                            $product_price = number_format( $data_prd[extra_money] );
                            
                            // 총 금액
                            $productTotalPrice += $data_prd[extra_money];
                        }
                        else 
                            $product_price = '-';
                    }
                    else
                    {
                        $product_price = number_format( $data_prd[shop_price] );

                        // 총 금액
                        $productTotalPrice += $data_prd[shop_price];
                    }
                        
                    if( $li++ == 0 )
                    {
                        $list_seq          = $data[seq];
                        $list_collect_date = $data[collect_date];
                        $list_shop_name    = $shop_name;
                        $list_order_id     = $data[order_id];
                    }
                    else
                    {
                        $list_seq          = "";
                        $list_collect_date = "";
                        $list_shop_name    = "";
                        $list_order_id     = "";
                    }
                    
                    // 반품정보
                    $query_return = "select seq from return_money where order_products_seq=$data_prd[seq] and is_delete=0";
                    $result_return = mysql_query($query_return, $connect);
                    if( mysql_num_rows($result_return) )
                    {
                        $data_return = mysql_fetch_assoc($result_return);
                        $return_info = $data_return[seq];
                    }
                    else
                        $return_info = "";

                    // 배송정보
                    if( $data[status] == 7 || $data[status] == 8 )
                    {
                        // $_trans_no          = $trans_no_all; 다바걸에서 송장번호 복사하는데 불편
                        $_trans_no          = $data[trans_no];
                        $_trans_corp_link   = $trans_name_all;
                        $_trans_date        = $data[trans_date];
                        
                        if( $data[status] == 8 )
                        {
                            $_trans_date_pos = $data[trans_date_pos];

                            // 날짜 차이 구하기
                            $query_datediff = "SELECT DATEDIFF(now(),'$data[trans_date_pos]') AS DiffDate";
                            $result_datediff = mysql_query($query_datediff, $connect);
                            $data_datediff = mysql_fetch_assoc($result_datediff);
                            $_trans_date_pos2 = $data[trans_date_pos] . " (-$data_datediff[DiffDate])";
                        }
                        else
                        {
                            $_trans_date_pos = "";
                            $_trans_date_pos2 = "";
                        }
                    }
                    else
                    {
                        $_trans_no          = "";
                        $_trans_corp_link   = "";
                        $_trans_date        = "";
                        $_trans_date_pos    = "";
                        $_trans_date_pos2 = "";
                        
                        if( (_DOMAIN_ == 'box4u' || _DOMAIN_ == 'jkhdev') && $data[trans_corp] )
                            $_trans_corp_link   = $trans_name_all;
                    }
                    
                    // soramam 도서지역 표시
                    if( _DOMAIN_ == 'soramam' )
                    {
                        $cj_odb = new class_db();
                        $connect_cj = $cj_odb->connect(_MYSQL_KOREX_HOST_, "ezadmin", _MYSQL_KOREX_PASSWD_);
                        
                        $query_island = "select is_island from cj_areacode where zipcode='".preg_replace('/[^0-9]/','',$data[recv_zip])."'";
                        $result_island = mysql_query($query_island, $connect_cj);
                        $data_island = mysql_fetch_assoc($result_island);
                        
                        if( $data_island[is_island] == 2 )
                            $recv_add = "[도서지역] " . $data[recv_address];
                        else
                            $recv_add = $data[recv_address];
                    }
                    else
                        $recv_add = $data[recv_address];

                    // 선결제배송비
                    if( $data[trans_who] == "선불" )
                        $trans_who = $data[trans_who] . "($data[prepay_price])";
                    else
                        $trans_who = $data[trans_who];

					if( _DOMAIN_ == "ellse1205" && $data[c_seq] > 0 )
					{
                        $calc_amount       += $data_prd[extra_money];
                        $calc_supply_price += $data_prd[extra_money];
                    }
                    else
                    {
                        $calc_amount       += $data_prd[extra_money];
                        $calc_supply_price += $data_prd[extra_money];
                    }
                    
                    if( _DOMAIN_ == 'memorette' && $data_prd[marked] == 2 && ($data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 2 || $data[shop_id] % 100 == 78 || $data[shop_id] % 100 == 79))
                        $data[product_name] = "추가구성";

                    $val["list"][] = array( 
                        no                => $i,
                        list_seq          => $list_seq,
                        list_collect_date => $list_collect_date,
                        list_shop_name    => $list_shop_name,
                        list_order_id     => $list_order_id,
                        seq               => $data[seq],
                        pack              => $data[pack],
                        prd_seq           => $data_prd[seq],
                        collect_date      => $data[collect_date],
                        collect_time      => $data[collect_time],
                        order_date        => $data[order_date],
                        order_time        => $data[order_time],
                        shop_id           => $data[shop_id],
                        shop_name         => $shop_name,
                        order_id          => $data[order_id],
                        order_id_seq      => $data[order_id_seq],
                        cust_id           => $data[cust_id],
                        qty               => $data_prd[qty],
                        product_id        => $data_prd[product_id],
                        product_name      => $_infos[name],
                        options           => $_infos[options],
                        enable_sale       => ( _DOMAIN_ == 'parklon' ? 1 : $_infos[enable_sale]), // parklon 품절표시 안함(2014-07-29 장경희)
                        shop_price        => $product_price,
                        prd_shop_price	  => $_infos[shop_price],
                        status            => $data[status],
                        order_cs          => $data_prd[order_cs],
                        hold              => $data[hold],
                        not_trans         => $data[trans_status],
                        stock_format      => $stock_format,
                        supply_name       => $supply_name,
                        cart_no           => 0,
                        shop_product_id   => htmlspecialchars($data[shop_product_id]),
                        shop_product_name => htmlspecialchars($data[product_name]),
                        shop_options      => htmlspecialchars($data[options]),
                        shop_qty          => $data[qty],
                        shop_trans_who    => $data[org_trans_who],
                        shop_trans_price  => $data[trans_price],
                        settle_price      => 0,
                        amount            => $calc_amount,
                        supply_price      => $calc_supply_price,
                        order_price       => $order_price,
                        order_name        => htmlspecialchars($data[order_name]),
                        order_tel1        => htmlspecialchars($data[order_tel]),
                        order_tel2        => htmlspecialchars($data[order_mobile]),
                        recv_name         => htmlspecialchars($data[recv_name]),
                        recv_tel1         => htmlspecialchars($data[recv_tel]),
                        recv_tel2         => htmlspecialchars($data[recv_mobile]),
                        recv_zip          => $data[recv_zip],
                        recv_add          => htmlspecialchars($recv_add),
                        memo              => htmlspecialchars($data[memo]),
                        trans_date        => $_trans_date,
                        trans_no          => $_trans_no,
                        trans_corp_id     => $data[trans_corp],
                        trans_corp_name   => $trans_name,
                        trans_corp_link   => $_trans_corp_link,
                        trans_who         => $trans_who,
                        trans_date_pos    => $_trans_date_pos,
                        trans_date_pos2   => $_trans_date_pos2,
                        gift              => $data[gift],
                        divided_options   => htmlspecialchars($data_prd[shop_options]),
                        org_price         => $_infos[org_price],
                        org_trans_who     => $data[org_trans_who],
                        cs_priority       => $data[cs_priority],
                        cross_change      => $data[cross_change],
                        tb_no             => $data_prd[tb_no],
                        tb_status         => $data_prd[tb_status],
                        trans_key         => $data[trans_key],
                        is_gift           => $data_prd[is_gift],
                        trans_fee         => $data[trans_fee],
                        pack_lock         => $data[pack_lock],
                        delay_status      => $delay_status,
                        pack_disable      => ( $_infos[pack_disable] == 1 ? $_infos[pack_cnt] : "" ),
                        code_url          => $code_url,
                        url 	          => $url,
                        misong            => $data_prd[misong],
                        product_memo      => $_infos[memo],
                        pay_type          => $data[pay_type],
                        return_info       => $return_info,
                        server_time       => Date("Y-m-d H:m:s"),
                        memo_check        => $data[memo_check],
                        match_type        => $data_prd[match_type],
                        match_worker      => $data_prd[match_worker],
                        match_date        => $data_prd[match_date],
                        is_delete         => $_infos[is_delete],
                        category          => $prd_category,
                        pre_trans         => $pre_trans,
                        code2	          => $data[code2]
                    );
                    $i++;
                    $first_product = false;
                }
                
                // 정산 
                $_cnt = count($val["list"]);
                for($_i = 0; $_i < $_cnt; $_i++)
                {
                    if( $val["list"][$_i][seq] == $data[seq] )
                    {
                        $val["list"][$_i][amount] = $calc_amount;
                        $val["list"][$_i][supply_price] = $calc_supply_price;
                    }
                }

            }
            $val[productTotalQty] = number_format($productTotalQty);
            $val[productTotalPrice] = number_format($productTotalPrice);
        }
        echo json_encode( $val );
    }

    ///////////////////////////////////////////////////
    //
    // 묶음 상품 출력
    //
    function get_packed_list( &$val, $data, $i )
    {
           global $connect;
        $pack_list = "'" .  str_replace( ",", "','", $data[pack_list]) . "'";
        $arr_pack_list = split( ",", $data[pack_list] );

        foreach ( $arr_pack_list as $product_id )
        {
                $query = "select * from products where product_id ='$product_id'";

                $result = mysql_query ( $query, $connect );
                while ( $_infos= mysql_fetch_array( $result ) )
                {
                    $shop_name   = class_C::get_shop_name( $data[shop_id] );
                    $supply_name = class_vendor::get_name( $data[supply_id] );

                    // 재고 수량 조회
                    if ( $_SESSION[USE_3PL] )
                    {
                        $api_3pl = new class_3pl_api();
                        $str_stock = $api_3pl->get_current_stock2( $_infos[product_id] );
                    }

                    $tb_info = class_takeback::get_takeback_info_seq( $data[seq] );
                    $val["list"][] = array( 
                                no           => "<span class=red>$i<span>",
                                seq          => $data[seq],
                                pack         => $data[pack],
                                shop_name    => $shop_name,
                                collect_date => $data[collect_date],
                                qty          => $data[qty],
                                product_name =>  $_infos[name] ,
                                options      =>  $_infos[options] ? $_infos[options] : $data[option] ,
                                enable_sale  => $_infos[enable_sale],
                                supply_name  =>  $supply_name ,
                                packed       => $data[packed],
                                product_id   => $_infos[product_id],
                                shop_price   => $data[shop_price],
                                status       => $data[status],
                                stock        => $str_stock,
                                order_cs     => $data[order_cs],
                                status_tb    => $tb_info[error]?0:$tb_info[status]
                    );
                }
        }
    }

    //////////////////////////////
    // json 형식으로 query함 
    // date: 2008.5.7 -jk
    function query_json()
    {
        global $connect;

        $this->cs_list2( &$total_rows, &$result, &$real_total_qty );

        $val = array();
        $val["total_rows"] = $total_rows;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $shop_name = class_C::get_shop_name( $data[shop_id] );
            
            // 해당 주문의 order_products 의 order_cs를 조회하여 배열을 구한다.
            // 
            $order_cs = $this->get_order_products_cs($data[pack], $data[seq]);
            
            // 합포의 보류까지 확인
            $pack_hold = $this->get_orders_hold($data[pack], $data[seq], $data[hold]);
            
            // 주문 상태를 구한다. 합포일 경우 상태가 가장 높은 주문의 상태를 구한다.
            $pack_status = $this->get_order_pack_status($data[pack], $data[seq], $data[status]);
            
            // 주문의 회수 상태를 구한다.
            $query_tb = "select tb_no from order_products where order_seq=$data[seq] and tb_no > 0 ";
            $result_tb = mysql_query($query_tb, $connect);
            if( mysql_num_rows($result_tb) > 0 )
                $tb_status = 1;
            else
                $tb_status = 0;

            // 주문의 사은품 상태를 구한다.
            $gift = 0;
            if( $data[pack] )
                $query_gift = "select b.is_gift from orders a, order_products b where a.pack=$data[pack] and a.seq=b.order_seq and b.is_gift>0";
            else
                $query_gift = "select is_gift from order_products where order_seq=$data[seq] and is_gift>0";
            $result_gift = mysql_query($query_gift, $connect);
            if( mysql_num_rows($result_gift) )
                $gift = 2;  // 사은품 상품
            else
                if( $data[gift] )  $gift = 1;  // 사은품 메시지
                    
            // 배송지연 - 가장 오래된 미배송 주문의 발주일
            $pre_trans = $data[pre_trans];
            if( $data[pack] > 0 )
            {
                $query_date = "select collect_date from orders where pack=$data[pack] and status in (1,7) and order_cs<>1 order by collect_date limit 1";
                $result_date = mysql_query( $query_date, $connect );
                $data_date = mysql_fetch_assoc( $result_date );
                $old_collect_date = $data_date[collect_date];

                if( $_SESSION[USE_PRE_TRANS] )
                {
                    $query_pre_trans = "select max(pre_trans) max_pre_trans from orders where pack=$data[pack]";
                    $result_pre_trans = mysql_query($query_pre_trans, $connect);
                    $data_pre_trans = mysql_fetch_assoc($result_pre_trans);
                    
                    $pre_trans = $data_pre_trans[max_pre_trans];
                }
            }
            else
            {
                if( ($data[status]==1 || $data[status]==7) && $data[order_cs]!=1 )
                    $old_collect_date = $data[collect_date];
                else
                    $old_collect_date = "";
            }
               
            $delay_status = 0;
            $delay_date1 = date('Y-m-d', strtotime('-' . $_SESSION[CS_DELAY1] . ' day'));
            $delay_date2 = date('Y-m-d', strtotime('-' . $_SESSION[CS_DELAY2] . ' day'));
            if( $_SESSION[USE_CS_DELAY] && $old_collect_date )
            {
                if( $old_collect_date <= $delay_date2 )
                    $delay_status = 2;
                else if( $old_collect_date <= $delay_date1 )
                    $delay_status = 1;
            }

            // 부분배송
            if( $data[part_seq] )
            {
                if( $data[pack] > 0 )
                    $query_part = "select seq from orders where part_seq=$data[part_seq] and pack<>$data[pack]";
                else
                    $query_part = "select seq from orders where part_seq=$data[part_seq] and seq<>$data[seq]";

                $result_part = mysql_query($query_part, $connect);
                if( mysql_num_rows($result_part) )
                    $part_seq = $data[part_seq];
                else
                    $part_seq = 0;
            }
            else
                $part_seq = 0;

            // 일단 사용안함
            // $part_seq = 0;

            // soramam 도서지역 표시
            if( _DOMAIN_ == 'soramam' )
            {
                $cj_odb = new class_db();
                $connect_cj = $cj_odb->connect(_MYSQL_KOREX_HOST_, "ezadmin", _MYSQL_KOREX_PASSWD_);
                
                $query_island = "select is_island from cj_areacode where zipcode='".preg_replace('/[^0-9]/','',$data[recv_zip])."'";
                $result_island = mysql_query($query_island, $connect_cj);
                $data_island = mysql_fetch_assoc($result_island);
                
                if( $data_island[is_island] == 2 )
                    $recv_add = "[도서지역] " . $data[recv_address];
                else
                    $recv_add = $data[recv_address];
            }
            else
                $recv_add = $data[recv_address];

            // 전체수량
            if( $data[pack] > 0 )
            {
                $query_total_qty = "select sum(qty) sum_qty from orders where pack=$data[pack]";
                $result_total_qty = mysql_query($query_total_qty, $connect);
                $data_total_qty = mysql_fetch_assoc($result_total_qty);
                
                $total_qty = $data_total_qty[sum_qty];
            }
            else
                $total_qty = $data[qty];
                
            // 2014-01-27 김영국. sbs는 총수량 안봄
            if( _DOMAIN_ == 'sbs' )
                $total_qty = "";

            if( $_SESSION[CS_DISP_MEMO] && $data[memo] )
                $disp_memo = $data[memo];
            else
                $disp_memo = "";

            $val["list"][] = array( 
                        pack         => $data[pack] ? $data[pack] : $data[seq],
                        collect_date => ((_DOMAIN_ == 'lovestar9' || _DOMAIN_ == 'leroom' || _DOMAIN_ == 'dragon') ? $data[order_date] : $data[collect_date]),
                        shop_name    => $shop_name,
                        order_id     => $data[pack] ? "[" . $data[order_id] . "]" : $data[order_id],
                        recv_address => htmlspecialchars($recv_add),
                        recv_tel     => htmlspecialchars($data['recv_tel']),
                        recv_mobile  => htmlspecialchars($data['recv_mobile']),
                        order_name   => htmlspecialchars($data[order_name]),
                        recv_name    => htmlspecialchars($data[recv_name]),
                        order_cs     => $order_cs,
                        status       => $pack_status,
                        hold         => $pack_hold,
                        not_trans    => $data[trans_status],
                        cs_priority  => $data[cs_priority],
                        tb_status    => $tb_status,
                        gift         => $gift,
                        pack_lock    => $data[pack_lock],
                        delay_status => $delay_status,
                        part_seq     => $part_seq,
                        total_qty    => $total_qty,
                        memo         => $disp_memo,
                        pre_trans    => $pre_trans
            );
            
            // 전체 검색 결과 수량
            $val["real_total_qty"] = number_format($real_total_qty);
        }

        echo json_encode( $val );
    }
    
    function cs_exist2( $seq )
    {
        global $connect;
        $query = "select count(*) cnt from csinfo where order_seq='$seq'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[cnt];
    }
    
    ///////////////////////////////////////////
    // order_products의 order_cs 구하기
    //
    // seq 또는 pack으로 order_products를 조회하여, order_cs의 종류를 배열로 가져온다.
    //
    function get_order_products_cs($pack, $seq)
    {
        global $connect;
        
        // 합포
        if( $pack )
        {
            // 합포 주문 seq 
            $query = "select seq from orders where pack=$pack";
            $result = mysql_query($query, $connect);
            $seq_str = '';
            while( $data = mysql_fetch_assoc($result) )
            {
                $seq_str .= ($seq_str?",":"") . $data[seq];
            }
            
        }
        // 합포 아님
        else
        {
            $seq_str = $seq;
        }
        
        $query = "select order_cs from order_products where order_seq in ($seq_str) group by order_cs order by order_cs";
        $result = mysql_query($query, $connect);
        $cs_str = '';
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $cs_str != '' )  $cs_str .= ',';
            $cs_str .= $data[order_cs];
        }
        
        // 미송 여부
        $query = "select misong from order_products where order_seq in ($seq_str) and misong>0 group by misong order by misong";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[misong] == 1 )
                $cs_str .= ($cs_str==="" ? "" : "," ) . "m1";
            else
                $cs_str .= ($cs_str==="" ? "" : "," ) . "m2";
        }
        
        if(_DOMAIN_ =="box4u" && $pack)
        {
        	$query = "select count(*) cnt from orders where pack=$pack AND shop_id = 10058 AND code16 >''";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            if($data[cnt] > 0)
        		$cs_str .= ($cs_str==="" ? "" : "," ) . "code16";
        }

        // cs 여부
        // cs 내역 전체보기 -> CS가 있을경우 빨강cs 아이콘
        if( $_SESSION[CS_VIEW_ALL] > 0 )
        {
	        $query = "select count(*) as cnt from csinfo where order_seq in ($seq_str)";
	        if( $_SESSION[LOGIN_LEVEL] < 9 )
	        	$query .= " and is_delete=0";
	        	
	        $result = mysql_query($query, $connect);
	        $data = mysql_fetch_assoc($result);
	        
	        if( $data[cnt] >= 1 )
            	$cs_str .= ($cs_str==="" ? "" : "," ) . "x";
        }// CS 내역 사용자만 보기 // sys_cs ->파랑,  user_cs ->빨강
        else
        {
        	$query = "select count(*) as cnt from csinfo where order_seq in ($seq_str)";
	        if( $_SESSION[LOGIN_LEVEL] < 9 )
	        	$query .= " and is_delete=0";
        
        	$result = mysql_query($query, $connect);
	        $data = mysql_fetch_assoc($result);
        
        	$query = "select count(*) as cnt from csinfo where order_seq in ($seq_str)  AND user_content >'' ";
	        if( $_SESSION[LOGIN_LEVEL] < 9 )
	        	$query .= " and is_delete=0";
	        	
	        $result = mysql_query($query, $connect);
	        $_data = mysql_fetch_assoc($result);
	        
	        if($_data[cnt] > 0)
	        	$cs_str .= ($cs_str==="" ? "" : "," ) . "x";
	        else if($data[cnt] > 0)
	        	$cs_str .= ($cs_str==="" ? "" : "," ) . "xx";
        }
        return $cs_str;
    }

    ///////////////////////////////////////////
    // orders의 pack 고려한 hold 구하기
    //
    function get_orders_hold($pack, $seq, $hold)
    {
        global $connect;
        
        // 합포
        if( $pack )
        {
            // 합포 주문 seq 
            $query = "select hold from orders where pack=$pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $hold += $data[hold];
            }
        }
        return $hold;
    }

    ///////////////////////////////////////////
    // pack status 구하기
    //
    function get_order_pack_status($pack, $seq, $status)
    {
        global $connect;
        
        // 합포
        if( $pack > 0 )
        {
            // 합포 주문 seq 
            $query = "select status from orders where pack=$pack group by status order by status desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            return $data[status];
        }
        // 합포 아님
        else
        {
            return $status;
        }
    }

    ///////////////////////////////////////////
    // 검색
    function query()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type, $order_status, $shop_id;
        global $supply_code;

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
        $end_date = $_REQUEST["end_date"];

        global $keyword;

//        Header("Content-Type:plain/text;charset=euc-kr"); 

        $keyword =  $keyword;

echo "<table width=98% align=center>
  <tr>
    <td> product </td>
    <td> pp </td>
  </tr>
";
           $this->cs_list( &$total_rows, &$r_cs , 0 );

           while ( $data = mysql_fetch_array ( $r_cs ) )
           {
              $name = class_product::get_product_name($data[product_id]);

              echo "<tr>
              <td><a href=javascript:get_detail2('$data[seq]') onClick='javascript:click_action(this)'>흠 $name</a> </td>
              <td>$data[order_name] </td>
              </tr>";
              
           }
//        }

echo "</table>"; 
    }

    /////////////////////////////////////////////////////
    // 주문의 상태 변경
    // date: 2006.1.18 - jk
    function change_status()
    {
        global $connect, $top_url, $seq, $status, $order_cs, $link_url;
        
        $transaction = $this->begin("주문 상태 변경");

        $query = "update orders set status='$status', order_cs='$order_cs' where seq='$seq'";
        mysql_query ( $query, $connect );

        $this->opener_redirect( "template.htm" . base64_decode( $link_url ) . "top_url=$top_url");
        $this->closewin();

        $this->end( $transaction );
    }

    /////////////////////////////////////////////////////
    // 주문의 상태 변경
    // date: 2006.8.10 - jk
    function change_status2( $seq, $status, $order_cs )
    {
        global $connect;
        
        $transaction = $this->begin("주문 상태 변경");
        $query = "update orders set status='$status', order_cs='$order_cs' where seq='$seq'";
        mysql_query ( $query, $connect );

        $this->end( $transaction );
    }

    function E103()
    {
        global $connect;
        global $template;


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E104()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E105()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E106()
    {
        global $connect;
        global $template;
 
        // cs_type=취소
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E107()
    {
        global $connect;
        global $template;
 
        // cs_type=취소
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );

        $content = "==반품정보==\n반품택배사:\n반품송장번호:\n";
        $content .= "환불계좌:\n환불은행:\n예금주:\n ";
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E108()
    {
        global $connect;
        global $template;
 
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E109()
    {
        global $connect;
        global $template;
 
        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E110()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E113()
    {
        global $template, $name, $mobile;
        $connect = sys_db_connect();
        $mobile = str_replace("-", "", $mobile);

        //$query = "select sum(point) s from sys_user_memo where mobile='$mobile' and name='$name' order by crdate desc"; 
        $query = "select sum(point) s from sys_user_memo where mobile='$mobile' order by crdate desc"; 
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        $s = $data[s];

        $link_url = $this->build_link_url();
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////
    // date: 2006.4.17 - jk
    // 고객 성향 출력
    function E114()
    {
        global $template, $name, $mobile;
        $connect = sys_db_connect();
        $mobile = str_replace("-", "", $mobile);

        // $query = "select * from sys_user_memo where mobile='$mobile' and name='$name' order by crdate desc"; 
        $query = "select * from sys_user_memo where mobile='$mobile' order by crdate desc"; 
// echo $query;
        $result = mysql_query ( $query, $connect );
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////
    // date: 2006.4.17 - jk
    function add_memo()
    {
        global $template, $name, $mobile, $point, $link_url, $memo, $domain;
        $connect = sys_db_connect();
        $s_mobile = str_replace("-", "", $mobile);
        $query = "insert into sys_user_memo set mobile='$s_mobile', name='$name', point='$point', memo='". addslashes($memo) . "', domain='" . _DOMAIN_ . "'";
        mysql_query ( $query, $connect );

        $this->redirect( "popup.htm?" . $link_url );
        exit;
    }

    function E115()
    {
        global $connect;
        global $template, $name, $mobile;
 
        // $query = "select * from orders where recv_mobile='$mobile' and recv_name='$name'"; 
//and REPLACE(recv_address,'-', '') = '$recv_address'
        $query = "select * from orders where REPLACE( recv_mobile, '-', '' )='$mobile'"; 
        $result = mysql_query ( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E111()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function del()
    {
        global $connect;
        global $template, $seq, $link_url, $top_url;

        $transaction = $this->begin("주문 삭제");

        if ( $seq )
        {
            $query = "delete from orders where seq='$seq'";
            mysql_query ( $query, $connect );
        }

        $this->end($transaction);

        $this->redirect( base64_decode( $top_url ) );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////////////
    // cs list 
    // date : 2005.9.14
    // 2008.1.21 is_pack 추가 합포건만 조회
    function cs_list( &$total_rows, &$result, $limit=0)
    {
       global $connect, $trans_who, $order_status, $shop_id, $_trans_who, $date_type;

       $search_type   = $_REQUEST[search_type];
       $keyword       = $_REQUEST[keyword];
       $trans_who     =  $_trans_who;
       $start_date    = $_REQUEST[start_date] . " 00:00:00";
       $end_date      = $_REQUEST[end_date] . " 23:59:59";
       $order_cs      = $_REQUEST[order_cs];
       $shop_id       = $_REQUEST[shop_id];
       $supply_id     = $_REQUEST[supply_id];
       $page          = $_REQUEST[page];
       $act           = $_REQUEST[act];
       $line_per_page = _line_per_page;

        //////////////////////////////////////////////
        // 검색
        $starter = $page ? ($page-1) * $line_per_page : 0;
        $options = "";
        //======================================
        // date: 2007.11.5
        // 품절 상품 check - jk
        global $soldout_only;
        $_soldout_products = "";
        if ( $soldout_only )
        {
            $_soldout_products = class_product::get_soldout_list();
            $options .= " and a.product_id in ( $_soldout_products )";
        }

        if ( $trans_who )
                $options .= " and a.trans_who = '$trans_who' ";

        //=======================================
        // 검색키워드
        if ($keyword)
        {
            switch ( $search_type )
            {
                case 1: // 주문자
                    if ( _DOMAIN_ == "leedb" )
                        $options .= "and a.order_name like '%${keyword}%'" ;
                    else
                        $options .= "and a.order_name = '${keyword}'" ;
                    break;
                case 2: // 주문번호
                    $options .= "and a.order_id = '${keyword}'" ;
                    break;
                case 3: // 상품명
                    $query = "select product_id from products where name like '%$keyword%'";
                    $result = mysql_query( $query, $connect );
                    $ids    = "";

                    $i = 0;
                    while ( $data = mysql_fetch_assoc( $result ) )
                    {
                        if ( $i>0)
                           $ids .= ",";
                        $ids .= "'$data[product_id]'";
                        $i++;
                    }

                    if ( $i > 0 )
                        $options .= "and a.product_id in ( $ids ) " ;
                    else
                        $options .= "and a.product_name like '%${keyword}%' " ;

                    break;
                case 4: // 전화번호
                    // $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;

                    $arr = array();
                    $arr = split( "-", $keyword );
                    $length = sizeof( $arr );

                    if (_DOMAIN_ == "nak21" or _DOMAIN_ == "ezadmin" )
                    {
                        if ( $length <= 2 )
                        {
                                //$options .= "and a.recv_mobile2 = '$keyword' or a.recv_tel = '$keyword'";
                                $options .= "and a.recv_mobile2 = '$keyword' ";
                        }
                        else
                        {
                                //$options .= "and a.recv_mobile = '$keyword' or a.recv_tel = '$keyword'";
                                $options .= "and a.recv_mobile = '$keyword' ";
                        }
                    }
                    else
                        $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;

                    break;
                case 5: // 수령자
                    if ( _DOMAIN_ == "leedb" )
                        $options .= "and (a.recv_name like '%$keyword%') " ;
                    else
                        $options .= "and (a.recv_name = '$keyword') " ;
                    break;
                case 6:  // 송장번호
                    $options .= "and a.trans_no = '$keyword' "; 
                    break;
                case 7: // 어드민 코드
                    $options .= "and a.seq = '$keyword' ";
                    break;
                case 8: // 주문자 전화
                    $options .= "and (a.order_tel like '%$keyword%' or a.order_mobile like '%$keyword%') " ;
                    break;
                case 9: // 상품 코드 
                    $options .= "and a.product_id = '$keyword' " ;
                    break;
                case 10: // 수령자 전화 
                    $options .= "and a.recv_tel = '$keyword' " ;
                    break;
                case 11: // 옥션 코드 
                    $options .= "and a.code1 = '$keyword' " ;
                    break;
                case 12: // 회수송장번호
                    $query = "select order_seq from order_takeback where trans_no='$keyword'";
                    $result = mysql_query( $query, $connect );
                    if( mysql_num_rows( $result ) > 0 )
                    {
                        $seq_list = "(";
                    while( $data = mysql_fetch_array( $result ) )
                        $seq_list .= $data[order_seq] . ",";
                    $seq_list = substr( $seq_list, 0, strlen($seq_list)-1) . ")";
                    
                    $options .= "and a.seq in $seq_list " ;
                }else
                        $options .= "and a.seq = '' " ;
                    break;
            }
          }

        //------------------------------------------------
        //
        // CS 상태
        // 
        if ($order_cs != '')
        {
                switch ( $order_cs )
                {
                case 1:  // 정상
                   $options .= "and a.order_cs = 0 ";
                break;
                case 2: // 취소
                   $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
                break;
                case 3: // 교환 
                   $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
                break;
                case 9: // 맞교환 
                   $options .= "and a.order_cs=9 ";
                break;
                }
        }

        //------------------------------------------------
        //
        // 주문 상태
        //
        if ( $order_status == 99 ) // hold
            $options .= "and a.hold > 0";
        else if ( $order_status == 1 )
             $options .= "and a.status in (1,2) ";
        else if ( $order_status )
             $options .= "and a.status = '$order_status' ";

//debug ($options);

        // session level이 0이면 업체임
        if ( !$_SESSION["LOGIN_LEVEL"] )
        $options .= " and supply_id = '" . $_SESSION["LOGIN_CODE"] . "'";

        // 판매처
        if ($shop_id != '')
        $options .= " and a.shop_id = '${shop_id}' " ;

        //선불 착불 
        if ( $trans_who )
        $options .= " and a.trans_who = '${trans_who}' " ;

        // 공급처
        if ( !$_SESSION["LOGIN_LEVEL"] )
        if ($supply_id != '')
        $options .= " and a.supply_id = '${supply_id}' " ;

        // 판매처
        if ($shop_id != '')
        $options .= " and a.shop_id = '$shop_id' " ;

        
        // promotion 업체의 경우 조회 가능한 shop_id list를 조회해야 함
        if ( $_SESSION[LOGIN_LEVEL] == 6 )
        {
            $shop_list = $this->get_promotion_shop();
            $options .= " and a.shop_id in ( $shop_list )";
        }

        ////////////////////////////////////
        // total count check
        $query_cnt = "SELECT COUNT(DISTINCT seq) cnt FROM orders a 
           where a." . $date_type . ">= '$start_date'
             and a." . $date_type . "<= '$end_date' 
             and (a.seq=a.pack or a.pack is null or a.pack='')
                 $options ";

        ///////////////////////////////////
        //
        // seq 와 pack이 같거나 pack이 null인 경우만 출력
        // 주문 번호로 조회할 경우는 이 부분을 타지 않는다. - 2006.8.28
        //   하위 주문 번호의 경우 조회가 안될 수도 있음
        // 송장 번호로 조회할 경우도 만찬가지 - 2006.8.28
        // 
        // 합포는 하나로 나오게
        if ( $keyword )
        {
            switch ( $search_type )
            {
                case "2":  // order_id 코드
                    if ( _DOMAIN_ == "ckcompany" )
                        $options .= " group by pack";
                    else
                        $options .= " group by pack, order_id ";
                    break;
                case "7":  // eaadmin코드
                case "3":  // product_id 코드
                    $options .= " group by seq";
                    break;
                default :
                        $options .= " and (seq=pack or pack is null or pack=0) group by seq";
                    break;
            }
        }
        else
        {
            $options .= " and (seq=pack or pack is null or pack=0) group by seq";
        }

        /////////////////////////////////////////////////////
        $sql = "select a.* 
                  from orders a";

        $where_clause = "
           where a.${date_type} >= '$start_date'
             and a.${date_type} <= '$end_date'
                 ${options}
           order by a.seq desc";

        //////////////////////////////////////////
        // 전체를 다 찍고 싶어도 최대값은 1000개
        $line_per_page = 10;

        global $start;
        $start = $start ? $start : 0;
        $limit_clause = " limit $start, $line_per_page";

        // count는 중지
        if ( $limit == -1 ) // download
            $query = $sql.$where_clause;
        else
            $query = $sql.$where_clause.$limit_clause;
debug ( "cs : " . $query );
        $result = mysql_query($query, $connect) or die(mysql_error());

        $total_rows = $this->get_count( $query_cnt );
    }

    ///////////////////////////////////////////////////
    //
    // 신발주용 조회
    //
    // 날짜조건 : 발주일, 송장입력일, 배송일, [취소일], [교환일]
    // 콤보박스 : 판매처, 상태, C/S, 배송비
    // 체크박스 : 보류, [품절], [미처리C/S]
    // 검색어 : 수령자, 수령자 전화, 수령자 핸드폰, 주문자, 주문자 전화, 주문자 핸드폰, 관리번호, 주문번호, 송장번호, 옥션 ID,
    //          [판매처 상품명], [판매처 상품코드], [어드민 상품명], [어드민 상품코드]
    // *[]는 JOIN 검색
    // 
    // 1. 검색 조건으로 pack=0 order by seq desc limit 100. => seq_arr[100]
    // 2. 검색 조건으로 pack>0 goup by pack order by seq desc limit 100. => pack_arr[100]
    // 3. seq in ( seq_arr, pack_arr ) order by seq desc limit 100
    //
    // orders a,
    // order_products b,
    // products c,
    // csinfo d,
    //
    function cs_list2( &$total_rows, &$result, &$real_total_qty)
    {
        global $connect;
        
        $date_type       = $_REQUEST[date_type];
        $start_date      = $_REQUEST[start_date];
        $end_date        = $_REQUEST[end_date];
        $search_type     = $_REQUEST[search_type];
        $keyword         = $_REQUEST[keyword];
        $shop_id         = $_REQUEST[shop_id];
        $group_id        = $_REQUEST[group_id];
        $order_status    = $_REQUEST[order_status];
        $order_cs        = $_REQUEST[order_cs];
        $query_trans_who = $_REQUEST[query_trans_who];
        $is_gift         = $_REQUEST[is_gift];
        $work_type       = $_REQUEST[work_type];
        $hold_order      = $_REQUEST[hold_order];
        $soldout_only    = $_REQUEST[soldout_only];
        $cs_check        = $_REQUEST[cs_check];
        $cs_pack         = $_REQUEST[cs_pack];
        
        $str_supply_code	= $_REQUEST[str_supply_code];
        $multi_supply_group	= $_REQUEST[multi_supply_group];
        $multi_supply		= $_REQUEST[multi_supply];

        $sort_title      = $_REQUEST[sort_title];
        $sort_direction  = $_REQUEST[sort_direction];

        $start           = $_REQUEST[start];
        $limit           = $_REQUEST[limit];

        $search_options = "";

        // 테이블 사용 flag
        $use_order_products = false;
        $use_products = false;
        $use_csinfo = false;
        $use_no_csinfo = false;
        
        // 날짜
        switch( $date_type )
        {
        	case "order_date":
            case "collect_date":
                $search_options = " a.$date_type >= '$start_date' and a.$date_type <= '$end_date' ";
                break;
                
            case "trans_date":
            case "trans_date_pos":
                $search_options = " a.$date_type >= '$start_date 00:00:00' and a.$date_type <= '$end_date 23:59:59' ";
                break;
            case "cancel_date":
            case "change_date":
                $search_options = " b.$date_type >= '$start_date 00:00:00' and b.$date_type <= '$end_date 23:59:59' ";
                $use_order_products = true;
                break;
        }

        // 판매처
        if( $shop_id )
            $search_options .= " and a.shop_id=$shop_id ";

        // 판매처그룹
        if( $group_id )
        {
            $shop_id_list = "";
            $query_group_id = "select shop_id from shopinfo where group_id=$group_id";
debug("판매처 그룹 : " . $query_group_id);
            $result_group_id = mysql_query($query_group_id, $connect);
            while( $data_group_id = mysql_fetch_assoc($result_group_id) )
                $shop_id_list .= $data_group_id[shop_id] . ",";

            $shop_id_list = substr($shop_id_list,0,-1);
            $search_options .= " and a.shop_id in ($shop_id_list) ";
        }
        
        
        if($str_supply_code)
        {
        	$search_options .= " and c.supply_code in ($str_supply_code) ";
        	$use_products = true;
        }
        if($multi_supply)
        {
        	$search_options .= " and c.supply_code in ($multi_supply) ";
        	$use_products = true;
        }
        

        // 상태
        
        // 주문상태
        if( $order_status == 17  )
            $search_options .= " and a.status in (1,7) ";
        else if( $order_status == 18  )
            $search_options .= " and a.status in (1,8) ";
        else if( $order_status == 78  )
            $search_options .= " and a.status in (7,8) ";
        else if( $order_status >= 0  )
            $search_options .= " and a.status=$order_status ";

        // C/S
        if( $order_cs )
        {
            switch( $order_cs )
            {
                // 정상
                case 1:
                    $search_options .= " and b.order_cs=0 ";
                    $use_order_products = true;
                    break;
                // 정상+교환
                case 13:
                    $search_options .= " and b.order_cs in (0,2,4,5,6,7,8) ";
                    $use_order_products = true;
                    break;
                // 취소
                case 2:
                    $search_options .= " and b.order_cs in (1,2,3,4) ";
                    $use_order_products = true;
                    break;
                // 교환
                case 3:
                    $search_options .= " and b.order_cs in (5,6,7,8) ";
                    $use_order_products = true;
                    break;
                // 맞교환
                case 9:
                    $search_options .= " and a.cross_change=1 ";
                    break;
                // 배송후교환C
                case 20:
                    $search_options .= " and a.c_seq>0 ";
                    break;
                // 배송후교환C+정상
                case 21:
                    $search_options .= " and a.c_seq>0 and a.order_cs in (0,2,4,5,6,7,8) ";
                    break;
            }
        }

        // 배송비
        if( $query_trans_who )
            $search_options .= " and a.trans_who='$query_trans_who' ";

        // 사은품
        if( $is_gift == 1 )
            $search_options .= " and a.gift<>'' ";
        if( $is_gift == 2 )
        {
            $search_options .= " and b.is_gift>0 ";
            $use_order_products = true;
        }
        
        // 작업
        if( $work_type )
        {
            if( $work_type == 10 )
                $search_options .= " and d.cs_type in (10,11,16)  ";
            else if( $work_type == 12 )
                $search_options .= " and d.cs_type in (12,13,18)  ";
            else if( $work_type == 17 )
            {
                $search_options .= " and d.cs_type = 17 and b.order_cs in (5,6) ";
                $use_order_products = true;
            }
            else if( $work_type == 32 )
            {
                $search_options .= " and d.cs_type = 17 and b.order_cs in (7,8) ";
                $use_order_products = true;
            }
            else if( 201 <= $work_type && $work_type <= 205 )
            {
                $search_options .= " and b.match_type = $work_type - 200 ";
                $use_order_products = true;
            }
            else if( $work_type == 300 )
            {
                // 부분배송
                $query_part = "select seq, pack, part_seq 
                                 from orders 
                                where collect_date>='$start_date' and collect_date<='$end_date' 
                                  and part_seq>0 
                                group by part_seq";
                $result_part = mysql_query($query_part, $connect);
                while( $data_part = mysql_fetch_assoc($result_part) )
                {
                    $real_part = false;
                    $query_part_pack = "select pack from orders where part_seq=$data_part[part_seq] and seq <> $data_part[seq] ";
                    $result_part_pack = mysql_query($query_part_pack, $connect);
                    while( $data_part_pack = mysql_fetch_assoc($result_part_pack) )
                    {
                        if( $data_part_pack[pack] == 0 || $data_part[pack] <> $data_part_pack[pack] )
                        {
                            $real_part = true;
                            break;
                        }
                    }
                    
                    if( !$real_part )
                    {
                        $query_part_reset = "update orders set part_seq = 0 where part_seq = $data_part[part_seq]";
                        mysql_query($query_part_reset, $connect);
                    }
                }
                
                $search_options .= " and a.part_seq > 0 ";
            }
            else if( $work_type == 301 )
            {
                if( $_SESSION[USE_PRE_TRANS] )
                {
                    $search_options .= " and a.pre_trans = 1 ";
                }
            }
            else
                $search_options .= " and d.cs_type = $work_type ";
                
            if( $work_type <= 200 )
                $use_csinfo = true;
        }
    
        // 보류
        if( $hold_order == 'true' )
            $search_options .= " and a.hold > 0 ";

        // 품절
        if( $soldout_only == 'true' )
        {
            $search_options .= " and c.enable_sale <> 1 ";
            $use_products = true;
        }
        
        // 미처리 C/S
        if( $cs_check == 'true' || $cs_check == 1 )
        {
            $search_options .= " and d.cs_result = 0 and d.is_delete=0 ";
            $use_csinfo = true;
        }
        else if( $cs_check == 2 )
        {
            $use_no_csinfo = true;
        }

        // 합포
        if( $cs_pack == 'true' )
        {
            $search_options .= " and a.pack > 0 ";
        }

        //=======================================
        // 검색키워드 또는 메모 선택
        if ($keyword || $search_type == 28 )
        {
            $keyword = trim( $keyword );
            switch ( $search_type )
            {
                // 수령자
                case 0: 
                    if( strpos($keyword, "+") )
                    {
                        $key_arr = explode("+", $keyword);
                        $search_options .= " and a.recv_name = '$key_arr[0]' and (a.recv_tel like '%$key_arr[1]' or a.recv_mobile like '%$key_arr[1]') ";
                    }
                    else
                        $search_options .= " and a.recv_name = '$keyword' ";
                    break;
                // 수령자 부분
                case 14: 
                    if( _DOMAIN_ == 'box4u' )
                        $search_options .= " and (a.recv_name like '%$keyword%' or a.order_name like '%$keyword%') ";
                    else
                        $search_options .= " and a.recv_name like '%$keyword%' ";
                    break;
                // 수령자 전화
                case 1: 
/*
                    if( strlen($keyword) == 4 )
                        $search_options .= " and substring(a.recv_tel,-4) = '$keyword' ";
                    else if( strpos($keyword, "-") === false )
                        $search_options .= " and replace(a.recv_tel,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                    else
*/
                        $search_options .= " and a.recv_tel = '$keyword' ";
                    break;
                // 수령자 핸드폰
                case 2: 
/*
                    if( strlen($keyword) == 4 )
                        $search_options .= " and substring(a.recv_mobile,-4) = '$keyword' ";
                    else if( strpos($keyword, "-") === false )
                        $search_options .= " and replace(a.recv_mobile,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                    else
*/
                        $search_options .= " and a.recv_mobile = '$keyword' ";
                    break;
                // 주소
                case 16: 
                    $search_options .= " and a.recv_address like '%$keyword%' ";
                    break;
                // 수령자 + 구매자
                case 17: 
                    $search_options .= " and (a.recv_name = '$keyword' or a.order_name = '$keyword')";
                    break;
                // 주문자
                case 3: 
                    $search_options .= " and a.order_name = '$keyword' ";
                    break;
                // 주문자 부분
                case 15: 
                    $search_options .= " and a.order_name like '%$keyword%' ";
                    break;
                // 주문자 전화
                case 4: 
/*
                    if( strlen($keyword) == 4 )
                        $search_options .= " and substring(a.order_tel,-4) = '$keyword' ";
                    else if( strpos($keyword, "-") === false )
                        $search_options .= " and replace(a.order_tel,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                    else
*/
                        $search_options .= " and a.order_tel = '$keyword' ";
                    break;
                // 주문자 핸드폰
                case 5: 
/*
                    if( strlen($keyword) == 4 )
                        $search_options .= " and substring(a.order_mobile,-4) = '$keyword' ";
                    else if( strpos($keyword, "-") === false )
                        $search_options .= " and replace(a.order_mobile,'-','') = '" . str_replace(' ','',$keyword) . "' ";
                    else
*/
                        $search_options .= " and a.order_mobile = '$keyword' ";
                    break;
                // 전화번호
                case 19: 
                    $tel_seq_arr = array();
                    
                    $new_tel = preg_replace('/[^0-9]/','',$keyword);
                    if( (int)$new_tel == 0 || strlen($new_tel) < 4 )
                        $search_options .= " and false ";
                    else
                    {
                        if( strlen($new_tel) == 4 )
                            $query_sub = "select seq from tel_info where tel_short='$new_tel'";
                        else
                            $query_sub = "select seq from tel_info where tel='$new_tel'";

                        $result_sub = mysql_query($query_sub, $connect);
                        while($data_sub = mysql_fetch_assoc($result_sub))
                            $tel_seq_arr[] = $data_sub[seq];
                            
                        $tel_seq_str = implode(',',$tel_seq_arr);
                        
                        if( $tel_seq_str )
                            $search_options .= " and a.seq in ($tel_seq_str) ";
                        else
                            $search_options .= " and false ";
                    }
                    break;

                // 관리번호
                case 6: 
                    $search_options .= " and a.seq='$keyword' ";
                    break;
                // 주문번호
                case 7: 
                    $search_options .= " and (a.order_id = '$keyword' or a.order_id = 'C$keyword' or a.order_id = 'CC$keyword' or a.order_id = 'CCC$keyword' or a.order_id = 'CCCC$keyword' or a.order_id = 'CCCCC$keyword' or a.order_id = '*$keyword' or a.order_id like '$keyword%') ";
                    break;
                // 주문번호부분
                case 27: 
                    $search_options .= " and a.order_id like '%$keyword%' ";
                    break;
                // 송장번호
                case 8: 
                    $search_options .= " and a.trans_no='" . str_replace('-', '', $keyword) . "' ";
                    break;
                // 판매처 상품명
                case 9: 
                    $search_options .= " and a.product_name like '%$keyword%' ";
                    break;
                // 판매처 옵션
                case 18: 
                    $keyword = str_replace(" ", "%", $keyword);
                    if( _DOMAIN_ == "eleven2" )
                    {
                        $search_options .= " and b.shop_options like '%$keyword%' ";
                        $use_products = true;
                    }
                    else
                        $search_options .= " and a.options like '%$keyword%' ";
                    break;
                // 판매처 상품코드
                case 10: 
                    $search_options .= " and a.shop_product_id like '%$keyword%' ";
                    break;
                // 어드민 상품명
                case 11: 
                    $_keyword = preg_replace('/\s+/',"%", trim($keyword));
                    $search_options .= " and c.name like '%$_keyword%' ";
                    $use_products = true;
                    break;
                // 어드민 옵션
                case 21: 
                    $search_options .= " and c.options like '%$keyword%' ";
                    $use_products = true;
                    break;
                // 어드민 상품코드
                case 12: 
                    $search_options .= " and c.product_id = '$keyword' ";
                    $use_products = true;
                    break;
                // 구매자ID
                case 13: 
                    $search_options .= " and a.cust_id='$keyword' ";
                    break;
                // 결제수단
                case 20: 
                    $search_options .= " and a.pay_type='$keyword' ";
                    break;
                // CS내용[시스템]
                case 22: 
                    $search_options .= " and d.content like '%$keyword%' ";
                    $use_csinfo = true;
                    break;
                // CS내용[사용자]
                case 29: 
                    $search_options .= " and d.user_content like '%$keyword%' ";
                    $use_csinfo = true;
                    break;
                // 회수송장
                case 23: 
                    $_seq_arr = array();
                    // 반품 송장
                    $query_tb = "select order_seq from return_money where return_trans_no='$keyword'";
                    $result_tb = mysql_query($query_tb, $connect);
                    if( mysql_num_rows($result_tb) )
                    {
                        $data_tb = mysql_fetch_assoc($result_tb);
                        $_seq_arr[] = $data_tb[order_seq];
                    }
                    
                    // 회수송장
                    $query_tb = "select order_seq from takeback_order where trans_no='$keyword'";
                    $result_tb = mysql_query($query_tb, $connect);
                    if( mysql_num_rows($result_tb) )
                    {
                        $data_tb = mysql_fetch_assoc($result_tb);
                        $_seq_arr[] = $data_tb[order_seq];
                    }

                    if( count($_seq_arr) )
                        $search_options .= " and a.seq in (" . implode(",", $_seq_arr) . ") ";
                    else
                        $search_options .= " and a.seq='' ";
                    break;
                // order_id_seq
                case 24: 
                    $search_options .= " and a.order_id_seq = '$keyword' ";
                    break;
                // 쿠팡 딜 이름
                case 25: 
                    $search_options .= " and a.shop_id % 100 = 53 and a.code3 like '%$keyword%' ";
                    break;
                // 상품카테고리
                case 26: 
                    $query_category = "select * from category where name = '$keyword' ";
                    $result_category = mysql_query($query_category, $connect);
                    $data_category = mysql_fetch_assoc($result_category);
                    
                    $use_products = true;
                    $search_options .= " and c.category='$data_category[seq]' ";
                    break;
                // 메모
                case 28:
                    if( $keyword )
                        $search_options .= " and a.memo like '%$keyword%' ";
                    else
                        $search_options .= " and a.memo > '' ";
                    break;
            }
        }
        $query = "select if(a.pack=0,a.seq,a.pack) seq_pack from orders a ";

        //+++++ use index +++++++++++++++++++++++++++++++++++
        if( $keyword && 0 )  // 2014-08-19 장경희. 더 느림
        {
            if( $search_type == 0 || $search_type == 3 || $search_type == 14 || $search_type == 15 || $search_type == 17 )
                $query .= " use index (orders_idx5, orders_idx19) ";
            else if( $search_type == 1 || $search_type == 2 || $search_type == 4 || $search_type == 5 )
                $query .= " use index (orders_idx6, orders_idx7, orders_idx20, orders_idx21) ";
        }

        //+++++ 테이블 +++++++++++++++++++++++++++++++++++
        // products 테이블 조인
        if( $use_products )
            $query .= ", order_products b, products c ";
        // order_products 테이블 조인
        else if( $use_order_products )
            $query .= ", order_products b ";
        // csinfo 테이블 조인
        if( $use_csinfo )
            $query .= ", csinfo d ";
            
        // use_no_csinfo 추가 쿼리
        if( $use_no_csinfo )
            $query_no_csinfo = $query . ($use_csinfo ? "" : ", csinfo d ");
            
        //+++++ where +++++++++++++++++++++++++++++++++++
        $query_where = "";

        // products 테이블 조인
        if( $use_products )
            $query_where .= " where a.seq=b.order_seq and b.product_id=c.product_id ";
        // order_products 테이블 조인
        else if( $use_order_products )
            $query_where .= " where a.seq=b.order_seq ";
        // csinfo 테이블 조인
        if( $use_csinfo )
        {
            if( $use_products || $use_order_products )
                $query_where .= " and a.seq=d.order_seq ";
            else
                $query_where .= " where a.seq=d.order_seq ";
        }

        if( $use_products || $use_order_products || $use_csinfo )
            $query_where .= " and " . $search_options;
        else
            $query_where .= " where " . $search_options;
            
        // use_no_csinfo 추가 쿼리
        if( $use_no_csinfo )
            $query_no_csinfo .= $query_where . ($use_csinfo ? "" : " and a.seq=d.order_seq ");

        $query .= $query_where;
        
        // csinfo 테이블 조인 ** cs 이력 없음 조회
        if( $use_no_csinfo )
        {
            $csinfo_pack_arr = array();
            $query_no_csinfo .= " group by seq_pack";
debug("cs 이력 없음 조회1" . $query_no_csinfo);
            $result_no_csinfo = mysql_query($query_no_csinfo, $connect);
            while( $data_no_csinfo = mysql_fetch_assoc($result_no_csinfo) )
                $csinfo_pack_arr[] = $data_no_csinfo[seq_pack];

            $csinfo_pack_str = implode(",", $csinfo_pack_arr);
            
            $csinfo_pack_arr2 = array();
            if( $csinfo_pack_str )
            {
                $query_no_csinfo2 = "select seq from orders where seq in ($csinfo_pack_str) or pack in ($csinfo_pack_str)";
                $result_no_csinfo2 = mysql_query($query_no_csinfo2, $connect);
                while( $data_no_csinfo2 = mysql_fetch_assoc($result_no_csinfo2) )
                    $csinfo_pack_arr2[] = $data_no_csinfo2[seq];
    
                $query .= " and a.seq not in (" . implode(",",$csinfo_pack_arr2) . ") ";
            }
        }
debug("cs_query : " . $query);
        //*********************************
        // 전체 수량 구하기
        $query_qty = $query . " group by seq_pack";
        $result_qty = mysql_query($query_qty, $connect);
        $total_rows = mysql_num_rows($result_qty);
        $real_total_qty = $total_rows;
            
        //*********************************
        // 범위 seq 구하기
        $seq_arr = array();

        $query_seq = $query . " group by seq_pack order by ";
        switch( $sort_title )
        {
            case 0:  // 관리번호
                $query_seq_sort = " seq_pack ";
                break;
            case 1:  // 발주일
                $query_seq_sort = " collect_date ";
                break;
            case 2:  // 판매처
                $query_seq_sort = " shop_id ";
                break;
            case 3:  // 주문번호
                $query_seq_sort = " order_id ";
                break;
            case 4:  // 주소
                $query_seq_sort = " recv_address ";
                break;
            case 5:  // 연락처
                $query_seq_sort = " recv_tel ";
                break;
            case 6:  // 핸드폰
                $query_seq_sort = " recv_mobile ";
                break;
            case 7:  // 주문자
                $query_seq_sort = " order_name ";
                break;
            case 8:  // 수령자
                $query_seq_sort = " recv_name ";
                break;
            case 9:  // 총수량
                $query_seq_sort = " sum(a.qty) ";
                break;
            default:
                $query_seq_sort = " seq_pack ";
        }
        if( !$sort_direction )
            $query_seq_sort .= " desc ";
        
        if( _DOMAIN_ == 'plays' || _DOMAIN_ == 'lovestar9'  || _DOMAIN_ == 'leroom' || _DOMAIN_ == 'dragon' )
            $query_seq_sort .= " , seq_pack desc ";

        $query_seq .= $query_seq_sort . " limit $start, $limit";
debug("cs 조회:". $query_seq);

        $result_seq = mysql_query($query_seq, $connect);
        while( $data_seq = mysql_fetch_assoc($result_seq) )
            $seq_arr[] = $data_seq[seq_pack];
            
        $seq_str = implode(",", $seq_arr);
        
        $orderby_seq_str = "";
        foreach( array_reverse($seq_arr) as $_seq )
            $orderby_seq_str .= ($orderby_seq_str ? "," : "") . "seq=" . $_seq;
        
        //*********************************
        // 최종 쿼리
        $query_seq_sort = str_replace("seq_pack", "seq", $query_seq_sort);
        $query = "select * from orders where seq in ($seq_str) order by $orderby_seq_str";

        $result = mysql_query($query, $connect);
        
        return $result;
    }

    function get_count($query)
    {
        global $connect;
        
        $result_cnt = mysql_query($query, $connect);
        $list = mysql_fetch_array($result_cnt);
        return $list[cnt];
    }
    ///////////////////////////////////////////////
    // cs의 존재 여부 check
    // date: 2005.12.2
    function cs_exist( $seq )
    {
        global $connect;

        $query = "select count(*) cnt from csinfo where order_seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        if ( $data[cnt] )
            echo "<img src="._IMG_SERVER_."/images/G_cs.gif align=absmiddle>";
        else
            echo "<img src="._IMG_SERVER_."/images/P_cs.gif align=absmiddle>";
    }

    ///////////////////////////////////////////////
    // cs_tooltip by syhwang 2012.11.6
	function cs_tooltip()
	{
		global $connect;

		if($_SESSION[CS_VIEW_ALL] > 0)
        	$query = "select * from csinfo where order_seq='$_REQUEST[seq]' order by input_date desc, input_time desc limit 5";
        else
        	$query = "select * from csinfo where order_seq='$_REQUEST[seq]' AND user_content > '' order by input_date desc, input_time desc limit 5";
        $result = mysql_query ( $query, $connect );

		$ret = "";
		while ($data = mysql_fetch_assoc($result))
		{
			$ret .= "<b>[" . substr($data[input_date],5,5) . "] "
				 . $data[writer] . "</b></br>"
				 . $data[user_content] . "</br>";
		}
		
		echo $ret;
	}
    // cs_tooltip by syhwang 2012.11.6
	function cs_tooltip2()
	{
		global $connect;
		
        $query = "select * from csinfo where order_seq='$_REQUEST[seq]' AND content >'' order by input_date desc, input_time desc limit 5";
        
        $result = mysql_query ( $query, $connect );

		$ret = "";
		while ($data = mysql_fetch_assoc($result))
		{
			$ret .= "<b>[" . substr($data[input_date],5,5) . "] "
				 . $data[writer] . "</b></br>"
				 . $data[content] . "</br>";
		}
		
		echo $ret;
	}   
    ///////////////////////////////////////////////
    // 미처리 CS의 존재 여부
    // Date: 2006.8.14
    function isComplete( $seq )
    {
      global $connect;
      $query = "select count(*) cnt from csinfo where cs_result = 0 and order_seq=$seq";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      return $data[cnt] ? "미처리" : "완료";
    } 

    ///////////////////////////////////////////////
    // 교환
    // date: 2005.9.21 jk
    function change_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty, $top_url;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("교환");

       ////////////////////////////////////////////////
       // 부분 취소 로직을 입력함
       if ( $qty != $org_qty )
           $this->part_cancel_action( "배송후" );
       else
       {
           // 주문의 상태 변경
           $this->change_action();

           // 상태...
           $this->change_cs_result(0); 
       }

       // 주문 생성
       // order_id
       $order_id = "C" . $order_id;

       if ( $order_cs == _exchange )
          $exchange_option = 1;
       else
          $exchange_option = 0;

// echo "create--";
       $this->create_order( $order_id, &$seq, $exchange_option );

       // cs를 남겨야 함
       $this->csinsert(0);



       $this->redirect( $link_url . "&top_url=" . $top_url );

       $this->end( $transaction );
       exit;
    }

    /////////////////////////////////
    // 배송전 교환 요청
    function modify_csinsert()
    {
       global $top_url, $link_url, $order_id, $order_cs, $qty, $org_qty;
       global $seq, $connect;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("변경-교환");

       ////////////////////////////////////////////////
       // 부분 취소 로직을 입력함
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "배송 전" );

           ///////////////////////////////////////
           // 주문 생성 order_id
           $order_id = "C" . $order_id;
    
           if ( $order_cs == _exchange )
              $exchange_option = 1;
           else
              $exchange_option = 0;

           $this->create_order( $order_id, &$seq, $exchange_option );

           ////////////////////////////////////////////////////////////////////
           // 새로운 주문이 생성 되기 때문에 part cancel의 상태를 완료로 변경
           $query = "update part_cancel set status='처리완료' where seq='$seq'";
           mysql_query( $query, $connect );
       }
       else
       {
           $query = "update orders set order_cs = " . _change_req_b . " where seq='$seq'";       
           mysql_query ( $query, $connect );

           // 주문의 내용 변경 
           $this->order_update(0);
       }

       // cs를 남겨야 함
       $this->csinsert(0);

       // opener redirect
       // $this->opener_redirect( $link_url );

       $this->end( $transaction );

       $this->redirect( $link_url . "&top_url=" . $top_url );

       // 완료 페이지
       //$this->redirect( "?template=E109&seq=$seq");
       exit;
    }

    ////////////////////////////////////////////////
    // 반품 
    function refund_csinsert()
    {
       global $link_url, $qty, $org_qty;

       $transaction = $this->begin("반품");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // 부분 취소 확인
       if ( $qty != $org_qty )
           $this->part_cancel_action( "배송후" );
       else
           $this->cancel_action("", _cancel_req_a ); // 배송후 취소 요청

       $this->jsAlert( " 취소 되었습니다 ");

       global $top_url;
       $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );
       $this->end( $transaction );
       exit;
    }

    ////////////////////////////////////////////////
    // 취소 
    // 부분 취소 로직을 넣음
    function cancel_csinsert()
    {
       global $link_url;
       global $qty, $org_qty;

       $transaction = $this->begin("취소");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // 부분 취소 확인
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "배송 전" );
       }
       else
          $this->cancel_action(); 

       //$this->set_small_window(" 취소 되었습니다 ");
       //$this->opener_redirect ( base64_decode($link_url) );

       $this->jsAlert( " 취소 되었습니다 ");

       global $top_url; 
       $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );

       $this->end ( $transaction );

       exit;
    }

    ////////////////////////////////////////
    // 일반 cs내역을 남긴다. jkh 신발주버전
    function csinsert4()
    {
        global $connect, $seq, $pack, $reason, $content;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = 0,
                       cs_reason  = '$reason',
                       cs_result  = '0',
                       user_content    = '$content'";
        mysql_query ( $sql, $connect );
    }

    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. jkh 신발주버전 - 즉시 완료처리
    function csinsert6( $pack, $cs_type=0, $content = '', $cs_reason='', $do_complete, $is_bck=0 )
    {
        global $connect, $seq, $bck_connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$do_complete',
                       user_content    = '$content'";
        mysql_query ( $sql, $connect );

        if( $is_bck )
        {
            // 입력된 csinfo의 seq를 구해서 그걸로 작업해야함
            $query = "select seq from csinfo where order_seq='$seq' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);

            $sql .= ", seq=$data[seq]";
            mysql_query ( $sql, $bck_connect );
        }
    }
    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. choi_ung ..sys_content 추가    
    function csinsert9( $seq, $pack, $cs_type=0, $content = '', $sys_content = '', $cs_reason='', $is_bck=0, $cs_result=0 )
    {
        global $connect, $bck_connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$cs_result',
                       content    = '$sys_content',
                       user_content = '$content'";
        if($cs_result == 1)
        	$sql .=", complete_date = now()";
        	
        mysql_query ( $sql, $connect );

        if( $is_bck )
        {
            // 입력된 csinfo의 seq를 구해서 그걸로 작업해야함
            $query = "select seq from csinfo where order_seq='$seq' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $sql .= ", seq=$data[seq]";
            mysql_query( $sql, $bck_connect );
        }
    }
    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. choi_ung ..sys_content 추가    
    function csinsert8( $pack, $cs_type=0, $content = '', $sys_content = '', $cs_reason='', $is_bck=0, $cs_result=0 )
    {
        global $connect, $seq, $bck_connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$cs_result',
                       content    = '$sys_content',
                       user_content = '$content'";
        if($cs_result == 1)
        	$sql .=", complete_date = now()";
        	
        mysql_query ( $sql, $connect );

        if( $is_bck )
        {
            // 입력된 csinfo의 seq를 구해서 그걸로 작업해야함
            $query = "select seq from csinfo where order_seq='$seq' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $sql .= ", seq=$data[seq]";
            mysql_query( $sql, $bck_connect );
        }
    }

    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. 오매칭 교환 cs 남기기
    function csinsert10( $pack, $cs_type=0, $content = '', $sys_content = '', $cs_reason='', $is_bck=0, $cs_result=0, $other_seq )
    {
        global $connect, $seq, $bck_connect;

        $sql = "insert csinfo 
                   set order_seq  = '$other_seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$cs_result',
                       content    = '$sys_content',
                       user_content = '$content'";
        if($cs_result == 1)
        	$sql .=", complete_date = now()";
        	
        mysql_query ( $sql, $connect );

        if( $is_bck )
        {
            // 입력된 csinfo의 seq를 구해서 그걸로 작업해야함
            $query = "select seq from csinfo where order_seq='$other_seq' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $sql .= ", seq=$data[seq]";
            mysql_query( $sql, $bck_connect );
        }
    }

    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. jkh 신발주버전
    
    function csinsert3( $pack, $cs_type=0, $content = '', $cs_reason='', $is_bck=0, $cs_result=0 )
    {
        global $connect, $seq, $bck_connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$cs_result',
                       user_content    = '$content'";
        if($cs_result == 1)
        	$sql .=", complete_date = now()";
        	
        mysql_query ( $sql, $connect );

        if( $is_bck )
        {
            // 입력된 csinfo의 seq를 구해서 그걸로 작업해야함
            $query = "select seq from csinfo where order_seq='$seq' order by seq desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $sql .= ", seq=$data[seq]";
            mysql_query( $sql, $bck_connect );
        }
    }

    ////////////////////////////////////////
    // cs 처리 후, cs내역을 남긴다. jkh 신발주버전
    function csinsert7( $pack, $cs_type=0, $content = '', $cs_reason='', $seq )
    {
        global $connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '0',
                       user_content    = '$content'";
        mysql_query ( $sql, $connect );
    }

    // cs 처리 후, cs내역을 남긴다.
    function csinsert5( $seq, $pack, $cs_type=0, $content = '', $cs_reason='', $cs_result=0 )
    {
        global $connect;

        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$pack',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '$cs_type',
                       cs_reason  = '$cs_reason',
                       cs_result  = '$cs_result',
                       user_content    = '$content'";
        mysql_query ( $sql, $connect );
    }

    ////////////////////////////////////////
    // cs내역을 남긴다.
    // date: 2006.8.9 - jk.ryu
    // 
    function csinsert2( $seq, $content, $cs_reason='' )
    {
        global $connect;

        $writer = $_SESSION[LOGIN_NAME];

        $sql = "insert into csinfo set 
                  order_seq = '$seq',
                  input_date = now(),
                  input_time = now(),
                  writer = '$writer',
                  cs_type = '$cs_type',
                  cs_reason = '$cs_reason',
                  cs_result = '0',
                  user_content = '$content',
                  trans_who='$trans_who',
                  trans_fee='$trans_fee'
        ";
        mysql_query ( $sql, $connect );
    }

    ////////////////////////////////////////
    //
    function csinsert( $link_option = "1" )
    {
        global $connect;
        global $template, $order_seq, $cs_type, $cs_reason, $cs_result, $content, $trans_who, $trans_fee;

        $transaction = $this->begin( "일반상담" );

        $order_seq = $_REQUEST[order_seq] ? $_REQUEST[order_seq] : $order_seq;
        $cs_type   = $_REQUEST[cs_type] ? $_REQUEST[cs_type] : $cs_type;
        $writer    = $_SESSION[LOGIN_NAME];
        $cs_reason = $_REQUEST[cs_reason] ? $_REQUEST[cs_reason] : $cs_reason;
        $cs_result = $_REQUEST[cs_result] ? $_REQUEST[cs_result] : $cs_result;
        $content   = $_REQUEST[content] ? $_REQUEST[content] : $content;
        $content   =  $content;

        $link_url = "?template=$template&seq=$order_seq";

        $sql = "insert into csinfo set 
                  order_seq = '$order_seq',
                  input_date = now(),
                  input_time = now(),
                  writer = '$writer',
                  cs_type = '$cs_type',
                  cs_reason = '$cs_reason',
                  cs_result = '0',
                  user_content = '$content',
                  trans_who='$trans_who',
                  trans_fee='$trans_fee'
        ";

         mysql_query($sql, $connect) or die(mysql_error());
        /////////////////////////////////////////////////
        // 취소 요청일 경우 order의 order_cs의 상태를 취소로 변경해야 함
        /*
        switch ( $cs_type )
        {
           case 1: // 취소
              $this->cancel_action( $order_seq );   
           break;
           case 2:  
           break;
           case 3: 
           break;
        }
        */

        $this->end( $transaction );

        global $top_url;
        if ( $link_option )
           $this->redirect(  $link_url  . "&top_url=" . $top_url );
          // $this->redirect ( $link_url );
    }


    ///////////////////////////////////
    // 취소 주문 가능한 주문인지 여부 확인
    function enable_cancel( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // 정상이 아니면 취소 금지
       if ( $order_cs )
          echo "disabled";
    }

    function enable_change( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // 정상이 아니면 취소 금지
       if ( $order_cs )
          echo "disabled";
    }

    

    ///////////////////////////////////////////////////////
    // 전체 항목을 정상으로 돌림
    // Date: 2006.9.6 - jk.ryu
    function set_normal_all()
    {
        global $connect, $order_seq, $link_url;
        $seq = $order_seq;
        $_pack = "";

        // pack번호를 가져온다
        $sql = "select pack,status,order_cs from orders where seq='$seq'";
        $result = mysql_query ( $sql, $connect );
        $data = mysql_fetch_array ( $result );
        $_pack = $data[pack] ? $data[pack] : $seq;
        $_seq  = $seq;

        //========================================
        // 배송중인 상품은 정상으로 만들 수 없다 만들어 무엇하리~
        // 2007.11.20 -jk
        if ( $data[status] == 8 )
        {
            echo "이미 배송 중 입니다.";
            exit;
        }   

        if ( $_SESSION[USE_3PL] && $data[status] == 7 )
        {
            echo "송장 상태 입니다. 3PL 사용업체는 송장을 삭제 해 주십시요.";
            exit;
        }   

       $transaction = $this->begin("전체 주문복원 합포번호: $data[pack]/stat: $data[status]/cs: $data[order_cs]/L: $_SESSION[LOGIN_LEVEL]");
       $this->csinsert2( $seq, "전체 항복 주문 복귀:[ 합포번호: $data[pack] ]");

       // 주문에 속한 seq들을 가져온다
       if ( $data[pack] )
       { 
         $query = "select seq from orders where pack='$data[pack]'";
         $result = mysql_query ( $query, $connect );

         $i = 0;
         $seq = ""; 
         
         while ( $data = mysql_fetch_array ( $result ))
         {
           if ( $i ) $seq .= ",";
           $seq .= $data[seq];
           $i++;
         }
       }

        // 관리자는 모든 항목을 정상으로 만들 수 있음
        if ( $_SESSION[LOGIN_LEVEL] >= 8 )
        {
            $query = "update orders set order_cs=0 
                       where seq in ($seq) ";
        }
        else
        {
            $query = "update orders set order_cs=0 
                       where seq in ($seq) 
                         and order_cs not in ( 3,4,7,8,10)";
        }
        mysql_query ( $query, $connect );
        echo "전체 항목 전상 주문 복귀 완료";

        // 3pl용 부분
        // 2007.11.20 - jk
        if ( $_SESSION[USE_3PL] )
        {
            $_obj = new class_3pl();

            // 개별에서도 전체 정상을 누르는 경우가 있음
            if ( $_pack )
                $_obj->set_normal_all( $_pack );
            else
                $_obj->set_normal( $_seq );
        }
    }

    ////////////////////////////////////////////////
    // 정상 전환
    function set_normal ( $order_cs='0' )
    {
       global $connect, $seq, $link_url;

       $transaction = $this->begin("주문복원 $seq");

       $query = "select order_id, status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

        if ( $data[status] == 8 )
        {
            echo "배송 상태 입니다.";
            exit;
        }

        if ( $_SESSION[USE_3PL] && $data[status] == 7 )
        {
            echo "송장 상태 입니다.송장을 삭제해 주십시요!";
            exit;
        }


       switch ( $data[order_cs] )
       {
           case 0:
              echo "이미 정상 주문 입니다.";
              exit;
           break;
           case _cancel_com_b:
           case _cancel_com_a:
              $this->end( $transaction, "주문복원오류" );
              echo "이미 취소 되었습니다";
              exit;
           break;
           case _change:
           case _change_reg_a:
           case _change_com_a:
              $this->end( $transaction, "주문복원오류" );
              echo ("교환요청 주문입니다"); 
              //$this->jsAlert("교환요청 주문입니다"); 
              //$this->back();
              exit;
           break;
           case _exchange:
           case _exchange_com:
              $this->end( $transaction, "주문복원오류" );
              echo ("맞교환요청 주문입니다"); 
              exit;
           break;
           case _change_req_b:  // 배송전 교환 요청=>주문의 상태가 확인 이하
              if ( $data[status] >= _trans_confirm )
              {
                 $this->end( $transaction, "주문복원오류" );
                 echo ("이미 배송 단계 입니다."); 
                 exit;
              }
           break;
           case _change_req_a:  // 배송후 교환 요청=>교환 발주의 상태가 확인 이하
              $query = "select status from orders where order_id = 'C" . $data[order_id] . "'";
              $result = mysql_query ( $query, $connect );
              $data = mysql_fetch_array ( $result );

              if ( $data[status] >= _order_confirm )
              {
                 $this->end( $transaction, "주문복원오류" );
                 echo ("이미 배송 단계 입니다."); 
                 exit;
              }
           break;
       } 

       $query = "update orders set order_cs=0 where seq='$seq'";
       mysql_query ( $query , $connect );
       $this->end( $transaction );

echo "정상 주문 복귀 완료";

        //==========================
        // 3pl 처리 부분
        if ( $_SESSION[USE_3PL] && $seq )
        {
            $obj = new class_3pl();
            $obj->set_normal( $seq );
        }

 
       $this->csinsert2( $seq, "정상주문 복귀 [code: $seq]");

       //$this->redirect( $link_url );
       //global $top_url;
       //$this->redirect( base64_decode( $link_url ). "&top_url=" . $top_url );
       exit;
    }

    /////////////////////////////////////////////////
    // 교환 발주 생성
    // date: 2005.9.21 jk 
    function order_change()
    {
       global $connect, $seq;

       ////////////////////////////////////
       // 정보를 가져온다.
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       //////////////////////////////////
       // 1. 취소인지 check
       switch ( $data[order_cs] )
       {
          case _cancel_req_b:
          case _cancel_req_a:
             $this->set_small_window ("주문을 정상으로 돌린 후 처리 하세요");
             exit;
          break;
          case _cancel_com_b:
          case _cancel_com_a:
             $this->set_small_window ("취소 완료된 주문은 교환이 불가능합니다");
             exit;
          break;
          case _change_req_b:
          case _change_req_a:
             $this->set_small_window ("이미 교환된 주문 입니다");
             exit;
          break;
          case _change_com_b:
          case _change_com_a:
             $this->set_small_window ("교환 발주된 주문에서 처리 해야 합니다");
             exit;
          break;
       }

       //////////////////////////////////
       // 2. 정상
       // 2.1 배송 전
       if ( $data[status] < _order_confirm )
       {
          $this->opener_redirect ( "template.htm?template=E102&seq=$seq" );
          $this->closewin();
          exit;
       }
       // 2.2 배송 후
       else if ( $data[status] >= _trans_no || $data[status] == _order_confirm )
       {
          $this->redirect("?template=E108&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }

       // 3.3 처리 불가
       else
       {
          $this->set_small_window("처리 불가 상태 입니다");
          exit;
       }
    }

    /////////////////////////////////////////////////
    // 원 주문의 상태를 1: 취소로 변경한다
    // Date: 2005.09.20 jk
    function order_cancel()
    {
       global $connect, $seq, $cancel_cs_reason;

       $cs_reason = $cancel_cs_reason;  // cancel_cs_reason
       $cs_type = 1;                    // 취소

       $transaction = $this->begin( "취소" );

       ///////////////////////////////////////////////////
       // 상태 정보가져 온다
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       /////////////////////////////////////////////////
       // step 1. order_cs 확인
       // CS가 완료된 경우 이전 CS내용을 확인해야 함
       // 1. 이미 취소 완료된 주문의 재 취소 불가
       // 2. 교환 주문이 생성된 원 주문은  취소 불가 
       switch ( $data[order_cs] )        
       {
           // 불가 case 들
           case _cancel_req_b: // 배송전 취소 요청
           case _cancel_req_a: // 배송후 취소 요청
//             $this->set_small_window ( "이미 취소 요청이 되어 있음" );
             $this->jsAlert( "이미 취소 요청이 되어 있음" );
             $this->back();
             exit;
           break;
           case _cancel_com_b:
           case _cancel_com_a:
             //$this->set_small_window ( "취소 완료 되었습니다" );
             $this->jsAlert( "취소 완료 되었습니다" );
             $this->back();
             exit;
           break;
           case _change_req_a:
           case _change_com_b:
           case _change_com_a:
           case _exchange_com:
             //$this->set_small_window ( "교환 발주된 주문에서 처리 해야 합니다" );
             $this->jsAlert( "교환 발주된 주문에서 처리 해야 합니다" );
             $this->back();
             exit;
           break;

       }

       //////////////////////////////////////////
       // step 2. 배송 전 취소 주문 확인 전 / 후 check
       // 주문 다운받은 후 배송 확인을 함  
       // 2.1. 확인 전
       if ( $data[status] < _order_confirm )
       {
          $link_url = base64_decode( $_REQUEST["link_url"] );
          $this->cancel_action( $seq );  
          $this->set_small_window ( "취소 완료");
          $this->closewin();
          $this->opener_redirect( $link_url );
          exit;
       }
       ////////////////////////////////////////
       // 2.2. 확인 후 
       // 배송 확인 후 취소 여부를 결정 후 취소 해야 함 -> 취소 관련 CS를 남김
       // 취소 불가일 경우는 반품임 
       else if ( $data[status] >= _trans_no ) 
       {
          /////////////////////////////////////////////////////
          // 송장 번호 입력 후 => 무조건 반품
          $this->redirect("?template=E107&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }    
       else
       {
          ///////////////////////////////////////////////////
          // 배송 여부를 확인해야 하는 상태
          $this->redirect("?template=E106&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }
    }

    //////////////////////////////////////////////////////
    // 상태 변경
    function change_cs_result( $redirect_option = 1)
    {
       global $order_seq, $connect, $link_url, $cs_result;

       $transaction = $this->begin( "처리상태변경" );

       $query = "select status, order_cs from orders where seq='$order_seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );
      
       //////////////////////////////////////////////////////////////
       // To 처리 완료
       // To 처리중은 진행하지 않는다 => 데이터복구는 불가능

       if ( $cs_result )
       {
          switch ( $data[order_cs] )
          {
             // 배송전 취소 요청
             case 12:        // 배송전 취소 확인
             case _cancel_req_b: 
                $query = "update orders set order_cs = '" . _cancel_com_b ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // 배송후 취소 요청
             case _cancel_req_a: 
                $query = "update orders set order_cs = '" . _cancel_com_a ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // 배송전 교환 요청
             case _change_req_b: 
                $query = "update orders set order_cs = '" . _change_com_b ."' where seq='$order_seq'";
                mysql_query ( $query );
             break;
          }
       }

       if ( $cs_result == 0 )
          $this->end( $transaction, "CS진행중설정"); 
       else
          $this->end( $transaction, "CS완료설정"); 

       $query = "update csinfo set cs_result='$cs_result' where order_seq='$order_seq'";
       mysql_query ( $query, $connect );

//echo "url->" . urldecode($link_url);

       $this->end( $transaction );

       if ( $redirect_option )
       {
          global $top_url;
          $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );
          exit;
       }
    }

    ///////////////////////////////////////////////////////
    // 부분 취소 실행
    // date: 2005.11.2
    function part_cancel_action( $seq, $order_status, $qty )
    {
       global $connect, $seq, $qty, $org_qty, $options, $memo;
       
       // 주문의 개수를 변경함 
       // 원 주문의 개수는 변경하지 않는다 - 2005.12.5 jk
       /*
       $change_qty = $org_qty - $qty;
       $query = "update orders set qty='$change_qty', options='" . addslashes($options) . "', memo='" . addslashes($memo) ."' where seq='$seq'";
//echo $query;
//exit;
       mysql_query ( $query, $connect );
       */
        $this->set_hold2( $seq, 5);
       // part_cancel에 값을 넣는다.
       $query = "insert part_cancel set order_seq='$seq', cancel_req_date=Now(), order_status='$order_status', status='미처리', qty='$qty'";

       mysql_query ( $query, $connect );
    }

    ///////////////////////////////////////////////////////
    // 취소 실행
    function cancel_action( $seq, $status = _cancel_req_b )
    {
       global $connect;
    
       $query = "update orders set order_cs=$status, refund_cs_date=Now() 
                  where seq='$seq' and order_cs not in (1,2,3,4,7,8)";
       $result = mysql_query ( $query, $connect );
       $cnt = mysql_affected_rows ();

        // hold값 5: 취소 보류
        // 2008.5.20 - jk
        class_E900::set_hold2( $seq, 5);

        //======================================
        // 3pl을 사용하는 업체의 경우엔. 상태를 변경해 줌 
        // 2007.11.16 -jk
        if ( $_SESSION[USE_3PL] )
        {
             $obj_3pl = new class_3pl();
            $obj_3pl->sync_cs ( $seq, $status );
        }

       if ( $cnt ) return 1;
       else return 0;
    }

    /////////////////////////////////////////////////////
    // 교환 실행
    function change_action( $seq="" )
    {
       global $connect, $order_cs, $status;
       if ( $_REQUEST["seq"] )
         $seq = $_REQUEST["seq"]; 

       $query = "update orders set order_cs=" . $order_cs. " where seq='$seq'";
       mysql_query ( $query, $connect );

        // 교환 일까?        
        if ( $_SESSION[USE_3PL] )
        {
             $obj_3pl = new class_3pl();
            $obj_3pl->sync_cs ( $seq, $order_cs);
        }
    }



    ///////////////////////////////////////////////////////
    // window의 size를 작게 만들어 준다.
    function set_small_window( $text )
    {
       //echo "<img src='"._IMG_SERVER_."/images/can_link.gif' name=img_main> " . $text;
?>
<style type="text/css">
<!--
.text {
        font-family: "굴림", "돋움", Seoul, "한강체";
        font-size: 12px;
        font-style: normal;
        font-weight: bold;
        color: #FF3300;
}
-->
</style>

<table border="0" cellpadding="0" cellspacing="0" width=100%>
  <tr>
    <td width="142" align="right"><img src="<?=_IMG_SERVER_?>/images/img.gif" name=img_main></td>
        <td width="258"><span class=text><?= $text ?></span></td>
  </tr>
  <tr>
        <td colspan="2" align="center">
        <a href="javascript:self.close()"><img src="<?=_IMG_SERVER_?>/images/done_btn.gif" border="0"></a>&nbsp;&nbsp;
        <a href="javascript:self.close()"><img src="<?=_IMG_SERVER_?>/images/close_btn.gif" border="0"></a></td>
  </tr>
</table>

<script language=javascript>
   tid=setTimeout( resize ,200);

   function resize()
   {
      if ( document.images["img_main"].complete )
      {
         window.resizeTo ( 470, 240 )
         setTimeout ( auto_close, 4000 )
      }
      else
         tid=setTimeout( resize ,200);
   }

   function auto_close()
   {
       self.close();
   }

</script>
<?
    }

    ///////////////////////////////////////////////////
    // 
    function disp_btn ( $code )
    {
        switch ( $code )
        {
           case _cancel_req_b: // 정상 주문으로 변경 가능
           case _cancel_req_a: 
           case _change_req_b:
           case _change_reg_a:
           case _change_com_b:
              echo "&nbsp;&nbsp; <a href=javascript:set_normal() class=btn3>정상주문으로 복귀</a>";
           break;
        }
    }

    ////////////////////////////////////////////////
    // log 이력 조회
    // for Ajax log info
    // date: 2006.8.14 - jk
    function get_loghistory ()
    {
      global $connect, $seq;

      // 합포인지 여부판단
      $query = "select pack from orders where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $pack = $data[pack];

      if ( $pack )
      {
              $query = "select seq from orders where pack='$pack'";
              $result = mysql_query ( $query, $connect );

              $cnt = 0;
              if ( mysql_num_rows ( $result ) > 1 )
              { 
                $seq = "";
                while ( $data = mysql_fetch_array ( $result ) )
                {
                        if ( $cnt > 0 )
                                $seq.= ",";

                        $seq .= "$data[seq]";

                        $cnt++;
                }
              }
      }
?>
<table cellspacing=0 cellpadding=0 border=0 style='padding:2px' width=100%>
<?
  $sql = "select * from transaction where target_id in ($seq) ";

  if ( $_SESSION[LOGIN_LEVEL] != 9 )
      $sql .= " and owner <> 'root'";

  $sql .= " order by commit_date desc, starttime desc";

// echo $sql;

  $result = mysql_query($sql, $connect);
 
  $cs_result = 1;
  while ($list2 = mysql_fetch_array($result))
  {             
    if ( $cs_result )
       if ( !$list2[cs_result] )
          $cs_result = 0;
?>                    
    <tr>                 
      <td width=120 align=center><?= $list2[commit_date] ?>  <?=$list2[starttime]?></td>
      <td width=60>[<?=$list2[target_id]?>]</td>
      <td><?=$list2[status]?></td>
      <td width=70 align=right><b><?=$list2[owner]?></b></td>
    </tr>          
<?
  }
      echo "</table>";
    }


    ////////////////////////////////////////////////
    // CS 이력 조회
    // for Ajax CS info
    // date: 2006.7.29 - jk
    function get_cshistory ()
    {
      global $connect, $seq;
?>
<table cellspacing=0 cellpadding=0 border=0 style='padding:2px' width=100%>
<?
  // pack번호를 가져온다
  $sql = "select pack from orders where seq='$seq'";
  $result = mysql_query ( $sql, $connect );
  $data = mysql_fetch_array ( $result );

  // 주문에 속한 seq들을 가져온다
  if ( $data[pack] )
  {
    $query = "select seq from orders where pack='$data[pack]'";
    $result = mysql_query ( $query, $connect );

    $i = 0;
    $seq = "";

    while ( $data = mysql_fetch_array ( $result ))
    {
      if ( $i ) $seq .= ",";

      $seq .= $data[seq];
      $i++;
    }
  }

  $sql = "select * from csinfo where order_seq in ($seq)";
  if ( $_SESSION[LOGIN_LEVEL] != 9 )
  {
       $sql.=" and writer <> '관리자'";
  }
  $sql .= " order by input_date desc, input_time desc";
//echo $sql;

  $result = mysql_query($sql, $connect);
                    
  $cs_result = 1;

  // 값이 있을경우만 조회
  if ( $result )
  while ($list2 = mysql_fetch_array($result))
  {             
    if ( $cs_result )
       if ( !$list2[cs_result] )
          $cs_result = 0;
?>                    
    <tr>                 
      <td width=5><img src=<?=_IMG_SERVER_?>/images/btn_x.gif alt='상담완료'></td>
      <td width=92> 상태: [<?= $list2[cs_result] ? "처리완료" : "미처리" ?>] </td>
      <td>· 접수일시 : <?=$list2[input_date]?> <?=$list2[input_time]?></td>
      <td>· 접수자 : <b><?=$list2[writer]?></b></td>
      <td><span class=red><?=$list2[cs_reason]?></span></td>
    </tr>          
    <tr>
      <td colspan=5>· <b>내용</b> : [ <?= $list2[order_seq] ?>] <?=nl2br($list2[content])?></td>
    </tr>
    <tr><td height=1 bgcolor=#CFCFCF colspan=5></td></tr>
<?
  }
      echo "</table>";
    }


    ////////////////////////////////////////////////
    // 미처리 CS의 완료 처리
    // date: 2006.8.14 - jk.ryu
    // rule : 배송 전 취소 
    // is_all: 0: 부분 완료 / 1: 전체 완료
    function set_complete()
    {
      global $connect, $seq, $is_all;
      $transaction = $this->begin("CS 완료 처리");

        if ( $is_all )
        {
            // 전체 완료로 변경
            $query = "select pack, is_bck from orders where seq=$seq";
            $result = mysql_query ( $query, $connect );
            $data   = mysql_fetch_array ( $result );
            
            if( $data[is_bck] )
                $is_bck = 1;
            else
                $is_bck = 0;

            // 완료처리 cs 추가
            $this->csinsert3($data[pack], 100, $content, "", $is_bck);

            if ( $data[pack] )
                $query = "select seq,pack,order_cs from orders where pack=$data[pack]";
            else
                $query = "select seq,pack,order_cs from orders where seq=$seq";

            $result = mysql_query ( $query, $connect );

            while ( $data= mysql_fetch_array( $result ) )
                class_cs::set_complete( $data[order_cs], $data[seq], $is_bck );
                
            // 회수 완료 jkh
            $val = class_takeback::get_takeback_info_seq($seq);            
            if( $val[error] == 0 && $val[status] == 5 )
                class_takeback::set_complete($val[number]);
        }
        else // 부분 완료
        {
            $query = "select order_cs from orders where seq='$seq'";
            $result = mysql_query ( $query, $connect );
            class_cs::set_complete( $data[order_cs], $seq );
        }

        $this->end($transaction);
        echo "완료";
    }

    function get_product()
    {
        global $product_id, $connect;
        $val = array();

        $query  = "select * from products where product_id='$product_id'";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );

        $val[product_id] = $product_id;
        if ( $data[org_id] )
        {
            $query  = "select * from products where product_id='$data[org_id]'";
            $result = mysql_query ( $query, $connect );
            $data   = mysql_fetch_array ( $result );
            $val[product_id] .= "($data[product_id])";
        }

        $val[org_id]     = $data[product_id];
        $val[img]        = $data[img_500] ? $data[img_500] : "";
        $val[img_500]    = $data[img_500];
        $val[name]       =  $data[name] ;
        $val[options]    =  $data[options] ;
        $val[shop_link]  = $data[shop_link] ? $data[shop_link] : "X";
        $val[shop_price] = $data[shop_price];

        echo json_encode( $val );
    }

    ////////////////////////////////////////////////
    // CS 상세 정보
    // for Ajax CS info
    // date: 2006.7.28 - jk
    // last update: 2006.8.14
    function get_detail2 ()
    {
       global $connect, $seq, $product_id;

       $data = class_takeback::get_detail( $seq );
        $_return[reg_date]      = $data[reg_date]?$data[reg_date]:"";
        $_return[trans_date]    = $data[trans_date]?$data[trans_date]:"";
        $_return[trans_no]      = $data[trans_no]?$data[trans_no]:"";
        $_return[complete_date] = $data[complete_date]?$data[complete_date]:"";

        if ( isset($data[status] ) )
            $_is_return = 1;
        else
            $_is_return = 0;

       ///////////////////////////////////////
       $sql = "select * from orders where seq = '$seq'";
       $data = mysql_fetch_array(mysql_query($sql, $connect));

       // product_id가 있는 경우 처리
       $product_id = $product_id ? $product_id : $data[product_id];

       class_product::get_product_name_option($product_id, &$product_name, &$product_option);
       $order_status = $this->get_order_status($data[status],1);
       $order_cs     = $this->get_order_cs($data[order_cs],1 );

       $trans_corp   = class_E::get_trans_name( $data[trans_corp] );

        if ( $_SESSION[USE_3PL] && $data[status] < 7 )        
        {
             $obj_3pl = new class_3pl();
            $trans_info = $obj_3pl->get_print_log( $data[pack] ? $data[pack] : $data[seq] );
        }
        else
            $trans_info   = class_top::print_delivery( $trans_corp, $data[trans_no] );
                

       $shop_name    = class_C::get_shop_name( $data[shop_id] );

        if ( _DOMAIN_ == "kbj" || $data[shop_id] == "10093" )
            $shop_name .= "( $data[code2] )";

        if ( _DOMAIN_ == "pnb" || $data[shop_id] == "10087" )
            $shop_name .= "( $data[code2] )";

        if ( $data[shop_id]%10 == 1 )
            $shop_name .= "( $data[code1] )";

       $star_name    = class_star::get_name( $data[star_code] );
       $supply_name  = class_vendor::get_name( $data[supply_id] );
       $org_price    = $data[org_price];

        if ( _DOMAIN_ == "pdcock" ) // 2008.11.14 pdcock는 체결가를 사용해 달라고 요청 -> pdcock의 박창원씨 요청
            $shop_price   = $data[code1] ? $data[code1] : $data[amount];
        else
            $shop_price   = $data[shop_price];

       $part_cancel  = $this->get_part_cancel_count( $seq );
       $cs_status    = $this->isComplete( $seq );
       $hold         = $this->hold_string( $data[hold] );        // 2008.1.9 보류 추가 jk

       $message      = str_replace( array("\n","\r","\r\n"), "", $data[message] );
       $memo         = str_replace( array("\n","\r","\r\n"), "", $data[memo] );
       $options      = str_replace( array("\n","\r","\r\n"), "", $data[options] );
       $product_option = str_replace( array("\n","\r","\r\n"), "", $product_option );

        $pos = strpos ( $data[recv_zip], "-" );
        if ( $pos ) 
                list($zip1, $zip2) = split("-", $data[recv_zip] );
        else
        {
                $zip1 = substr($data[recv_zip],0,3);
                $zip2 = substr($data[recv_zip],3,3);
        }        
      
        $trans_no = str_replace( array("\n","\r","\r\n"), "", $data[trans_no] );
        $order_seq = $data[order_seq] ? "(" . $data[order_seq] . ")" : "";

        if ( _DOMAIN_ == "codipia" )
                if ( $data[owner] == "양두나/auto" )
                        $data[owner] = "양두나";

        // 미송 notyet 관련
        // 2008.5.2 - jk
        $notyet_infos = class_notyet::get_infos( $data[seq] );

        $val = array();
        $val[notyet_reg_date] = $notyet_infos[crdate];
        $val[notyet_trans_no] = "$notyet_infos[trans_no] / $notyet_infos[trans_corp] / $notyet_infos[trans_date]";
        $val[notyet_trans_date] = $notyet_infos[trans_date];
        $val[notyet_trans_date_pos] = $notyet_infos[trans_date_pos];
        $val[seq] = $data[seq];
        $val[pack] = $data[pack] ? $data[pack] : "";
        $val[order_id]     = $data[order_id];
        $val[shop_id]      = $data[shop_id];
        $val[product_id]    = $product_id;
        $val[shop_product_id] = $data[shop_product_id];
        $val[order_seq]     = $order_seq;
        $val[priority]      = $data[priority] ? $data[priority] : 0;
        $val[order_name]    =  "$data[order_name] / $shop_name";
        $val[recv_name]     =  $data[recv_name];
        $val[real_product_name] = $data[product_name];
        $val[qty]           = $data[qty];
        $val[part_cancel]   = $part_cancel;
        $val[product_name]  = "$data[warehouse] / $product_name [매칭작업: $data[owner] ]";
        $val[product_option]=  $product_option;
        $val[real_option]   = $options;
        $val[option]        = $product_option;
        $val[message]       = $message;
        $val[memo]          = $memo;
        $val[order_tel]     = $data[order_tel];
        $val[trans_date]    = $data[trans_date] ? $data[trans_date] : "";
        $val[trans_date_pos] = $data[trans_date_pos] ? $data[trans_date_pos] : "";
        $val[qty]           = $data[qty];
        $val[part_cancel]   = $part_cancel;
        $val[order_mobile]  = $data[order_mobile];
        $val[recv_tel]      = $data[recv_tel];
        $val[recv_mobile]   = $data[recv_mobile];
        $val[trans_who]     =  $data[trans_who];
        $val[trans_price]   = $data[trans_price];
        $val[trans_info]    = "$trans_no/$trans_info";
        $val[trans_no]      = $trans_no;
        $val[trans_corp]    = $data[trans_corp];
        $val[recv_address]  = $data[recv_address];
        $val[recv_zip1]     = $zip1;
        $val[recv_zip2]     = $zip2;
        $val[order_cs]      = $order_cs;
        $val[cs_code]       = $data[order_cs];
        $val[status_code]   = $data[status];
        $val[item_order_cs] = $data[order_cs];
        $val[org_price]     = "$org_price 원";
        $val[shop_price]    = "$shop_price 원";
        $val[change_shop_price] = $data[shop_price];
        $val[order_status]  =  $order_status;
        $val[star_name]     = $star_name;
        $val[supply_name]   = $supply_name;
        $val[cs_status]     =  $cs_status;
        $val[warehouse]     = $data[warehouse];
        $val[hold]          = $hold;
        $val[gift]          = $data[gift];
        $val[supply_id]     = $data[supply_id];
        $val[code9]         = $data[code9];
        $val[code10]        = $data[code10];
        $val[collect_time]  = $data[collect_date] . " " . $data[collect_time];
        $val[refund_cs_date]= $data[refund_cs_date];
        $val[refund_date]   = $data[refund_date];
        $val[org_trans_who] =  $data[org_trans_who] ;

        $val[is_return]     = $_is_return;
        $val[return_reg_date] = $_return[reg_date];
        $val[return_trans_date] = $_return[trans_date];
        $val[return_trans_no] = $_return[trans_no];
        $val[return_complete_date] = $_return[complete_date];

        echo json_encode( $val );
    }

    ////////////////////////////////////////////////
    // CS 상세 정보
    function get_detail ()
    {
       global $connect;
       ///////////////////////////////////////
       $seq = $_REQUEST["seq"];

       ///////////////////////////////////////
       $sql = "select * from orders where seq = '$seq'";
       $list = mysql_fetch_array(mysql_query($sql, $connect));

       return $list;
    }

   // org_id return
   function get_org_id( $product_id )
   {
       return class_E::get_org_id( $product_id );
   }

   ////////////////////////////////////////////////////////
   // option이 
   function option_string( $product_id )
   {
      global $connect;

      $query = "select options from products where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

?>
      <table border=0 cellpadding=0 cellspacing=0>
         <tr>
            <td width=100 align=center><img src=<?=_IMG_SERVER_?>/images/li.gif align=absmiddle> 상품 옵션</td>
            <td width=1 bgcolor=cccccc></td>
            <td width=4></td>
            <td width=300><?= nl2br($data[options]) ?></td>
         </tr>
      </table>
<?
   }
   ////////////////////////////////////////////////////////
   // option이 
   function option_combo( $product_id )
   {
      global $connect;

      $query = "select options from products where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $options = $data[options];

      $option = split ( "\n", $options );
      $cnt =  count($option);

      $i = 0;
      while ( $i < $cnt - 1 )
      {
         if ( !trim( $option[$i] ) ) break; 

         list ( $key, $opts ) = split ( ":" , $option[$i] );

         echo "
         <br>
         <input type=hidden name=option" . $i . "_key value=\"$key\">
         <select name=option" . $i . "_value style=width:200>";
            echo "<option value=0>$key</option>";

         $os = split(",", $opts);
         foreach ( $os as $o )
            echo "<option value='$o' alt='$o'>$o</option>";

         echo "</select>";
         $i++;
      }
?>

<?
//      return $options; 
   }

   function order_update_utf8( $arr_old_data )
   {
        global $connect, $seq;
        global $trans_who, $isexchange, $recv_address;

        // pack여부를 확인
        $query = "select pack from orders where seq='$seq'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        $pack = $data[pack] ? $data[pack] : 0;

        $arr_items = array( "order_mobile", "order_tel", "order_address", "recv_address", "recv_mobile", "recv_tel", "recv_zip", "trans_price","recv_name" );
        
        if (  $trans_who  == "선불" ||  $trans_who  == "착불"  )
            $trans_who =  $trans_who;
        else
            $trans_who = $trans_who;
        
//debug( "$query / $trans_who / address: $recv_address / " .  $trans_who );

        $query = "update orders set ";        
        $_str = "";
        for( $i=0; $i < count($arr_items) ;$i++)
        {
            $item = $arr_items[$i];
            global $$item;

            if ( $i > 0 )
                $query .= ","; 

            $str_item =  $$item ;
            $query .= $item. "=\"" . $str_item . "\"";

            if ( $str_item != $arr_old_data[$item] )
                $_str .= "이전[$item]: $arr_old_data[$item] / 갱신: $str_item <br>";
        }

        if ( $isexchange == 1)
        {
            $query .= ",order_cs=9 ";  // 맞교환
        }

        // trans_who 선택 로직은 위쪽에 있음
        $query .= " ,trans_who='$trans_who' where ";
        
        if ( $pack )
            $query .= "pack='$pack'";
        else
            $query .= "seq='$seq'";


        mysql_query ( $query, $connect );
        echo "[" . mysql_affected_rows() . "]";

        // 메모는 따로 저장
        global $memo;
        $query = "update orders set memo='" .  $memo . "' where seq='$seq'";
        mysql_query( $query, $connect );
         echo "변경 완료";

        $this->csinsert2( $seq, $_str );
   }

   /////////////////////////////////////////
   //
   // cs의 주문정보를 update
   // datea : 2005.9.16
   // 합포일 경우 배송 메모는 update하지 않아야 한다.
   // date: 2007.8.30 - jk
   //
   function order_update( $redirect_option=1 )
   {
      global $connect, $seq;
      $transaction = $this->begin("주문정보수정");

      $query = "select order_cs from orders where seq=$seq";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );
      $_order_cs = $data[order_cs];

      if ( $_REQUEST["redirect_option"] )
         $redirect_option = $_REQUEST["redirect_option"];

      $query = "update orders set ";
      $options = "";
      
      $i = 0;
      $recv_mobile;
      $_memo_string;

      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;
         if ( $key == "lastscrollerpos" ) continue;
         if ( preg_match ("/ys-ext-comp/", $key )) continue;
         // 2007.8.30 추가 - memo는 밑에서 따로 처리 할 예정
         if ( $key == "memo" ) {
                     $_memo_string =  $val;
                continue;
         }

         // starshop위한 Ajax
         if ( $key == "recv_address" or
              $key == "memo" or
              $key == "trans_who" or
              $key == "recv_name" )
         { 
             $val =  $val;
         }


         if ( preg_match("/^option/", $key) )
         {  
             if ( preg_match ("/key/", $key) )
                $options .= $val . ":";
             if ( preg_match ("/value$/", $key) )
                $options .= $val . ",";
         }
         else if ( $key == "isexchange")
         {
             if ( $_order_cs == 0 )
                 if ( $val == 1 )
                     $query .= ",order_cs=9 ";        // 맞교환
                 else 
                     $query .= ",order_cs=0 ";        // 정상
          }        
         else if ( $key == "zip1")
         {
               //$zip = $val . "-";
               $zip = $val;
         }
         else if ( $key == "recv_mobile")
         {
               $recv_mobile = $val;
               $query .= ",recv_mobile='" . addslashes( $val ) . "'"; 
         }
         else if ( preg_match("/^zip2/", $key) )
               $zip .= $val;
         else
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "redirect_option" && $key != "order_cs" && $key != "org_qty" && $key != "popup1221" && $key != "trans_fee" && $key != "top_url" && $key != "shop_price" && $key != "supply_price" && $key != "org_price" && $key != "amount" )
         {
               if ( $i != 0 )
                 $query .= ",";

               $query .= $key . "='" . addslashes( $val ) . "'"; 
               $i++;
         }
      }

      // recv_mobile2의 값을 추가
      if ( _DOMAIN_ == "nak21" or _DOMAIN_== "ezadmin" )
      {
             $numbers = split( "-" , $recv_mobile );
             $query .= ",recv_mobile2='" . $numbers[1] . "-" . $numbers[2] . "'";
      }

      //$query .= "order_cs = " . _change_req_b . ", recv_zip='$zip'";
      // starshop을 위한 버젼
      // $query .= "order_cs = " . _change_req_b;

      ////////////////////////////////////////////////////////////
      // 옵션 변경을 선택할 경우에만 옵션값을 변경한다.
      // if ( $_REQUEST["option_change"] )
      // 옵션별 발주가 아닐경우  
      global $options;
      if ( !$_SESSION[STOCK_MANAGE_USE] and $options != "" )
          $query .= ",options = '$options'";


      // 전체 seq 번호를 가져온다
      $query_seq = "select seq, pack from orders where seq='$seq'";
      $result = mysql_query ( $query_seq, $connect );
      $data = mysql_fetch_array ( $result );
      $pack = $data[pack] ? $data[pack] : $data[seq];

      if ( $data[pack] )
          $query2 = " where pack = $pack";
      else
          $query2 = " where seq = $pack";

      mysql_query ( $query . $query2, $connect );

//echo $query . $query2;
// debug( $query . $query2 );

        // 메모 update
        $query = "update orders set memo='$_memo_string' where seq='$seq'";
        mysql_query ( $query, $connect );
        
echo "변경되었습니다.";

      //$this->jsAlert("변경 되었습니다");
      $transaction = $this->end($transaction);

      if ( $redirect_option )
      {
         global $link_url, $top_url;
         $this->redirect( $link_url . "&top_url=" . $top_url );
         exit;
      }
   }


   /////////////////////////////////////////
   // cs의 주문정보를 update   
   // datea : 2005.9.16  
   // exchange_option: 1 => 맞교환
   function create_order( $order_id, &$seq, $exchange_option = 0 )
   {                     
      global $connect, $org_qty;

      $query = "insert into orders set ";
      $options = "";

      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;
         /*
         if ( preg_match("/^option/", $key) )
         {  
             if ( preg_match ("/key/", $key) )
                $options .= $val . ":";
             if ( preg_match ("/value$/", $key) )
                $options .= $val . ",";
         }
         else 
         */
         if ( $key == "order_id")
               $query .= $key . "='" . $order_id. "',"; 
         else if ( $key == "zip1")
               $zip = $val . "-";
         else if ( preg_match("/^zip2/", $key) )
               $zip .= $val;
//         else if ( $key == "org_qty" )
//             $query .= "qty='" . $org_qty . "',";
         else if ( $key == "order_cs" )
         {
             if ( $exchange_option )
                $query .= "status='" . _order_exchange . "',";

             $query .= "order_cs ='" . _change . "',";
         }
         else
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "trans_fee" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "order_cs" && $key != "org_qty" && $key != "top_url" )
               $query .= $key . "='" . $val . "',"; 
      }
      $query .= "recv_zip='$zip'";

      ////////////////////////////////////////////////////////////
      // 옵션 변경을 선택할 경우에만 옵션값을 변경한다.
      /*
      if ( $_REQUEST["option_change"] )
          $query .= ",options = '$options'";
      else
         $query .= ",options='" . $_REQUEST["options"] . "'";
      */
      ////////////////////////////////////////////////////////
      // order_cs 
      $query .= ", trans_price='" . $_REQUEST["trans_fee"] . "'";

      // collect_date / order_date = Now
      $query .= ", collect_date = Now()";

//echo $query;
//exit;
      mysql_query ( $query, $connect );

      ///////////////////////////////////
      // seq를 가져온다
      $query = "select seq from part_cancel order by seq desc limit 1";

      $result = mysql_query( $query, $connect );
      $data =mysql_fetch_array ( $result );
      $seq = $data[seq];

   }
   
   function enable_sale( $product_id )
   {
      class_E::enable_sale( $product_id );
   }



    ////////////////////////////////////
    // Use E111.htm
    function sms()
    {
        global $connect;
        global $template;

        $sender = $_REQUEST[sender];
        $receiver = $_REQUEST[receiver];
        $message = $_REQUEST[message];

        // 1. send sms
        require_once "lib/sms_lib.php";
        sms_send($receiver, $sender, $message);
        
        // 2. upate sys_domain
        $sys_connect = sys_db_connect();

		$upd_sql = "update sys_domain set   
						   paid_sms = if (sms = 0, paid_sms-1, paid_sms)
						 , sms      = if (sms > 0, sms-1, sms) 
					 where id = '"._DOMAIN_."'";
        @mysql_query($upd_sql, $sys_connect);

        echo "<script>alert('문자메시지 전송이 완료되었습니다.');</script>";
        echo "<script>self.close();</script>";
        exit;
    }

    /////////////////////////////////////////
    // 부분 취소 개수
    // date: 2005.12.7 - jk.ryu
    function get_part_cancel_count ( $seq )
    {
        return class_E::get_part_cancel_count( $seq );
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

         // increase column
         $j = 0;
         foreach ( $download_items as $key=>$value )
         {
            $worksheet->write($i, $j, $this->get_data( $data, $key, $i ) );
            $j++;
         }

         // cs list
         global $connect;
         $query = "select * from csinfo where order_seq='$data[seq]'";

         $result_cs = mysql_query ( $query, $connect );

         while ( $data_cs = mysql_fetch_array ( $result_cs ) )
         {
             $worksheet->write($i, $j, $j . "/" . $data_cs[content] );
             $j++;
         }

         // increase row
         $i++;
      }
   }
   
   function get_data( $data, $key, $i )
   {
      switch ( $key )
      {
          case "shop_name":
              return class_C::get_shop_name( $data[shop_id] );
              break;
          case "status":
              return $this->get_order_status( $data[status],2 );
              break;
          case "trans_name":// 택배사 이름
              return class_E::get_trans_name($data[trans_corp]);
              break;
          case "order_tel":
          case "order_mobile":
          case "recv_tel":
          case "recv_mobile":
              if ( preg_match("/-/", $data[$key]) )
                  return $data[$key];
              else
              {
                  // 사이에 - 를 넣는다.
                  return substr( $data[$key],0,3 ) . "-" . substr( $data[$key],3,strlen($data[$key]) );
              }
              break;
          default :
              return $data[$key];
      }
   }


// **********************************************************************************************************
//   회 수   로 직
// **********************************************************************************************************

/*
    // 회수 정보를 가져온다. 
    function get_takeback_info()
    {
        global $seq;
            echo json_encode(class_takeback::get_takeback_info_seq($seq));
    }        
*/
    // 회수 정보를 회수번호를 키워드로 가져온다.
    function get_takeback_bynum()
    {
        global $number;
            echo json_encode(class_takeback::get_takeback_info_number($number));
    }        
    
    // 회수번호를 키워드로 전체 회수 정보를 가져온다.
    function get_takeback_all()
    {
            global $connect, $number ;
        
            $query = "select * from order_takeback where number=$number";
            $result = mysql_query( $query, $connect );
            $val['query'] = $query;
        $val['list'] = array();
            if( mysql_num_rows( $result ) > 0 )
            {
                while( $data = mysql_fetch_array( $result ) )
                {
                $data_p = class_order::get_order( $data[order_seq] );
                $info = class_product::get_product_infos( $data_p[product_id] );
                   
                $val['list'][] = array(
                            number                    => $data[number],
                            seq             => $data[order_seq],
                            pack            => $data_p[pack   ],
                            product_id      => $data_p[product_id  ],
                            name            =>  $info[name],
                            options         =>  $info[option]?$info[option]:$data_p[options],
                            order_name      =>  $data_p[order_name  ],
                            recv_name       =>  $data_p[recv_name   ],
                            status          => $data[status]
                );
            }                    
                    $val['error'] = 0;
        }
        else
            $val['error'] = 1;
            
            echo json_encode( $val );
    }        
    
    // 합포 전체 회수접수한다. 송장번호 기준. jkh - 2008.10.22
    function reg_takeback_all()
    {
            global $connect, $pack, $seq, $product_id, $number, $invoice, $return, $trans_who, $refund_req, $bank_req;

        $trans_who =  $trans_who ;
        // 회수번호 최대값을 가져온다.
        if( $number == 0 )
            $num_new = class_takeback::get_new_number();
        else
            $num_new = $number;

        // 송장번호가 같은 주문정보 가져옴
        $data = class_order::get_order( $seq );
        $query_pack = "select * from orders where trans_no =$data[trans_no]";
        $result_pack = mysql_query( $query_pack, $connect );
        while( $data_pack = mysql_fetch_array( $result_pack ) )
        {
            $seq_pack = $data_pack[seq];
            $qty_pack = $data_pack[qty];

            class_takeback::set_regist( $pack, 
                                        $seq_pack, 
                                        $product_id, 
                                        $num_new, 
                                        $invoice, 
                                        $trans_who,
                                        $refund_req, 
                                        $bank_req, 
                                        $qty_pack, 
                                        $return );
                
                $seq = $seq_pack;
        }
        $this->get_takeback_info();
    }

    // 회수 접수한다. jkh - 2008.10.10
    function reg_takeback()
    {
            global $connect, $pack, $seq, $product_id, $number, $invoice, $return, $trans_who, $refund_req, $bank_req, $qty_req;

        // 회수번호 최대값을 가져온다.
        if( $number == 0 )
            $num_new = class_takeback::get_new_number();
        else
            $num_new = $number;

        $trans_who =  $trans_who ;
        class_takeback::set_regist( $pack, 
                                    $seq,
                                    $product_id, 
                                    $num_new, 
                                    $invoice, 
                                    $trans_who,
                                    $refund_req, 
                                    $bank_req, 
                                    $qty_req, 
                                    $return );

        $this->get_takeback_info();
    }        

    // 회수 취소한다. jkh - 2008.10.10
    function cancel_takeback()
    {
            global $seq;
        class_takeback::cancel_takeback( $seq );    

        $this->get_takeback_info();
    }        

    // 주문정보를 가져온다. 필요한 항목 그때그때 추가...
    function get_order_info()
    {
            global $connect,  $seq;
        $data = class_order::get_order( $seq );
        
        $val['address'] =  $data[recv_address];
        
        echo json_encode( $val );
    }        

    // 시스템에 등록된 택배사 코드 전체를 가져온다. (택배사 선택 콤보박스용)
    function get_corp_all()
    {
        global $connect, $sys_connect;
        $sys_connect = sys_db_connect();
        
        $query = "select * from sys_transinfo order by trans_corp";
        $result = mysql_query( $query, $sys_connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $val['list'][] = array(
                code => $data[id],
                name =>  $data[trans_corp]
            );
        }
        echo json_encode( $val );
    }

    // 회수 도착 확인
    function set_tb_pos()
    {
        global $connect, $pack, $seq, $product_id, $number, $corp, $transno;
        
        // 회수 접수 안된 경우, 개별 도착확인 insert
        if( $number == 0 )
        {
            $new_num = class_takeback::get_new_number();
            class_takeback::pos_takeback_insert( $pack, 
                                                 $seq, 
                                                 $product_id, 
                                                 $new_num, 
                                                 $corp, 
                                                 $transno );
        }
        // 회수 접수된 경우, 회수 번호를 기준으로 도착 확인 update
        else
        {
            class_takeback::pos_takeback_update( $number, 
                                                 $corp, 
                                                 $transno );
        }            
        $this->get_takeback_info();
    }

    // 전체 회수 도착 확인 - 회수 접수 안된 경우에만 해당
    function set_tb_pos_all()
    {
        global $pack, $seq, $number, $corp, $transno;
        
        $new_num = class_takeback::get_new_number();
        class_takeback::pos_takeback_insert_all( $pack, 
                                                 $seq, 
                                                 $product_id, 
                                                 $new_num, 
                                                 $corp, 
                                                 $transno );
        $this->get_takeback_info();
    }

    // 회수 도착 취소
    function cancel_tb_pos()
    {
        global $seq, $number;

        $val = class_takeback::get_takeback_info_seq( $seq );
        class_takeback::cancel_tb_pos( $seq, $number, $val[regist_date], $val[request_date], $val[receive_date] );
        $this->get_takeback_info();
    }

    // 박스 개봉 정보를 저장한다..
    function set_box_info()
    {
            global $seq, $number, $num_new, $trans_get, $refund_get, $qty_get, $reason_get, $prd_status;

        $trans_get =  $trans_get ;        
        class_takeback::set_box_info( $seq,
                                      $number,
                                      $num_new,
                                      $trans_get,
                                      $refund_get,
                                      $qty_get,
                                      $reason_get,
                                      $prd_status );
        $this->get_takeback_info();
    }        

    // 박스 개봉 취소
    function cancel_tb_box()
    {
        global $seq, $number;
        
        class_takeback::cancel_tb_box( $seq, $number );
        $this->get_takeback_info();
    }

    // 회수 묶음을 푼다. 
    function unbind_takeback()
    {
            global $seq;

        $num_new = class_takeback::get_new_number();
        class_takeback::change_number( $seq, $num_new );
    }        

    // 기본 택배사 이름을 가져온다.
    function get_base_trans()
    {
        global $connect, $sys_connect;
        $sys_connect = sys_db_connect();
        
        $query = "select base_trans_code from ez_config";
        $result = mysql_query($query,$connect);
        $data = mysql_fetch_array($result);
/*        
        $query = "select trans_corp from sys_transinfo where id=$data[base_trans_code]";
        $result = mysql_query( $query, $sys_connect );
        $data = mysql_fetch_array( $result );
        
        $val['name'] =  $data[trans_corp];
*/
        $val['code'] = $data[base_trans_code];
        echo json_encode( $val );
    }        
    
    function is_pack_order( $seq )
    {
        global $connect;
        
        $query = "select pack from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        if( $data[pack] > 0 )
        {
            $pack_info[is_pack] = true;
            $pack_info[pack] = $data[pack];
        }
        else
        {
            $pack_info[is_pack] = false;
            $pack_info[pack] = 0;
        }
        return $pack_info;
    }

    // 주문 복사/교환 상품을 찾는다.
    function search_popup_product()
    {
        global $connect, $name, $query_type, $query_string, $shop_id, $page, $auth_e3, $_limit;
         
        $name = trim( $name );
        $query_string = trim( $query_string );
        
        $page  = $page ? $page : 1;
        
        if($_limit > 0)
        	$limit = 100;	
        else
        	$limit = 30;
        	
        $start = ( $page - 1 ) * $limit;
        
        $condition_page = " limit $start, $limit";
        
        $query = "select product_id, name, options, supply_code, enable_sale from products where is_delete=0 and is_represent=0 ";
        if( $name )
        {
            // 옵션 교환만 가능 권한.
            if( $auth_e3 )
                $query .= " and name = '$name' ";
            else
            {
                $name = preg_replace('/\s+/', "%", $name);
                $query .= " and name like '%$name%' ";
            }
        }
        
        if( $query_string )
        {
            switch( $query_type )
            {
                case 1: $query .= " and options like '%$query_string%' "; break;
                case 2: $query .= " and product_id = '$query_string' "; break;
                case 3: $query .= " and brand like '%$query_string%' "; break;
            }
        }

        if( $_SESSION[CS_EXCEPT_SOLDOUT] )
            $query .= " and enable_sale=1 ";

        // 수량
        $result = mysql_query($query, $connect);
        $total_row = mysql_num_rows($result);

        // 검색
        $query .= " order by name $condition_page";
        $result = mysql_query($query, $connect);
        
        $products = array();
        $i=0;
        while( $data = mysql_fetch_array($result) )
        {
            $price_arr = class_product::get_price_arr($data[product_id], $shop_id);
            
            if( $data[enable_sale] == 1 )
                $img = "<img src='"._IMG_SERVER_."/images/soldout_blank.gif'>";
            else
                $img = "<img src='"._IMG_SERVER_."/images/soldout.gif'>";

            $i++;
            $products[] = array(
                product_id => $data[product_id],
                name       => htmlspecialchars($data[name]),
                options    => htmlspecialchars($data[options]),
                supply     => class_supply::get_name($data[supply_code]),
                price      => $price_arr[shop_price],
                total_rows => $total_row,
                product_id2 => $data[product_id] . "&nbsp;" . $img,
                img         => "&nbsp;" . $img
            );
        }
        echo json_encode($products);
    }
    
    // cs 이력을 가져온다. 신발주용
    function get_cs_history()
    {
        global $connect, $seq, $cs_all;

        $val['list'] = array();
        
        $seq_list = $this->get_seq_list($seq, &$pack);
        if( $_SESSION[LOGIN_LEVEL] == 9 )
            $query = "select * from csinfo where order_seq in ($seq_list) order by seq desc";
        else
            $query = "select * from csinfo where order_seq in ($seq_list) and is_delete=0 order by seq desc";
debug("cs 이력을 가져온다. 신발주용 : " . $query);
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $del_enable = 0;
            if( $_SESSION[DEL_CS] )
            {
                // 타계정 CS내역 삭제
                $arr_auth = split(",", $_SESSION[AUTH]);

                if( $_SESSION[LOGIN_NAME] == $data[writer] || $_SESSION[LOGIN_ID] == $data[writer] || ( array_search("E6", $arr_auth) !== false ) )
                    $del_enable = 1;
            }
            if($cs_all == "true")
            {
            	$val['list'][] = array(
                id_seq     => $data[seq],
                seq        => $data[order_seq],
                pack       => $data[pack],
                input_time => $data[input_date] . " " . $data[input_time],
                writer     => $data[writer],
                cs_type    => $data[cs_type],
                cs_reason  => $data[cs_reason],
                cs_result  => $data[cs_result],
                content    => $data[content]."<br>".$data[user_content],
                del_enable => $del_enable,
                is_delete  => $data[is_delete]
            	);
            }
            else if($data[user_content] >'')
            {
            	$val['list'][] = array(
                id_seq     => $data[seq],
                seq        => $data[order_seq],
                pack       => $data[pack],
                input_time => $data[input_date] . " " . $data[input_time],
                writer     => $data[writer],
                cs_type    => $data[cs_type],
                cs_reason  => $data[cs_reason],
                cs_result  => $data[cs_result],
                content    => $data[user_content],
                del_enable => $del_enable,
                is_delete  => $data[is_delete]
            	);
            }
            
            
        }            
        echo json_encode($val);
    }

    // cs 이력을 삭제한다
    function del_cs_history()
    {
        global $connect, $bck_connect, $seq;
        
        $query = "update csinfo set is_delete=1, delete_date=now() where seq=$seq";
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);

        $val['error'] = 0;
        echo json_encode($val);
    }
    
    function get_seq_list($seq, &$pack)
    {
        global $connect;
        
        $seq_list = '';

        $query = "select pack from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $pack = $data[pack];
        if( $data[pack] > 0 )
        {
            $query = "select seq from orders where pack=$data[pack]";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $seq_list .= ($seq_list?',':'') . $data[seq];
        }
        else
            $seq_list = $seq;

        return $seq_list;
    }

    function get_seq_list2($seq, $pack)
    {
        global $connect;
        
        $seq_list = '';
        if( $pack > 0 )
        {
            $query = "select seq from orders where pack=$pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $seq_list .= ($seq_list ? ',' : '') . $data[seq];
        }
        else
            $seq_list = $seq;

        return $seq_list;
    }
    
    // order_products의 order_cs를 보고 orders의 order_cs를 수정한다.
    function modify_orders_cs($seq)
    {
        global $connect;

        $cs = 0;
        $refund_price = 0;
        $extra_money = 0;
        
        $query = "select order_cs, refund_price, extra_money from order_products where order_seq=$seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            switch( $data[order_cs] )
            {
                case 0: $cs = $cs | 1;   break;  // 정상		            
                case 1: $cs = $cs | 2;   break;  // 배송전 전체 취소		    
                case 2: $cs = $cs | 4;   break;  // 배송전 부분 취소		    
                case 3: $cs = $cs | 8;   break;  // 배송후 전체 취소		    
                case 4: $cs = $cs | 16;  break;  // 배송후 부분 취소		    
                case 5: $cs = $cs | 32;  break;  // 배송전 전체 교환		    
                case 6: $cs = $cs | 64;  break;  // 배송전 부분 교환		    
                case 7: $cs = $cs | 128; break;  // 배송후 전체 교환		    
                case 8: $cs = $cs | 256; break;  // 배송후 부분 교환		    
            }                    
            $refund_price += $data[refund_price];
            $extra_money += $data[extra_money];
        }
        
        // 0. 정상
        if( $cs == 1 )
            $order_cs = 0;
        
        // 1. 배송전 전체취소
        if( $cs&(2+4) && !($cs&(1+8+16+32+64+128+256)))
            $order_cs = 1;
            
        // 2. 배송전 부분취소
        if( $cs&(2+4) && $cs&(1+32+64) && !($cs&(8+16+128+256)) )
            $order_cs = 2;
            
        // 3. 배송후 전체취소
        if( $cs&(8+16) && !($cs&(1+32+64+128+256)) )
            $order_cs = 3;
            
        // 4. 배송후 부분취소
        if( $cs&(8+16) && $cs&(1+32+64+128+256) )
            $order_cs = 4;
            
        // 5. 배송전 전체교환
        if( $cs&(32+64) && !($cs&(1+2+4+8+16+128+256)) )
            $order_cs = 5;
            
        // 6. 배송전 부분교환
        if( $cs&(32+64) && $cs&1 && !($cs&(2+4+8+16+128+256)) )
            $order_cs = 6;
            
        // 7. 배송후 전체교환
        if( $cs&(128+256) && !($cs&(1+8+16+32+64)) )
            $order_cs = 7;
            
        // 8. 배송후 부분교환
        if( $cs&(128+256) && $cs&(1+32+64) && !($cs&(8+16)) )
            $order_cs = 8;
        
        // orders 변경
        $query = "update orders 
                     set order_cs     = $order_cs,
                         refund_price = $refund_price,
                         extra_money  = $extra_money
                   where seq = $seq";
        $result1 = mysql_query($query, $connect);
        
        // order_products 변경
        switch( $order_cs )
        {
            case 1:
                $query = "update order_products set order_cs=1 where order_seq=$seq and order_cs=2";
                break;
            case 2:
                $query = "update order_products set order_cs=2 where order_seq=$seq and order_cs=1";
                break;
            case 3:
                $query = "update order_products set order_cs=3 where order_seq=$seq and order_cs=4";
                break;
            case 4:
                $query = "update order_products set order_cs=4 where order_seq=$seq and order_cs=3";
                break;
            case 5:
                $query = "update order_products set order_cs=5 where order_seq=$seq and order_cs=6";
                break;
            case 6:
                $query = "update order_products set order_cs=6 where order_seq=$seq and order_cs=5";
                break;
            case 7:
                $query = "update order_products set order_cs=7 where order_seq=$seq and order_cs=8";
                break;
            case 8:
                $query = "update order_products set order_cs=8 where order_seq=$seq and order_cs=7";
                break;
        }
        $result2 = mysql_query($query, $connect);
        
        return $result1 && $result2;
    }

    // 상품취소 또는 상품 정상복귀 시에 order_products의 order_cs를 보고 orders의 hold를 수정한다.
    function modify_orders_hold($seq_list)
    {
        global $connect;

        $cancel_flag = false;
        $normal_flag = false;
        
        $query = "select order_cs from order_products where order_seq in ($seq_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[order_cs] == 1 || $data[order_cs] == 2 )  
                $cancel_flag = true;
            else
                $normal_flag = true;
        }
        
        // 전체정상
        if( !$cancel_flag && $normal_flag )
            $this->set_cs_hold(6, $seq_list, true);

        // 부분취소
        if( $cancel_flag && $normal_flag )
            $this->set_cs_hold(5, $seq_list);
            
        // 전체취소
        if( $cancel_flag && !$normal_flag )
            $this->set_cs_hold(4, $seq_list);

        return;
    }
    
    // 상품코드와 판매처로 상품가격 가져오기
    function get_product_price()
    {
        global $connect, $product_id, $shop_id;
        
        $price_arr = class_product::get_price_arr($product_id, $shop_id);
        
        $val['error'] = 0;
        $val['price'] = $price_arr[shop_price];
        echo json_encode( $val );
    }
    
    ///////////////////////////////////////
    // 합포 여부
    function get_pack($seq)
    {
        global $connect;
        
        $query = "select pack from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        return $data["pack"];
    }
    
    ///////////////////////////////////////
    // 회수 접수
    // 
    // 0 - 수정
    // 1 - 접수
    // 2 - 도착
    // 3 - 개봉
    //
    function takeback_regist()
    {
        global $connect, $seq,$pack,$prdSeq,$content,$sts,$tb_no;
        
        $val = array();
        switch( $sts )
        {
            case 0:
                $transaction = $this->begin("회수수정");
                break;
            case 1:
                $transaction = $this->begin("회수접수");
                break;
            case 2:
                $transaction = $this->begin("회수도착");
                break;
            case 3:
                $transaction = $this->begin("회수개봉");
                break;
        }
        
        $prd_seq_list = "";

        //**********************
        // 접수
        //**********************
        if( $sts == 1 )
        {
            // 회수 수량 - 전체
            if( !$_REQUEST[tb_qty] )
            {
                $seq_list = "";
    
                // 합포
                if( $pack > 0 )
                {
                    $query = "select seq from orders where pack=$pack and status=8";
                    $result = mysql_query($query, $connect);
                    while( $data = mysql_fetch_assoc($result) )
                        $seq_list .= ($seq_list ? "," : "" ) . $data[seq];
                }
                else
                    $seq_list = $seq;
                    
                $query = "select seq from order_products where order_seq in ($seq_list) and order_cs in (0,5,6) and tb_status=0";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_assoc($result) )
                    $prd_seq_list .= ($prd_seq_list ? "," : "" ) . $data[seq];
            }
            // 회수 수량 - 개별
            else
            {
                // 부분 수량일 경우, order_products를 쪼갠다.
                if( $_REQUEST[tb_qty] < $_REQUEST[tb_qty_all] )
                {
                    // 회수 접수되지 않고 남는 수량
                    $tb_qty_rest = $_REQUEST[tb_qty_all] - $_REQUEST[tb_qty];
                    
                    // 기존 order_products를 회수되는 수량으로 변경
                    $query = "update order_products set qty = $_REQUEST[tb_qty] where seq = $prdSeq";
                    mysql_query($query, $connect);
                    
                    // order_products에 qty를 회수되지 않는 수량으로 하여 복사한다.
                    $query = "insert order_products 
                                     (
                                         order_seq,product_id,qty,order_cs,cancel_date,change_date,refund_price,
                                         shop_id,shop_product_id,shop_options,marked,status,match_date,supply_id,
                                         shop_price,extra_money,no_save,location,is_gift,tb_no,tb_trans,tb_status,
                                         tb_type,tb_reason,tb_return,tb_return_yet
                                     ) select 
                                         order_seq,product_id,$tb_qty_rest,order_cs,cancel_date,change_date,refund_price,
                                         shop_id,shop_product_id,shop_options,marked,status,match_date,supply_id,
                                         shop_price,extra_money,no_save,location,is_gift,tb_no,tb_trans,tb_status,
                                         tb_type,tb_reason,tb_return,tb_return_yet 
                                       from order_products 
                                       where seq=575668";
                    mysql_query($query, $connect);
                }
                $prd_seq_list = $prdSeq;
            }
            $tb_no = 0;
        }
        
        // 접수처리한다.
        $this->update_takeback($_REQUEST, $sts, $tb_no, $prd_seq_list);

        if( !$ret )
        {
            $val['error'] = 0;
            switch( $sts )
            {
                case 0:
                    $this->end("회수 수정 성공");
                    $this->csinsert3(0, 25, $content);
                    break;
                case 1:
                    $this->end("회수 접수 성공");
                    $this->csinsert3(0, 26, $content);
                    break;
                case 2:
                    $this->end("회수 도착 성공");
                    $this->csinsert3(0, 27, $content);
                    break;
                case 3:
                    $this->end("회수 개봉 성공");
                    $this->csinsert3(0, 28, $content);
                    break;
            }
        }
        else
        {
            $val['error'] = 1;
            switch( $sts )
            {
                case 0:
                    $this->end("회수 수정 실패");
                    break;
                case 1:
                    $this->end("회수 접수 실패");
                    break;
                case 2:
                    $this->end("회수 도착 실패");
                    break;
                case 3:
                    $this->end("회수 개봉 실패");
                    break;
            }
        }
        echo json_encode( $val );
    }
    
    ///////////////////////////////////////
    // 회수 정보 update
    function update_takeback( $par, $sts, $tb_no, $list='' )
    {
        global $connect;
        
        // 회수 번호가 있는 업데이트
        if( $tb_no )
        {
            // 상태 값이 있으면 상태 변경
            if( $sts )
                $query_status = "tb_status = $sts,";
            // 상태 값이 없으면, 현 상태에서 수정
            else
                $query_status = "";
                
            $query = "update order_products 
                         set tb_trans      = " . intval($par[tb_trans     ]) . ",
                             $query_status
                             tb_type       = " . intval($par[tb_type      ]) . ",
                             tb_reason     = " . intval($par[tb_reason    ]) . ",
                             tb_trans_no   = " . "'" .  $par[tb_trans_no  ]  . "',
                             tb_return     = " . intval($par[tb_return    ]) . ",
                             tb_return_yet = " . intval($par[tb_return_yet]) . "
                       where tb_no = $tb_no";
        }
        // 회수 번호가 없는 신규 업데이트 
        else
        {
            // 가장 큰 회수번호 구하기
            $query = "select tb_no from order_products order by tb_no desc limit 1";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $new_tb_no = $data[tb_no] + 1;

            $query = "update order_products 
                         set tb_no         = $new_tb_no,
                             tb_trans      = " . intval($par[tb_trans     ]) . ",
                             tb_status     = $sts,
                             tb_type       = " . intval($par[tb_type      ]) . ",
                             tb_reason     = " . intval($par[tb_reason    ]) . ",
                             tb_trans_no   = " . "'" .  $par[tb_trans_no  ]  . "',
                             tb_return     = " . intval($par[tb_return    ]) . ",
                             tb_return_yet = " . intval($par[tb_return_yet]) . "
                       where seq in ($list)";
        }
        return mysql_query($query, $connect);
    }

    ///////////////////////////////////////
    // 회수 정보 가져오기
    function get_takeback_info()
    {
        global $connect, $prd_seq;

        $val = array();
        
        $query = "select * from order_products where seq=$prd_seq";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) == 0 )
        {
            $val['error'] = 1;
            echo json_encode( $val );
            return;
        }
        
        $data = mysql_fetch_assoc($result);
        $val["qty"          ] = $data[qty          ];
        $val["tb_no"        ] = $data[tb_no        ];
        $val["tb_trans"     ] = $data[tb_trans     ];
        $val["tb_status"    ] = $data[tb_status    ];
        $val["tb_type"      ] = $data[tb_type      ];
        $val["tb_reason"    ] = $data[tb_reason    ];
        $val["tb_return"    ] = $data[tb_return    ];
        $val["tb_return_yet"] = $data[tb_return_yet];
        $val["tb_trans_no"  ] = $data[tb_trans_no  ];

        $val['error'] = 0;
        echo json_encode( $val );
    }
    
    // ################################################
    //
    //     유틸 함수
    //
    // ################################################

    // 판매처 목록
    function get_shop_list()
    {
        global $template, $connect;
        
        $val = class_shop::get_shop_list();
        
        echo json_encode( $val );
    }

    // CS 재고 표시 포멧
    function get_stock_format($product_id)
    {
        global $template, $connect;

        $f = $_SESSION[CS_STOCK_FORMAT];
        
        // 포멧이 없으면
        if( $f == '' )  return '';
        
        // 현재재고
        if( strpos( $f, "A" ) !== false )
            $a = class_stock::get_current_stock($product_id);
            
        // 송장상태
        if( strpos( $f, "B" ) !== false )
            $b = class_stock::get_ready_stock($product_id);

        // 접수상태
        if( strpos( $f, "C" ) !== false )
            $c = class_stock::get_ready_stock2($product_id);
        
        $a1 = array("A", "B", "C");
        $a2 = array( ($a>0 ? $a : $a), ($b>0 ? $b : 0), ($c>0 ? $c : 0) );
        $f2 = str_replace( $a1, $a2, $f );

        $ptrn = "/\[([^\]]+)\]/";
        $stock_format = preg_replace_callback( $ptrn, 'class_E900::change_stock_format', $f2 );
        
        return $stock_format;
    }
    
    // CS 재고 표시 포멧 변경함수 ( 클래스 내에서는 static으로 정의되어야 함 )
    static function change_stock_format($input)
    {
        eval( "\$re = " . $input[1] . ";" );
        return $re;
    }
    
    // 최근배송정보
    function get_recent_address()
    {
        global $connect;
        
        $query = "select * from new_order_recent order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val = array();
        $val['recv_name'] = $data[recv_name];
        $val['recv_tel'] = $data[recv_tel];
        $val['recv_mobile'] = $data[recv_mobile];
        $val['recv_zip1'] = $data[recv_zip1];
        $val['recv_zip2'] = $data[recv_zip2];
        $val['recv_address1'] = $data[recv_address1];
        $val['recv_address2'] = $data[recv_address2];
        
        echo json_encode( $val );
    }
    
    // 반품정보 되돌리기. (정상복귀 실행시)
    function cancel_return($prd_seq, $each)
    {
        global $connect;
        
        $query = "select * from return_money where order_products_seq = $prd_seq and is_delete=0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            // 예정정보 있으면, 도착 취소
            if( $data[is_expect] )
            {
                $query_return = "update return_money 
                                    set return_type        = 0,
                                        cancel_type        = '',
                                        change_type        = '',
                                        return_site        = 0,
                                        return_envelop     = 0,
                                        return_account     = 0,
                                        return_notget      = 0,
                                        return_trans_who   = 0,
                                        return_trans_corp  = 0,
                                        return_trans_no    = '',
                                        restockin_auto     = 0,
                                        is_return          = 0,
                                        return_date        = 0,
                                        return_worker      = '',
                                        is_complete        = 0,
                                        complete_date      = 0,
                                        complete_worker    = ''
                                  where seq = $data[seq]";
                mysql_query($query_return, $connect);
                
                // 로그 남기기
                if( $each )
                    $log_str = '개별정상복귀실행 도착취소';
                else
                    $log_str = '전체정상복귀실행 도착취소';
                    
                $query_return_log = "insert return_money_log
                                        set seq                = '$data[seq]',
                                            log_type           = 'cancel return',
                                            log_contents       = '$log_str',
                                            log_date           = now(),
                                            log_worker         = '$_SESSION[LOGIN_NAME]'";
                mysql_query($query_return_log, $connect);
            }
            // 예정정보 없으면, 삭제 처리
            else
            {
                $query_return = "update return_money 
                                    set is_delete          = 1,
                                        delete_date        = now(),
                                        delete_worker      = '$_SESSION[LOGIN_NAME]'
                                  where seq = $data[seq]";
                mysql_query($query_return, $connect);

                // 로그 남기기
                if( $each )
                    $log_str = '개별정상복귀실행 반품정보 삭제';
                else
                    $log_str = '전체정상복귀실행 반품정보 삭제';
                    
                $query_return_log = "insert return_money_log
                                        set seq                = '$data[seq]',
                                            log_type           = 'delete',
                                            log_contents       = '$log_str',
                                            log_date           = now(),
                                            log_worker         = '$_SESSION[LOGIN_NAME]'";
                mysql_query($query_return_log, $connect);
            }
        }
    }

    function cs_complete_go()
    {
        global $connect, $bck_connect, $seq;
        
        $log = "\r\n[" . date("Y-m-d H:i:s") . "] $_SESSION[LOGIN_NAME] - 완료처리";
        $query = "update csinfo 
                     set cs_result = 1,
                         complete_date = if(complete_date>0,complete_date,now()),
                         content = concat(content, '$log')
                   where seq=$seq";
		debug($query);
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);
    }

    function cs_complete_back()
    {
        global $connect, $bck_connect, $seq;
        
        $log = "\r\n[" . date("Y-m-d H:i:s") . "] $_SESSION[LOGIN_NAME] - 완료처리취소";
        $query = "update csinfo 
                     set cs_result = 0,
                         complete_date = 0,
                         content = concat(content, '$log')
                   where seq=$seq";
        mysql_query($query, $connect);
        mysql_query($query, $bck_connect);
    }

    function E910()
    {
        $master_code = substr( $template, 0,1);
        include "template/E/E910.htm";
    }
    
    function E920()
    {
        $master_code = substr( $template, 0,1);
        include "template/E/E920.htm";
    }
    
    // 옵션재고 가져오기
    function get_option_stock()
    {
        global $connect, $product_id;
        
        $val = array();
        
        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $org_id = $data[org_id];
        
        $query = "select product_id, name, options, enable_sale from products where org_id='$org_id' and is_delete=0 order by options";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            $val["name"] = $data[name];
            
            if( $data[enable_sale] == 1 )
                $img = "&nbsp;<img src='"._IMG_SERVER_."/images/soldout_blank.gif'>";
            else
                $img = "&nbsp;<img src='"._IMG_SERVER_."/images/soldout.gif'>";
            
            $val["products"][] = array(
                "product_id"   => $data[product_id] . $img,
                "options"      => $data[options],
                "stock_format" => $this->get_stock_format($data[product_id])
            );
        }
        echo json_encode( $val );
    }
    
    // 취소/교환 금액 가져오기
    function get_extra_refund_price()
    {
        global $connect, $seq;
        
        $val = array();
        
        $query = "select * from order_products where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val["extra_money"] = $data[extra_money];
        $val["refund_price"] = $data[refund_price];
                
        echo json_encode( $val );
    }
    
    // 취소/교환 금액 설정하기
    function set_extra_refund_price()
    {
        global $connect, $seq, $extra_money, $refund_price;
        
        $val = array();
        
        $query = "select * from order_products where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $order_seq=$data[order_seq];
        $product_id = $data[product_id];
        
        $old_extra_money = $data[extra_money];
        $old_refund_price = $data[refund_price];
        
        $sub_extra_money = $extra_money - $data[extra_money];
        $sub_refund_price = $refund_price - $data[refund_price];
        
        // order_products
        $query = "update order_products 
                     set extra_money = '$extra_money',
                         refund_price = '$refund_price'
                   where seq = $seq";
        mysql_query($query, $connect);
        
        // orders
        $query = "update orders 
                     set extra_money = extra_money + '" . $sub_extra_money . "',
                         refund_price = refund_price + '" . $sub_refund_price . "'
                   where seq = $data[order_seq]";
        mysql_query($query, $connect);
        
        $seq = $order_seq;
        $content = "[상품코드 : $product_id] 추가금액 : ".number_format($extra_money)." 원, 취소금액 : ".number_format($refund_price)." 원";
        $this->csinsert3(0, 0, $content, '');

        $val["error"] = 0;
        echo json_encode( $val );
    }
    
    // 검색상품 재고 가져오기
    function get_stock_info()
    {
        global $connect, $id_list;
        
        $val = array();
        foreach( explode(",", $id_list) as $id_str )
        {
            $id_arr = explode(" ", $id_str);
            $id = $id_arr[1];
            
            $val["products"][] = array(
                "product_id"   => $id,
                "stock_format" => $this->get_stock_format($id)
            );
        }
        echo json_encode( $val );
    }
    
    // 복구된 주문을 cs 작업한 경우, 25번에 복사
    function save_to_bck($seq_list)
    {
        global $connect, $bck_connect;
        
        // orders
        $query = "select * from orders where seq in ($seq_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_exist = "select * from orders where seq=$data[seq]";
            $result_exist = mysql_query($query_exist, $bck_connect);

            // 있는 경우 update
            if( mysql_num_rows($result_exist) )
            {
                $query_bck = "update orders set ";
                foreach( $data as $key => $val )
                {
                    if( $key == "seq" ) continue;
                    $query_bck .= " $key = '" . addslashes($val) . "',";
                }
                $query_bck = substr($query_bck,0,-1) . " where seq=$data[seq]";
                mysql_query($query_bck, $bck_connect);
            }
            // 없는 경우 insert
            else
            {
                $query_bck = "insert orders set ";
                foreach( $data as $key => $val )
                {
                    $query_bck .= " $key = '" . addslashes($val) . "',";
                }
                $query_bck = substr($query_bck,0,-1);
                mysql_query($query_bck, $bck_connect);
            }
        }

        // order_products
        $query = "select * from order_products where order_seq in ($seq_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_exist = "select * from order_products where seq=$data[seq]";
            $result_exist = mysql_query($query_exist, $bck_connect);

            // 있는 경우 update
            if( mysql_num_rows($result_exist) )
            {
                $query_bck = "update order_products set ";
                foreach( $data as $key => $val )
                {
                    if( $key == "seq" ) continue;
                    $query_bck .= " $key = '" . addslashes($val) . "',";
                }
                $query_bck = substr($query_bck,0,-1) . " where seq=$data[seq]";
                mysql_query($query_bck, $bck_connect);
            }
            // 없는 경우 insert
            else
            {
                $query_bck = "insert order_products set ";
                foreach( $data as $key => $val )
                {
                    $query_bck .= " $key = '" . addslashes($val) . "',";
                }
                $query_bck = substr($query_bck,0,-1);
                mysql_query($query_bck, $bck_connect);
            }
        }
    }
    
    // cti 팝업 정보
    function cti_get_history()
    {
        global $connect, $callid;

        $val = array();
        $val["tel"] = "";
        $val["name"] = "";
        $val["list"] = array();
        
        $query = "select * from cti_call_history where callid='$callid'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $tel = $data[tel];
        
        if( $tel )
        {
            $val["tel"] = $tel;

            $first = true;
            $query = "select * from cti_call_history where tel='$tel' order by seq desc limit 1,10";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $val["list"][] = array(
                    "name"      => $data[name],
                    "crdate"    => $data[crdate],
                    "last_call" => $data[last_call],
                    "last_time" => $data[last_time],
                    "memo"      => $data[memo]
                );
    
                if( $first )
                {
                    $first = false;
                    $val["name"] = $data[name];
                }
            }
        }
        echo json_encode( $val );
    }

    // cti 메모 저장
    function cti_save_memo()
    {
        global $connect, $tel, $memo;

        $query = "select * from cti_tel_memo where tel='$tel'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
            $query = "update cti_tel_memo set memo='$memo' where tel='$tel'";
        else
            $query = "insert cti_tel_memo set tel='$tel', memo='$memo'";
        mysql_query($query, $connect);
    }

    function change_cs_new_drop( $pack_drop )
    {
        // order_cs 수정 - drop
        foreach( $pack_drop as $pack_key => $pack_val )
        {
            $is_normal = false;
            $is_cancel_before = false;
            $is_cancel_after  = false;
            $is_change_before = false;
            $is_change_after  = false;
            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                if( $seq_val[order_cs] == 0 )
                    $is_normal = true;
                else if( $seq_val[order_cs] == 1 || $seq_val[order_cs] == 2 )
                    $is_cancel_before = true;
                else if( $seq_val[order_cs] == 3 || $seq_val[order_cs] == 4 )
                    $is_cancel_after = true;
                else if( $seq_val[order_cs] == 5 || $seq_val[order_cs] == 6 )
                    $is_change_before = true;
                else
                    $is_change_after = true;
            }

            // 전체취소 - 배송후
            if( !$is_normal && !$is_change_after && !$is_change_before && $is_cancel_after )
            {
                $pack_drop[$pack_key][order_cs] = 3;
                $cs_part = false;
            }
            // 전체취소 - 배송전
            else if( !$is_normal && !$is_change_after && !$is_change_before && $is_cancel_before )
            {
                $pack_drop[$pack_key][order_cs] = 1;
                $cs_part = false;
            }
            // 부분취소 - 배송후
            else if( $is_cancel_after )
            {
                $pack_drop[$pack_key][order_cs] = 4;
                $cs_part = true;
            }
            // 부분취소 - 배송전
            else if( $is_cancel_before )
            {
                $pack_drop[$pack_key][order_cs] = 2;
                $cs_part = true;
            }

            // 전체교환 - 배송후
            else if( !$is_normal && $is_change_after )
            {
                $pack_drop[$pack_key][order_cs] = 7;
                $cs_part = false;
            }
            // 전체교환 - 배송전
            else if( !$is_normal && $is_change_before )
            {
                $pack_drop[$pack_key][order_cs] = 5;
                $cs_part = false;
            }
            // 부분교환 - 배송후
            else if( $is_change_after )
            {
                $pack_drop[$pack_key][order_cs] = 8;
                $cs_part = true;
            }
            // 부분교환 - 배송전
            else if( $is_change_before )
            {
                $pack_drop[$pack_key][order_cs] = 6;
                $cs_part = true;
            }
            // 정상
            else
            {
                $pack_drop[$pack_key][order_cs] = 0;
                $cs_part = true;
            }

            foreach( $pack_val[order_products] as $seq_key => $seq_val )
            {
                if( $seq_val[order_cs] == 1 || $seq_val[order_cs] == 2 )
                {
                    if( $cs_part )
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 2;
                    else
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 1;
                }
                else if( $seq_val[order_cs] == 3 || $seq_val[order_cs] == 4 )
                {
                    if( $cs_part )
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 4;
                    else
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 3;
                }
                else if( $seq_val[order_cs] == 5 || $seq_val[order_cs] == 6 )
                {
                    if( $cs_part )
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 6;
                    else
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 5;
                }
                else if( $seq_val[order_cs] == 7 || $seq_val[order_cs] == 8 )
                {
                    if( $cs_part )
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 8;
                    else
                        $pack_drop[$pack_key][order_products][$seq_key][order_cs] = 7;
                }
            }
        }
        return $pack_drop;
    }

    function send_return_sms($seq, $data_orders)
    {
        // 반품확인시 자동 sms
        if( $_SESSION[SEND_RETURN_SMS_AUTO] )
        {
        	global $sys_connect;
        	$sql = "select * from sys_domain where id = '" . _DOMAIN_ . "'";
        	$sys_list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));
        	$sender = $sys_list[corp_tel];

            $hp_head = array("010","011","016","017","018","019","050");
            $mobile_head = substr($data_orders[recv_mobile], 0, 3);
            $tel_head = substr($data_orders[recv_tel], 0, 3);
            if( array_search( $mobile_head, $hp_head ) !== false )
                $receiver = $data_orders[recv_mobile];
            else if( array_search( $tel_head, $hp_head ) !== false )
                $receiver = $data_orders[recv_tel];
            else
                $receiver = "";
            
            if( $receiver )
            {
                class_sms::send_ums($_SESSION[SEND_RETURN_SMS_MSG], $sender, $receiver, $seq);
                $cs_msg = "<반품확인 sms 자동전송($receiver)>" . $_SESSION[SEND_RETURN_SMS_MSG];
            }
            else
                $cs_msg = "<반품확인 sms 자동전송 실패>전화번호 오류";


            $this->csinsert3(0, 31, $cs_msg, "");

        }
    }
}


?>
