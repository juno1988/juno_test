<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_GD00
//

class class_GD00 extends class_top {

    ///////////////////////////////////////////

    function GD00()
    {
	global $connect, $start_date, $end_date;

        if ( !$start_date )
        {
	    $timestamp = strtotime("-3 days");
            $start_date = date("Y-m-d",$timestamp);
        }

	global $template;
        $transaction = $this->begin("발주요약표");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

}

?>
