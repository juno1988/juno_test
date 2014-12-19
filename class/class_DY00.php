<?
//===================================
// eshealthvill전용
// build     : 2012-07-01 (jkryu)
// lastupdate: 2012-07-20 (jkryu)
// 2012-07-20
//  orders에 trans_type, trans_change 추가
//  검색시 접수상태의 주문의 trans_type을 재 계산한다.
//  trans_change가 1인 주문은 trans_type을 재 계산하지 않는다.
//  상품의 택배 / 직택배가 변경된 경우..
//
require_once "class_top.php";
require_once "class_file.php";
require_once "/home/ezadmin/public_html/shopadmin/maps/geolib.php";
	
class class_DY00 extends class_top
{
    function DY00()
    {
    	global $template, $connect;
        
        $start_date = date('Y-m-d', strtotime('-180 day'));
        
    	$top_url = base64_encode( $this->build_link_url() );
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function DY01()
    {
        
    	global $template, $connect, $txtName, $txtAddress, $view_map, $txtSeqs, $connect;
        
        if ( $view_map == 1 )
        {
            //$address_list = array("서울시 중구 신당2동370-24", "서울시중구신당1동110", "서울시중구을지로2가외환은행본점", "서울중구을지로동2");
            //$address_list = array( $txtAddress );

            $name_list    = split(",", $txtName);
            $address_list = split(",", $txtAddress);

            $arrs         = array();
            
            //-- 주소갯수만큼 LOOP
			$i = 0;
            foreach ($address_list as $address) {

                //-- 위도 경도 가져오기
                //-- get loc/lng with google geoapi
                $latlng = geocode_google(urlencode($address));
				$name = $name_list[$i];
                
                $item = array("name" => $name, "address" => $address, "loc" => $latlng[0], "lng"=> $latlng[1]);
                array_push($arrs, $item);
				$i++;
            }
            
            $json_str = json_encode($arrs);
        }
        
        //echo "seqs: $txtSeqs ";
        
        include "template/D/DY01.htm";
    }
    
    //
    // 완료 처리
    function deliv_complete_action()
    {
        global $seqs, $connect,$trans_date,$extra_text;   
        $trans_no = $trans_date. $extra_text;
        
        $query = "update orders set status=8, trans_date_pos='$trans_date', trans_no=concat(trans_no,'/$extra_text') where seq in ( $seqs ) and status=7";
        mysql_query( $query, $connect );
        
        $query = "update orders set status=8, trans_date_pos='$trans_date', trans_no=concat(trans_no,'/$extra_text') where pack in ( $seqs ) and status=7";
        mysql_query( $query, $connect );
        
    }
    
    //
    // 배송예정
    function deliv_ready_action()
    {
        global $seqs, $connect,$trans_date,$extra_text;   
        $trans_no = $trans_date.$extra_text;
        
        //$query = "update orders set status=7, trans_no='$trans_no', trans_date='$trans_date' where seq in ( $seqs ) and status=1";
        $query = "update orders set status=7, trans_corp='30067', trans_no='$trans_no', trans_date='$trans_date' where seq in ( $seqs )";
        mysql_query( $query, $connect );
        
        //$query = "update orders set status=7, trans_no='$trans_no', trans_date='$trans_date' where pack in ( $seqs ) and status=1";
        $query = "update orders set status=7, trans_corp='30067',trans_no='$trans_no', trans_date='$trans_date' where pack in ( $seqs )";
        mysql_query( $query, $connect );
        
    }

    function DY01_get_list()
    {
        global $str_seqs, $connect;
        
        $query = "select seq,pack from orders where seq in ( $str_seqs )
                   order by field( seq, $str_seqs ) ";
        
        $result = mysql_query( $query, $connect );
        $str_seqs = ""; 
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $str_seqs .= $str_seqs ? "," : "";
            if ( $data[pack] )
                $str_seqs .= $this->get_pack_seq( $data[seq] );  
            else
                $str_seqs .= $data[seq];   
        }
        
        return $str_seqs;
    }
    
    function popup_get_order_list()
    {
        $str_seqs = $this->DY01_get_list();
        
        $arr_result = $this->get_order_list( $str_seqs, "no sort" );
        echo json_encode( $arr_result );   
    }
    
    function get_pack_seq ( $seq )
    {
        global $connect;
        $query = "select seq from orders where pack=$seq";
        $result = mysql_query( $query, $connect );
        $_seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $_seqs .= $_seqs ? "," : "";
            $_seqs .= $data[seq];
        }
        return $_seqs;
    }
    
    function search_core()
    {
        global $date_type, $start_date, $end_date, $status, $order_cs, $disp_group, $area1, $area2, $area3, $group1, $group2, $trans_no;
        
        // 조건식으로 area별 zip code를 구해낸다.
        if ( $disp_group == "true" )
        {
            $str_zip = $this->get_group_area( $group1, $group2 );   
        }
        else
        {
            $str_zip = $this->get_area( $area1, $area2, $area3 );   
        }
        
        // 핵심 로직
        // 조건식으로 seq, pack을 구해 낸다.
        $str_seqs   = $this->get_trans_type_seq( $str_zip );
        
        // 조회할 데이터가 없는경우 종료.
        if ( !$str_seqs )
        {
            $arr_result["error"] = 1;
            $arr_result["msg"]   = "조회 값이 없습니다";
            
            return $arr_result; 
        }
        
        // 구해진 seq로 주문 조회
        // $arr_result["order"]: 주문 리스트
        // $arr_result["as"]   : AS 리스트
        $arr_result = $this->get_order_list( $str_seqs );
        
        return $arr_result;   
    }
    
    //
    // table
    //      eunsung_zipcode
    //
    //      orders
    //      order_products
    //      products.trans_type = direct: 직배, trans: 택배
    //
    function search()
    {
        $arr_result = $this->search_core();
        
        echo json_encode( $arr_result );
    }
    
    // DY01에서 다운로드
    function download2()
    {
        $str_seqs = $this->DY01_get_list();
                
        $arr_result = $this->get_order_list( $str_seqs, "no sort" );

        $this->download_action( $arr_result );   
    }
    
    //
    // download
    //
    function download()
    {
        $arr_result = $this->search_core();
        
        // print_r ( $arr_result );
        
        $this->download_action( $arr_result );
    }
    
    function download_action( $arr_result )
    {
        //print_r( $arr_result );
        // download 자료를 만들어야 함.
        $arrDn   = array();
        
        $arrDn[] = array(
            "발주일"
            ,"주문자/수령자"
            ,"상품명"
            ,"주소"
            ,"CS내용"
            ,"전화"
            ,"핸드폰"
            ,"발주처"
            ,"비고"
        );
        
        $row_cnt = 0;
        for( $i = 0; $i < count( $arr_result["order"] ); $i++)
        {    
            if ( $arr_result["order"][ $i ]["is_first_row"] == 1 )
            {
                if ( $i > 0 )
                {
                    $row_cnt++;
                    $arrDn[] = $row; 
                }
                    
                $products = trim($arr_result["order"][ $i ]["name"]) . trim($arr_result["order"][ $i ]["options"]). "-" . trim($arr_result["order"][ $i ]["qty"]) ."개<br>";
                $row = array(
                    $arr_result["order"][ $i ]["collect_date"]
                    ,$arr_result["order"][ $i ]["order_name"] . "/" . $arr_result["order"][ $i ]["recv_name"]
                    ,$products
                    ,"(" . $arr_result["order"][ $i ]["recv_zip"] . ")" . $arr_result["order"][ $i ]["recv_address"]
                    ,$arr_result["order"][ $i ]["csmemo"]
                    ,$arr_result["order"][ $i ]["recv_tel"]
                    ,$arr_result["order"][ $i ]["recv_mobile"]
                    ,$arr_result["order"][ $i ]["shop_name"]
                    ,""
                );
            }
            else
            {   
                $products .= trim($arr_result["order"][ $i ]["name"]) . trim($arr_result["order"][ $i ]["options"]). "-" . trim($arr_result["order"][ $i ]["qty"]) . "개<br>";
                $row[2] = $products;
            }
        }
        
        if ( count( $row ) > 1 )
            $arrDn[] = $row;         
        
        // 초기화..
        $row = NULL;    
        // as 정보
        for( $i = 0; $i < count( $arr_result["as"] ); $i++)
        {
            if ( $arr_result["as"][ $i ]["is_first_row"] == 1 )
            {
                if ( $i > 0 )
                    $arrDn[] = $row; 
                    
                $products = trim($arr_result["as"][ $i ]["name"]) . trim($arr_result["as"][ $i ]["options"]). "-" . trim($arr_result["as"][ $i ]["qty"]) ."개<br>";
                
                $row = array(
                    $arr_result["as"][ $i ]["collect_date"]
                    ,$arr_result["as"][ $i ]["order_name"] . "/" . $arr_result["as"][ $i ]["recv_name"]
                    ,$products
                    ,"(" . $arr_result["as"][ $i ]["recv_zip"] . ")" . $arr_result["as"][ $i ]["recv_address"]
                    ,$arr_result["as"][ $i ]["csmemo"]
                    ,$arr_result["as"][ $i ]["recv_tel"]
                    ,$arr_result["as"][ $i ]["recv_mobile"]
                    ,$arr_result["as"][ $i ]["shop_name"]
                    ,""
                );
            }
            else
            {   
                $products .= trim($arr_result["as"][ $i ]["name"]) . trim($arr_result["as"][ $i ]["options"]). "-" . trim($arr_result["as"][ $i ]["qty"]) . "개<br>";
                
                //$row[2] = iconv("utf-8", "cp949", $products);
                $row[2] = $products;
            }
        }
        
        if ( count( $row ) > 1 )
            $arrDn[] = $row;
        
        $obj_file = new class_file();
        $obj_file->download( $arrDn, "total_order_" . Date("Ymd") . ".xls",1);   
    }
    
    //
    // 구해진 seq와 pack으로 정보를 가져온다.
    //
    // $arr_result["order"]: 주문 리스트
    // $arr_result["as"]   : AS 리스트
    function get_order_list( $str_seqs, $no_sort = "" )
    {
        global $connect;    
        $arr_result = array();
        $arr_result["order"] = array();
        $arr_result["as"]    = array();
            
        // 조회할 값이 없을 경우 
        if ( !$str_seqs )
        {
            return;   
        }
        
        $query = "select orders.seq,orders.pack, orders.order_id, products.trans_type 
                        ,if(orders.pack<>null||orders.pack<>0,orders.pack, orders.seq ) xx
                        ,products.name, products.options, order_products.qty
                        ,orders.recv_zip, orders.recv_mobile, orders.recv_tel
                        ,orders.recv_address, orders.shop_id, orders.collect_date
                        ,orders.order_name
                        ,orders.recv_name
                        ,orders.status
                        ,orders.trans_no
                        ,orders.trans_date
                        ,orders.trans_date_pos
                    from orders, order_products, products
                   where orders.seq                = order_products.order_seq
                     and order_products.product_id = products.product_id
                     and orders.hold               = 0
                     and orders.seq in ( $str_seqs ) ";

        if ( $no_sort != "no sort")                     
            $query .= " order by xx";
        else
            $query .= " order by field( orders.seq, $str_seqs ) ";
        
        // debug( "get_order_list: " . $query );
        
        $result = mysql_query( $query, $connect );                     
        
        //
        // data 구조
        //
        // seq, is_first_row, product_cnt, pack, xx, recv_name, recv_address, recv_tel, recv_mobile, product_list [ pid, name, option, qty ]
        //
        $no = 1;
        // xx array의 count를 계산한다.
        $h_row_count = array();
        $old_xx = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {   
            $data[options] = $data[options] ? $data[options] : "&nbsp;";
            
            $h_row_count[ $data[xx] ]++;
            $data['no'] = $no++;
            if ( $old_xx != $data[xx] )
            {
                $data['is_first_row'] = 1;
                $old_xx = $data[xx];   
            }
            else
            {
                $data['is_first_row'] = 0;
            }
            
            if ( $data["trans_type"] == "1" )
                $data["trans_type"] = "<span class=red>직배</span>";
            else
                $data["trans_type"] = "택배";
            
            if ( !$data["trans_no"] )
            {
                $data["trans_no"] = "&nbsp;";   
            }
            
            if ( $data["trans_date"] == "0000-00-00 00:00:00" || !$data["trans_date"])
                $data["trans_date"] = "&nbsp;";
            
            if ( $data["trans_date_pos"] == "0000-00-00 00:00:00" || !$data["trans_date_pos"])
                $data["trans_date_pos"] = "&nbsp;";
            
            if ( $data["status"] == 7 )
                $data["status"] = "예정";
            else if ( $data["status"] == 8 )
                $data["status"] = "완료";
            else if ( $data["status"] == 1 )
                $data["status"] = "미처리";
            
            // AS건인지 여부 확인
            if ( substr( $data["order_id"],0,1 ) == "C" )
            {
                $data["order_id"] = $data["order_id"] . "(" . $data["seq"] . ")";
                $arr_result["as"][] = $data;
            }
            else
            {
                $data["order_id"] = $data["order_id"] . "(" . $data["seq"] . ")";
                $arr_result["order"][] = $data;
            }
        }
        
        //
        //
        $arr_shop_name = $this->get_arr_shop_name();
        
        // order와 as에 대해서 두 번 작업한다.
        $iNo = 0;
        for( $i = 0; $i < count( $arr_result["order"] ); $i++)
        {
            $arr_result["order"][ $i ]["csmemo"]      = $this->get_cs_history( $arr_result["order"][ $i ]["seq"], $arr_result["order"][ $i ]["pack"] );
            $arr_result["order"][ $i ]["product_cnt"] = $h_row_count[ $arr_result["order"][ $i ]["xx"] ]; 
            $arr_result["order"][ $i ]["shop_name"]   = $arr_shop_name[ $arr_result["order"][ $i ]["shop_id"] ]; 
        }
        
        for( $i = 0; $i < count( $arr_result["as"] ); $i++)
        {
            $arr_result["as"][ $i ]["csmemo"]      = $this->get_cs_history( $arr_result["as"][ $i ]["seq"], $arr_result["as"][ $i ]["pack"] );
            $arr_result["as"][ $i ]["product_cnt"] = $h_row_count[ $arr_result["as"][ $i ]["xx"] ]; 
            $arr_result["as"][ $i ]["shop_name"]   = $arr_shop_name[ $arr_result["as"][ $i ]["shop_id"] ]; 
            
        }
        
        return $arr_result;
    }
    
    // csinfo
    function get_cs_history( $seq, $pack = "" )
    {
        global $connect;
        
        $seq = $seq;
        if ( $pack )
        {
            $query = "select seq from orders where pack=$pack";
            //debug( "get_cs_history $query ");
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $seq .= $seq ? "," : "";
                $seq .= $data[seq];   
            }
        }
        
        $query = "select input_date, input_time, writer, content from csinfo where order_seq in ( $seq )";
        debug( "get_cs_history $query ");
            
        $result = mysql_query( $query, $connect );
        $memo = "";
        while ( $data = mysql_Fetch_assoc( $result ) )
        {
            if ( $data[content] != "" )
                $memo .= "$data[input_date] $data[input_time] $data[writer] $data[content] //<br>";
        }
        
        debug( $memo );
        
        return $memo;
    }
    
    function get_arr_shop_name()
    {
        global $connect;
        
        $query = "select shop_id, shop_name from shopinfo";   
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[ $data["shop_id"] ] = $data["shop_name"];   
        }
        
        return $arr_result;
    }
    
    //
    // 직택배 여부
    // 직택배 상품의 seq, pack을 가져온다.
    // 
    // 직배 우선
    // 직배로 조회 할 경우 택배가 조회되지 않는다.
    function get_trans_type_seq( $str_zip )
    {
        debug("get_trans_type_seq");
        
        global $connect, $date_type, $start_date, $end_date, $status, $order_cs,$trans_type,$trans_no;
        
        if( $date_type != "collect_date" )
        {
            $start_date .= " 00:00:00";
            $end_date   .= " 23:59:59";
        }
        
        $query = "select orders.seq, orders.pack from orders, order_products, products 
                   where orders.seq                 = order_products.order_seq
                     and order_products.product_id  = products.product_id
                     and orders.$date_type         >= '$start_date'
                     and orders.$date_type         <= '$end_date'
                     ";
  
        if ( $trans_no )
            $query .= " and orders.trans_no like ('%$trans_no%') ";
        
        if ( $str_zip )
            $query .= " and replace(orders.recv_zip,'-','') in ( $str_zip ) ";
        
        if ( $order_cs != "all" )
        {
            if ( $order_cs == "normal" )
            {
                $query .= " and orders.order_cs not in ( 1,2,3,4,12 ) ";
            }
            else
            {
                $query .= " and orders.order_cs in ( 1,2,3,4,12 ) ";
            }
        }
                 
        if ( $status != "all" )
        {
            $query .= " and orders.status in ( $status ) ";  
        }
        
        $query .= " limit 2000";
        debug( $query );
        
        $result = mysql_query( $query, $connect );
        $arr_seq = array();
        $str_pack = "";
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            if ( $data[pack] != 0 )
            {
                $str_pack .= $str_pack ? "," : "";
                $str_pack .= $data[pack];
            }
            else
            {
                $arr_seq[] = $data[seq];    
            }
        }
        
        //
        // pack된 seq를 구한다.
        $query = "select seq from orders where pack in ( $str_pack )";
        $result = mysql_query( $query, $connect );
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_seq[] = $data[seq];   
        }
        
        $str_seqs = "";
        foreach( $arr_seq as $_seq )
        {
            $str_seqs .= $str_seqs ? "," : "";
            $str_seqs .= $_seq;
        }
        
        //
        // 고객이 택배로 조회를 할 경우 조회가 되면 안됨
        // products.trans_type == trans
        //
        debug("str_seqs: $str_seqs");
        debug("trans_type: $trans_type");
        
        if ( $trans_type != "all" )
        {
            // 직배: 1 택배: 0
            if ( $trans_type == "trans" )
            {
                //$str_seqs = $this->remove_trans_type_seq( $str_seqs, "direct"  );
                $str_seqs = $this->remove_trans_type_seq( $str_seqs, "1"  );
            }
            else
            {
                //$str_seqs = $this->remove_trans_type_seq( $str_seqs, "trans" );
                $str_seqs = $this->remove_trans_type_seq( $str_seqs, "0" );
            }
        }
        
        debug("str_seqs: $str_seqs");
        
        return $str_seqs;
    }
    
    //
    // 직배가 포함될 경우 제거, 직배가 제거된 seq를 return한다.
    // trans_type: 0: 택배 1: 직배
    function remove_trans_type_seq( $str_seq , $remove_trans_type )
    {
        global $connect;
        
        // order_products와 products join 한다
        $query = "select orders.pack,orders.seq, products.trans_type
                        ,if(orders.pack<>null||orders.pack<>0,orders.pack, orders.seq ) xx
                    from orders, order_products, products
                   where orders.seq                = order_products.order_seq
                     and order_products.product_id = products.product_id
                     and orders.seq                in ( $str_seq )
                     order by xx";
        
        debug( $query );
             
        $result = mysql_query( $query, $connect );
        
        //
        // trans_type == direct 인 seq,pack을 
        $str_seq     = "";
        $str_temp    = "";      // 합포 주문인 경우에 추가 전에 temp하게 저장
        $is_pack_direct  = 0;   // (합포 주문이 ) 직배 자료 인지 여부..
        $before_pack = 0;       // 이전 자료의 합포 번호
        
        //debug( "remove trans_type: $remove_trans_type");
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            //if ( $data[pack] )
            if ( 1 )
            {
                // 최초 실행
                if ( $before_pack == 0 )
                    $before_pack = $data[pack];
            
                debug( "$data[seq] / $data[xx]==$before_pack/$data[trans_type]/r: $remove_trans_type/rt:$is_remove_type/id: $is_pack_direct  ");
                // before자료 처리  
                // 배송으로 찾는경우 직배를 모두 삭제
                // 직배로 찾는경우                   
                //if ( $before_pack != $data[pack] )
                if ( $before_pack != $data[xx] )
                {
                    // $data[trans_type] == $remove_trans_type => is_remove_type = 1
                    // 찾아 졌음
                    if ( $is_remove_type == 0 )
                    {
                        $str_seq    .= $str_seq ? "," : "";
                        $str_seq    .= $str_temp;
                    }
                    else
                    {
                        // direct를 다 가져와야 하는경우.
                        //if ( $remove_trans_type == "trans" &&  $is_pack_direct == 1 )
                        if ( $remove_trans_type == "0" &&  $is_pack_direct == 1 )
                        {   
                            $str_seq    .= $str_seq ? "," : "";
                            $str_seq    .= $str_temp; 
                        }
                    }
                    
                    $str_temp       = "";         // 초기화.
                    $is_remove_type = 0;
                    $is_pack_direct = 0;
                    //$before_pack    = $data[pack];
                    $before_pack    = $data[xx];
                } 
                              
                //
                // current 자료 처리   
                //         
                //if ( $before_pack == $data[pack] )
                if ( $before_pack == $data[xx] )
                {
                    $str_temp .= $str_temp ? "," : "";
                    $str_temp .= $data[seq];
                }
                
                // 
                if ( $data[trans_type] == $remove_trans_type )
                    $is_remove_type = 1;
                    
                // 합포 중 하나라도 direct가 있으면 direct배송.
                //if ( $data[trans_type] == "direct" )
                if ( $data[trans_type] == "1" )
                    $is_pack_direct = 1;
            }
            else
            {
                //debug( "data_seq: $data[seq] / $data[trans_type] / $trans_type ");
                if( $data[trans_type] != $remove_trans_type )
                {
                    
                    $str_seq .= $str_seq ? "," : "";
                    $str_seq .= $data[seq];
                }   
                else
                {
                    //debug( "direct remove single: $data[seq]");   
                }
            }
        }
        
        // 최종..
        if ( $str_temp  )
        {
            if ( $is_remove_type == 0 )
            {
                $str_seq .= $str_seq ? "," : "";
                $str_seq .= $str_temp;
            }
            else
            {
                
                //if ( $remove_trans_type == "trans" &&  $is_pack_direct == 1 )
                if ( $remove_trans_type == "0" &&  $is_pack_direct == 1 )
                {
                    $str_seq .= $str_seq ? "," : "";
                    $str_seq .= $str_temp;
                }
            }   
        }
        
        //debug( "remove $str_seq ");
        return $str_seq;
    }
    
    //
    //
    //
    function get_group_area( $group1, $group2 )
    {
        global $connect;
        
        if( $group1 == "all" && $group2 == "all" )
            return "";
            
        // all 이 아닐경우..
        $query = "select zipcode from eunsung_zipcode";
        
        if ( $group1 != "all" )
            $query .= " where group1 = '$group1'";
        
        if ( $group2 != "all" )
            $query .= " and group2 = '$group2'";
        
        //debug( $query );
        
        $result = mysql_query( $query, $connect );
        $str_zip = "";
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $str_zip .= $str_zip ? "," : "";
            $str_zip .= "'$data[zipcode]'";   
        }
        
        return $str_zip;
    }
    
    function get_area( $area1, $area2, $area3 )
    {
        global $connect;
           
    }

    function get_area1()
    {
        global $connect;
        $query = "select * from eunsung_zipcode group by area1 order by area1";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['area1'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    // parent
    function get_area2()
    {
        global $connect,$parent;
        $query = "select * from eunsung_zipcode where area1='$parent' group by area2 order by area2";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['area2'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
     // parent
    function get_area3()
    {
        global $connect,$area1,$area2;
        $query = "select * from eunsung_zipcode where area1='$area1' and area2 ='$area2' group by area3 order by area3";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['area3'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    function get_group_area1()
    {
        global $connect;
        $query = "select * from eunsung_zipcode group by group1 order by group1";
        //debug( $query );
        
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['group1'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }

    function get_group_area2()
    {
        global $connect, $parent;
        $query = "select * from eunsung_zipcode where group1='$parent' group by group2 order by group2";
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['group2'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    function get_group_text()
    {
        global $connect, $area1, $area2;
        $query = "select * from eunsung_zipcode where group1='$area1' and group2 = '$area2' group by area2 order by area2";
        //debug( $query );
        
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data['value'] = $data['area2'];
            $arr_result[] = $data;
        }
        
        echo json_encode( $arr_result );
    }
    
    
    // old logic
    
}
?>
