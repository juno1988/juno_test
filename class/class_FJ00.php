<?
require_once "class_top.php";
require_once "class_stock.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_product.php";

////////////////////////////////
// class name: class_FJ00
//
// stat_upload_file : 업로드한 정산 파일에 대한 master정보
//      status: 1: 업로드 / 2: 오류 / 3: 완료
//
// stat_upload_data : 업로드한 정산 파일의 내용
// stat_upload_down: 
class class_FJ00 extends class_top 
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
    
    function FJ00()
    {
        global $template, $start_date, $end_date, $shop_id, $page, $order_id, $search_all;
        
        if( !$start_date )
            $start_date = date("Y-m-d", strtotime("-60 day") );
        
        $result = $this->get_list();
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function FJ01()
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
        
        $this->redirect("template15.htm?template=FJ00");        
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
        
        debug(  $query . $query2 );
        
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
        
        // 동일한 파일명은 등록 불가
        $query = "select * from stat_upload_file where filename='" . $_FILES['_file']['name'] . "'";
        $result = mysql_query( $query, $connect );
        $rows   = mysql_num_rows( $result );
        
        if ( $rows >= 1 )
        {
            $this->jsAlert("이미 등록된 파일명 입니다");
            $this->redirect("template15.htm?template=FJ00");   
        }
        
        $key_order_id     = -1;
        $key_order_id_seq = -1;
        $key_product_id   = -1;
       
        //print_r( $_FILES );
        
        // stat_upload_file에 값을 저장
        $query = "insert stat_upload_file set filename='" . $_FILES['_file']['name'] . "', status=1, crdate=Now(),shop_id='$shop_id'";
        $result = mysql_query( $query, $connect );
        
        // 방금 입력한 seq를 가져온다.
        $query = "select LAST_INSERT_ID() seq from stat_upload_file";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $file_seq = $data[seq];
                        
        // stat_upload_data에 값을 저장        
        $obj = new class_file();
        $arr_data = $obj->upload();
        
        // row
        // key를 찾는다.
        $_find_key = 0;
        for( $row=0; $row < count($arr_data); $row++ )
        {
            // find key            
            // 주문 번호 찾기
            if ( $_find_key == 0 )
            {
                for( $col = 0; $col <= count($arr_data[$row]); $col++ )
                {
                    // 주문번호
                    if ( $key_order_id == -1 )
                    {
                        if ( preg_match("/주문번호|고객사용번호|주문ID|낙찰 번호|체결번호|거래번호/", trim( $arr_data[$row][$col] ) ) )
                        {
                            $key_order_id = $col;   
                            
                            //echo "order_id: $col <br>";
                        }
                    }
                    
                    // 부 주문번호
                    //if ( preg_match("/배송ID|상품별거래번호|seq|주문순번|발주번호(주문/반품)/", str_replace( " ","",$arr_data[$row][$col] ) ) )
                    if( preg_match("/배송ID|상품별거래번호|seq|주문순번|발주번호(주문\/반품)/", str_replace(" ","",$arr_data[$row][$col])))
                    {
                        $key_order_id_seq = $col;
                    }
                    
                    // 상품번호
                    if ( preg_match("/상품코드|상품순번|상품번호|상품ID|상품상세코드|단품코드|물품 번호/", trim( $arr_data[$row][$col] ) ) )
                    {
                        $key_product_id = $col;
                    }
                }
                
                if ( $key_order_id >= 0 )                
                    $_find_key = 1;
            }

			debug( "" );           
 
            // key를 찾지 못한 경우 계속...지나간다.
            for( $col = 0; $col <= count($arr_data[$row]); $col++ )
            {
                //echo "$row / $col : " . $arr_data[$row][$col] . "<br>";   
                $query = "insert into stat_upload_data values( $file_seq, $row, $col, \"" . addslashes( trim($arr_data[$row][$col]) ) . "\" ";
                
                if ( $col == $key_order_id )
                {
                    $query .= ",'order_id'";
                } 
                else if ( $col == $key_order_id_seq )
                {
                    $query .= ",'order_id_seq'";
                }
                else if ( $col == $key_product_id )
                {
                    $query .= ",'product_id'";
                }   
                else
                {
                    $query .= ",null";
                }
                
                $query .= ")";

				debug( $query );

                mysql_query( $query, $connect );
            }
            
            // disp
            if( $row % 20 == 0 )
            {
                $str = "$row/" . count($arr_data);
                echo "<script language=javascript> 
                        show_waiting() 
                        show_txt ( '$str' );
                      </script>";
                flush();
            }
        }
        
        // key_order_id 가 -1 일경우 오류..종료..
        // 
        if ( $key_order_id == -1 )
        {
            $query = "update stat_upload_file set status=2, memo='헤더오류' where seq=$file_seq";
            mysql_query( $query, $connect );
            
            $this->jsAlert("필수 헤더가 없습니다.");
            $this->redirect("template15.htm?template=FJ00");
        }
        else
        {
            $query = "update stat_upload_file 
                         set status=3
                           , memo='완료' 
                           , key_order_id     = $key_order_id";
                           
            if ( $key_order_id_seq != -1 )
            {
                $query .= ", key_order_id_seq = $key_order_id_seq";   
            }              
            
            if ( $key_product_id != -1 )
            {
                $query .= ", key_product_id = $key_product_id";             
            }
                           
            $query .= " where seq=$file_seq";
            
            //echo( $query );
            mysql_query( $query, $connect );
        }
        
        // stat_upload_field 에 값을 저장.        
        $this->redirect( "?template=FJ00" );
    }
} 
