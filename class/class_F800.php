<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_F.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_F800
//

class class_F800 extends class_top {

    ///////////////////////////////////////////

    function F800()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("업체정산자료");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    function F801()
    {
	global $connect;
	global $template, $line_per_page;
        $transaction = $this->begin("업체정산자료");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    function confirm()
    {
	global $connect;
	global $template;

	$supply_id = $_SESSION[LOGIN_CODE];
	$settle_amount = $_REQUEST[settle_amount];
	$settle_year = $_REQUEST[settle_year];
	$settle_month = $_REQUEST[settle_month];

	$sql = "insert into settle_confirm set
		settle_year = '$settle_year',
		settle_month = '$settle_month',
		supply_id = '$supply_id',
		crdate = now(),
		crtime = now(),
		status = 1";

	@mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>alert('정산 확정처리가 완료되었습니다.');</script>";

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

}

?>
