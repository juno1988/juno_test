<?
////////////////////////////////
// class name: class_GG00
// date: 2007.7.24
// jk.ryu

require_once "class_top.php";
require_once "class_G.php";

class class_GG00 extends class_top {

  ///////////////////////////////////////////
  // 
  function GG00()
  {
	global $template, $year, $mon;

	//===================================
	// set date
	if ( !$year) 
		$year = date(Y);

	if ( !$mon ) 
		$mon = date(m);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }

  //==============================================
  // test를 위한 template
  // date: 2007.7.23
  // jk.ryu
  function GG10()
  {
	global $template;

        $link_url = "?" . $this->build_link_url();

        if (!$start_date) 
		$start_date = date('Y-m-d', strtotime('-30 day'));

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }

  //===========================================
  // 
  // query 내용 조회
  // date: 2007.7.24
  // jk.ryu
  function requestData()
  {
	global $template, $year, $mon;

	// 정상 배송 count
	$before_normal = number_format($this->get_count( "before", "normal" ));
	$before_change = number_format($this->get_count( "before", "change" ));
	$before_cancel = number_format($this->get_count( "before", "cancel" ));

	$after_normal = number_format($this->get_count( "after", "normal" ));
	$after_change = number_format($this->get_count( "after", "change" ));
	$after_cancel = number_format($this->get_count( "after", "cancel" ));

	echo "<?xml version=\"1.0\" encoding=\"euc-kr\">
<infos>
	<info>
		<month>$year / $mon</month>
		<before_normal>$before_normal</before_normal>
		<after_normal>$after_normal</after_normal>
		<before_change>$before_change</before_change>
		<after_change>$after_change</after_change>
		<before_cancel>$before_cancel</before_cancel>
		<after_cancel>$after_cancel</after_cancel>
	</info>
</infos>
";

  }

  // gr작업
  function requestData2()
  {
	echo "

<recordset>
<travelpackage>
<country_name>cuba</country_name>
<city>cayo coco</city>
<resort>club tryp cayo coco</resort>
<resort_rating>4</resort_rating>
<resort_typeofholiday>bench</resort_typeofholiday>
<resort_watersports>true</resort_watersports>
<resort_meals>true</resort_meals>
<resort_drinks>true</resort_drinks>
<package>
<package_dateofdep>5/8/98</package_dateofdep>
<package_price>879</package_price>
</package>
</travelpackage>
<travelpackage>
<country_name>cubfdsa</country_name>
<city>cayo cofdsaco</city>
<resort>club trypdsfa cayo coco</resort>
<resort_rating>3</resort_rating>
<resort_typeofholiday>bfdsaench</resort_typeofholiday>
<resort_watersports>true</resort_watersports>
<resort_meals>true</resort_meals>
<resort_drinks>true</resort_drinks>
<package>
<package_dateofdep>5/8/99</package_dateofdep>
<package_price>678</package_price>
</package>
</travelpackage>
</recordset>

";
  }

function requestData3()
  {
	echo"
	<menus>
                <menu>
                        <title> 공지사항</title>
                        </left_menu>
                        
                </menu>
                        <title> 전체공지
                                <value1> ( </value1>
                                <value2>  </value2>
                                <value3> / </value3>
                                <value4>  </value4>
                                <value5> ) </value5>
                                <number></number>
                                <unread_number></unread_number>
                        </title>
                <menu>
        
                <menu>
                        <title> 받은작업
                                <value1> ( </value1>
                                <value2>  </value2>
                                <value3> / </value3>
                                <value4>  </value4>
                                <value5> ) </value5>
                                <number></number>
                                <unread_number></unread_number>
                        </title>
                </menu>
        
                <menu>
                        <title> 받은메시지
                                <value1> ( </value1>
                                <value2>  </value2>
                                <value3> / </value3>
                                <value4>  </value4>
                                <value5> ) </value5>
                                <number></number>
                                <unread_number></unread_number>
                        </title>
                </menu>

                <menu>
                        <title> 업무일지
                                <value1> ( </value1>
                                <value2>  </value2>
                                <value3> / </value3>
                                <value4>  </value4>
                                <value5> ) </value5>
                                <number></number>
                                <unread_number></unread_number>
                        </title>
                </menu>
    </menus>        
    <lists>                			
        	<list>
         		<title> 제목 </title>
         		<order> 작성자 </order>
               		<day> 작성일 </day>
                	<time> 작성시간 </time>
                	<read></read>
                </list>		            
                				                				
     </lists>	
                
     <content>
     		<common>
     			<title></title>
     			<read_name></read_name>
     			<text></text>
     			<day></day>
     			<time></time>
     			<write_name></write_name>
     			<common_level></common_level>
     			<common_day></common_day>
     			<common_time></common_time>     									
     			<common_complete></common_complete>
     		  </common>
     			  
     		  <businesslog>
     			<day></day>
     			<time></time>
     			<text></text>
     		</businesslog>
     </content>

";

  }


function requestData4()
  {
        echo "
		


";
  }



  function get_count( $is_trans, $type, $is_download=0 )
  {
	global $template, $year, $mon, $connect;

	//==============================================
	//
	// download할 data인지 여부 확인
	//
	if ( $is_download )
		$query = "select * from orders where ";
	else
		$query = "select count(*) cnt from orders where ";


	//=============================================
	// 배송 여부 확인
	switch ( $is_trans )
	{
		case "before";
			$query .= " status <> 8 ";
			break;
		case "after";
			$query .= " status=8 ";
			break;
	}
	
	//==============================================
	//
	// type 확인 
	//
	switch ( $type )
	{
		// 정상
		case "normal":
			$query .= " and order_cs=0 and substring(order_id,1,1) <> 'C' ";
			break;	
		case "cancel":
			$query .= " and order_cs in ( 1,2,3,4,12 ) " ;
			break;
		case "change":
			$query .= " and order_cs in( 11,5,6,7,8,13,9,10 )" ;
			break;
	}

	$begin_date = sprintf("%4d-%02d-01" ,$year , $mon);
	$end_date   = sprintf("%4d-%02d-31" ,$year , $mon);

	$query .= " and collect_date >= '$begin_date' and collect_date <= '$end_date'";

//echo $query;

	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

	return $data[cnt];
  }  
  
}

?>
