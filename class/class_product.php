<?
require_once "class_file.php";
require_once "class_top.php";
require_once "class_supply.php";

// date: 2006.7.19
// jk.ryu
class class_product{

    function enable_sale( $product_id )
    {
        global $connect;
        
        $query = "select enable_sale from products where product_id='$product_id'";   
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[enable_sale] == 0 )
            return "[품절]";
    }

    //*********************************************
    // date :2009.7.28 - jk
    // 
    function get_price_arr( $product_id, $shop_id, $collect_date = "")
    {
        global $connect;
        
        // org_id, product_id 여부를 판단한
        $query = "select product_id, org_id from products where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        if ( $data[org_id] )
            $product_id = $data[org_id];
        
        if ( $collect_date )
            $_date = $collect_date;
        else
            $_date = date('Y-m-d');
            
        $query = "select * 
                    from price_history 
                   where start_date <= '$_date' 
                     and end_date   >= '$_date'
                     and shop_id     = '$shop_id'
                     and product_id  = '$product_id'
                     order by update_time desc, seq desc
                     ";
        $result = mysql_query( $query, $connect );
        $rows   = mysql_num_rows( $result );
        
        
        
        if ( !$rows )
        {
            // 값이 없을경우 대표 가격을 가져온다.
            $query = "select * from price_history where product_id='$product_id' 
                         and (shop_id='' or shop_id=0 or shop_id is null)";
            
            
            $result = mysql_query( $query, $connect );
            $rows   = mysql_num_rows( $result );
            
            if ( !$rows )
            {
                $query = "select org_price,supply_price,shop_price from products where product_id='$product_id'";
                
                
                $result = mysql_query( $query, $connect );
            }
        }
        
        $data = mysql_fetch_assoc( $result );
        
       // 원가
       $query_org = "select org_price from products where product_id='$product_id'";
       $result_org = mysql_query ( $query_org, $connect);
       $data_org = mysql_fetch_array ( $result_org);
        
        //if ( $product_id == "137081" );
        $arr  = array( 
            org_price      => $data_org[org_price]
            ,supply_price  => $data[supply_price]
            ,shop_price    => $data[shop_price]
        );
        return $arr;
    }

    var $m_items = "";
    function class_product()
    {
	$this->m_items = array( 
                "name"          => "like",
                "options"       => "like",
                "supply_code"   => "",
                "product_id"    => "",
                "product_id_3pl"=> "",
                "3pl_use"       => "",
                "3pl_use"       => "",
	);
    }

    // 상품명 중복검사
    function dup_check( $name, $product_id='' )
    {
    	global $connect;
    
    	$query  = "select * from products where name='$name' and is_delete=0";
    	if( $product_id )
    	    $query .= " and product_id<>'$product_id' and org_id<>'$product_id'";

    	$result = mysql_query( $query, $connect );
    	$data   = mysql_fetch_array( $result );
    	if ( $data[name] )
    	    return 1;
    	else
    	    return 0;
    }

    // 옵션 중복검사
    function dup_check_options( $options, $product_id, $org_id )
    {
    	global $connect;
        
    	$query  = "select * from products where org_id='$org_id' and options='$options' and is_delete=0 and product_id<>'$product_id'";
    	$result = mysql_query( $query, $connect );
    	$data   = mysql_fetch_array( $result );
    	if ( $data[product_id] )
    	    return 1;
    	else
    	    return 0;
    }

    // 바코드 중복검사
    function dup_check_barcode( $barcode, $product_id='' )
    {
    	global $connect;
    
        // 바코드가 없으면 통과
        if( !$barcode )  return 0;
        
    	$query  = "select * from products where barcode='$barcode' and is_delete=0";
    	if( $product_id )
    	    $query .= " and product_id<>'$product_id'";
    	$result = mysql_query( $query, $connect );
    	if ( mysql_num_rows($result) > 0 )
    	    return 1;
    	else
    	    return 0;
    }

    //******************************
    // date: 2009.5.2 - jk
    function delete( $product_id )
    {
	global $connect;

	// 원 상품 삭제
	$query = "delete from products where product_id='$product_id'";
	mysql_query( $query, $connect );

	// 옵션 상품 삭제
	$query = "delete from products where org_id='$product_id'";
	mysql_query( $query, $connect );

	// price_history삭제
	$query = "delete from price_history where product_id='$product_id'";
	mysql_query( $query, $connect );

    }

    //*********************************
    // 하부상품의 상품 코드 
    // 2009.7.30 - jk
    function get_child_product_id( $product_id )
    {
        global $connect;
        
        // org_id   
        $product_ids = "";
        $query = "select product_id from products where org_id='$product_id'";
        $result = mysql_query($query, $connect);
        
        if ( mysql_num_rows($result))
        {
            while ( $data = mysql_fetch_assoc( $result ) )
            {
                    $product_ids .= $product_ids ? ",":"";
                    $product_ids .= "'" . $data[product_id] . "'";
            }
        }
        return $product_ids;
    }

    function create_barcode( $product_id )
    {
    	global $connect;
    	$query = "select name, product_id, barcode from products where org_id='$product_id'";
    	$result = mysql_query( $query, $connect);
  
    	while ( $data = mysql_fetch_array( $result ) )
    	{
    	    // 이미 바코드가 있으면 건너뛴다
    	    if( $data[barcode] > '' )  continue;
    	    
	    	$barcode = class_top::get_barcode($data[product_id]);
    	    $query = "update products 
                             set barcode='$barcode'
                           where product_id='$data[product_id]'";
    	    mysql_query ( $query, $connect );
    	}
    	
    }

    //======================================
    // 개수 확인
    // 2007.11.21 - jk
    function get_count( $arr_items, $is_utf8=0 )
    {
	global $connect;
	$query = "select count(*) cnt from products ";

	if ( $is_utf8 )
	    $query.= $this->build_option_utf8( $arr_items );
	else
	    $query.= $this->build_option( $arr_items );

	$result = mysql_query ( $query, $connect);
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    //=======================================
    // 전체 list 정보 가져오기
    // 2008.7.22 - jk
    function get_total_list ( $arr_items, $is_utf8=0 )
    {
	global $connect, $start, $limit;
	$query = "select * from products ";

	if ( $is_utf8 )
	    $query.= $this->build_option_utf8( $arr_items );
	else
	    $query.= $this->build_option( $arr_items );

	$result = mysql_query ( $query, $connect) or die ( mysql_error() );
	return $result;
    }


    //=======================================
    // list 정보 가져오기
    // 2007.11.21 - jk
    function get_list ( $arr_items, $is_utf8=0 )
    {
	global $connect, $start, $limit;
	$query = "select * from products ";


	if ( $is_utf8 )
	    $query.= $this->build_option_utf8( $arr_items );
	else
	    $query.= $this->build_option( $arr_items );

	if ( $limit )
	    $query .= " limit $start, $limit";
	else
	    $query .= " limit 0, 30";

	$result = mysql_query ( $query, $connect) or die ( mysql_error() );
	return $result;
    }

    //=============================================
    // query를 위한 option생성 for utf8
    // date: 2008.7.8 - jk
    function build_option_utf8( $arr_items )
    {
	$_options = "";
	$i = 0;

        foreach ( $arr_items as $item=>$_opt )
        {
	    if ( $_opt == "null" )
	        $$item = "null";
	    else
                global  $$item;

            if ( $$item )
            {
                if ( $_cnt == 0 )
                        $_options .= " where ";
                else
                        $_options .= " and ";

                if ( $_opt == "like" )
                        $_options .= "$item like '%". iconv( 'utf-8', 'cp949', $$item)."%'";
		else if ( $_opt == "null" )
                        $_options .= "($item is null or $item = '')";
		else if ( $_opt == "zero" )
                        $_options .= "$item = '0'";
                else
                        $_options .= "$item = '". iconv('utf-8', 'cp949',  $$item)."'";

                $_cnt++;
            }
        }

	return $_options;
    }


    //=============================================
    // query를 위한 option생성
    // date: 2007.11.21 - jk
    function build_option( $arr_items )
    {
	$_options = "";
	$i = 0;

        foreach ( $arr_items as $item=>$_opt )
        {
            global  $$item;

	    $$item = $$item ? $$item : $_opt;
            if ( $$item )
            {
                if ( $_cnt == 0 )
                        $_options .= " where ";
                else
                        $_options .= " and ";

                if ( $_opt == "like" )
                        $_options .= "$item like '%". $$item."%'";
		else if ( $_opt == "null" )
                        $_options .= "$item = null";
		else if ( $_opt == "zero" )
                        $_options .= "$item = '0'";
                else
                        $_options .= "$item = '". $$item."'";

                $_cnt++;
            }
        }
	return $_options;
    }

    //=================================================
    // 상품 data update
    // 2007.11.20 - jk
    function sync_product( $arr_datas, $product_id )
    {
	global $connect;
	$query = "update products set ";
	
	$i = 0;
	foreach ( $arr_datas as $key=>$value )
	{
	    if ( $value )
	    {
	    	if ( $i != 0 ) 
		    $query .= ",";

	    	$query .= " $key='$value' ";
	    	$i++;
	    }
	    else if ( $value == "NULL" )
	    {
		if ( $i != 0 ) 
		    $query .= ",";

	    	$query .= " $key=NULL ";
	    	$i++;
  	    }
	    
	}

	$query .= " where product_id='$product_id'";
	mysql_query ( $query, $connect ) or die ( mysql_error() );
    }
  //====================================================
  //
  // for ckcompany
  // date: 2007.11.7 - jk
  //
  function build_product_id( $product_name, $option)
  {
	preg_match( "/\[(.*)\]/",$product_name, $matches );
	
	// | 과 /의 차이점은 무엇이지?
	// U 옵션은? Enter the letter "U" for Pattern Matching and then we are at
	// Unique의 옵션일까?
	$option_key = preg_match_all( "|\((.*)\)+|U",$option, $matches2 );

	$product_key = $matches[1];
	$_arr = $matches2[1];
	foreach ( $_arr as $key )
	{
		$product_key .= $key; 
	}

	// 공백 없애 주기
	$product_key = str_replace( " ", "", $product_key );	
	return $product_key;
  }
  //====================================================
  //
  // 상품 복사 할 때 사용 for midan
  //
  // 상품 복사
  function copy_product( $product_id )
  {
	// 상품 정보 복사
    // 새로운 상품 번호 return
	$new_product_id = $this->copy_product_info( $product_id );

	// copy_thumbnail
	$this->copy_thumbnail( $product_id, $new_product_id );

 	// 가격 정보 복사
	$this->copy_price( $product_id, $new_product_id );

	return $new_product_id;
   }

  // max 값으로 부터 product_id 반환
  function get_product_id_max( $max )
  {
	global $connect;
	$query = "select product_id from products where max='$max'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[product_id];
  }

  // max값을 가져옴
  function get_max()
  {
	global $connect;
	$query = "select max(max) m from products";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[m] + 1;
  }

  // 상품 정보 복사 후 max값 반환
  function copy_product_info( $product_id )
  {
	global $connect;
	
	$query = "select * from products where product_id='$product_id'";
	$result = mysql_query( $query, $connect );
	$data = mysql_fetch_array ( $result );

    // 중복되지 않는 상품명 구하기
    $name = $data[name] . "-사본";
    $query_name = "select name from products where name like '$name%' and is_delete=0";
    $result_name = mysql_query($query_name, $connect);

    // 비슷한 유형 있을경우 1 증가한 숫자를 붙인다.
    if( mysql_num_rows($result_name) )
    {
        $max_copy_num = 1;
        while( $data_name = mysql_fetch_assoc($result_name) )
        {
            $tail = substr($data_name[name], strlen($name));
            if( $max_copy_num <= $tail )  $max_copy_num = $tail + 1;
        }
        $name .= $max_copy_num;
    }
    // 비슷한 유형 없으면 1을 붙인다.
    else
    {
        $name .= "1";
    }
    
	$max = $this->get_max();
	$new_product_id = sprintf ( "%05d", $max );
	$query = "insert products 
	             set product_id     = '$new_product_id'       ,
			         max            = '$max'                  ,
			         supply_code    = '$data[supply_code]'    ,
			         reg_date       = now()                   ,
			         reg_time       = now()                   ,
			         last_update_date= now()                   ,
			         name           = '$name'                 ,
			         origin         = '$data[origin]'         ,
			         brand          = '$data[brand]'          ,
			         options        = '$data[options]'        ,
			         org_price      = '$data[org_price]'      ,
			         supply_price   = '$data[supply_price]'   ,
			         shop_price     = '$data[shop_price]'     ,
			         market_price   = '$data[market_price]'   ,
			         product_desc   = '". addslashes($data[product_desc]) . "'   ,
			         img_500        = '$data[img_500]'        ,
			         img_desc1      = '$data[img_desc1]'      ,
			         img_desc2      = '$data[img_desc2]'      ,
			         img_desc3      = '$data[img_desc3]'      ,
			         img_desc4      = '$data[img_desc4]'      ,
			         img_desc5      = '$data[img_desc5]'      ,
			         img_desc6      = '$data[img_desc6]'      ,
			         enable_sale    = '$data[enable_sale]'    ,
                     weight         = '$data[weight]'         ,
                     is_url_img     = '$data[is_url_img]'     ,
                     is_delete      = '$data[is_delete]'      ,
                     trans_code     = '$data[trans_code]'     ,
                     is_represent   = '0'                     ,
                     stock_manage   = '0'                     ,
                     enable_stock   = '$data[enable_stock]'   ,
                     no_sync        = '$data[no_sync]'        ,
                     no_stock_sync  = '$data[no_stock_sync]'  ,
                     barcode        = '$new_product_id'       ,
                     supply_options = '$data[supply_options]' ,  
                     stock_alarm1   = '$data[stock_alarm1]'   ,  
                     stock_alarm2   = '$data[stock_alarm2]'   ";  
    //**********************************************
    // 바코드는 중복되면 안되므로 공백
    // =>
    // 바코드는 상품코드로 입력한다. 2013-11-06 
    //**********************************************
	mysql_query( $query, $connect ) or die ( "hah" );
	return $new_product_id;
   }

    //=======================================================
    // 가격 복사
    function copy_price( $product_id, $new_product_id )
    {
        global $connect;
        
        $query = "select * from price_history where product_id='$product_id'";	
        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $query = "insert price_history 
                         set org_price     = '$data[org_price]',
              	             supply_price  = '$data[supply_price]', 
              	             shop_price    = '$data[shop_price]', 
              	             is_free_deliv = '$data[is_free_deliv]',
              	             tax           = '$data[tax]', 
              	             product_id    = '$new_product_id', 
              	             supply_code   = '$data[supply_code]',
              	             start_date    = '$data[start_date]', 
              	             end_date      = '$data[end_date]', 
              	             shop_id       = '$data[shop_id]', 
              	             update_time   = Now()";
            mysql_query ( $query, $connect );
        }

        $query = "select * from org_price_history where product_id='$product_id'";	
        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $query = "insert org_price_history 
                         set product_id    = '$new_product_id', 
              	             org_price     = '$data[org_price]',
              	             start_date    = '$data[start_date]', 
              	             worker        = '$data[worker]', 
              	             work_date     = '$data[work_date]', 
              	             is_base       = '$data[is_base]'";
            mysql_query ( $query, $connect );
        }
    }

  function copy_thumbnail( $product_id , $new_product_id )
  {
	if ( _DOMAIN_ == "ezadmin" )
		$_upload_path = "/home/ezadmin/public_html/shopadmin/uploads";
	else
		$_upload_path = "/home/ezadmin/public_html/shopadmin/uploads/" . _DOMAIN_;


	if (is_file("$_upload_path/$product_id" . "_100.jpg") )
	{
		copy ( 	"$_upload_path/$product_id" . "_100.jpg",  "$_upload_path/$new_product_id" . "_100.jpg" );
	} 
	else
	{
		echo "\ncopy fail\n<br>";
	}
  }

  // 상품명 검색시 사용
  function get_product_ids ( $keyword )
  {
	global $connect;
	$query = "select product_id from products where name like '%$keyword%'";

	$result = mysql_query ( $query, $connect );
	$i=0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
		if ( $i == 0 )
			$str .= "'";
		else
			$str .= ",'";	

		$str .= $data[product_id];
		$str .= "'";

		$i++;
	}

	return $str;
  }

    /////////////////////////////////////////////
    // get info
    function get_barcode( $product_id )
    {
	global $connect;
	$query = "select barcode from products where product_id='$product_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[barcode];
    }
    
    /////////////////////////////////////////////
    // get info
    function get_origin( $product_id )
    {
    	global $connect;
    	
    	$query = "select origin from products where product_id='$product_id'";
    	
    	$result = mysql_query ( $query, $connect );
    	$data = mysql_fetch_assoc ( $result );
    	return $data[origin];
    }
    
    /////////////////////////////////////////////
    // get info
    //
    function get_info ( $product_id, $parameter='' )
    {
    	global $connect;
    	$parameter = $parameter ? $parameter : "*";
    	$query = "select $parameter,org_id from products where product_id='$product_id'";
    	$result = mysql_query ( $query, $connect );
    	$data = mysql_fetch_assoc ( $result );
    	return $data;
    }

  // 품절 상품 목록 
  function get_soldout_list( )
  {
	global $connect;
	$query = "select product_id from products where enable_sale=0";
	$result = mysql_query ( $query, $connect );
	$i=0;
	while ( $data = mysql_fetch_array ( $result ) )
	{
		if ( $i == 0 )
			$str .= "'";
		else
			$str .= ",'";	

		$str .= $data[product_id];
		$str .= "'";

		$i++;
	}

	return $str;
  }

  // 상품명 return
  // 상품명과 option을 reference로 return함
  function get_brand_name ( $id )
  {
        global $connect;
        $query = "select brand from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data   = mysql_fetch_array ( $result );
        return $data[brand];
  }


  // 상품명 return
  // 상품명과 option을 reference로 return함
  function get_product_name2 ( $id , &$name, &$option )
  {
        global $connect;
        $query = "select name,options,enable_sale from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data   = mysql_fetch_array ( $result );
        $name   = $data[name];
        $option = $data[options];
  }

  function get_product_infos( $id )
  {
        global $connect;
        $query = "select name,options,enable_sale from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data   = mysql_fetch_array ( $result );
        $name   = $data[name];

	$infos = array ( name => $data[name], options => $data[options], enable_sale => $data[enable_sale] );
	return $infos;
  }

  // 상품명 return
  function get_product_name ( $id )
  {
        global $connect;
        $query = "select name from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data = mysql_fetch_array ( $result );
        return  $data[name];
  }

  function get_product_name_option($id, &$name, &$option)
  {
        global $connect;
        $query = "select name,options from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data = mysql_fetch_array ( $result );
        $name = $data[name];
        $option = $data[options];
  }

  // 상품 정보를 return
  function get_product_info($connect, $id)
  {
        $query = "select * from products where product_id='$id'";
        $result = mysql_query ( $query , $connect );
        $data = mysql_fetch_array ( $result );
        return $data;
  }

  // 이미지 출력
  function disp_image( $img_target , $width=100, $height=100 , $isLocal=1 )
  {
      //if (file_exists($img_target))
      if ( $img_target )
        echo "<img src='$img_target' width='$width' height='$height'>";
      else
	    echo "<img src='/images/noimage2.gif' width='$width' height='$height'>";
  }

  // 이미지 링크 출력
  function view_img( $url )
  {
    echo "<a href=$url target=new>$url</a>";
  }

  //====================================== 
  // 상품의 현재 가격 관련 정보를 받는다 
  // Date: 2006.7.26 - jk.ryu
  function get_current_vendor( &$org_price, &$shop_price, &$trade_fee  )
  {
    global $connect, $product_id;

    $query = "select * from price_history 
               where product_id = '$product_id' and org_price is not null
               order by update_time desc limit 1";



    $result = mysql_query ( $query, $connect);
    $data = mysql_fetch_array ( $result );

    $org_price  = $data[org_price];
    $shop_price = $data[shop_price];
    $trade_fee  = $data[trade_fee];
  } 

  //====================================== 
  // 가격 관련 정보를 받는다 
  // type: vendor(하부업체) , op(관리자)
  function get_pricehistory( $product_id, $type="vendor" )
  {
    global $connect;
    $query = "select * from price_history 
               where product_id = '$product_id'";
  
    if ( $type == "vendor" )
      $query .= " and org_price is not null";
    else
      $query .= " and org_price is null";

    $result = mysql_query ( $query, $connect );
    return $result;
  }

  function get_shop_price( $product_id )
  {
  	global $connect;
	$query = "select shop_price from products where product_id='$product_id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	return $data[shop_price];
  }

  //====================================== 
  // 대표 가격 관련 정보를 받는다 
  // 
  function get_primary_price( $product_id )
  {
    global $connect;
    $query = "select * from price_history 
               where product_id = '$product_id' 
                 and( shop_id='' or shop_id is null) 
                 and start_date < Now() and end_date > Now()";


    $result = mysql_query ( $query, $connect );
    $data = mysql_fetch_array( $result );

# print_r( $data );

    return $data;
  }


  //====================================== 
  // 가격 관련 정보를 받는다 
  // 
  function get_priceinfo( $seq )
  {
    global $connect;
    $query = "select * from price_history where seq = '$seq'";
    $result = mysql_query ( $query, $connect );
    $data = mysql_fetch_array( $result );

# print_r( $data );

    return $data;
  }

  // 이미지 삭제
   function img_delete()
   {
      global $connect, $product_id, $img_id;

      // update db
      $query = "update products set $img_id = '' where product_id='$product_id'";
      mysql_query ( $query, $connect ); 

//    echo $query;
 
      // delete file 
//      class_file::del ( $img );
      
      // global $top_url;
      // $this->opener_redirect ( "template.htm?template=C201&id=$id&top_url=$top_url" );
      // $this->jsAlert("삭제 되었습니다.");
      // $this->closewin();
  }

  function get_supply_id($product_id)
  {
    global $connect;
    $query = "select supply_code from products where product_id='$product_id'";
    $result = mysql_query ( $query, $connect );
    $data = mysql_fetch_array( $result );
    return $data[supply_code];
  }

    // 상품 정렬 기준 콤보박스 
    function get_sort_select($supply_address=0)
    {
	    global $products_sort;

		if( $products_sort )
	        $ps = $products_sort;
		else 
			$ps = $_SESSION[PRODUCTS_SORT];	

        $str = "
            <select name=products_sort id=products_sort>
                <option value=1 " . ( $ps == 1 ? "selected" : "" ) . ">상품명 </option>
                <option value=2 " . ( $ps == 2 ? "selected" : "" ) . ">공급처 > 상품명 </option>
                <option value=3 " . ( $ps == 3 ? "selected" : "" ) . ">등록일 > 상품명 </option>
                <option value=4 " . ( $ps == 4 ? "selected" : "" ) . ">등록일 > 공급처 > 상품명 </option>                
                <option value=8 " . ( $ps == 8 ? "selected" : "" ) . ">로케이션 </option>
                <option value=9 " . ( $ps == 9 ? "selected" : "" ) . ">로케이션 > 상품명 </option>
        ";// <option value = 5,6,7> F200.htm 에서 사용중
        if( $supply_address == 1 )
            $str .= "<option value=5 " . ( $ps == 5 ? "selected" : "" ) . ">공급처주소 > 상품명 </option>";
        
        $str .= "</select>";
        return $str;
    }
    
    // 상품 삭제
    function delete_product($product_id)
    {
        global $connect;
        
        // 상품정보
        $data = $this->get_info2($product_id);
        if( !$data )  return 1;  // 이미 삭제된 상품입니다.

        // 상품코드 리스트
        if( $data[is_represent] )
            $this->get_option_id($product_id, &$id_arr, &$id_str);
        else
            $id_str = "'" . $product_id . "'";

        // 주문, 재고 확인
        $ret = $this->check_order_stock($id_str);
        if( $ret )  return 2;
        
        // 매칭삭제
        $this->delete_match_info($id_str);
        
        // 상품삭제
        $query = "update products set is_delete=1, delete_date=now() where product_id in ($id_str)";
        mysql_query($query, $connect);
    }

    // 옵션상품코드 리스트
    function get_option_id($org_id, &$id_arr, &$id_str, $with_org_id=true)
    {
        global $connect;
        
        $id_arr = array();
        $id_str = "";

        // 대표상품코드 포함
        if( $with_org_id )
        {
            $id_arr[] = $org_id;
            $id_str .= "'" . $org_id . "',";
        }
        
        // 옵션상품코드 구하기
        $query = "select product_id from products where org_id = '$org_id'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $id_arr[] = $data[product_id];
            $id_str .= "'" . $data[product_id] . "',";
        }
        $id_str = substr($id_str,0,-1);
    }
    
    // 상품정보 가져오기. 삭제상품 제외
    function get_info2($product_id)
    {
        global $connect;
        
        $query = "select * from products where product_id='$product_id' and is_delete=0";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        return $data;
    }
    
    // 상품의 주문, 재고 확인
    function check_order_stock($id_str, $check_stock=0)
    {
        global $connect;
        
        // 주문확인
        $query = "select a.seq 
                    from orders a,
                         order_products b
                   where a.seq = b.order_seq and 
                         a.status in (1,7) and
                         a.order_cs not in (1,3) and 
                         b.product_id in ($id_str)";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) > 0 )
            return 1;  // 해당 상품의 주문이 있습니다.

        if( $check_stock )
        {
            // 재고확인
            $query = "select sum(stock) sum from current_stock where product_id in ($id_str) group by product_id";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( $data[sum] > 0 )
                    return 2;  // 해당 상품의 재고가 있습니다.
            }
        }
        
        return 0;
    }

    function show_multi_category()
    {
        global $connect, $depth, $category1, $category2, $category3;
        
        $val = array();
        
        // category1
        if( $depth == 2 || $depth == 3 )
        {
            $val["category1"] = "<option value=0 ".($category1 == 0 ? "selected" : "")." >(전체)</option>";
            $query = "select a.name val_name,
                             a.search_id val_id
                        from multi_category a
                             left outer join multi_category b on b.depth=2 and a.seq=b.parent
                             left outer join multi_category c on c.depth=3 and b.seq=c.parent
                       where a.depth = 1 ";
            if( $category2 > 0 )
                $query .= " and b.search_id = $category2";
            if( $category3 > 0 )
                $query .= " and c.search_id = $category3";
            $query .= " group by val_id order by val_name";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( !$data[val_id] )  continue;
                $val["category1"] .= "<option value=$data[val_id] ".($category1 == $data[val_id] ? "selected" : "")." >$data[val_name]</option>";
            }
        }
        
        // category2
        if( $depth == 1 || $depth == 3 )
        {
            $val["category2"] = "<option value=0 ".($category2 == 0 ? "selected" : "")." >(전체)</option>";
            $query = "select b.name val_name,
                             b.search_id val_id
                        from multi_category a
                             left outer join multi_category b on b.depth=2 and a.seq=b.parent
                             left outer join multi_category c on c.depth=3 and b.seq=c.parent
                       where a.depth = 1 ";
            if( $category1 > 0 )
                $query .= " and a.search_id = $category1";
            if( $category3 > 0 )
                $query .= " and c.search_id = $category3";
            $query .= " group by val_id order by val_name";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( !$data[val_id] )  continue;
                $val["category2"] .= "<option value=$data[val_id] ".($category2 == $data[val_id] ? "selected" : "")." >$data[val_name]</option>";
            }
        }
        
        // category3
        if( $depth == 1 || $depth == 2 )
        {
            $val["category3"] = "<option value=0 ".($category3 == 0 ? "selected" : "")." >(전체)</option>";
            $query = "select c.name val_name,
                             c.search_id val_id
                        from multi_category a
                             left outer join multi_category b on b.depth=2 and a.seq=b.parent
                             left outer join multi_category c on c.depth=3 and b.seq=c.parent
                       where a.depth = 1 ";
            if( $category1 > 0 )
                $query .= " and a.search_id = $category1";
            if( $category2 > 0 )
                $query .= " and b.search_id = $category2";
            $query .= " group by val_id order by val_name";
            $result = mysql_query($query, $connect);
            while( $data = mysql_fetch_assoc($result) )
            {
                if( !$data[val_id] )  continue;
                $val["category3"] .= "<option value=$data[val_id] ".($category3 == $data[val_id] ? "selected" : "")." >$data[val_name]</option>";
            }
        }
        
        echo json_encode($val);
    }

    // 상품명 수정
    function update_product_info()
    {
        global $connect, $field, $product_id, $new_val;
        
        $val = array();
        
        // 상품코드 리스트
        $this->get_product_list($product_id, &$product_list, &$product_list_org, &$org_id);
        
        //#####################
        // 상품명 수정
        //#####################
        if( $field == "product_name" )
        {
            // 중복이름 검사
            if( !$_SESSION[DUP_PRODUCT_NAME] )
            {
                $query = "select product_id from products where name='$new_val' and is_delete=0";
                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) )
                {
                    $val["error"] = 1;
                    echo json_encode($val);
                    exit;
                }
            }
            
            $query = "update products set name='$new_val' where product_id in ($product_list_org)";
            if( mysql_query($query, $connect) )
            {
                $val["error"] = 0;
                $val["product_list"] = str_replace("'","",$product_list);
            }
            else
                $val["error"] = 2;
        }
        //#####################
        // 옵션 수정
        //#####################
        else if( $field == "options" )
        {
            // 중복옵션 검사
            $query = "select product_id from products where product_id in ($product_list) and options='$new_val' and is_delete=0";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) )
            {
                $val["error"] = 11;
                echo json_encode($val);
                exit;
            }
            
            $query = "update products set options='$new_val' where product_id='$product_id'";
            if( mysql_query($query, $connect) )
                $val["error"] = 0;
            else
                $val["error"] = 12;
        }
        //#####################
        // 공급처 상품명
        //#####################
        else if( $field == "brand" )
        {
            $query = "update products set brand='$new_val' where product_id in ($product_list)";
            if( mysql_query($query, $connect) )
            {
                $val["error"] = 0;
                $val["product_list"] = str_replace("'","",$product_list);
            }
            else
                $val["error"] = 22;
        }
        //#####################
        // 공급처 옵션
        //#####################
        else if( $field == "supply_options" )
        {
            $query = "update products set supply_options='$new_val' where product_id='$product_id'";
            if( mysql_query($query, $connect) )
                $val["error"] = 0;
            else
                $val["error"] = 32;
        }
        //#####################
        // 상품메모
        //#####################
        else if( $field == "memo" )
        {
            $query = "update products set memo='$new_val' where product_id='$product_id'";
            if( mysql_query($query, $connect) )
                $val["error"] = 0;
            else
                $val["error"] = 42;
        }
        echo json_encode($val);
    }
    
    // 상품명 수정(가격)
    function update_product_price()
    {
        global $connect, $field, $product_id, $new_val;
        
        $val = array();
        
        // 상품코드 리스트
        $this->get_product_list($product_id, &$product_list, &$product_list_org, &$org_id);
        
        //#####################
        // 원가 수정
        //#####################
        if( $field == "org_price" )
        {
            $query = "update price_history set org_price = '$new_val', update_time = now() where product_id = $org_id and shop_id = 0";
            mysql_query($query, $connect);
            
            $query = "update products set org_price='$new_val' where product_id in ($product_list_org)";
            if( mysql_query($query, $connect) )
            {
                $val["error"] = 0;
                $val["product_list"] = str_replace("'","",$product_list);
            }
            else
                $val["error"] = 2;
        }
        $val["new_val"] = number_format($new_val);
        echo json_encode($val);
    }

    // 공급처 수정
    function change_supply()
    {
        global $connect, $supply_code, $product_id;
        
        // 상품코드 리스트
        $this->get_product_list($product_id, &$product_list, &$product_list_org, &$org_id);
        
        // 공급처 변경
        $query = "update products set supply_code=$supply_code where product_id in ($product_list_org)";
        mysql_query($query, $connect);
        
        // order_products 공급처 변경
        $query = "update order_products set supply_id=$supply_code where product_id in ($product_list_org)";
        mysql_query($query, $connect);

        $val = array();
        $val["product_list"] = str_replace("'","",$product_list);
        $val["supply_name"] = class_supply::get_name($supply_code);
        $val["group_name"] = class_supply::get_group_name($supply_code);
        echo json_encode($val);
    }
    
    // 옵션 상품코드로 전체 상품코드 리스트 구하기
    function get_product_list($product_id, &$product_list, &$product_list_org, &$org_id)
    {
        global $connect;
        
        // org_id
        $query = "select org_id from products where product_id='$product_id'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $org_id = $data[org_id];
        
        // 옵션관리상품
        if( $org_id > '' )
        {
            $query = "select product_id from products where org_id='$org_id' and is_delete=0";
            $result = mysql_query($query, $connect);
            while($data = mysql_fetch_assoc($result))
                $product_list .= ($product_list ? "," : "") . "'$data[product_id]'";
            $product_list_org = $product_list . ",'$org_id'";
        }
        // 옵션관리안함상품
        else
        {
            $product_list = "'$product_id'";
            $product_list_org = "'$product_id'";
            $org_id = "'$org_id'";
        }
    }
    
    function delete_match_info($id_list)
    {
        global $connect;
        
        $query = "select shop_id, shop_option, shop_code from code_match where id in ($id_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "delete from code_match where shop_id='$data[shop_id]' and shop_code='$data[shop_code]' and shop_option='$data[shop_option]'";
            mysql_query($query, $connect);
        }
    }
    
    function get_org_reg_date($product_id)
    {
        global $connect;
        
        
    }
}

?>
