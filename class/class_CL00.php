<?
require_once "class_db.php";
require_once "class_top.php";
/************
* brief: 자동 상품등록을 위한 카테고리 설정 부분
* date: 2009.3.3 - jk
* name: class_CL00.php
*************/

class class_CL00 extends class_top
{
    //////////////////////////////////////////////////////
    // 상품 리스트 
    function CL00()
    {
      global $template;
      $master_code = substr( $template, 0,1);
      include "template/C/CL00.htm";
    }

    /****************************
    * date: 2009.3.9 jk
    * category추가
    ****************************/
    function add_category()
    {
	global $connect, $name, $disp_code, $parent, $level;
	$_name = iconv('utf-8', 'cp949', $name );

	$_arr = array();
	$_arr[name] = $name;

	$query = "insert user_category set name='$_name', crdate=Now()";

	if ( $disp_code )
	    $query .= ", disp_code='$disp_code'";

	if ( $parent )
	    $query .= ", parent_code='$parent'";

	if ( $level )
	    $query .= ", level ='$level'";

	mysql_query( $query, $connect );

	$query  = "select * from user_category order by code desc limit 1";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_array( $result );
	$_arr[code] = $data[code];
	$_arr[disp_code] = $data[disp_code];
	$_arr[name]      = iconv('cp949', 'utf-8',$data[name]);
	$_arr[level]     = $data[level];

	echo json_encode( $_arr );
    }

    /************************************
    * date: 2009-3-18
    * desc: 사용자 category 삭제
    *************************************/
    function remove()
    {
	global $connect, $code;

	$query = "delete from user_shop_category where uc_code='$code'";
	mysql_query( $query, $connect );

	$query  = "delete from user_category where code=$code";
	mysql_query( $query, $connect );

	$_arr = array( success => 1 );
	echo json_encode( $_arr );	
    }

    /************************************
    * date: 2009-3-23
    * desc: 사용자 category 삭제
    *************************************/
    function del_sub_category( $code, &$arr )
    {
	global $connect;

	// 삭제 하고자 하는 카테고리의 하위 카테고리 존재 여부 check
	$query  = "select * from user_category where parent_code='$code'";
	$result = mysql_query( $query, $connect );
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr[] = $data[code];
	    $this->del_sub_category( $data[code],&$arr );
	}

	// 삭제 하고자 하는 카테고리 삭제
	$query  = "delete from user_category where code='$code'";
	$result = mysql_query( $query, $connect );
    }
    /************************************
    * date: 2009-3-23
    * desc: 사용자 category 삭제
    *************************************/
    function del_category()
    {
	global $connect, $code;
	$arr = array();

	// 삭제 하고자 하는 카테고리의 하위 카테고리 존재 여부 check
	$query  = "select * from user_category where parent_code='$code'";
	$result = mysql_query( $query, $connect );

	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr[] = $data[code];
	    $this->del_sub_category( $data[code],&$arr );
	}

	// 삭제 하고자 하는 카테고리 삭제
	$query  = "delete from user_category where code='$code'";
	$result = mysql_query( $query, $connect );

	$arr[] = $code;
	echo json_encode( $arr );
    }

    /************************************
    * date: 2009-3-10
    * desc: 사용자 category 저장
    *************************************/
    function save()
    {
	global $connect, $code, $shop_code, $target;

	$query = "delete from user_shop_category where shop_code=$shop_code and uc_code='$code'";

	if ( $target == "local" )
	    $query .= " and is_local=1";
	else
	    $query .= " and is_local=0";

	mysql_query( $query, $connect );

	for( $level=1; $level <=4; $level++)
	{
	    $sc_code = "c" . $level;
	    $sc_name = "c" . $level ."_name";
	    global $$sc_code, $$sc_name;

	    if ( $$sc_code <> "undefined" and $$sc_code <> 0 )
	    {
	        $query = "insert into user_shop_category
			    set uc_code   = $code
			       ,shop_code = $shop_code
                               ,sc_code   = '". $$sc_code . "'
                               ,sc_name   ='". iconv('utf-8','cp949', $$sc_name). "'
			       ,level     = $level ";	

		// target local 추가
		if ( $target == "local" )
		    $query .= " ,is_local=1";

	        mysql_query( $query, $connect );
	    }
	}
    }   

    function get_disp_code( $category )
    {
	global $connect;

	$query  = "select * from user_category where code ='$category'";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_array( $result );

	return $data[disp_code];
    } 
 
    /**********************************
    * date: 2009.3.9 
    * 사용자 카테고리 가져오기
    *   => level이 1인 category까지 가져와야 함
    **********************************/
    function get_category_code()
    {
	global $connect, $disp_code;
	$arr = array();

  	$query  = "select * from user_category where disp_code='$disp_code'";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_assoc( $result );	

	$arr[] = array( level         => $data[level]
			, code        => $data[code]
			, disp_code   => $data[disp_code]
			, parent_code => $data[parent_code]);

	if ( $data[level] > 1 )
	    $this->get_sub_category_code( $data[parent_code] , &$arr); 

	echo json_encode( $arr );
    }

    function get_sub_category_code($parent, &$arr)
    {
	global $connect;
	$query  = "select * from user_category where code='$parent'";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_assoc( $result );	

	$arr[] = array( level=>$data[level]
			, code=>$data[code]
			, disp_code=>$data[disp_code]
			, parent_code=>$data[parent_code]);

	if ( $data[level] > 1 )
	    $this->get_sub_category_code( $data[parent_code] , &$arr); 
    }

    /**********************************
    * date: 2009.3.9 
    * 사용자 카테고리 가져오기
    *
    **********************************/
    function get_user_shop_category()
    {
	global $connect, $code;
	$arr = array();

	$query = "select * from user_category where code = '$code'";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_assoc( $result );

	$arr[code] = $data[code];
	$arr[name] = iconv('cp949', 'utf-8',$data[name]);

	$query = "select * from user_shop_category where uc_code = '$code'";
	$result = mysql_query( $query, $connect );

	$arr['success'] = array(1=>0, 6=>0);
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $_code = $data[shop_code];
	    if ( $data[is_local] )
	    	$_code = "local".$data[shop_code];

	    $arr[$_code][] = array( 
		sc_code  => $data[sc_code]
		,sc_name => iconv('cp949', 'utf-8',$data[sc_name])
		,level   => $data[level] 
	    );

	    $arr['success'][$_code] = 1;
	}

	echo json_encode( $arr );
    }
   
    /**********************************
    * date: 2009.3.9 
    * 사용자 카테고리 가져오기
    *
    **********************************/
    function expand()
    {
	global $connect, $level, $parent_code;
	$query = "select * from user_category ";

	
	if ( $parent_code )
	    $query .= " where parent_code =$parent_code";

	if ( $level )
	{
	    $level = $level + 1;
	    $query .= " and level = $level";
	}

	$query .= " order by name desc";

	$result = mysql_query( $query, $connect );

	$arr_data = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr_data[] = array( code       => $data[code]
 				, disp_code => $data[disp_code]
 				, level     => $data[level]
				, name      => iconv('cp949', 'utf-8', $data[name] ));
	}
	echo json_encode( $arr_data );
    }
    /**********************************
    * date: 2009.3.9 
    * 사용자 카테고리 가져오기
    *
    **********************************/
    function get_user_category( $level='' )
    {
	global $connect, $level, $parent_code;
	$query = "select * from user_category ";

	if ( $level )
	{
	    if ( $level == "null" )
	        $query .= " where level=1";
	    else
	        $query .= " where level=$level";
	}

	if ( $parent_code )
	{
	    if ( $parent_code == 'null')
	        $query .= " and parent_code is null";
	    else
	        $query .= " and parent_code=$parent_code";
	}

	$query .= " order by name";
	$result = mysql_query( $query, $connect );

////  echo $query;

	$arr_data = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr_data[] = array( code       => $data[code]
 				, disp_code => $data[disp_code]
 				, level     => $data[level]
				, name      => iconv('cp949', 'utf-8', $data[name] ));
	}
	echo json_encode( $arr_data );
    }

    /**********************************
    * date: 2009.3.9 
    * local category 가져오기
    *
    **********************************/
    function get_local_category()
    {
	global $level, $shop_code, $level, $parent_code, $connect;
 
	$query = "select * from category 
                   where shop_code='$shop_code' ";

	if ( $level )
	    $query .= " and level='$level' ";

	if ( $parent_code )
	    $query .= " and parent_code='$parent_code' ";

	$query .= " order by name";

	$result = mysql_query( $query, $connect );
	$arr_category = array();
	// $arr_category['query']    = $query;
	$arr_category['category'] = array();

	while ( $data   = mysql_fetch_assoc( $result ) )
	{
	    $arr_category['category'][] = array( code=> $data[code], name=>iconv('cp949', 'utf-8', $data[name]));
	}

	echo json_encode( $arr_category );	
    }

    /**********************************
    * date: 2009.3.9 
    * 판매처 카테고리 가져오기
    *
    **********************************/
    function get_shop_category()
    {
	global $level, $shop_code, $level, $parent_code;
	$connect = class_db::connect("61.109.255.60","mento","mento");
 
	$query = "select * from category 
                   where shop_code='$shop_code' ";

	if ( $level )
	    $query .= " and level='$level' ";

	if ( $parent_code )
	    $query .= " and parent_code='$parent_code' ";

	$query .= "order by name";

	$result = mysql_query( $query, $connect );
	$arr_category = array();
	// $arr_category['query']    = $query;
	$arr_category['category'] = array();

	while ( $data   = mysql_fetch_assoc( $result ) )
	{
	    $arr_category['category'][] = array( code=> $data[code], name=>iconv('cp949', 'utf-8', $data[name]));
	}

	echo json_encode( $arr_category );	
    }

} 

?>
