<?
// tempupload table control
// date: 2008.6.23

class class_tempupload extends class_top 
{
    // temp_upload table에 데이터 저장
    // date: 2008.6.23 - jk
    function save( $shop_id, $arr_result, $type='')
    {
	$this->show_wait();

	// upload data init
	class_tempupload::init( $shop_id, $type );
	for ( $i=0; $i < count( $arr_result ); $i++ )
	{
	    $arr_row = $arr_result[$i];

            for ( $j=0; $j<count($arr_row); $j++ )
	    {
		$data = $arr_row[$j];
		class_tempupload::save_data($shop_id, $data, $i, $j, $type);		
	        $this->show_txt( "$i/$j" );
	    }
	}
	$this->hide_wait();
    }

    function init( $shop_id, $type )
    {
	global $connect;
	$query = "delete from upload_temp where shop_id='$shop_id' and type='$type'";
	mysql_query ( $query, $connect );
    }

    function save_data( $shop_id, $data, $row, $col, $type='' )
    {
	global $connect;
	$query = "insert into upload_temp set shop_id='$shop_id', type='$type', col=$col, row=$row, value='$data'";
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



