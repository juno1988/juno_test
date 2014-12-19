<?
require_once "class_top.php";
///////////////////////////////////////////
//
// build date: 2008.8.14 - jk
// 판매처 cs관련 
//
class class_cs 
{
    ////////////////////////////////////////
    // 미처리 cs주문 리스트 조회
    // date: 2008.5.26 - jk.ryu
    // status: 0: 미처리 / 1: 처리완료
    function get_list( $status=0 )
    {
        global $connect;
        $query = "select a.* from orders a, csinfo b 
                   where a.seq = b.order_seq
                     and (a.seq=a.pack or a.pack is null or a.pack='')
                     and b.cs_result = $status ";
        $result = mysql_query( $query, $connect );
        return $result;
    }

    /////////////////////////////////////////
    // 완료처리
    // date: 2008.5.26 - jk
    function set_complete( $order_cs, $seq, $is_bck=0 )
    {
        global $connect, $bck_connect;

        // cs의 상태 변경
        $query = "update csinfo 
                     set cs_result = 1, 
                         complete_date=if(complete_date>0,complete_date,now() )
                   where order_seq='$seq'";
        mysql_query ( $query, $connect);
        if( $is_bck )
            mysql_query ( $query, $bck_connect);
    }

    ////////////////////////////////////////
    // cs상태 변경.
    // date: 2008.5.1 - jk.ryu
    // 
    function change_status()
    {
        global $connect, $seq, $status, $order_cs;
        $val = array();

        $query  = "select status, order_cs from orders where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );
        $msg    = "[주문 상태 변경]이전 상태: $data[status] / $data[order_cs] ";
        class_cs::begin( $msg, $seq );

        $query = "update orders set status=$status, order_cs=$order_cs where seq=$seq";
        mysql_query ( $query, $connect );

        $val = array();
        $val[msg] = iconv( 'cp949', 'utf-8', "변경 완료");
        echo json_encode( $val );
    }

    ////////////////////////////////////////
    // cs상태 변경만 return없음.
    // date: 2008.8.7 - jk.ryu
    // 
    function change_status2( $seq, $status, $order_cs)
    {
        global $connect;
        $val = array();

        $query  = "select status, order_cs from orders where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );
        $msg    = "[주문 상태 변경]이전 상태: $data[status] / $data[order_cs] ";
        class_cs::begin( $msg, $seq );

        $query = "update orders set status=$status, order_cs=$order_cs where seq=$seq";
        mysql_query ( $query, $connect );
    }
    //
    // transaction 관련 function
    //
    function begin( $msg, $seq='' )
    {
      global $template, $connect, $_SESSION;

      $query = "insert into transaction 
                        set template    = '$template', 
                            commit_date = Now(), 
                            starttime   = Now(), 
                            owner       = '" . $_SESSION[LOGIN_ID] . "', 
                            target_id   = '$seq',
                            status      = '$msg'";
        mysql_query ( $query, $connect );
    }

    function session_check()
    {
        print_r ( $_SESSION );
    }

    ////////////////////////////////////////
    // cs내역을 남긴다.
    // date: 2008.4.29 - jk.ryu
    // 
    function csinsert($seq, $content, $writer="")
    {
        global $connect, $_SESSION;

        $writer = $_SESSION[LOGIN_NAME] ? $_SESSION[LOGIN_NAME] : $writer;

        $sql = "insert into csinfo set 
                  order_seq  = '$seq',
                  input_date = now(),
                  input_time = now(),
                  writer     = '$writer',
                  cs_result  = '1',
                  content    = '$content'";

        mysql_query ( $sql, $connect );
    }

  //=========================================
  //
  // cs 관련 정보 출력
  function disp_cshistory( $seq )
  {
        global $connect;

        $query = "select * from csinfo where order_seq=$seq order by input_date desc, input_time desc";

        $result = mysql_query ( $query, $connect );

        echo "<table cellspacing=0 cellpadding=0 border=0 style='padding:2px' width=100%>";

        while ( $data = mysql_fetch_array( $result ) )
        {
        ?>

    <tr>                 
      <td width=92> 상태: [<?= $data[cs_result] ? "처리완료" : "미처리" ?>] </td>
      <td>· 접수일시 : <?=$data[input_date]?> <?=$list2[input_time]?></td>
      <td>· 접수자 : <b><?=$data[writer]?></b></td>
    </tr>          
    <tr>
      <td colspan=4>· <b>내용</b> : <?=nl2br($data[content])?></td>
    </tr>
    <tr><td height=1 bgcolor=#CFCFCF colspan=4></td></tr>

        <?
        }
        echo "</table>";
  }


  function cs_link()
  {
        global $shop_id, $order_id;

        $shop_code = $shop_id % 100;
        echo "cs_link $shop_code / $order_id ";
        $func = "shop_" . $shop_code;

        echo " / ${func} ";

        // $this->${func}();

        print_r ( get_class_methods ( $this ) );

        echo "<br>=-===================<br>";

        if ( in_array( "shop_50", get_class_methods ( $this ) ) ) 
           echo "exist";
        else
           echo "non exist";

  }

  // =========
  // auction
  // 2008.8.13 - jk
  function shop_1()
  {
        global $order_id;
?>
<form method=post name='myform' action="http://escrow.auction.co.kr/Shipment/DeliveryListForSeller.aspx">
        <input type=text name=txtItem value='<?= $order_id ?>'>
        <input type=text name=ddlItemSearch value='6'>
        <input type=text name=__VIEWSTATE value="dDwxNTgyNTI0MTUzO3Q8O2w8aTwyPjtpPDM+Oz47bDx0PHA8bDxWaXNpYmxlOz47bDxvPGY+Oz4+Ozs+O3Q
8O2w8aTw1PjtpPDc+O2k8MjU+O2k8MzE+Oz47bDx0PHA8cDxsPFRleHQ7PjtsPDI3Oz4+Oz47Oz47dDxwPHA8bDxUZXh0Oz47bDwyNDs+Pjs+Ozs+O3Q8O2w8aTwwPjtpPDE
+O2k8Mj47aTwzPjs+O2w8dDx0PHA8cDxsPERhdGFUZXh0RmllbGQ7RGF0YVZhbHVlRmllbGQ7PjtsPENhdGVnb3J5TmFtZTtDYXRlZ29yeUlEOz4+O3A8bDxvbkNoYW5nZTs
+O2w8R2V0SW5mbyh0aGlzKTs+Pj47dDxpPDM5PjtAPC0tLS0tLSDsoITssrQgLS0tLS0tO+uqqOuLiO2EsC/tlITrprDthLAv67aA7ZKIO+uUlOy5tC9EU0xSL+uUlOy6oDv
sg53tmZwv6rOE7KCIL+ydtOuvuOyaqTvrhKTruYQv7LCo65+J7JqpQVY77ZW07Jm47ZmU7J6l7ZKIL+2WpeyImDvtlbjrk5ztj7Av7JWh7IS47ISc66asO+qzqO2UhO2BtOu
fvS/snZjrpZgv7Jqp7ZKIO+u2hOycoC/quLDsoIDqt4Av66y87Yuw7IqIO+usuOq1rC/sgqzrrLQv7Jqp7KeAO+yXrOyEseydmOulmDvrgqjshLHsnZjrpZg7TVAzL1BNUC/
sgqzsoIQ76rKM7J6E6riwL+2DgOydtO2LgDvqsIDrsKkv7Yyo7IWY7J6h7ZmUO+ylrOyWvOumrC/si5zqs4Q76rWt64K07ZmU7J6l7ZKIO+qxtOqwlS/snYzro4wv6rCA6rO
17Iud7ZKIO+yepeuCnOqwkC/tlZnsirXsmYTqtaw77Iqk7Y+s7LigL+ugiOyggDvrhbjtirjrtoEv642w7Iqk7YGs7YORO+y5qOq1rC/su6Ttirwv7J6l7Iud7IaM7ZKIO+y
KpO2MjC/thYzrp4jsnbTsmqnqtow77Yyo7IWY67iM656c65OcO+qwgOq1rC/snbjthYzrpqzslrQ77KO867Cp7IOd7ZmcL+yaleyLpOyImOuCqTtUVi/rg4nsnqXqs6Av7IS
47YOB6riwO+yLoOuwnC/qtazrkZAv7Jq064+Z7ZmUO+y2nOyCsC/snKDslYTsg53tmZzsmqntkog77JWE64+Z67O1L+yeoe2ZlC/snoTrtoDrs7U76rG06rCV7Jqp7ZKIL+u
5hOuNsC/slaDsmYQ77Jyg7JWE64+ZIOyghOynkS/ssLjqs6DshJw767Cx7ZmU7KCQL+ygnO2ZlOyDge2SiOq2jDvst6jrr7gv7JWF6riwL+2UhOudvOuqqOuNuDvsjIAv6rO
87J28L+uGjeyImOy2leyCsOusvDvsgrDsl4Xsmqntkogv6rO16rWsL+q4sOqzhDvsnpDrj5nssKjsmqntkog764+E7ISc7J2M67CYKOyxheqzvOydjOyVhSk76r2DL+2MrOy
LnC/shJzruYTsiqQ7PjtAPDA7MDEwMDAwMDA7MDIwMDAwMDA7MDMwMDAwMDA7MDQwMDAwMDA7MDUwMDAwMDA7MDcwMDAwMDA7MDgwMDAwMDA7MDkwMDAwMDA7MTAwMDAwMDA
7MTIwMDAwMDA7MTMwMDAwMDA7MTQwMDAwMDA7MTUwMDAwMDA7MTYwMDAwMDA7MTcwMDAwMDA7MTgwMDAwMDA7MTkwMDAwMDA7MjAwMDAwMDA7MjEwMDAwMDA7MjIwMDAwMDA
7MjMwMDAwMDA7MjQwMDAwMDA7MjUwMDAwMDA7MjcwMDAwMDA7MjgwMDAwMDA7MjkwMDAwMDA7MzAwMDAwMDA7MzEwMDAwMDA7MzIwMDAwMDA7MzMwMDAwMDA7MzYwMDAwMDA
7NDEwMDAwMDA7NDYwMDAwMDA7NTEwMDAwMDA7NTUwMDAwMDA7NTcwMDAwMDA7ODEwMDAwMDA7OTkwMDAwMDA7Pj47Pjs7Pjt0PHQ8cDw7cDxsPG9uQ2hhbmdlOz47bDxHZXR
JbmZvKHRoaXMpOz4+Pjt0PGk8MT47QDwtLS0tLS0g7KCE7LK0IC0tLS0tLTs+O0A8MDs+Pjs+Ozs+O3Q8dDxwPDtwPGw8b25DaGFuZ2U7PjtsPEdldEluZm8odGhpcyk7Pj4
+O3Q8aTwxPjtAPC0tLS0tLSDsoITssrQgLS0tLS0tOz47QDwwOz4+Oz47Oz47dDx0PHA8O3A8bDxvbkNoYW5nZTs+O2w8R2V0SW5mbyh0aGlzKTs+Pj47dDxpPDE+O0A8LS0
tLS0tIOyghOyytCAtLS0tLS07PjtAPDA7Pj47Pjs7Pjs+Pjt0PDE7bDxpPDc+Oz47bDx0PHA8bDxWaXNpYmxlOz47bDxvPGY+Oz4+Ozs+Oz4+Oz4+Oz4+O2w8YnRuU2VhcmN
oO0l0ZW1zTGlzdFJlcGVhdGVyOl9jdGwxOmJ0blNlbmRHb29kO0l0ZW1zTGlzdFJlcGVhdGVyOl9jdGwxOmJ0bkNhbmNlbDtJdGVtc0xpc3RSZXBlYXRlcjpfY3RsMTpidG5
SZWplY3Q7Pj4cBD7IPNuDhSYjZ/1pOuPwGlHlUQ==">

</form>

<script language="javascript">
    myform.submit()
</script>
<?
  }

  // gmarket
  function shop_2()
  {

  }

  // 11번가
  function shop_50()
  {
      echo "haha";
  }

}
?>
