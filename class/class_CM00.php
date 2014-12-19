<?
require_once "class_db.php";
require_once "class_top.php";
require_once "class_file.php";
/************
* brief: 자동 상품등록을 위한 카테고리 설정 부분
* date: 2009.3.3 - jk
* name: class_CM00.php
*************/

class class_CM00 extends class_top
{
    //////////////////////////////////////////////////////
    // 상품 리스트 
    function CM00()
    {
      global $template;
      $master_code = substr( $template, 0,1);
      include "template/C/CM00.htm";
    }

    /************************************
    * date: 2009-4.14
    * desc: 사용자 command 삭제
    *************************************/
    function remove()
    {
	global $connect, $seq;

	$query = "delete from auto_command_group where seq='$seq'";
	mysql_query( $query, $connect );

	$_arr = array( success => 1 );
	echo json_encode( $_arr );	
    }

    /************************
    * save job 
    ************************/
    function save_job()
    {
	global $connect, $shop_list, $name, $command;

	$name = iconv('utf-8', 'cp949', $name );

	$query = "insert into auto_command_group 
                     set name       = '$name'
                         ,command   = '$command'
                         ,reg_date  = Now() 
                         ,shop_list = '$shop_list'";
	mysql_query( $query, $connect );
	echo "save job";
    }

    function get_shop_list()
    {
        global $connect;
        $query = "select * from shopinfo 
                    where shop_id%10 in (1,6) 
                      and shop_id%100 < 10
                    order by shop_name";
	$result = mysql_query( $query, $connect );
	while ( $data = mysql_fetch_array( $result ) )
	{
	    echo "<input type=checkbox class=shop_info value='$data[shop_id]' onClick=\"javascript:add_shop2('$data[shop_id]','$data[shop_name]',this)\">[";
	    echo $data[shop_id] . "] $data[shop_name] <br>";
       	} 
    }

    /**********************************
    * date : 2009.3.19
    * desc : 작업
    **********************************/
    function upload()
    {
	global $_file;

	$obj 	  = new class_file();
 	$arr_data = $obj->upload();

	$_arr = array();
	$_arr['list']  = array();
	$_arr['error'] = 0;
	$_arr['msg']   = 'gogo';

	foreach ( $arr_data as $data )
	{
	    $_arr['list'][] = array( id=>$data[0] );
	}

	echo json_encode($_arr);
    }
 
    /**********************************
    * date : 2009.3.19
    * desc : 작업
    **********************************/
    function get_job_list()
    {
	global $connect, $page;

	$arr = array();
	$query = "select * from auto_command_group order by seq desc";
	$result = mysql_query( $query, $connect );

	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr[] = array( 
		name       => iconv('cp949', 'utf-8', $data[name] )
		,code      => $data[seq]
		,reg_date  => $data[reg_date]
		,command   => $data[command]
		,shop_list => $data[shop_list]
	    );
	}

	echo json_encode( $arr );
    }

/***************************************/

    /****************************
    * date: 2009.3.9 jk
    * category추가
    ****************************/
    function add_category()
    {
	global $connect, $name;
	$_name = iconv('utf-8', 'cp949', $name );

	$_arr = array();
	$_arr[name] = $name;

	$query = "insert user_category set name='$_name', crdate=Now()";
	mysql_query( $query, $connect );

	$query  = "select code from user_category order by code desc limit 1";
	$result = mysql_query( $query, $connect );
	$data   = mysql_fetch_array( $result );
	$_arr[code] = $data[code];

	echo json_encode( $_arr );
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

	    if ( $$sc_code <> "undefined" )
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
    function get_user_category()
    {
	global $connect;
	$query = "select * from user_category order by name";
	$result = mysql_query( $query, $connect );

	$arr_data = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr_data[] = array( code => $data[code], name=>iconv('cp949', 'utf-8', $data[name] ));
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
