<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_file.php";

////////////////////////////////
// class name: class_F400
//

class class_F400 extends class_top {

    function F400()
    {
		global $connect;
		global $template, $start_date, $end_date, $status, $date_type, $page, $supply_id;

        echo "<script>show_waiting()</script>";
        flush();
        if (!$start_date)
            $start_date = date('Y-m-d', strtotime('-20 day'));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting()</script>";
        flush();
    }

    // 검색
	function search()
    {
		global $connect;
		global $act, $status, $date_type, $supply_id, $start_date, $end_date, $template, $page;
        
        echo "<script> show_waiting() </script>";
        flush();

        if ( !$start_date )
            $start_date = date('Y-m-d', strtotime('-20 day'));

        include "template/F/F400.htm";
        echo "<script> hide_waiting() </script>";
        flush();
    }

    // 공급처별 정산 금액 계산   
    function calc()
    {
        global $connect, $supply_id, $start_date, $end_date, $page, $is_all, $except_return, $stock_type;
        
        $disp_cnt = 500;
        if( !$page )
            $page = 1;
        
        $start = ( $page - 1 ) * $disp_cnt;
        
        $arr_result1 = array();
        $arr_result2 = array();
        
        $arr_result2['page'] = $page;

        // supply_code 목록 생성 ( lv.0 )
        if ( $supply_id )
        {
            $query = "select * from userinfo where level = 0 and code = $supply_id";
            $arr_result2['total_rows'] = 1; 
        }
        else
        {
            $query = "select count(*) cnt from userinfo where level = 0";
            $_result = mysql_query( $query, $connect );
            $_data   = mysql_fetch_assoc( $_result );            
            $arr_result2['total_rows'] = $_data[cnt]; 
            
            //$query = "select * from userinfo where level = 0 limit $start, $disp_cnt";               
            $query = "select * from userinfo where level = 0";               
        }
        $result = mysql_query( $query, $connect );

        $arr_result2['success'] = 0;
        $row = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $row++;
            $arr_result1 = $this->get_supply_calc( $data[code] );
            
            // 출력할 자료가 있는경우에만 출력 - jkryu 2012.6.14
            if ( $arr_result1[sum_stockin]  > 0 
              || $arr_result1[sum_stockout] > 0 
              || $arr_result1[sum_trans]    > 0 
              || $arr_result1[sum_arrange]  > 0 
              || $arr_result1[sum_retin]    > 0 
              || $arr_result1[sum_retout]   > 0 
              || $arr_result1[stock]        > 0 
              )
            {
                $arr_result2['list'][] = array(  supply_code        =>  $data[code]
                                             ,supply_name       =>  $arr_result1[supply_name]
                                             ,sum_stockin       =>  number_format ( $arr_result1[sum_stockin] )
                                             ,sum_stockout      =>  number_format ( $arr_result1[sum_stockout] )
                                             ,sum_trans         =>  number_format ( $arr_result1[sum_trans] )
                                             ,sum_arrange       =>  number_format ( $arr_result1[sum_arrange] )
                                             ,sum_retin         =>  number_format ( $arr_result1[sum_retin] )
                                             ,sum_retout        =>  number_format ( $arr_result1[sum_retout] )
                                             ,sum_pri_stockin   =>  number_format ( $arr_result1[sum_pri_stockin] )
                                             ,sum_pri_stockout  =>  number_format ( $arr_result1[sum_pri_stockout] ) 
                                             ,stock             =>  number_format ( $arr_result1[stock] )
                                             ,stock_price       =>  number_format ( $arr_result1[stock_price] ) );
            }
        }
        $arr_result2['row']        = $row;
        $arr_result2['success']    = count( $arr_result2['list'] );
        $arr_result2['total_rows'] = count( $arr_result2['list'] );
        $_json = json_encode( $arr_result2 );
        
        echo $_json;
    }

    //////////////////////////////
    //
    // calc와 동일한 로직..
    function build_file()
    {
        global $connect, $supply_id, $start_date, $end_date, $page, $is_all;
        
        if( !$page )
            $page = 1;
        
        $start = ( $page - 1 ) * 20;
        
        $arr_result1 = array();
        $arr_result2 = array();
        
        $arr_result2['page'] = $page;

        // supply_code 목록 생성 ( lv.0 )
        if ( $supply_id )
            $query = "select * from userinfo where level = 0 and code = $supply_id";
        else
            $query = "select * from userinfo where level = 0";               
        
        $result = mysql_query( $query, $connect );

        $arr_result2[] = array("공급처코드","공급처 명","총입고","출고","배송","취소반품","교환반품","총입고가","총출고가");
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $row++;
            $arr_result1 = $this->get_supply_calc( $data[code] );
            $arr_result2[] = array(  $data[code]
                                     ,$arr_result1[supply_name]
                                     ,$arr_result1[sum_stockin] 
                                     ,$arr_result1[sum_stockout] 
                                     ,$arr_result1[sum_trans] 
                                     ,$arr_result1[sum_arrange] 
                                     ,$arr_result1[sum_retin] 
                                     ,$arr_result1[sum_retout] 
                                     ,$arr_result1[sum_pri_stockin] 
                                     ,$arr_result1[sum_pri_stockout]
                                     ,$arr_result1[stock]
                                     ,$arr_result1[stock_price]);
    
            if ( $row % 5 == 0 )
            {
                //$this->show_txt( $row );                                    
                echo "<script language='javascript'>parent.show_txt( $row )</script>";                                     
                flush();
            }
        }
        
        // file 저장
        $obj = new class_file();
        $obj->save_file( $arr_result2,"F400_공급처별정산통계.xls");
        
        // set download
        echo "<script language='javascript'>parent.hide_waiting()</script>";     
        echo "<script language='javascript'>parent.set_download()</script>";                                     
        
    }

    // 공급처별 정산 금액 계산
    // 입고 기준..
    function get_supply_calc( $supply_code )
    {
        global $connect;
        global $date_type, $start_date, $end_date, $except_return, $stock_type;

        $query = "select name from userinfo where code=$supply_code";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $_supply_name = $data[name];
        
        $arr_result = array();

        if( $except_return )
        {
            $query =  "select a.supply_code as supply_code,
                              c.name as supply_name,
                              sum(b.stockin - b.retin) as sum_stockin,
                              sum(b.stockout) as sum_stockout,                        
                              sum(b.trans) as sum_trans,
                              sum(b.arrange) as sum_arrange,
                              sum(b.retin) as sum_retin,
                              sum(b.retout) as sum_retout,
                              sum((b.stockin-b.retin) * a.org_price) as sum_pri_stockin,
                		      sum(b.stockout * a.org_price) as sum_pri_stockout
                         from products a, stock_tx b, userinfo c
                        where a.product_id  = b.product_id
                          and a.supply_code = $supply_code
                          and b.crdate      >= '$start_date' 
                          and b.crdate      <= '$end_date'
                          and a.supply_code = c.code
                          and b.bad         = '$stock_type'
                     group by supply_code";
        }
        else
        {
            $query =  "select a.supply_code as supply_code,
                              c.name as supply_name,
                              sum(b.stockin) as sum_stockin,
                              sum(b.stockout) as sum_stockout,                        
                              sum(b.trans) as sum_trans,
                              sum(b.arrange) as sum_arrange,
                              sum(b.retin) as sum_retin,
                              sum(b.retout) as sum_retout,
                              sum(b.stockin * a.org_price) as sum_pri_stockin,
                		      sum(b.stockout * a.org_price) as sum_pri_stockout
                         from products a, stock_tx b, userinfo c
                        where a.product_id  = b.product_id
                          and a.supply_code = $supply_code
                          and b.crdate      >= '$start_date' 
                          and b.crdate      <= '$end_date'
                          and a.supply_code = c.code
                          and b.bad         = '$stock_type'
                     group by supply_code";
        }
        $result = mysql_query( $query, $connect );
        $outdata = mysql_fetch_assoc( $result );
        $arr_result = array(    supply_code         =>  $supply_id
                                ,supply_name        =>  $_supply_name
                                ,sum_stockin        =>  $outdata[sum_stockin]
                                ,sum_stockout       =>  $outdata[sum_stockout]
                                ,sum_trans          =>  $outdata[sum_trans]
                                ,sum_arrange        =>  $outdata[sum_arrange]
                                ,sum_retin          =>  $outdata[sum_retin]
                                ,sum_retout         =>  $outdata[sum_retout]
                                ,sum_pri_stockin    =>  $outdata[sum_pri_stockin]
                                ,sum_pri_stockout   =>  $outdata[sum_pri_stockout] );  
                                
        // 배송 상품 개수..
        $query = "select sum(b.qty) sum_qty
                    from orders a, order_products b, products c
                   where a.seq = b.order_seq
                     and b.product_id = c.product_id
                     and a.trans_date_pos >= '$start_date 00:00:00'
                     and a.trans_date_pos <= '$end_date 23:59:59'
                     and c.supply_code = $supply_code";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $arr_result['sum_trans'] = $data[sum_qty];
                     
        // 현재고 & 현재고 총 입고가
        $query = "select sum(b.stock) b_sum_stock,
                         sum(a.org_price * b.stock) stock_price
                    from products a,
                         current_stock b
                   where a.product_id = b.product_id and
                         a.supply_code = $supply_code and
                         b.bad = '$stock_type'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $arr_result['stock'] = $data[b_sum_stock];
        $arr_result['stock_price'] = $data[stock_price];
        
        return $arr_result;
    }
    
    // 파일 만들기
    function save_file()
    {
        global $connect, $supply_id, $start_date, $end_date, $page, $is_all, $except_return, $stock_type;
        
        if( !$page )
            $page = 1;
        
        $start = ( $page - 1 ) * 20;
        
        $arr_result1 = array();
        $arr_result2 = array();
        
        // supply_code 목록 생성 ( lv.0 )
        if ( $supply_id )
        {
            $query = "select * from userinfo where level = 0 and code = $supply_id";
        }
        else
        {
            $query = "select * from userinfo where level = 0";
        }
        $result = mysql_query( $query, $connect );

        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $row++;
            $arr_result1 = $this->get_supply_calc( $data[code] );
            
            // 값이 없으면 출력하지 않음.. - jkryu
            if ( $arr_result1[sum_stockin]      > 0 
              || $arr_result1[sum_stockout]     > 0 
              || $arr_result1[sum_trans]        > 0 
              || $arr_result1[sum_arrange]      > 0 
              || $arr_result1[sum_retin]        > 0 
              || $arr_result1[sum_retout]       > 0 
              || $arr_result1[stock]            > 0 
            )
            {
                $arr_result2[] = array(  $data[code]
                                     ,$arr_result1[supply_name]
                                     ,$arr_result1[sum_stockin] 
                                     ,$arr_result1[sum_stockout] 
                                     ,$arr_result1[sum_trans] 
                                     ,$arr_result1[sum_arrange] 
                                     ,$arr_result1[sum_retin] 
                                     ,$arr_result1[sum_retout] 
                                     ,$arr_result1[sum_pri_stockin] 
                                     ,$arr_result1[sum_pri_stockout]  
                                     ,$arr_result1[stock]  
                                     ,$arr_result1[stock_price] );
            }
            
            if ( $row % 17 == 0 )
            {
                //$this->show_txt( $row );                                    
                echo "<script language='javascript'>parent.show_txt( $row )</script>";                                     
                flush();
            }
        }
        
        $this->make_file( $arr_result2, "download_supply_stat.xls" );
        echo "<script language='javascript'>parent.set_file('download_supply_stat.xls')</script>";
    }

    function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= $this->default_header;
        
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
        $buffer .= "<td class=header_item>공급처코드</td>\n";
        $buffer .= "<td class=header_item>공급처명</td>\n";
        $buffer .= "<td class=header_item>입고</td>\n";
        $buffer .= "<td class=header_item>출고</td>\n";
        $buffer .= "<td class=header_item>배송</td>\n";
        $buffer .= "<td class=header_item>조정</td>\n";
        $buffer .= "<td class=header_item>반품입고</td>\n";
        $buffer .= "<td class=header_item>반품출고</td>\n";
        $buffer .= "<td class=header_item>총입고가</td>\n";
        $buffer .= "<td class=header_item>총출고가</td>\n";
        $buffer .= "<td class=header_item>현재고</td>\n";
        $buffer .= "<td class=header_item>현재고총입고가</td>\n";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);
        
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            $buffer .= "<td class=str_item_center>$val[0]       </td>\n";
            $buffer .= "<td class=str_item>$val[1]</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[2] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[3] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[4] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[5] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[6] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[7] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[8] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[9] ) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[10]) . "</td>\n";
            $buffer .= "<td class=num_item>" . number_format($val[11]) . "</td>\n";
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }    

    // 파일 다운받기
    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"공급처별정산통계.xls"));
    }    

    // 엑셀 파일 헤더
    var $default_header = "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.header_item{
    font:bold 12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    background:#CCFFCC;
	text-align:center;
}
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
	mso-number-format:\"\\#\\,\\#\\#0_ \\;\\[Red\\]\\\\-\\#\\,\\#\\#0\\\\ \";
}
.per_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
	mso-number-format:0%;
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.str_item_center{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
	text-align:center;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
br
    {mso-data-placement:same-cell;}
</style>
<body>
<table border=1>
";
    
}
