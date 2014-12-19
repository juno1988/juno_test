<?
require_once "class_top.php";

class class_ES00 extends class_top
{
    //////////////////////////////////////
    // VIP 리스트
    function ES00()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    function check_net()
    {
        global $connect, $no;

        $arr = array();
        $arr['time'] = date('Y-m-d H:i:s');

debug("check_net : $no   $arr[time]");
        echo json_encode($arr);
    }
}
?>
