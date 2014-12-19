<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_F.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_F600
//

class class_F600 extends class_top {

    ///////////////////////////////////////////

    function F600()
    {
        global $template, $line_per_page;
        
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    function search()
    {
        global $connect,$date_type,$start_date,$end_date,$shop_id,$supply_id;   
        
        $query = "select a.order_id,b.* from order_products b left join orders a on a.seq = b.order_seq
                   where a.${date_type} >= '$start_date' and a.${date_type} <= '$end_date'";
                     
        $result   = mysql_query( $query, $connect );
        $arr_data = array();
        $arr_data['qurey'] = $query;        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data['list'][] = array(
                seq           => $data[seq]
                ,order_id     => $data[order_id]
                ,product_id   => $data[product_id]
                ,shop_name    => "zz"
                ,product_name => $info[product_name]
                ,options      => $info[options]
                ,qty          => $data[qty]
            );
        }
        
        echo json_encode( $arr_data );
    }
    
}

?>
