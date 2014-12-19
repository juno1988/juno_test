<?
require_once "class_top.php";
require_once "class_file.php";
require_once "class_tempupload.php";
require_once "class_cancelformat.php";
require_once "class_ui.php";

////////////////////////////////
// class name: class_EI00
//
class class_EI00 extends class_top {

    ///////////////////////////////////////////

    function EI00()
    {
	global $connect;
	global $template;
        global $start_date, $end_date, $keyword, $order_cs, $search_type;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function upload()
    {
	global $shop_id;
	// _file
	$arr_result = class_file::upload();
	class_tempupload::save( $shop_id, $arr_result,"cancel_reg" );

	$this->redirect("?template=EI00&shop_id=$shop_id");
    }

    function save_format()
    {
	echo "save_format";
	$obj = new class_cancelformat();
	$obj->save();
    }

    function load_format()
    {
	$obj = new class_cancelformat();
	$obj->load_format();
    }
}
