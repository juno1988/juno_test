<?
include_once "class_top.php";
include_once "class_newstat.php";
include_once "class_file.php";

class class_P100 extends class_top
{
    // 주문 정보 download
    // save_file function name은 reserved function            
    function save_file()
    {   
        global $connect, $user_id;
       
        $obj         = new class_newstat();
	$is_download = 1;
        $arr_result= $obj->stat_list_detail( $is_download );
       
        $_datas      = array();
	$_datas[] = array(     
                      "판매처코드",
                      "판매처",
                      "관리번호", 
                      "주문번호", 
                      "발주일", 
                      "배송일",
                      "상품번호",
                      "상품명",
                      "옵션",
                      "개수",
                      "배송상태",
                      "CS상태", 
                      "원가",
                      "정산가",
                      "판매가"
                  ); 
        
        foreach ( $arr_result['list'] as $_result )
        {
	    
 
            $_datas[] = array( 
                            $_result['shop_id'], 
                            iconv('utf-8','cp949', $_result['shop_name']),
                            $_result['seq'], 
                            $_result['order_id'], 
                            $_result['collect_date'],
                            $_result['trans_date_pos'],
                            $_result['product_id'],
                            iconv('utf-8','cp949',$_result['product_name']),
                            iconv('utf-8','cp949',$_result['options']),
                            $_result['qty'],
                            $_result['status'],
                            $_result['order_cs'],
                            $_result['org_price'],
                            $_result['supply_price'],
                            $_result['amount'],
                            $_result['shop_price'],
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
        // disp_type: shop 판매처별 정보
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
  
        /*
        $_ret = $obj->get_trans_info();        
        $val['trans']['pre']       = $_ret['trans']['선불']      ? $_ret['trans']['선불']      : 0;
        $val['trans']['post']      = $_ret['trans']['착불']      ? $_ret['trans']['착불']      : 0;
        $val['trans_pack']['pre']  = $_ret['trans_pack']['선불'] ? $_ret['trans_pack']['선불'] : 0;
        $val['trans_pack']['post'] = $_ret['trans_pack']['착불'] ? $_ret['trans_pack']['착불'] : 0;
        $val['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        // 총 판매가 정보..
        $result = $obj->get_product_price();
        $val['tot_supply_price'] = $result['tot_supply_price'];
        $val['tot_org_price']    = $result['tot_org_price'];
        */
        
        echo json_encode( $val ); 
    }
    function shop_list_old()
    {
        $obj    = new class_newstat();
        $_datas = array();
        // 자료 생성
        
        // 자료 가져오기
        $_datas = $obj->shop_list();
        
        //==============================================================
        // 선불 개수, 착불 개수, 선물 묶음 상품 개수, 총합
        //  상품별 선착불 정보
        //  기간별 선착불 정보
        //  _str_query 이 있는 경우 
        //
        // stat_shop에 정보 있음. 08-12-04        
        $_ret = $obj->get_trans_info();       
         
        debug("착불 개수:" . $_ret['trans']['착불'] ,1);
        
        $_datas['trans']['pre']       = $_ret['trans']['선불']      ? $_ret['trans']['선불']      : 0;
        $_datas['trans']['post']      = $_ret['trans']['착불']      ? $_ret['trans']['착불']      : 0;
        $_datas['trans_pack']['pre']  = $_ret['trans_pack']['선불'] ? $_ret['trans_pack']['선불'] : 0;
        $_datas['trans_pack']['post'] = $_ret['trans_pack']['착불'] ? $_ret['trans_pack']['착불'] : 0;
        $_datas['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        // 총 판매가 정보..
        $result = $obj->get_product_price();
        $_datas['tot_supply_price']   = $result['tot_supply_price'];
        $_datas['tot_org_price']      = $result['tot_org_price'];
                
        echo json_encode( $_datas );     
    }
    
    // 환경 설정
    // 2008.11.14 - jk
    // 선결제 택배비 : supply_deliv_price
    // 선불          : pre_deliv_price
    // 백마잔        : back_deliv_price
    // 
    function set_config()
    {
        global $connect;
        
        $arr_items = array( "pre_deliv_price","back_deliv_price","supply_deliv_price");
        
        $query = "";
        foreach ( $arr_items as $key )
        {
            global $$key;
            
            if ( $$key )
            {
                $query = "delete from stat_config where code='$key'";   
                mysql_query ($query, $connect );
                $query = "insert into stat_config set code='$key', value='" . $$key . "'";   
                
                debug ( $query,1 );
                mysql_query ($query, $connect );
            }
        }
        
        $val           = array();
        $val['error']  = 0;
        $val['logmsg'] = $query;
        
        echo json_encode( $val );
        
    }
    
    // chart정보
    function stat_chart()
    {
        global $shop_id, $date_type, $from_date, $to_date;
        $obj    = new class_newstat();
        $_datas = $obj->stat_detail( $shop_id, $date_type, $from_date, $to_date );
        
        echo "<chart caption='' yAxisName='Price' bgColor='F7F7F7, E9E9E9' showValues='10' numVDivLines='10' divLineAlpha='30' labelPadding ='5' yAxisValuesPadding ='10'><categories>";
        foreach ( $_datas['list'] as $data )
        {
            echo "<category label='$data[crdate]' />";               
        }
        echo "</categories>";
        
        echo "<dataset seriesName='매출' color='A66EDD' >";
        $total = 0;
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='" . str_replace( ",","", $data[total_shop_price]). "' />";
            $total = $total + str_replace( ",","", $data[total_shop_price]);
            
        }
        echo "</dataset>";
        
        echo "<dataset seriesName='평균' color='ff0000' >";
        
        $avg = round ( $total / count( $_datas['list'] ) );
        
        foreach ( $_datas['list'] as $data )
        {
            echo "<set value='$avg' />";
        }
        echo "</dataset>";
        
        echo "</chart>";
    }
    
    
    //일자별 정보
    // 2008.11.6 - jk
    function stat_list_daily()
    {
        $obj    = new class_newstat();
        $_datas = array();
        
        // 자료 가져오기
        $_datas = $obj->stat_list_daily();
        
        //==============================================================
        // 선불 개수, 착불 개수, 선물 묶음 상품 개수, 총합
        //  상품별 선착불 정보
        //  기간별 선착불 정보
        //  _str_query 이 있는 경우 
        //
        // stat_shop에 정보 있음. 08-12-04        
        $_ret = $obj->get_trans_info();        
        debug("착불 개수:" . $_ret['trans']['착불'] ,1);
        
        $_datas['trans']['pre']       = $_ret['trans']['선불']      ? $_ret['trans']['선불']      : 0;
        $_datas['trans']['post']      = $_ret['trans']['착불']      ? $_ret['trans']['착불']      : 0;
        $_datas['trans_pack']['pre']  = $_ret['trans_pack']['선불'] ? $_ret['trans_pack']['선불'] : 0;
        $_datas['trans_pack']['post'] = $_ret['trans_pack']['착불'] ? $_ret['trans_pack']['착불'] : 0;
        $_datas['supply_trans_cnt']   = $_ret['supply_trans_cnt']   ? $_ret['supply_trans_cnt']   : 0;
        
        // 총 판매가 정보..
        $result = $obj->get_product_price();
        $_datas['tot_supply_price']   = $result['tot_supply_price'];
        $_datas['tot_org_price']      = $result['tot_org_price'];        
        
        echo json_encode( $_datas );   
    }
    
       
 
    //일자별 정보
    // 2008.11.6 - jk
    function stat_list_detail($is_download=0)
    {
        $obj    = new class_newstat();
        $_datas = array();
        // 자료 생성

        // 자료 가져오기
        $_datas = $obj->stat_list_detail($is_download);
        
        echo json_encode( $_datas );   
    }
}
?>
