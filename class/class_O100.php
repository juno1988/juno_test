<?
include_once "class_top.php";
include_once "class_period.php";
include_once "class_newstat.php";
include_once "class_file.php";

class class_O100 extends class_top
{
    function period_list()
    {
        $obj    = new class_period();
        $_datas = array();
        // �ڷ� ����
        
        
        // �ڷ� ��������
        $_datas = $obj->period_list();
        
        echo json_encode( $_datas );     
   }
 
   //***//
   // save file 
   // 2009.2.2 - jk
   //***//
   function save_file()
   {
        global $connect, $user_id;

        $obj    = new class_period();
        $_datas = array();
        
        // �ڷ� ��������
        $_result = $obj->period_list();
       
        foreach ( $_result['list'] as $data )
        {
            $_datas[]  = array(
	       $data[order_date], 
	       $data[shop_id],
	       $data[product_id],                                   
	       iconv('utf-8','cp949',$data[product_name]),
	       iconv('utf-8','cp949',$data[options]),
	       iconv('utf-8','cp949',$data[shop_name]),
	       $data[qty],
	       $data[shop_price],
	       $data[total_shop_price]
            );
        }
        
        $obj    = new class_file();        
        return $obj->save_file( $_datas,  "$user_id/order_products_detail.xls" );  
   }
   
    
   function period_list_chart()
   {
        global $shop_id, $shop_type, $from_date, $to_date;
        if($shop_type==''){
          $obj    = new class_newstat();
          $_datas = $obj->period_list();
          
          echo "<chart caption='' yAxisName='Price' bgColor='F7F7F7, E9E9E9' showValues='10' numVDivLines='10' divLineAlpha='30' labelPadding ='5' yAxisValuesPadding ='10'><categories>";
          foreach ( $_datas['list'] as $data )
          {
              echo "<category label='$data[crdate]' />";               
          }
          echo "</categories>";
        
          echo "<dataset seriesName='���Ǹűݾ�' color='A66EDD' >";
        
          $total = 0;
          foreach ( $_datas['list'] as $data )
          {
              echo "<set value='" . str_replace( ",","", $data[total_shop_price]). "' />";
              $total = $total + str_replace( ",","", $data[total_shop_price]);
          }
          echo "</dataset>";
        }
        
         echo "<dataset seriesName='���' color='ff0000' >";
        
        $avg = round ( $total / count( $_datas['list'] ) );
        
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='$avg' />";
        }
        echo "</dataset>";
        
        echo "</chart>";
    }
    
    
    //********************************************************
    // stat_list_detail
    // 2008-12-9 - jk
    // disp_type : product:��ǰ��        / option: �ɼǺ�
    // query_type: product_id: ��ǰ�ڵ�  / product_name: ��ǰ��
    // 
    function stat_list_detail()
    {
        global $connect,$shop_id, $supply_id, $from_date, $to_date, $query,$query_type,$disp_type,$date_type;
        
        $val         = array();
        $val['list'] = array();
        
        debug( "�� ����", 1 );
        
    }
    
    
    //********************************************************
    // get list
    // 2008-12-23 - jk
    // disp_type : product:��ǰ��        / option: �ɼǺ�
    // query_type: product_id: ��ǰ�ڵ�  / product_name: ��ǰ��
    // ���� chart�ΰ�?
    //     => ��ǰ�� ������ ������� ��...
    function product_list_chart()
    {
        global $connect,$shop_id, $supply_id, $from_date, $to_date, $query,$query_type,$disp_type,$date_type;
        
        $val         = array();
        $val['list'] = array();        
        $obj         = new class_newstat();
        $_datas      = $obj->product_list_chart();   // ��ǰ�� ���� ��Ʈ..
    
        echo "<chart caption='' yAxisName='Price' bgColor='F7F7F7, E9E9E9' showValues='10' numVDivLines='10' divLineAlpha='30' labelPadding ='5' yAxisValuesPadding ='10'><categories>";
        foreach ( $_datas['list'] as $data )
        {
            echo "<category label='$data[crdate]' />";               
        }
        echo "</categories>";
        
        echo "<dataset seriesName='����' color='A66EDD' >";
        $total = 0;
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='" . str_replace( ",","", $data[total_supply_price]). "' />";
            $total = $total + str_replace( ",","", $data[total_supply_price]);
            
        }
        echo "</dataset>";
        
        echo "<dataset seriesName='���' color='ff0000' >";
        
        $avg = round ( $total / count( $_datas['list'] ) );
        
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='$avg' />";
        }
        echo "</dataset>";
        
        echo "</chart>";
    }
    
    //********************************************************
    // get list
    // 2008-11-20 - jk
    // disp_type : product:��ǰ��        / option: �ɼǺ�
    // query_type: product_id: ��ǰ�ڵ�  / product_name: ��ǰ��
    // 
    function product_list()
    {
        global $connect,$shop_id, $supply_id, $from_date, $to_date, $query,$query_type,$disp_type,$date_type;
        
        $val         = array();
        $val['list'] = array();        
        $obj         = new class_newstat();
        $result      = $obj->product_list($shop_id, $supply_id,$date_type, $from_date, $to_date, $query,$query_type,$disp_type);   
       
        // total row ���� ���     
        $val['cnt']  = $result['total_rows'];
         
        foreach ( $result['list'] as $key => $value )
        {
            foreach ( $value['order_cs'] as $_v )
            {
                $qty = $qty + $_v;   
            }
            
            $val['list'][] = array( 
                supply_name  => $value['supply_code']
               ,product_id   => $key
               ,product_name => iconv('cp949', 'utf-8', $value['name'] )
               ,options      => iconv('cp949', 'utf-8', $value['options'] )
               ,qty          => $value['tot_qty']
               ,cancel_qty   => $value['order_cs'][1] + $value['order_cs'][2] + $value['order_cs'][3] + $value['order_cs'][4] + $value['order_cs'][12]
               ,change_qty   => $value['order_cs'][5] + $value['order_cs'][6] + $value['order_cs'][7] + $value['order_cs'][8] + $value['order_cs'][11] + $value['order_cs'][13]
               ,org_price    => $value['org_price'] * $value['tot_qty']
               ,supply_price => $value['supply_price']
               ,shop_price   => $value['amount']
               ,margin       => 1
            );
            
            $qty = 0;
        }
        
        //==============================================================
        // ���� ����, ���� ����, ���� ���� ��ǰ ����, ����
        //  ��ǰ�� ������ ����
        //  �Ⱓ�� ������ ����
        //  _str_query �� �ִ� ��� 
        //
        // stat_shop�� ���� ����. 08-12-04        
        $_ret = $obj->get_trans_info();        
        $val['trans']['pre']       = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans']['post']      = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans_pack']['pre']  = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['trans_pack']['post'] = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        // �� �ǸŰ� ����..
        $result = $obj->get_product_price();
        $val['tot_supply_price'] = $result['tot_supply_price'];
        $val['tot_org_price']    = $result['tot_org_price'];
        
        echo json_encode( $val );   
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
            
            $val['list'][] = array( 
                shop_name         => $value['shop_name']
               ,cancel_price      => $value['cancel_price']
               ,tot_org_price     => $value['tot_org_price']
               ,tot_supply_price  => $tot_supply_price
               ,tot_shop_price    => $tot_shop_price
               ,margin            => ceil($margin)
               ,pre_trans_cnt     => $value['pre_trans_cnt']    ? $value['pre_trans_cnt']    : 0
               ,post_trans_cnt    => $value['post_trans_cnt']   ? $value['post_trans_cnt']   : 0
               ,supply_trans_cnt  => $value['supply_trans_cnt'] ? $value['supply_trans_cnt'] : 0
            );
            
            $qty = 0;
        }
  
        //print_r ( $val );
        //exit;
        

        // �ش� �Ⱓ���� ��� ����� �Ǿ����� �� �� ����
        /*
        $_ret = $obj->get_trans_info();        
        
        $val['trans']['pre']       = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans']['post']      = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans_pack']['pre']  = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['trans_pack']['post'] = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        */
        
        echo json_encode( $val ); 
    }
    
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
        
        debug ( "----cnt: " . $val['cnt'] , 1 );
        
        //$val['cnt']    = 52;        
        $val['list']   = $arr_result['list'];        
        echo json_encode( $val );
    }
    
    
    
    // 
    // Ư�� ��ǰ�� �Ǹ�ó�� ��
    // 2008.11.26 - jk
    // div_grid�� row�� ������ ��쿡�� ���� �� - 12-04
    //   ���� ���� function���� �ϸ鼭 shop_list���� ����..jk 2008-12-11
    function _shop_list()
    {
        global $shop_id, $supply_id, $date_type, $from_date, $to_date;
        global $query_type, $disp_type, $query, $product_id, $connect;
        
        $val         = array();
        $val['list'] = array();        
       
        $obj         = new class_newstat();
        //$result      = $obj->get_product_list($shop_id, $supply_id,$date_type, $from_date, $to_date, $query,$query_type,$disp_type);        
        $result      = $obj->shop_list($shop_id, $supply_id,$date_type, $from_date, $to_date, $query,$query_type,$disp_type);        
       
        $val['cnt']  = 40;
        
        //
        // disp_type: shop �Ǹ�ó�� ����
        //                
        foreach ( $result['list'] as $key => $value )
        {
            $qty = $qty ? $qty : 0;
            
            // print_r ( $value );
            
            if ( $value['order_cs'] )            
                foreach ( $value['order_cs'] as $_v )
                {
                    $qty = $qty + $_v;   
                }
            
            $val['list'][] = array( 
                shop_name     => $key
               ,product_id    => $key
               ,product_name  => iconv('cp949', 'utf-8', $value['name'] )
               ,options       => iconv('cp949', 'utf-8', $value['options'] )
               ,order_cnt     => $qty
               ,trans_cnt     => 'x'
               ,cancel_cnt    => $value['order_cs'][1] + $value['order_cs'][2] + $value['order_cs'][3] + $value['order_cs'][4] + $value['order_cs'][12]
               ,org_price     => $value['org_price'] * $qty
               ,supply_price  => $value['supply_price']
               ,shop_price    => $value['amount']
               ,margin        => 1
               ,pre_trans_cnt => $value['����']
               ,post_trans_cnt=> $value['����']
            );
            
            $qty = 0;
        }

        // �ش� �Ⱓ���� ��� ����� �Ǿ����� �� �� ����
        $_ret = $obj->get_trans_info();        
        
        $val['trans']['pre']       = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans']['post']      = $_ret['trans']['����']      ? $_ret['trans']['����']      : 0;
        $val['trans_pack']['pre']  = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['trans_pack']['post'] = $_ret['trans_pack']['����'] ? $_ret['trans_pack']['����'] : 0;
        $val['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        echo json_encode( $val ); 
    }
    
    // chart����
    function product_chart()
    {
        global $shop_id, $supply_id, $date_type, $from_date, $to_date;
        global $query_type, $disp_type, $query, $connect;
        
        $query = "select ";
        
        echo "<chart caption='' yAxisName='Price' bgColor='F7F7F7, E9E9E9' showValues='10' numVDivLines='10' divLineAlpha='30' labelPadding ='5' yAxisValuesPadding ='10'><categories>";
        foreach ( $_datas['list'] as $data )
        {
            echo "<category label='$data[crdate]' />";               
        }
        echo "</categories>";
        
        echo "<dataset seriesName='����' color='A66EDD' >";
        $total = 0;
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='" . str_replace( ",","", $data[total_shop_price]). "' />";
            $total = $total + str_replace( ",","", $data[total_shop_price]);
            
        }
        echo "</dataset>";
        
        echo "<dataset seriesName='���' color='ff0000' >";
        
        $avg = round ( $total / count( $_datas['list'] ) );
        
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='$avg' />";
        }
        echo "</dataset>";
        
        echo "</chart>";
    }
    
}
?>
