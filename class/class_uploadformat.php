<?
//================
// class_uploadformat
// date: 2008.1.2
// jk
// table: upload_format
// +----------+--------------+------+-----+---------+-------+
// | Field    | Type         | Null | Key | Default | Extra |
// +----------+--------------+------+-----+---------+-------+
// | template | varchar(30)  |      | PRI |         |       |
// | shop_id  | varchar(7)   |      | PRI |         |       |
// | seq      | int(2)       | YES  |     | NULL    |       |
// | macro    | varchar(100) | YES  |     | NULL    |       |
// +----------+--------------+------+-----+---------+-------+

class class_uploadformat{
    // save
    // data format
    // shop_id, 
    function save( $arr_items )
    {
	$this->reset( $arr_items[0] );

	for ( $i=0; $i < count( $arr_items ); $i++ )
	{
	    $_item = $arr_items[$i];
	    $this->save_item( $_item );
	}	
    }

    // reset -> 정보 삭제
    function reset( $arr_item )
    {
	global $connect;
	$query = "delete from upload_format 
                   where shop_id  = '" . $arr_item['shop_id'] . "' 
                     and template = '" . $arr_item['template'] . "'";

// echo $query;
// exit;
	mysql_query( $query, $connect );
    }

    // upload_format에 저장
    // macro값을 확인해서 저장함
    // <필드명>: 필드에 저장
    // <function명>[macro] : function명 function을 실행
    function save_item( $arr_item )
    {
	global $connect;
	$query = "insert into upload_format
                     set template = '" . $arr_item['template'] . "',
                         shop_id  = '" . $arr_item['shop_id']  . "',
                         seq      = '" . $arr_item['seq']      . "',
                         macro    = '" . $arr_item['macro']    . "'";
	mysql_query( $query, $connect );
    }

    // load
    function load()
    {
	global $connect, $shop_id;

	$query  = "select * from upload_format where shop_id=$shop_id";
	$result = mysql_query( $query, $connect );

	$arr_result = array();
        $count      = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $arr_result[ $data[macro] ] = $data[seq];	
	    $count++;
	}
	$arr_result['count'] = $count;
	return( $arr_result );
    }

}

?>
