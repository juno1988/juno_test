<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";

//require_once "Classes/PHPExcel.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_IJ00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고시점비교
    function IJ00()
    {
        global $template, $connect;
        global $stock1_seq, $stock2_seq,$stock_all;

        if( $stock1_seq && $stock2_seq )
        {
            // 전체 수량
            $query = $this->get_IJ00_query();
            $result = mysql_query($query, $connect);
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 재고시점비교 쿼리 
    function get_IJ00_query()
    {
        global $template, $connect;
        global $stock1_seq, $stock2_seq;

        $query = "select c.name       c_name,
                         b.product_id b_product_id,
                         b.location   b_location,
                         b.name       b_name,
                         b.options    b_options,
                         b.org_price  b_org_price,
                         a.bad        a_bad
                    from stock_save_data a,
                         products b,
                         userinfo c
                   where a.product_id = b.product_id and
                         b.supply_code = c.code and
                         a.sheet in ($stock1_seq, $stock2_seq) ";
        $query .= " group by b_product_id, a_bad order by a_bad, c_name, b_name, b_options";
        
        return $query;
    }


    function save_file()
    {
        global $template, $connect;
        global $stock1_seq, $stock2_seq, $stock_all;

        $query = $this->get_IJ00_query();
        $result = mysql_query($query, $connect);
        
        $arr_datas = array();
     
        while( $data = mysql_fetch_assoc($result) )
        { 
            $qty_stock1 = 0;
            $qty_stock2 = 0;
            $query_qty = "select * from stock_save_data where product_id='$data[b_product_id]' and bad=$data[a_bad]";
            $result_qty = mysql_query($query_qty, $connect);
            while( $data_qty = mysql_fetch_assoc($result_qty) )
            {
                if( $data_qty[sheet] == $stock1_seq )  $qty_stock1 = $data_qty[qty];
                if( $data_qty[sheet] == $stock2_seq )  $qty_stock2 = $data_qty[qty];
            }
            
            if( $stock_all == 0)
            {
                if( $qty_stock1 == $qty_stock2)  continue;
            }
            
            if( $data[a_bad] == 0 )
                $type = "정상";
            else
                $type = $_SESSION[EXTRA_STOCK_TYPE];
            
            $total_qty += $qty_stock2 - $qty_stock1 ;
            $total_price += ($qty_stock2 - $qty_stock1)*$data[b_org_price];

            $arr_datas[] = array(
                supply_name    => $data[c_name],
                product_id     => $data[b_product_id],
                product_name   => $data[b_name],
                options        => $data[b_options],
                location       => $data[b_location],
                org_price      => $data[b_org_price],
                stock_type     => $type,
                qty_stock1     => $qty_stock1,
                qty_stock2     => $qty_stock2,
                qty_sub        => $qty_stock2 - $qty_stock1,
                org_price_sub  => ($qty_stock2 - $qty_stock1)*$data[b_org_price]
            );
        }
        
        $arr_datas[] = array(
                supply_name    => "합계",
                product_id     => "",
                product_name   => "",
                options        => "",
                location       => "",
                org_price      => "",
                stock_type     => "",
                qty_stock1     => "",
                qty_stock2     => "",
                qty_sub        => $total_qty,
                org_price_sub  => $total_price
        );
        
        $this->make_file( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $include_detail;
        
        $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = $this->default_header;
        fwrite($handle, $buffer);

        // 헤더
        $buffer = "<tr>\n";
          
        $buffer .= "<th class=header_item>공급처   </th>\n";
        $buffer .= "<th class=header_item>상품코드 </th>\n";
        $buffer .= "<th class=header_item>상품명   </th>\n";
        $buffer .= "<th class=header_item>옵션     </th>\n";
        $buffer .= "<th class=header_item>로케이션 </th>\n";
        $buffer .= "<th class=header_item>원가     </th>\n";
        $buffer .= "<th class=header_item>타입     </th>\n";
        $buffer .= "<th class=header_item>재고시점1</th>\n";
        $buffer .= "<th class=header_item>재고시점2</th>\n";
        $buffer .= "<th class=header_item>수량차이 </th>\n";
        $buffer .= "<th class=header_item>금액차이   </th>\n";

        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach ( $arr_datas as $row ) 
        {
            $buffer = "<tr>\n";
            $buffer .= "<td class=str_item_center>" . $row[supply_name]  . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[product_id]   . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[product_name] . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[options]      . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[location]     . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[org_price]    . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[stock_type]   . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[qty_stock1]   . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[qty_stock2]   . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[qty_sub]      . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[org_price_sub]. "</td>\n";
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }

        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }

    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"재고로그.xls"));
    }    
    

    function IJ01()
    {
        global $template, $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function create_save_stock($is_init=0)
    {
        global $connect, $title_name;
        
        $val = array();
        
        if( $is_init )
        {
            // 재고초기화 이름 생성
            $title_name = "재고초기화(" . date("Y-m-d h:m:s") . ")";
        }
        else
        {
            // 동일 이름 확인
            $query = "select * from stock_save_list where name='$title_name'";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
            {
                $val['error'] = 1;
                echo json_encode($val);
                exit;
            }
        }
        
        $query = "insert stock_save_list
                     set crdate = now(), 
                         worker = '$_SESSION[LOGIN_NAME]', 
                         name   = '$title_name'";
        mysql_query($query, $connect);
        
        $query = "select * from stock_save_list order by seq desc limit 1";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $new_seq = $data[seq];
        
        $query = "select a.product_id a_product_id, 
                         a.stock a_stock, 
                         a.bad a_bad
                    from current_stock a,
                         products b
                   where a.product_id = b.product_id and
                         a.stock <> 0 and 
                         b.is_delete = 0 and
                         b.enable_stock = 1";
            
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query_data = "insert stock_save_data
                              set sheet = $new_seq,
                                  product_id = '$data[a_product_id]',
                                  qty = $data[a_stock],
                                  bad = $data[a_bad]";
            mysql_query($query_data, $connect);
        }
        
        if( !$is_init )
        {
            $val['error'] = 0;
            echo json_encode($val);
        }
    }

    function IJ02()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $stock_no, $title_name;

        // 페이지
        if( !$page )
        {
            $page = 1;
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
            return;
        }
        
        // 작업중
        $this->show_wait();

        // link url
        $par = array('template','title_name','stock_no');
        $link_url = $this->build_link_url3( $par );
        $line_per_page = 10;
        
        $query = "select * from stock_save_list ";
        if( $title_name )
            $query .= " where name like '%$title_name%'";
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result );
        
        $query .= " order by seq desc ";
        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
}

?>
