<?
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_supply.php";

//////////////////////////////////////////////
// 입고요청목록
class class_IA00 extends class_top
{
    // 목록
    function IA00()
    {
        global $template, $connect, $page, $link_url, $line_per_page;
        global $start_date, $end_date, $supply_code, $product_id, $name, $options;
  
        if( !$page ) $page = 1;
  
        // 전체 수량
        $total_rows = 0;
        $result = $this->get_list( &$total_rows, 0 );
        
        $par_arr = array("template","action","start_date","end_date","supply_id","product_id","name","options","page");
        $link_url_list = $this->build_link_par($par_arr); 

        $link_url = "?" . $this->build_link_url();
  
        $master_code = substr( $template, 0,1);
        include "template/I/IA00.htm";
    }

    // 목록 구하기
    function get_list(&$total_rows, $is_download)
    {
        global $template, $connect, $page, $link_url, $line_per_page;
        global $start_date, $end_date, $supply_code, $product_id, $name, $options;
        global $str_supply_code, $multi_supply_group, $multi_supply;
        
        // 목록을 가져온다.
        $query = "select a.seq as seq,
                         a.crdate as crdate,
                         a.product_id as product_id,
                         b.org_price,
                         a.qty as qty,
                         b.supply_code as supply_code,
                         b.name as name,
                         b.options as options
                    from stockin_req a,
                         products b
                   where a.crdate >= '$start_date' and
                         a.crdate <= '$end_date' and
                         a.product_id = b.product_id ";
        //if( $supply_code )
            //$query .= " and b.supply_code='$supply_code' ";
            
        if( $str_supply_code )
            $query .= " and b.supply_code in ( $str_supply_code )";
        
        if($multi_supply)
            $query .= " and b.supply_code in ( $multi_supply )";
            
        // debug( $query );
        
        if( $product_id )
            $query .= " and b.product_id='$product_id' ";
        
        if( $name )
            $query .= " and b.name like '%$name%' ";
        
        if( $options )
            $query .= " and b.options like '%$options%' ";
        
        $query .= " order by b.name ";
        
        // 전체 수량
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows($result);
        
        // 페이지 검색
        if( !$is_download )
        {
            $line_per_page = 20;
            $starter = ($page-1) * $line_per_page;
            $query .= " limit $starter, $line_per_page";
        }
//debug( $query );
        return mysql_query($query, $connect);
    }
    
    //////////////////////////////////////
    // 파일 만들기
    function save_file()
    {
        global $template, $connect, $page, $link_url, $line_per_page;
        global $start_date, $end_date, $supply_id, $product_id, $name, $options;

        $_arr = array();
        
        $total_rows = 0;
        $result = $this->get_list( &$total_rows, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $_arr[] = array(
                crate      => $data[crdate],
                supply     => class_supply::get_name( $data[supply_code] ),
                product_id => $data[product_id],
                name       => $data[name],
                options    => $data[options],
                org_price  => $data[org_price],
                qty        => $data[qty]
            );
        }
        
        $fn = $this->make_file( $_arr );
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
            "생성일"
            ,"공급처"
            ,"상품코드"
            ,"상품명"
            ,"옵션"
            ,"원가"
            ,"수량"
        );

        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
        $style2 = 'font:9pt "굴림"; white-space:nowrap;';

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";

            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $key == 'product_id' )
                    $buffer .= "<td style='$style1'>" . $value . "</td>";
                else
                    $buffer .= "<td style='$style2'>" . $value . "</td>";
            }
            
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 파일 다운받기
    function download2()
    {
        global $filename, $new_name;
        
        if( !$new_name )  $new_name = "stock_data.xls";
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }    
    
    function delete_req()
    {
        global $template, $connect, $page, $link_url, $line_per_page;
        global $start_date, $end_date, $supply_id, $product_id, $name, $options;

        $total_rows = 0;
        $result = $this->get_list( &$total_rows, 1 );

        $arr = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr[] = $data[seq];
        }
        $str = implode(',',$arr);
        $query = "delete from stockin_req where seq in ($str)";

        mysql_query($query, $connect);

        $this->IA00();
    }
}
?>
