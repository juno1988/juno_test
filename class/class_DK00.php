<?
//====================================
//
// 프리미엄 배송우선순위 관리
// name: class_DK00
// date: 2010.4.24 - jk
//
require_once "class_top.php";
require_once "class_order.php";
require_once "class_product.php";
require_once "class_pre_order.php";

class class_DK00 extends class_top {
    

    // init class
    function class_DK00()
    {
            
    }
    
    // 배송 
    function DK00()
    {
            global $template;
            $top_url = base64_encode ( $this->build_link_url() );

            if ( !$start_date )
            $start_date = date("Y-m-d", mktime (0,0,0,date("m")  , date("d")-5, date("Y")));

            $obj_pre = new class_pre_order();
            $result = $obj_pre->get_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function DK02()
    {
            global $template, $seq, $page, $top_url;
    
            $obj     = new class_order();
            $obj_pre = new class_pre_order();
    
            $link_url = base64_encode ( $this->build_link_url() );
    
            // query조건 DK00에서 모두 사용함
            $arr_options = $obj_pre->get_options( $seq );
            $data        = $obj_pre->get_infos( $seq );
    
            //$result     = $obj->get_list( $arr_options );
            $req_cnt    = $obj->get_count( $arr_options );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function del_rule()
    {
        global $connect,$seq;
        
        // 초기화..
        $query = "select * from pre_order where seq=$seq";
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->cancel_apply_one( $data );
        }    
        
        // 우선순위 rule 삭제.
        $query = "delete from pre_order where seq='$seq'";   
        mysql_query( $query, $connect );
    }

    //////////////////////////////
    //
    // 룰 조회.. 
    function query_rule()
    {
        global $connect;
        
        $arr_data = array();
        $query = "select * from pre_order order by priority desc";
        
        $result = mysql_query( $query, $connect );
        $arr_data[row_count] = 0;
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            // 요청 개수 확인
            $data[req_cnt] = $this->get_ready_cnt( $data[shop_id] );
            $data[apply_cnt] = $this->get_apply_cnt( $data[shop_id], $data[priority] );
            $data[shop_name] = $this->get_shop_name( $data[shop_id] );
            $arr_data[rows][] = $data;
            $arr_data[row_count]++;
        }
        echo json_encode( $arr_data );
    }

    ///////////////////////////////
    //
    // 판매처 상품명 가져오기
    function get_shop_name($shop_id )
    {
        global $connect;
        
        $_shop_name = "";
        if ( $shop_id )
        {
            $query = "select shop_name from shopinfo where shop_id=$shop_id";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $_shop_name = $data[shop_name];
        }
        
        return $_shop_name;
    }

    ////////////////////////////
    //
    // 전체 룰 적용....
    function apply_rule()
    {
        global $connect;
        
        // 적용을 초기화한 후 전체 적용..
        $this->cancel_apply();
        
        $query = "select * from pre_order";
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->apply_one( $data );
        }   
    }
    
    function cancel_apply()
    {
        global $connect;
        
        $query = "select * from pre_order";
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->cancel_apply_one( $data );
        }      
    }
    
    ////////////////////////////////
    // 
    // 1개의 업체 적용..
    function cancel_apply_one( $data )
    {
        global $connect;
        
        $query = "update orders set priority=null
                   where shop_id  = $data[shop_id] 
                     and status   = 1 
                     and order_cs not in (1,2,3,4,12)";
        mysql_query( $query , $connect );
    }
    
    ////////////////////////////////
    // 
    // 1개의 업체 적용..
    function apply_one( $data )
    {
        global $connect;
        
        $query = "update orders set priority=$data[priority] 
                   where shop_id=$data[shop_id] 
                     and status=1 
                     and order_cs not in (1,2,3,4,12)";
        mysql_query( $query , $connect );
    }

    /////////////////////////////////////
    //
    // 배송 예정 개수
    function get_apply_cnt( $shop_id, $priority )
    {
        global $connect;
        
        $_cnt = 0;
        if ( $shop_id )
        {
            $query = "select count(*) cnt 
                        from orders 
                       where shop_id = $shop_id 
                         and status  = 1 
                         and order_cs not in (1,2,3,4,12)
                         and priority = $priority";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );            
            $_cnt = $data[cnt];
        }
        return $_cnt;
    }
    
    //////////////////////////////////////
    // 배송 예정 개수
    function get_ready_cnt( $shop_id )
    {
        global $connect;
        
        if ( $shop_id )
        {
            $query = "select count(*) cnt 
                        from orders 
                       where shop_id = $shop_id 
                         and status  = 1 
                         and order_cs not in (1,2,3,4,12)";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );            
            return $data[cnt];
        }
        return 0;
    }

    ////////////////////////////////////////
    //
    // 배송 준비중인 주문의 개수를 찾는다.
    function query_order()
    {
        global $shop_id, $priority, $page, $connect;
        
        $page  = $page ? $page : 1;
        $start = ( $page - 1 ) * 20;
        
        $arr_result = array();
        
        // total count
        $query = "select count(*) cnt 
                    from orders 
                   where status=1 
                     and order_cs not in (1,2,3,4,12)
                     and shop_id=$shop_id";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $arr_result['count'] = $data[cnt] ? $data[cnt] : 0;
        
        // data
        $query = "select collect_date, count(*) cnt
                    from orders 
                   where status=1 
                     and order_cs not in (1,2,3,4,12)
                     and shop_id=$shop_id
                     group by collect_date";
                     
        $result = mysql_query( $query, $connect );
        $rows = mysql_num_rows( $result );
        $arr_result[row_cnt] = $rows;
        while ($data   = mysql_fetch_assoc( $result ))
        {
            $arr_result["rows"][] = $data;   
        }
        
        $arr_result[shop_id] = $shop_id;
        
        echo json_encode( $arr_result );
    }

    function save_priority_rule()
    {
        global $shop_id, $priority, $connect;
        
        $query = "insert into pre_order 
                     set priority = $priority
                         ,shop_id = $shop_id";
        mysql_query( $query, $connect );                        
        echo $query;
    }

    function DK01()
    {
        global $template, $seq, $page,$top_url, $connect;
        global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq;
    
        /*
            $obj     = new class_order();
            //$obj_pre = new class_pre_order();
    
            // query조건 DK00에서 모두 사용함
            // $arr_options = $this->build_options();
            if ( $seq )
               $arr_pre_order = $obj_pre->get_infos( $seq );
    
            if ( $page )
            {
                $result     = $obj->get_list( $arr_options );
                $req_cnt    = $obj->get_count( $arr_options );
            }
        */
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //======================================
    //  조회
    function query()
    {
        global $template, $options, $page,$product_name,$shop_id;

        $query     = "select * ";
        $query_cnt = "select count(*) cnt ";
        
        $option = " from orders where ";


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ////////////////////////////////////////////
    // 우선 순위 update
    //        
    function update_priority ()
    {
        global $seq, $priority, $warehouse, $top_url, $link_url;

        $obj_pre = new class_pre_order();
        $obj_pre->update_priority( $priority, $seq, $warehouse );
        $this->redirect ( "?" . base64_decode( $link_url ) . "&top_url=$top_url" );
    }

    function build_options()
    {
        global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq;
        // status는 미배송
        $status   =  "1,2,3,4,5,6";
        $order_cs =  "1,2,3,4";
        $arr_options = array ( 
                        shop_id      => $shop_id,
                        product_name => $product_name,
                        options      => $options,
                        status       => $status,
                        order_cs     => $order_cs );

        return $arr_options;
    }

    ///////////////////////////////////////
    // 정보 저장
    function save_options()
    {
        echo "save options";
        global $template;
        global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq, $top_url;
        $obj     = new class_order();
        $obj_pre = new class_pre_order();

        $top_url = base64_encode( $this->build_link_url() );
        // reg_title
        $seq = $obj_pre->reg_title( $txt_title );

        // reg_option
        $arr_options = $this->build_options();
        $obj_pre->reg_options( $arr_options, $seq );

        $this->redirect( "?template=DK02&seq=$seq&top_url=$top_url");
    }

    ////////////////////////////
    // title 저장
    function reg_title()
    {
        global $template;
        global $shop_id, $product_name, $options, $status, $order_cs, $page, $top_url, $txt_title;
        echo "reg_title: $txt_title<br>";

        class_pre_order::reg_title( $txt_title );        
echo "link_url: $top_url ";
        $this->redirect( "?". base64_decode( $top_url ) );
    }
    


    function request_detail()
    {
        global $_date;
        

        echo "<table border=1 width=500>
                <tr>
                    <td>시각</td>
                    <td>개수</td>
                    <td>msg</td>
                    <td>사용자</td>
                    <td>상태</td>
                </tr>";

        if ( $result )
        while ( $data = mysql_fetch_array ( $result ) )
        {
                echo "
                <tr>
                    <td>$data[reg_date]</td>
                    <td>$data[req_cnt]</td>
                    <td>$data[msg]</td>
                    <td>$data[req_user]</td>
                    <td>$data[status]</td>
                </tr>
                ";
        }
        echo "</table>";
        
    }

    //=========================================
    // 주문의 개수 파악.
    // date: 2007.11.13 - jk
    function get_count ( $_switch, $_date )
    {
        
        switch ( $_switch )
        {
            case "tot_orders":
                return $this->cnt_tot_orders( $_date );
                break;
            case "trans_request":
                
                break;
        }

    }

    //=============================
    // 전체 주문 개수
    function cnt_tot_orders( $_date )
    {
        global $connect;
        $query = "select count(*) cnt 
                   from orders a, products b
                   where a.product_id = b.product_id
                     
                     and a.collect_date = '$_date'
                     and a.status = 1
                     and a.order_cs not in (1,2,3,4,12)"; 

        $result = mysql_query ( $query , $connect );
        $data = mysql_fetch_array ( $result );
        return $data[cnt];
    }

    function del_seq()
    {
        global $seq;
        $obj_pre = new class_pre_order();
        $result = $obj_pre->del_seq( $seq );

        $this->jsAlert("삭제 완료");
        $this->redirect( "?template=DK00" );
    }

    /////////////////////////////////////
    // 전체 data sync
    function tot_sync()
    {
        global $connect;

        $obj_pre = new class_pre_order();
        $result = $obj_pre->get_list();

        $this->show_wait();

        // repack
        /* 이게 뭐지? 최부장 요청으로..변경해놓은듯.
        $arr_options[priority] = 99;
        $obj_pre->reflect_priority( $arr_options, 99 );
        */
        // 조건 logic을 가져옴
        while ( $data = mysql_fetch_array( $result ) )
        {
            // 상세 검색 로직을 가져옴
            $arr_options = $obj_pre->get_options( $data[seq] );

            if ( $arr_options )
            {
                $obj_pre->reflect_priority( $arr_options, $data[priority], $data[warehouse] );
            }
        }
        $this->hide_wait();
        $this->jsAlert("작업 완료");
        $this->redirect("?template=DK00");
    }

    function init()
    {
        $this->show_wait();
        $obj_pre = new class_pre_order();
        $obj_pre->init_priority();

        $this->hide_wait();
    }

    ////////////////////////////////////
    // 개별 sync
    function one_sync()
    {
        global $seq;

        $obj_pre = new class_pre_order();
        $data    = $obj_pre->get_infos( $seq );
        
        $this->show_wait();

         $arr_options = $obj_pre->get_options( $data[seq] );
        if ( $arr_options )
        {
            $obj_pre->reflect_priority( $arr_options, $data[priority], $data[warehouse] );
        }

        $this->hide_wait();
        $this->jsAlert("완료");
        $this->redirect( "?template=DK00" );
    }

    //=============================
    //
    // 배송 요청
    //
    function trans_request()
    {
        global $_date, $connect;

        
        $query = "select a.* from orders a, products b 
                   where a.product_id = b.product_id
                     
                     and a.collect_date = '$_date'
                     and status = 1
                     and order_cs not in (1,2,3,4,12)";
        $result = mysql_query ( $query, $connect );

        $cnt = 0;        
        $_date = "";
        while ( $data = mysql_fetch_array ( $result ) )
        {
                // name과 option을 가져와야 함
                class_product::get_product_name_option($data[product_id], &$name, &$option);

                $data[product_name] = $name;
                $data[options] = $option;

                
          }

        echo "$cnt 개 완료";
        
        
        
    }

}

?>
