<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_stat.php";

////////////////////////////////
// class name: class_G300
//

class class_G300 extends class_top {

    ///////////////////////////////////////////
    // date: 2008.7.9 - jk
    function G301()
    {
	global $connect;
	global $template;
	$_today = date('Y-m-d', strtotime('today'));

        include "template/G/G301.htm";
    }

    ///////////////////////////////////////////
    // date: 2008.7.9 - jk
    function G302()
    {
	global $connect;
	global $template, $act, $shop_id, $start_date;

        include "template/G/G302.htm";
    }

    function G300()
    {
	global $connect;
	global $template;

        $transaction = $this->begin("당일판매분요약표");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	$this->end($transaction);
    }

}

?>
