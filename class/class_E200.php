<?
require_once "class_top.php";
require_once "class_E.php";

////////////////////////////////
// class name: class_E200
//

class class_E200 extends class_top {

    ///////////////////////////////////////////

    function E200()
    {
	global $connect;
	global $template;

	$line_per_page = _line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>
