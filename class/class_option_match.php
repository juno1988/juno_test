<?

// class_option_match
// 신발주의 option_match 테이블 처리 class
// date: 2009.2.8
// jkh
// 
// 이 파일은 utf8 포멧임

class class_option_match{

    //////////////////////////////////////////////////////////////////////
    // 이미 등록된 상품인지 확인한다.
    function is_registered($shop_id, $shop_product_id)
    {
        global $connect;
        
        $query = "select * from option_match where shop_id=$shop_id and shop_product_id='$shop_product_id'";
        $result = mysql_query ( $query, $connect );
        if( mysql_num_rows( $result ) > 0 )
            return true;
        else
            return false;
    }

    //////////////////////////////////////////////////////////////////////
    // 옵션 매치 정보를 등록한다.
    function set_option_match()
    {
        global $connect, $shop_id, $shop_product_id, $shop_options, $option_title, $nobase;

        // 이미 등록되어있으면 오류
        if( $this->is_registered($shop_id, $shop_product_id) )  return 1;

        // 새로 등록한다.
        $query = "insert option_match 
                     set shop_id           = '$shop_id', 
                         shop_product_id   = '$shop_product_id',
                         option_title      = '$option_title',
                         nobase            = $nobase,
                         crdate            = now(),
                         worker            = '$_SESSION[LOGIN_NAME]'";                         
        // 등록 실패하면 오류
        if( !mysql_query ( $query, $connect ) )  return 2;
        
        // 발주중인 주문 중에서 일치하는 상품을 찾는다.
        $query = "select * 
                    from orders 
                   where status          = 0 and 
                         order_status    = 30 and 
                         shop_id         = $shop_id and
                         shop_product_id = '$shop_product_id' and
                         memo_check      = 0";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 해당 상품에 대해, 이미 order_products에 등록된 상품을 삭제한다.
            $query_del = "delete from order_products where order_seq = $data[seq]";
            mysql_query( $query_del, $connect );
            
            // 옵션 문자셋 변환
            $options = $data[options];

            // 옥션의 경우, [숫자 천단위 콤마,] 삭제
            if( $data[shop_id] % 100 == 1 || $data[shop_id] % 100 == 4 )
            {
                $options = preg_replace_callback ("|[0-9]+(,)[0-9]{3}원|U",
                     create_function(
                     '$matches',
                     'return str_replace(",", "",$matches[0]);'
                ),
                $options);
            }

            // 매칭 옵션에서 가격삭제 
            if( $_SESSION[DEL_PRICE] )
            {
                // 옥션(여러줄) - 본상품에만 가격 정보가 붙어있음
                if( $data[shop_id] % 100 == 1 )
                {
                    $options = preg_replace_callback ("|/-?[0-9]+원$|U",
                         create_function(
                         '$matches',
                         'return $matches[0] = "/";'
                    ),$options);

                    $options = preg_replace_callback ("|/-?[0-9]+원<추가구성상품>|U",
                         create_function(
                         '$matches',
                         'return $matches[0] = "/<추가구성상품>";'
                    ),$options);
                }

                // 옥션S(한줄) - 본상품에만 가격 정보가 붙어있음
                if( $data[shop_id] % 100 == 4 )
                {
                    $options = preg_replace_callback ("| \([0-9]+원\) [+\-][0-9]+원 /|U",
                         create_function(
                         '$matches',
                         'return $matches[0] = " /";'
                    ),$options);
                }

                // G마켓
                if( $data[shop_id] % 100 == 2 )
                {
                    $options = preg_replace_callback ("|\(-?[0-9]+원\),|U",
                         create_function(
                         '$matches',
                         'return $matches[0] = ",";'
                    ),$options);
                }

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

            // 설정대로 찢는다.
            $arr_option = $this->divide_options($shop_id, $shop_product_id, $options, $data[qty]);
            // 찢어진 옵션대로 상품 등록한다.
            foreach( $arr_option as $option )
            {
                // 옥션, 11번가는 추가상품도 수량 계산
                if( $shop_id % 100 == 50 || $shop_id % 100 == 1 || $shop_id % 100 == 4 )
                    $qty = $option[qty];
                else
                    $qty = $data[qty];
                    
                $reg_ok = class_order_products::insert_product(
                                $shop_id, 
                                $shop_product_id, 
                                $option['option'], 
                                '', 
                                $data[seq], 
                                $qty,
                                $option['marked'] );
                if( !$reg_ok )  break;
            }
            
            // 찢어진 주문에대해 매칭처리한다.
            $query_products = "select * from order_products where order_seq=$data[seq]";
            $result_products = mysql_query($query_products, $connect);
            while($data_products = mysql_fetch_array($result_products) )
            {
                // 매칭 정보를 찾는다.
                $query_match = "select id, qty 
                                  from code_match 
                                 where shop_id     = '$data_products[shop_id]' and 
                                       shop_code   = '$data_products[shop_product_id]' and
                                       shop_option = '$data_products[shop_options]'";
                $result_match = mysql_query( $query_match, $connect );
                if( mysql_num_rows( $result_match ) > 0 )
                {
                    // 찾아낸 매칭 정보를 배열로 만든다.
                    $arr_match_id = array();
                    $arr_match_qty = array();
                    while( $data_match = mysql_fetch_array( $result_match ) )
                    {
                        $arr_match_id[] = $data_match[id];
                        $arr_match_qty[] = $data_match[qty];
                    }
                    $match_fail = class_order_products::match_product($data_products[seq], $arr_match_id, $arr_match_qty, 0, 1);
                    if( $match_fail ) return 3;
                }
            }
        }
        return 0;
    }
    
    //////////////////////////////////////////////////////////////////////
    // 옵션 매치 정보를 삭제한다.
    function reset_option_match()
    {
        global $connect, $seq, $shop_id, $shop_product_id;

        // 옵션 찢기 정보를 삭제한다.
        $query = "delete from option_match where shop_id='$shop_id' and shop_product_id='$shop_product_id'";
        if( mysql_query($query,$connect) === false )  return 1;
        
        // 해당 주문을 검색한다.
        $query = "select * from orders where status=0 and order_status = 30 and shop_id='$shop_id' and shop_product_id='$shop_product_id'";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array( $result ) )
        {
            // 찢어서 등록한 order_products를 삭제한다.
            $query = "delete from order_products where order_seq = $data[seq]";
            mysql_query( $query, $connect );
            
            // 옵션 찢기 없이 새로 등록한다.
            $reg_ok = class_order_products::insert_product(
                            $data[shop_id], 
                            $data[shop_product_id], 
                            $data[options], 
                            '', 
                            $data[seq], 
                            $data[qty],
                            $data[memo_check]?3:0 );
        }
        
        return 0;
    }

    //////////////////////////////////////////////////////////////////////
    // 판매처별로 옵션정보를 배열로 변환
    function get_option_array($shop_id, $shop_options)
    {
        $arr_option = array();
        // 옵션이 빈 문자열이면 빈 배열 리턴 
        //if( !$shop_options )  return $arr_option;

        switch( $shop_id % 100 )
        {
            case 1: // 옥션            
            case 4: // 옥션S
                // 
                // 1. 한줄일때
                // <주문선택사항>오가닉다크초코+침받이모카/워머다크초코/1개/17,000원,오가닉다크초코+침받이모카/워머다크초코/1개/17,000원
                //
                // 2. 여러줄일때
                // <주문선택사항>탱크탑/D형/그레이XXL (6,000원) +800원 / 1개，탱크탑/D형/코발트블루XXL (6,000원) +800원 / 1개，탱크탑/D형/화이트XXL (6,000원) +800원 / 1개，탱크탑/D형/연핑크XXL (6,000원) +800원 / 1개
                //
                // <추가구성상품>커피n티 추가구매:담터 호두아몬드잣율무차 80T[1개] / 커피n티 추가구매:큐마트 종이컵 1000개[1개]
                //
                // <주문선택사항>은 타이틀이 없다. 
                // 본상품 없음일 경우, 다 추가상품으로...
                //
                
                // 추가구성상품 내용 $ac
                $arr1 = explode('<추가구성상품>', $shop_options);
                $ac = $arr1[1];
                
                // 주문선택사항 내용 $oc
                $arr2 = explode('<주문선택사항>', $arr1[0]);

                // 본상품 옵션을 자른다.
                if( $arr2[1] )
                {
                    $ptrn1 = '/(\/)([0-9]+)개(\/)/';  // 여러줄 형식
                    $ptrn2 = '/ ([0-9]+)개/';  // 한줄 형식

                    // 여러줄 형식
                    if( preg_match($ptrn1, $arr2[1]) )
                    {
                        $oc = preg_replace('/([0-9]+),([0-9]{3}원)/', '\1\2', $arr2[1]); // 가격 천단위 콤마 삭제

                        // 여러줄 형식에선 본상품 나눌필요 없음
                        $p = $oc;
                        // foreach( explode(',', $oc) as $p )
                        // {
                            preg_match( $ptrn1, $p, $matches );
                            $arr_option[] = array(
                                qty  => $matches[2],
                                opt  => preg_replace( $ptrn1, '\1\3', $p ),
                                type => 1
                            );
                        // }
                    }
                    // 한줄 형식
                    else if( preg_match($ptrn2, $arr2[1]) )
                    {
                        $oc = preg_replace('/(\+[0-9]+),([0-9]{3}원)/', '\1\2', $arr2[1]); // 가격 천단위 콤마 삭제
                        foreach( explode('，', $oc) as $p )
                        {
                            preg_match( $ptrn2, $p, $matches );
                            $arr_option[] = array(
                                qty  => $matches[1],
                                opt  => preg_replace( $ptrn2, '', $p ),
                                type => 1
                            );
                        }
                    }
                    // 이도저도 아니면
                    else
                    {
                        $arr_option[] = array(
                            qty  => 1,
                            opt  => $arr2[1],
                            type => 1
                        );
                    }
                }
                
                // 추가구성상품
                if( $ac )
                {
                    $ptrn = '/\[([0-9]+)개\]/';
                    foreach( explode(' / ', $ac) as $p )
                    {
                        preg_match($ptrn, $p, $matches);
                        $arr_option[] = array(
                            qty  => $matches[1],
                            opt  => preg_replace( $ptrn, '', $p ),
                            type => 2
                        );
                    }
                }
                break;
            case 2: // 지마켓
                $shop_options = substr( $shop_options, 0, strlen($shop_options)-1 );  // 맨 뒤의 , 제거
                $arr_option = explode( ",", $shop_options );
                break;
            case 6: // 인터파크
                $shop_options = explode( '<추가구성상품>', $shop_options );  // 추가구성상품만 옵션으로
                $arr_option = explode( ",", $shop_options[1] );
                break;
            case 50: // 11번가
                //*******************************************************************************
                // 11번가는 여러줄 발주 기준. 옵션 분리가 필요 없음.
                // 단, 본상품 옵션을 복수 선택사항으로 복수 상품으로 구성한 업체가 있음
                //*******************************************************************************
                $arr_option = explode( ",", $shop_options );
                break;
            case 72: // cafe24
                // 예) type=네이비바지set, 나시구매=네이비, 추가구매=선택안함
                $arr_option = explode( ", ", $shop_options );
                break;
        }
        return $arr_option;
    }

    //////////////////////////////////////////////////////////////////////
    // 옵션 찢기 미리보기 실행
    function get_preview()
    {
        global $connect, $shop_id, $shop_name, $shop_product_id, $shop_options, $base_options, $nobase;

        $val['list'] = array();
        
        // 옵션 문자열을 배열로 만든다.
        $arr_option = $this->get_option_array($shop_id, $shop_options);

        // 옵션 배열이 없으면, 기본상품 여부에 관계없이 하나의 상품으로 리턴
        if( !count($arr_option) )
        {
            $val['list'][] = array(
                shop_name  => $shop_name,
                product_id => $shop_product_id,
                options    => '',
                marked     => ($nobase==0)?1:2
            );
            $val['error'] = 0;
            return $val;
        }            

        // 기본 상품이 없으면
        if( $nobase )
        {
            switch( $shop_id % 100 )
            {
                case 1:  // 옥션
                case 4:  // 옥션S
                    foreach( $arr_option as $opt )
                    {
                        if( $opt[type] == 1 )
                            $opt_str = '<주문선택사항>' . $opt[opt];
                        else
                            $opt_str = '<추가구성상품>' . $opt[opt];
                            
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $opt_str,
                            marked     => 2
                        );
                    }
                    break;
                case 2:  // 지마켓
                    foreach( $arr_option as $opt )
                    {
                        // 옵션이 '선택안함'일 경우 넘어간다
                        $oArr = explode( ';', $opt );
                        if( $oArr[1] == '선택안함' )  continue;
                        
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $opt . ',',
                            marked     => 2
                        );
                    }
                    break;
                case 6:  // 인터파크
                    $top_opt = explode('<추가구성상품>', $shop_options);
                    foreach( $arr_option as $opt )
                    {
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $top_opt[0] . ( ($opt)?('<추가구성상품>' . $opt):'' ),
                            marked     => 2
                        );
                    }
                    break;
                case 50:  // 11번가
                    foreach( $arr_option as $opt )
                    {
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $opt,
                            marked     => 1
                        );
                    }
                    break;
                case 72:  // cafe24
                    foreach( $arr_option as $opt )
                    {
                        // 옵션이 '선택안함'일 경우 넘어간다
                        $oArr = explode( '=', $opt );
                        if( $oArr[1] == '선택안함' )  continue;
                        
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $opt,
                            marked     => 2
                        );
                    }
                    break;
            }
        }
        // 기본 상품이 있으면 
        else
        {
            // 본상품 title을 배열로 저장한다.
            $titles = array();
            $titles = json_decode( stripslashes($base_options) );

            $base_option = '';
            switch( $shop_id % 100 )
            {
                case 1:  // 옥션
                case 4:  // 옥션S
                    $base_flag = false;
                    foreach( $arr_option as $opt )
                    {
                        // 본상품
                        if( $opt[type] == 1 )
                        {
                            $val['list'][] = array(
                                shop_name  => $shop_name,
                                product_id => $shop_product_id,
                                options    => '<주문선택사항>' . $opt[opt],
                                marked     => 1
                            );
                            $base_flag = true;
                        }
                        // 추가상품
                        else
                        {
                            // 잘라낸 옵션에서 title 만 빼낸다.
                            $opt_title = explode( ':', $opt[opt] );
                            // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                            // 이미 위에서 본상품이 있는 경우에는, 무조건 추가상품으로 넘긴다.
                            if( false !== array_search( $opt_title[0], $titles ) && !$base_flag )
                            {
                                $base_option .= ($base_option?' / ':'<추가구성상품>') . $opt[opt];
                            }
                            else
                            {
                                $val['list'][] = array(
                                    shop_name  => $shop_name,
                                    product_id => $shop_product_id,
                                    options    => '<추가구성상품>' . $opt[opt],
                                    marked     => 2
                                );
                            }
                        }
                    }
                    // 본상품( $opt[type] == 1인 경우 )이 없을 경우, 추가
                    if( !$base_flag )
                    {
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $base_option,
                            marked     => 1
                        );
                    }
                    break;

                case 2:  // 지마켓
                    foreach( $arr_option as $opt )
                    {
                        // 잘라낸 옵션에서 title 만 빼낸다.
                        $opt_title = strtok( $opt, ';' );
                        // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                        if( false !== array_search( $opt_title, $titles ) )
                        {
                            $base_option .= $opt . ',';
                        }
                        else
                        {
                            // 옵션이 '선택안함'일 경우 넘어간다
                            $oArr = explode( ';', $opt );
                            if( $oArr[1] == '선택안함' )  continue;

                            $val['list'][] = array(
                                shop_name  => $shop_name,
                                product_id => $shop_product_id,
                                options    => $opt . ',',
                                marked     => 2
                            );
                        }
                    }
                    $val['list'][] = array(
                        shop_name  => $shop_name,
                        product_id => $shop_product_id,
                        options    => $base_option,
                        marked     => 1
                    );
                    break;
                    
                case 6:  // 인터파크
                    $opts_arr = explode('<추가구성상품>',$shop_options);

                    // 본상품
                    $base_opts = explode('<상품옵션>',$opts_arr[0]);
                    $base_arr = explode(',',$base_opts[1]);
                    foreach($base_arr as $base_opt)
                    {
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => ($base_opt)?('<상품옵션>' . $base_opt):'',
                            marked     => 1
                        );
                    }
                    
                    // 추가상품
                    if( $opts_arr[1] )
                    {
                        $added_arr = explode(',',$opts_arr[1]);
                        foreach($added_arr as $added_opt)
                        {
                            $val['list'][] = array(
                                shop_name  => $shop_name,
                                product_id => $shop_product_id,
                                options    => '<추가구성상품>' . $added_opt,
                                marked     => 2
                            );
                        }
                    }
                    break;

                case 50:  // 11번가
                    $base_flag = false;
                    foreach( $arr_option as $opt )
                    {
                        $val['list'][] = array(
                            shop_name  => $shop_name,
                            product_id => $shop_product_id,
                            options    => $opt,
                            marked     => 1
                        );
                    }
                    break;

                case 72:  // cafe24
                    foreach( $arr_option as $opt )
                    {
                        // 잘라낸 옵션에서 title 만 빼낸다.
                        $opt_title = strtok( $opt, '=' );
                        // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                        if( false !== array_search( $opt_title, $titles ) )
                        {
                            $base_option .= ( $base_option ? ", " : "" ) . $opt;
                        }
                        else
                        {
                            // 옵션이 '선택안함'일 경우 넘어간다
                            $oArr = explode( '=', $opt );
                            if( $oArr[1] == '선택안함' )  continue;

                            $val['list'][] = array(
                                shop_name  => $shop_name,
                                product_id => $shop_product_id,
                                options    => $opt,
                                marked     => 2
                            );
                        }
                    }
                    $val['list'][] = array(
                        shop_name  => $shop_name,
                        product_id => $shop_product_id,
                        options    => $base_option,
                        marked     => 1
                    );
                    break;
                    
            }
        }
        return $val;
    }

    //////////////////////////////////////////////////////////////////////
    // 옵션 찢기
    function divide_options($shop_id, $shop_product_id, $shop_options, $qty=1)
    {
        global $connect;

        // 등록된 기본옵션 타이틀을 찾는다.
        $query = "select * from option_match where shop_id=$shop_id and shop_product_id='$shop_product_id'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        
        $val = array();
        // 옵션이 없으면,
        if( !$shop_options )
        {
            $val[0]['option'] = '';
            $val[0]['marked'] = ($data[nobase]==0)?1:2;
            $val[0]['qty'] = $qty;
            return $val;
        }

        $arr_option = $this->get_option_array($shop_id, $shop_options);                

        // 기본 상품이 없으면
        if( $data[nobase] )
        {
            switch( $shop_id % 100 )
            {
                case 1:  // 옥션
                case 4:  // 옥션S
                    foreach( $arr_option as $opt )
                    {
                        // 본상품
                        if( $opt[type] == 1 )
                            $opt_str = '<주문선택사항>' . $opt[opt];
                        else
                            $opt_str = '<추가구성상품>' . $opt[opt];
                            
                        $val[] = array(
                            option => $opt_str,
                            marked => 2,
                            qty    => $opt[qty]
                        );
                    }
                    break;
                case 2:  // 지마켓
                    $shop_options = substr($shop_options, 0, strlen($shop_options)-1);  // 맨 마지막 , 제거
                    $arr_options = explode( ",", $shop_options );
                    $j = 0;
                    for( $i=0; $i<count($arr_options); $i++ )
                    {
                        // 옵션이 '선택안함'일 경우 넘어간다
                        $oArr = explode( ';', $arr_options[$i] );
                        if( $oArr[1] == '선택안함' )  continue;

                        $val[$j]['option'] = $arr_options[$i] . ',';
                        $val[$j]['marked'] = 2;  // 추가상품
                        $j++;
                    }
                    break;
                case 6:  // 인터파크
                    $top_opt = explode('<추가구성상품>', $shop_options);
                    $arr_options = explode(',', $top_opt[1]);
                    for( $i=0; $i<count($arr_options); $i++ )
                    {
                        $val[$i]['option'] = $top_opt[0] . ( ($arr_options[$i])?('<추가구성상품>' . $arr_options[$i]):'' );
                        $val[$i]['marked'] = 2;  // 추가상품
                    }
                    break;
                case 50:  // 11번가
                    foreach( $arr_option as $opt )
                    {
                        $val[$i]['option'] = $opt;
                        $val[$i]['marked'] = 1;
                        $val[$i]['qty'] = $qty;
                        $i++;
                    }
                    break;
                case 72:  // cafe24
                    $arr_options = explode( ", ", $shop_options );
                    $j = 0;
                    for( $i=0; $i<count($arr_options); $i++ )
                    {
                        // 옵션이 '선택안함'일 경우 넘어간다
                        $oArr = explode( '=', $arr_options[$i] );
                        if( $oArr[1] == '선택안함' )  continue;

                        $val[$j]['option'] = $arr_options[$i];
                        $val[$j]['marked'] = 2;  // 추가상품
                        $j++;
                    }
                    break;
            }
        }
        // 기본 상품이 있으면
        else
        {
            // 본상품 title을 배열로 저장한다.
            $titles = array();
            $titles = json_decode($data[option_title]);
            $base_option = '';
            switch( $shop_id % 100 )
            {
                case 1:  // 옥션
                case 4:  // 옥션S
                    $base_flag = false;
                    $base_qty = $qty; // 본상품의 기본 수량은 발주수량. 만일 추가상품에서 본상품이 발견되면, 그 수량으로 변경된다.
                    foreach( $arr_option as $opt )
                    {
                        // 본상품
                        if( $opt[type] == 1 )
                        {
                            $val[] = array(
                                option => '<주문선택사항>' . $opt[opt],
                                marked => 1,
                                qty    => $opt[qty]
                            );
                            $base_flag = true;
                        }
                        // 추가상품
                        else
                        {
                            // 잘라낸 옵션에서 title 만 빼낸다.
                            $opt_title = explode( ':', $opt[opt] );
                            // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                            // 이미 위에서 본상품이 있는 경우에는, 무조건 추가상품으로 넘긴다.
                            if( false !== array_search( $opt_title[0], $titles ) && !$base_flag )
                            {
                                $base_option .= ($base_option?' / ':'<추가구성상품>') . $opt[opt];
                                $base_qty = $opt[qty];
                            }
                            else
                            {
                                $val[] = array(
                                    option => '<추가구성상품>' . $opt[opt],
                                    marked => 2,
                                    qty    => $opt[qty]
                                );
                            }
                        }
                    }
                    // 본상품( $opt[type] == 1인 경우 )이 없을 경우, 추가
                    if( !$base_flag )
                    {
                        $val[] = array(
                            option => $base_option,
                            marked => 1,
                            qty    => $base_qty
                        );
                    }
                    break;
                case 2:  // 지마켓
                    // 마지막 , 를 지운다.
                    $shop_options = substr($shop_options, 0, strlen($shop_options)-1);
                    $arr_options = explode( ",", $shop_options );
                    $j = 0;
                    for( $i=0; $i<count($arr_options); $i++ )
                    {
                        // 잘라낸 옵션에서 title 만 빼낸다.
                        $opt_title = strtok( $arr_options[$i], ';' );
                        // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                        if( false !== array_search( $opt_title, $titles ) )
                            $base_option .= $arr_options[$i] . ',';
                        else
                        {
                            // 옵션이 '선택안함'일 경우 넘어간다
                            $oArr = explode( ';', $arr_options[$i] );
                            if( $oArr[1] == '선택안함' )  continue;

                            $val[$j]['option'] = $arr_options[$i] . ',';
                            $val[$j]['marked'] = 2;  // 추가상품
                            $j++;
                        }
                    }
                    $val[count($arr_options)]['option'] = $base_option;
                    $val[count($arr_options)]['marked'] = 1;  // 본상품
                    break;
                case 6:  // 인터파크
                    $opts_arr = explode('<추가구성상품>',$shop_options);

                    // 본상품
                    $base_opts = explode('<상품옵션>',$opts_arr[0]);
                    $base_arr = explode(',',$base_opts[1]);

                    for( $i=0; $i<count($base_arr); $i++ )
                    {
                        $val[$i]['option'] = ($base_arr[$i])?('<상품옵션>' . $base_arr[$i]):'';
                        $val[$i]['marked'] = 1;

                        // 수량 구하기
                        if( $base_arr[$i] )
                        {
                            preg_match ('|\/([0-9]+)개|U', $base_arr[$i], $matches);
                            $val[$i]['qty'] = $matches[1];
                        }
                        else
                            $val[$i]['qty'] = 0;
                    }
                    $last = $i;
                    
                    // 추가상품
                    if($opts_arr[1])
                    {
                        $added_arr = explode(',',$opts_arr[1]);
                        for( $i=0; $i<count($added_arr); $i++ )
                        {
                            $val[$i+$last]['option'] = '<추가구성상품>' . $added_arr[$i];
                            $val[$i+$last]['marked'] = 2;
                            $val[$i+$last]['qty'   ] = 1;
                        }
                    }
                    break;
                case 50:  // 11번가
                    $base_flag = false;
                    foreach( $arr_option as $opt )
                    {
                        $val[$i]['option'] = $opt;
                        $val[$i]['marked'] = 1;
                        $val[$i]['qty'] = $qty;
                        $i++;
                    }
                    break;
                case 72:  // cafe24
                    $arr_options = explode( ", ", $shop_options );
                    $j = 0;
                    for( $i=0; $i<count($arr_options); $i++ )
                    {
                        // 잘라낸 옵션에서 title 만 빼낸다.
                        $opt_title = strtok( $arr_options[$i], '=' );
                        // 본상품 title 에서, 잘라낸 옵션 title이 있는지 확인한다.
                        if( false !== array_search( $opt_title, $titles ) )
                            $base_option .= ( $base_option ? ", " : "" ) . $arr_options[$i];
                        else
                        {
                            // 옵션이 '선택안함'일 경우 넘어간다
                            $oArr = explode( '=', $arr_options[$i] );
                            if( $oArr[1] == '선택안함' )  continue;

                            $val[$j]['option'] = $arr_options[$i];
                            $val[$j]['marked'] = 2;  // 추가상품
                            $j++;
                        }
                    }
                    $val[count($arr_options)]['option'] = $base_option;
                    $val[count($arr_options)]['marked'] = 1;  // 본상품
                    break;
            }
        }
        return $val;
    }

    //////////////////////////////////////////////////////////////////////
    // 옵션 설정 정보를 가져온다.
    function get_option_set()
    {
        global $connect, $shop_id, $shop_product_id;

        $val = array();
        
        // 이미 등록되어있지 않으면 오류
        if( !$this->is_registered($shop_id, $shop_product_id) )
        {
            $val['error'] = 1;
            return $val;
        }

        $query = "select * from option_match where shop_id='$shop_id' and shop_product_id='$shop_product_id'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        
        $val['option_title'] = json_decode($data[option_title]);
        $val['nobase'] = $data[nobase];
        $val['error'] = 0;

        return $val;
    }

}
?>
