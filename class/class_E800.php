<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_3pl.php";
require_once "class_E900.php";
require_once "class_order.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_cancelformat.php";
require_once "class_ui.php";

////////////////////////////////
// class name: class_E800
//
class class_E800 extends class_top {

    function confirm()
    {
        global $connect, $seq;
        
        $query = "update auto_canceled_order set status=0 where seq=$seq";   
        mysql_query( $query, $connect );
        
        $arr_result = array();
        $arr_result["query"] = $query;
        $arr_result["success"] = 1;
        
        echo json_encode( $arr_result );
    }

    // 전체 확인
    function confirm_all()
    {
        global $connect, $seq, $start_date, $end_date,$status, $type;
        
        /*
        $query = "update auto_canceled_order set status=0 
                   where status <> 0 
                     and cancel_date >= '$start_date 00:00:00'
                     and cancel_date <= '$end_date 23:59:59'";   
        */
        $query = "update auto_canceled_order , orders
                     set auto_canceled_order.status = 0 
                    where auto_canceled_order.seq = orders.seq
                      and auto_canceled_order.cancel_date >= '$start_date 00:00:00'
                      and auto_canceled_order.cancel_date <= '$end_date 23:59:59'
                      and auto_canceled_order.status <> 0
                      and orders.status in ( $status )";
	
		if ( $type )
			$query .= " and auto_canceled_order.type = '$type'";               

        mysql_query( $query, $connect );
        
        $arr_result = array();
        $arr_result["query"] = $query;
        $arr_result["success"] = 1;
        
        echo json_encode( $arr_result );
    }

    function cancel_confirm()
    {
        global $connect, $seq;
        
        $query = "update auto_canceled_order set status=8 where seq=$seq";   
        mysql_query( $query, $connect );
        
        $arr_result = array();
        $arr_result["query"] = $query;
        $arr_result["success"] = 1;
        
        echo json_encode( $arr_result );
    }

    function canceled_list()
    {
            global $connect,$status, $start_date, $end_date, $shop_id,$view, $type;
            
            // 택배사 명 따기.
            $query = "select * from trans_info";
            $result = mysql_query( $query, $connect );
            $arr_trans_info = array();
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $arr_trans_info[ $data[id] ] = $data[trans_corp];
            }
            
            $arr    = array();
            $query = "select a.order_id, a.shop_id,a.order_name,b.cancel_date,a.status,a.seq,a.qty,b.status is_confirm,a.trans_date_pos,a.trans_no,a.trans_corp,a.product_name,b.type
                        from orders a, auto_canceled_order b
                       where a.seq = b.seq
                         and b.cancel_date >= '$start_date 00:00:00'
                         and b.cancel_date <= '$end_date 23:59:59' ";
                         
            if( _DOMAIN_ == 'lalael2' )
                $query .= " and a.collect_date>='2012-08-23' ";
            
            if ( $shop_id )
                $query .= " and a.shop_id=$shop_id ";
    
            if ( $status )
                $query .= " and a.status in ($status)";

			if ( $type )
				$query .= " and b.type = '$type' ";

            // 0: 전체
            // 1: 미확인
            // 2: 확인
            if ( $view == 1 )
            {
                $query .= " and b.status <> 0";
            }
            else if ( $view > 1 )
            {
                $query .= " and b.status = 0";
            }
               
            if ( $status == 8 )
                $query .= " limit 400";            
            else
            {
                $query .= " limit 100";            
            }
                
            debug( $query );
    
            $result = mysql_query( $query, $connect );
            $obj = new class_product();
    
            $arr_data[] = array(
                "주문번호"
                ,"관리번호"
                ,"판매처"
                ,"배송일"
                ,"취소일"
                ,"상태"
                ,"타입"
                ,"개수"
                ,"확인"
                ,"상품명"
                ,"주문자"  
            );
    
            while ( $data = mysql_fetch_array( $result ) )
            {
                $str_status = array( "1" => "접수", "7"=>"송장입력", "8"=>"배송", "0" => "배송확인" );
                $str_cancel_type = array( "cancel" => "취소", "refund"=>"반품", "" => "" );

                $p_info     = $obj->get_info( $data[product_id], "name" );
                $shop_name  = $this->get_shop_name( $data[shop_id] );
                $i++;
                $arr[] = array(
                            no          => $i 
                            ,order_id   => $data[order_id]
                            ,seq        => $data[seq]
                            ,seq_linked => $this->popupcs($data[seq])
                            ,shop_id    => $shop_name
                            ,trans_date_pos => $data[trans_date_pos]
                            ,cancel_date=> $data[cancel_date]
                            ,status     => $str_status[$data[status]]
                            ,qty        => $data[qty]
                            ,is_confirm => $data[is_confirm]
                            //,product_name => $p_info['name']
                            ,product_name => $data['product_name']
                            ,order_name => $data[order_name]
                            ,trans_no   => $data[trans_no]
                            ,trans_corp => $arr_trans_info[ $data[trans_corp] ]
                            ,type       => $str_cancel_type[ $data[type] ]
                );
                
                // save file
                $arr_data[] = array(
                    $data[order_id]            
                    ,$data[seq]                 
                    ,$shop_name             
                    ,$data[trans_date_pos]
                    ,$data[cancel_date]         
                    ,$str_status[$data[status]] 
                    ,$str_cancel_type[$data[type]] 
                    ,$data[qty]                 
                    ,$data[is_confirm]          
					,$data['product_name']
                    ,$data[order_name]          
                    ,$data[trans_no]
                );
            }
            echo json_encode( $arr );
            
            // file 생성.
            $obj = new class_file();
            $obj->save_file($arr_data, "E807_취소결과.xls");       
    }

    ///////////////////////////////////////////
    function E806()
    {
        global $template;
        global $connect, $shop_code;

        $query = "select * from shopinfo 
                       where shop_id%100 = $shop_code 
                       order by shop_id";
        $result = mysql_query( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////
    function E800()
    {
        global $connect;
        global $template;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;

        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-2 day'));
        $this->cs_list( &$total_rows, &$r_cs );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E801()
    {
        global $template;
        global $connect;

        $query = "select shop_id, shop_name from shopinfo";
        $result = mysql_query ( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E805()
    {
        global $template;
        global $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

 
    function E804()
    {
        global $template;
        global $connect;

        $query = "select shop_id, shop_name from shopinfo";
        $result = mysql_query ( $query, $connect );

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-2 day'));
        $this->cs_list( &$total_rows, &$r_cs );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 자동 취소
    function E808()
    {
        global $template;
        global $connect;

        $query = "select * from shopinfo where shop_id%100 in (1,2,50,11) order by shop_name";
        $result = mysql_query ( $query, $connect );


        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function E807()
    {
        global $template;
        global $connect;

        $query = "select shop_id, shop_name from shopinfo";
        $result = mysql_query ( $query, $connect );

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-7 day'));
        $this->cs_list( &$total_rows, &$r_cs );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function E802()
    {
        global $template;
        global $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E803()
    {
        global $template, $order_id;
        global $connect;

        $query = "select seq from orders where order_id='$order_id'";
        $result = mysql_query ( $query, $connect );

        $i = 0;
        $is_multi = 0; // false
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $seq = $data[seq];
            $i++;
        }

        if ( $i > 0 )
            $is_multi = 1; // true

   

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function del_cs_info()
    {
        global $template, $connect, $shop_id;

        $query = "delete from shop_csinfo where shop_id='$shop_id'";
        mysql_query ( $query );

        $this->redirect( "template.htm?template=$template" );
        // $this->closewin();
        exit;
    }

    //////////////////////////////////////////////////////////
    // add by jk for E805 popup
    // 2006.6.23
    // E805_upload.php 대신에 작업 함
    function upload_cancel()
    {
        global $shop_id, $connect;

        $obj = new class_file();
        $arr_result = $obj->upload();
        $arr_format = class_cancelformat::load_format( 1 );        // nojson option 1

        $query = "delete from shop_csinfo where shop_id='$shop_id'";
        mysql_query( $query, $connect );

        for ( $i=0; $i < count($arr_result ); $i++)
        {
            $_result = $arr_result[$i];

            $order_id     = $_result[ $arr_format[order_id]-1 ];
            $product_id   = $_result[ $arr_format[product_id]-1 ];
            $product_name = $_result[ $arr_format[product_name]-1 ];                // 추가
            $order_name   = $_result[ $arr_format[order_name]-1 ];                // 추가
            $cancel_date  = $_result[ $arr_format[cancel_date]-1 ];                // 추가
            $qty          = $_result[ $arr_format[qty]-1 ];
            $reason       = $_result[ $arr_format[reason]-1 ];

            //$order_id = str_replace(" ","",$order_id);

            if ( $order_id )
            {
                $sql = " insert into shop_csinfo set
                             order_id      = '$order_id',     
                             shop_id       = '$shop_id',
                             product_id    = '$product_id',
                             cancel_date   = '$cancel_date',
                             collect_date  = Now(),
                             memo          = '$reason',
                             product_name  = '".trim(strip_tags(addslashes( $product_name)))."',
                             order_name    = '$order_name',   
                             qty           = '".trim($qty)."'";
            // echo "<pre>$sql</pre>-----<br>";
                mysql_query( $sql, $connect );
            }
        }

        $this->redirect("?template=E804&shop_id=$shop_id");
        exit;
    }
    //////////////////////////////////////////////////////////
    // add by sy.hwang for E802 popup
    function upload()
    {
        global $template;
        global $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . "_upload.php";

        exit;
    }

    function delinfo()
    {
        global $connect, $template;

        $query = "delete from shop_csinfo";
        mysql_query ( $query, $connect );

        $this->redirect("?template=$template");
        exit;
    }

    //////////////////////////////////////////////////////////
    //
    // 취소 처리 적용
    // Date: 2006.5.2 - jk.ryu
    //
    function cancel_apply()
    {
        global $template, $x_hold;
        global $connect;

        $transaction = $this->begin("취소적용");

        //////////////////////////////////////////////////////
        //
        // 취소 테이블의 주문 상태를 조회 한다
        $query    = "select * from shop_csinfo";
        $result   = mysql_query ( $query, $connect );
        $num_rows = mysql_num_rows ( $result );

        $i = 0;
        $j = 0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            // 몇 번째 data를 처리 중인지 출력
            $i++; 
                $str = " $i / $num_rows 번 데이터 처리중";
            $this->disp_message ( $str ); 
            
            //$_order_id   = str_replace(" ", "", $data[order_id] );
            $_order_id   = trim($data[order_id]);
            $_product_id = str_replace(" ", "", $data[product_id] );
            
            // 주문의 상태 check 
            $query2  = "select seq, status, order_cs, pack 
                          from orders where order_id='$_order_id'";
                          
            if ( $data[product_id] )
                $query2 .= " and shop_product_id = '" . trim($data[product_id]) . "'";

            //debug ( "취소상태:" . $query2 );
                                      
            $result2 = mysql_query ( $query2, $connect );

            // row의 개수를 파악
            $rows  = mysql_num_rows   ( $result2 );
            $data2 = mysql_fetch_assoc( $result2 );

            ////////////////////////////////////////////////
            // 무조건 배송전의 경우...배송후는 db에 저장
            // 검색된 결과가 없을 경우
            // row의 개수가 1개일 경우 - 11번가의 경우 / hold = 4
            // row의 개수가 n개일 경우 - 부분취소
            if ( $_SESSION[LOGIN_LEVEL] == 9 )
            {
                //echo " 111: $query2 <br>----------<br>";
                
                //debug( $query2 );
            } 
            
            if ( $rows )
            {   
                if ( $data2[status] != 8 )
                {
                    $this->set_cancel( $data2[seq], $_order_id, $_product_id );
                }
                else
                {
                    $query = "insert into csinfo 
                                 set order_seq=$data2[seq]
                                     ,input_date=Now()
                                     ,input_time=Now()
                                     ,writer='" . $_SESSION[LOGIN_NAME] . "'
                                     ,cs_reason=''
                                     ,content='-[배송후] 엑셀 취소 CS처리-'";                                        
                    //debug( $query );
                    mysql_query( $query, $connect );   
                }
                
                // canceled_list 에 값을 저장함
                $this->add_cancel_history( $data2[seq],$_order_id,$data2[status] );
                $j++;
                
                // 전체 취소인지 여부 확인
                // 배송 후
                $this->check_all_cancel( $data2[seq],  $data2[status] );
            }
            
            // 2012.2.7 추가 - jkryu
            if ( $x_hold == 1 ) // 보류 걸지 않는다.
            {
                $this->release_hold( $data2[seq] );   
            }
        }
        
        //if ( $_SESSION[LOGIN_LEVEL] == 9 )
        //    exit;
                
        echo "
        <script>
            hide_waiting();
            alert( '총 " . $num_rows. "개 중 " . $j . "개가 취소 처리 되었습니다' )
        </script>
        ";

        $this->redirect("template.htm?template=E804");
    }
    
    // 
    // 보류 취소
    // 2012.2.7 추가 - jkryu
    function release_hold( $_seq )
    {
        global $connect;
        
        if ( $_seq )
        {
            $query = "update orders set hold=0 where seq=$_seq";
            debug( "release_hold: " . $query );
            mysql_query( $query, $connect );
        }
    }
    
    //========================================================
    // 취소 처리 - jk 배송전 부분 취소 무조건...
    // 2009.7.22
    function set_cancel( $seq, $order_id, $product_id )
    {
        global $connect,$x_hold;
        $query = "select count(*) cnt from order_products 
                   where order_seq       = '$seq' 
                     and shop_product_id = '$product_id' 
                     and order_cs not in (1,2,3,4)";

        //debug( "set_cancel: $query ");
                     
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        
        if ( $_SESSION[LOGIN_LEVEL] == 9 )
        {
            //echo "$query <br>----------<br>";
        }
        
        // 취소 아닌 주문이 있어야 함
        if ( $data[cnt] > 0 )
        {
            // orders 취소, hold
            $query = "update orders set order_cs=2 ";
            
            if ( $x_hold == 0 ) // 0인경우 보류 건다.
                $query .= ", hold=5 ";
                
            $query .= " where seq=$seq";
            
            
            mysql_query( $query, $connect );
            //debug( "set_cancel2: $query ");
            
            // order_products 취소
            $query = "update order_products set order_cs=2, cancel_date=Now()
                       where order_seq=$seq and shop_product_id='$product_id'";
            //debug( "set_cancel3: $query ");                       
            mysql_query( $query, $connect );
            
            if ( $_SESSION[LOGIN_LEVEL] == 9 )
            {
                //echo "$query <br>----------<br>";
            }
            
            // cs이력 저장
            $query = "insert into csinfo set order_seq=$seq, input_date=Now(), input_time=Now(), writer='" . $_SESSION[LOGIN_NAME] . "'
                                             ,content='엑셀 취소 처리'";                                        
            mysql_query( $query, $connect );
            //echo "$query <br>----------<br>";
            
            // 취소 결과 insert
            
        }
        
        // shop_csinfo에서 삭제
        $query = "delete from shop_csinfo where order_id='$order_id' and product_id='$product_id'";
        mysql_query( $query, $connect );
        
    }
    
    
    // 전체 자료가 취소인지 확인
    function check_all_cancel( $seq, $status="" )
    {
        global $connect,$x_hold;
        
        $query = "select order_cs from order_products where order_seq=$seq";
        
        $result = mysql_query( $query, $connect );
        $all_cancel = 1;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[order_cs] < 1 or $data[order_cs] > 4 )
                $all_cancel = 0;
        }
        
        if ( $all_cancel )
        {
            // 전체 취소 처리
            $order_cs = 1;
            
            if ( $status == 8 )
            {
                $order_cs = 3;  // 배송후 전체 취소   
            }
            
            $query = "update orders set order_cs=$order_cs";
            if ( $x_hold == 0 )     // x_hold가 0인경우 보류 건다.
                $query.= " , hold=4 ";
                
            $query .= " where seq=$seq";   
            
            mysql_query( $query, $connect );
            
            $query = "update order_products set order_cs=$order_cs where order_seq=$seq";   
            mysql_query( $query, $connect );
        }
    }
    
    //========================================================
    //
    // 취소 이력 저장
    function add_cancel_history( $seq,$order_id,$status )
    {
        global $connect;
        
        // auto_canceled_order 저장.
        $query = "insert into auto_canceled_order set seq=$seq, order_id='$order_id', cancel_date=Now(), status=$status";
        mysql_query( $query, $connect );
    }

    ////////////////////////////////////////////////////
    //
    // 실제 취소 처리를 하는 부분
    // date: 2006.5.2 - jk.ryu
    //
    function cancel_apply_action( $data , $cs_msg = '')
    {
        global $connect;
        global $order_seq, $cs_type, $cs_reason, $content;
       
        $cs_type   = "취소요청";
        $cs_reason = "자동";

        if ( $cs_msg )
            $content = $cs_msg;
        else
            $content   = addslashes ( "-판매처 취소 관리 CS처리-\n" . $data[cancel_why] . "\n" . $data[memo] );

if ( $_SESSION[LOGIN_LEVEL] == 9 )
{
        echo "[root only] 취소 요청 / $data[seq] / $data[status] / $data[order_cs] / $content<br>";
} 

        $order_seq = $data[seq];
        if ( $data[status] < 8 ) // 배송 전
        {
            // 배송 보류 작업 한 번 더 함
            if ( $x_hold == 0 )     // 보류 걸도록 셋팅...
            {
                if ( !$data[hold] )
                    class_E900::set_hold2( $order_seq, 5);
            }
            
            switch ( $data[order_cs ] )
            {
                case "0":  // 정상
                case "11": // 교환
                    // class_E900::csinsert(0);
                    class_E900::csinsert2( $order_seq, $content);
                    class_E900::cancel_action( $order_seq );
                    return true;
                    break;
            }

        }
        else  // 배송 후
        {
            switch ( $data[order_cs ] )
            {
                case "0":  // 정상
                case "5":  // 교환
                case "7":
                case "13":
                        class_E900::csinsert2( $order_seq, $content);
                    class_E900::cancel_action( $order_seq, 2 ); // 배송 후 취소 요청
                    return true;
                    break;
                case "11": // 교환
                    // echo " $order_seq / 배송 후 취소<br>";
                        class_E900::csinsert2( $order_seq, $content);
                    class_E900::cancel_action( $order_seq );
                    return true;
                    break;
            }
        }

        return false;
    }

    ////////////////////////////////////////////////////
    // 
    // message 출력
    //
    function disp_message( $str )
    {
        echo "<script language=javascript> 
                  show_waiting() 
                  show_txt ( '$str' );
               </script>";
        flush();
    }
    //////////////////////////////////////////////////
    // cs 정보의 최종 갱신일을 보여 준다
    // jk.ryu : 2006.5.1
    function get_lastinfodate( $shop_id )
    {
        global $connect;
        $query = "select collect_date from shop_csinfo order by collect_date desc limit 1";
        $result = mysql_query ( $query, $connect);
        $data = mysql_fetch_array ( $result );
        return $data[collect_date];
    }


    ///////////////////////////////////////////////////
    // cs list 품절 주문 리스트 
    // date : 2005.9.26
    function cs_list( &$total_rows, &$result )
    {
       global $connect; 
       global $start_date;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = $_REQUEST[keyword];
       $end_date = $_REQUEST[end_date];
       $order_cs = $_REQUEST[order_cs];
       $shop_id = $_REQUEST[shop_id];
       $supply_id = $_REQUEST[supply_id];
       $page = $_REQUEST[page];
       $act = $_REQUEST[act];
       $line_per_page = 100;

  //////////////////////////////////////////////
  // 검색
  if ( !$page )
     $page = 1;
  $starter = ($page-1) * $line_per_page;

  $options = "";

  /* 
  // 검색키워드
  if ($keyword)
  {
    if ($search_type == 1) // 주문자
        $options .= "and a.order_name = '${keyword}'" ;
    else if ($search_type == 2) // 주문번호
        $options .= "and a.order_id = '${keyword}'" ;
    else if ($search_type == 3) // 상품명
        $options .= "and a.product_name like '%${keyword}%' " ;
    else if ($search_type == 4) // 전화번호
        $options .= "and (a.recv_tel = '$keyword' or a.recv_mobile = '$keyword') " ;
  }

  // 주문상태
  if ($order_cs != '')
    $options .= "and a.order_cs = '${order_cs}'" ;

  // 판매처
  if ($shop_id != '')
    $options .= "and a.shop_id = '${shop_id}'" ;

  // 공급처
  if ($supply_id != '')
    $options .= "and a.supply_id = '${supply_id}'" ;

  */

  /////////////////////////////////////////////////////
  $sql = "select * from shop_csinfo ";
  $count_sql = "select count(*) cnt from shop_csinfo ";

  $limit_clause = " order by collect_date desc limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;
  $result = mysql_query($query, $connect) or die(mysql_error());
  $data = mysql_fetch_array ( $result );
  $total_rows = $data[cnt];

// echo $sql . $where_clause . $limit_clause;

  $query = $sql.$where_clause.$limit_clause;
  //debug( $query );
  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

  function get_shop_name( $shop_id )
  {
     return class_C::get_shop_name ( $shop_id );
  }

  function get_cs_link( $order_id )
  {
      
  } 
}
  ///////////////////////////////////////////
  // auction 처리
  class auction
  {
      function auction( $shop_id , $shop_name )
      {
?>
          <tr bgcolor=ffffff>
              <td width=70 align=center><img src=images/10001.gif></td>
              <td width=70><?= $shop_name ?></td>
              <td><?=  class_E800::get_lastinfodate( $shop_id ) ?></td>
              <td width=80 align=center>
                 <a href='javascript:auction("<?= $shop_id ?>")'><img src=http://www.ezadmin.co.kr/shopadmin/images/btn_auto_on.gif align=absmiddle></a>
              </td>
              <td width=80 align=center><img src=http://www.ezadmin.co.kr/shopadmin/images/btn_hand.gif></td>
              <td width=50 align=center>
                  <a href='javascript:del_cs_info("<?= $shop_id ?>")'>삭제</a>
              </td>
          </tr>
<?
      }
  }

  //////////////////////////////////////////
  // gmarket 처리
  class gmarket 
  {
      function gmarket( $shop_id, $shop_name )
      {
?>
          <tr bgcolor=ffffff>
              <td width=70 align=center><img src=images/10002.gif></td>
              <td width=70><?= $shop_name ?></td>
              <td>최종 정보</td>
              <td width=80 align=center><img src=images/btn_auto.gif></td>
              <td width=80 align=center>
                  <a href='javascript:gmarket("<?= $shop_id ?>")')><img src=images/btn_hand_on.gif align=absmiddle></a>
              </td>
              <td width=50 align=center>
                  <a href='javascript:del_cs_info("<?= $shop_id ?>")'>삭제</a></td>
          </tr>

<?
      }
  }
?>
