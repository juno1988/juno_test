<?
////////////////////////////////
// class name: class_A
//

class class_A {

    ////////////////////////////
    // 공급업체 리스트
    function get_supply_list( $str, $option = 0, &$count )
    {
       // option:0 => 전체
       // option:1 => 조건
       global $connect;

          // query 
          $query = "select * from userinfo";
          $query_cnt = "select count(*) as cnt from userinfo";

          //if ( $option )
             $option = " where name like '$str%' and level=0";  

          $result = mysql_query ( $query_cnt . $option, $connect );

          $data = mysql_fetch_array ( $result );
          $count = $data[cnt];

          $result = mysql_query ( $query . $option , $connect );

       return $result;
    }

}

?>
