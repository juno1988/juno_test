<?
// class_transcorp
// date: 2009.1.7
// jkh
class class_transcorp{

    function get_corp_name( $trans_corp )
    {
		return ($_SESSION['TRANS_CORP_NAME'][$trans_corp] ? $_SESSION['TRANS_CORP_NAME'][$trans_corp] : '');
    }

    function get_corp_name_old( $trans_corp )
    {
        global $sys_connect;
        $sys_connect = sys_db_connect();

        $query = "select trans_corp from sys_transinfo where id='$trans_corp'";
        $result = mysql_query( $query, $sys_connect );
        $data = mysql_fetch_array( $result );
        if( mysql_num_rows($result) > 0 ) 
	        return $data[trans_corp];
		else
			return '';
    }
}
?>
