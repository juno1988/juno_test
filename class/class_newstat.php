<?
include_once "class_top.php";
include_once "class_shop.php";
include_once "class_db.php";

class class_newstat extends class_top
{
    // gmarket rule
    var $m_rule = array();
    function init_rule()
    {
        // ������ ��
        $this->m_rule[2] = array(
                    cnt_order   => array( field => 'qty', rule=>'count' ), 
                    cnt_product => array( field => 'qty', rule=>'sum'),    
            );
    }
    
    //****************************************
    //
    // class�� �����Ǹ� �� ��� ����Ǵ� �κ�
    // stat_product�� stat_shop�� ������ ����
    // 2008.10.30 - jk
    //
    function class_newstat()
    {
        global $connect, $from_date, $to_date, $date_type;
        
        $_datas['list'] = array();
        $arr_idx        = array(); // date index
        $msg            = '';
        
        // �����ؾ��� data check
        $query = "select crdate
                    from stat_shop 
                   where crdate >= '$from_date' 
                     and crdate <= '$to_date'";
                     
        if ( $date_type ) 
            $query .= " and date_type='$date_type'";
        
        $query .= " group by crdate";
                     
        //******************************************
        // ������ �������� ������ ������
        //******************************************
        $result = mysql_query ( $query, $connect );        
        while ( $data = mysql_fetch_array( $result ) )  
        {
            // ���� ��¥�� �߰� ���� �ʴ´�.
            if ( $data[crdate] != date('Y-m-d') )
                $arr_idx[] = $data[crdate];        
        }
        
        //******************************************
        // ���� �� check
        // ������ ������ ����..
        //******************************************
        // ���ð� todate�� �⺻ - �� ���� - 2008.11.4 jk
        $_default = intval((strtotime( date('Y-m-d') ) - strtotime( $to_date) ) / 86400 );   
        $interval = intval((strtotime( $to_date) - strtotime( $from_date) ) / 86400 );   
        
        // ������ ������ ���ƾ� �� $i < $interval�� �����
        for ( $i=0; $i<=$interval; $i++ )
        {
            $j    = $i + $_default;
            $_key = date('Y-m-d', strtotime('-' . $j . ' day'));
            
            // ��ϵ� ��¥�� �ִ��� ���� check
            if ( array_keys( $arr_idx, $_key ) )  
            {
                debug( "$_key exist" ,0);
            }
            else 
            {   
                debug( "$_key None exist" ,0);
                                
                // ���� ��ǰ ó��
                $this->build_product_data( $_key, $date_type, &$msg );      
                
                // �Ǹ�ó �� ���� �ٸ�...
                // stat_shop�� ������ �־�� �� - ��¥��, �Ǹ�ó�� ���� ������ ����
                // ������ ���� üũ - 2008.11.10 jk
                $this->build_shop_data( $_key, $date_type, &$msg );
            }           
        }
    }
    
    //****************************************
    // 2008-12-26 - jk
    // period_list : �Ⱓ�� ���    
    function period_list()    
    {
        global $shop_id, $date_type,$from_date, $to_date, $query;
        global $connect;
        
        $_data             = array();
        $_data['list']     = array(); // ��ü ������ ���
        $_data['avg_list'] = array(); // ��ü ���  
        
        // ���� ����
        $_option = "  ";
        
        //----------------------------------------
        // ��ü ���
        $_query = "select avg(total_supply_price) supply_price,crdate, shop_id%100 shop_code 
                     from stat_shop  
                    where crdate >='$from_date' and crdate <='$to_date'";
                    
        
        
        $_query .=  " and date_type='collect_date'"; 
        
        if ( $shop_id )
        {
            $_query .= " and shop_id=$shop_id  group by crdate,shop_id%100 ";    
        }
        else
            $_query .= " group by crdate";
        
        // debug ( "[period_list] $_query",1 );
        // echo $_query;
        // exit;                
        $result = mysql_query( $_query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_data['list'][ $data['crdate'] ] = array( supply_price => $data['supply_price'] ? $data['supply_price'] : 0 );   
        }
        
        //----------------------------------------
        // ��ü ���
        // stat server�� ������ �����;� ��.
        // 
        $stat_connect = mysql_connect( "61.109.255.60", "mento", "mento" );
        // $stat_connect = mysql_connect( "localhost", "mento", "mento" );
        mysql_select_db("mento", $stat_connect);
              
        $_option .= " and shop_id=$shop_code";
        
        $_query = "select crdate, avg(tot_price) tot
                     from stat_user_shop 
                    where crdate >='$from_date' and crdate <='$to_date' "; 
        
        if ( $shop_id )
        {
            $shop_code = $shop_id % 100;   
            $_query .= " and shop_id%100=$shop_code ";    
        }
               
        $_query .= " group by crdate";
        
        if ( $shop_id )
            $_query .= ",shop_id%100";
        
        $result = mysql_query( $_query, $stat_connect );
        //echo $_query;        
        //exit;
        
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_data['avg_list'][$data['crdate']] = array( tot => $data['tot'], middle => $data['middle'] );   
        }
        
        return $_data;
    }
    
    
    //****************************************
    // 2008-11-28 - jk
    // ��ǰ list
    // disp_type : product:��ǰ��        / option: �ɼǺ�
    // query_type: product_id: ��ǰ�ڵ�  / product_name: ��ǰ��
    //  �˻� ��ư Ŭ���ø� ó���ϴ� ���� -> $_str_query�� �ִ� ��� �����ѵ�..����..function ������ �ʿ��� ��..
    function get_product_price()    
    {
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query;
        global $connect, $start, $limit,$action;
        
        debug ( "get produt_price", 1);
        
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
        
        // �˻� ������ �ִ� ���   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // ��ǰ�� ��ȸ     
                    $_options .= " and c.org_id = '$_str_query'";
                else                           // �ɼǺ� ��ȸ
                    $_options .= " and c.product_id = '$_str_query'";
                    
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $_options .= " and c.name like '%$_str_query%'";
            }   
        }
                
        //--------------------------------------
        // ��¥ date_type, from_date, to_date
        $_options .= " and b.{$date_type} >= '$from_date 00:00:00' 
                    and b.{$date_type} <= '$to_date 23:59:59' ";
                    
        //--------------------------------------
        // shop_id
        if ( $shop_id )
            $_options .= " and b.shop_id = '$shop_id' ";
            
        //--------------------------------------
        // supply_id
        if ( $supply_id )
            $_options .= " and c.supply_code = '$supply_id' ";             
        
        // logic 2. begin of �Ǹ� ����
        //  �� �Ǹ� / ���갡 ���.
        // ���� ���
        $query_total = "select sum(c.org_price*b.qty) tot_org_price " . $_options;

debug ( "����xx123: " . $query_total );

        $val['query'] = $query_total;
        $result      = mysql_query ( $query_total, $connect );        
        $data        = mysql_fetch_array( $result );        
        $val['tot_org_price']    = $data['tot_org_price'];
        
        // ���갡 ����
        $query_total = "select sum(b.supply_price) tot_supply_price " . $_options . " group by b.seq";
       
	 
        $result      = mysql_query ( $query_total, $connect );        
        while ( $data  = mysql_fetch_array( $result ) )
            $val['tot_supply_price'] = $val['tot_supply_price'] + $data['supply_price'];
        
        // end of logic 2        
        return $val;
        
        //------------------------���� �ٽ� «-----------------------
        $ids = "";
        // �˻� ������ �ִ� ���   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // ��ǰ�� ��ȸ  
                {   
                    // ��ǰ id�� ã�ƾ� ��..
                    $query = "select product_id from products 
                               where ( product_id='$_str_query' or org_id='$_str_query' )";
                               
                    $result = mysql_query( $query, $connect );
                    
                    while ( $data = mysql_fetch_array( $result ) )
                    {
                        $ids .= "'$data[product_id]',";   
                    }
                    $ids = substr( $ids, 0, strlen( $ids ) -1 ); 
                }
                else                           // �ɼǺ� ��ȸ
                    $ids = "'$_str_query'";
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $query .= " and c.name like '%$_str_query%'";
            }   
        }
        
        
        $query = "select sum( (b.supply_price+b.supply_price)*b.qty ) tot_supply_price 
			,sum(c.org_price) tot_org_price ";
        
        $query .= " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and b.product_id = c.product_id";
                     
        
        
        //--------------------------------------
        // ��¥ date_type, from_date, to_date
        $query .= " and b.{$date_type} >= '$from_date 00:00:00' 
                    and b.{$date_type} <= '$to_date 23:59:59' ";
                    
        //--------------------------------------
        // shop_id
        if ( $shop_id )
            $query .= " and b.shop_id = '$shop_id' ";
            
        //--------------------------------------
        // supply_id
        if ( $supply_id )
            $query .= " and c.supply_code = '$supply_id' "; 
           
        debug( $query, 1 );
        
        // ��ü ���� ���
        $result = mysql_query ( $query, $connect );   
        $data   = mysql_fetch_array( $result );
        
        $_ret['tot_supply_price'] = $data['tot_supply_price']; 
        $_ret['tot_org_price']    = $data['tot_org_price']; 
        
        
         debug( "--------------tot org price: " . $_ret['tot_org_price'], 1 );
        
        return $_ret;
                              
    }
    
    //========================================
    // Ư�� ��ǰ�� ������ �� ���� ��۰��� ������.
    // return data
    // val
    //  |- trans
    //  |     |- ���� : ���� ��� ����
    //  |     |- ���� : ���� ��� ����
    //  |- trans_pack
    //  |     |- ���� : ���� ��۽� ��۵� ��ǰ�� ���� -> �������� ���� docs�� �����Ǿ� ����.
    //  |     |- ���� : ���� ��۽� ��۵� ��ǰ�� ����
    //  |- supply_trans_cnt : ������
    //    
    // ���� ����: pre_deliv_count (������ �߰�) ������ �ù��� ���� �� �ݾ� 
    // ���� ����: post_deliv_count
    // ������   : paid_deliv_count => supply_trans_cnt 2008-12-4
    // ��ǰ ������ ���� ���: product_id
    // ��ǰ ��� ����:      : pack_deliv_qty
    function get_trans_info()
    {
        // ������ ����
        // ���� ���� , ���� �� ��ǰ ��
        // ���� ����
        //
        global $connect;
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type;
        
        $ids = "";
        $val = array();
        
        // begin of ��ǰ �ڵ� ���ϱ�
        // ��ǰ �ڵ� ���ϱ� ids �� product_id list�� ���� ��.
        $query = "";
        if ( $_str_query )
        {
            $query = "select product_id from products ";
            
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // ��ǰ�� ��ȸ     
                    $query .= " where org_id = '$_str_query'";
                else                           // �ɼǺ� ��ȸ
                    $query .= " where product_id = '$_str_query'";
                    
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $query .= " where name like '%$_str_query%'";
            }   
            
            //---------------------------------------            
            if ( $supply_id )
                $query .= " and supply_code = '$supply_id' "; 
        
        
            $result = mysql_query( $query, $connect );            
           
            while ( $data = mysql_fetch_array( $result ) )
            {
                $ids .= "'" . $data['product_id'] . "',";
            }
            
            $ids = substr( $ids, 0, strlen( $ids ) -1 ); 
        }
        // end of ��ǰ �ڵ� ���ϱ�
        else
        {
            
            $query  = "select count(*) cnt, trans_who ";  
            $_option .= " from orders
                       where {$date_type} >= '$from_date 00:00:00' 
                         and {$date_type} <= '$to_date 23:59:59' ";
                         
            // shop_id
            if ( $shop_id )
                $_option .= " and shop_id = '$shop_id' "; 
            
            // supply_id
            if ( $supply_id )
                $_option .= " and supply_id = '$supply_id' ";      
                       
            $query .=  $_option . " and (seq = pack or pack is null or pack='') group by trans_who"; 
            
            debug( "�Ⱓ�� ��� ���� : $query", 1);
            
            $result = mysql_query( $query, $connect );
        
            // ��, ���� ���� ����
            $val['trans'] = array();
            while ( $data   = mysql_fetch_array( $result ) )
            {
                $val['trans'][$data[trans_who]] = $data[cnt];               
                debug( "cnt: $data[cnt] / $data[trans_who]", 1 );
            }  
            
            // ������
            // ���� ������ + ���� ������            
            $supply_trans_cnt      = $this->get_supply_cnt( $_option );
            $val[supply_trans_cnt] = $supply_trans_cnt;
            
            return $val;
        }
                
        
        //****************************************************
        // begin of case 1
        // case 1 Ư����ǰ�� ���ҹ��, ���ҹ�� ���� ( pre_deliv_count , post_deliv_count )
        //  case 1.1 ���� ������
        $query  = "select count(*) cnt, trans_who ";  
              
        $_option .= " from orders a, stat_product b
                   where a.seq = b.seq 
                     and {$date_type} >= '$from_date 00:00:00' 
                     and {$date_type} <= '$to_date 23:59:59' ";
        
        //--------------------------------------
        // �˻� ������ �ִ� ���   query_type   
        // $_option �� �Ʒ������� ��� ����� ���� 2008.12.4
        if ( $ids )
        {
            $_option .= " and b.product_id in ( $ids ) ";
        }
        
        // shop_id
        if ( $shop_id )
            $_option .= " and a.shop_id = '$shop_id' ";
        
        // ���� ��ǰ ������ ����.
        $query .=  $_option . " and (a.pack is null or a.pack='') group by a.trans_who"; 
        $result = mysql_query( $query, $connect );
        
        // ��, ���� ���� ����
        $val['trans'] = array();
        while ( $data   = mysql_fetch_array( $result ) )
        {
            $val['trans'][$data[trans_who]] = $data[cnt];               
            debug( "cnt: $data[cnt] / $data[trans_who]", 1 );
        }        
        
        //  case 1.2 ���� ������
        //  ����..�׳�..
        //  ��ǰ�� ��ۺ� ���ϴ°� ������..8�� ��� ��ǰ�� 20�� ���� �ܰ� * �� ��� ����?
        $query  = "select trans_pack, sum(a.qty) qty, trans_who ";          
        $query .=  $_option . " and (a.pack is not null and a.pack <> '') group by a.pack";         
        
        debug( $query, 1 );
        
        $result = mysql_query( $query, $connect );
        
        // ��, ���� ���� ����
        while ( $data   = mysql_fetch_array( $result ) )
        {
            // debug( "��� ����2: " . $val['trans']['����'] . ' + ' . $data[qty] . " /  " . $data[trans_pack], 1 );
            
            $val['trans'][$data[trans_who]] = $val['trans'][$data[trans_who]] + ( $data[qty] / $data[trans_pack] );
            
        }
        debug( "��� ����2: " . ceil( $val['trans']['����'] ), 1 );
        
        //****************************************************
        // end of case 1
        //****************************************************
        
        
        //****************************************************
        // begin of case 2
        // case 2 ��ǰ �ڵ�, ��ǰ��� ����
        // ��ۻ�ǰ ���� - 2008-12-3 - jk
        $query  = "select a.trans_pack, a.trans_who,a.pack,a.product_id,a.qty,a.collect_date,a.shop_id " . $_option;
        // $query .= " group by product_id";
        
        //--------------------------------------
        // supply_id
        debug( $query, 1 );
        
        $result = mysql_query ( $query, $connect );
        
        // data�� �ߺ����� �������� sum�� �� �� ����            
        $trans_pack = 0;
        $val['trans_pack'] = array();
        $_pack = '';
        $_product_id = '';        
        while ( $data = mysql_fetch_array( $result ) )
        {
            // $trans_pack = $trans_pack + $data[trans_pack];
            if ( $_pack != $data[pack] || $_product_id != $data[product_id] || $data[pack] == '')
            {
                $val['trans_pack'][$data[trans_who]] = $val['trans_pack'][$data[trans_who]] + $data['trans_pack'];   
            }
            $_pack       = $data[pack];
            $_product_id = $data[product_id];
        }
        
        debug( "trans pack: ��:". $val['trans_pack']['����'] . " / ��: " . $val['trans_pack']['����'], 1 );
            
        //****************************************************
        // end of case 2
        //****************************************************
                
        //****************************************************
        // begin of case 3
        // case 3 ������ ����
        // supply_trans_cnt 2008-12-4
        // ���� 
        // 1, pack��ȣ�� ã�� �� ������ ���� trans_pack��� ã�´�.
        $supply_trans_cnt      = $this->get_supply_cnt( $_option );
        $val[supply_trans_cnt] = $supply_trans_cnt;
        
        //****************************************************
        // end of case 3
        //****************************************************
        return $val;
    }
    
    //*********************************************
    //
    // ������ ���� 2008-12-9 jk
    function get_supply_cnt( $_option )
    {
        global $connect;
        
        $supply_trans_cnt = 0;
        // �Ϲ� ������ ����        
        $query = "select sum(trans_pack) s 
                         $_option 
                     and (pack is null or pack='')
                     and pre_paid='������'";

        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        $supply_trans_cnt = $data[s] ? $data[s] : 0;
        
        // ���� ���� �� ������ ������ ã�´�
        $query = "select pack 
                         $_option ";
                        
        $result = mysql_query( $query, $connect );
        $packs  = '';
        while ( $data   = mysql_fetch_array( $result ) )
        {
            $packs = $data[pack] . ",";
        }
        $packs = substr( $packs, 0, strlen( $packs ) -1 );
        
        debug( $packs, 1 );
        
        if ( $packs )
        {
            $query = "select count(*) c from orders where pack in ( $packs ) and pre_paid='������'";
            debug( $query, 1 );
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_array( $result );
            $supply_trans_cnt = $supply_trans_cnt + ( $data[c] ? $data[c] : 0 );
        }
        
        
        debug( "������ ����:  $supply_trans_cnt", 1 );
        return $supply_trans_cnt;
    }
    
    //****************************************************
    //
    // ���ذ� �� �ȵǼ� �ٽ� ����� �� ..2008-12-2 jk
    //   1. ��ǰ�� ����Ʈ ���
    //   2. ��ü ��ǰ�� ���� �����ݾ� + ���� ���
    //      stat_product �� ������ �о�;� ��.
    //      ���� ���� �ݾ��� orders���� �о�;� ��, ������ stat_product + products���� �о�;� ��.
    //
    function product_list($shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type)
    {
        global $connect, $start, $limit,$action;
        
        $start = $start ? $start : 0;
        $limit = $limit ? $limit : 20;        
        
        $arr_product['product']= "org_id";
        $arr_product['option'] = "product_id";
        
        // ������ �ִ°��..
        $ids = $this->get_ids();
        
        //exit;
        
        // list ���� ����
        // trans_pack�̶�? 
        $query = "select sum(b.qty) qty, b.order_cs, c.{$arr_product[ $disp_type ]} product_id, b.shop_id, count(*) cnt, b.trans_who, sum(b.trans_pack)
                        , c.name,c.options, c.org_price
			, sum((b.supply_price+b.extra_supply_price)*b.qty) supply_price, sum( b.amount) amount, c.org_price,c.supply_code ";
                         
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
         
        if ( $ids )
            $_options .= " and a.product_id in ( $ids ) ";
                        
        //--------------------------------------
        // ��¥ date_type, from_date, to_date
        $_options .= " and b.{$date_type} >= '$from_date 00:00:00' 
                    and b.{$date_type} <= '$to_date 23:59:59' ";
                    
        //--------------------------------------
        // shop_id
        if ( $shop_id )
            $_options .= " and b.shop_id = '$shop_id' ";
            
        //--------------------------------------
        // supply_id
        if ( $supply_id )
            $_options .= " and c.supply_code = '$supply_id' ";             
        
        //***************************************************
        // logic 1. begin of ���� Ȯ��
        if ( $disp_type == "product" ) // ��ǰ�� ��ȸ        
            $query_cnt = "select count(distinct c.org_id) cnt " . $_options;
        else                           // �ɼǺ� ��ȸ        
            $query_cnt = "select count(distinct c.product_id) cnt " . $_options;
        
        $result    = mysql_query ( $query_cnt, $connect );        
        $data      = mysql_fetch_array( $result );        
        $val['total_rows'] = $data[cnt];
        
        // end of ���� Ȯ��
        
        //*****************************************************
        // logic 2. begin of �Ǹ� ����
        //  �� �Ǹ� / ���갡 ���.
        // ���� ���
        /*
        $query_total = "select sum(c.org_price) tot_org_price " . $_options;
        $result      = mysql_query ( $query_total, $connect );        
        $data        = mysql_fetch_array( $result );        
        $val['tot_org_price']    = $data['tot_org_price'];
        
        // ���갡 ����
        $query_total = "select b.supply_price " . $_options . " group by b.seq";
        
        $result      = mysql_query ( $query_total, $connect );        
        while ( $data  = mysql_fetch_array( $result ) )
            $val['tot_supply_price'] = $val['tot_supply_price'] + $data['supply_price'];
        
        // end of logic 2
        */
        
        // ��ǰ�� ��ȸ
        if ( $disp_type == "product" ) // ��ǰ�� ��ȸ        
            $_options .= " group by c.org_id, b.order_cs";                         
        else   // �ɼǺ� ��ȸ        
            $_options .= " group by c.product_id, b.order_cs";    
        
        $query .= $_options . " limit $start, $limit"; 
        
        // echo $query;
        // exit;
        debug( "[product_list] $query", 1 );
        // exit;
        
        $result = mysql_query ( $query, $connect ); 
           
        $val['list'] = array();    
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $disp_type == "product" )
                $product_id = $data['product_id'];
            else
                $product_id = $data['product_id'];
            
            //debug( " disp_type: $disp_type / $product_id ",1 );
            //exit;
            
            $val['list'][$product_id][shop_id]                   = $data[shop_id];
            $val['list'][$product_id][order_cs][$data[order_cs]] = $data[qty];            
            $val['list'][$product_id][amount]                    = $val['list'][$product_id][amount]       + $data[amount];
            $val['list'][$product_id][supply_price]              = $val['list'][$product_id][supply_price] + $data[supply_price];            
            $val['list'][$product_id][tot_qty]                   = $val['list'][$product_id][tot_qty]      + $data[qty];
            $val['list'][$product_id][org_price]                 = $data[org_price];
            $val['list'][$product_id][name]                      = $data[name];
            $val['list'][$product_id][options]                   = $data[options];         
        } 
        
        return $val;
    }
    
    //****************************************
    // 2008-11-20 - jk
    // ��ǰ list
    // disp_type : product:��ǰ��        / option: �ɼǺ�
    // query_type: product_id: ��ǰ�ڵ�  / product_name: ��ǰ��
    // 
    function get_product_list($shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type)
    {
        global $connect, $start, $limit,$action;
                
        $start = $start ? $start : 0;
        $limit = $limit ? $limit : 20;        
        
        if ( $disp_type == "product" ) // ��ǰ�� ��ȸ            
            $query = "select sum(a.qty) qty, b.order_cs, c.org_id product_id, b.shop_id, count(*) cnt, b.trans_who, b.trans_pack ";
        else if ( $disp_type == "option" )
            $query = "select sum(a.qty) qty, b.order_cs, c.product_id, c.options, b.shop_id, count(*) cnt, b.trans_who ";
            
        $query .= ", c.name, c.org_price, sum((b.supply_price+b.extra_supply_price)*b.qty) supply_price, sum( b.amount) amount, c.org_price";
        
        $query .= " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
     
        // �˻� ������ �ִ� ���   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // ��ǰ�� ��ȸ     
                    $query .= " and c.org_id = '$_str_query'";
                else                           // �ɼǺ� ��ȸ
                    $query .= " and a.product_id = '$_str_query'";
                    
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $query .= " and c.name like '%$_str_query%'";
            }   
        }
                
        //--------------------------------------
        // ��¥ date_type, from_date, to_date
        $query .= " and b.{$date_type} >= '$from_date 00:00:00' 
                    and b.{$date_type} <= '$to_date 23:59:59' ";
                    
        //--------------------------------------
        // shop_id
        if ( $shop_id )
            $query .= " and b.shop_id = '$shop_id' ";
            
        //--------------------------------------
        // supply_id
        if ( $supply_id )
            $query .= " and c.supply_code = '$supply_id' "; 
        
        //-------------------------------------------------------
        // ������ ����
        // 2008.11.26 - jk
        if ( $action == "shop_detail" )
            $query_trans_who = $query . " group by b.shop_id,b.trans_who";
        else
            $query_trans_who = $query . " group by b.trans_who";
        
        // ��ǰ�� ��ȸ
        if ( $disp_type == "product" ) // ��ǰ�� ��ȸ        
            $query .= " group by c.org_id, b.order_cs";                         
        else   // �ɼǺ� ��ȸ        
            $query .= " group by a.product_id, b.order_cs";
        
        
        // action shop_detail => �Ǹ�ó �� Ŭ���� �����..
        if ( $action == "shop_detail" )
            $query .= " , b.shop_id";       
        else
            $query .= " limit $start, $limit";        
        
        $val = array();        
        debug( $query_trans_who,1 );        
        debug( "--------------------------------------",1 );        
        
        $result = mysql_query ( $query, $connect );        
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $action == "shop_detail" )
            {                
                $val[$data[shop_id]][product_id]                = $data[product_id];
                $val[$data[shop_id]][order_cs][$data[order_cs]] = $data[qty];            
                $val[$data[shop_id]][amount]                    = $val[$data[product_id]][amount] + $data[amount];
                $val[$data[shop_id]][supply_price]              = $val[$data[product_id]][supply_price] + $data[supply_price];            
                $val[$data[shop_id]][tot_qty]                   = $val[$data[product_id]][tot_qty] + $data[qty];
                $val[$data[shop_id]][org_price]                 = $data[org_price];
                $val[$data[shop_id]][name]                      = $data[name];
                $val[$data[shop_id]][options]                   = $data[options];
            }
            else            
            {
                $val[$data[product_id]][shop_id]                   = $data[shop_id];
                $val[$data[product_id]][order_cs][$data[order_cs]] = $data[qty];            
                $val[$data[product_id]][amount]                    = $val[$data[product_id]][amount] + $data[amount];
                $val[$data[product_id]][supply_price]              = $val[$data[product_id]][supply_price] + $data[supply_price];            
                $val[$data[product_id]][tot_qty]                   = $val[$data[product_id]][tot_qty] + $data[qty];
                $val[$data[product_id]][org_price]                 = $data[org_price];
                $val[$data[product_id]][name]                      = $data[name];
                $val[$data[product_id]][options]                   = $data[options];    
                // �Ʒ� ���� ����
                //$val[$data[product_id]]['pre_deliv_cnt']           =  10;        
                //$val[$data[product_id]]['post_deliv_cnt']          =  10;        
            }
        } 
        
        //=================================================
        // ��ǰ�� �� ���� ����..
        $result = mysql_query ( $query_trans_who, $connect );   
    
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $action == "shop_detail" )
                $val[ $data[shop_id] ][ $data[trans_who] ]    = $data[cnt];                
            else
                $val[ $data[trans_who] ] = $data[cnt];                
        }

        // print_r ( $val );
        
        return $val;
    }
    
    
    //******************************************
    // ���� ��� ��������
    // date: 2008.10.28 - jk
    // date_type: collect_date, trans_date_pos
    function stat_detail($shop_id, $date_type,$from_date, $to_date)
    {
        global $connect;
        debug( "stat_detail" ,1 );
         
        //******************************************
        // ������ �� data��������
        //******************************************
        $query = "select crdate, 
                         shop_id, 
                         sum(cnt_order)          cnt_order, 
                         sum(total_shop_price)   total_shop_price, 
                         sum(pre_trans_cnt)      pre_trans_cnt, 
                         sum(supply_trans_cnt)   supply_trans_cnt, 
                         sum(post_trans_cnt)     post_trans_cnt,
                         sum(cancel_price)       cancel_price,
                         sum(total_supply_price) total_supply_price,                         
                         sum(total_org_price)    total_org_price
                    from stat_shop 
                   where crdate >= '$from_date' 
                     and crdate <= '$to_date'";
                     
        if ( $date_type ) 
            $query .= " and date_type='$date_type'";
            
        if ( $shop_id )
            $query .= " and shop_id = $shop_id ";
            
        $query .= " group by crdate";
        
        $result = mysql_query ( $query, $connect );   
        
        debug( $query,1 );
        
        while ( $data = mysql_fetch_array( $result ) )
        { 
            // �̹�� data
            $cnt_notrans = $this->notrans( $from_date, $to_date, $date_type, $data[shop_id] );
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );
            
            $_datas['list'][] = array( 
                                    crdate             => $data[crdate], 
                                    shop_name          => $shop_name,
                                    shop_id            => $data[shop_id],
                                    cnt_order          => $data[cnt_order],
                                    total_shop_price   => number_format($data[total_shop_price]),
                                    pre_trans_cnt      => number_format($data[pre_trans_cnt]),
                                    supply_trans_cnt   => number_format($data[supply_trans_cnt]),
                                    post_trans_cnt     => number_format($data[post_trans_cnt]),
                                    cancel_price       => number_format($data[cancel_price]),
                                    total_org_price    => number_format($data[total_org_price]),
                                    total_supply_price => number_format($data[total_supply_price]),
                                    cnt_notrans        => $cnt_notrans,
                                ); 
        }
        
        $_datas['query']     = $query;
        $_datas['from_date'] = $from_date;
        $_datas['to_date']   = $to_date;
        $_datas['msg']       = $msg;        
        
        return $_datas;   
    }
    
    // 2008.11.6
    // �� ����
    // ���� ���� ������ ������ ���ؼ� stat_product�� join�ؾ� ��.
    function stat_list_detail( $is_download=0 )
    {
        global $connect, $from_date, $to_date, $date_type,$shop_id,$start;
        
        $start = $start ? $start : 0;
        
        //******************************************
        // ������ �� data��������
        //******************************************
        // ������ supply_price�� qty�� ���ϸ� �ȵ�
        // $query        = "select *, (a.supply_price+a.extra_supply_price) * a.qty supply_price";
        $query        = "select * ";
	$query       .= " from orders a, stat_product b ";
        $query_cnt    = "select count(*) cnt 
                           from orders a, stat_product b";

        $query_option = " where a.seq = b.seq 
                            and substring(a.order_id,1,1) <> 'C' ";
        
        $query_option .= " and $date_type >= '$from_date 00:00:00' 
                           and $date_type  <= '$to_date 23:59:59'";
            
        if ( $shop_id )
            $query_option .= " and shop_id='$shop_id'";
        
        // total count
        $result        = mysql_query ( $query_cnt . $query_option, $connect );   
        $data          = mysql_fetch_array( $result );
        $_datas['cnt'] = $data['cnt'];
        
        // total data
	if ( !$is_download )
            $query_option .= "order by b.seq desc limit $start, 20";        
       
        $result        = mysql_query ( $query . $query_option, $connect );   
        
        while ( $data = mysql_fetch_array( $result ) )
        { 
            // �̹�� data
            // $cnt_notrans = $this->notrans( $from_date, $to_date, $date_type, $data[shop_id] );
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );
            
            $qty_more = 0;
            if ( $data['packed'] )
                $qty_more = count ( split( ",", $data[pack_list]) );

	    // if ( $data[pre_paid] == "������" && $data[order_subid] == 1) // �������� ��� ���� �ݾ��� 2500 ����
            //	$data[supply_price] = $data[supply_price] - 2500; 

	    // shop_price�� ���
	    switch ( $data[shop_id]%10  )
	    {
		case 1: // auction
		   $data[shop_price] = $data[shop_price] * $data[qty]; 
		break;
	    }

	    // ���� ��ǰ�� �ٸ� ��ǰ
	    if ( $data[no] > 1 )
		$data[supply_price] = 0;
 
            $_datas['list'][] = array( 
                                    seq             => $data[seq],        
                                    trans_who       => iconv('cp949', 'utf-8', $data[trans_who]),     
                                    margin          => $data[margin],        
                                    org_price       => $data[org_price],     
                                    supply_price    => $data[supply_price],  
                                    amount          => $data[amount],        
                                    shop_price      => $data[shop_price],        
                                    qty             => $data[qty],   
                                    qty_more        => $qty_more,        
                                    options         => iconv('cp949', 'utf-8', $data[options]),
                                    product_name    => iconv('cp949', 'utf-8', $data[product_name]),  
                                    product_id      => $data[product_id],    
                                    order_id        => $data[order_id],      
                                    shop_name       => $shop_name,     
                                    trans_date_pos  => $data[trans_date_pos],
                                    collect_date    => $data[collect_date]
                                ); 
        }
        
        $_datas['query']     = $query . $query_option;
        $_datas['shop_id']   = $shop_id;
        $_datas['to_date']   = $to_date;
        $_datas['msg']       = $msg;        
        
        return $_datas;   
    }
    
    //----------------------------------
    // 2008.12.23 - jk
    // ���ں� ���� - ����ݾ�? - amount
    function product_list_chart()
    {
        global $connect;
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type;
        
        $ids = $this->get_ids();
        
        // �Ϻ� ��ǰ ���� ���� ����
        $query = "select sum((b.supply_price+b.extra_supply_price) * b.qty) total_supply_price 
		         ,b.{$date_type} crdate ";
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
         
        if ( $ids )
            $_options .= " and a.product_id in ( $ids ) ";
                        
        //--------------------------------------
        // ��¥ date_type, from_date, to_date
        $_options .= " and b.{$date_type} >= '$from_date 00:00:00' 
                       and b.{$date_type} <= '$to_date 23:59:59' ";
                    
        //--------------------------------------
        // shop_id
        if ( $shop_id )
            $_options .= " and b.shop_id = '$shop_id' ";
            
        //--------------------------------------
        // supply_id
        if ( $supply_id )
            $_options .= " and c.supply_code = '$supply_id' ";             
        
        $_options .= " group by DATE_FORMAT(b.{$date_type},'%Y-%m-%d')";
        
        debug( "[product_list] $query $_options", 1 );
        
        $result = mysql_query ( $query . $_options, $connect ); 
        
        $_datas         = array();
        $_datas['list'] = array();
        
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_datas['list'][] =  array( 
                    crdate              => $data['crdate'], 
                    total_supply_price  => $data['total_supply_price'] );
        }
        
        return $_datas;
    }
    
    //----------------------------------
    // 2008.11.6
    // ���ں� ����
    function stat_list_daily()
    {
         global $connect, $from_date, $to_date, $date_type,$shop_id;
        
        //******************************************
        // build_shop function�� �ڷḦ �����Ѵ�.
        // ������ �� data��������
        //******************************************
        $query = "select crdate, 
                         shop_id, 
                         sum(cnt_order)          cnt_order, 
                         sum(total_shop_price)   total_shop_price, 
                         sum(pre_trans_cnt)      pre_trans_cnt, 
                         sum(supply_trans_cnt)   supply_trans_cnt,
                         sum(post_trans_cnt)     post_trans_cnt,
                         sum(cancel_price)       cancel_price,
                         sum(total_supply_price) total_supply_price,                         
                         sum(total_org_price)    total_org_price
                    from stat_shop 
                   where crdate >= '$from_date' 
                     and crdate <= '$to_date'";
        
        if ( $date_type ) // collect_date / trans_date_pos
            $query .= " and date_type='$date_type'";
            
        if ( $shop_id )
            $query .= " and shop_id='$shop_id'";
            
        $query .= " group by crdate order by crdate desc";        
        $result = mysql_query ( $query, $connect );   
        
        //echo $query;
        //exit;
        
        debug( $query,1 );        
        
        while ( $data = mysql_fetch_array( $result ) )
        { 
            // �̹�� data
            $cnt_notrans = $this->notrans( $from_date, $to_date, $date_type, $data[shop_id] );
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );            
            $margin      = ( $data[total_supply_price] - $data[total_org_price] ) / $data[total_supply_price] * 100;
            
            // ��� ������ �ݾ��� return��
            $arr_cancel_info = $this->get_cancelprice( $data[shop_id], $data[crdate], $date_type );
            
            $_datas['list'][] = array( 
                                    crdate             => $data[crdate], 
                                    shop_name          => $shop_name,
                                    shop_id            => $data[shop_id],
                                    cnt_order          => $data[cnt_order],
                                    tot_shop_price     => number_format($data[total_shop_price]),
                                    pre_trans_cnt      => number_format($data[pre_trans_cnt]),
                                    supply_trans_cnt   => number_format($data[supply_trans_cnt]),
                                    post_trans_cnt     => number_format($data[post_trans_cnt]),
                                    cancel_price       => number_format($arr_cancel_info[tot_supply_price]),
                                    tot_org_price      => number_format($data[total_org_price]),
                                    margin             => number_format($margin),
                                    tot_supply_price   => number_format($data[total_supply_price]),
                                    cnt_notrans        => $cnt_notrans,
                                ); 
        }
        
        $_datas['query']     = $query;
        $_datas['from_date'] = $from_date;
        $_datas['to_date']   = $to_date;
        $_datas['msg']       = $msg;        
        
        return $_datas;   
    }
    
    // ��� ����.
    function get_cancelprice( $shop_id, $crdate, $date_type )
    {
        global $connect;
        
        // ��ü ���
        $query = "select sum(qty) cnt, sum(supply_price+extra_supply_price) tot_supply_price 
                    from orders
                   where $date_type >= '$crdate 00:00:00'
                     and $date_type <= '$crdate 23:59:59'
                     and order_cs in (1,2,3,4,12)
                     and shop_id    = $shop_id";
        
        $result = mysql_query($query , $connect );
        $data = mysql_fetch_array( $result );

        // �κ� ���
        
        return $data;
    }
    
    //****************************************************
    // �ֹ� �� ���� ��������
    // stat_products�� ���� �ؾ� ��.
    function order_list()
    {
        global $shop_id,    $supply_id, $date_type, $from_date,  $to_date;
        global $query_type, $disp_type, $query,     $product_id, $connect, $start, $limit;    
        
        $start = $start ? $start : 0;
        
        //******************************************
        // ��ȸ�ؾ��� ��ǰ ��ȣ�� ���� ��� ������ *
        $ids = $this->get_ids();
        
        // seq, product_id, order_cs, qty�� ������ �� �� ����
        $query = "select a.shop_id, a.seq, a.pack, a.order_id,a.recv_name, b.product_id, b.name
			 ,b.options, a.qty,a.amount, a.extra_shop_price, a.extra_supply_price
                         ,a.packed, a.collect_date, a.trans_date, a.trans_date_pos, a.status, a.order_cs, a.trans_who
			 ,a.product_name real_product_name, a.options real_options
			 ,a.pre_paid
                         ,((a.supply_price+a.extra_supply_price)*a.qty) tot_supply_price, a.shop_price, b.org_price*a.qty as org_price ";
        
        $option = " from orders a, products b, stat_product c
                   where a.seq        = c.seq
                     and c.product_id = b.product_id                     
                     and a.{$date_type} >= '$from_date' 
                     and a.{$date_type} <= '$to_date'
		     and substring(a.order_id,1,1) <> 'C' ";
        
        if ( $ids )
            $option .= " and c.product_id in ( $ids )";
        
        if ( $shop_id )
            $option .= " and a.shop_id = '$shop_id' ";
            
        if ( $supply_id )
            $option .= " and a.supply_id = '$supply_id' ";    
        
        // ����
        $query_cnt  = "select count(*) cnt ";
        
        $result        = mysql_query( $query_cnt . $option , $connect );
        $data          = mysql_fetch_array( $result );
        
        $_datas = array();
        $_datas['cnt'] = $data['cnt'];
        
        // ����Ʈ
        // $limit�� ������ download
        if ( $limit )
            $query .= $option . " limit $start, $limit";
        else
            $query .= $option;
        
        debug ( "[order_list] $query", 1 );
        
        $result = mysql_query( $query, $connect );
        
        
        $_datas['list'] = array();
        
        while ( $data = mysql_fetch_array( $result ) )
        {
	    // �������� ��� �ǸŰ��� ���� �������� 2500�� ���Ѵ�
	    $trans_price = 0;
	    if ( $data[pre_paid] == "������" ) $trans_price = 2500;

            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );            
            $_datas['list'][] = array( 
                shop_id        => $data['shop_id'], 
                shop_name      => $shop_name,
                seq            => $data['seq'],
                pack           => $data['pack'],
                order_id       => $data['order_id'],
                recv_name      => iconv('cp949', 'utf-8', $data['recv_name']),
                product_id     => $data['product_id'],
                product_name   => iconv('cp949', 'utf-8', $data['name']),
                options        => iconv('cp949', 'utf-8', $data['options']),
                real_product_name  => iconv('cp949', 'utf-8', $data['real_product_name']),
                real_options       => iconv('cp949', 'utf-8', $data['real_options']),
                qty            => $data['qty'],
                packed         => $data['packed'],
                collect_date   => $data['collect_date'],
                trans_date     => $data['trans_date'],
                trans_date_pos => $data['trans_date_pos'],
                status         => $data['status'],
                order_cs       => $data['order_cs'],
                shop_price     => $data['shop_price']+$data[extra_shop_price] + $trans_price,   // �ǸŰ�
                amount         => $data['amount']+$data[extra_shop_price]*$data[qty] + $trans_price,   // �ǸŰ�
                supply_price   => $data['tot_supply_price'] + $trans_price, // ���갡
                org_price      => $data['org_price'],    // ����
                trans_who      => iconv('cp949', 'utf-8', $data['trans_who'] ),
                pre_paid       => iconv('cp949', 'utf-8', $data['pre_paid'] )
                
            );
        }
        // $_datas['list'][] = array( shop_id => 2, shop_name=>'hehe');
        
        
        return $_datas;
    }
    
    //*********************************
    // ���� �ؾ��� id�� ����
    function get_ids()
    {
        global $connect, $from_date, $to_date, $date_type,$disp_type,$query,$query_type,$shop_id,$supply_id;          
        $_query = $query;  // �Է� ��

        
        if ( $_query )
        {
            // id �� ���ؾ� ��
            if ( $disp_type == "product") // ��ǰ�� ���
            {
                // org_id ����� ���Ѵ�.
                if ( $query_type == "product_id")                
                {
                    $query = "select product_id, org_id from products where (product_id='$_query' or org_id='$_query')";
                }
                else
                {
                    $_query = iconv('utf-8', 'cp949', $_query );
                    $query  = "select product_id, org_id from products where name like '%" . $_query . "%'";
                }
               
                $result = mysql_query( $query, $connect );
                while ( $data = mysql_fetch_array( $result ) )
                {
                    $org_ids .= "'" . $data[org_id] . "',";
                    $ids     .= "'" . $data[product_id] . "',";
                }
                
                // org_id�� product_id group�� ���� ��
                $org_ids = substr( $org_ids, 0, strlen( $org_ids ) -1 ); 
                
                if ( $org_ids )
                {
                    $ids    = '';  // ids �ʱ�ȭ
                    $query  = "select product_id from products where org_id in ( $org_ids )";
                    $result = mysql_query( $query, $connect );
                    while ( $data = mysql_fetch_array( $result ) )
                    {
                        $ids .= "'" . $data[product_id] . "',";
                    }
                }                
            }
            else
            {                           // option�� ���
                if ( $query_type != "product_id")
                {
                    $query  = "select product_id from products where name like '%" . $_query . "%'";
                    $result = mysql_query( $query, $connect );
                    while ( $data = mysql_fetch_array( $result ) )
                    {
                        $ids .= "'" . $data[product_id] . "',";
                    }
                }
                else
                    $ids = "'$_query',";
            }
            
            $ids = substr( $ids, 0, strlen( $ids ) -1 ); 
        }
        
        return $ids;
    }
    
    //******************************************
    // ���� ��� ��������
    // date: 2008.10.28 - jk
    // date_type: collect_date, trans_date_pos
    function shop_list()
    {
        global $connect, $from_date, $to_date, $date_type,$disp_type,$query,$query_type,$shop_id,$supply_id;                  
        // debug("shop_list/ $_query/$disp_type/$query_type",1);
        
        //******************************************
        // ��ȸ�ؾ��� ��ǰ ��ȣ�� ���� ��� ������ *
        $ids = $this->get_ids();
        
        $_query = $query;
        $query = "";
        
        // orders���� �ڷ� ���� ��.
        // step 1. shop�� ����
        // step 2. shop�� ���� ���� �ݾ�
        // step 3. order_cs �� ���� ���� �ݾ�
        
        // step 1
        $query = "select a.shop_id, sum(b.org_price * c.qty) tot_org_price, a.order_cs ";
        
        $_option = " from orders a, products b, stat_product c
                   where a.seq        = c.seq
                     and c.product_id = b.product_id
		     and substring(a.order_id,1,1) <> 'C'
                     and a.$date_type >= '$from_date 00:00:00' 
                     and a.$date_type <= '$to_date 23:59:59'";
        
        if ( $shop_id )
            $_option .= " and a.shop_id = $shop_id ";
            
        if ( $supply_id )
            $_option .= " and b.supply_code = $supply_id ";
        
        if ( $ids )
            $_option .= " and c.product_id in ( $ids )";
        
        $query .= $_option . " group by a.shop_id, a.order_cs ";
        
        // debug( "[shop_list] $query", 1 );
        
        //echo $query;
        //exit;
        
        $val = array();            
        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $val[ $data[shop_id] ]['tot_org_price'] = $val[ $data[shop_id] ]['tot_org_price'] + $data['tot_org_price'];
        
        }
        
        // step 2. shop�� ���� ���� �ݾ�
	$_option = "";
        $query = "select shop_id, sum((supply_price+extra_supply_price)*qty) tot_supply_price
			,sum(amount) tot_shop_price, order_cs ";
	
        $_option .= " from orders
		   where $date_type >= '$from_date 00:00:00' 
                     and $date_type <= '$to_date 23:59:59'
		     and substring(order_id,1,1) <> 'C' ";
         
        if ( $shop_id )
            $_option .= " and shop_id = $shop_id ";
            
        if ( $supply_id )
            $_option .= " and supply_code = $supply_id ";
        
        if ( $ids )
            $_option .= " and product_id in ( $ids )";

        $query .= $_option . " group by seq, order_cs";
        
        
        $result = mysql_query ( $query, $connect );
         debug( "shop_list: [���꿹��] $query", 1 );
        // exit;
        
        $_datas = array();
        $_datas['list'] = array();
        
        while ( $data = mysql_fetch_array( $result ) )
        {   
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );

            $_datas['list'][ $data[shop_id] ]['shop_id']                             = $data['shop_id'];
            $_datas['list'][ $data[shop_id] ]['shop_name']                           = $shop_name;
            $_datas['list'][ $data[shop_id] ]['tot_supply_price'][ $data[order_cs] ] = $_datas['list'][ $data[shop_id] ]['tot_supply_price'][ $data[order_cs] ] + $data['tot_supply_price'];
            $_datas['list'][ $data[shop_id] ]['tot_shop_price'][ $data[order_cs] ]   = $_datas['list'][ $data[shop_id] ]['tot_shop_price'][ $data[order_cs] ] + $data['tot_shop_price'];
            $_datas['list'][ $data[shop_id] ]['tot_org_price']                       = $val[ $data[shop_id] ]['tot_org_price'];

	    // debug ( "shop_price x: $data[tot_shop_price]");
        }
        
        // step 3. ��� ������ ��ȸ
        // ��ȯ ����� �ù�� ���� ��
        $query = "select shop_id, count(*) cnt, trans_who, pre_paid, sum(qty) qty ";
        # $query .= $_option . " group by shop_id, trans_pack, pre_paid";
        $query .= $_option . "  and (pack='' or pack is null or seq=pack) group by shop_id, trans_who, pre_paid";

        $result = mysql_query ( $query, $connect );
        
        while ( $data = mysql_fetch_array( $result ) )
        {   
            if ( $data['trans_who'] == "����" )
            {
                if ( $data['pre_paid'] == '������' )                    
                    $_datas['list'][ $data[shop_id] ]['supply_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['supply_trans_cnt'] + $data['cnt'];
                
                $_datas['list'][ $data[shop_id] ]['pre_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['pre_trans_cnt'] + $data['cnt'];        
            }else{
                $_datas['list'][ $data[shop_id] ]['post_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['post_trans_cnt'] + $data['cnt'];    
            }
            
            $_datas['list'][ $data[shop_id] ]['cnt_order'] = $_datas['list'][ $data[shop_id] ]['cnt_order'] + $data['qty'];    
        }            
        
        // ��� �ݾ� ��ȸ
        // date: 2009.1.19 - jk
        $arr_cancel_info = $this->get_cancel_price( $ids );
        
        foreach ( $arr_cancel_info as $_cancel_info )
        {
            $_datas['list'][ $_cancel_info[shop_id] ]['cancel_price'] = $_cancel_info['tot_cancel_price']; 
        }
        
        return $_datas;   
    }
    
    // cancel price
    // shop_list������ �θ���.
    function get_cancel_price( $product_ids = '' )
    {
        global $connect, $from_date, $to_date, $date_type,$disp_type,$query,$query_type,$shop_id,$supply_id,$cancel_option;
        $arr_cancel_info = array();
          
        $query = "select sum((supply_price+extra_supply_price)*qty) tot_cancel_price, shop_id
                    from orders 
                   where order_cs in (1,2,3,4,12)";
        
        if ( $cancel_option == "refund_date")
        {
            $query .= " and refund_date >= '$from_date 00:00:00'
                        and refund_date <= '$to_date 23:59:59'";
        }
        else
        {
            $query .= " and collect_date >= '$from_date 00:00:00'
                        and collect_date <= '$to_date 23:59:59'";
        }
        
        if ( $shop_id )
            $query .= " and shop_id = '$shop_id'";
            
        if ( $supply_id )
            $query .= " and supply_id = '$supply_id'";
        
        if ( $product_ids )
            $query .= " and product_id in ( $product_ids )";

        $query .= " group by shop_id";

        //echo "//---//\n";
        //echo ( "[get_cancel_price] $query" );
        //echo "//---//\n";
        
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_array( $result ) )
        {
            // echo "xxxxx in class_newsstat";
            $arr_cancel_info[] = array( shop_id => $data[shop_id], tot_cancel_price => $data[tot_cancel_price] );
        }
        
        // �κ� ��� �ݿ�
        // �ϴ� ����..
        //print_r ( $arr_cancel_info );
        //echo "//---//\n";
        return $arr_cancel_info;
    }
    
    // �̹��
    function notrans( $from_date, $to_date, $date_type, $shop_id )
    {
        global $connect;   
        $query = "select count(*) cnt from orders 
                   where $date_type >= '$from_date 00:00:00'
                     and $date_type <= '$to_date 23:59:59'
                     and status=7
                     and order_cs not in (1,2,3,4,12)
                     and shop_id=$shop_id";
                     
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        return $data[cnt];
    }
    
    // ���� �� ����
    // amount�� supply_price�� �����Ѵ�.
    // ����: 2008.11.4
    function apply_auction_rule( $_crdate, $date_type  )
    {
        global $connect;        
        
        //
        // amount�� ���� �����ؾ� ��.
        // 2008.10.31
        $query = "select seq, options,shop_price, qty,supply_price,o_supply_price
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59'
                       and substring(shop_id,5,1) = 1";
                       
        $result = mysql_query ( $query, $connect );
        
        // ������ ��� 
        // ����  : ������ ���ؾ� ��
        // ������: ���� �����ݾ׿� �߰�
        // ����  : �Ǽ��� ����
        while ( $data = mysql_fetch_array($result))
        {     
	    //****************************
	    // step1 
	    // auction �� �������� �ֹ��� ��� ���� ���� �ݾ��� ����..�����ؾ� �� 
            // 2009.3.12
            // ������       
            if ( $data['pre_paid'] == "������" )
            {
                $amount         = $data[amount] - 2500; 
                $supply_price   = $data[supply_price] - 2500;
                $this->move_supply_price( $data[seq] );                
                $this->update_data ( $amount, $supply_price,  $data[seq] );
            }
            $_extra = 0;
       } // end of while    
    }
    
    // ������ �� ����
    // amount�� supply_price�� �����Ѵ�.
    // ����: 2008.11.4
    function apply_gmarket_rule( $_crdate, $date_type  )
    {
        global $connect;        
         
        //
        // �߰� ���� , ���纻 ���� ���.
        // 2008.10.31
        $query = "select order_id 
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59'
                       and shop_id%100 = 2
                       and order_subid = 1";

	$result = mysql_query( $query, $connect );
	$str = '';
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $str .= "'".$data[order_id]."',";
	}
	$str = substr( $str , 0, strlen( $str ) -1 );

	// ���纻�� ���� �ֹ��� �����´�
        $query = "select order_id 
                    from orders
		     where order_id in ( $str )
                       and order_subid <> 2";

	$result = mysql_query( $query, $connect );
	$str = '';
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $str .= "'".$data[order_id]."',";
	}
	$str = substr( $str , 0, strlen( $str ) -1 );

	// �� ��ǰ�� extra_price�����ؾ� ��
        $query = "select seq, options,shop_price, qty,supply_price,amount,o_supply_price, extra_supply_price
                         ,pre_paid
                    from orders
		     where order_id in ( $str )";

        $result = mysql_query ( $query, $connect );
        
        // gmarket�� ��� 
        while ( $data = mysql_fetch_array($result))
        {
	    //****************************
	    // step 1
            // �߰����� �ݾ� Ȯ��
            $_extra = 0;
            preg_match_all("|[(](.*)[��)]|U", $data[options], $matches);
            
            for( $i=0; $i < count($matches[1]); $i++ )
            {
                $_extra = $_extra + $matches[1][$i];
            }

	    // �߰� �ݾ��� 0 �̻��� ���
	    // ���°ž�!!!! 2009.3.17
            // ������ �ǵ帮�� �ʰ� extra_supply_price, extra_shop_price�� �߰� ��
	    if ( $_extra > 0 and $data[extra_supply_price] == 0)
	    {
		$extra_shop_price   = $_extra;
		$extra_supply_price = (($_extra+$data[supply_price]) * 0.94) - $data[supply_price]; // 6% ����

		// ������ �ǵ帮�� ����..ok
                // $this->move_supply_price( $data[seq] );

		$this->update_extra_price( $extra_shop_price, $extra_supply_price, $data[seq] );
      	    }

	    //=====================================
	    // ������ ó��
	    // ����.. 2009.3.18 - jk
	    /*
	    if ( $data['pre_paid'] == "������" and $data[o_supply_price] == 0)
	    {
                $supply_price = $data[supply_price] - 2500;
                $amount       = $data[amount] - 2500; 
                $this->move_supply_price( $data[seq] );
                $this->update_data ( $amount, $supply_price, $data[seq] );
	    }
	    */
	}

        // ���纻�� ���
        // amount�� ���� �����ؾ� ��.
        // 2008.10.31
        $query = "select seq, options,shop_price, qty,supply_price,o_supply_price
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59'
                       and shop_id%100 = 2
                       and order_subid > 1";

        $result = mysql_query ( $query, $connect );
        
        // gmarket�� ��� 
        while ( $data = mysql_fetch_array($result))
        {
	    //****************************
	    // step 1
            // �߰����� �ݾ� Ȯ��
            $_extra = 0;
            preg_match_all("|[(](.*)[��)]|U", $data[options], $matches);
            
            for( $i=0; $i < count($matches[1]); $i++ )
            {
                $_extra = $_extra + $matches[1][$i];
            }

	    // �߰� �ݾ��� 0 �̻��� ���
	    // ���°ž�!!!! 2009.3.17
	    if ( $_extra > 0 )
	    {
		$amount       = $_extra;
		$supply_price = $_extra * 0.94; // 6% ����

                // $this->move_supply_price( $data[seq] );
                $this->update_data ( $amount, $supply_price, $data[seq] );
	    }

	    /*
	    //****************************
	    // step1 
	    // gmarket�� �������� �ֹ��� ��� ���� ���� �ݾ��� ����..�����ؾ� �� 
            // 2009.3.12

	    // supply_priceȮ��
            if ( !$data[o_supply_price] )
            {
                $supply_price = ($data[supply_price] * $data[qty]) + ($_extra*0.94);
                $amount       = ($data[shop_price] * $data[qty]) + $_extra; 

	        if ( $data['pre_paid'] == "������" )
		{
                    $supply_price = $supply_price - 2500;
                    $amount       = $amount - 2500; 
		}
                $this->move_supply_price( $data[seq] );
                $this->update_data ( $amount, $supply_price, $data[seq] );
            }
	    */
	}
    }

    //*************
    // �߰� �ݾ�    
    function update_extra_price( $extra_shop_price, $extra_supply_price, $seq )
    {
        global $connect;
        
        $query = "update orders 
		     set extra_supply_price = $extra_supply_price 
                        ,extra_shop_price   = $extra_shop_price
                   where seq=$seq";
        mysql_query ( $query, $connect );           
    }

    // supply_price�� �����ؾ� �ϴ°�� o_supply_price�� �� ���꿹���ݾ��� ���� �� supply_price�� update�Ѵ�
    // supply_price 
    function move_supply_price( $seq )
    {
        global $connect;
        
        $query = "update orders set o_supply_price = supply_price,o_shop_price = amount 
                   where seq=$seq";
        mysql_query ( $query, $connect );           
    }
    
    // data update
    // o_supply_price�� �� ���� �������� ���� �ϴµ� 0�� �������
    // 2008.11.3 - jk
    function update_data( $amount, $supply_price, $seq )
    {
        global $connect;
        
        // order_subid�� 1�� �ֹ��� ���� ��
        // �������� ��ǰ�� ������ ��� ������ �۾��� �� ����
	// -> ���� 2009.3.17 -> ���� - ���縦 ����� �ֹ��� �������� �и���.
        $query = "update orders set amount         = $amount, 
                                    supply_price   = $supply_price
                              where seq            = $seq 
                                ";
        debug ( $query );
        mysql_query( $query, $connect );
    }
    
    //************************************************************************
    // precondition: stat_product�� �����Ͱ� �̹� �����Ǿ� �� ����
    // date_type: collect_date, trans_date_pos
    // date: 2008.10.30 - jk
    // * build_shop_data�� stat_shop���̺� ���� ���� �ڷḸ �Է� ��
    // 
    function build_shop_data( $_crdate, $date_type, &$msg )
    {
        global $connect;
        
        // gmarket rule ����
	// ��а� ���� ������� �ʴ´� - jk 2009.3.13
        // $this->apply_auction_rule( $_crdate, $date_type );

	// 2009.3.16 - jk
        // extra_supply_price�߰�
        $this->apply_gmarket_rule( $_crdate, $date_type );

	// �ٸ� ����Ʈ�� ���� �־�� ��
	// $this->apply_rule($_crdate, $date_type );

        //            
        // crdate�� �����̸� �ڷ�� �ϴ� ���� �� �Է�
        if ( $_crdate == date('Y-m-d') )
        {
            // debug( "������ �����̴�" );            
            $query = "delete from stat_shop where crdate='$_crdate' and shop_id='$shop_id'";
            // debug( $query );            
            mysql_query ( $query, $connect );
        }
        
        // orders���� ó�� ������ �κ�
        // step 1. cnt_order, total_shop_price, total_supply_price, pre_trans_cnt, post_trans_cnt
        // total_supply_price�� ��� �о�;� �ϳ�?
        $query = "select count(*)          cnt_order, 
                         sum(amount)       total_shop_price,
                                           shop_id,
                         sum((supply_price+extra_supply_price)*a.qty ) total_supply_price                         
                    from orders a, stat_product b
                     where a.seq = b.seq 
                       and $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59' 
                  group by shop_id";    
        
        $result = mysql_query( $query, $connect );
        
        while ( $data   = mysql_fetch_array( $result ) )
        {
            $cnt_order          = $data[cnt_order];
            $total_shop_price   = $data[total_shop_price];
            $shop_id            = $data[shop_id];
            $total_supply_price = $data[total_supply_price];
            
            // ��ü ����
            // stat_product�� ��ǰ ���� �̿���
            $arr_info           = $this->get_org_price( $date_type, $_crdate, $shop_id );
            $total_org_price    = $arr_info['total_org_price'];
            $cnt_product        = $arr_info['cnt_product'];
            
            $query = "insert stat_shop 
                             set cnt_order          = $cnt_order,
                                 total_shop_price   = $total_shop_price,
                                 total_org_price    = $total_org_price,
                                 total_supply_price = $total_supply_price,                                 
                                 cnt_product        = $cnt_product,  
                                 shop_id            = $shop_id,
                                 date_type          = '$date_type',                                 
                                 crdate             ='$_crdate'";
            
            mysql_query( $query, $connect );
        }
        
        //***********************************************************
        // ������ ����
        // orders���� �о�;� ��.
        // 2008.10.30 ��ۺ� ���� �ݾ��� �о�;� ��. ����
        // 
        // date_type�� trans_date_pos�� ��쿡�� ���� ��
        if ( $date_type == "trans_date_pos" )
        {
            
            $query = "select count(*) cnt_transwho, trans_who, shop_id,pre_paid,seq,pack
                        from orders
                         where $date_type >= '$_crdate 00:00:00' 
                           and $date_type <= '$_crdate 23:59:59' 
                           and status = 8
                      group by shop_id, trans_no, trans_who, pre_paid";    
            
            // debug( $query , 1 );                     
            
            $result = mysql_query( $query, $connect );
            
            $_arr_info = array();
            while ( $data   = mysql_fetch_array( $result ) )
            {
                // debug( " $data[trans_who] / $data[cnt_transwho] ");
                
                if ( $data[trans_who] == "����" )                            
                {
                    if ( $data[pre_paid] == "������" )
                    {
                        $_arr_info[ $data[shop_id] ][supply]++;
                        
                        //
                        // ������ ��� amount���� 2500�� ����� �Ѵ�. 2008-11-10 jk
			// �� �� ���꿡�� �ݿ� ����
                        if ( $data[shop_id]%100 == 1 )
                        {
                            $arr_info = array( val=> -2500, seq=> $data[seq], pack=> $data[pack] );
                            $this->set_amount( $arr_info );   
                        }
                    }
                    else
                        $_arr_info[ $data[shop_id] ][pre]++;
                }
                else
                    $_arr_info[ $data[shop_id] ][post]++;
            }
            
            foreach( $_arr_info as $key => $value)
            {
                // ��ۺ� ���� ��� ���� ��� ����        
                // pre_transprice : ��ۺ� ����
                // post_transprice: ��ۺ� ����  .
                $pre    = $value[pre]  ? $value[pre] : 0 ;          
                $post   = $value[post] ? $value[post]: 0 ;         
                $supply = $value[supply] ? $value[supply]: 0 ;  
                 
                $query = "update stat_shop 
                                 set supply_trans_cnt  = $supply,
                                     pre_trans_cnt     = $pre,
                                     post_trans_cnt    = $post
                               where crdate            ='$_crdate'
                                 and shop_id           = $key
                                 and date_type         = '$date_type'";
                
                // debug( "$query" , 1);                     
                mysql_query( $query, $connect );
            }
        } // end if
        
        /**
        *
        *@date : 2009.1.19 - jk
        *@brief: ��Ҵ� �ǽð� �ݿ� �� 
        *
        **/
        //***********************************************************
        // cancel_price
        // 2008.10.30 - jk
        /*
        $query = "select count(*) cnt_cancel_order, sum(shop_price) cancel_price
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59' 
                       and order_cs in (1,2,3,4,12)
                  group by shop_id";    
        
        $result = mysql_query( $query, $connect );
        
        $_arr_info = array();
        while ( $data   = mysql_fetch_array( $result ) )
        {
            $query = "update stat_shop 
                             set cnt_cancel_order = $data[cnt_cancel_order],
                                 cancel_price     = $data[cancel_price]
                           where crdate           ='$_crdate'
                             and shop_id          = $key
                             and date_type        = '$date_type'";
             
            debug( $query );                     
            mysql_query( $query, $connect );
        }
        */
    }
    
    //array( val=> -2500, seq=> $data[seq], pack=> $data[pack] )
    function set_amount( $arr_info )
    {
        global $connect;
        
        $query = "update orders set amount=amount " . $arr_info['val']. " where pre_paid='������' and ";
        
        if ( $arr_info['pack'] )
            $query .= " pack=$arr_info[pack]";
        else
            $query .= " seq=$arr_info[seq]";
            
        //debug( $query, 1 );
    }
    
    
    // ��ǰ�� ���� ��������
    // 2008.11.3 - jk
    function get_org_price( $date_type, $_crdate, $shop_id )
    {
        global $connect;
        // stat_product���� ó�� ������ �κ�
        // step 2. cnt_product, total_org_price
        $query = "select sum(b.qty)       cnt_product,
                         sum(a.org_price) total_org_price
                    from products a, stat_product b, orders c
                   where a.product_id = b.product_id
                     and b.seq = c.seq
                     and c.$date_type >= '$_crdate 00:00:00' 
                     and c.$date_type <= '$_crdate 23:59:59'
                     and c.shop_id    = $shop_id";
        
        $result   = mysql_query ( $query, $connect );
        $data     = mysql_fetch_array( $result );
        
        return $data;
    }
    
    // ���� ��ǰ�� ó�� �� ���� ��ǰ�� ���� - �ϳ��� �ֹ��� ��� ��ǰ�� �ǸŵǾ����� check
    // date: 2008.10.29
    function build_product_data( $_crdate, $date_type, &$msg )
    {        
        global $connect;
        
        // 2008.10.29
        // ��ǰ�� ���� ������ stat_product�� �Է��Ѵ�. date_type�� ������ �ʿ�� ����
        $query = "select seq,product_id, packed, pack_list, qty,order_cs
                    from orders
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'";
                              
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $this->insert_product( $data );   
        }
        
        //====================================================
        // trans_pack �۾� ��� ��ǰ�� ��۵Ǿ����� check
        // ����, ���Ҹ� ó�� - jk
        // step 1. �۾��� �ϴ� �ֹ��� trans_pack�� qty�� �����ϰ� ����
        $query = "update orders set trans_pack = qty 
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'";
        $result = mysql_query( $query, $connect );             
        
        // step 2. �����ֹ��� �� �۾�
        $query = "select pack, sum(qty) qty
                    from orders
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'
                     and trans_who = '����'
                     and (pack is not null or pack <> '')
                   group by pack";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $this->update_trans_pack( $data[pack], $data[qty] );
        }
        
        // step 3. ������ �ֹ��� trans_pack ������ 0���� ������
        // ������ �ֹ��� ���ҷ� ������� �ϴ� ���̱� ������ ���߿� �������� ���� ��� �ϱ�� ��..
        /*
        $query = "select pack
                    from orders
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'
                     and pre_paid = '������'";
                     
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $this->update_trans_pack_zero( $data[pack] );
        }   
        */               
    }
    
    
    // pack, qty 2008-12-2
    function update_trans_pack_zero( $pack )
    {
        global $connect;
        $query = "update orders set trans_pack=0 where pack=$pack";
        mysql_query( $query, $connect );
    }
    
    // pack, qty 2008-12-2
    function update_trans_pack( $pack, $qty )
    {
        global $connect;
        $query = "update orders set trans_pack=$qty where pack=$pack";
        mysql_query( $query, $connect );
    }
    
    //
    // stat_product�� data�Է�
    function insert_product( $data )
    {
        global $connect;
        
        // stat_product�� ����� ������ ���°�� �Է� ��
        if ( !$this->check_stat_product($data[seq]) )
        {
            // ������ǰ���� ���� check
            if ( $data[packed] )
            {
                $arr_products = split(',', $data[pack_list] );   
                
                //for ( $i=0; $i <= count($arr_produts); $i++ )
                foreach ( $arr_products as $product_id )
                {
                    // 
                    // $query = " ���� ��ǰ insert $data[seq] / $product_id / $data[qty] ";
                    // $product_id = $arr_products[$i];
                    $query      = "insert into stat_product 
				      set seq        = '$data[seq]', 
	   				  product_id = '$product_id', 
					  qty        = $data[qty], 
			  		  order_cs   = $data[order_cs]";
                    mysql_query( $query, $connect );
                }
            }
            else
            {
                $query = "insert into stat_product 
			     set seq        = $data[seq], 
			         product_id = '$data[product_id]', 
				 qty        = $data[qty], 
				 order_cs   = $data[order_cs]";
                mysql_query( $query, $connect );
            }
        }          
    }
    
    function check_stat_product( $seq )
    {
        global $connect;
        
        $query = "select seq 
                    from stat_product
                   where seq='$seq'";
                       
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        return $data[seq];
    }
    
    
    
    
}
?>
