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

        $transaction = $this->begin("�Ǹ�ó�� �������");

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
      // �Ǹ�ó�� �� �������

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
            <td class=header2>��ǰ�ڵ�</td>
            <td class=header2>��ǰ��</td>
            <td class=header2>�ɼ�</td>
            <td class=header2>�ǸŰ�</td>
            <td class=header2>����</td>
            <td class=header2>�Ǹž�(��)</td>
          </tr>" );

      //=====================================================
      // 
      // download format�� ���� ������ �����´�
      // 

      fwrite($handle, $result_buffer . "</table></html>\n" );

      ///////////////////////////////////////
      // file close 
      fclose($handle);

      //////////////////////////////////////
      // 
      // ���� ��ȯ�� �ؾ� �� ��� ���⼭ �ؾ� ��
      //
      $saveTarget2 = $saveTarget . "_";
      $run_module = "/usr/bin/perl /home/ezadmin/public_html/shopadmin/html2xls.pl -o $saveTarget -o $saveTarget2";
      exec( $run_module );

      header("Content-type: application/vnd.ms-excel");
      header("Content-Disposition: attachment; filename=�����ǹ���_��ڳ�����.xls");
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
