<?
require_once "class_B.php";
require_once "class_top.php";
require_once "class_stock.php";
require_once "class_lock.php";
require_once "class_IJ00.php";
 
class class_BD00 extends class_top
{
   var $arr_items;
   var $val_items;  // 반듯이 입력해야 하는 item

    function BD00()
    {
        global $template;
        global $connect;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function BD01()
    {
        global $template;
        global $connect;

		$query = "SELECT island_custom_zipcode FROM ez_config";
		$result = mysql_query($query, $connect);
		$data = mysql_fetch_assoc($result);
		
		
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function custom_zipcode_save()
    {
		global $template;
		global $connect;
		global $island_custom_zipcode;
		
		$query = "UPDATE ez_config SET island_custom_zipcode = '$island_custom_zipcode'";
		$result = mysql_query($query, $connect);
		
		echo "<script>alert('등록되었습니다.');</script>";
		echo "<script>window.close();</script>";
    }
    function modify()
    {
        global $connect;

        $stock_manage_use  = $_REQUEST[stock_manage_use];
        $island_use        = $_REQUEST[island_use];
        $base_trans_code   = $_REQUEST[base_trans_code];
        $base_trans_price  = $_REQUEST[base_trans_price];
        $after_trans_price  = $_REQUEST[after_trans_price];
        $jaego_use         = $_REQUEST[jaego_use];

        $pack_balju_auto   = $_REQUEST[pack_balju_auto];
        $pack_diff_supply  = $_REQUEST[pack_diff_supply];
        $pack_diff_supply_group  = $_REQUEST[pack_diff_supply_group];
        $pack_diff_shop    = $_REQUEST[pack_diff_shop];
        $pack_diff_group   = $_REQUEST[pack_diff_group];
        $pack_soldout      = $_REQUEST[pack_soldout];
        $pack_trans        = $_REQUEST[pack_trans];

        $pack_gmarket_oversea   = $_REQUEST[pack_gmarket_oversea];
        $pack_gmarket_japan     = $_REQUEST[pack_gmarket_japan];
        $pack_gmarket_singapore = $_REQUEST[pack_gmarket_singapore];
        $pack_ogage_japan       = $_REQUEST[pack_ogage_japan];
        $pack_11st_oversea      = $_REQUEST[pack_11st_oversea];
        $pack_lotte_oversea     = $_REQUEST[pack_lotte_oversea];
        $pack_coupang           = $_REQUEST[pack_coupang];
        $pack_tmon              = $_REQUEST[pack_tmon];

        $bracket_match_use = $_REQUEST[bracket_match_use];
        $pack_bracket      = $_REQUEST[pack_bracket];
        $jaego_basedt      = $_REQUEST[jaego_basedt];
        $ez_version        = $_REQUEST[ez_version];
    
        $use_warehouse     = $_REQUEST[use_warehouse];                          
        $use_location      = $_REQUEST[use_location];          
        $use_weight        = $_REQUEST[use_weight];             
        $vendoring         = $_REQUEST[vendoring];       
        
        if ( $_REQUEST[island_shop] )
        {
            foreach( $_REQUEST[island_shop] as $b )
                $island_shop_str .= ($island_shop_str ? "," : "") . $b;
        }
        $island_custom_shopid	= $_REQUEST[island_custom_shopid];
        $island_zipcode			= $_REQUEST[island_zipcode];        
        $island_jeju_zipcode	= $_REQUEST[island_jeju_zipcode];
        $island_hold			= $_REQUEST[island_hold];
        $island_msg				= $_REQUEST[island_msg];
        $island_msg_txt			= $_REQUEST[island_msg_txt];
        $island_msg_position	= $_REQUEST[island_msg_position];
              

        $use_individual_qty= $_REQUEST[use_individual_qty];             
        $products_sort     = $_REQUEST[products_sort];             
        $basic_version     = $_REQUEST[basic_version];             
        $pack_gift         = $_REQUEST[pack_gift];
        $new_supply_select = $_REQUEST[new_supply_select];

        $modify_order_info = $_REQUEST[modify_order_info];
        
        //주문배송관리
        $expand_search         = $_REQUEST[expand_search];
        
        $change            = $_REQUEST[change];
        $cancel            = $_REQUEST[cancel];
        $usertrans_price   = $_REQUEST[usertrans_price];
        $barcode_format    = $_REQUEST[barcode_format];
        $sync_start_date   = $_REQUEST[sync_start_date];
        $match_sort        = $_REQUEST[match_sort];
        $balju_except_soldout = $_REQUEST[balju_except_soldout];
        $balju_always_memo = $_REQUEST[balju_always_memo];
        $del_social_option_no = $_REQUEST[del_social_option_no];
       
        $use_cs_delay      = $_REQUEST[use_cs_delay];
        $cs_delay1         = $_REQUEST[cs_delay1];
        $cs_delay2         = $_REQUEST[cs_delay2];
        $auto_hold         = $_REQUEST[auto_hold];
        $cs_stock_format   = $_REQUEST[cs_stock_format];
        $del_cs            = $_REQUEST[del_cs];
        
        $part_trans_limit  = $_REQUEST[part_trans_limit];
        
        $use_req_bill2     = $_REQUEST[use_req_bill2];
        $use_stock_period  = $_REQUEST[use_stock_period];
        $stock_period_day  = $_REQUEST[stock_period_day];
        $use_each_supply   = $_REQUEST[use_each_supply];
        $stock_in_standby   = $_REQUEST[stock_in_standby];
        $supply_return_ready   = $_REQUEST[supply_return_ready];
        
        $each_supply_email = $_REQUEST[each_supply_email];
        $link_sms_mobile   = $_REQUEST[link_sms_mobile];
        $sheet_email_from  = $_REQUEST[sheet_email_from];
        $sheet_email_subject = $_REQUEST[sheet_email_subject];
        $sheet_email_body  = $_REQUEST[sheet_email_body];
        $req_stockin_auto  = $_REQUEST[req_stockin_auto];
        $enable_cancel_stock = $_REQUEST[enable_cancel_stock];
        $multi_wh          = $_REQUEST[multi_wh];
        $new_cj_trans      = $_REQUEST[new_cj_trans];
        $special_stock     = $_REQUEST[special_stock];
        $disable_minus_stock     = $_REQUEST[disable_minus_stock];
        $ezchain_request   = $_REQUEST[ezchain_request];
        $extra_stock_type  = $_REQUEST[extra_stock_type];
        $ezchain_request_status  = $_REQUEST[ezchain_request_status];
        $pos_sheet_load    = $_REQUEST[pos_sheet_load];
        
        $match_option      = $_REQUEST[match_option];
        $match_option1_ex  = preg_replace('/\s/','',$_REQUEST[match_option1_ex]);
        $trans_cancel      = $_REQUEST[trans_cancel];
        $product_name_exp  = $_REQUEST[product_name_exp];
        $supply_org_price_type = $_REQUEST[supply_org_price_type];
        $gift_price_0      = $_REQUEST[gift_price_0];
        $supply_price_with_trans      = $_REQUEST[supply_price_with_trans];

        $ip_block_use      = $_REQUEST[ip_block_use];
        $white_ip          = $_REQUEST[white_ip];

        $del_price         = $_REQUEST[del_price];
        $cs_price          = $_REQUEST[cs_price];
        $cs_tab_cnt        = $_REQUEST[cs_tab_cnt];
        $dup_product_name  = $_REQUEST[dup_product_name];
        $sort_reg_date     = $_REQUEST[sort_reg_date];
        $product_orderby  = $_REQUEST[product_orderby];
        
        $multi_category    = $_REQUEST[multi_category];

        $cancel_type       = $_REQUEST[cancel_type];
        $change_type       = $_REQUEST[change_type];
        $user_cs_type      = $_REQUEST[user_cs_type];

        $add_name_11       = $_REQUEST[add_name_11];
        $new_order_who     = $_REQUEST[new_order_who];
        $use_trans_return  = $_REQUEST[use_trans_return];
        $takeback_use_default_trans_corp= $_REQUEST[takeback_use_default_trans_corp];
        $use_return_money  = $_REQUEST[use_return_money];
        $use_return_prd_all= $_REQUEST[use_return_prd_all];
        $after_change_who  = $_REQUEST[after_change_who];
        $use_shop_priority = $_REQUEST[use_shop_priority];
        $cs_except_soldout = $_REQUEST[cs_except_soldout];
        $cs_multi_trans_no = $_REQUEST[cs_multi_trans_no];
        $send_return_sms_auto = $_REQUEST[send_return_sms_auto];
        $send_return_sms_msg  = $_REQUEST[send_return_sms_msg];
        $return_pack_lock  = $_REQUEST[return_pack_lock];
        $use_pre_trans     = $_REQUEST[use_pre_trans];
        $use_stock_ox      = $_REQUEST[use_stock_ox];
        $cs_disp_memo      = $_REQUEST[cs_disp_memo];
        $cs_auto_complete1      = $_REQUEST[cs_auto_complete1];
        $cs_auto_complete2      = $_REQUEST[cs_auto_complete2];
        $cs_auto_complete3      = $_REQUEST[cs_auto_complete3];
        $cs_auto_complete4      = $_REQUEST[cs_auto_complete4];
		$cs_view_all			= $_REQUEST[cs_view_all];
		$use_cs_supply_search	= $_REQUEST[use_cs_supply_search];
		$is_transinfo_change	= $_REQUEST[is_transinfo_change];

        $base_sender_name  = $_REQUEST[base_sender_name];
        $base_sender_tel   = $_REQUEST[base_sender_tel];
        $base_sender_zip   = $_REQUEST[base_sender_zip];
        $base_sender_add1  = $_REQUEST[base_sender_add1];
        $base_sender_add2  = $_REQUEST[base_sender_add2];
        $use_sender_info   = $_REQUEST[use_sender_info];

        $base_trans_link_code1 = $_REQUEST[base_trans_link_code1];
        $base_trans_link_code2 = $_REQUEST[base_trans_link_code2];
        $use_trans_link_shop = $_REQUEST[use_trans_link_shop];
        $no_print_hold = $_REQUEST[no_print_hold];

        $use_product_price = $_REQUEST[use_product_price];
        $new_option_format = $_REQUEST[new_option_format];
        $pack_p_o          = $_REQUEST[pack_p_o];

        // 발주 오류나서 잠시 block = jkryu 2011.6.3
        if ( $_REQUEST[balju_order] )
        {
            foreach( $_REQUEST[balju_order] as $b )
                $balju_order_str .= ($balju_order_str ? "," : "") . $b;
        }


        
        // shoplinker 관련
        $use_shoplinker      = $_REQUEST[use_shoplinker];
        $use_confirm         = $_REQUEST[use_confirm];
        $shoplinker_id       = $_REQUEST[shoplinker_id];
        $shoplinker_password = $_REQUEST[shoplinker_password];
		$auto_order_time	 = $_REQUEST[auto_order_time];
        
        if ($base_trans_code) $option1 = " base_trans_code = '$base_trans_code',";
        else $options1 = "";
        
        
        // 사입업체 설정
        /*
        $use_saip           = $_REQUEST[use_saip];
        $prev_saip_seq      = $_REQUEST[prev_saip_seq];
        $saip_seq           = $_REQUEST[saip_seq];
        $saip_name          = $_REQUEST[saip_name];
        
        if($use_saip == 1){
            // 사입업체을 변경했을 경우
            if(isset($prev_saip_seq)){
                if($prev_saip_seq != $saip_seq){
                    // 시스템 DB 연결
                    //$sys_con = sys_db_connect();
                    
                    // 이전 사입업체 DB연결을 위해 sys계정 연결
                    $sql = "select * from sys_saip where saip_seq = '$prev_saip_seq'";
                    $list = mysql_fetch_assoc(mysql_query($sql, $connect));
                    //$list = mysql_fetch_assoc(mysql_query($sql, $sys_con));
                    
                    // 사입업체 DB 연결
                    $saip_connect = $this->saip_db_connect($list[host], $list[db_id], $list[db_name], $list[db_name]);
                    
                    // 사입업체 DB 연결 후 사입업체 고객 테이블에서 삭제
                    $sql = "delete from saip_customer where id = '$_SESSION[LOGIN_DOMAIN]'";
                    //mysql_query($sql, $saip_connect);
                    mysql_query($sql, $saip_connect);
                }
            }
            
            // 시스템 DB 연결
            //$sys_con = sys_db_connect();
            
            // 사입을 요청한 고객의 정보를 sys_domain에서 가져오기
            $sql = "select * from sys_domain where id = '$_SESSION[LOGIN_DOMAIN]'";
            //$userinfo = mysql_fetch_assoc(mysql_query($sql, $sys_con));
            $userinfo = mysql_fetch_assoc(mysql_query($sql, $connect));
            
            // 새로운 사입업체 DB연결을 위해 sys계정 연결
            $sql = "select * from sys_saip where saip_seq = '$saip_seq'";
            //$list = mysql_fetch_assoc(mysql_query($sql, $sys_con));
            $list = mysql_fetch_assoc(mysql_query($sql, $connect));
            
            // 사입업체 DB 연결 후 사입업체 고객 테이블에 추가
            //$saip_connect = $this->temp_sys_db_connect($list[host], $list[db_id], $list[db_name], $list[db_name]);
            $sql = "insert into saip_customer 
                    set id = '$_SESSION[LOGIN_DOMAIN]',
                        name = '$userinfo[name]',
                        host = '$userinfo[host]',
                        db_id = '$userinfo[db_id]',
                        db_pwd = '$userinfo[db_pwd]',
                        db_name = '$userinfo[db_name]',
                        corp_name = '$userinfo[corp_name]',
                        corp_no = '$userinfo[corp_no]',
                        corp_boss = '$userinfo[corp_boss]',
                        corp_address = '$userinfo[corp_address]',
                        corp_address2 = '$userinfo[corp_address2]',
                        corp_job1 = '$userinfo[corp_job1]',
                        corp_job2 = '$userinfo[corp_job2]',
                        corp_tel = '$userinfo[corp_tel]',
                        corp_mobile = '$userinfo[corp_mobile]',
                        corp_zip1 = '$userinfo[corp_zip1]',
                        corp_zip2 = '$userinfo[corp_zip2]',
                        crdate = now()";
            mysql_query($sql, $connect);
            //mysql_query($sql, $saip_connect);
        }else if($use_saip == 0){
            if(isset($prev_saip_seq)){
                // 이전 사입업체 DB연결을 위해 sys계정 연결
                $sql = "select * from sys_saip where saip_seq = '$prev_saip_seq'";
                $list = mysql_fetch_assoc(mysql_query($sql, $connect));
                
                // 사입업체 DB 연결 후 사입업체 고객 테이블에서 삭제
                // 연결했다고 가정
                // $sys_connect ...
                //$saip_connect = $this->temp_sys_db_connect($list[host], $list[db_id], $list[db_name], $list[db_name]);
                $sql = "delete from saip_customer where id = '$_SESSION[LOGIN_DOMAIN]'";
                //mysql_query($sql, $saip_connect);
                mysql_query($sql, $connect);
            }
        }
        */
        
        /*
		    ,use_saip = '$use_saip'
		    ,saip_seq = '$saip_seq'
		    ,saip_name = '$saip_name'
		*/
 
        $sql = "update ez_config set
                        jaego_use           = '$jaego_use',
                        island_use           = '$island_use',
                        pack_balju_auto   = '$pack_balju_auto',
                        pack_diff_supply  = '$pack_diff_supply',
                        pack_diff_supply_group  = '$pack_diff_supply_group',
                        pack_diff_shop    = '$pack_diff_shop',
                        pack_diff_group   = '$pack_diff_group',
                        pack_soldout      = '$pack_soldout',
                        pack_trans        = '$pack_trans',
                        pack_gmarket_oversea   = '$pack_gmarket_oversea',
                        pack_gmarket_japan     = '$pack_gmarket_japan',
                        pack_gmarket_singapore = '$pack_gmarket_singapore',
                        pack_ogage_japan       = '$pack_ogage_japan',
                        pack_11st_oversea      = '$pack_11st_oversea',
                        pack_lotte_oversea     = '$pack_lotte_oversea',
                        pack_coupang      = '$pack_coupang',
                        pack_tmon         = '$pack_tmon',
                        bracket_match_use = '$bracket_match_use',
                        pack_bracket      = '$pack_bracket',
                        jaego_basedt      = '$jaego_basedt',
                        ${option1}
                        base_trans_price  = '$base_trans_price',
                        after_trans_price  = '$after_trans_price',
                        pack_gift         = '$pack_gift',
                        expand_search	  = '$expand_search',
                        use_warehouse     = '$use_warehouse',
                        use_location      = '$use_location',
                        use_cs_delay      = '$use_cs_delay',
                        cs_delay1         = '$cs_delay1',
                        cs_delay2         = '$cs_delay2',
                        cancel_type       = '$cancel_type',
                        change_type       = '$change_type',
                        user_cs_type      = '$user_cs_type',
                        del_cs            = '$del_cs',
                        part_trans_limit  = '$part_trans_limit',
                        use_req_bill2     = '$use_req_bill2',
                        use_stock_period  = '$use_stock_period',
                        stock_period_day  = '$stock_period_day',
                        use_each_supply   = '$use_each_supply',
                        stock_in_standby   = '$stock_in_standby',
                        supply_return_ready   = '$supply_return_ready',
                        each_supply_email = '$each_supply_email',
                        link_sms_mobile   = '$link_sms_mobile',
                        sheet_email_from  = '$sheet_email_from',
                        sheet_email_subject = '$sheet_email_subject',
                        sheet_email_body  = '$sheet_email_body',
                        enable_cancel_stock = '$enable_cancel_stock',
                        multi_wh          = '$multi_wh',
                        new_cj_trans      = '$new_cj_trans',
                        special_stock     = '$special_stock',
                        disable_minus_stock     = '$disable_minus_stock',
                        ezchain_request   = '$ezchain_request',
                        extra_stock_type  = '$extra_stock_type',
                        ezchain_request_status  = '$ezchain_request_status',
                        pos_sheet_load    = '$pos_sheet_load',
                        req_stockin_auto  = '$req_stockin_auto',
                        modify_order_info = '$modify_order_info',
                        auto_hold         = '$auto_hold',
                        barcode_format    = '$barcode_format',
                        sync_start_date   = '$sync_start_date',
                        match_sort        = '$match_sort',
                        balju_except_soldout = '$balju_except_soldout',
                        balju_always_memo = '$balju_always_memo',
                        del_social_option_no = '$del_social_option_no',
                        use_individual_qty= '$use_individual_qty',
                        products_sort     = '$products_sort',
                        new_supply_select = '$new_supply_select',
                        
                        island_shop		  = '$island_shop_str',
                        island_custom_shopid = '$island_custom_shopid',						
						island_zipcode	  = '$island_zipcode',						
						island_jeju_zipcode   = '$island_jeju_zipcode',
						island_hold		  = '$island_hold',
						island_msg 		  = '$island_msg',		
						island_msg_txt	  = '$island_msg_txt',
						island_msg_position   = '$island_msg_position',
						
                        balju_order       = '$balju_order_str',
                        match_option      = '$match_option',
                        match_option1_ex  = '$match_option1_ex',
                        trans_cancel      = '$trans_cancel',
                        product_name_exp  = '$product_name_exp',
                        supply_org_price_type  = '$supply_org_price_type',
                        gift_price_0      = '$gift_price_0',
                        supply_price_with_trans      = '$supply_price_with_trans',
                        ip_block_use      = '$ip_block_use',
                        white_ip          = '$white_ip',
                        cs_price          = '$cs_price',
                        cs_tab_cnt        = '$cs_tab_cnt',
                        dup_product_name  = '$dup_product_name',
                        product_orderby	  = '$product_orderby',
                        sort_reg_date     = '$sort_reg_date',
                        multi_category    = '$multi_category',
                        cs_stock_format   = '$cs_stock_format',
                        add_name_11       = '$add_name_11',
                        new_order_who     = '$new_order_who',
                        use_trans_return  = '$use_trans_return',
                        takeback_use_default_trans_corp= '$takeback_use_default_trans_corp',
                        use_return_money  = '$use_return_money',
                        use_return_prd_all= '$use_return_prd_all',
                        base_sender_name  = '$base_sender_name',
                        base_sender_tel   = '$base_sender_tel',
                        base_sender_zip   = '$base_sender_zip',
                        base_sender_add1  = '$base_sender_add1',
                        base_sender_add2  = '$base_sender_add2',
                        use_sender_info   = '$use_sender_info',
                        base_trans_link_code1 = '$base_trans_link_code1',
                        base_trans_link_code2 = '$base_trans_link_code2',
                        use_trans_link_shop = '$use_trans_link_shop',
                        no_print_hold = '$no_print_hold',
                        new_option_format = '$new_option_format',
                        after_change_who  = '$after_change_who',
                        use_shop_priority = '$use_shop_priority',
                        cs_except_soldout = '$cs_except_soldout',
                        cs_multi_trans_no = '$cs_multi_trans_no',
                        send_return_sms_auto = '$send_return_sms_auto',
                        send_return_sms_msg  = '$send_return_sms_msg',
                        cs_multi_trans_no = '$cs_multi_trans_no',
                        return_pack_lock  = '$return_pack_lock',
                        use_pre_trans     = '$use_pre_trans',
                        use_stock_ox      = '$use_stock_ox',
                        cs_disp_memo      = '$cs_disp_memo',
                        cs_auto_complete1 = '$cs_auto_complete1',
                        cs_auto_complete2 = '$cs_auto_complete2',
                        cs_auto_complete3 = '$cs_auto_complete3',
                        cs_auto_complete4 = '$cs_auto_complete4',
                        cs_view_all		  = '$cs_view_all',
                        use_cs_supply_search = '$use_cs_supply_search',
                        is_transinfo_change = '$is_transinfo_change',
                        pack_p_o          = '$pack_p_o',
                        use_product_price = '$use_product_price',
						auto_order_time   = '$auto_order_time'";
						
        if ( $use_weight )
            $sql .= ",use_weight = $use_weight ";

        mysql_query($sql, $connect) or die(mysql_error());

        // 관리자모드 
        if( $_SESSION[LOGIN_LEVEL] == 9 )
        {
            // 샵링커 관련
            // if ( $use_shoplinker )
            $sql .= ",use_shoplinker = $use_shoplinker";
            $sql .= ",use_confirm    = $use_confirm";
            
            if ( $shoplinker_id )
                $sql .= ",shoplinker_id = '$shoplinker_id'";
    
            if ( $shoplinker_password )
                $sql .= ",shoplinker_password = '$shoplinker_password'";
            
            $sql .= "   ,basic_version     = '$basic_version'
                        ,stock_manage_use  = '$stock_manage_use'
                        ,del_price         = '$del_price'
                        ,vendoring         = '$vendoring'";
                        
            // sys_domain 의 svc_version
            $query = "update sys_domain set svc_version='" . ( $basic_version==0 ? 'P' : 'B' ) . "' where id='" . _DOMAIN_ . "'";

    		$sys15_connect = mysql_connect( _MYSQL_SYS_HOST_, _MYSQL_SYS_ID_, _MYSQL_SYS_PASSWD_ );
	    	mysql_select_db(_MYSQL_SYS_DB_, $sys15_connect);
            mysql_query($query, $sys15_connect);
        }
        
debug( "환경설정변경 : " . $sql );
        mysql_query($sql, $connect) or die(mysql_error());
        
        // 정산설정
        $sql = "update stat_config set value = '$change' where code='change'";
        mysql_query($sql, $connect) or die(mysql_error());
        
        $sql = "update stat_config set value = '$cancel' where code='cancel'";
        mysql_query($sql, $connect) or die(mysql_error());
        
        $sql = "update stat_config set value = '$usertrans_price' where code='usertrans_price'";
        mysql_query($sql, $connect) or die(mysql_error());        
        echo "<script>document.location.href = 'logout.php';</script>";
        exit;
    }
    
    function saip_db_connect($host, $db_id, $db_pw, $db_name)
    {
        $con = mysql_connect($host, $db_id, $db_pw, $db_name);
        mysql_select_db($db_name, $con);
        
        $charset="utf8";
        mysql_query("set session character_set_connection=${charset};", $con);
        mysql_query("set session character_set_results=${charset};", $con);                                                                                                                     
        mysql_query("set session character_set_client=${charset};", $con);
        
        return $con;
    }
    
    function tel_modify()
    {
        global $connect;

        $query = "select * from orders";
        $result = mysql_query($query, $connect);
        while($data=mysql_fetch_assoc($result))
        {
            if( strpos($data[recv_tel    ], "-") === false )  $new_recv_tel     = $this->arrange_tel( $data[recv_tel    ] );  else  $new_recv_tel     = $data[recv_tel    ];
            if( strpos($data[recv_mobile ], "-") === false )  $new_recv_mobile  = $this->arrange_tel( $data[recv_mobile ] );  else  $new_recv_mobile  = $data[recv_mobile ];
            if( strpos($data[order_tel   ], "-") === false )  $new_order_tel    = $this->arrange_tel( $data[order_tel   ] );  else  $new_order_tel    = $data[order_tel   ];
            if( strpos($data[order_mobile], "-") === false )  $new_order_mobile = $this->arrange_tel( $data[order_mobile] );  else  $new_order_mobile = $data[order_mobile];
                
            $recv_tel     = ( $new_recv_tel     == $data[recv_tel]     ? 0 : 1 );
            $recv_mobile  = ( $new_recv_mobile  == $data[recv_mobile]  ? 0 : 1 );
            $order_tel    = ( $new_order_tel    == $data[order_tel]    ? 0 : 1 );
            $order_mobile = ( $new_order_mobile == $data[order_mobile] ? 0 : 1 );

            if( $recv_tel || $recv_mobile || $order_tel || $order_mobile )
            {
                $query = "update orders set ";
                
                $set_option = "";
                if( $recv_tel     )  $set_option .= ( $set_option ? "," : "" ) . "recv_tel    ='$new_recv_tel'    ";
                if( $recv_mobile  )  $set_option .= ( $set_option ? "," : "" ) . "recv_mobile ='$new_recv_mobile' ";
                if( $order_tel    )  $set_option .= ( $set_option ? "," : "" ) . "order_tel   ='$new_order_tel'   ";
                if( $order_mobile )  $set_option .= ( $set_option ? "," : "" ) . "order_mobile='$new_order_mobile'";
                
                $query .= $set_option . " where seq=$data[seq]";
                mysql_query( $query, $connect );
            }
        }
    }

    function arrange_tel( $telno )
    {
        if ( $telno )
        {
            // 숫자이외의 정보는 삭제
            $telno = preg_replace("/[^0-9]/","",$telno );

            // 앞에 2자리를 먼저 check 
            $_ddd = substr( $telno,0,2);

            if ( $_ddd == "02" )
            {
                $_ddd = substr( $telno, 0,2);
                    $_tel = substr( $telno, 2);
            }
            else
            {
                $_ddd = substr( $telno, 0,3);
                $_tel = substr( $telno, 3);
            }

                // 8자리로 인식
            $_tel = sprintf("%8s", $_tel );

            // 전 4자리
            $t1 = substr( $_tel, 0,4);
            $t2 = substr( $_tel, -4);

            $telno = sprintf("%s-%d-%s", $_ddd, $t1,$t2);
            return $telno;
        }
        else
            return "";
    }        



    function setup_main()
    {
        global $connect;

        $id = "main_show_div" . substr($_REQUEST[id], -1);
        $sts = $_REQUEST[sts];

        $upd_sql= "update ez_config set ${id} = $sts";
        mysql_query($upd_sql, $connect) or die(mysql_error());
        exit;
    }

    function main_menu_popup()
    {
        global $connect;

        $sts = $_REQUEST[sts];

        $_SESSION[MAIN_MENU_POPUP] = $sts;

        exit;
    }
    // 상품합치기
    function merge_product()
    {
        global $connect;


        // 본상품 바코드를 옵션상품 바코드에 복사
        $query = "update products a, products b
                     set a.barcode = b.barcode 
                   where a.org_id = b.product_id and
                         b.barcode > '' and
                         a.reg_date = '" . date("Y-m-d") . "'";
        mysql_query($query, $connect);
        
        // 본상품 바코드 삭제
        $query = "update products
                     set barcode=''
                   where is_represent = 1 and 
                         reg_date = '" . date("Y-m-d") . "'";
        mysql_query($query, $connect);
        
        // 중복상품명 옵션 합치기
        $query = "select product_id, name, count(*) cnt from products where substring(product_id,1,1)<>'S' and is_delete=0 group by name having cnt>1";
        $result = mysql_query($query, $connect);
        
        $total_cnt = mysql_num_rows($result);
        
        $i = 0;
        while( $data= mysql_fetch_assoc($result) )
        {
            $query_del = "delete from products where substring(product_id,1,1)<>'S' and product_id<>'$data[product_id]' and name='" . addslashes($data[name]) . "' and is_delete=0";
            mysql_query($query_del, $connect);
            
            $query_update = "update products set org_id='$data[product_id]' where substring(product_id,1,1)='S' and name='" . addslashes($data[name]) . "' and is_delete=0";
            mysql_query($query_update, $connect);
            
            if( $i++ % 10 == 0 )
                debug( $total_cnt . " / " . $i );
        }
        
        // 중복옵션 삭제
        $query = "select org_id, options, count(product_id) cnt from products where substring(product_id,1,1)='S' and is_delete=0 group by org_id, options having cnt>1";
        $result = mysql_query($query, $connect);
        while($data = mysql_fetch_assoc($result) )
        {
            $query_del = "delete from products where org_id=$data[org_id] and options='$data[options]' and is_delete=0 limit " . ($data[cnt] - 1);
            mysql_query($query_del, $connect);
        }
        
        // 삭제된 상품 가격 삭제
        $query = "delete a from price_history a left outer join products on a.product_id=b.product_id where b.product_id is null";
        mysql_query($query, $connect);
        
        // 삭제된 상품 원가 삭제
        $query = "delete a from org_price_history a left outer join products on a.product_id=b.product_id where b.product_id is null";
        mysql_query($query, $connect);
      

        $val['error'] = 0;
        echo json_encode($val);
    }  
    function order_reset()
    {
    	global $connect, $balju_check_date;
    	
    	$val = array();
    	if( !$this->balju_check($balju_check_date) )
            $val['error'] = 1;    
        else
        {
			$query = "DELETE FROM orders";
			$result = mysql_query($query, $connect);
			
			$val['error'] = 0;
		}
        echo json_encode($val);
    }   
    function product_reset()
    {
    	global $connect, $balju_check_date;
    	
    	$val = array();
		if( !$this->balju_check($balju_check_date) )
            $val['error'] = 1;    
        else
        {
			$query = "truncate org_price_history";
			$result = mysql_query($query, $connect);
			
			$query = "truncate price_history";
			$result = mysql_query($query, $connect);
			
			$query = "truncate products";
			$result = mysql_query($query, $connect);
			
			$query = "truncate current_stock";
			$result = mysql_query($query, $connect);
			
			$query = "truncate stock_tx";
			$result = mysql_query($query, $connect);
			
			$query = "truncate stock_tx_history";
			$result = mysql_query($query, $connect);
			$val['error'] = 0;
		}
        echo json_encode($val);
    }
    
    // 전체재고 초기화
    function reset_stock()
    {
        global $connect, $type;
        
        // Lock Check
        $obj_lock = new class_lock(201);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // 재고초기화 시점 생성
        class_IJ00::create_save_stock(1);

        $obj = new class_stock();
        
        $query = "select * from current_stock where stock<>0";
        
        if( $type == 1 )
            $query .= " and bad = 0";
        else if( $type == 2 )
            $query .= " and bad = 1";
        
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // input parameter
            $info_arr = array(
                type       => "arrange",
                product_id => $data[product_id],
                bad        => $data[bad],
                location   => 'Def',
                qty        => 0,
                memo       => "전체재고 초기화"
            );
            $obj->set_stock($info_arr);
        }
        
        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }
    }
    
    // 판매처별 송화인정보
    function set_trans_info_to_shop()
    {
        global $connect;
        global $base_sender_name, $base_sender_tel, $base_sender_zip, $base_sender_add1, $base_sender_add2;
        
        // 먼저 환경설정에 저장
        $query = "update ez_config 
                     set base_sender_name = '$base_sender_name',
                         base_sender_tel  = '$base_sender_tel',
                         base_sender_zip  = '$base_sender_zip',
                         base_sender_add1 = '$base_sender_add1',
                         base_sender_add2 = '$base_sender_add2'";
        mysql_query($query, $connect);
        
        // 전체 판매처에 저장
        $query = "update shopinfo
                     set trans_sender_name = '$base_sender_name',
                         trans_sender_tel  = '$base_sender_tel',
                         trans_sender_zip  = '$base_sender_zip',
                         trans_sender_add1 = '$base_sender_add1',
                         trans_sender_add2 = '$base_sender_add2'";
        mysql_query($query, $connect);
    }
    
    function collect_date_from_order_date()
    {
        global $connect, $balju_check_date;
        
        $val = array();        
        if( !$this->balju_check($balju_check_date) )
            $val['error'] = 1;    
        else
        {
            $query = "update orders set collect_date=order_date where order_date>'0000-00-00 00:00:00'";
            mysql_query($query, $connect);
            $val['error'] = 0;
        }
        echo json_encode($val);
    }
    function balju_check($date_diff)
    {
    	global $connect;
        
    	$query = "SELECT date(work_date) FROM order_process_log WHERE date(work_date) <= date_add(now(), interval -".$date_diff." day) GROUP BY date(work_date) LIMIT 10";    	
        $result = mysql_query($query, $connect);
        $row = mysql_num_rows($result);        
    	if( $row  > 0 )
            return false;
		return true;
    }
}

?>
