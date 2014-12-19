<?
//=============================================
//
// name: 배송대행 Engine
// class_global.php
// first build date: 2007.3.7 jk.ryu
// 
// History

class class_global
{
  var $g_connect;
  function class_global()
  {
	global $global_domain;
	$this->g_connect = $this->connect( $global_domain );
  }
  //===================================
  //
  // global 배송 업체의 db와 연결
  // date: 2007.3.8 - jk
  //
  function connect( $domain )
  {
	$sys_connect = sys_db_connect();

	// db 접속 정보를 구한다.
	$query = "select * from sys_domain where id='$domain'";

	$result = mysql_query ( $query, $sys_connect );
	$data = mysql_fetch_array ( $result );

	$MYSQL_HOST = $data[host];
	$MYSQL_ID = $data[db_id]; 
	$MYSQL_PASSWD = $data[db_pwd];
	$MYSQL_DB = $data[db_name];

	$this->g_connect = mysql_connect($MYSQL_HOST, $MYSQL_ID, $MYSQL_PASSWD);
	mysql_select_db($MYSQL_DB, $this->g_connect);

	if (!$this->g_connect)
	{
		echo "mysql 데이터베이스에 연결할 수 없습니다.";
		exit;
	}

	return $this->g_connect;
  }

  //===================================
  //
  // global 배송 업체의 상품 조회 
  // date: 2007.3.8 - jk
  //
  function get_list()
  {
	global $global;
	// $connect = $this->connect( $global_domain );

	$query = "select * from products order by product_id desc";
	$result = mysql_query ( $query, $this->g_connect );
	
	return $result;
  }

  //===================================
  //
  // global 배송 업체의 상품 조회 
  // date: 2007.3.8 - jk
  //
  function get_detail( $product_id )
  {
	$query = "select * from products where product_id=$product_id";
	$result = mysql_query ( $query, $this->g_connect );
	
	return $result;
  }


}

?>
