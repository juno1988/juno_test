<?
//====================================
//
// name: class_KB00
// date: 2007.11.10 - jk
//
require_once "class_top.php";
require_once "class_file.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_3pl_api.php";
require_once "class_ui.php";

class class_KB00 extends class_top {

    var $m_items = "";
    function class_KB00()
    {
        $this->m_items = array (
                "supply_code"  => "",
                "product_id"   => "",
                "use_3pl"      => "",
                "name"         => "like",
                "options"      => "like",
        );
    }

    function KG01()
    {
        global $template, $connect;
        $start_date = date('Y-m-d', strtotime("today"));
        $end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/KG01.htm";
    }


    function KB00()
    {
        global $template, $connect;
        $start_date = date('Y-m-d', strtotime("today"));
        $end_date   = date('Y-m-d', strtotime("today"));

        include "template/K/KB00.htm";
    }

    function confirm_stockin()
    {
        global $product_id, $qty, $connect;
        $today = date('Y-m-d', strtotime("today"));

        if ( $qty ){
            $query = "select qty from stockin_req where product_id='$product_id' and crdate='$today'";
            $result = mysql_query ( $query, $connect );
            $data   = mysql_fetch_array ( $result );
        
            if ( $data[qty] )
            {
                $qty = $data[qty] + $qty;
                $query = "update stockin_req set qty=$qty 
                           where product_id='$product_id' and crdate='$today'";
                mysql_query ( $query, $connect );
            }
            else
            {
                $query = "insert into stockin_req set qty=$qty, product_id='$product_id', crdate=Now()";
                mysql_query ( $query, $connect );
            }
        }

        $val = array();
        $val[crdate] = $today;
        $val[qty]    = $qty;

        if ( mysql_affected_rows() != -1 )
            $val[result] = "ok";
        else
            $val[result] = "fail";

        echo json_encode( $val );
    }


    //======================================
    // file upload후 작업
    // 상품 정보를 excel의 내용으로 update함
    // date: 2007.11.21 - jk
    function upload()
    {
        $this->show_wait();

        global $connect, $_file, $top_url;
        $obj = new class_file();
        $arr_result = $obj->upload();

        $total_rows = sizeof ( $arr_result );
        $obj = new class_product();

        $rows = 0;
        foreach ( $arr_result as $row )
        {
            $rows++;
            if ( $rows == 1 ) continue;
 
            $infos[product_id_3pl] = $row[0];
            $infos[product_id]            = $row[1];
            $infos[barcode]            = $row[2];
            $infos[name]                  = $row[3];
            $infos[options]        = $row[4];
            $infos[supply_code]    = $row[5];
            $infos[enable_sale]    = $row[6];
            $infos[use_3pl]        = $row[7];

            ///////////////////////////////
            // sync product 
            $obj->sync_product( $infos, $row[0] );

            $str = "${rows} / ${total_rows}번째 작업중입니다.";
            echo "<script>show_txt('$str');</script>";
            flush();
        }

        $this->hide_wait();
        $this->jsAlert ( "변경: $rows개의 작업 완료" );

        $this->redirect ("?". base64_decode ( $top_url ) );
        exit;
    }

    //===============================
    // sync작업 수행
    // date: 2007.11.21 - jk
    function do_sync()
    {
        global $top_url;

        $this->show_wait();
        $obj     = new class_product();
        $obj_3pl = new class_3pl();

        ////////////////////////////////////////////////////////
        // 3pl을 사용하며 삭제되지 않은 상품
        $arr_items = array ( "use_3pl" => 1, "is_delete" => "zero" );
        $tot_rows  = $obj->get_count ( $arr_items );
        $obj->get_list( $arr_items );

        $_tot_cnt  = 0;
        $_update   = 0;
        $_reg      = 0;
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $_product_id = $data[product_id_3pl] ? $data[product_id_3pl] : $data[product_id];        

            //////////////////////////////////////
            // 1. is_reg_product 인지 확인해서
            if ( $obj_3pl->check_reg ( $_product_id ) )
            {
                $_tot_cnt++;
                $_update++;
                // 있을 경우 update
                // echo "있음 : $data[product_id_3pl] / $data[product_id] <br>";
                $obj_3pl->_update( $data, $_product_id );
            }
            else
            {
                $_tot_cnt++;
                $_reg++;
                // 없을 경우 do_reg
                // echo "없음 : $data[product_id_3pl] / $data[product_id] <br>";
                $obj_3pl->product_reg( $data[product_id], $data );
            }

            //////////////////////////////////////
            $msg = " $i / $tot_rows 작업중";        
            $this->show_txt ( $msg );
              $i++;
        }        
        $this->hide_wait();
        echo "\n\n";
        $this->jsAlert ( " 변경: $_update 등록: $_reg 총: $tot_rows 개의 작업 완료 ");
        $this->redirect( "?template=K902&top_url=$top_url" );
    }

    // chart를 그리기 위한 재고 이력 조회
    function get_stock_history()
    {
        global $connect, $product_id, $start_date, $end_date;
        
        echo "<chart caption='재고' yAxisName='수량' bgColor='F7F7F7, E9E9E9' showValues='0' numVDivLines='10' divLineAlpha='30'  labelPadding ='10' yAxisValuesPadding ='10'>";


        //=====================================================        
        //
        // date 부분 category 생성
        //
        $_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);
        $_start    = round( abs(strtotime(date('y-m-d'))-strtotime($end_date)) / 86400, 0 );
        $_interval = $_start + $_interval;

        echo "<categories>";
        if ( $_interval >= 0 )
            {
            for ( $i = $_interval; $i >= $_start; $i-- )
            {        
                $_date = date('Y-m-d', strtotime("-$i day"));
                echo "<category label='$_date' />\n ";
            }
        }
        echo "</categories>";

        //////////////////////////////////////////////////////////
        // 재고 data 생성
        $obj          = new class_3pl();
        $result = $obj->get_stock_history( $product_id, $start_date, $end_date );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[crdate]] = $data[qty];
        }

        echo "<dataset seriesName='재고' color='A66EDD' >\n";
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

        ////////////////////////////////////////////////////////////////
        //
        // 판매 data
        //
        $query = "select date_format(trans_date_pos,'%Y-%m-%d') pos_date, count(*) qty 
                    from orders                                 
                   where trans_date_pos >= '$start_date 00:00:00'
                     and trans_date_pos <= '$end_date 23:59:59'
                     and product_id='$product_id'                  
                     and status=8
                   group by date_format(trans_date_pos,'%Y-%m-%d') ";

        $result = mysql_query ( $query, $connect );
        $dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[pos_date]] = $data[qty];
        }

        echo "<dataset seriesName='배송' color='FF0000'>\n";
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


        /////////////////////////////////////////////////////
        // 입고
        $result = $obj->get_stock_in_history( $product_id, $start_date, $end_date );
        $dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[crdate]] = $data[qty];
        }

        echo "<dataset seriesName='입고' color='F99998'>\n";
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

        ////////////////////////////////////////////////////////////////
        //
        // 미배송 data
        //
        $query = "select collect_date, count(*) qty 
                    from orders                                 
                   where collect_date >= '$start_date'
                     and collect_date <= '$end_date'
                     and product_id='$product_id'                  
                     and status in (1,2,11 )                  
                     and order_cs not in (1,2,3,4,12 )                  
                   group by collect_date";

        $result = mysql_query ( $query, $connect );
        $sum = 0;
        $dataset = "";
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $dataset[$data[collect_date]] = $data[qty];
        }

        $sum = 0;
        echo "<dataset seriesName='미배송 합계' color='F6BD0F'>\n";
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
?>
</chart>
<?
    }

    //================================
    //
    // 상품 조회
    // 2007.11.20
    //
    function query()
    {
        global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date, $chk_nottrans;

        $product_arr = array();
        $arr_return = array();
        $this->get_list( &$arr_return );
        while ( $data = mysql_fetch_array($arr_return['result']) )
            $product_arr[] = $data['product_id'];

        $obj = new class_3pl();

        $val['error'] = 0;
        $val['total_rows'] = $arr_return['total_rows'];

        $val['list'] = array();
        $stock = $obj->get_stock_total( $product_arr );
        foreach( $stock as $id => $stc )
        {
            $obj_product = new class_product();
            $data = $obj_product->get_info( $id );
    
            $val['list'][] = array(
                product_id          => $id,
                product_name        => iconv("CP949", "UTF-8", $data[name]      ),
                options             => iconv("CP949", "UTF-8", $data[options]   ),
                price               => iconv("CP949", "UTF-8", $data[org_price] ),
                stock_1             => $stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y],
                stock_1_bad         => $stc[in_bad_y] - $stc[out_bad_y] - $stc[tr_bad_y],
                stock_in            => $stc[in_sum_t],
                stock_in_ret        => $stc[in_ret_t],
                stock_in_bad        => $stc[in_bad_t],
                stock_out           => $stc[out_sum_t],
                stock_out_ret       => $stc[out_ret_t],
                stock_out_bad       => $stc[out_bad_t],
                trans               => $stc[tr_sum_t],
                stock               => ($stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y]) + ($stc[in_sum_t] - $stc[out_sum_t] - $stc[tr_sum_t]),
                trans_exp           => $stc[tr_sum_w],
                stock_exp           => ($stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y]) + ($stc[in_sum_t] - $stc[out_sum_t] - $stc[tr_sum_t]) - $stc[tr_sum_w],
                trans_wait          => $stc[tr_sum_n],
                stock_bad           => ($stc[in_bad_y] - $stc[out_bad_y] - $stc[tr_bad_y]) + ($stc[in_bad_t] - $stc[out_bad_t] - $stc[tr_bad_t]),
                stock_in_all        => $stc[in_sum_y] + $stc[in_sum_t],
                stock_in_ret_all    => $stc[in_ret_y] + $stc[in_ret_t],
                stock_in_bad_all    => $stc[in_bad_y] + $stc[in_bad_t],
                stock_out_all       => $stc[out_sum_y] + $stc[out_sum_t],
                stock_out_ret_all   => $stc[out_ret_y] + $stc[out_ret_t],
                stock_out_bad_all   => $stc[out_bad_y] + $stc[out_bad_t],
                trans_all           => $stc[tr_sum_y] + $stc[tr_sum_t]
            );
        }
        
        echo json_encode( $val );
    }

    //=====================================
    // new download logic
    // 2009.2.2 - jk
    function save_file( $domain )
    {
        global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date, $chk_nottrans;
        global $user_id;

        //echo "userid: $user_id\n";
        //exit;

        $arr_return = array();        // order 정보 저장
        $arr_datas  = array();        // save 해야할 data저장
        $obj_file   = new class_file();
        $obj        = new class_3pl();

        // 미배송 조회
        if ( $chk_nottrans )
            $obj->not_trans_list( &$arr_return ,"no limit" );
        else
            $this->get_list( &$arr_return,"no limit" );

        $result = $arr_return[result];

        $arr_field = array ( 
                "product_id" => "상품번호",
                "name"             => "상품명",
                "options"    => "옵션",
                "price"    => "가격",
                "stock"             => "재고",
                "qty"             => "배송요청"
        );

        $_row = array();
        foreach( $arr_field as $key=>$title )
        {
            $_row[] = $title;
        }
        $arr_datas[] = $_row;

        //////////////////////////////////////////////////
        // download받을 data생성
        $obj = new class_3pl_api();
        $j   = 0;

        while ( $data = mysql_fetch_array ( $result ) )
        {
            $i    = 0;
            $j++;
            $_row = array();

            // test
            // if ( $j == 4 ) break;

            // 상품가격
            if ( $chk_nottrans )
            {
                $query_price = "select org_price from products where product_id='$data[product_id]'";
                $result_price = mysql_query($query_price, $connect);
                $data_price = mysql_fetch_array($result_price);
                
                $price = $data_price[org_price];
            }
            else
                $price = $data[org_price];

            foreach( $arr_field as $key=>$title )
            {
                if ( $key == "stock" )
                {
                    $_str  = $obj->batch_current_stock3 ( $domain, $data[product_id] );
                    $_row[] = $_str;
                }
                else if( $key == "price" )
                {
                    $_row[] = $price;
                }
                else
                    $_row[] = $data[$key];

                sleep( 0.2 );
                echo "#";
            }
            $arr_datas[] = $_row;
        }

        return $obj_file->save_file( $arr_datas, "$user_id/stock_list.xls" );
    }

    //=====================================
    // download2 
    // 2008.3.20 - jk
    function download2()
    {
        global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date, $chk_nottrans;

        $arr_return = array();        // order 정보 저장
        $arr_datas  = array();        // save 해야할 data저장
        $obj_file   = new class_file();
        $obj        = new class_3pl();

        $this->get_list( &$arr_return, 'xls_limit' );
        while ( $data = mysql_fetch_array($arr_return['result']) )
            $product_arr[] = $data['product_id'];

        $arr_field = array ( 
            product_id        => '상품코드'     ,
            product_name      => '상품명'       ,
            options           => '옵션'         ,
            price             => '원가'         ,
            stock_1           => '전일재고'     ,
            stock_1_bad       => '전일불량재고' ,
            stock_in          => '금일입고'     ,
            stock_in_ret      => '금일반품입고' ,
            stock_in_bad      => '금일불량입고' ,
            stock_out         => '금일출고'     ,
            stock_out_ret     => '금일반품출고' ,
            stock_out_bad     => '금일불량출고' ,
            trans             => '금일배송'     ,
            stock             => '금일재고'     ,
            trans_exp         => '배송예정'     ,
            stock_exp         => '가재고'       ,
            trans_wait        => '미배송'       ,
            stock_bad         => '금일불량재고' ,
            stock_in_all      => '입고누계'     ,
            stock_in_ret_all  => '반품입고누계' ,
            stock_in_bad_all  => '불량입고누계' ,
            stock_out_all     => '출고누계'     ,
            stock_out_ret_all => '반품출고누계' ,
            stock_out_bad_all => '불량출고누계' ,
            trans_all         => '배송누계'     
        );

        // 헤더 행
        $_row = array();
        foreach( $arr_field as $key=>$title )
            $_row[] = $title;

        // 전체 데이터에 헤더 정보 넣음
        $arr_datas[] = $_row;

        // 전체 데이터 구하기
        $obj = new class_3pl();
        $stock = $obj->get_stock_total( $product_arr );
        $product_info = $this->get_product_info( $product_arr );
        foreach( $stock as $id => $stc )
        {
            $arr_datas[] = array(
                $id,                                                                                                                        // product_id       
                $product_info[$id][name],                                                                                                   // product_name     
                $product_info[$id][options],                                                                                                // options          
                $product_info[$id][org_price],                                                                                              // price            
                $stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y],                                                                          // stock_1          
                $stc[in_bad_y] - $stc[out_bad_y] - $stc[tr_bad_y],                                                                          // stock_1_bad      
                $stc[in_sum_t],                                                                                                             // stock_in         
                $stc[in_ret_t],                                                                                                             // stock_in_ret     
                $stc[in_bad_t],                                                                                                             // stock_in_bad     
                $stc[out_sum_t],                                                                                                            // stock_out        
                $stc[out_ret_t],                                                                                                            // stock_out_ret    
                $stc[out_bad_t],                                                                                                            // stock_out_bad    
                $stc[tr_sum_t],                                                                                                             // trans            
                ($stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y]) + ($stc[in_sum_t] - $stc[out_sum_t] - $stc[tr_sum_t]),                  // stock            
                $stc[tr_sum_w],                                                                                                             // trans_exp        
                ($stc[in_sum_y] - $stc[out_sum_y] - $stc[tr_sum_y]) + ($stc[in_sum_t] - $stc[out_sum_t] - $stc[tr_sum_t]) - $stc[tr_sum_w], // stock_exp        
                $stc[tr_sum_n],                                                                                                             // trans_wait       
                ($stc[in_bad_y] - $stc[out_bad_y] - $stc[tr_bad_y]) + ($stc[in_bad_t] - $stc[out_bad_t] - $stc[tr_bad_t]),                  // stock_bad        
                $stc[in_sum_y] + $stc[in_sum_t],                                                                                            // stock_in_all     
                $stc[in_ret_y] + $stc[in_ret_t],                                                                                            // stock_in_ret_all 
                $stc[in_bad_y] + $stc[in_bad_t],                                                                                            // stock_in_bad_all 
                $stc[out_sum_y] + $stc[out_sum_t],                                                                                          // stock_out_all    
                $stc[out_ret_y] + $stc[out_ret_t],                                                                                          // stock_out_ret_all
                $stc[out_bad_y] + $stc[out_bad_t],                                                                                          // stock_out_bad_all
                $stc[tr_sum_y] + $stc[tr_sum_t]                                                                                             // trans_all        
            );
        }

        $obj_file->download( $arr_datas );
    }
    
    function get_product_info( $product_arr )
    {
        global $connect;
        
        $info = array();
        
        $product_list = '';
        foreach( $product_arr as $prd )
            $product_list .= ($product_list?',':'') . "'$prd'";
            
        $query = "select product_id, name, options, org_price from products where product_id in ($product_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_array($result) )
        {
            $info[$data[product_id]]['name'] = $data[name];
            $info[$data[product_id]]['options'] = $data[options];
            $info[$data[product_id]]['org_price'] = $data[org_price];
        }
        return $info;
    }

    ////////////////////////////////////////
    // 상품의 상세 정보 출력
    // 2008.3.14 - jk
    function get_detail()
    {
        global $product_id, $connect;
        $val         = array();

        $query = "select crdate,qty from stockin_req where product_id='$product_id' order by crdate desc limit 1";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array( $result );
        $val['last_stockin_req']   = $data[crdate];
        $val['last_stockin_qty']   = $data[qty];


        $obj_product = new class_product();
        $data        = $obj_product->get_info( $product_id );

        $val['product_id']   = $data[product_id];
        $val['name']         = iconv("CP949", "UTF-8", $data[name] );
        $val['options']      = iconv("CP949", "UTF-8", $data[options] );
        $val['supply_name']  = iconv("CP949", "UTF-8", $data[supply_name] );
        $val['org_price']    = iconv("CP949", "UTF-8", $data[org_price] );
        $val['supply_price'] = iconv("CP949", "UTF-8", $data[supply_price] );
        $val['shop_price']   = iconv("CP949", "UTF-8", $data[shop_price] );
        $val['barcod3']      = iconv("CP949", "UTF-8", $data[barcode] );

        echo json_encode( $val );
    }


    //=====================================
    // 상품 관련 조회 리스트
    // 2007.11.21 - jk
    function get_list( &$arr_return, $_flag="limit" )
    {
        global $connect, $page, $use_3pl;
        global $template, $connect, $name, $supply_code, $options, $product_id, $start_date, $end_date;
        $name    = iconv("UTF-8", "CP949", $name );
        $options = iconv("UTF-8", "CP949", $options );

        $page = $page ? $page : 1;
        $_starter = ($page - 1) * 20;

        ///////////////////////////////////////////////////////////
        // 데이터는 값이 있다고 가정함
        // 재고 값(Logic 1)과 배송 개수(Logic 2)가 값이 없을 경우에는 is_nodata=1
        $is_nodata = 0;

        /////////////////////////////////////////////////////////

        $option = "where is_delete = 0 and org_id<>''";
        if ( $name )
            $option .= " and name like '%$name%'";

        // 옵션 값이 있을 경우 
        if ( $options )
            $option .= " and options like '%$options%'";

        // 공급업체 코드가 있는 경우
        if ( $supply_code )
            $option .= " and supply_code = '$supply_code'";

        // 상품 코드 리스트 값이 있는 경우
        if ( $product_id )
            $option .= " and product_id = '$product_id'"; 
        
        //////////////////////////////////////////////////////////
        // count 
        $query  = "select count(*) cnt from products " . $option;
        $result    = mysql_query ( $query, $connect );
        $data      = mysql_fetch_array( $result );
        $arr_return['total_rows'] = $data[cnt];

        ///////////////////////////////////////////////////////////
        $option .= " order by product_id ";
        if ( $_flag == "limit" )
        {
            global $start;
            $start = $start ? $start : 0;                
            $option .= " limit $start, 20";
        }  
              
        if( $_flag == "xls_limit" )
            $option .= " limit 10000 ";

        // 실제 상품 정보 query하는 부분
        $query  = "select * from products " . $option;
        $arr_return['result'] = mysql_query( $query, $connect );
    }

    //========================================
    // 3pl 상품의 개수
    function get_count_3pl()
    {
        $obj = new class_product();

        // 조건
        $arr_items = array ( "use_3pl" => 1 );
        return $obj->get_count( $arr_items );
    }

    //==================================
    // 3pl에서 관리되고 있는 상품의 개수
    function get_count_3pl_manage()
    {
        $obj = new class_3pl();

        $arr_items = array ( "domain" => _DOMAIN_ );
        return $obj->product_count( $arr_items );
    }

    //=====================================
    // 개수
    function get_count()
    {
        global $connect;
        
        $query  = "select count(*) cnt from products";
        $query .= $this->build_option( $this->m_items );        
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data[cnt];
    }

}

?>
