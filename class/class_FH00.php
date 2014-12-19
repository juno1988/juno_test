<?
require_once "class_top.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_FH00
//

class class_FH00 extends class_top {

    ///////////////////////////////////////////

    function FH00()
    {
	    global $connect;
	    global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        $this->end($transaction);
    }

}

?>
