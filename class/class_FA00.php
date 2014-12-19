<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_FA00
//

class class_FA00 extends class_top {

    ///////////////////////////////////////////

    function FA00()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("판매처별 정산내역 II");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

}

?>
