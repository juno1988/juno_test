<?
/*-------------------------------------------


	class_K700.php
	desc : 3PL 입고요청

--------------------------------------------*/
require_once "class_top.php";
require_once "class_3pl.php";

class class_K700 extends class_top {
    var $m_3pl = "";
    var $m_connect = "";

    /////////////////////
    function class_K700()
    {
	$this->m_3pl     = new class_3pl();
	$this->m_connect = $this->m_3pl->m_connect;
    }

    function K700()
    {
	global $template;

	$sql = "select * from 3pl_sheet_in
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
			  and status >= '6'
			  and status <= '6'
		 order by seq desc";
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function K701()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}
?>
