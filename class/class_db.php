<?
//==================================
// name: class_user
// date: 2007.10.29
// db 연결 관리
//
// require_once "lib/config.php";

class class_db
{
    //===============================================
    //
    // db  connect
    // _는 내부에서만 사용
    // date: 2007.11.10 - jk.ryu
    //
    function connect( $host, $name, $pass , $db = "")
    {
      $gethostbyname_pimz = array(
        "admin42.ezadmin.co.kr" => "182.162.143.101",  
        "admin43.ezadmin.co.kr" => "182.162.143.105",  
        "admin44.ezadmin.co.kr" => "182.162.143.108",  
        "admin45.ezadmin.co.kr" => "182.162.143.175",
        "admin46.ezadmin.co.kr" => "182.162.143.176"
      );

		//-- localhost
//		if ($host == gethostbyname($_SERVER['SERVER_NAME'])) $host = "localhost";

		if ($host == $gethostbyname_pimz[$_SERVER['SERVER_NAME']]) $host = "localhost";


		if (!$connect) $connect = mysql_connect( $host, $name, $pass );
		if ( $db )
	    	mysql_select_db($db, $connect);
		else
	    	mysql_select_db($name, $connect);

		if (!$connect)
		{
			echo "mysql 데이터베이스에 연결할 수 없습니다.";
			exit;
		}

        $charset="utf8";
    
        mysql_query("set session character_set_connection=${charset};", $connect);
        mysql_query("set session character_set_results=${charset};", $connect);
        mysql_query("set session character_set_client=${charset};", $connect);

		return $connect;
    }


	function get_conn_info($sys_connect, $id)
	{
    	$sql = "select * from sys_domain where id = '$id'";
    	$list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));

    	return $list;
	}

}

?>
