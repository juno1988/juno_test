<?
require_once "class_top.php";
require_once "class_ui.php";
require_once "class_db.php";
require_once "class_order.php";
require_once "class_product.php";
require_once "class_takeback.php";
require_once "class_file.php";
require_once "class_format.php";
require_once "class_shop.php";
require_once "class_supply.php";
require_once "class_option_match.php";
require_once "class_order_products.php";
require_once "class_proc_info.php";
require_once "class_statrule.php";
require_once "class_statrule2.php";
require_once "class_C.php";
require_once "class_order.php";
require_once "class_lock.php";
require_once "class_auto_unpack.php";

class class_DC00 extends class_top
{
    ///////////////////////
    function DC00()
    {
      global $connect;
      global $template, $start_date, $end_date;
    
      $master_code = substr($template, 0, 1);
      include "template/D/DC00.htm";
    }

    function get_today()
    {
        $val['today_str'] = date('Y년 m월 d일');
        $val['error']=0;
        echo json_encode( $val );
        exit;
    }
    
    //////////////////////////////////////////////////////////
    // [발주] 판매처 정보와 발주정보를 가져온다. 첫 화면 표시를 위해
    function get_shopinfo()
    {
        global $connect;

        // 판매처 정보를 가져온다.
        $query = "select * from shopinfo where disable = 0 order by sort_name";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 추가주문은 넘어간다.
            if( $data[shop_id] == 10000 )  continue;
            
            $query_log = "select work_date, cnt_total, cnt_new, worker from order_process_log where shop_id=$data[shop_id] and balju_ok=1 order by work_date desc limit 1";
            $result_log = mysql_query( $query_log, $connect );
            $data_log = mysql_fetch_array( $result_log );
            $worker = $data_log[worker];
            $shop_name =  $data[shop_name];
            
            // 자동발주 가능 판매처
            $shop_id = $data[shop_id] % 100;
            if( $shop_id == 6 || 
                $shop_id == 68 || 
                $shop_id == 72 || 
                $shop_id == 73 || 
                $shop_id == 50 )
                $noauto = 1;  // 모두 자동발주 막음
            else
                $noauto = 1;

            $query_balju = "select count(*) cnt_balju from orders where shop_id='$data[shop_id]' and status=0";
            $result_balju = mysql_query($query_balju, $connect);
            $data_balju = mysql_fetch_assoc($result_balju);
            
            $val['list'][] = array(
                domain    => _DOMAIN_,
                shop_id   => $data[shop_id],
                shop_icon => class_C::get_shop_name2($data[shop_id]),
                shop_logo => $data[logo],
                shop_name => $shop_name,
                user_id   => $data[userid],
                work_date => "$data_log[work_date]",
                cnt_total => "$data_log[cnt_total]",
                cnt_new   => "$data_log[cnt_new]",
                cnt_balju => "$data_balju[cnt_balju]",
                worker    => "$worker",
                noauto    => $noauto
            );
        }
        $val['error']=0;
        echo json_encode( $val );
        exit;
    }        

    //////////////////////////////////////////////////////////
    // [발주] 포멧 복사 위해서 판매처 정보를 가져온다. 
    function get_shopinfo_domain()
    {
        global $sys_connect, $connect, $domain;
        
        // 타 도메인인 경우 
        if( $domain != _DOMAIN_ )
        {
            $query = "select * from sys_domain where id='$domain'";
            $result = mysql_query($query, $sys_connect);
            $data = mysql_fetch_assoc($result);
            
            $domain_connect = class_db::connect( $data[host], $data[db_name], $data[db_pwd] );
        }
        else
            $domain_connect = $connect;
        
        // 판매처 정보를 가져온다.
        $query = "select * from shopinfo where disable = 0 order by sort_name";
        $result = mysql_query( $query, $domain_connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 추가주문은 넘어간다.
            if( $data[shop_id] == 10000 )  continue;
            
            $val['list'][] = array(
                shop_id   => $domain . "," . $data[shop_id],
                shop_name => $domain . " : " . $data[shop_name]
            );
        }
        $val['error']=0;
        echo json_encode( $val );
    }        

    //////////////////////////////////////////////////////////
    // [발주] 판매처 헤더 복사를 위한 도메인 정보 가져오기
    function get_domain_info()
    {
        global $connect;
        
        $val['domain'] = _DOMAIN_;
        $val['is_root'] = ( $_SESSION[LOGIN_LEVEL] == 9 ? 1 : 0 );
        
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [발주] 판매처 헤더 복사
    function copy_shopheader()
    {
        global $sys_connect, $connect, $shop_id_from_domain, $shop_id_to;

        $arr = explode(",", $shop_id_from_domain);
        $domain = $arr[0];
        $shop_id_from = $arr[1];
        
        // 타 도메인인 경우 
        if( $domain != _DOMAIN_ )
        {
            $query = "select * from sys_domain where id='$domain'";
debug("헤더 복사1 : " . $query);
            $result = mysql_query($query, $sys_connect);
            $data = mysql_fetch_assoc($result);
            
            $from_connect = class_db::connect( $data[host], $data[db_name], $data[db_pwd] );
        }
        else
            $from_connect = $connect;
        
        // 기존 정보 삭제
        $query = "delete from shopheader where shop_id=$shop_id_to";
        mysql_query($query, $connect);
        
        $query = "delete from shop_transkey where shop_id=$shop_id_to";
        mysql_query($query, $connect);
        
        // 복사
        $query = "select * from shopheader where shop_id=$shop_id_from";
debug("헤더 복사2 : " . $query);
        $result = mysql_query($query, $from_connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_in = "insert shopheader 
                            set shop_id     = '$shop_id_to',
                                field_id    = '$data[field_id]',
                                field_name  = '$data[field_name]',
                                shop_header = '$data[shop_header]',
                                abs         = '$data[abs]',
                                idx         = '$data[idx]'";
            mysql_query($query_in, $connect);
        }
        
        $query = "select * from shop_transkey where shop_id=$shop_id_from";
        $result = mysql_query($query, $from_connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_in = "insert shop_transkey 
                            set shop_id  = '$shop_id_to',
                                space    = '$data[space]',
                                keyword  = '$data[keyword]',
                                transwho = '$data[transwho]'";
            mysql_query($query_in, $connect);
        }
        
        $val['error']=0;
        echo json_encode( $val );
    }        

    //////////////////////////////////////////////////////////
    // [발주] 현재 판매처 헤더 설정사항을 시스템 기본값으로 저장
    function save_header_system()
    {
        global $sys_connect, $connect, $shop_id;
        
        // 기존 정보 삭제
        $query = "delete from sys_shopheader where shop_id=$shop_id % 100";
        mysql_query($query, $sys_connect);
        
        $query = "delete from sys_shop_transkey where shop_id=$shop_id % 100";
        mysql_query($query, $sys_connect);
        
        // 복사
        $query = "select * from shopheader where shop_id=$shop_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_in = "insert sys_shopheader 
                            set shop_id     = $shop_id % 100,
                                field_id    = '$data[field_id]',
                                field_name  = '$data[field_name]',
                                shop_header = '$data[shop_header]',
                                abs         = '$data[abs]',
                                idx         = '$data[idx]'";
            mysql_query($query_in, $sys_connect);
        }
        
        $query = "select * from shop_transkey where shop_id=$shop_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_in = "insert sys_shop_transkey 
                            set shop_id  = $shop_id % 100,
                                space    = '$data[space]',
                                keyword  = '$data[keyword]',
                                transwho = '$data[transwho]'";
            mysql_query($query_in, $sys_connect);
        }
        
        $val['error']=0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 사은품/무료배송 설정을 가져온다.
    function get_gift_checklist()
    {
        global $connect;

        $val['list'] = array();
        
        $today = date("Y-m-d");
        $query = "select * from new_gift order by seq desc";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $val['list'][] = array(
                seq         => $data[seq],
                pid         => $data[pid],
                pid_ex      => $data[pid_ex],
                shop_code   => $data[shop_id],
                shop_id     => class_shop::get_shop_name($data[shop_id]),
                shop_pid    => $data[shop_pid],
                shop_pid_ex => $data[shop_pid_ex],
                qty_start   => $data[qty_start],
                qty_end     => $data[qty_end],
                price_start => $data[price_start],
                price_end   => $data[price_end],
                price_flag  => $data[price_flag],
                all_price_flag  => $data[all_price_flag],
                trans_free  => $data[trans_free],
                gift_msg    => $data[gift_msg],
                only_flag   => $data[only_flag],
                qty_flag    => $data[qty_flag],
                product     => $data[product],
                worker      => $data[worker],
                crdate      => substr($data[crdate],0,10)
            );            
        }
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [발주] 판매처 목록을 가져온다.
    function get_shop_name()
    {
        global $connect;

        $val['list'][] = array(
            shop_id   => 0,
            shop_name => '전체'
        );
        foreach( class_shop::get_shop_list() as $shop )
            $val['list'][] = $shop;
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [발주] 사은품/무료배송 설정을 저장한다.
    function insert_gift_set()
    {
        global $connect;

        $msg = $_REQUEST[gift_msg];
        $query = "insert new_gift 
                     set pid        = '$_REQUEST[pid]',
                         pid_ex     = '$_REQUEST[pid_ex]',
                         shop_id    = '$_REQUEST[shop_id]',
                         shop_pid   = '$_REQUEST[shop_pid]',
                         shop_pid_ex= '$_REQUEST[shop_pid_ex]',
                         qty_start  = '$_REQUEST[qty_start]',
                         qty_end    = '$_REQUEST[qty_end]',
                         price_start= '$_REQUEST[price_start]',
                         price_end  = '$_REQUEST[price_end]',
                         price_flag = '$_REQUEST[price_flag]',
                         all_price_flag = '$_REQUEST[all_price_flag]',
                         trans_free = '$_REQUEST[trans_free]',
                         gift_msg   = '$msg',
                         product    = '$_REQUEST[product]',
                         only_flag  = '$_REQUEST[only_flag]',
                         qty_flag   = '$_REQUEST[qty_flag]',
                         worker     = '$_SESSION[LOGIN_NAME]',
                         crdate     = now()";
        $val['error'] = (mysql_query($query, $connect)?0:1) ;
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [발주] 사은품/무료배송 설정을 수정한다.
    function update_gift_set()
    {
        global $connect;

        $msg = $_REQUEST[gift_msg];
        $query = "update new_gift 
                     set pid        = '$_REQUEST[pid]',
                         pid_ex     = '$_REQUEST[pid_ex]',
                         shop_id    = '$_REQUEST[shop_id]',
                         shop_pid   = '$_REQUEST[shop_pid]',
                         shop_pid_ex= '$_REQUEST[shop_pid_ex]',
                         qty_start  = '$_REQUEST[qty_start]',
                         qty_end    = '$_REQUEST[qty_end]',
                         price_start= '$_REQUEST[price_start]',
                         price_end  = '$_REQUEST[price_end]',
                         price_flag = '$_REQUEST[price_flag]',
                         all_price_flag = '$_REQUEST[all_price_flag]',
                         trans_free = '$_REQUEST[trans_free]',
                         gift_msg   = '$msg',
                         product    = '$_REQUEST[product]',
                         only_flag  = '$_REQUEST[only_flag]',
                         qty_flag   = '$_REQUEST[qty_flag]',
                         worker     = '$_SESSION[LOGIN_NAME]',
                         crdate     = now()
                   where seq  = '$_REQUEST[seq]'";
        $val['error'] = (mysql_query($query, $connect)?0:1) ;
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [발주] 사은품/무료배송 설정을 저장한다.
    function delete_gift_set()
    {
        global $connect, $seq;
        
        $query = "delete from new_gift where seq=$seq";
        $val['error'] = mysql_query($query,$connect)?0:1;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 수동 발주
    function reg_orders_manual()
    {
        global $connect, $shop_id, $collect_date, $mode, $is_auto, $version;

        $val = array();
        $val['success'] = true;

debug("발주시작");

        // 발주일
        if( !$collect_date )  $collect_date=date("Y-m-d");
        
        // 판매처 정보
        $query = "select special_encoding from shopinfo where shop_id=$shop_id";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $special_encoding = $data['special_encoding'];

        // 발주 파일을 업로드
        $objFile = new class_file();
        
        // 타오바오는 파일 2개 업로드
        if( $shop_id % 100 == 59 )
        {
            // $file_ext 는 무조건 csv
            
            // 초가 같을 경우 파일이 중복됨.
            list($usec, $sec) = explode(" ", microtime());
            $msec = substr($usec,2,3);
    
            $new_fn1 = date("YmdHis") . "_" . $msec . "_" . $shop_id . "(1).csv";
            $new_fn2 = date("YmdHis") . "_" . $msec . "_" . $shop_id . "(2).csv";
            
            // $dataArr = $objFile->upload3_tb( $new_fn1, $new_fn2, &$ret, $special_encoding );
            $dataArr = $this->upload3_tb( $new_fn1, $new_fn2, &$ret);
        }
        else
        {
            $file_ext = substr( $_FILES['_file']['name'], strlen( $_FILES['_file']['name'] ) - 3 );
            
            // 초가 같을 경우 파일이 중복됨.
            list($usec, $sec) = explode(" ", microtime());
            $msec = substr($usec,2,3);
    
            $new_fn = date("YmdHis") . "_" . $msec . "_" . $shop_id . "." . $file_ext;
            
            if( $shop_id % 100 == 67 )
            {
                if( $is_auto )
                    $dataArr = $objFile->upload3( $new_fn, &$ret, "cacao" );
                else
                    $dataArr = $objFile->upload3( $new_fn, &$ret );
            }
            else if( $special_encoding )
                $dataArr = $objFile->upload3( $new_fn, &$ret, $special_encoding );
            else
                $dataArr = $objFile->upload3( $new_fn, &$ret );
        }

        if( $shop_id % 100 == 26 && $is_auto && (!$version || $version < '1.0.15.396') )
        {
            $val['error'] = 20000;
            $val['msg'] = "발주 오류. 이지오토를 업데이트 하십시요.";
            if( $mode ) // 수동
                echo json_encode( $val );
            else  // 자동
                echo "DC00_error_1_, DC00_msg_" . iconv('utf-8','cp949',$msg) . "_";
            return;
        }
        
        if( $shop_id % 100 == 42 && $is_auto && (!$version || $version < '1.0.15.513') )
        {
            $val['error'] = 20000;
            $val['msg'] = "발주 오류. 이지오토를 업데이트 하십시요.";
            if( $mode ) // 수동
                echo json_encode( $val );
            else  // 자동
                echo "DC00_error_1_, DC00_msg_" . iconv('utf-8','cp949',$msg) . "_";
            return;
        }
        
        if( $ret )
        {
debug("초과초과");

            $val['error'] = 20000;
            $val['msg'] = "발주서 파일이 최대 20000 행을 초과하였습니다.";
            if( $mode ) // 수동
                echo json_encode( $val );
            else  // 자동
                echo "DC00_error_1_, DC00_msg_" . iconv('utf-8','cp949',$msg) . "_";
            return;
        }
        
        switch( strtoupper($file_ext) )
        {
            case "XLS": $x=0; break;
            case "CSV": $x=1; break;
            case "TXT": $x=1; break;
        }

        // Lock Check
        $obj_lock = new class_lock(516);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['msg'] = $msg;
            if( $mode ) // 수동
                echo json_encode( $val );
            else  // 자동
                echo "DC00_error_1_, DC00_msg_" . iconv('utf-8','cp949',$msg) . "_";
            return;
        }

/*
// 발주서 로그
$logCnt = 0;
foreach( $dataArr as $logRow )
{
    $logCnt++;
    $logStr = " 발주서 원본($logCnt) : ";
    foreach( $logRow as $logData )
        $logStr .= '"' . $logData . '",';
    debug( $logStr . "\n" );
}
*/
        // 발주 데이터로부터 orders 를 만든다.
        $objFormat = new class_format();
        if( $objFormat->make_orders($dataArr, $shop_id, &$cnt_total, &$cnt_new, &$msg, 0, $_FILES['_file']['name'], $new_fn) )
        {
            // Lock End
            $obj_lock->set_end(&$msg_lock);

            $val['success'] = true;
            $val['msg'] = $msg;
            $val['error']=1;
            if( $mode ) // 수동
                echo json_encode( $val );
            else  // 자동
                echo "DC00_error_1_, DC00_msg_" . iconv('utf-8','cp949',$msg) . "_";
            return;
        }

        // 11번가 추가상품 상품명 넣어주기
        // 11번가 추가상품의 경우 일반발주에서, 옵션공란시 상품명 복사 부분 때문에
        // 일반발주는 상품명 넣어주기 하지 않는다.
        if( $_SESSION[ADD_NAME_11] )
        {
            if( _DOMAIN_ == 'namsun' )
            {
                $query = "select shop_id, order_id
                            from orders 
                           where status=0 and 
                                 order_status=10 and
                                 shop_id % 100 = 50 and
                                 order_id_seq > 1 and
                                 substring(product_name,1,7)='┗(추가상품)' and
                                 options=''
                           group by shop_id, order_id
                           order by shop_id, order_id";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_assoc($result) )
                {
                    $org_order = array();
                    
                    $query_order = "select * from orders where shop_id=$data[shop_id] and order_id='$data[order_id]' order by order_id_seq";
                    $result_order = mysql_query($query_order, $connect);
                    while( $data_order = mysql_fetch_assoc($result_order) )
                    {
                        if( strpos($data_order[product_name], '┗(추가상품)') === false )
                        {
                            // 추가옵션 붙이기
                            if( $org_order["del_seq"] )
                            {
                                $query_up = "update orders
                                                set options = '$org_order[options]', 
                                                    code11  = $org_order[code11],
                                                    code12  = $org_order[code12],
                                                    code13  = $org_order[code13],
                                                    code14  = $org_order[code14],
                                                    code15  = $org_order[code15],
                                                    code16  = $org_order[code16],
                                                    code17  = $org_order[code17],
                                                    code18  = $org_order[code18],
                                                    code19  = $org_order[code19],
                                                    code20  = $org_order[code20],
                                                    code31  = $org_order[code31],
                                                    code32  = $org_order[code32],
                                                    code33  = $org_order[code33],
                                                    code34  = $org_order[code34],
                                                    code35  = $org_order[code35],
                                                    code36  = $org_order[code36],
                                                    code37  = $org_order[code37],
                                                    code38  = $org_order[code38],
                                                    code39  = $org_order[code39],
                                                    code40  = $org_order[code40]
                                              where seq = $org_order[seq]";
                                mysql_query($query_up, $connect);

                                $query_del = "delete from orders where seq in ($org_order[del_seq])";
                                mysql_query($query_del, $connect);
                            }

                            $org_order = array(
                                seq     => $data_order[seq],
                                options => $data_order[options],
                                code11  => $data_order[code11],
                                code12  => $data_order[code12],
                                code13  => $data_order[code13],
                                code14  => $data_order[code14],
                                code15  => $data_order[code15],
                                code16  => $data_order[code16],
                                code17  => $data_order[code17],
                                code18  => $data_order[code18],
                                code19  => $data_order[code19],
                                code20  => $data_order[code20],
                                code31  => $data_order[code31],
                                code32  => $data_order[code32],
                                code33  => $data_order[code33],
                                code34  => $data_order[code34],
                                code35  => $data_order[code35],
                                code36  => $data_order[code36],
                                code37  => $data_order[code37],
                                code38  => $data_order[code38],
                                code39  => $data_order[code39],
                                code40  => $data_order[code40],
                                del_seq => ''
                            );
                        }
                        else if( $org_order )
                        {
                            $org_order[options] .= ", $data_order[product_name]" . "-" . "$data_order[qty]" . "개";
                            $org_order[code11]  += $data_order[code11];
                            $org_order[code12]  += $data_order[code12];
                            $org_order[code13]  += $data_order[code13];
                            $org_order[code14]  += $data_order[code14];
                            $org_order[code15]  += $data_order[code15];
                            $org_order[code16]  += $data_order[code16];
                            $org_order[code17]  += $data_order[code17];
                            $org_order[code18]  += $data_order[code18];
                            $org_order[code19]  += $data_order[code19];
                            $org_order[code20]  += $data_order[code20];
                            $org_order[code31]  += $data_order[code31];
                            $org_order[code32]  += $data_order[code32];
                            $org_order[code33]  += $data_order[code33];
                            $org_order[code34]  += $data_order[code34];
                            $org_order[code35]  += $data_order[code35];
                            $org_order[code36]  += $data_order[code36];
                            $org_order[code37]  += $data_order[code37];
                            $org_order[code38]  += $data_order[code38];
                            $org_order[code39]  += $data_order[code39];
                            $org_order[code40]  += $data_order[code40];
                            $org_order[del_seq] .= ($org_order[del_seq] ? "," : "") . $data_order[seq];
                        }
                    }

                    // 추가옵션 붙이기
                    if( $org_order["seq"] && $org_order["del_seq"] )
                    {
                        $query_up = "update orders
                                        set options = '$org_order[options]', 
                                            code11  = $org_order[code11],
                                            code12  = $org_order[code12],
                                            code13  = $org_order[code13],
                                            code14  = $org_order[code14],
                                            code15  = $org_order[code15],
                                            code16  = $org_order[code16],
                                            code17  = $org_order[code17],
                                            code18  = $org_order[code18],
                                            code19  = $org_order[code19],
                                            code20  = $org_order[code20],
                                            code31  = $org_order[code31],
                                            code32  = $org_order[code32],
                                            code33  = $org_order[code33],
                                            code34  = $org_order[code34],
                                            code35  = $org_order[code35],
                                            code36  = $org_order[code36],
                                            code37  = $org_order[code37],
                                            code38  = $org_order[code38],
                                            code39  = $org_order[code39],
                                            code40  = $org_order[code40]
                                      where seq = $org_order[seq]";
                        mysql_query($query_up, $connect);
                        
                        $query_del = "delete from orders where seq in ($org_order[del_seq])";
                        mysql_query($query_del, $connect);
                    }
                }
            }
            // 일시적으로 추가상품 합치기 사용 안함. 2012-11-12
            else if( _DOMAIN_ == '_snowbuck' )
            {
                $query = "select shop_id, order_id
                            from orders 
                           where status=0 and 
                                 order_status=10 and
                                 shop_id = 10050 and
                                 order_id_seq > 1 and
                                 substring(product_name,1,7)='┗(추가상품)' and
                                 options=''
                           group by shop_id, order_id
                           order by shop_id, order_id";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_assoc($result) )
                {
                    $org_order_code = array();
                    $org_order_seq = array();
                    $org_order_base_options = "";
                    $org_order_add_options = "";
                    
                    $first_seq = 0;
                    $query_order = "select * from orders where shop_id=$data[shop_id] and order_id='$data[order_id]' order by order_id_seq";
                    $result_order = mysql_query($query_order, $connect);
                    while( $data_order = mysql_fetch_assoc($result_order) )
                    {
                        if( strpos($data_order[product_name], '┗(추가상품)') === false )
                        {
                            if( $first_seq == 0 )
                                $first_seq = $data_order[seq];
                            else
                                $org_order_seq[] = $data_order[seq];
                            $org_order_base_options .= $data_order[options] . ",";
                        }
                        else
                        {
                            $org_order_seq[] = $data_order[seq];
                            $org_order_add_options .=  "$data_order[product_name]" . "-" . "$data_order[qty]" . "개,";
                        }
                        
                        $org_order_code["code11"]  += $data_order[code11];
                        $org_order_code["code12"]  += $data_order[code12];
                        $org_order_code["code13"]  += $data_order[code13];
                        $org_order_code["code14"]  += $data_order[code14];
                        $org_order_code["code15"]  += $data_order[code15];
                        $org_order_code["code16"]  += $data_order[code16];
                        $org_order_code["code17"]  += $data_order[code17];
                        $org_order_code["code18"]  += $data_order[code18];
                        $org_order_code["code19"]  += $data_order[code19];
                        $org_order_code["code20"]  += $data_order[code20];
                        $org_order_code["code31"]  += $data_order[code31];
                        $org_order_code["code32"]  += $data_order[code32];
                        $org_order_code["code33"]  += $data_order[code33];
                        $org_order_code["code34"]  += $data_order[code34];
                        $org_order_code["code35"]  += $data_order[code35];
                        $org_order_code["code36"]  += $data_order[code36];
                        $org_order_code["code37"]  += $data_order[code37];
                        $org_order_code["code38"]  += $data_order[code38];
                        $org_order_code["code39"]  += $data_order[code39];
                        $org_order_code["code40"]  += $data_order[code40];
                        
                    }

                    $query_up = "update orders
                                    set options = '$org_order_base_options $org_order_add_options', 
                                        code11  = $org_order_code[code11],
                                        code12  = $org_order_code[code12],
                                        code13  = $org_order_code[code13],
                                        code14  = $org_order_code[code14],
                                        code15  = $org_order_code[code15],
                                        code16  = $org_order_code[code16],
                                        code17  = $org_order_code[code17],
                                        code18  = $org_order_code[code18],
                                        code19  = $org_order_code[code19],
                                        code20  = $org_order_code[code20],
                                        code31  = $org_order_code[code31],
                                        code32  = $org_order_code[code32],
                                        code33  = $org_order_code[code33],
                                        code34  = $org_order_code[code34],
                                        code35  = $org_order_code[code35],
                                        code36  = $org_order_code[code36],
                                        code37  = $org_order_code[code37],
                                        code38  = $org_order_code[code38],
                                        code39  = $org_order_code[code39],
                                        code40  = $org_order_code[code40]
                                  where seq = $first_seq";
                    mysql_query($query_up, $connect);
                    
                    $query_del = "delete from orders where seq in (" . implode(",", $org_order_seq) . ")";
                    mysql_query($query_del, $connect);
                }
            }
            else
            {
                $arr_11 = array();
                $query = "select seq, order_id, order_id_seq, product_name
                            from orders 
                           where status=0 and 
                                 order_status=10 and
                                 shop_id % 100 = 50 and
                                 order_id_seq > 1 and
                                 substring(product_name,1,7)='┗(추가상품)' and
                                 options=''";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_assoc($result) )
                {
                    $query_name = "select product_name 
                                     from orders 
                                    where status=0 and 
                                          order_status=10 and
                                          shop_id % 100 = 50 and
                                          order_id = '$data[order_id]' and 
                                          order_id_seq < $data[order_id_seq] and
                                          substring(product_name,1,7)<>'┗(추가상품)'
                                    order by order_id_seq desc limit 1";
                    $result_name = mysql_query($query_name, $connect);
                    
                    if( $data_name = mysql_fetch_assoc($result_name) )
                    {
                        $arr_11[] = array(
                            'seq'    => $data[seq],
                            'name'   => $data_name[product_name],
                            'options'=> $data[product_name]
                        );
                    }
                }
                
                // 실시간으로 상품명 update 할 경우, 옵션이 두개인 주문에서 옵션중복 발생
                // 그래서, 배열로 저장후 update는 나중에 한번에 함.
                $cnt = count($arr_11);
                for($i=0; $i<$cnt; $i++)
                {
                    $seq = $arr_11[$i][seq];
                    $name = $arr_11[$i][name];
                    $options = $arr_11[$i][options];
                    $query = "update orders set product_name='$name', options='$options' where seq=$seq";
debug("11번가 추가상품3 : " . $query);
                    mysql_query($query, $connect);
                }
            }
        }
        
        // 인터파크 추가상품 상품명 넣어주기 - memorette 제외
        $arr_inter = array();
        $query = "select seq, order_id, order_id_seq, product_name
                    from orders 
                   where status=0 and 
                         order_status=10 and
                         shop_id % 100 = 6 and
                         order_id_seq > 1 and
                         substring(product_name,1,1)='+'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_name = "select product_name 
                             from orders 
                            where status=0 and 
                                  order_status=10 and
                                  shop_id % 100 = 6 and
                                  order_id = '$data[order_id]' and 
                                  order_id_seq < $data[order_id_seq] and
                                  substring(product_name,1,1)<>'+'
                            order by order_id_seq desc limit 1";
            $result_name = mysql_query($query_name, $connect);
            
            if( $data_name = mysql_fetch_assoc($result_name) )
            {
                $arr_inter[] = array(
                    'seq'    => $data[seq],
                    'name'   => $data_name[product_name] . " " . $data[product_name]
                );
            }
        }

        if( _DOMAIN_ != 'cencorpkr' )
        {
            // 네이버샵n 추가구성상품 상품명 처리
            $arr_shopn = array();
            $query = "select seq, order_id, order_id_seq, shop_id, product_name, shop_product_id
                        from orders 
                       where status=0 and 
                             order_status=10 and
                             shop_id % 100 = 51 and
                             code2='추가구성상품' and
                             options=''";
    
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_name = "select product_name 
                                 from orders 
                                where status=0 and 
                                      order_status=10 and
                                      shop_id = '$data[shop_id]' and
                                      order_id = '$data[order_id]' and 
                                      shop_product_id = '$data[shop_product_id]' and
                                      code2 <> '추가구성상품'
                                order by order_id_seq limit 1";
                $result_name = mysql_query($query_name, $connect);
                
                if( $data_name = mysql_fetch_assoc($result_name) )
                {
                    $arr_shopn[] = array(
                        'seq'    => $data[seq],
                        'name'   => $data_name[product_name],
                        'options'=> $data[product_name]
                    );
                }
                else
                {
                    $arr_shopn[] = array(
                        'seq'    => $data[seq],
                        'name'   => $data[product_name],
                        'options'=> $data[product_name]
                    );
                }
            }
        }
        
        // 실시간으로 상품명 update 할 경우, 옵션이 두개인 주문에서 옵션중복 발생
        // 그래서, 배열로 저장후 update는 나중에 한번에 함.
        $cnt = count($arr_11);
        for($i=0; $i<$cnt; $i++)
        {
            $seq = $arr_11[$i][seq];
            $name = $arr_11[$i][name];
            $options = $arr_11[$i][options];
            $query = "update orders set product_name='$name', options='$options' where seq=$seq";
            mysql_query($query, $connect);
        }

        // 실시간으로 상품명 update 할 경우, 옵션이 두개인 주문에서 옵션중복 발생
        // 그래서, 배열로 저장후 update는 나중에 한번에 함. 
        // 2014-10-02 장경희. memorette 제외
        if( _DOMAIN_ != 'memorette' )
        {
            $cnt = count($arr_inter);
            for($i=0; $i<$cnt; $i++)
            {
                $name = $arr_inter[$i][name];
                $seq = $arr_inter[$i][seq];
                $query = "update orders set product_name = '$name' where seq=$seq";
                mysql_query($query, $connect);
            }
        }
        
        // 실시간으로 상품명 update 할 경우, 옵션이 두개인 주문에서 옵션중복 발생
        // 그래서, 배열로 저장후 update는 나중에 한번에 함.
        $cnt = count($arr_shopn);
        for($i=0; $i<$cnt; $i++)
        {
            $seq = $arr_shopn[$i][seq];
            $name = $arr_shopn[$i][name];
            $options = $arr_shopn[$i][options];
            $query = "update orders set product_name='$name', options='$options' where seq=$seq";
            mysql_query($query, $connect);
        }

        // Lock End
        $obj_lock->set_end(&$msg_lock);

        $val['success'] = true;
        $val['cnt_total'] = $cnt_total;
        $val['cnt_new'] = $cnt_new;
        
        // 현재 발주 상태 수량
        // 2014-12-12 장경희
        $query_balju_cnt = "select count(*) cnt from orders where shop_id=$shop_id and status=0";
        $result_balju_cnt = mysql_query($query_balju_cnt, $connect);
        $data_balju_cnt = mysql_fetch_assoc($result_balju_cnt);
        $val['cnt_balju'] = $data_balju_cnt[cnt];
        
        $val['error']=0;
        if( $mode ) // 수동
            echo json_encode( $val );
        else  // 자동
            echo "DC00_error_0_ DC00_msg_" . $val['cnt_balju'] . " 개 발주하였습니다." . "_";
    }

    //////////////////////////////////////////////////////////
    // [발주] 선착불 키워드 정보를 가져온다.
    function get_transwho_info()
    {
        global $connect, $shop_id, $select;

        $val['list'] = array();

        $query = "select * from shop_transkey where shop_id=$shop_id order by seq";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $val['list'][] = array(
                space    => $data[space],
                keyword  => $data[keyword],
                transwho => $data[transwho]
            );
        }
        $val['error'] = 0;
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [발주] 판매처 발주 포멧 헤더설정 정보를 가져온다.
    function get_header()
    {
        global $connect, $shop_id;

        if( $_SESSION[LOGIN_LEVEL] == 9 )
        {
            if( $shop_id % 100 == 72 )
                $match_code_msg = "매칭코드<br><span style='color:red'>*상품연동 자동매칭 설정<br>cafe24 : 상품코드</span>";
            else if( $shop_id % 100 == 68 )
                $match_code_msg = "매칭코드<br><span style='color:red'>*상품연동 자동매칭 설정<br>makeshop : link_id</span>";
            else
                $match_code_msg = "매칭코드";
                
            // AK몰
            if( $shop_id % 100 == 42 )
            {
                $order_type_msg = "주문구분<br><span style='color:red'>*필수 필드 [기수령여부]<br>값이 N 이어야 발주</span>";
                $order_type2_msg = "주문구분2<br><span style='color:red'>*필수 필드 [배송상태] 또는 [수령상태]<br>값이 '주문' 또는 '추가주문' 이어야 발주</span>";
            }
            // 롯데닷컴
            else if( $shop_id % 100 == 9 )
            {
                $order_type_msg = "주문구분<br><span style='color:red'>*필수 필드 [배송구분]<br>값이 '주문' 이어야 발주<br>값이 '맞교환', '교환주문', '주문', '추가'이면 환경설정</span>";
                $order_type2_msg = "주문구분2";
            }
            else
            {
                $order_type_msg = "주문구분";
                $order_type2_msg = "주문구분2";
            }
            
            $shop_pid_msg = "상품코드 <img src='" . _IMG_SERVER_ . "/images/abs.gif'><br><span style='color:red'>상품코드 없는 발주서는 pimz_code로 설정<br>상품코드는 최대 길이가 40자이므로<br>상품명이나 옵션 설정시 짤릴수 있기때문임</span>";
        }
        else
        {
            $match_code_msg = "매칭코드";
            $order_type_msg = "주문구분";
            $order_type2_msg = "주문구분2";
            $shop_pid_msg = "상품코드 <img src='" . _IMG_SERVER_ . "/images/abs.gif'>";
        }

        $val['list'] = array(
            array( field_id => "order_id"       , field_name => "주문번호 <img src='" . _IMG_SERVER_ . "/images/abs.gif'>"       , shop_header => "" ),
            array( field_id => "order_id_seq"   , field_name => "주문상세번호"   , shop_header => "" ),
            array( field_id => "order_type"     , field_name => $order_type_msg  , shop_header => "" ),
            array( field_id => "order_type2"    , field_name => $order_type2_msg , shop_header => "" ),
            array( field_id => "shop_product_id", field_name => $shop_pid_msg    , shop_header => "" ),
            array( field_id => "product_name"   , field_name => "상품명"         , shop_header => "" ),
            array( field_id => "options"        , field_name => "옵션"           , shop_header => "" ),
            array( field_id => "qty"            , field_name => "수량 <img src='" . _IMG_SERVER_ . "/images/abs.gif'>"           , shop_header => "" ),
            array( field_id => "order_date"     , field_name => "주문일"         , shop_header => "" ),
            array( field_id => "order_time"     , field_name => "주문시간"       , shop_header => "" ),
            array( field_id => "order_name"     , field_name => "주문자명"       , shop_header => "" ),
            array( field_id => "order_tel"      , field_name => "주문자전화번호" , shop_header => "" ),
            array( field_id => "order_mobile"   , field_name => "주문자핸드폰"   , shop_header => "" ),
            array( field_id => "order_email"    , field_name => "주문자이메일"   , shop_header => "" ),
            array( field_id => "order_zip"      , field_name => "주문자우편번호" , shop_header => "" ),
            array( field_id => "order_address"  , field_name => "주문자주소"     , shop_header => "" ),
            array( field_id => "recv_name"      , field_name => "수령자명 <img src='" . _IMG_SERVER_ . "/images/abs.gif'>"       , shop_header => "" ),
            array( field_id => "recv_tel"       , field_name => "수령자전화번호" , shop_header => "" ),
            array( field_id => "recv_mobile"    , field_name => "수령자핸드폰"   , shop_header => "" ),
            array( field_id => "recv_email"     , field_name => "수령자이메일"   , shop_header => "" ),
            array( field_id => "recv_zip"       , field_name => "수령자우편번호" , shop_header => "" ),
            array( field_id => "recv_address"   , field_name => "수령자주소"     , shop_header => "" ),
            array( field_id => "memo"           , field_name => "메모"           , shop_header => "" ),
            array( field_id => "short_mobile"   , field_name => "핸드폰뒷자리"   , shop_header => "" ),
            array( field_id => "cust_id"        , field_name => "구매자ID"       , shop_header => "" ),
            array( field_id => "amount"         , field_name => "판매금액"       , shop_header => "" ),
            array( field_id => "supply_price"   , field_name => "정산금액"       , shop_header => "" ),
            array( field_id => "prepay_price"   , field_name => "배송비금액"     , shop_header => "" ),
            array( field_id => "org_trans_who"  , field_name => "배송비구분"     , shop_header => "" ),
            array( field_id => "pay_type"       , field_name => "결제수단"       , shop_header => "" ),
            array( field_id => "match_code"     , field_name => $match_code_msg  , shop_header => "" ),
            array( field_id => "code1"          , field_name => "코드1 [code1]"  , shop_header => "" ),
            array( field_id => "code2"          , field_name => "코드2 [code2]"  , shop_header => "" ),
            array( field_id => "code3"          , field_name => "코드3 [code3]"  , shop_header => "" ),
            array( field_id => "code4"          , field_name => "코드4 [code4]"  , shop_header => "" ),
            array( field_id => "code5"          , field_name => "코드5 [code5]"  , shop_header => "" ),
            array( field_id => "code6"          , field_name => "코드6 [code6]"  , shop_header => "" ),
            array( field_id => "code7"          , field_name => "코드7 [code7]"  , shop_header => "" ),
            array( field_id => "code8"          , field_name => "코드8 [code8]"  , shop_header => "" ),
            array( field_id => "code9"          , field_name => "코드9 [code9]"  , shop_header => "" ),
            array( field_id => "code10"         , field_name => "코드10 [code10]", shop_header => "" ),
            array( field_id => "code21"         , field_name => "코드11 [code21]", shop_header => "" ),
            array( field_id => "code22"         , field_name => "코드12 [code22]", shop_header => "" ),
            array( field_id => "code23"         , field_name => "코드13 [code23]", shop_header => "" ),
            array( field_id => "code24"         , field_name => "코드14 [code24]", shop_header => "" ),
            array( field_id => "code25"         , field_name => "코드15 [code25]", shop_header => "" ),
            array( field_id => "code26"         , field_name => "코드16 [code26]", shop_header => "" ),
            array( field_id => "code27"         , field_name => "코드17 [code27]", shop_header => "" ),
            array( field_id => "code28"         , field_name => "코드18 [code28]", shop_header => "" ),
            array( field_id => "code29"         , field_name => "코드19 [code29]", shop_header => "" ),
            array( field_id => "code30"         , field_name => "코드20 [code30]", shop_header => "" ),
            array( field_id => "code11"         , field_name => "정산코드1 [code11]"  , shop_header => "" ),
            array( field_id => "code12"         , field_name => "정산코드2 [code12]"  , shop_header => "" ),
            array( field_id => "code13"         , field_name => "정산코드3 [code13]"  , shop_header => "" ),
            array( field_id => "code14"         , field_name => "정산코드4 [code14]"  , shop_header => "" ),
            array( field_id => "code15"         , field_name => "정산코드5 [code15]"  , shop_header => "" ),
            array( field_id => "code16"         , field_name => "정산코드6 [code16]"  , shop_header => "" ),
            array( field_id => "code17"         , field_name => "정산코드7 [code17]"  , shop_header => "" ),
            array( field_id => "code18"         , field_name => "정산코드8 [code18]"  , shop_header => "" ),
            array( field_id => "code19"         , field_name => "정산코드9 [code19]"  , shop_header => "" ),
            array( field_id => "code20"         , field_name => "정산코드10 [code20]" , shop_header => "" ),
            array( field_id => "code31"         , field_name => "정산코드11 [code31]" , shop_header => "" ),
            array( field_id => "code32"         , field_name => "정산코드12 [code32]" , shop_header => "" ),
            array( field_id => "code33"         , field_name => "정산코드13 [code33]" , shop_header => "" ),
            array( field_id => "code34"         , field_name => "정산코드14 [code34]" , shop_header => "" ),
            array( field_id => "code35"         , field_name => "정산코드15 [code35]" , shop_header => "" ),
            array( field_id => "code36"         , field_name => "정산코드16 [code36]" , shop_header => "" ),
            array( field_id => "code37"         , field_name => "정산코드17 [code37]" , shop_header => "" ),
            array( field_id => "code38"         , field_name => "정산코드18 [code38]" , shop_header => "" ),
            array( field_id => "code39"         , field_name => "정산코드19 [code39]" , shop_header => "" ),
            array( field_id => "code40"         , field_name => "정산코드20 [code40]" , shop_header => "" )
        );

        $real_arr = array();
        $query = "select * from shopheader where shop_id=$shop_id";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
            $real_arr[$data[field_id]] = $data[shop_header];

        for( $i=0; $i<count($val['list']); $i++)
        {
            $field_id = $val['list'][$i]['field_id'];
            $val['list'][$i]['shop_header'] = $real_arr[$field_id];
        }
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [발주] 판매처 발주 포멧 설정을 추가한다.
    function insert_header_format()
    {
        global $connect, $shop_id, $base_h, $type, $shop_h;

        $val = array();
        $base_arr = explode('|', $base_h);
        // 기존에 있는 헤더인지 확인한다.
        $query = "select seq from shopheader 
                   where shop_id    = '$shop_id' and 
                         field_id   = '$base_arr[0]'";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows($result) > 0 )
            $val['error'] = 1;
        else
        {
            // 판매처 헤더 이름이 중복됐는지 확인
            $query = "select seq from shopheader 
                       where shop_id     = '$shop_id' and 
                             shop_header = '$shop_h' ";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows($result) > 0 )
                $val['error'] = 2;
            else
            {
                $query = "insert shopheader
                             set shop_id     = '$shop_id',
                                 field_id    = '$base_arr[0]',
                                 field_name  = '$base_arr[1]',
                                 shop_header = '$shop_h',
                                 abs         = '$type' ";
debug("insert header:".$query);
                if( mysql_query($query, $connect) )
                    $val['error'] = 0;
                else
                    $val['error'] = 3;
            }
        }
        
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 판매처 발주 포멧 설정을 삭제한다.
    function delete_header_format()
    {
        global $connect, $shop_id, $base_h;

        $val = array();
        $base_arr = explode('|', $base_h);
        // 기존에 있는 헤더인지 확인한다.
        $query = "delete from shopheader
                   where shop_id    = '$shop_id' and 
                         field_id   = '$base_arr[0]'";
debug("delete header:".$query);
        if( mysql_query( $query, $connect ) )
            $val['error'] = 0;
        else
            $val['error'] = 1;

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 판매처 발주 포멧 설정을 사용자 설정값으로 저장한다.
    function save_header_format()
    {
        global $connect, $shop_id, $space, $befores, $afters, $fields, $headers;
debug("save header");
        // 선착불정보 삭제
        $query = "delete from shop_transkey where shop_id=$shop_id";
        mysql_query( $query, $connect );

        // 선불 배열 저장
        $bArr = explode("\\", $befores);
        $beforeArr = array();
        foreach( $bArr as $before )
        {
            if( $before == '[' || $before == '"]' || $before == '",' )  continue;
            $beforeKey = substr(  $before, 1 );
            $query = "insert shop_transkey set shop_id=$shop_id, space=0, keyword='$beforeKey', transwho=0";
            mysql_query( $query, $connect );
        }

        // 착불 배열 저장
        $aArr = explode("\\", $afters);
        $afterArr = array();
        foreach( $aArr as $after )
        {
            if( $after == '[' || $after == '"]' || $after == '",' )  continue;
            $afterKey = substr(  $after, 1 );
            $query = "insert shop_transkey set shop_id=$shop_id, space=0, keyword='$afterKey', transwho=1";
            mysql_query( $query, $connect );
        }
        
        // 공백 설정 저장
        $query = "insert shop_transkey set shop_id=$shop_id, space=1, keyword='', transwho=$space";
        mysql_query( $query, $connect );

        // json decode 전에 반드시 stripslashes
        $fArr = json_decode( stripslashes($fields) );
        $hArr = json_decode( stripslashes($headers) );
        for( $i=0; $i<count($hArr); $i++ )
        {
            $query = "select * from shopheader where shop_id=$shop_id and field_id='$fArr[$i]'";
            $result = mysql_query($query, $connect);
            
            // 기존에 정의되어 있는 경우
            if( mysql_num_rows($result) )
            {
                $data = mysql_fetch_assoc($result);

                // 헤더 값 있음
                if( $hArr[$i] )
                {
                    // 같으면 넘어감
                    if( $hArr[$i] == $data[shop_header] )
                        continue;
                    // 다르면 update
                    else
                    {
                        $query = "update shopheader set shop_header='" . addslashes( $hArr[$i] ) . "' where shop_id=$shop_id and field_id='$fArr[$i]'";
                        mysql_query( $query, $connect );
                    }
                }
                // 헤더 값 없음
                else
                {
                    // 레코드 삭제
                    $query = "delete from shopheader where shop_id=$shop_id and field_id='$fArr[$i]'";
                    mysql_query($query, $connect);
                }
            }
            // 기존에 레코드 없는 경우
            else
            {
                // 헤더 값 있으면 insert
                if( $hArr[$i] )
                {
                    $query = "insert shopheader set shop_id=$shop_id, field_id='$fArr[$i]', shop_header='$hArr[$i]', abs=1, idx=0";
                    mysql_query($query, $connect);
                }
            }
debug( "헤더 변경 : " . $query );
        }

/*
        // 변경값을 db에 저장
        $query = "select * from shopheader where shop_id=$shop_id order by seq"; // order by seq는 매우 중요.. 나중에 변경된 값을 저장할때 이 순서대로함.
        $result = mysql_query( $query, $connect );
        $i = 0;
        while( $data = mysql_fetch_array( $result ) )
        {
            $query_update = "update shopheader set shop_header='$headerArr[$i]' where shop_id=$data[shop_id] and field_name='$data[field_name]'";
            mysql_query( $query_update, $connect );
            $i++;
        }
*/
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 판매처 발주 포멧 설정을 기본값으로 복원한다.
    function restore_header_format()
    {
        global $connect, $sys_connect, $shop_id;
        $sys_connect = sys_db_connect();

        $shop_code = $shop_id % 100;

        // 업체에 저장된 선착불 키워드 정보 삭제
        $query = "delete from shop_transkey where shop_id=$shop_id";
        mysql_query( $query, $connect );
        
        // 시스템 선착불 키워드 테이블에서 정보를 가져옴
        $query = "select * from sys_shop_transkey where shop_id=$shop_code order by seq";
        $result = mysql_query( $query, $sys_connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $query_insert = "insert shop_transkey 
                                set shop_id  = $shop_id, 
                                    space    = '$data[space]', 
                                    keyword  = '$data[keyword]',
                                    transwho = '$data[transwho]'";
            mysql_query( $query_insert, $connect );
        }

        // 업체에 저장된 헤더정보 삭제
        $query = "delete from shopheader where shop_id=$shop_id";
        mysql_query( $query, $connect );
        
        // 시스템 헤더 테이블에서 정보를 가져옴
        $query = "select * from sys_shopheader where shop_id=$shop_code order by seq";
        $result = mysql_query( $query, $sys_connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $query_insert = "insert shopheader 
                                set shop_id     = $shop_id,
                                    field_id    = '$data[field_id]', 
                                    field_name  = '$data[field_name]',
                                    shop_header = '$data[shop_header]',
                                    abs         = $data[abs]";
            mysql_query( $query_insert, $connect );
            
        }
        $val['error'] = 0;
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [발주] 전체 신규 발주수량을 구한다. 그리드 위의 값 표시
    function get_orders_new()
    {
        global $connect;
        $query = "select count(*) as cnt from orders where status=0 and order_status=10";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );

        $val['cnt_new_all'] = $data[cnt];
        $val['error'] = 0;
        $val['service_stop'] = $this->check_pay_date();
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 전체 발주중인 발주수량을 구한다. 그래프 그리기 위한 값
    function get_orders_count()
    {
        global $connect;
        $query = "select shop_id, count(*) as cnt from orders where status=0 group by shop_id";
        $result = mysql_query( $query, $connect );
        $val['list'] = array();
        while( $data = mysql_fetch_array( $result ) )
        {
            $val['list']["$data[shop_id]"] = $data[cnt];
        }
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 발주중인 주문을 삭제한다.
    function del_orderings()
    {
        global $connect, $shop_id;

        // Lock Check
        $obj_lock = new class_lock(508);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $query = "select * from orders where status=0 and shop_id=$shop_id";
        $result = mysql_query( $query, $connect );
        $val = array();
        $cs_exist = false;
        $cnt = 0;
        while( $data = mysql_fetch_array( $result ) )
        {
            $query_cs = "select * from csinfo where order_seq = $data[seq]";
            $result_cs = mysql_query( $query_cs, $connect );
            // cs 접수된 주문 있음
            if( mysql_num_rows( $result_cs ) > 0 )
            {
                $cs_exist = true;
                $val['list'][] = array(
                    $data[seq],
                    $data[collect_date],
                    $data[recv_name],
                    $data[product_name],
                    $data[options]
                );
            }
            else
            {
                // orders
                $query_del = "delete from orders where seq=$data[seq]";
                mysql_query( $query_del, $connect );
                // order_products
                $query_del = "delete from order_products where order_seq=$data[seq]";
                mysql_query( $query_del, $connect );
                $cnt++;
            }
        }
        if( $cs_exist )       $val['error'] = 1;  // cs 접수된 주문 있음.
        else if( $cnt == 0 )  $val['error'] = 2;  // 삭제할 주문 없음
        else                  $val['error'] = 0;  // cs 접수된 주문 없음.

        // 삭제된 주문이 있을 경우, 발주 이력에 삭제 내용을 저장한다.
        if( $cnt > 0 )
        {
            $query = "select seq from order_process_log order by seq desc limit 1";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows( $result ) > 0 )
            {
                $data = mysql_fetch_array( $result );
                $orders_code = $data[seq] + 1;
            }
            else
                $orders_code = 1;
    
            // 발주 작업 log 기록
            $query = "insert order_process_log 
                         set seq       = $orders_code, 
                             shop_id   = $shop_id, 
                             work_date = now(),
                             cnt_total = $cnt * -1,
                             cnt_new   = 0,
                             balju_ok  = 1,
                             worker    = '$_SESSION[LOGIN_NAME]'";
                             
            if( !mysql_query( $query, $connect ) )
            {
                $val['msg'] = "발주 삭제 이력을 생성하는데 실패했습니다. 다시 시도해보시기 바랍니다.<br><br>
                               이 오류가 계속 발생되면 고객센터로 문의바랍니다.";
                $val['error'] = 3;
            }
        }
                
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] 발주 중인 전체 주문을 삭제한다.
    function del_all_orderings()
    {
        global $connect;

        $val = array();
        $cs_exist = false;
        $cnt_total = 0;
        
        // Lock Check
        $obj_lock = new class_lock(507);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 샵 정보를 가져온다.
        $query_shop = "select * from shopinfo";
        $result_shop = mysql_query( $query_shop, $connect );
        while( $data_shop = mysql_fetch_array( $result_shop ) )
        {
            $query = "select * from orders where status=0 and shop_id=$data_shop[shop_id]";
            $result = mysql_query( $query, $connect );
            $cnt = 0;
            while( $data = mysql_fetch_array( $result ) )
            {
                $query_cs = "select * from csinfo where order_seq = $data[seq]";
                $result_cs = mysql_query( $query_cs, $connect );
                // cs 접수된 주문 있음
                if( mysql_num_rows( $result_cs ) > 0 )
                {
                    $cs_exist = true;
                    $val['list'][] = array(
                        $data[seq],
                        $data[collect_date],
                        $data[recv_name],
                        $data[product_name],
                        $data[options]
                    );
                }
                else
                {
                    // orders
                    $query_del = "delete from orders where seq=$data[seq]";
                    mysql_query( $query_del, $connect );
                    // order_products
                    $query_del = "delete from order_products where order_seq=$data[seq]";
                    mysql_query( $query_del, $connect );
                    $cnt++;
                }
            }
            $cnt_total += $cnt;
            
            // 삭제된 주문이 있을 경우, 발주 이력에 삭제 내용을 저장한다.
            if( $cnt > 0 )
            {
                $query = "select seq from order_process_log order by seq desc limit 1";
                $result = mysql_query( $query, $connect );
                if( mysql_num_rows( $result ) > 0 )
                {
                    $data = mysql_fetch_array( $result );
                    $orders_code = $data[seq] + 1;
                }
                else
                    $orders_code = 1;
        
                // 발주 작업 log 기록
                $query = "insert order_process_log 
                             set seq       = $orders_code, 
                                 shop_id   = $data_shop[shop_id], 
                                 work_date = now(),
                                 cnt_total = $cnt * -1,
                                 cnt_new   = 0,
                                 balju_ok  = 1,
                                 worker    = '$_SESSION[LOGIN_NAME]'";
                mysql_query( $query, $connect );
            }
        }            
        if( $cs_exist )             $val['error'] = 1;  // cs 접수된 주문 있음.
        else if( $cnt_total == 0 )  $val['error'] = 2;  // 삭제할 주문 없음
        else                        $val['error'] = 0;  // cs 접수된 주문 없음.

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] C/S 있는 주문을 삭제한다.
    function delete_cs_order()
    {
        global $connect, $seq_str;
        
        $val = array();

        // Lock Check
        $obj_lock = new class_lock(509);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 선택된 주문 삭제 - orders
        $query = "delete from orders where seq in $seq_str";
        mysql_query( $query, $connect );
        // 선택된 주문 삭제 - order_products
        $query = "delete from order_products where order_seq in $seq_str";
        mysql_query( $query, $connect );

        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [발주] CS 이력을 가져온다.
    function get_cshistory()
    {
        global $connect, $seq;
        
        $query = "select * from csinfo where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $val['cs_info'] = '';
        $val['query'] = $query;
    	while ( $data = mysql_fetch_array ( $result ) )
    	{
    	    $msg = "[" . $data[input_date] . " " . $data[input_time] . "] ". $data[writer] . " / " . $data[content] . "\n" ;
    	    $val['cs_info'] .=   $msg;
    	}
    	echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [발주] 메모로 이동. 메모없는 주문을 order_status=20으로 바꾸고, 메모 있는 주문 목록을 돌려준다.
    //
    function make_memo()
    {
        global $connect;

        // 2014-01-22 박팀장. 일단 미사용
        if( _DOMAIN_ != 'box4u' )
        {
            // 메모가 없는 주문은 order_status=20 으로 한다.
            $query = "update orders set order_status=20 where status=0 and order_status=10 and memo=''";
            mysql_query( $query, $connect );
        }
        
        // 나머지 주문 목록은 order_status=15 으로 한다.
        $query = "update orders set order_status=15 where status=0 and order_status=10";
        mysql_query( $query, $connect );

        // 나머지 주문 목록의 seq 배열을 문자열로 저장한다.
        $arr_seq = array();
        $query = "select seq from orders where status=0 and order_status=15 order by seq";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array($result) )
            $arr_seq[] = $data[seq];
        $memo_seq = join(',',$arr_seq);

        $val['memo_seq'] = $memo_seq;
        echo json_encode( $val );
    }            

    //////////////////////////////////////////////////////////
    // [메모] 메모 목록을 가져온다.
    function get_memo()
    {
        global $connect, $memo_seq, $start, $limit;

        $val['list'] = array();
        
        // 메모확인할 주문이 없을 경우
        if( $memo_seq == '' )
        {
            $val['count'] = 0;
            $val['error']= 0;
            echo json_encode( $val );
            return;
        }            
        
        $query = "select * from orders where status=0 and order_status=15 and seq in ($memo_seq) order by qty desc, memo limit $start, $limit";
        $result = mysql_query( $query, $connect );
        $i=0;
        while( $data = mysql_fetch_array( $result ) )
        {
            // 2014-01-22 박팀장. 일단 미사용
            if( _DOMAIN_ == 'box4u' || _DOMAIN_ == 'jkhdev' )
            {
                /*
                if( $data[code30] )
                    $box_str = "<input type=checkbox name=keep_pack onclick='javascript:click_keep_pack(this, $data[seq])' checked /><label>합포유지(O)</label> | ";
                else
                    $box_str = "<input type=checkbox name=keep_pack onclick='javascript:click_keep_pack(this, $data[seq])' /><label>합포유지(X)</label> | ";
                */
                $box_str = "$data[order_name] / $data[recv_name] | ";
            }
            else 
                $box_str = "";

            $i++;
            $val['list'][] = array(
                no           => $i,
                seq          => $data[seq],
                shop_id      => $data[shop_id],
                shop_name    => class_shop::get_shop_name($data[shop_id]),
                product_name => $data[product_name],
                options      => $data[options],
                qty          => $data[qty],
                memo         => $box_str . $data[memo],
                order_id     => $data[order_id],
                recv_name    => $data[recv_name],
                recv_tel     => $data[recv_tel],
                recv_mobile  => $data[recv_mobile],
                recv_address => $data[recv_address],
                matching     => $data[memo_check]
            );
        }
        $query = "select count(*) as cnt_all from orders where status=0 and order_status=15 and seq in ($memo_seq)";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        
        $val['count'] = $data[cnt_all];
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [메모] 자동/수동 매칭 설정한다.
    function set_memo_check()
    {
        global $connect, $seq, $val_check;
        
        $val = array();

        // Lock Check
        $obj_lock = new class_lock(511);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 해당 주문이 이미 메모확인 처리된 주문인지 확인한다.
        $query = "select status, order_status from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) > 0 )
        {
            $data = mysql_fetch_array( $result );
            // 메모 확인된 주문 아니면, 자동/수동 매칭 여부를 변경한다.
            if( $data[status] == 0 && $data[order_status] == 15 )
            {
                $query = "update orders set memo_check=$val_check where seq=$seq";
                if( mysql_query( $query, $connect ) )
                    $val['error'] = 0;
                else
                    $val['error'] = 1;
            }
            // 이미 메모 확인된 주문이면 에러
            else
                $val['error'] = 2;
        }
        // 주문이 삭제되었으면 에러
        else
            $val['error'] = 3;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [메모] 자동/수동 매칭 전체 설정한다.
    function set_memo_check_all()
    {
        global $connect, $memo_seq, $val_check;
        
        $val = array();
        
        // Lock Check
        $obj_lock = new class_lock(512);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // memo_seq 가 빈 문자열이면 리턴
        if( $memo_seq == '' )
            $val['error'] = 0;
        else
        {
            // memo_seq 중에서 이미 메모 확인된 주문이 있는지 확인한다.
            $query = "select seq from orders where order_status <> 15 and seq in ($memo_seq)";
            $result = mysql_query( $query, $connect );
            // 있으면 오류
            if( mysql_num_rows( $result ) > 0 )
                $val['error'] = 1;
            // 없으면 전체설정 실행
            else
            {
                $query = "update orders set memo_check=$val_check where status=0 and order_status=15 and seq in ($memo_seq)";
                if( mysql_query( $query, $connect ) )
                    $val['error'] = 0;
                else
                    $val['error'] = 2;
            }
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [메모] 메모 다시보기. order_status=30 -> 20
    //
    function set_memo_back()
    {
        global $connect;

        // Lock Check
        $obj_lock = new class_lock(506);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }
        
        $val = array();
        // 현재 매칭중인 주문의 order_products를 삭제
        $query = "delete b from orders a, order_products b where a.seq=b.order_seq and a.status=0 and a.order_status=30";
        mysql_query($query, $connect);
        
        // 현재 메모확인 또는 매칭중인 주문의 order_status를 10으로 변경
        $query = "update orders set order_status=10 where status=0 and order_status in (20,30)";
        mysql_query($query, $connect);

        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [메모] 메모확인한다. order_status=20
    //
    function confirm_memo()
    {
        global $connect, $memo_seq;

        // Lock Check
        $obj_lock = new class_lock(501);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $val = array();
        // 메모 주문을 업데이트한다.
        if( $memo_seq != '' )
        {
            $query = "update orders set order_status=20 where status=0 and order_status=15 and seq in ($memo_seq)";
            if( mysql_query( $query, $connect ) === false )
                $val['error'] = 1;
            else
                $val['error'] = 0;
        }
        else
            $val['error'] = 0;

        // Lock End
        $obj_lock = new class_lock(501);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        if( $_SESSION[BASIC_VERSION] == 1 && $_SESSION[STOCK_MANAGE_USE] == 0 )
        {
            $query_match = "select product_id from products where is_delete=0 limit 1";
            $result_match = mysql_query( $query_match, $connect );
            if( mysql_num_rows( $result_match ) == 0 )
            {
                $val['error'] = 1;
                $val['msg'] = "등록된 상품이 없습니다. 기본상품을 등록하세요.";
            }
        }
     
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [매칭] 매칭정보를 생성한다.
    function set_match_auto()
    {
        // 1. orders에서 status=0, order_status=20(발주중이고, 메모정리된) 주문을 가져온다.
        // 2. 가져온 주문 중에, 추가옵션 주문 설정된 주문은 설정대로 찢는다. 메모 있는 주문은 찢지 않는다.
        // 3. 찢은 주문 중에 매칭정보 있으면 매칭한다.
        // 4. 찢지 않은 주문 중에 매칭정보 있으면 매칭한다.
        // 5. 찢은 주문과 찢지 않은 주문 중에 매칭정보 없는 주문 목록을 보여준다.

        global $connect, $start, $limit;

        // Lock Check
        $obj_lock = new class_lock(501);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 메모 확인 중인 주문은 전부 매칭단계로...
        $query = "update orders set order_status=20 where status=0 and order_status=15";
        if( mysql_query( $query, $connect ) === false )

        //////////////////////////////////////////////////////////////////////////////////
        //
        // 옥션 쿠폰 재적용 ==>> 한번만 실행 되도록 여기서 실행
        //
        // 동일 주문번호에 대해서 쿠폰금액이 중복으로 적용됨
        //
        // 옥션 판매처 

        if( _DOMAIN_ == 'pnx1748' )
        {
            // 옥션 주문 중에서 판매처, 주문번호 동일 주문 있는 경우 찾기
            $query = "select shop_id,
                             order_id, 
                             code15,
                             sum(qty) sum_qty,
                             count(seq) as cnt
                        from orders
                       where status=0 and
                             order_status=20 and
                             shop_id % 100 = 1
                    group by shop_id, order_id
                      having cnt>1";
debug("옥션 쿠폰할인1 : ".$query);
            $result = mysql_query($query, $connect);
            while($data = mysql_fetch_assoc($result))
            {
                $new_code15 = $data[code15] / $data[sum_qty];
                
                $query = "update orders
                             set code15 = qty * $new_code15
                           where status = 0 and
                                 order_status = 20 and
                                 shop_id = $data[shop_id] and
                                 order_id = '$data[order_id]'";
debug("옥션 쿠폰할인2 : ".$query);
                mysql_query($query, $connect);
            }
        }

        // 옥션, 지마켓 통합 esm
        $is_esm = true;
        
        // 주문을 읽어서 order_product에 상품을 넣는다.
        $query = "select * from orders where status=0 and order_status=20 order by seq";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 해당 주문이 이미 등록되 있으면 넘어간다.
            if( class_order_products::is_registered($data[seq]) )  continue;

            // 옵션 문자셋 변환
            $options = $data[options];
            
            // 매칭 옵션에서 가격삭제 
            if( $_SESSION[DEL_PRICE] )
            {
                // esm
                if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 2 || $data[shop_id] % 100 == 78 || $data[shop_id] % 100 == 79 )
                    $options = preg_replace("/\/-?[0-9]+원\//","/",$options);
                
                // 메이크샵
                if( $data[shop_id] % 100 == 68 )
                {
                    $options = preg_replace_callback ("|\([+\-]?[0-9]+원\)|U",
                         create_function(
                         '$matches',
                         'return $matches[0] = "";'
                    ),$options);
                }
            }

            $reg_ok = false;
            $opt_obj = new class_option_match();

            // 메모선택 안하고, 교환C/S 없고, 지마켓, 옥션 (ESM) 이면 무조건 찢는다.
            // namsun 10002 는 찢지 않는다. 2012-01-10 김정숙 요청
            if( $is_esm && !$data[memo_check] && $data[order_cs] == 0 && ($data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 2 || $data[shop_id] % 100 == 78 || $data[shop_id] % 100 == 79) && _DOMAIN_ != 'namsun' && _DOMAIN_ != 'aostar' && _DOMAIN_ != 'buy7942' && _DOMAIN_ != 'jjangjuk' )
            {
                // Long Options 확인
                if( preg_match('/^\*Long Options\(([0-9]+)\)\* .+$/',$options, $matches) )
                {
                    $lo_seq = $matches[1];
                    
                    $query_lo = "select * from long_options where seq=$lo_seq";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = $data_lo[options];
                }
                
                // 맨 마지막 ","는 무조건 삭제
                $options = preg_replace('/,$/','',$options);
                
                // <주문옵션>,<추가구성> 둘다 있는 경우
                if( preg_match('/^<주문옵션>(.+)<추가구성>(.+)$/',$options, $matches) )
                {
                    // 옥션 본상품도 무조건 나눔
                    if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 78 )
                    {
                        //###############################################
                        // 이 로직은, 사용자가 옵션 내용을 입력하는 경우
                        // 옵션 내용에 '선택:레드' 형식이 들어간 경우를
                        // 처리하기 위함
                        //
                        // 분리된 옵션에 '/1개 -' 의 형식의 데이터가 없으면
                        // 사용자입력으로 보고 앞의 옵션에 붙여넣는다.
                        //###############################################
                        $_opt_arr = array();
                        $_n = -1;
                        foreach( preg_split('/, (?=([^:](?!,\s))+:)/', $matches[1]) as $_opt_temp )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt_temp )  continue;

                            // 최초
                            if( $_n == -1 )
                            {
                                $_n = 0;
                                $_opt_arr[$_n] = $_opt_temp;
                            }
                            // 두번째 이후
                            else
                            {
                                if( preg_match('/\/[0-9]+개\s\-/', $_opt_temp) )
                                {
                                    $_n++;
                                    $_opt_arr[$_n] = $_opt_temp;
                                }
                                else
                                    $_opt_arr[$_n] .= ", " . $_opt_temp;
                            }
                        }

                        foreach( $_opt_arr as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            $data[qty] = $_m[1];

                            // 맨 뒤가 "개 - " 이면 삭제
                            if( preg_match('/\/[0-9]+개\s-\s?$/',$_opt) )
                                $base_opt = preg_replace('/\/[0-9]+개\s-\s?$/','',$_opt);
                            else
                                $base_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                            $reg_ok = class_order_products::insert_product(
                                            $data[shop_id], 
                                            $data[shop_product_id], 
                                            $base_opt, 
                                            '',
                                            $data[seq], 
                                            $data[qty],
                                            1 );
                        }
                    }
                    // 특정 업체는 지마켓 본상품도 무조건 나눔
                    else if( ($data[shop_id] % 100 == 2 || $data[shop_id] % 100 == 79) && ( _DOMAIN_ == 'mkh2009' || _DOMAIN_ == 'babysue' || _DOMAIN_ == 'pinkage' || _DOMAIN_ == 'babyddo' || _DOMAIN_ == 'buybye'  || _DOMAIN_ == 'ecitio'  || _DOMAIN_ == 'ezadmin' ) )
                    {
                        foreach( preg_split('/, (?=([^:](?!,\s))+:)/', $matches[1]) as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // $data[qty] = $_m[1];

                            // 맨 뒤가 "개 - " 이면 삭제
                            if( preg_match('/\/([0-9])+개\s-\s?$/',$_opt) )
                                $base_opt = preg_replace('/\/[0-9]+개\s-\s?$/','',$_opt);
                            else
                                $base_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                            $reg_ok = class_order_products::insert_product(
                                            $data[shop_id], 
                                            $data[shop_product_id], 
                                            $base_opt, 
                                            '',
                                            $data[seq], 
                                            $data[qty],
                                            1 );
                        }
                    }
                    else
                    {
                        // 주문옵션 여러개인 경우, ", " 로 구분하여 각 항목의 맨뒤 "/1개" 를 지우고 다시 결합.
                        $base_opt = "";
                        foreach( explode(", ", $matches[1]) as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            // 수량 구하기
                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // $data[qty] = $_m[1];

                            // 수량제거
                            $base_opt .= preg_replace('/\/[0-9]+개/','',$_opt) . ", ";
                        }
                        
                        // 마지막 ", " 삭제
                        $base_opt = substr($base_opt,0,-2);
                        
                        // sandl 업체는 주문옵션 옵션이 공란인 경우 상품명 사용
                        if( _DOMAIN_ == 'sandl' && $base_opt == '' )
                            $base_opt = $data[product_name];

                        $reg_ok = class_order_products::insert_product(
                                        $data[shop_id], 
                                        $data[shop_product_id], 
                                        $base_opt, 
                                        '',
                                        $data[seq], 
                                        $data[qty],
                                        1 );
                    }
                                    
                    // 추가구성 - 모두 개별 등록
                    foreach( explode(", ", $matches[2]) as $_opt )
                    {
                        // 내용 없으면 넘어감
                        if( !$_opt )  continue;

                        // 선택안함 있으면 넘어감
                        if( strpos($_opt, "선택안함") !== false )  continue;
                        
                        // 추가구성 개별 수량
                        preg_match('/\/([0-9]+)개/', $_opt, $_m);
                        $opt_qty = ($_m[1] ? $_m[1] : $data[qty]);
                        
                        // 추가구성 수량 삭제
                        $add_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                        $reg_ok = class_order_products::insert_product(
                                        $data[shop_id], 
                                        $data[shop_product_id], 
                                        $add_opt, 
                                        '',
                                        $data[seq], 
                                        $opt_qty,
                                        2 );
                        if( !$reg_ok )  break;
                    }
                }
                // <주문옵션>만 있고 <추가구성> 없는 경우
                else if( strpos($options,"<주문옵션>") !== false && strpos($options,"<추가구성>") === false )
                {
                    $options = str_replace("<주문옵션>","",$options);
                    
                    // 옥션 본상품도 무조건 나눔
                    if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 78 )
                    {
                        //###############################################
                        // 이 로직은, 사용자가 옵션 내용을 입력하는 경우
                        // 옵션 내용에 '선택:레드' 형식이 들어간 경우를
                        // 처리하기 위함
                        //
                        // 분리된 옵션에 '/1개 -' 의 형식의 데이터가 없으면
                        // 사용자입력으로 보고 앞의 옵션에 붙여넣는다.
                        //###############################################
                        $_opt_arr = array();
                        $_n = -1;
                        foreach( preg_split('/, (?=([^:](?!,\s))+:)/', $options) as $_opt_temp )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt_temp )  continue;

                            // 최초
                            if( $_n == -1 )
                            {
                                $_n = 0;
                                $_opt_arr[$_n] = $_opt_temp;
                            }
                            // 두번째 이후
                            else
                            {
                                if( preg_match('/\/[0-9]+개\s\-/', $_opt_temp) )
                                {
                                    $_n++;
                                    $_opt_arr[$_n] = $_opt_temp;
                                }
                                else
                                    $_opt_arr[$_n] .= ", " . $_opt_temp;
                            }
                        }

                        foreach( $_opt_arr as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            $data[qty] = $_m[1];

                            // 맨 뒤가 "개 - " 이면 삭제
                            if( preg_match('/\/[0-9]+개\s-\s?$/',$_opt) )
                                $base_opt = preg_replace('/\/[0-9]+개\s-\s?$/','',$_opt);
                            else
                                $base_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                            $reg_ok = class_order_products::insert_product(
                                            $data[shop_id], 
                                            $data[shop_product_id], 
                                            $base_opt, 
                                            '',
                                            $data[seq], 
                                            $data[qty],
                                            1 );
                        }
                    }
                    // 특정 업체는 지마켓 본상품도 무조건 나눔
                    else if( ($data[shop_id] % 100 == 2 || $data[shop_id] % 100 == 79) && ( _DOMAIN_ == 'mkh2009' || _DOMAIN_ == 'babysue' || _DOMAIN_ == 'pinkage' || _DOMAIN_ == 'babyddo' || _DOMAIN_ == 'buybye'  || _DOMAIN_ == 'ecitio'  || _DOMAIN_ == 'ezadmin' ) )
                    {
                        foreach( preg_split('/, (?=([^:](?!,\s))+:)/', $options) as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // $data[qty] = $_m[1];

                            // 맨 뒤가 "개 - " 이면 삭제
                            if( preg_match('/\/([0-9])+개\s-\s?$/',$_opt) )
                                $base_opt = preg_replace('/\/[0-9]+개\s-\s?$/','',$_opt);
                            else
                                $base_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                            $reg_ok = class_order_products::insert_product(
                                            $data[shop_id], 
                                            $data[shop_product_id], 
                                            $base_opt, 
                                            '',
                                            $data[seq], 
                                            $data[qty],
                                            1 );
                        }
                    }
                    else
                    {
                        // 주문옵션 여러개인 경우, "," 로 구분하여 각 항목의 맨뒤 [1개]를 지우고 다시 결합.
                        $base_opt = "";
                        foreach( explode(", ", $options) as $_opt )
                        {
                            // 내용 없으면 넘어감
                            if( !$_opt )  continue;

                            // 수량구하기
                            preg_match('/\/([0-9]+)개/',$_opt,$_m);
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // 2013-06-05
                            // 발주서 수량 열과 옵션 열의 수량이 다른경우가 있음
                            // 본상품은 무조건 수량 열의 값을 사용
                            //
                            // 2013-06-17
                            // 옥션은 옵션의 수량을, 지마켓은 발주서 수량을 사용
                            //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                            // $data[qty] = $_m[1];

                            // 수량삭제
                            $base_opt .= preg_replace('/\/[0-9]+개/','',$_opt) . ", ";
                        }
    
                        // 마지막 ", " 삭제
                        $base_opt = substr($base_opt,0,-2);

                        $reg_ok = class_order_products::insert_product(
                                        $data[shop_id], 
                                        $data[shop_product_id], 
                                        $base_opt, 
                                        '',
                                        $data[seq], 
                                        $data[qty],
                                        1 );
                    }
                }
                // <주문옵션> 없고 <추가구성>만 있는 경우
                else if( strpos($options,"<주문옵션>") === false && strpos($options,"<추가구성>") !== false )
                {
                    $options = str_replace("<추가구성>","",$options);

                    // sandl 업체는 주문옵션 옵션이 공란인 경우 상품명 사용
                    if( _DOMAIN_ == 'sandl' || _DOMAIN_ == 'bgb2010' || _DOMAIN == 'homeive' )
                        $new_base_opt = $data[product_name];
                    else
                        $new_base_opt = "";

                    // 옵션없는 본상품 등록
                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $new_base_opt, 
                                    '',
                                    $data[seq], 
                                    $data[qty],
                                    1 );

                    // 추가구성 - 모두 개별 등록
                    foreach( explode(", ", $options) as $_opt )
                    {
                        // 내용 없으면 넘어감
                        if( !$_opt )  continue;

                        // 선택안함 있으면 넘어감
                        if( strpos($_opt, "선택안함") !== false )  continue;
                    
                        // 추가구성 개별 수량
                        preg_match('/\/([0-9]+)개/', $_opt, $_m);
                        $opt_qty = $_m[1];
                        
                        // 추가구성 수량 삭제
                        $add_opt = preg_replace('/\/[0-9]+개/','',$_opt);

                        $reg_ok = class_order_products::insert_product(
                                        $data[shop_id], 
                                        $data[shop_product_id], 
                                        $add_opt, 
                                        '',
                                        $data[seq], 
                                        $opt_qty,
                                        2 );
                        if( !$reg_ok )  break;
                    }
                }
                // 구옵션
                else
                {
                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $options, 
                                    '',
                                    $data[seq], 
                                    $data[qty],
                                    0 );
                }
            }
            
            // 맘스투데이
            else if( !$data[memo_check] && $data[order_cs] == 0 && (
                    (_DOMAIN_ == 'jkhdev' && $data[shop_id] == 10083) ||
                    (_DOMAIN_ == '_iggo' && $data[shop_id] == 10084)
                ) 
            )
            {
                // Long Options 확인
                if( preg_match('/^\*Long Options\(([0-9]+)\)\* .+$/',$options, $matches) )
                {
                    $lo_seq = $matches[1];
                    
                    $query_lo = "select * from long_options where seq=$lo_seq";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = $data_lo[options];
                }

                // 상품명 찾기
                if( preg_match('/^([^\/]+)\s(사이즈 \/ .+)$/',$options, $matches) )
                {
                    $shop_product_name = $matches[1];
                    $shop_options = $matches[2];
                    
                    // orders의 상품명 수정
                    $query_p_name = "update orders set product_name = '$shop_product_name' where seq=$data[seq]";
                    mysql_query($query_p_name, $connect);
                    
                    foreach( preg_split('/EA - [0-9]+원/', $shop_options) as $_opt )
                    {
                        // 내용 없으면 넘어감
                        if( !$_opt )  continue;

                        preg_match('/^(.+)\s([0-9]+)$/',$_opt,$_m);
                        $base_opt = $_m[1];
                        $data[qty] = $_m[2];

                        $reg_ok = class_order_products::insert_product(
                                        $data[shop_id], 
                                        $base_opt, 
                                        $base_opt, 
                                        '',
                                        $data[seq], 
                                        $data[qty],
                                        1 );
                    }
                }
            }
            
            // 위메프
            else if( 
                !$data[memo_check] && $data[order_cs] == 0 && 0 &&
                _DOMAIN_ != 'namsun' &&  
                (_DOMAIN_ == 'ephod' || _DOMAIN_ == 'aostar' || _DOMAIN_ == 'kdj161' || _DOMAIN_ == 'milkids' || _DOMAIN_ == 'ezadmin' ) && 
                ( $data[shop_id] % 100 == 20 || $data[shop_id] % 100 == 54 ) 
            )

            {
                // Long Options 확인
                if( preg_match('/^\*Long Options\(([0-9]+)\)\* .+$/',$options, $matches) )
                {
                    $lo_seq = $matches[1];
                    
                    $query_lo = "select * from long_options where seq=$lo_seq";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = $data_lo[options];
                }
                
                // 위메프(자동)은 옵션 앞에 (딜번호) 추가로 들어옴
                $deal_no = "";
                if( $data[shop_id] % 100 == 54 )
                {
                    if( preg_match("/^(\([0-9]+\))(.+)/", $options, $matches) )
                    {
                        $deal_no = $matches[1];
                        $options = $matches[2];
                    }
                }
                
                // "[" 로 시작하면
                if( substr($options, 0, 1) == "[" )
                {
                    $options_arr = explode("],[", $options);
                    $new_opt = false;
                }
                else
                {
                    $options_arr = preg_split("/(?<=[0-9]개),/", $options);
                    $new_opt = true;
                }
                
                $options_cnt = count($options_arr);
                for( $i=0; $i < $options_cnt; $i++ )
                {
                    if( $new_opt )
                    {
                        // 수량 구하기
                        preg_match('/([0-9]+)개$/i', $options_arr[$i], $matches);
                        $new_qty = $matches[1];
                        
                        // 옵션에서 '1개' 제거하기
                        $new_options = preg_replace('/:[0-9]+개$/', '', $options_arr[$i] );
                    }
                    else
                    {
                        if( $options_cnt != 1 )
                        {
                            // 처음 옵션 뒤에 "]" 추가
                            if( $i == 0 )
                                $options_arr[$i] = $options_arr[$i] . "]";
                            // 마지막 옵션 앞에 "[" 추가
                            else if( $i == $options_cnt -1 )
                                $options_arr[$i] = "[" . $options_arr[$i];
                            // 중간 옵션 앞에 "[", 뒤에 "]" 추가
                            else
                                $options_arr[$i] = "[" . $options_arr[$i] . "]";
                        }
                        
                        // 수량 구하기
                        preg_match('/:([0-9]+)개\]$/i', $options_arr[$i], $matches);
                        $new_qty = $matches[1];
                        
                        // 옵션에서 ':1개' 제거하기
                        $new_options = preg_replace('/:[0-9]+개\]$/', ']', $options_arr[$i] );
                    }
                    
                    if( $deal_no )
                        $new_options = $deal_no . $new_options;

                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $new_options, 
                                    $new_options, 
                                    '',
                                    $data[seq], 
                                    $new_qty,
                                    1 );
                }
            }
            // 최웅 ..   데일리... 【03. 헬로팬더 - 퍼플/ L : 1개】【29. 빅토리 - 코코아/ L : 1개】
            // 위메프 ..  (274873)03_LPM095GN/ 하이스판 모던컬러 디링 슬림일자 면바지★한정특가|카키/XL:1개,06_LPJ032GN/ 프리미엄 하이스판 슬림일자 정장바지★한정특가|다크그레이/XL:1개
            // 10071 or wecompany -> 10080
            // 2014-10-02 장경희. wecompany 10080 판매처 옵션별 발주서 들어옴. 이 로직 더이상 필요없음
            else if
            ( 
                !$data[memo_check] && $data[order_cs] == 0 && 
                (  ( $data[shop_id] % 100 == 80  && _DOMAIN_ =="_wecompany" ) || ( $data[shop_id] % 100 == 71  && _DOMAIN_ =="_ezadmin" )  )
            )

            {
                // Long Options 확인
                if( preg_match('/^\*Long Options\(([0-9]+)\)\* .+$/',$options, $matches) )
                {
                    $lo_seq = $matches[1];
                    
                    $query_lo = "select * from long_options where seq=$lo_seq";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = $data_lo[options];
                }

                // ezadmin 은 shop_id 가 71..
                // 데일리... 【03. 헬로팬더 - 퍼플/ L : 1개】 중 "데일리... "를 땜
                $deal_no = "";
                if( $data[shop_id] % 100 == 80 || $data[shop_id] % 100 == 71 )
                {
                    if( preg_match("/(.+?)\s(.+)/", $options, $matches) )
                    {
                        $deal_no = $matches[1];
                        $options = $matches[2];
                    }
                }

				// 【 -> ,로 치환.
            	$options=str_replace("【",",",$options);
				$options=str_replace("】","",$options);
				$options = substr($options, 1);

				// ,로 옵션을 분리.
				$options_arr = preg_split("/(?<=[0-9]개),/", $options);
                
                $options_cnt = count($options_arr);
                
               	//수량 검사, orders 랑 order_products랑 비교후 같아야 분리
               	$data_qty = $data[qty];
                for( $i=0; $i < $options_cnt; $i++ )
                {
                    // 수량 구하기
                    preg_match('/([0-9]+)개$/i', $options_arr[$i], $matches);
                    $new_qty = $matches[1];
                    
                    $data_qty = $data_qty - $new_qty;
                }
                
                if($data_qty == 0)
                {
                	//수량이 맞으면 쪼개서 INSERT
	                for( $i=0; $i < $options_cnt; $i++ )
	                {   
	                	// 수량 구하기
	                    preg_match('/([0-9]+)개$/i', $options_arr[$i], $matches);
	                    $new_qty = $matches[1];
	                    
	                     
	                    // 옵션에서 '1개' 제거하기
	                    $new_options = preg_replace('/:\s[0-9]+개$/', '', $options_arr[$i] );
	                    $options_arr[$i] = "【" . $options_arr[$i] . "】";
	                    
	                    if( $deal_no )
	                        $new_options = $deal_no . $new_options;
	
	                    $reg_ok = class_order_products::insert_product(
	                                    $data[shop_id], 
	                                    $new_options, 
	                                    $new_options, 
	                                    '',
	                                    $data[seq], 
	                                    $new_qty,
	                                    1 );
	                }
	            }
	            else //수량이 안맞는경우 원본대로 INSERT
	            {
		            $reg_ok = class_order_products::insert_product(
	                                $data[shop_id], 
	                                $data[shop_product_id], 
	                                $options, 
	                                $data[match_code],
	                                $data[seq], 
	                                $data[qty],
	                                $data[memo_check]?3:0 ); 	
	            }
            }

            
            // 메모선택 안하고, 교환C/S 없고, 추가옵션주문설정 있으면 찢는다.
            else if( !$data[memo_check] && $data[order_cs] == 0 && $opt_obj->is_registered($data[shop_id], $data[shop_product_id]) )
            {
                // 찢어진 옵션 배열을 얻는다.
                $arr_option = $opt_obj->divide_options(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $options,
                                    $data[qty] );
                // 찢어진 옵션대로 상품 등록한다.
                foreach( $arr_option as $opt )
                {
                    // 옥션, 11번가는 추가상품도 계산한다.
                    if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 78 || $data[shop_id] % 100 == 4 || $data[shop_id] % 100 == 50 )
                        $qty = $opt[qty];
                    else
                        $qty = $data[qty];

                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $opt[option], 
                                    '',
                                    $data[seq], 
                                    $qty,
                                    $opt[marked] );
                    if( !$reg_ok )  break;
                }
            }

            // 2014-10-16 장경희. cafe24 세트상품
            else if( $data[shop_id] % 100 == 72 && preg_match('/\*\*[0-9]+\|\|/', $options) )
            {
				// "||" 로 옵션을 분리.
				$options_arr = preg_split("/\|\|/", $options);
                foreach($options_arr as $opt_val)
                {   
                    if( !$opt_val )  continue;
                    
                	// 수량 구하기
                    preg_match('/(.+)\*\*([0-9]+)$/i', $opt_val, $matches);
                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $matches[1], 
                                    '',
                                    $data[seq], 
                                    $matches[2],
                                    0 );
                }
            }

            // 메모선택 했거나, 교환C/S 있거나, 추가옵션주문설정 없으면, 옵션을 찢지 않고 주문 하나로 등록
            else
            {
                $reg_ok = class_order_products::insert_product(
                                $data[shop_id], 
                                $data[shop_product_id], 
                                $options, 
                                $data[match_code],
                                $data[seq], 
                                $data[qty],
                                $data[memo_check]?3:0 );
            }
            
            // 등록에 성공하면, 주문의 order_status를 30으로 바꾼다.
            if( $reg_ok )
            {
                $query = "update orders set order_status=30 where seq=$data[seq]";
                mysql_query( $query, $connect );
            }
        }

        // 자동매칭
        if( $_SESSION[BASIC_VERSION] == 1 && $_SESSION[STOCK_MANAGE_USE] == 0 )
        {
            if( _DOMAIN_ != 'donnandeco' && _DOMAIN_ != 'ppgirl' && _DOMAIN_ != 'memorette' )
            {
                // 주문에 옵션이 공란이면, 상품명 복사
                $query = "update orders a, order_products b
                             set a.options = a.product_name,
                                 b.shop_options = a.product_name
                           where a.seq = b.order_seq and
                                 a.status = 0 and 
                                 a.order_status = 30 and
                                 a.options = ''";
                mysql_query( $query, $connect);
            }
            
            $query_match = "select product_id from products where is_delete=0 limit 1";
            $result_match = mysql_query( $query_match, $connect );
            $data_match = mysql_fetch_array( $result_match );

            // order_product에서 매칭안된 상품을 자동일괄매칭한다.
            $query = "select * from order_products where status=0";
            $result = mysql_query( $query, $connect );
            while( $data = mysql_fetch_array( $result ) )
            {
                $arr_match_id  = array($data_match[product_id]);
                $arr_match_qty = array($data[qty]);

                // 매칭한다.
                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0);
            }
        }
        else if( $_SESSION[BASIC_VERSION] == 1 && $_SESSION[STOCK_MANAGE_USE] == 2 )
        {
            // order_product에서 매칭안된 상품을 자동매칭한다. 수동매칭, order_cs 제외
            $query = "select * from order_products where status=0 and marked in (0,1,2) and order_cs=0";
            $result = mysql_query( $query, $connect );
            while( $data = mysql_fetch_array( $result ) )
            {
                // 매칭 정보를 찾는다.
                $query_match = "select id, qty, auto_count 
                                  from code_match 
                                 where shop_id     = '$data[shop_id]' and 
                                       shop_code   = '$data[shop_product_id]'
                                       limit 1";  // 한개만 가져와서 매칭.
                $result_match = mysql_query( $query_match, $connect );
                if( mysql_num_rows( $result_match ) > 0 )
                {
                    // 배열 만든다.
                    $data_match = mysql_fetch_array( $result_match );
                    $arr_match_id  = array($data_match[id]);
                    $arr_match_qty = array($data[qty]);

                    // 매칭한다.
                    class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0);
                }
            }
        }
        else
        {
            // order_product에서 매칭안된 상품을 자동매칭한다. 수동매칭, order_cs 제외
            $query = "select * from order_products where status=0 and marked in (0,1,2) and order_cs=0";
            $result = mysql_query( $query, $connect );
            while( $data = mysql_fetch_array( $result ) )
            {
                $shop_code = $data[shop_id] % 100;

                //#################################
                //      pnd 자동매칭
                //#################################
                if( _DOMAIN_ == 'pnd' )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";

                    // 옥션, 지마켓
                    if( $shop_code==1 || $shop_code==2 )
                    {
                        if( preg_match('/^.+:\s?(.+)$/', $_option, $matches) )
                            $real_name = $matches[1];
                    }
                    // 샵N
                    else if( $shop_code==51 )
                    {
                        if( preg_match('/^.+:\s[0-9]+번\)\s(.+)\s\/\s.+:\s(.+)$/', $_option, $matches) )
                            $real_name = $matches[1] . "-" . $matches[2];
                        else if( preg_match('/^.+:\s?(.+)$/', $_option, $matches) )
                            $real_name = $matches[1];
                    }
                    // 쿠팡
                    else if( $shop_code==53 )
                    {
                        if( preg_match('/\)\s(.+\])\s(.+)\/(.+)$/', $_option, $matches) )
                            $real_option = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
                    }
                    // 하프클럽
                    else if( $shop_code==27 )
                    {
                        $_option = str_replace("_","-", $_option);
                        $_option = str_replace("/.","", $_option);
                        if( preg_match('/^(.+)$/', $_option, $matches) )
                            $real_name = $matches[1];
                    }
                    
                    if( $real_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                    else if( $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options = '$real_option' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      realcoco 자동매칭
                //#################################
                if( _DOMAIN_ == 'realcoco' && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==53 || $shop_code==54 || $shop_code==55 || $shop_code==68 || $shop_code==17 ) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
                    $real_option1 = "";
                    $real_option2 = "";

                    if( $shop_code == 1 || $shop_code == 2 || $shop_code == 50 )
                    {
                        if( preg_match('/^(.+):(.+)\-[0-9]+\[(.+)\/.+\-(.+)\]$/', $_option, $matches) )
                        {
                            $real_name = $matches[2];
                            $real_option1 = $matches[3];
                            $real_option2 = $matches[4];
                        }
                        else if( preg_match('/^(.+)\-[0-9]+:\[(.+)\/.+\-(.+)\]$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option1 = $matches[2];
                            $real_option2 = $matches[3];
                        }
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        if( preg_match('/^선택[0-9]+\)\s(.+)\s(.+)\s?\/\s?(.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option1 = $matches[2];
                            $real_option2 = $matches[3];
                        }
                    }
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                        // (182533)57. 마르코JK|블랙_F
                        if( preg_match('/^\([0-9]+\)[0-9]+\.\s(.+)\|(.+)_(.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option1 = $matches[2];
                            $real_option2 = $matches[3];
                        }
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                        if( preg_match('/^[0-9]+\.(.+)\|(.+)_(.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option1 = $matches[2];
                            $real_option2 = $matches[3];
                        }
                    }
                    // G마켓 일본
                    else if( $shop_code == 17 )
                    {
                        if( preg_match('/<판매자옵션코드>(.+)\-[0-9]+\[(.+)\/.+\-(.+)\]$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option1 = $matches[2];
                            $real_option2 = $matches[3];
                        }
                    }
                    else if( $shop_code == 68 )
                    {
                        // 상품명 구하기
                        $query_pname = "select product_name from orders where seq=$data[order_seq]";
                        $result_pname = mysql_query($query_pname, $connect);
                        $data_pname = mysql_fetch_assoc($result_pname);
                        
                        $real_name = $data_pname[product_name];
                        $real_option = preg_replace('/\S+ : /', ':', $_option);

                        // 젤 앞의 ':' 제거
                        $real_option = preg_replace('/^:/', '', $real_option);
                    }

                    if( $real_name > '' && $real_option > '' )
                    {
                        $real_name = preg_replace('/\(.+\)/', '', $real_name);

                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options=':$real_option' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                        else
                        {
                            // 옵션이 2 개인 경우
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and replace(replace(replace(options,':',''),',',''),' ','')='$real_option' ";
                            $result_prd_match = mysql_query($query_prd_match, $connect);
                            if( mysql_num_rows( $result_prd_match ) )
                            {
                                $data_prd_match = mysql_fetch_assoc($result_prd_match);
                                
                                // 찾아낸 매칭 정보를 배열로 만든다.
                                $arr_match_id  = array();
                                $arr_match_qty = array();
        
                                $arr_match_id[] = $data_prd_match[product_id];
                                $arr_match_qty[] = $data[qty];
        
                                // 매칭한다.
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                
                                continue;
                            }
                        }
                    }

                    if( $real_name > '' && $real_option1 > '' && $real_option2 > '' )
                    {
                        $real_name = preg_replace('/\(.+\)/', '', $real_name);

                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options=':$real_option1, :$real_option2' ";
debug("쿠팡 자동매칭 : " . $query_prd_match);
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      wizi 자동매칭
                //#################################
                if( (_DOMAIN_ == 'wizi' ) && ($shop_code==1 || $shop_code==2 || $shop_code==6 || $shop_code==50 ) )
                {
                    // 옥션
                    if( $shop_code == 1 )
                        preg_match('/^<주문선택사항>(.+)\[(.+)\]\/[0-9]+개\/$/', $data[shop_options], $matches);
                    // 지마켓
                    else if( $shop_code == 2 )
                        preg_match('/^.+;(.+)\[(.+)\],$/',$data[shop_options],$matches);
                    // 인터파크 
                    else if( $shop_code == 6 )
                        preg_match('/^.+ \/ (.+)\[(.+)\]$/',$data[shop_options],$matches);
                    // 11번가
                    else if( $shop_code == 50 )
                        preg_match('/^[^:]+:(.+)\[(.+)\]$/',$data[shop_options],$matches);

                    if( $matches[1] > '' && $matches[2] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$matches[1]%' and options='옵션:$matches[2]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      sgo 자동매칭
                //#################################
                if( (_DOMAIN_ == 'sgo') && ($shop_code==1 || $shop_code==2 || $shop_code==50 ) )
                {
                    $option_str = preg_replace('/★.+★/', '', $data[shop_options]);

                    $match_name = "";
                    $match_option = "";
                    
                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        if( preg_match('/^([^:]+):(.+)(\[[^\[\]]+\])$/', $option_str, $matches) )
                        {
                            $match_name = $matches[2];
                            $match_option = $matches[3];
                        }
                        else if( preg_match('/^([^:]+):(\[.+\])$/', $option_str, $matches) )
                        {
                            $match_name = $matches[1];
                            $match_option = $matches[2];
                        }
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        if( preg_match('/^([^:]+):(.+)(\[[^\[\]]+\])$/', $option_str, $matches) )
                        {
                            $match_name = $matches[2];
                            $match_option = $matches[3];
                        }
                    }

                    if( $match_name > '' && $match_option > '' )
                    {
                        $query_prd_match = "select product_id 
                                              from products 
                                             where is_delete=0 and 
                                                   is_represent=0 and 
                                                   options='$match_option' and
                                                   name like '%$match_name%' and 
                                                   name not like '{미사용%' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      voiceone 자동매칭
                //#################################
                if( (_DOMAIN_ == 'voiceone' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==53 ) )
                {
//                	2014.09.02 매칭 새로 요청
//                    $option_str = preg_replace('/★.+★/', '', $data[shop_options]);
//
//                    $match_name = "";
//                    $match_option = "";
//                    
//                    // 옥션, 지마켓
//                    if( $shop_code == 1 || $shop_code == 2 )
//                    {
//                        if( preg_match('/^([^:]+):(\[.+\])$/', $option_str, $matches) )
//                        {
//                            $match_name = $matches[1];
//                            $match_option = $matches[2];
//                        }
//                    }
//                    // 11번가
//                    else if( $shop_code == 50 )
//                    {
//                        if( preg_match('/^([^:]+):(.+)(\[[^\[\]]+\])$/', $option_str, $matches) )
//                        {
//                            $match_name = $matches[2];
//                            $match_option = $matches[3];
//                        }
//                    }


                    $option_str = $data[shop_options];
                    $match_name = "";
                    $match_option = "";
                    
                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        if( preg_match('/^(.+)\:(.+)\+(.+)$/', $option_str, $matches) )
                        {
                            $match_name = $matches[1];
                            $match_option = $matches[3]."-".$matches[2];
                        }
                    }
                    else if( $shop_code == 50 )
                    {
                        if( preg_match('/.+\:(.+)\[(.+)\]$/', $option_str, $matches) )
                        {
                            $match_name = $matches[1];
                            $match_option = $matches[2];
                        }
                    }
					else if( $shop_code == 53 )
                    {
                        if( preg_match('/^.+\s\((.+)\)\s\[(.+)\]$/', $option_str, $matches) )
                        {
                            $match_name = $matches[1];
                            $match_option = $matches[2];
                        }
                    }

                    if( $match_name > '' && $match_option > '' )
                    {
                        $query_prd_match = "select product_id 
                                              from products 
                                             where is_delete=0 and 
                                                   is_represent=0 and 
                                                   options='[$match_option]' and
                                                   name like '$match_name(%' and 
                                                   name not like '%{미사용}%' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) == 1)
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      freestyle 자동매칭
                //#################################
                if( (_DOMAIN_ == 'freestyle' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 ) )
                {
                    $option_str = $data[shop_options];

                    // 옥션
                    if( $shop_code == 1 )
                        preg_match('/^<주문선택사항>([^\/]+)\/([^\/]+)\/[0-9]+개\/$/', $option_str, $matches);
                    // 지마켓
                    else if( $shop_code == 2 )
                        preg_match('/^([^;]+);(.+),$/',$option_str,$matches);
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        if( strpos($option_str, "추가상품") )
                            preg_match('/┗\(추가상품\)([^-]+)-(.+)$/',$option_str,$matches);
                        else
                            preg_match('/^[^:]+:([^-]+)-(.+)$/',$option_str,$matches);
                    }

                    if( $matches[1] > '' && $matches[2] > '' )
                    {
                        $query_prd_match = "select product_id 
                                              from products 
                                             where is_delete=0 and 
                                                   is_represent=0 and 
                                                   substring( name, 1, if( locate('(',name), locate('(',name)-1, 100 )) = '$matches[1]' and 
                                                   options='[" . str_replace(" ", "-", $matches[2]) . "]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      suvin 자동매칭
                //#################################
                if( (_DOMAIN_ == 'suvin' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 ) )
                {
                    $_p_name = "";
                    $_option = "";

                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        preg_match('/^([^:]+):\[([^\[\]]+)\]$/',$data[shop_options],$matches);

                        if( $matches[1] > '' && $matches[2] > '' )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2];
                        }
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        preg_match('/^[^:]+:\.*([^\/\-]+)[\/\-]([^\s]+)\s([^\s]+)\s?([^\s]*)$/',$data[shop_options],$matches);

                        if( $matches[1] > '' && $matches[2] > '' && $matches[3] > '' )
                        {
                            $_p_name = $matches[1] . "/" . $matches[2];
                            if( $matches[4] )
                                $_option = $matches[3] . "-" . $matches[4];
                            else
                                $_option = $matches[3];
                        }
                    }
                    // 네이버샵N
                    else if( $shop_code == 51 )
                    {
                        preg_match('/^[^:]+:\s(.+)\s\/\s[^:]+:\s(.+)$/',$data[shop_options],$matches);

                        $_p_name = $matches[1];
                        $_option = $matches[2];
                    }

                    if( $_p_name > '' && $_option > '' )
                    {
                        // 네이버샵N은 상품명, 옵션 일치
                        if( $shop_code == 51 )
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$_p_name' and options = '$_option' ";
                        else
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$_p_name%' and options like '%$_option%' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      kdj161 자동매칭
                //#################################
                if( (_DOMAIN_ == 'kdj161' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 || $shop_code==53 ) )
                {
                    $option_str = $data[shop_options];
                    
                    $_p_name = "";
                    $_option = "";

                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        $option_str = str_replace('타입:', '', $option_str);
                        $option_str = str_replace(':', ' ', $option_str);

                        preg_match('/^(.+)-(.+)$/',$option_str,$matches);

                        if( $matches[1] > '' && $matches[2] > '' )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2];
                        }
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        preg_match('/^.+:(.+)\-(.+)$/',$option_str,$matches);

                        if( $matches[1] > '' && $matches[2] > '' )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2];
                        }
                    }
                    // 네이버샵N
                    else if( $shop_code == 51 )
                    {
                        if( strpos($option_str, ' / ') )
                        {
                            preg_match('/^.+: (.+) \/ .+: (.+)$/',$option_str,$matches);
    
                            if( $matches[1] > '' && $matches[2] > '' )
                            {
                                $_p_name = preg_replace('/선택[0-9]+\-/', '', $matches[1]);
                                $_option = $matches[2];
                            }
                        }
                        else
                        {
                            preg_match('/^.+: (.+)\-(.+)$/',$option_str,$matches);
    
                            if( $matches[1] > '' && $matches[2] > '' )
                            {
                                $_p_name = $matches[1];
                                $_option = $matches[2];
                            }
                        }
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        preg_match('/^.+ (.+) (.+)$/',$option_str,$matches);

                        if( $matches[1] > '' && $matches[2] > '' )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2];
                        }
                    }

                    if( $_p_name > '' && $_option > '' )
                    {
                        if( $shop_code == 53 )
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '% $_p_name' and replace(options,'-','') = '[$_option]' ";
                        else
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$_p_name' and replace(options,'-','') = '[$_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      salgubitbbyam 자동매칭
                //#################################
                if( (_DOMAIN_ == 'salgubitbbyam' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 ) )
                {
                    $option_str = preg_replace('/\[.+\]/', '', $data[shop_options]);
                    
                    // 옥션, 지마켓, 11번가
                    if( $shop_code == 1 || $shop_code == 2 || $shop_code == 50 )
                        preg_match('/^[^:]+:(.+)$/',$option_str,$matches);
                    // 네이버샵N
                    else if( $shop_code == 51 )
                        preg_match('/^[^:]+:\s(.+)$/',$option_str,$matches);

                    if( $matches[1] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$matches[1]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      gerio 자동매칭
                //#################################
                if( (_DOMAIN_ == 'gerio' ) && $shop_code==80 )
                {
                    $_option = $data[shop_options];

                    // ":" 있으면 [] 제거 후, 뒤쪽 부분.
                    if( strpos( $_option, ":" ) )
                    {
                        // 양쪽 [] 제거
                        $_option = preg_replace('/^\[/', '', $_option);
                        $_option = preg_replace('/\]$/', '', $_option);

                        list( $temp_str, $_option ) = explode(":", $_option);
                        
                        // 다시 [] 추가
                        $_option = "[$opt_str]";
                    }   
                    
                    $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options = '$_option' ";
                    $result_prd_match = mysql_query($query_prd_match, $connect);
                    if( mysql_num_rows( $result_prd_match ) )
                    {
                        $data_prd_match = mysql_fetch_assoc($result_prd_match);
                        
                        // 찾아낸 매칭 정보를 배열로 만든다.
                        $arr_match_id  = array();
                        $arr_match_qty = array();

                        $arr_match_id[] = $data_prd_match[product_id];
                        $arr_match_qty[] = $data[qty];

                        // 매칭한다.
                        class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                        
                        continue;
                    }
                    // 못찾을 경우 "/" 제거후 검색
                    else
                    {
                        // "/" 제거
                        $_option = preg_replace('/\//', '', $_option);

                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options = '$_option' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      happysugar 자동매칭
                //#################################
                if( (_DOMAIN_ == 'happysugar' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50) )
                {
                    $_option = $data[shop_options];

                    $_p_name = "";

                    // 옥션, 지마켓, 11번가
                    if( $shop_code == 1 || $shop_code == 2 || $shop_code == 50 )
                    {
                        // DO 도시락/식판:DO-73_ 코몽 스텐버스접시
                        if( preg_match('/^.+:(.+\_).+$/', $_option, $matches) )
                            $_p_name = $matches[1];
                    }
                    
                    if( $_p_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and brand = '$_p_name' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      thenines9 / raymon 자동매칭
                //#################################
                if( (_DOMAIN_ == 'thenines9' || _DOMAIN_ == 'raymon') && ($shop_code==1 || $shop_code==2 || $shop_code==27 || $shop_code==50 || $shop_code==51 || $shop_code==53 || $shop_code==54 || $shop_code==55 || $data[shop_id] == "10082" ) )
                {
                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        preg_match('/^(.+):(.+)$/', $data[shop_options], $matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2];
                    }
                    // 하프클럽
                    else if( $shop_code == 27 )
                    {
                        // 상품명 구하기
                        $query_pname = "select product_name from orders where seq=$data[order_seq]";
                        $result_pname = mysql_query($query_pname, $connect);
                        $data_pname = mysql_fetch_assoc($result_pname);

                        $matches = array();
                        preg_match('/\s([^\s]+)$/',$data_pname[product_name],$matches);
                        $_p_name = $matches[1];
                        
                        $matches = array();
                        preg_match('/^(.+)\/(.+)$/',$data[shop_options],$matches);
                        $_option = $matches[1] . "-" . $matches[2];
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        preg_match('/^(.+):(.+)\/(.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[2];
                        $_option = $matches[3];
                    }
                    // 네이버 샵N
                    else if( $shop_code == 51 )
                    {
                        // 상품명: RH1058MS / 색상: 블랙 / 사이즈: XL 
                        // 상품명: YE1108 / 색상: 다크그레이 / 사이즈: M 
                        preg_match('/^.+:\s(.+)\s\/\s.+:\s(.+)\s\/\s.+:\s(.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2] . "-" . $matches[3];
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        // 선택7)RM1049 오렌지/M
                        preg_match('/^.+\)\s?(.+)\s(.+)[\/\-](.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2] . "-" . $matches[3];
                    }
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                        preg_match('/^\([0-9]+\).+[\)\.\/]\s?(.+)\|(.+)\s?\/\s?(.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2] . "-" . $matches[3];
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                        // 03. CM1104|차콜-FREE
                        preg_match('/^[0-9]+\.\s?(.+)\|(.+)[\/\|\-](.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2] . "-" . $matches[3];
                    }
                    // 플레이어
                    else if( $data[shop_id] == "10082" )
                    {
                        // <상품명>눈꽃단가라니트 YE1124<StyleNo>76<옵션>차콜 : FREE
                        // YE1124 눈꽃단가라 R | [차콜-FREE]
                        preg_match('/^<상품명>.+\s([0-9A-Z]+)<StyleNo>[0-9]+<옵션>(.+)\s:\s(.+)$/',$data[shop_options],$matches);
                        $_p_name = $matches[1];
                        $_option = $matches[2] . "-" . $matches[3];
                    }

                    if( $_p_name > '' && $_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$_p_name%' and options = '[$_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      plays 자동매칭
                //#################################
                if( (_DOMAIN_ == 'plays' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 ) )
                {
                    // 옥션, 지마켓 추가상품
                    if( ($shop_code == 1 || $shop_code == 2) && $data[marked] == 2 )
                    {
                        preg_match('/^\$(.+):(.+)$/', $data[shop_options], $matches);
                        $_p_name = "";
                        $_option = $matches[1] . "-" . $matches[2];
                    }
                    // 옥션, 지마켓 본상품
                    else if( $shop_code == 1 || $shop_code == 2 )
                    {
                        preg_match('/^\$(.+):(.+)$/', $data[shop_options], $matches);
                        $_p_name = "";
                        $_option = $matches[2];
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        preg_match('/^(.+):(.+)$/',$data[shop_options],$matches);
                        $_p_name = "";
                        $_option = $matches[2];
                    }

                    if( $_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options = '[$_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      nyfriend 자동매칭
                //#################################
                if( (_DOMAIN_ == 'nyfriend' ) && ($shop_code==54 || $shop_code==55) )
                {
                    $_p_name = "";

                    // 위메프
                    if( $shop_code == 54 )
                    {
                        // 뒤에 괄호 있는 경우
                        if( preg_match('/^\([0-9]+\)[0-9]+[_\.]\s?(.+) \(.+\)$/', $data[shop_options], $matches) )
                            $_p_name = $matches[1];
                        else if( preg_match('/^\([0-9]+\)[0-9]+[_\.]\s?(.+)$/', $data[shop_options], $matches) )
                            $_p_name = $matches[1];
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                        // 뒤에 괄호 있는 경우
                        // 01. 남홀리패딩조끼 MHV 7|GREY S
                        if( preg_match('/^[0-9]+[_\.]\s?(.+)$/', $data[shop_options], $matches) )
                            $_p_name = $matches[1];
                    }

                    if( $_p_name > '' )
                    {
                        $_p_name = str_replace(" ", "%", $_p_name);
                        $_p_name = str_replace("|", " ", $_p_name);

                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$_p_name' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      box4u 자동매칭
                //#################################
                if( _DOMAIN_ == 'box4u' && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==58 ) )
                {
                    $_p_name = "";
                    
                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        if( preg_match('/:([0-9]+)번/', $data[shop_options], $matches) )
                            $_p_name = (int)$matches[1];
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        if( preg_match('/:([0-9]+)번/', $data[shop_options], $matches) )
                            $_p_name = (int)$matches[1];
                    }
                    // 고도몰
                    else if( $shop_code == 58 )
                    {
                        if( preg_match('/^([0-9]+)번/', $data[shop_options], $matches) )
                            $_p_name = (int)$matches[1];
                    }

                    if( $_p_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$_p_name' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      msoul 자동매칭
                //#################################
                if( (_DOMAIN_ == 'msoul' ) && ($shop_code==51 || $shop_code==53 || $shop_code==54 || $shop_code==55) )
                {
                    $_p_name = "";
                    $_option = "";
                    
                    // 네이버샵N
                    if( $shop_code == 51 )
                    {
                        // 상품코드: 03(상의)SUJP004 / 색상: 차콜 / 사이즈: XL(105)
                        if( preg_match('/^.+:\s(.+)\s\/\s.+:\s(.+)\s\/\s.+:\s(.+)$/', $data[shop_options], $matches) )
                        {
                            // 상품명
                            // 02. AAKN002
                            if( preg_match('/^[0-9]+[\.\)]\s(.+)$/', $matches[1], $name_matches) )
                                $_p_name = $name_matches[1];
                            // 03(상의)SUJP004
                            else if( preg_match('/^[0-9]+\(.+\)(.+)$/', $matches[1], $name_matches) )
                                $_p_name = $name_matches[1];
                            // 04-TSJP037
                            else if( preg_match('/^[0-9]+\-(.+)$/', $matches[1], $name_matches) )
                                $_p_name = $name_matches[1];
                            else
                                $_p_name = $matches[1];

                            $_option = $matches[2] . "-" . $matches[3];
                        }
                        // 색상: L(95) / 사이즈: 보카시카키
                        else if( preg_match('/^.+:\s(.+)\s\/\s.+:\s(.+)$/', $data[shop_options], $matches) )
                        {
                            $query_pname = "select product_name from orders where seq= $data[order_seq]";
                            $result_pname = mysql_query($query_pname, $connect);
                            $data_pname = mysql_fetch_assoc($result_pname);
                            
                            // [GMPT016]비투 모직 체크 슬랙스
                            if( preg_match('/^\[(.+)\]/', $data_pname[product_name], $name_matches) )
                                $_p_name = $name_matches[1];

                            $_option = $matches[1] . "-" . $matches[2];
                        }
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        // 선택06) WWJP001 양털 봄버 패딩 [네이비-L(100)]
                        if( preg_match('/^.+\)\s([^\s]+)\s.+\s\[(.+)\]$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2];
                        }
                    }
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                        // (184205)20. AAKN019/제이원 단가라 라운드니트|네이비_M(95~100)
                        if( preg_match('/^\(.+\)[0-9]+\.\s(.+)\s?\/.+\|(.+)_(.+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2] . "-" . $matches[3];
                        }
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                        // 04. SATPT001/S.에비뉴 스판 기모팬츠|02(먹색)|3(33)
                        if( preg_match('/^[0-9]+\.\s(.+)\/.+\|(.+)\|(.+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2] . "-" . $matches[3];
                        }
                        // 06-SUTS076 / (기모)USA 성조기 후드|메란지|2(100)
                        else if( preg_match('/^[0-9]+\-(.+)\s\/.+\|(.+)\|(.+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_option = $matches[2] . "-" . $matches[3];
                        }
                    }

                    if( $_p_name > "" && $_option > "" )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$_p_name/%' and options = '[$_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }


                //#################################
                //      justone 자동매칭
                //#################################
                if( (_DOMAIN_ == 'justone' ) && ($shop_code==1 || $shop_code==2 || $shop_code==5 || $shop_code==55 || $shop_code==50 || $shop_code==51 || $shop_code==53 || $shop_code==68 || $shop_code==81 ) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
   
                    // 11번가
                    if( $shop_code == 50 )
                    {
						if( preg_match('/\)(.+)-(.+)/',$_option,$matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }
                    
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                    	//10.JO012_마로니핏스키니진|데님|30                        
                        if( preg_match('/^[0-9]+\.(.+)\_.+\|([^\|]+)$/',$_option,$matches) )
                        {                            
                            $real_name = $matches[1];
                            $real_option = $matches[3];                            
                        }
                    }
                    
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                    	//(209459)04. EJ7011_네일워싱스키니진|블루/26
                        if( preg_match('/^.+\s(.+)\_.+\/(.+)$/',$_option,$matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[3];
						}
                    }
                                        
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                    	// 선택30번)J12037 27
                        if( preg_match('/^.+\)(.+)\s(.+)$/',$_option,$matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2];
                        }
                    }
                    
                    // 카카오스토리
                    else if( $shop_code == 81 )
                    {
                        if( preg_match('/^(.+)[\-\_\s]([^\-\_\s]+)$/',$_option,$matches) )
                        {
                            if( preg_match('/^([^_]+)_.+$/',$matches[1],$matches2) )
                            {
                                $real_name = $matches2[1];
                                $real_option = $matches[2];
                            }
                        }
                    }
                    
                    // 옥선 지마켓
                    else if( $shop_code == 1 || $shop_code == 2  )
                    {
						if( preg_match('/^.+\)(.+)\:(.+)$/',$_option,$matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
						else if( preg_match('/^(.+)\:(.+)$/',$_option,$matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
                    }                    
                    
                    
                    // 나머지 판매처
                    else
                    {
                        if( preg_match('/[^:]+:(.+)/',$_option,$matches) )
                            $_option = $matches[1];
                        else
                            $_option = preg_replace('/^┗\(추가상품\)/','',$_option);
                        
                        $_option = trim($_option);
                        
                        if( preg_match('/([^_\-]+)[_\-](.+)\(([^\(\)]+)\)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[3];
                        }
                        else if( preg_match('/([^_\-]+)[_\-](.+)[_\-]([^_\-]+)/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[3];
                        }
                    }

                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$real_name\_%' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
				
                //#################################
                //      b1212b 자동매칭 //2014_05_14 이예나 요청철회
                //#################################
                /*
                if( (_DOMAIN_ == 'b1212b' ) && ($shop_code==2 || $shop_code==102 || $shop_code==50 || $shop_code==30 || $shop_code==230 || $shop_code==51 || $shop_code==151 || $shop_code==251  || $shop_code==53|| $shop_code==55  ))
                {
					$_name = $data_pname[product_name];
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
   

                    // 11번가  "선택:(G) 100-CH 메뚜기/ 카키 "
                    if( $shop_code == 50 )
                    {
						if(preg_match('/^.+\:.+\s(.+)\s.+\/\s(.+)?$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
                    }                  
                    
                    // g마켓 // g마켓 #2 //$_option = "(N) 750-MH:다이마루숏챙/ 흰검(WH/Black) ";
                    if( $shop_code == 2 || $shop_code == 102 )
                    {
						if(preg_match('/^.+\s(.+)\:.+\/(.+)\(.+\)?$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/^.+\s(.+)\:.+\/(.+)?$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[2]);
						}
                    }         
                    
                    // 동대문모자
                    if( $shop_code == 30 )
                    {
						if(preg_match('/^.+\s(.+)\s.+$/', $_name, $matches) )
						{
							$real_name = $matches[1];
							if(preg_match('/^\[(.+)\]$/', $_option, $matches) )
								$real_option = $matches[1];
						}
                    }    
                    // 고도몰
                    if( $shop_code == 230 )
                    {
						if(preg_match('/^.+\s(.+)\s.+\s.+\/(.+)\]$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
						else if(preg_match('/^.+\s(.+)\s.+\/(.+)\]$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
                    }  
                    // 샵N
                    if( $shop_code == 51 )
                    {
						if(preg_match('/^.+\s(.+)\s(.+)\s.+\s.+\:(.+)\(.+$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[3]);
						}
						else if(preg_match('/^.+\s.+\s(.+)\s.+\s.+\s.+\/(.+)$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/^.+\s.+\s(.+)\s.+\s.+\/(.+)$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/^.+\s.+\s(.+)\s.+\/(.+)$/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = trim($matches[2]);
						}
                    }

                    // 샵N 머플러
                    if( $shop_code == 251 )
                    {
						if(preg_match('/\s(.+?)-(.+?)\s/', $_name, $matches) )
						{
							$real_name = trim($matches[0]);
							if(preg_match('/\s(.+?)\s.+\s(.+?)$/', $_option, $matches) )
								$real_option = trim($matches[2]).trim($matches[1]);
							else if(preg_match('/\s(.+?)$/', $_option, $matches) )
								$real_option = trim($matches[2]).trim($matches[1]);
						}
                    }
                    // 쿠팡
                    if( $shop_code == 53 )
                    {
						if(preg_match('/\s.+\s(.+?)\s.+\s.+\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/\s.+\s(.+?)\s.+\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }                    
                    // 티몬
                    if( $shop_code == 55 )
                    {
						if(preg_match('/.+\)(.+)-(.+?)\s.+\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1])."-".trim($matches[2]);
							$real_option = trim($matches[3]);
						}
                    }   
                    
                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name%' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                */
                //#################################
                //      masu 자동매칭
                //#################################
                if( (_DOMAIN_ == 'masu' ) && ($shop_code==53 ||$shop_code==55 ||$shop_code==54 ))
                {
					$_name = $data_pname[product_name];
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
   

                    // 쿠팡
                    if( $shop_code == 53 )
                    {
						if(preg_match('/(.+)\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }                  
                    
                    // 티몬
                    if( $shop_code == 55 )
                    {
						if(preg_match('/(.+)\|(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim(trim($matches[2])."/".trim($matches[3]));
						}
						else if(preg_match('/(.+)\|(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim(trim($matches[2])."/".trim($matches[3]));
						}
						else if(preg_match('/(.+)\|(.+)\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim(trim($matches[2])."/".trim($matches[3]));
						}
                    }  
                    // 위메프
                    if( $shop_code == 54 )
                    {
						if(preg_match('/\s(.+)\|(.+)\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."/".trim($matches[3]);
						}
						else if(preg_match('/\_(.+)\|(.+)\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."/".trim($matches[3]);
						}
                    }  



                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$real_name' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                
                //#################################
                //      bilizzard 자동매칭
                //#################################
                if( (_DOMAIN_ == 'bilizzard' ) && ($shop_code==1 ||$shop_code==2 ||$shop_code==50 ||$shop_code==51 ||$shop_code==53 ||$shop_code==54 ||$shop_code==55 ))
                {

//10001	옥션
//10002	G마켓
//10050	11번가
//10051	네이버샵N
//10053	쿠팡(자동)
//10054	위메이크프라이스(자동)
//10055	티켓몬스터(자동)

					$_name = $data_pname[product_name];
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
   

                    // 옥션 //$_option = "옵션1)SL-170스콧:블루150";
                    if( $shop_code == 1 )
                    {
						if(preg_match('/.+\)(.+)\:(.+)([0-9]\d.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    }
                     // 지마켓
                    if( $shop_code == 2 )
                    {
						if(preg_match('/.+\)(.+)\:(.+)([0-9]\d.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/\_(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    }       
                            
                     // 11번가 $_option = "디자인:17)뽀로로에나멜,사이즈:블루150"  ,  "디자인:31_뽀로로모노,사이즈:배색-5";
                    if( $shop_code == 50 )
                    {
						if(preg_match('/.+\)(.+)\,.+\:(.+)([0-9]\d.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/.+_(.+)\,.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }
                    
                    // 샵N $_option = "디자인: 09_뽀로로파스텔light / 컬러/사이즈: 핑크-150";
                    if( $shop_code == 51 )
                    {
						if(preg_match('/.+\_(.+)\/.+컬러\/사이즈\:\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }
                    
                    
                    
					// 쿠팡 $_option = "선택3)SL-150펄프 블루화이트 블루화이트230";
                    if( $shop_code == 53 )
                    {
						if(preg_match('/.+\)(.+)\s.+\s(.+)([0-9]\d.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/.+\)(.+)\s(.+)([0-9]\d.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						
					}
					// 위메프 $_option = "(237928)26. 뽀로로 레이스|핑크 / 7";
                    if( $shop_code == 54 )
                    {
						if(preg_match('/.+\.\s(.+)\|(.+)\/\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
					}
					
                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) < 2)
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                /* //이순화 실장 요청. 잠시보류
                //#################################
                //      laily 자동매칭
                //#################################
                if( (_DOMAIN_ == 'laily' ) && (  $shop_code==55)) //$shop_code==53 || $shop_code==54 ||
                {
					$_name = $data_pname[product_name];
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
   


                    // 쿠팡
                    if( $shop_code == 53 )
                    {
						if( preg_match('/\)(.+)\s(.+)/',$_option,$matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }

                    // 티몬 //위메프x
                    if( $shop_code == 55)// || $shop_code == 54)
                    {
						if( preg_match('/\.(.+)\|(.+)/',$_option,$matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }
                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[ $real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                */                                       
                
                //#################################
                //      qmfhswm7 자동매칭
                //#################################
                if( (_DOMAIN_ == 'qmfhswm7' ) && ($shop_code==50 || $shop_code == 54 || $shop_code == 1|| $shop_code == 2 ))
                {
					$_name = $data_pname[product_name];
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";

                    // 옥션
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
						if(preg_match('/\:(.+)\((.+)\)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
						else if(preg_match('/(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
                    }
                    // 11번가
                    if( $shop_code == 50 )
                    {
						if(preg_match('/:(.+)\((.+)\)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
                    }
                    
                    // 위메프
                    if( $shop_code == 54 )
                    {
						if(preg_match('/\s(.+)\|(.+)\//', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
						else if(preg_match('/\s(.+)\|(.+)\_/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option = $matches[2];
						}
                    }    



                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$real_name' and options='$real_option' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                //#################################
                //      dhslqjal 자동매칭
                //#################################
                if( (_DOMAIN_ == 'dhslqjal' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 ) )
                {
                    $_option = $data[shop_options];

                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        list($opt1, $opt2) = explode(":", $_option);
                        $opt_arr = explode("/", $opt2);
                        if( count($opt_arr) == 3 )
                        {
                            $prd_name = $opt_arr[0];
                            $prd_opt = $opt_arr[1] . "-" . $opt_arr[2];
                        }
                        else
                        {
                            $prd_name = $opt1;
                            $prd_opt = $opt_arr[0] . "-" . $opt_arr[1];
                        }
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        list($opt1, $opt2) = explode(":", $_option);
                        $opt_arr = explode("/", $opt2);
                        if( count($opt_arr) == 3 )
                        {
                            $prd_name = $opt_arr[0];
                            $prd_opt = $opt_arr[1] . "-" . $opt_arr[2];
                        }
                        else
                        {
                            $prd_name = $opt1;
                            $prd_opt = $opt_arr[0] . "-" . $opt_arr[1];
                        }
                    }
                    // 네이버샵N
                    else if( $shop_code == 51 )
                    {
                        $opt_arr = explode("/", $_option);
                        $prd_name = $opt_arr[0];
                        $prd_opt = $opt_arr[1] . "-" . $opt_arr[2];
                    }

                    if( $prd_name > '' && $prd_opt > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name='$prd_name' and options = '[$prd_opt]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                
                
                
                

                //#################################
                //      cocodream 자동매칭
                //#################################
                if( (_DOMAIN_ == 'cocodream' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
                    
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        if( preg_match('/([^\-]+)\-(.+)/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = str_replace( ":", "-", $matches[2]);
                        }
                    }
                    else if( $shop_code == 50 )
                    {
                        if( preg_match('/[^:]+:(.+)/', $_option, $matches) )
                            $_option = $matches[1];
                        
                        if( preg_match('/([^\-]+)\-(.+)/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = str_replace( ":", "-", $matches[2]);
                        }
                    }

                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

				//#################################
                //      jsg 자동매칭
                //#################################
                if( (_DOMAIN_ == 'jsg' ) && ($shop_code==1 || $shop_code==2 || $shop_code==53))
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
                    
                    if( $shop_code == 53)
                    {
                        if( preg_match('/\)\s(.+)\]\s(.+)/', $_option, $matches) )
                        {
							$real_name = $matches[1]."]";
							$real_name .= "-".$matches[2];
                        }                        
                    }
                    else if( $shop_code == 1 )
                    {
                        if(preg_match('/^.+:(.+)\/(.+)\/(.+)$/', $_option, $matches))
                        {
							$real_option = $matches[1];
                        }                        
                    }
                    else if( $shop_code == 2 )
                    {
                        if(preg_match('/^.+:(.+)\/(.+)\/(.+)$/', $_option, $matches))
                        {
							$real_option = $matches[1];
                        }                        
                    }

                    if( $real_name > '' || $real_opion )
                    {
                    	if( $real_name > '' )
                        	$query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' ";
                        else if( $real_option > '')
                        	$query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options = '$real_option' ";

debug("real_name 쿼리 :".$query_prd_match);
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                
                
                //#################################
                //      soramam 자동매칭
                //#################################
                if( (_DOMAIN_ == 'soramam' ) && ($shop_code == 1 || $shop_code == 2 || $shop_code == 102 || $shop_code == 202 || $shop_code == 50 || $shop_code == 51 || $shop_code == 53 || $shop_code == 54 || $shop_code == 55) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";
                    
                    
                    //soramam 10001 자동매칭
					//♥상품선택♥:[140137]-2013-311한국베다돌가루구두 - ♥색상 입력♥color：골드／♥사이즈입력♥size：180 
					//140137
					//:180, :골드
                    if( $shop_code == 1 )
                    {
						if(preg_match('/.+\[(.+)\].+：(.+)／.+：(.+)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option_1 = ":".trim($matches[2]).", :".trim($matches[3]);
							$real_option_2 = ":".trim($matches[3]).", :".trim($matches[2]);
						}
                    }
                    
                    //soramam 10002 자동매칭
					//♥상품선택♥:[140637]-빕핍망사양스판단화/10900원/1개, ♥색상 입력♥color:민트/1개, ♥사이즈입력♥size:200/1개
					//140637
					//:200, :민트
                    else if( $shop_code == 2 || $shop_code == 202 || $shop_code == 102)
                    {
						if(preg_match('/.+\[(.+)\].+:(.+),.+:(.+)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option_1 = ":".trim($matches[2]).", :".trim($matches[3]);
							$real_option_2 = ":".trim($matches[3]).", :".trim($matches[2]);
						}
                    }
                    
  					//soramam 10050 자동매칭
					//♥색상 입력♥size:160,♥사이즈입력♥color:핑크,♥상품선택♥:[140292]-라임라이트꽃단화-1개 (+7900원) 
					//140292
					//:160, :핑크
                    else if( $shop_code == 50 )
                    {
						if( preg_match('/:(.+),.+:(.+),.+\[(.+)\].+/', $_option, $matches) )
						{
							$real_name = $matches[3];
							$real_option_1 = ":".trim($matches[1]).", :".trim($matches[2]);
							$real_option_2 = ":".trim($matches[2]).", :".trim($matches[1]);
						}
                    }
                    
                    else if( $shop_code == 51 )
                    {
						if( preg_match('/:(.+)\/.+:(.+)\/.+\[(.+)\].+/', $_option, $matches) )
						{
							$real_name = $matches[3];
							$real_option_1 = ":".trim($matches[1]).", :".trim($matches[2]);
							$real_option_2 = ":".trim($matches[2]).", :".trim($matches[1]);
						}
					}
                    
                    else if( $shop_code == 54  ||$shop_code == 55)
                    {
						if( preg_match('/\[(.+)\].+\|(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option_1 = ":".trim($matches[2]).", :".trim($matches[3]);
							$real_option_2 = ":".trim($matches[3]).", :".trim($matches[2]);
						}
					}
					
					else if( $shop_code == 53 )
					{					
						if( preg_match('/\[(.+)\].+\s(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = $matches[1];
							$real_option_1 = ":".trim($matches[2]).", :".trim($matches[3]);
							$real_option_2 = ":".trim($matches[3]).", :".trim($matches[2]);
						}
					}

                    if( $real_name > '' && $real_option_1 > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name%' and options='$real_option_1' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                        else
                        {
	                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name%' and options='$real_option_2' ";
	                        $result_prd_match = mysql_query($query_prd_match, $connect);
	                        if( mysql_num_rows( $result_prd_match ) )
	                        {
	                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
	                            
	                            // 찾아낸 매칭 정보를 배열로 만든다.
	                            $arr_match_id  = array();
	                            $arr_match_qty = array();
	    
	                            $arr_match_id[] = $data_prd_match[product_id];
	                            $arr_match_qty[] = $data[qty];
	    
	                            // 매칭한다.
	                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
	                            
	                            continue;
	                        }
                        }
                    }
                }

                //#################################
                //      heayden 자동매칭
                //#################################
                if( (_DOMAIN_ == 'heayden' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";

                    if( $shop_code == 1 || $shop_code == 2 || $shop_code == 50 )
                    {
                        if( preg_match('/^(.+):(.+)\((.+)\)$/', $_option, $matches) )
                        {
                            $real_name = $matches[2];
                            $real_option = $matches[3];
                        }
                    }
                    else if( $shop_code == 51 )
                    {
                        if( preg_match('/^(.+): (.+) \/ (.+): (.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[2];
                            $real_option = $matches[4];
                        }
                    }

                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                //#################################
                //      shezme 자동매칭
                //#################################
                if( (_DOMAIN_ == 'shezme' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==53 || $shop_code==54 || $shop_code==55 ) )
                {
                    $_option = $data[shop_options];
                    $real_name = "";
                    $real_option = "";
                    
                    
                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        if(preg_match('/.+[0-9]\.\s(.+)\:(.+)\+(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                        else if(preg_match('/(.+)\:(.+)\+(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        if(preg_match('/.+[0-9]\.\s(.+)\/(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        if(preg_match('/.+[0-9]\.(.+)\s(.+)\/.+\((.+)\)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                        
						else if(preg_match('/.+[0-9]\.(.+)\s(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						
                    }
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                    	if(preg_match('/.+[0-9]\_(.+)\s\|\s(.+)\/.+\((.+)\)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    	else if(preg_match('/.+[0-9]\_(.+)\s\|\s(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                    	if(preg_match('/(.+)\|(.+)\|.+\((.+)\)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                        else if(preg_match('/(.+)\|(.+)\|(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
                    }
                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) ==1 )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      hotping 자동매칭
                //#################################
                if( (_DOMAIN_ == 'hotping' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==53 || $shop_code==72) )
                {
                    $_option = $data[shop_options];
                    
                    $real_name = "";
                    $real_option = "";

                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        // 01)B2-18 판도라:블랙-2
                        $_option = preg_replace('/^[0-9]+\)/','',$_option);
                        if( preg_match('/^(.+):(.+)$/', $_option, $matches) )
                            $real_name = $matches[1] . "-" . $matches[2];
                    }
                    else if( $shop_code == 50 )
                    {
                        // 상품명:A2-08 니스점프,사이즈:블랙-2
                        if( preg_match('/^(.+):(.+),(.+):(.+)$/', $_option, $matches) )
                            $real_name = $matches[2] . "-" . $matches[4];
                    }
                    else if( $shop_code == 53 )
                    {
                        // 선택13) B2-05 너의결혼식 블랙-2
                        if( preg_match('/^선택[0-9]+\)\s(.+)\s\-?([^\s]+)$/', $_option, $matches) )
                            $real_name = $matches[1] . "-" . $matches[2];
                    }
                    else if( $shop_code == 72 )
                    {
                        // 타입=AA-41 여우코드자켓-네이비-3
                        $real_name = preg_replace('/^[^=]+=\s?/','',$_option);
                    }

                    if( $real_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      dorosiwa 자동매칭
                //#################################
                if( (_DOMAIN_ == 'dorosiwa' ) && ($shop_code==2 || $shop_code==50 || $shop_code==55 )  )
                {
                    $_option = $data[shop_options];
                    
                    $real_name = "";
                    $real_option = "";

                    if( $shop_code == 2 )
                    {
                       if(preg_match('/.+\s(.+)\:(.+)\+(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]).",".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
                    }
                    else if( $shop_code == 50 )
                    {
                       if(preg_match('/.+\s(.+)\/(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]).",".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
                    }
                    else if( $shop_code == 55 )
                    {
                       if(preg_match('/.+\s(.+)\|(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]).",".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
                    }
                    if( $real_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options like '$real_option%'  ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) == 1 )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
                
                
                //#################################
                //      tobbyous  자동매칭
                //#################################
                if( (_DOMAIN_ == 'tobbyous' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==58 || $shop_code==5 || $shop_code==54 || $shop_code==53 || $shop_code==55) )
                {
                    $_option = $data[shop_options];
                    $_name = $data_pname[product_name];
                    
                    $real_name = "";
                    $real_option = "";
					
					if( !(preg_match('/세트/', $_option ) || preg_match('/SET/', $_option ) || preg_match('/set/', $_option )))
					{
						if( $shop_code == 1 || $shop_code == 2 )
						{
							if( preg_match('/\.(.+)/', $_option, $matches) )
		                    {
		                        $real_name = $matches[1];
								$real_name = str_replace(":","_",$real_name);
		                    }
		                    else if( preg_match('/.+:(.+)/', $_option, $matches) )
		                    {
		                        $real_name = $matches[1];
								$real_name = str_replace("+","_",$real_name);
		                    }
		                }
		                else if( $shop_code == 50 )
		                {
		                	if($_name=="┗(추가상품)")
		                		$real_name = $_option;
		                	else
		                	{
		                		if( preg_match('/:(.+)/', $_option, $matches) )
			                    {
			                        $real_name = $matches[1];
			                    }
		                	}
		                }
		                else if( $shop_code == 58 )
		                {
							if( preg_match('/:(.+)\^.+:(.+)/', $_option, $matches) )
	                        {
	                        	if($matches[1] == "free")
	                            	$real_name = $_name."_".$matches[2];
	                            else
	                            	$real_name = $_name."_".$matches[2].$matches[1];
	                        }
		                }
						else if( $shop_code == 5 )
						{
							$real_name = str_replace(" ","",$_name);
							if( preg_match('/:(.+)\/.+:(.+)\s/', $_option, $matches) )
	                        {
	                        	if($matches[1] == "free")
	                            	$real_name = $matches[2];
	                            else
	                            	$real_name = $matches[2].$matches[1];
	                        }
	                        else if( preg_match('/:(.+)\/.+:(.+)/', $_option, $matches) )
	                        {
	                        	if($matches[1] == "free")
	                            	$real_name = $matches[2];
	                            else
	                            	$real_name = $matches[2].$matches[1];
	                        }
						}
						else if( $shop_code == 54 )
						{
							if( preg_match('/_(.+)\|(.+)\//', $_option, $matches) )
	                        {                        
	                        	$real_name = $matches[1]."_".$matches[2];
	                        }
		                }
		                
		                else if( $shop_code ==53 )
						{
			                if( preg_match('/_(.+)\s(.+)/', $_option, $matches) )
		                    {                        
		                    	$real_name = $matches[1]."_".$matches[2];
		                    }
		                }
		                else if( $shop_code ==55 )
						{
			                if( preg_match('/\.(.+)\|(.+)/', $_option, $matches) )
	                        {                        
	                        	$real_name = $matches[1]."_".$matches[2];
	                        }
	                    }
	
	                    if( $real_name > '' )
	                    {
	                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name' ";
	                        $result_prd_match = mysql_query($query_prd_match, $connect);
	                        if( mysql_num_rows( $result_prd_match ) == 1 ) //검색결과가 1일때만//
	                        {
	                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
	                            
	                            // 찾아낸 매칭 정보를 배열로 만든다.
	                            $arr_match_id  = array();
	                            $arr_match_qty = array();
	    
	                            $arr_match_id[] = $data_prd_match[product_id];
	                            $arr_match_qty[] = $data[qty];
	    
	                            // 매칭한다.
	                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
	                            
	                            continue;
	                        }
	                    }
	                }
                }
                
                
                //#################################
                //      diesel01 자동매칭
                //#################################
                if( (_DOMAIN_ == 'diesel01' ) && ($shop_code==51) )
                {
                    $query_prd_name = "select product_name from orders where seq=$data[order_seq]";
                    $result_prd_name = mysql_query($query_prd_name, $connect);
                    $data_prd_name = mysql_fetch_assoc($result_prd_name);
                    
                    $_p_name = $data_prd_name[product_name];
                    $_option = $data[shop_options];

                    $real_name = $_p_name;
                    $real_option = "";

                    if( preg_match('/^.+: (.+)$/', $_option, $matches) )
                        $real_option = $matches[1];

                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='$real_option' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      zangternet 자동매칭
                //#################################
                if( _DOMAIN_ == 'zangternet' )
                {
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        $query_order_info = "select match_code from orders where seq=$data[order_seq]";
                        $result_order_info = mysql_query($query_order_info, $connect);
                        $data_order_info = mysql_fetch_assoc($result_order_info);
                        
                        $data[match_code] = $data_order_info[match_code];
                    }

                    if( $data[match_code] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and brand = '$data[match_code]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      sbs 자동매칭
                //#################################
                if( _DOMAIN_ == 'sbs' )
                {
                    if( $data[match_code] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and product_id = '$data[match_code]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      ilovejchina 자동매칭
                //#################################
                if( _DOMAIN_ == 'ilovejchina' )
                {
                    if( $data[match_code] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and barcode = '$data[match_code]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      ilovej 중국 자동매칭
                //#################################
                if( _DOMAIN_ == 'ilovej' && ($shop_code == 84 || $shop_code == 82) )
                {
                    if( $data[match_code] > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and product_id = '$data[match_code]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      cellogirl 자동매칭
                //#################################
                if( _DOMAIN_ == 'cellogirl' )
                {
                    $_p_name = "";
                    $_options = "";

                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        // 1026도오스키니:블랙26
                        if( preg_match('/^(.+):([^0-9]+)([0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        // 유고분또스커트:다홍M
                        else if( preg_match('/^(.+):([^A-Z]+)([A-Z]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        // 쫀쫀골지라운드티:베이지
                        else if( preg_match('/^(.+):([^0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2];
                        }
                    }

                    // 11번가
                    if( $shop_code == 50 )
                    {
                        // 타입:1042구제헤짐스키니-화이트26
                        if( preg_match('/^.+:(.+)\-([^0-9]+)([0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        else if( preg_match('/^.+:(.+)\-([^A-Z]+)([A-Z]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        else if( preg_match('/^.+:(.+)\-([^0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2];
                        }
                    }
                    
                    // 네이버샵N
                    if( $shop_code == 51 )
                    {
                        // 옵션명: 후라이스기본치마레깅스 / 타입: 블랙
                        if( preg_match('/^.+:\s(.+)\s\/\s.+:\s([^0-9]+)([0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        else if( preg_match('/^.+:\s(.+)\s\/\s.+:\s([^A-Z]+)([A-Z]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        else if( preg_match('/^.+:\s(.+)\s\/\s.+:\s([^0-9]+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2];
                        }
                    }

                    // 티몬
                    if( $shop_code == 55 )
                    {
                        // 01.스티치트임티셔츠|네이비|free
                        if( preg_match('/^[0-9]+\.(.+)\|(.+)\|(.+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2] . "-" . $matches[3];
                        }
                        else if( preg_match('/^[0-9]+\.(.+)\|(.+)$/', $data[shop_options], $matches) )
                        {
                            $_p_name = $matches[1];
                            $_options = $matches[2];
                        }
                    }

                    // 카페24
                    if( $shop_code == 72 )
                    {
                        // 상품명 구하기
                        $query_pname = "select product_name from orders where seq=$data[order_seq]";
                        $result_pname = mysql_query($query_pname, $connect);
                        $data_pname = mysql_fetch_assoc($result_pname);
                        $_p_name = $data_pname['product_name'];

                        // 색상:민트, 사이즈:26
                        if( preg_match('/^.+:(.+),\s.+:(.+)$/', $data[shop_options], $matches) )
                        {
                            $_options = $matches[1] . "-" . $matches[2];
                        }
                        else if( preg_match('/^.+:(.+)$/', $data[shop_options], $matches) )
                        {
                            $_options = $matches[1];
                        }
                    }

                    if( $_p_name > '' && $_options )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and name = '$_p_name' and (options = '[$_options]' or options = '[$_options-free]' )";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }

                //#################################
                //      7dress 자동매칭
                //#################################
                if( _DOMAIN_ == '7dress' )
                {
                    $_p_name = "";
                    $_options = "";
                    
                    if( $shop_code == 68 )
                    {
                        // 상품명 구하기
                        $query_pname = "select product_name from orders where seq=$data[order_seq]";
                        $result_pname = mysql_query($query_pname, $connect);
                        $data_pname = mysql_fetch_assoc($result_pname);
                        $_p_name = $data_pname['product_name'];
                        
                        // 색상 : 오렌지, 사이즈 : L(100)
                        if( preg_match('/^.+\s:\s(.+),\s.+\s:\s(.+)$/', $data[shop_options], $matches) )
                            $_options = $matches[1] . "-" . $matches[2];
                    }

                    if( $_p_name > '' && $_options )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and name = '$_p_name' and options = '[$_options]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                        else
                        {
                            // 옵션 뒤의 괄호 사이즈를 제거
                            $_options = preg_replace('/\([0-9]+\)/', '', $_options);
                            
                            $query_prd_match = "select product_id from products where is_delete=0 and name = '$_p_name' and options = '[$_options]' ";
                            $result_prd_match = mysql_query($query_prd_match, $connect);
                            if( mysql_num_rows( $result_prd_match ) )
                            {
                                $data_prd_match = mysql_fetch_assoc($result_prd_match);
                                
                                // 찾아낸 매칭 정보를 배열로 만든다.
                                $arr_match_id  = array();
                                $arr_match_qty = array();
        
                                $arr_match_id[] = $data_prd_match[product_id];
                                $arr_match_qty[] = $data[qty];
        
                                // 매칭한다.
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                
                                continue;
                            }
                        }
                    }
                }

                //#################################
                //      twinkygirl 자동매칭
                //#################################
                if( (_DOMAIN_ == 'twinkygirl' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 || $shop_code==53 || $shop_code==55) )
                {
                    $_option = $data[shop_options];

                    $real_name = "";
                    $real_option = "";

                    // 옥션, 지마켓
                    if( $shop_code == 1 || $shop_code == 2 )
                    {
                        // 옵션18) FT3012:블랙245/7000원
                        //"선택03) ST5301:핑크(PINK)-235/3000원";
                        if( preg_match('/^.+\)\s(.+):([^0-9]+)([0-9]+)\//', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] .  $matches[3];
                        }
                        // FT1011:누드핑크230/1000원
                        else if( preg_match('/^(.+):([^0-9]+)([0-9]+)\//', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] .  $matches[3];
                        }
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        // 모델명:선택01) FT1034 블랙,사이즈:240
                        // 모델명:선택54) STT301,사이즈:블랙245 
                        // 상품선택:ST1090-레드235 
                        if( preg_match('/^.+\)\s(.+)\s(.+),.+:([0-9]+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] . "-" . $matches[3];
                        }
                    }
                    // 샵N
                    else if( $shop_code == 51 )
                    {
                        // 선택1: FT1303 / 선택2: 블랙250
                        if( preg_match('/^.+:\s(.+)\s\/\s.+:\s([^0-9]+)([0-9]+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] . "-" . $matches[3];
                        }
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        // 선택02) FT1341 그레이-250
                        if( preg_match('/^.+\)\s(.+)\s(.+)\-(.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] . "-" . $matches[3];
                        }
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                        // 선택01) 스완 FT1303|네이비|240
                        if( preg_match('/^.+\)\s.+\s(.+)\|(.+)\|(.+)$/', $_option, $matches) )
                        {
                            $real_name = $matches[1];
                            $real_option = $matches[2] . "-" . $matches[3];
                        }
                    }

                    if( $real_name > '' && $real_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name%' and options='[$real_option]' ";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                }
				//#################################
				//      minem 자동매칭
				//#################################
				if( (_DOMAIN_ == 'minem' ) && ($shop_code==1 || $shop_code==2 || $shop_code==7 || $shop_code==9 || $shop_code==42  || $shop_code==50 || $shop_code==53 || $shop_code == 67  || $shop_code == 70 || $shop_code == 74) )
				{
				    $_option = $data[shop_options];
				    
				    
					// 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
					
				    $real_name = "";
				    $real_option = "";
				
				    // 옥션, 지마켓
				    if( $shop_code == 1 || $shop_code == 2  )
				    {
						if(preg_match('/.+\[(.+)\]:([^0-9]+)([0-9]+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
				    }
				    //gs shop
				    else if( $shop_code == 7)
				    {
						if(preg_match('/.+\/(.+)\/(.+)\,(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
					    else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
					    {
					    	$real_name = trim($matches[1]);
					    	$real_option = str_replace(",", "-", $_option);
					    	$real_option = trim($real_option);
					    }
					}
				    else if( $shop_code == 9  )
				    {
						if(preg_match('/.+:(.+)\_.+\/(.+)\,.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
						else if(preg_match('/.+:(.+)\_.+\_(.+)\,.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
						}
						else if(preg_match('/.+\/.+\/(.+)\/(.+)\/(.+)\-/', $_option, $matches) )
					    {
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
					    }
					    else if(preg_match('/.+\/(.+)\/(.+)\,.+\:(.+)/', $_option, $matches) )
					    {
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
							$real_option = str_replace(" ", "", $real_option);
					    }
						else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							$flag = 1;
						}
						else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							$flag = 1;
						}
						if($real_name >'' && $flag == 1)
						{
							if(preg_match('/.+\:(.+)\,.+\:(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1])."-".trim($matches[2]);
							}
						}
				    }
				    else if( $shop_code == 42  )
				    {
						if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
							$real_name = trim($matches[1]);
						else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
							$real_name = trim($matches[1]);
						if($real_name >'')
						{
							if(preg_match('/.+\:(.+)\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1])."-".trim($matches[2]);
							}
						}
				    }
				    else if( $shop_code == 50  )
				    {
						if(preg_match('/\[(.+)\]([^0-9]+)([0-9]+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							$flag = 1;
						}
						else if(preg_match('/.+\{.+\{(.+)\}/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							$flag = 1;
						}
						if($real_name >'' && $flag == 1)
						{
							if(preg_match('/.+\:(.+)\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1])."-".trim($matches[2]);
							}
						}
					}
					else if( $shop_code == 53  )
					{
						if(preg_match('/.+\{(.+)\}(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/.+\((.+)\)(.+)\/(.+)/', $_option, $matches) )
						{
						 	$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
					}
					else if( $shop_code == 67  )
					{
						if(preg_match('/.+\:(.+)\s\/\s(.+)\<.+\>(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[3]);
							$real_option = trim($matches[1])."-".trim($matches[2]);
						}
					}
					else if( $shop_code == 70  )
					{
						if(preg_match('/(.+)\_.+\_(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
						else if(preg_match('/.+\{(.+)\}/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/(.+)\/(.+)/', $_name, $matches) )
								$real_option = trim($matches[1])."-".trim($matches[2]);	
							else if(preg_match('/(.+)\:(.+)/', $_name, $matches) )
								$real_option = trim($matches[1])."-".trim($matches[2]);	
						}
					}
					else if( $shop_code == 74  )
					{
						if(preg_match('/(.+)\_.+\/(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2])."-".trim($matches[3]);
						}
					}
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '%$real_name%' and options='[$real_option]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      polotown 자동매칭
				//#################################
				if( (_DOMAIN_ == 'polotown' ) && ($shop_code==53 || $shop_code==54 || $shop_code==55) )
				{
				    $_option = $data[shop_options];
					
				    $real_name = "";
				    $real_option = "";
				
				    if( $shop_code == 55  )// 티몬
				    {
						//if(preg_match('/.+\)\s(.+)\s\/\s(.+)\|(.+)/', $_option, $matches) )
						if(preg_match('/.+\)\s(.+)\/(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1])."/".trim($matches[2]);
							$real_option = trim($matches[3]);
						}
				    }
//				    else if( $shop_code == 54 )//위메프
//				    {
//						if(preg_match('/.+\)\s(.+)\s\/\s(.+)\|(.+)/', $_option, $matches) )
//						{
//							$real_name = trim($matches[1])."/".trim($matches[2]);
//							$real_option = trim($matches[3]);
//						}
//					}
					else if( $shop_code == 53 )//쿠팡
					{
						if(preg_match('/^.+\)\s(.+)\s[0-9]{2}\-[0-9]{2}\)(.+)\|(.+)$/', $_option, $matches) )
						{
						 	$real_name = trim($matches[1])."/". trim($matches[2]);
							$real_option = trim($matches[3]);
						}
						else if(preg_match('/^(.+)\s[0-9]{2}\-[0-9]{2}\)(.+)\|(.+)$/', $_option, $matches) )
						{
							$real_name = trim($matches[1])."/". trim($matches[2]);
							$real_option = trim($matches[3]);
						}

					}
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      nanasalon 자동매칭
				//#################################
				if( (_DOMAIN_ == 'nanasalon' ) && ($shop_code==68) )
				{
				    $_option = $data[shop_options];				    
				    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
					
				    $real_name = "";
				    $real_option = "";
				
				    if( $shop_code == 68  )// 티몬
				    {
						$_name = str_replace("<br>","",$_name);
						if(preg_match('/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/\:\s(.+)\,.+\:\s(.+)/', $_option, $matches) )
								$real_option = trim($matches[1])."-".trim($matches[2]);
							else if(preg_match('/\:\s(.+)/', $_option, $matches) )
								$real_option = trim($matches[1]);
							else
								$real_option = "단일상품";
						}
				    }
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      obbang 자동매칭
				//#################################
				if( (_DOMAIN_ == 'obbang' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51  || $shop_code==54) )
				{
				    $_option = $data[shop_options];				    
//				    // 상품명 구하기
//	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
//	                $result_pname = mysql_query($query_pname, $connect);
//	                $data_pname = mysql_fetch_assoc($result_pname);
//					$_name = $data_pname[product_name];
					
					
				    $real_name = "";
				    $real_option = "";
				
				    if( $shop_code == 1 ||  $shop_code == 2  )//
				    {
						if(preg_match('/.+[0-9]\-(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
				    }
				    
				    if( $shop_code == 50 )//
				    {
					    if(preg_match('/.+[0-9]\-(.+)\((.+)\)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
					}
					
				    if( $shop_code == 51 )//
				    {
					    if(preg_match('/.+[0-9]\-(.+)\/.+\:\s(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
					}
					if( $shop_code == 54 )//
				    {
					    if(preg_match('/.+[0-9]\_(.+)\s\|\s(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2])."-".trim($matches[3]);
						}
					}
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      aoh1115 자동매칭
				//#################################
				if( (_DOMAIN_ == 'aoh1115' ) && ($shop_code==1 || $shop_code==2 || $shop_code==50 || $shop_code==51 ) )
				{
				    $_option = $data[shop_options];				    
				    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
					
				    $real_name = "";
				    $real_option = "";
				
					//갤리숄가디건:블랙(black)
				    if( $shop_code == 1 ||  $shop_code == 2  )
				    {
						if(preg_match('/(.+)\:(.+)\(/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
				    }
				    //타입:04베네치아버튼가디건/그레이
				    if( $shop_code == 50 )//
				    {
					    if(preg_match('/.+[0-9]\.(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
					    else if(preg_match('/.+[0-9](.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
						else if(preg_match('/.+\:(.+)\/(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
					}
					
				    if( $shop_code == 51 )//
				    {
				    	if(preg_match('/.+\s.+\s(.+)/', $_name, $matches) )
								$real_name = trim($matches[1]);
					    else if(preg_match('/\[.+\]\s(.+)/', $_name, $matches) )
								$real_name = trim($matches[1]);
						else if(preg_match('/\[.+\](.+)/', $_name, $matches) )
								$real_name = trim($matches[1]);
								
						if(preg_match('/.+\:(.+)/', $_option, $matches) )
						{
								$real_option = trim($matches[1]);
						}
					}
					
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option-FREE]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      going 자동매칭
				//#################################
				if( (_DOMAIN_ == 'going' ) && ($shop_code==55  ) )
				{
				    $_option = $data[shop_options];		
					
					
				    $real_name = "";
				    $real_option = "";
				
					//83V상하세트|화이트+블랙큰쥐돌이/L
				    if( $shop_code == 55 )
				    {
						if(preg_match('/(.+)\|(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
						}
				    }
					
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      maru 자동매칭
				//#################################
				if( (_DOMAIN_ == 'maru' ) && ($shop_code==68  ) )
				{
				    $_option = $data[shop_options];		
									    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
				    $real_name = $_name;
				    $real_option = "";
				
					//색상 : 그레이(모델착용) => 그레이(모델착용)
					//색상 : 핑크, 사이즈 : L = 핑크-L
				    if( $shop_code == 68 )
				    {
				    	if(preg_match('/.+\:\s(.+)\,\s.+\:\s(.+)/', $_option, $matches) )
						{
								$real_option = trim($matches[1]);
								$real_option .="-".trim($matches[2]);
						}
						else if(preg_match('/.+\:\s(.+)/', $_option, $matches) )
						{
								$real_option = trim($matches[1]);
						}
				    }
					
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' AND name   NOT LIKE '%set%' AND name NOT LIKE '%셋트%'AND name NOT LIKE '%세트%'AND name   NOT LIKE '%+%'";

				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				
				//#################################
				//      mumu 자동매칭
				//#################################
				if( (_DOMAIN_ == 'mumu' ) && ($shop_code==72  ) )
				{
				    $_option = $data[shop_options];		
				    
					// 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
	                
					$_name = $data_pname[product_name];
					
				    $real_name = $_name;
				    $real_option = "";
				
					//클래식 숄 가디건
					//컬러=모카
					//컬러=아이보리, 사이즈 =M
				    if( $shop_code == 72 )
				    {				
				    	if($_option == "")
				    		$real_option = "단일상품";
				    	else if(preg_match('/.+\=(.+)\,\s.+\=(.+)/', $_option, $matches) )
						{
								$real_option = trim($matches[1]);
								$real_option .= "-".trim($matches[2]);
						}	
				    	else if(preg_match('/.+\=(.+)/', $_option, $matches) )
						{
								$real_option = trim($matches[1]);
						}
				    }
					
				    if( $real_name > '' && $real_option > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options='[$real_option]' ";

				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      dearjane 자동매칭
				//#################################
				if( (_DOMAIN_ == 'dearjane' ) && ($shop_code==9 || $shop_code==7|| $shop_code==15 || $shop_code==26 || $shop_code == 27 || $shop_code == 38  || $shop_code == 50  || $shop_code == 51 || $shop_code == 55  || $shop_code == 74) )
				{
				    $_option = $data[shop_options];		
									    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
				    $real_name = "";
				    $real_option = "";
				
					//★12컬러!25~30까지★206피치슬림팬츠/D9530
					//타입:206팬츠Black,색상:28
					//디어제인 롯데마트
				    if( $shop_code == 9 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\,.+\:(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
				    }
				    
				    //디어제인 GS이숍
				    //★가을신상!루즈한핏★후리y롱니트코트/D9486
					//후리y롱니트코트,Mustard
				    else if( $shop_code == 7 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\,(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
				    }
				    
					//디어제인 신세계
				    //★가을신상!루즈한핏★후리y롱니트코트/D9486
					//후리y롱니트코트/Mustard
					//D9801양털후드기모트레이닝SET Gray/free
				    else if( $shop_code == 15 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/(.+)\s[^a-zA-Z]+([a-zA-Z]+)\//', $_option, $matches) )
							{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
							}
							else if(preg_match('/.+\s(.+)\//', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
							else if(preg_match('/.+\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
				    }
				    
				    //디어제인 cj
				    //선택01)트임맨투맨원피스9826/Gray
				    else if( $shop_code == 26 )
				    {
							if(preg_match('/.+\).+(.+[0-9]{3})\/(.+)/', $_option, $matches) )
							{
								$real_name = trim($matches[1]);
								$real_option = trim($matches[2]);
							}
				    }
				    //디어제인 하프클럽
				    //D9654 232부엉이니트Red/free
				    else if( $shop_code == 27 )
				    {
						if(preg_match('/(.+)\s[^a-zA-Z]+([a-zA-Z]+)\//', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1		]);
							}
						}
				    }
				    
				    //패션플러스
				    //뜨게양두니트가디건/D9587
				    //<품목번호>14558_D9587_0004<속성>뜨게양두니트가디건 Wine
				    //<품목번호>14558_D9654D9653D9645D9514_0002<속성>D9654 232부엉이니트Red free
				    else if( $shop_code == 38 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\s(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
						else if(preg_match('/.+\>.+\>(.+)\s[^a-zA-Z]+([a-zA-Z]+)\s/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
						else if(preg_match('/.+\>.+\>(.[0-9]+)[^a-zA-Z]+([a-zA-Z]+)\s/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
				    }
				    
				    //11번가
				    else if( $shop_code == 50 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\/.+\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
				    }
				    
				    //스토어팜
				    //타입|컬러: 후레아롱스커트|Black S
				    else if( $shop_code == 51 )
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\|.+\|(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
				    }
				    
				    
				    //티몬
				    //심플라인롱자켓|Grey|FREE
				    //콤비배색세미팬츠|Black|55
				    else if($shop_code == 55)
				    {
				    	if(preg_match('/.+\.(.+)\|(.+)\|/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
				    	else if(preg_match('/(.+)\|(.+)\|/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
						}
					}
					
					
					//아이스타일 24				    
				    else if($shop_code == 74)
				    {
				    	if(preg_match('/.+\/(.+)/', $_name, $matches) )
						{
							$real_name = trim($matches[1]);
							if(preg_match('/.+\/(.+)/', $_option, $matches) )
							{
								$real_option = trim($matches[1]);
							}
						}
					}
				    if( $real_name > '' && $real_option > '' )
				    {
				    	if($shop_code == 9 || $shop_code == 15  || $shop_code == 26 || $shop_code == 27 || $shop_code == 38  || $shop_code == 50  || $shop_code == 51   || $shop_code == 55 || $shop_code == 74)
				    		$options_query = " options='[$real_option]' ";
				    	else if($shop_code == 7)
				    		$options_query = " (options='[$real_option]' OR options='[#$real_option]') ";
				    	
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '%$real_name%' AND  $options_query AND name NOT LIKE '%세트%' AND name NOT LIKE '%set%' AND name NOT LIKE '%SET%'";

				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      bizforbiz 자동매칭
				//#################################
				if( (_DOMAIN_ == 'bizforbiz' ) && ($shop_code==2 || $shop_code==50  || $shop_code==53 || $shop_code==54) )
				{
				    $_option = $data[shop_options];		
					// 상품명 구하기
//	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
//	                $result_pname = mysql_query($query_pname, $connect);
//	                $data_pname = mysql_fetch_assoc($result_pname);
//					$_name = $data_pname[product_name];
					
				    $real_name = "";
				    $real_option = "";
				
					//모델명:IB-601B(블루)	=> IB-601B
				    if( $shop_code == 2 )
				    {
				    	if(preg_match('/.+\:(.+)\(/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
						}
				    }
				    //제품선택:IB-601R-(레드)	=> IB-601B
				    if( $shop_code == 50 )
				    {
				    	if(preg_match('/.+\:(.+)\-/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
						}
						elseif(preg_match('/.+\:(.+)\(/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
						}
				    }
					//모델명:인터비즈 가열식(Steam)가습기 IB-601G(그린)	=> IB-601B
					//ECO PTC 세라믹히터(BFB-728S) 실버 + 블랙(절전형)
					//ECO PTC 세라믹히터(BFB-727) 실버 + 그레이(절전형)
				    if( $shop_code == 53 )
				    {
				    	if(preg_match('/.+\((.+)\)\s/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
						}
				    }
				    //04_(한정특가)_IB-601R					
				    if( $shop_code == 54 )
				    {
				    	if(preg_match('/.+\)\_(.+)/', $_option, $matches) )
						{
								$real_name = trim($matches[1]);
						}
				    }
				    
				    
				    if( $real_name > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '%$real_name'  AND name NOT LIKE '%리퍼제품%'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      7chocola 자동매칭
				//  	68 메샵 체리 50 11번사 2 지마켓 1 옥션 51 스토어
				//		53 쿠팡 54 위메 55 티몬 102 G2 80위사몰 세븐쇼콜라 81 일본해피샵
				//		82 예스아시아 83 이미 84 쿠딩 85 호라 86 대만 155 티몬2
				//#################################
				if( (_DOMAIN_ == '_7chocola' ) && ($shop_code == 1 || $shop_code == 2 || $shop_code == 50 || $shop_code == 51 || $shop_code == 54  || $shop_code == 55  || $shop_code == 80) )
				{
				    $_option = $data[shop_options];		
				    $_name = "";
				    
				    if( $shop_code == 51 || $shop_code == 80)
				    {
						// 상품명 구하기
		                $query_pname = "select product_name from orders where seq=$data[order_seq]";
		                $result_pname = mysql_query($query_pname, $connect);
		                $data_pname = mysql_fetch_assoc($result_pname);	                
						$_name = $data_pname[product_name];
					}
				    $real_name = "";
				    $real_option = "";
					


					//04)러버덕:블랙
					//러버덕:블랙
					//모던블라인:블랙 M
				    if( $shop_code == 1 || $shop_code == 2 )
				    {
				    	if(preg_match('/.+\)(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
						}
						else if(preg_match('/(.+)\:(.+)\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);							
							$real_option = "색상 : ".trim($matches[2]).", 사이즈 : ".trim($matches[3]);
						}
						else if(preg_match('/(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
						}
				    }
				    //스타일:16)글램셀린느_화이트
				    //스타일:롤리폴리 _블랙
				    else if( $shop_code == 50 )
				    {
				    	if(preg_match('/.+\)(.+)\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
						}
						else if(preg_match('/.+\:(.+)\s\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
						}else if(preg_match('/.+\:(.+)\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
						}
				    }
				    
				    
				    //스티치라인 지퍼포인트 야상
				    //색상: 카키
				    else if( $shop_code == 51 )
				    {
				    	if(preg_match('/.+\:\s(.+)/', $_option, $matches) )
						{
							$real_name = $_name;
							$real_option = "색상 : ".trim($matches[1]);
						}
				    }
				    
				    
				    //30★공기처럼 힙라인 보정팬티 | 블랙/Free
				    //30_공기처럼 힙라인 보정팬티 | 블랙/Free
				    else if( $shop_code == 54 )
				    {
				    	if(preg_match('/[0-9]+[★_](.+)\s\|\s(.+)\/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
							if(trim($matches[3]) !="Free" && trim($matches[3]) !="free")
							{
								$real_option = $real_option.", 사이즈 : ".trim($matches[3]);
							}								
						}
				    }
				    //슬림드림 압박보정 긴팔탑|블랙|FREE
				    else if( $shop_code == 55 )
				    {
				    	if(preg_match('/(.+)\|(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = "색상 : ".trim($matches[2]);
							if(trim($matches[3]) !="Free" && trim($matches[3]) !="free")
							{
								$real_option = $real_option.", 사이즈 : ".trim($matches[3]);
							}								
						}
				    }
				    //색상:스킨
				    else if( $shop_code == 80 )
				    {
				    	if(preg_match('/(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = $_name;
							$real_option = "색상 : ".trim($matches[2]);							
						}
				    }
				    
				    
				    
				    if( $real_name > '' && $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '$real_name%' AND  options = '[$real_option]'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      jjangjuk 자동매칭
				//  	53 쿠팡  54 위메프  55 티몬
				//#################################
				if( (_DOMAIN_ == 'jjangjuk' ) && ($shop_code == 53  || $shop_code == 54 || $shop_code == 55 ) )
				{
				    $_option = $data[shop_options];		
				    $_name = "";
				    

				    $real_name = "";
				    $real_option = "";
					
				    if( $shop_code == 53 )
				    {
				    	if(preg_match('/.+\)(.+)\./', $_option, $matches) )
						{
							$real_option = trim($matches[1]);
						}
				    }
				    
				    //puff bs tee (2color)
				    if( $shop_code == 54 )
				    {
				    	if(preg_match('/.+\|\s(.+)\./', $_option, $matches) )
						{
							$real_option = trim($matches[1]);
						}
				    }
				    
				    
				    
				    if( $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0   AND  options = '[$real_option]'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				
				
				
				//#################################
				//      jayoung 자동매칭
				//  	1 옥션 2지마켓 50 11번가
				//#################################
				if( (_DOMAIN_ == 'jayoung' ) && ($shop_code == 1 ||$shop_code == 2 ||$shop_code == 50 ) )
				{
				    $_option = $data[shop_options];		
				    $_name = "";
				    
				    $real_name = "";
				    $real_option = "";
					


					//01)72111-털:브라운-235
				    if( $shop_code == 2 )
				    {
						if(preg_match('/.+\)(.+)\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);	
						}
				    }
				    //모델명:01)72111-털,색상-사이즈:블랙-230
				    else if( $shop_code == 50 )
				    {
						if(preg_match('/.+\)(.+)\,.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);	
						}
				    }
				    
				    if( $real_name > '' && $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' AND  options = '[$real_option]'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				
				
				//#################################
				//      9room 자동매칭
				//  	68 메샵 
				//#################################
				if( (_DOMAIN_ == '9room' ) && ($shop_code == 68 ) )
				{
				    $_option = $data[shop_options];		
				    $_name = "";
				    
				    if( $shop_code == 68 )
				    {
						// 상품명 구하기
		                $query_pname = "select product_name from orders where seq=$data[order_seq]";
		                $result_pname = mysql_query($query_pname, $connect);
		                $data_pname = mysql_fetch_assoc($result_pname);	                
						$_name = $data_pname[product_name];
					}
				    $real_name = "";
				    $real_option = "";
					


					//puff bs tee (2color)
				    if( $shop_code == 68 )
				    {
				    	$real_name = trim($_name);
						//color : Black
						//color : 베이지, size : 250
						if(preg_match('/\:\s(.+)\,.+\:\s(.+)/', $_option, $matches) )
						{
							$real_option = trim($matches[1]).",".trim($matches[2]);	
						}
						else if(preg_match('/\:(.+)/', $_option, $matches) )
						{
							$real_option = trim($matches[1]);
						}
				    }
				    
				    
				    
				    if( $real_name > '' && $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '$real_name%' AND  options = '$real_option'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				
				//#################################
				//      aidy 자동매칭
				//  	72 카페 잠시보류
				//#################################
				if( (_DOMAIN_ == 'aidy' ) && ($shop_code == 72 ) )
				{
				    $_option = $data[shop_options];		
				    $_name = "";
				    
				    if( $shop_code == 72 )
				    {
						// 상품명 구하기
		                $query_pname = "select product_name from orders where seq=$data[order_seq]";
		                $result_pname = mysql_query($query_pname, $connect);
		                $data_pname = mysql_fetch_assoc($result_pname);	                
						$_name = $data_pname[product_name];
					}
				    $real_name = "";
				    $real_option = "";
					


					
				    if( $shop_code == 72 )
				    {
				    	$real_name = trim($_name);
						if(preg_match('/.+\=(.+)[\,\/]\sSize\=(.+)/', $_option, $matches) )
						{
							$real_option = trim($matches[1])."/".trim($matches[2]);	
						}
				    }
				    
				    
				    
				    if( $real_name > '' && $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' AND  options = '[$real_option]'";				        
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      ringkigirl 자동매칭
				//#################################
				if( (_DOMAIN_ == 'ringkigirl' ) && ($shop_code == 1 || $shop_code == 2 || $shop_code == 50  || $shop_code == 55 ) )
				{
				    $_option = $data[shop_options];		
					
				    $real_name = "";
				    $real_option = "";
					//ㄲ):꽈배기허리밴딩-블랙XL

				    if( $shop_code == 1 || $shop_code == 2 || $shop_code == 50)
				    {
				    	if(preg_match('/.+\:(.+)\-(.+[^a-zA-Z])(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
							$real_option .= ",".trim($matches[3]);
						}
				    }
				    //에이미니트가디건|블랙|FREE
				    else if(  $shop_code == 55)
				    {
				    	if(preg_match('/(.+)\|(.+)\|(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
							$real_option = trim($matches[2]);
							$real_option .= ",".trim($matches[3]);
						}
				    }
				    
				    
				    if( $real_name > '' && $real_option > '')
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '$real_name%' AND  options = '$real_option'";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      ims21 자동매칭
				//#################################
				if( (_DOMAIN_ == 'ims21' ) && ($shop_code == 1 || $shop_code == 2 || $shop_code == 50 || $shop_code == 51 || $shop_code == 54  || $shop_code == 55) )
				{
				    $_option = $data[shop_options];		
					
				    $real_name = "";
				    
				    
					//품명:03.온도계O-219WT
				    if( $shop_code == 1  )
				    {
				    	if(preg_match('/.+\.(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
				    }
				    //품명:유리병-베이직
				    else if( $shop_code == 2  )
				    {
				    	if(preg_match('/.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
				    }
				     //품명:유리병-베이직
				     //품명:03.온도계O-219WT
				    else if( $shop_code == 50 || $shop_code == 51  )
				    {
				    	if(preg_match('/.+\:.+\.(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
						else if(preg_match('/.+\:(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
				    }
				    
				    //02_헬로키티 전자저울 KS-251KP				    
				    //02_전자저울 KS-251KP
				    //18_동물모양가위바위보반찬컵
				    else if( $shop_code == 54 )
				    {
				    	if(preg_match('/.+\s.+\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
						else if(preg_match('/.+\s(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
						else if(preg_match('/.+\_(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
					}
				    
				    //B.주먹밥메이커
				    //메추리알모양틀
				    else if( $shop_code == 55 )
				    {
				    	if(preg_match('/.+\.(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
				    	else if(preg_match('/(.+)/', $_option, $matches) )
						{
							$real_name = trim($matches[1]);
						}
					}
					
				    if( $real_name > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name LIKE '%$real_name%' ";
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				//#################################
				//      expaid 자동매칭
				//#################################
				if( _DOMAIN_ == 'expaid' )
				{
				    $_option = $data[shop_options];				    

				    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);
					$_name = $data_pname[product_name];
					
				    $real_name = $_name;
				    $real_option = $_option;
										
				    if( $real_name > '' )
				    {
				        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name = '$real_name' and options in ('$real_option')";
debug("expaid 자동매칭 : " . $query_prd_match);
				        $result_prd_match = mysql_query($query_prd_match, $connect);
				        if( mysql_num_rows( $result_prd_match ) == 1 )
				        {
				            $data_prd_match = mysql_fetch_assoc($result_prd_match);
				            
				            // 찾아낸 매칭 정보를 배열로 만든다.
				            $arr_match_id  = array();
				            $arr_match_qty = array();
				
				            $arr_match_id[] = $data_prd_match[product_id];
				            $arr_match_qty[] = $data[qty];
				
				            // 매칭한다.
				            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
				            
				            continue;
				        }
				    }
				}
				

                //#################################
                //      jbstar 자동매칭
                //#################################
                if( _DOMAIN_ == 'jbstar' )
                {
                    $option_str = $data[shop_options];

                    $match_name = "";
                    $match_option = "";
                    $match_product_id = "";
                    
                    // 홈페이지-푸카푸카, 나까마(도매)
                    if( $data[shop_id] == "10168" || $data[shop_id] == "10083" )
                    {
                        $match_product_id = "18692";
                    }
                    // 옥션
                    else if( $shop_code == 1 )
                    {
                        if( preg_match('/^.+:[0-9]+\)(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else if( preg_match('/^.+:(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 지마켓
                    else if( $shop_code == 2 )
                    {
                        if( preg_match('/^.+:(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 인터파크
                    else if( $shop_code == 6 )
                    {
                        $option_str = preg_replace('/호$/', '', $option_str);
                        //if( preg_match('/^.+ \/ (.+)$/', $option_str, $matches) )
                        if( preg_match('/^.+ \/\s[^0-9]+[0-9]\-(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // GS
                    else if( $shop_code == 7 )
                    {
                    	//선택6-해피엔코-G0755-70
                    	//H.또봇-H0098-200
                    	if(preg_match('/^[^0-9]+[0-9]\-(.+)/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else if(preg_match('/^[^0-9]\.(.+)/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else
                        {
	                        $option_str = preg_replace('/호$/', '', $option_str);
	                        $match_option = $option_str;
	                    }
                    }
                    // 롯데닷컴
                    else if( $shop_code == 9 )
                    {
                        $option_str = preg_replace('/,.+:.+$/', '', $option_str);
                        if( preg_match('/^.+:(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 신세계, 이마트
                    else if( $shop_code == 15 || $shop_code == 16 )
                    {
                        if( preg_match('/<옵션>(.+) \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else
                        	$match_option = $option_str;
                    }
                    // 홈플러스
                    else if( $shop_code == 22 )
                    {
                        if( preg_match('/<옵션>(.+)\/[0-9]+ \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else
                        	$match_option = $option_str;
                    }
                    // CJ
                    else if( $shop_code == 26 )
                    {
                        $option_str = preg_replace('/호$/', '', $option_str);
                        $option_str = str_replace('/', '-', $option_str);
                        if( preg_match('/^[0-9]+[_\.\)](.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        else
                            $match_option = $option_str;
                    }
                    // 보리보리
                    else if( $shop_code == 27 )
                    {
                        if( preg_match('/(.+)\/\.?/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // AK
                    else if( $shop_code == 42 )
                    {
                        if( preg_match('/<옵션>.+:(.+) \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 현대홈쇼핑
                    else if( $shop_code == 43 )
                    {
                        //if( preg_match('/<옵션>.+:(.+) \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $option_str;
                    }
                    // YES24
                    else if( $shop_code == 47 )
                    {
                    	//<모델명>.<옵션>겨울왕국-H1034-M [1]
                        if( preg_match('/.+<옵션>(.+) \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $option_str;
                    }
                    // 현대
                    else if( $data[shop_id] == "10080" )
                    {
                        if( preg_match('/<옵션>(.+) \[[0-9]+\]$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 11번가
                    else if( $shop_code == 50 )
                    {
                        //if( preg_match('/^.+:.+\)(.+)$/', $option_str, $matches) )
                        //선택1:장화,선택2:뽀로로-E0037-핑크-160
                        if( preg_match('/^.+선택2\:(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        if(preg_match('/^.+\[.+\](.+)$/', $_option, $matches) && $match_option =='' )
                        	$match_option = $matches[1];
                    }
                    // 샵N
                    else if( $shop_code == 51 )
                    {
                        if( preg_match('/^.+: [0-9]+[\)\_](.+) \/ .+: (.+)$/', $option_str, $matches) )
                            $match_option = $matches[1] . "-" . $matches[2];
                        else if( preg_match('/^.+: (.+) \/ .+: (.+)$/', $option_str, $matches) )
                            $match_option = $matches[1] . "-" . $matches[2];
                        else if( preg_match('/^.+: (.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                        
                        $match_option = str_replace("/","-",$match_option);
                    }
                    // 그루폰
                    else if( $shop_code == 52 )
                    {
                        $option_str = str_replace(' / ', '-', $option_str);
                        $option_str = str_replace('/', '-', $option_str);
                        if( preg_match('/[0-9]+\.\s?(.+)$/', $option_str, $matches) )
                            $match_name = $matches[1];
                    }
                    // 위메프
                    else if( $shop_code == 54 )
                    {
                        if( preg_match('/[0-9]+\.\s(.+)\|(.+)\|(.+)$/', $option_str, $matches) )
                            $match_name = $matches[1] . "|" . $matches[2] . "|" . $matches[3];
                        else if( preg_match('/[0-9]+_(.+)\|(.+)\/(.+)$/', $option_str, $matches) )
                            $match_name = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
                        else if( preg_match('/[0-9]+_(.+)\|(.+)$/', $option_str, $matches) )
                        {
                            if( $matches[2] == "해당없음" )
                                $match_name = $matches[1];
                            else
                                $match_name = $matches[1] . "-" . $matches[2];
                        }
                        else if( preg_match('/[0-9]+_(.+)$/', $option_str, $matches) )
                            $match_name = $matches[1];
                    }
                    // 티몬
                    else if( $shop_code == 55 )
                    {
                    	//36)겨울왕국 컵 [핸들2P-FRO4272]|단품

                        //if( preg_match('/[0-9]+[\)\.](.+)\|(.+)/', $option_str, $matches) )
                        if( preg_match('/[0-9]+\)(.+)\|(.+)/', $option_str, $matches) )
                        {
                        	if($matches[2] =="단품")
                        		$match_name = $matches[1];
                        	else
                            	$match_name = $matches[1] . "-" . $matches[2];
                        }
                    }
                    // 쿠팡
                    else if( $shop_code == 53 )
                    {
                        if( preg_match('/[0-9]+[\)\.](.+\[.+\])\s(.+)/', $option_str, $matches) )
                            $match_name = $matches[1] . "-" . $matches[2];
                    }
                    // 롯데아이몰
                    else if( $shop_code == 57 )
                    {
                        if( preg_match('/^.+:(.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 홈페이지-메이크샵
                    else if( $shop_code == 68 )
                    {
                        if( preg_match('/^.+ : (.+)$/', $option_str, $matches) )
                            $match_option = $matches[1];
                    }
                    // 맘스투데이-피코
                    else if( $data[shop_id] == "10086" )
                    {
                        // 1. 홈런 9부 겨울레깅스 [12신상눈꽃]/레드/7 - 1EA
                        if( preg_match('/^[0-9]+\.\s(.+)\/(.+)\/(.+)호?\s\-\s[0-9]+EA$/', $option_str, $matches) )
                            $match_option = $matches[1] . "-" . $matches[2] . "-" . $matches[3];
                    }
                    
                    
                    $match_option = trim($match_option);
                    $match_name = trim($match_name);

                    if( $match_option > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and options like '$match_option'";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) == 1 )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                        // options 없으면 name 찾는다
                        else
                        {
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$match_option'";
                            $result_prd_match = mysql_query($query_prd_match, $connect);
                            if( mysql_num_rows( $result_prd_match ) == 1)
                            {
                                $data_prd_match = mysql_fetch_assoc($result_prd_match);
                                
                                // 찾아낸 매칭 정보를 배열로 만든다.
                                $arr_match_id  = array();
                                $arr_match_qty = array();
        
                                $arr_match_id[] = $data_prd_match[product_id];
                                $arr_match_qty[] = $data[qty];
        
                                // 매칭한다.
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                
                                continue;
                            }
                            // options, name 없으면 origin 찾는다
                            else
                            {
                                $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and origin like '$match_option'";
                                $result_prd_match = mysql_query($query_prd_match, $connect);
                                if( mysql_num_rows( $result_prd_match ) ==1)
                                {
                                    $data_prd_match = mysql_fetch_assoc($result_prd_match);
                                    
                                    // 찾아낸 매칭 정보를 배열로 만든다.
                                    $arr_match_id  = array();
                                    $arr_match_qty = array();
            
                                    $arr_match_id[] = $data_prd_match[product_id];
                                    $arr_match_qty[] = $data[qty];
            
                                    // 매칭한다.
                                    class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                    
                                    continue;
                                }
                            }
                        }
                    }
                    else if( $match_name > '' )
                    {
                        $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and name like '$match_name'";
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            
                            continue;
                        }
                    }
                    else if( $match_product_id > '' )
                    {
                        // 찾아낸 매칭 정보를 배열로 만든다.
                        $arr_match_id  = array();
                        $arr_match_qty = array();

                        $arr_match_id[] = $match_product_id;
                        $arr_match_qty[] = $data[qty];

                        // 매칭한다.
                        class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                        
                        continue;
                    }
                }

                //#################################
                //      mixxmix, mincee80 자동매칭
                //#################################
                if( (_DOMAIN_ =='mixxmix' || _DOMAIN_ =='mincee80' ) && $data[shop_id]==10072 )
                {
                    $option_str = $data[shop_options];
                    
                    // 옵션
                    $_new_opt = "";
                    foreach( explode(",", $option_str) as $_opt )
                        $_new_opt .= ($_new_opt ? "," : "") . trim(preg_replace('/^[^=]+=/', '', $_opt));

                    // 상품코드
                    preg_match('/^([^\-]+)\-(.+)$/',$data[match_code],$matches);
                    if( $matches[1] && $matches[2] )
                    {
                        if( $matches[2] == '0-0-0' )
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and link_id = '$matches[1]'";
                        else
                        {
                            if( _DOMAIN_ == 'mixxmix' )
                                $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and link_id like '{$matches[1]}-%' and options = '$_new_opt' ";
                            else
                                $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and link_id like '{$matches[1]}-%' and options like '$_new_opt%' ";
                        }
                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            continue;
                        }
                    }
                }

                //#################################
                //      cherryspoon 자동매칭
                //#################################
                if( _DOMAIN_ == 'cherryspoon' && $data[shop_id]==10072 )
                {
                    $option_str = $data[shop_options];
                    
                    // 오매칭 때문에 cherryspoon만 괄호삭제
                    $option_str = preg_replace('/\([^\)]+\)$/', '', $option_str);

                    // 옵션
                    $_new_opt = "";
                    foreach( explode(",", $option_str) as $_opt )
                        $_new_opt .= ($_new_opt ? "," : "") . trim(preg_replace('/^[^=]+=/', '', $_opt));

                    // 상품코드
                    preg_match('/^([^\-]+)\-(.+)$/',$data[match_code],$matches);
                    if( $matches[1] && $matches[2] )
                    {
                        if( $matches[2] == '000A' )
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and link_id = '$matches[1]'";
                        else
                            $query_prd_match = "select product_id from products where is_delete=0 and is_represent=0 and link_id like '{$matches[1]}-%' and options like '$_new_opt%' ";

                        $result_prd_match = mysql_query($query_prd_match, $connect);
                        if( mysql_num_rows( $result_prd_match ) )
                        {
                            $data_prd_match = mysql_fetch_assoc($result_prd_match);
                            
                            // 찾아낸 매칭 정보를 배열로 만든다.
                            $arr_match_id  = array();
                            $arr_match_qty = array();
    
                            $arr_match_id[] = $data_prd_match[product_id];
                            $arr_match_qty[] = $data[qty];
    
                            // 매칭한다.
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            continue;
                        }
                    }
                }

                //#################################
                // 발주서 자동매칭 확인 - cafe24
                if( 
                    ($data[match_code] && $shop_code == 72 && _DOMAIN_ != 'cherryspoon' && _DOMAIN_ !='mixxmix' && _DOMAIN_ !='mincee80') ||
                    ($data[match_code] && $shop_code == 73 && _DOMAIN_ == 'dabagirl2')
                )
                {
                    // cafe24 2.0 버전의 경우
                    if( _DOMAIN_ == 'helloyoonsoo' )
                        $_match_code = substr($data[match_code],0,15) . "-" . $data[match_code];
                    // 뒤가 -0-0-0 으로 끝나면 삭제
                    else if( substr($data[match_code], -6) == '-0-0-0' )
                        $_match_code = substr($data[match_code],0,-6);
                    else
                        $_match_code = $data[match_code];

                    // 매칭코드가 등록된 상품코드인지 확인
                    $query_match_code = "select product_id 
                                               ,options
                                           from products 
                                          where link_id = '$_match_code' and
                                                no_sync = 0 and 
                                                is_delete = 0 and
                                                is_represent = 0";
                    
                    // woosung은 상품명에 "+" 가 들어있으면 자동매칭 안함
                    // 2013-12-19 류재관 요청
                    // mandlly 상품명에 "+" 가 들어있으면 자동매칭 안함
                    // 2013-12-26 이실장 요청
                    if( _DOMAIN_ == 'woosung' || _DOMAIN_ == 'mandlly' )
                    {
                        $query_pname = "select product_name from orders where seq= $data[order_seq]";
                        $result_pname = mysql_query($query_pname, $connect);
                        $data_pname = mysql_fetch_assoc($result_pname);
                        
                        if( strpos($data_pname[product_name], "+") !== false )
                            $query_match_code = " and 0";
                    }
                    
                    $result_match_code = mysql_query($query_match_code, $connect);
                    $_num_rows = mysql_num_rows($result_match_code);

                    if( $_num_rows == 1 )
                    {
                        $data_match_code = mysql_fetch_assoc($result_match_code);
                        if( _DOMAIN_ == 'hummingsuper' )
                        {
                            if($data[shop_options] == "")
                            {
                                // 매칭한다.
                                $arr_match_id = array($data_match_code[product_id]);
                                $arr_match_qty = array($data[qty]);
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                continue;
                            }

                            $opt_str = "";
                            $opt_arr = explode(",", $data_match_code[options]);
                            foreach( $opt_arr as $opt_val )
                            {
                                if( $opt_val == "선택안함" )  continue;
                                
                                $opt_str .= ($opt_str ? ",\s" : "") . ".+=.*" . preg_quote($opt_val) . ".*";
                            }
                            if( preg_match("/" . $opt_str . "/", $data[shop_options], $matches) )
                            {
                                // 매칭한다.
                                $arr_match_id = array($data_match_code[product_id]);
                                $arr_match_qty = array($data[qty]);
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                continue;
                            }
                        }
                        else
                        {
                            // 매칭한다.
                            $arr_match_id = array($data_match_code[product_id]);
                            $arr_match_qty = array($data[qty]);
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            continue;
                        }
                    }
                    else if( $_num_rows == 0 && (_DOMAIN_ == 'storynine' || _DOMAIN_ == 'zangternet' || _DOMAIN_ == 'icecream12' || _DOMAIN_ == 'hummingsuper' || _DOMAIN_ == 'changki77') )
                    {
                        // 매칭코드가 등록된 상품코드인지 확인
                        $query_match_code = "select product_id 
                                                   ,options
                                               from products 
                                              where new_link_id = '$_match_code' and
                                                    no_sync = 0 and 
                                                    is_delete = 0 and
                                                    is_represent = 0";
                        $result_match_code = mysql_query($query_match_code, $connect);
                        $_num_rows = mysql_num_rows($result_match_code);
        
                        if( $_num_rows == 1 )
                        {
                            $data_match_code = mysql_fetch_assoc($result_match_code);
                            // 매칭한다.
                            $arr_match_id = array($data_match_code[product_id]);
                            $arr_match_qty = array($data[qty]);
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            continue;
                        }
                    }
                    
                    if( $_num_rows == 0 && (_DOMAIN_ == 'bymina' || _DOMAIN_ == 'changki77' || _DOMAIN_ == 'dabagirl2' || _DOMAIN_ == 'mkids' ) )
                    {
                        // 매칭코드가 등록된 상품코드인지 확인
                        list($match_code1, $match_code2) = explode("-", $_match_code);

                        if( $match_code1 > '' && $match_code2 > '' )
                        {
                            $query_match_code = "select product_id
                                                       ,options
                                                   from products
                                                  where link_id like '$match_code1%' and
                                                        shop_product_code like '%$match_code2' and
                                                        no_sync = 0 and
                                                        is_delete = 0 and
                                                        is_represent = 0";
                            $result_match_code = mysql_query($query_match_code, $connect);
                            $_num_rows = mysql_num_rows($result_match_code);
            
                            if( $_num_rows == 1 )
                            {
                                $data_match_code = mysql_fetch_assoc($result_match_code);
                                // 매칭한다.
                                $arr_match_id = array($data_match_code[product_id]);
                                $arr_match_qty = array($data[qty]);
                                class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                                continue;
                            }
                        }
                    }
                }
                
                //#################################
                // 발주서 자동매칭 확인 - makeshop
                if( $data[match_code] && $shop_code == 68 )
                {
                    // 뒤가 00000 으로 끝나면 삭제 (M1314-00000000000000)
                    if( substr($data[match_code], -5) == '00000' )
                        list($_match_code, $temp_code) = explode("-",$data[match_code]);
                    else
                        $_match_code = $data[match_code];

                    // lusida 상품명 set 이면 수동매칭
                    $lusida_man = false;
                    if( _DOMAIN_ == 'lusida' )
                    {
                        $query_lusida = "select product_name from orders where seq=$data[order_seq]";
                        $result_lusida = mysql_query($query_lusida, $connect);
                        $data_lusida = mysql_fetch_assoc($result_lusida);
                        
                        if( substr($data_lusida[product_name],0,3) == "Set" )  $lusida_man = true;
                    }
                    
                    if( !$lusida_man )
                    {
                        // 매칭코드가 등록된 상품코드인지 확인
                        $query_match_code = "select product_id from products 
                                              where link_id = '$_match_code' and
                                                    no_sync = 0 and 
                                                    is_delete = 0 and
                                                    is_represent = 0";
    
                        $result_match_code = mysql_query($query_match_code, $connect);
                        if( mysql_num_rows($result_match_code) == 1 )
                        {
                            $data_match_code = mysql_fetch_assoc($result_match_code);
    
                            // 매칭한다.
                            $arr_match_id = array($data_match_code[product_id]);
                            $arr_match_qty = array($data[qty]);
                            class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                            continue;
                        }
                    }
                }
                
                //#################################
                // 발주서 자동매칭 확인 - 고도몰
                if( $data[match_code] && $shop_code == 58 )
                {
                    // 매칭코드가 등록된 상품코드인지 확인
                    $query_match_code = "select product_id 
                                           from products 
                                          where link_id = '$data[match_code]' 
                                            and no_sync = 0 
                                            and is_delete = 0 
                                            and is_represent = 0";
debug("고도몰 자동매칭 : " . $query_match_code);
                    $result_match_code = mysql_query($query_match_code, $connect);
                    if( mysql_num_rows($result_match_code) == 1 )
                    {
                        $data_match_code = mysql_fetch_assoc($result_match_code);

                        // 매칭한다.
                        $arr_match_id = array($data_match_code[product_id]);
                        $arr_match_qty = array($data[qty]);
                        class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 4);
                        continue;
                    }
                }
                
                // 매칭 정보를 찾는다.
                if( $_SESSION[MATCH_OPTION] == 1 && ($shop_code==1 || $shop_code==2 || $shop_code==78 || $shop_code==79 || $shop_code==6 || $shop_code==50 || (_DOMAIN_=='alice' && ($shop_code==68 || $shop_code==51)) ) )
                {
                    $query_match = "select id, qty, auto_count 
                                      from code_match 
                                     where shop_id     = '$data[shop_id]' and 
                                           shop_option = '$data[shop_options]'";
                }
                else if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
                {
				    // 상품명 구하기
	                $query_pname = "select product_name from orders where seq=$data[order_seq]";
	                $result_pname = mysql_query($query_pname, $connect);
	                $data_pname = mysql_fetch_assoc($result_pname);

                    $query_match = "select id, qty, auto_count 
                                      from code_match 
                                     where shop_id     = '$data[shop_id]' and 
                                           shop_code   = '$data[shop_product_id]' and
                                           shop_product_name = '$data_pname[product_name]' and
                                           shop_option = '$data[shop_options]'";
                }
                else
                {
                    $query_match = "select id, qty, auto_count 
                                      from code_match 
                                     where shop_id     = '$data[shop_id]' and 
                                           shop_code   = '$data[shop_product_id]' and
                                           shop_option = '$data[shop_options]'";
                }
debug( "매칭정보찾기($data[qty]) : " . $query_match);
                $result_match = mysql_query( $query_match, $connect );
                $match_count = mysql_num_rows( $result_match );
                
                // 판매처가 옥션(S),지마켓,11번가,인터파크,카페24 아니고, 
                // 주문수량이 2 이상이고, 
                // 매칭 상품이 2개 이상이면 
                // 자동매칭 안한다.
/*
                $shop_type = $data[shop_id] % 100;
                if( ($shop_type!=1 && $shop_type!=2 && $shop_type!=4 && $shop_type!=6 && $shop_type!=50 && $shop_type!=72) &&
                    $data[qty]>1 && $match_count>1 )  continue;
*/
                if( $match_count > 0 )
                {
                    // 찾아낸 매칭 정보를 배열로 만든다.
                    $arr_match_id  = array();
                    $arr_match_qty = array();
                    while( $data_match = mysql_fetch_array( $result_match ) )
                    {
                        $arr_match_id[] = $data_match[id];
                        if( $data_match[auto_count] )
                            $arr_match_qty[] = $data_match[qty] * $data[qty];
                        else
                            $arr_match_qty[] = $data_match[qty];
                    }
                    // 매칭한다.
                    class_order_products::match_product($data[seq], $arr_match_id, $arr_match_qty, 0, 0, 2);
                }
            }
        }
        $val['error'] = 0;

        // Lock End
        $obj_lock = new class_lock(501);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [매칭] 매칭 할 상품들을 표시한다. 
    function get_match_info()
    {
        global $connect, $start, $limit;

        // 매칭목록 정렬순서
        if( $_SESSION[MATCH_SORT] )
            $match_sort = $_SESSION[MATCH_SORT];
        else
            $match_sort = 'shop_id, shop_product_id, shop_options, order_seq';
        
        $query = "select * from order_products where status=0 order by $match_sort limit $start, $limit";
        $result = mysql_query( $query, $connect );
        
        $val['list'] = array();
        while( $data = mysql_fetch_array( $result ) )
        {
            // orders에서 정보를 가져온다.
            $data_order = class_order::get_order( $data[order_seq] );
            
            $val['list'][] = array(
                prd_seq      => $data[seq],
                seq          => $data[order_seq],
                shop_id      => $data[shop_id],
                shop_name    => class_shop::get_shop_name($data[shop_id]),
                product_id   => $data[shop_product_id],
                product_name => $data_order[product_name],
                options      => $data[shop_options],
                qty          => $data[qty],
                memo         => $data_order[memo],
                match_code   => $data[product_id],
                shop_price   => $data[shop_price],
                trans_who    => $data[trans_who],
                marked       => $data[marked],
                order_id     => $data_order[order_id],
                recv_name    => $data_order[recv_name],
                recv_tel     => $data_order[recv_tel] . ' / ' . $data_order[recv_mobile]
            );
        }

        $query = "select count(*) as cnt from order_products where product_id is null";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        $val['count'] = $data[cnt];
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [매칭] 해당 주문이 이미 매칭되었는지 확인한다.
    function check_matched()
    {
        global $connect, $prd_seq;
  
        // 해당 상품을 매칭한다.
        $query = "select status from order_products where seq=$prd_seq";

        $result = mysql_query($query, $connect);
        $data = mysql_fetch_array($result);
        
        if($data[status]==0)
            $val['error'] = 0;
        else
            $val['error'] = 1;
        
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [매칭] [팝업-상품매칭] 원주문 옵션보기 클릭
    function get_org_options()
    {
        global $connect, $seq;
  
        $order_info = class_order::get_order( $seq );
        $val['options'] = $order_info['options'];
        
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [매칭] [팝업-상품매칭] 상품 검색
    function get_product_list()
    {
        global $connect, $name, $options, $select, $start, $limit;
        
        // 조건식 구하기
        $q = "";
        if( $name )
        {
            $name = $name;
            // 공백은 %로 변경
            $name = str_replace(" ","%", $name );
            
            $q = " where name like '%$name%'";
        }
        if( $options )
        {
        	$select = $select;
        	$options = $options;
        	if($select == "option")
        	{
	        	// 공백은 %로 변경
	            $options = str_replace(" ","%", $options );
	            
	            if( $q )
	                $q .= " and options like '%$options%'";
	            else
	                $q = " where options like '%$options%'";
        	}
        	else if($select == "product_id")
        	{
        		if( $q )
	                $q .= " and product_id = '$options'";
	            else
	                $q = " where product_id = '$options'";
        	}
        	else if($select == "brand")
        	{
        		// 공백은 %로 변경
	            $options = str_replace(" ","%", $options );
	            
	            if( $q )
	                $q .= " and brand like '%$options%'";
	            else
	                $q = " where brand like '%$options%'";
        	}
        }
        $q .= ($q?" and ":" where ") . " is_represent=0 and is_delete=0 ";
        
        if( $_SESSION[BALJU_EXCEPT_SOLDOUT] )
            $q .= " and enable_sale=1 ";
        
        // 전체 목록을 가져오기
        if( _DOMAIN_ == 'ilovej' )
            $query = "select * from products" . $q . " order by supply_code, name, product_id limit $start, $limit";
        else
            $query = "select * from products" . $q . " order by supply_code, name, options limit $start, $limit";
        $result = mysql_query( $query, $connect );
        $val['list'] = array();
        while( $data = mysql_fetch_array( $result ) )
        {
            // 재고조회
            if( _DOMAIN_ == '7chocola' )
            {
                $query_stock = "select stock from current_stock where product_id='$data[product_id]' and bad=0 ";
                $result_stock = mysql_query($query_stock, $connect);
                $data_stock = mysql_fetch_assoc($result_stock);
                
                $data[options] = "(재고:" . sprintf("%3d", $data_stock[stock]) . ")  " . $data[options];
            }
            
            $val['list'][] = array(
                product_id   => $data[product_id],
                soldout_img  => ( $data[enable_sale] ? "" : "<img src=" . _IMG_SERVER_ . "/images/soldout.gif>" ),
                product_name => $data[name],
                options      => $data[options],
                shop_price   => $data[shop_price],
                supply_name  => class_supply::get_name($data[supply_code])
            );
        }
        // 전체 수량 구하기
        $query = "select count(*) as cnt from products" . $q;
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        
        $val['count'] = $data[cnt];
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-상품매칭] 매칭 버튼 클릭
    function match_products()
    {
        global $connect, $prd_seq;
        
        // Lock Check
        $obj_lock = new class_lock(502, $prd_seq);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 이미 매칭된 주문인지 확인
        $query = "select status from order_products where seq=$prd_seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val = array();
        if( $data[status] )
            // 이미 매칭된 경우
            $val['error'] = 1;
        else
            // 실제 매칭하는 함수
            $val['error'] = $this->set_match_product();

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-상품매칭] 매칭 실행. call by match_products
    function set_match_product()
    {
        global $connect, $prd_seq, $seq, $shop_id, $shop_product_id, $shop_product_name, $shop_options, $match_product_id, $match_product_qty, $delivery_qty, $match_save, $match_type, $qty, $auto_count;

debug_ms( "[매칭정보원본-상품코드] " . $match_product_id );                                 
debug_ms( "[매칭정보원본-매칭수량] " . $match_product_qty );                                 
debug_ms( "[매칭정보원본-배송수량] " . $delivery_qty );                                 

debug_ms( "[매칭정보원본-매칭상품명] " . $shop_product_name );                                 
debug_ms( "[매칭정보원본-매칭저장] " . $match_save );                                 


        // 매칭정보 디코딩
        $match_product_id = json_decode( stripslashes($match_product_id) );
        $match_product_qty = json_decode( stripslashes($match_product_qty) );
        $delivery_qty = json_decode( stripslashes($delivery_qty) );

        $shop_code = $shop_id % 100;

        // 기존에 매칭정보가 있는지 확인. 있을경우 매칭정보 저장하지 않음
        if( $match_save )
        {
            if( $_SESSION[MATCH_OPTION] == 1 && ($shop_code==1 || $shop_code==2 || $shop_code==6 || $shop_code==50 || (_DOMAIN_=='alice' && ($shop_code==68 || $shop_code==51))) )
                $query = "select * from code_match where shop_id='$shop_id' and shop_option='$shop_options'";
            else if( $_SESSION[MATCH_OPTION] == 2 && array_search($shop_id, explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
            {
			    // 상품명 구하기
                $query_pname = "select product_name from orders where seq=$data[order_seq]";
                $result_pname = mysql_query($query_pname, $connect);
                $data_pname = mysql_fetch_assoc($result_pname);

                $query = "select * from code_match where shop_id='$shop_id' and shop_option='$shop_options' and shop_code='$shop_product_id' and shop_product_name = '$data_pname[product_name]' ";
            }
            else
                $query = "select * from code_match where shop_id='$shop_id' and shop_option='$shop_options' and shop_code='$shop_product_id'";
debug_ms( "[매칭정보원본-매칭확인] " . $query );                                 
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
                $match_save = 0;
        }
        
        if( $match_save )
        {
            // 매칭정보 저장
            for($i=0; $i<count($match_product_id); $i++)
            {
                $query = "insert code_match
                             set id                = '$match_product_id[$i]',
                                 shop_id           = '$shop_id',
                                 shop_code         = '$shop_product_id',
                                 shop_product_name = '$shop_product_name',
                                 shop_option       = '$shop_options',
                                 input_date        = now(),
                                 input_time        = now(),
                                 qty               = $match_product_qty[$i],
                                 worker            = '$_SESSION[LOGIN_NAME]',
                                 match_type        = '$match_type',
                                 auto_count        = $auto_count";
debug_ms( "[매칭정보저장] " . $query );
                if( !mysql_query($query, $connect) )
                {
debug_ms( "[매칭정보저장실패!!] ");
                    $this->event_log( "매칭정보 저장실패 ", $query );
                    return 1;
                }
            }
            
            if( $_SESSION[BASIC_VERSION] == 1 && $_SESSION[STOCK_MANAGE_USE] == 2 )
            {
                // 해당 상품을 매칭한다.
                $query = "select * 
                            from order_products 
                           where status          = 0 and
                                 shop_id         = '$shop_id' and
                                 shop_product_id = '$shop_product_id' and
                                 marked in (0,1,2)";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_array($result) )
                    class_order_products::match_product($data[seq], $match_product_id, $match_product_qty, 0, 1, 1);
            }
            else
            {

                if( $_SESSION[MATCH_OPTION] == 1 && ($shop_code==1 || $shop_code==2 || $shop_code==6 || $shop_code==50 || (_DOMAIN_=='alice' && ($shop_code==68 || $shop_code==51))) )
                {
                    // 해당 상품을 매칭한다.
                    $query = "select * 
                                from order_products 
                               where status          = 0 and
                                     shop_id         = '$shop_id' and
                                     shop_options    = '$shop_options' and
                                     marked in (0,1,2)";
                }
                else if( $_SESSION[MATCH_OPTION] == 2 && array_search($shop_id, explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
                {
				    // 상품명 구하기
				    $pname_seq = array();
	                $query_pname = "select seq 
	                                  from orders 
	                                 where status=0 
	                                   and shop_id=$shop_id 
	                                   and shop_product_id='$shop_product_id' 
	                                   and product_name = '$shop_product_name' ";
debug("매칭 1 : " . $query_pname);
	                $result_pname = mysql_query($query_pname, $connect);
	                while( $data_pname = mysql_fetch_assoc($result_pname) )
	                    $pname_seq[] = $data_pname[seq];

                    // 해당 상품을 매칭한다.
                    $query = "select * 
                                from order_products 
                               where status          = 0 and
                                     shop_id         = '$shop_id' and
                                     shop_product_id = '$shop_product_id' and
                                     shop_options    = '$shop_options' and
                                     order_seq in (" . implode(",",$pname_seq) . ") and
                                     marked in (0,1,2)";
debug("매칭 2 : " . $query);
                }
                else
                {
                    // 해당 상품을 매칭한다.
                    $query = "select * 
                                from order_products 
                               where status          = 0 and
                                     shop_id         = '$shop_id' and
                                     shop_product_id = '$shop_product_id' and
                                     shop_options    = '$shop_options' and
                                     marked in (0,1,2)";
                }
                
                // 만일 매칭 상품이 2개 이상이고, 판매처가 옥션, 옥션S, 지마켓, 인터파크, 11번가, 카페24 아니면
                // 수량이 1 개 인 주문만 자동매칭한다.
/*
                $shopid = $shop_id % 100;
                if( count($match_product_id) > 1 && $shopid!=1 && $shopid!=2 && $shopid!=4 && $shopid!=6 && $shopid!=50 && $shopid!=72 )
                    $query .= " and qty = 1";
*/
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_array($result) )
                    class_order_products::match_product($data[seq], $match_product_id, $match_product_qty, 0, 1, 1);
            }
        }
        // 매칭 정보 저장 안함
        else
            class_order_products::match_product($prd_seq, $match_product_id, $delivery_qty, 1, 0, 3);
        
        return 0;
    }

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-추가옵션 주문관리] 설정 클릭
    function set_option_match()
    {
        global $connect;

        $val = array();
          
        // Lock Check
        $obj_lock = new class_lock(513);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $obj = new class_option_match();
        $val['error'] = $obj->set_option_match();

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-추가옵션 주문관리] 재설정 클릭
    function reset_option_match()
    {
        global $connect;

        $val = array();
          
        // Lock Check
        $obj_lock = new class_lock(514);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $obj = new class_option_match();
        $val['error'] = $obj->reset_option_match();

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-추가옵션 주문관리] 미리보기 클릭
    function get_preview()
    {
        global $connect;

        $obj = new class_option_match();
        echo json_encode( $obj->get_preview() );
    }    

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-추가옵션 주문관리] 옵션 찢기 설정 정보를 가져온다.
    function get_option_set()
    {
        global $connect;

        $obj = new class_option_match();
        echo json_encode( $obj->get_option_set() );
    }    

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-매칭 취소하기] 매칭 내역을 보여준다. 매칭 취소하기위해
    function get_cancel_match_info()
    {
        // 매칭할때, 매칭 정보가 저장됬는지 안됬는지를 확인한다.
        // 매칭한 order_products의 seq 뒤에 .을 붙이고, 0 또는 1을 붙인다.
        // 0이면 매칭정보 저장안함이고, 1이면 매칭정보 저장함을 뜻한다.

        global $connect, $match_list;

        $match_arr  = array();  // json decode 배열
        $match_seq  = array();  // 매칭 이력 seq 배열
        $match_save = array();  // 매칭 이력 저장 여부 배열
        
        $match_arr = json_decode( stripslashes($match_list) );  
        foreach( $match_arr as $match_item )
        {
            // 배열 item이 객체로 들어옴. $match_item->{"seq"  }
            $item_seq   = $match_item->{"seq"  };
            $item_saved = $match_item->{"saved"};
            $match_save[$item_seq] = $item_saved;
            array_push($match_seq, $item_seq);
        }
        $match_str = join(',',$match_seq);

        // 매칭 이력 정보를 구한다.
        $query = "select * from order_products where seq in ($match_str) order by match_date desc";
        $result = mysql_query($query, $connect);
        $val['list'] = array();
        while( $data = mysql_fetch_array( $result ) )
        {
            // 판매처 상품명 구하기
            $data_order = class_order::get_order($data[order_seq]);
            $shop_product_name = $data_order[product_name];
            
            // 매칭된 상품 
            $match_info = "";
            $query_match = "select b.product_id pid,
                                   b.name pname,
                                   b.options poptions,
                                   a.qty a_qty
                              from code_match a,
                                   products b
                             where a.shop_id='$data[shop_id]' and 
                                   a.shop_code='$data[shop_product_id]' and 
                                   a.shop_option='$data[shop_options]' and
                                   a.id = b.product_id";

            // 매칭조건에 상품명 있는 경우.
            if( $_SESSION[MATCH_OPTION] == 2 && array_search($data[shop_id], explode(",", $_SESSION[MATCH_OPTION1_EX])) === false )
                $query_match .= " and a.shop_product_name='$shop_product_name' ";
                
            $result_match = mysql_query($query_match, $connect);
            while( $data_match = mysql_fetch_assoc($result_match) )
                $match_info .= ( $match_info ? "<br>" : "" ) . "[" . $data_match[pid] . "] " . $data_match[pname] . " / " . $data_match[poptions] . " - " . $data_match[a_qty] . "개";
                               
            // 리턴 목록
            $val['list'][] = array(
                seq               => $data[seq],
                shop_id           => $data[shop_id],
                shop_name         => class_shop::get_shop_name($data[shop_id]),
                shop_product_id   => $data[shop_product_id],
                shop_product_name => $shop_product_name,
                shop_options      => $data[shop_options],
                match_info        => $match_info,
                saved             => $match_save[$data[seq]]
            );
        }
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [매칭] [팝업-매칭 취소하기] 매칭 작업을 취소한다.
    function cancel_match_history()
    {
        $val = array();
          
        // 발주(order_products 생성) 프로세스 실행중을 확인한다.
        $val = class_proc_info::is_running(1);

        // 실행중이 아니면
        if( !$val[run] )
        {
            // 발주(order_products 생성) 프로세스 실행중 저장
            if( $val['run_ok'] = class_proc_info::run_proc(1) )
            {
                $obj = new class_order_products();
                $val['error'] = $obj->cancel_match();

                // 발주(order_products 생성) 프로세스 종료
                class_proc_info::end_proc(1);
            }
        }
        echo json_encode( $val );
    }    

    //////////////////////////////////////////////////////////
    // [매칭] 매칭 완료를 확인한다.
    function check_match()
    {
        global $connect;
        
        // 매칭 미완료시 합포 불가
        if( _DOMAIN_ == 'bagper' )
        {
            $query = "select seq from orders where order_status<30";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows($result) > 0 )
                $val['complete'] = 2;
            else
            {
                // order_products에서 status가 0인 주문이 있는지 확인한다.
                $query = "select seq from order_products where status=0";
                $result = mysql_query( $query, $connect );
                if( mysql_num_rows($result) > 0 )
                    $val['complete'] = 2;
                else
                    $val['complete'] = 1;
            }
        }
        else
        {
            // order_products에서 status가 0인 주문이 있는지 확인한다.
            $query = "select seq from order_products where status=0";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows($result) > 0 )
                $val['complete'] = 0;
            else
                $val['complete'] = 1;
        }
        
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [합포] 주문다운로드된 주문 확인
    function check_print() 
    {
        global $connect;
        
        $val = array();
        $val['cnt1'] = 0; 
        $val['cnt2'] = 0; 
        $val['cnt3'] = 0; 
        
        // 주문다운로드2 확인
        $query = "select seq from orders where status=1 and order_cs<>1 and trans_key=2 and pack_lock=0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
        {
            // 발주주문 (주문다운로드2) 배송정보 임시 테이블
            $t = gettimeofday();
            $pack_temp_table = "pack_temp_" . substr($t[sec],-3) . $t[usec];
            $query_pack = "select recv_name, recv_address, recv_tel, recv_mobile from orders where status=1 and order_cs<>1 and trans_key = 2 and ( pack=seq or pack=0 )";
            $query_create = "create table $pack_temp_table engine=memory $query_pack";
            mysql_query($query_create, $connect );

            // trans_key=2 인 주문들끼리 합포 가능한 경우 찾기
            $query = "select count(recv_name) cnt from $pack_temp_table group by recv_name, recv_tel, recv_mobile, recv_address having cnt > 1";
            $result = mysql_query($query, $connect);
            $val['cnt3'] = mysql_num_rows($result);

            // 발주, 접수(trans_key=2 제외) 주문 중에서, 찾기
            $query = "select recv_name, recv_tel, recv_mobile, recv_address from orders where status in (0,1) and order_cs<>1 and trans_key<>2 and (pack=seq or pack=0)";
            $result = mysql_query($query, $connect);
            while($data=mysql_fetch_assoc($result))
            {
                if( _DOMAIN_ == 'jyi' )
                {
                    $query_match = "select count(recv_name) cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address ='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel     ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile  ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address='' and recv_address='$data[recv_address]' and 
                                             ( (recv_name<>''            and recv_name    ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel     ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile  ='$data[recv_mobile]' ) ) ) or
                                           ( recv_tel<>'' and recv_tel='$data[recv_tel]' and 
                                             ( (recv_name   <>''         and recv_name    ='$data[recv_name]'   ) or 
                                               (recv_address<>''         and recv_address ='$data[recv_address]') or 
                                               (length(recv_mobile) >= 7 and recv_mobile  ='$data[recv_mobile]' ) ) )";
                }
                else
                {
                    $query_match = "select count(recv_name) cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address<>'' and recv_address='$data[recv_address]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) )";
                }
                $result_match = mysql_query($query_match, $connect);
                $data_match = mysql_fetch_assoc($result_match);
                if( $data_match[cnt] > 0 )
                    $val['cnt3']++;
            }

            // 임시테이블 삭제
            mysql_query("drop table $pack_temp_table", $connect);
        }

        // 주문다운로드된 주문이 있는지 확인
        $query = "select seq from orders where status=1 and order_cs<>1 and trans_key=1 and pack_lock=0";
        $result = mysql_query($query, $connect);
        if( !$result )
        {
            echo json_encode( $val );
            return;
        }
        
        // 발주주문 배송정보 임시 테이블
        $t = gettimeofday();
        $pack_temp_table = "pack_temp_" . substr($t[sec],-3) . $t[usec];
        $query_pack = "select recv_name, recv_address, recv_tel, recv_mobile from orders where status=0 and order_status=30 group by recv_name, recv_address, recv_tel, recv_mobile";
        $query_create = "create table $pack_temp_table engine=memory $query_pack";
        mysql_query($query_create, $connect );

        // 임시 테이블에 주문다운로드된 주문 추가 - 합포
        $query = "select * from orders where status=1 and order_cs<>1 and pack>0 and seq=pack and trans_key in (0,1) and pack_lock=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_key = "insert $pack_temp_table 
                             set recv_name    = '$data[recv_name]',
                                 recv_address = '$data[recv_address]',
                                 recv_tel     = '$data[recv_tel]',
                                 recv_mobile  = '$data[recv_mobile]'";
            mysql_query($query_key, $connect);
        }
        
        // 임시 테이블에 주문다운로드된 주문 추가 - 단일주문
        $query = "select * from orders where status=1 and order_cs<>1 and pack=0 and trans_key in (0,1) and pack_lock=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_key = "insert $pack_temp_table 
                             set recv_name    = '$data[recv_name]',
                                 recv_address = '$data[recv_address]',
                                 recv_tel     = '$data[recv_tel]',
                                 recv_mobile  = '$data[recv_mobile]'";
            mysql_query($query_key, $connect);
        }
        
        $cnt = 0;
        $pack_enable_arr = array();
        
        // 합포
        $query = "select * from orders where status=1 and order_cs<>1 and pack>0 and seq=pack and trans_key=1 and pack_lock=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 일치 or 검증
            if( _DOMAIN_ == 'jyi' )
            {
                $query_pack_just = "select count(recv_name) cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address<>'' and recv_address='$data[recv_address]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_tel<>'' and recv_tel='$data[recv_tel]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) )";
            }
            else
            {
                $query_pack_just = "select count(recv_name) cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address<>'' and recv_address='$data[recv_address]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) )";
            }
            $result_pack_just = mysql_query($query_pack_just, $connect);
            $data_pack_just = mysql_fetch_assoc($result_pack_just);
            if( $data_pack_just[cnt] > 1 )
            {
                $cnt++;
                $pack_enable_arr[] = $data[seq];
            }
        }

        // 단품
        $query = "select * from orders where status=1 and order_cs<>1 and pack=0 and trans_key=1 and pack_lock=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 일치 or 검증
            if( _DOMAIN_ == 'jyi' )
            {
                $query_pack_just = "select count(recv_name) as cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address<>'' and recv_address='$data[recv_address]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_tel<>'' and recv_tel='$data[recv_tel]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) )";
            }
            else
            {
                $query_pack_just = "select count(recv_name) as cnt
                                      from $pack_temp_table
                                     where ( recv_name<>'' and recv_name='$data[recv_name]' and 
                                             ( (recv_address<>''         and recv_address='$data[recv_address]') or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) ) or
                                           ( recv_address<>'' and recv_address='$data[recv_address]' and 
                                             ( (recv_name   <>''         and recv_name   ='$data[recv_name]'   ) or 
                                               (length(recv_tel   ) >= 7 and recv_tel    ='$data[recv_tel]'    ) or 
                                               (length(recv_mobile) >= 7 and recv_mobile ='$data[recv_mobile]' ) ) )";
            }
            
            $result_pack_just = mysql_query($query_pack_just, $connect);
            $data_pack_just = mysql_fetch_assoc($result_pack_just);
            if( $data_pack_just[cnt] > 1 )
            {
debug("주문다운로드 합포 : $query_pack_just ");
                $cnt++;
                $pack_enable_arr[] = $data[seq];
            }
        }
        $val['cnt1'] = $cnt; 
        
        $_str = "";
        $i = 1;
        foreach( $pack_enable_arr as $seq_val )
        {
            $_str .= $seq_val . ",";
            if($i++ % 5 == 0)  $_str .= "<br>";
        }
        $val['pack_enable_str'] = $_str; 
        
        // 임시테이블 삭제
        mysql_query("drop table $pack_temp_table", $connect);
        
/*
        // 송장합포
        if( $_SESSION[PACK_TRANS] )
        {
            $query = "select seq from orders where status=7 and order_cs<>1";
            $result = mysql_query($query, $connect);
            $val['cnt2'] = mysql_num_rows($result); 
        }
*/
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [매칭] 매칭 정보를 확인한다.
    function confirm_match()
    {
        global $connect;
        
        // Lock Check
        $obj_lock = new class_lock(503);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // order_status가 30인 orders 중에서, order_products가 모두 매칭된 주문을 order_status=40으로한다.
        $query = "select seq from orders where status=0 and order_status=30";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 해당 주문의 order_products에서, 매칭 미완료가 없으면, order_status=40으로 한다
            $query = "select status from order_products where order_seq='$data[seq]' and status=0";
            $result_status = mysql_query( $query, $connect );
            if( mysql_num_rows($result_status) == 0 )
            {
                $query = "update orders set order_status=40 where seq='$data[seq]'";
                mysql_query( $query, $connect );
            }
        }
        
        // Lock End
        $obj_lock = new class_lock(503);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [합포] 합포 작업을 수행한다. 새로 만듦
    function make_pack() 
    {
        global $connect, $hold_type;
        
        // Lock Check
        $obj_lock = new class_lock(503);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 발주시 자동합포 사용안함
        if( $_SESSION[PACK_BALJU_AUTO] )
        {
            $query = "update orders set order_status = 45 where status=0 and order_status=40";
            mysql_query($query, $connect);
            $val['error'] = 0;
            
            // Lock End
            $obj_lock = new class_lock(503);
            if( !$obj_lock->set_end(&$msg) )
            {
                $val['error'] = -9;
                $val['lock_msg'] = $msg;
            }

            echo json_encode( $val );
            return;
        }
        
        // 지마켓 해외배송 다운로드 기록 삭제
        if( _DOMAIN_ == 'gadis' )
        {
            $query_gp = "update orders set trans_key = 0 where status=1 and recv_address like '%G마켓 해외배송물류센터%' ";
            mysql_query($query_gp, $connect);
        }
        
        // 합포 자료
        $pack = array();
        
        // 합포 설정
        // 1.송장상태 주문 포함 여부
        $trans_set = $_SESSION[PACK_TRANS];

        // 2.공급처 고려 여부
        $supply_set = $_SESSION[PACK_DIFF_SUPPLY];
        
        // 3.판매처 고려 여부
        $shop_set = $_SESSION[PACK_DIFF_SHOP];
        
        // 4.품절상품 고려 여부
        $soldout_set = $_SESSION[PACK_SOLDOUT];

/*  2014-11-03 장경희. 필요 없는 부분으로 판단되어 실행 안함.
        // 5.기존 접수상태 주문 trans_no 초기화(주문다운로드2-부분배송 작업 결과 리셋)
        $query = "update orders set trans_no=null where status=1";
        mysql_query($query, $connect);
        
        // 6.주문다운로드2 작업 결과 리셋
        $query = "delete from print_enable";
        mysql_query($query, $connect);
*/
        
        // 임시 테이블 삭제
        mysql_query("truncate table orders_pack", $connect);
        
        // 주문다운로드 제외
        if( $hold_type == 0 || $hold_type == 1 )
            $trans_cond = " status = 1 and trans_key = 0 ";
        else
        {
            // 합포 검사할 자료를 구한다.
            if( $trans_set )
                $trans_cond = " status in (1,7) and trans_key in (0,1) ";
            else
                $trans_cond = " status = 1 and trans_key in (0,1) ";
        }
debug("합포단계 1");        
        $query = "select * from orders 
                   where (status=0 and order_status>=40) or 
                         ( $trans_cond and 
                           pack_lock=0 and 
                           (seq=pack or pack=0) ) 
                   order by seq";

        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array($result) )
        {
            $new_add = str_replace(' ', '', $data[recv_address] );
            
            // 합포인 경우 전체취소이면 넘어간다.
            if( $data[pack] > 0 )
            {
                $query_cancel = "select seq from orders where pack=$data[pack] and status<7 and order_cs not in (1,3)";
                $result_cancel = mysql_query($query_cancel, $connect);
                if( mysql_num_rows($result_cancel) == 0 )
                    continue;
            }
            else
            {
                if( $data[status] == 1 && ($data[order_cs] == 1 || $data[order_cs] == 3) )
                    continue;
            }

            // 지마켓 해외배송 합포
            if( $data[shop_id] % 100 == 2 )
            {
                if( $_SESSION[PACK_GMARKET_OVERSEA] != 1 && strpos( $new_add, "G마켓해외배송물류센터" ) > 0 )
                {
                    if( $_SESSION[PACK_GMARKET_OVERSEA] == 2 || _DOMAIN_ == 'realcoco' || _DOMAIN_ == 'rj2' || _DOMAIN_ == 'gadis' || _DOMAIN_ == 'muras' || _DOMAIN_ == 'tobbyous' )
                    {
                        $new_add = 'G마켓해외배송물류센터';
                        $data[recv_name] = 'G마켓해외배송';
                        $data[recv_tel] = '1234567';
                        $data[recv_mobile] = '1234567';
                    }
                    else
                        $new_add = substr($new_add,0,-11);
                }
            }
            // 지마켓 일본 합포
            else if( $data[shop_id] % 100 == 17 )
            {
                if( $_SESSION[PACK_GMARKET_JAPAN] )
                {
                    $new_add = '지마켓일본';
                    $data[recv_name] = '지마켓일본';
                    $data[recv_tel] = '070-8789-5459';
                    $data[recv_mobile] = '070-8789-5459';
                }
            }
            // 지마켓 싱가폴 합포
            else if( $data[shop_id] % 100 == 13 )
            {
                if( $_SESSION[PACK_GMARKET_SINGAPORE] )
                {
                    $new_add = '지마켓싱가폴';
                    $data[recv_name] = '지마켓싱가폴';
                    $data[recv_tel] = '070-8789-5459';
                    $data[recv_mobile] = '070-8789-5459';
                }
            }
            // 오가게 일본배송 합포
            // 2014-03-07 장경희. 오가게(통합) 일본배송 합포 
            // 2014-11-04 장경희. 하프클럽 추가.
            else if( $data[shop_id] % 100 == 76 || $data[shop_id] % 100 == 70 || $data[shop_id] % 100 == 27 )
            {
                // 2014-02-13 장경희. 오가게 일본배송 수령자가 "김재규(일본)"에서 "㈜크로스마켓(일본제"으로 변경. wifky
                // 2014-03-04 장경희. 오가게 일본배송 수령자가 "㈜크로스마켓(일본제"에서 "크로스마켓" 포함으로 변경. wifky
                if( $_SESSION[PACK_OGAGE_JAPAN] && ( strpos($data[recv_name],"크로스마켓") !== false ) )
                    $new_add = substr($new_add,0,-16);
            }
            // 11번가 해외배송 합포
            else if( $data[shop_id] % 100 == 50 )
            {
                if( $data[recv_name] == "글로벌통합배송지" )
                {
                    // 주소 앞에 주문번호가 포함된 경우가 있음
                    $new_add = "경기도파주시광탄면창만리149-6번지전세계[EMS]배송담당자";

                    // 고객별 합포
                    if( $_SESSION[PACK_11ST_OVERSEA] )
                        $data[recv_name] = $data[order_name];
                }
            }
            // 롯데닷컴 해외배송 합포
            else if( $data[shop_id] % 100 == 9 )
            {
                /*
                // 해외배송
                if( strpos($data[recv_address], "서울시 강서구 공항동 281번지 대한항공 김포 화물청사 9gate 1층 19번 창고 한진국제특송 롯데닷컴") !== false )
                {
                    $new_add = "서울시강서구공항동281번지대한항공김포화물청사9gate1층19번창고한진국제특송롯데닷컴";
                    if( !$_SESSION[PACK_LOTTE_OVERSEA] )
                    {
                        $data[recv_name] = "해외배송";
                        $data[recv_tel] = "010-1234-1234";
                        $data[recv_mobile] = "010-1234-1234";
                    }
                }
                */
                /*
                //
                // 자문요청사항 2014.12.15 - jk
                // || strpos($data[recv_address], "서울특별시 금천구 가산디지털2로 83 한진구로터미널 가산대리점 3층 (LOTTE-Q10) 롯데닷컴") !== false 
                */
                if( strpos($data[recv_address], "서울특별시 금천구 가산디지털2로 83 한진구로터미널 가산대리점 3층") !== false )
                {
                    $new_add = "서울특별시금천구가산디지털2로83한진구로터미널가산대리점3층(GL)롯데닷컴해외배송센터";
                    if( !$_SESSION[PACK_LOTTE_OVERSEA] )
                    {
                        $data[recv_name]   = "해외배송";
                        $data[recv_tel]    = "010-1234-1234";
                        $data[recv_mobile] = "010-1234-1234";
                    }
                }
                
                // 추가된 부분 - 2014.12.18
                if( strpos($data[recv_address], "서울특별시 금천구 가산디지털2로 83  한진구로터미널 가산대리점 3층") !== false )
                {
                    $new_add = "서울특별시금천구가산디지털2로83한진구로터미널가산대리점3층(GL)롯데닷컴해외배송센터";
                    if( !$_SESSION[PACK_LOTTE_OVERSEA] )
                    {
                        $data[recv_name]   = "해외배송";
                        $data[recv_tel]    = "010-1234-1234";
                        $data[recv_mobile] = "010-1234-1234";
                    }
                }
            }

            // 옥션, 지마켓 방문수령 따로 합포
            if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 2 )
            {
                if( $data[order_type] == '방문수령' )
                    $data[recv_name] .= '방문수령';
            }

            // 지마켓 "복수 배송 정보 확인 요망" => 우편번호를 "주소오류"로 변경
            if( $new_add == "복수배송정보확인요망" )
            {
                $query_zipcode = "update orders set recv_zip='주소오류' where seq=$data[seq]";
                mysql_query($query_zipcode, $connect);
            }
            
            // 공급처별 합포일 경우 공급처를 찢어진 상품별로 확인한다.
            // 공급처가 다른 상품이 있을 경우, $spl = -1 이 되고 이 주문은 합포되지 않는다.
            if( $supply_set )
            {
                $query = "select supply_id from order_products where order_seq='$data[seq]' group by supply_id";
                $result_supply = mysql_query( $query, $connect );
                if( mysql_num_rows($result_supply) > 1 )
                {
                    $spl = -1;
                }
                else
                {
                    $data_supply = mysql_fetch_assoc($result_supply);
                    $spl = $data_supply[supply_id];
                }
            }
            // 공급처그룹이 다른 경우 합포안함
            else if( $_SESSION[PACK_DIFF_SUPPLY_GROUP] )
            {
                $query = "select b.group_id g_id from order_products a, userinfo b where a.order_seq='$data[seq]' and a.supply_id=b.code group by g_id";
                $result_supply = mysql_query( $query, $connect );
                if( mysql_num_rows($result_supply) > 1 )
                {
                    $spl = -1;
                }
                else
                {
                    $data_supply = mysql_fetch_assoc($result_supply);
                    $spl = $data_supply[g_id];
                }
            }
            
            // 상품정보
            $query_products = "select b.* from order_products a, products b where a.order_seq=$data[seq] and a.product_id = b.product_id";
            $result_products = mysql_query($query_products, $connect);
            while( $data_products = mysql_fetch_assoc($result_products) )
            {
                // 품절주문 합포 안함
                if( $soldout_set && $data_products[enable_sale] == 0 )
                {
                    if( $data[status]==0 && $data[order_status]==40 )
                    {
                        $qeury_nopack  = "update orders set order_status=45 where seq = $data[seq]";
                        mysql_query( $qeury_nopack, $connect );
                    }
                    continue 2;
                }
                    
                // 접수 상태의 합포불가상품 합포 안함
                if( $data[status] == 1 && $data_products[pack_disable] == 1 )
                    continue 2;
            }
            
            // 판매처 그룹별 합포
            if( $_SESSION[PACK_DIFF_GROUP] )
            {
                $query_group = "select group_id from shopinfo where shop_id=$data[shop_id]";
                $result_group = mysql_query($query_group, $connect);
                $data_group = mysql_fetch_assoc($result_group);
                $shop_group = $data_group[group_id];
            }
            else
                $shop_group = 0;

            // 안심번호
            $pack_tel = $data[recv_tel];
            $pack_mobile = $data[recv_mobile];
            if( (_DOMAIN_ == 'realcoco' || _DOMAIN_ == 'my4244' || _DOMAIN_ == 'annina' || _DOMAIN_ == 'huiz2' || _DOMAIN_ == 'icos') && substr($pack_tel,0,3) == '050' && substr($pack_mobile,0,3) == '050' )
            {
                $pack_tel = "";
                $pack_mobile = "";
            }
            else if( _DOMAIN_ == 'jbstar' && $data[shop_id] == 10043 )
            {
                $pack_tel = "";
                $pack_mobile = "";
            }
            else
            {
                // 전화번호가 7자리 이하이면 동일여부 비교 안함. 전화번호 대신 seq 넣음.
                //if( strlen($pack_tel) < 7 )  $pack_tel = $data[seq];
                //if( strlen($pack_mobile) < 7 )  $pack_mobile = $data[seq];
            }

            // 은성헬스빌. 배송후교환건은 따로 합포
            if( _DOMAIN_ == 'eshealthvill' && $data[c_seq] > 0 )
                $c_seq = "_C";
            else
                $c_seq = "";
            
            // 10078, 10079 이베이 스마트배송 타 판매처랑 합포불가
            if( $data[shop_id] % 100 == 78 || $data[shop_id] % 100 == 79 )
            {
                $data[recv_name] .= $data[shop_id];
                $pack_tel        .= $data[shop_id];
                $pack_mobile     .= $data[shop_id];
                $new_add         .= $data[shop_id];
            }

            // dandi 10080,10082,10087,10085,10088 주문번호별 합포
            // 2014-07-09 황실장 요청
            if( (_DOMAIN_ == 'dandi') && ($data[shop_id] == 10080 || $data[shop_id] == 10082 || $data[shop_id] == 10087 || $data[shop_id] == 10085 || $data[shop_id] == 10088) )
            {
                $data[recv_name] .= $data[order_id];
                $pack_tel        .= $data[order_id];
                $pack_mobile     .= $data[order_id];
                $new_add         .= $data[order_id];
            }

            // polotown 위메프 딜별,주문별로만 합포.
            // 2014-07-10 천자문 요청
            if( _DOMAIN_ == 'polotown' )
            {
                // 위메프
                if($data[shop_id] % 100 == 54) 
                {
                    $data[recv_name] .= $data[order_id].$data[shop_product_id];
                    $pack_tel        .= $data[order_id].$data[shop_product_id];
                    $pack_mobile     .= $data[order_id].$data[shop_product_id];
                    $new_add         .= $data[order_id].$data[shop_product_id];
                }
                // 티몬
                // 2014-07-14 천자문 요청
                else if($data[shop_id] % 100 == 55) 
                {
                    $data[recv_name] .= $data[order_id];
                    $pack_tel        .= $data[order_id];
                    $pack_mobile     .= $data[order_id];
                    $new_add         .= $data[order_id];
                }
            }

            // digue는 메이크샵일 경우 주문번호가 같은 주문만 합포
            if( (_DOMAIN_ == 'digue' ) && $data[shop_id] % 100 == 68 )
            {
                $data[recv_name] .= $data[order_id];
                $pack_tel        .= $data[order_id];
                $pack_mobile     .= $data[order_id];
                $new_add         .= $data[order_id];
            }

            // friendmall 나들가게(10098) 경우 합포 안함
            if( (_DOMAIN_ == 'friendmall' ) && $data[shop_id] == 10098 )
            {
                $data[recv_name] .= $data[seq];
                $pack_tel        .= $data[seq];
                $pack_mobile     .= $data[seq];
                $new_add         .= $data[seq];
            }

            // 쿠팡일 경우 딜/합포장번호 같은 주문만 합포
            if( !$_SESSION[PACK_COUPANG] && $data[shop_id] % 100 == 53 )
            {
                $data[recv_name] .= $data[shop_id].$data[code2].$data[order_id_seq];
                $pack_tel        .= $data[shop_id].$data[code2].$data[order_id_seq];
                $pack_mobile     .= $data[shop_id].$data[code2].$data[order_id_seq];
                $new_add         .= $data[shop_id].$data[code2].$data[order_id_seq];
            }

            // 티몬일 경우 딜번호 같은 주문만 합포
            if( !$_SESSION[PACK_TMON] && $data[shop_id] % 100 == 55 )
            {
                $data[recv_name] .= $data[shop_id].$data[order_id_seq];
                $pack_tel        .= $data[shop_id].$data[order_id_seq];
                $pack_mobile     .= $data[shop_id].$data[order_id_seq];
                $new_add         .= $data[shop_id].$data[order_id_seq];
            }

            // helloyoonsoo 주문시간 동일할 경우만 합포
            if( _DOMAIN_ == 'helloyoonsoo' )
            {
                $data[recv_name] .= $data[order_date].$data[order_time];
                $pack_tel        .= $data[order_date].$data[order_time];
                $pack_mobile     .= $data[order_date].$data[order_time];
                $new_add         .= $data[order_date].$data[order_time];
            }

            // mshop2008 선착불 같은 경우만 합포
            if( _DOMAIN_ == 'mshop2008' )
            {
                $data[recv_name] .= $data[trans_who];
                $pack_tel        .= $data[trans_who];
                $pack_mobile     .= $data[trans_who];
                $new_add         .= $data[trans_who];
            }
            
            // ilovejchina 아이디, 이름, 주소로 합포 => 전화번호만 아이디로, 핸드폰 공백처리
            if( _DOMAIN_ == 'ilovejchina' )
            {
                $pack_tel        = $data[cust_id];
                $pack_mobile     = "";
            }
            
            // alice 발주일 같은 경우만 합포
            // buyclub  발주일 같은 경우만 합포
            if( _DOMAIN_ == 'alice' || _DOMAIN_ == 'buyclub' )
            {
                $data[recv_name] .= $data[collect_date];
                $pack_tel        .= $data[collect_date];
                $pack_mobile     .= $data[collect_date];
                $new_add         .= $data[collect_date];
            }
                        
            // grandnongsan 주문번호가 같으면 합포
            /* //요청취소 2014/05/08 김다은
            if( _DOMAIN_ == 'grandnongsan' )
            {
                $data[recv_name] .= $data[order_id];
                $pack_tel        .= $data[order_id];
                $pack_mobile     .= $data[order_id];
                $new_add         .= $data[order_id];
            }
			*/
			
            // wifky 주소 뒤에 번호 제거
            if( _DOMAIN_ == 'wifky' && ($data[shop_id] == 10476 || $data[shop_id] == 10076) )
            {
                $new_add = preg_replace ( '/\([0-9]+\)/' , '', $new_add );
            }
            
            // jbstar는 판매처별 합포안함. 예외 이마트,신세계는 합포
            if( _DOMAIN_ == 'jbstar' && ($data[shop_id] == 10015 || $data[shop_id] == 10016) )
            {
                $data[shop_id] = "1001516";
            }

            // orders_pack에 넣는다.
            $query_insert = "insert orders_pack
                                set seq       = '$data[seq]',
                                    pack      = '$data[pack]',
                                    status    = '$data[status]',
                                    order_id  = '$data[order_id]',
                                    order_id_seq  = '$data[order_id_seq]',
                                    supply_id = '$spl',
                                    shop_id   = '$data[shop_id]',
                                    name      = '$data[recv_name]{$c_seq}',
                                    tel       = '$pack_tel{$c_seq}',
                                    mobile    = '$pack_mobile{$c_seq}',
                                    address   = '$new_add{$c_seq}',
                                    pack_check= '',
                                    trans_who = '$data[trans_who]',
                                    shop_group= '$shop_group'";
            mysql_query( $query_insert, $connect );
        }
debug("합포단계 2");

        $query_all = "select seq from orders_pack";
        $result_all = mysql_query($query_all, $connect);
        while( $data_all = mysql_fetch_assoc($result_all) )
        {
            // 새로 가져온다.
            $query = "select * from orders_pack where seq=$data_all[seq]";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
// debug( "합포로그 :" . "  [seq]:" . $data[seq] . "  [pack]:" . $data[pack] . "  [status]:" . $data[status] . "  [supply_id]:" . $data[supply_id] . "  [shop_id]:" . $data[shop_id] . "  [name]:" . $data[name] . "  [tel]:" . $data[tel] . "  [mobile]:" . $data[mobile] . "  [address]:" . $data[address] . "  [pack_check]:" . $data[pack_check] . "  [trans_who]:" . $data[trans_who] );
            if( $data[pack] > 0 && $data[seq] != $data[pack] )  continue;

            // 합포검사 쿼리
            $query_pack = "select seq, trans_who from orders_pack use index (orders_pack_idx1) where seq>=$data[seq] and ";
            // 공급처 또는 공급처그룹
            if( $supply_set || $_SESSION[PACK_DIFF_SUPPLY_GROUP] )  $query_pack .= "supply_id='$data[supply_id]' and supply_id <> -1 and ";
            // 판매처
            if( $shop_set )  $query_pack .= "shop_id in ('$data[shop_id]', 10000) and ";
            // 판매처 그룹
            if( $_SESSION[PACK_DIFF_GROUP] )  $query_pack .= "shop_group = '$data[shop_group]' and ";
            
            if( _DOMAIN_ == 'dogpre' )
                $query_pack .= "order_id='$data[order_id]' and order_id_seq = '$data[order_id_seq]' ";
            else
                $query_pack .= "name='$data[name]' and tel = '$data[tel]' and mobile = '$data[mobile]' and address = '$data[address]'";
            
            $result_pack = mysql_query($query_pack, $connect);
            $seq_str = '';
            if( mysql_num_rows($result_pack) > 1 )
            {
                $seq_arr = array();
                $sunbool = false;
                while( $data_pack = mysql_fetch_assoc($result_pack) )
                {
                    $seq_arr[] = $data_pack[seq];
                    
                    // 합포건들 중에 하나라도 선불이면 전부 선불처리
                    if( $data_pack[trans_who] == "선불" )
                        $sunbool = true;
                }
                
                if( $sunbool )
                    $sunbool_str = ", trans_who='선불'";
                else
                    $sunbool_str = "";
                    
                $seq_str = implode( ',', $seq_arr );
                $query_update = "update orders_pack set pack=$data[seq], changed=1 $sunbool_str where seq in ($seq_str)";
                mysql_query( $query_update, $connect );
            }
        }
debug("합포단계 3");

        if( $_SESSION[PACK_GMARKET_OVERSEA] == 2 || _DOMAIN_ == 'realcoco' || _DOMAIN_ == 'rj2' || _DOMAIN_ == 'gadis' || _DOMAIN_ == 'muras' || _DOMAIN_ == 'tobbyous' )
            $query = "select * from orders_pack where (seq=pack or pack=0) ";
        else
            $query = "select * from orders_pack where (seq=pack or pack=0) and locate('G마켓해외배송물류센터',address)=0 ";

        // 11번가 해외배송 합포검증에서 제외. PACK_11ST_OVERSEA
        $query .= " and address<>'경기도파주시광탄면창만리149-6번지전세계[EMS]배송담당자' ";

        // 롯데닷컴 해외배송 합포검증에서 제외. PACK_LOTTE_OVERSEA
        $query .= " and address not like '서울시강서구공항동281번지대한항공김포화물청사9gate1층19번창고한진국제특송롯데닷컴%' ";

        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 합포검증 쿼리
            $query_check = "select seq from orders_pack where ( seq > $data[seq] and (seq=pack or pack=0) ) and status=0 and ";
            // 공급처 또는 공급처그룹
            if( $supply_set || $_SESSION[PACK_DIFF_SUPPLY_GROUP] )  $query_check .= "supply_id='$data[supply_id]' and supply_id <> -1 and ";
            // 판매처
            if( $shop_set )  $query_check .= "shop_id in ('$data[shop_id]', 10000) and ";
            // 판매처 그룹
            if( $_SESSION[PACK_DIFF_GROUP] )  $query_check .= "shop_group = '$data[shop_group]' and ";

            if( _DOMAIN_ == 'jyi' )
            {
                $query_check .= "( ( name<>'' and name='$data[name]' and 
                                     ( (length(tel   ) >= 7 and tel    = '$data[tel]'    ) or 
                                       (length(mobile) >= 7 and mobile = '$data[mobile]' ) or 
                                       (address<>''         and address= '$data[address]') ) ) or
                                   ( address<>'' and address = '$data[address]' and 
                                     ( (name   <>''         and name   = '$data[name]'   ) or 
                                       (length(tel   ) >= 7 and tel    = '$data[tel]'    ) or 
                                       (length(mobile) >= 7 and mobile = '$data[mobile]' ) ) ) or
                                   ( tel<>'' and tel = '$data[tel]' and 
                                     ( (name   <>''         and name   = '$data[name]'   ) or 
                                       (address<>''         and address= '$data[address]') or 
                                       (length(mobile) >= 7 and mobile = '$data[mobile]' ) ) ) )";
            }
            else
            {
                $query_check .= "( ( name<>'' and name='$data[name]' and 
                                     ( (length(tel   ) >= 7 and tel    = '$data[tel]'    ) or 
                                       (length(mobile) >= 7 and mobile = '$data[mobile]' ) or 
                                       (address<>''         and address= '$data[address]') ) ) or
                                   ( address<>'' and address = '$data[address]' and 
                                     ( (name   <>''         and name   = '$data[name]'   ) or 
                                       (length(tel   ) >= 7 and tel    = '$data[tel]'    ) or 
                                       (length(mobile) >= 7 and mobile = '$data[mobile]' ) ) ) )";
            }
            $result_check = mysql_query($query_check, $connect);
            $seq_arr = array();
            while( $data_check = mysql_fetch_assoc($result_check) )
                $seq_arr[] = $data_check[seq];

            if( count($seq_arr) )
            {
                $seq_str = implode( ',', $seq_arr );
                $query_update_pack_check = "update orders_pack set pack_check='$seq_str', changed=1 where seq=$data[seq]";
                mysql_query( $query_update_pack_check, $connect );
            }
        }
debug("합포단계 4");

        $query = "select seq, pack, pack_check, trans_who from orders_pack";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 새로 pack 변경된 경우, 이전에 pack 이었다면, orders에서 해당 pack의 하위 seq 리스트를 가져온다.
            $seq_str = "";
            if( $data[pack] > 0 && $data[seq] != $data[pack] ) 
                class_order::get_pack_seq($data[seq], &$seq_arr, &$seq_str);
            
            // pack 이 아닌경우, 또는 이전에 pack 이 아닌 경우는 자기 자신만.
            if( !$seq_str )
                $seq_str = $data[seq];
            
            $qeury_up  = "update orders set pack='$data[pack]', pack_check='$data[pack_check]', trans_who='$data[trans_who]', order_status=45 where seq in ($seq_str)";
            mysql_query( $qeury_up, $connect );
        }
debug("합포단계 5");

        // 합포 보류 확인.
        $query_hold_check = "select pack from orders where status in (0,1) and pack>0 and hold>0 group by pack";
        $result_hold_check = mysql_query($query_hold_check, $connect);
        while($data_hold_check = mysql_fetch_assoc($result_hold_check) )
        {
            // 합포주문의 보류를 확인한다.
            $pack_hold = 0;
            $query_hold = "select hold from orders where pack = $data_hold_check[pack] and hold>0";

            $result_hold = mysql_query($query_hold, $connect);
            while( $data_hold = mysql_fetch_assoc($result_hold) )
            {
                if( $data_hold[hold] == 4 ){
                    $pack_hold = 4;
                    break;
                }else if( $data_hold[hold] == 5 ){
                    $pack_hold = 5;
                }else if( $data_hold[hold] == 6 && $pack_hold != 5 ){
                    $pack_hold = 6;
                }else if( $data_hold[hold] == 3 && $pack_hold < 3 ){
                    $pack_hold = 3;
                }else if( $data_hold[hold] == 2 && $pack_hold < 2 ){
                    $pack_hold = 2;
                }else if( $data_hold[hold] == 1 && $pack_hold == 0 ){
                    $pack_hold = 1;
                }
            }

            $query_hold = "update orders set hold=$pack_hold where pack = $data_hold_check[pack]";
            mysql_query($query_hold, $connect);
        }
debug("합포단계 6");
        
        // 주문다운로드된 주문과 합포인 경우 보류 설정 안함
        if( $hold_type == 2 )
        {
            $query_trans_key = "select pack from orders where status=1 and pack>0 and order_cs<>1 and trans_key>0 group by pack";
            $result_trans_key = mysql_query($query_trans_key, $connect);
            while( $data_trans_key = mysql_fetch_assoc($result_trans_key) )
            {
                $query_key_check = "select seq from orders where pack='$data_trans_key[pack] and trans_key=0 and order_cs<>1";
                $result_key_check = mysql_query($query_key_check, $connect);
                if( mysql_num_rows($result_key_check) )
                {
                    // 합포주문의 주문다운로드 정보를 0로 한다.
                    $query_key = "update orders set trans_key = 0 where pack = $data_trans_key[pack]";
                    mysql_query($query_key, $connect);
                }
            }
        }
debug("합포단계 7");
        
        // 주문다운로드된 주문과 합포인 경우 보류 설정
        if( $hold_type == 3 )
        {
            $query_trans_key = "select pack from orders where status=1 and pack>0 and order_cs<>1 and trans_key>0 group by pack";
            $result_trans_key = mysql_query($query_trans_key, $connect);
            while( $data_trans_key = mysql_fetch_assoc($result_trans_key) )
            {
                // 합포주문의 주문다운로드 여부를 확인한다.
                $query_key = "select seq, hold from orders where pack = $data_trans_key[pack] and trans_key=0";
                $result_key = mysql_query($query_key, $connect);
                if( $data_key = mysql_fetch_assoc($result_key) )
                {
                    if( $data_key[hold] <= 3 )
                    {
                        $query_key = "update orders set hold=6, trans_key=1 where pack = $data_trans_key[pack]";
                        mysql_query($query_key, $connect);

                        $sql = "insert csinfo 
                                   set order_seq  = '$data_key[seq]',
                                       pack       = '$data_trans_key[pack]',
                                       input_date = now(),
                                       input_time = now(),
                                       writer     = '$_SESSION[LOGIN_NAME]',
                                       cs_type    = '8',
                                       cs_reason  = '',
                                       cs_result  = '0',
                                       content    = '발주자동합포'";
                        mysql_query ( $sql, $connect );
                    }
                }
            }
        }
debug("합포단계 8");

        $val['error'] = 0;

        // Lock End
        $obj_lock = new class_lock(503);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [합포] 합포 목록을 만든다
    function get_pack_list()
    {
        global $connect;

        // 발주시 자동합포 사용안함
        if( $_SESSION[PACK_BALJU_AUTO] || _DOMAIN_ == 'dogpre' )
        {
            $val['error'] = 0;
            echo json_encode( $val );
            
            return;
        }

        // 판매처명 
        $shop_name_arr = array();
        $query = "select shop_id, shop_name from shopinfo";
        $result = mysql_query($query, $connect);
        while($data=mysql_fetch_assoc($result))
            $shop_name_arr[$data[shop_id]] = $data[shop_name];
            
        // 결과
        $val['list'] = array();
                
        // 합포 자료
        $pack = array();
        
        // 합포 목록
        $query = "select * from orders use index (orders_idx11) where ( (status=0 and order_status=45) or status=1 ) and order_cs not in (1,3) and pack_check <> '' order by seq limit 50";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array($result) )
        {
            // 상품리스트를 만든다.
            $pack[] = array(
                seq   => $data[seq],
                shop  => $shop_name_arr[$data[shop_id]],
                pname => $data[product_name],
                qty   => $data[qty],
                add   => $data[recv_address],
                name  => $data[recv_name],
                tel1  => $data[recv_tel],
                tel2  => $data[recv_mobile],
                check => $data[pack_check]
            );
        }

        for($i=0; $i<count($pack); $i++)
        {
            // 합포 기준 주문
            $val['list'][] = array(
                seq   => $pack[$i][seq],
                shop  => $pack[$i][shop],
                pname => $pack[$i][pname],
                qty   => $pack[$i][qty],
                add   => $pack[$i][add],
                name  => $pack[$i][name],
                tel1  => $pack[$i][tel1],
                tel2  => $pack[$i][tel2],
                check => 0,
                hide  => true,
                comp  => '0000'
            );

            // 검증 목록에서 합포있으면, 기준주문만
            $pack_check = $pack[$i][check];
            $check_arr = array();
            $query_pack = "select seq from orders where seq in ($pack_check) and ( pack=0 or seq=pack )";
            $result_pack = mysql_query($query_pack, $connect);
            while( $data_pack = mysql_fetch_assoc($result_pack) )
            {
                $check_arr[] = $data_pack[seq];
            }
            
            for($j=0; $j<count($check_arr); $j++)
            {
                // 합포 목록
                $query = "select * from orders where seq=$check_arr[$j]";
                $result = mysql_query( $query, $connect );
                $data = mysql_fetch_array($result);

                $pack_add =  $data[recv_address];
                $pack_name = $data[recv_name];
                $pack_tel1 = $data[recv_tel];
                $pack_tel2 = $data[recv_mobile];
                
                $comp = '';
                if($pack[$i][add] !=$pack_add ) $comp='1';  else $comp='0';
                if($pack[$i][name]!=$pack_name) $comp.='1';  else $comp.='0';
                if($pack[$i][tel1]!=$pack_tel1) $comp.='1';  else $comp.='0';
                if($pack[$i][tel2]!=$pack_tel2) $comp.='1';  else $comp.='0';

                $val['list'][] = array(
                    seq   => $data[seq],
                    shop  => $shop_name_arr[$data[shop_id]],
                    pname => $data[product_name],
                    qty   => $data[qty],
                    add   => $pack_add,
                    name  => $pack_name,
                    tel1  => $pack_tel1,
                    tel2  => $pack_tel2,
                    check => $pack[$i][seq],
                    hide  => false,
                    comp  => $comp
                );
            }
        }
        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [합포] 강제 합포한다.
    function set_force_pack()
    {
        global $connect, $seq, $check, $hold_type;

        // Lock Check
        $obj_lock = new class_lock(504);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 합포되는 seq list
        $seq_arr = array();
        $query = "select seq from orders where seq=$seq or pack=$seq";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $seq_arr[] = $data[seq];
            
        // pack 수정
        $query = "update orders set pack='$check' where seq='$seq' or pack=$seq or seq='$check'";
        mysql_query( $query, $connect );

        // cs 추가
        $sql = "insert csinfo 
                   set order_seq  = '$seq',
                       pack       = '$check',
                       input_date = now(),
                       input_time = now(),
                       writer     = '$_SESSION[LOGIN_NAME]',
                       cs_type    = '8',
                       cs_reason  = '',
                       cs_result  = '0',
                       content    = '발주강제합포'";
        mysql_query ( $sql, $connect );

        // 선착불 수정
        $query = "select trans_who from orders where pack='$check'";
        $result = mysql_query($query, $connect);

        $sunbool = false;
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[trans_who] == '선불' )
                $sunbool = true;
        }
        
        if( $sunbool )
        {
            $query = "update orders set trans_who='선불' where pack='$check'";
            mysql_query( $query, $connect );
        }
        
        // pack_check 수정
        $query_seq = '';
        foreach( $seq_arr as $seq_each )
            $query_seq .= ($query_seq ? " or " : "") . " pack_check like '%$seq_each%' ";

        $query = "select seq, pack_check from orders where status in (0,1) and pack_check>'' ";
        if( $query_seq )
            $query .= " and ( $query_seq ) ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_array($result) )
        {
            $pack_check = split(',', $data[pack_check]);
            foreach( $seq_arr as $seq_rm )
            {
                if( array_search($seq_rm, $pack_check) !== false )
                    array_splice($pack_check, array_search($seq_rm, $pack_check), 1);
            }
            $check_str = implode( ',', $pack_check );
    
            $query = "update orders set pack_check='$check_str' where seq='$data[seq]'";
            mysql_query($query, $connect);
        }
        
        // 합포된 주문의 pack_check을 reset
        $query = "update orders set pack_check='' where seq in (" . implode(",", $seq_arr) . ")";
        mysql_query($query, $connect);
        
        // 보류설정
        if( $hold_type == 3 )
        {
            $trans_key_f = 0;
            $hold = 0;
            
            $query = "select trans_key, hold from orders where pack='$check'";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( $data[hold] == 4 ){
                    $hold = 4;
                }else if( $data[hold] == 5 && $hold != 4 ){
                    $hold = 5;
                }else if( $data[hold] == 6 && $hold < 4 ){
                    $hold = 6;
                }else if( $data[hold] == 3 && $hold < 3 ){
                    $hold = 3;
                }else if( $data[hold] == 2 && $hold < 2 ){
                    $hold = 2;
                }else if( $data[hold] == 1 && $hold == 0 ){
                    $hold = 1;
                }
                
                if( $data[trans_key] )  $trans_key_f = 1;
            }
            
            // 이미 주문다운로드된 주문이 하나라도 있을경우.
            if( $trans_key_f )
            {
                $hold_option = "";
                if( $hold <= 3 )
                {
                    $hold_option = ", hold=6 ";
                }
                
                $query = "update orders set trans_key=1 $hold_option where pack='$check'";
                mysql_query($query, $connect);
            }
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        $val['error'] = 0;
        echo json_encode( $val );
    }

    //////////////////////////////////////////////////////////
    // [합포] 사은품/무료배송 설정한다.
    function set_gift()
    {
        global $connect;
        
        $val = array();
        
        // Lock Check
        $obj_lock = new class_lock(505);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }
debug("사은품단계 1");

        // 합포 검증 - 발주 상태의 주문과 송장 상태의 주문이 합포된 경우가 있는지 확인
        $query = "select min(seq) min_seq, pack from orders where status=0 and order_status=45 and pack>0 group by pack";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 새 합포 주문 중에 송장 상태의 주문 있는지 확인
            $query_pack = "select count(*) cnt from orders where pack=$data[pack] and status=7";
            $result_pack = mysql_query($query_pack, $connect);
            $data_pack = mysql_fetch_assoc($result_pack);
            
            if( $data_pack[cnt] > 0 )
            {
                // 송장 수량이 1개 이면 합포 아님
                if( $data_pack[cnt] == 1 )
                {
                    $query_reset = "update orders set pack=0 where pack=$data[pack] and status=7";
                    mysql_query($query_reset, $connect);
                }

                // 송장 아닌 주문 수량
                $query_cnt = "select count(*) cnt from orders where pack=$data[pack] and status < 7";
                $result_cnt = mysql_query($query_cnt, $connect);
                $data_cnt = mysql_fetch_assoc($result_cnt);
                if( $data_cnt[cnt] == 1 )  
                    $_pack = 0;
                else
                    $_pack = $data[min_seq];
                
                $query_div = "update orders set pack=$_pack where pack=$data[pack] and status < 7";
                mysql_query($query_div, $connect);
            }
        }
debug("사은품단계 2");
        
        //////////////////////////////////////
        // 옥션 정산가 재계산 
        //////////////////////////////////////
        $query = "select * from orders where status=0 and order_status=45 and shop_id % 100 = 1 order by shop_id, seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 이전 주문과 판매처와 주문번호가 동일하고 code12 값이 0
            if( $old_shop_id == $data[shop_id] && $old_order_id == $data[order_id] && $data[code12] == 0 )
            {
                $price_arr[$data[seq]] = $data[code11];
            }
            else
            {
                // 주문번호 바뀐경우, 이전 자료가 둘 이상의 주문번호인 경우
                $seq_num = count($price_arr);
                if( $seq_num > 1 )
                {
                    // 금액 합
                    $sum_price = 0;
                    foreach($price_arr as $_val)
                        $sum_price += $_val;
                        
                    // 금액 비율로 정산가 재계산
                    $i = 1;
                    $total_price = 0;
                    foreach($price_arr as $key => $_val)
                    {
                        // 마지막 주문인경우 비율 계산 하지 않고, 차이값 뺄셈
                        if( $i == $seq_num )
                        {
                            $new_supply_price = $sum_supply_price - $total_price;
                        }
                        else
                        {
                            $new_supply_price = (int)($sum_supply_price * $_val / $sum_price + 0.5);
                            $total_price += $new_supply_price;
                        }
                        $query_re = "update orders set code12 = $new_supply_price where seq=$key";
                        mysql_query($query_re, $connect);
                        $i++;
                    }
                }

                $price_arr = array();
                $price_arr[$data[seq]] = $data[code11];
                    
                $old_shop_id = $data[shop_id];
                $old_order_id = $data[order_id];
                $sum_supply_price = $data[code12];
            }
        }
debug("사은품단계 3");

        // 맨 마지막 주문에서 한번더 확인
        $seq_num = count($price_arr);
        if( $seq_num > 1 )
        {
            // 금액 합
            $sum_price = 0;
            foreach($price_arr as $_val)
                $sum_price += $_val;
                
            // 금액 비율로 정산가 재계산
            $i = 1;
            $total_price = 0;
            foreach($price_arr as $key => $_val)
            {
                // 마지막 주문인경우 비율 계산 하지 않고, 차이값 뺄셈
                if( $i == $seq_num )
                {
                    $new_supply_price = $sum_supply_price - $total_price;
                }
                else
                {
                    $new_supply_price = (int)($sum_supply_price * $_val / $sum_price + 0.5);
                    $total_price += $new_supply_price;
                }
                $query_re = "update orders set code12 = $new_supply_price where seq=$key";
                mysql_query($query_re, $connect);
                $i++;
            }
        }

        //////////////////////////////////////
        // 정산정보 업데이트
        //////////////////////////////////////
        $obj = new class_statrule2();
        $query = "select * from orders where status=0 and order_status=45";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 정산 - 판매금액, 정산금액
            $stat_info[amount]       = ($data['amount']       ?  $data['amount']       : $obj->get_price2( "amount"      , $data ));
            $stat_info[supply_price] = ($data['supply_price'] ?  $data['supply_price'] : $obj->get_price2( "supply_price", $data ));
            $stat_info[prepay_trans] = $obj->get_price2( "prepay_trans", $data );
            $stat_info[prepay_cnt]   = ( $stat_info[prepay_trans] > 0 ? 1 : 0  );
            $query = "update orders 
                         set amount       = '$stat_info[amount]',
                             supply_price = '$stat_info[supply_price]',
                             prepay_price = '$stat_info[prepay_trans]',
                             prepay_cnt   = '$stat_info[prepay_cnt]'
                       where seq = $data[seq]";
            mysql_query($query, $connect);
        }

        // order_products에서 order_seq를 org_order_seq에 넣는다.
        $query = "update orders a, order_products b
                     set b.org_order_seq = b.order_seq
                   where a.seq = b.order_seq and
                         a.status = 0 and
                         a.order_status = 45";
        mysql_query( $query, $connect );

        // 발주 주문의 정산가
        $query = "select seq, amount, supply_price from orders where status=0 and order_status=45";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_cnt = "select count(*) cnt from order_products where order_seq=$data[seq] and is_gift=0";
            $result_cnt = mysql_query($query_cnt, $connect);
            $data_cnt = mysql_fetch_assoc($result_cnt);
            
            // order_products의 개수가 1 이면 amount, supply_price 그대로
            if( $data_cnt[cnt] == 1 )
            {
                $query_price = "update order_products 
                                   set prd_amount = $data[amount],
                                       prd_supply_price = $data[supply_price] 
                                 where order_seq=$data[seq]";
                mysql_query($query_price, $connect);
            }
            // order_products의 개수가 1 이상이면, 원가 비율로 amount, supply_price 나눔
            else
            {
                // 매칭상품 수량, 원가 가져오기
                $query_prd = "select a.seq a_seq,
                                     a.product_id a_product_id,
                                     a.qty a_qty,
                                     b.org_price b_org_price
                                from order_products a,
                                     products b
                               where a.order_seq = $data[seq] 
                                 and a.product_id = b.product_id ";
                $result_prd = mysql_query($query_prd, $connect);
                
                // 전체원가
                $org_price_total = 0;
                $org_price_arr = array();
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    // 2014-12-19 장경희 . crystal 정산가 0 문제 때문에 수정.
                    // 원가가 0 이면 1로...
                    if( $data_prd[b_org_price] == 0 )  $data_prd[b_org_price] = 1;
                    
                    $org_price_prd = $data_prd[a_qty] * $data_prd[b_org_price];
                    
                    // 각 order_product의 원가배열
                    $org_price_arr[] = array(
                        'seq'       => $data_prd[a_seq],
                        'org_price' => $org_price_prd
                    );
                    
                    // 원가 합
                    $org_price_total += $org_price_prd;
                }
                
                $amount_total = 0;
                $supply_price_total = 0;
                $cnt = count($org_price_arr);
                for( $i=0; $i<$cnt; $i++ )
                {
                    // 마지막일 경우는 전체에서 나머지 금액 빼기
                    if( $i == $cnt - 1 )
                    {
                        $amount_prd = $data[amount] - $amount_total;
                        $supply_price_prd = $data[supply_price] - $supply_price_total;
                    }
                    // 마지막 아니면, 원가 비율로
                    else
                    {
                        $amount_prd = (int)($data[amount] * $org_price_arr[$i][org_price] / $org_price_total);
                        $amount_total += $amount_prd;

                        $supply_price_prd = (int)($data[supply_price] * $org_price_arr[$i][org_price] / $org_price_total);
                        $supply_price_total += $supply_price_prd;
                    }
                    
                    // shop_price 업데이트
                    $query_price = "update order_products 
                                       set prd_amount = $amount_prd, 
                                           prd_supply_price = $supply_price_prd
                                     where seq = " . $org_price_arr[$i][seq];
                    mysql_query( $query_price, $connect);
                }
            }
        }
debug("사은품단계 7");
        
        // parklon 사은품 설정
        if( _DOMAIN_ == 'parklon' )
        {
            $query = "update orders a, order_products b, products c
                         set b.is_gift = 1
                       where a.seq = b.order_seq and 
                             b.product_id = c.product_id and
                             a.status = 0 and 
                             a.order_status = 45 and
                             substring(c.name, 1, 3) = '사은품'";
            mysql_query($query, $connect);
        }
        
        // chansin 도서지역 자동취소
        // 3/29 제주 추가
        if( _DOMAIN_ == 'changsin' || _DOMAIN_ == '_ezadmin' )
        {
            $query = "select if(pack>0,pack,seq) pack_seq 
                        from orders 
                       where status = 0 
                         and order_status = 45 
                         and replace(recv_zip,'-','') in (409851,535920,650914,556839,556841,573816,539911,548902,556838,417931,650941,556843,556853,656876,357941,535919,409890,799810,535940,530145,546908,535916,579913,537830,537905,537850,513893,548993,535894,650835,537853,799801,535892,513892,536928,537824,535835,537817,573819,573818,535830,539919,355848,417913,556832,417933,539917,539918,535847,537843,573812,537844,535932,556844,535843,409831,799821,535896,409853,536935,355846,535837,535860,400460,409832,409833,650922,535897,539910,650925,535863,650916,537836,537816,695980,556842,409882,548906,536929,650932,573814,650946,409840,537821,343852,537901,409881,535811,535917,537832,799820,555300,535943,530430,535935,556840,548894,537847,535934,799804,535915,556852,535832,535910,537825,556835,417930,539915,556855,548994,618430,417920,650912,513890,650944,535852,513891,409850,650923,535833,535914,535824,537826,535923,535813,650920,535806,556846,355847,535838,548990,799812,537809,650930,537814,537815,537840,535885,535890,799811,409830,535850,535893,417932,409910,537822,537842,535870,535871,535933,556847,799803,409892,799805,537818,535834,695950,535840,409880,548941,537833,537907,535880,573815,573817,650924,550270,556848,556850,556854,556836,537849,650913,556837,650926,664270,573810,556831,579911,535844,650931,535921,650910,695952,356878,556830,537922,535831,799822,535836,409841,417922,409912,409891,537851,535912,537834,537852,535861,799823,799800,409911,537835,579914,537848,539916,650915,535891,535884,417911,417912,650934,556849,535872,535841,535930,535931,537841,535926,409919,535925,535936,409893,535862,409842,537846,537823,535913,355845,618440,618450,535851,535823,417910,535895,535881,548992,539914,650933,573811,537902,539912,573813,417923,695983,537904,650911,535842,409852,537845,548991,537900,695951,535918,417921,556834,535873,537831,530440,535845,799813,535882,535816,535941,535942,535898,409913,579915,535911,799802,537920,579912,535805,537820,579910,618420,535924,355842,556851,548909,650927,537921,664250,650833,618410,539913,409883,650945,573955,537903,535883,650921,535922,690003,690011,690012,690021,690022,690029,690031,690032,690041,690042,690043,690050,690061,690062,690071,690072,690073,690081,690082,690090,690100,690110,690121,690122,690130,690140,690150,690161,690162,690163,690170,690180,690191,690192,690200,690210,690220,690231,690232,690241,690242,690600,690610,690700,690701,690703,690704,690705,690706,690707,690708,690709,690710,690711,690712,690713,690714,690715,690717,690718,690719,690720,690721,690722,690723,690724,690725,690726,690727,690728,690729,690730,690731,690732,690734,690735,690736,690737,690738,690739,690740,690741,690742,690743,690744,690750,690751,690755,690756,690760,690762,690764,690765,690766,690767,690769,690770,690771,690772,690773,690774,690775,690776,690777,690778,690779,690780,690781,690782,690783,690784,690785,690786,690787,690788,690789,690790,690800,690801,690802,690803,690804,690805,690806,690807,690808,690809,690810,690811,690812,690813,690814,690815,690816,690817,690818,690819,690820,690821,690822,690823,690824,690825,690826,690827,690828,690829,690830,690831,690832,690833,690834,690835,690836,690837,690838,690839,690840,690841,690842,690843,690846,690847,690850,695791,695792,695793,695794,695795,695796,695900,695901,695902,695903,695904,695905,695906,695907,695908,695909,695910,695911,695912,695913,695914,695915,695916,695917,695918,695919,695920,695921,695922,695923,695924,695925,695926,695927,695928,695929,695930,695931,695932,695933,695934,695940,695941,695942,695943,695944,695945,695946,695947,695948,695949,695950,695951,695952,695960,695961,695962,695963,695964,695965,695966,695967,695968,695969,695970,695971,695972,695973,695974,695975,695976,695977,695978,695979,695980,695981,695982,697010,697011,697012,697013,697014,697020,697030,697040,697050,697060,697070,697080,697090,697100,697110,697120,697130,697301,697310,697320,697330,697340,697350,697360,697370,697380,697390,697600,697700,697701,697703,697704,697705,697706,697707,697805,697806,697807,697808,697819,697820,697821,697822,697823,697824,697825,697826,697827,697828,697829,697830,697831,697832,697833,697834,697835,697836,697837,697838,697839,697840,697841,697842,697843,697844,697845,697846,697847,697848,697849,697850,697851,697852,697853,697854,697855,697856,697857,697858,697859,697860,697861,697862,697863,697864,699701,699702,699900,699901,699902,699903,699904,699905,699906,699907,699908,699910,699911,699912,699913,699914,699915,699916,699920,699921,699922,699923,699924,699925,699926,699930,699931,699932,699933,699934,699935,699936,699937,699940,699941,699942,699943,699944,699945,699946,699947,699948,699949,690851)
                       group by pack_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_cs = "insert csinfo 
                                set order_seq      = $data[pack_seq],
                                    input_date     = now(),
                                    input_time     = now(),
                                    writer         = '$_SESSION[LOGIN_NAME]',
                                    cs_type        = 10,
                                    cs_reason      = '',
                                    cs_result      = 0,
                                    content        = '도서지역 자동취소' ";
                mysql_query($query_cs, $connect);
            }

            $query = "update orders a, order_products b
                         set a.order_cs = 1,
                             b.order_cs = 1
                       where a.seq = b.order_seq 
                         and a.status = 0 
                         and a.order_status = 45 
                         and replace(a.recv_zip,'-','') in (409851,535920,650914,556839,556841,573816,539911,548902,556838,417931,650941,556843,556853,656876,357941,535919,409890,799810,535940,530145,546908,535916,579913,537830,537905,537850,513893,548993,535894,650835,537853,799801,535892,513892,536928,537824,535835,537817,573819,573818,535830,539919,355848,417913,556832,417933,539917,539918,535847,537843,573812,537844,535932,556844,535843,409831,799821,535896,409853,536935,355846,535837,535860,400460,409832,409833,650922,535897,539910,650925,535863,650916,537836,537816,695980,556842,409882,548906,536929,650932,573814,650946,409840,537821,343852,537901,409881,535811,535917,537832,799820,555300,535943,530430,535935,556840,548894,537847,535934,799804,535915,556852,535832,535910,537825,556835,417930,539915,556855,548994,618430,417920,650912,513890,650944,535852,513891,409850,650923,535833,535914,535824,537826,535923,535813,650920,535806,556846,355847,535838,548990,799812,537809,650930,537814,537815,537840,535885,535890,799811,409830,535850,535893,417932,409910,537822,537842,535870,535871,535933,556847,799803,409892,799805,537818,535834,695950,535840,409880,548941,537833,537907,535880,573815,573817,650924,550270,556848,556850,556854,556836,537849,650913,556837,650926,664270,573810,556831,579911,535844,650931,535921,650910,695952,356878,556830,537922,535831,799822,535836,409841,417922,409912,409891,537851,535912,537834,537852,535861,799823,799800,409911,537835,579914,537848,539916,650915,535891,535884,417911,417912,650934,556849,535872,535841,535930,535931,537841,535926,409919,535925,535936,409893,535862,409842,537846,537823,535913,355845,618440,618450,535851,535823,417910,535895,535881,548992,539914,650933,573811,537902,539912,573813,417923,695983,537904,650911,535842,409852,537845,548991,537900,695951,535918,417921,556834,535873,537831,530440,535845,799813,535882,535816,535941,535942,535898,409913,579915,535911,799802,537920,579912,535805,537820,579910,618420,535924,355842,556851,548909,650927,537921,664250,650833,618410,539913,409883,650945,573955,537903,535883,650921,535922,690003,690011,690012,690021,690022,690029,690031,690032,690041,690042,690043,690050,690061,690062,690071,690072,690073,690081,690082,690090,690100,690110,690121,690122,690130,690140,690150,690161,690162,690163,690170,690180,690191,690192,690200,690210,690220,690231,690232,690241,690242,690600,690610,690700,690701,690703,690704,690705,690706,690707,690708,690709,690710,690711,690712,690713,690714,690715,690717,690718,690719,690720,690721,690722,690723,690724,690725,690726,690727,690728,690729,690730,690731,690732,690734,690735,690736,690737,690738,690739,690740,690741,690742,690743,690744,690750,690751,690755,690756,690760,690762,690764,690765,690766,690767,690769,690770,690771,690772,690773,690774,690775,690776,690777,690778,690779,690780,690781,690782,690783,690784,690785,690786,690787,690788,690789,690790,690800,690801,690802,690803,690804,690805,690806,690807,690808,690809,690810,690811,690812,690813,690814,690815,690816,690817,690818,690819,690820,690821,690822,690823,690824,690825,690826,690827,690828,690829,690830,690831,690832,690833,690834,690835,690836,690837,690838,690839,690840,690841,690842,690843,690846,690847,690850,695791,695792,695793,695794,695795,695796,695900,695901,695902,695903,695904,695905,695906,695907,695908,695909,695910,695911,695912,695913,695914,695915,695916,695917,695918,695919,695920,695921,695922,695923,695924,695925,695926,695927,695928,695929,695930,695931,695932,695933,695934,695940,695941,695942,695943,695944,695945,695946,695947,695948,695949,695950,695951,695952,695960,695961,695962,695963,695964,695965,695966,695967,695968,695969,695970,695971,695972,695973,695974,695975,695976,695977,695978,695979,695980,695981,695982,697010,697011,697012,697013,697014,697020,697030,697040,697050,697060,697070,697080,697090,697100,697110,697120,697130,697301,697310,697320,697330,697340,697350,697360,697370,697380,697390,697600,697700,697701,697703,697704,697705,697706,697707,697805,697806,697807,697808,697819,697820,697821,697822,697823,697824,697825,697826,697827,697828,697829,697830,697831,697832,697833,697834,697835,697836,697837,697838,697839,697840,697841,697842,697843,697844,697845,697846,697847,697848,697849,697850,697851,697852,697853,697854,697855,697856,697857,697858,697859,697860,697861,697862,697863,697864,699701,699702,699900,699901,699902,699903,699904,699905,699906,699907,699908,699910,699911,699912,699913,699914,699915,699916,699920,699921,699922,699923,699924,699925,699926,699930,699931,699932,699933,699934,699935,699936,699937,699940,699941,699942,699943,699944,699945,699946,699947,699948,699949)";
            mysql_query($query, $connect);
        }

       

  
		
        if($_SESSION[ISLAND_HOLD] )//도서지역 보류사용 //최웅
        {
debug("환경설정 도서지역 자동보류!");
        	$shop_id ="";
        	$query = "select if(pack>0,pack,seq) pack_seq 
                        from orders 
                       where status = 0 
                         and order_status = 45 ";   
            if(!$_SESSION[ISLAND_SHOP_A]) //전체선택은 WHERE절 걸 필요없음..
            {
            	if($_SESSION[ISLAND_SHOP_B]) //쿠팡
	            	$shop_id = $shop_id ? $shop_id .=",53 " : $shop_id .="53";
	            if($_SESSION[ISLAND_SHOP_C]) //위메프
	            	$shop_id = $shop_id ? $shop_id .=",54 " : $shop_id .="54";
	            if($_SESSION[ISLAND_SHOP_D]) //티몬
	            	$shop_id = $shop_id ? $shop_id .=",55 " : $shop_id .="55";

	            $shop_id_query = "SELECT * FROM shopinfo WHERE shop_id % 100 IN ($shop_id)";
	            $shop_id_result = mysql_query($shop_id_query, $connect);

				$shop_id = "";
	        	while( $shop_id_data = mysql_fetch_assoc($shop_id_result) )
	        		$shop_id = $shop_id ? $shop_id .",$shop_id_data[shop_id] " : $shop_id ." $shop_id_data[shop_id] ";

	            if($_SESSION[ISLAND_SHOP_E]) //사용자
	            	$shop_id = $shop_id ? $shop_id.", ".$_SESSION[ISLAND_CUSTOM_SHOPID] : $_SESSION[ISLAND_CUSTOM_SHOPID];

	            $option .= " AND shop_id IN ($shop_id) ";
            }
            $zip_code = $this->get_default_zip_code();
            
            if($_SESSION[ISLAND_ZIPCODE]) //사용자 정의 코드
            	$zip_code = $_SESSION[ISLAND_CUSTOM_ZIPCODE];
            	
            $option .= " AND ( replace(recv_zip,'-','') in ($zip_code) ";
            
            
            if(!$_SESSION[ISLAND_ZIPCODE] && !$_SESSION[ISLAND_JEJU_ZIPCODE])
            	$option .= " OR left(recv_zip, 2) IN (69) )";
            else
            	$option .= " ) ";
            	
            	
            if($_SESSION[ISLAND_JEJU_ZIPCODE]) //제주 미포함
            	$option .= " AND left(recv_zip, 2) NOT IN (69) ";
            	
debug($query.$option." group by pack_seq");

			$result = mysql_query($query.$option." group by pack_seq", $connect);
        	while( $data = mysql_fetch_assoc($result) )
            {
                $query_cs = "insert csinfo 
                                set order_seq      = $data[pack_seq],
                                    input_date     = now(),
                                    input_time     = now(),
                                    writer         = '$_SESSION[LOGIN_NAME]',
                                    cs_type        = 1,
                                    cs_reason      = '',
                                    cs_result      = 0,
                                    content        = '도서지역 자동보류' ";
                mysql_query($query_cs, $connect);
            }

            $query = "update orders a
                         set a.hold = 1
                       where a.status = 0 
                         and a.order_status = 45 ";
                         
            mysql_query($query.$option, $connect);
        }
        
         
                
        // msoul 한진택배 추석기간 배송불가 자동보류 2013-09-05
        if( _DOMAIN_ == '_msoul' || _DOMAIN_ == '_ezadmin' )
        {
            $query = "select if(pack>0,pack,seq) pack_seq 
                            ,seq
                            ,pack
                        from orders 
                       where status = 0 
                         and order_status = 45 
                         and replace(recv_zip,'-','') in (409910,409911,409912,409919,409913,409850,409851,409852,409853,409890,409891,409892,409893,409880,409881,409882,409883,400460)
                       group by pack_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_hold = "update orders set hold=1 where ";
                if( $data['pack'] )
                    $query_hold .= " pack=$data[pack] ";
                else
                    $query_hold .= " seq=$data[seq] ";
                mysql_query($query_hold, $connect);
                
                $query_cs = "insert csinfo 
                                set order_seq      = $data[pack_seq],
                                    input_date     = now(),
                                    input_time     = now(),
                                    writer         = '$_SESSION[LOGIN_NAME]',
                                    cs_type        = 1,
                                    cs_reason      = '',
                                    cs_result      = 0,
                                    content        = '한진 추석기간 배송불가 자동보류' ";
                mysql_query($query_cs, $connect);
            }
        }
debug("사은품단계 8");

        //////////////////////////////////////
        // 매칭자동취소 상품을 처리한다.
        //////////////////////////////////////
        $query = "update orders a, order_products b, products c
                     set a.order_cs = 1,
                         b.order_cs = 1,
                         b.change_date = now()
                   where a.status = 0 and
                         a.order_status = 45 and
                         a.seq = b.order_seq and
                         b.product_id = c.product_id and
                         c.match_cancel = 1";
        mysql_query($query, $connect);
debug("사은품단계 9");
        
        // 취소주문 중에서 부분취소를 찾아서 orders를 부분취소로 변경한다.
        $query = "update orders a, order_products b
                     set a.order_cs = 2
                   where a.status = 0 and
                         a.order_status = 45 and
                         a.order_cs = 1 and
                         a.seq = b.order_seq and
                         b.order_cs = 0";
        mysql_query($query, $connect);
debug("사은품단계 10");
        
        // 취소주문 중에서 부분취소를 찾아서 order_products를 부분취소로 변경한다.
        $query = "update orders a, order_products b
                     set b.order_cs = 2
                   where a.status = 0 and
                         a.order_status = 45 and
                         a.order_cs = 2 and
                         a.seq = b.order_seq and
                         b.order_cs = 1";
        mysql_query($query, $connect);
debug("사은품단계 11");

        // 취소주문 중에서 합포이고, pack 인 주문을 찾는다.
        $query = "select seq, pack from orders where status=0 and order_status=45 and order_cs=1 and seq=pack";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_assoc($result) )
        {
            // 합포의 나머지 주문중에 전체취소 아닌 주문이 있는지 확인
            $query_pack = "select seq from orders where pack=$data[pack] and order_cs<>1";
            $result_pack = mysql_query($query_pack, $connect);
            if( mysql_num_rows($result_pack) > 0 )
            {
                $data_pack = mysql_fetch_assoc($result_pack);
                
                // 정상주문의 seq로 pack 번호 변경
                $query_new_pack = "update orders set pack=$data_pack[seq] where pack=$data[pack]";
                mysql_query($query_new_pack, $connect);
            }
        }
debug("사은품단계 12");
        
        //////////////////////////////////////
        // 사은품 설정 시작
        //////////////////////////////////////
        $pack = array();
        
        // 1. pack이 없는 주문을 처리한다.
        $query = "select * from orders where status=0 and order_status=45 and pack=0 and order_cs<>1 and gift_done=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_array($result) )
        {
            $order['seq']      = $data[seq];
            $order['shop_id']  = $data[shop_id];
            $order['shop_pid'] = $data[shop_product_id];
            $order['qty']      = $data[qty];
            $order['amount']   = $data[amount];
            $order['amount2']  = 0;
            $order['pay_type'] = $data[pay_type];
            
            // 쿠팡
            if( $data[shop_id] % 100 == 53 )
                $order['deal_no'] = $data[code2];
            // 위메프
            else if( $data[shop_id] % 100 == 54 )
                $order['deal_no'] = $data[code2];
            // 티몬
            else if( $data[shop_id] % 100 == 55 || $data[shop_id] % 100 == 41 )
                $order['deal_no'] = $data[code1];
            else
                $order['deal_no'] = "";

            // 1.1 order_products 에서, 어드민 상품 코드를 구한다.
            $query_prd = "select * from order_products where order_seq = $data[seq] and order_cs not in (1,2)";
            $result_prd = mysql_query($query_prd, $connect);
            $order['prd'] = array(); 
            while($data_prd = mysql_fetch_array($result_prd))
            {
                $prd['pid'] = $data_prd[product_id];
                $prd['qty'] = $data_prd[qty];
                $order['prd'][] = $prd;
                $order['amount2'] += $data_prd[shop_price];
            }
            
            $pack[0]['order'][] = $order;
        }
debug("사은품단계 13");

        // 2. pack이 있는 주문을 처리한다.
        $query = "select pack from orders where status=0 and order_status=45 and pack > 0 and order_cs<>1 and gift_done=0 group by pack order by pack";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_array($result) )
        {
            $order = array();
            
            // 2.1 orders에서 pack으로 query한다.
            $query_pack = "select * from orders where pack=$data[pack] and order_cs<>1";
            
            // 환경설정에서, 발주 다른 합포는 사은품 적용 안함일 경우
            if( $_SESSION[PACK_GIFT] )
                $query_pack .= " and status=0 and order_status=45";
            
            $result_pack = mysql_query($query_pack, $connect);
            while($data_pack=mysql_fetch_array($result_pack))
            {
                $order['seq']      = $data_pack[seq];
                $order['shop_id']  = $data_pack[shop_id];
                $order['shop_pid'] = $data_pack[shop_product_id];
                $order['qty']      = $data_pack[qty];
                $order['amount']   = $data_pack[amount];
                $order['amount2']  = 0;
                $order['pay_type'] = $data_pack[pay_type];
                
                // 쿠팡
                if( $data_pack[shop_id] % 100 == 53 )
                    $order['deal_no'] = $data_pack[code2];
                // 위메프
                else if( $data_pack[shop_id] % 100 == 54 )
                    $order['deal_no'] = $data_pack[code2];
                // 티몬
                else if( $data_pack[shop_id] % 100 == 55 || $data_pack[shop_id] % 100 == 41 )
                    $order['deal_no'] = $data_pack[code1];
                else
                    $order['deal_no'] = "";

                $order['prd']      = array();    
                // 2.2 order_products 에서, 어드민 상품 코드를 구한다.
                $query_prd = "select * from order_products where order_seq = $data_pack[seq] and is_gift=0 and order_cs not in (1,2)";
                $result_prd = mysql_query($query_prd, $connect);
                while($data_prd = mysql_fetch_array($result_prd))
                {
                    $prd['pid']   = $data_prd[product_id];
                    $prd['qty']   = $data_prd[qty];
                    $order['prd'][] = $prd;
                    $order['amount2'] += $data_prd[shop_price];
                }
                
                $pack[$data[pack]]['order'][] = $order;
            }
        }
debug("사은품단계 14");

        $check_arr = array();
        // 조건을 가져온다.
        $today = date("Y-m-d H:i:s");
        $query = "select * from new_gift where timestamp(start_date, concat(start_hour,':00:00'))<='$today' and '$today'<=timestamp(end_date, concat(end_hour,':59:59')) order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_array($result))
        {
            // 어드민 상품코드
            if( $data['pid'] ){
                $f_pid = true;
                $pid_arr = explode(',', $data['pid']);
                
                // 상품코드가 대표코드일 경우, 옵션 코드도 추가
                $pid_add = '';
                foreach( $pid_arr as $p_id )
                    $pid_add .= ($pid_add?',':'') . "'" . $p_id . "'";

                $query_add = "select product_id from products where org_id in ($pid_add)";
                $result_add = mysql_query($query_add, $connect);
                while( $data_add = mysql_fetch_array($result_add) )
                    $pid_arr[] = $data_add[product_id];

                // 제외 조건
                if( $data['pid_ex'] )
                    $f_pid_ex = true;
                else
                    $f_pid_ex = false;
            }else{
                $f_pid = false;
            }
                
            // 판매처
            if( $data['shop_id'] ){
                $f_shop_id = true;
                $shop_id = $data['shop_id'];
            }else{
                $f_shop_id = false;
            }
            
            // 판매처 상품코드
            if( $data['shop_pid'] ){
                $f_shop_pid = true;
                $shop_pid_arr = explode(',', $data['shop_pid']);

                // 제외 조건
                if( $data['shop_pid_ex'] )
                    $f_shop_pid_ex = true;
                else
                    $f_shop_pid_ex = false;
            }else{
                $f_shop_pid = false;
            }

            // 수량 최소
            if( $data['qty_start'] ){
                $f_qty_start = true;
                $qty_start = $data['qty_start'];
            }else{
                $f_qty_start = false;
            }
            
            // 수량 최대
            if( $data['qty_end'] ){
                $f_qty_end = true;
                $qty_end = $data['qty_end'];
            }else{
                $f_qty_end = false;
            }
            
            // 금액 최소
            if( $data['price_start'] ){
                $f_price_start = true;
                $price_start = $data['price_start'];
            }else{
                $f_price_start = false;
            }
            
            // 금액 최대
            if( $data['price_end'] ){
                $f_price_end = true;
                $price_end = $data['price_end'];
            }else{
                $f_price_end = false;
            }
            
            // 자체 판매가
            if( $data['price_flag'] )
                $f_price_flag = true;
            else
                $f_price_flag = false;

            // 전체 판매가
            if( $data['all_price_flag'] )
                $f_all_price_flag = true;
            else
                $f_all_price_flag = false;

            // 무료 배송
            if( $data['trans_free'] ){
                $f_trans_free = true;
            }else{
                $f_trans_free = false;
            }

            // 사은품 내용
            if( $data['gift_msg'] ){
                $f_gift_msg = true;
                $gift_msg = $data['gift_msg'];
            }else{
                $f_gift_msg = false;
            }

            // 사은품 상품코드
            if( $data['product'] )
            {
                $f_product = true;
                $product_arr = explode(',', $data['product']);
            }
            else
            {
                $f_product = false;
            }

            // 중복불가
            if( $data['only_flag'] )
                $f_only_flag = true;
            else
                $f_only_flag = false;

            // 수량만큼
            if( $data['qty_flag'] )
                $f_qty_flag = true;
            else
                $f_qty_flag = false;

            // 배수
            $qty_multi = $data['qty_multi'];
            
            // 랜덤적용 
            $random_gift = $data['random_gift'];
            
            // 딜번호
            $is_deal_no = false;
            $deal_no_arr = array();
            if( $data['deal_no'] )
            {
                foreach( explode(",",$data['deal_no']) as $deal_no_val )
                {
                    $deal_no_val = trim( $deal_no_val );
                    if( $deal_no_val )
                    {
                        $deal_no_arr[] = $deal_no_val;
                        $is_deal_no = true;
                        
		                // 제외 조건
		                if( $data['deal_no_ex'] )
		                    $f_deal_no_ex = true;
		                else
		                    $f_deal_no_ex = false;
                    }
                }
            }

            //############################
            // 새 사은품 로직 시작
            //############################

            // 발주중인 주문정보를 루프 돌리면서 조건에 걸리는 주문을 찾아낸다.
            foreach( $pack as $no => $p )
            {
                // 합포아닌 경우
                if( $no == 0 )
                {
                    foreach( $p['order'] as $ord )
                    {
                        // 발주서 판매가? 어드민 판매가?
                        if( $f_price_flag )
                            $amount = $ord['amount2'];
                        else
                            $amount = $ord['amount'];

                        // 판매처 조건 확인
                        if( $f_shop_id && ($shop_id != $ord['shop_id']) )  continue;

                        // 카페24 대표코드 검사
                        $p_shop_pid_im = false;
                        $p_shop_pid_ex = false;
                        if( $ord['shop_id'] % 100 == 72 && $f_shop_pid )
                        {
                            list($p_shop_pid, $_temp) = explode("-", $ord['shop_pid']);
                            if( $p_shop_pid > "" && !$f_shop_pid_ex && array_search($p_shop_pid, $shop_pid_arr) === false )
                                $p_shop_pid_im = true;
                            if( $p_shop_pid > "" &&  $f_shop_pid_ex && array_search($p_shop_pid, $shop_pid_arr) !== false )
                                $p_shop_pid_ex = true;
                        }
                        else
                        {
                            $p_shop_pid_im = true;
                            $p_shop_pid_ex = true;
                        }

                        // 판매처 상품코드 확인
                        if( $f_shop_pid && !$f_shop_pid_ex && (array_search($ord['shop_pid'], $shop_pid_arr) === false) && $p_shop_pid_im )  continue;
                        
                        // 판매처 상품코드 제외 확인
                        if( $f_shop_pid &&  $f_shop_pid_ex && (array_search($ord['shop_pid'], $shop_pid_arr) !== false) && $p_shop_pid_ex )  continue;

                        // 결제수단
                        if( $data[pay_type] && array_search($ord['pay_type'], explode(",",$data[pay_type])) === false )  continue;
                        
                        // 딜번호
                        if( $is_deal_no && !$f_deal_no_ex && array_search($ord['deal_no'], $deal_no_arr) === false )  continue;
                        
                        // 딜번호 제외 확인
                        if( $is_deal_no && $f_deal_no_ex && array_search($ord['deal_no'], $deal_no_arr) !== false )  continue;
                        
                        if( $f_pid )
                        {
                            $qty = 0;
                            foreach( $ord['prd'] as $prd )
                            {
                                // 어드민 상품코드
                                if( !$f_pid_ex && (array_search($prd['pid'], $pid_arr) === false) )  continue;
    
                                // 어드민 상품코드제외
                                if( $f_pid_ex && (array_search($prd['pid'], $pid_arr) !== false) )
                                {
                                    if( _DOMAIN_ == 'onseason' )
                                        continue 2;
                                    else
                                        continue;
                                }
    
                                $qty += $prd['qty'];
                            }
                        }
                        else
                            $qty = $ord['qty'];
                        
                        // 사은품 대상
                        if( $qty > 0 )
                        {
                            if( (!$f_qty_start || ($qty_start <= $qty)) && (!$f_qty_end || ($qty <= $qty_end)) &&
                                (!$f_price_start || ($price_start <= $amount)) && (!$f_price_end || ($amount <= $price_end)) )
                                $this->set_gift_db( $ord[seq], $f_trans_free, $f_gift_msg, $f_only_flag, $gift_msg, $f_product, $product_arr, $f_qty_flag, $ord[seq], $qty, $qty_multi, $random_gift );
                        }
                    }
                }
                // 합포인 경우
                else
                {
                    $total_qty = 0; //조건이 맞는 합계
                    $total_price = 0;
                    $t_total_price = 0; //조건없이 모든 합계
                    foreach( $p['order'] as $ord )
                    {
	                   	if($f_all_price_flag)
	                   	{
	                   		if( $f_price_flag )
                            	$t_total_price += $ord['amount2'];
                        	else
                           		$t_total_price += $ord['amount'];	
	                   	}
	                   	
                        // 판매처
                        if( $f_shop_id && $ord['shop_id'] != $shop_id )  continue;
                        
                        // 카페24 대표코드 검사
                        $p_shop_pid_im = false;
                        $p_shop_pid_ex = false;
                        if( $ord['shop_id'] % 100 == 72 && $f_shop_pid )
                        {
                            list($p_shop_pid, $_temp) = explode("-", $ord['shop_pid']);
                            if( $p_shop_pid > "" && !$f_shop_pid_ex && array_search($p_shop_pid, $shop_pid_arr) === false )
                                $p_shop_pid_im = true;
                            if( $p_shop_pid > "" &&  $f_shop_pid_ex && array_search($p_shop_pid, $shop_pid_arr) !== false )
                                $p_shop_pid_ex = true;
                        }
                        else
                        {
                            $p_shop_pid_im = true;
                            $p_shop_pid_ex = true;
                        }

                        // 판매처 상품코드
                        if( $f_shop_pid && !$f_shop_pid_ex && (array_search($ord['shop_pid'], $shop_pid_arr) === false) && $p_shop_pid_im )  continue;

                        // 판매처 상품코드제외
                        if( $f_shop_pid &&  $f_shop_pid_ex && (array_search($ord['shop_pid'], $shop_pid_arr) !== false) && $p_shop_pid_ex )  continue;

                        // 결제수단
                        if( $data[pay_type] && array_search($ord['pay_type'], explode(",",$data[pay_type])) === false )  continue;

                        // 딜번호
                        if( $is_deal_no && !$f_deal_no_ex && array_search($ord['deal_no'], $deal_no_arr) === false )  continue;
                        
                        // 딜번호 제외
                        if( $is_deal_no && $f_deal_no_ex && array_search($ord['deal_no'], $deal_no_arr) !== false )  continue;

                        if( $f_pid )
                        {
                            $qty = 0;
                            foreach( $ord['prd'] as $prd )
                            {
                                // 어드민 상품코드
                                if( !$f_pid_ex && (array_search($prd['pid'], $pid_arr) === false ) )  continue;
                                
                                // 어드민 상품코드제외
                                if( $f_pid_ex && (array_search($prd['pid'], $pid_arr) !== false ) )
                                {
                                    if( _DOMAIN_ == 'onseason' )
                                        continue 2;
                                    else
                                        continue;
                                }
                                
                                $qty += $prd['qty'];
                            }
                        }
                        else
                            $qty = $ord['qty'];

                        if( $qty )
                        {
                            // 사은품이 해당된 주문에 사음품을 붙이기 위해.
                            $gift_seq = $ord['seq'];
                            
                            // 전체 수량
                            $total_qty += $qty;

                            // 발주서 판매가? 어드민 판매가?
                            if( $f_price_flag )
                                $total_price += $ord['amount2'];
                            else
                                $total_price += $ord['amount'];
                        }
                    }
                    if( $total_qty > 0 )
                    {
                    	if($f_all_price_flag)
                    		$total_price = $t_total_price;

                        if( (!$f_qty_start || ($qty_start <= $total_qty)) && (!$f_qty_end || ($total_qty <= $qty_end)) &&
                            (!$f_price_start || ($price_start <= $total_price)) && (!$f_price_end || ($total_price <= $price_end)))
                        {
                            // 합포 주문의 seq 목록을 구한다.
                            $seq_arr = array();
                            foreach( $p['order'] as $ord )
                                $seq_arr[] = $ord[seq];
                            $seq_list = implode(',', $seq_arr);
                            // 사은품 적용
                            $this->set_gift_db( $seq_list, $f_trans_free, $f_gift_msg, $f_only_flag, $gift_msg, $f_product, $product_arr, $f_qty_flag, $gift_seq, $total_qty, $qty_multi, $random_gift );
                        }
                    }
                }
            }
        }
debug("사은품단계 15");
        
        $val['error'] = 0;

        // 사은품 완료
        $query = "update orders set order_status=50, gift_done=1 where status=0 and order_status=45";
        if( mysql_query($query, $connect) )
            debug("사은품 완료 성공 : " . $query);
        else
            debug("사은품 완료 실패 : " . $query);

        // Lock End
        $obj_lock = new class_lock(505);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // [합포] 사은품/무료배송 조건 충족시 db에 적용한다.
    function set_gift_db( $seq_list, $f_trans_free, $f_gift_msg, $f_only_flag, $gift_msg, $f_product, $product_arr, $qty_flag, $gift_seq='', $total_qty, $qty_multi, $random_gift )
    {
        global $connect;
        
        // 무료 배송
        if( $f_trans_free ) 
        {
            $tw = '선불';
            $query = "update orders set trans_who='$tw' where seq in ($seq_list)";
            mysql_query($query, $connect);
        }

        // 중복 불가 사은품 내용이 이미 있는지 확인
        $query = "select gift from orders where seq in ($seq_list)";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        if( preg_match('/[^,]+$/', $data[gift]) )
            return;
            
        // 중복 불가 사은품이 이미 있는지 확인
        $query = "select * from order_products where order_seq in ($seq_list) and is_gift=2";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
            return;
            
        // 수량만큼
        if( $qty_flag )
        {
            if( $qty_multi < 1 )  $qty_multi = 1;
            $qty = (int)( $total_qty / $qty_multi );
            if( $qty == 0 )
                return;
        }
        else
            $qty = 1;

        // 사은품 내용
        if( $f_gift_msg )
        {  
            if( $qty > 1 )
                $gift_msg .= "x" . $qty;
                
            // 중복 불가
            if( $f_only_flag )
            {
                $query = "update orders set gift='$gift_msg' where seq in ($seq_list)";
                mysql_query($query, $connect);
            }
            // 중복 가능
            else
            {
                $query = "update orders set gift=concat(ifnull(gift,''), '$gift_msg,') where seq in ($seq_list) and (gift is null or substring(gift,-1)=',')";
                mysql_query($query, $connect); 
            }
        }

        // 판매처 상품코드
        if( $f_product )
        {
            // 중복 불가
            if( $f_only_flag )
            {
                // 중복 불가일 경우, 이전 사은품 삭제
                $query = "delete from order_products where order_seq=$gift_seq and is_gift=1";
debug("삭제1 : " . $query);
                mysql_query($query, $connect);
                
                $gift_flag = 2;
            }
            else
                $gift_flag = 1;

            $query = "select * from orders where seq=$gift_seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_array( $result );

            // 사은품 적용 seq list
            if( $data[pack] > 0 )
            {
                $seq_arr = array();
                $query_pack = "select seq from orders where pack=$data[pack] and order_cs<>1 and status>0";
                $result_pack = mysql_query($query_pack, $connect);
                while( $data_pack = mysql_fetch_assoc($result_pack) )
                    $seq_arr[] = $data_pack[seq];
                    
                $seq_str = implode(",", $seq_arr);
            }
            else
                $seq_str = $data[seq];
                
            // 현재 적용된 사은품 목록(오늘 매칭된)을 가져온다
            // 발주중에 다른 사은품에 서로 같은 사은품이 적용되는 경우가 있음. 30분 이내에 매칭된 경우만 중복으로 넘어감.
            $gift_check_time = date("Y-m-d H:i:s", strtotime("-30 minutes") );
            $arr_gift_prd = array();
            $query_gift_prd = "select product_id from order_products where order_seq in ($seq_str) and is_gift=1 and match_date>'$gift_check_time' ";
            $result_gift_prd = mysql_query($query_gift_prd, $connect);
            while( $data_gift_prd = mysql_fetch_assoc($result_gift_prd) )
                $arr_gift_prd[] = $data_gift_prd[product_id];
                
            // 적용할 상품이 모두 적용되어있으면 적용 안한다.
            $gift_go = false;
            foreach( $product_arr as $product )
            {
                if( array_search($product, $arr_gift_prd) === false )
                {
                    $gift_go = true;
                    break;
                }
                // 발견된 상품코드는 배열에서 제외
                array_splice( $arr_gift_prd, array_search($product, $arr_gift_prd), 1 );
            }
            if( !$gift_go )  return;
            
            // 사은품 랜덤 적용
            if( $random_gift )
            {
                $random_arr = array();
                
                foreach( $product_arr as $p_id )
                {
                    $p_id_info = class_product::get_info($p_id);
                    
                    // 대표상품이면 옵션상품 추가
                    if( $p_id_info[is_represent] )
                    {
                        $query_prd_opt = "select product_id from products where org_id='$p_id' and is_delete = 0 ";
                        $result_prd_opt = mysql_query($query_prd_opt, $connect);
                        while( $data_prd_opt = mysql_fetch_assoc($result_prd_opt) )
                            $random_arr[] = $data_prd_opt[product_id];
                    }
                    else
                        $random_arr[] = $p_id;
                }
                $random_cnt = count($random_arr);
                $random_idx = rand(1,$random_cnt);

                $product_arr = array();
                $product_arr[] = $random_arr[ $random_idx - 1 ];
            }

            foreach( $product_arr as $product )
            {
                $prd_info = class_product::get_info($product);

                // shop_options 재설정
                if( $_SESSION[BASIC_VERSION] == 1 && $_SESSION[STOCK_MANAGE_USE] <> 1 )
                    $shop_options_gift = addslashes($prd_info[name]);
                else
                    $shop_options_gift = "사은품";

                $query = "insert order_products
                             set order_seq       = $gift_seq,
                                 product_id      = '$product',
                                 qty             = $qty,
                                 status          = 1,
                                 shop_id         = '$data[shop_id]',
                                 shop_product_id = '$data[shop_product_id]',
                                 shop_options    = '$shop_options_gift',
                                 match_date      = now(),
                                 is_gift         = $gift_flag,
                                 match_worker    = '$_SESSION[LOGIN_NAME]',
                                 org_price       = '" . $prd_info[org_price] * $qty . "',
                                 shop_price      = '" . $prd_info[shop_price] * $qty . "',
                                 supply_id       = '$prd_info[supply_code]'";
debug("사은품 적용 : " . $query);
                mysql_query($query, $connect);
            }
        }
    }
    
    //////////////////////////////////////////////////////////
    // [합포] 발주를 완료한다.
    function complete_balju()
    {
        global $connect;
        
		// include_once "Request.php";

        // Lock Check
        $obj_lock = new class_lock(505);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // order_status=50인 주문의 수량을 구한다.
        $query = "select count(seq) qty from orders where status=0 and order_status=50";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

/*
사용 안함
        // 발주수량 post
        $req = &new HTTP_Request();
        $req->setMethod(HTTP_REQUEST_METHOD_POST);
        $req->setURL('http://premium.ezadmin.co.kr/app/notice_order.php');
        $req->addPostData( "domain" , _DOMAIN_ );
        $req->addPostData( "order" , $data[qty] );
        $req->sendRequest();
*/

        ////////////////////////////
        // 합포 불가 사은품 찢기
        $query = "select a.seq seq, 
                         a.pack pack, 
                         b.seq seq_prd, 
                         b.qty qty, 
                         c.pack_cnt pack_cnt 
                    from orders a, 
                         order_products b, 
                         products c
                   where a.seq = b.order_seq and 
                         a.status=0 and 
                         a.order_status=50 and 
                         b.product_id=c.product_id and 
                         b.is_gift>0 and
                         c.pack_disable=1";

        // 2014-01-22 박팀장. 일단 미사용
        if( _DOMAIN_ == '_box4u' || _DOMAIN_ == '_jkhdev' )
            $query .= " and a.code30<>1";

        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 사은품 이외의 상품이 없으면 찢기 안함
            $query_check = "select seq from order_products where order_seq=$data[seq] and seq <> $data[seq_prd]";
            $result_check = mysql_query($query_check, $connect);
            if( mysql_num_rows($result_check) == 0 )  continue;            
            
            //*************************************************************************
            // 합포 나눌 수량 배열 : 타 상품과 합포 안되지만 자신끼리는 n개 합포처리
            //
            // 예) 수량 10, 동일상품 합포가능수량 3 => $arr_qty = (3,3,3,1)
            //
            $arr_qty = array();
            $p_qty = $data[qty];
            $pack_cnt = ( $data[pack_cnt]>1 ? $data[pack_cnt] : 1 );
            while( 1 )
            {
                if( $p_qty > $pack_cnt )
                {
                    $arr_qty[] = $pack_cnt;
                    $p_qty -= $pack_cnt;
                }
                else
                {
                    $arr_qty[] = $p_qty;
                    break;
                }
            }

            // 새 주문으로 복사하기
            foreach($arr_qty as $val)
                $new_seq = $this->copy_order($data[seq], $data[seq_prd], $val, 1);
                
            // 이전 자료 삭제
            $query_del = "delete from order_products where seq = $data[seq_prd]";
            mysql_query($query_del, $connect);
        }

        ////////////////////////////
        // 합포 불가 상품 찢기
        $query = "select a.seq seq, 
                         a.pack pack, 
                         b.seq seq_prd, 
                         b.qty qty, 
                         c.pack_cnt pack_cnt 
                    from orders a, 
                         order_products b, 
                         products c
                   where a.seq = b.order_seq and 
                         a.status=0 and 
                         a.order_status=50 and 
                         b.product_id=c.product_id and 
                         b.is_gift=0 and
                         c.pack_disable=1 ";

        // 2014-01-22 박팀장. 일단 미사용
        if( _DOMAIN_ == '_box4u' || _DOMAIN_ == '_jkhdev' )
            $query .= " and a.code30<>1";

        $query .= " order by a.seq desc, b.seq desc ";
debug("합포불가 상품찟기123 : " . $query);
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
debug("합포불가 상품찟기 while : $data[seq], $data[seq_prd]");
            // 앞의 주문에 의해서 pack 번호 변경됐을 수 있으므로 다시 조회
            $query_re = "select pack from orders where seq = $data[seq]";
            $result_re = mysql_query($query_re, $connect);
            $data_re = mysql_fetch_assoc($result_re);
            
            //*************************************************************************
            // 합포 나눌 수량 배열 : 타 상품과 합포 안되지만 자신끼리는 n개 합포처리
            //
            // 예) 수량 10, 동일상품 합포가능수량 3 => $arr_qty = (3,3,3,1)
            //
            $arr_qty = array();
            $p_qty = $data[qty];
            $pack_cnt = ( $data[pack_cnt]>1 ? $data[pack_cnt] : 1 );
            while( 1 )
            {
                if( $p_qty > $pack_cnt )
                {
                    $arr_qty[] = $pack_cnt;
                    $p_qty -= $pack_cnt;
                }
                else
                {
                    $arr_qty[] = $p_qty;
                    break;
                }
            }

            // 합포 찢을 수량과 동일수량의 사은품 있는지 확인. 1보다 클경우.
            $gift_arr = array();
            $query_gift = "select seq from order_products where order_seq=$data[seq] and qty=$data[qty] and is_gift>0 and qty>1";
            $result_gift = mysql_query($query_gift, $connect);
            while( $data_gift = mysql_fetch_assoc($result_gift) )
                $gift_arr[] = $data_gift[seq];

            // 세트 확인
            $query_set = "select * from order_products where order_seq=$data[seq] and seq<>$data[seq_prd] and is_gift=0";
            $result_set = mysql_query($query_set, $connect);
            //*******************************
            // 사은품 아닌 다른 상품 있음
            if( mysql_num_rows($result_set) > 0 )
            {
debug("사은품 아닌 다른 상품 있음");
$data_test = mysql_fetch_assoc($result_set);
debug_array($data_test);
                // 새 주문으로 복사하기
                foreach($arr_qty as $val)
                {
                    $new_seq = $this->copy_order($data[seq], $data[seq_prd], $val);
                    // 동일수량 사은품 찢기
                    $this->copy_order_gift($gift_arr, $new_seq, $val);
                }
                
                // 동일수량 사은품 있으면
                if( count($gift_arr) > 0 )
                    $delete_where = " where seq in ($data[seq_prd]," . implode(",",$gift_arr) . ")";
                else
                    $delete_where = " where seq = $data[seq_prd] ";
                    
                // 이전 자료 삭제
                $query_del = "delete from order_products " . $delete_where;
                mysql_query($query_del, $connect);
            }
            
            //*******************************
            // 사은품 아닌 다른 상품 없음
            else
            {
debug("사은품 아닌 다른 상품 없음");
                // 합포
                if( $data_re[pack] )
                {
                    // 자신이 pack이면 나머지 함포 주문들에 새 pack 을 부여. 나머지가 1개면 pack=0
                    if( $data[seq] == $data_re[pack] )
                    {
                        $query_pack = "select seq from orders where pack=$data_re[pack] and seq<>$data[seq] order by seq";
                        $result_pack = mysql_query($query_pack, $connect);
                        if( mysql_num_rows($result_pack) > 1 )
                        {
                            $data_pack = mysql_fetch_assoc( $result_pack );
                            mysql_query("update orders set pack=$data_pack[seq] where pack=$data_re[pack] and seq<>$data[seq]", $connect);
                        }
                        else
                        {
                            mysql_query("update orders set pack=0 where pack=$data_re[pack] and seq<>$data[seq]", $connect);
                        }
                    }

                    // 자신의 pack=0
                    mysql_query("update orders set pack=0 where seq=$data[seq]", $connect);
                }

debug_array($arr_qty);
                if( count($arr_qty) > 1 )
                {
                    $old_qty = array_pop( $arr_qty );
                    // 새 주문으로 복사하기
                    foreach($arr_qty as $val)
                    {
                        $new_seq = $this->copy_order($data[seq], $data[seq_prd], $val);

                        // 동일수량 사은품 찢기
                        $this->copy_order_gift($gift_arr, $new_seq, $val);
                    }
                
                    // 이전 자료 qty=$old_qty
                    $query_del = "update orders set qty=$old_qty where seq=$data[seq]";
                    mysql_query($query_del, $connect);

                    // 동일수량 사은품 있으면
                    if( count($gift_arr) > 0 )
                        $update_where = " where seq in ($data[seq_prd]," . implode(",",$gift_arr) . ")";
                    else
                        $update_where = " where seq = $data[seq_prd] ";
                    
                    // 이전 자료 qty=$old_qty
                    $query_del = "update order_products set qty=$old_qty " . $update_where;
                    mysql_query($query_del, $connect);
                }
            }
        }
        

        //조건부 합포분리 2014.05.21 최웅
		if(_DOMAIN_ == "_jkhdev" )//|| _DOMAIN_ == "ezadmin" )//|| _DOMAIN_ == "first")// || _DOMAIN_ == "bc0731")
		{
		    $unpack_obj = new class_auto_unpack();
			$unpack_obj->unpacking_2();
		}




        ///////////////////////////////
        // VIP 자동우선순위
        ///////////////////////////////
        $vip_list = array();
        $vip_list_add = array();
        $vip_list_name = array();

        $query_vip = "select * from customer_list where cust_type='vip'";
        $result_vip = mysql_query($query_vip, $connect);
        while( $data_vip = mysql_fetch_assoc($result_vip) )
        {
            // 전화
            $vip_tel = trim(preg_replace('/[^0-9]/', '', $data_vip[tel]));
            if( $vip_tel )
                $vip_list[] = $vip_tel;

            // 주소
            $vip_add = trim(preg_replace('/\s/', '', $data_vip[address]));
            if( $vip_add )
                $vip_list_add[] = $vip_add;

            // 이름
            $vip_name = trim(preg_replace('/\s/', '', $data_vip[name]));
            if( $vip_name )
                $vip_list_name[] = $vip_name;
        }

        if( $vip_list || $vip_list_add || $vip_list_name )
        {
            // 가장 큰 우선순위구하기
            $query = "select max(cs_priority) max_num from orders";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $max_cs_num = $data[max_num];
            
            $tel_arr = array();
            $query = "select pack,
                             if( pack>0, pack, seq) pack_seq,
                             recv_tel, 
                             recv_mobile,
                             order_tel,
                             order_mobile,
                             recv_address,
                             order_name,
                             recv_name
                        from orders 
                       where status = 0 and 
                             order_status = 50 
                       group by pack_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $vip_recv_tel     = trim(preg_replace('/[^0-9]/', '', $data[recv_tel]    ));
                $vip_recv_mobile  = trim(preg_replace('/[^0-9]/', '', $data[recv_mobile] ));
                $vip_order_tel    = trim(preg_replace('/[^0-9]/', '', $data[order_tel]   ));
                $vip_order_mobile = trim(preg_replace('/[^0-9]/', '', $data[order_mobile]));
                
                $vip_order_add = trim(preg_replace('/\s/', '', $data[recv_address]));

                $vip_order_name = trim(preg_replace('/\s/', '', $data[order_name]));
                $vip_recv_name  = trim(preg_replace('/\s/', '', $data[recv_name]));

                $is_vip = false;
                foreach( $vip_list as $_tel )
                {
                    if( $_tel == $vip_recv_tel || $_tel == $vip_recv_mobile || $_tel == $vip_order_tel || $_tel == $vip_order_mobile )
                    {
                        $is_vip = true; 
                        break;
                    }
                }

                if( !$is_vip )
                {
                    foreach( $vip_list_add as $_add )
                    {
                        if( $_add == $vip_order_add )
                        {
                            $is_vip = true; 
                            break;
                        }
                    }
                }

                if( !$is_vip )
                {
                    foreach( $vip_list_name as $_name )
                    {
                        if( $_name == $vip_order_name || $_name == $vip_recv_name )
                        {
                            $is_vip = true; 
                            break;
                        }
                    }
                }

                if( !$is_vip )  continue;
                
                $max_cs_num++;
                
                if( _DOMAIN_ != 'twoj2' )
                {
                    $query = "update orders set cs_priority = $max_cs_num where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                    
                    // cs 추가
                    $sql = "insert csinfo 
                               set order_seq  = '$data[pack_seq]',
                                   pack       = '$data[pack]',
                                   input_date = now(),
                                   input_time = now(),
                                   writer     = '$_SESSION[LOGIN_NAME]',
                                   cs_type    = '23',
                                   cs_reason  = '',
                                   cs_result  = '1',
                                   content    = '우수고객 자동우선순위설정'";
                    mysql_query ( $sql, $connect );
                }
                
                if( _DOMAIN_ == 'brownbunny' || _DOMAIN_ == 'sincompany' )
                {
                    $query = "update orders set recv_name = concat('♥',recv_name,'♥') where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                }
                else if( _DOMAIN_ == 'twoj2' || _DOMAIN_ == 'jkhdev' )
                {
                    $query = "update orders set recv_name = concat('♣',recv_name) where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                }
                else if( _DOMAIN_ == 'dodry' || _DOMAIN_ == 'mizne' )
                {
                    $query = "update orders set hold=1 where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                }
            }
        }
                
        ///////////////////////////////
        // luck 블랙리스트
        ///////////////////////////////
        $black_list = array();
        $black_list_add = array();
        $black_list_name = array();

        $query_black = "select * from customer_list where cust_type='black'";
        $result_black = mysql_query($query_black, $connect);
        while( $data_black = mysql_fetch_assoc($result_black) )
        {
            // 전화
            $black_tel = trim(preg_replace('/[^0-9]/', '', $data_black[tel]));
            if( $black_tel )
                $black_list[] = $black_tel;

            // 주소
            $black_add = trim(preg_replace('/\s/', '', $data_black[address]));
            if( $black_add )
                $black_list_add[] = $black_add;

            // 이름
            $black_name = trim(preg_replace('/\s/', '', $data_black[name]));
            if( $black_name )
                $black_list_name[] = $black_name;
        }

        if( $black_list || $black_list_add || $black_list_name )
        {
            // 가장 큰 우선순위구하기
            $query = "select max(cs_priority) max_num from orders";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            $max_cs_num = $data[max_num];
            
            $tel_arr = array();
            $query = "select pack,
                             if( pack>0, pack, seq) pack_seq,
                             recv_tel, 
                             recv_mobile,
                             order_tel,
                             order_mobile,
                             recv_address,
                             order_name,
                             recv_name
                        from orders 
                       where status = 0 and 
                             order_status = 50 
                       group by pack_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $black_recv_tel     = trim(preg_replace('/[^0-9]/', '', $data[recv_tel]    ));
                $black_recv_mobile  = trim(preg_replace('/[^0-9]/', '', $data[recv_mobile] ));
                $black_order_tel    = trim(preg_replace('/[^0-9]/', '', $data[order_tel]   ));
                $black_order_mobile = trim(preg_replace('/[^0-9]/', '', $data[order_mobile]));

                $black_order_add = trim(preg_replace('/\s/', '', $data[recv_address]));
                
                $black_order_name = trim(preg_replace('/\s/', '', $data[order_name]));
                $black_recv_name  = trim(preg_replace('/\s/', '', $data[recv_name]));

                $is_black = false;
                
                // 전화번호 비교
                foreach( $black_list as $_tel )
                {
                    if( $_tel == $black_recv_tel || $_tel == $black_recv_mobile || $_tel == $black_order_tel || $_tel == $black_order_mobile )
                    {
                        $is_black = true; 
                        break;
                    }
                }

                // 주소비교
                if( !$is_black )
                {
                    foreach( $black_list_add as $_add )
                    {
                        if( $_add == $black_order_add )
                        {
                            $is_black = true; 
                            break;
                        }
                    }
                }

                // 이름비교
                if( !$is_black )
                {
                    foreach( $black_list_name as $_name )
                    {
                        if( $_name == $black_order_name || $_name == $black_recv_name )
                        {
                            $is_black = true; 
                            break;
                        }
                    }
                }

                if( !$is_black )  continue;
                
                $query = "update orders set recv_name = concat('★',recv_name,'★') where seq = $data[pack_seq] or pack = $data[pack_seq]";
                mysql_query($query, $connect);

                if( _DOMAIN_ == 'dodry' )
                {
                    $max_cs_num++;
                    $query = "update orders set cs_priority = $max_cs_num where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                    
                    // cs 추가
                    $sql = "insert csinfo 
                               set order_seq  = '$data[pack_seq]',
                                   pack       = '$data[pack]',
                                   input_date = now(),
                                   input_time = now(),
                                   writer     = '$_SESSION[LOGIN_NAME]',
                                   cs_type    = '23',
                                   cs_reason  = '',
                                   cs_result  = '1',
                                   content    = '블랙고객 자동우선순위설정'";
                    mysql_query ( $sql, $connect );

                    $query = "update orders set hold=1 where seq = $data[pack_seq] or pack = $data[pack_seq]";
                    mysql_query($query, $connect);
                }
            }
        }

        ///////////////////////////////
        // cafe24 카페24 주문번호 넣기
        // 2014.09.25 최웅  찬영선배 요청..
        ///////////////////////////////
        
        $__order_id_seq = "";
        $__where_shop_id = "";
        
        //dabagirl2 , pantrading2 :  73 => code2, 72 => order_id_seq로
        if(_DOMAIN_ == "dabagirl2" || _DOMAIN_ == "pantrading2")
        {
        	// 72랑 73이랑 order_id_seq 가 다름.. 두번 넣음
        	$__order_id_seq = "code2";
       		$__where_shop_id = "shop_id % 100 = 73";
        	
        	$query = "INSERT IGNORE ezauto_cafe24_orders  (reg_date, shop_id, order_id, order_id_seq)
	        										SELECT	  now(), shop_id, order_id, $__order_id_seq
												  	  FROM orders 
												  	 WHERE status = 0 
												  	   AND order_status = 50
												  	   AND order_id NOT LIKE '%_사은품(%'  
												  	   AND $__where_shop_id";
												  	   
			mysql_query($query, $connect);

			$__order_id_seq = "order_id_seq";
       		$__where_shop_id = "shop_id % 100 = 72";

        }
        //kldh01 code2로
        else if(_DOMAIN_ == "kldh01")
        {
        	$__order_id_seq = "code2";
       		$__where_shop_id = "(shop_id % 100 = 72 OR shop_id % 100 = 73)";
        }
        else
        {
        	$__order_id_seq = "order_id_seq";
       		$__where_shop_id = "(shop_id % 100 = 72 OR shop_id % 100 = 73)";        	
        }
        
        $query = "INSERT IGNORE ezauto_cafe24_orders  (reg_date, shop_id, order_id, order_id_seq)
												SELECT	  now(), shop_id, order_id, $__order_id_seq
											  	  FROM orders 
											  	 WHERE status = 0 
											  	   AND order_status = 50 
											  	   AND order_id NOT LIKE '%_사은품(%' 
											  	   AND $__where_shop_id";
        mysql_query($query, $connect);
        
        
        ///////////////////////////////
        // 자동보류
        ///////////////////////////////
        if( _DOMAIN_ == 'sbs' )
        {
            // 2014-02-13 장경희. 10085 추가. 김다은 요청
            $query = "update orders set hold=1 where status=0 and order_status=50 and shop_id in (10081, 10083, 10085, 10086) and hold=0";
            mysql_query($query, $connect);
        }
        if( _DOMAIN_ == '7chocola' )
        {
            // 2014-12-09 장경희.
            $query = "update orders set hold=1 where status=0 and order_status=50 and shop_id in (10081,10082,10083,10084,10085,10086,10087) and hold=0";
            mysql_query($query, $connect);
        }
        
        if( _DOMAIN_ == 'sbs' || _DOMAIN_ == 'dinto33' || _DOMAIN_ == '7chocola' )
        {
            // 지마켓 해외배송 자동보류 - G마켓 해외배송물류센터
            $query = "update orders set hold=1 where status=0 and order_status=50 and recv_address like '%G마켓 해외배송물류센터%' and hold=0";
            mysql_query($query, $connect);
        }

        ///////////////////////////////
        // 배송비(trans_fee) 최대값
        ///////////////////////////////
        $query = "select * from orders where status=0 and order_status=50";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 합포
            if( $data[pack] > 0 )
            {
                $query_pack = "select max(c.trans_fee) max_trans_fee
                                 from orders a, order_products b, products c 
                                where a.seq=b.order_seq and
                                      b.product_id = c.product_id and
                                      a.pack = $data[pack]
                                group by a.pack";
                $result_pack = mysql_query($query_pack, $connect);
                $data_pack = mysql_fetch_assoc($result_pack);
                if( $data_pack[max_trans_fee] > 0 )
                {
                    $query_trans_fee = "update orders set trans_fee=$data_pack[max_trans_fee] where pack=$data[pack]";
                    mysql_query($query_trans_fee, $connect);
                }                                 
            }
            // 일반
            else
            {
                $query_single = "select max(c.trans_fee) max_trans_fee
                                   from orders a, order_products b, products c 
                                  where a.seq=b.order_seq and
                                        b.product_id = c.product_id and
                                        a.seq = $data[seq]
                                  group by a.seq";
                $result_single = mysql_query($query_single, $connect);
                $data_single = mysql_fetch_assoc($result_single);
                if( $data_single[max_trans_fee] > 0 )
                {
                    $query_trans_fee = "update orders set trans_fee=$data_single[max_trans_fee] where seq=$data[seq]";
                    mysql_query($query_trans_fee, $connect);
                }                                 
            }
        }
        
        // CTI_ORDERS
        global $sys_connect;
        $query = "select is_cti_use from sys_domain where id='"._DOMAIN_."'";
        $result = mysql_query($query, $sys_connect);
        $data = mysql_fetch_assoc($result);
        if( $data[is_cti_use] )
        {
            $this->insert_cti_orders();
        }

        // 선언 반드시 필요
        $val = array();
        
        // 특수주문 관리번호
        $special_seq_arr = array();

        // 특수주문 판매처
        $special_shop_arr = array();
        
        $query_ordertype = "select seq, shop_id from orders a where status=0 and order_status=50 " . $this->query_special_order();
        $result_ordertype = mysql_query($query_ordertype, $connect);
        if( mysql_num_rows($result_ordertype) )
        {
            while( $data_ordertype = mysql_fetch_assoc($result_ordertype) )
            {
                // 옥션, 지마켓은 별도 보류
                if( $data_ordertype[shop_id] % 100 != 1 && $data_ordertype[shop_id] % 100 != 2 )
                    $special_seq_arr[] = $data_ordertype[seq];
                    
                $special_shop_arr[] = $data_ordertype[shop_id] % 100;
            }
        }

        $val['msg'] = "";
        foreach( array_unique($special_shop_arr) as $_v )
        {
            switch($_v)
            {
                case 65:
                    $val['msg'] .= "홈앤쇼핑 주문 중에 교환 주문이 있습니다.\n\n";
                    break;
                case 7:
                    $val['msg'] .= "GS SHOP 주문 중에 교환 주문이 있습니다.\n\n";
                    break;
                case 9:
                    $val['msg'] .= "롯데닷컴 주문 중에 추가, 교환 주문이 있습니다.\n\n";
                    break;
                case 14:
                    $val['msg'] .= "롯데아이몰 주문 중에 교환 주문이 있습니다.\n\n";
                    break;
                case 26:
                    $val['msg'] .= "cjmall 주문 중에 교환 주문이 있습니다.\n\n";
                    break;
                case 27:
                case 70:
                    $val['msg'] .= "하프클럽, 오가게(통합) 주문 중에 교환 주문이 있습니다.\n\n";
                    break;
                case 1:
                case 2:
                    $val['msg'] .= "옥션, 지마켓 주문 중에 방문수령 주문이 있습니다.\n\n";
                    break;
            }
        }

        // 옥션, 지마켓, 11번가, 샵N, 체크아웃 방문수령 자동보류
        if( $_SESSION[BALJU_ORDER_G] )
        {
            $query = "update orders 
                         set hold = 1 
                       where status=0 
                         and order_status=50 
                         and hold=0 
                         and shop_id % 100 in (1,2,5,50,51) 
                         and order_type in ('방문수령','[방문수령]인증전') ";
            mysql_query($query, $connect);

            $query = "select if(pack>0, pack, seq) pack_seq 
                        from orders 
                       where status=0 
                         and order_status=50 
                         and shop_id % 100 in (1,2,5,50,51) 
                         and order_type in ('방문수령','[방문수령]인증전')
                       group by pack_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $query_cs = "insert csinfo 
                                set order_seq      = $data[pack_seq],
                                    input_date     = now(),
                                    input_time     = now(),
                                    writer         = '$_SESSION[LOGIN_NAME]',
                                    cs_type        = 1,
                                    cs_reason      = '',
                                    cs_result      = 0,
                                    content        = '방문수령 자동보류' ";
                mysql_query($query_cs, $connect);
            }
        }

        // 특수주문 자동보류
        if( $_SESSION[BALJU_ORDER_J] && count($special_seq_arr) > 0 )
        {
            $query = "update orders 
                         set hold = 1 
                       where hold=0 
                         and seq in (" . implode(",", $special_seq_arr) . ") ";
            mysql_query($query, $connect);

            foreach( $special_seq_arr as $_v )
            {
                $query_cs = "insert csinfo 
                                set order_seq      = $_v,
                                    input_date     = now(),
                                    input_time     = now(),
                                    writer         = '$_SESSION[LOGIN_NAME]',
                                    cs_type        = 1,
                                    cs_reason      = '',
                                    cs_result      = 0,
                                    content        = '특수주문 자동보류' ";
                mysql_query($query_cs, $connect);
            }
        }
        
        //+++++++++++++++++++++++++++++++++++++
        // paperplane 종합몰 즉시배송처리
        if( _DOMAIN_ == 'paperplane' )
        {
            $query = "update orders 
                         set status=8
                            ,trans_date=now()
                            ,trans_date_pos=now()
                            ,trans_no=code2
                            ,pack_check='' 
                       where status=0 
                         and order_status=50 
                         and shop_id = 10080 ";
            mysql_query( $query, $connect );
        }
        
        //+++++++++++++++++++++++++++++++++++++
        // 일반주문
        // order_status=50인 주문의 status를 1로 한다.
        $query = "update orders 
                     set status=1
                        ,pack_check='' 
                   where status=0 
                     and order_status=50 
                     and shop_id % 100 not in (78,79) ";
debug("발주완료 query : " . $query);
        mysql_query( $query, $connect );
        
        //+++++++++++++++++++++++++++++++++++++
        // 이베이 스마트배송
        $query = "update orders 
                     set status=8
                        ,trans_date=now()
                        ,trans_date_pos=now()
                        ,trans_no='스마트배송'
                        ,pack_check='' 
                   where status=0 
                     and order_status=50 
                     and shop_id % 100 in (78,79) ";
        mysql_query( $query, $connect );

        $val['error'] = 0;

        // Lock End
        $obj_lock = new class_lock(505);
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
        echo json_encode( $val );
    }
    
    //////////////////////////////////////////////////////////
    // 사용기간 만료 확인
    function check_pay_date()
    {
        global $sys_connect;
        $sys_connect = sys_db_connect();
        
        // hide_timeout_popup 값이 1 이면 만료확인 하지 않는다.
        $query = "select hide_timeout_popup from sys_domain where id='" . _DOMAIN_ . "'";
        $result = mysql_query( $query, $sys_connect );
        $data = mysql_fetch_assoc($result);
        if( $data[hide_timeout_popup] == 1 )
            return 0;
        
		//-- 15일경과시, expired
        $query = "select id from sys_domain where id='" . _DOMAIN_ . "' and svc_enddate < DATE_SUB(date_format(now(), '%Y-%m-%d'), INTERVAL 15 DAY)";
        $result = mysql_query( $query, $sys_connect );
        if( mysql_num_rows($result) > 0 )        
            return 1;


		//-- check holiday
		$today = date("Y-m-d");
		$sql = "select is_holiday from sys_calendar 
				 where dt = '$today' and is_holiday = 1";
		$list = @mysql_fetch_assoc(mysql_query($sql, $sys_connect));
		if ($list)
			$is_holiday = true;
		else 
			$is_holiday = false;

		//-- check dayname
		$sql = "select DAYNAME(curdate()) as dayname";
		$list = @mysql_fetch_assoc(mysql_query($sql, $sys_connect));
		if ($list[dayname] == "Saturday" || $list[dayname] == "Sunday")
			$is_holiday = true;


		//-- 10일 경과시, check
        $query = "select id from sys_domain where id='" . _DOMAIN_ . "' and svc_enddate < DATE_SUB(date_format(now(), '%Y-%m-%d'), INTERVAL 10 DAY)";
        $result = mysql_query( $query, $sys_connect );
        if( mysql_num_rows($result) > 0 ) {

			if ($is_holiday == true) return 0;
			if (date("H") < 10) return 0;

            return 1;

	    } else
            return 0;
    }

    //////////////////////////////////////////////////////////
    // 주문 복사 - 합포불가상품 찢기용
    // 
    // pack=0, 정산코드=0, supply_price=0, amount=0
    //
    function copy_order($seq, $seq_prd, $qty, $is_gift=0)
    {
        global $connect;

        // 원본 order_products
        $query = "select * from order_products where seq=$seq_prd";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[is_gift] )
            $qty_ratio = 0;
        else
        {
            // 사은품이 아닌 전체 상품 개수
            $query_qty = "select sum(qty) sum_qty from order_products where order_seq=$seq and is_gift=0 ";
            $result_qty = mysql_query($query_qty, $connect);
            $data_qty = mysql_fetch_assoc($result_qty);
            
            $qty_ratio = $qty / $data_qty[sum_qty];
        }

        // 원본 orders
        $query = "select * from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $new_val1  = round($qty_ratio * $data[qty]);
        $new_val2  = round($qty_ratio * $data[supply_price]);
        $new_val3  = round($qty_ratio * $data[amount]);
        $new_val4  = round($qty_ratio * $data[code11]);
        $new_val5  = round($qty_ratio * $data[code12]);
        $new_val6  = round($qty_ratio * $data[code13]);
        $new_val7  = round($qty_ratio * $data[code14]);
        $new_val8  = round($qty_ratio * $data[code15]);
        $new_val9  = round($qty_ratio * $data[code16]);
        $new_val10 = round($qty_ratio * $data[code17]);
        $new_val11 = round($qty_ratio * $data[code18]);
        $new_val12 = round($qty_ratio * $data[code19]);
        $new_val13 = round($qty_ratio * $data[code20]);
        $new_val14 = round($qty_ratio * $data[code31]);
        $new_val15 = round($qty_ratio * $data[code32]);
        $new_val16 = round($qty_ratio * $data[code33]);
        $new_val17 = round($qty_ratio * $data[code34]);
        $new_val18 = round($qty_ratio * $data[code35]);
        $new_val19 = round($qty_ratio * $data[code36]);
        $new_val20 = round($qty_ratio * $data[code37]);
        $new_val21 = round($qty_ratio * $data[code38]);
        $new_val22 = round($qty_ratio * $data[code39]);
        $new_val23 = round($qty_ratio * $data[code40]);
        
        // 복사 쿼리 orders
        $new_query = "insert orders set ";
        
        // 기존주문 update 쿼리
        $old_query = "update orders set ";
        
        foreach( $data as $key => $val )
        {
            if( $key == "seq" ) continue;
            
            if( $key == "pack"         ||
                $key == "status" )
                $new_query .= "$key=0,";

            else if( $key == "qty"          )
            {
                $new_query .= "$key=$new_val1,";
                $old_query .= "$key=$val - $new_val1,";
            }
            else if( $key == "supply_price" )
            {
                $new_query .= "$key=$new_val2,";
                $old_query .= "$key=$val - $new_val2,";
            }
            else if( $key == "amount"       )
            {
                $new_query .= "$key=$new_val3,";
                $old_query .= "$key=$val - $new_val3,";
            }
            else if( $key == "code11"       )
            {
                $new_query .= "$key=$new_val4,";
                $old_query .= "$key=$val - $new_val4,";
            }
            else if( $key == "code12"       )
            {
                $new_query .= "$key=$new_val5,";
                $old_query .= "$key=$val - $new_val5,";
            }
            else if( $key == "code13"       )
            {
                $new_query .= "$key=$new_val6,";
                $old_query .= "$key=$val - $new_val6,";
            }
            else if( $key == "code14"       )
            {
                $new_query .= "$key=$new_val7,";
                $old_query .= "$key=$val - $new_val7,";
            }
            else if( $key == "code15"       )
            {
                $new_query .= "$key=$new_val8,";
                $old_query .= "$key=$val - $new_val8,";
            }
            else if( $key == "code16"       )
            {
                $new_query .= "$key=$new_val9,";
                $old_query .= "$key=$val - $new_val9,";
            }
            else if( $key == "code17"       )
            {
                $new_query .= "$key=$new_val10,";
                $old_query .= "$key=$val - $new_val10,";
            }
            else if( $key == "code18"       )
            {
                $new_query .= "$key=$new_val11,";
                $old_query .= "$key=$val - $new_val11,";
            }
            else if( $key == "code19"       )
            {
                $new_query .= "$key=$new_val12,";
                $old_query .= "$key=$val - $new_val12,";
            }
            else if( $key == "code20"       )
            {
                $new_query .= "$key=$new_val13,";
                $old_query .= "$key=$val - $new_val13,";
            }
            else if( $key == "code31"       )
            {
                $new_query .= "$key=$new_val14,";
                $old_query .= "$key=$val - $new_val14,";
            }
            else if( $key == "code32"       )
            {
                $new_query .= "$key=$new_val15,";
                $old_query .= "$key=$val - $new_val15,";
            }
            else if( $key == "code33"       )
            {
                $new_query .= "$key=$new_val16,";
                $old_query .= "$key=$val - $new_val16,";
            }
            else if( $key == "code34"       )
            {
                $new_query .= "$key=$new_val17,";
                $old_query .= "$key=$val - $new_val17,";
            }
            else if( $key == "code35"       )
            {
                $new_query .= "$key=$new_val18,";
                $old_query .= "$key=$val - $new_val18,";
            }
            else if( $key == "code36"       )
            {
                $new_query .= "$key=$new_val19,";
                $old_query .= "$key=$val - $new_val19,";
            }
            else if( $key == "code37"       )
            {
                $new_query .= "$key=$new_val20,";
                $old_query .= "$key=$val - $new_val20,";
            }
            else if( $key == "code38"       )
            {
                $new_query .= "$key=$new_val21,";
                $old_query .= "$key=$val - $new_val21,";
            }
            else if( $key == "code39"       )
            {
                $new_query .= "$key=$new_val22,";
                $old_query .= "$key=$val - $new_val22,";
            }
            else if( $key == "code40"       )
            {
                $new_query .= "$key=$new_val23,";
                $old_query .= "$key=$val - $new_val23,";
            }

            else if( $key == "gift" )
                $new_query .= "$key='',";
            else if( $key == "unpack_type" )
                $new_query .= "$key=1,";
            else if( $key == "unpack_org" )
                $new_query .= "$key=$data[seq],";
            else if( $key == "org_seq" )
                $new_query .= "$key=$data[seq],";
            else if( $key == "order_id" && $is_gift==1 )
                $new_query .= "order_id='" . $val . "_사은품(" . $data[seq] . ")',";
            else if( $val === "" )
                $new_query .= "$key='',";
            else if( $val === null )
                continue;
            else
                $new_query .= "$key='$val',";
        }
        // 마지막 , 삭제
        $new_query = substr($new_query, 0, -1);
debug("insert orders : $new_query");
        mysql_query($new_query,$connect);

        // seq 읽어오기
        $query = "select seq, recv_tel, recv_mobile, order_tel, order_mobile from orders where status=0 and order_status=50 order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $new_seq = $data[seq];
        
        // 기존주문 update
        $old_query = substr($old_query,0,-1) . " where seq=$seq";
debug("update orders : $old_query");
        mysql_query($old_query, $connect);
        
        // 전화번호
        $this->inset_tel_info($new_seq, array($data[recv_tel],$data[recv_mobile],$data[order_tel],$data[order_mobile]));
        
        // 원본 order_products 
        $query = "select * from order_products where seq=$seq_prd";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
debug("copy order");
debug_array( $data );

        $new_val1  = $qty;
        
        $prd_qty_ratio = $qty / $data[qty];
        
        $new_val2  = round($prd_qty_ratio * $data[prd_supply_price]);
        $new_val3  = round($prd_qty_ratio * $data[prd_amount]);
        $new_val4  = round($prd_qty_ratio * $data[org_price]);
        $new_val5  = round($prd_qty_ratio * $data[shop_price]);

        // 복사 쿼리 order_products
        $new_query = "insert order_products set ";

        // 기존 쿼리 order_products update
        $old_query = "update order_products set ";

        foreach( $data as $key => $val )
        {
            if( $key == "seq" || !$val ) continue;
            
            if( $key == "order_seq" )
                $new_query .= "order_seq=$new_seq,";
            else if( $key == "qty" )
            {
                $new_query .= "$key=$new_val1,";
                $old_query .= "$key=qty - $new_val1,";
            }
            else if( $key == "prd_supply_price" )
            {
                $new_query .= "$key=$new_val2,";
                $old_query .= "$key=prd_supply_price - $new_val2,";
            }
            else if( $key == "prd_amount" )
            {
                $new_query .= "$key=$new_val3,";
                $old_query .= "$key=prd_amount - $new_val3,";
            }
            else if( $key == "org_price" )
            {
                $new_query .= "$key=$new_val4,";
                $old_query .= "$key=org_price - $new_val4,";
            }
            else if( $key == "shop_price" )
            {
                $new_query .= "$key=$new_val5,";
                $old_query .= "$key=shop_price - $new_val5,";
            }
            else if( $val === "" )
                $new_query .= "$key='',";
            else if( $val === null )
                continue;
            else
                $new_query .= "$key='$val',";
        }
        // 마지막 , 삭제
        $new_query = substr($new_query, 0, -1);
debug("insert order_products : $new_query");
        mysql_query($new_query,$connect);
        
        // 기존쿼리 update
        $old_query = substr($old_query, 0, -1) . " where seq=$seq_prd";
debug("update order_products : $old_query");
        mysql_query($old_query,$connect);
        
        return $new_seq;
    }

    // 같은 수량의 사은품 있을 경우 같이 찢기
    function copy_order_gift($gift_arr, $new_seq, $qty)
    {
        global $connect;
        
        foreach($gift_arr as $gift_seq)
        {
            // 원본 order_products 
            $query = "select * from order_products where seq=$gift_seq";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            // 복사 쿼리 order_products
            $query = "insert order_products set ";
            foreach( $data as $key => $val )
            {
                if( $key == "seq" || !$val ) 
                    continue;
                else if( $key == "order_seq" )
                    $query .= "order_seq=$new_seq,";
                else if( $key == "qty" )
                    $query .= "qty=$qty,";
                else if( $val === "" )
                    $query .= "$key='',";
                else if( $val === null )
                    continue;
                else
                    $query .= "$key='$val',";
            }
            // 마지막 , 삭제
            $query = substr($query, 0, -1);
            mysql_query($query,$connect);
        }
    }
    
    // CTI_ORDERS
    function insert_cti_orders()
    {
        global $connect;
        
        // 1달 전 자료 삭제
        $s_date = date("Y-m-d",strtotime("-1 month"));
        $query = "delete from cti_orders where crdate<'$s_date 00:00:00'";
        mysql_query($query, $connect);
        
        //*****************
        // 발주 단품
        //*****************
        $query = "select seq, 
                         order_tel, 
                         order_mobile, 
                         recv_tel, 
                         recv_mobile, 
                         recv_name, 
                         shop_id, 
                         qty,
                         amount
                    from orders 
                   where status = 0 and 
                         order_status = 50 and
                         pack = 0";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            $tel_arr = array();
            
            // 판매처명
            $shop_str = "SHOP_NAME_" . $data[shop_id];
            $shop_name = $_SESSION[$shop_str];
            
            // 각 전화번호에서 숫자 이외의 문자를 제거하고, 공백이 아닌경우 전화번호 배열에 넣는다.
            $order_tel = preg_replace('/[^0-9]/','',$data[order_tel]);
            if( $order_tel > '' )  $tel_arr[] = $order_tel;

            $order_mobile = preg_replace('/[^0-9]/','',$data[order_mobile]);
            if( $order_mobile > '' )  $tel_arr[] = $order_mobile;

            $recv_tel = preg_replace('/[^0-9]/','',$data[recv_tel]);
            if( $recv_tel > '' )  $tel_arr[] = $recv_tel;

            $recv_mobile = preg_replace('/[^0-9]/','',$data[recv_mobile]);
            if( $recv_mobile > '' )  $tel_arr[] = $recv_mobile;
            
            // 전화번호 중복 제거
            $new_tel_arr = array_unique($tel_arr);
            
            foreach( $new_tel_arr as $tel )
            {
                $query_insert = "insert cti_orders
                                    set order_seq = $data[seq],
                                        tel       = '$tel',
                                        name      = '$data[recv_name]',
                                        shop_name = '$shop_name',
                                        qty       = $data[qty],
                                        amount    = $data[amount],
                                        crdate    = now()";
                mysql_query($query_insert, $connect);
            }
        }
        
        //*****************
        // 발주 합포
        //*****************
        $query = "select pack
                    from orders 
                   where status = 0 and 
                         order_status = 50 and
                         pack > 0
                   group by pack
                   order by pack";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            $tel_arr = array();
            $tel_name = array();
            
            $qty = 0;
            $amount = 0;

            $query_pack = "select seq, 
                                  pack,
                                  order_tel, 
                                  order_mobile, 
                                  order_name,
                                  recv_tel, 
                                  recv_mobile, 
                                  recv_name, 
                                  shop_id, 
                                  qty,
                                  amount
                             from orders 
                            where pack = $data[pack]";
            $result_pack = mysql_query($query_pack, $connect);
            while( $data_pack = mysql_fetch_assoc($result_pack) )
            {
                // 각 전화번호에서 숫자 이외의 문자를 제거하고, 공백이 아닌경우 전화번호 배열에 넣는다.
                // 각 전화번호에 수령자명을 넣은 배열을 만든다.
                $order_tel = preg_replace('/[^0-9]/','',$data_pack[order_tel]);
                if( $order_tel > '' )
                {
                    $tel_arr[] = $order_tel;
                    $tel_name[$order_tel] = $data_pack[order_name];
                }
    
                $order_mobile = preg_replace('/[^0-9]/','',$data_pack[order_mobile]);
                {
                    $tel_arr[] = $order_mobile;
                    $tel_name[$order_mobile] = $data_pack[order_name];
                }
    
                $recv_tel = preg_replace('/[^0-9]/','',$data_pack[recv_tel]);
                {
                    $tel_arr[] = $recv_tel;
                    $tel_name[$recv_tel] = $data_pack[recv_name];
                }
    
                $recv_mobile = preg_replace('/[^0-9]/','',$data_pack[recv_mobile]);
                {
                    $tel_arr[] = $recv_mobile;
                    $tel_name[$recv_mobile] = $data_pack[recv_name];
                }

                // 합포 기준주문 - 판매처명
                if( $data_pack[seq] == $data_pack[pack] )
                {
                    $shop_str = "SHOP_NAME_" . $data_pack[shop_id];
                    $shop_name = $_SESSION[$shop_str];
                }
                
                $qty += $data_pack[qty];
                $amount += $data_pack[amount];
            }
            
            // 전화번호 중복 제거
            $new_tel_arr = array_unique($tel_arr);
            
            foreach( $new_tel_arr as $tel )
            {
                if( !$tel )  continue;

                $query_insert = "insert cti_orders
                                    set order_seq = $data[pack],
                                        tel       = '$tel',
                                        name      = '" . $tel_name[$tel] . "',
                                        shop_name = '$shop_name',
                                        qty       = $qty,
                                        amount    = $amount,
                                        crdate    = now()";
                mysql_query($query_insert, $connect);
            }
        }
        
    }
    
    // box4u 합포 유지
    function keep_pack()
    {
        global $connect, $template;
        global $seq, $chk;
        
        $query = "update orders set code30=$chk where seq=$seq";
        mysql_query($query, $connect);

        $val = array();
        $val['seq'] = $seq;
        $val['chk'] = $chk;
        echo json_encode($val);
    }


    // 타오바오발주
    function upload3_tb($upload_file1, $upload_file2, &$ret)
    {
        global $_file;

        $ret = 0;
        
        // php 숫자 자리수
        ini_set("precision", "16");

        // 
        ini_set("memory_limit", "400M");
        
        $excel_file1 = _balju_dir . $upload_file1;
        if (!move_uploaded_file($_FILES['_file1']['tmp_name'], $excel_file1))
            fatal("file upload failed #1");

        $excel_file2 = _balju_dir . $upload_file2;
        if (!move_uploaded_file($_FILES['_file2']['tmp_name'], $excel_file2))
            fatal("file upload failed #2");

        //===================================
        // part 2. data transaction 
        $_result1 = array();
        $_result2 = array();

        require_once "Classes/PHPExcel.php";

        $objReader = new PHPExcel_Reader_Excel5();
        $objReader->setReadDataOnly(true);

/*
        require_once "Classes/PHPExcel.php";

        $objReader = new PHPExcel_Reader_CSV();

        $objReader->setInputEncoding('euccn');
        $objReader->setDelimiter(',');
        $objReader->setEnclosure('"');
        $objReader->setLineEnding("\r\n");
        $objReader->setSheetIndex(0);
*/

        // #1
        $objPHPExcel = $objReader->load($excel_file1);
        $objPHPExcel->setActiveSheetIndex(0);
        $_result1 = $objPHPExcel->getActiveSheet()->toArray();
        
        // #2
        $objPHPExcel = $objReader->load($excel_file2);
        $objPHPExcel->setActiveSheetIndex(0);
        $_result2 = $objPHPExcel->getActiveSheet()->toArray();

        $_order_arr = array();
        foreach( $_result1 as $_r )
        {
            foreach( $_r as $_c )
                $_order_arr[(string)$_r[0]][] = $_c;
        }

        $_result = array();
        foreach( $_result2 as $_r )
        {
            
            foreach( $_order_arr[(string)$_r[0]] as $_col )
                $_r[] = $_col;

            $_result[] = $_r;
        }

        return $_result;
    }
}
?>
