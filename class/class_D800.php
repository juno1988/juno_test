<?
require_once "class_E.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_top.php";
require_once "class_shop.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_file.php";
require_once "ExcelReader/reader.php";
require_once "lib/ez_excel_lib.php";
require_once "class_ui.php";
require_once "class_category.php";
require_once "class_multicategory.php";


////////////////////////////////
// class name: class_D800
//
class class_D800 extends class_top 
{
   var $order_id;
   var $debug = "on"; // 전체 download: on/off
   var $format;
   var $font   = 'Arial'; 
   var $size   = 10; 
   var $align  = 'right'; 
   var $valign = 'vcenter'; 
   var $bold   = 0; 
   var $italic = 0; 

   function D800()
   {
      global $template, $start_date, $end_date, $shop_id, $sub, $connect;
      global $page, $seq,$pack, $order_id, $recv_name, $recv_mobile, $product_id, $product_name, $options, $status_sel, $order_cs_sel, $recv_address, $trans_no, $priority, $enable_sale, $_date, $supply_code,
             $shop_product_id, $shop_product_name, $shop_options, $special_order, $delete_order, $group_id, $s_group_id, $except_after_change, $recover_delete, $order_name, $is_island;
	  global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
	  global $category;
        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();

      $par_arr = array("template","action","shop_id","supply_code","_date","start_date","end_date","status_sel","order_cs_sel","seq","order_id","trans_no","recv_name",
                 "recv_mobile","recv_address","order_name","shop_product_id","shop_product_name","shop_options","product_id","product_name","options","page","enable_sale","special_order","delete_order","group_id","bck_search","s_group_id","is_island","str_category", "click_index", "current_category1", "current_category2", "current_category3", "m_sub_category_1", "m_sub_category_2", "m_sub_category_3", "category");
      $link_url_list = $this->build_link_par($par_arr);  


		$diff_date1 = explode("-",$start_date); 
		$diff_date2 = explode("-",$end_date); 
		
		$tm1 = mktime(0,0,0,$diff_date1[1],$diff_date1[2],$diff_date1[0]); 
		$tm2 = mktime(0,0,0,$diff_date2[1],$diff_date2[2],$diff_date2[0]);
		
		$diff_days = ($tm1 - $tm2) / 86400 + 1;
		
		if( !($seq || $order_id || $trans_no || $recv_name || $recv_mobile || $recv_address || $order_name || $shop_product_id || $shop_product_name || $shop_options || $product_id || $product_name || $options || $shop_id|| $group_id || $supply_code || $s_group_id || $status_sel || $order_cs_sel) )
		{
    		if( _DOMAIN_ == 'joongwon' && $diff_days < -7)
    		{
				echo "<script>alert('현재 서버 과부하로 검색조건 없이 7일 이상 검색이 제한 됩니다.');</script>";
				$master_code = substr( $template, 0,1);
				include "template/" . $master_code ."/" . $template . ".htm";
				return;
			}
    		else if($diff_days < -31)
    		{
				echo "<script>alert('검색조건 없이 1개월 이상 검색이 제한 됩니다.');</script>";
				$master_code = substr( $template, 0,1);
				include "template/" . $master_code ."/" . $template . ".htm";
				return;
			}
		}
			
			



      $line_per_page = _line_per_page;
      $link_url = "?" . $this->build_link_url();

      if ( $_REQUEST["page"] )
      {
         echo "<script>show_waiting()</script>";
         $opt = "only_not_trans";
         $result = $this->search2( &$total_orders, &$total_rows, &$total_trans, 0 ); 
         
         // 삭제주문 복구
         if( $recover_delete )
         {
            $result_recover = $this->search2( &$total_orders, &$total_rows, &$total_trans, 1 ); 
            
            $seq_arr = array();
            $pack_arr = array();
            while( $data_recover = mysql_fetch_assoc($result_recover) )
            {
                $seq_arr[] = $data_recover[seq];
                if( $data_recover[pack] )
                    $pack_arr[] = $data_recover[pack];
            }

            // pack 기준주문 빼고 일부만 삭제된 경우
            $reset_pack_list = implode(",", array_diff($pack_arr, $seq_arr));
            if( $reset_pack_list )
            {
                $query_reset_pack = "update orders_del set pack = 0 where pack in ($reset_pack_list)";
                mysql_query($query_reset_pack, $connect);
            }
            
            if( $seq_arr )
            {
                $seq_list = implode(",", $seq_arr);
                $query_recover = "insert orders select * from orders_del where seq in ($seq_list)";
                mysql_query($query_recover, $connect);
                
                $query_recover = "insert order_products select * from order_products_del where order_seq in ($seq_list)";
                mysql_query($query_recover, $connect);
                
                $query_recover = "delete from orders_del where seq in ($seq_list)";
                mysql_query($query_recover, $connect);
                
                $query_recover = "delete from order_products_del where order_seq in ($seq_list)";
                mysql_query($query_recover, $connect);
            }
            
            // 전화번호 검색
            $query_tel = "select seq, recv_tel, recv_mobile, order_tel, order_mobile from orders where seq in ($seq_list)";
            $result_tel = mysql_query($query_tel, $connect);
            while( $data_tel = mysql_fetch_assoc($result_tel) )
                $this->inset_tel_info($data_tel[seq], array($data_tel[recv_tel],$data_tel[recv_mobile],$data_tel[order_tel],$data_tel[order_mobile]));
         }
      }

	  if (!$start_date && _DOMAIN_ != "buyclub")
		$start_date = date('Y-m-d', strtotime('-30 day'));
      	
      $end_date = $end_date;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      if ( $_REQUEST["page"] )
         echo "<script>hide_waiting()</script>";

        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "확장주문검색[$start_date ~ $end_date]");

   }

    //============================================
    // 신발주용 검색로직
    // 2009-7-20 - jk
    function search2( &$total_orders, &$total_rows, &$total_trans, $is_download )
     {
            global $connect, $start_date, $end_date, $shop_id,$page, $seq, $pack, $order_id, $recv_name, $recv_mobile, $except_after_change,
                   $product_id, $product_name, $options, $status_sel,$order_cs_sel, $recv_address, $order_name, $trans_no, $_date, $supply_code,
                   $shop_product_id, $shop_product_name, $shop_options, $admin_options, $enable_sale, $special_order, $group_id, $s_group_id, $code_all, $stock_cnt, $delete_order,$download_type,$is_island;
                   
			global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
			global $category;
			
            // 상품 개수
            // 주문 개수
            $query_trans_cnt   = "select count(distinct if(a.pack=0, a.seq, a.pack)) cnt ";
            $query_order_cnt   = "select count(distinct a.seq) cnt ";
            $query_product_cnt = "select count(distinct b.seq) cnt ";
            $query_products_cnt = "select sum(b.qty) qty_sum ";

            // 주문 정보
            $query = "select if(a.pack>0,a.pack, a.seq ) xx,
                             a.seq,
                             a.pack,
                             a.shop_id,
                             a.order_id,
                             a.order_type,
                             a.status,
                             a.order_status,
                             a.copy_seq,
                             ifnull(b.order_cs, a.order_cs) as order_cs,
                             a.hold,
                             a.trans_who,
                             a.org_trans_who,
                             b.product_id,
                             b.supply_id,
                             a.shop_product_id,
                             a.product_name,
                             a.options,
                             b.shop_options,
                             a.qty order_qty,
                             ifnull(b.qty, a.qty) as qty,
                             a.amount,
                             a.supply_price,
                             a.extra_money,
                             a.trans_price,
                             products.supply_price prd_supply_price,
                             a.order_date,
                             a.order_time,
                             b.cancel_date,
                             b.is_gift,
                             a.collect_date,
                             a.collect_time,
                             a.trans_date,
                             a.trans_no,
                             a.trans_corp,                             
                             a.recv_address,
                             a.trans_date_pos,
                             a.priority,
                             a.order_name,
                             a.order_tel,
                             a.order_mobile,
                             a.recv_zip,
                             a.recv_name,
                             a.recv_tel,
                             a.recv_mobile,
                             a.memo,
                             a.trans_fee,
                             products.location,
                             a.code1,
                             a.code2,
                             b.prd_amount b_prd_amount,
                             b.prd_supply_price b_prd_supply_price,
                             b.extra_money b_extra_money,
                             products.category,
                             products.str_category ";

            if( $code_all == 1 )
            {
                $query .= ",a.code3,
                           a.code4,
                           a.code5,
                           a.code6,
                           a.code7,
                           a.code8,
                           a.code9,
                           a.code10,
                           a.code21,
                           a.code22,
                           a.code23,
                           a.code24,
                           a.code25,
                           a.code26,
                           a.code27,
                           a.code28,
                           a.code29,
                           a.code30,
                           a.code11,
                           a.code12,
                           a.code13,
                           a.code14,
                           a.code15,
                           a.code16,
                           a.code17,
                           a.code18,
                           a.code19,
                           a.code20,
                           a.code31,
                           a.code32,
                           a.code33,
                           a.code34,
                           a.code35,
                           a.code36,
                           a.code37,
                           a.code38,
                           a.code39,
                           a.code40 ";
            }

            if ( $download_type == 2 )
                $query .= ", if(a.pack=0, a.seq, a.pack) seq_pack ";

            // jk 추가, 주문별 다운로드 요청 2011.09.07
            if ( $download_type > 0 )
            {
                $query .= ",sum(b.org_price) sum_org_price";
            }

            // 재고 포함
            if( $stock_cnt && $is_download )
                $query .= ",current_stock.stock ";

            $query .= ",products.origin";
            $query .= ",a.pay_type";
            $query .= ",a.order_id_seq";
            $query .= ",a.cust_id";
            $query .= ",a.prepay_price";

            if( $delete_order )
            {
                $opt = " from orders_del a
                         left outer join order_products_del b on a.seq = b.order_seq
                         left outer join products on b.product_id = products.product_id ";
            }
            else
            {
                $opt = " from orders a
                         left outer join order_products b on a.seq = b.order_seq
                         left outer join products on b.product_id = products.product_id ";
            }

            // 재고 포함
            if( $stock_cnt && $is_download )
                $opt .= " left outer join current_stock on products.product_id = current_stock.product_id and current_stock.bad=0 ";

            // 취소일 추가  jk.ryu 2010-5.12
            if ( $_date == "cancel_date" )
            {
                $opt .= "where b.$_date >= '$start_date 00:00:00'
                           and b.$_date <= '$end_date 23:59:59' 
                           and b.order_cs in ( 1,2,3,4 )";
            }
            else if ( $_date == "change_date" )
            {
                $opt .= "where b.$_date >= '$start_date 00:00:00'
                           and b.$_date <= '$end_date 23:59:59' ";
            }
            
            else if( $_date == "collect_date" )
            {
                $opt .= "where a.$_date >= '$start_date'
                           and a.$_date <= '$end_date' ";
            }
            else if( $_date == "order_date" )
            {
                $opt .= "where a.$_date >= '$start_date'
                           and a.$_date <= '$end_date' ";
            }
            else if( $_date == "trans_date" )
            {
                $opt .= "where a.$_date >= '$start_date 00:00:00'
                           and a.$_date <= '$end_date 23:59:59' 
                           and a.status >= 7 ";
            } 
            else if( $_date == "trans_date_pos" )
            {
                $opt .= "where a.$_date >= '$start_date 00:00:00'
                           and a.$_date <= '$end_date 23:59:59' 
                           and a.status = 8 ";
            } 
            else
            {
                $opt .= "where a.$_date >= '$start_date 00:00:00'
                           and a.$_date <= '$end_date 23:59:59' ";
            } 
/*
            // 재고 포함
            if( $stock_cnt && $is_download )
                $opt .= " and b.product_id = current_stock.product_id and current_stock.bad=0 ";
*/
            // 그룹 추가
            if ( $group_id )
            {
                $shop_id = $this->get_group_shop( $group_id );      
            }
            
            if ( $shop_id )
                $opt .= " and a.shop_id in ( $shop_id ) ";
                
            if( $s_group_id )
            {
                $s_group_list = "";
                $query_sg = "select code from userinfo where level=0 and group_id=$s_group_id";
                $result_sg = mysql_query($query_sg, $connect);
                while( $data_sg = mysql_fetch_assoc($result_sg) )
                    $s_group_list .= ($s_group_list ? "," : "") . $data_sg[code];
                
                if( $s_group_list )
                    $opt .= " and b.supply_id in ($s_group_list) ";
                else
                    $opt .= " and 0 ";
            }    
            
            if ( $supply_code )
                    $opt .= " and b.supply_id = '$supply_code' ";

            switch( $status_sel )
            {
                case 1: $opt .= " and a.status = 0 "; break;
                case 2: $opt .= " and a.status = 1 "; break;
                case 3: $opt .= " and a.status = 7 "; break;
                case 4: $opt .= " and a.status = 8 "; break;
                case 5: $opt .= " and a.status in (1,7) "; break;
                case 6: $opt .= " and a.status in (1,8) "; break;
                case 7: $opt .= " and a.status in (7,8) "; break;
            }
            
            switch( $order_cs_sel )
            {
                case 1: $opt .= " and b.order_cs in ( 0 )"; break;
                case 2: $opt .= " and b.order_cs in ( 1,2,3,4 )"; break;
                case 3: $opt .= " and b.order_cs in ( 5,6,7,8 )"; break;
                case 4: $opt .= " and b.order_cs in ( 1,2 )"; break;
                case 5: $opt .= " and b.order_cs in ( 3,4 )"; break;
                case 6: $opt .= " and b.order_cs in ( 5,6 )"; break;
                case 7: $opt .= " and b.order_cs in ( 7,8 )"; break;
                case 8: $opt .= " and a.hold > 0"; break;
                case 9: $opt .= " and a.cross_change > 0"; break;
                case 10: $opt .= " and b.order_cs in ( 0,5,6,7,8 )"; break;
                case 11: $opt .= " and a.c_seq>0 "; break;
            }

            if ( $order_name )
                $opt .= " and a.order_name = '$order_name' ";

            if ( $recv_name )
                $opt .= " and a.recv_name = '$recv_name' ";

            if ( $recv_mobile )
                $opt .= " and a.recv_mobile = '$recv_mobile' ";
    
            if ( $trans_no )
                $opt .= " and a.trans_no = '$trans_no' ";
    
            if ( $seq )
                $opt .= " and a.seq = '$seq' ";
            
            if( $recv_address )             		
            	$opt .= " and a.recv_address like '%$recv_address%' ";
            
            if ( $order_id )
                $opt .= " and a.order_id like '$order_id%' ";

            if ( $pack )
                $opt .= " and a.pack = '$pack' ";   

            // shop
            if ( $shop_product_id )        
                $opt .= " and a.shop_product_id like '%$shop_product_id%' ";

            if ( $shop_product_name )
            	$opt .= " and a.product_name like '%$shop_product_name%' ";

  			if ( $shop_options )      			              
  			{
  			    if( _DOMAIN_ == 'eleven2' )
      			    $opt .= " and b.shop_options like '%$shop_options%' ";
                else
      			    $opt .= " and a.options like '%$shop_options%' ";
      		}
    
            // ezadmin
            if ( $product_id )        
                $opt .= " and products.product_id = '$product_id' ";

            if ( $product_name )
            	$opt .= " and products.name like '%$product_name%' ";

  			if ( $options )      			              
      			$opt .= " and products.options like '%$options%' ";
      			
      	    //품절
      	    if ( $enable_sale )
      	    	$opt .= " and products.enable_sale=0 ";
      	    	
      	    //도서지역
      	    if ( $is_island )
      	    	$opt .= " and  replace(a.recv_zip,'-','') in (409840,409841,409841,409842,409842,409842,409850,409851,409851,409851,409851,409851,409851,409852,409852,409853,409880,409881,409882,409883,409890,409891,409891,409891,409891,409891,409892,409892,409893,409893,409910,409910,409911,409911,409911,409911,409911,409912,409912,409913,409919,409919,409919,409919,409919,417910,417911,417911,417912,417912,417913,417913,417913,417920,417921,417921,417921,417921,417922,417922,417922,417922,417922,417923,417923,417923,417923,417930,417931,417931,417932,417933,513890,513891,513891,513891,513891,513892,513892,513892,513892,513892,513892,513892,513893,530145,530430,530440,530600,530800,530800,530801,530801,535805,535806,535820,535821,535821,535822,535822,535823,535823,535824,535824,535830,535831,535832,535833,535834,535835,535836,535837,535838,535840,535841,535841,535841,535841,535841,535841,535841,535841,535841,535841,535842,535842,535842,535842,535842,535842,535842,535842,535843,535843,535843,535843,535843,535843,535844,535844,535844,535845,535845,535846,535847,535850,535851,535851,535851,535851,535852,535852,535852,535860,535861,535861,535861,535861,535861,535862,535862,535862,535862,535863,535870,535871,535871,535871,535871,535872,535872,535872,535872,535873,535873,535873,535873,535880,535881,535881,535881,535881,535882,535882,535882,535883,535883,535884,535884,535884,535885,535890,535891,535891,535891,535891,535892,535892,535892,535893,535893,535893,535894,535894,535894,535895,535895,535895,535896,535896,535897,535897,535898,535910,535911,535912,535912,535912,535912,535912,535912,535913,535913,535913,535913,535914,535914,535914,535915,535916,535917,535917,535918,535919,535920,535921,535921,535922,535922,535923,535924,535924,535925,535925,535926,535926,535930,535931,535931,535932,535932,535933,535934,535934,535935,535935,535936,535936,535936,535940,535941,535942,535942,535943,535943,536928,537809,537818,537820,537821,537821,537821,537822,537822,537823,537823,537823,537823,537823,537823,537824,537824,537824,537825,537826,537830,537831,537831,537831,537831,537832,537833,537834,537834,537834,537834,537834,537834,537835,537835,537835,537835,537835,537836,537840,537841,537841,537841,537841,537841,537841,537841,537841,537842,537842,537842,537842,537842,537842,537843,537843,537843,537844,537845,537845,537845,537845,537845,537845,537845,537845,537845,537846,537847,537848,537848,537849,537849,537849,537850,537851,537851,537851,537851,537851,537851,537851,537852,537852,537852,537852,537852,537852,537852,537853,537900,537900,537901,537901,537901,537902,537902,537902,537902,537902,537902,537903,537903,537903,537903,537903,537903,537904,537904,537904,537904,537905,537907,537909,537909,537920,537921,537921,537921,537922,537922,537922,537922,539910,539911,539911,539912,539912,539912,539912,539912,539913,539913,539914,539914,539914,539914,539914,539915,539915,539915,539915,539915,539916,539916,539916,539916,539916,539917,539917,539917,539918,539918,539918,539919,539919,539919,539919,539919,539919,546908,548840,548902,548909,548930,548931,548931,548932,548932,548933,548934,548936,548941,548993,550270,555300,556830,556831,556832,556833,556834,556834,556835,556836,556837,556838,556839,556840,556841,556842,556843,556843,556844,556846,556847,556848,556849,556849,556850,556851,556852,556852,556853,556854,556855,573810,573811,573811,573812,573813,573813,573813,573814,573815,573815,573816,573816,573817,573818,573818,573819,579910,579911,579911,579912,579913,579914,579914,579915,618410,618420,618430,618440,618450,618821,645420,650833,650835,650910,650911,650912,650913,650914,650915,650916,650920,650925,650930,650931,650932,650932,650933,650934,650934,650941,650943,650943,650944,650945,650946,650947,656861,656861,656861,656861,656861,656861,656861,656861,656861,664260,664270,690003,690011,690012,690021,690022,690029,690031,690032,690032,690041,690042,690043,690050,690061,690062,690071,690072,690073,690081,690082,690090,690100,690110,690121,690122,690130,690140,690150,690161,690162,690163,690170,690180,690191,690192,690200,690210,690220,690231,690232,690241,690242,690600,690610,690700,690701,690703,690704,690705,690706,690707,690708,690709,690710,690711,690712,690713,690714,690715,690717,690718,690719,690720,690721,690722,690723,690724,690725,690726,690727,690728,690729,690730,690731,690732,690734,690735,690736,690737,690738,690739,690740,690741,690742,690743,690744,690747,690750,690751,690751,690755,690756,690760,690762,690764,690764,690765,690766,690767,690769,690770,690771,690772,690773,690774,690775,690776,690777,690778,690779,690780,690781,690782,690783,690784,690785,690786,690787,690788,690789,690790,690796,690800,690800,690800,690801,690801,690801,690802,690803,690804,690805,690806,690806,690807,690808,690808,690808,690808,690809,690809,690809,690809,690810,690810,690811,690811,690812,690812,690813,690813,690814,690814,690815,690816,690817,690817,690818,690819,690819,690820,690820,690821,690821,690821,690822,690822,690822,690822,690823,690823,690823,690824,690825,690825,690826,690827,690827,690828,690828,690828,690828,690829,690829,690830,690830,690830,690831,690832,690832,690832,690833,690833,690833,690834,690835,690835,690835,690836,690836,690837,690838,690838,690839,690839,690840,690841,690842,690843,690844,690846,690846,690846,690847,690850,690850,690851,695705,695789,695791,695792,695793,695794,695795,695796,695900,695901,695902,695903,695903,695904,695904,695904,695905,695905,695906,695907,695908,695909,695910,695911,695912,695913,695914,695915,695916,695917,695918,695918,695919,695920,695921,695921,695922,695923,695924,695925,695926,695927,695928,695929,695930,695931,695931,695932,695933,695934,695940,695941,695942,695942,695942,695943,695944,695945,695945,695946,695947,695948,695949,695950,695951,695951,695952,695952,695952,695960,695961,695962,695962,695963,695964,695965,695966,695967,695968,695969,695970,695971,695972,695973,695974,695975,695975,695976,695976,695977,695978,695979,695980,695981,695982,695983,695983,695983,697010,697011,697012,697013,697014,697020,697030,697040,697050,697060,697070,697080,697090,697100,697110,697120,697130,697301,697310,697320,697330,697340,697350,697360,697370,697370,697380,697390,697600,697700,697701,697703,697704,697704,697705,697705,697706,697707,697805,697806,697807,697808,697819,697820,697821,697822,697823,697824,697825,697826,697827,697828,697829,697830,697831,697832,697833,697834,697835,697836,697837,697838,697838,697839,697840,697840,697841,697841,697841,697842,697842,697842,697842,697843,697843,697844,697845,697846,697847,697848,697849,697850,697851,697852,697853,697854,697855,697855,697856,697856,697857,697857,697858,697859,697859,697860,697860,697861,697862,697862,697863,697863,697864,699701,699701,699702,699900,699901,699902,699902,699903,699903,699904,699904,699905,699905,699906,699907,699908,699910,699911,699911,699912,699913,699914,699915,699916,699920,699921,699921,699921,699921,699922,699922,699923,699923,699924,699925,699925,699926,699930,699931,699931,699931,699931,699931,699932,699932,699932,699933,699933,699934,699935,699936,699937,699937,699940,699941,699942,699943,699944,699945,699946,699947,699948,699949,799800,799800,799801,799801,799801,799802,799803,799804,799810,799811,799811,799811,799811,799812,799813,799820,799821,799821,799821,799821,799821,799822,799822,799822,799823) ";	
      	    	
      	            
	        if( $category )
	            $opt .= " and products.category = '$category' ";
	        
	        $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
	        if ( $arr_search_id[$m_sub_category_1] )
	            $opt .= " and products.m_category1=" . $arr_search_id[$m_sub_category_1];
	        
	        if ( $arr_search_id[$m_sub_category_2] )
	            $opt .= " and products.m_category2=" . $arr_search_id[$m_sub_category_2];
	        
	        if ( $arr_search_id[$m_sub_category_3] )
	            $opt .= " and products.m_category3=" . $arr_search_id[$m_sub_category_3];	
      	    	
      			
      	    // 배송후교환 제외
      	    if ( $except_after_change )
      	    	$opt .= " and a.c_seq=0 ";
      			
      	    //특수주문
      	    if ( $special_order )
            	$opt .= $this->query_special_order();
            
            // jk 추가, 주문별 다운로드 요청 2011.09.07            
            if ( $download_type == 1 )
                $opt .= " group by a.seq ";
            else if ( $download_type == 2 )
                $opt .= " group by seq_pack ";

            // 주문 개수
            $query_trans_cnt   = $query_trans_cnt . $opt;
            $query_order_cnt   = $query_order_cnt . $opt;
            $query_product_cnt = $query_product_cnt . $opt;
            $query_products_cnt = $query_products_cnt . $opt;

            // 전체 배송 기준 개수
            $result_trans_cnt = mysql_query( $query_trans_cnt, $connect );
            $data             = mysql_fetch_assoc( $result_trans_cnt );
            $total_trans      = $data[cnt];

            // 전체 주문 개수
            $result_order_cnt = mysql_query( $query_order_cnt, $connect );
            $data             = mysql_fetch_assoc( $result_order_cnt );
            $total_orders     = $data[cnt];

            // 전체 상품 개수
            $result_product_cnt = mysql_query( $query_product_cnt, $connect );
            $data               = mysql_fetch_assoc( $result_product_cnt );
            //$total_rows         = $data[cnt];

            // 전체 상품 수
            $result_products_cnt = mysql_query( $query_products_cnt, $connect );
            $data               = mysql_fetch_assoc( $result_products_cnt );
            $total_rows     = $data[qty_sum];
                    
            if( _DOMAIN_ == 'tokio' )
                $opt .= " order by xx desc, b.order_seq, b.is_gift ";
            else if( _DOMAIN_ == 'shabath' )
                $opt .= " order by xx desc, b.order_seq, b.is_gift, b.seq ";
            else
                $opt .= " order by xx, b.order_seq, b.is_gift, b.marked, b.seq ";
            
            if ( !$is_download )
            {
                // limit
                $start = (($page ? $page : 1 )-1) * 20;
                $opt .= " limit $start, 20";
            }
debug("확장주문검색 쿼리 :".$query . $opt);
            $result = mysql_query( $query . $opt, $connect );  
            
            return $result;
    }

    // 
    // 해당 그룹의 판매처 리스트..
    function get_group_shop( $group_id )
    {
        global $connect,$shop_id;
        
        $query = "select shop_id from shopinfo where group_id=$group_id"; 
        
        if ( $shop_id )
            $query .= " and shop_id=$shop_id";

        $result = mysql_query( $query, $connect );
        $shop_ids = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_ids .= $shop_ids ? "," : "";
            $shop_ids .= $data[shop_id];
        }
        
debug("shop_ids : " . $shop_ids );
        return $shop_ids;
    }

   function search( &$_rows , $opt="", $download = 0 )
   {
      global $connect, $start_date, $end_date, $shop_id, $sub;
      global $page, $seq, $pack, $order_id, $recv_name, $recv_mobile, $product_id, $product_name, $options,$status, $order_cs, $recv_address,$trans_no, $priority, $enable_sale, $_date, $warehouse, $order_name;

      $today= date('Ymd', strtotime("now"));

      $start_date = $start_date ? $start_date : $today;
      $end_date = $end_date ? $end_date : $today;

      $line_per_page = _line_per_page;
      $page = $_REQUEST["page"];
      $today= date('Ymd', strtotime("now"));

      if ( !$page ) $page = 1;
      $start = ( $page - 1 ) * 20;

      ////////////////////////////////////////////////////////////////
      $query_cnt = "select count(*) as cnt, sum(orders.qty) tot_qty ";
      $query = "select orders.*, date_format( trans_date, '%Y-%m-%d') trans_date, products.name, products.options opt ";

      ////////////////////////////////////////////////////////////////
      $option = " from orders , products 
                 where orders.product_id = products.product_id
                   and $_date >= '$start_date 00:00:00'
                   and $_date <= '$end_date 23:59:59'";

      ///////////////////////////////////////////////////////
      // warehouse: 1 => 반포의 경우
      if ( $warehouse )
      {

      }

      if ( $seq )
         $option .= " and orders.seq = '$seq' ";

      if ( $pack )
         $option .= " and orders.pack = '$pack' ";

//echo $option;

      if ( $sub )
         $option .= " and orders.order_subid >= '$sub'";
        
      if ( !$enable_sale )
         $option .= " and products.enable_sale = '$enable_sale'";

      if ( $priority )
         $option .= " and orders.priority = '$priority'";

      if ( $order_id )
         $option .= " and orders.order_id = '$order_id'";

      if ( $order_name )
         $option .= " and orders.order_name = '$order_name'";

      if ( $recv_name )
         $option .= " and orders.recv_name = '$recv_name'";

      if ( $recv_mobile )
         $option .= " and orders.recv_mobile = '$recv_mobile'";

      if ( $shop_id)
         $option .= " and orders.shop_id = $shop_id ";

      if ( $trans_no )
         $option .= " and orders.trans_no = $trans_no";

      if ( $recv_address )
      {
               //$recv_address = str_replace (" ", "%", $recv_address );
         $option .= " and orders.recv_address like '%$recv_address%'";
      }

      if ( $product_id)
         $option .= " and products.product_id = '$product_id'";

      if ( $product_name )
         $option .= " and products.name like '%$product_name%'";

      if ( $options )
      {
              // $options = str_replace (" ", "%", $options );
         $option .= " and products.options like '%$options%'";
      }

      if ( $status )
         if ( $status == 99 )
            $option .= " and orders.status not in ( 1,7,8 )";
         else if ( $status == 1)        // 접수
            $option .= " and orders.status in (1,2) ";
         else if ( $status == 98 )
            $option .= " and orders.hold > 0";
         else
            $option .= " and orders.status = '$status'";

      if ( $order_cs )
      {
         switch ( $order_cs )
         {
            case "91": // 정상
               $option .= " and orders.order_cs = 0";
               break;
            case "92": // 취소
               $option .= " and orders.order_cs in ( 1,12,3,4,2 )";
               break;
            case "93": // 교환
               $option .= " and orders.order_cs in ( 11,5,7,13,6,8 )";
               break;
            default:
               $option .= " and orders.order_cs = $order_cs";
         }
      }


      $option .= " order by $_date desc";

      if ( !$download )
        $limit = " limit $start, $line_per_page";

      ////////////////////////////////////////////////////////////////
      // total count
      $result     = mysql_query ( $query_cnt . $option, $connect);
      $data       = mysql_fetch_array ( $result );
      $_rows[total_rows] = $data[cnt];
      $_rows[total_qty]  = $data[tot_qty];

      $result     = mysql_query ( $query . $option . $limit, $connect );

//if ( $_SESSION[LOGIN_LEVEL] == 9 )
    print $query_cnt . $option;
//exit;

      /////////////////////////////////////////////////////////////// 
      return $result;
   } 

   // jk 2008.7.21
   function _getFormatArray($params = NULL) { 
    $temp = array('font'   => $this->font, 
                  'size'   => $this->size, 
                  'bold'   => $this->bold, 
                  'align'  => $this->align, 
                  'valign' => $this->valign); 
    if(isset($params)) { 
      foreach($params as $key => $value) { 
        $temp[$key] = $value; 
      } 
    } 
    return $temp; 
  } 


    function save_file()
    {
        global $connect, $saveTarget, $filename, $search_date, $code_all, $stock_cnt, $start_date, $end_date, $except_after_change, $delete_order;

        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();

        // download format에 대한 정보를 가져온다
        
        
		// elkara 항목추가 하지 말것! //2014-05-28 김다은 요청
		// box4u  항목추가 X!
		//if( _DOMAIN_ != 'elkara' ||  _DOMAIN_ != 'box4u')
		$download_items["seq"               ]  = "관리번호";
		$download_items["pack"              ]  = "합포번호";
		$download_items["shop_name"         ]  = "판매처";
		$download_items["order_id"          ]  = "주문번호";
		$download_items["status"            ]  = "배송상태";
		$download_items["order_cs"          ]  = "CS상태";
		$download_items["order_create_type" ]  = "주문타입";
		$download_items["enable_sale"       ]  = "품절";
		$download_items["gift"              ]  = "사은품";
		$download_items["hold"              ]  = "보류여부";
		$download_items["trans_who"         ]  = "선착불";
		$download_items["org_trans_who"     ]  = "원본선착불";
		$download_items["product_id"        ]  = "상품아이디";
		$download_items["barcode"           ]  = "바코드";
		$download_items["supply_name"       ]  = "공급처";
if( _DOMAIN_ != 'elkara' )
		$download_items["maker"       		]  = "제조사";
		$download_items["shop_product_id"   ]  = "업체상품코드";
		$download_items["product_name"      ]  = "상품명";
		$download_items["options"           ]  = "선택사항";
		$download_items["real_product_name" ]  = "실제 상품명";
		$download_items["real_options"      ]  = "실제 옵션";
if( _DOMAIN_ != 'elkara' )
		$download_items["category" 		    ]  = "카테고리";
		$download_items["order_qty"         ]  = "실제개수";
		$download_items["qty"               ]  = "판매개수";
		$download_items["amount"            ]  = "총판매금액";
		$download_items["supply_price"      ]  = "정산예정금액";
		$download_items["commission"        ]  = "수수료";
		$download_items["org_price"         ]  = "원가"; 
		$download_items["total_price"       ]  = "총원가"; 
		$download_items["sum_org_price"     ]  = "sum 원가"; 
		$download_items["prd_supply_price"  ]  = "공급가"; 
		$download_items["sum_supply_price"  ]  = "총공급가"; 
		$download_items["trans_price"       ]  = "추가결제금액";
		$download_items["order_date"        ]  = "주문일";
		$download_items["order_time"        ]  = "주문시간";
		$download_items["cancel_date"       ]  = "취소일";
		$download_items["collect_date"      ]  = "발주일";
		$download_items["collect_time"      ]  = "발주시간";
		$download_items["trans_date"        ]  = "송장입력일";
		$download_items["trans_no"          ]  = "송장번호";
		$download_items["trans_corp"        ]  = "택배사";
if( _DOMAIN_ == 'soimall' )
        $download_items["is_island"]  = "도서지역";
		$download_items["recv_zip"          ]  = "배송지우편번호";
		$download_items["recv_address"      ]  = "배송지주소";
		$download_items["trans_date_pos"    ]  = "배송일";
		$download_items["trans_worker"      ]  = "배송작업자";
		$download_items["priority"          ]  = "우선순위";        
		$download_items["order_name"        ]  = "주문자";        
		$download_items["order_tel"         ]  = "주문자전화";        
		$download_items["order_mobile"      ]  = "주문자전화2";
		$download_items["recv_name"         ]  = "수령자";    
		$download_items["recv_tel"          ]  = "수령자전화";       
		$download_items["recv_mobile"       ]  = "수령자전화2";
		$download_items["memo"              ]  = "메모";
		$download_items["brand"             ]  = "공급처상품명";
		$download_items["supply_options"    ]  = "공급처옵션";
		$download_items["trans_fee"         ]  = "배송비";
		$download_items["location"          ]  = "로케이션";

if( _DOMAIN_ == 'mediheim' )
{
        $download_items["weight"]  = "영업점수";
        $download_items["ad_fee"]  = "광고비";
}
else
        $download_items["weight"]  = "중량";
            
        
        
        $download_items["code1"]  = "코드1";
        $download_items["code2"]  = "코드2";

if( $code_all == 1 )
{
        $download_items["code3"]  = "코드3"      ;
        $download_items["code4"]  = "코드4"      ;
        $download_items["code5"]  = "코드5"      ;
        $download_items["code6"]  = "코드6"      ;
        $download_items["code7"]  = "코드7"      ;
        $download_items["code8"]  = "코드8"      ;
        $download_items["code9"]  = "코드9"      ;
        $download_items["code10"] = "코드10"     ;
        $download_items["code21"] = "코드11"     ;
        $download_items["code22"] = "코드12"     ;
        $download_items["code23"] = "코드13"     ;
        $download_items["code24"] = "코드14"     ;
        $download_items["code25"] = "코드15"     ;
        $download_items["code26"] = "코드16"     ;
        $download_items["code27"] = "코드17"     ;
        $download_items["code28"] = "코드18"     ;
        $download_items["code29"] = "코드19"     ;
        $download_items["code30"] = "코드20"     ;
        $download_items["code11"] = "정산코드1"  ;
        $download_items["code12"] = "정산코드2"  ;
        $download_items["code13"] = "정산코드3"  ;
        $download_items["code14"] = "정산코드4"  ;
        $download_items["code15"] = "정산코드5"  ;
        $download_items["code16"] = "정산코드6"  ;
        $download_items["code17"] = "정산코드7"  ;
        $download_items["code18"] = "정산코드8"  ;
        $download_items["code19"] = "정산코드9"  ;
        $download_items["code20"] = "정산코드10" ;
        $download_items["code31"] = "정산코드11" ;
        $download_items["code32"] = "정산코드12" ;
        $download_items["code33"] = "정산코드13" ;
        $download_items["code34"] = "정산코드14" ;
        $download_items["code35"] = "정산코드15" ;
        $download_items["code36"] = "정산코드16" ;
        $download_items["code37"] = "정산코드17" ;
        $download_items["code38"] = "정산코드18" ;
        $download_items["code39"] = "정산코드19" ;
        $download_items["code40"] = "정산코드20" ;
}
        
if( $stock_cnt )
        $download_items["stock"] = "재고" ;

        // 원산지
        $download_items["origin"]  = "원산지";
        // 결제수단
        $download_items["pay_type"]  = "결제수단";
        // 주문상세번호
        $download_items["order_id_seq"]  = "주문상세번호";
        // 구매자아이디
        $download_items["cust_id"]  = "구매자ID";
        // 선결제배송비
        $download_items["prepay_price"]  = "선결제배송비";
        
        
        
//확장주문검색 다운로드 항목추가 이곳에 하기.. 
if( _DOMAIN_ != 'elkara' ||  _DOMAIN_ != 'box4u') 
		$download_items["cs_reason"          ]  = "취소교환사유";





        if( _DOMAIN_ == 'goldplate' && $_SESSION[LOGIN_ID] == '트랜소닉' )
        {
//판매처,          ,수령자 관리번호,어드민상품명,   ,판매개수        배송상태,                   송장번호,배송일,택배사,주소,수령자전화,수령자전화2            
            $download_items = array(
                "seq"                 => "관리번호",
                "pack"                => "합포번호",
                "shop_name"           => "판매처",
                "status"              => "배송상태",
                "order_cs"            => "CS상태",
                "product_name"        => "상품명",
                "options"             => "선택사항",
                "qty"                 => "판매개수",
                "trans_date"          => "송장입력일",
                "trans_no"            => "송장번호",
                "trans_corp"          => "택배사",
                "recv_zip"            => "배송지우편번호",
                "recv_address"        => "배송지주소",
                "trans_date_pos"      => "배송일",
                "recv_name"           => "수령자",        
                "recv_tel"            => "수령자전화",        
                "recv_mobile"         => "수령자전화2"
            );
        }


        $_rows     = "";
        $_products = "";
        $download  = 1;
        $result    = $this->search2( &$_rows, &$_products , &$total_trans, $download ); 

        $arr = array();
        // header
        $_row = array();
        foreach( $download_items as $key=>$value ) 
        {
            $_row[$key] = $value;
        }
        



        $saveFile = "download-".time().".xls";
	    $saveTarget = _upload_dir . $saveFile; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
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
<html><table border=1>
";
        fwrite($handle, $buffer);

        $buffer = "<tr>\n";
        // for column
        foreach ( $_row as $key=>$value) 
            $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);





        $arr[] = $_row;
        
        // data
        $i = 0;
        $old_seq = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // 동일주문확인
            if( $old_seq == $data[seq] )
                $same_order = true;
            else
            {
                $same_order = false;
                
                // 사은품일 경우는 제외
                if( !$data[is_gift] || $_SESSION[GIFT_PRICE_0] )
                    $old_seq = $data[seq];
            }
            
            $_row = array();
            $product_info = class_product::get_info( $data[product_id] );
            foreach( $download_items as $key=>$value ) 
            {
                // 같은 주문이고, 필드가 amount, supply_price 일 경우는 0
                if( $same_order && ($key == "amount" || $key == "supply_price" || $key == "commission") && _DOMAIN_ != "changsin2"  && _DOMAIN_ != "changsin" )
                    $_row[$key] = 0;

                else if( ( $data[order_cs] == 1 ||
                           $data[order_cs] == 2 ||
                           $data[order_cs] == 3 ||
                           $data[order_cs] == 4 ) && _DOMAIN_ == "ellse1205"  &&
                         ( $key == "amount"       ||
                           $key == "supply_price" ||
                           $key == "commission"   ||
                           $key == "org_price"    ||
                           $key == "total_price"  ||
                           $key == "sum_org_price" ||
                           $key == "prd_supply_price" ||
                           $key == "sum_supply_price" ) )  
                    $_row[$key] = 0;
                else
                    $_row[$key] = $this->get_data( $data, $key,$product_info );
            }
            
            
            $buffer = "<tr>\n";

            // for column
            foreach ( $_row as $key=>$value) 
            {
            	//박스포유는 배송일/취소일에 시간 제외시킴
            	if(($key == "trans_date_pos" || $key == "cancel_date" )&& _DOMAIN_ =="box4u")            	
            		$value = substr($value,0,10);
            	
	        	if($key == "trans_fee" && _DOMAIN_ =="changsin")
	        	{
					if($data[pack]>0)
			        {
			        	$__query = "	
			        	  SELECT	max(c.trans_fee) max_trans_price
							FROM	orders a
							   ,	order_products b
							   ,	products c
						   WHERE	a.seq = b.order_seq
						  	 AND	b.product_id = c.product_id
						     AND	a.pack = $data[pack]
						GROUP BY	a.pack
																			";
			        }
			        else 
			        {
			        	$__query = "	
			        	  SELECT	c.trans_fee max_trans_price
							FROM	orders a
							   ,	order_products b
							   ,	products c
						   WHERE	a.seq = b.order_seq
						  	 AND	b.product_id = c.product_id
						     AND	a.seq = $data[seq]
																			";         	
			        }
			        
			        $__result = mysql_query( $__query, $connect );
			        $__data   = mysql_fetch_assoc( $__result );
			        $value = $__data[max_trans_price];
	        	}
	        	if($key =="is_island")
	        	{
	        		$zip_num = (int)preg_replace('/[^0-9]/','',$data[recv_zip]);
					$value = ""	;
					if(in_array($zip_num, array(409840,409841,409841,409842,409842,409842,409850,409851,409851,409851,409851,409851,409851,409852,409852,409853,409880,409881,409882,409883,409890,409891,409891,409891,409891,409891,409892,409892,409893,409893,409910,409910,409911,409911,409911,409911,409911,409912,409912,409913,409919,409919,409919,409919,409919,417910,417911,417911,417912,417912,417913,417913,417913,417920,417921,417921,417921,417921,417922,417922,417922,417922,417922,417923,417923,417923,417923,417930,417931,417931,417932,417933,513890,513891,513891,513891,513891,513892,513892,513892,513892,513892,513892,513892,513893,530145,530430,530440,530600,530800,530800,530801,530801,535805,535806,535820,535821,535821,535822,535822,535823,535823,535824,535824,535830,535831,535832,535833,535834,535835,535836,535837,535838,535840,535841,535841,535841,535841,535841,535841,535841,535841,535841,535841,535842,535842,535842,535842,535842,535842,535842,535842,535843,535843,535843,535843,535843,535843,535844,535844,535844,535845,535845,535846,535847,535850,535851,535851,535851,535851,535852,535852,535852,535860,535861,535861,535861,535861,535861,535862,535862,535862,535862,535863,535870,535871,535871,535871,535871,535872,535872,535872,535872,535873,535873,535873,535873,535880,535881,535881,535881,535881,535882,535882,535882,535883,535883,535884,535884,535884,535885,535890,535891,535891,535891,535891,535892,535892,535892,535893,535893,535893,535894,535894,535894,535895,535895,535895,535896,535896,535897,535897,535898,535910,535911,535912,535912,535912,535912,535912,535912,535913,535913,535913,535913,535914,535914,535914,535915,535916,535917,535917,535918,535919,535920,535921,535921,535922,535922,535923,535924,535924,535925,535925,535926,535926,535930,535931,535931,535932,535932,535933,535934,535934,535935,535935,535936,535936,535936,535940,535941,535942,535942,535943,535943,536928,537809,537818,537820,537821,537821,537821,537822,537822,537823,537823,537823,537823,537823,537823,537824,537824,537824,537825,537826,537830,537831,537831,537831,537831,537832,537833,537834,537834,537834,537834,537834,537834,537835,537835,537835,537835,537835,537836,537840,537841,537841,537841,537841,537841,537841,537841,537841,537842,537842,537842,537842,537842,537842,537843,537843,537843,537844,537845,537845,537845,537845,537845,537845,537845,537845,537845,537846,537847,537848,537848,537849,537849,537849,537850,537851,537851,537851,537851,537851,537851,537851,537852,537852,537852,537852,537852,537852,537852,537853,537900,537900,537901,537901,537901,537902,537902,537902,537902,537902,537902,537903,537903,537903,537903,537903,537903,537904,537904,537904,537904,537905,537907,537909,537909,537920,537921,537921,537921,537922,537922,537922,537922,539910,539911,539911,539912,539912,539912,539912,539912,539913,539913,539914,539914,539914,539914,539914,539915,539915,539915,539915,539915,539916,539916,539916,539916,539916,539917,539917,539917,539918,539918,539918,539919,539919,539919,539919,539919,539919,546908,548840,548902,548909,548930,548931,548931,548932,548932,548933,548934,548936,548941,548993,550270,555300,556830,556831,556832,556833,556834,556834,556835,556836,556837,556838,556839,556840,556841,556842,556843,556843,556844,556846,556847,556848,556849,556849,556850,556851,556852,556852,556853,556854,556855,573810,573811,573811,573812,573813,573813,573813,573814,573815,573815,573816,573816,573817,573818,573818,573819,579910,579911,579911,579912,579913,579914,579914,579915,618410,618420,618430,618440,618450,618821,645420,650833,650835,650910,650911,650912,650913,650914,650915,650916,650920,650925,650930,650931,650932,650932,650933,650934,650934,650941,650943,650943,650944,650945,650946,650947,656861,656861,656861,656861,656861,656861,656861,656861,656861,664260,664270,690003,690011,690012,690021,690022,690029,690031,690032,690032,690041,690042,690043,690050,690061,690062,690071,690072,690073,690081,690082,690090,690100,690110,690121,690122,690130,690140,690150,690161,690162,690163,690170,690180,690191,690192,690200,690210,690220,690231,690232,690241,690242,690600,690610,690700,690701,690703,690704,690705,690706,690707,690708,690709,690710,690711,690712,690713,690714,690715,690717,690718,690719,690720,690721,690722,690723,690724,690725,690726,690727,690728,690729,690730,690731,690732,690734,690735,690736,690737,690738,690739,690740,690741,690742,690743,690744,690747,690750,690751,690751,690755,690756,690760,690762,690764,690764,690765,690766,690767,690769,690770,690771,690772,690773,690774,690775,690776,690777,690778,690779,690780,690781,690782,690783,690784,690785,690786,690787,690788,690789,690790,690796,690800,690800,690800,690801,690801,690801,690802,690803,690804,690805,690806,690806,690807,690808,690808,690808,690808,690809,690809,690809,690809,690810,690810,690811,690811,690812,690812,690813,690813,690814,690814,690815,690816,690817,690817,690818,690819,690819,690820,690820,690821,690821,690821,690822,690822,690822,690822,690823,690823,690823,690824,690825,690825,690826,690827,690827,690828,690828,690828,690828,690829,690829,690830,690830,690830,690831,690832,690832,690832,690833,690833,690833,690834,690835,690835,690835,690836,690836,690837,690838,690838,690839,690839,690840,690841,690842,690843,690844,690846,690846,690846,690847,690850,690850,690851,695705,695789,695791,695792,695793,695794,695795,695796,695900,695901,695902,695903,695903,695904,695904,695904,695905,695905,695906,695907,695908,695909,695910,695911,695912,695913,695914,695915,695916,695917,695918,695918,695919,695920,695921,695921,695922,695923,695924,695925,695926,695927,695928,695929,695930,695931,695931,695932,695933,695934,695940,695941,695942,695942,695942,695943,695944,695945,695945,695946,695947,695948,695949,695950,695951,695951,695952,695952,695952,695960,695961,695962,695962,695963,695964,695965,695966,695967,695968,695969,695970,695971,695972,695973,695974,695975,695975,695976,695976,695977,695978,695979,695980,695981,695982,695983,695983,695983,697010,697011,697012,697013,697014,697020,697030,697040,697050,697060,697070,697080,697090,697100,697110,697120,697130,697301,697310,697320,697330,697340,697350,697360,697370,697370,697380,697390,697600,697700,697701,697703,697704,697704,697705,697705,697706,697707,697805,697806,697807,697808,697819,697820,697821,697822,697823,697824,697825,697826,697827,697828,697829,697830,697831,697832,697833,697834,697835,697836,697837,697838,697838,697839,697840,697840,697841,697841,697841,697842,697842,697842,697842,697843,697843,697844,697845,697846,697847,697848,697849,697850,697851,697852,697853,697854,697855,697855,697856,697856,697857,697857,697858,697859,697859,697860,697860,697861,697862,697862,697863,697863,697864,699701,699701,699702,699900,699901,699902,699902,699903,699903,699904,699904,699905,699905,699906,699907,699908,699910,699911,699911,699912,699913,699914,699915,699916,699920,699921,699921,699921,699921,699922,699922,699923,699923,699924,699925,699925,699926,699930,699931,699931,699931,699931,699931,699932,699932,699932,699933,699933,699934,699935,699936,699937,699937,699940,699941,699942,699943,699944,699945,699946,699947,699948,699949,799800,799800,799801,799801,799801,799802,799803,799804,799810,799811,799811,799811,799811,799812,799813,799820,799821,799821,799821,799821,799821,799822,799822,799822,799823)))
					{
						$value = "도서지역"	; 
					}
				} 
				if($key =="cs_reason")
	        	{
	        		if($data[order_cs] > 0)
	        		{
	        			switch($data[order_cs])
	        			{
	        				case 1:
	        				case 2:
	        				case 3:
	        				case 4:
		        				$cs_type = "10, 16 ";
		        				break;
	        				case 5:
	        				case 6:
	        				case 7:
	        				case 8:
		        				$cs_type = 17;
		        				break;
	        			}
	        			
		        		$__query = "SELECT cs_reason FROM csinfo WHERE order_seq = $data[seq] AND cs_type IN ($cs_type) ORDER BY seq DESC LIMIT 1";
		        		$__result = mysql_query( $__query, $connect );
				        $__data   = mysql_fetch_assoc( $__result );
	 			        $value = $__data[cs_reason];
	 			    }
	        	}
            	
                if( $key == "stock"        ||
                    $key == "qty"          ||
                    $key == "amount"       ||
                    $key == "supply_price" ||
                    $key == "commission"   ||
                    $key == "org_price"    ||
                    $key == "total_price"  ||
                    $key == "sum_org_price" ||
                    $key == "prd_supply_price" ||
                    $key == "sum_supply_price" ||
                    $key == "trans_price"  ||
                    $key == "trans_fee"    ||
                    $key == "prepay_price" ||
                    $key == "code11"       ||
                    $key == "code12"       ||
                    $key == "code13"       ||
                    $key == "code14"       ||
                    $key == "code15"       ||
                    $key == "code16"       ||
                    $key == "code17"       ||
                    $key == "code18"       ||
                    $key == "code19"       ||
                    $key == "code20"       ||
                    $key == "code31"       ||
                    $key == "code32"       ||
                    $key == "code33"       ||
                    $key == "code34"       ||
                    $key == "code35"       ||
                    $key == "code36"       ||
                    $key == "code37"       ||
                    $key == "code38"       ||
                    $key == "code39"       ||
                    $key == "code40" )
                    $buffer .= "<td class=num_item>" . $value . "</td>";
                else
                    $buffer .= "<td class=str_item>" . $value . "</td>";
            }
            
            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
            
            $i++;
            if( $i % 73 == 0 )
            {
                $msg = " $i / $_products ";
                echo "<script language='javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
            
            usleep(1000);
        }     
        
        echo "<script language='javascript'>parent.set_file('$saveFile')</script>";

        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "확장주문검색다운로드[$start_date ~ $end_date]");

    } 

    //////////////////////////////////////
    // 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "order_list.xls");
    }    

   function get_data ( $data, $key, $info='')
   {
      global $connect;

      $arr_chars     = array("`","/","=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'","<br>" );
      $_option       = "";

      // 사은품 가격 0
      if( $data['is_gift'] && !$_SESSION[GIFT_PRICE_0] )
        $gift_price = 1;
      else
        $gift_price = 0;

      switch ( $key )
      {
          case "order_cs":
              return $this->get_order_cs2( $data['order_cs'] );
              break;
          case "order_create_type":
              if( $data[c_seq] > 0 )
                $create_str = "배송후교환";
              else if( $data[copy_seq] > 0 )
                $create_str = "주문복사";
              else if( $data[seq] == $data[order_id] )
                $create_str = "주문생성";
              else
                $create_str = "발주";
              return $create_str;
              break;
          case "trans_worker":
              if( _DOMAIN_ == 'beginning' || _DOMAIN_ == 'changsin' )
              {
                $query_w = "select owner from stock_tx_history where order_seq=$data[seq] and job='trans' order by seq desc limit 1";
                $result_w = mysql_query($query_w, $connect);
                $data_w = mysql_fetch_assoc($result_w);
                $str = $data_w[owner];
              }
              else
                $str = "";
              return $str;
              break;
          case "status":
              return $this->get_order_status2( $data['status'] );
              break;
          case "shop_name":
              return class_D::get_shop_name($data[shop_id]);
              break;
          case "barcode":
              return $info['barcode'];
              break;
          case "supply_name":
              return $this->get_supply_name2($data[supply_id]);
              break;
          case "maker":
              return $info['maker'];
              break;
          case "real_product_name":
              return $data[product_name];
              break;
          case "real_options":
              if( $_SESSION[STOCK_MANAGE_USE] == 1 && $data[shop_id] % 100 != 1 && $data[shop_id] % 100 != 2)
                return $data[options];
              else
                return $data[shop_options];
              break;
          case "product_name":
              return $info['name'];
              break;
          case "options":
              return $info['options'];
              break;
          case "weight":
              return $info['weight'];
              break;
          case "brand":
              return $info['brand'];
              break;
          case "supply_options":
              return $info['supply_options'];
              break;
          case "enable_sale":
              return $info['enable_sale']?"":"품절";
              break;
          case "is_island":
              return $info['is_island']?"":"도서지역";
              break;
          case "gift":
              return $data['is_gift']?"사은품":"";
              break;
          case "hold":
              return ( $data['hold'] ? "보류" : "" );
              break;
          case "amount":
              if( _DOMAIN_ == 'changsin2' ||  _DOMAIN_ == "changsin"  )
                  return $gift_price ? 0 : ($data['b_prd_amount'] + $data['b_extra_money']);
              else if( _DOMAIN_ == 'luxnholic' )
                  return $gift_price ? 0 : ($data['amount'] + $data['b_extra_money']);
              else
                  return $gift_price ? 0 : ($data['amount'] + $data['extra_money']);
              break;          
          case "supply_price":
              if( _DOMAIN_ == 'changsin2' ||  _DOMAIN_ == "changsin"  )
                  return $gift_price ? 0 : ($data['b_prd_supply_price'] + $data['b_extra_money']);
              else if( _DOMAIN_ == 'luxnholic')
                  return $gift_price ? 0 : ($data['supply_price'] + $data['b_extra_money']);
              else
                  return $gift_price ? 0 : ($data['supply_price'] + $data['extra_money']);
              break;          
          case "commission":
              if( _DOMAIN_ == 'changsin2' ||  _DOMAIN_ == "changsin"  )
                  return $gift_price ? 0 : ($data['b_prd_amount'] - $data['prd_supply_price']);
              else
                  return $gift_price ? 0 : ($data['amount'] - $data['supply_price']);
              break;          
          case "org_price":
              return $gift_price ? 0 : $info['org_price'];
              break;          
          case "total_price":
              return $gift_price ? 0 : $data['qty'] * $info['org_price'];
              break;
          case "prd_supply_price":
              return $gift_price ? 0 : $data['prd_supply_price'];
              break;          
          case "sum_supply_price":
              return $gift_price ? 0 : $data['qty'] * $data['prd_supply_price'];
              break;          
          case "ad_fee":
              return "";
              break;
          case "trans_corp":
              return class_E::get_trans_name( $data[trans_corp] );
              break;
          case "category":
              return $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data[str_category]) : class_category::get_category_name( $data[category] );
              break;
          case "cancel_date":
              if( $data[order_cs] == 1 || $data[order_cs] == 2 || $data[order_cs] == 3 || $data[order_cs] == 4 )
                  $val = $data[$key] ? $data[$key] : "";
              else
                  $val = "";
              return $val;
              break;          
          default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("=","\r", "\n", "\r\n","\t" ), " ", $val );
              break; 
      }
   }

   function get_cs_reason( $seq )
   {
        global $connect;
        $query = "select cs_reason from csinfo where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $_str = "";
        while ( $data = mysql_fetch_array( $result ) )
         {
            if ( $data[cs_reason] )
                    $_str = $data[cs_reason];
        }
        return $_str;
   }
}
