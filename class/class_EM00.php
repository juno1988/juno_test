<?
//
// 우체국의 경우 회수 송장번호를 우리가 발번한다.
// insert_takeback.php에 있는 듯 - 2014.7.8

require_once "class_top.php";
require_once "class_db.php";
require_once "class_shop.php";
require_once "class_E900.php";
require_once "class_transcorp.php";

class class_EM00 extends class_top
{ 
    function EM00()
    {
        global $template;
        global $connect, $seq, $prdSeq;
        
        $query = "select base_trans_code,takeback_use_default_trans_corp from ez_config";
		$result = mysql_query( $query, $connect );
		$data   = mysql_fetch_assoc( $result );
		
        if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 day'));
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //
    // takeback_receiverinfo에 값 추가
    // 2011.4.29 - jk
    function add_receiverinfo()
    {
        global $connect;
        global $receiver_name, $receiver_tel1, $receiver_tel2, $receiver_mobile1, $receiver_mobile2, $receiver_zip1, $receiver_zip2, $receiver_address1, $receiver_address2, $receiver_address3;
        
        $query = "insert into takeback_receiverinfo
                     set name = '$receiver_name'
                        ,tel  = '$receiver_tel1 $receiver_tel2'
                        ,tel1 = '$receiver_tel1'
                        ,tel2 = '$receiver_tel2'
                        ,mobile = '$receiver_mobile1 $receiver_mobile2'
                        ,mobile1  = '$receiver_mobile1'
                        ,mobile2  = '$receiver_mobile2'
                        ,zip1     = '$receiver_zip1'
                        ,zip2     = '$receiver_zip2'
                        ,address1 = '$receiver_address1'
                        ,address2 = '$receiver_address2'
                        ,address3 = '$receiver_address3'
                        ,reg_date = now()";

debug("add_receiverinfo: $query");    

        mysql_query( $query, $connect );
        
        // list 갱신..
        // get_receiverlist();
        $query = "select LAST_INSERT_ID() seq from takeback_receiverinfo";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );
    }

    // 
    // 수정
    //
    function mod_receiverinfo()
    {
        global $connect, $seq;
        global $receiver_name, $receiver_tel1, $receiver_tel2, $receiver_mobile1, $receiver_mobile2, $receiver_zip1, $receiver_zip2, $receiver_address1, $receiver_address2, $receiver_address3;
       
		if ( $receiver_name )
		{ 
            $query = "update takeback_receiverinfo
                         set name = '$receiver_name'
                            ,tel  = '$receiver_tel1 $receiver_tel2'
                            ,tel1 = '$receiver_tel1'
                            ,tel2 = '$receiver_tel2'
                            ,mobile = '$receiver_mobile1 $receiver_mobile2'
                            ,mobile1  = '$receiver_mobile1'
                            ,mobile2  = '$receiver_mobile2'
                            ,zip1     = '$receiver_zip1'
                            ,zip2     = '$receiver_zip2'
                            ,address1 = '$receiver_address1'
                            ,address2 = '$receiver_address2'                        
                            ,address3 = '$receiver_address3'
                        where seq=$seq";
                        
            mysql_query( $query, $connect );
		    debug("mod_receiverinfo: $query");    
       	}
 
        // echo $query;
        
        $data = array( seq => $seq );        
        echo json_encode( $data );   
    }

	function set_default()
    {
        global $connect, $seq;

        if ( $seq )
        {
            $query = "update takeback_receiverinfo set last_send=0 where last_send=1";
            mysql_query( $query, $connect );

            $query = "update takeback_receiverinfo set last_send=1 where seq=$seq";
			debug( $query );
            mysql_query( $query, $connect );
        }
    }        

    //
    // 삭제
    //
    function del_reciverinfo()
    {
        global $connect, $seq;
        
        $query = "delete from takeback_receiverinfo where seq=$seq";   

debug("del_receiverinfo: $query");    
        mysql_query( $query, $connect );
        
        echo $query;
    }

    // 
    // takeback_receiverinfo에서 자료 가져옴
    // 2011.4.29 - jk
    function get_receiverlist()
    {
        global $connect;
        
        $arr_result = array();
        $query = "select * from takeback_receiverinfo order by last_send, name";
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }

    // 회수 상세정보
    function get_detail_takeback()
    {
        global $connect, $seq;   
        $query = "select * from takeback_order where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $trans_name = class_transcorp::get_corp_name( $data[trans_corp] );
        $data[trans_no_link] = class_top::print_delivery($trans_name,$data[trans_no],0,"cs_trans_no");
        
        echo json_encode( $data );
    }

    function get_detail()
    {
        global $connect, $seq;   
        
        $query = "select * from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // tel1
        $arr = $this->arrange_tel( $data[recv_tel] );
        $data[recv_tel1] = $arr[0];
        $data[recv_tel2] = $arr[1];
        
        $arr = $this->arrange_tel( $data[recv_mobile] );
        $data[mobile1] = $arr[0];
        $data[mobile2] = $arr[1];
        
        $receiver_zip = preg_replace("/[^0-9]/","",$data[recv_zip] );
        $data[zip1]    = substr( $receiver_zip,0,3);
        $data[zip2]    = substr( $receiver_zip,3,3);
        
        // 택배사 zipcode 테이블에서 정보를 가져온다.
        $arr_address = $this->get_dt_address( $receiver_zip );
        
        // 판매처 
        if ( _DOMAIN_ == "soramam" || _DOMAIN_ == "pimz")
        {
            $shop_name = class_shop::get_shop_name( $data['shop_id'] );
            $data['recv_name'] = $data['recv_name'] . "(" . $shop_name . ")";
        }
        
        
        
        //print_r ( $arr_address );
        
        // 1번째 값이 true일 경우 조회 됨..
        if ( $arr_address[0] == 1)
        {
            /*
            $data["address1"] = $arr_address[1] . " " . $arr_address[2];
            $data["address2"] = $arr_address[3];
            //$data["address1"] = "가나다";
            // address2의 앞 2개의 키워드만 찾는다.     
            // 공백으로 정해진 주소를 나눈다.
            $_arr = array_merge( split(" ", $arr_address[1]),split(" ",$arr_address[2]),split(" ",$arr_address[3]));
            
            //print_r ( $_arr );
            
            $pos = 0;
            $offset = 0;
            foreach( $_arr as $key )
            {
                //echo "v->$val\n"; 
                $_key = $this->arrange_address( $key );   
                //Echo $_key . "\n";
                
                $pos = strpos( $data[recv_address], $_key, $offset);
                
                //echo "pos: $pos\n";
                
                // 못 찾는 경우 공백을 찾는다.
                if ( !$pos )
                    $pos = strpos( $data[recv_address],  " ", $offset) + 1;
                
                if ( $pos )
                    $offset = $pos;
                   
                //echo "p: $pos / off: $offset\n";
            }
            //echo "$data[recv_address]\n";
           
            $pos = strpos( $data[recv_address], " " , $offset); 
            //$data[address3] .= mb_substr( $data[recv_address], $pos ) . "/" . $data[recv_address];
            //$data[address3] .= mb_substr( $data[recv_address], $pos,"utf-8" );
            //mb_internal_encoding("utf-8");
            //$data[address3] .= mb_substr( $data[recv_address], $pos );
            $data[address3] .= substr( $data[recv_address], $pos );
            //echo "address3: " . $data[address3];
            */
        }
        else
        {
            // 조회 안되는 경우
            $data[zip1] = "";
            $data[zip2] = "";
            $data[address1] = "택배사 우편번호 오류, 우편번호 검색해 주십시요";
            $data[address3] = $data[recv_address];
        }
        
        
        // 취소된 상품 관련된 정보
        // $arr_products = $this->get_cancel_products( $
        //print_r( $data );
        echo json_encode( $data );
    }
    
    // 
    // 반품 수정
    // 실 등록 전에는 수정이 가능함
    function modify_takeback()
    {
        global $connect, $seq;
        global $sender_name,$sender_tel1,$sender_tel2,$sender_mobile1,$sender_mobile2,$sender_zip1,$sender_zip2;
        global $sender_address1,$sender_address2,$sender_address3,$receiver_name,$receiver_tel1,$receiver_tel2,$receiver_mobile1;
        global $receiver_mobile2,$receiver_zip1,$receiver_zip2,$receiver_address1,$receiver_address2,$receiver_address3;
        global $trans_who,$takeback_seq,$ret_type,$shop,$qty,$memo,$product_price,$trans_price,$receiver_seq,$takeback_seq;
       
		// modify전에 상태가 1이 아니면 modify되지 않음.
	    $query = "select status from takeback_order where seq=$takeback_seq";	
		$result = mysql_query( $query, $connect );
		$data   = mysql_fetch_assoc( $result );

		if ( $data[status] == 1 )
        {
 
            // 박스 수량이 없으면 기본 1
            $qty = $qty ? $qty : 1;
            $trans_price = $trans_price ? $trans_price : 0;
            $product_price = $product_price ? $product_price : 0;
    
            $query = "update takeback_order 
                         set order_seq       = $seq
                            ,sender_name     = '$sender_name'
                            ,sender_tel      = '$sender_tel1 $sender_tel2'
                            ,sender_tel1     = '$sender_tel1'
                            ,sender_tel2     = '$sender_tel2'
                            ,sender_mobile   = '$sender_mobile1 $sender_mobile2'
                            ,sender_mobile1  = '$sender_mobile1'
                            ,sender_mobile2  = '$sender_mobile2'
                            ,sender_zip1     = '$sender_zip1'
                            ,sender_zip2     = '$sender_zip2'
                            ,sender_address1 = '$sender_address1'
                            ,sender_address2 = '$sender_address2'
                            ,sender_address3 = '$sender_address3'
                            ,recv_name       = '$receiver_name'
                            ,recv_tel        = '$receiver_tel1 $receiver_tel2'
                            ,recv_tel1       = '$receiver_tel1'
                            ,recv_tel2       = '$receiver_tel2'
                            ,recv_mobile     = '$receiver_mobile1 $receiver_mobile2'
                            ,recv_mobile1    = '$receiver_mobile1'
                            ,recv_mobile2    = '$receiver_mobile2'
                            ,recv_zip1       = '$receiver_zip1'
                            ,recv_zip2       = '$receiver_zip2'
                            ,recv_address1   = '$receiver_address1'
                            ,recv_address2   = '$receiver_address2'
                            ,recv_address3   = '$receiver_address3'
                            ,trans_who       = '$trans_who'
                            ,reg_date        = Now()
                            ,memo            = '$memo'
                            ,status          = 1
                            ,box             = $qty
                            ,ret_type        = '$ret_type'
                            ,trans_price     = $trans_price
                            ,product_price   = $product_price
                            ,recv_seq        = $receiver_seq
                       where seq = $takeback_seq";
            mysql_query( $query, $connect );
    
		    debug( $query );       
		}
     
        // 입력된 자료 출력..
        $query = "select * from takeback_order where seq=$takeback_seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );        
    }
    
    function del_takeback()
    {
        global $connect, $seq;
        global $sender_name,$sender_tel1,$sender_tel2,$sender_mobile1,$sender_mobile2,$sender_zip1,$sender_zip2;
        global $sender_address1,$sender_address2,$sender_address3,$receiver_name,$receiver_tel1,$receiver_tel2,$receiver_mobile1;
        global $receiver_mobile2,$receiver_zip1,$receiver_zip2,$receiver_address1,$receiver_address2,$receiver_address3;
        global $trans_who,$takeback_seq,$ret_type,$shop,$qty,$memo,$product_price,$trans_price,$receiver_seq,$takeback_seq;
        
        // 박스 수량이 없으면 기본 1
        $qty = $qty ? $qty : 1;
        $trans_price = $trans_price ? $trans_price : 0;
        $product_price = $product_price ? $product_price : 0;
        
        $query = "select order_seq from takeback_order where seq=$takeback_seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        // cs입력
        $this->csinsert($data[order_seq],"회수요청 취소");
        
        // 입력된 자료 출력..
        $query = "select * from takeback_order where seq=$takeback_seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 삭제 실행..
        $query = "delete from takeback_order 
                   where seq = $takeback_seq";
        mysql_query( $query, $connect );
        
        echo json_encode( $data );
    }
    
    ////////////////////////////////////////
    // 일반 cs내역을 남긴다. jkh 신발주버전
    function csinsert($_seq="", $_msg = "")
    {
        global $connect, $memo,$seq;
        
        $content = $_msg ? $_msg : "회수요청] $memo";
        $seq  = $_seq ? $_seq : $seq;
        
        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = 34,
                       cs_reason  = '',
                       cs_result  = '0',
                       content    = '$content'";
        mysql_query ( $sql, $connect );
        
        debug( $sql );
    }
    
    //
    // 원 송장 번호를 구한다. 
    //
    function get_org_trans_no( $seq )
    {
        global $connect;
        $query = "select trans_no,trans_corp from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data  = mysql_fetch_assoc( $result );
        return $data;
    }
    // 
    // 반품 접수
    // 
    function reg_takeback()
    {
        global $connect, $seq;
        global $sender_name,$sender_tel1,$sender_tel2,$sender_mobile1,$sender_mobile2,$sender_zip1,$sender_zip2;
        global $sender_address1,$sender_address2,$sender_address3,$receiver_name,$receiver_tel1,$receiver_tel2,$receiver_mobile1;
        global $receiver_mobile2,$receiver_zip1,$receiver_zip2,$receiver_address1,$receiver_address2,$receiver_address3;
        global $trans_who,$takeback_seq,$ret_type,$shop,$qty,$memo,$product_price,$trans_price,$receiver_seq;
        global $prdSeq, $site_price, $envelop_price, $account_price;

        // last_send 수정
        if( _DOMAIN_ == "soramam" )
        {
            /*
            $query = "update takeback_receiverinfo set last_send=0";
            mysql_query( $query, $connect );
            
            $query = "update takeback_receiverinfo set last_send=1 where name='$receiver_name'";
            debug( $query );
            mysql_query( $query, $connect );
            */
        }
        
		// 기본 택배사 정보..
	    $query = "select base_trans_code,takeback_use_default_trans_corp from ez_config";
		$result = mysql_query( $query, $connect );
		$data   = mysql_fetch_assoc( $result );
		
		// 원 송장번호 정보
        $arr_info     = $this->get_org_trans_no( $seq );
        $org_trans_no = $arr_info[trans_no];
        
        // 회수시 기본 택배사 사용여부 설정 2013.4.10 - jkryu
		if ( $data[takeback_use_default_trans_corp] == 1 )
	        $_trans_corp = $data[base_trans_code]; 
	    else
            $_trans_corp = $arr_info[trans_corp]; 
	    
        // cs에 회수관련 메시지 남긴다.
        $this->csinsert();
        
        // 반품예정 정보
        $this->return_expect( $prdSeq, $site_price, $envelop_price, $account_price, $trans_who );
        
        // 박스 수량이 없으면 기본 1
        $qty = $qty ? $qty : 1;
        $trans_price = $trans_price ? $trans_price : 0;
        $product_price = $product_price ? $product_price : 0;
        
        $query = "insert into takeback_order 
                     set order_seq       = $seq
                        ,sender_name     = '$sender_name'
                        ,sender_tel      = '$sender_tel1 $sender_tel2'
                        ,sender_tel1     = '$sender_tel1'
                        ,sender_tel2     = '$sender_tel2'
                        ,sender_mobile   = '$sender_mobile1 $sender_mobile2'
                        ,sender_mobile1  = '$sender_mobile1'
                        ,sender_mobile2  = '$sender_mobile2'
                        ,sender_zip1     = '$sender_zip1'
                        ,sender_zip2     = '$sender_zip2'
                        ,sender_address1 = '$sender_address1'
                        ,sender_address2 = '$sender_address2'
                        ,sender_address3 = '$sender_address3'
                        ,recv_name       = '$receiver_name'
                        ,recv_tel        = '$receiver_tel1 $receiver_tel2'
                        ,recv_tel1       = '$receiver_tel1'
                        ,recv_tel2       = '$receiver_tel2'
                        ,recv_mobile     = '$receiver_mobile1 $receiver_mobile2'
                        ,recv_mobile1    = '$receiver_mobile1'
                        ,recv_mobile2    = '$receiver_mobile2'
                        ,recv_zip1       = '$receiver_zip1'
                        ,recv_zip2       = '$receiver_zip2'
                        ,recv_address1   = '$receiver_address1'
                        ,recv_address2   = '$receiver_address2'
                        ,recv_address3   = '$receiver_address3'
                        ,trans_who       = '$trans_who'
                        ,reg_date        = Now()
                        ,memo            = '$memo'
                        ,status          = 1
                        ,box             = $qty
                        ,ret_type        = '$ret_type'
                        ,trans_price     = $trans_price
                        ,product_price   = $product_price
                        ,recv_seq        = $receiver_seq
                        ,trans_corp      = '$_trans_corp'
                        ,org_trans_no    = '$org_trans_no'
                        ";
		debug( $query );
        mysql_query( $query, $connect );
        
        // 입력된 자료 출력..
        $query = "select LAST_INSERT_ID() seq from takeback_order";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );        
        
    }

    //
    // 주소 정렬
    // 제일 끝의 시, 군, 구, 동, 읍, 면 삭제.
    //
    function arrange_address( $val )
    {
        // 시, 군, 구, 동, 읍, 면
        $val = preg_replace("/[시|군|구|동|읍|면]$/","", $val);
        
        return $val;                  
    }

    //
    // 환경설정을 보고 택배사 우편번호 테이블을 조회해서 return을 해줘야 한다.
    //
    

    //
    // 대통 주소가져오기
    //
    function get_dt_address( $zipcode )
    {
        $odb = new class_db();
        // $dt_connect = $odb->connect(_MYSQL_KOREX_HOST_, _MYSQL_KOREX_ID_, _MYSQL_KOREX_PASSWD_);       
        $dt_connect = $odb->connect("66.232.145.163", "cjdt", "pimz8282");       
        
        //$query  = "select * from zip_code where post_no='$zipcode'";
        //$query  = "select * from zip_code_new where zip_no='$zipcode'";
        $query  = "select * from zipcode where zip_no='$zipcode'";
        
        $result = mysql_query($query, $dt_connect);
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data )        
	    {
            //return array(1,$data[addr_state],$data[addr_city], $data[addr_town]);
            return array(1,$data[sido],$data[gugun], $data[dong]);
        }
        else
            return array(0);
    }

    //
    // 2014.5.8 - epost에서 검색해 오는걸로 수정함.- jkryu
    //
    function zip_search()
    {
        global $_txt;
        
        require_once "HTTP/Request.php";
        
        $arr_data = array();
        
        if ( strcmp( $_txt, "" ) ) 
        {
            //$user_address = iconv('utf8','cp949',$user_address);
            $key = "f012367cda4fe7e5f1251903512192";
            //$req = new HTTP_Request("http://biz.epost.go.kr/KpostPortal/openapi");
            $req = new HTTP_Request("http://admin4.ezadmin.co.kr/zipcode_proxy.htm");
            $req->setMethod(HTTP_REQUEST_METHOD_POST);
            $req->addPostData("regkey", $key);
            $req->addPostData("target", "post");
            $req->addPostData("query", $_txt);
            
            if (!PEAR::isError($req->sendRequest())) 
            {
                 $response1 = $req->getResponseBody();
            } else {
                 $response1 = "";
            }
    
            $response2 = str_replace("&", "&amp;", $response1);
    
            $dom = new DOMDocument();
            $dom->loadXML($response2); 
            $dom->preserveWhiteSpace = false; 
            $domList = $dom->getElementsByTagName('item');
    
            $zip_arr = array();
            foreach( $domList as $domEl )
            {
                $zip_arr[] = array(
                    zip => $domEl->getElementsByTagName('postcd')->item(0)->nodeValue,
                    add => $domEl->getElementsByTagName('address')->item(0)->nodeValue
                );
            }
            
            // 주소로 정렬
            foreach ($zip_arr as $key => $row) 
            {
                $zip[$key] = $row['zip'];
                $add[$key] = $row['add'];
            }
            
            if( $zip_arr )
                array_multisort($add, SORT_ASC, $zip, SORT_ASC, $zip_arr);
    
            $cnt = 0;
            
            $arr_data['success'] = 1;
            
            foreach( $zip_arr as $zip_val )    
            {
                $data["ZIPCODE"]  = $zip_val['zip'];
                $data["ADDRESS"]  = $zip_val['add'];
                
                $data["success"]     = 1;
            
                $arr_data[rows][]    = $data;      
            }
        }
        echo json_encode($arr_data);
    }

    //
    // 우편번호 가져오기.
    //
	// sysdomain에 connect해서 찾는다
	// 2014.5.8 - epost에서 검색해 오는걸로 수정함.
    function zip_search_org()
    {
        global $_txt;
        $sys_connect = sys_db_connect();    //2013.6.12 현재 15번 서버..
        
        $odb = new class_db();
        $dt_connect = $odb->connect(_MYSQL_KOREX_HOST_, _MYSQL_KOREX_ID_, _MYSQL_KOREX_PASSWD_);       
        
        $arr_keyword = split( " ", $_txt );
      
		// DONG => RI로 변경 2013.2.28 
		// zipcode => zip_code_new 변경 2013.6.12 - jkryu
        if ( count($arr_keyword) > 1 )
        {
            $query = "select * from zipcode
                   where DONG like '%$arr_keyword[0]%' 
                     and RI   like '%$arr_keyword[1]%'";
        }
        else
        {
           $query = "select * from zipcode
                   where DONG like '%$arr_keyword[0]%'
                      or RI   like '%$arr_keyword[0]%'";
        }          
        
        $query .= " and is_update = 1 limit 150";

        debug( $query );

        $result = mysql_query( $query, $sys_connect );
        $arr_data = array();
        $arr_data["success"] = 0;
        
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            //$data[success]       = $this->check_korex_zipcode( $data[ZIPCODE], $dt_connect );
            $data["RI"]          = "";
            $data["BUNJI"]       = "";
            $data["success"]     = 1;
            
            $arr_data[rows][]    = $data;            
            $arr_data["success"] = 1;
        }

        //if ( !$arr_data )
        //    $arr_data = $this->cj_zip_search();

        echo json_encode($arr_data);
    }

    //
    // 대통 우편번호 check
    //
    function check_korex_zipcode( $zipcode, $dt_connect )
    {
        $zipcode = str_replace("-","",$zipcode);
        
        $query  = "select * from zip_code 
                   where post_no='$zipcode'";    
        
        debug( "check korex zip: $query ");
        
        $result = mysql_query($query, $dt_connect);
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[post_no] )
            return 1;   // 검색되면 true
        else
            return 0;   // 검색 안 되면 false
    }

    //
    // 조회
    // search_type에 따라 검색 조건이 다름
    // search_type: trans(배송)   : orders에서 검색함
    // search_type: takeback(반품): takeback_order에서 검색함
    //
    function query()
    {
        global $connect;
        header('Content-Type: text/html; charset=utf-8'); 
        
		$start_date      = $_REQUEST["f-calendar-field-1"];
		$end_date        = $_REQUEST["f-calendar-field-2"];
        $search_sender   = $_REQUEST["search_sender"];      // 송화인으로 검색
        $search_receiver = $_REQUEST["search_receiver"];    // 수화인으로 검색
        $name            = $_REQUEST["name"];
        $tel1            = $_REQUEST["tel1"];
        $tel2            = $_REQUEST["tel2"];
        $search_type     = $_REQUEST["search_type"];
        $trans_no        = $_REQUEST["trans_no"];
        $seq             = $_REQUEST["seq"];
        $page            = $_REQUEST["page"] ? $_REQUEST["page"] : 1;

        $starter = ($page-1)*20;
        
        if ( $search_type == "trans" )
        {
            $sql     = "select trans_date_pos, shop_id, seq,pack,order_id,product_name,options,qty,recv_name,order_name";
            $sql_cnt = "select count(*) cnt ";
            $option  = "from orders 
                       where status=8 and trans_date_pos between '$start_date 00:00:00' and '$end_date 23:59:59' ";
                     
            if ( $name )
                $option .= " and recv_name='$name' ";
                
            if ( $tel1 || $tel2 )
            {
                $tel = $tel1 . $tel2;
                $option .= " and recv_tel = '$tel' ";
            }
            
            if ( $trans_no )
            {
                $option .= " and trans_no='$trans_no' ";
            }
            
            if ( $seq )
            {
                $option .= " and seq=$seq ";   
            }
            
            $page_option = " order by seq desc limit $starter, 20";
                 
        }
        else if ( $search_type == "takeback" || $search_type == "takeback_1" || $search_type == "takeback_2" || $search_type == "takeback_3" || $search_type == "takeback_4")
        {
            $sql     = "select a.trans_date_pos, a.shop_id, a.seq,pack,a.order_id,a.product_name,a.options,a.qty,a.recv_name,a.order_name";
            $sql_cnt = "select count(*) cnt ";
            $option  = "from orders a, takeback_order b
                       where a.seq    = b.order_seq
                         and a.status = 8 
                         and trans_date_pos between '$start_date 00:00:00' and '$end_date 23:59:59' ";
        
            if ( $search_type == "takeback_1" )
            {
               $option .= " and b.status=1 ";
            }
            else if ( $search_type == "takeback_2" )
            {
               $option .= " and b.status=2 ";
            }
            else if ( $search_type == "takeback_3" )
            {
               $option .= " and b.status=3 ";
            }
            else if ( $search_type == "takeback_4" )
            {
               $option .= " and b.status=4 ";
            }
                    
            if ( $name )
                $option .= " and b.recv_name='$name' ";
                
            if ( $tel1 || $tel2 )
            {
                $tel = $tel1 . $tel2;
                $option .= " and b.recv_tel like '%$tel%' ";
            }
            
            if ( $trans_no )
            {
                $option .= " and b.trans_no='$trans_no' ";
            }
            
            if ( $seq )
            {
                $option .= " and a.seq=$seq ";   
            }
            
            $page_option = " group by a.seq order by b.seq desc limit $starter, 20";
        }
        
        
        
        //echo "$sql $option $page_option";
        //exit;
        
        // 개수..
        $result = mysql_query( "$sql_cnt $option ", $connect) or die(mysql_error());
        $data   = mysql_fetch_assoc( $result );
        $cnt    = $data[cnt];
        
        // 자료
		debug( $sql. $option. $page_option );

		$result = mysql_query( "$sql $option $page_option", $connect) or die(mysql_error());
		echo "<script language='javascript'>page_index($cnt,$page)</script>";
		$this->disp_result( $result );
    }
   
    //
    // 조회 결과 출력
    //
    function disp_result( $result ) 
    {
        $ret = "<table cellspacing=0 cellpadding=0 border=0 bgcolor=#CCCCCC width=100%>";
        $ret .= "<tr bgcolor=#E5E591 height=22>
                    <td>배송일</td>
                    <td>판매처</td>
                    <td>관리번호</td>
                    <td>주문번호</td>
                    <td>상품명</td>
                    <td>옵션</td>
                    <td>개수</td>
                    <td>금액</td>
                    <td>주문자</td>
                    <td>수령자</td>
                </tr>";
        
        
    	while ( $data = mysql_fetch_assoc($result) )
    	{
        	//$ret .= "<tr bgcolor=#FFFFFF height=20 class='NavOff' onmouseover=\"className='NavOn'\" onmouseout=\"className='NavOff'\">
        	$shop_name = class_shop::get_shop_name( $data[shop_id] );
        	$ret .= "
        	<tr><td colspan=10 class='td_line'></td></tr>
        	<tr bgcolor=#FFFFFF height=20 class='NavOff' onmouseover=\"swap_class(this)\" onClick='get_detail($data[seq])'>
                    <td>$data[trans_date_pos]</td>
                    <td>$shop_name</td>
                    <td>$data[seq]</td>
                    <td>$data[order_id]</td>
                    <td>$data[product_name]</td>
                    <td>$data[options]</td>
                    <td>$data[qty]</td>
                    <td>$data[amount]</td>
                    <td>$data[order_name]</td>
                    <td>$data[recv_name]</td>
            </tr>";
            
            $ret .= $this->get_sub_takeback( $data[seq] );
    	}
    
        $ret .= "</table>";
    
        

    	echo $ret;  
    }
    
    //
    // 회수 요청 리스트 2011.5.9
    function get_sub_takeback( $seq )
    {
        global $connect;
        
        $str = "";
        
        $query = "select * from takeback_order where order_seq = $seq order by seq desc";
        $_result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $_result ) )
        {
            $str_reg_trans_corp = "<span class=red>실패</span>";
            
            if ( $data[reg_error] == 0 &&  $data[status] >= 2)
                $str_reg_trans_corp = "<span class=red>성공</span>";
            else
                $str_reg_trans_corp = "<span class=red>준비중</span>";
            
             $str .= "<tr bgcolor=#FFFFFF height=20 id='row_" . $data[seq] . "' class='NavOff' onmouseover=\"swap_class(this)\" onClick='get_detail_takeback($data[seq],$data[order_seq])'>
                    <td align='right'> <img src=images/icon_takeback.gif> &nbsp;</td>
                    <td colspan=9>
                    $data[recv_name] 접수.: $data[reg_date] / 송장: $data[trans_date]($data[trans_no]) / 수령: $data[recv_date] / 택배등록: $str_reg_trans_corp
                    </td>
            </tr>";
        }
        
        return $str;
    }
    
    
    // 전화번호 정렬
    // 4:8의 형태가 되어야 함
	// 025211774 => (  02 5211774)의 형태..
    // - 는 사라져야 함
    // 0505로 시작되는 번호 => 0505 1234567
    function arrange_tel( $telno )
    {
        // 숫자이외의 정보는 삭제
        $telno = preg_replace("/[^0-9]/","",$telno );

        // 앞에 2자리를 먼저 check
        $_ddd = substr( $telno,0,4);

        if ( $_ddd == "0505" 
          || $_ddd == "1688"
          || $_ddd == "0502"
          //|| $_ddd == "0103"
          || $_ddd == "0130"
          || $_ddd == "0303"
          || $_ddd == "0502"
          || $_ddd == "0504"
          || $_ddd == "0505"
          || $_ddd == "0506"
          || $_ddd == "1544"
          || $_ddd == "1566"
          || $_ddd == "1577"
          || $_ddd == "1588"
          || $_ddd == "1599"
          || $_ddd == "1600"
          || $_ddd == "1644"
          || $_ddd == "1666"
          || $_ddd == "1688"
        )
        {
            $_ddd = substr( $telno, 0,4);
            $_tel = substr( $telno, 4);
        }
        else
        {
            $_ddd = substr( $telno,0,2);

            if ( $_ddd == "02" )
            {
                $_ddd = substr( $telno, 0,2);
                $_tel = substr( $telno, 2);
            }
            else
            {
                $_ddd = substr( $telno, 0,3);
                $_tel = substr( $telno, 3);
            }

        }
        return array($_ddd, $_tel );
    }

    function return_expect($prdSeq, $ret_site, $ret_envelop, $ret_account, $trans_who )
    {
        global $connect;

        $val = array();
        
        $ret_site = preg_replace('/[^0-9]/','',$ret_site);
        $ret_envelop = preg_replace('/[^0-9]/','',$ret_envelop);
        $ret_account = preg_replace('/[^0-9]/','',$ret_account);
        if( $trans_who == "02" || $trans_who == "04" )
            $r_trans_who = 1;
        else
            $r_trans_who = 0;
        
        // 주문정보
        $query = "select a.seq             a_seq,
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
                         order_products b,
                         products c
                   where a.seq = b.order_seq and 
                         b.product_id = c.product_id and 
                         b.seq = $prdSeq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        // 배송 아님
        if( $data[a_status] != 8 )
            return;

        // 취소 상품
        if( $data[b_order_cs] == 1 || $data[b_order_cs] == 2 || $data[b_order_cs] == 3 || $data[b_order_cs] == 4 )
            return;

        // 배송후 교환
        if( $data[b_order_cs] == 7 || $data[b_order_cs] == 8 )
            return;


        // 이미 반품예정 정보 있는지 확인
        $query_return = "select * from return_money where order_products_seq = $prdSeq and is_delete=0";
        $result_return = mysql_query($query_return, $connect);
        if( mysql_num_rows($result_return) )
        {
            $data_return = mysql_fetch_assoc($result_return);
            
            // 이미 반품처리된 상품
            if( $data_return['is_return'] == 1 )
                return;

            // 반품예정 update
            $query = "update return_money 
                         set expect_site        = '$ret_site',
                             expect_envelop     = '$ret_envelop',
                             expect_account     = '$ret_account',
                             expect_trans_who   = '$r_trans_who'
                       where seq = $data_return[seq]";
            mysql_query($query, $connect);

            // 반품예정 로그
            $log_contents  = "사이트결제 : " . number_format($ret_site) . " 원, ";
            $log_contents .= "동봉 : " . number_format($ret_envelop) . " 원, ";
            $log_contents .= "계좌 : " . number_format($ret_account) . " 원, ";
            $log_contents .= "선착불 : " . ($r_trans_who ? "착불" : "선불");
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
                         set order_seq          = '$data[a_seq]',
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
            $log_contents .= "선착불 : " . ($r_trans_who ? "착불" : "선불");
            $query_return_log = "insert return_money_log
                                    set seq                = '$data[seq]',
                                        log_type           = 'expect',
                                        log_contents       = '$log_contents',
                                        log_date           = now(),
                                        log_worker         = '$_SESSION[LOGIN_NAME]'";
            mysql_query($query_return_log, $connect);
        }
    }
    
    
}// end of class
?>
