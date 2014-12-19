<?
require_once "class_top.php";
require_once "class_A.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_BH00
//

class class_BH00 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function BH00()
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
