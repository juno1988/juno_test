<?
//========================================
//
// ezadmin���� rtmall�� ����ϱ� ���� 
// date: 2007.11.9 - jk.ryu
// unit test: unit_test/test_rtmall.php
//
require_once "class_db.php";
require_once "class_order.php";
require_once "class_supply.php";
require_once "class_product.php";

class class_rtmall{
    var $m_connect = "";

    // rtmall ��ü�� �����ϸ� �ٷ� rtmall������ connect��
    function class_rtmall()
    {
    	if ( $_SESSION )
	{
		//$_server = "121.156.52.85";
		//$_name   = "rt";
		//$_pass   = "skatjdqlfeld";
		//$this->connect( $_server, $_name, $_pass );
	}
    }

    function connect( $host, $name, $pass )
    {
	$obj = new class_db();

	$this->m_connect = $obj->connect( $host, $name, $pass );
	return $this->m_connect;
    }

    // �ܼ� ���� ������
    function get_info( $seq )
    {
	$query = "select * 
                    from rtmall_orders 
                   where seq    = '$seq' 
                     and domain = '" . _DOMAIN_ . "'";

        $result = mysql_query ( $query, $this->m_connect );
	$data   = mysql_fetch_array ( $result );
	return $data;
    }

    //===================================================
    // set normal: ���� �ֹ� ���� ó��
    // 2007.11.20 - jk
    // unit test: 
    function set_normal( $seq )
    {
    	// ��ϵ� rtmall �ֹ����� ���� Ȯ��
	$this->is_rtmall_order( $seq );    	

debug ( "3PL ($seq) ���� ����ó��");

	//=========================================
	// normal�� �����ϴ� ���� �������� check 
	if ( $this->enable_normal( $seq ) )
	{
	    // status�� 8�� ���̽��� �������� �ʴ´�.
	    $query = "select qty 
                        from rtmall_orders 
	    	       where domain='" . _DOMAIN_ . "' 
		         and seq='$seq' 
                         and status <> 8";
   
	    $result = mysql_query ( $query, $this->m_connect );
    
	    // ��ü ��� ��û
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

	    // �� �ֹ��� ���� ó��
	    $infos[order_cs] = "0";
	    $this->sync_infos( $infos, $seq, 1 );	
	}
    }

    //=========================================
    //
    // ���� normal�� �� �� ������ ��� �� �� �ֳ�?
    // ���ο� rule�� ���� �� -> normalȭ�� ����Ǹ� order_cs�� -1�� �Էµ�
    // date: 2007.11.20 -> ��ҿ� ������
    //
    function enable_normal( $seq )
    {
	$query = "select order_cs 
                    from rtmall_orders 
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
    // set normal_all: ��ü �ֹ� ���� ó��
    // 2007.11.20 - jk
    // unit test: 
    function set_normal_all ( $pack )
    {
    	echo "set normal all";

	$query = "select seq,status from rtmall_orders 
	           where domain='" . _DOMAIN_ . "'
		     and pack='$pack'";

debug ( "set normal all: $query ");

	$result = mysql_query ( $query, $this->m_connect );

	while ( $data = mysql_fetch_array ( $result ) )
            if ( $data[status] != 8 )
	        $this->set_normal( $data[seq] );
	    else
                debug ( "set normal fail: $data[seq] / $data[status] / $data[trans_no] / $data[trans_date_pos]");
    }
   
    //====================================================
    // ��ȯ �۾� ����
    // 2007.11.17 - jk
    // seq: �� �ֹ�
    // new_product_id: ����� �ֹ� ��ȣ
    // new_qty: ����� ����
    // new_seq: �ű� �ֹ� 
    function change_product( $seq, $org_qty, $new_product_id ,$new_qty, $new_seq=0 )
    {
debug ( "3PL ($seq) ��ȯ ó��");
	//=====================================================
        // ��ȯ �۾� �� ���� �۾�
        // date: 2007.11.19 - jk
	$this->change_precheck( $new_seq, $seq );

	//==================================================
	// �̹� ��۵� �ֹ����� check
	// ���: 1 �̹��: 0
	// ��� �Ĵ� ��ȯ�� �߻��ص� �� �ֹ��� ��� ������ ������ ����
	if ( $this->check_trans( $seq ) )
	{
	    $infos[order_cs]     = 6;      // ��� �� ��ȯ ��û
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
	    // �̹���� ��츸 ����..
	    //===================================================
	    // ��ȯ ���� ���� design
	    // 2007.11.19 - jk
	    // 
	    $_rtmall_order   = $this->is_rtmall_order( $seq );
	    $_rtmall_product = $this->is_rtmall_product( $new_product_id );

debug ( " seq: $seq, new_product_id: $new_product_id " );   
 
	    //===========================================
	    // � function�� ����� ������ ����  
	    $_func = $this->_change_selector ( $_rtmall_order, $_rtmall_product );
    
            echo " rtmall_order: $_rtmall_order / $_rtmall_product \n";
            echo "function [change_product]: $_func \n";
  	    //============================================
	    // return�� ����� ���� 
            //
	    // $_func[1][1] = "change_pl2pl";      rtmall ��ǰ�� rtmall ��ǰ���� ��ȯ
	    // $_func[1][0] = "change_pl2self";    rtmall ��ǰ�� ��ü ��ǰ���� ��ȯ - ��� ��
	    // $_func[0][1] = "change_self2pl";    ��ü ��ǰ�� rtmall ��ǰ���� ��ȯ - �� �ֹ� ����
	    // $_func[0][0] = "change_self2self";  ��ü ��ǰ�� ��ü ��ǰ���� ��ȯ - �ƹ��͵� �� ��
	    $this->${_func}( $seq, $org_qty, $new_product_id, $new_qty );
	
	}
    }

    //========================================
    //
    // ��� ���� check 2007.11.19 - jk
    function check_trans( $seq )
    {
    	global $connect;
	$query = "select status from orders where seq='$seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	if ( $data[status] == 8 ) // ���
	    return 1;
	else
	    return 0;
    }

    // is_rtmall_order���� �ڵ����� ��� �Ǵµ�~~
    function change_self2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
	if ( !$this->is_rtmall_reg( $seq ) )
	    $this->order_reg( $seq );
    }

    //==============
    function change_self2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
	//�۾� �ؾ� �ϳ�?
    }

    //================================
    // rtmall ��ǰ�� rtmall�� ����
    // 2007.11.19 - jk
    // unit test: _rtmall_test.php 11.19 - jk
    function change_pl2pl( $seq, $org_qty, $new_product_id, $new_qty )
    {
debug ( "rtmall��ǰ�� rtmall��ǰ���� ����");
	if ( $org_qty == $new_qty )
	{
	    $data = class_product::get_product_infos( $new_product_id );
            $infos[product_id]   = $new_product_id;
            $infos[product_name] = $data[name];
            $infos[options]      = $data[option];
	    $infos[order_cs]     = 5;      // ��� �� ��ȯ ��û
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
    	    //========================================
    	    // 1. ���� �ֹ��� ���� ����
    	    // 2. ���� �ֹ����� �ű� �ֹ� ���� �� ��� ��û
	    // ���� �ֹ��� status
    	    $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
	}
    }

    //================================
    // rtmall ��ǰ�� �ڻ� ��ǰ���� ����
    // 2007.11.19 - jk
    // unit test: _rtmall_test.php 11.19 - jk
    function change_pl2self( $seq, $org_qty, $new_product_id, $new_qty )
    {
debug ( "��� �� ����");

	if ( $org_qty == $new_qty )
	{
	    $infos[order_cs]     = 1;      // ��� �� ��� ��û
	    $this->sync_infos( $infos, $seq );	
	}
	else
	{
    	    //========================================
    	    // 1. ���� �ֹ��� ���� ����
    	    // 2. ���� �ֹ����� �ű� �ֹ� ���� �� ��� ��û
	    // ���� �ֹ��� status
    	    $this->part_cancel( $seq, $org_qty, $new_product_id, $new_qty );
	}
    }

    //========================================
    // 1. ���� �ֹ��� ���� ����
    // 2. ���� �ֹ����� �ű� �ֹ� ���� �� ��� ��û
    function part_cancel( $seq, $org_qty, $new_product_id, $new_qty )
    {
        $data = class_product::get_product_infos( $new_product_id );
        $infos[pack] = $seq;

        // 1. ���� �ֹ��� ���� ����
        $infos[qty] = $org_qty - $new_qty;
        $this->sync_infos( $infos, $seq );	
    
        // 2. ���� �ֹ����� �ű� �ֹ� ���� �� ��� ��û
        $new_info = $this->copy_order ( $seq );

        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];
        $infos[qty]          = $new_qty;
        $infos[order_cs]     = 1;

	// seq_subid ����� 2007.12.31 - jk
        //$this->sync_infos( $infos, $new_info[seq], $new_info[seq_subid] );	
        $this->sync_infos( $infos, $new_info[seq] );	
    }

    //====================================
    // function ����..
    // 2007.11.19 - jk
    //
    function _change_selector ( $_rtmall_order, $_rtmall_product )
    {
	$_func[1][1] = "change_pl2pl";
	$_func[1][0] = "change_pl2self";
	$_func[0][1] = "change_self2pl";
	$_func[0][0] = "change_self2self";

	return $_func[$_rtmall_order][$_rtmall_product];
    }

    //=============================================
    // ��ȯ �۾� �� ���� �۾�
    function change_precheck( $new_seq, $org_seq )
    {
	//=====================================================
	// new_seq�� �ִ� ��� ���ο� �ֹ��� �����´�
	if ( $new_seq )
	{
	    // rtmall ��ǰ ���� ���� check 
	    if ( $this->is_rtmall_use ( $new_seq ) )
	    {
	        $data = class_order::get_order( $new_seq );
		$data[pack] = $org_seq;
	        $this->order_reg( $data );
	    }
	}
    }

    //============================
    // ��ü ��� ��ǰ���� ����f
    // 2007.11.17 - jk
    function change_self_product( $data, $qty, $new_product_id )
    {
	echo "change self product";
    }

    //========================================================
    // rtmall ��ǰ���� rtmall ��ǰ���� ����
    // change rtmall product to rtmall product - 2007.11.17 - jk
    function change_rtmall_product( $data, $qty, $new_product_id )
    {
    	if ( $data[qty] < $qty )
	{
	    echo "���� ����";
	    exit;
	}

	if ( $data[status] == 8 )
	    $this->_change_rtmall_product_after( $data[seq] ); // ��� ���� ���
	else
	    $this->_change_rtmall_product_before( $data[seq], $data[qty], $new_product_id, $qty ); // ��� ���� ���
    }

    //==========================================
    // �ֹ��� ����
    // 2007.11.17 - jk
    function copy_order( $seq )
    {
	$query  = "select * 
                    from rtmall_orders 
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

	// ���� ����
	// arr[seq] / arr[seq_subid] return
	return $this->insert_order( $arr_datas );
    }

    function get_max_subid( $seq )
    {
	// max sub id 
	$query = "select max(seq_subid) seq_subid 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ . "' 
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data   = mysql_fetch_array ( $result );
	return $data[seq_subid];
    }

    //=================================
    // �ֹ� �Է�
    // 2007.11.17 - jk
    function insert_order( $arr_datas )
    {
	$query = "insert into rtmall_orders set ";
	
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
    // ��� �� 3�� ���� ��ǰ���� ��ȯ
    // date: 2007.11.17 - jk
    function _change_rtmall_product_before( $seq, $qty, $new_product_id, $new_qty )
    {
debug ( "��� �� 3�� ������ ��ȯ");
    	// name, option, enable_sale ������ ���۵�
	$data = class_product::get_product_infos( $new_product_id );
        $infos[product_id]   = $new_product_id;
        $infos[product_name] = $data[name];
        $infos[options]      = $data[option];

	if ( $qty != $new_qty )
	{
	    // ��ȯ ��ǰ�� ����� ��..
	    $new_seq = $this->copy_order( $seq );

	    //======================================
	    // ���� ������� �ֹ��� ������ ����
	    // �� ��ǰ�� ������ �ű� �ֹ� ���� 
	    $infos[order_cs]     = 5;      // ��� �� ��ȯ ��û
	    $infos[qty]          = $new_qty;      
	    $this->sync_infos( $infos, $new_seq );	

	    //================================
	    // ���� �ֹ��� ���� ����
	    // ���� �ֹ��� ������ ���� ��
	    // ���� ���� - �ű� �ֹ� ���� 
	    // ������ �ٸ� ��� �ű� �ֹ��� ���� �ȴ�. 
	    $infos2[order_cs]     = 5;      // ��� �� ��ȯ ��û
	    $infos2[qty]          = $qty - $new_qty;      
	    $this->sync_infos( $infos2, $seq );	
	}
	else
	{
	    //==================================================
	    // set infos that would save at rtmall_order table
	    $infos[order_cs]     = 5;      // ��� �� ��ȯ ��û
	    $this->sync_infos( $infos, $seq );	
	}
    }

    //=======================================
    //
    // ��� �� 3�� ���� ��ǰ���� ��ȯ
    // 2007.11.17 - jk
    function _change_rtmall_product_after( $seq )
    {
debug ( "��� �� 3�� ������ ��ȯ");

	echo "��� �� 3�� ���� ��ȯ";
	$infos[order_cs]     = 6;      // ��� �� ��ȯ ��û
	$this->sync_infos( $infos, $seq );	
    }


    // ������ ���� ��
    function sync_infos( $arr_datas, $seq, $seq_subid=0 )
    {
	$query = "update rtmall_orders set ";
	
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

debug ( "[sync infos] $query");

//echo "$query<br>";
	if ( $i > 0 )
	{
	    mysql_query ( $query, $this->m_connect ) or die( $query . mysql_error() );
	}
    }
    // ��ü ���� ����
    function del_trans_all( $pack )
    {
	$query = "update rtmall_orders 
	             set trans_corp     = '', 
                         trans_date     = '',
                         trans_no       = '',
                         trans_date_pos = '',
                         status         = 1,
			 warehouse      = null
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and ( seq = $pack or pack = $pack )";
	debug ( "[del_trasn_all] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    // ���� ���� ����
    function del_trans_info( $seq )
    {
	$query = "update rtmall_orders 
	             set trans_corp    = '', 
                         trans_date    = '',
                         trans_no      = '',
                         trans_date_pos= '',
                         status        = '1',
			 warehouse     = null
                   where status <> 8 
                     and domain='" . _DOMAIN_ . "'
                     and seq = $seq";
	debug ( "[del_trasn_info] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // pack���� ����
    function sync_pack( $old_pack, $new_pack )
    {
        if ( $old_pack)
        {
	    $query = "update rtmall_orders set pack=$new_pack 
                       where domain='" . _DOMAIN_ . "'
                         and pack=$old_pack";
	    mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
        }
    }

    ///////////////////////////////////////////////////
    // pack���� ����
    function do_pack( $seq, $pack )
    {
	$query = "update rtmall_orders set pack=$pack 
                   where domain='" . _DOMAIN_ . "'
                     and seq=$seq";
debug( "[do pack] $query " );
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    ///////////////////////////////////////////////////
    // pack���� ����
    function remove_pack( $seq )
    {
	$query = "update rtmall_orders set pack=null 
                   where domain='" . _DOMAIN_ . "'
                     and seq=$seq";
debug ( "[unpack] $query");
	mysql_query ( $query, $this->m_connect ) or die( mysql_error() );
    }

    //======================================================
    // cs�� sync��
    // 2007.11.16 - jk
    function sync_cs ( $seq, $order_cs )
    {
	if ( $order_cs )
	{
	    $query = "update rtmall_orders set order_cs=$order_cs 
                   where domain='" ._DOMAIN_ ."'
                     and seq='$seq'";	
	    mysql_query ( $query, $this->m_connect );
	}
    }

    //==================================
    // rtmall �ֹ��� ���� ��ȣ �Է�
    function insert_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no)
    {
	if ( $this->is_rtmall_use ( $seq ) )
	{
	    //$pack = $this->check_pack( $seq );
	    //if ( $pack )
	    //	$this->_update_pack_trans_no( $pack, $trans_corp, $trans_no, $warehouse);
	    //else
	    $this->_update_trans_no( $seq, $trans_corp, $trans_no, $warehouse, $insert_all_trans_no);
	}
    }

    //=========================================
    // ���� ���� ���� check
    function check_pack ( $seq ) 
    {
	global $connect;
	$query = "select pack from rtmall_orders 
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
    // ���� ���� �Է�
    // 2007.11.16 - jk
    // ������ �Ϲ��� ��� ó�� ��
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
	$query = "update rtmall_orders 
			     set trans_corp='$trans_corp', 
				 trans_no='$trans_no', 
				 warehouse='$warehouse', 
				 order_cs ='$order_cs', 
				 status   ='$status', 
				 pack     ='$pack', 
				 trans_date=Now()
			   where domain='" ._DOMAIN_ ."'";

	// ���� ��ȣ�� ������ �� �� �� ������
	if ( $insert_all_trans_no )
	{
	    if ( $pack )
	    {
	        mysql_query ( $query . " and pack=$pack", $this->m_connect );
                debug( "[update_trans_no1] $query and pack=$pack" );
	    }
	    else
                debug( "[update_trans_no no pack no] $query and pack=$pack" );
	}

	mysql_query ( $query ." and seq=$seq", $this->m_connect );
debug( "[update_trans_no2] $query and seq=$seq" );
	echo " change rtmall trans no";
    }

    //=======================================
    // ���� ���� �Է�
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

	$query    = "update rtmall_orders 
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
debug( "[pack pack trans no] $query" );
	mysql_query ( $query, $this->m_connect );
	echo "all update";
    }

    //=====================================
    // rtmall �ֹ����� ��ϵǾ����� check
    function is_rtmall_order( $seq )
    {
    	$_return = 0;
	if ( $this->is_rtmall_use( $seq ) )
	{
	    if ( !$this->is_rtmall_reg( $seq ) )
	    {
	    	// $this->order_reg( $seq );
		// �۾� ���� �ʴ´�
		exit;
	    }

	    $_return = 1;	
	}
	return $_return;
    }

    function is_rtmall_reg( $seq )
    {
	$query = "select count(*) cnt 
                    from rtmall_orders 
                   where domain='" . _DOMAIN_ ."'
                     and seq='$seq'";
	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt] ? 1 : 0;
    }

    //==================================
    // rtmall �ֹ��� ���� ��ȣ �Է�
    // local connect ������ ����ؾ� ��
    function is_rtmall_use( $seq )
    {
	global $connect;
	$query = "select product_id from orders where seq='$seq'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	$product_id = $data[product_id];

	// use_rtmall �� check
	if ( $product_id )
	{
	    // �ش� ��ǰ�� rtmall ��ǰ���� check
	    $_result = $this->is_rtmall_product( $product_id );
	    echo "rtmall ���";
	}
	else
	{
	    $_result = 0;
	}	

	return $_result;
    }

    function is_rtmall_product( $product_id )
    {
    	global $connect;
	$query = "select use_rtmall from products where product_id='$product_id'";	
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$_result = $data[use_rtmall];
	return $_result;	
    }

    //======================================================
    // cs�� sync��
    // 2007.11.16 - jk
    function sync_status ( $seq, $status )
    {
	if ( $status )
	{
	    $query = "update rtmall_orders set status = $status
			   where domain='" ._DOMAIN_ ."'
			     and seq='$seq'";	
	    mysql_query ( $query, $this->m_connect );
	}
    }


    //========================================
    // rtmall_regist�� �۾� ��� 2007.11.15 - jk
    function regist_tx( $type, $cnt, $msg, $user, $_date )
    {
	if ( $cnt )
	{
	    //================================================================
            // Q.
	    // req_date�� ��� �ؾ� ����? 14�� ���ָ� 17�ϳ� ��û ���� ���
            //
	    $query = "insert into rtmall_tx set 
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
    // ��� ��û �� 
    function request_detail( $_date )
    {
	$query = "select * from rtmall_tx 
		   where req_date >= '$_date 00:00:00'
                     and req_date <= '$_date 23:59:59'
                     and domain='" . _DOMAIN_ . "'";
// echo $query;
	$result = mysql_query ( $query , $this->m_connect );
	return $result;
    }

    function cnt_req_orders( $arr_options )
    {
	//$sm=1; 
	//$sd=23; 
	//$sy=2008; 

        list( $sy, $sm, $sd ) = split("-", $arr_options[collect_date] );
	$start = mktime(0,0,0,$sm, $sd, $sy); 
	$end   = mktime(23,59,59,$sm, $sd, $sy); 

	$query = "select count(*) cnt from tbl_order
		   where date >=  $start 
                     and date <= $end 
                     and id = 'ezadmin' ";
	
	$result = mysql_query ( $query , $this->m_connect ) or die ( mysql_error() );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    /////////////////////////////////////////////////////
    // �����ڸ� ���� update ezadmin �����ڸ� ���� �κ�
    function update_order( $data )
    {
	if ( $data[seq] )
	{
	    $shop_name = class_C::get_shop_name( $data[shop_id] );

            $query   = "update rtmall_orders ";
            $options = "
                            set 
			    product_id      = '$data[product_id]',
			    pack            = '$data[pack]',
			    product_name    = '$data[product_name]',
			    options 	    = '$data[options]',
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
            debug( "[update_order] $query" . "/" .  $_result );
	 
	    // update�� �� �� ��� 
	    if ( $_result == 0 )
            {
	        $query2 = "insert into rtmall_orders " . $options;
	        $query2 .= " ,seq   = $data[seq]
                             ,domain='" ._DOMAIN_ . "'";

                debug( "[update_order] $query2" );
	        mysql_query ( $query2, $this->m_connect ) ;
            }
	}
 
    }

    ////////////////////////////////////////////
    // ��� ���� Ȯ��
    //
    function is_reg ( $seq )
    {
	$query  = "select ordernum from tbl_order where ordernum=$seq";

	$result = mysql_query ( $query, $this->m_connect ) ;
	$data   = mysql_fetch_array ( $result );

	return $data[ordernum] ? $data[ordernum] : 0;
    }

    //=======================================
    // �ֹ� ���� 
    // date: 2007.11.13
    function del_orders ( $seqs )
    {
    	$query = "delete from tbl_order where ordernum in ( $seqs ) and status=1";
	mysql_query ( $query, $this->m_connect );
	
    	$query = "delete from tbl_orderlist where orderno in ( $seqs ) and status=1";
	mysql_query ( $query, $this->m_connect );
    }

    function get_transno( $seq , &$tot_rows)
    {
	$query = "select a.isinvoice, b.delivery 
                    from tbl_orderlist a, tbl_agent_member b
                   where a.isagencyid = b.id
                     and a.orderno = $seq";	

	$result   = mysql_query ( $query, $this->m_connect );
        $tot_rows = mysql_num_rows( $result );

        $data     = mysql_fetch_array ( $result );
	return $data;
    }

    //=======================================
    // �ֹ� ���
    // date: 2007.11.13
    function order_reg ( $data )
    {
	if ( !$this->is_reg( $data[seq] ) )
	{
	    $supply_id = class_supply::get_supplyid( $data[supply_id] );
	    // $pnum      = class_product::get_barcode( $data[product_id] );
	    $pnum      = $data[product_id]; // ����

            list( $sy, $sm, $sd ) = split("-", $data[collect_date] );
	    $collect_date         = mktime(0,0,0,$sm, $sd, $sy); 

	    if ( $data[trans_who] == "����" )
		$prepay = 2500;  
 
	    // �ֹ� ��� 
	    $cost      = class_product::get_shop_price( $data[product_id] );
	    $price     = $data[qty] * $cost;
	    $recv_zip  = str_replace( "-", "", $data[recv_zip] );
	    $order_zip = str_replace( "-", "", $data[order_zip] );

	    $tel = $data[order_tel]?$data[order_tel]:$data[recv_tel];
	    $hp  = $data[order_mobile]?$data[order_mobile]:$data[recv_mobile];

	    $query = "insert tbl_order set 
			    date        = $collect_date,
			    ordernum    = $data[seq],
                            id		= 'ezadmin',
			    name        = '$data[order_name]',
			    post	= '$order_zip',
			    addr	= '$data[order_address]',
			    tel		= '$tel',
			    hp		= '$hp',
			    receiver	= '$data[recv_name]',
			    repost      = '$recv_zip',
			    readdr      = '$data[recv_address]',
			    retel       = '$data[recv_tel]',
			    rehp        = '$data[recv_mobile]',
			    comment	= '$data[memo]',
			    status	= 1,
			    ispay	= 1,
			    paytype	= 2,
			    isagency	= 1,
			    price       = '$price',
			    isagencyid	= '$supply_id'";
	    if ( $prepay )
                 $query .= ",prepay=$prepay";

	    debug ( $query );
	    mysql_query ( $query, $this->m_connect );
	    //$affect = mysql_affected_rows();

	    // ��ǰ ��� 
            // pnum�� barcode�� ���� ��
            // pnum�� rtmall�� idx�� ��� �ִ� ����

	    //////////////////////////////////////////
	    // cost : �ܰ�
            // price : �� �ݾ�
	    // 2008.3.4 - jk
  	    $query = "insert tbl_orderlist set 
			    date        = $collect_date,
			    orderno     = $data[seq],
			    id		= 'ezadmin',
			    pcode	= '$data[product_id]',
			    pnum        = '$pnum',
			    name	= '$data[product_name]',
			    opt         = '$data[options]',
			    cost	= '$cost',
			    ea		= '$data[qty]',
			    price	= '$price',
			    status	= 1,
			    isagency	= 1,
			    isagencyid	= '$supply_id',
			    paytype     = 2,
			    ispay	= 1,
			    island	= '$data[island]'";
	    if ( $prepay )
                 $query .= ",prepay=$prepay";

	    debug ( $query );
	    mysql_query ( $query, $this->m_connect );
	    // $affect = mysql_affected_rows();
	}
	else
	{
	    debug ( " $data[seq] already reg " );
	    $result = 0; 	// fail
	}
	   
	return $affect; 
    }
    
    //=======================================
    // ��ϵ� ��ǰ�� rtmall�� ���� ��
    function product_reg( $product_id , $data="")
    {
    	global $connect;

	if ( !$data )
	    $data = class_product::get_product_info ( $connect, $product_id );

	// rtmall��ǰ�� ��쿡�� ���
	if ( $data[use_rtmall] ) 
	{
		$options = str_replace( array("\r","\n","\r\n"),"", $data[options] );

		//===================================
		// rtmall�� ��ǰ data ����
		$query = "insert rtmall_products set 
					domain      = '" . _DOMAIN_ ."',
					product_id  = '$data[product_id]',
					product_name= '$data[name]',
					options     = '$options',
					crdate      = Now(),
					enable_sale = '$data[enable_sale]',
					barcode     = '$data[barcode]',
					org_id      = '$data[org_id]',
					reg_date    = Now() ";
debug ( $query );
		mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
		// exit;
	}
    }

    //////////////////////////////////////
    //�ʱ�ȭ
    function init_priority()
    {
	$query = "update rtmall_orders 
                     set priority=null 
                   where domain='" . _DOMAIN_ ."'
                     and status=1 and priority <> 99";
	mysql_query ( $query , $this->m_connect) or die( mysql_error() );
	return mysql_affected_rows();
    }


    function product_count( $arr_items )
    {
	$query = "select count(*) cnt from rtmall_products ";
	$query.= $this->build_option( $arr_items );

	$result = mysql_query ( $query, $this->m_connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    //=============================================
    // query�� ���� option����
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
    // ��ϵ� ��ǰ�� ������ ���� ��� ���� update
    // 2007.11.12
    //
    function product_update( $product_id )
    {
    	global $connect;
	$data = class_product::get_product_info ( $connect, $product_id );

	// rtmall��ǰ�� ��쿡�� ���
	if ( $data[use_rtmall] ) 
	    if ( $this->check_reg( $product_id ) )   // ���� ��ϵǾ� �ִ� ��ǰ���� ���� check�ؾ� ��
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
    // �ɼ��� ���� Ȥ�� ���
    // org_id: �� ��ǰ id
    // option_id: ����� id
    function option_update( $org_product_id, $new_product_id )
    {
    	global $connect;
	$data = class_product::get_product_info ( $connect, $new_product_id );

	// ���� ��ϵǾ� �ִ� ��ǰ���� ���� check�ؾ� ��
	if ( $data[use_rtmall] ) 
	    if ( $this->check_reg( $org_product_id ) )               
	    	$this->_update( $data, $org_product_id );
	    else
	    	$this->product_reg( $new_product_id );
    }

    //=====================================
    // ���� ��� ���� üũ
    // ����� Ȯ�ε� ��� 1�� ��ȯ
    // ����� Ȯ�� �ȵ� ��� 0�� ��ȯ
    function check_reg( $product_id )
    {
    	$query = "select count(*) cnt 
                    from rtmall_products 
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
        //===================================
        // rtmall�� ��ǰ data ����
        $query = "update rtmall_products set 
				product_id  = '$data[product_id]',
    				product_name= '$data[name]',
    				options     = '$data[options]',
    				barcode     = '$data[barcode]',
				enable_sale = '$data[enable_sale]'
			where	domain      = '" . _DOMAIN_ ."'
			  and   product_id  = '$org_id'";
        //debug ( $query );
        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }

    //////////////////////////////////////////////////////
    // ��� ���� ��� 
    function cancel_hold()
    {
	global $connect, $seq;
	$query = "update rtmall_orders 
                     set hold=0
		   where domain = '" . _DOMAIN_ ."'
                     and (seq=$seq or pack=$seq)";
        mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
    }


}

?>
