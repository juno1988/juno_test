<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_GI00
//

class info{
    var $trans_date;
    var $cnt = array( "선불"=> 0,
                      "착불"=> 0 );       // 선불 , 착불 
}

class class_GI00 extends class_top {

    ///////////////////////////////////////////

    function GI00()
    {
	global $connect;
	global $template, $line_per_page, $act, $start_date, $end_date, $status, $date_type, $_type, $_string;

        echo "<script>show_waiting()</script>";
        flush();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-7 day'));

        // data 가져오기
        $infos = $this->get_list();

        $master_code = substr( $template, 0,1);
        include "template/G/GI00.htm";

        echo "<script>hide_waiting()</script>";
        flush();

    }

    // list 가져오기
    function get_list()
    {
        global $connect, $start_date, $end_date, $infos, $trans_corp, $shop_id, $supply_id, $status, $_type, $_string;

	$date_type = $date_type ? $date_type : "trans_date_pos";

	$query_cnt = "select count(*) cnt ";

        $query = "select count(*) cnt, orders.product_id,packed, pack_list, orders.qty";
	$option = " from orders, order_takeback 
                   where orders.seq = order_takeback.order_seq
		     and order_takeback.complete_date >= '$start_date 00:00:00' 
                     and order_takeback.complete_date <= '$end_date 23:59:59'";

	if ( $trans_corp != 99 )
	        $option .= " and order_takeback.trans_corp='$trans_corp'";

	if ( $shop_id )
		$option .= " and orders.shop_id='$shop_id'";

	if ( $supply_id )
		$option .= " and orders.supply_id='$supply_id' ";

	if ( $_string )
	{
	    $_type = $_type ? $_type : "product_name";
	    $option .= " and orders." . $_type. "='$_string' ";
	}

        $option .= " group by DATE_FORMAT(order_takeback.complete_date,'%Y-%m-%d') order by orders.product_id";

//echo $query. $option;


        $result = mysql_query ( $query . $option, $connect );
	$arr_result = array();
	while ( $data = mysql_fetch_array( $result ) )
	{
	    if ( $data[packed] )
	    {
		$_arr_products = split(",", $data[pack_list] );
		foreach ( $_arr_products as $_product_id)
		{
	            $arr_result["list"][$_product_id] = $arr_result[$product_id] + $data[cnt];
		}
	    }
	    else
	        $arr_result["list"][$data[product_id]] = $arr_result[$data[product_id]] + $data[cnt];

	     $arr_result["total"] = $arr_result[total] + $data[cnt];
	}

        return $arr_result;
    }

    //------------------------------------------------
    // 송장 번호 download
    //
    function download()
    {
      	global $connect, $saveTarget, $filename, $trans_who, $trans_date, $trans_corp, $start_date, $end_date;
	global $infos, $trans_corp, $shop_id, $supply_id, $date_type,$status;

      if ( $trans_date == "all" )
      {
        $start_date = $start_date;
        $end_date = $end_date;
      }
      else
      {
        $start_date = $trans_date;
        $end_date = $trans_date;
      }		

      $handle = fopen ($saveTarget, "w");
    
//======================================
      
        $query = "select count(*) cnt, DATE_FORMAT($date_type,'%Y-%m-%d') as trans_date , trans_who, trans_no, shop_id,trans_date_pos,status,order_cs 
                    from orders 
                   where $date_type >= '$start_date 00:00:00' 
                     and $date_type <= '$end_date 23:59:50'";

      	if ( $trans_who != "모두" )
        	$query .= " and trans_who='$trans_who'";

        if ( $trans_corp != 99 )
                $query .= " and trans_corp='$trans_corp'";

        if ( $shop_id )
                $query .= " and shop_id='$shop_id' ";

        if ( $supply_id )
                $query .= " and supply_id='$supply_id' ";

	switch ( $status )
	{
		case "7": // 배송전
			$query .= " and status <> 8 ";
			break;
		case "8": // 배송 후
			$query .= " and status=8 ";
			break;
	}

	//$query .= " and order_cs not in (12,13) ";
       $query .= " group by trans_no, DATE_FORMAT($date_type,'%Y-%m-%d'), trans_who order by $date_type desc";     

//================================== 

      $result = mysql_query ( $query, $connect );

      /////////////////////////////////////
      //
      // file 생성
      // 
      fwrite($handle, "<table><tr><td>배송비</td></td>송장번호</td><td>판매처</td></tr>");
      while ( $data = mysql_fetch_array ( $result ) )
      {
          $buffer = "<tr><td>$data[trans_who]</td><td>$data[trans_no]</td><td> " .class_C::get_shop_name($data[shop_id]) . "</td></tr>";
	  fwrite($handle, $buffer);
	  $buffer = "";
      }
      fwrite($handle, "</table>");

      //////////////////////////////////////
      // 
      // 파일 변환을 해야 할 경우 여기서 해야 함
      //
      $saveTarget2 = $saveTarget . "_";
      $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      exec( $run_module ); 
     
      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=송장($trans_who).xls");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
      header("Pragma: public");

      if (is_file($saveTarget2)) { 
          $fp = fopen($saveTarget2, "r");   
          fpassthru($fp);  
      } 

      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);

      unlink($saveTarget);
      unlink($saveTarget2);

exit;


    }
}

?>
