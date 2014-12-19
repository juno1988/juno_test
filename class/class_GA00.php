<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_GA00
//

class class_GA00 extends class_top {

    ///////////////////////////////////////////
    function GA00()
    {
	global $connect;
	global $template;

        $transaction = $this->begin("미발주요약표");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    ///////////////////////////////////////////
    function GA02()
    {
	global $connect;
	global $template, $act, $start_date, $end_date, $shop_id;
	$_shop_list = $this->get_promotion_shop();

        include "template/G/GA02.htm";
    }

    ///////////////////////////////////////////
    function GA01()
    {
	global $connect;
	global $template;

	$start_date = date('Y-m-d', strtotime('-5 days'));
	$end_date   = date('Y-m-d', strtotime('today'));

        include "template/G/GA01.htm";
    }


    function get_brand_name( $org_id )
    {
        global $connect;
        $query = "select brand from products where product_id='$org_id'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        return $data[brand];
    }

    function get_phone( $supply_id )
    {
	global $connect;

        $query = "select * from userinfo where code='$supply_id'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return "$data[tel] / $data[mobile]";
    }

    //==================================
    // excel download
    // date: 2007.7.31 - jk
    function download2()
    {
	global $connect, $saveTarget, $download_type;
	global $trans_corp, $trans_format;
	global $start_date, $end_date;


	$query = "select * from orders 
			where status in (1,2,7) 
			and order_cs not in (1,2,3,4,12) 
			and collect_date >= '$start_date' 
			and collect_date <= '$end_date' 
		order by product_id";

	$result = mysql_query ( $query, $connect );
		
	// file open
	$handle = fopen ($saveTarget, "w");

	//====================================================
	//	
	// header 출력 부분 결정
	// date: 2007.7.31 - jk
	//
	$buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=euc-kr'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";

	$buffer .= "<tr>
		<td>상품번호</td>
		<td>발주일</td>
		<td>판매처</td>
		<td>주문번호</td>
		<td>상품명</td>
		<td>옵션</td>
		<td>개수</td>
		<td>공급처</td>
		<td>수령자</td>
		<td>수령자 전화</td>
		<td>수령자 핸드폰</td>
	</tr>\n";
	fwrite($handle, $buffer);

	//===================================================
	//
	// data부분 download
	//
	while ( $data = mysql_fetch_array ( $result ) )
	{
		$buffer = "";

		class_D::get_product_name_option2( $data[product_id], &$_name, &$_option );
		$_supply_name = $this->get_supply_name( $data[product_id] );

		if ( !$_option )
			$_option = $data[options];

		$buffer .= "<tr>
			<td>$data[product_id]</td>
			<td>$data[collect_date]</td>
			<td>$data[shop_id]</td>
			<td style='mso-number-format:\"\@\"'>$data[order_id]</td>
			<td>$_name</td>
			<td>$_option</td>
			<td>$data[qty]</td>
			<td>$_supply_name</td>
			<td>$data[recv_name]</td>
			<td>$data[recv_tel]</td>
			<td>$data[recv_mobile]</td>
		</tr>\n";
		fwrite($handle, $buffer);
	}
	
	// footer 기록
	fwrite($handle, "</table>");
        fclose($handle);

	header("Content-type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=미배송" . date('Ymd') . ".xls");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");

        if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
        } 

        fclose($fp);
	unlink($saveTarget);

    }
}

?>
