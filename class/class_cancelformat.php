<?
// tempupload table control
// date: 2008.6.23

class class_cancelformat extends class_top 
{
    // temp_upload table에 데이터 저장
    // date: 2008.6.23 - jk
    function save()
    {
	global $shop_id, $order_id, $product_id, $qty, $reason, $product_name, $cancel_date, $order_name;
	$shop_code = $shop_id % 100;

	// upload data init
	$this->init( $shop_id );
	
	$this->save_data ( $shop_code, "order_id", $order_id );
	$this->save_data ( $shop_code, "product_id", $product_id);
	$this->save_data ( $shop_code, "qty", $qty);
	$this->save_data ( $shop_code, "reason", $reason);

	$this->save_data ( $shop_code, "product_name", $product_name);
	$this->save_data ( $shop_code, "cancel_date", $cancel_date);
	$this->save_data ( $shop_code, "order_name", $order_name);
    }

    function init( $shop_id )
    {
	global $connect;
	$shop_code = $shop_id % 100;
	$query = "delete from cancel_format where shop_id='$shop_code'";
	echo $query;
	mysql_query ( $query, $connect );
    }

    //////////////////////////////////////////
    // load format
    // date: 2008.6.23 - jk
    function load_format( $nojson = 0 )
    {
	global $connect, $shop_id;
	$shop_code = $shop_id % 100;

	$val = array();
	$val[count] = 0;
	$query = "select * from cancel_format where shop_id='$shop_code'";
	$result = mysql_query ( $query, $connect );

	// num row가 없을 경우 sys_connect를 한다
	if ( !mysql_num_rows( $result ) )
	{
	    $sys_connect = sys_db_connect();

	    $query = "select * from cancel_format where shop_id='$shop_code'";
	    $result = mysql_query ( $query, $sys_connect );
	    $val[server] = "sys_connect / $sys_connect";
	}
	$val[query] = $query;

	$i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $val[$data[id]] = $data[value];
	    $val[count] = $i++;
	}
	if ( !$nojson )
	    echo json_encode ( $val );
	return $val;
    }

    function save_data( $shop_id, $id, $value )
    {
	global $connect;
	$query = "insert into cancel_format set shop_id='$shop_id', id='$id', value='$value'";
	mysql_query ( $query, $connect );
    }

    function get_list( $shop_id, $type )
    {
	global $connect;
	$arr_data[] = array();

       	// json 방식 return
	$query = "select * from upload_temp where shop_id='$shop_id' and type='$type'"; 
	$result = mysql_query ( $query, $connect );

	
    }
}

?>



