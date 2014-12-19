<?
require_once "class_top.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_G900
//

class class_G900 extends class_top {

    ///////////////////////////////////////////

    function G900()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("매출통계(업체용)");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

}

?>
