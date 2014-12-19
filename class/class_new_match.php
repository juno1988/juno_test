<?

class class_new_match
{
    // 판매처, 판매처 상품코드, 판매처 옵션으로 매칭 상품코드를 얻는다.
    function get_match_product($shop_id, $shop_product_id, $shop_options)
    {
        global $connect, $collect_date;

        $shop_options = iconv('utf-8','cp949',$shop_options);
        $query = "select product_id 
                    from new_match 
                   where shop_id         = '$shop_id' and 
                         shop_product_id = '$shop_product_id' and
                         shop_options    = '$shop_options'";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) > 0 )
        {
            $data = mysql_fetch_array( $result );
            return $data[product_id];
        }
        else
            return false;
    }
}
?>
