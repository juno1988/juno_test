<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_C.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
require_once "class_ui.php";
require_once "class_category.php";
require_once "class_multicategory.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_I100 extends class_top
{
    //////////////////////////////////////////////////////
    // 재고 조회
    function I100()
    {
        global $template, $connect, $ecn_stock, $ecn_w_info, $ecn_w_list;
        
        // 이지체인 창고재고조회
        $this->get_ecn_info();

        $link_url = "?" . $this->build_link_url();     
        
        // 판매처별 상품 리스트를 가져온다 
        if ( $_REQUEST["page"] )
        {
         // 재고 처리
         $stock_option = 1;
         $result = class_C::get_product_supply_list( &$total_rows, $stock_option );
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 재고이력
    function I101()
    {
        global $template, $connect, $product_id, $inout_type, $stock, $page;

        // 상품정보
        $product_info = class_product::get_info($product_id);
        $supply_name = class_supply::get_name($product_info[supply_code]);
        
        // 재고이력 구하기
        $info_arr = array(
            product_id => $product_id,
            bad        => $inout_type
        );

        $obj = new class_stock();

        // 전체
        $stock_all = $obj->get_sum( $info_arr );
        $data_all = array(
            in      => ( $stock_all[$inout_type][in     ] ? $stock_all[$inout_type][in     ] : "&nbsp;" ),
            out     => ( $stock_all[$inout_type][out    ] ? $stock_all[$inout_type][out    ] : "&nbsp;" ),
            trans   => ( $stock_all[$inout_type][trans  ] ? $stock_all[$inout_type][trans  ] : "&nbsp;" ),
            arrange => ( $stock_all[$inout_type][arrange] ? $stock_all[$inout_type][arrange] : "&nbsp;" ),
            retin   => ( $stock_all[$inout_type][retin  ] ? $stock_all[$inout_type][retin  ] : "&nbsp;" ),
            retout  => ( $stock_all[$inout_type][retout ] ? $stock_all[$inout_type][retout ] : "&nbsp;" )
        );

        // 날짜별
        $total = 0;
        if( !$page ) $page = 1;
        $stock_detail = $obj->get_date_stock($product_id, $inout_type, $page, 14, &$total);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 송장, 접수 상태 조회
    function I104()
    {
        global $template, $connect, $product_id, $stock, $status, $page, $sum;

        // 상품정보
        $product_info = class_product::get_info($product_id);
        $supply_name = class_supply::get_name($product_info[supply_code]);
        
        if ( $status == "ready_trans" )
        {
            $query = "select a.seq as seq, 
                             a.pack as pack, 
                             a.collect_date as collect_date, 
                             a.collect_time as collect_time, 
                             a.order_date as order_date, 
                             a.order_time as order_time, 
                             a.shop_id as shop_id, 
                             a.order_id as a_order_id,
                             a.product_name as a_product_name,
                             a.recv_name as recv_name,
                             a.recv_tel as recv_tel,
                             a.recv_mobile as recv_mobile,
                             b.qty as qty,
                             a.cust_id cust_id 
                        from orders a, order_products b
                             ,(select * from print_enable where product_id='$product_id' and status=3 group by pack) c
                       where b.order_seq = c.order_seq 
                         and a.seq = b.order_seq";
        }
        else
        {    
            
            // 주문정보
            $query = "select a.seq as seq, 
                             a.pack as pack, 
                             a.collect_date as collect_date, 
                             a.collect_time as collect_time,
                             a.order_date as order_date, 
                             a.order_time as order_time, 
                             a.shop_id as shop_id, 
                             a.order_id as a_order_id,
                             a.product_name as a_product_name,
                             a.recv_name as recv_name,
                             a.recv_tel as recv_tel,
                             a.recv_mobile as recv_mobile,
                             b.qty as qty,
                             a.cust_id cust_id 
                        from orders a, order_products b
                       where a.status in ($status) and
                             b.product_id = '$product_id' and
                             b.order_cs not in (1,2,3,4) and
                             a.seq = b.order_seq";
        }
        
        // 개수
        $query .= " order by collect_date desc, seq desc ";
        $result = mysql_query($query, $connect);

        $sum = 0;
        
        $orders = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $temp_Arr[seq]          = $data[seq];
            $temp_Arr[pack]         = $data[pack];
        if(_DOMAIN_ == "lovestar9"  || _DOMAIN_ == 'leroom' || _DOMAIN_ == 'dragon' )
            $temp_Arr[collect_date] = $data[order_date] . " " . substr($data[order_time],0,5);
        else
            $temp_Arr[collect_date] = $data[collect_date] . " " . substr($data[collect_time],0,5);
            $temp_Arr[shop_name]    = class_shop::get_shop_name($data[shop_id]);
            $temp_Arr[order_id]     = $data[a_order_id];
            $temp_Arr[product_name] = $data[a_product_name];
            $temp_Arr[recv_name]    = $data[recv_name];
            $temp_Arr[tel]          = $data[recv_tel] . " / " . $data[recv_mobile];
            $temp_Arr[qty]          = $data[qty];
            $temp_Arr[cust_id] 	  = $data[cust_id];
            
            $orders[] = $temp_Arr;
            $sum += $data[qty];
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////////////
    // chart
    function I105()
    {
        global $template, $connect, $product_id;

    	$start_date = date('Y-m-d', strtotime("-7 day"));
    	$end_date   = date('Y-m-d', strtotime("today"));

        // 상품정보
        $product_info = class_product::get_info($product_id);
        $supply_name = class_supply::get_name($product_info[supply_code]);

        // 현 재고
        $obj = new class_stock();
        $input_info = array( product_id => $product_id );
        $stock_info = $obj->get_current_stock_all( $input_info );

        $stock = 0; // 정상재고
        foreach( $stock_info as $loc )
        {
            foreach( $loc as $key => $value )
            {
                if( $key == 0 && $value[success] == 1 )  $stock += $value[stock];
            }
        }
        
        // 입고요청수량
        $query = "select qty from stockin_req where crdate=date(now()) and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $input_req = ($data[qty] ? $data[qty] : 0);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // History
    function I106()
    {
        global $template, $connect, $product_id;

    	$start_date = date('Y-m-d', strtotime("-7 day"));
    	$end_date   = date('Y-m-d', strtotime("today"));

        // 상품정보
        $product_info = class_product::get_info($product_id);
        $supply_name = class_supply::get_name($product_info[supply_code]);

        // 현 재고
        $obj = new class_stock();
        $input_info = array( product_id => $product_id );
        $stock_info = $obj->get_current_stock_all( $input_info );

        $stock = 0; // 정상재고
        foreach( $stock_info as $loc )
        {
            foreach( $loc as $key => $value )
            {
                if( $key == 0 && $value[success] == 1 )  $stock += $value[stock];
            }
        }
        
        // 입고요청수량
        $query = "select qty from stockin_req where crdate=date(now()) and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $input_req = ($data[qty] ? $data[qty] : 0);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    function save_file_I106()
    {
    	global $connect;
    	global $product_id, $start_date, $end_date, $show_trans, $stock_type, $work_type;
    	global $supply_name, $product_name, $product_options;
    	
    	$temp_arr = $this->get_tx_history(1);
    	    	
    	$arr_datas =  array();
		$title_data = array();
		
    	$title_data[start_date]		= $start_date;
    	$title_data[end_date]		= $end_date;
    	$title_data[supply_name]	= $supply_name;
    	$title_data[product_name]	= $product_name;
    	$title_data[product_option]	= $product_options;
    	
		$arr_datas = $temp_arr['list'];

    	$this->make_file_I106($title_data, $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
    function make_file_I106( $title_data, $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $connect;
    	global $product_id, $start_date, $end_date, $show_trans, $stock_type, $work_type;
    	global $supply_name, $product_name, $product_option;
    	
    	$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item colspan=2>	공급처  </td>";
			$buffer .="<td class=str_item>	$title_data[supply_name]   	</td>";		
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
	        $buffer .="<td class=header_item colspan=2>	상품코드  	</td>";
			$buffer .="<td class=str_item>	$product_id</td>";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
	        $buffer .="<td class=header_item colspan=2>	상품명  	</td>";
			$buffer .="<td class=str_item>	$title_data[product_name]</td>";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
	        $buffer .="<td class=header_item colspan=2>	옵션  	</td>";
			$buffer .="<td class=str_item>	$title_data[product_option]</td>";
        $buffer .= "</tr>\n";

        $buffer .= "<tr>\n";
        	$buffer .="<td class=header_item colspan=2>	기간  	</td>";
			$buffer .="<td class=str_item>	$title_data[start_date] ~ $title_data[end_date]</td>";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
        $buffer .= "</tr>\n";
        
        $buffer .= "<tr>\n";
	        $buffer .="<td class=header_item>작업일		</td>";
	        $buffer .="<td class=header_item>타입		</td>";
	        $buffer .="<td class=header_item>작업		</td>";
	        $buffer .="<td class=header_item>수량		</td>";
	        $buffer .="<td class=header_item>재고		</td>";
	        $buffer .="<td class=header_item>관리번호	</td>";
	        $buffer .="<td class=header_item>작업자		</td>";
	        $buffer .="<td class=header_item>메모		</td>";
	        $buffer .="<td class=header_item>전표 		</td>";
        $buffer .= "</tr>\n";
        			
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";            
	            
		        $buffer .= "<td>" . $val[crdate] . "</td>";
		        if( $val[bad] == '0' )
		            $buffer .= "<td  class=str_item>정상</td>";
		        else
		            $buffer .= "<td  class=str_item style='color:red'>" .$_SESSION[EXTRA_STOCK_TYPE]. "</td>";
		            
		        $buffer .= "<td class=str_item>" . $val[work]   . "</td>";
		        $buffer .= "<td class=num_item>" . $val[qty]    . "</td>";
		        $buffer .= "<td class=num_item>" . $val[stock]  . "</td>";
		        if( ($val[job] == 'trans' || $val[job] == 'retin') && $val[order_seq] > 0 )
		            $buffer .= "<td class=str_item>" . $val[order_seq]  . "</td>";
		        else
		            $buffer .= "<td class=str_item> </td>";
		            
		        $buffer .= "<td class=str_item>" . $val[owner]  . "&nbsp;</td>";
		        
		        $buffer .= "<td class=str_item>" . $val[memo]  . "&nbsp;</td>";
		
		        if( $val[job] == 'in' || $val[job] == 'out' || $val[job] == 'retin' || $val[job] == 'retout' )
		            $buffer .= "<td  class=str_item>" . $val[sheet]  . "</td>";
		        else
		            $buffer .= "<td  class=str_item> </td>";
		            
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    function download_I106()
    {
        global $filename;
        global $start_date	,	$end_date	,$product_id;
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date." ".$product_id."_I106.xls");
    } 

    //////////////////////////////////////////////////////
    // chart
    function I107()
    {
        global $template, $connect, $product_id;

        // 상품정보
        $product_info = class_product::get_info($product_id);
        $supply_name = class_supply::get_name($product_info[supply_code]);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 재고조정
    function I108()
    {
        global $template, $connect, $product_id, $sel_type, $sel_stock_work, $memo_work;

        $product_info = class_product::get_info( $product_id );
        $work_supply = class_supply::get_name( $product_info[supply_code] );
        $work_name = $product_info[name];
        $work_options = $product_info[options];        
        
        $obj = new class_stock();
        $input_info = array( product_id => $product_id );
        $stock_info = $obj->get_current_stock_all( $input_info );

        $stock1 = 0; // 정상재고
        $stock2 = 0; // 불량재고
        foreach( $stock_info as $loc )
        {
            foreach( $loc as $key => $value )
            {
                if( $key == 0 && $value[success] == 1 )  $stock1 += $value[stock];
                if( $key == 1 && $value[success] == 1 )  $stock2 += $value[stock];
            }
        }
        
        $work_stock = $stock1;
        $work_bad_stock = $stock2;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    
    //////////////////////////////////////////////////////
    // 공급처정보
    function I110()
    {
        global $template, $connect, $supply_code;
        
        $query = "select * from userinfo where code=$supply_code";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    
    //////////////////////////////////////////////////////
    // 
    function I111()
    {
        global $template, $connect;        
        global $supply_code, $page, $id, $s_group_id, $query_type, $query_str, $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt, $notrans_status;
        global $stock_status, $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $soldout_zero, $products_sort, $except_soldout;
        global $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
        global $category;
        global $multi_supply_group, $multi_supply;
        
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	//////////////////////////////////////////////////////
    // 
    function I112()
    {
    	// search
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $category,
			   $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
			   $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock,
			   $page, $id, $s_group_id, $stock_type;
			   
		global $str_supply_code, $query_type, $query_str, $notrans_status, $products_sort, $except_soldout, $soldout_zero,
			   $str_category, $click_index, $current_category1, $current_category2, $current_category3, $m_sub_category_1, $m_sub_category_2, $m_sub_category_3;
    	global $category;
    	global $multi_supply_group, $multi_supply;
        // 공통 모듈에서 data 가져온다.
        $total_rows = 0;
        $is_download = 1;
    	$_arr = $this->get_list( &$total_rows , $is_download);
		
		echo "<script>parent.hide_waiting();</script>";
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    function save_pre_stock()
    {
        global $template, $connect, $product_id, $qty;
        
        $query = "update products set safe_stock=$qty where product_id='$product_id'";
        mysql_query($query, $connect);
    }

    // 재고조정 데이터 삭제
    function init()
    {
        global $connect;
        class_stock::clear_template();
    }

    function upload()
    {
        $obj = new class_file();
        $arr_data =  $obj->upload();
        
        class_stock::clear_template();
        foreach ($arr_data as $data )
        {
            if ( $data[11] )
            {
                $_data = array( 
                    product_id     => $data[1]
                    ,name          => $data[2]
                    ,options       => $data[3]
                    ,current_stock => $data[5]
                    ,stock         => $data[10]
                    ,command       => $data[11]
                );   
                class_stock::insert_template( $_data );
            }
        }
        
        echo "
        <script language='javascript'>
        parent.load_data()
        </script>
        ";        
    }

    //******************************
    // 재고 적용
    function apply()
    {
        $arr_data = class_stock::get_stock_template();
        
        print_r ( $arr_data );
        
        $i = 0;
        $obj = new class_stock();
        
        foreach ( $arr_data['list'] as $data )
        {
            $command = mb_convert_case( $data['command'], MB_CASE_UPPER, "UTF-8");
            
            // 입고
            if ( $command == "A" )
            {
                $obj->stock_in( $data[product_id], $data[stock]);        
            }
            // 조정
            else if ( $command == "M" )
            {
                $obj->stock_arrange( $data[product_id], $data[stock]);        
            }
            // 출고
            else if ( $command == "D" )
            {
                $obj->stock_out( $data[product_id], $data[stock]);        
            }
            
            $i++;
            echo "
            <script language='javascript'>
            parent.show_txt('$i/" . $arr_data['cnt'] . " - " . $data['command'] . "/" . $data['stock'] . "')
            </script>
            ";  
        }   
        
        // 재고 테이블 초기화
        class_stock::clear_template();
        
        echo "
        <script language='javascript'>
        parent.hide_waiting()
        parent.load_data()
        </script>
        ";       
    }

    //******************************
    // template data 읽어온다.
    // 2009.7.31
    function load_template_data()
    {
        global $connect;
        
        $arr_data = array();
        
        // 총 개수
        $query  = "select count(*) cnt from stock_template where login_id='$_SESSION[LOGIN_ID]'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );        
        $arr_data['total_rows'] = $data['cnt'];
        
        // 200개에 대한 자료
        $query  = "select * from stock_template where login_id='$_SESSION[LOGIN_ID]' limit 200";
        $result = mysql_query( $query, $connect );
        
        while ( $data=mysql_fetch_assoc( $result )  )
        {
            $arr_data['list'][] = $data;   
        }
        echo json_encode($arr_data);
    }

    // 하위 상품 목록
    function expand()
    {
        global $connect,$product_id;
        $obj = new class_stock();
        
        $query = "select * from products where org_id='$product_id' and enable_stock=1";
        $result = mysql_query( $query, $connect );
        $arr_data = array();
        $arr_data['total'] = 0;
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data['total']++;
            $stock_info = $obj->get_stock( $data[product_id],"child" );
            
            $arr_data['list'][] = array( 
                supply_name       => ""
                ,product_id       => $data[product_id]
                ,product_name     => ""
                ,options          => $data[options]
                ,stock            => $stock_info[stock]
                ,yesterday_stock  => $stock_info[yesterday_stock]
                ,stock_in         => $stock_info[in]
                ,stock_out        => $stock_info[out]
                ,trans_cnt        => $stock_info[trans]
                ,org_price        => $data[org_price]
                ,enable_sale      => $data[enable_sale]
            );
        }
        echo json_encode($arr_data);
    }

    // get list
    function get_list( &$total_rows, $is_download=0 )
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $query_type, $query_str, $page, $category,
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt, $stock_status, $notrans_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock, $soldout_zero;
               
        global $supply_tel, $supply_brand, $supply_options, $org_price, $enable_sale, $products_sort, $except_soldout;
        global $str_supply_code, $s_group_id, $str_category;
        global $multi_supply_group, $multi_supply;
        
        // 이지체인 재고
        $this->get_ecn_info();
        
        /////////////////////////
        // products 테이블 - 상품.

        // 본상품 제외 & 삭제상품 제외 & 재고관리 상품
        
        // 본상품 등록일 join 너무 느림
        // $query_products = "select a.product_id as product_id, if(b.product_id is null, a.reg_date, b.reg_date) as reg_date 
        //                     from products a left outer join products b on (a.org_id=b.product_id) ";
        $query_products = "select a.product_id as product_id, a.reg_date as reg_date 
                             from products a ";
        
        $query_products .= " where a.is_represent=0 and a.is_delete=0 and a.enable_stock=1 ";

        
        if( $str_supply_code )
            $query_products .= " and a.supply_code in ( $str_supply_code ) ";
		if($multi_supply)
			$query_products .= " and a.supply_code in ( $multi_supply ) ";
        if( $query_str ) 
        {
            if( $query_type == 'product_id' )
                $query_products .= " and a.product_id = '$query_str' ";
                
            if( $query_type == 'name' )
            {
                // 독립서버에 한해서 멀티검색어 기능. 구분자는 $.
                if( ($_SESSION[IS_DB_ALONE] || _DOMAIN_ == 'ezadmin') && strpos($query_str, "$") !== false )
                {
                    $or_str = "";
                    foreach(explode("$", $query_str) as $str_val)
                    {
                        if( !$str_val )  continue;
                        
                        $or_str .= ($or_str ? " or " : "") . " replace(a.name,' ','') like '%$str_val%' ";
                    }
                    $query_products .= " and ($or_str) ";
                }
                else
                    // 공백제외 속도 너무 느림
                    // $query_products .= " and replace(a.name,' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";
                    $query_products .= " and a.name like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";
            }
    
            if( $query_type == 'options' )
                $query_products .= " and a.options like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";
    
            if( $query_type == 'name_options' )
                $query_products .= " and replace(concat(a.name, a.options),' ','') like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($query_str)) . "%' ";
    
            if( $query_type == 'barcode')
                $query_products .= " and a.barcode = '$query_str' ";

            if( $query_type == 'supply' )
            {
                $supply_arr = array();
                $query_supply = "select code from userinfo where level=0 and name like '%".str_replace(array('%','_'),array('\\%','\\_'),$query_str)."%' ";
                $result_supply = mysql_query($query_supply, $connect);
                while( $data_supply = mysql_fetch_assoc($result_supply) )
                    $supply_arr[] = $data_supply[code];
                
                $query_products .= " and a.supply_code in (".implode(",", $supply_arr).") ";
            }
    
            if( $query_type == 'brand' )
                $query_products .= " and a.brand like '%" . str_replace(" ", "%", trim($query_str)) . "%' ";
    
            if( $query_type == 'supply_options' )
                $query_products .= " and a.supply_options like '%" . str_replace(" ", "%", trim($query_str)) . "%' ";

            if( $query_type == 'origin' )
                $query_products .= " and a.origin like '%$query_str%' ";

            if( $query_type == 'product_memo' )
                $query_products .= " and a.memo like '%$query_str%' ";
                
            if( $query_type == 'link_id' )
                $query_products .= " and a.link_id like '%$query_str%' ";
            
            if( $query_type == 'location' )
                $query_products .= " and a.location like '$query_str%' ";
                
            if( $query_type == 'reg_date' )            
            {
				$query_str_arr = explode("~", $query_str);
			    if(is_null($query_str_arr[1]))
			    {
			    	// yyyy-mm-dd
			    	$format_arr = explode("-", $query_str_arr[0]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
						$e_date = date("Y-m-d",strtotime($query_str_arr[0]));
					}
			    	else
			    		$date_check = false;
			    }                
			    else if($query_str_arr[0]=="")
			    {
			    	// ~yyyy-mm-dd
			    	$format_arr = explode("-", $query_str_arr[1]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = "0000-00-00";
						$e_date = date("Y-m-d",strtotime($query_str_arr[1]));
					}
			    	else
			    		$date_check = false;
			    }
			    else if($query_str_arr[1]=="")
			    {
			    	// yyyy-mm-dd~
			    	$format_arr = explode("-", $query_str_arr[0]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
			    		$e_date = "2050-12-31";
					}
			    	else
			    		$date_check = false;	
			    }
			    else if($query_str_arr[0] && $query_str_arr[1])
			    {
			    	// yyyy-mm-dd ~ yyyy-mm-dd
			    	$format_arr_1 = explode("-", $query_str_arr[0]);
			    	$format_arr_2 = explode("-", $query_str_arr[1]);
			    	if($format_arr_1[0] && $format_arr_1[1] && $format_arr_1[2] && $format_arr_2[0] && $format_arr_2[1] && $format_arr_2[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
			    		$e_date = date("Y-m-d",strtotime($query_str_arr[1]));
					}
			    	else
			    	 	$date_check = false;
			    	
			    }
			    if((is_null($s_date) && is_null($e_date)))
			    {
			    	echo '<script> alert("검색어 입력이 잘못되었습니다.\\n아래 형식 중 하나의 방법으로 입력하시기 바랍니다.\\n\\n입력일 당일 : 2014-03-10\\n입력일 이후 : 2014-03-10~\\n입력일 이전 : ~2014-03-10\\n입력일 기간 : 2014-03-10~2014-03-10");</script>';
			    	$s_date = $e_date = date("Y-m-d");
			    	$query_str = $s_date."~".$e_date;
			    }			    
                $query_products .= " and a.reg_date >= '$s_date 00:00:00'
                					 and a.reg_date <= '$e_date 23:59:59'";
            }
            if( $query_type == 'sale_stop_date' )            
            {
				$query_str_arr = explode("~", $query_str);
			    if(is_null($query_str_arr[1]))
			    {
			    	// yyyy-mm-dd
			    	$format_arr = explode("-", $query_str_arr[0]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
						$e_date = date("Y-m-d",strtotime($query_str_arr[0]));
					}
			    	else
			    		$date_check = false;
			    }                
			    else if($query_str_arr[0]=="")
			    {
			    	// ~yyyy-mm-dd
			    	$format_arr = explode("-", $query_str_arr[1]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = "0000-00-00";
						$e_date = date("Y-m-d",strtotime($query_str_arr[1]));
					}
			    	else
			    		$date_check = false;
			    }
			    else if($query_str_arr[1]=="")
			    {
			    	// yyyy-mm-dd~
			    	$format_arr = explode("-", $query_str_arr[0]);
			    	if($format_arr[0] && $format_arr[1] && $format_arr[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
			    		$e_date = "2050-12-31";
					}
			    	else
			    		$date_check = false;	
			    }
			    else if($query_str_arr[0] && $query_str_arr[1])
			    {
			    	// yyyy-mm-dd ~ yyyy-mm-dd
			    	$format_arr_1 = explode("-", $query_str_arr[0]);
			    	$format_arr_2 = explode("-", $query_str_arr[1]);
			    	if($format_arr_1[0] && $format_arr_1[1] && $format_arr_1[2] && $format_arr_2[0] && $format_arr_2[1] && $format_arr_2[2])
			    	{
						$s_date = date("Y-m-d",strtotime($query_str_arr[0]));
			    		$e_date = date("Y-m-d",strtotime($query_str_arr[1]));
					}
			    	else
			    	 	$date_check = false;
			    	
			    }
			    if((is_null($s_date) && is_null($e_date)))
			    {
			    	echo '<script> alert("검색어 입력이 잘못되었습니다.\\n아래 형식 중 하나의 방법으로 입력하시기 바랍니다.\\n\\n입력일 당일 : 2014-03-10\\n입력일 이후 : 2014-03-10~\\n입력일 이전 : ~2014-03-10\\n입력일 기간 : 2014-03-10~2014-03-10");</script>';
			    	$s_date = $e_date = date("Y-m-d");
			    	$query_str = $s_date."~".$e_date;
			    }			    
                $query_products .= " and a.sale_stop_date >= '$s_date 00:00:00'
                					 and a.sale_stop_date <= '$e_date 23:59:59'";
            }
            
        }

        if( $query_type == 'stock_alarm1' )
            $query_products .= " and a.stock_alarm1 > 0 ";

        if( $query_type == 'stock_alarm2' )
            $query_products .= " and a.stock_alarm2 > 0 ";
        
        if( $category )
            $query_products .= " and a.category = '$category' ";

        //        
        // for multicategory
        // 2012.1.29 - jk str_category => m_category1, m_category2, m_category3로 구조 변경
        global $m_sub_category_1,$m_sub_category_2,$m_sub_category_3;
        $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
        if ( $arr_search_id[$m_sub_category_1] )
            $query_products .= " and a.m_category1=" . $arr_search_id[$m_sub_category_1];
        
        if ( $arr_search_id[$m_sub_category_2] )
            $query_products .= " and a.m_category2=" . $arr_search_id[$m_sub_category_2];
        
        if ( $arr_search_id[$m_sub_category_3] )
            $query_products .= " and a.m_category3=" . $arr_search_id[$m_sub_category_3];
                    
        // 품절
        if( $except_soldout == 1 )
            $query_products .= " and a.enable_sale=1 ";
        else if( $except_soldout == 2 )
            $query_products .= " and a.enable_sale=0 ";

        debug( "stock query: $query_products ");

        // products 임시테이블
        $t = gettimeofday();
        $products_temp_table = "products_temp_" . substr($t[sec],-3) . $t[usec];
        mysql_query("create table $products_temp_table (primary key (product_id)) engine=MyISAM $query_products", $connect );
/*
        $query_create = "create table $products_temp_table 
                         ( 
                             product_id varchar(30),
                             reg_date date
                         ) engine=memory";
        mysql_query($query_create, $connect);
        $query_select = $query_products;
        $result_select = mysql_query($query_select, $connect );
        while( $data_select = mysql_fetch_assoc($result_select) )
        {
            $query_insert = "insert $products_temp_table set product_id='$data_select[product_id]', reg_date='$data_select[reg_date]'";
            mysql_query($query_insert, $connect);
        }
*/
        
        /////////////////////////
        // current_stock 테이블 - 현재고
        // stock_status
        if( $stock_status || $soldout_zero || is_numeric($stock_start) || is_numeric($stock_end) )
            $use_current_stock = 1;

        //********************************************
        // 2013.10.29
        // 사용하지 않음. left outer join 으로 처리
        //********************************************
        // current_stock query
        if( $use_current_stock && 0 )
        {
            $query_current_stock = "select product_id, sum(stock) as stock from current_stock 
                                     where bad = $stock_type group by product_id $opt_current_stock";

            // current_stock 임시테이블
            $t = gettimeofday();
            $current_stock_temp_table = "current_stock_temp_" . substr($t[sec],-3) . $t[usec];
            mysql_query("create table $current_stock_temp_table (primary key (product_id)) engine=MyISAM $query_current_stock", $connect );
/*
            $query_create = "create table $current_stock_temp_table 
                             ( 
                                 product_id varchar(30),
                                 stock int,
                                 primary key (product_id)
                             ) engine=memory";
            mysql_query($query_create, $connect);
            $query_select = $query_current_stock;
            $result_select = mysql_query($query_select, $connect );
            while( $data_select = mysql_fetch_assoc($result_select) )
            {
                $query_insert = "insert $current_stock_temp_table set product_id='$data_select[product_id]', stock='$data_select[stock]'";
                mysql_query($query_insert, $connect);
            }
*/
        }
        
        /////////////////////////
        // orders 테이블 - 미배송
        $use_notrans = 0;
        if( $notrans_day || $notrans_cnt || $stock_status == 3 )
        {
            $use_notrans = 1;
            $query_notrans = "select order_products.product_id, sum(order_products.qty) sum from orders, order_products 
                              where ";
            if( $notrans_status == 1 )
                $query_notrans .= " orders.status = 1 ";
            else if( $notrans_status == 2 )
                $query_notrans .= " orders.status = 7 ";
            else
                $query_notrans .= " orders.status in (1,7) ";
                
            $query_notrans .= " and order_products.order_cs not in (1,2,3,4) ";
            if( $notrans_day )
                $query_notrans .= " and collect_date<=date_sub(now(), interval $notrans_day day)";
            
            $query_notrans .= " and orders.seq=order_products.order_seq group by order_products.product_id ";
            
            if( $notrans_cnt )
                $query_notrans .= " having sum >= $notrans_cnt";

            // orders 임시테이블
            $t = gettimeofday();
            $orders_temp_table = "orders_temp_" . substr($t[sec],-3) . $t[usec];
            mysql_query("create table $orders_temp_table (primary key (product_id)) engine=MyISAM $query_notrans", $connect );
/*
            $query_create = "create table $orders_temp_table 
                             ( 
                                 product_id varchar(30),
                                 sum int,
                                 primary key (product_id)
                             ) engine=memory";
            mysql_query($query_create, $connect);
            $query_select = $query_notrans;
            $result_select = mysql_query($query_select, $connect );
            while( $data_select = mysql_fetch_assoc($result_select) )
            {
                $query_insert = "insert $orders_temp_table set product_id='$data_select[product_id]', sum='$data_select[sum]'";
                mysql_query($query_insert, $connect);
            }
*/
        }

        /////////////////////////
        // stock_tx 테이블 - 작업
        $use_stock_tx = 0;
    if( is_numeric($work_start) || is_numeric($work_end) || $work_type == "arrange_all" || $work_type == "no_order")
        {
            $use_stock_tx = 1;

            if( $work_type == "arrange_all" )
            {
                $query_stock_tx = "select product_id, arrange as qty from stock_tx 
                                   where crdate >= '$start_date' and crdate <= '$end_date' and bad='$inout_type' and arrange <> 0 ";
                $query_stock_tx .= " group by product_id ";
            }
            else if( $work_type == "order" )
            {
                $query_stock_tx = "select product_id, sum(qty) as qty from order_products
                                    where match_date >= '$start_date 00:00:00' and
                                          match_date <= '$end_date 23:59:59' 
                                    group by product_id having ";
                if( is_numeric($work_start) )
                {
                    $query_stock_tx .= " qty >= $work_start ";
                    if( is_numeric($work_end) )
                        $query_stock_tx .= " and qty <= $work_end ";
                }
                else if( is_numeric($work_end) )
                    $query_stock_tx .= " qty <= $work_end ";
            }
            else if( $work_type == "no_order" )
            {
                $query_stock_tx = "select a.product_id, 0 as qty 
                                     from products a left outer join order_products b 
                                                                  on a.product_id = b.product_id
                                                                 and b.match_date >= '$start_date 00:00:00' 
                                                                 and b.match_date <= '$end_date 23:59:59' 
                                    where b.product_id is null";
debug("발주없음 : " . $query_stock_tx);
            }
            else
            {
                $query_stock_tx = "select product_id, sum($work_type) as qty from stock_tx 
                                   where crdate >= '$start_date' and crdate <= '$end_date' and bad='$inout_type' ";
                $query_stock_tx .= " group by product_id having";
                if( is_numeric($work_start) )
                {
                    $query_stock_tx .= " qty >= $work_start ";
                    if( is_numeric($work_end) )
                        $query_stock_tx .= " and qty <= $work_end ";
                }
                else if( is_numeric($work_end) )
                    $query_stock_tx .= " qty <= $work_end ";
            }

            // stock_tx 임시테이블
            $t = gettimeofday();
            $stock_tx_temp_table = "stock_tx_temp_" . substr($t[sec],-3) . $t[usec];
            mysql_query("create table $stock_tx_temp_table (primary key (product_id)) engine=MyISAM $query_stock_tx", $connect );
/*
            $query_create = "create table $stock_tx_temp_table 
                             ( 
                                 product_id varchar(30),
                                 sum int,
                                 primary key (product_id)
                             ) engine=memory";
            mysql_query($query_create, $connect);
            $query_select = $query_stock_tx;
            $result_select = mysql_query($query_select, $connect );
            while( $data_select = mysql_fetch_assoc($result_select) )
            {
                $query_insert = "insert $stock_tx_temp_table set product_id='$data_select[product_id]', sum='$data_select[sum]'";
                mysql_query($query_insert, $connect);
            }
*/
        }

        //////////////////////
        // 전체쿼리 만들기
        $from_opt = "";
        $where_opt = "";
        
        // from
        $from_opt = " from $products_temp_table a, products p ";

        if( $use_current_stock )
            $from_opt .= " left outer join current_stock b on p.product_id = b.product_id and b.bad = $stock_type ";

        $from_opt .= ", userinfo e ";

        if( $use_notrans ) 
            $from_opt .= ", $orders_temp_table c ";

        if( $use_stock_tx ) 
            $from_opt .= ", $stock_tx_temp_table d ";

        // where
        $where_opt = " where a.product_id=p.product_id and p.supply_code=e.code ";    

        if( $use_notrans )
            $where_opt .= " and a.product_id = c.product_id ";
        if( $use_stock_tx )
            $where_opt .= " and a.product_id = d.product_id ";
            
        // 재고 상태 - 경고수량
        if( $stock_status == 1 )
            $where_opt .= " and p.stock_alarm1 > 0 and p.stock_alarm2 < ifnull(b.stock,0) and ifnull(b.stock,0) <= p.stock_alarm1 ";
        // 재고 상태 - 위험수량
        else if( $stock_status == 2 )
            $where_opt .= " and p.stock_alarm2 > 0 and ifnull(b.stock,0) <= p.stock_alarm2 ";
        // 재고 상태 - 경고+위험수량
        else if( $stock_status == 4 )
            $where_opt .= " and (p.stock_alarm1 + p.stock_alarm2) > 0 and ifnull(b.stock,0) <= if(p.stock_alarm2 > p.stock_alarm1, p.stock_alarm2, p.stock_alarm1) ";
        // 재고 상태 - 재고부족
        else if( $stock_status == 3 )
            $where_opt .= " and ifnull(b.stock,0) < c.sum ";

        /////////////////////////
        // current_stock 테이블 - 현재고
        if( is_numeric($stock_start) )
            $where_opt .= " and ifnull(b.stock,0) >= '$stock_start' ";

        if( is_numeric($stock_end) )
            $where_opt .= " and ifnull(b.stock,0) <= '$stock_end' ";

        if( $soldout_zero )
            $where_opt .= " and (ifnull(b.stock,0)<0 or ifnull(b.stock,0)>0 or p.enable_sale=1) ";

        // order by
        if( _DOMAIN_ == 'justone' )
            $sort_p_options = "p.category, p.name";
        else
            $sort_p_options = "p.name";

        // order by
        if( _DOMAIN_ == 'ilovej' || _DOMAIN_ == 'ilovejchina' || _DOMAIN_ == 'polotown'   || $_SESSION[PRODUCT_ORDERBY] )
            $sort_c_options = "p.product_id";
        else
            $sort_c_options = "p.options";

        $order_by = "";
        if ( $products_sort == 1 ) // 상품명
            $order_by .= "order by $sort_p_options, $sort_c_options";
        else if ( $products_sort == 2 ) // 공급처 > 상품명
            $order_by .= "order by e.name, $sort_p_options, $sort_c_options";
        else if ( $products_sort == 3 )  // 등록일 > 상품명
            $order_by .= "order by a.reg_date desc, $sort_p_options, $sort_c_options";
        else if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $order_by .= "order by a.reg_date desc, e.name, $sort_p_options, $sort_c_options";
        else if ( $products_sort == 8 )  // 로케이션
            $order_by .= "order by p.location asc ";
        else if ( $products_sort == 9)  // 로케이션 > 상품명
            $order_by .= "order by p.location asc, $sort_p_options, $sort_c_options";

        $query = "select a.product_id as product_id $from_opt $where_opt $order_by";
debug("재고조회:" . $query);
        $result = mysql_query( $query, $connect );
        if( !$is_download )
        {
            $page  = $page ? $page : 1;
            $limit = 30;
            $start = ($page - 1) * $limit;
            $condition_page = " limit $start, $limit";
        
            $total_rows = mysql_num_rows($result);
            $result = mysql_query( $query . $condition_page, $connect );
            
        }   
            
        $_arr   = array();
        $i      = 0;
        $_arr['total'] = $total_rows;

        $obj = new class_stock();        
        while ( $data = mysql_fetch_array( $result ) )
        {   
debug("현재고 while : " . $data[product_id]);
            // 상품정보
            $product_info = class_product::get_info( $data[product_id] );

            // 품절제외
            if( (($except_soldout == 1 ) && $product_info[enable_sale] == 0) ||
                ($except_soldout == 2 && $product_info[enable_sale] == 1) )  
            {
                $_arr['total']--;
                continue;
            }

            $i++;

            $input_info = array( product_id => $data[product_id] );
            $stock_info = $obj->get_current_stock_all( $input_info );

            $stock1 = 0; // 정상재고
            $stock2 = 0; // 불량재고
            foreach( $stock_info as $loc )
            {
                foreach( $loc as $key => $value )
                {
                    if( $key == 0 && $value[success] == 1 )  $stock1 += $value[stock];
                    if( $key == 1 && $value[success] == 1 )  $stock2 += $value[stock];
                }
            }

            // 송장
            $trans_ready = class_stock::get_ready_stock($data[product_id]);  

            // 접수
            $before_trans = class_stock::get_ready_stock2($data[product_id]);  

            // 당일요청수량
            $today_str = date("Y-m-d");
            $query_req = "select qty from stockin_req where crdate='$today_str' and product_id='$data[product_id]'";
            $result_req = mysql_query($query_req, $connect);
            $data_req = mysql_fetch_assoc($result_req);
            
            // 기간배송수량
            $query_trans = "select sum(b.qty) sum 
                              from orders a, order_products b 
                             where a.seq=b.order_seq and 
                                   a.trans_date_pos >= '$start_date 00:00:00' and
                                   a.trans_date_pos <= '$end_date 23:59:59' and
                                   a.status=8 and 
                                   b.product_id = '$data[product_id]'
                          group by b.product_id";
            $result_trans = mysql_query($query_trans, $connect);
            $data_trans = mysql_fetch_assoc($result_trans);

            // 기간입고수량
            $query_stockin = "select sum(stockin) sum_stockin
                              from stock_tx
                             where product_id = '$data[product_id]' and 
                                   bad = 0 and
                                   crdate >= '$start_date' and
                                   crdate <= '$end_date'";
            $result_stockin = mysql_query($query_stockin, $connect);
            $data_stockin = mysql_fetch_assoc($result_stockin);

            global $check_option;

            $temp_arr = array();
			$temp_arr[supply_name]		= class_supply::get_name( $product_info[supply_code] );
			$temp_arr[product_id]		= $data[product_id];
			
			if( _DOMAIN_ == 'buyclub' )
				$temp_arr[product_name] = stock_view_icon($data[product_id]).$product_info[name];
			else
				$temp_arr[product_name] = $product_info[name];	
				
			$temp_arr[options] 			= $product_info[options];
				
			if( _DOMAIN_ == 'tokio' || _DOMAIN_ == 'kizbon' ||  _DOMAIN_ == 'ezadmin' || _DOMAIN_ == 'pinkage' || _DOMAIN_ == 'dabagirl2')
				$temp_arr[location] 	= $product_info[location];
				
			if( _DOMAIN_ == 'onseason' || _DOMAIN_ == 'paranormal' || _DOMAIN_ == 'purple' || _DOMAIN_ == 'dbk7894' || _DOMAIN_ == 'maru' || _DOMAIN_ == 'msoul' )
			{
				$temp_arr[brand] 		=  $product_info[brand];
				$temp_arr[barcode] 		=  $product_info[barcode];
			}
			$temp_arr[stock] 			= ($stock1?$stock1:0);
			$temp_arr[bad_stock] 		= ($stock2?$stock2:0);

            // 이지체인 재고
            global $ecn_stock, $ecn_w_list;
            if( $ecn_stock )
            {
                $temp_arr["ecn_stock"] = array();

                $query_ecn = "select warehouse_seq, qty from ecn_current_stock where product_id='$data[product_id]' and bad=0 and warehouse_seq in ($ecn_w_list)";
                $result_ecn = mysql_query($query_ecn, $connect);
                while($data_ecn = mysql_fetch_assoc($result_ecn))
                    $temp_arr["ecn_stock"][$data_ecn[warehouse_seq]] = $data_ecn[qty];
            }
			
if($_SESSION[STOCK_IN_STANDBY]) 
			$temp_arr[stock_in_standby] = $product_info[reserve_qty];
			
			$temp_arr[trans_ready] 		= ($trans_ready?$trans_ready:"");
			$temp_arr[before_trans] 	= ($before_trans?$before_trans:"");
			
if(_DOMAIN_ =='lalael2') // 예정수량
			$temp_arr[expect_qty] =  $this->get_expect_stockin_qty($data[product_id]);
			
			$temp_arr[work_cnt] 		= " ";
			$temp_arr[work_type]		= " ";
			$temp_arr[memo]	 			= " ";
			$temp_arr[enable_sale] 		= ($product_info[enable_sale]? "":"품절");
			$temp_arr[supply_code] 		= $product_info[supply_code];			
			$temp_arr[memo] 		= $product_info[memo];
				

            
            if( $product_info[org_id] )
            {
                $query_org_info = "select * from products where product_id='$product_info[org_id]'";
                $result_org_info = mysql_query($query_org_info, $connect);
                $data_org_info = mysql_fetch_assoc($result_org_info);
                
                $product_org_id = $product_info[org_id];
                $product_img_url = $data_org_info[img_500];
            }
            else
            {
                $product_org_id = $product_info[product_id];
                $product_img_url = $product_info[img_500];
            }
            
            $temp_arr['img_info'] = $this->disp_image3_1( $product_org_id, $product_img_url );

            if( $is_download && ($stock_status == 1 || $stock_status == 2 || $stock_status == 3 || $check_option == 1 ) )
            {
                // 공급처 연락처
                $query_supply_tel = "select * from userinfo where code='$product_info[supply_code]'";
                $result_supply_tel = mysql_query($query_supply_tel, $connect);
                $data_supply_tel = mysql_fetch_assoc($result_supply_tel);

                $stock_sub = $trans_ready + $before_trans - $stock1;
                $stock_sub = ( $stock_sub > 0 ? $stock_sub : 0 );
                
                $temp_arr["stock_alarm1"  ] = $product_info[stock_alarm1];             // 경고수량
                $temp_arr["stock_alarm2"  ] = $product_info[stock_alarm2];             // 위험수량
                $temp_arr["stock_minus"   ] = $stock_sub;                              // 부족수량
                $temp_arr["supply_tel"    ] = $data_supply_tel[tel]		. " / "	. $data_supply_tel[mobile	];  // 공급처 전화/휴대폰번호
                $temp_arr["supply_add"    ] = $data_supply_tel[address1]. " "	. $data_supply_tel[address2	];	// 공급처 주소
                $temp_arr["supply_brand"  ] = $product_info[brand];                    // 공급처 상품명
                $temp_arr["supply_options"] = $product_info[supply_options];           // 공급처 옵션
                
                if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
                    $temp_arr["org_price"     ] = $product_info[org_price];

                $temp_arr["total_price"   ] = $product_info[org_price] * ($stock1?$stock1:0);                // 재고금액
                $temp_arr["reg_date"      ] = $product_info[reg_date];                 // 등록일
                $temp_arr["enable_sale"   ] = $product_info[enable_sale] ? "":"품절";  // 품절
                $temp_arr["today_req"     ] = $data_req[qty];                          // 당일요청수량
                $temp_arr["trans_qty"     ] = $data_trans[sum];                        // 기간배송수량
                $temp_arr["trans_stockin" ] = $data_stockin[sum_stockin];              // 기간입고수량
                $temp_arr["fake_stock"    ] = ($stock1?$stock1:0) - ($trans_ready?$trans_ready:0) - ($before_trans?$before_trans:0);
                $temp_arr["location1"     ] = $product_info[location];                 // 로케이션
                $temp_arr["barcode1"      ] = $product_info[barcode];                  // 바코드
                $temp_arr["product_memo"  ] = $product_info[memo];                     // 메모
                $temp_arr["product_gift"  ] = $product_info[product_gift];             // 사은품
                
                
                $temp_arr["category"      ] = ($_SESSION[MULTI_CATEGORY] ? htmlspecialchars(class_multicategory::get_category_str($product_info[str_category])) : class_category::get_category_name($product_info[category]) );
                
                // 마지막 입고일
                $query_last_stock = "select date(crdate) last_date from stock_tx_history where product_id='$data[product_id]' and job='in' order by seq desc limit 1";
                $result_last_stock = mysql_query($query_last_stock, $connect);
                $data_last_stock = mysql_fetch_assoc($result_last_stock);

                $temp_arr["last_stockin"  ] = $data_last_stock[last_date];
                $temp_arr["org_id"        ] = $product_info[org_id];

                $temp_arr["origin"        ] = $product_info[origin];
                $temp_arr["supply_price"  ] = $product_info[supply_price];
                $temp_arr["shop_price"    ] = $product_info[shop_price];
       
                // 송장상태 수령자
                if( _DOMAIN_ == 'swim' )
                {
                    if( $trans_ready )
                    {
                        $name_str = "";
                        $query_name = "select distinct b.recv_name recv_name 
                                         from order_products a
                                             ,orders b
                                        where b.status     = 7 
                                          and a.product_id = '$data[product_id]'
                                          and a.order_cs in (0,5,6,7,8)
                                          and a.order_seq  = b.seq";
                        $result_name = mysql_query($query_name, $connect);
                        while( $data_name = mysql_fetch_assoc($result_name) )
                            $name_str .= ($name_str ? "," : "") . $data_name['recv_name'];
    
                        $temp_arr["recv_name"] = $name_str;
                    }
                    else
                        $temp_arr["recv_name"] = "";
                }
            }

            // 다운로드 아닐경우 원가 구하기
            if( !$is_download && _DOMAIN_ == 'sgo' )
            {
                // 원가
                if( array_search( "C1", explode(",",$_SESSION[AUTH]) ) === FALSE )
                    $temp_arr["org_price"     ] = $product_info[org_price];
                else
                    $temp_arr["org_price"     ] = "";
            }
            $_arr['list'][] = $temp_arr;

            if ( $i % 3 == 0)
            {
                echo "
                <script language='javascript'>
                parent.show_txt( $i )
                </script>
                ";
                flush();
            }
            
            usleep(1000);
        }

        // 임시 테이블 삭제
        mysql_query( "drop table if exists $products_temp_table");
        mysql_query( "drop table if exists $current_stock_temp_table");
        mysql_query( "drop table if exists $orders_temp_table");
        mysql_query( "drop table if exists $stock_tx_temp_table");

        return $_arr;
    }

    // search
    // 2009.7.27
    function search()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $category,
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock, $stock_type;
               
        global $str_supply_code, $query_type, $query_str, $notrans_status, $products_sort, $except_soldout, $soldout_zero;
        global $multi_supply_group, $multi_supply;
        
        // 공통 모듈에서 data 가져온다.
        $total_rows = 0;
        $_arr = $this->get_list( &$total_rows );
        $_json         = json_encode( $_arr );

        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
        </script>
        ";
    }   

    // 메인 화면에서 현재고 클릭
    function search_main_all()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   

    // 메인 화면에서 금일입고 클릭
    function search_main_in()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.work_start.value = 1;
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   

    // 메인 화면에서 금일출고 클릭
    function search_main_out()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.work_type.value = 'stockout';
          myform.work_start.value = 1;
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   

    // 메인 화면에서 금일배송 클릭
    function search_main_trans()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.work_type.value = 'trans';
          myform.work_start.value = 1;
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   

    // 메인 화면에서 재고경고 클릭
    function search_main_alarm1()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.stock_status.value = 1;
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   

    // 메인 화면에서 금일배송 클릭
    function search_main_alarm2()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, 
               $stock_start, $stock_end, $bad_start, $bad_end, $notrans_day, $notrans_cnt, $stock_status,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "
        <script language='javascript'>
          show_waiting();
          myform.action.value = 'search';
          myform.stock_status.value = 2;
          myform.except_soldout.value = 0;
          myform.submit();
        </script>
        ";
    }   


   function get_shop_name( $shop_id )
   {
      return class_C::get_shop_name($shop_id);
   }

    //****************************
    // show detail
    // date: 2009.7.29 - jk
    function show_detail()
    {
        global $product_id,$is_org;
        
        $obj_stock = new class_stock();        
        $arr_data  = array();
        
        /*
        $arr_data['product']    = array( name=>'xxx', options=>'bbb');
        $arr_data['stock_tx'][] = array( date=>'2009-7-29', stock=>'4', in=>'3',out=>2,trans=>4);
        $arr_data['stock_tx'][] = array( date=>'2009-7-27', stock=>'4', in=>'3',out=>2,trans=>4);
        
        $arr_data['history'][] = array( date=>'2009-7-27', time=>'13:00:00', job=>'in', qty=>'4',owner=>'aa');
        $arr_data['history'][] = array( date=>'2009-7-27', time=>'13:00:00', job=>'in', qty=>'2',owner=>'aa');
        */
        
        // product name, options
        $info = class_product::get_info( $product_id );
        $arr_data['product']    = array( name=> $info['name'], options=>$info['options']);
        
        // stock_tx list
        $arr_data['stock_tx']   = $obj_stock->stock_tx_list($product_id,$is_org);
        
        // stock_tx_history
        $arr_data['history'] = $obj_stock->get_tx_history($product_id,$is_org);
                
        echo json_encode( $arr_data );
    }
    
    // stock_tx_history 조회
    function get_tx_history($is_download = 0)
    {
        global $product_id, $page, $start_date, $end_date,$connect,$show_trans, $stock_type, $work_type;
        
        $arr_result = array();
        
        // list
        $query = "select * from stock_tx_history 
                   where crdate >= '$start_date 00:00:00'
                     and crdate <= '$end_date 23:59:59'
                     and product_id = '$product_id'";
                     
        if ( $show_trans != 1 )
            $query .= " and job <> 'trans'";               
                 
        if( $stock_type )
            $query .= " and bad = $stock_type-1";
        
        if( $work_type )
        {
            switch( $work_type )
            {
                case 1: $work = 'in'     ; break;
                case 2: $work = 'out'    ; break;
                case 3: $work = 'trans'  ; break;
                case 4: $work = 'arrange'; break;
                case 5: $work = 'retin'  ; break;
                case 6: $work = 'retout' ; break;
                case 7: $work = 'SHOP_REQ' ; break;
            }
            $query .= " and job = '$work'";
        }
        $query .= " and job <> 'MOVE' ";

        $result = mysql_query( $query, $connect );
        $arr_result[total_rows] = mysql_num_rows($result);

		if($is_download)
			$query .= " order by seq desc ";
		else
		{
			$start = ($page - 1) * 30 ;
	        $query .= " order by seq desc limit $start, 30";	
		}
        
        $result = mysql_query( $query, $connect );
        
        $arr_code = array( in => "입고", out => "출고" ,trans => "배송", arrange => "재고조정", retin => "반품입고", retout => "반품출고", SHOP_REQ => "매장출고", HQ_RETURN => "매장반품");
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $data["work"] = $arr_code[$data[job]];
            if( $data["sheet"] )
            {
                if( $data[job] == 'SHOP_REQ' )
                {
                    $query_sheet = "select a.seq a_seq
                                          ,b.name b_name 
                                      from ecn_warehouse_out_req_sheet a
                                          ,ecn_warehouse b
                                     where a.in_warehouse_id = b.seq
                                       and a.seq = $data[sheet]";
debug("재고로그조회 매장입고요청 : " . $query_sheet);
                    $result_sheet = mysql_query($query_sheet, $connect);
                    $data_sheet = mysql_fetch_assoc($result_sheet);
                    
                    $data["sheet"] = "[" . $data_sheet[a_seq] . "] " . $data_sheet[b_name];
debug("재고로그조회 매장입고요청 data : " . $data["sheet"]);
                }
                else
                {
                    if( $data[job] == 'in' || $data[job] == 'retin' )
                        $sheet_table = "sheet_in";
                    else
                        $sheet_table = "sheet_out";
    
                    $query_sheet = "select title from $sheet_table where seq=$data[sheet]";
                    $result_sheet = mysql_query($query_sheet, $connect);
                    $data_sheet = mysql_fetch_assoc($result_sheet);
                    
                    $data["sheet"] = $data_sheet[title];
                }
            }
            else
                $data["sheet"] = "";
            
            $arr_result['list'][] = $data;   
        }

        if($is_download)
        	return $arr_result;
        else
        	echo json_encode( $arr_result );
    }
    
    //****************************
    // show detail
    // date: 2009.7.29 - jk
    function show_detail2()
    {
        global $product_id,$is_org;
        
        $obj_stock = new class_stock();        
        $arr_data  = array();
        
        // product name, options
        $info = class_product::get_info( $product_id );
        $arr_data['product']  = array( name=> $info['name'], options=>$info['options']);
        
        $_loc = $obj_stock->get_location($product_id);
        $arr_data['location'] = $_loc['list'];
        
        // stock_tx list
        $arr_data['stock_tx'] = $obj_stock->stock_tx_location($product_id);
        
        echo json_encode( $arr_data );
    }
    
    //*******************************
    // 입고
    // 
    function stock_in()
    {
        global $product_id,$qty,$location;
        
        
        $obj = new class_stock();        
        $obj->stock_in($product_id,$qty,$location);
        
    }
    
    //*******************************
    // 출고
    // 
    function stock_out()
    {
        global $product_id,$qty,$location;
        
        $obj = new class_stock();
        
        $obj->stock_out($product_id,$qty,$location);
        
    }
    //*******************************
    // 조정
    // 
    function stock_arrange()
    {
        global $product_id,$qty,$location;
        
        $obj = new class_stock();
        $obj->stock_arrange($product_id,$qty,$location);
    }
    
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $template, $connect;
        global $supply_code, $product_id, $name, $options, $s_group_id,
               $stock_start, $stock_end, $stock_type, $notrans_day, $notrans_cnt, $stock_status, $str_supply_code,
               $start_date, $end_date, $work_type, $work_start, $work_end, $inout_type, $is_stock;
        global $multi_supply_group, $multi_supply;

        ini_set("memory_limit", "500M");
        
        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();
        
        // get_list from common module
        $is_download = 1;
        $_arr = $this->get_list( &$total_rows, $is_download );        
    
        $fn = $this->make_file( $_arr['list'] );

        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "현재고다운로드[$start_date ~ $end_date]");

        echo "<script language='javascript'>parent.set_file('$fn')</script>";      
    }
    
    function make_file( $arr_datas )
    {                
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
          
         global $check_option, $stock_status;     

        $_arr = array();
        
        $_arr[] = "공급처";
        $_arr[] = "상품코드";
        $_arr[] = "상품명";
        $_arr[] = "옵션";
        
        if( _DOMAIN_ == 'tokio' ||  _DOMAIN_ == 'kizbon' ||  _DOMAIN_ == 'ezadmin' || _DOMAIN_ == 'pinkage' || _DOMAIN_ == 'dabagirl2')
        {
            $_arr[] = "로케이션";
        }
        else if( _DOMAIN_ == 'onseason' || _DOMAIN_ == 'paranormal'  || _DOMAIN_ == 'purple'|| _DOMAIN_ == 'dbk7894' || _DOMAIN_ == 'maru' || _DOMAIN_ == 'msoul' )
        {
            $_arr[] = "공급처상품명";
            $_arr[] = "바코드";
        }
        
        $_arr[] = "정상재고";
        $_arr[] = $_SESSION[EXTRA_STOCK_TYPE] . "재고";        


        // 이지체인 창고재고
        global $ecn_stock, $ecn_w_info;
        if( $ecn_stock )
        {
            foreach( $ecn_w_info as $_v )
                $_arr[] = $_v[name];
        } 


if($_SESSION[STOCK_IN_STANDBY]) 
		$_arr[] = "입고대기";
        $_arr[] = "송장";
        $_arr[] = "접수";
if(_DOMAIN_ =='lalael2') // 예정수량
		$_arr[] = "입고예정";
        $_arr[] = "작업수량";
        $_arr[] = "작업";
        $_arr[] = "메모";
        $_arr[] = "품절";

        //상세정보포함 체크확인  
        if( $check_option == true || $stock_status == 1 || $stock_status == 2 || $stock_status == 3)
        {   
            $_arr[] = "경고수량";
            $_arr[] = "위험수량";
            $_arr[] = "부족수량";
            $_arr[] = "공급처연락처";
            $_arr[] = "공급처주소";
            $_arr[] = "공급처상품명";
            $_arr[] = "공급처옵션";
            $_arr[] = "원가";
            $_arr[] = "재고금액";
            $_arr[] = "등록일";
            $_arr[] = "당일요청수량";
            $_arr[] = "기간배송수량";
            $_arr[] = "기간입고수량";
            $_arr[] = "가재고";
            $_arr[] = "로케이션";
            $_arr[] = "바코드";
            $_arr[] = "상품메모";
            $_arr[] = "사은품";
            $_arr[] = "카테고리";
            $_arr[] = "마지막입고일";
            $_arr[] = "대표상품코드";
            
            $_arr[] = "원산지";
            $_arr[] = "공급가";
            $_arr[] = "판매가";

            if( _DOMAIN_ == 'swim' )
                $_arr[] = "수령자명";
        }  
        
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style1 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:\\@';
            $style2 = 'font:9pt "굴림"; white-space:nowrap;';
			$style3 = 'font:9pt "굴림"; white-space:nowrap; mso-number-format:"0_ ";' ;
            // for column
            foreach ( $row as $key=>$value) 
            {
                if( $key == 'img_info' || $key == 'supply_code' )  
                    continue;
                else if(  $key == 'ecn_stock' )
                {
                    foreach( $ecn_w_info as $_v )
                	    $buffer .= "<td style='$style3'>" . $value[$_v[seq]] . "</td>";
                }
                else
                {
                    $value = str_replace("<br>", " ", $value);
                    
                    if( $key == 'product_id' || $key == 'product_name' || $key == 'barcode' || $key == 'barcode1' )
                        $buffer .= "<td style='$style1'>" . $value . "</td>";
                    else if(  $key == 'stock' || $key == 'bad_stock' )
                    	$buffer .= "<td style='$style3'>" . $value . "</td>";
                    else
                    {
                        if( _DOMAIN_ == 'au2' && ($key == 'stock' || $key == 'bad_stock') && $value == 0 )
                            $buffer .= "<td style='$style2'></td>";
                        else
                            $buffer .= "<td style='$style2'>" . $value . "</td>";
                    }
                }
            }
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename, $new_name;
        
        if( !$new_name )  $new_name = "stock_data.xls";
        
        $obj = new class_file();
        $obj->download_file( $filename, $new_name);
    }    

    //////////////////////////////////////
    // 재고이력 다운로드 - 파일 만들기
    function save_file2()
    {
        global $template, $connect;
        global $product_id, $bad;
        
        // 재고이력 구하기
        $info_arr = array(
            product_id => $product_id,
            bad        => $bad
        );
        $obj = new class_stock();

        // 이력
        $_arr = $obj->get_date_stock_download($product_id, $bad);
        // 합
        $stock_all = $obj->get_sum( $info_arr );
        $_arr[] = array(
            crdate  => "합",
            in      => ( $stock_all[$bad][in     ] ? $stock_all[$bad][in     ] : "" ),
            out     => ( $stock_all[$bad][out    ] ? $stock_all[$bad][out    ] : "" ),
            trans   => ( $stock_all[$bad][trans  ] ? $stock_all[$bad][trans  ] : "" ),
            arrange => ( $stock_all[$bad][arrange] ? $stock_all[$bad][arrange] : "" ),
            retin   => ( $stock_all[$bad][retin  ] ? $stock_all[$bad][retin  ] : "" ),
            retout  => ( $stock_all[$bad][retout ] ? $stock_all[$bad][retout ] : "" ),
            stock   => "-"
        );
        
        $fn = $this->make_file2( $_arr );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file2( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_stock_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        $_arr = array(
            "날짜"
            ,"입고"
            ,"출고"
            ,"배송"
            ,"조정"
            ,"반품입고"
            ,"반품출고"
            ,"재고"
        );
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
                $buffer .= "<td style='$style'>" . $value . "</td>";
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    //////////////////////////////////////
    // 미배송주문 다운로드 - 파일 만들기
    function save_file3()
    {
        global $template, $connect;
        global $product_id, $status;
        
        // 주문정보
        $query = "select a.seq as seq, 
                         a.collect_date as collect_date, 
                         a.shop_id as shop_id, 
                         a.recv_name as recv_name,
                         a.recv_tel as recv_tel,
                         a.recv_mobile as recv_mobile,
                         b.qty as qty
                    from orders a, order_products b
                   where a.status in ($status) and
                         b.product_id = '$product_id' and
                         b.order_cs not in (1,2,3,4) and
                         a.seq = b.order_seq";
        $result = mysql_query($query, $connect);
        
        $orders = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $orders[] = array(
                seq          => $data[seq],
                collect_date => $data[collect_date],
                shop_name    => class_shop::get_shop_name($data[shop_id]),
                recv_name    => $data[recv_name],
                tel          => $data[recv_tel] . " / " . $data[recv_mobile],
                qty          => $data[qty]
            );
        }
        
        $fn = $this->make_file3( $orders );
        echo "<script language='javascript'>parent.set_file('$fn')</script>";
    }
    
    function make_file3( $arr_datas )
    {
        // 시간을 이용하여 중복되지 않는 파일명을 만든다.
        $t = gettimeofday();
        $filename = "download_order_data_" . substr($t[sec],-3) . $t[usec] . ".xls";

        // file open
        $handle = fopen ( _upload_dir . $filename, "w" );
 
        $buffer .= "
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
            <body>
            <html><table border=1>
        ";
        fwrite($handle, $buffer);
 
        $_arr = array(
            "관리번호"
            ,"발주일"
            ,"판매처"
            ,"고객명"
            ,"연락처"
            ,"수량"
        );
        $style = 'font:bold 10pt "굴림"; white-space:nowrap; background:#CCFFCC;';

        $buffer = "<tr>\n";
        foreach ( $_arr as $value) 
            $buffer .= "<td style='$style'>" . $value . "</td>";
        $buffer .= "</tr>\n";
        fwrite($handle, $buffer);

        // for row
        foreach( $arr_datas as $row )
        {
            $buffer = "<tr>\n";
            $style = 'font:9pt "굴림"; white-space:nowrap;';

            // for column
            foreach ( $row as $key=>$value) 
                $buffer .= "<td style='$style'>" . $value . "</td>";
 
            $buffer .= "</tr>\n";
 
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($fp);

        return $filename;
    }

    // 재고 작업 팝업에서 일반정보를 가져온다
    function get_stock_info()
    {
        global $connect, $template, $product_id;
        
        $val = array();
        
        $product_info = class_product::get_info( $product_id );
        $val['supply'] = class_supply::get_name( $product_info[supply_code] );
        $val['name'] = $product_info[name];
        $val['options'] = $product_info[options];        
        
        $obj = new class_stock();
        $input_info = array( product_id => $product_id );
        $stock_info = $obj->get_current_stock_all( $input_info );

        $stock1 = 0; // 정상재고
        $stock2 = 0; // 불량재고
        foreach( $stock_info as $loc )
        {
            foreach( $loc as $key => $value )
            {
                if( $key == 0 && $value[success] == 1 )  $stock1 += $value[stock];
                if( $key == 1 && $value[success] == 1 )  $stock2 += $value[stock];
            }
        }
        
        $val['stock'] = $stock1;
        $val['bad_stock'] = $stock2;
        
        echo json_encode($val);
    }
    
    // 재고 작업 실행
    function set_stock_data()
    {
        global $connect, $template, $product_id, $bad, $type, $qty, $memo;

        $val = array();

        // Lock Check
        $obj_lock = new class_lock(201);
        if( !$obj_lock->set_start(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
            echo json_encode( $val );
            return;
        }

        // input parameter
        $info_arr = array(
            type       => $type,
            product_id => $product_id,
            bad        => $bad,
            location   => 'Def',
            qty        => $qty,
            memo       => $memo
        );

        $obj = new class_stock();
        $obj->set_stock($info_arr);

        $val[stock] = class_stock::get_current_stock( $product_id, 0 );
        $val[stock_bad] = class_stock::get_current_stock( $product_id, 1 );
        
        $val['error'] = 0;

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $val['error'] = -9;
            $val['lock_msg'] = $msg;
        }

        echo json_encode($val);
    }

    // chart를 그리기 위한 재고 이력 조회
    function get_stock_history()
    {
        global $connect, $product_id, $start_date, $end_date;
        
        $chart_title1 = "재고";
        $chart_title2 = "입고";
        $chart_title3 = "배송";
        $chart_title4 = "미배송";
        $chart_title5 = "입고요청";

        echo "<chart bgColor            = 'F7F7F7, E9E9E9' 
                     showLegend         = '1'
                     showValues         = '0' 
                     numVDivLines       = '10' 
                     divLineAlpha       = '50'  
                     labelPadding       = '10' 
                     yAxisValuesPadding = '10' >";

        //=====================================================        
        // date 부분 category 생성
        $_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);
        $_start    = round( abs(strtotime(date('y-m-d'))-strtotime($end_date)) / 86400, 0 );
        $_interval = $_start + $_interval;

        echo "<categories>";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('d', strtotime("-$i day"));
                echo "<category label='$_date' />\n ";
            }
        }
        echo "</categories>";

        //////////////////////////////////////////////////////////
        // data 생성
        $_tot_deliv = 0;  // 총 배송
        $_tot_input = 0;  // 총 입고
        $query = "select crdate, 
                         sum(stock) as stock,
                         sum(trans) as trans,
                         sum(stockin) as input
                    from stock_tx 
                   where crdate     >= '$start_date' and
                         crdate     <= '$end_date' and
                         product_id = '$product_id' and
                         bad        = 0
                group by crdate";
        $result = mysql_query($query, $connect);
        $dataset = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $dataset[$data[crdate]]['stock'] = $data[stock];
            $dataset[$data[crdate]]['trans'] = $data[trans];
            $dataset[$data[crdate]]['input'] = $data[input];
            $_tot_deliv += $data[trans];
            $_tot_input += $data[input];
        }

        // 재고
        echo "<dataset seriesName='$chart_title1' color='daa530' renderAs='Area'>\n";
        if ( $_interval >= 0 )
        {
            $_last_val = 0;
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['stock'] ? $dataset[$_date]['stock'] : $_last_val;
                
                // 만일 stock_tx 없을 경우, 재고 0으로 처리되는경우, 기간 이전의 재고값 가져온다.
                if( $i == $_interval && $_val == 0 )
                {
                    $query_last = "select sum(stock) as stock
                                     from stock_tx 
                                    where crdate     < '$_date' and
                                          product_id = '$product_id' and
                                          bad        = 0
                                 group by crdate
                                 order by crdate desc
                                    limit 1";
debug( $query_last );
                    $result_last = mysql_query($query_last, $connect);
                    if( $data_last = mysql_fetch_assoc($result_last) )
                        $_val = $data_last[stock];
                    else
                        $_val = 0;
                }
                
                $_last_val = $_val;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 입고
        echo "<dataset seriesName='$chart_title2' color='3cd371' renderAs='Column'>\n";
        if ( $_interval >= 0 )
            {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['input'] ? $dataset[$_date]['input'] : 0;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 배송
        echo "<dataset seriesName='$chart_title3' color='00afff' renderAs='line'>\n";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date]['trans'] ? $dataset[$_date]['trans'] : 0;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        // 미배송
        $query = "select a.collect_date as collect_date,
                         count(b.qty) as qty
                    from orders a,
                         order_products b
                   where a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and
                         b.product_id='$product_id' and
                         a.status in (1,7 )                  
                         b.order_cs not in (1,2,3,4)                  
                group by a.collect_date";
        $result = mysql_query ( $query, $connect );
        $dataset = array();
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[collect_date]] = $data[qty];
        }

        $sum = 0;
        echo "<dataset seriesName='$chart_title4' color='dc143c' renderAs='line'>\n";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                if( $dataset[$_date] )
                     $sum = $sum + $dataset[$_date];
                else
                     $sum = $sum;
                echo "<set value='$sum' />\n ";
            }
        }
        echo "</dataset>\n";
        
        // 입고요청
        $query = "select crdate, qty 
                    from stockin_req
                   where crdate >= '$start_date' and
                         crdate <= '$end_date' and
                         product_id = '$product_id'";
        $result = mysql_query ( $query, $connect );
        $dataset = array();
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[crdate]] = $data[qty];
        }

        echo "<dataset seriesName='$chart_title5' color='b43fe9'>\n";
        if ( $_interval >= 0 )
        {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                $_val  = $dataset[$_date] ? $dataset[$_date] : 0;
                echo "<set value='$_val' />\n ";
            }
        }
        echo "</dataset>\n";

        echo "
              <styles>
                <definition>
                    <style name='MyFirstFontStyle' type='font' font='dotum' size='11' color='000000' bold='0' bgColor='FFFFDD' />
                </definition>
            
                <application>
                    <apply toObject='Legend' styles='MyFirstFontStyle' />
                </application>
              </styles>
        ";
        
        echo "</chart>";
    }
    
    // 입고, 배송 평균 구하기
    function get_stock_average()
    {
        global $connect, $product_id, $start_date, $end_date;

        // 평균입고, 평균배송
        $query = "select sum(trans) as trans,
                         sum(stockin) as stockin
                    from stock_tx 
                   where crdate     >= '$start_date' and
                         crdate     <= '$end_date' and
                         product_id = '$product_id' and
                         bad        = 0";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc($result);
        
        // 평균발주
        $query_order = "select sum(b.qty) as b_qty
                    from orders a, order_products b
                   where a.seq = b.order_seq and
                         a.collect_date >= '$start_date' and
                         a.collect_date <= '$end_date' and
                         b.product_id = '$product_id' and
                         b.order_cs not in (1,2)";
debug( $query_order );                         
        $result_order = mysql_query( $query_order, $connect );
        $data_order = mysql_fetch_assoc($result_order);
        
        // 날짜 차이 구하기
        $arr_start = explode('-', $start_date);
        $arr_end = explode('-', $end_date);
        
        $start_time = mktime(0,0,0,$arr_start[1],$arr_start[2],$arr_start[0]);
        $end_time = mktime(0,0,0,$arr_end[1],$arr_end[2],$arr_end[0]);
        
        $date_sub = NUMBER_FORMAT(intval(($end_time-$start_time)/86400)) + 1;
        
        $val['stockin'] = NUMBER_FORMAT($data[stockin] / $date_sub);
        $val['trans'] = NUMBER_FORMAT($data[trans] / $date_sub);
        $val['order'] = NUMBER_FORMAT($data_order[b_qty] / $date_sub);
        
        echo json_encode($val);
    }

    // 입고요청 추가
    function add_input_plan()
    {
        global $connect, $product_id, $qty;
        
        // 이미 있는지 확인
        $query = "select * from stockin_req 
                   where crdate = date(now()) and 
                         product_id='$product_id'";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
        {
            // 업데이트
            $query = "update stockin_req
                         set qty = qty + $qty
                       where crdate = date(now()) and
                             product_id='$product_id'";
        }
        else
        {
            // 추가하기
            $query = "insert stockin_req
                         set crdate = now(),
                             product_id = '$product_id',
                             qty = '$qty'";
        }
        mysql_query($query, $connect);
        
        // 추가된 값 구하기
        $query = "select qty from stockin_req where crdate = date(now()) and product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $val = array();
        $val['data'] = $data[qty];
        
        echo json_encode( $val );
    }

    // 가입고 재고 초기화 : realcoco 전용
    function reset_bad_stock()
    {
        global $connect;
        
        $query = "update current_stock set stock=0 where bad=1";
        mysql_query($query, $connect);
debug( "가입고재고 초기화 : " . $query );
    }
    
    // 재고작업 팝업에서 품절처리
    function set_soldout_product()
    {
        global $connect, $product_id, $soldout;
        
        $mode = ($soldout=="true" ? 0 : 1);
        $query = "update products set enable_sale=$mode " . ($mode ? "" : ", sale_stop_date=now()") . " where product_id='$product_id'";
debug("재고작업 팝업  체크박스 : " . $query);
        mysql_query($query, $connect);
        
        // 대표상품 부분품절체크
        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $org_id = $data[org_id];
        
        $_en = false;
        $_dis = false;
        $query = "select enable_sale from products where org_id='$org_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[enable_sale] )  
                $_en = true;
            else
                $_dis = true;
        }
        
        // 부분품절
        if( $_en && $_dis )
            $_e = 2;
        // 판매가능
        else if( $_en && !$_dis )
            $_e = 1;
        // 품절
        else
            $_e = 0;
            
        $query = "update products set enable_sale=$_e where product_id='$org_id'";
        mysql_query($query, $connect);
        
        // products history
        class_C::insert_products_history($product_id, ($mode ? "판매가능" : "품절"), "재고작업팝업에서 품절체크박스");
    }
    // 재고작업 팝업에서 전체품절처리
    function set_soldout_product_all()
    {
        global $connect, $product_id, $soldout;
        
        $mode = ($soldout=="true" ? 0 : 1);
        
        // 대표상품 구하기
        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $org_id = $data[org_id];
        
        if( $org_id > '' ) 
            $where_str = " product_id = '$org_id' or org_id='$org_id'";
        else
            $where_str = " product_id = '$product_id'";
        
        $query = "update products set enable_sale=$mode " . ($mode ? "" : ", sale_stop_date=now()") . " where " . $where_str;
debug("재고작업 팝업 전체품절  체크박스 : " . $query);
        mysql_query($query, $connect);


		$query = "SELECT product_id FROM products WHERE " . $where_str;
		$result = mysql_query($query, $connect);
		while($data = mysql_fetch_assoc($result))
		{
			// products history			
			class_C::insert_products_history($data[product_id], ($mode ? "판매가능" : "품절"), "재고작업팝업에서 전체품절체크박스");
		}
    }
}
?>
