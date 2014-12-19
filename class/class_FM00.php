<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_ui.php";
require_once "class_stat_supply.php";

//
// 공급처 정산 계산하는 class
//
class class_FM00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    
    function FM01()
    {
        global $template, $connect;
        global $type, $shop_id, $supply_id, $start_date, $end_date, $date_type;
        
        include "template/F/FM01.htm";           
    }
    
    function FM02()
    {
        global $template, $connect;
        global $type, $shop_id, $supply_id, $start_date, $end_date, $date_type;
        
        include "template/F/FM02.htm";           
    }
    
    //
    // 관련 table stat_supply, tot_qty == tot_pack_qty : 단일주문
    //                         tot_qty != tot_pack_qty : 묶음주문
    //
    function get_order_list_test()
    {
        global $connect;
        global $type, $shop_id, $supply_id, $start_date, $end_date, $date_type;
        
        $arr_supply_name = $this->get_supply_info();
        
        $start_date = "2012-7-1";
        $end_date   = "2012-7-16";
        
        $query = "select orders.order_id
                       , orders.seq
                       , orders.pack
                       , order_products.product_id
                       , order_products.qty
                       , order_products.order_cs
                       , products.name product_name
                       , products.options 
                       , products.supply_code
                       , orders.collect_date crdate
                       , orders.amount
                       , orders.supply_price
                       , orders.status
                   from orders, order_products, products
                  where orders.seq                = order_products.order_seq
                    and order_products.product_id = products.product_id 
                    and orders.collect_date      >= '$start_date'
                    and orders.collect_date      <= '$end_date'
                    limit 1000;
                    ";
        
        //exit;
        $result = mysql_query( $query, $connect );
        $num = 1;
        $prev_seq = "";
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[num]         = $num++;
            $data[status]      = $this->get_order_status($data[status]) . " " . $this->get_order_cs( $data[order_cs]);
            
            if ( $prev_seq != $data[seq] )
            {
                $data["is_first_row"] = 1;
                $data["row_cnt"]      = $this->get_order_row_cnt( $data[seq] );
            }
            else
            {
                $data["is_first_row"] = 0;
                $data["row_cnt"]      = 1;
            }
      
            $prev_seq          = $data[seq];      
            $data[supply_name] = $arr_supply_name[ $data[supply_code] ]; 
            $arr_result[]      = $data;
        }
        echo json_encode( $arr_result );
    }
    
    function get_supply_info()
    {
        global $connect;
        
        $query = "select code,name from userinfo where level=0";
        $result = mysql_query( $query, $connect );
        
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[ $data[code] ] = $data[name];
        }  
        return $arr_result;
    }
    
    //
    // 관련 table stat_supply, tot_qty == tot_pack_qty : 단일주문
    //                         tot_qty != tot_pack_qty : 묶음주문
    //
    function get_order_list()
    {
        global $connect;
        global $type, $shop_id, $supply_id, $start_date, $end_date, $date_type;
        
        $arr_supply_name = $this->get_supply_info();
        
        if ( $type == "single_order_cnt" )
        {
            $str_seqs = $this->get_single_order_seq( $shop_id, $supply_id );   
        }
        else if ( $type == "part_order_cnt" )
        {
            $str_seqs = $this->get_part_order_seq( $shop_id, $supply_id );   
        }
        else if ( $type == "order_cnt" )
        {            
            $str_seqs = $this->get_order_seq($shop_id, $supply_id);
        }
        
        $query = "select orders.order_id
                       , orders.seq
                       , orders.pack
                       , order_products.product_id
                       , order_products.qty
                       , order_products.order_cs
                       , products.name product_name
                       , products.options 
                       , products.supply_code
                       , orders.$date_type crdate
                       , orders.amount
                       , orders.supply_price
                       , orders.status
                   from orders, order_products, products
                  where orders.seq                = order_products.order_seq
                    and order_products.product_id = products.product_id 
                    and orders.$date_type        >= '$start_date'
                    and orders.$date_type        <= '$end_date'
                    and orders.seq in ( $str_seqs )
                    ";
        
        debug( $query );
        //exit;
        $result = mysql_query( $query, $connect );
        $num = 1;
        $prev_seq = "";
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[num]         = $num++;
            $data[status]      = $this->get_order_status($data[status]) . " " . $this->get_order_cs( $data[order_cs]);
            
            if ( $prev_seq != $data[seq] )
            {
                $data["is_first_row"] = 1;
                $data["row_cnt"]      = $this->get_order_row_cnt( $data[seq] );
            }
            else
            {
                $data["is_first_row"] = 0;
                $data["row_cnt"]      = 1;
            }
      
            $prev_seq          = $data[seq];      
            $data[supply_name] = $arr_supply_name[ $data[supply_code] ]; 
            $arr_result[]      = $data;
        }
        echo json_encode( $arr_result );
    }
    
    // 주문 수량
    function get_order_seq( $shop_id, $supply_id )
    {
        global $connect;
        
        $query = "select distinct order_seq from stat_supply where supply_id='$supply_id' and shop_id='$shop_id' ";   
        $result = mysql_query( $query, $connect );
        
        $_seqs = "";
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $_seqs .= $_seqs ? "," : "";
            $_seqs .= $data[order_seq];
        }
        return $_seqs;
    }
    
    // 단일주문 수량 계산
    function get_single_order_seq( $shop_id, $supply_id )
    {
        global $connect;
        $query = "select order_seq from stat_supply 
                   where supply_id = '$supply_id' 
                     and shop_id   = '$shop_id' 
                     group by order_seq";
        
        $result = mysql_query( $query, $connect );
        $seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];   
        }
        
        $query = "select order_seq from stat_supply 
                   where order_seq in ( $seqs )
                     group by order_seq having count(*) = 1";                     

        $result = mysql_query( $query, $connect );
        
        $_seqs = "";
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $_seqs .= $_seqs ? "," : "";
            $_seqs .= $data[order_seq];
        }
        return $_seqs;
    }
    
     function get_part_order_seq( $shop_id, $supply_id )
    {
        global $connect;
        $query = "select order_seq from stat_supply 
                   where supply_id = '$supply_id' 
                     and shop_id   = '$shop_id' 
                     group by order_seq";
        
        $result = mysql_query( $query, $connect );
        $seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];   
        }
        
        $query = "select order_seq from stat_supply 
                   where order_seq in ( $seqs )
                     group by order_seq having count(*) > 1";                     

        $result = mysql_query( $query, $connect );
        
        $_seqs = "";
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $_seqs .= $_seqs ? "," : "";
            $_seqs .= $data[order_seq];
        }
        return $_seqs;
    }
    
    // 
    // row의 number
    //
    function get_order_row_cnt( $seq )
    {
        global $connect;
        
        $query  = "select count(*) cnt from order_products where order_seq='$seq'";
        
        debug( $query );
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[cnt];
    }
    
    //
    // 정산 계산 결과 출력
    // 
    function disp_stat_result()
    {
        global $connect, $str_supply_code;
        
        $obj = new class_stat_supply( $connect ); 
        
        // 조건에 맞게 정산 자료를 생성한다.
        $arr_result = $obj->calc( $str_supply_code );
        $str = "";
        foreach( $arr_result as $key => $data )
        {
            if ( $key > 0 )
            {
                $str .= $this->disp_rows ( $data, $obj );  
            }   
        }
        
        echo $str;
    }
    
    function disp_rows( $rows,$obj )
    {
        $str ;
        $row       = 0;
        $order_cnt = 0;   // 주문의 총 개수
        $order_sCnt = 0;  // single주문 개수'
        $order_pCnt = 0;  // part 주문개수
        
        $su_p_cnt      = 0;  // 공급처 상품 총개수
        $su_c_qty      = 0;  // 공급처 별 취소수량
        $su_amount     = 0;  // 공급처별 판매금액
        $tot_su_c_amount = 0; // 취소금액
        $tot_real_supply_price = 0; // 정산총액..
        $tot_su_supply_price   = 0; 
        $tot_su_extra_money    = 0;
        $tot_su_org_price      = 0;     //총 공급가
        $tot_su_g_org_price    = 0;     //총 공급가
        $tot_benefit           = 0;
        $tot_deliv_price       = 0;     // 전체 선불 배송비
        
        foreach ( $rows as $row )
        {
            $_cnt = $obj->get_order_cnt($row['shop_id'], $row['supply_id']);
            $_sCnt = $obj->get_single_order_cnt( $row['shop_id'], $row['supply_id']);
            $_pCnt = $obj->get_part_order_cnt($row['shop_id'], $row['supply_id']);
            $_bb = round( $row[benefit] / $row[su_amount] , 3) * 100; // 순 이익률
            
            $str .= "<tr shop_id='" . $row['shop_id'] . "' supply_id='" .  $row['supply_id'] . "'>";       
            
            $str .= "<td>" . $row[supply_name] . "<br>($row[supply_id])</td>";
            $str .= "<td>" . $row[shop_name] . "<br>($row[shop_id])</td>";
            $str .= "<td class='wPopup' type='order_cnt'>" . number_format( $_cnt) . "</td>";
            $str .= "<td class='wPopup' type='single_order_cnt'>" . number_format( $_sCnt) . "</td>";
            $str .= "<td class='wPopup' type='part_order_cnt'>" . number_format( $_pCnt) . "</td>";
            $str .= "<td>" . number_format( $row[su_qty]) . "</td>";
            $str .= "<td>" . number_format( $row[su_c_qty]) . "</td>";
            $str .= "<td>" . number_format( $row[su_amount]) . "</td>";
            $str .= "<td>" . number_format( $row[su_c_amount]) . "</td>";
            $str .= "<td>" . number_format( $row[real_supply_price]) . "</td>";
            $str .= "<td>" . number_format( $row[su_supply_price]) . "</td>";
            $str .= "<td>" . number_format( $row[su_extra_money]) . "</td>";
            $str .= "<td>" . number_format( $row[su_org_price]) . "</td>";
            $str .= "<td>" . number_format( $row[su_g_org_price]) . "</td>";
            $str .= "<td>" . number_format( $row[deliv_price]) . "</td>";
            $str .= "<td>" . number_format( $row[etc]) . "</td>";
            $str .= "<td>" . number_format( $row[benefit]) . "</td>";
            $str .= "<td>" . $_bb . "%</td>";
            $str .= "</tr>";
            
            $order_cnt  += $_cnt;
            $order_sCnt += $_sCnt;
            $order_pCnt += $_pCnt;
            $su_p_cnt   += $row[su_qty];
            $su_c_qty   += $row[su_c_qty];
            $su_amount  += $row[su_amount];
            $tot_su_c_amount       += $row[su_c_amount];
            $tot_real_supply_price += $row[real_supply_price];
            $su_supply_price       += $row[su_supply_price];
            $tot_su_extra_money    += $row[su_extra_money];
            $tot_su_org_price      += $row[su_org_price];
            $tot_su_g_org_price    += $row[su_g_org_price];
            $tot_benefit           += $row[benefit];
            $tot_deliv_price     += $row[deliv_price];
        }
        
        $_bb = round( $tot_benefit / $tot_real_supply_price , 3) * 100; // 순 이익률
        
        // 부분합 row추가
        // 부분 합 추가..
        $str .= "<tr style='background-color:#cccccc;height:45px;'>
            <td>총합</td>
            <td>&nbsp;</td>
            <td>" . number_format($order_cnt) ."</td>
            <td>" . number_format($order_sCnt) ."</td>
            <td>" . number_format($order_pCnt) ."</td>
            <td>" . number_format($su_p_cnt) ."</td>
            <td>" . number_format($su_c_qty) ."</td>
            <td>" . number_format($su_amount) ."</td>
            <td>" . number_format($tot_su_c_amount) ."</td>
            <td>" . number_format($tot_real_supply_price) ."</td>
            <td>" . number_format($su_supply_price) ."</td>
            <td>" . number_format($tot_su_extra_money) ."</td>
            <td>" . number_format($tot_su_org_price) ."</td>
            <td>" . number_format($tot_su_g_org_price) ."</td>
            <td>" . number_format($tot_deliv_price) ."</td>
            <td>" . 0 ."</td>
            <td>" . number_format($tot_benefit) ."</td>
            <td>" . $_bb ."%</td>
        </tr>";
       
        
        
        return $str;
           
    }
    
    //
    // 공급처별 정산 자료 계산을 위한 자료 생성
    //      계산은 전체 주문에 대해서 만들어 낸다.
    //      조회는 stat_supply 의 subset을 보여준다.        
    // date: 2012.5.14
    // 
    // order_pack : 합포 번호
    // 
    function build_stat()
    {
        global $connect,$supply_code, $str_supply_code, $start_date, $end_date, $date_type, $supply_group, $supply_code,$str_supply_code;
        
        if ( $supply_group )
        {
            $_str = $this->get_group_supply( $supply_group );
            
            if ( $str_supply_code )
            {
                if ( $_str )
                    $str_supply_code = $str_supply_code . "," . $_str;
            }
            else
            {
                $str_supply_code = $_str;
            }
        }

        debug( "supply_code: $str_supply_code");
                        
        // stat_suply 초기화
        $this->init_stat_supply();
        
        // stat_supply에 값을 입력 한다.
        $query = "select seq,pack,amount,supply_price,shop_id,trans_who 
                    from orders ";
        
        if ( $date_type == "collect_date")
        {
            $query .= "where $date_type >= '$start_date'
                     and $date_type <= '$end_date'";
        }
        else
        {
            $query .= "where $date_type >= '$start_date 00:00:00'
                     and $date_type <= '$end_date 23:59:59'";
        }

        //$query .= " and seq=655670";                   

        debug( $query );
                   
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->calc_products( $data );
        }
        
        //
        // stat_suply의 결과로 tot_pack_qty 계산 합포별 tot_qty의 sum..
        // 
        $this->calc_tot_pack_qty();
        
        echo "<br>e: " . date("H:i:s");  
        
        echo "<script language='javascript'>
        parent.disp_stat_result('" . $str_supply_code . "');
        </script>
        ";
        
    }
    
    //
    // tot_pack_qty 계산..
    //
    function calc_tot_pack_qty()
    {
        global $connect;
        
        $query = "select sum(tot_qty) tt, order_pack from stat_supply where order_pack <> 0 group by order_pack";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->insert_tot_pack_qty( $data );
        }
    }
    
    //
    // 
    //
    function insert_tot_pack_qty( $data ) 
    {
        global $connect;
        
        $query = "update stat_supply set tot_pack_qty = $data[tt] where order_pack=$data[order_pack]";
        mysql_query( $query, $connect );
    }
    
    
    //
    // stat_supply table초기화
    //
    function init_stat_supply()
    {
        global $connect;
        
        $query = "truncate stat_supply";  
        mysql_query( $query, $connect );
    }
    
    //
    // order_products관련 계산
    //
    function calc_products( $order_data )
    {
        global $connect;
                
        $query = "Select * 
                    from order_products 
                   where order_seq=" . $order_data[seq];
       
        // order_products에 order_cs가 1,2,3,4 는 취소
        $result  = mysql_query( $query, $connect );
        $arr_products = array();
        while ( $product_data = mysql_fetch_assoc( $result ) )
        {
            $arr_products[] = $product_data;
        }
        
        $this->insert_stat_base( $order_data,$arr_products );      
    }
    
    //
    // pack의 상품 수
    //
    private $m_arr_pack_qty = array();
    function get_pack_product_qty( $_pack )
    {
        global $connect;
        
        
    }
    
    //
    // supply_id를 기준으로 해서 1줄씩 생성..
    // 공급처의 수 만큼 row가 만들어 진다
    //
    function insert_stat_base( $order_data, $arr_products )
    {
        //print_r ( $arr_products );
        // tot products qty 구한다.
        $tot_amount       = $order_data['amount'];          // 총 판매가
        $tot_supply_price = $order_data['supply_price'];    // 총 판매가
        $tot_org_price    = 0;                              // 총 원가
        $tot_qty          = 0;                              // 전체 판매 상품 개수
        $tot_pack_qty     = 0;                              // 합포일 경우 전체 수량.
        $tr_is_part_deliv = 0;                              // 부분배송은 기본값은 0
        $order_pack       = $order_data['pack'] ? $order_data['pack'] : 0;
        
        // 합포일 경우 전체 합포의 qty를 찾는다.
        if ( $order_data[pack] )
        {
            $this->get_pack_product_qty( $order_data[pack] );
        }        
        
        // 공급처의 개수만큼 row가 나온다.
        $h_supply = array();    // * 기준!!!
        for( $i=0; $i < count( $arr_products); $i++ )
        {
            // default 값
            $h_supply[ $arr_products[$i]['supply_id'] ]['supply_id']        = $arr_products[$i]['supply_id'];
            $h_supply[ $arr_products[$i]['supply_id'] ]['shop_id']          = $arr_products[$i]['shop_id'];
            $h_supply[ $arr_products[$i]['supply_id'] ]['order_seq']        = $arr_products[$i]['order_seq'];
            
            // 전체 주문
            $tot_amount       += $arr_products[$i]['prd_amount'];
            $tot_supply_price += $arr_products[$i]['prd_supply_price'];
            $tot_org_price    += $arr_products[$i]['org_price'];
            
            echo "to:" . $tot_org_price . "<br>";
            
            $tot_qty          += $arr_products[$i]['qty'];
                 
            // 공급처별
            $h_supply[ $arr_products[$i]['supply_id'] ]['su_amount']       += $arr_products[$i]['prd_amount'];  // 공급처별 
            $h_supply[ $arr_products[$i]['supply_id'] ]['su_supply_price'] += $arr_products[$i]['prd_supply_price'];
            $h_supply[ $arr_products[$i]['supply_id'] ]['su_org_price']    += $arr_products[$i]['org_price'];
            $h_supply[ $arr_products[$i]['supply_id'] ]['su_qty']          += $arr_products[$i]['qty'];
            $h_supply[ $arr_products[$i]['supply_id'] ]['su_extra_money']  += $arr_products[$i]['extra_money'];
            
            // 취소인 경우
            if ( $arr_products[$i]['order_cs'] ==1 || $arr_products[$i]['order_cs'] ==2 || $arr_products[$i]['order_cs'] ==3 ||  $arr_products[$i]['order_cs'] ==4 )
            {
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_c_amount']       += $arr_products[$i]['prd_amount'];        // 공급처별 취소 결제금액
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_c_supply_price'] += $arr_products[$i]['prd_supply_price'];  // 공급처별 취소 공급가
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_c_org_price']    += $arr_products[$i]['org_price'];         // 공급처별 취소 원가
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_c_qty']          += $arr_products[$i]['qty'];               // 공급처별 취소 개수                
            }
            
            // 사은품 (이미 정산가에 포함되어 있음)
            if ( $arr_products[$i]['is_gift'] == 1 )
            {
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_g_amount']       += $arr_products[$i]['prd_amount'];        // 공급처별 사은품 결제금액
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_g_supply_price'] += $arr_products[$i]['prd_supply_price'];  // 공급처별 사은품 공급가
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_g_org_price']    += $arr_products[$i]['org_price'];         // 공급처별 사은품 원가
                $h_supply[ $arr_products[$i]['supply_id'] ]['su_g_qty']          += $arr_products[$i]['qty'];               // 공급처별 사은품 개수                
            }
            
            // tr_is_pre 선불 : 1, 착불: 0
            $h_supply[ $arr_products[$i]['supply_id'] ]['tr_is_pre'] = $order_data['trans_who'] == "선불" ? 1 : 0;
        }
        
        // 부분 배송 처리..
        // 공급처가 2개 이상이면 부분 배송임.
        if ( count( $h_supply ) > 1 )
            $tr_is_part_deliv = 1;
            
        foreach ( $h_supply as $key => $value )
        {
            $h_supply[ $key ]['tr_is_part_deliv'] = $tr_is_part_deliv;      // 복수의 공급처일 경우 1, 단수의 공급처 0
            
            $h_supply[ $key ]['tot_amount']       = $tot_amount;
            $h_supply[ $key ]['tot_supply_price'] = $tot_supply_price;
            $h_supply[ $key ]['tot_org_price']    = $tot_org_price;
            $h_supply[ $key ]['tot_qty']          = $tot_qty;
            $h_supply[ $key ]['order_pack']       = $order_pack;
            $h_supply[ $key ]['real_supply_price']= $h_supply[ $key ]['su_supply_price'] + $h_supply[ $key ]['su_extra_money']; // 정산금액 + 추가 입금금액
            
            // db에 값을 입력        
            $this->insert_data( $h_supply[ $key ] );
        }
    }
    
    //
    // db에 값을 입력
    //
    function insert_data( $h_data )
    {
        // data 초기화
        $items = array( "su_c_amount"
                       ,"su_c_qty"
                       ,"su_c_supply_price"
                       ,"su_c_org_price"
                       ,"su_g_amount"
                       ,"su_g_supply_price"
                       ,"su_g_org_price");
        
        foreach ( $items as $key )
        {
            $h_data[ $key ] = $h_data[ $key ] ? $h_data[ $key ] : 0;   
        }                       
        
        global $connect;
        $query = "insert stat_supply 
                     set supply_id               = '$h_data[supply_id]'
                        ,shop_id                 = '$h_data[shop_id]'
                        ,order_seq               = $h_data[order_seq]
                        ,order_pack              = $h_data[order_pack]
                        ,tot_amount              = $h_data[tot_amount]
                        ,tot_supply_price        = $h_data[tot_supply_price]
                        ,tot_org_price           = $h_data[tot_org_price]
                        ,tot_qty                 = $h_data[tot_qty]
                        ,su_amount               = $h_data[su_amount]
                        ,su_supply_price         = $h_data[su_supply_price]
                        ,su_org_price            = $h_data[su_org_price]
                        ,su_qty                  = $h_data[su_qty]
                        ,su_extra_money          = $h_data[su_extra_money]
                        ,su_c_qty                = $h_data[su_c_qty]
                        ,su_c_amount             = $h_data[su_c_amount]
                        ,su_c_supply_price       = $h_data[su_c_supply_price]
                        ,su_c_org_price          = $h_data[su_c_org_price]
                        ,su_g_amount             = $h_data[su_g_amount]
                        ,su_g_supply_price       = $h_data[su_g_supply_price]
                        ,su_g_org_price          = $h_data[su_g_org_price]
                        ,tr_is_pre               = $h_data[tr_is_pre]
                        ,tr_is_part_deliv        = $h_data[tr_is_part_deliv]
                        ,crdate                  = Now()
                        ";
        mysql_query( $query, $connect );
    }
    
    // 
    //
    //
    function build_query()
    {
        
    }
    
    function FM00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group;

        // 불량창고 이름
        $bad_name = $_SESSION[EXTRA_STOCK_TYPE];

        // 조회 필드
        $title = 'FM00';
        $f = $this->get_setting();
        
        if( $search )
        {
            // 전체 쿼리
            $query = $this->get_FM00();
            $result = mysql_query($query, $connect);
            
            // 총 개수
            $total_rows = mysql_num_rows( $result );
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //###############################
    // 메인 쿼리
    //###############################
    function get_FM00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group;
        
        $query = "select * from userinfo where level=0 ";
        if( $str_supply_code )
            $query .= " and code in ($str_supply_code) ";
        if( $supply_group )
            $query .= " and group_id = '$supply_group' ";

        $query .= " order by group_id, name";
            
        return $query;
    }
    
    function save_file_FM00()
    {
        global $template, $connect, $title, $search;
        global $supply_code, $str_supply_code, $start_date, $end_date, $stock_type, $supply_group;

        // 조회 필드
        $title = 'FM00_file';
        $f = $this->get_setting();

        $start_time = time();
        $i = 0;
        
        $sum_stock1 = 0;
        $sum_stock2 = 0;
        $sum_stock3 = 0;
        $sum_stock4 = 0;
        $sum_stock5 = 0;
        $sum_stock6 = 0;
        $sum_stock7 = 0;
        $sum_stock8 = 0;
        $sum_stock9 = 0;
        $sum_stock10 = 0;
        $sum_stock11 = 0;
        $sum_stock12 = 0;
        $sum_stock13 = 0;
        $sum_stock14 = 0;
        $sum_stock15 = 0;
        $sum_stock16 = 0;
        $sum_stock17 = 0;
        $sum_stock18 = 0;
    
        $arr_datas = array();
        
        $query = $this->get_FM00();
        $result = mysql_query($query, $connect);

        // 총 개수
        $total_rows = mysql_num_rows( $result );

        while( $data = mysql_fetch_assoc($result) )
        {
            $stock1  = 0;
            $stock2  = 0;
            $stock3  = 0;
            $stock4  = 0;
            $stock5  = 0;
            $stock6  = 0;
            $stock7  = 0;
            $stock8  = 0;
            $stock9  = 0;
            $amount1 = 0;
            $amount2 = 0;
            $amount3 = 0;
            $amount4 = 0;
            $amount5 = 0;
            $amount6 = 0;
            $amount7 = 0;
            $amount8 = 0;
            $amount9 = 0;
            
            $query_stock = "select a.job a_job,
                                   sum(a.qty) sum_a_qty,
                                   sum(a.qty * a.org_price) sum_a_org_price
                              from stock_tx_history a,
                                   products b
                             where a.product_id = b.product_id and
                                   b.supply_code = $data[code] and
                                   a.crdate >= '$start_date 00:00:00' and
                                   a.crdate <= '$end_date 23:59:59' and
                                   a.bad = '$stock_type'
                             group by job";
            $result_stock = mysql_query($query_stock, $connect);
            while( $data_stock = mysql_fetch_assoc($result_stock) )
            {
                switch( $data_stock[a_job] )
                {
                    case "in":
                        $stock1  = $data_stock[sum_a_qty];
                        $amount1 = $data_stock[sum_a_org_price];
                        break;
                    case "retin":
                        $stock2  = $data_stock[sum_a_qty];
                        $amount2 = $data_stock[sum_a_org_price];
                        break;
                    case "out":
                        $stock4  = $data_stock[sum_a_qty];
                        $amount4 = $data_stock[sum_a_org_price];
                        break;
                    case "retout":
                        $stock5  = $data_stock[sum_a_qty];
                        $amount5 = $data_stock[sum_a_org_price];
                        break;
                    case "trans":
                        $stock7  = $data_stock[sum_a_qty];
                        $amount7 = $data_stock[sum_a_org_price];
                        break;
                    case "arrange":
                        $stock8  = $data_stock[sum_a_qty];
                        $amount8 = $data_stock[sum_a_org_price];
                        break;
                }
                
                // 총입고
                $stock3 = $stock1 + $stock2;
                $amount3 = $amount1 + $amount2;
    
                // 총출고
                $stock6 = $stock4 + $stock5;
                $amount6 = $amount4 + $amount5;
            }
            
            // 현재고
            $query_cur = "select sum(a.stock) sum_a_stock,
                                 sum(a.stock * b.org_price) sum_amount 
                            from current_stock a,
                                 products b
                           where a.product_id = b.product_id and
                                 b.supply_code = '$data[code]' and
                                 a.bad = '$stock_type'";
            $result_cur = mysql_query($query_cur, $connect);
            $data_cur = mysql_fetch_assoc($result_cur);
                
            $stock9  = ($data_cur[sum_a_stock] ? $data_cur[sum_a_stock] : 0);
            $amount9 = ($data_cur[sum_amount]  ? $data_cur[sum_amount]  : 0);
            
            $temp_arr = array();
            if( $f[supply_code   ] ) $temp_arr[supply_code   ] = $data[code];
            if( $f[supply_group  ] ) $temp_arr[supply_group  ] = $this->get_supply_group_name($data[group_id]);
            if( $f[supply_name   ] ) $temp_arr[supply_name   ] = $data[name];
            if( $f[stock_in      ] ) $temp_arr[stock_in      ] = $stock1;
            if( $f[stock_retin   ] ) $temp_arr[stock_retin   ] = $stock2;
            if( $f[stock_allin   ] ) $temp_arr[stock_allin   ] = $stock3;
            if( $f[stock_out     ] ) $temp_arr[stock_out     ] = $stock4;
            if( $f[stock_retout  ] ) $temp_arr[stock_retout  ] = $stock5;
            if( $f[stock_allout  ] ) $temp_arr[stock_allout  ] = $stock6;
            if( $f[stock_trans   ] ) $temp_arr[stock_trans   ] = $stock7;
            if( $f[stock_arrange ] ) $temp_arr[stock_arrange ] = $stock8;
            if( $f[stock_current ] ) $temp_arr[stock_current ] = $stock9;
            if( $f[amount_in     ] ) $temp_arr[amount_in     ] = $amount1;
            if( $f[amount_retin  ] ) $temp_arr[amount_retin  ] = $amount2;
            if( $f[amount_allin  ] ) $temp_arr[amount_allin  ] = $amount3;
            if( $f[amount_out    ] ) $temp_arr[amount_out    ] = $amount4;
            if( $f[amount_retout ] ) $temp_arr[amount_retout ] = $amount5;
            if( $f[amount_allout ] ) $temp_arr[amount_allout ] = $amount6;
            if( $f[amount_trans  ] ) $temp_arr[amount_trans  ] = $amount7;
            if( $f[amount_arrange] ) $temp_arr[amount_arrange] = $amount8;
            if( $f[amount_current] ) $temp_arr[amount_current] = $amount9;
            $arr_datas[] = $temp_arr;
            
            $sum_stock1  += $stock1;
            $sum_stock2  += $stock2;
            $sum_stock3  += $stock3;
            $sum_stock4  += $stock4;
            $sum_stock5  += $stock5;
            $sum_stock6  += $stock6;
            $sum_stock7  += $stock7;
            $sum_stock8  += $stock8;
            $sum_stock9  += $stock9;
            $sum_stock10 += $amount1;
            $sum_stock11 += $amount2;
            $sum_stock12 += $amount3;
            $sum_stock13 += $amount4;
            $sum_stock14 += $amount5;
            $sum_stock15 += $amount6;
            $sum_stock16 += $amount7;
            $sum_stock17 += $amount8;
            $sum_stock18 += $amount9;
    
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$i / $total_rows' )</script>";
                FMush();
            }
    
            usleep(10000);
        }

        $temp_arr = array();
        if( $f[supply_code   ] ) $temp_arr[supply_code   ] = "";
        if( $f[supply_group  ] ) $temp_arr[supply_group  ] = "";
        if( $f[supply_name   ] ) $temp_arr[supply_name   ] = "합계";
        if( $f[stock_in      ] ) $temp_arr[stock_in      ] = $sum_stock1;
        if( $f[stock_retin   ] ) $temp_arr[stock_retin   ] = $sum_stock2;
        if( $f[stock_allin   ] ) $temp_arr[stock_allin   ] = $sum_stock3;
        if( $f[stock_out     ] ) $temp_arr[stock_out     ] = $sum_stock4;
        if( $f[stock_retout  ] ) $temp_arr[stock_retout  ] = $sum_stock5;
        if( $f[stock_allout  ] ) $temp_arr[stock_allout  ] = $sum_stock6;
        if( $f[stock_trans   ] ) $temp_arr[stock_trans   ] = $sum_stock7;
        if( $f[stock_arrange ] ) $temp_arr[stock_arrange ] = $sum_stock8;
        if( $f[stock_current ] ) $temp_arr[stock_current ] = $sum_stock9;
        if( $f[amount_in     ] ) $temp_arr[amount_in     ] = $sum_stock10;
        if( $f[amount_retin  ] ) $temp_arr[amount_retin  ] = $sum_stock11;
        if( $f[amount_allin  ] ) $temp_arr[amount_allin  ] = $sum_stock12;
        if( $f[amount_out    ] ) $temp_arr[amount_out    ] = $sum_stock13;
        if( $f[amount_retout ] ) $temp_arr[amount_retout ] = $sum_stock14;
        if( $f[amount_allout ] ) $temp_arr[amount_allout ] = $sum_stock15;
        if( $f[amount_trans  ] ) $temp_arr[amount_trans  ] = $sum_stock16;
        if( $f[amount_arrange] ) $temp_arr[amount_arrange] = $sum_stock17;
        if( $f[amount_current] ) $temp_arr[amount_current] = $sum_stock18;
        $arr_datas[] = $temp_arr;
    
        $this->make_file_FM00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file_FM00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $str_supply_code, $query_type, $query_str, $products_sort, $except_soldout, $category, $display_result;

        $saveTarget = _upload_dir . $filename; 

        // 조회 필드
        $title = 'FM00_file';
        $f = $this->get_setting();

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        if( $f[supply_code   ] ) $buffer .= "<td class=header_item>공급처코드</td>";
        if( $f[supply_group  ] ) $buffer .= "<td class=header_item>공급처그룹</td>";
        if( $f[supply_name   ] ) $buffer .= "<td class=header_item>공급처명</td>";
        if( $f[stock_in      ] ) $buffer .= "<td class=header_item>입고</td>";
        if( $f[stock_retin   ] ) $buffer .= "<td class=header_item>반품입고</td>";
        if( $f[stock_allin   ] ) $buffer .= "<td class=header_item>총입고</td>";
        if( $f[stock_out     ] ) $buffer .= "<td class=header_item>출고</td>";
        if( $f[stock_retout  ] ) $buffer .= "<td class=header_item>반품출고</td>";
        if( $f[stock_allout  ] ) $buffer .= "<td class=header_item>총출고</td>";
        if( $f[stock_trans   ] ) $buffer .= "<td class=header_item>배송</td>";
        if( $f[stock_arrange ] ) $buffer .= "<td class=header_item>조정</td>";
        if( $f[stock_current ] ) $buffer .= "<td class=header_item>현재고</td>";
        if( $f[amount_in     ] ) $buffer .= "<td class=header_item>입고금액</td>";
        if( $f[amount_retin  ] ) $buffer .= "<td class=header_item>반품입고금액</td>";
        if( $f[amount_allin  ] ) $buffer .= "<td class=header_item>총입고금액</td>";
        if( $f[amount_out    ] ) $buffer .= "<td class=header_item>출고금액</td>";
        if( $f[amount_retout ] ) $buffer .= "<td class=header_item>반품출고금액</td>";
        if( $f[amount_allout ] ) $buffer .= "<td class=header_item>총출고금액</td>";
        if( $f[amount_trans  ] ) $buffer .= "<td class=header_item>배송금액</td>";
        if( $f[amount_arrange] ) $buffer .= "<td class=header_item>조정금액</td>";
        if( $f[amount_current] ) $buffer .= "<td class=header_item>현재고금액</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                if( $key == 'supply_code' || $key == 'supply_group' || $key == 'supply_name' )
                    $buffer .= "<td class=str_item>$v</td>\n";
                else
                    $buffer .= "<td class=num_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download_FM00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "aaa.xls" );
    }    


    
    //*****************************
    // 조회 필드 읽기
    //*****************************
    function get_setting()
    {
        global $connect, $title;
        
        $query = "select field from field_set where title='$title'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $f = array();
        foreach( explode(",",$data[field]) as $_f )
            $f[$_f] = 1;

        return $f;
    }
    
    //*****************************
    // 조회 필드 설정
    //*****************************
    function save_setting()
    {
        global $connect, $title, $setting;
        
        $query = "update field_set set field='$setting' where title='$title'";
        mysql_query($query, $connect);
    }
    
    //*****************************
    // 재고정보
    //*****************************
    function get_stock_info()
    {
        global $connect, $product_id;

        $val = array();
        
        // 상품원가
        $data = class_product::get_info($product_id, 'org_price');
        $org_price = $data[org_price];
        
        $stock_obj = new class_stock();
        $val['stock_n'] = $stock_obj->get_current_stock($product_id, 0);
        $val['stock_np'] = $val['stock_n'] * $org_price;
        $val['stock_b'] = $stock_obj->get_current_stock($product_id, 1);
        $val['stock_bp'] = $val['stock_b'] * $org_price;
        
        echo json_encode($val);
    }
    
}
?>
