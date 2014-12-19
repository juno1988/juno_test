<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_EQ00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고 로그 조회
    function EQ00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $string;

        $string = trim( $string );
        
        $seq_arr = array();
        $pack_arr = array();
        if( $string )
        {
            $query = "select seq, pack from orders where seq=$string or pack=$string";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                $seq_arr[] = $data[seq];
                $seq_arr[] = $data[pack];

                $pack_arr[] = $data[seq];
                if( $data[pack] <> 0 ) 
                    $pack_arr[] = $data[pack];
            }
            $seq_list = implode(",", array_unique($seq_arr));
            $pack_list = implode(",", array_unique($pack_arr));
            
            $query = "select seq, pack, status, order_cs from orders where seq in ($seq_list) or pack in ($pack_list) order by pack, seq";
            $result = mysql_query($query, $connect);
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function change_pack()
    {
        global $connect, $seq, $new_pack;
        
        $query = "update orders set pack='$new_pack' where seq=$seq";
debug("합포 수정 : " . $query);
        mysql_query($query, $connect);
    }
    
}

?>
