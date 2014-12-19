<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_file.php";

////////////////////////////////
// class name: class_G500
//

class class_G500 extends class_top {

    ///////////////////////////////////////////

    function G500()
    {
        global $connect;
        global $template, $page;

        $link_url = "?" . $this->build_link_url();

        $start_date = $_REQUEST["start_date"];
        $end_date = $_REQUEST["end_date"];

        if ( $page )
           $result = $this->get_list( &$total_rows ); 

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function download()
    {
        global $connect;
        global $template, $page;

        $result = $this->get_list( &$total_rows, 1 ); 

        $arr_datas   = array();
        $arr_datas[] = array( "페이지코드", "상태", "작업대상", "실행일", "실행시간", "작업자");

        while ( $data = mysql_fetch_array( $result ) )
        {
            $arr_datas[] = array( 
                $data[template   ], 
                $data[status     ], 
                $data[target_id  ], 
                $data[commit_date], 
                $data[starttime  ], 
                $data[owner      ] );
        }

        $oFile = new class_file();
           $oFile->download( $arr_datas, $file_name = "cs_data.xls" );
    }

    function get_list( &$total_rows, $is_download=0 )
    {
       global $connect, $page;
       global $type, $string;

       $line_per_page = _line_per_page;

       if ( !$page ) $page = 1;
       $starter = ( $page - 1 ) * $line_per_page;

       $start_date = $_REQUEST["start_date"];
       $end_date = $_REQUEST["end_date"];

       $query_cnt = "select count(*) cnt ";
       $query = "select * ";
       $option = " from transaction 
                  where commit_date >= '$start_date'
                    and commit_date <= '$end_date'";

       if ( $_SESSION[LOGIN_LEVEL] != 9 )
           $option .= " and owner <> 'root' ";

       if ( $type )
          $option .= " and $type like '%$string%'";
          /*
          switch ( $type )
          {
             case "owner":
             case "target_id" :
                $option .= " and $type = '$string'";
             break;
          }    
          */
        if ( !$is_download )
            $limit = " order by no desc limit $starter, $line_per_page";
        else
             $limit = " order by no desc"; 

       ///////////////////////////////////////////////
       // total count
       $result = mysql_query ( $query_cnt . $option, $connect );
       $data = mysql_fetch_array ( $result );       
       $total_rows = $data[cnt];

       ///////////////////////////////////////////////
       // result
       $result = mysql_query ( $query . $option . $limit, $connect );
       return $result;
    }
}

?>
