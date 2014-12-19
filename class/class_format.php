<?
require_once "class_shop.php";

class class_format extends class_top
{
    function make_orders($dataArr, $shop_id, &$cnt_total, &$cnt_new, &$msg, $is_auto=0, $old_fn, $new_fn) 
    {
        global $sys_connect, $connect, $collect_date, $is_auto;

        // 청주시 구 우편번호
        $chungju_old_zip = array('363851','360170','360171','360172','360200','360210','360215','360220','360225','360226','360227','360560','360565','360566','360567','360568','360701','360702','360703','360707','360716','360764','360767','360768','360774','360775','360776','360781','360782','360784','360804','360805','360806','360807','360808','360813','360814','360815','360816','360817','360818','360819','360820','360821','360822','361003','361100','361101','361102','361140','361150','361151','361152','361160','361201','361202','361205','361206','361230','361240','361250','361280','361701','361703','361704','361705','361706','361708','361709','361710','361711','361712','361713','361716','361717','361718','361722','361724','361727','361742','361744','361746','361747','361748','361749','361750','361751','361752','361753','361754','361761','361762','361763','361764','361765','361766','361767','361768','361769','361770','361771','361772','361774','361775','361786','361787','361788','361789','361790','361791','361792','361793','361794','361804','361805','361806','361807','361808','361809','361821','361822','361823','361826','361827','361828','361829','361831','361832','361833','361834','361835','361836','361837','361838','361845','361850','361851','361852','361853','361854','361859','361860','361861','361862','361863','361864','361865','361866','361867','361875','363700','363701','363702','363703','363704','363705','363706','363707','363708','363789','363791','363792','363810','363811','363812','363813','363814','363820','363821','363822','363823','363830','363831','363832','363833','363834','363840','363841','363842','363843','363844','363849','363850','363851','363852','363853','363860','363861','363862','363870','363871','363872','363873','363874','363890','363891','363892','363893','363894','363910','363911','363912','363913','363914','363950','363951','363952','363953','363954','363955','363-851','360-170','360-171','360-172','360-200','360-210','360-215','360-220','360-225','360-226','360-227','360-560','360-565','360-566','360-567','360-568','360-701','360-702','360-703','360-707','360-716','360-764','360-767','360-768','360-774','360-775','360-776','360-781','360-782','360-784','360-804','360-805','360-806','360-807','360-808','360-813','360-814','360-815','360-816','360-817','360-818','360-819','360-820','360-821','360-822','361-003','361-100','361-101','361-102','361-140','361-150','361-151','361-152','361-160','361-201','361-202','361-205','361-206','361-230','361-240','361-250','361-280','361-701','361-703','361-704','361-705','361-706','361-708','361-709','361-710','361-711','361-712','361-713','361-716','361-717','361-718','361-722','361-724','361-727','361-742','361-744','361-746','361-747','361-748','361-749','361-750','361-751','361-752','361-753','361-754','361-761','361-762','361-763','361-764','361-765','361-766','361-767','361-768','361-769','361-770','361-771','361-772','361-774','361-775','361-786','361-787','361-788','361-789','361-790','361-791','361-792','361-793','361-794','361-804','361-805','361-806','361-807','361-808','361-809','361-821','361-822','361-823','361-826','361-827','361-828','361-829','361-831','361-832','361-833','361-834','361-835','361-836','361-837','361-838','361-845','361-850','361-851','361-852','361-853','361-854','361-859','361-860','361-861','361-862','361-863','361-864','361-865','361-866','361-867','361-875','363-700','363-701','363-702','363-703','363-704','363-705','363-706','363-707','363-708','363-789','363-791','363-792','363-810','363-811','363-812','363-813','363-814','363-820','363-821','363-822','363-823','363-830','363-831','363-832','363-833','363-834','363-840','363-841','363-842','363-843','363-844','363-849','363-850','363-851','363-852','363-853','363-860','363-861','363-862','363-870','363-871','363-872','363-873','363-874','363-890','363-891','363-892','363-893','363-894','363-910','363-911','363-912','363-913','363-914','363-950','363-951','363-952','363-953','363-954','363-955');
        // 청주시 신 우편번호
        $chungju_new_zip = array('360851','363170','363171','363172','363200','363210','363215','363220','363225','363226','363227','363560','363565','363566','363567','363568','363701','363702','363703','363707','363716','363764','363767','363768','363774','363775','363776','363708','363709','363710','363804','363805','363806','363807','363808','363813','363814','363815','363816','363817','363818','363819','363820','363821','363822','362003','362100','362101','362102','362140','362150','362151','362152','362160','362201','362202','362205','362206','362230','362240','362250','362280','362701','362703','362704','362705','362706','362708','362709','362710','362711','362712','362713','362716','362717','362718','362722','362724','362727','362742','362744','362746','362747','362748','362749','362750','362751','362752','362753','362754','362761','362762','362763','362764','362765','362766','362767','362768','362769','362770','362771','362772','362774','362775','362786','362787','362788','362789','362790','362791','362792','362793','362794','362804','362805','362806','362807','362808','362809','362855','362856','362857','362826','362827','362828','362829','362831','362832','362833','362834','362835','362836','362837','362838','362845','362850','362851','362852','362853','362854','362859','362860','362861','362862','362863','362864','362865','362866','362867','362875','361709','361701','361710','361703','361704','361705','361706','362707','361708','360792','361791','361792','362810','362811','362812','362813','362814','362820','362821','362822','362823','360830','360831','360832','360833','360834','360840','360841','360842','360843','360844','360849','360850','360851','360852','360853','360860','360861','360862','360870','360871','360872','360873','360874','361890','361891','361892','361893','361894','361910','361911','361912','361913','361914','361950','361951','361952','361953','361954','361955','360-851','363-170','363-171','363-172','363-200','363-210','363-215','363-220','363-225','363-226','363-227','363-560','363-565','363-566','363-567','363-568','363-701','363-702','363-703','363-707','363-716','363-764','363-767','363-768','363-774','363-775','363-776','363-708','363-709','363-710','363-804','363-805','363-806','363-807','363-808','363-813','363-814','363-815','363-816','363-817','363-818','363-819','363-820','363-821','363-822','362-003','362-100','362-101','362-102','362-140','362-150','362-151','362-152','362-160','362-201','362-202','362-205','362-206','362-230','362-240','362-250','362-280','362-701','362-703','362-704','362-705','362-706','362-708','362-709','362-710','362-711','362-712','362-713','362-716','362-717','362-718','362-722','362-724','362-727','362-742','362-744','362-746','362-747','362-748','362-749','362-750','362-751','362-752','362-753','362-754','362-761','362-762','362-763','362-764','362-765','362-766','362-767','362-768','362-769','362-770','362-771','362-772','362-774','362-775','362-786','362-787','362-788','362-789','362-790','362-791','362-792','362-793','362-794','362-804','362-805','362-806','362-807','362-808','362-809','362-855','362-856','362-857','362-826','362-827','362-828','362-829','362-831','362-832','362-833','362-834','362-835','362-836','362-837','362-838','362-845','362-850','362-851','362-852','362-853','362-854','362-859','362-860','362-861','362-862','362-863','362-864','362-865','362-866','362-867','362-875','361-709','361-701','361-710','361-703','361-704','361-705','361-706','362-707','361-708','360-792','361-791','361-792','362-810','362-811','362-812','362-813','362-814','362-820','362-821','362-822','362-823','360-830','360-831','360-832','360-833','360-834','360-840','360-841','360-842','360-843','360-844','360-849','360-850','360-851','360-852','360-853','360-860','360-861','360-862','360-870','360-871','360-872','360-873','360-874','361-890','361-891','361-892','361-893','361-894','361-910','361-911','361-912','361-913','361-914','361-950','361-951','361-952','361-953','361-954','361-955');

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
            // pimz_ 헤더는 공백제거 안함
            if( substr($data['shop_header'],0,5) == 'pimz_' )
                $headerArr["header"]["$data[field_id]"] = $data['shop_header'];
            else
                $headerArr["header"]["$data[field_id]"] = str_replace( " ", "", $data['shop_header'] );

            $headerArr["abs"   ]["$data[field_id]"] = "$data[abs]";
        }

        $order_id_header = str_replace("[병합]","",$headerArr["header"]["order_id"]);
        
        // "주문번호" 를 기준으로 헤더 열을 찾느다.
        $find=false;
        $header_pos = 0;
        for( $i=0; $i<count($dataArr); $i++ )
        {
            for( $j=0; $j<count($dataArr[$i]); $j++ )
            {
                $order_id_arr = explode("^", $order_id_header);
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
            $msg = "발주서에서 [" . $order_id_header . "] 필드를 찾을수 없습니다1.<br><br>발주서 포멧을 확인하세요.";
            return 6;
        }

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
                    // 셀병합 헤더
                    $m_header = false;
                    if( strpos($header,"[병합]") !== false )
                    {
                        $header = str_replace("[병합]","",$header);
                        $m_header = true;
                    }

                    foreach( $dataArr[0] as $pos => $hStr )
                    {
                        // pid는 필수선택을 주문옵션으로
                        if( _DOMAIN_ == 'pid' && $hStr == '필수선택' )
                            $hStr = '주문옵션';

                        // 헤더 찾음
                        if( $header == str_replace( array(" ","\n","\r"), "", $hStr ) )
                        {
                            if( isset($hPosArr["$field"]) )
                                $hPosArr["$field"] .= ",".$pos;
                            else
                                $hPosArr["$field"] = $pos;
                            
                            // 셀병합 헤더    
                            if( $m_header )  $hPosArr["$field"] .= "M";
                            
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
                        $msg = "발주서에서 [" . $header_err . "] 필드를 찾을수 없습니다2.<br><br>발주서 포멧을 확인하세요.";
                        return 1;
                    }else  // 옵션 필드이면 -1 저장
                        $hPosArr["$field"] = -1;
                }
            }
            else
            {
                // 동일 헤더 두번째 찾기
                $first_header = true;
        
                // 복수헤더 검사
                $headers = explode( "|", $headerOrg );
                foreach( $headers as $header )
                {
                    // 셀병합 헤더
                    $m_header = false;
                    if( strpos($header,"[병합]") !== false )
                    {
                        $header = str_replace("[병합]","",$header);
                        $m_header = true;
                    }

                    $find_ok = false;
                    foreach( $dataArr[0] as $pos => $hStr )
                    {
                        // 시스템 데이터
                        if( substr($header,0,4)=='pimz' )
                        {
                            $hPosArr["$field"] = $header;
                            continue 2;
                        }
                        
                        // pid는 필수선택을 주문옵션으로
                        if( _DOMAIN_ == 'pid' && $hStr == '필수선택' )
                            $hStr = '주문옵션';

                        // [택일]
                        $header = str_replace('[택일]','',$header);
                        
                        // 헤더 찾음
                        if( $header == str_replace( array(" ","\n","\r"), "", $hStr ) )
                        {
                            // soulmi 10085 판매처 '우편번호' 필드가 2개임. 두번째 '우편번호' 필드가 수령자 우편번호임.
                            // 카카오스타일은 "번호" 필드가 2개. 첫번째 번호는 code1, 두번째 번호는 shop_product_id
                            // 카카오스타일은 "이름" 필드가 2개. 첫번째 이름은 구매자, 두번째 이름은 수령자
                            if( (_DOMAIN_ == 'soulmi' && $shop_id == 10085 && $field == 'recv_zip') ||
                                ($shop_id % 100 == 56 && $field == 'shop_product_id') || 
                                ($shop_id % 100 == 56 && $field == 'recv_name') )
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

                            // 셀병합 헤더    
                            if( $m_header )  $hPosArr["$field"] .= "M";
                            
                            $find_ok = true;
                            break;
                        }
                    }
                    if( !$find_ok ) // 찾기 실패
                    {
                        if( $headerArr["abs"]["$field"] )  // 필수 필드이면 에러
                        {
                            $header_err = $header;
                            $msg = "발주서에서 [" . $header_err . "] 필드를 찾을수 없습니다3.<br><br>발주서 포멧을 확인하세요.";
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
                    // 상품코드 : 무조건 code
                    else if( substr($posOrg,5,4) == 'code' )
                    {
                        $orderArr[$k][$field] = "code";
                    }
                    // 숫자
                    else if( substr($posOrg,5,6) == 'number' )
                    {
                        $orderArr[$k][$field] = substr($posOrg,12);
                    }
                    // 텍스트
                    else if( substr($posOrg,5,4) == 'text' )
                    {
                        $orderArr[$k][$field] = substr($posOrg,10);
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
                            // [택일]
                            if( strpos($eachTitle[$j],"[택일]") !== false )
                            {
                                if( !$orderArr[$k][$field] ) 
                                    $orderArr[$k][$field] = str_replace( array("\\",'"',"'","??"), ' ', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            }
                            // 주소, 메모 필드는 헤더를 추가하지 않는다.
                            else if( $field == 'recv_address' )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'","??"), ' ', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            else if( $field == 'options' && _DOMAIN_ == 'pinkage' && $shop_id == 10068 )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'","??"), ' ', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            else if( $field == 'recv_name' || $field == 'order_name' )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'"), ' ', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            else if( $field == 'memo' )
                                $orderArr[$k][$field] .= str_replace( array("\\",'"',"'"), '', trim($dataArr[$i][$posArr[$j]]) ) . " ";
                            // 우편번호, 전화번호는 "-" 를 추가
                            else if( $field == 'recv_zip' || $field == 'recv_tel' || $field == 'recv_mobile' || $field == 'order_tel' || $field == 'order_mobile' )
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
                    // 병합
                    if( substr($posArr[0],-1,1) == 'M' )
                    {
                        $posArr[0] = str_replace("M","",$posArr[0]);
                        $raw_val = trim( $dataArr[$i][$posArr[0]] );

                        if( $k >= 1 && $raw_val == '' )
                            $raw_val = $orderArr[$k-1][$field];
                    }
                    else
                        $raw_val = trim( $dataArr[$i][$posArr[0]] );

                    if( $field == 'recv_address' )
                        $orderArr[$k][$field] = str_replace( array("\\",'"',"'","??"), ' ', $raw_val );
                    else
                        $orderArr[$k][$field] = str_replace( array("\\",'"',"'"), '', $raw_val );
                }
            }
            
            // 주문구분
            $order_type = $orderArr[$k]["order_type"];
            $order_type2 = $orderArr[$k]["order_type2"];
            $shop_code = $shop_id % 100;

            //*******************************
            // 2013-11-11 
            // hmall 주분번호 변경 전환
            //*******************************
            if( $shop_code == 43 && $orderArr[$k]["order_id_seq"] > 1000000 )
                $orderArr[$k]["order_id"] = substr($orderArr[$k]["order_id"], 0, 8) . " " . substr($orderArr[$k]["order_id"], 8) . " 00" . substr($orderArr[$k]["order_id_seq"], 0, 1) . " " . substr($orderArr[$k]["order_id_seq"], 1, 3) . " " . substr($orderArr[$k]["order_id_seq"], 4, 3);
            //*******************************
            // 2013-11-11 
            // 하프클럽 주문번호 수식제거
            //*******************************
            else if( $shop_code == 27 )
                $orderArr[$k]["order_id"] = str_replace(array('"','='), '', $orderArr[$k]["order_id"]);
            //*******************************
            // 2014-05-22
            // 메이크샵 주문번호, 상품코드 수식제거
            //*******************************
            else if( $shop_code == 68 )
            {
                $orderArr[$k]["order_id"] = str_replace(array('"','='), '', $orderArr[$k]["order_id"]);
                $orderArr[$k]["shop_product_id"] = str_replace(array('"','='), '', $orderArr[$k]["shop_product_id"]);
            }

            // 메이크샵 결제수단
            if( $shop_code == 68 )
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
            }

            //*****************************************************************************************
            //*****************************************************************************************

            // 주문번호 필드값이 있어야만 orderArr에 넣는다. 주문번호가 "합계" 이면 제외한다.
            if( $orderArr[$k]["order_id"] == "" || $orderArr[$k]["order_id"] == "합계" || $orderArr[$k]["order_id"] == "결제번호" )
                array_pop( $orderArr );
            // 상품코드 필드값이 있어야만 orderArr에 넣는다. 일반발주 제외
            else if( $orderArr[$k]["shop_product_id"] == "" && ($_SESSION[STOCK_MANAGE_USE] == 1 || $_SESSION[STOCK_MANAGE_USE] == 2) )
            {
                $msg = "상품코드가 없는 주문이 발견되었습니다. ($k)<br><br>발주서를 확인하세요";
                return 7;
            }
            // 2014-10-27 장경희. 임찬영요청
            // parklon 배송형태 "00" 발주안함
            else if( $shop_code == 43 && $orderArr[$k]["code3"] == "00" )
                array_pop( $orderArr );
            // 2014-11-07 장경희. 천자문요청
            // bjstolo 배송형태 "협력사직택배" 발주안함
            else if( _DOMAIN_ == 'bjstolo' && $shop_code == 43 && $orderArr[$k]["code3"] == "협력사직택배" )
                array_pop( $orderArr );
            // (X) AK몰 : order_type (기수령여부) = "N"
            // 2014-09-20 장경희. ak 변경
            // AK몰 : order_type (기수령여부) => (배송구분) = "N" => "출고"
            else if( $shop_code == 42 && $order_type != "출고" )
                array_pop( $orderArr );
            // (X) AK몰 : order_type2 (배송상태, 수령상태) = "주문"
            // 2014-09-20 장경희. ak 변경
            // AK몰 : order_type2 (배송상태, 수령상태) => (배송유형) = "주문" => "정상출고"
            else if( $shop_code == 42 && $order_type2 != "정상출고" )
                array_pop( $orderArr );
            // 신세계 : order_type (배송유형) = "정상출하" 또는 ""
            // 신세계 또는 이마트 : order_type (출고유형) = "일반출고" * 2014-01-02 변경사항
            else if( $shop_code == 15 && $order_type != "일반출고" && $order_type != "일반" && (_DOMAIN_ != "gaelimhs" || $order_type != "교환출고" ) )
            {
                array_pop( $orderArr );
                debug( "신세계 주문유형 오류 : " . $order_type );
            }
            else if( $shop_code == 16 && $order_type != "일반출고"  && (_DOMAIN_ != "gaelimhs" || $order_type != "교환출고" ) )
            {
                array_pop( $orderArr );
                debug( "이마트 주문유형 오류 : " . $order_type );
            }
            // 2014-09-23 장경희. 신세계, 이마트 지시수량=0 & 취소수량>0 이면 제외
            else if( ($shop_code == 15 || $shop_code == 16) && $orderArr[$k]["qty"] < 1 && $orderArr[$k]["code9"] > 0 )
            {
                array_pop( $orderArr );
            }
            // 2014-11-05 장경희. 쿠팡은 수량이 0 이면 건너뜀. 취소 주문임
            else if( $orderArr[$k]["qty"] == 0 && $shop_code == 53 )
            {
                array_pop( $orderArr );
            }
            // 2014-11-27 장경희. changki77 해당 주문 수량 0 으로 들어올때 건너뜀.
            else if( 
                _DOMAIN_ == 'changki77' &&  (
                    $orderArr[$k]['order_id'] == '20141117-0002386' ||
                    $orderArr[$k]['order_id'] == '20141117-0002500' ||
                    $orderArr[$k]['order_id'] == '20141117-0002516' ||
                    $orderArr[$k]['order_id'] == '20141117-0002526' ||
                    $orderArr[$k]['order_id'] == '20141117-0002555' ||
                    $orderArr[$k]['order_id'] == '20141117-0002591' ||
                    $orderArr[$k]['order_id'] == '20141117-0002666' ||
                    $orderArr[$k]['order_id'] == '20141117-0002827' ||
                    $orderArr[$k]['order_id'] == '20141117-0002233' ||
                    $orderArr[$k]['order_id'] == '20141117-0002841' ||
                    $orderArr[$k]['order_id'] == '20141117-0002833' ||
                    $orderArr[$k]['order_id'] == '20141117-0002855' ||
                    $orderArr[$k]['order_id'] == '20141117-0002791' ||
                    $orderArr[$k]['order_id'] == '20141117-0002975' ||
                    $orderArr[$k]['order_id'] == '20141117-0002958' ||
                    $orderArr[$k]['order_id'] == '20141117-0003024' ||
                    $orderArr[$k]['order_id'] == '20141117-0002699' ||
                    $orderArr[$k]['order_id'] == '20141117-0002292' ||
                    $orderArr[$k]['order_id'] == '20141117-0003068' ||
                    $orderArr[$k]['order_id'] == '20141117-0003173' ||
                    $orderArr[$k]['order_id'] == '20141117-0003188' ||
                    $orderArr[$k]['order_id'] == '20141117-0003208' ||
                    $orderArr[$k]['order_id'] == '20141117-0003213' ||
                    $orderArr[$k]['order_id'] == '20141117-0003228' ||
                    $orderArr[$k]['order_id'] == '20141117-0003290' ||
                    $orderArr[$k]['order_id'] == '20141117-0003300' ||
                    $orderArr[$k]['order_id'] == '20141117-0003319' ||
                    $orderArr[$k]['order_id'] == '20141117-0003382' ||
                    $orderArr[$k]['order_id'] == '20141117-0003402' ||
                    $orderArr[$k]['order_id'] == '20141117-0003411' ||
                    $orderArr[$k]['order_id'] == '20141117-0003505' ||
                    $orderArr[$k]['order_id'] == '20141117-0003511' ||
                    $orderArr[$k]['order_id'] == '20141117-0003486' ||
                    $orderArr[$k]['order_id'] == '20141117-0003731' ||
                    $orderArr[$k]['order_id'] == '20141117-0003745' ||
                    $orderArr[$k]['order_id'] == '20141117-0003467' ||
                    $orderArr[$k]['order_id'] == '20141117-0003659' ||
                    $orderArr[$k]['order_id'] == '20141117-0003778' ||
                    $orderArr[$k]['order_id'] == '20141117-0003925' ||
                    $orderArr[$k]['order_id'] == '20141117-0003865' ||
                    $orderArr[$k]['order_id'] == '20141117-0004011' ||
                    $orderArr[$k]['order_id'] == '20141117-0004034' ||
                    $orderArr[$k]['order_id'] == '20141117-0004074' ||
                    $orderArr[$k]['order_id'] == '20141117-0004115' ||
                    $orderArr[$k]['order_id'] == '20141118-0000124' ||
                    $orderArr[$k]['order_id'] == '20141118-0000154' ||
                    $orderArr[$k]['order_id'] == '20141118-0000200' ||
                    $orderArr[$k]['order_id'] == '20141118-0000224' ||
                    $orderArr[$k]['order_id'] == '20141118-0000237' ||
                    $orderArr[$k]['order_id'] == '20141118-0000268' ||
                    $orderArr[$k]['order_id'] == '20141118-0000622' ||
                    $orderArr[$k]['order_id'] == '20141118-0000666' ||
                    $orderArr[$k]['order_id'] == '20141117-0003003' ||
                    $orderArr[$k]['order_id'] == '20141118-0000706' ||
                    $orderArr[$k]['order_id'] == '20141118-0000751' ||
                    $orderArr[$k]['order_id'] == '20141118-0000696' ||
                    $orderArr[$k]['order_id'] == '20141118-0000767' ||
                    $orderArr[$k]['order_id'] == '20141118-0000818' ||
                    $orderArr[$k]['order_id'] == '20141118-0000809' ||
                    $orderArr[$k]['order_id'] == '20141118-0000841' ||
                    $orderArr[$k]['order_id'] == '20141118-0000191' ||
                    $orderArr[$k]['order_id'] == '20141118-0000724' ||
                    $orderArr[$k]['order_id'] == '20141118-0001011' ||
                    $orderArr[$k]['order_id'] == '20141118-0001078' ||
                    $orderArr[$k]['order_id'] == '20141118-0000749' ||
                    $orderArr[$k]['order_id'] == '20141118-0001104' ||
                    $orderArr[$k]['order_id'] == '20141118-0001058' ||
                    $orderArr[$k]['order_id'] == '20141118-0000973' ||
                    $orderArr[$k]['order_id'] == '20141117-0004008' ||
                    $orderArr[$k]['order_id'] == '20141117-0004091' ||
                    $orderArr[$k]['order_id'] == '20141117-0003841'
                )
            )
            {
                array_pop( $orderArr );
            }
            // 수량이 1 보다 작으면 에러
            else if( $orderArr[$k]["qty"] < 1 )
            {
                $msg = "수량이 1 보다 작은 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // 수동발주이고 수령자명 없으면 오류
            else if( !$is_auto && $orderArr[$k]["recv_name"] == "" )
            {
                $msg = "수령자명이 없는 주문이 발견되었습니다.($k)<br><br>발주서를 확인하세요";
                return 7;
            }
            // 11번가 오래된 주문 발주안함
            else if( $shop_code == 50 && $orderArr[$k]["order_date"] < "2014/06/01 00:00:00" )
            {
                array_pop( $orderArr );
            }
            // 티몬 수령자 전화 없으면 오류
            else if( $shop_code == 41 && $orderArr[$k]["recv_tel"] == "" )
            {
                $msg = "전화번호 없는 주문이 발견되었습니다.<br><br>발주서를 확인하세요<br><br>전화번호 열이 수식으로 된 경우 csv로 저장하여 발주하세요";
                return 7;
            }
            // 위메프 딜번호 없으면 오류
            else if( $shop_code == 54 && $orderArr[$k]["code2"] == "" )
            {
                $msg = "딜번호 없는 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // 위메프 딜번호 형식 오류
            else if( $shop_code == 54 && preg_match('/[^0-9]/',$orderArr[$k]["code2"],$matches) )
            {
                $msg = "딜번호 형식이 잘못된 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // 위메프 구매자MID 없으면 오류
            else if( $shop_code == 54 && $orderArr[$k]["order_id_seq"] == "" )
            {
                $msg = "구매자MID 없는 주문이 발견되었습니다.<br><br>발주서를 확인하세요";
                return 7;
            }
            // 위메프 송장번호 있으면 발주 안함
            else if( $shop_code == 54 && $orderArr[$k]["order_type2"] > "" )
                array_pop( $orderArr );
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
                if( $order_type == "주문" || (_DOMAIN_ =='caraz' && $order_type == "일반") )
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
                // order_type (배송구분) 가 '교환'인 경우, 주문번호 뒤에 "교환" + '주문상세번호(주문내역일련번호)' 추가하여 발주 => 환경설정
                else if( $order_type == "교환" && $_SESSION[BALJU_ORDER_A] )
                {
                    $orderArr[$k]["order_id"] .= "_교환" . $orderArr[$k]["order_id_seq"];
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
                    
                    // 교환주문 정산 0 처리
                    if( _DOMAIN_ == '_ezadmin' || _DOMAIN_ == 'changsin' )
                    {
                        $orderArr[$k]["code11"] = 0;
                        $orderArr[$k]["code12"] = 0;
                        $orderArr[$k]["code13"] = 0;
                        $orderArr[$k]["code14"] = 0;
                        $orderArr[$k]["code15"] = 0;
                        $orderArr[$k]["code16"] = 0;
                        $orderArr[$k]["code17"] = 0;
                        $orderArr[$k]["code18"] = 0;
                        $orderArr[$k]["code19"] = 0;
                        $orderArr[$k]["code20"] = 0;
                        $orderArr[$k]["code31"] = 0;
                        $orderArr[$k]["code32"] = 0;
                        $orderArr[$k]["code33"] = 0;
                        $orderArr[$k]["code34"] = 0;
                        $orderArr[$k]["code35"] = 0;
                        $orderArr[$k]["code36"] = 0;
                        $orderArr[$k]["code37"] = 0;
                        $orderArr[$k]["code38"] = 0;
                        $orderArr[$k]["code39"] = 0;
                        $orderArr[$k]["code40"] = 0;
                    }

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
            // 27:하프클럽, 70:오가게(통합)
            else if( $shop_code == 27 || $shop_code == 70 )
            {
                // 주문번호(order_id)가 "합계"면 건너뛴다.
                if( $orderArr[$k]["order_id"] == "합계" )
                    array_pop( $orderArr );
                // order_type (교환주문) = "N"
                else if( $order_type == "N" )  // 정상주문
                    $k++;
                // order_type (교환주문) = "Y" => 환경설정
                else if( $order_type == "Y" )
                {
                    if( $_SESSION[BALJU_ORDER_F] )
                        $k++;
                    else
                        array_pop( $orderArr );
                }
                else
                {
                    array_pop( $orderArr );
                    debug( "[$shop_id] 하프클럽, 오가게(통합) 주문구분 오류 : " . $order_type );
                }
            }
            // 아이하우스의 경우, 주문번호(order_id)가 "합계"면 건너뛴다.
            else if( $shop_code == 28 && $orderArr[$k]["order_id"] == "합계" )
                array_pop( $orderArr );
            // H몰 : order_type (주문구분) = "교환출고"
            else if( $shop_code == 43 && $order_type == "교환출고" )
                array_pop( $orderArr );
            // cafe24 : pay_type (결제수단)
            else if( $shop_code == 72 )
            {
                // 개인결제창 발주제외
                if( $_SESSION[BALJU_ORDER_H] && strpos($orderArr[$k]["product_name"], "개인결제창") !== false )
                    array_pop( $orderArr );
                else
                {
                    // 결제
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
            }
            // 옥션, 지마켓 esm "택배사명:방문수령" 발주 안함
            // 2014-07-15 스마트배송
            else if( ($shop_code == 1 || $shop_code == 2 || $shop_code == 78 || $shop_code == 79) && $order_type == "방문수령" && !$_SESSION[BALJU_ORDER_G] )
                array_pop( $orderArr );
            // 11번가 "송장번호:[방문수령]인증전" 발주 안함
            else if( $shop_code == 50 && $order_type == "[방문수령]인증전" && !$_SESSION[BALJU_ORDER_G] )
                array_pop( $orderArr );
            // 샵N, 체크아웃 "배송방법:방문수령" 발주 안함
            else if( ($shop_code == 5 || $shop_code == 51) && $order_type == "방문수령" && !$_SESSION[BALJU_ORDER_G] )
                array_pop( $orderArr );
            // 2014-10-20 장경희
            // 홈앤쇼핑 : order_type (주문구분:교환배송) => 발주안함
            else if( $shop_code == 65 && $order_type == "교환배송" && !$_SESSION[BALJU_ORDER_I] )
                array_pop( $orderArr );
            // 아이스타일24 : order_type (배송상태) != "발송완료"
            else if( $shop_code == 74 && $order_type == "발송완료" )
                array_pop( $orderArr );
            // onpop 맘스투데이 상품명에 수량 제거
            // 1. 오랄비 스테이지/2단계 - 20EA            
            else if( _DOMAIN_ == 'onpop' && $shop_id == 10083 )
            {
                $new_name = preg_replace('/\s\-\s[0-9]+EA$/','', $orderArr[$k]["shop_product_id"]);
                $orderArr[$k]["shop_product_id"] = $new_name;
                $orderArr[$k]["product_name"] = $new_name;
                $k++;
            }
            // lovestar9 "바로가기 아이콘 할인/적립" 발주안함
            else if( (_DOMAIN_ == 'lovestar9' || _DOMAIN_ == 'leroom' || _DOMAIN_ == 'dragon') && $shop_code == 68 && $orderArr[$k]["product_name"] == "바로가기 아이콘 할인/적립" )
                array_pop( $orderArr );
            // 2014-10-02 장경희
            // going 10080 맘스투데이 "배송정보"가 "결제완료"인 경우만 발주
            else if( _DOMAIN_ == 'going' && $shop_id == 10080 && $orderArr[$k]["order_type"] != "결제완료" )
                array_pop( $orderArr );
            else
            {
                // ljc6605 판매처 10081, 10082 code30 값 없으면 오류
                if( _DOMAIN_ == 'ljc6605' && ($shop_id==10081 || $shop_id==10082) && !$orderArr[$k]["code30"] )
                {
                    $msg = "문의전화 데이터가 없는 주문이 있습니다.<br><br>발주서를 확인하세요";
                    return 7;
                }

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
            // 옥션, 지마켓은 서로 교차 검사
            // 2014-07-15 스마트배송 추가
            if( $shop_id % 100 == 1 || $shop_id % 100 == 2 || $shop_id % 100 == 78 || $shop_id % 100 == 79 )
            {
                $query = "select * from shopinfo 
                           where shop_id%100 in (1,2,78,79) and 
                                 shop_id <> $shop_id and 
                                 balju_stop=0";
            }
            else
            {
                $query = "select * from shopinfo 
                           where shop_id%100 = $shop_id%100 and 
                                 shop_id <> $shop_id and 
                                 balju_stop=0";
            }
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
                        $query_shop = "select shop_product_id 
                                         from orders 
                                        where shop_id = $data[shop_id] and 
                                              shop_product_id in $shop_pid and 
                                              collect_date > '$old_day' and
                                              copy_seq = 0 ";  // 복사주문 검사 제외
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
                        if( (int)$matches[1] <= (int)$orderArr[$i][org_trans_who] )
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

        // auto_trans 리셋을 위한 배열
        $reset_auto_trans = array();
        
        $cnt_new = 0;
        foreach( $orderArr as $order )
        {
            //***********************************
            // jbstar 샵링커 판매처
            //***********************************
            if( $shop_id==10066 && _DOMAIN_ == 'jbstar' )
            {
                switch( $order[code30] )
                {
                    case "(주)LG패션"       :  $_shop_id = 10084; break;
                    case "롯데아이몰"       :  $_shop_id = 10014; break;
                    case "AK몰"             :  $_shop_id = 10042; break;
                    case "신세계닷컴2.0"    :  $_shop_id = 10015; break;
                    case "(주)롯데닷컴"     :  $_shop_id = 10009; break;
                    case "GS홈쇼핑(eshop)"  :  $_shop_id = 10007; break;
                    case "CJ홈쇼핑"         :  $_shop_id = 10026; break;
                    case "(주)현대홈쇼핑"   :  $_shop_id = 10080; break;
                    case "디앤샵"           :  $_shop_id = 10003; break;
                    case "하프클럽"         :  $_shop_id = 10127; break;
                    case "이지웰"           :  $_shop_id = 10091; break;
                    case "이마트"           :  $_shop_id = 10016; break;
                    case "샵N"              :  $_shop_id = 10051; break;
                    case "NH쇼핑"           :  $_shop_id = 10039; break;
                    case "홈플러스"         :  $_shop_id = 10022; break;
                    case "YES24"            :  $_shop_id = 10047; break;
                    case "홈앤쇼핑"         :  $_shop_id = 10065; break;
                }
            }
            else if( $shop_id==10066 && _DOMAIN_ == 'jbstar2' )
            {
                switch( $order[code30] )
                {
                    case "(주)LG패션"       :  $_shop_id = 10084; break;
                    case "롯데아이몰"       :  $_shop_id = 10014; break;
                    case "AK몰"             :  $_shop_id = 10042; break;
                    case "신세계닷컴2.0"    :  $_shop_id = 10015; break;
                    case "(주)롯데닷컴"     :  $_shop_id = 10009; break;
                    case "GS홈쇼핑(eshop)"  :  $_shop_id = 10007; break;
                    case "CJ홈쇼핑"         :  $_shop_id = 10026; break;
                    case "(주)현대홈쇼핑"   :  $_shop_id = 10043; break;
                    case "디앤샵"           :  $_shop_id = 10003; break;
                    case "하프클럽"         :  $_shop_id = 10127; break;
                    case "이지웰"           :  $_shop_id = 10091; break;
                    case "이마트"           :  $_shop_id = 10016; break;
                    case "샵N"              :  $_shop_id = 10051; break;
                    case "NH쇼핑"           :  $_shop_id = 10039; break;
                    case "홈플러스"         :  $_shop_id = 10022; break;
                    case "YES24"            :  $_shop_id = 10047; break;
                    case "홈앤쇼핑"         :  $_shop_id = 10065; break;
                }
            }
            else if( $shop_id==10066 && _DOMAIN_ == 'jsg' )
            {
                switch( $order[code30] )
                {
                    case "신세계닷컴2.0"    :  $_shop_id = 10015; break;
                }
            }
            //***********************************
            // makoto AK몰 판매처
            //***********************************
            else if( $shop_id == 10042 && _DOMAIN_ == 'makoto' )
            {
                if( strpos($order[product_name], "MAKOTO") !== false || strpos($order[product_name], "무료배송") !== false )
                    $_shop_id = 10042;
                else
                    $_shop_id = 10142;
            }
            //***********************************
            // makoto 롯데아이몰 판매처
            //***********************************
            else if( $shop_id == 10014 && _DOMAIN_ == 'makoto' )
            {
                if( strpos($order[product_name], "MAKOTO") !== false || strpos($order[product_name], "무료배송") !== false )
                    $_shop_id = 10014;
                else
                    $_shop_id = 10114;
            }
            //***********************************
            // mkh2009 CJ 판매처
            //***********************************
            else if( $shop_id == 10026 && _DOMAIN_ == 'mkh2009' )
            {
                if( strpos(str_replace(" ","",$order[product_name]), "오클락특가") !== false )
                    $_shop_id = 10126;
                else
                    $_shop_id = 10026;
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
                        // array(355705,355840,355841,355842,355843,355844,355845,355846,355847,355848,409840,409850,409851,409852,409853,409890,409891,409892,409893,409910,409911,409912,409913,409919,417910,417911,417912,417913,417920,417921,417922,417923,417930,417931,417932,417933,513890,513891,513892,513893,527913,536928,537809,537830,537831,537832,537833,537834,537835,537836,537840,537841,537842,537843,537844,537845,537846,537847,537848,537849,537850,537851,537852,537853,537870,537871,537872,537873,537874,537900,537901,537902,537903,537904,537905,537907,537909,539910,539911,539912,539913,539914,539915,539916,539917,539918,539919,548940,548941,548944,548993,556830,556831,556832,556833,556834,556835,556836,556837,556838,556839,556840,556841,556842,556843,556844,556846,556847,556848,556849,556850,556851,556852,556853,556854,556855,573810,573811,573812,573813,573814,573815,573816,573817,573818,573819,573955,579910,579911,579912,579913,579914,579915,618430,618450,650910,650911,650912,650913,650914,650915,650916,650920,650921,650922,650923,650924,650925,650926,650927,650930,650931,650932,650933,650934,664250,664270,695950,695951,695952,695980,695983,799800,799801,799802,799803,799804,799805,799810,799811,799812,799813,799820,799821,799822,799823,409832) 
                        // 2014-08-21 장경희 수정
                        array(355705,355840,355841,355843,355844,355847,355848,409840,537850,537851,537852,537853,539910,539911,539912,539913,539914,539915,539916,539917,539918,539919,548940,548941,548944,409832,548943,535915)
                    ) 
                )
                    continue;
            }
            
            // 2014-11-10 장경희. AK몰 누락 확인
            if( $shop_id % 100 == 42 )
            {
                $query_ak = "select code5 from orders where shop_id=$shop_id and order_id='$order[order_id]' and orders_code<>$orders_code";
                $result_ak = mysql_query($query_ak, $connect);
                if( mysql_num_rows($result_ak) )
                {
                    $_chk = false;
                    while( $data_ak = mysql_fetch_assoc($result_ak ) )
                    {
                        if( $data_ak[code5] == $order[code5] )
                        {
                            $_chk = true;
                            break;
                        }
                    }
                    
                    // 같은 주문번호에, 없는 상품순번
                    if( !$_chk )
                    {
                        // event
                        $query_event = "insert sys_event_list 
                                           set domain = '" . _DOMAIN_ . "', 
                                               who = '" . $_SESSION[LOGIN_ID] . "',
                                               event = 'AK 발주 누락',
                                               cmt = '주문번호:$order[order_id], 상품순번:$order[code5]'";
                        mysql_query($query_event, $sys_connect);
                    }
                }
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
            // AK몰
            else if( $shop_id % 100 == 42 )
            {
                if( _DOMAIN_ == 'makoto' )
                    $query = "select * from orders where shop_id in (10042, 10142) and order_id in ('$order[order_id]','$order[code2]') and orders_code<>$orders_code";
                else if( _DOMAIN_ == 'jbstar' || _DOMAIN_ == 'parklon' )
                    $query = "select * from orders where shop_id % 100 = 42 and order_id in ('$order[order_id]','$order[code2]') and code5 = '$order[code5]' and orders_code<>$orders_code";
                else
                    $query = "select * from orders where shop_id % 100 = 42 and order_id in ('$order[order_id]','$order[code2]') and orders_code<>$orders_code";
            }
            // makoto 롯데아이몰
            else if( _DOMAIN_ == 'makoto' && $shop_id % 100 == 14 )
                $query = "select * from orders where shop_id in (10014, 10114) and order_id='$order[order_id]' and orders_code<>$orders_code";
            // mkh2009 CJ
            else if( _DOMAIN_ == 'mkh2009' && $shop_id % 100 == 26 )
                $query = "select * from orders where shop_id in (10026, 10126) and order_id='$order[order_id]' and orders_code<>$orders_code";
            // 쿠팡(자동) - 합포장번호 확인(딜번호 확인안함)
            else if( $shop_id % 100 == 53 )
                $query = "select * from orders where shop_id=$_shop_id and order_id='$order[order_id]' and order_id_seq = '$order[order_id_seq]' and orders_code<>$orders_code";
            // 위메프(자동) - 딜번호(code2) 확인
            // 2014-08-11. 주문번호 번경으로 딜번호, 구매자MID, 주문일, 주문시간
            else if( $shop_id % 100 == 54 )
                $query = "select * from orders where code2='$order[code2]' and order_id_seq='$order[order_id_seq]' and concat(order_date, ' ', order_time)='$order[order_date]' and orders_code<>$orders_code";
            // 티몬(자동) - 딜번호(code1) 확인
            else if( $shop_id % 100 == 55 )
                $query = "select * from orders where shop_id % 100 in (41,55) and order_id='$order[order_id]' and code1 = '$order[code1]' and orders_code<>$orders_code";
            // 티몬 수동 icos - 딜번호(code1) 확인
            else if( $shop_id % 100 == 41 && _DOMAIN_ == 'icos' )
                $query = "select * from orders where shop_id % 100 = 41 and order_id='$order[order_id]' and code1 = '$order[code1]' and orders_code<>$orders_code";
            // 신세계/이마트 "배송번호" 확인
            else if( $shop_id % 100 == 15 || $shop_id % 100 == 16 )
                $query = "select * from orders where shop_id=$_shop_id and order_id_seq='$order[order_id_seq]' and orders_code<>$orders_code";
            // alice 메이크샵 10068 (시크헤라메이크샵) 상품별거래번호로 중복체크
            // nanasalon 메이크샵 10068 상품별거래번호로 중복체크
            else if( (_DOMAIN_ == 'alice' || _DOMAIN_ == 'nanasalon') && $shop_id == 10068 )
                $query = "select * from orders where shop_id=$_shop_id and order_id_seq='$order[order_id_seq]' and orders_code<>$orders_code";
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
                    {
                        $reset_auto_trans[] = $data[seq];
                        // mysql_query("update orders set auto_trans=0 where seq=$data[seq]", $connect);
                    }

					//가장 최근 취소중. EZAUTO 취소만 취소철회조회에 INSERT. 최웅//
					//쿠팡, 인터파크는 취소해도 발주가 들어옴..
                    if( $data[order_cs] >= 1  && $data[order_cs] <= 4 && $shop_id % 100 != 53 && $shop_id % 100 != 6 )
                    {
						debug("취초철회 [$order[order_id]] START ######");
						if($this->check_shop_cancel_withdraw($data,$order)) // 판매처별 조건 check
						{
	                    	$csinfo_query = "SELECT * FROM csinfo WHERE order_seq = '$data[seq]' ORDER BY input_date DESC, input_time DESC";
	                     	$csinfo_result = mysql_query( $csinfo_query, $connect );
	
	                     	
	                     	$_ezauto_cancel = 0;
	                     	while($csinfo_data = mysql_fetch_assoc($csinfo_result))
	                     	{ // 이지오토 취소가 부분취소,전체취소보다 위에 있어야함.
	                     		if($csinfo_data[writer]=="이지오토" && preg_match("/Client 자동취소/",$csinfo_data[content]))
	                     		{
	                     			$_ezauto_cancel = 1;
	                     			$_cancel_date = $csinfo_data[input_date]." ".$csinfo_data[input_time];
	                     			break;
	                     		}
	                     		else if($csinfo_data[cs_type] == 10 || $csinfo_data[cs_type] == 16 ) //부분취소, 전체취소
	                     			break;
	                     	}
	                     	
	                     	//order_cs IN (1,2,3,4) AND 이지오토 자동취소 = 취소 철회
	                     	if($_ezauto_cancel)
	                     	{
	                     		$cancel_withdraw_select = "SELECT count(*) cnt FROM cancel_withdraw WHERE order_seq = $data[seq] AND confirm_check < 1";
	                     		$cancel_withdraw_result = mysql_query( $cancel_withdraw_select, $connect );
	                     		$cancel_withdraw_data = mysql_fetch_assoc($cancel_withdraw_result);
	                     		
	                     		if($cancel_withdraw_data[cnt] == 0)
	                     		{
									debug("취초철회 INSERT #####");
									debug("cancel_withdraw_data[cnt] : ".$cancel_withdraw_data[cnt]);
									debug("cancel_withdraw_select : ".$cancel_withdraw_select);
	
		                     		$cancel_withdraw_insert = 
		                     		"INSERT IGNORE INTO cancel_withdraw SET
		                     		 		order_seq		= '$data[seq]'
		                     		 	,	order_id		= '$data[order_id]'
		                     		 	,	order_id_seq	= '$data[order_id_seq]'
		                     		 	,	cancel_date		= '$_cancel_date'
		                     		 	,	insert_date		= now()
		                     		 	";
		                     		 	
		                     		 mysql_query( $cancel_withdraw_insert, $connect );
		                     	}
		                     	else 
		                     	{
									debug("취초철회 미확인 주문이 있으므로 pass #####");
		                     	}
	                     	}
	                    }
						debug("취초철회 END ######");
                    }
                }
                continue;
            }

            // 소셜(티몬,쿠팡,위메프) 3개월전 주문 제외
            if( $shop_id % 100 == 53 || $shop_id % 100 == 54 || $shop_id % 100 == 55 )
            {
                // 3개월 전 날짜
                $bck_date = date("Y-m-d", mktime(0, 0, 0, intval(date('m'))-3, 1, intval(date('Y'))));
            
                if( $order[order_date] < $bck_date )
                    continue;
            }
            
            // 신세계 (구)주문번호 한번더 확인
            if( $shop_id % 100 == 15 )
            {
                $query = "select * from orders where shop_id=$_shop_id and order_id='$order[code28]' and orders_code<>$orders_code";
                $result = mysql_query( $query, $connect );
                if( mysql_num_rows( $result ) > 0 )
                    continue;
            }
            
            // cafe24 같은 파일 내에 동일 배송코드 있으면 발주안함
            if( ($shop_id % 100 == 72 || $shop_id % 100 == 73) && _DOMAIN_ != '3point' && _DOMAIN_ != 'joongwon' )
            {
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and code2='$order[code2]' and orders_code=$orders_code";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) > 0 )
                    continue;
            }

            // NS홈쇼핑 같은 파일 내에 동일 주문번호, 주문상세순번 중복있으면 발주안함
            // 2014-08-12 김정숙 요청
            if( $shop_id % 100 == 8 && _DOMAIN_ != 'grandnongsan' && _DOMAIN_ != 'madebms' )
            {
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and order_id_seq='$order[order_id_seq]' and orders_code=$orders_code";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) > 0 )
                    continue;
            }

            // 2014-12-01 장경희.
            // AK 같은 파일 내에 동일 주문번호, 주문상세순번 중복있으면 발주안함
            if( $shop_id % 100 == 42 )
            {
                $query = "select * from orders where shop_id=$shop_id and order_id='$order[order_id]' and order_id_seq='$order[order_id_seq]' and orders_code=$orders_code";
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
            
            // 2014-10-21 장경희
            // 메이크샵 정산 필드 : 할인 상세내역(주문전체)
            if( $shop_id % 100 == 68 )
            {
                for($_n = 11; $_n <= 40; $_n++)
                {
                    if( str_replace(" ","", $headerArr["header"]["code$_n"]) == "할인상세내역(주문전체)" )
                    {
                        $sum = 0;
                        preg_match_all("/:(.+)원/",$order["code$_n"], $out);
                        foreach( $out[1] as $out_val )
                            $sum += str_replace(",","",$out_val);
                            
                        $order["code$_n"] = $sum * -1;
                    }
                }
            }
            
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
            if( substr($shop_id,-2) == 50 && _DOMAIN_ != 'soramam' && _DOMAIN_ != 'namsun' && _DOMAIN_ != 'snowbuck' && _DOMAIN_ != 'ecoskin'  && _DOMAIN_ != 'paperplanes' )
            {
                if( _DOMAIN_ == 'clubmobile' )
                    $options = $order[options];
                else if( _DOMAIN_ == 'ggee2' || _DOMAIN_ == 'jpole2' || _DOMAIN_ == 'soonsoo' )
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
            else if( $shop_id % 100 == 68 && ($order[shop_product_id]=="99999990GIFT" || $order[shop_product_id]=="9999990MGIFT") )
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
            if( preg_match('/^[\[\(]([0-9]{3}\-?[0-9]{3})[\]\)]\s?(.*)/', $order[recv_address], $matches) )
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
            
            // majorgolf 10084
            if( (_DOMAIN_ == 'majorgolf' && $shop_id == 10084) || (_DOMAIN_ == 'mastersgolf' && $shop_id == 10193) )
            {
                if( preg_match('/^\(?([0-9]{3}\-[0-9]{3})\)? (.*)/', $order[recv_address], $matches) )
                {
                    $order[recv_zip] = $matches[1];
                    $order[recv_address] = $matches[2];
                }
            }
            
            // 주문일 - 공백 앞부분
            $order_date_arr = explode(" ", $order[order_date]);
            $order_date = $order_date_arr[0];
            
            if( $shop_id % 100 == 69 )
            {
                // 2014年03月17日
                $order_date = str_replace(array('年','月'), '-', $order_date);
                $order_date = str_replace(array('日'), '', $order_date);
            }

            // 주문시간
            if( $shop_id % 100 == 68 )
            {
                // 메이크샵 
                //2014-06-09 (12:03:45)
                if( preg_match('/\((.+)\)/', $order[order_time], $_time) )
					$order_time = $_time[1];
				else if( preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}/', $order[order_time], $_time) ) // 2014-07-30 15:26:05
                	$order_time = substr($order[order_time],11);
				else//20140607143132
                	$order_time = substr($order[order_time],8,2) . ":" . substr($order[order_time],10,2) . ":" . substr($order[order_time],12,2);
            }
            else if( preg_match('/([0-9]+):([0-9]+):([0-9]+)/', $order[order_time], $matches) )
            {
                if( strpos($order[order_time], "오후") !== false || strpos($order[order_time], "PM") !== false )
                    if( $matches[1] < 12 )  $matches[1] += 12;

                $order_time = $matches[1] . ":" . $matches[2] . ":" . $matches[3];
            }
            else if( $shop_id % 100 == 151 || (_DOMAIN_=='sandl' && $shop_id==10007) )// 51(샵N)은 원복되서 적용안함.
            {
                $EXCEL_DATE = $order[order_date];
                $UNIX_DATE = ($EXCEL_DATE - 25569) * 86400;
                
                $order_date = gmdate("Y-m-d", $UNIX_DATE);
                $order_time = gmdate("H:i:s", $UNIX_DATE);
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

            // 메모
            $memo = $order[memo];
            
            
            if($_SESSION[ISLAND_MSG]) //도서지역 메모 표시. 환경설정. //최웅
            {
debug("환경설정 도서지역 메모표시!");
            	$island = 0;
            	$island_shop_id = "";
            	
            	//step1 ..shop_id 탐색..
            	if(!$_SESSION[ISLAND_SHOP_A]) //전체선택은 WHERE절 걸 필요없음..
	            {
	            	if($_SESSION[ISLAND_SHOP_B]) //쿠팡
		            	$island_shop_id = $island_shop_id ? $island_shop_id .=",53 " : $island_shop_id .="53";
		            if($_SESSION[ISLAND_SHOP_C]) //위메프
		            	$island_shop_id = $island_shop_id ? $island_shop_id .=",54 " : $island_shop_id .="54";
		            if($_SESSION[ISLAND_SHOP_D]) //티몬
		            	$island_shop_id = $island_shop_id ? $island_shop_id .=",55 " : $island_shop_id .="55";
	
		            $shop_id_query = "SELECT * FROM shopinfo WHERE shop_id % 100 IN ($island_shop_id)";
		            $shop_id_result = mysql_query($shop_id_query, $connect);
					$island_shop_id = "";
		        	while( $shop_id_data = mysql_fetch_assoc($shop_id_result) )
		        		$island_shop_id = $island_shop_id ? $island_shop_id .",$shop_id_data[shop_id] " : $island_shop_id ." $shop_id_data[shop_id] ";

		            if($_SESSION[ISLAND_SHOP_E] && $_SESSION[ISLAND_CUSTOM_SHOPID]) //사용자
		            	$island_shop_id = $island_shop_id ? $island_shop_id.", ".$_SESSION[ISLAND_CUSTOM_SHOPID] : $_SESSION[ISLAND_CUSTOM_SHOPID];


		            if($island_shop_id && in_array($shop_id, explode(",",str_replace(" ","",$island_shop_id))))
		            	$island = 1;
	            }
	            else
	            	$island = 1;

	            //step2 zipcode 탐색..	
            	if($island)
            	{
            		$island = 0;
            		$zip_num = (int)preg_replace('/[^0-9]/','',$order[recv_zip]); // orders의 우편번호
					$zip_code = $this->get_default_zip_code(); //class_top에 있는 기본 우편번호
					
					if($_SESSION[ISLAND_ZIPCODE]) //사용자 정의 코드
						$zip_code = $_SESSION[ISLAND_CUSTOM_ZIPCODE];
						
            		if(in_array($zip_num, explode(",",$zip_code)))
            			$island = 1;
            		
            		//기본 미포함 [690~ 전체 미포함]
            		//커스텀 미포함[690~ 미포함]
            		if($_SESSION[ISLAND_JEJU_ZIPCODE] && substr($order[recv_zip],0,2) == "69") //제주 미포함
            			$island = 0;
            		else if($island)
            		 	$island = 1;
            		 	
            		 	
            		//기본 포함 [690~ 전체 포함]
            		//커스텀 포함[커스텀중 69만 포함]
            		if(!$_SESSION[ISLAND_ZIPCODE] && !$_SESSION[ISLAND_JEJU_ZIPCODE] && substr($order[recv_zip],0,2) == "69" )
            			$island = 1;
            	}
            	
            	//step3 msg_text 붙임..
            	if($island && $_SESSION[ISLAND_MSG_TXT])
            	{
            		$msg_txt = $_SESSION[ISLAND_MSG_TXT];
            		switch($_SESSION[ISLAND_MSG_POSITION])
            		{
	            		case 1://주소앞
							$recv_address = $msg_txt . $recv_address;
	            		break;
	            		case 2://주소뒤
	            			$recv_address = $recv_address. $msg_txt;
	            		break;
	            		case 3://메모앞
	            			$memo = $msg_txt . $memo;
	            		break;
	            		case 4://메모뒤
	            			$memo = $memo. $msg_txt;
	            		break;
            		}
            	}
            }
            	

            
            if( _DOMAIN_ == 'donnandeco' || _DOMAIN_ == '_ezadmin' )
            {
                $zip_num = (int)preg_replace('/[^0-9]/','',$order[recv_zip]);
				
                // 도서지역
                if(in_array($zip_num, $this->get_donnandeco_zip_code("array","1")))
                    $memo = "도서지역[◈3000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","2")))
                    $memo = "도서지역[◈4000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","3")))
                    $memo = "도서지역[◈4500]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","4")))
                    $memo = "도서지역[◈5000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","5")))
                    $memo = "도서지역[◈6000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","6")))
                    $memo = "도서지역[◈7000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","7")))
                    $memo = "도서지역[◈8000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","8")))
                    $memo = "도서지역[◈10000]" . $memo;
                else if(in_array($zip_num, $this->get_donnandeco_zip_code("array","9")))
                    $memo = "도서지역[◈11000]" . $memo;
                
                
            }           

            // kshsame2 배송불가, 배송지연
            if( _DOMAIN_ == 'kshsame2' || _DOMAIN_ == '_ezadmin' )
            {
                $zip_num = (int)preg_replace('/[^0-9]/','',$order[recv_zip]);

                // 배송불가
                if(in_array($zip_num, 
                    array(400460,409880,409830,409890,409840,409850,409910)
                ))
                    $recv_address = "[배송불가지역]" . $recv_address;
                
                // 배송지연
                if(in_array($zip_num, 
                    array(356020,356080,356880,356980,356890)
                ))
                    $recv_address = "[배송지연지역]" . $recv_address;
            }
            
            // 옥션, 지마켓 옵션이 매우 긴 경우, 또는 맘스투데이
            // 2014-07-15 스마트배송
            if( $shop_id % 100 == 1 || $shop_id % 100 == 2 || $shop_id % 100 == 78 || $shop_id % 100 == 79 || $shop_id % 100 == 54 || (_DOMAIN_ == 'iggo' && $shop_id == 10084) || (_DOMAIN_ == 'wecompany' && $shop_id == 10080))
            {
                if( mb_strlen( $options, "utf8" ) > 255 )
                {
                    $query_lo = "insert long_options set options='$options', crdate=now()";
                    mysql_query($query_lo, $connect);
                    
                    $query_lo = "select * from long_options order by seq desc limit 1";
                    $result_lo = mysql_query($query_lo, $connect);
                    $data_lo = mysql_fetch_assoc($result_lo);
                    
                    $options = "*Long Options(" . $data_lo[seq] . ")* " . $options;
                }
            }

            // 쿠팡, 티몬 소셜 옵션 번호 삭제
            if( $_SESSION[DEL_SOCIAL_OPTION_NO] )
            {
                // 쿠팡
                if($shop_id % 100 == 53)
                    $options = preg_replace('/^선택\s?[0-9]+\)\s?/', '', $options );
                    
                // 티몬
                else if($shop_id % 100 == 55)
                    $options = preg_replace('/^[0-9]+\.\s?/', '', $options );
            }

            // ttree 옥션, 지마켓 옵션 앞에 2자리 번호 삭제
            if( _DOMAIN_ == 'ttree' && ($shop_id % 100 == 1 || $shop_id % 100 == 2) )
                $options = preg_replace('/^<주문옵션>[0-9]{2}/', '<주문옵션>', $options );

            // runiya 모든 판매처 옵션 앞에 "01." 삭제
            if( _DOMAIN_ == 'runiya' )
            {
                $options = preg_replace('/^<주문옵션>[0-9]+\./', '<주문옵션>', $options );
                $options = preg_replace('/^[0-9]+\./', '', $options );
            }
            
            // box4u, jkhdev 합포유지 code30은 1
            // 2014-01-22 박팀장. 일단 미사용
            if( _DOMAIN_ == '_box4u' || _DOMAIN_ == '_jkhdev' )
                $order[code30] = 1;
                
            // 66girls 청주시 우편번호 자동 수정
            if( _DOMAIN_ == '66girls' || _DOMAIN_ == 'ezadmin' )
            {
                $_find = array_search($order[recv_zip], $chungju_old_zip);
                if( $_find !== false )
                    $order[recv_zip] = $chungju_new_zip[$_find];
            }

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
                             memo            = '$memo',
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
                             order_status    = 5,
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
            $cnt_new++;
        }

        // 전화번호 저장
        $query = "select seq, recv_tel, recv_mobile, order_tel, order_mobile from orders where status=0 and order_status=5";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $this->inset_tel_info($data[seq], array($data[recv_tel],$data[recv_mobile],$data[order_tel],$data[order_mobile]));

        $query = "update orders set order_status = 10 where status=0 and order_status=5";
        mysql_query($query, $connect);

        $reset_auto_trans_list = implode(",", array_unique($reset_auto_trans));
        mysql_query("update orders set auto_trans=0 where seq in ($reset_auto_trans_list)", $connect);

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
	function check_shop_cancel_withdraw($data,$order)
	{
		$shop_xd = $data[shop_id] % 100;
		$is_check = false;
		
		if(($shop_xd == 1 || $shop_xd == 2) && ($data[order_id] == $order[order_id]) )
			$is_check = true;
		else if(   ($shop_xd == 3 || $shop_xd == 5 || $shop_xd ==6 || $shop_xd ==9 || $shop_xd ==50 || $shop_xd ==51)
		         && ($data[order_id] == $order[order_id]) && ($data[order_id_seq] == $order[order_id_seq])   )
			$is_check = true;
		else if(   ($shop_xd == 57 || $shop_xd == 60 || $shop_xd ==15 || $shop_xd ==16)
		         && ($data[order_id] == $order[order_id]) && ($data[order_id_seq] == $order[order_id_seq])  && ($data[shop_product_id] == $order[shop_product_id])   )
			$is_check = true;
		else if(   ($shop_xd == 53 )
		         && ($data[order_id] == $order[order_id]) && ($data[options] == $order[options])   )
			$is_check = true;
		else if(   ($shop_xd == 72 || $shop_xd == 73 )
		         && ($data[order_id] == $order[order_id]) && ($data[code2] == $order[code2])   )
			$is_check = true;
		return $is_check;
	}
}
?>
