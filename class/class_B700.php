<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_B100
//

class class_B700 extends class_top {

    ///////////////////////////////////////////
    //
    // shop들의 list출력
    function B700()
    {
    	global $connect;
    	global $template, $line_per_page;
    	
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function B701()
    {
    	global $connect;
    	global $template, $line_per_page;
    	
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //
    // 포맷 삭제
    // date: 2007.3.27
    //
    function del_format()
    {
        global $format_name, $trans_corp, $format_id, $connect;

		$query = "delete from trans_conf where trans_corp ='$trans_corp' and format_id='$format_id'";
		mysql_query ( $query, $connect );

		//=================================
		//
		// format data 삭제
		// date: 2007.3.27
		//
		$table = "trans_format";
        $query = "delete from $table where trans_id='$trans_corp' and format_id='$format_id'";
        mysql_query ( $query, $connect );


		// format combobox 출력
		$this->trans_select ();
    }

	//=====================================
    //
    // 사용자 정의 포맷 이름 추가
    // date: 2007.1.9
    //
    function add_name()
    {
        global $format_name, $trans_corp, $connect;

		$query = "insert into trans_conf 
                     set trans_corp = $trans_corp
                         ,name      = '$format_name'
                         ,crdate    = Now()";

		mysql_query ( $query, $connect );
		$query = "select * from trans_conf
                   where trans_id='$trans_corp' and name='$format_name' ";

        $query = "select LAST_INSERT_ID() format_id";                   
		$result = mysql_query ( $query, $connect );
		$data = mysql_fetch_array ( $result );

		// format combobox 출력
		$this->trans_select ( $data[format_id] );
    }

    //
    // 사용자 정의 포맷 저장
    // date: 2007.1.9
    //
    function save_format()
    {
		global $is_header;	// 헤더가 있는지 여부 1: 헤더 있음, 0: 헤더 없음
		global $pack_multiline;	// 합포가 여러줄인지  0: 한 줄, 1: 여러 줄
		global $sort1,$sort2,$sort3,$sort4;	// 정렬 순서
		global $trans_corp;	// 택배사
		global $trans_format;	// 포맷
		global $product_sum;
		global $position_seq;		// 송장입력 관리 번호 
		global $position_transno;	// 송장 번호 
		global $connect;

        if ( $sort1 )
            $sort_info = $sort1;
        
        if ( $sort2 )
        {
            $sort_info .= $sort_info ? "," : "";
            $sort_info .= $sort2;
        }
        
        if ( $sort3 )
        {
            $sort_info .= $sort_info ? "," : "";
            $sort_info .= $sort3;
        }
        
        if ( $sort4 )
        {
            $sort_info .= $sort_info ? "," : "";
            $sort_info .= $sort4;
        }

		// 환경 삭제
		# $query = "delete from trans_index_info where trans_id='$trans_corp' and format_id='$trans_format'";
		# mysql_query ( $query, $connect );

		// 환경 저장
		$query = "update trans_conf
					set is_header      = '$is_header', 
				        pack_multiline = '$pack_multiline', 
				        sortinfo       = '$sort_info',
				        product_sum    = '$product_sum'
			      where trans_corp     ='$trans_corp' 
                    and format_id      ='$trans_format'";
debug( "택배포멧1 : " . $query );
		mysql_query ( $query, $connect );

		// 송장 번호 관련 저장
		$query = "update trans_conf
					set position_seq     = '$position_seq',
						position_transno = '$position_transno'
			      where trans_corp       ='$trans_corp' ";

debug( "택배포멧2 : " . $query );
		mysql_query ( $query, $connect );

		//*********************************************
        // data 저장
        // 기존의 data를 삭제 한 후 새로 저장한다
		// step 1. 기존의 data 삭제
		$table = "trans_format";	
		$query = "delete from $table where trans_id=$trans_corp and format_id=$trans_format";
		mysql_query ( $query, $connect );

		// step 2. 저장
		$j = 1;
        for ( $i=65; $i <= 115; $i++ )
        {
		    if ( $i < 91 )
			    $key = "macro" . chr($i);
		    else
		    {
			    $key = "macroA" . chr($i - 26);
		    }

		    global $$key;
		    if ( $$key )
            {		
			    $query = "insert into $table set trans_id='$trans_corp', format_id='$trans_format', seq=$j, macro_value='" . $$key . "', macro_desc=''";

debug( "택배포멧3 : " . $query );
			    mysql_query ( $query, $connect );
		    }
		    $j++;
        } 

		$this->jsAlert("저장되었습니다.");
    }

    //
    // 사용자 정의 포맷 정보 갱신
    // date: 2007.1.9
    //
    function update_format()
    {
		global $is_header;	// 헤더가 있는지 여부 1: 헤더 있음, 0: 헤더 없음
		global $pack_multiline;	// 합포가 여러줄인지  0: 한 줄, 1: 여러 줄
		global $sort_info;	// 정렬 순서
		global $trans_corp;	// 택배사
		global $trans_format;	// 포맷

	
    }

    function trans_select( $sel_format_id = 0)
    {
    	global $trans_corp, $connect;

		$query = "select * from trans_conf
                       where trans_corp =$trans_corp 
						order by name";

	    $result = mysql_query ( $query, $connect );

        $rows = mysql_num_rows( $result );

		if ( $rows )
        {
		    while ( $data = mysql_fetch_array ( $result ) )
		    {
			    echo "<option value='$data[format_id]'";
    
			    if ( $data[format_id] == $sel_format_id)
				    echo " selected ";

			    //echo ">" . iconv('utf-8','cp949',  $data[name]) . " </option>\n";
			    echo "> $data[name]</option>\n";
		    }
	    }
	    else
	    {
		    echo "<option value=99>default</option>";	
	    }
    }

    // format 저장함
    // 안 쓸 것 같은데?
    function set_format()
    {

    }
    
    function get_position( $trans_corp )
    {
        global $connect;

        $query = "select position_seq,position_transno from trans_conf where trans_corp='$trans_corp' and position_seq <> ''";
		$result = mysql_query ( $query );
		$data = mysql_fetch_array ( $result );
        return $data;
    }
    
    //
    // macro desinger의 item추가..
    function add_item()
    {
        global $format_id, $row, $col, $item, $title, $connect;
        
        //echo "f: $format_id, $row,$col, $item, $title";
        
        $query = "insert md_item 
                     set format_id = $format_id
                        ,row       = $row
                        ,col       = $col
                        ,item      = '$item'
                        ,title     = '$title'";
        
        mysql_query( $query, $connect );
        
        $query = "select * from md_item where format_id=$format_id and row=$row and col=$col";
        //echo $query;
        $result = mysql_query( $query , $connect );
        $data   = mysql_fetch_assoc( $result );
        
        echo json_encode( $data );
                                
    }
    

    
    //===============================================
    //
    // macro desinger item삭제.
    // date: 20011.10.24 - jk.ryu
    //
    function md_removeit()
    {
        global $connect, $format_id, $row, $col;   
        $row++;
        $col++;
        
        $query = "delete from md_item where format_id=$format_id and col=$col and row=$row";
        mysql_query( $query, $connect );
        $this->move_col_left( $format_id,$row, $col );
    }
    
    //====================================== 
    //
    // 나머지 column을 좌측 이동
    // 중간에 있는 item을 삭제 할 경우..나머지는 값을 하나씩 빼야 한다.
    //
    function move_col_left( $format_id,$row, $col )
    {
        global $connect;
        $query = "update md_item set col=col-1 where format_id=$format_id and row=$row and col > $col";
        mysql_query( $query, $connect );
    }
    
    //======================
    //
    // item 좌측 이동
    function moveleft()
    {
        global $connect, $format_id, $row, $col, $item_id;
        
        // echo "item: $item_id";
        
        /*
        $query = "select item_id from md_item where format_id=$format_id and row=$row and col=$col";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        */
        
        // 왼쪽의 자료를 오른쪽으로 옮기기..
        $row++;
        $query = "update md_item set col=col+1 where format_id=$format_id and row=$row and col=$col";
        echo $query;
        $result = mysql_query( $query, $connect );
        
        // 선택된 item을 왼쪽으로 옮기기
        $query = "update md_item set col=col-1 where format_id=$format_id and item_id=$item_id";
        $result = mysql_query( $query, $connect );
    }
    
    //
    // 선택된 아이템 우측 이동..
    function moveright()
    {
        global $connect, $format_id, $row, $col, $item_id;
        
        $row++;
        $col++;
        
        // 오른쪽 자료를 왼쪽으로 옮기기
        $col = $col + 1; // 오른쪽 자료 선택.
        $query = "update md_item set col=col-1 where format_id=$format_id and row=$row and col=$col";
        mysql_query( $query, $connect );
        
        // 선택 자료를 오른쪽으로 옮기기
        $query = "update md_item set col=col+1 where format_id=$format_id and item_id=$item_id";
        mysql_query( $query, $connect );
    }
    
    //
    // 사용자 정의 값 가져오기..
    function load_defined_value()
    {
        global $connect, $format_id, $item_id;
        
        $query = "select * from md_defined where format_id=$format_id and item_id=$item_id order by seq";
        //echo $query;
        $result = mysql_query( $query, $connect );
        
        $arr_data = array();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = $data;
        }
        
        echo json_encode( $arr_data );
    }
    
    //
    // 사용자 정의값 저장..
    //  2011-10-25 - jk
    function save_defined_value()
    {
        global $connect, $format_id, $item_id, $org_value, $target_value;
        
        $query = "insert md_defined 
                     set format_id    = $format_id
                       , item_id      = $item_id
                       , org_value    = '$org_value'
                       , target_value = '$target_value'";
        
        echo $query;
        
        mysql_query( $query, $connect );
        
    }
    
    //======================================
    //
    // 우측 이동..
    function move_col_swap( $row, $col )
    {
        
    }
        
    //===============================================
    //
    // macro desinger format을 읽어온다
    // date: 20011.10.21 - jk.ryu
    //
    function md_load_format()
    {
        global $connect, $trans_format, $trans_corp;

		$query = "select * from trans_conf where format_id='$trans_format' and trans_corp='$trans_corp'";
		
		$result = mysql_query ( $query );
		$data = mysql_fetch_array ( $result );

		$is_system       = $data[is_system];
		$is_header       = $data[is_header]?$data[is_header]:0;
		$pack_multiline  = $data[pack_multiline];
		$product_sum     = $data[product_sum];
		//$sortinfo        = $data[sortinfo];		
        //debug( "sortinfo1: $data[sortinfo] ");
        				
		$sortinfo        = $this->load_sort_info( $data[sortinfo] ); // sort를 combobox로 할 수 있도록 수정..2011.3.7 jk
		
		if ( !$data[position_seq] && !$data[position_transno] )
		{
		    $arr_position = $this->get_position( $trans_corp );   
		}
		
		$position_seq    = $data[position_seq]     ? $data[position_seq]     : $arr_position[position_seq];
		$position_transno= $data[position_transno] ? $data[position_transno] : $arr_position[position_transno];
        
        $arr_data                   = array();
        $arr_data[format_id]        = $data[format_id];
		$arr_data[is_header]        = $is_header;
		$arr_data[pack_multiline]   = $pack_multiline;
		$arr_data[sortinfo]         = $sortinfo;
		$arr_data[position_seq]     = $position_seq;
		$arr_data[position_transno] = $position_transno;
	    $arr_data[product_sum]      = $product_sum;
	    
		//=====================================
		//
		// date: 2007.3.13 -jk
		// trans_format에서 확인 후 없을 경우 sys_trans_format에서 읽어 온다
		//
		$query = "select * from md_item where format_id=$trans_format order by row, col";
		
		//echo $query;		
		$result = mysql_query( $query, $connect );
		$arr_data["rows"] = array();
		while ( $data = mysql_fetch_assoc( $result ) )
		{
		    $arr_data["rows"][$data[row]-1][] = $data;   
		}
		
	    echo json_encode( $arr_data);    
    }
    
	//===============================================
    //
    // format을 읽어온다
    // date: 2007.1.9 - jk.ryu
    //
    function load_format()
    {
		global $connect, $trans_format, $trans_corp;

		$query = "select * from trans_conf where format_id='$trans_format' and trans_corp='$trans_corp'";
		debug( $query );
		$result = mysql_query ( $query , $connect);
		$data = mysql_fetch_array ( $result );

		debug("is_header: $data[is_header]\n");

		$is_system       = $data[is_system];
		$is_header       = $data[is_header]?$data[is_header]:0;
		$pack_multiline  = $data[pack_multiline];
		$product_sum     = $data[product_sum];
		//$sortinfo        = $data[sortinfo];		
        //debug( "sortinfo1: $data[sortinfo] ");
        				
		$sortinfo        = $this->load_sort_info( $data[sortinfo] ); // sort를 combobox로 할 수 있도록 수정..2011.3.7 jk
		
		if ( !$data[position_seq] && !$data[position_transno] )
		{
		    $arr_position = $this->get_position( $trans_corp );   
		}
		
		$position_seq    = $data[position_seq]     ? $data[position_seq]     : $arr_position[position_seq];
		$position_transno= $data[position_transno] ? $data[position_transno] : $arr_position[position_transno];

		//=====================================
		//
		// date: 2007.3.13 -jk
		// trans_format에서 확인 후 없을 경우 sys_trans_format에서 읽어 온다
		//
		$query  = "select * from trans_format where format_id='$trans_format' and trans_id='$trans_corp' order by seq";
		$result = mysql_query ( $query, $connect );

		if ( mysql_num_rows ( $result ) == 0 )
		{
			$query  = "select * from sys_trans_format where format_id='$trans_format' and trans_id='$trans_corp' order by seq";
			$result = mysql_query ( $query, $sys_connect );
		}

		$arr_data                   = array();
		$arr_data[is_header]        = $is_header;
		$arr_data[pack_multiline]   = $pack_multiline;
		$arr_data[sortinfo]         = $sortinfo;
		$arr_data[position_seq]     = $position_seq;
		$arr_data[position_transno] = $position_transno;
	    $arr_data[product_sum]      = $product_sum;
		// date: 2007.1.11 - jk.ryu
		// date: 2009.7.9 - jk.ryu update
		// 합포 출력 여부
		// 헤더 출력 여부
		// 정렬순서 출력
		// data생성 , index는 65부터 시작
        // 64 + 1 : A를 의미함
		$cnt = 0;
		while ( $data   = mysql_fetch_array ( $result ) )
        {
			// 가장 마지막 ,는 삭제
			// if ( $cnt != 0 )
			// 무조건 제일 앞에 "," 추가
			$index = 64 + $data[seq];

			if ( $index < 91 )
				$key = "macro" . chr($index);
			else
				$key = "macroA" . chr($index-26);

			$arr_data[$key] = $data[macro_value];
			$cnt++;
		}
		echo json_encode( $arr_data);
    }
    
    //
    // 2011.3.7 - jkryu
    function load_sort_info( $sortinfo )
    {
        $sortinfo = preg_replace("/a.collect_date/"    ,"collect_date"      , $sortinfo );
        $sortinfo = preg_replace("/a.pack/"            ,"pack"              , $sortinfo );
        $sortinfo = preg_replace("/a.seq/"             ,"seq"               , $sortinfo );
        $sortinfo = preg_replace("/a.shop_id/"         ,"shop_id"           , $sortinfo );
        $sortinfo = preg_replace("/b.product_id/"      ,"product_id"        , $sortinfo );
        $sortinfo = preg_replace("/b.options/"         ,"options"           , $sortinfo );        
        $sortinfo = preg_replace("/a.options/"         ,"shopoption"        , $sortinfo );
        $sortinfo = preg_replace("/shop_options/"      ,"shopoption"        , $sortinfo );
        $sortinfo = preg_replace("/a.product_name/"    ,"shop_productname"  , $sortinfo );
        $sortinfo = preg_replace("/shop_product_name/" ,"shop_productname"  , $sortinfo );
        $sortinfo = preg_replace("/b.product_name/"    ,"product_name"      , $sortinfo );
        $sortinfo = preg_replace("/a.shop_product_id/" ,"shop_productid"    , $sortinfo );
        $sortinfo = preg_replace("/shop_product_id/"   ,"shop_productid"    , $sortinfo );
        
        debug( "sortinfo2:" . $sortinfo );
        
        return $sortinfo;
    }
}
?>
