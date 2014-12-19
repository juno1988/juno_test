<?
require_once "class_top.php";
require_once "class_A.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_A800
//

class class_A800 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function A800()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function save()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>
