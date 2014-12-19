<?
require_once "class_top.php";
require_once "class_F.php";
require_once "class_C.php";
require_once "class_ui.php";
require_once "class_shop.php";
require_once "class_supply.php";
require_once "class_statrule2.php";

////////////////////////////////
// class name: class_F100
//

class class_F100 extends class_top {

    ///////////////////////////////////////////
    function F100()
    {
        global $connect;
        global $template;
        $master_code = substr( $template, 0,1);
        
        $end_date = date("Y-m-d H:i:s",strtotime("+10 years"));
        
        $par_arr = array("template","action","shop_id","supply_id","string","page");
        $link_url_list = $this->build_link_par($par_arr);  
        
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    ///////////////////////////////////////////
    function F101()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    ///////////////////////////////////////////
    function F102()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    // 룰 적용
    function apply_rule()
    {
        global $_seq, $_shop_id, $_supply_id, $connect, $_term;
        
        echo "seq: $_seq <br>";
        
        $arr_seq = split("\|", $_seq );
        
        // date string
        // 정산룰이 적용될 발주 원본..
        $seqs = "";
        foreach( $arr_seq as $seq )
        {
            if ( $seq )
            {
                $seqs .= $seqs ? "," : "";
                $seqs .= $seq;
            }
        }
        
        //**************
        $query = "select * from stat_rule2 where seq in ( $seqs )";
debug( "rule: $query");
        $result = mysql_query( $query, $connect );
        
        while ( $rule_data = mysql_fetch_assoc( $result ) )
        {
            // rule을 가져와서 rule에 맞는 amount, supply_price,prepay_trans를 구한다.
            // 기준은 from_date, to_date의 발주된 주문.
            
            $this->affect_orders( $rule_data );
        }
        
        echo "<script language='javascript'>
            alert('작업 완료');
            </script>";
    }
    
    //*************************************
    // orders에 rule적용
    function affect_orders( $rule_data )
    {
        global $connect, $_term;
debug( "F100 PHP affect_orders : 1" );

        // rule을 올린다.
        $obj = new class_statrule2();
        $obj->load_rule( $rule_data );

debug( "F100 PHP affect_orders : 2" );
        $from_date = date('Y-m-d', strtotime("-$_term day"));
        
        
        
        //*******************
        // order 검색
        $shop_id         = $rule_data[shop_id];
        if ( $shop_id == "null" )
        	$shop_id = "";
        
        $supply_id       = $rule_data[supply_id];
        if ( $supply_id == "null" )
        	$supply_id = "";
        	
        $product_id      = $rule_data[product_id]; // 없음
        
        if ( $product_id == "null" )
        	$product_id = "";
        
        $shop_product_id = $rule_data[shop_product_id];
        if ( $shop_product_id == "null" )
        	$shop_product_id = "";
        	
        //*******************************
        // supply_id, product_id는 join이 필요함        
        if ( $supply_id or $product_id ) // join 이 필요
        {
            // product_id만 있는경우
            if ( $product_id && !$supply_id )
            {   
                $query = "select a.shop_id,a.pay_type,a.qty,a.shop_product_id,a.seq, a.code11, a.code12, a.code13,a.code14,a.code15, a.code16, a.code17, a.code18, a.code19 ,a.org_trans_who
                            from orders a
                                 ,(select order_seq, product_id from order_products where product_id='$product_id') b
                    where a.seq = b.order_seq
                      and a.collect_date >= '$from_date'
                      ";                 
            }
            
            // supply_id만 있는경우
            if ( $supply_id && !$product_id )
            {
                $query = "select a.shop_id,a.pay_type,a.collect_date,a.qty,a.shop_product_id,a.seq, a.code11, a.code12, a.code13,a.code14,a.code15, a.code16, a.code17, a.code18, a.code19 ,a.org_trans_who
                            from orders a
                                 ,(select order_seq, product_id from order_products where supply_id='$supply_id') b
                    where a.seq = b.order_seq
                      and a.collect_date >= '$from_date'
                      ";                 
            }
            
            if ( $supply_id && $product_id )
            {
                $query = "select a.shop_id,a.pay_type,a.collect_date,a.qty,a.shop_product_id,a.seq, a.code11, a.code12, a.code13,a.code14,a.code15, a.code16, a.code17, a.code18, a.code19 
                            from orders a
                                 ,(select order_seq, product_id 
                                     from order_products 
                                    where supply_id  = '$supply_id' 
                                      and product_id = '$product_id') b
                    where a.seq = b.order_seq
                      and a.collect_date >= '$from_date'
                      ";                 
            }       
            
            if ( $shop_id )
                $query .= " and a.shop_id = $shop_id ";
            
            /*    
            if ( $supply_id )
                $query .= " and a.supply_id = $supply_id ";
            */
            
            // 판매처 상품코드 - jkryu 2013.4.24
            if (  $shop_product_id )
                $query .= " and a.shop_product_id='$shop_product_id'";
        }
        else
        {
            //***********************
            $query = "select * from orders 
                       where collect_date >= '$from_date'";
            
            if ( $shop_id )
                $query .= " and shop_id = $shop_id";
                
            if (  $shop_product_id )
            {
                //$query .= " and shop_product_id='$shop_product_id'";
                $str_shop_product_id = str_replace(",","','",$shop_product_id);
                $query .= " and shop_product_id in ('$str_shop_product_id')";
            }
        }
        
        // rule의 from_date, to_date를 적용
        if ( $rule_data[from_date] )
            $query .= " and collect_date >= '$rule_data[from_date]' ";
        
        if ( $rule_data[to_date] )
            $query .= " and collect_date <= '$rule_data[to_date]' ";
        
        debug("[affect rule] $query ");
        
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc ( $result ) )
        {
            $arr_info[seq] = $data[seq];
            
            // margin_trans
            $arr_info[amount      ] = $obj->get_price("amount"      , $data );
            $arr_info[supply_price] = $obj->get_price("supply_price", $data );
            $arr_info[prepay_price] = $obj->get_price("prepay_trans", $data );
            
            // margin_trans 추가 2012.9.20 - jkryu
            
            // margin_trans는 실시간 적용 -> 배송 후 적용가능..
            // $arr_info[margin_trans] = $obj->get_price("margin_trans", $data );
            // $arr_info[prepay_cnt]   = $obj->get_count("prepay_trans", $data );
            
            print_r ( $arr_info );
            //exit;
            
            $this->affect( $arr_info );
            
            $i++;
            if ( $i % 20 == 0 )
            {
                echo "<script language='javascript'>
                        parent.show_txt(" . $i . ");
                    </script>";
            }
            
        }
        
        echo "<script language='javascript'>
            parent.hide_waiting();
            </script>";
                    
    }
    
    //********************************
    // 실제 적용
    // margin_trans는 실시간 적용 -> 배송 후 적용가능..
    function affect( $arr_info )
    {
        global $connect;
        
        $arr_info[prepay_cnt] = $arr_info[prepay_price];
        
        if ( $arr_info[prepay_price] > 0 )
            $arr_info[prepay_cnt] = 1;
        else
            $arr_info[prepay_cnt] = 0;
            
        $query = "update orders 
                     set amount         = $arr_info[amount]
                         ,supply_price  = $arr_info[supply_price]
                         ,prepay_price  = $arr_info[prepay_price]
                         ,prepay_cnt    = $arr_info[prepay_cnt]
                   where seq = $arr_info[seq]";
        mysql_query ( $query, $connect );                  
         
        ////////////////////////////////////////////////////////////////////////
        // 2012-09-11 장경희 추가  
        // 적용된 정산금액에 대해 order_products의 원가별 정산금액 수정
        
        // 기존 금액이 실수라서 정수 변환
        $order_amount       = (int)($arr_info[amount] + 0.5);
        $order_supply_price = (int)($arr_info[supply_price] + 0.5);

        $org_sum = 0;
        $org_arr = array();
        $query = "select seq, org_price from order_products where order_seq=$arr_info[seq] and is_gift = 0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            // 원가 합
            $org_sum += $data[org_price];
            
            // 상품별 원가 배열
            $org_arr[$data[seq]] = $data[org_price];
        }
        
        // order_products 개수
        $cnt = count( $org_arr );
        
        $sum_amount = 0;
        $sum_supply_price = 0;

        $i = 1;
        foreach( $org_arr as $org_key => $org_val )
        {
            if( $i++ < $cnt )
            {
                $new_amount       = (int)($order_amount       * $org_val / $org_sum);
                $new_supply_price = (int)($order_supply_price * $org_val / $org_sum);
    
                $sum_amount       += $new_amount;
                $sum_supply_price += $new_supply_price;
            }
            else
            {
                $new_amount       = (int)($order_amount       - $sum_amount);
                $new_supply_price = (int)($order_supply_price - $sum_supply_price);
            }

            $query = "update order_products set prd_amount = '$new_amount', prd_supply_price = '$new_supply_price' where seq='$org_key'";
            mysql_query($query, $connect);
        }
    }
    
    // 
    function get_rule_detail()
    {
        global $connect, $seq;
        
        $query = "select * from stat_rule2 where seq=$seq";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );
    }
    
    //
    // rule list 2 
    // 2011.8.1 - jkryu
    function list_rule2()
    {
        global $connect,$shop_id,$chk_search_all;
        $arr_data = array();
        $arr_data['total_rows'] = 0;
        
        $query = "select a.shop_id sid, a.shop_id%100 code ,a.shop_name, b.*
                    from shopinfo a left join stat_rule2 b on (  a.shop_id = b.shop_id ) ";
        
        $query .= " where 
                      a.disable = 0 ";
        
        if ( $shop_id )
        	$query .= " and a.shop_id=$shop_id ";
        	
        $_today = Date("Y-m-d");
        
        if ( $chk_search_all == 0 )
            $query .= " and ( from_date <= '$_today' and to_date >= '$_today' ) and enable=1 ";
            
        $query .= "order by sort_name";
             
        debug( $query );
        
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_name = $data[shop_name];
            
			if ( $data[supply_id] )
			    $supply_name = $this->get_supply_name2( $data[supply_id] );
            // $_data = $this->get_stat_info( $data[shop_id] );  
            
            $_data['seq']             = $data['seq']             ? $data['seq']          : "0";
            $_data['title']           = $data['title']           ? $data['title']        : "&nbsp;";
            $_data['from_date']       = $data['from_date']       ? $data['from_date']    : "&nbsp;";
            $_data['to_date']         = $data['to_date']         ? $data['to_date']      : "&nbsp;";
            $_data['shop_product_id'] = $data['shop_product_id'] ? $data['shop_product_id'] : "&nbsp;";
            $_data['amount']          = $data['amount']          ? $data['amount']       : "&nbsp;";
            $_data['supply_price']    = $data['supply_price']    ? $data['supply_price'] : "&nbsp;";
            $_data['prepay_trans']    = $data['prepay_trans']    ? $data['prepay_trans'] : "&nbsp;";
            $_data['priority']        = $data['priority']        ? $data['priority']     : "&nbsp;";
            $_data['margin_trans']    = $data['margin_trans']    ? $data['margin_trans'] : "&nbsp;";
            $_data['shop_name']       = $shop_name;
            $_data['supply_name']     = $supply_name;
            $_data['shop_id']         = $data['sid'];
            $_data['enable']          = $data['enable'];
            
            $arr_data['list'][] = $_data;
             
        }
        
        echo json_encode( $arr_data );
    }
    
    function get_stat_info( $shop_id )
    {
        global $connect;
        
        $query  = "select * from stat_rule2 where shop_id='$shop_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $data[supply_id]   = $data[supply_id] ? $data[supply_id] : "&nbsp;";            
        $data[shop_name]   = class_shop::get_shop_name( $data[shop_id] );
        $data[supply_name] = class_supply::get_name( $data[supply_id]);
        
        //$data[shop_name]   = $data[shop_name]   ? $data[shop_name]   : $shop_id;
        $data[supply_name] = $data[supply_name] ? $data[supply_name] : "&nbsp;";
          
        return $data;    
    }
    
    
    // rule list
    function list_rule()
    {
        global $connect, $page,$shop_id;
        $start = ($page - 1) * 20;
        
        $arr_data = array();
        
        // page index
        // total
        $query = "select count(*) cnt from stat_rule2";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $arr_data['total_rows'] = $data[cnt];
        
        // 내용
        $query = "select * from stat_rule2 ";
        if ( $shop_id )
        	$query .= " where shop_id=$shop_id ";
        
        $query .= " order by seq desc limit $start, 20";
        
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data[supply_id] = $data[supply_id] ? $data[supply_id] : "&nbsp;";
            
            $shop_name   = class_shop::get_shop_name( $data[shop_id] );
            $supply_name = class_supply::get_name( $data[supply_id]);
            
            $arr_data['list'][] = array(
                title            => $data[title]
                ,shop_id         => $data[shop_id]
                ,shop_name       => $shop_name
                ,supply_id       => $data[supply_id]
                ,supply_name     => $supply_name
                ,priority        => $data[priority]
                ,from_date       => $data[from_date]
                ,to_date         => $data[to_date]
                ,shop_product_id => $data[shop_product_id]
                ,product_id      => $data[product_id]
                ,supply_price    => $data[supply_price]
                ,supply_percent  => $data[supply_percent]
                ,amount          => $data[amount]
                ,prepay_trans    => $data[prepay_trans]
                ,seq             => $data[seq]
                ,enable          => $data[enable]
            );
        }   
        
        echo json_encode( $arr_data );
    }
    
    //*****************
    // rule 변경
    function mod_rule()
    {
        global $connect, $seq;
        
        global $shop_id, $supply_id, $start_date , $end_date , $shop_product_id , $product_id; 
        global $priority , $amount , $supply_price , $supply_percent , $prepay_trans, $margin_trans, $title, $enable;
        
        $priority  = $priority  ? $priority  : 0;
        $supply_id = $supply_id ? $supply_id : 0;
        
        // stat_rule에 입력
        $query = "update stat_rule2
                     set  title           = '$title'
                         ,priority        = '$priority'
                         ,shop_id         = '$shop_id'
                         ,supply_id       = '$supply_id'
                         ,from_date       = '$start_date'
                         ,to_date         = '$end_date'
                         ,shop_product_id = '$shop_product_id'
                         ,product_id      = '$product_id'
                         ,supply_price    = '$supply_price'
                         ,supply_percent  = '$supply_percent'
                         ,amount          = '$amount'
                         ,prepay_trans    = '$prepay_trans'
                         ,margin_trans    = '$margin_trans'
                         ,enable          = '$enable'
                    where seq=$seq";
debug("정산룰 수정 : " . $query);
        echo $query;
                            
        mysql_query( $query, $connect );   
    }
    
    //****
    // 선택된 rule삭제..
    function del_rules()
    {
        global $connect, $_seqs;
        
        $arr_seq = split("\|", $_seqs
         );
        
        // date string
        // 정산룰이 적용될 발주 원본..
        $seqs = "";
        foreach( $arr_seq as $seq )
        {
            if ( $seq )
            {
                $seqs .= $seqs ? "," : "";
                $seqs .= $seq;
            }
        }
                
        $query = "delete from stat_rule2 where seq in ( $seqs )";

        echo $query;
        mysql_query( $query, $connect );
        
    }
    
    // rule 삭제    
    function del_rule()
    {
        global $connect, $seq;
        
        $query = "delete from stat_rule2 where seq=$seq";   

        mysql_query( $query, $connect );
    }
    
    // rule 등록
    // 
    function reg_rule()
    {
        global $connect;
        global $shop_id, $supply_id, $start_date , $end_date , $shop_product_id , $product_id; 
        global $priority , $amount , $supply_price , $supply_percent , $prepay_trans, $margin_trans, $title;
        
        $priority = $priority ? $priority : 0;
        
        // stat_rule에 입력
        $query = "insert into stat_rule2
                     set  title           = '$title'
                         ,priority        = '$priority'
                         ,shop_id         = '$shop_id'
                         ,supply_id       = '$supply_id'
                         ,from_date       = '$start_date'
                         ,to_date         = '$end_date'
                         ,shop_product_id = '$shop_product_id'
                         ,product_id      = '$product_id'
                         ,supply_price    = '$supply_price'
                         ,supply_percent  = '$supply_percent'
                         ,amount          = '$amount'
                         ,margin_trans    = '$margin_trans'
                         ,prepay_trans    = '$prepay_trans'";
        //echo $query; 
		//debug( $query );
        mysql_query( $query, $connect );    
        
        // get last one                 
        $query = "select last_insert_id() seq from stat_rule2";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc($result );
        $seq    = $data[seq];
        
        // get
        $query = "select * from stat_rule2 where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc($result );
        
        //$arr_result['query'] = $query;
        $arr_result['data'] = $data;
        
        echo json_encode( $arr_result );
    }
}

?>
