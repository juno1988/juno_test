<?
require_once "Request.php";

class class_3pl_api
{
    var $m_req;
    var $m_config;

    function class_3pl_api()
    {
	global $connect;
	$sql = "select * from ez_config";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $this->m_config = mysql_fetch_array($result);

        $req = &new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->setURL('http://www.ez3pl.co.kr/api.htm');
	$req->addPostData( "db_host" , $this->m_config[dbserver_3pl] );
	$req->addPostData( "db_name" , $this->m_config[dbname_3pl] );
	$req->addPostData( "db_pwd" ,  $this->m_config[dbpass_3pl] );
	$this->m_req = $req;
    }

    // test 성공
    // http://scm.ezadmin.co.kr/function.htm?template=3pl_api&action=test
    function test()
    {
	global $product_id;

	$req = $this->m_req;
        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_current_stock" );
	$req->sendRequest();
        $response1 = $req->getResponseBody();
	echo "<pre>";
	echo $response1;
	echo "</pre>";
    }

    /////////////////////////////////////////////
    //
    // 상품의 현재 재고 값을 가져온다
    // 2008.1.2 - jk
    //
    function get_current_stock()
    {
	global $product_id;

	$req        = $this->m_req;
	$product_id = $product_id ? $product_id : "8B38T131DGZ67";
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";

        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_current_stock" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();
        echo $req->getResponseBody();
	exit;	
    }

    /////////////////////////////////////////////
    //
    // batch에서 재고 가져오기
    // 2009.3.20 - jk
    //
    function batch_current_stock3( $domain,$product_id )
    {
	$req        = $this->m_req;

        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_current_stock3" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
        //return $_str;
    }

    /////////////////////////////////////////////
    //
    // 상품의 현재 재고 값을 가져온다
    // 2008.1.2 - jk
    //
    function get_current_stock3( $product_id )
    {
	$req        = $this->m_req;
	$product_id = $product_id ? $product_id : "8B38T131DGZ67";
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";

        // action은 class_api의 method를 call한다
	// $req->addPostData( "action" ,  "get_current_stock3" );
	$req->addPostData( "action" ,  "get_current_stock3" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
        //return $_str;
    }


    /////////////////////////////////////////////
    //
    // 상품의 현재 재고 값을 가져온다
    // 2008.1.2 - jk
    //
    function get_current_stock2( $product_id )
    {
	$req        = $this->m_req;
	$product_id = $product_id ? $product_id : "8B38T131DGZ67";
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";

        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_current_stock2" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
        //return $_str;
    }

    function get_location_stock_tot($product_id , $warehouse='')
    {
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";
	$req        = $this->m_req;

        // action은 class_api의 method를 call한다
	$req->addPostData( "action",    "get_location_stock_tot" );
	$req->addPostData( "domain",     $domain );
	$req->addPostData( "product_id", $product_id );
	$req->addPostData( "warehouse",  $warehouse);
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
    }    


    function get_location_stock($product_id , $warehouse='')
    {
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";
	$req        = $this->m_req;

        // action은 class_api의 method를 call한다
	$req->addPostData( "action",    "get_location_stock" );
	$req->addPostData( "domain",     $domain );
	$req->addPostData( "product_id", $product_id );
	$req->addPostData( "warehouse",  $warehouse);
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
    }    

    /////////////////////////////////////////////
    //
    // 배송예정 값을 가져온다
    // 2008.7.24 - jk
    //
    function get_reg_deliv( $product_id )
    {
	$req        = $this->m_req;
	$product_id = $product_id ? $product_id : "8B38T131DGZ67";
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";

        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_reg_deliv" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();

	$_str = $req->getResponseBody();
	return $req->getResponseBody();
        //return $_str;
    }


    /////////////////////////////////////////////
    //
    // 배송예정 값을 가져온다
    // 2008.7.24 - jk
    //
    function get_notyet_qty( $product_id )
    {
	$req        = $this->m_req;
	$product_id = $product_id ? $product_id : "8B38T131DGZ67";
	$domain     = _DOMAIN_ ? _DOMAIN_ : "ckcompany";

        // action은 class_api의 method를 call한다
	$req->addPostData( "action" ,  "get_notyet_qty" );
	$req->addPostData( "domain" ,  $domain );
	$req->addPostData( "product_id" ,  $product_id );
	$req->sendRequest();

	$_str = $req->getResponseBody();

	return $req->getResponseBody();
        //return $_str;
    }


}

?>
