<?
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_stock.php";

//////////////////////////////////////////////
// 입고전표
class class_I300 extends class_top
{
   // 입고전표 목록
   function I300()
   {
      global $template, $connect, $page;
      global $start_date, $end_date, $bad_type, $ret_type, $string, $wh;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-14 day'));
      if (!$end_date) $end_date = date('Y-m-d');

      // 상세 정보 가져온다
      $query = "select * from sheet_in where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' ";
      if( $bad_type )
        $query .= " and bad=$bad_type-1 ";
      if( $ret_type )
        $query .= " and ret=$ret_type-1 ";
      if( $string )
        $query .= " and title like '%$string%'";
      if( $wh )
      {
        if( $wh == 'base' )
            $query .= " and wh = '' ";
        else
            $query .= " and wh = '$wh' ";
      }
      
      $query .= " order by seq desc";

      // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );

      $link_url = "?" . $this->build_link_url();

      // 페이지
      if(!$page) $page=1;
      
      $line_per_page = 50;

      $starter = ($page-1) * $line_per_page;
      $limit = " limit $starter, $line_per_page";

      $result = mysql_query($query . $limit, $connect);

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 입고전표 상세
   function I301()
   {
      global $template, $connect, $page, $sheet, $wh;

      if( $wh )
      {
          $query = "select 	 seq       	a_seq
							,product_id	a_product_id
							,crdate    a_crdate
							,job       a_job
							,qty       a_qty
							,owner     a_owner
							,location  a_location
							,stock     a_stock
							,memo      a_memo
							,bad       a_bad
							,sheet     a_sheet
							,order_seq a_order_seq
							,org_price a_org_price
							,wh        a_wh
					  from stock_tx_history_wh
				     where sheet=$sheet
				       and job in ('in', 'retin')
				  order by seq desc";
          $query_price = "select sum(qty*org_price) sum from stock_tx_history_wh where sheet=$sheet and job in ('in', 'retin') and wh='$wh'";
      }
      else
      {   
            $query = "select a.seq a_seq
							,a.product_id a_product_id
							,sum(a.qty) a_qty
							,a.org_price a_org_price
							,a.memo a_memo
							,a.crdate a_crdate
							,a.owner a_owner
							,if(a.sub_seq>0,a.sub_seq,a.seq) s_seq
							,b.org_price b_org_price
							,b.extra_price b_extra_price
                        from stock_tx_history a, products b 
                       where a.product_id = b.product_id 
                         and a.job in ('in', 'retin') 
                         and a.sheet in ($sheet)  
                    group by s_seq 
                    order by a_seq " . (_DOMAIN_ == 'au2' ? "" : " desc");
                    
                     
                     
debug("입고전표상세 : " . $query);
          $query_price = "select sum(qty*org_price) sum from stock_tx_history where sheet=$sheet and job in ('in', 'retin')";
      }

      // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );

      // 전체 금액
      $result_price = mysql_query($query_price, $connect);
      $data_price = mysql_fetch_assoc($result_price);
      $total_price = $data_price[sum];
      $link_url = "?" . $this->build_link_url();

      $result = mysql_query($query, $connect);

      $product_obj = new class_product();
      $supply_obj  = new class_supply();
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 입고전표 상세
   function I30a()
   {
      global $template, $connect, $page, $sheet, $wh;

      if( $wh )
      {
          $query = "select * from stock_tx_history_wh where sheet=$sheet and job in ('in', 'retin') order by seq desc";
          $query_price = "select sum(qty*org_price) sum from stock_tx_history_wh where sheet=$sheet and job in ('in', 'retin') and wh='$wh'";
      }
      else
      {
          $query = "select * from stock_tx_history where sheet=$sheet and job in ('in', 'retin') order by seq desc";
          $query_price = "select sum(qty*org_price) sum from stock_tx_history where sheet=$sheet and job in ('in', 'retin')";
      }

      // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );

      // 전체 금액
      $result_price = mysql_query($query_price, $connect);
      $data_price = mysql_fetch_assoc($result_price);
      $total_price = $data_price[sum];
      
      $link_url = "?" . $this->build_link_url();

      $result = mysql_query($query, $connect);

      $product_obj = new class_product();
      $supply_obj  = new class_supply();
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 입고전표 제목변경
   function I302()
   {
      global $template, $connect, $sheet;

      $query = "select * from sheet_in where seq=$sheet";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_assoc($result);
      
      $title = $data[title];
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 출력하기
   function I303()
   {
      global $template, $connect, $sheet, $wh;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 원가 변경
   function I304()
   {
      global $template, $connect, $seq;

      $query = "select * from stock_tx_history where seq=$seq";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_assoc($result);
      
      $product_id = $data[product_id];
      $org_price = $data[org_price];
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 수량 변경
   function I306()
   {
      global $template, $connect, $seq;

      $query = "select sum(qty) sum_qty from stock_tx_history where seq=$seq or sub_seq=$seq";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_assoc($result);
      
      $product_id = $data[product_id];
      $sum_qty = $data[sum_qty];
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 출력하기
   function I305()
   {
      global $template, $connect, $sheet, $wh;

      $query = "select * from sheet_in where seq=$sheet";
      $result = mysql_query($query, $connect);
      $data_sheet = mysql_fetch_assoc($result);
      
      if( $wh )
      {
          // 전체 수량
          $query = "select * from stock_tx_history_wh where sheet=$sheet and job in ('in', 'retin') and wh='$wh' order by seq desc";
          $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
          $result = mysql_query($query, $connect);
    
          // 전체 금액
          $query_price = "select sum(qty*org_price) sum from stock_tx_history_wh where sheet=$sheet and job in ('in', 'retin') and wh='$wh'";
          $result_price = mysql_query($query_price, $connect);
          $data_price = mysql_fetch_assoc($result_price);
          $total_price = $data_price[sum];
      }
      else
      {
          $query = "select seq
                          ,product_id
                          ,sum(qty) qty
                          ,org_price
                          ,memo
                          ,crdate
                          ,owner
                          ,if(sub_seq>0,sub_seq,seq) s_seq
                      from stock_tx_history 
                     where sheet=$sheet 
                       and job in ('in', 'retin') 
                     group by s_seq
                     order by seq desc";


          $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
          $result = mysql_query($query, $connect);
    
          // 전체 금액
          $query_price = "select sum(qty*org_price) sum from stock_tx_history where sheet=$sheet and job in ('in', 'retin')";
          $result_price = mysql_query($query_price, $connect);
          $data_price = mysql_fetch_assoc($result_price);
          $total_price = $data_price[sum];
      }
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    function I320()
    {
        global $template, $connect, $sheet;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
   
    function I321()
    {
        global $template, $connect, $sheet;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
   
    function change_org_price()
    {
        global $connect, $seq, $product_id, $org_price, $price_sync;
        
        $query = "update stock_tx_history set org_price=$org_price where seq=$seq";
        mysql_query($query, $connect);
        
        if( $price_sync )
        {
            // 옵션상품
            if( substr($product_id,0,1) == 'S' )
            {
                // org_id 
                $query = "select org_id from products where product_id='$product_id'";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);
                $org_id = $data[org_id];
                
                $query = "update products set org_price=$org_price where product_id='$org_id' or org_id='$org_id' ";
                mysql_query($query, $connect);
                
            }
            else
            {
                $org_id = $product_id;
                $query = "update products set org_price=$org_price where product_id='$org_id'";
                mysql_query($query, $connect);
            }
            $query = "update price_history set org_price=$org_price where product_id='$org_id' and shop_id=0";
            mysql_query($query, $connect);
        }

        echo "<script> opener.ref(); self.close();</script>";
    }
    
    function change_sum_qty()
    {
        global $connect, $seq, $sum_qty;
        
        $query = "select sum(qty) sum_qty, product_id, job, bad, sheet, if(sub_seq>0,sub_seq,seq) s_seq from stock_tx_history where seq=$seq or sub_seq=$seq group by s_seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        if( $data[sum_qty] != $sum_qty )
        {
            $change_qty = $sum_qty - $data[sum_qty];
            
            $info_arr = array(
                type       => $data[job],
                product_id => $data[product_id],
                bad        => $data[bad],
                location   => 'Def',
                qty        => $change_qty,
                sub_seq    => $seq,
                memo       => "전표수정($data[sheet])"
            );
    
            $obj = new class_stock();
            $obj->set_stock($info_arr, $_SESSION[LOGIN_NAME], $data[sheet]);
            
        }

        echo "<script> opener.ref(); self.close();</script>";
    }
    
    function change_title_in()
    {
        global $connect, $sheet, $title;
        
        $query = "update sheet_in set title='$title' where seq=$sheet";
        mysql_query($query, $connect);
        
        echo "<script>opener.change_title('$title');self.close()</script>";
    }

    function del_sheet_in()
    {
        global $connect, $sheet;
        
        $query = "delete from sheet_in where seq=$sheet";
        mysql_query($query, $connect);
        debug("전표삭제 : $query");
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 [전체]
    function save_file_all()
    {
        global $template, $connect, $sheet, $start_date, $end_date, $bad_type, $ret_type, $string, $sheet_each;
        
        if( $_SESSION[MULTI_WH] )
        {
            //#############
            // 기본
            //#############

            // 상세 정보 가져온다
            $query = "select * from sheet_in where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' and wh=''";
            if( $bad_type )
                $query .= " and bad=$bad_type-1 ";
            if( $ret_type )
                $query .= " and ret=$ret_type-1 ";
            if( $string )
                $query .= " and title like '%$string%'";
    
            $result = mysql_query($query, $connect);
            
            $sht = '';
            while( $data = mysql_fetch_assoc($result) )
                $sht .= ($sht ? "," : "" ) . $data[seq];
            
            $query = "select a.product_id a_product_id, 
                             b.name b_name,
                             b.brand b_brand,
                             b.options b_options,
                             sum(a.qty) a_qty_sum,
                             b.org_price b_org_price,
                             b.extra_price b_extra_price,
                             b.reg_date b_reg_date
                        from stock_tx_history a, products b
                       where a.product_id = b.product_id and
                             a.job in ('in', 'retin') and 
                             a.sheet in ($sht) 
                    group by a.product_id 
                    order by b.supply_code, b.name, b.options";
            $result = mysql_query($query, $connect);
            
            $arr = array();
            $supply_obj  = new class_supply();
            while($data = mysql_fetch_assoc($result))
            {
                $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                
                $arr[] = array(
                    wh           => "&nbsp;",
                    supply_name  => $supply_name,
                    product_id   => $data[a_product_id],
                    name         => $data[b_name],
                    brand        => $data[b_brand],
                    options      => $data[b_options],
                    qty          => $data[a_qty_sum],
                    org_price    => $data[b_org_price] + $data[b_extra_price],
                    reg_date     => $data[b_reg_date] );
            }

            //#############
            // 창고
            //#############

            $query_wh = "select * from warehouse order by name";
            $result_wh = mysql_query($query_wh, $connect);
            while( $data_wh = mysql_fetch_assoc($result_wh) )
            {
                $wh = $data_wh[name];
                
                // 상세 정보 가져온다
                $query = "select * from sheet_in where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' and wh='$wh'";
                if( $bad_type )
                    $query .= " and bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and ret=$ret_type-1 ";
                if( $string )
                    $query .= " and title like '%$string%'";
        
                $result = mysql_query($query, $connect);
                
                $sht = '';
                while( $data = mysql_fetch_assoc($result) )
                    $sht .= ($sht ? "," : "" ) . $data[seq];
                
                $query = "select a.product_id a_product_id, 
                                 b.name b_name,
                                 b.brand b_brand,
                                 b.options b_options,
                                 sum(a.qty) a_qty_sum,
                                 b.org_price b_org_price,
                                 b.extra_price b_extra_price,
                                 b.reg_date b_reg_date
                            from stock_tx_history_wh a, products b
                           where a.product_id = b.product_id and
                                 a.job in ('in', 'retin') and 
                                 a.sheet in ($sht) and
                                 a.wh = '$wh'
                        group by a.product_id 
                        order by b.supply_code, b.name, b.options";
                $result = mysql_query($query, $connect);
                
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                    
                    $arr[] = array(
                        wh           => $wh,
                        supply_name  => $supply_name,
                        product_id   => $data[a_product_id],
                        name         => $data[b_name],
                        brand        => $data[b_brand],
                        options      => $data[b_options],
                        qty          => $data[a_qty_sum],
                        org_price    => $data[b_org_price] + $data[b_extra_price],
                        reg_date     => $data[b_reg_date] );
                }
            }
        }
        else
        {
            if( $sheet_each )
            {
                $query = "select a.title  a_title
                                ,c.org_id     c_org_id
                                ,c.product_id c_product_id
                                ,c.name       c_name
                                ,c.brand      c_brand
                                ,c.options    c_options
                                ,sum(b.qty)   b_qty
                                ,c.org_price  c_org_price
                                ,c.extra_price c_extra_price
                                ,c.reg_date   c_reg_date
                                ,b.crdate     b_crdate
                                ,c.barcode    c_barcode
                                ,if(b.sub_seq>0,b.sub_seq,b.seq) s_seq
                            from sheet_in a
                                ,stock_tx_history b
                                ,products c
                           where a.seq = b.sheet
                             and b.job in ('in', 'retin')
                             and b.product_id = c.product_id
                             and a.crdate >= '$start_date 00:00:00' 
                             and a.crdate <= '$end_date 23:59:59' ";
                if( $bad_type )
                    $query .= " and a.bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and a.ret=$ret_type-1 ";
                if( $string )
                    $query .= " and a.title like '%$string%'";
                    
                $query .= " group by s_seq ";
                $query .= " order by a.seq, c.name ";
                $result = mysql_query($query, $connect);
                
                $arr = array();
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[c_product_id] );
                    
                    $arr[] = array(
                        sheet_name   => $data[a_title],
                        supply_name  => $supply_name,
                        org_id       => $data[c_org_id],
                        product_id   => $data[c_product_id],
                        name         => $data[c_name],
                        brand        => $data[c_brand],
                        options      => $data[c_options],
                        qty          => $data[b_qty],
                        org_price    => $data[c_org_price]+$data[c_extra_price],
                        barcode      => $data[c_barcode],
                        stock_qty    => class_stock::get_current_stock( $data[c_product_id], 0 ),
                        reg_date     => $data[c_reg_date],
                        crdate       => $data[b_crdate] 
                    );
                }
            }
            else
            {
                // 상세 정보 가져온다
                $query = "select * from sheet_in where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' ";
                if( $bad_type )
                    $query .= " and bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and ret=$ret_type-1 ";
                if( $string )
                    $query .= " and title like '%$string%'";
        
                $result = mysql_query($query, $connect);
                
                $sht = '';
                while( $data = mysql_fetch_assoc($result) )
                    $sht .= ($sht ? "," : "" ) . $data[seq];
                
                $query = "select b.org_id b_org_id, 
                                 a.product_id a_product_id,                                  
                                 b.name b_name,
                                 b.brand b_brand,
                                 b.options b_options,
                                 sum(a.qty) a_qty_sum,
                                 b.org_price b_org_price,
                                 b.extra_price b_extra_price,
                                 b.reg_date b_reg_date
                            from stock_tx_history a, products b
                           where a.product_id = b.product_id and
                                 a.job in ('in', 'retin') and 
                                 a.sheet in ($sht) 
                        group by a.product_id 
                        order by b.supply_code, b.name, b.options";
                $result = mysql_query($query, $connect);
                
                $arr = array();
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                    
                    $arr[] = array(
                        supply_name  => $supply_name,
                        org_id       => $data[b_org_id],
                        product_id   => $data[a_product_id],
                        name         => $data[b_name],
                        brand        => $data[b_brand],
                        options      => $data[b_options],
                        qty          => $data[a_qty_sum],
                        org_price    => $data[b_org_price] + $data[b_extra_price],
                        reg_date     => $data[b_reg_date] );
                }
            }
        }
        
        $fn = $this->make_file_all( $arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    function make_file_all( $arr_datas )
    {
        global $sheet_each;

        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);

        if( $_SESSION[MULTI_WH] )
        {
            $_arr = array(
                "창고"
                ,"공급처"
                ,"상품코드"
                ,"상품명"
                ,"사입처상품명"
                ,"옵션"
                ,"수량"
                ,"원가"
                ,"상품등록일"
            );
        }
        else if($sheet_each)
        {
            $_arr = array(
                "전표명"
               ,"공급처"
               ,"대표코드"
               ,"상품코드"
               ,"상품명"
               ,"사입처상품명"
               ,"옵션"
               ,"수량"
               ,"원가"
               ,"바코드"
               ,"재고수량"
               ,"상품등록일"
               ,"작업일"
            );
        }
        else
        {
            $_arr = array(
                "공급처"
                ,"대표코드"
                ,"상품코드"
                ,"상품명"
                ,"사입처상품명"
                ,"옵션"
                ,"수량"
                ,"원가"
                ,"상품등록일"
            );
        }
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
        {
            if( _DOMAIN_ == 'au2' && $value == '원가' )  continue;
            $buffer .= "<td style='$style'>" . $value . "</td>";
        }
        
        $buffer .= "\n</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
        $style2 = 'font:9pt "굴림"; white-space:nowrap;';
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";

            // for column
            foreach ( $row as $key=>$value) 
            {
                if( _DOMAIN_ == 'au2' && $key == 'org_price' )  continue;

                if( $key == 'org_id' || $key == 'product_id' || $key == 'crdate' || $key == 'reg_date' )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
            }
            $buffer .= "\n</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $template, $connect, $sheet, $wh;
        
        // get list from common module
        if( $wh )
        {
             $query = "select 	 seq       	a_seq
							,product_id	a_product_id
							,crdate    a_crdate
							,job       a_job
							,qty       a_qty
							,owner     a_owner
							,location  a_location
							,stock     a_stock
							,memo      a_memo
							,bad       a_bad
							,sheet     a_sheet
							,order_seq a_order_seq
							,org_price a_org_price
							,wh        a_wh
					  from stock_tx_history_wh
				     where sheet=$sheet
				       and job in ('in', 'retin')
				  order by seq desc";
		}
        else
        {
           $query = "select a.seq a_seq
					,a.product_id a_product_id
					,sum(a.qty) a_qty
					,a.org_price a_org_price
					,a.memo a_memo
					,a.crdate a_crdate
					,a.owner a_owner
					,if(a.sub_seq>0,a.sub_seq,a.seq) s_seq
					,b.org_price b_org_price
					,b.extra_price b_extra_price
                from stock_tx_history a, products b 
               where a.product_id = b.product_id 
                 and a.job in ('in', 'retin') 
                 and a.sheet in ($sheet)  
            group by s_seq 
            order by a_seq " . (_DOMAIN_ == 'au2' ? "" : " desc");
                     
        }

        $result = mysql_query($query, $connect);
        
        $arr = array();
        $obj = new class_product();
        $supply_obj  = new class_supply();
        
        while($data = mysql_fetch_assoc($result))
        {
            $info = $obj->get_info($data[a_product_id]);
            $supply_info = $supply_obj->get_info( $info[supply_code] );

            $arr[] = array(
                supply_name  => $supply_info[name],
                supply_tel   => $supply_info[tel] . " / " . $supply_info[mobile],
                supply_address  => $supply_info[address1] . " " . $supply_info[address2],
                brand        => $info[brand],
                supply_options => $info[supply_options],
                org_id       => $info[org_id],
                product_id   => $data[a_product_id],
                name         => $info[name],
                options      => $info[options],
                qty          => $data[a_qty],
                org_price    => $data[a_org_price],
                price        => $data[a_qty] * $data[a_org_price],
                shop_price   => $info[shop_price] + $data[b_extra_price],
                tot_shop_price => $data[a_qty] * ($info[shop_price] + $data[b_extra_price]),
                reg_date     => $info[reg_date],
                memo         => $data[a_memo],
                barcode      => $info[barcode],
                stock_qty    => class_stock::get_current_stock( $data[a_product_id], 0 ),
                crdate       => $data[a_crdate],
                worker       => $data[a_owner] );
        }
        
        $fn = $this->make_file( $arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        $_arr = array(
            "공급처"
            ,"공급처전화번호"
            ,"공급처주소"
            ,"공급처상품명"
            ,"공급처옵션"
            ,"대표코드"
            ,"상품코드"
            ,"상품명"
            ,"옵션"
            ,"수량"
            ,"원가"
            ,"총원가"
            ,"판매가"
            ,"총판매가"
            ,"상품등록일"
            ,"재고메모"
            ,"바코드"
            ,"재고수량"
            ,"작업일"
            ,"작업자"
        );
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
        {
            if( $_SESSION[MULTI_WH] )
            {
                if( $value == '원가' || $value == '총원가' )  continue;
            }
            $buffer .= "<td style='$style'>" . $value . "</td>";
        }
        
        $buffer .= "\n</tr>\n";
        fwrite($handle, $buffer);

        // for row
        $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
        $style2 = 'font:9pt "굴림"; white-space:nowrap;';
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";

            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $_SESSION[MULTI_WH] )
                {
                    if($key == 'org_price' || $key == 'price')  continue;
                }

                if( $key == 'org_id' || $key == 'product_id' || $key == 'crdate' || $key == 'reg_date' || $key == 'brand' )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
            }
            $buffer .= "\n</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename, $new_name;
        
        if( !$new_name )  $new_name = "stock_data.xls";
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }    

   // 출고전표 목록
   function I310()
   {
      global $template, $connect, $page;
      global $start_date, $end_date, $bad_type, $ret_type, $string, $wh;

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-14 day'));
      if (!$end_date) $end_date = date('Y-m-d');

      // 상세 정보 가져온다
      $query = "select * from sheet_out where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' ";
      if( $bad_type )
        $query .= " and bad=$bad_type-1 ";
      if( $ret_type )
        $query .= " and ret=$ret_type-1 ";
      if( $string )
        $query .= " and title like '%$string%'";
      if( $wh )
      {
        if( $wh == 'base' )
            $query .= " and wh = '' ";
        else
            $query .= " and wh = '$wh' ";
      }
      
      $query .= " order by seq desc";

      // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );

      $link_url = "?" . $this->build_link_url();

      // 페이지
      if(!$page) $page=1;
      $line_per_page = 50;
      $starter = ($page-1) * $line_per_page;
      $limit = " limit $starter, $line_per_page";

      $result = mysql_query($query . $limit, $connect);

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 출고전표 상세
   function I311()
   {
      global $template, $connect, $page, $sheet, $wh;

      if( $wh )
      {
          $query = "select seq       	a_seq
							,product_id	a_product_id
							,crdate    a_crdate
							,job       a_job
							,qty       a_qty
							,owner     a_owner
							,location  a_location
							,stock     a_stock
							,memo      a_memo
							,bad       a_bad
							,sheet     a_sheet
							,order_seq a_order_seq
							,org_price a_org_price
							,wh        a_wh
					 from stock_tx_history_wh
				    where sheet=$sheet
				      and job in ('out', 'retout')
				 order by seq desc";
      }
      else
      {
          
			$query = "select a.seq a_seq
							,a.product_id a_product_id
							,sum(a.qty) a_qty
							,a.org_price a_org_price
							,a.memo a_memo
							,a.crdate a_crdate
							,a.owner a_owner
							,if(a.sub_seq>0,a.sub_seq,a.seq) s_seq
							,b.org_price b_org_price
							,b.extra_price b_extra_price
                        from stock_tx_history a, products b 
                       where a.product_id = b.product_id 
                         and a.job in ('out', 'retout') 
                         and a.sheet in ($sheet)  
                    group by s_seq 
                    order by a_seq " . (_DOMAIN_ == 'au2' ? "" : " desc");
      }

      // 전체 수량
      $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );

      $link_url = "?" . $this->build_link_url();

      // 페이지
      if(!$page) $page=1;

      if(_DOMAIN_ == 'au2')
        $line_per_page = 1000000;
      else
        $line_per_page = 50;

      $starter = ($page-1) * $line_per_page;
      $limit = " limit $starter, $line_per_page";

      $result = mysql_query($query . $limit, $connect);

      $product_obj = new class_product();
      $supply_obj  = new class_supply();
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   // 출고전표 제목변경
   function I312()
   {
      global $template, $connect, $sheet;

      $query = "select * from sheet_out where seq=$sheet";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_assoc($result);
      
      $title = $data[title];
      
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 출력하기
   function I313()
   {
      global $template, $connect, $sheet, $wh;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   
   // 출력하기
   function I315()
   {
      global $template, $connect, $sheet, $wh;

      $query = "select * from sheet_out where seq=$sheet";
      $result = mysql_query($query, $connect);
      $data_sheet = mysql_fetch_assoc($result);

      if( $wh )
      {
          // 전체 수량
          $query = "select * from stock_tx_history_wh where sheet=$sheet and job in ('out', 'retout') and wh='$wh' order by seq desc";
          $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
          $result = mysql_query($query, $connect);

          // 전체 금액
          $query_price = "select sum(qty*org_price) sum from stock_tx_history_wh where sheet=$sheet and job in ('out', 'retout') and wh='$wh'";
          $result_price = mysql_query($query_price, $connect);
          $data_price = mysql_fetch_assoc($result_price);
          $total_price = $data_price[sum];
      }
      else
      {
        if( _DOMAIN_ == 'digue' )
        {
          $query = "select a.seq            a_seq 
                          ,a.product_id     a_product_id
                          ,sum(a.qty)       a_qty
                          ,a.org_price      a_org_price
                          ,a.memo           a_memo
                          ,a.crdate         a_crdate
                          ,a.owner          a_owner
                          ,if(a.sub_seq>0,a.sub_seq,a.seq) s_seq
                      from stock_tx_history a
                          ,products b
                     where a.sheet=$sheet 
                       and a.job in ('out', 'retout') 
                       and a.product_id = b.product_id
                       and b.supply_code = $supply_code
                     group by s_seq
                     order by a.seq desc";
          $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
          $result = mysql_query($query, $connect);
    
          // 전체 금액
          $query_price = "select sum(qty*org_price) sum from stock_tx_history where sheet=$sheet and job in ('out', 'retout')";
          $result_price = mysql_query($query_price, $connect);
          $data_price = mysql_fetch_assoc($result_price);
          $total_price = $data_price[sum];
        }
        else
        {
          $query = "select seq             a_seq       
                          ,product_id      a_product_id
                          ,sum(qty)        a_qty       
                          ,org_price       a_org_price 
                          ,memo            a_memo      
                          ,crdate          a_crdate    
                          ,owner           a_owner     
                          ,if(sub_seq>0,sub_seq,seq) s_seq
                      from stock_tx_history 
                     where sheet=$sheet 
                       and job in ('out', 'retout') 
                     group by s_seq
                     order by seq desc";


          $total_rows = mysql_num_rows( mysql_query( $query, $connect ) );
          $result = mysql_query($query, $connect);
    
          // 전체 금액
          $query_price = "select sum(qty*org_price) sum from stock_tx_history where sheet=$sheet and job in ('out', 'retout')";
          $result_price = mysql_query($query_price, $connect);
          $data_price = mysql_fetch_assoc($result_price);
          $total_price = $data_price[sum];
        }
      }
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

    function change_title_out()
    {
        global $connect, $sheet, $title;
        
        $query = "update sheet_out set title='$title' where seq=$sheet";
        mysql_query($query, $connect);
        
        echo "<script>opener.change_title('$title');self.close()</script>";
    }

    function del_sheet_out()
    {
        global $connect, $sheet;
        
        $query = "delete from sheet_out where seq=$sheet";
        mysql_query($query, $connect);
        debug("전표삭제 : $query");
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기 [전체]
    function save_file2_all()
    {
        global $template, $connect, $sheet, $start_date, $end_date, $bad_type, $ret_type, $string, $sheet_each;
        
        if( $_SESSION[MULTI_WH] )
        {
            //#############
            // 기본
            //#############

            // 상세 정보 가져온다
            $query = "select * from sheet_out where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' and wh=''";
            if( $bad_type )
                $query .= " and bad=$bad_type-1 ";
            if( $ret_type )
                $query .= " and ret=$ret_type-1 ";
            if( $string )
                $query .= " and title like '%$string%'";
    
            $result = mysql_query($query, $connect);
            
            $sht = '';
            while( $data = mysql_fetch_assoc($result) )
                $sht .= ($sht ? "," : "" ) . $data[seq];
            
            $query = "select a.product_id a_product_id, 
                             b.name b_name,
                             b.brand b_brand,
                             b.options b_options,
                             sum(a.qty) a_qty_sum,
                             b.org_id b_org_id,
                             b.org_price b_org_price,
                             b.extra_price b_extra_price,
                             b.reg_date b_reg_date
                        from stock_tx_history a, products b
                       where a.product_id = b.product_id and
                             a.job in ('out', 'retout') and 
                             a.sheet in ($sht) 
                    group by a.product_id 
                    order by b.supply_code, b.name, b.options";
            $result = mysql_query($query, $connect);
            
            $arr = array();
            $supply_obj  = new class_supply();
            while($data = mysql_fetch_assoc($result))
            {
                $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                
                $arr[] = array(
                    wh           => "&nbsp;",
                    supply_name  => $supply_name,
                    org_id 		 => $data[b_org_id],
                    product_id   => $data[a_product_id],
                    name         => $data[b_name],
                    brand        => $data[b_brand],
                    options      => $data[b_options],
                    qty          => $data[a_qty_sum],
                    org_price    => $data[b_org_price] + $data[b_extra_price],
                    reg_date     => $data[b_reg_date] );
            }
            
            //#############
            // 창고
            //#############

            $query_wh = "select * from warehouse order by name";
            $result_wh = mysql_query($query_wh, $connect);
            while( $data_wh = mysql_fetch_assoc($result_wh) )
            {
                $wh = $data_wh[name];
                
                // 상세 정보 가져온다
                $query = "select * from sheet_out where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' and wh='$wh'";
                if( $bad_type )
                    $query .= " and bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and ret=$ret_type-1 ";
                if( $string )
                    $query .= " and title like '%$string%'";
        
                $result = mysql_query($query, $connect);
                
                $sht = '';
                while( $data = mysql_fetch_assoc($result) )
                    $sht .= ($sht ? "," : "" ) . $data[seq];
                
                $query = "select a.product_id a_product_id, 
                                 b.name b_name,
                                 b.brand b_brand,
                                 b.options b_options,
                                 sum(a.qty) a_qty_sum,
                                 b.org_id b_org_id,
                                 b.org_price b_org_price,
                                 b.extra_price b_extra_price,
                                 b.reg_date b_reg_date
                            from stock_tx_history_wh a, products b
                           where a.product_id = b.product_id and
                                 a.job in ('out', 'retout') and 
                                 a.sheet in ($sht) and
                                 a.wh = '$wh'
                        group by a.product_id 
                        order by b.supply_code, b.name, b.options";
                $result = mysql_query($query, $connect);
                
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                    
                    $arr[] = array(
                        wh           => $wh,
                        supply_name  => $supply_name,
                        org_id	 	 => $data[b_org_id],
                        product_id   => $data[a_product_id],
                        name         => $data[b_name],
                        brand        => $data[b_brand],
                        options      => $data[b_options],
                        qty          => $data[a_qty_sum],
                        org_price    => $data[b_org_price] + $data[b_extra_price],
                        reg_date     => $data[b_reg_date] );
                }
            }
        }
        else
        {
            if( $sheet_each )
            {
                $query = "select a.title  a_title
                                ,c.org_id     c_org_id
                                ,c.product_id c_product_id
                                ,c.name       c_name
                                ,c.brand      c_brand
                                ,c.options    c_options
                                ,b.qty        b_qty
                                ,c.org_price  c_org_price
                                ,c.extra_price c_extra_price
                                ,c.reg_date   c_reg_date
                                ,b.crdate     b_crdate
                                ,c.barcode    c_barcode
                                ,if(b.sub_seq>0,b.sub_seq,b.seq) s_seq
                            from sheet_out a
                                ,stock_tx_history b
                                ,products c
                           where a.seq = b.sheet
                             and b.job in ('out', 'retout')
                             and b.product_id = c.product_id
                             and a.crdate >= '$start_date 00:00:00' 
                             and a.crdate <= '$end_date 23:59:59' ";
                if( $bad_type )
                    $query .= " and a.bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and a.ret=$ret_type-1 ";
                if( $string )
                    $query .= " and a.title like '%$string%'";
                    
                $query .= " group by s_seq ";
                $query .= " order by a.seq, c.name ";
                $result = mysql_query($query, $connect);
                
                $arr = array();
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[c_product_id] );
                    
                    $arr[] = array(
                        sheet_name   => $data[a_title],
                        supply_name  => $supply_name,
                        org_id       => $data[c_org_id],
                        product_id   => $data[c_product_id],
                        name         => $data[c_name],
                        brand        => $data[c_brand],
                        options      => $data[c_options],
                        qty          => $data[b_qty],
                        org_price    => $data[c_org_price] + $data[c_extra_price],
                        barcode      => $data[c_barcode],
                        stock_qty    => class_stock::get_current_stock( $data[c_product_id], 0 ),
                        reg_date     => $data[c_reg_date],
                        crdate       => $data[b_crdate] 
                    );
                }
            }
            else
            {
                // 상세 정보 가져온다
                $query = "select * from sheet_out where crdate >= '$start_date 00:00:00' and crdate <= '$end_date 23:59:59' ";
                if( $bad_type )
                    $query .= " and bad=$bad_type-1 ";
                if( $ret_type )
                    $query .= " and ret=$ret_type-1 ";
                if( $string )
                    $query .= " and title like '%$string%'";
        
                $result = mysql_query($query, $connect);
                
                $sht = '';
                while( $data = mysql_fetch_assoc($result) )
                    $sht .= ($sht ? "," : "" ) . $data[seq];
                
                $query = "select a.product_id a_product_id, 
                                 b.name b_name,
                                 b.brand b_brand,
                                 b.options b_options,
                                 sum(a.qty) a_qty_sum,
                                 b.org_id b_org_id,
                                 b.org_price b_org_price,
                                 b.extra_price b_extra_price,
                                 b.reg_date b_reg_date
                            from stock_tx_history a, products b
                           where a.product_id = b.product_id and
                                 a.job in ('out', 'retout') and 
                                 a.sheet in ($sht) 
                        group by a.product_id 
                        order by b.supply_code, b.name, b.options";
                $result = mysql_query($query, $connect);
                
                $arr = array();
                $supply_obj  = new class_supply();
                while($data = mysql_fetch_assoc($result))
                {
                    $supply_name = $supply_obj->get_name2( $data[a_product_id] );
                    
                    $arr[] = array(
                        supply_name  => $supply_name,
                        org_id		 => $data[b_org_id],
                        product_id   => $data[a_product_id],
                        name         => $data[b_name],
                        brand        => $data[b_brand],
                        options      => $data[b_options],
                        qty          => $data[a_qty_sum],
                        org_price    => $data[b_org_price] + $data[b_extra_price],
                        reg_date     => $data[b_reg_date] );
                }
            }
        }
        
        $fn = $this->make_file_all( $arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file2()
    {
        global $template, $connect, $sheet, $wh;
        
        // get list from common module
        if( $wh )
            $query = "select 	 seq       	a_seq
							,product_id	a_product_id
							,crdate    a_crdate
							,job       a_job
							,qty       a_qty
							,owner     a_owner
							,location  a_location
							,stock     a_stock
							,memo      a_memo
							,bad       a_bad
							,sheet     a_sheet
							,order_seq a_order_seq
							,org_price a_org_price
							,wh        a_wh
					  from stock_tx_history_wh
				     where sheet=$sheet
				       and job in ('out', 'retout')
				  order by seq desc";
        else
        {
           $query = "select a.seq a_seq
						,a.product_id a_product_id
						,sum(a.qty) a_qty
						,a.org_price a_org_price
						,a.memo a_memo
						,a.crdate a_crdate
						,a.owner a_owner
						,if(a.sub_seq>0,a.sub_seq,a.seq) s_seq
						,b.org_price b_org_price
						,b.extra_price b_extra_price
	                from stock_tx_history a, products b 
	               where a.product_id = b.product_id 
	                 and a.job in ('out', 'retout') 
	                 and a.sheet in ($sheet)  
	            group by s_seq 
	            order by a_seq " . (_DOMAIN_ == 'au2' ? "" : " desc");
        }
            
        $result = mysql_query($query, $connect);
        
        $arr = array();
        $obj = new class_product();
        $supply_obj  = new class_supply();
        
        while($data = mysql_fetch_assoc($result))
        {
            $info = $obj->get_info($data[a_product_id]);
            $supply_info = $supply_obj->get_info( $info[supply_code] );

            // price 
            if( _DOMAIN_ == 'kldh01' )
                $_price = $data[a_qty] * $info[supply_price];
            else
                $_price = $data[a_qty] * $data[a_org_price];
                
            $arr[] = array(
                supply_name  => $supply_info[name],
                supply_tel   => $supply_info[tel] . " / " . $supply_info[mobile],
                supply_address  => $supply_info[address1] . " " . $supply_info[address2],
                brand        => $info[brand],
                supply_options => $info[supply_options],
                org_id       => $info[org_id],
                product_id   => $data[a_product_id],
                name         => $info[name],
                options      => $info[options],
                qty          => $data[a_qty],
                org_price    => $info[org_price],
                price        => $_price,
                shop_price   => $info[shop_price] + $data[b_extra_price],
                total_price  => $data[a_qty] * ($info[shop_price] + $data[b_extra_price]),
                reg_date     => $info[reg_date],
                memo         => $data[a_memo],
                barcode      => $info[barcode],
                stock_qty    => class_stock::get_current_stock( $data[a_product_id], 0 ),
                crdate       => $data[a_crdate],
                worker       => $data[a_owner] );
        }

        $fn = $this->make_file( $arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }

    // 테이블 이상으로 stock_tx가 문제생긴 업체용. kldh01
    function create_stock_tx()
    {
        global $template, $connect;

        // stock_tx_history 수정
        $query = "select * from stock_tx_history where ";

/*
        // 조정 부분 수정
        $query = "select * from stock_tx_history where job='arrange' order by seq ";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_stock = "select * from stock_tx_history 
                             where product_id='$data[product_id]' and 
                                   seq < $data[seq] and 
                                   bad = $data[bad]
                             order by seq desc limit 1";
            $result_stock = mysql_query($query_stock, $connect);
            if( mysql_num_rows($result_stock) )
            {
                $data_stock = mysql_fetch_assoc($result_stock);
                $arrange_stock = $data[stock] - $data_stock[stock];
            }
            else
                $arrange_stock = $data[stock];
                
            // 무조건 있다는 가정하에 없데이트
            $query_run = "update stock_tx 
                             set arrange = arrange + $arrange_stock
                           where product_id = '$data[product_id]' and
                                 bad = $data[bad] and
                                 crdate = substr('$data[crdate]',1,10)";
            mysql_query($query_run, $connect);
        }

        // 처음.....
        $query_date = "select substring(crdate, 1, 10) cdate from stock_tx_history group by cdate order by cdate";
        $result_date = mysql_query($query_date, $connect);
        while( $data_date = mysql_fetch_assoc($result_date) )
        {
            // 날짜.
            $d = $data_date[cdate];
            $old_pid = '';
            $old_bad = 0;
            
            $query = "select product_id, bad, job, sum(qty) sum_qty
                        from stock_tx_history 
                       where substring(crdate,1,10) = '$d'
                       group by product_id, bad, job
                       order by product_id, bad, job";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                switch( $data[job] )
                {
                    case "arrange":  $job = "arrange" ; break;
                    case "in"     :  $job = "stockin" ; break;
                    case "out"    :  $job = "stockout"; break;
                    case "retin"  :  $job = "retin"   ; break;
                    case "retout" :  $job = "retout"  ; break;
                    case "trans"  :  $job = "trans"   ; break;
                    default:  continue 2;
                }
                
                // update
                if( $old_pid == $data[product_id] && $old_bad == $data[bad] )
                {
                    $query_run = "update stock_tx 
                                     set bad        = $data[bad],
                                         $job       = $data[sum_qty]
                                   where crdate     = '$d' and 
                                         product_id = '$data[product_id]' and
                                         bad        = $data[bad]";
                }
                // insert
                else
                {
                    // 그날의 최종재고 구하기
                    $query_stock = "select stock 
                                      from stock_tx_history 
                                     where substring(crdate,1,10) = '$d' and
                                           product_id = '$data[product_id]' and
                                           bad = $data[bad]
                                     order by seq desc limit 1";
                    $result_stock = mysql_query($query_stock, $connect);
                    $data_stock = mysql_fetch_assoc($result_stock);
                    
                    $query_run = "insert stock_tx 
                                     set crdate     = '$d',
                                         product_id = '$data[product_id]',
                                         location   = 'Def',
                                         bad        = $data[bad],
                                         $job       = $data[sum_qty],
                                         stock      = $data_stock[stock]";
                }
                mysql_query($query_run, $connect);
                
                $old_pid = $data[product_id];
                $old_bad = $data[bad];
            }
        }
*/
    }

    function del_sheet_from()
    {
        global $connect, $seq;
        
        $query = "update stock_tx_history set sheet=0 where seq=$seq";
        mysql_query($query, $connect);
        
        debug( "전표제외 : " . $query );
    }
    
    function edit_memo()
    {
        global $connect, $seq, $wh, $memo;
        
        if( $wh )
            $query = "update stock_tx_history_wh set memo='$memo' where seq=$seq";
        else
            $query = "update stock_tx_history set memo='$memo' where seq=$seq";
        
        mysql_query($query, $connect);
    }
    
    function create_stockin_sheet()
    {
        global $connect, $type_bad, $type_return, $sheet_title;
        
        $val = array();
        
        // 동일 전표명 확인
        $query = "select * from sheet_in where title='$sheet_title'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
            echo json_encode($val);
            exit;
        }
        
        $query = "insert sheet_in 
                     set crdate = now(), 
                         cruser = '$_SESSION[LOGIN_NAME]', 
                         title  = '$sheet_title', 
                         bad    = '$type_bad', 
                         ret    = '$type_return'";
        mysql_query($query, $connect);
        
        $val['error'] = 0;
        echo json_encode($val);
    }
    
    function create_stockout_sheet()
    {
        global $connect, $type_bad, $type_return, $sheet_title;
        
        $val = array();
        
        // 동일 전표명 확인
        $query = "select * from sheet_out where title='$sheet_title'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
            echo json_encode($val);
            exit;
        }
        
        $query = "insert sheet_out
                     set crdate = now(), 
                         cruser = '$_SESSION[LOGIN_NAME]', 
                         title  = '$sheet_title', 
                         bad    = '$type_bad', 
                         ret    = '$type_return'";
        mysql_query($query, $connect);
        
        $val['error'] = 0;
        echo json_encode($val);
    }
    
}
?>
