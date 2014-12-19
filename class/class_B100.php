<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_ui.php";
require_once "class_shop.php";
//require_once "class_autologin.php";
////////////////////////////////
// class name: class_B100
//

class class_B100 extends class_top {

    function reset_fail()
    {
        global $connect, $shop_id;
        
        $query = "update shopinfo set login_fail_count=0 where shop_id=$shop_id";
        mysql_query( $query, $connect );
        //echo $query;
    }
    // shopinfo의 설정내용을 가져온다.
    // 
    function get_shop_info( $shop_id )
    {
        global $connect;
        $query = "select * from shopinfo where shop_id='$shop_id'";
        $result = mysql_query($query, $connect );
        $data   = mysql_fetch_assoc( $result );
    }

    ///////////////////////////////////////////
    // shop들의 list출력

    function get_groupname( $group_id )
    {
        global $connect;
        $query = "select name from shop_group where group_id='$group_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        return $data[name];
    }

    // group 등록
    function reg_group()
    {
        global $connect, $name;
        
        $val = array();
        
        // 이미 등록된 그룹명인지 확인
        $query = "select * from shop_group where name='$name' ";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
        }   
        else
        {
            $query = "insert shop_group set name='$name'";
            mysql_query( $query, $connect);
            
            $val['error'] = 0;
        }
        
        echo json_encode($val);
    }

    // 그룹 정보 query
    function group_query()
    {
        global $connect, $name;
        
        //
        // shopinfo에서 group의 개수를 가져온다.
        $query = "select group_id, count(*) cnt from shopinfo group by group_id";
        $result = mysql_query( $query, $connect );
        $arr_cnt = array();
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_cnt[$data[group_id]] = $data[cnt];   
        }
        
        //
        // list 
        $query = "select * from shop_group where name like '%$name%'";
        $result = mysql_query ($query, $connect );

        $val = array();
        $val['list'] = array();

        while ( $data = mysql_fetch_array( $result ) )
        {
            $val['list'][] = array( 
                group_id   => $data[group_id], 
                name       => $data[name], 
                crdate     => $data[crdate],
                qty        => $arr_cnt[$data[group_id]] ? $arr_cnt[$data[group_id]] : 0
                );
        }
        echo json_encode( $val );
    }

    // 그룹 삭제
    function del_group()
    {
        global $connect, $group_id;
        
        $query = "select * from shop_group where group_id=$group_id";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc($result);

debug("판매처그룹삭제:$data[name]");

        // 그룹삭제
        $query = "delete from shop_group where group_id=$group_id";
        mysql_query( $query, $connect );
        
        // 해당 그룹의 판매처 그룹아이디 리셋
        $query = "update shopinfo set group_id=0 where group_id=$group_id";
        mysql_query( $query, $connect );
    }

    function B100()
    {
        global $connect;
        global $template, $group_id, $string;

        $sql = "select * from shopinfo where (shop_name like '%$string%' or shop_id like '%$string%')";
        if ( $group_id )
            $sql .= " and group_id=$group_id";
        $sql .= " order by disable, sort_name ";
        $result = mysql_query($sql, $connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    ///////////////////////////////////////////
    // B100 -> delte_shop
    // jk modify
    // 
    function delete()
    {
            global $connect,$shop_id;
            $sys_connect = sys_db_connect();
   
            // 해당 판매처의 주문이 있는지 확인한다.
            $query = "select * from orders where shop_id=$shop_id";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) > 0 )
            {
                echo "<script>
                        alert(\"해당 판매처에 발주된 주문이 있습니다. 주문을 모두 삭제한 후에 판매처를 삭제하세요\");
                        document.location.href = '?template=B103&shop_id=$shop_id';
                      </script>";
                exit;
            }
            $sql = "delete from shopinfo where shop_id  = '$shop_id'";
            mysql_query($sql, $connect) or die(mysql_error());
    
            $sql = "delete from shopheader where shop_id  = '$shop_id'";
            mysql_query($sql, $connect) or die(mysql_error());
    
            $sql = "delete from shop_transkey where shop_id  = '$shop_id'";
            mysql_query($sql, $connect) or die(mysql_error());
    
            $sql = "delete from shopupload_format where shop_id  = '$shop_id'";
            mysql_query($sql, $connect) or die(mysql_error());
    
            $sql = "delete from stat_rule2 where shop_id  = '$shop_id'";
            mysql_query($sql, $connect) or die(mysql_error());
    
            /////////////////////////////////////////
            // TRANS DB UPDATE (sy.hwang 2005.12.16)
            $sql = "delete from  ez_trans_shop
                     where userid = '"._DOMAIN_."'
                       and shop_id = '$shop_id'";
            mysql_query($sql, $sys_connect) or die(mysql_error());
            /////////////////////////////////////////
    
            // 매칭 정보도 같이 지워야 함
            $query = "delete from code_match where shop_id='$shop_id'";
            mysql_query($query, $connect) or die(mysql_error());
    
            echo "<script>document.location.href = '?template=B100';</script>";
            exit;
    }

    /////////////////////////////////////////////
    // 판매처 등록 수정
    // 2008.10.9 - jk
    // 5번 서버도 로긴해서 작업 해야 함.
    function reg_shop()
    {
        global $page,$connect, $userid, $passwd, $shop_id, $shop_name, $sort_name, $admin_url, $url, $code_url, 
               $promotion_id, $group_id, $auth_code, $logo,$stock_sync, $is_deliv_global, $balju_stop,$disable,$string,$is_auto_confirm, $use_auto_cancel,
               $use_trans_add, $trans_address, $trans_tel, $trans_zip, $trans_name,$trans_link_code1,$trans_link_code2,$box_type,
               $trans_sender_name,$trans_sender_tel,$trans_sender_zip,$trans_sender_add1,$trans_sender_add2, $charge, $margin, $auto_price,
               $print_product_name, $print_option;
        
        $str_print_product_option = "product_option";
		if ( $print_product_name == 1 && $print_option == 1)
		    $str_print_product_option = "product_option";
        else if ( $print_option == 1 )
		    $str_print_product_option = "option";
        else
		    $str_print_product_option = "product";
		        
        $stock_sync = $stock_sync ? $stock_sync : 0;
        $sys_connect = sys_db_connect();         // 6번 서버에 로긴..

        $link_url = "?" .  $this->build_link_url();

        $sort_name = ($sort_name ? $sort_name : $shop_name);
        
        $is_auto_confirm = $is_auto_confirm ? $is_auto_confirm : 0;
        $query = "insert shopinfo set 
                         shop_name            = '$shop_name', 
                         sort_name            = '$sort_name', 
                         userid               = '$userid', 
                         passwd               = '$passwd', 
                         admin_url            = '$admin_url', 
                         url                  = '$url',
                         code_url             = '" . trim($code_url) . "',
                         promotion_id         = '$promotion_id',
                         group_id             = '$group_id',
                         shop_id              = '$shop_id',
                         disable              = '$disable',
                         balju_stop           = '$balju_stop',
                         use_trans_add        = '$use_trans_add',
                         trans_address        = '$trans_address',
                         trans_tel            = '$trans_tel',
                         trans_zip            = '$trans_zip',
                         trans_name           = '$trans_name',
                         charge               = '$charge',
                         margin               = '$margin',
                         print_product_option = '$str_print_product_option',
                         auto_price           = '$auto_price',";
        if ( $auth_code )
            $query .= "  auth_code            = '$auth_code',";

		if ( !$use_auto_cancel )
            $query .= "  use_auto_cancel      = '0',";

		if ( !$is_auto_confirm )
			$query .= "  is_auto_confirm      = '0',";

        // 지마켓 해외 배송 - 2010-9-13 jk        
        $is_deliv_global = $is_deliv_global ? $is_deliv_global : 0;
        $query .= " is_deliv_global    ='$is_deliv_global',";

        if( $_SESSION[USE_SENDER_INFO] )
        {
            $query .= " trans_sender_name = '$trans_sender_name',
                        trans_sender_tel  = '$trans_sender_tel',
                        trans_sender_zip  = '$trans_sender_zip',
                        trans_sender_add1 = '$trans_sender_add1',
                        trans_sender_add2 = '$trans_sender_add2', ";
        }

        $query .= "logo='$logo',
                   trans_link_code1 = '$trans_link_code1',
                   trans_link_code2 = '$trans_link_code2',
                   box_type = '$box_type'
                   ";
        mysql_query ( $query , $connect);
debug("판매처등록 : " . $query);        
        /////////////////////////////////////////
        
        $shop_short = $shop_id % 100;
        
        // 옥션,지마켓 이베이스마트
        if( $shop_short == 78 )  $shop_short = 1;
        if( $shop_short == 79 )  $shop_short = 2;
        
        /////////////////////////////////////////
        // 발주 헤더 복사
        $sql = "select * from sys_shopheader where shop_id = $shop_short ";
        $result = mysql_query( $sql, $sys_connect );
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert shopheader
                         set shop_id     = $shop_id,
                             field_id    = '$data[field_id]',
                             field_name  = '$data[field_name]',
                             shop_header = '$data[shop_header]',
                             abs         = $data[abs]";
            mysql_query( $query, $connect );
        }
        /////////////////////////////////////////
        
        /////////////////////////////////////////
        // 배송비 키워드 설정 복사
        $sql = "select * from sys_shop_transkey where shop_id = $shop_short ";
        $result = mysql_query( $sql, $sys_connect );
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert shop_transkey
                         set shop_id  = $shop_id,
                             space    = $data[space],
                             keyword  = '$data[keyword]',
                             transwho = $data[transwho]";
            mysql_query( $query, $connect );
        }
        /////////////////////////////////////////
                  
        /////////////////////////////////////////
        // 정산 룰 복사
        $sql = "select * from sys_stat_rule where shop_code = $shop_short ";
        $result = mysql_query( $sql, $sys_connect );
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert stat_rule2
                         set priority         = 0,
                             enable           = 1,
                             supply_id        = 0,
                             shop_id          = $shop_id,
                             from_date        = now(),
                             to_date          = '" . date('Y-m-d', strtotime('+10 year')) . "',
                             shop_product_id  = '',
                             product_id       = '',
                             supply_price     = '$data[supply_price]',
                             supply_percent   = 0,
                             amount           = '$data[amount]',
                             prepay_trans     = '$data[prepay_trans]',
                             title            = '" . class_shop::get_shop_name($shop_id) . " 기본 정산룰" . "',
                             reg_date         = now(),
                             owner            = '" . $_SESSION[LOGIN_NAME] . "'";
            mysql_query( $query, $connect );
        }

        /////////////////////////////////////////
        // 판매처 송장 업로드 포멧
        $sql = "select * from sys_shopupload_format where shop_id = $shop_short ";
        $result = mysql_query( $sql, $sys_connect );
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert shopupload_format
                         set shop_id    = $shop_id,
                             type       = '$data[type]',
                             seq        = '$data[seq]',
                             header     = '$data[header]',
                             value      = '$data[value]',
                             user_value = '$data[user_value]'";
            mysql_query( $query, $connect );
        }
        /////////////////////////////////////////

        $this->redirect( $link_url );
        exit;
    }


    /////////////////////////////////////////////
    // promotion_id
    function modify()
    {
        global $page,$connect, $userid, $passwd, $shop_id, $shop_name, $sort_name, $admin_url, $url, $code_url, 
               $promotion_id, $group_id, $auth_code, $logo,$stock_sync, $is_deliv_global, $balju_stop,$disable,$string,$is_auto_confirm,$use_auto_cancel,
               $use_trans_add, $trans_address, $trans_tel, $trans_zip, $trans_name,$trans_link_code1,$trans_link_code2,$box_type,
               $trans_sender_name,$trans_sender_tel,$trans_sender_zip,$trans_sender_add1,$trans_sender_add2, $charge, $margin, $auto_price, $shop_cross_check,
               $print_product_name, $print_option, $use_ezlogin, $id_place, $password_place, $btn_place, $skip_deal, $admin, $md, $deal_selected,$ez_admin,$ez_md;

		$str_print_product_option = "product_option";
		if ( $print_product_name == 1 && $print_option == 1)
		    $str_print_product_option = "product_option";
        else if ( $print_option == 1 )
		    $str_print_product_option = "option";
        else
		    $str_print_product_option = "product";

/*        
        echo "stock_sync : $stock_sync <br>";
        
        $stock_sync = $stock_sync ? $stock_sync : 0;
        
        echo "stock_sync : $stock_sync <br>";
*/        
        $sys_connect = sys_db_connect();

        $link_url = "?" .  $this->build_link_url();
        
        // 교차발주검사
        $query = "select * from shop_cross_check where shop_id = $shop_id % 100 ";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $data = mysql_fetch_assoc($result);
            
            // 기존에 미사용에서 사용으로 변경
            if( $data[not_use] && $shop_cross_check )
                $query = "update shop_cross_check set not_use=0, crdate=now(), worker='$_SESSION[LOGIN_NAME]' where shop_id = $shop_id % 100";
            // 기존 사용에서 미사용으로 변경
            else if( !$data[not_use] && !$shop_cross_check )
                $query = "update shop_cross_check set not_use=1, crdate=now(), worker='$_SESSION[LOGIN_NAME]' where shop_id = $shop_id % 100";

            mysql_query($query, $connect);
        }
        // 기존 사용에서 미사용으로 변경
        else if( !$shop_cross_check )
        {
            $query = "insert shop_cross_check set shop_id = $shop_id % 100, not_use=1, crdate=now(), worker='$_SESSION[LOGIN_NAME]' ";
            mysql_query($query, $connect);
        }
       
        $is_auto_confirm = $is_auto_confirm ? $is_auto_confirm : 0;
		$use_auto_cancel = $use_auto_cancel ? $use_auto_cancel : 0;

        $query = "update shopinfo set 
                         shop_name       = '$shop_name', 
                         sort_name       = '$sort_name', 
                         userid          = '$userid', 
                         passwd          = '$passwd', 
                         admin			 = '$admin',
                         md				 = '$md',
                         ez_admin		 = '$ez_admin',
                         ez_md			 = '$ez_md',
                         admin_url       = '$admin_url', 
                         url             = '$url',
                         code_url        = '" . trim($code_url) . "',
                         promotion_id    = '$promotion_id',
                         group_id        = '$group_id',
                         balju_stop      = '$balju_stop',
                         disable         = '$disable',
                         is_auto_confirm = '$is_auto_confirm',
						 use_auto_cancel = '$use_auto_cancel',
                         use_trans_add   = '$use_trans_add',
                         trans_address   = '$trans_address',
                         trans_tel       = '$trans_tel',
                         trans_zip       = '$trans_zip',
                         trans_name      = '$trans_name',
                         charge          = '$charge',
                         margin          = '$margin',
                         login_fail_count = 0,
                         trans_link_code1 = '$trans_link_code1',
                         trans_link_code2 = '$trans_link_code2',
                         box_type        = '$box_type',
                         print_product_option = '$str_print_product_option',
                         auto_price      = '$auto_price',
						 skip_deal		 = '$skip_deal',
						 deal_selected   = '$deal_selected'";

         // ezlogin을 사용해 함 
        if ( $_SESSION[LOGIN_LEVEL] == 9 )
        {
            $query .= ",use_ezlogin     = '$use_ezlogin' ";
            $query .= ",id_place        = '$id_place' ";
            $query .= ",password_place  = '$password_place' ";
            $query .= ",btn_place       = '$btn_place' ";
        }

/////////////////////////////////////////////////////////////////////////////
// 14.07.11 찬영선배 요청으로 주석처리..
// 보안코드 공백입력시 update 안쳐짐.
// 14.07.11 최웅 
// 레벨9가 아닌 사용자가 저장할경우 auth 가 아예 안들어옴..
// 다시 품
        if ( $auth_code != "")
            $query .= " ,auth_code    ='" . trim($auth_code) ."'";
/////////////////////////////////////////////////////////////////////////////
        
        // 지마켓 해외 배송 - 2010-9-13 jk        
        $is_deliv_global = $is_deliv_global ? $is_deliv_global : 0;
        $query .= " ,is_deliv_global    ='$is_deliv_global'";
        
        if( $_SESSION[USE_SENDER_INFO] )
        {
            $query .= ",trans_sender_name = '$trans_sender_name',
                        trans_sender_tel  = '$trans_sender_tel',
                        trans_sender_zip  = '$trans_sender_zip',
                        trans_sender_add1 = '$trans_sender_add1',
                        trans_sender_add2 = '$trans_sender_add2' ";
        }
        
        if ( $logo )
            $query .= " ,logo         ='$logo'";

        $query .= " where shop_id='$shop_id'";
        
        //echo $query;
        //exit;
debug( "판매처 정보 수정 : " . $query );
        mysql_query ( $query , $connect);

/* => 더이상 사용 안하는 로직 by 류재관. 2011-07-04 장경희 작업
        /////////////////////////////////////////
        // TRANS DB UPDATE (sy.hwang 2005.12.16)
        $sql = "select count(*) cnt from ez_trans_shop where userid = '"._DOMAIN_."' and shop_id = '$shop_id'";
        $list = mysql_fetch_array(mysql_query($sql, $sys_connect));
        
        if ($list[cnt] > 0)
        {
          $sql = "update ez_trans_shop set
                  shop_name    = '" . iconv('utf-8','cp949',$shop_name) . "',
                  login_id     = '$userid',
                  login_pwd    = '$passwd',";
                  
          if ( $auth_code )
              $sql .= " auth_code    = '$auth_code',";
                  
          $sql .= " code1        = 'http:\/\/$url'
                 where userid  = '"._DOMAIN_."'
                   and shop_id = '$shop_id'";
        }
        else
        {
          $sql = "insert into ez_trans_shop set
                  userid    = '"._DOMAIN_."',
                  shop_id   = '$shop_id',
                  shop_name = '" . iconv('utf-8','cp949',$shop_name) . "',
                  login_id  = '$userid',
                  code1     = 'http:\/\/$url',";
                  
          if ( $auth_code )
              $sql .= "auth_code = '$auth_code',";
              
          $sql .= " login_pwd = '$passwd'";
        }
        
        mysql_query($sql, $sys_connect) or die(mysql_error());
        /////////////////////////////////////////
*/
        $this->redirect( "?template=B103&shop_id=$shop_id&page=$page&string=$string" );
        exit;
    }

    ///////////////////////////////////////////
    // B101
    function B101()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }



    ///////////////////////////////////////////
    // B102
    function B102()
    {
        global $connect;
        global $template;

        $shop_id = $_GET[shop_id];

        $sql = "select * from shopinfo where shop_id = '$shop_id'";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    // B103
    function B103()
    {
        global $connect;
        global $template;

        $shop_id = $_REQUEST[shop_id];

        $sql = "select * from shopinfo where shop_id = '$shop_id'";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);

        $promotion_id = $list[promotion_id];
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    function B104()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function B105()
    {
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function B106()
    {
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function B107()
    {
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function test()
    {
        global $template;

        echo "<script language='javascript'>parent.show_waiting()</script>";  
        flush();

        for($i=0; $i<10; $i++)
        {
            echo "<script language='javascript'>parent.show_txt( $i )</script>";  
            flush();

            debug("test $i  ");
            sleep(1);
        }

        echo "<script language='javascript'>parent.hide_waiting()</script>";  
        flush();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function check_base_shopinfo()
    {
        global $connect, $sys_connect, $shop_id;
        
        $val = array();
    	$val['new_shop_id'] = $shop_id;

        $shop_id = $shop_id % 100;
        
        $sql = "select count(*) cnt from shopinfo where (shop_id%100 = $shop_id)";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);
        
        $val['cnt'] = $list[cnt];
        if ($list[cnt]> 0)
        {
        	$sql = "select max(shop_id) max_id from shopinfo where (shop_id%100 = $shop_id)";
        	$list1 = mysql_fetch_array(mysql_query($sql, $connect));
        	$val['new_shop_id'] = $list1[max_id] + 100;
        }

        // shop 기본정보
    	$base_shopid = 10000 + $shop_id;
    	$sql = "select shop_name, url, logo from sys_shopinfo2 where shop_id = '$base_shopid'";
    	$list2 = mysql_fetch_array(mysql_query($sql, $sys_connect));
    	
    	$val['shop_name'] = $list2[shop_name];
    	$val['url'] = $list2[url];
    	$val['logo'] = $list2[logo];
    	
    	echo json_encode( $val );
    }

    function check_user_shopinfo()
    {
        global $connect, $sys_connect, $shop_id;
        
        $val = array();
    	$val['new_shop_id'] = $shop_id;

        $shop_id = $shop_id % 100;
        
        $sql = "select count(*) cnt from shopinfo where (shop_id%100 = $shop_id)";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);
        
        $val['cnt'] = $list[cnt];
        if ($list[cnt]> 0)
        {
        	$sql = "select max(shop_id) max_id from shopinfo where (shop_id%100 = $shop_id)";
        	$list1 = mysql_fetch_array(mysql_query($sql, $connect));
        	$val['new_shop_id'] = $list1[max_id] + 100;
        }

        // shop 기본정보
    	$base_shopid = 10000 + $shop_id;
    	$sql = "select shop_name, url, logo from sys_shopinfo2 where shop_id = '$base_shopid'";
    	$list2 = mysql_fetch_array(mysql_query($sql, $sys_connect));
    	
    	$val['shop_name'] = $list2[shop_name];
    	$val['url'] = $list2[url];
    	$val['logo'] = $list2[logo];
    	
    	echo json_encode( $val );
    }
    
    function update_request_info()
    {
        global $connect, $group_name, $group_id;

        $val = array();
        
        $query = "update shop_group set name ='$group_name' where group_id = '$group_id'";
        $result = mysql_query($query,$connect);
          
        $val["error"] = 0;
        echo json_encode($val);
    }


	//----------------------------------------
	//-- 암호화 세팅 
	function encode_data()
	{
		global $connect;

		$shop_id = $_REQUEST[shop_id];
		$upd_sql = "update shopinfo set is_data_encoded = 1 where shop_id = '$shop_id'";
		mysql_query($upd_sql, $connect) or die(mysql_error());
		debug($upd_sql);

		echo "암호화설정이 성공적으로 저장되었습니다.";
	}
	
	//----------------------------------------
	//-- 사입업체 설정
	function B110()
    {
        // 추후 추가
        //global $connect;
        global $template;

        // 추후 삭제
        $connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        $sql = "select * from ez_config";
        $result = mysql_query($sql, $connect10) or die(mysql_error());
        $list = mysql_fetch_assoc($result);
        
        // 추후 sys계정으로 연결
        $sql = "select * from sys_saip where saip_seq = '$list[saip_seq]'";
        $saip_list = mysql_fetch_assoc(mysql_query($sql, $connect10));
        $name = $saip_list[name];

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //----------------------------------------
	//-- 사입업체 찾기 팝업
    function B111()
    {
        global $connect;
        global $template, $string;
        
        // 추후 sys계정으로 연결
        //$connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        //$connect10 = mysql_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        //mysql_select_db("ejjung", $connect10);
        
        $sql = "select * from sys_saip where name like '%${string}%' order by name";
        $result = mysql_query($sql, $connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    
    //----------------------------------------
	//-- 사용할 사입업체 저장
    function saip_save()
    {
        //global $connect;
        global $template;
        
        foreach($_REQUEST as $key => $val) $$key = $val;
  
        $connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        
        if($use_saip == 1){
            // 사입업체을 변경했을 경우
            if($prev_saip_seq != 0){
                // 시스템 DB 연결
                //$sys_con = sys_db_connect();
                
                // 이전 사입업체 DB연결을 위해 sys계정 연결
                $sql = "select * from sys_saip where saip_seq = '$prev_saip_seq'";
                $list = mysql_fetch_assoc(mysql_query($sql, $connect10));
                //$list = mysql_fetch_assoc(mysql_query($sql, $sys_con));
                
                // 사입업체 DB 연결
                //$saip_connect = $this->temp_sys_db_connect($list[host], $list[db_id], $list[db_name], $list[db_name]);
                
                // 사입업체 DB 연결 후 사입업체 고객 테이블에서 삭제
                $sql = "delete from saip_customer where id = '$_SESSION[LOGIN_DOMAIN]'";
                //mysql_query($sql, $saip_connect);
                mysql_query($sql, $connect10);
            }
            
            // 시스템 DB 연결
            //$sys_con = sys_db_connect();
            
            // 사입을 요청한 고객의 정보를 sys_domain에서 가져오기
            $sql = "select * from sys_domain where id = '$_SESSION[LOGIN_DOMAIN]'";
            //$userinfo = mysql_fetch_assoc(mysql_query($sql, $sys_con));
            $userinfo = mysql_fetch_assoc(mysql_query($sql, $connect10));
            
            // 새로운 사입업체 DB연결을 위해 sys계정 연결
            $sql = "select * from sys_saip where saip_seq = '$saip_seq'";
            //$list = mysql_fetch_assoc(mysql_query($sql, $sys_con));
            $list = mysql_fetch_assoc(mysql_query($sql, $connect10));
            
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
            mysql_query($sql, $connect10);
            //mysql_query($sql, $saip_connect);
        }else if($use_saip == 0){
            if($prev_saip_seq != 0){
                // 이전 사입업체 DB연결을 위해 sys계정 연결
                $sql = "select * from sys_saip where saip_seq = '$prev_saip_seq'";
                $list = mysql_fetch_assoc(mysql_query($sql, $connect10));
                
                // 사입업체 DB 연결 후 사입업체 고객 테이블에서 삭제
                // 연결했다고 가정
                // $sys_connect ...
                //$saip_connect = $this->temp_sys_db_connect($list[host], $list[db_id], $list[db_name], $list[db_name]);
                $sql = "delete from saip_customer where id = '$_SESSION[LOGIN_DOMAIN]'";
                //mysql_query($sql, $saip_connect);
                mysql_query($sql, $connect10);
            }
        }
        
        // 환경설정 테이블에 저장
        if($id){
            $sql = "update ez_config
                    set use_saip = '$use_saip',
                        saip_seq = '$saip_seq'
                    where id='$id'";
            mysql_query($sql, $connect10);
        }else{
            $sql = "insert into ez_config
                    set use_saip = '$use_saip',
                        saip_seq = '$saip_seq'";
            mysql_query($sql, $connect10);
        }
        
        echo "<script>document.location.href='?template=$template';</script>";
    }
    
    function temp_sys_db_connect($host, $db_id, $db_pw, $db_name)
    {
        $connect10 = mysql_connect($host, $db_id, $db_pw, $db_name);
        mysql_select_db($db_name, $connect10);
        
        $charset="utf8";
        mysql_query("set session character_set_connection=${charset};", $connect10);
        mysql_query("set session character_set_results=${charset};", $connect10);                                                                                                                     
        mysql_query("set session character_set_client=${charset};", $connect10);
        
        return $connect10;
    }
}

?>
