<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_lock.php";

//////////////////////////////////////////////
// get_list : 상품 리스트
// get_detail : 상품 상세 정보

class class_I200 extends class_top
{
    //////////////////////////////////////////////////////
    // 상품 리스트 
    function I200_org()
    {
        global $template;
        $link_url = "?" . $this->build_link_url();     
        
        // 판매처별 상품 리스트를 가져온다 
        if ( $_REQUEST["page"] )
        {
         // 재고 처리
         $stock_option = 1;
         $result = class_C::get_product_supply_list( &$total_rows, $stock_option );
         // $result = class_C::get_list( &$total_rows, 1 );
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // 상품 리스트 
    function I200()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //////////////////////////////////////////////////////
    // _dummy 리스트 
    function I202()
    {
        global $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
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
        $row_num = 0;
        
        
        $culumn = array();
        $culumn["product_id"] = -1;
        $culumn["stock"		] = -1;
        $culumn["command"	] = -1;
        $culumn["memo"		] = -1;
        $culumn["sheet"		] = -1;
        
        
        foreach ($arr_data as $data )
        {
        	if($row_num == 0)
        	{
        		for($i = 0; $i < count($data) ; $i++)
        		{
        			switch($data[$i])
        			{
        				case "상품코드":
        					$culumn["product_id"] = $i;
        				break;	
        				case "작업수량":
        					$culumn["stock"		] = $i;
        				break;
        				case "작업":
        					$culumn["command"	] = $i;
        				break;
        				case "메모":
        					$culumn["memo"		] = $i;
        				break;
        				case "전표번호":
        					$culumn["sheet"		] = $i;
        				break;
        			}
        		}
        		if($culumn["product_id"	] < 0)
        		{
        			$culumn["product_id"	] = 1;
        			
        			//echo "
			        //	<script language='javascript'>
			        //		alert('상품코드 항목은 필수입니다.');
			        //	</script>";
			        //break;
        		}
        		if( $culumn["stock"	] < 0)
        		{
        			$culumn["stock"	] = 8;
					//echo "
			        //	<script language='javascript'>
			        //		alert('작업수량 항목은 필수입니다.');
			        // 	</script>";
			        //break;
        		}
        		if($culumn["command"	] < 0)
        		{
        			$culumn["command"	] = 9;
        			//echo "
			        //	<script language='javascript'>
			        //		alert('작업 항목은 필수입니다.');
			        //	</script>";
			        //break;
        		}
        		if($culumn["memo"	] < 0)
        		{
        			$culumn["memo"	] = 10;
        		}
        		if($culumn["sheet"	] < 0)
        		{
        			$culumn["sheet"	] = 11;
        		}
        		//print_r($culumn);
        	}
        	if (( $data[$culumn["stock"]] > 0 || $data[$culumn["stock"]] < 0 || strtoupper($data[$culumn["stock"]]) == 'ZERO' ) && $row_num > 0)
            {
                $_data = array(); 
            	$_data["product_id"	]	= trim($data[$culumn["product_id"]]);
            	$_data["stock"		]	= ( strtoupper($data[$culumn["stock"]]) == 'ZERO' ? 0 : $data[$culumn["stock"]] );
            	$_data["command"	]	= trim($data[$culumn["command"]]);
                $_data["memo"		]	= trim($data[$culumn["memo"]]);
                $_data["sheet"		]	= trim($data[$culumn["sheet"]]);
                //print_r($_data);
                class_stock::insert_template( $_data );
            }
            $row_num++;
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
        global $connect;
        
        // Lock Check
        $obj_lock = new class_lock(202);
        if( !$obj_lock->set_start(&$msg) )
        {
            echo "
            <script language='javascript'>
                parent.alert('$msg');
                parent.hide_waiting();
                parent.dup_apply_check = 0;
            </script>
            ";  
            return;
        }

        //#######################
        // 서버로드 체크 start
        //#######################
        $svr_load_start = time();


        $arr_data = class_stock::get_stock_template();
        
        $i = 0;
        $obj = new class_stock();
        
        foreach ( $arr_data['list'] as $data )
        {
            $command = mb_convert_case( $data['command'], MB_CASE_UPPER, "UTF-8");

            // command의 첫글자가 B 이면 불량
            if( substr($command,0,1) == 'B' )
                $bad = 1;
            else
                $bad = 0;
            $current_stock = class_stock::get_current_stock($data[product_id], $bad);

            switch( $command )
            {
                case "A":  
                case "BA":  
                    $cmd = "in"; 
                    $stock = $data[stock];
                    break;
                case "D":  
                case "BD":  
                    $cmd = "out"; 
                    $stock = $data[stock];
                    break;
                case "M":  
                case "BM":  
                    $cmd = "arrange"; 
                    $stock = $data[stock];
                    break;
                case "P":  
                case "BP":  
                    $cmd = "arrange"; 
                    $stock = $current_stock + $data[stock];
                    break;
                case "RA":  
                case "BRA":  
                    $cmd = "retin"; 
                    $stock = $data[stock];
                    break;
                case "RD":  
                case "BRD":  
                    $cmd = "retout"; 
                    $stock = $data[stock];
                    break;
                default: 
                    continue 2;
            }

            if( $data[sheet] && $_SESSION[MULTI_WH] )
            {
                // 창고 구하기
                if( $cmd == 'in' )  
                    $sheet_table = 'sheet_in';
                else if( $cmd == 'out' )  
                    $sheet_table = 'sheet_out';
                else 
                    $sheet_table = '';

                if( $sheet_table )
                {
                    $query_wh = "select * from $sheet_table where seq=$data[sheet]";
                    $result_wh = mysql_query($query_wh, $connect);
                    $data_wh = mysql_fetch_assoc($result_wh);
                }

                if( $data_wh[wh] )
                {
                    $obj->set_stock_wh( array( type       => $cmd,
                                               product_id => $data[product_id],
                                               wh         => $data_wh[wh],
                                               bad        => $bad,
                                               location   => 'Def', 
                                               sheet      => $data[sheet], 
                                               qty        => $stock,
                                               worker     => $_SESSION[LOGIN_NAME],
                                               memo       => $data[memo]));
                }
            }
            else if( $data[sheet] && !$_SESSION[MULTI_WH] )
            {
                $info = array( 
                    type       => $cmd,
                    product_id => $data[product_id],
                    bad        => $bad,
                    location   => 'Def',
                    qty        => $stock,
                    memo       => $data[memo]
                );
                $obj->set_stock($info, $_SESSION[LOGIN_NAME], $data[sheet]);
            }
            else
            {
                $info = array( 
                    type       => $cmd,
                    product_id => $data[product_id],
                    bad        => $bad,
                    location   => 'Def',
                    qty        => $stock,
                    memo       => $data[memo]
                );
                $obj->set_stock($info);
            }
            
            $i++;
            echo "
            <script language='javascript'>
            parent.show_txt('$i/" . $arr_data['cnt'] . " - " . $data['command'] . "/" . $data['stock'] . "')
            </script>
            ";  
            
            usleep(1000);
        }   
        
        // 재고 테이블 초기화
        class_stock::clear_template();
        
        //#######################
        // 서버로드 체크 log
        //#######################
        $this->svr_load_log($svr_load_start, "재고조정");

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            echo "
            <script language='javascript'>
                parent.alert('$msg');
                parent.hide_waiting();   
            </script>
            ";       
        }
        else
        {
            echo "
            <script language='javascript'>
            parent.apply_end();
            </script>
            ";       
        }
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
            $prd_info = class_product::get_info($data[product_id]);
            $command = strtoupper($data[command]);

            // command의 첫글자가 B 이면 불량
            if( substr($command,0,1) == 'B' )
                $bad = 1;
            else
                $bad = 0;

            $current_stock = class_stock::get_current_stock($data[product_id],$bad);
            switch( $command )
            {
                case "A":
                case "RA":
                case "P":
                case "BA":
                case "BRA":
                case "BP":
                    $after_stock = $current_stock + $data[stock];
                    break;
                case "M":
                case "BM":
                    $after_stock = $data[stock];
                    break;
                case "D":
                case "RD":
                case "BD":
                case "BRD":
                    $after_stock = $current_stock - $data[stock];
                    break;
                default:
                    continue 2;
            }
            
            $arr_data['list'][] = array(
                product_id    => $data[product_id],
                name          => $prd_info[name], 
                options       => $prd_info[options],
                current_stock => $current_stock,
                stock         => $data[stock],   
                after_stock   => $after_stock,   
                command       => $data[command],
                memo          => $data[memo]
            );
        }
        echo json_encode($arr_data);
    }

    // 하위 상품 목록
    function expand()
    {
        global $connect,$product_id;
        $obj = new class_stock();
        
        $query = "select * from products where org_id='$product_id'";
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
            );
        }
        echo json_encode($arr_data);
    }

    //******************************
    // download
    function download()
    {
        global $template, $action,$connect,$string,$string_type,$org_product;
        
        // get list from common module
        $is_download = 1;
        $result = $this->get_list( &$total_rows, $is_download );
        
        $obj = new class_stock();     
        $_arr[] = array(
            "공급처"
            ,"상품코드"
            ,"상품명"
            ,"옵션"
            ,"전일재고"
            ,"금일재고"
            ,"금일입고"
            ,"금일출고"
            ,"금일배송"
            ,"원가"
            ,"재고수정"
            ,"작업(A/M/D)"
        );
                   
        while ( $data = mysql_fetch_array( $result ) )
        {   
            $i++;
            $stock_info = $obj->get_stock( $data[product_id] );
            $supply_name = $this->get_supply_name2( $data[supply_code] );
            
            $_arr[] = array( 
                 $supply_name
                ,$data[product_id]
                ,$data[name]
                ,$data[options]
                ,$stock_info[yesterday_stock]
                ,$stock_info[stock]                
                ,$stock_info[in]
                ,$stock_info[out]
                ,$stock_info[trans]
                ,$data[org_price]
            );
            
            if ( $i % 100 == 0)
            {
                echo "
                <script language='javascript'>
                parent.show_txt( $i )
                </script>
                ";
                flush();
            }
        }
        
        $obj_file = new class_file();
        $file_name = $_SESSION[LOGIN_ID];
        $obj_file->save_file( $_arr, $file_name );
        
        echo "
        <script language='javascript'>
        parent.download_ready( '$file_name' )
        </script>
        ";    
    }
    
    // 실제 download 시작
    function download_file()
    {
        global $file,$name;
        
        $obj = new class_file();
        $obj->download_file( $file, $name );   
    }

    // get list
    function get_list( &$total_rows, $is_download=0 )
    {
        global $template, $action,$connect,$string,$string_type,$org_product,$supply_code,$page,$is_stock;        
        $product_ids = "";
        
        $query     = "select distinct(b.product_id) ,b.name,b.options,b.org_price,b.supply_code,b.org_id";
        $query_cnt = "select count(distinct(b.product_id)) cnt";
        
        // 조건
        $is_where = 0;
        if ( $is_stock )
        {
            $condition = " from current_stock a 
                      left join products b on a.product_id=b.product_id ";
        }
        else
        {
            $condition = " from products b 
                      left join current_stock a on a.product_id = b.product_id";
            //            where b.org_id <> '' ";
            //$is_where = 1;
        }
        
        // 공급처
        
        $condition .= $is_where ? " and " : " where ";
        $condition .= " (( b.stock_manage = 1 and substr(b.product_id,1,1) = 'S') or (b.stock_manage <> 1 and substr(b.product_id,1,1) <> 'S')) ";
        $is_where   = 1;
        
        
        if ( $string )
        {
            $condition .= $is_where ? " and " : " where ";
            $is_where   = 1;
            
            $condition .= " b.${string_type} ";
            
            if ( $string_type == "name" )
                $condition .= " like '%$string%'";
            else
                $condition .= " = '$string'";
        }
        
        // 공급처
        if ( $supply_code )
        {
            $condition .= $is_where ? " and " : " where ";
            $condition .= " b.supply_code=$supply_code";
            $is_where   = 1;
        }
        
        //***********************
        // page index
        // download가 아닐경우만 처리 되는 부분
        if ( !$is_download )
        {
            $page  = $page ? $page : 1;
            $limit = 30;
            $start = ($page - 1) * $limit;
            $condition_page = " order by name limit $start, $limit";
        
            //***********************
            // count
            $query_cnt  = $query_cnt . $condition;
            
            $result     = mysql_query( $query_cnt, $connect );
            $data       = mysql_fetch_assoc( $result );
            $total_rows = $data['cnt'];
        }   
        
        //***********************
        // data
        $query = $query . $condition . $condition_page;
        echo "query: " . $query;
        
        $result = mysql_query( $query, $connect );
        return $result;
    }

    // search
    // 2009.7.27
    function search()
    {
        global $template, $action,$connect,$string,$string_type,$org_product;
        
        // 공통 모듈에서 data 가져온다.
        $total_rows = 0;
        $result = $this->get_list( &$total_rows );
        
        if ( !mysql_num_rows( $result ) )
        {
            global $connect;
            $query = "select * from products 
                    where (( b.stock_manage = 1 and substr(b.product_id,1,1) = 'S') or (b.stock_manage <> 1 and substr(b.product_id,1,1) <> 'S')) ";
            
            if ( $string_type )
                $query .= $string_type . "='$string'";
            
            $result = mysql_query( $query, $connect );
            $total_rows = mysql_num_rows( $result );
        }
        
        $_arr   = array();
        $i      = 0;
        $_arr['total'] = $total_rows;
        $_arr['query'] = $query;
        
        $obj = new class_stock();        
        while ( $data = mysql_fetch_array( $result ) )
        {   
            $i++;
            if ( substr($data[product_id],0,1) <> 'S' )
                $data[options] = '';
            
            // org_product는 하부 상품에 대한 전체 재고를 구함.
            if ( $org_product )
            {
                //$stock_info = $obj->get_stock( $data[product_id],"parent" );
                $stock_info = array(stock => '', ready_stock=>'', yesterday_stock=>'');
            }
            else
                $stock_info = $obj->get_stock( $data[product_id] );
            
            $supply_name = $this->get_supply_name2( $data[supply_code] );
            
            $_arr['list'][] = array( 
                supply_name      => $supply_name
                ,product_id      => $data[product_id]
                ,product_name    => $data[name]
                ,options         => $data[options] ? $data[options] : "&nbsp;"
                ,stock           => $stock_info[stock]
                ,ready_stock     => $stock_info[ready_stock]
                ,yesterday_stock => $stock_info[yesterday_stock]
                ,stock_in        => $stock_info[in]
                ,stock_out       => $stock_info[out]
                ,trans_cnt       => $stock_info[trans]
                ,org_price       => $data[org_price]
            );
            
            if ( $i % 3 == 0)
            {
                echo "
                <script language='javascript'>
                parent.show_txt( $i )
                </script>
                ";
                flush();
            }
        }
        
        $_json         = json_encode( $_arr );
        
        echo "
        <script language='javascript'>
        parent.disp_rows( $_json )
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
    
    //*******************************
    // 입고
    // 
    function stock_in()
    {
        global $product_id,$qty;
        
        
        $obj = new class_stock();
        
        $obj->stock_in($product_id,$qty);
        
    }
    
    //*******************************
    // 출고
    // 
    function stock_out()
    {
        global $product_id,$qty;
        
        $obj = new class_stock();
        
        $obj->stock_out($product_id,$qty);
        
    }
    //*******************************
    // 조정
    // 
    function stock_arrange()
    {
        global $product_id,$qty;
        
        $obj = new class_stock();
        
        $obj->stock_arrange($product_id,$qty);
        
    }
    
    
}
?>
