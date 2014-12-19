<?

class class_pack
{
    // 매칭에서 넘어오면서 합포 자료 생성한다.
    function make_pack()
    {
        global $connect;
        
        // 합포 자료
        $recv_info = array();
        
        // 합포 검사할 자료를 구한다.
        $query = "select * from orders where status=0 and order_status=40";
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_array($result) )
        {
            // 상품리스트를 만든다.
            $item = array(
                seq  => $data[seq],
                add  => str_replace(' ', '', $data[address] ),
                name => $data[recv_name],
                tel1 => $data[recv_tel],
                tel2 => $data[recv_mobile],
                pack => 0
            );
            $recv_info[] = $item;
        }
        
        for($i=0; $i<count($recv_info); $i++)
        {
            // 이미 합포된 주문이면 넘어간다
            if( $recv_info[$i][pack] )  continue;

            for($j=$i+1; $j<count($recv_info); $j++)
            {
                // 이미 합포된 주문이면 넘어간다
                if( $recv_info[$j][pack] )  continue;
                
                // 합포
                if( ($recv_info[$i][add] == $recv_info[$j][add]) &&
                    ($recv_info[$i][name] == $recv_info[$j][name]) &&
                    ($recv_info[$i][tel1] == $recv_info[$j][tel1]) &&
                    ($recv_info[$i][tel2] == $recv_info[$j][tel2]) )
                {
                    $recv_info[$i][pack] = $recv_info[$i][seq];
                    $recv_info[$j][pack] = $recv_info[$i][seq];
                }
                // 검증
                else if( (($recv_info[$i][add] == $recv_info[$j][add]) && ($recv_info[$i][name] == $recv_info[$j][name])) ||
                         (($recv_info[$i][add] == $recv_info[$j][add]) && ($recv_info[$i][tel1] == $recv_info[$j][tel1])) ||
                         (($recv_info[$i][add] == $recv_info[$j][add]) && ($recv_info[$i][tel2] == $recv_info[$j][tel2])) ||
                         (($recv_info[$i][name] == $recv_info[$j][name]) && ($recv_info[$i][tel1] == $recv_info[$j][tel1])) ||
                         (($recv_info[$i][name] == $recv_info[$j][name]) && ($recv_info[$i][tel2] == $recv_info[$j][tel2])) )
                {
                }
            }
        }
    }

    // seq를 검사하여 이미 등록되어있는지 확인한다.
    function is_registered($seq)
    {
        global $connect;
        
        $query = "select * from order_products where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows($result) > 0 )
            return true;
        else
            return false;
    }
        
    // 상품을 추가한다.
    function insert_product($shop_id, $shop_product_id, $shop_options, $order_seq, $qty, $marked)
    {
        global $connect;

        $shop_options = iconv('utf-8','cp949',$shop_options);
        $query = "insert order_products 
                     set shop_id         = '$shop_id',
                         shop_product_id = '$shop_product_id', 
                         shop_options    = '$shop_options', 
                         order_seq       = '$order_seq', 
                         qty             = '$qty',
                         marked          = '$marked'";
        return mysql_query( $query, $connect );
    }

    //////////////////////////////////////////////////////////
    // 매칭 취소한다.
    function cancel_match()
    {
        global $connect, $seq, $saved;

        $query = "select * from order_products where seq = $seq";
        $result = mysql_query($query, $connect);
        // 해당 주문 정보가 삭제됐음.
        if( mysql_num_rows($result) == 0 )  return 1;
        
        $data = mysql_fetch_array($result);
        // 해당 주문이 매칭 취소되었음
        if( $data[status] == 0 )  return 2;

        $data_order = class_order::get_order($data[order_seq]);
        // 해당 주문이 발주 완료했음.
        if( $data_order[status] != 0 )  return 3;

        // 매칭 정보를 저장했을 경우
        if( $saved )
        {
            // 매칭 키 값
            $m_id = $data[shop_id];
            $m_pd = $data[shop_product_id];
            $m_op = $data[shop_options];
            
            // 매칭 정보를 삭제한다.
            $query = "delete from code_match 
                       where shop_id     = '$m_id' and 
                             shop_code   = '$m_pd' and 
                             shop_option = '$m_op'";
            mysql_query($query, $connect);
            
            // order_products에서 매칭된 주문의 order_seq를 구한다.
            $seq_arr = array();
            $query = "select order_seq 
                        from order_products
                       where shop_id         = '$m_id' and 
                             shop_product_id = '$m_pd' and 
                             shop_options    = '$m_op'
                    group by order_seq";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_array($result) )
            {
                array_push( $seq_arr, $data[order_seq] );
            }
            $seq_str = join(',',$seq_arr);
            
            // order_products에서 해당 주문을 삭제한다.
            $query = "delete from order_products where order_seq in ($seq_str)";
            mysql_query($query, $connect);
            
            // order_products에 새로 등록한다.
            $query = "select * from orders where seq in ($seq_str)";
            $result = mysql_query( $query, $connect );
        }
        // 매칭 정보를 저장하지 않았을 경우
        else
        {
            // order_products에서 해당 주문을 삭제한다.
            $query = "delete from order_products where seq = $seq";
            mysql_query($query, $connect);
            
            // order_products에 새로 등록한다.
            $query = "select * from orders where seq = $data[order_seq]";
            $result = mysql_query( $query, $connect );
        }

        // $result 를 매칭정보 저장했을때와, 안했을때 공통으로 사용
        while( $data = mysql_fetch_array( $result ) )
        {
            // 옵션 문자셋 변환
            $options = iconv('cp949','utf-8',$data[options]);
            $reg_ok = false;
            // 메모선택 안하고, 교환C/S 없고, 추가옵션주문설정 있으면 찢는다.
            if( !$data[memo_check] && $data[order_cs] == 0 && 
                class_option_match::is_registered($data[shop_id], $data[shop_product_id]) )
            {
                // 찢어진 옵션 배열을 얻는다.
                $arr_option = class_option_match::divide_options(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $options,
                                    $data[qty] );
                // 찢어진 옵션대로 상품 등록한다.
                foreach( $arr_option as $opt )
                {
                    // 옥션이면 수량을 별도 계산
                    if( $data[shop_id] % 100 == 1 && $opt[qty] != 0 )
                        $qty = $opt[qty];
                    else if( $data[shop_id] % 100 == 50 )
                        $qty = $opt[qty];
                    else
                        $qty = $data[qty];

                    $reg_ok = class_order_products::insert_product(
                                    $data[shop_id], 
                                    $data[shop_product_id], 
                                    $opt[option], 
                                    $data[seq], 
                                    $qty,
                                    $opt[marked] );
                    if( !$reg_ok )  break;
                }
            }
            // 메모선택 했거나, 교환C/S 있거나, 추가옵션주문설정 없으면, 옵션을 찢지 않고 주문 하나로 등록
            else
            {
                $reg_ok = class_order_products::insert_product(
                                $data[shop_id], 
                                $data[shop_product_id], 
                                $options, 
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
        
        return 0;
    }
}
?>
