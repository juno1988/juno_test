<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_E900.php";
require_once "class_lock.php";

////////////////////////////////
// class name: class_DB00
//

class class_DB00 extends class_top {

    ///////////////////////////////////////////
    // DB00

    function DB00()
    {
        global $connect;
        global $template, $line_per_page, $activate;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function DB01()
    {
        global $connect;
        global $template, $line_per_page, $activate, $include_trans;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 역배송-파일
    function DB02()
    {
        global $connect;
        global $template, $line_per_page, $activate;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 역배송-스캔
    function DB03()
    {
        global $connect;
        global $template, $line_per_page, $activate;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 역배송 파일 업로드
    function upload3()
    {
        global $top_url, $connect, $_file;

        $this->show_wait();

        // 초기화
        $query = "delete from upload_temp where type='reverse_transno'";
        mysql_query ( $query, $connect );

        $obj = new class_file();
        $arr_result = $obj->upload();

        $rows = 0;
        foreach ( $arr_result as $row )
        {
            $t_no = preg_replace('/[^0-9]/','',$row[0]);
            if( !is_numeric($t_no) )  continue;
 
            $infos[shop_id]    = "transno";
            $infos[type]       = "reverse_transno";
            $infos[row]        = $rows;
            $infos[col]        = 1;
            $infos[value]      = $t_no;
            $this->insert_info( $infos );
            $rows++;
        }

        $this->hide_wait();
        $this->jsAlert ( $rows . " 개의 송장번호가 업로드 되었습니다." );
        $this->redirect ("?template=DB02" );
    }
    
    // 역배송 처리
    function reverse_trans()
    {
        global $connect;

        $this->show_wait();

        // 재고차감
        $obj = new class_stock();
        
        // 배송처리 제외 송장번호
        $transno_list = "";
        $query = "select value from upload_temp where type='reverse_transno'";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result))
            $transno_list .= ($transno_list ? "," : "") . $data[value];

        // 배송처리할 주문
        $rows = 0;
        $query = "select if(pack>0,pack,seq) seq_pack, seq, pack
                    from orders
                   where status = 7 and
                         hold = 0 and
                         order_cs not in (1,3) and
                         trans_no not in ($transno_list)
                   group by seq_pack";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $rows++; 
            
            // 배송처리
            $query_trans = "update orders set status=8, trans_date_pos=now() where "; 

            if( $data[pack] > 0 )
                $query_trans .= "pack=$data[pack]";
            else
                $query_trans .= "seq=$data[seq]";

            $query_trans .= " and order_cs not in (1,3) and hold=0"; 
            mysql_query($query_trans, $connect);
            
            // 재고차감
            $query_pid = "select b.order_seq b_order_seq,
                                 b.product_id b_product_id, 
                                 b.qty b_qty
                            from orders a, 
                                 order_products b 
                           where a.seq = b.order_seq and";

            if( $data[pack] > 0 )
                $query_pid .= "  a.pack = $data[pack] and ";
            else
                $query_pid .= "  a.seq = $data[seq] and ";

            $query_pid .= "      a.status = 8 and
                                 b.order_cs not in (1,2,3,4)";
            $result_pid = mysql_query($query_pid, $connect);
            while( $data_pid = mysql_fetch_assoc($result_pid) )
            {
                $obj->set_stock( array( type       => 'trans',
                                        product_id => $data_pid[b_product_id], 
                                        bad        => 0,
                                        location   => 'Def',
                                        qty        => $data_pid[b_qty],
                                        memo       => '역배송 처리',
                                        order_seq  => $data_pid[b_order_seq] ) );
            }
        }
        
        // 임시송장 삭제
        $query = "delete from upload_temp where type='reverse_transno'";
        mysql_query($query, $connect);

        $this->hide_wait();
        $this->jsAlert ( $rows . " 개의 송장이 배송처리되었습니다." );
        $this->redirect ("?template=DB03" );
    }
    
    // 역배송 처리
    function reverse_trans2()
    {
        global $connect, $list;

        // 재고차감
        $obj = new class_stock();
        
        // 배송처리 제외 송장번호
        $transno_list = $list;

        // 배송처리할 주문
        $rows = 0;
        $query = "select if(pack>0,pack,seq) seq_pack, seq, pack
                    from orders
                   where status = 7 and
                         hold = 0 and
                         order_cs not in (1,3) and
                         trans_no not in ($transno_list)
                   group by seq_pack";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $rows++; 
            
            // 배송처리
            $query_trans = "update orders set status=8, trans_date_pos=now() where "; 

            if( $data[pack] > 0 )
                $query_trans .= "pack=$data[pack]";
            else
                $query_trans .= "seq=$data[seq]";

            $query_trans .= " and order_cs not in (1,3) and hold=0"; 
            mysql_query($query_trans, $connect);
            
            // 재고차감
            $query_pid = "select b.order_seq b_order_seq,
                                 b.product_id b_product_id, 
                                 b.qty b_qty
                            from orders a, 
                                 order_products b 
                           where a.seq = b.order_seq and";

            if( $data[pack] > 0 )
                $query_pid .= "  a.pack = $data[pack] and ";
            else
                $query_pid .= "  a.seq = $data[seq] and ";

            $query_pid .= "      a.status = 8 and
                                 b.order_cs not in (1,2,3,4)";
            $result_pid = mysql_query($query_pid, $connect);
            while( $data_pid = mysql_fetch_assoc($result_pid) )
            {
                $obj->set_stock( array( type       => 'trans',
                                        product_id => $data_pid[b_product_id], 
                                        bad        => 0,
                                        location   => 'Def',
                                        qty        => $data_pid[b_qty],
                                        memo       => '역배송 처리',
                                        order_seq  => $data_pid[b_order_seq] ) );
            }
        }
        
        // 임시송장 삭제
        $query = "delete from upload_temp where type='reverse_transno'";
        mysql_query($query, $connect);

        $val = array();
        $val[cnt] = $rows;
        
        echo json_encode($val);
    }
    

    function DB10()
    {
        global $connect;
        global $template, $line_per_page, $activate;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function DB11()
    {
        global $connect;
        global $template, $line_per_page, $activate;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    ////////////////////////////////////////////////////
    //
    // 값 조회
    //
    function query()
    {
        global $connect, $template, $start_date, $begin_time, $end_time, $shop_id, $include_trans, $only_trans;

        $begin_time = $begin_time ? $begin_time : "00:00:00";        
        $end_time   = $end_time ? $end_time : "23:59:59";        

        // 배송주문포함
        if( $only_trans )
        {
            $date_type = "trans_date_pos";
            $status_con = "8";
        }
        else
        {
            $date_type = "trans_date";
            if( $include_trans == "on" )
                $status_con = "7,8";
            else
                $status_con = "7";
        }
        
        if ( $shop_id )
            $option .= " and shop_id = $shop_id";

        $query = "select * from orders 
                   where $date_type >= '$start_date $begin_time' 
                     and $date_type <= '$start_date $end_time' 
                         ${option} and status in ($status_con) ";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        $tot_rows = mysql_num_rows ( $result );

        // 전체 송장수
        $query_trans = $query . " group by trans_no";
        $result_trans = mysql_query($query_trans, $connect);
        $trans_total = mysql_num_rows($result_trans);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////////
    //
    // 송장 번호 삭제
    //
    function del_transno()
    {
        global $connect;
        global $template, $start_date, $begin_time, $end_time, $shop_id, $include_trans;

        $this->show_wait();

        $obj_lock = new class_lock(306);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->hide_wait();
            $this->query();
            exit;
        }

        // 재고차감
        $obj = new class_stock();

        // 배송주문포함
        if( $include_trans == "on" )
            $status_con = "7,8";
        else
            $status_con = "7";

        if ( $shop_id )
            $option .= " and shop_id = $shop_id";

        // cs insert

        ////////////////////////////////////////////////////
        //
        // cs 입력
        $query = "select seq,pack,status,trans_no,trans_corp,trans_date,trans_date_pos from orders           
                    where trans_date >= '$start_date $begin_time' 
                      and trans_date <= '$start_date $end_time'
                          ${option} and status in ($status_con) ";

        $pack_seq_arr = array();
        
        $result = mysql_query( $query, $connect );        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // 배송주문포함 - 재고 살리기
            if( $include_trans == "on" && $data[status]==8 )
            {
                $query_stock = "select * from order_products where order_seq=$data[seq] and order_cs not in (1,2)";
                $result_stock = mysql_query($query_stock, $connect);
                while( $data_stock = mysql_fetch_assoc($result_stock) )
                {
                    $obj->set_stock( array( type       => 'trans',
                                            product_id => $data_stock[product_id], 
                                            bad        => 0,
                                            location   => 'Def',
                                            qty        => -1 * $data_stock[qty],
                                            memo       => '송장일괄삭제 - 배송취소',
                                            order_seq  => $data_stock[order_seq] ) );
                }
            }

            if ( $data[pack] == 0 || $data[seq] == $data[pack] )
            {
                // 배송주문포함 - 배송취소
                if( $include_trans == "on" && $data[status]==8 )
                    class_E900::csinsert9($data["seq"],$data["pack"],7,"","송장일괄삭제 <배송pos일:".$data['trans_date_pos']."> ");
                                
                class_E900::csinsert9($data["seq"],$data["pack"],5,"","송장일괄삭제 <택배사:".$data['trans_corp']."><송장번호:".$data['trans_no']."><송장입력일:".$data['trans_date'].">");
            }
            
            // 합포기준주문이 취소인 주문 처리용
            if( $data[pack] > 0 )
            {
                $pack_seq_arr[$data[pack]][] = array(
                    "seq"            => $data[seq]
                   ,"pack"           => $data[pack]
                   ,"status"         => $data[status]
                   ,"trans_no"       => $data[trans_no]
                   ,"trans_corp"     => $data[trans_corp]
                   ,"trans_date"     => $data[trans_date]
                   ,"trans_date_pos" => $data[trans_date_pos]
                );
            }
        }
        
        // 합포기준주문이 취소인 주문 처리
        foreach( $pack_seq_arr as $pack_key => $pack_val )
        {
            // 합포 기준 주문이 포함된 경우 제외
            foreach( $pack_val as $order_val )
            {
                if( $pack_key == $order_val[seq] )
                    continue 2;
            }
            
            $data = $pack_val[0];

            // 배송주문포함 - 배송취소
            if( $include_trans == "on" && $data[status]==8 )
                class_E900::csinsert9($data["seq"],$data["pack"],7,"","송장일괄삭제 <배송pos일:".$data['trans_date_pos']."> ");
                            
            class_E900::csinsert9($data["seq"],$data["pack"],5,"","송장일괄삭제 <택배사:".$data['trans_corp']."><송장번호:".$data['trans_no']."><송장입력일:".$data['trans_date'].">");
        }

        $query = "update orders 
                     set trans_no   = '',
                         trans_date = '',
                         trans_date_pos = '',
                         status     = 1,
                         trans_corp = '',
                         auto_trans = 0,
                         trans_key  = 0
                   where trans_date >= '$start_date $begin_time' 
                     and trans_date <= '$start_date $end_time'
                         ${option} and status in ($status_con) ";
        mysql_query ( $query, $connect ) or die( mysql_error() );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->hide_wait();
        $this->query();
    }

    //////////////////////////////////////////////////
    //
    // 배송취소
    //
    function del_transno2()
    {
        global $connect;
        global $template, $start_date, $begin_time, $end_time, $shop_id;

        $this->show_wait();

        $obj_lock = new class_lock(309);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->hide_wait();
            $this->query();
            exit;
        }

        // 재고차감
        $obj = new class_stock();

        if ( $shop_id )
            $option .= " and shop_id = $shop_id";

        // cs insert

        ////////////////////////////////////////////////////
        //
        // cs 입력
        $query = "select seq,pack,status,trans_no,trans_corp,trans_date,trans_date_pos from orders           
                    where trans_date_pos >= '$start_date $begin_time' 
                      and trans_date_pos <= '$start_date $end_time'
                          ${option} and status=8";
                         
        $pack_seq_arr = array();

        $result = mysql_query( $query, $connect );        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $query_stock = "select * from order_products where order_seq=$data[seq] and order_cs not in (1,2)";
            $result_stock = mysql_query($query_stock, $connect);
            while( $data_stock = mysql_fetch_assoc($result_stock) )
            {
                $obj->set_stock( array( type       => 'trans',
                                        product_id => $data_stock[product_id], 
                                        bad        => 0,
                                        location   => 'Def',
                                        qty        => -1 * $data_stock[qty],
                                        memo       => '배송일괄취소',
                                        order_seq  => $data_stock[order_seq] ) );
            }

            if ( $data[pack] == 0 || $data[seq] == $data[pack] )
                class_E900::csinsert9($data["seq"],$data["pack"],7,"","배송일괄취소 <배송pos일:".$data['trans_date_pos']."> ");

            // 합포기준주문이 취소인 주문 처리용
            if( $data[pack] > 0 )
            {
                $pack_seq_arr[$data[pack]][] = array(
                    "seq"            => $data[seq]
                   ,"pack"           => $data[pack]
                   ,"status"         => $data[status]
                   ,"trans_no"       => $data[trans_no]
                   ,"trans_corp"     => $data[trans_corp]
                   ,"trans_date"     => $data[trans_date]
                   ,"trans_date_pos" => $data[trans_date_pos]
                );
            }
        }      
        
        // 합포기준주문이 취소인 주문 처리
        foreach( $pack_seq_arr as $pack_key => $pack_val )
        {
            // 합포 기준 주문이 포함된 경우 제외
            foreach( $pack_val as $order_val )
            {
                if( $pack_key == $order_val[seq] )
                    continue 2;
            }
            
            $data = $pack_val[0];
            class_E900::csinsert9($data["seq"],$data["pack"],7,"","배송일괄취소 <배송pos일:".$data['trans_date_pos']."> ");
        }

        $query = "update orders 
                     set trans_date_pos = '',
                         status     = 7
                   where trans_date_pos >= '$start_date $begin_time' 
                     and trans_date_pos <= '$start_date $end_time'
                         ${option} and status=8";
        mysql_query ( $query, $connect ) or die( mysql_error() );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->hide_wait();
        $this->query();
    }

    // upload
    function upload()
    {
        global $top_url, $connect, $_file;

        $this->show_wait();

        // 초기화
        $query = "delete from upload_temp where type='del_transno'";
        mysql_query ( $query, $connect );

        $obj = new class_file();
        $arr_result = $obj->upload();

        $rows = 0;
        foreach ( $arr_result as $row )
        {
            $t_no = preg_replace('/[^0-9]/','',$row[0]);
            if( !is_numeric($t_no) )  continue;
 
            $infos[shop_id]    = "transno";
            $infos[type]       = "del_transno";
            $infos[row]        = $rows;
            $infos[col]        = 1;
            $infos[value]      = $t_no;
            $this->insert_info( $infos );
            $rows++;
        }

        $this->hide_wait();
        $this->jsAlert ( $rows . " 개의 송장번호가 업로드 되었습니다." );
        $this->redirect ("?template=DB00&activate=2" );
        exit;
    }

    // upload
    function upload2()
    {
        global $top_url, $connect, $_file;

        $this->show_wait();

        // 초기화
        $query = "delete from upload_temp where type='cancel_trans'";
        mysql_query ( $query, $connect );

        $obj = new class_file();
        $arr_result = $obj->upload();

        $rows = 0;
        foreach ( $arr_result as $row )
        {
            $t_no = preg_replace('/[^0-9]/','',$row[0]);
            if( !is_numeric($t_no) )  continue;
 
            $infos[shop_id]    = "transno";
            $infos[type]       = "cancel_trans";
            $infos[row]        = $rows;
            $infos[col]        = 1;
            $infos[value]      = $t_no;
            $this->insert_info( $infos );
            $rows++;
        }

        $this->hide_wait();
        $this->jsAlert ( $rows . " 개의 송장번호가 업로드 되었습니다." );
        $this->redirect ("?template=DB10&activate=2" );
        exit;
    }

    ///////////////////////////////////////////
    // upload_temp에 값을 입력
    function insert_info( $infos )
    {
        global $connect;
        $query = "insert into upload_temp 
                     set shop_id='$infos[shop_id]',
                         type   ='$infos[type]',
                         row    ='$infos[row]',
                         col    ='$infos[col]',
                         value  ='$infos[value]'";
debug("송장업로드:".$query);
        mysql_query ( $query, $connect );
    }

    function del_uploaded()
    {
        global $connect, $include_trans;

        $this->show_wait();

        // Lock Start
        $obj_lock = new class_lock(305);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->hide_wait();
            $this->redirect ("?template=DB00" );
            exit;
        }

        $query = "select * From upload_temp where type='del_transno' order by row";
        $result = mysql_query ( $query, $connect );
        $i=0;
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $data[value] )
            {
                $trans_nos .= $i ? "," : "";
                $trans_nos .= $data[value];
                $i++;
            }
        }
        
        // 배송주문포함
        if( $include_trans == "on" )
            $status_con = "7,8";
        else
            $status_con = "7";

        $obj = new class_stock();

        // cs 입력
        $cnt = 0;
        $pack_seq_arr = array();

        $query = "select seq,pack,status,trans_no,trans_corp,trans_date,trans_date_pos from orders where trans_no in ( $trans_nos ) and status in ($status_con)";
        $result = mysql_query( $query, $connect );        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // 배송주문포함 - 재고 살리기
            if( $include_trans == "on" && $data[status]==8 )
            {
                $query_stock = "select * from order_products where order_seq=$data[seq] and order_cs not in (1,2)";
                $result_stock = mysql_query($query_stock, $connect);
                while( $data_stock = mysql_fetch_assoc($result_stock) )
                {
                    $obj->set_stock( array( type       => 'trans',
                                            product_id => $data_stock[product_id], 
                                            bad        => 0,
                                            location   => 'Def',
                                            qty        => -1 * $data_stock[qty],
                                            memo       => '송장일괄삭제 - 배송취소',
                                            order_seq  => $data_stock[order_seq] ) );
                }
            }

            // 송장삭제
            if ( $data[pack] == 0 || $data[seq] == $data[pack] )
            {
                // 배송주문포함 - 배송취소
                if( $include_trans == "on" && $data[status]==8 )
                    class_E900::csinsert9($data["seq"],$data["pack"],7,"","송장일괄삭제 <배송pos일:".$data['trans_date_pos']."> ");

                class_E900::csinsert9($data["seq"],$data["pack"],5,"","송장일괄삭제 <택배사:".$data['trans_corp']."><송장번호:".$data['trans_no']."><송장입력일:".$data['trans_date'].">");
                $cnt++;
            }

            // 합포기준주문이 취소인 주문 처리용
            if( $data[pack] > 0 )
            {
                $pack_seq_arr[$data[pack]][] = array(
                    "seq"            => $data[seq]
                   ,"pack"           => $data[pack]
                   ,"status"         => $data[status]
                   ,"trans_no"       => $data[trans_no]
                   ,"trans_corp"     => $data[trans_corp]
                   ,"trans_date"     => $data[trans_date]
                   ,"trans_date_pos" => $data[trans_date_pos]
                );
            }
        }
        
        // 합포기준주문이 취소인 주문 처리
        foreach( $pack_seq_arr as $pack_key => $pack_val )
        {
            // 합포 기준 주문이 포함된 경우 제외
            foreach( $pack_val as $order_val )
            {
                if( $pack_key == $order_val[seq] )
                    continue 2;
            }
            
            $data = $pack_val[0];

            // 배송주문포함 - 배송취소
            if( $include_trans == "on" && $data[status]==8 )
                class_E900::csinsert9($data["seq"],$data["pack"],7,"","송장일괄삭제 <배송pos일:".$data['trans_date_pos']."> ");

            class_E900::csinsert9($data["seq"],$data["pack"],5,"","송장일괄삭제 <택배사:".$data['trans_corp']."><송장번호:".$data['trans_no']."><송장입력일:".$data['trans_date'].">");
            $cnt++;
        }

        // 송장삭제
        $query = "update orders set status=1, trans_corp='', trans_no='', trans_date='', trans_key=0, auto_trans = 0 where trans_no in ($trans_nos) and status in ($status_con)";
        mysql_query( $query, $connect );

        // 삭제한 송장 정보 삭제        
        $query = "delete From upload_temp where type='del_transno'";
        mysql_query ( $query, $connect );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->jsAlert($cnt . " 개의 송장이 삭제 되었습니다.");
        $this->hide_wait();
        $this->redirect ("?template=DB00" );
    }

    function del_uploaded2()
    {
        global $connect;

        $this->show_wait();

        // Lock Start
        $obj_lock = new class_lock(307);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->hide_wait();
            $this->redirect ("?template=DB10" );
            exit;
        }

        $query = "select * From upload_temp where type='cancel_trans' order by row";
        $result = mysql_query ( $query, $connect );
        $i=0;
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $data[value] > '' )
            {
                $trans_nos .= $i ? "," : "";
                $trans_nos .= $data[value];
                $i++;
            }
        }

        $obj = new class_stock();

        // cs 입력
        $cnt = 0;
        $pack_seq_arr = array();

        $query = "select seq,pack,trans_no,trans_date_pos from orders where trans_no in ( $trans_nos ) and status=8";
        $result = mysql_query( $query, $connect );        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[pack] == 0 || $data[seq] == $data[pack] )
            {
                class_E900::csinsert9($data["seq"],$data["pack"],7,"","일괄배송취소 <배송pos일:$data[trans_date_pos]>");
                $cnt++;
            }

            // 재고 다시 살리기
            $query_stock = "select seq from orders where seq=$data[seq] and status=8";
            $result_stock = mysql_query($query_stock, $connect);
            while( $data_stock = mysql_fetch_assoc($result_stock) )
            {
                $query_prd = "select product_id, qty, order_seq from order_products where order_seq=$data_stock[seq] and order_cs not in (1,2) and no_stock=0";
                $result_prd = mysql_query($query_prd, $connect);
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    $obj->set_stock( array( type       => 'trans',
                                            product_id => $data_prd[product_id], 
                                            bad        => 0,
                                            location   => 'Def',
                                            qty        => -1 * $data_prd[qty],
                                            memo       => '일괄배송취소',
                                            order_seq  => $data_prd[order_seq] ) );
                }
            }

            // 합포기준주문이 취소인 주문 처리용
            if( $data[pack] > 0 )
            {
                $pack_seq_arr[$data[pack]][] = array(
                    "seq"            => $data[seq]
                   ,"pack"           => $data[pack]
                   ,"status"         => $data[status]
                   ,"trans_no"       => $data[trans_no]
                   ,"trans_corp"     => $data[trans_corp]
                   ,"trans_date"     => $data[trans_date]
                   ,"trans_date_pos" => $data[trans_date_pos]
                );
            }
        }
        
        // 합포기준주문이 취소인 주문 처리
        foreach( $pack_seq_arr as $pack_key => $pack_val )
        {
            // 합포 기준 주문이 포함된 경우 제외
            foreach( $pack_val as $order_val )
            {
                if( $pack_key == $order_val[seq] )
                    continue 2;
            }
            
            $data = $pack_val[0];
            class_E900::csinsert9($data["seq"],$data["pack"],7,"","일괄배송취소 <배송pos일:$data[trans_date_pos]>");
            $cnt++;
        }

        // 배송취소
        $query = "update orders set status=7, trans_date_pos='' where trans_no in ($trans_nos) and status=8";
        mysql_query( $query, $connect );

        // 삭제한 송장 정보 삭제        
        $query = "delete From upload_temp where type='cancel_trans'";
        mysql_query ( $query, $connect );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->jsAlert($cnt . " 개의 주문이 배송취소 되었습니다.");
        $this->hide_wait();
        $this->redirect ("?template=DB10" );
    }
}

?>
