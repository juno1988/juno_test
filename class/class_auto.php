<?
require_once "class_top.php";
/****************************
* 자동화를 위한 class
* date: 2009.3.28 - jk
* reg    : 등록
* update : 갱신
* get    : 조회
* commit : 완료
****************************/

class class_auto extends class_top {
    //************************
    // 등록
    function reg( $product_id, $shop_id , $status=0, $action)
    {
	global $connect;

	// 
	$query = "insert into auto_transaction 
                     set product_id = '$product_id'
                         , shop_id  = '$shop_id'
		         , regdate  = Now()
		         , action   = '$action'
			 , status   = $status";

	mysql_query( $query, $connect );
    }

    //************************
    // 수정 
    function modify( $product_id )
    {
	global $connect;

	$query = "update auto_transaction set status=0 
                   where product_id='$product_id'
                     and action='sync'";
	mysql_query( $query, $connect );
    }

    //*******************************
    // 등록된 상품의 상태 변경
    // 2009.4.22 - jk
    //*******************************
    function change_enable_sale()
    {
	global $connect,$seq;
	$query = "select * from auto_product_reg where seq=$seq";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_assoc( $result );

	if ( $data[enable_sale] )
	    $_enable_sale = 0;
	else
	    $_enable_sale = 1;

	$data[enable_sale] = $_enable_sale;

	$query = "update auto_product_reg set enable_sale='$_enable_sale' where seq=$seq";
	mysql_query($query, $connect);

	// 내용 수정 표시
	$this->modify($data[product_id]);
	echo json_encode( $data );	
    }
    
    

    //*******************************
    // 상품별 command 추가
    // 2009.4.16 - jk
    //*******************************
    function add_product_command( $product_id, $arr_command )
    {
	global $connect;
	$query = "delete from product_command_group where product_id='$product_id'";
	mysql_query( $query, $connect );

	foreach( $arr_command as $command )
	{
	    $query = "insert into product_command_group 
		         set product_id='$product_id'
			     ,command_id = $command";
	    mysql_query( $query, $connect );
	}

        /*************************************
        * auto_transaction 추가
        * 2009.4.17 - jk
        *************************************/
	$arr = $this->get_command_list( $product_id );
	foreach ( $arr as $a )
	{
	    $this->put_auto_transaction( $a );
	}	
    }

    //-----------------------------------------
    //
    //
    function disp_auto_shop_list()
    {
	global $connect, $product_id;

	$query = "select * from auto_product_reg where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
	$_arr   = array();	
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $_arr[] = $data;
	}
	echo json_encode( $_arr );
    }

    //*******************************
    // 상품별 command 리스트 array
    // 2009.4.17 - jk
    //*******************************
    function disp_transaction_list()
    {
	global $connect, $product_id;
	$query = "select * from auto_transaction where product_id='$product_id'";
	$result = mysql_query( $query, $connect );

	$_arr = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $_arr[] = $data;
	}
	echo json_encode( $_arr );
    }

    //*******************************
    // 상품별 command 리스트 array
    // 2009.4.17 - jk
    //*******************************
    function put_auto_transaction( $data )
    {
	global $connect;

	$query = "delete from auto_transaction where product_id='$data[product_id]'";
	mysql_query( $query, $connect );

	$arr_command = split(",", $data[command] );
	$arr_shopid  = split(",", $data[shop_list] );

	foreach ( $arr_command as $command )
	{
	    foreach ( $arr_shopid as $shop_id )
	    {
		$query = "insert into auto_transaction 
                             set product_id = '$data[product_id]'
                                 ,action    = '$command'
                                 ,shop_id   = '$shop_id'
                                 ,regdate   = Now()
                                 ,status    = 0";

		mysql_query ( $query , $connect );
	    }
	}
    }

    //*******************************
    // 상품별 command 리스트 array
    // 2009.4.17 - jk
    //*******************************
    function get_command_list( $product_id )
    {
	global $connect; 
	
	$query = "select * 
                    from product_command_group a, auto_command_group b
                   where a.command_id = b.seq
                     and a.product_id ='$product_id'";
	$result = mysql_query( $query, $connect );

	$_arr = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $data[name] = iconv('cp949','utf-8', $data[name] );
	    $_arr[] = $data;
	}
	return $_arr;
    }

    //*******************************
    // 상품별 command 리스트
    // 2009.4.17 - jk
    //*******************************
    function disp_command_list()
    {
	global $product_id;
	$_arr = $this->get_command_list( $product_id );
	echo json_encode( $_arr );
    }
    
     
}
?>
