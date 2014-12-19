<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_lock.php";

// 중복상품 삭제
class class_CO00 extends class_top
{
    //////////////////////////////////////////////////////
    // 개별 삭제
    function CO00()
    {
        global $template, $page, $name, $options, $supply_code, $link_url_list, $del_product_name, $del_options, $del_product_id;
        global $del_order_qty, $del_stock_qty, $del_stock_bad_qty, $modify_product_name, $modify_options, $modify_product_id, $clear_data;
          
        $par_arr = array("template","action","name","options","supply_code","page","del_product_name","del_options","del_product_id",
                         "del_order_qty","del_stock_qty","del_stock_bad_qty","modify_product_name","modify_options","modify_product_id", "clear_data");
        
        // 중복상품 삭제 완료시 선택된 상품 정보를 초기화한다.
        if( $clear_data )
        {
            $_REQUEST["del_product_name"] = "";
            $_REQUEST["del_options"] = "";
            $_REQUEST["del_product_id"] = "";
            $_REQUEST["del_order_qty"] = "";
            $_REQUEST["del_stock_qty"] = "";
            $_REQUEST["del_stock_bad_qty"] = "";
            $_REQUEST["modify_product_name"] = "";
            $_REQUEST["modify_options"] = "";
            $_REQUEST["modify_product_id"] = "";
            $_REQUEST["clear_data"] = "";
        }
        
        $link_url_list = $this->build_link_par($par_arr);  
        $line_per_page = _line_per_page;
        
        // 판매처별 상품 리스트를 가져온다 
        if ( $_REQUEST["page"] )
        {
            echo "<script> show_waiting() </script>";    
            $result = $this->get_product_list( &$total_rows );
        }    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        if ( $_REQUEST["page"] )
            echo "<script> hide_waiting() </script>";

        $this->un_build_link_par($par_arr);
    }
     
    //////////////////////////////////////////////////////
    // 일괄 삭제
    function CO01()
    {
        global $template;
          
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        
        echo "<script> hide_waiting() </script>";
        $this->un_build_link_par($par_arr);
    }
     
    //////////////////////////////////////////////////////
    // 로그
    function CO02()
    {
        global $connect, $template, $action, $page;
        global $start_date, $end_date, $supply_code, $del_product_id, $del_product_name, $del_options,
               $change_product_id, $change_product_name, $change_options;

        if( !$start_date )  $start_date = date('Y-m-d', strtotime('-30 day'));
        
        $par_arr = array("template","action","supply_code","start_date","end_date","page",
                         "del_product_id","del_product_name","del_options","change_product_id","change_product_name","change_options");
        $link_url_list = $this->build_link_par($par_arr);     
  
        if ( $page )
        {
            $query = "select * from change_product_log a, products b, products c
                       where a.crdate >= '$start_date 00:00:00' and a.crdate <= '$end_date 23:59:59' and
                             a.del_product_id=b.product_id and
                             a.change_product_id=c.product_id ";
            if( $supply_code )
                $query .= " and a.supply_id='$supply_code'";
            if( $del_product_id )
                $query .= " and a.del_product_id='$del_product_id'";
            if( $del_product_name )
                $query .= " and b.name like '%$del_product_name%'";
            if( $del_options )
                $query .= " and b.options like '%$del_options%'";
            if( $change_product_id )
                $query .= " and a.change_product_id='$change_product_id'";
            if( $change_product_name )
                $query .= " and c.name like '%$change_product_name%'";
            if( $change_options )
                $query .= " and c.options like '%$change_options%'";

            $result = mysql_query($query, $connect);
            $total_rows = mysql_num_rows($result);
            
            $line_per_page = 20;
            $starter = ($page-1) * $line_per_page;
            $query .= " order by seq desc limit $starter, $line_per_page";
            $result = mysql_query($query, $connect);
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
     
    // 상품 검색 리스트
    function get_product_list( &$total_rows )
    {
        global $connect, $name, $options;
        
        $page = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
        $starter = ($page - 1) * 20;
        
        $query_cnt = "select count(*) cnt ";
        $query = "select * ";
        
        $option = " from products ";
        
        if ( $name && $options )
            $option .= " where name like '%$name%' and options like '%$options%' ";
        else if ( $name )
            $option .= " where name like '%$name%' ";
        else if ( $options )
            $option .= " where options like '%$options%' ";
            
        else   
        $option .= " where name like '%$string%' ";
        
        if ( $_REQUEST["supply_code"] )
            $option .= " and supply_code =  '" . $_REQUEST["supply_code"] . "'";
        
        $option .= " and is_delete = 0";
        
        // 대표 상품만 검색한다.
        $option .= " and ( stock_manage=0 or is_represent=0 ) ";
        
        $result = mysql_query ( $query_cnt . $option, $connect );
        $data = mysql_fetch_array ( $result );
        $total_rows = $data[cnt];
                  
        $limit = " order by reg_date desc limit $starter, " . _line_per_page;
        
        $result = mysql_query ( $query . $option . $limit, $connect );
        return $result;
    }
    
    // order_products 주문수량
    function orders_qty($product_id)
    {
        global $connect;
        $query = "select sum(qty) as qty from order_products
                    where product_id='$product_id' group by product_id";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        
        return ($data[qty] ? $data[qty] : "0");
    }
    
    //삭제 / 변경 상품 선택
    function get_products()
    {
        global $connect, $product_id;
         
        $query_count = "select count(*) as cnt from products where product_id='$product_id'";
        $result_count = mysql_query($query_count, $connect);
        
        $rows_data = mysql_fetch_assoc($result_count);
        $total_row = $rows_data[cnt];  
                
        $query = "select a.product_id, a.name,a.options, a.supply_code
                    from order_products c, products a left outer join current_stock b
                    on a.product_id=b.product_id 
                    where a.product_id='$product_id' group by a.product_id";
         
        $result = mysql_query($query, $connect);
        
        $products = array();
        $products='';
        $i=0;
        while( $data = mysql_fetch_array($result) )
        {   
            $i++;            
            $order_qty = $this->orders_qty($data[product_id]);
            
            $products[] = array(
                product_id     => $data[product_id],
                name           => $data[name],
                options        => $data[options], 
                stock_qty      => class_stock::get_current_stock($data[product_id]),
                stock_bad_qty  => class_stock::get_current_stock($data[product_id],1),
                order_qty      => $order_qty,
                supply_code    => $data[supply_code]
            );
        }        
        echo json_encode($products);   
    }
    
    function delete_dup_product()
    {
        global $connect, $del_product_id, $modify_product_id, $stock_qty, $stock_bad_qty, $type, $supply_code;

        // Lock Check
        $obj_lock = new class_lock(401);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['success'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        $val['success'] = $this->modify_stock();
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['success'] = -9;
            $val['lock_msg'] = $msg;
        }
        echo json_encode( $val );
    }

    // 실제 상품 삭제, 변경 실행
    function modify_stock()
    {
        global $connect, $del_product_id, $modify_product_id, $stock_qty, $stock_bad_qty, $type, $supply_code;
        
        // 리턴
        $arr = array();

        ///////////////////////////////////////
        // 상품삭제 여부
        $query = "select is_delete from products where product_id='$del_product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            if( $data[is_delete] )
                $arr['success'] = -1;
        }
        else
            $arr['success'] = -2;
            
        ///////////////////////////////////////
        // 상품삭제 여부
        $query = "select is_delete from products where product_id='$modify_product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            if( $data[is_delete] )
                $arr['success'] = -3;
        }
        else
            $arr['success'] = -4;

        ///////////////////////////////////////
        // 상품이 이미 삭제되었거나, 없는 상품일 경우
        if( $arr['success'] < 0 )
        {
            return $arr['success'];
        }

        ///////////////////////////////////////
        // 로그 남기기
        
        // 주문 order_products seq 구하기
        $arr_seq = array();
        $query = "select seq from order_products where product_id='$del_product_id'";
        $result = mysql_query($query, $connect);
        while($data=mysql_fetch_assoc($result))
            $arr_seq[] = $data[seq];
            
        // 주문 order_products order_seq 구하기
        $arr_order_seq = array();
        $query = "select distinct order_seq from order_products where product_id='$del_product_id'";
        $result = mysql_query($query, $connect);
        while($data=mysql_fetch_assoc($result))
            $arr_order_seq[] = $data[order_seq];

        // 재고로그 stock_tx_history seq 구하기
        $arr_stock_log = array();
        $query = "select seq from stock_tx_history where product_id='$del_product_id'";
        $result = mysql_query($query, $connect);
        while($data=mysql_fetch_assoc($result))
            $arr_stock_log[] = $data[seq];

        // 로그 기록
        $query = "insert change_product_log
                     set crdate            = now(),
                         worker            = '$_SESSION[LOGIN_NAME]',
                         supply_id         = $supply_code,
                         del_seq           = '" . implode(",",$arr_seq) . "',
                         del_order_seq     = '" . implode(",",$arr_order_seq) . "',
                         del_product_id    = '$del_product_id',
                         del_log           = '" . implode(",",$arr_stock_log) . "',
                         change_product_id = '$modify_product_id',
                         stock_qty         = $stock_qty,
                         stock_bad_qty     = $stock_bad_qty";
        mysql_query($query, $connect);
        
        // 주문 변경
        $query = "update order_products set product_id = '$modify_product_id' where product_id = '$del_product_id' ";
        mysql_query( $query, $connect );

        // 정상재고
        for($_bad=0; $_bad<2; $_bad++)
        {
            $query_del_stock = "select * from current_stock where product_id='$del_product_id' and bad=$_bad";
            $result_del_stock = mysql_query($query_del_stock, $connect);
            if( mysql_num_rows($result_del_stock) )
            {
                $data_del_stock = mysql_fetch_assoc($result_del_stock);
    
                // 현재고를 직접 변경. 로그는 아래에서 변경
                $query_stock_exist = "select * from current_stock where product_id='$modify_product_id' and bad=$_bad";
                $result_stock_exist = mysql_query($query_stock_exist, $connect);
                if( mysql_num_rows($result_stock_exist) )
                {
                    $query_stock = "update current_stock 
                                       set stock = stock + $data_del_stock[stock]
                                          ,move = move + $data_del_stock[move] 
                                     where product_id = '$modify_product_id' 
                                       and bad=$_bad";
                }
                else
                {
                    $query_stock = "insert current_stock 
                                       set stock = $data_del_stock[stock]
                                          ,move = $data_del_stock[move] 
                                          ,product_id = '$modify_product_id'
                                          ,location = 'Def'
                                          ,bad = $_bad";
                }
                mysql_query($query_stock, $connect);
            }
        }
        
        // 재고로그 변경
        $query = "update stock_tx_history set product_id='$modify_product_id' where product_id='$del_product_id'";
        mysql_query($query, $connect);
        
        // 재고 로그 다시 계산 - 정상재고
        $ff = true;
        $old_stock = 0;
        $query = "select * from stock_tx_history where product_id='$modify_product_id' and bad=0 order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 첫 log
            if( $ff )
            {
                $ff = false;
                $old_stock = $data[stock];
                continue;
            }
            
            switch( $data[job] )
            {
                case 'in':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'out':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'trans':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'arrange':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'movein':
                    $new_stock = $old_stock;
                    break;
                case 'moveout':
                    $new_stock = $old_stock;
                    break;
                case 'retin':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'retout':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'SHOP_REQ':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'HQ_RETURN':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'MOVE':
                    $new_stock = $old_stock;
                    break;
            }
            
            // 새 stock으로 변경
            $query = "update stock_tx_history set stock=$new_stock where seq=$data[seq]";
            mysql_query($query, $connect);
            
            $old_stock = $new_stock;
        }
        
        // 재고 로그 다시 계산 - 불량재고
        $ff = true;
        $old_stock = 0;
        $query = "select * from stock_tx_history where product_id='$modify_product_id' and bad=1 order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 첫 log
            if( $ff )
            {
                $ff = false;
                $old_stock = $data[stock];
                continue;
            }
            
            switch( $data[job] )
            {
                case 'in':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'out':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'trans':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'arrange':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'movein':
                    $new_stock = $old_stock;
                    break;
                case 'moveout':
                    $new_stock = $old_stock;
                    break;
                case 'retin':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'retout':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'SHOP_REQ':
                    $new_stock = $old_stock - $data[qty];
                    break;
                case 'HQ_RETURN':
                    $new_stock = $old_stock + $data[qty];
                    break;
                case 'MOVE':
                    $new_stock = $old_stock;
                    break;
            }
            
            // 새 stock으로 변경
            $query = "update stock_tx_history set stock=$new_stock where seq=$data[seq]";
            mysql_query($query, $connect);
            
            $old_stock = $new_stock;
        }

        // 일자별 재고 삭제 -> 변경이 불가한 이유는 날짜별로 중복되는 경우 생김
        $query = "delete from stock_tx where product_id in ('$modify_product_id','$del_product_id')";
        mysql_query($query, $connect);
        
        // 일자별 재고 다시 생성 - 정상
        $old_stock = 0;
        $query = "select * from stock_tx_history where product_id='$modify_product_id' and bad=0 order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 작업
            if( $data[job] == "in" || $data[job] == "out" )
                $job = "stock" . $data[job];
            else if( $data[job] == "MOVE" )
                continue;
            else
                $job = $data[job];
                
            // 날짜 레코드가 있는지 확인
            $cdate = substr( $data[crdate], 0, 10);
            $query_date = "select * from stock_tx where product_id='$modify_product_id' and crdate='$cdate' and bad=$data[bad]";
            $result_date = mysql_query($query_date, $connect);
            
            // 있으면 update
            if( mysql_num_rows($result_date) )
            {
                $query_run = "update stock_tx 
                                 set $job = $job + $data[qty],
                                     stock = $data[stock]
                               where product_id='$modify_product_id' and
                                     crdate = '$cdate' and
                                     bad = $data[bad]";
            }
            // 없으면 insert
            else
            {
                $query_run = "insert stock_tx 
                                 set crdate = '$cdate',
                                     product_id='$modify_product_id',
                                     location = 'Def',
                                     bad = $data[bad],
                                     $job = $data[qty],
                                     stock = $data[stock]";
            }
            mysql_query($query_run, $connect);
        }

        // 일자별 재고 다시 생성 - 불량
        $old_stock = 0;
        $query = "select * from stock_tx_history where product_id='$modify_product_id' and bad=1 order by seq";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
        {
            // 작업
            if( $data[job] == "in" || $data[job] == "out" )
                $job = "stock" . $data[job];
            else if( $data[job] == "MOVE" )
                continue;
            else
                $job = $data[job];
                
            // 날짜 레코드가 있는지 확인
            $cdate = substr( $data[crdate], 0, 10);
            $query_date = "select * from stock_tx where product_id='$modify_product_id' and crdate='$cdate' and bad=$data[bad]";
            $result_date = mysql_query($query_date, $connect);
            
            // 있으면 update
            if( mysql_num_rows($result_date) )
            {
                $query_run = "update stock_tx 
                                 set $job = $job + $data[qty],
                                     stock = $data[stock]
                               where product_id='$modify_product_id' and
                                     crdate = '$cdate' and
                                     bad = $data[bad]";
            }
            // 없으면 insert
            else
            {
                $query_run = "insert stock_tx 
                                 set crdate = '$cdate',
                                     product_id='$modify_product_id',
                                     location = 'Def',
                                     bad = $data[bad],
                                     $job = $data[qty],
                                     stock = $data[stock]";
            }
            mysql_query($query_run, $connect);
        }
        
        // 삭제된 상품 현재고 삭제
        $query = "delete from current_stock where product_id='$del_product_id'";
        mysql_query($query, $connect);
        
        // 상품 삭제 is_delete = 1;       
        $query = "update products set is_delete=1, delete_date=Now() where product_id = '$del_product_id' ";
        mysql_query( $query, $connect );

        // 매칭정보 변경
        $query = "update code_match set id = '$modify_product_id' where id = '$del_product_id' ";
        mysql_query( $query, $connect );

        // 삭제 상품의 org_id 검색
        $query = "select org_id from products where product_id = '$del_product_id' ";
        $result = mysql_query( $query, $connect );
        $data_org_id = mysql_fetch_assoc( $result );
        
        // org_id 상품 정보
        $query = "select * from products where org_id = '$data_org_id[org_id]' and is_delete=0 ";
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) == 0 )
        {
            // 남은 옵션상품이 없는 경우 대표상품 삭제 is_delete = 1;
            $query = "update products set is_delete=1, delete_date=Now() where product_id = '$data_org_id[org_id]' ";
            mysql_query( $query, $connect );
        }

        // 바코드그룹 설정
        $query = "select barcode from products where product_id='$del_product_id' ";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $del_barcode = $data[barcode];
        
        if( $del_barcode > '' )
        {
            $new_barcode = array();
            $new_barcode[] = $del_barcode;
            
            $query = "select * from barcode_group where org_barcode='$del_barcode'";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( $data[barcode] > '' )
                    $new_barcode[] = $data[barcode];
            }
            $query = "delete from barcode_group where org_barcode='$del_barcode'";
            mysql_query($query, $connect);
            
            $query = "select barcode from products where product_id='$modify_product_id' ";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            $org_barcode = $data[barcode];
            
            foreach($new_barcode as $b)
            {
                if( $b != $org_barcode)
                {
                    $query = "insert barcode_group 
                                 set barcode = '$b',
                                     org_barcode = '$org_barcode',
                                     crdate = now(),
                                     worker = '$_SESSION[LOGIN_NAME]'";
                    mysql_query($query, $connect);
                }
            }
        }
        
        // ecn product change
        if( $_SESSION[IS_ECN_USE] )
            $this->ecn_product_change();

        // 성공
        return 0;        
    }
        
    // 중복상품 삭제 일괄 업로드
    function upload()
    {
        global $connect, $admin_file, $_file;
        
        $this->show_wait();

        // Lock Check
        $obj_lock = new class_lock(402);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect("?template=CO01");
            exit;
        }

        $arr = array();
        $obj = new class_file();
        if( $obj->upload2('', &$arr) )
        {
            // Lock End
            if( !$obj_lock->set_end(&$msg) )
            {
                $this->jsAlert($msg);
            }
            $this->redirect("?template=CO01");
            exit;
        }

        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더

            // 필수 입력 항목이 없으면 넘어간다.
            if( !$row[0] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 삭제될 상품코드를 입력하세요 <br> ";
                continue;
            }else if( !$row[1] ){
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 변경될 상품코드를 입력하세요 <br> ";
                continue;
            }

            // 삭제될 상품코드 검사
            $del_product = $row[0];
            $query = "select * from products where product_id='$del_product'";
            $result = mysql_query($query, $connect);
            $data_del = mysql_fetch_assoc($result);
            
            // 삭제될 상품코드가 존재하지 않는다.
            if( !$data_del[product_id] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 삭제될 상품코드가 잘못되었습니다.<br> ";
                continue;
            }

            // 삭제될 상품코드가 이미 삭제된 상품코드
            if( $data_del[is_delete] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 삭제될 상품코드가 이미 삭제되었습니다.<br> ";
                continue;
            }

            // 삭제될 상품코드가 대표상품코드
            if( $data_del[is_represent] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 대표상품코드를 삭제할 수 없습니다.<br> ";
                continue;
            }

            // 변경될 상품코드 검사
            $change_product = $row[1];
            $query = "select * from products where product_id='$change_product'";
            $result = mysql_query($query, $connect);
            $data_change = mysql_fetch_assoc($result);
            
            // 변경될 상품코드가 존재하지 않는다.
            if( !$data_change[product_id] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 변경될 상품코드가 잘못되었습니다.<br> ";
                continue;
            }

            // 변경될 상품코드가 이미 삭제된 상품코드
            if( $data_change[is_delete] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 변경될 상품코드가 이미 삭제되었습니다.<br> ";
                continue;
            }

            // 변경될 상품코드가 대표상품코드
            if( $data_change[is_represent] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 대표상품코드로 변경할 수 없습니다.<br> ";
                continue;
            }

            // 공급처가 다르면 삭제 불가
            if( $data_del[supply_code] != $data_change[supply_code] )
            {
                if( $err_cnt++ < 20 )
                    $err_result .= " $i 행 : 공급처가 다르면 변경할 수 없습니다.<br> ";
                continue;
            }

            // 삭제 실행
            global $del_product_id, $modify_product_id, $stock_qty, $stock_bad_qty, $type, $supply_code;
            
            $del_product_id = $data_del[product_id];
            $modify_product_id = $data_change[product_id];
            $stock_qty = class_stock::get_current_stock($del_product_id);
            $stock_bad_qty = class_stock::get_current_stock($del_product_id,1);
            $type = "arrange";
            $supply_code = $data_del[supply_code];
            
            $this->modify_stock();

            $this->show_txt( $i . "/" . count($arr));          
            $n++;
        }
       
        $this->hide_wait();
    
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
            $this->jsAlert($msg);

        $this->jsAlert("$n 개 처리되었습니다.");
        
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=CO01&err_cnt=$err_cnt&err_result=$err_result");
        
    }

    function ecn_product_change()
    {
        global $connect, $del_product_id, $modify_product_id, $stock_qty, $stock_bad_qty, $type, $supply_code;
        
        // 매장별로 처리
        $w_list = array();
        $query = "select distinct warehouse_seq from ecn_current_stock where product_id in ('$del_product_id', '$modify_product_id')";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
            $w_list[] = $data[warehouse_seq];

        // 재고로그 변경
        $query = "update ecn_stock_tx_history set product_id='$modify_product_id' where product_id='$del_product_id'";
        mysql_query($query, $connect);

        // 일자별 재고 삭제 -> 변경이 불가한 이유는 날짜별로 중복되는 경우 생김
        $query = "delete from ecn_stock_tx where product_id in ('$modify_product_id','$del_product_id')";
        mysql_query($query, $connect);
            
        foreach( $w_list as $w_seq )
        {
            for($_bad=0; $_bad<=1; $_bad++)
            {
                // 현재재고
                $del_stock_qty = $this->get_ecn_shop_stock($w_seq, $del_product_id, $_bad);
                if ( $del_stock_qty['stock'] || $del_stock_qty['move'] )
                {
                    $modify_stock_qty = $this->get_ecn_shop_stock($w_seq, $modify_product_id, $_bad);
                    
                    // 삭제 상품 정상재고 수량 + 변경될 상품 정상재고 수량
                    $qty  = $del_stock_qty['stock'] + $modify_stock_qty['stock'];
                    $move = $del_stock_qty['move']  + $modify_stock_qty['move'];
                    
                    // 현재고를 직접 변경. 로그는 아래에서 변경
                    $query_stock_exist = "select * from ecn_current_stock where warehouse_seq = $w_seq and product_id='$modify_product_id' and bad=$_bad";
                    $result_stock_exist = mysql_query($query_stock_exist, $connect);
                    if( mysql_num_rows($result_stock_exist) )
                        $query_stock = "update ecn_current_stock set qty=$qty, move=$move where warehouse_seq = $w_seq and product_id='$modify_product_id' and bad=$_bad";
                    else
                        $query_stock = "insert ecn_current_stock set qty=$qty, move=$move, warehouse_seq = $w_seq, product_id='$modify_product_id',bad=$_bad";
debug("ecn 중복삭제 : " . $query_stock);
                    mysql_query($query_stock, $connect);
                }
    
                // 재고 로그 다시 계산
                $ff = true;
                $old_stock = 0;
                $old_move = 0;
                $query = "select * from ecn_stock_tx_history where warehouse_seq=$w_seq and product_id='$modify_product_id' and bad=$_bad order by seq";
                $result = mysql_query($query, $connect);
                while($data = mysql_fetch_assoc($result))
                {
                    // 첫 log
                    if( $ff )
                    {
                        $ff = false;
                        $old_stock = $data[stock];
                        $old_move  = $data[move];
                        continue;
                    }
                    
                    switch( $data[work] )
                    {
                        case "STOCK_IN":
                            $new_stock = $old_stock + $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "STOCK_OUT":
                            $new_stock = $old_stock - $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "SHOP_REQ":
                            $new_stock = $old_stock + $data[qty];
                            $new_move  = $old_move - $data[qty];
                            break;
                        case "HQ_RETURN":
                            $new_stock = $old_stock - $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "MOVE_IN":
                            $new_stock = $old_stock + $data[qty];
                            $new_move  = $old_move - $data[qty];
                            break;
                        case "MOVE_OUT":
                            $new_stock = $old_stock - $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "CUST_RETURN":
                            $new_stock = $old_stock + $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "ARRANGE":
                            $new_stock = $old_stock + $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "SHOP_SELL":
                            $new_stock = $old_stock - $data[qty];
                            $new_move  = $old_move;
                            break;
                        case "MOVE":
                            $new_stock = $old_stock;
                            $new_move  = $old_move + $data[qty];
                            break;
                    }
                    
                    // 새 stock으로 변경
                    $query = "update ecn_stock_tx_history set stock=$new_stock, move=$new_move where seq=$data[seq]";
                    mysql_query($query, $connect);
                    
                    $old_stock = $new_stock;
                    $old_move  = $new_move;
                }
    
                // 일자별 재고 다시 생성
                $old_stock = 0;
                $query = "select * from ecn_stock_tx_history where warehouse_seq=$w_seq and product_id='$modify_product_id' and bad=$_bad order by seq";
                $result = mysql_query($query, $connect);
                while($data = mysql_fetch_assoc($result))
                {
                    // 작업
                    if( $data[work] == "MOVE" )
                        $job_str = "";
                    else
                        $job_str = ", $data[work] = $data[work] + $data[qty]";
                        
                    // 날짜 레코드가 있는지 확인
                    $cdate = substr( $data[crdate], 0, 10);
                    $query_date = "select * from ecn_stock_tx where warehouse_seq=$w_seq and product_id='$modify_product_id' and crdate='$cdate' and bad=$_bad";
                    $result_date = mysql_query($query_date, $connect);
                    
                    // 있으면 update
                    if( mysql_num_rows($result_date) )
                    {
                        $query_run = "update ecn_stock_tx 
                                         set stock=$data[stock],
                                             move=$data[move]
                                             $job_str
                                       where warehouse_seq = $w_seq and
                                             product_id='$modify_product_id' and
                                             crdate = '$cdate' and
                                             bad = $_bad";
                    }
                    // 없으면 insert
                    else
                    {
                        $query_run = "insert ecn_stock_tx 
                                         set crdate = '$cdate',
                                             warehouse_seq=$w_seq,
                                             product_id='$modify_product_id',
                                             bad = $_bad,
                                             stock=$data[stock],
                                             move=$data[move]
                                             $job_str";
                    }
                    mysql_query($query_run, $connect);
                }
            }
        }

        // 삭제된 상품 현재고 삭제
        $query = "delete from ecn_current_stock where product_id='$del_product_id'";
        mysql_query($query, $connect);

        // ecn 전체 테이블 product_id 변경
        $tbl_arr = $this->find_tables_having_field("product_id", "ecn_");
        foreach( $tbl_arr as $tbl_name )
        {
            if( $tbl_name == "ecn_current_stock" || $tbl_name == "ecn_stock_tx" || $tbl_name == "ecn_stock_tx_history" )  continue;
            
            $query = "update $tbl_name set product_id='$modify_product_id' where product_id='$del_product_id'";
            mysql_query($query, $connect);
        }
        
    }

    function get_ecn_shop_stock($warehouse_seq, $product_id, $bad=0)
    {
        global $connect;
        
        $query = "select qty, move from ecn_current_stock where warehouse_seq=$warehouse_seq and product_id='$product_id' and bad=$bad";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);

        $re = array(
            "stock" => $data[qty],
            "move"  => $data[move]
        );
        
        return $re;
    }
}
?>
