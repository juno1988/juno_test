<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_combo.php";
require_once "class_category.php";
require_once "class_stock.php";
require_once "class_product.php";
require_once "class_ui.php";
require_once "class_3pl.php";
require_once "class_CL00.php";
require_once "class_auto.php";
require_once "class_supply.php";

//////////////////////////////////////////////
// 상품 변경 로그
class class_CQ00 extends class_top
{
    function CQ00()
    {
        global $template, $pick_soldout_date, $start_date, $end_date, $packed, $date_type,$e_stock, $stock_manage, $link_url_list;
        
        $par_arr = array("template","action","supply_code","string_type","string","start_date","end_date","page");
        $link_url_list = $this->build_link_par($par_arr);     
        
        if ( $_REQUEST["page"] )
            $result = $this->get_list( &$total_rows, $page );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CQ01()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CQ02()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CQ03()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CQ04()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CQ05()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}
?>
