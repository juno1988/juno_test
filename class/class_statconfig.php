<?
//
// 정산 환경 설정
// 2009.7.3 - jk
//

class class_statconfig {
    ///////////////////////////////////////////

	var $m_arr_config = array();
    function class_statconfig()
    {
		global $connect, $query_type;

		// set default config
		$this->m_arr_config['change']          = "new";
		$this->m_arr_config['cancel']          = $query_type;
		$this->m_arr_config['usertrans_price'] = "2000";

		// class생성과 동시에 rule정보를 읽어온다
		$query  = "select * from stat_config";

		$result = mysql_query( $query, $connect );
		while ( $data = mysql_fetch_assoc( $result ) )
		{
			$this->m_arr_config[ $data[code] ] = $data[value];
		}
	}

	// 환경 값을 return
	function get_config()
	{
		return $this->m_arr_config;
	}

	function save_config( $code, $value )
	{
		global $connect;

		$query = "select * from stat_config where code='$code'";
		$result = mysql_query( $query, $connect );
		$num    = mysql_num_rows( $result );

		if ( $num )
			$query = "update stat_config set value='$value' where code='$code'";
		else
			$query = "insert into stat_config set value='$value', code='$code'";

		mysql_query( $query, $connect );
	}
}
?>
