<?
// 공급자 정보
// 2008.1.30

class class_supply{
    function get_supplyid( $code )
    {
	global $connect;
	$query = "select id 
	            From userinfo 
		   where code='$code'";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );
	return $data[id];
    }
    
    function get_info( $code )
    {
	global $connect;
	$query = "select * 
	            From userinfo 
		   where code='$code'";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_assoc( $result );
	return $data;
    }
    
    function get_name( $code )
    {
	global $connect;
	$query = "select name 
	            From userinfo 
		   where code='$code'";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );
	return $data[name];
    }

    function get_name2( $code )
    {
	global $connect;
	$query = "select name 
	            From userinfo 
		   where code=(select supply_code from products where product_id='$code')";
	$result = mysql_query ( $query, $connect );
	$data   = mysql_fetch_array ( $result );
	return $data[name];
    }

    function arr_get_supply_code()
    {
		global $connect;
	
		$arr = array();
		$query = "select * from userinfo where level=0";
		$result = mysql_query( $query, $connect );
		while ( $data = mysql_fetch_assoc( $result ) )
		{
		    $arr[ $data[name] ] = $data[code];
		}
	
		return $arr;
    }
    
    function get_supply_select()
    {
		global $connect, $tag, $str, $not_in_code, $in_code, $supply_id, $name;
	
		
		$arr = array();
		$query = "select * from userinfo where level=0 ";
		
		if($str >'')
		 $query.=" and name like '%$str%'";
		
		if($not_in_code >'')
		 $query.=" and code NOT IN ($not_in_code) ";
		
		if($in_code >'')
		 $query.=" and code IN ($in_code) ";
		 
		if($name >'')
		 $query.=" and name like '%$name%' ";
		  
		$query.=" order by name";
		
		$result = mysql_query( $query, $connect );
		$str ="";
		
		
		while ( $data = mysql_fetch_assoc( $result ) )
		{
			if($supply_id == $data[code])
			{
				$str.= "<$tag value='$data[code]' selected>$data[name]</$tag>";
			}
			else
			{
			 	$str.= "<$tag value='$data[code]' >$data[name]</$tag>";
			}
		    
		}
		echo $str;
		
    }
        
    function get_group_name($code)
    {
        global $connect;
        
        $query = "select b.name b_name from userinfo a, supply_group b where a.group_id=b.group_id and a.code=$code";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
debug($data[b_name] );
        return ($data[b_name] ? $data[b_name] : "");
    }    
    function get_supply_arr()
    {
        global $connect;
        
        $info_arr = array();
        
        $query = "select a.code     a_code,
                         a.name     a_name, 
                         a.address1 a_address1,
                         a.address2 a_address2,
                         a.tel      a_tel,     
                         a.mobile   a_mobile,
                         a.email    a_email,
                         a.md       a_md,
                         a.admin    a_admin,
                         a.ez_md    a_ez_md,
                         a.ez_admin a_ez_admin,
                         a.account_number  a_account_number,
                         b.name     b_name
                    from userinfo a left outer join supply_group b on a.group_id=b.group_id";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $info_arr[$data[a_code]]["supply_name"] = $data[a_name    ];
            $info_arr[$data[a_code]]["address1"   ] = $data[a_address1];
            $info_arr[$data[a_code]]["address2"   ] = $data[a_address2];
            $info_arr[$data[a_code]]["tel"        ] = $data[a_tel     ];
            $info_arr[$data[a_code]]["mobile"     ] = $data[a_mobile  ];
            $info_arr[$data[a_code]]["email"      ] = $data[a_email   ];
            $info_arr[$data[a_code]]["group_name" ] = $data[b_name    ];
            $info_arr[$data[a_code]]["md"         ] = (_DOMAIN_ == 'tokio' ? $data[a_admin] : $data[a_md]);
            $info_arr[$data[a_code]]["admin"      ] = $data[a_admin	  ];
            $info_arr[$data[a_code]]["ez_md"      ] = $data[a_ez_md	  ];
            $info_arr[$data[a_code]]["ez_admin"      ] = $data[a_ez_admin	  ];
            $info_arr[$data[a_code]]["account_number"] = $data[a_account_number];
        }
        return $info_arr;
    }
    
    function supply_search()
    {
    	global $connect;
    	global $s_group, $query_type, $query_str, $n_group, $multi_group;
    	
    	//$query = "SELECT a.name a_name, a.code a_code, b.name b_name FROM userinfo a, supply_group b WHERE level=0 AND a.group_id=b.group_id ";
    	$query = "SELECT a.name a_name, a.code a_code, b.name b_name FROM userinfo a LEFT OUTER JOIN supply_group b ON a.group_id=b.group_id WHERE level=0 ";
    	if($query_str)
    	{
    		if($query_type==1) //이름
    			$option .= " AND a.name like '%$query_str%'";
    		else if($query_type==2) //코드
    			$option .= " AND a.code like '%$query_str%'";
    		else if($query_type==3) //아이디
    			$option .= " AND a.id like '%$query_str%'";
    	}
    	if($s_group)
    		$option .= " AND a.group_id IN ($s_group)";
    	if($n_group)
    		$option .= " AND a.code NOT IN ($n_group)";	
    	if($multi_group)
    		$option .= " AND a.code IN ($multi_group)";	
    			
    	$option .= " ORDER BY a.name";
    		
debug($query. $option);

		$result = mysql_query ( $query. $option, $connect ) or die( mysql_error() );
		$data_arr = array();
		while ( $data = mysql_fetch_array ( $result ) )
		{
			$data[b_name] == null ? $data[b_name] = "&nbsp;" : $data[b_name] = $data[b_name];
			$data_arr[] = $data;
		}
		
		echo json_encode( $data_arr );
    }
}
?>
