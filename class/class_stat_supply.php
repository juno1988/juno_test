<?
//
// name: class_stat_supply
// date: 2012.5.18
// 공급처 정산룰..
//
class cSupplyInfo
{
    public $supply_id;
    public $shop_id;
    public $tot_amount;
    public $tot_supply_price;
    public $tot_org_price;
    public $tot_qty;
    public $tot_pack_qty;           // 총 개수..
    public $su_amount;              // 공급처 별 총 판매금액
    public $su_supply_price;        // 공급처 별 총 공급금액
    public $su_org_price;           // 공급처 별 총 원가
    public $su_qty;                 // 공급처 별 총 개수
    public $su_c_amount;            // 취소금액
    public $su_c_supply_price;
    public $su_g_amount;
    public $su_g_supply_price;
    public $su_g_org_price;
    public $tr_is_pre;              // 배송(선불여부
    public $tr_is_part_deliv;       // 부분 배송 여부
    public $tr_tot_art_product_cnt; // 전체 배송 상품 개수
}

//
// 
//
class class_stat_supply
{
    private $m_list;
    private $m_connect;
    private $m_supply_list;
    private $m_shop_list;
    
    function class_stat_supply( $connect )
    {
        $this->m_connect = $connect;    
        $this->m_list    = array();
        
        // supply list
        $query = "select * from userinfo where level=0";
        $result = mysql_query($query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->m_supply_list[ $data[code] ] = $data[name];   
        }
                
        // shop list
        $query = "select * from shopinfo";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $this->m_shop_list[ $data[shop_id] ] = $data[shop_name];   
        }
    }
    
    //
    // 원하는 값 출력
    //
    public function get_value()
    {
        return "aa";   
    }
    
    //
    // ** core **
    // 정산 값 계산
    // 구조
    // 공급처 - 판매처 - 주문수량
    //        - 판매처 - 주문수량   
    //       
    public function calc($str_supply_code)
    {
        global $supply_group;
        
        $query = "select * from stat_supply ";
        
        if ( $str_supply_code )
            $query .= " where supply_id in ( $str_supply_code )";
        
        debug( "stat_supply: " . $query );
        
        $result = mysql_query( $query, $this->m_connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )        
        { 
            $this->add_row( $data );  
        }
        
        return $this->m_list;
    }
    
    //
    // list추가
    //
    private function add_row( $data )
    {
        //
        //
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'shop_name']          = $this->m_shop_list[ $data[shop_id] ];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'shop_id']            = $data[shop_id];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'supply_name']        = $this->m_supply_list[ $data[supply_id] ];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'supply_id']          = $data[supply_id];
        
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'tot_amount' ]       += $data[ 'tot_amount' ]       ? $data[ 'tot_amount' ]       : 0;
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'tot_supply_price' ] += $data[ 'tot_supply_price' ] ? $data[ 'tot_supply_price' ] : 0;
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'tot_qty' ]          += $data[ 'tot_qty' ]          ? $data[ 'tot_qty' ]          : 0;
        
        // 공급처
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_qty' ]           += $data[ 'su_qty' ];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_amount' ]        += $data[ 'su_amount' ];
        
        //su_supply_price
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_supply_price' ]  += $data[ 'su_supply_price' ];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_extra_money' ]   += $data[ 'su_extra_money' ];
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'real_supply_price' ]  += $data[ 'su_supply_price' ] + $data['su_extra_money'];
        
        // 취소
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_c_qty' ]         += $data[ 'su_c_qty' ];       // 취소 상품수
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_c_amount' ]      += $data[ 'su_c_amount' ];    // 취소 판매가
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_c_supply' ]      += $data[ 'su_c_supply' ];    // 취소 정산가
        
        // 원가
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_org_price' ]     += $data[ 'su_org_price' ];    // 상품 원가
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'su_g_org_price' ]   += $data[ 'su_g_org_price' ];  // 사은품 원가(상품원가에 포함)
        
        // 비고
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'etc' ]              = 0;  // 비고...
        
        // 선불 택배비
        // deliv_price
        if ( $data[tr_is_pre] == 1 )
        {
            $_p = $data[su_qty] / $data[tot_pack_qty] * 2500;
            $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'deliv_price' ] += $_p;
        }
        
        // 실이익
        $benefit = $data[ 'su_supply_price' ] + $data['su_extra_money'] - $data[ 'su_org_price' ] - $data[ 'su_g_org_price' ] - $_p;
        $this->m_list[ $data[supply_id] ][ $data[shop_id] ][ 'benefit' ]         += $benefit;
        
    }
    
    // 주문 수량
    public function get_order_cnt( $shop_id, $supply_id )
    {
        $query = "select count(distinct order_seq) cnt from stat_supply where supply_id='$supply_id' and shop_id='$shop_id' ";   
        $result = mysql_query( $query, $this->m_connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[cnt];
    }
    
    // 단일주문
    public function get_single_order_cnt( $shop_id, $supply_id )
    {
        $query = "select order_seq from stat_supply 
                   where supply_id = '$supply_id' 
                     and shop_id   = '$shop_id' 
                     group by order_seq";
        
        $result = mysql_query( $query, $this->m_connect );
        $seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];   
        }
        
        $query = "select order_seq from stat_supply 
                   where order_seq in ( $seqs )
                     group by order_seq having count(*) = 1";                     

        $result = mysql_query( $query, $this->m_connect );
        
        $cnt = 0;
        while( $data   = mysql_fetch_assoc( $result ) )
        {
            $cnt++;
        }
        
        return $cnt;
    }
 
    // 묶음 주문
    public function get_part_order_cnt( $shop_id, $supply_id )
    {
        $query = "select order_seq from stat_supply 
                   where supply_id = '$supply_id' 
                     and shop_id   = '$shop_id' 
                     group by order_seq";
        
        $result = mysql_query( $query, $this->m_connect );
        $seqs = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[order_seq];   
        }
        
        $query = "select order_seq from stat_supply 
                   where order_seq in ( $seqs )
                     group by order_seq having count(*) > 1";                     
      
        $result = mysql_query( $query, $this->m_connect );
        
        $cnt = 0;
        while( $data   = mysql_fetch_assoc( $result ) )
        {
            $cnt++;
        }
        
        return $cnt;
    }   
}
?>
