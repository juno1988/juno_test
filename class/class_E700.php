<?
require_once "class_top.php";
require_once "class_E.php";
require_once "class_C.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_E700
//

class class_E700 extends class_top {

    ///////////////////////////////////////////

    function E700()
    {
	global $connect;
	global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;


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

    function E701()
    {
	global $connect;
	global $template;

        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $content = "==반품정보==\n반품택배사:\n반품송장번호:\n";
        $content .= "환불계좌:\n환불은행:\n예금주:\n ";

	$sql = "select smsuse from userinfo where id = '$_SESSION[LOGIN_ID]' and level = 0";
	$user_data = mysql_fetch_array(mysql_query($sql, $connect));
	$smsuse = $user_data[smsuse];

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E702()
    {
	global $connect;
	global $template, $page;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;


	$line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-30 day'));
        $end_date = $_REQUEST["end_date"];

        if ( $page )
        {
	   echo "<script>show_waiting();</script>";
           $this->knc_cs_list( &$total_rows, &$r_cs );
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        if ( $page )
	   echo "<script>hide_waiting();</script>";
    }

    function E703()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E704()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E705()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E706()
    {
	global $connect;
	global $template;
 
        // cs_type=취소
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E707()
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

    function E708()
    {
	global $connect;
	global $template;
 
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E709()
    {
	global $connect;
	global $template;
 
        $link_url = "?" . $this->build_link_url();
        $list = $this->get_detail();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E710()
    {
	global $connect;
	global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E711()
    {
	global $connect;
	global $template;
 
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }



    ///////////////////////////////////////////////////
    // cs list 
    // date : 2005.9.14
    function cs_list( &$total_rows, &$result , $limit_option = 0)
    {
       global $connect, $trans_who;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = $_REQUEST[keyword];
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

  // 검색키워드
  if ($keyword)
  {
    switch ( $search_type )
    {
        case 1: // 주문자
            $options .= "and a.order_name like '%${keyword}%'" ;
            break;
        case 2: // 주문번호
            $options .= "and a.order_id = '${keyword}'" ;
            break;
        case 3: // 상품명
            $options .= "and a.product_name like '%${keyword}%' " ;
            break;
        case 4: // 전화번호
            $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;
            break;
        case 5: // 수령자
            $options .= "and (a.recv_name like '%$keyword%') " ;
            break;
        case 6:  // 송장번호
            $options .= "and a.trans_no = $keyword "; 
            break;
    }
  }
//echo $options;

  // 주문상태
  if ($order_cs != '')
  {
    switch ( $order_cs )
    {
        case 1:  // 정상
           $options .= "and a.status = "._order_normal;
        break;
        case 2: // 취소
           $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
        break;
        case 3: // 교환 
           $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
        break;
	case 4: // 송장입력
	   $options .= "and a.status = '" . _trans_no . "'";
	   break;
	case 5: // 배송확인
	   $options .= "and a.status = '" . _trans_confirm . "'";
	   break;
    }
  }

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

  if ( !$limit_option )
     $limit_clause = " limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;


  $result_cnt = mysql_query($count_sql.$where_clause, $connect) or die(mysql_error());

  $list = mysql_fetch_array($result_cnt);
  $total_rows = $list[cnt];

  $query = $sql.$where_clause.$limit_clause;

//echo $query . $limit_clause;
//exit;

  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

    ///////////////////////////////////////////////////
    // cs list 
    // date : 2005.9.14
    function knc_cs_list( &$total_rows, &$result , $limit_option = 0)
    {
       global $connect, $trans_who;

       ///////////////////////////////////////
       $search_type = $_REQUEST[search_type];
       $keyword = $_REQUEST[keyword];
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

  // 검색키워드
  if ($keyword)
  {
    switch ( $search_type )
    {
        case 1: // 주문자
            $options .= "and a.order_name like '%${keyword}%'" ;
            break;
        case 2: // 주문번호
            $options .= "and a.order_id = '${keyword}'" ;
            break;
        case 3: // 상품명
            $options .= "and a.product_name like '%${keyword}%' " ;
            break;
        case 4: // 전화번호
            $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;
            break;
        case 5: // 수령자
            $options .= "and (a.recv_name like '%$keyword%') " ;
            break;
        case 6:  // 송장번호
            $options .= "and a.trans_no = $keyword "; 
            break;
    }
  }

  // 주문상태
  if ($order_cs != '')
  {
    switch ( $order_cs )
    {
        case 1:  // 정상
           $options .= "and a.status = "._order_normal;
        break;
        case 2: // 취소
           $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
        break;
        case 3: // 교환 
           $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
        break;
	case 4: // 송장입력
	   $options .= "and a.status = '" . _trans_no . "'";
	   break;
	case 5: // 배송확인
	   $options .= "and a.status = '" . _trans_confirm . "'";
	   break;
    }
  }

  // session level이 0이면 업체임
  //if ( !$_SESSION["LOGIN_LEVEL"] )
  //    $options .= " and supply_id = '" . $_SESSION["LOGIN_CODE"] . "'";

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
  // 판매처 10033 KNC인것만 보여짐.
  $sql = "select a.* 
            from orders a";
  $count_sql = "select count(*) cnt from orders a ";

  $where_clause = "
           where a.collect_date >= '$start_date'
             and a.collect_date <= '$end_date'
	     and a.shop_id = '10033' 
                 ${options}
           order by a.seq desc";

  if ( !$limit_option )
     $limit_clause = " limit $starter, $line_per_page";

  $query = $count_sql.$where_clause;


  $result_cnt = mysql_query($count_sql.$where_clause, $connect) or die(mysql_error());

  $list = mysql_fetch_array($result_cnt);
  $total_rows = $list[cnt];

  $query = $sql.$where_clause.$limit_clause;

  $result = mysql_query($sql.$where_clause.$limit_clause, $connect) or die(mysql_error());

    }

    ///////////////////////////////////////////////
    // cs의 존재 여부 check
    // date: 2005.12.2
    function cs_exist( $seq )
    {
        global $connect;

        $query = "select count(*) cnt from csinfo where order_seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        if ( $data[cnt] )
            echo "<img src=images/G_cs.gif align=absmiddle>";
        else
            echo "<img src=images/P_cs.gif align=absmiddle>";
    }

    ///////////////////////////////////////////////
    // 교환
    // date: 2005.9.21 jk
    function change_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty;

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

       $this->create_order( $order_id, &$seq, $exchange_option );

       // cs를 남겨야 함
       $this->csinsert(0);

       // opener redirect
       // $this->opener_redirect( $link_url );
       $this->redirect( $link_url );

       $this->end( $transaction );
       exit;
    }

    /////////////////////////////////
    // 배송전 교환 요청
    function modify_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty;
       global $seq, $connect;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("변경");

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

       $this->redirect( $link_url );

       // 완료 페이지
       //$this->redirect( "?template=E709&seq=$seq");
       exit;
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

       $this->jsAlert( " 취소 되었습니다 ");
       $this->redirect( base64_decode($link_url) );

       $this->end( $transaction );
       exit;
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
       $this->redirect( base64_decode($link_url) );
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
	$content = addslashes($_REQUEST[content]);

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

        if ( $link_option )
           $this->redirect ( $link_url );
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
           case _change_com_b:
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
              if ( $data[status] >= _trans_confirm )
              {
                 $this->end( $transaction, "주문복원오류" );
                 $this->jsAlert("이미 배송 단계 입니다."); 
                 $this->back();
                 exit;
              }
           break;
           case _change_req_a:  // 배송후 교환 요청=>교환 발주의 상태가 확인 이하
              $query = "select status from orders where order_id = 'C" . $data[order_id] . "'";
              $result = mysql_query ( $query, $connect );
              $data = mysql_fetch_array ( $result );

              if ( $data[status] >= _order_confirm )
              {
                 $this->end( $transaction, "주문복원오류" );
                 $this->jsAlert("이미 배송 단계 입니다."); 
                 $this->back();
                 exit;
              }
           break;
       } 

       $query = "update orders set order_cs=0 where seq='$seq'";
       mysql_query ( $query , $connect );
       $link_url = urldecode( $link_url );
       $this->end( $transaction );

       $this->redirect( $link_url );
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
          $this->opener_redirect ( "template.htm?template=E702&seq=$seq" );
          $this->closewin();
          exit;
       }
       // 2.2 배송 후
       else if ( $data[status] >= _trans_no || $data[status] == _order_confirm )
       {
          $this->redirect("?template=E708&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
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
          $this->redirect("?template=E707&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }    
       else
       {
          ///////////////////////////////////////////////////
          // 배송 여부를 확인해야 하는 상태
          $this->redirect("?template=E706&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
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
          $this->redirect ( base64_decode( $link_url ) );
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

//echo "status->$status";
//exit;
       if ( $_REQUEST["seq"] )
         $seq = $_REQUEST["seq"]; 
       
       $query = "update orders set order_cs=$status, refund_cs_date=Now() where seq='$seq'";
       mysql_query ( $query, $connect );
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
              echo "&nbsp;&nbsp; <a href=javascript:set_normal() class=btn3>정상주문으로 복귀</a>";
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

   function get_org_id( $product_id )
   {
       global $connect;
       $query = "select org_id from products where product_id='$product_id'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       return $data[org_id];
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

         if ( preg_match("/^option/", $key) )
         {  
             if ( preg_match ("/key/", $key) )
                $options .= $val . ":";
             if ( preg_match ("/value$/", $key) )
                $options .= $val . ",";
         }
         else if ( $key == "zip1")
         {
               $zip = $val . "-";
         }
         else if ( preg_match("/^zip2/", $key) )
               $zip .= $val;
         else
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "redirect_option" && $key != "order_cs" && $key != "org_qty" && $key != "popup1221" && $key != "trans_fee")
               $query .= $key . "='" . $val . "',"; 
      }

      $query .= "order_cs = " . _change_req_b . ", recv_zip='$zip'";

      ////////////////////////////////////////////////////////////
      // 옵션 변경을 선택할 경우에만 옵션값을 변경한다.
      if ( $_REQUEST["option_change"] )
          $query .= ",options = '$options'";

      $query .= " where seq= '" . $_REQUEST["seq"] . "'";

      mysql_query ( $query, $connect );

      //$this->jsAlert("변경 되었습니다");
      $transaction = $this->end($transaction);
      if ( $redirect_option )
      {
         $this->redirect ( $_REQUEST["link_url"] );
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
         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "seq" && $key != "template" && $key != "order_seq" && $key != "content" && $key != "trans_fee" && $key != "cs_type" && $key != "cs_reason" && $key != "cs_result" && $key != "order_cs" && $key != "org_qty" )
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

//echo $query;
//exit;
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

function print_delivery( $delivery_office, $delivery_no )
{
	if($delivery_office == "") 
		$delivery_office = "해당사항 없음";

	$delivery_office = strtoupper( trim( $delivery_office ) );
	switch ( $delivery_office )
	{
		case "로젠택배":
			return "<a href='http://www.ilogen.com/customer/reserve_03_ok.asp?f_slipno={$delivery_no}' target=new>$delivery_office</a>";
		case "CJ택배":
		case "CJGLS" :
			return "<a href='http://www.cjgls.com/contents/gls/gls004/gls004_06_01.asp?slipno={$delivery_no}' target=_new>$delivery_office</a>";
			return"<a href='http://www.cjgls.co.kr/kor.html' target=_new>$delivery_office</a>";
		case "대한통운" :
                        $url = "http://www.doortodoor.co.kr/servlets/cmnChnnel?tc=dtd.cmn.command.c03condiCrg01Cmd&invc_no=";
			return "<a href='" . $url . $delivery_no ."' target=_new>$delivery_office  <img src=images/car.gif border=0 align=absmiddle alt=택배조회></a>";
		case "삼성택배" :
			return "<a href='http://www.samsunghth.com/' target=_new>$delivery_office</a>";
		case "아주택배" :
		case "아주택배(구형)" :
                        $no1 = substr( $delivery_no, 0,2);
			$no2 = substr( $delivery_no, 2,4);
                        $no3 = substr( $delivery_no, 6,4);
			$url = "http://www.ajulogis.co.kr/common/asp/search_history_proc.asp?sheetno1=" . $no1. "&sheetno2=$no2&sheetno3=$no3";
			return "<a href='$url' target=_new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='택배조회'></a>";
		case "우체국" :
		case "우편등기" :
		case "우체국택배" :
			return "<a href='http://cp-asw.epost.go.kr:4949/trace/Trace_list.jsp?sid1={$delivery_no}' target=_new>$delivery_office</a>";
		case "한국택배" :
			return "<a href='http://dms.ktlogis.com:8080/trace/TraceProduct.jsp' target=_new>$delivery_office</a>";
		case "한진택배" :
			return "<a href='http://www.hanjinexpress.hanjin.net/customer/plsql/hddcw07.result?wbl_num=".trim($delivery_no)."' target=_new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='택배조회'></a>";
		case "현대택배" :
			//return "<a href='http://www.hyundaiexpress.com/hydex/servlet/tracking/cargoSearchResult?InvoiceNumber={$delivery_no}' target=_new>{$delivery_office}</a>";
			return "<a href='http://www.hyundaiexpress.com/hydex/jsp/support/search/re_08.jsp?InvNo={$delivery_no}' target=_new>{$delivery_office}</a>";
			// temporary....the good is not always good. yaplab
			//return "<a href='http://www.hyundaiexpress.com/hydex/html/tracking/tracking_index.htm' target=_new>$delivery_office</a>";
		case "트라넷" :
			return "<a href='http://www.etranet.co.kr/new/index.php' target=_new>$delivery_office</a>";
		case "KGB" :
			return "<a href='http://www.kgbls.co.kr/tracing.asp?number={$delivery_no}' target=new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='택배조회'></a>";
		case "KGB특급택배" :
			return "<a href='http://www.ikgb.co.kr/' target=_new>$delivery_office</a>";
		case "훼미리택배" :
			if ( strlen( $delivery_no) < 10 )
				return "<a href='http://www.e-family.co.kr/' target=_new>$delivery_office</a>";
			else
				return "<a href='http://www.e-family.co.kr/tracking.jsp?item_no1=". substr( $delivery_no, 0, 4 ) ."&item_no2=". substr( $delivery_no, 4, 4 ) . "&item_no3=". substr( $delivery_no, 8, 4 )."' target=_new>$delivery_office</a>";
		case "이클라인" :
			return "<a href='http://www.ecline.net/tracking/customer02.html#t01' target=_new>$delivery_office</a>";
		case "옐로우캡" :
			return "<a href='http://www.yellowcap.co.kr/tak/content01_1.htm' target=_new>$delivery_office</a>";
		default :
			return $delivery_office;
			break;
	}
   }

    ////////////////////////////////////
    // Use E711.htm
    function sms()
    {
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
	@mysql_query($ins_sql, $sys_connect);

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
          "order_id"		=> "주문번호",
          "org_price"		=> "판매가",
          "product_name"	=> "상품명",
          "options"		=> "주문옵션",
          "qty"			=> "개수",
          "shop_name"		=> "판매처",  // 작업 필요
          "order_name"		=> "주문자",
          "order_tel"		=> "주문자 연락처",
          "recv_name"		=> "수령자",
          "recv_tel"		=> "수령자 연락처",
          "recv_mobile"		=> "수령자 연락처2",
          "recv_zip"		=> "배송지우편번호",
          "recv_address"	=> "배송지주소",
          "message"		=> "주문시 요구사항",
          "collect_date"	=> "발주일",
          "status"	=> "주문상태",
          "trans_name"		=> "택배사",
          "trans_no"		=> "송장번호"
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
            $worksheet->write($i, $j, $this->get_data( $data, $key, $i ) );
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
      switch ( $key )
      {
          case "shop_name":
              return class_C::get_shop_name( $data[shop_id] );
              break;
          case "status":
              return $this->get_order_status( $data[status] );
              break;
          case "trans_name":// 택배사 이름
              return class_E::get_trans_name($data[trans_corp]);
              break;
          default :
              return $data[$key];
      }
   }


}

?>
