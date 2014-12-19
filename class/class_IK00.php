<?
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_supply.php";

//////////////////////////////////////////////
// 입고전표
class class_IK00 extends class_top
{
   // 입고전표 목록
   function IK00()
   {
      global $template, $connect, $page;
      global $start_date, $end_date, $string;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-14 day'));
      if (!$end_date) $end_date = date('Y-m-d');

      // 상세 정보 가져온다
      $query = "select * from stockin_req_sheet where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' ";
     
      if( $string )
        $query .= " and name like '%$string%'";
      
      $query .= " order by seq desc";

      $link_url = "?" . $this->build_link_url();
      // 페이지
      if(!$page)
      {
        $page=1;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        exit;
      }
       // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
      
      $line_per_page = 50;
      $starter = ($page-1) * $line_per_page;
      $limit = " limit $starter, $line_per_page";

      $result = mysql_query($query . $limit, $connect);

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }  

    function IK01()
    {
        global $template, $connect, $sheet;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
   
    function create_stockin_sheet()
    {
        global $connect, $sheet_title;
        
        $val = array();
        
        // 동일 전표명 확인
        $query = "select * from stockin_req_sheet where name='$sheet_title'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
            echo json_encode($val);
            exit;
        }
        
        $query = "insert stockin_req_sheet 
                     set crdate = now(), 
                         worker = '$_SESSION[LOGIN_NAME]', 
                         name   = '$sheet_title'";
debug("전표생성:$query");                         
        mysql_query($query, $connect);
        
        $val['error'] = 0;
        echo json_encode($val);
    }
    
}
?>
