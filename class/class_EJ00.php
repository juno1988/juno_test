<?
require_once "class_top.php";
require_once "class_ui.php";
require_once "class_order.php";
require_once "class_product.php";
require_once "class_takeback.php";
require_once "class_file.php";
require_once "class_transcorp.php";

class class_EJ00 extends class_top
{
    ///////////////////////
    function EJ00()
    {
    	global $connect;
    	global $template, $start_date, $end_date;
    
    	$master_code = substr($template, 0, 1);
        include "template/E/EJ00.htm";
    }


    // 회수주문 검색을 위한 상품코드 리스트를 만든다.
    function get_pid_list($p_name, $options)
    {
        global $connect;
        
        if( !$p_name && !$options ) return "";
        if( $p_name  ) $cond .= " name like '%$p_name%'";
        if( $options ) $cond .= $cond?" and ":"" . " options like '%$options%'";
        $query = "select product_id from products where " . $cond;
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) == 0 ) return "('0')";    // 결과가 없으면 ('0')을 리턴하여 회수주문검색 안되도록한다.
        
        $i=0;
        $str = "";
        while( $data = mysql_fetch_array( $result ) )
        {
            if( $i==0 ) $str  = "('" . $data[product_id] . "'";
            else        $str .= ",'" . $data[product_id] . "'";
            $i++;
        }
        $str .= ")";
        return $str;
    }
    
    // 회수주문 검색을 위한 주문번호 리스트를 만든다.
    function get_order_list($recv_name,$recv_hp,$org_transno)
    {
        global $connect;
        
        if( !$recv_name && !$recv_hp && !$org_transno ) return "";
        if( $recv_name   ) $cond .= " recv_name = '$recv_name'";
        if( $recv_hp     ) $cond .= $cond?" and ":"" . " recv_mobile = '$recv_hp'";
        if( $org_transno ) $cond .= $cond?" and ":"" . " trans_no = '$org_transno'";
        $query = "select seq from orders where " . $cond;
        $result = mysql_query( $query, $connect );
        if( mysql_num_rows( $result ) == 0 ) return "('0')";    // 결과가 없으면 ('0')을 리턴하여 회수주문검색 안되도록한다.
        
        $i=0;
        $str = "";
        while( $data = mysql_fetch_array( $result ) )
        {
            if( $i==0 ) $str  = "('" . $data[seq] . "'";
            else        $str .= ",'" . $data[seq] . "'";
            $i++;
        }
        $str .= ")";
        return $str;
    }
    
    // 회수주문 검색
    function search_takeback()
    {
        global $connect;
        global $p_name,$option,$status,$return_req,$return_get,$recv_name,$recv_hp,$org_transno,$tb_transno,$trans_who,
               $trans_get,$rec_start,$rec_end,$req_start,$req_end,$reg_start,$reg_end,$pos_start,$pos_end,$comp_start,$comp_end,
               $qty_err,$return_err,$fee_err,$return_ng,$p_status;

        $p_name    = iconv('utf-8','cp949',$p_name   );
        $option    = iconv('utf-8','cp949',$option   );
        $recv_name = iconv('utf-8','cp949',$recv_name);
        $trans_who = iconv('utf-8','cp949',$trans_who);
        $trans_get = iconv('utf-8','cp949',$trans_get);
        
        $pid_in   = $this->get_pid_list($p_name,$option );
        $order_in = $this->get_order_list($recv_name,$recv_hp,$org_transno);
            
        if( $domain     )  $cond .= ($cond?" and ":"") . (" domain         = '$domain'   ");
        if( $pid_in     )  $cond .= ($cond?" and ":"") . (" product_id    in $pid_in     ");
        if( $status     )  $cond .= ($cond?" and ":"") . (" status         = $status     ");
        if( $return_req )  $cond .= ($cond?" and ":"") . (" reason_req     = $return_req ");
        if( $return_get )  $cond .= ($cond?" and ":"") . (" reason_get     = $return_get ");
        if( $order_in   )  $cond .= ($cond?" and ":"") . (" order_seq     in $order_in   ");
        if( $tb_transno )  $cond .= ($cond?" and ":"") . (" trans_no       = $tb_transno ");
        if( $trans_who  )  $cond .= ($cond?" and ":"") . (" trans_who      = '$trans_who'");
        if( $trans_get  )  $cond .= ($cond?" and ":"") . (" trans_get      = '$trans_get'");
        if( $p_status   )  $cond .= ($cond?" and ":"") . (" product_status = $p_status   ");
        if( $rec_start  && $rec_end  )  $cond .= ($cond?" and ":"") . (" receive_date  >= '$rec_start'  and receive_date  < '$rec_end'  ");
        if( $req_start  && $req_end  )  $cond .= ($cond?" and ":"") . (" request_date  >= '$req_start'  and request_date  < '$req_end'  ");
        if( $reg_start  && $reg_end  )  $cond .= ($cond?" and ":"") . (" regist_date   >= '$reg_start'  and regist_date   < '$reg_end'  ");
        if( $pos_start  && $pos_end  )  $cond .= ($cond?" and ":"") . (" pos_date      >= '$pos_start'  and pos_date      < '$pos_end'  ");
        if( $comp_start && $comp_end )  $cond .= ($cond?" and ":"") . (" complete_date >= '$comp_start' and complete_date < '$comp_end' ");

        // 불일치
        if( $qty_err    == 'true' )  $cond .= ($cond?" and ":"") . (" qty_req <> qty_get ");
        if( $return_err == 'true' )  $cond .= ($cond?" and ":"") . (" reason_req <> reason_get ");
        if( $fee_err    == 'true' )  $cond .= ($cond?" and ":"") . (" ((trans_who <> trans_get) or ((ifnull(refund_req,0) + ifnull(bank_req,0)) <> (ifnull(refund_get,0) + ifnull(bank_get,0)))) ");
        
        
        if( ($qty_err=='true') || ($return_err=='true') || ($fee_err=='true') )
            $cond .= ($cond?" and ":"") . (" status in (5,6) ");  // 불일치를 선택하면 '박스개봉', '회수완료' 상태의 주문만 검색!!
            
        // 회수 이상
        if( $return_ng  == 1 )  
            $cond .= ($cond?" and ":"") . (" status not in (6) ");
        else if( $return_ng  == 2 )  
        {
            $t_time = date('Y-m-d H:i:s', mktime() - 5*24*60*60);
            $cond .= ($cond?" and ":"") . (" receive_date is not null and receive_date < '$t_time' ");
        }
        else if( $return_ng  == 3 )  
            $cond .= ($cond?" and ":"") . (" receive_date is null ");

        $query = "select * from order_takeback";
        if( $cond ) $query .= " where " . $cond;
        $query .= " order by number desc ";
        $val['query'] = $query;
        $result = mysql_query( $query, $connect );
        $val['list'] = array();
        $i = 1;
        $old_num = 0;
        while( $data = mysql_fetch_array( $result ) )
        {
            if( $old_num == $data[number] )  $eq = true;
            else  $eq = false;
                
            $data_order = class_order::get_order( $data[order_seq] );
            $data_prd = class_product::get_info( $data_order[product_id] );

            $val['list'][] = array(
                num            => $i,
                number         => $data[number],
                number_show    => $eq?'-':$data[number],
                order_seq      => $data[order_seq],
                recv_name      => iconv('cp949','utf-8',$data_order[recv_name]),
                recv_hp        => $data_order[recv_mobile],
                recv_tel       => $data_order[recv_tel],
                recv_address   => iconv('cp949','utf-8',$data_order[recv_address]),
                product_name   => iconv('cp949','utf-8',$data_prd[name]),
                option         => iconv('cp949','utf-8',$data_prd[options]),
                qty            => $data_order[qty],
                order_date     => $data_order[order_date]>0?$data_order[order_date]:"",
                collect_date   => $data_order[collect_date]>0?$data_order[collect_date]:"",
                trans_date     => $data_order[trans_date_pos]>0?$data_order[trans_date_pos]:"",
                receive_date   => $data[receive_date],
                request_date   => $data[request_date],
                regist_date    => $data[regist_date],
                pos_date       => $data[pos_date],
                box_date       => $data[box_date],
                complete_date  => $data[complete_date],
                trans_corp     => $eq?'-':iconv('cp949','utf-8', class_transcorp::get_corp_name($data[trans_corp])),
                trans_no       => $eq?'-':$data[trans_no],
                status         => $data[status],
                tb_qty_req     => $data[qty_req],
                tb_qty_get     => $data[qty_get],
                tb_qty         => $data[qty_req] . " / " . $data[qty_get],
                return_req     => $data[reason_req],                            
                return_get     => $data[reason_get],                            
                return_all     => $data[reason_req] . "," . $data[reason_get],
                trans_who_req  => iconv('cp949','utf-8',$data[trans_who]),
                trans_who_get  => iconv('cp949','utf-8',$data[trans_get]),
                trans_who      => $eq?'-':iconv('cp949','utf-8',$data[trans_who] . " / " . $data[trans_get]),
                return_fee_req => $eq?0:$data[refund_req],                            
                return_fee_get => $eq?0:$data[refund_get],                            
                return_fee     => $eq?'-':$data[refund_req] . " / " . $data[refund_get],
                bank_fee_req   => $eq?0:$data[bank_req],
                bank_fee_get   => $eq?0:$data[bank_get],
                bank_fee       => $eq?'-':$data[bank_req] . " / " . $data[bank_get],
                cs_worker      => iconv('cp949','utf-8',$data[who_receive])
            );
            $i++;
            $old_num = $data[number];
        }
        echo json_encode( $val );
    }

    // 택배비 계좌 확인금액을 입력한다.
    function set_bank_get()
    {
        global $connect, $domain, $seq, $bank;
        
        $query = "update order_takeback set bank_get=$bank where order_seq=$seq";
        return mysql_query( $query, $connect );
    }
    
    // takeback 전체 진행상황 조회
    function get_all_result()
    {
        global $connect;
        
        // 전체 회수 건수
        $query = "select count(*) as cnt_all from order_takeback";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        $val['cnt_all'] = $data[cnt_all];
        
        // 회수 완료 건수
        $query = "select count(*) as cnt_complete from order_takeback where status=6";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        $val['cnt_complete'] = $data[cnt_complete];
        
        // 도착 확인 수 
        $query = "select count(*) as cnt_pos from order_takeback where status=4";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        $val['cnt_pos'] = $data[cnt_pos];
        
        // C/S 미접수 건수
        $query = "select count(*) as cnt_nocs from order_takeback where receive_date is null";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        $val['cnt_nocs'] = $data[cnt_nocs];
        
        $val['test'] = class_takeback::get_takeback_status( 165224, 165224 );
        echo json_encode( $val );
    }

    function get_cshistory_tb()
    {
        global $connect, $seq;
        
        $query = "select * from csinfo where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $val['cs_info'] = '';
        $val['query'] = $query;
    	while ( $data = mysql_fetch_array ( $result ) )
    	{
//    	    $msg = "[" . $data[input_date] . " " . $data[input_time] . "] / " . $data[content] . "\n" ;
//    	    $val['cs_info'] .=  iconv('cp949', 'utf-8', $msg);

    	    $val['cs_info'] .= "[" . $data[input_date] . " " . $data[input_time] . "] / " . $data[content] . "\n" ;
    	}
    	echo json_encode( $val );
    }

    // cs 생성
    function cs_insert_tb()
    {
        global $connect, $seq, $content;
        
    	$query = "insert csinfo 
    	             set order_seq  = '$seq',
                         input_date = now(),
                         input_time = now(),
                         writer     = '$_SESSION[LOGIN_NAME]',
                         cs_result  = '0',
                         content    = '$content'";
debug("CS 생성 : " . $query);                         
    	mysql_query($query, $connect);
    }
    
    // 검색결과를 다운로드한다.
    function download2()
    {
        global $connect;

        global $p_name,$option,$status,$return_req,$return_get,$recv_name,$recv_hp,$org_transno,$tb_transno,$trans_who,
               $trans_get,$rec_start,$rec_end,$req_start,$req_end,$reg_start,$reg_end,$pos_start,$pos_end,$comp_start,$comp_end,
               $qty_err,$return_err,$fee_err,$return_ng,$p_status;

        $header =  array ("No","회수번호","관리번호","고객명","핸드폰","집전화","주소","상품명","옵션","수량","주문일","발주일",
                          "배송일","회수접수일","회수요청일","송장등록일","도착확인일","박스개봉일","회수완료일","회수택배사","회수송장번호",
                          "회수상태","회수수량","반품구분","택배비부담","동봉택배비","계좌택배비","C/S담당자");
                          
        foreach( $header as $each_val )  $val[0][] = iconv( "utf-8","cp949", $each_val );

        $p_name    = iconv('utf-8','cp949',$p_name   );
        $option    = iconv('utf-8','cp949',$option   );
        $recv_name = iconv('utf-8','cp949',$recv_name);
        $trans_who = iconv('utf-8','cp949',$trans_who);
        $trans_get = iconv('utf-8','cp949',$trans_get);
        
        $pid_in   = $this->get_pid_list($p_name,$option );
        $order_in = $this->get_order_list($recv_name,$recv_hp,$org_transno);
            
        if( $domain     )  $cond .= ($cond?" and ":"") . (" domain         = '$domain'   ");
        if( $pid_in     )  $cond .= ($cond?" and ":"") . (" product_id    in $pid_in     ");
        if( $status     )  $cond .= ($cond?" and ":"") . (" status         = $status     ");
        if( $return_req )  $cond .= ($cond?" and ":"") . (" reason_req     = $return_req ");
        if( $return_get )  $cond .= ($cond?" and ":"") . (" reason_get     = $return_get ");
        if( $order_in   )  $cond .= ($cond?" and ":"") . (" order_seq     in $order_in   ");
        if( $tb_transno )  $cond .= ($cond?" and ":"") . (" trans_no       = $tb_transno ");
        if( $trans_who  )  $cond .= ($cond?" and ":"") . (" trans_who      = '$trans_who'");
        if( $trans_get  )  $cond .= ($cond?" and ":"") . (" trans_get      = '$trans_get'");
        if( $p_status   )  $cond .= ($cond?" and ":"") . (" product_status = $p_status   ");
        if( $rec_start  && $rec_end  )  $cond .= ($cond?" and ":"") . (" receive_date  >= '$rec_start'  and receive_date  < '$rec_end'  ");
        if( $req_start  && $req_end  )  $cond .= ($cond?" and ":"") . (" request_date  >= '$req_start'  and request_date  < '$req_end'  ");
        if( $reg_start  && $reg_end  )  $cond .= ($cond?" and ":"") . (" regist_date   >= '$reg_start'  and regist_date   < '$reg_end'  ");
        if( $pos_start  && $pos_end  )  $cond .= ($cond?" and ":"") . (" pos_date      >= '$pos_start'  and pos_date      < '$pos_end'  ");
        if( $comp_start && $comp_end )  $cond .= ($cond?" and ":"") . (" complete_date >= '$comp_start' and complete_date < '$comp_end' ");

        // 불일치
        if( $qty_err    == 'true' )  $cond .= ($cond?" and ":"") . (" qty_req <> qty_get ");
        if( $return_err == 'true' )  $cond .= ($cond?" and ":"") . (" reason_req <> reason_get ");
        if( $fee_err    == 'true' )  $cond .= ($cond?" and ":"") . (" ((trans_who <> trans_get) or ((ifnull(refund_req,0) + ifnull(bank_req,0)) <> (ifnull(refund_get,0) + ifnull(bank_get,0)))) ");
        if( ($qty_err=='true') || ($return_err=='true') || ($fee_err=='true') )
            $cond .= ($cond?" and ":"") . (" status = 5 ");  // 불일치를 선택하면 '박스개봉' 상태의 주문만 검색!!
            
        // 회수 이상
        if( $return_ng  == 1 )  
            $cond .= ($cond?" and ":"") . (" status not in (6) ");
        else if( $return_ng  == 2 )  
        {
            $t_time = date('Y-m-d H:i:s', mktime() - 5*24*60*60);
            $cond .= ($cond?" and ":"") . (" receive_date is not null and receive_date < '$t_time' ");
        }
        else if( $return_ng  == 3 )  
            $cond .= ($cond?" and ":"") . (" receive_date is null ");

        $query = "select * from order_takeback";
        if( $cond ) $query .= " where " . $cond;
        $query .= " order by number desc ";
        $result = mysql_query( $query, $connect );

        // 회수상태
        $data_status = array( 
            "", 
            iconv( "utf-8", "cp949", "[1] 회수접수"), 
            iconv( "utf-8", "cp949", "[2] 회수요청"), 
            iconv( "utf-8", "cp949", "[3] 송장등록"), 
            iconv( "utf-8", "cp949", "[4] 도착확인"), 
            iconv( "utf-8", "cp949", "[5] 박스개봉"), 
            iconv( "utf-8", "cp949", "[6] 회수완료")
        );
        // 반품구분
        $data_return = array(
            iconv( "utf-8", "cp949", ""            ),
            iconv( "utf-8", "cp949", "단순교환"    ),
            iconv( "utf-8", "cp949", "옵션교환"    ),
            iconv( "utf-8", "cp949", "불량교환(동)"),
            iconv( "utf-8", "cp949", "불량교환(타)"),
            iconv( "utf-8", "cp949", "오배송교환"  ),
            iconv( "utf-8", "cp949", "단순반품"    ),
            iconv( "utf-8", "cp949", "불량반품"    ),
            iconv( "utf-8", "cp949", "오배송반품"  ),
            iconv( "utf-8", "cp949", "배송지연반품"),
            iconv( "utf-8", "cp949", "기타"        )
        );

        $row = 1;
        $old_num = 0;
        while( $data = mysql_fetch_array( $result ) )
        {
            if( $old_num == $data[number] )  $eq = true;
            else  $eq = false;
                
            $data_order = class_order::get_order( $data[order_seq] );
            $data_prd = class_product::get_info( $data[product_id] );
            
            $val[] = array(
                $row,                                                         
                $eq?'-':$data[number],                                        
                $data_order[seq],                                       
                $data_order[recv_name],                                       
                $data_order[recv_mobile],                                     
                $data_order[recv_tel],                                        
                $data_order[recv_address],                                    
                $data_prd[name],                                              
                $data_prd[options],                                           
                $data_order[qty],                                             
                $data_order[order_date]>0?$data_order[order_date]:"",         
                $data_order[collect_date]>0?$data_order[collect_date]:"",         
                $data_order[trans_date_pos]>0?$data_order[trans_date_pos]:"", 
                $data[receive_date],                                          
                $data[request_date],                                          
                $data[regist_date],                                           
                $data[pos_date],                                              
                $data[box_date],                                              
                $data[complete_date],                                         
                $eq?'-':class_transcorp::get_corp_name($data[trans_corp]),
                $eq?'-':$data[trans_no],
                $data_status[$data[status]],
                $data[qty_req] . " / " . $data[qty_get],                      
                $data_return[$data[reason_req]] . " / " . $data_return[$data[reason_get]],
                $eq?'-':$data[trans_who] . " / " . $data[trans_get],          
                $eq?'-':$data[refund_req] . " / " . $data[refund_get],        
                $eq?'-':$data[bank_req] . " / " . $data[bank_get],            
                $data[who_receive]
            );
            
            $row++;
            $old_num = $data[number];
        }
        $obj = new class_file();
        $obj->download( $val, iconv("utf-8","cp949","회수정보 조회결과"));
    }

// 내부 함수

    // order_takeback에 등록되어있는지 확인한다.
    function is_registered($seq, $domain)
    {
        global $connect;
        
        $query = "select * from order_takeback where order_seq=$seq and domain='$domain'";
        $result = mysql_query( $query, $connect );
        return mysql_num_rows( $result );
    }

    // order_takeback에서 정보를 가져온다. seq 값으로.
    function get_tbinfo_seq($seq, $domain)
    {
        global $connect;
        
        $query = "select * from order_takeback where order_seq=$seq and domain='$domain'";
        $result = mysql_query( $query, $connect );
        return mysql_fetch_array( $result );
    }

    // order_takeback에서 정보를 가져온다. 완료된 주문정보를 number 값으로.
    function get_tbinfo_num($number, $domain)
    {
        global $connect;
        
        $query = "select * from order_takeback where domain='$domain' and number=$number and status=5";
        $result = mysql_query( $query, $connect );
        return mysql_fetch_array( $result );
    }

    // order_takeback에서 number의 최대값을 가져온다.
    function get_max_num($domain)
    {
        global $connect;
        
        $query = "select max(number) as max_num from order_takeback where domain='$domain'";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_array( $result );
        return $data[max_num];
    }

    // order_takeback에서 ezadmin으로 cs transaction을 전송한다.
    function add_transaction( $obj, $seq, $domain, $status)
    {
        global $connect;
        
        $query = "select number from order_takeback where order_seq=$seq and domain='$domain'";
        $result = mysql_query( $query, $connect );

        $status = iconv('utf-8','cp949',$status);        
        $obj->add_cs_tr($seq, $data[number], $status);
    }
}
?>
