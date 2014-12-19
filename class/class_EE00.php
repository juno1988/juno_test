<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";

//$bck_connect = bck_db_connect();

////////////////////////////////
// class name: class_EE00
// EE02: 이현열 요청사항 - 송장조회
// admin4.ezadmin.co.kr/dummy.htm?template=EE02
// EE03: 아아태 요청사항 - 송장조회
//
class class_EE00 extends class_top 
{
    //
    // 김연수 소장 요청사항
    // 2012.11.02 - jkryu
    // http://admin4.ezadmin.co.kr/dummy.htm?template=EE04
	//

    function EE04()
    {
        global $act;
        
        $arr_domain = array();
        
        if ( $act == "query" )
        {
            global $fromdate , $todate;
            
            $fromdate = $fromdate ? $fromdate : "2012-11-1";
            $todate   = $todate   ? $todate   : "2012-11-3";
            
            $arr_domain[] = array( "id" => "ljc6605"   , "host" => "121.254.179.105" ,"name" => "효정");
            $arr_domain[] = array( "id" => "3point"    , "host" => "66.232.146.71"   ,"name" => "플루크");
            $arr_domain[] = array( "id" => "styleshop" , "host" => "121.254.179.106" ,"name" => "리치 ");
            $arr_domain[] = array( "id" => "oops"      , "host" => "121.254.179.106" ,"name" => "스타일투킬 ");
            $arr_domain[] = array( "id" => "barbie"    , "host" => "121.254.179.106" ,"name" => "바비스토리 ");
            $arr_domain[] = array( "id" => "plays"     , "host" => "121.254.179.106" ,"name" => "플레이즈 ");
            $arr_domain[] = array( "id" => "sbs"       , "host" => "66.232.145.241"  ,"name" => "쏭바이쏭 ");
            $arr_domain[] = array( "id" => "lilly"     , "host" => "66.232.145.241"  ,"name" => "쿨하다 ");
            $arr_domain[] = array( "id" => "huiz2"     , "host" => "66.232.146.205"  ,"name" => "휴아이지 ");
            $arr_domain[] = array( "id" => "lylon"     , "host" => "66.232.145.241"  ,"name" => "라일론 ");
            $arr_domain[] = array( "id" => "loropop"   , "host" => "121.254.179.118" ,"name" => "로로팝 ");
            $arr_domain[] = array( "id" => "perte"     , "host" => "66.232.146.205"  ,"name" => "빼르떼 ");
            $arr_domain[] = array( "id" => "treksta"   , "host" => "66.232.146.205"  ,"name" => "현우 ");
            $arr_domain[] = array( "id" => "nudonado"  , "host" => "66.232.146.205"  ,"name" => "너도나도");
            $arr_domain[] = array( "id" => "dogpre"    , "host" => "222.231.24.90" ,"name" => "강아지대통령");
            $arr_domain[] = array( "id" => "gw"        , "host" => "121.254.179.118" ,"name" => "지더블유");
            $arr_domain[] = array( "id" => "ozsama"    , "host" => "66.232.145.241"  ,"name" => "옷사마");
            $arr_domain[] = array( "id" => "alice"     , "host" => "222.231.24.102"  ,"name" => "리드미컬");
            $arr_domain[] = array( "id" => "ccstars"   , "host" => "121.254.179.118" ,"name" => "초코별");
            
            for( $i=0; $i < count( $arr_domain ); $i++ )
            {
                $_domain = $arr_domain[ $i ];
                
                $connect = mysql_connect($_domain[host], $_domain[id], "gideksdl");
                mysql_select_db($_domain[id], $connect);         
                
                $query  = "select count( distinct(trans_no) ) cnt from orders where trans_date >='$fromdate' and trans_date <='$todate'";
                
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                $arr_domain[ $i ][cnt] = $data[cnt];
                
                
                // 배송 건수 추가
                $query  = "select count( distinct(trans_no) ) cnt from orders where trans_date_pos >='$fromdate' and trans_date_pos <='$todate'";
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                $arr_domain[ $i ][trans_cnt] = $data[cnt];
            }
        }
        
        include "template/E/EE04.htm"; 
    }

    ///////////////////////////////////////////
    function EE03()
    {
        global $trans_no;
        $host = "66.232.146.205";
        $id   = "kshsame2";
        $pass = "gideksdl";
        
        $connect = mysql_connect($host, $id, $pass);
        mysql_select_db($id, $connect);  
        
        $is_real = "정품인증 실패";
        
        if ( $trans_no )
        {
            $query = "select count(*) cnt from orders where trans_no='$trans_no' and status in (7,8)";
            $result= mysql_query( $query, $connect );
            $data  = mysql_fetch_assoc( $result );
            
            if ( $data[cnt] >= 1 )
            {
                $is_real = "정품인증";   
            }
            else
            {
                $is_real = "정품인증 실패";
            }
        }
        
        include "template/E/EE03.htm"; 
    }
   
    function EE00()
    {
	    global $connect;
	    global $template;


        $par_arr = array("template","action","search_type","keyword","start_date","end_date","shop_id","page");
        $link_url_list = $this->build_link_par($par_arr);  
    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //
    // 이현열 소장 요청사항..
    // 다바걸 cs 정보 가져온다.
    // 2011.11.18 - jk
    //
    function get_trans_no_cs()
    {
        global $trans_no, $domain;
        
        $arr_domain = array();
        $arr_domain['dabagirl2'] = array("host" => "222.231.24.90" );
        $arr_domain['flyday']    = array("host" => "222.231.24.90" );
        $arr_domain['soramam']   = array("host" => "66.232.145.241" );
        $arr_domain['hellodiva'] = array("host" => "66.232.146.171" );
        $arr_domain['ananshuz2'] = array("host" => "66.232.146.171" );
        $arr_domain['kikiholic'] = array("host" => "66.232.146.171" );
        $arr_domain['dm']        = array("host" => "66.232.146.205" );
        $arr_domain['aboutyou2'] = array("host" => "121.254.179.118" );
        $arr_domain['aboutyou']  = array("host" => "121.254.179.118" );
        $arr_domain['mrs']       = array("host" => "66.232.146.205" );
        $arr_domain['wenis2']    = array("host" => "121.254.179.118" );
        $arr_domain['twoj2']     = array("host" => "121.254.179.118" );
        
        $host = $arr_domain[$domain]["host"];
        $id   = $domain;
        $pass = "gideksdl";
        
        $connect = mysql_connect($host, $id, $pass);
        mysql_select_db($id, $connect);        
        $arr_result = array();
        
        // status, order_cs 가져오기
        $status   = 0;
        $order_cs = 0;
        $str_seqs = "";
        
        $query = "select status, order_cs,seq,trans_date,trans_date_pos,trans_no,recv_name,recv_tel,recv_mobile,recv_zip,recv_address from orders where trans_no='$trans_no'";

		$arr_result["query1"] = $query;

        $result = mysql_query( $query, $connect );
        
		$arr_result["num"] = mysql_num_rows($result);

        if ( mysql_num_rows($result) == 0 )
        {
            $query = "select status, order_cs,seq,trans_date,trans_date_pos,trans_no,recv_name,recv_tel,recv_mobile,recv_zip,recv_address from orders_old where trans_no='$trans_no'";
            $result = mysql_query( $query, $connect );
        }
        
        // 과거 주문에도 없는경우 trans_upload_log를 찾는다.
        if ( mysql_num_rows($result) == 0 )
        {
            $query = "select order_seq from trans_upload_log where trans_no='$trans_no'";
            //echo $query;
            $result = mysql_query( $query, $connect );
            $data = mysql_fetch_assoc( $result );
            $_seq = $data[order_seq];
            
            // 현재 trans_no를 구한다.
            $query = "select trans_no from orders where seq=$_seq";
            $result = mysql_query( $query, $connect );
            $data = mysql_fetch_assoc( $result );
            $_trans_no = $data[trans_no];
            
            // 송장번호 삭제
            if ( $trans_no != $_trans_no )
            {
                $arr_result["is_del"] = "송장번호 변경 => " . $_trans_no;
            }
        }
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $status < $data[status] )
                $status = $data[status];
                
            if ( $order_cs < $data[order_cs] )
                $order_cs = $data[order_cs];
                
            if ( $str_seqs != "" )
                $str_seqs .= ",";
                
            $str_seqs .= $data[seq];
            
            $arr_result["trans_date"]     = $data["trans_date"];
            $arr_result["trans_date_pos"] = $data["trans_date_pos"];
            $arr_result["trans_no"]       = $data["trans_no"];
            
            // 추가 2012.9.27 - jkryu
            $arr_result["recv_name"]      = $data["recv_name"];
            $arr_result["recv_tel"]       = $data["recv_tel"];
            $arr_result["recv_mobile"]    = $data["recv_mobile"];
            $arr_result["recv_zip"]       = $data["recv_zip"];
            $arr_result["recv_address"]   = $data["recv_address"];
        }
        
        $arr_result["status"]   = $status;
        $arr_result["order_cs"] = $order_cs;
        
        
        // cs 조회
        $query = "select * from csinfo where order_seq in ( $str_seqs ) order by seq desc";        

		$arr_result["query"] = $query;

        $result = mysql_query( $query, $connect );
        
        if ( mysql_num_rows($result) == 0 )
        {
            $query = "select * from csinfo_old where order_seq in ( $str_seqs ) order by seq desc";        
            $result = mysql_query( $query, $connect );    
        }
        
        // echo $query;
        
        $arr_result["list"] = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result["list"][] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    //
    // 이현열 소장 요청사항..
    // 다바걸 cs보여준다.
    //
    function EE02()
    {
	    global $connect;
	    global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function EE01()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function recover()
    {
        global $connect, $seq;
        global $cur_connect, $bck_connect;

        // 합포 확인
        $query = "select pack from orders where seq=$seq";
        $result = mysql_query($query, $bck_connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[pack] > 0 )
        {
            $seq_arr = array();
            $query = "select seq from orders where pack=$data[pack]";
            $result = mysql_query($query, $bck_connect);
            while( $data = mysql_fetch_assoc($result) )
                $seq_arr[] = $data[seq];
                
            $seq_list = implode(",", $seq_arr);
        }
        else
            $seq_list = $seq;

        // orders 
        $query = "select * from orders where seq in ($seq_list)";
        $result = mysql_query($query, $bck_connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_insert = "insert orders set ";
            foreach( $data as $key => $val )
                $query_insert .= " $key = '" . addslashes($val) . "',";

debug("과거주문복구 orders : " . $query_insert);
            mysql_query( substr($query_insert,0,-1), $cur_connect );
            
            // 전화번호 검색
            $this->inset_tel_info($data[seq], array($data[recv_tel],$data[recv_mobile],$data[order_tel],$data[order_mobile]));
        }
        
        // is_bck = 1, 합포불가
        $query_bck = "update orders set is_bck = 1, pack_lock=1 where seq in ($seq_list)";
        mysql_query($query_bck, $cur_connect);
        
        // order_products 
        $query = "select * from order_products where order_seq in ($seq_list)";
        $result = mysql_query($query, $bck_connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_insert = "insert order_products set ";
            foreach( $data as $key => $val )
                $query_insert .= " $key = '" . addslashes($val) . "',";
            
debug("과거주문복구 order_products : " . $query_insert);
            mysql_query( substr($query_insert,0,-1), $cur_connect );
        }

        // csinfo
        $query = "select * from csinfo where order_seq in ($seq_list)";
        $result = mysql_query($query, $bck_connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_insert = "insert csinfo set ";
            foreach( $data as $key => $val )
                $query_insert .= " $key = '" . addslashes($val) . "',";
            
debug("과거주문복구 csinfo : " . $query_insert);
            mysql_query( substr($query_insert,0,-1), $cur_connect );
        }
    }
}
?>
