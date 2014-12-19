<?
/*
*
* date: 2012.3.6 - jkryu
* desc: cafe24 관련된 class
*
*/

class class_cafe24
{
    var $m_connect;
    
    function class_cafe24( $connect )
    {
        $this->m_connect = $connect;   
    }
    
    function stock_sync( $product_id, $shop_id, $stock )
    {
        list( $shop_id, $auth_code ) = $this->get_info( $shop_id );
        
        $link_id = "";
        $file_name = $this->get_pinfo( $product_id, $shop_id, $auth_code, $stock, &$link_id );
        
        /*
		$url  = "http://datahub.echosting.cafe24.com/Api/?
		*/
		
		$param = "service_type=ezadmin&data_type=xml&mall_id=$shop_id&auth_code=$auth_code&command=SetStock&mode=total&data_url=http://" . $_SERVER['HTTP_HOST'] . "/tmp/" . $file_name;
    	
    	debug( $url );
    	
    	/*
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch); 
        */
        
        // 오류 발생해서 수정함
        require_once "HTTP/Request.php";
        $req = new HTTP_Request("http://datahub.echosting.cafe24.com/Api/");
        $req->setMethod("get");
        $req->addRawQueryString( $param );
        
        
        debug( "get_url: " . $req->getUrl() );
        
        /*
        $req->setMethod(HTTP_REQUEST_METHOD_POST);
        $req->addPostData("service_type", "ezadmin");
        $req->addPostData("data_type"   , "xml");
        $req->addPostData("mall_id"     , $shop_id);
        $req->addPostData("auth_code"   , $auth_code );
        $req->addPostData("command"     , "SetStock");
        $req->addPostData("mode"        , "total");
        $req->addPostData("data_url"    , "http://" . $_SERVER['HTTP_HOST'] . "/tmp/" . $file_name );
        */
        
        if (!PEAR::isError($req->sendRequest())) 
        {
             $output = $req->getResponseBody();
        } else {
             $output = "";
        }
        
        debug( "output:" . $output );
        
        $query = "insert into stock_sync_log 
                     set product_id = '$product_id'
                        ,crdate     = Now()
                        ,shop_id    = '$shop_id'
                        ,link_id    = '$link_id'
                        ,stock      = '$stock'
                        ,worker     = '" . $_SESSION[LOGIN_NAME] . "'
                        ,result     = '$output'";
                        
        //debug( $query );                        
        mysql_query( $query, $this->m_connect );                        
        
		echo $output;
    }

	function get_file_name( $product_id, $shop_id, $stock )
	{
        list( $shop_id, $auth_code ) = $this->get_info( $shop_id );
        
        $link_id = "";
        $file_name = $this->get_pinfo( $product_id, $shop_id, $auth_code, $stock, &$link_id );

		return $file_name;
	}

    // 상품 정보로 xml file을 만들고 그 full path를 return한다.
    function get_pinfo( $product_id, $shop_id, $auth_code, $stock, &$link_id )
    {
        $query    = "select link_id from products where product_id='$product_id'";
        $result   = mysql_query( $query, $this->m_connect );
        $data     = mysql_fetch_assoc( $result );        
        $arr_data = split("-", $data[link_id] );
        $link_id = $data[link_id];
        
        $prd_code  = $arr_data[0];
        
        // 단품일 경우엔 prd_code가 0-0-0 이라고 함.. 김철팀장 답변 2012.3.6
        if ( count( $arr_data) > 3 )
            $item_code = $arr_data[1] . "-" . $arr_data[2] . "-" . $arr_data[3];
        // 솔업인 경우 xxxx-xxx 형식 2014.11.18 - jkryu        
        else if ( count( $arr_data ) == 2 )
            $item_code = $arr_data[1];
        else
            $item_code = "0-0-0";
        
        $output = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $output .= "<product>\n";
        $output .= "    <service_type>ezadmin</service_type>\n";
        $output .= "    <data_type>xml</data_type>\n";
        $output .= "    <mall_id>$shop_id</mall_id>\n";
        $output .= "    <auth_code>$auth_code</auth_code>\n";
        $output .= "    <prd_list>\n";
        $output .= "        <prd idx=\"1\">\n";
        $output .= "            <prd_code>$prd_code</prd_code>\n";
        $output .= "            <item_list>\n";
        $output .= "                <item idx=\"1\">\n";
        $output .= "                    <item_code>$item_code</item_code>\n";
        $output .= "                    <enter_cnt>$stock</enter_cnt>\n";
        $output .= "                </item>\n";
        $output .= "            </item_list>\n";
        $output .= "        </prd>\n";
        $output .= "    </prd_list>\n";
        $output .= "</product>\n";
        
        // file 저장
        $dir = "/home/ezadmin/public_html/shopadmin/tmp/";
        $file_name = _DOMAIN_ . ".xml";
        
        $f = fopen( $dir . $file_name, "w");
        fwrite($f,$output,strlen($output)); 
        fclose($f); 
        
        return $file_name;
    }
    
	// 인증 정보
	function get_info($shop_id)
    {
        $query = "select userid,auth_code from shopinfo where shop_id=$shop_id";
        $result = mysql_query( $query, $this->m_connect );
        $data   = mysql_fetch_assoc( $result );
        
        return array($data[userid],$data[auth_code]);
	}
}
?>
