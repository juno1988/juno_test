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
        // 지마켓 룰
        $this->m_rule[2] = array(
                    cnt_order   => array( field => 'qty', rule=>'count' ), 
                    cnt_product => array( field => 'qty', rule=>'sum'),    
            );
    }
    
    //****************************************
    //
    // class가 생성되면 그 즉시 실행되는 부분
    // stat_product와 stat_shop의 데이터 생성
    // 2008.10.30 - jk
    //
    function class_newstat()
    {
        global $connect, $from_date, $to_date, $date_type;
        
        $_datas['list'] = array();
        $arr_idx        = array(); // date index
        $msg            = '';
        
        // 생성해야할 data check
        $query = "select crdate
                    from stat_shop 
                   where crdate >= '$from_date' 
                     and crdate <= '$to_date'";
                     
        if ( $date_type ) 
            $query .= " and date_type='$date_type'";
        
        $query .= " group by crdate";
                     
        //******************************************
        // 있으면 가져오고 없으면 생성함
        //******************************************
        $result = mysql_query ( $query, $connect );        
        while ( $data = mysql_fetch_array( $result ) )  
        {
            // 오늘 날짜는 추가 하지 않는다.
            if ( $data[crdate] != date('Y-m-d') )
                $arr_idx[] = $data[crdate];        
        }
        
        //******************************************
        // 없는 값 check
        // 오늘은 무조건 생성..
        //******************************************
        // 오늘과 todate의 기본 - 값 생성 - 2008.11.4 jk
        $_default = intval((strtotime( date('Y-m-d') ) - strtotime( $to_date) ) / 86400 );   
        $interval = intval((strtotime( $to_date) - strtotime( $from_date) ) / 86400 );   
        
        // 오늘은 무조건 돌아야 함 $i < $interval을 사용함
        for ( $i=0; $i<=$interval; $i++ )
        {
            $j    = $i + $_default;
            $_key = date('Y-m-d', strtotime('-' . $j . ' day'));
            
            // 등록된 날짜가 있는지 여부 check
            if ( array_keys( $arr_idx, $_key ) )  
            {
                debug( "$_key exist" ,0);
            }
            else 
            {   
                debug( "$_key None exist" ,0);
                                
                // 묶음 상품 처리
                $this->build_product_data( $_key, $date_type, &$msg );      
                
                // 판매처 별 룰이 다름...
                // stat_shop에 데이터 넣어야 함 - 날짜별, 판매처별 정보 가지고 있음
                // 선착불 정보 체크 - 2008.11.10 jk
                $this->build_shop_data( $_key, $date_type, &$msg );
            }           
        }
    }
    
    //****************************************
    // 2008-12-26 - jk
    // period_list : 기간별 통계    
    function period_list()    
    {
        global $shop_id, $date_type,$from_date, $to_date, $query;
        global $connect;
        
        $_data             = array();
        $_data['list']     = array(); // 자체 데이터 평균
        $_data['avg_list'] = array(); // 업체 평균  
        
        // 조건 생성
        $_option = "  ";
        
        //----------------------------------------
        // 자체 평균
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
        // 업체 평균
        // stat server의 정보를 가져와야 함.
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
    // 상품 list
    // disp_type : product:상품별        / option: 옵션별
    // query_type: product_id: 상품코드  / product_name: 상품명
    //  검색 버튼 클릭시만 처리하는 로직 -> $_str_query가 있는 경우 동일한데..흠흠..function 정리가 필요할 듯..
    function get_product_price()    
    {
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query;
        global $connect, $start, $limit,$action;
        
        debug ( "get produt_price", 1);
        
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
        
        // 검색 조건이 있는 경우   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // 상품별 조회     
                    $_options .= " and c.org_id = '$_str_query'";
                else                           // 옵션별 조회
                    $_options .= " and c.product_id = '$_str_query'";
                    
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $_options .= " and c.name like '%$_str_query%'";
            }   
        }
                
        //--------------------------------------
        // 날짜 date_type, from_date, to_date
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
        
        // logic 2. begin of 판매 정보
        //  총 판매 / 정산가 계산.
        // 원가 계산
        $query_total = "select sum(c.org_price*b.qty) tot_org_price " . $_options;

debug ( "원가xx123: " . $query_total );

        $val['query'] = $query_total;
        $result      = mysql_query ( $query_total, $connect );        
        $data        = mysql_fetch_array( $result );        
        $val['tot_org_price']    = $data['tot_org_price'];
        
        // 정산가 생성
        $query_total = "select sum(b.supply_price) tot_supply_price " . $_options . " group by b.seq";
       
	 
        $result      = mysql_query ( $query_total, $connect );        
        while ( $data  = mysql_fetch_array( $result ) )
            $val['tot_supply_price'] = $val['tot_supply_price'] + $data['supply_price'];
        
        // end of logic 2        
        return $val;
        
        //------------------------이하 다시 짬-----------------------
        $ids = "";
        // 검색 조건이 있는 경우   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // 상품별 조회  
                {   
                    // 상품 id를 찾아야 함..
                    $query = "select product_id from products 
                               where ( product_id='$_str_query' or org_id='$_str_query' )";
                               
                    $result = mysql_query( $query, $connect );
                    
                    while ( $data = mysql_fetch_array( $result ) )
                    {
                        $ids .= "'$data[product_id]',";   
                    }
                    $ids = substr( $ids, 0, strlen( $ids ) -1 ); 
                }
                else                           // 옵션별 조회
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
        // 날짜 date_type, from_date, to_date
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
        
        // 전체 원가 계산
        $result = mysql_query ( $query, $connect );   
        $data   = mysql_fetch_array( $result );
        
        $_ret['tot_supply_price'] = $data['tot_supply_price']; 
        $_ret['tot_org_price']    = $data['tot_org_price']; 
        
        
         debug( "--------------tot org price: " . $_ret['tot_org_price'], 1 );
        
        return $_ret;
                              
    }
    
    //========================================
    // 특정 상품의 선착불 및 선불 배송개수 가져옴.
    // return data
    // val
    //  |- trans
    //  |     |- 선불 : 선불 배송 개수
    //  |     |- 착불 : 착불 배송 개수
    //  |- trans_pack
    //  |     |- 선불 : 선불 배송시 배송된 상품의 개수 -> 계산로직은 구글 docs에 정리되어 있음.
    //  |     |- 착불 : 착불 배송시 배송된 상품의 개수
    //  |- supply_trans_cnt : 선결제
    //    
    // 선불 개수: pre_deliv_count (선결제 추가) 어차피 택배사로 지출 될 금액 
    // 착불 개수: post_deliv_count
    // 선결제   : paid_deliv_count => supply_trans_cnt 2008-12-4
    // 상품 정보가 있을 경우: product_id
    // 상품 배송 개수:      : pack_deliv_qty
    function get_trans_info()
    {
        // 선결제 개수
        // 선불 개수 , 선불 총 상품 수
        // 착불 개수
        //
        global $connect;
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type;
        
        $ids = "";
        $val = array();
        
        // begin of 상품 코드 구하기
        // 상품 코드 구하기 ids 에 product_id list를 생성 함.
        $query = "";
        if ( $_str_query )
        {
            $query = "select product_id from products ";
            
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // 상품별 조회     
                    $query .= " where org_id = '$_str_query'";
                else                           // 옵션별 조회
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
        // end of 상품 코드 구하기
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
            
            debug( "기간별 배송 개수 : $query", 1);
            
            $result = mysql_query( $query, $connect );
        
            // 선, 착불 정보 저장
            $val['trans'] = array();
            while ( $data   = mysql_fetch_array( $result ) )
            {
                $val['trans'][$data[trans_who]] = $data[cnt];               
                debug( "cnt: $data[cnt] / $data[trans_who]", 1 );
            }  
            
            // 선결제
            // 개별 선결제 + 합포 선결제            
            $supply_trans_cnt      = $this->get_supply_cnt( $_option );
            $val[supply_trans_cnt] = $supply_trans_cnt;
            
            return $val;
        }
                
        
        //****************************************************
        // begin of case 1
        // case 1 특정상품의 선불배송, 착불배송 개수 ( pre_deliv_count , post_deliv_count )
        //  case 1.1 개별 선착불
        $query  = "select count(*) cnt, trans_who ";  
              
        $_option .= " from orders a, stat_product b
                   where a.seq = b.seq 
                     and {$date_type} >= '$from_date 00:00:00' 
                     and {$date_type} <= '$to_date 23:59:59' ";
        
        //--------------------------------------
        // 검색 조건이 있는 경우   query_type   
        // $_option 은 아래에서도 계속 사용할 예정 2008.12.4
        if ( $ids )
        {
            $_option .= " and b.product_id in ( $ids ) ";
        }
        
        // shop_id
        if ( $shop_id )
            $_option .= " and a.shop_id = '$shop_id' ";
        
        // 개별 상품 선착불 정보.
        $query .=  $_option . " and (a.pack is null or a.pack='') group by a.trans_who"; 
        $result = mysql_query( $query, $connect );
        
        // 선, 착불 정보 저장
        $val['trans'] = array();
        while ( $data   = mysql_fetch_array( $result ) )
        {
            $val['trans'][$data[trans_who]] = $data[cnt];               
            debug( "cnt: $data[cnt] / $data[trans_who]", 1 );
        }        
        
        //  case 1.2 합포 선착불
        //  으미..죽네..
        //  상품의 배송비를 구하는게 목적임..8개 배송 상품은 20개 개당 단가 * 총 배송 개수?
        $query  = "select trans_pack, sum(a.qty) qty, trans_who ";          
        $query .=  $_option . " and (a.pack is not null and a.pack <> '') group by a.pack";         
        
        debug( $query, 1 );
        
        $result = mysql_query( $query, $connect );
        
        // 선, 착불 정보 저장
        while ( $data   = mysql_fetch_array( $result ) )
        {
            // debug( "결과 선불2: " . $val['trans']['선불'] . ' + ' . $data[qty] . " /  " . $data[trans_pack], 1 );
            
            $val['trans'][$data[trans_who]] = $val['trans'][$data[trans_who]] + ( $data[qty] / $data[trans_pack] );
            
        }
        debug( "결과 선불2: " . ceil( $val['trans']['선불'] ), 1 );
        
        //****************************************************
        // end of case 1
        //****************************************************
        
        
        //****************************************************
        // begin of case 2
        // case 2 상품 코드, 상품배송 개수
        // 배송상품 개수 - 2008-12-3 - jk
        $query  = "select a.trans_pack, a.trans_who,a.pack,a.product_id,a.qty,a.collect_date,a.shop_id " . $_option;
        // $query .= " group by product_id";
        
        //--------------------------------------
        // supply_id
        debug( $query, 1 );
        
        $result = mysql_query ( $query, $connect );
        
        // data가 중복으로 세어져서 sum을 쓸 수 없음            
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
        
        debug( "trans pack: 선:". $val['trans_pack']['선불'] . " / 착: " . $val['trans_pack']['착불'], 1 );
            
        //****************************************************
        // end of case 2
        //****************************************************
                
        //****************************************************
        // begin of case 3
        // case 3 선결제 개수
        // supply_trans_cnt 2008-12-4
        // 합포 
        // 1, pack번호를 찾은 후 선결제 여부 trans_pack배송 찾는다.
        $supply_trans_cnt      = $this->get_supply_cnt( $_option );
        $val[supply_trans_cnt] = $supply_trans_cnt;
        
        //****************************************************
        // end of case 3
        //****************************************************
        return $val;
    }
    
    //*********************************************
    //
    // 선결제 개수 2008-12-9 jk
    function get_supply_cnt( $_option )
    {
        global $connect;
        
        $supply_trans_cnt = 0;
        // 일반 선결제 개수        
        $query = "select sum(trans_pack) s 
                         $_option 
                     and (pack is null or pack='')
                     and pre_paid='선결제'";

        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        $supply_trans_cnt = $data[s] ? $data[s] : 0;
        
        // 합포 정보 중 선결제 개수를 찾는다
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
            $query = "select count(*) c from orders where pack in ( $packs ) and pre_paid='선결제'";
            debug( $query, 1 );
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_array( $result );
            $supply_trans_cnt = $supply_trans_cnt + ( $data[c] ? $data[c] : 0 );
        }
        
        
        debug( "선결제 개수:  $supply_trans_cnt", 1 );
        return $supply_trans_cnt;
    }
    
    //****************************************************
    //
    // 이해가 잘 안되서 다시 만들어 냄 ..2008-12-2 jk
    //   1. 상품별 리스트 출력
    //   2. 전체 상품의 정산 예정금액 + 원가 출력
    //      stat_product 의 정보를 읽어와야 함.
    //      정산 예정 금액은 orders에서 읽어와야 함, 원가는 stat_product + products에서 읽어와야 함.
    //
    function product_list($shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type)
    {
        global $connect, $start, $limit,$action;
        
        $start = $start ? $start : 0;
        $limit = $limit ? $limit : 20;        
        
        $arr_product['product']= "org_id";
        $arr_product['option'] = "product_id";
        
        // 조건이 있는경우..
        $ids = $this->get_ids();
        
        //exit;
        
        // list 정보 생성
        // trans_pack이라? 
        $query = "select sum(b.qty) qty, b.order_cs, c.{$arr_product[ $disp_type ]} product_id, b.shop_id, count(*) cnt, b.trans_who, sum(b.trans_pack)
                        , c.name,c.options, c.org_price
			, sum((b.supply_price+b.extra_supply_price)*b.qty) supply_price, sum( b.amount) amount, c.org_price,c.supply_code ";
                         
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
         
        if ( $ids )
            $_options .= " and a.product_id in ( $ids ) ";
                        
        //--------------------------------------
        // 날짜 date_type, from_date, to_date
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
        // logic 1. begin of 개수 확인
        if ( $disp_type == "product" ) // 상품별 조회        
            $query_cnt = "select count(distinct c.org_id) cnt " . $_options;
        else                           // 옵션별 조회        
            $query_cnt = "select count(distinct c.product_id) cnt " . $_options;
        
        $result    = mysql_query ( $query_cnt, $connect );        
        $data      = mysql_fetch_array( $result );        
        $val['total_rows'] = $data[cnt];
        
        // end of 개수 확인
        
        //*****************************************************
        // logic 2. begin of 판매 정보
        //  총 판매 / 정산가 계산.
        // 원가 계산
        /*
        $query_total = "select sum(c.org_price) tot_org_price " . $_options;
        $result      = mysql_query ( $query_total, $connect );        
        $data        = mysql_fetch_array( $result );        
        $val['tot_org_price']    = $data['tot_org_price'];
        
        // 정산가 생성
        $query_total = "select b.supply_price " . $_options . " group by b.seq";
        
        $result      = mysql_query ( $query_total, $connect );        
        while ( $data  = mysql_fetch_array( $result ) )
            $val['tot_supply_price'] = $val['tot_supply_price'] + $data['supply_price'];
        
        // end of logic 2
        */
        
        // 상품별 조회
        if ( $disp_type == "product" ) // 상품별 조회        
            $_options .= " group by c.org_id, b.order_cs";                         
        else   // 옵션별 조회        
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
    // 상품 list
    // disp_type : product:상품별        / option: 옵션별
    // query_type: product_id: 상품코드  / product_name: 상품명
    // 
    function get_product_list($shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type)
    {
        global $connect, $start, $limit,$action;
                
        $start = $start ? $start : 0;
        $limit = $limit ? $limit : 20;        
        
        if ( $disp_type == "product" ) // 상품별 조회            
            $query = "select sum(a.qty) qty, b.order_cs, c.org_id product_id, b.shop_id, count(*) cnt, b.trans_who, b.trans_pack ";
        else if ( $disp_type == "option" )
            $query = "select sum(a.qty) qty, b.order_cs, c.product_id, c.options, b.shop_id, count(*) cnt, b.trans_who ";
            
        $query .= ", c.name, c.org_price, sum((b.supply_price+b.extra_supply_price)*b.qty) supply_price, sum( b.amount) amount, c.org_price";
        
        $query .= " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
     
        // 검색 조건이 있는 경우   query_type   
        if ( $_str_query )
        {
            if ( $query_type == "product_id" )
            {
                if ( $disp_type == "product" ) // 상품별 조회     
                    $query .= " and c.org_id = '$_str_query'";
                else                           // 옵션별 조회
                    $query .= " and a.product_id = '$_str_query'";
                    
            }
            else if ( $query_type == "product_name" )
            {
                $_str_query = iconv('utf-8', 'cp949', $_str_query );
                $query .= " and c.name like '%$_str_query%'";
            }   
        }
                
        //--------------------------------------
        // 날짜 date_type, from_date, to_date
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
        // 선착불 정보
        // 2008.11.26 - jk
        if ( $action == "shop_detail" )
            $query_trans_who = $query . " group by b.shop_id,b.trans_who";
        else
            $query_trans_who = $query . " group by b.trans_who";
        
        // 상품별 조회
        if ( $disp_type == "product" ) // 상품별 조회        
            $query .= " group by c.org_id, b.order_cs";                         
        else   // 옵션별 조회        
            $query .= " group by a.product_id, b.order_cs";
        
        
        // action shop_detail => 판매처 상세 클릭시 실행됨..
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
                // 아래 따로 있음
                //$val[$data[product_id]]['pre_deliv_cnt']           =  10;        
                //$val[$data[product_id]]['post_deliv_cnt']          =  10;        
            }
        } 
        
        //=================================================
        // 상품별 선 착불 정보..
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
    // 정산 결과 가져오기
    // date: 2008.10.28 - jk
    // date_type: collect_date, trans_date_pos
    function stat_detail($shop_id, $date_type,$from_date, $to_date)
    {
        global $connect;
        debug( "stat_detail" ,1 );
         
        //******************************************
        // 생성한 후 data가져오기
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
            // 미배송 data
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
    // 상세 정보
    // 상세한 매출 정보가 나오기 위해선 stat_product와 join해야 함.
    function stat_list_detail( $is_download=0 )
    {
        global $connect, $from_date, $to_date, $date_type,$shop_id,$start;
        
        $start = $start ? $start : 0;
        
        //******************************************
        // 생성한 후 data가져오기
        //******************************************
        // 옥션은 supply_price에 qty를 곱하면 안됨
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
            // 미배송 data
            // $cnt_notrans = $this->notrans( $from_date, $to_date, $date_type, $data[shop_id] );
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );
            
            $qty_more = 0;
            if ( $data['packed'] )
                $qty_more = count ( split( ",", $data[pack_list]) );

	    // if ( $data[pre_paid] == "선결제" && $data[order_subid] == 1) // 선결제의 경우 정산 금액을 2500 차감
            //	$data[supply_price] = $data[supply_price] - 2500; 

	    // shop_price의 경우
	    switch ( $data[shop_id]%10  )
	    {
		case 1: // auction
		   $data[shop_price] = $data[shop_price] * $data[qty]; 
		break;
	    }

	    // 묶음 상품의 다른 상품
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
    // 일자별 매출 - 정산금액? - amount
    function product_list_chart()
    {
        global $connect;
        global $shop_id, $supply_id,$date_type,$from_date, $to_date, $_str_query,$query_type,$disp_type;
        
        $ids = $this->get_ids();
        
        // 일별 상품 매출 정보 생성
        $query = "select sum((b.supply_price+b.extra_supply_price) * b.qty) total_supply_price 
		         ,b.{$date_type} crdate ";
        $_options = " from stat_product a, orders b, products c
                   where a.seq        = b.seq
                     and a.product_id = c.product_id";
         
        if ( $ids )
            $_options .= " and a.product_id in ( $ids ) ";
                        
        //--------------------------------------
        // 날짜 date_type, from_date, to_date
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
    // 일자별 정보
    function stat_list_daily()
    {
         global $connect, $from_date, $to_date, $date_type,$shop_id;
        
        //******************************************
        // build_shop function이 자료를 생성한다.
        // 생성한 후 data가져오기
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
            // 미배송 data
            $cnt_notrans = $this->notrans( $from_date, $to_date, $date_type, $data[shop_id] );
            $shop_name   = iconv('cp949','utf-8', class_shop::get_shop_name( $data["shop_id"] ) );            
            $margin      = ( $data[total_supply_price] - $data[total_org_price] ) / $data[total_supply_price] * 100;
            
            // 취소 개수와 금액을 return함
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
    
    // 취소 정보.
    function get_cancelprice( $shop_id, $crdate, $date_type )
    {
        global $connect;
        
        // 전체 취소
        $query = "select sum(qty) cnt, sum(supply_price+extra_supply_price) tot_supply_price 
                    from orders
                   where $date_type >= '$crdate 00:00:00'
                     and $date_type <= '$crdate 23:59:59'
                     and order_cs in (1,2,3,4,12)
                     and shop_id    = $shop_id";
        
        $result = mysql_query($query , $connect );
        $data = mysql_fetch_array( $result );

        // 부분 취소
        
        return $data;
    }
    
    //****************************************************
    // 주문 상세 정보 가져오기
    // stat_products를 참조 해야 함.
    function order_list()
    {
        global $shop_id,    $supply_id, $date_type, $from_date,  $to_date;
        global $query_type, $disp_type, $query,     $product_id, $connect, $start, $limit;    
        
        $start = $start ? $start : 0;
        
        //******************************************
        // 조회해야할 상품 번호가 있을 경우 가져옴 *
        $ids = $this->get_ids();
        
        // seq, product_id, order_cs, qty의 정보를 알 수 있음
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
        
        // 개수
        $query_cnt  = "select count(*) cnt ";
        
        $result        = mysql_query( $query_cnt . $option , $connect );
        $data          = mysql_fetch_array( $result );
        
        $_datas = array();
        $_datas['cnt'] = $data['cnt'];
        
        // 리스트
        // $limit가 없으면 download
        if ( $limit )
            $query .= $option . " limit $start, $limit";
        else
            $query .= $option;
        
        debug ( "[order_list] $query", 1 );
        
        $result = mysql_query( $query, $connect );
        
        
        $_datas['list'] = array();
        
        while ( $data = mysql_fetch_array( $result ) )
        {
	    // 선결제의 경우 판매가와 정산 예정가에 2500을 더한다
	    $trans_price = 0;
	    if ( $data[pre_paid] == "선결제" ) $trans_price = 2500;

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
                shop_price     => $data['shop_price']+$data[extra_shop_price] + $trans_price,   // 판매가
                amount         => $data['amount']+$data[extra_shop_price]*$data[qty] + $trans_price,   // 판매가
                supply_price   => $data['tot_supply_price'] + $trans_price, // 정산가
                org_price      => $data['org_price'],    // 원가
                trans_who      => iconv('cp949', 'utf-8', $data['trans_who'] ),
                pre_paid       => iconv('cp949', 'utf-8', $data['pre_paid'] )
                
            );
        }
        // $_datas['list'][] = array( shop_id => 2, shop_name=>'hehe');
        
        
        return $_datas;
    }
    
    //*********************************
    // 쿼리 해야할 id를 구함
    function get_ids()
    {
        global $connect, $from_date, $to_date, $date_type,$disp_type,$query,$query_type,$shop_id,$supply_id;          
        $_query = $query;  // 입력 값

        
        if ( $_query )
        {
            // id 를 구해야 함
            if ( $disp_type == "product") // 상품별 출력
            {
                // org_id 목록을 구한다.
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
                
                // org_id로 product_id group을 선택 함
                $org_ids = substr( $org_ids, 0, strlen( $org_ids ) -1 ); 
                
                if ( $org_ids )
                {
                    $ids    = '';  // ids 초기화
                    $query  = "select product_id from products where org_id in ( $org_ids )";
                    $result = mysql_query( $query, $connect );
                    while ( $data = mysql_fetch_array( $result ) )
                    {
                        $ids .= "'" . $data[product_id] . "',";
                    }
                }                
            }
            else
            {                           // option별 출력
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
    // 정산 결과 가져오기
    // date: 2008.10.28 - jk
    // date_type: collect_date, trans_date_pos
    function shop_list()
    {
        global $connect, $from_date, $to_date, $date_type,$disp_type,$query,$query_type,$shop_id,$supply_id;                  
        // debug("shop_list/ $_query/$disp_type/$query_type",1);
        
        //******************************************
        // 조회해야할 상품 번호가 있을 경우 가져옴 *
        $ids = $this->get_ids();
        
        $_query = $query;
        $query = "";
        
        // orders에서 자료 가져 옴.
        // step 1. shop별 원가
        // step 2. shop별 정산 예정 금액
        // step 3. order_cs 별 정산 예정 금액
        
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
        
        // step 2. shop별 정산 예정 금액
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
         debug( "shop_list: [정산예정] $query", 1 );
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
        
        // step 3. 배송 개수별 조회
        // 교환 배송은 택배비만 존재 함
        $query = "select shop_id, count(*) cnt, trans_who, pre_paid, sum(qty) qty ";
        # $query .= $_option . " group by shop_id, trans_pack, pre_paid";
        $query .= $_option . "  and (pack='' or pack is null or seq=pack) group by shop_id, trans_who, pre_paid";

        $result = mysql_query ( $query, $connect );
        
        while ( $data = mysql_fetch_array( $result ) )
        {   
            if ( $data['trans_who'] == "선불" )
            {
                if ( $data['pre_paid'] == '선결제' )                    
                    $_datas['list'][ $data[shop_id] ]['supply_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['supply_trans_cnt'] + $data['cnt'];
                
                $_datas['list'][ $data[shop_id] ]['pre_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['pre_trans_cnt'] + $data['cnt'];        
            }else{
                $_datas['list'][ $data[shop_id] ]['post_trans_cnt'] = $_datas['list'][ $data[shop_id] ]['post_trans_cnt'] + $data['cnt'];    
            }
            
            $_datas['list'][ $data[shop_id] ]['cnt_order'] = $_datas['list'][ $data[shop_id] ]['cnt_order'] + $data['qty'];    
        }            
        
        // 취소 금액 조회
        // date: 2009.1.19 - jk
        $arr_cancel_info = $this->get_cancel_price( $ids );
        
        foreach ( $arr_cancel_info as $_cancel_info )
        {
            $_datas['list'][ $_cancel_info[shop_id] ]['cancel_price'] = $_cancel_info['tot_cancel_price']; 
        }
        
        return $_datas;   
    }
    
    // cancel price
    // shop_list에서만 부른다.
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
        
        // 부분 취소 반영
        // 일단 보류..
        //print_r ( $arr_cancel_info );
        //echo "//---//\n";
        return $arr_cancel_info;
    }
    
    // 미배송
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
    
    // 옥션 룰 적용
    // amount와 supply_price를 수정한다.
    // 생성: 2008.11.4
    function apply_auction_rule( $_crdate, $date_type  )
    {
        global $connect;        
        
        //
        // amount의 값을 변경해야 함.
        // 2008.10.31
        $query = "select seq, options,shop_price, qty,supply_price,o_supply_price
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59'
                       and substring(shop_id,5,1) = 1";
                       
        $result = mysql_query ( $query, $connect );
        
        // 옥션의 경우 
        // 선불  : 원가에 더해야 함
        // 선결제: 정산 예정금액에 추가
        // 착불  : 건수만 보임
        while ( $data = mysql_fetch_array($result))
        {     
	    //****************************
	    // step1 
	    // auction 은 복수개의 주문일 경우 개별 정산 금액이 들어옴..수정해야 함 
            // 2009.3.12
            // 선결제       
            if ( $data['pre_paid'] == "선결제" )
            {
                $amount         = $data[amount] - 2500; 
                $supply_price   = $data[supply_price] - 2500;
                $this->move_supply_price( $data[seq] );                
                $this->update_data ( $amount, $supply_price,  $data[seq] );
            }
            $_extra = 0;
       } // end of while    
    }
    
    // 지마켓 룰 적용
    // amount와 supply_price를 수정한다.
    // 수정: 2008.11.4
    function apply_gmarket_rule( $_crdate, $date_type  )
    {
        global $connect;        
         
        //
        // 추가 결제 , 복사본 없는 경우.
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

	// 복사본이 없는 주문을 가져온다
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

	// 원 상품은 extra_price설정해야 함
        $query = "select seq, options,shop_price, qty,supply_price,amount,o_supply_price, extra_supply_price
                         ,pre_paid
                    from orders
		     where order_id in ( $str )";

        $result = mysql_query ( $query, $connect );
        
        // gmarket의 경우 
        while ( $data = mysql_fetch_array($result))
        {
	    //****************************
	    // step 1
            // 추가구매 금액 확인
            $_extra = 0;
            preg_match_all("|[(](.*)[원)]|U", $data[options], $matches);
            
            for( $i=0; $i < count($matches[1]); $i++ )
            {
                $_extra = $_extra + $matches[1][$i];
            }

	    // 추가 금액이 0 이상일 경우
	    // 가는거야!!!! 2009.3.17
            // 원본은 건드리지 않고 extra_supply_price, extra_shop_price를 추가 함
	    if ( $_extra > 0 and $data[extra_supply_price] == 0)
	    {
		$extra_shop_price   = $_extra;
		$extra_supply_price = (($_extra+$data[supply_price]) * 0.94) - $data[supply_price]; // 6% 마진

		// 원가는 건드리지 않음..ok
                // $this->move_supply_price( $data[seq] );

		$this->update_extra_price( $extra_shop_price, $extra_supply_price, $data[seq] );
      	    }

	    //=====================================
	    // 선결제 처리
	    // 안함.. 2009.3.18 - jk
	    /*
	    if ( $data['pre_paid'] == "선결제" and $data[o_supply_price] == 0)
	    {
                $supply_price = $data[supply_price] - 2500;
                $amount       = $data[amount] - 2500; 
                $this->move_supply_price( $data[seq] );
                $this->update_data ( $amount, $supply_price, $data[seq] );
	    }
	    */
	}

        // 복사본의 경우
        // amount의 값을 변경해야 함.
        // 2008.10.31
        $query = "select seq, options,shop_price, qty,supply_price,o_supply_price
                    from orders
                     where $date_type >= '$_crdate 00:00:00' 
                       and $date_type <= '$_crdate 23:59:59'
                       and shop_id%100 = 2
                       and order_subid > 1";

        $result = mysql_query ( $query, $connect );
        
        // gmarket의 경우 
        while ( $data = mysql_fetch_array($result))
        {
	    //****************************
	    // step 1
            // 추가구매 금액 확인
            $_extra = 0;
            preg_match_all("|[(](.*)[원)]|U", $data[options], $matches);
            
            for( $i=0; $i < count($matches[1]); $i++ )
            {
                $_extra = $_extra + $matches[1][$i];
            }

	    // 추가 금액이 0 이상일 경우
	    // 가는거야!!!! 2009.3.17
	    if ( $_extra > 0 )
	    {
		$amount       = $_extra;
		$supply_price = $_extra * 0.94; // 6% 마진

                // $this->move_supply_price( $data[seq] );
                $this->update_data ( $amount, $supply_price, $data[seq] );
	    }

	    /*
	    //****************************
	    // step1 
	    // gmarket은 복수개의 주문일 경우 개별 정산 금액이 들어옴..수정해야 함 
            // 2009.3.12

	    // supply_price확인
            if ( !$data[o_supply_price] )
            {
                $supply_price = ($data[supply_price] * $data[qty]) + ($_extra*0.94);
                $amount       = ($data[shop_price] * $data[qty]) + $_extra; 

	        if ( $data['pre_paid'] == "선결제" )
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
    // 추가 금액    
    function update_extra_price( $extra_shop_price, $extra_supply_price, $seq )
    {
        global $connect;
        
        $query = "update orders 
		     set extra_supply_price = $extra_supply_price 
                        ,extra_shop_price   = $extra_shop_price
                   where seq=$seq";
        mysql_query ( $query, $connect );           
    }

    // supply_price를 변경해야 하는경우 o_supply_price에 원 정산예정금액을 저장 후 supply_price를 update한다
    // supply_price 
    function move_supply_price( $seq )
    {
        global $connect;
        
        $query = "update orders set o_supply_price = supply_price,o_shop_price = amount 
                   where seq=$seq";
        mysql_query ( $query, $connect );           
    }
    
    // data update
    // o_supply_price에 원 공급 예정가가 들어가야 하는데 0이 들어있음
    // 2008.11.3 - jk
    function update_data( $amount, $supply_price, $seq )
    {
        global $connect;
        
        // order_subid가 1인 주문만 적용 함
        // 여러개의 상품을 구매할 경우 여러번 작업할 수 있음
	// -> 변경 2009.3.17 -> 변경 - 복사를 사용해 주문이 여러개로 분리됨.
        $query = "update orders set amount         = $amount, 
                                    supply_price   = $supply_price
                              where seq            = $seq 
                                ";
        debug ( $query );
        mysql_query( $query, $connect );
    }
    
    //************************************************************************
    // precondition: stat_product에 데이터가 이미 정리되어 들어가 있음
    // date_type: collect_date, trans_date_pos
    // date: 2008.10.30 - jk
    // * build_shop_data는 stat_shop테이블에 값이 없는 자료만 입력 함
    // 
    function build_shop_data( $_crdate, $date_type, &$msg )
    {
        global $connect;
        
        // gmarket rule 적용
	// 당분간 룰을 사용하지 않는다 - jk 2009.3.13
        // $this->apply_auction_rule( $_crdate, $date_type );

	// 2009.3.16 - jk
        // extra_supply_price추가
        $this->apply_gmarket_rule( $_crdate, $date_type );

	// 다른 사이트도 룰이 있어야 함
	// $this->apply_rule($_crdate, $date_type );

        //            
        // crdate가 오늘이면 자료는 일단 삭제 후 입력
        if ( $_crdate == date('Y-m-d') )
        {
            // debug( "오늘이 오늘이다" );            
            $query = "delete from stat_shop where crdate='$_crdate' and shop_id='$shop_id'";
            // debug( $query );            
            mysql_query ( $query, $connect );
        }
        
        // orders에서 처리 가능한 부분
        // step 1. cnt_order, total_shop_price, total_supply_price, pre_trans_cnt, post_trans_cnt
        // total_supply_price는 어디서 읽어와야 하나?
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
            
            // 전체 원가
            // stat_product의 상품 정보 이용함
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
        // 선착불 정보
        // orders에서 읽어와야 함.
        // 2008.10.30 배송비 기준 금액을 읽어와야 함. 없음
        // 
        // date_type이 trans_date_pos일 경우에만 실행 됨
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
                
                if ( $data[trans_who] == "선불" )                            
                {
                    if ( $data[pre_paid] == "선결제" )
                    {
                        $_arr_info[ $data[shop_id] ][supply]++;
                        
                        //
                        // 옥션의 경우 amount에서 2500을 빼줘야 한다. 2008-11-10 jk
			// 향 후 정산에서 반영 예정
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
                // 배송비 값이 모두 있을 경우 실행        
                // pre_transprice : 배송비 선불
                // post_transprice: 배송비 착불  .
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
        *@brief: 취소는 실시간 반영 함 
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
        
        $query = "update orders set amount=amount " . $arr_info['val']. " where pre_paid='선결제' and ";
        
        if ( $arr_info['pack'] )
            $query .= " pack=$arr_info[pack]";
        else
            $query .= " seq=$arr_info[seq]";
            
        //debug( $query, 1 );
    }
    
    
    // 상품의 원가 가져오기
    // 2008.11.3 - jk
    function get_org_price( $date_type, $_crdate, $shop_id )
    {
        global $connect;
        // stat_product에서 처리 가능한 부분
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
    
    // 묶음 상품의 처리 및 개별 상품의 정보 - 하나의 주문에 몇개의 상품이 판매되었는지 check
    // date: 2008.10.29
    function build_product_data( $_crdate, $date_type, &$msg )
    {        
        global $connect;
        
        // 2008.10.29
        // 상품에 대한 정보를 stat_product에 입력한다. date_type을 저장할 필요는 없음
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
        // trans_pack 작업 몇개의 상품이 배송되었는지 check
        // 합포, 선불만 처리 - jk
        // step 1. 작업을 하는 주문의 trans_pack을 qty와 동일하게 변경
        $query = "update orders set trans_pack = qty 
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'";
        $result = mysql_query( $query, $connect );             
        
        // step 2. 합포주문만 재 작업
        $query = "select pack, sum(qty) qty
                    from orders
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'
                     and trans_who = '선불'
                     and (pack is not null or pack <> '')
                   group by pack";
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $this->update_trans_pack( $data[pack], $data[qty] );
        }
        
        // step 3. 선결제 주문의 trans_pack 개수를 0으로 변경함
        // 선결제 주문도 선불로 나가기는 하는 건이기 때문에 나중에 수익으로 별도 계산 하기로 함..
        /*
        $query = "select pack
                    from orders
                   where $date_type >= '$_crdate 00:00:00'
                     and $date_type <= '$_crdate 23:59:59'
                     and pre_paid = '선결제'";
                     
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
    // stat_product에 data입력
    function insert_product( $data )
    {
        global $connect;
        
        // stat_product에 저장된 정보가 없는경우 입력 함
        if ( !$this->check_stat_product($data[seq]) )
        {
            // 묶음상품인지 여부 check
            if ( $data[packed] )
            {
                $arr_products = split(',', $data[pack_list] );   
                
                //for ( $i=0; $i <= count($arr_produts); $i++ )
                foreach ( $arr_products as $product_id )
                {
                    // 
                    // $query = " 묶음 상품 insert $data[seq] / $product_id / $data[qty] ";
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
