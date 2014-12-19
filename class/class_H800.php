<?
require_once "class_top.php";
require_once "class_F.php";
require_once "class_C.php";
require_once "class_ui.php";
require_once "class_shop.php";
require_once "class_supply.php";
require_once "class_statrule2.php";

////////////////////////////////
// class name: class_H800
//

class class_H800 extends class_top {
    ///////////////////////////////////////////
    function H800()
    {
        global $connect, $select_code, $select_user,$start_date, $end_date, $start_time, $end_time, $page,$str_code,$select_type;
        
        $query = "select * from ezauto_log 
                   where crdate >= '$start_date $start_time:00:00'
                     and crdate <= '$end_date $end_time:00:00'                     
                     ";
        if ( $select_user )
            $query .= " and user='$select_user' ";
        
        if ( $str_code )
            $query .= " and code='$str_code' ";
        
        if ( $select_type )
            $query .= " and type='$select_type' ";
        
        $query .= " order by seq desc limit 300";
        
        $result = mysql_query( $query, $connect );
        
        $total_rows = mysql_num_rows( $result );
        
        $master_code = substr( $template, 0,1);
        include "template/H/H800.htm";
    }

    function get_list()
    {
        echo "get list";   
        
    }
    
    function query()
    {
        global $connect, $select_code, $select_user;
        
        
        
        $master_code = substr( $template, 0,1);
        include "template/H/H800.htm";
    }

}

?>
