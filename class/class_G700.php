<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_G700
//

class class_G700 extends class_top {

    ///////////////////////////////////////////

    function G700()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("공급처별 매출 통계");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

}

?>
