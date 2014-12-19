<?
//========================================
//
// ezadmin¿¡¼­ 3plÀ» »ç¿ëÇÏ±â À§ÇØ 
// date: 2007.11.9 - jk.ryu
// unit test: unit_test/test_3pl.php
//
require_once "class_db.php";
require_once "class_product.php";
require_once "class_order.php";
require_once "class_C.php";
require_once "class_E.php";
// require_once "class_3pl_api.php";

class class_3pl{
    var $m_connect = "";

    // 3pl °´Ã¼¸¦ »ý¼ºÇÏ¸é ¹Ù·Î 3pl¼­¹ö¿¡ connectÇÔ
    function class_3pl()
    {
            if ( $_SESSION )
        {
                $_server = $_SESSION[DBSERVER_3PL];
                $_name   = $_SESSION[DBNAME_3PL];
                $_pass   = $_SESSION[DBPASS_3PL];

                $this->connect( $_server, $_name, $_pass );
        }
    }
  
    // 3pl»óÇ° »èÁ¦ 
    // 2009.5.2 - jk
    function product_delete( $product_id )
    {
        // ¿ø »óÇ° »èÁ¦
        $query = "delete from 3pl_products where product_id='$product_id'";
        mysql_query ( $query, $this->m_connect );

        // ¿É¼Ç »óÇ° »èÁ¦
        $query = "delete from 3pl_products where org_id='$product_id'";
        mysql_query ( $query, $this->m_connect );
    }


    function check_product_delete()
    {
        echo "haha";
    }

    function save_file()
    {
        echo "class_3pl::save_file()";
    }

    function connect( $host, $name, $pass )
    {
        $obj = new class_db();

        $this->m_connect = $obj->connect( $host, $name, $pass );
        return $this->m_connect;
    }

    /////////////////////////////////////// 
    // ÀÔ°í ±âÁØ ¸®½ºÆ® Ãâ·Â
    // 2008.10.07 - jk
    function get_stockin_list( &$arr_return )
    {
        global $start_date, $end_date, $start;
        $start = $start ? $start : 0;

        $query       = "select sum(a.qty) qty, a.product_id, b.product_name, b.options, a.product_id ";
        $query_cnt   = "select count( distinct(a.product_id )) cnt ";
        $options     = " from 3pl_stock_in a, 3pl_products b
                       where a.product_id = b.product_id
                         and a.start_date >= '$start_date 00:00:00'
                         and a.start_date <= '$end_date 23:59:59'
                         and a.domain      = '" . _DOMAIN_ . "'";

//debug ( "[get_stockin_list] $query_cnt $options");
        // count
        $result  = mysql_query ( $query_cnt . $options, $this->m_connect );
        $data    = mysql_fetch_array ( $result );
        // total °³¼ö return
        $arr_return[total_rows] = $data[cnt];        

        // total list return
        $options.= " group by a.product_id order by qty desc limit $start, 20";
//debug ( "[get_stockin_list] $query $options");
        $arr_return[result] = mysql_query ( $query . $options, $this->m_connect );
    }

    /////////////////////////////////////// 
    // È¸¼ö ¿äÃ» µî·Ï
    // 2008.9.29 - jk
    function regist_takeback( $seq, $product_id, $qty )
    {
        $query = "insert 3pl_takeback 
                     set seq           = '$seq',
                         seq_subid     = 1,
                         product_id    = '$product_id',
                         takeback_date = Now(),
                           req_qty       = '$qty',
                         status        = 0,
                         domain        ='" ._DOMAIN_ ."'";
        $result = mysql_query ( $query, $this->m_connect );
    }

    // 3pl ¼ÛÀå Ãâ·Â °¡´ÉÇÑ »óÇ° °³¼ö
    // 2008.9.30 - jk
    function get_printable_products( $warehouse )
    {
        $query = "select product_id, sum(qty) cnt, location,warehouse 
                    from 3pl_print_enable 
                   where domain='". _DOMAIN_ ."'";

        if ( $warehouse )
            $query .= " and warehouse='$warehouse'";

              $query .= " group by product_id";

debug ( $query );

        return mysql_query ( $query, $this->m_connect );
    }

    //=====================================
    // ¹Ì¹è¼Û Á¶È¸
    // 2008.10.2 - jk
    function not_trans_list( &$arr_return, $_flag="limit" )
    {
        global $page, $use_3pl;
        global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;
        $name    = iconv("UTF-8", "CP949", $name );
        $options = iconv("UTF-8", "CP949", $options );

        $page = $page ? $page : 1;
        $_starter = ($page - 1) * 20;

        ///////////////////////////////////////////////////////////
        // µ¥ÀÌÅÍ´Â °ªÀÌ ÀÖ´Ù°í °¡Á¤ÇÔ
        // Àç°í °ª(Logic 1)°ú ¹è¼Û °³¼ö(Logic 2)°¡ °ªÀÌ ¾øÀ» °æ¿ì¿¡´Â is_nodata=1
        $is_nodata = 0;

        /////////////////////////////////////////////////////////
        //
        // ½ÇÁ¦ »óÇ° Á¤º¸ queryÇÏ´Â ºÎºÐ
        $query  = "select product_id, sum(qty) qty , product_name as name, options
                     from 3pl_orders 
                    where (status <> 8 and order_cs not in (1,2,3,4,12) )
                      and domain='" . _DOMAIN_ . "' ";

        if ( $name )
            $option .= " and name like '%$name%'";

        // ¿É¼Ç °ªÀÌ ÀÖÀ» °æ¿ì 
        if ( $options )
            $option .= " and options like '%$options%'";


        // °ø±Þ¾÷Ã¼ ÄÚµå°¡ ÀÖ´Â °æ¿ì
        if ( $supply_code )
            $option .= " and supply_code = '$supply_code'";


        // »óÇ° ÄÚµå ¸®½ºÆ® °ªÀÌ ÀÖ´Â °æ¿ì
        if ( $product_id )
            $option .= " and product_id = '$product_id'"; 

        //////////////////////////////////////////////////////////
        // count 
        $query_cnt  = "select count(distinct product_id) cnt 
                     from 3pl_orders
                    where (status <> 8 
                      and order_cs not in (1,2,3,4,12) ) 
                      and domain='" ._DOMAIN_ . "' ";

        $result    = mysql_query ( $query_cnt . $option, $this->m_connect );
        $data      = mysql_fetch_array( $result );
        $arr_return[total_rows] = $data[cnt];

        $option .= " group by product_id";

        ///////////////////////////////////////////////////////////
        if ( $_flag == "limit" )
        {
            global $start;
            $start = $start ? $start : 0;                
            $option .= " limit $start, 20";
        }

debug ( "[not_trans_list] $query  $option" );
        $result = mysql_query ( $query . $option , $this->m_connect );
        $arr_return[result] = $result;
    }

    //==================================
    // ¼ÛÀå ¼ÛÅÂÀÇ ÁÖ¹® Á¤º¸ °¡Á®¿Â´Ù
    // 2008.9.30 - jk
    function get_trans_order( $warehouse )
    {
        $query = "select product_id, sum(qty) cnt, warehouse, location
                    from 3pl_orders
                   where status=7 
                     and order_cs not in (1,2,3,4,12)";

        if ( $warehouse )
            $query .= " and warehouse='$warehouse'";

        $query .= " group by product_id";
        return mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö ¿äÃ» Ãë¼Ò
    // 2008.9.29 - jk
        /*
    function cancel_takeback( $seq )
    {
        $query = "delete from 3pl_takeback
                   where seq    = '$seq' 
                     and domain = '"._DOMAIN_."'";
        mysql_query ( $query, $this->m_connect );
    }
        */

    // È¸¼ö ¼ÛÀå µî·Ï 
    // 2008.9.29 - jk
    function  regist_takeback_transno( $seq, $trans_no, $trans_who, $trans_corp )
    {
        $query = "update 3pl_takeback 
                     set trans_no   = '$trans_no',
                         trans_who  = '$trans_who',
                         trans_corp = '$trans_corp',
                         status     = 7,
                         trans_date = Now()
                   where domain     = '"._DOMAIN_."'
                     and seq        = $seq";
        debug ( $query );
        mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö ¿Ï·á
    // 2008.9.29 - jk
    function  takeback_complete ( $seq )
    {
        $query = "update 3pl_takeback 
                     set status         = 8,
                         trans_date_pos = Now()
                   where domain         = '"._DOMAIN_."'
                     and seq            = $seq";
        debug ( $query );
        mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö Á¢¼ö
    // 2008.10.11 - jkh
    function  reg_takeback($pack , $seq, $product_id, $number, $invoice, $address, $return, $trans_who, $refund_req, $bank_req, $qty_req)
    {
        $query = "insert 3pl_takeback 
                     set pack          = '$pack', 
                         order_seq     = '$seq', 
                         product_id    = '$product_id',
                         domain        = '"._DOMAIN_."', 
                         number        = '$number', 
                         invoice       = '$invoice', 
                         address       = '$address', 
                         receive_date  = now(), 
                         who_receive   = '"._DOMAIN_."', 
                         trans_who     = '$trans_who', 
                         refund_req    = '$refund_req', 
                         bank_req      = '$bank_req', 
                         qty_req       = '$qty_req',
                         reason_req    = '$return',
                         status        = 1";
        mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö Ãë¼Ò
    // 2008.10.11 - jkh
    function  cancel_takeback( $seq )
    {
            $query = "delete from 3pl_takeback where order_seq = $seq and domain = '"._DOMAIN_."'";
        mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö ¼ÛÀå µî·Ï
    // 2008.10.13 - jkh
    function  takeback_trans_no( $seq, $trans_no, $trans_corp, $memo )
    {
            $query = "update 3pl_orders set tb_status=2, tb_trans_no='$trans_no', tb_trans_corp=$trans_corp, tb_regist_date=now(), 
                      tb_memo='$memo' where seq = '$seq' and domain = '"._DOMAIN_."'";
        mysql_query ( $query, $this->m_connect );
    }

    // È¸¼ö ¿Ï·á
    // 2008.10.13 - jkh
    function  complete_takeback( $seq, $status, $qty_get, $refund_get, $memo )
    {
            $query = "update 3pl_orders set tb_status=$status, tb_qty_get=$qty_get, tb_refund_get=$refund_get, tb_pos_date=now(), 
                      tb_memo='$memo' where seq = '$seq' and domain = '"._DOMAIN_."'";
        mysql_query ( $query, $this->m_connect );
    }

    // Ã¢°í Á¤º¸ °¡Á®¿È
    // 2008.7.23 - jk
    function get_warehouse()
    {
        $query = "select * from 3pl_warehouse";
        $result = mysql_query ( $query, $this->m_connect );
        return $result;
    }

    // ´Ü¼ø Á¤º¸ °¡Á®¿È
    function get_info( $seq )
    {
        $query = "select * 
                    from 3pl_orders 
                   where seq    = '$seq' 
                     and domain = '" . _DOMAIN_ . "'";

        $result = mysql_query ( $query, $this->m_connect );
        $data   = mysql_fetch_array ( $result );
        return $data;
    }

    //////////////////////////////////////////////
    // ÀÔ°í Á¤º¸ Á¶È¸2 
    // »óÇ°º° ÀÔ°í °³¼ö Á¶È¸
    // date: 2008.4.17 - jk
    function get_stock_in_history2( $start_date, $end_date, $stockin_cnt=0 , $arr_product )
    {
        // Àç°í Á¤º¸ °¡Á®¿È
        $query = "select *, sum(qty) sum_qty 
                    from 3pl_stock_in
                   where domain     = '" . _DOMAIN_ . "'
                     and start_date >= '$start_date'
                     and start_date <= '$end_date'";

        ////////////////////////////////////////////////////
        for ( $j=0; $j < count( $arr_product); $j++ )
        {
            if ( $j == 0 ) $query .= " and product_id in (";
            if ( $j > 0 ) $query .= ",";
            $query.= "'" . $arr_product[$j] . "'";
            if ( $j == count($arr_product) - 1 ) $query .= ") ";
        }
        ////////////////////////////////////////////////////

        $query .= " group by product_id";

        if ( $stockin_cnt )
            $query .= " having sum(qty) >= $stockin_cnt";        

//debug ( "[get_stock_in_history2] $query ");

        $result = mysql_query ( $query, $this->m_connect );
        return $result;
    }

    //////////////////////////////////////////////
    // ÀÔ°í Á¤º¸ Á¶È¸
    // date: 2008.2.22 - jk
    function get_stock_in_history( $product_id='', $start_date, $end_date, $stockin_cnt=0 )
    {
        // Àç°í Á¤º¸ °¡Á®¿È
        $query = "select *, sum(qty) sum_qty 
                    from 3pl_stock_in
                   where domain     = '" . _DOMAIN_ . "'
                     and start_date >= '$start_date'
                     and start_date <= '$end_date'";

        if ( $product_id )        
            $query .= " and product_id = '$product_id' ";

        $query .= " group by start_date";

        if ( $stockin_cnt )
            $query .= " having sum(qty) >= $stockin_cnt";        

        $result = mysql_query ( $query, $this->m_connect );
        return $result;
    }


    //////////////////////////////////////////////
    // Àç°í Á¤º¸ Á¶È¸
    // date: 2008.2.22 - jk
    function get_stock_history( $product_id, $start_date, $end_date )
    {
debug ( "get_stock_history start" );
        // Àç°í Á¤º¸ °¡Á®¿È
        $query = "select * from 3pl_stock_history 
                   where domain     = '" . _DOMAIN_ . "'
                     and crdate    >= '$start_date'
                     and crdate    <= '$end_date'
                     and product_id = '$product_id'";

debug ( "[get_stock_history] $query " );

        $result = mysql_query ( $query, $this->m_connect );
        return $result;
    }

    ////////////////////////////////////////////////
    // Á¶°Ç °Ë»ö °¡´ÉÇÑ 3pl Àç°í ¸®½ºÆ®
    function get_stock_history2( $arr_info )
    {
	// Àç°í Á¤º¸ °¡Á®¿È
        $query = "select * from 3pl_product_location
                   where domain     = '" . _DOMAIN_ . "'";

        ////////////////////////////////////////////////////
        $arr_product = $arr_info[arr_product];
        for ( $j=0; $j < count( $arr_product); $j++ )
        {
            if ( $j == 0 ) $query .= " and product_id in (";
            if ( $j > 0 ) $query .= ",";
            $query.= "'" . $arr_product[$j] . "'";
            if ( $j == count($arr_product) - 1 ) $query .= ") ";
        }

        if ( $arr_info[product_id] )
           $query .= " and product_id = '$arr_info[product_id]'";

        $query .= " group by product_id ";

        // Àç°í °³¼ö Á¤ÀÇµÈ °³¼ö ÀÌ»ó 
        if ( $arr_info[current_stock_over] or $arr_info[current_stock_below] )
        {
            $query .= " having stock > 0 ";

            if ( $arr_info[current_stock_over] )
                $query .= " and sum(stock) >= '$arr_info[current_stock_over]'";

            // Àç°í °³¼ö Á¤ÀÇµÈ °³¼ö ÀÌÇÏ
            if ( $arr_info[current_stock_below] )
                $query .= " and sum(stock) <= '$arr_info[current_stock_below]'";
        }

        debug ( "[get_stock_history2] $query" );
        //if ( $_SESSION[LOGIN_LEVEL] == 9 ) exit;

        $result = mysql_query ( $query, $this->m_connect );
        return $result;
    }

    ///////////////////////////////////////////////
    // 2008.9.8 - jk
    // »óÇ°ÀÌ 3pl·Î Àü¼ÛµÇ¾î »ç¿ëµÇ¾ú´ÂÁö È®ÀÎ
    function is_stock_reg( $ids )
    {
        $query = "select count(*) cnt from 3pl_product_location where product_id in ($ids)";
if ( $_SESSION[LOGIN_LEVEL]==9)
{ 
    debug( $query );
}

        $result = mysql_query ( $query, $this->m_connect );
        $data   = mysql_fetch_array ( $result );
        return $data[cnt];        
    }

    ////////////////////////////////////////////
    // ÇöÀç Àç°í ..
    // ¾îÁ¦ ±îÁöÀÇ Àç°í ÀÓ..
    function get_current_stock( $product_id )
    {
        $query = "select qty from 3pl_stock_history where domain='"._DOMAIN_."' and product_id='$product_id' order by crdate desc limit 1";

        // º¯°æÇÔ
        $query = "select sum(stock) qty from 3pl_current_stock
                 where domain     = '" . _DOMAIN_ ."'
                   and product_id = '$product_id'";
//debug ( $query );

        $result = mysql_query ( $query, $this->m_connect );
        $data   = mysql_fetch_array ( $result );
        return $data[qty];        
    }

    //===================================================
    // set normal: °³º° ÁÖ¹® Á¤»ó Ã³¸®
    // 2007.11.20 - jk
    // unit test: 
    function set_normal( $seq )
    {
            // µî·ÏµÈ 3pl ÁÖ¹®ÀÎÁö ¿©ºÎ È®ÀÎ
        $this->is_3pl_order( $seq );            

// debug ( "3PL ($seq) °³º° Á¤»óÃ³¸®");

        //=========================================
        // normal·Î º¯°æÇÏ´Â °ÍÀÌ °¡´ÉÇÑÁö check 
        if ( $this->enable_normal( $seq ) )
        {
            // status°¡ 8ÀÎ ÄÉÀÌ½º´Â º¯°æÇÏÁö ¾Ê´Â´Ù.
            $query = "select qty 
                        from 3pl_orders 
                           where domain='" . _DOMAIN_ . "' 
                         and seq='$seq' 
                         and status <> 8";
   
            $result = mysql_query ( $query, $this->m_connect );
    
            // ÀüÃ¼ Ãë¼Ò ¿äÃ»
            $_qty = 0;
            $_child = 0;
            while ( $data = mysql_fetch_array ( $result ) )
            {
                $_qty = $_qty + $data[qty];
                $infos[order_cs] = -1;
                // $this->sync_infos( $infos, $seq, $data[seq_subid] );        
                $this->sync_infos( $infos, $seq );        
                $_child++;
            }
    
            if ( $_child >  1 )
                $infos[qty] = $_qty;

            // ¿ø ÁÖ¹®À» Á¤»ó Ã³¸®
            $infos[order_cs] = "0";
            $this->sync_infos( $infos, $seq, 1 );        
        }
    }

    //=========================================
    //
    // ¾ðÁ¦ normal·Î µÉ ¼ö ¾ø´ÂÁö ¾î¶»°Ô ¾Ë ¼ö ÀÖ³ª?
    // »õ·Î¿î rule¸¦ »ý¼º ÇÔ -> normalÈ­°¡ ½ÇÇàµÇ¸é order_cs¿¡ -1ÀÌ ÀÔ·ÂµÊ
    // date: 2007.11.20 -> Ãë¼Ò¿Í µ¿ÀÏÇÔ
    //
    function enable_normal( $seq )
    {
        $query = "select order_cs 
                    from 3pl_orders 
                   where domain='" . _DOMAIN_ ."' 
                     and seq='$seq'";
        $result = mysql_query ( $query, $this->m_connect );

        $_result = 1;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            if ( $data[order_cs] == -1 )
            {
                $_result = 0;
                break;
            }
        }
        return $_result;
    }

    //===================================================
    // set normal_all: ÀüÃ¼ ÁÖ¹® Á¤»ó Ã³¸®
    // 2007.11.20 - jk
    // unit test: 
    function set_normal_all ( $pack )
    {
            echo "set normal all";

        $query = "select seq,status from 3pl_orders 
                   where domain='" . _DOMAIN_ . "'
                     and pack='$pack'";

//debug ( "set normal all: $query ");

        $result = mysql_query ( $query, $this->m_connect );

        while ( $data = mysql_fetch_array ( $result ) )
            if ( $data[status] != 8 )
                $this->set_normal( $data[seq] );
            else
                debug ( "set normal fail: $data[seq] / $data[status] / $data[trans_no] / $data[trans_date_pos]");
    }

    ///////////////////////////////////////////////////
    // change packed product
    // date: 2008.3.4 - jk
    // ÇÕÆ÷ »óÇ° º¯°æ
    function change_packed_product2( $seq )
    {
        global $connect;
        $query     = "select pack_list from orders where seq=$seq";
        $result    = mysql_query ( $query, $connect );
        $data      = mysql_fetch_array ( $result );
        $pack_list = $data[pack_list];

        $pack_list = str_replace(",", "','", $pack_list );
        $query = "select product_id,name,options from products where product_id in ( '$pack_list' )";
        $result = mysql_query ( $query, $connect );
        $i = 1;
        $_arr = array ( "\r", "\n" );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $product_name = str_replace( $_arr, "", $data[name] );
            $options      = str_replace( $_arr, "", $data[option] );
            $product_id   = str_replace( $_arr, "", $data[product_id] );

            // seq_subid¸¦ Ã£´Â´Ù.
            $query = "update 3pl_orders 
                         set product_id   = '$product_id', 
                             product_name = '$product_name', 
                             options      = '$options' ,
                             order_cs     = 5
                       where seq          = $seq 
                         and seq_subid    = $i";
            //debug ( $query );
            mysql_query ( $query, $this->m_connect );
            $i++;
        }
    }

    //====================================================
    // ¹­À½ »óÇ° ±³È¯ ÀÛ¾÷ ¼öÇà
    // seq: ¿ø ÁÖ¹®
    // new_product_id: º¯°æµÈ ÁÖ¹® ¹øÈ£
    // new_qty: º¯°æµÈ °³¼ö
    // new_seq: ½Å±Ô ÁÖ¹® 
    // org_product_id: ¹­À½ »óÇ°¿¡¼­ »ç¿ë
    function change_packed_product( $seq, $org_qty, $new_product_id ,$new_qty, $new_seq=0, $org_product_id, $pack_list )
    {
        $data         = class_product::get_product_infos( $new_product_id );
        $product_name = $data[name];
        $options      = $data[option];

        // seq_subid¸¦ Ã£´Â´Ù.
        $query = "update 3pl_orders 
                     set product_id   = '$new_product_id', 
                         product_name = '$product_name', 
                         options      = '$options' ,
                         order_cs     = 5
                   where seq          = $seq 
                     and order_cs     = 0
                     and product_id   = '$org_product_id'";

//debug ( $query );
        $result = mysql_query ( $query, $this->m_connect );
    }

    //====================================================
    // ±³È¯ ÀÛ¾÷ ¼öÇà
    // 2007.11.17 - jk
    // seq: ¿ø ÁÖ¹®
    // new_product_id: º¯°æµÈ ÁÖ¹® ¹øÈ£
    // new_qty: º¯°æµÈ °³¼ö
    // new_seq: ½Å±Ô ÁÖ¹® 
    // org_product_id: ¹­À½ »óÇ°¿¡¼­ »ç¿ë
    function change_product( $seq, $org_qty, $new_product_id ,$new_qty, $new_seq=0, $org_product_id="" )
    {
//debug ( "3PL ($seq) ±³È¯ Ã³¸®");
        //=====================================================
        // ±³È¯ ÀÛ¾÷ Àü ÁøÇà ÀÛ¾÷
        // date: 2007.11.19 - jk
        $this->change_precheck( $new_seq, $seq );

        //==================================================
        // ÀÌ¹Ì ¹è¼ÛµÈ ÁÖ¹®ÀÎÁö check
        // ¹è¼Û: 1 ¹Ì¹è¼Û: 0
        // ¹è¼Û ÈÄ´Â ±³È¯ÀÌ ¹ß»ýÇØµµ ¿ø ÁÖ¹®ÀÇ ¹è¼Û °³¼ö´Â º¯°æÀÌ ¾øÀ½
        if ( $this->check_trans( $seq ) )
        {
            $infos[order_cs]     = 6;      // ¹è¼Û ÈÄ ±³È¯ ¿äÃ»
            $this->sync_infos( $infos, $seq );        
        }
        else
        {
            // ¹Ì¹è¼ÛÀÏ °æ¿ì¸¸ ½ÇÇà..
            //===================================================
            // ±³È¯ ·ÎÁ÷ »õ·Î design
            // 2007.11.19 - jk
            // 
            $_3pl_order   = $this->is_3pl_order( $seq );
            $_3pl_product = $this->is_3pl_product( $new_product_id );

//debug ( " seq: $seq, new_product_id: $new_product_id " );   
 
            //===========================================
            // ¾î¶² functionÀ» »ç¿ëÇÒ °ÍÀÎÁö ¼±ÅÃ  
            $_func = $this->_change_selector ( $_3pl_order, $_3pl_product );
    
            echo " org: $org_product_id/ new: $new_product_id\n";
              //============================================
            // returnµÈ °á°ú¸¦ ½ÇÇà 
            //
            // $_func[1][1] = "change_pl2pl";      3pl »óÇ°À» 3pl »óÇ°À¸·Î ±³È¯
            // $_func[1][0] = "change_pl2self";    3pl »óÇ°À» ÀÚÃ¼ »óÇ°À¸·Î ±³È¯ - Ãë¼Ò ÇÔ
            // $_func[0][1] = "change_self2pl";    ÀÚÃ¼ »óÇ°À» 3pl »óÇ°À¸·Î ±³È¯ - ½Å ÁÖ¹® »ý¼º
            // $_func[0][0] = "change_self2self";  ÀÚÃ¼ »óÇ°À» ÀÚÃ¼ »óÇ°À¸·Î ±³È¯ - ¾Æ¹«°Íµµ ¾È ÇÔ
            $this->${_func}( $seq, $org_qty, $new_product_id, $new_qty );
        }
    }

    //========================================
    //
    // ¹è¼Û ¿©ºÎ check 2007.11.19 - jk
    function check_trans( $seq )
    {
            global $connect;
        $query = "select status from orders where seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        if ( $data[status] == 8 ) // ¹è¼Û
            return 1;
        else
            return 0;
    }

    // is_3pl_order¿¡¼­ ÀÚµ¿À¸·Î µî·Ï µÇ´Âµ¥~~
    function change_self2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
        if ( !$this->is_3pl_reg( $seq ) )
            $this->order_reg( $seq );
    }

    //==============
    function change_self2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
        //ÀÛ¾÷ ÇØ¾ß ÇÏ³ª?
    }

    //================================
    // 3pl »óÇ°À» 3pl·Î º¯°æ
    // 2007.11.19 - jk
    // unit test: _3pl_test.php 11.19 - jk
    function change_pl2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
//debug ( "3pl»óÇ°À» 3pl»óÇ°À¸·Î º¯°æ");
        if ( $org_qty == $new_qty )
        {
            $data = class_product::get_product_infos( $new_product_id );
            $infos[product_id]   = $new_product_id;
            $infos[product_name] = $data[name];
            $infos[options]      = $data[option];
            $infos[order_cs]     = 5;      // ¹è¼Û Àü ±³È¯ ¿äÃ»
            $this->sync_infos( $infos, $seq );        
        }
        else
        {
                //========================================
                // 1. ±âÁ¸ ÁÖ¹®ÀÇ Á¤º¸ º¯°æ
                // 2. ±âÁ¸ ÁÖ¹®À¸·Î ½Å±Ô ÁÖ¹® »ý¼º ÈÄ Ãë¼Ò ¿äÃ»
            // ±âÁ¸ ÁÖ¹®ÀÇ status
                $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
        }
    }

    //================================
    // 3pl »óÇ°À» ÀÚ»ç »óÇ°À¸·Î º¯°æ
    // 2007.11.19 - jk
    // unit test: _3pl_test.php 11.19 - jk
    function change_pl2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
//debug ( "¹è¼Û Àü º¯°æ");

        if ( $org_qty == $new_qty )
        {
            $infos[order_cs]     = 1;      // ¹è¼Û Àü Ãë¼Ò ¿äÃ»
            $this->sync_infos( $infos, $seq );        
        }
        else
        {
                //========================================
                // 1. ±âÁ¸ ÁÖ¹®ÀÇ Á¤º¸ º¯°æ
                // 2. ±âÁ¸ ÁÖ¹®À¸·Î ½Å±Ô ÁÖ¹® »ý¼º ÈÄ Ãë¼Ò ¿äÃ»
            // ±âÁ¸ ÁÖ¹®ÀÇ status
                $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
        }
    }

    //========================================
    // 1. ±âÁ¸ ÁÖ¹®ÀÇ Á¤º¸ º¯°æ
    // 2. ±âÁ¸ ÁÖ¹®À¸·Î ½Å±Ô ÁÖ¹® »ý¼º ÈÄ Ãë¼Ò ¿äÃ»
    // 2009.3.31 - jk
    // ±âÁ¸ ÁÖ¹®ÀÇ qty ¼ö·® º¯°æ? - Á¤»óÁÖ¹®À¸·Î º¹±Í°¡ ¾î·Á¿ò?
    function part_cancel( $seq, $org_qty, $new_product_id, $new_qty )
    {
        $infos[hold] = 4; // Ãë¼Ò º¸·ù
        $infos[qty]  = $org_qty - $new_qty;
        $this->sync_infos( $infos, $seq );        
        
        /* ±âÁ¸·ÎÁ÷ - 2009.3.31
        $data = class_product::get_product_infos( $new_product_id );
        $infos[pack] = $seq;

        // 1. ±âÁ¸ ÁÖ¹®ÀÇ Á¤º¸ º¯°æ
        $infos[qty] = $org_qty - $new_qty;
        $this->sync_infos( $infos, $seq );        
    
        // 2. ±âÁ¸ ÁÖ¹®À¸·Î ½Å±Ô ÁÖ¹® »ý¼º ÈÄ Ãë¼Ò ¿äÃ»
        $new_info = $this->copy_order ( $seq );

        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];
        $infos[qty]          = $new_qty;
        $infos[order_cs]     = 1;

        // seq_subid »ç¶óÁü 2007.12.31 - jk
        //$this->sync_infos( $infos, $new_info[seq], $new_info[seq_subid] );        
        $this->sync_infos( $infos, $new_info[seq] );        
        */
    }

    //====================================
    // function ¼±ÅÃ..
    // 2007.11.19 - jk
    //
    function _change_selector ( $_3pl_order, $_3pl_product )
    {
        $_func[1][1] = "change_pl2pl";
        $_func[1][0] = "change_pl2self";
        $_func[0][1] = "change_self2pl";
        $_func[0][0] = "change_self2self";

        return $_func[$_3pl_order][$_3pl_product];
    }

    //=============================================
    // ±³È¯ ÀÛ¾÷ Àü ¼öÇà ÀÛ¾÷
    function change_precheck( $new_seq, $org_seq )
    {
        //=====================================================
        // new_seq°¡ ÀÖ´Â °æ¿ì »õ·Î¿î ÁÖ¹®À» °¡Á®¿Â´Ù
        if ( $new_seq )
        {
            // 3pl »óÇ° ÀÎÁö ¿©ºÎ check 
            if ( $this->is_3pl_use ( $new_seq ) )
            {
                $data = class_order::get_order( $new_seq );
                $data[pack] = $org_seq;
                $this->order_reg( $data );
            }
        }
    }

    //============================
    // ÀÚÃ¼ ¹è¼Û »óÇ°À¸·Î º¯°æf
    // 2007.11.17 - jk
    function change_self_product( $data, $qty, $new_product_id )
    {
        echo "change self product";
    }

    //========================================================
    // 3pl »óÇ°¿¡¼­ 3pl »óÇ°À¸·Î º¯°æ
    // change 3pl product to 3pl product - 2007.11.17 - jk
    function change_3pl_product( $data, $qty, $new_product_id )
    {
            if ( $data[qty] < $qty )
        {
            echo "¼ö·® ¿À·ù";
            exit;
        }

        if ( $data[status] == 8 )
            $this->_change_3pl_product_after( $data[seq] ); // ¹è¼Û ÈÄÀÎ °æ¿ì
        else
            $this->_change_3pl_product_before( $data[seq], $data[qty], $new_product_id, $qty ); // ¹è¼Û ÀüÀÎ °æ¿ì
    }

    //==========================================
    // ÁÖ¹®À» º¹»ç
    // 2007.11.17 - jk
    function copy_order( $seq )
    {
        $query  = "select * 
                    from 3pl_orders 
                   where domain='" . _DOMAIN_ . "' 
                     and seq='$seq'";
        $result = mysql_query ( $query, $this->m_connect );
        $data   = mysql_fetch_array ( $result );
        $fields = mysql_num_fields( $result );
        $data[seq_subid] = $this->get_max_subid( $seq );

        $out="";
        for ( $i = 0; $i < $fields; $i++ ) {
            $fname = mysql_field_name( $result, $i );
            if ( $fname == "seq_subid" )
                $arr_datas[$fname] = $data[$fname] + 1;
            else
                $arr_datas[$fname] = $data[$fname];
        }

        // º¹»ç ½ÇÇà
        // arr[seq] / arr[seq_subid] return
        return $this->insert_order( $arr_datas );
    }

    function get_max_subid( $seq )
    {
        // max sub id 
        $query = "select max(seq_subid) seq_subid 
                    from 3pl_orders 
                   where domain='" . _DOMAIN_ . "' 
                     and seq='$seq'";
        $result = mysql_query ( $query, $this->m_connect );
        $data   = mysql_fetch_array ( $result );
        return $data[seq_subid];
    }

    //=================================
    // ÁÖ¹® ÀÔ·Â
    // 2007.11.17 - jk
    function insert_order( $arr_datas )
    {
        $query = "insert into 3pl_orders set ";
        
        $i = 0;
        foreach ( $arr_datas as $key=>$value )
        {
            if ( $value )
            {
                    if ( $i != 0 ) 
                    $query .= ",";

                    $query .= " $key='$value' ";
                    $i++;
            }
        }
        mysql_query ( $query, $this->m_connect );

        return array( seq => $arr_datas[seq], seq_subid => $arr_datas[seq_subid] );
    }

    //===========================================
    //
    // ¹è¼Û Àü 3ÀÚ ¹°·ù »óÇ°À¸·Î ±³È¯
    // date: 2007.11.17 - jk
    function _change_3pl_product_before( $seq, $qty, $new_product_id, $new_qty )
    {
//debug ( "¹è¼Û Àü 3ÀÚ ¹°·ù·Î ±³È¯");
            // name, option, enable_sale Á¤º¸°¡ Àü¼ÛµÊ
        $data = class_product::get_product_infos( $new_product_id );
        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];

        if ( $qty != $new_qty )
        {
            // ±³È¯ »óÇ°À» ¸¸µé¾î ³¿..
            $new_seq = $this->copy_order( $seq );

            //======================================
            // »õ·Î ¸¸µé¾îÁø ÁÖ¹®ÀÇ Á¤º¸¸¦ º¯°æ
            // ½Å »óÇ°ÀÇ Á¤º¸·Î ½Å±Ô ÁÖ¹® »ý¼º 
            $infos[order_cs]     = 5;      // ¹è¼Û Àü ±³È¯ ¿äÃ»
            $infos[qty]          = $new_qty;      
            $this->sync_infos( $infos, $new_seq );        

            //================================
            // ±âÁ¸ ÁÖ¹®ÀÇ Á¤º¸ º¯°æ
            // ±âÁ¸ ÁÖ¹®Àº ¼ö·®ÀÌ º¯°æ µÊ
            // ±âÁ¸ °³¼ö - ½Å±Ô ÁÖ¹® °³¼ö 
            // °³¼ö°¡ ´Ù¸¦ °æ¿ì ½Å±Ô ÁÖ¹®ÀÌ »ý¼º µÈ´Ù. 
            $infos2[order_cs]     = 5;      // ¹è¼Û Àü ±³È¯ ¿äÃ»
            $infos2[qty]          = $qty - $new_qty;      
            $this->sync_infos( $infos2, $seq );        
        }
        else
        {
            //==================================================
            // set infos that would save at 3pl_order table
            $infos[order_cs]     = 5;      // ¹è¼Û Àü ±³È¯ ¿äÃ»
            $this->sync_infos( $infos, $seq );        
        }
    }

    //=======================================
    //
    // ¹è¼Û ÈÄ 3ÀÚ ¹°·ù »óÇ°À¸·Î ±³È¯
    // 2007.11.17 - jk
    function _change_3pl_product_after( $seq )
    {
//debug ( "¹è¼Û ÈÄ 3ÀÚ ¹°·ù·Î ±³È¯");

        echo "¹è¼Û ÈÄ 3ÀÚ ¹°·ù ±³È¯";
        $infos[order_cs]     = 6;      // ¹è¼Û ÈÄ ±³È¯ ¿äÃ»
        $this->sync_infos( $infos, $seq );        
    }

    ///////////////////////////////////
    // Á¤º¸¸¦ ¸ÂÃç ÁÜ - ÇÕÆ÷±âÁØ
    function sync_infos2( $arr_datas, $pack )
    {
        $query = "update 3pl_orders set ";
        
        $i = 0;
        foreach ( $arr_datas as $key=>$value )
        {
            if ( $value )
            {
                    if ( $i != 0 ) 
                    $query .= ",";

                if ( $value == "NULL" )
                        $query .= " $key=''";
                else if ( $value == "now" )
                        $query .= " $key=Now() ";
                else
                        $query .= " $key='$value' ";

                    $i++;
            }
        }
        $query .= " where ( domain='" ._DOMAIN_ ."' and seq='$pack')
                     or (domain='". _DOMAIN_ ."' and  pack=$pack)";

        if ( $i > 0 )
        {
            mysql_query ( $query, $this->m_connect ) or die( $query . mysql_error() );
        }
    }

    //***********************************************
    //
    // 2009.4.3 - jk
    // desc: 
    // cs¿¡¼­ ¹è¼Û¿Ï·á¸¦ ½ÇÇàÇÒ °æ¿ì ½ÇÇàµÇ´Â ·ÎÁ÷
    //
    function set_location_confirm_trans( $seq ) 
    {
        // ÇÏ³ªÀÇ seq´Â ¹­À½ »óÇ°ÀÏ °æ¿ì 3pl¿¡ º¹¼ö°³ÀÇ ÁÖ¹®À» °®´Â´Ù.
        // ez¿¡¼­ ¹è¼Û ¿Ï·á¸¦ ÇÒ °æ¿ì ÇØ´ç »óÇ°ÀÇ locationÀÌ ¸ðµÎ Â÷°¨ µÇ¾î¾ß ÇÑ´Ù
        $query = "select status,product_id,location,qty,warehouse
                    from 3pl_orders
                   where domain='". _DOMAIN_ ."'
                     and seq = $seq
                     and status <> 8
                     and order_cs not in (1,2,3,4,12)";


        $result = mysql_query( $query, $this->m_connect );
        while ( $data = mysql_fetch_assoc( $result ) )
         {
            $this->change_location_stock( $data[warehouse], $data[location], $data[product_id], $data[qty] );
        }
    }

    function change_location_stock( $warehouse, $location, $product_id, $qty )
    {
        $query = "update 3pl_product_location
                    set stock     = stock - $qty
                  where domain    = '"._DOMAIN_."'
                    and warehouse = '$warehouse'
                    and location  = '$location'
                    and product_id= '$product_id'";

        mysql_query( $query, $this->m_connect );
    }

    

    // Á¤º¸¸¦ ¸ÂÃç ÁÜ
    function sync_infos( $arr_datas, $seq, $seq_subid=0 )
    {
        $query = "update 3pl_orders set ";
        
        $i = 0;
        foreach ( $arr_datas as $key=>$value )
        {
            if ( $value )
            {
                    if ( $i != 0 ) 
                    $query .= ",";

                if ( $value == "NULL" )
                        $query .= " $key=''";
                else if ( $value == "now" )
                        $query .= " $key=Now() ";
                else
                        $query .= " $key='$value' ";

                    $i++;
            }
        }
        $query .= " where domain='" ._DOMAIN_ ."'
                     and seq='$seq' ";

        // if ( $seq_subid )
        //    $query .= " and seq_subid='$seq_subid'";        

//debug ( "[sync infos] $query");
//echo "$query<br>";
        if ( $i > 0 )
        {
            mysql_query ( $query, $this->m_connect ) or die( $query . mysql_error() );
        }

        // locationÁ¤º¸ »èÁ¦ ÇØ¾ß ÇÔ

        // ¹è¼Û ¿Ï·á·Î º¯°æµÈ °æ¿ì...locationÀç°í Á¶Á¤ÇØ¾ß ÇÔ.
        // Á¤º¸ È®ÀÎ..

    }
    // ÀüÃ¼ ¼ÛÀå »èÁ¦
    function del_trans_all( $pack )
    {
        $query = "update 3pl_orders 
                     set trans_corp     = '', 
                         trans_date     = '',
                         trans_no       = '',
                         trans_date_pos = '',
                         status         = 1
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and ( seq = $pack or pack = $pack )";
        //debug ( "[del_trasn_all] $query " );
        mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    // °³º° ¼ÛÀå »èÁ¦
    function del_trans_info( $seq )
    {
        $query = "update 3pl_orders 
                     set trans_corp    = '', 
                         trans_date    = '',
                         trans_no      = '',
                         trans_date_pos= '',
                         status        = '1'
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and seq = $seq";
        //debug ( "[del_trasn_info] $query " );
        mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // packÁ¤º¸ º¯°æ
    function sync_pack( $old_pack, $new_pack )
    {
        if ( $old_pack)
        {
            $query = "update 3pl_orders set pack=$new_pack 
                       where domain='" . _DOMAIN_ . "'
                         and pack=$old_pack";
            mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
        }
    }

    ///////////////////////////////////////////////////
    // packÁ¤º¸ º¯°æ
    function do_pack( $seq, $pack )
    {
        $query = "update 3pl_orders set pack=$pack 
                   where domain='" . _DOMAIN_ . "'
                     and seq=$seq";
//debug( "[do pack] $query " );
        mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // packÁ¤º¸ º¯°æ
    // ¹­À½ »óÇ°ÀÇ ÇÕÆ÷°¡ Ç®¾îÁú °æ¿ì 3pl¿¡´Â °è¼Ó ¹­À½ »óÇ°À¸·Î ³²¾Æ ÀÖ¾î¾ß ÇÔ
    // 2008.9.25 - jk
    function remove_pack( $seq )
    {
        global $connect;
        $query = "select packed from orders where seq=$seq";
        $result = mysql_query ( $query, $connect ) or die( debug ( mysql_error() ) );
        $data   = mysql_fetch_array( $result );

        // ¹­À½ÀÎÁö ¿©ºÎ È®ÀÎÇØ ¹­À½ÀÏ °æ¿ì ÇÕÆ÷ Á¤º¸¸¦ °è¼Ó À¯Áö ÇØ¾ß ÇÑ´Ù 
        //  - °³º°ÀÌ µÇ¾î ¹ö¸®¸é Àç°í°¡ ¾ø¾îµµ ¼ÛÀå Ãâ·Â µÊ
        if ( $data[packed] )        
        {
            $query = "update 3pl_orders set pack=$seq
                       where domain='" . _DOMAIN_ . "'
                         and seq=$seq";
        }
        else
        {
            $query = "update 3pl_orders set pack=null 
                       where domain='" . _DOMAIN_ . "'
                         and seq=$seq";
        }
debug ( "[remove_pack] $query" );
        mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    //======================================================
    // cs¸¦ syncÇÔ
    // 2007.11.16 - jk
    function sync_cs ( $seq, $order_cs )
    {
        if ( $order_cs )
        {
            $query = "update 3pl_orders set order_cs=$order_cs 
                   where domain='" ._DOMAIN_ ."'
                     and seq='$seq'";        
            mysql_query ( $query, $this->m_connect );
        }
    }

    //==================================
    // 3pl ÁÖ¹®ÀÇ ¼ÛÀå ¹øÈ£ ÀÔ·Â
    function insert_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no)
    {
        if ( $this->is_3pl_use ( $seq ) )
        {
            //$pack = $this->check_pack( $seq );
            //if ( $pack )
            //        $this->_update_pack_trans_no( $pack, $trans_corp, $trans_no, $warehouse);
            //else
            $this->_update_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no);
        }
    }

    //=========================================
    // ÇÕÆ÷ ÀÎÁö ¿©ºÎ check
    function check_pack ( $seq ) 
    {
        global $connect;
        $query = "select pack from 3pl_orders 
                   where domain='" ._DOMAIN_ ."'
                     and seq='$seq'";
        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );

        if ( $data[pack] )
            return $data[pack];
        else
            return 0;
    }

    //=======================================
    // °³º° ¼ÛÀå ÀÔ·Â
    // 2007.11.16 - jk
    // ÇÕÆ÷¿Í ÀÏ¹ÝÀ» ¸ðµÎ Ã³¸® ÇÔ
    function _update_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no )
    {
        global $connect;
        $query    = "select status,order_cs,pack 
                       from orders 
                      where seq=$seq";
        $result   = mysql_query ( $query, $connect ) or die( mysql_error() );
        $data     = mysql_fetch_array( $result );
        $status   = $data[status];
        $order_cs = $data[order_cs];
        $pack     = $data[pack];

        /////////////////////////////////////////////
        $query = "update 3pl_orders 
                             set trans_corp ='$trans_corp'
                                 ,trans_no   ='$trans_no' 
                                 ,order_cs   ='$order_cs'
                                 ,status     ='$status' 
                                 ,pack       ='$pack' ";
        // warehouse°¡ ¾øÀ» ¼öµµ ÀÖÀ½
        // jk.ryu 2009.4.29 
        if ( $warehouse )
                     $query .= " ,warehouse  ='$warehouse' ";

                     $query .= " ,trans_date = Now()
                           where domain='" ._DOMAIN_ ."'";

        // ÇÕÆ÷ ¹øÈ£°¡ ÀÖÀ»¶© ÇÑ ¹ø ´õ µ¹¸°´Ù
        if ( $insert_all_trans_no )
        {
            if ( $pack )
            {
                debug( "[update_trans_no1] $query and pack=$pack" );
                mysql_query ( $query . " and pack=$pack", $this->m_connect );
            }
            else
                debug( "[update_trans_no no pack no] $query and pack=$pack" );
        }

        mysql_query ( $query ." and seq=$seq", $this->m_connect );
//debug( "[update_trans_no2] $query and seq=$seq" );
        echo " change 3pl trans no";
    }

    //=======================================
    // ÇÕÆ÷ ¼ÛÀå ÀÔ·Â
    // 2007.11.16 - jk
    function _update_pack_trans_no( $pack, $trans_corp, $trans_no, $warehouse)
    {
        global $connect;
        $query    = "select status,order_cs,pack
                       from orders 
                      where seq=$pack";
        $result   = mysql_query ( $query, $connect );
        $data     = mysql_fetch_array( $result );
        $status   = $data[status];
        $order_cs = $data[order_cs];
        $pack     = $data[pack];

        $query    = "update 3pl_orders 
                             set trans_corp= '$trans_corp', 
                                 trans_no  = '$trans_no', 
                                 warehouse = '$warehouse', 
                                 status    = '$status', 
                                 order_cs  = '$order_cs', 
                                 pack      = '$pack', 
                                 trans_date= Now()
                           where domain='" ._DOMAIN_ ."'
                             and pack=$pack";        
//echo $query;        
//debug( "[pack pack trans no] $query" );
        mysql_query ( $query, $this->m_connect );
        echo "all update";
    }

    //=====================================
    // 3pl ÁÖ¹®À¸·Î µî·ÏµÇ¾ú´ÂÁö check
    function is_3pl_order( $seq )
    {
            $_return = 0;
        if ( $this->is_3pl_use( $seq ) )
        {
            if ( !$this->is_3pl_reg( $seq ) )
            {
                    // $this->order_reg( $seq );
                // ÀÛ¾÷ ÇÏÁö ¾Ê´Â´Ù
                exit;
            }

            $_return = 1;        
        }
        return $_return;
    }

    function is_3pl_reg( $seq )
    {
        $query = "select count(*) cnt 
                    from 3pl_orders 
                   where domain='" . _DOMAIN_ ."'
                     and seq='$seq'";
        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );
        return $data[cnt] ? 1 : 0;
    }

    //==================================
    // 3pl ÁÖ¹®ÀÇ ¼ÛÀå ¹øÈ£ ÀÔ·Â
    // local connect Á¤º¸¸¦ »ç¿ëÇØ¾ß ÇÔ
    function is_3pl_use( $seq )
    {
        global $connect;
        $query = "select product_id from orders where seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        $product_id = $data[product_id];

        // use_3pl °ª check
        if ( $product_id )
        {
            // ÇØ´ç »óÇ°ÀÌ 3pl »óÇ°ÀÎÁö check
            $_result = $this->is_3pl_product( $product_id );
            echo "3pl »ç¿ë";
        }
        else
        {
            $_result = 0;
        }        

        return $_result;
    }

    function is_3pl_product( $product_id )
    {
            global $connect;
        $query = "select use_3pl from products where product_id='$product_id'";        
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        $_result = $data[use_3pl];
        return $_result;        
    }

    //======================================================
    // cs¸¦ syncÇÔ
    // 2007.11.16 - jk
    function sync_status ( $seq, $status )
    {
        if ( $status )
        {
            $query = "update 3pl_orders set status = $status
                           where domain='" ._DOMAIN_ ."'
                             and seq='$seq'";        
            mysql_query ( $query, $this->m_connect );
        }
    }


    //========================================
    // 3pl_regist¿¡ ÀÛ¾÷ µî·Ï 2007.11.15 - jk
    function regist_tx( $type, $cnt, $msg, $user, $_date )
    {
        if ( $cnt )
        {
            //================================================================
            // Q.
            // req_date´Â ¾î¶»°Ô ÇØ¾ß ÇÏÁö? 14ÀÏ ¹ßÁÖ¸¦ 17ÀÏ³¯ ¿äÃ» ÀâÀ» °æ¿ì
            //
            $query = "insert into 3pl_tx set 
                                domain='" . _DOMAIN_ ."',
                                req_user = '$user',
                                req_date= Now(),
                                req_cnt = '$cnt',
                                msg = '$msg',
                                status=0,
                                type='$type'";

            mysql_query ( $query, $this->m_connect );
        }
    }

    //========================================
    // ¹è¼Û ¿äÃ» »ó¼¼ 
    function request_detail( $_date )
    {
        $query = "select * from 3pl_tx 
                   where req_date >= '$_date 00:00:00'
                     and req_date <= '$_date 23:59:59'
                     and domain='" . _DOMAIN_ . "'";
// echo $query;
        $result = mysql_query ( $query , $this->m_connect );
        return $result;
    }

    function cnt_req_orders( $arr_options )
    {
        $query = "select count(*) cnt from 3pl_orders 
                   where collect_date = '$arr_options[collect_date]'
                     and domain='" . _DOMAIN_ . "'";
// echo $query;
        if ( $arr_options[status] )
            $query .= " and status = $arr_options[status]";
            // $query .= " and a.status = 1";

        if ( $arr_options[order_cs] )
            $query .= " and order_cs in ( $arr_options[order_cs] ) ";
            //$query .= " and a.order_cs not in (1,2,3,4,12) ";

        $result = mysql_query ( $query , $this->m_connect );
        $data = mysql_fetch_array ( $result );
        return $data[cnt];
    }

    /////////////////////////////////////////////////////
    // °ü¸®ÀÚ¸¸ ¾²´Â update ezadmin °ü¸®ÀÚ¸¦ À§ÇÑ ºÎºÐ
    function update_order( $data )
    {
        if ( $data[seq] )
        {
            $shop_name = class_C::get_shop_name( $data[shop_id] );

            $query   = "update 3pl_orders ";
            $options = "
                            set 
                            product_id      = '$data[product_id]',
                            pack            = '$data[pack]',
                            product_name    = '$data[product_name]',
                            options             = '$data[options]',
                            qty             = '$data[qty]',
                            status          = '$data[status]',
                            order_cs        = '$data[order_cs]',
                            collect_date    = '$data[collect_date]',
                            order_name      = '$data[order_name]',
                            recv_name       = '$data[recv_name]',
                            recv_tel        = '$data[recv_tel]',
                            recv_mobile     = '$data[recv_mobile]',
                            trans_who       = '$data[trans_who]',
                            recv_zip        = '$data[recv_zip]',
                            recv_address    = '$data[recv_address]',
                            memo            = '$data[memo]',
                            shop_id         = '$data[shop_id]',
                            shop_name       = '$shop_name',
                            priority        = '$data[priority]'";

            if ( $data[warehouse] )
                $options .= " ,warehouse       = '$data[warehouse]'";

            if ( $data[trans_no] )
                $options .= ", trans_no        = '$data[trans_no]'";

            if ( $data[trans_corp] )
                $options .= ", trans_corp      = '$data[trans_corp]'";

            if ( $data[trans_date] )
                $options .= ", trans_date      = '$data[trans_date]'";

            if ( $data[trans_date_pos] )
                $options .= ", trans_date_pos  = '$data[trans_date_pos]'";

            $query .= $options . " where domain='" . _DOMAIN_ . "' and seq='$data[seq]';";

            mysql_query ( $query, $this->m_connect ) ;
            $_result = mysql_affected_rows();
            //debug( "[update_order] $query" . "/" .  $_result );
         
            // update°¡ ¾È µÉ °æ¿ì 
            if ( $_result == 0 )
            {
                $query2 = "insert into 3pl_orders " . $options;
                $query2 .= " ,seq   = $data[seq]
                             ,domain='" ._DOMAIN_ . "'";

                // debug( "[update_order] $query2" );
                mysql_query ( $query2, $this->m_connect ) ;
            }
        }
 
    }

    //=======================================
    // ÁÖ¹® µî·Ï
    // date: 2007.11.13
    function order_reg ( $data , $seq_subid=1)
    {
        global $connect;
        $shop_name = class_C::get_shop_name( $data[shop_id] );

        // ºÎºÐ Ãë¼Ò ¿©ºÎ check - 2009.3.31 - jk
        // new logic start
        $qty  = $data[qty];
        $hold = $data[hold];

         $part_cancel = class_E::get_part_cancel_count( $data[seq] );
        if ( $part_cancel > 0 ) 
        {
            $hold = 5; // Ãë¼Ò º¸·ù
            $qty  = $qty - $part_cancel;
        }
        
        // new logic end
        $memo = $data[gift] ? "(" . $data[gift] . ")" : "" . $data[memo];

        $query = "insert 3pl_orders set 
                        domain                 = '" . _DOMAIN_ . "',
                        seq                 = $data[seq],
                        seq_subid         = $seq_subid,
                        pack                = '$data[pack]',
                        order_id         = '$data[order_id]',
                        order_subid         = '$data[order_subid]',
                        shop_id         = '$data[shop_id]',
                        shop_name         = '$shop_name',
                        shop_product_id = '$data[shop_product_id]',
                        product_id      = '$data[product_id]',
                        product_name         = '$data[product_name]',
                        options         = '$data[options]',
                        qty             = '$qty',
                        status          = '$data[status]',
                        order_cs        = '$data[order_cs]',
                        order_date      = '$data[order_date]' ,
                        collect_date    = '$data[collect_date]' ,
                        collect_time    = '$data[collect_time]' ,
                        trans_req_date  = Now(),
                        order_name         = '$data[order_name]',
                        order_mobile        = '$data[order_mobile]',
                        recv_name        = '$data[recv_name]',
                        recv_tel        = '$data[recv_tel]',
                        recv_mobile         = '$data[recv_mobile]',
                        trans_who       = '$data[trans_who]',
                        trans_price     = '$data[trans_price]',
                        recv_zip        = '$data[recv_zip]',
                        memo            = '$memo',
                        priority        = '$data[priority]',
                        warehouse       = '$data[warehouse]',
                        hold            = '$hold',
                        recv_address         = '$data[recv_address]'
                        ";

        mysql_query ( $query, $this->m_connect );
        $affect = mysql_affected_rows();
        debug ( "[affect: $affect] $query" );

        if ( $affect > 0 )
            return 1;
        else
        {
            // warehouse¿¡ °ªÀÌ ¾ø´Â °æ¿ì¿¡¸¸ Ã³¸® ÇÔ
            // warehouse¿¡ °ªÀÌ ÀÖ´Â °æ¿ì ÀÛ¾÷ 2 ½ÇÇà ÈÄ ¼ÛÀå Ãâ·Â Áß ÀÓ.
            // »óÇ°ÀÌ º¯°æµÈ °æ¿ì¿Í »óÇ°ÀÌ µ¿ÀÏÇÑ °æ¿ì°¡ ÀÖÀ½.
            // »óÇ°ÀÌ µ¿ÀÏÇÏ°í warehouse°¡ ÀÖ´Â°æ¿ì => ±×´ë·Î µÐ´Ù.
            // »óÇ°ÀÌ ´Ù¸£°í warehouse°¡ ÀÖ´Â°æ¿ì => ±³È¯ ÀÓ »óÇ° Á¤º¸ ¼öÁ¤ÇØ¾ß ÇÔ.
            // date: 2008.8.5 - jk
            $query = "select warehouse , status, trans_date_pos
                        from 3pl_orders 
                       where seq       = '$data[seq]' 
                         and seq_subid = $seq_subid
                         and domain    = '" ._DOMAIN_ . "'";

            $_result                  = mysql_query( $query, $this->m_connect );
            $arr_warehouse            = mysql_fetch_array( $_result );
            $arr_warehouse[warehouse] = $_warehouse ? $_warehouse : $data[warehouse];

            // stats°¡ 8ÀÎ°æ¿ì ÀÌ ·ÎÁ÷¿¡ ¿À¸é ¿À·ù -> orderÀÇ status¸¦ ¹è¼Û ¿Ï·á·Î º¯°æÇØ¾ß ÇÔ..
            // date: 2008.12.29 - jk
            if ( $arr_warehouse[status] == 8 )
            {
                $query = "update orders set status=8, trans_date_pos='$arr_warehouse[trans_date_pos]' 
                           where seq=$data[seq]";
                mysql_query ( $query, $connect );
            }
            else
            {
                  $query = "update 3pl_orders set 
                            product_id      = '$data[product_id]',
                            pack            = '$data[pack]',
                            trans_who       = '$data[trans_who]',
                            product_name    = '$data[product_name]',
                            options             = '$data[options]',
                            qty             = '$qty',
                            hold            = '$hold',
                            status          = '$data[status]',
                            order_cs        = '$data[order_cs]',
                            priority        = '$data[priority]'
                        where domain    = '" . _DOMAIN_ . "'
                          and seq       = '$data[seq]'
                          and seq_subid = $seq_subid
                          and status    <> 8";

                mysql_query ( $query, $this->m_connect );
                $affect = mysql_affected_rows();
            }
            debug ( "[update affect: $affect] $query" );
        }
        return $affect;
    }

    //=======================================
    // µî·ÏµÈ »óÇ°À» 3pl¿¡ º¹»ç ÇÔ
    function product_reg( $product_id , $data="")
    {
            global $connect;

        if ( !$data )
            $data = class_product::get_product_info ( $connect, $product_id );

        // 3pl»óÇ°ÀÎ °æ¿ì¿¡¸¸ µî·Ï
        if ( $data[use_3pl] ) 
        {
                $options = str_replace( array("\r","\n","\r\n"),"", $data[options] );

                //===================================
                // 3pl¿¡ »óÇ° data Àü¼Û
                $query = "insert 3pl_products set 
                                        domain      = '" . _DOMAIN_ ."',
                                        product_id  = '$data[product_id]',
                                        supply_id   = '$data[supply_code]',
                                        product_name= '$data[name]',
                                        options     = '$options',
                                        crdate      = Now(),
                                        enable_sale = '$data[enable_sale]',
                                        barcode     = '$data[barcode]',
                                        org_id      = '$data[org_id]',
                                        reg_date    = Now() ";
 //debug ( $query );
                mysql_query ( $query, $this->m_connect );
                // exit;
        }
    }

    //////////////////////////////////////
    //ÃÊ±âÈ­
    function init_priority()
    {
        $query = "update 3pl_orders 
                     set priority=null 
                   where domain='" . _DOMAIN_ ."'
                     and status=1 and priority <> 99";
        mysql_query ( $query , $this->m_connect) or die( mysql_error() );
        return mysql_affected_rows();
    }

    function get_print_log ($seq)
    {
        $query = "select msg from 3pl_print_log where seq=$seq";
        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );
        return $data[msg];
    }

    // »óÇ° »èÁ¦
    function del_products($ids)
    {
        $query = "delete from 3pl_products where product_id in ( $ids )";
        mysql_query ( $query, $this->m_connect );
    }

    function product_count( $arr_items )
    {
        $query = "select count(*) cnt from 3pl_products ";
        $query.= $this->build_option( $arr_items );
        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );
        return $data[cnt];
    }

    //=============================================
    // query¸¦ À§ÇÑ option»ý¼º
    // date: 2007.11.21 - jk
    function build_option( $arr_items )
    {
        $_options = "";
        $i = 0;
        foreach ( $arr_items as $item=>$_opt )
        {
            global  $$item;
            $$item = $$item ? $$item : $_opt;
            if ( $$item )
            {
                if ( $_cnt == 0 )
                        $_options .= " where ";
                else
                        $_options .= " and ";

                if ( $_opt == "like" )
                        $_options .= "$item like '%". $$item."%'";
                else
                        $_options .= "$item = '". $$item."'";

                $_cnt++;
            }
        }
        return $_options;
    }
   

    //=======================================
    //
    // µî·ÏµÈ »óÇ°ÀÇ º¯°æÀÌ ÀÖÀ» °æ¿ì ³»¿ë update
    // 2007.11.12
    //
    function product_update( $product_id )
    {
            global $connect;
        $data = class_product::get_product_info ( $connect, $product_id );

        // 3pl»óÇ°ÀÎ °æ¿ì¿¡¸¸ µî·Ï
        if ( $data[use_3pl] ) 
            if ( $this->check_reg( $product_id ) )   // ½ÇÁ¦ µî·ÏµÇ¾î ÀÖ´Â »óÇ°ÀÎÁö ¿©ºÎ checkÇØ¾ß ÇÔ
            {
                $query = "select * From products where product_id='$product_id' or org_id='$product_id'";
                $result = mysql_query ( $query, $connect ) or die( mysql_error() );
                while ( $data = mysql_fetch_array ( $result ) )
                {
                        $this->_update( $data, $data[product_id] );
                }
            }
            else
            {
                $this->product_reg( $product_id );
            }
    }

    //=========================================
    // ¿É¼ÇÀÇ ¼öÁ¤ È¤Àº µî·Ï
    // org_id: ¿ø »óÇ° id
    // option_id: º¯°æµÈ id
    function option_update( $org_product_id, $new_product_id )
    {
            global $connect;
        $data = class_product::get_product_info ( $connect, $new_product_id );

        // ½ÇÁ¦ µî·ÏµÇ¾î ÀÖ´Â »óÇ°ÀÎÁö ¿©ºÎ checkÇØ¾ß ÇÔ
        if ( $data[use_3pl] ) 
            if ( $this->check_reg( $org_product_id ) )               
                    $this->_update( $data, $org_product_id );
            else
                    $this->product_reg( $new_product_id );
    }

    //=====================================
    // 3pl »óÇ° Á¤º¸
    function get_3pl_product_info( $product_id )
    {
            $query = "select *
                    from 3pl_products 
                   where product_id='$product_id' 
                     and domain='" . _DOMAIN_ . "'";

//debug ( $query );

        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );
        return $data;
    }


    //=====================================
    // ½ÇÁ¦ µî·Ï ¿©ºÎ Ã¼Å©
    // µî·ÏÀÌ È®ÀÎµÉ °æ¿ì 1À» ¹ÝÈ¯
    // µî·ÏÀÌ È®ÀÎ ¾ÈµÉ °æ¿ì 0À» ¹ÝÈ¯
    function check_reg( $product_id )
    {
            $query = "select count(*) cnt 
                    from 3pl_products 
                   where product_id='$product_id' 
                     and domain='" . _DOMAIN_ . "'";


        $result = mysql_query ( $query, $this->m_connect );
        $data = mysql_fetch_array ( $result );

        if ( $data[cnt] )
                $result = 1;
        else
                $result = 0;

        return $result;
    }

    function _update( $data, $org_id )
    {
        $barcode = $data[barcode] ? $data[barcode] : $data[product_id];

        //===================================
        // 3pl¿¡ »óÇ° data Àü¼Û
        $query = "update 3pl_products set 
                                product_id  = '$data[product_id]',
                                supply_id   = '$data[supply_code]',
                                    product_name= '$data[name]',
                                    options     = '$data[options]',
                                enable_sale = '$data[enable_sale]'
                        where        domain      = '" . _DOMAIN_ ."'
                          and   product_id  = '$org_id'";
        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );

        ////////////////////////////////////////////////
        // ¹ÙÄÚµå Á¤º¸´Â µû·Î update 2008.2.15 - jk
        $query = "update 3pl_products set 
                                    barcode     = '$barcode'
                        where        domain      = '" . _DOMAIN_ ."'
                          and   product_id  = '$org_id'";

        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }

    //////////////////////////////////////////////////////
    // ¹è¼Û º¸·ù Ãë¼Ò 
    function cancel_hold( $_seq = "" )
    {
        global $connect, $seq;
        $seq = $_seq ? $_seq : $seq;        // ÀÔ·ÂµÈ parameter¿¡ ¿ì¼±ÇÑ´Ù.        2008.5.20 = jk

        $query = "update 3pl_orders 
                     set hold=0
                   where domain = '" . _DOMAIN_ ."'
                     and (seq=$seq or pack=$seq)";
//debug ( $query );

        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }

    function get_stock_total( $product_arr )
    {
        global $start_date;
        
        // start_date ´ÙÀ½³¯Â¥ ±¸ÇÏ±â
        $next_date = explode('-', $start_date);
        $end_date = date("Y-m-d", mktime(0,0,0,$next_date[1],$next_date[2]+1,$next_date[0]));

        $connect = $this->m_connect;
        
        // ÀüÀÏÀÔ°íÇÕ        in_sum_y
        // ÀüÀÏ¹ÝÇ°ÀÔ°íÇÕ    in_ret_y
        // ÀüÀÏºÒ·®ÀÔ°íÇÕ    in_bad_y
        // ±ÝÀÏÀÔ°í          in_sum_t
        // ±ÝÀÏ¹ÝÇ°ÀÔ°í      in_ret_t
        // ±ÝÀÏºÒ·®ÀÔ°í      in_bad_t
        // 
        // ÀüÀÏÃâ°íÇÕ        out_sum_y
        // ÀüÀÏ¹ÝÇ°Ãâ°íÇÕ    out_ret_y
        // ÀüÀÏºÒ·®Ãâ°íÇÕ    out_bad_y
        // ±ÝÀÏÃâ°í          out_sum_t
        // ±ÝÀÏ¹ÝÇ°Ãâ°í      out_ret_t
        // ±ÝÀÏºÒ·®Ãâ°í      out_bad_t
        // 
        // ÀüÀÏ¹è¼ÛÇÕ        tr_sum_y
        // ±ÝÀÏ¹è¼Û          tr_sum_t
        // ¹è¼Û¿¹Á¤          tr_sum_w
        // ¹Ì¹è¼Û            tr_sum_n

        // stock array
        $stock = array();
        foreach( $product_arr as $prd )
        {
            $stock[$prd] = array(
                in_sum_y  => 0,
                in_ret_y  => 0,
                in_bad_y  => 0,
                in_sum_t  => 0,
                in_ret_t  => 0,
                in_bad_t  => 0,
                out_sum_y => 0,
                out_ret_y => 0,
                out_bad_y => 0,
                out_sum_t => 0,
                out_ret_t => 0,
                out_bad_t => 0,
                tr_sum_y  => 0,
                tr_sum_t  => 0,
                tr_sum_w  => 0,
                tr_sum_n  => 0
            );
        }

        // product_id ¹®ÀÚ¿­
        $product_list = '';
        foreach( $product_arr as $prd )
            $product_list .= ($product_list?',':'') . "'$prd'";

        // ÀüÀÏ ÀÔ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_sum_y'] = $data[qty];

        // ÀüÀÏ ¹ÝÇ°ÀÔ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where memo = '·ÎÄÉÀÌ¼Ç ¹ÝÇ°ÀÔ°í' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_ret_y'] = $data[qty];

        // ÀüÀÏ ºÒ·®ÀÔ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where warehouse = 'X' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_bad_y'] = $data[qty];

        // ±ÝÀÏ ÀÔ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and
                         start_date < '$end_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_sum_t'] = $data[qty];

        // ±ÝÀÏ ¹ÝÇ°ÀÔ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where memo = '·ÎÄÉÀÌ¼Ç ¹ÝÇ°ÀÔ°í' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and
                         start_date < '$end_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_ret_t'] = $data[qty];

        // ±ÝÀÏ ºÒ·®ÀÔ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_in 
                   where warehouse = 'X' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and 
                         start_date < '$end_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['in_bad_t'] = $data[qty];

        // ÀüÀÏ Ãâ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_sum_y'] = $data[qty];

        // ÀüÀÏ ¹ÝÇ°Ãâ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where memo = '·ÎÄÉÀÌ¼Ç ¹ÝÇ°Ãâ°í' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_ret_y'] = $data[qty];

        // ÀüÀÏ ºÒ·®Ãâ°í ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where warehouse = 'X' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date < '$start_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_bad_y'] = $data[qty];

        // ±ÝÀÏ Ãâ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and
                         start_date < '$end_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_sum_t'] = $data[qty];

        // ±ÝÀÏ ¹ÝÇ°Ãâ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where memo = '·ÎÄÉÀÌ¼Ç ¹ÝÇ°Ãâ°í' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and
                         start_date < '$end_date' and
                         warehouse <> 'X'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_ret_t'] = $data[qty];

        // ±ÝÀÏ ºÒ·®Ãâ°í
        $query = "select product_id, sum(qty) as qty from 3pl_stock_out 
                   where warehouse = 'X' and
                         domain = '" . _DOMAIN_ . "' and 
                         product_id in ($product_list) and 
                         start_date >= '$start_date' and
                         start_date < '$end_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['out_bad_t'] = $data[qty];

        // ÀüÀÏ ¹è¼Û ÇÕ
        $query = "select product_id, sum(qty) as qty from 3pl_orders
                   where domain = '" . _DOMAIN_ . "' and 
                         status=8 and
                         product_id in ($product_list) and 
                         trans_date_pos < '$start_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['tr_sum_y'] = $data[qty];

        // ±ÝÀÏ ¹è¼Û
        $query = "select product_id, sum(qty) as qty from 3pl_orders
                   where domain = '" . _DOMAIN_ . "' and 
                         status=8 and
                         product_id in ($product_list) and 
                         trans_date_pos >= '$start_date' and
                         trans_date_pos < '$end_date'
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['tr_sum_t'] = $data[qty];
            
        // ¹è¼Û ¿¹Á¤
        $query = "select product_id, sum(qty) as qty from 3pl_orders
                   where domain = '" . _DOMAIN_ . "' and 
                         status=7 and
                         product_id in ($product_list) and 
                         order_cs not in (1,2,3,4,12)
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['tr_sum_w'] = $data[qty];

        // ¹Ì¹è¼Û
        $query = "select product_id, sum(qty) as qty from 3pl_orders
                   where domain = '" . _DOMAIN_ . "' and 
                         status=1 and
                         product_id in ($product_list) and 
                         order_cs not in (1,2,3,4,12)
                         group by product_id";
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_array($result) )
            $stock[strtoupper($data[product_id])]['tr_sum_n'] = $data[qty];

        // °á°ú ¸®ÅÏ
        return $stock;
    }
        
}

?>
