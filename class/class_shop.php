<?
// class_shop
// shop관련된 class
// date: 2008.8.18
// jk.ryu
class class_shop{

    function get_shop_name( $shop_id )
    {
        global $connect;

        $query = "select shop_name From shopinfo where shop_id=$shop_id";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );
        return $data[shop_name];
    }

    function get_shop_name2( $shop_id )
    {
        global $connect;

        $shop_name_s = "SHOP_NAME_" . $shop_id;
        return $_SESSION[$shop_name_s];
    }

    function product_link()
    {
        global $connect, $product_id, $shop_id;
        $shop_code = $shop_id % 100;

        $query = "select * from code_match 
                   where id        = '$product_id' 
                     and shop_id   = '$shop_id'";

        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );

        $is_success = 0;
        // 값이 없을 경우 다른 mall정보도 찾아야 함
        if ( !mysql_num_rows( $result ) )
        {
            $query = "select * from code_match 
                       where id = '$product_id' 
                       limit 1"; 
            $result = mysql_query ( $query, $connect );
            $data   = mysql_fetch_array ( $result );

            if ( mysql_num_rows( $result ) )
            {
                $is_success      = 1;
                $shop_product_id = $data[shop_code];
                $shop_code       = $data[shop_id] % 100;
            }

        }

        $_url = "";
        if ( $is_success )
        {
            switch ( $shop_code )
            {
                case 1:        // action
                    $_url = "http://itempage.auction.co.kr/DetailView.aspx?itemno=" . $shop_product_id;
                    break;        
            }
        }
        else
        {
            $_url = "http://admin.ezadmin.co.kr/template.htm?template=C202&product_id=" . $shop_product_id;
        }
        
        return $_url;
    }

    function get_shop_list()
    {
        global $connect;
        
        if( _DOMAIN_ == 'efolium2' || _DOMAIN_ == 'jwc2' || _DOMAIN_ == 'kldh01' || _DOMAIN_ == 'queens2' || _DOMAIN_ == 'stylebyyam' )
            $query = "select shop_id, shop_name from shopinfo where disable=0 order by sort_name";
        else
            $query = "select shop_id, shop_name from shopinfo where shop_id<>10000 and disable=0 order by sort_name";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_array($result) )
        {
            $val[] = array(
                shop_id   => $data[shop_id],
                shop_name => $data[shop_name]
            );            
        }
        return $val;
    }
    
    // 판매처 정보를 return함
    function get_info( $shop_id)
    {
        global $connect;
        
        $query = "select * from shopinfo where shop_id='$shop_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data;
    }
    
}
?>
