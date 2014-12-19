<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_D400
//

class class_GE00 extends class_top 
{
   function saveInfo()
   {
	global $connect, $packTransNo;
	$query = "insert into packing_list set transno_list='$packTransNo'";
	mysql_query ( $query, $connect );
	echo "목록 저장";
   }

   // 정상 상태의 주문의 상태 변경
   function change_status( $audio, $trans_no )
   {
      global $connect;
      $query = "select seq, status, order_cs from orders where trans_no='$trans_no'";
      $result = mysql_query ( $query , $connect );

      while ( $data = mysql_fetch_array ( $result ) )
      {
          switch ( $audio )
          {
              case "0":	 // 정상
                  if ( $data[order_cs] == 13 ) // 배송전 교환 확인
                      $this->change_action( 8,7,$data[seq] );  // to 배송전 교환 완료
                  else
                      $this->change_action( 8,0,$data[seq] );  // to 정상
                  break;
              case "4":  // 배송 전 취소
                  if ( $data[order_cs] == 1 ) // 배송전 취소 요청 
                      $this->change_action( 7,12,$data[seq] ); // to 배송전 취소 확인
                  break;
              case "5":  // 배송 전 교환
                  if ( $data[order_cs] == 5 ) // 배송전 교환 요청 
                      $this->change_action( 7,13,$data[seq] ); // to 배송전 교환 확인
                  break;

          } 
      }
   }

   function change_action( $status, $order_cs, $seq )
   {
       global $connect;

       // midan의 경우 +2일 해줘야 함
       $pos_date = date('Y-m-d h:i:s', strtotime('+2 day'));
       $query = "update orders set status='$status',trans_date_pos='$pos_date'";
       if ( $order_cs )
           $query .= ", order_cs='$order_cs' ";

       $query .= " where seq='$seq'";

       mysql_query ( $query, $connect );
   }

   // 상태를 체크 한 후 처리가능 여부 출력
   // 1: 이상
   // 2: :
   function checkStatus( $trans_no )
   {
      global $connect;
      $query = "select status, order_cs from orders where trans_no='$trans_no'";
      $result = mysql_query ( $query , $connect );

      $ret_audio = 0;
      $cnt = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
          $cnt++;
          switch ( $data[status] )
          {
              case 8:  // 배송 후 
                  $audio = 2; // 이미 확인
                  break;
              case 7:  // 배송 전
                  switch ( $data[order_cs] )
                  {
                      case 1:
                      case 2:
                          $audio = 4; // 취소
                          break;
                      case 11:
                      case 5:
                      case 6:
                      case 7:
                      case 8:
                      case 9:
                      case 10:
                          $audio = 5;
                          break;
                      case 12:  // 취소 확인
                          $audio = 3;
                          break;
                  }
                  break;
          }
          //
          // 상태 체크
          //
          if ( $audio > $ret_audio )
              $ret_audio = $audio;
      }

      // 검색 안될경우 오류
      if ( !$cnt )
          $ret_audio = 99;

      // return해야 할 audio상태를 결정하고 전송 
      if ( $ret_audio > 0 )
      {
          echo $ret_audio;
          return $ret_audio; 
      }

   }

   function querying( $query_date = "")
   {
     global $trans_no, $packTransNo,$connect;

     if ( $query_date )
     {
	echo "<table width=98%><tr><td align=right><a href=javascript:printThis() class=btn1 align=right><img src=images/print_link.gif absalign=middle> Print </a></td></tr></table>   ";
	$query = "select * from packing_list where reg_date='$query_date'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$packTransNo = $data[transno_list];
     }

     // 상태 검증
     // date: 2006.11.29 - jk.ryu
     // 나종훈이면 체크 안 함 
     //if ( $_SESSION[LOGIN_CODE] != 1003 )
     //{

     //===============================================
     // 검증을 마쳤을 경우 상태 변경
     // 조회만 할 경우는 change_staus없음
     // date: 2007.3.22 - jk.ryu
     if ( !$query_date )
     {
	     $audio = $this->checkStatus( $trans_no );
	     $audio = $audio ? $audio : 0;

	     $this->change_status( $audio, $trans_no );

	     if ( $audio > 0 )
	       exit;
     }

     $arrTransNo = array ();
     $arrTransNo = split("\|", $packTransNo);

//echo "list=> $packTransNo <br>";

     $i = 0;
     foreach ( $arrTransNo as $t )
     {
       if ( $i < count($arrTransNo) && $i != 0 )
         $strTransNo .= ",";

       $strTransNo .= $t;
       $i++;
     }     

     $query = "select product_id, options, sum(qty) as sumQty, packed, pack_list, trans_no
                 from orders
                where trans_no in ( $strTransNo )
		and status=8
                group by pack_list, product_id";  

//if ( _DOMAIN_ == "midan" )
//	echo $query;

echo "<table width=600 border=1>";

     $result = mysql_query ( $query, $connect );

     $j = 0;
     while ( $data = mysql_fetch_array ( $result ) )
     {

//echo "trans_no: $data[trans_no] / <br>";

       // header는 1회만 출력
       if ( $j == 0)
       {
         echo"
  <tr>
    <td width=100 class=header2>ID</td>
    <td width=300 class=header2>NAME</td>
    <td width=100 class=header2>OPTION</td>
    <td width=100 class=header2>QTY</td>
  </tr>
";
        }

        //----------------------------------
        //
        // 묶음 상품인 경우
        //
        if ( $data[packed] )
        {

//echo "pack list $data[pack_list] <br>";

          $pack_list = $data[pack_list];
          $list = split(",", $pack_list);

          $i=0;
          foreach ( $list as $id )
          {
               if ( $i != 0 ) $temp .= ",";
                  $temp .= "'$id'";
                  $i++;
          }

          $query_packed = "select product_id, name, options from products where product_id in ( $temp )";
          $temp = "";  // temp 초기화

// echo $query_packed;

          $result_packed = mysql_query ( $query_packed, $connect );
          while ( $data_packed = mysql_fetch_array ( $result_packed ) )
          {

            //$id = $data_packed[org_id] ? $data_packed[org_id]:$data_packed[product_id];
            //class_D::get_product_name_option3($data_packed[product_id], &$name, &$option);

            echo "
  <tr>
    <td width=100 align=center>$data_packed[product_id]</td>
    <td width=300>$data_packed[name]</td>
    <td width=100 align=center>$data_packed[options]</td>
    <td width=100 align=center>$data[sumQty]</td>
  </tr>
";
            $sum = $sum + $data[sumQty];
            $j++;
          }
        }
        else
        {
        //----------------------------------
        //
        // 묶음 상품이 아닌 경우        
        //
        $id = $data[org_id] ? $data[org_id]:$data[product_id];

	if ( _DOMAIN_ == "midan" || _DOMAIN_ == "sccompany" || _DOMAIN_ == "piona" || _DOMAIN_ == "mambo74" )
       		class_D::get_product_name_option2($id, &$name, &$option);
	else
        	class_D::get_product_name_option3($id, &$name, &$option);

        echo "
  <tr>
    <td width=100 align=center>$id</td>
    <td width=300>$name</td>
    <td width=100 align=center>$option</td>
    <td width=100 align=center>$data[sumQty]</td>
  </tr>
";
        $sum = $sum + $data[sumQty];
        $j++;
        }
      }

    // 조회 개수가 없을 경우
    if ( $j == 0 )
    {

         echo"
  <tr>
    <td width=100 class=header2>ID</td>
    <td width=300 class=header2>NAME</td>
    <td width=100 class=header2>OPTION</td>
    <td width=100 class=header2>QTY</td>
  </tr>
  <tr>
    <td colspan=4 align=center header=30>조회 이상</td>
  </tr>
";

    }
    echo "
  <tr>
    <td colspan=3 align=right header=30>Total: &nbsp;</td>
    <td align=center> $sum </td>
  </tr></table>";

   }

   function GE02()
   {
      global $template, $start_date, $end_date, $top_url, $keyword;
      global $connect;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-7 day'));

      $query = "select DATE_FORMAT(reg_date,'%Y %H:%i:%s') as freg_date, reg_date  from packing_list where reg_date > '$start_date 00:00:00' and reg_date < '$end_date 23:59:59' order by reg_date desc";
      $result = mysql_query ( $query, $connect );

      echo "<script>hide_waiting()</script>";

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function GE00()
   {
      global $template, $start_date, $end_date, $top_url, $keyword;
      $line_per_page = _line_per_page;
      if (!$start_date) $start_date = date('Y-m-d', strtotime('-7 day'));

      $link_url = "?" . $this->build_link_url();

      $result = class_D::get_order_list( &$total_rows, 0, "collect_date" ); 

      echo "<script>hide_waiting()</script>";

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   /////////////////////////////////////
   // 하부 vendor을 위한 개별 송장 입력
   /*
   function GE02()
   {
      global $template, $start_date, $end_date;
      $line_per_page = _line_per_page;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-7 day'));

      $link_url = "?" . $this->build_link_url();
      $result = class_D::get_order_list( &$total_rows, 0, "collect_date" ); 

      echo "<script>hide_waiting()</script>";

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   */
 
   function GE01()
   {
      global $template, $link_url, $top_url, $query_date;

      if ( $query_date )
      {
         $this->querying( $query_date );
      }
      else
      {
         // seq를 넘기고 택배사 코드와 송장번호를 받는다
         class_D::get_trans_info( $_REQUEST["seq"], &$trans_corp, &$trans_no );
      }

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function del_transinfo()
   {
      global $connect, $link_url, $top_url;
      $transaction = $this->begin("송장삭제");

      $trans_corp = $_REQUEST["trans_corp"];
      $trans_no = $_REQUEST["trans_no"];
      $seq = $_REQUEST["seq"];

      $query = "update orders set trans_corp = null, 
                                  trans_no=null, 
                                  trans_date=null, 
                                  trans_date_pos=null, 
                                  pack=null, 
                                  status='1', 
                                  collect_date=Now()
                       where seq = '$seq'";

      mysql_query ( $query , $connect );
      $this->end( $transaction );

      global $top_url;
      $this->opener_redirect ( "template.htm" . base64_decode($link_url) . "&top_url=" . $top_url );
      $this->closewin( );
      exit;

   }


   function insert()
   {
      global $connect, $link_url, $top_url;
      $transaction = $this->begin("송장수정");

      $trans_corp = $_REQUEST["trans_corp"];
      $trans_no = $_REQUEST["trans_no"];
      $seq = $_REQUEST["seq"];

      $query = "select status from orders where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $order_status = $data[status];

      // midan의 경우 +2일 해줘야 함
      // 2007.9.11 dnshop의 경우 +1로 배송을 등록하면 오류 발생
      // 다시 + 2일로 변경함
      $pos_date = date('Y-m-d h:i:s', strtotime('+2 day'));

      if ( $_SESSION[LOGIN_LEVEL] )
      {
          if ( $order_status != 8 )
          {
              // trans_date 에 now를 입력, status를 6
              // 
              $query = "update orders set trans_corp = '$trans_corp', 
                                          trans_no='$trans_no', trans_date=Now(), trans_date_pos='$pos_date',
                                          status=" . _trans_no ." where seq = '$seq' 
                                          and status <>" . _trans_confirm;

          }
          else
          {
              // trans_date 에 now를 입력, status를 6
              $query = "update orders set trans_corp = '$trans_corp', trans_no='$trans_no', trans_date=Now(), trans_date_pos='$pos_date' where seq = '$seq'";

          }
      }
      else
      {
          $query = "update orders set trans_corp = '$trans_corp', trans_no='$trans_no', trans_date=Now(), trans_date_pos='$pos_date', status=" . _trans_confirm . " where seq = '$seq'";
      }

      mysql_query ( $query , $connect );
      $this->end( $transaction );
//echo $query;
//exit;
      global $top_url;
      $this->opener_redirect ( "template.htm" . base64_decode($link_url) . "&top_url=" . $top_url );
      $this->closewin( );
      exit;

   }

}

?>
