<?
require_once "class_top.php";
require_once "class_F.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_F700
//

class class_F700 extends class_top {

    ///////////////////////////////////////////
    function F700()
    {
	global $connect;
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function add()
    {
	global $connect;
	global $template;

	/////////////////////////////////////////
	$settle_year = $_POST[s_year];
	$settle_month = $_POST[s_mon];
	$start_date = $_POST[start_date];
	$end_date = $_POST[end_date];
	$query_type = $_POST[query_type];

	$sql = "select * from settle_history where settle_year = '$settle_year' and settle_month = '$settle_month'";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$list = mysql_fetch_array($result);
	if ($list)
	{
	    echo "<script>alert('해당년월은 이미 설정되어 있습니다.\\n수정하시려면 삭제후 재생성하시기 바랍니다.');</script>";
	}
	else
	{
	    $sql = "insert into  settle_history set
		     	settle_year = '$settle_year',
		       	settle_month = '$settle_month',
			start_date = '$start_date',
			end_date = '$end_date',
			base_day = '$query_type'";
	    mysql_query($sql, $connect) or die(mysql_error());
	    echo "<script>alert('설정되었습니다.');</script>";
	}

	/////////////////////////////////////////
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function del()
    {
	global $connect;
	global $template;

	/////////////////////////////////////////
	$settle_year = $_REQUEST[year];
	$settle_month = $_REQUEST[month];

	$sql = "delete from settle_history where settle_year = '$settle_year' and settle_month = '$settle_month'";
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>alert('삭제되었습니다.');</script>";


	/////////////////////////////////////////
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

    }

    ///////////////////////////////////////////
}

?>
