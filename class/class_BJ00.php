<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_BJ00
//
class class_BJ00 extends class_top {
    function BJ00()
    {
		global $connect, $template;
		$sys_connect = sys_db_connect();
		
		$master_code = substr( $template, 0,1);
		
		$query = "select * from sys_emergency_contacts WHERE domain = '". _DOMAIN_ ."'";
		$result = mysql_query ( $query, $sys_connect ) or die( mysql_error() );
		$data = mysql_fetch_array ( $result );
		
		$query = "SELECT id, manager, reseller FROM sys_domain WHERE id = '" . _DOMAIN_ . "'";
		$result = mysql_query ( $query, $sys_connect ) or die( mysql_error() );
		$sys_data = mysql_fetch_array ( $result );
		
		if(!$sys_data[reseller])
			$sys_data[reseller] = $sys_data[manager] ? $sys_data[manager] : "";
		
		
		$query = "SELECT * FROM sys_admin_user WHERE id LIKE '%$sys_data[reseller]%' OR name  LIKE '%$sys_data[reseller]%'";
		$result = mysql_query ( $query, $sys_connect ) or die( mysql_error() );
		$sys_user = mysql_fetch_array ( $result );
		
		if(!$sys_data[reseller])
			$sys_data[reseller] = "담당자 배정중입니다.";
		else
			$sys_data[contact] = $sys_user[mobile];
			
/*		
		switch($sys_data[reseller])
		{
			case "admin1":
			$sys_data[contact] = "010-9956-0882";
			break;
			
			case "admin2":
			$sys_data[contact] = "010-9786-5007";
			break;
			
			case "나종훈":
			$sys_data[contact] = "010-6284-7664";
			break;
			
			case "박진영":
			$sys_data[contact] = "010-4718-0708";
			break;
			
			case "천자문":
			$sys_data[contact] = "010-9927-3461";
			break;	
			
			case "":
			$sys_data[reseller] = "담당자 배정중입니다.";
			break;	
		}
*/
	
        include "template/" . $master_code ."/" . $template . ".htm";
    }
	function modify()
	{
		global $connect, $template;
		global $email, $office_contact, $office_addr;
		global $balju_name, $balju_contact, $trans_name, $trans_contact, $cs_name, $cs_contact;
		global $trans_crop, $trans_crop_tel, $trans_crop_name, $trans_crop_contact;
		
		$sys_connect = sys_db_connect();
		
		$query = "select * from sys_emergency_contacts WHERE domain = '". _DOMAIN_ ."'";
		$result = mysql_query ( $query, $sys_connect ) or die( mysql_error() );
		$data_rows = mysql_num_rows($result);
		
		if($data_rows)
		{
			$query = "UPDATE sys_emergency_contacts SET
							email               ='$email'
							,office_contact     ='$office_contact'
							,office_addr        ='$office_addr'
							,balju_name         ='$balju_name'
							,balju_contact      ='$balju_contact'
							,trans_name         ='$trans_name'
							,trans_contact      ='$trans_contact'
							,cs_name            ='$cs_name'
							,cs_contact         ='$cs_contact'
							,trans_crop         ='$trans_crop'
							,trans_crop_name    ='$trans_crop_name'
							,trans_crop_contact ='$trans_crop_contact'
							,trans_crop_tel		='$trans_crop_tel'
							WHERE  domain = '". _DOMAIN_ ."'";
		}
		else
		{
		 	$query = "INSERT INTO sys_emergency_contacts SET
		 					 domain 			='"._DOMAIN_ ."'
							,email              ='$email'
							,office_contact     ='$office_contact'
							,office_addr        ='$office_addr'
							,balju_name         ='$balju_name'
							,balju_contact      ='$balju_contact'
							,trans_name         ='$trans_name'
							,trans_contact      ='$trans_contact'
							,cs_name            ='$cs_name'
							,cs_contact         ='$cs_contact'
							,trans_crop         ='$trans_crop'
							,trans_crop_name    ='$trans_crop_name'
							,trans_crop_contact ='$trans_crop_contact'
							,trans_crop_tel		='$trans_crop_tel'
							";
		}
		$result = mysql_query ( $query, $sys_connect ) or die( mysql_error() );
debug($query);
		$this->BJ00();
	}
}

?>

