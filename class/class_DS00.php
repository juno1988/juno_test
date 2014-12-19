<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_stock.php";
require_once "class_ui.php";
require_once "class_supply.php";
require_once "class_shop.php";
require_once "class_category.php";
require_once "class_multicategory.php";
require_once "class_table.php";
require_once "Classes/PHPExcel.php";
include "class_C.php";

class class_DS00 extends class_top
{
    //###############################
    // 메인 화면
    //###############################
    function DS00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order, $page;
		global $cur_connect;
		
		global $search ,$date_type, $work_type ,$start_date ,$end_date ,$option , $panel_open,
			   $query_str ,$status ,$group_id ,$shop_id ,$cs, $select_field, $download_field, $download_type ;
		global $multi_supply_group, $multi_supply, $str_supply_code;
	
		global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $str_category, $category;
		global $status_sel, $order_cs_sel, $special_option, $c_cs ;
		global $panel_option, $field_change, $recover_delete ,$delay_long;
		
		global $line_per_page;
		
		if(!$page)
			$page = 1;
			
		$line_per_page = 50;
		
	    if(!$select_field )
			$select_field = "DS00";
		
		if(!$panel_open )
			$panel_open = "false";
		
		if(!$search )
			$search	 = "0";	
			
		//상세검색 패널이 접혀있으면, 파라메터를 ""으로 초기화.
		$this->DS00_detail_option("start");
		
		if( $search < 1)
        {
            // 초기 검색 조건
            $page_code = 'DS00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
            
			// 발주기간
            if( $f_start_date == 0 )
                $start_date = date("Y-m-d", strtotime("-3 month"));
            else if( $f_start_date == 1 )
                $start_date = date("Y-m-d", strtotime("-2 month"));
            else if( $f_start_date == 2 )
                $start_date = date("Y-m-d", strtotime("-1 month"));
            else if( $f_start_date == 3 )
                $start_date = date("Y-m-d", strtotime("-1 week"));
            else if( $f_start_date == 4 )                
                $start_date = date("Y-m-d");

			switch($f_date_type)
			{
				case 0:
				$date_type = "collect_date"	;
				break;	
				case 1:
				$date_type = "order_date";
				break;
				case 2:
				$date_type = "trans_date";
				break;
				case 3:
				$date_type = "trans_date_pos";
				break;
				case 4:
				$date_type = "cancel_date";
				break;
				case 5:
				$date_type = "change_date";
				break;
				case 6:
				$date_type = "cs_date";
				break;
				case 7:
				$date_type = "sale_stop_date";
				break;
			}
			$status_sel = $f_status_sel;
			$order_cs_sel = $f_order_cs_sel;
			
			if($f_detail_panel)
				$panel_open = "true";

			if($field_change < 1)
			{
				switch($f_select_field_sel)
				{
					case 0:
					$select_field = "DS00"	;
					break;	
					case 1:
					$select_field = "DS00_1";
					break;
					case 2:
					$select_field = "DS00_2";
					break;
					case 3:
					$select_field = "DS00_3";
					break;
					case 4:
					$select_field = "DS00_4";
					break;
					case 5:
					$select_field = "DS00_5";
					break;
					case 6:
					$select_field = "DS00_6";
					break;
				}
			}
			switch($f_download_field_sel)
			{
				case 0:
				$download_field  = "DS00_file";
				break;	
				case 1:
				$download_field = "DS00_file_1";
				break;
				case 2:
				$download_field = "DS00_file_2";
				break;
				case 3:
				$download_field = "DS00_file_3";
				break;
				case 4:
				$download_field = "DS00_file_4";
				break;
				case 5:
				$download_field = "DS00_file_5";
				break;
				case 6:
				$download_field = "DS00_file_6";
				break;
			}
        }
        //배송지연 클릭시 이동은 조회페이지 setting을 읽는다
        if($delay_long)
        {
        	// 초기 검색 조건
            $page_code = 'DS00_search';
            $f_search = class_table::get_setting();
            
            foreach($f_search as $f_val)
            {
                $f_var = "f_$f_val[field_id]";
                $$f_var = $f_val[field_name];
            }
            
			switch($f_select_field_sel)
			{
				case 0:
				$select_field = "DS00"	;
				break;	
				case 1:
				$select_field = "DS00_1";
				break;
				case 2:
				$select_field = "DS00_2";
				break;
				case 3:
				$select_field = "DS00_3";
				break;
				case 4:
				$select_field = "DS00_4";
				break;
				case 5:
				$select_field = "DS00_5";
				break;
				case 6:
				$select_field = "DS00_6";
				break;
			}
			switch($f_download_field_sel)
			{
				case 0:
				$download_field  = "DS00_file";
				break;	
				case 1:
				$download_field = "DS00_file_1";
				break;
				case 2:
				$download_field = "DS00_file_2";
				break;
				case 3:
				$download_field = "DS00_file_3";
				break;
				case 4:
				$download_field = "DS00_file_4";
				break;
				case 5:
				$download_field = "DS00_file_5";
				break;
				case 6:
				$download_field = "DS00_file_6";
				break;
			}
        	
        }
        
        
		
        
        // 조회 필드
        $connect = $cur_connect;  //조회필드는 현DB
        $page_code = $select_field;
        $f = class_table::get_setting();
        
        if( $_REQUEST[bck_search] )
	    	$connect = bck_db_connect(); //백업은 백업 conn
        if( $search )
        {
            // 전체 쿼리
            $data_all = $this->get_DS00($f, &$total_rows, &$sum_arr, &$total_trans, &$total_order , &$total_product, &$total_data_rows);

            // 정렬방향
            if( $sort )
                $sort_order = ($sort_order ? 0 : 1);
        }
        
        // 삭제주문 복구
		if( $recover_delete )
		{	
			$data_all = $this->get_DS00($f, &$total_rows, &$sum_arr, &$total_trans, &$total_order , &$total_product, &$total_data_rows);
			
			$seq_arr = array();
			$pack_arr = array();
			foreach($data_all as $key => $val)
			{
				$seq_arr[] = $val[org_seq];
				if($val[pack])
					$pack_arr[] = $val[pack];
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
		}
        
        
		//상세검색 패널이 접혀있으면, 파라메터를 원복시킴.
		$connect = $cur_connect;
		$this->DS00_detail_option("end");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function DS00_detail_option($type="")
    {
    	//상세검색 패널이 접혀있다면. 세부검색조건 안먹게..
    	global $search ,$date_type, $work_type ,$start_date ,$end_date ,$option , $panel_open,
			   $query_str ,$status ,$group_id ,$shop_id ,$cs, $select_field , $download_field;
		global $multi_supply_group, $multi_supply, $str_supply_code;
	
		global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $str_category, $category;
		global $status_sel, $order_cs_sel, $special_option, $c_cs ;
		global $panel_option;
		
		if($panel_open != "false")
			return; //상세검색 페이지가 열려있다면 패스.
			
		if($type =="start")
		{
			foreach($option as $idx => $opt)
	        {
	        	if($idx > 0) // 0은 기본검색임..
	        	{
		        	$panel_option['option'][$idx] = $option[$idx];
		        	$panel_option['query_str'][$idx] = $query_str[$idx];
		        	
		        	$query_str[$idx] = "";
		        	$option[$idx] = "";
		        }
	        }
	        
	        foreach($cs as $idx => $opt)
	        {
	        	$panel_option['cs'][$idx] = $cs[$idx];
	        	unset($cs[$idx]);// = "";
	        }
	        
	        foreach($status as $idx => $opt)
	        {
	        	$panel_option['status'][$idx] = $status[$idx];
	        	unset($status[$idx]);//
	        }
	        
	        foreach($special_option as $idx => $opt)
	        {
	        	$panel_option['special_option'][$idx] = $special_option[$idx];
	        	unset($special_option[$idx]);
	        }
	        
	        $panel_option['c_cs'][0] = $c_cs;
	        
	        $panel_option['m_sub_category'][0] = $m_sub_category_1;
	        $panel_option['m_sub_category'][1] = $m_sub_category_2;
	        $panel_option['m_sub_category'][2] = $m_sub_category_3;
	        $panel_option['category'][0] = $category;
	        $m_sub_category_1 = "";
	        $m_sub_category_2 = "";
	        $m_sub_category_3 = "";
	        $category = "";
	        $c_cs = "";
		}
    	else
    	{
			foreach($option as $idx => $opt)
		    {
		    	if($idx > 0) // 0은 기본검색임..
	        	{	
			    	$query_str[$idx] = $panel_option['query_str'][$idx];
			    	$option[$idx] = $panel_option['option'][$idx];
		    	}
		    }
		    foreach($panel_option['cs'] as $idx => $opt)
	        {
	        	$cs[$idx] = $panel_option['cs'][$idx];
	        }
		    foreach($panel_option['status'] as $idx => $opt)
	        {
	        	$status[$idx] = $panel_option['status'][$idx];
	        }
	        foreach($panel_option['special_option'] as $idx => $opt)
	        {
	        	$special_option[$idx] = $panel_option['special_option'][$idx];
	        }
	        $c_cs = $panel_option['c_cs'][0];
	        
	        $m_sub_category_3 = $panel_option['m_sub_category'][0];
	        $m_sub_category_3 = $panel_option['m_sub_category'][1];
	        $m_sub_category_3 = $panel_option['m_sub_category'][2];
	        $category = $panel_option['category'][0];
		}	
    }
    //###############################
    // 메인 쿼리 - 불러오기
    //###############################
    function get_DS00($f, &$total_rows, &$sum_arr, &$total_trans, &$total_order , &$total_product, $total_data_rows,$is_download=0)
    {
        ini_set('memory_limit', '1000M');

        global $template, $connect, $page_code, $search, $sort, $sort_order, $page;
		global $search ,$date_type,$work_type ,$start_date ,$end_date ,$option , $panel_open,
			   $query_str ,$status ,$group_id ,$shop_id ,$cs, $select_field, $download_field, $download_type;
		global $multi_supply_group, $multi_supply, $str_supply_code;
		global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $str_category, $category;
		global $status_sel, $order_cs_sel, $special_option, $c_cs ;
		global $line_per_page;
        $this->show_wait($is_download);

		$is_del_order = 0;
        // 공급처 정보 배열
        $supply_info = class_supply::get_supply_arr();

		$query_trans_cnt   = "select count(distinct if(a.pack=0, a.seq, a.pack)) cnt";
		$query_order_cnt   = "select count(distinct a.seq) cnt, sum(a.qty) _sum ";
        $query_product_cnt = "select count(distinct b.seq) cnt, sum(b.qty) _sum ";
        
//,a.qty					 as orders_qty
        $query = "	select   a.seq					 as orders_seq
	        				,a.pack					 as orders_pack
							,a.order_id				 as orders_order_id						
							,a.order_id_seq			 as orders_order_id_seq
							,a.shop_id				 as orders_shop_id
							,a.shop_product_id		 as orders_shop_product_id
							,a.product_name			 as orders_product_name
							,a.options				 as orders_options							
							,ifnull(b.qty, a.qty) 	 as orders_qty
							,a.amount				 as orders_amount
							,a.supply_price			 as orders_supply_price
							,a.trans_price			 as orders_trans_price
							,a.trans_who			 as orders_trans_who
							,a.status				 as orders_status
							,a.order_cs				 as orders_order_cs
							,a.order_date			 as orders_order_date
							,a.order_time			 as orders_order_time
							,a.collect_date			 as orders_collect_date
							,a.trans_no				 as orders_trans_no
							,a.trans_corp			 as orders_trans_corp
							,a.trans_date			 as orders_trans_date
							,a.trans_date_pos		 as orders_trans_date_pos
							,date(a.trans_date_pos)  as orders_trans_date_pos_date_only
							,time(a.trans_date_pos)  as orders_trans_date_pos_time_only							
							,a.order_name			 as orders_order_name
							,a.order_tel			 as orders_order_tel
							,a.order_mobile			 as orders_order_mobile
							,a.order_email			 as orders_order_email
							,a.order_zip			 as orders_order_zip
							,a.order_address		 as orders_order_address
							,a.recv_name			 as orders_recv_name
							,a.recv_tel				 as orders_recv_tel
							,a.recv_mobile			 as orders_recv_mobile
							,a.recv_email			 as orders_recv_email
							,a.recv_zip				 as orders_recv_zip
							,a.recv_address			 as orders_recv_address
							,a.memo					 as orders_memo
							,a.code1				 as orders_code1
							,a.code2				 as orders_code2
							,a.code3				 as orders_code3
							,a.code4				 as orders_code4
							,a.code5				 as orders_code5
							,a.code6				 as orders_code6
							,a.code7				 as orders_code7
							,a.code8				 as orders_code8
							,a.code9				 as orders_code9
							,a.code10				 as orders_code10
							,a.gift					 as orders_gift
							,a.collect_time			 as orders_collect_time
							,a.hold					 as orders_hold
							,a.code11				 as orders_code11
							,a.code12				 as orders_code12
							,a.code13				 as orders_code13
							,a.code14				 as orders_code14
							,a.code15				 as orders_code15
							,a.code16				 as orders_code16
							,a.code17				 as orders_code17
							,a.code18				 as orders_code18
							,a.code19				 as orders_code19
							,a.code20				 as orders_code20
							,a.cross_change			 as orders_cross_change
							,a.prepay_price			 as orders_prepay_price
							,a.code21				 as orders_code21
							,a.code22				 as orders_code22
							,a.code23				 as orders_code23
							,a.code24				 as orders_code24
							,a.code25				 as orders_code25
							,a.code26				 as orders_code26
							,a.code27				 as orders_code27
							,a.code28				 as orders_code28
							,a.code29				 as orders_code29
							,a.code30				 as orders_code30
							,a.code31				 as orders_code31
							,a.code32				 as orders_code32
							,a.code33				 as orders_code33
							,a.code34				 as orders_code34
							,a.code35				 as orders_code35
							,a.code36				 as orders_code36
							,a.code37				 as orders_code37
							,a.code38				 as orders_code38
							,a.code39				 as orders_code39
							,a.code40				 as orders_code40
							,a.cust_id				 as orders_cust_id
							,a.pay_type				 as orders_pay_type
							,a.order_type2			 as orders_order_type2
							,a.order_id_seq2		 as orders_order_id_seq2
							,a.c_seq				 as orders_c_seq
							,a.copy_seq				 as orders_copy_seq
							,a.priority				 as orders_priority
							
											
							,c.product_id			 as products_product_id
							,c.barcode				 as products_barcode
							,c.maker				 as products_maker
							,c.supply_code			 as products_supply_code
							,c.reg_date				 as products_reg_date
							,c.reg_time				 as products_reg_time
							,c.last_update_date		 as products_last_update_date
							,c.name					 as products_name
							,c.options				 as products_options
							,c.brand				 as products_brand
							,c.supply_options		 as products_supply_option
							,c.img_500				 as products_img_500
							,c.img_desc1			 as products_img_desc1
							,c.img_desc2			 as products_img_desc2
							,c.img_desc3			 as products_img_desc3
							,c.img_desc4			 as products_img_desc4
							,c.enable_sale			 as products_enable_sale
							,c.memo					 as products_memo
							,c.str_category			 as products_str_category
							,c.trans_type			 as products_trans_type
							,c.location				 as products_location
							,c.weight				 as products_weight
							,c.origin				 as products_origin
							,c.supply_price			 as products_supply_price
											
							,b.product_id			 as order_products_product_id
							,b.qty					 as order_products_qty
							,b.order_cs				 as order_products_order_cs
							,b.cancel_date			 as order_products_cancel_date
							,date(b.cancel_date)	 as order_products_cancel_date_date_only
							,time(b.cancel_date)	 as order_products_cancel_date_time_only
							,b.change_date			 as order_products_change_date
							,b.refund_price			 as order_products_refund_price
							,b.match_date			 as order_products_match_date
							,b.extra_money			 as order_products_extra_money
							,b.is_gift				 as order_products_is_gift
							,b.org_price			 as order_products_org_price
							,b.prd_amount			 as order_products_prd_amount
							,b.prd_supply_price		 as order_products_prd_supply_price
							,b.match_type			 as order_products_match_type
							,b.match_worker			 as order_products_match_worker 
		
						";
        if ( $download_type == 2 )
            $query .= ", if(a.pack=0, a.seq, a.pack) seq_pack ";

        
        if ( $download_type > 0 )
        {
            $query .= ",sum(b.org_price) sum_org_price";
        }
        
    	$opt = " FROM orders a
                 LEFT OUTER JOIN order_products b ON a.seq = b.order_seq
                 LEFT OUTER JOIN products c on b.product_id = c.product_id "; 	
        foreach($special_option as $idx => $checked_option)
        {
        	if($checked_option == "del_order")
        	{
        		$is_del_order = 1;
        		$opt = " FROM orders_del a
                     LEFT OUTER JOIN order_products_del b ON a.seq = b.order_seq
                     LEFT OUTER JOIN products c on b.product_id = c.product_id ";
				break;
        	}
        }
        if($date_type == "cs_date" || $work_type)
        	$opt .=" ,csinfo d  ";
        
        if ( $date_type == "cancel_date" || $date_type == "change_date" )
        {
            $opt .= "WHERE b.$date_type >= '$start_date 00:00:00'
                       AND b.$date_type <= '$end_date 23:59:59' ";
            if ( $date_type == "cancel_date" )
            	$opt .="AND b.order_cs IN ( 1,2,3,4 )";
        }
        else if( $date_type == "collect_date"|| $date_type == "order_date"  )
        {
            $opt .= "WHERE a.$date_type >= '$start_date'
                       AND a.$date_type <= '$end_date' ";
        }
        else if( $date_type == "trans_date" || $date_type == "trans_date_pos")
        {
            $opt .= "WHERE a.$date_type >= '$start_date 00:00:00'
                       AND a.$date_type <= '$end_date 23:59:59' ";
			if( $date_type == "trans_date")
				$opt .="AND a.status >= 7 ";
			else 
				$opt .="AND a.status = 8 ";
        } 
        else if( $date_type == "sale_stop_date")
        {
            $opt .= "WHERE c.$date_type >= '$start_date 00:00:00'
                       AND c.$date_type <= '$end_date 23:59:59' ";
        }
        else if( $date_type == "cs_date")
        {
        	$opt .=" WHERE d.input_date >= '$start_date'
        			   AND d.input_date <= '$end_date' " ;
        }
        else
        {
            $opt .= "WHERE a.$date_type >= '$start_date 00:00:00'
                       AND a.$date_type <= '$end_date 23:59:59' ";
        } 
		if($date_type == "cs_date" || $work_type)
        	$opt .=" AND b.order_seq = d.order_seq ";
		if($date_type == "sale_stop_date")
			$opt .=" AND c.enable_sale = 0 ";
		
		//공급처멀티선택		
		if($multi_supply)
			$opt .= " AND b.supply_id in ( $multi_supply ) ";	
			
		//공급처선택		
		if($str_supply_code)
			$opt .= " AND b.supply_id in ( $str_supply_code ) ";
			
		// 판매처
        if( $shop_id )
            $opt .= " AND a.shop_id='$shop_id' ";
            
        // 판매처그룹
        if( $group_id )
        {
            $shop_id_arr = array();
            $query_group = "select shop_id from shopinfo where group_id='$group_id'";
            $result_group = mysql_query($query_group, $connect);
            while( $data_group = mysql_fetch_assoc($result_group) )
                $shop_id_arr[] = $data_group[shop_id];
                
            $opt .= " AND a.shop_id IN (". implode(",", $shop_id_arr) .")";
        }
        
        
        switch( $status_sel )
        {
            case 1: $opt .= " AND a.status = 0 "; break;
            case 2: $opt .= " AND a.status = 1 "; break;
            case 3: $opt .= " AND a.status = 7 "; break;
            case 4: $opt .= " AND a.status = 8 "; break;
            case 5: $opt .= " AND a.status IN (1,7) "; break;
            case 6: $opt .= " AND a.status IN (1,8) "; break;
            case 7: $opt .= " AND a.status IN (7,8) "; break;
        }
        
        switch( $order_cs_sel )
        {
            case 1: $opt .= " AND b.order_cs IN ( 0 ) "; break;
            case 2: $opt .= " AND b.order_cs IN ( 1,2,3,4 ) "; break;
            case 3: $opt .= " AND b.order_cs IN ( 5,6,7,8 ) "; break;
            case 4: $opt .= " AND b.order_cs IN ( 1,2 ) "; break;
            case 5: $opt .= " AND b.order_cs IN ( 3,4 ) "; break;
            case 6: $opt .= " AND b.order_cs IN ( 5,6 ) "; break;
            case 7: $opt .= " AND b.order_cs IN ( 7,8 ) "; break;
            case 8: $opt .= " AND a.hold > 0 "; break;
            case 9: $opt .= " AND a.cross_change > 0 "; break;
            case 10: $opt .= " AND b.order_cs IN ( 0,5,6,7,8 ) "; break;
            case 11: $opt .= " AND a.c_seq > 0 "; break;
        }
        
        // 작업
        if( $work_type )
        {
            if( $work_type == 10 )
                $opt .= " and d.cs_type in (10,11,16)  ";
            else if( $work_type == 12 )
                $opt .= " and d.cs_type in (12,13,18)  ";
            else if( $work_type == 17 )
                $opt .= " and d.cs_type = 17 ";
            else if( $work_type == 32 )
                $opt .= " and d.cs_type = 17 ";
            else
                $opt .= " and d.cs_type = $work_type ";
        }
        
        if( $c_cs == "c_seq")
            $opt .= " AND a.c_seq > 0 ";
        else if( $c_cs == "not_c_seq")
            $opt .= " AND a.c_seq = 0 ";
            
        foreach($special_option as $checked_option)
        {
//        	if($checked_option == "c_seq")
//        		$opt .= " AND a.c_seq > 0 ";
//        	if($checked_option == "not_c_seq")
//        		$opt .= " AND a.c_seq = 0 ";
        	if($checked_option == "gift")
        		$opt .= " AND b.is_gift > 0  ";
        		
        	if($checked_option == "hold")
        		$opt .= " AND a.hold > 0 ";
        		
        	if($checked_option == "change")
        		$opt .= " AND a.cross_change > 0 ";
        		
        	if($checked_option == "special_order")
            	$opt .= $this->query_special_order();

        	if($checked_option == "enable_sale")
        		$opt .= " AND c.enable_sale = 0 ";
        		
        	if($checked_option == "part_seq")
        		$opt .= " AND a.part_seq > 0 ";
        		
        	if($checked_option == "pre_trans")
        		$opt .= " AND a.pre_trans > 0 ";
        		
        	if($checked_option == "island")	
        	{
        		//사용자 우편번호
        		if($_SESSION[ISLAND_ZIPCODE])
        		{
        			//ez_config 에서 값을 읽어야됨
        			$zip_code_query = "SELECT island_custom_zipcode FROM ez_config";
					$zip_code_result = mysql_query($zip_code_query, $connect);
					$zip_code_data = mysql_fetch_assoc($zip_code_result);
					
					$zip_code = $zip_code_data[island_custom_zipcode];
        		}
        		else
        			$zip_code = class_top::get_default_zip_code();
        		$opt .= " AND REPLACE(recv_zip,'-','') IN ( $zip_code ) ";
        		
        		//제주미포함
        		if($_SESSION[ISLAND_JEJU_ZIPCODE])
        			$opt .= " AND LEFT(recv_zip,2) NOT IN ( 69 ) ";
        	}
        		
        		
        		
        }       
        
        
        
        // 멀티카테고리
        $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);

        if ( $arr_search_id[$m_sub_category_1] )
            $opt .= " AND c.m_category1= " . $arr_search_id[$m_sub_category_1];
        
        if ( $arr_search_id[$m_sub_category_2] )
            $opt .= " AND c.m_category2= " . $arr_search_id[$m_sub_category_2];
        
        if ( $arr_search_id[$m_sub_category_3] )
            $opt .= " AND c.m_category3= " . $arr_search_id[$m_sub_category_3];
            
        // 카테고리
        if ( $category )
            $opt .= " AND c.category = '$category' ";   
        //CS 
        if(count($cs))
        	$opt .= " AND b.order_cs IN (". implode(",", $cs) .") ";
        	
        //status
        if(count($status))
        	$opt .= " AND a.status IN (". implode(",", $status) .") ";
	    
        foreach($option as $idx => $option_str)
        {
        	if($query_str[$idx] && $option[$idx] == "product_id")
        		$opt .= " AND c.$option_str = '".$query_str[$idx]."' ";
        	else if($query_str[$idx] && $option[$idx] == "name" || $option[$idx] == "options")
        		$opt .= " AND c.$option_str like '%".$query_str[$idx]."%' ";
        	else if($query_str[$idx] && $option[$idx] == "org_product_name" || $option[$idx] == "shop_options" || $option[$idx] == "recv_name"|| $option[$idx] == "recv_mobile"|| $option[$idx] == "recv_address"|| $option[$idx] == "order_name")
        		$opt .= " AND a.$option_str like '%".$query_str[$idx]."%' ";
        	else if($query_str[$idx] && $option[$idx])
        		$opt .= " AND a.$option_str = '".$query_str[$idx]."' ";
        }
        
        
        
        if ( $download_type == 1 )
            $opt .= " group by a.seq ";
        else if ( $download_type == 2 )
            $opt .= " group by seq_pack ";
		else if($date_type == "cs_date")
	    	$opt .= " GROUP BY b.seq ";    
	    	
    	$opt .=" ORDER BY a.collect_date ASC, a.collect_time ASC ";
    	
		            
        $query_trans_cnt   = $query_trans_cnt . $opt;
        $query_order_cnt   = $query_order_cnt . $opt;
        $query_product_cnt = $query_product_cnt . $opt;
        

debug("query_trans_cnt : ".$query_trans_cnt);
debug("query_order_cnt : ".$query_order_cnt);
debug("query_product_cnt : ".$query_product_cnt);
		
		 // 전체 배송 기준 개수
        $result_trans_cnt = mysql_query( $query_trans_cnt, $connect );
        $data             = mysql_fetch_assoc( $result_trans_cnt );
        $total_trans      = $data[cnt];

        // 전체 주문 개수
        $result_order_cnt = mysql_query( $query_order_cnt, $connect );
        $data             = mysql_fetch_assoc( $result_order_cnt );
        $total_order[cnt]  = $data[cnt];
        $total_order[_sum] = $data[_sum];

        // 전체 상품 개수
        $result_product_cnt = mysql_query( $query_product_cnt, $connect );
        $data               = mysql_fetch_assoc( $result_product_cnt );
        $total_product[cnt]  = $data[cnt];
        $total_product[_sum] = $data[_sum];
        

		
		$result = mysql_query($query .$opt, $connect);
		$total_data_rows = mysql_num_rows($result);
		
		
		if ( !$is_download )
		    $opt .= " limit " . ($page - 1) * $line_per_page . ", $line_per_page";
		
debug("확장주문검색 2 get_DS00 : " . $query.$opt);
//echo "확장주문검색 2 get_DS00 : " . $query.$opt;


        $result = mysql_query($query .$opt, $connect);
        $total_rows = mysql_num_rows($result);
        
        $start_time = time();
    
        $sum_arr = array();
    
        // 전체 데이타
        $data_all = array();
        $i = 1;        
        while( $data = mysql_fetch_assoc($result) )
        {
            $i++;
            $new_time = time();
            if( $new_time - $start_time > 0 )
            {
                $start_time = $new_time;
                echo str_pad(" " , 256); 
                if( $is_download )
                    echo "<script type='text/javascript'>parent.show_txt( '$i / $total_rows' )</script>";
                else
                    echo "<script type='text/javascript'>show_txt( '자료 생성중 : $i / $total_rows' )</script>";
                flush();
            }
            usleep(1000);
            
            // 출력 정보 가져오기
            $temp_arr = $this->get_DS00_data_arr($data, $f, $supply_info,$is_download,$is_del_order);



//data : 쿼리데이터
//temp_arr : 필요한것만 담은것..
$aaaaaaa = 0;
if($aaaaaaa)
{
	echo "<pre>";
	print_r($data);
	print_r($temp_arr);
	echo "</pre>";
}



            // 합계
            foreach( $f as $f_val )
            {
                if( $f_val[chk] )
                {
                    if( $f_val[use_sum] )
                        $sum_arr[$f_val[field_id]] += $temp_arr[$f_val[field_id]];
                }
            }
    
            $data_all[] = $temp_arr;
        }

        // 합계의 첫번째는 "합계"
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $sum_arr[$f_val[field_id]] = "합계";
                break;
            }
        }
    
        // 기본정렬
        $sort_arr = array();
        foreach( $f as $f_val )
        {
            if( $f_val[sort] > 0 )
            {
                $sort_arr[] = array(
                    "no"    => $f_val[sort],
                    "field" => $f_val[field_id]
                );
            }
        }
        // 정렬순서 정렬
        $sort_arr = $this->array_array_sort($sort_arr, "no");
    
        // 정렬 필드를 정렬 하여 배열로...
        $ss_arr = array();
        
        // 헤더 클릭
        if( $sort )
        {
            $ss_arr[] = $sort;
            if( $sort_order )
                $ss_arr[] = SORT_ASC;
            else
                $ss_arr[] = SORT_DESC;
        }

        foreach( $sort_arr as $s_val )
        {
            $ss_arr[] = $s_val[field];
            
            // 수량일 경우 역순정렬
            if( $s_val[field] == "org_price"     ||
                $s_val[field] == "stock"         ||
                $s_val[field] == "not_yet_deliv" ||
                $s_val[field] == "lack_qty"      ||
                $s_val[field] == "request_qty" )
                $ss_arr[] = SORT_DESC;
        }

        // 정렬필드 순으로 전체 데이터 정렬하기
        foreach ($ss_arr as $ss_key => $ss_val) 
        {
            if (is_string($ss_val)) 
            {
                $tmp = array();
                foreach ($data_all as $da_key => $da_val)
                    $tmp[$da_key] = $da_val[$ss_val];
                $ss_arr[$ss_key] = $tmp;
            }
        }
        $ss_arr[] = &$data_all;
        call_user_func_array('array_multisort', $ss_arr);

        return $data_all;
    }

    //###############################
    // 다운로드 파일 만들기
    //###############################
    function save_file_DS00()
    {
        global $template, $connect, $page_code, $search, $sort, $sort_order;
		global $cur_connect;
        global $search ,$date_type ,$start_date ,$end_date ,$option , $panel_open,
			   $query_str ,$status ,$group_id ,$shop_id ,$cs, $select_field ;
		global $multi_supply_group, $multi_supply, $str_supply_code;	
		
		global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $str_category, $category;
		global $status_sel, $order_cs_sel, $special_option, $c_cs ;
		global $download_field;
		global $panel_option;
		
		if($status[0] == "")
			unset($status[0]);
			
		if($cs[0] == "")
			unset($cs[0]);
			
		$this->DS00_detail_option("start");
    
		
        // 조회 필드
        $connect = $cur_connect;  //조회필드는 현DB
        $page_code = $download_field;
        $f = class_table::get_setting();


        // 전체 쿼리
        if( $_REQUEST[bck_search] )
	    	$connect = bck_db_connect(); //백업은 백업 conn
        $data_all = $this->get_DS00($f, &$total_rows, &$sum_arr, &$total_trans, &$total_order , &$total_produc, &$total_data_rows , 1);

        $data_all[] = $sum_arr;
        $fn = "expandorder_search" . date("Ymd_His") . ".xls";
        $this->make_file_DS00( $data_all, $fn, $f );
        $connect = $cur_connect;  //조회필드는 현DB
        echo "<script type='text/javascript'>parent.set_file('$fn')</script>";
    }

    //###############################
    // 파일 생성
    //###############################
    function make_file_DS00( $data_all, $fn, $f )
    {
        global $connect;
        
        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();

        $col = 0;
        $row = 1;

        ini_set("memory_limit","512M");
            
        // 헤더 & 폭
        $cell_width = array();
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $sheet->getCellByColumnAndRow($col++, $row)->setValueExplicit($f_val[header], PHPExcel_Cell_DataType::TYPE_STRING);
                $cell_width[$f_val[field_id]] = strlen( iconv('utf-8','cp949',$f_val[header] ) );
            }
        }

        $end_col = PHPExcel_Cell::stringFromColumnIndex($col-1);
        
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFill()->getStartColor()->setARGB('FFCCFFCC');
        
        if( _DOMAIN_ == 'beginning' )
            $sheet->getStyle("A{$row}:{$end_col}{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        else
            $sheet->getStyle("A{$row}:{$end_col}{$row}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A{$row}:{$end_col}{$row}")->getFont()->setBold(true);
        
        foreach ($data_all as $data_val) {
            $row++;
            $col = 0;

            foreach( $f as $f_val )
            {
                if( !$f_val[chk] )  continue;
                
                $d_key = $f_val[field_id];
                $d_val = $data_val[$d_key];
                
                if( $f_val[tag] == "img" )
                    list($_temp, $d_val) = explode("|", $d_val);

                // 폭 계산
                $new_width = strlen( iconv('utf-8','cp949',$d_val) );
                if( $cell_width[$d_key] < $new_width )  
                    $cell_width[$d_key] = $new_width;

                class_table::print_xls($d_val, $f_val[is_num], &$sheet, $col, $row);
                $col++;
            }
        }
        $data_all = array();

        // 최종 폭 설정
        $col = 0;
        foreach( $f as $f_val )
        {
            if( $f_val[chk] )
            {
                $col_idx = PHPExcel_Cell::stringFromColumnIndex($col++);
                $sheet->getColumnDimension($col_idx)->setWidth($cell_width[$f_val[field_id]]+2);
            }
        }

        // border
        
 
        $styleArray = array(
        	'font' => array(
        		'name' => '굴림',
        		'size' => 9,
        	),
        	'borders' => array(
        		'allborders' => array(
        			'style' => PHPExcel_Style_Border::BORDER_THIN ,
        			'color' => array('argb' => 'FF000000'),
        		),
        	),
        );
        $sheet->getStyle('A1:'.$end_col.$row)->applyFromArray($styleArray);
        
        $objPageSetup = new PHPExcel_Worksheet_PageSetup();
        $objPageSetup->setFitToPage(true);
        $objPageSetup->setFitToWidth(1);
        $objPageSetup->setFitToHeight(0);

        $sheet->setPageSetup($objPageSetup);

        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        return $filename;
    }

    //###############################
    // 다운로드
    //###############################
    function download_DS00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, $filename );
    }    

    //###############################
    // 조회 필드 설정팝업
    //###############################
    function DS01()
    {
        global $template, $connect, $page_code;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //*****************************
    // 출력 row data 생성하기
    //*****************************
    function get_DS00_data_arr($data, $f, $supply_info,$is_download,$is_del_order)
    {
        global $connect;
        global $work_no, $order_status;

		// 사은품 가격 0
		if( $data['order_products_is_gift'] && !$_SESSION[GIFT_PRICE_0] )
			$gift_price = 1;
		else
			$gift_price = 0;

        $temp_arr = array();
        //삭제주문 검색시 삭제일 출력
        if($is_del_order)
        {
        	$query_del = "select * from csinfo where order_seq=$data[orders_seq] and cs_type=33";
	        $result_del = mysql_query($query_del, $connect);
	        $data_del = mysql_fetch_assoc($result_del);        	
        	$temp_arr[del_date] = "$data_del[input_date] $data_del[input_time]($data_del[writer])";
        }
        foreach( $f as $f_val )
        {
			if( !$f_val[chk] && !$f_val[sort] )  continue;

            //+++++++++++++++++++
            // 공급처 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "supply" ) 
            {
                $temp_arr[$f_val[field_id]] = class_table::get_supply_arr_data($f_val[field_id], $supply_info, $data[products_supply_code]);
                continue;
            }

            //+++++++++++++++++++
            // 상품 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "product" ) 
            {
            	$str="";
            	if($f_val[field_id] == "img_500" )
            	{
            		if($data["products_".$f_val[field_id]])
            			$str = "<img src= '".$data["products_".$f_val[field_id]]."' width=100>";
            		$temp_arr[$f_val[field_id]] = $str;
            	}
            	else if($f_val[field_id] == "img_desc1" || $f_val[field_id] == "img_desc2" || $f_val[field_id] == "img_desc3" || $f_val[field_id] == "img_desc4" )
            	{
            		if($data["products_".$f_val[field_id]])
            			$str = "<img src = '".$data["products_".$f_val[field_id]]."'>";
            		$temp_arr[$f_val[field_id]] = $str;
            	}
            	else if($f_val[field_id] == "stock_format")
            	{
            		$str = $this->get_stock_format($data["products_product_id"]);
            		$temp_arr[$f_val[field_id]] = $str;
            	}
            	else if($f_val[field_id] == "stock")
            	{
            		$temp_arr[$f_val[field_id]] = class_stock::get_current_stock( $data["products_product_id"] );
            	}
            	else if($f_val[field_id] == "str_category")
            	{
            		$temp_arr[$f_val[field_id]] = $_SESSION[MULTI_CATEGORY] ? class_multicategory::get_category_str($data[products_str_category]) : class_category::get_category_name( $data[products_category] );
            	}
            	else if($f_val[field_id] == "supply_code")
            	{
            		$temp_arr[$f_val[field_id]] = class_C::get_supplyname($data["products_".$f_val[field_id]]);
            	}
            	else if($f_val[field_id] == "enable_sale")
            	{
            		$str = "";
            		switch($data["products_".$f_val[field_id]])
            		{
	            		case 0:
	            			$str = "품절";
	            			break;
	            		case 1:
	            			$str = "";
	            			break;
	            		case 2:
	            			$str = "부분품절";
	            			break;
	            	}
            		$temp_arr[$f_val[field_id]] = $str;
            	}
            	else 
            	{
            		$products_field = $f_val[field_id];
            		switch($f_val[field_id])
            		{
            			case "p_options":
            				$products_field = "options";
            				break;
            			case "product_supply_price":
            				$products_field = "supply_price";
            				break;
            		}
            		//사은품은 공급가 0원..
            		if($gift_price && $f_val[field_id] == "product_supply_price")
            			$data["products_".$products_field] = 0;
            		$temp_arr[$f_val[field_id]] = $data["products_".$products_field];
            	}
            	
                continue;
            }

            //+++++++++++++++++++
            // 주문 정보
            //+++++++++++++++++++
            if( $f_val[data_type] == "order" ) 
            {
            	$orders_field = $f_val[field_id];
            	if($f_val[field_id] == "o_options")
            			$orders_field = "options";
          		$order_val = $data["orders_".$orders_field];
          		

	            if($f_val[field_id] == "shop_id")
	        		$temp_arr[$f_val[field_id]] = class_C::get_shop_name($order_val);
	        	else if($f_val[field_id]== "order_cs")
	        	{
	        		$temp_arr[$f_val[field_id]] = $this->get_order_cs($order_val);
	        		if($is_download)
	        			$temp_arr[$f_val[field_id]] = $this->get_order_cs2($order_val);
	        	}
	        	else if($f_val[field_id] == "status")
	        	{
	        		$temp_arr[$f_val[field_id]] = $this->get_order_status($order_val);
	        		if($is_download)
	        			$temp_arr[$f_val[field_id]] = $this->get_order_status2($order_val);
	        	}
	        	else if($f_val[field_id] == "hold")
	        	{
	        		$temp_arr[$f_val[field_id]] = "";
	        		if($order_val)
	        			$temp_arr[$f_val[field_id]] = "보류";
	        	}
	        	else if($f_val[field_id] == "cross_change")
	        	{
	        		$temp_arr[$f_val[field_id]] = "";
	        		if($order_val)
	        			$temp_arr[$f_val[field_id]] = "맞교환";
	        		
	        	}
	        	
	        	else if($f_val[field_id] == "shop_md"||$f_val[field_id] == "shop_admin"||$f_val[field_id] == "shop_ez_md"||$f_val[field_id] == "shop_ez_admin")
	        	{
					$shop_info = class_shop::get_info($data["orders_shop_id"]);
					$temp_str = str_replace("shop_","",$f_val[field_id]);
					$temp_arr[$f_val[field_id]] = $shop_info[$temp_str];
	        	}
	        	else if($f_val[field_id] =="is_island")
	        	{
	        		$temp_arr[$f_val[field_id]] = "";
	        		//사용자 우편번호
	        		if($_SESSION[ISLAND_ZIPCODE])
	        		{
	        			//ez_config 에서 값을 읽어야됨
	        			$zip_code_query = "SELECT island_custom_zipcode FROM ez_config";
						$zip_code_result = mysql_query($zip_code_query, $connect);
						$zip_code_data = mysql_fetch_assoc($zip_code_result);
						
						$zip_code = $zip_code_data[island_custom_zipcode];
	        		}
	        		else
	        			$zip_code = class_top::get_default_zip_code();	        		
	        		$zip_arr = explode(",", $zip_code);
	        		if(in_array(str_replace("-", "", $data["orders_recv_zip"]), $zip_arr))
	        			$temp_arr[$f_val[field_id]] ="도서지역";
					
					debug($temp_arr[$f_val[field_id]]);
	        		//제주미포함
	        		if($_SESSION[ISLAND_JEJU_ZIPCODE])
	        		{
	        			if(substr($data["orders_recv_zip"],0,2) == "69")
	        			{
	        				$temp_arr[$f_val[field_id]] = "";
	        			}
	        		}
	        	}
	        	else if($f_val[field_id] =="seq")
	        	{
	        		if(!$is_download)
	        		{
	        			global $cur_connect;
		                if( $_REQUEST[bck_search] )
		                {
		                    $query_bck_search = "select seq from orders where seq=".$data["orders_".$orders_field];
		                    $result_bck_search = mysql_query($query_bck_search, $cur_connect);
		                    if( !mysql_num_rows($result_bck_search) )
		                        $temp_arr[$f_val[field_id]] = "<a href=\"javascript:openwin3('popup.htm?template=EE01&bck_search=1&seq=".$data["orders_".$orders_field]."', 'pop', '600', '500');\" class=btn2>복구</a>&nbsp;";
		                }	
	        			$temp_arr[$f_val[field_id]] = $temp_arr[$f_val[field_id]].$this->popupcs($order_val);
	        			$temp_arr[org_seq] = $order_val;
	        		}
	        		else 
	        		{
	        		 	$temp_arr[$f_val[field_id]] = $data["orders_".$orders_field];
	        		}
	        	}
	        	else if($f_val[field_id] =="pack")
	        	{
	        		$data["orders_".$orders_field] = $data["orders_".$orders_field] == 0 ? "" : $data["orders_".$orders_field];
	        		$temp_arr[$f_val[field_id]] = $data["orders_".$orders_field];
	        	}
	        	else if($f_val[field_id] =="order_create_type")
	        	{
					if( $data[orders_c_seq] > 0 )
						$create_str = "배송후교환";
					else if( $data[orders_copy_seq] > 0 )
						$create_str = "주문복사";
					else if( $data[orders_seq] == $data[orders_order_id] )
						$create_str = "주문생성";
					else
						$create_str = "발주";
					$temp_arr[$f_val[field_id]] = $create_str;
	        	}
	        	else if($f_val[field_id] =="commission")
	        		$temp_arr[$f_val[field_id]] = $gift_price ? 0 : ($data['orders_amount'] - $data['orders_supply_price']);
	        	else 
	                $temp_arr[$f_val[field_id]] = $data["orders_".$orders_field];
	                
	            //사은품은 정산코드 0
            	if($gift_price && 
            	( $f_val[field_id] == "code11" 
            	||$f_val[field_id] == "code12"
            	||$f_val[field_id] == "code13"
            	||$f_val[field_id] == "code14"
            	||$f_val[field_id] == "code15"
            	||$f_val[field_id] == "code16"
            	||$f_val[field_id] == "code17"
            	||$f_val[field_id] == "code18"
            	||$f_val[field_id] == "code19"
            	||$f_val[field_id] == "code20"
            	||$f_val[field_id] == "code31"
            	||$f_val[field_id] == "code32"
            	||$f_val[field_id] == "code33"
            	||$f_val[field_id] == "code34"
            	||$f_val[field_id] == "code35"
            	||$f_val[field_id] == "code36"
            	||$f_val[field_id] == "code37"
            	||$f_val[field_id] == "code38"
            	||$f_val[field_id] == "code39"
            	||$f_val[field_id] == "code40"))
            		$temp_arr[$f_val[field_id]] = 0;
                continue;
            }
            
            //+++++++++++++++++++
            // 주문 정보 상세
            //+++++++++++++++++++
            if( $f_val[data_type] == "order_product" ) 
            {
            	
            	if($f_val[field_id]=="sum_supply_price")
            	{
            		$temp_arr[$f_val[field_id]] = $gift_price ? 0 : $data[products_supply_price] * $data[order_products_qty];
            	}
            	else if($f_val[field_id]=="match_type")
            	{
            		$str = "";
            		switch($data["order_products_match_type"])
            		{
            			case 1: $str = "신규 매칭정보"; break;
            			case 2: $str = "이전 매칭정보"; break;
            			case 3: $str = "매칭정보 미저장"; break;
            			case 4: $str = "프로그램 매칭"; break;
            			case 5: $str = "CS 상품선택"; break;
            			case 0: $str = ""; break;
            		}
            		$temp_arr[$f_val[field_id]] = $str;
            		$str = "";
            	}
            	else if($f_val[field_id]=="match_type")
            	{
            		$temp_arr[$f_val[field_id]] ="";
            		if($data["order_products_is_gift"])
            			$temp_arr[$f_val[field_id]] ="사은품";
            	}
            	else 
            	{
            	 	$temp_arr[$f_val[field_id]] = $data["order_products_".$f_val[field_id]];
            	}
                continue;
            }
            
            if($f_val[field_id] == "cs_content")
            {
            	$cs_content ="";            	
            	$cs_query = "SELECT * FROM csinfo WHERE order_seq = ".$data["orders_seq"];
            	$cs_result = mysql_query($cs_query, $connect);            	
            	$row=0;
        		while( $cs_data = mysql_fetch_assoc($cs_result) )
        		{	
        			if($row)
        			{
	        			if($is_download)
	        				$cs_content .="\n";
	        			else
	        				$cs_content .="<br>";
	        		}
        				
        			$cs_content .=
        			$cs_data[input_date]." ".
        			$cs_data[input_time]." ".
        			$cs_data[writer]." ".
        			$cs_data[content]." ".
        			$cs_data[user_content];
        			$row++;
        		}
        		if($cs_content == "")
        		{
	        		if($is_download)
	        			$cs_content =" ";
	        		else
	        			$cs_content ="&nbsp;";
        		}
            	$temp_arr[$f_val[field_id]] = $cs_content;
            }
            else if($f_val[field_id] == "cs_reason")
            {
            	if($data[orders_order_cs] > 0)
        		{
        			switch($data[orders_order_cs])
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
        			
	        		$__query = "SELECT cs_reason FROM csinfo WHERE order_seq = $data[orders_seq] AND cs_type IN ($cs_type) ORDER BY seq DESC LIMIT 1";
	        		$__result = mysql_query( $__query, $connect );
			        $__data   = mysql_fetch_assoc( $__result );
 			        $cs_reason = $__data[cs_reason];
 			    }
 			    
            	$temp_arr[$f_val[field_id]] = "$cs_reason";
            }

//cs_reason 취소교환사유
        }
        
        return $temp_arr;
    }
    // CS 재고 표시 포멧
    function get_stock_format($product_id)
    {
        global $template, $connect;

        $f = $_SESSION[CS_STOCK_FORMAT];
        
        // 포멧이 없으면
        if( $f == '' )  return '';
        
        // 현재재고
        if( strpos( $f, "A" ) !== false )
            $a = class_stock::get_current_stock($product_id);
            
        // 송장상태
        if( strpos( $f, "B" ) !== false )
            $b = class_stock::get_ready_stock($product_id);

        // 접수상태
        if( strpos( $f, "C" ) !== false )
            $c = class_stock::get_ready_stock2($product_id);
        
        $a1 = array("A", "B", "C");
        $a2 = array( ($a>0 ? $a : $a), ($b>0 ? $b : 0), ($c>0 ? $c : 0) );
        $f2 = str_replace( $a1, $a2, $f );

        $ptrn = "/\[([^\]]+)\]/";
        $stock_format = preg_replace_callback( $ptrn, 'class_DS00::change_stock_format', $f2 );
        
        return $stock_format;
    }
    
    // CS 재고 표시 포멧 변경함수 ( 클래스 내에서는 static으로 정의되어야 함 )
    static function change_stock_format($input)
    {
        eval( "\$re = " . $input[1] . ";" );
        return $re;
    }
}
?>
