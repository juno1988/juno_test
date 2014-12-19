<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_D400
//

class class_D600 extends class_top 
{
   function D600()
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
   function D602()
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
  
   function D601()
   {
      global $template, $link_url, $top_url;

      // seq를 넘기고 택배사 코드와 송장번호를 받는다
      class_D::get_trans_info( $_REQUEST["seq"], &$trans_corp, &$trans_no );

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

      //==============================================================================
      //
      // 합포 count가 2밖에 안될 경우 1개의 송장이 삭제 되면 나머지는 합포 주문이 아님
      // date: 2007.10.29
      //
      $query = "select pack from orders where seq=$seq";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $pack = $data[pack];
      if ( $pack )
      {
      	$query = "select count(*) cnt from orders where pack=$pack";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$cnt = $data[cnt];

	//=======================================
      	// 합포 count가 2개 밖에 안될 경우
	// pack을 모두 null로 변경한다
	if ( $cnt <= 2 )
	{
		$query = "update orders set pack=null where pack=$pack";
		mysql_query ( $query, $connect );
	}
	else
	{
		// 2개 이상일 경우
		// seq 와 pack이 같을 경우 
		// 다른 
		echo "seq: $seq / pack: $pack ";
		if ( $seq == $pack )
		{
			$query = "select seq from orders where seq <> pack and pack=$pack order by seq limit 1";
			$result = mysql_query ( $query, $connect );
			$data = mysql_fetch_array ( $result );
			$sub_pack = $data[seq];

			// pack no를 변경
			$query = "update orders set pack=$sub_pack where pack=$pack";
			$result = mysql_query ( $query, $connect );
		}
	}
      }

      $query = "update orders set trans_corp = null, 
                                  trans_no=null, 
                                  trans_date=null, 
                                  trans_date_pos=null, 
                                  pack=null, 
                                  status='1'
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

      // trans_no에 공백을 삭제 함
      $pattern = "/(\D+)/";
      $replacement = "";
      $trans_no = preg_replace($pattern, $replacement, $trans_no);

      $query = "select status from orders where seq='$seq'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $order_status = $data[status];

      if ( $_SESSION[LOGIN_LEVEL] )
      {
          if ( $order_status != 8 )
          {
              // trans_date 에 now를 입력, status를 6
              $query = "update orders set trans_corp = '$trans_corp', 
                                          trans_no='$trans_no', trans_date=Now(), 
                                          status=" . _trans_no ." where seq = '$seq' 
                                          and status <>" . _trans_confirm;

          }
          else
          {
              // trans_date 에 now를 입력, status를 6
              $query = "update orders set trans_corp = '$trans_corp', trans_no='$trans_no', trans_date=Now(), trans_date_pos=Now() where seq = '$seq'";

          }
      }
      else
      {
          $query = "update orders set trans_corp = '$trans_corp', trans_no='$trans_no', trans_date=Now(), trans_date_pos=Now(), status=" . _trans_confirm . " where seq = '$seq'";
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
