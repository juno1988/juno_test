<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_GC00
//

class class_GC00 extends class_top {

    ///////////////////////////////////////////

    function GC00()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("매입매출통계");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }
}

?>
