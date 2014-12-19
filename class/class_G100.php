<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_stat.php";

////////////////////////////////
// class name: class_G100
//

class class_G100 extends class_top {

    ///////////////////////////////////////////

    function G100()
    {
	global $connect;
	global $template, $line_per_page;

        $transaction = $this->begin("판매처별 매출통계");

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    function download2()
    {
      global $connect, $saveTarget, $filename, $trans_corp;

      ///////////////////////////////////////
      $shop_id = $_REQUEST[shop_id];
      $start_date = $_REQUEST[start_date];
      $end_date = $_REQUEST[end_date];
      if (!$start_date) $start_date = date('Y-m-01');

      //////////////////////////////////////////////
      // 판매처별 상세 매출통계

      $sql = "select distinct product_id, product_name, avg(shop_price) shop_price, sum(qty) qty, sum(amount) amount, 
                     match_options,seq
            from orders
           where shop_id = '$shop_id'
             and collect_date >= '$start_date'
             and collect_date <= '$end_date'
             and order_cs < 5
           group by product_id
           order by amount desc";
      $result = mysql_query($sql, $connect) or die(mysql_error());

      fwrite ( $handle, "<table border=0 cellpadding=0 cellspacing=1 bgcolor='#999999' width='100%'>
          <tr>
            <td class=header2>seq</td>
            <td class=header2>상품코드</td>
            <td class=header2>상품명</td>
            <td class=header2>옵션</td>
            <td class=header2>판매가</td>
            <td class=header2>수량</td>
            <td class=header2>판매액(원)</td>
          </tr>" );

      //=====================================================
      // 
      // download format에 대한 정보를 가져온다
      // 

      fwrite($handle, $result_buffer . "</table></html>\n" );

      ///////////////////////////////////////
      // file close 
      fclose($handle);

      //////////////////////////////////////
      // 
      // 파일 변환을 해야 할 경우 여기서 해야 함
      //
      $saveTarget2 = $saveTarget . "_";
      $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      exec( $run_module );

      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=오늘의발주_대박나세요.xls");
      header("Expires: 0");
      header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
      header("Pragma: public");

      if (is_file($saveTarget2)) {
          $fp = fopen($saveTarget2, "r");
          fpassthru($fp);
      }

      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);

      unlink($saveTarget);
      unlink($saveTarget2);
      exit;
    }
}

?>
