<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require_once "class_product.php";
require_once "class_lock.php";
require_once "class_ui.php";
require_once "class_DK00.php";
require_once "class_order.php";
require_once "class_shop.php";

////////////////////////////////
// class name: class_DR00
//
class class_DR00 extends class_top {

  var $max_product = 0;
  var $min_product = 0;             // date: 2007-7-11 추가
  var $m_order = 1;                 // 순서
  var $m_star_refund_count = 0;     // 순서
  var $m_gift_count = 0;            // 순서

    function save_reserve_order()
    {
        global $connect,$value;
        $query = "update ez_config set pre_deliv='$value'";
        $result = mysql_query( $query, $connect );
        echo $query;
    }
    
    function load_reserve_order()
    {
        global $connect;
        $query = "select pre_deliv from ez_config";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );   
    }

  function DR01()
  {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $confirm;

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
        if (!$end_date)   $end_date = date('Y-m-d');

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // 배송 가능 이력 및 확인
    // 2012.3.7 - jkryu
    function DR03()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $confirm, $type, $idx;
        
        if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
        if (!$end_date)   $end_date = date('Y-m-d');
        
        if ( $type != "last" )
            $result_idx = $this->get_print_history();
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function set_deliv_all()
    {
        global $idx, $type, $value,$is_deliv_all,$worker, $connect;   
        
        echo "$idx/$type/$value /$is_deliv_all ";        
        ////debug("vv: $value ");
        
        // type이 last인 경우 print_enable의 값을 변경
        // 그 외는 print_enable_history의 값을 변경
        list( $order_seq, $product_seq) = split("\|", $value );
        
        // type last는 수정가능함을 의미..
        if ( $type == "last")
        {
            // 배송 가능은 합포 전체에 대해서...
            // 배송 불가는 합포가 자동으로 분리됨.
            // 2012.4.16 - jkryu
            $query    = "select pack,is_pack from print_enable where order_seq=$order_seq";
            //debug( "q2:" . $query );
            
            $result   = mysql_query( $query, $connect );
            $data     = mysql_fetch_assoc( $result );
            $_pack    = $data[pack];
            $_is_pack = $data[is_pack];
            
            // seqs를 구한다.
            $i = 0;
            if ( $data[is_pack] == 1 )
            {
                $query  = "select order_seq from print_enable where pack=$_pack";
                //debug("q2-1: $query");
                $result = mysql_query( $query, $connect );
                $_seqs  = "";
                while ( $data = mysql_fetch_assoc( $result ) )
                {
                    $_seqs .= $_seqs ? ",":"";
                    $_seqs .= $data[order_seq];   
                    $i++;
                }
            }
            else
            {
                $_seqs = $order_seq;
            }
            
            // 전체 출력 가능..
            if ( $is_deliv_all == 1 )
            {
                // 묶인 상품이 1개 이상일 경우.
                if ( $i > 1 )
                {
                    // orders의 합포를 다시 묶어준다.
                    $query = "update orders set pack=$_pack where seq in ( $_seqs )";    
                    mysql_query( $query, $connect );
                }
                
                // 전 주문이 is_deliv_all이 되어야 함. - 2012.4.5 - jk
                //$query = "update print_enable set is_deliv_all = $is_deliv_all, is_work=1
                //           where order_seq=$order_seq and product_seq = $product_seq";
                $query = "update print_enable set is_deliv_all = $is_deliv_all, is_work=1
                           where order_seq in ( $_seqs )";
                                       
                //debug( "q6: $query ");
                mysql_query( $query, $connect );
                
                
            }
            // 개별 출력 불가.
            else
            {
                // is_pack == 1 일경우 orders의 pack변경 , print_enable의 pack변경
                if ( $_is_pack == 1 )
                {
                    $row = 0;
                    $query  = "select order_seq 
                                 from print_enable 
                                where pack=$_pack 
                                  and order_seq <> $order_seq 
                                  and is_deliv_all = 1";
                    //debug( "get pack: $query ");                                      
                    $result = mysql_query( $query, $connect );
                    $data   = mysql_fetch_assoc( $result );
                    $row    = mysql_num_rows( $result );
                    
                    if ( $_pack == $order_seq )
                    {
                        
                        // print_enable의 pack 변경
                        $query = "update print_enable set pack=$data[order_seq] where order_seq in ($_seqs)";
                        //debug( "pp up: $query");
                        mysql_query( $query, $connect );
                        
                        //
                        // orders의 pack 변경   
                        // 2개일 경우 print_enable의 pack번호를 변경해준다. 
                        if ( $row == 1 )
                        {
                            $query = "update orders set pack=0 where seq in ($_seqs)";
                        }
                        else
                            $query = "update orders set pack=$data[order_seq] where seq in ($_seqs) and pack <> 0";
                         
                        //debug( "x qu: $query ");
                        mysql_query( $query, $connect );
                    }
                    else
                    {
                        // 남아있는 pack의 개수 확인.
                        
                        if ( $row == 1 )
                        {
                            $query = "update orders set pack=0 where seq in ($_seqs)";
                            mysql_query( $query, $connect );
                        }
                    }
                    
                    // 합포를 제외 한다.   
                    $query = "update orders set pack=0 where seq=$order_seq";
                    //debug( "q1:" . $query );
                    mysql_query( $query, $connect );
                }
                
                $query = "update print_enable set is_deliv_all = $is_deliv_all, is_work=1
                           where order_seq = $order_seq";
                                       
                //debug( "q4: $query ");
                mysql_query( $query, $connect );
            }
            
            // 이력 저장
            $is_deliv_all_from = 0;
            if ( $is_deliv_all == 0 )
                $is_deliv_all_from = 1;
        
            // print_enable_history_work에 idx가 0으로 저장...
            $query = "insert print_enable_history_work 
                         set idx                 = 0
                             ,order_seq          = $order_seq
                             ,product_seq        = $product_seq
                             ,work_time          = Now()
                             ,is_delive_all_from = $is_deliv_all_from
                             ,is_delive_all_to   = $is_deliv_all
                             ,worker             = '$worker'";
            ////debug( $query );                             
            mysql_query( $query, $connect );
        }
        else
        {
            
        }
        
    }
    
    
    
    // 이력 조회..
    function search_history()
    {
        global $connect, $idx, $deliv_all, $type, $product_name;
        
        echo "<table class='tableGridA' style='width:99%'>
                <thead>
                <tr>
                    <th width=30>배송</th>
                    <th width=30>작업</th>
                    <th width=50>관리번호</th>
                    <th width=50>합포번호</th>
                    <th width=150>주문번호</th>
                    <th width=60>판매처</th>
                    <th width=80>발주일</th>
                    <th width=60>수취인</th>
                    <th width=60>상품코드</th>
                    <th>상품명</th>
                    <th>옵션</th>
                    <th>개수</th>
                    <th>재고</th>
                    <th>판매가</th>
                    <th>출력</th>
                </tr>
                </thead>";
        
        // get_list part        
        
        $query = $this->get_query();

        $result = mysql_query( $query, $connect );
                
        $pack   = 0;
        $arr_orders   = array();
        $arr_products = array();
        $arr_shop     = array();
        
        // checkbox 출력 여부 체크..
        $check_display = 0;
        
        // seq 별로 나눌 수 있다.
        $prev_seq    = 0;
        $prev_pack   = 0;
        $current_seq = 0;
        $is_td_disp  = 1;    // td값 출력여부 저장 1: 출력 0: 미출력
        $row_cnt     = 0;    // 출력된 row의 값
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( !array_key_exists( $data[shop_id], $arr_shop ) )
            {
                $o = $this->get_shop_info( $data[shop_id] );
                $arr_shop[$data[shop_id]] = $o;
            }
            else
            {
                $o = $arr_shop[$data[shop_id]];
            }
            
            // print_r ( $o );
            if ( $data[pack] != $pack )
            {
                $i++;   
                $pack = $data[pack];
                $check_display = 1;
            }
            
            if ( $i % 2 == 0 )
                $_c = "even";
            else
                $_c = "odd";
            
            echo "<tr class='$_c' onMouseOver='javascript:swap_class(this)'>
                    ";
            
            // 하나의 주문에 하나의 배송 체크만..
            if ( $check_display == 1 )  
            {
                if ( $prev_seq != $data[order_seq] )
                {
                    $product_cnt = $this->get_row_cnt( $data[order_seq] );
                    
                    echo "<td rowspan=" . $product_cnt .">";
                    echo "<input type=checkbox value='$data[order_seq]|$data[product_seq]' onClick='javascript:_sel(this)'";
                        
                    if ( $data[is_deliv_all] == 1 )
                        echo " checked ";                  
                        
                    echo ">";
                }
            }
            else
            {
                echo "&nbsp;";   
            }  
            
            $str_stock = "<span class=red>X</span>";
            
            if ( $data[is_stock] == 1 )
                $str_stock = "O";
            
            if ( $data[is_work] )
            {
                $str_work = $this->get_work_history( $idx, $data[order_seq], $data[product_seq], $type);
                
                $work = "<a title='$str_work' href=#><img src=images/sync_dn.png width=15></a>";
            }
            else
                $work = "&nbsp;";
            
            $shop_name = class_shop::get_shop_name( $data[shop_id] );
            $shop_name = $shop_name ? $shop_name : "&nbsp;";
            
            echo "</td>
                    ";
                    
            // seq는 row span당 1번 
            if ( $prev_seq != $data[order_seq] )  
            {
                $_pack = "";
                if ( $prev_pack != $data[pack] )
                {
                    $_pack = $data[pack];
                }
                
                echo "<td rowspan=$product_cnt>$work</td>";
                echo "<td rowspan=$product_cnt>$data[seq]</td>
                      <td rowspan=$product_cnt>$_pack</td>
                      <td rowspan=$product_cnt>$data[order_id]</td>
                      <td rowspan=$product_cnt>$shop_name</td>
                      <td rowspan=$product_cnt>$data[collect_date]</td>
                      <td rowspan=$product_cnt>$data[recv_name]</td>
                ";
            }
            
            if ( $data[is_deliv_all] == 1 )
                $str_print = "<img src=images/printer.png>";
            else
                $str_print = "&nbsp;";
                
            echo"
                    <td>$data[product_id]</td>
                    <td>$data[product_name]</td>
                    <td>$data[options]</td>
                    <td>$data[qty]</td>
                    <td>$str_stock</td>";
                    
            if ( $prev_seq != $data[order_seq] )  
            {
                $_amount = "";
                if ( $prev_pack != $data[pack] )
                {
                    $_amount = number_format($data[amount]);
                }
                
                echo "<td rowspan=$product_cnt>" . $_amount . "</td>";
                echo "<td rowspan=$product_cnt>$str_print</td>";
                $prev_seq = $data[order_seq];
                $prev_pack = $data[pack];
            }
                    
            echo "</tr>";
            
            if ( $prev_seq == $data[order_seq] )
            {
                $is_td_disp = 0;   
            }                  
        }
        echo "</table>";
    }
    
    function get_row_cnt( $seq )
    {
        global $connect;
        
        $query = "Select count(*) cnt from order_products where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[cnt];   
    }
    
    //
    function get_work_history( $idx, $order_seq, $product_seq, $type)
    {
        global $connect;
        $idx = $idx ? $idx : 0;
        
        $query = "select * from print_enable_history_work 
                  where idx         = $idx 
                    and order_seq   = $order_seq 
                    and product_seq = $product_seq 
                  order by work_time desc";
        
        $result = mysql_query( $query , $connect );
        $str = " - ";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // $str .= $str ? " - " : "";
            
            $ss  = "<span class=red>재고부족</span>";
            if ( $data[is_delive_all_to] == 1 )
                $ss  = "<span class=blue>&nbsp;</span>";
            
            $str .= sprintf( "%18s %18s %18s - ", $data[work_time], $ss, $data[worker] );
        }   
        
        return $str;
        
    }
    
    function get_shop_info( $shop_id )
    {
        global $connect;
        
        $query = "select * from shopinfo where id='$shop_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[name];
    }

    // 
    // 이력 조회
    // total_amount 금액을 계산한다. - 
    // 
    // 0: 일반 조회, 1: 다운로드 조회
    function get_query( $is_download = 0)
    {
        global $connect,$idx, $deliv_all, $type, $product_name, $options, $price;
        
        if ( $type == "last" )
        {
            $tbl = "print_enable";
        }
        else
        {
            $tbl = "print_enable_history";   
        }
        
        // product_name이 있는경우
        $seqs  = "";
        $packs = "";    // 합포일 경우엔 pack에 pack번호, 단품인 경우 pack에 seq가 들어간다.
        if ( $product_name || $options )
        {
            $query = "select pack, order_seq 
                        from " . $tbl. " a, products b ";

            if ( $type == "last" )
            {
                $query .= " where a.product_id = b.product_id";
            }
            else
            {
                $query .= " where a.idx=$idx
                         and a.product_id = b.product_id";
            }
                       
            if ( $product_name )
                $query .= " and b.name like '%$product_name%'";   
            
            if ( $options )
                $query .= " and b.options like '%$options%'";   
                
            //debug( "find pid: " . $query );
                         
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                if ( $data[order_seq] )
                {
                    $seqs  .= $seqs ? "," : "";
                    $seqs  .= $data[order_seq];
                }
                
                if ( $data[pack] )
                {
                    $packs .= $packs ? "," : "";
                    $packs .= $data[pack];
                }
            }            
        }
        
        //debug( "price: $price / is_download: $is_download ");
        // amount계산
        if ( $price || !$is_download )
        {
            $this->calc_amount( $type, $idx, $deliv_all, $seqs, $packs );
        }
        
        // list 조회
        $query = "select a.*
                         ,b.collect_date,b.order_id,b.seq,b.pack order_pack,b.recv_name,b.order_date
                         , b.order_time, b.shop_id, d.name product_name,d.options
                   from  " . $tbl . " a, orders b, order_products c, products d";
                   
        if ( $type == "last" )
        {
            $query .= " where a.order_seq   = b.seq";
        }
        else
        {
            $query .= " where a.idx=$idx
                     and a.order_seq   = b.seq";
        }
            
        $query .= " and a.product_seq = c.seq
                    and c.product_id  = d.product_id";
               
        // 배송가능 상태                   
        if ( $deliv_all != -1)
            $query .= " and a.is_deliv_all=$deliv_all";

        if ( $seqs )
        {
            $query .= " and a.order_seq in ( $seqs )";   
        }

        if ( $packs )
        {
            $query .= " and a.pack in ( $packs )";   
        }

        if ( $price )
        {
            $query .= " and a.amount >= $price ";   
        }

        $query .= " order by a.pack,b.order_date, order_time";

        //debug( $query );

        return $query;               
    }

    //
    // 총 판매 금액..계산
    // 
    function calc_amount( $type, $idx, $deliv_all, $seqs, $packs )
    {
        global $connect;
        
        if ( $type == "last" )
        {
            $tbl = "print_enable";
        }
        else
        {
            $tbl = "print_enable_history";   
        }
        
        $query = "select sum(b.amount) s_amount , a.pack 
                    from " . $tbl . " a, orders b ";
        
        if ( $type == "last" )
        {
            $query .= " where a.order_seq = b.seq";
        }
        else
        {
            $query .= " where a.idx        = $idx
                          and a.order_seq = b.seq";
        }
        
         // 배송가능 상태                   
        if ( $deliv_all != -1)
            $query .= " and a.is_deliv_all=$deliv_all";

        if ( $seqs )
        {
            $query .= " and a.order_seq in ( $seqs )";   
        }

        if ( $packs )
        {
            $query .= " and a.pack in ( $packs )";   
        }

        $query .= " group by pack";

        // debug( "calc_amount: $query ");

        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->update_amount_price( $data[s_amount], $data[pack] , $tbl, $idx );
        }
    }
    
    //
    // 총 금액 update
    //
    function update_amount_price( $s_amount, $pack , $tbl, $idx )
    {
        global $connect;
        
        $query = "update $tbl set amount=$s_amount where pack=$pack";
        if ( $idx )
            $query .= " and idx=$idx";
        
        //debug( $query );
        
        mysql_query( $query, $connect );
    }
    
    function get_products_info( $product_id )
    {
        global $connect;
        $query = "select * from products where product_id=$product_id";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data;   
    }


    function get_order_info( $seq )
    {
        global $connect;
        $query = "select * from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data;   
    }

    function a_get_print_history()
    {
        global $connect;
        
        $query = "select * from print_history order by idx desc limit 30";
        
        $result = mysql_query( $query, $connect );
        $arr_result['str'] = $str;
        $arr_result['cnt'] = 0;
        $arr_status = array(1=>"생성작업", 2=>"다운로드", 3=>"송장입력", 4=>"초기화");
        $arr_part_deliv = array( 0 => "없음", 1=>"사용" );
        $arr_deliv_priority = array( "delay" => "배송지연", "pack" => "합포"  );
        $arr_use_shop_priority = array( 1 => "적용", 0 => ""  );
        $arr_reserve_order     = array( 1 => "적용", 0 => ""  );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[shop_name]         = class_C::get_shop_name( $data["shop_id"] ? $data[shop_id] : "" );
            $data[supply_id]         = $this->get_supply_name2( $data["supply_id"] );
            $data[status_log]        = $this->get_detail_status( $data[idx] );
            $data[status]            = $arr_status[ $data[status] ];
            $data[part_deliv]        = $data[part_deliv] ? $data[part_deliv] : "";
            $data[deliv_priority]    = $arr_deliv_priority[ $data[deliv_priority] ];
            $data[use_shop_priority] = $arr_use_shop_priority[$data[use_shop_priority]];
            $data[reserve_order    ] = $arr_use_shop_priority[$data[reserve_order]];
            $data[pack_deliv_cnt]    = $data[pack_deliv_cnt] ? $data[pack_deliv_cnt] : 0;
            $data[single_deliv_cnt]  = $data[single_deliv_cnt] ? $data[single_deliv_cnt] : 0;
            $data[shop_group]        = $this->get_shop_group_name( $data[shop_group] );
            $data[supply_group]      = $this->get_supply_group_name( $data[supply_group] );
            
            $arr_result['list'][] = $data;
            $arr_result['cnt']++; 
        }
        
        echo json_encode( $arr_result );   
    }

    function get_print_history()
    {
        global $connect;
        
        $query = "select * from print_history order by crdate desc limit 20";   
        $result = mysql_query( $query, $connect );
        
        return $result;
    }

    // 하단에 리스트 출력
    function DR02()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $confirm;
        global $trans_who, $status,$supply_code,$s_group_id,$shop_id,$group_id,$start_date,$end_date;
        
        if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
        if (!$end_date)   $end_date = date('Y-m-d');
        
        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
        
        $query = "select a.collect_date,a.collect_time,a.shop_id,a.order_id,a.order_name
                    from orders a, order_products b
                   where a.seq = b.order_seq";
        
        if ( $shop_id )
            $query .= " and a.shop_id in ( $shop_id ) ";
        
        if ( $supply_code )
            $query .= " and b.supply_id in ( $supply_code )";

        if ( $trans_who )
            $query .= " and a.trans_who = '$trans_who' ";
            
        if ( $status == 0)
            $query .= " and a.status not in (0,7,8)";        
        else
            $query .= " and a.status = $status ";
        
        if ( $start_date )
            $query .= " and a.collect_date >= '$start_date' ";        
            
        if ( $end_date )
            $query .= " and a.collect_date <= '$end_date' ";
            
        // 전체취소는 출력하지 않는다.
        $query .= " and a.order_cs not in ( 1,3 )";
        
        $result = mysql_query( $query, $connect );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function wrap_debug( $str )
    {
        debug( $str );
    }

    // 재고 확인..
    // 2009.12.2 - jk
    // rule 정리
    // 파일생성을 누르면 자동으로 작업 2번이 작동 함.
    // Rule은 심플할 수록 좋음..무조건 마지막에 다운 받은 자료를 업로드 해야 함. - 재고확인 작업 -> 다운로드
    // 
    // 2011.5.25
    //    reserve_order 선주문 예약배송 추가..
    //      이전 주문 중 미배송 건이 있으면 재고가 있어도 배송 나가지 않는다.
    function make_printable($use_app=false, &$ret_msg='')
    {   
        //****************************************************
        // 비주얼베이직 client에서 호출
        // echo 사용시 반드시 if( !$use_app ) 사용
        //****************************************************
        global $connect,$start_date,$end_date,$qty_part_deliv,$enable_part_deliv,$qty_part_deliv,$deliv_priority,$use_shop_priority,$shop_id,$group_id,$supply_code,$s_group_id,$reserve_order;

        // 보류제외
        $except_hold = $this->is_except_hold();

        // 판매처 우선순위 작업 자동 실행 2011.4.1 - jk
        $obj = new class_DK00();
        $obj->apply_rule();
        
        if( !$use_app )
        {
            echo( " start make printable / $qty_part_deliv" );
            
            echo "print_enable";
            echo "<script language='javascript'>parent.show_waiting()</script>";
            flush();
        }
        
        // 초기화 작업 중...
        $job_idx = $this->init_printable($use_app);
                
        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
        
        // //debug("start make printable / $supply_code");
        
        
        // Lock Start
        $obj_lock = new class_lock(301);
        if( !$obj_lock->set_start(&$msg) )
        {
            if( !$use_app )
            {
                echo "<script language='javascript'>parent.hide_waiting()</script>";
                echo "<script language='javascript'>parent._init()</script>";
                flush();
                $this->jsAlert($msg);
                return;
            }
            else
            {
                $ret_msg = $msg;
                return false;
            }
        }
        
        /////////////////////////////////////////
        // date: 2010.5.1 - jk
        // cs 우선순위기능 추가 : cs에서 우선순위 설정하면 배송시 선 출고됨.
        // cs_priority필드 추가 됨
        //
        // Step 1. CS 우선순위가 있는 주문 조회
        $query = "select seq,pack,pack_lock
                    ,if(pack<>null||pack<>0,1, 0 ) is_pack 
                    , sum(qty) sqty
                    from orders 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and status=1
                     and order_cs not in (1,3)
                     and (cs_priority <> 0 or priority <> 0) ";  // priority의 default가 null -> 0 으로 변경 2011.6.13

        // 선주문 배송예약일 경우엔 판매처 정보가 중요하지 않음.                     
        if ( $shop_id && $reserve_order != 1)
            $query .= " and shop_id in ($shop_id)";
    
        // alice의 경우 보류가 출력안됨 2011.6.13 - jk, 이대리 요청사항
        if ( $except_hold )
            $query .= " and hold=0 ";
                              
        // cs_priority는 cs주문이 먼저 배송, priority는 판매처 주문은 그 다음..
        // cs우선순위는 무조건 적용.
        $query .= " group by xx order by cs_priority desc ";            

        // 판매처 우선순위는 $use_shop_priority가 없는 경우는 실행하지 않는다.
        // 2011.4.6 - jk
        if ( $use_shop_priority )
            $query .= ",priority desc";            
        
        // 작업 조건에 따른 작업 우선순위
        // 우선순위가 있는 주문은 신규 주문이 먼저 돌아버림..오류..
        // 2011.6.14 - jk
        if ( $deliv_priority == "pack" )
            $query .= " , is_pack desc,seq ";
        else if ( $deliv_priority == "total_qty" )        // 상품수량+배송지연, 수량 많은것 순, 수량 많은 것 중에는 오래된 것 순.
            $query .= " , sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " , collect_date, sqty desc , collect_time ";
        else
            $query .= " ,seq ";   
        
        $this->wrap_debug($query);
        
        // 상품 수량 우선 배송..2011.6.13
        // print_enable의 total_qty 필드에 총 상품 수를 입력한다.
        // print_enable_sort 테이블 생성..
        // pack, total_qty 의 정보 저장..
        // //debug( "print_enable total_qty check -> " . $print_enable );        
        $result = mysql_query( $query, $connect );
        
        $_rows = mysql_num_rows( $result );
        if ( $_rows > 0 )
        {   
            while ( $data = mysql_fetch_assoc( $result ) )
            { 
                // 재고 확인..
                // make_printable에 값을 넣은 후 재고가 있는경우 상태가 2임.
                if ( $data[pack] )
                    $this->check_stock_pack( $data[pack] );
                else
                    $this->check_stock( $data[seq], $data[pack] );
                
                // 배송 가능 여부 체크
                $this->check_deliv( $data[seq], $data[pack] );
                
                // 부분 배송 체크
                // pack_lock 은 합포 금지.. 2011.4.6 jkryu
                if ( $qty_part_deliv  > 0 && $data[pack_lock] == 0)
                {
                    // $this->check_part_deliv( $data[seq], $data[pack], $qty_part_deliv );   
                    $this->check_part_deliv2( $data[seq], $data[pack], $qty_part_deliv, $use_app );   
                }
                
                if ( !$use_app && $i%2 == 0 )
                {
                    echo "<script language='javascript'>parent.show_txt('$i')</script>";   
                    flush();
                }                         
            }
        }

    
        ////////////////////////////////////////
        // Step 2. CS 우선순위가 없는 주문 조회
        // 주문 조회
        $query = "select seq,pack,pack_lock
                       ,if(pack<>null||pack<>0,pack, seq ) xx 
                       ,if(pack<>null||pack<>0,1, 0 ) is_pack 
                       ,sum(qty) sqty
                   from orders 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and status=1
                     and order_cs not in (1,3)
                     and cs_priority = 0";

        // 선주문 배송예약일 경우엔 판매처 정보가 중요하지 않음.                     
        // 2013.1.7 - jkryu
        if ( $shop_id && $reserve_order != 1  )        
            $query .= " and shop_id in ($shop_id)";
        
        // alice의 경우 보류가 출력안됨 2011.6.13 - jk, 이대리 요청사항
        if ( $except_hold )
            $query .= " and hold=0 ";
        
        // 상품 수량 우선 배송..2011.6.13
        // print_enable의 total_qty 필드에 총 상품 수를 입력한다.
        // print_enable_sort 테이블 생성..
        // pack, total_qty 의 정보 저장..        
        if ( $deliv_priority == "pack" )
            $query .= " group by xx order by is_pack desc,seq";
        else if ( $deliv_priority == "total_qty" )        
            $query .= " group by xx order by sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " group by xx order by collect_date, sqty desc , collect_time ";
        else
            $query .= " group by xx order by seq ";                

    /*
            else if ( $deliv_priority == "total_qty" )        // 상품수량+배송지연, 수량 많은것 순, 수량 많은 것 중에는 오래된 것 순.
            $query .= " , sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " , collect_date, sqty desc , collect_time";

    */
        
        //debug( "make_print q2:" . $query );
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        
        $job_i = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        { 
            $job_i++;
            $this->wrap_debug("[ $job_i] $data[seq] start ############################-----");
            
            // 재고 확인..
            // make_printable에 값을 넣은 후 재고가 있는경우 상태가 2임.
            if ( $data[pack] )
                $this->check_stock_pack( $data[pack] );
            else
                $this->check_stock( $data[seq], $data[pack] );
            
            // 배송 가능 여부 체크
            $this->check_deliv( $data[seq], $data[pack] );
            
            // 부분 배송 체크
            // pack_lock = 1 합포 금지.. 2011.4.6 jk
            if ( $qty_part_deliv  > 0 && $data[pack_lock] == 0 )
            {
                // $this->check_part_deliv( $data[seq], $data[pack], $qty_part_deliv );   
                $this->check_part_deliv2( $data[seq], $data[pack], $qty_part_deliv, $use_app );   
            }
            
            if ( !$use_app && $i%2 == 0 )
            {
                echo "<script language='javascript'>parent.show_txt('$i')</script>";   
                flush();
            }
            
            $this->wrap_debug("[ $job_i] $data[seq] end ############################-----");
            //echo "i:$i<br>";            
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }
        
        // hide waiting
        if ( !$use_app )
        {
            echo "<script language='javascript'>
                    parent.hide_waiting()
                    
                    parent.build_file('$job_idx');
                    
                    parent._init()
                </script>";
            flush();
        }
        
        //************************************
        // 재고 확인 작업, 배송 가능 작업 후
        // 개수 확인 작업
        // 합포 개수
        /*
        $query = "select count(distinct(pack)) cnt from print_enable where is_pack=1 and status=3 and is_deliv_all=1";  
              
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $pack_cnt = $data[cnt] ? $data[cnt] : 0;
        */
        
        $pack_cnt = $this->get_count2("pack",$use_app);
        
        
        // 일반 개수
        
        /*
        $query = "select count(distinct(pack)) cnt from print_enable where is_pack=0 and status=3 and is_deliv_all=1";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if( !$use_app )
        {
            echo "일반개수";
            print_r ( $data );
        }
        
        $single_cnt = $data[cnt] ? $data[cnt] : 0;
        */
        $single_cnt = $this->get_count2("common",$use_app);
        
        $query = "update print_history set pack_deliv_cnt = $pack_cnt, single_deliv_cnt=$single_cnt where idx=$job_idx";
        $this->wrap_debug($query);
        if( !$use_app )
            echo $query;
        mysql_query( $query, $connect );
        
        
        
        //*************************************
        // print_enable의 status가 3인 seq값의 trans_key를 1로 변경
        // 2010.5.24 - jk
        $query  = "select order_seq from print_enable where status=3";
        $this->wrap_debug($query);
        $result = mysql_query( $query, $connect );
        $seqs   = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];
        }
        
        // 2011.2.15 - jk
        // 주문 다운로드에서는 trans_key를 1로 변경
        // 주문 다운로드2에서는 trans_key를 2로 변경
        // 주문 다운로드2번은 합포 할 때 합포를 하지 않음, 반듯이 초기화 후 합포가 진행됨
        // 초기화 시 trans_key=0으로 변경 됨
        $query = "update orders set trans_key=2 where seq in ( $seqs )";
        mysql_query( $query, $connect );

        if( $use_app )
        {
            return true;
        }
    }
    
    // make_printable 원본 2012-12-17
    function make_printable_org($use_app=false, &$ret_msg='')
    {   
        //****************************************************
        // 비주얼베이직 client에서 호출
        // echo 사용시 반드시 if( !$use_app ) 사용
        //****************************************************
        global $connect,$start_date,$end_date,$qty_part_deliv,$enable_part_deliv,$qty_part_deliv,$deliv_priority,$use_shop_priority,$shop_id,$group_id,$supply_code,$s_group_id;

        // 보류제외
        $except_hold = $this->is_except_hold();

        // 판매처 우선순위 작업 자동 실행 2011.4.1 - jk
        $obj = new class_DK00();
        $obj->apply_rule();
        
        if( !$use_app )
        {
            echo( " start make printable / $qty_part_deliv" );
            
            echo "print_enable";
            echo "<script language='javascript'>parent.show_waiting()</script>";
            flush();
        }
        
        // 초기화 작업 중...
        $job_idx = $this->init_printable($use_app);
                
        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
        
        // //debug("start make printable / $supply_code");
        
        
        // Lock Start
        $obj_lock = new class_lock(301);
        if( !$obj_lock->set_start(&$msg) )
        {
            if( !$use_app )
            {
                echo "<script language='javascript'>parent.hide_waiting()</script>";
                echo "<script language='javascript'>parent._init()</script>";
                flush();
                $this->jsAlert($msg);
                return;
            }
            else
            {
                $ret_msg = $msg;
                return false;
            }
        }
        
        /////////////////////////////////////////
        // date: 2010.5.1 - jk
        // cs 우선순위기능 추가 : cs에서 우선순위 설정하면 배송시 선 출고됨.
        // cs_priority필드 추가 됨
        //
        // Step 1. CS 우선순위가 있는 주문 조회
        $query = "select seq,pack,pack_lock
                    ,if(pack<>null||pack<>0,pack, seq ) xx
                    ,if(pack<>null||pack<>0,1, 0 ) is_pack 
                    , sum(qty) sqty
                    from orders 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and status=1
                     and order_cs not in (1,3)
                     and (cs_priority <> 0 or priority <> 0) ";  // priority의 default가 null -> 0 으로 변경 2011.6.13
                     
        if ( $shop_id )
            $query .= " and shop_id in ($shop_id)";
    
        // alice의 경우 보류가 출력안됨 2011.6.13 - jk, 이대리 요청사항
        if ( $except_hold )
            $query .= " and hold=0 ";
                              
        // cs_priority는 cs주문이 먼저 배송, priority는 판매처 주문은 그 다음..
        // cs우선순위는 무조건 적용.
        $query .= " group by xx order by cs_priority desc ";            

        // 판매처 우선순위는 $use_shop_priority가 없는 경우는 실행하지 않는다.
        // 2011.4.6 - jk
        if ( $use_shop_priority )
            $query .= ",priority desc";            
        
        // 작업 조건에 따른 작업 우선순위
        // 우선순위가 있는 주문은 신규 주문이 먼저 돌아버림..오류..
        // 2011.6.14 - jk
        if ( $deliv_priority == "pack" )
            $query .= " , is_pack desc,seq ";
        else if ( $deliv_priority == "total_qty" )        // 상품수량+배송지연, 수량 많은것 순, 수량 많은 것 중에는 오래된 것 순.
            $query .= " , sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " , collect_date, sqty desc , collect_time ";
        else
            $query .= " ,seq ";   
        
        $this->wrap_debug($query);
        
        // 상품 수량 우선 배송..2011.6.13
        // print_enable의 total_qty 필드에 총 상품 수를 입력한다.
        // print_enable_sort 테이블 생성..
        // pack, total_qty 의 정보 저장..
        // //debug( "print_enable total_qty check -> " . $print_enable );        
        $result = mysql_query( $query, $connect );
        
        $_rows = mysql_num_rows( $result );
        if ( $_rows > 0 )
        {   
            while ( $data = mysql_fetch_assoc( $result ) )
            { 
                // 재고 확인..
                // make_printable에 값을 넣은 후 재고가 있는경우 상태가 2임.
                if ( $data[pack] )
                    $this->check_stock_pack( $data[pack] );
                else
                    $this->check_stock( $data[seq], $data[pack] );
                
                // 배송 가능 여부 체크
                $this->check_deliv( $data[seq], $data[pack] );
                
                // 부분 배송 체크
                // pack_lock 은 합포 금지.. 2011.4.6 jkryu
                if ( $qty_part_deliv  > 0 && $data[pack_lock] == 0)
                {
                    // $this->check_part_deliv( $data[seq], $data[pack], $qty_part_deliv );   
                    $this->check_part_deliv2( $data[seq], $data[pack], $qty_part_deliv, $use_app );   
                }
                
                if ( !$use_app && $i%2 == 0 )
                {
                    echo "<script language='javascript'>parent.show_txt('$i')</script>";   
                    flush();
                }                         
            }
        }
        
        ////////////////////////////////////////
        // Step 2. CS 우선순위가 없는 주문 조회
        // 주문 조회
        $query = "select seq,pack,pack_lock
                       ,if(pack<>null||pack<>0,pack, seq ) xx 
                       ,if(pack<>null||pack<>0,1, 0 ) is_pack 
                       ,sum(qty) sqty
                   from orders 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and status=1
                     and order_cs not in (1,3)
                     and cs_priority = 0";
                     
        if ( $shop_id )
            $query .= " and shop_id in ($shop_id)";
        
        // alice의 경우 보류가 출력안됨 2011.6.13 - jk, 이대리 요청사항
        if ( $except_hold )
            $query .= " and hold=0 ";
        
        // 상품 수량 우선 배송..2011.6.13
        // print_enable의 total_qty 필드에 총 상품 수를 입력한다.
        // print_enable_sort 테이블 생성..
        // pack, total_qty 의 정보 저장..        
        if ( $deliv_priority == "pack" )
            $query .= " group by xx order by is_pack desc,seq";
        else if ( $deliv_priority == "total_qty" )        
            $query .= " group by xx order by sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " group by xx order by collect_date, sqty desc , collect_time ";
        else
            $query .= " group by xx order by seq ";                

    /*
            else if ( $deliv_priority == "total_qty" )        // 상품수량+배송지연, 수량 많은것 순, 수량 많은 것 중에는 오래된 것 순.
            $query .= " , sqty desc, xx ";   
        else if ( $deliv_priority == "delay_total_qty" )  // 배송지연+상품수량, 발주일 동일, 상품 많은 것
            $query .= " , collect_date, sqty desc , collect_time";

    */
        
            
        //debug( "make_print q2:" . $query );
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        
        $job_i = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        { 
            $job_i++;
            $this->wrap_debug("[ $job_i] $data[seq] start ############################-----");
            
            // 재고 확인..
            // make_printable에 값을 넣은 후 재고가 있는경우 상태가 2임.
            if ( $data[pack] )
                $this->check_stock_pack( $data[pack] );
            else
                $this->check_stock( $data[seq], $data[pack] );
            
            // 배송 가능 여부 체크
            $this->check_deliv( $data[seq], $data[pack] );
            
            // 부분 배송 체크
            // pack_lock = 1 합포 금지.. 2011.4.6 jk
            if ( $qty_part_deliv  > 0 && $data[pack_lock] == 0 )
            {
                // $this->check_part_deliv( $data[seq], $data[pack], $qty_part_deliv );   
                $this->check_part_deliv2( $data[seq], $data[pack], $qty_part_deliv, $use_app );   
            }
            
            if ( !$use_app && $i%2 == 0 )
            {
                echo "<script language='javascript'>parent.show_txt('$i')</script>";   
                flush();
            }
            
            $this->wrap_debug("[ $job_i] $data[seq] end ############################-----");
            //echo "i:$i<br>";            
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }
        
        // hide waiting
        if ( !$use_app )
        {
            echo "<script language='javascript'>
                    parent.hide_waiting()
                    
                    parent.build_file('$job_idx');
                    
                    parent._init()
                </script>";
            flush();
        }
        
        //************************************
        // 재고 확인 작업, 배송 가능 작업 후
        // 개수 확인 작업
        // 합포 개수
        $query = "select count(distinct(pack)) cnt from print_enable where is_pack=1 and status=3 and is_deliv_all=1";  
        $this->wrap_debug($query);
              
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $pack_cnt = $data[cnt] ? $data[cnt] : 0;
        
        // 일반 개수
        $query = "select count(distinct(pack)) cnt from print_enable where is_pack=0 and status=3 and is_deliv_all=1";
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if( !$use_app )
        {
            echo "일반개수";
            print_r ( $data );
        }
        
        $single_cnt = $data[cnt] ? $data[cnt] : 0;
        
        $query = "update print_history set pack_deliv_cnt = $pack_cnt, single_deliv_cnt=$single_cnt where idx=$job_idx";
        $this->wrap_debug($query);
        if( !$use_app )
            echo $query;
        mysql_query( $query, $connect );
        
        
        
        //*************************************
        // print_enable의 status가 3인 seq값의 trans_key를 1로 변경
        // 2010.5.24 - jk
        $query  = "select order_seq from print_enable where status=3";
        $this->wrap_debug($query);
        $result = mysql_query( $query, $connect );
        $seqs   = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];
        }
        
        // 2011.2.15 - jk
        // 주문 다운로드에서는 trans_key를 1로 변경
        // 주문 다운로드2에서는 trans_key를 2로 변경
        // 주문 다운로드2번은 합포 할 때 합포를 하지 않음, 반듯이 초기화 후 합포가 진행됨
        // 초기화 시 trans_key=0으로 변경 됨
        $query = "update orders set trans_key=2 where seq in ( $seqs )";
        mysql_query( $query, $connect );

        if( $use_app )
        {
            return true;
        }
    }
    
    /////////////////////////////////////////////////////
    // 로직 수정
    // 2009.12.17 - jk
    // 부분 배송 로직..
    function check_part_deliv2( $seq, $pack="", $qty_part_deliv, $use_app=false )
    {
        global $connect;
        
        // 배송 후 교환은 부분배송에 포함되지 않는다. 2012.11.26 - jkryu
        if ( $pack )
            $query = "Select count(*) cnt from orders where pack=$pack and c_seq > 0";
        else
            $query = "Select count(*) cnt from orders where seq=$seq and c_seq > 0";
        
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $cnt1   = $data[cnt];
        
        if ( $cnt1 > 0 )
        {
            debug( "$seq / $pack 부분 배송에 제외 $query");
            return;   
        }
        
        // 배송 가능이 $qty_part_deliv이상이 기본
        // print_enable의 개수 check
        if ( $pack )
            $query = "select count(*) cnt from print_enable where pack=$pack";
        else
            $query = "select count(*) cnt from print_enable where pack=$seq";
        
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $cnt1   = $data[cnt];
        
        // order_products의 개수
        if($pack )
        {
            $query = "select count(*) cnt 
                        from order_products a,
                             orders b
                        where a.order_seq = b.seq
                          and b.pack = $pack
                          and a.order_cs not in (1,2,3,4)";   
        }
        else
        {
            $query = "select count(*) cnt 
                        from order_products a
                        where a.order_seq = $seq
                          and a.order_cs not in (1,2,3,4)";   
        }
        
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
        $cnt2   = $_data[cnt];
        
        if( !$use_app )
            echo "\n 개수체크1: $cnt1 / 개수체크2: $cnt2 / 부분: $qty_part_deliv \n";        
        
        // 총 개수가 부분 취소 수량보다 커야 함.
        // $cnt1: 배송 가능 합포 상품 개수
        // $cnt2: 실제 합포 상품 수
        // $qty_part_deliv: 부분 배송 지정 개수
        if ( $cnt1 >= $qty_part_deliv )
        {
            if( !$use_app )
                echo "cnt1: $cnt1 / cnt2: $cnt2 / $qty_part_deliv \n";
            
            // 전체 주문의 상품에 대해 작업이 되었음 체크
            // 중간에 check해 봐야 의미 없음.
            // 합포 주문이 분리되는 로직
            //   합포 번호가 1234로 4개의 주문이 있음 그 중 2개가 분리될 경우
            //   1234(합포번호), 1235 (배송 가능)
            //   1236, 1237 (배송 불가)
            if ( $cnt1 == $cnt2 )
            {
                $pack = $pack ? $pack : $seq;
                
                // 전체가 배송 가능한 주문은 상태가 3                
                // 전체가 배송 불가능한 주문은 합포가 풀림
                // 따봉..더 쉽네    
                $query  = "select order_seq
                             from print_enable 
                            where pack=$pack 
                            group by order_seq"; 
                
                $this->wrap_debug($query);
                                            
                $result = mysql_query( $query, $connect );
                
                // orders의 order_products가 전체 배송 가능한지 확인..
                // is_deliv_all의 값을 1로 만들어 준다.
                while ( $data = mysql_fetch_assoc( $result ) )
                {
                    $this->check_total_deliv( $data[order_seq], $use_app );
                }                
                
                // is_deliv_all의 개수를 구한다.
                $query = "select sum(qty) tot from print_enable where pack=$pack and is_deliv_all=1";
                $this->wrap_debug($query);
                
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                // 전체 배송 가능한 상품들만의 합을 구해본다.
                // 부분 취소 가능한지 연구...
                if ( $data[tot] >= $qty_part_deliv )
                {
                    //
                    // part 1. 미배송 주문의 처리..
                    //
                    $query = "select a.seq from orders a, print_enable b
                               where a.seq = b.order_seq and 
                                     (b.is_deliv_all is null or b.is_deliv_all = 0) and
                                     b.pack = $pack";
                    
                    $this->wrap_debug($query);
                    
                    $result = mysql_query($query, $connect );
                    $_zzz = "";
                    $_yyy = "";
                    $_cnt = 0;
                    while ( $data = mysql_fetch_assoc ( $result ) )
                    {
                        $_zzz .= $_zzz ? "," : "";
                        $_zzz .= $data[seq];                        
                        $_yyy = $data[seq];
                        $_cnt++;
                    }   
                    
                    //
                    // 배송 나가지 않는 주문의 처리는 모두 이 부분에서..
                    // pack번호 변경, gift 삭제
                    if ( $_zzz != "" )
                    {
                        //
                        // 합포번호 변경전 부분 배송 설정해준다.
                        // 2011.4.6 - jk 
                        // part_seq
                        // 찢어지면서 call함
                        // 이미 part_seq에 값이 부여된 주문은 다시 변경되지 않음
                        // 예외: part_seq값이 부여된 주문끼리 묶이는 경우 새로운 값이 전체에 부여됨 
                        // part_trans 는 사용하지 않음.  
                        // 배송일 기준으로 부분배송 check하는 logic
                        // select substr(trans_date_pos,1,10),count(distinct(part_seq)) from orders where trans_date_pos >='2011-9-15 00:00:00' and trans_date_pos <='2011-10-15 23:59:59' and part_seq <> 0 group by substr(trans_date_pos,1,10) ;                     
                        class_order::set_part_seq( $pack );
                        
                        if ( $_cnt == 1 )
                            $_yyy = 0;
                            
                        $query = "update orders set pack=$_yyy, gift='' where seq in ( $_zzz )";
                        $this->wrap_debug($query);
                        ////debug( "manghal..:" . $query );
                        mysql_query( $query, $connect );
                    }
                    //
                    // end of 미배송 주문.. 
                    
                    //
                    // 전체 배송이 아닌 주문의 합포를 수정 ...
                    $query = "update print_enable set pack=order_seq 
                               where pack=$pack 
                                 and (is_deliv_all is null or is_deliv_all = 0)";
                    
                    $this->wrap_debug($query);
                                                     
                    mysql_query($query, $connect );
                    
                    // 전체 배송 가능한 주문의 상태를 배송가능으로 수정
                    $query = "update print_enable set status=3 where pack=$pack and is_deliv_all=1";
                    $this->wrap_debug($query);
                    ////debug( $query );
                    mysql_query($query, $connect );
                    
                    // pack번호 변경
                    $query = "select order_seq,pack from print_enable where status=3 and pack=$pack limit 1";
                    $this->wrap_debug($query);
                    ////debug( "pack번호 변경: $query" );
                    $result = mysql_query( $query, $connect );
                    $data   = mysql_fetch_assoc( $result );
                    $new_pack = $data[order_seq];
                    
                    // 취소 주문의 합포 번호 변경
                    // 취소 주문은 배송 여부와 상관없이 합포 변경됨 .2010.9.16
                    $query = "update orders set pack=$new_pack where pack=$pack and order_cs in (1,2,3,4)";
                    $this->wrap_debug($query);
                    ////debug( $query );
                    mysql_query( $query, $connect );
                    
                    // 배송 대상 문의 합포 번호 변경..
                    $query = "update print_enable set pack=$new_pack where status=3 and pack=$pack";
                    $this->wrap_debug($query);
                    ////debug( $query );
                    $result = mysql_query( $query, $connect );
                    
                    // orders의 pack정보 변경
                    $query = "select order_seq from print_enable where pack=$new_pack";
                    $this->wrap_debug($query);
                    
                    $result = mysql_query( $query, $connect );
                    $_seqs = "";
                    $cnt_seqs = 0;
                    while ( $data = mysql_fetch_assoc( $result ) )
                    {
                        $_seqs .= $_seqs ? "," : "";
                        $_seqs .= $data[order_seq];
                        $cnt_seqs++;
                    }
                    
                    //
                    // 개수가 1개일 경우 합포 정보가 사라진다.                    
                    // 2010.12.2 - jk 수정
                    if ( $cnt_seqs == 1 )
                    {
                        // 합포인 주문 중에 1개만 배송 가능할 경우가 있음, 
                        // 나머지는 취소..위의 경우 오류 남, 해당 seq로 합포인 주문이 있을 수 있다.
                        $query = "select seq from orders where pack = $_seqs and seq <> $_seqs";
                        $this->wrap_debug($query);
                        $result = mysql_query( $query, $connect );
                        $rows   = mysql_num_rows ( $result );                        
                        
                        // 해당 seq이외의 합포 주문이 없을 경우에만 합포를 삭제 한다.
                        if ( $rows == 1 )
                        {
                            $query = "update orders set pack=0 where pack=$_seqs and seq <> $_seqs";
                            $this->wrap_debug($query);
                            mysql_query( $query, $connect );          
                        }
                        else 
                        {
                            $data   = mysql_fetch_assoc( $result );                            
                            $query = "update orders set pack=$data[seq] where pack=$_seqs and seq <> $_seqs";
                            $this->wrap_debug($query);
                            
                            mysql_query( $query, $connect );          
                        }
                        
                        $query = "update orders set pack=0 where seq = $_seqs";
                        $this->wrap_debug($query);
                        mysql_query( $query, $connect ); 
                    }
                    else
                    {
                        // 변경할 pack이 $_seqs에 포함되었는지 확인
                        $query = "update orders set pack=$new_pack where seq in ( $_seqs )";
                        $this->wrap_debug($query);
                        mysql_query( $query, $connect ); 
                        
                    }                     
                }
            }
        }
        
        // 최종적으로 합포에 몇개의 주문이 남아 있는지 확인하고
        // 1개만 남아있을 경우 합포 정보 삭제 한다.
        $query = "select count(*) cnt from orders where pack='$pack'";   
        $this->wrap_debug($query);     
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[cnt] == 1 )
        {
            $query = "update orders set pack=0 where pack='$pack'";
            $this->wrap_debug($query);
            mysql_query($query, $connect );   
        }
        
    }
    
    // 전체가 배송 가능할 경우 상태가 3
    // 그렇지 않을 경우 합포를 푼다?
    function check_total_deliv( $order_seq, $use_app=false )
    {
        global $connect;
        
        $query = "select count(*) cnt 
                    from print_enable 
                   where order_seq = $order_seq 
                     and is_stock=0";
        
        $this->wrap_debug($query);
        
        if( !$use_app )
            echo "check total \n $query \n-----\n";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // is_deliv_all을 1로 변경
        if ( !$data[cnt] )
        {
            $query = "update print_enable set is_deliv_all = 1 where order_seq=$order_seq";
            ////debug( $query );
            mysql_query( $query, $connect );
        }
        
    }
    
    
    //********************************
    // 부분 배송 처리
    // 2009.12.16 - jk
    // print_enable의 상품수와 order_products의 상품수가 동일하면 로직을 수행한다.
    function check_part_deliv( $seq, $pack="", $qty_part_deliv )
    {
        global $connect;
        
        // print_enable의 개수 check
        if ( $pack )
            $query = "select count(*) cnt from print_enable where pack=$pack";
        else
            $query = "select count(*) cnt from print_enable where pack=$seq";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $cnt1   = $data[cnt];
        
        // order_products의 개수
        if($pack )
        {
            $query = "select count(*) cnt 
                        from order_products a
                             ,(select seq from orders where pack=$pack ) b
                        where a.order_seq = b.seq
                          and a.order_cs not in (1,2,3,4)";   
        }
        else
        {
            $query = "select count(*) cnt 
                        from order_products a
                        where a.order_seq = $seq";   
            
        }
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $cnt2   = $data[cnt];
        
//        echo "\n 개수체크1: $cnt1 / 개수체크2: $cnt2 / 부분: $qty_part_deliv \n";        
        
        // 총 개수가 부분 취소 수량보다 커야 함.
        if ( $cnt1 > $qty_part_deliv )
        {
            // 전체 주문의 상품에 대해 작업이 되었음
            if ( $cnt1 == $cnt2 )
            {
                
//                echo( "주문 상품 작업 완료 [ $seq ] \n" );
                
                // is_stock = 1의 개수를 찾는다.
                if ( $pack )            
                    $query = "select count(*) cnt from print_enable where is_stock=1 and pack=$pack and is_deliv_all=1";                
                else
                    $query = "select count(*) cnt from print_enable where is_stock=1 and pack=$seq  and is_deliv_all=1";
                
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                // 부분 배송 가능한 주문인 경우 로직
                if ( $data[cnt] >= $qty_part_deliv )
                {
                    
//                    echo( " cnt: $data[cnt] / part_deliv: $qty_part_deliv \n" );                    
                    
                
                    // 미배송 주문을 신규 주문으로 생성 시킨다.
                    // 주문 생성
                    // product_seq의 order_seq를 변경
                    $pack = $pack ? $pack : $seq;
                    $query = "select order_seq from print_enable where pack=$pack and is_stock=0 group by order_seq";
                    $result = mysql_query( $query, $connect );
                    while ( $data = mysql_fetch_assoc( $result ) )
                    {
                        // 주문 생성
                        $_new_seq = $this->create_order( $data[order_seq] );
                        
//                        echo "신규 주문 생성 $_new_seq / $data[order_seq] \n";
                        
                        
                        // is_stock=0인 주문들의 order_seq변경
                        // from -> to
                        $this->move_order_products( $data[order_seq], $_new_seq );
                        
                        // 
                        
                    }
                    
                    // 배송 가능 상품의 print_enable의 status를 3으로 변경
                    $query = "update print_enable set status=3 where pack=$pack and is_stock=1";
                    ////debug( $query );
                    mysql_query( $query, $connect );                
                }
            }
        }
    }

    // print_enable의 정보 수정
    // 2009.12.17 - jk
    function move_order_products( $from_seq, $to_seq )
    {
        global $connect;
        
        if ( $from_seq == $to_seq )
        {
            $query = "update orders set pack=0 where seq=$from_seq";    
            mysql_query( $query );
            
            $query = "update print_enable set pack=$from_seq where order_seq=$from_seq and is_stock=0";
            mysql_query( $query, $connect );
        }
        else
        {
        
            // order_products의 order_seq를 옮긴다.
            $query = "update order_products set order_seq=$to_seq 
                       where seq in (select product_seq from print_enable where order_seq=$from_seq and is_stock=0)";
                       
            mysql_query( $query, $connect );
            
            $query = "update print_enable set order_seq = $to_seq, pack=$to_seq where order_seq=$from_seq and is_stock=0";
            mysql_query( $query, $connect );
        }
        
        
    }

    // 주문 생성 
    // 2009.12.17 - jk
    function create_order( $order_seq )
    {
        global $connect;
        
        // 하위 상품이 1개일 경우라면 분리될 필요가 없다.
        // 주문의 합포만 풀어 버리면 됨.
        $query = "select count(*) cnt from order_products where order_seq=$order_seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        if ( $data[cnt] > 1 )
        {   
            // 주문 생성
            $query = "insert into orders ( order_id,shop_id,shop_product_id,product_name,options,qty,collect_date,status,order_cs,recv_name,recv_tel,recv_mobile,recv_zip,recv_address,order_address,memo ) 
                          select order_id,shop_id,shop_product_id,product_name,options,qty,collect_date,status,order_cs,recv_name,recv_tel,recv_mobile,recv_zip,recv_address,order_address,memo
                            from orders where seq=$order_seq";
            $result = mysql_query( $query, $connect );
            
            // max seq를 구함
            /*$query = "select max(seq)+1 max from orders";*/
            $query  = "SELECT LAST_INSERT_ID() max";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $_max   = $data[max];
        }
        else
        {
            $_max = $order_seq;
        }
        
        return $_max;
    }

    //
    // check stock
    // 로직에서 오류가 발생해서 새로 만듬.
    function check_stock_pack( $pack )
    {
        global $connect;  
        
        $query = "select seq,pack from orders where pack=$pack";
        $this->wrap_debug("---check_stock_pack---");
        $this->wrap_debug( $query );
        
        ////debug( $query );
        $result = mysql_query( $query, $connect );
        while( $_data = mysql_fetch_assoc( $result ) )
        {
            ////debug( "check_stock_pack: " . $_data[seq] . "/" . $_data[pack] );
            $this->check_stock( $_data[seq], $_data[pack] );   
        }
    }

    // make_printable에서 실행함..
    // print_enable에 값을 넣는다.
    // print_enable의 status
    //      1: 입력
    //      2: 재고 있음
    //      3: 배송 가능
    //
    // 2011.5.25
    // reserve_order의 값이 1일경우..
    //      이전 주문 중 배송 못 나가는게 있는경우 배송 가능상태가 될 수 없음 - jk
    function check_stock( $seq, $pack )
    {
        global $connect,$reserve_order,$supply_code;
        $is_pack = $pack ? 1 : 0;
        $pack = $pack ? $pack : $seq;
        
        $query = "select seq, product_id, qty,supply_id 
                    from order_products
                   where order_seq = $seq
                     and order_cs not in (1,2,3,4 )";

        $this->wrap_debug("---check_stock---");
        $this->wrap_debug($query);
                     
        //echo $query . "<br>";
        $result = mysql_query( $query, $connect );
        
        $arr_result = array();
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = array( product_seq => $data[seq]
                                  ,product_id  => $data[product_id]
                                  ,supply_id   => $data[supply_id]
                                  ,qty         => $data[qty]
                                  ,status      => 1
                                  ,enable      => 0
                                  );
            // //debug( "check_stock: order_seq: $seq / product_seq: $data[seq] / product_id: $data[product_id] / qty: $data[qty] ");
        }
        
        //*************************************
        // print_enable에 값을 넣는다.       
        // 
        $arr_job_stock = array(); 
        for( $i=0; $i < count($arr_result); $i++ )
        {
            $data = $arr_result[$i];
            
            //echo "<br>----------<br>";
            //print_r ( $data );
            //echo "<br>----------<br>";
            
            $query = "insert into print_enable 
                          set order_seq    = $seq
                              ,product_seq = $data[product_seq]
                              ,pack        = $pack
                              ,org_pack    = $pack
                              ,product_id  = '$data[product_id]'
                              ,qty         = $data[qty]
                              ,status      = $data[status]
                              ,is_pack     = $is_pack";
            $this->wrap_debug($query);                              
            mysql_query( $query, $connect );
            
            // debug( $query );
            
            //
            // 추가로직: 공급처를 선택한 경우..
            // 2011.12.23 - jk
            // 공급처 그룹을 선택.. code1,code2,code3의 형태 - 2012.1.20
            //
            if ( $supply_code )
            {
                $b_find = 0;              
                $arr_supply_code = split( ",", $supply_code );                
                foreach( $arr_supply_code as $code )
                {
                    if ( $data[supply_id] == $code )    
                    {
                        $b_find = 1;
                        break;
                    }
                }
                
                // 공급처가 공급처 그룹에 속하지 않는경우 배송 불가...
                if( $b_find == 0 )
                {
                    // //debug( "공급처 불일치 배송불가: $data[supply_id] : $supply_code");
                    continue;
                }
                else
                {
                    // //debug( "공급처 일치 배송처리: $data[supply_id] : $supply_code");
                }
            }
            
            //*************************************
            // step2. 재고 체크
            // 일단 단일 창고..
            $_stock1 = 0;
            $_stock2 = 0;
            
            // print_enable에서 값을 구함
            // 같은 합포가 아닌 주문에서 값을 구함..
            if ( !$arr_job_stock[ $data[product_id] ] )
            {
                $query  = "select sum(qty) qty 
                             from print_enable 
                            where product_id  = '$data[product_id]' 
                              and pack <> $pack
                              and status      = 3
                              and is_deliv_all= 1";   
                $this->wrap_debug($query);
                //debug( $query );
                                                       
                $_result = mysql_query( $query, $connect );
                $_data   = mysql_fetch_assoc( $_result );
                $arr_job_stock[ $data[product_id] ] = $_data[qty] ? $_data[qty] : 0;  // 작업 중 재고
                
                //debug( "xxkk: $data[product_id] / ". $arr_job_stock[ $data[product_id] ] ."\n");
                
            }
            
            //
            // print_enable에서 값을 구함2
            // check중인 주문에 배송 가능한 주문이 있는경우...
            // 2011.3.28 - jk 추가..
            if ( $pack != 0 )
            {
                $query = "select sum(qty) sqty from print_enable 
                           where pack = $pack 
                             and is_stock=1 
                             and product_id ='$data[product_id]'";  
                $this->wrap_debug($query);
                //debug( "same pack : $query " );
                                           
                $result = mysql_query( $query, $connect );
                $_data  = mysql_fetch_assoc( $result );                
                $arr_pack_stock[ $data[product_id] ] = $_data[sqty];
            }
            else
            {
                $arr_pack_stock[ $data[product_id] ] = 0;
            }
            
            //debug( $data[product_id] . " step2 110328 same: " . $arr_pack_stock[ $data[product_id] ]." / other: " . $arr_job_stock[ $data[product_id] ] );
            
            //
            // reserve_order의 값을 보고..seq가 작은 미배송 건 중...
            //      선주문 배송예약은 우선순위를 "배송지연"으로 설정해야 함.
            //      print_enable table에 status가 3이 아닌경우 모두 배송 예약된 주문으로 처리 하면 됨.
            $reserve_cnt = 0;
            if ( $reserve_order == 1 )
            {
                $reserve_cnt = $this->get_reserve_count( $data[product_id], $data[product_seq], $pack);
            }
            
            //****
            //
            // current stock 정보를 가져온다.   
            // 복수의 location에 대비한 코드..
            //         
            $arr_stock = $this->arr_current_stock( $data[product_id] );      
            foreach ( $arr_stock as $_stock )
            {
                // stock의 개수 체크    
                // product_stock내의 여러 상품 코드 중에도 같은게 있을 수 있다.  
                // 실재고      
                $_current_stock = $_stock[stock] - $arr_job_stock[ $data[product_id] ] - $arr_pack_stock[ $data[product_id] ] - $reserve_cnt;    // 재고수량  - 작업중 재고                
                //debug(" $data[product_id] currnet stock: $_current_stock / stock1: $_stock[stock] / job stock: " . $arr_job_stock[ $data[product_id] ] . " pack stock: ".$arr_pack_stock[ $data[product_id] ] . " rev cnt: $reserve_cnt");
                
                if ( $arr_result[$i][qty] <= $_current_stock )
                {
                    $arr_result[$i]['enable']           = 1;
                    $arr_result[$i]['location']         = $_stock[location];
                    //$arr_job_stock[ $data[product_id] ] += $arr_result[$i][qty];
                    
                    // 배송 가능 check
                    $query = "update print_enable
                                 set status      = 2
                                     ,is_stock   = 1
                                     ,location   = '$_stock[location]'
                               where order_seq   = $seq
                                 and product_seq = $data[product_seq]";
                    $this->wrap_debug($query);
                    //debug( $query );
                    
                    mysql_query( $query, $connect );
                    break;
                }                
            }
        }
    }
    
    //*************************************        
    //
    // 배송 예약 개수 확인
    // 2011.5.22 - jk
    // pack이 같은경우 배송 예약을 타지 않는다. 2011.11.17 - jk
    //     => pack stock에서 이미 1개 잡는다.
    function get_reserve_count( $product_id, $product_seq, $pack)
    {
        global $connect;
        
        // 2012.9.18 - jkryu 수정
        // status <> 3 => status = 3으로 변경
        $query = "select sum(qty) sqty
                    from print_enable 
                   where product_id = '$product_id'
                     and product_seq <> $product_seq
                     and pack   <> $pack
                     and status <> 3";
        $this->wrap_debug($query);
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        //debug( "reverse count: cnt: $data[sqty] / " . $query );
        
        return $data[sqty];                     
    }
    
    //*************************************        
    //
    // step3 배송 가능 처리
    // 2009.12.8 - jk
    function check_deliv( $seq, $pack )
    {
        global $connect;
        
        $_pack = $pack ? $pack : $seq;
        
        // 합포이면 전체 배송 가능인지 여부 체크
        // 전체 배송 가능이 아니면 3이 될 수 없음 
        $query = "select * from print_enable where pack=$_pack";
        $this->wrap_debug("---check_deliv---");
        $this->wrap_debug($query);
         
        $result = mysql_query( $query, $connect );
        $deliv_all = 1; // 전체 배송 가능 여부..
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( !$data[is_stock] )
            {
                $deliv_all = 0;
                break;
            }
        }
                
        // 전체 배송 가능..
        if ( $deliv_all )
        {
            $query = "update print_enable set status=3,is_deliv_all=1 where pack=$_pack";
            $this->wrap_debug($query);
            ////debug( $query );
            mysql_query( $query, $connect );      
            
            // 전체 배송가능 표시..
            // block 해 버림....왜 쓰는지 모르겠음. = jkryu 2011.10.31
            //$query = "update orders set trans_no=$pack where pack=$_pack";
            //mysql_query( $query, $connect );      
        }
        else
        {
            $query = "update print_enable set status=2,is_deliv_all=0 where pack=$_pack";
            $this->wrap_debug($query);
            mysql_query( $query, $connect );      
            
            $query = "update orders set trans_no='' where pack=$_pack and status=1";
            $this->wrap_debug($query);
            mysql_query( $query, $connect );      
        }
        
        // 사포시..
        // $query = "update print_enable set status=3 where is_pack=0 and is_stock=1";
        // mysql_query( $query, $connect );
    }

    function arr_current_stock( $product_id )
    {
        global $connect;
        
        // $product_id로 재고 검사 여부를 확인한다.
        // 2010.1.12 - jk
        $query = "select enable_stock from products where product_id='$product_id'";
        $this->wrap_debug($query);
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 재고 관리를 하는 상품
        if ( $data[enable_stock] )
        {
            $query = "select * from current_stock where product_id='$product_id' and bad=0";
            $this->wrap_debug($query);
            $result = mysql_query( $query, $connect );
            $arr_result = array();
            while ( $data=mysql_fetch_assoc( $result ) )
            {
                $arr_result[] = $data;
            }
            
            // 송장 상태인 건을 빼야 함
            for($i=0; $i < count( $arr_result ); $i++ )
            {
                /*
                $query = "select sum(qty) qty
                            from order_products a
                                 ,(select seq from orders where status=7 and order_cs not in (1,3)) b
                           where a.product_id = '" . $arr_result[$i][product_id] . "'
                             and a.order_seq = b.seq";
                */
                $query = "select sum(a.qty) qty
                            from order_products a, orders b
                           where b.status = 7
                             and a.order_cs not in (1,2,3,4)
                             and a.product_id = '" . $arr_result[$i][product_id] . "'
                             and a.order_seq = b.seq";
                $this->wrap_debug($query);
                                                             
                $result = mysql_query( $query, $connect );                         
                $data   = mysql_fetch_assoc($result );
                
                $arr_result[$i][stock] -= $data[qty];
            }
        }
        else
        {
            // 재고 관리를 하지 않는 상품
            $arr_result[] = array( 
                product_id => $product_id
                ,location  => "xxxx"
                ,stock     => 1000
            );    
        }
        
        return $arr_result;        
    }


    // 작업 초기화
    // 2009-12-4 jk
    function init_printable($use_app=false)
    {
        global $connect,$start_date,$end_date,$qty_part_deliv,$enable_part_deliv,$qty_part_deliv,$deliv_priority,$shop_id,$use_shop_priority,$reserve_order,$group_id;
        global $supply_code, $group_id, $s_group_id;
        
        if( !$use_app ) 
            echo "start_init";
        
        // 초기화 가능한지 여부 check
        // 작업 가능한 상태 최종 주문이 송장입력완료, 4 or 초기화 상태
        $query  = "select * from print_history order by idx desc limit 1";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result ); 
        
        // 상태가 1~3 사이면 초기화 요청
        if ( !$use_app && ($data[status] >= 1 && $data[status] <= 3) )
        {
            echo "<script language='javascript'>
                parent.hide_waiting()
                alert(' 이미 작업중 입니다. 새로 작업을 하시려면 \"초기화\"를 진행시켜주십시요')
            </script>";            
            exit;   
        }
        
        if( !$use_app ) 
        {
            echo "<script language='javascript'>parent.show_txt('자료 초기화 중..')</script>";   
            flush();
        }
        
        $query = "update orders set trans_no='' where status=1 and trans_no <> ''";
        mysql_query( $query , $connect );
        
        // print_enable table초기화
        $query = "truncate print_enable";
        mysql_query( $query , $connect );

        // 작업 idx 생성
        $query  = "select max(idx)+1 idx from print_history";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $idx    = $data[idx] ? $data[idx] : 1;
        $shop_id= $shop_id ? $shop_id : 0;
        $supply_id = $supply_code ? $supply_code : "";
                        
        // print_history에 값 입력
        $query = "insert into print_history
                          set idx            = $idx
                             ,start_date     = '$start_date'
                             ,end_date       = '$end_date'
                             ,deliv_priority = '$deliv_priority'
                             ,use_shop_priority = '$use_shop_priority'
                             ,reserve_order     = '$reserve_order'
                             ,status         = 1
                             ,worker         = '" . $_SESSION['LOGIN_NAME'] . "'
                             ,shop_id        = $shop_id
                             ,supply_id      = '$supply_id'
                             ,crdate         = Now()
                             ,shop_group     = '$group_id'
                             ,supply_group   = '$s_group_id'";
        
        // 부분 배송 가능..
        if ( $qty_part_deliv )
            $query .= ",part_deliv = $qty_part_deliv";  

        debug( "init_print:" . $query );
            
        mysql_query( $query, $connect );
        
        // 작업 로그
        $this->insert_print_log( $idx, 1 );
        
        // 기존 자료를 옮기는 작업...
        // 1개월 자료를 제외하고 삭제...
        $query = "delete from print_enable_history where crdate <='" . date('Y-m-d', strtotime('-30 day')) . "'";
        mysql_query( $query, $connect );
        
        // make_printable 테이블을 make_printable_history로 옮긴다. 2012-01-03 - jkryu
        $query = "select * from print_enable";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->insert_history( $data, $idx );   
        }
        
        // orders에서 status가 1이면서 trans_no에 seq나 pack이들어간 주문을 삭제
        $this->clear_ready_orders($use_app);
        
        return $idx;
    }

    //==================
    //  로그 기록
    //  ok
    function insert_print_log( $idx, $status )
    {
        global $connect;
        
        // print_history_log에 값 입력
        $query = "insert into print_history_log
                          set idx    = $idx
                             ,status = 1
                             ,crdate = Now()
                             ,worker = '" . $_SESSION['LOGIN_NAME'] . "'";
                             
        mysql_query( $query, $connect );
    }
    
    // 다운로드 받은 주문의 정보 갱신
    function clear_ready_orders($use_app=false)
    {
        global $connect, $start_date, $end_date;
        $query = "update orders set trans_no='' 
                   where status=1 
                     and collect_date >= '$start_date'
                     and collect_date <= '$end_date'";
        if( !$use_app )
        {
            echo "<script language='javascript'>parent.show_txt('주문 초기화 중..')</script>";   
            flush();
        }
        
        mysql_query( $query, $connect );
    }


  function DR00()
  {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $confirm;

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
        if (!$end_date)   $end_date = date('Y-m-d');

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }

  //
  // print_enable의 모든 seq값이 송장상태인 주문이 없을경우.
  // 모든 seq의 pack을 되돌린다.2011.7.21 ozsama이 케이스
  // 1로 부분 배송을 두 번 연속 돌리는 경우 
  // 분리된 주문으로 인해 오류가 발생할 수 있다.
  function check_trans()
  {
        global $connect;
        
        $query = "select seq from print_enable";
        $result = mysql_query( $query, $connect );
        $seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[seq];    
        }
        
        // 송장이나 배송상태의 주문이 있는지 확인
        $query = "select count(*) cnt from orders where seq in ( $seqs )";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 하나도 없을경우 합포를 원상복귀 한다.
        /*
        if ( $data[cnt] == 0 )
        {
            $query = "select order_seq,org_pack from print_enable where is_pack=1";
            $result = mysql_query( $query, $connect );
            $seqs = "";
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $seqs .= $seqs ? "," : "";
                $seqs .= $data[order_seq];    
            }      
            
            // 
            if ( $seqs )
            {
                $query = "update orders a inner join print_enable b on a.seq = b.order_seq 
                             set a.pack = b.org_pack
                            where b.is_pack = 1";
                         
                mysql_query( $query, $connect );                           
                //debug( $query );
            }
            else
            {
                //debug( "there is no pack");   
            }
        }
        */
  }

  // download_date정보 삭제
  function remove_download_date($use_app=false)
  {
        global $connect, $type;

        $arr_result = array();

        // Lock Start
        $obj_lock = new class_lock(308);
        if( !$obj_lock->set_start(&$msg) )
        {
            if( !$use_app )
            {
                $arr_result[lock] = 1;
                $arr_result[lock_msg] = $msg;
                
                // list출력
                echo json_encode( $arr_result );
                return;
            }
            else
            {
                $ret_msg = $msg;
                return false;
            }
        }

        if( $type == "set_init" )
        {
            // print_enable의 모든 seq값이 송장상태인 주문이 없을경우.
            // 모든 seq의 pack을 되돌린다. 2011.7.21 ozsama이 케이스
            $this->check_trans();
            
            $query = "select * from print_history order by idx desc limit 1";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            
            $idx    = $data[idx];
            
            // 초기화 정보를 print_history_log에 입력
            $query = "insert into print_history_log 
                         set idx    =$idx
                             ,status=4
                             ,crdate=Now()
                             ,worker='" . $_SESSION['LOGIN_NAME'] . "'";
            mysql_query( $query, $connect );
            
            // 4는 초기화.
            $query = "update print_history set status=4 where idx=$idx";
            mysql_query( $query, $connect );            
            
            // orders의 정보삭제
            $query = "update orders set trans_no='' where status=1 and trans_no <> ''";
            mysql_query( $query, $connect );  
            
            // 2011.2.15 - jk
            // trans_key가 2인 주문의 trans_key를 0으로 변경함
            // trans_key: 1 주문 다운로드 표시
            // trans_key: 2 작업2번 대상임을 표시(trans_key가 2인 주문은 신규 주문과 합포하지 않는다)
            $query = "update orders set trans_key=0 where trans_key=2 and status=1";
            mysql_query( $query, $connect );
            
            // 기존 자료를 옮기는 작업...
            // 1개월 자료를 제외하고 삭제...
            $query = "delete from print_enable_history where crdate <='" . date('Y-m-d', strtotime('-30 day')) . "'";
            mysql_query( $query, $connect );
            
            // make_printable 테이블을 make_printable_history로 옮긴다. 2012-01-03 - jkryu
            $query = "select * from print_enable";
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $this->insert_history( $data, $idx );   
            }
            
            // print_enable_history_work의 idx가 0인 row의 idx를 수정
            $query = "update print_enable_history_work set idx=$idx where idx=0";
            ////debug( "set init to 0: " . $query );
            mysql_query( $query, $connect );
            
            // print_enable삭제
            $query = "truncate print_enable";
            mysql_query( $query, $connect );  
                      
        }
        
        // print_history
        $query = "select * from print_history order by idx desc limit 30";
        
        $result = mysql_query( $query, $connect );
        $arr_result['str'] = $str;
        $arr_result['cnt'] = 0;
        $arr_status = array(1=>"생성작업", 2=>"다운로드", 3=>"송장입력", 4=>"초기화");
        $arr_part_deliv = array( 0 => "없음", 1=>"사용" );
        $arr_deliv_priority = array( "delay" => "배송지연", "pack" => "합포"  );
        $arr_use_shop_priority = array( 1 => "적용", 0 => ""  );
        $arr_reserve_order     = array( 1 => "적용", 0 => ""  );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[shop_name]         = class_C::get_shop_name( $data["shop_id"] ? $data[shop_id] : "" );
            $data[supply_id]         = $this->get_supply_name2( $data["supply_id"] );
            $data[status_log]        = $this->get_detail_status( $data[idx] );
            $data[status]            = $arr_status[ $data[status] ];
            $data[part_deliv]        = $data[part_deliv] ? $data[part_deliv] : "";
            $data[deliv_priority]    = $arr_deliv_priority[ $data[deliv_priority] ];
            $data[use_shop_priority] = $arr_use_shop_priority[$data[use_shop_priority]];
            $data[reserve_order    ] = $arr_use_shop_priority[$data[reserve_order]];
            $data[pack_deliv_cnt]    = $data[pack_deliv_cnt] ? $data[pack_deliv_cnt] : 0;
            $data[single_deliv_cnt]  = $data[single_deliv_cnt] ? $data[single_deliv_cnt] : 0;
            $data[shop_group]        = $this->get_shop_group_name( $data[shop_group] );
            $data[supply_group]      = $this->get_supply_group_name( $data[supply_group] );
            
            $arr_result['list'][] = $data;
            $arr_result['cnt']++; 
        }
 
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        if(!$use_app)
        {
            $arr_result[lock] = 0;
            echo json_encode( $arr_result );
        }
        else
            return true;
    }

    // status
    // 2009.12.6 - jk
    function get_detail_status( $seq )
    {
        global $connect;
        
        $query = "select * from print_history_log where idx=$seq order by crdate desc";
        $result = mysql_query( $query, $connect );
        
        $_str = "";
        $arr_status = array(1=>"재고검사", 2=>"다운로드", 3=>"송장입력", 4=>"초기화");
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $_str .= "($data[crdate]) - " . $arr_status[ $data[status] ] . " - $data[worker] <br>";
        }
        return $_str;
    }

  function confirm()
  {
        global $template, $page;
        global $connect, $trans_who, $shop_id, $supply_code, $status, $confirm,$group_id;
        
        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }

        
        $status_list = "0,11,9,5,7,13";

        if ( $status == 1 ) // 정상
                $status_list = "0,7";
        elseif ( $status == 2 ) // 교환
                $status_list = "5,9,11,13";

        $query = "update orders set download_date = Now() where status in (1,2,11) and order_cs in ( $status_list ) ";
        
        if ( $shop_id )
                $query .= " and shop_id in ($shop_id) ";

        /*if ( $supply_code )
                $query .= " and supply_id=$supply_code";*/

        if ( $trans_who )
                $query .= " and trans_who='$trans_who'";
        
        if ( $confirm )
                $query .= " and download_date is not null ";

        mysql_query ( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }

  // 미송건 카운트
  // date: 2008.4.24 - jk
  function notyet_count()
  {
    global $connect;
    $query = "select count(*) cnt
                from orders where status <> 8 and order_cs not in ( 1,2,3,4 )";

    $result = mysql_query( $query, $connect );
    $data = mysql_fetch_assoc( $result );
    echo $data[cnt] ? $data[cnt] : 0;
  }

    // 합포 일반 수량..체크..
    function get_count2( $_key = "",$use_app )
    {
        global $connect, $key, $group_id;

        // shop_group 계산. - 2013.1.7 - jkryu
        if ( $group_id != "" )
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }        

        if ( $_key )
        {
            $key = $_key;
        }
        else
        {
            $key = $_REQUEST[key];
        }
        
        if ( $key == "common" )
        {
            // 일반 개수
            if ( $shop_id )
            {
                $query = "select count( distinct(b.pack)) cnt from orders a, print_enable b 
                       where a.seq = b.order_seq 
                         and b.status = 3
                         and b.is_pack = 0
                         and b.is_deliv_all=1
                         and a.shop_id in ( $shop_id )";    
            }
            else
            {
                $query = "select count(distinct(pack)) cnt from print_enable where status=3 and is_pack=0 and is_deliv_all=1";
            }
            
            debug( "get_count2: $query");
             
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );    
        }
        else
        {
            if ( $shop_id )
            {
                $query = "select count( distinct(b.pack)) cnt from orders a, print_enable b 
                       where a.seq = b.order_seq 
                         and b.status = 3
                         and b.is_pack = 1
                         and b.is_deliv_all=1
                         and a.shop_id in ( $shop_id )";    
            }
            else
            {
                $query = "select count(distinct(pack)) cnt from print_enable where status=3 and is_pack=1 and is_deliv_all=1";
            }
            
            //debug( "get_count2: $query");
            
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
        }
       
		if ( !$use_app ) 
            echo $data[cnt] ? $data[cnt] : 0;

        return $data[cnt] ? $data[cnt] : 0;
    }

    // 
    // 해당 그룹의 판매처 리스트..
    function get_group_shop( $group_id )
    {
        global $connect,$shop_id;
        
        $query = "select shop_id from shopinfo where group_id=$group_id"; 
        
        if ( $shop_id )
            $query .= " and shop_id=$shop_id";
        
        $result = mysql_query( $query, $connect );
        $shop_ids = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_ids .= $shop_ids ? "," : "";
            $shop_ids .= $data[shop_id];
        }
        
        return $shop_ids;
    }
   
    function get_count( $_key='' )
    {
        global $connect, $trans_who, $shop_id, $supply_code, $status, $confirm, $warehouse, $key,$group_id,$s_group_id,$start_date,$end_date;

        $key = $_REQUEST[key];
        
        debug( "key: $key");
        
        $key = $_key ? $_key : $key;        
    
        // 보류제외
        $except_hold = $this->is_except_hold();

        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }

        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
                    
        $query = "select count(distinct a.seq) cnt from orders a, order_products b where a.seq=b.order_seq and ";
        //////////////////////////////////
        if ( $status == 1 ) // 정상
            $query .= " a.status=1 and a.order_cs in ( 0,2 ) and substring(a.order_id,1,1)<>'C'";
        elseif ( $status == 2 ) // 교환
            $query .= " (a.status=1 and a.order_cs in ( 5,6,7,8 )) and substring(a.order_id,1,1)='C')";
        elseif ( $status == 7 )
            $query .= " a.status=7 and a.order_cs not in (1,2,3,4)";
        else
            $query .= " a.status=1 and a.order_cs not in (1,3)";
            
        switch ( $key )
        {
            // 2008.7.23 보류건을 구분해서 카운트하지 않음
            case "common" :        // 일반 개수
                $query .= " and a.pack = 0";
                break;
            case "pack" :        // 합포 개수
                $query .= " and a.seq=a.pack";
                break;
            case "hold" :        // 합포 개수
                $query .= " and a.hold > 0 and (a.seq=a.pack or a.pack=0)";
                break;
        } 
    
        // 보류를 출력하지 않는다.
        if ( $except_hold )
            $query .= " and a.hold=0 ";
    
        if ( $shop_id )
            $query .= " and a.shop_id in ($shop_id) ";
          
        if ( $supply_code )
            $query .= " and b.supply_id in ($supply_code) ";
        
        if ( $trans_who )
            $query .= " and a.trans_who='$trans_who' ";

        if ( $start_date )
                $query .= " and a.collect_date >= '$start_date'";
                
        if ( $end_date )
            $query .= " and a.collect_date <= '$end_date'";

        // 2011.2.18
        // pack일 경우 count재 생성
        if ( $key == "pack" )
        {
            $query = "select if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx, count(*) cnt 
                        from orders a, order_products b
                       where (a.pack is not null and a.pack <> 0)
                         and a.seq = b.order_seq";
            
            if ( $status == 1 ) // 정상
                $query .= " and a.status=1 and a.order_cs in ( 0,2 ) and substring(a.order_id,1,1)<>'C'";
            elseif ( $status == 2 ) // 교환
                $query .= " and (a.status=1 and a.order_cs in ( 5,6,7,8 )) and substring(a.order_id,1,1)='C')";
            elseif ( $status == 7 )
                $query .= " and a.status=7 and a.order_cs not in (1,2,3,4)";
            else
                $query .= " and a.status=1 and a.order_cs not in (1,3)";
            
            if ( $supply_code )
                $query .= " and b.supply_id=$supply_code ";
            
            if ( $shop_id )
                $query .= " and a.shop_id in ($shop_id) ";
            
            // 보류를 출력하지 않는다.
            if ( $except_hold )
                $query .= " and a.hold=0 ";
            
            if ( $trans_who )
                $query .= " and a.trans_who='$trans_who' ";

            if ( $start_date )
                $query .= " and a.collect_date >= '$start_date'";
                
            if ( $end_date )
                $query .= " and a.collect_date <= '$end_date'";
                         
            $query .= " group by xx";
         
            
         
            $result = mysql_query ( $query, $connect );
            $rows   = mysql_num_rows( $result );   
            
            debug( "####" . $query );
            echo $rows;
        }
        else
        {
            debug( "####" .  $query );
            $result = mysql_query ( $query, $connect );
            $data = mysql_fetch_array ( $result );
            echo $data[cnt];  
        }
        exit;      
    }
        
    var $m_download_config;
    //=====================================
    //
    // download
    // download_type
    //        req_takeback: 회수 요청
    //        trans_no: 송장 입력 회수 요청
    //        comp_takeback : 회수 완료 , 2007.3.15 추가 됨
    //      => download format이 달라야 함 
    // function download2()
    function build_file()
    {
        global $connect, $saveTarget, $download_type;
        global $trans_corp, $trans_format, $v_trans_format;
        global $start_date, $end_date,$shop_id;
        global $warehouse,$group_id,$s_group_id,$start_date,$end_date;
        
        // 판매처 우선순위~ 2011.4.1 - jk
        // 판매처 우선 순위는 프리미엄만 있음.
        // $obj = new class_DK00();
        // $obj->apply_rule();
        
        // save file 생성
        $filename = Date(Ymd) . "_" . rand(1,10000);
        $saveTarget = _upload_dir . $filename;

        echo "<script language='javascript'>parent.show_waiting()</script>";
        echo "build_file: saveTarget: $saveTarget <br>";

        // trans_format이 전달되지 않는 경우가 있음
        $trans_format = $trans_format ? $trans_format : $v_trans_format;

        // 환경정보 가져온다
        $download_config[is_header] = 0;
        $download_config[pack_multiline] = 0;
        $download_config[sortinfo] = 0;
        $download_config[is_system] = 0;
        $download_config[product_sum] = 0;

        // sys_trans_info에서 받아 옴
        $query = "select * from trans_conf where trans_corp='$trans_corp' and format_id='$trans_format'";

        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        $download_config[is_header] = $data[is_header];
        $download_config[pack_multiline] = $data[pack_multiline];
        $download_config[sortinfo] = $data[sortinfo];
        $download_config[is_system] = $data[is_system];
        $download_config[product_sum] = $data[product_sum];

        $this->m_download_config = $download_config;

        //==================================
        // format정보 다운로드
        // header 정보 다운로드
        $download_items = $this->get_format( $download_config );

        // file open
        $handle = fopen ($saveTarget, "w");

        //====================================================
        //        
        // header 출력 부분 결정
        // date: 2007.3.19 - jk
        //
        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

        if ( $download_config[is_header] )
        {
                $buffer .= "<tr>";
                $this->m_gift_count = 0;
                for( $i=0; $i < count( $download_items ); $i++ ) 
                {
                        list ( $key , $value ) = $download_items[$i];
                        list ( $key , $header ) = split( ":", $key );

                        if ( $header )
                        {
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>". $header . "</td>";
                            $buffer .= "<td  style='mso-number-format:General' align=center>". $header . "</td>";
                        }
                        else
                        {
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>" . $this->convert_title( $key ) . "</td>";
                            $buffer .= "<td  style='mso-number-format:General' align=center>" . $this->convert_title( $key ) . "</td>";
                        }
                }
                $buffer .= "</tr>\n";
        }
        fwrite($handle, $buffer);

        //==================================================
        // 
        // 다운로드 받은 주문은 trans_key의 값을 1로 변경해 준다.
        // 2010.5.24 - jkryu - jang의 요청..
        $query = "update orders set trans_key=1 where status in (1)  and order_cs not in (1,3)";
        mysql_query( $query, $connect );

        //====================================================
        //        
        // 주문 리스트 가져온다 
        // date: 2007.3.19 - jk
        //
        // 회수 추가 2007.5.8 - jk
        
        //debug( "build file: $status ");
        
        global $status;
        if ( $status == 3 ) // 회수 
            $result = class_takeback::get_list("req_takeback");
        else
        {
            //$result = $this->get_list( $download_config ); 
            
            // 2011.2.18 - jk
            // seq=pack인 주문이 취소가 되어도 합포중 정상이 한 건이라도 있으면 다운로드 됨
            $result = $this->get_list_new( $download_config ); 
        }
    
        //===================================================
        //
        // data부분 download
        //
        $cnt = 0;
        $seqs = "";
        $packs = "";
        while ( $data = mysql_fetch_array ( $result ) )
        {
            // 은성의 경우 직배일 경우 pass하는 로직을 넣는다.
            if ( _DOMAIN_ == "eshealthvill" )
            {
                // 직배인 경우 pass한다
                if ( $this->check_direct( $data[seq], $data[pack] ) )
                    continue;   
            }
            
                // seq 번호 리스트 구한다.
                if ( $data[pack] == 0 )
                {
                    if ( $seqs != "" )
                        $seqs .= ",";
                    
                    $seqs .= $data[seq];
                }
                
                // 합포 번호 리스트 구한다.
                if ( $data[pack] != 0 )
                {
                    if ( $packs != "" )
                        $packs .= ",";
                    
                    $packs .= $data[pack];
                }
                $cnt++;

                if ( $cnt%20 == 0 )        
                {
                    echo "<script language='javascript'>parent.show_txt( $cnt )</script>";
                    flush();
                }

                $buffer = "<tr>\n";
                $this->m_gift_count = 0;
                
                for( $i=0; $i < count( $download_items ); $i++ ) 
                {
                        list ( $key , $value ) = $download_items[$i];
                        list ( $key , $header, $min_max ) = split( ":", $key );

                        if ( preg_match( "/-/", $min_max) )
                        {
                            list ($min,$max ) = split ("-", $min_max );
                            $this->min_product = $min;
                            $this->max_product = $max - $min + 1;        // max에는 개수가 들어가야 함
                        }
                        else
                        {
                            $this->max_product = $min_max;
                            $this->min_product = 1;
                        }

                        if ( $key == "packs" )
                           $buffer .= $this->get_data( $data, $key, $value );
                        else if ( $key == "none" )
                            continue;                                
                        else
                        {
                            if ( $key == "packs" )
                            {
                                $buffer .= "<td style='mso-number-format:General'>" . $this->get_data( $data, $key, $value ) . "</td>";
                            }
                            else
                            {
                                $buffer .= "<td style='mso-number-format:\"\@\"'>" . $this->get_data( $data, $key, $value ) . "</td>";
                            }
                        }   
                        //$buffer .= "<td>" . $this->get_data( $data, $key, $value ) . "</td>";
                }
                $buffer .= "</tr>\n";
                fwrite($handle, $buffer);
                $buffer = "";
            }
    
            // orders에 download_date 기록.
            $this->set_download_date( $seqs, 0 );
            $this->set_download_date( $packs, 1 );
    
            // footer 기록
            fwrite($handle, "</table>");
        
                // $filename = _upload_dir . $filename;   
            echo "<script language='javascript'>
                       parent.hide_waiting()
                       parent.download('$filename')
                  </script>";
            flush();
        }

    //
    // 직배인지 택배인지 구분한다.
    //
    function check_direct( $seq, $pack )    
    {
        global $connect;
        $seqs = "";
        if ( $pack )
        {
            $query = "select seq from orders where pack=$pack";  
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $seqs .= $seqs ? "," : "";
                $seqs .= $data[seq];   
            }
        }
        else
            $seqs = $seq;
        
        $query = "select product_id from order_products where order_seq in ( $seqs )";
        $result = mysql_query( $query, $connect );
        $product_ids = "";
        while ( $data = mysql_fetch_assoc($result) )
        {
            $product_ids .= $product_ids ? "," : "";
            $product_ids .= "'$data[product_id]'";
        }
        
        // product_id중에 trans_type=1 직배
        $query = "select count(*) cnt from products where trans_type=1 and product_id in ( $product_ids )";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[cnt] > 0 )
            return 1;
        else
            return 0;
        
    }

    //
    // 다운로드일을 저장한다.
    //   무조건 최종 다운로드 일시로 update한다.
    //
    function set_download_date( $seqs, $is_pack = 0 )
    {
        if ( _DOMAIN_ == "pimz" || _DOMAIN_ == "efolium2" || _DOMAIN_ == "aostar" )
        {
            global $connect;
            
            $query = "update orders set download_date=Now() where ";
            if ( $is_pack  == 1 )
            {
                $query .= " pack in ( $seqs )";
            }
            else
            {
                $query .= " seq in ( $seqs )";
            }
                        
            mysql_query( $query, $connect );
        }
    }
    
    // 
    // 내용 검색 중 다운로드.
    //
    function build_file3()
    {
        // buile_file2의 내용을 완전 비슷하게...
        global $connect, $saveTarget;
        global $trans_corp, $trans_format;
        global $warehouse,$idx;
        
        echo "build file3: idx: $idx, $trans_corp, $trans_format ";
        
        // save file 생성
        $filename = Date(Ymd) . "_" . rand(1,10000) . "_" .$_SESSION['LOGIN_ID'];
        $saveTarget = _upload_dir . $filename;
    
        echo "<script language='javascript'>parent.show_waiting()</script>";
    
        echo "build_file: saveTarget: $saveTarget <br>";

        // trans_format이 전달되지 않는 경우가 있음
        $trans_format = $trans_format ? $trans_format : $v_trans_format;

        // 환경정보 가져온다
        $download_config[is_header] = 0;
        $download_config[pack_multiline] = 0;
        $download_config[sortinfo] = 0;
        $download_config[is_system] = 0;
        $download_config[product_sum] = 0;

        // sys_trans_info에서 받아 옴
        $query = "select * from trans_conf where trans_corp='$trans_corp' and format_id='$trans_format'";

        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        $download_config[is_header] = $data[is_header];
        $download_config[pack_multiline] = $data[pack_multiline];
        $download_config[sortinfo] = $data[sortinfo];
        $download_config[is_system] = $data[is_system];
        $download_config[product_sum] = $data[product_sum];
        
        $this->m_download_config = $download_config;

        //==================================
        // format정보 다운로드
        // header 정보 다운로드
        $download_items = $this->get_format( $download_config );

        // file open
        $handle = fopen ($saveTarget, "w");

        //====================================================
        //        
        // header 출력 부분 결정
        // date: 2007.3.19 - jk
        //
        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

        if ( $download_config[is_header] )
        {
                $buffer .= "<tr>";

                for( $i=0; $i < count( $download_items ); $i++ ) 
                {
                        list ( $key , $value ) = $download_items[$i];
                        list ( $key , $header ) = split( ":", $key );

                        if ( $header )
                        {
                            $buffer .= "<td  style='mso-number-format:General' align=center>". $header . "</td>";
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>". $header . "</td>";
                        }
                        else
                        {
                            $buffer .= "<td  style='mso-number-format:General' align=center>" . $this->convert_title( $key ) . "</td>";
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>" . $this->convert_title( $key ) . "</td>";
                        }
                }
                $buffer .= "</tr>\n";
        }
        fwrite($handle, $buffer);

        //====================================================
        //        
        // 주문 정보 가져옴.
        //     부분 검색의 조건에 대한 다운로드(금액별 등)
        //
        // 
        $is_download = 1;   // 0: 일반 조회, 1: 다운로드 조회
        $query = $this->get_query( $is_download );
        
        $dn_seqs = "";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $dn_seqs .= $dn_seqs ? "," : "";
            $dn_seqs .= $data[order_seq];   
        }
        
        global $status, $idx;        
        $tbl_name = "";
        if ( $type == "last" )
        {   
            $idx      = 0;                // idx보지 않는다.
        }
        
        $result = $this->get_list2( $download_config, $dn_seqs, $idx); 
    
        debug( "end of get list2" );

        //===================================================
        //
        // data부분 download
        //
        $cnt = 0;
        $seqs  = "";
        $packs = "";
        
        while ( $data = mysql_fetch_array ( $result ) )
        {        
            $cnt++;

            // seq 번호 리스트 구한다.
            if ( $data[pack] == 0 )
            {
                if ( $seqs != "" )
                    $seqs .= ",";
                
                $seqs .= $data[seq];
            }
            
            // 합포 번호 리스트 구한다.
            if ( $data[pack] != 0 )
            {
                if ( $packs != "" )
                    $packs .= ",";
                
                $packs .= $data[pack];
            }

            if ( $cnt%20 == 0 )        
            {
                echo "<script language='javascript'>parent.show_txt( $cnt )</script>";
                flush();
            }

            $buffer = "<tr>\n";
            $this->m_gift_count = 0;
            
            for( $i=0; $i < count( $download_items ); $i++ ) 
            {
                    list ( $key , $value ) = $download_items[$i];
                    list ( $key , $header, $min_max ) = split( ":", $key );

                    if ( preg_match( "/-/", $min_max) )
                    {
                        list ($min,$max ) = split ("-", $min_max );
                        $this->min_product = $min;
                        $this->max_product = $max - $min + 1;        // max에는 개수가 들어가야 함
                    }
                    else
                    {
                        $this->max_product = $min_max;
                        $this->min_product = 1;
                    }

                    if ( $key == "packs" )
                    {
                       $buffer .= $this->get_data( $data, $key, $value );
                    }
                    else if ( $key == "none" )
                        continue;                                
                    else
                    {
                        if ( $key == "packs" )
                        {
                            $buffer .= "<td style='mso-number-format:General'>" . $this->get_data( $data, $key, $value ) . "</td>";
                        }
                        else
                        {
                            $buffer .= "<td style='mso-number-format:\"\\@\"'>" . $this->get_data( $data, $key, $value ) . "</td>";
                        }
                    }
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
            $buffer = "";
        }
    
        // footer 기록
        fwrite($handle, "</table>");
        
        echo "<script language='javascript'>
                   parent.hide_waiting()
                   parent.download('$filename')
              </script>";
        flush();
        
    }
    
    //
    // 작업2의 결과 파일을 다운로드 받을 경우 사용 
    // current 사용 부분 - 2013.1.7 - jkryu
    //
    function build_file2()
    {
        global $connect, $saveTarget, $download_type;
        global $trans_corp, $trans_format, $v_trans_format;
        global $start_date, $end_date;
        global $warehouse,$job_idx;

        // Lock Start
        $obj_lock = new class_lock(301);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            return;
        }
        
        // save file 생성
        $filename = Date(Ymd) . "_" . rand(1,10000) . "_" .$_SESSION['LOGIN_ID'];
        $saveTarget = _upload_dir . $filename;
    
        echo "<script language='javascript'>parent.show_waiting()</script>";
    
        echo "build_file: saveTarget: $saveTarget <br>";

        // trans_format이 전달되지 않는 경우가 있음
        $trans_format = $trans_format ? $trans_format : $v_trans_format;

        // 환경정보 가져온다
        $download_config[is_header] = 0;
        $download_config[pack_multiline] = 0;
        $download_config[sortinfo] = 0;
        $download_config[is_system] = 0;
        $download_config[product_sum] = 0;

        // sys_trans_info에서 받아 옴
        $query = "select * from trans_conf where trans_corp='$trans_corp' and format_id='$trans_format'";

        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        $download_config[is_header] = $data[is_header];
        $download_config[pack_multiline] = $data[pack_multiline];
        $download_config[sortinfo] = $data[sortinfo];
        $download_config[is_system] = $data[is_system];
        $download_config[product_sum] = $data[product_sum];
        
        $this->m_download_config = $download_config;

        //==================================
        // format정보 다운로드
        // header 정보 다운로드
        $download_items = $this->get_format( $download_config );

        // file open
        $handle = fopen ($saveTarget, "w");

        //====================================================
        //        
        // header 출력 부분 결정
        // date: 2007.3.19 - jk
        //
        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

        if ( $download_config[is_header] )
        {
                $buffer .= "<tr>";

                for( $i=0; $i < count( $download_items ); $i++ ) 
                {
                        list ( $key , $value ) = $download_items[$i];
                        list ( $key , $header ) = split( ":", $key );

                        if ( $header )
                        {
                            $buffer .= "<td  style='mso-number-format:General' align=center>". $header . "</td>";
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>". $header . "</td>";
                        }
                        else
                        {
                            $buffer .= "<td  style='mso-number-format:General' align=center>" . $this->convert_title( $key ) . "</td>";
                            //$buffer .= "<td  style='mso-number-format:\@' align=center>" . $this->convert_title( $key ) . "</td>";
                        }
                }
                $buffer .= "</tr>\n";
        }
        fwrite($handle, $buffer);

        //====================================================
        //        
        // 주문 리스트 가져온다 
        // date: 2007.3.19 - jk
        //
        // 회수 추가 2007.5.8 - jk
        global $status;        
        $result = $this->get_list2( $download_config ); 

        //===================================================
        //
        // data부분 download
        //
        $cnt = 0;
        $seqs  = "";
        $packs = "";
        
        while ( $data = mysql_fetch_array ( $result ) )
        {        
            $cnt++;

            // seq 번호 리스트 구한다.
            if ( $data[pack] == 0 )
            {
                if ( $seqs != "" )
                    $seqs .= ",";
                
                $seqs .= $data[seq];
            }
            
            // 합포 번호 리스트 구한다.
            if ( $data[pack] != 0 )
            {
                if ( $packs != "" )
                    $packs .= ",";
                
                $packs .= $data[pack];
            }
            

            if ( $cnt%20 == 0 )        
            {
                echo "<script language='javascript'>parent.show_txt( $cnt )</script>";
                flush();
            }

            $buffer = "<tr>\n";
            $this->m_gift_count = 0;
            
            for( $i=0; $i < count( $download_items ); $i++ ) 
            {
                    list ( $key , $value ) = $download_items[$i];
                    list ( $key , $header, $min_max ) = split( ":", $key );

                    if ( preg_match( "/-/", $min_max) )
                    {
                        list ($min,$max ) = split ("-", $min_max );
                        $this->min_product = $min;
                        $this->max_product = $max - $min + 1;        // max에는 개수가 들어가야 함
                    }
                    else
                    {
                        $this->max_product = $min_max;
                        $this->min_product = 1;
                    }

                    if ( $key == "packs" )
                    {
                       $buffer .= $this->get_data( $data, $key, $value );
                    }
                    else if ( $key == "none" )
                        continue;                                
                    else
                    {
                        if ( $key == "packs" )
                        {
                            $buffer .= "<td style='mso-number-format:General'>" . $this->get_data( $data, $key, $value ) . "</td>";
                        }
                        else
                        {
                            $buffer .= "<td style='mso-number-format:\"\\@\"'>" . $this->get_data( $data, $key, $value ) . "</td>";
                        }
                    }
            }


            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
            $buffer = "";
        }
    
        // footer 기록
        fwrite($handle, "</table>");
    
        // download일 저장함
        $this->set_download_date( $seqs, 0 );
        $this->set_download_date( $packs, 1 );            
    
        //
        // job_idx로 file명을 기록한다. 2012-01-03 - jkryu
        // 
        $query = "update print_history set file_name='$filename' where idx=$job_idx";
        mysql_query( $query, $connect );
        
        echo "<script language='javascript'>
                   parent.hide_waiting()
                   parent.download('$filename')
              </script>";
        flush();

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }
    }
    
    // 파일 다운로드..
    function excel()
    {
        global $connect, $seq;
        
        $query  = "select * from print_history where idx=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 파일 다운로드
        header("Content-type: application/vnd.ms-excel");
        //header("Content-Disposition: attachment; filename=" . iconv("utf-8","cp949",$data[old_fn]));
        header("Content-Disposition: attachment; filename=" . iconv("utf-8","cp949","출력불가" . $data[file_name] . ".xls"));
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: public");
        
        $saveTarget2 = "/home/ezadmin/public_html/shopadmin/data/" . _DOMAIN_ . "/" . $data[file_name];
        if (is_file($saveTarget2)) { 
              $fp = fopen($saveTarget2, "r");   
              fpassthru($fp);  
        } 
        
        ////////////////////////////////////// 
        // file close and delete it 
        fclose($fp);    
    }

    //
    // history에 저장
    //
    function insert_history( $data, $job_idx )
    {
        global $connect;
        $query = "insert into print_enable_history set  
                idx          = $job_idx
                ,order_seq   = '$data[order_seq]'
                ,product_seq = '$data[product_seq]'
                ,pack        = '$data[pack]'
                ,product_id  = '$data[product_id]'
                ,location    = '$data[location]'
                ,qty         = '$data[qty]'
                ,status      = '$data[status]'
                ,is_pack     = '$data[is_pack]'
                ,is_stock    = '$data[is_stock]'
                ,is_deliv_all = '$data[is_deliv_all]'
                ,org_pack     = '$data[org_pack]'
                ,total_qty    = '$data[total_qty]'
                ,crdate       = Now()
                ,is_work      = '$data[is_work]'
                ,amount       = '$data[amount]'
                ";
        //debug( $query );
        mysql_query( $query, $connect );
    }
    
    //********************************************
    // 생성된 file을 download시켜 줌
    // 2009.8.7 - jk
    function download_file()
    {
        global $file;
        $obj = new class_file();
        $obj->download_file( $file, "job2_OrderDownload_" . date("mdHis") . ".xls");
        
    }
    

        //=======================================
        // 실제 download
        function download2()
        {
            // excel로 변경
            global $filename, $html_download;
            $saveTarget = _upload_dir . $filename;

            if ( !$html_download )
            {
                    $saveTarget2 = $saveTarget . "_[order].xls";

                        //print "save: $saveTarget \n";
                        //print "save2: $saveTarget2 \n";
                        //exit;

                    $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
                    exec( $run_module );
            }
            else        
                    $saveTarget2 = $saveTarget;

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=orderdownload_" . date('Ymd') . ".xls");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
            header("Pragma: public");
    
        if (is_file($saveTarget2)) { 
              $fp = fopen($saveTarget2, "r");   
              fpassthru($fp);  
        } 

        ////////////////////////////////////// 
        // file close and delete it 
            // file은 보관함
        fclose($fp);
            if ( !$html_download )
            {
                    //unlink($saveTarget);
                    // unlink($saveTarget2);
            }
            //else
                //unlink($saveTarget2);
    }

  //=============================================
  //
  // 판매처가 20002인지 여부 check 
  // date: 2007.6.12 - jk.ryu
  // 황대리 요청 사항
  //
  function check20002( $pack )
  {
        global $connect;

        $query = "select supply_id from orders where pack='$pack'";
        $result = mysql_query ( $query, $connect ) or die(mysql_error());;

        // return 값은 false
        $ret = 0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
                if ( $data[supply_id] == "20002" )
                        $ret = 1;
        }

        return $ret;        
  }
  
  //=============================================
  //
  // convert_title table로부터 data가져옴
  // date: 2007.3.27 - jk.ryu
  //
  function convert_title( $key )
  {
        switch ( $key )
        {
                case "seq":          return "주문번호"; break;
                case "order_id":     return "체결번호"; break;
                case "shop_name":    return "사이트명"; break;
                case "recv_name":    return "고객명"; break;
                case "recv_zip":     return "우편번호"; break;
                case "recv_zip_only":return "우편번호"; break;
                case "recv_address": return "주소"; break;
                case "recv_tel":     return "전화번호1"; break;
                case "recv_mobile":  return "전화번호2"; break;
                case "qty":          return "수량"; break;
                case "qty_only":     return "수량"; break;
                case "trans_who":    return "배송료구분"; break;
                case "memo":         return "배송시유의사항"; break;
                case "pack":         return "관리번호2"; break;
                case "packs":        return "상품"; break;
                case "collect_date": return "발주일"; break;
                default :
                        return $key;
        }
  }

  //=============================================
  //
  // 재고수량만큼만 배송하기에 사용 중(작업 2)
  // dn_seqs : download할 
  // tbal_name : print_enable, print_enbale_history
  // idx : 0 => print_enable / idx > 0 => print_enable_history;
  function get_list2( $download_config, $dn_seqs="", $idx=""  )
  {
        global $connect, $shop_id, $supply_code, $trans_who, $status, $confirm, $warehouse, $deliv_priority;
        global $enable_sale, $deliv_all,$reserve_order, $group_id;
        
        // 선주문 배송예약일 경우 $shop_id, $supply_code를 보고 
        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        // $status 
        // deliv_all -1 : 전체 , 0: 배송불가, 1: 배송가능
        $_status = "3";
        
        if ( $deliv_all == -1 )
        {
            $_status = "";
        }
        else if ( $deliv_all === 0 )
        {
            $_status = "1,2";   
        }
        
        //================================================
        //
        // 합포가 한 줄인지 여부 check
        // date: 2007.3.19 - jk.ryu
        //
        // 합포가 여러줄
        if ( $download_config[pack_multiline] )
        {
            if ( $idx > 0 )
            {
                $query = "select order_seq,sum(qty) sqty 
                            from print_enable_history
                           where idx = $idx
                             and order_seq in ( $dn_seqs )
                           group by order_seq";
            }
            else
            {
                if ( $_status )
                {
                    $query = "select order_seq,sum(qty) sqty 
                                from print_enable 
                               where status in ($_status) 
                               group by order_seq";    
                }
                else
                {
                    $query = "select order_seq,sum(qty) sqty 
                            from print_enable 
                           group by order_seq";    
                }
                
            }
            
            if ( $deliv_priority == "total_qty" )
                $query .= " order by sqty, order_seq";
            
            $result = mysql_query( $query, $connect );
            $ids = "";
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $ids .= $ids ? "," : "";
                $ids .= $data[order_seq];
            }
            
            $query = "select a.shop_id,a.order_id,a.seq,a.pack,a.recv_name,a.recv_zip,a.recv_address,order_address,a.gift,a.memo
                              ,a.trans_who
                              ,b.qty
                              ,a.recv_tel
                              ,a.recv_mobile
                              ,a.collect_date
                              ,a.cross_change
                              ,b.product_id
                              ,b.order_cs
                              ,b.cancel_date    
                              ,b.change_date    
                              ,b.refund_price   
                              ,b.shop_id        
                              ,b.shop_product_id
                              ,b.shop_options   
                              ,b.marked         
                              ,b.status         
                              ,b.match_date     
                              ,b.supply_id      
                              ,b.shop_price     
                              ,b.extra_money    
                              ,b.no_save        
                              ,b.location       
                              ,b.is_gift    
                              ,b.misong
                              ,a.code30    
                              ,a.code3
                              ,a.code1,a.code2,a.code3,a.code4,a.code5,a.code6,a.code7
                              ,a.code8,a.code9,a.code10,a.code11,a.code12,a.code13,a.code14
                              ,a.code15,a.code16,a.code17,a.code18,a.code19,a.code20,a.code21
                              ,a.code22,a.code23,a.code24,a.code25,a.code26,a.code27,a.code28
                              ,c.product_id       
                              ,c.supply_code      
                              ,c.name             
                              ,c.origin           
                              ,c.brand            
                              ,c.options          
                              ,c.org_price        
                              ,c.supply_price     
                              ,c.shop_price       
                              ,c.org_id           
                              ,c.enable_sale      
                              ,c.sale_stop_date   
                              ,c.sale_start_date  
                              ,c.is_delete        
                              ,c.stock_manage     
                              ,c.current_stock    
                              ,c.safe_stock       
                              ,c.delete_date      
                              ,c.market_price     
                              ,c.trans_fee
                              ,c.org_code         
                              ,c.packed           
                              ,c.org_seq          
                              ,c.is_store         
                              ,c.barcode          
                              ,c.barcode_type     
                              ,c.trans_code    
                              ,c.vat              
                              ,c.price_code       
                              ,c.represent_id     
                              ,c.location   p_location      
                              ,c.category         
                              ,c.enable_stock     
                              ,c.is_represent     
                              ,c.link_id          
                              ,e.sort_name
                              ,a.order_date
                              ,a.order_time
                              ,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                        from orders a
                             ,products c
                             ,order_products b
                             ,shopinfo e
                       where a.seq in ( $ids )
                         and a.seq = b.order_seq
                         and b.product_id = c.product_id
                         and a.shop_id = e.shop_id
                         and a.order_cs not in (1,3)
                         and b.order_cs not in (1,2,3,4)
                         ";
                         
                        if ( $reserve_order == 1  )
                        {
                            $query .= " and a.shop_id in ( $shop_id ) ";   
                        }
                         
        }
        // 합포가 한 줄
        else
        {
            // 
            $query = "select a.shop_id,a.order_id,a.seq,a.pack,a.recv_name,a.recv_zip,a.recv_address,a.order_address,a.gift,a.memo,c.name product_name
                            ,a.recv_tel, a.recv_mobile,a.trans_who,a.order_name,a.collect_date,a.cross_change,b.misong,a.code30
                            ,a.code1,a.code2,a.code3,a.code4,a.code5,a.code6,a.code7
                            ,a.code8,a.code9,a.code10,a.code11,a.code12,a.code13,a.code14
                            ,a.code15,a.code16,a.code17,a.code18,a.code19,a.code20,a.code21
                            ,a.code22,a.code23,a.code24,a.code25,a.code26,a.code27,a.code28
                            ,c.product_id       
                            ,c.supply_code      
                            ,c.name             
                            ,c.origin           
                            ,c.brand            
                            ,c.options          
                            ,c.org_price        
                            ,c.supply_price     
                            ,c.shop_price       
                            ,c.org_id           
                            ,c.enable_sale      
                            ,c.sale_stop_date   
                            ,c.sale_start_date  
                            ,c.is_delete        
                            ,c.stock_manage     
                            ,c.current_stock    
                            ,c.safe_stock       
                            ,c.delete_date      
                            ,c.market_price     
                            ,c.org_code         
                            ,c.packed           
                            ,c.org_seq          
                            ,c.is_store         
                            ,c.barcode          
                            ,c.barcode_type     
                            ,c.trans_code       
                            ,c.trans_fee
                            ,c.vat              
                            ,c.price_code       
                            ,c.represent_id     
                            ,c.location        p_location 
                            ,c.category         
                            ,c.enable_stock     
                            ,c.is_represent     
                            ,c.link_id          
                            ,a.code3
                            ,e.sort_name
                            ,sum(d.qty) qty
                            ,a.order_date
                            ,a.order_time
                            ,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx";
                            
                if ( $idx > 0 )
                {
                   $query .= " from orders a
                             ,print_enable_history d
                             ,order_products b
                             ,products c
                             ,shopinfo e
                       where d.idx = $idx
                         and a.seq = d.pack
                         and a.seq = b.order_seq
                         and b.product_id = c.product_id
                         and a.shop_id = e.shop_id
                         ";    
                }
                else
                {          
                   if ( $_status )
                   {                  
                       $query .= " from orders a
                                 ,print_enable d
                                 ,order_products b
                                 ,products c
                                 ,shopinfo e
                           where a.seq = d.pack
                             and a.seq = b.order_seq
                             and b.product_id = c.product_id
                             and a.shop_id = e.shop_id
                             and d.status in ( $_status )";
                    }
                    else
                    {
                       $query .= " from orders a
                                 ,print_enable d
                                 ,order_products b
                                 ,products c
                                 ,shopinfo e
                           where a.seq = d.pack
                             and a.seq = b.order_seq
                             and b.product_id = c.product_id
                             and a.shop_id = e.shop_id";
                    }
                }

            // 선주문 배송예약의 경우 2013.1.7 - jkryu
            if ( $reserve_order == 1  )
            {
                $query .= " and a.shop_id in ( $shop_id ) ";   
            }                       
                         
            //
            // 과거자료에서 자료를 다운 받는경우..합포가 한 줄 case
            // 
            if ( $dn_seqs )
            {
                $query .= " and a.seq in ( $dn_seqs )";
            }
            
            $query .= "  group by d.pack";
        }

		// voiceone의 경우 특정 공급처순으로 sorting을 요청 함. 2011.12.10 - jk
		if ( _DOMAIN_ == "voiceone" )
        {
			$query .= " order by c.supply_code=20004 desc, c.supply_code=20003 desc, c.supply_code=20006 desc";
            if ( $download_config[sortinfo] )
            {
                $query .= "," . $this->get_sort_info($download_config[sortinfo],$download_config);
            }   
    	}
		else
		{ 
            if ( $download_config[sortinfo] )
            {
                // $query .= " order by " . $download_config[sortinfo];
                $query .= " order by " . $this->get_sort_info($download_config[sortinfo],$download_config);
            }   
		}
        
        //debug( "build file2 query: $query ");
        //exit;
        
        $result = mysql_query ( $query, $connect );
        return $result;
  }
    
    //
    // sort문구를 정렬해준다.
    // 2011.3.7 - jkryu
    function get_sort_info( $sortinfo,$download_config="" )
    {
        $sortinfo = preg_replace("/pack/"                ,"a.pack"                 , $sortinfo );
        $sortinfo = preg_replace("/seq/"                 ,"a.seq"                  , $sortinfo );
        $sortinfo = preg_replace("/shop_id/"             ,"a.shop_id"              , $sortinfo );
        $sortinfo = preg_replace("/product_id/"          ,"b.product_id"           , $sortinfo );
        $sortinfo = preg_replace("/options/"             ,"c.options"              , $sortinfo );       // products.option => c
        $sortinfo = preg_replace("/shopoption/"          ,"b.shop_options"         , $sortinfo );
        $sortinfo = preg_replace("/product_name/"        ,"c.name"                 , $sortinfo );
        $sortinfo = preg_replace("/shop_productname/"    ,"a.product_name"         , $sortinfo );
        $sortinfo = preg_replace("/shop_productid/"      ,"a.shop_product_id"      , $sortinfo );
        $sortinfo = preg_replace("/sort_name/"           ,"e.sort_name"            , $sortinfo );
        $sortinfo = preg_replace("/order_date/"          ,"order_date, order_time" , $sortinfo );
        
        if ( $download_config[pack_multiline] )
        {
            $sortinfo = preg_replace("/qty desc,/"           ,"" , $sortinfo );
            $sortinfo = preg_replace("/,qty desc/"           ,"" , $sortinfo );
        }
            
        return $sortinfo;
    }
    
    //=============================================
    //
    // 2011.2.18 - jk
    // seq=pack인 주문이 취소가 되어도 합포중 정상이 한 건이라도 있으면 다운로드 됨
    //
    function get_list_new( $download_config )
    {
        global $connect, $shop_id, $supply_code, $trans_who, $status, $confirm, $warehouse;
        global $enable_sale,$group_id,$s_group_id,$start_date, $end_date;

        global $start_hour, $end_hour;

        // 보류제외
        $except_hold = $this->is_except_hold();

        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
        
        //================================================
        //
        // 합포가 한 줄인지 여부 check
        // date: 2007.3.19 - jk.ryu
        //
        // 합포가 여러줄        
        if ( $download_config[pack_multiline] )
        {
            // ggstory의 경우 각 주문당 1개의 정보만...출력함.
            if ( _DOMAIN_ == "ggstory" )
            {
                $query = "select *,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx from orders 
                           where status = 1 and order_cs not in (1,3)";
                
                if ( $shop_id )
                    $query .= " and shop_id in ( $shop_id )";
                    
                // 보류 출력안함
                if ( $except_hold )
                    $query .= " and hold=0 ";
            
                if ( $supply_code )
                    $query .= " and supply_id in ( $supply_code ) ";
                
                if ( $start_date )
                {
                    $query .= " and ( collect_date >= '$start_date'";
                    
                    if ( $start_hour )
                        $query .= " and collect_time >= '$start_hour:00:00'";
                    
                    $query .= " )";
                }
                
                if ( $end_date )
                {
                    $query .= " and end_date <= '$end_date'";
                    
                }
                
                $query .= " order by product_name";
            }
            else
            {
                // b.seq는 .. product_seq
                $query = "select b.*, a.order_date,a.order_time,a.seq,a.pack,a.recv_name,a.recv_zip,a.recv_address,a.order_address,a.gift,a.memo,a.order_id,a.options
                                ,a.recv_mobile,a.recv_tel,a.order_name,a.trans_who,a.supply_price,a.amount,a.collect_date
                                ,b.seq product_seq, b.marked,a.options,a.gift,a.cross_change,a.shop_id,b.seq as product_seq,c.org_price as org_price
                                ,a.code1,a.code2,a.code3,a.code4,a.code5,a.code6,a.code7,a.code8,a.code9,a.code10
                                ,a.code11,a.code12,a.code13,a.code14,a.code15,a.code16,a.code17,a.code18,a.code19,a.code20
                                ,a.code21,a.code22,a.code23,a.code24,a.code25,a.code26,a.code27,a.code28,a.code29,a.code30
                                ,b.is_gift,b.misong,a.code30,a.code10,a.order_tel,a.order_mobile
                                ,e.sort_name,a.seq order_seq,c.location p_location
                                ,c.trans_fee, a.trans_corp
                                ,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                            from orders a, order_products b, products c,shopinfo e
                   where a.seq        = b.order_seq
                     and b.product_id = c.product_id
                     and a.shop_id    = e.shop_id";
                     
                if ( $status == 1 ) // 정상
                {
                    $query .= " and a.status in (1) 
                                and a.order_cs in ( 0,2,4 )  
                                and substring(order_id,1,1)<>'C'";
                }
                elseif ( $status == 7 ) // 송장
                {
                    $query .= " and a.status in (7)  
                                and a.order_cs not in (1,3)";
                }
                elseif ( $status == 2 ) // 교환
                {
                    $query .= " and (((a.status=1 and a.order_cs in ( 5,6,7,8 )) 
                                 or (a.status=1 and a.order_cs not in (1,3) 
                                     and substring(order_id,1,1)='C')))";            
                }
                else
                {
                    $query .= " and a.status in (1)  
                                and a.order_cs not in (1,3)";
                }
                
                // 보류 출력안함
                if ( $except_hold )
                    $query .= " and a.hold=0 ";
                           
                if ( $shop_id )
                    $query .= " and a.shop_id in ( $shop_id )";
    
                if ( $supply_code )
                    $query .= " and b.supply_id in ( $supply_code )";
                 
                if ( $start_date )
                {
                    $query .= " and (a.collect_date >= '$start_date' ";
                    
                    if ( $start_hour )
                        $query .= " and a.collect_time >= '$start_hour:00:00'";
                    
                    $query .= ")";
                }
                
                if ( $end_date )
                {
                    $query .= " and (a.collect_date <= '$end_date' ";
                    
                    if ( $end_hour )
                        $query .= " and a.collect_time <= '$end_hour:59:59'";
                        
                    $query .= ")";
                }
                 
                 $query .= "  and b.order_cs not in (1,2,3,4)"; // 부분 취소는 다운 받는다.
            }
            
            // 은성은 보류를 다운받지 않는다.
            if ( _DOMAIN_ == "eshealthvill" )
            {
                $query .= " and a.hold=0 ";
            }
        }
        // 합포가 한 줄
        else
        {
            $query = "select a.*,b.marked,b.seq as product_seq,b.product_id,a.code4,a.code11,b.is_gift,b.misong
                            ,e.sort_name
                            ,a.seq order_seq
                            ,b.seq product_seq
                            ,c.location p_location
                            ,if(a.pack<>null||a.pack<>0,a.pack, a.seq ) xx
                            ,count(*) qty
                            ,sum(b.qty) sum_products
                        from orders a, order_products b,products c,shopinfo e
                       where a.seq        = b.order_seq 
                         and b.product_id = c.product_id
                         and a.shop_id    = e.shop_id";
                                          
            if ( $status == 1 ) // 정상
            {
                $query .= " and a.status in (1) and a.order_cs in ( 0,2,4 )  and substring(order_id,1,1)<>'C'";
            }
            elseif ( $status == 2 ) // 교환
            {
                $query .= " and ((a.status=1 and a.order_cs in ( 5,6,7,8 )) 
                            or (a.status=1 and a.order_cs not in (1,3) and substring(order_id,1,1)='C'))";
            }
            elseif ( $status == 7 ) // 송장
            {
                $query .= " and a.status in (7)  and a.order_cs not in (1,3)";                                            
            }
            else
            {
                $query .= " and a.status in (1)  and a.order_cs not in (1,3)";                                            
            }
            
            // 보류 출력안함
            if ( $except_hold )
                $query .= " and a.hold=0 ";
            
            if ( $shop_id )
                $query .= " and a.shop_id in ( $shop_id )";

            if ( $supply_code )
                $query .= " and b.supply_id in ( $supply_code ) ";
            
            if ( $start_date )
            {
                $query .= " and (a.collect_date >= '$start_date' ";
                
                if ( $start_hour )
                    $query .= " and a.collect_time >= '$start_hour:00:00'";
                
                $query .= ")";
            }
            
            if ( $end_date )
            {
                $query .= " and (a.collect_date <= '$end_date' ";
                
                if ( $end_hour )
                    $query .= " and a.collect_time <= '$end_hour:59:59'";
                    
                $query .= ")";
            }
            
            if ( _DOMAIN_ == "eshealthvill" )
            {
                $query .= " and a.hold=0 ";
            }
            
            $query .= " group by xx ";
        }
        
        if ( _DOMAIN_ != "ggstory" )
        {
            if ( $download_config[sortinfo] )
            {
                //$query .= " order by " . $download_config[sortinfo];
                $query .= " order by " . $this->get_sort_info($download_config[sortinfo]);
            }
        }
        
        //debug( "xx1: " . $query );

        $result = mysql_query ( $query, $connect );
        return $result;
  }
    
    
  //=============================================
  //
  // trans_format table로부터 data가져옴
  // date: 2007.3.19 - jk.ryu
  //
  function get_list( $download_config )
  {
        global $connect, $shop_id, $supply_code, $trans_who, $status, $confirm, $warehouse;
        global $enable_sale,$group_id, $s_group_id;

        if ( $group_id != "")
        {
            $shop_id = $this->get_group_shop( $group_id );   
        }
        
        if ( $s_group_id != "")
        {
            $supply_code = $this->get_group_supply( $s_group_id );   
        }
        
        //================================================
        //
        // 합포가 한 줄인지 여부 check
        // date: 2007.3.19 - jk.ryu
    //
        // 합포가 여러줄
        
        if ( $download_config[pack_multiline] )
        {
            // b.seq는 .. product_seq
            $query = "select b.*, a.seq,a.pack,a.recv_name,a.recv_zip,a.recv_address,a.order_address,a.gift,a.memo,a.order_id,a.options
                            ,a.recv_mobile,a.recv_tel,a.order_name,a.trans_who,a.supply_price,a.amount,a.collect_date
                            ,b.seq product_seq, b.marked,a.options,a.gift,a.cross_change,a.shop_id,b.seq as product_seq,c.org_price as org_price
                            ,a.code4,a.code11,b.is_gift,b.misong,a.code30,a.code10
                            ,e.sort_name
                        from orders a, order_products b, products c, shopinfo e
               where a.seq        = b.order_seq
                 and a.shop_id    = e.shop_id
                 and b.product_id = c.product_id";
                 
            if ( $status == 1 ) // 정상
                $query .= " and a.status in (1) and a.order_cs in ( 0,2,4 )  and substring(order_id,1,1)<>'C'";
            elseif ( $status == 2 ) // 교환
                $query .= " and (((a.status=1 and a.order_cs in ( 5,6,7,8 )) 
                             or (a.status=1 and a.order_cs not in (1,3) and substring(order_id,1,1)='C')))";
            else
                $query .= " and a.status in (1)  and a.order_cs not in (1,3)";            
                            
            if ( $shop_id )
                $query .= " and a.shop_id in ( $shop_id )";
                
            if ( $supply_code )
                $query .= " and b.supply_id in ( $supply_code )";
                
             $query .= "  and b.order_cs not in (1,2,3,4)";
             
             
             
        }
        // 합포가 한 줄
        else
        {
            $query = "select a.*,b.marked,b.seq as product_seq,b.product_id,a.code4,a.code11,b.is_gift,b.misong
                            ,e.sort_name
                        from orders a, order_products b, shopinfo e
                       where a.seq = b.order_seq
                         and a.shop_id = e.shop_id
                         and (a.seq      = a.pack or a.pack=0)";
                                          
            if ( $status == 1 ) // 정상
                $query .= " and a.status in (1) and a.order_cs in ( 0,2,4 )  and substring(order_id,1,1)<>'C'";
            elseif ( $status == 2 ) // 교환
                $query .= " and ((a.status=1 and a.order_cs in ( 5,6,7,8 )) 
                            or (a.status=1 and a.order_cs not in (1,3) and substring(order_id,1,1)='C'))";
            else
                $query .= " and a.status in (1)  and a.order_cs not in (1,3)";                                            
            
            if ( $shop_id )
                $query .= " and a.shop_id in ( $shop_id )";

            if ( $supply_code )
                $query .= " and b.supply_id in ( $supply_code )";
                
            $query .= " and b.order_cs not in (1,3) group by b.order_seq ";
        
            
        }
        
        if ( $download_config[sortinfo] )
        {
            //$query .= " order by " . $download_config[sortinfo];
            $query .= " order by " . $this->get_sort_info($download_config[sortinfo]);
        }

        ////debug( "xx: " . $query );

        $result = mysql_query ( $query, $connect );
        return $result;
  }

  //=============================================
  //
  // trans_format table로부터 data가져옴
  // date: 2007.3.19 - jk.ryu
  //
  function get_format( $download_config )
  {
        global $connect, $trans_corp, $trans_format;
        
        // trans_format의 값은 unique함
        $query  = "select * from trans_format 
                            where format_id='$trans_format' and trans_id='$trans_corp' order by seq";

        $result = mysql_query ( $query, $connect );

        $arr_format = array();
        $i = 0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
                // echo htmlspecialchars($data[macro_value]);
                $this->convert_macrotokey ( $data[macro_value], &$key, &$value );
                // echo " : key->$key / $value <br>";

                //$arr_format[$key] =  "test";        
                $arr_format[$i] =  array( $key, $value );        
                $i++;
        }
        return $arr_format;
  }

  //=============================================
  //
  // macro 값을 $key와 $value로 변경
  //
  function convert_macrotokey( $macro , &$key, &$value )
  {
        switch ( $macro )
        {
                case "<order_id>";
                        $key = "order_id";
                        $value = "주문번호";
                break;
                default :  // < > 사이의 값이 나옴
                        if ( preg_match( "/[\<](.*)[\>](.*)/", $macro, $matches ) )
                        {
                                $key   = $matches[1];
                                $value = $matches[2];
                        }
                        else
                                echo "code 123: $macro | [$key] 입력값 오류";
        }
  }

  //========================================
  //
  // 값에 따라 사용자 정의 값이 변경된다.
  //
  function selector( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $macros = $matches[1];
                $arr_datas = split( "\|\|", $macros );

                foreach ( $arr_datas as $_data )
                {
                        list( $key, $value, $target ) = split( ":", $_data );
                        if ( $data[$key] == $value )
                                return $target;
                }
        }
        else
                return "매크로 값 오류";
  }

  //========================================
  //
  // 사용자 정의 값의 경우..(ok)
  //  date: 2007.3.20 - jk.ryu
  //
  function user_defined( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
                return $matches[1];
        else
                return "매크로 값 오류";
        
  }

  //====================================
  //
  //  trans_price 처리 부분
  //  도서지역일 경우 배송비가 다를 수 있음
  //  date: 2007.4.27 - jk
  //
  function trans_price( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $arr_macro = split( "\|\|", $matches[1] );

                $default_price = $arr_macro[0];

                for ( $i=1;$i < count( $arr_macro ) ; $i++ )
                {
                        //( $zip, $price ) = split ( ",", $arr_macro[$i] );
                        $_ = split ( ",", $arr_macro[$i] );

                        // if ( $data[recv_zip] == $_[0] )
                        if (preg_match("/$_[0]/i", $data[recv_zip] ) )
                                $default_price = $_[1];
                }
        }

        return $default_price;
  }


  //====================================
  //
  // recv_name 처리 부분
  //  합포가 여러줄에 나올 경우는 pack대신 product를 사용하면 됨
  //  date: 2007.3.27 - jk
  //
  function recv( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $arr_macro = split( "\|\|", $matches[1] );

                for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                {
                        $string .= $this->get_data( $data, $arr_macro[$i] ) . " ";
                }
        }

        return $string;
  }

  //
  // 구매자명과 수령자명이 다를경우 구매자명 출력..
  // 같을경우엔 꾸니스토리  
  // date: 2011.3.15 - jk
  // <is_present:헤더>[name||꾸니스토리]
  // 이름,주소 번화번호,zip
  function is_present( $data, $macro )
  {
    global $connect;
    preg_match( "/[\[](.*)[\]]/", $macro, $matches );
    $arr_macro = split( "\|\|", $matches[1] );
    
    $_switch     = $arr_macro[0];
    $sender_name = $arr_macro[1];
    
    // //debug( "s: $_switch / $sender_name" );
    
    $query = "select order_name   , recv_name 
                    ,order_zip    , recv_zip
                    ,order_address, recv_address
                    ,order_tel    , recv_tel
                    ,order_mobile , recv_mobile
                from orders where seq='$data[seq]'";
    $result = mysql_query( $query, $connect );
    $_data  = mysql_fetch_assoc( $result );
    
    // 이름이 다르면 주문자의 정보를 출력 
    // 이름이 같으면 쇼핑몰의 정보 출력.
    if ( $_switch == "name" )
    {
        if ( $data[order_name] != $data[recv_name] )
            $sender_name = $data[order_name] ? $data[order_name] : $sender_name;     
    }
    else if ( $_switch == "zip" )
    {
        if ( $data[order_name] != $data[recv_name] )
            $sender_name = $data[order_zip] ? $data[order_zip] : $sender_name;
    }
    else if ( $_switch == "address" )
    {
        if ( $data[order_name] != $data[recv_name] )
            $sender_name = $data[order_address]? $data[order_address] : $sender_name;
    }
    else if ( $_switch == "tel" )
    {
        if ( $data[order_name] != $data[recv_name] )
            $sender_name = $data[order_tel]? $data[order_tel] : $sender_name;
    }
    else if ( $_switch == "mobile" )
    {
        if ( $data[order_name] != $data[recv_name] )
            $sender_name = $data[order_mobile]? $data[order_mobile] : $sender_name;
    }
    return $sender_name;
  } 

  //====================================
  //
  // product 처리 부분
  //  합포가 여러줄에 나올 경우는 pack대신 product를 사용하면 됨
  //  date: 2007.3.27 - jk
  //
  function product( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $arr_macro = split( "\|\|", $matches[1] );

                for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                {
                    switch ( $arr_macro[$i] )
                    {
                        case "gift_product":
                            // order_product에 gift로 표시된 상품을 가져온다.
                            $string   .= $this->get_gift_product( $data[seq] );
                        break;
                        case "supply_price":
                            $arr_price = class_product::get_price_arr( $data[product_id], $data[shop_id] );
                            $string   .= number_format($arr_price[supply_price]) . "원";
                        break;
                        default:
                            $string .= $this->get_data( $data, $arr_macro[$i] );
                    }
                }
        }
        return $string;
  }

  function get_gift_product( $seq )
  {
    global $connect;
    
    $query = "select product_id from order_products where order_seq=$seq and is_gift=1";
    $result = mysql_query( $query, $connect );
    $str_result = "";
    while ( $data = mysql_fetch_assoc( $result ) )
    {
        $str_result .= $str_result ? "," : "";
        $str_result .= $this->get_product_name ( $data[product_id] );
    }   
    
    return $str_result;
  }

  function total_products( $data )
  {
        global $connect;
        //=================================================
        //
        // 합포일 경우와 합포가 아닐 경우가 있음//
        // 
        
        
        
        if ( $data[pack] )
        {
                $query = "select seq 
                            from orders
                           where pack = $data[pack]";
                             
                $result = mysql_query( $query, $connect );
        
                $total_products = 0;
                // memo의 특수 문자 삭네
                while ( $data1 = mysql_fetch_array ( $result ) )
                {
                    $total_products += $this->total_products_seq( $data1[seq] ) - $this->get_part_cancel($data1[seq]);
                }
                
        }
        else
        // 합포가 아닌 경우
        {
            $total_products = $this->total_products_seq( $data[seq] ) - $this->get_part_cancel( $data[seq] );
        }
        return $total_products;
  }

    // total_products_seq
        // date: 2009.8.20 - jk
    function total_products_seq( $seq )
    {
            global $connect;
            $query = "select sum(qty) total_qty from order_products where order_seq=$seq and order_cs not in (1,2,3,4)";
            
            $result = mysql_query( $query, $connect );
            $_data   = mysql_fetch_assoc( $result );
            return $_data[total_qty];
    }
    
    function get_order_products_options( $seq )
    {
        global $connect;
        $query = "select shop_options from order_products where seq=$seq";
        ////debug( "gopo: " . $query );
        $result = mysql_query( $query, $connect );
        $_options = "";
        while($_data   = mysql_fetch_assoc( $result ))
        {
            $_options .= strip_tags($_data[shop_options]);
        }
        ////debug( "    $_options ");
        return $_options;
    }
 
  //====================================
  //
  // memo 처리 부분
  //  date: 2007.3.27 - jk
  //
  function memo ( $data, $macro )
  {
        global $connect;

        //=================================================
        //
        // 교환일 경우
        // date: 2007.5.2 -jk
        if ( ($data[order_cs] == 5 or $data[order_cs] == 11) && _DOMAIN_ != "dammom")
            $memo_str .= "[[교환]] ";
        
        if ( substr($data[order_id],0,1) == "C" && _DOMAIN_ != "dammom")
        {
            $memo_str .= "[[배송 후 교환]] ";
        }
        
        //=================================================
        //
        // 합포일 경우와 합포가 아닐 경우가 있음//
        // 
        if ( $data[pack] )
        {
            $query = "select * from orders where pack='$data[pack]'";
            
            $result = mysql_query( $query, $connect );
    
            preg_match( "/[\[](.*)[\]]/", $macro, $matches );
            $arr_macro = split( "\|\|", $matches[1] );

            $total_products = 0;
            $total_order = 0;

            $before_memo = "";
            // memo의 특수 문자 삭제..
            $string = "";
            $string2 = "";
            
            $arr_datas = array();   // array에 값 저장
            
            while ( $data1 = mysql_fetch_array ( $result ) )
            {
                $clean_memo = str_replace( array("\r","\n","\r\n"), " ", $data1[memo] );
                $clean_options = str_replace( array("\r","\n","\r\n"), " ", $data1[options] );

                if ( $clean_memo != $before_memo )
                {
                    $string .= $clean_memo;
                    $before_memo = $clean_memo;
                }
                $string2 .= $clean_options;

                $total_order++;
                
                // total_products
                $total_products += $this->total_products_seq( $data1[seq] ) - $this->get_part_cancel($data1[seq]);  
                
                for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                {
                    $arr_datas [ $arr_macro[$i] ] .= $data1[$arr_macro[$i]];
                }
                
            } // end of while
            
            $before_memo = "";
            
            for ( $i=0;$i < count( $arr_macro ) ; $i++ )
            {
                switch ( $arr_macro[$i] )
                {
                    case "order_id":
                            if ( $data[pack] == $data[seq] )
                                $memo_str .= $data[order_id];
                        break;
                    case "total_order":
                        if ( $this->m_download_config[pack_multiline] )
                        {
                            if ( $data[pack] == $data[seq] )
                                $memo_str .= " [총" . $total_order . "개] " . $mango_memo;
                        }                            
                        else 
                            $memo_str .= " [총" . $total_order . "개] " . $mango_memo;
                                
                            break;
                    case "total_products":
                        if ( $this->m_download_config[pack_multiline] )
                        {
                            if ( $data[pack] == $data[seq] )
                                $memo_str .= " [총" . $total_products . "개] " ;
                        }                            
                        else 
                            $memo_str .= " [총" . $total_products . "개] " ;
                            break;
                    case "total_products_only":
                            $memo_str .= $total_products;
                            break;
                    case "memo":
                            // 합포가 여러줄 출력인 경우..메모를 한 번만 출력한다.
                            if ( $this->m_download_config[pack_multiline] )
                            {
                                if ( $data[pack] == $data[seq] )
                                    $memo_str .= strip_tags ( str_replace( array("\r","\n","\r\n"), " ", $string ) );
                            }                            
                            else                            
                                $memo_str .= strip_tags ( str_replace( array("\r","\n","\r\n"), " ", $string ) );
                                
                            break;
                    case "real_option":
                            $memo_str .= strip_tags ( str_replace( array("\r","\n","\r\n"), " ", $string2 ) );
                            break;
                    case "shop_name":
                            $memo_str .= $this->get_data( $data, "shop_name" );
                            break;
                    case "gift":
                            $memo_str .= $this->get_gift( $data[seq]);;
                            break;
                    default:
                            $memo_str .= $arr_datas[code4];
                            //$memo_str .= $this->get_data( $data1, $arr_macro[$i] );
                            break;
                }
                
                ////debug ( "arr macro: $arr_macro[$i] / " . $memo_str );
            } // end of for
            
            //====================================================
            //
            // total 금액을 보고 사은품이 있는지 check한다.
            //  date: 2007.4.4 - jk.ryu
            //if ( $data[gift] )        
            //        return "사은품: $data[gift] / $memo_str";
            //else
                    return $memo_str;
        }
        else
        // 합포가 아닌 경우
        {
                if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
                {
                        $arr_macro = split( "\|\|", $matches[1] );

                        $total_order = 1;

                        // total products
                    $total_products = $this->total_products_seq( $data[seq] ) - $this->get_part_cancel($data[seq]);

                        for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                        {
                                switch ( $arr_macro[$i] )
                                {
                                        case "total_order":
                                                $memo_str.= " [총" . $total_order . "개] " ;
                                                break;
                                        case "total_products":
                                                $memo_str.= " [총" . $total_products . "개] ";
                                                break;
                                        case "total_products_only":
                                                $memo_str .= $total_products;
                                        break;
                                        case "memo":
                                                $memo_str.= strip_tags(str_replace( array("\r","\n","\r\n"), " ", $data[memo] ));
                                                break;
                                        default:
                                                $memo_str .= $this->get_data( $data, $arr_macro[$i] );
                                }
                        }
                }


                //if ( $data[gift] )        
                //        return "사은품: $data[gift] / $memo_str";
                //else
                        return $memo_str;
        }


  }

  //====================================
  //
  // island 추가 중
  // date: 2007.9.4 - jk
  //  <island>[선불:2500||착불:5000]
  /*
  function island( $data, $macro )
  {
        if ( $data[island] )
        {
                if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
                {
                        $arr_macro = split( "\|\|", $matches[1] );

                        for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                        {
                                list( $key, $value ) = split( ":", $arr_macro[$i] );
                                $trans_who[$key] = $value;
                        }

                        return $trans_who[$data[trans_who]];

                }
                else
                        return "매크로 값 오류";
        }
  }
  */



  //====================================
  //
  // 우편번호로 배송비 체크 하는 로직 - 미완
  //  date: 2007.5.23 - jk
  //
  function trans_zip( $data, $macro )
  {
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $arr_macro = split( "\|\|", $matches[1] );

                for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                {
                        list( $key, $value ) = split( ":", $arr_macro[$i] );

                        // 도서지역 추가
                        // date: 2007.5.8 - jk
                        if ( $key == "island" )
                        {
                                if ( $data[island] == 1 ) 
                                        return $value;
                        }

                        $trans_who[$key] = $value;
                }

                return $trans_who[$data[trans_who]];

        }
        else
                return "매크로 값 오류";
  }

    // 
    // 2011.8.4 합포 중 정상상태의 선불이 1개 라도 있으면 선불설정
    //
    function get_trans_who( $pack )
    {
        global $connect;
        static $_trans_who = "착불";
        static $_query;
        static $_result;
        static $_data;
        
        $_query  = "select count(*) cnt from orders where pack=$pack and order_cs = 0 and trans_who='선불'";
        ////debug( $_trans_who . ":" . $_query );
        $_result = mysql_query( $_query, $connect );
        $_data   = mysql_fetch_assoc( $_result );
        
        if ( $_data['cnt'] > 0 )
        {
            $_trans_who = "선불";
        }
        ////debug( $_trans_who . ":" . $_data['cnt'] );
        
        return $_trans_who;
    }
  //====================================
  //
  // 선 착불 매크로...(ok)
  //  date: 2007.3.20 - jk
  //
  //  date: 2007.9.4 - 도서선불, 도서착불 추가
  //  2011.8.4 - jk
  //    합포 중 하나라도 선불이 있으면 선불
  //    일단 정지 2011.8.16 - jk 
  function trans_who( $data, $macro )
  {
        // $data[trans_who] 를 다시 가져온다.
        // 합포일 경우 전수 조사를 해서 취소된 선불이 하나이상일 경우 선불
        // 일단 정지 2011.8.16 - jk
        /*
        if ( $data[pack] )
        {
           $data[trans_who] = $this->get_trans_who( $data[pack] );
        }
        */
        
        if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
        {
                $arr_macro = split( "\|\|", $matches[1] );

                for ( $i=0;$i < count( $arr_macro ) ; $i++ )
                {
                        list( $key, $value ) = split( ":", $arr_macro[$i] );

                        //==========================
                        // 도서지역 추가
                        // date: 2007.5.8 - jk
                        // 도서선불, 도서착불이 생겨서 더이상 사용하지 않음
                        /*
                        if ( $key == "island" )
                        {
                                //==========================================================
                                // 기본 5000에 배송비 2500을 제해서 출력해야 하는거 아닌가?
                                // 아래의 로직이 왜 있는걸까? - 2007.9.5
                                if ( $data[island] == 1 ) 
                                        return $value - $data[trans_price] ? 2500 : 0;

                        }
                        */

                        $trans_who[$key] = $value;
                }

                  //  date: 2007.9.4 - 도서선불, 도서착불 추가
                if ( $data[island] == 1 )
                {
                        // island keyword에 값이 있을경우 처리, 선착불 구분없이 처리
                        if ( $trans_who[island] != '' )
                        {
                                return $trans_who[island] - $data[trans_price] ? 2500 : 0;
                        }
                        // island keyword에 값이 없을 경우-> 도서선불, 도서착불
                        else
                        {
                                if ( $data[trans_who] == "선불" )
                                        return $trans_who["도서선불"] ? $trans_who["도서선불"] : $trans_who["선불"];
                                else
                                        return $trans_who["도서착불"] ? $trans_who["도서착불"] : $trans_who["착불"];
                        }
                }
                else
                        return $trans_who[$data[trans_who]];

        }
        else
                return "매크로 값 오류";
  }

    ///////////////////////////////////////////////////
    // ( 2008.1.3 ) 복합 조건을 같은 단품 주문 처리 -jk
    //  ex) <composite>[name||option||value]
    function composite( $data, $macro )
    {
        // []의 수를 세어야 함
        preg_match_all("|[\[](.*)[\]]|U", $macro, $matches);
        // 매크로 케이스 체크
        $arr_macro = split( "\|\|", $matches[1][0] );

        for ( $i=0; $i < count ( $arr_macro ); $i++ )
        {
            $_key = $arr_macro[$i];
            $_str .= $this->get_data( $data, $_key );
        }
        return $_str;
    }

    function get_datas( $data,$arr_macro )
    {
        for ( $i=0; $i < count ( $arr_macro ); $i++ )
        {
            $_key = $arr_macro[$i];
            $_str .= $this->get_data( $data, $_key );
        }
        return $_str;
    }

  function get_part_cancel( $seq )
  {
        /*
        global $connect;
        // 여러개가 부분 취소될 경우 bug가 있었음 - 완료 2008.11.26 -jk
        $query = "select sum(qty) qty from part_cancel where order_seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data[qty];
        */
        return 0;
  }

  //====================================
  //
  // 합포가 한 줄에 나올 경우
  //  date: 2007.3.20 - jk
  //  get_pack_data( $pack, $arr_keys ) 
  //
  function packs ( $data, $macro )
  {
        // 합포 case check
        // date: 2007.3.26 - jk

        $this->m_star_refund_count = 0;
        $this->m_gift_count = 0;
        
        //========================================
        //
        // macro 정보 가져오기
        // case 1, 2, 3를 경정해야 함
        // date: 2007.3.26 - jk.ryu
        //
        // 매크로 무결성 체크
        preg_match_all("|[\[](.*)[\]]|U", $macro, $matches);

        // 매크로 케이스 체크
        if ( count($matches[0]) > 1 ) 
        {
                $macro_case = 1; // ex) <packs>[product_name][options][qty]
                $arr_macro = $matches[1];
        } 
        //=======================================================
        //
        // pack_list가 있을 경우엔 각 셀에 product_id를 넣어야 함
        // date:2007.4.16 - jk.ryu
        // 구분자: 길이: 사은품이 생김 2007.8.10
        //=======================================================
        else
        {
                $arr_macro = split( "\|\|", $matches[1][0] );

                if ( preg_match( "/구분자:(.*),길이:(.*)/", $arr_macro[0], $matches_temp ) )
                {
                        $macro_case = 2;

                        $_qty = ""; // init
                        $_qty = $qty;

                        $separator = $matches_temp[1];        

                        if ( $separator == "cr" )
                                $separator = "\n";

                        $max = $matches_temp[2];
                        // print "sep: $separator / $max <br>";
                }
                else
                {
                        // 각 셀에 data가 들어가는 경우?
                        $macro_case = 3;
                }
        }

        //==============================================================
        //
        // data 처리 부분
        // order_products로 부터 값을 가져와야 함
        //
        return $this->get_pack_datas ( $data[pack] ? $data[pack] : $data[seq], $arr_macro, $macro_case, $separator, $max );
  }
    
    //==================================
    // date: 2010.12.1 - jk
    function get_org_product_name( $seq )
    {
        global $connect;
        $query = "select product_name from orders where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array( $result );
        
        return $data[product_name];
    }

  //==================================
  // date: 2007.3.26 - jk.
  function get_product_name( $product_id )
  {
        global $connect;
        $query = "select name from products where product_id='$product_id'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array( $result );

        if ( _DOMAIN_ == "yuen" 
        or _DOMAIN_ == "yeowoowa"
        or _DOMAIN_ == "miraeak" )
                preg_match("/\[?\](.*)/", $data[name], $matches);

        if ( $matches[1] )
                $name = $matches[1];
        else
                $name = $data[name];

        return $name; 
  }

  // supply_name
  function _supply_name( $seq )
  {
        global $connect;
        
        $query = "select supply_id from order_products where seq=$seq limit 1";
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
        
        return $this->get_supply_name2( $_data[supply_id] );
  }

  function get_master_recvaddress( $seq )
  {
		global $connect;
		$query = "select recv_zip, recv_address,order_address,order_name,recv_name from orders where seq=$seq";
		$result = mysql_query( $query, $connect );
		$data   = mysql_fetch_assoc( $result );
		return $data;
  }

  function get_order_data( $seq, $field )
  {
    global $connect;
    
    $query = "select $field from orders where seq=$seq";
    $result = mysql_query( $query, $connect );
    $data   = mysql_fetch_assoc( $result );
    
    return $data;
  }

    //
    // 배송비 가져옴
    // 2011.2.28 jkryu
    function get_trans_fee( $pack )
    {
        global $connect;
        
        $query = "select trans_fee from orders where pack=$pack order by trans_fee desc limit 1";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[trans_fee];
    }

    // 
    // 지마켓 일본은 판매처에 수령자 정보를 지정할 수 있음
    // 
    function get_trans_info( $shop_id )
    {
        global $connect;   
        
        $query = "select use_trans_add,trans_address,trans_tel,trans_zip,trans_name from shopinfo where shop_id='$shop_id'";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data;
    }

    // 도서지역 여부 return
    function is_island( $data )
    {
        global $connect, $trans_corp;
        static $_query;
        static $_data;
        static $_result;
        
        $zipcode = str_replace("-","",$data[recv_zip]);
        $_query = "select count(*) cnt from island 
                   where zipcode='$zipcode' 
                     and trans_corp='$trans_corp'";   
                     
        $_result = mysql_query( $_query, $connect );
        $_data   = mysql_fetch_assoc( $_result );
        
        // //debug( $data[recv_zip] . "/" . $_query . "cnt: $_data[cnt]");
        if ( $_data[cnt] > 0 )
        {
            return "(도서지역)";   
        }                    
        else
        {
            return "";   
        }        
    }

    function get_order_memo( $data )
    {
        global $connect;
        
        if ( $data[pack] )
        {
            $query = "select memo from orders where pack=$data[pack]";
        }
        else
        {
            $query = "select memo from orders where seq=$data[seq]";
        }
        
        $result = mysql_query( $query, $connect );
        
        $_str = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $_str .= $data[memo] . ",";
        }
        
        return $_str;
    }

    // 상품의 data return
    function get_product_location( $data )
    {
        global $connect;
        $query = "select location from products where product_id='$data[product_id]'";
        ////debug( $query );
        $result =  mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        return $data[location];
    }
    
  //=================================================
  //
  // 값을 받아 옴
  //  date: 2007.3.19 - jk.ryu
  // option을 만들어 넣었음 option에는 다운로드 할 값을 조건이 들어감 [cnt|name|option|memo] 이런식임
  //   "|" : 셀을 구분 함 
  //
  function get_data( $data, $key , $macro = "" )
  {
        //
        // method 처리 되는 key들
        // trans_who: 선착불 처리
        // user_defined: 사용자 정의
        // packs: 합포 처리
        // product: 상품 정보
        //
        global $connect;
        if ( method_exists( $this, $key ) )
        {
            return $this->$key($data, $macro);
        }
        else
        {
            $arr_macro = split( "\&", $key );
            $string = "";
            if ( count( $arr_macro ) > 1 )
            {
                $string = $this->get_datas( $data, $arr_macro );
            }
            else
            {
                switch ( $key )
                {  
                    case " ":
                        $string .= " ";
                        break;
                     case "주문자":
                        $string .= "주문자:";
                        break;
                    case "님":
                        $string .= "님";
                        break;
                    case "p_location2":
                        $str_location = $this->get_product_location( $data );
                        
                        if ( $str_location )
                            $str_location = "(" . $str_location . ")";
                        else
                            $str_location = "";
                        
                        $string .= $str_location;
                        break;
                    case "p_location":
                        $str_location = $this->get_product_location( $data );
                        $string .= $str_location;
                        break;
                        
                    case "order_memo":
                        $string .= $this->get_order_memo( $data );
                        ////debug ( "order_memo: $string ");
                        break;
                    case "products_cnt":
                        $string .= $this->total_products( $data );
                        break;
                    case "supply_price":
                        $string .= number_format($data[supply_price])."원";
                        break;
                    case "island":
                        $string .= $this->is_island( $data );
                        break;
                    case "part_deliv":
                        if ( $this->str_part_trans( $data, &$message ) )
                        {
                            $string .= $message;       
                        }
                        break; 
                    case "sort_name":
                        $string .= $data[sort_name];
                        break;
                    case "product_trans_fee":
                            $string .= $data[trans_fee];
                        break;
                    case "trans_fee":
                        // 합포일 경우엔 가장 비싼 trans_fee가 출력된다. 2011.2.28 - jkryu
                        if ( $data[pack] )
                            $string .= $this->get_trans_fee( $data[pack] );
                        else
                            $string .= $data[trans_fee];
                            
                        break;
                    case "order_code11":
                        $_arr    = $this->get_order_data( $data[order_seq] ? $data[order_seq] : $data[seq], "code11" );
                        $string .= $_arr["code11"];
                        break;
                    case "order_code10":
                        $_arr    = $this->get_order_data( $data[order_seq] ? $data[order_seq] : $data[seq], "code10" );
                        $string .= $_arr["code10"];
                        break;   
                    case "recv_address":
                        // 주소인경우 shopinfo에서 use_trans_add값이 1일경우 trans_address, trans_tel, trans_zip을 찾는다.
                        $arr_trans_info = $this->get_trans_info( $data[shop_id] );
                        
                        if ( $arr_trans_info[use_trans_add] )
                        {
                            $string .= str_replace( $search,"", $arr_trans_info[trans_address] );
                        }
                        else
                        {
                            // 합포이고, 여러줄로 다운받는 경우 주소가 달라지는 경우가 있음.
                            if ( $this->m_download_config[pack_multiline] )
    						{
    						    //if ( $data[seq] <> $data[pack] )
    						    //{
       								$_data = $this->get_master_recvaddress( $data[pack] ? $data[pack] : $data[seq] ); 
    								$data[$key] = $_data[$key];
    						    //}
    						}
    						$string .= str_replace( $search,"", $data[$key]);
					    }
                        
                        break;
                    case "order_name":	                
                        $search  = array('<', '>', '  ');

						// 합포이고, 여러줄로 다운받는 경우 주소가 달라지는 경우가 있음.
                        if ( $this->m_download_config[pack_multiline] )
						{
						    //if ( $data[seq] <> $data[pack] )
						    //{
   								$_data = $this->get_master_recvaddress( $data[pack] ? $data[pack] : $data[seq] ); 
								$data[$key] = $_data[$key];
						    //}
						}

                        $string .= str_replace( $search,"", $data[$key]);
                        break;                    
					case "trans_name":
						global $trans_corp;
	                    $string .= $this->get_trans_corp_name( $data[trans_corp]);
					break;
	                case "recv_name":
	                    $arr_trans_info = $this->get_trans_info( $data[shop_id] );
                        if ( $arr_trans_info[trans_name] )
                        {
                            $string .= str_replace( $search,"", $arr_trans_info[trans_name] );
                        }
                        else
                        {
    						$search  = array('<', '>', '  ');
    
    						// 합포이고, 여러줄로 다운받는 경우 주소가 달라지는 경우가 있음.
                            if ( $this->m_download_config[pack_multiline] )
    						{
    						    //if ( $data[seq] <> $data[pack] )
    						    //{
       								$_data = $this->get_master_recvaddress( $data[pack] ? $data[pack] : $data[seq] ); 
    								$data[$key] = $_data[$key];
    						    //}
    						}
    
                            $string .= str_replace( $search,"", $data[$key]);
                        }
						break;
				    case "order_products_options":
				    case "order_products_option":
				    case "order_product_option":
				        $string .= $this->get_order_products_options( $data[product_seq] );
				        break;
                    case "shop_options":
                    case "shop_option":
                        // <주문선택사항>, <추가구성상품> 파일에 표시되도록
                        // order_products의 is_gift>0 이면 어드민 옵션 출력
                        // 2012.4.23 - jk - 장차장요청사항
                        if ( $data[is_gift] > 0 && _DOMAIN_ == "bgb2010" )
                        {
                            $_option_str .= class_D::get_product_option( $data[product_id]);       
                        }
                        else
                        {
                            $_option_str = str_replace( "<","[",$data[shop_options]);
                            $_option_str = str_replace( ">","]",$_option_str);    
                        }
                        debug( "is_gift: $data[is_gift] / $_option_str");                            
                            
                        $string .= str_replace( array("주문선택사항;","상품선택;"),"",$_option_str);

                        break;
                    case "is_gift":
                        if ( $data[is_gift] )
                            $string .= "[사은품]";
                        break;
                    case "cross_change":
                        if ( $data[cross_change] )
                            $string .= "[맞교환]";
                        break;                    
                    case "seq":
                        // xx를 사용해야 함 2011.8.3
                        global $trans_corp;
                        // 한진택배..30078일 경우 seq앞에 GS-를 붙여준다
                        if ( $trans_corp == 30078 )
                        {
                            if ( $data[shop_id] % 100 == 7 )
                            {
                                $string = "GS-";
                            }
                            $string .=  $data[xx];
                        }
                        else
                        {
                            // 여러줄일 경우 자동으로 합포번호 출력
                            // seq/pack 자동 선택
                            //if ( $this->m_download_config[pack_multiline] )
                            $string = $data[xx];
                            //else
                            //    $string = $data[seq];
                        }
                        /*
                        global $trans_corp;
                        // 한진택배..30078일 경우 seq앞에 GS-를 붙여준다
                        if ( $trans_corp == 30078 )
                        {
                            if ( $data[shop_id] % 100 == 7 )
                            {
                                $string = "GS-";
                            }
                            $string .=  $data[seq];
                        }
                        else
                        {
                            // 여러줄일 경우 자동으로 합포번호 출력
                            // seq/pack 자동 선택
                            //if ( $this->m_download_config[pack_multiline] )
                            $string = $data[pack] ? $data[pack] : $data[seq];
                            //else
                            //    $string = $data[seq];
                        }
                        */
                        break;
                    case "pack":
                        global $trans_corp;
                        // 한진택배..30078일 경우 seq앞에 GS-를 붙여준다
                        if ( $trans_corp == 30078 )
                        {
                            if ( $data[shop_id] % 100 == 7 )
                            {
                                $string = "GS-";
                            }
                            $string .=  $data[xx];
                        }
                        else
                        {
                            // 여러줄일 경우 자동으로 합포번호 출력
                            // seq/pack 자동 선택
                            //if ( $this->m_download_config[pack_multiline] )
                            $string = $data[xx];
                            //else
                            //    $string = $data[seq];
                        }
                        /*
                        // 한진택배..30078일 경우 seq앞에 GS-를 붙여준다
                        if ( $trans_corp == 30078 )
                        {
                            if ( $data[shop_id] % 100 == 7 )
                            {
                                $string = "GS-";
                            }
                            $string .=  $data[pack] ? $data[pack] : $data[seq];
                        }
                        else
                        {
                            // 여러줄일 경우 자동으로 합포번호 출력
                            // seq/pack 자동 선택
                            //if ( $this->m_download_config[pack_multiline] )
                            $string = $data[pack] ? $data[pack] : $data[seq];
                            //else
                            //    $string = $data[seq];
                        }
                        */
                        break;
                    case "max_weight":
                                $string = $this->max_weight($data);
                                break;
                    case "total_weight":
                                $string = $this->total_weight($data);
                                break;
                    case "max_weight_select":
                        // <max_weight_select>[3500:1||2500:0||3000:3]
                        $macro = str_replace( array("[","]"),"",$macro );
                        $arr_rule = split("\|\|", $macro);                        
                        $weight = $this->max_weight($data);
                        $arr_data = array();
                        for( $i=0; $i < count( $arr_rule ); $i++ )
                        {
                            $_arr = split(":", $arr_rule[$i] );
                            $arr_data[$_arr[0]] = trim($_arr[1]);
                        }
                        $string = $arr_data[$weight] ? $arr_data[$weight] : 0;
                        break;
                                  
                        case "supply_name":
                                // 복수줄 출력은 product_seq를 사용 한 줄 출력은 seq를 사용
                                ////debug("supply_name1: $data[order_seq]");
                                ////debug("supply_name2: $data[product_seq]");
                                
                                $product_seq = $data[product_seq] ? $data[product_seq] : $data[seq];
                                
                                $string = $this->_supply_name($product_seq);
                                break;
                        case "org_price":
                                
                                $arr_price = class_product::get_price_arr( $data[product_id], $data[shop_id]);
                                $string = $data[qty] * $arr_price[org_price];
                                break;
                        case "\$":
                                $string = "\$";
                                break;
                        case "(":
                                $string .= "(";
                                break;
                        case ")":
                                $string .= ")";
                                break;
                        case "[":
                                $string .= "[";
                                break;
                        case "개":
                                $string .= "개";
                                break;         
                        case "총":
                                $string .= "총";
                                break;                                    
                        case "번":
                                $string .= "번";
                                break;         
                        case "-":
                                $string .= "-";
                                break;
                        case "_":
                                $string .= "_";
                                break;
                        case "]":
                                $string .= "]";
                                break;
                        case "star":
                                $string .= "★";
                                break;
                        case "tri":
                                $string .= "▶";
                                break;
                        case "star_refund":
                                $this->m_star_refund_count++;
                                if ( $this->m_star_refund_count == 1 )
                                        $string = "…=★ 교환 & 반품 031-575-1890/1885 ★=…``$``$";
                                break;
                        case "gift":
                                //if ( $this->m_gift_count != 1 )
                                //{
                                ////debug("seq: $data[seq],pack: $data[pack], order_seq: $data[order_seq]" );
                                $string = $this->get_gift( $data[order_seq] ? $data[order_seq] : $data[seq]);
                                //$this->m_gift_count = 1;
                                //}
                                
                                break;
                        case "only_gift":
                                $this->m_gift_count++;
                                if ( $this->m_gift_count== 1 )
                                        $string .= $data[gift];
                                break;
                        case "exchange":
                                if ( $data[order_cs] == 9 )
                                        $string = "[맞교환]";
                                break;
                        case "supply_product_name":
                        case "brand":
                                $string .= class_product::get_brand_name( $data[product_id] );
                                break;
                        case "enable_sale":
                                $string .= class_product::enable_sale( $data[product_id] );
                                break;
                        case "origin":
                                $string .= class_product::get_origin( $data[product_id] );
                                break;
                        case "marked": // 본상품 추가 상품 구분
                                if ( $data[marked] == 1 )
                                    $string = "본상품";
                                else if ( $data[marked] == 2 )
                                    $string = "추가상품";
                                else
                                    $string = "";
                                    
                                break;
                        case "market_price":
                                $string = "(" . number_format( $data[market_price] ) . "원)";
                                break;
                        //
                        case "order_date":
                                if( _DOMAIN_ == 'hana' )
                                    $string = $data[order_date];
                                else
                                    $string = $data[order_date] ? $data[order_date] : $data[collect_date];
                                break;
                        case "order_time":
                                $string = $data[order_time];
                                break;
                        case "collect_date":
                                if( _DOMAIN_ == 'hana' )
                                    $string = $data[collect_date];
                                else
                                    $string = substr( $data[collect_date], 5,10 );
                                break;
                        case "collect_time":
                                if ( substr( $data[collect_time], 0,2 ) < 12 )
                                        $string = "(AM)";
                                else
                                        $string = "(PM)";

                                //$string = " " . substr( $data[collect_time], 0,2 ) . "시 ";
                                break;
                        // order date: 2007.7.30 추가 -jk
                        case "order":
                                $string = $this->m_order++;
                                break;
                        // zero , date: 2007-07-02 추가 - jk
                        case "zero":
                                $string = "0";
                                break;
                        // star
                        case "star":
                                $string = " # ";
                                break;
                        case "space":
                                $string = "  ";
                                break;
                        case "gift_title":
                                if ( $data[gift] )
                                $string = "(사은품: " . $data[gift] . ")";
                                else
                                        $string = "";
                                break;
                        // dnshop 10080일 경우 - yeowoowa의 경우
                        // 2007.5.7 dnshop
                        case "dnshop_only":
                                if ( $data[shop_id] == "10080" )
                                        $string = "dnshop";
                                break;
                        //===============================================
                        // memo 관련 date: 2007.4.20
                        // 
                        case "org_memo":
                                $string .= strip_tags ( str_replace( array("<br>","\r","\n","\r\n")," ",$data[memo]));
                                break;
                        //===============================================
                        // packs 관련 date: 2007.3.19
                        // 
                        case "x":
                                $string = " &nbsp; ";
                                break;
                        //==============================
                        // leedb case
                        // 황대리 요청 사항
                        case "product_and_option2":
                                $product_name = "";
                                $option = "";
                                if ( $data[product_id] )
                                {
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$option );        
                                        $string = "$product_name [$option]";
                                        $string = str_replace( array ("\r", "\r\n", "\n"),"", $string );
                                }
                                else
                                        $string = "pid: $data[product_id]";
                                break;

                        case "options_only":
                            if ( $_SESSION[STOCK_MANAGE_USE] )
                            {
                                if ( is_numeric( substr($data[product_id],0,1) ) )
                                        $string = $data[options];
                                else
                                        $string = str_replace( array ("\r", "\r\n", "\n"),"",class_D::get_product_option( $data[product_id]) );
                            }
                            else
                            {
                                if ( $data[options] )
                                        $string = $data[options];
                            }
                            # $string = nl2br ( $string );
                            $string = str_replace( array ("\n","\r","\r\n","<br>"),"/", $string);
                            $string = str_replace( array ("\"","\s",":"," "),"", $string);
                            break;
                        case "qty_product_and_option":
                                $product_name = "";
                                $product_option = "";

                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                {
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                                }
                                else
                                {
                                        $product_name = $this->get_product_name( $data[product_id] );        
                                        $product_option = $data[options];
                                }

                                if ( _DOMAIN_ == "leedb" || _DOMAIN_ == "tg2263" )
                                {
                                        $product_options = $product_option ? $product_option : $data[options];
                                        $product_options = "[" . str_replace( array ("\r", "\r\n", "\n"),"", $product_options) . "]";
                                }
                                else
                                        $product_options = $product_option;

                                // $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                // $string = $data[product_id] . $data[options] . "/";

                                $string .= $data[qty] . "개:" . $product_name."-". $product_options;
                                break;


                        case "product_and_option":
                                $product_name = "";
                                $product_option = "";
                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                {
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                                }
                                else
                                {
                                        $product_name = $this->get_product_name( $data[product_id] );        
                                        $product_option = $data[options];
                                }

                                if ( _DOMAIN_ == "leedb" || _DOMAIN_ == "tg2263" )
                                {
                                        $product_options = $product_option ? $product_option : $data[options];
                                        $product_options = "[" . str_replace( array ("\r", "\r\n", "\n"),"", $product_options) . "]";
                                }
                                else
                                        $product_options = $product_option;

                                // $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                // $string = $data[product_id] . $data[options] . "/";

                                $string .= $product_name."-". $product_options;
                                break;

                        //===============================
                        // ckcompany요청 사항
                        case "tomorrow":
                                $string .= date('Y-m-d', strtotime('+1 day'));
                                break;        
                        case "yesterday":
                                $string .= date('Y-m-d', strtotime('-1 day'));
                                break;        
                        case "qty_product_option":

                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                {
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                                }
                                else
                                {
                                        $product_name = $this->get_product_name( $data[product_id] );        
                                        //echo "name->$product_name / $data[product_id] ////<br>";
                                }

                                $product_options = $product_option ? $product_option : $data[options];

                                $product_options = "[" . $product_options . "]";
                                $product_options = str_replace( array ("\r", "\r\n", "\n"),"", $product_options);

                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                //$string = $data[product_id] . $data[options] . "/";

                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string .= $qty."개:". $product_name. " - ".  $product_options;
                                break;

                        case "option_qty":
                                class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        

                                // $product_options = $product_option ? $product_option : $data[options];
                                
                                $product_options = "[" . $product_option . "]";
                                $product_options = str_replace( array ("\r", "\r\n", "\n"),"", $product_options);

                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string .= $product_options . " X $qty"; 
                                break;
                        
                        case "recv_tel":
                            // 고정 주소로 배송해야 하는경우..jk 2011.3.2 
                            $arr_trans_info = $this->get_trans_info( $data[shop_id] );
                        
                            if ( $arr_trans_info[use_trans_add] )
                            {
                                $string .= str_replace( $search,"", $arr_trans_info[trans_tel] );
                            }
                            else
                                $string .= $data["recv_tel"] ? $data["recv_tel"] : $data["recv_mobile"];
                                                        
                            break;
                        case "recv_mobile":
                            // 고정 주소로 배송해야 하는경우..jk 2011.3.2 
                            $arr_trans_info = $this->get_trans_info( $data[shop_id] );
                        
                            if ( $arr_trans_info[use_trans_add] )
                            {
                                $string .= str_replace( $search,"", $arr_trans_info[trans_tel] );
                            }
                            else
                                $string .= $data["recv_mobile"] ? $data["recv_mobile"] : $data["recv_tel"];
                                
                            break;
                        
                        case "recv_mobile2":
                            $string .= $data[recv_mobile];
                            break;
                        
                        case "product_name":
                            if ( $_SESSION[STOCK_MANAGE_USE] )
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                            else
                            {
                                    $product_name = $this->get_product_name( $data[product_id] );        
                                    //echo "name->$product_name / $data[product_id] ////<br>";
                            }
                            
                            $string .= $product_name;
                            
                            break;
                        
                        case "product_option_qty":

                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                                else
                                {
                                        $product_name = $this->get_product_name( $data[product_id] );        
                                        //echo "name->$product_name / $data[product_id] ////<br>";
                                }

                                $product_options = $product_option ? $product_option : $data[options];
                                $product_options = "[" . $product_options . "]";
                                $product_options = str_replace( array ("\r", "\r\n", "\n"),"", $product_options);

                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                //$string = $data[product_id] . $data[options] . "/";

                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string .= $product_name. " - ".  $product_options . " X $qty"; 
                                break;


                        case "qty_product":
                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                {
                                        $product_name = "";
                                        $product_option="";
                                        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );        
                                }
                                else
                                        $product_name = $this->get_product_name( $data[product_id] );        

                                if ( _DOMAIN_ == "ckcompany" )
                                        $string .= "(" . $data[warehouse] . ") $data[product_id] ";

                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string .= $qty . "개:". $product_name;
                                break;
        
                        //===============================================
                        // option 관련 date: 2007.3.19
                        // 
                        case "options":
                                $string = class_D::get_product_option( $data[product_id] );                                
                                $string = str_replace( array ("\n","\r","\r\n","<br>")," ", $string);
                                
                                if ( _DOMAIN_ == "sweetbox2" )
                                    $string = str_replace( array (";","\"","\s"),"", $string);
                                else
                                    $string = str_replace( array (":",";","\"","\s"),"", $string);
                                    
                                break;
                        case "product_option":
                                $string = "-[" . $data[options] . "]";
                                $string = str_replace( array ("\r","\r\n", "\n"),"", $string);
                                $string = strip_tags ( $string );
                                break;


                        //===============================================
                        // option 관련 date: 2007.4.4
                        // 
                        case "option_only":
                                if ( $_SESSION[STOCK_MANAGE_USE] )
                                        $string = "-" . class_D::get_product_option( $data[product_id] );
                                else
                                        $string = $data[options];

                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                break;

                        //===============================================
                        // option 관련 date: 2007.6.18
                        // 주문서 실제 옵션 출력
                        case "real_option":
                                $string = " " . $data[options];
                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);
                                break;
                        case "qty_real_option":
                                $string = "-" . $data[options];
                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $string);

                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string = $qty . "개:" . $string;
                                break;
                        //===============================================
                        // product_name 관련 date: 2007.3.19
                        // 
                        case "real_product_name":
                        case "org_product_name":
                                $string = $this->get_org_product_name( $data["order_seq"] ? $data[order_seq] : $data[seq] );
                                // //debug("org_product_name: $data[seq] / $data[pack] / $data[order_seq] /" . $string );   
                                break;
                        case "product_name":
                                $string = $this->get_product_name( $data[product_id] );        
                                break;
                        case "product_memo":
                                $string = str_replace( array ("\r", "\r\n", "\n"),"", $data[memo] );
                                break;
                        case "product_id":
                                $string = "(" . $data[product_id] . ")";
                                break;
                        case "product_id_only":
                                $string = $data[product_id];
                                break;
                        case "qty_only":
                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string = $qty;
                                break;
                        case "qty":
                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string = $qty . "개:";
                                break;
                        case "xqty":
                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string = " X" . $qty;
                                break;
                        case "xqty2":
                                $qty = $data[qty] - $this->get_part_cancel( $data[seq] );
                                $string = " x" . $qty;
                                break;
                        case "pack":
                                $string = $data[pack] ? $data[pack] : $data[seq];
                                break;
                        // 판매처 
                        case "misong":
                                if ( $data[misong] == 2 )
                                    $string = "[미송]";
                                break;                                
                        // 판매처 
                        case "shop_name":
                                $string = class_C::get_shop_name( $data["shop_id"] );
                                break;
                        // 총 판매 금액 
                        case "total_price":
                                $string = $this->get_total_price( $data );
                                break;
                        // 오전 오후
                        case "dayornight":
                                if ( date("a") == "am" )
                                    $string = "";
                                else
                                    $string = "★";
                                break;
                        //=================================================
                        // 우편 번호 관련
                        case "recv_zip_only":
                                // 합포이고, 여러줄로 다운받는 경우 주소가 달라지는 경우가 있음.
                                if ( $this->m_download_config[pack_multiline] && $data[pack] )
                                {
                                    if ( $data[seq] <> $data[pack] )
                                    {
                                        $_data = $this->get_master_recvaddress( $data[pack] );              
                                        $data[recv_zip] = $_data[recv_zip];
                                    }
                                }
                                $string = str_replace("-", "", $data[recv_zip] );
                                break;
                        case "recv_zip":                            
                            $arr_trans_info = $this->get_trans_info( $data[shop_id] );
                        
                            if ( $arr_trans_info[use_trans_add] )
                            {
                                $string .= str_replace( $search,"", $arr_trans_info[trans_zip] );
                            }
                            else
                            {
                            
                                // 합포이고, 여러줄로 다운받는 경우 주소가 달라지는 경우가 있음.
                                if ( $this->m_download_config[pack_multiline] && $data[pack] )
                                {
                                    if ( $data[seq] <> $data[pack] )
                                    {
                                        $_data = $this->get_master_recvaddress( $data[pack] );              
                                        $data[recv_zip] = $_data[recv_zip];
                                    }
                                }
                                $string = $data[recv_zip];
                            }
                            break;
                        // 일반 
                        default : 
                            if ( _DOMAIN_ == "sevar" ) // test해야 함 - 3.10
                               $string = str_replace("\/", "\|", $data[$key]);
                            else
                                $string = strip_tags($data[$key]);
                }
                return $string;
            }
        }


/*                
        //==============================================
        //        
        // 일반 macro 값이 있을 경우
        //  date: 2007.3.19 - jk
        //
        if ( $macro )
        {
                $string = "";

                if ( preg_match( "/[\[](.*)[\]]/", $macro, $matches ) )
                {
                        $arr_macro = split( "\|\|", $matches[1] );

                        $i = 0;
                        while( $i < count( $arr_macro ) )
                        {
                                $macro = $key . "_" . $arr_macro[$i];
                                $string .= $this->get_data( $data, $macro );
                                $i++;
                        }
                }

                return "<td>" . $string . "</td>";
        }
*/
  }        

  function get_trans_corp_name( $trans_corp )
  {
		global $connect;

		$query = "select trans_corp from trans_info where id='$trans_corp'";
		$result = mysql_query( $query, $connect );
		$data  = mysql_fetch_assoc( $result );

		return $data[trans_corp];

  }

  function get_total_price( $data )
  {
        global $connect;
        if ( $data[pack] )
                $query = "select seq,amount, order_cs from orders where pack='$data[pack]' and order_cs <> 1 and status <> 8";
        else
                $query = "select seq,amount, order_cs from orders where seq='$data[seq]' and order_cs <> 1 and status <> 8";

        $result = mysql_query ( $query, $connect );
        $price=0;

        while ( $data = mysql_fetch_array ( $result ) )
        {
            if ( $order_cs == 2 || $order_cs == 4) // 배송 전 부분 취소
                $price = $price - $this->get_part_cancel_price( $data[seq] );
                
            $price = $price + $data[amount];
        }

        return $price;        
  }

    ///////////////////////////////////////
    // 부분 취소 금액
    // 2010.8.20 - jk
    function get_part_cancel_price( $seq )
    {
        global $connect;
        $query  = "select sum(refund_price) sum_refund_price from order_products where order_seq=$seq";
        $result = mysql_query( $query, $connect );   
        $data   = mysql_fetch_assoc( $result );        
        return $data[sum_refund_price];
    }

    ////////////////////////////////
    // 
    function max_weight ( $data )
    {
        global $connect;
        
        $query = "select seq,pack from orders where seq=$data[seq]";
        ////debug($query);
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
            
        // shop_options 를 가져온다
        if ( $_data[pack] )
        {
            $query = "select seq from orders where pack=$_data[pack]";
            $result = mysql_query( $query, $connect );
            $str_seqs = "";
            
            //return $query;
            
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $str_seqs .= $str_seqs ? ",":"";
                $str_seqs .= "$data[seq]";   
            }
            
            $query = "select max(a.weight) max_weight
                    from products a, (select product_id,qty from order_products where order_seq in ( $str_seqs )) b
                    where a.product_id=b.product_id";
        }
        else
        {   
            $query = "select max(a.weight) max_weight
                        from products a, (select product_id,qty from order_products where order_seq=$data[seq]) b
                        where a.product_id=b.product_id";
                        
            ////debug( $query );                        
        }
        
        $result = mysql_query( $query, $connect );
        
        $total_weight = 0;
        $_data   = mysql_fetch_assoc( $result );
        return $_data[max_weight];
    }
    
    /////////////////////////////
    // total_weight for namsun pack available
    // date: 2010.3.11 - jk
    function total_weight( $data )
    {
        global $connect;
        
        $query = "select seq,pack from orders where seq=$data[seq]";
        ////debug($query);
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
            
        // shop_options 를 가져온다
        if ( $_data[pack] )
        {
            $query = "select seq from orders where pack=$_data[pack]";
            $result = mysql_query( $query, $connect );
            $str_seqs = "";
            
            //return $query;
            
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $str_seqs .= $str_seqs ? ",":"";
                $str_seqs .= "$data[seq]";   
            }
            
            $query = "select a.product_id,a.weight, b.qty
                    from products a, (select product_id,qty from order_products where order_seq in ( $str_seqs )) b
                    where a.product_id=b.product_id";
        }
        else
        {
            //print_r ( $data );
            //exit;
            
            $query = "select a.product_id,a.weight, b.qty
                        from products a, (select product_id,qty from order_products where order_seq=$data[seq]) b
                        where a.product_id=b.product_id";
                        
            ////debug( $query );                        
        }
        
        $result = mysql_query( $query, $connect );
        
        $total_weight = 0;
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $total_weight += $data[weight] * $data[qty];    
        }
        
        return $total_weight;
    }

    // amount for hiliving 
    function hiliving_amount( $data )
    {
        global $connect;
        
        // shop_options 를 가져온다
        $query = "select shop_options from order_products
                    where seq=$data[product_seq]";
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
        
        if ( $_data[shop_options] == "사은품" )
        {
            return 0;   
        }
        else
        {
            $query = "select sum(qty) tot from order_products 
                       where order_seq = $data[seq]
                         and shop_options <> '사은품'";
                       
            $result = mysql_query( $query, $connect );
            $_data   = mysql_fetch_assoc( $result );
            $tot    = $_data[tot];
            
            return ceil($data[amount] / $tot);
        }
    }

    // 타채널 입고가로 사용됨
    // 사은품을 제외한 상품의 총 개수에서 실제 공급가를 나눔.
    function hiliving_supply_price( $data )
    {
        global $connect;
        $query = "select shop_options from order_products
                    where seq=$data[product_seq]";
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
        
        if ( $_data[shop_options] == "사은품" )
        {
            return 0;   
        }
        else
        {
            
            $query = "select sum(qty) tot from order_products 
                       where order_seq = $data[seq] 
                         and shop_options <> '사은품'";
            $result = mysql_query( $query, $connect );
            $_data   = mysql_fetch_assoc( $result );
            $tot    = $_data[tot];
            
            return ceil($data[supply_price] / $tot);
        }
    }

  //=================================================
  // 
  // 합포 data는 여기서 처리
  // date: 2007.3.20 - jk.ryu
  //
  function get_pack_datas ( $pack, $arr_macro, $macro_case, $separator='', $max='' )
  {
        global $connect, $status;

        // 부분 배송인지 여부 체크 2010.7.28 - jk
        $part_deliv = 0;
        
        ///////////////////////////////
        // pack의 값은 변할 수 있다. -> org_pack을 구한다.
        $query  = "select org_pack  from print_enable where pack=$pack and status=3";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $_org_pack  = $data[org_pack];
        
        // org_pack
        $query = "select count(*) cnt from print_enable where org_pack=$_org_pack and status=3";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $cnt2   = $data[cnt];
        
        if ( $cnt2 > 0 )
        {            
            // print_enable의 qty의 sum
            $query = "select count(*) cnt from print_enable where org_pack=$_org_pack";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $cnt1   = $data[cnt];
            
            // 부분 배송임..
            if ( $cnt1 != $cnt2 )
            {
                $part_deliv = 1;
            }
        }
        
        // 합포 상품의 관리번호 가져오기
        $query = "select seq,product_name,gift,code10 from orders where pack='$pack'";
        ////debug( "get_pack:" . $query );
        $result = mysql_query ( $query, $connect );

        $arr_product_name = array();
        $seqs = "";

        if ( mysql_num_rows( $result ) > 1 )
        {
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                    $seqs .= $seqs ? ",$data[seq]" : $data[seq];
                    $arr_product_name[$data[seq]] = $data[product_name];        
            }
        }
        else
        {
            // 합포가 아닐 수도 있다
            $seqs = $pack;
        }

        // 상품 정보 가져오기
        // 취소된 상품 정보는 출력하지 않는다.
        $query = "select * , seq as product_seq
                    from order_products 
                   where order_seq in ( $seqs ) 
                     and order_cs not in (1,2,3,4) 
                   order by is_gift, product_id";

        //////////////////////////////////////////////
        // 2010.9.16 - jk 관리자 only 20일 배포 예정..
        // Test 중...                   
        // if($_SESSION[LOGIN_LEVEL] == 9 )
        // if( _DOMAIN_ == "parklon" )
        if( $this->m_download_config[product_sum] == 1 )
        {
            $query = "select *, sum(qty) qty 
                    from order_products 
                   where order_seq in ( $seqs ) 
                     and order_cs not in (1,2,3,4) 
                     group by product_id
                   order by is_gift, product_id";
        }                   
        
        if ( _DOMAIN_ == "suvin" || _DOMAIN_ == "gerio" )
        {
            $query = "select * , seq as product_seq
                    from order_products , products
                   where order_products.product_id = products.product_id
                     and order_products.order_seq in ( $seqs ) 
                     and order_products.order_cs not in (1,2,3,4) 
                   order by products.location"; 
        }
                   
        $result = mysql_query( $query, $connect );
        $string = "";
        $_cnt  = 0;
        $_cnt1 = 0;        // 실제 출력된 개수
        while ( $_data = mysql_fetch_assoc( $result ) )
        {
            // $data에는 product_name이 없음
            $_data[product_name] = $arr_product_name[$_data[order_seq]];
            $_cnt++;    
            $this->m_gift_count = 0;
                
            if ( $this->min_product )
            {
                if ( $_cnt < $this->min_product ) // 2007.7.11 - jkryu 추가 3-5 일 경우 3번째 데이터 부터 5번째 data가 출력되야 함
                    continue;
                }
        
                // =============================================
                // 개수는 max_product의 개수보다 클 수 없음
                // date: 2007.6.20 - jk
                if ( $this->max_product )
                    if ( $_cnt1 >= $this->max_product ) // >= => = 로 수정 2007.7.11 - jkryu
                        continue;
                        
                switch ( $macro_case )
                {
                    //===============================================
                    //
                    // case1 . 여러개의 셀에 정보를 넣을 경우
                    // ex) <packs>[product_name][options][qty]
                    //   date: 2007.3.20 - jk
                    //
                    case 1:
                            // 여러개의 경우 cell이 다름
                        for ($i=0; $i< count($arr_macro); $i++) 
                        {
                                // 여러개의 cell에 등록됨
                            //$string .= "<td  style='mso-number-format:\@' >";
                            $string .= "<td  style='mso-number-format:General' >";
                            $_arr = split( "\|\|", $arr_macro[$i] );
                            
                            //if ( $_cnt1 == 0 &&  $part_deliv == 1)
                            //    $string .= "[부분 배송] ";
                            if ( $_cnt1 == 0 )
                            {
                                if ( $this->str_part_trans( $_data ,&$message ) )
                                {
                                    $string .= $message;
                                }
                            }
                            
                            for ( $_i=0;$_i < count( $_arr ) ; $_i++ )
                            {
                                $string .= $this->get_data( $_data, $_arr[$_i] );
                            }
                            $string .= "</td>";
                        }
                            $_cnt1 = $_cnt1 + 1;        // 실 출력 개수
                        break;        

                //==================================================
                // 
                // case 2. 하나의 cell에 모든 정보가 다 들어갈 경우
                // date: 2007.3.20
                // ex) <packs>[구분자:|,길이:30||product_name||options|]
                // confirm start : 2007.3.26 -jk
                //
                case 2:
                    //============================================
                        // 묶음 상품이 아닌경우
                        // date: 2007.5.15 - jk
                        $i=1;
                        $temp = "";
                        
                        // $_cnt1 == 0 : 1번째 상품 앞에 [부분배송] 표시 한다.
                        // $part_deliv == 1 : 부분배송여부..
                        //if ( $part_deliv == 1)
                        while( $i < count( $arr_macro ) )
                        {
                            //if ( $_cnt1 == 0 && $part_deliv == 1)                        
                                //$string .= "★[부분배송]★";
                            if ( $_cnt1 == 0 ) 
                            {                       
                                if ( $this->str_part_trans( $_data ,&$message ) )
                                {
                                    $string .= $message;
                                }
                            }
                            // echo $arr_macro[$i] . "<br>";
                            ////debug("macro: $arr_macro[$i] / order_seq: $_data[order_seq]");
                            $temp .= $this->get_data( $_data, $arr_macro[$i] );
                            $i++;
                            
                            $_cnt1++; // 실 출력 개수..
                        }
                        
                        if ( $separator )
                            $string .= $this->pack_string( $temp, $separator, $max ); 
                        else
                            $string .= $temp . "|"; 
                        
                        
                        break;

                //======================================
                //
                // case 3. 하나의 셀마다 상품 data 출력
                // date: 2007.3.20 - jk.ryu
                // 묶음 상품의 경우 처리 방안
                //   date: 2007.4.16 - jk.ryu
                //
                case 3: 
                    //$string .= "<td  style='mso-number-format:\@'>";
                    $string .= "<td  style='mso-number-format:General'>";
                    $i=0;
                    while( $i < count( $arr_macro ) )
                    {       
                        // $string .= $arr_macro[$i] . "/";
                        //if ( $_cnt1 == 0 && $part_deliv == 1 && $i == 0 )
                        //    $string .= "★[부분배송]★";
                        if ( $_cnt1 == 0 && $i == 0 )
                        {
                            if ( $this->str_part_trans( $_data ,&$message ) )
                            {
                                $string .= $message;
                            }
                        }
                                
                        $string .= $this->get_data( $_data, $arr_macro[$i] );
                        $i++;
                    }
                    $string .= "</td>";
                    $_cnt1 = $_cnt1 + 1;        // 실 출력 개수
                    
                    break;
            }
            $i++;
        }

        //======================================
        // max_product만큼 공백 출력
            // date: 2007.6.20 - jk
            if ( $_cnt1 < $this->max_product )
            {
                for ( $i=$_cnt1 + 1; $i <= $this->max_product; $i++)
                {
                    //$string .= "<td  style='mso-number-format:\@'> ";
                    $string .= "<td  style='mso-number-format:Genearl'> ";
                    $string .= "</td>";
                }
            }

            if ( $macro_case == 2 )
            {
                    //return "<td  style='mso-number-format:\@'>$string</td>";
                    return "<td  style='mso-number-format:General'>$string</td>";
            }
            else
                    return $string;
  }
    
    //
    // 부분배송, 완료배송 여부를 확인해 준다.
    // orders.part_seq를 확인함
    // case 1. 합포인경우 
    //      status가 1이면서 합포 번호가 다른 주문이 존재하는 경우 부분배송
    //      없으면 완료배송
    // case 2. 합포가 아닌경우
    //      status가 1이면서 seq가 다른 주문이 존재하는 경우 부분배송
    //      없으면 완료배송
    //  
    function str_part_trans( $_data, &$message )
    {   
        global $connect;
        
        $query = "select seq,pack,part_seq from orders where seq=$_data[order_seq]";
        
        ////debug( "str_part_trans1: " . $query );
        
        $result = mysql_query( $query, $connect );
        $check_data = mysql_fetch_assoc( $result );
        
        if ( $check_data[part_seq] )
        {   
            $query = "select count(*) cnt from orders 
                       where part_seq =$check_data[part_seq] 
                         and status   =1 
                         and order_cs not in (1,2,3,4)";
                         
            if ( $check_data[pack] )
                $query .= " and pack <> $check_data[pack] ";
            else
                $query .= " and seq  <> $check_data[seq] ";
            
            //debug( "str_part_trans query: " . $query );            
            $result      = mysql_query( $query, $connect );
            $check_data1 = mysql_fetch_assoc( $result );
            
            if ( $check_data1[cnt] >= 1 )
                $message = "[부분배송]";
            //else
            //    $message =  "[완전배송]";
                
            return 1;
        }
        else
            return 0;
            
    }
    
    
  //====================================================
  //  
  // 묶음 상품의 경우 처리
  // date: 2007.4.16 - jk.ryu
  // 아무 로직도 없네.. date: 2007.5.15 - jk
  //
  function get_packed_list ( $product_id, $arr_macro, $data, $separator="|", $max=200 )
  {
        // product_id만 return할 경우 boxon 사용할 경우
        if ( $arr_macro[0] == "product_id_only" )
        {
                if ( _DOMAIN_ == "adong" and $product_id == "01864" )
                        return;
                else
                        return $product_id;        
        }


        // boxon 사용하지 않을 경우
        global $connect;
        $query = "select name, options,product_id from products where product_id='$product_id'";

        $result = mysql_query ( $query, $connect );
        $data_p = mysql_fetch_array ( $result );
         
        $data["product_name"] = $data_p["name"];
        $data["options"]      = $data_p["options"];
        $data["product_id"]   = $product_id;

        if ( _DOMAIN_ == "piglet177" )
                $temp .= $query;

        $i=0;
        // separator가 cell인 경우는 case 1. 하나의 셀에 각각의 정보가 들어가는 경우
        while( $i < count( $arr_macro ) )
        {
                // 선택된 값이 없을경우 다음 주문으로 pass
                if ( !$arr_macro[$i] ) continue;

                if ( $separator == "cells" ) 
                    $temp .= "<td  style='mso-number-format:General'>";
                    //$temp .= "<td  style='mso-number-format:\@'>";
                
                $temp .= $this->get_data( $data, $arr_macro[$i] );


                if ( $separator == "cells" ) $temp .= "</td>";
                $i++;
        }
        ////////////////////////////////////////////////////////
        // separator가 없으면 pack_string을 타지 않음
        if ( $separator != "cells" and $separator and $max ) 
                $string .= $this->pack_string( $temp, $separator, $max );
        else
                $string = $temp;
        return $string;
  }

  ///////////////////////////////////////////////
  // 정해진 개수로 string 자름
  function pack_string( $temp, $seperator = "|", $max_length = 44, $max_row = 5 )
   {
      $arr_chars = array("=","\r", "\n", "\r\n","\t", ",", ".", chr(13),"\"","'" );
      $temp = str_replace( $arr_chars, ".", $temp );

      // 무조건 50자 이하로 나누기
      $length = strlen ( $temp );
        
      $str = $temp;

        //==================================
        // zoc76 요청 사항 2007.11.15 - jk
        if( $seperator == "dongbu" )
                $seperator = "<>$";

       if ( $length  >= $max_length )
       {
            $pos = 0;
            $str = "";
            $j = 0;
           
            //////////////////////////////////////////////// 
            // 정해진 max_length마다 seperator를 입력
            $arr_string = $this->substr_kor($temp,$max_length );
            
            for( $i=0; $i < count($arr_string); $i++ )
            {
                ////debug( "xx: $i : $length" . $seperator );
                
                if ( $i > 0 )                
                {
                    
                    $str .= $seperator;   
                }
                
                $str .= $arr_string[$i];   
                
                ////debug( "ss:" . $seperator );
            }
            
            $str .= $seperator;
            
            /*
            while ( $pos < $length )
            {
               // 정해진 개수만큼 돌고 끝냄
               if ( $max_row )
                   if ( $j == $max_row ) break;

               $end_pos = $pos + $max_length; // max가 50

               if ( $end_pos > $length )
                  $end_pos = $length;
             
               for($i=$pos; $i<$end_pos; $i++) if(ord($temp[$i])>127) $i++;

               $left = $i - $pos;

               //$str .= $j . "/" .  $left;
               //$str .= substr( $temp, $pos, $left);

               //$pos = $end_pos + 1;
               $pos = $pos + $left;
 
               // if ( $end_pos != $length ) // 줄 바꿈 표시
               if ( $end_pos < $length ) // 줄 바꿈 표시
                {
                       //$str .= "($pos)" .  $seperator;
                       $str .= $seperator;
                }
               else
                {
                       // $str .= "[$pos/$length]" .  $seperator;
                       $str .= $seperator;
                }

               $j++;
            }*/
            
        }
        else
                $str .= $seperator;

        // 공백 매워 줌
        /*
        if ( $max_row )
        {
            if ( $max_row != $j )
                for ( $count = $j; $count < $max_row; $count++ )
                    $str .= " ". $seperator;
            else
                       $str .= " ". $seperator;
        }
        else
                   $str .= " ". $seperator;
        */

        return $str; 
   }
 
   // gift 처리 로직
   // 2008.10.16 - jk
   function get_gift( $order_seq )
   {
        global $connect;
        
        // 합포일 경우..
        $query = "select seq,pack from orders where seq=$order_seq";
        ////debug( "get_gift1: " . $query );
        $result = mysql_query( $query, $connect );
        $_data   = mysql_fetch_assoc( $result );
        
        
        // 
        if ( $_data[pack] )
            $query = "select seq,gift from orders where pack=$_data[pack] ";
        else
            $query = "select seq,gift from orders where seq=$_data[seq] ";
        
        ////debug( "get_gift2: " . $query );             
 
        $result2 = mysql_query( $query, $connect );
        $_gift = "";
        $arr_gift = array();
        while ( $data2 = mysql_fetch_assoc( $result2 ) )
        {
            if ( !in_array( $data2[gift], $arr_gift ) )
            {
                ////debug("no exist in array in " . $data2[seq] ." /". $data2[gift] );
                array_push( $arr_gift, $data2[gift] );
            }
        }

        foreach ( $arr_gift as $gift )
            $_gift .= $gift;

        return $_gift;
   }

    ////////////////////////
    // 보류주문제외
    function is_except_hold()
    {
        global $connect;
        
        $query = "select special_stock from ez_config";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        return $data[special_stock];
    }

}

?>
