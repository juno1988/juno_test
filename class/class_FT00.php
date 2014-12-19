<? 
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_transcorp.php";

////////////////////////////////
// class name: class_FT00
//
class class_FT00 extends class_top {

    ///////////////////////////////////////////
    function get_select_query()
	{
		global $connect		, 	$template	;
		global $select_year	,	$select_month;
		global $_tax; 
		$query = "	  SELECT	year(crdate) AS year
							,	month(crdate) AS month
							,	sum(homepage_sales_price+auction_sales_price+gmarket_sales_price+elevenst_sales_price) total_sales_price
							,	sum(homepage_sales_count+auction_sales_count+gmarket_sales_count+elevenst_sales_count) total_sales_count
							,	sum(homepage_sales_price) homepage_sales_price
							,	sum(homepage_sales_count) homepage_sales_count
							,	sum(auction_sales_price) auction_sales_price
							,	sum(auction_sales_count) auction_sales_count
							,	sum(gmarket_sales_price) gmarket_sales_price
							,	sum(gmarket_sales_count) gmarket_sales_count
							,	sum(elevenst_sales_price) elevenst_sales_price
							,	sum(elevenst_sales_count) elevenst_sales_count
							,	sum(return_price) return_price
							,	sum(return_count) return_count
							,	sum(order_box_price) order_box_price
							,	sum(etc_box_price) etc_box_price
							,	sum(fabric_price) fabric_price
						 FROM	stat_month 
						WHERE	crdate <= '$select_year-$select_month-31'
					 GROUP BY	year(crdate), month(crdate)					 
						 ";	
		
		return $query;
	}

    function FT00()
    {
		global $connect		, 	$template	;
		global $select_year	,	$select_month;
		global $_tax;
		global $arr_date;
		
		
		if(!$select_year)
		{
			$select_year = date("Y");
			$select_month = date('m');
			$_tax = "";
			$_REQUEST[select_year] 	= $select_year;
			$_REQUEST[select_month] = $select_month;
			$_REQUEST[_tax] = $_tax;
		}
		
		$query = $this->get_select_query();	
		$result = mysql_query($query, $connect);
		
		$arr_date = array();		
		while ($list = mysql_fetch_assoc( $result))
		{
			$arr_date[ $list[year] ][ $list[month] ]= array(
				"year" 					=> $list["year"					],
				"month" 				=> $list["month"				],
				"total_sales_price" 	=> $list["total_sales_price"	],
				"total_sales_count" 	=> $list["total_sales_count"	],
				"homepage_sales_price" 	=> $list["homepage_sales_price"	],
				"homepage_sales_count" 	=> $list["homepage_sales_count"	],
				"auction_sales_price" 	=> $list["auction_sales_price"	],
				"auction_sales_count" 	=> $list["auction_sales_count"	],
				"gmarket_sales_price" 	=> $list["gmarket_sales_price"	],
				"gmarket_sales_count" 	=> $list["gmarket_sales_count"	],
				"elevenst_sales_price" 	=> $list["elevenst_sales_price"	],
				"elevenst_sales_count" 	=> $list["elevenst_sales_count"	],
				"return_price" 			=> $list["return_price"			],
				"return_count" 			=> $list["return_count"			],
				"order_box_price" 		=> $list["order_box_price"		],
				"etc_box_price" 		=> $list["etc_box_price"		],
				"fabric_price"	 		=> $list["fabric_price"			],
				"return_price"	 		=> $list["return_price"			],
				"return_count"	 		=> $list["return_count"			]
			);
		}
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }
	
    function save_file_FT00()
    {
		global $connect		, 	$template	;
		global $select_year	,	$select_month;
		global $_tax;
		
		$query = $this->get_select_query();
        $result = mysql_query($query, $connect);
                
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
        	$data[return_price			] = $data[return_price			] * -1;
        	
        	if($_tax)
			{	//부가세 포함 선택시 201401 이전 자료 * 1.1
				if(($data[year] * 100) + ($data[month]) <= 201401)
				{
					$data[homepage_sales_price		] = floor($data[homepage_sales_price	] * 1.1);
					$data[auction_sales_price		] = floor($data[auction_sales_price		] * 1.1);
					$data[gmarket_sales_price		] = floor($data[gmarket_sales_price		] * 1.1);
					$data[elevenst_sales_price		] = floor($data[elevenst_sales_price	] * 1.1);
					$data[total_sales_price			] = $data[homepage_sales_price		] + $data[auction_sales_price		] + $data[gmarket_sales_price		] + $data[elevenst_sales_price		];
					$data[order_box_price			] = floor($data[order_box_price			] * 1.1);
					$data[etc_box_price				] = floor($data[etc_box_price			] * 1.1);
					$data[fabric_price				] = floor($data[fabric_price			] * 1.1);
					$data[return_price				] = floor($data[return_price			] * 1.1);
					
				}//201401이후 자료중 [기타, 주문, 원단]은 1.1배 해야함
				else if(($data[year] * 100) + ($data[month]) >= 201402)
				{
					$data[order_box_price			] = floor($data[order_box_price			] * 1.1);
					$data[etc_box_price				] = floor($data[etc_box_price			] * 1.1);
					$data[fabric_price				] = floor($data[fabric_price			] * 1.1);
				}
			}
			else
			{	//부가세 포함 미선택시 201401 이후 자료 / 1.1
				if(($data[year] * 100) + ($data[month]) >= 201402)
				{
					$data[homepage_sales_price		] = floor($data[homepage_sales_price	] *10 / 11);
					$data[auction_sales_price		] = floor($data[auction_sales_price		] *10 / 11);
					$data[gmarket_sales_price		] = floor($data[gmarket_sales_price		] *10 / 11);
					$data[elevenst_sales_price		] = floor($data[elevenst_sales_price	] *10 / 11);
					$data[return_price				] = floor($data[return_price			] *10 / 11);
					$data[total_sales_price			] = $data[homepage_sales_price		] + $data[auction_sales_price		] + $data[gmarket_sales_price		] + $data[elevenst_sales_price		];
				}
			}
			
        	$arr_temp = array();        	
			$arr_temp['year'	] =  			 $data[year					];
			$arr_temp['month'	] =  			 $data[month				];			
			$arr_temp['homepage_sales_price'] =  number_format($data[homepage_sales_price	]);
			$arr_temp['homepage_sales_count'] =  number_format($data[homepage_sales_count	]);
			$arr_temp['auction_sales_price'	] =  number_format($data[auction_sales_price	]);
			$arr_temp['auction_sales_count'	] =  number_format($data[auction_sales_count	]);
			$arr_temp['gmarket_sales_price'	] =  number_format($data[gmarket_sales_price	]);
			$arr_temp['gmarket_sales_count'	] =  number_format($data[gmarket_sales_count	]);
			$arr_temp['elevenst_sales_price'] =  number_format($data[elevenst_sales_price	]);
			$arr_temp['elevenst_sales_count'] =  number_format($data[elevenst_sales_count	]);
			$arr_temp['total_sales_price'	] =  number_format($data[total_sales_price		]);
			$arr_temp['total_sales_count'	] =  number_format($data[total_sales_count		]);
			$arr_temp['return_price'		] =  number_format($data[return_price			]);
			$arr_temp['return_count'		] =  number_format($data[return_count			]);
			$arr_temp['order_box_price'		] =  number_format($data[order_box_price		]);
			$arr_temp['etc_box_price'		] =  number_format($data[etc_box_price			]);
			$arr_temp['erp_box_price'		] =  number_format($data[etc_box_price			]+$data[order_box_price]);
			$arr_temp['total_price'			] =  number_format($data[total_sales_price		]-$data[return_price]+$data[etc_box_price]+$data[order_box_price]);
			$arr_temp['fabric_price'		] =  number_format($data[fabric_price			]);
        	$arr_datas[] = $arr_temp;
        	        	
            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }
        
        $this->make_file_FT00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
	}
	
    function make_file_FT00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
		global $connect		, 	$template	;
		global $select_year	,	$select_month;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
				$buffer .= "<td class=header_item  rowspan=2>	년		</td>";
				$buffer .= "<td class=header_item  rowspan=2>	월		</td>";
				$buffer .= "<td class=header_item  colspan=2>	홈페이지</td>";
				$buffer .= "<td class=header_item  colspan=2>	옥션	</td>";
				$buffer .= "<td class=header_item  colspan=2>	G마켓	</td>";
				$buffer .= "<td class=header_item  colspan=2>	11번가	</td>";
				$buffer .= "<td class=header_item  colspan=2>	택배박스계</td>";
				$buffer .= "<td class=header_item  colspan=2>	반품	</td>";
				$buffer .= "<td class=header_item  colspan=3>	ERP		</td>";
				$buffer .= "<td class=header_item  colspan=1>	총계	</td>";
				$buffer .= "<td class=header_item  rowspan=2>	원단매입</td>";
			$buffer .= "</tr>";
			$buffer .= "<tr>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	주문제작</td>";
				$buffer .= "<td class=header_item>	기타	</td>";
				$buffer .= "<td class=header_item>	계		</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				
			$buffer .= "</tr>";

        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
                $buffer .= "<td class=num_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        
        
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    
    function download_FT00()
    {
		global $select_year	,	$select_month;
		global $filename;
		
        $obj = new class_file();        
        $obj->download_file( $filename, $select_year."_".$select_month."_stat_file.xls");
    } 
    
    function get_FT01_query()
    {
    	global $connect		, 	$template	;
    	global $year		, 	$month		;
    	
    	$query = "SELECT		crdate
							,	(homepage_sales_price+auction_sales_price+gmarket_sales_price+elevenst_sales_price) total_sales_price
							,	(homepage_sales_count+auction_sales_count+gmarket_sales_count+elevenst_sales_count) total_sales_count
							,	homepage_sales_price
							,	homepage_sales_count
							,	auction_sales_price
							,	auction_sales_count
							,	gmarket_sales_price
							,	gmarket_sales_count
							,	elevenst_sales_price
							,	elevenst_sales_count
							,	return_price
							,	return_count
							,	order_box_price
							,	etc_box_price
							,	fabric_price
						 FROM stat_month 
						WHERE year(crdate) = $year AND month(crdate) = $month
						ORDER BY 	crdate
						";
						
		return $query;
    }
    
    function FT01()
    {
    	global $connect		, 	$template	;
    	global $year		, 	$month		;
    	
		$query = $this->get_FT01_query();	
		
		$result = mysql_query($query, $connect);
    	$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    function save_file_FT01()
    {
		global $connect		, 	$template	;
		global $year		, 	$month		;
		
		$query = $this->get_FT01_query();
        $result = mysql_query($query, $connect);
                
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
        	$data[order_box_price	] = floor($data[order_box_price	]  * 1.1);
        	$data[etc_box_price		] = floor($data[etc_box_price	]  * 1.1);
        	$data[fabric_price		] = floor($data[fabric_price	]  * 1.1);
        	
        	$arr_temp = array();        				
			$arr_temp['month'	] =  			 $data[crdate				];			
			$arr_temp['homepage_sales_price'] =  number_format($data[homepage_sales_price	]);
			$arr_temp['homepage_sales_count'] =  number_format($data[homepage_sales_count	]);
			$arr_temp['auction_sales_price'	] =  number_format($data[auction_sales_price	]);
			$arr_temp['auction_sales_count'	] =  number_format($data[auction_sales_count	]);
			$arr_temp['gmarket_sales_price'	] =  number_format($data[gmarket_sales_price	]);
			$arr_temp['gmarket_sales_count'	] =  number_format($data[gmarket_sales_count	]);
			$arr_temp['elevenst_sales_price'] =  number_format($data[elevenst_sales_price	]);
			$arr_temp['elevenst_sales_count'] =  number_format($data[elevenst_sales_count	]);
			$arr_temp['total_sales_price'	] =  number_format($data[total_sales_price		]);
			$arr_temp['total_sales_count'	] =  number_format($data[total_sales_count		]);
			$arr_temp['return_price'		] =  number_format($data[return_price			]);
			$arr_temp['return_count'		] =  number_format($data[return_count			]);
			$arr_temp['order_box_price'		] =  number_format($data[order_box_price		]);
			$arr_temp['etc_box_price'		] =  number_format($data[etc_box_price			]);
			$arr_temp['erp_box_price'		] =  number_format($data[etc_box_price			]+$data[order_box_price]);
			$arr_temp['total_price'			] =  number_format($data[total_sales_price		]+$data[return_price]+$data[etc_box_price]+$data[order_box_price]);
			$arr_temp['fabric_price'		] =  number_format($data[fabric_price			]);
        	$arr_datas[] = $arr_temp;
        	        	
            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }
        
        $this->make_file_FT01( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
	}
	
    function make_file_FT01($arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
		global $connect		, 	$template	;
		global $year	,	$month;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";				
				$buffer .= "<td class=header_item  rowspan=2>	일자	</td>";
				$buffer .= "<td class=header_item  colspan=2>	홈페이지</td>";
				$buffer .= "<td class=header_item  colspan=2>	옥션	</td>";
				$buffer .= "<td class=header_item  colspan=2>	G마켓	</td>";
				$buffer .= "<td class=header_item  colspan=2>	11번가	</td>";
				$buffer .= "<td class=header_item  colspan=2>	택배박스계</td>";
				$buffer .= "<td class=header_item  colspan=2>	반품	</td>";
				$buffer .= "<td class=header_item  colspan=3>	ERP		</td>";
				$buffer .= "<td class=header_item  colspan=1>	총계	</td>";
				$buffer .= "<td class=header_item  rowspan=2>	원단매입</td>";
			$buffer .= "</tr>";
			$buffer .= "<tr>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				$buffer .= "<td class=header_item>	수량	</td>";
				$buffer .= "<td class=header_item>	주문제작</td>";
				$buffer .= "<td class=header_item>	기타	</td>";
				$buffer .= "<td class=header_item>	계		</td>";
				$buffer .= "<td class=header_item>	매출액	</td>";
				
			$buffer .= "</tr>";

        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	if($key == "month")
                	$buffer .= "<td class=str_item>$v</td>\n";
                else 
                	$buffer .= "<td class=num_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        
        
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    
    function download_FT01()
    {
		global $select_year	,	$select_month;
		global $filename;
		
        $obj = new class_file();        
        $obj->download_file( $filename, $select_year."_".$select_month."_stat_day_file.xls");
    } 
    
}
?>