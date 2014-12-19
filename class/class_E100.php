<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_takeback.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_E100
//
class class_E100 extends class_top {

    ///////////////////////////////////////////

    function E100()
    {
        global $connect;
        global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type, $order_status;


        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 day'));
        $end_date = $_REQUEST["end_date"];

        if ( $page )
        {
           echo "<script>show_waiting();</script>";
           $this->cs_list( &$total_rows, &$r_cs );
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        if ( $page )
           echo "<script>hide_waiting();</script>";
    }

    //===================================================
    // 회수 송장 입력 
    // date: 2007.2.28 - jk.ryu
    function E116()
    {
        global $connect;
        global $template, $link_url, $top_url, $seq;

        $data = class_takeback::get_detail( $seq );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //===================================================
    // 회수 송장 입력 
    // date: 2007.2.28 - jk.ryu
    function modify_takeback_transinfo()
    {
        global $seq, $trans_no, $trans_corp, $link_url, $top_url;

        class_takeback::update_transinfo($seq, $trans_no, $trans_corp);

        $this->opener_redirect( "template.htm" . base64_decode( $link_url ) . "top_url=$top_url");
        $this->closewin();
        exit;
    } 

    function E101()
    {
        global $connect;
        global $template, $link_url, $top_url, $seq;

        $link_url = "?" . $this->build_link_url();
        $link_url = base64_encode($link_url);

        $list = $this->get_detail();
        $takeback_data = class_takeback::get_detail( $seq );

        $content = "==반품정보==\n반품택배사:\n반품송장번호:\n";
        $content .= "환불계좌:\n환불은행:\n예금주:\n ";

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E102()
    {
        global $connect;
        global $template;

        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E112()
    {
        global $connect;
        global $template, $link_url, $top_url, $seq, $status, $order_cs;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    /////////////////////////////////////////////////////
    // 배송확인
    // date: 2006.6.5 - jk
    function trans_confirm()
    {
        global $connect, $top_url, $seq, $status, $order_cs, $link_url;
        
        $transaction = $this->begin("배송확인");

        $query = "update orders set status='8'";
        $query .= " ,trans_date_pos = Now() ";
        $query .= " where seq='$seq'";

        mysql_query ( $query, $connect );

        $this->opener_redirect( "template.htm" . base64_decode( $link_url ) . "top_url=$top_url");
        $this->closewin();

        $this->end( $transaction );
    }
    /////////////////////////////////////////////////////
    // 주문의 상태 변경
    // date: 2006.1.18 - jk
    function change_status()
    {
        global $connect, $top_url, $seq, $status, $order_cs, $link_url;
        
        $transaction = $this->begin("주문 상태 변경 $status");

        $query = "update orders set status='$status', order_cs='$order_cs' ";

        // if ( $status == 8 )
        //    $query .= " ,trans_date_pos = Now() ";

        $query .= " where seq='$seq'";
        mysql_query ( $query, $connect );

        $this->opener_redirect( "template.htm" . base64_decode( $link_url ) . "top_url=$top_url");
        $this->closewin();

        $this->end( $transaction );
    }

    function E103()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E104()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E105()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E106()
    {
        global $connect;
        global $template;
 
        // cs_type=취소
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E107()
    {
        global $connect;
        global $template;
 
        // cs_type=취소
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );

        $content = "==반품정보==\n반품택배사:\n반품송장번호:\n";
        $content .= "환불계좌:\n환불은행:\n예금주:\n ";
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E108()
    {
        global $connect;
        global $template;
 
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E109()
    {
        global $connect;
        global $template;
 
        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E110()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E113()
    {
        global $template, $name, $mobile;
        $connect = sys_db_connect();
        $mobile = str_replace("-", "", $mobile);

        //$query = "select sum(point) s from sys_user_memo where mobile='$mobile' and name='$name' order by crdate desc"; 
        $query = "select sum(point) s from sys_user_memo where mobile='$mobile' order by crdate desc"; 
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        $s = $data[s];

        $link_url = $this->build_link_url();
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////
    // date: 2006.4.17 - jk
    // 고객 성향 출력
    function E114()
    {
        global $template, $name, $mobile;
        $connect = sys_db_connect();
        $mobile = str_replace("-", "", $mobile);

        // $query = "select * from sys_user_memo where mobile='$mobile' and name='$name' order by crdate desc"; 
        $query = "select * from sys_user_memo where mobile='$mobile' order by crdate desc"; 
// echo $query;
        $result = mysql_query ( $query, $connect );
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////
    // date: 2006.4.17 - jk
    function add_memo()
    {
        global $template, $name, $mobile, $point, $link_url, $memo, $domain;
        $connect = sys_db_connect();
        $s_mobile = str_replace("-", "", $mobile);
        $query = "insert into sys_user_memo set mobile='$s_mobile', name='$name', point='$point', memo='". addslashes($memo) . "', domain='" . _DOMAIN_ . "'";
        mysql_query ( $query, $connect );

        $this->redirect( "popup.htm?" . $link_url );
        exit;
    }

    function E115()
    {
        global $connect;
        global $template, $name, $mobile;
 
        // $query = "select * from orders where recv_mobile='$mobile' and recv_name='$name'"; 
//and REPLACE(recv_address,'-', '') = '$recv_address'
        $query = "select * from orders where REPLACE( recv_mobile, '-', '' )='$mobile'"; 
        $result = mysql_query ( $query, $connect );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E111()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E117()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E118()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E119()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E120()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E121()
    {
        global $connect;
        global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function del()
    {
        global $connect;
        global $template, $seq, $link_url, $top_url;

        $transaction = $this->begin("주문 삭제");

        // 기존 주문의 정보를 남김
        $query = "select * from orders where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        //debug ( "[주문 삭제] " . $_SESSION[LOGIN_NAME] . " $data[order_id] / $data[shop_id] / $data[seq] / $data[order_name] / $data[collect_date]" );

        if ( $seq )
        {
            $query = "delete from orders where seq='$seq'";
            mysql_query ( $query, $connect );
        }

        $this->end($transaction);

        $this->redirect( base64_decode( $top_url ) );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////////////
    // cs list 
    // date : 2005.9.14
    function cs_list( &$total_rows, &$result, $limit=0 )
    {
       global $connect, $trans_who, $order_status, $soldout_only;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = trim( $_REQUEST[keyword] );
       $start_date = $_REQUEST[start_date];
       $end_date = $_REQUEST[end_date];
       $order_cs = $_REQUEST[order_cs];
       $shop_id = $_REQUEST[shop_id];
       $supply_id = $_REQUEST[supply_id];
       $page = $_REQUEST[page];
       $act = $_REQUEST[act];
       $line_per_page = _line_per_page;

   //////////////////////////////////////////////
  // 검색
  $starter = $page ? ($page-1) * $line_per_page : 0;

  $options = "";

  // 상품명 검색
  // date: 2007.11.5 - jk
  if ( $search_type == 3 )
        $_productid_list = class_product::get_product_ids( $keyword );

        //======================================
        // date: 2007.11.5
        // 품절 상품 check - jk
        global $soldout_only;
        $_soldout_products = "";
        if ( $soldout_only )
        {
                $_soldout_products = class_product::get_soldout_list();
                $options .= " and a.product_id in ( $_soldout_products )";
        }

  // 검색키워드
  if ($keyword)
  {
    switch ( $search_type )
    {
        case 1: // 주문자
            if ( strlen($keyword) == 6 )
                    $options .= "and a.order_name = '${keyword}'" ;
            else
                    $options .= "and a.order_name like '%${keyword}%'" ;
            break;
        case 2: // 주문번호
            $options .= "and a.order_id = '${keyword}'" ;
            break;
        case 3: // 상품명
            // $options .= "and a.product_name like '%${keyword}%' " ;
            $options .= " and a.product_id in ( $_productid_list ) ";
            break;
        case 4: // 전화번호
            if ( _DOMAIN_ == "yangpa" 
                || _DOMAIN_ == "mam8872" 
                || _DOMAIN_ == "soocargo" 
            )
            {
                    if ( strlen ( $keyword ) == 4 )
                    {
                        $options .= "and a.short_mobile = '$keyword' " ;
                    }
                    else
                    {
                        $options .= "and a.recv_mobile = '$keyword' " ;
                    }
            }
            else
                    $options .= "and a.recv_mobile = '$keyword' " ;
                
            break;
        case 41:
            $options .= "and a.recv_tel = '$keyword' " ;
            break;
        case 5: // 수령자

            if ( strlen($keyword) == 6 )
                    $options .= "and (a.recv_name = '$keyword') " ;
            else
                    $options .= "and (a.recv_name like '%$keyword%') " ;

            break;
        case 6:  // 송장번호
            $options .= "and a.trans_no = '$keyword' "; 
            break;
        case 7: // 어드민 코드
            $options .= "and a.seq = '$keyword' ";
            break;
        case 8: // 주문자 전화
            $options .= "and a.order_mobile ='$keyword' " ;
            break;
        case 81:
            $options .= "and a.order_tel = '$keyword' " ;
            break;
        case 9: // 상품 코드 
            $options .= "and a.product_id = '$keyword' " ;
            break;
        case 10: // 옥션 코드 
            $options .= "and a.code1 = '$keyword' " ;
            break;
        case 11: // 인터파크 아이디 
            $options .= "and a.code10 = '$keyword' " ;
            break;
    }
  }
//echo $options;

  //------------------------------------------------
  //
  // CS 상태
  // 
  if ($order_cs != '')
  {
    switch ( $order_cs )
    {
        case 1:  // 정상
           $options .= "and a.order_cs in( 0,9,10 ) ";
        break;
        case 2: // 취소
           $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
        break;
        case 3: // 교환 
           $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
        break;
    }
  }

  //------------------------------------------------
  //
  // 주문 상태
  //
  if ( $order_status )
    $options .= "and a.status = '$order_status'";

  // session level이 0이면 업체임
  if ( !$_SESSION["LOGIN_LEVEL"] )
      $options .= " and supply_id = '" . $_SESSION["LOGIN_CODE"] . "'";

  // 판매처
  if ($shop_id != '')
    $options .= " and a.shop_id = '${shop_id}' " ;

  //선불 착불 
  if ( $trans_who )
    $options .= " and a.trans_who = '${trans_who}' " ;

  // 공급처
  if ($supply_id != '')
    $options .= " and a.supply_id = '${supply_id}' " ;


  /////////////////////////////////////////////////////
  $sql = "select a.* 
            from orders a";
  $count_sql = "select count(*) cnt from orders a ";

  $where_clause = "
           where a.collect_date >= '$start_date'
             and a.collect_date <= '$end_date'
                 ${options}
           order by a.seq desc";

  //////////////////////////////////////////
  // 전체를 다 찍고 싶어도 최대값은 1000개
  if ( $_SESSION[LOGIN_LEVEL] != 9 )
  {
      if ( $limit )
          $line_per_page = 3000;
  }
  else
  {
      if ( $limit )
          $line_per_page = 30000;
  }

  $limit_clause = " limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;
  $result_cnt = mysql_query($count_sql.$where_clause, $connect) or die(mysql_error());

  $list = mysql_fetch_array($result_cnt);
  $total_rows = $list[cnt];

  $query = $sql.$where_clause.$limit_clause;
  //debug($query);

  // if ( _DOMAIN_ == "mangosteen" && $_SESSION[LOGIN_LEVEL] == 9 )
  //        echo $sql.$where_clause.$limit_clause;

  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

    ///////////////////////////////////////////////
    // cs의 존재 여부 check
    // date: 2005.12.2
    function cs_exist( $seq )
    {
        global $connect;

        $string = "";
        $query = "select * from csinfo where order_seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $cnt = 0;

        while ( $data = mysql_fetch_array ( $result ) )
        {
            $cnt++;
            $string .= "--------------------------------------------<br>
                        $data[input_date] $data[input_time] - $data[writer]<br>
                        $data[content]<br>";
        }

        if ( $cnt )
            echo "<img src=images/G_cs.gif align=absmiddle>";
        else
            echo "<img src=images/P_cs.gif align=absmiddle>";

        return $string;
    }

    ///////////////////////////////////////////////
    // 회수 완료 
    // date: 2007.10.23 jk
    function takeback_complete()
    {
        global $seq,$link_url,$top_url;

        $transaction = $this->begin("회수완료");
        class_takeback::complete_item( $seq );
        $this->end( $transaction );

        $link_url = base64_decode( $link_url );
        $this->redirect( $link_url . "&top_url=" . $top_url );
    }

    ///////////////////////////////////////////////
    // 교환
    // date: 2005.9.21 jk
    function change_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty, $top_url;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("교환");

       ////////////////////////////////////////////////
       // 부분 취소 로직을 입력함
       if ( $qty != $org_qty )
           $this->part_cancel_action( "배송후" );
       else
       {
           // 주문의 상태 변경
           $this->change_action();

           // 상태...
           $this->change_cs_result(0); 
       }

       // 주문 생성
       // order_id
       $order_id = "C" . $order_id;

       if ( $order_cs == _exchange )
          $exchange_option = 1;
       else
          $exchange_option = 0;

// echo "create--";
       $this->create_order( $order_id, &$seq, $exchange_option );

       // cs를 남겨야 함
       $this->csinsert(0);



       $this->redirect( $link_url . "&top_url=" . $top_url );

       $this->end( $transaction );
       exit;
    }

    /////////////////////////////////
    // 배송전 교환 요청
    function modify_csinsert()
    {
       global $top_url, $link_url, $order_id, $order_cs, $qty, $org_qty;
       global $seq, $connect;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("변경 - 교환");

       $this->set_hold2( $seq, 4 );

       ////////////////////////////////////////////////
       // 부분 취소 로직을 입력함
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "배송 전" );

           ///////////////////////////////////////
           // 주문 생성 order_id
           $order_id = "C" . $order_id;
    
           if ( $order_cs == _exchange )
              $exchange_option = 1;
           else
              $exchange_option = 0;

           $this->create_order( $order_id, &$seq, $exchange_option );

           ////////////////////////////////////////////////////////////////////
           // 새로운 주문이 생성 되기 때문에 part cancel의 상태를 완료로 변경
           $query = "update part_cancel set status='처리완료' where seq='$seq'";
           mysql_query( $query, $connect );
       }
       else
       {
           $query = "update orders set order_cs = " . _change_req_b . " where seq='$seq'";       
           mysql_query ( $query, $connect );

           // 주문의 내용 변경 
           $this->order_update(0);
       }

       // cs를 남겨야 함
       $this->csinsert(0);

       // opener redirect
       // $this->opener_redirect( $link_url );

       $this->end( $transaction );

       $this->redirect( $link_url . "&top_url=" . $top_url );

       // 완료 페이지
       //$this->redirect( "?template=E109&seq=$seq");
       exit;
    }

    //==========================================
    //
    // 회수 요청
    // date: 2007.2.28 - jk.ryu
    //
    function req_takeback()
    {
        global $top_url, $link_url;

        $transaction = $this->begin("회수 요청");

        $str_takeback = class_takeback::regist_takeback(); 

        $this->jsAlert( $str_takeback );
        $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );
    }

    ////////////////////////////////////////////////
    // 반품 
    function refund_csinsert()
    {
       global $link_url, $qty, $org_qty;

       $transaction = $this->begin("반품");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // 부분 취소 확인
       if ( $qty != $org_qty )
           $this->part_cancel_action( "배송후" );
       else
           $this->cancel_action("", _cancel_req_a ); // 배송후 취소 요청


       // 회수 요청의 경우 처리 부분 추가
       // date: 2006.11.15 - jk.ryu
       global $chk_takeback;

       // 회수 요청을 체크 한 경우만 처리 됨
       $str_takeback = '';
       if ( $chk_takeback )
           $str_takeback = class_takeback::regist_takeback(); 

       $this->jsAlert( " $str_takeback 취소 되었습니다 ");

       global $top_url;
       $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );
       $this->end( $transaction );
       exit;
    }

    function takeback_request()
    {

    }

    ////////////////////////////////////////////////
    // 취소 
    // 부분 취소 로직을 넣음
    function cancel_csinsert()
    {
       global $link_url;
       global $qty, $org_qty;

       $transaction = $this->begin("취소");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // 부분 취소 확인
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "배송 전" );
       }
       else
          $this->cancel_action(); 

       //$this->set_small_window(" 취소 되었습니다 ");
       //$this->opener_redirect ( base64_decode($link_url) );

       $this->jsAlert( " 취소 되었습니다 ");

       global $top_url; 
       $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );

       $this->end ( $transaction );

       exit;
    }

    ////////////////////////////////////////
    //
    function csinsert( $link_option = "1" )
    {
        global $connect;
        global $template, $order_seq, $cs_type, $cs_reason, $cs_result, $content, $trans_who, $trans_fee;

        $transaction = $this->begin( "일반상담" );

        $order_seq = $_REQUEST[order_seq] ? $_REQUEST[order_seq] : $order_seq;
        $cs_type = $_REQUEST[cs_type] ? $_REQUEST[cs_type] : $cs_type;
        $writer = $_SESSION[LOGIN_NAME];
        $cs_reason = $_REQUEST[cs_reason] ? $_REQUEST[cs_reason] : $cs_reason;
        $cs_result = $_REQUEST[cs_result] ? $_REQUEST[cs_result] : $cs_result;
        $content = $_REQUEST[content] ? $_REQUEST[content] : $content;
        $content = addslashes($content);

        $link_url = "?template=$template&seq=$order_seq";

        $sql = "insert into csinfo set 
                  order_seq = '$order_seq',
                  input_date = now(),
                  input_time = now(),
                  writer = '$writer',
                  cs_type = '$cs_type',
                  cs_reason = '$cs_reason',
                  cs_result = '0',
                  content = '$content',
                  trans_who='$trans_who',
                  trans_fee='$trans_fee'
        ";

//echo $sql;
//exit;
         mysql_query($sql, $connect) or die(mysql_error());
        /////////////////////////////////////////////////
        // 취소 요청일 경우 order의 order_cs의 상태를 취소로 변경해야 함
        /*
        switch ( $cs_type )
        {
           case 1: // 취소
              $this->cancel_action( $order_seq );   
           break;
           case 2:  
           break;
           case 3: 
           break;
        }
        */

        $this->end( $transaction );

        global $top_url;
        if ( $link_option )
           $this->redirect(  $link_url  . "&top_url=" . $top_url );
          // $this->redirect ( $link_url );
    }


    ///////////////////////////////////
    // 취소 주문 가능한 주문인지 여부 확인
    function enable_cancel( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // 정상이 아니면 취소 금지
       if ( $order_cs )
          echo "disabled";
    }

    function enable_change( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // 정상이 아니면 취소 금지
       if ( $order_cs )
          echo "disabled";
    }

    ////////////////////////////////////////////////
    // 정상 전환
    function set_normal ( $order_cs='0' )
    {
       global $connect, $seq, $link_url;

       $transaction = $this->begin("주문복원");

       $query = "select order_id, status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       switch ( $data[order_cs] )
       {
           case _cancel_com_b:
           case _cancel_com_a:
              $this->end( $transaction, "주문복원오류" );
              $this->jsAlert("이미 취소 되었습니다"); 
              $this->back();
              exit;
           break;
           case _change:
           case _change_reg_a:
           case _change_com_a:
              $this->end( $transaction, "주문복원오류" );
              $this->jsAlert("교환요청 주문입니다"); 
              $this->back();
              exit;
           break;
           case _exchange:
           case _exchange_com:
              $this->end( $transaction, "주문복원오류" );
              $this->jsAlert("맞교환요청 주문입니다"); 
              $this->back();
              exit;
           break;
           case _change_req_b:  // 배송전 교환 요청=>주문의 상태가 확인 이하
              if ( $data[status] >= _trans_confirm && $data[status] != 11 )
              {
                 $this->end( $transaction, "주문복원오류" );
                 $this->jsAlert("11 이미 배송 단계 입니다."); 
                 $this->back();
                 exit;
              }
           break;
           case _change_req_a:  // 배송후 교환 요청=>교환 발주의 상태가 확인 이하
              $query = "select status from orders where order_id = 'C" . $data[order_id] . "'";
              $result = mysql_query ( $query, $connect );
              $data = mysql_fetch_array ( $result );

              if ( $data[status] >= _order_confirm && $data[status] != 11)
              {
                 $this->end( $transaction, "주문복원오류" );
                 $this->jsAlert(" 22 이미 배송 단계 입니다."); 
                 $this->back();
                 exit;
              }
           break;
       } 

       $query = "update orders set order_cs=0 where seq='$seq'";
       mysql_query ( $query , $connect );
       $link_url = urldecode( $link_url );
       $this->end( $transaction );

       // 회수 관련 부분
       class_takeback::delete_takeback();

       //$this->redirect( $link_url );
       global $top_url;
       $this->redirect( base64_decode( $link_url ). "&top_url=" . $top_url );
       exit;
    }

    /////////////////////////////////////////////////
    // 교환 발주 생성
    // date: 2005.9.21 jk 
    function order_change()
    {
       global $connect, $seq;

       ////////////////////////////////////
       // 정보를 가져온다.
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       //////////////////////////////////
       // 1. 취소인지 check
       switch ( $data[order_cs] )
       {
          case _cancel_req_b:
          case _cancel_req_a:
             $this->set_small_window ("주문을 정상으로 돌린 후 처리 하세요");
             exit;
          break;
          case _cancel_com_b:
          case _cancel_com_a:
             $this->set_small_window ("취소 완료된 주문은 교환이 불가능합니다");
             exit;
          break;
          case _change_req_b:
          case _change_req_a:
             $this->set_small_window ("이미 교환된 주문 입니다");
             exit;
          break;
          case _change_com_b:
          case _change_com_a:
             $this->set_small_window ("교환 발주된 주문에서 처리 해야 합니다");
             exit;
          break;
       }

       //////////////////////////////////
       // 2. 정상
       // 2.1 배송 전
       if ( $data[status] < _order_confirm )
       {
          $this->opener_redirect ( "template.htm?template=E102&seq=$seq" );
          $this->closewin();
          exit;
       }
       // 2.2 배송 후
       else if ( $data[status] >= _trans_no || $data[status] == _order_confirm )
       {
          $this->redirect("?template=E108&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }

       // 3.3 처리 불가
       else
       {
          $this->set_small_window("처리 불가 상태 입니다");
          exit;
       }
    }

    /////////////////////////////////////////////////
    // 원 주문의 상태를 1: 취소로 변경한다
    // Date: 2005.09.20 jk
    function order_cancel()
    {
       global $connect, $seq, $cancel_cs_reason;

       $cs_reason = $cancel_cs_reason;  // cancel_cs_reason
       $cs_type = 1;                    // 취소

       $transaction = $this->begin( "취소" );

       ///////////////////////////////////////////////////
       // 상태 정보가져 온다
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       /////////////////////////////////////////////////
       // step 1. order_cs 확인
       // CS가 완료된 경우 이전 CS내용을 확인해야 함
       // 1. 이미 취소 완료된 주문의 재 취소 불가
       // 2. 교환 주문이 생성된 원 주문은  취소 불가 
       switch ( $data[order_cs] )        
       {
           // 불가 case 들
           case _cancel_req_b: // 배송전 취소 요청
           case _cancel_req_a: // 배송후 취소 요청
//             $this->set_small_window ( "이미 취소 요청이 되어 있음" );
             $this->jsAlert( "이미 취소 요청이 되어 있음" );
             $this->back();
             exit;
           break;
           case _cancel_com_b:
           case _cancel_com_a:
             //$this->set_small_window ( "취소 완료 되었습니다" );
             $this->jsAlert( "취소 완료 되었습니다" );
             $this->back();
             exit;
           break;
           case _change_req_a:
           case _change_com_b:
           case _change_com_a:
           case _exchange_com:
             //$this->set_small_window ( "교환 발주된 주문에서 처리 해야 합니다" );
             $this->jsAlert( "교환 발주된 주문에서 처리 해야 합니다" );
             $this->back();
             exit;
           break;

       }

       //////////////////////////////////////////
       // step 2. 배송 전 취소 주문 확인 전 / 후 check
       // 주문 다운받은 후 배송 확인을 함  
       // 2.1. 확인 전
       if ( $data[status] < _order_confirm )
       {
          $link_url = base64_decode( $_REQUEST["link_url"] );
          $this->cancel_action( $seq );  
          $this->set_small_window ( "취소 완료");
          $this->closewin();
          $this->opener_redirect( $link_url );
          exit;
       }
       ////////////////////////////////////////
       // 2.2. 확인 후 
       // 배송 확인 후 취소 여부를 결정 후 취소 해야 함 -> 취소 관련 CS를 남김
       // 취소 불가일 경우는 반품임 
       else if ( $data[status] >= _trans_no ) 
       {
          /////////////////////////////////////////////////////
          // 송장 번호 입력 후 => 무조건 반품
          $this->redirect("?template=E107&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }    
       else
       {
          ///////////////////////////////////////////////////
          // 배송 여부를 확인해야 하는 상태
          $this->redirect("?template=E106&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }
    }

    //////////////////////////////////////////////////////
    // 상태 변경
    function change_cs_result( $redirect_option = 1)
    {
       global $order_seq, $connect, $link_url, $cs_result;

       $transaction = $this->begin( "처리상태변경" );

       $query = "select status, order_cs from orders where seq='$order_seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );
      
       //////////////////////////////////////////////////////////////
       // To 처리 완료
       // To 처리중은 진행하지 않는다 => 데이터복구는 불가능

       if ( $cs_result )
       {
          switch ( $data[order_cs] )
          {
             // 배송전 취소 요청
             case 12:        // 배송전 취소 확인
             case _cancel_req_b: 
                $query = "update orders set order_cs = '" . _cancel_com_b ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // 배송후 취소 요청
             case _cancel_req_a: 
                $query = "update orders set order_cs = '" . _cancel_com_a ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // 배송전 교환 요청
             case _change_req_b: 
                $query = "update orders set order_cs = '" . _change_com_b ."' where seq='$order_seq'";
                mysql_query ( $query );
             break;
          }
       }

       if ( $cs_result == 0 )
          $this->end( $transaction, "CS진행중설정"); 
       else
          $this->end( $transaction, "CS완료설정"); 

       $query = "update csinfo set cs_result='$cs_result' where order_seq='$order_seq'";
       mysql_query ( $query, $connect );

//echo "url->" . urldecode($link_url);

       $this->end( $transaction );

       if ( $redirect_option )
       {
          global $top_url;
          $this->redirect( base64_decode( $link_url ) . "&top_url=" . $top_url );
          exit;
       }
    }

    ///////////////////////////////////////////////////////
    // 부분 취소 실행
    // date: 2005.11.2
    function part_cancel_action( $order_status )
    {
       global $connect, $seq, $qty, $org_qty, $options, $memo;
       
       // 주문의 개수를 변경함 
       // 원 주문의 개수는 변경하지 않는다 - 2005.12.5 jk
       /*
       $change_qty = $org_qty - $qty;
       $query = "update orders set qty='$change_qty', options='" . addslashes($options) . "', memo='" . addslashes($memo) ."' where seq='$seq'";
//echo $query;
//exit;
       mysql_query ( $query, $connect );
       */

       // part_cancel에 값을 넣는다.
       $query = "insert part_cancel set order_seq='$seq', cancel_req_date=Now(), order_status='$order_status', status='미처리', qty='$qty'";
       mysql_query ( $query, $connect );
    }

    ///////////////////////////////////////////////////////
    // 취소 실행
    function cancel_action( $seq="" , $status = _cancel_req_b )
    {
       global $connect, $link_url;

       if ( $_REQUEST["seq"] )
         $seq = $_REQUEST["seq"]; 

       class_E100::set_hold2( $seq, 5);

       // org pack을 구함
       $query = "select pack from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       $org_pack = $data[pack];

        // 황대리 요청 사항 2008.1.10 - jk
        // 취소시 모두 보류를 걸기로 함
        if ( _DOMAIN_ == "pinkhoute" ) 
           $query = "update orders set order_cs=$status, refund_cs_date=Now(), pack=NULL, hold=1 where seq='$seq'";
        else
           $query = "update orders set order_cs=$status, refund_cs_date=Now(), pack=NULL where seq='$seq'";

       mysql_query ( $query, $connect );

        debug ( "[cancel_action] $query " );

       //////////////////////////////////////////////////////
       // 합포가 1개인지 여부를 파악해서 1개일 경우 삭제 함
       $query = "select count(*) cnt from orders where pack = '$org_pack'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array( $result );
 
       if ( $data[cnt] == 1 )
       {
         $query = "update orders set pack=NULL where pack='$org_pack'";
         $result = mysql_query ( $query, $connect );
       }       
    }

    //////////////////////////////////////////////////////
    //
    // 배송 보류 설정
    // 2008.5.20 - 사용하는 hold function 
    //   hold 추가
    //   4: 취소보류
    //   5: 교환보류
    function set_hold2( $seq , $hold='' )
    {
        // nak는 보류 걸지 않는다
        if ( _DOMAIN_ != "nak21" 
          && _DOMAIN_ != "yeowoowa")
        {
            global $connect;

            if ( !$seq )
                global $seq;

            // 정보 검색
            $query  = "select * From orders where seq=$seq";
            $result = mysql_query( $query, $connect ) or die( mysql_error() );
            $data   = mysql_fetch_array( $result );

            // 3pl업체가 아닐 경우 접수건은 보류 걸지 않는다   
            // date: 2008.6.5 - jk - 황대리 요청
            if ( $data[status] == 1 )
                return;        
 
            $transaction = $this->begin("배송보류설정[$hold] ");
            $query = "update orders 
                         set hold=$hold
                       where hold < $hold
                         and seq=$seq and status<>8";
        //debug ( "[set hold2] $query" );
            mysql_query ( $query, $connect ) or die( mysql_error() );
    
            // 합포 값이 있는 경우 보류 설정
            if ( $data[pack] )
            {
                $query = "update orders 
                             set hold=$hold
                           where hold < $hold
                             and pack=$data[pack] and status<>8";
        //debug ( "[set hold2] $query" );
                mysql_query ( $query, $connect ) or die( mysql_error() );
            }

        //debug ( "[set hold2] $query" );

        }
    }  
   

    /////////////////////////////////////////////////////
    // 교환 실행
    function change_action( $seq="" )
    {
       global $connect, $order_cs, $status;
       if ( $_REQUEST["seq"] )
         $seq = $_REQUEST["seq"]; 

       $query = "update orders set order_cs=" . $order_cs. " where seq='$seq'";
       mysql_query ( $query, $connect );
    }



    ///////////////////////////////////////////////////////
    // window의 size를 작게 만들어 준다.
    function set_small_window( $text )
    {
       //echo "<img src='images/can_link.gif' name=img_main> " . $text;
?>
<style type="text/css">
<!--
.text {
        font-family: "굴림", "돋움", Seoul, "한강체";
        font-size: 12px;
        font-style: normal;
        font-weight: bold;
        color: #FF3300;
}
-->
</style>

<table border="0" cellpadding="0" cellspacing="0" width=100%>
  <tr>
    <td width="142" align="right"><img src="images/img.gif" name=img_main></td>
        <td width="258"><span class=text><?= $text ?></span></td>
  </tr>
  <tr>
        <td colspan="2" align="center">
        <a href="javascript:self.close()"><img src="images/done_btn.gif" border="0"></a>&nbsp;&nbsp;
        <a href="javascript:self.close()"><img src="images/close_btn.gif" border="0"></a></td>
  </tr>
</table>

<script language=javascript>
   tid=setTimeout( resize ,200);

   function resize()
   {
      if ( document.images["img_main"].complete )
      {
         window.resizeTo ( 470, 240 )
         setTimeout ( auto_close, 4000 )
      }
      else
         tid=setTimeout( resize ,200);
   }

   function auto_close()
   {
       self.close();
   }

</script>
<?
    }

    ///////////////////////////////////////////////////
    // 
    function disp_btn ( $code )
    {
        switch ( $code )
        {
           case _cancel_req_b: // 정상 주문으로 변경 가능
           case _cancel_req_a: 
           case _change_req_b:
           case _change_reg_a:
           case _change_com_b:
              echo "&nbsp;&nbsp;<br> <a href=javascript:set_normal() class=btn3>정상주문으로 복귀</a>";
           break;
        }
    }

    ////////////////////////////////////////////////
    // CS 상세 정보
    function get_detail ()
    {
       global $connect;
       ///////////////////////////////////////
       $seq = $_REQUEST["seq"];

       ///////////////////////////////////////
       $sql = "select * from orders where seq = '$seq'";
       $list = mysql_fetch_array(mysql_query($sql, $connect));

       return $list;
    }

    function get_packed_options( $pack_list )
    {
        global $connect;

        $arr_pack_list = split ( ",", $pack_list );

        $i = 1;
        foreach ( $arr_pack_list as $id )
        {
                if ( $i != 1 ) $_ids .= ",";
                $_ids .= "'$id'";
                $i++;
        }

        $query = "select options from products where product_id in ( $_ids )";

        // echo $query;

        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array ( $result ) )
        {
                $options .= " $data[options], ";
        }
        return "(묶음 상품 옵션: $options )";
    }

   // org_id return
   function get_org_id( $product_id )
   {
       return class_E::get_org_id( $product_id );
   }

   ////////////////////////////////////////////////////////
   // option이 
   function option_string( $product_id )
   {
      global $connect;

      $query = "select options from products where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

?>
      <table border=0 cellpadding=0 cellspacing=0>
         <tr>
            <td width=100 align=center><img src=images/li.gif align=absmiddle> 상품 옵션</td>
            <td width=1 bgcolor=cccccc></td>
            <td width=4></td>
            <td width=300><?= nl2br($data[options]) ?></td>
         </tr>
      </table>
<?
   }
   ////////////////////////////////////////////////////////
   // option이 
   function option_combo( $product_id )
   {
      global $connect;

      $query = "select options from products where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $options = $data[options];

      $option = split ( "\n", $options );
      $cnt =  count($option);

      $i = 0;
      while ( $i < $cnt - 1 )
      {
         if ( !trim( $option[$i] ) ) break; 

         list ( $key, $opts ) = split ( ":" , $option[$i] );

         echo "
         <br>
         <input type=hidden name=option" . $i . "_key value=\"$key\">
         <select name=option" . $i . "_value style=width:200>";
            echo "<option value=0>$key</option>";

         $os = split(",", $opts);
         foreach ( $os as $o )
            echo "<option value='$o' alt='$o'>$o</option>";

         echo "</select>";
         $i++;
      }
?>

<?
//      return $options; 
   }

   /////////////////////////////////////////
   // cs의 주문정보를 update
   // datea : 2005.9.16
   function order_update( $redirect_option=1 )
   {
      global $connect;
 
      $transaction = $this->begin("주문정보수정");

      if ( $_REQUEST["redirect_option"] )
         $redirect_option = $_REQUEST["redirect_option"];

      $query = "update orders set ";
      $options = "";

      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;
         if ( preg_match("/_dwi/",$key)) continue;
         if ( preg_match("/lastscrollerpos/",$key)) continue;

         if ( preg_match("/^option/", $key) )
         {  
             if ( preg_match ("/key/", $key) )
                $options .= $val . ":";
             if ( preg_match ("/value$/", $key) )
                $options .= $val . ",";
         }
         else if ( $key == "zip1")
         {
               //$zip = $val . "-";
               $zip = $val;
         }
         else if ( preg_match("/^zip2/", $key) )
               $zip .= "-" . $val;
         else
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "redirect_option" && $key != "order_cs" && $key != "org_qty" && $key != "popup1221" && $key != "trans_fee" && $key != "top_url" && $key != "shop_price" && $key != "supply_price" && $key != "org_price" && $key != "amount" )
               $query .= $key . "='" . $val . "',"; 
      }

      $query .= "order_cs = " . _change_req_b . ", recv_zip='$zip'";

      ////////////////////////////////////////////////////////////
      // 옵션 변경을 선택할 경우에만 옵션값을 변경한다.
      // if ( $_REQUEST["option_change"] )
      // 옵션별 발주가 아닐경우  
      global $options;
      if ( !$_SESSION[STOCK_MANAGE_USE] )
          $query .= ",options = '$options'";

      // 교환할 경우 묶음 매칭을 다시 한다.
      // date: 2007.6.18
      $query .= ", pack_list=null";

      $query .= " where seq= '" . $_REQUEST["seq"] . "'";

      mysql_query ( $query, $connect );

      //$this->jsAlert("변경 되었습니다");
      $transaction = $this->end($transaction);
      if ( $redirect_option )
      {
         global $link_url, $top_url;
         $this->redirect( $link_url . "&top_url=" . $top_url );
         exit;
      }
   }

   /////////////////////////////////////////
   // cs의 주문정보를 update
   // datea : 2005.9.16
   // exchange_option: 1 => 맞교환
   function create_order( $order_id, &$seq, $exchange_option = 0 )
   {
      global $connect, $org_qty;

      $query = "insert into orders set ";
      $options = "";

      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;
         if ( $key == "lastscrollerpos" ) continue;
         /*
         if ( preg_match("/^option/", $key) )
         {  
             if ( preg_match ("/key/", $key) )
                $options .= $val . ":";
             if ( preg_match ("/value$/", $key) )
                $options .= $val . ",";
         }
         else 
         */
         if ( $key == "order_id")
               $query .= $key . "='" . $order_id. "',"; 
         else if ( $key == "zip1")
               $zip = $val . "-";
         else if ( preg_match("/^zip2/", $key) )
               $zip .= $val;
//         else if ( $key == "org_qty" )
//             $query .= "qty='" . $org_qty . "',";
         else if ( $key == "order_cs" )
         {
             if ( $exchange_option )
                $query .= "status='" . _order_exchange . "',";

             $query .= "order_cs ='" . _change . "',";
         }
         else
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "trans_fee" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "order_cs" && $key != "org_qty" && $key != "top_url" && !preg_match( "/_dwi/", $key) )
               $query .= $key . "='" . $val . "',"; 
      }
      $query .= "recv_zip='$zip'";

      ////////////////////////////////////////////////////////////
      // 옵션 변경을 선택할 경우에만 옵션값을 변경한다.
      /*
      if ( $_REQUEST["option_change"] )
          $query .= ",options = '$options'";
      else
         $query .= ",options='" . $_REQUEST["options"] . "'";
      */
      ////////////////////////////////////////////////////////
      // order_cs 
      $query .= ", trans_price='" . $_REQUEST["trans_fee"] . "'";

      // collect_date / order_date = Now
      $query .= ", collect_date = Now()";

      //debug( $_SESSION[LOGIN_NAME] . ":" . $query );

      mysql_query ( $query, $connect );

      ///////////////////////////////////
      // seq를 가져온다
      $query = "select seq from part_cancel order by seq desc limit 1";

      $result = mysql_query( $query, $connect );
      $data =mysql_fetch_array ( $result );
      $seq = $data[seq];

   }
   function enable_sale( $product_id )
   {
      class_E::enable_sale( $product_id );
   }

function print_delivery( $delivery_office, $delivery_no, $icon_type=0 )
{
        if($delivery_office == "") 
                $delivery_office = "해당사항 없음";

        // $delivery_office = strtoupper( trim( $delivery_office ) );
        $delivery_office = $delivery_office;

        switch ( $delivery_office )
        {
                case "KTLogics":
                        $from_date = date("Ymd", mktime (0,0,0,date("m")  , date("d")-10, date("Y")));
                        $to_date = date("Ymd", mktime (0,0,0,date("m")  , date("d"), date("Y")));
                        echo "<a href='http://www.kls.co.kr/customer/cus_trace_02.asp?fromDate=$from_date&invc_no=$delivery_no&invcSeq=&name=&name_type=send&searchMethod=I&senderName=&telno1=&telno2=&telno3=&toDate=$to_date' target=new>";
                        break;
                case "로엑스택배":
                        echo "<a href='http://www.loexe.co.kr/customer/cus_trace_02.asp?name_type=send&searchMethod=I&invc_no=$delivery_no' target=new>";
                        break;
                case "하나로택배":
                        echo "<a href='http://www.hanarologis.com/branch/chase/listbody.html?a_gb=center&a_cd=4&a_item=0&fr_slipno=$delivery_no' target=new>";
                        break;
                case "ECLINE":
                case "SC로지스":
                case "SC로지스(사가와)":
                case "사가와택배":
                        $no = str_replace( array(" "),"",$delivery_no);
                        echo "<a href='http://www.sagawa-korea.co.kr/sub4/default2_2.asp?awbino=$no' target=new>";
                        break;
                case "고려택배":
                        echo "<a href='http://www.gologis.com/delivery/s_search.asp?f_slipno=$delivery_no' target=new>";
                        break;
                case "로젠택배":
                        echo "<a href='http://www.ilogen.com/iLOGEN.Web/TRACE/TraceView.aspx?gubun=slipno&id=&slipno=$delivery_no' target=new>";
                        break;

                case "한덱스":
                        echo "<a href='http://ptop.e-handex.co.kr:8080/jsp/tr/detailSheet.jsp?iSheetNo=$delivery_no' target=new>";
                        break;
                case "CJ택배":
                case "CJ 택배":
                case "CJGLS" :
                case "CJ GLS택배" :
                        // echo "<a href='http://nexs.cjgls.com/web/detailform.jsp?slipno=slipno={$delivery_no}' target=_new>";
                        echo "<a href='http://nexs.cjgls.com/web/detailform.jsp?slipno=$delivery_no' target=_new>";
                        break;
                case "대한통운" :
                        //$url = "http://www.doortodoor.co.kr/servlets/cmnChnnel?tc=dtd.cmn.command.c03condiCrg01Cmd&invc_no=";
                        $url = "http://www.doortodoor.co.kr/jsp/cmn/Tracking.jsp?QueryType=3&pTdNo=";
                        echo "<a href='" . $url . $delivery_no ."' target=_new>";
                        break;
                case "삼성택배" :
                case "CJHTH" :
                        //echo "<a href='http://www.cjgls.co.kr/kor/service/service02_01.asp?slipno=$delivery_no' target=_new>";
                        echo "<a href='http://nexs.cjgls.com/web/detailform.jsp?slipno=$delivery_no' target=_new>";
                        
                        break;
                case "아주택배(구형)" :
                        echo "<a href='/template/aju.htm?trans_no={$delivery_no}' target=_new>";
                        break;
                case "아주택배" :
                        echo "<a href='/template/aju.htm?trans_no={$delivery_no}' target=_new>";
                        break;
                case "아주" :
                        echo "<a href='/template/aju.htm?trans_no={$delivery_no}' target=_new>";
                        break;
                case "우체국" :
                case "우편등기" :
                case "우체국택배" :
                        echo "<a href='http://service.epost.go.kr/trace.RetrieveRegiPrclDeliv.postal?sid1={$delivery_no}' target=_new>";
                        break;
                case "한국택배" :
                        echo "<a href='http://dms.ktlogis.com:8080/trace/TraceProduct.jsp' target=_new>$delivery_office</a>";
                        break;
                case "한진택배" :
                        echo "<a href='http://www.hanjin.co.kr/transmission/transmission.jsp?wbl_num=".trim($delivery_no)."' target=_new>";
                        break;
                case "현대택배" :
                        echo "<a href='http://scm.ezadmin.co.kr/template/hyundai.pl?InvNo={$delivery_no}' target=_new>";
                        // temporary....the good is not always good. yaplab
                        //return "<a href='http://www.hyundaiexpress.com/hydex/html/tracking/tracking_index.htm' target=_new>$delivery_office</a>";
                        break;
                case "트라넷":
                case "트라넷택배" :
                        echo "<a href='/template/tranet.htm?gubun=1&iv_no={$delivery_no}' target=_new>";
                        break;
                case "KGB택배" :
                case "KGB" :
                        echo "<a href='http://www.kgbls.co.kr/tracing.asp?number={$delivery_no}' target=new>";
                        break;
                case "KGB특급택배" :
                        echo "<a href='http://www.ikgb.co.kr/' target=_new>";
                        break;
                case "FAMILY":
                case "동부익스프레스택배" :
                        if ( strlen( $delivery_no) < 10 )
                                echo "<a href='http://www.e-family.co.kr/' target=_new>";
                        else
                                echo "<a href='http://www.dongbuexpress.co.kr/Html/Delivery/DeliveryCheckView.jsp?item_no=$delivery_no' target=_new>";
                        break;
                case "이클라인" :
                        echo"<a href='http://www.ecline.net/tracking/customer02.html#t01' target=_new>";
                        break;
                case "세덱스" :
                        echo"<a href='http://ptop.sedex.co.kr:8080/jsp/tr/detailSheet.jsp?iSheetNo=$delivery_no' target=_new>";
                        break;
                case "이노지스" :
                case "이노지스택배" :
                        echo"<a href='http://www.innogis.net/Tracking/Tracking_view.asp?invoice=$delivery_no' target=_new>";
                        break;
                case "옐로우캡" :
                        echo "<a href='http://www.yellowcap.co.kr/custom/inquiry_result.asp?INVOICE_NO=$delivery_no' target=_new>";
                        break;
                default :
                        return $delivery_office;
                        break;
        }

        if ( $icon_type )
          echo "<img src=images/icon_04.gif alt=배송확인 align=absmiddle>"; 
        else
          echo "$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='택배조회'>";

        echo "</a>";
        return "";
   }

    ////////////////////////////////////
    // Use E111.htm
    function sms()
    {
        /*
        global $connect;
        global $template;

        $sender = $_REQUEST[sender];
        $receiver = $_REQUEST[receiver];
        $message = $_REQUEST[message];

        // 1. send sms
        require_once "lib/sms_lib.php";
        sms_send($receiver, $sender, $message);
        
        // 2. upate sys_domain
        $sys_connect = sys_db_connect();
        $upd_sql = "update sys_domain set sms = sms-1 where id = '"._DOMAIN_."'";
        @mysql_query($upd_sql, $sys_connect);

        // 3. insert sms_history
        $message = addslashes($message);
        $ins_sql = "insert into sms_history (input_date, input_time, userid, username, receiver, sender, message) values (now(), now(), '$_SESSION[LOGIN_ID]', '$_SESSION[LOGIN_NAME]', '$receiver', '$sender', '$message')";
        @mysql_query($ins_sql, $connect);
        */
        
        echo "<script>alert('문자메시지 전송이 완료되었습니다.');</script>";
        echo "<script>self.close();</script>";
        exit;
    }

    /////////////////////////////////////////
    // 부분 취소 개수
    // date: 2005.12.7 - jk.ryu
    function get_part_cancel_count ( $seq )
    {
        return class_E::get_part_cancel_count( $seq );
    }

   ////////////////////////////////////////
   // excel download
   function download2()
   {
      require_once 'Spreadsheet/Excel/Writer.php';
      global $connect, $saveTarget, $filename;

      // Creating a workbook
      $workbook = new Spreadsheet_Excel_Writer();

      // sending HTTP headers
      $workbook->send( $filename . ".xls" );

      // Creating a worksheet
      $worksheet = $workbook->addWorksheet('주문 내역');

      // download format에 대한 정보를 가져온다
      // $result = $this->get_format();

      $download_items = array (
          "seq"                        => "관리번호",
          "order_id"                => "주문번호",
          "status"                => "상태",
          "order_cs"                => "cs상태",
          "shop_name"                => "판매처",
          "trans_who"                => "배송비",
          "product_name"        => "상품명",
          "options"                => "주문옵션",
          "memo"                => "메모사항",        // 메모 추가 2006.3.9
          "org_price"                => "공급가",
          "amount"                => "총 판매 금액",    // K
          "shop_price"                => "판매단가",       // K
          "qty"                        => "개수",
          "order_name"                => "주문자",
          "order_tel"                => "주문자 연락처",
          "order_mobile"        => "주문자 연락처2",
          "recv_name"                => "수령자",
          "recv_tel"                => "수령자 연락처",
          "recv_mobile"                => "수령자 연락처2",
          "recv_zip"                => "우편번호",
          "recv_address"        => "배송지주소",
          "message"                => "주문시 요구사항",
          "collect_date"        => "발주일",
          "trans_name"                => "택배사",
          "trans_no"                => "송장번호",
      ); 

      //////////////////////////////////////////////
      $limit_option = 1;   // 전체가 출력됨 0: 20개만 출력됨

      $this->cs_list( &$total_rows, &$result, $limit_option );
      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
   }
 
   /////////////////////////////////////////////////////// 
   // excel에 write 함
   // date: 2005.10.20
   function write_excel ( $worksheet, $result, $download_items, $rows = 0 )
   {
      $i = $rows ? $rows : 0;
      $j = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
         // header
         if ( $i == 0 && $header != -99 )
         {
            $j = 0;
            foreach ( $download_items as $key=>$value )
            {
               $worksheet->write($i, $j, $value );
               $j++;
            }
            $i++;
         }

         // increase column
         $j = 0;
         foreach ( $download_items as $key=>$value )
         {
            $worksheet->writeString($i, $j, $this->get_data( $data, $key, $i ) );
            $j++;
         }

         // cs list
         global $connect;
         $query = "select * from csinfo where order_seq='$data[seq]'";

         $result_cs = mysql_query ( $query, $connect );

         while ( $data_cs = mysql_fetch_array ( $result_cs ) )
         {
             $worksheet->write($i, $j, $j . "/" . $data_cs[content] );
             $j++;
         }

         // increase row
         $i++;
      }
   }
   
   function get_data( $data, $key, $i )
   {
        class_D::get_product_name_option2( $data[product_id], &$product_name, &$product_option );

      switch ( $key )
      {
          case "options":
                //옵션별 발주일 경우와 그렇지 않을 경우..
                if ( $_SESSION[STOCK_MANAGE_USE] )
                        return $product_option ? $product_option : $data[options];
                else
                        return $data[options];
                break;
          case "amount":
              return $data[code1] ? $data[code1] : $data[amount];
              break;
          case "shop_name":
              return class_C::get_shop_name( $data[shop_id] );
              break;
          case "product_name":
                        return $product_name;
              break;
          case "status":
              return $this->get_order_status( $data[status],2 );
              break;
          case "order_cs":
              return $this->get_order_cs( $data[order_cs],2 );
              break;
          case "trans_name":// 택배사 이름
              return class_E::get_trans_name($data[trans_corp]);
              break;
          case "order_tel":
          case "order_mobile":
          case "recv_tel":
          case "recv_mobile":
              if ( preg_match("/-/", $data[$key]) )
                  return $data[$key];
              else
              {
                  // 사이에 - 를 넣는다.
                  return substr( $data[$key],0,3 ) . "-" . substr( $data[$key],3,strlen($data[$key]) );
              }
              break;
          default :
              return $data[$key];
      }
   }

   function link_shop_product( $product_id, $shop_id )
   {
        $shop_xp = (int)($shop_id%100);
       switch ( $shop_xp )
       {
           // G market
           case "2":
               $link = "http://www.gmarket.co.kr/challenge/neo_goods/goods.asp?goodscode=$product_id";
               break;
       } 

       if ( $link && $product_id )
           echo "&nbsp;<a href='$link' class=btn1 target=new>판매처상품</a>";
   }
}

?>
