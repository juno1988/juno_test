<?
require_once "class_top.php";
require_once "class_stock.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_CT00
//
// stat_upload_file : 업로드한 정산 파일에 대한 master정보
//      status: 1: 업로드 / 2: 오류 / 3: 완료
//
// stat_upload_data : 업로드한 정산 파일의 내용
// stat_upload_down: 
class class_CT00 extends class_top 
{ 
    var $m_file_seq = 0;
    // header 생성
    var $header_idx = array(
            "collect_date"      => "ez)발주일"
            ,"order_id"         => "ez)주문번호"
            ,"order_id_seq"     => "ez)부주문번호"    
            ,"shop_product_id"  => "ez)상품번호"
            ,"status"           => "ez)배송상태"
            ,"order_cs"         => "ez)CS상태"
            ,"shop_id"          => "ez)판매처"
            ,"seq"              => "ez)관리번호"
            ,"product_name"     => "ez)상품명"
            ,"product_options"  => "ez)옵션"
            ,"product_org_price" => "ez)원가합"
            ,"qty"               => "ez)개수"
        );
    var $arr_status = array(
            1 => "접수",
            7 => "송장",
            8 => "배송"
        );
        
    var $arr_cs = array(
            0 => "정상",
            1 => "취소",
            2 => "취소",
            3 => "취소",
            4 => "취소",
            5 => "교환",
            6 => "교환",
            7 => "교환",
            8 => "교환"
        );
    
    function CT00()
    {
        global $template, $start_date, $end_date, $shop_id, $page, $order_id, $search_all;
        
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function CT01()
    {
        global $template, $start_date, $end_date, $shop_id, $page, $order_id, $search_all;
        
        if( !$start_date )
            $start_date = date("Y-m-d", strtotime("-30 day") );
        
        $result = $this->get_list();
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function del()
    {
        global $connect, $seq;
        
        // stat_upload_file 삭제
        $query = "delete from stat_upload_file where seq=$seq";
        mysql_query( $query, $connect );
        
        // stat_upload_data 삭제
        $query = "delete from stat_upload_data where file_seq=$seq";
        mysql_query( $query, $connect );
        
        $this->redirect("template15.htm?template=CT00");        
    }

    // list
    function get_list()
    {
        global $connect;
        
        $_date  = date("Y-m-d", strtotime("-1 day") );
        $query  = "select seq from stat_upload_file where crdate <= '$_date 23:59:59'";
        $result = mysql_query( $query, $connect );
        $seqs   = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $seqs .= $seqs ? "," : "";
            $seqs .= $data[seq];
        }
        
        if ( $seqs != "" )
        {
            // stat_upload_file의 내용 삭제
            $query = "delete from stat_upload_file where seq in ( $seqs )";
            mysql_query( $query, $connect );
    
            // stat_upload_data의 내용 삭제
            $query = "delete from stat_upload_data where file_seq in ( $seqs )";
            mysql_query( $query, $connect );        
        }
        
        // list 조회
        $query  = "select * 
                     from stat_upload_file 
                    where crdate >= '" . Date("Y-m-d") . " 00:00:00' order by seq desc";
        $result = mysql_query( $query, $connect );
        
        // 
        return $result;
    }

    //
    // 기존 주문을 기준으로 주문 다운로드.
    // 2011.2.7 - jk
    function download_n()
    {
        global $connect, $shop_id, $date_type, $start_date, $end_date;
        
        $arr_data = array();
        
        // stat_upload_down 의 필드 값을 가져온다
        $arr_fields = $this->get_field();        
        $str_fields = "";
        foreach( $arr_fields as $_field )
        {
            if ( !preg_match(  "/product/", $_field ) )
            {
                $str_fields .= $str_fields ? "," : "";
                $str_fields .= $_field;
            }
        }
                
        // stat_upload_file 에서 key_order_id, key_order_id_seq, key_product_id 값을 알아온다
        // $arr_key    = $this->get_key( $shop_id );
        $query = "select $str_fields ,seq from orders 
                   where shop_id     = '$shop_id'
                     and $date_type >= '$start_date'
                     and $date_type <= '$end_date'
                     and substring( order_id,1,1) <> 'C'
                     order by order_id";
        
        $result  = mysql_query( $query, $connect );
        $tot_num = mysql_num_rows ( $result );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $_arr = array();
            
            // id     : 상품id
            // name   : 상품명
            // option : 옵션
            // qty    : 개수
            // price  : 합계 금액
            $_arr_order_product_info = $this->get_order_product_info( $data[seq] );
            //foreach ( $data as $_field => $_v )
            foreach ( $arr_fields as $_field )
            {
                //
                if ( $_field == "status" )
                {
                    $_arr[] = $this->arr_status[ $data[$_field] ];   
                }
                else if ( $_field == "order_cs" )
                {
                    $_arr[] = $this->arr_cs[ $data[$_field] ];   
                }
                else if ( $_field == "product_name" || $_field == "product_options" || $_field == "product_org_price" )
                {
                    
                    $_arr[] = $_arr_order_product_info[ $_field ];   
                }
                else
                {
                    //$_arr[] = $_v;  
                    $_arr[] = $data[$_field];  
                } 
            }
            
            // GS이샵의 경우..
            if ( $shop_id % 100 == 7 )
            {
                $_arr2 = split("\-", $data[order_id_seq] );
                $_order_id_seq = $_arr2[2];
            }
            else
            {
                $_order_id_seq = $data[order_id_seq];   
            }
            
            $_arr_source = $this->get_source( $shop_id, $data[order_id], $_order_id_seq, $data[shop_product_id]);
            $arr_data[]  = array_merge( $_arr, $_arr_source );
        }
                
        $arr_header = array();
        foreach( $arr_fields as $_field )
        {
            $arr_header[] = $this->header_idx[$_field];   
        }
        
        // data header 삽입
        $query = "select count(*) cnt,row 
                    from stat_upload_data 
                   where file_seq=" . $this->m_file_seq . " 
                     and row < 10 
                     and value <> '' group by row";
        $result = mysql_query( $query, $connect );
        $row_idx = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $data[cnt] > 5 )
            {
                $row_idx = $data[row];
                break;
            }
        }
        
        $query = "select value 
                    from stat_upload_data 
                   where row=$row_idx 
                     and file_seq=" . $this->m_file_seq . " order by col";
        
        
                             
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_header[] = $data[value];   
        }
        
        // data array의 상단에 삽입
        //$_arr_data = array();
        array_unshift( $arr_data, $arr_header );
        
        $obj_file = new class_file();
        $filename = $shop_id . "_정산결과.xls";
        $obj_file->download( $arr_data, "dn_" . iconv('utf-8','cp949',$filename) );
    }

    function get_order_product_info( $seq )
    {
        global $connect;
        
        $query = "select product_id,shop_id from order_products where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $ids = "";
        $shop_id = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $ids .= $ids ? "," : "";
            $ids .= "'$data[product_id]'";   
            
            $shop_id = $data[shop_id];
        }
        
        //
        $query = "select org_code,product_id,name,options from products where product_id in ( $ids )";        
        $result = mysql_query( $query, $connect );
        
        $name = "";
        $options = "";
        $org_price = 0;
        while ( $data =mysql_fetch_assoc( $result ) )
        {
            $name .= $name ? "," : "";
            $name .= $data[name];
            
            $options .= $options ? "," : "";
            $options .= $data[options];
            
            $arr_price = class_product::get_price_arr( $data[product_id], $shop_id );            
            $org_price = $org_price + $arr_price[org_price];
        }
        
        $arr_result = array(
            product_name       => $name
            ,product_options   => $options
            ,product_org_price => $org_price
        );
        
        return $arr_result;
    }

    // 
    // get key..
    function get_source( $shop_id, $order_id, $order_id_seq, $shop_product_id )
    {
        global $connect;
        
        $_arr  = array();
        $query = "select file_seq,row,col,type from stat_upload_data 
                   where type  = 'order_id' 
                     and value = '$order_id'";
        
        $result = mysql_query( $query, $connect );
        
        $row      = -1;  // 값 없음
        $file_seq = -1;  // 값 없음
        
        $num = mysql_num_rows( $result );
        if ( $num == 1 )
        {
            $data = mysql_fetch_assoc( $result );
            $row      = $data[row];
            $file_seq = $data[file_seq];
        }
        else if ( $num > 1 )
        {
            // 여러개의 file_seq, row중 하나를 찾아야 함.
            $file_seqs = "";
            $rows      = "";
         
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                // 찾아지지 않는경우 default    
                $file_seq   = $data[file_seq];
                $row        = $data[row];
                
                // 조건 생성
                $file_seqs .= $file_seqs ? "," : "";
                $file_seqs .= $data[file_seq];
                
                $rows      .= $rows ? "," : "";
                $rows      .= $data[row];
            }
            
            // order_id_seq로 찾는다.
            if ( $order_id_seq != "" )
            {
                $query = "select * from stat_upload_data 
                           where file_seq in ( $file_seqs )
                             and row      in ( $rows )
                             and value like '%$order_id_seq%'
                             and type  = 'order_id_seq'";
                
                //echo $query;
                //echo "<br>----";
                                             
                $result   = mysql_query( $query, $connect );
                $data     = mysql_fetch_assoc( $result );
                $row      = $data[row];
                $file_seq = $data[file_seq];                             
            }
            // shop_product_id로 찾는다.
            else if ( $shop_product_id != "" )
            {
                $query = "select * from stat_upload_data 
                           where file_seq in ( $file_seqs )
                             and row      in ( $rows )
                             and value like '%$shop_product_id%'
                             and type  = 'product_id'";
                
                //echo $query;
                //echo "<br>----";
                     
                $result   = mysql_query( $query, $connect );
                $data     = mysql_fetch_assoc( $result );
                $row      = $data[row];
                $file_seq = $data[file_seq];      
            }  
        }
        
        //
        // row값과 file_seq로 전체 값을 구한다.
        if ( $row >= 0 and $file_seq >= 0 )
        {
            $query = "select value 
                        from stat_upload_data 
                       where file_seq = $file_seq
                         and row      = $row
                         order by col";
            
            // m_file_seq 설정..
            $this->m_file_seq = $file_seq;
            
            //echo $query;
            //echo "<br>----";
        
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                foreach( $data as $_v )
                {
                    $_arr[] = $_v;
                }
            }                         
        }          
                
        return $_arr;                   
    }

    //
    // 정산을 위해 등록한 파일을 다운로드
    function download()
    {
        global $connect, $seq;
        
        $key_order_id     = -1;
        $key_order_id_seq = -1;
        $key_product_id   = -1;
       
        $query    = "select * from stat_upload_file where seq=$seq";
        $result   = mysql_query( $query, $connect );
        $data     = mysql_fetch_assoc( $result );
        $filename = $data[filename];
        
        if ( $data[key_order_id] != "" )
        $key_order_id     = $data[key_order_id];
        
        if ( $data[key_order_id_seq] != "" )
            $key_order_id_seq = $data[key_order_id_seq];
            
        if ( $data[key_product_id] != "" )
            $key_product_id   = $data[key_product_id];
        
        $arr_result = array();
        $query = "select * from stat_upload_data where file_seq=$seq order by row, col";
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[$data[row]][$data[col]] = $data[value];   
        }
        
        // download받을 필드 읽어 온다.
        $arr_fields = $this->get_field();
        
        // data 생성.
        for ( $i=0; $i < count( $arr_result ); $i++ )
        {
            if ( $key_order_id != -1 )
                $order_id     = $arr_result[$i][$key_order_id];
            
            if ( $key_order_id_seq != -1 )
            {
                $_order_id_seq = $arr_result[$i][$key_order_id_seq];
            }
            
            if ( $key_product_id != -1 )
                $product_id   = $arr_result[$i][$key_product_id];
            
            // header 
            if ( $i == 0 )
            {
                foreach( $arr_fields as $_field )
                {
                    $arr_header[] = $this->header_idx[$_field];   
                } 
                
                $arr_result[$i] = array_merge( $arr_result[$i], $arr_header );  
            }
            else
            {
                if ( $order_id != "" || $_order_id_seq  != "" || $product_id != "")
                {
                    $arr_data = $this->get_data( $order_id, $_order_id_seq, $product_id ,$arr_fields);            
                    $arr_result[$i] = array_merge( $arr_result[$i], $arr_data );
                }
            }
            
            // test
            /*
            $arr_result[$i][] = $order_id;
            $arr_result[$i][] = $order_id_seq;
            $arr_result[$i][] = $product_id;
            */
        }
        
        $obj_file = new class_file();
        $obj_file->download( $arr_result, "dn_" . iconv('utf-8','cp949',$filename) );
        exit;
    }

    //
    // 검증이 가능 할 수도 있고, 불가능 할 수도 있음.
    //
    function get_data( $order_id, $order_id_seq, $product_id ,$arr_fields )
    {
        global $connect;
        $arr_data = array();
        $query = "select * from orders where";
        
        $query2 = "";
        if ( $order_id )
        {
            $query2 .= " order_id='$order_id' ";
        }
        
        if ( $order_id_seq )
        {
            $query2 .= $query2 ? " and " : " ";
            $query2 .= " order_id_seq='$order_id_seq' ";
        }
        
        if ( $product_id )
        {
            $query2 .= $query2 ? " and " : " ";
            $query2 .= " shop_product_id like '%$product_id'";
        }
        
        $result = mysql_query( $query . $query2, $connect );
        $rows   = mysql_num_rows( $result );
        
        if ( $rows == 0 )
        {
            
            $result = mysql_query( $query1 . $query2, $connect );
            $rows   = mysql_num_rows( $result );
            
            if ( $rows == 0 )
            {
                
                $result = mysql_query( $query1, $connect );
                $rows   = mysql_num_rows( $result );
            }
        }
        
        $_data  = mysql_fetch_assoc( $result );
        if ( $rows >= 1 )
        {
            $_arr_order_product_info = $this->get_order_product_info( $_data[seq] );
            
            for( $i =0; $i < count($arr_fields ); $i++ )
            {
                if ( $arr_fields[$i] == "status" )
                {
                    $arr_data[] = $this->arr_status[ $_data[$arr_fields[$i]] ];   
                }
                else if ( $arr_fields[$i] == "order_cs" )
                {
                    $arr_data[] = $this->arr_cs[ $_data[$arr_fields[$i]] ];   
                }
                else if ( $arr_fields[$i] == "product_name" || $arr_fields[$i] == "product_options" || $arr_fields[$i] == "product_org_price" )
                {
                    $arr_data[] = $_arr_order_product_info[ $arr_fields[$i] ];   
                }
                else
                {
                    $arr_data[] = $_data[$arr_fields[$i]];
                }
            }
        }
        return $arr_data;
    }

    function get_field()
    {
        global $connect;
        
        $query = "select * from stat_upload_down order by seq";
        $result = mysql_query( $query, $connect );
        $arr_data = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            
            $arr_data[] = $data[fieldname];   
        }
        return $arr_data;
    }   

    // 
    function upload()
    {
        global $connect, $shop_id, $_file, $admin_file_name, $trans_corp, $default_format;
             
        // stat_upload_data에 값을 저장        
        $obj = new class_file();
        $arr_data = $obj->upload();
      
        // print_r ( $arr_data );
        
        // 다운로드 데이터..
        $arr_download_data = array();
        $arr_download_data[] = array(
             "상품명"
            ,"공급처코드 / 공급처명"
            ,"공급처 상품명"
            ,"공급처 옵션"
            ,"원산지"
            ,"택배비"
            ,"중량"
            ,"원가"
            ,"공급가"
            ,"판매가"
            ,"시중가"
            ,"옵션1"
            ,"옵션2"
            ,"옵션3"
            ,"옵션관리"
            ,"바코드"
            ,"대표 이미지"
            ,"설명 이미지1"
            ,"설명 이미지2"
            ,"설명 이미지3"
            ,"설명 이미지4"
            ,"설명 이미지5"
            ,"비고 이미지"
            ,"상품설명"
            ,"재고경고수량"
            ,"재고위험수량"
            ,"합포불가"
            ,"동일상품 합포가능 수량"
            ,"로케이션"
            ,"메모"
            ,"제조사"
            ,"사은품"
            ,"담당MD"
            ,"관리자(정)"
            ,"관리자(부)"
            ,"무료배송"
            ,"카테고리"
            ,"cafe24 상품동기화 안함"
            ,"배송타입"
        );
        
        $str_last_product_name = "";
        
        $tot = count( $arr_data);
        for( $i=2; $i < $tot; $i++ )
        {
            $_data = $arr_data[ $i ];
            if ( $_data[0] == "" )
            {
                // 상품명 작업.
                $arr_download_data[ count( $arr_download_data ) -1 ][0] .= $_data[1];
                $str_last_product_name .= $_data[1];
                continue;   
            }
            
            $str_last_product_name = "";
            
            $str_option1 = "";  // 1번째 option string
            $str_option2 = "";  // 2번째 option string
            $str_option3 = "";  // 3번째 option string
            $arr_option1 = array();
            $arr_option2 = array();
            $arr_option3 = array();
            
            $org_price   = 0;
            $shop_price  = 0;
            $arr_split_options = array();
                
            // single일 경우
            if ( $_data[22] == "" )
            {       
                // option의 개수
                $arr_options = split("\|", $_data[24] );
                
                
                for( $option_cnt = 0; $option_cnt < count( $arr_options ); $option_cnt++ )
                {
                    $_option = $arr_options[$option_cnt];
                        
                    $arr_sub_options = split("\^", $_option );
                    
                    $org_price       = $arr_sub_options[2];
                    $shop_price      = $arr_sub_options[3];
                    
                    //$str_option1 .= $str_option1 ? "," : "";
                    //$str_option1 .= $arr_sub_options[0];   
                    //echo "str_option:" . $str_option1 . "<br>";
                    if ( !in_array( $arr_sub_options[0], $arr_option1 ) )
                    {
                        array_push($arr_option1, $arr_sub_options[0]);
                    }
                    
                    //$str_option2 .= $str_option2 ? "," : "";
                    //$str_option2 .= $_arr_data[1];
                    if ( !in_array( $arr_sub_options[1], $arr_option2 ) )
                    {
                        array_push($arr_option2, $arr_sub_options[1]);
                    }
                }
            }
            //else if ( $_data[22] == "double" )
            else
            {
                $arr_price = split("\^", $_data[24] );
                $org_price = $arr_price[2];
                
                // 25에 값이 있냐 없냐로 옵션 처리 방식이 나뉨
                if ( $_data[25] == "" )
                {
                    $arr_datas = split("\|", $_data[24] );
                    
                    foreach( $arr_datas as $str_data )
                    {               
                        $_arr_data = split("\^", $str_data );
                        
                        //$str_option1 .= $str_option1 ? "," : "";
                        //$str_option1 .= $_arr_data[0];
                        if ( !in_array( $_arr_data[0], $arr_option1 ) )
                        {
                            array_push($arr_option1, $_arr_data[0]);
                        }
                        
                        //$str_option2 .= $str_option2 ? "," : "";
                        //$str_option2 .= $_arr_data[1];
                        if ( !in_array( $_arr_data[1], $arr_option2 ) )
                        {
                            array_push($arr_option2, $_arr_data[1]);
                        }
                    }
                }
                else
                {
                    $arr = split("\|", $_data[26] );
                    
                    for( $oo_cnt = 0; $oo_cnt < count( $arr ); $oo_cnt++ )
                    {
                        $arr_d = split("\^", $arr[ $oo_cnt ] );
                        
                        if ( !in_array( $arr_d[ 1 ], $arr_option1 ) )
                        {
                            array_push($arr_option1, $arr_d[ 1 ] );
                        }
                    }
                    
                    /*
                    $arr = preg_split("/(?=\^(.*)\^)/", $_data[26] );
                    for( $oo_cnt = 0; $oo_cnt < count( $arr ); $oo_cnt++ )
                    {
                        if ( !in_array( $arr[ $oo_cnt ], $arr_option1 ) )
                        {
                            array_push($arr_option1, $arr[$oo_cnt] );
                        }
                    }
                   
                    $arr_keys = split("\|", $_data[25] );
                    //echo "26: $_data[26] <br>";
                    
                    $cnt=0;
                    foreach( $arr_keys as $str_key )
                    {               
                        // 상의^o|하의^o
                        $arr_key2 = split("\^", $str_key );
                        $str_key  = $arr_key2[0];
                        $cnt++;
                        
                        // 상의^[상의] SK.36/그레이/M(95)^0|상의^[상의] SK.36/그레이/L(100)^0|상의^[상의] SK.36/챠콜/M(95)^0|상의^[상의] SK.36/챠콜/L(100)^0|상의^[상의] SK.36/블랙/M(95)^0|상의^[상의] SK.36/블랙/L(100)^0|하의^[하의] EN.26/그레이/L(28~30)^0|하의^[하의] EN.26/그레이/XL(32~34)^0|하의^[하의] EN.26/그레이/XXL(34~36)^4000|하의^[하의] EN.26/챠콜/L(28~30)^0|하의^[하의] EN.26/챠콜/XL(32~34)^0|하의^[하의] EN.26/챠콜/XXL(34~36)^4000|하의^[하의] EN.26/블랙/L(28~30)^0|하의^[하의] EN.26/블랙/XL(32~34)^0|하의^[하의] EN.26/블랙/XXL(34~36)^4000
                        // 상의^[상의] EN.25/챠콜/L(95)^0|상의^[상의] EN.25/챠콜/XL(100)^0|상의^[상의] EN.25/챠콜/XXL(105~108)^2000|상의^[상의] EN.25/그레이/L(95)^0|상의^[상의] EN.25/그레이/XL(100)^0|상의^[상의] EN.25/블랙/L(95)^0|상의^[상의] EN.25/블랙/XL(100)^0|하의^[하의] EN.22/챠콜/L(28~30)^0|하의^[하의] EN.22/챠콜/XL(32~34)^0|하의^[하의] EN.22/그레이/L(28~30)^0|하의^[하의] EN.22/그레이/XL(32~34)^0|하의^[하의] EN.22/블랙/L(28~30)^0|하의^[하의] EN.22/블랙/XL(32~34)^0
                        //echo "key: $str_key<br>";
                        //$arr = preg_split("/(?=($str_key\^(.?)+\^)/", $_data[26] );
                        $arr = preg_split("/(?=$str_key\^(.*)\^)/", $_data[26] );
                        
                        for( $oo_cnt = 1; $oo_cnt < count( $arr ); $oo_cnt++ )
                        {
                            if ( $cnt == 1 )
                            {
                                $key = "str_option1";   
                            }   
                            else if ( $cnt == 2 )
                            {
                                $key = "str_option2";
                            }
                            
                            $arr_option = split( "\/", $arr[$oo_cnt] ); 
                            
                            $$key .= $$key ? "," : "";
                            $_arr = split("\^", $arr_option[2] );
                            
                            $str_option_temp = $arr_option[1] . " " . $_arr[0];
                            $$key .= $str_option_temp;
                            
                            // 
                            if ( $key == "str_option1" )
                            {
                                if ( !in_array( $str_option_temp, $arr_option1 ) )
                                {
                                    array_push($arr_option1, $str_option_temp);
                                }    
                            }
                            
                            if ( $key == "str_option2" )
                            {
                                if ( !in_array( $str_option_temp, $arr_option2 ) )
                                {
                                    array_push($arr_option2, $str_option_temp);
                                }    
                            }
                            
                        }
                        //echo "<br>-----<br>";
                        //print_r( $arr );
                        //echo "<br>-----<br>";
                    }
                    */
                }
                
            }
            
            $str_option1 = "";
            $str_option2 = "";
            foreach( $arr_option1 as $_str )
            {   
                $str_option1 .= $str_option1 ? "," : "";
                $str_option1 .= $_str;
            }
            
            foreach( $arr_option2 as $_str )
            {   
                $str_option2 .= $str_option2 ? "," : "";
                $str_option2 .= $_str;
            }
            
            if ( strpos($_data[1],"개인결제") > 0 )
            {
                continue;   
            }
            
            //
            $arr_download_data[] = array(
                  $_data[1]           // a 상품명
                , ""                  // b 공급처 코드
                , $_data[4]           // c 공급처 상품명
                , ""                  // d 공급처 옵션
                , $_data[0]           // e 원산지
                , ""                  // f 택배비
                , ""                  // g 중량
                , $org_price          // h 원가
                , ""                  // i 공급가
                , $shop_price         // j 판매가
                , ""                  // k 시중가
                , ":" . $str_option1  // l 옵션1
                , ":" . $str_option2  // m 옵션2
                , ""                  // n 옵션3
                , "1"                 // o 옵션관리
                , ""                  // p 바코드
                , ""                  // q 대표이미지 url
                , ""                  // r 설명 url
                , ""                  // s 설명 이미지2
                , ""                  // t 설명 이미지3
                , ""                  // u 설명 이미지4
                , ""                  // v 설명 이미지5
                , ""                  // w 비고이미지
                , htmlspecialchars( $_data[12] )          // x 상품설명
                , ""                  // y 재고경고수량
                , ""                  // z 재고 위험 수량
                , "0"                 // aa 합포가능
                , ""                  // ab 동일상품 합포 가능
                , ""                  // ac 로케이션
            );
            
        }
        
        $arr_download_data[ count( $arr_download_data ) -1 ][0] .= $str_last_product_name;
        
        $obj->download($arr_download_data, "data.xls");
        
    }
    
} 
