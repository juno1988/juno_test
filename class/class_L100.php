<?
require_once "class_top.php";
require_once "class_product.php";

class class_L100 extends class_top
{
    function class_L100()
    {
    }

    function L100()
    {
	global $connect, $sys_connect;
	// promotion shopid를 가져와야 함.
	$_shop_list = $this->get_promotion_shop();

	include "template/L/L100.htm";
    }

    // 판매처 관리
    function L101()
    {

	include "template/L/L101.htm";
    }

    // 상품 관리
    function L102()
    {

	include "template/L/L102.htm";
    }

    // 주문 관리
    function L103()
    {

	include "template/L/L103.htm";
    }



    //////////////////////////////////////
    // shopnmae
    // 2008.7.8 - jk
    //
    function get_shop_list()
    {
	global $connect;
	$val = array();

	// promotor는 loginid가 promotion_id임
	$query  = "select * from shopinfo where promotion_id='$_SESSION[LOGIN_ID]'";
	$result = mysql_query( $query, $connect );	

	$val['cnt']    = mysql_num_rows($result);
	$val['query']  = $query; 
	$val['list']   = array();

	$i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $val['list'][] = array( 
				'shop_id'  	=> $data[shop_id] , 
				'shop_name'	=> iconv('cp949', 'utf-8', $data[shop_name] ),
				'userid'	=> $data[userid],
				'password'	=> $data[passwd]
			);
	    $i++;
	}
	echo json_encode( $val );
    }

    ///////////////////////////////////
    // 상품 리스트  - jk
    function get_product_list()
    {
	global $product_name;
	$obj = new class_product();

	// 조건 설정
	$arr_items = array( 
		'product_id' => '',
		'org_id'     => 'null',
		'options'    => 'like',
		'name'       => 'like' );

	$is_utf8 = 1;
	$cnt = $obj->get_count( $arr_items, $is_utf8 );	

	$val = array();
	// promotor는 loginid가 promotion_id임
	$val['cnt']    = $cnt;
	$val['list']   = array();

	$result = $obj->get_list( $arr_items , $is_utf8);	
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $val['list'][] = array( 
				'product_id'  	=> $data[product_id], 
				'name'		=> iconv( 'cp949', 'utf-8', $data[name] ),
				'regdate'	=> $data[regdate],
				'supply_price'	=> $data[supply_price] 
			);
	}

	echo json_encode( $val );
    }
}

?>
