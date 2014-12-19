<?
// class_shop
// shop관련된 class
// date: 2008.8.18
// jk.ryu
/*
class class_shop{
    function get_shop_name( $shop_id )
    {
	global $connect;

	$query = "select shop_name From shopinfo where shop_id=$shop_id";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );
	return $data[shop_name];
    }
    function product_link()
    {
	global $connect, $product_id, $shop_id;
	$shop_code = $shop_id % 100;

	$query = "select * from code_match 
		   where id        = '$product_id' 
		     and shop_id   = '$shop_id'";

	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );

	$is_success = 0;
	// 값이 없을 경우 다른 mall정보도 찾아야 함
	if ( !mysql_num_rows( $result ) )
	{
	    $query = "select * from code_match 
		       where id = '$product_id' 
		       limit 1"; 
	    $result = mysql_query ( $query, $connect );
	    $data   = mysql_fetch_array ( $result );

	    if ( mysql_num_rows( $result ) )
	    {
		$is_success      = 1;
		$shop_product_id = $data[shop_code];
		$shop_code       = $data[shop_id] % 100;
	    }

	}
	$_url = "";
	if ( $is_success )
	{
	    switch ( $shop_code )
	    {
		case 1:	// action
	            $_url = "http://itempage.auction.co.kr/DetailView.aspx?itemno=" . $shop_product_id;
		    break;	
	    }
	}
	else
	{
	    $_url = "http://admin.ezadmin.co.kr/template.htm?template=C202&product_id=" . $shop_product_id;
	}
	
	return $_url;
    }

}
	*/
?>
