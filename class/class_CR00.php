<?
require_once "class_top.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_CR00 extends class_top
{
   function CR00()
   {
        global $connect, $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

    function get_product_price()
    {
        global $connect;
        
        $val = array();

        // 판매처
        $shopinfo_arr = array();
        $query_shop = "select * from shopinfo where auto_price = 1 order by sort_name";
        $result_shop = mysql_query($query_shop, $connect);
        $i = 0;
        while( $data_shop = mysql_fetch_assoc($result_shop) )
            $shopid_arr[$i++] = $data_shop[shop_id];

        $cnt = count($shopid_arr);
        
        $query = "select * from products limit 50";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $temp_arr = array(
                product_id   => $data[product_id],
                product_name => $data[name],
                options      => $data[options],
                org_price    => number_format($data[org_price])
            );
            
            for( $i=0; $i < $cnt; $i++ )
            {
                $query_price = "select * from price_history 
                                 where product_id = '$data[product_id]' and
                                       shop_id = " . $shopid_arr[$i];
                $result_price = mysql_query($query_price, $connect);
                if( mysql_num_rows($result_price) )
                {
                    $data_price = mysql_fetch_assoc($result_price);
                    $temp_arr[$shopid_arr[$i] . "_supl"] = number_format( $data_price[supply_price] );
                    $temp_arr[$shopid_arr[$i] . "_shop"] = number_format( $data_price[shop_price] );
                    
                    $rate = ($data_price[shop_price] - $data_price[supply_price]) * 100 / $data_price[shop_price];
                    $temp_arr[$shopid_arr[$i] . "_rate"] = number_format( $rate ) . "%";
                }
                else
                {
                    $temp_arr[$shopid_arr[$i] . "_supl"] = "";
                    $temp_arr[$shopid_arr[$i] . "_shop"] = "";
                    $temp_arr[$shopid_arr[$i] . "_rate"] = "";
                }
            }

            $val['list'][] = $temp_arr;
        }
        $val['error']=0;
        echo json_encode( $val );
    }
}
?>
