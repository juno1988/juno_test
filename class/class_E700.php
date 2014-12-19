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

        $content = "==��ǰ����==\n��ǰ�ù��:\n��ǰ�����ȣ:\n";
        $content .= "ȯ�Ұ���:\nȯ������:\n������:\n ";

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
 
        // cs_type=���
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function E707()
    {
	global $connect;
	global $template;
 
        // cs_type=���
        $cs_type = 1;
        $link_url = base64_decode( $_REQUEST["link_url"] );

        $content = "==��ǰ����==\n��ǰ�ù��:\n��ǰ�����ȣ:\n";
        $content .= "ȯ�Ұ���:\nȯ������:\n������:\n ";
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
  // �˻�
  $starter = $page ? ($page-1) * $line_per_page : 0;

  $options = "";

  // �˻�Ű����
  if ($keyword)
  {
    switch ( $search_type )
    {
        case 1: // �ֹ���
            $options .= "and a.order_name like '%${keyword}%'" ;
            break;
        case 2: // �ֹ���ȣ
            $options .= "and a.order_id = '${keyword}'" ;
            break;
        case 3: // ��ǰ��
            $options .= "and a.product_name like '%${keyword}%' " ;
            break;
        case 4: // ��ȭ��ȣ
            $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;
            break;
        case 5: // ������
            $options .= "and (a.recv_name like '%$keyword%') " ;
            break;
        case 6:  // �����ȣ
            $options .= "and a.trans_no = $keyword "; 
            break;
    }
  }
//echo $options;

  // �ֹ�����
  if ($order_cs != '')
  {
    switch ( $order_cs )
    {
        case 1:  // ����
           $options .= "and a.status = "._order_normal;
        break;
        case 2: // ���
           $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
        break;
        case 3: // ��ȯ 
           $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
        break;
	case 4: // �����Է�
	   $options .= "and a.status = '" . _trans_no . "'";
	   break;
	case 5: // ���Ȯ��
	   $options .= "and a.status = '" . _trans_confirm . "'";
	   break;
    }
  }

  // session level�� 0�̸� ��ü��
  if ( !$_SESSION["LOGIN_LEVEL"] )
      $options .= " and supply_id = '" . $_SESSION["LOGIN_CODE"] . "'";

  // �Ǹ�ó
  if ($shop_id != '')
    $options .= " and a.shop_id = '${shop_id}' " ;

  //���� ���� 
  if ( $trans_who )
    $options .= " and a.trans_who = '${trans_who}' " ;

  // ����ó
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
  // �˻�
  $starter = $page ? ($page-1) * $line_per_page : 0;

  $options = "";

  // �˻�Ű����
  if ($keyword)
  {
    switch ( $search_type )
    {
        case 1: // �ֹ���
            $options .= "and a.order_name like '%${keyword}%'" ;
            break;
        case 2: // �ֹ���ȣ
            $options .= "and a.order_id = '${keyword}'" ;
            break;
        case 3: // ��ǰ��
            $options .= "and a.product_name like '%${keyword}%' " ;
            break;
        case 4: // ��ȭ��ȣ
            $options .= "and (a.recv_tel like '%$keyword%' or a.recv_mobile like '%$keyword%') " ;
            break;
        case 5: // ������
            $options .= "and (a.recv_name like '%$keyword%') " ;
            break;
        case 6:  // �����ȣ
            $options .= "and a.trans_no = $keyword "; 
            break;
    }
  }

  // �ֹ�����
  if ($order_cs != '')
  {
    switch ( $order_cs )
    {
        case 1:  // ����
           $options .= "and a.status = "._order_normal;
        break;
        case 2: // ���
           $options .= "and a.order_cs in ("._cancel_req_b."," ._cancel_req_a . "," . _cancel_com_b . "," . _cancel_com_a ."," . _cancel_req_confirm . " )" ;
        break;
        case 3: // ��ȯ 
           $options .= "and a.order_cs in ("._change ."," ._change_req_b. "," ._change_req_a . "," ._change_com_b ."," ._change_com_a . "," . _change_req_confirm . ")" ;
        break;
	case 4: // �����Է�
	   $options .= "and a.status = '" . _trans_no . "'";
	   break;
	case 5: // ���Ȯ��
	   $options .= "and a.status = '" . _trans_confirm . "'";
	   break;
    }
  }

  // session level�� 0�̸� ��ü��
  //if ( !$_SESSION["LOGIN_LEVEL"] )
  //    $options .= " and supply_id = '" . $_SESSION["LOGIN_CODE"] . "'";

  // �Ǹ�ó
  if ($shop_id != '')
    $options .= " and a.shop_id = '${shop_id}' " ;

  //���� ���� 
  if ( $trans_who )
    $options .= " and a.trans_who = '${trans_who}' " ;

  // ����ó
  if ($supply_id != '')
    $options .= " and a.supply_id = '${supply_id}' " ;


  /////////////////////////////////////////////////////
  // �Ǹ�ó 10033 KNC�ΰ͸� ������.
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
    // cs�� ���� ���� check
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
    // ��ȯ
    // date: 2005.9.21 jk
    function change_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("��ȯ");

       ////////////////////////////////////////////////
       // �κ� ��� ������ �Է���
       if ( $qty != $org_qty )
           $this->part_cancel_action( "�����" );
       else
       {
           // �ֹ��� ���� ����
           $this->change_action();

           // ����...
           $this->change_cs_result(0); 
       }

       // �ֹ� ����
       // order_id
       $order_id = "C" . $order_id;

       if ( $order_cs == _exchange )
          $exchange_option = 1;
       else
          $exchange_option = 0;

       $this->create_order( $order_id, &$seq, $exchange_option );

       // cs�� ���ܾ� ��
       $this->csinsert(0);

       // opener redirect
       // $this->opener_redirect( $link_url );
       $this->redirect( $link_url );

       $this->end( $transaction );
       exit;
    }

    /////////////////////////////////
    // ����� ��ȯ ��û
    function modify_csinsert()
    {
       global $link_url, $order_id, $order_cs, $qty, $org_qty;
       global $seq, $connect;

       $link_url = base64_decode( $link_url );

       $transaction = $this->begin("����");

       ////////////////////////////////////////////////
       // �κ� ��� ������ �Է���
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "��� ��" );

           ///////////////////////////////////////
           // �ֹ� ���� order_id
           $order_id = "C" . $order_id;
    
           if ( $order_cs == _exchange )
              $exchange_option = 1;
           else
              $exchange_option = 0;

           $this->create_order( $order_id, &$seq, $exchange_option );

           ////////////////////////////////////////////////////////////////////
           // ���ο� �ֹ��� ���� �Ǳ� ������ part cancel�� ���¸� �Ϸ�� ����
           $query = "update part_cancel set status='ó���Ϸ�' where seq='$seq'";
           mysql_query( $query, $connect );
       }
       else
       {
           $query = "update orders set order_cs = " . _change_req_b . " where seq='$seq'";       
           mysql_query ( $query, $connect );

           // �ֹ��� ���� ���� 
           $this->order_update(0);
       }

       // cs�� ���ܾ� ��
       $this->csinsert(0);

       // opener redirect
       // $this->opener_redirect( $link_url );

       $this->end( $transaction );

       $this->redirect( $link_url );

       // �Ϸ� ������
       //$this->redirect( "?template=E709&seq=$seq");
       exit;
    }

    ////////////////////////////////////////////////
    // ��ǰ 
    function refund_csinsert()
    {
       global $link_url, $qty, $org_qty;

       $transaction = $this->begin("��ǰ");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // �κ� ��� Ȯ��
       if ( $qty != $org_qty )
           $this->part_cancel_action( "�����" );
       else
           $this->cancel_action("", _cancel_req_a ); // ����� ��� ��û

       $this->jsAlert( " ��� �Ǿ����ϴ� ");
       $this->redirect( base64_decode($link_url) );

       $this->end( $transaction );
       exit;
    }

    ////////////////////////////////////////////////
    // ��� 
    // �κ� ��� ������ ����
    function cancel_csinsert()
    {
       global $link_url;
       global $qty, $org_qty;

       $transaction = $this->begin("���");
       $this->csinsert(0); 

       ////////////////////////////////////////////////
       // �κ� ��� Ȯ��
       if ( $qty != $org_qty )
       {
           $this->part_cancel_action( "��� ��" );
       }
       else
          $this->cancel_action(); 

       //$this->set_small_window(" ��� �Ǿ����ϴ� ");
       //$this->opener_redirect ( base64_decode($link_url) );

       $this->jsAlert( " ��� �Ǿ����ϴ� ");
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

        $transaction = $this->begin( "�Ϲݻ��" );

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
        // ��� ��û�� ��� order�� order_cs�� ���¸� ��ҷ� �����ؾ� ��
        /*
        switch ( $cs_type )
        {
           case 1: // ���
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
    // ��� �ֹ� ������ �ֹ����� ���� Ȯ��
    function enable_cancel( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // ������ �ƴϸ� ��� ����
       if ( $order_cs )
          echo "disabled";
    }

    function enable_change( $status, $order_cs )
    {
       //if ( $status >= _order_confirm )
       //   echo "disabled";
       
       //////////////////////////////////////////////
       // ������ �ƴϸ� ��� ����
       if ( $order_cs )
          echo "disabled";
    }

    ////////////////////////////////////////////////
    // ���� ��ȯ
    function set_normal ( $order_cs='0' )
    {
       global $connect, $seq, $link_url;

       $transaction = $this->begin("�ֹ�����");

       $query = "select order_id, status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       switch ( $data[order_cs] )
       {
           case _cancel_com_b:
           case _cancel_com_a:
              $this->end( $transaction, "�ֹ���������" );
              $this->jsAlert("�̹� ��� �Ǿ����ϴ�"); 
              $this->back();
              exit;
           break;
           case _change:
           case _change_com_b:
           case _change_com_a:
              $this->end( $transaction, "�ֹ���������" );
              $this->jsAlert("��ȯ��û �ֹ��Դϴ�"); 
              $this->back();
              exit;
           break;
           case _exchange:
           case _exchange_com:
              $this->end( $transaction, "�ֹ���������" );
              $this->jsAlert("�±�ȯ��û �ֹ��Դϴ�"); 
              $this->back();
              exit;
           break;
           case _change_req_b:  // ����� ��ȯ ��û=>�ֹ��� ���°� Ȯ�� ����
              if ( $data[status] >= _trans_confirm )
              {
                 $this->end( $transaction, "�ֹ���������" );
                 $this->jsAlert("�̹� ��� �ܰ� �Դϴ�."); 
                 $this->back();
                 exit;
              }
           break;
           case _change_req_a:  // ����� ��ȯ ��û=>��ȯ ������ ���°� Ȯ�� ����
              $query = "select status from orders where order_id = 'C" . $data[order_id] . "'";
              $result = mysql_query ( $query, $connect );
              $data = mysql_fetch_array ( $result );

              if ( $data[status] >= _order_confirm )
              {
                 $this->end( $transaction, "�ֹ���������" );
                 $this->jsAlert("�̹� ��� �ܰ� �Դϴ�."); 
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
    // ��ȯ ���� ����
    // date: 2005.9.21 jk 
    function order_change()
    {
       global $connect, $seq;

       ////////////////////////////////////
       // ������ �����´�.
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       //////////////////////////////////
       // 1. ������� check
       switch ( $data[order_cs] )
       {
          case _cancel_req_b:
          case _cancel_req_a:
             $this->set_small_window ("�ֹ��� �������� ���� �� ó�� �ϼ���");
             exit;
          break;
          case _cancel_com_b:
          case _cancel_com_a:
             $this->set_small_window ("��� �Ϸ�� �ֹ��� ��ȯ�� �Ұ����մϴ�");
             exit;
          break;
          case _change_req_b:
          case _change_req_a:
             $this->set_small_window ("�̹� ��ȯ�� �ֹ� �Դϴ�");
             exit;
          break;
          case _change_com_b:
          case _change_com_a:
             $this->set_small_window ("��ȯ ���ֵ� �ֹ����� ó�� �ؾ� �մϴ�");
             exit;
          break;
       }

       //////////////////////////////////
       // 2. ����
       // 2.1 ��� ��
       if ( $data[status] < _order_confirm )
       {
          $this->opener_redirect ( "template.htm?template=E702&seq=$seq" );
          $this->closewin();
          exit;
       }
       // 2.2 ��� ��
       else if ( $data[status] >= _trans_no || $data[status] == _order_confirm )
       {
          $this->redirect("?template=E708&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }

       // 3.3 ó�� �Ұ�
       else
       {
          $this->set_small_window("ó�� �Ұ� ���� �Դϴ�");
          exit;
       }
    }

    /////////////////////////////////////////////////
    // �� �ֹ��� ���¸� 1: ��ҷ� �����Ѵ�
    // Date: 2005.09.20 jk
    function order_cancel()
    {
       global $connect, $seq, $cancel_cs_reason;

       $cs_reason = $cancel_cs_reason;  // cancel_cs_reason
       $cs_type = 1;                    // ���

       $transaction = $this->begin( "���" );

       ///////////////////////////////////////////////////
       // ���� �������� �´�
       $query = "select status, order_cs from orders where seq='$seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       /////////////////////////////////////////////////
       // step 1. order_cs Ȯ��
       // CS�� �Ϸ�� ��� ���� CS������ Ȯ���ؾ� ��
       // 1. �̹� ��� �Ϸ�� �ֹ��� �� ��� �Ұ�
       // 2. ��ȯ �ֹ��� ������ �� �ֹ���  ��� �Ұ� 
       switch ( $data[order_cs] )        
       {
           // �Ұ� case ��
           case _cancel_req_b: // ����� ��� ��û
           case _cancel_req_a: // ����� ��� ��û
//             $this->set_small_window ( "�̹� ��� ��û�� �Ǿ� ����" );
             $this->jsAlert( "�̹� ��� ��û�� �Ǿ� ����" );
             $this->back();
             exit;
           break;
           case _cancel_com_b:
           case _cancel_com_a:
             //$this->set_small_window ( "��� �Ϸ� �Ǿ����ϴ�" );
             $this->jsAlert( "��� �Ϸ� �Ǿ����ϴ�" );
             $this->back();
             exit;
           break;
           case _change_req_a:
           case _change_com_b:
           case _change_com_a:
           case _exchange_com:
             //$this->set_small_window ( "��ȯ ���ֵ� �ֹ����� ó�� �ؾ� �մϴ�" );
             $this->jsAlert( "��ȯ ���ֵ� �ֹ����� ó�� �ؾ� �մϴ�" );
             $this->back();
             exit;
           break;

       }

       //////////////////////////////////////////
       // step 2. ��� �� ��� �ֹ� Ȯ�� �� / �� check
       // �ֹ� �ٿ���� �� ��� Ȯ���� ��  
       // 2.1. Ȯ�� ��
       if ( $data[status] < _order_confirm )
       {
          $link_url = base64_decode( $_REQUEST["link_url"] );
          $this->cancel_action( $seq );  
          $this->set_small_window ( "��� �Ϸ�");
          $this->closewin();
          $this->opener_redirect( $link_url );
          exit;
       }
       ////////////////////////////////////////
       // 2.2. Ȯ�� �� 
       // ��� Ȯ�� �� ��� ���θ� ���� �� ��� �ؾ� �� -> ��� ���� CS�� ����
       // ��� �Ұ��� ���� ��ǰ�� 
       else if ( $data[status] >= _trans_no ) 
       {
          /////////////////////////////////////////////////////
          // ���� ��ȣ �Է� �� => ������ ��ǰ
          $this->redirect("?template=E707&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }    
       else
       {
          ///////////////////////////////////////////////////
          // ��� ���θ� Ȯ���ؾ� �ϴ� ����
          $this->redirect("?template=E706&seq=$seq&link_url=" . $_REQUEST["link_url"]  );
          exit;
       }
    }

    //////////////////////////////////////////////////////
    // ���� ����
    function change_cs_result( $redirect_option = 1)
    {
       global $order_seq, $connect, $link_url, $cs_result;

       $transaction = $this->begin( "ó�����º���" );

       $query = "select status, order_cs from orders where seq='$order_seq'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );
      
       //////////////////////////////////////////////////////////////
       // To ó�� �Ϸ�
       // To ó������ �������� �ʴ´� => �����ͺ����� �Ұ���

       if ( $cs_result )
       {
          switch ( $data[order_cs] )
          {
             // ����� ��� ��û
             case _cancel_req_b: 
                $query = "update orders set order_cs = '" . _cancel_com_b ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // ����� ��� ��û
             case _cancel_req_a: 
                $query = "update orders set order_cs = '" . _cancel_com_a ."', refund_date=Now() where seq='$order_seq'";
                mysql_query ( $query );
             break;
             // ����� ��ȯ ��û
             case _change_req_b: 
                $query = "update orders set order_cs = '" . _change_com_b ."' where seq='$order_seq'";
                mysql_query ( $query );
             break;
          }
       }

       if ( $cs_result == 0 )
          $this->end( $transaction, "CS�����߼���"); 
       else
          $this->end( $transaction, "CS�Ϸἳ��"); 

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
    // �κ� ��� ����
    // date: 2005.11.2
    function part_cancel_action( $order_status )
    {
       global $connect, $seq, $qty, $org_qty, $options, $memo;
       
       // �ֹ��� ������ ������ 
       // �� �ֹ��� ������ �������� �ʴ´� - 2005.12.5 jk
       /*
       $change_qty = $org_qty - $qty;
       $query = "update orders set qty='$change_qty', options='" . addslashes($options) . "', memo='" . addslashes($memo) ."' where seq='$seq'";
//echo $query;
//exit;
       mysql_query ( $query, $connect );
       */

       // part_cancel�� ���� �ִ´�.
       $query = "insert part_cancel set order_seq='$seq', cancel_req_date=Now(), order_status='$order_status', status='��ó��', qty='$qty'";
       mysql_query ( $query, $connect );
    }

    ///////////////////////////////////////////////////////
    // ��� ����
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
    // ��ȯ ����
    function change_action( $seq="" )
    {
       global $connect, $order_cs, $status;
       if ( $_REQUEST["seq"] )
         $seq = $_REQUEST["seq"]; 

       $query = "update orders set order_cs=" . $order_cs. " where seq='$seq'";
       mysql_query ( $query, $connect );
    }



    ///////////////////////////////////////////////////////
    // window�� size�� �۰� ����� �ش�.
    function set_small_window( $text )
    {
       //echo "<img src='images/can_link.gif' name=img_main> " . $text;
?>
<style type="text/css">
<!--
.text {
	font-family: "����", "����", Seoul, "�Ѱ�ü";
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
           case _cancel_req_b: // ���� �ֹ����� ���� ����
           case _cancel_req_a: 
           case _change_req_b:
           case _change_reg_a:
              echo "&nbsp;&nbsp; <a href=javascript:set_normal() class=btn3>�����ֹ����� ����</a>";
           break;
        }
    }

    ////////////////////////////////////////////////
    // CS �� ����
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
   // option�� 
   function option_string( $product_id )
   {
      global $connect;

      $query = "select options from products where product_id='$product_id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

?>
      <table border=0 cellpadding=0 cellspacing=0>
         <tr>
            <td width=100 align=center><img src=images/li.gif align=absmiddle> ��ǰ �ɼ�</td>
            <td width=1 bgcolor=cccccc></td>
            <td width=4></td>
            <td width=300><?= nl2br($data[options]) ?></td>
         </tr>
      </table>
<?
   }
   ////////////////////////////////////////////////////////
   // option�� 
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
   // cs�� �ֹ������� update
   // datea : 2005.9.16
   function order_update( $redirect_option=1 )
   {
      global $connect;
 
      $transaction = $this->begin("�ֹ���������");

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
      // �ɼ� ������ ������ ��쿡�� �ɼǰ��� �����Ѵ�.
      if ( $_REQUEST["option_change"] )
          $query .= ",options = '$options'";

      $query .= " where seq= '" . $_REQUEST["seq"] . "'";

      mysql_query ( $query, $connect );

      //$this->jsAlert("���� �Ǿ����ϴ�");
      $transaction = $this->end($transaction);
      if ( $redirect_option )
      {
         $this->redirect ( $_REQUEST["link_url"] );
         exit;
      }
   }

   /////////////////////////////////////////
   // cs�� �ֹ������� update
   // datea : 2005.9.16
   // exchange_option: 1 => �±�ȯ
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
      // �ɼ� ������ ������ ��쿡�� �ɼǰ��� �����Ѵ�.
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
      // seq�� �����´�
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
		$delivery_office = "�ش���� ����";

	$delivery_office = strtoupper( trim( $delivery_office ) );
	switch ( $delivery_office )
	{
		case "�����ù�":
			return "<a href='http://www.ilogen.com/customer/reserve_03_ok.asp?f_slipno={$delivery_no}' target=new>$delivery_office</a>";
		case "CJ�ù�":
		case "CJGLS" :
			return "<a href='http://www.cjgls.com/contents/gls/gls004/gls004_06_01.asp?slipno={$delivery_no}' target=_new>$delivery_office</a>";
			return"<a href='http://www.cjgls.co.kr/kor.html' target=_new>$delivery_office</a>";
		case "�������" :
                        $url = "http://www.doortodoor.co.kr/servlets/cmnChnnel?tc=dtd.cmn.command.c03condiCrg01Cmd&invc_no=";
			return "<a href='" . $url . $delivery_no ."' target=_new>$delivery_office  <img src=images/car.gif border=0 align=absmiddle alt=�ù���ȸ></a>";
		case "�Ｚ�ù�" :
			return "<a href='http://www.samsunghth.com/' target=_new>$delivery_office</a>";
		case "�����ù�" :
		case "�����ù�(����)" :
                        $no1 = substr( $delivery_no, 0,2);
			$no2 = substr( $delivery_no, 2,4);
                        $no3 = substr( $delivery_no, 6,4);
			$url = "http://www.ajulogis.co.kr/common/asp/search_history_proc.asp?sheetno1=" . $no1. "&sheetno2=$no2&sheetno3=$no3";
			return "<a href='$url' target=_new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='�ù���ȸ'></a>";
		case "��ü��" :
		case "������" :
		case "��ü���ù�" :
			return "<a href='http://cp-asw.epost.go.kr:4949/trace/Trace_list.jsp?sid1={$delivery_no}' target=_new>$delivery_office</a>";
		case "�ѱ��ù�" :
			return "<a href='http://dms.ktlogis.com:8080/trace/TraceProduct.jsp' target=_new>$delivery_office</a>";
		case "�����ù�" :
			return "<a href='http://www.hanjinexpress.hanjin.net/customer/plsql/hddcw07.result?wbl_num=".trim($delivery_no)."' target=_new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='�ù���ȸ'></a>";
		case "�����ù�" :
			//return "<a href='http://www.hyundaiexpress.com/hydex/servlet/tracking/cargoSearchResult?InvoiceNumber={$delivery_no}' target=_new>{$delivery_office}</a>";
			return "<a href='http://www.hyundaiexpress.com/hydex/jsp/support/search/re_08.jsp?InvNo={$delivery_no}' target=_new>{$delivery_office}</a>";
			// temporary....the good is not always good. yaplab
			//return "<a href='http://www.hyundaiexpress.com/hydex/html/tracking/tracking_index.htm' target=_new>$delivery_office</a>";
		case "Ʈ���" :
			return "<a href='http://www.etranet.co.kr/new/index.php' target=_new>$delivery_office</a>";
		case "KGB" :
			return "<a href='http://www.kgbls.co.kr/tracing.asp?number={$delivery_no}' target=new>$delivery_office <img src=images/car.gif border=0 align=absmiddle alt='�ù���ȸ'></a>";
		case "KGBƯ���ù�" :
			return "<a href='http://www.ikgb.co.kr/' target=_new>$delivery_office</a>";
		case "�ѹ̸��ù�" :
			if ( strlen( $delivery_no) < 10 )
				return "<a href='http://www.e-family.co.kr/' target=_new>$delivery_office</a>";
			else
				return "<a href='http://www.e-family.co.kr/tracking.jsp?item_no1=". substr( $delivery_no, 0, 4 ) ."&item_no2=". substr( $delivery_no, 4, 4 ) . "&item_no3=". substr( $delivery_no, 8, 4 )."' target=_new>$delivery_office</a>";
		case "��Ŭ����" :
			return "<a href='http://www.ecline.net/tracking/customer02.html#t01' target=_new>$delivery_office</a>";
		case "���ο�ĸ" :
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

	echo "<script>alert('���ڸ޽��� ������ �Ϸ�Ǿ����ϴ�.');</script>";
	echo "<script>self.close();</script>";
	exit;
    }

    /////////////////////////////////////////
    // �κ� ��� ����
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
      $worksheet = $workbook->addWorksheet('�ֹ� ����');

      // download format�� ���� ������ �����´�
      // $result = $this->get_format();

      $download_items = array (
          "order_id"		=> "�ֹ���ȣ",
          "org_price"		=> "�ǸŰ�",
          "product_name"	=> "��ǰ��",
          "options"		=> "�ֹ��ɼ�",
          "qty"			=> "����",
          "shop_name"		=> "�Ǹ�ó",  // �۾� �ʿ�
          "order_name"		=> "�ֹ���",
          "order_tel"		=> "�ֹ��� ����ó",
          "recv_name"		=> "������",
          "recv_tel"		=> "������ ����ó",
          "recv_mobile"		=> "������ ����ó2",
          "recv_zip"		=> "����������ȣ",
          "recv_address"	=> "������ּ�",
          "message"		=> "�ֹ��� �䱸����",
          "collect_date"	=> "������",
          "status"	=> "�ֹ�����",
          "trans_name"		=> "�ù��",
          "trans_no"		=> "�����ȣ"
      ); 

      //////////////////////////////////////////////
      $limit_option = 1;   // ��ü�� ��µ� 0: 20���� ��µ�
      $this->cs_list( &$total_rows, &$result, $limit_option );

      $this->write_excel ( $worksheet, $result, $download_items, $rows );

      // Let's send the file
      $workbook->close();
   }
 
   /////////////////////////////////////////////////////// 
   // excel�� write ��
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
          case "trans_name":// �ù�� �̸�
              return class_E::get_trans_name($data[trans_corp]);
              break;
          default :
              return $data[$key];
      }
   }


}

?>
