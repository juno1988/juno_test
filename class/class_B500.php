<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_B500
//
class class_B500 extends class_top {

    ///////////////////////////////////////////

    function B500()
    {
	global $connect;
	global $template, $line_per_page;
	$link_url = $this->build_link_url();

        $result = $this->trans_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    // add
    // date: 2005.9.2
    function add()
    {
	global $connect, $trans_corp, $shop_id, $code;
        
	$link_url = "?" . $this->build_link_url();

	$sql = "insert into trans_shop 
                   set trans_corp = '$trans_corp',
                       shop_id = '$shop_id',
                       code = '$code'";

	mysql_query($sql, $connect) or die(mysql_error());

        $this->redirect ( $link_url );
	exit;
    }

    ///////////////////////////////////////////
    // delete
    function delete()
    {
	global $connect;
    
	$id_list = $_REQUEST["result"];
        $id_list = substr ( $id_list, 0, strlen( $id_list) - 1 );

	$sql = "delete from trans_info where id in ($id_list)";
	mysql_query($sql, $connect) or die(mysql_error());

	echo "<script>document.location.href = '?template=B400';</script>";
	exit;
    }

    //////////////////////////////////////////////////////
    // list 
    function trans_list()
    {
       global $connect, $shop_id, $trans_corp;

       $query = "select * , a.trans_corp trans_name
                   from trans_info a, trans_shop b, shopinfo c
                  where a.id = b.trans_corp
                    and b.shop_id = c.shop_id";

       if ( $shop_id )
           $query .= " and b.shop_id = '$shop_id' ";

       $query .= " order by a.trans_corp";

       $result = mysql_query ( $query, $connect );
       return $result;
    }
}

?>
