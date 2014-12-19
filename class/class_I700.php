<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
class class_I700 extends class_top
{
    //////////////////////////////////////////////////////
    // ¹­À½ »óÇ° ¸ÅÄªÃ³¸® 
    function I700()
    {
	global $template;
	global $connect;

	$this->begin( '¹­À½»óÇ° ¸ÅÄª ½ÃÀÛ');
	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
    }

    function match()
    {
	global $template;
	global $connect;

	$id = $_REQUEST[id];
	$pack_list = $_REQUEST[pack_list];
	$pack_list = str_replace("*", "", $pack_list);

	$sql = "update orders set 
			pack_list = '$pack_list'
	  	 where seq = '$id'
		   and packed = '1'
	";
	mysql_query($sql, $connect) or die(mysql_error());
        $this->redirect("template.htm?template=I700");

    }

    function I701()
    {
	global $template;
	global $connect;

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
    }
}
