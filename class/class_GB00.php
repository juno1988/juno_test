<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_GB00
//

class info{
    var $trans_date;
    var $cnt = array( "����"=> 0,
                      "����"=> 0 );       // ���� , ���� 
}

class class_GB00 extends class_top {

    ///////////////////////////////////////////

    function GB00()
    {
	global $connect;
	global $template, $line_per_page, $act, $start_date, $end_date, $status, $date_type;

        echo "<script>show_waiting()</script>";
        flush();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-20 day'));

        // data ��������
        $infos = $this->get_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting()</script>";
        flush();

    }

    // list ��������
    function get_list()
    {
        global $connect, $start_date, $end_date, $infos, $trans_corp, $shop_id, $supply_id, $status, $date_type;

	$date_type = $date_type ? $date_type : "trans_date_pos";

        $query = "select count(*) cnt, DATE_FORMAT( $date_type ,'%Y-%m-%d') as trans_date , trans_who ";

	$option = " from orders 
                   where $date_type >= '$start_date 00:00:00' 
                     and $date_type <= '$end_date 23:59:59'";

	if ( $trans_corp != 99 )
	        $option .= " and trans_corp='$trans_corp'";

	if ( $shop_id )
		$option .= " and shop_id='$shop_id' ";

	if ( $supply_id )
		$option .= " and supply_id='$supply_id' ";

	switch ( $status )
	{
		case "7": // �����
			$option .= " and status <> 8 and order_cs not in (1,2,3,4,12) ";
			break;
		case "8": // ��� ��
			$option .= " and status=8 ";
			break;
	}

	//$query .= " and order_cs not in (12,13)";

       $query .= $option . " group by trans_no, DATE_FORMAT($date_type,'%Y-%m-%d'), trans_who order by $date_type desc";

//if ( $_SESSION[LOGIN_LEVEL] == 9 )
//    echo $query;

        $result = mysql_query ( $query, $connect );

        while ( $data = mysql_fetch_array ( $result ) )
        {
            $infos[$data[trans_date]][$data[trans_who]] = $infos[$data[trans_date]][$data[trans_who]] + 1;

            if ( $data[trans_who] == "����" )
                $infos["����"] = $infos["����"] + 1;
            else
                $infos["����"] = $infos["����"] + 1;
        }

	// ���� , �Ϲ� ����
	// 1. ����
	$query = "select count(*) cnt, DATE_FORMAT( $date_type ,'%Y-%m-%d') as trans_date";
	$query .= $option . " and seq = pack group by DATE_FORMAT($date_type,'%Y-%m-%d') order by $date_type desc";

	$result = mysql_query( $query, $connect );

	while ( $data   = mysql_fetch_array( $result ) )
	    $infos[$data[trans_date]]['����'] = $data['cnt'];	

	// 2. ����
	$query = "select count(*) cnt, DATE_FORMAT( $date_type ,'%Y-%m-%d') as trans_date, packed";
	$query .= $option . " and (pack is null or pack = '' ) group by DATE_FORMAT($date_type,'%Y-%m-%d'), packed order by $date_type desc";

	$result = mysql_query( $query, $connect );

	$tot = 0;
	while ( $data   = mysql_fetch_array( $result ) )
	{
	    if ( $data[packed] )
	        $infos[$data[trans_date]]['packed'] = $data[cnt];	
	    else
	        $infos[$data[trans_date]]['����'] = $infos[$data[trans_date]]['����'] + $data[cnt];	
	}

        return $infos;
    }

    //------------------------------------------------
    // ���� ��ȣ download
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

      	if ( $trans_who != "���" )
        	$query .= " and trans_who='$trans_who'";

        if ( $trans_corp != 99 )
                $query .= " and trans_corp='$trans_corp'";

        if ( $shop_id )
                $query .= " and shop_id='$shop_id' ";

        if ( $supply_id )
                $query .= " and supply_id='$supply_id' ";

	switch ( $status )
	{
		case "7": // �����
			$query .= " and status <> 8 ";
			break;
		case "8": // ��� ��
			$query .= " and status=8 ";
			break;
	}

	//$query .= " and order_cs not in (12,13) ";
       $query .= " group by trans_no, DATE_FORMAT($date_type,'%Y-%m-%d'), trans_who order by $date_type desc";     

//================================== 

      $result = mysql_query ( $query, $connect );

      /////////////////////////////////////
      //
      // file ����
      // 
      fwrite($handle, "<table><tr><td>��ۺ�</td></td>�����ȣ</td><td>�Ǹ�ó</td></tr>");
      while ( $data = mysql_fetch_array ( $result ) )
      {
          $buffer = "<tr><td>$data[trans_who]</td><td>$data[trans_no]</td><td> " .class_C::get_shop_name($data[shop_id]) . "</td></tr>";
	  fwrite($handle, $buffer);
	  $buffer = "";
      }
      fwrite($handle, "</table>");

      //////////////////////////////////////
      // 
      // ���� ��ȯ�� �ؾ� �� ��� ���⼭ �ؾ� ��
      //
      $saveTarget2 = $saveTarget . "_";
      $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      exec( $run_module ); 
     
      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=����($trans_who).xls");
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
