<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_F.php";

////////////////////////////////
// class name: class_FD00
//

class class_FD00 extends class_top {

    ///////////////////////////////////////////

    function FD00()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function FD01()
    {
	global $connect;
	global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>
