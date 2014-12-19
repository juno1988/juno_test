<?
class class_auto_unpack
{
    //합포불가 리스트
    private $autopack_disable = array();
    //합포설정 리스트
    private $autopack_list = array();
    //합포설정 리스트 단품
    private $autopack_list_single = array();

    
    //#####################################
    // 생성자
    //#####################################
    function __construct()
    {
        //합포불가 리스트를 불러온다.
        $this->autopack_disable = $this->get_autopack_disable();

        //합포설정 리스트를 불러온다.
        $this->autopack_list = $this->get_autopack_list();

        //합포설정 리스트 단품을 불러온다.
        $this->autopack_list_single = $this->get_autopack_list_single();
    }

    
    //#####################################
    // 합포불가 목록 불러오기
    //#####################################
    private function get_autopack_disable()
    {
        global $connect;

        //합포불가 리스트를 불러온다.
        $_autopack_disable = array();
        $query = "SELECT * FROM autopack_disable order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            if( $data['box_type1'] > '' && $data['box_type2'] > '' && $data['box_type1'] != $data['box_type2'] )
            {
                $_autopack_disable[] = array(
                    "seq"       => $data['seq']
                   ,"box_type1" => $data['box_type1']
                   ,"box_type2" => $data['box_type2']
                );
            }
        }
        return $_autopack_disable;
    }


    //#####################################
    // 합포 목록 불러오기
    //#####################################
    private function get_autopack_list()
    {
        global $connect;

        // 합포목록 리스트를 불러온다.
        $_autopack_list = array();        
        $query = "SELECT * FROM autopack_list order by seq";
        $result = mysql_query($query, $connect);            
        while($data = mysql_fetch_assoc($result))
        {
            if( $data['product_type'] > '' && $data['pack_max'] > 0 )
            {
                $_autopack_list[ $data['box_type'] ][] = array(
                    "product_type" => $data['product_type']
                   ,"pack_max"     => $data['pack_max']
                );
            }
        }
        return $_autopack_list;
    }


    //#####################################
    // 합포 목록 단품 불러오기
    //#####################################
    private function get_autopack_list_single()
    {
        global $connect;

        $_autopack_list_single = array();
        foreach( $this->autopack_list as $val )
        {
            if( count($val) > 1 )  continue;
            
            $_autopack_list_single[$val[0]['product_type']] = $val[0]['pack_max'];
        }
        return $_autopack_list_single;
    }


    //#####################################
    // 전체주문 처리
    //#####################################
    public function unpacking_2()
    {
        global $connect;

        // 대상 주문
        $query = "SELECT seq
                        ,pack
                        ,IF(pack>0 , pack, seq) pack_xx 
                    FROM orders
                   WHERE (status = 0 AND order_status = 50 and pack_lock = 0 )
                      OR (status = 1 AND order_cs NOT IN (1,2,3,4) and pack_lock = 0 )
                GROUP BY pack_xx "; 

        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $_query = "SELECT if(a.pack>0, a.pack, a.seq) pack_seq
                             ,a.seq          a_seq
                             ,b.seq          b_seq
                             ,b.product_id   b_product_id
                             ,b.qty          b_qty
                             ,d.product_type d_product_type
                             ,d.pack_min     d_pack_min
                         FROM orders a
                             ,order_products b
                             ,products c LEFT OUTER JOIN autopack_group d ON c.product_id = d.product_id 
                        WHERE a.seq = b.order_seq
                          AND b.product_id = c.product_id ";
            if($data[pack] > 0 )
                $_query .= "AND a.pack = $data[pack]" ;
            else
                $_query .= "AND a.seq = $data[seq]" ;

			$_query .= " AND b.is_gift = 0 " ;
            $_query .= " order by a.seq, b.seq" ;
            $_result = mysql_query($_query, $connect);
            
            // 합포 단위 상품목록
            $order_arr = array();
            while( $_data = mysql_fetch_assoc($_result) )
            {
                $order_arr[$_data['a_seq']][$_data['b_seq']] = array(
                    "product_id"   => $_data['b_product_id']
                   ,"org_qty"      => $_data['b_qty']
                   ,"qty"          => $_data['b_qty']
                   ,"product_type" => $_data['d_product_type']
                   ,"pack_min"     => $_data['d_pack_min']
                   ,"new_seq"      => 0
                   ,"new_prd_seq"  => 0
                );
            }
            $this->do_unpack( $order_arr );
        }
    }


    //#####################################
    // 합포불가 실행
    //#####################################
    private function do_unpack($order_arr)
    {
        // 합포불가 
        $disable_box = $this->check_autopack_disable($order_arr);
        
        // 합포분리 box 
        $unpack_box = array();

        // 합포불가 분리된 box에 대해서 각각 합포제외 처리
        foreach($disable_box as $box_val)
        {
            $temp_box = $this->get_unpack_box($box_val);
            $unpack_box = array_merge($unpack_box, $temp_box);
        }

        // 실제로 orders, order_products를 분리한다.
        if( count($unpack_box) > 1 )
            $this->exe_unpack( $unpack_box );
    }
    
    
    //#####################################
    // 합포불가 설정 처리
    //#####################################
    private function check_autopack_disable($order_arr)
    {
        // box 내 상품타입 리스트
        $type_arr = $this->get_product_type_list($order_arr);
        
        // 상품타입 1 이하면 1box로 리턴
        if( count($type_arr) <= 1 )  return array($order_arr);

        // 타입 분리
        $box_type_arr = array();
        $n = 0;
        while(1)
        {
            $first_prd = true;
            $next_box_type = array();
            foreach($type_arr as $t)
            {
                // 첫 상품타입은 0번 타입 박스에
                if( $first_prd )
                {
                    $first_prd = false;
                    $box_type_arr[$n][] = $t;
                    continue;
                }

                // 이전 타입 박스에 있는 상품과 합포불가 확인
                foreach($box_type_arr[$n] as $t_val)
                {
                    // 현재 상품타입 $t와, 이전 타입 박스의 상품 $t_val이 합포불가인지 확인
                    if( $this->check_autopack_disable_prd($t, $t_val) )
                    {
                        // 합포불가면 다음 타입 박스에...
                        $next_box_type[] = $t;
                        continue 2;
                    }
                }
                // 여기가 실행되면 합포불가 아니므로 이전 타입 박스에...
                $box_type_arr[$n][] = $t;
            }

            // 다음 타입 박스 있으면 다시 체크
            if( $next_box_type )
            {
                $type_arr = $next_box_type;
                $n++;
            }
            else
                break;
        }

        // 타입 박스 배열대로 주문을 합포분리한다.
        $disable_box = $this->exe_autopack_disable($order_arr, $box_type_arr);
        
        return $disable_box;
    }


    //#####################################
    // box 내 상품타입 리스트
    //#####################################
    private function get_product_type_list($order_arr)
    {
        $type_arr = array();
        foreach( $order_arr as $ord_v )
        {
            foreach( $ord_v as $prd_v )
            {
                // 상품타입이 있을 경우만 처리
                if( $prd_v['product_type'] > '' )
                    $type_arr[] = $prd_v['product_type'];
            }
        }
        return array_unique($type_arr);
    }


    //#####################################
    // 현재 상품타입 $t와, 이전 타입 박스의 상품 $t_val이 합포불가인지 확인
    //#####################################
    private function check_autopack_disable_prd($t, $t_val)
    {
        foreach($this->autopack_disable as $val)
        {
            if( $val['box_type1'] == $t && $val['box_type2'] == $t_val )
                return true;
            if( $val['box_type2'] == $t && $val['box_type1'] == $t_val )
                return true;
        }
        return false;
    }


    //#####################################
    // 타입 박스 배열대로 주문을 합포분리한다.
    //#####################################
    private function exe_autopack_disable($order_arr, $box_type_arr)
    {
        // 타입 박스가 1개면 그대로 리턴.
        if( count($box_type_arr) == 1 )
            return array($order_arr);
        
        $disable_box = array();
        
        $is_first = true;   
        foreach( $box_type_arr as $box_val )
        {
            // 첫번째 박스는 그대로 둔다.
            if( $is_first )
            {
                $is_first = false;
                continue;
            }
            
            $temp_box = array();
            
            // 주문에 대해서 타입 박스에 들어있는 타입을 분리한다.
            foreach( $order_arr as $order_key => $order_val )
            {
                // 현재 주문에 들어있는 상품들 중에서 분리할 타입 외에 다른 타입이 포함되어있는지 확인
                if( $this->check_unpack_only( $order_val, $box_val ) )
                {
                    // 다른 타입이 없으면 주문을 그대로 다음 박스로 옮긴다.
                    $temp_box[$order_key] = $order_val;
                    unset( $order_arr[$order_key] );
                }
                else
                {
                    // 다른 타입이 있으면, 각 상품에 대해 타입을 체크하여 다른 박스로 옮긴다.
                    foreach( $order_val as $prd_key => $prd_val )
                    {
                        // 현재 타입이 분리될 타입 아니면 넘어간다.
                        if( false === array_search( $prd_val[product_type], $box_val ) )  continue;
                        
                        $prd_val[new_seq] = 1;
                        $temp_box[$order_key][$prd_key] = $prd_val;
                        
                        unset( $order_arr[$order_key][$prd_key] );
                    }
                }
            }
            $disable_box[] = $temp_box;
        }
        $disable_box = array_merge(array($order_arr), $disable_box);
        return $disable_box;
    }


    //#####################################
    // 현재 주문에 들어있는 상품들 중에서 분리할 타입 외에 다른 타입이 포함되어있는지 확인
    //#####################################
    private function check_unpack_only( $order_val, $box_val )
    {
        foreach( $order_val as $val )
        {
            if( $val[product_type] > '' )
                if( false === array_search( $val[product_type], $box_val ) )
                    return false;
        }
        return true;
    }


    //#####################################
    // 박스를 max 수량으로 나누기
    //#####################################
    private function get_unpack_box($box_val)
    {
        $result_box = array();

        // full box 나누기
        $rest_box = array();
        foreach( $box_val as $order_val )
        {
            foreach( $this->autopack_list as $pack_set )
            {
                $full_box = array();
                $this->check_full_box( $pack_set, $order_val, $full_box, $rest_box );
                $result_box = array_merge($result_box, $full_box);
            }
        }

        // 남은 주문의 총 박스수량 구하기
        $total_rest_box_qty = $this->get_box_cnt($rest_box);
        
        // 박스 수량이 하나면 그대로
        if( $total_rest_box_qty == 1 )
            $result_box = array_merge($result_box, $rest_box);
        
        return $result_box;
    }


    //#####################################
    // full box 나누기
    //#####################################
    private function check_full_box( $pack_set, $order_val, &$full_box, &$rest_box )
    {
        
    }
    
    
    //#####################################
    // 총 박스수량 구하기
    //#####################################
    private function get_box_cnt($box_val)
    {
        $box_qty = array();
        foreach( $box_val as $order_val )
        {
            foreach( $order_val as $prd_val )
            {
                if( $prd_val[product_type] > '' )
                    $box_qty[$prd_val[product_type]] += $prd_val[qty];
            }
        }

        // 설정
        foreach( $this->autopack_list as $pack_set )
        {
            // 한 설정으로 여러박스 가능
            while( 1 )
            {
                // 각 설정의 상품 수량 검사
                foreach( $pack_set as $prd_set )
                {
                    // 해당 상품 수량 확인
                    if( !($box_qty[$prd_set[product_type]] >= $prd_set[pack_max]) )
                        continue 3;  // 다음 설정으로
                }

                // 각 설정의 상품 수량제외
                foreach( $pack_set as $prd_set )
                    $box_qty[$prd_set[product_type]] -= $prd_set[pack_max];

                $box_cnt++;
            }
        }

        // 나머지 박스 확인
        foreach( $box_qty as $_qty )
        {
            if( $_qty > 0 )
            {
                $box_cnt++;
                break;
            }
        }
        return $box_cnt;
    }


    //#####################################
    // 실제로 orders, order_products를 분리
    //#####################################
    private function exe_unpack($unpack_box)
    {
    }


}
?>
