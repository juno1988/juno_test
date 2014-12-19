<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_D.php";

////////////////////////////////
// class name: class_G400
//

class class_G400 extends class_top {

    ///////////////////////////////////////////

    function G400()
    {
	global $connect;
	global $template;

	$act = $_REQUEST[act];
	$type = $_REQUEST[type];

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>
