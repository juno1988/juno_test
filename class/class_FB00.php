<?
require_once "class_E.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_top.php";
require_once "class_D.php";
require_once "class_transcorp.php";
require_once "class_stock.php";
require_once "class_E900.php";

////////////////////////////////
// class name: class_FB00
//
class class_FB00 extends class_top 
{ 
    function FB00()
    {
        global $template, $start_date, $end_date, $shop_id, $page, $order_id, $search_all;
        
        if ( !$page )
        {
            $_REQUEST[page] = 1;
            $page = 1;
        }
        
        $par_arr = array("template","action","shop_id","start_date","end_date","search_all","page");
        
        $line_per_page = _line_per_page;
        $link_url_list = $this->build_link_par($par_arr);  
        $link_url = "?" . $this->build_link_url();
        
        echo "<script>show_waiting()</script>";             
        
        $result = $this->search( &$total_rows );          
        
        if( !$start_date )
            $start_date = date("Y-m-d", strtotime("-60 day") );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        echo "<script>hide_waiting()</script>";
    }

    //============================================
    // 검색로직
    // 
    function search( &$total_rows )
    {
        global $connect, $start_date, $end_date, $shop_id, $page, $order_name, $search_all, $line_per_page;

        if ( !$start_date)  $start_date = date('Y-m-d', strtotime('-60 day'));
        if ( !$end_date )  $end_date = date('Y-m-d');

        if( $search_all )
        {
            $query = " select a.seq          a_seq         ,
                              a.pack         a_pack        ,
                              a.status       a_status      ,
                              a.order_cs     a_order_cs    ,
                              a.hold         a_hold        ,
                              a.collect_date a_collect_date,
                              a.shop_id      a_shop_id     ,
                              a.order_id     a_order_id    ,
                              a.product_name a_product_name,
                              a.recv_name    a_recv_name   ,
                              a.trans_date   a_trans_date  ,
                              a.trans_corp   a_trans_corp  ,
                              a.trans_no     a_trans_no    ,
                              b.trans_no     b_trans_no    ,
                              b.crdate       b_crdate
                         from orders a, trans_check b
                        where b.crdate >= '$start_date 00:00:00' and 
                              b.crdate <= '$end_date 23:59:59' and 
                              a.seq = b.seq";
        }
        else
        {
            $query = " select a.seq          a_seq         ,
                              a.pack         a_pack        ,
                              a.status       a_status      ,
                              a.order_cs     a_order_cs    ,
                              a.hold         a_hold        ,
                              a.collect_date a_collect_date,
                              a.shop_id      a_shop_id     ,
                              a.order_id     a_order_id    ,
                              a.product_name a_product_name,
                              a.recv_name    a_recv_name   ,
                              a.trans_date   a_trans_date  ,
                              a.trans_corp   a_trans_corp  ,
                              a.trans_no     a_trans_no    ,
                              b.trans_no     b_trans_no    ,
                              b.crdate       b_crdate
                         from orders a, trans_check b
                        where b.crdate >= '$start_date 00:00:00' and 
                              b.crdate <= '$end_date 23:59:59' and 
                              a.seq = b.seq and
                              a.status<8";
        }
        // 판매처
        if ( $shop_id )        
            $query .= " and a.shop_id = '$shop_id' ";

	    $query .= " order by a.seq ";

        // 전체 주문 개수
        $total_rows = 0;
        $trans_check_arr = array();
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 합포이면서 취소인 경우, pack 중에 정상 배송인 건이 있는지 확인한다.
            if( $data[a_pack] > 0 && $data[a_order_cs] == 1 )
            {
                // 같은 pack에서 배송건 찾는다.
                $query_pack = "select seq from orders where pack=$data[a_pack] and status=8";
                $result_pack = mysql_query($query_pack, $connect);
                if( mysql_num_rows($result_pack) > 0 )  continue;
            }
            
            if( $data[a_status] == 8 )
                $sts = 8;
            else
                $sts = 0;
            
            $trans_check_arr[] = array(
                "a_seq"          => $data[a_seq]         ,
                "a_pack"         => $data[a_pack]        ,
                "a_status"       => $data[a_status]      ,
                "a_order_cs"     => $data[a_order_cs]    ,
                "a_hold"         => $data[a_hold]        ,
                "a_collect_date" => $data[a_collect_date],
                "a_shop_id"      => $data[a_shop_id]     ,
                "a_order_id"     => $data[a_order_id]    ,
                "a_product_name" => $data[a_product_name],
                "a_recv_name"    => $data[a_recv_name]   ,
                "a_trans_date"   => $data[a_trans_date]  ,
                "a_trans_corp"   => $data[a_trans_corp]  ,
                "a_trans_no"     => $data[a_trans_no]    ,
                "b_trans_no"     => $data[b_trans_no]    , 
                "b_crdate"       => $data[b_crdate]      ,
                "sts"            => $sts
            );
            $total_rows++;
        }
        return $trans_check_arr;
    }
    
    //택배사 링크
    function corp_link( $seq,$trans_no )
    {
        global $connect;
        
        $query = "select trans_corp, trans_no from orders where seq=$seq ";
        $result = mysql_query( $query, $connect );        
        $data   = mysql_fetch_assoc( $result ); 
        
        //택배사 이름
        $trans_name = class_transcorp::get_corp_name( $data[trans_corp]);
        //택배사 이름으로 택배조회 링크걸기
        $result = class_top::print_delivery( $trans_name, $trans_no, 0, "FB00");
        
        return $result;
    }
    
    /////////////////////////////////
    // orders table 정보 검색
    function get_orders($seq)
    {
        global $connect;
        // 정보 검색
        $query  = "select * from orders where seq=$seq and status=7";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );   
    
        return $data;
    }
   
    // 배송확인 업데이트
    function trans_update($seq)
    {
        global $connect;
        
        $data_orders = $this->get_orders($seq);
        $seq_list = $this->get_seq_list2($seq, $data_orders[pack]);

        // 보류면 실행 안함
        if( $data_orders[hold] > 0 )
        {
            debug("보류 주문 pass");
            return;
        }

        // 송장상태 아니면 실행 안함
        if( $data_orders[status] != 7 )
        {  
            debug("송장 상태 아님 paxx");
            return;
        }

        // 전체취소면 실행 안함
        $query = "select seq from orders where seq in ($seq_list) and order_cs <> 1";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) == 0 )
        {
            debug("전체 취소 누락 pass");    
            return;
        }

        // 배송확인 업데이트 쿼리
        $query = "update orders set trans_date_pos=now(), status=8 where seq in ($seq_list) and order_cs <> 1";
        mysql_query ( $query, $connect );

        // 재고차감
        $obj = new class_stock();
        $query_prd = "select product_id, qty, order_seq from order_products 
                       where order_seq in ($seq_list) 
                         and order_cs not in (1,2)
                         ";
                    
        $result_prd = mysql_query($query_prd, $connect);
        while( $data_prd = mysql_fetch_assoc($result_prd) )
        {
            $obj->set_stock( array( type       => 'trans',
                                    product_id => $data_prd[product_id], 
                                    bad        => 0,
                                    location   => 'Def',
                                    qty        => $data_prd[qty],
                                    memo       => '배송누락 배송처리',
                                    order_seq  => $data_prd[order_seq] ) );
        }
        
        $_seq = $seq;
        global $seq;    // seq가 global로 선언되어 있어야 cs가 남는다? 뭔가 이상함 2012.7.24
        $seq = $_seq;   
        $oCs = new class_E900();
        $oCs->csinsert3($seq, 6, "배송누락 배송처리");
        
    }
    
    // 전체 배송확인 위한 주문 검색
    function search2()
    {
        global $connect, $start_date, $end_date, $shop_id, $page, $order_name;
       
        if ( !$start_date)  $start_date = date('Y-m-d', strtotime('-60 day'));
        if ( !$end_date )  $end_date = date('Y-m-d');

        // 주문 정보 , 확인일 기준
        $query = " select orders.seq as seq,
                          orders.pack a_pack,
                          orders.order_cs a_order_cs
                     from orders, trans_check
                    where trans_check.crdate >= '$start_date 00:00:00' 
                      and trans_check.crdate <= '$end_date 23:59:59'
                      and orders.seq=trans_check.seq
                      and orders.status < 8     
                      and orders.hold = 0";
        // 판매처
        if ( $shop_id )        
            $query .= " and orders.shop_id = '$shop_id' ";

        $query .= " order by trans_check.seq";
        
        $result = mysql_query( $query, $connect );
        return $result;
    }
    
    // 개별 배송확인
    function confirm_trans()
    {
        global $seq;
        
        $this->trans_update($seq);
    } 
    
    // 전체 배송확인
    function confirm_trans_all()
    {
        global $template, $connect, $start_date, $end_date, $shop_id, $page, $order_id, $search_all;

        echo "<script>show_waiting()</script>";
        flush();

        // 정보 검색
        $result = $this->search2();
        
        while( $data_orders = mysql_fetch_assoc($result) )
        {  
            //################################################
            // 기존 로직에 문제가 있음.
            // 2013-07-24 장경희
            //################################################

            // 합포이고 pack 이면서 취소인 경우, pack 이 아니면서 정상 배송인 건이 있는지 확인한다.
            // if( $data_orders[seq] == $data_orders[a_pack] && $data_orders[a_order_cs] == 0 )

            // 합포이면 배송인 건이 있는지 확인한다.
            if( $data_orders[a_pack] > 0 )
            {
                // 같은 pack에서 배송건 찾는다.
                $query_pack = "select seq from orders where pack=$data_orders[a_pack] and status=8";
                $result_pack = mysql_query($query_pack, $connect);
                if( mysql_num_rows($result_pack) > 0 )  continue;
            }
            
            $this->trans_update($data_orders[seq]); 
        }

        if ( !$page )
        {
            $_REQUEST[page] = 1;
            $page = 1;
        }
        
        $par_arr = array("template","action","shop_id","start_date","end_date","search_all","page");
        
        $line_per_page = _line_per_page;
        $link_url_list = $this->build_link_par($par_arr);  
        $link_url = "?" . $this->build_link_url();
        
        
        $result = $this->search( &$total_rows );          
        
        if( !$start_date )
            $start_date = date("Y-m-d", strtotime("-60 day") );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        echo "<script>hide_waiting()</script>";
    }
    
    function get_seq_list2($seq, $pack)
    {
        global $connect;
        
        $seq_list = '';
        if( $pack > 0 )
        {
            $query = "select seq from orders where pack=$pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $seq_list .= ($seq_list ? ',' : '') . $data[seq];
        }
        else
            $seq_list = $seq;

        return $seq_list;
    }
}

?>