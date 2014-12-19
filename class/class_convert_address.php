<?

error_reporting(0);

///////////////////////////////////////////
//
//	date : 2013.12.11 
//	worker : icy
//	name : class_convert_address
//	condition : the address received can be divided into 4 values ( can be more ... )
// 				( sd_name, sgg_name, road_name, bld_no )
//  return value = new_address ( old_address ) 
//

class class_convert_address // extends class_top
{
	function connect_15_ezadmin()
	{
	    $connect = mysql_connect("66.232.146.171", "ezadmin", "pimz8282");
	    mysql_select_db( "ezadmin" , $connect );

		return $connect;
	}

	function connect_34_ezadmin()
	{
	    $connect = mysql_connect("222.231.24.79", "root", "pimz8282");
	    mysql_select_db( "ezadmin" , $connect );

		return $connect;
	}

	function convert_address( $address_in, $zip_code )
	{
//		$connect = class_convert_address::connect_15_ezadmin();
		$connect = class_convert_address::connect_34_ezadmin();

		$address_out = "";
		$zip_code = preg_replace("/[^0-9]/", "", $zip_code);
		$arr_temp = class_convert_address::change_sd_name($address_in);
		$address = $arr_temp['address'];
		$address = addslashes(str_replace(" ", "", $address));

		$query = "select *, if( bld_minor_no <> 0, 
								concat(sd_name, '%', 
									   sgg_name, '%', 
								  	   road_name,  
								  	   bld_major_no, '%',
								  	   bld_minor_no, '%'),
						        concat(sd_name, '%', 
								  	   sgg_name, '%', 
								  	   road_name,  
								  	   bld_major_no, '%'))
					        as full_road_name
					from major_address 
				   where zip_code='$zip_code' 
					 and '$address'	like
					  if ( bld_minor_no <> 0, 
							 concat(sd_name, '%', 
									sgg_name, '%', 
									road_name,  
									bld_major_no, '-',
									bld_minor_no, '%'),
							 concat(sd_name, '%', 
                                    sgg_name, '%', 
                                    road_name, '', 
                                    bld_major_no, '%')
					     )														 
				   order by length( full_road_name) desc";

//class_convert_address::debug( $query );

		$result = mysql_query ( $query, $connect );
		if ( $list = mysql_fetch_assoc( $result ) )
		{
		 	$address_out = "(";
			
			$address_out .= $list["legal_emd_name"] . " ";  
			//$address_out .= $list["adm_dong_name"] . " ";  
			$address_out .= $list["address_major_no"]; 
			if ( $list["address_minor_no"] )
				$address_out .= "-" . $list["address_minor_no"] .")" ;
			else
				$address_out .= ")";
		}
		else
			$address_out = "";		


//class_convert_address::debug( $address_in . $address_out );

		return $address_in . $address_out ;
	}

	function change_sd_name($address)
	{
		$sd_code = class_convert_address::get_sd_code();

		$arr_return = array(
			"sd_code" => "",
			"sd_name" => "",
			"address" => $address
		);

		for ( $i = 1; $i <= 3; $i++ )
		{
			$sd_name = "sd_name" . $i;
			foreach ( $sd_code as $key => $val )
			{
				if ( strpos($address, $val[$sd_name] ) !== false  )
				{
					$address = str_replace($val[$sd_name], $val['sd_name1'], $address );
					$arr_return = array( 
						"sd_code" => $key,
						"sd_name" => $val['sd_name1'],
						"address" => $address
					);
					return $arr_return;
				}
			}
		}

		return $arr_return;		
	}

	function get_sd_code()
	{
//		$connect = class_convert_address::connect_15_ezadmin();
		$connect = class_convert_address::connect_34_ezadmin();

		$query = "select * from sd_code";
		$result = mysql_query( $query, $connect );
		while ( $list = mysql_fetch_assoc( $result ) )
		{
			$sd_code[ $list['sd_code' ] ] = array(
				"sd_name1" => $list["sd_name1"],
				"sd_name2" => $list["sd_name2"],
				"sd_name3" => $list["sd_name3"]
			);
		}

		return $sd_code;
	}

	function get_zip_code($address_in)
	{
//		$connect = class_convert_address::connect_15_ezadmin();
		$connect = class_convert_address::connect_34_ezadmin();

		$arr_temp = class_convert_address::change_sd_name($address_in);

		$sd_name = $arr_temp['sd_name'];
		$address = $arr_temp['address'];
		$address = addslashes(str_replace(" ", "", $address));
		$query = "select sd_name, sgg_name, road_name, 
						 bld_major_no, bld_minor_no, zip_code
					from major_address 
				   where '$address'
							like concat(sd_name, '%', 
										sgg_name, '%', 
										road_name, '%', 
										bld_major_no, '%',
										bld_minor_no, '%')
					 and sd_name = '$sd_name'";	

		$result = mysql_query ( $query, $connect );
	
		$arr_data = array();
	
		if ( mysql_num_rows( $result ) > 0 )
		{
			while( $list = mysql_fetch_assoc( $result ) )
			{
				$arr_data[] = array(
					"sd_name" 	   => $list[sd_name],
					"sgg_name" 	   => $list[sgg_name],
					"road_name"    => $list[road_name],
					"bld_major_no" => $list[bld_major_no],
					"bld_minor_no" => $list[bld_minor_no],
					"zip_code" 	   => $list[zip_code]
				);
			}			
		}
		
		return $arr_data;
	}

	function get_zip_code_by_road_name($road_name, $bld_no)
	{
//		$connect = class_convert_address::connect_15_ezadmin();
		$connect = class_convert_address::connect_34_ezadmin();

		if ( strpos($bld_no, "-" ) != false )
		{
			$arr_bld_no = explode("-", $bld_no ); 
			$bld_major_no = trim($arr_bld_no[0]); 
			$bld_minor_no = trim($arr_bld_no[1]);
		}
		else
			$bld_major_no = trim($bld_no);

		$road_name = trim($road_name);
		$road_name = str_replace( " ", "", $road_name );

		$query = "select * 
					from major_address
				   where road_name like '$road_name%'";

		if ( $bld_major_no )
			$query .= " and bld_major_no = '$bld_major_no'";

		if ( $bld_minor_no )
			$query .= " and bld_minor_no = '$bld_minor_no'";

		$result = mysql_query( $query, $connect );
		$arr_data = array();
		if ( mysql_num_rows( $result ) > 100 ) 
		{
			$arr_data['error'] = array(
				"msg" => "검색 결과가 " . mysql_num_rows($result)  ." 건 입니다. <br> 주소를 더 자세히 입력해 주세요. "
			);
		}
		else if ( !mysql_num_rows( $result ) )
		{
			$arr_data['error'] = array(
				"msg" => "검색결과가 없습니다. <br> 주소를 더 정확히 입력해 주세요. "
			);		
		}
		else
		{
			while( $list = mysql_fetch_assoc( $result ) )
			{
				$arr_data[] = array(
					"sd_name" 	   => $list['sd_name'],
					"sgg_name" 	   => $list['sgg_name'],
					"road_name"    => $list['road_name'],
					"bld_major_no" => $list['bld_major_no'],
					"bld_minor_no" => $list['bld_minor_no'],
					"zip_code" 	   => $list['zip_code']
				);
			}			
		}
		return $arr_data;
	}

    function debug($str)
    {
        $logfile =  "/home/ezadmin/public_html/shopadmin/class/cca.log";
        $fp = @fopen($logfile, "a+");
        $output = date("Y/m/d H:i:s").$str."\n";
        @fwrite($fp, $output);
        @fclose($fp);
    }


/*
	function convert_address( $address_in ) 
	{
		$address_out = "";

		$found = false;
		while ( $found == false )
		{
			try 
			{
				$arr_address_in = class_convert_address::analyse_address( $address_in );		
				if ( count( $arr_address_in ) < 4 )
					throw new Exception('address analysis failed');
			
				$road_name_code = class_convert_address::get_road_name_code( $arr_address_in );
				$address_out = class_convert_address::get_address( $road_name_code, $arr_address_in );

				if ( $address_out )
					$found = true;
			}
			catch ( Exception $ex )
			{
				echo 'Caught exception: ' .  $ex->getMessage();
				$found = true;
			}	
		}

		return $address_in . "(" . trim($address_out) . ")" ;
	}
*/

/*
	function analyse_address( $address_in )
	{
		try
		{
			$address_in = trim( $address_in );
			$arr_address = explode(" ", $address_in );

			$arr_sd_name_code= class_convert_address::get_sd_name_code( $arr_address[0] );
			$arr_address_in["sd_name"] = $arr_sd_name_code["sd_name"];
			$arr_address_in["sd_code"] = $arr_sd_name_code["sd_code"];

			$arr_address_in["sgg_name"] = $arr_address[1];

			$road_name = "";
			for ( $i = 2; $i < count( $arr_address ) ; $i++ )
			{
				if ( strpos($arr_address[$i], "로" ) != false || strpos($arr_address[$i], "길") != false )
				{
					if ( is_numeric( substr( $arr_address[$i], 0, 1) ) == true )
					{
						$j = $i -1;
						$road_name = trim($arr_address[$j]) . trim($arr_address[$i]);
						
					}
					else
					{
						if ( $i > 3 )
						{			
						
						}
						else
						{			

						}
					}
				}
			}

			if ( $road_name != "" )
				$arr_address_in["road_name"] = $road_name;

			$arr_address_in["road_name"] = $arr_address[2];
			$arr_address_in["bld_no"] = $arr_address[3];

		}
		catch( Exception $ex )
		{

		}
		return $arr_address_in;
	}
	function analyse_address_by_meaning( $address_in )
	{
		$arr_address_in = array(
			"sd_name"   => "",
			"sd_code"   => "",
			"sgg_name"  => "",
			"road_name" => "",
			"bld_no"	=> ""
		);

		try
		{
			$found = false;

			$address_length = class_top::len_mysql( $address_in );
			while ( $found == false )
			{
				for( $i = 0; $i < $address_length; $i++ )
				{
					for ( $j = 0; $j < $address_length - $i; $j++ )
					{
						$adrs = class_top::cutstr_mysql( $address_in, $i, $j );
						if ( $adrs != "" )
						{
							echo $i . " / " . $j. " / " . $adrs . "\r\n";

						}
					}
				}

				$found = true;

			}		
		}
		catch(Exception $ex)
		{		

		}

		return $arr_address_in;
	}

	function get_sd_name_code ( $data )
	{		
		$sd_name = "";
		$sd_code = "";

		foreach ( $arr_sd_code as $key => $val )
		{		
			foreach( $val as $key2 => $val2 )
			{		
				if ( $data == $val2 )
				{
					$sd_name = $val['sd_name1'];
					$sd_code = $key;

					break;
				}
			}
		}

		$arr_return["sd_name"] = $sd_name;
		$arr_return["sd_code"] = $sd_code;

		return $arr_return; 
	}

	function get_road_name_code( $arr_address_in )
	{
		$sys_connect = class_convert_address::connect_db();
		$road_name_code = "";
		$query = "select sgg_code, road_name_no 
					from road_name_code
				   where sd_name = '$arr_address_in[sd_name]'
					 and sgg_name like '%$arr_address_in[sgg_name]%'
					 and road_name like '%$arr_address_in[road_name]%'";

		$result = mysql_query($query, $sys_connect);

		if ( mysql_num_rows( $result ) > 0 )
		{
			$list = mysql_fetch_assoc($result);
			$road_name_code = $list['sgg_code'] . $list['road_name_no'];
		}

		return $road_name_code; 
	}

	function get_address ( $road_name_code, $arr_address_in )
	{
		$sys_connect = class_convert_address::connect_db();
		$sd_code = $arr_address_in["sd_code"];
		$major_address = $sd_code . "_major_address";
		$bld_no = $arr_address_in["bld_no"];

		$address_out = "";

		$query = "select *
					from $major_address
				   where road_name_code = '$road_name_code'
					 and bld_major_no = '$bld_no'";
	
		$result = mysql_query( $query, $sys_connect );
		if ( mysql_num_rows( $result ) > 0 )
		{
			$list = mysql_fetch_assoc($result);
			$address_no = $list['address_major_no'] . "-" . $list["address_minor_no"];

			$bld_name = "";
			if ( $list['bld_ledger_name'] )
				$bld_name = $list['bld_ledger_name'];
			else if ( $list['sgg_bld_name'] )
				$bld_name = $list['sgg_bld_name'];
			else if ( $list['detail_bld_name'] )
				$bld_name = $list['detail_bld_name'];

			$address_out = $list['adm_dong_name'] . " " . $address_no . " " . $bld_name;
		}

		return $address_out; 
	}
*/
}

?>
