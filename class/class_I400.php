<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_I400 extends class_top
{
  //////////////////////////////////////////////////////
  // 상품 리스트 
  function I400()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function add()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[product_id];
	$start_date = $_REQUEST[start_date];
	$alarm_qty = $_REQUEST[alarm_qty];

	$sql = "select product_id from jaegolist where product_id = '$product_id' and is_delete = 0";
	$result = mysql_query($sql, $connect) or die(mysql_error());
	$list = mysql_fetch_array($result);

	if ($list)
	{
	  echo "<script>alert('${product_id} 코드 상품은 이미 재고관리 중인 상품입니다.');</script>";
	  echo "<script>history.back();</script>";
	  exit;
	}
	else
	{
	  $sql = "insert into jaegolist set
			product_id = '$product_id',
			start_date = '$start_date',
			input_time = now(),
			alarm_qty = '$alarm_qty'";
	  mysql_query($sql, $connect) or die(mysql_error());
	}
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I400");
	exit;
  }   

  function addall()
  {
	global $template;
	global $connect;

	$id_list = $_REQUEST[id_list];
	$start_date = $_REQUEST[start_date];
	$alarm_qty = $_REQUEST[alarm_qty];

	$id_list = str_replace("'", "", stripslashes($id_list));
	$arr_id = split(",", $id_list);

	for ($i = 0; $i < sizeof($arr_id); $i++)
	{
	    $product_id = $arr_id[$i];
	    $sql = "select product_id from jaegolist where product_id = '$product_id' and is_delete = 0";
	    $result = mysql_query($sql, $connect) or die(mysql_error());
	    $list = mysql_fetch_array($result);

	    if (!$list)
	    {
	      $sql = "insert into jaegolist set
			product_id = '$product_id',
			start_date = '$start_date',
			input_time = now(),
			alarm_qty = '$alarm_qty'";
	      mysql_query($sql, $connect) or die(mysql_error());
	    }
	}
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I400");
	exit;
  }   

  function I401()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I402()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function I403()
  {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
  }

  function save()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[product_id];
	$start_date = $_REQUEST[start_date];
	$alarm_qty = $_REQUEST[alarm_qty];
  	$sql = "update jaegolist set
			start_date = '$start_date',
			alarm_qty = '$alarm_qty'
		 where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());
	echo "<script>self.close();</script>";
	$this->opener_redirect("template.htm?template=I400");
	exit;
  }

  function delete()
  {
	global $template;
	global $connect;

	$product_id = $_REQUEST[product_id];
	$sql = "delete from jaegolist where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());

	$sql = "delete from jaego_inout where product_id = '$product_id'";
	mysql_query($sql, $connect) or die(mysql_error());

	$this->redirect("template.htm?template=I400");
	exit;
  }
}
?>
