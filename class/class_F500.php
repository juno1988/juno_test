<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_transcorp.php";
require_once "class_ui.php";
////////////////////////////////
// class name: class_F500
//

class info{
    var $trans_date;
    var $cnt = array( "선불"=> 0,
                      "착불"=> 0 );       // 선불 , 착불 
}

class class_F500 extends class_top {

    ///////////////////////////////////////////

    function F500()
    {
        global $connect;
        global $template, $line_per_page, $act, $start_date, $end_date, $status, $date_type , $group_trans_no, $except_add_order;
		global $str_supply_code, $s_group_id;
				
        echo "<script>show_waiting()</script>";
        flush();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-20 day'));

        // data 가져오기
        // $infos = $this->get_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting()</script>";
        flush();

    }

    function search()
    {
        global $connect;
        global $template, $line_per_page, $act, $start_date, $end_date, $status, $date_type, $group_trans_no, $except_add_order;
        global $str_supply_code, $s_group_id;

        echo "<script>show_waiting()</script>";
        flush();

        if (!$start_date) $start_date = date('Y-m-d', strtotime('-20 day'));

        // data 가져오기
        $infos = $this->get_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting()</script>";
        flush();

    }


    // list 가져오기
    function get_list()
    {
        global $connect, $start_date, $end_date, $infos, $trans_corp, $shop_id, $supply_id, $status, $date_type, $except_add_order;
		global $str_supply_code, $s_group_id, $supply_code;
		
		if(count($supply_code)>0)
			$str_supply_code = $this->get_str_supply();
		
        $date_type = $date_type ? $date_type : "trans_date_pos";
        
        if( _DOMAIN_ == 'box4u' )
        {
            $cnt_query = "sum(a.qty) cnt";
            $cnt_query2 = "sum(a.qty) cnt";
        }
        else
        {
            $cnt_query = "count(*) cnt";
            $cnt_query2 = "count(distinct a.trans_no) cnt";
        }

        $query = "select $cnt_query, DATE_FORMAT( a.$date_type ,'%Y-%m-%d') as trans_date , a.trans_who ";

		if( _DOMAIN_ == 'box4u' )
		{	
			$option = "	from orders a use index (orders_idx10)
			           where a.$date_type >= '$start_date 00:00:00' 
                         and a.$date_type <= '$end_date 23:59:59'";
		}
		else 
		{
			$option = " from orders a use index (orders_idx10), order_products b, products c 
					  where a.seq = b.order_seq and b.product_id = c.product_id
					    and a.$date_type >= '$start_date 00:00:00' 
			            and a.$date_type <= '$end_date 23:59:59'";
		}

		if($str_supply_code && _DOMAIN_ != "box4u")
			$option .= "and c.supply_code IN ($str_supply_code) ";
        if ( $trans_corp != 99 )
            $option .= " and a.trans_corp='$trans_corp'";

        if ( $shop_id )
            $option .= " and a.shop_id='$shop_id' ";
/*
        // 추가주문 제외
        if( $except_add_order )
            $option .= " and seq<>order_id and c_seq=0 and copy_seq=0 ";
*/
        switch ( $status )
        {
            case "7": // 송장입력 + 정상
                $option .= " and a.status=7  and a.order_cs not in (1,2,3,4) ";
                break;
            case "8": // 배송 후
                $option .= " and a.status=8 ";
                break;
        }
        
        $query .= $option . " group by a.trans_no, DATE_FORMAT(a.$date_type,'%Y-%m-%d'), a.trans_who order by a.$date_type desc";
debug("배송통계1 : " . $query);
        $result = mysql_query ( $query, $connect );

        while ( $data = mysql_fetch_array ( $result ) )
        {
            if( _DOMAIN_ == 'box4u' )
                $infos[$data[trans_date]][$data[trans_who]] += $data[cnt];
            else
                $infos[$data[trans_date]][$data[trans_who]]++;

            if ( $data[trans_who] == "선불" )
            {
                if( _DOMAIN_ == 'box4u' )
                    $infos["선불"] += $data[cnt];
                else
                    $infos["선불"]++;
            }
            else
            {
                if( _DOMAIN_ == 'box4u' )
                    $infos["착불"] += $data[cnt];
                else
                    $infos["착불"]++;
            }
        }

        // 합포 , 일반 구분
        // 1. 합포
        $query = "select $cnt_query, DATE_FORMAT( a.$date_type ,'%Y-%m-%d') as trans_date";
        $query .= $option . " and a.pack > 0 group by a.trans_no, DATE_FORMAT(a.$date_type,'%Y-%m-%d') order by a.$date_type desc";
debug("배송통계2 : " . $query);
        $result = mysql_query( $query, $connect );

        while ( $data   = mysql_fetch_array( $result ) )
        {
            if( _DOMAIN_ == 'box4u' )
                $infos[$data[trans_date]]['합포'] += $data[cnt];
            else
                $infos[$data[trans_date]]['합포']++;
        }

        // 2. 개별
        $query = "select $cnt_query2, DATE_FORMAT( a.$date_type ,'%Y-%m-%d') as trans_date ";
        $query .= $option . " and a.pack=0 group by DATE_FORMAT(a.$date_type,'%Y-%m-%d') order by a.$date_type desc";
debug("배송통계3 : " . $query);
        $result = mysql_query( $query, $connect );
        $tot = 0;
        while ( $data   = mysql_fetch_array( $result ) )
            $infos[$data[trans_date]]['개별'] = $infos[$data[trans_date]]['개별'] + $data[cnt];        

        return $infos;
    }

    //------------------------------------------------
    // 송장 번호 download
    //
    function save_file()
    {
        global $connect, $saveTarget, $filename, $trans_who, $trans_date, $trans_corp, $start_date, $end_date;
        global $infos, $trans_corp, $shop_id, $supply_id, $date_type, $status, $group_trans_no;
		global $str_supply_code, $s_group_id;
		
        if( $trans_date != 'all' )
        {
            $start_date = $trans_date;
            $end_date = $trans_date;
        }
        
        $query = "select DATE_FORMAT(a.trans_date_pos,'%Y-%m-%d') as trans_date_pos,
                         a.collect_date,
                         a.trans_date,
                         a.seq,
                         a.pack,
                         a.recv_address,
                         a.trans_corp,
                         a.trans_no, 
                         a.trans_who, 
                         a.shop_id,
                         a.order_id,
                         a.recv_name,
                         a.product_name,
                         a.options,
                         sum(a.qty) as qty ";
                  
        if(_DOMAIN_ =="box4u")
        {
        	$query .=" from orders a use index (orders_idx10)
        				where a.$date_type >= '$start_date 00:00:00' 
	                      and a.$date_type <= '$end_date 23:59:50'"; 	
        }
        else
        {
	        $query .=" from orders a use index (orders_idx10), order_products b, products c 
	                   where a.seq = b.order_seq and b.product_id = c.product_id          
	                     and a.$date_type >= '$start_date 00:00:00' 
	                     and a.$date_type <= '$end_date 23:59:50'"; 	
        }
         
		if($str_supply_code && _DOMAIN_ != "box4u")
			$query .= "and c.supply_code IN ($str_supply_code) ";  
                   
        if( $trans_corp != 99 )
            $query .= " and a.trans_corp='$trans_corp'";

        if( $shop_id )
            $query .= " and a.shop_id='$shop_id' ";

        if( $supply_id )
            $query .= " and a.supply_id='$supply_id' ";

        switch ( $status )
        {
            case "7": // 배송전
                $query .= " and a.status=7 and a.order_cs not in (1,2,3,4) ";
                break;
            case "8": // 배송 후
                $query .= " and a.status=8 ";
                break;
        }

        switch( $trans_who )
        {
            case "선불":
            case "착불":
                $query .= " and a.trans_who='$trans_who' ";
                if( $group_trans_no )
                    $query .= " group by a.seq order by a.trans_no, a.$date_type";
                else
                    $query .= " group by a.trans_no order by a.$date_type";
                break;
            case "합포":
                $query .= " and pack>0 ";
                if( $group_trans_no )
                    $query .= " group by a.seq order by a.trans_no, a.$date_type, a.pack, a.seq";
                else
                    $query .= " group by a.trans_no order by a.$date_type, a.pack, a.seq ";
                break;
            case "개별":
                $query .= " and pack=0 ";
                if( $group_trans_no )
                    $query .= " group by a.seq order by a.trans_no, a.$date_type, a.pack, a.seq";
                else
                    $query .= " group by a.trans_no order by a.$date_type, a.pack, a.seq ";
                break;
            case "모두":
                if( $group_trans_no )
                    $query .= " group by a.seq order by a.trans_no, a.$date_type, a.seq";
                else
                    $query .= " group by a.trans_no order by a.$date_type, a.seq";
                break;
        }
        
debug("다운로드 ".$query);

        $_data = array();
        $_data[] = array(
            collect_date => "발주일",
            trans_date   => "송장등록일",
            date_type    => ($date_type=="trans_date_pos"?"배송일":"발주일"),
            seq          => "관리번호",
            pack         => "합포번호",
            trans_corp   => "택배사",
            trans_no     => "송장번호",
            trans_who    => "선착불",
            shop_id      => "판매처",
            order_id     => "주문번호",
            recv_name    => "수령자",
            recv_address => "수령지",
            product_name => "상품명",
            options      => "옵션",
            qty          => "수량"
        );
        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $_data[] = array(
                collect_date => $data[collect_date],
                trans_date   => $data[trans_date],
                date_type    => ($date_type=="trans_date_pos"?$data[trans_date_pos]:$data[collect_date]),
                seq          => $data[seq],
                pack         => $data[pack],
                trans_corp   => class_transcorp::get_corp_name( $data[trans_corp] ),
                trans_no     => $data[trans_no],
                trans_who    => $data[trans_who],
                shop_id      => class_shop::get_shop_name( $data[shop_id] ),
                order_id     => $data[order_id],
                recv_name    => $data[recv_name],
                recv_address => $data[recv_address],
                product_name => $data[product_name],
                options      => $data[options],
                qty          => $data[qty]
            );
        }

        $this->save_file_xls( $_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

   function save_file_xls( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<body>
<html><table border=1>
";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            // for column
            foreach ( $row as $key=>$value) 
            {
                if ( $key == 'qty' )
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\"'>" . $value . "</td>";
                else
                    $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
            }

            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
   }

    //////////////////////////////////////
    // 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "trans_list.xls");
    }    

}

?>
