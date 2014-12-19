<?
require_once "class_top.php";
require_once "class_H.php";

////////////////////////////////
// class name: class_HC00
//

class class_HC00 extends class_top {

    ///////////////////////////////////////////

  function HC00()
  {
	global $connect;
	global $template;

    $master_code = substr( $template, 0,1);
    include "template/" . $master_code ."/" . $template . ".htm";
  }

  function faqlist()
  {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$page = $_REQUEST[page];

	$link_url = "?template=HC00&";

    if ($_REQUEST[category]) {
		$options = " where category = '$_REQUEST[category]' ";
    }

	$sql = "select count(*) cnt from sys_faq_board ${options}";
	$total = mysql_fetch_array(mysql_query($sql, $sys_connect));
	$total_rows = $total[cnt];

	$line_per_page = 50;
	$starter = $page ? ($page-1) * $line_per_page : 0;


	$sql = "select * from sys_faq_board ${options} order by input_time desc limit $starter, $line_per_page";
	$result = mysql_query($sql, $sys_connect);

	$strHTML = "
		<div style='margin-left:10px;'>
			<img src='images15/faq_list_hdr.gif' />
		</div>
		<table cellspacing=0 cellpadding=0 border=0 width=800>
  		<tr>
    		<td bgcolor='#FFFFFF' style='padding-left:20px' valign=top>";

	$strHTML .= "<table border='0' align='center' cellpadding='0' cellspacing='0' width='100%'>";

  	while ($list = mysql_fetch_array($result)) {
    	$today = date("Y-m-d");
    	$regdate = substr($list[input_time],0,10);

		$strHTML .= "
          <tr height=32 onmouseover='trover(this);' onmouseout='trout(this);'>
            <td align=center width=40>$list[no]</td>
            <td align=center width=110>$list[category]</td>
            <td>&nbsp;<a href='javascript:faqview($list[no])'>" . stripslashes( $list[subject]) . "</a></td>
            <td align=center width=110>$regdate</td>
            <td align=center width=50>$list[hit]</td>
          </tr>
          <tr><td height='1' colspan=5 bgcolor='DFDFDF'></td></tr>
		";
	}

	$strHTML .= "</table>
					</td>
				  </tr>
				</table>
				";

    echo $strHTML;
  }


  function faqview()
  {
	global $connect;
	global $template;

	$sys_connect = sys_db_connect();
	$no = $_REQUEST[no];

	$sql = "select * from sys_faq_board where no = '$no'";
	$list = mysql_fetch_array(mysql_query($sql, $sys_connect));

	$strHTML = "
<table border='0' align='center' cellpadding='0' cellspacing='0' width='800'>
  <tr height=30>
	<td width=50% align=left></td>
	<td width=50% align=right><a href='javascript:faqlist();'><img src=/images/btn_list.gif border=0></a></td>
   </tr>
</table>

<table cellspacing=0 cellpadding=0 border=0 width=800 bgcolor='#A5A5A5'>
  <tr>
    <td bgcolor='#FFFFFF' style='padding-left:10px' valign=top>
	<!--게시판-->
	<table border='0' cellpadding='0' cellspacing='0' bgcolor=#999999 width='100%'>
	  <tr>
	    <td height=1 colspan=3 bgcolor=#999999></td>
	  </tr>
	  <tr height=40> 
	    <td colspan=3 align='left' style='height:40px;text-align:center;background-color:#EFEFEF;'><font style='font-size:16px;'><b>&nbsp;" . stripslashes($list[subject]) . "</b></font></td>
	  <tr><td height=1 colspan=3 bgcolor=#CFCFCF></td></tr>
	  </tr>
	  <tr height=35 bgcolor=#FFFFFF> 
	    <td> 등록일 :&nbsp;$list[input_time]</td>
	    <td align=right>조회수 :&nbsp;$list[hit]</td>
	  </tr>
	  <tr>
	    <td height=1 colspan=3 bgcolor=CCCCCC></td>
	  </tr>
	</table>

        <table border='0' align='center' cellpadding='5' cellspacing='1' bgcolor=#FFFFFF width='100%'>
          <tr>
	    <td height=10></td>
	  </tr>
          <tr>
	    <td height=150 valign=top>" .nl2br(stripslashes($list[content])) . "</td>
          </tr>
          <tr><td height=10></td></tr>
          <tr><td height=1 bgcolor=#CCCCCC></td></tr>
        </table>

        <table border='0' align='center' cellpadding='0' cellspacing='0' width='100%'>
          <tr height=30>
            <td width=50% align=left></td>
            <td width=50% align=right><a href='javascript:faqlist();'><img src=/images/btn_list.gif border=0></a></td>
           </tr>
        </table>
	";

  	@mysql_query("update sys_faq_board set hit=hit+1 where no = '$no'", $sys_connect);

	echo $strHTML;
  }

}

?>
