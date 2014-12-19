<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_HD00
//

class class_HD00 extends class_top {

    ///////////////////////////////////////////

    function HD00()
    {
		global $connect;
		global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>
