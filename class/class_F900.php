<?
require_once "class_top.php";
require_once "class_F.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_F900
//

class class_F900 extends class_top {

    ///////////////////////////////////////////
    function F900()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
}

?>
