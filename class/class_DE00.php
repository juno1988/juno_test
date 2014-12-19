<?
require_once "class_top.php";
//require_once "class_A.php";
require_once "class_file.php";
require_once "class_lock.php";
require_once "class_shop.php";

////////////////////////////////
// class name: class_DE00
//

class class_DE00 extends class_top {
    ///////////////////////////////////////////
    // DE00

    function DE01()
    {
        global $connect;
        global $template, $line_per_page;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function tt( $data_array )
    {
        // print_r ( $data_array );
        echo "data:  $data_array[0] / $data_array[1] / $data_array[2] <br>";
    }

    function upload()
    {
        global $top_url, $connect, $_file;

        $obj = new class_file();
        $obj->upload_ref( $this, "tt" );


        // $obj->upload_ref();

    //    $arr_result = $obj->upload();

exit;
        foreach ( $arr_result as $row )
        {
            $rows++;
            if ( $rows == 1 ) continue;
 
            $infos['collect_date'] = $row[0]; // A
            $infos['trans_no']     = $row[1];
            $infos['recv_name']    = $row[2];
            $infos['product_name'] = $row[3]; // D
            $infos['recv_zip']     = $row[4];

            $infos['recv_address'] = $row[5] . $row[6]; // F,G
            $infos['recv_tel']     = $row[7];
            $infos['recv_mobile']  = $row[8];
            $infos['qty']          = $row[9]; // J
            $infos['trans_who']    = $row[10]; // K
            $infos['product_id']   = $row[11]; // K
            $infos['order_id']     = $row[12]; // K

            // $this->insert_info( $infos );
            ///////////////////////////////
            // sync product 
            $str = "${rows} / ${total_rows}번째 작업중입니다.";
            echo "<script>show_txt('$str');</script>";
            flush();
        }

        $this->hide_wait();
        $this->jsAlert ( "변경: $rows개의 작업 완료" );
        // $this->redirect ("?template=DE01" );
        exit;
    }

    //
    function insert_info( $infos )
    {
        global $connect;

        $query = "insert into orders set status=8, trans_date='2009-2-1', trans_date_pos='2009-2-1', ";

        $i = 0;
        foreach ( $infos as $key=>$value )
        {
            $i++;
            $query .= $key . "=\"". htmlspecialchars(addslashes($value)) . "\"";
            if ( count($infos) != $i )
                $query .= ",";
        }

        echo "<br>----<br>";
        echo $query;
        echo "<br>----<br>";

        mysql_query( $query, $connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////
    // DE00

    function DE00()
    {
        global $connect;
        global $template, $line_per_page, $act;
        $transaction = $this->begin("주문삭제 page open");        // 2009.1.19

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function delete()
    {
        global $connect;
        global $template, $shop_id, $collect_date, $begin_time, $end_time;
        
        $arr_auth = split(",", $_SESSION[AUTH]);
        if( array_search("E2", $arr_auth) !== false )
        {
            $this->jsAlert("주문삭제 권한이 없습니다.");
            $this->redirect("?template=DE00&act=query&start_date=$collect_date&begin_time=$begin_time&end_time=$end_time");
            exit;
        }

        $obj_lock = new class_lock(304);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect("?template=DE00&act=query&start_date=$collect_date&begin_time=$begin_time&end_time=$end_time");
            exit;
        }

        // 총 삭제수량
        $del_qty = 0;
        
        // 옥션, 지마켓 스마트배송은 배송상태도 삭제.
        if( $shop_id % 100 == 78 || $shop_id % 100 == 79 )
        {
            $query = "select seq
                        from orders 
                       where collect_date = '$collect_date' and 
                             collect_time >= '$begin_time' and 
                             collect_time <= '$end_time' and 
                             shop_id = '$shop_id' and 
                             c_seq = 0 and
                             copy_seq = 0 and
                             seq <> order_id";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $single_arr[] = $data[seq];
                
            $list_del = implode(",", $single_arr);
            $query_del = "insert orders_del select * from orders where seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "insert order_products_del select * from order_products where order_seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "delete from orders where seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "delete from order_products where order_seq in ($list_del) ";
            mysql_query($query_del, $connect);
            
            $del_qty += count( $single_arr );
            
        }
        else
        {
            //########################
            // 발주주문삭제
            //########################
            //
            // ** 삭제되지 않는 주문 **
            // 1. 배송된 주문 : status = 8
            // 2. 배송후교환 : c_seq > 0
            // 3. 주문복사 : copy_seq > 0
            // 4. 주문생성 : seq = order_id
            //
            
            //*******************
            // 합포주문
            //*******************
            $pack_arr = array();
            $query = "select pack
                        from orders 
                       where collect_date = '$collect_date' and 
                             collect_time >= '$begin_time' and 
                             collect_time <= '$end_time' and 
                             shop_id = '$shop_id' and 
                             pack > 0 and
                             status < 8 and
                             c_seq = 0 and
                             copy_seq = 0 and
                             seq <> order_id
                       group by pack";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $pack_arr[] = $data[pack];
                
            // 합포 주문이 있는경우, 합포주문 삭제처리
            if( $pack_arr )
            {
                $pack_str = implode(",",$pack_arr);
        
                //*******************
                // 배송된 합포
                //*******************
                $trans_pack = array();
                $query = "select pack from orders where status=8 and pack in ($pack_str) group by pack";
                $result = mysql_query($query, $connect);
                while($data = mysql_fetch_assoc($result))
                    $trans_pack[] = $data[pack];
                    
                if( $trans_pack )
                    $trans_str = implode(",",$trans_pack);
                else
                    $trans_str = "";
                
                //*******************
                // 삭제하면 합포가 찢어지는 경우
                //*******************
                $repack_arr = array();
                $query = "select seq,
                                 pack,
                                 order_id,
                                 collect_date,
                                 collect_time,
                                 c_seq,
                                 copy_seq
                            from orders 
                           where pack in ($pack_str) ";
                           
                // 배송된 합포가 있으면 제외
                if( $trans_str )
                    $query .= " and pack not in ($trans_str) ";
                    
                $query .= " order by pack";
                $result = mysql_query($query, $connect);
                while( $data=mysql_fetch_assoc($result) )
                {
                    $repack_arr[$data[pack]][] = array(
                        "seq"          => $data[seq],
                        "pack"         => $data[pack],
                        "order_id"     => $data[order_id],
                        "collect_date" => $data[collect_date],
                        "collect_time" => $data[collect_time],
                        "c_seq"        => $data[c_seq],
                        "copy_seq"     => $data[copy_seq]
                    );
                }
    
                foreach( $repack_arr as $repack )
                {
                    $pack_del = false;
                    $seq_del = array();
                    $seq_rem = array();
                    foreach( $repack as $pack_each )
                    {
                        // 삭제범위
                        if( $pack_each[collect_date] == $collect_date && $pack_each[collect_time] >= $begin_time && $pack_each[collect_time] <= $end_time )
                        {
                            // 삭제될 seq
                            if( $pack_each[seq] != $pack_each[order_id] && $pack_each[c_seq] == 0 && $pack_each[copy_seq] == 0 )
                            {
                                // 합포기준
                                if( $pack_each[seq] == $pack_each[pack] )
                                    $pack_del = true;
        
                                $seq_del[] = $pack_each[seq];
                            }
                            else
                                $seq_rem[] = $pack_each[seq];
                        }
                        else
                            $seq_rem[] = $pack_each[seq];
                    }
                    
                    // 합포 기준이 변경되는 경우
                    if( $pack_del )
                    {
                        // 범위 내
                        $list_rem = implode(",", $seq_rem);
                        if( count($seq_rem) > 1 )
                        {
                            $query_repack = "update orders set pack=$seq_rem[0] where seq in ($list_rem)";
                            mysql_query($query_repack, $connect);
                        }
                        else
                        {
                            $query_repack = "update orders set pack=0 where seq in ($list_rem)";
                            mysql_query($query_repack, $connect);
                        }
                    }
                    
                    // 주문 삭제
                    $list_del = implode(",", $seq_del);
                    $query_del = "insert orders_del select * from orders where seq in ($list_del) ";
                    mysql_query($query_del, $connect);
                    $query_del = "insert order_products_del select * from order_products where order_seq in ($list_del) ";
                    mysql_query($query_del, $connect);
                    $query_del = "delete from orders where seq in ($list_del) ";
                    mysql_query($query_del, $connect);
                    $query_del = "delete from order_products where order_seq in ($list_del) ";
                    mysql_query($query_del, $connect);
                    
                    $del_qty += count( $seq_del );
                }
            }
            
            //*******************
            // 단품주문
            //*******************
            $single_arr = array();
            $query = "select seq
                        from orders 
                       where collect_date = '$collect_date' and 
                             collect_time >= '$begin_time' and 
                             collect_time <= '$end_time' and 
                             shop_id = '$shop_id' and 
                             pack = 0 and
                             status < 8 and
                             c_seq = 0 and
                             copy_seq = 0 and
                             seq <> order_id";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
                $single_arr[] = $data[seq];
                
            $list_del = implode(",", $single_arr);
            $query_del = "insert orders_del select * from orders where seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "insert order_products_del select * from order_products where order_seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "delete from orders where seq in ($list_del) ";
            mysql_query($query_del, $connect);
            $query_del = "delete from order_products where order_seq in ($list_del) ";
            mysql_query($query_del, $connect);
            
            $del_qty += count( $single_arr );
        }
        
        // 로그 남기기
        $query = "insert delete_order_log
                     set crdate = now(),
                         worker = '$_SESSION[LOGIN_NAME]',
                         shop_id = $shop_id,
                         collect_date = '$collect_date',
                         begin_time = '$begin_time',
                         end_time = '$end_time',
                         qty = $del_qty";
        mysql_query($query, $connect);
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->redirect("?template=DE00&act=query&start_date=$collect_date&begin_time=$begin_time&end_time=$end_time");
    }
}

?>
