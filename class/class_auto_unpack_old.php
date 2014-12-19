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
                   ,"p_seq"        => ""
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

        // 합포불가 대상
        $disable_arr = array();
        // 합포불가 아님
        $normal_arr = array();

        // 합포불가 설정 처리
        $this->autopack_disable_order($disable_arr, $normal_arr);

        // 최종 합포제외 대상 주문
        $unpack_list = array();

        // 합포목록 설정 처리
        $unpack_list = $this->autopack_list_order($disable_arr, $normal_arr);

        // 기본포장단위로 재합포
        $unpack_list_final = $this->repack_check($unpack_list);
        
        // 합포제외 실행
        $this->do_unpack( $unpack_list_final );
    }


    //#####################################
    // 합포불가 설정 처리
    //#####################################
    private function autopack_disable_order(&$disable_arr, &$normal_arr)
    {
        global $connect;

        //************************************************
        // 합포불가 상품 체크
        //************************************************
        $query = "SELECT seq
                        ,pack
                        ,IF(pack>0 , pack, seq) pack_xx 
                    FROM orders
                   WHERE (status = 0 AND order_status = 50 and pack_lock = 0 )
                      OR (status = 9 AND order_cs NOT IN (1,2,3,4) and pack_lock = 0 )
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
                $order_arr[] = array(
                    "pack"         => $_data['pack_seq']
                   ,"seq"          => $_data['a_seq']
                   ,"p_seq"        => $_data['b_seq']
                   ,"product_id"   => $_data['b_product_id']
                   ,"org_qty"      => $_data['b_qty']
                   ,"qty"          => $_data['b_qty']
                   ,"product_type" => $_data['d_product_type']
                   ,"pack_min"     => $_data['d_pack_min']
                );
            }
            $result_order = $this->check_autopack_disable($order_arr);
            if( count($result_order) > 1 )
                // 합포불가
                $disable_arr = array_merge($disable_arr, $result_order);
            else
                // 합포불가아님
                $normal_arr = array_merge($normal_arr, $result_order);
        }
    }
    
    
    //#####################################
    // 개별 주문 합포불가 설정 처리
    //#####################################
    private function check_autopack_disable($order_arr)
    {
        // 결과주문
        $result_order_arr = array();
        
        $check_order = $order_arr;
        
        // 하나의 합포 주문에 대해 100번이상 합포불가로 찢어지는 경우 없음. 무한루프를 막기위해 100번으로 설정
        for($i=0; $i<1; $i++)
        {
            $new_check_order = array();
            
            // 합포불가 설정
            foreach($this->autopack_disable as $_unpack_val)
            {
                $fine_prd = false;
                
                // 합포목록 : 합포불가 첫번째 상품 포함 여부.
                // 첫번째 상품이 접수상태인지 확인.
                foreach( $check_order as $_order_val )
                {
                    if( $_order_val['product_type'] == $_unpack_val['box_type1'] )
                    {
                        $find_prd = true;
                        break;
                    }
                }
                
                // 일치상품 없으면 다음 불가 설정으로..
                if( !$find_prd )  continue;
                
                // 합포목록 : 합포불가 두번째 상품 포함 여부
                foreach( $check_order as $key => $_order_val )
                {
                    if( $_order_val['product_type'] == $_unpack_val['box_type2'] )
                    {
                        $new_check_order[] = $_order_val;

                        unset($check_order[$key]);
                    }
                }
            }

            // 결과
            if( $check_order )
                $result_order_arr[] = $check_order;
            if( $new_check_order )
                $result_order_arr[] = $new_check_order;

/*
            // 재확인.
            if( $new_check_order )
                $check_order = $new_check_order;
            else
                break;
*/
        }
        return $result_order_arr;
    }


    //#####################################
    // 합포목록 설정 처리
    //#####################################
    private function autopack_list_order($disable_arr, $normal_arr)
    {
        // 최종 합포 제외 배열
        $unpack_final = array();

        foreach( $disable_arr as $order_val )
        {
            $result_arr = $this->check_autopack_list($order_val);
            $unpack_final = array_merge($unpack_final, $result_arr);
        }

        // 합포불가로 찢어지는 주문이 아닌경우, 합포설정에서도 찢어지지 않으면 최종 합포분리 배열에서 뺀다.
        foreach( $normal_arr as $order_val )
        {
            $result_arr = $this->check_autopack_list($order_val);
            if( count($result_arr) > 1 )
                $unpack_final = array_merge($unpack_final, $result_arr);
        }
        return $unpack_final;
    }
    
    
    //#####################################
    // 개별 주문 합포목록 설정 처리
    //#####################################
    private function check_autopack_list($order_val)
    {
        // 합포분리 실행
        $rest_check = false;
        $result_total = $this->unpack_autopack_list($order_val, $rest_check);

        // 마지막 목록이 한박스를 차지하는 목록이 아닐 경우, 각 상품목록의 합으로 두박스 이상으로 분리 가능할수 있으므로 이를 처리한다.
        if( $rest_check )
        {
            $cnt = count($result_total);
            $result_arr = $this->unpack_autopack_list_rest( $result_total[ $cnt - 1 ] );

            unset($result_total[ $cnt - 1 ]);
            $result_total = array_merge($result_total, $result_arr);
        }
        return $result_total;
    }

    
    //#####################################
    // 개별 주문 합포목록 설정 처리
    //#####################################
    private function unpack_autopack_list($order_val, &$rest_check)
    {
        // 처리결과
        $result_arr = array();
        // 합포설정목록
        foreach( $this->autopack_list as $pack_val )
        {
            for($j=0; $j<10; $j++)
            {
                // 수량 그대로 합포분리 확인
                $p_type_arr = $this->get_product_type_arr($order_val);
                if( count($p_type_arr) > 1 )
                {
                    $temp_pack_val = $pack_val;
                    $temp_order_val = $p_type_arr;
                    
                    $re = $this->unpack_order_arr($temp_pack_val, $temp_order_val);
                    if( $re )  
                    {
                        if( count($p_type_arr) == count($re) )
                        {
                            $exact_unpack_order = $this->exact_unpack($order_val);
                            $result_arr = array_merge($result_arr, $exact_unpack_order);
                            unset($order_val);
                            continue 2;
                        }
                    }                    
                    else
                    {
                        continue 2;
                    }
                }
                
                $re = $this->unpack_order_arr($pack_val, $order_val);
                if( $re )  
                {
                    // 남은 상품 목록에 합포설정 상품 없으면 분리취소.
                    if( $this->is_pack_prd($order_val) )
                        $result_arr[] = $re;
                    else
                    {
                        $order_val = $old_order_val;
                        continue 2;
                    }
                }                    
                else
                    continue 2;
            }
        }
        
        // 맨 마지막 남은 상품들
        if( $order_val )
        {
            $result_arr[] = $order_val;
            $rest_check = true;
        }
        else
            $rest_check = false;

        return $result_arr;
    }
    

    //#####################################
    // 합포 상품 분리 배열처리
    //#####################################
    private function unpack_order_arr(&$pack_val, &$order_val)
    {
        $_o_val_check = $order_val;
        // 합포설정 내 상품목록
        foreach( $pack_val as $pp_key => $pp_val )
        {
            // 주문 내 상품목록
            foreach( $_o_val_check as $_prd_key => $_prd_val )
            {
                if( $pp_val['product_type'] == $_prd_val['product_type'] && $pp_val['pack_max'] <= $_prd_val['qty'] )
                {
                    $pack_val[$pp_key]['p_seq'] = $_prd_val['p_seq'];
                    
                    // 찾은 상품은 다음 검색에서 제외
                    unset( $_o_val_check[$_prd_key] );
                    
                    // 다음 찾을 상품으로
                    continue 2;
                }
            }
            // 여기가 실행되면 상품을 못찾은 경우. 다음 설정으로 넘어감
            return false;
        }

        //-----------------------------------------------------------------------------------
        // 여기가 실행되면 상품을 모두 찾은 경우. 설정만큼 주문에서 상품을 제외한다.
        //-----------------------------------------------------------------------------------

        // 합포분리 대상
        $_unpack_arr = array();

        // 기존 합포 목록
        $old_order_val = $order_val;

        // 합포설정 내 상품목록
        foreach( $pack_val as $pp_val )
        {
            // 주문 내 상품목록
            foreach( $order_val as $_prd_key => $_prd_val )
            {
                if( $_prd_val['p_seq'] == $pp_val['p_seq'] && $_prd_val['product_type'] == $pp_val['product_type'] )
                {
                    $_unpack_arr[] = array(
                        "pack"         => $_prd_val['pack']
                       ,"seq"          => $_prd_val['seq']
                       ,"p_seq"        => $_prd_val['p_seq']
                       ,"product_id"   => $_prd_val['product_id']
                       ,"org_qty"      => $_prd_val['org_qty']
                       ,"qty"          => $pp_val['pack_max']
                       ,"product_type" => $_prd_val['product_type']
                       ,"pack_min"     => $_prd_val['pack_min']
                    );

                    // 수량이 같으면 상품목록 삭제
                    if( $pp_val['pack_max'] == $_prd_val['qty'] )
                        unset( $order_val[ $_prd_key ] );
                    // 수량이 다르면 수량 차감
                    else
                        $order_val[ $_prd_key ]['qty'] -= $pp_val['pack_max'];

                    // 다음 설정 상품
                    continue 2;
                }
            }
        }
        
        return $_unpack_arr;
    }


    //#####################################
    // 개별 주문 합포목록 설정 처리 - 나머지 재확인
    //#####################################
    private function unpack_autopack_list_rest($order_val)
    {
        global $connect;

        $_sum_arr = array();
        foreach( $order_val as $_v )
        {
            $_sum_arr[$_v['product_type']]['qty'] += $_v['qty'];
            $_sum_arr[$_v['product_type']]['pack_min'] = $_v['pack_min'];
        }
        
        foreach( $_sum_arr as $_k => $_v )
        {
            $check_sum_arr[] = array(
                "pack"         => 1
               ,"seq"          => 1
               ,"p_seq"        => 1
               ,"product_id"   => 1
               ,"org_qty"      => $_v['qty']
               ,"qty"          => $_v['qty']
               ,"product_type" => $_k
               ,"pack_min"     => $_v['pack_min']
            );
        }

        // 합포분리 실행
        $rest_check = false;
        $result_total = $this->unpack_autopack_list($check_sum_arr, $rest_check);

        // 상품별로 개수 구한다.
        $prd_arr = array();
        foreach( $result_total as $res_key => $res_val )
        {
            foreach( $res_val as $res_prd )
            {
                $prd_arr[$res_prd['product_type']][] = array(
                    "box" => $res_key
                   ,"qty" => $res_prd['qty']
                );
            }
        }

        $box = array();
        
        //************************************************
        // 상품 타입별로 분리하여 수량 맞추기
        //************************************************

        // 목록에서 수량 일치를 찾는다
        foreach( $prd_arr as $p_key => $p_val )
        {
            foreach( $p_val as $_k => $_v )
            {
                foreach( $order_val as $ord_key => $ord_val )
                {
                    if( $p_key == $ord_val['product_type'] && $_v['qty'] == $ord_val['qty'] )
                    {
                        unset($order_val[$ord_key]);
                        unset($prd_arr[$p_key][$_k]);
                        $box[$_v['box']][] = $ord_val;
                        continue 2;
                    }
                }
            }
        }
                
        // 목록에서 수량이 큰 경우를 찾는다.
        foreach( $prd_arr as $p_key => $p_val )
        {
            foreach( $p_val as $_k => $_v )
            {
                foreach( $order_val as $ord_key => $ord_val )
                {
                    if( $p_key == $ord_val['product_type'] && $_v['qty'] < $ord_val['qty'] )
                    {
                        //나머지 수량
                        $order_val[$ord_key]['qty'] = $ord_val['qty'] - $_v['qty'];
                        unset($prd_arr[$p_key][$_k]);
                        
                        // 박스 수량
                        $ord_val['qty'] = $_v['qty'];
                        $box[$_v['box']][] = $ord_val;
                        continue 2;
                    }
                }
            }            
        }

        // 목록에서 나머지 찾는다.
        foreach( $prd_arr as $p_key => $p_val )
        {
            foreach( $p_val as $_k => $_v )
            {
                $qty_sum = 0;
                foreach( $order_val as $ord_key => $ord_val )
                {
                    if( $p_key == $ord_val['product_type'] )
                    {
                        $qty_sum += $ord_val['qty'];
                        
                        // 작으면 다음
                        if( $_v['qty'] >= $qty_sum )
                        {
                            unset($order_val[$ord_key]);
                            unset($prd_arr[$p_key][$_k]);
                            $box[$_v['box']][] = $ord_val;

                            // 일치하면 다음 찾을 상품
                            if( $_v['qty'] == $qty_sum )
                                continue 2;
                        }
                        else 
                        {
                            //나머지 수량
                            $order_val[$ord_key]['qty'] = $qty_sum - $_v['qty'];
                            unset($prd_arr[$p_key][$_k]);
                            
                            // 박스 수량
                            $ord_val['qty'] = $ord_val['qty'] - $order_val[$ord_key]['qty'];
                            $box[$_v['box']][] = $ord_val;
                            continue 2;
                        }
                    }
                }
            }            
        }
    	return $box;
    }
    

    //#####################################
    // 기본포장단위로 재합포
    //#####################################
    private function repack_check($unpack_list)
    {
        // 단일상품 합포
        $single_arr = array();
        foreach($unpack_list as $unpack_key => $unpack_val)
        {
            // 상품이 하나인 경우만 처리
            if( count($unpack_val) > 1 )  continue;

            $un_prd = $unpack_val[0];

            // pack_min이 0보다 큰 경우만 유효
            if( $un_prd['pack_min'] > 0 )
            {
                $_pack_max = $this->autopack_list_single[$un_prd['product_type']];
                
                // pack_max가 pack_min의 배수면 넘어간다.
                if( $_pack_max % $un_prd['pack_min'] == 0 )  continue;

                $single_arr[$un_prd['p_seq']][$un_prd['product_id']][] = array(
                    "unpack_no"    => $unpack_key
                   ,"product_type" => $un_prd['product_type']
                   ,"pack_max"     => $_pack_max
                   ,"pack_min"     => $un_prd['pack_min']
                   ,"qty"          => $un_prd['qty']
                );
            }
        }

        // seq
        foreach($single_arr as $s_key => $s_val)
        {
            // product_id
            $pack_full_arr = array();
            foreach($s_val as $p_key => $p_val)
            {
                // 합포가 하나면 넘어간다.
                if( count($p_val) == 1 )  continue;
                
                $pack_max = $p_val[0]['pack_max'];
                $pack_min = $p_val[0]['pack_min'];
                
                // 나머지 상품 수량
                $rest_qty = 0;
                foreach( $p_val as $_v )
                {
                    if( $_v['pack_max'] == $_v['qty'] )
                        $pack_full_arr[] = $_v['unpack_no'];
                    else
                    {
                        $rest_qty = $_v['qty'];
                        $rest_pack_no = $_v['unpack_no'];
                    }
                }
                
                // 나머지 상품 수량이 0 이면 넘어간다.
                if( !$rest_qty )  continue;
                
                // 풀박스 개수
                $full_cnt = count($pack_full_arr);
                
                // 합포 쪼개기 최대수량
                $max_box = ceil( $pack_max / ($pack_max - $pack_min) ) - 1;
                
                for($i=1; $i<=$max_box; $i++)
                {
                    if( $full_cnt < $i )  continue;
                    
                    if( ($pack_max * $i + $rest_qty) % $pack_min == 0 )
                    {
                        for($j=$full_cnt; $j>$full_cnt-$i; $j--)
                            $unpack_list[ $pack_full_arr[$j-1] ][0]['qty'] = $pack_min;

                        $unpack_list[ $rest_pack_no ][0]['qty'] = $pack_min;
                    }
                }
            }
        }
        return $unpack_list;
    }
    
    
    //#####################################
    // 합포제외 실행
    //#####################################
    private function do_unpack( $unpack_list )
    {
        global $connect;

        // 합포 목록의 전체 seq 목록
        $seq_all = array();
        $unpack_order = array();
        $unpack_order_pack = array();
        $n = 0;
        foreach( $unpack_list as $list_val )
        {
            // 박스번호
            $n++;
            // 합포 내 상품
            foreach( $list_val as $list_p_val )
            {
                $seq_all[] = $list_p_val['seq'];
                $unpack_order[$n][$list_p_val['seq']][$list_p_val['p_seq']] = $list_p_val['qty'];
                
                // 해당 박스 번호의 합포번호를 별도로 가져간다.
                $unpack_order_pack[$n] = $list_p_val['pack'];
            }
        }
        $seq_list = implode(",", array_unique($seq_all));

        // 분리대상 주문목록
        $org_order = array();
        $query = "select if(a.pack>0,a.pack,a.seq) pack_seq
                        ,a.seq a_seq
                        ,b.seq b_seq
                        ,b.qty b_qty
                    from orders a
                        ,order_products b
                   where a.seq = b.order_seq
                     and a.seq in ($seq_list)
                   order by pack_seq, a.seq, b.seq ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $org_order[$data['pack_seq']][$data['a_seq']][$data['b_seq']] = $data['b_qty'];
        }

        // 합포 목록
        foreach( $unpack_order as $_pack_key => $pack_val )
        {
            // 박스번호로부터 합포번호 구하기
            $pack_key = $unpack_order_pack[$_pack_key];

            // 정보가 동일하면 넘어간다.
            if( $pack_val == $org_order[$pack_key] )
            {
                // 남은 주문의 합포번호 확인
                $org_order_seq_arr = array();
                foreach( $org_order[$pack_key] as $_order_key => $_order_val )
                    $org_order_seq_arr[] = $_order_key;
                
                if( count($org_order_seq_arr) == 1 )
                    $org_order_new_pack = 0;
                else
                    $org_order_new_pack = min($org_order_seq_arr);

                $query = "update orders set pack=$org_order_new_pack where seq in (" . implode(",",$org_order_seq_arr) . ")";
                mysql_query($query, $connect);

                unset( $org_order[$pack_key] );
                continue;
            }
            
            // 합포 내 주문
            $new_pack_seq_arr = array();
            foreach( $pack_val as $order_key => $order_val )
            {
                // 정보가 동일하면 합포정보 변경
                if( $order_val == $org_order[$pack_key][$order_key] )
                {
                    $new_pack_seq_arr[] = $order_key;
                    unset( $org_order[$pack_key][$order_key] );
                    continue;
                }

                // 주문 내 상품
                $first_prd = true;
                foreach( $order_val as $prd_key => $prd_val )
                {
                    // 새 주문
                    if( $first_prd )
                    {
                        // 원주문 상품수
                        $org_total_qty = 0;
                        foreach( $org_order[$pack_key][$order_key] as $_qty )
                            $org_total_qty += $_qty;
                            
                        // 합포분리 상품수
                        $pack_total_qty = 0;
                        foreach( $order_val as $_qty )
                            $pack_total_qty += $_qty;
                            
                        // 새 orders 생성
                        $new_seq = $this->make_new_order($order_key, $org_total_qty, $pack_total_qty);
                        $new_pack_seq_arr[] = $new_seq;
                        
                        $first_prd = false;
                    }
                    
                    // 정보가 동일하면 seq 변경
                    if( $prd_val == $org_order[$pack_key][$order_key][$prd_key] )
                    {
                        $query = "update order_products set order_seq = $new_seq where seq=$prd_key";
                        mysql_query( $query, $connect );
                        
                        unset( $org_order[$pack_key][$order_key][$prd_key] );
                        continue;
                    }

                    // 정보가 다르면 새 order_products 생성
                    $this->make_new_order_products($new_seq, $prd_key, $prd_val);
                    $org_order[$pack_key][$order_key][$prd_key] -= $prd_val;
                }
            }
            
            // 새 합포번호
            if( count($new_pack_seq_arr) == 1 )
                $new_pack_no = 0;
            else
                $new_pack_no = min($new_pack_seq_arr);
                
            $query = "update orders set pack = $new_pack_no where seq in (" . implode(",",$new_pack_seq_arr) . ")";
            mysql_query( $query, $connect );
        }
    }


    //#####################################
    // 새 주문 만들기
    //#####################################
    private function make_new_order( $seq, $org_qty, $qty )
    {
        global $connect;

        $field_arr = array();
        $data_arr = array();
        
        // 원주문 수량
        $query = "select * from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        // 변경수량 구하기
        if( $data[qty] <= 2 )
        {
            $new_qty = 1;
            $old_qty = 1;
        }
        else
        {
            $new_qty = round($data[qty] * $qty / $org_qty);
            if( $new_qty == $data[qty] )  $new_qty--;

            $old_qty = $data[qty] - $new_qty;
        }

        // 정산금액
        list($new_amount, $old_amount) = $this->divide_price($data['amount'], $new_qty, $old_qty);
        list($new_supply_price, $old_supply_price) = $this->divide_price($data['supply_price'], $new_qty, $old_qty);

        list($new_code11, $old_code11) = $this->divide_price($data['code11'], $new_qty, $old_qty, $data['shop_id'], 11);
        list($new_code12, $old_code12) = $this->divide_price($data['code12'], $new_qty, $old_qty, $data['shop_id'], 12);
        list($new_code13, $old_code13) = $this->divide_price($data['code13'], $new_qty, $old_qty, $data['shop_id'], 13);
        list($new_code14, $old_code14) = $this->divide_price($data['code14'], $new_qty, $old_qty, $data['shop_id'], 14);
        list($new_code15, $old_code15) = $this->divide_price($data['code15'], $new_qty, $old_qty, $data['shop_id'], 15);
        list($new_code16, $old_code16) = $this->divide_price($data['code16'], $new_qty, $old_qty, $data['shop_id'], 16);
        list($new_code17, $old_code17) = $this->divide_price($data['code17'], $new_qty, $old_qty, $data['shop_id'], 17);
        list($new_code18, $old_code18) = $this->divide_price($data['code18'], $new_qty, $old_qty, $data['shop_id'], 18);
        list($new_code19, $old_code19) = $this->divide_price($data['code19'], $new_qty, $old_qty, $data['shop_id'], 19);
        list($new_code20, $old_code20) = $this->divide_price($data['code20'], $new_qty, $old_qty, $data['shop_id'], 20);
        list($new_code31, $old_code31) = $this->divide_price($data['code31'], $new_qty, $old_qty, $data['shop_id'], 31);
        list($new_code32, $old_code32) = $this->divide_price($data['code32'], $new_qty, $old_qty, $data['shop_id'], 32);
        list($new_code33, $old_code33) = $this->divide_price($data['code33'], $new_qty, $old_qty, $data['shop_id'], 33);
        list($new_code34, $old_code34) = $this->divide_price($data['code34'], $new_qty, $old_qty, $data['shop_id'], 34);
        list($new_code35, $old_code35) = $this->divide_price($data['code35'], $new_qty, $old_qty, $data['shop_id'], 35);
        list($new_code36, $old_code36) = $this->divide_price($data['code36'], $new_qty, $old_qty, $data['shop_id'], 36);
        list($new_code37, $old_code37) = $this->divide_price($data['code37'], $new_qty, $old_qty, $data['shop_id'], 37);
        list($new_code38, $old_code38) = $this->divide_price($data['code38'], $new_qty, $old_qty, $data['shop_id'], 38);
        list($new_code39, $old_code39) = $this->divide_price($data['code39'], $new_qty, $old_qty, $data['shop_id'], 39);
        list($new_code40, $old_code40) = $this->divide_price($data['code40'], $new_qty, $old_qty, $data['shop_id'], 40);

        $query = "show columns from orders";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data['Field'] == "seq" )  continue;
            
            $field_arr[] = $data['Field'];
            
            if( $data['Field'] == "qty" )
                $data_arr[] = $new_qty;
            else if( $data['Field'] == "amount" )
                $data_arr[] = $new_amount;
            else if( $data['Field'] == "supply_price" )
                $data_arr[] = $new_supply_price;
            else if( $data['Field'] == "code11" )
                $data_arr[] = $new_code11;
            else if( $data['Field'] == "code12" )
                $data_arr[] = $new_code12;
            else if( $data['Field'] == "code13" )
                $data_arr[] = $new_code13;
            else if( $data['Field'] == "code14" )
                $data_arr[] = $new_code14;
            else if( $data['Field'] == "code15" )
                $data_arr[] = $new_code15;
            else if( $data['Field'] == "code16" )
                $data_arr[] = $new_code16;
            else if( $data['Field'] == "code17" )
                $data_arr[] = $new_code17;
            else if( $data['Field'] == "code18" )
                $data_arr[] = $new_code18;
            else if( $data['Field'] == "code19" )
                $data_arr[] = $new_code19;
            else if( $data['Field'] == "code20" )
                $data_arr[] = $new_code20;
            else if( $data['Field'] == "code31" )
                $data_arr[] = $new_code31;
            else if( $data['Field'] == "code32" )
                $data_arr[] = $new_code32;
            else if( $data['Field'] == "code33" )
                $data_arr[] = $new_code33;
            else if( $data['Field'] == "code34" )
                $data_arr[] = $new_code34;
            else if( $data['Field'] == "code35" )
                $data_arr[] = $new_code35;
            else if( $data['Field'] == "code36" )
                $data_arr[] = $new_code36;
            else if( $data['Field'] == "code37" )
                $data_arr[] = $new_code37;
            else if( $data['Field'] == "code38" )
                $data_arr[] = $new_code38;
            else if( $data['Field'] == "code39" )
                $data_arr[] = $new_code39;
            else if( $data['Field'] == "code40" )
                $data_arr[] = $new_code40;
            else
                $data_arr[] = $data['Field'];
        }

        $field_list = implode(",", $field_arr);
        $data_list = implode(",", $data_arr);
        
        $query = "insert orders ($field_list) select $data_list from orders where seq = $seq";
        mysql_query($query, $connect);
        
        $query = "select seq from orders order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        // 기존주문 수량 변경
        $query = "update orders 
                     set qty = $old_qty
                        ,amount = $old_amount
                        ,supply_price = $new_supply_price
                        ,code11 = $old_code11
                        ,code12 = $old_code12
                        ,code13 = $old_code13
                        ,code14 = $old_code14
                        ,code15 = $old_code15
                        ,code16 = $old_code16
                        ,code17 = $old_code17
                        ,code18 = $old_code18
                        ,code19 = $old_code19
                        ,code20 = $old_code20
                        ,code31 = $old_code31
                        ,code32 = $old_code32
                        ,code33 = $old_code33
                        ,code34 = $old_code34
                        ,code35 = $old_code35
                        ,code36 = $old_code36
                        ,code37 = $old_code37
                        ,code38 = $old_code38
                        ,code39 = $old_code39
                        ,code40 = $old_code40
                   where seq = $seq";
        mysql_query($query, $connect);
        
        return $data[seq];
    }


    //#####################################
    // 새 order_products 만들기
    //#####################################
    private function make_new_order_products( $seq, $p_seq, $qty )
    {
        global $connect;

        $field_arr = array();
        $data_arr = array();
        
        // 기존수량
        $query = "select qty from order_products where seq = $p_seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $old_qty = $data[qty] - $qty;
        
        $query = "show columns from order_products";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data['Field'] == "seq" )  continue;
            
            $field_arr[] = $data['Field'];

            if( $data['Field'] == "order_seq" )
                $data_arr[] = $seq;
            else if( $data['Field'] == "qty" )
                $data_arr[] = $qty;
            else
                $data_arr[] = $data['Field'];
        }
            
        $field_list = implode(",", $field_arr);
        $data_list = implode(",", $data_arr);
        
        $query = "insert order_products ($field_list) select $data_list from order_products where seq = $p_seq";
        mysql_query($query, $connect);
        
        // 기존주문 수량변경
        $query = "update order_products set qty = $old_qty where seq = $p_seq";
        mysql_query($query, $connect);
    }


    //#####################################
    // 주문 목록에 합포설정 상품 확인
    //#####################################
    private function is_pack_prd($order_val)
    {
        $is_pack_prd = false;
        foreach( $order_val as $_v )
        {
            if( $_v['product_type'] > '' )
            {
                $is_pack_prd = true;
                break;
            }
        }
        return $is_pack_prd;        
    }


    //#####################################
    // 정산금액 나누기
    //#####################################
    private function divide_price($org_price, $new_qty, $old_qty, $shop_id=0, $code_no=0)
    {
        $org_equal = false;
        
        if( $shop_id > 0 && $code_no > 0 )
        {
            if( _DOMAIN_ == 'first' )
            {
                if( ( $shop_id == 10042 && ($code_no == 11 || $code_no == 12) )
                 || ( $shop_id == 10026 && ($code_no == 12) )
                 || ( $shop_id == 10007 && ($code_no == 11) )
                 || ( $shop_id == 10043 && ($code_no == 11) )
                 || ( $shop_id == 10009 && ($code_no == 11 || $code_no == 12) )
                 || ( $shop_id == 10057 && ($code_no == 11 || $code_no == 12) )
                 || ( $shop_id == 10015 && ($code_no == 11 || $code_no == 12) )
                )
                    $org_equal = true;
            }
        }

        if( $org_equal )
        {
            $new_price = $org_price;
            $old_price = $org_price;
        }
        else
        {
            $new_price = floor($org_price * $new_qty / ($new_qty + $old_qty) );
            $old_price = $org_price - $new_price;
        }
        return array($new_price, $old_price);
    }


    //#####################################
    // product_type 있는 상품
    //#####################################
    private function get_product_type_arr($order_val)
    {
        $arr = array();
        foreach($order_val as $_val)
        {
            if( $_val['product_type'] > '' )
                $arr[] = $_val;
        }
        return $arr;
    }


    //#####################################
    // 상품 그대로 찢기
    //#####################################
    private function exact_unpack($order_val)
    {
        $product_type_arr = array();
        $no_type_arr = array();
        foreach($order_val as $_val)
        {
            if( $_val['product_type'] > '' )
                $product_type_arr[] = array($_val);
            else
                $no_type_arr[] = $_val;
        }
        
        foreach( $no_type_arr as $_val )
        {
            // 같은 주문
            foreach( $product_type_arr as $p_type_key => $p_type_val )
            {
                if( $p_type_val[0]['seq'] == $_val['seq'] )
                {
                    $product_type_arr[$p_type_key][] = $_val;
                    continue 2;
                }
            }

            // 없으면 첫번째 주문
            $product_type_arr[0][] = $_val;
        }
        
        return $product_type_arr;
    }
}
?>
