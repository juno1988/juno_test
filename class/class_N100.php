<?
include_once "class_top.php";
include_once "class_item.php";
include_once "class_newstat.php";
include_once "class_file.php";

class class_N100 extends class_top
{
    //=============================================
    // �ֹ� ����
    function order_list()
    {
        global $connect;
        $val         = array();
        $val['list'] = array();
        $obj         = new class_newstat();
        $arr_result  = $obj->order_list();
        
        // cnt�� �־�� ��
        $val['cnt']    = $arr_result['cnt'];        
     
        $val['list']   = $arr_result['list'];        
        echo json_encode( $val );
    }
    
    // �ֹ� ���� download
    // save_file function name�� reserved function            
    function save_file()
    {   
        global $connect, $user_id;
       
echo "save file start\n $user_id / $connect ";
 
        $obj         = new class_newstat();
        $arr_result  = $obj->order_list();
        
        $_datas      = array();
        $_datas[] = array( 
                            "�Ǹ�ó�ڵ�"
                            ,"�Ǹ�ó"
                            ,"������ȣ"
                            ,"�ֹ���ȣ"
                            ,"������"
                            ,"������"
                            ,"�����"
                            ,"��ǰ�ڵ�"
                            ,"��ǰ��"
                            ,"�ɼ�"
                            ,"������ǰ��"
                            ,"�����ɼ�"
                            ,"��ۻ���"
                            ,"cs����"
                            ,"����"
                            ,"�����ݾ�"
                            ,"���갡(�������ݾ� ������)"
                            ,"����"
                            ,"������"
                            ,"������"
                        );
        foreach ( $arr_result['list'] as $_result )
        {
        //    debug ( "shop_name: " . $_result['shop_name'] );
            $_datas[] = array( 
                            $_result['shop_id'], 
                            iconv('utf-8','cp949', $_result['shop_name']),
                            $_result['seq'], 
                            $_result['order_id'], 
                            iconv('utf-8','cp949',$_result['recv_name']),
                            $_result['collect_date'],
                            $_result['trans_date_pos'],
                            $_result['product_id'],
                            iconv('utf-8','cp949',$_result['product_name']),
                            iconv('utf-8','cp949',$_result['options']),
                            iconv('utf-8','cp949',$_result['real_product_name']),
                            iconv('utf-8','cp949',$_result['real_options']),
                            $_result['status'],
                            $_result['order_cs'],
                            $_result['qty'],
                            $_result['amount'],
                            $_result['supply_price'],
                            $_result['org_price'],
                            iconv('utf-8','cp949',$_result['trans_who']),
                            iconv('utf-8','cp949',$_result['pre_paid'])
                        );
        }
        
        $obj = new class_file();        
        return $obj->save_file( $_datas, "$user_id/order_detail.xls" );
    }

    function shop_list()
    {
        global $shop_id, $supply_id, $date_type, $from_date, $to_date;
        global $query_type, $disp_type, $query, $product_id, $connect;
        
        $val         = array();
        $val['list'] = array();        
       
        $obj         = new class_newstat();
        $result      = $obj->shop_list($shop_id, $supply_id,$date_type, $from_date, $to_date, $query,$query_type,$disp_type);        
       
        $val['cnt']  = 40;
        
        //
        // disp_type: shop �Ǹ�ó�� ����
        //     
        $tot_shop_price   = 0;
        $tot_supply_price = 0;
        foreach ( $result['list'] as $value )
        {
            $qty = $qty ? $qty : 0;
            
            $tot_supply_price = 0;
            $tot_shop_price   = 0;
            
            foreach ( $value['tot_supply_price'] as $v )
                $tot_supply_price = $tot_supply_price + $v;   
            
            foreach ( $value['tot_shop_price'] as $v )
                $tot_shop_price = $tot_shop_price + $v;   
            
            $margin      = ( $tot_supply_price - $value[tot_org_price] ) / $tot_supply_price * 100;
            
            /*
            $val['list'][] = array( 
                shop_name         => $value['shop_name']
               ,cancel_price      => $value['tot_supply_price'][1] + $value['tot_supply_price'][2] + $value['tot_supply_price'][3] + $value['tot_supply_price'][4] + $value['tot_supply_price'][12]
               ,tot_org_price     => $value['tot_org_price']
               ,tot_supply_price  => $tot_supply_price
               ,tot_shop_price    => $tot_shop_price
               ,margin            => ceil($margin)
               ,pre_trans_cnt     => $value['pre_trans_cnt']    ? $value['pre_trans_cnt']    : 0
               ,post_trans_cnt    => $value['post_trans_cnt']   ? $value['post_trans_cnt']   : 0
               ,supply_trans_cnt  => $value['supply_trans_cnt'] ? $value['supply_trans_cnt'] : 0
               ,cnt_order         => $value['cnt_order']
            );
            */
            $val['list'][] = array( 
                shop_name         => $value['shop_name']
               ,shop_id           => $value['shop_id']
               ,cancel_price      => $value['cancel_price']
               ,tot_org_price     => $value['tot_org_price']
               ,tot_supply_price  => $tot_supply_price
               ,tot_shop_price    => $tot_shop_price
               ,margin            => ceil($margin)
               ,pre_trans_cnt     => $value['pre_trans_cnt']    ? $value['pre_trans_cnt']    : 0
               ,post_trans_cnt    => $value['post_trans_cnt']   ? $value['post_trans_cnt']   : 0
               ,supply_trans_cnt  => $value['supply_trans_cnt'] ? $value['supply_trans_cnt'] : 0
               ,cnt_order         => $value['cnt_order']
            );           
            $qty = 0;
        }
  
        $_ret = $obj->get_trans_info();        
        $val['trans']['pre']       = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans']['post']      = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans_pack']['pre']  = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['trans_pack']['post'] = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        // �� �ǸŰ� ����..
        //$result = $obj->get_product_price();
        //$val['tot_supply_price'] = $result['tot_supply_price'];
        //$val['tot_org_price']    = $result['tot_org_price'];
        
        echo json_encode( $val ); 
    }
    
    function item_list()
    {
        $obj    = new class_item();
        $_datas = array();
        // �ڷ� ����
        
        
        // �ڷ� ��������
        $_datas = $obj->item_list();
        
        echo json_encode( $_datas );     
    }
    
    
    function period_list()
    {
        $obj    = new class_period();
        $_datas = array();
        // �ڷ� ����
        
        
        // �ڷ� ��������
        $_datas = $obj->period_list();
        
        echo json_encode( $_datas );     
   }
   
    
   function period_list_chart()
   {
        global $shop_id, $shop_type, $from_date, $to_date;
        
        
        $obj    = new class_newstat();
        $_datas = $obj->period_list();
        
        //print_r( $_datas);
        //exit;
        
        // date interval��ŭ ��ȸ..
        // ��� ���  
        $_interval = intval((strtotime( $to_date )-strtotime( $from_date )) / 86400);      
        $_start    = round( abs(strtotime(date('y-m-d'))-strtotime($to_date)) / 86400, 0 );
        $_interval = $_start + $_interval;
    
        echo "<chart caption='' yAxisName='Price' bgColor='F7F7F7, E9E9E9' showValues='10' numVDivLines='10' divLineAlpha='30' labelPadding ='5' yAxisValuesPadding ='10'><categories>";
        // category
        $_start = $_start ? $_start : 0;
        
        
        for ( $i = $_interval; $i >= $_start; $i-- )
        {
            if ( $i == 0 )
                $_date = date('Y-m-d', strtotime("Now"));               
            else
                $_date = date('Y-m-d', strtotime("-$i day"));               
            
            echo "<category label='" . $_date . "' />"; 
        }
        echo "</categories>";
        
        // �ڻ�        
        echo "<dataset seriesName='�ڻ����' color='3366CC' >";        
        $total = 0;
        // avg_list�� key�� ���� ��¥��
        // key�� avg_list�� ����ϰ� data�� _data['list']�� ��� ��.
        //foreach ( $_datas['avg_list'] as $key => $value )
        //{
        for ( $i = $_interval; $i >= $_start; $i-- )
        {
            if ( $i == 0 )
                $_date = date('Y-m-d', strtotime("Now"));               
            else
                $_date = date('Y-m-d', strtotime("-$i day"));   
            
            $data = $_datas['list'][$_date];
            echo "<set value='" . str_replace( ",","", $data['supply_price'] ? $data['supply_price'] : 0). "' date='$_date'/>";          
        }
        echo "</dataset>";
        
        // �߰� ��ü
        echo "<dataset seriesName='�߰���ü' color='A66EDD' >";        
        $total = 0;
        //foreach ( $_datas['avg_list'] as $data )
        //{
        for ( $i = $_interval; $i >= $_start; $i-- )
        {
            $_date = date('Y-m-d', strtotime("-$i day"));  
            
            $data = $_datas['avg_list'][$_date];
            echo "<set value='" . str_replace( ",","", $data['middle'] ? $data['middle'] : 0 ). "' />";          
        }
        echo "</dataset>";
                
        // ��ü ��ü
        echo "<dataset seriesName='��ü��ü' color='ff0000' >";
        //foreach ( $_datas['avg_list'] as $data )
        //{
        for ( $i = $_interval; $i >= $_start; $i-- )
        {
            $_date = date('Y-m-d', strtotime("-$i day"));  
            
            $data = $_datas['avg_list'][$_date];
            echo "<set value='";
            echo $data['tot'] ? $data['tot'] : 0;
            echo "'  date='$_date'/>";
        }
        echo "</dataset>";        
        echo "</chart>";
    }
    
}
?>
