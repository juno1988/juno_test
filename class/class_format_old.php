<?
require_once "class_shop.php";

class class_format extends class_top
{
    function make_orders($dataArr, $shop_id, &$cnt_total, &$cnt_new, &$msg, $is_auto=0, $old_fn, $new_fn) 
    {
        global $sys_connect, $connect, $collect_date, $is_auto;

        // 발주 코드를 생성한다. 
        $query = "select seq from order_process_log order by seq desc";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) > 0 )
        {
            $data = mysql_fetch_array( $result );
            $orders_code = $data[seq] + 1;
        }
        else
            $orders_code = 1;

        // 발주 작업 log 기록
        $worker = $_SESSION[LOGIN_NAME] . ( $is_auto ? "(자동)" : "" );
        $query = "insert order_process_log 
                     set seq       = $orders_code, 
                         shop_id   = $shop_id, 
                         work_date = now(),
                         worker    = '$worker',
                         old_fn    = '$old_fn',
                         new_fn    = '$new_fn'";
debug( "발주 작업 log 기록 : " . $query );
        if( !mysql_query( $query, $connect ) )
        {
            $msg = "발주 코드 생성에 실패했습니다. 다시 발주하시기 바랍니다.<br><br>" .
                   "이 오류가 계속 발생되면 고객센터로 문의바랍니다.";
            return 4;
        }

        // 판매처 이름 
        $query_shopinfo = "select * from shopinfo where shop_id=$shop_id";
        $result_shopinfo = mysql_query($query_shopinfo, $connect);
        $data_shopinfo = mysql_fetch_assoc($result_shopinfo);
        
        // 업체별로 설정된 헤더 정보로 헤더배열을 만든다.
        $headerArr = array();
        $query = "select * from shopheader where shop_id=$shop_id order by seq";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            $headerArr["header"]["$data[field_id]"] = str_replace( " ", "", $data['shop_header'] );
            $headerArr["abs"   ]["$data[field_id]"] = "$data[abs]";
        }

        // "주문번호" 를 기준으로 헤더 열을 찾느다.
        $find=false;
        $header_pos = 0;
        for( $i=0; $i<count($dataArr); $i++ )
        {
            for( $j=0; $j<count($dataArr[$i]); $j++ )
            {
                $order_id_arr = explode("^", $headerArr["header"]["order_id"]);
                foreach( $order_id_arr as $_h )
                {
                    if( str_replace( array(" ","\n","\r"), "", $dataArr[$i][$j] ) == $_h )
                        $find = true;
                }
            }
            if( $find )
            {
                $header_pos = $i;
                break;
            }
        }
        // 헤더를 찾으면 헤더 위는 삭제
        if( $find )
        {
            for($i=0; $i<$header_pos; $i++)
                array_shift( $dataArr );
        }
        // 헤더를 못찾으면 에러
        else
        {
            $msg = "발주 오류!!!<br><br>" . 
                   "발주서에서 헤더를 찾을 수 없습니다.";
            return 6;
        }
        
        // 동일 헤더 두번째 찾기
        $first_header = true;
        
        // 발주 데이터에서 헤더 위치를 찾는다
        $hPosArr = array();
        foreach( $headerArr["header"] as $field => $headerOrg )
        {
            // ** 선택헤더와 복수헤더 동시 사용 불가
            
            $headers = explode( "^", $headerOrg );

            // 선택헤더
            if( count($headers) > 1 )
            {
                $find_ok = false;
                foreach( $headers as $header )
                {
                    foreach( $dataArr[0] as $pos => $hStr )
                    {
                        // 헤더 찾음
                        if( $header == str_replace( array(" ","\n","\r"), "", $hStr ) )
                        {
                            if( isset($hPosArr["$field"]) )
                                $hPosArr["$field"] .= ",".$pos;
                            else
                                $hPosArr["$field"] = $pos;
                            $find_ok = true;
                            break;
                        }
                    }
                    if( !$find_ok )
                        continue;
                    else
                        break;
                }
                if( !$find_ok ) // 찾기 실패
                {
                    if( $headerArr["abs"]["$field"] )  // 필수 필드이면 에러
                    {
                        $header_err = $header;
                        $msg = "발주서에서 [" . $header_err . "] 필드를 찾을수 없습니다.<br><br>발주서 포멧을 확인하세요.";
                        return 1;
                    }else  // 옵션 필드이면 -1 저장
                        $hPosArr["$field"] = -1;
                }
            }
            else
            {
                // 복수헤더 검사
                $headers = explode( "|", $headerOrg );
                foreach( $headers as $header )
                {
                    $find_ok = false;
                    foreach( $dataArr[0] as $pos => $hStr )
                    {
                        // 시스템 데이터
                        if( substr($header,0,4)=='pimz' )
                        {
                            $hPosArr["$field"] = $header;
                            continue 2;
                        }
                        
                        // 헤더 찾음
                        if( $header == str_replace( array(" ","\n","\r"), "", $hStr ) )
                        {
                            // soulmi 10085 판매처 '우편번호' 필드가 2개임. 두번째 '우편번호' 필드가 수령자 우편번호임.
                            if( _DOMAIN_ == 'soulmi' && $shop_id == 10085 && $field == 'recv_zip' )
                            {
                                if( $first_header )
                                {
                                    $first_header = false;
                                    continue;
                                }                                    
                            }
                            
                            if( isset($hPosArr["$field"]) )
                                $hPosArr["$field"] .= ",".$pos;
                            else
                                $hPosArr["$field"] = $pos;
                            $find_ok = true;
                            break;
                        }
                    }
                    if( !$find_ok ) // 찾기 실패
                    {
                        if( $headerArr["abs"]["$field"] )  // 필수 필드이면 에러
                        {
                            $header_err = $header;
                            $msg = "발주서에서 [" . $header_err . "] 필드를 찾을수 없습니다.<br><br>발주서 포멧을 확인하세요.";
                            return 1;
                        }else  // 옵션 필드이면 -1 저장
                            $hPosArr["$field"] = -1;
                    }
                }
            }
        }

        // orders 배열을 만든다.
        $orderArr = array();
        $k = 0;
        for( $i=1; $i < count($dataArr); $i++ )  // 발주 루프
        {
            foreach( $hPosArr as $field => $posOrg )  // 필드 루프
            {
                if( $posOrg == -1 )    // 발견 못한 옵션헤더는 ""로 한다.
                {
                    $orderArr[$k][$field] ="";
                    continue;
                }
                else if( substr($posOrg,0,4) == 'pimz' )  // 시스템 데이터
                {
                    // 판매처명
                    if( substr($posOrg,5,9) == 'shop_name' )
                        $orderArr[$k][$field] = $data_shopinfo[shop_name];
                    // 쿠폰금액
                    else if( substr($posOrg,5,6) == 'code_c' )
                    {
                        $code_str = "code" . substr($posOrg,11);
                        $code_value = $orderArr[$k][$code_str];
                        
                        preg_match('/^\[([0-9]+)\]/', $code_value, $matches);
                        $orderArr[$k][$field] = $matches[1];
                    }
                    // 수량 : 무조건 1
                    else if( substr($posOrg,5,3) == 'qty' )
                    {
                        $pimz_qty = substr($posOrg,9,1);
                        $orderArr[$k][$field] = ( $pimz_qty > 0 ? $pimz_qty : 1 );
                    }
                    continue;
                }
                
                $orderArr[$k][$field] = '';
                $posArr = explode( ",", $posOrg );
                if( count($posArr) > 1 )
                {
                    $eachTitle = explode("|",$headerArr["header"][$field]);
                    for( $j=0; $j<count($posArr); $j++ )  // 복수 필드 루프
                    {
                        if( $dataArr[$i][$posArr[$j]] )
                        {
                            // 주소, 메모 필드는 헤더를 추가하지 않는다.
                            if( $field == 'recv_address' )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'","??"), ' ', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            else if( $field == 'memo' )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'"), '', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            else if( $field == 'recv_zip' )
                                $orderArr[$k][$field] .= ($orderArr[$k][$field] ? "-" : "") . str_replace( array("\\",'"',"'"), '', trim($dataArr[$i][$posArr[$j]]) );
                            // 정산코드는 값을 더한다
                            else if( $field == 'code11' || 
                                     $field == 'code12' || 
                                     $field == 'code13' || 
                                     $field == 'code14' || 
                                     $field == 'code15' || 
                                     $field == 'code16' || 
                                     $field == 'code17' || 
                                     $field == 'code18' || 
                                     $field == 'code19' || 
                                     $field == 'code20' || 
                                     $field == 'code31' || 
                                     $field == 'code32' || 
                                     $field == 'code33' || 
                                     $field == 'code34' || 
                                     $field == 'code35' || 
                                     $field == 'code36' || 
                                     $field == 'code37' || 
                                     $field == 'code38' || 
                                     $field == 'code39' || 
                                     $field == 'code40'  )
                                $orderArr[$k][$field] += str_replace( array("\\",'"',"'",","), '', trim($dataArr[$i][$posArr[$j]]) );
                            // 코드 필드는 중간에 '_'를 넣는다. flora2를 제외한 업체의 상품코드에도 '_' 사용
                            else if( substr($field,0,4) == 'code' || ( _DOMAIN_ != 'flora2' && $field == 'shop_product_id' ) )
                                $orderArr[$k][$field] .= ($orderArr[$k][$field] ? "_" : "") . str_replace( array("\\",'"',"'"), '', trim($dataArr[$i][$posArr[$j]]) );
                            else
                                $orderArr[$k][$field] .= "<" . $eachTitle[$j] . ">" . str_replace( array("\\",'"',"'"), '', trim($dataArr[$i][$posArr[$j]]) );
                        }
                    }
                }
                else
                {
                    if( $field == 'recv_address' )
                        $orderArr[$k][$field] = str_replace( array("\\",'"',"'","??"), ' ', trim( $dataArr[$i][$posArr[0]] ) );
                    else
                        $orderArr[$k][$field] = str_replace( array("\\",'"',"'"), '', trim( $dataArr[$i][$posArr[0]] ) );
                }
            }
            
            // 주문구분
            $order_type = $orderArr[$k]["order_type"];
            $order_type2 = $orderArr[$k]["order_type2"];
            $shop_code = $shop_id % 100;
            
            // 주문번호 필드값이 있어야만 orderArr에 넣는다. 주문번호가 "합계" 이면 제외한다.
            if( $orderArr[$k]["order_id"] == "" || $orderArr[$k]["order_id"] == "합계" )
                array_pop( $orderArr );
            // 상품코드 필드값이 있어야만 orderArr에 넣는다. 일반발주 제외
            else if( $orderArr[$k]["shop_product_id"] == "" && ($_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2) )
            {
                $msg = "상품코드가 없는 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // 수동발주이고 수령자명 없으면 오류
            else if( !$is_auto && $orderArr[$k]["recv_name"] == "" )
            {
                $msg = "수령자명이 없는 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // somi 
            else if( 
                _DOMAIN_ == 'somi' && $shop_code != 27 && $shop_code != 2 && $shop_code != 68 &&
                (
                    (
                        ( $shop_code != 7 ) &&
                        ( substr($orderArr[$k]["product_name"],0,strlen('[알트베니]')) != '[알트베니]' &&
                          substr($orderArr[$k]["product_name"],0,strlen('[오클락][알트베니]')) != '[오클락][알트베니]'
                        )
                    ) ||
                    ( 
                        ( $shop_code == 7 ) &&
                        ( substr($orderArr[$k]["product_name"],0,strlen('A-')) != 'A-' )
                    )
                )
            )
                array_pop( $orderArr );
            // somi2
            else if( 
                _DOMAIN_ == 'somi2' && $shop_code != 8 &&
                (
                    (
                        ( $shop_code != 7 ) &&
                        ( substr($orderArr[$k]["product_name"],0,strlen('[알트베니]')) == '[알트베니]' )
                    ) ||
                    ( 
                        ( $shop_code == 7 ) &&
                        ( substr($orderArr[$k]["product_name"],0,strlen('A-')) == 'A-' )
                    )
                )
            )
                array_pop( $orderArr );
            // GS홈쇼핑 
            else if( $shop_code == 7 )
            {
                // order_type (주문유형) = "주문".   이전에는 "주문취소일"을 체크 했으나 변경됨.
                if( $order_type == "주문" )
                    $k++;
                // order_type (주문유형) 가 '교환주문'인 경우
                else if( $order_type == "교환주문" && $_SESSION[BALJU_ORDER_D] )
                    $k++;
                else
                {
                    array_pop( $orderArr );
                    debug( "GS홈쇼핑 주문구분 오류 : " . $order_type );
                }
            }
            // 롯데닷컴
            else if( $shop_code == 9 )
            {
                if( $order_type == "주문" )
                    $k++;
                // order_type (배송구분) 가 '맞교환'인 경우, 주문번호 뒤에 "맞교환" + '주문상세번호(주문내역일련번호)' 추가하여 발주 => 환경설정
                else if( $order_type == "맞교환" && $_SESSION[BALJU_ORDER_A] )
                {
                    $orderArr[$k]["order_id"] .= "_맞교환" . $orderArr[$k]["order_id_seq"];
                    $k++;
                }
                // order_type (배송구분) 가 '추가'인 경우, 주문번호 뒤에 "추가" + '주문상세번호(주문내역일련번호)' 추가하여 발주 => 환경설정
                else if( $order_type == "추가" && $_SESSION[BALJU_ORDER_C] )
                {
                    $orderArr[$k]["order_id"] .= "_추가" . $orderArr[$k]["order_id_seq"];
                    $k++;
                }
                // order_type (배송구분) 가 '교환주문'인 경우, 주문번호 뒤에 "교환주문" + '주문상세번호(주문내역일련번호)' 추가하여 발주 => 환경설정
                else if( $order_type == "교환주문" && $_SESSION[BALJU_ORDER_A] )
                {
                    $orderArr[$k]["order_id"] .= "_교환주문" . $orderArr[$k]["order_id_seq"];
                    $k++;
                }
                else
                {
                    array_pop( $orderArr );
                    debug( "롯데닷컴 주문구분 오류 : " . $order_type );
                }
            }
            // 롯데아이몰
            else if( $shop_code == 14 )
            {
                // order_type2 ( 취소여부 )
                if( $order_type2 == "취소" )
                    array_pop( $orderArr );
                else
                {
                    if( $order_type == "주문" )
                        $k++;
                    // order_type (주문구분) 가 '교환' 또는 '교환배송'인 경우
                    else if( ($order_type == "교환" || $order_type == "교환배송") && $_SESSION[BALJU_ORDER_E] )
                        $k++;
                    else
                    {
                        array_pop( $orderArr );
                        debug( "롯데아이몰 주문구분 오류 : " . $order_type );
                    }
                }
            }
            // 신세계 : order_type (배송유형) = "정상출하" 또는 ""
            else if( $shop_code == 15 && $order_type != "정상출하" && $order_type != "" )
            {
                array_pop( $orderArr );
                debug( "신세계 주문유형 오류 : " . $order_type );
            }
            // cj몰
            else if( $shop_code == 26 )
            {
                // order_type (주문구분) = "주문"
                if( $order_type == "주문" )
                    $k++;
                // order_type (주문구분) = "교환배송" => 환경설정
                else if( $order_type == "교환배송" && $_SESSION[BALJU_ORDER_B] )
                    $k++;
                else
                {
                    array_pop( $orderArr );
                    debug( "cj몰 주문구분 오류 : " . $order_type );
                }
            }
            // 하프클럽의 경우, 주문번호(order_id)가 "합계"면 건너뛴다.
            else if( $shop_code == 27 && $orderArr[$k]["order_id"] == "합계" )
                array_pop( $orderArr );
            // 아이하우스의 경우, 주문번호(order_id)가 "합계"면 건너뛴다.
            else if( $shop_code == 28 && $orderArr[$k]["order_id"] == "합계" )
                array_pop( $orderArr );
            // AK몰 : order_type (기수령여부) = "N"
            else if( $shop_code == 42 && $order_type != "N" )
                array_pop( $orderArr );
            // AK몰 : order_type2 (배송상태, 수령상태) = "주문"
            else if( $shop_code == 42 && $order_type2 != "주문" && $order_type2 != "추가주문" )
                array_pop( $orderArr );
            // cafe24 : pay_type (결제수단)
            else if( $shop_code == 72 )
            {
                if( $orderArr[$k]["pay_type"] == 'C' )
                    $orderArr[$k]["pay_type"] = "카드";
                else if( $orderArr[$k]["pay_type"] == 'M' )
                    $orderArr[$k]["pay_type"] = "적립금";
                else if( $orderArr[$k]["pay_type"] == 'R' )
                    $orderArr[$k]["pay_type"] = "현금";
                else if( $orderArr[$k]["pay_type"] == 'A' )
                    $orderArr[$k]["pay_type"] = "실시간계좌이체";
                else if( $orderArr[$k]["pay_type"] == 'H' )
                    $orderArr[$k]["pay_type"] = "휴대폰결제";
                else if( $orderArr[$k]["pay_type"] == 'E' )
                    $orderArr[$k]["pay_type"] = "에스크로가상계좌";
                $k++;
            }
            // 옥션, 지마켓 esm "택배사명:방문수령" 발주 안함
            else if( ($shop_code == 1 || $shop_code == 2) && $order_type == "방문수령" )
                array_pop( $orderArr );
            else
            {
                // 수량이 1 보다 작으면 에러
                if( $orderArr[$k]["qty"] < 1 )
                {
                    $msg = "수량이 1 보다 작은 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                    return 7;
                }

                // 인터파크, 메이크샵은 주문번호에 '****000000' 또는 '****E+****' 형식으로 들어오면 오류
                if( $shop_code == 6 || $shop_code == 68 )
                {
                    if( strpos($orderArr[$k]["order_id"], "E+") !== false )
                    {
                        $msg = "주문번호가 손상되었습니다. <br><br>수령자 : " . $orderArr[$k]['recv_name'] . "<br><br>발주서를 확인하세요";
                        return 7;
                    }
                }
                $k++;
            }
        }

        // 발주 로그
        for( $i=0; $i < count($orderArr); $i++ )
            debug( "발주로그 - i:" . $i . ", order_id:" . $orderArr[$i]["order_id"] );

        // 동일 판매처 교차발주 검사
        $shop_cross_check = true;
        if( $shop_id%100 == 29 ||
            $shop_id%100 == 30 ||
            $shop_id%100 == 68 ||
            $shop_id%100 == 72 ||
            $shop_id%100 == 74 ||
            $shop_id%100 >= 80 )
            $shop_cross_check = false;
        else
        {
            // 교차 발주 검사 설정 읽기
            $query = "select * from shop_cross_check where shop_id = $shop_id % 100 and not_use = 1";
            $result = mysql_query($query,$connect);
            if( mysql_num_rows($result) )
                $shop_cross_check = false;
        }

        if( $shop_cross_check )
        {
            $query = "select * from shopinfo 
                       where shop_id%100 = $shop_id%100 and 
                             shop_id <> $shop_id and 
                             balju_stop=0";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows( $result ) > 0 )
            {
                $shop_pid = "";
                foreach( $orderArr as $orderEach )
                {
                    if( $orderEach['shop_product_id'] == '' ) continue;
                      
                    if( $shop_pid )
                        $shop_pid .= ",'" . $orderEach['shop_product_id'] . "'";
                    else
                        $shop_pid = "('" . $orderEach['shop_product_id'] . "'";
                }
                
                if( $shop_pid ) 
                {
                    $shop_pid .= ")";
                    
                    $old_day = date("Y-m-d", strtotime("-31 day"));
                    
                    while( $data = mysql_fetch_array( $result ) )
                    {
                        $query_shop = "select shop_product_id from orders where shop_id = $data[shop_id] and shop_product_id in $shop_pid
                                       and collect_date > '$old_day'";
                        $result_shop = mysql_query( $query_shop, $connect );
                        if( mysql_num_rows( $result_shop ) > 0 )
                        {
                            $data_shop = mysql_fetch_assoc($result_shop);
                            $shop_name = class_shop::get_shop_name($data[shop_id]);
                            $msg = "발주 오류!!!<br><br>" . 
                                   "현재 발주중인 주문의 판매처 상품코드[$data_shop[shop_product_id]]가, 판매처 [$shop_name]의 기존주문정보에서 검색되었습니다.<br><br>" . 
                                   "발주 파일과 판매처를 확인하세요";
                            return 2;
                        }
                    }
                }
            }
        }
        
        // 선착불 키워드 정보를 가져온다.
        $beforeKey = array();
        $afterKey = array();
        $query = "select * from shop_transkey where shop_id=$shop_id";
        $result = mysql_query( $query, $connect );
        while( $data=mysql_fetch_array($result) )
        {
            if( $data[space] )  $space=$data[transwho];  // 공백 확인
            else  // 키워드
            {
                if( $data[transwho] )  $afterKey[]  = $data[keyword];   // 착불
                else                   $beforeKey[] = $data[keyword];   // 선불
            }
        }

        // "선불", "착불" 정보를 새로 넣는다.
        for( $i=0; $i<count( $orderArr ); $i++ )
        {
            // 공백일 경우
            if( $orderArr[$i][org_trans_who] == "" )
            {
                switch( $space )
                {
                    case 0: $orderArr[$i]['trans_who_ez'] = '선불'; break;
                    case 1: $orderArr[$i]['trans_who_ez'] = '착불'; break;
                    default:
                        $msg = "선착불 정보에 공백이 있습니다.<br><br>" .
                               "발주 정보와 발주서 포멧 정보를 확인하세요.";
                        return 3;
                }
            }
            else
            {
                $trans_who_flag = false;
                // 선불인지 확인
                foreach( $beforeKey as $before )
                {
                    // 금액 이상 설정
                    if( preg_match('/^\[UP\]([0-9]+)/i', $before, $matches) )
                    {
                        if( $matches[1] <= $orderArr[$i][org_trans_who] )
                        {
                            $trans_who_ez = '선불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                    // 키워드 포함
                    else if( preg_match('/^\[\+\](.+)/i', $before, $matches) )
                    {
                        if( strpos( $orderArr[$i][org_trans_who], $matches[1] ) !== false )
                        {
                            $trans_who_ez = '선불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                    // 키워드 제외
                    else if( preg_match('/^\[\-\](.+)/i', $before, $matches) )
                    {
                        if( strpos( $orderArr[$i][org_trans_who], $matches[1] ) === false )
                        {
                            $trans_who_ez = '선불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                    else
                    {
                        if( $before == $orderArr[$i][org_trans_who] )
                        { 
                            $trans_who_ez = '선불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                }
                if( $trans_who_flag )
                {
                    $orderArr[$i]['trans_who_ez'] = $trans_who_ez;
                    continue;                
                }
                
                // 착불인지 확인
                foreach( $afterKey as $after )
                {
                    // 키워드 포함
                    if( preg_match('/^\[\+\](.+)/i', $after, $matches) )
                    {
                        if( strpos( $orderArr[$i][org_trans_who], $matches[1] ) !== false )
                        {
                            $trans_who_ez = '착불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                    // 키워드 제외
                    else if( preg_match('/^\[\-\](.+)/i', $after, $matches) )
                    {
                        if( strpos( $orderArr[$i][org_trans_who], $matches[1] ) === false )
                        {
                            $trans_who_ez = '착불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                    else
                    {
                        if( $after == $orderArr[$i][org_trans_who] )
                        { 
                            $trans_who_ez = '착불';  
                            $trans_who_flag = true;  
                            break;  
                        }
                    }
                }
                if( $trans_who_flag )
                {
                    $orderArr[$i]['trans_who_ez'] = $trans_who_ez;
                    continue;                
                }

                $trans_who_err = $orderArr[$i][org_trans_who];
                $msg = "선착불 정보에 등록되지 않은 키워드 '" . htmlspecialchars($trans_who_err) . "'가 발견되었습니다.<br><br>" .
                       "발주 정보와 발주서 포멧 정보를 확인하세요.";
                return 3;
            }
        }

        $cnt_total = count( $orderArr );

        // 발주 작업 log 기록
        $query = "update order_process_log 
                     set cnt_total = $cnt_total
                   where seq = $orders_code";
debug( "발주 작업 log 기록 : " . $query );
        if( !mysql_query( $query, $connect ) )
        {
            $msg = "발주 코드 생성에 실패했습니다. 다시 발주하시기 바랍니다.<br><br>" .
                   "이 오류가 계속 발생되면 고객센터로 문의바랍니다.";
            return 4;
        }
        
        $cnt_new = 0;
        foreach( $orderArr as $order )
        {
            //***********************************
            // jbstar 샵링커 판매처
            //***********************************
            if( $shop_id==10066 && (_DOMAIN_ == 'jbstar' || _DOMAIN_ == 'jbstar_test' ) )
            {
                switch( $order[code30] )
                {
                    case "(주)LG패션"       :  $_shop_id = 10084; break;
                    case "롯데아이몰"       :  $_shop_id = 10014; break;
                    case "AK몰"             :  $_shop_id = 10042; break;
                    case "신세계닷컴"       :  $_shop_id = 10015; break;
                    case "(주)롯데닷컴"     :  $_shop_id = 10009; break;
                    case "GS홈쇼핑(eshop)"  :  $_shop_id = 10007; break;
                    case "CJ홈쇼핑"         :  $_shop_id = 10026; break;
                    case "(주)현대홈쇼핑"   :  $_shop_id = 10080; break;
                    case "디앤샵"           :  $_shop_id = 10003; break;
                    case "하프클럽"         :  $_shop_id = 10127; break;
                    case "이지웰"           :  $_shop_id = 10091; break;
                    case "이마트"           :  $_shop_id = 10016; break;
                    case "샵N"              :  $_shop_id = 10051; break;
                }
            }
            else
                $_shop_id = $shop_id;
            
            // 롯데닷컴 
            if( $shop_id % 100 == 9 )  
            {
                $query = "select * from orders where shop_id=$shop_id and order_id=concat( substring('$order[order_id]',1,11), substring('$order[order_id]',13,6)) and orders_code<>$orders_code";
                $result = mysql_query( $query, $connect );
                if( mysql_num_rows( $result ) > 0 )  continue;
            }
            
            // buy7942 도서지역 발주안함
            if( _DOMAIN_ == 'buy7942' )
            {
                if( in_array( 
                        (int)preg_replace('/[^0-9]/','',$order[recv_zip]), 
                        array(409910,409911,409912,409919,409913,799820,799821,799822,799823,799810,799811,799812,799813,799800,799801,799802,799805,799803,799804,417910,417911,417912,417913,417920,417921,417922,417923,417930,417931,417932,417933,409850,409851,409852,409853,409890,409891,409892,409893,355840,355841,355842,355845,355843,355844,355846,355705,355847,355848,573810,573811,573812,573813,573814,573815,573816,573817,573955,573819,573818,579910,579911,579912,579913,579914,579915,695980,695983,695950,695951,695952,548940,548941,548943,548944,556830,556831,556832,556833,556834,556835,556836,556837,556838,556839,556840,556841,556842,556843,556844,556846,556847,556848,556849,556850,556851,556852,556853,556854,556855,513890,513891,513892,513893,537840,537841,537842,537843,536928,537849,537845,537844,537847,537846,537848,537850,537851,537852,537853,537830,537831,537832,537833,537834,537835,537836,537900,537901,537902,537903,537904,537809,537905,537909,537907,548993,537870,537871,537872,537873,527913,537874,539910,539911,539912,539913,539914,539915,539916,539917,539918,539919,618430,618450,664270,664250,650930,650931,650932,650933,650934,650910,650911,650912,650913,650914,650915,650916,650920,650921,650922,650923,650924,650925,650926,650927,535890,535891,535892,535892,535893,535891,535893,535894,535892,535891,535891,535894,535895,535896,535897,535898,535896,535893,535894,535880,535881,535882,535881,535883,535884,535882,535881,535884,535885,535882,535881,535884,535883,535940,535941,535942,535942,535926,535943,535943,535840,535841,535841,535842,535843,535844,535841,535842,535843,535841,535842,535841,535843,535841,535844,535845,535845,535841,535842,535842,535843,535844,535841,535843,535843,535847,535842,535841,535842,535841,535842,535860,535861,535861,535862,535863,535862,535861,535862,535861,535861,535862,535810,535811,535812,535813,535814,535815,535816,535812,535817,535814,535815,535817,535705,535817,535815,535830,535831,535832,535834,535833,535835,535836,535837,535838,535870,535871,535872,535873,535871,535872,535873,535872,535872,535871,535873,535873,535871,535920,535921,535922,535923,535922,535924,535925,530145,535924,535921,535925,535800,535801,535802,535803,535804,535803,535805,535806,535802,535807,535804,535807,535850,535851,535852,535852,535851,535851,535852,535851,535930,535931,535932,535933,535934,535935,535935,535936,535934,535931,535932,535936,535936,535910,535912,535912,535913,535914,535917,535919,535915,535913,535914,535912,535912,535913,535912,535912,535911,535913,535917,535914,535918,535916,535705,413853) 
                    ) 
                )
                    continue;
            }

            // beginning cafe24
            if( $shop_id == 10072 and (_DOMAIN_ == 'beginning' || _DOMAIN_ == 'dm'  || _DOMAIN_ == 'jucifaci' ) )
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and code2='$order[code2]' and orders_code<>$orders_code";
            // caraz 11번가
            else if( $shop_id == 10050 and _DOMAIN_ == 'caraz' )
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and order_id_seq='$order[order_id_seq]' and orders_code<>$orders_code";
            // namsun 전 판매처 : 주문번호, 상품명, 옵션
            else if( _DOMAIN_ == 'namsun' )
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and product_name='$order[product_name]' and options='$order[options]' and orders_code<>$orders_code";
            else
                //****************************************************************************
                // jbstar 샵링커 자동 판매처 분리 때문에 shop_id를 $_shop_id로 변경
                //****************************************************************************
                $query = "select * from orders where shop_id=$_shop_id and order_id='$order[order_id]' and orders_code<>$orders_code";
            $result = mysql_query( $query, $connect );
            if( mysql_num_rows( $result ) > 0 )
            {
                while( $data = mysql_fetch_assoc($result) )
                {
                    // 배송주문 auto_trans를 0으로
                    if( $data[status] == 8 && $data[auto_trans] <> 0 )
                        mysql_query("update orders set auto_trans=0 where seq=$data[seq]", $connect);
                }
                continue;
            }
            
            // cafe24 같은 파일 내에 동일 배송코드 있으면 발주안함
            if( $shop_id == 10072 )
            {
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and code2='$order[code2]' and orders_code=$orders_code";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) > 0 )
                    continue;
            }

            $slen = strlen( $order[recv_mobile] );
            $short_mobile = substr( $order[recv_mobile], $slen-4, 4 );
            
            // 금액의 , 제거
            $order[amount] = str_replace(',','',$order[amount]);
            $order[price] = str_replace(',','',$order[price]);
            $order[supply_price] = str_replace(',','',$order[supply_price]);
            $order[trans_price] = str_replace(',','',$order[trans_price]);
            $order[prepay_price] = str_replace(',','',$order[prepay_price]);
            
            // 정산코드
            $order[code11] = preg_replace('/[^0-9\.\-]/','',$order[code11]);
            $order[code12] = preg_replace('/[^0-9\.\-]/','',$order[code12]);
            $order[code13] = preg_replace('/[^0-9\.\-]/','',$order[code13]);
            $order[code14] = preg_replace('/[^0-9\.\-]/','',$order[code14]);
            $order[code15] = preg_replace('/[^0-9\.\-]/','',$order[code15]);
            $order[code16] = preg_replace('/[^0-9\.\-]/','',$order[code16]);
            $order[code17] = preg_replace('/[^0-9\.\-]/','',$order[code17]);
            $order[code18] = preg_replace('/[^0-9\.\-]/','',$order[code18]);
            $order[code19] = preg_replace('/[^0-9\.\-]/','',$order[code19]);
            $order[code20] = preg_replace('/[^0-9\.\-]/','',$order[code20]);
            $order[code31] = preg_replace('/[^0-9\.\-]/','',$order[code31]);
            $order[code32] = preg_replace('/[^0-9\.\-]/','',$order[code32]);
            $order[code33] = preg_replace('/[^0-9\.\-]/','',$order[code33]);
            $order[code34] = preg_replace('/[^0-9\.\-]/','',$order[code34]);
            $order[code35] = preg_replace('/[^0-9\.\-]/','',$order[code35]);
            $order[code36] = preg_replace('/[^0-9\.\-]/','',$order[code36]);
            $order[code37] = preg_replace('/[^0-9\.\-]/','',$order[code37]);
            $order[code38] = preg_replace('/[^0-9\.\-]/','',$order[code38]);
            $order[code39] = preg_replace('/[^0-9\.\-]/','',$order[code39]);
            $order[code40] = preg_replace('/[^0-9\.\-]/','',$order[code40]);

            // org info
            $org_product_name = $order[product_name];
            $org_options      = $order[options];
            $org_memo         = $order[memo];

            // 11번가 옵션 뒤에 수량, 가격 제거
            if( substr($shop_id,-2) == 50 && _DOMAIN_ != 'soramam' && _DOMAIN_ != 'namsun' && _DOMAIN_ != 'snowbuck' && _DOMAIN_ != 'ezadmin' && _DOMAIN_ != 'ecoskin' )
            {
                if( _DOMAIN_ == 'clubmobile' )
                    $options = $order[options];
                else if( _DOMAIN_ == 'ggee2' || _DOMAIN_ == 'jpole2' )
                    $options = preg_replace( '/-[0-9]+개$/', '', $order[options] );
                else
                {
                    $options = preg_replace( '/-[0-9]+개$/', '', $order[options] );
                    $options = preg_replace( '/-[0-9]+개 \([\+-][0-9]+원\)$/', '', $options );
                }
                
                // 발주 매칭 조건이, 상품코드 사용 안함이고, 옵션이 공란이고
                // 상품명이 "┗(추가상품)"으로 시작하면, 
                // 또는 tobbyous 이면
                // 상품명을 옵션에 복사
                if( preg_match('/^(┗\(추가상품\))(.*)/', $order[product_name], $matches) )
                {
                    if( ($_SESSION[MATCH_OPTION] == 1 && $options == "" && _DOMAIN_ != 'snowbuck') || _DOMAIN_ == 'tobbyous' )
                    {
                        $order[product_name] = $matches[1];
                        $options = $matches[2];
                    }
                }
                
                // 11번가 mediheim. code6 수수료 % 제거
                if( _DOMAIN_ == 'mediheim' || _DOMAIN_ == 'ezadmin' )
                {
                    $order[code18] = preg_replace( '/(\(.+\))$/', '', $order[code6] );
                    $order[code18] = str_replace( ',', '', $order[code18] );
                }
            }
            // 메이크샵 사은품
            else if( $shop_id % 100 == 68 && $order[shop_product_id]=="99999990GIFT" )
            {
                $options = $order[product_name] . " " . $order[options];
            }
            else
                $options = $order[options];
                
            // 위메프 수령자명 주문자명 대체
            if( $shop_id % 100 == 20 )
            {
                if( $order[recv_name] == '' )
                {
                    $order[recv_name]   = $order[order_name];
                    $order[recv_tel]    = $order[order_tel];
                    $order[recv_mobile] = $order[order_mobile];
                }
            }
                
            // 한셈몰. 주소에 우편번호 들어옴
            if( preg_match('/^\[([0-9]{3}\-?[0-9]{3})\]\s?(.*)/', $order[recv_address], $matches) )
            {
                $order[recv_zip] = $matches[1];
                $order[recv_address] = $matches[2];
            }
            
            // 티몬 주소에 우편번호 들어옴
            if( $shop_id % 100 == 41 && preg_match('/^\(?([0-9]{3}\-[0-9]{3})\)? (.*)/', $order[recv_address], $matches) )
            {
                $order[recv_zip] = $matches[1];
                $order[recv_address] = $matches[2];
            }

            // 주문일 - 공백 앞부분
            $order_date_arr = explode(" ", $order[order_date]);
            $order_date = $order_date_arr[0];

            // 주문시간
            if( $shop_id % 100 == 68 )
            {
                // 메이크샵
                $order_time = substr($order[order_time],8,2) . ":" . substr($order[order_time],10,2) . ":" . substr($order[order_time],12,2);
            }
            else if( preg_match('/([0-9]+):([0-9]+):([0-9]+)/', $order[order_time], $matches) )
            {
                if( strpos($order[order_time], "오후") !== false || strpos($order[order_time], "PM") !== false )
                    if( $matches[1] < 12 )  $matches[1] += 12;

                $order_time = $matches[1] . ":" . $matches[2] . ":" . $matches[3];
            }
            else
                $order_time = $order[order_time];
            
            // 네이버체크아웃 주소 앞에 우편번호 제거
            if( $shop_id % 100 == 5 )
                $recv_address = preg_replace('/^\([0-9]{3}\-[0-9]{3}\) /', '', $order[recv_address] );
            else
                $recv_address = $order[recv_address];
                
            // 수령자명 없으면 "수령자명없음"
            if( !$order[recv_name] )
                $order[recv_name] = "수령자명없음";
                
            // qmart2 도서지역
            if( _DOMAIN_ == 'qmart2' || _DOMAIN_ == 'onseason' || _DOMAIN_ == 'wifky' || _DOMAIN_ == 'ezadmin' )
            {
                $island = 0;
                
                // 인천 옹진 도서지역 2009.2.6
                if (substr($order[recv_zip],0,3) == "409" && (substr($order[recv_zip],-3) != "870" && substr($order[recv_zip],-3) != "871" && substr($order[recv_zip],-3) != "872" ) )  
                    $island = 1;
        
                $island_list = array("417-920", "417-923", "417-910", "417-913", "417-930","417-933", "409-850", "409-583", "409-890", "409-893", "409-910", "409-913", "409-830", "409-833", "409-840", "409-482", "409-880", "409-883", "355-842", "355-845", "355-846", "355-847", "355-848", "355-820", "355-823", "573-810", "573-819", "579-910", "579-915", "650-941", "650-945", "650-930", "650-934", "650-910", "650-916", "650-833", "650-835", "650-920", "650-927", "799-800", "799-805", "799-820", "799-823", "799-810", "799-813", "513-890", "513-893", "535-910", "535-919", "535-890", "535-898", "535-880", "535-885", "535-840", "535-847", "535-850", "535-852", "535-870", "535-873", "530-847", "535-810", "535-817", "535-860", "535-863", "535-930", "535-936", "535-926", "535-940", "535-943", "530-145", "535-920", "535-925", "535-830", "535-838", "535-820", "535-824", "535-804", "535-806", "537-860", "537-864", "537-926", "539-810", "539-814", "537-880", "537-883", "537-809", "537-900", "537-907", "537-913","537-920", "537-925", "537-910", "537-914", "537-820", "537-826", "537-830", "537-836", "536-928", "537-840", "537-849", "536-929", "536-935", "537-814", "537-817", "539-910", "539-919", "548-904", "548-909", "548-930", "548-936", "556-840", "556-849", "556-850", "556-856", "556-830", "556-839", "555-300", "556-897", "550-280");        
                if (in_array($order[recv_zip], $island_list))         // 도서지역
                    $island = 1;
                
                // 울릉도
                $island_list2 = array("799-800","799-801","799-802","799-804","799-803","799-805","799-820","799-821","799-822","799-823","799-810","799-811","799-812","799-813");
                if (in_array($order[recv_zip], $island_list2))
                    $island = 1;
                
                if (substr($order[recv_zip],0,2) == "69")             // 제주 도서지역 
                    $island = 1;
                
                if (substr($order[recv_zip],0,3) == "534")            // 전남 무안 도서지역 제외
                    $island = 0;
                    
                if( $island )
                    $recv_address = "★도서지역★" . $recv_address;
            }
            
            // sshin2 제주
            if( _DOMAIN_ == 'sshin2' )
            {
                if (substr($order[recv_zip],0,2) == "69")             // 제주 도서지역 
                    $recv_address = "★" . $recv_address;
            }

            // 옥션, 지마켓 옵션이 매우 긴 경우
            if( $shop_id % 100 == 1 || $shop_id % 100 == 2 )
            {
                if( mb_strlen( $options, "utf8" ) > 512 )
                {
                    $query_lo = "insert long_options set options='$options', crdate=now()";
                    mysql_query($query_lo, $connect);
                    
                    $query_lo = "select * from long_options order by seq desc limit 1";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = "*Long Options(" . $data_lo[seq] . ")* " . $options;
                }
            }

            // 위메프 판매처의 경우 상품명을 여러주문으로 쪼갠다. 상품명, 옵션 동일하게 적용
            //
            // *** 2012-06-28 my4244 위메프 발주서 형식 변경됨 ***
            //
            if( _DOMAIN_ != 'milkids' && 
                _DOMAIN_ != 'namsun' && 
                ( $shop_id % 100 == 20 || 
                  ((_DOMAIN_ == '_my4244' || _DOMAIN_ == 'imig2') && $shop_id == 10080) ||
                  ( _DOMAIN_ == 'changsin' && $shop_id == 10186 ) 
                ) 
            )
            {
                $new_opt = false;
                if( $shop_id % 100 == 20 )
                {
                    // "[" 로 시작하면
                    if( substr($order[product_name], 0, 1) == "[" )
                        $options_arr = explode("],[", $order[product_name]);
                    else
                    {
                        $options_arr = preg_split("/(?<=[0-9]개),/", $order[product_name]);
                        $new_opt = true;
                    }
                }
                else if( _DOMAIN_ == 'imig2' && $shop_id == 10080 )
                    $options_arr = explode("]/[", $order[product_name]);
                else if( (_DOMAIN_ == 'my4244' && $shop_id == 10080) ||
                         (_DOMAIN_ == 'changsin' && $shop_id == 10186)
                       )
                    $options_arr = explode("],", $order[product_name]);
                
                $options_cnt = count($options_arr);
                for( $i=0; $i<$options_cnt; $i++ )
                {
                    if( $new_opt )
                    {
                        // 수량 구하기
                        preg_match('/([0-9]+)개$/i', $options_arr[$i], $matches);
                        $new_qty = $matches[1];
                        
                        // 옵션에서 '1개' 제거하기
                        $new_options = preg_replace('/:[0-9]+개$/', '', $options_arr[$i] );
                    }
                    
                    else if( (_DOMAIN_ == 'my4244' && $shop_id == 10080) || (_DOMAIN_ == 'changsin' && $shop_id == 10186) )
                    {
                        if( $options_cnt != 1 )
                        {
                            // 마지막 옵션 빼고 뒤에 "]" 추가
                            if( $i != $options_cnt -1 )
                                $options_arr[$i] = $options_arr[$i] . "]";
                        }
                        
                        // 수량 구하기
                        preg_match('/\[([0-9]+)개\]$/i', $options_arr[$i], $matches);
                        $new_qty = $matches[1];
                        
                        // 옵션에서 '1개' 제거하기
                        $new_options = preg_replace('/\[[0-9]+개\]$/', '', $options_arr[$i] );
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
                    
                    $query = "insert orders
                                 set order_id        = '$order[order_id]',
                                     order_id_seq    = '$order[order_id_seq]',
                                     order_type      = '$order[order_type]',
                                     order_type2     = '$order[order_type2]',
                                     shop_id         = '$shop_id',
                                     shop_product_id = '$new_options',
                                     product_name    = '$new_options',
                                     options         = '$new_options',
                                     qty             = '$new_qty',
                                     amount          = '$order[amount]',
                                     supply_price    = '$order[supply_price]',
                                     prepay_price    = '$order[prepay_price]',
                                     trans_who       = '$order[trans_who_ez]',
                                     trans_price     = '$order[trans_price]',
                                     org_trans_who   = '$order[org_trans_who]',
                                     order_date      = '$order_date',
                                     order_time      = '$order_time',
                                     pay_date        = '',
                                     collect_date    = '$collect_date',
                                     order_name      = '$order[order_name]',
                                     order_tel       = '$order[order_tel]',
                                     order_mobile    = '$order[order_mobile]',
                                     order_email     = '$order[order_email]',
                                     order_zip       = '$order[order_zip]',
                                     order_address   = '$order[order_address]',
                                     recv_name       = '$order[recv_name]',
                                     recv_tel        = '$order[recv_tel]',
                                     recv_mobile     = '$order[recv_mobile]',
                                     recv_email      = '$order[recv_email]',
                                     recv_zip        = '$order[recv_zip]',
                                     recv_address    = '$recv_address',
                                     cust_id         = '$order[cust_id]',
                                     memo            = '$order[memo]',
                                     message         = '$order[message]',
                                     pay_type        = '$order[pay_type]',
                                     match_code      = '$order[match_code]',
                                     code1           = '$order[code1]',
                                     code2           = '$order[code2]',
                                     code3           = '$order[code3]',
                                     code4           = '$order[code4]',
                                     code5           = '$order[code5]',
                                     code6           = '$order[code6]',
                                     code7           = '$order[code7]',
                                     code8           = '$order[code8]',
                                     code9           = '$order[code9]',
                                     code10          = '$order[code10]',  
                                     collect_time    = now(),
                                     short_mobile    = '$short_mobile',
                                     status          = 0,
                                     order_status    = 10,
                                     orders_code     = $orders_code,
                                     code11          = '$order[code11]',
                                     code12          = '$order[code12]',
                                     code13          = '$order[code13]',
                                     code14          = '$order[code14]',
                                     code15          = '$order[code15]',
                                     code16          = '$order[code16]',
                                     code17          = '$order[code17]',
                                     code18          = '$order[code18]',
                                     code19          = '$order[code19]',
                                     code20          = '$order[code20]',
                                     code21          = '$order[code21]',
                                     code22          = '$order[code22]',
                                     code23          = '$order[code23]',
                                     code24          = '$order[code24]',
                                     code25          = '$order[code25]',
                                     code26          = '$order[code26]',
                                     code27          = '$order[code27]',
                                     code28          = '$order[code28]',
                                     code29          = '$order[code29]',
                                     code30          = '$order[code30]',  
                                     code31          = '$order[code31]',
                                     code32          = '$order[code32]',
                                     code33          = '$order[code33]',
                                     code34          = '$order[code34]',
                                     code35          = '$order[code35]',
                                     code36          = '$order[code36]',
                                     code37          = '$order[code37]',
                                     code38          = '$order[code38]',
                                     code39          = '$order[code39]',
                                     code40          = '$order[code40]',
                                     org_product_name= '$org_product_name',
                                     org_options     = '$org_options',
                                     org_memo        = '$org_memo'
                                     "; 
debug( "발주 insert : " . $order[order_id] . " / " . $shop_id . " / " . $order[shop_product_id] . " / " . $options . " / " . $order[qty] . " / " . $order[recv_name]);
                    mysql_query( $query, $connect );
                }
            }
            else
            {
                $query = "insert orders
                             set order_id        = '$order[order_id]',
                                 order_id_seq    = '$order[order_id_seq]',
                                 order_type      = '$order[order_type]',
                                 order_type2     = '$order[order_type2]',
                                 shop_id         = '$_shop_id',
                                 shop_product_id = '$order[shop_product_id]',
                                 product_name    = '$order[product_name]',
                                 options         = '$options',
                                 qty             = '$order[qty]',
                                 amount          = '$order[amount]',
                                 supply_price    = '$order[supply_price]',
                                 prepay_price    = '$order[prepay_price]',
                                 trans_who       = '$order[trans_who_ez]',
                                 trans_price     = '$order[trans_price]',
                                 org_trans_who   = '$order[org_trans_who]',
                                 order_date      = '$order_date',
                                 order_time      = '$order_time',
                                 pay_date        = '',
                                 collect_date    = '$collect_date',
                                 order_name      = '$order[order_name]',
                                 order_tel       = '$order[order_tel]',
                                 order_mobile    = '$order[order_mobile]',
                                 order_email     = '$order[order_email]',
                                 order_zip       = '$order[order_zip]',
                                 order_address   = '$order[order_address]',
                                 recv_name       = '$order[recv_name]',
                                 recv_tel        = '$order[recv_tel]',
                                 recv_mobile     = '$order[recv_mobile]',
                                 recv_email      = '$order[recv_email]',
                                 recv_zip        = '$order[recv_zip]',
                                 recv_address    = '$recv_address',
                                 cust_id         = '$order[cust_id]',
                                 memo            = '$order[memo]',
                                 message         = '$order[message]',
                                 pay_type        = '$order[pay_type]',
                                 match_code      = '$order[match_code]',
                                 code1           = '$order[code1]',
                                 code2           = '$order[code2]',
                                 code3           = '$order[code3]',
                                 code4           = '$order[code4]',
                                 code5           = '$order[code5]',
                                 code6           = '$order[code6]',
                                 code7           = '$order[code7]',
                                 code8           = '$order[code8]',
                                 code9           = '$order[code9]',
                                 code10          = '$order[code10]',  
                                 collect_time    = now(),
                                 short_mobile    = '$short_mobile',
                                 status          = 0,
                                 order_status    = 10,
                                 orders_code     = $orders_code,
                                 code11          = '$order[code11]',
                                 code12          = '$order[code12]',
                                 code13          = '$order[code13]',
                                 code14          = '$order[code14]',
                                 code15          = '$order[code15]',
                                 code16          = '$order[code16]',
                                 code17          = '$order[code17]',
                                 code18          = '$order[code18]',
                                 code19          = '$order[code19]',
                                 code20          = '$order[code20]',
                                 code21          = '$order[code21]',
                                 code22          = '$order[code22]',
                                 code23          = '$order[code23]',
                                 code24          = '$order[code24]',
                                 code25          = '$order[code25]',
                                 code26          = '$order[code26]',
                                 code27          = '$order[code27]',
                                 code28          = '$order[code28]',
                                 code29          = '$order[code29]',
                                 code30          = '$order[code30]',  
                                 code31          = '$order[code31]',
                                 code32          = '$order[code32]',
                                 code33          = '$order[code33]',
                                 code34          = '$order[code34]',
                                 code35          = '$order[code35]',
                                 code36          = '$order[code36]',
                                 code37          = '$order[code37]',
                                 code38          = '$order[code38]',
                                 code39          = '$order[code39]',
                                 code40          = '$order[code40]',
                                 org_product_name= '$org_product_name',
                                 org_options     = '$org_options',
                                 org_memo        = '$org_memo'
                                 "; 
debug("발주 전체 쿼리 : " . $query);
debug( "발주 insert : " . $order[order_id] . " / " . $shop_id . " / " . $order[shop_product_id] . " / " . $options . " / " . $order[qty] . " / " . $order[recv_name]);
                if( !mysql_query( $query, $connect ) )
                {
                    echo mysql_error();
                    $msg = "발주 중에 오류가 발생했습니다.<br><br>발주 데이터를 확인하십시오.";
                    return 5;
                }
            }
            $cnt_new++;
        }

        // 발주 작업 로그
        $query = "update order_process_log set cnt_new = $cnt_new, balju_ok=1 where seq = $orders_code";
        mysql_query( $query, $connect );

        return 0;
    }
    
    // 지마켓 옵션가 합계 구하기
    function gmarket_option_price( $options )
    {
        preg_match_all("/\(([0-9]+)원\)/", $options, $price_arr);
        $sum = 0;
        foreach( $price_arr[1] as $price )
            $sum += $price;
        return $sum;
    }
        
}
?>
