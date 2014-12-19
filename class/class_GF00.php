<?
require_once "class_top.php";
require_once "class_G.php";

////////////////////////////////
// class name: class_GF00
//

class class_GF00 extends class_top {

    ///////////////////////////////////////////

    function GF00()
    {
	global $connect;
	global $template;

        $transaction = $this->begin("¸ÅÃâÄ¶¸°´õ");

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        $this->end($transaction);
    }

}

?>
