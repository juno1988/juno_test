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

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_DP00 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고 로그 조회
    function DP00()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_type, $string, $start_date, $end_date, $work_type, $stock_type, $is_del;

        $string = trim( $string );
        $line_per_page = 50;

        // 페이지
        if( !$page )
        {
            $page = 1;
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d");
            $work_type = "all";
            $stock_type = 0;
        }

        // link url
        $par = array('template', 'query_type', 'string', 'start_date', 'end_date', 'work_type', 'stock_type', 'is_del');
        $link_url = $this->build_link_url3( $par );
        
        // 전체 수량
        $query = $this->get_DP00_query();
        $result = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result );

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
debug("재고이력 : " . $query);
        $result = mysql_query($query, $connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 재고 로그 쿼리 
    function get_DP00_query()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_type, $string, $start_date, $end_date, $work_type, $stock_type, $is_del;

        if( $is_del )
            $tbl = "stock_tx_history_del";
        else
            $tbl = "stock_tx_history";

        $query = "select a.seq        a_seq,
                         a.crdate     a_crdate,
                         c.name       c_name,
                         b.product_id b_product_id,
                         b.name       b_name,
                         b.options    b_options,
                         a.bad        a_bad,
                         a.job        a_job,
                         a.qty        a_qty,
                         a.stock      a_stock,
                         a.owner      a_owner,
                         a.memo       a_memo,
                         a.sheet      a_sheet,
                         a.order_seq  a_order_seq
                    from $tbl a,
                         products b,
                         userinfo c
                   where a.product_id = b.product_id and
                         b.supply_code = c.code and
                         a.crdate >= '$start_date 00:00:00' and
                         a.crdate <= '$end_date 23:59:59' and
                         a.bad = $stock_type";

        if( $work_type == 'stockin' )
            $query .= " and a.job = 'in'";
        else if( $work_type == 'stockout' )
            $query .= " and a.job = 'out'";
        else if( $work_type != 'all' )
            $query .= " and a.job = '$work_type'";
        
        // 공급처
        if( $supply_code )
            $query .= " and c.code = $supply_code";
        
        // 검색
        if( $string )
        {
            if( $query_type == 'name' )
                $query .= " and b.name like '%$string%' ";
            else if( $query_type == 'options' )
                $query .= " and b.options like '%$string%' ";
            else if( $query_type == 'product_id' )
                $query .= " and b.product_id = '$string' ";
            else if( $query_type == 'memo' )
                $query .= " and a.memo like '%$string%' ";
        }
        $query .= " order by a.crdate desc ";
        
        return $query;
    }


    function save_file()
    {
        global $template, $connect, $page, $line_per_page, $link_url;
        global $supply_code, $query_type, $string, $start_date, $end_date, $work_type, $stock_type, $is_del;

        $query = $this->get_DP00_query();
        $result = mysql_query($query, $connect);
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_datas[] = array(
                 'crdate'       => $data[a_crdate]
                ,'supply_name'  => $data[c_name]
                ,'product_id'   => $data[b_product_id]
                ,'product_name' => $data[b_name]
                ,'options'      => $data[b_options]
                ,'job'          => class_stock::get_job_str( $data[a_job] )
                ,'qty'          => $data[a_qty]
                ,'stock'        => $data[a_stock]
                ,'order_seq'    => $data[a_order_seq]
                ,'sheet'        => $data[a_sheet]
                ,'owner'        => $data[a_owner]
                ,'memo'         => $data[a_memo]
            );
        }

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
        $buffer .= "<th class=header_item>작업일  </th>\n";
        $buffer .= "<th class=header_item>공급처  </th>\n";
        $buffer .= "<th class=header_item>상품코드</th>\n";
        $buffer .= "<th class=header_item>상품명  </th>\n";
        $buffer .= "<th class=header_item>옵션    </th>\n";
        $buffer .= "<th class=header_item>작업    </th>\n";
        $buffer .= "<th class=header_item>수량    </th>\n";
        $buffer .= "<th class=header_item>재고    </th>\n";
        $buffer .= "<th class=header_item>관리번호</th>\n";
        $buffer .= "<th class=header_item>전표번호</th>\n";
        $buffer .= "<th class=header_item>작업자  </th>\n";
        $buffer .= "<th class=header_item>메모    </th>\n";

        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        foreach ( $arr_datas as $row ) 
        {
            $buffer = "<tr>\n";
            $buffer .= "<td class=str_item_center>" . $row[crdate]       . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[supply_name]  . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[product_id]   . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[product_name] . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[options]      . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[job]          . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[qty]          . "</td>\n";
            $buffer .= "<td class=num_item       >" . $row[stock]        . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[order_seq]    . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[sheet]        . "</td>\n";
            $buffer .= "<td class=str_item_center>" . $row[owner]        . "</td>\n";
            $buffer .= "<td class=str_item       >" . $row[memo]         . "</td>\n";
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
    
    // 재고취소 위한 재고변동 확인 
    function check_stock_change()
    {
        global $connect, $seq;
        
        $val = array();
        $val['error'] = 0;
        
        $query = "select * from stock_tx_history where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $product_id = $data[product_id];
        
        // 해당 작업 이후에 로그가 있는지 확인
        $query = "select count(*) cnt from stock_tx_history where seq>$seq and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val['cnt'] = $data[cnt];

        // 해당 작업 이후에 로그가 있을 경우, 그 중에서 조정이 있는지 확인
        if( $data[cnt] > 0 )
        {
            $query = "select * from stock_tx_history where seq>$seq and product_id='$product_id' and job='arrange'";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
                $val['error'] = 1;
        }
        
        echo json_encode( $val );
    }
    
    // 재고취소 처리
    function cancel_stock()
    {
        global $connect, $seq;
        
        $val = array();
        
        // Lock Check
        $obj_lock = new class_lock(207);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $query = "select * from stock_tx_history where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $product_id = $data[product_id];
        $job = $data[job];
        $qty_job = $data[qty];
        $qty_stock = $qty_job;
        $bad = $data[bad];
        $crdate = substr( $data[crdate], 0, 10 );
        
        // job이 arrange 인 경우 이전 로그를 조사하여 증가수량 구한다.
        if( $job == "arrange" )
        {
            $query_ar = "select * from stock_tx_history where product_id='$product_id' and seq < $seq order by seq desc limit 1";
            $result_ar = mysql_query($query_ar, $connect);
            if( $data_ar = mysql_fetch_assoc($result_ar) )
                $qty_stock = $qty_job - $data_ar[stock];
        }
        else if( $job == "out" || $job == "retout" )
            $qty_stock = $qty_job * -1;

        // 현재로그를 취소로 이동
        $query = "insert stock_tx_history_del select * from stock_tx_history where seq=$seq";
        mysql_query($query, $connect);
        
        // 현재로그 삭제
        $query = "delete from stock_tx_history where seq=$seq";
        mysql_query($query, $connect);
        
        // 이후 로그 변경
        $query = "update stock_tx_history set stock=stock-$qty_stock where product_id='$product_id' and bad=$bad and seq>$seq";
        mysql_query($query, $connect);
        
        // stock_tx 변경
        if( $job == "in" )  $job = "stockin";
        else if( $job == "out" )  $job = "stockout";
        
        if( $job == "arrange" )
            $query = "update stock_tx set $job=$job-$qty_stock, stock=stock-$qty_stock where product_id='$product_id' and bad=$bad and crdate>='$crdate'";
        else
            $query = "update stock_tx set $job=$job-$qty_job, stock=stock-$qty_stock where product_id='$product_id' and bad=$bad and crdate>='$crdate'";
        mysql_query($query, $connect);
        
        // 현재고 변경
        $query = "update current_stock set stock=stock-$qty_stock where product_id='$product_id' and bad=$bad";
        mysql_query($query, $connect);
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        $val['error'] = 0;
        echo json_encode( $val );
    }
    
}

?>
