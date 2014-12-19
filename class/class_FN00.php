<?
require_once "class_top.php";

class class_FN00 extends class_top {

    function FN00()
    {
        global $template, $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

//	function 


}

?>
