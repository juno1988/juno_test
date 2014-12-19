<?
/*
*
* date: 2012.10.24 - cyim
* desc: godo 관련된 class
*
*/

class class_godo
{
    var $m_connect;
    var $partner_key;
    var $arr_products;
    
    function class_godo( $connect )
    {
        $this->m_connect = $connect;   
        $this->partner_key = "JUJGeSUyMiVBRCVCOVAlMTMlQkY=";
        $this->arr_products = array();
    }
    
    //
    // 고도몰 재고 정보 가져오기
    // Date: 2014.5.27
    // JUZBRmElRTJQTyU3QyVDRiU5QiUwOCVGRiU5QyUxMiU5NyVBRDglQjIlRDMlQ0UlODYlQ0UlQkNSdyUxMiU4MiVEM0xOJTFFJUM5JTFBJUFEJTFELSUwOHolMEI2dA==
    // sample url
    //
    function get_stock( $product_id,$link_id, $shop_id )
    {
        $arr_result = array();
        $arr_result['success']    = 0;
        $arr_result['product_id'] = $product_id;
        $arr_result['link_id']    = $link_id;
        $arr_result['stock']      = 0;
        $arr_result['source']     = "godo"; // godo, local
        
        //
        // $this->arr_products 에 존재 하는지 여부 체크
        //
        if ( $this->get_stock_global( $parent_link_id,$link_id ,&$stock) )
        {
            $arr_result['success'] = 1;
            $arr_result['stock']   = $stock;      
            $arr_result['source']  = "local"; // godo, local
        }
        else
        {
            // parent의 link_id를 가져온다.
            $parent_link_id = $this->get_parent_link_id( $product_id );
            
            // godo에서 데이터를 가져온다.
            $this->load_stock_data(  $parent_link_id,$link_id, $shop_id ); 
            
            if ( $this->get_stock_global( $parent_link_id,$link_id ,&$stock) )
            {
                $arr_result['success'] = 1;
                $arr_result['stock']   = $stock;      
                $arr_result['source']  = "godo"; // godo, local
            }
            else
            {
                $arr_result['success'] = 0;
            }
        }
        
        //
		// parsing
		//
		echo json_encode( $arr_result );
    }
    
    function get_parent_link_id ( $product_id )
    {
        global $connect;
        
        $query  = "select org_id from products where product_id='$product_id'";
        debug( "q1: $query");
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $org_id = $data['org_id'];
        
        // org_id로 parent_link_id를 구한다.
        $query  = "select link_id from products where product_id='$org_id'";
        debug( "q2: $query");
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        debug("org_link_id: $data[link_id]");
        
        return $data['link_id'];
    }
    
    function load_stock_data(  $parent_link_id,$link_id, $shop_id )
    {
        $key = $this->get_info( $shop_id );
        
        $url  = "openhub.godo.co.kr/enamoo/goods/Goods_Option_Search.php";
		$url .= "?partner_key=" . $this->partner_key;
		$url .= "&key=" . $key;
		$url .= "&goodsno=" . $parent_link_id;
		
		debug( $url );
		
		// xml parsing
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch); 
		
		// debug( $output );
		
		$datas = new SimpleXMLElement($output);
		$i = 0;
		
		foreach ( $datas->return->option_data as $opt )
		{
		    $goodsno = (string)$opt->goodsno;
		    $sno     = (string)$opt->sno;
		    $stock   = (string)$opt->stock;
		    
		    $this->arr_products[$goodsno][$sno] = $stock;
		    $i++;
		}
		    
    }
    
    function get_stock_global( $parent_link_id,$link_id, &$stock )
    {
        $result = 0;
        
        if ( array_key_exists ( $parent_link_id , $this->arr_products ) )
		{
		    if ( array_key_exists ( $link_id, $this->arr_products[ $parent_link_id ] ) )
		    {
		        $stock = $this->arr_products[ $parent_link_id ][ $link_id ];
		        $result = 1;
		    }
		    else
		    {
		        $stock = 0;
		    }
		}
		else
		{
		    $stock = 0;
		}
		
		return $result;    
    }
    
    //
    // 고도몰로 재고를 등록한다.
    //
    function stock_sync( $product_id, $shop_id )
    {
		debug("stock_sync: $product_id / $shop_id ");
		
        $key = $this->get_info( $shop_id );
        $file_name = $this->get_pinfo( $product_id, $stock );
        
		$url  = "openhub.godo.co.kr/enamoo/goods/Goods_Stock.php";
		$url .= "?partner_key=" . $this->partner_key;
		$url .= "&key=" . $key;
		$url .= "&data_url=http://". $_SERVER['HTTP_HOST'] . "/tmp/" . $file_name;
    	
    	debug( "url:" . $url );
    	
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        $output = curl_exec($ch); 
        curl_close($ch); 

        debug( "output:" . $output );
		echo $output;
    }

    // 상품 정보로 xml file을 만들고 그 full path를 return한다.
    function get_pinfo( $product_id )
    {
        $query = "select link_id, org_id,stock_manage from products where product_id='$product_id'";
        debug( $query );
        
        
        $result = mysql_query( $query, $this->m_connect );
		$data = mysql_fetch_assoc( $result ); 
		
		$goodsno = $data[link_id];
		$org_id = $data[org_id];
		
		$pdata = array();
		$query = "select product_id,link_id from products where org_id = $product_id";
		
		debug( $query );
		
        $result = mysql_query( $query, $this->m_connect );

		$totstock = 0;
		while ( $data = mysql_fetch_assoc( $result ) )
		{ 
		    $_stock = class_stock::get_current_stock( $data[product_id] );
		    
		    debug( "pid: $data[product_id] => $_stock");
		    
			$pdata[ $data[link_id] ] = $_stock;
			$totstock += $_stock;
		}
        
        
		$output  = "<?xml version='1.0' encoding='utf-8'?>";
		$output .= "  <data>";
		$output .= "    <goods_data idx='1'>";
		$output .= "	  <goodsno>$goodsno</goodsno>";
		$output .= "	  <totstock>$totstock</totstock>";

		$idx = 1;
		foreach ( $pdata as $sno => $stock )
		{
			$output .= "    <option_data idx='" . $idx++ . "'>";
			$output .= "	  <sno>$sno</sno>";
			$output .= "	  <stock>$stock</stock>";
			$output .= "	</option_data>";
		}

		$output .= "    </goods_data>\n";
        $output .= "  </data>\n";

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
        $query = "select auth_code from shopinfo where shop_id=$shop_id";
        
        debug( $query );
        
        $result = mysql_query( $query, $this->m_connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[auth_code];
	}
}
?>
