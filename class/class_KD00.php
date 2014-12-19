<?
//====================================
//
// 배송 요청
// name: class_KD00
// date: 2007.11.9 - jk
//
require_once "class_top.php";
require_once "class_order.php";
require_once "class_3pl.php";
require_once "class_product.php";

class cProduct{
    var $id;
    var $name;
    var $option;
    var $qty;
}

class class_KD00 extends class_top {
    var $m_obj_3pl = "";

    // init class
    function class_KD00()
    {
    }

    function KD00()
    {
	global $template, $start_date, $end_date;
	$link_url = base64_encode( $this->build_link_url() );

	if ( !$start_date )
            $start_date = date("Y-m-d", mktime (0,0,0,date("m")  , date("d")-3, date("Y")));

	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //======================================
    //  
    function combo_warehouse()
    {
    	$obj    = new class_3pl();
	$result = $obj->get_warehouse();

	echo "<select name=warehouse id=warehouse><option value=''>창고선택</option>";

	while ( $data = mysql_fetch_array( $result ) )
	   echo "<option value='$data[warehouse]'>$data[name]</option>";

	echo "</select>";
    }

    function download2()
    {
	$arr_datas = array();	// save 해야할 data저장

	// header지정
	$arr_val = array();
	$arr_val[] = array("상품코드", "상품명", "옵션","창고","로케이션","요청개수" );

	$obj_file = new class_file();
	$val = $this->get_result();

	foreach( $val['list'] as $row )
        {
	    // print_r ( $row );
	    $arr_val[] = array( $row[id], iconv('utf-8', 'cp949', $row[name]), iconv('utf-8', 'cp949', $row[options]), $row[warehouse], $row[location], $row[qty] ); 
	}
	$obj_file->download( $arr_val );
    }

    //======================================
    //  조회
    function query()
    {
	$val = $this->get_result();
	echo json_encode( $val );
    }
 
    // 조회 engine
    function get_result()
    {
	global $start_date, $end_date, $warehouse;

    	$obj         = new class_3pl();
    	$obj_product = new class_product();
	$val         = array();
	$val['list'] = array();

	// 3pl_print_enable의 개수를 찾는다
	$result = $obj->get_printable_products( $warehouse );
	while( $data = mysql_fetch_array($result ))
	{
	    $arr_info    = $obj_product->get_info( $data[product_id], "name,options");
	    $val['list'][] = array( id       => $data[product_id], 
				    qty      => $data[cnt], 
				    name     => iconv('cp949', 'utf-8', $arr_info[name]),
				    location => $data[location],
				    warehouse=> $data[warehouse],
				    options  => iconv('cp949', 'utf-8', $arr_info[options]) );
	}

	// 3pl_orders의 송장 상태 개수를 찾는다
	$result = $obj->get_trans_order( $warehouse );
	while( $data = mysql_fetch_array($result ))
	{
	    $_product_id = $data[product_id];
	    $_cnt        = $data[cnt];

	    $_is_exist   = 0;
	    // 동일한 product_id를 찾는다
	    for( $i=0; $i < count($val['list']); $i++ )
	    {
		if ( $val['list'][$i]['id'] == $_product_id )
		{
	            $val['list'][$i]['qty'] = $val['list'][$i]['qty'] + $_cnt;
	    	    $_is_exist   = 1;
		}
	    }

	    // 동일 product_id가 없는 경우 추가 
	    if ( !$_is_exist )
	    {
	        $arr_info    = $obj_product->get_info( $_product_id, "name,options");
	        $val['list'][] = array( id      => $_product_id, 
					qty     => $_cnt, 
				        location => $data[location],
				        warehouse=> $data[warehouse],
					name    => iconv('cp949', 'utf-8', $arr_info[name]),
					options => iconv('cp949', 'utf-8', $arr_info[options]) );
	    }
	}
	return $val;
    }
}
