<?
require_once "class_top.php";
require_once "class_D.php";

//////////////////////////////////////////////
//
// ilovejchina 중국 송장출력
//
class class_D200 extends class_top
{
    function D200()
    {
        global $template,$connect,$start_date, $end_date;
        
        $link_url = "?" . $this->build_link_url();
        $line_per_page = 100;

        $page = $_REQUEST["page"];

        $total_rows = 0;        
        $result = $this->search( &$total_rows );
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 검색
    function search( &$total_rows )
    {
        global $connect, $template, $start_date, $end_date, $shop_id, $page;
        $line_per_page = 100;
         
        $start_date = $start_date ? $start_date : date("Y-m-d");
        $end_date   = $end_date   ? $end_date   : date("Y-m-d");
        
        $query = "select * from orders 
                   where trans_date_pos >='$start_date 00:00:00' 
                     and trans_date_pos <='$end_date 23:59:59' ";
        
        $result = mysql_query ( $query, $connect );
        $total_rows = mysql_num_rows ( $result );

        $starter = $page ? ( $page-1 ) * $line_per_page : 0;
        $query .= " limit $starter, $line_per_page";
        $result = mysql_query ( $query, $connect );
        
        //echo $query;
        
        return $result;
    }
    
    function D207()
    {
        global $template,$connect,$start_date, $end_date;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";   
    }
}

?>
