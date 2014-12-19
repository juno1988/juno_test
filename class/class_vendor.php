<?
// date: 2006.7.25
// jk.ryu
class class_vendor{

  // date: 2006.7.25
  // 스타들의 리스트 출력
  // option에서 선택된 md 가 있을 경우 $star_code에 값이 passing되어 온다.
  function disp_option_vendor( $code="", $disabled = 0 )
  {
      global $connect;

      // vendor가 하나의 업체만을 볼 경우
      if ( $disabled ) 
      {
        $query = "select id,name,code from userinfo where level=0 and code='$code'";
        $result = mysql_query ( $query, $connect );

        $data = mysql_fetch_array ( $result );
        echo "<input type=hidden name=supply_code value='$data[code]'>$data[name]";
        return;
      }

      // 관리자가 전체를 볼 경우
      if ( $code )
          $sel_code[$code] = "selected";

      $query = "select id,name,code from userinfo where level=0";
      $result = mysql_query ( $query, $connect );

      echo "<select name=supply_code>
            <option value=''> 공급처 </option> \n";

      while ( $data = mysql_fetch_array ( $result ) )
      {
          echo "<option value=$data[code] " .  $sel_code[$data[code]] .  ">$data[name]</option>\n";
      }
      echo "</select>";
  }

  function get_name( $code="" )
  {
      global $connect;
      
      $query = "select name from userinfo where code='$code'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );
      return $data[name];
  }

  // 기본 수수료 
  function get_default_transfee( $code = "" )
  {
    global $connect;
      
      $query = "select trade_fee from userinfo where code='$code'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );
      return $data[trade_fee];
  }
}

?>
