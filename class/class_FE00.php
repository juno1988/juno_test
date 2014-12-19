<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_F.php";

////////////////////////////////
// class name: class_FE00
//

class class_FE00 extends class_top {

    ///////////////////////////////////////////

    function FE00()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function FE01()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>
