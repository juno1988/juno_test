<?
require_once "class_top.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_G800
//

class class_G800 extends class_top {

    ///////////////////////////////////////////

    function G800()
    {
	global $connect;
	global $template;

        $transaction = $this->begin("일별매출그래프(G800)");

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        $this->end($transaction);
    }

}

?>
