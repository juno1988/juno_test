<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_HA00
//

class class_HA00 extends class_top {

    ///////////////////////////////////////////

    function HA00()
    {
    	global $connect;
    	global $template;
    
    	$sys_connect = sys_db_connect();
    
    	$query = "select content from sys_domain_setting where id='" . _DOMAIN_ . "'";
    	$result = mysql_query($query, $sys_connect);
    	$data = mysql_fetch_assoc($result);
    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function save_setting()
    {
    	$sys_connect = sys_db_connect();
		foreach ($_REQUEST as $key=>$value) $$key = $value;

var_dump($_REQUEST);

		$id = $_SESSION[LOGIN_DOMAIN];
		$content = addslashes($content);

        $query = "select * from sys_domain_setting where id='$id'";
        $result = mysql_query($query, $sys_connect);
        if( mysql_num_rows($result) )
        	$query = "update sys_domain_setting set content='$content' where id='$id'";
        else
        	$query = "insert sys_domain_setting set content='$content',id='$id'";
echo $query;
        
    	mysql_query($query, $sys_connect);
    }

	//-------------------------------------
	// view mode
	function view_setting()
	{
    	$sys_connect = sys_db_connect();
		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$id = $_SESSION[LOGIN_DOMAIN];
        $sql = "select * from sys_domain_setting where id='$id'";

		$list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));
		if ($list && $list[content] != "" ) {
			$content = stripslashes(nl2br($list[content]));
			$strHTML = $content;
		} else {
			$strHTML = "설정 내용이 없습니다";
		}

		echo $strHTML;
	}

	//-------------------------------------
	// edit mode
	function edit_setting()
	{
    	$sys_connect = sys_db_connect();
		foreach ($_REQUEST as $key=>$value) $$key = $value;

		$id = $_SESSION[LOGIN_DOMAIN];
        $sql = "select * from sys_domain_setting where id='$id'";

		$strHTML = "<textarea id='domain_setting_textarea'>";
		$list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));
		if ($list && $list[content] != "" ) {
			$content = stripslashes(($list[content]));
			$strHTML .= $content;
		} else {
			$strHTML .= "-";
		}
		$strHTML .= "</textarea><br/><br/>";
		$strHTML .= "<center><a href='javascript:save_domain_setting();'><img src='/images/btn_save.gif'></a></center>";

		echo $strHTML;
	}

}

?>
