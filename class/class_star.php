<?
// date: 2006.7.19
// jk.ryu
class class_star{

  // date: 2006.7.19
  // 스타들의 리스트 출력
  // option에서 선택된 star가 있을 경우 $star_code에 값이 passing되어 온다.
  function disp_option_star( $star_code )
  {
      global $connect;

      if ( $star_code )
          $sel_code[$star_code] = "selected";

      $query = "select id,name,code from userinfo where level=1";

      $result = mysql_query ( $query, $connect );
      echo "<select name=star_code>
            <option value=''> 스타선택 </option> \n";
      while ( $data = mysql_fetch_array ( $result ) )
      {
          echo "<option value=$data[code] " .  $sel_code[$data[code]] .  ">$data[name]</option>\n";
      }
      echo "</select>";
  }

  // 이름 출력
  // date: 2006.7.28 - jk.ryu
  function get_name( $star_code )
  {
    global $connect;

    $query = "select id,name,code from userinfo where code='$star_code'";
    $result = mysql_query ( $query, $connect );
    $data = mysql_fetch_array ( $result );
    return $data[name];
  }

}

?>
