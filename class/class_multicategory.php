<?
//***********************************************
// multi category를 위한 수정 부분..
// 2011.5.27
// name: class_multicategory
// file: class_multicategory.php
//
// alter table multi_category modify seq int;
// alter table multi_category DROP primary key ;
// 2012.1.27
//  multi_category에 search_id 추가
//      search_id는 name이 동일할 경우 동일함, 0001,0002,0003의 형식을 갖는다.
//  products에 m_category1,m_category2,m_category3 추가
//      search_id의 조합으로 검색한다 0001,0002,0003의 형식 저장....
//
// alter table multi_category add `search_id` int(4) unsigned zerofill DEFAULT '0000'
// update multi_category set search_id=seq;
// alter table products add `m_category1` int(4) unsigned zerofill DEFAULT '0000';
// alter table products add `m_category2` int(4) unsigned zerofill DEFAULT '0000';
// alter table products add `m_category3` int(4) unsigned zerofill DEFAULT '0000';
// 
// products.str_category에는 한글 > 한글 > 한글 이 들어감
class class_multicategory 
{
    // $str은 12 > 22 > 33 과 같은 code가 들어감
    function get_category_str( $str )
    {
        global $connect;
        $arr_category = split( ">", $str );
        $str_category = "";
        for( $i=0; $i < count( $arr_category ); $i++ )
        {
            $str_category .= $str_category ? " > " : "";
            $str_category .= class_multicategory::_get_category_name( $arr_category[$i] );
        }
        
        return $str_category ? $str_category : $str;    
    }
    
    function _get_category_name( $code )
    {
        global $connect;
        $code = trim( $code );
        
        if ( $code )
        {
            $query = "select * from multi_category where seq=$code";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            return $data[name];
        }
        else
            return "";
    }
    
    function get_search_id($c1,$c2,$c3)
    {
        global $connect;
        $cl = $c1 ? $c1 : 0;
        $c2 = $c2 ? $c2 : 0;
        $c3 = $c3 ? $c3 : 0;
        
        $query = "select seq, search_id from multi_category where seq in ( $c1,$c2,$c3)";
        
//debug( "get_search_id:" . $query );
        
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[$data[seq]] = $data[search_id];   
        }
        return $arr_result;
    }
    
    //
    // m_category1, m_category2, m_category3에 값을 입력
    // str_category는 정보로써의 가치가 없음 그냥 참조만 함. m_category1, m_category2의 결과임.
    function update_multi_category( $product_id, $str_category )
    {
        global $connect;
        $query = "select str_category from products where product_id='$product_id'";
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        // 
        $arr_category = split( ">", $data[str_category] );
        $arr_search_id = array(0,0,0);
        $arr_name      = array("","","");
        $i = 0;
        $parent        = "";
        foreach( $arr_category as $index => $c )
        {
            $_c = trim( $c );
            if ( $_c )
            {
                //$query = "select name,search_id from multi_category where seq=$_c";
                $query = "select seq,name,search_id from multi_category where name='$_c' and  depth = $index+1 ";
                if ( $parent )
                    $query .= " and parent=$parent ";
                    
                $query .= " limit 1";
                
                //debug( "multi update1: " . $query );
                
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                
                if ( !$data )
                {
                    $query = "select seq,name,search_id from multi_category where seq=$_c and  depth = $index+1 ";
                    if ( $parent )
                        $query .=  " and parent=$parent ";
                    
                    //debug( "multi update2: " . $query );
                    
                    $result = mysql_query( $query, $connect );
                    $data   = mysql_fetch_assoc( $result );
                }
                
                $arr_search_seq[$i] = $data[seq] ? $data[seq] : 0;    
                $arr_search_id[$i] = $data[search_id] ? $data[search_id] : 0;
                $arr_name[$i]      = $data[name];
                $parent            = $data[seq];
                /*
                $query = "select search_id from multi_category where name='$data[name]'";
                $result = mysql_query( $query, $connect );
                while ( $data   = mysql_fetch_assoc( $result ) )
                {
                    $arr_search_id[$i] = $data[search_id];    
                }
                */
                
                //debug( "search_id: $data[search_id]" );
            }
            $i++;
        }
        
        if ( count($arr_search_id) > 0 )
        {            
			$str_category = "$arr_name[0] > $arr_name[1] > $arr_name[2]";            
            
            $query = "update products set m_category1=$arr_search_id[0]
                                         ,m_category2=$arr_search_id[1]
                                         ,m_category3=$arr_search_id[2] 
                                         ,str_category = '$arr_search_seq[0] > $arr_search_seq[1] > $arr_search_seq[2] >'
                       where product_id='$product_id' OR org_id='$product_id'";
                       
                       
            debug( $query );
            mysql_query( $query, $connect );
                                   
        }
        
    }
    
    //
    // 카테고리 추가..
    // act: modify, add
    function ma_add_category()
    {
        global $connect,$category1, $category2, $category3, $str, $idx, $act;
        $p_idx = $idx - 1;
        $key = "category" . $p_idx;
        $result = array();
        
        if ( $act == "modify" )
        {
            // modify   
            $result = $this->ma_add_category_m();
        }
        else if ( $act == "delete" )
        {
            $result = $this->ma_add_category_d();    
        }
        else
        {
            // add   
            $result = $this->ma_add_category_a();
        }
         
        echo json_encode( $result );
    }
    
    //
    // 카테고리 삭제...
    //
    function ma_add_category_d()
    {
        global $connect,$category1, $category2, $category3, $str, $idx;
        
        //debug( "del" );        
        if ( $idx == 1 )
            $category0 = 0;
        
        $p_idx = $idx - 1;
        $key = "category" . $p_idx;
        $key_current = "category" . $idx;
        
        // max값을 구한다.
        // 3. 수정
        $str_seqs   = $$key_current;
        $str_parent = "";        
        for ( $i = $idx+1; $i <= 4; $i++ )
        {
            if ( $str_seqs )
            {
                $depth = $i - 1;
                
                $query = "delete from multi_category where seq in ($str_seqs) and depth=" . $depth;
                if ( $str_parent )
                    $query .= " and parent in ( $str_parent )";
                    
                $result = mysql_query( $query, $connect );
                //debug( $query );
                
                $query = "select * from multi_category where parent in ( $str_seqs ) and depth=$i";
                $str_parent = $str_seqs;
                //debug( $query );
                
                $result = mysql_query( $query, $connect );
                $str_seqs = "";
                while ( $data = mysql_fetch_assoc( $result ) )
                {
                    $str_seqs .= $str_seqs ? "," : "";
                    $str_seqs .= $data[seq];
                }
            }
        }
        
        
        // 정보 리턴
        $query  = "select * from multi_category ";
        
        if ( $idx )
        {
            $p_i     = $idx - 1;
            $_parent = "category" . $p_i;
            
            $query .= " where depth=$idx and parent=" . $$_parent;
        }
        
        $query .= " group by name order by name";
        
        //debug( $query );
        
        $result     = mysql_query( $query, $connect );
        $arr_result = array(
            category1  => array()
            ,category2 => array()
            ,category3 => array()
        );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $is_select = 0;
            
            if ( $data[seq] == $seq )
                $is_select = "selected";
            
            $code = "category" . $data[depth];
            
            $arr_result[$code][] = array(
                value        => $data[seq]
                ,name      => $data[name]
                ,selected => $is_select
            );
        }
        
        return $arr_result;   
    }
    
    //
    // 카테고리 수정.
    //
    function ma_add_category_m()
    {
        global $connect,$category1, $category2, $category3, $str, $idx;
        
        if ( $idx == 1 )
            $category0 = 0;
        
        $p_idx = $idx - 1;
        $key = "category" . $p_idx;
        $key_current = "category" . $idx;
        
        // 0. 같은 이름이 혹시 있는지 찾는다.
        $query = "select search_id from multi_category where depth=$idx and name='$str'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $to_search_id = $data[search_id] ? $data[search_id] : "";
        
        // 1. 같은 이름의 category를 찾는다.
        // 같은 이름이 있을경우 update하지 않는다.
        $query = "select * from multi_category where depth=$idx and seq=" . $$key_current;
        //debug( $query );
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        $_str          = $data['name'];
        $org_search_id = $data[search_id];
        
        //
        $query = "Select * from multi_category where depth=$idx and name='$_str'";
        $result = mysql_query( $query, $connect );
        $str_seqs = "";
        while( $data   = mysql_fetch_assoc( $result ) )
        {
            $str_seqs .= $str_seqs ? "," : "";
            $str_seqs .= $data[seq];   
        }
        
        //
        // max값을 구한다.
        // 3. 수정
        $query = "update multi_category set name='$str' ";
        if ( $to_search_id )
            $query .= " ,search_id=$to_search_id";
        $query .= " where seq in ( $str_seqs )";
        //debug( $query );
        mysql_query( $query, $connect );
        
        //
        // to_search_id가 있는경우 products의 search_id를 변경해야 한다.
        //
        if ( $to_search_id )
        {
            $query = "update products set m_category" . $idx . "=$to_search_id 
                       where m_category" . $idx . "=$org_search_id";
            
            mysql_query( $query, $connect );
        }
        
        // 정보 리턴
        $query      = "select * from multi_category 
                        where depth=$idx and parent=" . $$key . "  
                        group by name order by name";
        //debug( $query );
        
        $result     = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $is_select = 0;
            if ( $data[name] == $str )
                $is_select = "selected";
                
            $arr_result[] = array(
                value        => $data[seq]
                ,name      => $data[name]
                ,selected => $is_select
            );
        }
        
        return $arr_result;   
    }
    
    function ma_add_category_a()
    {
        global $connect,$category1, $category2, $category3, $str, $idx;
        
        if ( $idx == 1 )
            $category0 = 0;
        
        $p_idx = $idx - 1;
        $key = "category" . $p_idx;
        
        // check
        // 동일한 이름의 다른 depth는 오류..!!
        $query = "select * from multi_category where depth <> $idx and name='$str'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data )
        {
            $arr_result = array( "error" => 1, "msg" => "이미 등록된 카테고리입니다" );
            echo json_encode( $arr_result );
            exit;   
        }
        
        // search_id를 찾는다.
        $query = "select * from multi_category where depth=$idx and name='$str'";
        //debug( $query );
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc($result );
        $search_id = $data[search_id] ? $data[search_id] : 0;
        
        if ( $search_id == 0 )
        {
            $query = "select max(search_id)+1 m_search_id from multi_category";
            $result =mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc($result);
            $search_id = $data[m_search_id] ? $data[m_search_id] : 1;   
        }
        
        // 1. 같은 이름의 category를 찾는다.
        //$query = "select * from multi_category where depth=$idx and name='$str' and parent=" . $$key;
        $query = "select * from multi_category where depth=$idx and name='$str' and parent=" . $$key;
        //debug( $query );
        //echo $query;
        
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        //debug( "name: " . $data[name] );
        //print_r ( $data );
        
        //
        // seq를 찾는다.
        //
        $seq = "";
        if ( $data[name] )
        {
            $seq = $data[seq];
        }
        else
        {
            // max값을 구한다.
            $query = "select max(seq)+1 seq from multi_category";
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            
            $seq = $data[seq] ? $data[seq] : 1;
            
            // 3. 입력
            $query = "insert into multi_category 
                         set seq=$seq, name='$str', parent=" . $$key . ",depth=$idx,search_id=$search_id";
            //debug( $query );
            mysql_query( $query, $connect );
            
        }
        
        // 정보 리턴
        $query      = "select * from multi_category where depth=$idx and parent=" . $$key . "  group by name order by name";
        //debug( $query );
        $result     = mysql_query( $query, $connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $is_select = 0;
            if ( $data[seq] == $seq )
                $is_select = "selected";
                
            $arr_result[] = array(
                value        => $data[seq]
                ,name      => $data[name]
                ,selected => $is_select
            );
        }
        
        return $arr_result;   
    }
    
    //
    // 1을 click -> 2,3 초기화 -> 2 변경
    // 2를 click -> 3, 초기화  -> 3 변경
    // 
    function ma_init_all()
    {
        global $connect,$category1, $category2, $category3, $idx;
        
        $arr_result = array();
        $str_category2 = "";
        
        // category 1
        $query = "select * from multi_category where depth=1 ";
        $query .= " group by name order by name"; 
         
        $result = mysql_query( $query, $connect );
        $arr_result['category1'] = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result['category1'][] = array( 
                 name  => $data[name]
                ,value => $data[seq] 
                ,selected => ""
            );
        }
        
        // category 2
        $query = "select * from multi_category where depth=2 ";
        
        if ( $category1 != -1 && $category1 != "" )
            $query .= " and parent = $category1 ";
        
        $query .= " group by name order by name"; 
         
        $result = mysql_query( $query, $connect );
        $arr_result['category2'] = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result['category2'][] = array( 
                 name  => $data[name]
                ,value => $data[seq] 
                ,selected => ""
            );
            
            if ( $idx <= 2 )
            {
                $str_category2 .= $str_category2 ? "," : "";
                $str_category2 .= $data[seq];
            }
        }
        
        // category 3
        $arr_result['category3'] = array();
        if ( $idx == 2 )
        {
            $query = "select * from multi_category where depth=3 ";
            
            if ( $category2 != -1 )
                $query .= " and parent in ( $category2 ) ";
            
            if ( $category2 == -1 )
                $query .= " and parent in ( $str_category2 ) ";
            
            $query .= " group by name order by name";  
            
            //debug( $query );
            
            $result = mysql_query( $query, $connect );
            
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $arr_result['category3'][] = array( 
                     name  => $data[name]
                    ,value => $data[seq] 
                    ,selected => ""
                );
            }
        }
        echo json_encode( $arr_result );
    }
    
    //
    // 상품 대량 등록시 category 저장
    // date: 2011-7-11
    function save_str_category( $str_category )
    {
        global $connect;
        
        $arr_category = split( '>', $str_category );
        
        $parent_id = 0;
        $is_new    = 0;     // 신규 추가일 경우 이후 전체추가.
        $arr_search_id = array(
                 1 => array( seq=>0, search_id =>0)
                ,2 => array( seq=>0, search_id =>0)
                ,3 => array( seq=>0, search_id =>0));

        // 값이 없는 경우..그냥 return
        if ( !$str_category )
            return $arr_search_id;
        
                
        for( $i = 0; $i < count( $arr_category ); $i++ )
        {
            $depth = $i + 1;
            
            if ( trim($arr_category[$i]) )
            {                
                $query = "select seq, name,search_id from multi_category 
                           where depth=$depth and name='" .trim($arr_category[$i]) . "'";
                           
                if ( $parent_id )
                    $query .= " and parent=$parent_id ";

                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                $_search_id = $data[search_id];
                $_seq       = $data[seq];
                
                if ( $data[seq] && $is_new != 1)
                {
                    $parent_id = $data[seq];
                }
                else
                {
                    $is_new = 1;
                    
                    // max seq 찾기
                    $query = "select max(seq)+1 seq from multi_category";
                    $result = mysql_query( $query, $connect );
                    $data   = mysql_fetch_assoc( $result );
                    $_seq   = $data[seq] ? $data[seq] : 1;
                    
                    // search_id 찾기
                    $query = "select seq, name,search_id from multi_category 
                           where depth=$depth and name='" .trim($arr_category[$i]) . "'";
                    
                    $result = mysql_query( $query, $connect );
                    $data   = mysql_fetch_assoc( $result );
                    
                    if ( !$data )
                    {
                        $query = "select max(search_id)+1 seq from multi_category";
                        $result = mysql_query( $query, $connect );
                        $data   = mysql_fetch_assoc( $result );
                        $_search_id = $data[seq] ? $data[seq] : 1;
                    }
                    else
                    {
                        $_search_id = $data[search_id];   
                    }
                    
                    $query = "insert into multi_category 
                                 set seq       = $_seq
                                    ,search_id = $_search_id      
                                    ,name      = '" . trim($arr_category[$i]) . "'
                                    ,parent    = $parent_id
                                    ,depth     = $depth";
                                    
                    mysql_query($query, $connect );                      
                    //debug( $query );
                    
                    // 최종 입력한 idx
                    // $parent_id = mysql_insert_id(); 
                    $parent_id = $_seq;
                }
            }
            
            $arr_search_id[$depth] = array( seq=>$_seq, search_id =>$_search_id);
            $_search_id = "0";
        }   
        
        return $arr_search_id;
    }
    
    function load_multi_data()
    {
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $connect, $click_index;
        global $current_category1, $current_category2, $current_category3;
        
        //debug("!!!!!!!c1: $current_category1, $current_category2, $current_category3" );
        
        
        $m_sub_category_1 = $m_sub_category_1 == "(전체)" ? -1 : $m_sub_category_1;
        $m_sub_category_2 = $m_sub_category_2 == "(전체)" ? -1 : $m_sub_category_2;
        $m_sub_category_3 = $m_sub_category_3 == "(전체)" ? -1 : $m_sub_category_3;
        
        //
        // 1->3 으로 검색
        //
        $str_category1="";
        $str_category2="";
        $str_category2="";
        $str_parent1 = "";
        $str_parent2 = "";
        $str_parent3 = "";
        $arr_category1 = array();
        $arr_category2 = array();
        $arr_category3 = array();
        
        // str_category1을 구한다.
        if ( $m_sub_category_1 != -1 )
        {
            $query = "select * from multi_category where depth=1";
        }
        else
        {
            $query = "select * from multi_category where depth=1";
            
            // 전체 선택이 아닌경우
            if ( $m_sub_category_1 != -1 && $m_sub_category_1 != "undefinded" )
                $query .= " and seq=$m_sub_category_1";
        }
        
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $str_category1 .= $str_category1 ? "," : "";
            $str_category1 .= $data[seq];   
        }
        
        // str_category2 구한다.
        $query = "select * from multi_category where depth=2 ";
        if ( $str_category1 )
        {
            $query .= " and parent in ( $str_category1 )";   
        }
        
        if ( $m_sub_category_1 != -1 && $m_sub_category_1 != "undefinded" )
            $query .= " and parent in ( $m_sub_category_1 )";   
        
        //debug( "c2: $query " );
        
        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $str_category2 .= $str_category2 ? "," : "";
            $str_category2 .= $data[seq];   
        }
        
        // str_category3 구한다.
        // 1-3으로 검색시 category2에 선택값이 없으면 category3도 값이 없음.
        if ( $str_category2 )
        {
            $query = "select * from multi_category where depth=3 ";
            if ( $str_category2 )
            {
                $query .= " and parent in ( $str_category2 )";   
            }
            
            if ( $m_sub_category_2 != -1 && $m_sub_category_2 != "undefinded" && $m_sub_category_2  != "" )
                $query .= " and parent in ( $m_sub_category_2 )";   
            
            //debug( "c3: $query " );
            
            $result = mysql_query( $query, $connect );
            while( $data = mysql_fetch_assoc( $result ) )
            {
                $str_category3 .= $str_category3 ? "," : "";
                $str_category3 .= $data[seq];   
            }
        }
        else
        {
            // category2가 없는 경우
            $str_category3 = "";    
        }
        
        //
        // 3->1 로 검색        
        //
        // categpry2가 -1 일경우에만...run
        // category2를 구한다.   
        // $str_category3이 없음 => category3의 값이 없음.
        if ( $str_category3 )
        {     
            if ( $m_sub_category_2 == -1 )
            {
                $query = "select * from multi_category where seq=$m_sub_category_3";
                $result = mysql_query( $query, $connect );
                $data   = mysql_fetch_assoc( $result );
                $_search_id = $data[search_id];
                
                //$query = "select * from multi_category where depth=3 and name='$_name'";
                $query = "select * from multi_category where depth=3 and search_id='$_search_id'";
                //debug( "xc3: $query" );
                
                $result = mysql_query( $query, $connect );
                $str_parent2 = "";
                
                while ( $data = mysql_fetch_assoc( $result ) )
                {
                    $str_parent2 .= $str_parent2 ? "," : "";
                    $str_parent2 .= $data[parent];   
                }      
            }
            else
                $str_parent2 = $m_sub_category_2;
        }
        
        //debug( "parent2: $str_parent2" );
        
        // category1을 구한다.        
        if ( $m_sub_category_1 == -1 && $str_parent2 != "" && $str_parent2 != "")
        {
            $query = "select * from multi_category where seq in ($str_parent2)";
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $str_parent1 .= $str_parent1 ? "," : "";
                $str_parent1 .= $data[parent];   
            }      
        }
        debug( "parent3: $query" );
        
        //
        // 결과 검색
        //
        // $arr_category1
        $arr_result = array( c1=>array(), c2=>array(), c3=>array() );
        
        // c1
        if ( $str_category1 != "" )
        {
            $query   = "select * from multi_category where depth=1 and seq in ( $str_category1 ) ";
            
            if ( $m_sub_category_1 == -1 && $str_parent1 != "")
                $query .= " and seq in ( $str_parent1 ) ";
                
            $query .= " group by name order by name";        
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c1"][$data[seq]] = $data[name];
                $arr_result["c1"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }
        }
        else
            $arr_result["c1"] = array();
        
        // c2
        if ( $str_category2 != "" )
        {
            $query   = "select * from multi_category where depth=2 and seq in ( $str_category2 ) ";
            if ( $m_sub_category_2 == -1 && $str_parent2 != "")
                $query .= " and seq in ( $str_parent2 ) ";
            
            $query .= " group by name order by name";        
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c2"][$data[seq]] = $data[name];
                $arr_result["c2"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }
        }
        else
            $arr_result["c2"] = array();
        
        // c3
        if( $str_category3 != "" )
        {
            $query   = "select * from multi_category where depth=3 and seq in ( $str_category3 ) group by name order by name";        
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c3"][$data[seq]] = $data[name];
                $arr_result["c3"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }
        }
        else
            $arr_result["c3"] = array();
        
        //
        // submit 한 경우..
        //
        
        if ( $current_category1 )
        {
            $query   = "select * from multi_category where depth=1 and seq in ( $current_category1 ) group by name order by name";     
            
            //debug("cu1: $query");
               
            $result = mysql_query( $query, $connect );
            $arr_result["c1"] = array();
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c1"][$data[seq]] = $data[name];
                $arr_result["c1"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }   
        }
        
        if ( $current_category2 )
        {
            $query   = "select * from multi_category where depth=2 and seq in ( $current_category2 ) group by name order by name";        
            
            //debug("cu2: $query");
            
            $result = mysql_query( $query, $connect );
            $arr_result["c2"] = array();
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c2"][$data[seq]] = $data[name];
                $arr_result["c2"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }   
        }
        
        if ( $current_category3 )
        {
            $query   = "select * from multi_category where depth=3 and seq in ( $current_category3 ) group by name order by name";        
            debug("cu3: $query");
            $result = mysql_query( $query, $connect );
            $arr_result["c3"] = array();
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                //$arr_result["c3"][$data[seq]] = $data[name];
                $arr_result["c3"][$data[seq]] = array( name=>$data[name],search_id=>$data[search_id]);
            }   
        }
        else if ( $current_category3 == "" )
        {
            // $arr_result["c3"] = array();    
        }
        
        
        echo json_encode( $arr_result );
    }
    
    //
    // 한 번에 대량으로 category 출력
    // date: 2011.7.7
    function _load_multi_data()
    {
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $connect, $click_index;
        
        // test
        // $click_index = 1;
        // $m_sub_category_1 = "가방";
        //$m_sub_category_1 = str_replace("(전체)","", $m_sub_category_1);
        //$m_sub_category_2 = str_replace("(전체)","", $m_sub_category_2);
        //$m_sub_category_3 = str_replace("(전체)","", $m_sub_category_3);
         
        $arr_result = array();
        $arr_c1     = array();
        $arr_c2     = array();
        $arr_c3     = array();
        
        //debug( "!!!! click_index: $click_index" );
        
        //
        // step 1. parent찾기 
        // category3, category2 의 parent를 찾는다.
        $parent_id = array();        
        if ( $click_index >= 1 )
        {            
            for( $i = 3; $i >=1; $i-- )
            {
                $val = "m_sub_category_$i";
                
                //if ( $$val != "undefined" )
                //{
                    if ( $parent_id[$i] )
                    {
                        $query = "select * from multi_category where depth=$i and seq in (" . $parent_id[$i] . ")";
                        
                        if ( $$val != "undefined" && $$val != -1)
                            $query .= " and seq = " . $$val;
                    }
                    else
                    {
                        if ( $$val != "undefined" && $$val != -1)
                            $query = "select * from multi_category where depth=$i and seq =". $$val ."";
                        else
                            $query = "select * from multi_category where depth=$i";
                    }
                    // $query = "select * from multi_category where depth=$i and name='". $$val ."'";
                    
                    //debug( "$i (" . $$val . " )find parent: $query");
                    
                    $result = mysql_query( $query, $connect );
                    
                    while ( $data   = mysql_fetch_assoc( $result ) )
                    {
                        $parent_id[$i-1] .= $parent_id[$i-1] ? "," : "";
                        $parent_id[$i-1] .= $data[parent];                
                    }
                    
                    //debug( "$i parent:" . $parent_id[$i-1] );
                //}
            }
        }
        
        //
        // step 2. child 찾기..
        // 실 데이터를 가져오는 부분
        // 일단 카테고리는 3개 고정
        $query = "select * from multi_category where depth = 1";
        
        if ( $parent_id[1] )
            $query .= " and seq in (" . $parent_id[1] . ")";
            
        if ( $m_sub_category_1 != "-1" && $m_sub_category_1 != "undefined" )
        {
            $query .= " and seq = $m_sub_category_1 ";
        }
        
        $query .= " order by name";
        
        //debug("click_index: $click_index");
        //debug( "m_sub_category_1: " . $m_sub_category_1 );
        //debug( "c1 xxx query: $query");
        
        $result = mysql_query( $query, $connect );
        
        $_sub_category1 = "";
        $_sub_category2 = "";
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_c1[ $data["seq"] ] = $data["name"];
            
            if ( ($m_sub_category_1 != "-1" && $m_sub_category_1 == $data["name"]) || $m_sub_category_1 != "undefined")
            {
                $_sub_category1 .= $_sub_category1 ? "," : "";
                $_sub_category1 .= $data["seq"];       
            }
        }
        
        $query = "select * from multi_category where depth = 2";
        if ( $parent_id[2] )
            $query .= " and seq in (" . $parent_id[2] . ")";
            
        if ( $_sub_category1 )
        {
            $query .= " and parent in ( $_sub_category1 )";
        }
        $query .= " group by name order by name";
        
        //debug( "c2 query: $m_sub_category_2 : $query");
        
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_c2[ $data["seq"] ] = $data["name"];
            
            //debug ( "xxx: $m_sub_category_2 / $data[name] // $data[seq] ");
            
            // 전체일 경우 category3은 출력한다?
            if ( $m_sub_category_2 == "-1"  || $m_sub_category_2 == "undefined")
            {
                //$_sub_category1 = ""; 
                
                $_sub_category2 .= $_sub_category2 ? "," : "";
                $_sub_category2 .= $data[seq];        
            }
            else if ( $m_sub_category_2 != "" ) 
            {
                if (  $m_sub_category_2 == $data["seq"] )
                {
                    $_sub_category2 = $data["seq"];  
                
                    //debug( "!!!_sub_category2: $_sub_category2 ");
                    // $_sub_category1 = "";         
                }
            }
            else
            {
                $_sub_category2 .= $_sub_category2 ? "," : "";
                $_sub_category2 .= $data[seq];     
            }
        }
        
        if ( $m_sub_category_2 == "-1" )
        {
            $_sub_category1 = "";    
            //debug("전전전");   
        }
        
        // category 3
        //if ( $_sub_category1 == "" || $_sub_category1=="-1" || $_sub_category2 != "")
        //{
            $query = "select * from multi_category where depth = 3";
            if ( $_sub_category2 )
            {
                $query .= " and parent in ( $_sub_category2 )";
            }
            
            $query .= " group by name  order by name";
            //debug ( "c3: $query" );
            
            $result = mysql_query( $query, $connect );
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                $arr_c3[ $data["seq"] ] = $data["name"];
            }
        //}
        
        // packing
        //$arr_c1 = array_unique($arr_c1);
        //$arr_c2 = array_unique($arr_c2);
        //$arr_c3 = array_unique($arr_c3);
        
        $arr_result["c1"] = $arr_c1;
        $arr_result["c2"] = $arr_c2;
        $arr_result["c3"] = $arr_c3;
        
        //print_r ( $arr_c2 );
        
        //sort( $arr_result["c1"] );
        //sort( $arr_result["c2"] );
        //sort( $arr_result["c3"] );
        echo json_encode( $arr_result );	
        
    }
    
    // 상품에 category설정
    function set_category( $product_id )
    {
        return;
        
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3, $m_sub_category_4, $connect;
        
        if ( $m_sub_category_1 )
        {
            $query = "update products set 
                             category1 = $m_sub_category_1";
                             
            if ( $m_sub_category_2 )
                $query .= " ,category2 = $m_sub_category_2";
                
            if ( $m_sub_category_3 )
                $query .= " ,category3 = $m_sub_category_3";   
                
            if ( $m_sub_category_4 )
                $query .= " ,category4 = $m_sub_category_4";
        
            $query .= " where product_id = '$product_id'";
            
            //debug( $query );
            //echo $query;
            mysql_query( $query, $connect );
        }
        else
        {
            //print_r ( $_REQUEST );
        }   
        //exit;             
    }
    
    
    //
    // category data 가져옴
    //
    function get_category_data()
    {
		global $connect, $depth, $parentCategory,$name,$is_total;
        $depth = $depth ? $depth : 1;
        $parent = $parentCategory ? $parentCategory : 0;
        $arr_result = array();
        
        $arr_result["depth"]  = $depth;
        $arr_result["parent"] = $parentCategory;
        
        //
        // 출력 가능한 카테고리 선택..
        // 기준은 현재 선택..
        // logic
        // 1st 1,2,3 번째 계산..
        // 2nd 3,2,1 번째 계산..
        $str_category = "";
        for( $i = 1; $i <= 3; $i++ )
        {
            $key = "m_sub_category_" . $i;
            global $$key;            
            //debug( $key . ":" . $$key );
            
            if ( $$key != "" && $$key != "undefined" )
            {
                $str_category = $$key;
            }      
            else
            {
                if ( $str_category )
                {
                    $_query = "select seq from multi_category where depth=$i and parent in ( $str_category )";
                    //debug( $_query );
                    
                    $_result = mysql_query( $_query, $connect );
                    
                    
                    while ( $_data = mysql_fetch_assoc( $_result ) )
                    {
                        $str_category .= $str_category ? "," : "";
                        $str_category .= $_data[seq];
                    }
                }
            }
            //debug( "str_category:" . $str_category );
        }
        
        
        if ( $depth > 1 )
        {
            /*
            $query1 = "select * from multi_category where parent='$parentCategory' and depth=" . ($depth-1);
            
            echo $query1;
            
            $result = mysql_query( $query1,$connect );
            $data   = mysql_fetch_assoc( $result );
            */
            
            if ( $parentCategory != "" &&  $parentCategory != "0" && $parent != "undefined")
            {
                // find depth & parent category
                /*
                for( $i = $depth; $i > 0; $i-- )
                {
                    $key = "m_sub_category_" . $i;
                    global $$key;
                    
                    if ( $$key )
                    {
                        $query = "select * from multi_category where depth=$i and    
                    }    
                }
                */
                
                $query = "select * from multi_category 
                           where depth='$depth' 
                             and parent in ($parentCategory)  ";
                             
                if ( $str_category )
                    $query .= "and seq  in ($str_category) ";                             
                            
                $query .= " group by name order by name";
                
            }
            else
            {
                $query = "select * 
                            from multi_category 
                           where depth='$depth' ";
                if ( $str_category )
                    $query .= "and seq  in ($str_category) ";
                
                $query .= " group by name order by name";
            }
            
        }
        else
        {
            //
            if ( $parent !=0 && $parent != "" && $parent != "undefined")
            {
                $query = "select * from multi_category 
                           where depth='$depth' 
                           and parent in ($parent) ";
                //if ( $str_category )
                //    $query .= "and seq  in ($str_category) ";
                           
                $query .= "group by name order by name";
            }
            else
            {
                $query = "select * 
                             from multi_category
                            where depth='$depth' ";
                            
                //if ( $str_category )
                //    $query .= "and seq  in ($str_category) ";
                    
                $query .= "group by name order by name";
            }
            
	    }
	    
	    //debug( $query );
	    
		$result = mysql_query( $query, $connect );
	    
	    $arr_result["query1"] = $query1;
	    $arr_result["query"]  = $query;
	    $arr_result["_list"] = array();
	    
	    // 기본 값 전체를 추가한다.
	    //if ( $is_total == 1 )
	    //{
	       $arr_result["_list"][] = array( seq => "-1", name=> "(전체)",  parent=>"0", depth=>$depth );
	    //}
	    
		while ( $data = mysql_fetch_assoc( $result ) )
		{
            $arr_result["_list"][] = $data;
		}
	    echo json_encode( $arr_result );	
    } 


    // 등록된 값이 있는지 확인
    function check_value()
    {
        global $connect, $name, $depth;
        $depth = $depth ? $depth : 1;
        
        $query = "select * from multi_category where name like '%$name%' and depth=$depth";
        $result = mysql_query( $query, $connect );
	    $arr_result = array();
		while ( $data = mysql_fetch_assoc( $result ) )
		{
            $arr_result[] = $data;
		}
	    echo json_encode( $arr_result );	
    }

   
    // 카테고리 값 추가 
    function insert_new_category()
    {
        global $connect,$depth,$category,$parent_idx,$name;
        $depth      = $depth      ? $depth      : 1;
        $parent_idx = $parent_idx ? $parent_idx : 0;
        
        $query = "insert into multi_category set name='" . trim($name) . "', parent=$parent_idx, depth=$depth";
        mysql_query($query, $connect );
        
        //echo $query;
        // date refresh
        $query = "select * from multi_category where parent=$parent_idx";
        $result = mysql_query( $query, $connect );
        $arr_data = array();
        while( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = $data;   
        } 
        
        echo json_encode( $arr_data );
       
    }

    // 카테고리 삭제
    function del_category()
    {

    }
    
    /*
        shoplinker용 function list
        date: 2011.6.8 - jk
    */
    function sl_get_category()
    {
        global $connect,$parent, $depth;
        $depth = $depth ? $depth : 0;
        
        $sys_connect = sys_db_connect();
        
        $query = "select * from sl_category where depth=$depth and parent=$parent order by name";
        $result = mysql_query( $query, $sys_connect );
        $arr_result = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_result[] = $data;   
        }
        
        echo json_encode( $arr_result );
    }
}

?>
