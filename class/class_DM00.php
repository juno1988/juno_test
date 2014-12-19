<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_DM00
//

class class_DM00 extends class_top {

    ///////////////////////////////////////////
    // shopµéÀÇ listÃâ·Â

    function DM00()
    {
	global $connect;
	global $template;

	$transaction = $this->begin("ÇÕÆ÷ Àü ÀÚ·á°ËÁõ");
	$this->end($transaction);

	$line_per_page = _line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

   function DM01()
   {
      global $template;
      global $connect;
    
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function modify()
   {
	global $template;
	global $connect;
	$transaction = $this->begin("ÇÕÆ÷ Àü ÀÚ·á°ËÁõ ÀÚ·á º¯°æ");
	$seq = $_REQUEST[seq];
	$order_name = $_REQUEST[order_name];
	$recv_name = $_REQUEST[recv_name];
	$recv_tel = $_REQUEST[recv_tel];
	$recv_mobile = $_REQUEST[recv_mobile];
	$recv_zip = $_REQUEST[recv_zip];
	$recv_address = $_REQUEST[recv_address];

	$sql = "update tbl_pack_tmp2 set
		  recv_name = '$recv_name',
		  order_name = '$order_name',
		  recv_tel = '$recv_tel',
		  recv_mobile = '$recv_mobile',
		  recv_zip = '$recv_zip',
		  recv_address = '$recv_address'
		 where seq = '$seq'
	";
	mysql_query($sql, $connect) or die(mysql_error());

	$sql = "update orders set
		  recv_name = '$recv_name',
		  order_name = '$order_name',
		  recv_tel = '$recv_tel',
		  recv_mobile = '$recv_mobile',
		  recv_zip = '$recv_zip',
		  recv_address = '$recv_address'
		 where seq = '$seq'
	";
	mysql_query($sql, $connect) or die(mysql_error());

	$this->end($transaction);

        $this->closewin();
        // $this->redirect("popup.htm?template=DM01&seq=$seq");
        exit;
   }

}

?>
