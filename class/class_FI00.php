<?
require_once "class_top.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_FI00
//

class class_FI00 extends class_top {

    ///////////////////////////////////////////

    function FI00()
    {
	global $connect;
	global $template;

        $transaction = $this->begin("매출캘린더");

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        $this->end($transaction);
    }

}

?>
